<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_Overlay6 extends ContentViews_Elementor_Widget {

	protected static $sameAs = 'overlay6';

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Overlay 6';
	}

	public function _layout_custom() {
		return self::ovl6_atts();
	}

	static function ovl6_atts() {
		$atts = [
			'columns'		 => [
				'default'		 => '2',
				'tablet_default' => '2',
				'mobile_default' => '1',
			],
			'postsPerPage'	 => [
				'default' => 5,
			],
			'isSpec'		 => [
				'default' => '',
			],
			'sameAs'		 => [
				'default' => 'overlay6',
			],
		];

		return array_replace_recursive( ContentViews_Elementor_Widget_Overlay2::special_overlay_atts(), $atts );
	}

}
