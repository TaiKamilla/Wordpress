<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Plugin;

if ( !class_exists( 'ContentViews_Elementor_Hooks' ) ) {

	class ContentViews_Elementor_Hooks {

		static function init_hooks() {
			/* ------------ EACH BELOW FUNCTION MUST ONLY EXECUTED FOR ELEMENTOR WIDGET ------------ */
			add_filter( PT_CV_PREFIX_ . 'mapping_value', array( __CLASS__, 'filter_mapping_value' ), 10, 4 );
			add_filter( PT_CV_PREFIX_ . 'block_settings', array( __CLASS__, 'filter_block_settings' ) );

			// Disable loadmore/infinite pagination in Elementor Preview
			add_filter( PT_CV_PREFIX_ . 'pagination_data', array( __CLASS__, 'disable_pagination_in_preview' ), 1000 );

			// Higher priority to run after same block filters
			add_filter( PT_CV_PREFIX_ . 'pagination_data', array( __CLASS__, 'filter_pagination_data' ), 999 );
			add_filter( PT_CV_PREFIX_ . 'set_view_settings', array( __CLASS__, 'set_widget_pagination_settings' ), 999 );

			// Ajax request for Elementor only
			add_action( 'wp_ajax_contentviews_elementor_search_post', [ __CLASS__, 'ajax_search_by_title' ] );
			add_action( 'wp_ajax_contentviews_elementor_get_title', [ __CLASS__, 'ajax_get_title_by_ids' ] );
		}

		static function filter_mapping_value( $value, $info, $data, $settings ) {

			// Only do for elementor
			if ( !ContentViews_Elementor_Init::is_widget( $data ) ) {
				return $value;
			}

			$value = self::get_value_from_widget( $value );

			return $value;
		}

		// Get value for some specific widget control types
		static function get_value_from_widget( $value ) {
			// SLIDER control
			if ( is_array( $value ) && isset( $value[ 'unit' ], $value[ 'size' ] ) ) {
				// get 'size' for not responsive control
				$value = $value[ 'size' ];
			}

			return $value;
		}

		static function filter_block_settings( $attributes ) {

			if ( $attributes[ 'viewType' ] === 'scrollable' && ContentViews_Elementor_Init::is_widget( $attributes ) ) {
				$cols							 = $attributes[ 'columns' ];
				$rows							 = self::get_value_from_widget( $attributes[ 'rowNum' ] );
				$attributes[ 'postsPerPage' ]	 = (int) $cols * (int) $rows * (int) self::get_value_from_widget( $attributes[ 'slideNum' ] );

				if ( !$attributes[ 'scrollAuto' ] ) {
					$attributes[ 'scrollInterval' ] = 0;
				} else {
					$attributes[ 'scrollInterval' ] = (int) self::get_value_from_widget( $attributes[ 'scrollInterval' ] );
				}
			}

			return $attributes;
		}

		// Disable loadmore/infinite pagination in Elementor Preview
		static function disable_pagination_in_preview( $args ) {
			if ( ContentViews_Elementor_Init::is_widget() ) {
				if ( !empty( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] === 'elementor_ajax' ) {
					$args .= ' data-disabled="1" data-elpreview="1" ';
				}
			}

			return $args;
		}

		// Add extra data to pagination
		static function filter_pagination_data( $args ) {
			if ( ContentViews_Elementor_Init::is_widget() ) {
				$widgetid	 = isset( $GLOBALS[ 'cv_elementor_widgetID' ] ) ? $GLOBALS[ 'cv_elementor_widgetID' ] : '';
				$postid		 = isset( $GLOBALS[ 'cv_current_post' ] ) ? $GLOBALS[ 'cv_current_post' ] : '';
				$args		 = sprintf( 'data-iselementor="%s" data-postid="%s"', esc_attr( $widgetid ), esc_attr( $postid ) );
			}

			return $args;
		}

		// Generate view settings from widget
		static function set_widget_pagination_settings( $args ) {
			if ( !empty( $_POST[ 'iselementor' ] ) ) {
				$page_id	 = (int) $_POST[ 'postid' ];
				$widget_id	 = cv_sanitize_vid( $_POST[ 'iselementor' ] );
				$args		 = self::get_widget_settings( $page_id, $widget_id );
			}

			return $args;
		}

		// Get widget settings for Ajax pagination
		static function get_widget_settings( $page_id, $widget_id ) {
			$document	 = Plugin::$instance->documents->get( $page_id );
			$settings	 = [];
			if ( $document ) {
				$elements	 = Plugin::instance()->documents->get( $page_id )->get_elements_data();
				$widget_data = self::find_element_recursive( $elements, $widget_id );
				if ( !empty( $widget_data ) && is_array( $widget_data ) ) {
					$widget = Plugin::instance()->elements_manager->create_element_instance( $widget_data );
				}
				if ( !empty( $widget ) ) {
					$widget_info = $widget->get_settings();

					$all_data	 = ContentViews_Elementor_Render::get_attributes_and_settings( $widget_info );
					$settings	 = $all_data[ 1 ];
				}
			}
			return $settings;
		}

		static function find_element_recursive( $elements, $form_id ) {

			foreach ( $elements as $element ) {
				if ( $form_id === $element[ 'id' ] ) {
					return $element;
				}

				if ( !empty( $element[ 'elements' ] ) ) {
					$element = self::find_element_recursive( $element[ 'elements' ], $form_id );

					if ( $element ) {
						return $element;
					}
				}
			}

			return false;
		}

		// Ajax - search posts when typing title
		static function ajax_search_by_title() {
			$post_type	 = !empty( $_POST[ 'post_type' ] ) ? sanitize_text_field( $_POST[ 'post_type' ] ) : 'post';
			$search		 = !empty( $_POST[ 'term' ] ) ? sanitize_text_field( $_POST[ 'term' ] ) : '';

			$post_list	 = self::query_by_title( $post_type, $search );

			$results = [];
			foreach ( $post_list as $key => $title ) {
				$results[] = [ 'id' => $key, 'text' => $title ];
			}

			wp_send_json( [ 'results' => $results ] );
		}

		// Query by title
		static function query_by_title( $post_type = 'any', $search = '' ) {
			global $wpdb;
			$where	 = '';
			$data	 = [];
			$limit	 = 'LIMIT 10';

			if ( 'any' === $post_type ) {
				$searchable_post_types = get_post_types( [ 'exclude_from_search' => false ] );
				if ( empty( $searchable_post_types ) ) {
					$where .= ' AND 1=0 ';
				} else {
					$where .= " AND {$wpdb->posts}.post_type IN ('" . join( "', '", array_map( 'esc_sql', $searchable_post_types ) ) . "')";
				}
			} elseif ( !empty( $post_type ) ) {
				$post_types	 = explode( ',', $post_type );
				$where		 .= " AND {$wpdb->posts}.post_type IN ('" . join( "', '", array_map( 'esc_sql', $post_types ) ) . "')";
			}

			if ( !empty( $search ) ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
			}

			$query	 = "select post_title,ID  from $wpdb->posts where post_status = 'publish' $where $limit";
			$results = $wpdb->get_results( $query );
			if ( !empty( $results ) ) {
				foreach ( $results as $row ) {
					$data[ $row->ID ] = $row->post_title;
				}
			}
			return $data;
		}

		// Ajax - get post title from IDs
		static function ajax_get_title_by_ids() {
			if ( empty( $_POST[ 'id' ] ) || empty( array_filter( (array) $_POST[ 'id' ] ) ) ) {
				wp_send_json_error( [] );
			}

			$ids		 = array_map( 'intval', (array) $_POST[ 'id' ] );
			$post_types	 = empty( $_POST[ 'post_type' ] ) ? 'post' : array_map( 'sanitize_text_field', explode( ',', $_POST[ 'post_type' ] ) );

			$post_info	 = get_posts( [
				'post_type'	 => $post_types,
				'include'	 => $ids,
			] );
			$response	 = wp_list_pluck( $post_info, 'post_title', 'ID' );

			if ( !empty( $response ) ) {
				wp_send_json_success( [ 'results' => $response ] );
			} else {
				wp_send_json_error( [] );
			}
		}

	}

}