<?php
	require_once("includes/dbconn.php");
	require_once("includes/access.php");
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	$per_page = 25;

	$page = 1;
	$prev_page = $page - 1;
	$prev_page2 = $page - 2;
	$next_page = $page + 1;
	$next_page2 = $page + 2;
	
	$i = 0;
	$mysqli->real_query("
		SELECT FullName 
		FROM ad
		WHERE UID = '$target'
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$i++;
		$fullName = $row['FullName'];
	}
	
	$mysqli->real_query("
		SELECT COUNT(`CI Name`) AS unclaimed
		FROM cmdb_dump
		WHERE `Owner Name` = 'Not Available'
	");
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$unclaimed = $row['unclaimed'];
	}
	
	$j = 0;
	$mysqli->real_query("
		SELECT UID 
		FROM server_user_compliance_history
		WHERE UID = '$target'
		ORDER BY date DESC
		LIMIT 1
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$j++;
	}
	
	
	/*
	$mysqli->real_query("
		SELECT `CI Name`
		FROM cleansed_cmdb_dump
		WHERE `Owner Name` = '$fullName'
		LIMIT 1
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$j++;
	}
	*/
	
	if ($i != 0) {
	
		// Thresholds
		$yellow_threshold = 30;
		$green_threshold = 70;
		
		$mysqli->real_query("
			SELECT FullName 
			FROM ad
			WHERE UID = '$target'
		");
		echo mysqli_error($mysqli);
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			$fullName = $row['FullName'];
		}
		
		// Get the count of servers we're showing
		$mysqli->real_query("
			SELECT COUNT(address) AS total_num
			FROM cleansed_cmdb_dump
			WHERE `Owner Name` = '$fullName'
		");
		echo mysqli_error($mysqli);
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			$total_num = $row['total_num'];
		}

		$pages = ceil($total_num / $per_page);
		
		// Get the number of uncompliant servers
		$uncompliant_servers_array = array();
		
		$mysqli->real_query("
			SELECT * 
			FROM server_user_compliance_history
			WHERE UID = '$target'
			ORDER BY date DESC
			LIMIT 1
		");
		echo mysqli_error($mysqli);
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			$total_uncompliant = $row['o_uncompliant'] + $row['m_uncompliant'];
			array_push($uncompliant_servers_array, $total_uncompliant);
		}
		
		$uncompliant_servers_data = implode(",", $uncompliant_servers_array);
		
		require_once('includes/getStats.php');
	
	}
	
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8"/>
	<!--[if IE]><script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7/html5shiv.min.js"></script><![endif]-->
	<title>Adobe Privileged Account Portal</title>
	<meta name="viewport" content="width=device-width"/>
	<link rel="stylesheet" href="style.css"/>
	<link rel="stylesheet" href="filters.css"/>
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
		
		var itemRow = $('table.team tr.s_row');
		itemRow.click(function(e) {
			e.preventDefault();
			$.fancybox({
				'href': $(this).find('td.server a').attr('href'),
				'autoSize': false,
				'autoDimensions': false,
				'width': '1000px',
				'height': '90%',
				'preload' : true,
				'transitionIn': 'none',
				'transitionOut': 'none',
				'type': 'iframe'
			});
		});
		
		$( document ).on( "click", "a.unclaim", function() {
			var server_name = $(this).attr('rel');
			var $a = $(this);
			
			$.ajax({ 
				type: 'POST', 
				url: 'unclaim.php', 
				data: { server_name: server_name }, 
				dataType: 'json',
				success: function (data) { 					
					if ( data['status'] == 'Success' ) {
						$a.parent().parent().addClass('success');
						$a.parent().html("<span class='msg'><strong>Successfully Unclaimed</strong></span>");
					}
					else {
						$a.parent().parent().addClass('error');
						$a.parent().html("<span class='msg'><strong>Error:</strong> "+ data['message'] +"</span>");
					}
					
				}
			});
			
			return false;
		});
		
		$( document ).on( "click", "a.transfer", function() {
			var server_name = $(this).attr('rel');
			var $t = $(this);
			
			$(this).parent().html("<form method='post' class='tt'><label>Transfer to:</label><span><b></b>Enter UID</span><input type='text' value='' name='transfer_to' /><input type='hidden' name='server_name' value='"+server_name+"' /><input type='submit' value='Go!'/></form><a href='#' class='unclaim' rel='"+server_name+"'>Unclaim server</a>");
			
			return false;
		});
		
		$( document ).on( "focus", "form.tt input[type=text]", function() {
			$(this).parent().children('span').fadeIn().css("display","inline-block");
			return false;
		});
		$( document ).on( "focusout", "form.tt input[type=text]", function() {
			$(this).parent().children('span').fadeOut();
			return false;
		});
		
		
		$( document ).on( "click", "a.reset", function() {
			var server_name = $(this).attr('rel');
			$('.errorMsg').fadeOut();
			$(this).parent().parent().parent().parent().removeClass("error");
			$(this).parent().parent().parent().html("<a class='transfer' href='#' rel='"+server_name+"'><i class='fa fa-chevron-circle-right'></i>Transfer Ownership</a>");
			return false;
		});
		
		$( document ).on( "submit", "form.tt", function() {
			var $a = $(this);
			
			$.ajax({ 
				type: 'POST', 
				url: 'transfer.php', 
				data: $($a).serialize(),
				dataType: 'json',
				success: function (data) { 					
					if ( data['status'] == 'Success' ) {
						$a.parent().parent().addClass('success');
						$a.parent().html("<span class='msg'><strong>Successfully Transferred</strong></span>");
					}
					else {
						$a.parent().parent().addClass('error');
						$a.parent().html("<span class='msg'><strong>Error</strong><div class='errorMsg'><b></b>"+ data['message'] +"<br /><a href='#' class='reset' rel='"+data['server_name']+"'>Go back</a></div>");
					}
					
				}
			});
			
			return false;
		});
		
	});
	</script>
