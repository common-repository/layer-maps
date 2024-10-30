<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('LM_Admin')) {

	class LM_Admin {

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

			$this->setup_admin_pages();

			/**
			 * General setup
			 */
			add_filter('upload_mimes', array($this, 'update_mime_types'));
			add_action('admin_menu', array($this, 'update_admin_menu'));


			/**
			 * Metaboxes
			 */

			// Map and pin metaboxes
			add_action('add_meta_boxes', array($this, 'add_pin_metaboxes'));
			add_action('add_meta_boxes', array($this, 'add_map_metaboxes'));

			// Saving actions
			add_action('save_post', array($this, 'save_postcode_meta'), 1, 2);
			add_action('save_post', array($this, 'save_kml_meta'), 1, 2);


			/**
			 * Modify maps post type
			 */

			// Update columns
			add_filter('manage_edit-' . Layer_Maps::MAP_POST_TYPE . '_columns', array($this, 'update_post_type_column'));
			add_action('manage_' . Layer_Maps::MAP_POST_TYPE . '_posts_custom_column', array($this, 'update_post_type_taxonomy_links'), 10, 2);
			add_action('manage_' . Layer_Maps::PIN_POST_TYPE . '_posts_custom_column', array($this, 'update_post_type_add_map_column'), 10, 2);

			/**
			 * Modify maps post type
			 */

			// Update columns
			add_filter('manage_edit-' . Layer_Maps::PIN_POST_TYPE . '_columns', array($this, 'update_pin_post_type_column'));

			/**
			 * Modify pins view for map
			 */

			// Showing pins associated with maps
			if ( is_admin() ) add_filter('pre_get_posts', array($this, 'filter_by_map'));

			/**
			 * Modify layers taxonomy
			 */

			// Add and edit forms
			add_action(Layer_Maps::LAYER_TAXONOMY . '_edit_form_fields', array($this, 'layer_taxonomy_edit_custom_fields'), 10, 2);
			add_action(Layer_Maps::LAYER_TAXONOMY . '_add_form_fields', array($this, 'layer_taxonomy_add_custom_fields'), 10, 2);

			// Saving actions
			add_action('edited_' . Layer_Maps::LAYER_TAXONOMY, array($this, 'save_layer_taxonomy_custom_fields'), 10, 2);
			add_action('create_' . Layer_Maps::LAYER_TAXONOMY, array($this, 'save_layer_taxonomy_custom_fields'), 10, 2);

			// Custom taxonomy columns
			add_filter('manage_edit-' . Layer_Maps::LAYER_TAXONOMY . '_columns', array($this, 'update_taxonomy_column'));
			add_filter('manage_' . Layer_Maps::LAYER_TAXONOMY . '_custom_column', array($this, 'add_taxonomy_totals'), 10, 3);
			add_filter('manage_edit-' . Layer_Maps::LAYER_TAXONOMY . '_sortable_columns', array($this, 'sortable_taxonomy_column'));
			add_filter('get_terms', array($this, 'sortable_taxonomy_column_order'), 10, 3);
			add_filter(Layer_Maps::LAYER_TAXONOMY . '_row_actions', array($this, 'update_taxonomy_links'), 10, 2);

		}

		public function update_mime_types($existing_mimes = array()) {
			$existing_mimes['kml'] = 'application/vnd.google-earth.kml+xml';
			return $existing_mimes;
		}

		public function update_admin_menu() {
			remove_submenu_page('edit.php?post_type=' . Layer_Maps::MAP_POST_TYPE, 'post-new.php?post_type=' . Layer_Maps::MAP_POST_TYPE);
		}

		public function setup_admin_pages() {
			new LM_Admin_Page_Import('Import', 'layermaps_import');
			new LM_Admin_Page_Settings('Settings', 'layermaps_settings');
		}

		public function add_pin_metaboxes() {
			add_meta_box('pin_meta', 'Pin Details', array($this, 'pin_meta'), Layer_Maps::PIN_POST_TYPE, 'advanced', 'default');
		}

		public function add_map_metaboxes() {
			add_meta_box('kml_meta', 'KML Layer URL', array($this, 'kml_meta'), Layer_Maps::MAP_POST_TYPE, 'normal', 'default');
			add_meta_box('shortcode_meta', 'Shortcode', array($this, 'shortcode_meta'), Layer_Maps::MAP_POST_TYPE, 'normal', 'default');
		}

		public function pin_meta() {
			global $post;

			echo '<input type="hidden" name="postcodemeta_noncename" id="postcodemeta_noncename" value="' .
				wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

			$postcode = get_post_meta($post->ID, '_layermaps_postcode', true);
			$lat = get_post_meta($post->ID, '_layermaps_lat', true);
			$long = get_post_meta($post->ID, '_layermaps_long', true);
			$pin_colour = get_post_meta($post->ID, '_layermaps_pin_colour', true);
			$pin_id_number = get_post_meta($post->ID, '_layermaps_pin_id', true);

			?>
			<label for="_layermaps_postcode">Address</label>
			<textarea rows="4" cols="50" name="_layermaps_postcode" class="widefat" /><?php echo $postcode; ?></textarea>
			<br/><p class="description">A postcode works here, but the full address provides more accuracy.</p>
			<br/>
			<label for="_layermaps_pin_id">Pin ID Number</label>

			<input type="text" name="_layermaps_pin_id" value="<?php echo $pin_id_number ?>" class="widefat" /><br/><br/>

			<label for="_layermaps_pin_color">Pin Colour</label>
			<select name="_layermaps_pin_colour" class="widefat" />
				<option value="black" <?php echo $pin_colour == 'black' ? 'selected' : '' ?>>Black</option>
				<option value="light_white" <?php echo $pin_colour == 'light_white' ? 'selected' : '' ?>>White</option>
				<option value="blue" <?php echo $pin_colour == 'blue' ? 'selected' : '' ?>>Blue</option>
				<option value="green" <?php echo $pin_colour == 'green' ? 'selected' : '' ?>>Green</option>
				<option value="grey" <?php echo $pin_colour == 'grey' ? 'selected' : '' ?>>Grey</option>
				<option value="indigo" <?php echo $pin_colour == 'indigo' ? 'selected' : '' ?>>Indigo</option>
				<option value="light_blue" <?php echo $pin_colour == 'light_blue' ? 'selected' : '' ?>>Light Blue</option>
				<option value="light_green" <?php echo $pin_colour == 'light_green' ? 'selected' : '' ?>>Light Green</option>
				<option value="light_red" <?php echo $pin_colour == 'light_red' ? 'selected' : '' ?>>Light Red</option>
				<option value="maroon" <?php echo $pin_colour == 'maroon' ? 'selected' : '' ?>>Maroon</option>
				<option value="navyblue" <?php echo $pin_colour == 'navyblue' ? 'selected' : '' ?>>Navy Blue</option>
				<option value="orange" <?php echo $pin_colour == 'orange' ? 'selected' : '' ?>>Orange</option>
				<option value="pink" <?php echo $pin_colour == 'pink' ? 'selected' : '' ?>>Pink</option>
				<option value="purple" <?php echo $pin_colour == 'purple' ? 'selected' : '' ?>>Purple</option>
				<option value="red" <?php echo $pin_colour == 'red' ? 'selected' : '' ?>>Red</option>
				<option value="turquoise" <?php echo $pin_colour == 'turquoise' ? 'selected' : '' ?>>Turquoise</option>
				<option value="yellow" <?php echo $pin_colour == 'yellow' ? 'selected' : '' ?>>Yellow</option>
			</select>
			<p class="description" id="tagline-description">Pin colours can be overridden by the colour of its associated layer.</p>
			<p><strong>Lat:</strong> <?php echo $lat ?></p>
			<p><strong>Long:</strong> <?php echo $long ?></p>
		<?php }

		public function save_postcode_meta($post_id, $post) {
			if ( !wp_verify_nonce( $_POST['postcodemeta_noncename'], plugin_basename(__FILE__) )) {
				return $post->ID;
			}

			// Is the user allowed to edit the post or page?
			if ( !current_user_can( 'edit_post', $post->ID ))
				return $post->ID;

			$pin_meta['_layermaps_postcode'] = $_POST['_layermaps_postcode'];
			$pin_meta['_layermaps_pin_id'] = $_POST['_layermaps_pin_id'];
			$pin_meta['_layermaps_pin_colour'] = $_POST['_layermaps_pin_colour'];

			$postcode = urlencode($pin_meta['_layermaps_postcode']);

			$details_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $postcode . '&key=' . Layer_Maps()->get_option('api_key');

			/*
			if ($_POST['_layermaps_postcode'] == get_post_meta($post->ID, '_layermaps_postcode', true)) {
				$pin_meta['_layermaps_lat'] = get_post_meta($post->ID, '_layermaps_lat', true);
				$pin_meta['_layermaps_long'] = get_post_meta($post->ID, '_layermaps_long', true);
			}*/

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $details_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = json_decode(curl_exec($ch), true);

			// If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
			if ($response['status'] != 'OK') {
				return null;
			}

			$geometry = $response['results'][0]['geometry'];
			if (isset($geometry['location']['lat']) && is_numeric($geometry['location']['lat'])) {
				$pin_meta['_layermaps_lat'] = $geometry['location']['lat'];
			} else {
				$pin_meta['_layermaps_lat'] = '';
			}
			if (isset($geometry['location']['lng']) && is_numeric($geometry['location']['lng'])) {
				$pin_meta['_layermaps_long'] = $geometry['location']['lng'];
			} else {
				$pin_meta['_layermaps_long'] = '';
			}

			foreach ($pin_meta as $key => $value) {
				if( $post->post_type == 'revision' ) return;
				$value = implode(',', (array)$value);
				if(get_post_meta($post->ID, $key, FALSE)) {
					update_post_meta($post->ID, $key, $value);
				} else {
					add_post_meta($post->ID, $key, $value);
				}
				if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
			}
		}

		public function kml_meta() {
			global $post;

			echo '<input type="hidden" name="kmlmeta_noncename" id="kmlmeta_noncename" value="' .
				wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

			$kml_url = get_post_meta($post->ID, '_layermaps_kml_layer', true);

			echo '<input type="text" name="_layermaps_kml_layer" id="_layermaps_kml_layer" class="widefat" value="' . $kml_url . '" placeholder="Leave blank for no KML Layer" />';
		}

		public function save_kml_meta() {

			global $post;

			if ( !wp_verify_nonce( $_POST['kmlmeta_noncename'], plugin_basename(__FILE__) )) {
				return $post->ID;
			}

			// Is the user allowed to edit the post or page?
			if ( !current_user_can( 'edit_post', $post->ID ))
				return $post->ID;

			$kml_url = $_POST['_layermaps_kml_layer'];
			if ($kml_url) {
				update_post_meta($post->ID, '_layermaps_kml_layer', $kml_url);
			} else {
				delete_post_meta($post->ID, '_layermaps_kml_layer');
			}
		}

		public function shortcode_meta() {
			global $post;

			echo '<input type="hidden" name="shortcodemeta_noncename" id="shortcodemeta_noncename" value="' .
				wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

			echo '<p>To view this map, copy and paste the following shortcode into your Page or Post: <strong>[layermap id="'. $post->ID .'"]</strong>';
		}

		public function layer_taxonomy_edit_custom_fields($term) {
			$term_id = $term->term_id;
			$term_meta = get_option( '_layermaps_layer_pin_colour_' . $term_id );

			$layer_colour = isset($term_meta['colour']) && !empty($term_meta['colour']) ? $term_meta['colour'] : '';

			?>

			<tr class="form-field">
				<th scope="row">
					<label for="tag-layermaps-colour"><?php _e('Layer Pin Colour'); ?></label>
				</th>
				<td>
					<select name="tag-layermaps[colour]" id="tag-layermaps[colour]" class="postform">
						<option value="">None</option>
						<option value="black" <?php echo $layer_colour == 'black' ? 'selected' : '' ?>>Black</option>
						<option value="light_white" <?php echo $layer_colour == 'light_white' ? 'selected' : '' ?>>White</option>
						<option value="blue" <?php echo $layer_colour == 'blue' ? 'selected' : '' ?>>Blue</option>
						<option value="green" <?php echo $layer_colour == 'green' ? 'selected' : '' ?>>Green</option>
						<option value="grey" <?php echo $layer_colour == 'grey' ? 'selected' : '' ?>>Grey</option>
						<option value="indigo" <?php echo $layer_colour == 'indigo' ? 'selected' : '' ?>>Indigo</option>
						<option value="light_blue" <?php echo $layer_colour == 'light_blue' ? 'selected' : '' ?>>Light Blue</option>
						<option value="light_green" <?php echo $layer_colour == 'light_green' ? 'selected' : '' ?>>Light Green</option>
						<option value="light_red" <?php echo $layer_colour == 'light_red' ? 'selected' : '' ?>>Light Red</option>
						<option value="maroon" <?php echo $layer_colour == 'maroon' ? 'selected' : '' ?>>Maroon</option>
						<option value="navyblue" <?php echo $layer_colour == 'navyblue' ? 'selected' : '' ?>>Navy Blue</option>
						<option value="orange" <?php echo $layer_colour == 'orange' ? 'selected' : '' ?>>Orange</option>
						<option value="pink" <?php echo $layer_colour == 'pink' ? 'selected' : '' ?>>Pink</option>
						<option value="purple" <?php echo $layer_colour == 'purple' ? 'selected' : '' ?>>Purple</option>
						<option value="red" <?php echo $layer_colour == 'red' ? 'selected' : '' ?>>Red</option>
						<option value="turquoise" <?php echo $layer_colour == 'turquoise' ? 'selected' : '' ?>>Turquoise</option>
						<option value="yellow" <?php echo $layer_colour == 'yellow' ? 'selected' : '' ?>>Yellow</option>
					</select>
					<p class="description">Set the colour for all pins within this layer, the pin colour will be used if no colour is set.</p>
				</td>
			</tr>

			<?php
		}

		public function layer_taxonomy_add_custom_fields($term) {

			$term_id = $term->term_id;
			$term_meta = get_option( '_layermaps_layer_pin_colour_' . $term_id );

			$layer_colour = isset($term_meta['colour']) && !empty($term_meta['colour']) ? $term_meta['colour'] : '';

			?>

			<div class="form-field term-colour-wrap">
				<label for="tag-layermaps-colour"><?php _e('Layer Pin Colour'); ?></label>
				<select name="tag-layermaps[colour]" id="tag-layermaps[colour]" class="postform">
					<option value="">None</option>
					<option value="black" <?php echo $layer_colour == 'black' ? 'selected' : '' ?>>Black</option>
					<option value="light_white" <?php echo $layer_colour == 'light_white' ? 'selected' : '' ?>>White</option>
					<option value="blue" <?php echo $layer_colour == 'blue' ? 'selected' : '' ?>>Blue</option>
					<option value="green" <?php echo $layer_colour == 'green' ? 'selected' : '' ?>>Green</option>
					<option value="grey" <?php echo $layer_colour == 'grey' ? 'selected' : '' ?>>Grey</option>
					<option value="indigo" <?php echo $layer_colour == 'indigo' ? 'selected' : '' ?>>Indigo</option>
					<option value="light_blue" <?php echo $layer_colour == 'light_blue' ? 'selected' : '' ?>>Light Blue</option>
					<option value="light_green" <?php echo $layer_colour == 'light_green' ? 'selected' : '' ?>>Light Green</option>
					<option value="light_red" <?php echo $layer_colour == 'light_red' ? 'selected' : '' ?>>Light Red</option>
					<option value="maroon" <?php echo $layer_colour == 'maroon' ? 'selected' : '' ?>>Maroon</option>
					<option value="navyblue" <?php echo $layer_colour == 'navyblue' ? 'selected' : '' ?>>Navy Blue</option>
					<option value="orange" <?php echo $layer_colour == 'orange' ? 'selected' : '' ?>>Orange</option>
					<option value="pink" <?php echo $layer_colour == 'pink' ? 'selected' : '' ?>>Pink</option>
					<option value="purple" <?php echo $layer_colour == 'purple' ? 'selected' : '' ?>>Purple</option>
					<option value="red" <?php echo $layer_colour == 'red' ? 'selected' : '' ?>>Red</option>
					<option value="turquoise" <?php echo $layer_colour == 'turquoise' ? 'selected' : '' ?>>Turquoise</option>
					<option value="yellow" <?php echo $layer_colour == 'yellow' ? 'selected' : '' ?>>Yellow</option>
				</select>
				<p>Set the colour for all pins within this layer.</p>
			</div>

			<?php
		}

		public function save_layer_taxonomy_custom_fields($term_id) {
			if (isset($_POST['tag-layermaps'])) {
				$term_meta = get_option('_layermaps_layer_pin_colour_' . $term_id);
				$cat_keys = array_keys($_POST['tag-layermaps']);
				
				foreach ($cat_keys as $key) {
					if (isset($_POST['tag-layermaps'][$key])) {
						$term_meta[$key] = $_POST['tag-layermaps'][$key];
					}
				}

				update_option('_layermaps_layer_pin_colour_' . $term_id, $term_meta);
			}
		}

		public function update_taxonomy_column($columns) {
			unset($columns['posts']);
			unset($columns['slug']);
			unset($columns['description']);
			$columns['layermaps_pin_count'] = 'Pins';

			return $columns;
		}

		public function get_taxonomy_totals($term, $taxonomy, $type) {

			$args = array(
				'post_type' => $type,
				'tax_query' => array(
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => array($term),
					),
				),
				'posts_per_page' => -1
			);
			$query = new WP_Query($args);

			if(count($query->posts) > 0) {
				return count($query->posts);
			}

			return 0;
		}

		public function add_taxonomy_totals($value, $column_name, $id){
			if($column_name == 'layermaps_pin_count') {
				return $this->get_taxonomy_totals($id, Layer_Maps::LAYER_TAXONOMY, Layer_Maps::PIN_POST_TYPE);
			}

			return $value;
		}

		public function sortable_taxonomy_column($sortable_columns) {
			$sortable_columns['layermaps_pin_count'] = 'layermaps_pin_count';

			return $sortable_columns;
		}

		function sortable_taxonomy_column_order($terms, $taxonomies, $args ) {

			if($args['orderby'] == 'layermaps_pin_count') {

				$sorted_terms = array();
				foreach($terms as $term) {
					$order = $this->get_taxonomy_totals($term->term_id, $taxonomies[0], Layer_Maps::PIN_POST_TYPE);
					$sorted_terms[$order] = $term;
				}

				if($args['order'] == 'asc') {
					ksort($sorted_terms, SORT_NUMERIC);
				}
				else {
					krsort( $sorted_terms, SORT_NUMERIC);
				}

				return $sorted_terms;
			}

			return $terms;

		}

		public function update_taxonomy_links($actions, $object) {
			$actions['view'] = '<a href="' . admin_url('edit.php?post_type=' . Layer_Maps::PIN_POST_TYPE) . '&' . Layer_Maps::LAYER_TAXONOMY . '=' . $object->slug . '">View Pins</a>';
			return $actions;
		}

		function update_post_type_column($columns) {

			$columns = array(
				'cb' => '<input type="checkbox" />',
				'title' => __('Map Name'),
				'layers' => __('Attached Layers'),
				'date' => __('Date')
			);

			return $columns;
		}

		function update_pin_post_type_column($columns) {

			$columns = array(
				'cb' => '<input type="checkbox" />',
				'title' => __('Pin Name'),
				'taxonomy-layermaps_layer' => __('Attached to Layer'),
				'layermaps_map' => __('Attached on Map'),
				'date' => __('Date')
			);

			return $columns;
		}

		public function update_post_type_taxonomy_links($column, $post_id) {

			if($column == 'layers') {

				$layers_terms = wp_get_post_terms($post_id, Layer_Maps::LAYER_TAXONOMY);

				$layers = array();
				if(!empty($layers_terms)) {
					foreach($layers_terms as $term) {
						$layers[] = '<a href="' . admin_url('edit.php?post_type=' . Layer_Maps::PIN_POST_TYPE) . '&' . Layer_Maps::LAYER_TAXONOMY . '=' . $term->slug . '">' . $term->name . '</a>';
					}
				}

				echo implode(', ', $layers);

			}
		}

		public function get_posts_by_term($term, $taxonomy, $type) {

			$args = array(
				'post_type' => $type,
				'tax_query' => array(
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term,
					),
				),
				'posts_per_page' => -1
			);
			$query = new WP_Query($args);

			$posts = array();
			foreach ($query->posts as $post) {
				$posts[] = array('name' => $post->post_title, 'slug' => $post->post_name, 'id' => $post->ID);
			}

			return $posts;
		}

		public function update_post_type_add_map_column($column, $post_id) {
			if($column == 'layermaps_map') {
				$layers_terms = wp_get_post_terms($post_id, Layer_Maps::LAYER_TAXONOMY);

				$layers = array();
				if(!empty($layers_terms)) {
					foreach($layers_terms as $term) {
						$layers[] = $term->term_id;
					}
				}

				$maps = $this->get_posts_by_term($layers, Layer_Maps::LAYER_TAXONOMY, Layer_Maps::MAP_POST_TYPE);

				$maps_string = array();
				foreach ($maps as $map) {
					$maps_string[] = '<a href="' . admin_url('edit.php?post_type=' . Layer_Maps::PIN_POST_TYPE) . '&lm_map=' . $map['slug'] . '">' . $map['name'] . '</a>';
				}

				echo implode(', ', $maps_string);
			}
		}

		function filter_by_map($query) {
			global $wpdb;

			if ($_GET['lm_map']) {
				$slug = $_GET['lm_map'];
				$post_type = Layer_Maps::MAP_POST_TYPE;

				$map_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '$slug' AND post_type = '$post_type'");

				$layers_terms = wp_get_post_terms($map_id, Layer_Maps::LAYER_TAXONOMY);

				$layers = array();
				if(!empty($layers_terms)) {
					foreach($layers_terms as $term) {
						$layers[] = $term->term_id;
					}
				}

				$tax_query = array(
					'taxonomy' => Layer_Maps::LAYER_TAXONOMY,
					'field'    => 'id',
					'terms'    => $layers,
					'operator'=> 'IN'
				);
				$query->tax_query->queries[] = $tax_query;
				$query->query_vars['tax_query'] = $query->tax_query->queries;

				return $query;
			}
		}
	}
}

LM_Admin::instance();