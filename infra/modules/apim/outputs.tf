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

output "api_product_id" {
  value       = try(azurerm_api_management_product.jti[0].product_id, null)
  description = "Product id enforcing the subscription key. Null until openapi_spec_url is set."
}

output "api_docs_url" {
  value       = local.api_auth_enabled == 1 ? "${azurerm_api_management.main.gateway_url}/jti-docs" : null
  description = "Public Swagger UI URL (no key required). Null until openapi_spec_url is set."
}

output "api_consumer_subscription_keys" {
  value = {
    for name, sub in azurerm_api_management_subscription.consumers :
    name => sub.primary_key
  }
  sensitive   = true
  description = "Per-consumer primary subscription keys, ONLY when api_consumers is populated (TF-managed mode). Empty map in the default portal-managed mode. View with: terraform output -json api_consumer_subscription_keys."
}
