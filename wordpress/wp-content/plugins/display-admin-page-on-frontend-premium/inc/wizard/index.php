<?php

if ( ! class_exists( 'WPFA_Initial_Setup_Wizard' ) ) {
	/**
	 * Main class
	 */
	class WPFA_Initial_Setup_Wizard {

		private static $instance               = null;
		private $list_of_plugins_on_site       = array();
		private $url_base_plugin               = '';
		private $url_admin                     = '';
		private $nonce_for_ajax_actions        = '';
		private $menu_name                     = 'Dashboard';
		private $list_plugins_required         = array();
		private $current_view_plugin           = '';
		private $plugin_activated_from_network = false;
		private $external_api                  = array(
			'urlBasePluginsRequired'       => 'https://api.wpfrontendadmin.com/templates/required-plugins',
			'urlBaseTemplateElementorWpfa' => 'https://api.wpfrontendadmin.com/templates',
			'urlBaseTemplateWpfa'          => 'https://api.wpfrontendadmin.com/templates',
		);
		private $ajax_request_timeout          = 5;

		private function __construct() {
		}

		public function init() {
			add_action( 'init', array( $this, 'plugin_init' ), 20 );
		}

		public function is_wizard_allowed(){
			
			if ( ! VG_Admin_To_Frontend_Obj()->is_master_user() ) {
				return false;
			}
			$can_master_user_use_wizard = apply_filters( 'wp_frontend_admin/can_master_user_initialize_wizard', true );
			if ( ! $can_master_user_use_wizard ) {
				return false;
			}
			if ( ! dapof_fs()->is_plan__premium_only( 'platform', true ) ) {
				return false;
			}
			return true;
		}

		public function plugin_init() {
			if ( ! $this->is_wizard_allowed() ) {
				return;
			}
			if ( dapof_fs()->is_plan__premium_only( 'platform', true ) ) {
				$is_multisite = is_multisite();

				if ( ! $is_multisite ) {
					add_action( 'admin_menu', array( $this, 'add_menu_for_wp_fronted_admin' ) );
					// initialize plugin data.
					add_action( 'admin_init', array( $this, 'admin_init' ) );
					return;
				}

				$current_link = '';
				if ( ! empty( $_GET['page'] ) ) {
					$current_link = wp_unslash( sanitize_text_field( $_GET['page'] ) );
				}

				if ( $is_multisite && is_network_admin() ) {
					add_action( 'network_admin_menu', array( $this, 'add_menu_for_wp_fronted_admin' ) );
				} elseif ( $is_multisite && 'wpfa_wizard_initial' === $current_link ) {
					add_action( 'admin_menu', array( $this, 'add_menu_for_wp_fronted_admin' ) );

					$step_view = ! empty( $_GET['view'] ) ? wp_unslash( sanitize_text_field( $_GET['view'] ) ) : '';
					$template  = ! empty( $_GET['template'] ) ? wp_unslash( sanitize_text_field( $_GET['template'] ) ) : '';

					if ( 'dashboard-pages' === $step_view && $template ) {
						$this->current_view_plugin           = 'dashboard-menu';
						$this->plugin_activated_from_network = true;
					}
				}

				// initialize plugin data.
				add_action( 'admin_init', array( $this, 'admin_init' ) );
			}
		}

		/**
		 * Function for initializing plugin data
		 */
		public function admin_init() {
			// Code to initialize ajax calls.
			add_action( 'wp_ajax_create_custom_menu_for_wpfa', array( $this, 'create_custom_menu' ) );
			add_action( 'wp_ajax_create_pages', array( $this, 'create_pages' ) );
			add_action( 'wp_ajax_import_template_elementor', array( $this, 'import_template_elementor' ) );
			add_action( 'wp_ajax_search_site_multisite', array( $this, 'search_site_multisite' ) );
			add_action( 'wp_ajax_install_required_plugin', array( $this, 'install_required_plugin' ) );
			add_action( 'wp_ajax_activate_plugin_from_ajax', array( $this, 'activate_plugin_from_ajax' ) );
			add_action( 'wp_ajax_get_menu_items', array( $this, 'get_menu_items' ) );
			add_action( 'wp_ajax_get_list_of_required_plugins', array( $this, 'get_list_of_required_plugins' ) );
			add_action( 'wp_ajax_get_list_of_elementor_templates', array( $this, 'get_list_of_elementor_templates' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_app_initial_wizard' ) );
		}

		public function _get_templates_from_api() {
			$transient = 'wpfa_wizard_elementor_templates';
			$data      = get_transient( $transient );

			if ( ! $data ) {
				$response = wp_remote_get(
					$this->external_api['urlBaseTemplateElementorWpfa'],
					array(
						'timeout' => $this->ajax_request_timeout,
						'headers' => $this->get_auth_headers(),
					)
				);
				if ( ! is_wp_error( $response ) ) {
					$data = json_decode( $response['body'] );
					set_transient( $transient, $data, WEEK_IN_SECONDS );
				}
			}

			return $data;
		}

		public function get_list_of_elementor_templates() {
			$administrator_permission = is_multisite() ? 'manage_network' : 'manage_options';

			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nonce_ajax_actions_wpfa_template' )
				|| ! current_user_can( 'edit_posts' ) || ! current_user_can( $administrator_permission ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'You do not have permission to perform this action', 'vg_admin_to_frontend' ),
					)
				);
				exit();
			}

			$response_list_templates = $this->_get_templates_from_api();
			if ( empty( $response_list_templates ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'We weren\'t able to retrieve the list of available templates, please try again later', 'vg_admin_to_frontend' ),
					)
				);
			}
			wp_send_json_success( $response_list_templates );
		}

		public function _get_required_plugins_from_api() {
			$transient_key = 'wpfa_wizard_required_plugins';
			$data          = get_transient( $transient_key );

			if ( ! $data ) {
				$response_required_plugins = wp_remote_get(
					$this->external_api['urlBasePluginsRequired'],
					array(
						'timeout' => $this->ajax_request_timeout,
						'headers' => $this->get_auth_headers(),
					)
				);

				if ( ! is_wp_error( $response_required_plugins ) ) {
					$data = json_decode( $response_required_plugins['body'] );
					set_transient( $transient_key, $data, WEEK_IN_SECONDS );
				}
			}
			return $data;
		}
		public function get_list_of_required_plugins() {
			$administrator_permission = is_multisite() ? 'manage_network' : 'manage_options';

			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nonce_ajax_actions_wpfa_template' )
				|| ! current_user_can( 'edit_posts' ) || ! current_user_can( $administrator_permission ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'You do not have permission to perform this action', 'vg_admin_to_frontend' ),
					)
				);
			}

			$response_list_plugins = $this->_get_required_plugins_from_api();
			if ( empty( $response_list_plugins ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'We weren\'t able to retrieve the list of required plugins, please try again later.', 'vg_admin_to_frontend' ),
					)
				);
			}
			wp_send_json_success( $response_list_plugins );
		}

		public function get_menu_items() {
			$administrator_permission = is_multisite() ? 'manage_network' : 'manage_options';

			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nonce_ajax_actions_wpfa_template' )
				|| ! current_user_can( 'edit_posts' ) || ! current_user_can( $administrator_permission ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'You do not have permission to perform this action', 'vg_admin_to_frontend' ),
					)
				);
				exit();
			}

			$list_items_menu = wp_get_nav_menu_items( $this->menu_name );

			if ( ! $list_items_menu ) {
				wp_send_json_success( array() );
			}

			wp_send_json_success( $list_items_menu );
		}

		public function search_site_multisite() {
			global $wpdb;

			$administrator_permission = is_multisite() ? 'manage_network' : 'manage_options';

			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nonce_ajax_actions_wpfa_template' )
				|| ! current_user_can( 'edit_posts' ) || ! current_user_can( $administrator_permission ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'You do not have permission to perform this action', 'vg_admin_to_frontend' ),
					)
				);
			}

			$sites = array();

			if ( empty( $_POST['search'] ) ) {
				$args = array(
					'number' => 10,
				);

				$sites = get_sites( $args );
			} else {
				$search_site = sanitize_text_field( wp_unslash( $_POST['search'] ) );

				$sites = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT blog_id, domain, path FROM $wpdb->blogs 
						WHERE (path LIKE %s OR domain LIKE %s) 
						AND archived = '0' AND deleted = '0' AND spam = '0' ORDER BY blog_id LIMIT 10",
						'%' . $wpdb->esc_like( $search_site ) . '%',
						'%' . $wpdb->esc_like( $search_site ) . '%'
					)
				);
			}

			$sites_format = array();

			foreach ( $sites as $subsite ) {
				$subsite_id   = $subsite->blog_id;
				$subsite_name = get_blog_details( $subsite_id )->blogname;
				$subsite_url  = get_admin_url( $subsite_id );

				$sites_format[] = array(
					'id'       => $subsite_id,
					'name'     => $subsite_name,
					'urlAdmin' => $subsite_url,
				);
			}

			wp_send_json_success( $sites_format );
		}

		public function get_authorization_hash() {
			// license key, site ID, and secret key
			$license_id       = dapof_fs()->_get_license()->id;
			$install_id       = dapof_fs()->get_site()->id;
			$site_private_key = dapof_fs()->get_site()->secret_key;

			// create the signature that verifies we own this license and install.
			$nonce   = time();
			$pk_hash = hash( 'sha512', $site_private_key . '|' . $nonce );
			$auth    = base64_encode( implode( '|', array( $pk_hash, $nonce, $license_id, $install_id ) ) );
			return $auth;
		}

		public function get_auth_headers() {
			$headers = array(
				'Authorization' => $this->get_authorization_hash(),
			);
			return $headers;
		}

		public function _get_template_contents_from_api( $file, $language ) {
			$transient    = 'wpfa_wizard_template1' . $file . $language;
			$file_content = get_transient( $transient );

			if ( ! $file_content ) {
				$url_template = $this->external_api['urlBaseTemplateWpfa'] . '/' . $language . '/' . $file;

				$response_template = wp_remote_get(
					$url_template,
					array(
						'timeout' => $this->ajax_request_timeout,
						'headers' => $this->get_auth_headers(),
					)
				);

				if ( ! is_wp_error( $response_template ) ) {
					// Import elementor template.
					$file_content = sanitize_text_field( $response_template['body'] );
					set_transient( $transient, $file_content, WEEK_IN_SECONDS );
				}
			}
			return $file_content;
		}

		/**
		 * Function to import elementor templates
		 *
		 * @return void
		 */
		public function import_template_elementor() {
			$administrator_permission = is_multisite() ? 'manage_network' : 'manage_options';

			if ( empty( $_POST['template_elementor'] ) || empty( $_POST['nonce'] )
				|| ! wp_verify_nonce( $_POST['nonce'], 'nonce_ajax_actions_wpfa_template' ) || ! current_user_can( 'edit_posts' )
				|| ! current_user_can( $administrator_permission ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'You do not have permission to perform this action', 'vg_admin_to_frontend' ),
					)
				);
			}

			$file              = sanitize_text_field( wp_unslash( $_POST['template_elementor'] ) );
			$list_of_templates = $this->_get_templates_from_api();
			if ( empty( $list_of_templates ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'We weren\'t able to retrieve the list of available templates, please try again later', 'vg_admin_to_frontend' ),
					)
				);
			}

			$id_of_templates = array_column( $list_of_templates, 'idTemplate' );

			if ( ! in_array( $file, $id_of_templates, true ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'The template that you want to import does not exist. Please try with another template', 'vg_admin_to_frontend' ),
					)
				);
			}

			// Automatically set the template color as main color
			$template = array_values( wp_list_filter( $list_of_templates, array( 'idTemplate' => $file ) ) );
			if ( property_exists( $template[0], 'mainColor' ) && $template[0]->mainColor ) {
				VG_Admin_To_Frontend_Obj()->update_option( 'main_color', $template[0]->mainColor );
			}

			// Get language to verify which template to use.
			$language = get_locale();
			if ( ! preg_match( '/^(en|es)_/', $language ) ) {
				$language = 'en_US';
			}

			if ( str_starts_with( $language, 'es_' ) ) {
				$file = 'es-' . $file;
			}

			// Get templates by name to verify that the template to be imported does not exist.
			$args                = array(
				'post_type'              => 'elementor_library',
				'tabs_group'             => 'library',
				'elementor_library_type' => 'page',
				'title'                  => $file,
				'numberposts'            => 1,
			);
			$elementor_templates = get_posts( $args );

			if ( count( $elementor_templates ) > 0 ) {
				$data_templates = array(
					'id'         => $elementor_templates[0]->ID,
					'post_title' => $elementor_templates[0]->post_title,
				);

				wp_send_json_success( $data_templates );
			}
			$file_content = $this->_get_template_contents_from_api( $file, $language );
			if ( empty( $file_content ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'We weren\'t able to retrieve the selected template, please try again later (1).', 'vg_admin_to_frontend' ),
					)
				);
			}

			if ( 'Error missing parameters' === $file_content || 'Error parameter: language is invalid' === $file_content
				|| 'Error parameter: id_template is invalid' === $file_content || 'File not found error' === $file_content ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'We weren\'t able to retrieve the selected template, please try again later (2).', 'vg_admin_to_frontend' ),
					)
				);
			}

			$response_elementor = \Elementor\Plugin::instance()->templates_manager->import_template(
				array(
					'fileData' => $file_content,
					'fileName' => $file . '.json',
				)
			);

			if ( is_wp_error( $response_elementor ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'There was an error when creating the pages with Elementor', 'vg_admin_to_frontend' ) . ' : ' . esc_html( $response_elementor->get_error_message() ),
						'fileData' => $file_content,
						'fileName' => $file . '.json',
					)
				);
			}

			$data_template = array();

			if ( ! empty( $response_elementor ) && count( $response_elementor ) > 0 ) {
				$data_template = array(
					'id'         => $response_elementor[0]['template_id'],
					'post_title' => $response_elementor[0]['title'],
				);
			}

			wp_send_json_success( $data_template );
		}

		public function create_custom_menu() {
			$administrator_permission = is_multisite() ? 'manage_network' : 'manage_options';

			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nonce_ajax_actions_wpfa_template' )
				|| ! current_user_can( 'edit_posts' ) || ! current_user_can( $administrator_permission ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'You do not have permission to perform this action', 'vg_admin_to_frontend' ),
					)
				);
			}

			$menu_exists = wp_get_nav_menu_object( $this->menu_name );

			if ( $menu_exists ) {
				wp_send_json_success(
					array(
						'idMenu' => $menu_exists->term_id,
					)
				);
			}

			$menu_id = wp_create_nav_menu( $this->menu_name );

			wp_send_json_success(
				array(
					'idMenu' => $menu_id,
				)
			);
		}

		public function get_name_of_page_to_create( $name_page_to_create ) {

			$name_page = ! empty( $name_page_to_create ) ? strtolower( $name_page_to_create ) : '';

			if ( 'donations' === $name_page
				|| 'donaciones' === $name_page ) {

				$language = get_locale();

				if ( str_starts_with( $language, 'es_' ) ) {
					$name_page_to_create = 'Formularios de Donación';
				} elseif ( str_starts_with( $language, 'en_' ) ) {
					$name_page_to_create = 'Donation Forms';
				}
			}
			return $name_page_to_create;
		}

		public function create_pages() {
			$administrator_permission = is_multisite() ? 'manage_network' : 'manage_options';

			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nonce_ajax_actions_wpfa_template' )
				|| ! current_user_can( 'edit_posts' ) || ! current_user_can( $administrator_permission ) ) {

				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'You do not have permission to perform this action', 'vg_admin_to_frontend' ),
					)
				);
			}

			$list_pages = ! empty( $_POST['data'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['data'] ) ) ) : false;

			if ( ! is_array( $list_pages ) || empty( $_POST['menu_id'] )
				|| empty( $_POST['type_page'] ) || empty( $_POST['id_template_elementor'] ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'Invalid parameters.', 'vg_admin_to_frontend' ),
					)
				);
			}

			$type_page        = sanitize_text_field( wp_unslash( $_POST['type_page'] ) );
			$valid_page_types = array( 'parent', 'child' );
			$menu_id          = intval( $_POST['menu_id'] );

			if ( ! $menu_id ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'The menu id is invalid', 'vg_admin_to_frontend' ),
					)
				);
			}

			if ( ! in_array( $type_page, $valid_page_types, true ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'Sorry, the type of page you have sent is not valid.', 'vg_admin_to_frontend' ),
					)
				);
			}

			// Verifying the format we receive in data of pages to create.
			$invalid_page_format = false;

			// If this is a multisite, automatically set the current subsite as the dashboard site
			if ( is_multisite() ) {
				VG_Admin_To_Frontend_Obj()->update_option( 'global_dashboard_id', get_current_blog_id() );
			}

			if ( 'parent' === $type_page ) {
				foreach ( $list_pages as $page ) {
					if ( ! property_exists( $page, 'menuName' ) || ! property_exists( $page, 'iconWp' )
					|| ! property_exists( $page, 'linkWp' ) || ! property_exists( $page, 'hideLabel' )
					|| ! property_exists( $page, 'typeLink' ) || ! property_exists( $page, 'menuPosition' ) ) {
						$invalid_page_format = true;
						break;
					}
				}
			} elseif ( 'child' === $type_page ) {
				foreach ( $list_pages as $page ) {
					if ( ! property_exists( $page, 'idPageParent' ) || ! property_exists( $page, 'idMenuParent' )
					|| ! property_exists( $page, 'menuName' ) || ! property_exists( $page, 'menuPosition' )
					|| ! property_exists( $page, 'linkWp' ) ) {
						$invalid_page_format = true;
						break;
					}
				}
			}

			if ( $invalid_page_format ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'Error. Missing data to create pages', 'vg_admin_to_frontend' ),
					)
				);
			}

			$pages_created         = array();
			$id_template_elementor = sanitize_text_field( wp_unslash( $_POST['id_template_elementor'] ) );

			if ( 'parent' === $type_page ) {

				foreach ( $list_pages as $index => $item_page ) {

					$type_link      = sanitize_text_field( $item_page->typeLink );
					$link_wp        = sanitize_text_field( $item_page->linkWp );
					$page_details   = array();
					$id_page_parent = 0;
					$menu_parent_id = 0;
					$page_name      = sanitize_text_field( wp_strip_all_tags( $item_page->menuName ) );

					if ( 'normal' === $type_link ) {
						$title_page_to_create = $this->get_name_of_page_to_create( $page_name );

						$page_details   = array(
							'post_title'   => $title_page_to_create,
							'post_content' => '',
							'post_status'  => 'publish',
							'post_type'    => 'page',
						);
						$id_page_parent = wp_insert_post( $page_details );

						// Updating page data so that it can be edited with elementor.
						$this->update_elementor_data( $id_page_parent, $id_template_elementor, $link_wp );

						// Add parent page to wpfa menu.
						$menu_parent_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							array(
								'menu-item-title'     => $page_name,
								'menu-item-object-id' => (int) $id_page_parent,
								'menu-item-object'    => 'page',
								'menu-item-status'    => 'publish',
								'menu-item-type'      => 'post_type',
								'menu-item-position'  => (int) $item_page->menuPosition,
							)
						);
					} elseif ( 'custom' === $type_link ) {
						// Add parent page to wpfa menu.
						$menu_parent_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							array(
								'menu-item-title'    => $page_name,
								'menu-item-url'      => $link_wp,
								'menu-item-status'   => 'publish',
								'menu-item-type'     => 'custom',
								'menu-item-position' => 99,
							)
						);
					}

					// Adding icon.
					if ( $item_page->iconWp ) {

						$hide_label  = ( 'yes' === $item_page->hideLabel ) ? 1 : 0;
						$icon_plugin = $this->plugin_wp_to_use_to_create_icons();

						if ( 'wp-menu-icons' === $icon_plugin ) {
							// Create icon with plugin: WP Menu Icons.
							$key_wp_menu_icons = ( defined( 'WPMI_DB_KEY' ) ) ? WPMI_DB_KEY : '';

							if ( empty( $key_wp_menu_icons ) ) {
								return;
							}

							$plugin_data_wp_menu_icons = array(
								'label'    => $hide_label,
								'position' => 'before',
								'align'    => 'middle',
								'size'     => '1.3',
								'icon'     => 'dashicons ' . sanitize_text_field( $item_page->iconWp ),
								'color'    => '',
							);

							update_post_meta( $menu_parent_id, $key_wp_menu_icons, $plugin_data_wp_menu_icons );
						} elseif ( 'menu-icons' === $icon_plugin ) {

							if ( ! class_exists( 'Menu_Icons_Meta' ) ) {
								return;
							}

							// Create icon with plugin: Menu Icons.
							$key_menu_icons = class_exists( 'Menu_Icons_Meta' ) ? Menu_Icons_Meta::KEY : '';

							if ( empty( $key_menu_icons ) ) {
								return;
							}

							$plugin_data_menu_icons = array(
								'type'           => 'dashicons',
								'icon'           => sanitize_text_field( $item_page->iconWp ),
								'hide_label'     => $hide_label,
								'position'       => 'before',
								'vertical_align' => 'middle',
								'font_size'      => '1.4',
								'svg_width'      => '1',
								'image_size'     => 'thumbnail',
							);
							update_post_meta( $menu_parent_id, $key_menu_icons, $plugin_data_menu_icons );
						}
					}

					$pages_created[] = array(
						'namePage'     => $page_name,
						'idBackend'    => (int) $id_page_parent,
						'idMenuParent' => (int) $menu_parent_id,
					);
				}

				wp_send_json_success( $pages_created );
			} elseif ( 'child' === $type_page ) {
				foreach ( $list_pages as $index => $item_page ) {
					$page_name            = sanitize_text_field( wp_strip_all_tags( $item_page->menuName ) );
					$title_page_to_create = $this->get_name_of_page_to_create( $page_name );
					$link_wp              = sanitize_text_field( $item_page->linkWp );

					$page_details  = array(
						'post_title'   => $title_page_to_create,
						'post_content' => '',
						'post_status'  => 'publish',
						'post_parent'  => (int) $item_page->idPageParent,
						'post_type'    => 'page',
					);
					$id_page_child = wp_insert_post( $page_details );

					// Updating page data so that it can be edited with elementor.
					$this->update_elementor_data( $id_page_child, $id_template_elementor, $link_wp );

					// Add sub-page to wpfa menu.
					wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-title'     => $page_name,
							'menu-item-object-id' => (int) $id_page_child,
							'menu-item-object'    => 'page',
							'menu-item-parent-id' => (int) $item_page->idMenuParent,
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => (int) $item_page->menuPosition,
						)
					);

					$pages_created[] = array(
						'namePage'     => $page_name,
						'idBackend'    => (int) $id_page_child,
						'idMenuParent' => (int) $item_page->idMenuParent,
					);
				}

				wp_send_json_success( $pages_created );
			}
		}

		public function plugin_wp_to_use_to_create_icons() {

			$list_plugins  = get_plugins();
			$plugins_icons = array(
				'wp-menu-icons' => array( 'slug' => '' ),
				'menu-icons'    => array( 'slug' => '' ),
			);

			foreach ( $list_plugins as $slug_plugin => $value_plugin ) {
				if ( str_contains( $slug_plugin, 'wp-menu-icons' ) ) {
					$plugins_icons['wp-menu-icons']['slug'] = $slug_plugin;
				} elseif ( str_contains( $slug_plugin, 'menu-icons' ) ) {
					$plugins_icons['menu-icons']['slug'] = $slug_plugin;
				}
			}

			$plugin_to_use_to_create_icons = '';

			if ( is_multisite() ) {
				foreach ( $plugins_icons as $key_plugin => $plugin_slug ) {
					if ( empty( $plugin_to_use_to_create_icons )
						&& is_plugin_active_for_network( $plugins_icons[ $key_plugin ]['slug'] ) ) {
						$plugin_to_use_to_create_icons = $key_plugin;
					}
				}
			} else {
				foreach ( $plugins_icons as $key_plugin => $plugin_slug ) {
					if ( empty( $plugin_to_use_to_create_icons ) && is_plugin_active( $plugins_icons[ $key_plugin ]['slug'] ) ) {
						$plugin_to_use_to_create_icons = $key_plugin;
					}
				}
			}

			return $plugin_to_use_to_create_icons;
		}

		public function update_elementor_data( $page_id, $id_template, $link_wp ) {

			if ( empty( $page_id ) || empty( $id_template ) || empty( $link_wp ) ) {
				return;
			}

			$page_id     = intval( $page_id );
			$id_template = intval( $id_template );
			$link_wp     = sanitize_text_field( $link_wp );

			// Set the WordPress template to use.
			update_post_meta( $page_id, '_wp_page_template', 'elementor_canvas' );

			// Make sure you don’t have to click on “Edit With Elementor”
			// the first time you access the page.
			update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );

			// There are a few other parameters needed to make the page work.
			$elementor_version     = ( defined( 'ELEMENTOR_VERSION' ) ) ? ELEMENTOR_VERSION : '';
			$elementor_pro_version = ( defined( 'ELEMENTOR_PRO_VERSION' ) ) ? ELEMENTOR_PRO_VERSION : '';
			update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
			update_post_meta( $page_id, '_elementor_version', $elementor_version );
			update_post_meta( $page_id, '_elementor_pro_version', $elementor_pro_version );
			update_post_meta( $page_id, '_elementor_css', '' );

			// Fetch the Elementor settings, data, assets, and controls from
			// the template, so they can be copied to the new page.
			$settings      = get_post_meta( $id_template, '_elementor_page_settings', true );
			$assets        = get_post_meta( $id_template, '_elementor_page_assets', true );
			$controls      = get_post_meta( $id_template, '_elementor_controls_usage', true );
			$data_template = json_decode( get_post_meta( $id_template, '_elementor_data', true ), true );

			// Adding wpfa shortcode to content of each created page
			// Passing values by reference in a loop: https://www.php.net/manual/en/language.references.pass.php.
			foreach ( $data_template as &$item_template ) {
				if ( ! empty( $item_template['elements'] ) && is_array( $item_template['elements'] ) ) {
					foreach ( $item_template['elements'] as &$child_settings ) {
						if ( ! empty( $child_settings['elements'] ) && is_array( $child_settings['elements'] ) ) {
							foreach ( $child_settings['elements'] as &$widget_settings ) {
								if ( ! empty( $widget_settings['widgetType'] )
									&& $widget_settings['widgetType'] === 'text-editor'
									&& ! empty( $widget_settings['settings'] ) ) {
									$widget_settings['settings']['editor'] = "[vg_display_admin_page page_url='" . esc_url( $link_wp ) . "']";
								} elseif ( ! empty( $widget_settings['elements'] ) && is_array( $widget_settings['elements'] ) ) {
									foreach ( $widget_settings['elements'] as &$widget_editor ) {
										if ( ! empty( $widget_editor['elements'] ) && is_array( $widget_editor['elements'] ) ) {

											foreach ( $widget_editor['elements'] as &$widget_text_editor ) {
												if ( ! empty( $widget_text_editor['widgetType'] )
													&& $widget_text_editor['widgetType'] === 'text-editor'
													&& ! empty( $widget_text_editor['settings'] ) ) {
													$widget_text_editor['settings']['editor'] = "[vg_display_admin_page page_url='" . esc_url( $link_wp ) . "']";
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}

			// Copy the Elementor setting, data, assets, and controls into
			// the new page.
			update_post_meta( $page_id, '_elementor_page_settings', $settings );
			update_post_meta( $page_id, '_elementor_data', wp_json_encode( $data_template ) );
			update_post_meta( $page_id, '_elementor_page_assets', $assets );
			update_post_meta( $page_id, '_elementor_controls_usage', $controls );

		}

		public function get_data_to_send_in_js() {
			$this->url_base_plugin        = plugin_dir_url( __FILE__ );
			$this->nonce_for_ajax_actions = wp_create_nonce( 'nonce_ajax_actions_wpfa_template' );
			$this->url_admin              = ( $this->plugin_activated_from_network ) ? get_admin_url() : network_admin_url();

			$plugins_required = $this->_get_required_plugins_from_api();

			if ( ! empty( $plugins_required ) ) {
				$this->list_plugins_required = $plugins_required;
				$plugins_required            = array_column( $plugins_required, 'slug' );
			}

			$list_plugins_in_site = get_plugins();
			$is_multisite         = is_multisite();

			foreach ( $list_plugins_in_site as $key_plugin => $plugin ) {
				$slug_plugin_required = explode( '/', $key_plugin )[0];

				if ( in_array( $slug_plugin_required, $plugins_required, true ) ) {
					$is_active_plugin = false;

					if ( $is_multisite ) {
						$is_active_plugin = is_plugin_active_for_network( $key_plugin );
					} else {
						$is_active_plugin = is_plugin_active( $key_plugin );
					}

					$this->list_of_plugins_on_site[] = array(
						'name'     => $plugin['Title'],
						'slug'     => $key_plugin,
						'isActive' => $is_active_plugin,
					);
				}
			}
		}

		/**
		 * Download, install plugin
		 *
		 * If the plugin directory already exists, this will only attempt to activate the plugin
		 */
		public function install_required_plugin() {

			$administrator_permission = is_multisite() ? 'manage_network' : 'manage_options';

			if ( empty( $_POST['slugPlugin'] ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nonce_ajax_actions_wpfa_template' )
				|| ! current_user_can( 'install_plugins' ) || ! current_user_can( $administrator_permission ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'The plugin is invalid or you do not have permission to perform this action', 'vg_admin_to_frontend' ),
					)
				);
			}

			// check to determine that it is a required plugin slug.
			$slug = sanitize_text_field( wp_unslash( ( $_POST['slugPlugin'] ) ) );

			$list_of_required_plugins = $this->_get_required_plugins_from_api();
			if ( empty( $list_of_required_plugins ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'Error installing the plugin, try again later', 'vg_admin_to_frontend' ),
					)
				);
			}
			$plugins_required_slugs = array_column( $list_of_required_plugins, 'slug' );

			if ( ! in_array( $slug, $plugins_required_slugs, true ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'Error. The plugin you want to install is not required for the dashboard templates.', 'vg_admin_to_frontend' ),
					)
				);
			}

			// Files required for plugin installation.
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array(
						'sections' => false,
					),
				)
			);

			$status = array();
			$error  = array();

			$status['pluginName'] = $api->name;
			$error['pluginName']  = $api->name;

			$plugin_dir = trailingslashit( WP_PLUGIN_DIR ) . $slug;

			// the plugin is installed.
			if ( is_dir( $plugin_dir ) ) {

				$install_status  = install_plugin_install_status( $api );
				$activate_plugin = false;

				if ( current_user_can( 'activate_plugin', $install_status['file'] ) && is_plugin_inactive( $install_status['file'] ) ) {
					$activate_plugin = true;
				}

				if ( is_multisite() && current_user_can( 'manage_network_plugins' ) && is_plugin_inactive( $install_status['file'] ) ) {
					$activate_plugin = true;
				}

				if ( $activate_plugin ) {
					$response = $this->activate_wp_plugin( $slug );

					if ( $response ) {
						$status['activation'] = 'success';
						$status['slug']       = $slug;
						wp_send_json_success( $status );
					} else {
						wp_send_json_error(
							array(
								'errorMessage' => esc_html__( 'Error activating the plugin', 'vg_admin_to_frontend' ),
							)
						);
					}
				} elseif ( ! is_plugin_inactive( $install_status['file'] ) ) {
					$status['activation'] = 'success';
					$status['slug']       = $slug;
					wp_send_json_success( $status );
				} else {
					wp_send_json_error(
						array(
							'errorMessage' => esc_html__( 'Error activating the plugin', 'vg_admin_to_frontend' ),
						)
					);
				}
			}

			if ( is_wp_error( $api ) ) {
				$status['errorMessage'] = $api->get_error_message();
				wp_send_json_error( $status );
			}

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $api->download_link );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$status['debug'] = $skin->get_upgrade_messages();
			}

			if ( is_wp_error( $result ) ) {
				$error['errorCode']    = $result->get_error_code();
				$error['errorMessage'] = $result->get_error_message();
			} elseif ( is_wp_error( $skin->result ) ) {
				$error['errorCode']    = $skin->result->get_error_code();
				$error['errorMessage'] = $skin->result->get_error_message();
			} elseif ( $skin->get_errors()->has_errors() ) {
				$error['errorMessage'] = $skin->get_error_messages();
			} elseif ( is_null( $result ) ) {
				global $wp_filesystem;
				$error['errorCode']    = 'unable_to_connect_to_filesystem';
				$error['errorMessage'] = esc_html__( 'Unable to connect to the filesystem. Please confirm your credentials.', 'vg_admin_to_frontend' );

				// Pass through the error from WP_Filesystem if one was raised.
				if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
					$error['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
				}
			}

			if ( ! empty( $error['errorMessage'] ) ) {
				wp_send_json_error( $error );
			}

			$install_status  = install_plugin_install_status( $api );
			$activate_plugin = false;

			if ( current_user_can( 'activate_plugin', $install_status['file'] ) && is_plugin_inactive( $install_status['file'] ) ) {
				$activate_plugin = true;
			}

			if ( is_multisite() && current_user_can( 'manage_network_plugins' ) && is_plugin_inactive( $install_status['file'] ) ) {
				$activate_plugin = true;
			}

			if ( $activate_plugin ) {
				$response = $this->activate_wp_plugin( $slug );

				if ( $response ) {
					$status['activation'] = 'success';
					$status['slug']       = $slug;
					wp_send_json_success( $status );
				} else {
					wp_send_json_error(
						array(
							'errorMessage' => esc_html__( 'Error activating the plugin', 'vg_admin_to_frontend' ),
						)
					);
				}
			} elseif ( ! is_plugin_inactive( $install_status['file'] ) ) {
				$status['activation'] = 'success';
				$status['slug']       = $slug;
				wp_send_json_success( $status );
			} else {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'Error activating the plugin', 'vg_admin_to_frontend' ),
					)
				);
			}
		}


		public function activate_plugin_from_ajax() {
			$administrator_permission = is_multisite() ? 'manage_network' : 'manage_options';

			if ( empty( $_POST['slugPlugin'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nonce_ajax_actions_wpfa_template' ) || ! current_user_can( 'activate_plugins' ) || ! current_user_can( 'install_plugins' ) || ! current_user_can( $administrator_permission ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'The plugin is invalid or you do not have permission to perform this action', 'vg_admin_to_frontend' ),
					)
				);
			}

			$slug = sanitize_text_field( wp_unslash( ( $_POST['slugPlugin'] ) ) );

			$list_of_required_plugins = $this->_get_required_plugins_from_api();
			if ( empty( $list_of_required_plugins ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'Error installing the plugin, try again later', 'vg_admin_to_frontend' ),
					)
				);
			}
			$plugins_required_slugs = array_column( $list_of_required_plugins, 'slug' );

			if ( ! in_array( $slug, $plugins_required_slugs, true ) ) {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'Error. The plugin you want to activate is not required for the dashboard templates.', 'vg_admin_to_frontend' ),
					)
				);
			}

			$response = $this->activate_wp_plugin( $slug );

			if ( $response ) {
				$response_ajax = array(
					'slug' => $slug,
				);
				wp_send_json_success( $response_ajax );
			} else {
				wp_send_json_error(
					array(
						'errorMessage' => esc_html__( 'Error activating the plugin', 'vg_admin_to_frontend' ),
					)
				);
			}
		}

		/**
		 * Activate plugin
		 *
		 * Verify the plugin directory and find the main file to activate it
		 *
		 * @param string $slug The plugin slug (must be the same as the name of the plugin directory).
		 */
		public function activate_wp_plugin( $slug = null ) {
			$plugin_dir         = trailingslashit( WP_PLUGIN_DIR ) . $slug . '/';
			$activation_success = false;
			$files              = glob( $plugin_dir . '*.php' );
			$is_multisite       = is_multisite();

			foreach ( $files as $full_path ) {

				if ( is_file( $full_path ) ) {

					$activation = activate_plugin( $full_path, '', $is_multisite );

					if ( ! is_wp_error( $activation ) ) {
						$activation_success = true;
						break;
					}
				}
			}

			return $activation_success;
		}

		public function admin_enqueue_app_initial_wizard( $hook ) {

			if ( ! empty( $hook ) && ! strpos( $hook, 'wpfa_wizard_initial' ) ) {
				return;
			}

			// Getting data to send to js file.
			$this->get_data_to_send_in_js();
			$permitted_tags  = array(
				'b'  => array(),
				'br' => array(),
				'a'  => array(
					'href'   => array(
						'https://wordpress.org/plugins/menu-icons/',
						'https://wordpress.org/plugins/wp-menu-icons/',
					),
					'target' => array(
						'_blank',
					),
				),
			);
			$link_quadlayers = 'https://wordpress.org/plugins/wp-menu-icons/';
			$link_themeisle  = 'https://wordpress.org/plugins/menu-icons/';

			$app_data = array(
				'translations' => array(
					'initialSettings'                   => esc_html__( 'Initial settings', 'vg_admin_to_frontend' ),
					'welcome'                           => esc_html__( 'Welcome', 'vg_admin_to_frontend' ),
					'requiredPlugins'                   => esc_html__( 'Required plugins', 'vg_admin_to_frontend' ),
					'dashboardSite'                     => esc_html__( 'Dashboard site', 'vg_admin_to_frontend' ),
					'dashboardDesign'                   => esc_html__( 'Dashboard design', 'vg_admin_to_frontend' ),
					'dashboardMenu'                     => esc_html__( 'Dashboard menu', 'vg_admin_to_frontend' ),
					'dashboardPages'                    => esc_html__( 'Dashboard pages', 'vg_admin_to_frontend' ),
					'inProcess'                         => esc_html__( 'In process', 'vg_admin_to_frontend' ),
					'notCompleted'                      => esc_html__( 'Not completed', 'vg_admin_to_frontend' ),
					'complete'                          => esc_html__( 'Complete', 'vg_admin_to_frontend' ),
					'pleaseSelectTheSiteWhereTheConfigurationsWillBeApplied' => esc_html__( 'Please select the site where the configurations will be applied', 'vg_admin_to_frontend' ),
					'enterTheNameOfTheSiteToSearch'     => esc_html__( 'Enter the name of the site to search', 'vg_admin_to_frontend' ),
					'pleaseSelectATemplateForYourSites' => esc_html__( 'Please select a template for your sites:', 'vg_admin_to_frontend' ),
					'pleaseWaitWhileWeFinishConfiguringEverything' => esc_html__( 'Please wait while we finish configuring everything', 'vg_admin_to_frontend' ),
					'theProcessMayTakeAFewMinutesPleaseDoNotCloseThisPage' => esc_html__( 'The process may take a few minutes, please do not close this page', 'vg_admin_to_frontend' ),
					'processSuccessfullyCompleted'      => esc_html__( 'Process successfully completed', 'vg_admin_to_frontend' ),
					'theProcessHasBeenSuccessfullyCompletedYouCanSeeTheChangesInYourSite' => esc_html__( 'The process has been successfully completed, you can see the changes in your site', 'vg_admin_to_frontend' ),
					'goToTheSite'                       => esc_html__( 'Go to the site', 'vg_admin_to_frontend' ),
					'deactivated'                       => esc_html__( 'Deactivated', 'vg_admin_to_frontend' ),
					'installedAndActivated'             => esc_html__( 'Installed and activated', 'vg_admin_to_frontend' ),
					'notInstall'                        => esc_html__( 'Not install', 'vg_admin_to_frontend' ),
					'seeMore'                           => esc_html__( 'See more', 'vg_admin_to_frontend' ),
					'template'                          => esc_html__( 'Template %s', 'vg_admin_to_frontend' ),
					'selectTemplate'                    => esc_html__( 'Select template', 'vg_admin_to_frontend' ),
					'next'                              => esc_html__( 'Next', 'vg_admin_to_frontend' ),
					'back'                              => esc_html__( 'Back', 'vg_admin_to_frontend' ),
					'pleaseSelectThePagesYouWishToAddInOrderToContinue' => esc_html__( 'Please select the pages you wish to add in order to continue.', 'vg_admin_to_frontend' ),
					'theElementorWebsiteBuilderHasItAllDragAndDropPageBuilderPixelPerfectDesign' => esc_html__( 'The Elementor Website Builder has it all: drag and drop page builder, pixel perfect design...', 'vg_admin_to_frontend' ),
					'createHeaderFooterAndBlocksForYourWordpressWebsiteUsingElementorPageBuilderForFree' => esc_html__( 'Create Header, Footer and Blocks for your WordPress website using Elementor Page Builder for free.', 'vg_admin_to_frontend' ),
					'jetstickyIsThePluginWhichAllowsToMakeTheSectionsAndColumnsBuiltWithElementor' => esc_html__( 'JetSticky is the plugin which allows to make the sections and columns built with Elementor...', 'vg_admin_to_frontend' ),
					'wpMenuIconsAllowsYouToAddIconsToYourItemsWordpressMenu' => esc_html__( 'WP Menu Icons allows you to add icons to your items WordPress menu.', 'vg_admin_to_frontend' ),
					'spiceUpYourNavigationMenusWithPrettyIconsEasily' => esc_html__( 'Spice up your navigation menus with pretty icons, easily.', 'vg_admin_to_frontend' ),
					'allowsYouToAddShortcodesInWordpressNavigationMenus' => esc_html__( 'Allows you to add shortcodes in WordPress Navigation Menus.', 'vg_admin_to_frontend' ),
					'noOptionThatMatchesTheQuery'       => esc_html__( 'No option that matches the query', 'vg_admin_to_frontend' ),
					'noOptionAvailableWithThisName'     => esc_html__( 'No option available with this name', 'vg_admin_to_frontend' ),
					'installAndActivate'                => esc_html__( 'Install and activate', 'vg_admin_to_frontend' ),
					'activate'                          => esc_html__( 'Activate', 'vg_admin_to_frontend' ),
					'pleaseInstallAndActivateTheRequiredPluginsToContinue' => esc_html__( 'Please install and activate the required plugins to continue', 'vg_admin_to_frontend' ),
					'close'                             => esc_html__( 'Close', 'vg_admin_to_frontend' ),
					'error'                             => esc_html__( 'Error', 'vg_admin_to_frontend' ),
					'goToDashboard'                     => esc_html__( 'Go to dashboard', 'vg_admin_to_frontend' ),
					'goToPages'                         => esc_html__( 'Go to pages', 'vg_admin_to_frontend' ),
					'installThisPluginOrThePluginMenuIcons' => esc_html__( 'Install this plugin or the plugin Menu Icons', 'vg_admin_to_frontend' ),
					'installThisPluginOrThePluginWpMenuIcons' => esc_html__( 'Install this plugin or the plugin WP Menu Icons', 'vg_admin_to_frontend' ),
					'welcomeToWpfaTemplateImporterWeWillGuideYouStepByStepToCreateAFrontendDashboardForWordpressUsingElementorTemplates' => wp_kses( __( 'We\'ll guide you step by step to create a frontend dashboard using Elementor templates.<br><br>This wizard will allow you to automate most of the steps required to create your dashboard, including installing the necessary free plugins, importing the dashboard template, creating every dashboard page, and creating a dashboard menu with icons.<br><br>You can run this wizard multiple times in order to add more pages to your dashboard, just try to avoid asking the wizard to create existing pages because you might end up with duplicate dashboard pages (which you can manually delete afterwards).<br><br>The wizard is just a tool to save you time, but everything done by the wizard can be done manually too, in case you need more flexibility or more advanced use cases.', 'vg_admin_to_frontend' ), $permitted_tags ),
					'noteForTheTemplatesImportedWithThisPluginToWorkProperlyYouMustHaveTheWpFrontendAdminPluginInstalledAndActivatedAlongWithTheOtherPluginsWeWillShowYouInTheNextStep' => wp_kses( __( '<b>Notes:</b> For the templates imported with this plugin to work properly, you must have the WP Frontend Admin plugin installed and activated. If you\'re using a multi-site network, the license must be activated network wide.<br>Also, this wizard makes requests to the external WP Frontend Admin API to use the up-to-date templates.', 'vg_admin_to_frontend' ), $permitted_tags ),
					'theFollowingPluginsAreRequiredSoPleaseInstallAndOrActivateThemBelow' => esc_html__( 'The following plugins are required, so please install and/or activate them below.', 'vg_admin_to_frontend' ),
					'noteToAddMenuIconsToTheDashboardYouCanInstallWhetherWpMenuIconsByQuadlayersOrMenuIconsByThemeisleToAvoidAnyConflictsWeRecommendYouToUseOneOptionOnly' => wp_kses( sprintf( __( '<b>Note:</b> To add menu icons to the dashboard, you can install whether <b>"WP Menu Icons"</b> by <a href="%1$s" target="_blank">Quadlayers</a> or <b>"Menu Icons"</b> by <a href="%2$s" target="_blank">ThemeIsle</a>. To avoid any conflicts, we recommend you to use one option only.', 'vg_admin_to_frontend' ), $link_quadlayers, $link_themeisle ), $permitted_tags ),
					'selectTheTemplateYouWantToUseToBuildYourFrontendDashboard' => esc_html__( 'Select the template you want to use to build your frontend dashboard.', 'vg_admin_to_frontend' ),
					'belowYouCanSeeAllTheWpAdminPagesFromYourDashboardSelectThePagesYouWantToAddToTheFrontendDashboardYouCanUseTheDropdownToSelectUnselectSpecificPages' => esc_html__( 'Below, you can see all the wp-admin pages from your dashboard. Select the pages you want to add to the frontend dashboard. You can use the dropdown to select/unselect specific pages.', 'vg_admin_to_frontend' ),
					'waitWhileWeCompleteTheFollowingProcesses' => esc_html__( 'Please wait a few minutes while we complete the following tasks:', 'vg_admin_to_frontend' ),
					'createANavigationMenuForYourFrontendDashboard' => esc_html__( 'Create a navigation menu for your frontend dashboard.', 'vg_admin_to_frontend' ),
					'buildTheFrontendDashboardPagesYouSelected' => esc_html__( 'Build the frontend dashboard pages you selected.', 'vg_admin_to_frontend' ),
					'automaticallyAddMenuIconsToYourFrontendDashboardMenu' => esc_html__( 'Automatically add menu icons to your frontend dashboard menu.', 'vg_admin_to_frontend' ),
					'selectAll'                         => esc_html__( 'Select all', 'vg_admin_to_frontend' ),
					'unselectAll'                       => esc_html__( 'Unselect all', 'vg_admin_to_frontend' ),
					'import'                            => esc_html__( 'Import', 'vg_admin_to_frontend' ),
					'mySite'                            => esc_html__( 'My site', 'vg_admin_to_frontend' ),
					'logOut'                            => esc_html__( 'Log out', 'vg_admin_to_frontend' ),
					'wpfaTemplates'                     => esc_html__( 'Template wizard', 'vg_admin_to_frontend' ),
					'installAllPlugins'                 => esc_html__( 'Install all plugins', 'vg_admin_to_frontend' ),
				),
				'config'       => array(
					'isMultisite'         => is_multisite(),
					'isNetworkAdmin'      => is_network_admin(),
					'currentViewPlugin'   => $this->current_view_plugin,
					'listPlugins'         => $this->list_of_plugins_on_site,
					'listPluginsRequired' => $this->list_plugins_required,
					'urlBasePlugin'       => esc_url( $this->url_base_plugin ),
					'nonceAjaxWPFA'       => $this->nonce_for_ajax_actions,
					'urlAdmin'            => $this->url_admin,
					'language'            => get_locale(),
				),
			);

			// Loading vuejs and libraries.
			wp_enqueue_script( 'wpfa-vuejs-library', plugins_url( '/assets/js/vendor/vue@2.js', __FILE__ ), array(), filemtime( __DIR__ . '/assets/js/vendor/vue@2.js' ), false );
			// Loading plugin: vee-multiselect.
			wp_enqueue_script( 'wpfa-initial-wizard-plugin-vee-multiselect', plugins_url( '/assets/js/vendor/vue-multiselect.min.js', __FILE__ ), array( 'wpfa-vuejs-library' ), filemtime( __DIR__ . '/assets/js/vendor/vue-multiselect.min.js' ), false );

			// Loading vuejs app components.
			wp_enqueue_script( 'wpfa-app-component-welcome', plugins_url( '/assets/js/app/components/wpfa-component-welcome.js', __FILE__ ), array( 'wpfa-vuejs-library' ), filemtime( __DIR__ . '/assets/js/app/components/wpfa-component-welcome.js' ), true );
			wp_enqueue_script( 'wpfa-app-component-required-plugins', plugins_url( '/assets/js/app/components/wpfa-component-required-plugins.js', __FILE__ ), array( 'wpfa-vuejs-library' ), filemtime( __DIR__ . '/assets/js/app/components/wpfa-component-required-plugins.js' ), true );
			wp_enqueue_script( 'wpfa-app-component-dashboard-site', plugins_url( '/assets/js/app/components/wpfa-component-dashboard-site.js', __FILE__ ), array( 'wpfa-vuejs-library' ), filemtime( __DIR__ . '/assets/js/app/components/wpfa-component-dashboard-site.js' ), true );
			wp_enqueue_script( 'wpfa-app-component-dashboard-design', plugins_url( '/assets/js/app/components/wpfa-component-dashboard-design.js', __FILE__ ), array( 'wpfa-vuejs-library' ), filemtime( __DIR__ . '/assets/js/app/components/wpfa-component-dashboard-design.js' ), true );
			wp_enqueue_script( 'wpfa-app-component-dashboard-menu', plugins_url( '/assets/js/app/components/wpfa-component-dashboard-menu.js', __FILE__ ), array( 'wpfa-vuejs-library' ), filemtime( __DIR__ . '/assets/js/app/components/wpfa-component-dashboard-menu.js' ), true );
			wp_enqueue_script( 'wpfa-app-component-done', plugins_url( '/assets/js/app/components/wpfa-component-done.js', __FILE__ ), array( 'wpfa-vuejs-library' ), filemtime( __DIR__ . '/assets/js/app/components/wpfa-component-done.js' ), true );
			wp_enqueue_script( 'wpfa-app-sweetalert2', plugins_url( '/assets/js/vendor/sweetalert2@11.js', __FILE__ ), array( 'wpfa-vuejs-library' ), filemtime( __DIR__ . '/assets/js/vendor/sweetalert2@11.js' ), true );

			// Loading vuejs app.
			wp_enqueue_script(
				'wpfa-initial-wizard-app-main',
				plugins_url( '/assets/js/app.js', __FILE__ ),
				array(
					'wpfa-vuejs-library',
					'wpfa-initial-wizard-plugin-vee-multiselect',
					'wpfa-app-component-welcome',
					'wpfa-app-component-required-plugins',
					'wpfa-app-component-dashboard-site',
					'wpfa-app-component-dashboard-design',
					'wpfa-app-component-dashboard-menu',
					'wpfa-app-component-done',
					'wpfa-app-sweetalert2',
					'jquery',
				),
				filemtime( __DIR__ . '/assets/js/app.js' ),
				true
			);
			wp_localize_script( 'wpfa-initial-wizard-app-main', 'wpfaData', $app_data );

			// Loading app styles.
			wp_enqueue_style( 'wpfa-vue-multiselect', plugins_url( '/assets/css/vendor/vue-multiselect.min.css', __FILE__ ), array(), filemtime( __DIR__ . '/assets/css/vendor/vue-multiselect.min.css' ) );
			wp_enqueue_style( 'wpfa-animate', plugins_url( '/assets/css/vendor/animate.min.css', __FILE__ ), array(), filemtime( __DIR__ . '/assets/css/vendor/animate.min.css' ) );
			wp_enqueue_style( 'wpfa-initial-wizard-style', plugins_url( '/assets/css/style.css', __FILE__ ), array(), filemtime( __DIR__ . '/assets/css/style.css' ) );
		}

		/**
		 * Function to create new item in administrative panel menu
		 */
		public function add_menu_for_wp_fronted_admin() {
			add_submenu_page(
				'wpatof_welcome_page',
				esc_html__( 'Template wizard', 'vg_admin_to_frontend' ),
				esc_html__( 'Template wizard', 'vg_admin_to_frontend' ),
				'manage_options',
				'wpfa_wizard_initial',
				array( $this, 'render_app_vue_wizard' )
			);
		}

		/**
		 * Function to be able to render initial settings page
		 */
		public function render_app_vue_wizard() {
			require_once __DIR__ . '/admin/wpfa-view-initial-wizard.php';
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new WPFA_Initial_Setup_Wizard();
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

WPFA_Initial_Setup_Wizard::get_instance();
