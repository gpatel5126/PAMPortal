<?php

require_once("includes/dbconn.php");

$per_page = 25;

$page = $_GET['page'];
$prev_page = $page - 1;
$prev_page2 = $page - 2;
$next_page = $page + 1;
$next_page2 = $page + 2;
$id = $_GET['id'];

/*$mysqli->real_query("
	SELECT FullName 
	FROM ad
	WHERE UID = '$id'
");
echo mysqli_error($mysqli);
$res = $mysqli->use_result();
while ($row = $res->fetch_assoc()) {
	$owner_name = $row['FullName'];
}*/

if ($stmt = $mysqli->prepare("
	SELECT FullName 
	FROM ad
	WHERE UID = ?
")) {
	$stmt->bind_param("ss", $id);
	$stmt->execute();
	$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
	$owner_name = $row['FullName'];
}
}

$lower_limit = ($page * $per_page) - $per_page;
$upper_limit = $page * $per_page;

if ($id != '') {
	/*$mysqli->real_query("
		SELECT COUNT(address) AS total_num
		FROM cmdb_dump
		WHERE `Owner Name` = '$owner_name'
	");
}
echo mysqli_error($mysqli);
$res = $mysqli->use_result();
while ($row = $res->fetch_assoc()) {
	$total_num = $row['total_num'];
}*/
if ($stmt = $mysqli->prepare("
		SELECT COUNT(address) AS total_num
		FROM cmdb_dump
		WHERE `Owner Name` = ?
	"))  {

$stmt->bind_param("s", $owner_name);
	$stmt->execute();
	$res = $stmt->get_result();
	
while ($row = $res->fetch_assoc()) {
	$total_num = $row['total_num'];
}
}



$pages = ceil($total_num / $per_page);

?>

<div class="pages">
	<div class="pp_cont">
		<?php if ($prev_page != 0) { ?>
			<a href="<?php echo "people_server_page.php?page={$prev_page}&id={$id}"; ?>" class="pp"><i class="fa fa-chevron-left"></i>Previous Page</a>
		<?php } ?>
	</div>
		<div class="numbers">
			<?php 
				if ($page - 2 > 0) { echo "<a href='people_server_page.php?page={$prev_page2}&id={$id}'>{$prev_page2}</a>"; }
				if ($page - 1 > 0) { echo "<a href='people_server_page.php?page={$prev_page}&id={$id}'>{$prev_page}</a>"; }
				if ($page > 0) { echo "<a class='active' href='people_server_page.php?page={$page}&id={$id}'>{$page}</a>"; }
				if ($page + 1 < $pages) { echo "<a href='people_server_page.php?page={$next_page}&id={$id}'>{$next_page}</a>"; }
				if ($page + 2 < $pages) { echo "<a href='people_server_page.php?page={$next_page2}&id={$id}'>{$next_page2}</a>"; }
			?>
		</div>
	<div class="np_cont">
		<?php if ($next_page < $pages) { ?>
			<a href="<?php echo "people_server_page.php?page={$next_page}&id={$id}"; ?>" class="np">Next Page<i class="fa fa-chevron-right"></i></a>
		<?php } ?>
	</div>
</div>

<table class="my_servers">
	<thead>
		<tr>
			<th class="status"></th>
			<th>Server name</th>
			<th>Protected?</th>
			<th>Confidentiality</th>
			<th>System Role</th>
			<th class='action'>Transfer/Unclaim</th>
			<th class='protect'>Protect</th>
		</tr>
	</thead>
	<?php 
		if ($id != 'it_managed') {
			$mysqli->real_query("
				SELECT *
				FROM cleansed_cmdb_dump
				WHERE `Owner Name` = '$owner_name'
				ORDER BY address ASC
				LIMIT $lower_limit,$per_page
			");
		}
		
		$i = 0;
		echo mysqli_error($mysqli);
		$res = $mysqli->use_result();
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
				echo "<td>{$row['System Role']}</td>";
				echo "<td><a class='transfer' href='#' rel='{$row['CI Name']}'><i class='fa fa-chevron-circle-right'></i>Transfer Ownership</a></td>";
				echo "<td><a class='protect'><i class='fa fa-lock'></i>Protect</a></td>";
			echo "</tr>";
		}
		if ($i == 0) {
			echo "<td></td>";
			echo "<td colspan='5'>You don't own any servers.</td>";
		}
	
	?>
	
</table>
<!-- <a href="#" class="fullreport"><i class="fa fa-chevron-circle-right"></i>View full report</a> -->
<div class="pages">
	<div class="pp_cont">
		<?php if ($prev_page != 0) { ?>
			<a href="<?php echo "people_server_page.php?page={$prev_page}&id={$id}"; ?>" class="pp"><i class="fa fa-chevron-left"></i>Previous Page</a>
		<?php } ?>
	</div>
		<div class="numbers">
			<?php 
				if ($page - 2 > 0) { echo "<a href='people_server_page.php?page={$prev_page2}&id={$id}'>{$prev_page2}</a>"; }
				if ($page - 1 > 0) { echo "<a href='people_server_page.php?page={$prev_page}&id={$id}'>{$prev_page}</a>"; }
				if ($page > 0) { echo "<a class='active' href='people_server_page.php?page={$page}&id={$id}'>{$page}</a>"; }
				if ($page + 1 < $pages) { echo "<a href='people_server_page.php?page={$next_page}&id={$id}'>{$next_page}</a>"; }
				if ($page + 2 < $pages) { echo "<a href='people_server_page.php?page={$next_page2}&id={$id}'>{$next_page2}</a>"; }
			?>
		</div>
	<div class="np_cont">
		<?php if ($next_page < $pages) { ?>
			<a href="<?php echo "people_server_page.php?page={$next_page}&id={$id}"; ?>" class="np">Next Page<i class="fa fa-chevron-right"></i></a>
		<?php } ?>
	</div>
</div>