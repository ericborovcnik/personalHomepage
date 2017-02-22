<?php
/*
 * Load config
 */

if (!defined('APP_BASE_PATH')) {
	//es gab einen grund warum APP_BASE_PATH vor _init deklariert wurde, jedoch nicht wirklich nötig
	define('APP_BASE_PATH', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
}
require_once APP_BASE_PATH.'config/config.php';

/*
 * Set INI-Variables
 */
ini_set('error_reporting',       E_ALL ^ E_NOTICE);
ini_set('error_log',             APP_BASE_PATH.'debug.log');
ini_set('include_path',          APP_BASE_PATH.'lib/'.PATH_SEPARATOR.APP_BASE_PATH.'app/');
ini_set('max_execution_time',    0);
ini_set('output_handler',       'ob_gzhandler');
ini_set('memory_limit',         '1024M');
ini_set('post_max_size',        '20M');
ini_set('upload_max_filesize',  '20M');

/*
 * Specify Autoloader
 */
spl_autoload_register(function($class) {
	$root = APP_BASE_PATH.'app'.DIRECTORY_SEPARATOR;
	$searchPath = array(
		'Zend'				=>	APP_BASE_PATH.'lib'.DIRECTORY_SEPARATOR,
		'Ruckusing'		=>	APP_BASE_PATH.'lib'.DIRECTORY_SEPARATOR.'Ruckusing'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR,
		'PHPExcel'		=>	APP_BASE_PATH.'lib'.DIRECTORY_SEPARATOR
	);
	foreach($searchPath as $key => $path) {
		if(strpos($class, $key.'_') === 0) {
			$root = $path;
			break;
		}
		if($class == $key) {
			$root = $path;
			break;
		}
	}
	$class = $root.str_replace('_', DIRECTORY_SEPARATOR, $class).'.php';
	@include $class;
});

date_default_timezone_set(CONFIG_LOCALE_TIMEZONE);