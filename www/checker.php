CMDB results
<table style="width: 500px;">
	<tr>
		<th>CI Name</th>
		<th>Protected</th>
	</tr>
<?php

	require_once("includes/dbconn.php");
	
	$target = $_GET['target'];
	
	$mysqli->real_query("
		SELECT *
		FROM cmdb_dump
		WHERE `CI Name` LIKE '%$target%'
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		echo "<tr>";
		echo "<td>{$row['CI Name']}</td>";
		echo "<td>{$row['implemented']}</td>";
		echo "</tr>";
	}
	
?>
</table>
<br /><br />

CyberArk results
<table style="width: 500px;">
	<tr>
		<th>Address</th>
		<th>Target System Username</th>
	</tr>
<?php
	
	$target = $_GET['target'];
	
	$mysqli->real_query("
		SELECT *
		FROM ca_dump
		WHERE `Target System Address` LIKE '%$target%'
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		echo "<tr>";
		echo "<td>{$row['Target system address']}</td>";
		echo "<td>{$row['Target system user name']}</td>";
		echo "</tr>";
	}
	
?>
</table>