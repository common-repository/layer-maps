<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('LM_Frontend')) {

	class LM_Frontend {

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

		public function __construct() {

			add_action('wp_ajax_nopriv_layermaps_get_pins', array($this, 'get_pins'));
			add_action('wp_ajax_layermaps_get_pins', array($this, 'get_pins'));

			add_action('wp_ajax_nopriv_layermaps_filter_pins', array($this, 'filter_pins'));
			add_action('wp_ajax_layermaps_filter_pins', array($this, 'filter_pins'));

			add_action( 'wp_ajax_nopriv_layermaps_kml_layer', array($this, 'kml_layer'));
			add_action( 'wp_ajax_layermaps_kml_layer', array($this, 'kml_layer'));

			add_shortcode('layermap', array($this, 'layermap_shortcode'));

		}

		public function get_pins() {
			$map_id = $_POST['map_id'];
			$kml = get_post_meta($map_id, '_layermaps_kml_layer', true);

			$terms = wp_get_post_terms( $map_id, array(Layer_Maps::LAYER_TAXONOMY));

			$items = array();

			foreach($terms as $term) {
				$items[] = $term->term_id;
			}

			$layers_array = array();

			foreach($items as $item) {

				$pins = array();

				$args = array(
					'post_type' => Layer_Maps::PIN_POST_TYPE,
					'posts_per_page' => -1,
					'tax_query' => array(
						array(
							'taxonomy' => Layer_Maps::LAYER_TAXONOMY,
							'field'    => 'term_id',
							'terms'    => array($item),
						),
					),
				);
				$query = new WP_Query( $args );

				foreach($query->posts as $layer_pin) {
					$pins[] = array(
						get_post_field('post_title', $layer_pin->ID),
						get_post_meta($layer_pin->ID, '_layermaps_lat', true),
						get_post_meta($layer_pin->ID, '_layermaps_long', true),
						get_post_meta($layer_pin->ID, '_layermaps_pin_id', true),
						get_post_meta($layer_pin->ID, '_layermaps_pin_colour', true),
						get_post_field('post_content', $layer_pin->ID)
					);
				}

				$layer_colour = get_option( '_layermaps_layer_pin_colour_' . $item );

				$layer_colour = isset($layer_colour['colour']) || !empty($layer_colour['colour']) ? $layer_colour['colour'] : '';

				$layers_array[] = array(
					$item,
					get_term_by('id', $item, Layer_Maps::LAYER_TAXONOMY )->name,
					$layer_colour,
					$pins
				);
			}

			$response = array($layers_array, $kml);

			echo json_encode($response);
			exit;
		}

		public function filter_pins() {
			$map_id = $_POST['map_id'];
			$layers = $_POST['layers'];
			$kml = get_post_meta($map_id, '_layermaps_kml_layer', true);

			$response = array();

			if (isset($layers) && !empty($layers)) {

				$layers_array = array();

				foreach($layers as $item) {

					$pins = array();

					$args = array(
						'post_type' => Layer_Maps::PIN_POST_TYPE,
						'posts_per_page' => -1,
						'tax_query' => array(
							array(
								'taxonomy' => Layer_Maps::LAYER_TAXONOMY,
								'field'    => 'term_id',
								'terms'    => array($item),
							),
						),
					);
					$query = new WP_Query( $args );

					foreach($query->posts as $layer_pin) {
						$pins[] = array(
							get_post_field('post_title', $layer_pin->ID),
							get_post_meta($layer_pin->ID, '_layermaps_lat', true),
							get_post_meta($layer_pin->ID, '_layermaps_long', true),
							get_post_meta($layer_pin->ID, '_layermaps_pin_id', true),
							get_post_meta($layer_pin->ID, '_layermaps_pin_colour', true),
							get_post_field('post_content', $layer_pin->ID)
						);
					}

					$layer_colour = get_option( '_layermaps_layer_pin_colour_' . $item );

					$layer_colour = isset($layer_colour['colour']) || !empty($layer_colour['colour']) ? $layer_colour['colour'] : '';

					$layers_array[] = array(
						$item,
						get_term_by( 'id', (int)$item, Layer_Maps::LAYER_TAXONOMY )->name,
						$layer_colour,
						$pins
					);
				}

				$response = array($layers_array, $kml);
			}

			echo json_encode($response);
			exit;
		}

		public function kml_layer() {
			$map_id = $_POST['map_id'];

			if ($_POST['kml_on'] == 'yes') {
				$kml = get_post_meta($map_id, '_layermaps_kml_layer', true);
			} else {
				$kml = '';
			}
			echo json_encode($kml);
			exit;
		}

		public function layermap_shortcode($atts, $content = null) {
			$option = shortcode_atts( array(
				'id' => ''
			), $atts );

			$id = $option['id'];

			$template = new LM_Template_Loader('shortcode', 'layermap');

			$template->set('layermaps_map_id', $id);

			return $template->render(true);

		}

	}

}

LM_Frontend::instance();