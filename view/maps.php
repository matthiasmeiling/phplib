<?php
	require_once (dirname (__FILE__).'/../controller/tteam.php');
	class maps {
		public function __construct ($cms) {
			$this->cms = $cms;
			$cms->add_script ('jquery');
			$cms->add_script ('jquery-ui');
			$cms->add_template_style ('jquery-ui');
	
		}
		public function view () {
			echo 'Hello Wold!';
		}

	}
?>
