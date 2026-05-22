# Bootstrap: creates the Azure Storage Account used as the Terraform state backend.
# Run this ONCE per project. Its own state stays local (it's the chicken-and-egg).
#
# Usage:
#   terraform init
#   terraform apply
#
# Then take the output `storage_account_name` and update
# environments/{prod,staging}/backend.tf with it.

terraform {
  required_version = ">= 1.6"

  required_providers {
    azurerm = {
      source  = "hashicorp/azurerm"
      version = "~> 4.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.6"
    }
  }
}

provider "azurerm" {
  # azurerm v4 auto-registers all RPs by default, which hangs on deprecated ones
  # (Microsoft.Blueprint). We register only what we need via `az provider register`.
  resource_provider_registrations = "none"
  features {}
}

variable "location" {
  type        = string
  default     = "francecentral"
  description = "Azure region for the state storage account."
}

variable "project" {
  type        = string
  default     = "jti"
  description = "Short project identifier (used in resource names)."
}

# Random suffix to make the storage account name globally unique.
resource "random_string" "suffix" {
  length  = 6
  upper   = false
  special = false
  numeric = true
}

resource "azurerm_resource_group" "tfstate" {
  name     = "rg-${var.project}-tfstate"
  location = var.location

  tags = {
    project    = var.project
    purpose    = "terraform-state"
    managed_by = "terraform"
  }
}

resource "azurerm_storage_account" "tfstate" {
  name                     = "st${var.project}tfstate${random_string.suffix.result}"
  resource_group_name      = azurerm_resource_group.tfstate.name
  location                 = azurerm_resource_group.tfstate.location
  account_tier             = "Standard"
  account_replication_type = "LRS" # Local — cheapest. Use GRS for prod-grade durability.

  min_tls_version           = "TLS1_2"
  shared_access_key_enabled = true # Required for the azurerm backend

  blob_properties {
    versioning_enabled = true # keep history of state file changes

    delete_retention_policy {
      days = 30
    }

    container_delete_retention_policy {
      days = 30
    }
  }

  tags = {
    project    = var.project
    purpose    = "terraform-state"
    managed_by = "terraform"
  }
}

resource "azurerm_storage_container" "tfstate" {
  name                  = "tfstate"
  storage_account_id    = azurerm_storage_account.tfstate.id
  container_access_type = "private"
}

output "resource_group_name" {
  value       = azurerm_resource_group.tfstate.name
  description = "Use this in environments/*/backend.tf"
}

output "storage_account_name" {
  value       = azurerm_storage_account.tfstate.name
  description = "Use this in environments/*/backend.tf"
}

output "container_name" {
  value       = azurerm_storage_container.tfstate.name
  description = "Use this in environments/*/backend.tf"
}
