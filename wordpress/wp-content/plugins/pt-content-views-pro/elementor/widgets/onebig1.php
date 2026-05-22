<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class ContentViews_Elementor_Widget_OneBig1 extends ContentViews_Elementor_Widget {

	public function get_name() {
		return 'contentviews_widget_' . basename( __FILE__, '.php' );
	}

	public function _get_widgetName() {
		return basename( __FILE__, '.php' );
	}

	// Change for each widget
	public function get_title() {
		return 'Big Post 1';
	}

	public function _layout_custom() {
		$atts							 = self::onebig_atts();
		$atts[ 'imgSize' ][ 'default' ]	 = 'full';
		return $atts;
	}

	static function onebig_atts() {
		$atts = [
			'viewType'				 => [
				'default' => 'onebig',
			],
			'columns'				 => [
				'default'		 => '1',
				'tablet_default' => '1',
				'mobile_default' => '1',
			],
			// hide Columns option. Show others* options
			'hasOne'				 => [
				'default' => '1',
			],
			'onePosition'			 => [
				'default' => 'above-others',
			],
			'showThumbnailOthers'	 => [
				'default' => self::$switchOn,
			],
			'showTaxonomyOthers'	 => [
				'default' => self::$switchOn,
			],
			'showContentOthers'		 => [
				'default' => self::$switchOn,
			],
			'showReadmoreOthers'	 => [
				'default' => self::$switchOn,
			],
			'showMeta'				 => [
				'default' => self::$switchOn,
			],
			'showMetaOthers'		 => [
				'default' => self::$switchOn,
			],
			'imgSize'				 => [
				'default' => 'large',
			],
			'formatWrap'			 => [
				'default' => 'yes',
			],
			'thumbPosition'			 => [
				'default' => 'left',
			],
			'thumbPositionOthers'	 => [
				'default' => 'left',
			],
			'thumbnailsmMaxWidth'	 => [
				'default' => [
					'size'	 => 40,
					'unit'	 => '%',
				],
			],
			'excerptLengthOthers'	 => [
				'default'	 => [
					'size'	 => 15,
					'unit'	 => 'px',
				],
			],
		];


		return $atts;
	}

}
