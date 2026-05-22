<?php
if ( ! class_exists( 'WPFA_Show_Own_Posts' ) ) {

	class WPFA_Show_Own_Posts {

		private static $instance    = false;
		var $disable_column_removal = false;

		private function __construct() {
		}

		function init() {
			$is_wpadmin                     = is_admin() && ! is_network_admin();
			$is_eventin_events_rest_request = strpos( $_SERVER['REQUEST_URI'], '/eventin/v2/' ) !== false;
			if ( $is_wpadmin ) {
				add_action( 'wp_frontend_admin/quick_settings/after_save', array( $this, 'save_meta_box' ), 10, 2 );
			}
			if ( $is_wpadmin || $is_eventin_events_rest_request ) {
				add_action( 'pre_get_posts', array( $this, 'filter_posts_query' ) );
				add_filter( 'ajax_query_attachments_args', array( $this, 'filter_media_query' ) );
			} else {
				add_action( 'wp_frontend_admin/quick_settings/after_fields', array( $this, 'render_meta_box' ), 15 );
			}
			if ( is_admin() ) {
				$this->migrate_old_setting();
			}
		}

		function migrate_old_setting() {
			global $wpdb;
			if ( (int) get_site_option( 'wpfa_show_own_posts_migrated' ) ) {
				return;
			}
			update_site_option( 'wpfa_show_own_posts_migrated', 1 );
			$original_blog_id = WPFA_Global_Dashboard_Obj()->switch_to_dashboard_site();
			$meta_values      = $wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id WHERE meta_key = 'vgfa_show_own_posts' AND p.post_status = 'publish' " );
			$post_types       = array();
			foreach ( $meta_values as $meta_value ) {
				$meta_value = maybe_unserialize( $meta_value );
				foreach ( $meta_value as $post_type_key => $enabled ) {
					if ( $enabled === 'on' ) {
						$post_types[] = $post_type_key;
					}
				}
			}
			if ( ! empty( $post_types ) ) {
				$global_value = array_map( 'trim', explode( ',', VG_Admin_To_Frontend_Obj()->get_settings( 'restrict_post_types_by_author', '' ) ) );
				$global_value = array_merge( $global_value, $post_types );
				$global_value = array_unique( array_filter( $global_value ) );
				VG_Admin_To_Frontend_Obj()->update_option( 'restrict_post_types_by_author', implode( ',', $global_value ) );
			}
			WPFA_Global_Dashboard_Obj()->restore_site( $original_blog_id );
		}

		function filter_media_query( $query ) {
			$vgfa = VG_Admin_To_Frontend_Obj();
			if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() || ! current_user_can( 'upload_files' ) || wp_doing_cron() || $vgfa->is_master_user() ) {
				return $query;
			}
			$whitelisted_capability = VG_Admin_To_Frontend_Obj()->get_settings( 'whitelisted_user_capability' );
			if ( $whitelisted_capability && current_user_can( $whitelisted_capability ) ) {
				return $query;
			}
			$this->migrate_old_setting();
			$raw_restricted_post_types = trim( $vgfa->get_settings( 'restrict_post_types_by_author', '' ) );
			if ( ! $raw_restricted_post_types ) {
				$raw_restricted_post_types = '';
			}
			$post_type             = 'attachment';
			$restricted_post_types = array_map( 'trim', explode( ',', $raw_restricted_post_types ) );
			if ( in_array( $post_type, $restricted_post_types, true ) ) {
				$query['author'] = get_current_user_id();
			}
			return $query;
		}

		function filter_posts_query( $wp_query ) {

			$vgfa = VG_Admin_To_Frontend_Obj();
			if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() || wp_doing_ajax() || wp_doing_cron() || $vgfa->is_master_user() ) {
				return;
			}
			$edit_posts_capability = 'edit_posts';
			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();

				// Check if we are on a post listing page
				if ( isset( $screen->post_type ) && 'edit' === $screen->base ) {
					$post_type_object = get_post_type_object( $screen->post_type );
					if ( $post_type_object ) {
						$edit_posts_capability = $post_type_object->cap->edit_posts;
					}
				}
			}
			if ( ! current_user_can( $edit_posts_capability ) ) {
				return;
			}

			$whitelisted_capability = VG_Admin_To_Frontend_Obj()->get_settings( 'whitelisted_user_capability' );
			if ( $whitelisted_capability && current_user_can( $whitelisted_capability ) ) {
				return;
			}
			$post_type = $wp_query->get( 'post_type' );
			if ( ! is_string( $post_type ) ) {
				return;
			}
			$this->migrate_old_setting();
			$raw_restricted_post_types = trim( $vgfa->get_settings( 'restrict_post_types_by_author', '' ) );
			if ( ! $raw_restricted_post_types ) {
				$raw_restricted_post_types = '';
			}
			$restricted_post_types = array_map( 'trim', explode( ',', $raw_restricted_post_types ) );
			if ( in_array( $post_type, $restricted_post_types, true ) ) {
				$wp_query->set( 'author', get_current_user_id() );
				add_filter( 'views_edit-' . $post_type, array( $this, 'fix_post_counts' ) );
			}
		}

		function fix_post_counts( $views ) {

			global $current_user, $wp_query;

			unset( $views['mine'] );

			$post_type = $wp_query->get( 'post_type' );
			$types     = array(
				array( 'status' => null ),
				array( 'status' => 'publish' ),
				array( 'status' => 'draft' ),
				array( 'status' => 'pending' ),
				array( 'status' => 'trash' ),
			);

			foreach ( $types as $type ) {

				$query = array(
					'author'      => $current_user->ID,
					'post_type'   => $post_type,
					'post_status' => $type['status'],
				);

				$result = new WP_Query( $query );

				if ( $type['status'] == null ) :

					$class = ( $wp_query->query_vars['post_status'] == null ) ? ' class="current"' : '';

					$views['all'] = sprintf( __( '<a href="%s"' . $class . '>All <span class="count">(%d)</span></a>', 'all' ), admin_url( 'edit.php?post_type=' . $post_type ), $result->found_posts );

				elseif ( $type['status'] == 'publish' ) :

					$class = ( $wp_query->query_vars['post_status'] == 'publish' ) ? ' class="current"' : '';

					$views['publish'] = sprintf( __( '<a href="%s"' . $class . '>Published <span class="count">(%d)</span></a>', 'publish' ), admin_url( 'edit.php?post_status=publish&post_type=' . $post_type ), $result->found_posts );

				elseif ( $type['status'] == 'draft' ) :

					$class = ( $wp_query->query_vars['post_status'] == 'draft' ) ? ' class="current"' : '';

					$views['draft'] = sprintf( __( '<a href="%s"' . $class . '>Draft' . ( ( sizeof( $result->posts ) > 1 ) ? 's' : '' ) . ' <span class="count">(%d)</span></a>', 'draft' ), admin_url( 'edit.php?post_status=draft&post_type=' . $post_type ), $result->found_posts );

				elseif ( $type['status'] == 'pending' ) :

					$class = ( $wp_query->query_vars['post_status'] == 'pending' ) ? ' class="current"' : '';

					$views['pending'] = sprintf( __( '<a href="%s"' . $class . '>Pending <span class="count">(%d)</span></a>', 'pending' ), admin_url( 'edit.php?post_status=pending&post_type=' . $post_type ), $result->found_posts );

				elseif ( $type['status'] == 'trash' ) :

					$class = ( $wp_query->query_vars['post_status'] == 'trash' ) ? ' class="current"' : '';

					$views['trash'] = sprintf( __( '<a href="%s"' . $class . '>Trash <span class="count">(%d)</span></a>', 'trash' ), admin_url( 'edit.php?post_status=trash&post_type=' . $post_type ), $result->found_posts );

				endif;
			}

			return $views;
		}

		/**
		 * Meta box display callback.
		 *
		 * @param WP_Post $post Current post object.
		 */
		function render_meta_box( $post ) {
			$show_own_posts = get_post_meta( $post->ID, 'vgfa_show_own_posts', true );
			?>
			<div class="field show-own-posts">
				<label>
					<input type="hidden" name="vgfa_show_own_posts[{post_type}]" value="">
					<input type="checkbox" name="vgfa_show_own_posts[{post_type}]" <?php checked( ! empty( $show_own_posts ) ); ?>> <?php _e( 'The users should see the posts created by them only', VG_Admin_To_Frontend::$textname ); ?> <a href="#" data-tooltip="down" aria-label="<?php esc_attr_e( 'This does not apply to administrators, please test it as a normal user.', VG_Admin_To_Frontend::$textname ); ?>">(?)</a>
				</label>

				<hr>
			</div>
			<?php
		}

		function save_meta_box( $post_id, $post ) {
			if ( isset( $_REQUEST['vgfa_show_own_posts'] ) && is_array( $_REQUEST['vgfa_show_own_posts'] ) ) {
				if ( isset( $_REQUEST['vgfa_show_own_posts']['{post_type}'] ) ) {
					$_REQUEST['vgfa_show_own_posts'] = array();
				}
				$data              = array_filter( array_map( 'sanitize_text_field', $_REQUEST['vgfa_show_own_posts'] ) );
				$current_meta_data = get_post_meta( $post_id, 'vgfa_show_own_posts', true );
				if ( empty( $current_meta_data ) ) {
					$current_meta_data = array();
				}

				$removed = array_keys( array_diff_assoc( $current_meta_data, $data ) );

				$global_value = array_map( 'trim', explode( ',', VG_Admin_To_Frontend_Obj()->get_settings( 'restrict_post_types_by_author', '' ) ) );
				$global_value = array_merge( $global_value, array_keys( $data ) );
				$global_value = array_unique( array_filter( array_diff( $global_value, $removed ) ) );
				VG_Admin_To_Frontend_Obj()->update_option( 'restrict_post_types_by_author', implode( ',', $global_value ) );

				update_post_meta( $post_id, 'vgfa_show_own_posts', $data );
			}
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new WPFA_Show_Own_Posts();
				self::$instance->init();
			}
			return self::$instance;
		}

		function __set( $name, $value ) {
			$this->$name = $value;
		}

		function __get( $name ) {
			return $this->$name;
		}
	}

}

if ( ! function_exists( 'WPFA_Show_Own_Posts_Obj' ) ) {

	function WPFA_Show_Own_Posts_Obj() {
		return WPFA_Show_Own_Posts::get_instance();
	}
}
WPFA_Show_Own_Posts_Obj();
