<?php
	session_start();
	
	require_once("includes/dbconn.php");
	
	
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
         <ns1:processName>:Adobe-SA-CyberArk_BAO_API:CreateSafeRequest</ns1:processName>
         <ns1:parameters> 
            <ns1:Input> 
               <ns1:Parameter> 
                  <ns1:Name required="false">inputevent</ns1:Name> 
                  <ns1:Value> 
                     <ns1:XmlDoc> 
                        <input> 
                           <safename>'. $safeName .'</safename> 
                           <owners>'. $owner_id .'</owners> 
                           <members>'. $members .'</members>
						   <controllers>'. $controllers .'</controllers>
                           <site>'. $geo .'</site> 
						   <env>'. $env .'</env>
                           <description>'. $description .'</description> 
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
	  
	  //print_r($xml->xpath('//ns1:XmlDoc')[0]->result->line[1]);
	  
	  $success_or_failure = $xml->xpath('//ns1:XmlDoc')[$success_index]->value;
	  
	  if ($success_or_failure == "Failure") {
		$status = "Failure";
		$errors = array();
		foreach ($xml->xpath('//ns1:XmlDoc')[$error_index]->result->line as $errorMsg) {
			$errors[] = (string)$errorMsg;
		}
	  }
	  else if ($success_or_failure == "Success") {
		$status = "Success";
		$errors = "None";
	  }
	  else {
		$status = "Failure";
		$errors = "Could not connect";
	  }
	  
	  
	  $result = array('status'=>$status,'safeName'=>$fullSafeName,'messages'=>$errors);
	  
	  echo json_encode($result);
	  
}
else {
	$result = array('status'=>"Failure",'safeName'=>"",'messages'=>"Could not connect");
	  
	  echo json_encode($result);
}
	
	
	

?>