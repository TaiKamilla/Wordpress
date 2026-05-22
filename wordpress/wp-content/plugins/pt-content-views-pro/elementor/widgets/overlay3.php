<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_Overlay3 extends ContentViews_Elementor_Widget {

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Overlay 3';
	}

	public function _layout_custom() {
		$atts = [
			'columns'		 => [
				'default'		 => '4',
				'tablet_default' => '4',
				'mobile_default' => '1',
			],
			'postsPerPage'	 => [
				'default' => 4,
			],
		];

		return array_replace_recursive( ContentViews_Elementor_Widget_Overlay2::special_overlay_atts(), $atts );
	}

}
