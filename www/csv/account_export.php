<?php
	require_once("../includes/dbconn.php");
	require_once("../includes/access.php");
	
	if ( isset($_COOKIE['user_id']) ) {
		$owner_id = $_COOKIE['user_id'];
	} else {
		$owner_id = $_SESSION['uid'];
	}
	
	
    function query_to_csv($owner, $filename, $attachment = false, $headers = true) {
	
		global $mysqli;
        
        if($attachment) {
            // send response headers to the browser
            header( 'Content-Type: text/csv' );
            header( 'Content-Disposition: attachment;filename='.$filename);
            $fp = fopen('php://output', 'w');
        } else {
            $fp = fopen($filename, 'w');
        }
		
		
        
        // $result = mysql_query($query, $db_conn) or die( mysql_error( $db_conn ) );
        
        if($headers) {
            // output header row (if at least one row exists)
            /*
				$row = mysql_fetch_assoc($result);
				if($row) {
					fputcsv($fp, array_keys($row));
					// reset pointer back to beginning
					mysql_data_seek($result, 0);
				}
			*/
		}
		
		$first_row = array("Account","Account Display Name","Owner","Protected?");
		fputcsv($fp, $first_row);
			
		/*$mysqli->real_query("
			SELECT Account, `Display Name`, ad.FullName, accounts_dump.implemented
			FROM accounts_dump
			LEFT JOIN ad ON accounts_dump.owner = ad.uid
			WHERE accounts_dump.Owner = '$owner' OR accounts_dump.Manager = '$owner' OR accounts_dump.Manager2 = '$owner' OR accounts_dump.Manager3 = '$owner' OR accounts_dump.Manager4 = '$owner' OR accounts_dump.Manager5 = '$owner' OR accounts_dump.Manager5 = '$owner' OR accounts_dump.Manager6 = '$owner' OR accounts_dump.Manager7 = '$owner' OR accounts_dump.Manager8 = '$owner' OR accounts_dump.Manager9 = '$owner'
			ORDER BY `Display Name` ASC
		");
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			if ($row['implemented'] == "y") { $protected = "Yes"; }
			else { $protected = "No"; }
			
			$row_output = array($row['Account'], $row['Display Name'], $row['FullName'], $protected);
			fputcsv($fp, $row_output);
		}*/
        
		if ($stmt = $mysqli->prepare("
			SELECT Account, `Display Name`, ad.FullName, accounts_dump.implemented
			FROM accounts_dump
			LEFT JOIN ad ON accounts_dump.owner = ad.uid
			WHERE accounts_dump.Owner = ? OR accounts_dump.Manager = ? OR accounts_dump.Manager2 = ? OR accounts_dump.Manager3 = ? OR accounts_dump.Manager4 = ? OR accounts_dump.Manager5 = ? OR accounts_dump.Manager6 = ? OR accounts_dump.Manager7 = ? OR accounts_dump.Manager8 = ? OR accounts_dump.Manager9 = ?
			ORDER BY `Display Name` ASC
		")) 
		
		{
		$stmt->bind_param("ssssssssss", $owner, $owner, $owner, $owner, $owner, $owner, $owner, $owner, $owner, $owner);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) 
			{
			if ($row['implemented'] == "y") { $protected = "Yes"; }
			else { $protected = "No"; }
			
			$row_output = array($row['Account'], $row['Display Name'], $row['FullName'], $protected);
			fputcsv($fp, $row_output);
			}
		}
		/*
        while($row = mysql_fetch_assoc($result)) {
            fputcsv($fp, $row);
        }
		*/
        
        fclose($fp);
		
	}

    // output as an attachment
	$fileName = "{$owner_id}_accounts.csv";
    query_to_csv($owner_id, $fileName, true);

?>