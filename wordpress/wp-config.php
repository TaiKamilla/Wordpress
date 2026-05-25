<?php
/**
 * WordPress configuration — environment-aware.
 */

// ----------------------------------------------------------------------------
// HTTPS detection behind a TLS-terminating proxy (Azure App Service, Cloudflare)
// ----------------------------------------------------------------------------
// App Service terminates TLS at its front-end and forwards plain HTTP to the
// container, setting X-Forwarded-Proto: https. Without this block, WordPress's
// is_ssl() returns false, sees that siteurl/home are https://, and 301s every
// request back to the same URL — an infinite loop that takes 30+ seconds per hop
// (the full WP boot fires before WP decides to redirect). This MUST run before
// any WordPress code, so it stays at the top of the file.
if (
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (!empty($_SERVER['HTTP_X_ARR_SSL'])) // Azure App Service legacy ARR header
) {
    $_SERVER['HTTPS'] = 'on';
}

/**
 * Original config docblock follows.
 *
 * Connection settings, the multisite domain, and cryptographic keys are read
 * from environment variables (set by Azure App Service in staging/prod via
 * Terraform's app_settings). Hardcoded fallbacks below preserve compatibility
 * with the previous hosting setup; they are NOT used when the corresponding
 * env var is set.
 *
 * Required env vars in production:
 *   WORDPRESS_DB_HOST, WORDPRESS_DB_USER, WORDPRESS_DB_PASSWORD, WORDPRESS_DB_NAME
 *
 * Recommended env vars per environment (override the legacy fallbacks):
 *   WORDPRESS_DOMAIN_CURRENT_SITE  (e.g. "staging.journalismtrustinitiative.org")
 *   WORDPRESS_AUTH_KEY, WORDPRESS_SECURE_AUTH_KEY, WORDPRESS_LOGGED_IN_KEY,
 *   WORDPRESS_NONCE_KEY, WORDPRESS_AUTH_SALT, WORDPRESS_SECURE_AUTH_SALT,
 *   WORDPRESS_LOGGED_IN_SALT, WORDPRESS_NONCE_SALT
 *
 * Optional debug flags:
 *   WORDPRESS_WP_DEBUG, WORDPRESS_WP_DEBUG_LOG, WORDPRESS_WP_DEBUG_DISPLAY,
 *   WORDPRESS_SCRIPT_DEBUG  (any of true/false)
 */

// Helper: read an env var, fall back to a default if unset or empty.
if (!function_exists('jti_env')) {
    function jti_env(string $name, string $default = ''): string {
        $v = getenv($name);
        return ($v !== false && $v !== '') ? $v : $default;
    }
}

// ----------------------------------------------------------------------------
// Database
// ----------------------------------------------------------------------------
// Credentials come from the environment (Azure App Service app_settings, set by
// Terraform; or .env for local docker-compose). No secret fallbacks in source.
define('DB_NAME',     jti_env('WORDPRESS_DB_NAME',     ''));
define('DB_USER',     jti_env('WORDPRESS_DB_USER',     ''));
define('DB_PASSWORD', jti_env('WORDPRESS_DB_PASSWORD', ''));
define('DB_HOST',     jti_env('WORDPRESS_DB_HOST',     'localhost'));
define('DB_CHARSET',  'utf8mb4');
define('DB_COLLATE',  'utf8mb4_general_ci');

// Azure Database for MySQL Flexible Server requires TLS by default. Detect by
// hostname suffix and enable the SSL client flag automatically.
if (str_ends_with(DB_HOST, '.mysql.database.azure.com')) {
    define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
}

// ----------------------------------------------------------------------------
// Cryptographic keys & salts
// ----------------------------------------------------------------------------
// Salts are injected as env vars by Terraform (random_password → App Service
// app_settings; values live in tfstate/blob backend, never in source).
// For local docker-compose dev, set WORDPRESS_*_KEY / *_SALT in your .env.
// Empty fallback = WordPress will complain if env vars are missing, which is
// the correct fail-loud behavior (no weak hardcoded defaults in the repo).
define('AUTH_KEY',         jti_env('WORDPRESS_AUTH_KEY',         ''));
define('SECURE_AUTH_KEY',  jti_env('WORDPRESS_SECURE_AUTH_KEY',  ''));
define('LOGGED_IN_KEY',    jti_env('WORDPRESS_LOGGED_IN_KEY',    ''));
define('NONCE_KEY',        jti_env('WORDPRESS_NONCE_KEY',        ''));
define('AUTH_SALT',        jti_env('WORDPRESS_AUTH_SALT',        ''));
define('SECURE_AUTH_SALT', jti_env('WORDPRESS_SECURE_AUTH_SALT', ''));
define('LOGGED_IN_SALT',   jti_env('WORDPRESS_LOGGED_IN_SALT',   ''));
define('NONCE_SALT',       jti_env('WORDPRESS_NONCE_SALT',       ''));

