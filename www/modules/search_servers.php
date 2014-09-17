<?php
	require_once("../includes/dbconn.php");
	session_start();
	
	if ( isset($_GET['limit']) ) {
		$limit = $_GET['limit'];
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
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	if ( isset($_POST['server_query']) ) {
		$query = mysqli_real_escape_string($mysqli, $_POST['server_query']);
		$query = "%{$query}%";
	} else {
		$query = "None";
	}
	
	$starting = ($page*$limit) - $limit;
	
	if ($stmt = $mysqli->prepare("
		SELECT COUNT(*) AS count_results
		FROM cmdb_dump
		WHERE `address` LIKE ?
	")) {
		$stmt->bind_param("s", $query);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$results = $row['count_results'];
		}
	}
	$cmdb_arr = array();
	
	if ($stmt = $mysqli->prepare("
		SELECT *
		FROM cmdb_dump
		WHERE `address` LIKE ?
		ORDER BY `address` ASC
		LIMIT ?,?;
	")) {
		$stmt->bind_param("sii", $query, $starting, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$temp_arr = array("address"=>$row['address'],"implemented"=>$row['implemented'],"confidentiality"=>$row['Confidentiality'],"owner"=>$row['Owner Name'],"primary_contact"=>$row['Primary Contact'],"role"=>$row['System Role']);
			array_push($cmdb_arr, $temp_arr);
		}
	}
	
	$arr = array('num_results' => $results, 'results' => $cmdb_arr);
	
	echo json_encode($arr);
	
?>