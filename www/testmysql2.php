<?php 
$mysqli = mysqli_connect('localhost','root','','dash'); 
if (!$mysqli) { 
	die('Could not connect to MySQL: ' . mysql_error()); 
} 

?> 

<?php

	$target = 'sgary';

	$query = "
		SELECT * 
		FROM server_user_compliance_history
		WHERE UID = '$target'
		ORDER BY date DESC

	
	";
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);

	if($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$owned_compliant = $row['o_compliant'];
		$owned_uncompliant = $row['o_uncompliant'];
		$managed_compliant = $row['m_compliant'];
		$managed_uncompliant = $row['m_uncompliant'];
echo $managed_compliant;
echo "<br />";	
		}
	}
	else {
		echo 'NO RESULTS';	
	}

	// How many servers?
	$query = "SELECT COUNT(address) AS server_count FROM cmdb_inventory WHERE (owner_id = '$target' OR Manager = '$target' OR Manager2 = '$target' OR Manager3 = '$target' OR Manager4 = '$target' OR Manager5 = '$target' OR Manager6 = '$target' OR Manager7 = '$target' OR Manager8 = '$target' OR Manager9 = '$target' OR Manager10 = '$target')
	AND implemented = 'y'";
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);

	if($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			echo stripslashes($row['server_count']);
echo "<br />";	
		}
	}
	else {
		echo 'NO RESULTS';	
	}

echo 'Connection OK'; mysqli_close($mysqli); 


?>

