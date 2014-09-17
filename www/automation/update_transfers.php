<?php
	require_once("../includes/dbconn.php");
	
	$change_array = array();
	$cis = array();
	
	$mysqli->real_query("
		SELECT server_actions.*, ad.FullName
		FROM server_actions
		LEFT JOIN ad ON server_actions.user_id = ad.UID
		WHERE action_id > 45
		ORDER BY action_id DESC
		
	");	
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$ci = $row['server_name'];
		$user_id = $row['user_id'];
		$fullName = $row['FullName'];
		$transfer_to = $row['transfer_to'];
		$transfer_to_full_name = $row['transfer_to_full_name'];
		
		echo $ci;
		echo "<br />";
		
		
		
		if ($row['action_type'] == "unclaim") {
			/*
			$mysqli->real_query("
				UPDATE cmdb_dump SET `Owner Name` = 'Not Available', `Owner Contact` = 'Not Available' WHERE `CI Name` = '$ci' 
			");
			echo mysqli_error($mysqli);
			*/
			$new_owner_email = "Not Available";
			$new_owner_fullname = "Not Available";
		}
		else if ($row['action_type'] == "claim") {
			/*
			$mysqli->real_query("
				UPDATE cmdb_dump SET `Owner Name` = '$fullName', `Owner Contact` = '$user_id' WHERE `CI Name` = '$ci' 
			");
			echo mysqli_error($mysqli);
			*/
			$new_owner_email = "{$user_id}@adobe.com";
			$new_owner_fullname = $fullName;
		}
		else if ($row['action_type'] == "transfer_ownership") {
			/*
			$mysqli->real_query("
				UPDATE cmdb_dump SET `Owner Name` = '$transfer_to_full_name', `Owner Contact` = '$transfer_to' WHERE `CI Name` = '$ci' 
			");
			echo mysqli_error($mysqli);
			*/
			$new_owner_email = "{$transfer_to}@adobe.com";
			$new_owner_fullname = $transfer_to_full_name;
		}		
		
		if ( !in_array($ci, $cis) ) {
			array_push($cis, $ci);
			$temp_array = array('ci'=>$ci,'new_owner_email'=>$new_owner_email,'new_owner_fullname'=>$new_owner_fullname);
			array_push($change_array, $temp_array);
		}
	}		
	
print_r($change_array);

	foreach($change_array as $server) {
		$ci = $server['ci'];
		$new_owner_email = $server['new_owner_email'];
		$new_owner_fullname = $server['new_owner_fullname'];
		
		$mysqli->real_query("
			UPDATE cmdb_dump SET `Owner Name` = '$new_owner_fullname', `Owner Contact` = '$new_owner_email' WHERE `CI Name` = '$ci' 
		");
		echo mysqli_error($mysqli);
		
		echo " - done.<br />";
	}
	
	
	
?>