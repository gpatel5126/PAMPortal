<?php
	
	require_once("../includes/dbconn.php");
	
	$ca_array = array();
	
	echo "
	<table>
		<tr>
			<th>Safe</th>
			<th>Device Type</th>
			<th>Policy ID</th>
			<th>Target System Address</th>
			<th>Target System User Name</th>
			<th>Group Name</th>
			<th>Last Accessed Date</th>
			<th>Last Accessed By</th>
			<th>Last Modified Date</th>
			<th>Last Modified By</th>
			<th>Change Failure</th>
			<th>Verification Failure</th>
			<th>Failure Reason</th>
			<th>Name</th>
			<th>Address</th>
		</tr>
	";
	
	$row = 1;
	$flag = true;
	$file = fopen("dumps/ca.csv","r");
	while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        $num = count($data);
		if ($row != 1) {
			echo "<tr>\n";
			//echo "<p> $num fields in line $row: <br /></p>\n";			
			
			// Run tests of the address
			$address = str_replace(".","",$data[3]);
			if ( is_numeric($address) ) {
				$address = $data[3];
			}
			else {
				$address = str_replace("http://","",$data[3]);
				$address = str_replace("https://","",$address);
				$address = str_replace("www.","",$address);
				$address = explode(".",$address);
				$address = $address[0];
			}
			
			echo "<td>{$data[0]}</td>";
			echo "<td>{$data[1]}</td>";
			echo "<td>{$data[2]}</td>";
			echo "<td>{$data[3]}</td>";
			echo "<td>{$data[4]}</td>";
			echo "<td>{$data[5]}</td>";
			echo "<td>{$data[6]}</td>";
			echo "<td>{$data[7]}</td>";
			echo "<td>{$data[8]}</td>";
			echo "<td>{$data[9]}</td>";
			echo "<td>{$data[10]}</td>";
			echo "<td>{$data[11]}</td>";
			echo "<td>{$data[12]}</td>";
			echo "<td>{$data[13]}</td>";
			echo "<td>{$address}</td>";
			
			$col0 = mysqli_real_escape_string($mysqli, $data[0]);
			$col1 = mysqli_real_escape_string($mysqli, $data[1]);
			$col2 = mysqli_real_escape_string($mysqli, $data[2]);
			$col3 = mysqli_real_escape_string($mysqli, $data[3]);
			$col4 = mysqli_real_escape_string($mysqli, $data[4]);
			$col5 = mysqli_real_escape_string($mysqli, $data[5]);
			$col6 = mysqli_real_escape_string($mysqli, $data[6]);
			$col7 = mysqli_real_escape_string($mysqli, $data[7]);
			$col8 = mysqli_real_escape_string($mysqli, $data[8]);
			$col9 = mysqli_real_escape_string($mysqli, $data[9]);
			$col10 = mysqli_real_escape_string($mysqli, $data[10]);
			$col11 = mysqli_real_escape_string($mysqli, $data[11]);
			$col12 = mysqli_real_escape_string($mysqli, $data[12]);
			$col13 = mysqli_real_escape_string($mysqli, $data[13]);
			$address = mysqli_real_escape_string($mysqli, $address);
			
			$values_string = "('{$col0}','{$col1}','{$col2}','{$col3}','{$col4}','{$col5}','{$col6}','{$col7}','{$col8}','{$col9}','{$col10}','{$col11}','{$col12}','{$col13}','{$address}')";
			if ($data[0] != "") {
				array_push($ca_array, $values_string);
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
	
	$values = implode(",", $ca_array);
	
	
	
	echo "
	</table>";
	
	fclose($file);
	
	
	// Start the MySQL transaction
	try {
		$mysqli->autocommit(FALSE);
		
		$mysqli->real_query("
			TRUNCATE table ca_dump
		");
		$mysqli->real_query("
			INSERT INTO ca_dump
			(`Safe`, `Device Type`, `Policy ID`, `Target System Address`, `Target System User Name`, `Group Name`, `Last accessed date`, `Last Accessed By`, `Last Modified Date`, `Last Modified By`, `Change Failure`, `Verification Failure`, `Failure Reason`, `Name`, `address`)
			VALUES $values;
		");	
		$mysqli->real_query("
			UPDATE cmdb_dump
			RIGHT JOIN ca_dump ON cmdb_dump.`address` = ca_dump.`address` AND ca_dump.`Target System User Name` IN ('root','administrator','carkunix','carkadmin','monadmin','mcsadmin')
			SET implemented = 'y'
		");
		$mysqli->real_query("
			UPDATE ad
			SET implemented = 'y' 
			WHERE employeeType IN (0,9) AND uid IN (SELECT account FROM implemented_accounts)
		");
		$mysqli->commit();
	} catch (Exception $e) {
		$mysqli->rollback();
		echo "Failed.";
	}
?>