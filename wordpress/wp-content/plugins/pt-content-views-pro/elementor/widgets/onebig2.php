<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_OneBig2 extends ContentViews_Elementor_Widget {

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Big Post 2';
	}

	public function _layout_custom() {
		$atts = [
			'onePosition'				 => [
				'default' => 'beside-others',
			],
			'oneWidth'					 => [
				'default'	 => '50%',
			],
			'swapPosition'				 => [
				'default' => self::$switchOff,
			],
			'showThumbnailOthers'		 => [
				'default' => self::$switchOff,
			],
			'showTaxonomyOthers'		 => [
				'default' => self::$switchOff,
			],
			'showContentOthers'			 => [
				'default' => self::$switchOff,
			],
			'showReadmoreOthers'		 => [
				'default' => self::$switchOff,
			],
			'thumbnailsmMaxWidth'		 => [
				'default'	 => [
					'size'	 => 100,
					'unit'	 => 'px',
				],
			],
		];


		return array_replace_recursive( ContentViews_Elementor_Widget_OneBig1::onebig_atts(), $atts );
	}

}
