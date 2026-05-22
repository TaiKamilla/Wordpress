<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_Overlay1 extends ContentViews_Elementor_Widget {

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Overlay 1';
	}

	public function _layout_custom() {
		return self::overlay_atts();
	}

	static function overlay_atts() {
		$atts = [
			'viewType'			 => [
				'default' => 'overlaygrid',
			],
			'gridGap'			 => [
				'default' => [
					'size'	 => 10,
					'unit'	 => 'px',
				],
			],
			'overlaid'			 => [
				'default' => self::$switchOn,
			],
			'overlayClickable'	 => [
				'default' => self::$hasPro ? self::$switchOn : self::$switchOff,
			],
			'overOnHover'		 => [
				'default' => self::$switchOff,
			],
			'overlayType'		 => [
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color'		 => [
						'default' => '#0006',
					],
					'color_b'	 => [
						'default' => '#000c',
					],
				],
			],
			'overlayOpacity'	 => [
				'default' => [
					'size'	 => 0.8,
					'unit'	 => 'px',
				],
			],
			'overlayPosition'	 => [
				'default' => 'middle',
			],
			'showContent'		 => [
				'default' => self::$switchOff,
			],
			'showMeta'			 => [
				'default' => self::$switchOn,
			],
			'showReadmore'		 => [
				'default' => self::$switchOff,
			],
			'hetargetHeight'	 => [
				'default' => [
					'size'	 => 250,
					'unit'	 => 'px',
				],
			],
		];

		return $atts;
	}

}
