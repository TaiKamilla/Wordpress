# ---------------------------------------------------------------------------
# Keyed access + rate limiting for the JTI external "Certification API".
# ---------------------------------------------------------------------------
# Layers:
#   1. APIM product (subscription_required=true) — enforces a subscription key
#      (Ocp-Apim-Subscription-Key header). No key -> 401 at the gateway, before
#      the request ever reaches WordPress.
#   2. API policy — injects the defense-in-depth X-Gateway-Secret header and
#      points the backend at the WordPress jti/v1 REST base. Optionally adds
#      rate-limit-by-key + quota-by-key per subscription (var.enable_rate_limiting,
#      non-Consumption skus only — see tier note below).
#   3. WordPress (modules/wordpress) verifies X-Gateway-Secret in a
#      permission_callback so a direct hit to the origin (bypassing APIM) is
#      rejected even though the origin path is reachable through Front Door.
#
# The ENTIRE stack below is gated on `var.openapi_spec_url != ""` — identical
# to the API import in main.tf. With no spec URL (the default), the imported
# API doesn't exist, so there is nothing to attach a product/policy to and the
# plan stays at 0 changes. Set openapi_spec_url to activate the whole feature
# at once.
#
# Tier note (verified against Azure 2026-06-02): the CONSUMPTION sku rejects
# BOTH rate-limit-by-key AND quota-by-key ("Policy is not allowed in
# 'Consumption' sku") — and the non-keyed rate-limit/quota too. Policy-based
# rate limiting therefore requires Developer/Basic/Standard/Premium. The
# rate-limit/quota block below is gated on var.enable_rate_limiting (default
# false) so the policy is valid on Consumption (key auth + secret + backend
# only); flip it true once the sku is bumped. Subscriptions/keys, set-header
# and set-backend-service work on all tiers including Consumption.

locals {
  # Master gate: only build the keyed-API resources when the API is imported.
  api_auth_enabled = var.openapi_spec_url == "" ? 0 : 1

  # Secondary gates (still require api_auth_enabled).
  gateway_secret_enabled    = var.openapi_spec_url != "" && var.gateway_secret != "" ? 1 : 0
  origin_basic_auth_enabled = var.openapi_spec_url != "" && var.wp_origin_basic_auth_b64 != "" ? 1 : 0

  # Split openapi_spec_url into base + filename so the public docs API can
  # same-origin-proxy the spec (avoids needing CORS on the blob).
  openapi_spec_file = var.openapi_spec_url == "" ? "" : element(reverse(split("/", var.openapi_spec_url)), 0)
  openapi_spec_base = var.openapi_spec_url == "" ? "" : trimsuffix(var.openapi_spec_url, "/${local.openapi_spec_file}")

  # API-level policy. Template conditionals keep optional blocks out of the XML
  # entirely when their backing named value isn't created (an empty {{...}}
  # reference would fail policy validation at apply time).
  #
  # counter-key is the subscription id (always present here because the product
  # requires a subscription); the IP fallback only matters if the policy is ever
  # reused on an unsubscribed path.
  jti_api_policy_xml = <<-XML
    <policies>
      <inbound>
        <base />
    %{if var.enable_rate_limiting~}
        <rate-limit-by-key calls="${var.api_rate_limit_calls}" renewal-period="${var.api_rate_limit_period_seconds}" counter-key="@(context.Subscription?.Id ?? context.Request.IpAddress)" />
    %{endif~}
    %{if var.enable_quota~}
        <quota-by-key calls="${var.api_quota_calls}" renewal-period="${var.api_quota_period_seconds}" counter-key="@(context.Subscription?.Id ?? context.Request.IpAddress)" />
    %{endif~}
    %{if var.gateway_secret != ""~}
        <set-header name="X-Gateway-Secret" exists-action="override">
          <value>{{jti-gateway-secret}}</value>
        </set-header>
    %{endif~}
    %{if var.wp_origin_basic_auth_b64 != ""~}
        <set-header name="Authorization" exists-action="override">
          <value>Basic {{jti-origin-basic-auth}}</value>
        </set-header>
    %{endif~}
    %{if var.wp_api_base_url != ""~}
        <set-backend-service base-url="${var.wp_api_base_url}" />
    %{endif~}
      </inbound>
      <backend>
        <base />
      </backend>
      <outbound>
        <base />
      </outbound>
      <on-error>
        <base />
      </on-error>
    </policies>
  XML
}

