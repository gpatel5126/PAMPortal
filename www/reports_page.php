<?php

require_once("includes/dbconn.php");

$per_page = 25;

$page = $_GET['page'];
$prev_page = $page - 1;
$prev_page2 = $page - 2;
$next_page = $page + 1;
$next_page2 = $page + 2;
$type = $_GET['type'];

$lower_limit = ($page * $per_page) - $per_page;
$upper_limit = $page * $per_page;

if ($type == 'it_managed') {
	$mysqli->real_query("
		SELECT COUNT(address) AS total_num
		FROM cmdb_dump
		WHERE implemented = 'n' AND cmdb_dump.`System Role` IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server')
	");
}
else if ($type == 'it_owned') {
	$mysqli->real_query("
		SELECT COUNT(address) AS total_num
		FROM cmdb_inventory
		WHERE implemented = 'n' AND cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id = 'gerri' OR cmdb_inventory.Manager = 'gerri' OR cmdb_inventory.Manager2 = 'gerri' OR cmdb_inventory.Manager3 = 'gerri' OR cmdb_inventory.Manager4 = 'gerri' OR cmdb_inventory.Manager5 = 'gerri' OR cmdb_inventory.Manager6 = 'gerri' OR cmdb_inventory.Manager7 = 'gerri' OR cmdb_inventory.Manager8 = 'gerri' OR cmdb_inventory.Manager9 = 'gerri' OR cmdb_inventory.Manager10 = 'gerri') AND (cmdb_inventory.owner_id != 'tkhuu' AND cmdb_inventory.Manager != 'tkhuu' AND cmdb_inventory.Manager2 != 'tkhuu' AND cmdb_inventory.Manager3 != 'tkhuu' AND cmdb_inventory.Manager4 != 'tkhuu' AND cmdb_inventory.Manager5 != 'tkhuu' AND cmdb_inventory.Manager6 != 'tkhuu' AND cmdb_inventory.Manager7 != 'tkhuu' AND cmdb_inventory.Manager8 != 'tkhuu' AND cmdb_inventory.Manager9 != 'tkhuu' AND cmdb_inventory.Manager10 != 'tkhuu'))
	");
}
else if ($type == 'self_managed') {
	$mysqli->real_query("
		SELECT COUNT(address) AS total_num
		FROM cmdb_inventory
		WHERE implemented = 'n' AND cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id != 'gerri' AND cmdb_inventory.Manager != 'gerri' AND cmdb_inventory.Manager2 != 'gerri' AND cmdb_inventory.Manager3 != 'gerri' AND cmdb_inventory.Manager4 != 'gerri' AND cmdb_inventory.Manager5 != 'gerri' AND cmdb_inventory.Manager6 != 'gerri' AND cmdb_inventory.Manager7 != 'gerri' AND cmdb_inventory.Manager8 != 'gerri' AND cmdb_inventory.Manager9 != 'gerri' AND cmdb_inventory.Manager10 != 'gerri') OR (cmdb_inventory.owner_id = 'tkhuu' OR cmdb_inventory.Manager = 'tkhuu' OR cmdb_inventory.Manager2 = 'tkhuu' OR cmdb_inventory.Manager3 = 'tkhuu' OR cmdb_inventory.Manager4 = 'tkhuu' OR cmdb_inventory.Manager5 = 'tkhuu' OR cmdb_inventory.Manager6 = 'tkhuu' OR cmdb_inventory.Manager7 = 'tkhuu' OR cmdb_inventory.Manager8 = 'tkhuu' OR cmdb_inventory.Manager9 = 'tkhuu' OR cmdb_inventory.Manager10 = 'tkhuu'))
	");
}
else if ($type == 'not_available') {
	$mysqli->real_query("
		SELECT COUNT(address) AS total_num
		FROM cmdb_dump
		WHERE `Owner Name` = 'Not Available'
	");
}


echo mysqli_error($mysqli);
$res = $mysqli->use_result();
while ($row = $res->fetch_assoc()) {
	$total_num = $row['total_num'];
}

$pages = ceil($total_num / $per_page);

?>

<div class="pages">
	<div class="pp_cont">
		<?php if ($prev_page != 0) { ?>
			<a href="<?php echo "reports_page.php?page={$prev_page}&type={$type}"; ?>" class="pp"><i class="fa fa-chevron-left"></i>Previous Page</a>
		<?php } ?>
	</div>
		<div class="numbers">
			<?php 
				if ($page - 2 > 0) { echo "<a href='reports_page.php?page={$prev_page2}&type={$type}'>{$prev_page2}</a>"; }
				if ($page - 1 > 0) { echo "<a href='reports_page.php?page={$prev_page}&type={$type}'>{$prev_page}</a>"; }
				if ($page > 0) { echo "<a class='active' href='reports_page.php?page={$page}&type={$type}'>{$page}</a>"; }
				if ($page + 1 < $pages) { echo "<a href='reports_page.php?page={$next_page}&type={$type}'>{$next_page}</a>"; }
				if ($page + 2 < $pages) { echo "<a href='reports_page.php?page={$next_page2}&type={$type}'>{$next_page2}</a>"; }
			?>
		</div>
	<div class="np_cont">
		<?php if ($next_page < $pages) { ?>
			<a href="<?php echo "reports_page.php?page={$next_page}&type={$type}"; ?>" class="np">Next Page<i class="fa fa-chevron-right"></i></a>
		<?php } ?>
	</div>
</div>

<table class="my_servers">
	<thead>
		<tr>
			<th class="status"></th>
			<th>Server name</th>
			<th>Owner</th>
			<th>Primary Contact</th>
			<th>Protected?</th>
			<th>Confidentiality</th>
			<th>System Role</th>
			<?php if ($type == "not_available") { ?><th class='action'>Action</th><?php } ?>
		</tr>
	</thead>
	<?php 
		if ($type == 'it_managed') {
			$mysqli->real_query("
				SELECT *
				FROM cmdb_dump
				WHERE implemented = 'n' AND cmdb_dump.`System Role` IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server')
				ORDER BY address ASC
				LIMIT $lower_limit,$per_page
			");
		}
		else if($type == 'it_owned') {
			$mysqli->real_query("
				SELECT *, cmdb_dump.`Owner Name`
				FROM cmdb_inventory
				LEFT JOIN cmdb_dump ON cmdb_dump.address = cmdb_inventory.address
				WHERE cmdb_dump.implemented = 'n' AND cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id = 'gerri' OR cmdb_inventory.Manager = 'gerri' OR cmdb_inventory.Manager2 = 'gerri' OR cmdb_inventory.Manager3 = 'gerri' OR cmdb_inventory.Manager4 = 'gerri' OR cmdb_inventory.Manager5 = 'gerri' OR cmdb_inventory.Manager6 = 'gerri' OR cmdb_inventory.Manager7 = 'gerri' OR cmdb_inventory.Manager8 = 'gerri' OR cmdb_inventory.Manager9 = 'gerri' OR cmdb_inventory.Manager10 = 'gerri') AND (cmdb_inventory.owner_id != 'tkhuu' AND cmdb_inventory.Manager != 'tkhuu' AND cmdb_inventory.Manager2 != 'tkhuu' AND cmdb_inventory.Manager3 != 'tkhuu' AND cmdb_inventory.Manager4 != 'tkhuu' AND cmdb_inventory.Manager5 != 'tkhuu' AND cmdb_inventory.Manager6 != 'tkhuu' AND cmdb_inventory.Manager7 != 'tkhuu' AND cmdb_inventory.Manager8 != 'tkhuu' AND cmdb_inventory.Manager9 != 'tkhuu' AND cmdb_inventory.Manager10 != 'tkhuu'))
				ORDER BY cmdb_dump.address ASC
				LIMIT $lower_limit,$per_page
			");
		}
		else if ($type == 'self_managed') {
			$mysqli->real_query("
				SELECT *
				FROM cmdb_inventory
				LEFT JOIN cmdb_dump ON cmdb_dump.address = cmdb_inventory.address
				WHERE cmdb_dump.implemented = 'n' AND cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id != 'gerri' AND cmdb_inventory.Manager != 'gerri' AND cmdb_inventory.Manager2 != 'gerri' AND cmdb_inventory.Manager3 != 'gerri' AND cmdb_inventory.Manager4 != 'gerri' AND cmdb_inventory.Manager5 != 'gerri' AND cmdb_inventory.Manager6 != 'gerri' AND cmdb_inventory.Manager7 != 'gerri' AND cmdb_inventory.Manager8 != 'gerri' AND cmdb_inventory.Manager9 != 'gerri' AND cmdb_inventory.Manager10 != 'gerri') OR (cmdb_inventory.owner_id = 'tkhuu' OR cmdb_inventory.Manager = 'tkhuu' OR cmdb_inventory.Manager2 = 'tkhuu' OR cmdb_inventory.Manager3 = 'tkhuu' OR cmdb_inventory.Manager4 = 'tkhuu' OR cmdb_inventory.Manager5 = 'tkhuu' OR cmdb_inventory.Manager6 = 'tkhuu' OR cmdb_inventory.Manager7 = 'tkhuu' OR cmdb_inventory.Manager8 = 'tkhuu' OR cmdb_inventory.Manager9 = 'tkhuu' OR cmdb_inventory.Manager10 = 'tkhuu'))
				ORDER BY cmdb_dump.address ASC
				LIMIT $lower_limit,$per_page
			");
		}
		else if ($type == 'not_available') {
			$mysqli->real_query("
				SELECT *
				FROM cmdb_dump
				WHERE `Owner Name` = 'Not Available'
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
				echo "<td class='own_name'>{$row['Owner Name']}</td>";
				echo "<td class='own_name'>{$row['Primary Contact']}</td>";
				echo "<td>{$protected}</td>";
				echo "<td>{$row['Confidentiality']}</td>";
				echo "<td>{$row['System Role']}</td>";
				if ($type == "not_available") { echo "<td><a class='claim' href='#' rel='{$row['CI Name']}'><i class='fa fa-chevron-circle-right'></i>Claim this server</a></td>"; }
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
			<a href="<?php echo "reports_page.php?page={$prev_page}&type={$type}"; ?>" class="pp"><i class="fa fa-chevron-left"></i>Previous Page</a>
		<?php } ?>
	</div>
		<div class="numbers">
			<?php 
				if ($page - 2 > 0) { echo "<a href='reports_page.php?page={$prev_page2}&type={$type}'>{$prev_page2}</a>"; }
				if ($page - 1 > 0) { echo "<a href='reports_page.php?page={$prev_page}&type={$type}'>{$prev_page}</a>"; }
				if ($page > 0) { echo "<a class='active' href='reports_page.php?page={$page}&type={$type}'>{$page}</a>"; }
				if ($page + 1 < $pages) { echo "<a href='reports_page.php?page={$next_page}&type={$type}'>{$next_page}</a>"; }
				if ($page + 2 < $pages) { echo "<a href='reports_page.php?page={$next_page2}&type={$type}'>{$next_page2}</a>"; }
			?>
		</div>
	<div class="np_cont">
		<?php if ($next_page < $pages) { ?>
			<a href="<?php echo "reports_page.php?page={$next_page}&type={$type}"; ?>" class="np">Next Page<i class="fa fa-chevron-right"></i></a>
		<?php } ?>
	</div>
</div>