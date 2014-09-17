<table>
	<thead>
		<th>IP</th>
		<th>Address (name)</th>
		<th>Destination IP</th>
	</thead>

<?php
	require_once("../includes/dbconn.php");
	
	$row = 1;
	$flag = true;
	$file = fopen("dumps/ad_servers.csv","r");
	while (($data = fgetcsv($file, 1000, ",")) !== FALSE && $row < 50) {
        $num = count($data);
		if ($row != 1) {
			echo "<tr>\n";
			//echo "<p> $num fields in line $row: <br /></p>\n";	

			$ip = $data[0];
			$dest_ip = $data[1];	
			$ip_resolved = gethostbyaddr($ip);
			//$ip_resolved = "test.corp.adobe.com";
			
			// Run tests of the address
			$address = str_replace(".","",$ip_resolved);
			if ( is_numeric($address) ) {
				$address = $data[0];
			}
			else {
				$address = str_replace("http://","",$ip_resolved);
				$address = str_replace("https://","",$address);
				$address = str_replace("www.","",$address);
				$address = explode(".",$address);
				$address = $address[0];
			}
			
			echo "<tr>";
			echo "<td>{$row}</td>";
			echo "<td>{$ip}</td>";
			echo "<td>{$address}</td>";
			echo "<td>{$dest_ip}</td>";
			echo "</tr>\n";
			
			ob_flush();
			flush();
			
			$mysqli->real_query("
				UPDATE ad_servers
				SET address='$address'
				WHERE `Source IP` = '$ip'
			");
			
			/*
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
			*/
			
			/*
			for ($c=0; $c < $num; $c++) {
				echo "<td>". $data[$c] . "</td>\n";
			}
			*/
		
		}
		$row++;
    }
	
	fclose($file);
?>