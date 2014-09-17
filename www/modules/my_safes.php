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
	
	if ( isset($_POST['access']) ) {
		$access = $_POST['access'];
	}
	else {
		$access = "all";
	}
	
	$start = ($page * $limit) - $limit;
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	// Get filter parameters
	if ( isset($_POST['safe_query']) ) {
		$search = mysqli_real_escape_string($mysqli, $_POST['safe_query']);
		$search = "AND safe_inventory.SafeName LIKE '%{$search}%'";
	} else {
		$search = "";
	}
	
	if ( isset($_POST['currentSafe']) ) {
		$currentSafe = $_POST['currentSafe'];
	} else {
		$currentSafe = "";
	}
	
	
	$num_results = 0;
	if ($access == "all") {
		$stmt = $mysqli->prepare("
			SELECT COUNT(*) AS counted
			FROM (
				SELECT SafeID 
				FROM (
					SELECT SafeID
					FROM (
					
						SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Owner' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'YES'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search	
						
						UNION
						
						SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Controller' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'NO'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search	
						
						UNION
						
						SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Member' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'NO' AND safe_group_access.NoConfirmRequired = 'NO'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search
					) t1
				GROUP BY SafeID
				) t2
			) t3
		");
		$stmt->bind_param("ssssss", $currentSafe, $target, $currentSafe, $target, $currentSafe, $target);
		$stmt->execute();
		$res = $stmt->get_result();
				
		while ($row = $res->fetch_assoc()) {
			$num_results = $row['counted'];
		}
		
	}
	else {
		$stmt = $mysqli->prepare("
			SELECT COUNT(*) AS counted
			FROM (
				SELECT SafeID 
				FROM (
					SELECT SafeID
					FROM (
					
						SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Owner' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'YES'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search	
						
						UNION
						
						SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Controller' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'NO'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search
					) t1
				GROUP BY SafeID
				) t2
			) t3
		");
		$stmt->bind_param("ssss", $currentSafe, $target, $currentSafe, $target);
		$stmt->execute();
		$res = $stmt->get_result();
				
		while ($row = $res->fetch_assoc()) {
			$num_results = $row['counted'];
		}
	}	
	
	
	$safes_array = array();
	if ($access == "all") {
		$stmt = $mysqli->prepare("
			SELECT DISTINCT SafeName, SafeID, SafeCreationDate2, role 
			FROM (
			
				SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Owner' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'YES'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search	
						
						UNION
						
						SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Controller' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'NO'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search	
						
						UNION
						
						SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Member' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'NO' AND safe_group_access.NoConfirmRequired = 'NO'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search
				
			) t1
			
			GROUP BY SafeID
			ORDER BY SafeCreationDate DESC
			LIMIT ?,?
		");
		$stmt->bind_param("ssssssii", $currentSafe, $target, $currentSafe, $target, $currentSafe, $target, $start, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$temp_array = array("name"=>$row['SafeName'],"created"=>$row['SafeCreationDate2'],"role"=>$row['role']);
			array_push($safes_array, $temp_array);
		}
	}
	else {
		$stmt = $mysqli->prepare("
			SELECT DISTINCT SafeName, SafeID, SafeCreationDate2, role 
			FROM (
			
				SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Owner' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'YES'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search	
						
						UNION
						
						SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Controller' AS role
						FROM safe_users
						RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
						RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'NO'
						LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
						WHERE UserName = ? $search
				
			) t1
			
			GROUP BY SafeID
			ORDER BY SafeCreationDate DESC
			LIMIT ?,?
		");
		$stmt->bind_param("ssssii", $currentSafe, $target, $currentSafe, $target, $start, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$temp_array = array("name"=>$row['SafeName'],"created"=>$row['SafeCreationDate2'],"role"=>$row['role']);
			array_push($safes_array, $temp_array);
		}
	}	
	
	$arr = array("num_results"=>$num_results,"results"=>$safes_array);
	
	echo json_encode($arr);
?>