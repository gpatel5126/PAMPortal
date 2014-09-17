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
		search_value = "";
		page = 1;
		pages = 1;
		limit = 25;
		prot = "all";
		first_time = true;
		roles = "all";
		
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
		
		$(".pages_bar .prot > a").click( function() {
			var rel = $(this).attr('rel');
			$(this).siblings('a').removeClass("active");
			$(this).addClass("active");
			prot = rel;
			loadServers(1);	
			return false;			
		});
		
		// System roles
		$(".pages_bar .roles > a").click( function() {
			$(this).siblings('ul').fadeToggle(300);
			$(this).toggleClass("active");
			return false;
		});
		
		// System roles
		$( document ).on( "click", ".pages_bar .roles > ul a", function() {
			var rel = $(this).attr("rel");
			if (rel == "all") {
				$(".pages_bar .roles > ul a").removeClass('active');
				$(".pages_bar .roles > a").html("All system roles<i class='fa fa-chevron-circle-down'></i>");
				$(this).addClass('active');
				$(".pages_bar .roles > select option").prop('selected', false);
				$(".pages_bar .roles > select option[value='all']").prop('selected', true);
			}
			else {
				$(".pages_bar .roles > select option[value='all']").prop('selected', false);
				$(".pages_bar .roles > ul a[rel=all]").removeClass('active');
				if ( $(this).hasClass('active') ) {
					$(this).removeClass('active');
					$(".pages_bar .roles > select option[value='"+rel+"']").prop('selected', false);
				}
				else {
					$(this).addClass('active');
					$(".pages_bar .roles > select option[value='"+rel+"']").prop('selected', true);
				}
				var how_many = $(".pages_bar .roles > ul a.active").length;
				if (how_many == 0) {
					$(".pages_bar .roles > ul a[rel=all]").click();
				}
				else {
					if (how_many == 1) { var how_many_s = ""; } else { how_many_s = "s"; }
					$(".pages_bar .roles > a").html(how_many + " system role"+ how_many_s +" selected<i class='fa fa-chevron-circle-down'></i>");
				}
			}
			
			roles = $(".pages_bar .roles > select").val().join(",");
			
			loadServers(1);
			return false;
		});
		
		// Hide roles list when we click outside roles box
		$(document).mouseup(function (e)
		{
			var container = $(".pages_bar .roles > ul");
			if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0) // ... nor a descendant of the container
			{
				$(".pages_bar .roles > a").removeClass("active");
				container.fadeOut();
			}
		});
		
		$('a.nxt').click( function() {
			if (page >= pages) {
				// Do nothing
			} else {
				if (page == 1) { $(this).siblings('.prv').removeClass('inactive'); }
				page = page + 1;
				loadServers(page);
				if (page == pages) {
					$(this).addClass("inactive");
				}
			}
			return false;
		});
		$('a.prv').click( function() {
			if (page <= 1) {
				
			} else {
				if (page == pages) { $(this).siblings('.nxt').removeClass('inactive'); }
				page = page - 1;
				loadServers(page);
				
				if (page == 1) {
					$(this).addClass("inactive");
				}
			}
			return false;
		});
		
		// Load the initial list of safes
		loadServers(1);
		
		$( document ).on( "keyup", "#searchServers", function() {
			search_value = $(this).val();
			loadServers(1);
			
		});
		
		$('#searchServers').on('blur', function(){
		   if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val('search your servers...'); }
		   
		}).on('focus', function(){
			$(this).removeClass('inactive');
			if ( $(this).val() == "search your servers..." ) { $(this).val(''); }
		});
		
		
		
	});
	function loadServers(current_page) {
		$.ajax({ 
			type: 'POST', 
			url: 'modules/my_servers.php', 
			data: { server_query: search_value, page: current_page, prot: prot, roles: roles }, 
			dataType: 'json',
			success: function (data) { 					
				$("table#my_servers tbody").empty();
				
				pages = Math.ceil(data.num_results/limit);
				page = current_page;
				
				if (data.num_results != 1) { var ess = "s"; } else { var ess = ""; }
				$(".num_results").text(data.num_results+" server"+ ess);
				
				// Make the previous button inactive if we need to
				if ( page == 1) { $(".prv").addClass("inactive"); }
				if ( pages <= 1 || page == pages) { $(".nxt").addClass("inactive"); } else { $(".nxt").removeClass("inactive"); }
				
				if (first_time == true) {
					$.each(data.roles, function(i, item) {
						$(".pages_bar .roles > ul").append("<li><a href='#' rel='"+item.role_name+"'><i class='fa fa-check-square'></i>"+item.role_name+"</a></li>");
						$(".pages_bar .roles > select").append("<option value='"+item.role_name+"'>"+item.role_name+"</option>");
					});
					first_time = false;
				}
				
				if (data.num_results == 0) {
					if (search_value == "" && prot == "all" && roles == "all") {
						$("table#my_servers tbody").append("<tr><td></td><td colspan='6'>You don't own any servers.</td></tr>");
					}
					else {
						$("table#my_servers tbody").append("<tr><td></td><td colspan='6'>No matching servers.</td></tr>");
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
						
						$("table#my_servers tbody").append("<tr class='"+status_class+"'><td class='status'><span></span></td><td class='search'>"+item.address+"</td><td>"+item.primary_contact+"</td><td>"+status_name+"</td><td>"+item.confidentiality+"</td><td>"+item.role+"</td><td><a class='transfer' href='#' rel='"+item.ci+"'><i class='fa fa-chevron-circle-right'></i>Transfer Ownership</a></td><td><a href='#' class='protect'><i class='fa fa-lock'></i>Protect</a></td></tr>");
						
						if (search_value.length > 0) {
							$("table#my_servers tbody tr td.search").highlight(search_value);
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
			<li><a href="my_servers.php" class="active"><b></b><i class="fa fa-desktop"></i>My Servers<?php if ($i != 0 && $j != 0) { echo "<span>{$total_uncompliant}</span>"; } ?></a></li>
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
<h1 id="main"><i class="fa fa-desktop"></i>My Servers
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
		<?php } else {?>
		<div class="notice">
			<i class="fa fa-exclamation-triangle"></i>Don't see your server listed?  See if it's one of the <a href="server_report.php?type=not_available"><?php echo $unclaimed; ?> unclaimed servers in the CMDB</a>!
		</div>
		<div class="one-col cf">
			<div>
				<h2><i class="fa fa-user"></i>Servers I own</h2>
				<div id="load_container">
					<div class="pages_bar" style="margin-bottom: 10px;">
						<div class="filters">
							<span class="num_results">300 results</span>
							<input type="text" name="search_query" id="searchServers" class="inactive" value="search your servers..." />
							<div class="prot">
								<a href="#" class="active" rel="all">All</a><a href="#" rel="protected">Protected</a><a href="#" rel="unprotected">Unprotected</a>
							</div>
							<div class="roles">
								<a href="#">All system roles<i class="fa fa-chevron-circle-down"></i></a>
								<ul class="roles_dd">
									<li><a href="#" rel="all" class="active"><i class="fa fa-check-square"></i>All</a></li>
								</ul>
								<select multiple name="roles[]">
									<option value="all">all</option>
								</select>
							</div>
						</div>
						<div class="page_num">Page <span class="page">0</span> of <span class="pages">0</span></div><a href="#" class="prv inactive" rel="search"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="search"><i class='fa fa-chevron-right'></i></a>
					</div>
					<table class="my_servers" id="my_servers">
						<thead>
							<tr>
								<th class="status"></th>
								<th>Server name</th>
								<th>Primary Contact</th>
								<th>Protected?</th>
								<th>Confidentiality</th>
								<th>System Role</th>
								<th class='action'>Transfer/Unclaim</th>
								<th class='protect'>Protect</th>
							</tr>
						</thead>
						<tbody>
						
						</tbody>
					</table>
				</div>
			</div>
			<div>
				<h2><i class="fa fa-users"></i>My team's servers</h2>
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
									//echo "<td class='act'><a class='protect' href='#'><i class='fa fa-chevron-circle-right'></i>Contact</a></td>";
								echo "</tr>";
								$users++;
							}
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