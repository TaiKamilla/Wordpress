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

variable "log_retention_days" {
  type        = number
  default     = 30
  description = "Log retention in Log Analytics. 30 days is the cheapest free-tier-friendly value."
}

variable "app_service_id" {
  type = string
}

variable "apim_id" {
  type = string
}

variable "front_door_id" {
  type        = string
  default     = null
  description = "Front Door profile ID. Pass null to skip Front Door diagnostic settings (e.g. in environments without Front Door)."
}

variable "tags" {
  type    = map(string)
  default = {}
}
