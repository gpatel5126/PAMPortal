<?php
	
	require_once("../includes/dbconn.php");
	
	$values_array = array();
	
	//$username = $_POST['username'];
	//$ldappass = $_POST['password'];
	
// using ldap bind
    $ldaprdn  = "CN=PAMbind,CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM";     // ldap rdn or dn
    $ldappass = '24wrsfxv@$WRSFXV';  // associated password

    // connect to ldap server
    $ldapconn = ldap_connect("SJCAD-VIP.CORP.ADOBE.COM")
            or die("Could not connect to LDAP server.");

    // Set some ldap options for talking to 
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    //ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

    if ($ldapconn) {
	
			$cookie = '';

            // binding to ldap server
            $ldapbind = @ldap_bind($ldapconn, $ldaprdn, $ldappass);

            // If successful
            if ($ldapbind) {
				//header("Location: dashboard.php");
                echo "LDAP bind successful...\n";
				
				$justthese = array("UID", "DisplayName", "givenname", "sn", "mail", "adobeactivestatus", "employeetype", "adobecostcenter", "Manager", "cn");
				
				$pagesize = 1000;
				
				
				// Search surname entry
				echo "<table style='width: 1100px;'>";
				echo "<tr>";
					echo "<th>#</th>";
					echo "<th>UID</th>";
					echo "<th>Display Name</th>";
					echo "<th>Given Name</th>";
					echo "<th>Surnam</th>";
					echo "<th>Mail</th>";
					echo "<th>Adobe Active Status</th>";
					echo "<th>Employee Type</th>";
					echo "<th>Adobe Cost Center</th>";
					echo "<th>Manager</th>";
				
				echo "</tr>";
				
				$i =1;
				
				
				//if ($sr) {					
					
					$i = 0;
					
					do {
						ldap_control_paged_result($ldapconn, $pagesize, true, $cookie);
						$sr=@ldap_search($ldapconn, "CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM", "(&(!(userAccountControl:1.2.840.113556.1.4.803:=2))(objectClass=user))", $justthese); 
						//$sr=@ldap_search($ldapconn, "CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM", "UID=d*"); 

						if ($sr) {
							$info = ldap_get_entries($ldapconn, $sr);
							//print_r($info);
							
							foreach($info as $e) {
								$uid = mysqli_real_escape_string($mysqli, @$e['cn'][0]);
								$displayName = mysqli_real_escape_string($mysqli, @$e['displayname'][0]);
								$givenname = mysqli_real_escape_string($mysqli, @$e['givenname'][0]);
								$sn = mysqli_real_escape_string($mysqli, @$e['sn'][0]);
								$mail = mysqli_real_escape_string($mysqli, @$e['mail'][0]);
								$adobeactivestatus = mysqli_real_escape_string($mysqli, @$e['adobeactivestatus'][0]);
								$employeetype = mysqli_real_escape_string($mysqli, @$e['employeetype'][0]);
								$adobecostcenter = mysqli_real_escape_string($mysqli, @$e['adobecostcenter'][0]);
								$manager = mysqli_real_escape_string($mysqli, @$e['manager'][0]);
								$manager = explode(',', $manager);
								$manager = $manager[0];
								$manager = mysqli_real_escape_string($mysqli, str_replace("CN=","",$manager));
								
								if ($uid != "") {
									echo "<tr>";
										echo "<td>{$i}</td>";
										echo "<td>{$uid}</td>";
										echo "<td>{$displayName}</td>";
										echo "<td>{$givenname}</td>";
										echo "<td>{$sn}</td>";
										echo "<td>{$mail}</td>";
										echo "<td>{$adobeactivestatus}</td>";
										echo "<td>{$employeetype}</td>";
										echo "<td>{$adobecostcenter}</td>";
										echo "<td>{$manager}</td>";
									
									echo "</tr>";
									/*
									$displayname = @$e['displayname'][0];
									$uid = $e['uid'][0];
									$manager = $e['manager']['uid'];;
									echo $i;
									echo " - ";
									echo $displayname;
									echo " ({$uid})";
									echo " - Manager: {$manager}";
									echo "<br />";
									$i++;
									*/
									
									echo "</tr>";
									$i++;
									
									$values_string = "('{$uid}','{$displayName}','{$givenname}','{$sn}', '{$mail}', '{$adobeactivestatus}','{$employeetype}','{$adobecostcenter}','{$manager}')";
									
									array_push($values_array, $values_string);
									
									
								}
							}
							
						
							@ldap_control_paged_result_response($ldapconn, $sr, $cookie);
						}
						
					
					} while($cookie !== null && $cookie != '');
					
					
					echo "</table>";
					
					//exit;
					
				}
			// If failed
			else {
				echo "LDAP bind failed...\n";
            }

    }
	
	$values = implode(",", $values_array);
	
	echo $values;
	
	
	try {
		$mysqli->autocommit(FALSE);
		$mysqli->real_query("
			TRUNCATE table ad_hold
		");
		$mysqli->real_query("
			INSERT INTO ad_hold
			(UID, FullName, givenname, sn, mail, adobeactivestatus, employeeType, adobecostcenter, Manager)
			VALUES $values;
		");
		echo mysqli_error($mysqli);
		$mysqli->real_query("
			UPDATE ad_hold
			LEFT JOIN ad_hold m2 ON ad_hold.manager = m2.uid
			LEFT JOIN ad_hold m3 ON m2.manager = m3.uid
			LEFT JOIN ad_hold m4 ON m3.manager = m4.uid
			LEFT JOIN ad_hold m5 ON m4.manager = m5.uid
			LEFT JOIN ad_hold m6 ON m5.manager = m6.uid
			LEFT JOIN ad_hold m7 ON m6.manager = m7.uid
			LEFT JOIN ad_hold m8 ON m7.manager = m8.uid
			LEFT JOIN ad_hold m9 ON m8.manager = m9.uid
			LEFT JOIN ad_hold m10 ON m9.manager = m10.uid
			SET ad_hold.manager2 = m2.manager,
			ad_hold.manager3 = m3.manager,
			ad_hold.manager4 = m4.manager,
			ad_hold.manager5 = m5.manager,
			ad_hold.manager6 = m6.manager,
			ad_hold.manager7 = m7.manager,
			ad_hold.manager8 = m8.manager,
			ad_hold.manager9 = m9.manager,
			ad_hold.manager10 = m10.manager;
		");
		echo mysqli_error($mysqli);
		$mysqli->real_query("
			UPDATE ad_hold 
			SET implemented = 'n' 
			WHERE employeeType IN (0,9)
		");
		echo mysqli_error($mysqli);
		$mysqli->real_query("
			UPDATE ad_hold 
			SET implemented = 'y' 
			WHERE employeeType IN (0,9) AND uid IN (SELECT account FROM implemented_accounts)
		");
		echo mysqli_error($mysqli);
		$mysqli->real_query("
			TRUNCATE table ad
		");
		echo mysqli_error($mysqli);
		$mysqli->real_query("
			INSERT INTO ad
			SELECT * FROM ad_hold;
		");
		echo mysqli_error($mysqli);
		$mysqli->commit();
	} catch (Exception $e) {
		$mysqli->rollback();
	}
	

?>