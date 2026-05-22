<?php
/*
 * Settings for Live Filter
 *
 * REQUIRE: under each 'params' must contain only 1 array(),
 * otherwise, the checked state and value might not be remained after save the view.
 *
 * @since 5.0
 * @author ptguy
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	die;
}

if ( is_admin() && class_exists( 'PT_Content_Views_Admin' ) ) {

	class CVP_LIVE_FILTER_SETTINGS {

		public static function typeof_filter( $prefix ) {
			if ( strpos( $prefix, 'ctf-filter-' ) !== false ) {
				return 'ctf-filter';
			} elseif ( strpos( $prefix, 'order-custom-field-' ) !== false ) {
				return 'ctf-sort';
			} else {
				return 'tax-filter';
			}
		}

		public static function name_postfix( $prefix ) {
			$type = self::typeof_filter( $prefix );
			return ($type === 'ctf-filter' || $type === 'ctf-sort') ? '[]' : '';
		}

		/**
		 * Enable Live Filter
		 *
		 * @param string $prefix
		 * @return array
		 */
		public static function enable_live_filter( $prefix, $label = false, $icon = 'search' ) {
			return array(
				'label'			 => array(
					'text' => '<span class="dashicons dashicons-' . $icon . '"></span>' . __( 'Live filter', 'content-views-pro' ),
				),
				'extra_setting'	 => array(
					'params' => array(
						'group-class' => PT_CV_PREFIX . 'live-filter-enable',
					),
				),
				'params'		 => array(
					array(
						'type'		 => 'checkbox',
						'name'		 => $prefix . 'live-filter-enable' . self::name_postfix( $prefix ),
						'options'	 => PT_CV_Values::yes_no( 'yes', $label ? $label : __( 'Show as filters to visitors', 'content-views-pro' )  ),
						'std'		 => '',
					),
				),
			);
		}

		/**
		 * Input type of Live Filter
		 *
		 * @param string $prefix
		 * @return array
		 */
		public static function output_type( $prefix ) {
			$for_ctf = ( self::typeof_filter( $prefix ) === 'ctf-filter' ) ? true : false;

			return array(
				'label'		 => array(
					'text' => __( 'Type', 'content-views-pro' ),
				),
				'params'	 => array(
					array(
						'type'		 => 'select',
						'name'		 => $prefix . 'live-filter-type' . self::name_postfix( $prefix ),
						'options'	 => PT_CV_Values_Pro::lf_types( $for_ctf ),
						'std'		 => 'dropdown',
					),
				),
				'dependence' => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		/**
		 * Operator for multi selections type: Checkbox
		 *
		 * @param type $prefix
		 * @return type
		 */
		public static function operator_options( $prefix ) {
			$setting = array(
				'label'			 => array(
					'text' => __( 'Behavior', 'content-views-pro' ),
				),
				'extra_setting'	 => array(
					'params' => array(
						'group-class'	 => PT_CV_PREFIX . 'live-filter-settings-operator',
						'wrap-class'	 => PT_CV_PREFIX . 'live-filter-settings-common',
					),
				),
				'params'		 => array(
					array(
						'type'		 => 'select',
						'name'		 => $prefix . 'live-filter-operator' . self::name_postfix( $prefix ),
						'options'	 => PT_CV_Values_Pro::lf_behavior(),
						'std'		 => 'AND',
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);

			// For taxonomy, leverage current dependency script
			$for_taxonomy = array(
				'label'			 => array(
					'text' => '',
				),
				'extra_setting'	 => array(
					'params' => array(
						'width' => 12,
					),
				),
				'params'		 => array(
					array(
						'type'	 => 'group',
						'params' => array(
							$setting
						),
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-type', 'checkbox' ),
			);

			return (self::typeof_filter( $prefix ) === 'ctf-filter') ? $setting : $for_taxonomy;
		}

		/**
		 * Default text for Radio, Dropdown
		 */
		public static function default_text( $prefix ) {
			$setting = array(
				'label'			 => array(
					'text' => __( 'The "All" Text', 'content-views-pro' ),
				),
				'extra_setting'	 => array(
					'params' => array(
						'group-class'	 => PT_CV_PREFIX . 'live-filter-settings-default_text',
						'wrap-class'	 => PT_CV_PREFIX . 'live-filter-settings-common',
					),
				),
				'params'		 => array(
					array(
						'type'	 => 'text',
						'name'	 => $prefix . 'live-filter-default-text' . self::name_postfix( $prefix ),
						'std'	 => '',
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);

			// For taxonomy, leverage current dependency script
			$for_taxonomy = array(
				'label'			 => array(
					'text' => '',
				),
				'extra_setting'	 => array(
					'params' => array(
						'width' => 12,
					),
				),
				'params'		 => array(
					array(
						'type'	 => 'group',
						'params' => array(
							$setting
						),
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-type', array( 'radio', 'dropdown', 'button' ) ),
			);

			return (self::typeof_filter( $prefix ) === 'ctf-filter') ? $setting : $for_taxonomy;
		}

		/**
		 * Heading text of Live Filter
		 *
		 * @param string $prefix
		 * @param string $default
		 * @param string $desc
		 * @return array
		 */
		public static function label_text( $prefix, $default = '', $desc = '' ) {
			return array(
				'label'		 => array(
					'text' => __( 'Label', 'content-views-pro' ),
				),
				'params'	 => array(
					array(
						'type'	 => 'text',
						'name'	 => $prefix . 'live-filter-heading' . self::name_postfix( $prefix ),
						'std'	 => $default,
						'desc'	 => $desc,
					),
				),
				'dependence' => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		/**
		 * Show posts count of Live Filter
		 *
		 * @param string $prefix
		 * @return array
		 */
		public static function show_posts_count( $prefix ) {
			return array(
				'label'			 => array(
					'text' => '',
				),
				'extra_setting'	 => array(
					'params' => array(
						'width'		 => 12,
						'wrap-class' => PT_CV_PREFIX . 'live-filter-settings-common',
					),
				),
				'params'		 => array(
					array(
						'type'		 => 'checkbox',
						'name'		 => $prefix . 'live-filter-show-count' . self::name_postfix( $prefix ),
						'options'	 => PT_CV_Values::yes_no( 'yes', __( 'Show posts count', 'content-views-pro' ) ),
						'std'		 => '',
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		/**
		 * Hide empty of Live Filter
		 *
		 * @param string $prefix
		 * @return array
		 */
		public static function hide_empty_values( $prefix ) {
			return array(
				'label'			 => array(
					'text' => '',
				),
				'extra_setting'	 => array(
					'params' => array(
						'width'		 => 12,
						'wrap-class' => PT_CV_PREFIX . 'live-filter-settings-common',
					),
				),
				'params'		 => array(
					array(
						'type'		 => 'checkbox',
						'name'		 => $prefix . 'live-filter-hide-empty' . self::name_postfix( $prefix ),
						'options'	 => PT_CV_Values::yes_no( 'yes', __( 'Hide empty values (which has no post)', 'content-views-pro' ) ),
						'std'		 => '',
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		public static function set_order_flag( $prefix ) {
			return array(
				'label'			 => array(
					'text' => '',
				),
				'extra_setting'	 => array(
					'params' => array(
						'wrap-class' => PT_CV_PREFIX . 'live-filter-settings-common',
					),
				),
				'params'		 => array(
					array(
						'type'		 => 'select',
						'name'		 => $prefix . 'live-filter-order-flag' . self::name_postfix( $prefix ),
						'options'	 => PT_CV_Values_Pro::lf_order_flag(),
						'std'		 => '',
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		public static function order_options_by( $prefix ) {
			$for_ctf = ( CVP_LIVE_FILTER_SETTINGS::typeof_filter( $prefix ) === 'ctf-filter' ) ? true : false;

			return array(
				'label'			 => array(
					'text' => __( 'Order by', 'content-views-pro' ),
				),
				'extra_setting'	 => array(
					'params' => array(
						'group-class' => PT_CV_PREFIX . 'live-filter-settings-common',
					),
				),
				'params'		 => array(
					array(
						'type'		 => 'select',
						'name'		 => $prefix . 'live-filter-order-options' . self::name_postfix( $prefix ),
						'options'	 => PT_CV_Values_Pro::lf_orderby( $for_ctf ),
						'std'		 => '',
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		public static function require_exist( $prefix ) {
			return array(
				'label'			 => array(
					'text' => '',
				),
				'extra_setting'	 => array(
					'params' => array(
						'width'		 => 12,
						'wrap-class' => PT_CV_PREFIX . 'live-filter-settings-common',
					),
				),
				'params'		 => array(
					array(
						'type'	 => 'group',
						'params' => array(
							array(
								'label'			 => array(
									'text' => '',
								),
								'extra_setting'	 => array(
									'params' => array(
										'width' => 12,
									),
								),
								'params'		 => array(
									array(
										'type'		 => 'checkbox',
										'name'		 => $prefix . 'live-filter-require-exist' . self::name_postfix( $prefix ),
										'options'	 => PT_CV_Values::yes_no( 'yes', __( "Always hide the posts which don't have this taxonomy", 'content-views-pro' ) ),
										'std'		 => '',
									),
								),
								'dependence'	 => array( $prefix . 'terms[]', '', PT_CV_Functions::wp_version_compare( '5.6' ) ? '== false ||' : '== null ||' ),
							),
						),
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		public static function hide_non_matching( $prefix ) {
			return array(
				'label'			 => array(
					'text' => '',
				),
				'extra_setting'	 => array(
					'params' => array(
						'width'		 => 12,
						'wrap-class' => PT_CV_PREFIX . 'live-filter-settings-common',
					),
				),
				'params'		 => array(
					array(
						'type'		 => 'checkbox',
						'name'		 => $prefix . 'live-filter-hide-non-matching' . self::name_postfix( $prefix ),
						'options'	 => PT_CV_Values::yes_no( 'yes', __( "Always hide the posts which don't have this custom field", 'content-views-pro' ) ),
						'std'		 => '',
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		public static function id_to_text( $prefix ) {
			return array(
				'label'			 => array(
					'text' => '',
				),
				'extra_setting'	 => array(
					'params' => array(
						'width'		 => 12,
						'wrap-class' => PT_CV_PREFIX . 'live-filter-settings-common',
					),
				),
				'params'		 => array(
					array(
						'type'	 => 'group',
						'params' => array(
							array(
								'label'			 => array(
									'text' => __( 'What to show as options?', 'content-views-pro' ),
								),
								'params'		 => array(
									array(
										'type'		 => 'select',
										'name'		 => $prefix . 'live-filter-id-to-text' . self::name_postfix( $prefix ),
										'options'	 => PT_CV_Values_Pro::lf_ctf_label(),
										'std'		 => '',
									),
								),
							),
						),
					),
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		static function _date_range( $prefix, $label, $array ) {
			return array(
				'label'			 => array(
					'text' => $label,
				),
				'extra_setting'	 => array(
					'params' => array(
						'group-class' => PT_CV_PREFIX . 'live-filter-settings-date_range',
					),
				),
				'params'		 => array(
					$array
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		public static function date_range_operator( $prefix ) {
			return self::_date_range( $prefix, __( 'Operator', 'content-views-pro' ), array(
					'type'		 => 'select',
					'name'		 => $prefix . 'live-filter-daterange-operator' . self::name_postfix( $prefix ),
					'options'	 => PT_CV_Values_Pro::lf_date_operator(),
					'std'		 => '',
					'desc'		 => __( "You can change the 'From' and 'To' texts in the Label field above (when selecting 'From - To', use comma to separate texts)", 'content-views-pro' ),
			) );
		}

		static function range_slider( $prefix, $label, $array ) {
			return array(
				'label'			 => array(
					'text' => $label,
				),
				'extra_setting'	 => array(
					'params' => array(
						'group-class' => PT_CV_PREFIX . 'live-filter-settings-range_slider',
					),
				),
				'params'		 => array(
					$array
				),
				'dependence'	 => array( $prefix . 'live-filter-enable', 'yes' ),
			);
		}

		public static function range_slider_from( $prefix ) {
			return self::range_slider( $prefix, __( 'Start from', 'content-views-pro' ), array(
					'type'	 => 'number',
					'name'	 => $prefix . 'live-filter-rangeslider-from' . self::name_postfix( $prefix ),
					'std'	 => '0',
				) );
		}

		public static function range_slider_step( $prefix ) {
			return self::range_slider( $prefix, __( 'Step', 'content-views-pro' ), array(
					'type'	 => 'text',
					'name'	 => $prefix . 'live-filter-rangeslider-step' . self::name_postfix( $prefix ),
					'std'	 => '1',
					'desc'	 => __( 'Allow only numbers and dot', 'content-views-pro' ),
			) );
		}

		public static function range_slider_prefix( $prefix ) {
			return self::range_slider( $prefix, __( 'Prefix', 'content-views-pro' ), array(
					'type'	 => 'text',
					'name'	 => $prefix . 'live-filter-rangeslider-prefix' . self::name_postfix( $prefix ),
					'std'	 => '',
				) );
		}

		public static function range_slider_postfix( $prefix ) {
			return self::range_slider( $prefix, __( 'Suffix', 'content-views-pro' ), array(
					'type'	 => 'text',
					'name'	 => $prefix . 'live-filter-rangeslider-postfix' . self::name_postfix( $prefix ),
					'std'	 => '',
				) );
		}

        public static function range_slider_thousand_separator( $prefix ) {
            return self::range_slider( $prefix, __( 'Thousand separator', 'content-views-pro' ), array(
                'type'    => 'select',
                'name'    => $prefix . 'live-filter-rangeslider-thousandseparator' . self::name_postfix( $prefix ),
                'options' => PT_CV_Values_Pro::lf_thousand_separator(),
                'std'  => '',
            ) );
        }

    }

}