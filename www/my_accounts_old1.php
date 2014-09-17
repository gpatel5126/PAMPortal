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
		
		if ($j == 0) { $uncompliant_accounts_data = '0,0'; $a_starting_date = 'No accounts'; $a_ending_date = ''; }
		
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
	<link rel="stylesheet" href="protect_account.css"/>
	<script src="js/jquery.js"></script>
	<script src="js/feedback.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="js/sparkline.js"></script>
	<script src="js/highlight.js"></script>
	<script src="js/jquery.easypiechart.min.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
	
	<!-- Add fancyBox main JS and CSS files -->
	<script type="text/javascript" src="source/jquery.fancybox.js?v=2.1.5"></script>
	<link rel="stylesheet" type="text/css" href="source/jquery.fancybox.css?v=2.1.5" media="screen" />
	
	
	<script>
	$(function() {
		// Initial variables
		var geo = $('select#geoSelect').val();
		var nickname = '';
		var env_code = 'GEN';
		inv_limit = 12;
		safe_limit = 12;
		var xhr;
		
		inv_search_value = '';
		safeName = '';
		accountName = '';
		
		
		safeQuery = '';
		currentSafe = 'none';
		inputFlag = true;
		passwordFlag = true;
		
		// Safe listing pages
		page = 1;
		pages = 1;
		safePage = 1;
		safePages = 1;
		
		var all_page = 1;	
		inv_page = 1;
		inv_pages = 1;
		
		$('.fancybox').fancybox({
			autoSize: false,
            autoDimensions: false,
			width: '1000px',
			height: '90%',
		
			preload   : true
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
		
		$(document).on("keyup paste", "input[type=password]", function() {
			var passwordVal = $("input[name=password]").val();
			var password2Val = $("input[name=password2]").val();
			
			if ( passwordVal != "" && password2Val != "" ) {
				inputFlag = false;
				if ( passwordVal != password2Val ) {
					$(".passwords .p_error").css("display","block");
					$(".passwords .p_error").show();
					passwordFlag = true;
				}
				else {
					$(".passwords .p_error").hide();
					passwordFlag = false;
				}
			}
			else {
				inputFlag = true;
			}
			
			if (inputFlag == true || passwordFlag == true) {
				$("#protectButton").addClass("inactive");
			}
			else {
				$("#protectButton").removeClass("inactive");
			}
		});
		
		$("#protectButton").click( function() {
			if ( $(this).hasClass('inactive') ) {
			
			}
			else {
				$("#protectForm").submit();
			}
		});
		
		// When you search for a safe
		$( document ).on( "keyup", "input#safe_search", function() {
			safePage = 1;
			safeQuery = $(this).val();
			if (safeQuery.length > 0) { $(this).removeClass("inactive"); }
			loadChooseSafes();
		});
		
		
		$('input#safe_search').on('blur', function(){
		   if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val('start typing to search your safes...'); }
		   
		}).on('focus', function(){
			$(this).removeClass('inactive');
			if ( $(this).val() == "start typing to search your safes..." ) { $(this).val(''); }
		});
		
		// When you click to select a safe
		$( document ).on( "click", "#safe_list ul li a", function() {
			$("#safe_list a").removeClass("active");
			$(this).addClass("active");
			$("a#use_this_safe").removeClass("inactive");
			safeName = $(this).attr('rel');
			return false;
		});
		
		// Open disclaimer on protect
		$(document).on("click", "a.protect2", function() {
			accountName = $(this).attr('rel');
			openDisclaimer();
			return false;
		});
		
		// Open disclaimer on protect
		$(document).on("click", "a#continue", function() {
			openChooseSafe();
			return false;
		});
		
		// Close disclaimer on click
		$(document).on("click", "a.closeDisclaimer", function() {
			accountName = '';
			closeDisclaimer();
			return false;
		});
		
		// Close disclaimer on click
		$(document).on("click", "a#closeChooseSafe", function() {
			closeChooseSafe();
			return false;
		});
		
		// Open protect account form
		$(document).on("click", "a#use_this_safe", function() {
			if ($(this).hasClass('inactive')) {
				
			}
			else {
				openProtectForm();
			}
			return false;
		});
		
		// Close protect account form
		$(document).on("click", "a#closeProtectForm", function() {
			closeProtectForm();
			return false;
		});
		
		// Click on acc picker
		$( document ).on( "click", "ul.acc_picker li a", function() {
			var rel = $(this).attr('rel');
			var id = $(this).parent().parent().attr('id');
			
			$(this).parent().siblings().children('a').removeClass("active");
			$(this).addClass('active');
			$("input[name="+id+"]").val(rel);
			
			return false;
		});
		
		$( document ).on( "click", "a#back_after_create", function() {
			$("#disclaimer").fadeOut(200);
			$("#choose_a_safe").fadeOut(200);
			$("#protect_account").fadeOut(200);

			$(".one-col").fadeTo(200, 1);
			$(".one-col").addClass("active");
			
			return false;
		});
		$( document ).on( "click", "a#back_after_error", function() {
			$("#formLoading").fadeOut();
			return false;
		});
		
		
		// ---------- PREVIOUS AND NEXT --------------
		$('a.nxt').click( function() {
			var type = $(this).attr('rel');
			if (type == 'safe') {
				if (safePage >= safePages) {
					// Do nothing
				} else {
					if (safePage == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					safePage = safePage + 1;
					loadChooseSafes();
					
					if (safePage == safePages) {
						$(this).addClass("inactive");
					}
				}
			}
			return false;
		});
		$('a.prv').click( function() {
			var type = $(this).attr('rel');
			if (type == 'safe') {
				if (safePage <= 1) {
					
				} else {
					if (safePage == safePages) { $(this).siblings('.nxt').removeClass('inactive'); }
					safePage = safePage - 1;
					loadChooseSafes();
					
					if (page == 1) {
						$(this).addClass("inactive");
					}
				}
			}
			return false;
		});
		
		$("#protectForm").submit( function() {
			var policy_length = $('input[name=policy_length]').val();
			var passwordType = $('input[name=passwordType]').val();
			var password = $('input[name=password]').val();
			
			var formHeight = $(this).height() + 5;
			$("#formLoading").css('height',formHeight);
			$("#formLoading").html('<img src="images/large_loader.gif" alt="" /><br />Protecting your account!<span>This should take about five seconds.</span>');
			$("#formLoading").fadeIn(700);
			
			$.ajax({
			   type: "POST",
			   url: 'account_handler.php',
			   data: { action: 'Protect', safeName: safeName, account_name: accountName, password: password, policy_length: policy_length, passwordType: passwordType },
			   dataType: 'json',
			   success: function(data)
			   {
					if ( data.status == 'Success') {
						$("#formLoading").html("<i class='fa fa-check-circle'></i><br />Your account has been protected!<span>Object created with name '"+data.objectName+"'</span><a href='#' class='formReset fButton' id='back_after_create'><i class='fa fa-chevron-circle-left'></i>Back to my accounts!</a>");
					}
					else if ( data.status == 'Failure') {
						var errors = '<ul>';
						for ( var i = 0; i < data.messages.length; i++ ) {
							errors += "<li>" + data.messages[i] + "</li>";
						}
						errors += "</ul>";
						
						$("#formLoading").html("<i class='fa fa-times-circle red'></i><br />There were errors with your request:<span class='errors'>"+ errors + "</span><a href='#' class='formGoBack fButton' id='back_after_error'><i class='fa fa-chevron-circle-left'></i>Go back and fix errors</a>");
					}
			   }
			 });
			 return false;
		});
	});
	
	function openProtectForm() {
	
		$("#disclaimer .shadow").fadeTo(400,0.8);
		
		var safeHeight = $("#choose_a_safe").height();
		$("#choose_a_safe .shadow").css({
			'height': safeHeight + 40
		});
		$("#choose_a_safe .shadow").fadeTo(400,0.5);	
		
		$("#formLoading").hide();
		
		$("#disclaimer").animate({
			'margin-left': '50px'
		}, 400);
		$("#choose_a_safe").animate({
			'margin-left': '100px'
		}, 400);
		
		$("input[name=password]").val('');
		$("input[name=password2]").val('');
		$("input[name=passwordType]").val('keep');
		$("input[name=policy_length]").val('7');
		$("ul.acc_picker li a").removeClass("active");
		$("ul#policy_length li a[rel=7]").addClass("active");
		$("ul#passwordType li a[rel=keep]").addClass("active");
		
		$(".one-col").fadeTo(400, .1);
		$(".one-col").removeClass("active");
		
		$("#protect_account").css({
			opacity: 0
		});
		$("#protect_account").show();
		$("#protect_account").animate({
			'opacity': 1,
			'margin-left': '200px'
		}, 400);
	}
	
	function closeProtectForm() {
	
		$("#disclaimer .shadow").fadeTo(400,0.5);
		$("#choose_a_safe .shadow").fadeOut();
		
		$("#disclaimer").animate({
			'margin-left': '100px'
		}, 400);
		
		$("#choose_a_safe").css({
			opacity: 0
		});
		$("#choose_a_safe").show();
		$("#choose_a_safe").animate({
			'opacity': 1,
			'margin-left': '200px'
		}, 400);
		
		$("#protect_account").animate({
			'opacity': 0,
			'margin-left': '400px'
		}, 200, function() {
			$("#protect_account").hide();
		});
	}
	
	function openChooseSafe() {
		safePage = 1;
		
		$("#disclaimer").animate({
			'margin-left': '100px'
		}, 400);
		
		var disclaimerHeight = $("#disclaimer").height();
		$("#disclaimer .shadow").css({
			'height': disclaimerHeight + 40
		});
		
		$("#disclaimer .shadow").fadeTo(400,0.5);	
		loadChooseSafes();
		
		$(".one-col").fadeTo(400, .1);
		$(".one-col").removeClass("active");
		
		$("#choose_a_safe").css({
			opacity: 0
		});
		$("#choose_a_safe").show();
		$("#choose_a_safe").animate({
			'opacity': 1,
			'margin-left': '200px'
		}, 400);
	}
	
	function openDisclaimer() {
		$(".one-col").fadeTo(400, .3);
		$(".one-col").removeClass("active");
		
		$("#disclaimer .shadow").hide();
		$("#choose_a_safe .shadow").hide();
		
		$("#disclaimer").css({
			opacity: 0
		});
		$("#disclaimer").show();
		
		$("#disclaimer").animate({
			'opacity': 1,
			'margin-left': '200px'
		}, 400);
	}
	function closeDisclaimer() {
		$(".one-col").fadeTo(200, 1);
		$(".one-col").addClass("active");
		
		$("#disclaimer").animate({
			'opacity': 0,
			'margin-left': '300px'
		}, 200, function() {
			$("#disclaimer").hide();
		});
	}
	function closeChooseSafe() {
		$(".one-col").fadeTo(400, .3);
		$(".one-col").removeClass("active");
		
		$("#disclaimer").css({
			opacity: 0
		});
		$("#disclaimer").show();
		
		$("#disclaimer .shadow").fadeOut();
		
		$("#disclaimer").animate({
			'opacity': 1,
			'margin-left': '200px'
		}, 400);
		
		$("#choose_a_safe").animate({
			'opacity': 0,
			'margin-left': '400px'
		}, 200, function() {
			$("#choose_a_safe").hide();
		});
	}
	
	
	function loadChooseSafes() {
		$.ajax({ 
			type: 'POST', 
			url: '../modules/my_safes.php', 
			data: { safe_query: safeQuery, page: safePage, limit: 4, access: 'limited' }, 
			dataType: 'json',
			success: function (data) { 		
				$("#safe_list ul").empty();
				
				ObjNewSafeName = "";
				$("a#use_this_safe").addClass("inactive");
				
				safePages = Math.ceil(data.num_results/4);
				
				// Make the previous button inactive if we need to
				if ( safePages == 1) { $(".prv[rel=safe]").addClass("inactive"); }
				if ( safePages <= 1 || safePage == safePages) { $(".nxt[rel=safe]").addClass("inactive"); } else { $(".nxt[rel=safe]").removeClass("inactive"); }
				
				if (data.num_results == 0) {
					$("#safe_pages .num_results").text(data.num_results + " safes");
					if (safeQuery == "") {
						$("#safe_list ul").append("<li class='none'>You don't have access to any safes.</li>");
					}
					else {
						$("#safe_list ul").append("<li class='none'>No matching safes.</li>");
					}		
					$("span.safePages").text(1);
					$("span.safePage").text(1);
				}
				else {
					$("#safe_pages .num_results").text(data.num_results + " safes");
					$("span.safePages").text(safePages);
					$("span.safePage").text(safePage);
					$.each(data.results, function(i, item) {		
						$("#safe_list ul").append("<li><a href='#' rel='"+item.name+"'><i class='fa fa-check-square'></i>"+ item.name +"</a></li>");
					});
					if (safeQuery.length > 0) {
						$("#safe_list ul li a").highlight(safeQuery);
					}
				}
			}
		});
	}
		
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
		
		<?php } else { ?>
		<div id="disclaimer" class="window cf">
			<div class="shadow"></div>
			<h2><i class="fa fa-lock"></i><span>Protect your account</span></h2>
			<p>
				If you choose to protect your account, CyberArk will take over control of your password and change it on a regular interval.
			</p>
			<p>
				Do you wish to continue?
			</p>
			<a href="#" class="button closeDisclaimer">No, go back</a>
			<a href="#" class="button right" id="continue">Yes, continue</a>
		</div>
		<div id="choose_a_safe" class="window cf">
			<div class="shadow"></div>
			<h2><i class="fa fa-list"></i><span>Choose a safe</span></h2>
			<p>
				To protect your account, you must first choose a CyberArk safe to store it in or <a href="create_safe.php">create a new safe</a>:
			</p>
			<div id="safe_list">
				<input type="text" name="safe_search" id="safe_search" class="inactive" value="start typing to search your safes..." />
				<ul>
	
				</ul>
			</div>
			<div class="pages_bar all" id="safe_pages">
				<div class="filters">
					<span class="num_results">0 objects</span>
				</div>
				<div class="page_num">Page <span class="safePage">1</span> of <span class="safePages">1</span></div><a href="#" class="prv inactive" rel="safe"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="safe"><i class='fa fa-chevron-right'></i></a>
			</div>
			
			<a href="#" class="button" id="closeChooseSafe">Go back</a>
			<a href="#" class="button right" id="use_this_safe">Use this safe</a>
		</div>
		<div id="protect_account" class="window cf">
			<h2><i class="fa fa-lock"></i><span>Protect your account</span></h2>
			<div id="formLoading"><img src="images/large_loader.gif" alt="" /><br />Adding your object!<span>This should take about ten seconds.</span></div>
			<form method="post" id="protectForm">
				<fieldset>
					<p>
						Verify that the information below is correct and fill in the current account password to protect your account! 
					</p>
					<input type="hidden" name="action" value="Protect" />
					<div class="passwords cf">
						<label>Password Change Interval Policy</label>
						<ul id="policy_length" class="acc_picker">
							<li><a href="#" class="active" rel="7"><i class="fa fa-check-square"></i>7 days</a></li>
							<li><a href="#" rel="30"><i class="fa fa-check-square"></i>30 days</a></li>
						</ul>
						<input type="hidden" name="policy_length" value="7" />
					
						<div>
							<label>Current password:</label>
							<input type="password" name="password" autocomplete="off" /> <span class="req">*</span>
						</div>
						<div>
							<label>Current password (confirm):</label>
							<input type="password" name="password2" autocomplete="off" /> <span class="req">*</span>
						</div>
						<span class="p_error">Your passwords do not match.  Please type them in again.</span>
						
						<label class="extra">Password type</label>
						<ul id="passwordType" class="acc_picker">
							<li><a href="#" class="active" rel="keep"><i class="fa fa-check-square"></i>Keep</a></li>
							<li><a href="#" rel="reconcile"><i class="fa fa-check-square"></i>Reconcile</a></li>
							<li><a href="#" rel="change"><i class="fa fa-check-square"></i>Change</a></li>
						</ul>
						<input type="hidden" name="passwordType" value="keep" />
					</div>
					
					<a href="#" class="button" id="closeProtectForm">Go back</a>
					<a href="#" class="button right inactive" id="protectButton">Protect your account!</a>
				</fieldset>
			</form>
		</div>
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
							<th></th>
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
									echo "<td>";
										if ($protected == "No") { echo "<a class='protect' rel='{$row['Account']}'><i class='fa fa-lock'></i>Protect (coming soon)</a>"; }
										else { echo "<span class='prot'>Protected</span>"; }
									echo "</td>";
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
							<!-- <th>Action</th> -->
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
									//echo "<td class='act'><a class='protect' href='#'><i class='fa fa-chevron-circle-right'></i>Contact</a></td>";
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