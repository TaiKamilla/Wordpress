<?php
// Define styles controls

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'ContentViews_Elementor_Style_Controls' ) ) {

	class ContentViews_Elementor_Style_Controls {

		// Get default option for select - redeclare here to use self::
		static function _default_option( $arr ) {
			return PT_CV_Functions::array_get_first_key( $arr );
		}


		/**
		 * ############################################################
		 * ---------- HEADING
		 * ############################################################
		 */
		static function _heading_style_controls( $localize_info, $prefix ) {

			return [
				"headingAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						"{{WRAPPER}} {$prefix}heading-container" => 'text-align: {{VALUE}};'
					],
				],
				"heading" =>
				[
					'name'				 => "heading",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}heading-container *",
				]
				,
				"headingColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}heading-container *" => 'color: {{VALUE}};'
					],
				],
				"headingHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}heading-container:hover *" => 'color: {{VALUE}};'
					],
				],
				"headingBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}heading-container *" => 'background-color: {{VALUE}};'
					],
				],
				"headingHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}heading-container:hover *" => 'background-color: {{VALUE}};'
					],
				],
				"headingMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}heading-container *" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"headingPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}heading-container *" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"headingBorderColor" =>
				[
					'label'	 => __( 'Border Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'condition' => [
						'headingStyle!' => 'heading1',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}heading-container" => 'border-color: {{VALUE}};'
					],
				],
				"headingBorderWidth" =>
				[
					'label'	 => __( 'Border Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'condition' => [
						'headingStyle!' => 'heading1',
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}heading-container" => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- THUMBNAIL
		 * ############################################################
		 */
		static function _thumbnail_style_controls( $localize_info, $prefix ) {

			return [
				"thumbnailAllBorderStyle" =>
				[
					'label'		 => __( 'Border Style', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'border_styles' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'border_styles' ),
					'selectors' => [
						"{{WRAPPER}} {$prefix}view[class*='cveffect'] {$prefix}thumb-wrapper, {{WRAPPER}} {$prefix}view:not([class*='cveffect']) {$prefix}thumbnail" => 'border-style: {{VALUE}};'
					],
				],
				"thumbnailAllBorderWidth" =>
				[
					'label'	 => __( 'Border Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'thumbnailAllBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}view[class*='cveffect'] {$prefix}thumb-wrapper, {{WRAPPER}} {$prefix}view:not([class*='cveffect']) {$prefix}thumbnail" => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"thumbnailAllBorderColor" =>
				[
					'label'	 => __( 'Border Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'thumbnailAllBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}view[class*='cveffect'] {$prefix}thumb-wrapper, {{WRAPPER}} {$prefix}view:not([class*='cveffect']) {$prefix}thumbnail" => 'border-color: {{VALUE}};'
					],
				],
				"thumbnailAllBorderRadius" =>
				[
					'label'	 => __( 'Border Radius', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}view[class*='cveffect'] {$prefix}thumb-wrapper, {{WRAPPER}} {$prefix}view:not([class*='cveffect']) {$prefix}thumbnail" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"thumbnailAllBoxShadow" =>
				[
					'name'				 => "thumbnailAllBoxShadow",
					'_cv_group_control'	 => 'box_shadow',
					'selector' => "{{WRAPPER}} {$prefix}thumbnail",
				]
				,
				"thumbnailAllMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}thumb-wrapper" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"thumbnailAllPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}thumbnail" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- TITLE
		 * ############################################################
		 */
		static function _title_style_controls( $localize_info, $prefix ) {

			return [
				"titleAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						"{{WRAPPER}} {$prefix}title a" => 'text-align: {{VALUE}}; display: block;'
					],
				],
				"title" =>
				[
					'name'				 => "title",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}title:not({$prefix}titlesm) a",
				]
				,
				"titlesm" =>
				[
					'name'				 => "titlesm",
					'_cv_group_control'	 => 'typographysm',
					'condition' => [
						'hasOne' => '1',
					],
					'selector' => "{{WRAPPER}} {$prefix}titlesm a",
				]
				,
				"titleColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}title a" => 'color: {{VALUE}};'
					],
				],
				"titleHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}title:hover a" => 'color: {{VALUE}};'
					],
				],
				"titleBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}title a" => 'background-color: {{VALUE}};'
					],
				],
				"titleHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}title:hover a" => 'background-color: {{VALUE}};'
					],
				],
				"titleMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}title a" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; display: block;'
					],
				],
				"titlePadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}title a" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; display: block;'
					],
				],
				"titleBorderStyle" =>
				[
					'label'		 => __( 'Border Style', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'border_styles' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'border_styles' ),
					'selectors' => [
						"{{WRAPPER}} {$prefix}title a" => 'border-style: {{VALUE}};'
					],
				],
				"titleBorderWidth" =>
				[
					'label'	 => __( 'Border Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'titleBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}title a" => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"titleBorderColor" =>
				[
					'label'	 => __( 'Border Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'titleBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}title a" => 'border-color: {{VALUE}};'
					],
				],
				"titleBorderRadius" =>
				[
					'label'	 => __( 'Border Radius', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}title a" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"titleBoxShadow" =>
				[
					'name'				 => "titleBoxShadow",
					'_cv_group_control'	 => 'box_shadow',
					'selector' => "{{WRAPPER}} {$prefix}title a",
				]
			,
			];
		}

		/**
		 * ############################################################
		 * ---------- TOP META
		 * ############################################################
		 */
		static function _topmeta_style_controls( $localize_info, $prefix ) {

			return [
				"taxoterm" =>
				[
					'name'				 => "taxoterm",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}taxoterm *",
				]
				,
				"taxotermColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm *" => 'color: {{VALUE}};'
					],
				],
				"taxotermHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm:hover *" => 'color: {{VALUE}};'
					],
				],
				"taxotermBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm *" => 'background-color: {{VALUE}};'
					],
				],
				"taxotermHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm:hover *" => 'background-color: {{VALUE}};'
					],
				],
				"taxotermMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"taxotermPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm *" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"taxotermBorderStyle" =>
				[
					'label'		 => __( 'Border Style', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'border_styles' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'border_styles' ),
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm *" => 'border-style: {{VALUE}};'
					],
				],
				"taxotermBorderWidth" =>
				[
					'label'	 => __( 'Border Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'taxotermBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm *" => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"taxotermBorderColor" =>
				[
					'label'	 => __( 'Border Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'taxotermBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm *" => 'border-color: {{VALUE}};'
					],
				],
				"taxotermBorderRadius" =>
				[
					'label'	 => __( 'Border Radius', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}taxoterm *" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"taxotermBoxShadow" =>
				[
					'name'				 => "taxotermBoxShadow",
					'_cv_group_control'	 => 'box_shadow',
					'selector' => "{{WRAPPER}} {$prefix}taxoterm *",
				]
			,
			];
		}

		/**
		 * ############################################################
		 * ---------- CUSTOM FIELD
		 * ############################################################
		 */
		static function _customfield_style_controls( $localize_info, $prefix ) {

			return [
				"custom__fieldsAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						"{{WRAPPER}} {$prefix}custom-fields" => 'text-align: {{VALUE}};'
					],
				],
				"custom__fields" =>
				[
					'name'				 => "custom__fields",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}custom-fields",
				]
				,
				"custom__fieldsColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}custom-fields" => 'color: {{VALUE}};'
					],
				],
				"custom__fieldsHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}custom-fields:hover" => 'color: {{VALUE}};'
					],
				],
				"custom__fieldsBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}custom-fields" => 'background-color: {{VALUE}};'
					],
				],
				"custom__fieldsHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}custom-fields:hover" => 'background-color: {{VALUE}};'
					],
				],
				"custom__fieldsMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}custom-fields" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"custom__fieldsPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}custom-fields" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- CONTENT
		 * ############################################################
		 */
		static function _content_style_controls( $localize_info, $prefix ) {

			return [
				"contentAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						"{{WRAPPER}} {$prefix}content" => 'text-align: {{VALUE}};'
					],
				],
				"content" =>
				[
					'name'				 => "content",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}content",
				]
				,
				"contentColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}content" => 'color: {{VALUE}};'
					],
				],
				"contentHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}content:hover" => 'color: {{VALUE}};'
					],
				],
				"contentBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}content" => 'background-color: {{VALUE}};'
					],
				],
				"contentHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}content:hover" => 'background-color: {{VALUE}};'
					],
				],
				"contentMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}content" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"contentPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}content" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- READ MORE
		 * ############################################################
		 */
		static function _readmore_style_controls( $localize_info, $prefix ) {

			return [
				"readmoreAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						"{{WRAPPER}} {$prefix}rmwrap" => 'text-align: {{VALUE}};'
					],
				],
				"readmore" =>
				[
					'name'				 => "readmore",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}readmore",
				]
				,
				"readmoreColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore" => 'color: {{VALUE}};'
					],
				],
				"readmoreHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore:hover" => 'color: {{VALUE}};'
					],
				],
				"readmoreBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore" => 'background-color: {{VALUE}};'
					],
				],
				"readmoreHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore:hover" => 'background-color: {{VALUE}};'
					],
				],
				"readmoreMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"readmorePadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"readmoreBorderStyle" =>
				[
					'label'		 => __( 'Border Style', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'border_styles' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'border_styles' ),
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore" => 'border-style: {{VALUE}};'
					],
				],
				"readmoreBorderWidth" =>
				[
					'label'	 => __( 'Border Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'readmoreBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore" => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"readmoreBorderColor" =>
				[
					'label'	 => __( 'Border Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'readmoreBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore" => 'border-color: {{VALUE}};'
					],
				],
				"readmoreBorderRadius" =>
				[
					'label'	 => __( 'Border Radius', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}readmore" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"readmoreBoxShadow" =>
				[
					'name'				 => "readmoreBoxShadow",
					'_cv_group_control'	 => 'box_shadow',
					'selector' => "{{WRAPPER}} {$prefix}readmore",
				]
			,
			];
		}

		/**
		 * ############################################################
		 * ---------- BOTTOM META
		 * ############################################################
		 */
		static function _bottom_meta_style_controls( $localize_info, $prefix ) {

			return [
				"meta__fieldsAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						"{{WRAPPER}} {$prefix}meta-fields" => 'text-align: {{VALUE}};'
					],
				],
				"meta__fields" =>
				[
					'name'				 => "meta__fields",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}meta-fields *",
				]
				,
				"meta__fieldsColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}meta-fields *" => 'color: {{VALUE}}!important;'
					],
				],
				"meta__fieldsHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}meta-fields:hover *" => 'color: {{VALUE}};'
					],
				],
				"meta__fieldsBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}meta-fields" => 'background-color: {{VALUE}};'
					],
				],
				"meta__fieldsHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}meta-fields:hover" => 'background-color: {{VALUE}};'
					],
				],
				"meta__fieldsMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}meta-fields" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"meta__fieldsPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}meta-fields" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
			];
		}

		/**
		 * ############################################################
		 * ---------- PAGINATION
		 * ############################################################
		 */
		static function _pagination_style_controls( $localize_info, $prefix ) {

			return [
				"paginationAlign" =>
				[
					'label'		 => __( 'Alignment', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::CHOOSE,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'alignment' ),
					'default'	 => '',
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper" => 'text-align: {{VALUE}};'
					],
				],
				"pagination" =>
				[
					'name'				 => "pagination",
					'_cv_group_control'	 => 'typography',
					'selector' => "{{WRAPPER}} {$prefix}pagination-wrapper a",
				]
				,
				"paginationColor" =>
				[
					'label'	 => __( 'Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper a" => 'color: {{VALUE}};'
					],
				],
				"paginationHoverColor" =>
				[
					'label'	 => __( 'Hover Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper a:hover" => 'color: {{VALUE}};'
					],
				],
				"paginationBgColor" =>
				[
					'label'	 => __( 'Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper a" => 'background-color: {{VALUE}};'
					],
				],
				"paginationHoverBgColor" =>
				[
					'label'	 => __( 'Hover Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper a:hover" => 'background-color: {{VALUE}};'
					],
				],
				"paginationMargin" =>
				[
					'label'	 => __( 'Margin', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"paginationPadding" =>
				[
					'label'	 => __( 'Padding', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper a" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"paginationActiveBgColor" =>
				[
					'label'	 => __( 'Active Background Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper .active a" => 'background-color: {{VALUE}};'
					],
				],
				"paginationBorderStyle" =>
				[
					'label'		 => __( 'Border Style', 'content-views-query-and-display-post-page' ),
					'type'		 => \Elementor\Controls_Manager::SELECT,
					'options'	 => ContentViews_Elementor_Widget::_get_options( 'border_styles' ),
					'default'	 => ContentViews_Elementor_Widget::_get_default_val( 'border_styles' ),
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper a" => 'border-style: {{VALUE}};'
					],
				],
				"paginationBorderWidth" =>
				[
					'label'	 => __( 'Border Width', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'paginationBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper a" => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"paginationBorderColor" =>
				[
					'label'	 => __( 'Border Color', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::COLOR,
					'conditions' => [
						'terms' => [
							[
								'name'		 => 'paginationBorderStyle',
								'operator'	 => '!in',
								'value'		 => [ 'none', '' ],
							],
						],
					],
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper a" => 'border-color: {{VALUE}};'
					],
				],
				"paginationBorderRadius" =>
				[
					'label'	 => __( 'Border Radius', 'content-views-query-and-display-post-page' ),
					'type'	 => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors' => [
						"{{WRAPPER}} {$prefix}pagination-wrapper a" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
				],
				"paginationBoxShadow" =>
				[
					'name'				 => "paginationBoxShadow",
					'_cv_group_control'	 => 'box_shadow',
					'selector' => "{{WRAPPER}} {$prefix}pagination-wrapper a",
				]
			,
			];
		}

	}

}