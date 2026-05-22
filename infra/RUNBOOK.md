# JTI WordPress on Azure — Runbook

> **Audience:** human operators and AI coding agents. This document captures
> the deployment topology, the *non-obvious* tweaks that took a 13 GB DB import
> from 40 s TTFB to 2 s TTFB, and the gotchas to avoid on a re-deploy.
>
> **Repos:**
> - Infra (Terraform): `/Users/yafar/reliefapplications/JTI/wordpress/infra`
> - WordPress image: `/Users/yafar/reliefapplications/JTI/wordpress-image`
> - DB dumps live outside both: `/Users/yafar/reliefapplications/JTI/staging2.sql`

---

## 1. Architecture (as of 2026-05-07)

```
        Cloudflare (proxy + DNS for *.journalismtrustinitiative.org)
                  │
                  ▼
       Azure Front Door (PROD ONLY)  — staging skips this
                  │
                  ▼
      Azure App Service (Linux Container, B1, France Central)
                  │
        ┌─────────┴────────────────────────┐
        │                                  │
        ▼                                  ▼
  Image: acrjtistaginghecl.azurecr.io   Azure Files
   (ALL of wp-content baked in:           (only wp-content/uploads/
    plugins/themes/languages/etc.)         mounted at runtime)
                  │
                  ▼
      Azure Database for MySQL Flex Server (B_Standard_B1ms, 8.4)
```

**Key principle:** the App Service container is **read-only** at runtime
(except `/var/www/html/wp-content/uploads`). Plugin/theme updates flow
through CI: edit code → rebuild image → restart App Service.
`DISALLOW_FILE_MODS = true` in `wp-config.php` enforces this.

### What the staging stack contains

| Resource | Name | Purpose |
|---|---|---|
| Resource group | `rg-jti-staging` | All staging resources |
| App Service Plan | `asp-jti-staging` | B1, Linux |
| Web App | `app-jti-staging-fuml` | WordPress container, `https_only=true` |
| ACR | `acrjtistaginghecl` | Private Docker registry. **`admin_enabled=true`** because the operator lacks User Access Administrator to grant AcrPull |
| MySQL Flex Server | `mysql-jti-staging-fuml` | DB. Burstable B1ms |
| Storage Account | `stwpjtistagingfuml` | Azure Files for uploads |
| File share `wp-content` | (legacy, kept for rollback) | Was the old "everything-on-AzFiles" share |
| File share `wp-uploads` | The new uploads-only share | Mounted at `/var/www/html/wp-content/uploads` |
| Blob Storage | `stjtistagingq4pb` | Static website (Swagger UI / OpenAPI / WP media offload target) |
| APIM | `apim-jti-staging-cfob` | Consumption tier |
| App Insights | `appi-jti-staging` | (Logs only — no PHP SDK installed; data sits in Log Analytics) |
| Log Analytics Workspace | `log-jti-staging` | Customer ID `8861ec4f-dabb-4927-ab59-fa84fb48a116` |

**Custom domain:** `staging.journalismtrustinitiative.org` (DNS via Cloudflare,
must be set to **DNS-only / grey cloud** during App Service managed-cert
issuance, can be flipped back to **proxied / orange** afterward).

### Key Terraform settings

In `modules/wordpress/main.tf`:

- `mount_path = "/var/www/html/wp-content/uploads"` — **mounts the uploads share at the uploads sub-path**, NOT the entire wp-content
- `share_name = azurerm_storage_share.wp_uploads_only.name` — the new dedicated share
- `restrict_to_frontdoor = false` (staging) / `true` (prod)
- `domain_current_site = var.custom_domain`
- App Service `WEBSITES_ENABLE_APP_SERVICE_STORAGE = "false"`
- `health_check_path = "/"` requires `health_check_eviction_time_in_min = 10`

In `environments/staging/providers.tf`:

- `resource_provider_registrations = "none"` — required because the operator
  doesn't have rights to register Microsoft.Blueprint et al.
- `prevent_deletion_if_contains_resources = false` — needed to destroy a RG
  containing App Insights "Smart Detection" action groups created by Azure.

---

## 2. Performance gotchas — read before re-importing!

These are the issues that took 40 s → 2 s. **Each one is silently triggered
by a perfectly normal-looking import** and you will hit them again unless
you follow the procedures below.

### Gotcha #1 — Azure MySQL has GIPK enabled by default

**Symptom:** after a DB import, every WP query is a full-table scan, TTFB
ranges 13–40 s, every WP table looks like it has only 1 index (the PK), and
that PK is on a column called `my_row_id` instead of `ID`.

