<?php
	require_once("../includes/dbconn.php");
	session_start();
	
	if ( isset($_GET['safe']) ) {
		$safe = mysqli_real_escape_string($mysqli, $_GET['safe']);
	}
	else {
		$safe = "xxx";
	}
	
	$owner_count = 0;
	if ($stmt = $mysqli->prepare("
		SELECT COUNT(*) AS safeCount
		FROM safe_inventory
		WHERE SafeName = ?
	")) {
		$stmt->bind_param("s", $safe);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$safeCount = $row['safeCount'];
		}
	}
	
	if ($safeCount == 0) {
		$arr = array("greenLight" => 'true');
	}
	else {
		$arr = array("greenLight" => 'false');
	}
	
	echo json_encode($arr);
	
?>