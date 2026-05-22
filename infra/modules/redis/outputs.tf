output "id" {
  value       = azurerm_redis_cache.wp.id
  description = "Redis resource ID. Use for diagnostic settings, role assignments, etc."
}

output "hostname" {
  value       = azurerm_redis_cache.wp.hostname
  description = "Redis hostname (e.g. redis-jti-staging-xxxx.redis.cache.windows.net)."
}

output "ssl_port" {
  value       = azurerm_redis_cache.wp.ssl_port
  description = "SSL/TLS port (always 6380). Use this; non-SSL is disabled."
}

output "primary_access_key" {
  value       = azurerm_redis_cache.wp.primary_access_key
  sensitive   = true
  description = "Primary access key — used as the password in the Redis URL."
}

output "name" {
  value = azurerm_redis_cache.wp.name
}
