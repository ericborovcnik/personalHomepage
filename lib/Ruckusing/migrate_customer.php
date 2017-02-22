<?php

/*
 * Initialisiere Betriebsumgebung
 */
$base_path = $_SERVER['PWD'].DIRECTORY_SEPARATOR.$_SERVER['SCRIPT_NAME'];
$base_path = str_replace('lib'.DIRECTORY_SEPARATOR.'Ruckusing'.DIRECTORY_SEPARATOR.'migrate_customer.php','', $base_path);
$base_path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $base_path);
define('APP_BASE_PATH', $base_path);
define('RUCKUSING_WORKING_BASE', $base_path.'lib'.DIRECTORY_SEPARATOR.'Ruckusing');
define('RUCKUSING_BASE', RUCKUSING_WORKING_BASE);

require_once(APP_BASE_PATH.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'_init.php');
$db_config = require RUCKUSING_WORKING_BASE.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'ruckusing.conf.php';

/*
 * Customer-Parameter gesetzt?
 */
$customerId = $argv[2];
if(!$customerId) {
	print "\nUsage: ".$argv[0]." customerId\n";
	exit;
}
$dbname = CONFIG_DB_NAME.'_'.$customerId;
$db = Db::getCustomerDb($customerId);

/*
 * Customer-DB connected?
 */
if(!$db->isConnected()) {
	print "\nCould not connect to database ".$dbname."\n";
	exit;
}

/*
 * Config anpassen, sodass Customer-Umgebung enthalten ist
 */
 
$db_config['db']['development']['database'] = $dbname;
$db_config['migrations_dir']['default'] = RUCKUSING_WORKING_BASE.DIRECTORY_SEPARATOR.'customer_migrations'; 


require_once RUCKUSING_BASE . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.inc.php';
require_once RUCKUSING_BASE . DIRECTORY_SEPARATOR . 'Factory.php';

$argv = array(
	$argv[0],
	'db:migrate'
);


$main = new Ruckusing_FrameworkRunner($db_config, $argv);
echo $main->execute();





// $dir = APP_BASE_PATH . 'lib' . DIRECTORY_SEPARATOR . 'RuckusingCustomers' . DIRECTORY_SEPARATOR;

// $command = 'cd ' . $dir . ';php main.php db:migrate dbname:' . $dbname;

// echo($command."\n");
// break;

// system($command);