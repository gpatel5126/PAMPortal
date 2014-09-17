<?php
	require_once("includes/dbconn.php");
	
	session_start();
	header('Content-Type: application/json');
	
	if (isset($_SESSION['uid'])) {
		$user_id = mysqli_real_escape_string($mysqli, $_SESSION['uid']);
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
	
	if ($stmt = $mysqli->prepare("
		SELECT EmployeeType, FullName
		FROM ad
		WHERE UID = ?
		")) {
		$stmt->bind_param("s", $user_id);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$employeeType = $row['EmployeeType'];
			$fullName = $row['FullName'];
		}
	}
	
	if ($employeeType != 1) { 
		$status = "Failure"; 
		$message = "You must be employee type 1 to claim this server."; 
	}
	else {
		$mysqli->real_query("
			INSERT INTO server_actions
			(server_name, user_id, action_type, date) VALUES ('$server_name', '$user_id', 'claim', NOW()) 
		");
		$mysqli->real_query("
			UPDATE cmdb_dump
			SET `Owner Name` = '$fullName' 
			WHERE `CI Name` = '$server_name'
		");
	}
	
	$data = array('status'=>$status,'message'=>$message,'owner_name'=>$fullName);
	
	echo json_encode($data);

?>