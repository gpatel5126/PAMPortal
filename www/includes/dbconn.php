<?php
 
	$mysqli = mysqli_connect('localhost','root','','dash'); 
	if (!$mysqli) { 
		die('Could not connect to MySQL: ' . mysql_error()); 
	} 

?> 