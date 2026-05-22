<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_List1 extends ContentViews_Elementor_Widget {

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'List';
	}

	public function _layout_custom() {
		$atts = [
			'layoutFormat'		 => [
				'default' => '2-col',
			],
			'columns'			 => [
				'default'		 => '1',
				'tablet_default' => '1',
				'mobile_default' => '1',
			],
			'formatWrap'		 => [
				'default' => 'yes',
			],
			'zigzag'			 => [
				'default' => '',
			],
			'thumbPosition'		 => [
				'default' => 'left',
			],
			'thumbnailMaxWidth'	 => [
				'default' => [
					'size'	 => 40,
					'unit'	 => '%',
				],
			],
			'thumbnailHeight'	 => [
				'default' => [
					'size'	 => 300,
					'unit'	 => 'px',
				],
			],
		];

		return $atts;
	}

}
