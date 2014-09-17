<?php
	require_once("includes/dbconn.php");
	require_once("includes/access.php");
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	if (!$core) {
		exit;
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
	<title>PAM Portal</title>
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
	});
	</script>
</head>
<body>
<aside id="sidebar">
	<div id="logo">
		<img src="images/logo.png" alt="" /><span>Privileged Account Management Portal</span>
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
			<?php if ($core) { ?><li><a href="feedback_log.php"><b></b><i class="fa fa-file-text"></i>Feedback Log</a></li> <?php } ?>
			<?php if ($core) { ?><li><a href="acct_policy_updates.php" class="active"><b></b><i class="fa fa-file-text"></i>Account Policy Updates</a></li> <?php } ?>
		</ul>
		<?php } ?>
		
		<h3>My Reports</h3>
		<ul>
			<li><a href="my_servers.php"><b></b><i class="fa fa-desktop"></i>My Server Accounts<?php if ($i != 0 && $j != 0) { echo "<span>{$total_uncompliant}</span>"; } ?></a></li>
			<li><a href="my_accounts.php"><b></b><i class="fa fa-user"></i>My Accounts</a></li>
		</ul>
		
		<h3>Security Actions</h3>
		<ul>
			<li><a href="create_safe.php"><b></b><i class="fa fa-lock"></i>Create/Manage Safes</a></li>
			<li><a href="create_account.php"><b></b><i class="fa fa-briefcase"></i>Add/Manage Accounts</a></li>
			<!--<li><a href="#"><b></b><i class="fa fa-briefcase"></i>Pending Approvals<span>7</span></a></li>-->
			<li><a href="logout.php"><b></b><i class="fa fa-sign-out"></i>Logout</a></li>
		</ul>
	</nav>
</aside>
<h1 id="main"><i class="fa fa-file-text"></i>Account Policy Updates
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
				<h2><i class="fa fa-file-text"></i>Account Policy Updates By Users</h2>
				<table class="my_servers">
					<thead>
						<tr>
							<th>Date/Time of Change</th>
							<th>Account Name</th>
							<th>Request Made By</th>
							<th>Changed To</th>
						</tr>
					</thead>
					<?php 
						if ($stmt = $mysqli->prepare("
							SELECT * 
							FROM account_policy_updates
							ORDER BY policy_update_id DESC
						")) {
							$i = 0;
							
							$stmt->execute();
							$res = $stmt->get_result();
							
							while ($row = $res->fetch_assoc()) {
								$i++;
								echo "<tr>";
									echo "<td>{$row['last_changed']}</td>";
									echo "<td>{$row['account_name']}</td>";
									echo "<td>{$row['requester_id']}</td>";
									echo "<td>{$row['status']}</td>";
									echo "</tr>";
							}
						}
						if ($i == 0) {
							echo "<td></td>";
							echo "<td colspan='5'>No account policy changes requested.</td>";
						}
					
					?>
					
				</table>
			</div>
		</div>
		
		<?php } ?>
	</div>
	
</div>
</body>
</html>