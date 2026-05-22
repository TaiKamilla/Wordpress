locals {
  project     = "jti"
  environment = "staging"
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
  # lacks User Access Administrator permission (can't grant AcrPull to the
  # App Service MI). When that permission is available, set this to false and
  # uncomment the role assignment below.
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

  replication_type = "LRS"

  tags = local.tags
}

# ---------- WordPress (App Service + MySQL) ----------

module "wordpress" {
  source = "../../modules/wordpress"

  project             = local.project
  environment         = local.environment
  location            = local.location
  resource_group_name = azurerm_resource_group.main.name

  # B2 (2 shared vCPU, 3.5 GB) — bumped from B1 after Phase 1/2 perf work showed
  # homepage TTFB was CPU-bound on B1's single core. B2 dropped homepage p50
  # 4.1s → 2.7s and p90 4.9s → 3.0s. See infra/perf-baseline/.
  app_service_sku          = "B2"
  wordpress_image          = var.wordpress_image
  docker_registry          = module.container_registry.login_server
  docker_registry_username = module.container_registry.admin_username
  docker_registry_password = module.container_registry.admin_password

  mysql_admin_username = var.mysql_admin_username
  mysql_admin_password = var.mysql_admin_password
  # B2s (2 vCPU, 4 GB) — bumped from B1ms alongside the App Service upgrade.
  mysql_sku_name   = "B_Standard_B2s"
  mysql_storage_gb = 20

  # Object cache — Phase 2.1 (~$16/mo). When the redis module is removed,
  # these empty defaults disable the Redis path in wp-config.php.
  redis_host     = module.redis.hostname
  redis_port     = module.redis.ssl_port
  redis_password = module.redis.primary_access_key

  # Phase 1.3 — disable inline wp-cron. External scheduler must hit /wp-cron.php
  # every ~5 min (see infra/perf-baseline/SCHEDULER_OPTIONS.md), otherwise
  # WordPress scheduled tasks never run.
  disable_wp_cron = true

  # Staging is reached directly (no Front Door), so leave the Web App publicly
  # reachable but bind a custom hostname for friendlier URLs.
  custom_domain       = var.custom_domain
  domain_current_site = var.custom_domain

  tags = local.tags
}

# ---------- Redis (object cache for WordPress) ----------
# Phase 2.1 — single biggest lever for logged-in /app-jti/ perf.
# Basic C0 = 250 MB, single node, no SLA. ~$16/mo France Central.
# See infra/perf-baseline/PHASE2_REDIS_INTEGRATION_PLAN.md.
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

  openapi_spec_url = var.openapi_spec_url

  tags = local.tags
}

# ---------- NO Front Door in staging ----------
# Staging accesses the App Service and APIM directly via their default hostnames:
#   - WordPress: https://<app-name>.azurewebsites.net
#   - APIM:      https://<apim-name>.azure-api.net
#   - Static:    https://<storage>.z6.web.core.windows.net
#
# This saves ~$35/month (Front Door base fee). Add the front-door module
# back if you need to test edge behavior (caching, routing) in staging.

# ---------- Monitoring ----------

module "monitoring" {
  source = "../../modules/monitoring"

  project             = local.project
  environment         = local.environment
  location            = local.location
  resource_group_name = azurerm_resource_group.main.name

  app_service_id = module.wordpress.app_service_id
  apim_id        = module.apim.id
  # front_door_id intentionally not passed (no Front Door in staging)

  log_retention_days = 30

  tags = local.tags
}
