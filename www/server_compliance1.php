<?php
	require_once("includes/dbconn.php");
	require_once("includes/access.php");
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
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
	
	/* ------------------------------------------------- */
	
	// IT Managed Server Array
	$itm_uncompliant_servers_array = array();
	
	$itm_z = 0;
	$mysqli->real_query("
		SELECT *, DATE_FORMAT(date, '%b %e') AS date2
		FROM total_history
		ORDER BY date ASC
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		if ($itm_z == 0) {
			$itm_starting_date = $row['date2'];
		}
		$itm_total_uncompliant = $row['it_managed_n'];
		array_push($itm_uncompliant_servers_array, $itm_total_uncompliant);
		$itm_z++;
		$itm_ending_date = $row['date2'];
	}
	$itm_uncompliant_servers_data = implode(",", $itm_uncompliant_servers_array);
	
	
	// IT Owned Server Array
	$ito_uncompliant_servers_array = array();
	
	$ito_z = 0;
	$mysqli->real_query("
		SELECT *, DATE_FORMAT(date, '%b %e') AS date2
		FROM total_history
		ORDER BY date ASC
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		if ($ito_z == 0) {
			$ito_starting_date = $row['date2'];
		}
		$ito_total_uncompliant = $row['it_owned_n'];
		array_push($ito_uncompliant_servers_array, $ito_total_uncompliant);
		$ito_z++;
		$ito_ending_date = $row['date2'];
	}
	$ito_uncompliant_servers_data = implode(",", $ito_uncompliant_servers_array);
	
	// Self Managed Server Array
	$s_uncompliant_servers_array = array();
	
	$s_z = 0;
	$mysqli->real_query("
		SELECT *, DATE_FORMAT(date, '%b %e') AS date2
		FROM total_history
		ORDER BY date ASC
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		if ($s_z == 0) {
			$s_starting_date = $row['date2'];
		}
		$s_total_uncompliant = $row['self_managed_n'];
		array_push($s_uncompliant_servers_array, $s_total_uncompliant);
		$s_z++;
		$s_ending_date = $row['date2'];
	}
	$s_uncompliant_servers_data = implode(",", $s_uncompliant_servers_array);
	
	
	/* ------------------------------------------------- */
	
	
	if ($i != 0) {
	
		// Thresholds
		$yellow_threshold = 30;
		$green_threshold = 70;
		
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
			}
		}
		
		$mysqli->real_query("
			SELECT * 
			FROM total_history
			ORDER BY date DESC
			LIMIT 1
		");
		echo mysqli_error($mysqli);
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			$total_servers_y = $row['total_servers_y'];
			$total_servers_n = $row['total_servers_n'];
			$total_servers = $total_servers_y + $total_servers_n;
			$compliance_total_servers = round(100*$total_servers_y/$total_servers,2);
			
			$it_managed_y = $row['it_managed_y'];
			$it_managed_n = $row['it_managed_n'];
			$total_it_managed = $it_managed_y + $it_managed_n;
			$compliance_it_managed = round(100*$it_managed_y/$total_it_managed,2);
			
			$it_owned_y = $row['it_owned_y'];
			$it_owned_n = $row['it_owned_n'];
			$total_it_owned = $it_owned_y + $it_owned_n;
			$compliance_it_owned = round(100*$it_owned_y/$total_it_owned,2);
			
			$self_managed_y = $row['self_managed_y'];
			$self_managed_n = $row['self_managed_n'];
			$total_self_managed = $self_managed_y + $self_managed_n;
			$compliance_self_managed = round(100*$self_managed_y/$total_self_managed,2);
			
			$no_owner_y = $row['no_owner_y'];
			$no_owner_n = $row['no_owner_n'];
			$total_no_owner = $no_owner_y + $no_owner_n;
			$compliance_no_owner = round(100*$no_owner_y/$total_no_owner,2);
			
			
			$no_owner_percent = round(100*$total_no_owner/$total_servers,2);
			
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
	<script src="js/feedback.js"></script>
	<!--[if lt IE 10]>
		<link rel="stylesheet" type="text/css" href="ie9.css">
	<![endif]-->
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="js/sparkline.js"></script>
	<script src="js/jquery.easypiechart.min.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
	<script>
	$(function() {
		$( "#total_comp" ).animate({
			width: '<?php echo $compliance_total_servers; ?>%',
		  }, 1000, "easeOutBounce", function() {
			// Animation complete.
		  });
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
			size: 100,
			scaleLength: 0,
			trackColor: '#e5e5e5'
		});
		var chart = window.chart = $('.chart').data('easyPieChart');
		$('.js_update').on('click', function() {
			chart.update(Math.random()*200-100);
		});
		$("#sparkline").sparkline([<?php echo $itm_uncompliant_servers_data; ?>], {
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

		$("#sparkline2").sparkline([<?php echo $ito_uncompliant_servers_data; ?>], {
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

		$("#sparkline3").sparkline([<?php echo $s_uncompliant_servers_data; ?>], {
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
			
			<li><a href="logout.php"><b></b><i class="fa fa-sign-out"></i>Logout</a></li>
		</ul>
	</nav>
</aside>
<h1 id="main"><i class="fa fa-laptop"></i>Server Compliance
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
				<h2><i class="fa fa-laptop"></i>Total Server Compliance</h2>
				<div class="totalBar"><span id="total_comp" style="width: 0%;"><?php echo $compliance_total_servers; ?>%</span></div>
				<div class="leftNum"><span><?php echo number_format($total_servers_y); ?></span>Compliant Servers</div>
				<div class="rightNum">Total Servers<span><?php echo number_format($total_servers); ?></span></div>
			</div>
		</div>
		<div id="server_scores">
			<div>
				<h3>IT "Managed" Servers</h3>
				<span class="chart" data-percent="<?php echo $compliance_it_managed; ?>">
					<span class="percent"></span>
				</span>
				<div class="stats2">
					<div class="split">
						<div>
							<span><?php echo number_format($it_managed_y); ?></span>
							<em>Compliant</em>
						</div>
						<div class='un'>
							<span><?php echo number_format($it_managed_n); ?></span>
							<em>Remaining</em>
						</div>
					</div>
					<span class="total"><?php echo number_format($total_it_managed); ?></span>
					<em>Total Servers</em>
				</div>
				<div class="hist_chart" style="width: 100%;">
					<strong><i class="fa fa-bar-chart-o"></i>Remaining uncompliant servers</strong>
					<div id="sparkline"></div>
					<span><?php echo $itm_starting_date; ?></span><span class="end"><?php echo $itm_ending_date; ?></span>
				</div>
				<a href="server_report.php?type=it_managed" class="full_report"><i class="fa fa-arrow-circle-right"></i>View full report</a>
			</div>
			
			<div class="last">
				<h3>IT "Self-Managed" Servers</h3>
				<span class="chart" data-percent="<?php echo $compliance_it_owned; ?>">
					<span class="percent"></span>
				</span>
				<div class="stats2">
					<div class="split">
						<div>
							<span><?php echo number_format($it_owned_y); ?></span>
							<em>Compliant</em>
						</div>
						<div class='un'>
							<span><?php echo number_format($it_owned_n); ?></span>
							<em>Remaining</em>
						</div>
					</div>
					<span class="total"><?php echo number_format($total_it_owned); ?></span>
					<em>Total Servers</em>
				</div>
				<div class="hist_chart" style="width: 100%;">
					<strong><i class="fa fa-bar-chart-o"></i>Remaining uncompliant servers</strong>
					<div id="sparkline2"></div>
					<span><?php echo $ito_starting_date; ?></span><span class="end"><?php echo $ito_ending_date; ?></span>
				</div>
				<a href="server_report.php?type=it_owned" class="full_report"><i class="fa fa-arrow-circle-right"></i>View full report</a>
			</div>
			
			
		</div>
		
		<div class="one-col cf">
			<div class="grey">
				<h2><i class="fa fa-laptop"></i>Servers with owner name "Not Available"</h2>
				<div class="totalBar"><span style="width: <?php echo $no_owner_percent; ?>%"><?php echo $no_owner_percent; ?>%</span></div>
				<div class="leftNum"><span><?php echo number_format($total_no_owner); ?></span>Total servers with no owner listed</div>
				<div class="rightNum">Total servers<span><?php echo number_format($total_servers); ?></span></div>
				<a href="server_report.php?type=not_available" id="na"><i class="fa fa-arrow-circle-right"></i>View all servers with owner name "Not Available"</a>
			</div>
			<div>
				<h2><i class="fa fa-bar-chart-o"></i>Compliance over time</h2>
				Historical compliance graph coming soon.
			</div>
		</div>
		
		<?php } ?>
	</div>
	
</div>
</body>
</html>