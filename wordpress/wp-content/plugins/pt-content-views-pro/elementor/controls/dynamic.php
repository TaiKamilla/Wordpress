<?php

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use \Elementor\Base_Data_Control;

class ContentViews_Elementor_Control_Dynamic extends Base_Data_Control {

	public function get_type() {
		return 'contentviews-dynamic';
	}

	public function enqueue() {
		wp_register_script( 'contentviews-dynamic', plugins_url( 'elementor/assets/js/control-dynamic.js', PT_CV_FILE_PRO ), [ 'jquery' ], PT_CV_VERSION_PRO, true );
		wp_localize_script(
		'contentviews-dynamic', 'contentviews_dynamic_localize', [
			'taxo_list' => PT_CV_Values::taxonomy_list( true ),
			'lftext_arr' => [ '_orderby' => __( 'Sort', 'content-views-pro' ), '_search' => __( 'Search', 'content-views-pro' ) ],
		]
		);
		wp_enqueue_script( 'contentviews-dynamic' );
	}

	public function content_template() {
		?>
		<# var relatedAttributes = {}, currentElementorAtts1 = elementor.panel.currentView.currentPageView.model.attributes.settings.attributes; #>
		<# relatedAttributes.lfSortOpts = currentElementorAtts1['lfSortOpts']; #>
		<# relatedAttributes.searchLfEnable = currentElementorAtts1['searchLfEnable']; #>
		<# relatedAttributes.sortCtf = currentElementorAtts1['sortCtf']; #>
		<# relatedAttributes.filterCtf = currentElementorAtts1['filterCtf']; #>
		<# Object.keys( contentviews_dynamic_localize.taxo_list ).map( ( taxo ) => {
			relatedAttributes[taxo + '__LfEnable'] = currentElementorAtts1[taxo + '__LfEnable'];
		} ); #>
		<div class="elementor-control-field">
			<div class="elementor-control-input-wrapper elementor-control-unit-5">
				<input id="<?php $this->print_control_uid(); ?>" type="hidden" data-setting="{{ data.name }}" />
			</div>
		</div>
		<#
		( function( $ ) {
		$( document.body ).trigger( 'contentviews_dynamic_init',{currentValue:data.controlValue,data:data,relatedAtts:relatedAttributes} );
		}( jQuery ) );
		#>
		<?php

	}

}
