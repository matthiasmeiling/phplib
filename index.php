<?php 
	require_once (dirname (__FILE__) ."/controller/parse_url.php");
	require_once (dirname (__FILE__) ."/controller/cms.php");
	$p = new UrlParser ($_GET['p']);
	$file = "";
	if ($p->file == "index.html" or $p->file == "index.php") {
		$file = $p->parentdir;
	} else {
		$file = $p->basefile;
	}

	$cms = new Cms ($file, $p);
?>
