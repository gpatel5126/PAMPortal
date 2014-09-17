<?php
	require_once("../includes/dbconn.php");
	session_start();
	
	if ( isset($_POST['limit']) ) {
		$limit = $_POST['limit'];
	}
	else {
		$limit = 25;
	}
	if ( isset($_POST['page']) ) {
		$page = $_POST['page'];
	}
	else {
		$page = 1;
	}
	
	if ( isset($_POST['report']) ) {
		$report = $_POST['report'];
	}
	else {
		$report = "it_managed";
	}
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = mysqli_real_escape_string($mysqli, $_COOKIE['user_id']);
	} else {
		$target = mysqli_real_escape_string($mysqli, $_SESSION['uid']);
	}
	
	// Get user full name
	if ($stmt = $mysqli->prepare("
		SELECT FullName
		FROM ad
		WHERE UID = ?
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$fullName = $row['FullName'];
		}
	}
	
	// Get filter parameters
	if ( isset($_POST['server_query']) ) {
		$search = mysqli_real_escape_string($mysqli, $_POST['server_query']);
		$search = "AND cleansed_cmdb_dump.address LIKE '%{$search}%'";
	} else {
		$search = "";
	}
	if ( isset($_POST['prot']) ) {
		$prot = mysqli_real_escape_string($mysqli, $_POST['prot']);
		if ($prot == "all") { $prot = ""; }
		if ($prot == "protected") { $prot = "AND cleansed_cmdb_dump.implemented = 'y'"; }
		if ($prot == "unprotected") { $prot = "AND cleansed_cmdb_dump.implemented = 'n'"; }
	} else {
		$prot = "";
	}
	if ( isset($_POST['roles']) ) {
		$roles_filt = mysqli_real_escape_string($mysqli, $_POST['roles']);
		if ($roles_filt == "all") { $roles_filt = ""; }
		else {
			$roles_filt = explode(",",$roles_filt);
			$roles_filt = implode("', '", $roles_filt);
			if ($report == "it_owned") {
				$roles_filt = "AND cmdb_inventory.`System Role` IN ('{$roles_filt}')";
			}
			else {
				$roles_filt = "AND cleansed_cmdb_dump.`System Role` IN ('{$roles_filt}')";
			}
		}
	} else {
		$roles_filt = "";
	}
	
	
	$starting = ($page*$limit) - $limit;
	
	$roles_arr = array();
	$cmdb_arr = array();
	
	if ($report == "it_managed") {
	
		// System Roles
		if ($stmt = $mysqli->prepare("
			SELECT `System Role`
			FROM cleansed_cmdb_dump
			WHERE cleansed_cmdb_dump.`System Role` IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server')
			GROUP BY `System Role`
			ORDER BY `System Role` ASC
		")) {
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$roles = array("role_name"=>$row['System Role']);
				array_push($roles_arr,$roles);
			}
		}
	
		// The count
		if ($stmt = $mysqli->prepare("
			SELECT COUNT(*) as counted
			FROM cleansed_cmdb_dump
			WHERE cleansed_cmdb_dump.`System Role` IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') $search $prot $roles_filt
			ORDER BY cleansed_cmdb_dump.address ASC
		")) {
			//$stmt->bind_param("s", $fullName);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$results = $row['counted'];
			}
		}
		
		// The results
		$stmt = $mysqli->prepare("	
			SELECT *
			FROM cleansed_cmdb_dump
			WHERE cleansed_cmdb_dump.`System Role` IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') $search $prot $roles_filt
			ORDER BY cleansed_cmdb_dump.address ASC
			LIMIT ?,?
		");
		$stmt->bind_param("ii", $starting, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		
	}
	else if ($report == "it_owned") {
		
		// System Roles
		if ($stmt = $mysqli->prepare("
			SELECT cmdb_inventory.`System Role`
			FROM cmdb_inventory
			LEFT JOIN cleansed_cmdb_dump ON cleansed_cmdb_dump.address = cmdb_inventory.address
			WHERE cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id = 'gerri' OR cmdb_inventory.Manager = 'gerri' OR cmdb_inventory.Manager2 = 'gerri' OR cmdb_inventory.Manager3 = 'gerri' OR cmdb_inventory.Manager4 = 'gerri' OR cmdb_inventory.Manager5 = 'gerri' OR cmdb_inventory.Manager6 = 'gerri' OR cmdb_inventory.Manager7 = 'gerri' OR cmdb_inventory.Manager8 = 'gerri' OR cmdb_inventory.Manager9 = 'gerri' OR cmdb_inventory.Manager10 = 'gerri') AND (cmdb_inventory.owner_id != 'tkhuu' AND cmdb_inventory.Manager != 'tkhuu' AND cmdb_inventory.Manager2 != 'tkhuu' AND cmdb_inventory.Manager3 != 'tkhuu' AND cmdb_inventory.Manager4 != 'tkhuu' AND cmdb_inventory.Manager5 != 'tkhuu' AND cmdb_inventory.Manager6 != 'tkhuu' AND cmdb_inventory.Manager7 != 'tkhuu' AND cmdb_inventory.Manager8 != 'tkhuu' AND cmdb_inventory.Manager9 != 'tkhuu' AND cmdb_inventory.Manager10 != 'tkhuu'))
			GROUP BY `System Role`
			ORDER BY `System Role` ASC
		")) {
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$roles = array("role_name"=>$row['System Role']);
				array_push($roles_arr,$roles);
			}
		}
	
		// The count
		if ($stmt = $mysqli->prepare("
			SELECT COUNT(DISTINCT cmdb_inventory.address) as counted
			FROM cmdb_inventory
			LEFT JOIN cleansed_cmdb_dump ON cleansed_cmdb_dump.address = cmdb_inventory.address
			WHERE cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id = 'gerri' OR cmdb_inventory.Manager = 'gerri' OR cmdb_inventory.Manager2 = 'gerri' OR cmdb_inventory.Manager3 = 'gerri' OR cmdb_inventory.Manager4 = 'gerri' OR cmdb_inventory.Manager5 = 'gerri' OR cmdb_inventory.Manager6 = 'gerri' OR cmdb_inventory.Manager7 = 'gerri' OR cmdb_inventory.Manager8 = 'gerri' OR cmdb_inventory.Manager9 = 'gerri' OR cmdb_inventory.Manager10 = 'gerri') AND (cmdb_inventory.owner_id != 'tkhuu' AND cmdb_inventory.Manager != 'tkhuu' AND cmdb_inventory.Manager2 != 'tkhuu' AND cmdb_inventory.Manager3 != 'tkhuu' AND cmdb_inventory.Manager4 != 'tkhuu' AND cmdb_inventory.Manager5 != 'tkhuu' AND cmdb_inventory.Manager6 != 'tkhuu' AND cmdb_inventory.Manager7 != 'tkhuu' AND cmdb_inventory.Manager8 != 'tkhuu' AND cmdb_inventory.Manager9 != 'tkhuu' AND cmdb_inventory.Manager10 != 'tkhuu')) $search $prot $roles_filt
		")) {
			//$stmt->bind_param("s", $fullName);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$results = $row['counted'];
			}
		}
		
		// The results
		$stmt = $mysqli->prepare("	
			SELECT DISTINCT cleansed_cmdb_dump.*
			FROM cmdb_inventory
			LEFT JOIN cleansed_cmdb_dump ON cleansed_cmdb_dump.address = cmdb_inventory.address
			WHERE cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id = 'gerri' OR cmdb_inventory.Manager = 'gerri' OR cmdb_inventory.Manager2 = 'gerri' OR cmdb_inventory.Manager3 = 'gerri' OR cmdb_inventory.Manager4 = 'gerri' OR cmdb_inventory.Manager5 = 'gerri' OR cmdb_inventory.Manager6 = 'gerri' OR cmdb_inventory.Manager7 = 'gerri' OR cmdb_inventory.Manager8 = 'gerri' OR cmdb_inventory.Manager9 = 'gerri' OR cmdb_inventory.Manager10 = 'gerri') AND (cmdb_inventory.owner_id != 'tkhuu' AND cmdb_inventory.Manager != 'tkhuu' AND cmdb_inventory.Manager2 != 'tkhuu' AND cmdb_inventory.Manager3 != 'tkhuu' AND cmdb_inventory.Manager4 != 'tkhuu' AND cmdb_inventory.Manager5 != 'tkhuu' AND cmdb_inventory.Manager6 != 'tkhuu' AND cmdb_inventory.Manager7 != 'tkhuu' AND cmdb_inventory.Manager8 != 'tkhuu' AND cmdb_inventory.Manager9 != 'tkhuu' AND cmdb_inventory.Manager10 != 'tkhuu')) $search $prot $roles_filt
			ORDER BY cmdb_inventory.address ASC
			LIMIT ?,?
		");
		$stmt->bind_param("ii", $starting, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		
	}
	else if ($report == "not_available") {
		
		// System Roles
		if ($stmt = $mysqli->prepare("
			SELECT `System Role`
			FROM cleansed_cmdb_dump
			WHERE `Owner Name` = 'Not Available'
			GROUP BY `System Role`
			ORDER BY `System Role` ASC
		")) {
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$roles = array("role_name"=>$row['System Role']);
				array_push($roles_arr,$roles);
			}
		}
	
		// The count
		if ($stmt = $mysqli->prepare("
			SELECT COUNT(*) as counted
			FROM cleansed_cmdb_dump
			WHERE `Owner Name` = 'Not Available'
			ORDER BY address ASC
		")) {
			//$stmt->bind_param("s", $fullName);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$results = $row['counted'];
			}
		}
		
		// The results
		$stmt = $mysqli->prepare("	
			SELECT *
			FROM cleansed_cmdb_dump
			WHERE `Owner Name` = 'Not Available'
			ORDER BY address ASC
			LIMIT ?,?
		");
		$stmt->bind_param("ii", $starting, $limit);
		$stmt->execute();
		$res = $stmt->get_result();
		
	}
	while ($row = $res->fetch_assoc()) {
		$temp_arr = array("address"=>$row['address'],"Owner Name"=>$row['Owner Name'], "primary_contact"=>$row['Primary Contact'], "primary_contact"=>$row['Primary Contact'],"implemented"=>$row['implemented'],"confidentiality"=>$row['Confidentiality'],"owner"=>$row['Owner Name'],"role"=>$row['System Role'],"ci"=>$row['CI Name']);
		array_push($cmdb_arr, $temp_arr);
	}
	
	$arr = array('num_results' => $results, 'results' => $cmdb_arr, 'roles' => $roles_arr);
	
	echo json_encode($arr);
	
?>