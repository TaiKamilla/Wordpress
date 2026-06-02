# ---------- WordPress / MySQL ----------

variable "wordpress_image" {
  type        = string
  default     = "wordpress:6.5-apache"
  description = "WordPress Docker image. Pin to a specific version in production."
}

variable "mysql_admin_username" {
  type        = string
  default     = "wpadmin"
  description = "MySQL administrator username."
}

variable "mysql_admin_password" {
  type        = string
  sensitive   = true
  description = "MySQL admin password. Set via env var: TF_VAR_mysql_admin_password=..."
}

# ---------- APIM ----------

variable "apim_publisher_name" {
  type        = string
  default     = "Reporters Without Borders"
  description = "Publisher name shown to API consumers."
}

variable "apim_publisher_email" {
  type        = string
  description = "Publisher email shown to API consumers."
}

variable "apim_sku_name" {
  type        = string
  default     = "Consumption_0"
  description = "Consumption_0 (cheapest), Developer_1 (~€40/mo), Basic_1 (prod-grade)."
}

variable "openapi_spec_url" {
  type        = string
  default     = ""
  description = "Public URL to the OpenAPI spec in Blob. Leave empty until uploaded. NOTE: setting this also activates the keyed-API stack (product, key enforcement, rate limit, gateway-secret header) — see modules/apim/api-auth.tf."
}

variable "gateway_secret" {
  type        = string
  default     = ""
  sensitive   = true
  description = "Shared secret for the keyed external API. Threaded into BOTH the apim named value (X-Gateway-Secret header) and the WordPress JTI_API_GATEWAY_SECRET app setting so they always match. Pass via TF_VAR_gateway_secret — never commit. Empty = X-Gateway-Secret layer off."
}

variable "wp_origin_basic_auth_b64" {
  type        = string
  default     = ""
  sensitive   = true
  description = "OPTIONAL (staging only). base64(\"jti:<PUBLIC_ACCESS_PWD>\"). When set, APIM adds Authorization: Basic so it can reach the Basic-Auth-gated staging origin for end-to-end key tests. Pass via TF_VAR_wp_origin_basic_auth_b64. Leave empty otherwise."
}

# ---------- Custom domain ----------

variable "custom_domain" {
  type        = string
  default     = ""
  description = "Custom hostname for the staging App Service. Leave empty for the first apply, then set to 'staging.journalismtrustinitiative.org' once DNS is ready."
}
