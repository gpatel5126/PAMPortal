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
		
		$first_row = array("Display Name","Owner","Protected?");
		fputcsv($fp, $first_row);
			
		$mysqli->real_query("
			SELECT `Display Name`, ad.FullName, accounts_dump.implemented
			FROM accounts_dump
			LEFT JOIN ad ON accounts_dump.owner = ad.uid
			WHERE accounts_dump.Owner = '$owner' OR accounts_dump.Manager = '$owner' OR accounts_dump.Manager2 = '$owner' OR accounts_dump.Manager3 = '$owner' OR accounts_dump.Manager4 = '$owner' OR accounts_dump.Manager5 = '$owner' OR accounts_dump.Manager5 = '$owner' OR accounts_dump.Manager6 = '$owner' OR accounts_dump.Manager7 = '$owner' OR accounts_dump.Manager8 = '$owner' OR accounts_dump.Manager9 = '$owner'
			ORDER BY `Display Name` ASC
		");
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			if ($row['implemented'] == "y") { $protected = "Yes"; }
			else { $protected = "No"; }
			
			$row_output = array($row['Display Name'], $row['FullName'], $protected);
			fputcsv($fp, $row);
		}
        
		/*
        while($row = mysql_fetch_assoc($result)) {
            fputcsv($fp, $row);
        }
		*/
        
        fclose($fp);
    }

    // Using the function
    $sql = "SELECT * FROM table";
    // $db_conn should be a valid db handle

    // output as an attachment
    query_to_csv($owner_id, "test.csv", true);

?>