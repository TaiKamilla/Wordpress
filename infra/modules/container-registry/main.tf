# Azure Container Registry — private image registry for the WordPress Docker image.
# Auth: managed identity only (admin user disabled). The App Service's system-assigned
# identity gets AcrPull at the env level.

resource "random_string" "suffix" {
  length  = 4
  upper   = false
  special = false
  numeric = true
}

resource "azurerm_container_registry" "main" {
  # ACR names: 5-50 chars, alphanumeric only (no hyphens). Hence stripped name.
  name                = "acr${var.project}${var.environment}${random_string.suffix.result}"
  resource_group_name = var.resource_group_name
  location            = var.location
  sku                 = var.sku
  admin_enabled       = var.admin_enabled

  tags = var.tags
}
