<?php
/*
Plugin Name: Layer Maps
Plugin URI: https://www.ecommnet.uk/layer-maps/
Description: Simple Google Maps plugin to create layers, add pins and display mapping data.
Version: 1.2
Author: Ecommnet
Author URI: https://www.ecommnet.uk/
Text Domain: layer-maps
*/

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('Layer_Maps')) {

	class Layer_Maps {

		const MAP_POST_TYPE = 'layermaps_map';
		const PIN_POST_TYPE = 'layermaps_pin';
		const LAYER_TAXONOMY = 'layermaps_layer';

		protected $plugin_options = '_layermaps_settings';

		protected $option_defaults = array(
			'api_key' => '',
			'enable_clustering' => false
		);

		protected $map_post_type_args = array(
			'public'        =>   true,
			'show_in_menu'  =>   true,
			'menu_icon'     =>   'dashicons-location',
			'has_archive'   =>   true,
			'rewrite'       =>   true,
			'supports'      =>   array('title', 'editor'),
			'taxonomies' 	=>	 array(self::LAYER_TAXONOMY)
		);

		protected $pin_post_type_args = array(
			'public'        =>   true,
			'menu_icon'     =>   'dashicons-location',
			'has_archive'   =>   true,
			'rewrite'       =>   true,
			'supports'      =>   array('title', 'editor'),
			'taxonomies' 	=>	 array(self::LAYER_TAXONOMY)
		);

		protected $layer_taxonomy_args = array(
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array('slug' => self::LAYER_TAXONOMY),
		);

		/**
		 * Plugin version number
		 * @var string
		 */
		public $version = '1.2';

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
			$this->includes();

			add_action('init', array($this, 'register_post_types'));
			add_action('init', array($this, 'register_taxonomies'), 0);
		}

		/**
		 * Detect type of request
		 * @param $type
		 * @return bool
		 */
		private function is_request($type) {
			switch ($type) {
				case 'admin' :
					return is_admin();
				case 'ajax' :
					return defined('DOING_AJAX');
				case 'cron' :
					return defined('DOING_CRON');
				case 'frontend' :
					return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
			}
		}

		/**
		 * Include necessary plugin files
		 */
		public function includes() {

			include_once($this->plugin_path() . '/includes/class-lm-template-loader.php');

			if ($this->is_request('admin')) {

				// Enqueue scripts and styles
				include_once($this->plugin_path() . '/includes/admin/class-lm-admin-scripts.php');

				// Admin features
				include_once($this->plugin_path() . '/includes/admin/class-lm-admin-page.php');
				include_once($this->plugin_path() . '/includes/admin/class-lm-admin-page-settings.php');
				include_once($this->plugin_path() . '/includes/admin/class-lm-admin-page-import.php');

				// Admin controller
				include_once($this->plugin_path() . '/includes/admin/class-lm-admin.php');
			}

			if ($this->is_request('frontend')) {

				// Enqueue scripts and styles
				include_once($this->plugin_path() . '/includes/class-lm-frontend-scripts.php');

				// Frontend controller
				include_once($this->plugin_path() . '/includes/class-lm-frontend.php');
			}
		}

		public function get_option($option_field, $option_group = null) {

			if(!isset($option_group)) {
				$option_group = $this->plugin_options;
			}

			$get_option = get_option($option_group);

			$options = !empty($get_option) ? array_merge($this->option_defaults, $get_option) : $this->option_defaults;

			return isset($options[$option_field]) ? $options[$option_field] : '';
		}

		public function get_options($option_group = null) {

			if(!isset($option_group)) {
				$option_group = $this->plugin_options;
			}

			$get_option = get_option($option_group);

			$options = !empty($get_option) ? array_merge($this->option_defaults, $get_option) : $this->option_defaults;

			return $options;
		}

		public function register_post_types() {
			$this->post_type_labels();

			register_post_type(self::MAP_POST_TYPE, apply_filters('layermaps_map_post_type_args', $this->map_post_type_args));

			register_post_type(self::PIN_POST_TYPE, apply_filters('layermaps_pin_post_type_args', $this->pin_post_type_args));
		}

		public function register_taxonomies() {

			$this->taxonomy_labels();

			register_taxonomy(self::LAYER_TAXONOMY, array(self::MAP_POST_TYPE, self::PIN_POST_TYPE), $this->layer_taxonomy_args);
		}

		public function post_type_labels() {

			$this->map_post_type_args['labels'] = apply_filters('layermaps_map_post_type_labels', array(
				'name'                  =>   __('Layer Maps', 'layer-maps'),
				'singular_name'         =>   __('Map', 'layer-maps'),
				'add_new_item'          =>   __('Add New Map', 'layer-maps'),
				'all_items'             =>   __('Maps', 'layer-maps'),
				'edit_item'             =>   __('Edit Map', 'layer-maps'),
				'new_item'              =>   __('New Map', 'layer-maps'),
				'view_item'             =>   __('View Map', 'layer-maps'),
				'not_found'             =>   __('You have no maps.', 'layer-maps'),
				'not_found_in_trash'    =>   __('You have no trashy maps.', 'layer-maps')
			));

			$this->pin_post_type_args['labels'] = apply_filters('layermaps_pin_post_type_labels', array(
				'name'                  =>   __('Pins', 'layer-maps'),
				'singular_name'         =>   __('Pin', 'layer-maps'),
				'add_new_item'          =>   __('Add New Pin', 'layer-maps'),
				'all_items'             =>   __('Pins', 'layer-maps'),
				'edit_item'             =>   __('Edit Pin', 'layer-maps'),
				'new_item'              =>   __('New Pin', 'layer-maps'),
				'view_item'             =>   __('View Pin', 'layer-maps'),
				'not_found'             =>   __('You have no pins.', 'layer-maps'),
				'not_found_in_trash'    =>   __('You have no trashy pins.', 'layer-maps')
			));

			$this->pin_post_type_args['show_in_menu'] = 'edit.php?post_type=' . self::MAP_POST_TYPE;

		}

		public function taxonomy_labels() {
			$this->layer_taxonomy_args['labels'] = apply_filters('layermaps_layer_taxonomy_labels', array(
				'name'              => _x( 'Layers', 'taxonomy general name', 'layer-maps' ),
				'singular_name'     => _x( 'Layer', 'taxonomy singular name', 'layer-maps' ),
				'search_items'      => __( 'Search Layers', 'layer-maps' ),
				'all_items'         => __( 'All Layers', 'layer-maps' ),
				'parent_item'       => __( 'Parent Layer', 'layer-maps' ),
				'parent_item_colon' => __( 'Parent Layer:', 'layer-maps' ),
				'edit_item'         => __( 'Edit Layer', 'layer-maps' ),
				'update_item'       => __( 'Update Layer', 'layer-maps' ),
				'add_new_item'      => __( 'Add New Layer', 'layer-maps' ),
				'new_item_name'     => __( 'New Layer Name', 'layer-maps' ),
				'menu_name'         => __( 'Layers', 'layer-maps' ),
			));
		}

		/**
		 * Return the plugin URL
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit(plugins_url('/', __FILE__));
		}

		/**
		 * Return the plugin directory path
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit(plugin_dir_path(__FILE__));
		}

		/**
		 * Return the plugin template path
		 * @return string
		 */
		public function template_path() {
			return apply_filters('layermaps_template_path', 'layermaps/');
		}

		/**
		 * Return the vendor directory path
		 * @return string
		 */
		public function vendor_path() {
			return apply_filters('layermaps_vendor_path', $this->plugin_path() . '/vendor');
		}

	}

}

/**
 * Returns the main instance of the plugin
 */
function Layer_Maps() {
	return Layer_Maps::instance();
}

// Global for backwards compatibility.
$GLOBALS['layer_maps'] = Layer_Maps();
