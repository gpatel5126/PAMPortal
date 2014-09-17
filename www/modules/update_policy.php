<?php
	require_once("../includes/dbconn.php");
	session_start();
	
	$target = $_SESSION['uid'];
	$counted = 0;
	$message = "";
	
	// Get filter parameters
	if ( isset($_POST['new_policy']) ) {
		$accountStatus = mysqli_real_escape_string($mysqli, $_POST['new_policy']);
		if ($accountStatus == "manual") {
			$accountStatus = "Manual";
		} else {
			$accountStatus = "Automatic";
		}
	} else {
		$accountStatus = "Automatic";
	}
	if ( isset($_POST['relatedAccount']) ) {
		$relatedAccount = mysqli_real_escape_string($mysqli, $_POST['relatedAccount']);
	} else {
		$relatedAccount = "PAMlook";
	}
	
	if ($stmt = $mysqli->prepare("
		SELECT COUNT(*) AS counted FROM accounts_dump WHERE Account = ? AND Owner = ?
	")) {
		$stmt->bind_param("ss", $relatedAccount, $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$count = $row['counted'];
		}
		
		// This user is the owner
		if ($count > 0) {
			try {
				$mysqli->autocommit(FALSE);
				// Delete the old records
				if ($stmt = $mysqli->prepare("
					DELETE FROM account_policy_updates WHERE account_name = ?
				")) {
					$stmt->bind_param("s", $relatedAccount);
					$stmt->execute();
					
					echo mysqli_error($mysqli);
					
					// Insert the new records			
					if ($stmt = $mysqli->prepare("
						INSERT INTO account_policy_updates (account_name, status, last_changed, requester_id) 
						VALUES (?, ?, NOW(), ?)
					")) {						
						$stmt->bind_param("sss", $relatedAccount, $accountStatus, $target);
						$stmt->execute();
						
						echo mysqli_error($mysqli);
						
						$status = "Success";
						$mysqli->commit();
						}
					}
					else {
						$status = "Failure";
					}
				}
			catch (Exception $e) {
				$mysqli->rollback();
				$status = "Failure";
				$message = "Connection error";
			}
		}
		else {
			$status = "Failure";
			$message = "You are not the owner.";
		}
	}
	else {
		$status = "Failure";
		$message = "Connection error";
		exit;
	}
	
	$arr = array('status' => $status,"message"=>$message);
	
	echo json_encode($arr);
	
?>