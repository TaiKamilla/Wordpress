locals {
  project     = "jti"
  environment = "prod"
  location    = "francecentral"

  tags = {
    project     = local.project
    environment = local.environment
    managed_by  = "terraform"
    client      = "rsf-jti"
  }
}

resource "azurerm_resource_group" "main" {
  name     = "rg-${local.project}-${local.environment}"
  location = local.location
  tags     = local.tags
}

# ---------- Container Registry (private Docker registry for the WP image) ----------

module "container_registry" {
  source = "../../modules/container-registry"

  project             = local.project
  environment         = local.environment
  location            = local.location
  resource_group_name = azurerm_resource_group.main.name

  # Admin user enabled — required because the operator deploying this stack
  # lacks User Access Administrator permission. Switch to false + role
  # assignment below when that permission is available (especially for prod).
  admin_enabled = true

  tags = local.tags
}

# AcrPull role assignment — only needed when admin_enabled = false on ACR.
# Requires User Access Administrator (or Owner) permission on the RG/ACR scope.
# resource "azurerm_role_assignment" "wordpress_acr_pull" {
#   count                = module.wordpress.identity_principal_id == null ? 0 : 1
#   scope                = module.container_registry.id
#   role_definition_name = "AcrPull"
#   principal_id         = module.wordpress.identity_principal_id
# }

# ---------- Storage (Swagger UI + OpenAPI spec + WP media) ----------

module "blob_storage" {
  source = "../../modules/blob-storage"

  project             = local.project
  environment         = local.environment
  location            = local.location
  resource_group_name = azurerm_resource_group.main.name

  replication_type = "LRS" # bump to GRS for cross-region durability

  tags = local.tags
}

# ---------- WordPress (App Service + MySQL) ----------

module "wordpress" {
  source = "../../modules/wordpress"

  project             = local.project
  environment         = local.environment
  location            = local.location
  resource_group_name = azurerm_resource_group.main.name

  # P1v3 (2 dedicated vCPU, 8 GB) — dedicated tier (no burstable contention) for
  # the ~2.5s Elementor homepage. Bumped from B2 on 2026-06-03 alongside staging.
  # Prod also has the Cloudflare anon edge cache (no Basic Auth here), so most
  # public hits never reach the origin; 2 dedicated cores handle the dynamic /
  # logged-in traffic. See infra/perf-baseline/.
  app_service_sku          = "P1v3"
  wordpress_image          = var.wordpress_image
  docker_registry          = module.container_registry.login_server
  docker_registry_username = module.container_registry.admin_username
  docker_registry_password = module.container_registry.admin_password

  mysql_admin_username = var.mysql_admin_username
  mysql_admin_password = var.mysql_admin_password
  # B2s (2 vCPU, 4 GB) — same tier as staging.
  mysql_sku_name   = "B_Standard_B2s"
  mysql_storage_gb = 20

  # Object cache — Phase 2.1 (~$16/mo). Big lever for logged-in /app-jti/.
  redis_host     = module.redis.hostname
  redis_port     = module.redis.ssl_port
  redis_password = module.redis.primary_access_key

  # Phase 1.3 — disable inline wp-cron. External scheduler must hit /wp-cron.php
  # every ~5 min (see infra/perf-baseline/SCHEDULER_OPTIONS.md), otherwise
  # WordPress scheduled tasks never run.
  disable_wp_cron = true

  # Prod is public — no Basic Auth gate.
  enable_basic_auth = false

  # Prod: the baked image is the single source of truth for code. Block all
  # admin-side plugin/theme/core edits via wp-admin so a "click Update" in
  # the admin can't drift the running container's wp-content vs the next
  # image build. All code changes flow through CI: edit → rebuild → deploy.
  disallow_file_mods = true

  # Lock the Web App down to Front Door traffic only.
  restrict_to_frontdoor = true

  # Custom domain is owned by Front Door in prod, not the App Service.
  # Don't pass var.custom_domain here.
  # But we DO need to tell WP multisite which apex domain it serves on:
  domain_current_site = var.custom_domain

  # Same secret as the apim module's gateway_secret so the WP plugin can verify
  # the X-Gateway-Secret header APIM injects.
  api_gateway_secret = var.gateway_secret

  tags = local.tags
}

# ---------- Redis (object cache for WordPress) ----------
# Phase 2.1 — single biggest lever for logged-in /app-jti/ perf.
# Basic C0 = 250 MB, single node, no SLA. ~$16/mo France Central.
# For prod-grade SLA, consider bumping to Standard C0 (~$32/mo, 99.9% SLA).
module "redis" {
  source = "../../modules/redis"

  project             = local.project
  environment         = local.environment
  location            = local.location
  resource_group_name = azurerm_resource_group.main.name

  sku_name = "Basic"
  family   = "C"
  capacity = 0

  tags = local.tags
}

# ---------- API Management ----------

module "apim" {
  source = "../../modules/apim"

  project             = local.project
  environment         = local.environment
  location            = local.location
  resource_group_name = azurerm_resource_group.main.name

  publisher_name  = var.apim_publisher_name
  publisher_email = var.apim_publisher_email
  sku_name        = var.apim_sku_name

  # Set this once the OpenAPI spec is uploaded to Blob Storage.
  # Example: "https://<storage>.z6.web.core.windows.net/openapi.json"
  openapi_spec_url = var.openapi_spec_url

  # Keyed external API (activates only when openapi_spec_url is set).
  gateway_secret = var.gateway_secret
  # Prod: reach WP through Front Door (the apex custom domain). FD route "/*"
  # forwards /wp-json/* to the WP origin with X-Azure-FDID, satisfying the
  # restrict_to_frontdoor origin lock — no raw App Service exposure. Empty until
  # the custom domain is configured.
  wp_api_base_url = var.custom_domain == "" ? "" : "https://${var.custom_domain}/wp-json/jti/v1"
  # Prod origin is public behind Front Door — no Basic Auth, so no creds needed.

  tags = local.tags
}

# ---------- Front Door (edge / routing) ----------

module "front_door" {
  source = "../../modules/front-door"

  project             = local.project
  environment         = local.environment
  resource_group_name = azurerm_resource_group.main.name

  wordpress_hostname = module.wordpress.app_hostname
  apim_hostname      = module.apim.gateway_hostname
  blob_hostname      = module.blob_storage.primary_web_host

  custom_domain = var.custom_domain

  tags = local.tags
}

# ---------- Monitoring ----------

module "monitoring" {
  source = "../../modules/monitoring"

  project             = local.project
  environment         = local.environment
  location            = local.location
  resource_group_name = azurerm_resource_group.main.name

  app_service_id = module.wordpress.app_service_id
  apim_id        = module.apim.id
  front_door_id  = module.front_door.id

  log_retention_days = 90 # prod: keep longer

  tags = local.tags
}
