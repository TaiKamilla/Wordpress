# Azure API Management.
#
# SKU choices:
# - "Consumption_0"  — serverless, pay-per-call, ~free at low traffic. CHEAPEST. No VNet, no caching.
# - "Developer_1"    — single instance, ~€40/month, full feature set, NO SLA. Dev/staging only.
# - "Basic_1"        — production-grade, ~€140/month, 99.95% SLA.
#
# Default below is Consumption for cheapness. Override per-environment as needed.

resource "azurerm_api_management" "main" {
  name                = "apim-${var.project}-${var.environment}-${random_string.suffix.result}"
  location            = var.location
  resource_group_name = var.resource_group_name

  publisher_name  = var.publisher_name
  publisher_email = var.publisher_email

  sku_name = var.sku_name

  tags = var.tags
}

resource "random_string" "suffix" {
  length  = 4
  upper   = false
  special = false
  numeric = true
}

# Imports the OpenAPI spec from Blob Storage as an APIM API.
# The spec must be publicly readable (or use a SAS URL) at the given URL.
resource "azurerm_api_management_api" "jti" {
  count = var.openapi_spec_url == "" ? 0 : 1

  name                = "jti-api"
  resource_group_name = var.resource_group_name
  api_management_name = azurerm_api_management.main.name
  revision            = "1"
  display_name        = "JTI API"
  path                = "jti"
  protocols           = ["https"]

  import {
    content_format = "openapi-link" # or "swagger-link-json" for Swagger 2.0
    content_value  = var.openapi_spec_url
  }
}
