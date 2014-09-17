<?php
	

	
	if ($stmt = $mysqli->prepare("
		SELECT * 
		FROM server_user_compliance_history
		WHERE UID = ?
		ORDER BY date DESC
		LIMIT 1
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$owned_compliant = $row['o_compliant'];
			$owned_uncompliant = $row['o_uncompliant'];
			$managed_compliant = $row['m_compliant'];
			$managed_uncompliant = $row['m_uncompliant'];
			$j++;
		}
	}
	
	if ($j == 0) {
		$owned_compliant = 0;
		$owned_uncompliant = 0;
		$managed_compliant = 0;
		$managed_uncompliant = 0;
	}
	
	$total_owned = $owned_compliant + $owned_uncompliant;
	$total_managed = $managed_compliant + $managed_uncompliant;
	$total_servers = $total_owned + $total_managed;
	
	$total_uncompliant = $owned_uncompliant + $managed_uncompliant;
	
	if ($total_servers > 0) {
		$server_total_compliance = round(100*($owned_compliant + $managed_compliant)/($total_servers), 1);
	} else {
		$server_total_compliance = "N/A";
	}
	if ($owned_compliant + $owned_uncompliant > 0) {
		$server_owned_compliance = round(100*($owned_compliant)/($owned_compliant + $owned_uncompliant), 1);
	} else {
		$server_owned_compliance = "N/A";
	}
	if ($managed_compliant + $managed_uncompliant > 0) {
		$server_managed_compliance = round(100*($managed_compliant)/($managed_compliant + $managed_uncompliant), 1);
	} else {
		$server_managed_compliance = "N/A";
	}
	
	if (!isset($accounts)) { $accounts = 0; }
	
	// Accounts data
	if ($stmt = $mysqli->prepare("
		SELECT * 
		FROM account_user_compliance_history
		WHERE UID = ?
		ORDER BY date DESC
		LIMIT 1
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$a_owned_compliant = $row['o_compliant'];
			$a_owned_uncompliant = $row['o_uncompliant'];
			$a_managed_compliant = $row['m_compliant'];
			$a_managed_uncompliant = $row['m_uncompliant'];
		}
	}
	
	if ($accounts == 0) {
		$a_owned_compliant = 0;
		$a_owned_uncompliant = 0;
		$a_managed_compliant = 0;
		$a_managed_uncompliant = 0;
	}
	
	$a_total_owned = $a_owned_compliant + $a_owned_uncompliant;
	$a_total_managed = $a_managed_compliant + $a_managed_uncompliant;
	$a_total_accounts = $a_total_owned + $a_total_managed;
	
	$a_total_uncompliant = $a_owned_uncompliant + $a_managed_uncompliant;
	
	if ($a_total_accounts > 0) {
		$a_account_total_compliance = round(100*($a_owned_compliant + $a_managed_compliant)/($a_total_accounts), 1);
	} else {
		$a_account_total_compliance = "N/A";
	}
	if ($a_owned_compliant + $a_owned_uncompliant > 0) {
		$a_account_owned_compliance = round(100*($a_owned_compliant)/($a_owned_compliant + $a_owned_uncompliant), 1);
	} else {
		$a_account_owned_compliance = "N/A";
	}
	if ($a_managed_compliant + $a_managed_uncompliant > 0) {
		$a_account_managed_compliance = round(100*($a_managed_compliant)/($a_managed_compliant + $a_managed_uncompliant), 1);
	} else {
		$a_account_managed_compliance = "N/A";
	}
	
?>