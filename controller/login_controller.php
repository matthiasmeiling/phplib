<?php
	/* VERSION 1.0 */
	require_once dirname (__FILE__)."/database.php";
	require_once dirname (__FILE__)."/utils.php";

	session_start ();

	class LoginController {
		public $HASH_SALT = "65ac8706622a151064cac7ec3744a484";
		public $msg = "";
		private $expire = 86400;		// interval [s] for session expire, when no logincheck was performed
		public function __construct () {
			global $db_controller;
			$db_controller->AddInstall (
				Array (
					'users' => '
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						user VARCHAR (256) NOT NULL,
						hash VARCHAR (256) NOT NULL,
						email VARCHAR (256) NOT NULL,
						role INTEGER NOT NULL,
						confirmed TINYINT,
						home VARCHAR (256),
						soft_limit INTEGER,
						hard_limit INTEGER,
						session VARCHAR (256),
						expire DATETIME'
				)
			);
		}
		
		/** This class memeber has to be extended by child class */
		//public function Check

		/** Performs login from given named array which has at least the following keys:
		    - user: user name
			- pass: password
			@return eiher true if login succeeded or false if not <br>
					if user not confirmed yet, the false will be returned and the msg variable will be set. 
		*/
		public function Login ($array) {
			if ( ( !empty ( $array['user'] ) && ( !empty ( $array['pass'] ) ) ) ) {
				$pass = $array['pass'];
				$user = $array['user'];
				$hash = $this->Hash ($user, $pass);
				$info = $this->GetUserInfo ($user);
				//var_dump ($info);
				//var_dump ($hash);
				if ( !is_null ($info) and strcmp ( $hash, $info['hash'] ) == 0 ) {
					if ( $info['confirmed'] != 1) {
						$this->msg = 'User not confirmed';
						return false;
					} else {
						$this->StartSession ($info);
						/* WORKS */
						return true;
					}
				} else {
					$this->msg = 'User unknown or wrong password!';
					return false;
				}
			} else {
				return $this->CheckSession ();
			}
			return false;
		}

		public function Logout () {
			$this->StopSession ();
		}

		private function StartSession ($uinfo) {
			global $db_controller;
			$this->info = $uinfo;
			$_SESSION['user'] = $uinfo['user'];
			$db_controller->edit_row ("users", $uinfo['id'], array("session" => $this->SessionId()), true);
			$this->UpdateSession ();
		}

		private function StopSession () {
			session_destroy ();
		}
		
		/** Generates a Hashvalue for the session id to be stored in the database */
		private function SessionId () {
			return md5 (session_id () . $this->HASH_SALT);
		}

		/** Check weather session is still alive 
			@return true if session still alive, false otherwise (sets msg containing the reason)
		*/
		public function CheckSession () {
			/* check existing session array */
			//var_dump ($_SESSION);
			if ( KeysExist (array ("user", "expire"), $_SESSION) ) {
				/* First check expire */
				if ($_SESSION['expire'] > time () ){
					$info = $this->GetUserInfo ($_SESSION['user']);
					//var_dump( $info);
					if ($info) {
						if (strcmp ($info['session'], $this->SessionId ()) == 0) {
							$this->UpdateSession ();
							$this->info = $info;
							return true;
						}
					}
				} else {
					$this->msg = "Session expired";
					return false;
				}
			} 
			return false;
		}

		private function UpdateSession () {
			$_SESSION['expire'] = time () + $this->expire;
		}

		private function GetUserInfo ($user) {
			global $db_controller;
			$result = $db_controller->fetch_key ('users', 'user', $user, 'string');
			if  (count ($result) == 1) {
				return $result[0];
			} else {
				return null;
			}
		}

		public function Hash ($user, $pass) {
			/* /bin/bash 
			   md5 -q -s 65ac8706622a151064cac7ec3744a484$PASS`md5 -q -s $USER`
			*/
			return md5 ($this->HASH_SALT . $pass . ( md5 ( $user ) ) );
		}

	}
?>
