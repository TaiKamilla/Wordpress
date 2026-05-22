# JTI Infrastructure — Project Context

This file gives Claude Code the context to work on this project effectively. Claude Code reads it automatically.

## Project

**JTI** (Journalism Trust Initiative) — Azure infrastructure for the API and WordPress site, built for **Reporters Without Borders (RSF)** by **Relief Applications**. Infrastructure-as-Code lives in `infra/`.

Client contact: Benjamin Sabbah (RSF). Internal team: Raphael Bonnaud (CEO), Bertrand, Mauricio Galaviz.

## Architecture

Resources in each environment stack:

- **Azure API Management** (Consumption tier) — API gateway, imports OpenAPI spec from Blob
- **Azure Front Door Standard** — global edge with 3 routes: `/api/*` → APIM, `/static/*` → Blob, `/*` → WordPress (**PROD ONLY**; staging skips it, saves ~$35/mo)
- **Azure Blob Storage** — Swagger UI + OpenAPI definition + WP media offload target (static website enabled)
- **Azure App Service for Containers** (**B2** Linux) — dockerised WordPress (`wordpress:6.8.3-php8.3-apache` base, custom image with baked plugins + phpredis)
- **Azure Database for MySQL Flexible Server** (**B_Standard_B2s**, 2 vCPU/4 GB, `io_scaling_enabled = true`) — WP DB
- **Azure Cache for Redis** (Basic C0, 250 MB) — WP object cache; phpredis ext + `object-cache.php` drop-in baked in image
- **Azure Files share** — mounted into App Service at `/var/www/html/wp-content/uploads` only (10 GB prod, 1 GB staging)
- **Azure Container Registry** (Basic) — private Docker registry for the WP image (admin user enabled — operator lacks RBAC for MI+AcrPull)
- **Azure Monitor** — Log Analytics + App Insights + Portal Dashboard, with diagnostic settings on App Service / APIM / Front Door

WordPress uses Azure Files for uploads (native WP support). MySQL is required — WordPress does not natively support MS SQL. **Redis is tightly coupled to the object-cache.php drop-in**: removing one without the other crashes the site.

**Tier history:** Started B1 + B1ms (~$34/mo staging). Bumped to **B2 + B2s + Redis Basic C0** in May 2026 after Phase 1/2 perf work — see `infra/RUNBOOK.md` §6 for the journey.

## Decisions already made

- **IaC**: Terraform >= 1.6 with `azurerm ~> 4.0`. (Chose over OpenTofu/Pulumi for ecosystem maturity. BSL license is fine — internal-use only, not embedded in a product.)
- **Structure**: root + child modules + multi-environment (staging + prod)
- **State backend**: Azure Blob Storage. One storage account, separate state keys per env (`prod.terraform.tfstate`, `staging.terraform.tfstate`).
- **Bootstrap state**: stays **local** (chicken-and-egg with the storage account it creates).
- **Front Door**: **prod only**. Saves ~$35/mo base fee. Staging accesses App Service and APIM directly via `*.azurewebsites.net` / `*.azure-api.net`.
- **APIM tier**: `Consumption_0` (cheapest, pay-per-call). Alternatives: `Developer_1` (~$40/mo, no SLA), `Basic_1` (prod-grade ~$140/mo).
- **App Service tier**: **B2** (2 shared vCPU, 3.5 GB). Bumped from B1 in May 2026 after perf traces showed Elementor body render dominated on single B1 vCPU. P0v3 (~+$29/mo) is the next step if `<1 s` origin on logged-in pages becomes a hard requirement.
- **MySQL**: **B_Standard_B2s** (Burstable, 2 vCPU/4 GB, `io_scaling_enabled = true`). No HA, public access with Azure-services firewall rule. Acceptable for MVP. For prod-grade isolation later: VNet + Private Endpoint.
- **Redis**: **Basic C0** (~$16/mo, no SLA). For prod, consider bumping to Standard C0 (~$32/mo, 99.9 % SLA, primary/replica).
- **wp-cron**: disabled inline via `WORDPRESS_DISABLE_WP_CRON=true`. Requires external scheduler (Cloudflare Worker or cron-job.org) — see `perf-baseline/SCHEDULER_OPTIONS.md`.
- **Repo layout**: monorepo, `infra/` folder inside the JTI repo.

## File layout

