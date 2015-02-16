<?php
	require_once (dirname (__FILE__).'/../controller/tteam.php');
	class profil {
		public function __construct ($cms) {
			$this->cms = $cms;
			$cms->add_script ('jquery');
			$cms->add_script ('jquery-ui');
			$cms->add_script ('ckeditor');
			$cms->add_script ('fileupload');
			$cms->add_script ('attachment');
			$cms->add_template_style ('/PREFIX_NOT_CONSIDERED/jquery-ui');
			$this->tteam = new Tteam ();
		}

		private function get_profile ($user, $profile=null) {
			global $db_controller;
			$results = $db_controller->fetch_key ('profiles', 'owner', $user['id'], 'integer');
			if ($profile == null) {
				return $results;
			} else {
				$retval = Array ();
				/* check weather owner matches */
				foreach ($results as $result) {
					if ($result['id'] == $profile) {
						$retval[] = $result;
					}
				}
				return $retval;
			}
		}

		private function tteam_spec ($p, $readonly=false) {
			$retval = "";
			$ro = ($readonly) ? 'disabled' : '';
			$hd_checked = (strcmp($p['mitglied_hd'], "1") == 0) ? 'checked' : ''; 
			$pfd_checked = (strcmp($p['mitglied_pfd'], "1") == 0) ? 'checked' : ''; 
			$gilded_checked = (strcmp($p['gilde_d'], "1") == 0) ? 'checked' : ''; 
			$foerder_checkd = (strcmp($p['foerdermitglied'], "1") == 0) ? 'checked' : ''; 
			$retval .=
				'<label>Mitglied Hunde (Rang): </label><input type="checkbox" name="mitglied_hd" value="1" '.$hd_checked.' '.$ro.'/> (<input type="text" name="rang_hd" value="'.$p['rang_hd'].'" '.$ro.'/>) <br>
				<label>Mitglied Pferde (Rang): </label><input type="checkbox" name="mitglied_pfd" value="1" '.$pfd_checked.' '.$ro.'/> (<input type="text" name="rang_pfd" value="'.$p['rang_pfd'].'" '.$ro.'/>) <br>
				<label>Gilde D: </label><input type="checkbox" name="gilde_d" value="1" '.$gilded_checked.' '.$ro.'/> <br>
				<label>Fortbildungen: </label><input type="text" name="fortbildungen" '.$ro.' /> <br>
				<label>F&ouml;rdermitglied: </label><input type="checkbox" name="foerdermitglied" value="1" '.$foerder_checked.' '.$ro.'/> <br>
				';
			return $retval;
		}

		private function update_profile ($post) {
			global $db_controller;
			$user = $this->cms->login->info;
			$ttcntr = $this->tteam;
			if (isset ($post['id'])) {
				$profiles = $db_controller->fetch_id ('profiles', $post['id']);
				if (count ($profiles > 0)) {
					$profile = $profiles[0];
					if (strcmp ($user['role'], '0') == 0 or $user['id'] == $profile['owner']) {
						$ttcntr->update_profile ($post);
					}
				}
			}
		}

		public function view () {
			$user = $this->cms->login->info;
			$profile = null;
			if (isset ($cms->get['profile'])) {
				$profile = $cms->get['profile'];
			}

			$this->update_profile ($this->cms->post);

			$profile_data = $this->get_profile ($user, $profile);
			if ( count ($profile_data) == 0) {
				echo '<p class="err_msg">Not Authorized to manage Profile '.$profile.'</p>';
			} elseif (count ($profile_data) == 1) {
				$p = $profile_data[0];
				//var_dump ($p);
				$this->cms->get_tmpl ('content_header');
				echo '<h1>'.$p['vorname'].' '.$p['nachname'].'</h1>';
				echo '<form method="POST" action="#" class="change_profile">
					  <input type="hidden" name="id" value="'.$p['id'].'"/>
					  <div class="kontakt">
					  	<input class="attachment" type="hidden" name="bild" value="'.$p['bild'].'"/>
						<h2>Kontakt:</h2> 
						<label>Vorname: </label><input type="text" name="vorname" value="'.$p['vorname'].'"/>
						<label>Nachname: </label><input type="text" name="nachname" value="'.$p['nachname'].'"/>
						<label>Stra&szlig;e: </label><input type="text" name="strasse" value ="'.$p['strasse'].'"/>
						<label>Postleitzahl: </label><input type="text" name="plz" value="'.$p['plz'].'"/>
						<label>Ort: </label><input type="text" name="ort" value="'.$p['ort'].'"/>
						<label>Telefon: </label><input type="text" name="tel" value="'.$p['tel'].'"/>
						<label>Telefon zus&auml;tzlich: </label><input type="text" name="tel_add" value="'.$p['tel_add'].'"/>
						<label>Fax: </label><input type="text" name="fax" value="'.$p['fax'].'"/>
						<label>eMail: </label><input type="text" name="email" value="'.$p['email'].'"/>
					  </div>';
				echo '<div class="tteam_spec">
						<h2>TTeam:</h2>';
				if (strcmp ($user['role'], "0") == 0) {
					/* here we have an admin */
					echo $this->tteam_spec ($p, false);
				} else {
					echo '<p>Die folgenden Werte geh&ouml;ren zu deinem Profil, k&ouml;nnen allerdings nur von einem Administrator ge&auml;ndert werden.</p>';
					echo $this->tteam_spec ($p, true);
				}
				echo '<div class="cv">
					  	<h2>Lebenslauf:</h2>
						<textarea name="cv" class="ckeditor">'.$p['cv'].'</textarea>
					  </div>';
				echo '<div class="control">
						<button type="submit">&Auml;nderungen anwenden</button>
					  </div>';
				$this->cms->get_tmpl ('content_footer');
			} else {
				echo '
					<table>
						<tr>
							<th>Name</th><th>Ort</th>
						</tr>';
				echo '</table>';
			}
		}
		
	}
?>
