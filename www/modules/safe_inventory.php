<?php
	require_once("../includes/dbconn.php");
	
	session_start();
	$start = 0;
	if ( isset($_POST['limit']) ) {
		$limit = $_POST['limit'];
	}
	else {
		$limit = 5;
	}
	if ( isset($_POST['page']) ) {
		$page = $_POST['page'];
	}
	else {
		$page = 1;
	}
	
	if ( isset($_POST['safe_name']) ) {
		$safe = mysqli_real_escape_string($mysqli, $_POST['safe_name']);
	}
	else {
		$safe = "UnixCompute";
		$canCreate = "NO";
	}
	
	$start = ($page * $limit) - $limit;
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	// Get filter parameters
	if ( isset($_POST['inventory_query']) ) {
		$search = mysqli_real_escape_string($mysqli, $_POST['inventory_query']);
		$search2 = "AND ObjectName LIKE '%{$search}%'";
		$search = "AND name LIKE '%{$search}%'";
		
	} else {
		$search = "";
		$search2 = "";
	}
	
	// Find out what permissions the user has to this safe
	if ($stmt = $mysqli->prepare("
		SELECT CreateObject, NoConfirmRequired
		FROM safe_users
		RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
		RIGHT JOIN safe_group_access ON safe_group_access.GroupID = safe_group_members.GroupID AND safe_group_access.SafeName = ?
		WHERE safe_users.UserName = ?
		ORDER BY createObject DESC
		LIMIT 1
	")) {
		$stmt->bind_param("ss", $safe, $target);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$createObject = $row['CreateObject'];
			$noConfirmRequired = $row['NoConfirmRequired'];
			$canCreate = $createObject;
		}
	}
	
	
	$num_results = 0;
	if ($stmt = $mysqli->prepare("
		SELECT COUNT(*) AS counted
		
		FROM (
			SELECT Name as ObjectName
			FROM ca_dump
			WHERE Safe = ? $search
			
			UNION
			
			SELECT ObjectName
			FROM safe_object_inventory
			WHERE SafeName = ? $search2
		) t1
	")) {
		$stmt->bind_param("ss", $safe, $safe);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$num_results = $row['counted'];
		}
	}
	
	
	
	$inv_array = array();
	if ($stmt = $mysqli->prepare("
		SELECT * 
		
		FROM (
			SELECT Name as ObjectName, `Target system user name` AS AccountName, `Policy ID` AS PolicyID, `Target System Address` AS HostName
			FROM ca_dump
			WHERE Safe = ? $search
			
			UNION
			
			SELECT ObjectName, AccountName, PolicyID, HostName
			FROM safe_object_inventory
			WHERE SafeName = ? $search2
		) t1
		
		LIMIT ?,?
	")) {
		$stmt->bind_param("ssii", $safe, $safe, $start, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			
			$temp_array = array('name'=>$row['ObjectName'], 'username' => $row['AccountName'], 'policy' => $row['PolicyID'], 'hostname' => $row['HostName']);
			array_push($inv_array, $temp_array);
		}
	}
	
	$arr = array("num_results"=>$num_results,"results"=>$inv_array,"can_create"=>$canCreate);
	
	echo json_encode($arr);
?>