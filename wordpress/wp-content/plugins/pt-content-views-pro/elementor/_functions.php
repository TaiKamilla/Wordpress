<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}


if ( !class_exists( 'ContentViews_Elementor_Function_Pro' ) ) {

	class ContentViews_Elementor_Function_Pro {

		// Same as checkLiveFilter() in block js
		static function check_has_livefilter( $arr_data ) {

			$obj = array();

			$lf1 = !empty( $arr_data[ 'lfSortOpts' ] ) ? $arr_data[ 'lfSortOpts' ] : null;
			if ( is_array( $lf1 ) && count( $lf1 ) > 0 ) {
				$obj[ '_orderby' ] = 1;
			}

			if ( !empty( $arr_data[ 'searchLfEnable' ] ) ) {
				$obj[ '_search' ] = 1;
			}

			foreach ( [ 'sortCtf', 'filterCtf' ] as $akey ) {
				$keyVal = !empty( $arr_data[ $akey ] ) ? $arr_data[ $akey ] : null;
				if ( is_array( $keyVal ) && count( $keyVal ) > 0 ) {
					foreach ( $keyVal as $item ) {
						if ( isset( $item[ 'key' ] ) && !empty( $item[ 'lfenable' ] ) ) {
							$obj[ $akey === 'sortCtf' ? '_orderby' : $item[ 'key' ] ] = 1;
						}
					}
				}
			}

			$taxos = PT_CV_Values::taxonomy_list( true );
			foreach ( (array) array_keys( $taxos ) as $taxonomy ) {
				if ( !empty( $arr_data[ $taxonomy . '__LfEnable' ] ) ) {
					$obj[ 'tx_' . $taxonomy ] = 1;
				}
			}

			return $obj;
		}

		// Register section with controls
		static function register_section_controls( $_this, $key, $section_info, $general_controls, $style_controls ) {
			$_this->start_controls_section(
			"contentviews_section_{$key}", $section_info
			);
			
			$_this->start_controls_tabs( "{$key}_tabs" );

			// Tab General
			if ( !empty( $general_controls ) ) {
				$_this->start_controls_tab( "{$key}_general_tab", [ 'label' => esc_html__( 'General', 'content-views-query-and-display-post-page' ), ] );
				$_this->_add_controls( $general_controls );
				$_this->end_controls_tab();
			}

			// Tab Style
			if ( !empty( $style_controls ) ) {
				$_this->start_controls_tab( "{$key}_style_tab", [ 'label' => esc_html__( 'Style', 'content-views-query-and-display-post-page' ), ] );
				$_this->_add_controls( $style_controls );
				$_this->end_controls_tab();
			}

			$_this->end_controls_tabs();

			$_this->end_controls_section();
		}

	}

}