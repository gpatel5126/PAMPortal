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
	<link rel="stylesheet" href="protect_account.css"/>
	<script src="js/jquery.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="js/sparkline.js"></script>
	<script src="js/highlight.js"></script>
	<script src="js/feedback.js"></script>
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
		
		search_value = "";
		page = 1;
		pages = 1;
		limit = 25;
		prot = "all";
		
		// Safe listing pages
		safePage = 1;
		safePages = 1;
		
		whatUsage = "";
		safeName = "";
		
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
		
		// Password input
		$(document).on("keyup paste", "input[name=currentPassword]", function() {
			var passwordVal = $(this).val();
			if ( passwordVal != "" ) { $("#continue").removeClass("inactive"); }
			else { $("#continue").addClass("inactive"); }
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
			if ( $(this).hasClass("active") ) {
				$(this).removeClass("active");
				safeName = "";
			}
			else {
				$("#safe_list a").removeClass("active");
				$(this).addClass("active");
				safeName = $(this).attr('rel');
				if (safeName != "" && whatUsage != "") {
					$("a#use_this_safe").removeClass("inactive");
				}
			}
			return false;
		});
		
		// When you click to select a safe
		$( document ).on( "click", "#what_usage ul li a", function() {
			if ( $(this).hasClass("active") ) {
				$(this).removeClass("active");
				whatUsage = "";
			}
			else {
				$("#what_usage a").removeClass("active");
				$(this).addClass("active");
				whatUsage = $(this).attr('rel');
				if (safeName != "" && whatUsage != "") {
					$("a#use_this_safe").removeClass("inactive");
				}
			}
			
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
			if ( $(this).hasClass("inactive") ) { }
			else {
				$("#formLoading").hide();
				
				$("#currentPasswordLoader").show();
				var currentPassword = $("input[name=currentPassword]").val();
				// Authenticate password
				$.ajax({
				   type: "POST",
				   url: 'modules/verify_account.php',
				   data: { accountName: accountName, password: currentPassword },
				   dataType: 'json',
				   success: function(data)
				   {
						if ( data.status == 'Success') {
							// If successful, open the next screen
							openChooseSafe();
							$("div#currentPasswordError").hide();
							$("#currentPasswordLoader").hide();
						}
						else if ( data.status == 'Failure') {
							// If failure, show error							
							$("div#currentPasswordError").show();
							$("#currentPasswordLoader").hide();
						}
				   }
				 });
			}
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
		
		
		$( document ).on( "click", "a#back_after_create", function() {
			$("#disclaimer").fadeOut(200);
			$("#choose_a_safe").fadeOut(200);
			
			loadAccounts(1);

			$(".one-col").fadeTo(200, 1);
			$(".one-col").addClass("active");
			
			return false;
		});
		$( document ).on( "click", "a#back_after_error", function() {
			$("#formLoading").fadeOut();
			return false;
		});
		
		$(".pages_bar .prot > a").click( function() {
			var rel = $(this).attr('rel');
			$(this).siblings('a').removeClass("active");
			$(this).addClass("active");
			prot = rel;
			loadAccounts(1);
			return false;	
		});
		
		
		// ---------- PREVIOUS AND NEXT --------------
		$('a.nxt').click( function() {
			var type = $(this).attr('rel');
			if (type == 'safe') {
				if (safePage >= safePages) {
					// Do nothing
				} else {
					safeName = "";
					if (safePage == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					safePage = safePage + 1;
					loadChooseSafes();
					
					if (safePage == safePages) {
						$(this).addClass("inactive");
					}
				}
			}
			else if (type == 'search') {
				if (page >= pages) {
					// Do nothing
				} else {
					if (page == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					page = page + 1;
					loadAccounts(page);
					if (page == pages) {
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
			else if (type == "search") {
				if (page <= 1) {
					
				} else {
					if (page == pages) { $(this).siblings('.nxt').removeClass('inactive'); }
					page = page - 1;
					loadAccounts(page);
					
					if (page == 1) {
						$(this).addClass("inactive");
					}
				}
			}
			return false;
		});
		
		$( document ).on( "click", "a.update_switcher", function() {
			var policyType = $(this).attr("id");
			var relatedAccount = $(this).attr("rel");
			var thisAccount = $(this);
			
			thisAccount.siblings("span.result").html("<img src='images/tiny_loader.gif' alt='' />").css("display","inline-block").show();
			thisAccount.siblings("span.error").hide();
			
			$.ajax({
			   type: "POST",
			   url: 'modules/update_policy.php',
			   data: { new_policy: policyType, relatedAccount: relatedAccount },
			   dataType: 'json',
			   success: function(data)
			   {
					//thisAccount.siblings(".tiny_loader").hide();
					
					if ( data.status == 'Success') {
						thisAccount.siblings("span.result").html("<i class='fa fa-check green'></i>");
						thisAccount.siblings("a.update_switcher").removeClass("active");
						thisAccount.addClass("active");
					}
					else if ( data.status == 'Failure') {
						thisAccount.siblings("span.result").html("<i class='fa fa-times red'></i>");
						thisAccount.siblings("span.error").html(data.message).css("display","block").show();
					}
			   }
			 });
			
			return false;
		});
		
		
		$("#use_this_safe").click( function() {
			var password = $('input[name=currentPassword]').val();
			
			var formHeight = $("#protectFull").height() + 5;
			$("#formLoading").css('height',formHeight);
			$("#formLoading").html('<img src="images/large_loader.gif" alt="" /><br />Protecting your account!<span>This should take about five seconds.</span>');
			$("#formLoading").fadeIn(700);
			
			$.ajax({
			   type: "POST",
			   url: 'account_handler1.php',//added a 1 to break the process
			   data: { action: 'Protect', safeName: safeName, account_name: accountName, password: password, policyType: whatUsage },
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
		
		
		loadAccounts(1);
		
		$( document ).on( "keyup", "#searchServers", function() {
			search_value = $(this).val();
			loadAccounts(1);
		});
		
		$('#searchServers').on('blur', function(){
		   if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val('search your accounts...'); }
		   
		}).on('focus', function(){
			$(this).removeClass('inactive');
			if ( $(this).val() == "search your accounts..." ) { $(this).val(''); }
		});
		
		
		// ------ Password help box --------
		$( document ).on( "click", "#password_help", function() {
			$("#help_window").fadeToggle();
			return false;
		});
		$( document ).on( "click", "#help_window .exit", function() {
			$("#help_window").fadeOut();
			return false;
		});
		// ------ Password help box 2--------
		$( document ).on( "click", "#password_help2", function() {
			$("#help_window2").fadeToggle();
			return false;
		});
		$( document ).on( "click", "#help_window2 .exit", function() {
			$("#help_window2").fadeOut();
			return false;
		});
	});
	
	function openChooseSafe() {
		safePage = 1;
		
		whatUsage = "";
		$("#what_usage a").removeClass("active");
		safeName = "";
		
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
		$("input[name=currentPassword]").val("");
		$("#continue").addClass("inactive");
		$("div#currentPasswordError").hide();
		
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
			data: { safe_query: safeQuery, page: safePage, limit: 4 }, 
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
	
	function loadAccounts(current_page) {
		$.ajax({ 
			type: 'POST', 
			url: 'modules/my_accounts.php', 
			data: { query: search_value, page: current_page, prot: prot }, 
			dataType: 'json',
			success: function (data) { 					
				$("table#my_accounts tbody").empty();
				
				pages = Math.ceil(data.num_results/limit);
				page = current_page;
				
				if (data.num_results != 1) { var ess = "s"; } else { var ess = ""; }
				$(".num_results").text(data.num_results+" account"+ ess);
				
				// Make the previous button inactive if we need to
				if ( page == 1) { $(".prv").addClass("inactive"); }
				if ( pages <= 1 || page == pages) { $(".nxt").addClass("inactive"); } else { $(".nxt").removeClass("inactive"); }
				
				if (data.num_results == 0) {
					if (search_value == "" && prot == "all") {
						$("table#my_accounts tbody").append("<tr><td></td><td colspan='6'>You don't own any accounts.</td></tr>");
					}
					else {
						$("table#my_accounts tbody").append("<tr><td></td><td colspan='6'>No matching accounts.</td></tr>");
					}
				}
				else {
					$("#temp_container").hide();
					$("#results_container").slideDown(300);
					$(".pages").text(pages);
					$(".page").text(page);
					
					$.each(data.results, function(i, item) {
						if (item.implemented == "y") {
							var status_class = "pro";
							var status_name = "Yes";
						}
						else {
							var status_class = "un";
							var status_name = "No";
						}
						
						var manual_active = "";
						var auto_active = "";
						
						if (item.policy_status == "Manual") { var manual_active = " active"; }
						else { var auto_active = " active"; }
						
						lineItem = "<tr class='"+status_class+"'><td class='status'><span></span></td><td class='search'>"+item.account+"</td><td>"+item.displayName+"</td><td>"+status_name+"</td><td><a target='_blank' href='https://amp.corp.adobe.com/'><i class='fa fa-chevron-circle-right'></i>Manage in AMP</a></td>";
						if (item.implemented == "n") {
							lineItem += "<td class='actions'><a class='protect2' rel='"+item.account+"'><i class='fa fa-lock'></i>Protect</a></td>";
						}
						else {
							lineItem += "<td class='actions'><a href='#' class='update_switcher"+auto_active+"' id='auto' rel='"+item.account+"'>Automatic</a><a href='#'class='update_switcher"+manual_active+"' id='manual' rel='"+item.account+"'>Manual</a><span class='result'><img src='images/tiny_loader.gif' alt='' /></span><span class='error'></span></td>";
						}
						lineItem += "</tr>";
						
						$("table#my_accounts tbody").append(lineItem);
						
						if (search_value.length > 0) {
							$("table#my_accounts tbody tr td.search").highlight(search_value);
						}
					});
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
		<?php } else if ($j == 0 && $accounts == 0) { ?>
		<div id="welcome">
			<h2>This user doesn't own or manage any servers.</h2>
		</div>
		
		<?php } else { ?>
		<div id="disclaimer" class="window cf">
			<div class="shadow"></div>
			<h2><i class="fa fa-lock"></i><span>Protect your account</span></h2>
			<p>
				If you choose to protect your account, CyberArk will store the password and typically take control over it and change it on a regular interval.
			</p>
			<p>
				If you wish to continue, enter the current account password to verify ownership.
			</p>
			<div id="currentPasswordCont">
				<label id="currentPasswordLabel">Current Password:</label><input type="password" name="currentPassword" id="currentPassword" /><img src="images/loader.gif" alt="" id="currentPasswordLoader" />
			</div>
			<div id="currentPasswordError">
				The password is incorrect.
			</div>
			<a href="#" class="button closeDisclaimer">Go back</a>
			<a href="#" class="button right inactive" id="continue">Authenticate account</a>
		</div>
		<div id="choose_a_safe" class="window cf">
			<div id="formLoading"><img src="images/large_loader.gif" alt="" /><br />Adding your object!<span>This should take about ten seconds.</span></div>
			<h2><i class="fa fa-list"></i><span>Choose a safe and account usage type</span></h2>
			<div id="protectFull" class="cf">
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
						<span class="num_results">0 safes</span>
					</div>
					<div class="page_num">Page <span class="safePage">1</span> of <span class="safePages">1</span></div><a href="#" class="prv inactive" rel="safe"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="safe"><i class='fa fa-chevron-right'></i></a>
				</div>
				<!--help for new p1 feature
				<table class="my_servers" id="my_accounts">
					<thead>
						<tr>
							<th class="status"></th>
							<th>How is this account's Password used?</th>
							<th style="width: 260px;"><i class="fa fa-question-circle"></i></th>
						</tr>
					</thead>
				</table>-->
							
	
					<div id="what_usage">
					<p>How is this account's password used? &nbsp;<i class="fa fa-question-circle" id="password_help2"></i></p>
					<ul>
						<li><a href="#" rel="manual"><i class="fa fa-check-square"></i><span>Application/Script Account - Password to be changed manually</span></a></li>
						<li><a href="#" rel="automatic"><i class="fa fa-check-square"></i><span>Interactive Account - Password to be changed automatically</span></a></li>
					</ul>
				</div>
				<div id="help_window2">
						<a href="#" class="exit"><i class="fa fa-times-circle"></i></a>
						<strong><b>App/Script Account vs. Interactive Account</br></strong>
						<p><u>Application/Script Account</u> - Accounts used in applications, scripts, or other systems in which an automatic change of password would lead to an outage or disruption in service. Password changes need to be made manually in a coordinated effort to update the applications/scripts with the new password at the same time it changes in the domain. You will receive email notifications reminding you to update these passwords every 90 days. These accounts should eventually migrate to the CyberArk AIM (Application Identity Manager).<br><u>Interactive Account</u> - Accounts that someone will use interactively to access a system or an application. If the password is automatically updated at regular intervals, it will not result in a service down-time or other outage. CyberArk will change these passwords at least once every 7 days. Those with access can retrieve use or retrieve the password via CyberArk.</p>
					</div>
				
				<a href="#" class="button" id="closeChooseSafe">Go back</a>
				<a href="#" class="button right" id="use_this_safe">Protect this account!</a>
			</div>
		</div>
		<div class="one-col cf">
			<div>
				<a href="csv/account_export.php" id="csv_download"><i class="fa fa-file-excel-o"></i>Download an account export (CSV)</a>
				<h2><i class="fa fa-user"></i>Accounts I own</h2>
				<div id="load_container">
					<div class="pages_bar" style="margin-bottom: 10px;">
						<div class="filters">
							<span class="num_results">0 results</span>
							<input type="text" name="search_query" id="searchServers" class="inactive" value="search your accounts..." />
							<div class="prot">
								<a href="#" class="active" rel="all">All</a><a href="#" rel="protected">Protected</a><a href="#" rel="unprotected">Unprotected</a>
							</div>
						</div>
						<div class="page_num">Page <span class="page">0</span> of <span class="pages">0</span></div><a href="#" class="prv inactive" rel="search"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="search"><i class='fa fa-chevron-right'></i></a>
					</div>
					
					<div id="help_window">
						<a href="#" class="exit"><i class="fa fa-times-circle"></i></a>
						<strong>Automatic vs. Manual</strong>
						<p><u>Automatic</u> - CyberArk will change these passwords at least once every 7 days.<br><u>Manual</u> - CyberArk will not change the passwords, you will receive email notifications reminding you to update these passwords every 90 days.</p>
					</div>
					<table class="my_servers" id="my_accounts">
						<thead>
							<tr>
								<th class="status"></th>
								<th>Account</th>
								<th>Display Name</th>
								<th>Protected?</th>
								<th>Manage</th>
								<th style="width: 260px;">Password Management / Protect &nbsp; <i class="fa fa-question-circle" id="password_help"></i></th>
							</tr>
						</thead>
						<tbody>
						
						</tbody>
					</table>
				
				</div>
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