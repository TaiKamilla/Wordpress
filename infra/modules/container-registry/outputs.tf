output "id" {
  value       = azurerm_container_registry.main.id
  description = "Use as scope for AcrPull role assignment."
}

output "name" {
  value = azurerm_container_registry.main.name
}

output "login_server" {
  value       = azurerm_container_registry.main.login_server
  description = "Use as docker_registry input for the wordpress module (e.g., acrjtistagingABCD.azurecr.io)."
}

output "admin_username" {
  value       = azurerm_container_registry.main.admin_username
  sensitive   = true
  description = "Admin username for the ACR. Empty when admin_enabled = false."
}

output "admin_password" {
  value       = azurerm_container_registry.main.admin_password
  sensitive   = true
  description = "Admin password for the ACR. Empty when admin_enabled = false."
}
