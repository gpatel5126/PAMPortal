<?php
	require_once("../includes/dbconn.php");
	
	set_time_limit (0);
	
	$today = date("Y-m-d");
	$results = 0;
	$mysqli->real_query("
		SELECT UID
		FROM account_user_compliance_history
		WHERE date = '$today'
		LIMIT 1
	");	
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$results++;
	}	
	
	if ($results > 0) {
		$mysqli->real_query("
			DELETE FROM account_user_compliance_history WHERE date = '$today'
		");
	}
	
	
	$mysqli->real_query("
		INSERT INTO account_user_compliance_history
		(UID, m_compliant, m_uncompliant, o_compliant, o_uncompliant, date)
		SELECT UID, implemented_managed, unimplemented_managed, implemented_owned, unimplemented_owned, NOW()
		FROM account_stats
	");
	
	
	
?>