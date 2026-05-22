<?php
if ( ! class_exists( 'WPFA_MultiTenant_Dashboard_Site' ) ) {

	class WPFA_MultiTenant_Dashboard_Site {

		private static $instance = null;
		public $sites_table_name = 'wpfa_network_sites';

		private function __construct() {
		}

		public function init() {
			$this->maybe_create_network_sites_table();
			$this->maybe_auto_log_in_from_client_site();
			$this->maybe_clear_network_sites_table();

			add_filter( 'wp_frontend_admin/global_dashboard/network_sites_options', array( $this, 'get_network_sites_options' ) );
			add_filter( 'wp_frontend_admin/global_dashboard/current_site_url', array( $this, 'get_client_site_url' ) );
			add_filter( 'wp_frontend_admin/global_dashboard/manageable_sites', array( $this, 'get_manageable_sites' ), 10, 2 );
			if ( is_admin() ) {
				add_action( 'wp_frontend_admin/quick_settings/after_save', array( WPFA_Global_Dashboard_Obj(), 'save_meta_box__premium_only' ), 10, 2 );
			}
			add_filter( 'wp_frontend_admin/global_dashboard/site_id_for_admin_content', array( $this, 'get_site_id_for_admin_content' ) );
			add_filter( 'wp_frontend_admin/shortcode/admin_page_final_url', array( $this, 'get_admin_page_final_url' ) );
			add_action( 'wpfa_cron', array( $this, 'prepare_configuration_for_tenant_sites_sync' ) );
			add_action( 'wp_frontend_admin/options_page/after_troubleshooting_content', array( $this, 'render_troubleshooting_content' ) );
			add_filter( 'wp_frontend_admin/my_site_url', array( $this, 'get_mysite_url' ) );
			add_filter( 'allowed_redirect_hosts', array( $this, 'allow_safe_redirects_to_client_site' ) );
			add_filter( 'wp_frontend_admin/login_form_html', '__return_empty_string' );
			add_filter( 'vg_admin_to_frontend/frontend/js_data', array( $this, 'modify_js_data' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_ajax_vgfa_update_site_plugins_theme', array( $this, 'update_site_plugins_theme' ) );
			add_filter( 'wp_frontend_admin/site_active_plugins', array( $this, 'get_site_active_plugins' ) );
			add_filter( 'wp_frontend_admin/site_active_theme', array( $this, 'get_site_active_theme' ) );
			add_action( 'vg_plugin_sdk/settings/' . VG_Admin_To_Frontend::$textname . '/options', array( $this, 'add_settings_fields' ), 20 );
			if ( WPFA_Multitenant::is_wildcloud() ) {
				add_action( 'update_option_' . VG_Admin_To_Frontend::$textname, array( $this, 'after_wpfa_option_updated' ) );
			}

			if ( VG_Admin_To_Frontend_Obj()->is_master_user() && ! empty( $_GET['wpfa_download_config_file'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpfa' ) ) {
				$this->download_zip_file_from_config_plugin();
			}
		}

		function download_zip_file_from_config_plugin() {

			$file_content = WPFA_Multitenant_Obj()->get_config_file_contents();
			$zip          = new ZipArchive();
			$file_name    = 'wp-frontend-admin-configuration.zip';

			if ( ! function_exists( 'wp_tempname' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$file_path = wp_tempnam( $file_name );

			if ( $zip->open( $file_path, ZIPARCHIVE::CREATE ) !== true ) {
				die( 'WP Frontend Admin: Can not create zip file' );
			}

			$zip->addFromString( 'wp-frontend-admin-configuration/index.php', $file_content );
			$zip->close();

			header( 'Pragma: public' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Cache-Control: public' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
			header( 'Content-Transfer-Encoding: binary' );
			// make sure the file size isn't cached
			clearstatcache();
			header( 'Content-Length: ' . filesize( $file_path ) );
			readfile( $file_path );
			die();
		}
		function after_wpfa_option_updated() {
			$config_file_path = VG_Admin_To_Frontend_Obj()->get_file_config_file_path();
			$directory        = dirname( $config_file_path );
			wp_mkdir_p( $directory );
			$file_content = WPFA_Multitenant_Obj()->get_config_file_contents();
			file_put_contents( $config_file_path, $file_content );
		}
		function add_settings_fields( $sections ) {
			// Render the long explanations after the first option in the tab
			$sections['multitenant']['fields'][0]['callback_after_field'] = array( $this, 'render_config_explainer_for_tenants' );

			if ( ! WPFA_Multitenant::is_wildcloud() ) {
				$sections['multitenant']['fields'][] = array(
					'id'       => 'tenant_sync_minutes',
					'type'     => 'text',
					'validate' => 'numeric',
					'title'    => __( 'How often should the tenant sites sync settings with the dashboard site?', VG_Admin_To_Frontend::$textname ),
					'desc'     => __( 'By default, you work on your dashboard site and the new WP Frontend Admin settings applied in the dashboard site will be synced with the tenants every 60 minutes, but you can set a low amount of minutes here when you\'re developing the sites to see your configuration applied faster. But increase the number when you finish because this can cause your tenant sites to make unnecessary requests to your dashboard server and overload it if you have a lot of tenants.', VG_Admin_To_Frontend::$textname ),
				);
			}
			$sections['multitenant']['fields'][] = array(
				'id'       => 'tenant_superadmin_emails',
				'type'     => 'text',
				'title'    => __( 'What accounts have access to the wp-admin dashboard?', VG_Admin_To_Frontend::$textname ),
				'desc'     => __( 'Please enter multiple email addresses separated with commas. Any account that logs in with any of those email addresses will skip all the restrictions and be able to access wp-admin in the tenant sites.', VG_Admin_To_Frontend::$textname ),
			);
			return $sections;
		}

		function render_config_explainer_for_tenants( $field ) {
			if ( WPFA_Multitenant_Obj()->get_environment() !== 'dashboard_site' ) {
				return;
			}
			?>
			<div class="wpfa-file-config-tenants">
				<?php if ( WPFA_Multitenant::is_wildcloud() ) { ?>
					<p><?php _e( 'There are 2 ways to sync the WP Frontend Admin configuration between your dashboard site and tenant sites.', VG_Admin_To_Frontend::$textname ); ?></p>
					<p><?php _e( 'You can manually download this configuration file and upload it to your version site as a plugin:', VG_Admin_To_Frontend::$textname ); ?> <a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpfa_download_config_file', 1 ), 'wpfa' ) ); ?>"><?php _e( 'Download file', VG_Admin_To_Frontend::$textname ); ?></a></p>
					<p><?php _e( 'Or you can wait and your tenant sites will sync with your dashboard site automatically every hour, but only if they are properly connected to the dashboard site already', VG_Admin_To_Frontend::$textname ); ?></p>
				<?php } else { ?>
					<p><?php _e( 'There are 2 ways to sync the WP Frontend Admin configuration between your dashboard site and tenant sites.', VG_Admin_To_Frontend::$textname ); ?></p>
					<p><?php _e( 'You can manually download this configuration file and upload it to your tenant sites in the wp-content/plugins directory:', VG_Admin_To_Frontend::$textname ); ?> <a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpfa_download_config_file', 1 ), 'wpfa' ) ); ?>"><?php _e( 'Download file', VG_Admin_To_Frontend::$textname ); ?></a></p>
					<p><?php _e( 'Or you can wait and your tenant sites will sync with your dashboard site automatically every hour, but only if they are properly connected to the dashboard site already', VG_Admin_To_Frontend::$textname ); ?></p>
				<?php } ?>
			
				<?php if ( ! is_ssl() ) { ?>
					<p style="color:red;"><?php _e( 'IMPORTANT: You need to use HTTPS on your dashboard site and all the tenant sites, otherwise the Single Sign On will not work. You can ask your hosting provider about this if you don\'t know how.', VG_Admin_To_Frontend::$textname ); ?></p>
				<?php } ?>
			</div>
			<?php
		}

		function get_site_active_theme( $active_theme ) {
			$external_theme = $this->get_site_meta( 'active_theme' );
			if ( ! empty( $external_theme ) ) {
				$active_theme = $external_theme;
			}
			return $active_theme;
		}


		function get_site_active_plugins( $active_plugins ) {
			$external_plugins = $this->get_site_meta( 'active_plugins' );
			if ( ! empty( $external_plugins ) ) {
				$active_plugins = $external_plugins;
			}
			return $active_plugins;
		}

		function update_site_plugins_theme() {
			if ( ( empty( $_POST['plugins'] ) && empty( $_POST['theme'] ) ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wpfa' ) ) {
				wp_send_json_error();
			}

			$license_owner_private_key = dapof_fs()->get_user()->secret_key;

			if ( ! empty( $_POST['plugins'] ) ) {
				$plugins = $this->decrypt( $_POST['plugins'], $license_owner_private_key );
				if ( ! empty( $plugins ) ) {
					$plugins = array_map( 'sanitize_text_field', json_decode( $plugins, true ) );
					$this->update_site_meta( 'last_plugin_change', time() );
					$this->update_site_meta( 'active_plugins', $plugins );
					$this->update_site_meta( 'last_plugins_hash', sanitize_text_field( $_POST['last_plugins_hash'] ) );
				} else {
					wp_send_json_error( 'decryption_failed' );
				}
			}
			if ( ! empty( $_POST['theme'] ) ) {
				$theme = $this->decrypt( $_POST['theme'], $license_owner_private_key );
				if ( ! empty( $theme ) ) {
					$theme = sanitize_text_field( $theme );
					$this->update_site_meta( 'last_theme_change', time() );
					$this->update_site_meta( 'active_theme', $theme );
				} else {
					wp_send_json_error( 'decryption_failed' );
				}
			}
			wp_send_json_success();
		}

		function enqueue_assets() {
			wp_enqueue_script( 'wpfa-dashboard-js', plugins_url( '/dashboard.js', __FILE__ ), array( 'vg-frontend-admin-init' ), filemtime( __DIR__ . '/dashboard.js' ) );
		}

		function get_site_meta( $key, $default = null, $id = null ) {
			$site = $this->get_local_site( $id );
			if ( ! $site ) {
				return null;
			}

			$out = isset( $site->meta[ $key ] ) ? $site->meta[ $key ] : null;
			if ( is_null( $out ) ) {
				$out = $default;
			}
			return $out;
		}

		function update_site_meta( $key, $value, $id = null ) {
			global $wpdb;
			$site = $this->get_local_site( $id );
			if ( ! $site ) {
				return;
			}

			$site->meta[ $key ] = $value;
			$wpdb->update(
				$this->sites_table_name,
				array(
					'meta' => wp_json_encode( $site->meta ),
				),
				array(
					'id' => $site->id,
				)
			);
		}

		function modify_js_data( $data ) {
			// We need to change this value, so the stateful hashes use the client site wp-admin instead of the dashboard site wp-admin prefixes
			$data['wpadmin_base_url'] = $this->get_admin_page_final_url( $data['wpadmin_base_url'] );

			if ( strpos( $data['wpadmin_base_url'], '?' ) !== false ) {
				$data['wpadmin_base_url'] = strtok( $data['wpadmin_base_url'], '?' );
			}
			$data['current_site_last_plugin_update'] = VG_Admin_To_Frontend_Obj()->get_settings( 'enable_required_plugins_check' ) ? (int) $this->get_site_meta( 'last_plugin_change', time() ) : false;
			$data['current_site_last_plugins_hash']  = VG_Admin_To_Frontend_Obj()->get_settings( 'enable_required_plugins_check' ) ? $this->get_site_meta( 'last_plugins_hash', time() ) : false;
			$data['current_site_last_theme_update']  = VG_Admin_To_Frontend_Obj()->get_settings( 'enable_required_themes_check' ) ? (int) $this->get_site_meta( 'last_theme_change', time() ) : false;
			return $data;
		}

		function allow_safe_redirects_to_client_site( $hosts ) {
			global $wpdb;

			$client_urls = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT url FROM %i', $this->sites_table_name ) );
			foreach ( $client_urls as $client_url ) {
				if ( $client_url ) {
					$client_host = parse_url( $client_url, PHP_URL_HOST );
					if ( ! in_array( $client_host, $hosts, true ) ) {
						$hosts[] = $client_host;
					}
				}
			}
			return $hosts;
		}

		function get_mysite_url( $url ) {
			$url = $this->get_client_site_url();
			return $url;
		}

		function maybe_clear_network_sites_table() {
			global $wpdb;
			if ( empty( $_GET['wpfa_multitenant_clear_network_sites'] ) || empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wpfa' ) ) {
				return;
			}

			$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $this->sites_table_name ) );
			$wpdb->delete(
				$wpdb->usermeta,
				array(
					'meta_key' => 'wpfa_external_sites',
				)
			);

			$redirect_to = esc_url( remove_query_arg( array( 'wpfa_multitenant_clear_network_sites', '_wpnonce' ) ) );
			wp_safe_redirect( $redirect_to );
			exit();
		}

		function render_troubleshooting_content() {
			global $wpdb;
			?>
			<div class="wpfa-multitenant-troubleshooting">				
				<h3><?php esc_html_e( 'Multitenant', VG_Admin_To_Frontend::$textname ); ?></h3>
				<p><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpfa_multitenant_clear_network_sites', 1 ), 'wpfa' ) ); ?>"><?php esc_html_e( 'Clear network sites table', VG_Admin_To_Frontend::$textname ); ?></a></p>
				<h3><?php esc_html_e( 'Tenant sites', VG_Admin_To_Frontend::$textname ); ?></h3>
				<p><?php esc_html_e( 'Showing the first 100 sites for performance reasons.', VG_Admin_To_Frontend::$textname ); ?></p>
				<?php
				$rows = $wpdb->get_results( "SELECT * FROM {$this->sites_table_name} LIMIT 100", ARRAY_A );
				echo '<table>';
				// Print header row.
				if ( ! empty( $rows ) ) {
					echo '<thead><tr>';
					foreach ( array_keys( $rows[0] ) as $column ) {
						echo '<th>' . esc_html( $column ) . '</th>';
					}
					echo '</tr></thead>';
				}
				// Print data rows.
				foreach ( $rows as $row ) {
					echo '<tr>';
					foreach ( $row as $key => $value ) {
						if ( $key == 'meta' ) {
							echo '<td>' . wp_kses_post( json_encode( json_decode( $value, true ), JSON_PRETTY_PRINT ) ) . '</td>';
						} else {
							echo '<td>' . wp_kses_post( $value ) . '</td>';
						}
					}
					echo '</tr>';
				}
				echo '</table>';
				?>
			</div>
			<?php
		}

		function prepare_configuration_for_tenant_sites_sync() {
			if ( ! wp_doing_cron() ) {
				return;
			}
			$sync_minutes   = (int) VG_Admin_To_Frontend_Obj()->get_settings( 'tenant_sync_minutes', 60 );
			$last_sync_time = get_option( 'wpfa_last_sync_request' );
			if ( ! empty( $last_sync_time ) && $last_sync_time > ( time() - ( MINUTE_IN_SECONDS * $sync_minutes ) ) ) {
				return;
			}

			$options                   = WPFA_Multitenant_Obj()->get_configuration_for_tenant_site();
			$license_owner_private_key = dapof_fs()->get_user()->secret_key;
			$hash                      = md5( 'wpfa-dashboard-site-config' . $license_owner_private_key );
			$file_path                 = __DIR__ . '/' . sanitize_file_name( $hash ) . '.json';

			file_put_contents( $file_path, wp_json_encode( $options ) );
			update_option( 'wpfa_last_sync_request', time() );
		}

		function get_admin_page_final_url( $url ) {
			$site = $this->get_local_site();
			if ( ! $site ) {
				return $url;
			}
			$url = str_replace( admin_url(), $site->wp_admin_url, $url );
			$url = add_query_arg( 'wpfam', 1, $url );
			return $url;
		}

		function get_site_id_for_admin_content() {
			$blog_id = (int) WPFA_Global_Dashboard_Obj()->get_current_site_id();
			return $blog_id;
		}

		function get_manageable_sites( $sites, $user_id ) {
			global $wpdb;

			if ( VG_Admin_To_Frontend_Obj()->is_master_user() ) {
				$site_ids   = $wpdb->get_col( "SELECT id FROM {$this->sites_table_name}" );
				$site_ids[] = 0;
			} else {
				$user_sites = get_user_meta( $user_id, 'wpfa_external_sites', true );
				$site_ids   = wp_list_pluck( $user_sites, 'local_site_id' );
			}

			$out = array_map( 'intval', $site_ids );
			return $out;
		}

		function get_client_site_url() {
			$blog_id = (int) WPFA_Global_Dashboard_Obj()->get_current_site_id();
			$site    = $this->get_local_site( $blog_id );

			if ( $site ) {
				$out = $site->url;
			} else {
				$out = home_url();
			}
			return $out;
		}

		function get_network_sites_options() {
			global $wpdb;
			$sites      = $wpdb->get_results( "SELECT * FROM {$this->sites_table_name}" );
			$options    = wp_list_pluck( $sites, 'url', 'id' );
			$options[0] = home_url();
			return $options;
		}

		function get_local_site( $id = null ) {
			global $wpdb;

			if ( is_null( $id ) ) {
				$id = (int) WPFA_Global_Dashboard_Obj()->get_current_site_id();
			}

			if ( ! $id ) {
				return false;
			}
			$out = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->sites_table_name} WHERE id = %d", $id ) );

			if ( ! $out ) {
				return false;
			}
			if ( empty( $out->meta ) ) {
				$out->meta = array();
			} else {
				$out->meta = json_decode( $out->meta, true );
			}
			return $out;
		}

		function maybe_create_network_sites_table() {
			global $wpdb;
			$this->sites_table_name = $wpdb->prefix . $this->sites_table_name;
			$table_exists           = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->sites_table_name ) ) === $this->sites_table_name;
			if ( $table_exists ) {
				return;
			}
			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}
			$sql = "CREATE TABLE `{$wpdb->prefix}wpfa_network_sites` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `url` VARCHAR(400) NOT NULL,
                    `wp_admin_slug` VARCHAR(100) NOT NULL,
                    `wp_admin_url` VARCHAR(400) NOT NULL,
                    `name` VARCHAR(400) NOT NULL,
					`meta` varchar(4000) NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `url` (`url`),
                    UNIQUE KEY unique_url_wp_admin_slug (`url`, `wp_admin_slug`)
                )           
                $collate";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		public function get_local_site_id( $site_url, $wp_admin_slug, $name ) {
			global $wpdb;
			$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->sites_table_name} WHERE url = %s AND wp_admin_slug = %s", $site_url, $wp_admin_slug ) );

			if ( $existing_id ) {
				$wpdb->update(
					$this->sites_table_name,
					array(
						'name' => sanitize_text_field( $name ),
					),
					array(
						'id' => $existing_id,
					)
				);
				return $existing_id;
			}

			$wpdb->insert(
				$this->sites_table_name,
				array(
					'url'           => esc_url_raw( $site_url ),
					'wp_admin_slug' => sanitize_text_field( $wp_admin_slug ),
					'name'          => sanitize_text_field( $name ),
					'wp_admin_url'  => esc_url_raw( $site_url ) . sanitize_text_field( $wp_admin_slug ),
				)
			);
			return $wpdb->insert_id;
		}

		public function maybe_auto_log_in_from_client_site() {
			if ( empty( $_GET['wpfa_tenant_sso'] ) || empty( $_GET['wpfa_hash'] ) ) {
				return;
			}

			$license_owner_private_key = dapof_fs()->get_user()->secret_key;
			// We accept the hash as a raw value or as url-encoded value
			$data_from_client_site = $this->decrypt( urldecode( $_GET['wpfa_hash'] ), $license_owner_private_key );
			if ( ! $data_from_client_site ) {
				$data_from_client_site = $this->decrypt( $_GET['wpfa_hash'], $license_owner_private_key );
			}
			if ( ! is_string( $data_from_client_site ) || strpos( $data_from_client_site, '{' ) !== 0 ) {
				die( 'Invalid hash' );
			}
			$data_from_client_site = json_decode( $data_from_client_site, true );

			$roles         = array_map( 'sanitize_text_field', $data_from_client_site['roles'] );
			$user_id       = (int) $data_from_client_site['user_id'];
			$user_email    = sanitize_email( $data_from_client_site['user_email'] );
			$site_url      = esc_url_raw( $data_from_client_site['site_url'] );
			$wp_admin_slug = sanitize_text_field( $data_from_client_site['wp_admin_slug'] );
			$license_id    = (int) $data_from_client_site['wpfa_license_id'];
			$install_id    = (int) $data_from_client_site['wpfa_install_id'];
			$created_at    = (int) $data_from_client_site['created_at'];
			$site_name     = sanitize_text_field( $data_from_client_site['site_name'] );

			// The SSO token expires in 1 minute
			if ( $created_at < ( time() - MINUTE_IN_SECONDS ) ) {
				wp_redirect( add_query_arg( 'wpfa_error', 'token_expired', $site_url ) );
				exit();
			}

			try {
				// This will throw an Exception if the license is invalid, so no if needed here.
				$is_request_valid = $this->is_request_license_valid( $install_id, $license_id );
				$user_id          = $this->get_local_user_id( $user_email, $user_id, $roles, $wp_admin_slug, $site_url, $site_name );
				$user             = get_user_by( 'ID', $user_id );

				wp_clear_auth_cookie();
				wp_set_current_user( $user->ID );
				wp_set_auth_cookie( $user->ID );
				do_action( 'wp_login', $user->user_login, $user );

				$frontend_dashboard_url = VG_Admin_To_Frontend_Obj()->get_settings( 'redirect_to_frontend', home_url( '/' ) );
				wp_redirect( $frontend_dashboard_url );
			} catch ( Exception $e ) {
				// Redirect the user back to their origin site if anything went wrong with the dashboard log in
				wp_redirect( add_query_arg( 'wpfa_error', $e->getMessage(), $site_url ) );
			}
			exit();
		}

		function get_local_user_id( $email, $external_wp_id, $external_roles, $wp_admin_slug, $site_url, $site_name ) {
			$user_id = email_exists( $email );
			if ( ! $user_id ) {
				$user_id = wp_insert_user(
					array(
						'user_email' => $email,
						'user_login' => preg_replace( '/\@.+$/', '', $email ) . wp_generate_password( 5, false, false ),
						'role'       => current( $external_roles ),
					)
				);
			}

			if ( ! $user_id || is_wp_error( $user_id ) ) {
				throw new Exception( 'dashboard_user_not_available' );
			}

			$sites = get_user_meta( $user_id, 'wpfa_external_sites', true );
			if ( ! is_array( $sites ) ) {
				$sites = array();
			}
			$local_site_id           = $this->get_local_site_id( $site_url, $wp_admin_slug, $site_name );
			$sites[ $local_site_id ] = array(
				'user_id'       => $external_wp_id,
				'local_site_id' => $local_site_id,
			);
			update_user_meta( $user_id, 'wpfa_external_sites', $sites );

			return $user_id;
		}

		function is_request_license_valid( $install_id, $license_id ) {

			$install_id = (string) $install_id;
			$license_id = (string) $license_id;

			$api = dapof_fs()->get_api_user_scope();
			// on success, looks like the response from https://freemius.docs.apiary.io/#reference/installs-/-a.k.a.-sites/install/retrieve-install
			$site_object = $api->get( sprintf( '/plugins/%d/installs.json?ids=%d', dapof_fs()->get_id(), $install_id ) );
			// Check for error
			if ( property_exists( $site_object, 'error' ) || empty( $site_object->installs ) ) {
				throw new Exception( 'license_not_found' );
			}
			$site_object = $site_object->installs[0];

			// Verify the site ID they sent is for the license ID we want to validate
			if ( $site_object->license_id !== $license_id ) {
				throw new Exception( 'site_license_mismatch' );
			}

			$license_id_from_dashboard_site = (string) dapof_fs()->_get_license()->id;
			if ( $license_id_from_dashboard_site !== $license_id ) {
				throw new Exception( 'dashboard_site_client_site_license_mismatch' );
			}
			if ( ! dapof_fs()->can_use_premium_code__premium_only() ) {
				throw new Exception( 'license_expired_cancelled' );
			}
			if ( ! dapof_fs()->is_plan( 'platform', true ) ) {
				throw new Exception( 'license_is_not_platform_plan' );
			}
			return true;
		}

		function decrypt( $encryptedText, $key ) {
			$data      = base64_decode( $encryptedText );
			$iv        = substr( $data, 0, openssl_cipher_iv_length( 'aes-256-cbc' ) );
			$decrypted = openssl_decrypt( substr( $data, openssl_cipher_iv_length( 'aes-256-cbc' ) ), 'aes-256-cbc', $key, 0, $iv );
			return $decrypted;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new WPFA_MultiTenant_Dashboard_Site();
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

if ( ! function_exists( 'WPFA_MultiTenant_Dashboard_Site_Obj' ) ) {
	/**
	 * @return WPFA_MultiTenant_Dashboard_Site
	 */
	function WPFA_MultiTenant_Dashboard_Site_Obj() {
		return WPFA_MultiTenant_Dashboard_Site::get_instance();
	}
}
WPFA_MultiTenant_Dashboard_Site_Obj();
