<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('LM_Admin_Taxonomies')) {

	class LM_Admin_Taxonomies {

		/**
		 * Single instance of the class
		 */
		protected static $_instance = null;

		/**
		 * Instance of the class
		 */
		public static function instance() {
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Initiate the plugin by setting up actions and filters
		 */
		public function __construct() {


		}



	}

}
