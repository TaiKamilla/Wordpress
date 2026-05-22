# JTI Infrastructure

Terraform configuration for the JTI (Journalism Trust Initiative) Azure infrastructure.

## Architecture

- **Azure API Management (APIM)** — API gateway, hosts the OpenAPI spec
- **Azure Front Door** — global edge, routes traffic to APIM / Blob / WordPress (**prod only**)
- **Azure Blob Storage** — Swagger UI + OpenAPI definition + WP media
- **Azure App Service for Containers** — dockerised WordPress (B1 tier)
- **Azure Database for MySQL Flexible Server** — WordPress DB
- **Azure Monitor + Dashboard** — observability

### Environment differences

| Resource | Staging | Prod |
|---|---|---|
| Front Door | ❌ skipped (saves ~$35/mo) | ✅ |
| Log retention | 30 days | 90 days |
| MySQL/App Service SKU | same (B1 / B1ms) | same |

Staging traffic hits the App Service and APIM directly via their `*.azurewebsites.net` and `*.azure-api.net` hostnames — fine for internal testing.

## Layout

```
infra/
├── bootstrap/              # one-time: creates the state storage account
├── modules/                # reusable building blocks
│   ├── blob-storage/
│   ├── wordpress/          # App Service + MySQL + Azure Files
│   ├── apim/
│   ├── front-door/
│   └── monitoring/
└── environments/
    ├── staging/
    └── prod/
```

Each environment has its own state file (same storage account, different blob key).

## Prerequisites

- Terraform >= 1.6 — https://developer.hashicorp.com/terraform/install
- Azure CLI — https://learn.microsoft.com/cli/azure/install-azure-cli
- Logged in: `az login` and `az account set --subscription <SUB_ID>`

## First-time setup (bootstrap)

The bootstrap creates the storage account that holds Terraform state. Run **once per project**.

```bash
cd infra/bootstrap
terraform init
terraform apply
```

Note the output `storage_account_name`. Update it in:
- `infra/environments/prod/backend.tf`
- `infra/environments/staging/backend.tf`

## Deploying an environment

```bash
cd infra/environments/prod      # or staging

# Copy the example tfvars and fill in real values
cp terraform.tfvars.example terraform.tfvars

terraform init                  # connects to the Azure Blob backend
terraform plan                  # review changes
terraform apply                 # apply
```

## Secrets

**Never commit `terraform.tfvars`** if it contains passwords. It's in `.gitignore`.

For real secrets (MySQL password, etc.), use one of:
- Environment variables: `export TF_VAR_mysql_admin_password=...`
- Azure Key Vault (recommended for prod) — referenced in Terraform via the `azurerm_key_vault_secret` data source

## Useful commands

```bash
terraform fmt -recursive        # format all .tf files
terraform validate              # syntax check
terraform plan -out=tfplan      # save plan
terraform apply tfplan          # apply saved plan
terraform destroy               # tear down (careful!)
terraform state list            # see what Terraform manages
```

## Cost estimates

See [INFRACOST.md](./INFRACOST.md) for the full cost workflow. TL;DR:

```bash
infracost breakdown --config-file infracost.yml
```

Then add ~$42/mo for Front Door manually (Infracost doesn't price the new tier).
