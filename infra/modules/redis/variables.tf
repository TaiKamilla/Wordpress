variable "project" {
  type        = string
  description = "Project short name (e.g. 'jti'). Used in resource naming."
}

variable "environment" {
  type        = string
  description = "Environment short name (e.g. 'staging', 'prod')."
}

variable "location" {
  type        = string
  description = "Azure region. Match the App Service region for lowest RTT."
}

variable "resource_group_name" {
  type        = string
  description = "Resource group to create the cache in."
}

variable "sku_name" {
  type        = string
  default     = "Basic"
  description = <<-EOT
    Redis SKU tier. Choices: Basic | Standard | Premium.
    - Basic     : single node, no SLA, cheapest. Good for staging / non-critical.
    - Standard  : two nodes (master/replica), 99.9% SLA. Recommended for prod.
    - Premium   : VNet support, persistence, clustering, geo-replication.
  EOT
}

variable "family" {
  type        = string
  default     = "C"
  description = "Redis family. 'C' for Basic/Standard (small caches), 'P' for Premium."
}

variable "capacity" {
  type        = number
  default     = 0
  description = <<-EOT
    Redis capacity. For family=C: 0=250MB, 1=1GB, 2=2.5GB, 3=6GB, 4=13GB, 5=26GB, 6=53GB.
    For family=P: 1=6GB, 2=13GB, 3=26GB, 4=53GB.
    C0 (250 MB) is plenty for a typical WP site's object cache + transients.
  EOT
}

variable "tags" {
  type    = map(string)
  default = {}
}
