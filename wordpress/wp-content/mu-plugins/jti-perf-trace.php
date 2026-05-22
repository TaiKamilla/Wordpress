<?php
/**
 * JTI Perf Trace — phase + resource timings logged to error_log on every slow request.
 *
 * Reads as a single JSON line in AppServiceConsoleLogs:
 *   ResultDescription contains "JTI_PERF"
 *
 * Disable by deleting this file from the image.
 */

if (defined('JTI_DISABLE_PERF_TRACE') && JTI_DISABLE_PERF_TRACE) {
    return;
}

$GLOBALS['_jti_t0']            = microtime(true);
$GLOBALS['_jti_phases']        = [];
$GLOBALS['_jti_http_calls']    = [];
$GLOBALS['_jti_http_starts']   = [];

function _jti_mark($p) {
    $GLOBALS['_jti_phases'][$p] = [
        'ms'     => round((microtime(true) - $GLOBALS['_jti_t0']) * 1000),
        'mem_mb' => round(memory_get_usage() / 1048576, 1),
    ];
}

add_action('muplugins_loaded',  function () { _jti_mark('mu_plugins_loaded'); }, 999);
add_action('plugins_loaded',    function () { _jti_mark('plugins_loaded'); }, 999);
add_action('after_setup_theme', function () { _jti_mark('after_setup_theme'); }, 999);
add_action('init',              function () { _jti_mark('init_start'); }, -999);
add_action('init',              function () { _jti_mark('init_end'); }, 999);
add_action('wp',                function () { _jti_mark('wp'); }, 999);
add_action('template_redirect', function () { _jti_mark('template_redirect'); }, 999);
add_filter('template_include',  function ($t) { _jti_mark('template_include'); return $t; }, 999);
add_action('wp_head',           function () { _jti_mark('wp_head_start'); }, -999);
add_action('wp_head',           function () { _jti_mark('wp_head_end'); }, 999);
add_action('wp_footer',         function () { _jti_mark('wp_footer_start'); }, -999);
add_action('wp_footer',         function () { _jti_mark('wp_footer_end'); }, 999);

// Capture every outbound HTTP call WP makes (license checks, update probes, etc.)
add_filter('pre_http_request', function ($r, $args, $url) {
    $GLOBALS['_jti_http_starts'][md5($url)] = microtime(true);
    return $r;
}, 1, 3);

add_filter('http_response', function ($response, $args, $url) {
    $key = md5($url);
    if (isset($GLOBALS['_jti_http_starts'][$key])) {
        $GLOBALS['_jti_http_calls'][] = [
            'host'   => parse_url($url, PHP_URL_HOST),
            'path'   => substr(parse_url($url, PHP_URL_PATH) ?? '', 0, 60),
            'ms'     => round((microtime(true) - $GLOBALS['_jti_http_starts'][$key]) * 1000),
            'status' => is_array($response) ? ($response['response']['code'] ?? null) : null,
        ];
        unset($GLOBALS['_jti_http_starts'][$key]);
    }
    return $response;
}, 999, 3);

add_action('shutdown', function () {
    global $wpdb;

    $total_ms = round((microtime(true) - $GLOBALS['_jti_t0']) * 1000);
    // Only log requests that took at least 500ms — keeps log noise low for static assets.
    if ($total_ms < 500 && !isset($_GET['_jti_perf'])) {
        return;
    }

    $usage = function_exists('getrusage') ? getrusage() : [];
    $cpu_user_ms = isset($usage['ru_utime.tv_sec'])
        ? $usage['ru_utime.tv_sec'] * 1000 + intval($usage['ru_utime.tv_usec'] / 1000)
        : null;
    $cpu_sys_ms = isset($usage['ru_stime.tv_sec'])
        ? $usage['ru_stime.tv_sec'] * 1000 + intval($usage['ru_stime.tv_usec'] / 1000)
        : null;

    // Sum per-host HTTP time so we can see "is X.com always slow"
    $http_by_host = [];
    foreach ($GLOBALS['_jti_http_calls'] as $call) {
        $h = $call['host'] ?? '?';
        if (!isset($http_by_host[$h])) {
            $http_by_host[$h] = ['count' => 0, 'ms' => 0];
        }
        $http_by_host[$h]['count']++;
        $http_by_host[$h]['ms'] += $call['ms'];
    }

    $summary = [
        'uri'             => substr($_SERVER['REQUEST_URI'] ?? '', 0, 100),
        'method'          => $_SERVER['REQUEST_METHOD'] ?? '',
        'total_ms'        => $total_ms,
        'phases'          => $GLOBALS['_jti_phases'],
        'mem_peak_mb'     => round(memory_get_peak_usage() / 1048576, 1),
        'cpu_user_ms'     => $cpu_user_ms,
        'cpu_sys_ms'      => $cpu_sys_ms,
        'io_in_blocks'    => $usage['ru_inblock'] ?? null,
        'io_out_blocks'   => $usage['ru_oublock'] ?? null,
        'page_faults_maj' => $usage['ru_majflt'] ?? null,
        'db_queries'      => isset($wpdb) ? $wpdb->num_queries : null,
        'http_count'      => count($GLOBALS['_jti_http_calls']),
        'http_total_ms'   => array_sum(array_column($GLOBALS['_jti_http_calls'], 'ms')),
        'http_by_host'    => $http_by_host,
        // Top 5 slowest individual outbound calls
        'http_slowest'    => array_slice(
            (function ($calls) {
                usort($calls, fn($a, $b) => ($b['ms'] ?? 0) <=> ($a['ms'] ?? 0));
                return $calls;
            })($GLOBALS['_jti_http_calls']),
            0, 5
        ),
    ];

    error_log('JTI_PERF ' . wp_json_encode($summary));
}, 999);
