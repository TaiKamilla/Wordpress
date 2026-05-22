<?php

if ( ! class_exists( 'WPFA_Multitenant' ) ) {

	class WPFA_Multitenant {

		private static $instance = null;
		public static $file      = __FILE__;

		private function __construct() {
		}

		public function get_config_plugin_header() {
			return '/*
			Plugin Name: WP Frontend Admin - Configuration File
			Plugin URI: https://wpfrontendadmin.com/?utm_source=wp-admin&utm_medium=plugins-list
			Description: This plugin contains a configuration file to automatically configure all your sites based on your dashboard settings. This plugin does not require activation, the settings are read from this file even when the plugin is deactivated. Last update: ' . current_time( 'mysql', true ) . ' UTC
			Version: 1.0.0
			Author: WP Frontend Admin
			*/';
		}

		public function get_config_file_contents() {
			$options          = $this->get_configuration_for_tenant_site();
				$file_content = '<?php				
				' . $this->get_config_plugin_header() . '
								
								$config = ' . var_export( $options, true ) . ';';
								return $file_content;
		}
		public function init() {
			if ( ! dapof_fs()->is_plan__premium_only( 'platform', true ) ) {
				return false;
			}
			if ( is_admin() ) {
				add_action( 'vg_plugin_sdk/settings/' . VG_Admin_To_Frontend::$textname . '/options', array( $this, 'add_settings_tab__premium_only' ) );
				add_filter( 'vg_admin_to_frontend/backend/js_data', array( $this, 'add_data_for_js' ) );
			}

			// Unset WPMU settings when we detect the site is running in WildCloud with WPMU mode enabled
			if( $this->is_wildcloud() && VG_Admin_To_Frontend_Obj()->get_settings('enable_wpmu_mode')){
				VG_Admin_To_Frontend_Obj()->update_option('enable_wpmu_mode', false);
				VG_Admin_To_Frontend_Obj()->update_option('redirect_to_frontend', '');
				VG_Admin_To_Frontend_Obj()->update_option('global_dashboard_id', '');
				VG_Admin_To_Frontend_Obj()->update_option('root_domain', '');
			}

			$environment = $this->get_environment();
			if ( $environment === 'client_site' ) {
				require_once __DIR__ . '/client-site.php';
			} elseif ( $environment === 'dashboard_site' ) {
				require_once __DIR__ . '/dashboard-site.php';
			}

		}

		// Function to encrypt a text string
		function encrypt( $text, $key ) {
			$iv        = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
			$encrypted = openssl_encrypt( $text, 'aes-256-cbc', $key, 0, $iv );
			return base64_encode( $iv . $encrypted );
		}

		function get_configuration_for_tenant_site() {
			global $wpdb;
			$options                       = get_option( VG_Admin_To_Frontend::$textname );
			$options['environment_type']   = 'client_site';
			// Random capability for access restrictions, to ensure that no one is whitelisted to access the wp-admin on tenant sites
			$options['whitelisted_user_capability']   = 'lkjkjkjkjkjkjjk';
			$options['dashboard_site_url'] = home_url( '/' );
			if ( VG_Admin_To_Frontend_Obj()->get_settings( 'add_post_edit_link' ) ) {
				$edit_post_page_id             = VG_Admin_To_Frontend_Obj()->get_page_id( admin_url( 'post.php?action=edit' ), __( 'Edit' ), 'edit_posts' );
				$options['edit_post_base_url'] = esc_url( get_permalink( $edit_post_page_id ) );
			} else {
				$options['edit_post_base_url'] = null;
			}
			$options['dashboard_site_plugin_url'] = plugins_url( '/', __FILE__ );
			$options['license_key']               = dapof_fs()->_get_license()->secret_key;
			$options['page_settings']             = array(
				'vgfa_text_changes'     => $wpdb->get_results( "SELECT meta_value, post_id FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id WHERE p.post_status = 'publish' AND pm.meta_key = 'vgfa_text_changes'", ARRAY_A ),
				'vgfa_disabled_columns' => $wpdb->get_results( "SELECT meta_value, post_id FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id WHERE p.post_status = 'publish' AND  meta_key = 'vgfa_disabled_columns' ", ARRAY_A ),
				'vgfa_hidden_elements'  => $wpdb->get_results( "SELECT meta_value, post_id FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id WHERE p.post_status = 'publish' AND  meta_key = 'vgfa_hidden_elements' ", ARRAY_A ),
			);

			$info                   = parse_url( home_url() );
			$host                   = $info['host'];
			$options['root_domain'] = $host;
			// Each site will have its own login URL, so we'll no longer use this on the client sites. This setting will be used on the dashboard site only to display the message asking them to log in on their client sites.
			// Update: Allow them to set the login_page_url, because they can use absolute paths like /login so the login shortcode properly handles redirects in the tenant sites, also helps to rewrite the wp-login.php URL in the tenant to the custom login URL
			// $options['login_page_url'] = null;
			$options['last_config_file_update'] = current_time( 'mysql', true );
			return $options;
		}

		function add_data_for_js( $data ) {
			$data['environment_type'] = $this->get_environment();
			return $data;
		}

		function get_environment() {
			return VG_Admin_To_Frontend_Obj()->get_settings( 'environment_type', '' );
		}
		function add_settings_tab__premium_only( $sections ) {
			$sections['multitenant'] = array(
				'title'  => __( 'Multitenant', VG_Admin_To_Frontend::$textname ),
				'fields' => array(
					array(
						'id'      => 'environment_type',
						'type'    => 'select',
						'options' => array(
							''               => __( 'None, this is a regular standalone site or regular multisite network', VG_Admin_To_Frontend::$textname ),
							'dashboard_site' => __( 'Dashboard site', VG_Admin_To_Frontend::$textname ),
							'client_site'    => __( 'Client site', VG_Admin_To_Frontend::$textname ),
						),
						'title'   => __( 'Environment type', VG_Admin_To_Frontend::$textname ),
					),
				),
			);
			return $sections;
		}

		static function is_wildcloud() {
			$request_data = json_encode( array_keys( $_SERVER ) );
			return stripos( $request_data, 'WPCS' ) !== false;
		}


		/**
		 * Creates or returns an instance of this class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new WPFA_Multitenant();
				self::$instance->init();
			}
			return self::$instance;
		}

		public function __set( $name, $value ) {
			$this->name = $value;
		}

		public function __get( $name ) {
			return $this->name;
		}

	}
}

if ( ! function_exists( 'WPFA_Multitenant_Obj' ) ) {
	/**
	 * @return WPFA_Multitenant
	 */
	function WPFA_Multitenant_Obj() {
		return WPFA_Multitenant::get_instance();
	}
}
add_action( 'init', 'WPFA_Multitenant_Obj' );
add_action( 'rest_init', 'WPFA_Multitenant_Obj' );

// Schedule cron jobs
register_activation_hook(
	VG_Admin_To_Frontend::$file,
	function() {
		if ( ! wp_next_scheduled( 'wpfa_cron' ) ) {
			wp_schedule_event( time(), 'per_minute', 'wpfa_cron' );
		}
	}
);
add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['per_minute'] = array(
			'interval' => 60,
			'display'  => __( 'One Minute' ),
		);
		return $schedules;
	}
);