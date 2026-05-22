<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}


if ( !class_exists( 'ContentViews_Elementor_Init' ) ) {

	class ContentViews_Elementor_Init {

		const MINIMUM_ELEMENTOR_VERSION = '3.5.0';

		private static $instance = null;

		static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		function __construct() {
			if ( $this->is_compatible() ) {
				add_action( 'elementor/init', [ $this, 'init_components' ] );

				// Hooks
				include_once dirname( __FILE__ ) . '/_hooks.php';
				ContentViews_Elementor_Hooks::init_hooks();
			}
		}

		public function is_compatible() {

			return true;
		}

		/**
		 * Load the addons functionality only after Elementor is initialized.
		 */
		function init_components() {
			add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
			add_action( 'elementor/controls/register', [ $this, 'register_new_controls' ] );
			add_action( 'elementor/elements/categories_registered', [ $this, 'add_elementor_widget_categories' ], 0 );
			add_filter( 'elementor/editor/localize_settings', [ $this, 'promote_pro_widgets' ] );
			
			add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'add_editor_styles' ] );
			add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'add_editor_scripts' ] );
			add_action( 'elementor/preview/enqueue_styles', [ $this, 'add_preview_styles' ] );
		}

		/**
		 * Register Widgets
		 * Load widgets files and register new Elementor widgets.
		 */
		public function register_widgets( $widgets_manager ) {
			include_once dirname( __FILE__ ) . '/render.php';
			include_once dirname( __FILE__ ) . '/style.php';
			include_once dirname( __FILE__ ) . '/widget.php';

			foreach ( glob( dirname( __FILE__ ) . '/widgets/*.php' ) as $file ) {
				include_once $file;

				$filename	 = basename( $file, '.php' );
				$classname	 = 'ContentViews_Elementor_Widget_' . ucfirst( $filename );
				if ( class_exists( $classname, false ) ) {
					$widgets_manager->register( new $classname() );
				}
			}
		}

		// Register controls
		public function register_new_controls( $controls_manager ) {
			foreach ( glob( dirname( __FILE__ ) . '/controls/*.php' ) as $file ) {
				include_once $file;

				$filename	 = basename( $file, '.php' );
				$classname	 = 'ContentViews_Elementor_Control_' . ucfirst( $filename );
				if ( class_exists( $classname, false ) ) {
					$controls_manager->register( new $classname() );
				}
			}
		}

		// Register widget categories
		public function add_elementor_widget_categories( $elements_manager ) {
			$categories = [];

			$categories[ 'contentviews-elementor' ]	 = [
				'title'	 => 'Content Views',
				'icon'	 => 'fa fa-plug',
			];

			$old_categories	 = $elements_manager->get_categories();
			$categories		 = array_merge( $categories, $old_categories );

			$set_categories = function ( $categories ) {
				$this->categories = $categories;
			};

			$set_categories->call( $elements_manager, $categories );
		}

		// Promote pro widgets
		public function promote_pro_widgets( $config ) {
			$hasPro = PT_CV_Functions::has_pro();

			if ( $hasPro ) {
				return $config;
			}

			$promotion_widgets = [];

			if ( isset( $config[ 'promotionWidgets' ] ) ) {
				$promotion_widgets = $config[ 'promotionWidgets' ];
			}
			
			$pro_widgets = [];
			$arr		 = [ 'big-post-1' => 'Big Post 1', 'big-post-2' => 'Big Post 2', 'overlay-2' => 'Overlay 2', 'overlay-3' => 'Overlay 3', 'overlay-4' => 'Overlay 4', 'overlay-5' => 'Overlay 5', 'overlay-6' => 'Overlay 6', 'overlay-7' => 'Overlay 7', 'overlay-8' => 'Overlay 8', 'pinterest' => 'Pinterest', 'timeline' => 'Timeline' ];
			foreach ( $arr as $key => $name ) {
				$pro_widgets[] = [
					'name'		 => "contentviews_widget_{$key}",
					'title'		 => $name,
					'icon'		 => "contentviews_widget_{$key} $key",
					'categories' => '["contentviews-elementor"]',
				];
			}

			$combine_array = array_merge( $promotion_widgets, $pro_widgets );

			$config[ 'promotionWidgets' ] = $combine_array;

			return $config;
		}

		// Add editor/control styles
		public function add_editor_styles() {
			wp_register_style( 'contentviews-for-controls', plugins_url( 'assets/css/widget.css', __FILE__ ) );
			wp_enqueue_style( 'contentviews-for-controls' );
		}

		// Add editor/control scripts
		public function add_editor_scripts() {
			wp_register_script( 'contentviews-js-controls', plugins_url( 'assets/js/widget.js', __FILE__ ) );
			wp_enqueue_script( 'contentviews-js-controls' );
		}

		// Add Preview styles
		public function add_preview_styles() {
			wp_register_style( 'contentviews-for-preview', plugins_url( 'assets/css/preview.css', __FILE__ ) );
			wp_enqueue_style( 'contentviews-for-preview' );
		}

		// Differentiate it from block/shortcode
		static function is_widget( $arr = null ) {
			if ( $arr === null ) {
				$value = PT_CV_Functions::setting_value( PT_CV_PREFIX . 'isElementorWidget' );
			} else {
				$value = isset( $arr[ 'isElementorWidget' ] ) ? $arr[ 'isElementorWidget' ] : null;
			}
			return !empty( $value );
		}

	}

}

ContentViews_Elementor_Init::get_instance();