</head>
<body>
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
			<li><a href="server_compliance.php"><b></b><i class="fa fa-laptop"></i>Server Compliance</a></li>
			<li><a href="account_compliance.php"><b></b><i class="fa fa-users"></i>Account Compliance</a></li>
		</ul>
		<?php } ?>
		
		<h3>My Reports</h3>
		<ul>
			<li><a href="my_servers.php" class="active"><b></b><i class="fa fa-desktop"></i>My Servers<?php if ($i != 0 && $j != 0) { echo "<span>{$total_uncompliant}</span>"; } ?></a></li>
			<li><a href="my_accounts.php"><b></b><i class="fa fa-user"></i>My Accounts</a></li>
		</ul>
		
		<h3>Security Actions</h3>
		<ul>
			<li><a href="create_safe.php"><b></b><i class="fa fa-lock"></i>Create/Manage Safes</a></li>
			<li><a href="#"><b></b><i class="fa fa-briefcase"></i>Add/Manage Objects</a></li>
			<li><a href="#"><b></b><i class="fa fa-briefcase"></i>Pending Approvals<span>7</span></a></li>
			<li><a href="logout.php"><b></b><i class="fa fa-sign-out"></i>Logout</a></li>
		</ul>
	</nav>
</aside>
<h1 id="main"><i class="fa fa-desktop"></i>My Servers</h1>
<?php if ($priv) { ?>
<div id="choose"><form method="post" action="set_user.php">Active Directory UID: &nbsp;<input type="text" name="id" value="<?php echo $target; ?>" /><input type="submit" value="Go" /><a href="me.php" id="link_me">Back to me</a></form><span>(Available during beta testing only)</span></div>
<?php } ?>
<div id="content">
	<div id="mydash">
		<?php if ($i==0) { ?>
		<div id="welcome">
			<h2>This user isn't in Active Directory, please choose another user.</h2>
		</div>
		<?php } else {?>
		<div class="notice">
			<i class="fa fa-exclamation-triangle"></i>Don't see your server listed?  See if it's one of the <a href="server_report.php?type=not_available"><?php echo $unclaimed; ?> unclaimed servers in the CMDB</a>!
		</div>
		<div class="one-col cf">
			<div>
				<h2><i class="fa fa-user"></i>Servers I own</h2>
				<div id="fb">
					<div class="filters">
						<span class="label"><i class="fa fa-filter"></i>Filters:</span>
						<ul>
							<li><a href="#" class='active'>All</a></li>
							<li><a href="#">Compliant</a></li>
							<li><a href="#">Uncompliant</a></li>
						</ul>
					</div>
					<div>
						<input type="text" name="search" />
					</div>
					<div>
						Page 1 of 25
						<a href="#"><</a><a href="#">></a>
					</div>
				</div>
				<div id="load_container">
					<?php if ($pages > 1) { ?>
					<div class="pages">
						<div class="pp_cont">
							<?php if ($prev_page != 0) { ?>
								<a href="<?php echo "people_server_page.php?page={$prev_page}&id={$target}"; ?>" class="pp"><i class="fa fa-chevron-left"></i>Previous Page</a>
							<?php } ?>
						</div>
							<div class="numbers">
								<?php 
									if ($page - 2 > 0) { echo "<a href='people_server_page.php?page={$prev_page2}&id={$target}'>{$prev_page2}</a>"; }
									if ($page - 1 > 0) { echo "<a href='people_server_page.php?page={$prev_page}&id={$target}'>{$prev_page}</a>"; }
									if ($page > 0) { echo "<a class='active' href='people_server_page.php?page={$page}&id={$target}'>{$page}</a>"; }
									if ($page + 1 < $pages) { echo "<a href='people_server_page.php?page={$next_page}&id={$target}'>{$next_page}</a>"; }
									if ($page + 2 < $pages) { echo "<a href='people_server_page.php?page={$next_page2}&id={$target}'>{$next_page2}</a>"; }
								?>
							</div>
						<div class="np_cont">
							<?php if ($next_page < $pages) { ?>
								<a href="<?php echo "people_server_page.php?page={$next_page}&id={$target}"; ?>" class="np">Next Page<i class="fa fa-chevron-right"></i></a>
							<?php } ?>
						</div>
					</div>
					<?php } ?>
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
							$mysqli->real_query("
								SELECT *
								FROM cleansed_cmdb_dump
								WHERE `Owner Name` = '$fullName'
								LIMIT 25
							");
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
					<?php if ($pages > 1) { ?>
					<div class="pages">
						<div class="pp_cont">
							<?php if ($prev_page != 0) { ?>
								<a href="<?php echo "people_server_page.php?page={$prev_page}&id={$target}"; ?>" class="pp"><i class="fa fa-chevron-left"></i>Previous Page</a>
							<?php } ?>
						</div>
							<div class="numbers">
								<?php 
									if ($page - 2 > 0) { echo "<a href='people_server_page.php?page={$prev_page2}&id={$target}'>{$prev_page2}</a>"; }
									if ($page - 1 > 0) { echo "<a href='people_server_page.php?page={$prev_page}&id={$target}'>{$prev_page}</a>"; }
									if ($page > 0) { echo "<a class='active' href='people_server_page.php?page={$page}&id={$target}'>{$page}</a>"; }
									if ($page + 1 < $pages) { echo "<a href='people_server_page.php?page={$next_page}&id={$target}'>{$next_page}</a>"; }
									if ($page + 2 < $pages) { echo "<a href='people_server_page.php?page={$next_page2}&id={$target}'>{$next_page2}</a>"; }
								?>
							</div>
						<div class="np_cont">
							<?php if ($next_page < $pages) { ?>
								<a href="<?php echo "people_server_page.php?page={$next_page}&id={$target}"; ?>" class="np">Next Page<i class="fa fa-chevron-right"></i></a>
							<?php } ?>
						</div>
					</div>
					<?php } ?>
				</div>
				<!-- <a href="#" class="fullreport"><i class="fa fa-chevron-circle-right"></i>View full report</a> -->
			</div>
			<div>
				<h2><i class="fa fa-users"></i>My team's servers</h2>
				<table class="my_servers team">
					<thead>
						<tr>
							<th class="status"></th>
							<th>Team Member</th>
							<th class='stat'>Owned</th>
							<th class='stat'>Managed</th>
							<th class='stat'>Unprotected</th>
							<th class='comp'>Compliance</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$users = 0;
						// List of people under you and their compliance
						$mysqli->real_query("
							SELECT c_id, ad.FullName, ad.UID, server_user_compliance_history.o_compliant, server_user_compliance_history.o_uncompliant, server_user_compliance_history.m_compliant, server_user_compliance_history.m_uncompliant
							FROM server_user_compliance_history
							LEFT JOIN ad ON server_user_compliance_history.UID = ad.UID
							WHERE ad.Manager = '$target' AND c_id IN (SELECT max(c_id) FROM server_user_compliance_history GROUP BY UID) AND (o_compliant > 0 OR o_uncompliant > 0 OR m_compliant > 0 OR m_uncompliant > 0)
							GROUP BY ad.FullName
							ORDER BY date DESC, ad.FullName ASC
						");
						echo mysqli_error($mysqli);
						$res = $mysqli->use_result();
						while ($row = $res->fetch_assoc()) {
							$user_server_total_compliance = round(100*($row['o_compliant'] + $row['m_compliant'])/($row['o_compliant'] + $row['o_uncompliant'] + $row['m_compliant'] + $row['m_uncompliant']), 1);
							$total_servers_owned = $row['o_compliant'] + $row['o_uncompliant'];
							$total_servers_managed = $row['m_compliant'] + $row['m_uncompliant'];
							$total_unprotected = $row['o_uncompliant'] + $row['m_uncompliant'];
							echo "<tr class='s_row'>";
								echo "<td></td>";
								echo "<td class='server'><a class='fancybox fancybox.iframe nm' href='server_user_report.php?uid={$row['UID']}'>";
									echo $row['FullName'];
								echo "<i class='fa fa-external-link-square'></i></a></td>";
								echo "<td class='stat'>{$total_servers_owned}</td>";
								echo "<td class='stat'>{$total_servers_managed}</td>";
								echo "<td class='stat'>{$total_unprotected}</td>";
								echo "<td class='comp'>";
									echo "<span class='comp'>";
										echo $user_server_total_compliance;
										echo "%";
										if ($user_server_total_compliance == 0) { $user_server_total_compliance = 5; }
									echo "</span>";
									echo "<span class='bar "; if ($user_server_total_compliance >= $green_threshold) { echo 'green'; } else if ($user_server_total_compliance >= $yellow_threshold) { echo 'yellow'; } echo "'>";
										echo "<em style='width: {$user_server_total_compliance}%'></em>";
									echo "</span>";
								echo "</td>";
								echo "<td class='act'><a class='protect' href='#'><i class='fa fa-chevron-circle-right'></i>Contact</a></td>";
							echo "</tr>";
							$users++;
						}
						if ($users == 0) {
							echo "<tr>";
								echo "<td></td>";
								echo "<td colspan='6'>You don't manage any team members that own servers.</td>";
							echo "</tr>";
						}
					
					?>
					
					</tbody>
				</table>	
			</div>
		</div>
		
		<?php } ?>
	</div>
	
</div>
</body>
</html>