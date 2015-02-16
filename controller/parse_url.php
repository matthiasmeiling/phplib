<?php
	require_once ('utils.php');
	class UrlParser {
		function __construct ($url) {
			$this->url = $url;
			$this->realurl = my_realpath ($url);
			$this->dir = my_dirname ($url);
			$this->file = basename ($url);
			$this->extension = "";
			$this->basefile = $this->file;
			if ( ($dotpos = strrpos ($this->file, ".")) !== False ) {
				$this->extension = substr ($this->file, $dotpos + 1);
				$this->basefile = substr ($this->file, 0, $dotpos);
			}
			
			$this->parentdir = $this->dir;
			if ( ($last_slash = strrpos ($this->dir, "/")) !== false ) {
				$this->parentdir = substr ($this->dir, $last_slash + 1);
			}
		}
	}
?>
