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
  description = "Public URL to the OpenAPI spec in Blob. Leave empty until uploaded."
}

# ---------- Custom domain ----------

variable "custom_domain" {
  type        = string
  default     = ""
  description = "Custom apex domain for prod. Leave empty for the first apply (gets you the FD endpoint URL), then set to 'journalismtrustinitiative.org' to register the custom domain and emit a validation_token."
}
