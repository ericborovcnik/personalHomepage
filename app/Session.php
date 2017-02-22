<?php
/**
 * Steuert die Session-Umgebung und die Interaktion mit dem angemeldeten Benutzer
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-11-27	eb	from scratch
 * @version		2016-06-29	eb	Unterstützt Session-Datei
 */
abstract class Session {

	/**
	 * Vermittelt die SessionId
	 * @return string
	 */
	public static function getId() {
		return Zend_Session::getId();
	}
	
	/**
	 * Prüft, ob die aktuelle Sitzung angemeldet ist
	 */
	public static function isLoggedIn() {
		if($_SESSION['loginstate'] == true)		return true;
		return false;
	}
	
	/**
	 * Initialisiert die Benutzersitzung
	 */
	public static function start() {
		Db::initDb();
		$config = array(
			'name'						=>	'session',
			'primary'					=>	'id',
			'modifiedColumn'	=>	'modified',
			'dataColumn'			=>	'data',
			'lifetimeColumn'	=>	'lifetime'
		);
		Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable($config));
		Zend_Session::start();
		if(!isset($_SESSION['userid']))		User::setUser();
		if(!isset($_SESSION['language']))	$_SESSION['language'] = CONFIG_LOCALE_LANGUAGE;
		if(!isset($_SESSION['locale']))		$_SESSION['locale'] = CONFIG_LOCALE_CODE;
	}
	
	/**
	 * Beendet die Benutzersitzung 
	 */
	public static function stop() {
		Util::cleanDir(User::getDir('session'));
		Zend_Session::destroy();
		unset($_SESSION);
	}
	
}