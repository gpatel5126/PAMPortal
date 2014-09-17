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
		
		if ($stmt = $mysqli->prepare("
			SELECT * 
			FROM total_history
			ORDER BY date DESC
			LIMIT 1
		")) {
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$total_accounts_y = $row['total_accounts_y'];
				$total_accounts_n = $row['total_accounts_n'];
				$total_accounts = $total_accounts_y + $total_accounts_n;
				$compliance_total_accounts = round(100*$total_accounts_y/$total_accounts,2);
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
			width: '<?php echo $compliance_total_accounts; ?>%',
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
		$("#sparkline").sparkline([210,200,199,194,189,186,185,182,169,164,170,163,160,147,145,150,136], {
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

		$("#sparkline2").sparkline([1249,1254,1289,1240,1450,1423,1420,1410,1319,1327,1323,1325,1320,1310,1300,1350,1334], {
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

		$("#sparkline3").sparkline([2700,2721,2750,2761,2751,2739,2737,2741,2745,2750,2780,2762,2761,2765,2760,2800,2847], {
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
			<li><a href="account_compliance.php" class="active"><b></b><i class="fa fa-users"></i>Account Compliance</a></li>
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
<h1 id="main"><i class="fa fa-users"></i>Account Compliance
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
				<h2><i class="fa fa-laptop"></i>Total Account Compliance</h2>
				<div class="totalBar"><span id="total_comp" style="width: 0%;"><?php echo $compliance_total_accounts; ?>%</span></div>
				<div class="leftNum"><span><?php echo number_format($total_accounts_y); ?></span>Protected Accounts</div>
				<div class="rightNum">Total Accounts<span><?php echo number_format($total_accounts); ?></span></div>
			</div>
		</div>
		
		<?php } ?>
	</div>
	
</div>
</body>
</html>