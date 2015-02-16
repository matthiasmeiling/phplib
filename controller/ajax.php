<?php
	require_once ("../model/config.php");			/* confiugration */
	require_once ("./database.php");				/* creates the global db_controller instnace */
	require_once ("./database_updater.php");		/* creates the global db_update_controller instnace */
	/**
	* This is the ajax - controller, which gets POST - DBs Requests and returns a string as HTML-Code 
	*/
	class AjaxController {
		/**
		* checks weather values from array are in $_POST-Array 
		* @returns True if all values are in $_POST, False otherwise
		*/
		function values_in_post(
			$values /**< value-array, which should be checked */
		){
			foreach ($values as $value){
				if (  !array_key_exists($value, $_POST) ){
					return false;
				}
			}
			return true;
		}
		
		/**
		* checks weather values from array are in $_GET-Array 
		* @returns True if all values are in $_GET, False otherwise
		*/
		function values_in_get(
			$values /**< value-array, which should be checked */
		){
			foreach ($values as $value){
				if (  !array_key_exists($value, $_GET) ){
					return false;
				}
			}
			return true;
		}

		/**
		 * Performs a user login and retruns a validation hash
		 */
		function login ($user, $pwd) {
			return "DUMMYHASH";
		}

		/** 
		* checks if user and password are consistent and if you are restricted to perform the given action.
		* TODO: currently just a DUMMY. 
		*/
		function check_user ( $user, $hash ){
			return true;	

		}


		/**
		* if login-data are correct, return true, otherwise false
		*/
		function check_login () {
			if ( true || $this->values_in_get(array("user", "hash")) ){
				/* check pw and user */
				return $this->check_user ($_GET['user'], $_GET['hash']);
			}
			return false;
		}

		function __construct(){
			/** Databases which will be returned through get request */
			$this->GET_NAMES = array (
					'items',
					'provanance',
					'categories',
					'techniques',
					'countries',
					'locations',
					'conditions',
					'persons',
					'images',
					'view_items'
				);
			$this->UPDATE_NAMES = array (
					'items',
					'provanance',
					'categories',
					'techniques',
					'countries',
					'locations',
					'conditions',
					'persons',
					'images'
			);
			$this->INSERT_NAMES = array (
					'items',
					'provanance',
					'categories',
					'techniques',
					'countries',
					'locations',
					'conditions',
					'persons',
					'images'
			);
			$this->DELETE_NAMES = array (
					'items',
					'provanance',
					'categories',
					'techniques',
					'countries',
					'locations',
					'conditions',
					'persons',
					'images'
			);

			$this->msg = 'unknown';
			
		}

		/**
		 * returns list of files from Type $type
		 */
		function get_files ($type) {
			$IMGBASE = dirname (__FILE__) . '/../js/fileupload/upload/';
			$dh = opendir ($IMGBASE);
			while (false !== ($file = readdir($dh)) ){
				if (is_file ($IMGBASE . $file)) {
					$files[] = $file;
				}
			}
			closedir ($dh);
			return $files;
		}


		/**
		 * performs an update on an existing row
		 */
		function perform_update ($array) {
			global $db_update_controller;
			if (in_array ($array['table'], $this->UPDATE_NAMES) and !empty ($array['id']) ){
				if ($db_update_controller->edit_row($array['table'], $array['id'], $array)){
					return $array['id'];
				} else {
					$this->msg = $array['table']. ": unknown error while updating row";
					return false;
				}
			} else {
				$this->msg = $array['table']. ": not updateable.";
				return false;
			}
			return false;
		}

		/**
		 * performs an insert into the database
		 */
		function perform_insert ($array) {
			global $db_update_controller;
			try {
				if (in_array ($array['table'], $this->INSERT_NAMES) ){
					$retval = $db_update_controller->insert_row($array['table'], $array);
					$this->msg = $db_update_controller->used_id;
					return $retval;
				} else {
					$this->msg = $array['table']. ": not updateable.";
					return false;
				}
				return false;
			} catch (Exception $e) {
				$this->msg = $e->getMessage();
				return false;
			}
		}
		
		/**
		 * performs an delete of one cell in the database
		 */
		function perform_delete ($array) {
			global $db_update_controller;
			if (in_array ($array['table'], $this->DELETE_NAMES) ){
				if (!empty ($array['id'])) {
					$array['deleted'] = 1;
					$retval = $db_update_controller->edit_row($array['table'], $array['id'], $array, true);
					return $retval;
				}
				$this->msg = "Id has to be set";
			} else {
				$this->msg = $array['table']. ": not updateable.";
				return false;
			}
			return false;
		}



		/**
		 * Creates a tuple of JSON([true|false], returnValue) as a retrunvalue for the remote application
		 */
		function r ( $success, $retVal ) {
			if (!$retVal) {
				$retVal = $this->msg;
			}
			echo json_encode (array ($success, $retVal));
			die ();
		}

		function HandleRequest () {
			global $db_controller;
			if ( strcmp ($_GET['action'], "login") == 0) {
				$hash = $this->login ($_GET['user'], $_GET['pwd']);
				if ( !empty ($hash) ) {
					$this->r(true, $hash);
				} else {
					$this->r(false, $hash);
				}
			}
			if ( $this->check_login () ) {
				$action = $_GET['action'];
				if ( $action == "get" ) {
					/* action=get&name=items&offset=1&count=2 */
					$name = $_GET['table'];
					if ( isset ( $name ) ) {
						$offset = -1;
						$count = -1;
						
						if ($this->values_in_get(array("count", "offset"))) {
							$offset = intVal ($_GET['offset']);
							$count = intVal ($_GET['count']);
						}

						$limit = array ($offset, $count);

						/* Fetch data */
						try {
							if (isset ($_GET['id'])) {
								/* only fetch id */
								$this->r (true, $db_controller->fetch_id ($name, $_GET['id']));	
							} else {
								/* fetch whole table */
								$this->r (true, $db_controller->fetch ($name, $limit));
							}
						} catch (Exception $e ) {
							$this->r (false, "Table ".$name." could not be fetched.");
						}
						$this->r(false, false);	
					} else if (isset ($_GET['files'])){
						/* retrun files from fileupload */
						$this->r(true, $this->get_files ($_GET['files']));
					}
				} else if (strcmp ($action, "update") == 0 ) {
					$this->r ($this->perform_update ($_GET), Null);
				} else if (strcmp ($action, "add") == 0 ) {
					$this->r ($this->perform_insert ($_GET), Null);
				} else if (strcmp ($action, "delete") == 0 ) {
					$this->r ($this->perform_delete ($_GET), Null);
				} else if (strcmp ($action, "install") == 0) {
					/* action=install */
					$db_controller->install ();
					$this->r (true, "successfully installed.");
				} else {
					$this->r(false, "unknown action: " . $action);
				}
			} else {
				$this->r (false, "Login Failed");
			}
		}


	}

	$ajax = new AjaxController();
	$ajax->HandleRequest ();

?>
