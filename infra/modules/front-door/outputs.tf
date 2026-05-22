output "id" {
  value = azurerm_cdn_frontdoor_profile.main.id
}

output "endpoint_hostname" {
  value       = azurerm_cdn_frontdoor_endpoint.main.host_name
  description = "Public Front Door hostname (e.g. fde-jti-prod.z01.azurefd.net)."
}

output "endpoint_url" {
  value = "https://${azurerm_cdn_frontdoor_endpoint.main.host_name}"
}

output "custom_domain_validation_token" {
  # The provider marks this as sensitive, but it's a public DNS-validation mechanism — wrap to expose it.
  value       = var.custom_domain == "" ? null : nonsensitive(azurerm_cdn_frontdoor_custom_domain.main[0].validation_token)
  description = "Token to use as the value of the DNS TXT record `_dnsauth.<custom_domain>`. Required for Front Door to issue the managed certificate."
}

output "custom_domain_url" {
  value       = var.custom_domain == "" ? "" : "https://${var.custom_domain}"
  description = "Public URL on the custom domain. Empty string when no custom domain is configured."
}
