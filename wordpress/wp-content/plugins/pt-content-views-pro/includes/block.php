<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'PT_CV_Block_Pro' ) ) {

    class PT_CV_Block_Pro {
		static function pro_block_hooks() {
			add_filter( PT_CV_PREFIX_ . 'block_attributes', array( __CLASS__, 'filter_block_attributes' ) );
			add_filter( PT_CV_PREFIX_ . 'block_settings', array( __CLASS__, 'filter_block_settings' ) );
			add_filter( PT_CV_PREFIX_ . 'block_fields', array( __CLASS__, 'filter_block_fields' ) );
			add_filter( PT_CV_PREFIX_ . 'mapping_value', array( __CLASS__, 'filter_mapping_value' ), 10, 4 );
			add_filter( PT_CV_PREFIX_ . 'mapping_settings', array( __CLASS__, 'filter_mapping_settings' ), 10, 2 );
			add_filter( PT_CV_PREFIX_ . 'block_editor_output', array( __CLASS__, 'filter_block_editor_output' ), 10, 2 );
			add_filter( PT_CV_PREFIX_ . 'block_localize_data', array( __CLASS__, 'filter_block_localize_data' ) );
			add_filter( PT_CV_PREFIX_ . 'dargs_others', array( __CLASS__, 'filter_dargs_others' ), 10, 2 );
			add_filter( PT_CV_PREFIX_ . 'mediathumb_html', array( __CLASS__, 'filter_mediathumb_html' ) );
			add_filter( PT_CV_PREFIX_ . 'set_view_settings', array( __CLASS__, 'filter_set_view_settings' ) );
		}

		static function filter_block_attributes( $atts ) {

			// show ctf
			$atts[ 'CTFnoempty' ]		 = [
				'__key'		 => 'custom-fields-hide-empty',
				'type'		 => 'boolean',
			];
			$atts[ 'CTFcustomname' ]	 = [
				'__key'	 => 'custom-fields-custom-name-list',
				'type'	 => 'string',
			];
			$atts[ 'CTFshortcode' ]		 = [
				'__key'	 => 'custom-fields-run-shortcode',
				'type'	 => 'boolean',
			];
			$atts[ 'CTFlinebreak' ]		 = [
				'__key'	 => 'custom-fields-enable-linebreak',
				'type'	 => 'boolean',
			];
			$atts[ 'CTFembed' ]		 = [
				'__key'	 => 'custom-fields-enable-oembed',
				'type'	 => 'boolean',
			];
			$atts[ 'CTFdatefm' ]		 = [
				'__key'	 => 'custom-fields-date-custom-format',
				'type'	 => 'boolean',
			];
			$atts[ 'CTFdatenew' ]		 = [
				'__key'	 => 'custom-fields-date-format',
				'type'	 => 'string',
				'default' => 'F j, Y',
			];
			$atts[ 'CTFdateold' ]		 = [
				'__key'	 => 'custom-fields-date-format-from',
				'type'	 => 'string',
			];

			// live sort ctf, @since 5.15
			$atts[ 'lfSortLabel' ]	 = [
				'__key'		 => 'livesort-live-filter-heading',
				'type'		 => 'string',
				'default'	 => __( 'Sort by', 'content-views-pro' ),
			];
			$atts[ 'lfSortDefault' ] = [
				'__key'	 => 'livesort-default-text',
				'type'	 => 'string',
			];
			$atts[ 'lfSortOpts' ]	 = [
				'type' => 'array',
			];
			$atts[ 'lfSortText' ]	 = [
				'__key'	 => 'livesort-options-text',
				'type'	 => 'string',
			];

			// live filter search, @since 5.15
			$atts[ 'searchLfEnable' ]	 = [
				'__key'		 => 'search-live-filter-enable',
				'type'		 => 'boolean',
			];
			$atts[ 'lfSearchLabel' ] = [
				'__key'	 => 'search-live-filter-heading',
				'type'	 => 'string',
				'default'	 => __( 'Search', 'content-views-pro' ),
			];
			$atts[ 'lfSearchHolder' ]	 = [
				'__key'	 => 'search-live-filter-placeholder',
				'type' => 'string',
			];

			$atts[ 'lfArrange' ]	 = [
				'type' => 'array',
			];
			$atts[ 'lfWrap' ]	 = [
				'__key' => 'wrap-live-filter',
				'type' => 'boolean',
			];
			$atts[ 'lfPosition' ]	 = [
				'type' => 'string',
				'default' => 'above',
			];
			$atts[ 'lfWidth' ]	 = [
				'type' => 'string',
			];

			$atts[ 'lfCustomize' ]	 = [
				'__key' => 'lf-element-customize',
				'type' => 'boolean',
			];
			$atts[ 'lfEleColor' ]	 = [
				'type' => 'string',
			];

			// published date, @since 5.15
			$atts[ 'postDate' ]	 = [
				'__key' => 'post_date_custom_date',
				'type' => 'string',
			];
			$atts[ 'postDateFrom' ]	 = [
				'__key' => 'post_date_from',
				'type' => 'string',
			];
			$atts[ 'postDateTo' ]	 = [
				'__key' => 'post_date_to',
				'type' => 'string',
			];
			$atts[ 'postYear' ]	 = [
				'__key' => 'post_date_custom_year',
				'type' => 'string',
				'default' => current_time( 'Y' ),
			];
			$atts[ 'postMonth' ]	 = [
				'__key' => 'post_date_custom_month',
				'type' => 'string',
				'default' => current_time( 'n' ),
			];

			// show ads, @since 5.15
			$atts[ 'adPosition' ]	 = [
				'__key' => 'ads-position',
				'type' => 'string',
			];
			$atts[ 'adPositionMan' ]	 = [
				'__key' => 'ads-position-manual',
				'type' => 'string',
			];
			$atts[ 'adSCode' ]	 = [
				'__key' => 'ads-enable-shortcode',
				'type' => 'boolean',
			];
			$atts[ 'adRepeat' ]	 = [
				'__key' => 'ads-repeat-times',
				'type' => 'string',
				'default' => '1',
			];
			$atts[ 'adPerPage' ]	 = [
				'__key' => 'ads-per-page',
				'type' => 'string',
				'default' => '1',
			];
			for ($i = 0; $i < 10; $i++) {
				$atts[ 'ads-content' . $i ]	 = [
					'__key' => '__SAME__',
					'type' => 'string',
				];
			}

			// image sub
			$atts[ 'subImgRole' ]	 = [
				'__key'		 => 'field-thumbnail-role',
				'type'		 => 'string',
			];
			$atts[ 'subImgCtf' ]	 = [
				'type' => 'object',
			];
			$atts[ 'subImgFetch' ]	 = [
				'__key'	 => 'field-fetch-builder-content',
				'type'	 => 'boolean',
			];

			// Exclude
			$atts[ 'excludeCurrent' ]	 = [
				'__key'	 => 'exclude-current',
				'type'	 => 'boolean',
			];
			$atts[ 'excludeProtected' ]	 = [
				'__key'	 => 'exclude-pw-protected',
				'type'	 => 'boolean',
			];
			$atts[ 'excludeChild' ]	 = [
				'__key'	 => 'exclude-children-posts',
				'type'	 => 'boolean',
			];

			// Social share
			$atts[ 'showShare' ]	 = [
				'type'	 => 'boolean',
			];
			$atts[ 'shareBtn' ]	 = [
				'type'		 => 'array',
				'default'	 => [
					[ 'value' => 'facebook', 'label' => __( 'Facebook', 'content-views-pro' ) ],
					[ 'value' => 'twitter', 'label' => __( 'X (Twitter)', 'content-views-pro' ) ]
				],
			];
			$atts[ 'shareCircle' ]	 = [
				'__key'	 => 'other-social-circle',
				'type'	 => 'boolean',
			];
			$atts[ 'shareCount' ]	 = [
				'__key'	 => 'other-social-count',
				'type'	 => 'boolean',
			];

			// Other options
			$atts[ 'authorCurrent' ] = [
				'__key'		 => 'author-current-user',
				'type'		 => 'string',
				'default'	 => '',
			];

			$atts[ 'stickyPost' ] = [
				'__key'	 => 'sticky-posts',
				'type'	 => 'string',
				//'default'	 => 'default',
			];

			$atts[ 'excerptManualPro' ] = [
				'__key'		 => 'field-excerpt-manual',
				'type'		 => 'string',
				'default'	 => 'yes',
			];
			$atts[ 'excerptHtmlPro' ] = [
				'__key'		 => 'field-excerpt-allow_html',
				'type'		 => 'string',
			];
			$atts[ 'excerptNoDots' ]	 = [
				'__key'	 => 'field-excerpt-hide_dots',
				'type'	 => 'boolean',
			];
			$atts[ 'excerptExclude' ]	 = [
				'__key'	 => 'field-excerpt-remove-tag',
				'type'	 => 'boolean',
			];
			$atts[ 'excerptExcTag' ]	 = [
				'__key'	 => 'field-excerpt-tag-to-remove',
				'type'	 => 'string',
			];
			$atts[ 'excerptHook' ]	 = [
				'__key'	 => 'field-excerpt-enable_filter',
				'type'	 => 'boolean',
			];

			return $atts;
		}

		static function filter_block_settings( $attributes ) {
			$layout = $attributes[ 'whichLayout' ];

			if ( $attributes[ 'blockName' ] === 'onebig1' && $layout === 'layout3' ) {
				$attributes[ 'layoutFormat' ] = '2-col';
			}

			if ( $attributes[ 'blockName' ] === 'onebig2' && $layout !== 'layout1' ) {
				$attributes[ 'showThumbnailOthers' ] = true;
				$attributes[ 'thumbPositionOthers' ] = ($layout === 'layout2') ? 'left' : 'right';
			}

			if ( $attributes[ 'blockName' ] === 'overlay2' && $layout !== 'layout1' ) {
				$arr = [ 'layout2' => 4, 'layout3' => 5 ];

				$attributes[ 'postsPerPage' ] = $arr[ $layout ];
			}

			if ( $attributes[ 'blockName' ] === 'overlay3' && $layout === 'layout3' ) {
				$attributes[ 'postsPerPage' ] = 5;
			}

			if ( $attributes[ 'blockName' ] === 'overlay4' ) {
				if ( $layout === 'layout3' ) {
					$attributes[ 'postsPerPage' ] = 3;
				}
				if ( $layout === 'layout5' ) {
					$attributes[ 'postsPerPage' ] = 5;
				}
			}

			$is_elementor_widget = class_exists( 'ContentViews_Elementor_Init' ) && ContentViews_Elementor_Init::is_widget( $attributes );
			if ( !$is_elementor_widget ) {
				// For Select (blank/yes/) that has equivalent Toogle (true/false) in Free, must convert when not modified in Pro yet
				if ( isset( $attributes[ 'excerptManual' ] ) && $attributes[ 'excerptManual' ] !== true ) {
					$attributes[ 'excerptManualPro' ] = '';
				}
				if ( isset( $attributes[ 'excerptHtml' ] ) && $attributes[ 'excerptHtml' ] !== false ) {
					$attributes[ 'excerptHtmlPro' ] = 'yes';
				}
			}

			return $attributes;
		}

		static function filter_block_fields( $args ) {
			$args[] = 'LFSlabel';
			$args[] = 'LFSoption';
			$args[] = 'LFSrange';
			$args[] = 'LFSbutton';
			$args[] = 'LFSsubmit';
			$args[] = 'LFSreset';
			return $args;
		}

		static function filter_block_editor_output( $output, $block_attributes ) {
			if ( PT_CV_Functions::get_global_variable( 'lf_enabled' ) ) {
				$text	 = '<p style="text-align: center; background: #eee; margin: 10px 0;">' . __( 'Please view the page to see fully functional filters', 'content-views-query-and-display-post-page' ) . '</p>';
				$output	 = $text . $output;
			}

			// custom CSS for editor only
			if ( !empty( $block_attributes[ 'showPagination' ] ) ) {
				$text	 = '<style> .block-editor .pt-cv-pginfinite+.pt-cv-pagination-wrapper .pt-cv-spinner {opacity: 1 !important} </style>';
				$output	 .= $text;
			}

			return $output;
		}

		// @since 5.15
		static function filter_mapping_value( $value, $info, $data, $settings ) {
			// taxonomy live filter options
			if ( strpos( $info[ '__key' ], 'live-filter-' ) !== false ) {
				// boolean value set 'yes'
				if ( $value && $info[ 'type' ] === 'boolean' ) {
					$value = 'yes';
				}

				if ( strpos( $info[ '__key' ], 'live-filter-type' ) !== false && empty( $value ) ) {
					$value = 'dropdown';
				}
			}

			return $value;
		}

		static function filter_mapping_settings( $settings, $data ) {
			// @since 6.4
			$is_elementor_widget = class_exists( 'ContentViews_Elementor_Init' ) && ContentViews_Elementor_Init::is_widget( $data );

			// @since 5.14
			if ( isset( $data[ 'sortCtf' ] ) ) {
				$arr = [
					'datefm'	 => 'date-format',
					'kcomma'	 => 'thousand-commas',
					'lfenable'	 => 'enable',
					'lftext'	 => 'heading',
				];

				foreach ( $data[ 'sortCtf' ] as $ctf ) {
					// add missing keys
					foreach ( array_keys( $arr ) as $option ) {
						if ( !isset( $ctf[ $option ] ) ) {
							$ctf[ $option ] = '';
						}
					}

					foreach ( $ctf as $key => $value ) {
						// modify key/value
						if ( strpos( $key, 'lf' ) !== false ) {
							// boolean value set 'yes'
							if ( ($value === 'true' || (int) $value === 1) && in_array( $key, [ 'lfenable' ] ) ) {
								$value = 'yes';
							}

							$key = 'live-filter-' . (isset($arr[ $key ]) ? $arr[ $key ] : '');
						} else if ( isset($arr[ $key ]) ) {
							$key = $arr[ $key ];
						}

						// init
						if ( !isset( $settings[ PT_CV_PREFIX . 'order-custom-field-' . $key ] ) ) {
							$settings[ PT_CV_PREFIX . 'order-custom-field-' . $key ] = [];
						}

						// modify value
						if ( $key === 'key' ) {
							$value = isset( $value[ 'value' ] ) ? $value[ 'value' ] : $value;
						}

						$settings[ PT_CV_PREFIX . 'order-custom-field-' . $key ][] = $value;
					}
				}
			}

			// @since 5.14
			if ( isset( $data[ 'filterCtf' ] ) ) {
				$arr = [
					'datefm'		 => 'date-format',
					'lfenable'		 => 'enable',
					'lftype'		 => 'type',
					'lfbehavior'	 => 'operator',
					'lflabel'		 => 'heading',
					'lfdefault'		 => 'default-text',
					'lforder'		 => 'order-options',
					'lforderflag'	 => 'order-flag',
					'lftotext'		 => 'id-to-text',
					'lfcount'		 => 'show-count',
					'lfnoempty'		 => 'hide-empty',
					'lfrequire'		 => 'hide-non-matching',
					'lfdateoperator' => 'daterange-operator',
					'lfrangestep'	 => 'rangeslider-step',
					'lfrangepre'	 => 'rangeslider-prefix',
					'lfrangepos'	 => 'rangeslider-postfix',
					'lfrangesepa'	 => 'rangeslider-thousandseparator',
				];

				$defaults = [
					'lftype'		 => 'dropdown',
					'lfbehavior'	 => 'AND',
					'lfdateoperator' => 'date-from',
					'lfrangestep'	 => '1',
					'lfrangesepa'	 => 'space',
				];

				foreach ( $data[ 'filterCtf' ] as $ctf ) {
					// add missing keys
					foreach ( array_keys( $arr ) as $option ) {
						if ( !isset( $ctf[ $option ] ) ) {
							$ctf[ $option ] = isset( $defaults[ $option ] ) ? $defaults[ $option ] : '';
						}
					}

					foreach ( $ctf as $key => $value ) {
						// @since 6.4
						if ( $is_elementor_widget ) {
							// ignore keys that not belong to selected types
							if ( strpos( $key, 'ele_operator_' ) !== false ) {
								if ( $key !== 'ele_operator_' . $ctf[ 'type' ] ) {
									continue;
								}
							}
							if ( strpos( $key, 'ele_value_' ) !== false ) {
								if ( $key !== 'ele_value_' . $ctf[ 'type' ] ) {
									continue;
								}
							}

							// ignore 'value' for date/datetime as they use their own value key
							if ( $key === 'value' && strpos( $ctf[ 'type' ], 'DATE' ) !== false ) {
								continue;
							}

							// set key and value for elementor
							if ( strpos( $key, 'ele_operator_' ) !== false ) {
								$key	 = 'operator';
								$value	 = $ctf[ 'ele_operator_' . $ctf[ 'type' ] ];
							}
							if ( strpos( $key, 'ele_value_' ) !== false ) {
								$key	 = 'value';
								$value	 = $ctf[ 'ele_value_' . $ctf[ 'type' ] ];
							}
						}

						// modify key/value
						if ( strpos( $key, 'lf' ) !== false ) {
							// boolean value set 'yes'
							if ( ($value === 'true' || (int) $value === 1) && in_array( $key, [ 'lfenable', 'lfcount', 'lfnoempty', 'lfrequire' ] ) ) {
								$value = 'yes';
							}

							$key = 'live-filter-' . (isset($arr[ $key ]) ? $arr[ $key ] : '');
						} else if ( isset($arr[ $key ]) ) {
							$key = $arr[ $key ];
						}

						// init
						if ( !isset( $settings[ PT_CV_PREFIX . 'ctf-filter-' . $key ] ) ) {
							$settings[ PT_CV_PREFIX . 'ctf-filter-' . $key ] = [];
						}

						// modify value
						if ( $key === 'key' ) {
							$value = isset( $value[ 'value' ] ) ? $value[ 'value' ] : $value;
						}

						if ( $key === 'value' && strpos( $ctf[ 'type' ], 'DATE' ) !== false && !empty( $value ) && strtotime( $value ) !== false ) {
							$date	 = new DateTime( $value );
							$value	 = $date ? $date->format( 'Y/m/d' . (($ctf[ 'type' ] == 'DATETIME') ? ' H:i:s' : '') ) : $value;
						}

						$settings[ PT_CV_PREFIX . 'ctf-filter-' . $key ][] = $value;
					}
				}

			}

			// @since 5.15
			$lfso = ContentViews_Block::values_from_block( $data, 'lfSortOpts', '' );
			if ( $is_elementor_widget ) {
				$lfso = isset( $data[ 'lfSortOpts' ] ) ? $data[ 'lfSortOpts' ] : '';
			}
			if ( !empty( $lfso ) ) {
				$settings[ PT_CV_PREFIX . 'livesort-options' ] = $lfso;
			}

			$lfarr = ContentViews_Block::values_from_block( $data, 'lfArrange', '' );
			if ( $is_elementor_widget ) {
				$lfarr = isset( $data[ 'lfArrange' ] ) ? $data[ 'lfArrange' ] : '';
			}
			if ( !empty( $lfarr ) ) {
				$settings[ PT_CV_PREFIX . 'position-live-filters' ] = implode( ',', $lfarr );
			}

			if ( !empty( $data[ 'lfPosition' ] ) ) {
				if ( $data[ 'lfPosition' ] !== 'above' ) {
					$settings[ PT_CV_PREFIX . 'wrap-live-filter' ] = 'yes';

					$lfwid	 = isset( $data[ 'lfWidth' ] ) ? sanitize_text_field( $data[ 'lfWidth' ] ) : '3-9';
					$lfwid	 = explode( '-', $lfwid );
					if ( isset( $lfwid[ 0 ], $lfwid[ 1 ] ) ) {
						$lfclss = " col-md-{$lfwid[ 0 ]}" . ($data[ 'lfPosition' ] === 'onright' ? ' pull-right' : '');
						$settings[ PT_CV_PREFIX . 'class-live-filter' ] = $lfclss;

						if ( !isset( $settings[ PT_CV_PREFIX . 'view-css-class' ] ) ) {
							$settings[ PT_CV_PREFIX . 'view-css-class' ] = '';
						}
						if ( strpos( $settings[ PT_CV_PREFIX . 'view-css-class' ], 'col-md' ) === false ) {
							$settings[ PT_CV_PREFIX . 'view-css-class' ] .= " col-md-{$lfwid[ 1 ]}";
						}
					}
				}
			}


			if ( $is_elementor_widget ) {
				// in case this control is not saved in JS (enable lf, then save, without open lf configuration panel)
				// for output only, not for show/hide lf configuration controls in editor
				$data[ 'hasLF' ] = ContentViews_Elementor_Function_Pro::check_has_livefilter( $data );
			}
			if ( !empty( $data[ 'hasLF' ] ) ) {
				$settings[ PT_CV_PREFIX . 'taxonomy-exclude-children' ] = 'yes';
			}


			// @since 5.16
			$imcf = isset( $data[ 'subImgCtf' ][ 'value' ] ) ? $data[ 'subImgCtf' ][ 'value' ] : null;
			if ( !empty( $imcf ) ) {
				$settings[ PT_CV_PREFIX . 'field-thumbnail-ctf' ] = $imcf;
			}

			if ( !empty( $data[ 'pinNoBox' ] ) ) {
				$settings[ PT_CV_PREFIX . 'pinterest-box-style' ] = 'border';
			}
			if ( !empty( $data[ 'pinNoBd' ] ) ) {
				$settings[ PT_CV_PREFIX . 'pinterest-no-bb' ] = 'no-bb';
			}

			if ( !empty( $data[ 'showShare' ] ) ) {
				$settings[ PT_CV_PREFIX . 'other-social-show' ] = 'yes';
			}
			$sharebtn = ContentViews_Block::values_from_block( $data, 'shareBtn', '' );
			if ( $is_elementor_widget ) {
				$sharebtn = isset( $data[ 'shareBtn' ] ) ? $data[ 'shareBtn' ] : '';
			}
			if ( !empty( $sharebtn ) ) {
				$settings[ PT_CV_PREFIX . 'other-social-buttons' ] = $sharebtn;
			}

			if ( isset( $data[ 'viewType' ] ) && $data[ 'viewType' ] === 'timeline' ) {
				$ps = isset( $settings[ PT_CV_PREFIX . 'pagination-style' ] ) ? $settings[ PT_CV_PREFIX . 'pagination-style' ] : '';
				if ( $ps === 'regular' ) {
					$settings[ PT_CV_PREFIX . 'pagination-style' ] = 'infinite';
				}
			}

			// @since 6.2 temporary disable this feature for blocks
			$membership_plugin = PT_CV_Functions_Pro::has_access_restriction_plugin();
			if ( $membership_plugin ) {
				$settings[ PT_CV_PREFIX . 'advanced-settings' ] = array_diff( $settings[ PT_CV_PREFIX . 'advanced-settings' ], [ 'check_access_restriction' ] );
			}


			// @since 7.2 fix warning in media.php line 71 for old Block/Widget that selected "Custom size" image option
			if ( $settings[ PT_CV_PREFIX . 'field-thumbnail-size' ] === PT_CV_PREFIX . 'custom' ) {
				$settings[ PT_CV_PREFIX . 'field-thumbnail-size' ] = 'full';
			}


			return $settings;
		}

		static function filter_block_localize_data( $localize ) {
			$localize[ 'data' ][ 'custom_field_keys' ] = PT_CV_Values_Pro::custom_fields( 'default empty' );
			$localize[ 'data' ][ 'custom_field_types' ]	 = PT_CV_Values_Pro::custom_field_type();
			$localize[ 'data' ][ 'ctf_operator_all' ]	 = PT_CV_Values_Pro::ctf_filter_operator_all();
			$localize[ 'data' ][ 'ctf_operator_each' ]	 = PT_CV_Values_Pro::ctf_filter_operator_each();
			$localize[ 'data' ][ 'ctfdesc_select' ]	 = PT_CV_Values_Pro::ctf__desc( 'select' );
			$localize[ 'data' ][ 'ctfdesc_name' ]	 = PT_CV_Values_Pro::ctf__desc( 'name' );
			$localize[ 'data' ][ 'ctfdesc_datenew' ]	 = PT_CV_Values_Pro::ctf__desc( 'datenew' );
			$localize[ 'data' ][ 'ctfdesc_dateold' ]	 = PT_CV_Values_Pro::ctf__desc( 'dateold' );

			// @since 5.15
			$localize[ 'data' ][ 'lfsort_options' ]	 = CVP_LIVE_FILTER_SORTBY::common_sortby();
			$localize[ 'data' ][ 'lf_settings' ] = [
				'types'		 => PT_CV_Values_Pro::lf_types(),
				'types_ctf'	 => PT_CV_Values_Pro::lf_types( true ),
				'behavior' => PT_CV_Values_Pro::lf_behavior(),
				'orderflag' => PT_CV_Values_Pro::lf_order_flag(),
				'orderby'	 => PT_CV_Values_Pro::lf_orderby(),
				'orderby_ctf'	 => PT_CV_Values_Pro::lf_orderby( true ),
				'text_ctf'		 => PT_CV_Values_Pro::lf_ctf_label(),
				'dateoperator'	 => PT_CV_Values_Pro::lf_date_operator(),
				'thousandseparator' => PT_CV_Values_Pro::lf_thousand_separator(),
			];
			$localize[ 'data' ][ 'lf_position' ]	 = [
				'above' => __( 'Above results', 'content-views-pro' ),
				'onleft' => __( 'On the left side', 'content-views-pro' ),
				'onright' => __( 'On the right side', 'content-views-pro' ),
			];
			$localize[ 'data' ][ 'lf_width' ]	 = [
				'3-9' => '25%',
				'4-8' => '33%',
				'6-6' => '50%',
			];

			$localize[ 'data' ][ 'month_options' ] = array_combine( range( 1, 12 ), range( 1, 12 ) );

			$localize[ 'data' ][ 'ad_positions' ] = PT_CV_Values_Pro::ad_positions();
			$localize[ 'data' ][ 'ad_desc' ] = PT_CV_Values_Pro::ad_desc1();

			$localize[ 'data' ][ 'img_sub_role' ] = PT_CV_Values_Pro::imgsub_role();

			$localize[ 'data' ][ 'social_btns' ] = PT_CV_Values_Pro::social_buttons();

			$localize[ 'data' ][ 'author_current' ] = PT_CV_Values_Pro::current_author_options();
			$localize[ 'data' ][ 'sticky_options' ] = PT_CV_Values_Pro::sticky_posts();
			$localize[ 'data' ][ 'manual_excerpt_options' ] = PT_CV_Values_Pro::manual_excerpt_settings();
			$localize[ 'data' ][ 'html_excerpt_options' ]	 = PT_CV_Values_Pro::excerpt_html_options();

			$localize[ 'data' ][ 'ctf_operator_elementor' ] = PT_CV_Values_Pro::ctf_filter_operator_elementor();

			return $localize;
		}

		public static function filter_dargs_others( $args, $post_idx ) {

			// Fields Position
			$position = PT_CV_Functions::setting_value( PT_CV_PREFIX . 'fieldsPosition' );
			if ( is_array( $position ) ) {
				$reorder_fields	 = $position;
				$new_order		 = [];
				$topMetaIdx		 = array_search( 'taxoterm', $args[ 'fields' ] );
				$block_atts		 = ContentViews_Block::get_attributes();
				foreach ( $reorder_fields as $idx => $block_field ) {
					$field = isset( $block_atts[ $block_field ][ '__key' ] ) ? str_replace( 'show-field-', '', $block_atts[ $block_field ][ '__key' ] ) : null;
					if ( in_array( $field, $args[ 'fields' ] ) ) {
						if ( $field === 'title' && $topMetaIdx !== false ) {
							$oldTitleIdx						 = array_search( 'title', $args[ 'fields' ] );
							$taxoIdx							 = ($topMetaIdx < $oldTitleIdx) ? -1 : 1;
							$new_order[ $idx * 5 + $taxoIdx ]	 = 'taxoterm';
						}

						$new_order[ $idx * 5 ] = $field;
					}
				}
				if ( $new_order ) {
					ksort( $new_order, SORT_NUMERIC );
					$args[ 'fields' ] = $new_order;
				}
			}

			return $args;
		}

		public static function filter_mediathumb_html( $args ) {
			if ( PT_CV_Functions_Pro::is_pure_block() ) {
				global $post;
				$mainp	 = PT_CV_Functions::get_global_variable( 'main_posts' );
				$small	 = is_array( $mainp ) && !in_array( $post->ID, $mainp );

				$class	 = PT_CV_PREFIX . ($small ? 'thumbnailsm' : 'thumbnail');
				$args	 = str_replace( 'cvp-videothumb', $class, $args );
			}

			return $args;
		}

		// @since 5.15
		public static function filter_set_view_settings( $args ) {
			// for blocks: set current post ID for live filter
			if ( !empty( $_POST[ 'postid' ] ) ) {
				$GLOBALS[ 'cv_current_post' ] = (int) $_POST[ 'postid' ];
			}

			// @since 6.4
			if ( !empty( $_POST[ 'iselementor' ] ) ) {
				$GLOBALS[ 'cv_elementor_widgetID' ] = cv_sanitize_vid( $_POST[ 'iselementor' ] );
			}

			return $args;
		}

	}
}

add_action( PT_CV_PREFIX_ . 'init', array( 'PT_CV_Block_Pro', 'pro_block_hooks' ) );
