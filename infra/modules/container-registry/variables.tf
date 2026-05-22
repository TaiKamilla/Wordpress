variable "project" {
  type = string
}

variable "environment" {
  type = string
}

variable "location" {
  type = string
}

variable "resource_group_name" {
  type = string
}

variable "sku" {
  type        = string
  default     = "Basic"
  description = "ACR SKU. Basic is cheapest (~$5/mo). Standard adds geo-replication, Premium adds private link."
  validation {
    condition     = contains(["Basic", "Standard", "Premium"], var.sku)
    error_message = "sku must be Basic, Standard, or Premium."
  }
}

variable "admin_enabled" {
  type        = bool
  default     = false
  description = "Enable the ACR admin user. Required when the App Service identity can't be granted AcrPull (e.g., the Terraform operator lacks User Access Administrator). Set to false once managed identity auth is in place — admin creds are long-lived passwords."
}

variable "tags" {
  type    = map(string)
  default = {}
}
