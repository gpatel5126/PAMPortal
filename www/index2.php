<?php
session_start();

if (isset($_SESSION['logged']) && isset($_SESSION['uid'])) {

	header("Location: dashboard.php");
	exit;
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8"/>
	<!--[if IE]><script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7/html5shiv.min.js"></script><![endif]-->
	<title>Adobe Privileged Account Portal</title>
	<meta name="viewport" content="width=device-width"/>
	<link rel="stylesheet" href="style.css"/>
	<script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="js/sparkline.js"></script>
	<script src="js/jquery.easypiechart.min.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
	<script>
		$( "form#lgg" ).submit(function( ) {
			$("#log_load").show();
		});
		$(function() {
			$('#login').css({
				'position' : 'absolute',
				'left' : '50%',
				'top' : '50%',
				'margin-left' : -$("#login").width()/2,
				'margin-top' : -$("#login").height()/2
			});
			
			//alert( $("#login").width() );
		});
	</script>
</head>
<body class="login-page">
<div id="login">
	<header>
		<img src="images/logo.png" alt="" /><span>Adobe Privileged Account Portal</span>
	</header>
	<?php
		if ( isset($_GET['i']) ) {
			echo "<div class='notice'>Invalid username or password</div>";
		}
	?>
	<p>
		Welcome to the Adobe Privileged Account Portal!  Login using your Adobe username and password to get started.
	</p>
<form method="post" action="ldap2.php" id="lgg">
	<fieldset>
		<label>Username:</label>
		<input type="text" name="username" autocomplete='off' />

		<label>Password:</label>
		<input type="password" name="password" />
		
		<input type="submit" value="Login to Portal" />
	</fieldset>
</form>
<div id="log_load">
	<img src="images/loader.gif" alt="" />
	<span>Logging you in!</span>
</div>
</div>
</body>
</html>