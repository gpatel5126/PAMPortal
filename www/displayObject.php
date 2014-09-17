<?php
	session_start();
	
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
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
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
			
			if ($canCreate == "YES") {
				$access = "Yes";
			}
			else {
				$access = "No";
			}
		}
	}
	
	
	/*
	$owner_id = $_SESSION['uid'];
	
	$safeName = mysqli_real_escape_string($mysqli, $_POST['safeName']);
	$who = mysqli_real_escape_string($mysqli, $_POST['who']);
	$geo = mysqli_real_escape_string($mysqli, $_POST['geoSelect']);
	$env = mysqli_real_escape_string($mysqli, $_POST['environment']);
	if ( isset($_POST['description']) ) {
		$description = mysqli_real_escape_string($mysqli, $_POST['description']);
	} else {
		$description = '';
	}
	
	
	//echo $safeName;
	
	//echo $owner_id;
	
	if ($who == "multiple_users") {
	
		//echo $who;
		if (isset($_POST['members'])) {
			$members = $_POST['members'];
			$members = implode(",", $members);
		}
		else {
			$members = "";
		}
		
		if (isset($_POST['controllers'])) {
			$controllers = $_POST['controllers'];
			$controllers = implode(",", $controllers);
		}
		else {
			$controllers = "";
		}
		
	}
	else {
		$members = "";
		$controllers = "";
	}
	
	$fullSafeName = "{$geo}_{$safeName}_{$env}";
	
	*/
	
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
			<actiontype>DisplayObject</actiontype>
			<accountuniquename>'. $postAccount .'</accountuniquename>
			<safename>'. $postSafeName .'</safename>
			<requesterid>anil</requesterid>
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
      /*
	  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Accept-Encoding: gzip,deflate',
          'Content-Type: text/xml;charset=UTF-8',
          'SOAPAction: urn:executeProcess'
        )
      );
	  */
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
		//print_r($xml);
		
		foreach ($xml->xpath('//ns1:Name') as $nameElemData) {
			if ($nameElemData == "status message") {
				$status_index = $i;
			}
			else if ($nameElemData == "success status") {
				$success_index = $i;
			}
			else if ($nameElemData == "result data") {
				$result_index = $i;
			}
			$i++;
		}
	  
	  //print_r($xml->xpath('//ns1:XmlDoc')[0]->result->line[1]);
	  
	  $success_or_failure = $xml->xpath('//ns1:XmlDoc')[$success_index]->value;
	  
	  if ($success_or_failure == "Success") {
		
		// Default these to errors
		$accountUniqueName = "error"; $accessed = "error"; $lastUsedDate = "error"; $lastUsedBy = "error"; $validationStatus = "error"; $policyId = "error"; $userName = "error"; $deviceType = "error"; $retriesCount = "error"; $port = "error"; $address = "error"; $dsn = "error"; $database = "error";
				
		$status = "Success";
		$errors = $xml->xpath('//ns1:XmlDoc')[$status_index]->value;
		$results = $xml->xpath('//ns1:XmlDoc')[$result_index]->value;
		
		$results = explode("\n",$results);		
	  
		  $lineCount = 0;
		  foreach($results as $line) {
			if ($lineCount == 0) {
				$props = trim($line, " ");
				$props = explode(" ,",$props);
				foreach($props as $prop) {
					$prop = explode(":",$prop,2);
					if ( isset($prop[0]) && isset($prop[1]) ) {
						$propertyName = trim($prop[0]," ");
						$propertyValue = trim(trim($prop[1], ' '),'"');
					
						switch($propertyName) {
							case "accountuniquename":
							$accountUniqueName = $propertyValue;
							break;
							
							case "accessed":
							$accessed = $propertyValue;
							break;
							
							case "lastuseddate":
							$lastUsedDate = $propertyValue;
							break;
							
							case "lastusedby":
							$lastUsedBy = $propertyValue;
							break;
							
							case "validationstatus":
							$validationStatus = $propertyValue;
							break;
						}
					}
				}
			}
			else {
				$line_split = explode(" ",$line,2);
				if ( isset($line_split[0]) && isset($line_split[1]) ) {
					$propertyName = $line_split[0];
					$propertyValue = trim($line_split[1]," ");
				
					switch($propertyName) {
						case "PolicyID":
						$policyId = $propertyValue;
						break;
						
						case "UserName":
						$userName = $propertyValue;
						break;
						
						case "DeviceType":
						$deviceType = $propertyValue;
						break;
						
						case "RetriesCount":
						$retriesCount = $propertyValue;
						break;
						
						case "Port":
						$port = $propertyValue;
						break;
						
						case "Address":
						$address = $propertyValue;
						break;
						
						case "DSN":
						$dsn = $propertyValue;
						break;
						
						case "Database":
						$database = $propertyValue;
						break;
					}
				}
			}
			$lineCount++;
		  }
		  
		  // Clean up the data
		  if ($retriesCount == "-1") {
			$retriesCount = "Indefinite";
		  }
		  $result = array(
			'access' => $access,
			'status' => $status,
			'results' => array(
				'validationStatus' => array(
					'name' => 'Validation Status',
					'value' => $validationStatus,
					'show' => 'yes'
				),
				'accountName' => array(
					'name' => 'Account Name',
					'value' => $userName,
					'show' => 'yes'
				),
				'address' => array(
					'name' => 'Address',
					'value' => $address,
					'show' => 'yes'
				),
				'deviceType' => array(
					'name' => 'Device Type',
					'value' => $deviceType,
					'show' => 'yes'
				),
				'accountUniqueName' => array(
					'name' => 'Account Unique Name',
					'value' => $accountUniqueName,
					'show' => 'no'
				),
				'lastUsedBy' => array(
					'name' => 'Last Used By',
					'value' => $lastUsedBy,
					'show' => 'yes'
				),
				'lastUsedDate' => array(
					'name' => 'Last Used Date',
					'value' => $lastUsedDate,
					'show' => 'yes'
				),
				'accessed' => array(
					'name' => 'Accessed',
					'value' => $accessed,
					'show' => 'yes'
				),
				'policyId' => array(
					'name' => 'Policy ID',
					'value' => $policyId,
					'show' => 'yes'
				),
				'retriesCount' => array(
					'name' => 'Retries Count',
					'value' => $retriesCount,
					'show' => 'yes'
				),
				'port' => array(
					'name' => 'Port',
					'value' => $port,
					'show' => 'yes'
				),
				'dsn' => array(
					'name' => 'DSN',
					'value' => $dsn,
					'show' => 'no'
				),
				'database' => array(
					'name' => 'Database/Service Name',
					'value' => $database,
					'show' => 'yes'
				)
			)
		  );
	  }
	  else {
		$status = "Failure";
		$errors = array();
		
		// Maybe update this to include actual error messages?
		/*
		foreach ($xml->xpath('//ns1:XmlDoc')[$status_index]->result->line as $errorMsg) {
			$errors[] = (string)$errorMsg;
		}
		*/
		$result = array(
			'status' => $status,
			'message' => "Object not found.  Please contact support."
		  );
	  }
	  echo json_encode($result);
}
else {
	echo printData('Unknown request or missing data.'); // parameters weren't passed
}
	
	
	

?>