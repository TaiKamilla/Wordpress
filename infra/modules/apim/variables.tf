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

variable "publisher_name" {
  type        = string
  description = "Name shown to API consumers (e.g. 'Reporters Without Borders')."
}

variable "publisher_email" {
  type        = string
  description = "Contact email shown to API consumers."
}

variable "sku_name" {
  type        = string
  default     = "Consumption_0"
  description = "APIM SKU. Format: '<tier>_<capacity>'. Use Consumption_0 for cheapest, Developer_1 for dev/test, Basic_1+ for prod."
}

variable "openapi_spec_url" {
  type        = string
  default     = ""
  description = "Public URL to the OpenAPI/Swagger spec (e.g. blob URL). Empty to skip API import."
}

variable "tags" {
  type    = map(string)
  default = {}
}

# ---------------- Keyed API access (see api-auth.tf) ----------------
# The whole keyed-API stack activates only when openapi_spec_url is set (the
# product/policy hang off the imported API). Until then these are inert.

variable "api_product_approval_required" {
  type        = bool
  default     = false
  description = "If true, a new subscription to the JTI external API product needs admin approval before its key works. false = self-service (key active immediately on create)."
}

variable "gateway_secret" {
  type        = string
  default     = ""
  sensitive   = true
  description = <<-EOT
    Shared defense-in-depth secret. APIM injects it as the X-Gateway-Secret
    request header (via a secret named value); the WordPress plugin verifies it
    against the JTI_API_GATEWAY_SECRET app setting (set the SAME value on the
    wordpress module). Empty = no header injected (the X-Gateway-Secret layer is
    off). Never commit the value — pass via TF_VAR_gateway_secret or Key Vault.
  EOT
}

variable "wp_api_base_url" {
  type        = string
  default     = ""
  description = <<-EOT
    Backend base URL the gateway rewrites to, e.g.
    "https://<host>/wp-json/jti/v1". APIM appends each operation path
    (/certifications, /search, ...) to this. Empty = use the server URL from the
    imported OpenAPI spec unchanged.
    - staging: the App Service default hostname (direct; no Front Door).
    - prod:    the public custom domain (routed through Front Door so the
      restrict_to_frontdoor origin lock is satisfied via X-Azure-FDID).
  EOT
}

variable "wp_origin_basic_auth_b64" {
  type        = string
  default     = ""
  sensitive   = true
  description = <<-EOT
    Optional. base64("user:password") of the WP origin's HTTP Basic Auth, used
    only where the origin is gated by Basic Auth (staging). When set, APIM adds
    an `Authorization: Basic <this>` header to backend calls so it can reach the
    gated origin. Leave empty on prod (origin is public behind Front Door).
    Pass via TF_VAR — never commit.
  EOT
}

variable "enable_rate_limiting" {
  type        = bool
  default     = false
  description = <<-EOT
    Include rate-limit-by-key + quota-by-key in the API policy. MUST be false on
    the Consumption sku — Azure rejects both policies there ("Policy is not
    allowed in 'Consumption' sku"). Set true ONLY when sku_name is Developer /
    Basic / Standard / Premium. Default false so the policy applies cleanly on
    Consumption (key auth + gateway secret + backend rewrite, no rate limiting).
  EOT
}

variable "api_rate_limit_calls" {
  type        = number
  default     = 60
  description = "rate-limit-by-key: max calls per renewal period, per subscription key. Only applied when enable_rate_limiting=true (non-Consumption sku)."
}

variable "api_rate_limit_period_seconds" {
  type        = number
  default     = 60
  description = "rate-limit-by-key: renewal period in seconds (default 60 = per-minute burst limit)."
}

variable "enable_quota" {
  type        = bool
  default     = false
  description = <<-EOT
    Include quota-by-key (a longer-window cap, e.g. per-day) in the API policy.
    Independent of enable_rate_limiting so you can have a per-second rate limit
    WITHOUT also imposing a daily quota. Same Consumption-sku restriction
    applies: must be false on Consumption. Default false.
  EOT
}

variable "api_quota_calls" {
  type        = number
  default     = 10000
  description = "quota-by-key: max calls per quota period, per subscription key. Only applied when enable_quota=true."
}

variable "api_quota_period_seconds" {
  type        = number
  default     = 86400
  description = "quota-by-key: quota period in seconds (default 86400 = per-day quota)."
}

variable "api_consumers" {
  type        = list(string)
  default     = []
  description = <<-EOT
    OPTIONAL. Names of third-party consumers to create APIM subscriptions for in
    Terraform. Default empty = subscriptions are managed in the APIM portal so
    consumer keys never enter TF state (recommended). If populated, each name
    gets an active subscription and its primary key is exposed via the sensitive
    api_consumer_subscription_keys output (keys then live in state).
  EOT
}
