<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}


if ( !class_exists( 'ContentViews_Elementor_Hooks_Pro' ) ) {

	class ContentViews_Elementor_Hooks_Pro {

		static function init_hooks() {
			add_action( 'elementor/widgets/register', array( __CLASS__, 'register_pro_widgets' ), 20 );
			add_action( 'elementor/controls/register', array( __CLASS__, 'register_new_controls' ) );

			add_action( PT_CV_PREFIX_ . 'ctf_sort_controls_register', array( __CLASS__, 'action_ctf_sort_controls_register' ) );
			add_action( PT_CV_PREFIX_ . 'ctf_query_controls_register', array( __CLASS__, 'action_ctf_query_controls_register' ) );
			add_action( PT_CV_PREFIX_ . 'top_section', array( __CLASS__, 'action_live_filter_controls' ) );

			add_action( PT_CV_PREFIX_ . 'fields_position_controls', array( __CLASS__, '_fields_position_controls' ) );

			add_action( PT_CV_PREFIX_ . 'after_section', array( __CLASS__, 'add_other_sections' ), 10, 2 );
		}

		/**
		 * ############################################################
		 * ---------- PRO WIDGETS
		 * ############################################################
		 */
		static function register_pro_widgets( $widgets_manager ) {

			if ( !class_exists( 'ContentViews_Elementor_Widget' ) ) {
				return;
			}

			foreach ( glob( dirname( __FILE__ ) . '/widgets/*.php' ) as $file ) {
				include_once $file;

				$filename	 = basename( $file, '.php' );
				$classname	 = 'ContentViews_Elementor_Widget_' . ucfirst( $filename );
				if ( class_exists( $classname, false ) ) {
					$widgets_manager->register( new $classname() );
				}
			}
		}

		// Register controls
		static function register_new_controls( $controls_manager ) {
			foreach ( glob( dirname( __FILE__ ) . '/controls/*.php' ) as $file ) {
				include_once $file;

				$filename	 = basename( $file, '.php' );
				$classname	 = 'ContentViews_Elementor_Control_' . ucfirst( $filename );
				if ( class_exists( $classname, false ) ) {
					$controls_manager->register( new $classname() );
				}
			}
		}

		// Register Sort custom field controls
		static function action_ctf_sort_controls_register( $_this ) {
			// Start Popover
			$_this->add_control(
			"sortctf__popover", [
				'label'		 => __( 'Sort by Custom Field', 'content-views-query-and-display-post-page' ),
				'type'		 => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'classes'	 => 'contentviews-control-indent contentviews-control-mg0',
			]
			);
			$_this->start_popover();


			// Repeater
			$repeater = new \Elementor\Repeater();

			$controls = self::_customfield_sort_controls();
			foreach ( $controls as $control_name => $control_settings ) {
				$repeater->add_control( $control_name, $control_settings );
			}

			$_this->add_control(
			'sortCtf', [
				'label'			 => __( 'Select field to sort', 'content-views-pro' ),
				'type'			 => \Elementor\Controls_Manager::REPEATER,
				'fields'		 => $repeater->get_controls(),
				'title_field'	 => '{{{ key }}} ({{{ order }}})',
				'prevent_empty'	 => false,
			]
			);

			// End Popover
			$_this->end_popover();
		}

		/**
		 * ############################################################
		 * ---------- SORT CUSTOM FIELD
		 * ############################################################
		 */
		static function _customfield_sort_controls() {

			return [
				"key" =>
				[
					'label'		 => __( 'Field', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT2,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'custom_field_keys' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'custom_field_keys' ),
				],
				"type" =>
				[
					'label'		 => __( 'Type', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'custom_field_types' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'custom_field_types' ),
				],
				"order" =>
				[
					'label'		 => __( 'Order', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'orders' ),
					'default'	 => 'desc',
				],
				"kcomma" =>
				[
					'label'	 => __( 'This field uses comma "," as thousand separator', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'type' => 'NUMERIC',
					],
				],
				"datefm" =>
				[
					'label'	 => __( 'Date Format', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'type',
								'operator'	 => 'in',
								'value'		 => [ 'DATE', 'DATETIME' ],
							],
						],
					],
				],
				"lfenable" =>
				[
					'label'	 => __( 'Show as sort option to visitors', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"lftext" =>
				[
					'label'	 => __( 'Label', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'condition' => [
						'lfenable' => 'yes',
					],
				],
			];
		}
		
		// Register Query custom field controls
		static function action_ctf_query_controls_register( $_this ) {

			$_this->add_control(
			"filterCtfRel", [
				'label'		 => __( 'Fields Relation', 'content-views-pro' ),
				'type'		 => \Elementor\Controls_Manager::SELECT,
				'options'	 => ContentViews_Elementor_Widget::_get_options( 'taxorelation' ),
				'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'taxorelation' ),
			]
			);

			// Fields
			$repeater = new \Elementor\Repeater();

			$controls = self::_customfield_query_controls();
			foreach ( $controls as $control_name => $control_settings ) {
				$repeater->add_control( $control_name, $control_settings );
			}

			$_this->add_control(
			'filterCtf', [
				'label'			 => __( 'Select field to query', 'content-views-pro' ),
				'type'			 => \Elementor\Controls_Manager::REPEATER,
				'fields'		 => $repeater->get_controls(),
				'title_field'	 => '{{{ key }}}',
				'prevent_empty'	 => false,
			]
			);

		}

		/**
		 * ############################################################
		 * ---------- FILTER CUSTOM FIELD
		 * ############################################################
		 */
		static function _customfield_query_controls() {

			return [
				"key" =>
				[
					'label'		 => __( 'Field', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT2,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'custom_field_keys' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'custom_field_keys' ),
				],
				"type" =>
				[
					'label'		 => __( 'Type', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'custom_field_types' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'custom_field_types' ),
				],
				"datefm" =>
				[
					'label'	 => __( 'Date Format', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'description' => __( "Set MySQL format of this field, if result is incorrect", "content-views-pro" ) . ' (<a target="_blank" href="http://docs.contentviewspro.com/specify-date-format-for-sorting-custom-field/">read more</a>)',
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'type',
								'operator'	 => 'in',
								'value'		 => [ 'DATE', 'DATETIME' ],
							],
						],
					],
				],
				"ele_operator_CHAR" =>
				[
					'label'		 => __( 'Compare', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'ctf_operator_elementor', 'CHAR' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'ctf_operator_elementor', 'CHAR' ),
					'condition' => [
						'lfenable!'	 => 'yes',
						'type'		 => 'CHAR',
					],
				],
				"ele_operator_NUMERIC" =>
				[
					'label'		 => __( 'Compare', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'ctf_operator_elementor', 'NUMERIC' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'ctf_operator_elementor', 'NUMERIC' ),
					'condition' => [
						'lfenable!'	 => 'yes',
						'type'		 => 'NUMERIC',
					],
				],
				"ele_operator_DECIMAL" =>
				[
					'label'		 => __( 'Compare', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'ctf_operator_elementor', 'DECIMAL' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'ctf_operator_elementor', 'DECIMAL' ),
					'condition' => [
						'lfenable!'	 => 'yes',
						'type'		 => 'DECIMAL',
					],
				],
				"ele_operator_DATE" =>
				[
					'label'		 => __( 'Compare', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'ctf_operator_elementor', 'DATE' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'ctf_operator_elementor', 'DATE' ),
					'condition' => [
						'lfenable!'	 => 'yes',
						'type'		 => 'DATE',
					],
				],
				"ele_operator_DATETIME" =>
				[
					'label'		 => __( 'Compare', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'ctf_operator_elementor', 'DATETIME' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'ctf_operator_elementor', 'DATETIME' ),
					'condition' => [
						'lfenable!'	 => 'yes',
						'type'		 => 'DATETIME',
					],
				],
				"ele_operator_BINARY" =>
				[
					'label'		 => __( 'Compare', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'ctf_operator_elementor', 'BINARY' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'ctf_operator_elementor', 'BINARY' ),
					'condition' => [
						'lfenable!'	 => 'yes',
						'type'		 => 'BINARY',
					],
				],
				"value" =>
				[
					'label'	 => __( 'Value', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'condition' => [
						'lfenable!'	 => 'yes',
						'type!'		 => [ 'DATE', 'DATETIME' ],
					],
				],
				"ele_value_DATE" =>
				[
					'label'	 => __( 'Value', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::DATE_TIME,
					'picker_options' => [ 'enableTime' => false ],
					'condition' => [
						'lfenable!'	 => 'yes',
						'type'		 => 'DATE',
					],
				],
				"ele_value_DATETIME" =>
				[
					'label'	 => __( 'Value', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::DATE_TIME,
					'condition' => [
						'lfenable!'	 => 'yes',
						'type'		 => 'DATETIME',
					],
				],
				"__desc_tmp_1" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( 'Enter values separated by comma', 'content-views-pro' ),
					'condition' => [
						'lfenable!'			 => 'yes',
						'type'				 => 'CHAR',
						'ele_operator_CHAR'	 => [ 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ]
					],
				],
				"__desc_tmp_2" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( 'Enter values separated by comma', 'content-views-pro' ),
					'condition' => [
						'lfenable!'				 => 'yes',
						'type'					 => 'NUMERIC',
						'ele_operator_NUMERIC'	 => [ 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ]
					],
				],
				"__desc_tmp_3" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( 'Enter values separated by comma', 'content-views-pro' ),
					'condition' => [
						'lfenable!'				 => 'yes',
						'type'					 => 'DECIMAL',
						'ele_operator_DECIMAL'	 => [ 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ]
					],
				],
				"__desc_tmp_4" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( 'Enter values separated by comma', 'content-views-pro' ),
					'condition' => [
						'lfenable!'			 => 'yes',
						'type'				 => 'DATE',
						'ele_operator_DATE'	 => [ 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ]
					],
				],
				"__desc_tmp_5" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( 'Enter values separated by comma', 'content-views-pro' ),
					'condition' => [
						'lfenable!'				 => 'yes',
						'type'					 => 'DATETIME',
						'ele_operator_DATETIME'	 => [ 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ]
					],
				],
				"__desc_tmp_10" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( 'Enter 1 for True, 0 for False', 'content-views-pro' ),
					'condition' => [
						'lfenable!'	 => 'yes',
						'type'		 => 'BINARY',
					],
				],
				"__heading_CTFLF" =>
				[
					'label'	 => __( 'Live Filter', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				],
				"lfenable" =>
				[
					'label'	 => __( 'Show as filters to visitors', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"lftype" =>
				[
					'label'		 => __( 'Filter Type', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'types_ctf' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'types_ctf' ),
					'condition' => [
						'lfenable' => 'yes',
					],
				],
				"lfbehavior" =>
				[
					'label'		 => __( 'Behavior', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'behavior' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'behavior' ),
					'condition' => [
						'lftype'	 => 'checkbox',
						'lfenable'	 => 'yes',
					],
				],
				"lflabel" =>
				[
					'label'	 => __( 'Label', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'description' => __( "Enter a space to remove label", "content-views-pro" ),
					'condition' => [
						'lfenable' => 'yes',
					],
				],
				"lfdefault" =>
				[
					'label'	 => __( 'The "All" Text', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'lftype',
								'operator'	 => 'in',
								'value'		 => [ 'radio', 'dropdown', 'button' ],
							],
							[
								'name'		 => 'lfenable',
								'operator'	 => '===',
								'value'		 => 'yes',
							],
						],
					],
				],
				"lforder" =>
				[
					'label'		 => __( 'Order By', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'orderby_ctf' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'orderby_ctf' ),
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'lftype',
								'operator'	 => '!in',
								'value'		 => [ 'range_slider', 'date_range' ],
							],
							[
								'name'		 => 'lfenable',
								'operator'	 => '===',
								'value'		 => 'yes',
							],
						],
					],
				],
				"lforderflag" =>
				[
					'label'		 => "",
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'orderflag' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'orderflag' ),
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'lftype',
								'operator'	 => '!in',
								'value'		 => [ 'range_slider', 'date_range' ],
							],
							[
								'name'		 => 'lfenable',
								'operator'	 => '===',
								'value'		 => 'yes',
							],
						],
					],
				],
				"lftotext" =>
				[
					'label'		 => __( 'Options Text', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'text_ctf' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'text_ctf' ),
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'lftype',
								'operator'	 => '!in',
								'value'		 => [ 'range_slider', 'date_range' ],
							],
							[
								'name'		 => 'lfenable',
								'operator'	 => '===',
								'value'		 => 'yes',
							],
						],
					],
				],
				"lfcount" =>
				[
					'label'	 => __( 'Show posts count', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'lftype',
								'operator'	 => '!in',
								'value'		 => [ 'range_slider', 'date_range' ],
							],
							[
								'name'		 => 'lfenable',
								'operator'	 => '===',
								'value'		 => 'yes',
							],
						],
					],
				],
				"lfnoempty" =>
				[
					'label'	 => __( 'Hide values have no post', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'lftype',
								'operator'	 => '!in',
								'value'		 => [ 'range_slider', 'date_range' ],
							],
							[
								'name'		 => 'lfenable',
								'operator'	 => '===',
								'value'		 => 'yes',
							],
						],
					],
				],
				"lfrequire" =>
				[
					'label'	 => __( 'Hide posts that do not have this custom field', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'lftype',
								'operator'	 => '!in',
								'value'		 => [ 'range_slider', 'date_range' ],
							],
							[
								'name'		 => 'lfenable',
								'operator'	 => '===',
								'value'		 => 'yes',
							],
						],
					],
				],
				"lfdateoperator" =>
				[
					'label'		 => __( 'Operator', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'dateoperator' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'dateoperator' ),
					'condition' => [
						'lftype'	 => 'date_range',
						'lfenable'	 => 'yes',
					],
				],
				"lfrangestep" =>
				[
					'label'	 => __( 'Step', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'description' => __( "Allow only numbers and dot", "content-views-pro" ),
					'condition' => [
						'lftype'	 => 'range_slider',
						'lfenable'	 => 'yes',
					],
				],
				"lfrangepre" =>
				[
					'label'	 => __( 'Prefix', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'condition' => [
						'lftype'	 => 'range_slider',
						'lfenable'	 => 'yes',
					],
				],
				"lfrangepos" =>
				[
					'label'	 => __( 'Suffix', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::TEXT,
					'condition' => [
						'lftype'	 => 'range_slider',
						'lfenable'	 => 'yes',
					],
				],
				"lfrangesepa" =>
				[
					'label'		 => __( 'Thousand Separator', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_settings', 'thousandseparator' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_settings', 'thousandseparator' ),
					'condition' => [
						'lftype'	 => 'range_slider',
						'lfenable'	 => 'yes',
					],
				],
			];
		}


		// Register Live Filter general controls
		static function action_live_filter_controls( $_this ) {
			$key	 = 'lfconfig';
			$prefix = ' .' . PT_CV_PREFIX;

			$section_info		 = [
				'label'	 => esc_html__( 'Live Filter', 'content-views-pro' ),
				'tab'	 => ContentViews_Elementor_Widget::_another_tab(),
			];
			$general_controls	 = self::_livefilter_general_controls();
			$style_controls		 = self::_livefilter_style_controls( $prefix );

			ContentViews_Elementor_Function_Pro::register_section_controls( $_this, $key, $section_info, $general_controls, $style_controls );
		}

		/**
		 * ############################################################
		 * ---------- LIVE FILTER GENERAL
		 * ############################################################
		 */
		static function _livefilter_general_controls() {

			return [
				"__lfEmpty" =>
				[
					'label'	 => "",
					'type'	 => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( 'No live filters enabled yet (you can enable in Content tab)', 'content-views-query-and-display-post-page' ),
					'condition' => [
						'hasLF' => '',
					],
				],
				"hasLF" =>
				[
					'label'	 => "",
					'type'	 => 'contentviews-dynamic',
				],
				"lfArrange" =>
				[
					'label'	 => __( 'Arrange filters', 'content-views-pro' ),
					'type'	 => 'contentviews-sortable',
					'multiple' => true,
					'label_block' => true,
					'description' => __( "Drag & drop filters in the order you want to show", "content-views-query-and-display-post-page" ),
					'condition' => [
						'hasLF!' => '',
					],
				],
				"lfPosition" =>
				[
					'label'		 => __( 'Filters Position', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_position' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_position' ),
					'condition' => [
						'hasLF!' => '',
					],
				],
				"lfWidth" =>
				[
					'label'		 => __( 'Filters Width', 'content-views-pro' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'lf_width' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'lf_width' ),
					'condition' => [
						'lfPosition!'	 => 'above',
						'hasLF!'		 => '',
					],
				],
				"lfWrap" =>
				[
					'label'	 => __( 'Wrap live filters in a div', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'condition' => [
						'lfPosition' => 'above',
						'hasLF!'	 => '',
					],
				],
				"noLFSub" =>
				[
					'label'	 => __( 'Hide Submit button', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'return_value' => 'none',
					'condition' => [
						'hasLF!' => '',
					],
					'selectors' => [
						"{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-submit" => 'display: {{VALUE}} !important',
					],
				],
				"noLFRes" =>
				[
					'label'	 => __( 'Hide Reset button', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
					'return_value' => 'none',
					'description' => __( "2 buttons are shown automatically when enabled search field, or range slider type", "content-views-query-and-display-post-page" ),
					'condition' => [
						'hasLF!' => '',
					],
					'selectors' => [
						"{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-reset" => 'display: {{VALUE}} !important',
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- LIVE FILTER STYLE
		 * ############################################################
		 */
		static function _livefilter_style_controls( $prefix ) {

			return [
				"lfCustomize" =>
				[
					'label'	 => __( 'Change radio, checkbox color', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::SWITCHER,
				],
				"lfEleColor" =>
				[
					'label'	 => __( 'Color', 'content-views-pro' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'condition' => [
						'lfCustomize' => 'yes',
					],
					'selectors' => [
						".cvp-live-filter.cvp-customized input:hover,
				.cvp-live-filter.cvp-customized input:focus,
				.cvp-live-filter.cvp-customized select:hover,
				.cvp-live-filter.cvp-customized select:focus,
				.cvp-live-filter.cvp-customized input~div:hover,
				.cvp-live-filter.cvp-customized input~div:focus" => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}} !important;',
						'.cvp-live-filter.cvp-customized input[type="checkbox"]:checked'																																																											 => 'background-color: {{VALUE}}',
						'.cvp-live-filter.cvp-customized input[type="radio"]:checked'																																																												 => 'border-color: {{VALUE}}',
						'.cvp-live-filter.cvp-customized input[type="radio"]:checked::before'																																																										 => 'border-color: {{VALUE}}; background: {{VALUE}};',
					],
				],
				"__heading2lfstyle" =>
				[
					'label'	 => __( 'Label', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				],
				"LFSlabelAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .cvp-label' => 'text-align: {{VALUE}};'
					],
				],
				"LFSlabel" =>
				[
					'name'				 => "LFSlabel",
					'_cv_group_control'	 => 'typography',
					'selector' => '{{WRAPPER}} .cvp-live-filter .cvp-label',
				]
				,
				"LFSlabelColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .cvp-label' => 'color: {{VALUE}};'
					],
				],
				"LFSlabelHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .cvp-label:hover' => 'color: {{VALUE}};'
					],
				],
				"LFSlabelBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .cvp-label' => 'background-color: {{VALUE}};'
					],
				],
				"LFSlabelHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .cvp-label:hover' => 'background-color: {{VALUE}};'
					],
				],
				"LFSlabelMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .cvp-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"LFSlabelPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .cvp-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"__heading3lfstyle" =>
				[
					'label'	 => __( 'Option', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				],
				"LFSoptionAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type="text"], {{WRAPPER}} .cvp-live-filter div > label, {{WRAPPER}} .cvp-live-filter select, {{WRAPPER}} .cvp-live-filter .irs-from, {{WRAPPER}} .cvp-live-filter .irs-to' => 'text-align: {{VALUE}};'
					],
				],
				"LFSoption" =>
				[
					'name'				 => "LFSoption",
					'_cv_group_control'	 => 'typography',
					'selector' => '{{WRAPPER}} .cvp-live-filter input[type="text"], {{WRAPPER}} .cvp-live-filter div > label, {{WRAPPER}} .cvp-live-filter select, {{WRAPPER}} .cvp-live-filter .irs-from, {{WRAPPER}} .cvp-live-filter .irs-to',
				]
				,
				"LFSoptionColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type="text"], {{WRAPPER}} .cvp-live-filter div > label, {{WRAPPER}} .cvp-live-filter select, {{WRAPPER}} .cvp-live-filter .irs-from, {{WRAPPER}} .cvp-live-filter .irs-to' => 'color: {{VALUE}};'
					],
				],
				"LFSoptionHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type="text"]:hover, {{WRAPPER}} .cvp-live-filter div > label:hover, {{WRAPPER}} .cvp-live-filter select:hover, {{WRAPPER}} .cvp-live-filter .irs-from:hover, {{WRAPPER}} .cvp-live-filter .irs-to:hover' => 'color: {{VALUE}};'
					],
				],
				"LFSoptionBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type="text"], {{WRAPPER}} .cvp-live-filter div > label, {{WRAPPER}} .cvp-live-filter select, {{WRAPPER}} .cvp-live-filter .irs-from, {{WRAPPER}} .cvp-live-filter .irs-to' => 'background-color: {{VALUE}};'
					],
				],
				"LFSoptionHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type="text"]:hover, {{WRAPPER}} .cvp-live-filter div > label:hover, {{WRAPPER}} .cvp-live-filter select:hover, {{WRAPPER}} .cvp-live-filter .irs-from:hover, {{WRAPPER}} .cvp-live-filter .irs-to:hover' => 'background-color: {{VALUE}};'
					],
				],
				"LFSoptionMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type="text"], {{WRAPPER}} .cvp-live-filter div > label, {{WRAPPER}} .cvp-live-filter select, {{WRAPPER}} .cvp-live-filter .irs-from, {{WRAPPER}} .cvp-live-filter .irs-to' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"LFSoptionPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type="text"], {{WRAPPER}} .cvp-live-filter div > label, {{WRAPPER}} .cvp-live-filter select, {{WRAPPER}} .cvp-live-filter .irs-from, {{WRAPPER}} .cvp-live-filter .irs-to' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"__heading4lfstyle" =>
				[
					'label'	 => __( 'Type: Range Slider', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				],
				"LFSrangeColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .irs-from, {{WRAPPER}} .cvp-live-filter .irs-to, {{WRAPPER}} .cvp-live-filter .irs-bar' => 'color: {{VALUE}};'
					],
				],
				"LFSrangeHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .irs-from:hover, {{WRAPPER}} .cvp-live-filter .irs-to:hover, {{WRAPPER}} .cvp-live-filter .irs-bar:hover' => 'color: {{VALUE}};'
					],
				],
				"LFSrangeBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .irs-from, {{WRAPPER}} .cvp-live-filter .irs-to, {{WRAPPER}} .cvp-live-filter .irs-bar' => 'background-color: {{VALUE}};'
					],
				],
				"LFSrangeHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter .irs-from:hover, {{WRAPPER}} .cvp-live-filter .irs-to:hover, {{WRAPPER}} .cvp-live-filter .irs-bar:hover' => 'background-color: {{VALUE}};'
					],
				],
				"__heading5lfstyle" =>
				[
					'label'	 => __( 'Type: Button (Active)', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				],
				"LFSbuttonColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type=radio]:checked~div' => 'color: {{VALUE}};'
					],
				],
				"LFSbuttonHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type=radio]:checked~div:hover' => 'color: {{VALUE}};'
					],
				],
				"LFSbuttonBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type=radio]:checked~div' => 'background-color: {{VALUE}};'
					],
				],
				"LFSbuttonHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type=radio]:checked~div:hover' => 'background-color: {{VALUE}};'
					],
				],
				"LFSbuttonBorderStyle" =>
				[
					'label'		 => __( 'Border Style', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'border_styles' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'border_styles' ),
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type=radio]:checked~div' => 'border-style: {{VALUE}};'
					],
				],
				"LFSbuttonBorderWidth" =>
				[
					'label'	 => __( 'Border Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'LFSbuttonBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type=radio]:checked~div' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"LFSbuttonBorderColor" =>
				[
					'label'	 => __( 'Border Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'LFSbuttonBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type=radio]:checked~div' => 'border-color: {{VALUE}};'
					],
				],
				"LFSbuttonBorderRadius" =>
				[
					'label'	 => __( 'Border Radius', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter input[type=radio]:checked~div' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"LFSbuttonBoxShadow" =>
				[
					'name'				 => "LFSbuttonBoxShadow",
					'_cv_group_control'	 => 'box_shadow',
					'selector' => '{{WRAPPER}} .cvp-live-filter input[type=radio]:checked~div',
				]
				,
				"__heading6lfstyle" =>
				[
					'label'	 => __( 'Submit button', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				],
				"LFSsubmitAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-submit' => 'text-align: {{VALUE}};'
					],
				],
				"LFSsubmit" =>
				[
					'name'				 => "LFSsubmit",
					'_cv_group_control'	 => 'typography',
					'selector' => '{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-submit',
				]
				,
				"LFSsubmitColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-submit' => 'color: {{VALUE}};'
					],
				],
				"LFSsubmitHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-submit:hover' => 'color: {{VALUE}};'
					],
				],
				"LFSsubmitBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-submit' => 'background-color: {{VALUE}};'
					],
				],
				"LFSsubmitHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-submit:hover' => 'background-color: {{VALUE}};'
					],
				],
				"LFSsubmitMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-submit' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"LFSsubmitPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"__heading7lfstyle" =>
				[
					'label'	 => __( 'Reset button', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				],
				"LFSresetAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-reset' => 'text-align: {{VALUE}};'
					],
				],
				"LFSreset" =>
				[
					'name'				 => "LFSreset",
					'_cv_group_control'	 => 'typography',
					'selector' => '{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-reset',
				]
				,
				"LFSresetColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-reset' => 'color: {{VALUE}};'
					],
				],
				"LFSresetHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-reset:hover' => 'color: {{VALUE}};'
					],
				],
				"LFSresetBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-reset' => 'background-color: {{VALUE}};'
					],
				],
				"LFSresetHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-reset:hover' => 'background-color: {{VALUE}};'
					],
				],
				"LFSresetMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-reset' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"LFSresetPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						'{{WRAPPER}} .cvp-live-filter ~ .cvp-live-button .cvp-live-reset' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
			];
		}

		// Show Fields Position controls
		static function _fields_position_controls( $arr ) {

			$arr[ 'fieldsDesc0' ]		 = [
				'label'		 => '',
				'type'		 => \Elementor\Controls_Manager::RAW_HTML,
				'raw'		 => __( '<em>Drag & drop to change fields positions</em>', 'content-views-pro' ),
			];
			$arr[ 'fieldsPosition' ]		 = [
				'label'		 => '',
				'type'		 => 'contentviews-sortable',
				'options'	 => ContentViews_Block_Common::fields_sortable(),
				'multiple'	 => true,
				'show_label' => false,
			];
			$arr[ 'fieldsDesc1' ]			 = [
				'label'		 => '',
				'type'		 => \Elementor\Controls_Manager::RAW_HTML,
				'raw'		 => __( '(<b>Top Meta</b> shows above <b>Title</b>)', 'content-views-pro' ),
				'condition'	 => [
					'showTaxonomy'	 => 'yes',
					'showTitle'		 => 'yes',
					'taxoPosition'	 => 'above_title',
				],
			];
			$arr[ 'fieldsDesc2' ]			 = [
				'label'		 => '',
				'type'		 => \Elementor\Controls_Manager::RAW_HTML,
				'raw'		 => __( '(<b>Top Meta</b> shows below <b>Title</b>)', 'content-views-pro' ),
				'condition'	 => [
					'showTaxonomy'	 => 'yes',
					'showTitle'		 => 'yes',
					'taxoPosition'	 => 'below_title',
				],
			];
			$arr[ 'fieldsDesc3' ]			 = [
				'label'		 => '',
				'type'		 => \Elementor\Controls_Manager::RAW_HTML,
				'raw'		 => __( '(<b>Top Meta</b> shows over <b>Featured Image</b>)', 'content-views-pro' ),
				'condition'	 => [
					'showTaxonomy'	 => 'yes',
					'showThumbnail'	 => 'yes',
					'taxoPosition!'	 => [ 'above_title', 'below_title' ],
				],
			];

			return $arr;
		}

		// Add other sections controls
		static function add_other_sections( $_this, $key ) {
			// Add below this section
			if ( $key === 'title' ) {
				$prefix = ' .' . PT_CV_PREFIX;

				$sections = [
					[
						'woo_price',
						[
							'label'		 => '&emsp;&emsp;' . esc_html__( 'Woo - Price', 'content-views-query-and-display-post-page' ),
							'tab'		 => ContentViews_Elementor_Widget::_another_tab(),
							'conditions' => [
								'terms'			 => [
									[
										'name'		 => 'showWooPrice',
										'operator'	 => '!==',
										'value'		 => '',
									],
									ContentViews_Elementor_Widget::_woo_condition()
								],
							],
						],
						null,
						self::_woo_price_style_controls( $prefix ),
					],
					[
						'woo_atc',
						[
							'label'		 => '&emsp;&emsp;' . esc_html__( 'Woo - Add To Cart', 'content-views-query-and-display-post-page' ),
							'tab'		 => ContentViews_Elementor_Widget::_another_tab(),
							'conditions' => [
								'terms' => [
									[
										'name'		 => 'showWooATC',
										'operator'	 => '!==',
										'value'		 => '',
									],
									ContentViews_Elementor_Widget::_woo_condition()
								],
							],
						],
						null,
						self::_woo_atc_style_controls( $prefix ),
					],
				];

				foreach ( $sections as $section ) {
					ContentViews_Elementor_Function_Pro::register_section_controls( $_this, $section[ 0 ], $section[ 1 ], $section[ 2 ], $section[ 3 ] );
				}
			}
		}

		/**
		 * ############################################################
		 * ---------- WOO PRICE STYLE
		 * ############################################################
		 */
		static function _woo_price_style_controls( $prefix ) {

			return [
				"woopriceAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooprice" => 'text-align: {{VALUE}};'
					],
				],
				"wooprice" =>
				[
					'name'				 => "wooprice",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}wooprice",
				]
				,
				"woopriceColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooprice" => 'color: {{VALUE}};'
					],
				],
				"woopriceHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooprice:hover" => 'color: {{VALUE}};'
					],
				],
				"woopriceBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooprice" => 'background-color: {{VALUE}};'
					],
				],
				"woopriceHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooprice:hover" => 'background-color: {{VALUE}};'
					],
				],
				"woopriceMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooprice" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"woopricePadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooprice" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- WOO ADD-TO-CART STYLE
		 * ############################################################
		 */
		static function _woo_atc_style_controls( $prefix ) {

return [
				"wooatcAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooatc" => 'text-align: {{VALUE}};'
					],
				],
				"wooatc" =>
				[
					'name'				 => "wooatc",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}wooatc a",
				]
				,
				"wooatcColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooatc a" => 'color: {{VALUE}};'
					],
				],
				"wooatcHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooatc:hover a" => 'color: {{VALUE}};'
					],
				],
				"wooatcBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooatc a" => 'background-color: {{VALUE}};'
					],
				],
				"wooatcHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooatc:hover a" => 'background-color: {{VALUE}};'
					],
				],
				"wooatcMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooatc a" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"wooatcPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}wooatc a" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
			];
		}

	}

	ContentViews_Elementor_Hooks_Pro::init_hooks();
}