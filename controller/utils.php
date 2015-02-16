<?php
	function debug ($string) {
		echo ('<pre class="debug">'.$string.'</pre>');
	}
	/** checks weather given keys exist in array,
		typically used for checking whather some keys are present in $_POST/$_GET array
	*/
	function KeysExist ( $keys, $array ) {
		foreach ($keys as $key) {
			if (! array_key_exists ($key, $array) ) {
				return false;
			}
		}
		
		return true;
	}
	/**
	 * relative filepath. Thanks to http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
	 * switched from and to similar to python function.
	 */
	function relpath($to, $from) {
		// some compatibility fixes for Windows paths
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from ;
		$to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
		$from = str_replace('\\', '/', $from);
		$to   = str_replace('\\', '/', $to);

		$from     = explode('/', $from);
		$to       = explode('/', $to);
		$relPath  = $to;

		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}

	function my_dirname ($path) {
		if ( ($pos = strrpos ($path, '/')) !== false ) {
			return substr ($path, 0, $pos);
		}
		return '';
	}

	function my_realpath ($path) {
		$start = "";
		$end = "";
		$sp_path = explode ('/', $path);
		$rel_path = array ();

		foreach ($sp_path as $p) {
			if ($p == "." or $p == "") {
				continue;
			} else if ($p == "..") {
				array_pop ($rel_path);
			} else {
				$rel_path [] = $p;
			}
		}
		if (strpos ($path, '/') == 0) {
			$start = '/';
		}
		if (strrpos ($path, '/') == strlen ($path) - 1) {
			$end = '/';
		}

		return $start . implode ("/", $rel_path) . $end;
	}

?>
