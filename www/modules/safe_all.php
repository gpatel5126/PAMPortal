<?php
	require_once("../includes/dbconn.php");
	
	session_start();
	
	$start = 0;
	if ( isset($_GET['limit']) ) {
		$limit = $_GET['limit'];
	}
	else {
		$limit = 5;
	}
	
	if ( isset($_GET['page']) ) {
		$page = $_GET['page'];
	}
	else {
		$page = 1;
	}
	
	$start = ($page * $limit) - $limit;
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	$owner_count = 0;
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
		ORDER BY SafeCreationDate DESC
		LIMIT $start,$limit
	")) {
		$stmt->bind_param("sssii", $target, $target, $target, $start, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			echo "<tr>";
			echo "<td>{$row['SafeName']}</td>";
			echo "<td>{$row['SafeCreationDate2']}</td>";
			echo "<td>{$row['role']}</td>";
			echo "</tr>";
			$owner_count++;
		}
	}
	if ($owner_count == 0) {
		echo "<tr><td colspan='3'>You don't have access to any safes.</td></tr>";
	}
?>