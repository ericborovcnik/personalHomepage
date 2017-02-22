<?php
/**
 * Generische Modul-Klasse
 * Eine Modul-Klasse gibt Auskunft über Menü- und Navigationselemente und bietet den Katalog der Zugriffsobjekte an
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-13	eb	from scratch
 *
 */
abstract class Module_Base {
	
	const MODULE_NAME					= '';				//	Modul-Bezeichnung
	const MODULE_DESCRIPTION	= '';				//	Modul-Beschreibung
	const MODULE_ACTIVE				= false;		//	Modul initial aktiv?
	
	/**
	 * Registriert das Module anhand der MODULE_-Konstanten,
	 */
	public static function register() {
		$code = static::getCode();
		$module = static::getModule();
		if(!$module) {
			Db::tModule()->insert(array(
				'code'				=>	$code,
				'name'				=>	static::MODULE_NAME,
				'description'	=>	static::MODULE_DESCRIPTION,
				'active'			=>	static::MODULE_ACTIVE ? 1 : 0
			));
		}
		
	}

	/**
	 * Initialisiert alle aktiven Module aus der Tabelle module
	 */
	public static function initializeModules() {
		$modules = Db::tModule()->getModuleList();
		foreach($modules as $module => $active) {
			$class = Util::getClass($module);
			if($class) {
				if($active) {
					$class::activate();
				} else {
					$class::deactivate();
				}
			}
		}
		
	}
	
	/**
	 * Aktiviert das Modul, indem die statischen Inhaltselemente registriert werden
	 */
	public static function activate() {
		/*
		 * Erzeuge alle Zugriffsrechte
		 */
		$module = static::getModule();
		if(!$module)		return;
		$tUsergroup = Db::tUsergroup();
		$tAccessObject = Db::tAccessobject();
		$tAccess = Db::tAccess();
		$admin = $tUsergroup->getAdmin();
		$user = $tUsergroup->getUser();
		$guest = $tUsergroup->getGuest();
		$objects = static::getAccessObjects();
		foreach($objects as $object) {
			/*
			 * Prüfen, ob das Zugriffsrecht bereits registriert ist.
			 * Ist dies noch nicht der Fall, dann erzeuge es und initialisiere die Zuordnungen für die Benutzergruppen Admin, User, Guest
			 */
			$uao = $tAccessObject->fetchRow('code='.Db::quote($object['code']));
			if(!$uao) {
				$object['module_id'] = $module->getId();
				//	right does not exist - create right and accesslist
 				$accessobjectId = $tAccessObject->insert($object);
 				for($i=0; $i<=2; $i++) {
 					$access = substr($object['acl'], $i, 1);
 					switch($i) {
 						case 0:		$groupId = $admin->getId();		break;
 						case 1:		$groupId = $user->getId();		break;
 						case 2:		$groupId = $guest->getId();		break;
 					}
 					$tAccess->setAccess($groupId, $accessobjectId, $access);
 				}
			}
		}
	} 
	
	/**
	 * Deaktiviert das Modul, indem die statischen Inhaltselemente deregistriert werden
	 */
	public static function deactivate() {
		/*
		 * Eliminiere alle Zugriffsrechte
		 */
		$code = get_called_class();
		
		// 		Log::out('deactivate '.get_class($static));
	}

	/**
	 * Ermittelt den Katalog an Zugriffsobjekten zum Modul
	 * @return array											[[code, name, description, acl]]
	 */
	public static function getAccessObjects() {
		return array();
	}
	
	/**
	 * Ermittelt ein mehrdimensionales Array mit den administrativen Menü-Elementen
	 * @return array											[[text,icon,access,action[text,icon,access,action[...]]]
	 */
	public static function getAdministration() {
		return array();
	}
	
	/**
	 * Ermittelt ein zweidimensionales Array mit den Navigationselementen
	 * @return array											[[text,action,access,tasks[[text,action,access]]
	 */
	public static function getNavigation() {
		return array();
	}
	
	/**
	 * Ermittelt die Bezeichnung der aktuellen Nutzklasse
	 * @return string
	 */
	public static function getClassName() {
		return get_called_class();
	}
	
	/**
	 * Extrahiert den Modul-Code anhand der Klasse
	 * @return string 
	 */
	public static function getCode() {
		$class = static::getClassName();
		return strtolower(substr($class, strrpos($class, 'Module_')+7));
	}
	
	/**
	 * Ermittelt den Modul-Datensatz als Ableitung der Klasse
	 * Dabei wird berücksichtigt, dass ein registriertes Modul auch ein Submodul sein kann (plan_company)
	 * rsp. dass es eine Kunden- oder Länderspezifische Ableitung sein kann (Cust_Abc_Module_Plan_Company)
	 * Es wird die Zeichenkette NACH Module_ extrahiert
	 * @return Model_Module								korrespondierender Modul-Datensatz
	 */
	public static function getModule() {
		$code = static::getCode();
		return Db::tModule()->fetchRow('code='.Db::quote($code));
	}
	
	/**
	 * Shortcut zur Übersetzungsmatrix
	 * @param string $key									Sprachschlüssel
	 * @param string|array $subst					Substitution
	 * @return string
	 */
	public static function _($key, $subst=null) {
		return Language::get($key, $subst);
	}
	
}