<?php
	require_once("includes/dbconn.php");
	require_once("includes/access.php");
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	$i = 0;
	$mysqli->real_query("
		SELECT FullName 
		FROM ad
		WHERE UID = '$target'
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$i++;
	}
	
	$j = 0;
	$mysqli->real_query("
		SELECT UID 
		FROM server_user_compliance_history
		WHERE UID = '$target'
		ORDER BY date DESC
		LIMIT 1
	");
	echo mysqli_error($mysqli);
	$res = $mysqli->use_result();
	while ($row = $res->fetch_assoc()) {
		$j++;
	}
	
	if ($i != 0 && $j != 0) {
	
		// Thresholds
		$yellow_threshold = 30;
		$green_threshold = 70;
		
		$mysqli->real_query("
			SELECT FullName 
			FROM ad
			WHERE UID = '$target'
		");
		echo mysqli_error($mysqli);
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			$fullName = $row['FullName'];
		}
		
		// Get the number of uncompliant servers
		$uncompliant_servers_array = array();
		
		$mysqli->real_query("
			SELECT * 
			FROM server_user_compliance_history
			WHERE UID = '$target'
			ORDER BY date DESC
			LIMIT 1
		");
		echo mysqli_error($mysqli);
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			$total_uncompliant = $row['o_uncompliant'] + $row['m_uncompliant'];
			array_push($uncompliant_servers_array, $total_uncompliant);
		}
		
		$uncompliant_servers_data = implode(",", $uncompliant_servers_array);
		
		$mysqli->real_query("
			SELECT * 
			FROM server_user_compliance_history
			WHERE UID = '$target'
			ORDER BY date DESC
			LIMIT 1
		");
		echo mysqli_error($mysqli);
		$res = $mysqli->use_result();
		while ($row = $res->fetch_assoc()) {
			$owned_compliant = $row['o_compliant'];
			$owned_uncompliant = $row['o_uncompliant'];
			$managed_compliant = $row['m_compliant'];
			$managed_uncompliant = $row['m_uncompliant'];
		}
		
		$total_owned = $owned_compliant + $owned_uncompliant;
		$total_managed = $managed_compliant + $managed_uncompliant;
		$total_servers = $total_owned + $total_managed;
		
		$total_uncompliant = $owned_uncompliant + $managed_uncompliant;
		
		if ($total_servers > 0) {
			$server_total_compliance = round(100*($owned_compliant + $managed_compliant)/($total_servers), 1);
		} else {
			$server_total_compliance = "N/A";
		}
		if ($owned_compliant + $owned_uncompliant > 0) {
			$server_owned_compliance = round(100*($owned_compliant)/($owned_compliant + $owned_uncompliant), 1);
		} else {
			$server_owned_compliance = "N/A";
		}
		if ($managed_compliant + $managed_uncompliant > 0) {
			$server_managed_compliance = round(100*($managed_compliant)/($managed_compliant + $managed_uncompliant), 1);
		} else {
			$server_managed_compliance = "N/A";
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
	<link rel="stylesheet" href="create_safe.css"/>
	<script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="js/sparkline.js"></script>
	<script src="js/jquery.easypiechart.min.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
	
	<!-- Add fancyBox main JS and CSS files -->
	<script type="text/javascript" src="source/jquery.fancybox.js?v=2.1.5"></script>
	<link rel="stylesheet" type="text/css" href="source/jquery.fancybox.css?v=2.1.5" media="screen" />
	
	<script type="text/javascript" src="js/jquery.mockjax.js"></script>
    <script type="text/javascript" src="js/jquery.autocomplete.js"></script>
	
	
	<script>
	$(function() {
	
		var geo = $('select#geoSelect').val();
		var nickname = '';
		var env_code = 'gen';
	
		$('.fancybox').fancybox({
			autoSize: false,
            autoDimensions: false,
			width: '1000px',
			height: '90%',
		
			preload   : true
		});
		
		
		$("form").bind("keypress", function (e) {
			if (e.keyCode == 13) {
				return false;
			}
		});
		
		$("a.who").click( function() {
			var optionPicked = $(this).attr('id');
			
			if ( $(this).hasClass('active') ) {
			
			} else {
				$('input[name="who"]').val(optionPicked);
				$('a.who').removeClass("active");
				$(this).addClass('active');
				
				if (optionPicked == 'just_me') {
					$('#members_fields').slideUp();
				}
				else {
					$('#members_fields').slideDown();
				}
			}
			return false;			
		});
		
		$( "#nickname" ).keyup(function() {			
			if (this.value.match(/[^a-zA-Z0-9 _]/g)) { 
				this.value = this.value.replace(/[^a-zA-Z0-9 _]/g, ''); 
			}
			else {
				nickname = $(this).val();
				updateSafeName(nickname, geo, env_code);
			}
		});
		
		$("a.where").click( function() {
			var geoToSelect = $(this).attr('id');
			$('a.where').removeClass("active");
			$(this).addClass('active');
			
			$('select#geoSelect').val(geoToSelect);
			
			geo = $('select#geoSelect').val();
			
			updateSafeName(nickname, geo, env_code);
			
			return false;			
		});
		
		$( document ).on( "click", "a.remove", function() {
			var idToRemove = $(this).attr('rel');
			idToRemove = idToRemove.replace('u_', '');
			
			$("#members table tbody tr#u_"+idToRemove).fadeOut().remove();
			$("#membersSelect option#u_"+idToRemove).remove();
			$("#controllersSelect option#c_"+idToRemove).remove();
			
			if ( $('#members table tbody').children(':visible').length == 0) {
				$('tr.initial').fadeIn();
			}
			
			return false;
		});
		
		$( document ).on( "click", "a.formGoBack", function() {
			$("#formLoading").fadeOut();
			
			return false;
		});
		
		// Open environments window
		$(".envs > a").click( function() {
			$('#environments').fadeIn().css("display","inline-block");
			return false;
		});
		
		// Select environment on click
		$("#environments a").click( function() {
			var env_code = $(this).attr('rel');
			var text = $(this).text();
			
			$("#env_name").text(text);
			$("#env_input").val(env_code);
			$("#environments a").removeClass('active');
			$(this).addClass('active');
			
			$("#environments").hide();
			
			updateSafeName(nickname, geo, env_code);
		});
		
		// Hide environments div when we click outside
		$(document).mouseup(function (e)
		{
			var container = $("#environments");
			if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0) // ... nor a descendant of the container
			{
				container.fadeOut();
			}
		});
		
		$( document ).on( "click", "a.formReset", function() {
			$("#formLoading").fadeOut();
			
			// Clear out the safe name
			$("#nickname").val('');
			$("#safeNameInput").val('');
			nickname = '';
			
			// Go back to just me
			$("form#cs input[name=who]").val('just_me');
			$(".who").removeClass('active');
			$(".who#just_me").addClass('active');
			
			// Clear the environment
			$("#env_input").val('gen');
			$("#environments a").removeClass(".active");
			$("#environments a:nth-child(1)").addClass(".active");
			$("#env_name").text("General Environment");
			
			// Clear out the selected members
			$('#membersSelect').find('option').remove().end();
			$('#members table tbody tr').not('.initial').remove();
			$('#members table tbody tr.initial').fadeIn();
			$('#members_fields').slideUp();
			$('#autocomplete').val('Search for an ActiveDirectory user');
			
			// Back to SJC
			$("select#geoSelect").val('sjc');
			$("a.where").removeClass("active");
			$("a.where#sjc").addClass("active");
			geo = $("#geoSelect").val();
			
			// Close the safe name field
			$("#safeNameCont").slideUp();
			
			return false;
		});
		
		$('#autocomplete').focus( function() {
			if ( $(this).val() == 'Search for an ActiveDirectory user') {
				$(this).val('');
			}
		});
		
		$( document ).on( "click", "a.m_type", function() {
			var idToRemove = $(this).attr('rel');
			idToRemove = idToRemove.replace('u_', '');
			
			if ( $(this).hasClass('active') ) {
				
			}
			else {
				$(this).parent().children('a').removeClass('active');
				$(this).addClass('active');
				
				// If we clicked on "controllers"
				if ( $(this).hasClass('controllers') ) {
					$("#membersSelect option#u_"+idToRemove).remove();
					$("#controllersSelect").append("<option id='c_"+idToRemove+"' value='"+idToRemove+"' selected>"+idToRemove+"</option>");
				}
				// If we clicked on "members"
				else {
					$("#controllersSelect option#c_"+idToRemove).remove();
					$("#membersSelect").append("<option id='u_"+idToRemove+"' value='"+idToRemove+"' selected>"+idToRemove+"</option>");
				}
				
				
			}
			
			
			return false;
		});
		
		$('#autocomplete').autocomplete({
			serviceUrl: '/autocomplete/uid.php',
			lookupLimit: 10,
			minChars: 2,
			//triggerSelectOnValidInput: false,
			onSelect: function (suggestion) {
				//alert('You selected: ' + suggestion.value + ', ' + suggestion.data);
				$(this).val('Search for an ActiveDirectory user');
				$(this).blur();
				
				if ( $('tr.initial').is(':visible') ) {
					$('tr.initial').hide();
				}
				
				$("#members table tbody").append($('<tr id="u_'+suggestion.data+'"><td>'+ suggestion.value +'</td><td>'+ suggestion.data +'</td><td><a href="#" rel="u_'+suggestion.data+'" class="active m_type members">Member</a><a href="#" rel="u_'+suggestion.data+'" class="m_type controllers">Controller</a></td><td><a href="#" rel="'+suggestion.data+'" class="remove"><i class="fa fa-times-circle red"></i></a></td></tr>').hide().fadeIn(500));
				
				$("#membersSelect").append("<option id='u_"+suggestion.data+"' value='"+suggestion.data+"' selected>"+suggestion.value+"</option>");
				
			}
		});
		
		
		/*
		$('form#cs').submit( function() {
			var formHeight = $(this).height() + 5;
			$("#formLoading").css('height',formHeight);
			$("#formLoading").html('<img src="images/large_loader.gif" alt="" /><br />Creating your safe!<span>This should take about ten seconds.</span>');
			$("#formLoading").fadeIn(700);
			
			$.ajax({
			   type: "POST",
			   url: 'add_safe.php',
			   data: $("form#cs").serialize(), // serializes the form's elements.
			   dataType: 'json',
			   success: function(data)
			   {
					alert(data);
					if ( data.status == 'Success') {
						$("#formLoading").html("<i class='fa fa-check-circle'></i><br />Your safe has been created!<span>Safe created with name '"+data.safeName+"'</span><a href='#' class='formReset fButton'><i class='fa fa-chevron-circle-right'></i>Create another safe!</a>");
					}
					else if ( data.status == 'Failure') {
						var errors = '<ul>';
						for ( var i = 0; i < data.messages.length; i++ ) {
							errors += "<li>" + data.messages[i] + "</li>";
						}
						errors += "</ul>";
						
						$("#formLoading").html("<i class='fa fa-times-circle red'></i><br />There were errors with your request:<span class='errors'>"+ errors + "</span><a href='#' class='formGoBack fButton'><i class='fa fa-chevron-circle-left'></i>Go back and fix errors</a>");
					}
			   }
			 });
			
			return false;
		});
		*/
		
	});
	
	function updateSafeName(nickname, geo, env) {
		var cleansed_nickname = nickname.replace(/ /g,"_");
		cleansed_nickname = cleansed_nickname.toLowerCase();
		
		var finalName = geo+"_"+cleansed_nickname+"_"+env;
		
		$('#safeName').text(finalName);
		$('#safeNameInput').val(cleansed_nickname);
		
		$("#safeNameCont").slideDown();
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
		</ul>
		<?php } ?>
		
		<h3>My Reports</h3>
		<ul>
			<li><a href="my_servers.php"><b></b><i class="fa fa-desktop"></i>My Servers<?php if ($i != 0 && $j != 0) { echo "<span>{$total_uncompliant}</span>"; } ?></a></li>
			<li><a href="my_accounts.php"><b></b><i class="fa fa-user"></i>My Accounts</a></li>
		</ul>
		
		<h3>Security Actions</h3>
		<ul>
			<li><a href="#" class="active"><b></b><i class="fa fa-lock"></i>Create/Manage Safes</a></li>
			<li><a href="#"><b></b><i class="fa fa-briefcase"></i>Add/Manage Objects</a></li>
			<li><a href="#"><b></b><i class="fa fa-briefcase"></i>Pending Approvals<span>7</span></a></li>
			<li><a href="logout.php"><b></b><i class="fa fa-sign-out"></i>Logout</a></li>
		</ul>
	</nav>
</aside>
<h1 id="main"><i class="fa fa-lock"></i>Create/Manage Safes</h1>
<?php if ($priv) { ?>
<div id="choose"><form method="post" action="set_user.php">Active Directory UID: &nbsp;<input type="text" name="id" value="<?php echo $target; ?>" /><input type="submit" value="Go" /><a href="me.php" id="link_me">Back to me</a></form><span>(Available during beta testing only)</span></div>
<?php } ?>
<div id="content">
	<div id="mydash">
		<div class="two-col cf">
			<div>
				<h2><i class="fa fa-lock"></i>Create a New Safe</h2>
				<div id="formLoading"><img src="images/large_loader.gif" alt="" /><br />Creating your safe!<span>This should take about ten seconds.</span></div>
				<form method="post" id="cs" action="add_safe.php">
					<fieldset>
						<label>Safe nickname:</label>
						<input type="text" name="nickname" id="nickname" />
						
						<input type="text" name="safeName" id="safeNameInput" style="display: none;" />
						
						<label>Who will use this safe?</label>
						<!-- Just me or team -->
						<a href="#" id="just_me" class='who active'><i class="fa fa-user"></i>Just Me</a><a href="#" id="multiple_users" class='who'><i class="fa fa-users"></i>Multiple users</a>
						
						<input type='hidden' name='who' value='just_me' />
						
						<div id="members_fields">
							<label>Safe members:</label>
							<div id="add_members">
								<label>Add a safe member:</label><input type="text" name="uid" id="autocomplete" value="Search for an ActiveDirectory user" />
							</div>
							<div id="members">
								<table>
									<thead>
										<tr>
											<th>Full Name</th>
											<th>UID</th>
											<th>User type</th>
											<th class='removeth'></th>
										</tr>
									</thead>
									<tbody>
										<tr class='initial'>
											<td colspan='3'>No members selected yet</td>
										</tr>
									</tbody>
								</table>
							</div>
							
							<select multiple name="members[]" id="membersSelect" style="display: none;">
							  
							</select>
							
							<select multiple name="controllers[]" id="controllersSelect" style="display: none;">
							  
							</select>
						</div>
						
						<label>Geo/Location:</label>
						<a href="#" class='where active' id='sj'>San Jose</a><a href="#" class='where' id='da'>Dallas</a><a href="#" class='where' id='du'>Dublin</a><a href="#" class='where' id='ut'>Lehi</a><a href="#" class='where' id='no'>Noida</a>
						<select id="geoSelect" name="geoSelect" style="display: none;">
							<option value="sj" id='sj'>San Jose</option>
							<option value="du" id='du'>Dublin</option>
							<option value="da" id='da'>Dallas</option>
							<option value="no" id='no'>Noida</option>
							<option value="ut" id='ut'>Lehi</option>
						</select>
						
						<label>Safe Environment:</label>
						<div class="envs">
							<span id="env_name">General environment</span> (<a href="#">change environment</a>)
							<div id="environments">
								<b></b>
								<a href="#" rel="gen" class='active'><i class="fa fa-bullseye"></i>General Environment</a>
								<a href="#" rel="dmz"><i class="fa fa-bullseye"></i>DMZ</a>
								<a href="#" rel="sol"><i class="fa fa-bullseye"></i>Solstice/BAST</a>
								<a href="#" rel="cf"><i class="fa fa-bullseye"></i>Cold Fusion</a>
								<a href="#" rel="sec"><i class="fa fa-bullseye"></i>Other Secured Zones</a>
								<a href="#" rel="vpc"><i class="fa fa-bullseye"></i>VPC</a>
							</div>
						</div>
						
						<input type="hidden" name="environment" id="env_input" value="gen" />
						
						<label>Safe Description</label>
						<textarea name="description" class='desc'></textarea>
					</fieldset>
					
					<div id="safeNameCont">
						<label>Proposed safe name:</label>
						<div id="safeName">
							safeName
						</div>
					</div>
					
					<input type="submit" value="Create your safe!" />
				</form>
			</div>
			<div class="last">
				<h2><i class="fa fa-user"></i>Safes I own</h2>
				Safe list coming soon.
			</div>
			<div class="last second">
				<h2><i class="fa fa-user"></i>Safes I have access to</h2>
				Safe list coming soon.
			</div>
		</div>
	</div>
	
</div>
</body>
</html>