**Cause:** Azure MySQL Flexible Server defaults to
`sql_generate_invisible_primary_key = ON`. When the dump's `CREATE TABLE`
statements omit an inline PK (very common in phpMyAdmin / mysqldump output —
see Gotcha #4), MySQL silently auto-creates a hidden 6-byte `my_row_id`
auto-increment PK. Then the dump's *later* `ALTER TABLE … ADD PRIMARY KEY (id),
ADD KEY x, ADD KEY y` statement fails with **`ERROR 1068: Multiple primary
key defined`**, and because that ALTER is one atomic statement, the
`ADD KEY` clauses are rolled back too. **Result: every table missing its
secondary indexes.**

**Fix — apply BEFORE import:**

```bash
az mysql flexible-server parameter set \
  --resource-group rg-jti-staging \
  --server-name mysql-jti-staging-fuml \
  --name sql_generate_invisible_primary_key \
  --value OFF
```

The parameter is dynamic (no restart). Verify:

```bash
mysql -h mysql-jti-staging-fuml.mysql.database.azure.com \
      -u wpadmin --password='...' \
      --ssl-mode=REQUIRED \
      -N -e "SHOW VARIABLES LIKE 'sql_generate_invisible_primary_key';"
# expect: sql_generate_invisible_primary_key  OFF
```

**If you've already imported with GIPK ON**, the only clean fix is drop &
re-import after disabling. Surgically rebuilding indexes on existing tables
is fragile because Azure MySQL won't let you drop the hidden `my_row_id`
without complications.

### Gotcha #2 — Strict `sql_mode` rejects zero-date defaults

**Symptom:** during ALTER TABLE replays, errors like:
`ERROR 1067 (42000): Invalid default value for 'post_date'`,
`Invalid default value for 'comment_date'`, `'user_registered'`, etc.

**Cause:** the dump declares `DEFAULT '0000-00-00 00:00:00'` on TIMESTAMP /
DATETIME columns, which strict `sql_mode` rejects.

**Fix:** start the import session with permissive sql_mode:

```bash
mysql ... \
  --init-command="SET SESSION sql_mode=''" \
  --force \
  wordpress < dump.sql
```

`--force` lets mysql continue past per-statement errors. Without it, it
aborts at the first one and skips ALL subsequent ALTER statements.

### Gotcha #3 — `Duplicate entry '0' for key … PRIMARY` blocks PK addition

**Symptom:** during ALTER TABLE replays, errors like
`ERROR 1062 (23000): Duplicate entry '0' for key 'wp_options.PRIMARY'`,
on tables `wp_options`, `wp_sitemeta`, `wp_actionscheduler_logs`, etc.

**Cause:** between a `CREATE TABLE` (without PK) and the deferred
`ALTER TABLE … ADD PRIMARY KEY`, WordPress can write rows with default-zero
IDs (because there's no AUTO_INCREMENT yet). Common culprits:
`_transient_*`, `_site_transient_*`, action-scheduler logs.

**Fix:** delete those rows BEFORE retrying the ALTER:

```sql
USE wordpress;
DELETE FROM wp_options WHERE option_id = 0;
DELETE FROM wp_5_options WHERE option_id = 0;
DELETE FROM wp_sitemeta WHERE meta_id = 0;
DELETE FROM wp_actionscheduler_logs WHERE log_id = 0;
DELETE FROM wp_5_actionscheduler_logs WHERE log_id = 0;
DELETE FROM wp_actionscheduler_actions WHERE action_id = 0;
DELETE FROM wp_5_actionscheduler_actions WHERE action_id = 0;
```

These rows are caches/logs — WP regenerates them automatically on next
request.

### Gotcha #4 — Self-conflicting bookmark ALTER in some staging dumps

**Symptom:** import errors out at line ~831700 with
`ERROR 1068: Multiple primary key defined` for table `bookmark`. Note
this is the *same error code* as Gotcha #1 but for a different reason.

**Cause:** the staging2.sql dump's `bookmark` ALTER TABLE statement was
generated when GIPK was active on the source DB, leading to two PRIMARY KEY
clauses ending up in one ALTER. With GIPK disabled (Gotcha #1 fix), this
issue *disappears* — but if you somehow still hit it, just edit the dump to
remove the duplicate `ADD PRIMARY KEY` and keep only the secondary key:

```sql
-- Before (line ~831700):
ALTER TABLE `bookmark`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_DA62921DA76ED395` (`user_id`);

-- After (no PK declaration since CREATE TABLE already declared it inline
-- once GIPK is OFF):
ALTER TABLE `bookmark`
  ADD KEY `IDX_DA62921DA76ED395` (`user_id`);
```

### Gotcha #5 — `wp-content` mounted from Azure Files is fatally slow

**Symptom:** TTFB 30–50 s on every request even for trivial pages.

**Cause:** Azure Files SMB has 2–10 ms latency per stat/open/read. A WP
page can include 3,000+ PHP files. With `validate_timestamps=1` (PHP
default), opcache stats every file on every request → 5–15 s of pure SMB
latency per page, before any actual work.

**Fix:** **bake** themes / plugins / mu-plugins / languages into the Docker
image; mount only `wp-content/uploads/` from Azure Files (uploads are
read-mostly and tolerate latency). See "deployment cycle" below.

### Gotcha #6 — TLS termination at App Service confuses `is_ssl()`

**Symptom:** Every request 301-redirects to itself. The body is empty.
`x-redirect-by: WordPress`. Browser shows infinite redirect loop. TTFB is
high (30–50 s) because WP boots fully before issuing the redirect.

**Cause:** App Service terminates TLS at its front-end and forwards plain
HTTP to the container with `X-Forwarded-Proto: https`. WordPress's
`is_ssl()` only checks `$_SERVER['HTTPS']`, sees it empty, decides "this
is http, must redirect to https" → redirects to the same URL.

**Fix:** in `wp-config.php`, **before any WP code runs**:

```php
if (
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (!empty($_SERVER['HTTP_X_ARR_SSL']))
) {
    $_SERVER['HTTPS'] = 'on';
}
```

This is already in our `wp-config.php` at the top of the file. **Do not
remove it.**

### Gotcha #7 — `wp-cron` synchronous on every page load

**Symptom:** intermittent 5–15 s spikes on otherwise-fast pages. After
restart, the first 3–5 requests are much slower than steady state.

**Cause:** with `DISABLE_WP_CRON = false`, every page load triggers the
cron-spawning logic. A freshly-imported site has many overdue cron events
that all fire inline on the first few requests.

**Mitigation we tested but did NOT keep:** setting `DISABLE_WP_CRON = true`
and letting an external cron call `/wp-cron.php?doing_wp_cron=1`. Removed
because the perf gain was within variance. **If you need consistent
sub-3 s performance, re-enable and add an external scheduler.**

### Gotcha #8 — JTI custom plugin's cron callback throws

**Symptom:** PHP fatal in `AppServiceConsoleLogs`:

```
PHP Fatal error: Uncaught ArgumentCountError: Too few arguments to function
JTI_Custom_Organisation_Endpoints::__construct(), 0 passed in
.../jti-custom/jti-custom.php on line 107 and exactly 1 expected
```

**Cause:** `jti-custom/jti-custom.php` line ~107 instantiates
`JTI_Custom_Organisation_Endpoints` with no arguments, but the constructor
requires a `$loader`. The cron event `jti_warm_community_cache` fires this
callback, fatals every time it runs.

**Fix:** edit the cron callback to pass a throwaway loader:

```php
add_action('jti_warm_community_cache', function () {
    if (class_exists('JTI_Custom_Organisation_Endpoints') && class_exists('JTI_Custom_Loader')) {
        $warmer = new JTI_Custom_Organisation_Endpoints(new JTI_Custom_Loader());
        $warmer->warm_community_cache();
    }
});
```

This change is in our local repo. It was deployed via the Azure Files share
because at the time the plugin still lived on AzFiles. **After the
bake-into-image migration, this fix lives in the image** — verify it's
there by checking
`wordpress-image/wordpress/wp-content/plugins/jti-custom/jti-custom.php`.

---

## 3. Initial deployment from scratch

> **Reminder:** read Section 2 before starting. Skipping any of the
> Section 2 fixes will result in a working-but-broken site (no indexes /
> redirect loop / etc.) that takes hours to debug.

### 3.1 Bootstrap state storage (one-time)

```bash
cd infra/bootstrap
terraform init
terraform apply
# Note: storage_account_name has a random suffix. Update environments/{prod,staging}/backend.tf
```

### 3.2 Deploy infra

```bash
cd infra/environments/staging
cp terraform.tfvars.example terraform.tfvars
# Edit tfvars: set apim_publisher_email, apim_sku_name, custom_domain
export TF_VAR_mysql_admin_password='<strong password>'
terraform init
terraform plan
terraform apply  # ~15-30 min for APIM + everything else
```

### 3.3 Disable GIPK on the new MySQL server (CRITICAL — see Gotcha #1)

```bash
az mysql flexible-server parameter set \
  --resource-group rg-jti-staging \
  --server-name $(terraform output -raw mysql_fqdn | cut -d. -f1) \
  --name sql_generate_invisible_primary_key \
  --value OFF
```

### 3.4 Build & push the WordPress image

The first build needs `wordpress/wp-content/` to be **fully populated locally**
(plugins, themes, mu-plugins, languages — *not* uploads). On a fresh repo:

```bash
cd /Users/yafar/reliefapplications/JTI/wordpress-image

# Sync wp-content from the source environment (or copy from old hosting)
# THEN ensure wp-config.php has all the fixes from Gotchas #6 #7 #8

az acr build \
  --registry $(terraform output -raw acr_name) \
  --image wp-jti:latest \
  --file docker/Dockerfile \
  .
```

`.dockerignore` already excludes `uploads/`, `updraft/`, `plugins-old/`,
`upgrade*/`, `*.zip`, `*.sql`, etc. **Do not remove these exclusions** —
the build context will balloon to 10 GB+ otherwise (we hit a 13 GB
`wordpress.zip` once that we then had to move).

### 3.5 Import the database (CRITICAL — Gotchas #1 #2 #3)

```bash
KEY=$(az storage account keys list \
  --account-name <storage-account-name> \
  --resource-group rg-jti-staging \
  --query "[0].value" -o tsv)

# 1. CONFIRM GIPK IS OFF (do not skip this)
mysql -h <db-fqdn> -u wpadmin --password='...' --ssl-mode=REQUIRED \
      -N -e "SHOW VARIABLES LIKE 'sql_generate_invisible_primary_key';"
# Expected: sql_generate_invisible_primary_key  OFF

# 2. (re)create empty database
mysql -h <db-fqdn> -u wpadmin --password='...' --ssl-mode=REQUIRED \
      -e "DROP DATABASE IF EXISTS wordpress; CREATE DATABASE wordpress CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Import (~10–15 min for ~500 MB dump)
mysql -h <db-fqdn> -u wpadmin --password='...' --ssl-mode=REQUIRED \
      --init-command="SET SESSION sql_mode=''" \
      wordpress < /path/to/staging2.sql

# 4. Cleanup duplicate-0 rows (Gotcha #3)
mysql -h <db-fqdn> -u wpadmin --password='...' --ssl-mode=REQUIRED \
      -e "USE wordpress;
          DELETE FROM wp_options WHERE option_id = 0;
          DELETE FROM wp_5_options WHERE option_id = 0;
          DELETE FROM wp_sitemeta WHERE meta_id = 0;
          DELETE FROM wp_actionscheduler_logs WHERE log_id = 0;
          DELETE FROM wp_5_actionscheduler_logs WHERE log_id = 0;
          DELETE FROM wp_actionscheduler_actions WHERE action_id = 0;
          DELETE FROM wp_5_actionscheduler_actions WHERE action_id = 0;"

# 5. If the first import errored on a duplicate-0 PK or invalid-default,
#    re-run JUST the index ALTERs from after the failure point with --force:
#    sed -n '831395,$p' staging2.sql | mysql ... --init-command="SET SESSION sql_mode=''" --force wordpress
```

### 3.6 Verify indexes are present

```bash
mysql -h <db-fqdn> -u wpadmin --password='...' --ssl-mode=REQUIRED -N -e "
SELECT table_name, COUNT(DISTINCT index_name) AS n
FROM information_schema.statistics
WHERE table_schema='wordpress'
  AND table_name IN ('wp_posts','wp_postmeta','wp_options','wp_users','wp_usermeta','wp_term_relationships')
GROUP BY table_name;"
```

**Expected:** every WP core table should have ≥3 indexes. If any returns
1, something is still broken — re-read Gotchas #1–#3.

```bash
# Should return 0:
mysql ... -N -e "USE wordpress; SELECT COUNT(DISTINCT table_name) FROM information_schema.columns WHERE table_schema='wordpress' AND column_name='my_row_id';"
```

### 3.7 Migrate uploads to the dedicated share

If uploads come from another host, copy them to the `wp-uploads` share
(NOT the legacy `wp-content` share):

```bash
# Server-side copy from the old all-in-one share to the new uploads-only share
az storage file copy start-batch \
  --account-name <storage-account> \
  --account-key "$KEY" \
  --destination-share wp-uploads \
  --source-share wp-content \
  --source-path uploads
# Or: use Azure Storage Explorer to drag-drop the local uploads/ folder
# directly into the wp-uploads share root.
```

Resulting layout in `wp-uploads`:

```
2024/  2025/  2026/  ast-block-templates-json/  astra-docs/  complianz/
content-views/  edd/  elementor/  ...
```

(top-level uploads contents, **NOT** wrapped in another `uploads/` folder).

### 3.8 Restart and verify

```bash
az webapp restart --name app-jti-staging-fuml --resource-group rg-jti-staging
# Wait ~60-90s for cold-pull of the image, then:
APP_IP=$(dig +short A app-jti-staging-fuml.azurewebsites.net | tail -1)
curl --resolve "staging.journalismtrustinitiative.org:443:$APP_IP" \
     -o /dev/null -s --max-time 30 \
     -w "ttfb=%{time_starttransfer}s code=%{http_code}\n" \
     "https://staging.journalismtrustinitiative.org/"
# Expected: code=200, ttfb=2-4s on first warm hit.
```

### 3.9 DNS + Cloudflare

1. In the Cloudflare zone for `journalismtrustinitiative.org`:
   - `staging` CNAME → `app-jti-staging-fuml.azurewebsites.net`, **DNS only (grey)** during cert issuance
2. Create a TXT record `asuid.staging.journalismtrustinitiative.org` with
   the App Service's `custom_domain_verification_id`:
   ```bash
   terraform output -raw custom_domain_verification_id
   ```
3. Apply the App Service custom hostname binding (handled by Terraform's
   `wordpress` module when `custom_domain` is set and
   `restrict_to_frontdoor=false`).
4. Wait for the managed cert (~5 min). Once SSL works on the custom domain,
   flip the Cloudflare CNAME back to **proxied (orange)** if you want CDN.

---

## 4. Day-to-day deployment cycle

### Update WordPress image (plugin/theme bumps, code changes)

```bash
cd /Users/yafar/reliefapplications/JTI/wordpress-image

# Edit files. Rebuild + push.
az acr build \
  --registry acrjtistaginghecl \
  --image wp-jti:latest \
  --file docker/Dockerfile \
  .

# Restart so the App Service pulls the new :latest tag
az webapp restart --name app-jti-staging-fuml --resource-group rg-jti-staging
```

`.dockerignore` and `wp-config.php` settings together enforce that only
`wp-content/uploads` is mounted from Azure Files at runtime; everything
else lives in the image.

### Update wp-content via the Azure Files share (legacy, prefer image rebuild)

If a quick fix needs to land without an image rebuild, you *can* upload
to the legacy `wp-content` share — but the new architecture mounts
`wp-uploads` instead, so this only works for files inside `uploads/`.
For non-uploads files, **rebuild the image**.

### Database changes

Schema migrations are typically run by WP plugins on first activation.
After importing or migrating, browse `/wp-admin` to trigger them.
**Never** apply schema changes directly to MySQL while pointing the
running Web App at it — restart the App Service after.

### Querying logs

App Service container output goes to Log Analytics workspace
`log-jti-staging` (customer ID `8861ec4f-dabb-4927-ab59-fa84fb48a116`).

Useful KQL:

```kql
// Slowest requests in the last 30 min
AppServiceHTTPLogs
| where TimeGenerated > ago(30m)
| project TimeGenerated, CsMethod, CsUriStem, ScStatus, TimeTaken
| order by TimeTaken desc | take 50

// PHP errors / warnings
AppServiceConsoleLogs
| where TimeGenerated > ago(30m)
| where ResultDescription has_any ("PHP Fatal", "PHP Warning", "PHP Notice")
| order by TimeGenerated desc | take 100

// Per-phase WP timing (when the JTI_PERF mu-plugin is enabled — see Section 5)
AppServiceConsoleLogs
| where TimeGenerated > ago(15m)
| where ResultDescription contains "JTI_PERF"
| project TimeGenerated, ResultDescription
| order by TimeGenerated desc
```

---

## 5. Performance investigation tools

### `wp-content/mu-plugins/jti-perf-trace.php`

Drops phase timings + memory + CPU + DB query count + outbound HTTP into
`error_log`, visible in `AppServiceConsoleLogs`. Logs requests that took
≥500 ms or include `?_jti_perf=1` in the URL.

JSON shape:

```json
{
  "uri": "/?_jti_perf=1",
  "method": "GET",
  "total_ms": 2123,
  "phases": {
    "mu_plugins_loaded": {"ms": 165, "mem_mb": 6.4},
    "plugins_loaded":    {"ms": 376, "mem_mb": 9.6},
    "after_setup_theme": {"ms": 411, "mem_mb": 11.6},
    "init_start":        {"ms": 411, "mem_mb": 11.6},
    "init_end":          {"ms": 1112, "mem_mb": 25.4},
    "wp":                {"ms": 1340, "mem_mb": 26.2},
    "template_redirect": {"ms": 1342, "mem_mb": 26.2},
    "template_include":  {"ms": 1943, "mem_mb": 27.2},
    "wp_head_start":     {"ms": 1943, "mem_mb": 27.3},
    "wp_head_end":       {"ms": 3027, "mem_mb": 29.9},
    "wp_footer_start":   {"ms": 12391, "mem_mb": 35.9},
    "wp_footer_end":     {"ms": 12714, "mem_mb": 36}
  },
  "mem_peak_mb": 36.9,
  "cpu_user_ms": 2057, "cpu_sys_ms": 343,
  "db_queries": 354,
  "http_count": 0,
  "http_total_ms": 0,
  "http_by_host": [],
  "http_slowest": []
}
```

### How to read it

| Phase pair | What's between |
|---|---|
| `mu_plugins_loaded` → `plugins_loaded` | Regular plugin file inclusions |
| `plugins_loaded` → `after_setup_theme` | Theme setup |
| `init_start` → `init_end` | All `init` action callbacks |
| `init_end` → `wp` | Request parsing + main query |
| `wp` → `template_include` | Query execution |
| `template_include` → `wp_head_end` | `<head>` rendering |
| **`wp_head_end` → `wp_footer_start`** | **Page body content (theme + Elementor)** |
| `wp_footer_*` | Closing scripts |

If `http_count > 0`, look at `http_by_host` and `http_slowest` for slow
outbound calls.

If a particular gap is dominant and `cpu_user_ms` is much smaller than
`total_ms`, the time is being spent waiting (DB or external HTTP).

### App Insights / Application Insights

Available at `appi-jti-staging` but **the WP container has no PHP SDK
installed**, so the "Performance" / "Failures" / "Live Metrics" tabs
will be empty. Useful queries land in Log Analytics (above).

If you need real PHP-level profiling, install Microsoft's PHP App Insights
SDK in the Dockerfile, or drop in Tideways/SPX. Adds runtime cost.

---

## 6. Performance journey log

### Round 1 (2026-05-07): Infra-side fixes
| Stage | Origin TTFB | Notes |
|---|---|---|
| Initial broken state (everything on AzFiles, no perf fixes) | 38–54 s | WP redirect-loop on every request; `wp-cron` fatal flooded it |
| Fixed `X-Forwarded-Proto` detection (Gotcha #6) | 40–50 s | Got 200s instead of 301-loops; still slow |
| Bake plugins/themes into image; mount only uploads (Gotcha #5) | 12–16 s | SMB latency removed; remaining cost was DB |
| Disabled GIPK + replayed missing indexes (Gotchas #1–#4) | **2.4–2.8 s** | Single biggest gain. Index recovery brought us into prod range |
| Tuned OPcache (`validate_timestamps=0`, JIT) | 2.1–2.3 s | Modest gain; warm-state floor was MySQL query time |

### Round 2 (2026-05-11..12): Code + cache + tier
Baseline before this round: `/` p50 4.10 s, `/app-jti/` p50 1.54 s, `/countries` cold MISS **14 s**, ~15 % request timeout rate from wp-cron storms on B1's single vCPU.

| Phase | What | Origin TTFB result | Notes |
|---|---|---|---|
| 1.1 | Added `country_id_idx` STORED generated column to `wp_5_jti_organisations` + `wp_jti_organisations`; rewrote 5 JOIN sites in `class-organisation-endpoints.php` (column swap + LEFT-JOIN→EXISTS for the 2 row-explosion queries) | `/countries` cold MISS **14 s → 1.4 s** | Pure code + ALTER, free |
| 2.1 | Added `azurerm_redis_cache` (Basic C0, ~$16/mo); baked `phpredis` extension + Redis Object Cache plugin v2.5.4 `object-cache.php` drop-in into image; env-var-driven `WP_REDIS_*` in wp-config | 98 % cache hit ratio; ~25-30 % origin TTFB drop on all URLs | `module "redis"` in `infra/modules/redis/` |
| 1.3 | Env-var-driven `DISABLE_WP_CRON`, set `WORDPRESS_DISABLE_WP_CRON=true` | Failures **15 % → 0 %**; p95 down sharply | **Requires external scheduler** — see `perf-baseline/SCHEDULER_OPTIONS.md` |
| Tier bump | B1 → B2 App Service (2 vCPU, 3.5 GB); B1ms → B2s MySQL (2 vCPU, 4 GB); `io_scaling_enabled = true` on storage | Homepage p50 **2.21 s**, p90 2.76 s | The remaining 50 % of every page is Elementor PHP rendering — pure CPU |
| CF cache rule | Anonymous HTML edge cache (4 h edge TTL, 1 h browser TTL) | **< 200 ms** for anonymous repeat visitors | Section 7 below |

### Round 2 final numbers (100-sample, cache-busted, 2026-05-12)

```
URL                                       n     p50     p75     p90     p95     max  fail
/                                       100    2.21    2.37    2.58    2.76    5.85  0
/community/                             100    1.52    1.65    1.85    1.91    2.51  0
/app-jti/                               100    1.26    1.35    1.54    1.72    5.21  0
/jti-custom/v1/public/.../countries     100    0.88    1.01    1.16    1.26    3.20  0
```

Anonymous via Cloudflare HIT: ~100-140 ms TTFB.

**Cost delta from Round 2:** +~$44/mo on staging (Redis +$16, B1→B2 +$13, B1ms→B2s +$15). Staging now ~$80/mo.

### What's still on the table (cost-gated, blocked)
| Lever | Expected origin p50 on `/` | Monthly delta |
|---|---|---|
| **B2 → P0v3** (1 dedicated Cascade Lake vCPU) | ~1.5-1.8 s | +$29 vs B2 |
| **B2 → P1v3** (2 dedicated Cascade Lake vCPU) | ~1.0-1.5 s | +$84 vs B2 |
| Migrate to single VM (D2s_v3 with Nginx+PHP-FPM+MySQL+Redis+Varnish co-located) | ~1.0-1.5 s | ~$70 (replaces B2+B2s+Redis) |
| Managed WP host (Kinsta/WP Engine) | 0.5-1 s | ~$30-50 replaces everything |

### Removing Redis: don't do it naively
Removing the `WP_REDIS_*` env vars **breaks the site** because `wp-content/object-cache.php` is baked into the image and `WP_CACHE = true` is hardcoded. The drop-in falls back to `localhost:6379` and times out on every cache op (99-100/100 request failures observed during the 2026-05-12 A/B test). To genuinely remove Redis:

1. Delete `wp-content/object-cache.php` from the image source
2. Set `WP_CACHE = false` in `wp-config.php`
3. Rebuild + push image
4. Restart

Expected impact: origin homepage p50 drifts from **2.21 s → ~2.8-3.2 s** on B2 (≈25-30 % regression). Decision logged: keep Redis at ~$16/mo.

---

## 7. Cloudflare Cache Rule (DEPLOYED 2026-05-11)

**Status:** applied for staging. Anonymous origin traffic drops to ~100-140 ms via CF edge.

```
(http.host eq "staging.journalismtrustinitiative.org"
 and http.request.method eq "GET"
 and not http.cookie contains "wordpress_logged_in_"
 and not http.cookie contains "wp-postpass_"
 and not http.cookie contains "comment_author_"
 and not starts_with(http.request.uri.path, "/wp-admin")
 and not starts_with(http.request.uri.path, "/wp-login")
 and not starts_with(http.request.uri.path, "/wp-cron")
 and not http.request.uri.path contains "/feed"
 and not starts_with(http.request.uri.path, "/wp-json"))
```

- **Cache eligibility:** Eligible
- **Edge TTL:** Override origin → 4 hours
- **Browser TTL:** Override origin → 1 hour

**Note on syntax:** the runbook used to recommend `contains(http.request.uri.path, "/feed")` (function form). Cloudflare's expression parser rejects this — `contains` is a binary operator, not a function. Only `starts_with` works as a function. Fixed in the snippet above.

For prod, replicate with `http.host eq "journalismtrustinitiative.org"`.

Pending: Install the **Cloudflare WordPress plugin** in WP admin so post saves auto-purge the cache.

---

## 8. Files to look at

| File | Purpose | Notes |
|---|---|---|
| `wordpress-image/Dockerfile` | Image build | Adds php-opcache.ini, copies wordpress/ |
| `wordpress-image/docker/Dockerfile` | Image build | Phase 2: adds `pecl install redis` + `libssl-dev` for phpredis |
| `wordpress-image/docker/php-opcache.ini` | OPcache prod tuning | `validate_timestamps=0` requires baked image |
| `wordpress-image/docker/apache.conf` | Apache vhost | Standard Apache+mod_php |
| `wordpress-image/.dockerignore` | Build context filter | **Do not remove the `*.zip` and uploads exclusions** |
| `wordpress-image/wordpress/wp-config.php` | WP config | X-Forwarded-Proto, env-var driven, DISALLOW_FILE_MODS=true, Phase 2 Redis defines, Phase 1.3 `DISABLE_WP_CRON` toggle |
| `wordpress-image/wordpress/wp-content/object-cache.php` | **Redis object-cache drop-in** (Phase 2) | Redis Object Cache plugin v2.5.4. **Tightly coupled to `WP_REDIS_HOST` being set** — remove both together or site crashes |
| `wordpress-image/wordpress/wp-content/plugins/redis-cache/` | Companion plugin | Gives `wp redis status` admin command; not strictly required |
| `wordpress-image/wordpress/wp-content/mu-plugins/jti-perf-trace.php` | Phase tracer | Logs to `error_log`, visible in Log Analytics. `?_jti_perf=1` to force. |
| `wordpress-image/wordpress/wp-content/plugins/jti-custom/includes/api/class-organisation-endpoints.php` | Custom plugin | Phase 1.1: column swap + EXISTS rewrites on 5 SQL sites. JSON_EXTRACT in JOINs is BANNED here. |
| `wordpress-image/wordpress/wp-content/plugins/jti-custom/jti-custom.php` | Custom plugin | Cron callback fix at line ~107 (Gotcha #8) |
| `infra/infra/modules/wordpress/main.tf` | Terraform | Mount path `/wp-content/uploads`, share `wp_uploads_only`, Redis env vars, `io_scaling_enabled=true`, env-var-driven `WORDPRESS_DISABLE_WP_CRON` |
| `infra/infra/modules/redis/` | **NEW Terraform module** | Azure Cache for Redis Basic C0 (~$16/mo) |
| `infra/perf-baseline/` | Measurement scripts + history | `measure.sh`, `show-traces.sh`, plans, baselines, scheduler options |
| `infra/perf-baseline/SCHEDULER_OPTIONS.md` | wp-cron external scheduler | **Required reading** — pick Cloudflare Worker (recommended) or cron-job.org |
| `infra/RUNBOOK.md` | This file | |

---

## 9. Glossary of magic strings used in commands

| Variable | Value (staging) |
|---|---|
| Resource group | `rg-jti-staging` |
| Web App name | `app-jti-staging-fuml` |
| App Service hostname | `app-jti-staging-fuml.azurewebsites.net` |
| App Service IP (current) | re-resolve with `dig +short A app-jti-staging-fuml.azurewebsites.net` |
| MySQL hostname | `mysql-jti-staging-fuml.mysql.database.azure.com` |
| MySQL admin user | `wpadmin` |
| MySQL admin password | (in tfvars or env, never commit) |
| MySQL DB name | `wordpress` |
| **Redis hostname** | `redis-jti-staging-wvca.redis.cache.windows.net` (SSL port 6380) |
| **Redis SKU** | Basic C0 (250 MB, ~$16/mo, no SLA) |
| Storage account | `stwpjtistagingfuml` |
| File share (active) | `wp-uploads` (mounted at `/var/www/html/wp-content/uploads`) |
| File share (legacy) | `wp-content` (kept as rollback; can delete after stable) |
| Container Registry | `acrjtistaginghecl.azurecr.io` |
| Image | `acrjtistaginghecl.azurecr.io/wp-jti:latest` (Phase 2 baked in 2026-05-11) |
| Image tags retained | `phase1-1-20260511-1147`, `phase2-20260511-1238` |
| Log Analytics customerId | `8861ec4f-dabb-4927-ab59-fa84fb48a116` |
| Subscription | `58124fdc-b590-44dc-a3b9-0c187d7c572e` |

---

## 10. Decision log — why we did each non-obvious thing

| Decision | Reason |
|---|---|
| Bake everything into image except uploads | SMB latency on PHP includes is fatal for WP perf. AzFiles for full wp-content gives 30–50 s TTFB; baking gives 2 s. (Gotcha #5) |
| Don't keep mutable wp-content on AzFiles | Image-vs-DB drift risk. If admin auto-updates a plugin, a future image redeploy reverts the code while the DB stays migrated → broken site. `DISALLOW_FILE_MODS = true` enforces single source of truth. |
| Use ACR admin user instead of MI | Operator lacks User Access Administrator to grant AcrPull role to App Service MI. Switch to MI + role assignment when permission is available (commented-out block in `environments/{prod,staging}/main.tf`). |
| Disable GIPK at server level | Saves every future re-import from the same broken state. (Gotcha #1) |
| Random-suffix MySQL server name | Azure reserves deleted server names for ~5 days, blocking re-creation. Suffix lets us recreate immediately. |
| Always-on=true on App Service | B1 tier has no scale-to-zero; this prevents the platform from idling the container. |
| Prod uses Front Door + Web App locked to AzureFrontDoor.Backend service tag | Edge cache + WAF + global routing for prod. Staging skips Front Door (~$35/mo savings). |
| Custom domain handled by App Service in staging, by Front Door in prod | DNS prerequisite (TXT for asuid + CNAME) must exist before `terraform apply`. (See `domain_current_site` var.) |
| **B1 → B2 + B1ms → B2s (2026-05-12)** | Phase 1.1/2/1.3 traces showed 50 % of every page was Elementor body render on a single shared B1 vCPU. B2 dropped p50 by ~30 % and p90 by ~40 %. ~$28/mo for the bump pays back vs hours of further chasing. |
| **Keep `object-cache.php` + Redis as a tightly-coupled pair** | Removing only the env vars leaves the drop-in trying to connect to `localhost:6379` and the site times out catastrophically (verified 2026-05-12). To genuinely remove Redis, you must also delete the drop-in and set `WP_CACHE = false`. |
| **wp-cron disabled via env var; needs external scheduler** | Single B1/B2 worker can't absorb wp-cron stampedes (we saw 17-19 s cron requests blocking everything). Disable + external 5-min ping = 0 % timeouts. See `perf-baseline/SCHEDULER_OPTIONS.md`. |

---

## 11. When things break (quick triage)

| Symptom | Likely cause | Where to look |
|---|---|---|
| TTFB >10 s, every request | Bake-into-image not in effect, OR DB indexes missing | Verify mount_path is `/wp-content/uploads`; check index counts with the SQL in 3.6 |
| 301 to same URL, infinite loop | `X-Forwarded-Proto` block missing from wp-config.php | Top of `wordpress/wp-config.php` (Gotcha #6) |
| **5xx / mass timeouts after redeploy** | `WP_REDIS_*` env vars missing but `object-cache.php` still in image → drop-in defaults to `localhost:6379` and timeouts cascade | `az webapp config appsettings list -g rg-jti-staging -n app-jti-staging-fuml --query "[?starts_with(name,'WP_REDIS')]"` should return 5 entries. Redis hostname: `redis-jti-staging-wvca.redis.cache.windows.net`. |
| **Long cron events / occasional 17-19 s requests** | wp-cron inline; container worker blocked | Check `WORDPRESS_DISABLE_WP_CRON=true` is set on App Service. If yes, confirm external scheduler is still hitting `/wp-cron.php` (otherwise scheduled tasks accumulate). |
| `cf-cache-status: DYNAMIC` always | Cache rule not applied or cookies present | Cloudflare dashboard → Cache Rules. Test in Incognito to rule out logged-in cookies. Section 7 has the exact rule. |
| App Service won't pull new image | Auth issue or webhook missing | `az webapp config container show -n app-jti-staging-fuml -g rg-jti-staging`; for ACR admin auth, env vars `DOCKER_REGISTRY_SERVER_*` must be set (handled by Terraform) |
| Storage Explorer / `az storage file upload` returns 403 | Wrong RBAC. Use `--auth-mode key` (or `--account-key "$KEY"`) instead of `--auth-mode login` for File shares; the Blob roles in the error message DON'T apply |
| Dump import errors out partway | Almost always one of Gotchas #1–#4 | See Section 2 |
| **Want to see where time is going on a slow request** | JTI_PERF mu-plugin logs phase timings | Add `?_jti_perf=1` to any URL; then `infra/perf-baseline/show-traces.sh` or KQL in §5 |

---

## 12. Hand-off note for AI agents

If you're an AI agent picking up this work:

1. **Read Section 2 in full before doing any DB work.** Most of the
   surprising failures we hit are well-known traps once you know the
   patterns. The Section 2 fixes are not optional.
2. **Local-only commands are fine without approval. Cloud writes need
   explicit user OK.** Examples of cloud writes:
   `terraform apply`, `az ... create / set / delete`, `az acr build`
   (because it pushes to ACR), `az webapp restart`, `mysql ... INSERT/UPDATE/DELETE/DROP`.
   Examples of OK-without-approval: `terraform plan`, `az ... show / list`,
   `mysql ... SELECT`, file edits, lints.
3. **Don't paste hardcoded computed values.** Derive values via
   `terraform output -raw …` or query Azure. Pasting "20.111.1.7" into
   a doc made one timing test break two restarts later.
4. **For long-running tasks, use `run_in_background=true` on Bash and
   wait for the notification.** Don't poll with sleep loops.
5. **Don't assume cache means caching.** Prod's Varnish responds with
   `x-cache: HIT` even on lots of pages; the user knew their PHP was
   fast on cache miss. Always cache-bust before drawing conclusions
   about origin speed.
