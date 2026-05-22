# Backend configuration: same storage account as prod, different key (separate state).

terraform {
  backend "azurerm" {
    resource_group_name  = "rg-jti-tfstate"     # ← from bootstrap output
    storage_account_name = "stjtitfstateem91ed" # ← from bootstrap output
    container_name       = "tfstate"
    key                  = "staging.terraform.tfstate"
  }
}
