variable "project" {
  type = string
}

variable "environment" {
  type = string
}

variable "location" {
  type = string
}

variable "resource_group_name" {
  type = string
}

# ---------------- App Service ----------------

variable "app_service_sku" {
  type        = string
  default     = "B1"
  description = "App Service Plan SKU. B1 is the cheapest 'always-on' Linux tier."
}

variable "wordpress_image" {
  type        = string
  default     = "wordpress:6.5-apache"
  description = "Docker image for WordPress. Use a pinned tag in production."
}

variable "docker_registry" {
  type        = string
  default     = "index.docker.io"
  description = "Docker registry hostname (no scheme). Default: Docker Hub."
}

variable "docker_registry_username" {
  type        = string
  default     = ""
  description = "Username for private registries that don't support managed identity (e.g., ACR with admin_enabled = true). Leave empty to fall back to managed identity."
}

variable "docker_registry_password" {
  type        = string
  default     = ""
  sensitive   = true
  description = "Password for private registries that don't support managed identity. Leave empty to fall back to managed identity."
}

# ---------------- MySQL ----------------

variable "mysql_admin_username" {
  type        = string
  description = "MySQL administrator username."
}

variable "mysql_admin_password" {
  type        = string
  sensitive   = true
  description = "MySQL administrator password. Set via TF_VAR_mysql_admin_password env var or tfvars."
}

variable "mysql_sku_name" {
  type        = string
  default     = "B_Standard_B1ms"
  description = "MySQL Flexible Server SKU. B_Standard_B1ms is the cheapest burstable tier."
}

variable "mysql_storage_gb" {
  type        = number
  default     = 20
  description = "MySQL storage in GB. Minimum 20."
}

# ---------------- Network lockdown ----------------

variable "restrict_to_frontdoor" {
  type        = bool
  default     = false
  description = "If true, restrict the App Service to traffic from AzureFrontDoor.Backend service tag only. Use when Front Door is in front."
}

variable "app_service_outbound_ips" {
  type        = list(string)
  default     = []
  description = <<-EOT
    Optional list of App Service outbound IPs to allowlist on MySQL. When empty,
    falls back to a permissive AllowAzureServices rule (any Azure tenant can attempt
    connections). Two-phase: first apply leaves it empty; after the App Service is up,
    populate via tfvars and re-apply. Get the IPs with:
      az webapp show --name <app> --resource-group <rg> --query outboundIpAddresses -o tsv | tr ',' '\n' | sort -u
  EOT
}

# ---------------- Custom domain ----------------

variable "custom_domain" {
  type        = string
  default     = ""
  description = <<-EOT
    Custom hostname to bind to the App Service. Only effective when restrict_to_frontdoor=false
    (otherwise the env's Front Door owns the custom domain). DNS records must exist before apply:
    - TXT 'asuid.<domain>' = the App Service's custom_domain_verification_id
    - CNAME '<domain>' -> '<app>.azurewebsites.net'
  EOT
}

variable "domain_current_site" {
  type        = string
  default     = ""
  description = <<-EOT
    Sets the WORDPRESS_DOMAIN_CURRENT_SITE env var. WordPress multisite uses this as the
    primary domain — when a request comes in on a different hostname, WP redirects to this one.
    For staging-without-Front-Door this is typically the same as `custom_domain`.
    For prod-with-Front-Door this is your apex/canonical domain.
    Leave empty to defer to wp-config.php's hardcoded fallback.
  EOT
}

# ---------------- wp-cron control ----------------

variable "disable_wp_cron" {
  type        = bool
  default     = false
  description = <<-EOT
    When true, sets WORDPRESS_DISABLE_WP_CRON=true on the App Service, which
    wp-config.php reads to define DISABLE_WP_CRON in WordPress. Inline wp-cron
    stops firing on user requests (removes 5-19s spikes on B1/B2). Requires an
    external scheduler to hit /wp-cron.php every few minutes — otherwise
    scheduled tasks never run. See infra/perf-baseline/SCHEDULER_OPTIONS.md.
  EOT
}

# ---------------- WordPress hardening ----------------

variable "disallow_file_mods" {
  type        = bool
  default     = false
  description = <<-EOT
    When true, sets WORDPRESS_DISALLOW_FILE_MODS=true. wp-config.php defines
    DISALLOW_FILE_MODS=true, blocking ALL admin-driven code changes: theme/
    plugin install + update + edit, WP core auto-updates, plugin/theme file
    editor. Recommended TRUE for prod (image is single source of truth — all
    code changes flow through CI). Recommended FALSE for staging (lets you
    iterate via wp-admin; the polling overlay persists changes to /persist).
  EOT
}

variable "automatic_updater_disabled" {
  type        = bool
  default     = true
  description = <<-EOT
    When true, sets WORDPRESS_AUTOMATIC_UPDATER_DISABLED=true. Blocks WP
    core's background auto-update path even when disallow_file_mods is false.
    Almost always you want this true: auto-updates on a baked image get
    reverted on every redeploy (drift), and on a writable filesystem they
    can race with your CI deploys.
  EOT
}

# ---------------- HTTP Basic Auth (image-level + runtime toggle) ----------------

variable "enable_basic_auth" {
  type        = bool
  default     = false
  description = <<-EOT
    When true, sets JTI_BASIC_AUTH=true on the App Service so the container
    entrypoint starts Apache with -DBASIC_AUTH (enforces HTTP Basic Auth on
    every non-healthcheck request). REQUIRES the image to have been built
    with --build-arg HTPASSWD_PASSWORD=<pw>; otherwise Apache fails to load
    (no .htpasswd file). Recommended TRUE for staging, FALSE for prod.
  EOT
}

# ---------------- Object cache (Redis) ----------------

variable "redis_host" {
  type        = string
  default     = ""
  description = "Hostname of the Azure Cache for Redis instance. Empty disables the Redis object cache (wp-config.php gates on WP_REDIS_HOST being set)."
}

variable "redis_port" {
  type        = number
  default     = 6380
  description = "TLS port for Redis. Default 6380 (Azure Redis SSL port)."
}

variable "redis_password" {
  type        = string
  default     = ""
  sensitive   = true
  description = "Redis primary access key (used as the AUTH password)."
}

# ---------------- Uploads ----------------

variable "uploads_quota_gb" {
  type        = number
  default     = 50
  description = "Azure Files share quota in GB for wp-content/uploads."
}

# ---------------- Misc ----------------

variable "tags" {
  type    = map(string)
  default = {}
}
