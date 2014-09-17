<?php
$mysqli = new mysqli("localhost", "root", "", "dash");

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

$city = "pambind";
$currentSafe = "DU_controller_safe_GEN";
$target = "pri76183";

/* create a prepared statement */
if ($stmt = $mysqli->prepare("SELECT COUNT(*) AS counted
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
					WHERE UserName = ?
					
					UNION
					
					SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Controller' AS role
					FROM safe_users
					RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
					RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'YES' AND safe_group_access.NoConfirmRequired = 'NO'
					LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
					WHERE UserName = ?
					
					UNION
					
					SELECT DISTINCT safe_inventory.SafeName, safe_inventory.SafeID, safe_inventory.SafeCreationDate, Date_format(SafeCreationDate, '%m/%d/%Y') AS SafeCreationDate2, 'Member' AS role
					FROM safe_users
					RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
					RIGHT JOIN safe_group_access ON safe_group_members.GroupID = safe_group_access.GroupID AND safe_group_access.CreateObject = 'NO' AND safe_group_access.NoConfirmRequired = 'NO'
					LEFT JOIN safe_inventory ON safe_group_access.SafeID =  safe_inventory.SafeID AND safe_inventory.SafeName != ?
					WHERE UserName = ?
				) t1
			GROUP BY SafeID
			) t2
		) t3")) {

    /* bind parameters for markers */
    $stmt->bind_param("ssssss", $currentSafe, $target, $currentSafe, $target, $currentSafe, $target);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
		echo $row['counted'];
    }
    $stmt->close();
}
?>