# ---------- WordPress / MySQL ----------

variable "wordpress_image" {
  type        = string
  default     = ""
  description = <<-EOT
    Fully-qualified Docker image for the WordPress container, e.g.
    "acrjtiprodXXXX.azurecr.io/wp-jti:v1.0.0". Pin a specific tag in
    production (never :latest — you lose rollback safety and risk a
    redeploy pulling a half-baked image).
    Bootstrap order:
      1. First `terraform apply` with this empty: creates the prod ACR.
      2. `az acr build --registry <prod-acr> --image wp-jti:v1.0.0 .`
         (omit --build-arg HTPASSWD_PASSWORD — prod runs without Basic Auth.)
      3. Set this var to the full URL + tag, re-apply.
  EOT
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
