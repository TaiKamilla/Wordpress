# Azure Blob Storage for static content (Swagger UI, OpenAPI definition)
# and WordPress media (via WP Offload Media plugin or as a separate container).

resource "azurerm_storage_account" "main" {
  name                     = "st${var.project}${var.environment}${random_string.suffix.result}"
  resource_group_name      = var.resource_group_name
  location                 = var.location
  account_tier             = "Standard"
  account_replication_type = var.replication_type
  account_kind             = "StorageV2"

  min_tls_version            = "TLS1_2"
  https_traffic_only_enabled = true

  # Enable static website hosting (serves Swagger UI directly)
  static_website {
    index_document     = "index.html"
    error_404_document = "404.html"
  }

  blob_properties {
    versioning_enabled = true
  }

  tags = var.tags
}

resource "random_string" "suffix" {
  length  = 4
  upper   = false
  special = false
  numeric = true
}

# Container for the OpenAPI definition file
resource "azurerm_storage_container" "api_definition" {
  name                  = "api-definition"
  storage_account_id    = azurerm_storage_account.main.id
  container_access_type = "blob" # public read for individual blobs (Front Door fronts it)
}

# Container for WordPress media (uploads offloaded here)
resource "azurerm_storage_container" "wp_media" {
  name                  = "wp-media"
  storage_account_id    = azurerm_storage_account.main.id
  container_access_type = "blob"
}
