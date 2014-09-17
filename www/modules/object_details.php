<?php
	require_once("../includes/dbconn.php");
	session_start();
	
	if ( isset($_POST['object_name']) ) {
		$name = mysqli_real_escape_string($mysqli, $_POST['object_name']);
	}
	else {
		$name = "xxx";
	}
	
	$count = 0;
	if ($stmt = $mysqli->prepare("
		SELECT *
		FROM ca_dump
		WHERE Name = ?
	")) {
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$arr = array('object_name'=>$row['Name'],'policyId'=>$row['Policy ID'],'deviceType'=>$row['Device type'],'hostName'=>$row['Target system address'],'safeName'=>$row['Safe'],'accountName'=>$row['Target system user name']);
			$count++;
		}
	}
	
	if ($count == 0) {
		if ($stmt = $mysqli->prepare("
			SELECT *
			FROM safe_object_inventory
			WHERE ObjectName = ?
		")) {
			$stmt->bind_param("s", $name);
			$stmt->execute();
			$res = $stmt->get_result();
		
			while ($row = $res->fetch_assoc()) {
				$arr = array('object_name'=>$row['ObjectName'],'policyId'=>$row['PolicyID'],'deviceType'=>$row['DeviceType'],'hostName'=>$row['HostName'],'safeName'=>$row['SafeName'],'accountName'=>$row['AccountName']);
			}
		}
	}
	
	
	
	echo json_encode($arr);
	
?>