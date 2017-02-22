<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . '_init.php';

$CustomerTable = new Model_CustomerTable();
$Customers = $CustomerTable->fetchAll();
foreach($Customers as $Customer) {
	$dir = __DIR__ . DIRECTORY_SEPARATOR;
	$command = 'cd ' . $dir . ';php migrate_customer.php ' . $Customer->getId();
echo ($command."\n");
break;
	system($command);
}