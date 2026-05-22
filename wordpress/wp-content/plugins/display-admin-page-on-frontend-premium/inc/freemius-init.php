<?php

// Create a helper function for easy SDK access.
if ( !function_exists( 'dapof_fs' ) ) {
    function wpfa_maybe_auto_activate_license(  $dapof_fs, $license_key  ) {
        $cache_key = 'wpfa_dont_activate_license' . md5( $license_key );
        if ( !get_site_transient( $cache_key ) ) {
            try {
                $site = $dapof_fs->get_site_info( array(
                    'blog_id' => get_current_blog_id(),
                ) );
                $results = $dapof_fs->opt_in(
                    false,
                    false,
                    false,
                    $license_key,
                    false,
                    false,
                    false,
                    null,
                    array($site)
                );
                if ( is_object( $results ) && property_exists( $results, 'error' ) && is_string( $results->error ) ) {
                    set_site_transient( $cache_key, $results->error, HOUR_IN_SECONDS * 6 );
                }
            } catch ( \Throwable $e ) {
                set_site_transient( $cache_key, $e->getMessage(), HOUR_IN_SECONDS * 6 );
            }
        }
    }

    function dapof_fs() {
        global $dapof_fs;
        if ( !isset( $dapof_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_1877_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_1877_MULTISITE', true );
            }
            $dapof_fs = fs_dynamic_init( array(
                'id'              => '1877',
                'slug'            => 'display-admin-page-on-frontend',
                'type'            => 'plugin',
                'public_key'      => 'pk_64475c4417669fbcc17c076e31b38',
                'is_premium'      => true,
                'has_addons'      => false,
                'has_paid_plans'  => true,
                'trial'           => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
                'has_affiliation' => 'selected',
                'menu'            => array(
                    'slug'        => 'wpatof_welcome_page',
                    'first-path'  => 'admin.php?page=wpatof_welcome_page',
                    'support'     => false,
                    'affiliation' => false,
                    'network'     => true,
                ),
                'is_live'         => true,
            ) );
            // Try to auto activate using the constant WPFA_LICENSE_KEY
            if ( $dapof_fs->is__premium_only() ) {
                add_action( 'admin_init', function () {
                    global $dapof_fs;
                    if ( wp_doing_ajax() ) {
                        return;
                    }
                    if ( defined( 'WPFA_LICENSE_KEY' ) && WPFA_LICENSE_KEY && !is_network_admin() && $dapof_fs->is_free_plan() && $dapof_fs->is__premium_only() ) {
                        $cache_key = 'wpfa_dont_activate_license' . md5( WPFA_LICENSE_KEY );
                        wpfa_maybe_auto_activate_license( $dapof_fs, WPFA_LICENSE_KEY );
                        if ( $wpfa_license_error_message = get_site_transient( $cache_key ) ) {
                            $GLOBALS['wpfa_license_constant_error'] = $wpfa_license_error_message;
                            add_action( 'admin_notices', function () {
                                if ( VG_Admin_To_Frontend_Obj()->is_master_user() ) {
                                    $class = 'notice notice-error';
                                    $message = sprintf( __( 'WP Frontend Admin: You added WPFA_LICENSE_KEY to your wp-config.php to automatically activate a license on your entire network.<br>But we received this error: %s<br>Please make sure your license is valid. We\'ll try again in 6 hours.', 'vg_admin_to_frontend' ), esc_html( $GLOBALS['wpfa_license_constant_error'] ) );
                                    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
                                }
                            } );
                        }
                    }
                    $license_key = VG_Admin_To_Frontend_Obj()->get_settings( 'license_key' );
                    if ( !empty( $license_key ) && $dapof_fs->is_free_plan() && $dapof_fs->is__premium_only() ) {
                        wpfa_maybe_auto_activate_license( $dapof_fs, $license_key );
                        $cache_key = 'wpfa_dont_activate_license' . md5( $license_key );
                        if ( $wpfa_license_error_message = get_site_transient( $cache_key ) ) {
                            $GLOBALS['wpfa_license_constant_error'] = $wpfa_license_error_message;
                            add_action( 'admin_notices', function () {
                                if ( VG_Admin_To_Frontend_Obj()->is_master_user() ) {
                                    $class = 'notice notice-error';
                                    $message = sprintf( __( 'WP Frontend Admin: We weren\'t able to activate the license received from your dashboard site.<br>But we received this error: %s<br>Please make sure your license is valid. We\'ll try again in 6 hours.', 'vg_admin_to_frontend' ), esc_html( $GLOBALS['wpfa_license_constant_error'] ) );
                                    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
                                }
                            } );
                        }
                    }
                } );
            }
        }
        return $dapof_fs;
    }

    // Init Freemius.
    dapof_fs();
    // Signal that SDK was initiated.
    do_action( 'dapof_fs_loaded' );
}