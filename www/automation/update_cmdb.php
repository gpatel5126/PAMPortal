<?php
	
	require_once("../includes/dbconn.php");
	
	$cmdb_array = array();
	
	echo "
	<table>
		<tr>
			<th>Instance ID</th>
			<th>Reconciliation ID</th>
			<th>CI Name</th>
			<th>Owner Name</th>
			<th>Owner Contact</th>
			<th>Primary Contact</th>
			<th>Primary Contact Email</th>
			<th>Secondary Contact</th>
			<th>Secondary Contact Email</th>
			<th>Availability</th>
			<th>CI Integrity</th>
			<th>Confidentiality</th>
			<th>System Role</th>
			<th>Vendor Application</th>
			<th>Environment Specification</th>
			<th>Address</th>
		</tr>
	";
	
	$row = 1;
	$flag = true;
	$file = fopen("dumps/cmdb.csv","r");
	while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        $num = count($data);
		if ($row != 1) {
			echo "<tr>\n";
			//echo "<p> $num fields in line $row: <br /></p>\n";			
			
			// Run tests of the address
			$address = str_replace(".","",$data[2]);
			if ( is_numeric($address) ) {
				$address = $data[2];
			}
			else {
				$address = str_replace("http://","",$data[2]);
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
			echo "<td>{$data[14]}</td>";
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
			$col14 = mysqli_real_escape_string($mysqli, $data[14]);
			$address = mysqli_real_escape_string($mysqli, $address);
			
			$values_string = "('{$col0}','{$col1}','{$col2}','{$col3}','{$col4}','{$col5}','{$col6}','{$col7}','{$col8}','{$col9}','{$col10}','{$col11}','{$col12}','{$col13}','{$col14}','{$address}')";
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
			TRUNCATE table cmdb_dump
		");
		$mysqli->real_query("
			INSERT INTO cmdb_dump
			(`Instance ID`, `Reconciliation Identity`, `CI Name`, `Owner Name`, `Owner Contact`, `Primary Contact`, `Primary Contact Email`, `Secondary Contact`, `Secondary Contact Email`, `Availability`, `CI Integrity`, `Confidentiality`, `System Role`, `Vendor Application`, `Environment Specification`, `address`)
			VALUES $values;
		");
		$mysqli->real_query("
			UPDATE cmdb_dump
			RIGHT JOIN ca_dump ON cmdb_dump.`address` = ca_dump.`address` AND ca_dump.`Target System User Name` IN ('root','administrator','carkunix','carkadmin','monadmin','mcsadmin')
			SET implemented = 'y'
		");
		$mysqli->commit();
	} catch (Exception $e) {
		$mysqli->rollback();
	}
	
	
?>