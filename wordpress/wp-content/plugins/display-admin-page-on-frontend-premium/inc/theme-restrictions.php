<?php
if (!class_exists('WPFA_Theme_Restrictions')) {

	class WPFA_Theme_Restrictions {

		static private $instance = false;
		public $filtered_menus = array();

		private function __construct() {
			
		}

		function init() {
			if (dapof_fs()->is_plan('platform', true)) {
				if (is_admin()) {
					add_action('wp_frontend_admin/quick_settings/after_save', array($this, 'save_quick_settings'));
				} else {
					add_filter('the_content', array($this, 'notify_super_admins_why_page_wont_load'));
					add_action('template_redirect', array($this, 'redirect_unavailable_page'));
					add_filter('wp_get_nav_menu_items', array($this, 'remove_pages_from_menu'), 10, 3);
					add_action('wp_frontend_admin/quick_settings/after_fields', array($this, 'render_quick_settings_field'));
				}
				add_filter('vg_plugin_sdk/settings/' . VG_Admin_To_Frontend::$textname . '/options', array($this, 'add_global_option'));
			}
		}

		function notify_super_admins_why_page_wont_load($content) {
			$post_id = get_the_ID();
			if (VG_Admin_To_Frontend_Obj()->is_master_user() && !$this->page_id_can_be_seen($post_id)) {
				$all_themes = wp_get_themes();
				$required_theme = get_post_meta($post_id, 'wpfa_required_theme', true);
				$theme_name = isset($all_themes[$required_theme]) ? $all_themes[$required_theme]['Name'] : $required_theme;
				ob_start();
				include VG_Admin_To_Frontend::$dir . '/views/frontend/required-theme-missing.php';
				$message = ob_get_clean();
				$content = $message . $content;
			}
			return $content;
		}

		function _get_site_active_theme() {
			if (is_multisite()) {
				$active_theme = get_blog_option(WPFA_Global_Dashboard_Obj()->get_site_id_for_admin_content(), 'template');
			} else {
				$active_theme = get_option('template');
			}
			return apply_filters( 'wp_frontend_admin/site_active_theme', $active_theme );
		}

		function page_id_can_be_seen($page_id, $active_theme = null) {
			if (!VG_Admin_To_Frontend_Obj()->get_settings('enable_required_themes_check')) {
				return true;
			}
			$current_value = get_post_meta($page_id, 'wpfa_required_theme', true);
			if (empty($current_value)) {
				return true;
			}
			$active_theme = $active_theme ? $active_theme : $this->_get_site_active_theme();
			return $current_value === $active_theme;
		}

		function redirect_unavailable_page() {
			if (is_singular() && !VG_Admin_To_Frontend_Obj()->is_master_user() && !$this->page_id_can_be_seen(get_queried_object_id())) {
				$redirect_to = VG_Admin_To_Frontend_Obj()->get_settings('redirect_to_frontend', home_url('/'));
				$current_url = VG_Admin_To_Frontend_Obj()->get_current_url();
				if ($current_url !== $redirect_to) {
					wp_redirect(esc_url(add_query_arg('wpfa_pr_unavailable_page', 2, $redirect_to)));
					exit();
				}
			}
		}

		function remove_pages_from_menu($items, $menu, $args) {
			if (isset($this->filtered_menus[$menu->term_id])) {
				return $this->filtered_menus[$menu->term_id];
			}

			$available_menu_items = $items;
			if (!VG_Admin_To_Frontend_Obj()->is_master_user()) {

				$active_theme = $this->_get_site_active_theme();

				$available_menu_items = array();
				foreach ($items as $item) {
					if ($item->type === 'post_type' && !$this->page_id_can_be_seen((int) $item->object_id, $active_theme)) {
						continue;
					}

					$available_menu_items[] = $item;
				}
			}
			$this->filtered_menus[$menu->term_id] = $available_menu_items;

			return $available_menu_items;
		}

		function save_quick_settings($post_id) {
			if (!VG_Admin_To_Frontend_Obj()->is_master_user()) {
				return;
			}
			if (!VG_Admin_To_Frontend_Obj()->get_settings('enable_required_themes_check')) {
				return;
			}
			if (empty($_REQUEST['wpfa_required_theme'])) {
				$_REQUEST['wpfa_required_theme'] = '';
			}

			update_post_meta($post_id, 'wpfa_required_theme', sanitize_text_field($_REQUEST['wpfa_required_theme']));
		}

		function render_quick_settings_field($post) {
			if (!VG_Admin_To_Frontend_Obj()->is_master_user()) {
				return;
			}
			if (!VG_Admin_To_Frontend_Obj()->get_settings('enable_required_themes_check')) {
				return;
			}


			// Show all themes, even deactivated, because it's helpful to restrict based on themes that customer sites might use
			$all_themes = wp_get_themes();
			$current_value = get_post_meta($post->ID, 'wpfa_required_theme', true);
			if (empty($current_value)) {
				$current_value = '';
			}
			?>
			<div class="field themes-manager">
				<label><?php _e('Required theme', VG_Admin_To_Frontend::$textname); ?> <a href="#" data-tooltip="down" aria-label="<?php esc_attr_e('We will remove this page from the menus when the selected theme is not activated.', VG_Admin_To_Frontend::$textname); ?>">(?)</a>
				</label>
				<select name="wpfa_required_theme">
					<option value="">--</option>
					<?php
					foreach ($all_themes as $theme_id => $theme) {
						$theme_name = $theme['Name'];
						?>
						<option <?php selected($theme_id === $current_value); ?> value="<?php echo esc_attr($theme_id); ?>"><?php echo esc_html($theme_name); ?></option>
						<?php
					}
					?>
				</select>
			</div>
			<hr>
			<?php
		}

		function add_global_option($sections) {
			$sections['access-restrictions']['fields'][] = array(
				'id' => 'enable_required_themes_check',
				'type' => 'switch',
				'title' => __('Hide pages when a required theme is deactivated?', VG_Admin_To_Frontend::$textname),
				'desc' => __('If you enable this option, we will allow you to select the required theme on every page that you create. So we can remove the dashboard pages from the menus when the required theme is not activated in the site being managed. This is good if you want to allow users to activate and deactivate themes and automatically adjust the frontend dashboard menus.', VG_Admin_To_Frontend::$textname),
				'default' => false,
			);
			return $sections;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Theme_Restrictions::$instance) {
				WPFA_Theme_Restrictions::$instance = new WPFA_Theme_Restrictions();
				WPFA_Theme_Restrictions::$instance->init();
			}
			return WPFA_Theme_Restrictions::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Theme_Restrictions_Obj')) {

	function WPFA_Theme_Restrictions_Obj() {
		return WPFA_Theme_Restrictions::get_instance();
	}

}
WPFA_Theme_Restrictions_Obj();
