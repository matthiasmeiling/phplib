<?php
	require_once ('utils.php');
	require_once ('login_controller.php');
	global $SCRIPTS;
	$SCRIPTS = array (
		'jquery'=>'extern/jquery/jquery-2.1.1.min.js',
		'jquery-ui'=>'extern/jquery/jquery-ui.min.js',
		'ckeditor'=>'extern/ckeditor/ckeditor.js',
		'fileupload'=>
			array ('extern/fileupload/js/vendor/jquery.ui.widget.js',
				'extern/fileupload/js/jquery.iframe-transport.js',
				'extern/fileupload/js/jquery.fileupload.js'),
		'attachment'=>'model/attachment.js'
	);
	$EXTERN_SCRIPTS = array (
		'google-maps'=>'https://maps.googleapis.com/maps/api/js?v=3.exp'
	);

	/**
	 * Content Management Class.
	 * Provides all functionality used by View-Scripts.
	 */
	class Cms {
		public function __construct ($base_file, $url) {
			$this->url = $url;
			$this->basepath = realpath (dirname (__FILE__)."/../");
			$this->get = $_GET;
			$this->post = $_POST;
			$this->template = "standard";
			$this->template_path = $this->basepath.'/templates/'.$this->template;
			$this->styles = array ();
			$this->scripts = array ();
			$this->extern_scripts = array ();

			//var_dump ($_GET);
		
			
			/* login if neccessary */
			$this->login = new LoginController ();
			if (!$this->login->Login ($_POST)) {
				if (!$this->page ("login")) {
					die ("no login-view exists!");	
				}
			} else {
				if ( !$this->page ($base_file) )  {
					$this->page404 ();
				}
			}
		}

		/**
		 * executes a script from Template directory. E.g. you want to include content_head.php you only have to call
		 * $cms->get_tmpl ("content_head");
		 */

		public function get_tmpl ($string) {
			$filename = $this->template_path . '/'. $string . '.php';
			if (file_exists ($filename)) {
				if (! @include ($filename)) {
					debug ('some error occured while including '.$filename);
				}
			} else {
				debug ('file not found: '.$filename);
			}
		}

		private function page ($base_file) {
			$view_dir = dirname (__FILE__) . "/../view/";
			/* call the base_file from view directory */
			$file = $view_dir . basename ($base_file) . ".php";
			if (file_exists ($file)) {
				require_once ($file);
				if (class_exists ($base_file)) {
					$content = new $base_file ($this);
					$this->header ();
					$content->view ();
					$this->footer ();
					return true;
				}
			} 
			return false;
		}

		private function page404 () {
			echo ("404 Error.");
		}

		private function set_styles () {
			/* look for style.css */	
			if (is_dir ($this->template_path)) {
				if ($dh = opendir($this->template_path)) {
					while (($file = readdir($dh)) !== false) {
						if ($file == "style.css") {
							$file = $this->template_path . "/" . $file;
							array_unshift ($this->styles, $file); 
						}
					}
					closedir($dh);
				}
			}
			foreach ($this->styles as $style) {
				$script_path = my_realpath ($this->basepath.'/'.$this->url->dir.'/');
				echo ('<link rel="stylesheet" type="text/css" href="'.relpath ($style, $script_path).'">');
			}
		}
		
		private function set_scripts () {
			$script_path = my_realpath ($this->basepath.'/'.$this->url->dir.'/');
			/* set gloabl PATH variable */
			echo ('<script type="text/javascript">var BASE_PATH = "'.relpath (my_realpath(dirname (__FILE__).'/../'), $script_path).'";</script>');
			foreach ($this->scripts as $script) {
				echo ('<script type="text/javascript" src="'.relpath ($script, $script_path).'"></script>');
			}
		}
		
		/**
		 * Adds a script into the scripts/extern_scripts array
		 * is called by add_script. 
		 * @param script may be either array or string. script path. Either URL (for extern) or file path to script (local)
		 * @param extern if this is set, the script is added to extern scripts array
		 */
		private function priv_add_script ($script, $extern=false) {
			$base = dirname (__FILE__).'/../';
			if ( !is_array ($script) ) {
				$script = array ($script);
			}
			if (!empty ($script)) {
				foreach ($script as $part) {
					if ($extern) {
						$this->extern_scripts [] = $part;
					} else {
						$this->scripts [] = my_realpath($base.$part);
					}
				}
			}
			
		}

		public function add_script ($script_name) {
			global $SCRIPTS, $EXTERN_SCRIPTS;
			if (array_key_exists ($script_name, $SCRIPTS)) {
				$this->priv_add_script ($SCRIPTS[$script_name], false);
			} elseif (array_key_exists ($script_name, $EXTERN_SCRIPTS)) {
				$this->priv_add_script ($EXTERN_SCRIPTS[$script_name], true);
			}
		}

		private function tbd_add_script ($script_name) {
			global $SCRIPTS, $EXTERN_SCRIPTS;
			$base = dirname (__FILE__).'/../';
			if (array_key_exists ($script_name, $SCRIPTS)) {
				/* local scripts */
				$script = $SCRIPTS[$script_name];
				if ( is_array ($script) ) {
					foreach ($script as $part) {
						$this->scripts [] = my_realpath($base.$part);
					}
				} else {
					$this->scripts [] = my_realpath($base.$script);
				}
			} elseif (array_key_exists ($script_name, $EXTERN_SCRIPTS)) {
				/* extern/remote scripts */
				$script = $EXTERN_SCRIPTS[$script_name];
				
			}

		}
		
		/**
		 * Adds style from template directory.
		 * Make sure, that you only specify the filename without suffix *.css.
		 */
		public function add_template_style ($style_name) {
			$filename = $this->template_path . '/'. basename($style_name) . '.css';
			if (file_exists ($filename)) {
				$this->styles [] = my_realpath($filename);
			}
		}

		/**
		 * writes header to browser
		 */
		public function header () {
			echo ('<!DOCTYPE html>
				<html>
					<head>');
			$this->set_styles ();
			$this->set_scripts ();
			echo ('</head>
				<body>');
			$this->header = function () {};
		}

		/**
		 * writes footer to browser.
		 */
		public  function footer () {
			echo ('</body></html>');
		}

	}
?>
