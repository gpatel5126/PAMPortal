<?php
	require_once("includes/dbconn.php");
	require_once("includes/access.php");
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	$my_uid = $_SESSION['uid'];
	
	
	$per_page = 25;
	
	$page = 1;
	$prev_page = $page - 1;
	$prev_page2 = $page - 2;
	$next_page = $page + 1;
	$next_page2 = $page + 2;
	if ( isset($_GET['type']) ) {
		$type = $_GET['type'];
	} else {
		$type = 'it_managed';
	}
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
	
	
	if ($type == 'it_managed') {
		$type_name = "IT Managed Unprotected Servers";
	}
	else if ($type == "it_owned") {
		$type_name = "IT Self Managed Unprotected Servers";
	}
	else if ($type == "self_managed") {
		$type_name = "Self Managed Unprotected Servers";
	}
	else if ($type == "not_available") {
		$type_name = "Servers without an Owner Name";
	}	
	
	
	$i = 0;
	if ($stmt = $mysqli->prepare("
		SELECT FullName 
		FROM ad
		WHERE UID = ?
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$i++;
		}
	}
	
	$j = 0;
	if ($stmt = $mysqli->prepare("
		SELECT UID 
		FROM server_user_compliance_history
		WHERE UID = ?
		ORDER BY date DESC
		LIMIT 1
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$j++;
		}
	}
	
	if ($i != 0) {
	
		// Thresholds
		$yellow_threshold = 30;
		$green_threshold = 70;
		
		if ($stmt = $mysqli->prepare("
			SELECT FullName 
			FROM ad
			WHERE UID = ?
		")) {
			$stmt->bind_param("s", $target);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$fullName = $row['FullName'];
			}
		}
		
		// Get the number of uncompliant servers
		$uncompliant_servers_array = array();
		
		if ($stmt = $mysqli->prepare("
			SELECT *
			FROM server_user_compliance_history
			WHERE UID = ?
			ORDER BY date DESC
			LIMIT 1
		")) {
			$stmt->bind_param("s", $target);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$total_uncompliant = $row['o_uncompliant'] + $row['m_uncompliant'];
				array_push($uncompliant_servers_array, $total_uncompliant);
			}
		}
	}
	
?>
<!DOCTYPE html>
<html>
<head>
	<title>Adobe Privileged Account Portal</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="UTF-8"/>
	<!--[if IE]><script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7/html5shiv.min.js"></script><![endif]-->
	<meta name="viewport" content="width=device-width"/>
	<link rel="stylesheet" href="style.css"/>
	<script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="js/sparkline.js"></script>
	<script src="js/jquery.easypiechart.min.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
	
	<!-- Add fancyBox main JS and CSS files -->
	<script type="text/javascript" src="source/jquery.fancybox.js?v=2.1.5"></script>
	<link rel="stylesheet" type="text/css" href="source/jquery.fancybox.css?v=2.1.5" media="screen" />
	
	
	<script>
	$(function() {
	
		$('.fancybox').fancybox({
			autoSize: false,
            autoDimensions: false,
			width: '1000px',
			height: '90%',
		
			preload   : true
		});
		
		$( document ).on( "click", ".pages a", function() {
			var go = $(this).attr('href');
			$('#loading').show();
			$("#load_container").load(go, function() {
				$('#loading').hide();
			});
			$('#content').animate({
			   scrollTop: $("#content").offset().top
			});
			
			return false;
		});
		
		$( document ).on( "click", "a.claim", function() {
			var server_name = $(this).attr('rel');
			var $t = $(this);
			$.ajax({ 
				type: 'POST', 
				url: 'claim2.php', 
				data: { server_name: server_name }, 
				dataType: 'json',
				success: function (data) { 					
					if ( data['status'] == 'Success' ) {
						$t.parent().parent().addClass('success');
						$t.parent().siblings('.own_name').html( data['owner_name'] );
						$t.parent().html("<span class='msg'><strong>Success:</strong>&nbsp; Server claimed!</span>");
						
					}
					else {
						$t.parent().parent().addClass('error');
						$t.parent().html("<span class='msg'><strong>Error:</strong>&nbsp; Employee type not '1'</span>");
					}
					
				}
			});
			
			return false;
		});
		
	});
	</script>
