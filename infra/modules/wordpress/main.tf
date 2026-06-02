# WordPress on Azure App Service for Containers (Linux, B1).
# - Compute: App Service Plan (B1) running a WP Docker image
# - DB: Azure Database for MySQL Flexible Server (Burstable B1ms — cheapest)
# - Persistent uploads: Azure Files share mounted at /var/www/html/wp-content/uploads
#
# Notes:
# - B1 has no scale-to-zero (always on, ~€12-14/month).
# - For prod, consider Private Endpoint + VNet integration. Kept public here for simplicity.

# ---------------------------------------------------------------------------
# Persistent storage for wp-content/uploads (Azure Files share mounted into App Service)
# ---------------------------------------------------------------------------

resource "azurerm_storage_account" "wp_files" {
  name                     = "stwp${var.project}${var.environment}${random_string.suffix.result}"
  resource_group_name      = var.resource_group_name
  location                 = var.location
  account_tier             = "Standard"
  account_replication_type = "LRS"
  account_kind             = "StorageV2"
  min_tls_version          = "TLS1_2"

  tags = var.tags
}

resource "random_string" "suffix" {
  length  = 4
  upper   = false
  special = false
  numeric = true
}

# WordPress cryptographic salts. Generated here so they live ONLY in Terraform
# state (Azure blob backend), never in the image or git. Injected into the Web
# App as WORDPRESS_AUTH_KEY/_SALT/... env vars (see app_settings below); our
# wp-config.php reads them via getenv() with empty fallbacks.
# To rotate (invalidates all sessions): `terraform apply -replace='...wp_salt["AUTH_KEY"]'`.
resource "random_password" "wp_salt" {
  for_each = toset([
    "AUTH_KEY", "SECURE_AUTH_KEY", "LOGGED_IN_KEY", "NONCE_KEY",
    "AUTH_SALT", "SECURE_AUTH_SALT", "LOGGED_IN_SALT", "NONCE_SALT",
  ])
  length           = 64
  special          = true
  override_special = "!@#%^&*()-_=+[]{}:?."
}

resource "azurerm_storage_share" "wp_content" {
  name               = "wp-content"
  storage_account_id = azurerm_storage_account.wp_files.id
  quota              = var.uploads_quota_gb
}

# Rename of azurerm_storage_share.wp_uploads → wp_content (to reflect that it
# now holds the entire wp-content directory, not just uploads). Combined with
# the changed `name` attribute below, Terraform will destroy the old share
# and create a new one — staging share is empty/disposable so this is safe.
moved {
  from = azurerm_storage_share.wp_uploads
  to   = azurerm_storage_share.wp_content
}

# Single canonical share for everything under wp-content. Mounted at /persist
# in the Web App. Share root mirrors wp-content layout (plugins/, themes/,
# languages/, mu-plugins/, uploads/) — browsing the share = browsing wp-content.
#
# Runtime breakdown (handled by docker/entrypoint-persist.sh):
#   - uploads/ is symlinked from /var/www/html/wp-content/uploads → /persist/uploads
#     (direct write to AzFiles, zero data-loss window)
#   - plugins/ themes/ languages/ mu-plugins/ are rsync-overlaid: local copy in
#     the image for fast PHP includes, watcher syncs changes to /persist
#
# (The legacy wp_uploads_only share was removed after the consolidation —
# everything lives on wp_content now.)

# ---------------------------------------------------------------------------
# MySQL Flexible Server
# ---------------------------------------------------------------------------

resource "azurerm_mysql_flexible_server" "wp" {
  # Suffix ensures global uniqueness AND avoids Azure's post-delete name reservation
  # (~5 days). When you replace the server, the new one gets a fresh name automatically.
  name                   = "mysql-${var.project}-${var.environment}-${random_string.suffix.result}"
  resource_group_name    = var.resource_group_name
  location               = var.location
  administrator_login    = var.mysql_admin_username
  administrator_password = var.mysql_admin_password
  version                = "8.4" # LTS — supported through ~2031. Changing this ForceNews the server (data loss).

  sku_name = var.mysql_sku_name # Default: B_Standard_B1ms (cheapest burstable)
  # zone omitted — not all regions support AZ "1" (e.g. spaincentral). Let Azure pick.

  storage {
    size_gb           = var.mysql_storage_gb
    auto_grow_enabled = true
    # IOPS auto-scaling — enabled on B2s+ (no-op on B1ms). Free feature; lets
    # MySQL temporarily exceed the default 360 IOPS during bursts. The
    # azurerm provider now rejects explicit `iops` when io_scaling_enabled is
    # true (validation added in newer provider versions), so we rely on the
    # B2s default of 360 IOPS as the floor and let scaling handle the rest.
    io_scaling_enabled = true
  }

  backup_retention_days = 7

  tags = var.tags
}

