<?php
	
	require_once("../includes/dbconn.php");
	
	$cmdb_array = array();
	
	echo "
	<table>
		<tr>
			<th>Safe ID</th>
			<th>Safe Name</th>
			<th>Group ID</th>
			<th>Group Name</th>
		</tr>
	";
	
	$row = 1;
	$flag = true;
	$file = fopen("ca_dumps/safeowners.csv","r");
	while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
        $num = count($data);
		if ($row > 3) {
			echo "<tr>\n";
			//echo "<p> $num fields in line $row: <br /></p>\n";			
			
			// Run tests of the address			
			echo "<td>{$data[0]}</td>";
			echo "<td>{$data[1]}</td>";
			echo "<td>{$data[2]}</td>";
			echo "<td>{$data[3]}</td>";
			
			$col0 = mysqli_real_escape_string($mysqli, $data[0]);
			$col1 = mysqli_real_escape_string($mysqli, $data[1]);
			$col2 = mysqli_real_escape_string($mysqli, $data[2]);
			$col3 = mysqli_real_escape_string($mysqli, $data[3]);
			
			$values_string = "({$col0},'{$col1}',{$col2},'{$col3}')";
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
			TRUNCATE table safe_group_access
		");
		$mysqli->real_query("
			INSERT INTO safe_group_access
			(`SafeID`, `SafeName`, `GroupID`, `GroupName`)
			VALUES $values;
		");
		$mysqli->commit();
	} catch (Exception $e) {
		$mysqli->rollback();
	}
	
	
?>