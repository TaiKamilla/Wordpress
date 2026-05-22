<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'ContentViews_Elementor_Render' ) ) {

	class ContentViews_Elementor_Render {
		
		// Render widget
		static function widget_output( $_this ) {

			$output	 = $style	 = '';

			$widget_info = $_this->get_settings();

			$all_data	 = self::get_attributes_and_settings( $widget_info );
			$widget_info = $all_data[ 0 ];

			// disable things relate to classic views
			add_filter( PT_CV_PREFIX_ . 'hide_editview', '__return_true' );

			// output
			ob_start();
			$GLOBALS[ 'cv_current_post' ]	 = apply_filters( PT_CV_PREFIX_ . 'current_postid', get_queried_object_id() );
			$GLOBALS[ 'cv_elementor_widgetID' ]	 = $_this->get_id();

			$settings	 = $all_data[ 1 ];
			$view_id	 = $_this->get_id();

			self::show_widget_id( $view_id );

			echo PT_CV_Functions::view_process_settings( $view_id, $settings );

			// maybe need view_final_output();

			$output .= ob_get_clean();

			
			// extra
			$class = apply_filters( PT_CV_PREFIX_ . 'wrapper_class', PT_CV_PREFIX . 'wrapper' );

			$style		 = $field_css	 = '';

			echo "<div class='$class'> $output </div> $style";
		}

		static function get_attributes_and_settings( $widget_info ) {
			$widget_info = apply_filters( PT_CV_PREFIX_ . 'block_settings', $widget_info );

			$settings = array();
			self::mapping( $widget_info, $settings );

			return [ $widget_info, $settings ];
		}

		// Generate view settings from widget data
		static function mapping( $data, &$settings ) {

			$atts = self::get_controls_names();
			foreach ( $atts as $block_key => $info ) {
				if ( isset( $info[ '__key' ] ) ) {
					$key							 = ($info[ '__key' ] !== '__SAME__') ? $info[ '__key' ] : $block_key;
					$value							 = isset( $data[ $block_key ] ) ? $data[ $block_key ] : '';
					$settings[ PT_CV_PREFIX . $key ] = apply_filters( PT_CV_PREFIX_ . 'mapping_value', $value, $info, $data, $settings );
				}
			}

			// required options
			$settings[ PT_CV_PREFIX . 'advanced-settings' ] = array_keys( PT_CV_Values::advanced_settings() );

			// handle some complex options
			if ( empty( $settings[ PT_CV_PREFIX . 'limit' ] ) ) {
				$settings[ PT_CV_PREFIX . 'limit' ] = 1000000;
			}
			if ( empty( $settings[ PT_CV_PREFIX . 'pagination-items-per-page' ] ) ) {
				$settings[ PT_CV_PREFIX . 'pagination-items-per-page' ] = 6;
			}

			$settings[ PT_CV_PREFIX . 'field-thumbnail-nowprpi' ]	 = !$settings[ PT_CV_PREFIX . 'responsiveImg' ];
			$settings[ PT_CV_PREFIX . 'field-thumbnail-nodefault' ]	 = !$settings[ PT_CV_PREFIX . 'defaultImg' ];

			$settings[ PT_CV_PREFIX . 'multi-post-types' ] = self::values_from_widget( $data, 'multipostType', 'any' );

			$settings[ PT_CV_PREFIX . 'taxonomy' ] = PT_CV_Functions::get_taxonomies_by_post_type( $data );

			if ( is_array( $settings[ PT_CV_PREFIX . 'taxonomy' ] ) ) {
				foreach ( $settings[ PT_CV_PREFIX . 'taxonomy' ] as $taxonomy ) {
					$selected_terms = self::values_from_widget( $data, "{$taxonomy}__terms", [] );
					if ( !empty( $selected_terms ) ) {
						$settings[ PT_CV_PREFIX . "$taxonomy-terms" ] = $selected_terms;
					}
				}
			}

			// show ctf
			$settings[ PT_CV_PREFIX . 'custom-fields-list' ] = self::values_from_widget( $data, 'CTFlist', '' );
			if ( isset( $data[ 'CTFname' ] ) && $data[ 'CTFname' ] ) {
				$settings[ PT_CV_PREFIX . 'custom-fields-enable-custom-name' ] = 'yes';
			}
			
			$postparent = isset( $data[ 'parentPage' ] ) ? intval( $data[ 'parentPage' ] ) : null;
			if ( $postparent ) {
				$settings[ PT_CV_PREFIX . 'post_parent' ] = $postparent;
			}

			$settings[ PT_CV_PREFIX . 'post__in' ]		 = self::values_from_widget( $data, 'includeId', '' );
			$settings[ PT_CV_PREFIX . 'post__not_in' ]	 = self::values_from_widget( $data, 'excludeId', '' );

			$settings[ PT_CV_PREFIX . 'author__in' ]							 = self::values_from_widget( $data, 'author', '' );
			$settings[ PT_CV_PREFIX . 'author__not_in' ]						 = self::values_from_widget( $data, 'authorNot', '' );
			$columns															 = isset( $data[ 'columns' ] ) ? (int) $data[ 'columns' ] : 1;
			$settings[ PT_CV_PREFIX . $data[ 'viewType' ] . '-number-columns' ]	 = $columns;
//			$settings[ PT_CV_PREFIX . 'resp-tablet-number-columns' ]			 = isset( $columns[ 'sm' ] ) ? $columns[ 'sm' ] : $columns[ 'md' ];
//			$settings[ PT_CV_PREFIX . 'resp-number-columns' ]					 = $columns[ 'xs' ];

			$meta = self::values_from_widget( $data, 'metaWhich', array() );
			foreach ( $meta as $field ) {
				$settings[ PT_CV_PREFIX . "meta-fields-$field" ] = 'yes';
			}

			// Make value like Block
			$settings[ PT_CV_PREFIX . 'metaWhichOthers' ] = array_map( [ __CLASS__, 'value_like_block' ], $settings[ PT_CV_PREFIX . 'metaWhichOthers' ] );

			// Switch fields position
			$settings = ContentViews_Block::topmeta_reposition( $settings );

			$settings = apply_filters( PT_CV_PREFIX_ . 'mapping_settings', $settings, $data );

			//echo "<pre>"; print_r($settings); echo "</pre>";
		}

		// Elementor returns empty string for not modified controls
		static function values_from_widget( $arr, $key, $default ) {
			return !empty( $arr[ $key ] ) ? $arr[ $key ] : $default;
		}

		// Array of [widgetControl => viewSettingKey]
		static function get_controls_names() {
			$general_controls = ContentViews_Block::get_attributes( false );
			// Don't include Taxonomy Live Filter attributes (because Elementor doesn't use "-" in control name)
			// Don't include Style attributes (Style controls are already processed by Elementor with 'selector' or 'selectors')

			// Taxonomy Live Filter: replace - by __
			$taxos = PT_CV_Values::taxonomy_list( true );
			foreach ( (array) array_keys( $taxos ) as $taxonomy ) {
				// update key for this control
				$general_controls[ "{$taxonomy}__operator" ] = [ 'type' => 'string', '__key' => "$taxonomy-operator", ];

				$general_controls[ "{$taxonomy}__LfEnable" ]	 = [ 'type' => 'boolean', '__key' => "$taxonomy-live-filter-enable", ];
				$general_controls[ "{$taxonomy}__LfType" ]		 = [ 'type' => 'string', '__key' => "$taxonomy-live-filter-type", ];
				$general_controls[ "{$taxonomy}__LfBehavior" ]	 = [ 'type' => 'string', '__key' => "$taxonomy-live-filter-operator", ];
				$general_controls[ "{$taxonomy}__LfLabel" ]		 = [ 'type' => 'string', '__key' => "$taxonomy-live-filter-heading", ];
				$general_controls[ "{$taxonomy}__LfDefault" ]	 = [ 'type' => 'string', '__key' => "$taxonomy-live-filter-default-text", ];
				$general_controls[ "{$taxonomy}__LfOrder" ]		 = [ 'type' => 'string', '__key' => "$taxonomy-live-filter-order-options", ];
				$general_controls[ "{$taxonomy}__LfOrderFlag" ]	 = [ 'type' => 'string', '__key' => "$taxonomy-live-filter-order-flag", ];
				$general_controls[ "{$taxonomy}__LfCount" ]		 = [ 'type' => 'boolean', '__key' => "$taxonomy-live-filter-show-count", ];
				$general_controls[ "{$taxonomy}__LfNoEmpty" ]	 = [ 'type' => 'boolean', '__key' => "$taxonomy-live-filter-hide-empty", ];
				$general_controls[ "{$taxonomy}__LfRequire" ]	 = [ 'type' => 'boolean', '__key' => "$taxonomy-live-filter-require-exist", ];
			}

			// custom controls in all widgets, those setting != key			
			$custom_others = [
				'isElementorWidget'	 => [
					'__key' => '__SAME__',
				],
				'blockName'			 => [
					'__key' => '__SAME__',
				],
				// collapsible
				'openFirst'	 => [
					'__key' => 'collapsible-open-first-item',
				],
				'openAll'	 => [
					'__key' => 'collapsible-open-all',
				],
				// list1
				'formatWrap'	 => [
					'__key' => 'lf-nowrap',
				],
				'zigzag'		 => [
					'__key' => 'lf-alternate',
				],
				'thumbPosition'	 => [
					'__key' => 'field-thumbnail-position',
				],
				// onebig2
				'oneWidth'		 => [
					'__key' => '__SAME__',
				],
				'swapPosition'	 => [
					'__key' => '__SAME__',
				],
				// overlay2
				'isSpec'		 => [
					'__key' => '__SAME__',
				],
				// timeline
				'timeDistance'	 => [
					'__key' => 'timeline-long-distance',
				],
				'timeSimulate'	 => [
					'__key' => 'timeline-simulate-fb',
				],
			];

			// onebig1
			$custom_ob1 = [];
			foreach ( ContentViews_Block_OneBig1::onebig_atts() as $name => $arr ) {
				self::get_new_key_pair( $name, $arr, $custom_ob1 );
			}

			// overlay
			foreach ( ContentViews_Block_Overlay1::overlay_atts() as $name => $arr ) {
				self::get_new_key_pair( $name, $arr, $custom_ob1 );
			}

			// scrollable
			foreach ( ContentViews_Block_Scrollable::scrollable_atts() as $name => $arr ) {
				self::get_new_key_pair( $name, $arr, $custom_ob1 );
			}

			// Adverts
			for ( $i = 0; $i < 10; $i++ ) {
				$custom_ob1[ 'ads__content' . $i ] = [
					'__key' => 'ads-content' . $i,
				];
			}

			// Unset keys not existed in widget
			unset( $general_controls[ 'excerptManualPro' ] );
			unset( $general_controls[ 'excerptHtmlPro' ] );

			return array_merge( $general_controls, $custom_others, $custom_ob1 );
		}

		// Get new key/setting pair from custom attributes
		static function get_new_key_pair( $name, $arr, &$custom_ob1 ) {
			if ( isset( $arr[ '__key' ] ) ) {
				$custom_ob1[ $name ] = [
					'__key' => $arr[ '__key' ],
				];
			}
		}

		// Populate format [value=>, label=>]
		static function value_like_block( $value ) {
			return [ 'value' => $value, 'label' => $value ];
		}

		// Show Widget ID in Edit mode
		static function show_widget_id( $view_id ) {
			$is_edit = \Elementor\Plugin::$instance->editor->is_edit_mode();
			if ( $is_edit ) {
				$desc = __( 'Use it as the VIEW_ID in custom code', 'content-views-query-and-display-post-page' );
				printf( '<div class="cv-elementor-wid" style="opacity:0" title="%s">ID: %s</div>', esc_attr( $desc ), esc_html( $view_id ) );
			}
		}

	}

}