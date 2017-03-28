<?php
			define("USERNAME", "woocommercedemo@webkul.com");
			define("PASSWORD", "webkul12#");
			define("SECURITY_TOKEN", "T8OOEnnNbRP4oIIO1d4ACMYIq");
			require_once ("soapclient/SforceEnterpriseClient.php");
			$mySforceConnection = new SforceEnterpriseClient();
			$mySforceConnection->createConnection("enterprise.wsdl.xml");
			$response=$mySforceConnection->login(USERNAME, PASSWORD.SECURITY_TOKEN);
			echo "<pre>";
			print_r($mySforceConnection->describeGlobal());

			
			echo "</pre>";
				
									

			
				
									
?>