<?php

require_once("../includes/dbconn.php");

echo "
	<table>
		<tr>
			<th>Instance ID</th>
			<th>Name</th>
			<th>OwnerName</th>
			<th>HostName</th>
			<th>PrimaryContact</th>
			<th>PrimaryContactEmail</th>
			<th>SecondaryContact</th>
			<th>Secondary Contact Email</th>
			<th>Reconciliation Identity</th>
			<th>Confidentiality</th>
			<th>System Role</th>			
			<th>Address</th>			
		</tr>
	</thead>
	<tbody>
	";
	
set_time_limit(0);

class AuthHeader {
    function LoginInfo($LoginResponse)
    {
       $this->userId  = "username";
       $this->password = "password";
	   $this->lang = "lang";	   
 
    }
}

    
  $client= new SoapClient("https://adobe-dev.onbmc.com/cmdbws/server/cmdbws.wsdl");
  $ns = 'http://schemas.xmlsoap.org/wsdl/soap';
  $LoginResponse =array('userId' => "PAMbind", 'password' => "Password", 'lang' => "english");
  $ClassResponse =array('namespaceName' => "BMC.CORE", 'className' => "BMC_ComputerSystem");
  $query = '\'AssetLifecycleStatus\' = "Deployed" AND (\'SystemRole\' = "IaaS Managed - Linux VM" OR \'SystemRole\'="ESR Server" OR \'SystemRole\' = "IaaS AIX LPAR Managed" OR \'SystemRole\' = "Iaas AIX Server" OR \'SystemRole\' = "IaaS ESX Blade Server" OR \'SystemRole\' = "IaaS LAB" OR \'SystemRole\' = "IaaS Linux Managed VM" OR \'SystemRole\' = "IaaS Linux Unmanaged VM" OR \'SystemRole\' = "IaaS Unix Blade Server" OR \'SystemRole\' = "IaaS Unix Physical Server" OR \'SystemRole\' = "IaaS Windows Blade Server" OR \'SystemRole\' = "IaaS Windows Managed VM" OR \'SystemRole\' = "IaaS Windows Unmanaged VM" OR \'SystemRole\' = "Infoblox Physical Appliance" OR \'SystemRole\' = "Legacy Server" OR \'SystemRole\' = "Legacy Server - ESX" OR \'SystemRole\' = "Legacy Server - OSX" OR \'SystemRole\' = "Legacy Server - Unix" OR \'SystemRole\' = "Legacy Server - Windows" OR \'SystemRole\' = "Legacy Windows Managed Server" OR \'SystemRole\' = "Not Available" OR \'SystemRole\' = "NTP Server Appliance" OR \'SystemRole\' = "SAP Virtual Host" OR \'SystemRole\' = "Unkown" OR \'SystemRole\' = "Virtual Appliances")';
  $attributes = array("items" => array("Name", "OwnerName","HostName","PrimaryContact","PrimaryContactEmail","SecondaryContact","SecondaryContactEmail","ReconciliationIdentity","Confidentiality","SystemRole"));
  $firstRetrieve = "0";
  $maxRetrieve = "NO_MAX_RETRIEVE";
  //$maxRetrieve = "10";
  $sortOrder = array("items"=>array("attributeName"=>"Name", "sortOrder"=>"ASCENDING"));
  $aDatasetId = "BMC.ASSET";
  $aGetMask = "GET_MASK_NONE";
  
  $AuthHeader = new AuthHeader($LoginResponse);
  $header =  new SoapHeader($ns,"AuthenticationInfo", $AuthHeader,false);
  $client->__setSoapHeaders(array($header));
  
  $params= array("loginInfo" => $LoginResponse, "classNameId" => $ClassResponse, "query" => $query, "attributes" => $attributes, "firstRetrieve" => $firstRetrieve, "maxRetrieve" => $maxRetrieve, "sortOrder" => $sortOrder, "aDatasetId" => $aDatasetId, "aGetMask" => $aGetMask); 
  $result = $client->GetInstances($params);
  
  $cmdb_array = array();
  
  foreach ($result->instanceInfo->items as $entry) {
	$instanceId = $entry->instanceId;
	
	foreach ($entry->instanceAttributes->items as $att) {
		//echo $att->name;
		if ($att->name == "Name") { if (!empty($att->value)) { $name = $att->value->stringValue; } else { $name="Not Available"; } } 
		if ($att->name == "OwnerName") { if (!empty($att->value)) { $ownerName = $att->value->stringValue; } else { $ownerName = "Not Available"; } } 
		if ($att->name == "HostName") { if (!empty($att->value)) { $hostName = $att->value->stringValue; } else { $hostName = "Not Available"; } } 
		if ($att->name == "PrimaryContact") { if (!empty($att->value)) { $primarycontact = $att->value->stringValue; } else { $primaryContact = "Not Available"; } } 
		if ($att->name == "PrimaryContactEmail") { if (!empty($att->value)) { $primaryContactEmail = $att->value->stringValue; } else { $primaryContactEmail = "Not Available"; } } 
		if ($att->name == "SecondaryContact") { if (!empty($att->value)) { $secondaryContact = $att->value->stringValue; } else { $secondaryContact = "Not Available"; } } 
		if ($att->name == "SecondaryContactEmail") { if (!empty($att->value)) { $secondaryContactEmail = $att->value->stringValue; } else { $secondaryContactEmail = "Not Available"; } } 
		if ($att->name == "ReconciliationIdentity") { if (!empty($att->value)) { $reconciliationIdentity = $att->value->stringValue; } else { $reconciliationIdentity = "Not Available"; } } 
		if ($att->name == "Confidentiality") { 
			if (!empty($att->value)) { 
				$confidentiality = $att->value->enumValue; 
				switch($confidentiality) {
					case 10:
						$confidentiality = "Low";
						break;
					case 20:
						$confidentiality = "Medium";
						break;
					case 30:
						$confidentiality = "High";
						break;
				}
			} 
			else { 
				$confidentiality = "Not Available"; 
			}
		} 
		if ($att->name == "SystemRole") { if (!empty($att->value)) { $systemRole = $att->value->stringValue; } else { $systemRole = "Not Available"; } } 

		
	}
	echo "<tr>";
		echo "<td>{$instanceId}</td>";
		echo "<td>{$name}</td>";
		echo "<td>{$ownerName}</td>";
		echo "<td>{$hostName}</td>";
		echo "<td>{$primaryContact}</td>";
		echo "<td>{$primaryContactEmail}</td>";
		echo "<td>{$secondaryContact}</td>";
		echo "<td>{$secondaryContactEmail}</td>";
		echo "<td>{$reconciliationIdentity}</td>";
		echo "<td>{$confidentiality}</td>";
		echo "<td>{$systemRole}</td>";
		
		// Run tests of the address
		$address = str_replace(".","",$name);
		if ( is_numeric($address) ) {
			$address = $name;
		}
		else {
			$address = str_replace("http://","",$name);
			$address = str_replace("https://","",$address);
			$address = str_replace("www.","",$address);
			$address = explode(".",$address);
			$address = $address[0];
		}
		
		$temp_array = "('$instanceId', '$reconciliationIdentity', '$name', '$ownerName', '----Owner Contact----', '$primaryContact', '$primaryContactEmail', '$secondaryContact', '$secondaryContactEmail', '----Availability-----', '-----CI Integrity----', '$confidentiality', '$systemRole', '----Vendor Application-----', '-----Environment-----', '$address')";
		array_push($cmdb_array, $temp_array);
	
		echo "<td>{$address}</td>";
		
	echo "</tr>";
  }
  
  echo "</tbody>";
  echo "</table>";
  
  echo "<pre>";
  //print_r($result);
  echo "</pre>";
  
  $values = implode(",", $cmdb_array);
  
// Start the MySQL transaction

try {
	$mysqli->autocommit(FALSE);
	$mysqli->real_query("
		TRUNCATE table cmdb_dump2
	");
	$mysqli->real_query("
		INSERT INTO cmdb_dump2
		(`Instance ID`, `Reconciliation Identity`, `CI Name`, `Owner Name`, `Owner Contact`, `Primary Contact`, `Primary Contact Email`, `Secondary Contact`, `Secondary Contact Email`, `Availability`, `CI Integrity`, `Confidentiality`, `System Role`, `Vendor Application`, `Environment Specification`, `address`)
		VALUES $values;
	");
	$mysqli->real_query("
		UPDATE cmdb_dump2
		RIGHT JOIN ca_dump ON cmdb_dump.`address` = ca_dump.`address` AND ca_dump.`Target System User Name` IN ('root','administrator','carkunix','carkadmin','monadmin','mcsadmin')
		SET implemented = 'y'
	");
	$mysqli->commit();
} catch (Exception $e) {
	$mysqli->rollback();
}

	
?>

