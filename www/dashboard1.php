<?php
	require_once("includes/dbconn.php");
	require_once("includes/access.php");
	
	// See if we're using the cookie or the session
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	// Check if there's any matching users in our AD table
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
			$fullName = $row['FullName'];
		}
	}
	
	// Check to see if they have any server history
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
	
	// Check to see if they have any account history
	$accounts = 0;
	if ($stmt = $mysqli->prepare("
		SELECT UID 
		FROM account_user_compliance_history
		WHERE UID = ?
		ORDER BY date DESC
		LIMIT 1
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$accounts++;
		}
	}
	
	// Check how many unclaimed servers there are
	if ($stmt = $mysqli->prepare("
		SELECT COUNT(`CI Name`) AS unclaimed
		FROM cmdb_dump
		WHERE `Owner Name` = 'Not Available'
	")) {
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$unclaimed = $row['unclaimed'];
		}
	}
	
	if ($i != 0) {
	
		// Thresholds
		$yellow_threshold = 30;
		$green_threshold = 70;
		
		// Get the number of uncompliant servers in an array
		$uncompliant_servers_array = array();
		
		$z = 0;
		if ($stmt = $mysqli->prepare("
			SELECT *, DATE_FORMAT(date, '%b %e') AS date2
			FROM server_user_compliance_history
			WHERE UID = ?
			ORDER BY date ASC
		")) {
			$stmt->bind_param("s", $target);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				if ($z == 0) {
					$starting_date = $row['date2'];
				}
				$total_uncompliant = $row['o_uncompliant'] + $row['m_uncompliant'];
				
				array_push($uncompliant_servers_array, $total_uncompliant);
				$z++;
				$ending_date = $row['date2'];
			}
		}
		$uncompliant_servers_data = implode(",", $uncompliant_servers_array);
		
		if ($j == 0) { $uncompliant_servers_data = '0,0'; $starting_date = 'No servers'; $ending_date = ''; }
		
		
		// Get the number of uncompliant servers in an array
		$uncompliant_accounts_array = array();
		
		$z = 0;
		if ($stmt = $mysqli->prepare("
			SELECT *, DATE_FORMAT(date, '%b %e') AS date2
			FROM account_user_compliance_history
			WHERE UID = ?
			ORDER BY date ASC
		")) {
			$stmt->bind_param("s", $target);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				if ($z == 0) {
					$a_starting_date = $row['date2'];
				}
				$total_uncompliant = $row['o_uncompliant'] + $row['m_uncompliant'];
				
				array_push($uncompliant_accounts_array, $total_uncompliant);
				$z++;
				$a_ending_date = $row['date2'];
			}
		}
		$uncompliant_accounts_data = implode(",", $uncompliant_accounts_array);
		
		if ($z == 0) { $uncompliant_accounts_data = '0,0'; $a_starting_date = 'No accounts'; $a_ending_date = ''; }
		
		require_once('includes/getStats.php');
	
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
	<script src="js/feedback.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="js/sparkline.js"></script>
	<script src="js/jquery.easypiechart.min.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
	<script>
	$(function() {
		$('.chart').easyPieChart({
			easing: 'easeOutBounce',
			onStep: function(from, to, percent) {
				$(this.el).find('.percent').text(Math.round(percent));
			},
			barColor: function(percent) {
				return (percent > 40) ? '#59c807' : '#b50000';
			},
			lineCap: 'butt',
			lineWidth: 11,
			scaleLength: 0,
			trackColor: '#e5e5e5'
		});
		var chart = window.chart = $('.chart').data('easyPieChart');
		$('.js_update').on('click', function() {
			chart.update(Math.random()*200-100);
		});
		$("#sparkline").sparkline([<?php echo $uncompliant_servers_data; ?>], {
			type: 'line',
			width: '100%',
			height: '50',
			lineColor: '#83bbf4',
			fillColor: '#eaf1f7',
			spotColor: undefined,
			minSpotColor: undefined,
			maxSpotColor: undefined,
			highlightSpotColor: undefined,
			highlightLineColor: undefined,
			normalRangeColor: '#ff0000',
			drawNormalOnTop: true});
		$("#sparkline2").sparkline([<?php echo $uncompliant_accounts_data; ?>], {
			type: 'line',
			width: '100%',
			height: '50',
			lineColor: '#83bbf4',
			fillColor: '#eaf1f7',
			spotColor: undefined,
			minSpotColor: undefined,
			maxSpotColor: undefined,
			highlightSpotColor: undefined,
			highlightLineColor: undefined,
			normalRangeColor: '#ff0000',
			drawNormalOnTop: true});
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
			<li><a href="dashboard.php" class="active"><b></b><i class="fa fa-tachometer"></i>My Dashboard</a></li>
		</ul>
		
		<?php if ($priv) { ?>
		<h3>Executive Reports</h3>
		<ul>
			<li><a href="server_compliance.php"><b></b><i class="fa fa-laptop"></i>Server Compliance</a></li>
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
			
			<li><a href="logout.php"><b></b><i class="fa fa-sign-out"></i>Logout</a></li>
		</ul>
	</nav>
</aside>
<h1 id="main"><i class="fa fa-tachometer"></i>My Dashboard
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
		<?php } else if ($j == 798798 && $accounts == 809808) { ?>
		<div class="notice">
			<i class="fa fa-exclamation-triangle"></i>There are still <?php echo $unclaimed; ?> unclaimed servers.  Improve the CMDB by <a href="server_report.php?type=not_available">claiming your servers now</a>!
		</div>
		
		<div id="welcome">
			<h2>This user doesn't own or manage any servers.</h2>
		</div>
		
		<?php } else { ?>
		<div class="notice">
			<i class="fa fa-exclamation-triangle"></i>There are still <?php echo $unclaimed; ?> unclaimed servers.  Improve the CMDB by <a href="server_report.php?type=not_available">claiming your servers now</a>!
		</div>
		
		<div id="welcome">
			<h2>Welcome, <?php echo $fullName; ?>!</h2>
		</div>
		<div id="scorecards">
			<?php
				$total_status = "grey";
				if ($a_account_total_compliance !== "N/A") {
					if ($a_account_total_compliance >= $green_threshold) { $total_status = "green"; }
					else if ($a_account_total_compliance >= $yellow_threshold) { $total_status = "yellow"; }
					else { $total_status = "red"; }
				}
			?>
			<div class="<?php echo $total_status; ?>">
				<h3>My Account Compliance</h3>
				<div class="left">
					<span class="complete"><?php echo $a_account_total_compliance; ?><?php if ($total_status != 'grey') { echo "%"; } ?></span>
					<span class="caption">Complete</span>
				</div>
				<div class="right">
					<span class="remaining"><?php echo number_format($a_total_uncompliant); ?></span>
					<span class="caption">Remaining</span>
				</div>
				<div class="progress"><span style="width: <?php echo $a_account_total_compliance; ?>%;"></span></div>
			</div>
			<?php
				$total_status = "grey";
				if ($server_total_compliance !== "N/A") {
					if ($server_total_compliance >= $green_threshold) { $total_status = "green"; }
					else if ($server_total_compliance >= $yellow_threshold) { $total_status = "yellow"; }
					else { $total_status = "red"; }
				}
			?>
			<div class="<?php echo $total_status; ?>">
				<h3>My Server Compliance</h3>
				<div class="left">
					<span class="complete"><?php echo $server_total_compliance; ?><?php if ($total_status != 'grey') { echo "%"; } ?></span>
					<span class="caption">Complete</span>
				</div>
				<div class="right">
					<span class="remaining"><?php echo number_format($total_uncompliant); ?></span>
					<span class="caption">Remaining</span>
				</div>
				<div class="progress"><span style="width: <?php echo $server_total_compliance; ?>%;"></span></div>
			</div>
			
			<div class="grey last">
				<h3>Security Actions</h3>
				<a href="create_safe.php" class="num"><span><i class="fa fa-lock"></i></span>Create a safe</a>
				<a href="create_account.php" class="num"><span><i class="fa fa-briefcase"></i></span>Add/manage objects</a>
			</div>
		</div>
		<div class="two-col cf">
			<div>
				<h2><i class="fa fa-user"></i>My account compliance</h2>
				<div class="hist_chart">
					<div id="sparkline2"></div>
					<span><?php echo $a_starting_date; ?></span><span class="end"><?php echo $a_ending_date; ?></span>
				</div>
				<div class="unc">
					<strong><?php echo number_format($a_total_uncompliant); ?></strong>
					<span>Unprotected<br />Accounts</span>
				</div>
				<div class="stats">
					<div>
						<?php
							$owned_status = "";
							if ($a_account_owned_compliance !== "N/A") {
								if ($a_account_owned_compliance >= $green_threshold) { $owned_status = "green"; }
								else if ($a_account_owned_compliance >= $yellow_threshold) { $owned_status = "yellow"; }
								else { $owned_status = "red"; }
							}
						?>
						<div class="<?php echo $owned_status; ?>"><?php echo $a_account_owned_compliance; if ($a_account_owned_compliance !== "N/A") { echo "%"; } ?></div>
						<span>Personal Compliance</span>
						<span class="lite"><?php if ($a_owned_uncompliant) { echo number_format($a_owned_uncompliant); } else { echo "0"; }; ?> Unprotected Accounts</span>
					</div>
					<div style="float: right;">
						<?php
							$team_status = "";
							if ($a_account_managed_compliance !== "N/A") {
								if ($a_account_managed_compliance >= $green_threshold) { $team_status = "green"; }
								else if ($a_account_managed_compliance >= $yellow_threshold) { $team_status = "yellow"; }
								else { $team_status = "red"; }
							}
						?>
						<div class="<?php echo $team_status; ?>"><?php echo $a_account_managed_compliance; if ($a_account_managed_compliance !== "N/A") { echo "%"; } ?></div>
						<span>Team Compliance</span>
						<span class="lite"><?php if ($a_managed_uncompliant) { echo number_format($a_managed_uncompliant); } else { echo "0"; }; ?> Unprotected Accounts</span>
					</div>
				</div>
				<div class="team">
					<table>
						<thead>
							<tr>
								<th>Team Member</th>
								<th class="c">Compliance</th>
								<th style="padding-left: 0px; padding-right: 0px;"></th>
							</tr>
						</thead>
						<tbody>
					
					<?php
						$users = 0;
						// List of people under you and their compliance
						if ($stmt = $mysqli->prepare("
							SELECT c_id, ad.FullName, account_user_compliance_history.o_compliant, account_user_compliance_history.o_uncompliant, account_user_compliance_history.m_compliant, account_user_compliance_history.m_uncompliant
							FROM account_user_compliance_history
							LEFT JOIN ad ON account_user_compliance_history.UID = ad.UID
							WHERE ad.Manager = ? AND c_id IN (SELECT max(c_id) FROM account_user_compliance_history GROUP BY UID) AND (o_compliant > 0 OR o_uncompliant > 0 OR m_compliant > 0 OR m_uncompliant > 0)
							ORDER BY date DESC, ad.FullName ASC
						")) {
							$stmt->bind_param("s", $target);
							$stmt->execute();
							$res = $stmt->get_result();
							while ($row = $res->fetch_assoc()) {
								$user_uncompliant = $row['o_uncompliant'] + $row['m_uncompliant'];
								$user_server_total_compliance = round(100*($row['o_compliant'] + $row['m_compliant'])/($row['o_compliant'] + $row['o_uncompliant'] + $row['m_compliant'] + $row['m_uncompliant']), 1);
								echo "<tr>";
									echo "<td>";
										echo $row['FullName'];
									echo "</td>";
									echo "<td>";
										echo "<span class='comp'>";
											echo $user_server_total_compliance;
											echo "%";
											if ($user_server_total_compliance == 0) { $user_server_total_compliance = 5; }
										echo "</span>";
										echo "<span class='bar "; if ($user_server_total_compliance >= $green_threshold) { echo 'green'; } else if ($user_server_total_compliance >= $yellow_threshold) { echo 'yellow'; }  echo "'>";
											echo "<em style='width: {$user_server_total_compliance}%'></em>";
										echo "</span>";
									echo "</td>";
									echo "<td style='padding-left: 0px; padding-right: 0px; color: #666666;'>{$user_uncompliant}</td>";
								echo "</tr>";
								$users++;
							}
						}
						if ($users == 0) {
							echo "<tr>";
								echo "<td colspan='2'>You don't manage any team members.</td>";
							echo "</tr>";
						}
						
					
					?>
							</tr>
						</tbody>
					</table>
				</div>
				<a href="my_accounts.php" class="fullreport"><i class="fa fa-chevron-circle-right"></i>View full report</a>
			</div>
			<div class="last">
				<h2><i class="fa fa-desktop"></i>My server compliance</h2>
				<div class="hist_chart">
					<div id="sparkline"></div>
					<span><?php echo $starting_date; ?></span><span class="end"><?php echo $ending_date; ?></span>
				</div>
				<div class="unc">
					<strong><?php echo number_format($total_uncompliant); ?></strong>
					<span>Unprotected<br />Servers</span>
				</div>
				<div class="stats">
					<div>
						<?php
							$owned_status = "";
							if ($server_owned_compliance !== "N/A") {
								if ($server_owned_compliance >= $green_threshold) { $owned_status = "green"; }
								else if ($server_owned_compliance >= $yellow_threshold) { $owned_status = "yellow"; }
								else { $owned_status = "red"; }
							}
						?>
						<div class="<?php echo $owned_status; ?>"><?php echo $server_owned_compliance; if ($server_owned_compliance !== "N/A") { echo "%"; } ?></div>
						<span>Personal Compliance</span>
						<span class="lite"><?php if ($owned_uncompliant) { echo number_format($owned_uncompliant); } else { echo "0"; }; ?> Unprotected Servers</span>
					</div>
					<div style="float: right;">
						<?php
							$team_status = "";
							if ($server_managed_compliance !== "N/A") {
								if ($server_managed_compliance >= $green_threshold) { $team_status = "green"; }
								else if ($server_managed_compliance >= $yellow_threshold) { $team_status = "yellow"; }
								else { $team_status = "red"; }
							}
						?>
						<div class="<?php echo $team_status; ?>"><?php echo $server_managed_compliance; if ($server_managed_compliance !== "N/A") { echo "%"; } ?></div>
						<span>Team Compliance</span>
						<span class="lite"><?php if ($managed_uncompliant) { echo number_format($managed_uncompliant); } else { echo "0"; }; ?> Unprotected Servers</span>
					</div>
				</div>
				<div class="team">
					<table>
						<thead>
							<tr>
								<th>Team Member</th>
								<th class="c">Compliance</th>
								<th style="padding-left: 0px; padding-right: 0px;"></th>
							</tr>
						</thead>
						<tbody>
					
					<?php
						$users = 0;
						// List of people under you and their compliance
						if ($stmt = $mysqli->prepare("
							SELECT c_id, ad.FullName, ad.UID, server_user_compliance_history.o_compliant, server_user_compliance_history.o_uncompliant, server_user_compliance_history.m_compliant, server_user_compliance_history.m_uncompliant
							FROM server_user_compliance_history
							LEFT JOIN ad ON server_user_compliance_history.UID = ad.UID
							WHERE ad.Manager = ? AND c_id IN (SELECT max(c_id) FROM server_user_compliance_history GROUP BY UID) AND (o_compliant > 0 OR o_uncompliant > 0 OR m_compliant > 0 OR m_uncompliant > 0)
							GROUP BY ad.FullName
							ORDER BY date DESC, ad.FullName ASC
						")) {
							$stmt->bind_param("s", $target);
							$stmt->execute();
							$res = $stmt->get_result();
							while ($row = $res->fetch_assoc()) {
								$user_uncompliant = $row['o_uncompliant'] + $row['m_uncompliant'];
								$user_server_total_compliance = round(100*($row['o_compliant'] + $row['m_compliant'])/($row['o_compliant'] + $row['o_uncompliant'] + $row['m_compliant'] + $row['m_uncompliant']), 1);
								echo "<tr>";
									echo "<td>";
										echo $row['FullName'];
									echo "</td>";
									echo "<td>";
										echo "<span class='comp'>";
											echo $user_server_total_compliance;
											echo "%";
											if ($user_server_total_compliance == 0) { $user_server_total_compliance = 5; }
										echo "</span>";
										echo "<span class='bar "; if ($user_server_total_compliance >= $green_threshold) { echo 'green'; } else if ($user_server_total_compliance >= $yellow_threshold) { echo 'yellow'; }  echo "'>";
											echo "<em style='width: {$user_server_total_compliance}%'></em>";
										echo "</span>";
									echo "</td>";
									echo "<td style='padding-left: 0px; padding-right: 0px; color: #666666;'>{$user_uncompliant}</td>";
								echo "</tr>";
								$users++;
							}
						}
						if ($users == 0) {
							echo "<tr>";
								echo "<td colspan='2'>You don't manage any team members that own servers.</td>";
							echo "</tr>";
						}
						
					
					?>
							</tr>
						</tbody>
					</table>
				</div>
				<a href="my_servers.php" class="fullreport"><i class="fa fa-chevron-circle-right"></i>View full report</a>
			</div>
		</div>
		<?php } ?>
	</div>
	
</div>
</body>
</html>