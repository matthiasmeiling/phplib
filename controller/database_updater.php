<?php
	/* DELETED: VERSION 1.0 */
	require_once(dirname(__FILE__)."/database.php");
	
	class DBUpdater {
		protected $controller;
		public $result;
		public $meta;
		public $foreign;
		function __construct(){
			global $db_controller;
			$this->controller = $db_controller;
		}
		
		function __destruct() {
		}

		/** fetches table content for ndatabase $name_key 
		* sets ::result as the same as  resultset from db_controller->fetch()
		* sets ::meta as keys (or colnames) from ::result
		* @returns ::result
		*/

		function fetch($name_key) {
			$this->result = $this->controller->fetch($name_key)	;
			return $this->fetch_additional($name_key);
		}

		function fetch_id($name_key, $id){
			/* just invoke parent funciton */
			$this->result = $this->controller->fetch_id($name_key, $id);
			return $this->fetch_additional($name_key);
		}

		/**
		* invoked afte each fetch function to fetch the meta and foreign as well
		*/
		protected function fetch_additional($name){
			$this->meta = $this->controller->fetch_meta($name);
			$this->foreign = $this->controller->fetch_foreign($name);
			return $this->result;
		}

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
								$values[] = "'".$this->controller->escape($value)."'";
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
			$result = $this->controller->perform_query ('SELECT LAST_INSERT_ID() as id');
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
			$meta = $this->controller->fetch_meta($name_key);
			$matching = $this->match_meta_values($meta, $input_values);
			if ( !empty($matching) ){
				
				/* prepare sql statement */
				$sql = 'INSERT INTO '.$this->controller->get_db($name_key).' ( '.implode(",", $matching[0]).' ) VALUES ( '.implode(",", $matching[1]).' )';
				$this->controller->perform_query($sql);
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
			
			$meta = $this->controller->fetch_meta($name_key);
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
					$id = "'".$this->controller->escape($id)."'";
				}
				$sql = 'UPDATE '.$this->controller->get_db($name_key).' SET '.implode(",", $sql_values).' WHERE id='.$id; 
				$this->controller->perform_query($sql);
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
			$meta = $this->controller->fetch_meta($name_key);
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
				

				$sql = 'DELETE FROM '.$this->controller->get_db($name_key).' WHERE '.implode(' AND ', $sql_values).' LIMIT 1';
				$this->controller->perform_query($sql);
				return true;

			}
			return false;
		}
	}


	global $db_update_controller;
	$db_update_controller = new DBUpdater();
?>
