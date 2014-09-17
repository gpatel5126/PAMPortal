<?php
class Contact {
    function Contact($id, $name) 
    {
        $this->userId = $id;
        $this->password = $name;
    }
}

/* Initialize webservice with your WSDL */
$client = new SoapClient("https://adobe-dev.onbmc.com/cmdbws/server/cmdbws.wsdl");

/* Fill your Contact Object */
$contact = new Contact("pri76183", "pri76183");

$params = array(
  "loginInfo" => $contact
);

/* Invoke webservice method with your parameters, in this case: Function1 */
$response = $client->GetInstances($params);

/* Print webservice response */
var_dump($response);

?>