<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_Overlay2 extends ContentViews_Elementor_Widget {

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Overlay 2';
	}

	public function _layout_custom() {
		return self::special_overlay_atts();
	}

	static function special_overlay_atts() {
		$atts = [
			'columns'					 => [
				'default'		 => '2',
				'tablet_default' => '2',
				'mobile_default' => '1',
			],
			'postsPerPage'				 => [
				'default' => 3,
			],
			'thumbnailsmMaxWidth'		 => [
				'default' => [],
			],
			'formatWrap'				 => [
				'default' => '',
			],
			'isSpec'				 => [
				'default'	 => '1',
			],
			'thumbnailHeight'		 => [
				'default'	 => [
					'size'	 => 350,
					'unit'	 => 'px',
				],
			],
			'thumbnailsmHeight'		 => [
				'default'	 => [
					'size'	 => 175,
					'unit'	 => 'px',
				],
			],
		];

		return array_replace_recursive( ContentViews_Elementor_Widget_OneBig1::onebig_atts(), ContentViews_Elementor_Widget_Overlay1::overlay_atts(), $atts );
	}

}
