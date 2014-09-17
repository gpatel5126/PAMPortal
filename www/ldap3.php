<?php
	
	//$username = $_POST['username'];
	//$ldappass = $_POST['password'];
	
// using ldap bind
    $ldaprdn  = "CN=pri76183,CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM";     // ldap rdn or dn
    $ldappass = '$Y$v3rt9';  // associated password

    // connect to ldap server
    $ldapconn = ldap_connect("SJCAD-VIP.CORP.ADOBE.COM")
            or die("Could not connect to LDAP server.");

	ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
	
	
    // Set some ldap options for talking to 
    //ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    //ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

    if ($ldapconn) {

            // binding to ldap server
            $ldapbind = @ldap_bind($ldapconn, $ldaprdn, $ldappass);

            // If successful
            if ($ldapbind) {
				//header("Location: dashboard.php");
                echo "LDAP bind successful...\n";
				
				$justthese = array("UID", "DisplayName");
				
				// $ds is a valid link identifier (see ldap_connect)
				

				$dn        = 'CN=pri76183,CN=USERS,DC=ADOBENET,DC=GLOBAL,DC=ADOBE,DC=COM';
				$filter    = 'UID=de*';
				//$justthese = array('ou', 'sn', 'givenname', 'mail');

				// enable pagination with a page size of 100.
				$pageSize = 100;

				$cookie = '';
				do {
					ldap_control_paged_result($ldapconn, $pageSize, true, $cookie);

					$result  = ldap_search($ldapconn, $dn, $filter, $justthese);
					$entries = ldap_get_entries($ldapconn, $result);
						 
					foreach ($entries as $e) {
						print_r($e);
					}

					ldap_control_paged_result_response($ldapconn, $result, $cookie);
				   
				} while($cookie !== null && $cookie != '');
            } 
			// If failed
			else {
				//header("Location: index.php?i=incorrect");
				echo "LDAP bind failed...\n";
				//exit;
            }
		}

?>