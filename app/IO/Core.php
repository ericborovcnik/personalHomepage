<?php
/**
 * Initialisierungsmethoden
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-11	eb	init, login, logout
 */

class IO_Core extends IO_Base {
	
	const CHECK_LOGIN = false;
	const ACCESS = '';

	/**
	 * Initialisiert die Client-Benutzerumgebung (Zugriffsobjekte, Sprachschlüssel, Lokalisierung)
	 */
	public function init() {
		User::resetAccess();
		$user = User::getUser();
		Language::reset();
 		return Util::jsonSuccess(array(
 			'loginname'		=>	$user->getLoginname(),
 			'username'		=>	$this->_($user->getUsername()),
 			'usergroup'		=>	$this->_($user->getUsergroup()->getName()),
 			'language'		=>	Db::tLanguage()->fetchLanguage($_SESSION['language'], true),
 			'locale'			=>	User::getLocale(),
 			'session'			=>	Session::isLoggedIn(),
 			'toolbar'			=>	$this->_getToolbar($user),
 			'navigation'	=>	$this->_getNavigation(),
 			'access'			=>	User::getAccess()
 		));
	}
	
	/**
	 * Führt die Anmeldeprozedur durch, prüft BenutzerID und Passwort
	 */
	public function login() {
		//
		//	Gibt es einen aktiven Benutzer mit dieser ID?
		$user = db::tUser()->fetch($this->params->username);
		if(!$user) {
			return Util::jsonError(array(
				'code'	=>	1,
				'error'	=>	$this->_('User unknown')
			));
		}
		if($user->getPassword() != $this->params->password) {
			return Util::jsonError(array(
				'code'	=>	2,
				'error'	=>	$this->_('Wrong password')
			));
		}
		if(User::login($this->params->username, $this->params->password)) {
			return Util::jsonSuccess();
		}
	}

	/**
	 * Meldet die Sitzung ab
	 */
	public function logout() {
		User::logout();
	}

	/**
	 * Ermittelt die Toolbar für den aktuellen Benutzer
	 * @param Model_User $user
	 * @return array											Mehrdimensionales Array mit der Menustruktur für die Toolbar [text, tooltip, icon, action[]]
	 */
	private function _getToolbar($user) {
		$mnuRegister	=	array('text'=>$this->_('Register'),							'action'=>'app.user.register()');
		$mnuLogin			=	array('text'=>$this->_('Login'),								'action'=>'app.user.login()');
		$mnuLogout		=	array('text'=>$this->_('Logout'),								'action'=>'app.user.logout()');
		$mnuDocs			=	array('text'=>$this->_('Online-Documentation'),	'action'=>'console.log("online-documentation")');
		$mnuSupport		=	array('text'=>$this->_('Support'),							'action'=>'app.user.support()');
		
		if(Session::isLoggedIn()) {
			$mnuUser = array($mnuLogout);
			$mnuLogin['action'] = '';
			$mnuHelp = array($mnuDocs, $mnuSupport);
		} else {
			$mnuUser = array($mnuRegister);
			$mnuHelp = array($mnuDocs);
		}
		
		$toolbar = array(
			array(
				'text'		=>	$this->_($user->getUsername()).' ('.$this->_($user->getUsergroup()->getName()).')',
				'action'	=>	$mnuUser
			),
			$mnuLogin,
			$this->_getToolbarAdminMenu(),
			array('action'=>'-'),
			array(
				'text'		=>	$this->_('Help'),
				'action'	=>	$mnuHelp
			)
		);
		return $toolbar;		
	}
	
	/**
	 * Ermittelt die Navigation anhand der aktiven und verfügbaren Module
	 * @return array											Zweidimensionales Array mit Modulen und Task; ein Modul kann selbst sein Task sein [text, access, action, tasks[]]
	 */
	private function _getNavigation() {
		$modules = Db::tModule()->getModuleList(true);
		$navigation = array();
		foreach($modules as $key => $active) {
			$class = Util::getClass('Module_'.$key);
			$positions = $this->_getNavigationPositions(strtolower($key), $class::getNavigation());
			foreach($positions as $key => $position) {
				if(array_key_exists($key, $navigation)) {
					if(!is_array($position['tasks']))			continue;
					if(!is_array($navigation[$key]['tasks']))		$navigation[$key]['tasks'] = array();
					$navigation[$key]['tasks'] = array_merge($navigation[$key]['tasks'], $position['tasks']);
				} else {
					$navigation[$key] = $position;
				}
			}
		}
		return $navigation;
	}
	/**
	 * Prüft die Navigationselemente, ob die definierten Zugriffsrechte vorliegen
	 * @param string $module
	 * @param array $positions						Zweidimensionales Array mit Navigations-Positionen [text,accees,action,tasks[]]
	 */
	private function _getNavigationPositions($module, $positions) {
		/*
		 * Eliminiere alle Top-Positionen ohne Rechte
		 */
		foreach($positions as $posIdx => $position) {
			$access = $position['access'] ? $module.'_'.$position['access'] : '';
			if(!User::canRead($access)) {
				unset($positions[$posIdx]);
			} else {
				/*
				 * Eliminiere alel Task-Positionen ohne Rechte
				 */
				if(!is_array($position['tasks']))		continue;
				foreach($position['tasks'] as $taskIdx => $task) {
					$access = $task['access'] ? $module.'_'.$task['access'] : '';
					if(!User::canRead($access)) {
						unset($positions[$posIdx]['tasks'][$taskIdx]);
					}
				}
			}
		}
		return $positions;
	}

	/**
	 * Ermittelt die Menü-Struktur mit den Administrativen Positionen aller aktiven Module
	 * @return array											Struktur mit Menü-Positionen [text,icon,action[]]
	 */
	private function _getToolbarAdminMenu() {
		$modules = Db::tModule()->getModuleList(true);
		$menu = array();
		foreach($modules as $key => $active) {
			$class = Util::getClass('Module_'.$key);
			$positions = $this->_getToolbarAdminMenuPositions(strtolower($key), $class::getAdministration());
			$menu = array_merge($menu, $positions);
		}
		return array(
			'text'		=>	$this->_('Administration'),
			'action'	=>	$menu
		);
		
	}
	
	/**
	 * Rekursive Prüfung der Menü-Positionen, ob die definierten Zugriffsrechte vorliegen
	 * @param string $module							Modul-Key
	 * @param array $positions						Mehrdimensionales Array mit Menü-Positionen [text,access,icon,action[]]
	 * @return array											Mehrdimensionales Array mit Menü-Positionen nach Rechteprüfung
	 */
	private function _getToolbarAdminMenuPositions($module, $positions) {
		$result = array();
		foreach($positions as $position) {
			if($position['access']) {
				$access = $module.'_'.$position['access'];
			} else {
				$access = '';
			}
			if(User::canRead($access)) {
				$item = array(
					'text'			=>	$position['text'],
					'icon'			=>	$position['icon'],
				);
				if(is_string($position['action'])) {
					$item['action'] = $position['action']; 
				} else if(is_array($position['action'])) {
					$item['action'] = $this->_getToolbarAdminMenuPositions($module, $position['action']);
				}
				$result[] = $item;
			}
		}
		return $result;

	}
	

}