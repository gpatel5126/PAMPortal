<?php
 
	$mysqli = mysqli_connect('sj1glm736','PAMSTGUSER','P4mUser3','PAMSTG', 3313); //added 5th argument to get right port
	if (!$mysqli) { 
		die('Could not connect to MySQL: ' . mysql_error()); 
	} 

?> 