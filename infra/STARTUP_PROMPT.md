# Startup Prompt for Claude Code

If you're using Claude Code in this repo, **`CLAUDE.md` is read automatically** — you don't need this file. Just open Claude Code and start chatting.

This prompt is here as a fallback for:
- Other AI tools that don't auto-read `CLAUDE.md`
- Manually pasting context into a fresh Claude.ai conversation
- Sharing the context with a teammate

---

## Paste this as your first message

```
I'm setting up Terraform infrastructure for the JTI (Journalism Trust Initiative) project at Relief Applications. The client is Reporters Without Borders (RSF). Here's the full context.

## Architecture

The JTI stack on Azure:
- Azure API Management (Consumption tier) — API gateway, hosts the JTI OpenAPI spec
- Azure Front Door Standard — global edge, routes /api/* /static/* /* (PROD ONLY)
- Azure Blob Storage — Swagger UI + OpenAPI definition + WP media offload target
- Azure App Service for Containers (B1 Linux) — dockerised WordPress
- Azure Database for MySQL Flexible Server (B_Standard_B1ms) — WP DB
- Azure Files share — mounted at /var/www/html/wp-content/uploads (10 GB prod, 1 GB staging)
- Azure Monitor (Log Analytics + App Insights + Portal Dashboard)

WP uses Azure Files for uploads (native, no plugin). MySQL is required (WP doesn't support MS SQL).

## Decisions

- IaC: Terraform >= 1.6, azurerm ~> 4.0
- Structure: root + child modules + multi-env (staging + prod)
- State: Azure Blob backend, one storage account, separate keys per env
- Front Door: PROD ONLY (saves ~$35/mo); staging accesses App Service / APIM directly
- APIM tier: Consumption_0 (cheapest)
- Repo: monorepo, infra/ folder inside the JTI repo
- Cost: ~$33/mo staging, ~$85/mo prod, ~$120/mo total

## File layout

infra/
├── README.md, INFRACOST.md, .gitignore, infracost.yml
├── bootstrap/                              # creates state storage account
├── modules/
│   ├── blob-storage/, wordpress/, apim/, front-door/, monitoring/
└── environments/
    ├── staging/                            # NO front_door module
    └── prod/                               # full stack

Each module has main.tf / variables.tf / outputs.tf. Each environment has backend.tf, providers.tf, main.tf, variables.tf, outputs.tf, terraform.tfvars.example, infracost-usage.yml.

## Conventions

- Tags: project, environment, managed_by, client
- Naming: <type>-<project>-<env>-<random_suffix> where global uniqueness needed
- Storage account names: st<project><env><random_string>, lowercase, ≤24 chars
- MySQL password via TF_VAR_mysql_admin_password env var, marked sensitive
- Backend keys: prod.terraform.tfstate, staging.terraform.tfstate

## Caveats

- Infracost only supports CLASSIC azurerm_frontdoor, NOT the new azurerm_cdn_frontdoor_*. Front Door cost (~$42/mo) added manually. See INFRACOST.md.
- APIM Consumption provisioning takes 30-45 min — terraform apply will hang on it.
- bootstrap state stays LOCAL (chicken-and-egg with the state storage account it creates).

## My preferences

- I'm Yafar, Full Stack Developer at Relief Applications, Granada
- Concise, direct, no preamble
- Commands chained with &&, copy-pasteable solutions
- Push back if I'm asking for the wrong thing

## What I need

The project files exist already (this repo). Pick up from where they are. If anything looks off, tell me before changing it. We were about to bootstrap and deploy staging.
```
