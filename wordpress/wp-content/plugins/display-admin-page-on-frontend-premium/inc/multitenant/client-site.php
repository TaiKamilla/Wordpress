<?php
if ( ! class_exists( 'WPFA_Client_Site' ) ) {

	class WPFA_Client_Site {

		private static $instance = null;

		private function __construct() {
		}

		public function init() {
			add_action( 'wp_login', array( $this, 'dollie_maybe_send_to_dashboard_site' ), PHP_INT_MAX, 2 );
			add_filter( 'login_redirect', array( $this, 'maybe_send_to_dashboard_site' ), PHP_INT_MAX, 3 );
			add_action( 'vg_plugin_sdk/settings/' . VG_Admin_To_Frontend::$textname . '/options', array( $this, 'add_settings_fields' ), 20 );
			add_filter( 'allowed_redirect_hosts', array( $this, 'allow_safe_redirects_to_dashboard_site' ) );
			if ( is_admin() ) {
				add_action( 'vg_plugin_sdk/settings/' . VG_Admin_To_Frontend::$textname . '/after_tabs_content', array( $this, 'render_css_to_hide_dashboard_options' ) );
				add_filter( 'vg_admin_to_frontend/backend/js_data', array( $this, 'modify_js_data' ) );
			}
			add_filter( 'wp_frontend_admin/can_master_user_initialize_wizard', '__return_false' );
			add_filter( 'vg_admin_to_frontend/welcome_steps', array( $this, 'filter_welcome_steps' ) );

			$dashboard_site_url = VG_Admin_To_Frontend_Obj()->get_settings( 'dashboard_site_url' );
			if ( ! empty( $dashboard_site_url ) && ! VG_Admin_To_Frontend_Obj()->get_settings( 'root_domain' ) ) {
				$info = parse_url( $dashboard_site_url );
				$host = $info['host'];
				VG_Admin_To_Frontend_Obj()->update_option( 'root_domain', $host );
			}

			// Trigger a sync every hour automatically, even when the site is just created
			$this->sync_with_dashboard_site();
			add_action( 'wpfa_cron', array( $this, 'sync_with_dashboard_site' ) );
			add_filter( 'wp_frontend_admin/post_edit_url', array( $this, 'get_post_edit_link' ), 10, 2 );
			add_action( 'activated_plugin', array( $this, 'detect_when_any_plugin_changed' ) );
			add_action( 'deactivated_plugin', array( $this, 'detect_when_any_plugin_changed' ) );
			add_action( 'after_switch_theme', array( $this, 'detect_when_theme_changed' ) );
			add_filter( 'wildcloud_sso_admin_url', array( $this, 'wildcloud_sso_redirect_to_frontend_dashboard' ), 10, 2 );
			$this->third_party_cookies_handler();
			add_filter( 'wp_frontend_admin/is_master_user', array( $this, 'who_is_master_on_tenants' ), 10, 2 );
			add_filter( 'wp_frontend_admin/master_capability', array( $this, 'no_master_capability' ) );
		}

		// Only whitelisted emails are master on tenants
		function no_master_capability() {
			$emails       = array_filter( array_map( 'trim', explode( ',', VG_Admin_To_Frontend_Obj()->get_settings( 'tenant_superadmin_emails', '' ) ) ) );
			$current_user = get_userdata( get_current_user_id() );
			return ( $current_user && in_array( $current_user->user_email, $emails, true ) ) ? 'manage_options' : 'asdfdfdfdfd';
		}

		// Only whitelisted emails are master on tenants
		function who_is_master_on_tenants( $is_master_user, $user_id ) {
			$emails       = array_filter( array_map( 'trim', explode( ',', VG_Admin_To_Frontend_Obj()->get_settings( 'tenant_superadmin_emails', '' ) ) ) );
			$current_user = get_userdata( $user_id );
			return $current_user && in_array( $current_user->user_email, $emails, true );
		}

		function third_party_cookies_handler() {
			if ( ! is_admin() || ! empty( $_POST ) || wp_doing_ajax() || empty( $_GET['wpfam'] ) || ( is_user_logged_in() && empty( $_GET['wpfam'] ) ) ) {
				return;
			}

			if ( is_user_logged_in() && ! empty( $_GET['wpfam'] ) ) {
				$redirect_to = esc_url_raw( remove_query_arg( 'wpfam' ) );
				wp_safe_redirect( $redirect_to );
				exit();
			}

			?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
	<h1><?php esc_html_e( 'We need your permission', VG_Admin_To_Frontend::$textname ); ?></h1>
<p><?php esc_html_e( 'This dashboard needs to load content from %s, which is an external site, and your browser requires explicit permission to do so.', VG_Admin_To_Frontend::$textname ); ?></p>
<p><?php esc_html_e( 'Please click on the button below and please grant the permission in the browser\'s dialog to be able to use this dashboard.', VG_Admin_To_Frontend::$textname ); ?></p>
<button type="button" id="refresh-cookies" class="third-party-cookies-permission"><?php esc_html_e( 'Grant permission', VG_Admin_To_Frontend::$textname ); ?></button>
<script>
	var wpfa_third_party_cookies_data = 
			<?php
			echo wp_json_encode(
				array(
					'login_url' => esc_url_raw( wp_login_url( $_SERVER['REQUEST_URI'] ) ),
				)
			);
			?>
</script>
<script src="<?php echo esc_url( plugins_url( '/third-party-cookies.js', __FILE__ ) ); ?>"></script>
</body>
</html>
			<?php
			die();
		}


		function dollie_maybe_send_to_dashboard_site( $user_login, $user ) {
			$admin_url = $this->maybe_send_to_dashboard_site( null, null, $user );
			if ( $admin_url ) {
				wp_redirect( $admin_url );
				exit();
			}
		}
		function wildcloud_sso_redirect_to_frontend_dashboard( $admin_url, $user ) {
			$admin_url = $this->maybe_send_to_dashboard_site( $admin_url, null, $user );
			return $admin_url;
		}

		function detect_when_any_plugin_changed() {
			$active_plugins = get_option( 'active_plugins' );
			sort( $active_plugins );
			$new_active_plugins_hash  = md5( wp_json_encode( $active_plugins ) );
			$last_active_plugins_hash = get_option( 'wpfa_last_plugin_change_hash' );
			if ( $last_active_plugins_hash && $last_active_plugins_hash === $new_active_plugins_hash ) {
				return;
			}

			update_option( 'wpfa_last_plugin_change', time() );
			update_option( 'wpfa_last_plugin_change_hash', $new_active_plugins_hash );
		}
		function detect_when_theme_changed() {
			$active_theme      = get_option( 'template' );
			$last_active_theme = get_option( 'wpfa_last_theme' );
			if ( $last_active_theme && $last_active_theme === $active_theme ) {
				return;
			}

			update_option( 'wpfa_last_theme_change', time() );
			update_option( 'wpfa_last_theme', $active_theme );
		}

		function modify_js_data( $data ) {
			$license_owner_private_key = dapof_fs()->get_user()->secret_key;
			if ( VG_Admin_To_Frontend_Obj()->get_settings( 'enable_required_plugins_check' ) ) {
				$hash = WPFA_Multitenant_Obj()->encrypt( wp_json_encode( WPFA_Plugin_Restrictions_Obj()->_get_site_active_plugins() ), $license_owner_private_key );
				$data['extra_backend_data_to_report_to_parent']['active_plugins'] = $hash;

				// Use a very old timestamp as the default value to indicate that there's no recent change
				$data['extra_backend_data_to_report_to_parent']['last_plugin_change']      = get_option( 'wpfa_last_plugin_change', strtotime( '2023-01-01' ) );
				$data['extra_backend_data_to_report_to_parent']['last_plugin_change_hash'] = get_option( 'wpfa_last_plugin_change_hash' );
			}
			if ( VG_Admin_To_Frontend_Obj()->get_settings( 'enable_required_themes_check' ) ) {
				$data['extra_backend_data_to_report_to_parent']['active_theme'] = WPFA_Multitenant_Obj()->encrypt( WPFA_Theme_Restrictions_Obj()->_get_site_active_theme(), $license_owner_private_key );

				// Use a very old timestamp as the default value to indicate that there's no recent change
				$data['extra_backend_data_to_report_to_parent']['last_theme_change'] = get_option( 'wpfa_last_theme_change', strtotime( '2023-01-01' ) );
			}
			return $data;
		}
		function get_post_edit_link( $url, $post_id ) {
			$base_url = VG_Admin_To_Frontend_Obj()->get_settings( 'edit_post_base_url' );
			if ( $base_url ) {
				$url_parameters = array( 'post' => $post_id );
				$url            = esc_url( add_query_arg( $url_parameters, $base_url ) );
			}
			return $url;
		}

		function sync_with_dashboard_site() {
			$dashboard_site_url = VG_Admin_To_Frontend_Obj()->get_settings( 'dashboard_site_url' );
			if ( empty( $dashboard_site_url ) ) {
				return;
			}

			// Only make the request based on the configuration
			$last_sync_time = get_option( 'wpfa_last_sync_request' );
			$sync_minutes   = (int) VG_Admin_To_Frontend_Obj()->get_settings( 'tenant_sync_minutes', 60 );
			if ( ! empty( $last_sync_time ) && $last_sync_time > ( time() - ( MINUTE_IN_SECONDS * $sync_minutes ) ) ) {
				return;
			}

			$dashboard_site_plugin_url = VG_Admin_To_Frontend_Obj()->get_settings( 'dashboard_site_plugin_url', untrailingslashit( $dashboard_site_url ) . '/wp-content/plugins/display-admin-page-on-frontend-premium' );

			$license_owner_private_key = dapof_fs()->get_user()->secret_key;
			$hash                      = md5( 'wpfa-dashboard-site-config' . $license_owner_private_key );
			$config_file_url           = untrailingslashit( $dashboard_site_plugin_url ) . '/' . $hash . '.json';
			$response                  = wp_remote_get(
				$config_file_url,
				array(
					'sslverify' => false,
				)
			);
			update_option( 'wpfa_last_sync_request', time() );

			if ( is_wp_error( $response ) ) {
				return;
			}
			$options = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( empty( $options ) ) {
				return;
			}
			$file_content = '<?php				
				' . WPFA_Multitenant_Obj()->get_config_plugin_header() . '
								
								$config = ' . var_export( $options, true ) . ';';

			file_put_contents(
				VG_Admin_To_Frontend_Obj()->get_file_config_file_path(),
				$file_content
			);
		}

		function filter_welcome_steps( $steps ) {
			if ( isset( $steps['create_page_easy'] ) ) {
				unset( $steps['create_page_easy'] );
			}
			if ( isset( $steps['note'] ) ) {
				unset( $steps['note'] );
			}
			if ( isset( $steps['allowed_urls'] ) ) {
				unset( $steps['allowed_urls'] );
			}
			return $steps;
		}

		function render_css_to_hide_dashboard_options() {
			?>
<style>
.wpse-settings-form-wrapper .tabs-links,
.wpse-settings-form-wrapper .tab-content {
	display: none !important;
}
.wpse-settings-form-wrapper .Multitenant.tab-content {
	margin: 0 auto !important;
	display: block !important;
}
</style>
			<?php
		}
		function allow_safe_redirects_to_dashboard_site( $hosts ) {
			global $wpdb;
			$dashboard_site_url = VG_Admin_To_Frontend_Obj()->get_settings( 'dashboard_site_url' );
			if ( ! $dashboard_site_url ) {
				return $hosts;
			}
			$dashboard_host = parse_url( $dashboard_site_url, PHP_URL_HOST );
			if ( ! in_array( $dashboard_host, $hosts, true ) ) {
				$hosts[] = $dashboard_host;
			}

			return $hosts;
		}

		function add_settings_fields( $sections ) {
			$sections['multitenant']['fields'][] = array(
				'id'                   => 'dashboard_site_url',
				'type'                 => 'text',
				'validate'             => 'url',
				'title'                => __( 'Dashboard site URL', VG_Admin_To_Frontend::$textname ),
				'callback_after_field' => array( $this, 'render_advanced_settings_toggle' ),
			);
			return $sections;
		}
		function render_advanced_settings_toggle( $field ) {
			if ( WPFA_Multitenant::is_wildcloud() ) {
				?>
				<style>
					.wpse-settings-form-wrapper form.wpse-set-settings .field-wrapper input,
.wpse-settings-form-wrapper form.wpse-set-settings .field-wrapper select,
.wpse-settings-form-wrapper form.wpse-set-settings .field-wrapper label,
.wpse-settings-form-wrapper form.wpse-set-settings .button-primary {
	display: none !important;
}
.wpse-settings-form-wrapper form.wpse-set-settings {
	border: 0 !important;
}
				</style>
				<div class="wpfa-multitenant-description">
					<p><?php printf( esc_html__( 'We detected that you\'re using WildCloud and this site is a tenant site. All the WP Frontend Admin configuration is automatically synced with your dashboard site, so you don\'t have to configure anything related to WP Frontend Admin on this site. All your front-end dashboard configurations must be made  in the dashboard site. The configuration was last synced on %s UTC', VG_Admin_To_Frontend::$textname ), VG_Admin_To_Frontend_Obj()->get_settings( 'last_config_file_update' ) ); ?></p>
				</div>
				<?php
			} else {
				?>
				<div class="wpfa-multitenant-description">
					<p><?php _e( 'We have 2 types of sites in a multitenant platform (i.e. myapp.com): the "tenants" are all the sites created for your customers/users (i.e. mystore1.com, mystore2.com) and the "dashboard" site is an external site that you configure where all your customers will manage their own sites (i.e. dashboard.myapp.com).', VG_Admin_To_Frontend::$textname ); ?></p>
					<p><?php _e( 'You have configured this site as a tenant site and you must enter the homepage URL of your external dashboard site. You no longer need to configure the dashboard for every client site and you\'ll configure the dashboard in the dashboard site..', VG_Admin_To_Frontend::$textname ); ?></p>
				</div>
				<?php
			}
			if ( ! is_ssl() ) {
				?>
				<div class="wpfa-multitenant-description">
					<p style="color:red;"><?php _e( 'IMPORTANT: You need to use HTTPS on this site, otherwise the Single Sign On will not work between this site and the dashboard site. You can ask your hosting provider about this if you don\'t know how.', VG_Admin_To_Frontend::$textname ); ?></p>
				</div>
				<?php
			}
		}

		public function maybe_send_to_dashboard_site( $redirect_to, $request, $user ) {
			$allowed_roles_for_dashboard_site = array_map( 'trim', explode( ',', VG_Admin_To_Frontend_Obj()->get_settings( 'dashboard_users_role' ) ) );
			if ( ! empty( $allowed_roles_for_dashboard_site ) && VG_Admin_To_Frontend_Obj()->user_has_any_role( $allowed_roles_for_dashboard_site, $user ) ) {
				$sso_url = $this->get_sso_url( $user );
				if ( ! empty( $sso_url ) ) {
					$redirect_to = $sso_url;
				}
			}

			return $redirect_to;
		}


		public function get_sso_url( $user ) {
			$dashboard_site_url = VG_Admin_To_Frontend_Obj()->get_settings( 'dashboard_site_url' );
			if ( ! $dashboard_site_url ) {
				return;
			}
			$data_required_by_dashboard_site = json_encode(
				array(
					'roles'           => $user->roles,
					'user_id'         => $user->ID,
					'user_email'      => $user->user_email,
					'site_url'        => home_url(),
					'wp_admin_slug'   => str_replace( home_url(), '', admin_url() ),
					'wpfa_license_id' => dapof_fs()->_get_license()->id,
					'wpfa_install_id' => dapof_fs()->get_site()->id,
					'created_at'      => time(),
					'site_name'       => get_option( 'blogname' ),
				)
			);

			$license_owner_private_key = dapof_fs()->get_user()->secret_key;
			$hash                      = WPFA_Multitenant_Obj()->encrypt( $data_required_by_dashboard_site, $license_owner_private_key );
			$url                       = add_query_arg(
				array(
					'wpfa_hash'       => urlencode( $hash ),
					'wpfa_tenant_sso' => 'yes',
				),
				$dashboard_site_url,
			);
			return $url;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new WPFA_Client_Site();
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

if ( ! function_exists( 'WPFA_Client_Site_Obj' ) ) {
	/**
	 * @return WPFA_Client_Site
	 */
	function WPFA_Client_Site_Obj() {
		return WPFA_Client_Site::get_instance();
	}
}
WPFA_Client_Site_Obj();
