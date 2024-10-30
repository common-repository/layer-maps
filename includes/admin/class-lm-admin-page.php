<?php

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!class_exists('LM_Admin_Page')) {

	class LM_Admin_Page {

		private $title;
		private $capability;
		private $slug;
		private $option_group;
		private $option_section;

		/**
		 * Initiate the plugin by setting up actions and filters
		 */
		public function __construct($title, $slug, $capability = 'edit_posts') {

			$this->title = $title;
			$this->capability = $capability;
			$this->slug = $slug;
			$this->option_group = $slug . '_fields';
			$this->option_section = '_' . $slug;

			add_action('admin_menu', array($this, 'setup_admin_menu'));
			add_action('admin_init', array($this, 'register_settings'));
		}

		public function get_option_group() {
			return $this->option_group;
		}

		public function get_option_section() {
			return $this->option_section;
		}

		public function setup_admin_menu() {
			add_submenu_page('edit.php?post_type=' . Layer_Maps::MAP_POST_TYPE, $this->title, $this->title, $this->capability, $this->slug, array($this, 'page_form_fields'));
		}

		public function register_settings() {
			register_setting($this->option_group, $this->option_section);
		}

	}

}