# ---------------------------------------------------------------------------
# Product — enforces the subscription key.
# ---------------------------------------------------------------------------
resource "azurerm_api_management_product" "jti" {
  count = local.api_auth_enabled

  product_id            = "jti-external-api"
  display_name          = "JTI External Certification API"
  description           = "Keyed read-only access to JTI certification data for approved third parties."
  api_management_name   = azurerm_api_management.main.name
  resource_group_name   = var.resource_group_name
  subscription_required = true
  approval_required     = var.api_product_approval_required
  published             = true
  # One subscription key pair per consumer; bump if a consumer needs more.
  subscriptions_limit = 1
}

resource "azurerm_api_management_product_api" "jti" {
  count = local.api_auth_enabled

  product_id          = azurerm_api_management_product.jti[0].product_id
  api_name            = azurerm_api_management_api.jti[0].name
  api_management_name = azurerm_api_management.main.name
  resource_group_name = var.resource_group_name
}

# ---------------------------------------------------------------------------
# Named values — secrets referenced from the policy via {{name}}.
# ---------------------------------------------------------------------------
# The shared gateway secret. WordPress holds the same value in the
# JTI_API_GATEWAY_SECRET app setting and verifies the X-Gateway-Secret header.
resource "azurerm_api_management_named_value" "gateway_secret" {
  count = local.gateway_secret_enabled

  name                = "jti-gateway-secret"
  display_name        = "jti-gateway-secret"
  api_management_name = azurerm_api_management.main.name
  resource_group_name = var.resource_group_name
  secret              = true
  value               = var.gateway_secret
}

# Optional: base64("user:pass") for environments where the WP origin sits behind
# HTTP Basic Auth (staging). Empty on prod (origin is public behind Front Door).
resource "azurerm_api_management_named_value" "origin_basic_auth" {
  count = local.origin_basic_auth_enabled

  name                = "jti-origin-basic-auth"
  display_name        = "jti-origin-basic-auth"
  api_management_name = azurerm_api_management.main.name
  resource_group_name = var.resource_group_name
  secret              = true
  value               = var.wp_origin_basic_auth_b64
}

# ---------------------------------------------------------------------------
# API policy — rate limit, quota, secret injection, backend rewrite.
# ---------------------------------------------------------------------------
resource "azurerm_api_management_api_policy" "jti" {
  count = local.api_auth_enabled

  api_name            = azurerm_api_management_api.jti[0].name
  api_management_name = azurerm_api_management.main.name
  resource_group_name = var.resource_group_name
  xml_content         = local.jti_api_policy_xml

  # The XML references named values via {{...}}; APIM validates those exist at
  # apply time, so the named values must be created first. Terraform can't infer
  # this dependency from a string, so make it explicit.
  depends_on = [
    azurerm_api_management_named_value.gateway_secret,
    azurerm_api_management_named_value.origin_basic_auth,
  ]
}

# ---------------------------------------------------------------------------
# Per-consumer subscriptions (OPTIONAL — default OFF).
# ---------------------------------------------------------------------------
# Decision: subscriptions are created in the APIM portal by default, so consumer
# keys NEVER enter Terraform state. Leave var.api_consumers empty for that mode.
#
# If you'd rather manage consumers as code, populate var.api_consumers with a
# list of names; each gets an active subscription and its primary key is exposed
# as a SENSITIVE output (api_consumer_subscription_keys). Note this writes the
# keys into the state file — protect the backend accordingly.
resource "azurerm_api_management_subscription" "consumers" {
  for_each = var.openapi_spec_url == "" ? toset([]) : toset(var.api_consumers)

  display_name        = each.value
  api_management_name = azurerm_api_management.main.name
  resource_group_name = var.resource_group_name
  product_id          = azurerm_api_management_product.jti[0].id
  state               = "active"
}
