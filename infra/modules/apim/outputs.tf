output "id" {
  value = azurerm_api_management.main.id
}

output "name" {
  value = azurerm_api_management.main.name
}

output "gateway_url" {
  value = azurerm_api_management.main.gateway_url
}

output "gateway_hostname" {
  value       = replace(replace(azurerm_api_management.main.gateway_url, "https://", ""), "/", "")
  description = "Gateway hostname without scheme. Use as Front Door origin."
}

output "developer_portal_url" {
  value = azurerm_api_management.main.developer_portal_url
}
