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
	<title>PAM Portal</title>
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
	<script src="js/highlight.js"></script>
	<script src="js/jquery.easypiechart.min.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">	
	
	<!-- Page specific files -->
	<script type="text/javascript" src="js/jquery.mockjax.js"></script>
    <script type="text/javascript" src="js/jquery.autocomplete.js"></script>
    <script type="text/javascript" src="js/manage_accounts.js"></script>
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
			<?php if ($core) { ?><li><a href="acct_policy_updates.php"><b></b><i class="fa fa-file-text"></i>Account Policy Updates</a></li> <?php } ?>
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
			<li><a href="create_account.php"class="active"><b></b><i class="fa fa-briefcase"></i>Add/Manage Accounts</a></li>
			<!--<li><a href="#"><b></b><i class="fa fa-briefcase"></i>Pending Approvals<span>7</span></a></li>-->
			<li><a href="logout.php"><b></b><i class="fa fa-sign-out"></i>Logout</a></li>
		</ul>
	</nav>
</aside>
<h1 id="main"><i class="fa fa-lock"></i>Add/Manage Objects
<?php if ($priv) { ?>
<div id="choose"><form method="post" action="set_user.php">Active Directory UID: &nbsp;<input type="text" name="id" value="<?php echo $target; ?>" /><input type="submit" value="Go" /><a href="me.php" id="link_me">Back to me</a></form><span>(Available during beta testing only)</span></div>
<?php } ?>
</h1>
<div id="content">
	<div id="mydash">
		<div class="accounts cf">
			<div id="createObject">
				<a href="#" class="closeCreate"><i class="fa fa-times-circle"></i></a>
				<h2><i class="fa fa-archive"></i><span>Create a new object</span></h2>
				<div id="formLoading" class="obj"><img src="images/large_loader.gif" alt="" /><br />Adding your object!<span>This should take about ten seconds.</span></div>
				<form method="post" id="cs">
					<fieldset>
						<label>Safe to store object in:</label>
						<span id="safe_to_store"></span><br />
						<input type="hidden" name="safeName" />
						<div class="fresh">
							<input type="text" name="safe" id="safe_input" autocomplete="off" /> <span class="req">*</span>
						</div>
						
						<label>What type of account is this?</label>
						<!-- Just me or team -->
						<a href="#" class='who active' rel="os"><i class="fa fa-windows"></i>Operating System</a><a href="#" class='who' rel="db"><i class="fa fa-database"></i>Database</a>
						
						<input type="hidden" name="account_type" value="os" />
						
						<!-- operating system -->
						<div class="os">
							<label>Which operating system?</label>
							<ul id="os_type" class="acc_picker">
								<li><a href="#" class="active" rel="windows"><i class="fa fa-check-square"></i>Windows</a></li>
								<li><a href="#" rel="unix"><i class="fa fa-check-square"></i>Unix/Linux/Mac (SSH)</a></li>
							</ul>
							<input type="hidden" name="os_type" value="windows" />
						</div>
						<div class="db">
							<label>What type of database?</label>
							<ul id="database_type" class="acc_picker">
								<li><a href="#" class="active" rel="mssql"><i class="fa fa-check-square"></i>MSSQL</a></li>
								<li><a href="#" rel="mysql"><i class="fa fa-check-square"></i>MySQL</a></li>
								<li><a href="#" rel="oracle"><i class="fa fa-check-square"></i>Oracle</a></li>
							</ul>
							<input type="hidden" name="database_type" value="mssql" />
						</div>
												
						<label>Account name:</label>
						<input type="text" name="account_name" id="account_input" autocomplete="off" /> <span class="req">*</span>						
		
						
						<div class="os">
							<label>Server Information</label>
							<div class="grey cf">
								<div style="float: left; width: 100%">
									<input type="text" name="os_server_name" class="inactive" id="os_server_name" value="Server name" autocomplete="off" /> <span class="corp">.corp.adobe.com</span><span class="req">*</span>
									<a href="#" id="not_corp">Not .corp.adobe.com?</a>
								</div>
							</div>
							<input type="hidden" name="not_corp" value="false" />
						</div>
						
						<div class="db">
							<label>Database Information</label>
							<div class="grey cf">
								<div style="float: left; width: 100%">
									<input type="text" name="db_server_name" id="db_server_name" class="inactive" value="Database server name" autocomplete="off" /> <span class="corp">.corp.adobe.com</span><span class="req">*</span>
									<a href="#" id="not_corp">Not .corp.adobe.com?</a>
								</div>
								<div>
									<input type="text" name="port" value="Port #" class="inactive" autocomplete="off" /> <span class="req">*</span>
								</div>
								<div>
									<input type="text" name="databaseName" class="inactive" value="Database name" autocomplete="off" /> <span class="req">*</span>
								</div>
							</div>
						</div>
						
						<label>Password Change Interval Policy</label>
						<ul id="policy_length" class="acc_picker">
							<li><a href="#" class="active" rel="7"><i class="fa fa-check-square"></i>7 days</a></li>
							<li><a href="#" rel="30"><i class="fa fa-check-square"></i>30 days</a></li>
						</ul>
						<input type="hidden" name="policy_length" value="7" />
						
						<div class="passwords cf">
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
						
						<div id="formError">
							Please fill out all the required fields before submitting.
						</div>
						
						<input type="hidden" name="action" value="Add" />
						
						<input type="submit" value="Create your object!" disabled="disabled" />
						
					</fieldset>
				</form>
			</div>
			<div id="object">
				<!-- Delete div -->
				<div id="delete_div">
					<h3>Are you sure?</h3>
					<div id="delete_buttons">
						<a href="#" class="no">No, go back</a><a href="#" id="delete_final">Yes, delete</a>
					</div>
					<div id="delete_spinner">
						<img src="images/loader_white.gif" alt="" />Please wait a few seconds
					</div>
					<div id="delete_success">
						<a href="#" id="back_after_delete"><i class="fa fa-chevron-circle-left"></i>Back to safe inventory</a>
					</div>
					<div id="delete_errors">
						<a href="#" class="no"><i class="fa fa-chevron-circle-left"></i>Back to object</a>
						<div>
						
						</div>
					</div>
				</div>
				
				<!-- Move div -->
				<div id="move_div">
					<h3>Where do you want to move this?</h3>
					<div id="move_cont">
						<div id="move_safes">
							<span class="safes_i_have_access_to">Safes you have access to:</span>
							<input type="text" id="moveSafe" name="move_safe_search" value="start typing to search your safes..." />
							<ul>
								
							</ul>
							<div id="move_pages">
								<span class="num_results">109 Safes</span>
								<div class="pages_ops">
									<span class="num_pages">Page <em class="movePage">1</em> of <em class="movePages">29</em></span><a href="#" class="prv" rel="move"><i class="fa fa-chevron-left"></i></a><a href="#" class="nxt" rel="move"><i class="fa fa-chevron-right"></i></a>
								</div>
							</div>
						</div>
						<div id="move_buttons">
							<a href="#" class="no">Go back</a>
							<a href="#" id="move_final" class="inactive">Move</a>
						</div>
					</div>
					<div id="move_spinner">
						<img src="images/loader_white_blue.gif" alt="" />Please wait a few seconds
					</div>
					<div id="move_success">
						<a href="#" id="back_after_delete">Back to safe inventory</a>
					</div>
					<div id="move_errors">
						There were errors with your move (<a href="#" class="no">Go back</a>)
						<div>
						
						</div>
					</div>
					
				</div>
				
				<!-- Update div -->
				<div id="update_div">
					<div id="updateFormLoading" class="obj"><img src="images/large_loader.gif" alt="" /><br />Updating your object!<span>This should take about five seconds.</span></div>
					<h3>Update this object</h3>
					<form method="post">
						<label class="up">Account name</label>
						<input type="text" name="new_account_name" id="new_account_name" class="up" />
						
						<div class="os">
							<label class="up">Server name</label>
							<input type="text" name="new_os_server_name" class="up" id="new_os_server_name" autocomplete="off" />
						</div>
						
						<div class="db">
							<div class="grey cf">
								<div style="float: left; width: 100%">
									<label class="up">Database Server</label>
									<input type="text" name="new_db_server_name" id="new_db_server_name" class="up" value="Database server name" style="width: 60%;" autocomplete="off" />
								</div>
								<div>
									<label class="up">Port</label>
									<input type="text" name="new_port" value="Port #" id="new_port" class="up" autocomplete="off" style="width: 80%;" /> <span class="req">*</span>
								</div>
								<div>
									<label class="up">Database/Service Name</label>
									<input type="text" name="new_database_name" id="new_database_name" class="up" autocomplete="off" style="width: 80%;" /> <span class="req">*</span>
								</div>
							</div>
						</div>
						
						<label class="up">Password change interval</label>
						<ul id="new_policy_length" class="acc_picker">
							<li><a href="#" class="active" rel="7"><i class="fa fa-check-square"></i>7 days</a></li>
							<li><a href="#" rel="30"><i class="fa fa-check-square"></i>30 days</a></li>
						</ul>
						<input type="hidden" name="new_policy_length" value="7" />
					</form>
					<div id="update_buttons">
						<a href="#" class="no button">Go back</a>
						<a href="#" id="update_final" class="button right">Update Object</a>
					</div>
				</div>
				
				<!-- Object details -->
				<a href="#" class="closeObject"><i class="fa fa-times-circle"></i></a>
				<h2><i class="fa fa-archive"></i><span>Object name</span></h2>
				<div id="objectLoader">
					<img src="images/blue_loader.gif" alt="" /><span>Loading updated object details...</span>
				</div>
				<table id="objectDetails">
					
				</table>
				<a href="#" id="update_button" class="object_actions"><i class="fa fa-pencil-square"></i>Update this object</a><a href="#" id="move_button" class="object_actions"><i class="fa fa-arrows"></i>Move this object</a><a href="#" id="delete_button" class="object_actions"><i class="fa fa-times"></i>Delete this object</a>
			</div>
			<div id="inventory">
				<div class="shader">
					
				</div>
				<a href="#" class="closeInventory"><i class="fa fa-times-circle"></i></a>
				<h2><i class="fa fa-lock"></i><span></span> Inventory</h2>
				<a href="#" class="add_new_account"><i class="fa fa-plus-square"></i>Add new object</a>
				<table class="my_servers" id="my_inventory">
					<thead>
						<tr>
							<th class="obj_name">Object name</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
					
					</tbody>
				</table>
				<div class="pages_bar all">
					<div class="filters">
						<span class="inv_num_results">0 objects</span>
					</div>
					<div class="page_num">Page <span class="inv_page">1</span> of <span class="inv_pages">1</span></div><a href="#" class="prv inactive" rel="inv"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="inv"><i class='fa fa-chevron-right'></i></a>
				</div>
			</div>
			<div id="safes" class="active">
				<h2><i class="fa fa-user"></i>My Safes</h2>				
				<div id="safe_list_container">
					<table class="my_servers" id="my_safes">
						<thead>
							<tr>
								<th>Safe Name</th>
								<th>Created</th>
								<th>Role</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							
						</tbody>
					</table>
					<div id="all_safes_loading" class='pages_loading'>
						<img src="images/loader.gif" alt="" />
					</div>
					<div class="pages_bar all">
						<div class="filters">
							<span class="num_results">0 safes</span>
						</div>
						<div class="page_num">Page <span class="page">1</span> of <span class="pages">4</span></div><a href="#" class="prv inactive" rel="safes"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="safes"><i class='fa fa-chevron-right'></i></a>
					</div>
				</div>
			</div>
			
			<div id="inst">
				Select a safe to manage its objects or add a new object.
			</div>
		</div>
	</div>
	
</div>
</body>
</html>