#!/usr/bin/env bash
# One-shot cost: Infracost + Front Door (which Infracost can't price).
# Front Door figures are computed from the rate constants and traffic inputs below.
# Override traffic inputs via env vars.
#
# Rate sources:
#   https://learn.microsoft.com/en-us/azure/frontdoor/understanding-pricing
#   https://azure.microsoft.com/pricing/details/frontdoor/

set -euo pipefail
cd "$(dirname "$0")"

# Front Door Standard, Zone 1 (NA / Europe / Middle East / Africa) — USD
FD_BASE_FEE=35
FD_EGRESS_CLIENT_PER_GB=0.083
FD_EGRESS_ORIGIN_PER_GB=0.02
FD_PER_10K_REQUESTS=0.009  # $0.009 per 10,000 requests = $0.90 per 1M

# Traffic inputs — defaults from current Cloudflare measurement.
# Override e.g.: EGRESS_CLIENT_GB=80 REQUESTS_M=8 ./cost.sh
EGRESS_CLIENT_GB="${EGRESS_CLIENT_GB:-53}"
EGRESS_ORIGIN_GB="${EGRESS_ORIGIN_GB:-5}"
REQUESTS_M="${REQUESTS_M:-5}"

TMPFILE=$(mktemp)
trap 'rm -f "$TMPFILE"' EXIT

infracost breakdown --config-file infracost.yml --format json --out-file "$TMPFILE" >/dev/null
infracost output --format table --path "$TMPFILE"

PROD_INFRACOST=""
OVERALL_INFRACOST=""
if command -v jq >/dev/null 2>&1; then
    PROD_INFRACOST=$(jq -r '.projects[] | select(.name=="prod") | .breakdown.totalMonthlyCost' "$TMPFILE")
    OVERALL_INFRACOST=$(jq -r '.totalMonthlyCost' "$TMPFILE")
fi

awk -v base="$FD_BASE_FEE" \
    -v ec_rate="$FD_EGRESS_CLIENT_PER_GB" -v ec_gb="$EGRESS_CLIENT_GB" \
    -v eo_rate="$FD_EGRESS_ORIGIN_PER_GB" -v eo_gb="$EGRESS_ORIGIN_GB" \
    -v rq_rate="$FD_PER_10K_REQUESTS" -v rq_m="$REQUESTS_M" \
    -v prod="$PROD_INFRACOST" -v overall="$OVERALL_INFRACOST" '
BEGIN {
    ec = ec_gb * ec_rate
    eo = eo_gb * eo_rate
    rq = rq_m * 100 * rq_rate                  # rq_m millions × 100 = number of 10k batches
    fd = base + ec + eo + rq
    printf "\n──────────────────────────────────\n"
    printf "+ Front Door Standard (prod only, not priced by Infracost):  +$%.2f/mo\n", fd
    printf "  Base $%.2f + Egress→client $%.2f (%g GB × $%.3f)\n", base, ec, ec_gb, ec_rate
    printf "                + Egress→origin $%.2f (%g GB × $%.2f)\n", eo, eo_gb, eo_rate
    printf "                + Requests $%.2f (%gM × $%.3f per 10k)\n", rq, rq_m, rq_rate
    if (prod != "") {
        printf "\n= Prod all-in:    ~$%.2f/mo\n", prod + fd
        printf "= Combined:       ~$%.2f/mo\n", overall + fd
    }
    printf "\nOverride traffic inputs via env: EGRESS_CLIENT_GB EGRESS_ORIGIN_GB REQUESTS_M\n"
}'
