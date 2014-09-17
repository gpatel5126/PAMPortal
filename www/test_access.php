<?php
	require_once("includes/dbconn.php");

	$limited_access = array();
	
	$mysqli->real_query("
			SELECT uid 
			FROM ad
			WHERE UID = 'paulette' OR Manager = 'paulette' OR Manager2 = 'paulette' OR Manager3 = 'paulette' OR Manager4 = 'paulette' OR Manager5 = 'paulette' OR Manager6 = 'paulette' OR Manager7 = 'paulette' OR Manager8 = 'paulette' OR Manager9 = 'paulette' OR Manager10 = 'paulette'
		");
		echo mysqli_error($mysqli);
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			$user_id = $row['uid'];
			array_push($limited_access, $user_id);
		}
		
	print_r($limited_access);
	
	
?>