</head>
<body>
<div id="report"></div>
<aside id="sidebar">
	<div id="logo">
		<img src="images/logo.png" alt="" /><span>Adobe Privileged Account Portal</span>
	</div>
	<nav>
		<ul>
			<li><a href="dashboard.php"><b></b><i class="fa fa-tachometer"></i>My Dashboard</a></li>
		</ul>
		
		<?php if ($priv) { ?>
		<h3>Executive Reports</h3>
		<ul>
			<li><a href="server_compliance.php" class="active"><b></b><i class="fa fa-laptop"></i>Server Compliance</a></li>
			<li><a href="account_compliance.php"><b></b><i class="fa fa-users"></i>Account Compliance</a></li>
			<li><a href="server_lookup.php"><b></b><i class="fa fa-search"></i>Server Lookup &amp; Reports</a></li>
			<?php if ($core) { ?><li><a href="login_logs.php"><b></b><i class="fa fa-file-text"></i>Log of User Logins</a></li> <?php } ?>
		</ul>
		<?php } ?>
		
		<h3>My Reports</h3>
		<ul>
			<li><a href="my_servers.php"><b></b><i class="fa fa-desktop"></i>My Servers<?php if ($i != 0 && $j != 0) { echo "<span>{$total_uncompliant}</span>"; } ?></a></li>
			<li><a href="my_accounts.php"><b></b><i class="fa fa-user"></i>My Accounts</a></li>
		</ul>
		
		<h3>Security Actions</h3>
		<ul>
			<li><a href="create_safe.php"><b></b><i class="fa fa-lock"></i>Create/Manage Safes</a></li>
			<li><a href="create_account.php"><b></b><i class="fa fa-briefcase"></i>Add/Manage Objects</a></li>
			<li><a href="#"><b></b><i class="fa fa-briefcase"></i>Pending Approvals<span>7</span></a></li>
			<li><a href="logout.php"><b></b><i class="fa fa-sign-out"></i>Logout</a></li>
		</ul>
	</nav>
