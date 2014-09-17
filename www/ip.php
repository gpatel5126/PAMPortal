<?php
	set_time_limit (0);
	
	require_once("includes/dbconn.php");
	
	$ip = gethostbyname('pam.corp.adobe.com');
	echo $ip;

	if ($stmt = $mysqli->prepare("
		SELECT `CI Name`, address
		FROM cleansed_cmdb_dump
	")) {
		//$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			echo $row['CI Name'];
			echo " - ";
			$address = $row['address'];
			
			$ip = gethostbyname($row['CI Name']);
			echo $ip;
			
			echo "<br />";
			
			$mysqli->real_query("
				UPDATE ad_servers
				SET address='$address'
				WHERE `Source IP` = '$ip'
			");
			
			ob_flush();
			flush();
		}
	}
	
?>

