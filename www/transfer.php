<?php
	require_once("includes/dbconn.php");
	
	session_start();
	header('Content-Type: application/json');
	
	if (isset($_SESSION['uid'])) {
		$user_id = mysqli_real_escape_string($mysqli, $_SESSION['uid']);
		
		$mysqli->real_query("
			SELECT FullName
			FROM ad
			WHERE UID = '$user_id'
		");
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			$myfullName = $row['FullName'];
		}
	}
	else {
		die('no uid');
	}
	
	if (isset($_POST['server_name'])) {
		$server_name = mysqli_real_escape_string($mysqli, $_POST['server_name']);
	}
	else {
		die("No server name");
	}
	if (isset($_POST['transfer_to'])) {
		$transfer_to = mysqli_real_escape_string($mysqli, $_POST['transfer_to']);
	}
	else {
		
	}
	
	$matches = 0;
	$mysqli->real_query("
		SELECT EmployeeType, FullName, UID
		FROM ad
		WHERE UID = '$transfer_to'
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$employeeType = $row['EmployeeType'];
		$fullName = $row['FullName'];
		$matches++;
	}
	
	$server_matches = 0;
	$mysqli->real_query("
		SELECT `CI Name`
		FROM cmdb_dump
		WHERE `Owner Name` = '$myfullName' AND `CI Name` = '$server_name'
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$server_matches++;
	}
	
	if ($server_matches == 0) {
		$status = "Failure"; 
		$message = "You don't own this server."; 
	}
	else if ($matches == 0) {
		$status = "Failure"; 
		$message = "The employee are transferring to doesn't exist in ActiveDirectory."; 
	}
	else if ($employeeType != 1) { 
		$status = "Failure"; 
		$message = "The employee you are transferring to must be of employee type 1"; 
	}
	else {
		$mysqli->real_query("
			INSERT INTO server_actions
			(server_name, user_id, action_type, transfer_to, transfer_to_full_name, date) VALUES ('$server_name', '$user_id', 'transfer_ownership', '$transfer_to', '$fullName', NOW()) 
		");
		$mysqli->real_query("
			UPDATE cmdb_dump
			SET `Owner Name` = '$fullName' 
			WHERE `CI Name` = '$server_name'
		");
		
		$status = "Success";
		$message = "Server successfully transferred!"; 
	}
	
	$data = array('status'=>$status,'message'=>$message,'server_name'=>$server_name);
	
	echo json_encode($data);

?>