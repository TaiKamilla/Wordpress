output "app_service_id" {
  value = azurerm_linux_web_app.wp.id
}

output "app_service_name" {
  value = azurerm_linux_web_app.wp.name
}

output "app_hostname" {
  value       = azurerm_linux_web_app.wp.default_hostname
  description = "Default *.azurewebsites.net hostname. Use as Front Door origin."
}

output "app_url" {
  value = "https://${azurerm_linux_web_app.wp.default_hostname}"
}

output "mysql_server_id" {
  value = azurerm_mysql_flexible_server.wp.id
}

output "mysql_fqdn" {
  value = azurerm_mysql_flexible_server.wp.fqdn
}

output "mysql_database_name" {
  value = azurerm_mysql_flexible_database.wordpress.name
}

output "identity_principal_id" {
  value       = try(azurerm_linux_web_app.wp.identity[0].principal_id, null)
  description = "App Service's system-assigned managed identity. Use as principal_id for AcrPull role assignment on the container registry. Null if the Web App was created before the identity block was added — re-apply to populate."
}

output "wp_files_storage_account_name" {
  value       = azurerm_storage_account.wp_files.name
  description = "Storage account holding the wp-content share. Use for `az storage file upload-batch` from CI."
}

output "wp_content_share_name" {
  value       = azurerm_storage_share.wp_content.name
  description = "File share name (mounted at /var/www/html/wp-content)."
}

output "outbound_ip_addresses" {
  value       = azurerm_linux_web_app.wp.outbound_ip_address_list
  description = "App Service outbound IPs. Copy into env tfvars as `app_service_outbound_ips` after first apply, then re-apply to lock down MySQL."
}

output "custom_domain_verification_id" {
  # The provider marks this as sensitive, but it's a public DNS-validation mechanism — wrap to expose it.
  value       = nonsensitive(azurerm_linux_web_app.wp.custom_domain_verification_id)
  description = "Use as the value of the TXT record 'asuid.<custom_domain>' before binding a custom hostname."
}
