# Azure Front Door (Standard tier).
#
# Routes:
#   /api/*       -> APIM (gateway)
#   /static/*    -> Blob Storage static website
#   /*           -> WordPress (App Service)
#
# Standard tier is sufficient for most use cases. Premium adds WAF Bot Protection
# and Private Link to origins (~5x cost).

resource "azurerm_cdn_frontdoor_profile" "main" {
  name                = "afd-${var.project}-${var.environment}"
  resource_group_name = var.resource_group_name
  sku_name            = var.sku_name # Default: Standard_AzureFrontDoor

  tags = var.tags
}

resource "azurerm_cdn_frontdoor_endpoint" "main" {
  name                     = "fde-${var.project}-${var.environment}"
  cdn_frontdoor_profile_id = azurerm_cdn_frontdoor_profile.main.id
  tags                     = var.tags
}

# ---------------- Custom domain (optional) ----------------
# Created only when var.custom_domain is non-empty. After apply, take the
# `validation_token` output and create a TXT record `_dnsauth.<domain>` with
# its value, then point the domain at the endpoint via CNAME (or ALIAS for apex).

resource "azurerm_cdn_frontdoor_custom_domain" "main" {
  count                    = var.custom_domain == "" ? 0 : 1
  name                     = "fdc-${var.project}-${var.environment}"
  cdn_frontdoor_profile_id = azurerm_cdn_frontdoor_profile.main.id
  host_name                = var.custom_domain

  tls {
    certificate_type    = "ManagedCertificate"
    minimum_tls_version = "TLS12"
  }
}

locals {
  custom_domain_ids = var.custom_domain == "" ? [] : [azurerm_cdn_frontdoor_custom_domain.main[0].id]
}

# ---------------- Origin group: WordPress ----------------

resource "azurerm_cdn_frontdoor_origin_group" "wordpress" {
  name                     = "og-wordpress"
  cdn_frontdoor_profile_id = azurerm_cdn_frontdoor_profile.main.id
  session_affinity_enabled = false

  load_balancing {
    sample_size                 = 4
    successful_samples_required = 3
  }

  health_probe {
    interval_in_seconds = 100
    path                = "/"
    protocol            = "Https"
    request_type        = "HEAD"
  }
}

resource "azurerm_cdn_frontdoor_origin" "wordpress" {
  name                          = "origin-wordpress"
  cdn_frontdoor_origin_group_id = azurerm_cdn_frontdoor_origin_group.wordpress.id

  enabled                        = true
  host_name                      = var.wordpress_hostname
  http_port                      = 80
  https_port                     = 443
  origin_host_header             = var.wordpress_hostname
  priority                       = 1
  weight                         = 1000
  certificate_name_check_enabled = true
}

# ---------------- Origin group: APIM ----------------

resource "azurerm_cdn_frontdoor_origin_group" "apim" {
  name                     = "og-apim"
  cdn_frontdoor_profile_id = azurerm_cdn_frontdoor_profile.main.id
  session_affinity_enabled = false

  load_balancing {
    sample_size                 = 4
    successful_samples_required = 3
  }

  health_probe {
    interval_in_seconds = 100
    path                = "/status-0123456789abcdef"
    protocol            = "Https"
    request_type        = "GET"
  }
}

resource "azurerm_cdn_frontdoor_origin" "apim" {
  name                          = "origin-apim"
  cdn_frontdoor_origin_group_id = azurerm_cdn_frontdoor_origin_group.apim.id

  enabled                        = true
  host_name                      = var.apim_hostname
  http_port                      = 80
  https_port                     = 443
  origin_host_header             = var.apim_hostname
  priority                       = 1
  weight                         = 1000
  certificate_name_check_enabled = true
}

# ---------------- Origin group: Blob (static content) ----------------

resource "azurerm_cdn_frontdoor_origin_group" "blob" {
  name                     = "og-blob"
  cdn_frontdoor_profile_id = azurerm_cdn_frontdoor_profile.main.id
  session_affinity_enabled = false

  load_balancing {
    sample_size                 = 4
    successful_samples_required = 3
  }

  health_probe {
    interval_in_seconds = 100
    path                = "/"
    protocol            = "Https"
    request_type        = "HEAD"
  }
}

resource "azurerm_cdn_frontdoor_origin" "blob" {
  name                          = "origin-blob"
  cdn_frontdoor_origin_group_id = azurerm_cdn_frontdoor_origin_group.blob.id

  enabled                        = true
  host_name                      = var.blob_hostname
  http_port                      = 80
  https_port                     = 443
  origin_host_header             = var.blob_hostname
  priority                       = 1
  weight                         = 1000
  certificate_name_check_enabled = true
}

# ---------------- Routes ----------------

resource "azurerm_cdn_frontdoor_route" "api" {
  name                          = "route-api"
  cdn_frontdoor_endpoint_id     = azurerm_cdn_frontdoor_endpoint.main.id
  cdn_frontdoor_origin_group_id = azurerm_cdn_frontdoor_origin_group.apim.id
  cdn_frontdoor_origin_ids      = [azurerm_cdn_frontdoor_origin.apim.id]

  supported_protocols             = ["Http", "Https"]
  patterns_to_match               = ["/api/*"]
  forwarding_protocol             = "HttpsOnly"
  link_to_default_domain          = true
  https_redirect_enabled          = true
  cdn_frontdoor_custom_domain_ids = local.custom_domain_ids
}

resource "azurerm_cdn_frontdoor_route" "static" {
  name                          = "route-static"
  cdn_frontdoor_endpoint_id     = azurerm_cdn_frontdoor_endpoint.main.id
  cdn_frontdoor_origin_group_id = azurerm_cdn_frontdoor_origin_group.blob.id
  cdn_frontdoor_origin_ids      = [azurerm_cdn_frontdoor_origin.blob.id]

  supported_protocols             = ["Http", "Https"]
  patterns_to_match               = ["/static/*"]
  forwarding_protocol             = "HttpsOnly"
  link_to_default_domain          = true
  https_redirect_enabled          = true
  cdn_frontdoor_custom_domain_ids = local.custom_domain_ids
}

resource "azurerm_cdn_frontdoor_route" "wordpress" {
  name                          = "route-wordpress"
  cdn_frontdoor_endpoint_id     = azurerm_cdn_frontdoor_endpoint.main.id
  cdn_frontdoor_origin_group_id = azurerm_cdn_frontdoor_origin_group.wordpress.id
  cdn_frontdoor_origin_ids      = [azurerm_cdn_frontdoor_origin.wordpress.id]

  supported_protocols             = ["Http", "Https"]
  patterns_to_match               = ["/*"] # catch-all
  forwarding_protocol             = "HttpsOnly"
  link_to_default_domain          = true
  https_redirect_enabled          = true
  cdn_frontdoor_custom_domain_ids = local.custom_domain_ids
}
