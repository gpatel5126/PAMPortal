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
	
	// Get filter parameters
	if ( isset($_POST['query']) ) {
		$search = mysqli_real_escape_string($mysqli, $_POST['query']);
		$search = "AND account LIKE '%{$search}%'";
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
	
	
	$starting = ($page*$limit) - $limit;
	
	if ($stmt = $mysqli->prepare("
		SELECT COUNT(*) as counted
		FROM accounts_dump
		WHERE `Owner` = ? $search $prot
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		
		
		while ($row = $res->fetch_assoc()) {
			$results = $row['counted'];
		}
	}
	
	$accounts_arr = array();
	if ($stmt = $mysqli->prepare("
		SELECT accounts_dump.*, account_policy_updates.status
		FROM accounts_dump
		LEFT JOIN account_policy_updates ON accounts_dump.Account = account_policy_updates.account_name
		WHERE `Owner` = ? $search $prot
		ORDER BY `Account` ASC
		LIMIT ?,?
	")) {
		$stmt->bind_param("sii", $target, $starting, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			if ($row['status'] == "") {
				$status = "Automatic";
			}
			else {
				$status = $row['status'];
			}
		
			$temp_arr = array("account"=>$row['Account'],"displayName"=>$row['Display Name'],"implemented"=>$row['implemented'],"policy_status"=>$status);
			array_push($accounts_arr, $temp_arr);
		}
	}
	
	$arr = array('num_results' => $results, 'results' => $accounts_arr);
	
	echo json_encode($arr);
	
?>