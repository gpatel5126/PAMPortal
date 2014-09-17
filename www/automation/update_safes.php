<?php
	
	require_once("../includes/dbconn.php");
	
	$cmdb_array = array();
	
	echo "
	<table>
		<tr>
			<th>Safe ID</th>
			<th>Safe Name</th>
			<th>Safe Creation Date</th>
		</tr>
	";
	
	$row = 1;
	$flag = true;
	$file = fopen("ca_dumps/allsafes.csv","r");
	while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
        $num = count($data);
		if ($row > 3) {
			echo "<tr>\n";
			//echo "<p> $num fields in line $row: <br /></p>\n";			
			
			// Run tests of the address			
			echo "<td>{$data[0]}</td>";
			echo "<td>{$data[1]}</td>";
			echo "<td>{$data[28]}</td>";
			
			$col0 = mysqli_real_escape_string($mysqli, $data[0]);
			$col1 = mysqli_real_escape_string($mysqli, $data[1]);
			$col32 = mysqli_real_escape_string($mysqli, $data[28]);
			
			$col32=date("Y-m-d H:i:s",strtotime($col32));
			
			$values_string = "('{$col0}','{$col1}','{$col32}')";
			if ($data[0] != "") {
				array_push($cmdb_array, $values_string);
			}
			
			/*
			for ($c=0; $c < $num; $c++) {
				echo "<td>". $data[$c] . "</td>\n";
			}
			*/
		echo "</tr>\n";
		
		}
		$row++;
    }
	
	$values = implode(",", $cmdb_array);
	
	
	
	echo "
	</table>";
	
	echo $values;
	
	fclose($file);
	
	
	// Start the MySQL transaction
	try {
		$mysqli->autocommit(FALSE);
		$mysqli->real_query("
			TRUNCATE table safe_inventory
		");
		$mysqli->real_query("
			INSERT INTO safe_inventory
			(`SafeID`, `SafeName`, `SafeCreationDate`)
			VALUES $values;
		");
		$mysqli->commit();
	} catch (Exception $e) {
		$mysqli->rollback();
	}
	
	
?>