```
infra/
├── README.md
├── INFRACOST.md
├── .gitignore
├── infracost.yml                          # multi-project Infracost config
├── bootstrap/
│   ├── main.tf                            # creates state storage account (random suffix)
│   └── README.md
├── modules/
│   ├── blob-storage/                      # Storage account + static website + containers
│   ├── container-registry/                # Azure Container Registry (Basic, admin auth)
│   ├── wordpress/                         # App Service Plan B2 + Linux Web App + Azure Files mount + MySQL B2s + DB + firewall + Redis env vars + DISABLE_WP_CRON toggle
│   ├── redis/                             # Azure Cache for Redis Basic C0 (Phase 2.1, May 2026)
│   ├── apim/                              # API Management with optional OpenAPI import (openapi-link)
│   ├── front-door/                        # CDN Front Door Standard + 3 origin groups + 3 routes
│   └── monitoring/                        # Log Analytics + App Insights + diagnostic settings + Portal dashboard
├── perf-baseline/                         # NEW: measurement scripts + history + plans
│   ├── measure.sh                         # cache-busted TTFB sampling, RUNS=N
│   ├── show-traces.sh                     # pulls JTI_PERF phase traces from Log Analytics
│   ├── before.json, after_*.json          # baselines per phase
│   ├── PHASE1_JSON_FIX_PLAN.md            # JSON_EXTRACT → generated column plan
│   ├── PHASE2_REDIS_INTEGRATION_PLAN.md   # Redis module + wp-config wiring plan
│   └── SCHEDULER_OPTIONS.md               # external wp-cron scheduler (REQUIRED reading)
└── environments/
    ├── staging/                           # NO front_door module
    │   ├── backend.tf, providers.tf, main.tf, variables.tf, outputs.tf
    │   ├── terraform.tfvars.example
    │   └── infracost-usage.yml
    └── prod/
        ├── (same files as staging)
        └── infracost-usage.yml
```

## Conventions

- All resources tagged with: `project`, `environment`, `managed_by`, `client`
- Naming: `<type>-<project>-<env>` or `<type>-<project>-<env>-<random_suffix>` where global uniqueness is needed
- Storage account names: `st<project><env><random_string>` (lowercase, globally unique, ≤24 chars)
- Random suffix: `random_string` (length=4, lowercase, numeric)
- MySQL password: passed via `TF_VAR_mysql_admin_password`, marked `sensitive = true` — never in `.tfvars` files committed to git
- `terraform.tfvars` is gitignored; `terraform.tfvars.example` is committed
- Always run `terraform fmt -recursive` before committing

## Important caveats

- **Infracost gap**: only supports classic `azurerm_frontdoor`, NOT the new `azurerm_cdn_frontdoor_*` we use. Front Door cost (~$42/mo prod) must be **added manually**. See `infra/INFRACOST.md`.
- **APIM provisioning is slow**: Consumption tier 30–45 min, Developer/Basic 45–90 min. `terraform apply` will sit on the APIM resource. This is normal — don't cancel.
- **Storage account names are GLOBAL across Azure**: the `random_string` suffix prevents collisions when re-running bootstrap.
- **`bootstrap/terraform.tfstate` is local** — back it up. Losing it means Terraform won't know it created those resources, but they'll still exist in Azure.

## Cost expectation (updated 2026-05-12)

Post-Phase 1/2 tier bump on staging; prod values are projections (prod not yet deployed on Azure).

| | Monthly |
|---|---|
| Staging — App B2 ($26) + MySQL B2s ($30) + Redis Basic C0 ($16) + Files ($5) + ACR ($5) + APIM/Logs ($5) | **~$87** |
| Prod (same as staging tiers) | ~$87 |
| Prod Front Door (manual, not in Infracost) | ~$44 |
| **Total** | **~$218/mo** |

For reference, pre-Phase 1/2 baseline was ~$121/mo (B1 + B1ms, no Redis). The tier bump + Redis adds ~$44/mo per environment.

Set Azure budget alerts at **$100 (staging RG)** and **$160 (prod RG)**.

## Common workflows

### First-time setup (bootstrap)

```bash
az login
cd infra/bootstrap
terraform init && terraform apply
# Note the output `storage_account_name` (has random suffix)
# Update environments/{prod,staging}/backend.tf with the value
```

### Deploy an environment

```bash
cd infra/environments/staging      # try staging first
cp terraform.tfvars.example terraform.tfvars
export TF_VAR_mysql_admin_password='<strong password>'
terraform init
terraform plan
terraform apply
```

### Cost check

```bash
cd infra
infracost breakdown --config-file infracost.yml
# Then add ~$42 for Front Door manually (prod only). See INFRACOST.md for the breakdown.
```

### Common Terraform commands

```bash
terraform fmt -recursive
terraform validate
terraform plan -out=tfplan && terraform apply tfplan
terraform state list
terraform destroy                           # careful!
```

## Personal context

- Operator: **Yafar** (Full Stack Developer at Relief Applications, based in Granada, Spain)
- Communication style: concise, direct, no excessive preamble
- Prefers commands chained with `&&`, copy-pasteable solutions
- Pushes back on workarounds — wants correct solutions, not the easy way out
- Native Spanish speaker; English for code/work fine

## When in doubt

- Read the relevant module's `variables.tf` and `outputs.tf` — they document the contract
- For Azure-specific quirks, verify against current docs (Microsoft moves fast on Front Door / APIM)
- Never auto-fill secrets — env vars or Azure Key Vault only
- Before quoting costs, check `INFRACOST.md` and remember the Front Door manual addition
- **Read `RUNBOOK.md` §6 (Performance journey log) before doing perf work** — Round 2 (May 2026) captures hard-won lessons: don't remove Redis env vars without also removing `object-cache.php`; wp-cron disabled needs a scheduler; B2 was the right tier bump and P0v3 is the next one if needed.
