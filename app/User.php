<?php
/**
 * Bietet Methoden rund um die Benutzersitzung an
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-11-30	eb	from scratch
 * @version		2016-04-26	eb	getParam, setParam
 * @version		2016-06-17	eb	getCustomerId
 * @version		2016-06-29	eb	setCustomerId über Dateisystem, weil Session durch Echtzeit-Aurufe sich beissen
 *
 * @outline
 * $_SESSION['loginstate']							true, wenn der Benutzer angemeldet wurde
 * $_SESSION['userid']									Enthält die Benutzer-ID des aktuellen Benutzers
 * $_SESSION['language']								ISO-Sprachcode			de_CH, en_GB
 * $_SESSION['locale']									ISO-Lokalisierung 	de_CH, en_GB
 * $_SESSION['mediadir']								/media
 * $_SESSION['uploaddir']								/media/upload
 * $_SESSION['dbdir']										/media/upload/CONFIG_DB_NAME
 * $_SESSION['userdir']									/media/upload/CONFIG_DB_NAME/users/id
 * $_SESSION['tmpdir']									/media/upload/CONFIG_DB_NAME/users/id/tmp
 */
abstract class User {

	private static $_access = null;				//	Sammelt die Zugriffsobjekte mit [right] = 'read|write'
	

	/**
	 * Prüft, ob für den Benutzer ein Leserecht vorliegt
	 * @param string $access							Access-Tag
	 */
	public static function canRead($access) {
		if(!self::$_access)		self::resetAccess();
		if($access == '')		return true;
		$right = self::$_access[$access];
		switch($right) {
			case 'read':
			case 'write':
				return true;
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Prüft, ob für den Benutzer ein Schreibrecht vorliegt
	 * @param string $access
	 * @return boolean
	 */
	public static function canWrite($access) {
		if(!self::$_access)		self::resetAccess();
		$right = self::$_access[$access];
		if($right == 'write')		return true;
		return false;
	}

	/**
	 * Vermittelt die ACL-Zugriffsliste
	 * @return array											Hash-Array mit den Rechteobjekte als Key und read|write als Wert
	 */
	public static function getAccess() {
		return self::$_access;
	}

	/**
	 * Schreibt die aktuelle CustomerID in die Datei
	 * @param integer $id
	 */
	public static function setCustomerId($id) {
		$dir = User::getDir('session');
		$file = $dir.'/customerId';
		file_put_contents($file, $id);
	}
	
	/**
	 * Vermittelt die CustomerID des aktuellen Kunden.
	 * Gibt es keinen aktuellen Kunden, dann wird die erste zugängliche ID ermittelt.
	 * Gibt es keinen Kunden, dann wird 0 geliefert.
	 */
	public static function getCustomerId() {
		$dir = User::getDir('session');
		$file = $dir.'/customerId';
		$id = file_get_contents($file);
		if(!$id) {
			$customers = Db::tMember()->fetchUser();
			if($customers->count() >0) $id = $customers->current()->getCustomerId();
			User::setCustomerId($id);
		}
		return $id;
	}
	
	/**
	 * Ermittelt ein Standard-Verzeichnis ab Tag
	 * @param string $dirtag							Verzeichnis-Tag [mediadir, dbdir, uploaddir, userdir, tmpdir, customerdir]
	 * @return string											Verzeichnis
	 */
	public static function getDir($dirtag) {
		switch($dirtag) {
			case 'media':			$dir = 'media';																											break;
			case 'db':				$dir = 'media/'.CONFIG_DB_NAME;																			break;
			case 'upload':		$dir = 'media/upload';																							break;
			case 'user':			$dir = 'media/'.CONFIG_DB_NAME.'/users/'.self::getId();							break;
			case 'session':		$dir = 'media/'.CONFIG_DB_NAME.'/session/'.Session::getId();				break;
			case 'tmp':				$dir = 'media/'.CONFIG_DB_NAME.'/users/'.self::getId().'/tmp';			break;
			case 'customer':	$dir = 'media/'.CONFIG_DB_NAME.'/customers/'.self::getCustomerId();	break;
			default:		return;
		}
		@mkdir($dir, 0777, true);
		$_SESSION[$dirtag] = $dir;
		return $dir;
	}

	/**
	 * Ermittelt die BenutzergruppenID ab Session
	 * @return integer
	 */
	public static function getGroupId() {
		return $_SESSION['usergroupid'];
	}

	/**
	 * Ermittelt die BenutzerID, ist diese noch nicht gesetzt, dann wird ein Gast-Benutzer ermittelt
	 */
	public static function getId() {
		$id = $_SESSION['userid'];
		if($id === null)		$id = self::_getGuestId();
		return $id;
	}

	/**
	 * Ermittelt die Attributeliste zur Lokalisierung
	 */
	public static function getLocale() {
		$symbols = Zend_Locale_Data::getList($_SESSION['locale'], 'symbols');
		$symbols['iso'] = $_SESSION['locale'];
		$symbols['date'] = Zend_Locale_Data::getContent($_SESSION['locale'], 'date', 'short');
		$symbols['time'] = Zend_Locale_Data::getContent($_SESSION['locale'], 'time', 'short');
		$symbols['timelong'] = Zend_Locale_Data::getContent($_SESSION['locale'], 'time', 'medium');
		/*
		 * Convert ISO to PHP
		 */
 		$search 	= array('dd', 'MM', 'yyyy', 'HH', 'mm', 'ss', 'yy',	'M');
 		$replace 	= array('d',  'm',  'Y', 		'H', 	'i', 	's',	'Y',	'n');
 		$symbols['date'] = str_replace($search, $replace, $symbols['date']);
 		$symbols['time'] = str_replace($search, $replace, $symbols['time']);
 		$symbols['timelong'] = str_replace($search, $replace, $symbols['timelong']);
		return $symbols;
	}

	/**
	 * Ermittelt einen Benutzerparameter
	 * @param string $paramKey						userparam.paramkey
	 * @param string $paramValue					Optionaler Vorgabewert
	 */
	public static function getParam($paramKey, $paramValue='') {
		return User::getUser()->getParam($paramKey, $paramValue);
	}
	
	/**
	 * Ermittelt den aktuellen Benutzer
	 * @return Model_User
	 */
	public static function getUser() {
		return Db::tUser()->find(self::getId());
	}

	/**
	 * Ermittelt den aktuellen Benutzernamen
	 */
	public static function getUserName(){
		$user = self::getUser();
		return $user->loginname;
	}

	/**
	 * Prüft, ob der Benutzer angemeldet ist
	 */
	public static function isLoggedIn() {
		if($_SESSION['loginstate'] == true)		return true;
		return false;
	}

	/**
	 * Führt den Anmeldeprozess durch
	 * @param string $username						Benutzername
	 * @param string $password						SHA512-Hashwert des Passworts
	 */
	public static function login($username, $password) {
		//
		//	Ermittle einen aktiven Benutzer mit diesem Namen
		$user = Db::tUser()->fetch($username);
		if(!$user) {
			Log::err('['.$username.'] - invalid user. Login failed', $_SERVER);
			return false;
		}
		if($user->getPassword() != $password) {
			Log::err('['.$username.'] - invalid password. Login failed', $_SERVER);
			return false;
		}
		$_SESSION['loginstate']		= true;
		$_SESSION['userid']				=	$user->id;
		$_SESSION['usergroupid']	=	$user->usergroup_id;
		$_SESSION['language']			=	$user->language;
		$_SESSION['locale']				=	$user->locale;
		//
		//	Zugriffsobjekte bereitstellen
		self::resetAccess();
		//
		//	Session-Update
		self::setUser($user->id);
		//
		//	Medien-Verzeichnisse sicherstellen
		self::getDir('mediadir');
		self::getDir('uploaddir');
		self::getDir('dbdir');
		self::getDir('userdir');
		Util::cleanDir(self::getDir('tmpdir'), false);
		//
		//	Erstelle Protokolleintrag im userlog
		Db::tUserlog()->insert(array(
			'user_id'			=>	$user->id,
			'date'				=>	Date::get('now', 'Y-m-d H:i:s'),
			'host'			=>	$_SERVER['REMOTE_ADDR'],
			'agent'			=>	$_SERVER['HTTP_USER_AGENT']
		));
		$user->setLastlogin(Date::get('now', 'Y-m-d H:i:s'));
		$user->setLogincount($user->getLogincount()+1);
		$user->save();

		return true;
	}

	/**
	 * Vernichtet die Benutzersitzung
	 */
	public static function logout() {
		session::stop();
	}

	/**
	 * Reinitialisiert die Zugriffsrechte
	 */
	public static function resetAccess() {
		self::$_access = Db::tAccess()->getAccesslist(self::getGroupId());
	}

	/**
	 * Erzeugt einen Benutzerparameter
	 * @param string $paramKey						userparam.paramkey
	 * @param mixed $paramValue						userparam.paramvalue
	 */
	public static function setParam($paramKey, $paramValue) {
		User::getUser()->setParam($paramKey, $paramValue);
	}
	
	/**
	 * Aktualisiert den Session-Datensatz um den aktuellen Benutzer
	 * @param integer $userId
	 */
	public  static function setUser($userId=null) {
		if(!$userId) {
			$user = Db::tUser()->getGuest();
		} else {
			$user = Db::tUser()->find($userId);
		}
		$_SESSION['userid'] = $user->getId();
		$_SESSION['usergroupid'] = $user->getUsergroupId();
		Db::tSession()->update(array('user_id'=>$userId), 'id='.Db::quote(Zend_Session::getId()));
	}

	/*
	 * Private Methods
	 */

	/**
	 * Ermittelt die ID des Gast-Benutzers (und stellt Rahmenbedingungen sicher)
	 * @return integer
	 */
	private static function _getGuestId() {
		$db = Db::getDb();
		if($db->fetchOne('select id from user where id=0') === null) {
			if($db->fetchOne('select id from usergroup where id=0') === null) {
				$db->insert('usergroup', array(
					'id'				=>	0,
					'usergroup'	=>	'Guests'
				));
			}
			$db->insert('user', array(
				'id'						=>	0,
				'usergroup_id'	=>	0,
				'loginname'			=>	'guest',
				'password'			=>	'',
				'username'			=>	'Guest-User',
				'language'			=>	CONFIG_LOCALE_LANGUAGE,
				'locale'				=>	CONFIG_LOCALE_CODE,
			));
		}
		$_SESSION['userid'] = 0;
		$_SESSION['usergroupid'] = 0;
		return 0;
	}

}
