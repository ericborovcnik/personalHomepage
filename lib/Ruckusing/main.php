<?php 
/**
 * Einstiegsprozess für die Ruckusing-Migrationen
 * php main.php db:migrate [dbname:databasename]
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-15	eb	from scratch
 */
 
$base_path = $_SERVER['PWD'].DIRECTORY_SEPARATOR.$_SERVER['SCRIPT_NAME'];
$base_path = str_replace('lib'.DIRECTORY_SEPARATOR.'Ruckusing'.DIRECTORY_SEPARATOR.'main.php','', $base_path);
$base_path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $base_path);
define('APP_BASE_PATH', $base_path);
define('RUCKUSING_WORKING_BASE', $base_path.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Ruckusing');
define('RUCKUSING_BASE', RUCKUSING_WORKING_BASE);

require_once(APP_BASE_PATH.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'_init.php');
$db_config = require RUCKUSING_WORKING_BASE.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'ruckusing.conf.php';

/*
 * Ist die Zieldatenbank durch dbname: - Parameter gesetzt?
 */
for($i=0; $i<count($argv); $i++) {
	if(strpos($argv[$i], 'dbname:') === 0) {
		$db_config['db']['development']['database'] = substr($argv[$i], strpos($argv[$i], ':') + 1);
		unset($argv[$i]);
	}
}

require_once RUCKUSING_BASE.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.inc.php';
require_once RUCKUSING_BASE.DIRECTORY_SEPARATOR.'Factory.php';

$main = new Ruckusing_FrameworkRunner($db_config, $argv);
echo $main->execute();
