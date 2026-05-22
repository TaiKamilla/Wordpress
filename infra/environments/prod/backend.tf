# Backend configuration: Terraform state lives in Azure Blob Storage.
#
# IMPORTANT: Update the values below after running `bootstrap/`.
# The bootstrap module outputs `resource_group_name` and `storage_account_name` —
# paste them here.

terraform {
  backend "azurerm" {
    resource_group_name  = "rg-jti-tfstate"     # ← from bootstrap output
    storage_account_name = "stjtitfstateem91ed" # ← from bootstrap output (random suffix!)
    container_name       = "tfstate"
    key                  = "prod.terraform.tfstate" # different key per environment
  }
}
