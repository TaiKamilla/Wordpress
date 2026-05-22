<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_Collapsible extends ContentViews_Elementor_Widget {

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Collapsible';
	}

	public function _layout_custom() {
		$atts = [
			'viewType'			 => [
				'default' => 'collapsible',
			],
			'openFirst'			 => [
				'default' => 'yes',
			],
		];



		return $atts;
	}

}
