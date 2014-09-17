<?php

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
         <ns1:processName>:CyberArk_MyWorkTest:NotifyEmail</ns1:processName> 
         <ns1:parameters> 
            <ns1:Input> 
               <ns1:Parameter> 
                  <ns1:Name required="false">inputevent</ns1:Name> 
                  <ns1:Value> 
                     <ns1:XmlDoc> 
                        <input> 
                           <fromaddress>Test Email <pri76183@adobe.com></fromaddress> 
                           <toaddress>steven.r.boiko@us.pwc.com</toaddress> 
                           <subject>test</subject> 
                           <body>sjc</body> 
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
	echo "Loading<br />";
      $ch = curl_init('https://sj1slm934.corp.adobe.com:8443/baocdp/orca');
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $soap);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Accept-Encoding: gzip,deflate',
          'Content-Type: text/xml;charset=UTF-8',
          'SOAPAction: urn:executeProcess'
        )
      );
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // need to remove going to prod
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // need to remove going to prod

      $result = curl_exec($ch);
      if ($err = curl_error($ch)) die("died");

  // need to declare namespaces for SimpleXMLElement to work...
      $xml = simplexml_load_string($result, NULL, NULL, "http://schemas.xmlsoap.org/soap/envelope/");
      $xml->registerXPathNamespace('S', 'http://schemas.xmlsoap.org/soap/envelope/');
      $xml->registerXPathNamespace('ns1', 'http://bmc.com/ao/xsd/2008/09/soa');

  // error responses aren't not consistent and meaningful, so just check if there is a success
      foreach ($xml->xpath('//ns1:Name') as $nameElemData) {
		echo $nameElemData;
      }
	  //print_r($xml->xpath('//ns1:XmlDoc')[0]->result->line[1]);
	  
	  echo "<br /><br />";
	  
	  $success_or_failure = $xml->xpath('//ns1:XmlDoc')[2]->value;
	  
	  $errors = array();
	  if ($success_or_failure == "Failure") {
		echo "Failure";
		echo "<br /><br />";
		foreach ($xml->xpath('//ns1:XmlDoc')[0]->result->line as $errorMsg) {
			echo $errorMsg;
			echo "<br />";
			$errors[] = (string)$errorMsg;
		}
	  }
	  else {
		echo "Success?";
	  }
	  
	  echo "<br /><br />";
	  
	  print_r($errors);
	  
	  
	  //echo $success_or_failure;
	  
	  /*
	  
	  if (isset($xml->xpath('//ns1:XmlDoc')[0]->result)) {
		echo "errors";
	  } 
	  else if (isset($xml->xpath('//ns1:XmlDoc')[1]->values)) {
		echo "success";
	  }
	  
	  echo $count_1;
	  
	  	  
	  $errors = $xml->xpath('//ns1:XmlDoc')[0]->result->line;
	  $count = count($errors);
	  echo $count;
	  
	  echo "<br />";
	  
	  if ($count > 0) {
		for ($i=0;$i<$count;$i++) {
			echo $xml->xpath('//ns1:XmlDoc')[0]->result->line[$i];
			echo "<br />";
		}
	  }
	  else {
		echo "Success?";
	  }
	  
	  */
	  
      //echo printData();
}
else {
	echo printData('Unknown request or missing data.'); // parameters weren't passed
}

?>