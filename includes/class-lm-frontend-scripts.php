<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('LM_Frontend_Scripts')) {

	class LM_Frontend_Scripts {

		public static function init() {

			add_action( 'wp_enqueue_scripts', array(__CLASS__, 'load_scripts') );

		}

		public static function load_scripts() {

			wp_enqueue_style('lm-styles', Layer_Maps()->plugin_url() . '/assets/css/lm-styles.css', array(), Layer_Maps()->version);

			wp_enqueue_script('lm-markerclusterer', Layer_Maps()->plugin_url() . '/assets/js/vendor/js-marker-clusterer/src/markerclusterer_compiled.js', array('jquery'), Layer_Maps()->version, true);
			wp_enqueue_script('lm-scripts', Layer_Maps()->plugin_url() . '/assets/js/lm-scripts.js', array('jquery'), Layer_Maps()->version, true);

			wp_enqueue_script('lm-googlemaps', '//maps.googleapis.com/maps/api/js?key=' . Layer_Maps()->get_option('api_key'), array(), Layer_Maps()->version, true);

			wp_localize_script( 'lm-scripts', 'layermaps_options', array(
				'enable_clustering' => Layer_Maps()->get_option('enable_clustering')
			));

			wp_localize_script( 'lm-scripts', 'layermaps_params', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'plugin_url' => Layer_Maps()->plugin_url()
			));
		}

	}

}

LM_Frontend_Scripts::init();
