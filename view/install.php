<?php
	class Install {
		public function __construct ($cms) {
			$this->cms = $cms;	
		}

		public function view () {
			$info = $this->cms->login->info;
			/* TODO: check with unauthorized instance */
			if (strcmp ($info['role'],"0") == 0 and strcmp ($info['confirmed'], "1") == 0) {
				global $db_controller;
				require_once (dirname (__FILE__)."/../controller/tteam.php");
				$t = new Tteam ();
				$db_controller->Install ();
				echo ("<p>Install successfull</p>");
			} else {
				echo ("<p>Not Authorized</p>");
			}
		}
	}
?>
