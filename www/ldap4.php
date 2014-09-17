<?php
	
	//$username = $_POST['username'];
	//$ldappass = $_POST['password'];
	
// using ldap bind
    $ldaprdn  = "CN=pri76183,CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM";     // ldap rdn or dn
    $ldappass = '$Y$v3rt9';  // associated password

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
				
				$justthese = array("UID", "DisplayName", "givenname", "sn", "mail", "adobeactivestatus", "employeetype", "adobecostcenter", "Manager");
				
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
						ldap_control_paged_result($ldapconn, 1000, true, $cookie);
						//$sr=@ldap_search($ldapconn, "CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM", "SN=*", $justthese); 
						$sr=@ldap_search($ldapconn, "CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM", "SN=narron"); 

						if ($sr) {
							$info = ldap_get_entries($ldapconn, $sr);
							print_r($info);
							
							foreach($info as $e) {
								$uid = @$e['uid'][0];
								$displayName = @$e['displayname'][0];
								$givenname = @$e['givenname'][0];
								$sn = @$e['sn'][0];
								$mail = @$e['mail'][0];
								$adobeactivestatus = @$e['adobeactivestatus'][0];
								$employeetype = @$e['employeetype'][0];
								$adobecostcenter = @$e['adobecostcenter'][0];
								$manager = @$e['manager'][0];
								$manager = explode(',', $manager);
								$manager = $manager[0];
								$manager = str_replace("CN=","",$manager);
								
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
							}
							
						
							@ldap_control_paged_result_response($ldapconn, $sr, $cookie);
						}
						
					
					} while($cookie !== null && $cookie != '');
					
					
					echo "</table>";
					
					//exit;
					
				}
			// If failed
			else {
				header("Location: index.php?i=incorrect");
				echo "LDAP bind failed...\n";
				exit;
            }

    }

?>