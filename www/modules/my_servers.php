<?php
	require_once("../includes/dbconn.php");
	session_start();
	
	if ( isset($_POST['limit']) ) {
		$limit = $_POST['limit'];
	}
	else {
		$limit = 25;
	}
	if ( isset($_POST['page']) ) {
		$page = $_POST['page'];
	}
	else {
		$page = 1;
	}
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = mysqli_real_escape_string($mysqli, $_COOKIE['user_id']);
	} else {
		$target = mysqli_real_escape_string($mysqli, $_SESSION['uid']);
	}
	
	// Get user full name
	if ($stmt = $mysqli->prepare("
		SELECT FullName
		FROM ad
		WHERE UID = ?
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$fullName = $row['FullName'];
		}
	}
	
	// Get filter parameters
	if ( isset($_POST['server_query']) ) {
		$search = mysqli_real_escape_string($mysqli, $_POST['server_query']);
		$search = "AND address LIKE '%{$search}%'";
	} else {
		$search = "";
	}
	if ( isset($_POST['prot']) ) {
		$prot = mysqli_real_escape_string($mysqli, $_POST['prot']);
		if ($prot == "all") { $prot = ""; }
		if ($prot == "protected") { $prot = "AND implemented = 'y'"; }
		if ($prot == "unprotected") { $prot = "AND implemented = 'n'"; }
	} else {
		$prot = "";
	}
	if ( isset($_POST['roles']) ) {
		$roles_filt = mysqli_real_escape_string($mysqli, $_POST['roles']);
		if ($roles_filt == "all") { $roles_filt = ""; }
		else {
			$roles_filt = explode(",",$roles_filt);
			$roles_filt = implode("', '", $roles_filt);
			$roles_filt = "AND `System Role` IN ('{$roles_filt}')";
		}
	} else {
		$roles_filt = "";
	}
	
	
	$starting = ($page*$limit) - $limit;
	
	$roles_arr = array();	
	if ($stmt = $mysqli->prepare("
		SELECT `System Role`
		FROM cleansed_cmdb_dump
		WHERE `Owner Name` = ?
		GROUP BY `System Role`
		ORDER BY `System Role` ASC
	")) {
		$stmt->bind_param("s", $fullName);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$roles = array("role_name"=>$row['System Role']);
			array_push($roles_arr,$roles);
		}
	}
	
	if ($stmt = $mysqli->prepare("
		SELECT COUNT(*) as counted
		FROM cleansed_cmdb_dump
		WHERE `Owner Name` = ? $search $prot $roles_filt
	")) {
		$stmt->bind_param("s", $fullName);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$results = $row['counted'];
		}
	}
	
	$cmdb_arr = array();
	if ($stmt = $mysqli->prepare("
		SELECT *
		FROM cleansed_cmdb_dump
		WHERE `Owner Name` = ? $search $prot $roles_filt
		ORDER BY `address` ASC
		LIMIT ?,?
	")) {
		$stmt->bind_param("sii", $fullName, $starting, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$temp_arr = array("address"=>$row['address'],"primary_contact"=>$row['Primary Contact'],"implemented"=>$row['implemented'],"confidentiality"=>$row['Confidentiality'],"owner"=>$row['Owner Name'],"role"=>$row['System Role'],"ci"=>$row['CI Name']);
			array_push($cmdb_arr, $temp_arr);
		}
	}
	
	$arr = array('num_results' => $results, 'results' => $cmdb_arr, 'roles' => $roles_arr);
	
	echo json_encode($arr);
	
?>