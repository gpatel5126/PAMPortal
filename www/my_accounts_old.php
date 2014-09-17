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
			$fullName = $row['FullName'];
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
	
	if ($i != 0 && ($j != 0 || $accounts != 0)) {
	
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
		
		$uncompliant_servers_data = implode(",", $uncompliant_servers_array);
		
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
		
		var itemRow = $('table.team tr.s_row');
		itemRow.click(function(e) {
			e.preventDefault();
			$.fancybox({
				'href': $(this).find('td.account a').attr('href'),
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
		$("#sparkline").sparkline([25,27,21,22,24,26,24,20,27,29,32,21,19,18,17,15,10], {
		//$("#sparkline").sparkline([<?php echo $uncompliant_servers_data; ?>], {
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
			<li><a href="server_compliance.php"><b></b><i class="fa fa-laptop"></i>Server Compliance</a></li>
			<li><a href="account_compliance.php"><b></b><i class="fa fa-users"></i>Account Compliance</a></li>
			<li><a href="server_lookup.php"><b></b><i class="fa fa-search"></i>Server Lookup &amp; Reports</a></li>
			<?php if ($core) { ?><li><a href="login_logs.php"><b></b><i class="fa fa-file-text"></i>Log of User Logins</a></li> <?php } ?>
		</ul>
		<?php } ?>
		
		<h3>My Reports</h3>
		<ul>
			<li><a href="my_servers.php"><b></b><i class="fa fa-desktop"></i>My Servers<?php if ($i != 0 && $j != 0) { echo "<span>{$total_uncompliant}</span>"; } ?></a></li>
			<li><a href="my_accounts.php" class="active"><b></b><i class="fa fa-user"></i>My Accounts</a></li>
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
<h1 id="main"><i class="fa fa-user"></i>My Accounts
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
		<?php } else if ($j == 0 && $accounts == 0) { ?>
		<div id="welcome">
			<h2>This user doesn't own or manage any servers.</h2>
		</div>
		
		<?php } else { ?>
		<div class="one-col cf">
			<div>
				<a href="csv/account_export.php" id="csv_download"><i class="fa fa-file-excel-o"></i>Download an account export (CSV)</a>
				<h2><i class="fa fa-user"></i>Accounts I own</h2>
				<table class="my_servers">
					<thead>
						<tr>
							<th class="status"></th>
							<th>Account</th>
							<th>Display Name</th>
							<th>Protected?</th>
							<th>Action</th>
						</tr>
					</thead>
					<?php 
						if ($stmt = $mysqli->prepare("
							SELECT *
							FROM accounts_dump
							WHERE `Owner` = ?
							LIMIT 25
						")) {
							$i = 0;
							$stmt->bind_param("s", $target);
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
									echo "<td>{$row['Account']}</td>";
									echo "<td>{$row['Display Name']}</td>";
									echo "<td>{$protected}</td>";
									echo "<td><a target='_blank' href='https://amp.corp.adobe.com/'><i class='fa fa-chevron-circle-right'></i>Manage in AMP</a></td>";
								echo "</tr>";
							}
						}
						if ($i == 0) {
							echo "<td></td>";
							echo "<td colspan='5'>You don't own any accounts.</td>";
						}
					
					?>
					
				</table>
				<!-- <a href="#" class="fullreport"><i class="fa fa-chevron-circle-right"></i>View full report</a> -->
			</div>
			<div>
				<h2><i class="fa fa-users"></i>My team's accounts</h2>
				<table class="my_servers team">
					<thead>
						<tr>
							<th class="status"></th>
							<th>Team Member</th>
							<th class='stat'>Owned</th>
							<th class='stat'>In Team</th>
							<th class='stat'>Unprotected</th>
							<th class='comp'>Compliance</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$users = 0;
						// List of people under you and their compliance
						if ($stmt = $mysqli->prepare("
							SELECT c_id, ad.UID, ad.FullName, account_user_compliance_history.o_compliant, account_user_compliance_history.o_uncompliant, account_user_compliance_history.m_compliant, account_user_compliance_history.m_uncompliant
							FROM account_user_compliance_history
							LEFT JOIN ad ON account_user_compliance_history.UID = ad.UID
							WHERE ad.Manager = ? AND c_id IN (SELECT max(c_id) FROM account_user_compliance_history GROUP BY UID) AND (o_compliant > 0 OR o_uncompliant > 0 OR m_compliant > 0 OR m_uncompliant > 0)
							ORDER BY date DESC, ad.FullName ASC
						")) {
							$stmt->bind_param("s", $target);
							$stmt->execute();
							$res = $stmt->get_result();
							
							while ($row = $res->fetch_assoc()) {
								$user_account_total_compliance = round(100*($row['o_compliant'] + $row['m_compliant'])/($row['o_compliant'] + $row['o_uncompliant'] + $row['m_compliant'] + $row['m_uncompliant']), 1);
								$total_accounts_owned = $row['o_compliant'] + $row['o_uncompliant'];
								$total_accounts_managed = $row['m_compliant'] + $row['m_uncompliant'];
								$total_unprotected = $row['o_uncompliant'] + $row['m_uncompliant'];
								echo "<tr class='s_row'>";
									echo "<td></td>";
									echo "<td class='account'><a class='fancybox fancybox.iframe nm' href='account_user_report.php?uid={$row['UID']}'>";
										echo $row['FullName'];
									echo "<i class='fa fa-external-link-square'></i></a></td>";
									echo "<td class='stat'>{$total_accounts_owned}</td>";
									echo "<td class='stat'>{$total_accounts_managed}</td>";
									echo "<td class='stat'>{$total_unprotected}</td>";
									echo "<td class='comp'>";
										echo "<span class='comp'>";
											echo $user_account_total_compliance;
											echo "%";
											if ($user_account_total_compliance == 0) { $user_account_total_compliance = 5; }
										echo "</span>";
										echo "<span class='bar "; if ($user_account_total_compliance >= $green_threshold) { echo 'green'; } else if ($user_account_total_compliance >= $yellow_threshold) { echo 'yellow'; } echo "'>";
											echo "<em style='width: {$user_account_total_compliance}%'></em>";
										echo "</span>";
									echo "</td>";
									echo "<td class='act'><a class='protect' href='#'><i class='fa fa-chevron-circle-right'></i>Contact</a></td>";
								echo "</tr>";
								$users++;
							}
						}
						if ($users == 0) {
							echo "<tr>";
								echo "<td></td>";
								echo "<td colspan='6'>You don't manage any team members.</td>";
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