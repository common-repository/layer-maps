<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('LM_Admin_Page_Settings')) {

	class LM_Admin_Page_Settings extends LM_Admin_Page {

		public function page_form_fields() {

			$option_group = $this->get_option_group();
			$option_section = $this->get_option_section();

			?>

			<div class="wrap">
			<h2>Layer Maps Settings</h2>

			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla non varius velit. Praesent euismod nibh vitae dui malesuada lacinia. Quisque hendrerit eget turpis sed aliquet.</p>

			<form method="post" action="options.php">

				<?php settings_fields($option_group); ?>

				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row"><label for="<?php echo $option_section ?>[api_key]">Google Maps API Key</label></th>
						<td>
							<input name="<?php echo $option_section ?>[api_key]" type="text" id="<?php echo $option_section ?>[api_key]" value="<?php echo Layer_Maps()->get_option('api_key') ?>" class="regular-text">
							<p class="description">To get a Google Maps API Key go to <a target="_blank" href="https://console.developers.google.com/flows/enableapi?apiid=maps_backend&keyType=CLIENT_SIDE&reusekey=true">here</a> and follow the instructions.</p>
						</td>
					</tr>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo $option_section ?>[enable_clustering]">Enable Pin Clustering</label></th>
						<td>
							<input name="<?php echo $option_section ?>[enable_clustering]" type="checkbox" id="<?php echo $option_section ?>[enable_clustering]" value="1" <?php echo Layer_Maps()->get_option('enable_clustering') == 1 ? 'checked' : '' ?>>
						</td>
					</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>

			</form>

		<?php

		}

	}

}