</aside>
<h1 id="main"><i class="fa fa-desktop"></i>Server Report
<?php if ($priv) { ?>
<div id="choose"><form method="post" action="set_user.php">Active Directory UID: &nbsp;<input type="text" name="id" value="<?php echo $target; ?>" /><input type="submit" value="Go" /><a href="me.php" id="link_me">Back to me</a></form><span>(Available during beta testing only)</span></div>
<?php } ?>
</h1>
<div id="content">
	<div id="mydash">
		<?php if ($i==0) { ?>
		<div id="welcome">
			<h2>This user isn't in Active Directory, please choose another user.</h2>
		</div>
		
		<?php } else { ?>
		<div class="one-col cf">
			<div>
				<h2><i class="fa fa-user"></i><?php echo $type_name; ?></h2>
				<div id="loading" style="display: none;">
					loading...
				</div>
				<div id="load_container">
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
								<?php if ($type == "not_available") { ?><th class='action'>Action</th> <?php } ?>
							</tr>
						</thead>
						<?php 
							if ($type == 'it_managed') {
								$stmt = $mysqli->prepare("
									SELECT *
									FROM cmdb_dump
									WHERE implemented = 'n' AND cmdb_dump.`System Role` IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server')
									ORDER BY cmdb_dump.address ASC
									LIMIT ?,?
								");
							}
							else if($type == 'it_owned') {
								$stmt = $mysqli->prepare("
									SELECT *, cmdb_dump.`Owner Name`
									FROM cmdb_inventory
									LEFT JOIN cmdb_dump ON cmdb_dump.address = cmdb_inventory.address
									WHERE cmdb_dump.implemented = 'n' AND cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id = 'gerri' OR cmdb_inventory.Manager = 'gerri' OR cmdb_inventory.Manager2 = 'gerri' OR cmdb_inventory.Manager3 = 'gerri' OR cmdb_inventory.Manager4 = 'gerri' OR cmdb_inventory.Manager5 = 'gerri' OR cmdb_inventory.Manager6 = 'gerri' OR cmdb_inventory.Manager7 = 'gerri' OR cmdb_inventory.Manager8 = 'gerri' OR cmdb_inventory.Manager9 = 'gerri' OR cmdb_inventory.Manager10 = 'gerri') AND (cmdb_inventory.owner_id != 'tkhuu' AND cmdb_inventory.Manager != 'tkhuu' AND cmdb_inventory.Manager2 != 'tkhuu' AND cmdb_inventory.Manager3 != 'tkhuu' AND cmdb_inventory.Manager4 != 'tkhuu' AND cmdb_inventory.Manager5 != 'tkhuu' AND cmdb_inventory.Manager6 != 'tkhuu' AND cmdb_inventory.Manager7 != 'tkhuu' AND cmdb_inventory.Manager8 != 'tkhuu' AND cmdb_inventory.Manager9 != 'tkhuu' AND cmdb_inventory.Manager10 != 'tkhuu'))
									ORDER BY cmdb_dump.address ASC
									LIMIT ?,?
								");
							}
							else if ($type == 'self_managed') {
								$stmt = $mysqli->prepare("
									SELECT *
									FROM cmdb_inventory
									LEFT JOIN cmdb_dump ON cmdb_dump.address = cmdb_inventory.address
									WHERE cmdb_dump.implemented = 'n' AND cmdb_inventory.`System Role` NOT IN ('Iaas Managed Blade','Iaas AIX LPAR Managed','Iaas Linux Managed VM','Iaas Windows Managed VM','Legacy Windows Managed Server') AND ((cmdb_inventory.owner_id != 'gerri' AND cmdb_inventory.Manager != 'gerri' AND cmdb_inventory.Manager2 != 'gerri' AND cmdb_inventory.Manager3 != 'gerri' AND cmdb_inventory.Manager4 != 'gerri' AND cmdb_inventory.Manager5 != 'gerri' AND cmdb_inventory.Manager6 != 'gerri' AND cmdb_inventory.Manager7 != 'gerri' AND cmdb_inventory.Manager8 != 'gerri' AND cmdb_inventory.Manager9 != 'gerri' AND cmdb_inventory.Manager10 != 'gerri') OR (cmdb_inventory.owner_id = 'tkhuu' OR cmdb_inventory.Manager = 'tkhuu' OR cmdb_inventory.Manager2 = 'tkhuu' OR cmdb_inventory.Manager3 = 'tkhuu' OR cmdb_inventory.Manager4 = 'tkhuu' OR cmdb_inventory.Manager5 = 'tkhuu' OR cmdb_inventory.Manager6 = 'tkhuu' OR cmdb_inventory.Manager7 = 'tkhuu' OR cmdb_inventory.Manager8 = 'tkhuu' OR cmdb_inventory.Manager9 = 'tkhuu' OR cmdb_inventory.Manager10 = 'tkhuu'))
									ORDER BY cmdb_dump.address ASC
									LIMIT ?,?
								");
							}
							else if ($type == 'not_available') {
								$stmt = $mysqli->prepare("
									SELECT *
									FROM cmdb_dump
									WHERE `Owner Name` = 'Not Available'
									ORDER BY address ASC
									LIMIT ?,?
								");								
							}
							
							$i = 0;
							
							$stmt->bind_param("ii", $lower_limit,$per_page);
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
				</div>
			</div>
		</div>
		
		<?php } ?>
	</div>
	
</div>
</body>
</html>