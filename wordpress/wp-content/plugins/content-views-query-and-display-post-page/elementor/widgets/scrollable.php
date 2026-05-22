<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_Scrollable extends ContentViews_Elementor_Widget {

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Scrollable';
	}

	public function _layout_custom() {
		$atts = [
			'viewType'			 => [
				'default' => 'scrollable',
			],
			'columns'			 => [
				'default'		 => '2',
				'mobile_default' => '1',
			],
			'thumbnailHeight'	 => [
				'default' => [
					'size'	 => 300,
					'unit'	 => 'px',
				],
			],
			'thumbnailMaxWidth'	 => [
				'default' => [
					'size'	 => 100,
					'unit'	 => '%',
				],
			],
			'rowNum'			 => [
				'default' => [
					'size'	 => 1,
					'unit'	 => 'px',
				],
			],
			'slideNum'			 => [
				'default' => [
					'size'	 => 3,
					'unit'	 => 'px',
				],
			],
			'scrollNav'			 => [
				'default' => self::$switchOn,
			],
			'scrollIndi'		 => [
				'default' => self::$switchOn,
			],
			'scrollInterval'	 => [
				'default' => [
					'size'	 => 5,
					'unit'	 => 'px',
				],
			],
		];

		return $atts;
	}

}
