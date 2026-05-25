<?php
/**
 * Plugin Name: JTI — Force Elementor Internal CSS
 * Description: Two-layer enforcement that Elementor renders CSS inline in
 *              <head> (no per-post external files at wp-content/uploads/
 *              elementor/css/post-*.css). Eliminates the "404 on cached
 *              Elementor CSS after a fresh container / wp-content reshuffle"
 *              failure mode.
 *
 *              Layer 1 (runtime filter):
 *                Forces option `elementor_css_print_method` to `internal` at
 *                every get_option() call. Cheap, always-on.
 *
 *              Layer 2 (one-shot enforcement, persistent marker):
 *                On first plugins_loaded after the marker is missing:
 *                  - update_option(elementor_css_print_method, internal)   ← DB write
 *                  - DELETE FROM wp_postmeta WHERE meta_key='_elementor_css'
 *                    (so Elementor regenerates fresh with the new option)
 *                  - unlink any stale post-*.css files
 *                  - set marker option so this never runs again unless wiped
 *
 *              To re-trigger enforcement (e.g. after a major Elementor
 *              upgrade): DELETE FROM wp_options WHERE option_name='jti_elementor_internal_enforced';
 *
 * Author:      JTI infra
 * Version:     1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ---- Layer 1: always-on filter ------------------------------------------
add_filter('option_elementor_css_print_method',         fn() => 'internal', 999);
add_filter('default_option_elementor_css_print_method', fn() => 'internal', 999);

// ---- Layer 2: one-shot DB enforcement ----------------------------------
add_action('plugins_loaded', function () {
    static $ran_this_request = false;
    if ($ran_this_request) {
        return;
    }
    $ran_this_request = true;

    // Use a PERSISTENT option as the marker so this runs at most once per
    // database (not once per container life). To re-trigger:
    //   DELETE FROM wp_options WHERE option_name='jti_elementor_internal_enforced';
    $marker_key = 'jti_elementor_internal_enforced';

    // Read marker bypassing our own filter on css_print_method — different
    // option name so safe to use get_option here.
    if (get_option($marker_key) === '1.2') {
        return; // already enforced for this database
    }

    // 1) Make sure the DB has the canonical value (filter isn't enough —
    //    Elementor reads the option indirectly in places that bypass filters).
    update_option('elementor_css_print_method', 'internal', 'on');

    // 2) Wipe cached per-post CSS metadata so Elementor regenerates with the
    //    new option. Direct SQL because there are usually 100s of rows.
    global $wpdb;
    if (isset($wpdb)) {
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_css'");
    }

    // 3) Delete any stale on-disk CSS files left over from when Elementor
    //    was in External mode.
    $dir = WP_CONTENT_DIR . '/uploads/elementor/css';
    if (is_dir($dir)) {
        foreach (glob($dir . '/post-*.css')   ?: [] as $f) { @unlink($f); }
        foreach (glob($dir . '/global*.css')  ?: [] as $f) { @unlink($f); }
    }

    // 4) Mark done. Version-suffixed so we can re-trigger by bumping version.
    update_option($marker_key, '1.2', 'on');
}, 1); // priority 1: run early, before Elementor reads its options