resource "azurerm_mysql_flexible_database" "wordpress" {
  name                = "wordpress"
  resource_group_name = var.resource_group_name
  server_name         = azurerm_mysql_flexible_server.wp.name
  charset             = "utf8mb4"
  collation           = "utf8mb4_unicode_ci"
}

# Network policy for MySQL:
# - When `app_service_outbound_ips` is empty (first apply, or unspecified): fall back to
#   AllowAzureServices (0.0.0.0/0 special rule) — permissive but the only way to bootstrap.
# - When the list is populated: create one rule per App Service outbound IP — much tighter.
# - Long-term, replace with Private Endpoint + VNet integration (azurerm_private_endpoint).
resource "azurerm_mysql_flexible_server_firewall_rule" "azure_services_fallback" {
  count               = length(var.app_service_outbound_ips) == 0 ? 1 : 0
  name                = "AllowAzureServices"
  resource_group_name = var.resource_group_name
  server_name         = azurerm_mysql_flexible_server.wp.name
  start_ip_address    = "0.0.0.0"
  end_ip_address      = "0.0.0.0"
}

# Idempotent rename — tells Terraform that any state entry under the old address
# belongs to the new address. Safe even if no old state exists.
moved {
  from = azurerm_mysql_flexible_server_firewall_rule.azure_services
  to   = azurerm_mysql_flexible_server_firewall_rule.azure_services_fallback[0]
}

resource "azurerm_mysql_flexible_server_firewall_rule" "app_service" {
  for_each = toset(var.app_service_outbound_ips)

  name                = "AllowAppService-${replace(each.value, ".", "-")}"
  resource_group_name = var.resource_group_name
  server_name         = azurerm_mysql_flexible_server.wp.name
  start_ip_address    = each.value
  end_ip_address      = each.value
}

# ---------------------------------------------------------------------------
# App Service Plan + Web App
# ---------------------------------------------------------------------------

resource "azurerm_service_plan" "wp" {
  name                = "asp-${var.project}-${var.environment}"
  resource_group_name = var.resource_group_name
  location            = var.location
  os_type             = "Linux"
  sku_name            = var.app_service_sku # B1

  tags = var.tags
}

