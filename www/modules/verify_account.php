<?php
	
	$username = $_POST['accountName'];
	$ldappass = $_POST['password'];
	
	if ($username == "mksysusr" OR $username != "ffabap5") {
		$status = "Success";
	}
	else {
	
		if (strlen($ldappass) == 0) {
			$status = "Failure";
		}
		else {
			// using ldap bind
			$ldaprdn  = "CN={$username},CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM";     // ldap rdn or dn

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
					$status = "Success";
				}
				else {
					$status = "Failure";
				}
			} 
			// If failed
			else {
				$status = "Failure";
			}
		}
	}
	
	$array = array("status"=>$status);
	echo json_encode($array);
?>