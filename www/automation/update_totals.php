<?php
	require_once("../includes/dbconn.php");
	require_once("../includes/access.php");
	
	set_time_limit (0);
	
	// Total
	echo "<strong>Total</strong><br />";
	$mysqli->real_query("
		SELECT implemented, COUNT(*) AS counted
		FROM
			(
				SELECT address, implemented
				FROM qualifying_servers
				UNION ALL
				SELECT address, implemented
				FROM cmdb_dump
				WHERE `Owner Name` = 'Not Available'
			) t1
		GROUP BY implemented
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		echo "{$row['implemented']} - {$row['counted']}<br />";
		
		if ($row['implemented'] == 'y') {
			$total_servers_y = $row['counted'];
		}
		if ($row['implemented'] == 'n') {
			$total_servers_n = $row['counted'];
		}
	}
	
	
	// IT Managed
	echo "<br /><strong>IT Managed</strong><br />";
	$mysqli->real_query("
		SELECT implemented, COUNT(*) AS counted
		FROM cleansed_cmdb_dump
		WHERE cleansed_cmdb_dump.`System Role` IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server')
		GROUP BY implemented
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		echo "{$row['implemented']} - {$row['counted']}<br />";
		
		if ($row['implemented'] == 'y') {
			$it_managed_y = $row['counted'];
		}
		if ($row['implemented'] == 'n') {
			$it_managed_n = $row['counted'];
		}
	}
	
	
	
	// IT Self Managed
	echo "<strong>IT Self Managed</strong><br />";
	$mysqli->real_query("
		SELECT implemented, COUNT(*) AS counted
		FROM cmdb_inventory
		WHERE cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id = 'gerri' OR cmdb_inventory.Manager = 'gerri' OR cmdb_inventory.Manager2 = 'gerri' OR cmdb_inventory.Manager3 = 'gerri' OR cmdb_inventory.Manager4 = 'gerri' OR cmdb_inventory.Manager5 = 'gerri' OR cmdb_inventory.Manager6 = 'gerri' OR cmdb_inventory.Manager7 = 'gerri' OR cmdb_inventory.Manager8 = 'gerri' OR cmdb_inventory.Manager9 = 'gerri' OR cmdb_inventory.Manager10 = 'gerri') AND (cmdb_inventory.owner_id != 'tkhuu' AND cmdb_inventory.Manager != 'tkhuu' AND cmdb_inventory.Manager2 != 'tkhuu' AND cmdb_inventory.Manager3 != 'tkhuu' AND cmdb_inventory.Manager4 != 'tkhuu' AND cmdb_inventory.Manager5 != 'tkhuu' AND cmdb_inventory.Manager6 != 'tkhuu' AND cmdb_inventory.Manager7 != 'tkhuu' AND cmdb_inventory.Manager8 != 'tkhuu' AND cmdb_inventory.Manager9 != 'tkhuu' AND cmdb_inventory.Manager10 != 'tkhuu'))
		GROUP BY implemented
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		echo "{$row['implemented']} - {$row['counted']}<br />";
		
		if ($row['implemented'] == 'y') {
			$it_owned_y = $row['counted'];
		}
		if ($row['implemented'] == 'n') {
			$it_owned_n = $row['counted'];
		}
	}
	
	
	// Self Managed
	echo "<strong>Self Managed</strong><br />";
	$mysqli->real_query("
		SELECT implemented, COUNT(*) AS counted
		FROM cmdb_inventory
		WHERE cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id != 'gerri' AND cmdb_inventory.Manager != 'gerri' AND cmdb_inventory.Manager2 != 'gerri' AND cmdb_inventory.Manager3 != 'gerri' AND cmdb_inventory.Manager4 != 'gerri' AND cmdb_inventory.Manager5 != 'gerri' AND cmdb_inventory.Manager6 != 'gerri' AND cmdb_inventory.Manager7 != 'gerri' AND cmdb_inventory.Manager8 != 'gerri' AND cmdb_inventory.Manager9 != 'gerri' AND cmdb_inventory.Manager10 != 'gerri') OR (cmdb_inventory.owner_id = 'tkhuu' OR cmdb_inventory.Manager = 'tkhuu' OR cmdb_inventory.Manager2 = 'tkhuu' OR cmdb_inventory.Manager3 = 'tkhuu' OR cmdb_inventory.Manager4 = 'tkhuu' OR cmdb_inventory.Manager5 = 'tkhuu' OR cmdb_inventory.Manager6 = 'tkhuu' OR cmdb_inventory.Manager7 = 'tkhuu' OR cmdb_inventory.Manager8 = 'tkhuu' OR cmdb_inventory.Manager9 = 'tkhuu' OR cmdb_inventory.Manager10 = 'tkhuu'))
		GROUP BY implemented
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		echo "{$row['implemented']} - {$row['counted']}<br />";
		
		if ($row['implemented'] == 'y') {
			$self_managed_y = $row['counted'];
		}
		if ($row['implemented'] == 'n') {
			$self_managed_n = $row['counted'];
		}
	}
	
	// Exceptions
	echo "<strong>Exceptions</strong><br />";
	$mysqli->real_query("
		SELECT COUNT(*) AS counted
		FROM cmdb_dump
		LEFT JOIN blocked_types ON blocked_types.type_name = cmdb_dump.`System Role`
		LEFT JOIN blocked_servers ON blocked_servers.server_address = cmdb_dump.`address`
		WHERE type_block_id IS NOT null OR server_block_id IS NOT null
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		echo "{$row['counted']}<br />";
		$exceptions = $row['counted'];
	}
	
	// No Owned
	echo "<strong>Owner Not Available</strong><br />";
	$mysqli->real_query("
		SELECT COUNT(*) AS counted, implemented
		FROM cmdb_dump
		WHERE `Owner Name` = 'Not Available'
		GROUP BY implemented
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		echo "{$row['implemented']} - {$row['counted']}<br />";
		if ($row['implemented'] == 'y') {
			$no_owner_y = $row['counted'];
		}
		if ($row['implemented'] == 'n') {
			$no_owner_n = $row['counted'];
		}
	}
	
	
	echo "<strong>Accounts</strong><br />";
	$mysqli->real_query("
		SELECT COUNT(*) AS counted, implemented
		FROM accounts_dump
		GROUP BY implemented
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		echo "{$row['implemented']} - {$row['counted']}<br />";
		if ($row['implemented'] == 'y') {
			$total_accounts_y = $row['counted'];
		}
		if ($row['implemented'] == 'n') {
			$total_accounts_n = $row['counted'];
		}
	}	
	
	$mysqli->real_query("
		INSERT INTO total_history (`total_servers_y`,`total_servers_n`,`it_managed_y`,`it_managed_n`,`it_owned_y`,`it_owned_n`,`self_managed_y`,`self_managed_n`,`no_owner_y`,`no_owner_n`,`total_accounts_y`,`total_accounts_n`,`exceptions`,date)
		VALUES ($total_servers_y,$total_servers_n,$it_managed_y,$it_managed_n,$it_owned_y,$it_owned_n,$self_managed_y,$self_managed_n,$no_owner_y,$no_owner_n,$total_accounts_y,$total_accounts_n,$exceptions,NOW())
	");

	echo mysqli_error($mysqli);
?>