# Staging has no Front Door — services are accessed directly via their Azure default hostnames.

output "wordpress_app_url" {
  value       = module.wordpress.app_url
  description = "Public WordPress URL (staging entry point)."
}

output "apim_gateway_url" {
  value       = module.apim.gateway_url
  description = "APIM gateway URL (staging entry point for the API)."
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

output "app_hostname" {
  value       = module.wordpress.app_hostname
  description = "The Azure-default hostname (e.g. app-jti-staging-XXXX.azurewebsites.net). Use as the CNAME target for the custom domain."
}

output "custom_domain_verification_id" {
  value       = module.wordpress.custom_domain_verification_id
  description = <<-EOT
    Use as the value of the TXT record at `asuid.staging.journalismtrustinitiative.org`
    BEFORE setting `custom_domain` in tfvars. With the TXT in place, the binding
    can be created without the CNAME (you can add the CNAME for routing later).
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
  description = "Copy these into terraform.tfvars as `app_service_outbound_ips = [...]` and re-apply to lock down MySQL."
}