resource "azurerm_linux_web_app" "wp" {
  name                = "app-${var.project}-${var.environment}-${random_string.suffix.result}"
  resource_group_name = var.resource_group_name
  location            = var.location
  service_plan_id     = azurerm_service_plan.wp.id

  # System-assigned managed identity — used to pull from ACR without storing creds.
  identity {
    type = "SystemAssigned"
  }

  site_config {
    always_on = true

    # Container registry auth:
    # - When docker_registry_username is provided, use those creds (admin auth path).
    # - Otherwise fall back to the App Service managed identity (requires AcrPull
    #   role on the registry, which needs User Access Administrator to grant).
    container_registry_use_managed_identity = var.docker_registry_username == ""

    application_stack {
      docker_image_name        = var.wordpress_image
      docker_registry_url      = "https://${var.docker_registry}"
      docker_registry_username = var.docker_registry_username == "" ? null : var.docker_registry_username
      docker_registry_password = var.docker_registry_password == "" ? null : var.docker_registry_password
    }

    health_check_path                 = "/"
    health_check_eviction_time_in_min = 10
    ftps_state                        = "Disabled"
    http2_enabled                     = true
    minimum_tls_version               = "1.2"

    # Lock the Web App down to Front Door traffic only when restrict_to_frontdoor=true.
    # Without this, the *.azurewebsites.net hostname is reachable directly from the
    # internet, bypassing Front Door / WAF / caching.
    ip_restriction_default_action = var.restrict_to_frontdoor ? "Deny" : "Allow"

    dynamic "ip_restriction" {
      for_each = var.restrict_to_frontdoor ? [1] : []
      content {
        name        = "AllowFrontDoor"
        action      = "Allow"
        priority    = 100
        service_tag = "AzureFrontDoor.Backend"
      }
    }
  }

  app_settings = merge(
    {
      # WordPress core configuration (read by our custom wp-config.php via getenv())
      WORDPRESS_DB_HOST     = azurerm_mysql_flexible_server.wp.fqdn
      WORDPRESS_DB_USER     = var.mysql_admin_username
      WORDPRESS_DB_PASSWORD = var.mysql_admin_password
      WORDPRESS_DB_NAME     = azurerm_mysql_flexible_database.wordpress.name

      # App Service settings
      WEBSITES_ENABLE_APP_SERVICE_STORAGE = "false"
      WEBSITES_PORT                       = "80"
      # DOCKER_REGISTRY_SERVER_URL is managed by site_config.application_stack.docker_registry_url
      # in azurerm v4 — setting it here causes "cannot set a value for ..." error.

      # TLS to MySQL — kept as a fallback for environments still using the official
      # wordpress image's auto-generated wp-config. Our custom wp-config.php enables
      # MYSQLI_CLIENT_SSL natively when DB_HOST ends in .mysql.database.azure.com.
      WORDPRESS_CONFIG_EXTRA = "define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);"
    },
    # WordPress salts — Terraform-generated, state-only, never in source/image.
    { for k, v in random_password.wp_salt : "WORDPRESS_${k}" => v.result },
    var.domain_current_site == "" ? {} : {
      WORDPRESS_DOMAIN_CURRENT_SITE = var.domain_current_site
    },
    var.redis_host == "" ? {} : {
      # Phase 2.1 — Redis object cache. wp-config.php reads these and defines
      # WP_REDIS_* + WP_CACHE=true; object-cache.php drop-in then engages.
      WP_REDIS_HOST     = var.redis_host
      WP_REDIS_PORT     = tostring(var.redis_port)
      WP_REDIS_PASSWORD = var.redis_password
      WP_REDIS_SCHEME   = "tls"
      # Salt prevents staging<->prod key collisions if they ever share Redis.
      WP_CACHE_KEY_SALT = "${var.project}-${var.environment}:"
    },
    var.disable_wp_cron ? {
      # Phase 1.3 — disable inline wp-cron. REQUIRES an external scheduler
      # to be hitting /wp-cron.php every ~5 min (Cloudflare Worker, etc.).
      # See infra/perf-baseline/SCHEDULER_OPTIONS.md.
      WORDPRESS_DISABLE_WP_CRON = "true"
    } : {},
    var.disallow_file_mods ? {
      WORDPRESS_DISALLOW_FILE_MODS = "true"
    } : {},
    var.automatic_updater_disabled ? {
      WORDPRESS_AUTOMATIC_UPDATER_DISABLED = "true"
    } : {},
    var.enable_basic_auth ? {
      # Entrypoint reads this and starts Apache with -DBASIC_AUTH, engaging
      # the <IfDefine BASIC_AUTH> block in docker/apache.conf. REQUIRES the
      # image to have been built with --build-arg HTPASSWD_PASSWORD=<pw>.
      JTI_BASIC_AUTH = "true"
    } : {},
    var.api_gateway_secret == "" ? {} : {
      # Shared secret for the keyed external API. The jti-custom plugin verifies
      # the APIM-injected X-Gateway-Secret header against this in its
      # permission_callback. Must match the apim module's gateway_secret. When
      # unset (this branch absent), the plugin leaves jti/v1 open.
      JTI_API_GATEWAY_SECRET = var.api_gateway_secret
    },
  )

  # Single mount: the wp-content share at /persist. Container's
  # docker/entrypoint-persist.sh handles the rest (symlink uploads, overlay
  # plugins/themes/etc., background watcher). See the share resource above
  # for the full rationale.
  storage_account {
    name         = "wp-content"
    type         = "AzureFiles"
    account_name = azurerm_storage_account.wp_files.name
    share_name   = azurerm_storage_share.wp_content.name
    access_key   = azurerm_storage_account.wp_files.primary_access_key
    mount_path   = "/persist"
  }

  https_only = true

  tags = var.tags
}

# ---------------------------------------------------------------------------
# Custom hostname (only when no Front Door in front of this Web App)
# ---------------------------------------------------------------------------
# Skipped when restrict_to_frontdoor=true (Front Door owns the custom domain in that case).
# DNS prerequisites:
#   TXT 'asuid.<custom_domain>' = azurerm_linux_web_app.wp.custom_domain_verification_id
#   CNAME '<custom_domain>'     -> azurerm_linux_web_app.wp.default_hostname
# Set both before apply, otherwise the binding will fail with a verification error.

locals {
  enable_app_service_custom_domain = var.custom_domain != "" && !var.restrict_to_frontdoor
}

resource "azurerm_app_service_custom_hostname_binding" "main" {
  count               = local.enable_app_service_custom_domain ? 1 : 0
  hostname            = var.custom_domain
  app_service_name    = azurerm_linux_web_app.wp.name
  resource_group_name = var.resource_group_name

  # Cert binding is set separately by azurerm_app_service_certificate_binding below.
  lifecycle {
    ignore_changes = [ssl_state, thumbprint]
  }
}

resource "azurerm_app_service_managed_certificate" "main" {
  count                      = local.enable_app_service_custom_domain ? 1 : 0
  custom_hostname_binding_id = azurerm_app_service_custom_hostname_binding.main[0].id
}

resource "azurerm_app_service_certificate_binding" "main" {
  count               = local.enable_app_service_custom_domain ? 1 : 0
  hostname_binding_id = azurerm_app_service_custom_hostname_binding.main[0].id
  certificate_id      = azurerm_app_service_managed_certificate.main[0].id
  ssl_state           = "SniEnabled"
}
