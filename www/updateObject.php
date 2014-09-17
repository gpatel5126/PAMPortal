<?php
	session_start();
	$target = $_SESSION['uid'];
	
	require_once("includes/dbconn.php");
	
	if ( isset($_POST['accountUniqueName']) ) {
		$postAccount = mysqli_real_escape_string($mysqli, $_POST['accountUniqueName']);
	} else {
		$postAccount = '';
	}
	if ( isset($_POST['safeName']) ) {
		$postSafeName = mysqli_real_escape_string($mysqli, $_POST['safeName']);
	} else {
		$postSafeName = '';
	}
	if ( isset($_POST['accountType']) ) {
		$accountType = mysqli_real_escape_string($mysqli, $_POST['accountType']);
		if ($accountType == "Database") { $accountType = "db"; }
		if ($accountType == "Operating System") { $accountType = "os"; }
	} else {
		$accountType = '';
	}
	if ( isset($_POST['accountName']) ) {
		$accountName = mysqli_real_escape_string($mysqli, $_POST['accountName']);
	} else {
		$accountName = '';
	}
	if ( isset($_POST['policyID']) ) {
		$policyID = mysqli_real_escape_string($mysqli, $_POST['policyID']);
	} else {
		$policyID = '';
	}
	
	/* Use code similar to this for production...you'll have to examine the policy ID and then modify it to match what the user posted
	
		// Determine the policy type and host name
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
		
	*/
	
	if ( isset($_POST['address']) ) {
		$address = mysqli_real_escape_string($mysqli, $_POST['address']);
	} else {
		$address = '';
	}
	if ( isset($_POST['port']) ) {
		$port = mysqli_real_escape_string($mysqli, $_POST['port']);
	} else {
		$port = '';
	}
	if ( isset($_POST['new_database_name']) ) {
		$database = mysqli_real_escape_string($mysqli, $_POST['new_database_name']);
	} else {
		$database = '';
	}
	
	// Default canCreate
	$canCreate = "None";
	
	// Find out what permissions the user has to this safe
	if ($stmt = $mysqli->prepare("
		SELECT CreateObject, NoConfirmRequired
		FROM safe_users
		RIGHT JOIN safe_group_members ON safe_users.UserID = safe_group_members.UserID
		RIGHT JOIN safe_group_access ON safe_group_access.GroupID = safe_group_members.GroupID AND safe_group_access.SafeName = ?
		WHERE safe_users.UserName = ?
		ORDER BY createObject DESC
		LIMIT 1
	")) {
		$stmt->bind_param("ss", $postSafeName, $target);
		$stmt->execute();
		$res = $stmt->get_result();
		
		while ($row = $res->fetch_assoc()) {
			$createObject = $row['CreateObject'];
			$noConfirmRequired = $row['NoConfirmRequired'];
			$canCreate = $createObject;
		}
	}
	
	if ($canCreate == "YES") {
		$access = "Owner";
	}
	else {
		$access = "Member";
	}
	
	$soap = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
			<soapenv:Header>
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
				<actiontype>UpdateObject</actiontype>
				<accountuniquename>'. $postAccount .'</accountuniquename>
				<accounttype>'. $accountType .'</accounttype>
				<safename>'. $postSafeName .'</safename>
				<policyid>'. $policyID .'</policyid>
				<accountname>'. $accountName .'</accountname>
				<address>'. $address .'</address> 
				<port>'. $port .'</port>
				<database>'. $database .'</database>
				<requesterid>'. $target .'</requesterid>
			</input>
			</ns1:XmlDoc>
			</ns1:Value>
			</ns1:Parameter>
			</ns1:Input>
			<ns1:Output xsi:type="ns1:OutputType" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>;
			</ns1:parameters>
			</ns1:executeProcess>
			</soapenv:Body>
			</soapenv:Envelope>';

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
      if ($err = curl_error($ch)) die("died");
	  
	  //echo $result;

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
		  }
		  else {
			$status = "None";
			$errors = "None";
		  }
		  
		  $object_name = "OBJ_{$accountName}_{$address}_{$accountType}";
		  if ($accountType == "db") {
				$object_name .= "_{$database}";
		  }
		  
		  $result = array('status'=>$status,'messages'=>$errors,'new_object_name'=>$object_name);
		  
		  echo json_encode($result);
}
else {
	echo printData('Unknown request or missing data.'); // parameters weren't passed
}
?>