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
  features {
    resource_group {
      # App Insights auto-creates a "Smart Detection" action group that Terraform
      # doesn't track. With prevent_deletion_if_contains_resources=true, RG destroys
      # hang on this orphan. Setting false lets Azure cascade-delete it cleanly.
      # Trade-off: any non-tracked resources in the RG also get destroyed without warning.
      prevent_deletion_if_contains_resources = false
    }
  }
}
