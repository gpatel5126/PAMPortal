<?php
	require_once("includes/dbconn.php");
	
	$user = $_GET['id'];
	
	if ($stmt = $mysqli->prepare("
		SELECT FullName 
		FROM ad
		WHERE UID = ?
	")) {
		$stmt->bind_param("s", $user);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$fullName = $row['FullName'];
		}
	}
	echo "<h2>{$fullName}'s Servers Owned</h2>"
?>

<table class="my_servers">
					<thead>
						<tr>
							<th class="status"></th>
							<th>Server name</th>
							<th>Protected?</th>
							<th>Confidentiality</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
<?php
	if ($stmt = $mysqli->prepare("
		SELECT *
		FROM cmdb_dump
		WHERE `Owner Name` = ?
		LIMIT 25
	")) {
		$i = 0;
		$stmt->bind_param("s", $fullName);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$i++;
			$protected = $row['implemented'];
			
			if ($protected == "n") {
				$protected = "No"; $p_status = "un";
			} else {
				$protected = "Yes"; $p_status = "pro";
			}
			echo "<tr class='{$p_status}'>";
				echo "<td class='status'><span></span></td>";
				echo "<td>{$row['address']}</td>";
				echo "<td>{$protected}</td>";
				echo "<td>{$row['Confidentiality']}</td>";
				echo "<td><a class='protect' href='#'><i class='fa fa-chevron-circle-right'></i>Protect</a></td>";
			echo "</tr>";
		}
	}
	if ($i == 0) {
		echo "<td></td>";
		echo "<td colspan='4'>{$fullName} doesn't own any servers.</td>";
	}
?>
</tbody>
</table>