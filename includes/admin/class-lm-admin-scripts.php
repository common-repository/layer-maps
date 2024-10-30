<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('LM_Admin_Scripts')) {

	class LM_Admin_Scripts {

		public static function init() {

			add_action( 'admin_enqueue_scripts', array(__CLASS__, 'load_scripts') );
		}

		public static function load_scripts() {

			wp_enqueue_style('dashicons');
			wp_enqueue_style('lm-styles', Layer_Maps()->plugin_url() . '/assets/css/lm-admin-styles.css', array(), Layer_Maps()->version);

			wp_enqueue_script('lm-scripts', Layer_Maps()->plugin_url() . '/assets/js/lm-admin-scripts.js', array('jquery'), Layer_Maps()->version, true);
		}

	}

}

LM_Admin_Scripts::init();
