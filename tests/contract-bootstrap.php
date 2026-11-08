<?php
/**
 * Independent test doubles for the narrow public Bricks API consumed by tests.
 * Not Bricks source; not a substitute for licensed-theme compatibility tests.
 * Load only with WP-CLI --require and FSBR_CONTRACT_TEST=1 on isolated CI.
 */
namespace {
	if ( PHP_SAPI !== 'cli' || getenv( 'FSBR_CONTRACT_TEST' ) !== '1' ) {
		throw new \RuntimeException( 'Contract doubles are CLI-test-only.' );
	}
	if ( defined( 'BRICKS_VERSION' ) ) {
		throw new \RuntimeException( 'Do not mix contract doubles with a real Bricks theme.' );
	}
	define( 'BRICKS_VERSION', '2.4.3' );
}
namespace Bricks {
	class Element_Form {
		public $controls = array();
		public $control_options = array();
		public function set_controls() {
			$this->controls = array( 'actions' => array( 'options' => array( 'email' => 'Email' ) ) );
		}
	}
}
namespace Bricks\Integrations\Form {
	class Init {
		public $form_settings = array();
		public $form_fields = array();
		public $results = array();
		public $uploaded_files = array();
		public function get_settings() { return $this->form_settings; }
		public function get_fields() { return $this->form_fields; }
		public function get_uploaded_files() { return $this->uploaded_files; }
		public function get_results() { return $this->results; }
		public function set_result( $result ) { $this->results[ $result['type'] ][] = $result; }
	}
}
