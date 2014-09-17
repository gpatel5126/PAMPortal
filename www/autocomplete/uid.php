<?php
	require_once("../includes/dbconn.php");
	
	if (isset($_GET['query'])) {
		$target = mysqli_real_escape_string($mysqli, $_GET['query']);
		$target = "%{$target}%";
	
	
		if ($stmt = $mysqli->prepare("
			SELECT FullName as value, UID as data
			FROM ad
			WHERE FullName LIKE ?
			LIMIT 10
		")) {
		
			$info = array();
			$stmt->bind_param("s", $target);
			$stmt->execute();
			$res = $stmt->get_result();
		
			while ($row = $res->fetch_assoc()) {
				array_push($info,$row);
			}
		
		}
		
		$setup = array('query'=>$target,'suggestions'=>$info);
		
		echo json_encode($setup);
	
	}
?>