<?php
	require_once("includes/dbconn.php");
	require_once("includes/access.php");
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	$user = $_GET['uid'];
	
	$i = 0;
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
			$i++;
		}
	}
	
	if ($i > 0) {
		
	
	}
	
	if ($stmt = $mysqli->prepare("
		SELECT m_uncompliant, o_uncompliant, m_compliant, o_compliant
		FROM account_user_compliance_history
		WHERE UID = ?
		ORDER BY account_user_compliance_history.date DESC
		LIMIT 1
	")) {
		$stmt->bind_param("s", $user);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$total = $row['m_uncompliant'] + $row['o_uncompliant'];
			$total_managed = $row['m_uncompliant'] + $row['m_compliant'];
			$total_owned = $row['o_uncompliant'] + $row['o_compliant'];
			$o_uncompliant = $row['o_uncompliant'];
		}
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
		$( document ).on( "click", "#left a", function() {
			if ( $(this).hasClass('below') ) {
				
				if ( $(this).is("#servers_owned") ) {
					$(".end").removeClass('active');
				}
			
				if ( $(this).hasClass("current") ) {
					$(this).removeClass('active');
					$(this).removeClass('current');
					
					$(this).siblings(".sub").slideUp();
					
					resetOwned();
				}
				else {
					$(this).parent().siblings("div").children("a").removeClass("active");
					$("#left a").removeClass("current");
					$(this).addClass('active');
					$(this).addClass('current');
					$(this).parent().siblings("div").children(".sub").slideUp();
					var hash = $(this).prop("hash");
					hash = hash.replace("#","");
					$(this).siblings(".sub").load('check_user_accounts.php?id='+hash).slideDown();
					$("#right").load('accounts_owned.php?id='+hash);
				}
			
			}
			else {
			
				$(this).parent().siblings("div").children("a").removeClass("active");
				$("#left a").removeClass("current");
				
				$(this).addClass('current');
				$(this).parent().siblings("div").children(".sub").slideUp();
				var hash = $(this).prop("hash");
				hash = hash.replace("#","");
				$("#right").load('accounts_owned.php?id='+hash);
			}
			return false;
		});
		
	});
	function resetOwned() {
		$("a#servers_owned").addClass("active");
		$("a#servers_owned").addClass("current");
		
		$("#right").load('accounts_owned.php?id=<?php echo $user; ?>');
	}
	</script>
</head>
<body>
<div id="report" class="cf">
	<h4>Account Compliance Report</h4>
	<h1><?php echo $fullName; ?></h1>
	
	<div id="left">
		<!-- Servers owned -->
		<div id="test">
			
		</div>
		
		<h3><?php echo $fullName; ?>'s Accounts</h3>
		<a class="current below" id="servers_owned" href="#<?php echo $user; ?>"><span><?php echo $total_owned; ?> accounts owned</span><em><?php echo $o_uncompliant; ?></em></a>
		
		<h3><?php echo $fullName; ?>'s Team</h3>
		<!-- Team -->
		<?php
			$team = 0;
			if ($stmt = $mysqli->prepare("
				SELECT c_id, ad.FullName, ad.UID, account_user_compliance_history.o_compliant, account_user_compliance_history.o_uncompliant, account_user_compliance_history.m_compliant, account_user_compliance_history.m_uncompliant
				FROM account_user_compliance_history
				LEFT JOIN ad ON account_user_compliance_history.UID = ad.UID
				WHERE ad.Manager = ? AND c_id IN (SELECT max(c_id) FROM account_user_compliance_history GROUP BY UID) AND (o_compliant > 0 OR o_uncompliant > 0 OR m_compliant > 0 OR m_uncompliant > 0)
				GROUP BY ad.FullName
				ORDER BY date DESC, ad.FullName ASC
			")) {
				
				$stmt->bind_param("s", $user);
				$stmt->execute();
				$res = $stmt->get_result();
				
				while ($row = $res->fetch_assoc()) {
					$fullName2 = $row['FullName'];
					
					$total = $row['m_uncompliant'] + $row['o_uncompliant'];
					$total_managed = $row['m_uncompliant'] + $row['m_compliant'];
					
					$total_owned = $row['o_uncompliant'];
					
					echo "
					<div>
						<a href='#{$row['UID']}'"; if ($total_managed > 0) { echo " class='below'"; } echo ">";
							echo "<span>{$fullName2}</span><em class='num'>{$total}</em>
						</a>
						<div class='sub'>
								
						</div>
					</div>";
					$team++;
				}
			}
			if ($team == 0) {
				echo "<span class='no'>No team members.</span>";
			}
		?>
		
		
	</div>
	
	
	<div id="right">
		<?php echo "<h2>{$fullName}'s Accounts</h2>"; ?>
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
			<tbody>
		<?php

			if ($stmt = $mysqli->prepare("
				SELECT *
				FROM accounts_dump
				WHERE `Owner` = ?
				LIMIT 25
			")) {
				$i = 0;
				$stmt->bind_param("s", $user);
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
						echo "<td><a class='protect' href='#'><i class='fa fa-chevron-circle-right'></i>Protect</a></td>";
					echo "</tr>";
				}
			}
			if ($i == 0) {
				echo "<td></td>";
				echo "<td colspan='4'>{$fullName} doesn't own any accounts.</td>";
			}


		?>
		</tbody>
		</table>
	</div>
	
	
</div>
</body>
</html>