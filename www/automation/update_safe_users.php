<?php
	
	require_once("../includes/dbconn.php");
	
	$cmdb_array = array();
	
	echo "
	<table>
		<tr>
			<th>User ID</th>
			<th>User Name</th>
		</tr>
	";
	
	$row = 1;
	$flag = true;
	$file = fopen("ca_dumps/userslist.csv","r");
	while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
        $num = count($data);
		if ($row > 3) {
			echo "<tr>\n";
			//echo "<p> $num fields in line $row: <br /></p>\n";			
			
			// Run tests of the address			
			echo "<td>{$data[0]}</td>";
			echo "<td>{$data[1]}</td>";
			
			$col0 = mysqli_real_escape_string($mysqli, $data[0]);
			$col1 = mysqli_real_escape_string($mysqli, $data[1]);
			
			$values_string = "({$col0},'{$col1}')";
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
			TRUNCATE table safe_users
		");
		$mysqli->real_query("
			INSERT INTO safe_users
			(`UserID`, `UserName`)
			VALUES $values;
		");
		$mysqli->commit();
	} catch (Exception $e) {
		$mysqli->rollback();
	}
	
	
?>