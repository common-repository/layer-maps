<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('LM_Admin_Page_Import')) {

	class LM_Admin_Page_Import extends LM_Admin_Page {

		private $upload_complete;
		private $upload_message;
		private $import_complete;
		private $import_message;
		private $import_errors;

		private $allowed_file_types = array(
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'text/csv'
		);

		public function page_form_fields() {

			if(isset($_FILES['selected_file']) && $_FILES['selected_file']['size'] > 0) {
				$this->upload_file();
			}

			if(isset($_POST['layer']) && !empty($_POST['layer'])) {
				$this->process_file();
			}

			$args = array(
				'post_status' => 'inherit',
				'post_type' => 'attachment',
				'orderby' => 'post_date',
				'order' => 'desc',
				'posts_per_page' => -1
			);

			$attachments = new WP_Query($args);

			$layers = get_terms(array(Layer_Maps::LAYER_TAXONOMY), array('hide_empty' => false));

			?>

			<div class="wrap">
			<h2>Import Data</h2>

			<?php if(isset($this->upload_complete) && $this->upload_complete === true) : ?>

				<div class="updated">
					<p><strong><?php echo $this->upload_message; ?></strong></p>
				</div>

			<?php elseif(isset($this->upload_complete) && $this->upload_complete === false) : ?>

				<div class="error">
					<p><strong><?php echo $this->upload_message; ?></strong></p>
				</div>

			<?php endif; ?>

			<?php if(isset($this->import_complete) && $this->import_complete === true) : ?>

				<div class="updated">
					<p><strong><?php echo $this->import_message; ?></strong></p>
				</div>

			<?php elseif(isset($this->import_complete) && $this->import_complete === false) : ?>

				<div class="error">
					<p><strong><?php echo $this->import_message; ?></strong>

						<?php foreach($this->import_errors as $error) : ?>
							<br>- <?php echo $error; ?>
						<?php endforeach; ?>
					</p>
				</div>

			<?php endif; ?>

			<p>Import pins attached to a given layer by uploading addresses from a spreadsheet or CSV file.</p>

			<p>Please edit <a href="<?php echo Layer_Maps()->plugin_url() ?>/layermaps-import-template.xlsx">this spreadsheet template</a> with your data and upload it using the form below.</p>

			<form method="post" action="" enctype="multipart/form-data">

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="selected_file">Upload File</label></th>
							<td>
								<input type="file" name="selected_file" id="selected_file" class="regular-text">
								<p class="description">Select a CSV or XLSX file to upload.</p>
							</td>
						</tr>

					</tbody>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Upload File') ?>" />
				</p>

			</form>

			<?php if(!empty($attachments->posts)) : ?>

			<form method="post" action="">

				<h3 class="title">Import File</h3>
				<p>Once your file is uploaded, select it from the list and choose the layer you want to import the data into.</p>

				<table class="form-table">
					<tbody>

						<tr>
							<th scope="row"><label for="import_file">Available Files</label></th>
							<td>
								<select name="attachment" id="import_file">

									<?php foreach($attachments->posts as $attachment) : ?>
										<?php if(in_array($attachment->post_mime_type, $this->allowed_file_types)) : ?>
											<option value="<?php echo $attachment->ID; ?>"><?php echo date('Y/m/d', strtotime($attachment->post_date)); ?> - <?php echo $attachment->post_title; ?></option>
										<?php endif; ?>
									<?php endforeach; ?>

								</select>
								<p class="description">Select the file you want to import.</p>

							</td>
						</tr>

						<tr>
							<th scope="row"><label for="layer">Map Layers</label></th>
							<td>
								<select name="layer" id="layer">
									<option selected="selected" value="">&mdash; Select &mdash;</option>
									<?php if(!empty($layers)) : ?>
										<?php foreach($layers as $layer) : ?>
											<option value="<?php echo $layer->term_id; ?>>"><?php echo $layer->name; ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
								<p class="description">Select the layer you want to import into.</p>
							</td>
						</tr>

					</tbody>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Import') ?>" />
				</p>

			</form>

			<?php endif; ?>

		<?php

		}

		public function upload_file() {

			$arr_file_type = wp_check_filetype($_FILES['selected_file']['name']);
			$uploaded_file_type = $arr_file_type['type'];

			$allowed_file_types = $this->allowed_file_types;

			if(in_array($uploaded_file_type, $allowed_file_types)) {

				$upload_overrides = array( 'test_form' => false );

				$uploaded_file = wp_handle_upload($_FILES['selected_file'], $upload_overrides);

				if(isset($uploaded_file['file'])) {

					$file_name_and_location = $uploaded_file['file'];

					$upload_dir = wp_upload_dir();

					$attachment = array(
						'guid'           => $upload_dir['url'] . '/' . basename($file_name_and_location),
						'post_mime_type' => $uploaded_file_type,
						'post_title' => basename($file_name_and_location),
						'post_content' => '',
						'post_status' => 'inherit'
					);

					$attach_id = wp_insert_attachment( $attachment, $file_name_and_location );

					$attach_data = wp_generate_attachment_metadata( $attach_id, $file_name_and_location );

					wp_update_attachment_metadata($attach_id,  $attach_data);

					$this->upload_complete = true;
					$this->upload_message = 'Upload complete.';
				}
				else {
					$this->upload_complete = false;
					$this->upload_message = 'There was an unknown issue uploading your file.';
				}

			}
			else {
				$this->upload_complete = false;
				$this->upload_message = 'The file you selected is not the correct format.';
			}

		}

		public function parse_data($attachment) {
			require_once Layer_Maps()->vendor_path() . '/autoload.php';

			$items = array();
			$columns = array();

			$file = get_attached_file($attachment);

			if(!empty($file)) {

				try {
					$file_type = PHPExcel_IOFactory::identify($file);
					$reader = PHPExcel_IOFactory::createReader($file_type);
					$phpexcel = $reader->load($file);

					$sheet = $phpexcel->getSheet(0);
					$highest_row = $sheet->getHighestRow();
					$highest_column = $sheet->getHighestColumn();

					$accepted_columns = array('markername', 'popuptext', 'address', 'pincolour');

					for ($row = 1; $row <= $highest_row; $row++){

						$data = $sheet->rangeToArray('A' . $row . ':' . $highest_column . $row, null, true, false);

						if(count($columns) != count($accepted_columns)) {
							foreach($data[0] as $key => $item) {
								if(in_array($item, $accepted_columns, true)) {
									$columns[$key] = $item;
								}
							}
						}

						if(count($columns) == count($accepted_columns)) {

							if(!isset($found_row)) {
								$found_row = $row;
							}

							if($row > $found_row) {
								foreach($data[0] as $key => $item) {

									if(isset($columns[$key])) {
										$items[$row][$columns[$key]] = $item;
									}

								}
							}

						}

					}

				}
				catch(Exception $e) {
					wp_die('Error loading file');
				}
			}

			return $items;
		}

		public function process_file() {
			
			$items = $this->parse_data($_POST['attachment']);

			$accepted_colours = array(
				'black',
				'blue',
				'green',
				'grey',
				'indigo',
				'orange',
				'pink',
				'purple',
				'red',
				'turquoise',
				'yellow',
				'maroon',
				'navyblue',
				'light_blue',
				'light_green',
				'light_red',
				'light_white',
			);

			$errors = array();
			$message = 'The following import errors occurred:';

			if(!empty($items)) {
				foreach($items as $key => $item) {

					if(!empty($item['markername']) && !empty($item['address'])) {

						$args = array(
							'post_title'    => $item['markername'],
							'post_type'     => Layer_Maps::PIN_POST_TYPE,
							'post_content'  => $item['popuptext'],
							'post_status'   => 'publish',
							'post_author'   => get_current_user_id(),
							'tax_input' => array(
								Layer_Maps::LAYER_TAXONOMY => array($_POST['layer'])
							)
						);

						$post_id = wp_insert_post($args);

						$details_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($item['address']) . '&key=' . Layer_Maps()->get_option('api_key');

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $details_url);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						$response = json_decode(curl_exec($ch), true);

						if ($response['status'] == 'OK') {

							$geometry = $response['results'][0]['geometry'];

							$pin_meta = array();

							$pin_meta['_layermaps_lat'] = $geometry['location']['lat'];
							$pin_meta['_layermaps_long'] = $geometry['location']['lng'];

							foreach ($pin_meta as $key => $value) {
								update_post_meta($post_id, $key, $value);
							}

							if(isset($item['pincolour']) && in_array($item['pincolour'], $accepted_colours)) {
								update_post_meta($post_id, '_layermaps_pin_colour', $item['pincolour']);
							}
							else {
								update_post_meta($post_id, '_layermaps_pin_colour', 'black');
							}

							update_post_meta($post_id, '_layermaps_postcode', $item['address']);
						}
						else {
							$message = 'The import has partially completed with the following issues:';
							$errors[] = 'Could not geocode row ' . $key . '.';
						}
					}
					else {
						$message = 'The import has partially completed with the following issues:';
						$errors[] = 'Could not import row ' . $key . ' due to missing data.';
					}
				}
			}
			else {
				$errors[] = 'Parsed file returned empty results.';
			}

			if(!empty($errors)) {
				$this->import_complete = false;
				$this->import_message = $message;
				$this->import_errors = $errors;
			}
			else {
				$this->import_complete = true;
				$this->import_message = 'Import completed successfully.';
			}

		}

	}

}
