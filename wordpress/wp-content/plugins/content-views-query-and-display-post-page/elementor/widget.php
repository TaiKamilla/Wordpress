<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'ContentViews_Elementor_Widget' ) ) {

	class ContentViews_Elementor_Widget extends \Elementor\Widget_Base {
		
		protected static $switchOn		 = 'yes';
		protected static $switchOff		 = '';
		protected static $hasPro		 = false;
		protected static $localizeData	 = '';

		public function get_name() {

		}

		public function get_title() {

		}

		public function get_icon() {
			return $this->get_name();
		}

		public function get_categories() {
			return [ 'contentviews-elementor' ];
		}

		public function get_keywords() {
			return [ 'grid', 'list', 'content', 'view', 'layout' ];
		}

		public function get_custom_help_url() {

		}

		protected function get_upsale_data() {
			if ( PT_CV_Functions::has_pro() ) {
				$require_pro	 = '7.0';
				$pro_version	 = get_option( 'pt_cv_version_pro' );
				$update_require	 = $pro_version && version_compare( $pro_version, $require_pro, '<' ) ? true : false;
				return $update_require ? [
					'condition'		 => true,
					'title'			 => esc_html__( 'Update PRO version', 'elementor-list-widget' ),
					'description'	 => esc_html__( 'Please update PRO version to 7.0 to ensure all premium features work as expected', 'elementor-list-widget' ),
				] : null;
			} else {
				return [
					'condition'		 => true,
					'title'			 => esc_html__( 'Unlock All Possibilities', 'elementor-list-widget' ),
					'description'	 => esc_html__( 'Get access to advanced filters, frontend search, premium layouts with Content Views Pro', 'elementor-list-widget' ),
					'upgrade_url'	 => 'https://www.contentviewspro.com/?utm_source=elementorWidget&utm_medium=banner',
					'upgrade_text'	 => esc_html__( 'Upgrade Now', 'elementor-list-widget' ),
				];
			}
		}

		/** Do later
		  public function get_script_depends() {

		  }

		 */
		public function get_style_depends() {
			return [ 'contentviews-for-widget' ];
		}

		protected function render() {
			ContentViews_Elementor_Render::widget_output( $this );
		}

		protected function content_template() {

		}

		protected function register_controls() {
			$GLOBALS[ 'cvElementor' ] = true;
			
			$prefix			 = ' .' . PT_CV_PREFIX;
			self::$hasPro		 = PT_CV_Functions::has_pro();
			self::$localizeData	 = $localize_info		 = self::_localize_data();

			// Tab 'Content'
			$this->start_controls_section(
			'contentviews_section_query', [
				'label'	 => esc_html__( 'Query', 'content-views-query-and-display-post-page' ),
				'tab'	 => \Elementor\Controls_Manager::TAB_CONTENT,
			]
			);
			$this->_query_controls( $localize_info );
			$this->end_controls_section();


			// Tab 'Layout'
			$this->start_controls_section(
			'contentviews_section_layout', [
				'label'	 => esc_html__( 'Layout', 'content-views-query-and-display-post-page' ),
				'tab'	 => \Elementor\Controls_Manager::TAB_LAYOUT,
			]
			);
			$this->_layout_controls( $localize_info, $prefix );
			$this->end_controls_section();

			// Tab 'Settings'
			$this->start_controls_section(
			'contentviews_section_fields', [
				'label'	 => esc_html__( 'Fields', 'content-views-query-and-display-post-page' ),
				'tab'	 => self::_another_tab(),
			]
			);
			$this->_fields_controls( $localize_info, $prefix );
			$this->end_controls_section();

			do_action( PT_CV_PREFIX_ . 'top_section', $this );

			$arr = self::_fields_list();
			foreach ( $arr as $key => $text ) {
				$method_name = "_{$key}_controls";
				if ( method_exists( $this, $method_name ) ) {
					$this->start_controls_section(
					'contentviews_section_' . $key, [
						'label'	 => $text,
						'tab'	 => self::_another_tab(),
					]
					);
					$controls = $this->$method_name( $localize_info, $prefix );

					if ( in_array( $key, [ 'item', 'showadvert', 'socialshare' ] ) ) {
						$this->_add_controls( $controls );
					} else {
						$suffix = ($key === 'customfield' && !self::$hasPro) ? '_premium' : '';

						$this->start_controls_tabs( "{$key}_tabs{$suffix}" );

						// Tab General
						$this->start_controls_tab( "{$key}_general_tab", [ 'label' => esc_html__( 'General', 'content-views-query-and-display-post-page' ), ] );
						$this->_add_controls( $controls );
						$this->end_controls_tab();

						// Tab Style
						$this->start_controls_tab( "{$key}_style_tab", [ 'label' => esc_html__( 'Style', 'content-views-query-and-display-post-page' ), ] );
						$method_name = "_{$key}_style_controls";
						if ( method_exists( 'ContentViews_Elementor_Style_Controls', $method_name ) ) {
							$controls = ContentViews_Elementor_Style_Controls::$method_name( $localize_info, $prefix );
							$this->_add_controls( $controls );
						}
						$this->end_controls_tab();

						$this->end_controls_tabs();
					}

					$this->end_controls_section();
				}

				do_action( PT_CV_PREFIX_ . 'after_section', $this, $key );
			}
		}

		// Choose between layout or settings
		static function _another_tab() {
			return \Elementor\Controls_Manager::TAB_SETTINGS;
		}

		static function _wrapper_class() {
			return self::$hasPro ? '' : 'contentviews-control-premium';
		}

		// Get options
		static function _get_options( $option_name, $sub_key = null ) {
			if ( $sub_key ) {
				return isset( self::$localizeData[ 'data' ][ $option_name ][ $sub_key ] ) ? self::$localizeData[ 'data' ][ $option_name ][ $sub_key ] : [];
			} else {
				return isset( self::$localizeData[ 'data' ][ $option_name ] ) ? self::$localizeData[ 'data' ][ $option_name ] : [];
			}
		}

		// Check and get default option
		static function _get_default_val( $option_name, $sub_key = null ) {
			if ( $sub_key ) {
				return isset( self::$localizeData[ 'data' ][ $option_name ][ $sub_key ] ) ? self::_default_option( self::$localizeData[ 'data' ][ $option_name ][ $sub_key ] ) : '';
			} else {
				return isset( self::$localizeData[ 'data' ][ $option_name ] ) ? self::_default_option( self::$localizeData[ 'data' ][ $option_name ] ) : '';
			}
		}

		// Get default option from array
		static function _default_option( $arr ) {
			return PT_CV_Functions::array_get_first_key( $arr );
		}

		// Get localized data for control options
		static function _localize_data() {
			// Same environment as shortcode
			$GLOBALS[ 'cv_outside_gutenberg' ] = true;

			$localize_info	 = [ 'data' => ContentViews_Block_Common::get_data() ];
			$localize_info	 = apply_filters( PT_CV_PREFIX_ . 'block_localize_data', $localize_info );

			// Modify options
			$localize_info[ 'data' ][ 'border_styles' ] = array_merge( [ '' => __( 'Default' ), ], $localize_info[ 'data' ][ 'border_styles' ] );

			// Block [value=>, label=>]. Elementor [value=>label]
			$localize_info[ 'data' ][ 'meta_fields' ] = array_column( ContentViews_Block_Common::meta_list(), 'label', 'value' );

			// Block: value => CSS value. Elementor: only can use {{VALUE}} so use CSS value directly
			// 'top' : 'flex-start', 'middle' : 'center', 'bottom' : 'flex-end'
			$localize_info[ 'data' ][ 'ovlposi' ] = [
				'flex-start' => __( 'Top', 'content-views-query-and-display-post-page' ),
				'center'	 => __( 'Middle', 'content-views-query-and-display-post-page' ),
				'flex-end'	 => __( 'Bottom', 'content-views-query-and-display-post-page' ),
			];

			$localize_info[ 'data' ][ 'alignment' ] = [
				'left'	 => [
					'title'	 => __( 'Left', 'content-views-query-and-display-post-page' ),
					'icon'	 => 'eicon-text-align-left',
				],
				'center' => [
					'title'	 => __( 'Center', 'content-views-query-and-display-post-page' ),
					'icon'	 => 'eicon-text-align-center',
				],
				'right'	 => [
					'title'	 => __( 'Right', 'content-views-query-and-display-post-page' ),
					'icon'	 => 'eicon-text-align-right',
				],
			];

			// Differ from Block, make it similar to Pro
			if ( !self::$hasPro ) {
				$localize_info[ 'data' ][ 'manual_excerpt_options' ] = [
					''		 => __( 'Ignore manual excerpt', 'content-views-pro' ),
					'yes'	 => __( 'Use manual excerpt', 'content-views-pro' ),
				];
				$localize_info[ 'data' ][ 'html_excerpt_options' ]	 = [
					''		 => __( 'Strip all HTML tags', 'content-views-pro' ),
					'yes'	 => __( 'Allow some HTML tags (a, br, strong, em, strike, i, ul, ol, li)', 'content-views-pro' ),
				];
			}

			// Generate new key to set condition
			$arr	 = [];
			$notaxo	 = [];
			foreach ( $localize_info[ 'data' ][ 'post_types_vs_taxonomies' ] as $post_type => $taxonomies ) {
				if ( empty( $taxonomies ) ) {
					$notaxo[] = $post_type;
				}

				foreach ( $taxonomies as $taxonomy ) {
					if ( !isset( $arr[ $taxonomy ] ) ) {
						$arr[ $taxonomy ] = [];
					}
					$arr[ $taxonomy ][] = $post_type;
				}
			}
			$localize_info[ 'data' ][ 'taxonomies_vs_post_types' ]	 = $arr;
			$localize_info[ 'data' ][ 'notaxo_post_types' ]			 = $notaxo;


			return $localize_info;
		}

		// List of fields for Layout and Style tabs
		static function _fields_list() {
			return [
				'heading'		 => __( 'Heading Text', 'content-views-query-and-display-post-page' ),
				'item'			 => __( 'Item', 'content-views-query-and-display-post-page' ),
				'thumbnail'		 => '&emsp;&emsp;' . __( 'Featured Image', 'content-views-query-and-display-post-page' ),
				'title'			 => '&emsp;&emsp;' . __( 'Title', 'content-views-query-and-display-post-page' ),
				'topmeta'		 => '&emsp;&emsp;' . __( 'Top Meta', 'content-views-query-and-display-post-page' ),
				'customfield'	 => '&emsp;&emsp;' . __( 'Custom Field', 'content-views-query-and-display-post-page' ),
				'content'		 => '&emsp;&emsp;' . __( 'Content', 'content-views-query-and-display-post-page' ),
				'readmore'		 => '&emsp;&emsp;' . __( 'Read More', 'content-views-query-and-display-post-page' ),
				'bottom_meta'	 => '&emsp;&emsp;' . __( 'Bottom Meta', 'content-views-query-and-display-post-page' ),
				'pagination'	 => __( 'Pagination', 'content-views-query-and-display-post-page' ),
				'showadvert'	 => __( 'Show Advertisement', 'content-views-query-and-display-post-page' ),
				'socialshare'	 => __( 'Social Share', 'content-views-query-and-display-post-page' ),
			];
		}

		// Conditions for Product type
		static function _woo_condition() {
			return [
				'relation'	 => 'or',
				'terms'		 => [
					[
						'name'		 => 'postType',
						'operator'	 => '===',
						'value'		 => 'product',
					],
					[
						'relation'	 => 'and',
						'terms'		 => [
							[ 'name' => 'postType', 'operator' => '===', 'value' => 'any', ],
							[ 'name' => 'multipostType', 'operator' => 'contains', 'value' => 'product', ],
						],
					],
				],
			];
		}

		// Custom widget name to identify
		public function _get_widgetName() {

		}

		// Get value of some special controls
		public function _get_special_control( $control_name, $default = '' ) {
			$static_controls = $this->_layout_static_controls();
			$static_val		 = isset( $static_controls[ $control_name ] ) ? $static_controls[ $control_name ][ 'default' ] : $default;

			$modified_controls = $this->_layout_custom();
			return isset( $modified_controls[ $control_name ] ) ? $modified_controls[ $control_name ][ 'default' ] : $static_val;
		}

		// Get options (but exclude some values for some controls)
		public function _get_option_from( $option_key, $control_name ) {
			$options = ContentViews_Elementor_Widget::_get_options( $option_key );
			if ( $control_name === 'multipostType' ) {
				unset( $options[ 'any' ] );
			}
			if ( $control_name === 'pagingStyle' ) {
				if ( in_array( $this->_get_widgetName(), [ 'overlay2', 'overlay3', 'overlay4', 'overlay5', 'overlay7', 'overlay8' ] ) ) {
					unset( $options[ 'loadmore' ] );
					unset( $options[ 'infinite' ] );
				}

				if ( $this->_get_widgetName() === 'timeline' ) {
					unset( $options[ 'regular' ] );
				}
			}
			return $options;
		}

		// Generate upgrade link
		public function _prolink( $control_name = '', $text = 'PRO', $path = null, $wrap = true ) {
			if ( self::$hasPro ) {
				return '';
			}
			$path	 = empty( $path ) ? 'https://www.contentviewspro.com/' : $path;
			$widget	 = $this->get_title();
			$url	 = "{$path}?utm_source=elementorWidget&utm_medium={$widget}&utm_campaign={$control_name}";
			$link	 = "<a href='" . esc_url( $url ) . "' target='_blank'>" . esc_html( $text ) . "</a>";
			return $wrap ? "<span class='contentviews-pro-label'>$link</span>" : $link;
		}

		/**
		 * ############################################################
		 * ---------- QUERY
		 * ############################################################
		 */
		public function _query_controls( $localize_info ) {



			$this->add_control(
			"postType", [
				'label'		 => __( 'Content type', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::SELECT,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'post_types' ),
				'default'	 => 'post',
			]
			);


			if ( !self::$hasPro ) {


				$this->add_control(
				"__postTypePro", [
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw'				 => __( 'Query custom post type (product, event, etc.) and media file', 'content-views-query-and-display-post-page' ) . ' >> ' . $this->_prolink( "__postTypePro", __( 'get PRO', 'content-views-query-and-display-post-page' ), null, false ),
					'content_classes'	 => 'contentviews-pro-section',
				]
				);
			}


			$this->add_control(
			"multipostType", [
				'label'		 => "",
				'type'		 => \Elementor\Controls_Manager::SELECT2,
				'options'	 => $this->_get_option_from( 'post_types', 'multipostType' ),
				'default'	 => [],
				'multiple'	 => true,
				'label_block' => true,
				'description' => __( 'Leave empty to include all post types', 'content-views-pro' ),
				'condition' => [
					'postType' => 'any',
				],
			]
			);


			$this->add_control(
			"wooPick", [
				'label'		 => __( 'WooCommerce Hot Pick', 'content-views-query-and-display-post-page' ) . $this->_prolink( "wooPick" ),
				'type'		 => \Elementor\Controls_Manager::SELECT,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'woo_pick' ),
				'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'woo_pick' ),
				'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
				'condition' => [
					'postType' => 'product',
				],
			]
			);


			$this->add_control(
			"postsPerPage", [
				'label'	 => __( 'Posts per page', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::NUMBER,
				'default' => $this->_get_special_control( 'postsPerPage', 6 ),
				'min' => 1,
				'description' => __( 'Enable pagination to have multiple pages', "content-views-query-and-display-post-page" ),
				'conditions' => [
					'terms' => [
						[
							'name'		 => 'isSpec',
							'operator'	 => '!==',
							'value'		 => '1',
						],
						[
							'name'		 => 'viewType',
							'operator'	 => '!==',
							'value'		 => 'scrollable',
						],
						[
							'name'		 => 'blockName',
							'operator'	 => '!in',
							'value'		 => [ 'overlay7', 'overlay8' ],
						],
					],
				],
			]
			);


			$this->add_control(
			"offset", [
				'label'	 => __( 'Offset', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::NUMBER,
			]
			);


			$this->add_control(
			"__headingTaxo", [
				'label'	 => __( 'Taxonomy', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
			);


			$this->add_control(
			"taxonomyRelation", [
				'label'		 => __( 'Taxonomy Relation', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::SELECT,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'taxorelation' ),
				'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'taxorelation' ),
				'conditions' => [
					'terms' => [
						[
							'name'		 => 'postType',
							'operator'	 => '!in',
							'value'		 => (array) $localize_info[ 'data' ][ 'notaxo_post_types' ],
						],
					],
				],
			]
			);

			$this->add_control(
			"noTaxonomyForThis", [
				'label'		 => '',
				'type'		 => \Elementor\Controls_Manager::RAW_HTML,
				'raw'		 => esc_html__( 'There is no taxonomy for selected content type', 'content-views-query-and-display-post-page' ),
				'conditions' => [
					'terms' => [
						[
							'name'		 => 'postType',
							'operator'	 => 'in',
							'value'		 => (array) $localize_info[ 'data' ][ 'notaxo_post_types' ],
						],
					],
				],
			]
			);


			foreach ( $localize_info[ 'data' ][ 'taxonomies' ] as $tname => $tlabel ) {
				if ( empty( $localize_info[ 'data' ][ 'terms' ][ $tname ] ) ) {
					//continue;
				}


				$this->add_control(
				"{$tname}__terms", [
					'label'		 => "+ $tlabel",
					'type'		 => \Elementor\Controls_Manager::SELECT2,
					'options'	 => $localize_info[ 'data' ][ 'terms' ][ $tname ],
					'multiple' => true,
					'label_block' => true,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'postType',
								'operator'	 => 'in',
								'value'		 => ContentViews_Elementor_Widget::_get_options( 'taxonomies_vs_post_types', $tname ),
							],
						],
					],
				]
				);

				$this->add_control(
				"{$tname}__operator", [
					'label'		 => "Operator",
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => $localize_info[ 'data' ][ 'taxooperator' ],
					'default'	 => ContentViews_Elementor_Widget::_default_option( $localize_info[ 'data' ][ 'taxooperator' ] ),
					'classes'	 => 'contentviews-control-indent',
					'conditions' => [
						'terms' => [
							[
								'name'		 => "{$tname}__terms",
								'operator'	 => '!==',
								'value'		 => '',
							],
							[
								'name'		 => 'postType',
								'operator'	 => 'in',
								'value'		 => ContentViews_Elementor_Widget::_get_options( 'taxonomies_vs_post_types', $tname ),
							],
						],
					],
				]
				);

				$this->add_control(
				"{$tname}__popover", [
					'label'		 => __( 'Live Filter', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::POPOVER_TOGGLE,
					'classes'	 => 'contentviews-control-indent',
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'postType',
								'operator'	 => 'in',
								'value'		 => ContentViews_Elementor_Widget::_get_options( 'taxonomies_vs_post_types', $tname ),
							],
						],
					],
				]
				);
				$this->start_popover();
				$taxolf_controls = $this->_taxonomy_livefilter_controls( $tname );
				$this->_add_controls( $taxolf_controls );
				$this->end_popover();
			}

			if ( !self::$hasPro ) {


				$this->add_control(
				"__taxonomyPro", [
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw'				 => __( '+ Query by custom taxonomy', 'content-views-query-and-display-post-page' ) . ' >> ' . $this->_prolink( "__taxonomyPro", __( 'get PRO', 'content-views-query-and-display-post-page' ), null, false ),
					'content_classes'	 => 'contentviews-pro-section',
				]
				);
			}


			$this->add_control(
			"__includeExclude", [
				'label'	 => __( 'Include/Exclude', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
			);


			$this->add_control(
			"parentPage", [
				'label'	 => __( 'Parent Page', 'content-views-query-and-display-post-page' ),
				'type'	 => 'contentviews-creatable',
				'multiple' => false,
				'description' => __( 'Select parent page to show its children', 'content-views-query-and-display-post-page' ),
				'condition' => [
					'postType' => 'page',
				],
			]
			);


			$this->add_control(
			"includeId", [
				'label'	 => __( 'Posts Include Only', 'content-views-query-and-display-post-page' ),
				'type'	 => 'contentviews-creatable',
				'description' => __( 'Type to search by post title', 'content-views-query-and-display-post-page' ),
			]
			);


			$this->add_control(
			"excludeId", [
				'label'	 => __( 'Posts Exclude', 'content-views-query-and-display-post-page' ),
				'type'	 => 'contentviews-creatable',
				'description' => __( 'Type to search by post title', 'content-views-query-and-display-post-page' ),
			]
			);


			$this->add_control(
			"excludeCurrent", [
				'label'	 => __( 'Exclude current post', 'content-views-pro' ) . $this->_prolink( "excludeCurrent" ),
				'type'	 => \Elementor\Controls_Manager::SWITCHER,
				'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
			]
			);


			$this->add_control(
			"excludeProtected", [
				'label'	 => __( 'Exclude password protected posts', 'content-views-pro' ) . $this->_prolink( "excludeProtected" ),
				'type'	 => \Elementor\Controls_Manager::SWITCHER,
				'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
			]
			);

			if ( self::$hasPro ) {
				$this->add_control(
				"excludeChild", [
					'label'	 => __( 'Exclude children posts', 'content-views-pro' ) . $this->_prolink( "excludeChild" ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
				]
				);
			}
			if ( ContentViews_Elementor_Widget::_get_options( 'sticky_options' ) ) {
				$this->add_control(
				"stickyPost", [
					'label'		 => __( 'Sticky Post', 'content-views-query-and-display-post-page' ) . $this->_prolink( "stickyPost" ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'sticky_options' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'sticky_options' ),
					'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
				]
				);
			}

			$this->add_control(
			"__heading13", [
				'label'	 => __( 'Sort', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
			);


			$this->add_control(
			"orderby", [
				'label'		 => __( 'Order by', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::SELECT,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'orderby' ),
				'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'orderby' ),
			]
			);


			$this->add_control(
			"order", [
				'label'		 => __( 'Order', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::SELECT,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'orders' ),
				'default'	 => 'desc',
			]
			);

			if ( self::$hasPro ) {
				$this->_customfield_sort_controls();
			} else {

				$this->add_control(
				"__proSortIntro", [
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw'				 => __( 'Sort by custom fields, random order, comment count', 'content-views-query-and-display-post-page' ) . ' >> ' . $this->_prolink( "__proSortIntro", __( 'get PRO', 'content-views-query-and-display-post-page' ), null, false ),
					'content_classes'	 => 'contentviews-pro-section',
				]
				);
			}


			// Start Popover
			$this->add_control(
			"livesort__popover", [
				'label'		 => __( 'Live Filter (Sort)', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'classes'	 => 'contentviews-control-indent contentviews-control-mg0',
			]
			);
			$this->start_popover();

			if ( self::$hasPro ) {

				$this->add_control(
				"lfSortLabel", [
					'label'	 => __( 'Label', 'content-views-query-and-display-post-page' ) . $this->_prolink( "lfSortLabel" ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'Sort by', 'content-views-pro' ),
					'label_block' => true,
					'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
				]
				);


				$this->add_control(
				"lfSortDefault", [
					'label'	 => __( 'Change "Default" text', 'content-views-query-and-display-post-page' ) . $this->_prolink( "lfSortDefault" ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'label_block' => true,
					'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
				]
				);


				$this->add_control(
				"lfSortOpts", [
					'label'		 => __( 'Common Options', 'content-views-query-and-display-post-page' ) . $this->_prolink( "lfSortOpts" ),
					'type'		 => \Elementor\Controls_Manager::SELECT2,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lfsort_options' ),
					'default'	 => '',
					'multiple'	 => true,
					'label_block' => true,
					'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
				]
				);


				$this->add_control(
				"lfSortText", [
					'label'	 => "" . $this->_prolink( "lfSortText" ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'label_block' => true,
					'classes'		 => ContentViews_Elementor_Widget::_wrapper_class(),
					'description'	 => __( "Customize texts of above options. Separate texts by comma", "content-views-query-and-display-post-page" ),
				]
				);
			} else {
				$intro_controls = [
					"lfSortIntro" => [
						'label'				 => __( 'Show sort options to visitors', 'content-views-pro' ),
						'type'				 => \Elementor\Controls_Manager::SWITCHER,
						'_cv_pro_control'	 => true,
						'description'		 => $this->_prolink( "lfSortIntro", __( "See Demo", "content-views-query-and-display-post-page" ), 'https://contentviewspro.com/demo/faceted-search-live-filter/', false ),
					]
				];
				$this->_add_controls( $intro_controls );
			}

			// End Popover
			$this->end_popover();


			$this->add_control(
			"__heading14", [
				'label'	 => __( 'Author', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
			);


			$this->add_control(
			"author", [
				'label'		 => __( 'Author In', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::SELECT2,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'authors' ),
				'default'	 => '',
				'multiple'	 => true,
			]
			);


			$this->add_control(
			"authorNot", [
				'label'		 => __( 'Author Not In', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::SELECT2,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'authors' ),
				'default'	 => '',
				'multiple'	 => true,
			]
			);

			if ( ContentViews_Elementor_Widget::_get_options( 'author_current' ) ) {
				$this->add_control(
				"authorCurrent", [
					'label'		 => __( 'For Logged In User', 'content-views-query-and-display-post-page' ) . $this->_prolink( "authorCurrent" ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'author_current' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'author_current' ),
					'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
				]
				);
			}

			$this->add_control(
			"__headingKeyword", [
				'label'	 => __( 'Keyword', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
			);


			$this->add_control(
			"keyword", [
				'label'	 => "",
				'type'	 => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( 'Enter keyword to searching for posts', 'content-views-query-and-display-post-page' ) . '. ' . __( 'It will search in post title, excerpt, content.', 'content-views-query-and-display-post-page' ),
			]
			);


			// Start Popover
			$this->add_control(
			"livesearch__popover", [
				'label'		 => __( 'Live Filter (Search)', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'classes'	 => 'contentviews-control-indent contentviews-control-mg0',
			]
			);
			$this->start_popover();

			if ( self::$hasPro ) {

				$this->add_control(
				"searchLfEnable", [
					'label'	 => __( 'Show search field to visitors', 'content-views-pro' ) . $this->_prolink( "searchLfEnable" ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'classes'		 => ContentViews_Elementor_Widget::_wrapper_class(),
					'description'	 => $this->_prolink( "searchLfEnable", __( "See Demo", "content-views-query-and-display-post-page" ), 'https://contentviewspro.com/demo/faceted-search-live-filter/', false ),
				]
				);


				$this->add_control(
				"lfSearchLabel", [
					'label'	 => __( 'Label', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'Search', 'content-views-pro' ),
					'condition' => [
						'searchLfEnable' => 'yes',
					],
				]
				);


				$this->add_control(
				"lfSearchHolder", [
					'label'	 => __( 'Placeholder', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'condition' => [
						'searchLfEnable' => 'yes',
					],
				]
				);
			} else {
				$intro_controls = [
					"lfSearchIntro" => [
						'label'				 => __( 'Show search field to visitors', 'content-views-pro' ),
						'type'				 => \Elementor\Controls_Manager::SWITCHER,
						'_cv_pro_control'	 => true,
						'description'		 => $this->_prolink( "lfSearchIntro", __( "See Demo", "content-views-query-and-display-post-page" ), 'https://contentviewspro.com/demo/faceted-search-live-filter/', false ),
					]
				];
				$this->_add_controls( $intro_controls );
			}

			// End Popover
			$this->end_popover();


			$this->add_control(
			"__heading17", [
				'label'	 => __( 'Custom Field', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
			);

			if ( self::$hasPro ) {
				$this->_customfield_query_controls();
			} else {

				$this->add_control(
				'__ctfQueryDemo', [
					'label'	 => esc_html__( 'Select custom field to query', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::REPEATER,
					'fields' => [],
				]
				);



				$this->add_control(
				"__ctfQueryIntro", [
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw'				 => __( 'Query by custom fields', 'content-views-query-and-display-post-page' ) . ' >> ' . $this->_prolink( "__ctfQueryIntro", __( 'get PRO', 'content-views-query-and-display-post-page' ), null, false ),
					'content_classes'	 => 'contentviews-pro-section',
				]
				);
			}


			$this->add_control(
			"__headingDate", [
				'label'	 => __( 'Published Date', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
			);


			$this->add_control(
			"filterDate", [
				'label'		 => "",
				'type'		 => \Elementor\Controls_Manager::SELECT,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'date_options' ),
				'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'date_options' ),
				'label_block' => true,
				'classes' => ContentViews_Elementor_Widget::_wrapper_class(),
			]
			);


			if ( !self::$hasPro ) {


				$this->add_control(
				"__queryDate", [
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw'				 => __( 'Query by published date', 'content-views-query-and-display-post-page' ) . ' >> ' . $this->_prolink( "__queryDate", __( 'get PRO', 'content-views-query-and-display-post-page' ), null, false ),
					'content_classes'	 => 'contentviews-pro-section',
				]
				);
			}


			$this->add_control(
			"postDate", [
				'label'	 => __( 'Select Date', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::DATE_TIME,
				'condition' => [
					'filterDate' => 'custom_date',
				],
			]
			);


			$this->add_control(
			"postDateFrom", [
				'label'	 => __( 'From Date', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::DATE_TIME,
				'condition' => [
					'filterDate' => 'custom_time',
				],
			]
			);


			$this->add_control(
			"postDateTo", [
				'label'	 => __( 'To Date', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::DATE_TIME,
				'condition' => [
					'filterDate' => 'custom_time',
				],
			]
			);


			$this->add_control(
			"postYear", [
				'label'	 => __( 'Select Year', 'content-views-query-and-display-post-page' ),
				'type'	 => \Elementor\Controls_Manager::NUMBER,
				'default' => current_time( 'Y' ),
				'condition' => [
					'filterDate' => 'custom_year',
				],
			]
			);


			$this->add_control(
			"postMonth", [
				'label'		 => __( 'Select Month', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::SELECT,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'month_options' ),
				'default'	 => current_time( 'n' ),
				'condition' => [
					'filterDate' => 'custom_month',
				],
			]
			);
		}

		// Add controls from array
		public function _add_controls( $controls ) {
			// get modified controls
			$modified_controls = $this->_layout_custom();
			foreach ( $controls as $control_name => $control_settings ) {
				// modify default value of control by widget
				if ( isset( $modified_controls[ $control_name ] ) ) {
					$control_settings = array_replace( $control_settings, $modified_controls[ $control_name ] );
				}

				// Don't add control if doesn't match condition
				if ( isset( $control_settings[ '_cv_show_when' ] ) && !$control_settings[ '_cv_show_when' ] ) {
					continue;
				}

				// Query controls add class and label directly because it use add_control directly to add Terms list
				// Other controls: add class and label here
				if ( isset( $control_settings[ '_cv_pro_control' ] ) ) {
					$control_settings[ 'classes' ]	 = ContentViews_Elementor_Widget::_wrapper_class();
					$control_settings[ 'label' ]	 .= $this->_prolink( $control_name );
				}

				if ( !empty( $control_settings[ '_cv_group_control' ] ) ) {
					$group_type = null;
					if ( $control_settings[ '_cv_group_control' ] === 'typography' || $control_settings[ '_cv_group_control' ] === 'typographysm' ) {
						$group_type = \Elementor\Group_Control_Typography::get_type();
					}
					if ( $control_settings[ '_cv_group_control' ] === 'box_shadow' ) {
						$group_type = \Elementor\Group_Control_Box_Shadow::get_type();
					}
					if ( $control_settings[ '_cv_group_control' ] === 'color_gradient_picker' ) {
						$group_type						 = \Elementor\Group_Control_Background::get_type();
						$control_settings[ 'types' ]	 = [ 'classic', 'gradient' ];
						$control_settings[ 'exclude' ]	 = [ 'image' ];
					}
					if ( $group_type ) {
						$this->add_group_control( $group_type, $control_settings );
					}
				} else if ( !empty( $control_settings[ '_cv_responsive' ] ) ) {
					$this->add_responsive_control( $control_name, $control_settings );
				} else {
					$this->add_control( $control_name, $control_settings );
				}
			}
		}

		//  All layout controls
		public function _layout_controls( $localize_info, $prefix ) {
			$controls = array_merge( $this->_layout_static_controls(), $this->_layout_general_controls( $localize_info, $prefix ) );
			$this->_add_controls( $controls );
		}

		// Custom layout controls for each widget
		public function _layout_custom() {
			return [];
		}

		// Fixed/static layout controls
		public function _layout_static_controls() {
			return [
				"isElementorWidget"	 => [
					'type'		 => \Elementor\Controls_Manager::HIDDEN,
					'default'	 => 'yes',
				],
				// from blocks
				"blockName"		 => [
					'type'		 => \Elementor\Controls_Manager::HIDDEN,
					'default'	 => $this->_get_widgetName(),
				],
				"viewType"		 => [
					'type'		 => \Elementor\Controls_Manager::HIDDEN,
					'default'	 => 'blockgrid',
				],
				"layoutFormat"	 => [
					'type'		 => \Elementor\Controls_Manager::HIDDEN,
					'default'	 => '1-col',
				],
				"hasOne"		 => [
					'type'		 => \Elementor\Controls_Manager::HIDDEN,
					'default'	 => '',
				],
				"isSpec"		 => [
					'type'		 => \Elementor\Controls_Manager::HIDDEN,
					'default'	 => '',
				],
				"formatWrap"	 => [
					'type'		 => \Elementor\Controls_Manager::HIDDEN,
					'default'	 => '',
				],
				"zigzag"			 => [
					'type'		 => \Elementor\Controls_Manager::HIDDEN,
					'default'	 => '',
				],
//				"hasLF"				 => [
//					'type'		 => \Elementor\Controls_Manager::HIDDEN,
//					'default'	 => '',
//				],
				"sameAs"			 => [
					'type'		 => \Elementor\Controls_Manager::HIDDEN,
					'default'	 => '',
				],
			];
		}

		// Fields switcher controls
		public function _fields_controls() {
			$condition_woo = self::_woo_condition();

			$default_woo = self::$hasPro ? self::$switchOn : self::$switchOff;

			$fields	 = [
				[ 'showHeading', self::$switchOn, __( 'Heading Text', 'content-views-query-and-display-post-page' ) ],
				[ 'showPagination', self::$switchOff, __( 'Pagination', 'content-views-query-and-display-post-page' ) ],
				[ 'separator1' ],
				[ 'showThumbnail', self::$switchOn, __( 'Featured Image', 'content-views-query-and-display-post-page' ) ],
				[ 'showTitle', self::$switchOn, __( 'Title', 'content-views-query-and-display-post-page' ) ],
				[ 'showTaxonomy', self::$switchOn, __( 'Top Meta', 'content-views-query-and-display-post-page' ) ],
				[ 'showCustomField', self::$switchOff, __( 'Custom Field', 'content-views-query-and-display-post-page' ), [ 'ispremium' => true ] ],
				[ 'showContent', self::$switchOn, __( 'Content', 'content-views-query-and-display-post-page' ) ],
				[ 'showReadmore', self::$switchOn, __( 'Read More', 'content-views-query-and-display-post-page' ) ],
				[ 'showMeta', self::$switchOn, __( 'Bottom Meta', 'content-views-query-and-display-post-page' ) ],

				[ 'showWooPrice', $default_woo, __( 'Woo - Price', 'content-views-query-and-display-post-page' ), [ 'ispremium' => true, 'depend' => $condition_woo ] ],
				[ 'showWooATC', $default_woo, __( 'Woo - Add To Cart', 'content-views-query-and-display-post-page' ), [ 'ispremium' => true, 'depend' => $condition_woo ] ],
			];

			$arr = [];

			foreach ( $fields as $field_setting ) {
				$this_setting = $field_setting[ 0 ] === 'separator1' ? [
					'type' => \Elementor\Controls_Manager::DIVIDER,
				] : [
					'label'		 => $field_setting[ 2 ],
					'type'		 => \Elementor\Controls_Manager::SWITCHER,
					'default'	 => $field_setting[ 1 ],
					'label_on'	 => __( 'Show', 'content-views-query-and-display-post-page' ),
					'label_off'	 => __( 'Hide', 'content-views-query-and-display-post-page' ),
				];
				if ( isset( $field_setting[ 3 ] ) ) {
					if ( isset( $field_setting[ 3 ][ 'depend' ] ) ) {
						$this_setting[ 'conditions' ] = $field_setting[ 3 ][ 'depend' ];
					}
					if ( isset( $field_setting[ 3 ][ 'ispremium' ] ) ) {
						$this_setting[ '_cv_pro_control' ] = true;
					}
					if ( isset( $field_setting[ 3 ][ 'desc_text' ] ) ) {
						$this_setting[ 'description' ] = $field_setting[ 3 ][ 'desc_text' ];
					}
					if ( isset( $field_setting[ 3 ][ 'separate' ] ) ) {
						$this_setting[ 'separator' ] = $field_setting[ 3 ][ 'separate' ];
					}
				}
				$arr[ $field_setting[ 0 ] ] = $this_setting;
			}


			$arr[ '__headingfieldsPosi' ]	 = [
				'label'		 => __( 'Fields Position', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::HEADING,
				'separator'	 => 'before',
			];
			if ( self::$hasPro ) {
				$arr = apply_filters( PT_CV_PREFIX_ . 'fields_position_controls', $arr );
			} else {
				$arr['__fieldPositionIntro'] = [
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw'				 => __( 'Change position of fields (<em>for example: show Title above Image</em>)', 'content-views-query-and-display-post-page' ) . ' >> ' . $this->_prolink( "__fieldPositionIntro", __( 'get PRO', 'content-views-query-and-display-post-page' ), null, false ),
					'content_classes'	 => 'contentviews-pro-section',
				];
			}

			$this->_add_controls( $arr );
		}

		// Live filter for taxonomy controls
		public function _taxonomy_livefilter_controls( $tname ) {

			return [
				"{$tname}__LfEnable" =>
				[
					'label'	 => __( 'Show as filters to visitors', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control'	 => true,
					'description'		 => $this->_prolink( "$tname-LfEnable", __( "See Demo", "content-views-query-and-display-post-page" ), 'https://contentviewspro.com/demo/faceted-search-live-filter/', false ),
				],
				"{$tname}__LfType" =>
				[
					'label'		 => __( 'Filter Type', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'types' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'types' ),
					'condition' => [
						"{$tname}__LfEnable" => 'yes',
					],
				],
				"{$tname}__LfBehavior" =>
				[
					'label'		 => __( 'Behavior', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'behavior' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'behavior' ),
					'condition' => [
						"{$tname}__LfEnable" => 'yes',
						"{$tname}__LfType"	 => 'checkbox',
					],
				],
				"{$tname}__LfLabel" =>
				[
					'label'	 => __( 'Label', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'description' => __( "Enter a space to remove label", "content-views-query-and-display-post-page" ),
					'condition' => [
						"{$tname}__LfEnable" => 'yes',
					],
				],
				"{$tname}__LfDefault" =>
				[
					'label'	 => __( 'The "All" text', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'condition' => [
						"{$tname}__LfEnable" => 'yes',
						"{$tname}__LfType"	 => [ 'radio', 'dropdown', 'button', '' ],
					],
				],
				"{$tname}__LfOrder" =>
				[
					'label'		 => __( 'Order By', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'orderby' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'orderby' ),
					'condition' => [
						"{$tname}__LfEnable" => 'yes',
					],
				],
				"{$tname}__LfOrderFlag" =>
				[
					'label'		 => "",
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'orderflag' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'orderflag' ),
					'condition' => [
						"{$tname}__LfEnable" => 'yes',
					],
				],
				"{$tname}__LfCount" =>
				[
					'label'	 => __( 'Show posts count', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						"{$tname}__LfEnable" => 'yes',
					],
				],
				"{$tname}__LfNoEmpty" =>
				[
					'label'	 => __( 'Hide values have no post', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						"{$tname}__LfEnable" => 'yes',
					],
				],
				"{$tname}__LfRequire" =>
				[
					'label'	 => __( 'Hide posts that do not have this taxonomy', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						"{$tname}__LfEnable" => 'yes',
					],
				],
			];
		}

		// Custom Field Sort controls
		public function _customfield_sort_controls() {
			do_action( PT_CV_PREFIX_ . 'ctf_sort_controls_register', $this );
		}

		// Custom Field Query controls
		public function _customfield_query_controls() {
			do_action( PT_CV_PREFIX_ . 'ctf_query_controls_register', $this );
		}

		/**
		 * ############################################################
		 * ---------- LAYOUT
		 * ############################################################
		 */
		public function _layout_general_controls( $localize_info, $prefix ) {

			return [
				"whichLayout" =>
				[
					'label'		 => __( 'Select Layout', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => isset( $localize_info[ 'data' ][ 'pre_layouts' ][ $this->_get_widgetName() ] ) ? $localize_info[ 'data' ][ 'pre_layouts' ][ $this->_get_widgetName() ] : [],
					'default'	 => 'layout1',
					'label_block' => true,
					'classes' => 'contentviews-whichLayout contentviews-' . $this->_get_widgetName() . (self::$hasPro ? '' : ' contentviews-pro-require') . (isset( $localize_info[ 'data' ][ 'pre_layouts' ][ $this->_get_widgetName() ] ) ? '' : ' contentviews-nolayout'),
				],
				"__layoutPro" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw'				 => __( 'Access to all PRO layouts', 'content-views-query-and-display-post-page' ) . ' >> ' . $this->_prolink( "__layoutPro", __( 'get PRO', 'content-views-query-and-display-post-page' ), null, false ),
					'content_classes'	 => 'contentviews-pro-section',
					'_cv_show_when' => !self::$hasPro,
				],
				"slideNum" =>
				[
					'label'	 => __( 'Slides', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range' => [
						'px' => [
							'min'	 => 1,
							'max'	 => 20,
							'step'	 => 1,
						],
					],
					'condition' => [
						'viewType' => 'scrollable',
					],
				],
				(($this->_get_special_control( 'hasOne' ) !== '1' && !in_array( $this->_get_special_control( 'viewType' ), [ 'collapsible', 'timeline' ] )) || $this->_get_special_control( 'viewType' ) === 'onebig') ? "showColumnsControl" : "hideColumnsControl" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::HIDDEN,
				],
				"columns" =>
				[
					'label'		 => $this->_get_special_control( 'viewType' ) === 'onebig' ? __( 'Others Item Columns', 'content-views-query-and-display-post-page' ) : __( 'Columns', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => [ '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', ],
					'_cv_responsive' => true,
					'default'		 => '3',
					'mobile_default' => '1',
					'selectors' => in_array( $this->_get_special_control( 'viewType' ), [ 'overlaygrid', 'blockgrid', 'onebig' ] ) ? [
						"{{WRAPPER}} {$prefix}page" . ($this->_get_special_control( 'blockName' ) === 'onebig2' ? ' .small-items' : '') => ($this->_get_special_control( 'sameAs' ) === 'overlay6') ? '' : 'grid-template-columns: repeat({{VALUE}}, 1fr)',
					] : [],
				],
				"rowNum" =>
				[
					'label'	 => __( 'Rows', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range' => [
						'px' => [
							'min'	 => 1,
							'max'	 => 4,
							'step'	 => 1,
						],
					],
					'_cv_pro_control' => true,
					'condition' => [
						'viewType' => 'scrollable',
					],
				],
				"hetargetHeight" =>
				[
					'label'	 => __( 'Row Height', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range'		 => [
						'px' => [
							'min'	 => 0,
							'max'	 => 1000,
							'step'	 => 1,
						],
					],
					'size_units' => [ 'px', 'em', 'rem', '%', 'vh' ],
					'_cv_responsive' => true,
					'conditions' => [
						'relation'	 => 'and',
						'terms'		 => [
							[
								'relation'	 => 'or',
								'terms'		 => [
									[
										'name'		 => 'isSpec',
										'operator'	 => '===',
										'value'		 => '1',
									],
									[
										'name'		 => 'blockName',
										'operator'	 => 'in',
										'value'		 => [ 'overlay1', 'overlay6', 'overlay7', 'overlay8' ],
									],
								],
							],
							[
								'name'		 => 'overlaid',
								'operator'	 => '===',
								'value'		 => 'yes',
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}page"						 => 'grid-auto-rows: {{SIZE}}{{UNIT}}',
						"{{WRAPPER}} .overlay6 {$prefix}page"			 => 'grid-template-rows: calc({{SIZE}}{{UNIT}}*1.5);',
						"{{WRAPPER}} .overlay5.layout2 {$prefix}page"	 => 'grid-auto-rows: calc({{SIZE}}{{UNIT}}/var(--rowspan,2));',
					],
				],
				"gridGap" =>
				[
					'label'	 => __( 'Gap', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range' => [
						'px' => [
							'min'	 => 0,
							'max'	 => 100,
							'step'	 => 1,
						],
					],
					'_cv_responsive' => true,
					'default' => [ 'size' => 20, 'unit' => 'px', ],
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'viewType',
								'operator'	 => 'in',
								'value'		 => [ 'overlaygrid', 'blockgrid', 'onebig' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}page, {{WRAPPER}} {$prefix}view.onebig2 {$prefix}page .small-items" => 'grid-gap: {{SIZE}}{{UNIT}}',
					],
				],
				"onePosition" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::HIDDEN,
					'condition' => [
						'viewType' => 'onebig',
					],
				],
				"swapPosition" =>
				[
					'label'	 => __( 'Swap Position', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control' => true,
					'condition' => [
						'blockName' => 'onebig2',
					],
				],
				"oneWidth" =>
				[
					'label'		 => __( 'Big Item Width', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'onewidth' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'onewidth' ),
					'_cv_pro_control' => true,
					'condition' => [
						'blockName' => 'onebig2',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}view.swap-position .pt-cv-page"		 => 'grid-template-columns: auto {{VALUE}}',
						"{{WRAPPER}} {$prefix}view:not(.swap-position) .pt-cv-page"	 => 'grid-template-columns: {{VALUE}} auto',
					],
				],
				"scrollNav" =>
				[
					'label'	 => __( 'Show navigation', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'viewType' => 'scrollable',
					],
				],
				"scrollIndi" =>
				[
					'label'	 => __( 'Show indicator', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'viewType' => 'scrollable',
					],
				],
				"scrollBelow" =>
				[
					'label'	 => __( 'Show text below image', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control' => true,
					'condition' => [
						'viewType' => 'scrollable',
					],
				],
				"scrollAuto" =>
				[
					'label'	 => __( 'Automatic cycle', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control' => true,
					'condition' => [
						'viewType' => 'scrollable',
					],
				],
				"scrollInterval" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range' => [
						'px' => [
							'min'	 => 1,
							'max'	 => 10,
							'step'	 => 1,
						],
					],
					'_cv_pro_control' => true,
					'condition' => [
						'viewType'	 => 'scrollable',
						'scrollAuto' => 'yes',
					],
				],
				"openFirst" =>
				[
					'label'	 => __( 'Open the first item by default', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'viewType' => 'collapsible',
					],
				],
				"openAll" =>
				[
					'label'	 => __( 'Open all items', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control' => true,
					'condition' => [
						'viewType' => 'collapsible',
					],
				],
				"pinNoBox" =>
				[
					'label'	 => __( 'Remove the box shadow', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'viewType' => 'pinterest',
					],
				],
				"pinNoBd" =>
				[
					'label'	 => __( 'Remove the border between fields', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'viewType' => 'pinterest',
					],
				],
				"timeDistance" =>
				[
					'label'	 => __( 'Increase vertical space to ensure posts are shown in correct order', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'viewType' => 'timeline',
					],
				],
				"timeSimulate" =>
				[
					'label'	 => __( 'Use fixed structure (to simulate the Facebook Timeline item)', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'viewType' => 'timeline',
					],
				],
				"alignment" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => 'left',
					'selectors' => [
						"{{WRAPPER}} {$prefix}view" => 'text-align: {{VALUE}};'
					],
				],
				"linkTarget" =>
				[
					'label'		 => __( 'Open Item In', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'target_options' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'target_options' ),
				],
				"windowWidth" =>
				[
					'label'	 => __( 'Window Width (PX)', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::NUMBER,
					'default' => 800,
					'min'	 => 300,
					'max'	 => 1500,
					'step'	 => 5,
					'condition' => [
						'linkTarget' => 'pt-cv-window',
					],
				],
				"windowHeight" =>
				[
					'label'	 => __( 'Window Height (PX)', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::NUMBER,
					'default' => 600,
					'min'	 => 300,
					'max'	 => 1500,
					'step'	 => 5,
					'condition' => [
						'linkTarget' => 'pt-cv-window',
					],
				],
				"lbWidth" =>
				[
					'label'	 => __( 'Lightbox Width (%)', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::NUMBER,
					'default' => 80,
					'min'	 => 50,
					'max'	 => 100,
					'step'	 => 5,
					'condition' => [
						'linkTarget' => 'pt-cv-lightbox',
					],
				],
				"lbHeight" =>
				[
					'label'	 => __( 'Lightbox Height (%)', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::NUMBER,
					'default' => 80,
					'min'	 => 50,
					'max'	 => 100,
					'step'	 => 5,
					'condition' => [
						'linkTarget' => 'pt-cv-lightbox',
					],
				],
				"lbSelector" =>
				[
					'label'	 => __( 'Which to load on lightbox', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'description' => __( "Leave empty to load whole page. To load specific part, please enter selector to identify it, for example: #content or #main or .post or .hentry or another value depends on the active Theme", "content-views-query-and-display-post-page" ),
					'condition' => [
						'linkTarget' => 'pt-cv-lightbox',
					],
				],
				"lbNavi" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::HIDDEN,
					'default' => 'yes',
					'condition' => [
						'linkTarget' => 'pt-cv-lightbox-image',
					],
				],
				"linkNofollow" =>
				[
					'label'	 => __( 'Use rel="nofollow" for item links', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control' => true,
				],
				"noPostFound" =>
				[
					'label'		 => __( 'When no posts found', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'nopost_options' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'nopost_options' ),
					'label_block' => true,
				],
				"noPostText" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'label_block' => true,
					'description' => __( 'Enter your text here. Leave empty to hide it', "content-views-query-and-display-post-page" ),
					'condition' => [
						'noPostFound' => 'changetext',
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- ITEM
		 * ############################################################
		 */
		public function _item_controls( $localize_info, $prefix ) {

			return [
				"overlaid" =>
				[
					'label'	 => __( 'Enable Overlay', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control'	 => true,
					'description'		 => __( "Disable to use compound mode", "content-views-query-and-display-post-page" ) . ' ' . $this->_prolink( "overlaid", __( "(demo)", "content-views-query-and-display-post-page" ), 'https://contentviewspro.com/demo/disable-overlay/', false ),
					'condition' => [
						'viewType' => 'overlaygrid',
					],
				],
				"overlayClickable" =>
				[
					'label'	 => __( 'Whole Overlay Clickable', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control' => true,
					'condition' => [
						'viewType'	 => 'overlaygrid',
						'overlaid'	 => 'yes',
					],
				],
				"overOnHover" =>
				[
					'label'	 => __( 'Show Text On Hover', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control' => true,
					'condition' => [
						'viewType'	 => 'overlaygrid',
						'overlaid'	 => 'yes',
					],
				],
				"overlayPosition" =>
				[
					'label'		 => __( 'Text Position', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'ovlposi' ),
					'default'	 => 'center',
					'condition' => [
						'viewType'	 => 'overlaygrid',
						'overlaid'	 => 'yes',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}overlay-wrapper" => 'justify-content: {{VALUE}}',
					],
				],
				"overlayType" =>
				[
					'name'				 => "overlayType",
					'_cv_group_control'	 => 'color_gradient_picker',
					'condition' => [
						'viewType'	 => 'overlaygrid',
						'overlaid'	 => 'yes',
					],
					'selector' => "{{WRAPPER}} {$prefix}thumb-wrapper::before",
				]
				,
				"overlayOpacity" =>
				[
					'label'	 => __( 'Overlay Opacity', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'separator' => 'before',
					'range' => [
						'px' => [
							'min'	 => 0,
							'max'	 => 1,
							'step'	 => 0.1,
						],
					],
					'condition' => [
						'viewType'	 => 'overlaygrid',
						'overlaid'	 => 'yes',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}thumb-wrapper::before" => 'opacity: {{SIZE}}',
					],
				],
				"overlay__wrapperPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'condition' => [
						'viewType'	 => 'overlaygrid',
						'overlaid'	 => 'yes',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}overlay-wrapper" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"content__itemBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'condition' => [
						'viewType!' => 'overlaygrid',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}view:not(.list1.layout3):not(.scrollable):not(.pinterest) {$prefix}content-item, {{WRAPPER}} {$prefix}view.list1.layout3 {$prefix}remain-wrapper, {{WRAPPER}} {$prefix}view.scrollable {$prefix}carousel-caption , {{WRAPPER}} {$prefix}view.pinterest {$prefix}pinmas" => 'background-color: {{VALUE}};'
					],
				],
				"remain__wrapperBgColor" =>
				[
					'label'	 => __( 'Inside Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'condition' => [
						'blockName'		 => 'grid1',
						'whichLayout'	 => 'layout3',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}remain-wrapper" => 'background-color: {{VALUE}};'
					],
				],
				"content__itemPadding" =>
				[
					'label'	 => $this->_get_special_control( 'viewType' ) === 'pinterest' ? __( 'Margin', 'content-views-query-and-display-post-page' ) : __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'condition' => [
						'viewType!' => 'overlaygrid',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}view:not(.list1.layout3):not(.scrollable):not(.collapsible) {$prefix}content-item, {{WRAPPER}} {$prefix}view.list1.layout3 {$prefix}remain-wrapper, {{WRAPPER}} {$prefix}view.scrollable {$prefix}carousel-caption , {{WRAPPER}} {$prefix}view.collapsible .panel-body" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"remain__wrapperPadding" =>
				[
					'label'	 => __( 'Inside Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'condition' => [
						'blockName'		 => 'grid1',
						'whichLayout!'	 => 'layout1',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}remain-wrapper" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"content__itemBorderStyle" =>
				[
					'label'		 => __( 'Border Style', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'border_styles' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'border_styles' ),
					'selectors' => [
						"{{WRAPPER}} {$prefix}view:not(.list1.layout3):not(.scrollable):not(.pinterest) {$prefix}content-item, {{WRAPPER}} {$prefix}view.list1.layout3 {$prefix}remain-wrapper, {{WRAPPER}} {$prefix}view.scrollable {$prefix}carousel-caption , {{WRAPPER}} {$prefix}view.pinterest {$prefix}pinmas" => 'border-style: {{VALUE}};'
					],
				],
				"content__itemBorderWidth" =>
				[
					'label'	 => __( 'Border Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'content__itemBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}view:not(.list1.layout3):not(.scrollable):not(.pinterest) {$prefix}content-item, {{WRAPPER}} {$prefix}view.list1.layout3 {$prefix}remain-wrapper, {{WRAPPER}} {$prefix}view.scrollable {$prefix}carousel-caption , {{WRAPPER}} {$prefix}view.pinterest {$prefix}pinmas" => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"content__itemBorderColor" =>
				[
					'label'	 => __( 'Border Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'content__itemBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}view:not(.list1.layout3):not(.scrollable):not(.pinterest) {$prefix}content-item, {{WRAPPER}} {$prefix}view.list1.layout3 {$prefix}remain-wrapper, {{WRAPPER}} {$prefix}view.scrollable {$prefix}carousel-caption , {{WRAPPER}} {$prefix}view.pinterest {$prefix}pinmas" => 'border-color: {{VALUE}};'
					],
				],
				"content__itemBorderRadius" =>
				[
					'label'	 => __( 'Border Radius', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}view:not(.list1.layout3):not(.scrollable):not(.pinterest) {$prefix}content-item, {{WRAPPER}} {$prefix}view.list1.layout3 {$prefix}remain-wrapper, {{WRAPPER}} {$prefix}view.scrollable {$prefix}carousel-caption , {{WRAPPER}} {$prefix}view.pinterest {$prefix}pinmas" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"content__itemBoxShadow" =>
				[
					'name'				 => "content__itemBoxShadow",
					'_cv_group_control'	 => 'box_shadow',
					'selector' => "{{WRAPPER}} {$prefix}view:not(.list1.layout3):not(.scrollable):not(.pinterest) {$prefix}content-item, {{WRAPPER}} {$prefix}view.list1.layout3 {$prefix}remain-wrapper, {{WRAPPER}} {$prefix}view.scrollable {$prefix}carousel-caption , {{WRAPPER}} {$prefix}view.pinterest {$prefix}pinmas",
				]
			,
			];
		}

		/**
		 * ############################################################
		 * ---------- HEADING
		 * ############################################################
		 */
		public function _heading_controls( $localize_info, $prefix ) {

			return [
				"headingText" =>
				[
					'label'	 => __( 'Heading Text', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'Heading', 'content-views-query-and-display-post-page' ),
				],
				"headingStyle" =>
				[
					'label'		 => __( 'Style', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'heading_styles' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'heading_styles' ),
				],
				"headingTag" =>
				[
					'label'		 => __( 'HTML Tag', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'title_tags' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'title_tags' ),
				],
				"headingHide" =>
				[
					'label'	 => __( 'Hide when no posts found', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'default' => 'yes',
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- THUMBNAIL
		 * ############################################################
		 */
		public function _thumbnail_controls( $localize_info, $prefix ) {

			return [
				"showThumbnailOthers" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::HIDDEN,
					'condition' => [
						'viewType' => 'onebig',
					],
				],
				"thumbPosition" =>
				[
					'label'		 => __( 'Thumbnail Position', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'thumb_positions' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'thumb_positions' ),
					'conditions' => [
						'relation'	 => 'or',
						'terms'		 => [
							[
								'relation'	 => 'and',
								'terms'		 => [
									[
										'name'		 => 'blockName',
										'operator'	 => '===',
										'value'		 => 'list1',
									],
									[
										'name'		 => 'whichLayout',
										'operator'	 => '!==',
										'value'		 => 'layout3',
									],
								],
							],
							[
								'relation'	 => 'and',
								'terms'		 => [
									[
										'name'		 => 'blockName',
										'operator'	 => '===',
										'value'		 => 'onebig1',
									],
									[
										'name'		 => 'whichLayout',
										'operator'	 => '===',
										'value'		 => 'layout3',
									],
								],
							],
						],
					],
				],
				"thumbPositionOthers" =>
				[
					'label'		 => __( 'Others Thumbnail Position', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'thumb_positions' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'thumb_positions' ),
					'condition' => [
						'viewType' => 'onebig',
					],
				],
				"imgSize" =>
				[
					'label'		 => __( 'Image Size', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'img_sizes' ),
					'default'	 => 'large',
				],
				"imgSizeOthers" =>
				[
					'label'		 => __( 'Others Image Size', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'img_sizes' ),
					'default'	 => 'large',
					'condition' => [
						'hasOne' => '1',
					],
				],
				"thumbnailMaxWidth" =>
				[
					'label'	 => __( 'Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range'		 => [
						'px' => [
							'min'	 => 0,
							'max'	 => 2000,
							'step'	 => 1,
						],
					],
					'size_units' => [ 'px', 'em', 'rem', '%', 'vh' ],
					'_cv_responsive' => true,
					'condition' => [
						'viewType!' => 'overlaygrid',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}thumb-wrapper:not(.miniwrap)" => 'width: {{SIZE}}{{UNIT}};'
					],
				],
				"thumbnailHeight" =>
				[
					'label'	 => __( 'Height', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range'		 => [
						'px' => [
							'min'	 => 0,
							'max'	 => 1400,
							'step'	 => 1,
						],
					],
					'size_units' => [ 'px', 'em', 'rem', '%', 'vh' ],
					'_cv_responsive' => true,
					'conditions' => [
						'relation'	 => 'or',
						'terms'		 => [
							[
								'relation'	 => 'and',
								'terms'		 => [
									[
										'name'		 => 'isSpec',
										'operator'	 => '!==',
										'value'		 => '1',
									],
									[
										'name'		 => 'blockName',
										'operator'	 => '!in',
										'value'		 => [ 'overlay1', 'overlay6', 'overlay7', 'overlay8' ],
									],
								],
							],
							[
								'relation'	 => 'and',
								'terms'		 => [
									[
										'name'		 => 'viewType',
										'operator'	 => '===',
										'value'		 => 'overlaygrid',
									],
									[
										'name'		 => 'overlaid',
										'operator'	 => '!==',
										'value'		 => 'yes',
									],
								],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}thumbnail:not({$prefix}thumbnailsm)" => 'height: {{SIZE}}{{UNIT}};'
					],
				],
				"thumbnailsmMaxWidth" =>
				[
					'label'	 => __( 'Others Image Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range'		 => [
						'px' => [
							'min'	 => 0,
							'max'	 => 2000,
							'step'	 => 1,
						],
					],
					'size_units' => [ 'px', 'em', 'rem', '%', 'vh' ],
					'_cv_responsive' => true,
					'condition' => [
						'viewType' => 'onebig',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}thumb-wrapper.miniwrap" => 'width: {{SIZE}}{{UNIT}};'
					],
				],
				"thumbnailsmHeight" =>
				[
					'label'	 => __( 'Others Image Height', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range'		 => [
						'px' => [
							'min'	 => 0,
							'max'	 => 1400,
							'step'	 => 1,
						],
					],
					'size_units' => [ 'px', 'em', 'rem', '%', 'vh' ],
					'_cv_responsive' => true,
					'conditions' => [
						'relation'	 => 'or',
						'terms'		 => [
							[
								'name'		 => 'viewType',
								'operator'	 => '===',
								'value'		 => 'onebig',
							],
							[
								'relation'	 => 'and',
								'terms'		 => [
									[
										'name'		 => 'viewType',
										'operator'	 => '===',
										'value'		 => 'overlaygrid',
									],
									[
										'name'		 => 'overlaid',
										'operator'	 => '!==',
										'value'		 => 'yes',
									],
									[
										'name'		 => 'blockName',
										'operator'	 => '!==',
										'value'		 => 'overlay1',
									],
								],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}thumbnailsm" => 'height: {{SIZE}}{{UNIT}};'
					],
				],
				"thumbnailEffect" =>
				[
					'label'		 => __( 'Hover Effect', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'thumb_effects' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'thumb_effects' ),
				],
				"__heading10" =>
				[
					'label'	 => __( 'Substitute With', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::HEADING,
				],
				"subImg" =>
				[
					'label'		 => "",
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'img_sub' ),
					'default'	 => self::$hasPro ? 'image' : 'none',
					'label_block' => true,
					'_cv_pro_control'	 => true,
					'description'		 => self::$hasPro ? '' : __( 'If no featured image is added to a post, show the substitute option as thumbnail', 'content-views-query-and-display-post-page' ),
				],
				"subImgCtf" =>
				[
					'label'		 => __( 'Select Custom Field', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'custom_field_keys' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'custom_field_keys' ),
					'label_block' => true,
					'condition' => [
						'subImg' => 'image-ctf',
					],
				],
				"subImgRole" =>
				[
					'label'		 => "",
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'img_sub_role' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'img_sub_role' ),
					'label_block' => true,
					'condition' => [
						'subImg!' => 'none',
					],
				],
				"subImgFetch" =>
				[
					'label'	 => __( 'Fetch content from page builder to find substitute', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'subImg!' => 'none',
					],
				],
				"defaultImg" =>
				[
					'label'	 => __( 'Show default image if no image set', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'default' => 'yes',
				],
				"responsiveImg" =>
				[
					'label'	 => __( 'Responsive image (srcset, sizes)', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"lazyImg" =>
				[
					'label'	 => __( 'Lazyload', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control'	 => true,
					'description'		 => __( 'Defer loading of images until they are needed, to improve page load time', 'content-views-query-and-display-post-page' ),
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- TITLE
		 * ############################################################
		 */
		public function _title_controls( $localize_info, $prefix ) {

			return [
				"titleTag" =>
				[
					'label'		 => __( 'HTML Tag', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'title_tags' ),
					'default'	 => 'h4',
				],
				"titleLength" =>
				[
					'label'	 => __( 'Length (letters)', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range' => [
						'px' => [
							'min'	 => 1,
							'max'	 => 100,
							'step'	 => 1,
						],
					],
					'_cv_pro_control' => true,
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- TOP META
		 * ############################################################
		 */
		public function _topmeta_controls( $localize_info, $prefix ) {

			return [
				"showTaxonomyOthers" =>
				[
					'label'	 => __( 'Others Item Meta', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'hasOne' => '1',
					],
				],
				"topmetaWhich" =>
				[
					'label'		 => __( 'Select Meta', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'top_meta' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'top_meta' ),
					'_cv_pro_control' => true,
				],
				"taxoWhich" =>
				[
					'label'		 => __( 'Taxonomy', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT2,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'taxonomies' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'taxonomies' ),
					'_cv_pro_control' => true,
					'condition' => [
						'topmetaWhich' => 'mtt_taxonomy',
					],
				],
				"taxoPosition" =>
				[
					'label'		 => __( 'Position', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'taxo_positions' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'taxo_positions' ),
					'condition' => [
						'viewType!' => 'collapsible',
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- CUSTOM FIELD
		 * ############################################################
		 */
		public function _customfield_controls( $localize_info, $prefix ) {

			return [
				"CTFlist" =>
				[
					'label'		 => __( 'Select Fields', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT2,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'custom_field_keys' ),
					'default'	 => [],
					'multiple'	 => true,
					'label_block' => true,
					'description' => ContentViews_Elementor_Widget::_get_options( 'ctfdesc_select' ),
				],
				"CTFcolumn" =>
				[
					'label'	 => __( 'Fields Per Row', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range' => [
						'px' => [
							'min'	 => 1,
							'max'	 => 4,
							'step'	 => 1,
						],
					],
				],
				"CTFnoempty" =>
				[
					'label'	 => __( 'Hide field has empty value', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"CTFname" =>
				[
					'label'	 => __( 'Show field name', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"CTFcustomname" =>
				[
					'label'	 => __( 'Customize field name', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'label_block' => true,
					'description' => ContentViews_Elementor_Widget::_get_options( 'ctfdesc_name' ),
					'condition' => [
						'CTFname' => 'yes',
					],
				],
				"CTFshortcode" =>
				[
					'label'	 => __( 'Execute shortcode in field value', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"CTFlinebreak" =>
				[
					'label'	 => __( 'Enable line breaks in field value', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"CTFembed" =>
				[
					'label'	 => __( 'Fetch embed HTML in field value', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"CTFdatefm" =>
				[
					'label'	 => __( 'Convert Date field to a new format', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"CTFdatenew" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'default' => 'F j, Y',
					'description' => ContentViews_Elementor_Widget::_get_options( 'ctfdesc_datenew' ),
					'condition' => [
						'CTFdatefm' => 'yes',
					],
				],
				"CTFdateold" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'description' => ContentViews_Elementor_Widget::_get_options( 'ctfdesc_dateold' ),
					'condition' => [
						'CTFdatefm' => 'yes',
					],
				],
				"__showCTFIntro" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'separator' => 'before',
					'raw'				 => __( 'Show custom fields', 'content-views-query-and-display-post-page' ) . ' >> ' . $this->_prolink( "__showCTFIntro", __( 'get PRO', 'content-views-query-and-display-post-page' ), null, false ),
					'content_classes'	 => 'contentviews-pro-section',
					'_cv_show_when' => !self::$hasPro,
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- CONTENT
		 * ############################################################
		 */
		public function _content_controls( $localize_info, $prefix ) {

			return [
				"showContentOthers" =>
				[
					'label'	 => __( 'Others Item Content', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'hasOne' => '1',
					],
				],
				"contentShow" =>
				[
					'label'		 => __( 'What to show', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'content_show' ),
					'default'	 => 'excerpt',
				],
				"excerptLength" =>
				[
					'label'	 => __( 'Length', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'default' => [ 'size' => 20, 'unit' => 'px', ],
					'range' => [
						'px' => [
							'min'	 => 1,
							'max'	 => 150,
							'step'	 => 1,
						],
					],
					'condition' => [
						'contentShow' => 'excerpt',
					],
				],
				"excerptLengthOthers" =>
				[
					'label'	 => __( 'Others Length', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SLIDER,
					'range' => [
						'px' => [
							'min'	 => 1,
							'max'	 => 150,
							'step'	 => 1,
						],
					],
					'condition' => [
						'hasOne' => '1',
					],
				],
				"excerptManual" =>
				[
					'label'		 => __( 'Manual excerpt', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'manual_excerpt_options' ),
					'default'	 => 'yes',
					'label_block' => true,
					'condition' => [
						'contentShow' => 'excerpt',
					],
				],
				"excerptHtml" =>
				[
					'label'		 => __( 'HTML Tags In Excerpt', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'html_excerpt_options' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'html_excerpt_options' ),
					'label_block' => true,
					'condition' => [
						'contentShow' => 'excerpt',
					],
				],
				"excerptNoDots" =>
				[
					'label'	 => __( 'Remove "..." at the end of excerpt', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_show_when' => self::$hasPro,
					'condition' => [
						'contentShow' => 'excerpt',
					],
				],
				"excerptExclude" =>
				[
					'label'	 => __( 'Exclude content of these HTML tags', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_show_when' => self::$hasPro,
					'condition' => [
						'contentShow' => 'excerpt',
					],
				],
				"excerptExcTag" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'label_block' => true,
					'description'	 => __( 'Separate tags by comma', "content-views-query-and-display-post-page" ),
					'_cv_show_when'	 => self::$hasPro,
					'condition' => [
						'contentShow'	 => 'excerpt',
						'excerptExclude' => 'yes',
					],
				],
				"excerptHook" =>
				[
					'label'	 => __( 'Enable line breaks, translate, do shortcodes, apply filters in excerpt', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_show_when' => self::$hasPro,
					'condition' => [
						'contentShow' => 'excerpt',
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- READ MORE
		 * ############################################################
		 */
		public function _readmore_controls( $localize_info, $prefix ) {

			return [
				"showReadmoreOthers" =>
				[
					'label'	 => __( 'Others Item Read More', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'hasOne' => '1',
					],
				],
				"readmoreText" =>
				[
					'label'	 => __( 'Read more text', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'default' => ucwords( rtrim( __( 'Read more...' ), '.' ) ),
					'label_block' => true,
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- BOTTOM META
		 * ############################################################
		 */
		public function _bottom_meta_controls( $localize_info, $prefix ) {

			return [
				"showMetaOthers" =>
				[
					'label'	 => __( 'Others Item Meta', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'hasOne' => '1',
					],
				],
				"metaWhich" =>
				[
					'label'		 => __( 'Select Meta', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT2,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'meta_fields' ),
					'default'	 => [ 'date', 'author' ],
					'multiple'	 => true,
					'label_block' => true,
				],
				"metaWhichOthers" =>
				[
					'label'		 => __( 'Select Others Item Meta', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT2,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'meta_fields' ),
					'default'	 => [ 'date', 'author' ],
					'multiple'	 => true,
					'label_block' => true,
					'condition' => [
						'hasOne'		 => '1',
						'showMetaOthers' => 'yes',
					],
				],
				"metaSeparator" =>
				[
					'label'		 => __( 'Separator', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'meta_separator' ),
					'default'	 => '/',
				],
				"metaIcon" =>
				[
					'label'	 => __( 'Show icon', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"authorAvatar" =>
				[
					'label'	 => __( 'Author Avatar', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"authorPrefix" =>
				[
					'label'	 => __( 'Author Prefix', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
				],
				"dateFormat" =>
				[
					'label'		 => __( 'Date format', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'date_format' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'date_format' ),
					'_cv_pro_control' => true,
				],
				"dateFormatCustom" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'description' => __( 'To define your format, please check', 'content-views-pro' ) . ' ' . $this->_prolink( "dateFormatCustom", __( "this document", "content-views-query-and-display-post-page" ), 'https://codex.wordpress.org/Formatting_Date_and_Time', false ),
					'condition' => [
						'dateFormat' => 'custom',
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- PAGINATION
		 * ############################################################
		 */
		public function _pagination_controls( $localize_info, $prefix ) {

			return [
				"pagingType" =>
				[
					'label'		 => __( 'Type', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'paging_types' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'paging_types' ),
				],
				"pagingStyle" =>
				[
					'label'		 => __( 'Style', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => $this->_get_option_from( 'paging_styles', 'pagingStyle' ),
					'default'	 => self::_default_option( $this->_get_option_from( 'paging_styles', 'pagingStyle' ) ),
					'condition' => [
						'pagingType' => 'ajax',
					],
				],
				"__paginationPro" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw'				 => __( 'Infinite Scroll, and Load More button', 'content-views-query-and-display-post-page' ) . ' >> ' . $this->_prolink( "__paginationPro", __( 'get PRO', 'content-views-query-and-display-post-page' ), null, false ),
					'content_classes'	 => 'contentviews-pro-section',
					'_cv_show_when' => !self::$hasPro,
				],
				"pagingMoreText" =>
				[
					'label'	 => __( 'Load more text', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'_cv_pro_control' => true,
					'condition' => [
						'pagingType'	 => 'ajax',
						'pagingStyle'	 => 'loadmore',
					],
				],
				"pagingNoScroll" =>
				[
					'label'	 => __( 'Disable scroll to top after changing page', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'pagingType' => 'ajax',
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- ADVERT
		 * ############################################################
		 */
		public function _showadvert_controls( $localize_info, $prefix ) {

return [
				"showAds" =>
				[
					'label'	 => __( 'Show ads in output', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control'	 => true,
					'description'		 => $this->_prolink( "showAds", __( "See Demo", "content-views-query-and-display-post-page" ), 'https://contentviewspro.com/demo/show-advertisements-in-layout/', false ),
				],
				"adSCode" =>
				[
					'label'	 => __( 'Execute shortcode in ad content', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"adPosition" =>
				[
					'label'		 => __( 'Ads Positions', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'ad_positions' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'ad_positions' ),
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"adPositionMan" =>
				[
					'label'	 => __( 'Manual Positions', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'description' => __( 'Enter numbers (separate by comma, in increasing order) to set positions to show ads on each page. These numbers must be smaller than "Posts Per Page" value', "content-views-query-and-display-post-page" ),
					'condition' => [
						'showAds'	 => 'yes',
						'adPosition' => 'manual',
					],
				],
				"adPerPage" =>
				[
					'label'	 => __( 'Ads Per Page', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::NUMBER,
					'default' => 1,
					'min' => 1,
					'condition' => [
						'showAds'		 => 'yes',
						'showPagination' => 'yes',
					],
				],
				"adRepeat" =>
				[
					'label'	 => __( 'Repeat Ads', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::NUMBER,
					'default' => 1,
					'min' => 1,
					'description' => ContentViews_Elementor_Widget::_get_options( 'ad_desc' ),
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content0" =>
				[
					'label'	 => 'Ad 1',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content1" =>
				[
					'label'	 => 'Ad 2',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content2" =>
				[
					'label'	 => 'Ad 3',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content3" =>
				[
					'label'	 => 'Ad 4',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content4" =>
				[
					'label'	 => 'Ad 5',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content5" =>
				[
					'label'	 => 'Ad 6',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content6" =>
				[
					'label'	 => 'Ad 7',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content7" =>
				[
					'label'	 => 'Ad 8',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content8" =>
				[
					'label'	 => 'Ad 9',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
				"ads__content9" =>
				[
					'label'	 => 'Ad 10',
					'type'	 => \Elementor\Controls_Manager::TEXTAREA,
					'ai' => [ 'active' => false, ],
					'condition' => [
						'showAds' => 'yes',
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- SOCIAL SHARE
		 * ############################################################
		 */
		public function _socialshare_controls( $localize_info, $prefix ) {

			return [
				"showShare" =>
				[
					'label'	 => __( 'Show sharing buttons', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'_cv_pro_control'	 => true,
					'description'		 => $this->_prolink( "showShare", __( "See Demo", "content-views-query-and-display-post-page" ), 'https://contentviewspro.com/demo/social-sharing/', false ),
				],
				"shareBtn" =>
				[
					'label'		 => __( 'Select Buttons', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT2,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'social_btns' ),
					'default'	 => [ 'facebook', 'twitter' ],
					'multiple'	 => true,
					'label_block' => true,
					'condition' => [
						'showShare' => 'yes',
					],
				],
				"shareCircle" =>
				[
					'label'	 => __( 'Use circle buttons', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'showShare' => 'yes',
					],
				],
				"shareCount" =>
				[
					'label'	 => __( 'Show share count', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'showShare' => 'yes',
					],
				],
			];
		}

	}

}