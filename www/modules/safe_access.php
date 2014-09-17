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
	
	$member_count = 0;
	if ($stmt = $mysqli->prepare("		
		SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2
		FROM safe_users
		RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
		RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.NoConfirmRequired = 'NO'
		LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID
		WHERE UserName = ?
		ORDER BY SafeCreationDate DESC
		LIMIT ?,?
	")) {
		$stmt->bind_param("sii", $target, $start, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			echo "<tr>";
			echo "<td>{$row['SafeName']}</td>";
			echo "<td>{$row['SafeCreationDate2']}</td>";
			echo "<td><a href='create_account.php#{$row['SafeName']}'><i class='fa fa-chevron-circle-right'></i>View objects</a></td>";
			echo "</tr>";
			$member_count++;
		}
	}
	if ($member_count == 0) {
		echo "<tr><td colspan='3'>You don't have access to any safes.</td></tr>";
	}
?>