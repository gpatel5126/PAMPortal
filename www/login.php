<?php

	$username = 'adobe';
	$password = 'nd@477U49!epo$kf';
	
	if ( isset ($_POST['username']) && isset($_POST['password'] ) ) {
		
		if ($_POST['username'] == $username && $_POST['password'] == $password) {
			header("Location: dashboard.php");
			setcookie("logged_in2", "uewfyxl865", time()+3600*36);  /* expire in 36 hours */
			exit;
		}
		else {
			header("Location:index.php?i=incorrect");
			exit;
		}
		
	}
	else {
		header("Location:index.php?i=incorrect");
		exit;
	}
	
	
?>