<?php
	require_once ('utils.php');
	require_once ('../model/config.php');
	/* VERSION 1.1 */

	class DBController {
		private $link = null;
		private $dbs = Array();				/* stores names of all databases we use here as an array */
		private $install_dbs = Array();		/* stores sql-statements for creating db */
		public $stmt = null; 				/* stores last prpared statement */

		function __construct() {
			global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_DB;

			// Connect to mysql-database 
			$this->link = new mysqli(
				$DB_HOST,
				$DB_USER,
				$DB_PASSWORD,
				$DB_DB
			);

			if ($this->link->connect_error){
				/* was not able to connect to db */
				throw new Exception($this->link->connect_error);
			}

		}

		function __destruct() {
			if ($this->link){
				$this->link->close();
			}
		}

		/**
		* checks weather database name is one of us 
		*/
		function check_db_name($name){
			return  array_key_exists($name, $this->dbs) ;
		}

		/**
		* Dummy-Update
		*/
		private function update01() {
			$sql = "ALTER TABLE `".$this->dbs['items']."` ADD (
					tags VARCHAR(2048)
				)";
			$this->perform_query($sql);
		}


		/** Appends custom table declarations to the controller
		    See: Install () which creates these tables. 
			NOTE: each table name will be prefixed with configured prefix.
		*/
		function AddInstall ($array) {
			global $DB_PREFIX;
			foreach ($array as $key=>$value) {
				$dbname = $DB_PREFIX.$key;
				$sql = 'CREATE TABLE IF NOT EXISTS `'.$dbname.'` ( '.$value.' ) ENGINE=InnoDB';
				if (!array_key_exists ( $key, $this->dbs)) {
					$this->dbs[$key] = $dbname;
					$this->install_dbs[$dbname] = $sql;
				} else {
					throw new Exception("table name already used: ".$dbname);
				}
			}
		}

		/** 
		* Installs all required database tables and views.
		*/
		function Install() {
			if (is_array ($this->dbs)) {
				foreach ($this->install_dbs as $dbname=>$sql) {
					$this->perform_query ($sql);
				}
			}
		}
		
		/**
		* performs a query on server.
		* @return If no error occurs the resultset is returned
		* @throws Exception if error occurs.
		*/
		function perform_query($query) {
			$result = $this->link->query ($query);
			if (!$result){
				throw new Exception($this->link->error);
			} else {
				return $result;
			}
		}
		
		/**
		* gets results from given database 
		* @returns named array
		*/
		function fetch(
					$name_key, /**< database name key */
					$limit = array (-1, -1)
				){
			$resultset = array();
			if ( $this->check_db_name($name_key) ){
				$name = $this->dbs[$name_key];
				$resultset =  $this->fetch_by_name($name, $limit);
			}
			return $resultset;
		}

		/**
		* Fetch results from DB with given ID 
		* This function expects a id column in given database 
		*/

		function fetch_id(
			$name_key /**< database name key */,
			$id /**< id column */
		){
			return $this->fetch_key ($name_key, "id", $id, "integer");
		}

		function fetch_key (
			$name_key /**< database name key */,
			$key /**< where key  */,
			$value /**< where value */,
			$type /**< type of $value, has to be string or integer */
		){
			$where = "";

			/* check type */
			if ( strcmp ( $type, "string" ) == 0 ){
				$where = sprintf("WHERE %s = '%s'", $this->escape($key), $this->escape($value));
			} else if ( strcmp ( $type, "integer" ) == 0 ) {
				$where = sprintf("WHERE %s = %d", $this->escape($key), intval($value));
			} else {
				throw new Exception("type has to be string or integer");
			}
			
			/* get result */
			if ( $this->check_db_name($name_key) ){
				$name = $this->dbs[$name_key];
				$sql = sprintf("SELECT * FROM `%s` ".$where, $this->escape($name));
				return $this->fetch_resultset($sql);	
			}
			return array();
		}

		/**
		* Fetches Resultset from sql query
		* NOTE: checks for column name deleted. if value == 1 it will be not fethced.
		*/
		protected function fetch_resultset($sql/**< sql query which has to be performed */){
			$resultset = array();
			$result = $this->perform_query($sql);
			while ( ($row = $result->fetch_assoc()) ){
				if (array_key_exists ("deleted", $row) ){
					if ($row['deleted'] == 1) {
						continue;
					}
				}
				/* reescape content */
				$tmp = array();
				foreach ($row as $key => $val){
					$tmp[$key] = $this->unescape($val);
				}
				$resultset[]= $tmp;
			}
			$result->free();
			return $resultset; 
		}

		
		/** 
		* Fetches data by real database name. This may be security problematic
		*/

		protected function fetch_by_name(
					$name/**< database name */,
					$limit = array (-1, -1)
				){
			if ($limit[0] < 0 or $limit [1] <= 0) {
				$limit = "";
			} else {
				$limit = sprintf ("LIMIT %d, %d", $limit[0], $limit[1]);
			}
			/* do not any checkings on the name */
			$sql = sprintf("SELECT * FROM `%s` %s", $this->escape($name), $limit);
			return $this->fetch_resultset($sql);	
		}

		/**
		* converts Mysql-Type-string into string or integer. see: ::fetch_meta
		* @returns string | integer
		*/
		function convert_mysql_type (
			$type /**< MYSQL-type */){
			if ( stristr($type, "int") != false ) {
				return "integer";
			} else {
				return "string";
			}
		}

		/**
		* fetch columns of given table 
		*/
		function fetch_meta($name_key /**< database name key */ ) {
			$resultset = array();
			if ( $this->check_db_name($name_key) ){
				$name = $this->dbs[$name_key]; 
				$sql = 'SHOW COLUMNS FROM '.$name;
				$result = $this->perform_query($sql);
				while ($row = $result->fetch_assoc() ){
					$resultset[$row['Field']] = $this->convert_mysql_type($row['Type']);
				}
			}
			return $resultset;
		}

		/**
		* fetch all foreign-key-tables
		* TODO: do the representation_column
		*/
		function fetch_foreign($name_key/**< databeas name key */){
			$resultset = array();
			if ( $this->check_db_name($name_key) ){
				$name = $this->dbs[$name_key];
				/* get the foreign key informations. Adapted from: 
					http://stackoverflow.com/questions/201621/how-do-i-see-all-foreign-keys-to-a-table-or-column
					26. Jun. 2013
				*/
				$sql = "
					SELECT 
						TABLE_NAME,
						COLUMN_NAME,
						CONSTRAINT_NAME,
						REFERENCED_TABLE_NAME,
						REFERENCED_COLUMN_NAME 
					from 
						INFORMATION_SCHEMA.KEY_COLUMN_USAGE
					where
						CONSTRAINT_NAME != 'PRIMARY' AND
						TABLE_NAME = '".$this->escape($name)."' AND 
						
						REFERENCED_COLUMN_NAME IS NOT NULL AND 
						REFERENCED_TABLE_NAME  IS NOT NULL
					
				";
				$result = $this->perform_query($sql);
				while ( ($row = $result->fetch_assoc() ) ){
					$resultset[$row['COLUMN_NAME']] = 
						array (
							"values" => $this->fetch_by_name($row['REFERENCED_TABLE_NAME']),
							"column" => $row['REFERENCED_COLUMN_NAME'],
							"column_repr" => ""
						);
				}
				$result->free();

				return $resultset;
			}
		}

		/**
		* get all possible db_names
		*/
		function get_dbs(){
			return array_keys($this->dbs);
		}

		/**
		* get db from db_array
		*/
		function get_db($name){
			if ( $this->check_db_name($name) ){
				return $this->dbs[$name];
			} else {
				return false;
			}
		}
		
		/**
		* escapes stirings for database usage
		*/
		function escape($string){
			return $this->link->real_escape_string($string);
		}

		/**
		* unescapes strings which were escaped by escape 
		*/
		function unescape($string) {
			return stripslashes($string);
		}

		/* ************ UPDATER ************ */

		/**
		* match input values with meta values, eg. for insert_row or edit_row
		* @returns: array( column-names, corresponding-values ), where coulumn-names and values are arrays with same keys for iteration, or array() if an error occurs anywhere
		*/
		private function match_meta_values (
			$meta /**< array of meta - values */,
			$input_values /**< values to be matched with meta-array */){
			
			if ( !empty($meta) ) {
				$col_names = array();
				$values = array ();
				/* convert values into correct value array */
				foreach ( $meta as $col_name => $type ) {
					$col_names [] = $col_name;
					if ( array_key_exists($col_name, $input_values) ){
						/* check type */
						$value = $input_values[$col_name];
						if ( strlen($value) == 0 ){
							/* value is empty, so set value as NULL */
							$values[] = 'NULL';
						} else {
							/* set value as int or string value */
							if ( strcmp($type, "integer") == 0 ) {
								$values[] = intval($value);
							} else {
								$values[] = "'".$this->escape($value)."'";
							}
						}
					} else {
						$values[] = 'NULL';
						//return array();
					}
				}

				return array($col_names, $values);
			}
			return array();

		}
		/**
		 * returns last AUTO_INCREMENT number
		 * see: http://dev.mysql.com/doc/refman/5.0/en/getting-unique-id.html
		 */
		function get_used_id () {
			$result = $this->perform_query ('SELECT LAST_INSERT_ID() as id');
			if ( ($row = $result->fetch_assoc()) ){
				return $row['id'];
			} else {
				return -1;
			}
		}

		/**
		* insers row into table name_key
		* @returns True on success, False on wrong input and throws exception if sql error occurs
		*/
		function insert_row(
			$name_key /**< database name */,
			$input_values /**< assoc array (e.g. $_POST) */){
			$meta = $this->fetch_meta($name_key);
			$matching = $this->match_meta_values($meta, $input_values);
			if ( !empty($matching) ){
				
				/* prepare sql statement */
				$sql = 'INSERT INTO '.$this->get_db($name_key).' ( '.implode(",", $matching[0]).' ) VALUES ( '.implode(",", $matching[1]).' )';
				$this->perform_query($sql);
				$this->used_id = $this->get_used_id ();
				return true; 

			}
			return false;
		}

		/**
		* edits a given row of a table
		* NOTE: table has to have a column with name id!!! TODO: change id to - all old entries. so e.g. there may be $_POST['old_id'] and $_POST['id']. Put all $_POST['old_*'] into where clause (see ::delete_row)
		*/
		function edit_row (
			$name_key /**< Database name */,
			$id /**< id of database */,
			$input_values /**< values to be updated */,
			$ignoreNull = false){
			
			$meta = $this->fetch_meta($name_key);
			$matching = $this->match_meta_values($meta, $input_values);
			if ( array_key_exists("id", $meta) and !empty($matching) ){
				$sql_values = array();
				for ( $i = 0 ; $i<count($matching[0]); $i++){
					if ($ignoreNull && strcmp ($matching[1][$i], "NULL") == 0 ){
						continue;
					}
					$sql_values [] = $matching[0][$i] .'='. $matching[1][$i];
				}

				/* prepeare id */
				if ( strcmp ($meta['id'], "integer") == 0){
					$id = intval($id);
				} else {
					$id = "'".$this->escape($id)."'";
				}
				$sql = 'UPDATE '.$this->get_db($name_key).' SET '.implode(",", $sql_values).' WHERE id='.$id; 
				$this->perform_query($sql);
				return true;
			}
			return false;
		}

		/**
		* deletes a given row of  a table 
		*/

		function delete_row (
			$name_key /**< database name */,
			$input_values /**< valus to be checked in where clause */,
			$checkNull = true
		){
			$meta = $this->fetch_meta($name_key);
			$matching = $this->match_meta_values($meta, $input_values);	 /* get the matching values, if $meta-key not found in $input_values, you will get an empty array() */

			if ( !empty($matching) ){
				$sql_values = array();
				for ( $i = 0 ; $i<count($matching[0]); $i++){
					if ( strcmp($matching[1][$i], "NULL") == 0){
						/* check weather value is a null value */
						if ($checkNull) {
							$sql_values [] = $matching[0][$i] .' IS '. $matching[1][$i];
						}
					} else {
						$sql_values [] = $matching[0][$i] .'='. $matching[1][$i];
					}
				}
				

				$sql = 'DELETE FROM '.$this->get_db($name_key).' WHERE '.implode(' AND ', $sql_values).' LIMIT 1';
				$this->perform_query($sql);
				return true;

			}
			return false;
		}
		
	}

	/* immediately create an instance */
	global $db_controller;
	$db_controller = new DBController()
?>
