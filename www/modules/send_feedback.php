<?php
	require_once("../includes/dbconn.php");
	session_start();
	

	$target = $_SESSION['uid'];
	
	if ( isset($_POST['feedback']) ) {
		$feedback = $_POST['feedback'];
	} else {
		$feedback = "Blank";
	}
	
	if ($stmt = $mysqli->prepare("
		INSERT INTO feedback (username, feedback, date) 
		VALUES (?, ?, NOW())
	")) {
		$stmt->bind_param("ss", $target, $feedback);
		$stmt->execute();
	}
	
	echo mysqli_error($mysqli);
	
	$arr = array('success' => "yes");
	
	echo json_encode($arr);
	
?>