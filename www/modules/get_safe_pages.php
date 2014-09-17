<?php
	require_once("../includes/dbconn.php");
	session_start();
	
	if ( isset($_GET['limit']) ) {
		$limit = $_GET['limit'];
	}
	else {
		$limit = 5;
	}
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	$owner_count = 0;
	if ($stmt = $mysqli->prepare("
		SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Owner' AS role
		FROM safe_users
		RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
		RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'YES'
		LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID
		WHERE UserName = ?
		ORDER BY SafeCreationDate DESC
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$owner_count++;
		}	
	}
	
	$owner_pages = ceil($owner_count/$limit);
	
	$access_count = 0;
	if ($stmt = $mysqli->prepare("
		SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Controller' AS role
		FROM safe_users
		RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
		RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'NO'
		LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID
		WHERE UserName = ?	
		
		UNION
		
		SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Member' AS role
		FROM safe_users
		RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
		RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'NO' AND safe_group_access.NoConfirmRequired = 'NO'
		LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID
		WHERE UserName = ?			
	")) {
		$stmt->bind_param("ss", $target, $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$access_count++;
		}	
	}
	
	$access_pages = ceil($access_count/$limit);
	
	$all_count = 0;
	if ($stmt = $mysqli->prepare("
		SELECT DISTINCT SafeName, SafeID, SafeCreationDate2, role 
		FROM (
		
			SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Owner' AS role
			FROM safe_users
			RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
			RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'YES'
			LEFT JOIN safe_inventory ON safe_group_access.SafeID = safe_inventory.SafeID
			WHERE UserName = ?
			
			UNION
			
			SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Controller' AS role
			FROM safe_users
			RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
			RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'NO'
			LEFT JOIN safe_inventory ON safe_group_access.SafeID = safe_inventory.SafeID
			WHERE UserName = ?
			
			UNION
			
			SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Member' AS role
			FROM safe_users
			RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
			RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'NO' AND safe_group_access.NoConfirmRequired = 'NO'
			LEFT JOIN safe_inventory ON safe_group_access.SafeID = safe_inventory.SafeID
			WHERE UserName = ?
			
		) t1
		
		GROUP BY SafeID
	")) {
		$stmt->bind_param("sss", $target, $target, $target);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$all_count++;
		}
	}
	
	$all_pages = ceil($all_count/$limit);
	
	$arr = array('owner_pages' => $owner_pages, 'access_pages' => $access_pages, 'all_pages' => $all_pages);
	
	echo json_encode($arr);
	
?>