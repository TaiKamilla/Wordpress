output "front_door_url" {
  value       = module.front_door.endpoint_url
  description = "Public entry point for the JTI site."
}

output "wordpress_app_url" {
  value       = module.wordpress.app_url
  description = "Direct App Service URL (also reachable via Front Door)."
}

output "apim_gateway_url" {
  value = module.apim.gateway_url
}

output "apim_developer_portal_url" {
  value = module.apim.developer_portal_url
}

output "blob_static_endpoint" {
  value       = module.blob_storage.primary_web_endpoint
  description = "Upload Swagger UI files and the OpenAPI spec here."
}

output "mysql_fqdn" {
  value = module.wordpress.mysql_fqdn
}

output "front_door_endpoint_hostname" {
  value       = module.front_door.endpoint_hostname
  description = "The Azure-default Front Door hostname (e.g. fde-jti-prod.z01.azurefd.net). Use as the ALIAS/ANAME target for the apex custom domain."
}

output "custom_domain_validation_token" {
  value       = module.front_door.custom_domain_validation_token
  description = <<-EOT
    Token for the DNS TXT record `_dnsauth.journalismtrustinitiative.org`.
    Add this record at the registrar; Azure / DigiCert validates and issues the
    managed certificate within ~15-30 min. Routing (apex ALIAS) is independent
    and can be added separately.
  EOT
}

output "acr_login_server" {
  value       = module.container_registry.login_server
  description = "Use in your Docker push pipeline: docker push <acr_login_server>/wp-jti:<tag>"
}

output "acr_name" {
  value       = module.container_registry.name
  description = "Use with `az acr build`: az acr build --registry <acr_name> ..."
}

output "wp_files_storage_account_name" {
  value       = module.wordpress.wp_files_storage_account_name
  description = "Storage account holding wp-content. Use for `az storage file upload-batch`."
}

output "wp_content_share_name" {
  value       = module.wordpress.wp_content_share_name
  description = "Azure Files share name (mounted at /var/www/html/wp-content)."
}

output "app_service_outbound_ips" {
  value       = module.wordpress.outbound_ip_addresses
  description = "Copy these into terraform.tfvars as `app_service_outbound_ips = [...]` and re-apply to lock down MySQL to App Service IPs only."
}
