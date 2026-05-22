<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_Timeline extends ContentViews_Elementor_Widget {

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Timeline';
	}

	public function _layout_custom() {
		$atts = [
			'viewType'	 => [
				'default' => 'timeline',
			],
			'showMeta'	 => [
				'default' => self::$switchOn,
			],
		];

		return $atts;
	}

}
