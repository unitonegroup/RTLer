<?php
	include('rtler.php');
	$target_file = "styles.css";
	$rtler = new RTLer($target_file);
	$rtler->rtl();
	echo "<pre>";
	echo $rtler->render();
	echo "</pre>";
?>