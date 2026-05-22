<?php

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use \Elementor\Base_Data_Control;

class ContentViews_Elementor_Control_Creatable extends Base_Data_Control {

	public function get_type() {
		return 'contentviews-creatable';
	}

	public function enqueue() {
		wp_register_script( 'contentviews-creatable', plugins_url( 'elementor/assets/js/control-creatable.js', PT_CV_FILE ), [ 'jquery-elementor-select2' ], PT_CV_VERSION, true );
		wp_localize_script(
		'contentviews-creatable', 'contentviews_creatable_localize', [
			'ajaxurl' => esc_url( admin_url( 'admin-ajax.php' ) ),
		]
		);
		wp_enqueue_script( 'contentviews-creatable' );
	}

	protected function get_default_settings() {
		return [
			'multiple' => true,
		];
	}

	public function content_template() {
		$control_uid = $this->get_control_uid();
		?>

		<# var currentID = elementor.panel.currentView.currentPageView.model.attributes.settings.attributes[data.name]; #>
		<div class="elementor-control-field">
			<# if ( data.label ) { #>
			<label for="<?php echo esc_attr( $control_uid ); ?>" class="elementor-control-title">{{{data.label }}}</label>
			<# } #>
			<div class="elementor-control-input-wrapper elementor-control-unit-5">
				<# var multiple = ( data.multiple ) ? 'multiple' : ''; #>
				<select id="<?php echo esc_attr( $control_uid ); ?>" {{ multiple }} class="ea-select2" data-setting="{{ data.name }}"></select>
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<#
		( function( $ ) {
		$( document.body ).trigger( 'contentviews_creatable_init',{currentID:data.controlValue,data:data,multiple:data.multiple} );
		}( jQuery ) );
		#>
		<?php
	}

}
