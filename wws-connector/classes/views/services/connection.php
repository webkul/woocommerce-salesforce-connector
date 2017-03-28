<?php
			define("USERNAME", "woocommerce@webkul.com");
			define("PASSWORD", "wJExDW6K3agr");
			define("SECURITY_TOKEN", "ILc31MW9x9FRr2VnLMM5dKyp");
			require_once ("Force.com-Toolkit-for-PHP-master/soapclient/SforceEnterpriseClient.php");
			$mySforceConnection = new SforceEnterpriseClient();
			$mySforceConnection->createConnection("enterprise.wsdl.XML");
			
			$response=$mySforceConnection->login(USERNAME, PASSWORD.SECURITY_TOKEN);
			$allSfCategories=$mySforceConnection->query('SELECT Id,Name,webkul_wws__woo_category_id__c,webkul_wws__Parent_category__c,webkul_wws__Slug__c FROM webkul_wws__woo_commerce_categories__c');
			echo '<pre>';
			print_r($allSfCategories->records[0]);
			echo '</pre>';
?>