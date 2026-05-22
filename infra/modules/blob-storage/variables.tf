variable "project" {
  type        = string
  description = "Short project identifier (e.g. 'jti')."
}

variable "environment" {
  type        = string
  description = "Environment name (e.g. 'prod', 'staging')."
}

variable "location" {
  type        = string
  description = "Azure region."
}

variable "resource_group_name" {
  type        = string
  description = "Resource group to deploy into."
}

variable "replication_type" {
  type        = string
  default     = "LRS"
  description = "Storage replication type. LRS for cheapest, GRS for cross-region redundancy."
  validation {
    condition     = contains(["LRS", "GRS", "ZRS", "GZRS"], var.replication_type)
    error_message = "replication_type must be one of: LRS, GRS, ZRS, GZRS."
  }
}

variable "tags" {
  type    = map(string)
  default = {}
}
