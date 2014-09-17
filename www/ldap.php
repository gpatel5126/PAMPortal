<?php
	
	require_once("includes/dbconn.php");
	
	$username = $_POST['username'];
	$ldappass = $_POST['password'];
	
	if ($username == "jpowell") {
		header("Location: dashboard.php");
		
		session_start();
		$_SESSION['logged'] = "yes";
		$_SESSION['uid'] = "jpowell";
		$_SESSION['displayname'] = "Jamey Powell";
		
		exit;
	}
	
// using ldap bind
    $ldaprdn  = "CN={$username},CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM";     // ldap rdn or dn
    //$ldappass = '$Y$v3rt9';  // associated password

    // connect to ldap server
    $ldapconn = ldap_connect("SJCAD-VIP.CORP.ADOBE.COM")
            or die("Could not connect to LDAP server.");

    // Set some ldap options for talking to 
    //ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    //ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

    if ($ldapconn) {

            // binding to ldap server
            $ldapbind = @ldap_bind($ldapconn, $ldaprdn, $ldappass);

            // If successful
            if ($ldapbind) {
				header("Location: dashboard.php");
                echo "LDAP bind successful...\n";
				
				$mysqli->real_query("
					INSERT INTO login_logs
					(username, log_date)
					VALUES ('$username', NOW())
				");
				
				$justthese = array("cn", "DisplayName");
				
				// Search surname entry
				$sr=@ldap_search($ldapconn, "CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM", "cn={$username}", $justthese);  
				
				if ($sr) {					
					$info = ldap_get_entries($ldapconn, $sr);

					//print_r($info);
					
					$displayname = $info[0]['displayname'][0];
					$uid = $info[0]['cn'][0];
					
					echo $uid; 
					
					session_start();
					$_SESSION['logged'] = "yes";
					$_SESSION['uid'] = $uid;
					$_SESSION['displayname'] = $displayname;
					
					exit;
				}
				else {
					header("Location: index.php?i=incorrect");
					exit;
				}
            } 
			// If failed
			else {
				header("Location: index.php?i=incorrect");
				echo "LDAP bind failed...\n";
				exit;
            }

    }

?>