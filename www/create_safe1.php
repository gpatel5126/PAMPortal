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
		$stmt->bind_param("s", $user);
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
		FROM account_user_compliance_history
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
	
	if ($i != 0 && $j != 0) {
	
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
				$owned_compliant = $row['o_compliant'];
				$owned_uncompliant = $row['o_uncompliant'];
				$managed_compliant = $row['m_compliant'];
				$managed_uncompliant = $row['m_uncompliant'];
			}
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
	<script src="js/sparkline.js"></script>
	<script src="js/jquery.easypiechart.min.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">	
	
	<!-- Page specific files -->
	<script type="text/javascript" src="js/jquery.mockjax.js"></script>
    <script type="text/javascript" src="js/jquery.autocomplete.js"></script>
    <script type="text/javascript" src="js/create_safe.js"></script>
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
			<li><a href="my_accounts.php"><b></b><i class="fa fa-user"></i>My Accounts</a></li>
		</ul>
		
		<h3>Security Actions</h3>
		<ul>
			<li><a href="#" class="active"><b></b><i class="fa fa-lock"></i>Create/Manage Safes</a></li>
			<li><a href="create_account.php"><b></b><i class="fa fa-briefcase"></i>Add/Manage Objects</a></li>
			
			<li><a href="logout.php"><b></b><i class="fa fa-sign-out"></i>Logout</a></li>
		</ul>
	</nav>
</aside>
<h1 id="main"><i class="fa fa-lock"></i>Create/Manage Safes
<?php if ($priv) { ?>
<div id="choose"><form method="post" action="set_user.php">Active Directory UID: &nbsp;<input type="text" name="id" value="<?php echo $target; ?>" /><input type="submit" value="Go" /><a href="me.php" id="link_me">Back to me</a></form><span>(Available during beta testing only)</span></div>
<?php } ?>
</h1>
<div id="content">
	<div id="mydash">
		<div class="two-col cf">
			<div>
				<h2><i class="fa fa-lock"></i>Create a New Safe</h2>
				<div id="formLoading"><img src="images/large_loader.gif" alt="" /><br />Creating your safe!<span>This should take about ten seconds.</span></div>
				<form method="post" id="cs">
					<fieldset>
						<label>Safe nickname:</label>
						<input type="text" name="nickname" id="nickname" autocomplete="off" /> <span class="req">*</span>
						
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
						
						<label>Location of where a majority of the accounts are used:</label>
						<a href="#" class='where active' id='SJ'>San Jose</a><a href="#" class='where' id='DA'>Dallas</a><a href="#" class='where' id='DU'>Dublin</a><a href="#" class='where' id='NO'>Noida</a>
						<select id="geoSelect" name="geoSelect" style="display: none;">
							<option value="SJ" id='SJ'>San Jose</option>
							<option value="DU" id='DU'>Dublin</option>
							<option value="DA" id='DA'>Dallas</option>
							<option value="NO" id='NO'>Noida</option>
						</select>
						
						<label>Safe Environment:</label>
						<div class="envs">
							<span id="env_name">General environment</span> (<a href="#">change environment</a>)
							<div id="environments">
								<b></b>
								<a href="#" rel="GEN" class='active'><i class="fa fa-bullseye"></i>General Environment</a>
								<!-- <a href="#" rel="DMZ"><i class="fa fa-bullseye"></i>DMZ</a> -->
								<a href="#" rel="SOL"><i class="fa fa-bullseye"></i>Solstice/BAST</a>
								<a href="#" rel="CF"><i class="fa fa-bullseye"></i>Cold Fusion</a>
								<!-- <a href="#" rel="SEC"><i class="fa fa-bullseye"></i>Other Secured Zones</a> -->
								<!-- <a href="#" rel="VPC"><i class="fa fa-bullseye"></i>VPC</a> -->
							</div>
						</div>
						
						<input type="hidden" name="environment" id="env_input" value="GEN" />
						
						<label>Safe Description</label>
						<textarea name="description" class='desc'></textarea>
					</fieldset>
					
					<div id="safeNameCont">
						<label>Final safe name:</label>
						<div id="safeName">
							<span>safeName</span>
							<img src="images/loader.gif" alt="" id="safeLoader" />
						</div>
						
					</div>
					
					<div id="nameError"></div>
					
					<input type="submit" value="Create your safe!" disabled="disabled" />
				</form>
			</div>
			<div class="last">
				<h2><i class="fa fa-user"></i>Safes I own</h2>
				<table class="my_servers">
					<thead>
						<tr>
							<th>Safe Name</th>
							<th>Created</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody id="safe_owner">
					
					</tbody>
				</table>
				<div id="owned_loading" class='pages_loading'>
					<img src="images/loader.gif" alt="" />
				</div>
				<div class="pages_bar owned">
					<div class="page_num">Page <span id="owned_page">1</span> of <span id="owned_pages">4</span></div><a href="#" class="prv inactive" rel="owned"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="owned"><i class='fa fa-chevron-right'></i></a>
				</div>
			</div>
			<div class="last second">
				<h2><i class="fa fa-user"></i>Safes I have access to</h2>
				<table class="my_servers">
					<thead>
						<tr>
							<th>Safe Name</th>
							<th>Created</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody id="safe_access">
				
					</tbody>
				</table>
				<div id="access_loading" class='pages_loading'>
					<img src="images/loader.gif" alt="" />
				</div>
				<div class="pages_bar access">
					<div class="page_num">Page <span id="access_page">1</span> of <span id="access_pages">4</span></div><a href="#" class="prv inactive" rel="access"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="access"><i class='fa fa-chevron-right'></i></a>
				</div>
			</div>
		</div>
	</div>
	
</div>
</body>
</html>