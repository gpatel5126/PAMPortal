<?php
	/*
	$client = new SoapClient('http://www.webservicex.net/geoipservice.asmx?WSDL');
	$result = $client->GetGeoIP(array('IPAddress' => '8.8.8.8'));

	print_r($result);

	
	echo "<br /><br />";
	$country = $result->GetGeoIPResult->CountryName;
	
	*/
	
?>
<?php

$client = new SoapClient(null, array('location' => "http://localhost/soap.php",
                                     'uri'      => "http://test-uri/"));
$headers = array();

$headers[] = new SoapHeader('http://soapinterop.org/echoheader/', 
                            'echoMeStringRequest',
                            'hello world');

$headers[] = new SoapHeader('http://soapinterop.org/echoheader/', 
                            'echoMeStringRequest',
                            'hello world again');

$client->__setSoapHeaders($headers);

$client->__soapCall("echoVoid", null);
?>