// ----------------------------------------------------------------------------
// Multisite
// ----------------------------------------------------------------------------
define('WP_ALLOW_MULTISITE',   true);
define('MULTISITE',            true);
define('SUBDOMAIN_INSTALL',    false);
define('DOMAIN_CURRENT_SITE',  jti_env('WORDPRESS_DOMAIN_CURRENT_SITE', 'journalismtrustinitiative.org'));
define('PATH_CURRENT_SITE',    '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);

// ----------------------------------------------------------------------------
// Debug
// ----------------------------------------------------------------------------
// Defaults are off to keep prod/staging fast: every WP_DEBUG_LOG write hits
// wp-content/debug.log over Azure Files SMB, which is catastrophic for TTFB.
// SCRIPT_DEBUG=true loads the unminified /wp-includes/js/dist/* bundles (3-5× more bytes).
// Override per environment via Terraform app_settings (WORDPRESS_WP_DEBUG=true etc.).
define('WP_DEBUG',         filter_var(jti_env('WORDPRESS_WP_DEBUG',         'false'), FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_LOG',     filter_var(jti_env('WORDPRESS_WP_DEBUG_LOG',     'false'), FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_DISPLAY', filter_var(jti_env('WORDPRESS_WP_DEBUG_DISPLAY', 'false'), FILTER_VALIDATE_BOOLEAN));
define('SCRIPT_DEBUG',     filter_var(jti_env('WORDPRESS_SCRIPT_DEBUG',     'false'), FILTER_VALIDATE_BOOLEAN));
@ini_set('display_errors', 0);

// ----------------------------------------------------------------------------
// Performance / runtime
// ----------------------------------------------------------------------------
define('WP_CACHE',        true);
// Toggle wp-cron via the WORDPRESS_DISABLE_WP_CRON env var (Phase 1.3).
// Default false: WP fires cron inline on page loads. Flip to true after an
// external scheduler is hitting /wp-cron.php (and /app-jti/wp-cron.php) every
// few minutes — otherwise scheduled tasks never run.
define('DISABLE_WP_CRON', filter_var(jti_env('WORDPRESS_DISABLE_WP_CRON', 'false'), FILTER_VALIDATE_BOOLEAN));

// ---- Redis object cache (Phase 2.1) ----
// Reads env vars set by the Terraform `wordpress` module when redis_host is
// non-empty. The object-cache.php drop-in in wp-content/ engages when these
// are defined AND the phpredis extension is loaded.
if (jti_env('WP_REDIS_HOST', '') !== '') {
    define('WP_REDIS_HOST',         jti_env('WP_REDIS_HOST', ''));
    define('WP_REDIS_PORT',         (int) jti_env('WP_REDIS_PORT', '6380'));
    define('WP_REDIS_PASSWORD',     jti_env('WP_REDIS_PASSWORD', ''));
    define('WP_REDIS_SCHEME',       jti_env('WP_REDIS_SCHEME', 'tls'));
    define('WP_REDIS_DATABASE',     0);
    define('WP_REDIS_TIMEOUT',      1);
    define('WP_REDIS_READ_TIMEOUT', 1);
    // Prevent staging<->prod cache key collisions if they ever share Redis.
    $jti_cache_salt = jti_env('WP_CACHE_KEY_SALT', '');
    if ($jti_cache_salt !== '') {
        define('WP_CACHE_KEY_SALT', $jti_cache_salt);
    }
}

// Admin-side plugin/theme installs and updates are ALLOWED. Changes are
// persisted to Azure Files (/persist mount) by docker/persist-watcher.sh and
// restored on container start by docker/entrypoint-persist.sh — see those
// files for the conflict rule ("persist always wins") and the runbook for
// the "roll forward a plugin baseline" procedure.
//
// Silent automatic updates remain DISABLED: only admin-initiated updates
// flow through. Override either via env vars on a per-env basis if needed.
define('DISALLOW_FILE_MODS',         filter_var(jti_env('WORDPRESS_DISALLOW_FILE_MODS',         'false'), FILTER_VALIDATE_BOOLEAN));
define('AUTOMATIC_UPDATER_DISABLED', filter_var(jti_env('WORDPRESS_AUTOMATIC_UPDATER_DISABLED', 'true'),  FILTER_VALIDATE_BOOLEAN));
ini_set('max_execution_time', 0);
// Was 2048M — far above what App Service B1 (1.75 GB total) can give one PHP
// worker. Cap at 512M so a runaway request OOMs fast instead of swapping the
// whole container.
ini_set('memory_limit', '512M');

// ----------------------------------------------------------------------------
// Misc
// ----------------------------------------------------------------------------
$table_prefix = 'wp_';

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once(ABSPATH . 'wp-settings.php');
