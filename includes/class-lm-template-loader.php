<?php

if (!class_exists('LM_Template_Loader')) {

	class LM_Template_Loader {

		public $view = null;
		public $vars = null;

		public function __construct($slug, $name = '') {

			$template = '';

			// If a name is set, look in theme or child theme
			if ($name) {
				$template = locate_template(array("{$slug}-{$name}.php", Layer_Maps()->template_path() . "{$slug}-{$name}.php"));
			}

			// If not found, load our default template
			if (!$template && $name && file_exists(Layer_Maps()->plugin_path() . "/templates/{$slug}-{$name}.php")) {
				$template = Layer_Maps()->plugin_path() . "/templates/{$slug}-{$name}.php";
			}

			// If not found, look for just the slug in theme or child theme
			if (!$template) {
				$template = locate_template(array("{$slug}.php", Layer_Maps()->template_path() . "{$slug}.php"));
			}

			// Load it
			if ($template) {
				$this->view = $template;
			}
		}

		public function set($name, $value) {
			$this->vars[$name] = $value;
			return $this;
		}

		public function render($return = false) {
			if($this->vars) {
				extract($this->vars, EXTR_SKIP);
			}

			if ($return === true) {
				ob_start();
			}

			include_once $this->view;

			if ($return === true) {
				return ob_get_clean();
			}
		}
	}
}

