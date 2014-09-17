<?php
	$today = date("Y-m-d");
	
	require_once("../includes/dbconn.php");
	set_time_limit (0);
	
	$date_of_previous_update = "2014-08-05";
	$date_of_current_update = "2014-08-18";

	$extra = 0;
	// An array for all the people who now have 0 servers
	$more_array = array();
	$mysqli->real_query("
		SELECT t1.uid
		FROM (SELECT * FROM server_user_compliance_history WHERE date = '$date_of_previous_update') t1
		LEFT JOIN (SELECT * FROM server_user_compliance_history WHERE date = '$date_of_current_update') t2 ON (t1.UID = t2.UID)
		WHERE t2.UID IS NULL
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$extra++;
		$uid = $row['uid'];
		$owner_string = "('$uid',0,0,0,0,NOW())";
		
		array_push($more_array, $owner_string);
	}	
	$values = implode(",", $more_array);
	
	echo $values;
	
	if ($extra > 0) {
		$mysqli->real_query("
			INSERT INTO server_user_compliance_history
			(UID, m_compliant, m_uncompliant, o_compliant, o_uncompliant, date)
			VALUES $values;
		");
		echo mysqli_error($mysqli);
	}
	

?>