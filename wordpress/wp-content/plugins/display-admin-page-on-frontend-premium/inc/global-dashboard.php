<?php

if (!class_exists('WPFA_Global_Dashboard')) {

	class WPFA_Global_Dashboard {

		static private $instance = false;
		var $current_site_id = null;

		private function __construct() {
			
		}

		function init() {

			if (!is_multisite()) {
				return;
			}
			if (dapof_fs()->is_plan__premium_only('platform', true)) {
				add_shortcode('wp_frontend_admin_site_selector', array($this, 'get_site_selector__premium_only'));
				add_shortcode('wp_frontend_admin_client_site_logo', array($this, 'get_client_site_logo__premium_only'));
				add_filter('myblogs_blog_actions', array($this, 'modify_dashboard_url__premium_only'), 10, 2);
				add_action('init', array($this, 'set_site_from_url__premium_only'));
				if (is_admin()) {
					add_action('wp_frontend_admin/quick_settings/after_save', array($this, 'save_meta_box__premium_only'), 10, 2);
				}
			}
		}
		function get_client_site_logo__premium_only($atts = array(), $content = '') {
			extract(shortcode_atts(array(
				'size' => 'thumbnail',
			), $atts));

			if ( is_multisite() ) {
				$admin_content_site_id = WPFA_Global_Dashboard_Obj()->get_site_id_for_admin_content();
				$current_blog_id = get_current_blog_id();
				switch_to_blog($admin_content_site_id);
			}

			$logo_id = get_theme_mod( 'custom_logo' );
			if(!$logo_id){
				return '';
			}
			$out = '<div class="client-site-logo">' . wp_get_attachment_image( $logo_id, $size ) . '</div>';
			if( is_multisite()){
				switch_to_blog($current_blog_id);
			}
			return $out;
		}

		function get_network_sites_options(){
			$pre_value = apply_filters('wp_frontend_admin/global_dashboard/network_sites_options', null);
			if( !is_null($pre_value)){
				return $pre_value;
			}
			$network_sites = array();
			if ( is_multisite() ) {
				$admin_content_site_id = (int) $this->get_site_id_for_admin_content();
				if (!$admin_content_site_id) {
					$admin_content_site_id = get_current_blog_id();
				}
				$network_sites = VG_Admin_To_Frontend_Obj()->get_network_sites();
				if (!isset($network_sites[$admin_content_site_id]) && get_site($admin_content_site_id)) {
					$site_for_preview = get_site($admin_content_site_id);
					$network_sites[$site_for_preview->blog_id] = get_site_url($admin_content_site_id);
				}
			}
			return $network_sites;
		}

		/**
		 * Get the URL of the site owned by the user (client site)
		 *
		 * @return string
		 */
		function get_current_site_url(){
			$pre_value = apply_filters('wp_frontend_admin/global_dashboard/current_site_url', null);
			if( !is_null($pre_value)){
				return $pre_value;
			}
			$blog_id = (int) $this->get_current_site_id();
			if (!$blog_id) {
				$blog_id = null;
			}
			$url = get_site_url($blog_id, '/');
			return $url;
		}

		function get_frontend_dashboard_page_url_for_backend_url($url_path){
			$pre_value = apply_filters('wp_frontend_admin/global_dashboard/frontend_dashboard_page_url_for_backend_url', null, $url_path);
			if( !is_null($pre_value)){
				return $pre_value;
			}
			$original_blog_id = $this->switch_to_dashboard_site();

			$frontend_page_id = VG_Admin_To_Frontend_Obj()->get_page_id(esc_url(admin_url($url_path)), '');

			if ($frontend_page_id) {
				// FIX. Fatal error that happens when using the electro theme, because we switch the site context to the dashboard and the theme has a hook related to get_permalink that will try to include a PHP file that won't exist in the theme used by the dashboard, but it exists in the theme used by the customer site.
				add_filter('electro_enable_mobile_front_page', '__return_false', 20);
				$redirect_to = get_permalink($frontend_page_id);
			} else {
				$redirect_to = VG_Admin_To_Frontend_Obj()->get_settings('redirect_to_frontend', !empty(VG_Admin_To_Frontend_Obj()->get_settings('enable_wpadmin_access_restrictions')) ? home_url('/') : null);
			}

			$this->restore_site($original_blog_id);
			return $redirect_to;
		}

		function save_meta_box__premium_only($post_id, $post) {
			if (!isset($_REQUEST['site_id_for_preview'])) {
				return;
			}

			$site_id = (int) $_REQUEST['site_id_for_preview'];
			$this->switch_site($site_id);
		}

		function set_site_from_url__premium_only() {
			if (is_multisite() && !empty($_GET['vgfa_site_id']) && !empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'wpfa')) {
				$site_id = (int) $_GET['vgfa_site_id'];
				$this->switch_site($site_id);
				wp_redirect(remove_query_arg(array('vgfa_site_id', '_wpnonce')));
				exit();
			}
		}

		function get_site_id_for_admin_content() {
			$pre_value = apply_filters('wp_frontend_admin/global_dashboard/site_id_for_admin_content', null);
			if( !is_null($pre_value)){
				return $pre_value;
			}
			$blog_id = null;
			$user_belongs_to_blogs = null;
			if (!dapof_fs()->is_plan__premium_only('standard', true)) {
				if (is_multisite() && $this->get_dashboard_site_id()) {
					$blog_id = $this->get_current_site_id();
				}
			}
			return apply_filters('wp_frontend_admin/site_id_for_admin_content', $blog_id, $user_belongs_to_blogs);
		}

		function switch_to_dashboard_site() {
			$out = false;
			if (!dapof_fs()->is_plan__premium_only('standard', true)) {
				$global_dashboard_id = $this->get_dashboard_site_id();
				if ($global_dashboard_id && is_multisite() && get_current_blog_id() !== $global_dashboard_id) {
					$original_blog_id = get_current_blog_id();
					switch_to_blog($global_dashboard_id);
					$out = $original_blog_id;
				}
			}
			return $out;
		}

		function restore_site($site_id) {

			if (!dapof_fs()->is_plan__premium_only('standard', true)) {
				$global_dashboard_id = $this->get_dashboard_site_id();
				if ($global_dashboard_id && $site_id && is_multisite() && get_current_blog_id() !== $site_id) {
					restore_current_blog();
				}
			}
		}

		function is_global_dashboard() {
			$pre_value = apply_filters('wp_frontend_admin/global_dashboard/is_global_dashboard', null);
			if( !is_null($pre_value)){
				return $pre_value;
			}
			$out = false;

			if (!dapof_fs()->is_plan__premium_only('standard', true)) {
				$global_dashboard_id = (int) $this->get_dashboard_site_id();
				if ($global_dashboard_id && is_multisite() && get_current_blog_id() === $global_dashboard_id) {
					$out = true;
				}
			}
			return $out;
		}

		function get_dashboard_site_id() {
			$pre_value = apply_filters('wp_frontend_admin/global_dashboard/dashboard_site_id', null);
			if( !is_null($pre_value)){
				return $pre_value;
			}
			$id = VG_Admin_To_Frontend_Obj()->get_settings('global_dashboard_id');
			if ($id && !get_site($id)) {
				// If we saved a global dashboard id but the site does not exist, unset it on the options page
				VG_Admin_To_Frontend_Obj()->update_option('global_dashboard_id', '');
				$id = null;
			}
			return (int) $id;
		}

		function get_dashboard_url($blog_id, $url = null) {
			$pre_value = apply_filters('wp_frontend_admin/global_dashboard/dashboard_url', null, $blog_id, $url);
			if( !is_null($pre_value)){
				return $pre_value;
			}
			$dashboard_site_id = $this->get_dashboard_site_id();
			if (empty($url)) {
				$url = VG_Admin_To_Frontend_Obj()->get_settings('redirect_to_frontend', get_site_url($dashboard_site_id, '/'));
			}
			switch_to_blog($dashboard_site_id);
			$url = add_query_arg('vgfa_site_id', $blog_id, $url);
			$url = esc_url(wp_nonce_url($url, 'wpfa'));
			restore_current_blog();
			return $url;
		}

		function modify_dashboard_url__premium_only($actions, $user_blog) {
			if (!VG_Admin_To_Frontend_Obj()->is_master_user()) {
				$search = esc_url(admin_url());
				$replace = $this->get_dashboard_url($user_blog->userblog_id);
				$actions = str_replace($search, $replace, $actions);
			}
			return $actions;
		}

		function get_manageable_sites($user_id = null) {
			if (!$user_id) {
				$user_id = get_current_user_id();
			}
			$pre_value = apply_filters('wp_frontend_admin/global_dashboard/manageable_sites', null, $user_id);
			if( !is_null($pre_value)){
				return $pre_value;
			}
			if (!is_multisite()) {
				return array();
			}
			$user_belongs_to_blogs = get_blogs_of_user($user_id);
			if (VG_Admin_To_Frontend_Obj()->is_master_user()) {
				$network_sites = VG_Admin_To_Frontend_Obj()->get_network_sites();
				$allowed_sites = array_keys($network_sites);
			} else {
				$allowed_sites = array();
				foreach ($user_belongs_to_blogs as $user_blog) {
					switch_to_blog($user_blog->userblog_id);
					if (user_can($user_id, 'edit_posts')) {
						$allowed_sites[] = $user_blog->userblog_id;
					}
					restore_current_blog();
				}
			}
			return $allowed_sites;
		}

		function get_current_site_id($user_id = null) {
			if (!$user_id) {
				$user_id = get_current_user_id();
			}
			$pre_value = apply_filters('wp_frontend_admin/global_dashboard/current_site_id', null, $user_id);
			if( !is_null($pre_value)){
				return $pre_value;
			}
			$site_id = (int) get_user_meta($user_id, 'wpfa_current_site_id', true);
			$allowed_site_ids = $this->get_manageable_sites($user_id);
			$blog_id = null;
			if ($allowed_site_ids) {
				$blog_id = ($site_id && in_array($site_id, $allowed_site_ids, true) ) ? $site_id : end($allowed_site_ids);
			}
			return $blog_id;
		}

		function switch_site($site_id) {
			$allowed_site_ids = $this->get_manageable_sites();
			if (!$allowed_site_ids) {
				return;
			}
			if (!in_array($site_id, $allowed_site_ids, true)) {
				return;
			}
			$this->current_site_id = (int) $site_id;
			update_user_meta(get_current_user_id(), 'wpfa_current_site_id', $site_id);
		}

		function get_site_selector__premium_only($atts = array(), $content = '') {
			extract(shortcode_atts(array(
				'format' => 'grid', // grid | links_list
				'exclude_current_site' => false
							), $atts));

			if (!is_user_logged_in()) {
				return;
			}

			$manageable_sites = $this->get_manageable_sites();
			if (empty($manageable_sites)) {
				return;
			}
			$manageable_sites = get_sites(array(
				'site__in' => $manageable_sites
			));
			$current_site_id = $this->get_current_site_id();
			$template_name = $format === 'grid' ? 'site-selector' : 'site-selector-links-list';
			ob_start();
			include VG_Admin_To_Frontend::$dir . '/views/frontend/' . $template_name . '.php';
			return ob_get_clean();
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Global_Dashboard::$instance) {
				WPFA_Global_Dashboard::$instance = new WPFA_Global_Dashboard();
				WPFA_Global_Dashboard::$instance->init();
			}
			return WPFA_Global_Dashboard::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Global_Dashboard_Obj')) {

	function WPFA_Global_Dashboard_Obj() {
		return WPFA_Global_Dashboard::get_instance();
	}

}
WPFA_Global_Dashboard_Obj();
