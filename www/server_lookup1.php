<?php
	require_once("includes/dbconn.php");
	require_once("includes/access.php");
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	$j = 0;
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
	
	require_once('includes/getStats.php');
	
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
	<link rel="stylesheet" href="create_safe.css"/>
	<script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
	<script src="js/feedback.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="js/highlight.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
	
	<!-- Add fancyBox main JS and CSS files -->
	<script type="text/javascript" src="source/jquery.fancybox.js?v=2.1.5"></script>
	<link rel="stylesheet" type="text/css" href="source/jquery.fancybox.css?v=2.1.5" media="screen" />
	
	
	<script>
	$(function() {
		page = 0;
		pages = 0;
		limit = 25;
		$('.fancybox').fancybox({
			autoSize: false,
            autoDimensions: false,
			width: '1000px',
			height: '90%',
			preload   : true
		});
		
		$('#search input').on('blur', function(){
		   if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val('start typing a server name to search...'); }
		   
		}).on('focus', function(){
			$(this).removeClass('inactive');
			if ( $(this).val() == "start typing a server name to search..." ) { $(this).val(''); }
		});
		
		$( document ).on( "keyup", "#search input", function() {
			search_value = $(this).val();
			var $a = $(this);
			
			if (search_value.length <= 1) {
				$("#results_container").fadeOut();
				if (search_value.length == 0) {
					var message = "";
					$("#temp_container").hide();
				}
				else {
					var message = "Enter 2 or more characters to start your search.";
					$("#temp_container").text(message).fadeIn();
				}
			}
			
			if (search_value.length >= 2) {
				$.ajax({ 
					type: 'POST', 
					url: 'modules/search_servers.php', 
					data: { server_query: search_value }, 
					dataType: 'json',
					success: function (data) { 					
						$("table#results tbody").empty();
						
						pages = Math.ceil(data.num_results/limit);
						page = 1;
						$(".num_results").text(data.num_results+" results");
						$(".prv").addClass("inactive");
						
						if (data.num_results == 0) {
							var message = "No search results.";
							$("#results_container").fadeOut();
							$("#temp_container").text(message).fadeIn();
							
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
								
								
								if (pages == 1) { $(".nxt").addClass("inactive"); } else { $(".nxt").removeClass("inactive"); }
								$("table#results tbody").append("<tr class='"+status_class+"'><td class='status'><span></span></td><td class='search'>"+item.address+"</td><td>"+status_name+"</td><td>"+item.owner+"</td><td>"+item.primary_contact+"</td><td>"+item.confidentiality+"</td><td>"+item.role+"</td></tr>");
								$("table#results tbody tr td.search").highlight(search_value);
							});
						}
					}
				});
			}
			return false;
		});
		
		$('a.nxt').click( function() {
			var type = $(this).attr('rel');
			if (type == 'search') {
				if (page >= pages) {
					// Do nothing
				} else {
					if (page == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					page = page + 1;
					changePage(page);
					if (page == pages) {
						$(this).addClass("inactive");
					}
				}
			}
			return false;
		});
		$('a.prv').click( function() {
			var type = $(this).attr('rel');
			if (type == 'search') {
				if (page <= 1) {
					
				} else {
					if (page == pages) { $(this).siblings('.nxt').removeClass('inactive'); }
					page = page - 1;
					changePage(page);
					
					if (page == 1) {
						$(this).addClass("inactive");
					}
				}
			}
			return false;
		});
	});
	function changePage(new_page) {
		
		$.ajax({ 
			type: 'POST', 
			url: 'modules/search_servers.php', 
			data: { server_query: search_value, page: new_page }, 
			dataType: 'json',
			success: function (data) { 					
				$("table#results tbody").empty();
				
				pages = Math.ceil(data.num_results/limit);
				
				$.each(data.results, function(i, item) {
					if (item.implemented == "y") {
						var status_class = "pro";
						var status_name = "Yes";
					}
					else {
						var status_class = "un";
						var status_name = "No";
					}
					
					$(".pages").text(pages);
					$(".page").text(page);
					$("table#results tbody").append("<tr class='"+status_class+"'><td class='status'><span></span></td><td class='search'>"+item.address+"</td><td>"+status_name+"</td><td>"+item.owner+"</td><td>"+item.primary_contact+"</td><td>"+item.confidentiality+"</td><td>"+item.role+"</td></tr>");
					$("table#results tbody tr td.search").highlight(search_value);
				});
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
			<li><a href="server_lookup.php" class="active"><b></b><i class="fa fa-search"></i>Server Lookup &amp; Reports</a></li>
			<?php if ($core) { ?><li><a href="login_logs.php"><b></b><i class="fa fa-file-text"></i>Log of User Logins</a></li> <?php } ?>
		</ul>
		<?php } ?>
		
		<h3>My Reports</h3>
		<ul>
			<li><a href="my_servers.php"><b></b><i class="fa fa-desktop"></i>My Servers<?php echo "<span>{$total_uncompliant}</span>"; ?></a></li>
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
<h1 id="main"><i class="fa fa-search"></i>Server Lookup
<?php if ($priv) { ?>
<div id="choose"><form method="post" action="set_user.php">Active Directory UID: &nbsp;<input type="text" name="id" value="<?php echo $target; ?>" /><input type="submit" value="Go" /><a href="me.php" id="link_me">Back to me</a></form><span>(Available during beta testing only)</span></div>
<?php } ?>
</h1>
<div id="content">
	<div id="mydash">
		<!--
		<div class="notice">
			<i class="fa fa-exclamation-triangle"></i>Don't see your server listed?  See if it's one of the <a href="server_report.php?type=not_available"><?php echo $unclaimed; ?> unclaimed servers in the CMDB</a>!
		</div>
		-->
		<div class="one-col cf">
			<div>
				<h2><i class="fa fa-search"></i>Server Lookup</h2>
				<div id="search">
					<input type="text" name="search" autocomplete="off" class="inactive" value="start typing a server name to search..." />
				</div>
				<div id="temp_container">
					
				</div>
				<div id="results_container" style="display: none;">
					<div class="pages_bar" style="margin-bottom: 10px;">
						<span class="num_results">300 results</span>
						<div class="page_num">Page <span class="page">0</span> of <span class="pages">0</span></div><a href="#" class="prv inactive" rel="search"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="search"><i class='fa fa-chevron-right'></i></a>
					</div>
					<table class="my_servers" id="results">
						<thead>
							<tr>
								<th class="status"></th>
								<th>Server name</th>
								<th>Protected?</th>
								<th>Owner Name</th>
								<th>Primary Contact</th>
								<th>Confidentiality</th>
								<th>System Role</th>
							</tr>
						</thead>
						<tbody>
							
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	
</div>
</body>
</html>