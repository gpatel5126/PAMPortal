File names to check:
claim.php
csv/account_export.php
people_server_page.php
reports_page.php
server_compliance.php
server_report.php
transfer.php
unclaim.php

// Before
$server_matches = 0;
$mysqli->real_query("
	SELECT `CI Name`
	FROM cmdb_dump
	WHERE `Owner Name` = '$myfullName' AND `CI Name` = '$server_name'
");
echo mysqli_error($mysqli);
$res = $mysqli->use_result();
while ($row = $res->fetch_assoc()) {
	$server_matches++;
}


// After
$server_matches = 0;
if ($stmt = $mysqli->prepare("
	SELECT `CI Name`
	FROM cmdb_dump
	WHERE `Owner Name` = ? AND `CI Name` = ?
")) {
	$stmt->bind_param("ss", $myfullName, $server_name);
	$stmt->execute();
	$res = $stmt->get_result();
	
	while ($row = $res->fetch_assoc()) {
		$server_matches++;
	}
}