<?php
/**
 * Zentraler Einstiegspunkt für alle Anfragen vom Client
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-11-26	eb	Initialisiert ab revis
 */

set_error_handler("exception_error_handler", E_ERROR & E_RECOVERABLE_ERROR);

if(isset($_SERVER['argv'])) {
	/*
	 * Start ab Konsole? (fork)
	 */
	define('APP_BASE_PATH', $_SERVER['PWD'].'/');
	require_once APP_BASE_PATH.'app/_init.php';
	Log::out(array(
		'calling server.php by shell',
		$_SERVER['argv']
	));
} else {
	/*
	 * Prozess vom Browser gestartet
	 */
 	define('APP_BASE_PATH', realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . '/');
 	require_once APP_BASE_PATH.'app/_init.php';
	echo processRequest(array_merge($_POST, $_GET));
}

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function processRequest($params) {
	$module = 'IO_'.$params['module'];
	$action = $params['action'];
	if(!$action)			$action = 'run';
	$params['module'] = $module;
	$params['action'] = $action;

	/*
	 * Mini-Initialisierungssequenz um die Sitzung zu etablieren
	 */
	Session::start();
	
	/*
	 * Module exist?
	 */
	$class = Util::getClass($module);
	if(!$class) {
		$msg = '['.$module.'] - invalid module';
		Log::err($msg);
		return Util::jsonError($msg);
	}
	/*
	 * Class from IO_Factory?
	 */
	if(!is_subclass_of($class, 'IO_Base')) {
		$msg = '['.$module.'] - no subclass of IO_Base. Access denied';
		Log::err($msg);
		return Util::jsonError($msg);
	}
	/*
	 * Class supports action-method?
	 */
	if(!method_exists($class, $action)) {
		$msg = '['.$class.'::'.$action.'] - method not implemented';
		Log::err($msg);
		return Util::jsonError($msg);
	}
	/*
	 * Class enforces Login-State?
	 */
	if($class::CHECK_LOGIN) {
		if(!Session::isLoggedIn()) {
			$msg = 'User-Login required';
			Log::debug($msg);
			return Util::jsonLoginRequired();
		}
	}
	/*
	 * Class enforces access-right
	 */
	if($class::ACCESS) {
		if(!User::canRead($class::ACCESS)) {
			$msg = '['.$class::ACCESS.'] - access denied';
			Log::debug($msg);
			return Util::jsonNoAccess();
		}
	}
	try {
		$obj = new $class(Util::arrayToObject($params));
		return $obj->$action();
	} catch(Exception $e) {
		Log::err($e->getMessage(),$e->getTraceAsString());
		return Util::jsonError($e->getMessage());
	}

}
