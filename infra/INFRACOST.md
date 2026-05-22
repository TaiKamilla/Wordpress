# Infracost workflow

## Quick run

```bash
infracost breakdown --config-file infracost.yml
```

This reads `infracost.yml` at the project root, which runs all three projects (bootstrap, staging, prod) with their respective usage files.

For a single environment:

```bash
cd environments/prod
infracost breakdown --path . --usage-file infracost-usage.yml
```

## Why we have usage files

Infracost prices two kinds of resources differently:

1. **Fixed** (always-on) — App Service B1, MySQL B1ms compute. Priced exactly, no usage file needed.
2. **Usage-based** — Storage, logs, App Insights, APIM Consumption. Priced as `$X per GB / per million requests / etc.`. Without usage hints, Infracost shows them as `"Monthly cost depends on usage"` and adds **$0** to the total.

The `infracost-usage.yml` files tell Infracost roughly how much you'll use, so those usage-based costs show up in the total.

## Updating the usage estimates

The numbers in `infracost-usage.yml` are educated guesses calibrated from current JTI Cloudflare data. After running in production for a few weeks, replace them with real numbers from Azure Cost Management (Portal → Cost Management → Cost analysis → group by Meter).

To regenerate the usage file with the latest schema (new fields Infracost has added):

```bash
cd environments/prod
infracost breakdown --sync-usage-file --usage-file infracost-usage.yml --path .
```

This adds any missing fields without overwriting your filled-in values.

## ⚠️ Front Door is NOT in Infracost output

Infracost only supports the **classic** `azurerm_frontdoor` resource. We use the newer `azurerm_cdn_frontdoor_*` (Standard tier), which Infracost doesn't price yet (tracking issue: https://github.com/infracost/infracost/issues — search for "cdn_frontdoor").

**You must add Front Door cost manually.** Verified against MS Learn (rev. 2025-09-25) and the Azure pricing calculator, anchored to real Cloudflare bandwidth for journalismtrustinitiative.org (53 GB/mo outbound, ~5M requests assumed):

| Component | Calculation | Monthly |
|---|---|---|
| Standard base fee (per profile) | 1 × $35.00 | $35.00 |
| Outbound: edge → client (Zone 1) | 53 GB × $0.083 | $4.40 |
| Outbound: edge → origin (Zone 1) | ~5 GB × $0.02 | $0.10 |
| Requests (Zone 1) | 5M × $0.009 per 10k | $4.50 |
| Inbound client → edge | free | $0.00 |
| Routing rules (5 included) | free | $0.00 |
| **Front Door total (prod only)** | | **~$44.00** |

Pricing sources:
- https://learn.microsoft.com/en-us/azure/frontdoor/understanding-pricing
- https://azure.microsoft.com/pricing/details/frontdoor/

## Putting it all together

```text
Infracost (prod)        $30 fixed + $13 usage      = ~$43
+ Front Door (manual)                              = ~$44
────────────────────────────────────────────────────
Total prod                                         = ~$87/month

Infracost (staging)     $30 fixed + $4 usage       = ~$34
────────────────────────────────────────────────────
Total staging                                      = ~$34/month

Combined                                           ≈ $121/month
```

Run `./cost.sh` from `infra/` for a one-shot breakdown that already adds the Front Door figure.

## Verifying real cost after deployment

Set up a budget alert in Azure (Portal → Cost Management → Budgets):
- Per resource group: `rg-jti-prod` budget = $100/mo (alert at 80%, 100%)
- Per resource group: `rg-jti-staging` budget = $50/mo (alert at 80%, 100%)

After the first full billing month, compare actual costs to these estimates and tune the `infracost-usage.yml` numbers accordingly.

## Useful commands

```bash
# Show what Infracost can't price (always includes Front Door)
infracost breakdown --config-file infracost.yml --show-skipped

# Save baseline before a PR
infracost breakdown --config-file infracost.yml --format json --out-file baseline.json

# Show what a PR would cost
infracost diff --config-file infracost.yml --compare-to baseline.json

# JSON output for scripting
infracost breakdown --config-file infracost.yml --format json
```
