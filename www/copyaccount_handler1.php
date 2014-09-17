<?php
	session_start();
	
	require_once("includes/dbconn.php");
	
	// Are we in the prod environment or dev?
	$prod = false;
	
	$owner_id = $_SESSION['uid'];
	
	//Gunjan code line 12-16 - to check verfity or other API call
	if(isset($_POST['task'])){
		$task = mysql_real_escape_string($_POST['task']);	
	}else{
		$task='';
	}

	$action = mysqli_real_escape_string($mysqli, $_POST['action']);
	$protect = mysqli_real_escape_string($mysqli, $_POST['action']);
	$safeName = mysqli_real_escape_string($mysqli, $_POST['safeName']);
	
	$createObject = "NO";
	
	// Find out what permissions the user has to this safe
	if ($stmt = $mysqli->prepare("
		SELECT CreateObject, NoConfirmRequired
		FROM safe_users
		RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
		RIGHT JOIN safe_group_access ON safe_group_access.GroupID = safe_group_members.GroupID AND safe_group_access.SafeName = '$safeName'
		WHERE safe_users.UserName = ?
		ORDER BY createObject DESC
		LIMIT 1
	")) {
		$stmt->bind_param("s", $owner_id);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$createObject = $row['CreateObject'];
			$noConfirmRequired = $row['NoConfirmRequired'];
		}
		$canCreate = $createObject;
	}
	
	if ($canCreate != "NO") {
		$messages = array("You don't have permission to do this.");
		$result = array('status'=>'Failure','objectName'=>'none','messages'=>$messages);
		echo json_encode($result);
	}
	else {
		if ($action == "Protect") {
			$action = "Add";
			if ( isset($_POST['account_name']) ) { 
				$accountName = mysqli_real_escape_string($mysqli, $_POST['account_name']); 
			} else { $accountName = ""; }
			
			$account_type = "os";
			if ($prod == true) {
				if ( isset($_POST['policyType']) ) {
					$policyId = mysqli_real_escape_string($mysqli, $_POST['policyType']);
					if ($policyId == "manual") {
						$policyId = "GEN_WinDomain_90dNotification";
					}
					else {
						$policyId = "GEN_WinDomain_7d";
					}
				} else {
					$policyId = 'GEN_WinDomain_7d';
				}
			}
			else {
				$policyId = 'Windows Domain Account';
			}
			
			$hostName = "adobenet";
			$port = "";
			$objectName = "";
			$newSafeName = "";
			$databaseName = "";
			
			if ( isset($_POST['password']) ) {
				$password = mysqli_real_escape_string($mysqli, $_POST['password']);
			} else {
				$password = '';
			}	
			if ( isset($_POST['passwordType']) ) {
				$passwordType = mysqli_real_escape_string($mysqli, $_POST['passwordType']);
			} else {
				$passwordType = '';
			}	
		}
		else {
	
			if ( isset($_POST['account_type']) ) { 
				$account_type = mysqli_real_escape_string($mysqli, $_POST['account_type']); 
			} else { $account_type = ""; }
			
			if ( isset($_POST['os_type']) ) { 
				$os_type = mysqli_real_escape_string($mysqli, $_POST['os_type']); 
			} else { $os_type = ""; }
			
			if ( isset($_POST['database_type']) ) { 
				$db_type = mysqli_real_escape_string($mysqli, $_POST['database_type']); 
			} else { $db_type = ""; }
			
			
			// Determine the policy type and host name
			if ($action == "Add") {
				if ($account_type == "os") {
					if ( $os_type == "windows") { $policyId = "WinServerLocal"; }
					else if ( $os_type == "unix") { $policyId = "UnixSSH"; }
					
					if ( isset($_POST['os_server_name']) ) { 
						$hostName = mysqli_real_escape_string($mysqli, $_POST['os_server_name']); 
					} else { $hostName = ""; }
			
				}
				else if ($account_type == "db") {
					if ( $db_type == "mssql") { $policyId = "MSSQL"; }
					else if ( $db_type == "mysql") { $policyId = "MySQL"; }
					else if ( $db_type == "oracle") { $policyId = "Oracle"; }
					
					if ( isset($_POST['db_server_name']) ) { 
						$hostName = mysqli_real_escape_string($mysqli, $_POST['db_server_name']); 
					} else { $hostName = ""; }
				}
				else {
					$policyId = "";
					$hostName = "";
				}
			}
			else {
				$policyId = "";
				$hostName = "";
			}
			
			if ( isset($_POST['objectName']) ) { 
				$objectName = mysqli_real_escape_string($mysqli, $_POST['objectName']); 
			} else { $objectName = ""; }
			
			if ( isset($_POST['account_name']) ) { 
				$accountName = mysqli_real_escape_string($mysqli, $_POST['account_name']); 
			} else { $accountName = ""; }
			
			if ( isset($_POST['not_corp']) ) { 
				$not_corp = mysqli_real_escape_string($mysqli, $_POST['not_corp']); 
			} else { $not_corp = ""; }
			
			if ( $not_corp == "false" ) {
				$hostName = "{$hostName}.corp.adobe.com";
			}
			
			
			
			if ( isset($_POST['port']) ) { 
				$port = mysqli_real_escape_string($mysqli, $_POST['port']); 
			} else { $port = ""; }
			
			if ( isset($_POST['databaseName']) ) { 
				$databaseName = mysqli_real_escape_string($mysqli, $_POST['databaseName']); 
			} else { $databaseName = ""; }
			
			if ( isset($_POST['newSafeName']) ) {
				$newSafeName = mysqli_real_escape_string($mysqli, $_POST['newSafeName']);
			} else {
				$newSafeName = '';
			}	
			if ( isset($_POST['deviceType']) ) {
				$deviceType = mysqli_real_escape_string($mysqli, $_POST['deviceType']);
			} else {
				$deviceType = '';
			}	
			if ( isset($_POST['password']) ) {
				$password = mysqli_real_escape_string($mysqli, $_POST['password']);
			} else {
				$password = '';
			}	
			if ( isset($_POST['passwordType']) ) {
				$passwordType = mysqli_real_escape_string($mysqli, $_POST['passwordType']);
			} else {
				$passwordType = '';
			}	
		}
		
		if($task==''){
		$soap = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"> 
	   <soapenv:Header> 
		  <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"> 
			 <wsse:UsernameToken> 
				<wsse:Username>cyberark_dev</wsse:Username> 
				<wsse:Password>P@ssw0rd</wsse:Password> 
			 </wsse:UsernameToken> 
		  </wsse:Security> 
	   </soapenv:Header> 
	   <soapenv:Body> 
		  <ns1:executeProcess xmlns:ns1="http://bmc.com/ao/xsd/2008/09/soa"> 
			 <ns1:gridName>DEVGRID1</ns1:gridName> 
			 <ns1:processName>:Adobe-SA-CyberArk_BAO_API:AccountRequestProcess</ns1:processName> 
			 <ns1:parameters> 
				<ns1:Input> 
				   <ns1:Parameter> 
					  <ns1:Name required="false">inputevent</ns1:Name> 
					  <ns1:Value> 
						 <ns1:XmlDoc> 
							<input> 
							   <actiontype>'. $action .'</actiontype>';
							   if ( $action != "Add" && $action != "Protect" ) { $soap .= '<accountuniquename>'. $objectName .'</accountuniquename>'; }
							   $soap .= '<accounttype>'. $account_type .'</accounttype> 
							   <policyid>'. $policyId .'</policyid>
							   <accountname>'. $accountName .'</accountname>
							   <password>'. $password .'</password> 
							   <passwordtype>'. $passwordType .'</passwordtype>
							   <address>'. $hostName .'</address> 
							   <port>'. $port .'</port>
							   <database>'. $databaseName .'</database>
							   <safename>'. $safeName .'</safename>
							   <newsafename>'. $newSafeName .'</newsafename>
							   <requesterid>'. $owner_id .'</requesterid>
							</input> 
						 </ns1:XmlDoc> 
					  </ns1:Value> 
				   </ns1:Parameter> 
				</ns1:Input> 
				<ns1:Output xsi:type="ns1:OutputType" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/> 
			 </ns1:parameters> 
		  </ns1:executeProcess> 
	   </soapenv:Body> 
	</soapenv:Envelope>';
	}
	else {
	//Gunjan code line 229-271 - to craete soap API input 
		if ( $os_type == "windows") { $policyId = "Win"; }
		else if ( $os_type == "unix") { $policyId = "Unix"; }
		//$psw = mysqli_real_escape_string($mysqli, $_POST['password']);
		$psw = $_POST['password'];
		$policy_len = mysqli_real_escape_string($mysqli, $_POST['policy_length']);
			
		$soap = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"> 
	   <soapenv:Header> 
		  <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"> 
			 <wsse:UsernameToken> 
				<wsse:Username>baotest01</wsse:Username> 
				<wsse:Password>test123</wsse:Password> 
			 </wsse:UsernameToken> 
		  </wsse:Security> 
	   </soapenv:Header> 
	   <soapenv:Body> 
		  <ns1:executeProcess xmlns:ns1="http://bmc.com/ao/xsd/2008/09/soa"> 
			 <ns1:gridName>DEVGRID1</ns1:gridName> 
			 <ns1:processName>:Adobe-SA-CyberArk_BAO_API:CheckAccountPwd</ns1:processName> 
			 <ns1:parameters> 
				<ns1:Input> 
				   <ns1:Parameter> 
					  <ns1:Name required="false">inputevent</ns1:Name> 
					  <ns1:Value> 
						 <ns1:XmlDoc> 
							<input> 							  
								<accountname>'.$owner_id.'</accountname>
								<password>'.$psw.'</password>
								<policyid>'.$policyId.'-'.$policy_len.'-days</policyid>
								<address>sj1010005185062.corp.adobe.com</address>
							</input>
						 </ns1:XmlDoc> 
					  </ns1:Value> 
				   </ns1:Parameter> 
				</ns1:Input> 
				<ns1:Output xsi:type="ns1:OutputType" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/> 
			 </ns1:parameters> 
		  </ns1:executeProcess> 
	   </soapenv:Body> 
	</soapenv:Envelope>';
	}	

	$send = TRUE;

	if ($send) {
		//echo "Loading<br />";
		  $ch = curl_init('https://sg-dev.corp.adobe.com/cyberark?api_key=l7xx3803f6ba05e64715a99ebb4c68fe7649');
	  
		  $headers = array(
			"Content-type: text/xml;charset=\"utf-8\"",
			"Accept: text/xml",
			"Cache-Control: no-cache",
			"Pragma: no-cache",
			"SOAPAction: https://sg-dev.corp.adobe.com/cyberark?api_key=l7xx3803f6ba05e64715a99ebb4c68fe7649",
			"Content-length: ".strlen($soap) 
			);
		  
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $soap);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // need to remove going to prod
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // need to remove going to prod

			$result = curl_exec($ch);
			if ($err = curl_error($ch));// die("died");
 
		/*if($errno = curl_errno($ch)) {
			$error_message = curl_strerror($errno);
			echo "cURL error ({$errno}):\n {$error_message}";
			exit;
		}*/
		

		// need to declare namespaces for SimpleXMLElement to work...
		  $xml = simplexml_load_string($result, NULL, NULL, "http://schemas.xmlsoap.org/soap/envelope/");
		  $xml->registerXPathNamespace('S', 'http://schemas.xmlsoap.org/soap/envelope/');
		  $xml->registerXPathNamespace('ns1', 'http://bmc.com/ao/xsd/2008/09/soa');

	  // error responses aren't not consistent and meaningful, so just check if there is a success
		$i = 0;
		foreach ($xml->xpath('//ns1:Name') as $nameElemData) {
			if ($nameElemData == "status message") {
				$error_index = $i;
			}
			else if ($nameElemData == "success status") {
				$success_index = $i;
			}
			$i++;
		}
		  //print_r($xml->xpath('//ns1:XmlDoc')[0]->value);
		  
		  $success_or_failure = $xml->xpath('//ns1:XmlDoc')[$success_index]->value;
		  
		  if ($success_or_failure == "Failure") {
			$status = "Failure";
			$errors = array();
			foreach ($xml->xpath('//ns1:XmlDoc')[$error_index]->value as $errorMsg) {
				$errors[] = (string)$errorMsg;
			}
		  }
		  else if ($success_or_failure == "Success") {
			$status = "Success";
			$errors = "None";
			
			// If this successfully protected and there were no errors, update the stats for this user and the people above him
			if ($protect == "Protect") {
				$errors = "protections";
				if ($stmt = $mysqli->prepare("
					SELECT date
					FROM account_user_compliance_history
					ORDER BY date DESC
					LIMIT 1
				")) {
					$stmt->execute();
					$res = $stmt->get_result();
					while ($row = $res->fetch_assoc()) {
						$date = $row['date']; // This is the date of the most recent update
					}
				}
				
				$errors = mysqli_error($mysqli);
				
				$toUpdateArray = array();
				if ($stmt = $mysqli->prepare("
					SELECT Owner, Manager, Manager2, Manager3, Manager4, Manager5, Manager6, Manager7, Manager8, Manager9
					FROM accounts_dump
					WHERE Account = ?
					LIMIT 1
				")) {
					$stmt->bind_param("s", $accountName);
					$stmt->execute();
					$res = $stmt->get_result();
					while ($row = $res->fetch_assoc()) {
						$accountOwner = $row['Owner'];
						if ($row['Manager'] != "") { array_push($toUpdateArray, $row['Manager']); }
						if ($row['Manager2'] != "") { array_push($toUpdateArray, $row['Manager2']); }
						if ($row['Manager3'] != "") { array_push($toUpdateArray, $row['Manager3']); }
						if ($row['Manager4'] != "") { array_push($toUpdateArray, $row['Manager4']); }
						if ($row['Manager5'] != "") { array_push($toUpdateArray, $row['Manager5']); }
						if ($row['Manager6'] != "") { array_push($toUpdateArray, $row['Manager6']); }
						if ($row['Manager7'] != "") { array_push($toUpdateArray, $row['Manager7']); }
						if ($row['Manager8'] != "") { array_push($toUpdateArray, $row['Manager8']); }
						if ($row['Manager9'] != "") { array_push($toUpdateArray, $row['Manager9']); }
					}
					$toUpdateString = implode("', '", $toUpdateArray); //This is a list of people who need to have their accounts updated
				}
				
				$errors = mysqli_error($mysqli);
				
				
				// Update the most recent numbers
				if ($stmt = $mysqli->prepare("
					UPDATE ad
					SET implemented = 'y'
					WHERE UID = ?
				")) {
					$stmt->bind_param("s", $accountName);
					$stmt->execute();
				}
				
				$errors = mysqli_error($mysqli);
				
				if ($stmt = $mysqli->prepare("
					UPDATE account_user_compliance_history 
					SET o_compliant = o_compliant + 1, o_uncompliant = o_uncompliant - 1
					WHERE date = ? AND UID = ?
				")) {
					$stmt->bind_param("ss", $date, $accountOwner);
					$stmt->execute();
					
					$errors = "is this running?";
				}
				
				
				$errors = mysqli_error($mysqli);
				
				if ($stmt = $mysqli->prepare("
					UPDATE account_user_compliance_history 
					SET m_compliant = m_compliant + 1, m_uncompliant = m_uncompliant - 1
					WHERE date = ? AND UID IN ('$toUpdateString')
				")) {
					$stmt->bind_param("s", $date);
					$stmt->execute();
				}
				
				$errors = mysqli_error($mysqli);
				
			}
		  }
		  else {
			$status = "None";
			$errors = "None";
		  }
		  
		  $new_objectName = "OBJ_{$accountName}_{$hostName}_{$account_type}";
		  if ($account_type == "db") {
				$new_objectName .= "_{$databaseName}";
		  }
		  
		  $result = array('status'=>$status,'objectName'=>$new_objectName,'messages'=>$errors);
		  
		  echo json_encode($result);
		}
		else {
			echo printData('Unknown request or missing data.'); // parameters weren't passed
		}
	}
	
	
	

?>