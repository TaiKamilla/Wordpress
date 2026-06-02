# ---------------------------------------------------------------------------
# Public API documentation (Swagger UI) — NO subscription key required.
# ---------------------------------------------------------------------------
# Requirement: the docs page is public, but actual data calls fail without a
# valid key. The keyed data API lives in api-auth.tf (subscription_required via
# the product). This is a SEPARATE API with subscription_required = false, so
# anyone can load the docs; the "Try it out" button fires at the keyed data API
# and returns 401 without a subscription key.
#
# Two operations, both unkeyed:
#   GET /            -> returns a self-contained Swagger UI HTML page
#   GET /openapi.json -> same-origin proxy to the public spec blob (so the UI
#                        fetches the spec without a cross-origin/CORS problem)
#
# Gated on the same openapi_spec_url condition as the rest of the keyed stack.

resource "azurerm_api_management_api" "jti_docs" {
  count = local.api_auth_enabled

  name                  = "jti-docs-api"
  resource_group_name   = var.resource_group_name
  api_management_name   = azurerm_api_management.main.name
  revision              = "1"
  display_name          = "JTI API Documentation"
  path                  = "jti-docs"
  protocols             = ["https"]
  subscription_required = false # PUBLIC — no key to view the docs
}

resource "azurerm_api_management_api_operation" "docs_ui" {
  count = local.api_auth_enabled

  operation_id        = "get-docs-ui"
  api_name            = azurerm_api_management_api.jti_docs[0].name
  api_management_name = azurerm_api_management.main.name
  resource_group_name = var.resource_group_name
  display_name        = "Swagger UI"
  method              = "GET"
  url_template        = "/"
}

resource "azurerm_api_management_api_operation" "docs_spec" {
  count = local.api_auth_enabled

  operation_id        = "get-openapi-json"
  api_name            = azurerm_api_management_api.jti_docs[0].name
  api_management_name = azurerm_api_management.main.name
  resource_group_name = var.resource_group_name
  display_name        = "OpenAPI spec"
  method              = "GET"
  url_template        = "/openapi.json"
}

# Swagger UI page. The spec URL is computed from the request path in-browser so
# it works both directly (https://apim/jti-docs) and behind Front Door
# (https://host/api/jti-docs) — it always appends "/openapi.json" to the
# current path, hitting the proxy operation below on the same origin.
resource "azurerm_api_management_api_operation_policy" "docs_ui" {
  count = local.api_auth_enabled

  api_name            = azurerm_api_management_api.jti_docs[0].name
  api_management_name = azurerm_api_management.main.name
  resource_group_name = var.resource_group_name
  operation_id        = azurerm_api_management_api_operation.docs_ui[0].operation_id

  xml_content = <<-XML
    <policies>
      <inbound>
        <base />
        <return-response>
          <set-status code="200" reason="OK" />
          <set-header name="Content-Type" exists-action="override">
            <value>text/html; charset=utf-8</value>
          </set-header>
          <set-body><![CDATA[<!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>JTI Certification API — Documentation</title>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css" />
    </head>
    <body>
      <div id="swagger-ui"></div>
      <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
      <script>
        window.onload = function () {
          var base = window.location.pathname.replace(/\/+$/, "");
          window.ui = SwaggerUIBundle({
            url: base + "/openapi.json",
            dom_id: "#swagger-ui"
          });
        };
      </script>
    </body>
    </html>]]></set-body>
        </return-response>
      </inbound>
      <backend><base /></backend>
      <outbound><base /></outbound>
      <on-error><base /></on-error>
    </policies>
  XML
}

# Same-origin proxy of the public OpenAPI spec blob. Keeps the UI's spec fetch
# on the APIM origin so no CORS config is needed on the blob.
resource "azurerm_api_management_api_operation_policy" "docs_spec" {
  count = local.api_auth_enabled

  api_name            = azurerm_api_management_api.jti_docs[0].name
  api_management_name = azurerm_api_management.main.name
  resource_group_name = var.resource_group_name
  operation_id        = azurerm_api_management_api_operation.docs_spec[0].operation_id

  xml_content = <<-XML
    <policies>
      <inbound>
        <base />
        <set-backend-service base-url="${local.openapi_spec_base}" />
        <rewrite-uri template="/${local.openapi_spec_file}" />
      </inbound>
      <backend><base /></backend>
      <outbound>
        <base />
        <set-header name="Content-Type" exists-action="override">
          <value>application/json</value>
        </set-header>
      </outbound>
      <on-error><base /></on-error>
    </policies>
  XML
}
