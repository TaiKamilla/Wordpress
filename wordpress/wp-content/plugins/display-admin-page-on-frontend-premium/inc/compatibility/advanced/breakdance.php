<?php

if ( ! class_exists( 'WPFA_Breakdance' ) ) {

	class WPFA_Breakdance {

		private static $instance = false;

		private function __construct() {
		}

		function init() {
			if ( ! defined( '__BREAKDANCE_PLUGIN_FILE__' ) ) {
				return;
			}
			if ( dapof_fs()->is_plan( 'platform', true ) ) {
				add_filter( 'vg_frontend_admin/compatible_default_editors', array( $this, 'add_compatible_default_editor' ) );
				add_action( 'get_edit_post_link', array( $this, 'modify_edit_link' ), 100, 2 );
			}
		}
		function modify_edit_link( $link, $post_id ) {
			$post                 = get_post( $post_id );
			$default_editor       = VG_Admin_To_Frontend_Obj()->get_default_editor_for_post_type( $post->post_type );
			$supported_post_types = \Breakdance\Data\get_global_option('breakdance_settings_enabled_post_types');

			if ( ! is_array( $supported_post_types ) || ! in_array( $post->post_type, $supported_post_types, true ) || $default_editor !== 'breakdance' ) {
				return $link;
			}

			if ( isset( $_GET['fl_builder'] ) && ! empty( $_GET['vgfa_referrer'] ) ) {
				$referrer = preg_replace( '/\#.+$/', '', esc_url( base64_decode( $_GET['vgfa_referrer'] ) ) );
				$link     = $referrer . '#wpfa:' . base64_encode( 'post.php?action=edit&post=' . $post_id );
			} elseif ( ! isset( $_GET['fl_builder'] ) ) {
				$link = esc_url(
					add_query_arg(
						array(
							'breakdance' => 'builder',
							'id'         => $post_id,
						),
						home_url()
					)
				);
			}
			return $link;
		}

		function add_compatible_default_editor( $editors ) {
			$editors['breakdance'] = 'Breakdance';
			return $editors;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if ( null == WPFA_Breakdance::$instance ) {
				WPFA_Breakdance::$instance = new WPFA_Breakdance();
				WPFA_Breakdance::$instance->init();
			}
			return WPFA_Breakdance::$instance;
		}

		function __set( $name, $value ) {
			$this->$name = $value;
		}

		function __get( $name ) {
			return $this->$name;
		}
	}

}

if ( ! function_exists( 'WPFA_Breakdance_Obj' ) ) {

	function WPFA_Breakdance_Obj() {
		return WPFA_Breakdance::get_instance();
	}

}

add_action( 'plugins_loaded', 'WPFA_Breakdance_Obj' );
