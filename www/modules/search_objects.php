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
	if ( isset($_POST['query']) ) {
		$query = mysqli_real_escape_string($mysqli, $_POST['query']);
		$query = "%{$query}%";
		
	} else {
		$query = "%%";
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
			SELECT ObjectName
			FROM safe_object_inventory
			WHERE SafeName IN (
				SELECT DISTINCT safe_inventory.SafeName
				FROM safe_users
				RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
				RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID
				LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID
				WHERE UserName = ?	
			) AND ObjectName LIKE ?
		) t1
	")) {
		$stmt->bind_param("ss", $target, $query);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$num_results = $row['counted'];
		}
	}
	
	
	
	$obj_array = array();
	if ($stmt = $mysqli->prepare("
		SELECT ObjectName, SafeName
		FROM safe_object_inventory
		WHERE SafeName IN (
			SELECT DISTINCT safe_inventory.SafeName
			FROM safe_users
			RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
			RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID
			LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID
			WHERE UserName = ?	
		) AND ObjectName LIKE ?
		
		LIMIT ?,?
	")) {
		$stmt->bind_param("ssii", $target, $query, $start, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			
			$temp_array = array('objectName'=>$row['ObjectName'], 'safeName' => $row['SafeName']);
			array_push($obj_array, $temp_array);
		}
	}
	
	$arr = array("num_results"=>$num_results,"results"=>$obj_array,"can_create"=>"NO");
	
	echo json_encode($arr);
?>