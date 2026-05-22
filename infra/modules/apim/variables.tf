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

variable "publisher_name" {
  type        = string
  description = "Name shown to API consumers (e.g. 'Reporters Without Borders')."
}

variable "publisher_email" {
  type        = string
  description = "Contact email shown to API consumers."
}

variable "sku_name" {
  type        = string
  default     = "Consumption_0"
  description = "APIM SKU. Format: '<tier>_<capacity>'. Use Consumption_0 for cheapest, Developer_1 for dev/test, Basic_1+ for prod."
}

variable "openapi_spec_url" {
  type        = string
  default     = ""
  description = "Public URL to the OpenAPI/Swagger spec (e.g. blob URL). Empty to skip API import."
}

variable "tags" {
  type    = map(string)
  default = {}
}
