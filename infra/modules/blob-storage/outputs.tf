output "id" {
  value = azurerm_storage_account.main.id
}

output "name" {
  value = azurerm_storage_account.main.name
}

output "primary_web_endpoint" {
  value       = azurerm_storage_account.main.primary_web_endpoint
  description = "Static website endpoint (https://...z6.web.core.windows.net/). Use as Front Door origin."
}

output "primary_web_host" {
  value       = azurerm_storage_account.main.primary_web_host
  description = "Static website hostname without scheme. Used in Front Door origin config."
}

output "primary_blob_endpoint" {
  value = azurerm_storage_account.main.primary_blob_endpoint
}

output "api_definition_container" {
  value = azurerm_storage_container.api_definition.name
}

output "wp_media_container" {
  value = azurerm_storage_container.wp_media.name
}
