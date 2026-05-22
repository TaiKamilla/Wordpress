<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_Overlay8 extends ContentViews_Elementor_Widget {

	protected static $sameAs = 'overlay6';

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Overlay 8';
	}

	public function _layout_custom() {
		return ContentViews_Elementor_Widget_Overlay6::ovl6_atts();
	}

}
