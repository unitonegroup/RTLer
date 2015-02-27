<?php
if(isset($_POST["submit"])) {
	$target_dir = "uploads/";
	$target_file = $target_dir . basename($_FILES["file"]["name"]);
	$uploadOk = 1;
	$FileType = pathinfo($target_file,PATHINFO_EXTENSION);
	if($FileType != "css" ) {
		echo "Sorry, only CSS files are allowed.";
		$uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		echo "Sorry, your file was not uploaded.";
	// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
			include('rtler.php');
			$rtler = new RTLer($target_file);
			$rtler->rtl();
			header('Content-type: text/plain');
			header('Content-Disposition: attachment; filename="rtl.css"');
			echo $rtler->render();
		}
	}
}else{
?>
<head>
	<title>RTLer</title>
	<style>
		*{
			margin: 0px;
			padding: 0px;
			text-align: center;
		}
		h1{
			margin-top: 50px;
		}
		h2{
			margin-bottom: 50px;
		}
	</style>
</head>
<body>
	<h1>RTLer (CSS3 support)</h1>
	<h2>fork at <a href="https://github.com/unitoneict/RTLer">github</a></h2>
	<div>
	<form method="post" enctype="multipart/form-data">
		<label for="file">Upload file</label>
		<input type="file" id="file" name="file" />
		<input type="submit" value="Upload file" name="submit">
	</form>
	</div>
</body>
<?php
}