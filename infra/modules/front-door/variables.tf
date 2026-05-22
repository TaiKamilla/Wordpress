variable "project" {
  type = string
}

variable "environment" {
  type = string
}

variable "resource_group_name" {
  type = string
}

variable "sku_name" {
  type        = string
  default     = "Standard_AzureFrontDoor"
  description = "Front Door SKU. Standard_AzureFrontDoor or Premium_AzureFrontDoor."
}

variable "wordpress_hostname" {
  type        = string
  description = "WordPress App Service hostname (no scheme, no path)."
}

variable "apim_hostname" {
  type        = string
  description = "APIM gateway hostname (no scheme, no path)."
}

variable "blob_hostname" {
  type        = string
  description = "Blob static website hostname (e.g. ...z6.web.core.windows.net)."
}

variable "custom_domain" {
  type        = string
  default     = ""
  description = <<-EOT
    Custom hostname for the Front Door endpoint (e.g. 'journalismtrustinitiative.org').
    Empty string skips the custom domain. DNS prerequisites:
    - For an apex domain: ALIAS/ANAME or A record pointing at the FD endpoint
    - For a subdomain: CNAME pointing at <endpoint>.azurefd.net
    - TXT record '_dnsauth.<domain>' = the validation token output by Front Door (visible in the portal after first apply)
    Front Door issues a free managed certificate.
  EOT
}

variable "tags" {
  type    = map(string)
  default = {}
}
