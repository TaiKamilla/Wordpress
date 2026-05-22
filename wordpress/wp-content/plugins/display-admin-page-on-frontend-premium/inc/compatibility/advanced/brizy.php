<?php

if (!class_exists('WPFA_Brizy')) {

	class WPFA_Brizy {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			if (!defined('BRIZY_VERSION')) {
				return;
			}
			if (dapof_fs()->is_plan('platform', true)) {
				add_filter('brizy_editor_config', array($this, 'filter_editor_config'));
				add_filter('brizy_editor_page_context', array($this, 'filter_brizy_editor_page_context'));
				add_filter('vg_frontend_admin/compatible_default_editors', array($this, 'add_compatible_default_editor'));
				add_action('get_edit_post_link', array($this, 'modify_edit_link'), 100, 2);
				add_filter('vg_admin_to_frontend/backend/js_data', array($this, 'add_referer_to_brizy_buttons'));
				add_action('brizy_after_enabled_for_post', array($this, 'after_enabled_for_post_redirect'));
				add_filter('vg_admin_to_frontend/frontend/js_data', array($this, 'add_js_data'));
				add_filter('wp_frontend_admin/allowed_keywords_in_iframe', array($this, 'allow_keyword_in_iframe'));
				add_filter('wp_frontend_admin/pagebuilder_parameters', array($this, 'allow_keyword_in_iframe'));
				add_action('brizy_preview_enqueue_post', array($this, 'enqueue_backend_js_on_live_editor'));
			}
		}

		function enqueue_backend_js_on_live_editor(){
			VG_Admin_To_Frontend_Obj()->cleanup_admin_page_for_frontend();
			wp_print_scripts('vg-frontend-admin-outline');
		}

		function allow_keyword_in_iframe($keywords) {
			$keywords[] = Brizy_Editor::prefix('-edit-iframe');
			return $keywords;
		}

		function add_js_data($data) {
			$data['brizy_edit_parameter'] = Brizy_Editor::prefix('-edit');
			$data['fullscreen_pages_keywords'][] = Brizy_Editor::prefix('-edit-iframe');
			return $data;
		}

		function after_enabled_for_post_redirect($p) {
			if (empty($_GET['vgfa_referrer'])) {
				return;
			}

			$post = null;

			// obtain the post
			try {
				$post = Brizy_Editor_Post::get($p->ID);
			} catch (Exception $exception) {
				return;
			}
			$referrer = preg_replace('/\#.+$/', '', esc_url(base64_decode($_GET['vgfa_referrer'])));
			$new_referrer = base64_encode($referrer . '#wpfa:' . base64_encode('post.php?action=edit&post=' . $p->ID));

			$redirect_to = add_query_arg('vgfa_referrer', $new_referrer, $post->edit_url());
			wp_redirect($redirect_to);
			exit();
		}

		function add_referer_to_brizy_buttons($data) {
			$data['stateful_urls_that_require_referer'] .= ',action=_brizy_admin_editor_enable,action=in-front-editor';
			return $data;
		}

		function modify_edit_link($link, $post_id) {
			$default_editor = VG_Admin_To_Frontend_Obj()->get_default_editor_for_post_type(get_post_type($post_id));
			$supported_post_type = Brizy_Editor::checkIfPostTypeIsSupported($post_id, false);
			if (!empty($_GET[Brizy_Editor::prefix('-edit')]) || !$supported_post_type || $default_editor !== 'brizy') {
				return $link;
			}
			// $link = esc_url(add_query_arg(Brizy_Editor::prefix('-edit'), '1', get_permalink($post_id)));
			$link = esc_url(add_query_arg( array(
				'action' => 'in-front-editor',
				'post' => $post_id
			), admin_url('post.php')));
			return $link;
		}
		function add_compatible_default_editor($editors) {
			$editors['brizy'] = 'Brizy';
			return $editors;
		}

		function filter_editor_config($config) {
			if (!empty($_GET['vgfa_referrer'])) {
				$config['urls']['backToDashboard'] = esc_url(base64_decode($_GET['vgfa_referrer']));
				$option_types = wp_list_pluck( $config['ui']['leftSidebar']['more']['options'], 'icon');
				$dashboard_index = array_search( 'nc-back', $option_types, true);
				if( $dashboard_index !== false){
					$config['ui']['leftSidebar']['more']['options'][ $dashboard_index ]['link'] = esc_url(base64_decode($_GET['vgfa_referrer']));
				}
			}
			return $config;
		}

		function filter_brizy_editor_page_context($config) {
			if (!empty($_GET['vgfa_referrer'])) {
				$config['iframe_url'] = add_query_arg('vgfa_referrer', $_GET['vgfa_referrer'], $config['iframe_url']);
			}
			return $config;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Brizy::$instance) {
				WPFA_Brizy::$instance = new WPFA_Brizy();
				WPFA_Brizy::$instance->init();
			}
			return WPFA_Brizy::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Brizy_Obj')) {

	function WPFA_Brizy_Obj() {
		return WPFA_Brizy::get_instance();
	}

}
WPFA_Brizy_Obj();
