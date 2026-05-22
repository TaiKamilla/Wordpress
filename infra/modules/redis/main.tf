# Azure Cache for Redis — object cache for WordPress.
#
# Replaces (in our Azure topology) what prod runs as Memcached:
#   - WP transients → RAM
#   - get_option / get_user_meta / get_post → RAM
#   - autoload bloat (~2.5 MB) → loaded once into Redis, reused per request
#
# Tier: Basic C0 = 250 MB, single-node, no SLA. ~€14-16/mo.
# For prod, bump to Standard C0 (replicated, SLA) or higher.
#
# Network: public endpoint with TLS only. The App Service connects over the
# public Azure backbone (same region → ~1-2 ms RTT). For VNet integration
# move to Premium tier later.

resource "random_string" "suffix" {
  length  = 4
  upper   = false
  special = false
  numeric = true
}

resource "azurerm_redis_cache" "wp" {
  # Suffix ensures global uniqueness AND avoids Azure's post-delete name
  # reservation (~30 days for Redis). When you replace, you get a fresh name.
  name                = "redis-${var.project}-${var.environment}-${random_string.suffix.result}"
  resource_group_name = var.resource_group_name
  location            = var.location

  capacity = var.capacity # 0 = C0 = 250 MB on Basic/Standard, P1 on Premium
  family   = var.family   # "C" for Basic/Standard, "P" for Premium
  sku_name = var.sku_name # "Basic", "Standard", or "Premium"

  non_ssl_port_enabled = false
  minimum_tls_version  = "1.2"

  # Redis cache tuning — chosen for WP object cache workload:
  # - maxmemory_policy "allkeys-lru" → automatic eviction of least-used keys when full.
  #   The default "volatile-lru" only evicts keys with a TTL. WP uses lots of keys
  #   without TTLs (object cache groups), so allkeys-lru fits better.
  redis_configuration {
    maxmemory_policy = "allkeys-lru"
  }

  tags = var.tags
}
