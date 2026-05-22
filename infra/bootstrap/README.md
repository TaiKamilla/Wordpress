# Bootstrap

One-time setup. Creates the Azure Storage Account that holds Terraform state for `prod` and `staging`.

## Run

```bash
terraform init
terraform apply
```

## After apply

1. Note the outputs (`resource_group_name`, `storage_account_name`, `container_name`).
2. Edit `infra/environments/prod/backend.tf` and `infra/environments/staging/backend.tf` and replace the placeholders with these values.
3. Commit the updated `backend.tf` files (they don't contain secrets).

## State for the bootstrap itself

The bootstrap stores its own state **locally** (`bootstrap/terraform.tfstate`) because it's creating the very thing remote state would live in. Keep this state file safe — losing it means Terraform won't know it created these resources, but they'll still exist in Azure (you'd have to import or recreate).

For team setups, commit it to a private location (NOT git) or have one person own it. For learning, local is fine.
