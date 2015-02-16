<?php
	require_once dirname (__FILE__)."/database.php";
	require_once dirname (__FILE__)."/utils.php";

	class Tteam {
		/**
		 * updates profile from current array.
		 * Note: this function updates the db-entry without authority checking.
		 */
		public function update_profile ($array) {
			global $db_controller;
			if (KeysExist (array (
						'id',
						'vorname',
						'nachname',
						'strasse',
						'plz',
						'ort',
						'tel',
						'fax',
						'email',
						'bild'
					), $array )) {
				return $db_controller->edit_row (
					'profiles',
					$array['id'],
					$array,
					true);
			} else {
				return false;
			}

		}

		public function __construct () {
			global $db_controller;
			global $DB_PREFIX;
			$db_controller->AddInstall (
				Array (
					'attachments' => '
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						owner INT NOT NULL,
						target VARCHAR (128),
						size INT,
						mime VARCHAR (64),
						FOREIGN KEY (owner) REFERENCES '.$DB_PREFIX.'users (id)
					',
					'kontakte' => '
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						owner INT NOT NULL,
						vorname VARCHAR (48),
						nachname VARCHAR (48),
						strasse VARCHAR (48),
						plz VARCHAR (16),
						ort VARCHAR (64),
						tel VARCHAR (32),
						tel_add VARCHAR (64),
						fax VARCHAR (32),
						email VARCHAR (128),
						FOREIGN KEY (owner) REFERENCES '.$DB_PREFIX.'users (id)
					', 
					'profiles' => '
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						owner INT NOT NULL,
						vorname VARCHAR (48),
						nachname VARCHAR (48),
						strasse VARCHAR (64),
						plz VARCHAR (16),
						ort VARCHAR (64),
						gps VARCHAR (32),' . /* e.g. 50.337256,6.896306 */
						'tel VARCHAR (32),
						tel_add VARCHAR (64),
						fax VARCHAR (32),
						email VARCHAR (128),
						rang_pfd INT,
						rang_hd INT,
						mitglied_pfd TINYINT DEFAULT 0,
						mitglied_hd TINYINT DEFAULT 0,
						gilde_d TINYINT DEFAULT 0,
						letztes_update DATE,
						letztes_adv DATE,
						letztes_cert DATE,
						fortbildungen INT,
						bild INT,
						foerdermitglied TINYINT DEFAULT 0,
						cv VARCHAR (4048),
						cv_pdf INT,
						FOREIGN KEY (bild) REFERENCES '.$DB_PREFIX.'attachments (id),
						FOREIGN KEY (cv_pdf) REFERENCES '.$DB_PREFIX.'attachments (id),
						FOREIGN KEY (owner) REFERENCES '.$DB_PREFIX.'users (id)
					',
					'termine' => '
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						confirmed TINYINT DEFAULT 0,
						prac INT NOT NULL,
						von DATE NOT NULL,
						bis DATE NOT NULL,
						art VARCHAR (128),
						ort VARCHAR (128),
						description VARCHAR (4048),
						kontakt INT NOT NULL,
						ausbildung TINYINT DEFAULT 0,
						FOREIGN KEY (prac) REFERENCES '.$DB_PREFIX.'profiles (id),
						FOREIGN KEY (kontakt) REFERENCES '.$DB_PREFIX.'kontakte (id)'
					)
				);
		}
	}
?>
