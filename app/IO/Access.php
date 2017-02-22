<?php
/**
 * Zugriffsverwaltung
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-18	eb	from scratch
 */
class IO_Access extends IO_Base {
	
	const CHECK_LOGIN = true;
	const ACCESS = 'core_usermanagement';

	/*
	 * Benutzergruppen
	 */
	
	/**
	 * Ermittelt die Benutzergruppen
	 * @return jsontable
	 */
	public function usergroup_get() {
		if(!User::canRead('core_usermanagement'))								return $this->jsonNoAccess();
		return Db::tUsergroup()->getJsonTable($this->params);
	}

	/**
	 * Aktualisiert die Benutzergruppe
	 */
	public function usergroup_set() {
		if(!User::canWrite('core_usermanagement'))							return $this->jsonNoAccess();
		$t = Db::tUsergroup();
		
		switch($this->params->id) {
			case $t->getAdmin()->getId():
			case $t->getUser()->getId():
			case $t->getGuest()->getId():	return $this->jsonError($this->_('This usergroup is reserved and may not be changed'));	break;
		}
		
		// Switch Felder
		switch($this->params->field) {
			case 'code':
				if($this->params->value == '') {
					$this->addError($this->_('Code may not be empty'));
				} else {
					$u = $t->findByCode($this->params->value);
					if($u && $u->getId() != $this->params->id) {
						$this->addError($this->_('Code already exists'));
					}
					if ($this->params->value !== strtolower($this->params->value)) $this->addError($this->_('Code must be lowercase'));
					
				}
				break;
			case	'name':
				if($this->params->value == '')	{
					$this->addError($this->_('Group may not be empty'));
				}	else{
					$row = $t->findByName($this->params->value);
					if($row && $row->getId() != $this->params->value){
						$this->addError($this->_('Group already exists'));
					}
				}
				
				break;
		}
		if(!$this->hasError()) {
			$t->update(array($this->params->field => $this->params->value), 'id='.$this->params->id);
		}
		return $this->jsonResponse();
	}
	
	/**
	 * Erfasst eine Benutzergruppe
	 */
	public function usergroup_add() {
		if(!User::canWrite('core_usermanagement'))							return $this->jsonNoAcess();
		$t = Db::tUsergroup();
		$errfields = array();
		$errmsg = '';
		
		//	code empty?
		if($this->params->code == '')													$this->addError($this->_('Code may not be empty'),	'code');
		if($t->findByCode($this->params->code,$all=false))		$this->addError($this->_('Code already exists'), 		'code');
		if($this->params->name == '')													$this->addError($this->_('Group may not be empty'),	'name');
		if($t->findByName($this->params->name))								$this->addError($this->_('Group already exists'),		'name');
		
		if ($this->params->code !== strtolower($this->params->code)) $this->addError($this->_('Code must be lowercase'), 		'code');
		
		if(!$this->hasError()) {
			$t->insert(array(
				'code'				=>	$this->params->code,
				'name'				=>	$this->params->name,
				'description'	=>	$this->params->description
			));
		}
		return $this->jsonResponse();
	}
	
	/**
	 * Entfernt eine Benutzergruppe
	 * @return json|string
	 */
	public function usergroup_del() {
		if(!User::canWrite('core_usermanagement'))							return $this->jsonNoAccess();
		$t = Db::tUsergroup();
		switch($this->params->id) {
			case $t->getAdmin()->getId():
			case $t->getUser()->getId():
			case $t->getGuest()->getId():			return $this->jsonError($this->_('This usergroup is reserved and may not be removed'));	break;
		}
		$group = $t->find($this->params->id);		//	@var Model_Usergroup
		if(!$group)													return $this->jsonError($this->_('Usergroup not found'));
		if($group->getUsers()->count())			return $this->jsonError($this->_('Usergroup contains users.|Deletion not allowed.'));
		$t->delete("id=".Db::quote($this->params->id));
		return $this->jsonResponse($this->_('Usergroup successfully deleted'));
	}	
	
	/**
	 * Erzeugt eine Kopie einer Benutzergruppe
	 */
	public function usergroup_duplicate() {
		if(!User::canWrite('core_usermanagement'))							return $this->jsonNoAccess();
		$t = Db::tUsergroup();
		$sgroup = $t->find($this->params->id);		/* @var $sgroup Model_Usergroup	*/
		if(!$sgroup)												return $this->jsonError($this->_('Usergroup not found'));
		$sgroup->copy();
		return $this->jsonResponse();
	}

	/**
	 * Erzeugt einen Excel-Bericht mit den Benutzergruppen- und Rechte-Spezifikationen
	 */
	public function usergroup_excel() {
		$xl = New Export_XL(array(
			'browser'			=>	true,
			'report'			=>	$this->_('User- & Accessrights'),
			'orientation'	=>	'landscape',
			'pageswide'		=>	1
		));
		
		// Erzeuge die Tabelle mit den Benutzergruppen
		$xl->addSheet($this->_('Usergroups'));
		$usergroupsSheet = DB::tUsergroup()->fetchAll();
		
		// Header
		$xl->write(array(
			$this->_('id'),
			$this->_('Code'),
			$this->_('Name'),
			$this->_('Description')
		));
		$xl->setColumnWidth(80,4,4);					// Desciption Spalte
		$xl->setStyle('middle', 1, 1, 1,-1);
		$xl->newline();
		
		// Daten
		foreach($usergroupsSheet as $usergroup){
			$xl->write(array(
				$usergroup->getId(),
				$usergroup->getCode(),
				$usergroup->getName(),
				$usergroup->getDescription()
			));
			$xl->newline();
		}
	
		// Erzeuge die Tabelle mit den Benutzer-Details
		$xl->addSheet($this->_('Users'));
		$usersSheet = Db::tUser()->fetchAll();
		// Header
		$xl->write(array(
			$this->_('id'),
			$this->_('Usergroup'),
			$this->_('Loginname'),
			$this->_('Username'),
			$this->_('Email'),
			$this->_('Language'),
			$this->_('Locale'),
			$this->_('Lastlogin'),
			$this->_('Logincount'),
			$this->_('active_from'),
			$this->_('active_to')
		));
		
		$xl->setColumnWidth(15,2,4);			// Usergroup,Loginname, Username Spalte
		$xl->setColumnWidth(35,5,5);			// Email Spalte
		$xl->setColumnWidth(20,6,11);			// language, locale, lastlogin, logincount, active_from, active_to Spalte
		
		$xl->newline();
		
		// Daten
		foreach ($usersSheet as $user)	{
			$xl->write(array(
				$user->getId(),
				$user->getUsergroup()->getName(),
				$user->getLoginname(),
				$user->getUsername(),
				$user->getEmail(),
				$user->getLanguage(),
				$user->getLocale(),
				$user->getLastlogin(),
				$user->getLogincount(),
				$user->getActiveFrom(),
				$user->getActiveTo()
			));
			$xl->newline();
		}
	
		// Erzeuge die Zugriffsrechtetabellen für alle Gruppen
		$usergroups = Db::tUsergroup()->fetchAll(null,'code')->toArray();
		foreach($usergroups as $usergroup) {
			$xl->addSheet($this->_('Group').' '.$usergroup['code']);
			$users = Db::tUser()->fetchAll('usergroup_id='.$usergroup['id']);
			
			// Header
			$xl->write(array(
				$this->_('id'),
				$this->_('Usergroup'),
				$this->_('Loginname'),
				$this->_('Username'),
				$this->_('Password'),
				$this->_('Email'),
				$this->_('Language'),
				$this->_('Locale'),
				$this->_('Lastlogin'),
				$this->_('Logincount'),
				$this->_('active_from'),
				$this->_('active_to')
			));
			
			$xl->newline();
			
			foreach ($users as $user)	{
				// Daten
				$xl->write(array(
					$user->getId(),
					$user->getUsergroup()->getName(),
					$user->getLoginname(),
					$user->getUsername(),
					$user->getPassword(),
					$user->getEmail(),
					$user->getLanguage(),
					$user->getLocale(),
					$user->getLastlogin(),
					$user->getLogincount(),
					$user->getActiveFrom(),
					$user->getActiveTo()
				));
				$xl->newline();
			}

		}
		$xl->save();

	}
	
	
	/*
	 * Benutzer
	 */
	
	/**
	 * Ermittelt die Benutzer zur gewählten Gruppe
	 */
	public function user_get() {
		if(!User::canRead('core_usermanagement'))			return $this->jsonNoAccess();
		if(!is_numeric($this->params->parentid))		return $this->jsonResponse('no parentid given');
		if($this->params->showAllUsers=='true')	{
			$where = array('usergroup_id='.$this->params->parentid);
		}	else{
			$where = array(
				'usergroup_id='.$this->params->parentid,
				'isnull(active_to) or active_to ="0000-00-00" or active_to>'.Db::quote(Date::get('today'))
			);
		}
		
		$substitutes = array(
			'id'				=>	'user.id',
			'usergroup'	=>	'usergroup.name'
		);
	  
		return Db::tUser()->getJsonTable($this->params,$where, $substitutes);
	}
	
	/**
	 * Aktualisiert einen Benutzer
	 */
	public function user_set()	{
		if(!User::canWrite('core_usermanagement'))		return $this->jsonNoAccess();
		$t = Db::tUser();
		
		switch($this->params->id) {
			case $t->getAdmin()->getId():
			case $t->getGuest()->getId():	return $this->jsonError($this->_('This user is reserved and may not be changed'));	break;
		}
		
		switch($this->params->field) {
			// Selectbox Usergroup
			case 'usergroup':		$this->params->field = 'usergroup_id';		break;
			
			//	Loginname
			case 'loginname':
				if($this->params->value == '')	{
					return $this->jsonError($this->_('Username may not be empty.'));
				}	else{
					$u = $t->findByLoginname($this->params->value, Date::get('today'));
					
					// Usernamen muss eindeutig sein
					if($u && $u->getId() != $this->params->id)	return $this->jsonError($this->_('Username already exists.'));
					
					// Username muss kleingeschrieben sein
					if ($this->params->value !== strtolower($this->params->value)) return $this->jsonError($this->_('Username must be lowercase'));
				}
			break;
				
			//	Name
			case 'username':
				if($this->params->value == '')	return $this->jsonError($this->_('Name may not be empty.'));
				break;
				
			// Passwort							
			case 'password':
				if($this->params->value == '')	return $this->jsonError($this->_('Password may not be empty.'));
				$this->params->value = hash('sha512', $this->params->value, false);
				break;
			
			// Aktive bis
			case 'active_to':
				if($this->params->value== null || $this->params->value =='' || $this->params->value >= date::get('today')){
					$actualRow = $t->fetchRow('id='.$this->params->id);
					if ($actualRow) $actualLoginName = $actualRow->getLoginname();
					
					$today = Date::get('today');
					$otherRow = $t->fetchRow('id!='.$this->params->id.' and user.loginname LIKE '.Db::quote($actualLoginName).' and (isnull(active_to) or active_to="0000-00-00")');
					if($otherRow) $other = $otherRow->getLoginname();
					
					if($other == $actualLoginName)	return  $this->jsonError($this->_('A user with this name is already active.'));
				}	
				break;
				
		}	
		
		if(!$this->hasError()) {
			$t->update(array($this->params->field => $this->params->value), 'id='.$this->params->id);
		}
				
	}
	
	/**
	 * Erfasst einen Benutzer
	 */
	public function user_add()	{
		if(!User::canWrite('core_usermanagement'))							return $this->jsonNoAcess();
		$t = Db::tUser();
		
		$today = Date::get('today');
		$errfields = array();
		$errmsg = '';
		
		// Überprüfungen Feld Loginname
		if($this->params->loginname == '')		$this->addError($this->_('Username may not be empty.'),	'loginname');
		if($t->findByLoginname($this->params->loginname, $today)) $this->addError($this->_('Username already exists.'), 		'loginname');
		if ($this->params->loginname !== strtolower($this->params->loginname)) $this->addError($this->_('Username must be lowercase'), 	'loginname');
		
		// Überprüfungen Feld Name
		if($this->params->username == '')			$this->addError($this->_('Name may not be empty.'),	'username');
		
		// Überprüfungen Feld Passwort
		if($this->params->password == '')			$this->addError($this->_('Password may not be empty.'),	'password');
		
		if(!$this->hasError()) {
			$t->insert(array(
				'usergroup_id'	=>	$this->params->usergroup_id,
				'loginname'			=>	$this->params->loginname,
				'username'			=>	$this->params->username,
				'password'			=>	hash('sha512', $this->params->password, false),
				'email'					=>	$this->params->email,
				'language'			=>	CONFIG_LOCALE_LANGUAGE,
				'locale'				=>	CONFIG_LOCALE_CODE,
				'lastlogin'			=>	null,
				'logincount'		=>	0,
				'active_from'		=>	$today,
				'active_to'			=>	null
			));
		}
		return $this->jsonResponse();
	}
	
	/**
	 * Entfernt einen Benutzer
	 */
	public function user_del()	{
		if(!User::canWrite('core_usermanagement'))		return $this->jsonNoAccess();
		$today = Date::get('today');
		$t = Db::tUser();
		
		switch($this->params->id) {
			case $t->getAdmin()->getId():
			case $t->getGuest()->getId():	return $this->jsonError($this->_('This user is reserved and may not be changed'));	break;
		}
		
		$t->update(array('active_to'	=>	$today),'id='.$this->params->id);
		
		return $this->jsonResponse($this->_('User successfully deleted'));
	}
	
	/**
	 * Erstellt einen Excel-Bericht mit allen Benutzern einer bestimmen Benutzergruppe
	 */
	public function user_excel() {
		if(!User::canRead('core_usermanagement'))			return $this->jsonNoAccess();
		$xl = New Export_XL(array(
			'browser'			=>	true,
			'report'			=>	$this->_('Users'),
			'orientation'	=>	'landscape',
			'pageswide'		=>	1
		));
		
		$xl->addSheet($this->_('Users'));
		
		if($this->params->showAllUsers=='true')	{
			$users = Db::tUser()->fetchAll('usergroup_id='.$this->params->parentid);
		}	else{
			$users = Db::tUser()->fetchAll('usergroup_id='.$this->params->parentid.' AND (isnull(active_to) OR active_to ="0000-00-00" or active_to>'.Db::quote(Date::get('today')).')');
		}
		
		// Header
		$xl->write(array(
			$this->_('id'),
			$this->_('Usergroup'),
			$this->_('Loginname'),
			$this->_('Username'),
			$this->_('Email'),
			$this->_('Language'),
			$this->_('Locale'),
			$this->_('Lastlogin'),
			$this->_('Logincount'),
			$this->_('active_from'),
			$this->_('active_to')
		));
		
		$xl->setColumnWidth(15,2,4);				// Usergroup,Loginname, Username Spalte
		$xl->setColumnWidth(35,5,5);				// Email Spalte
		$xl->setColumnWidth(20,6,11);				// language, locale, lastlogin, logincount, active_from, active_to Spalte
		$xl->newline();
		
		foreach ($users as $user)	{
			// Daten
			$xl->write(array(
				$user->getId(),
				$user->getUsergroup()->getName(),
				$user->getLoginname(),
				$user->getUsername(),
				$user->getEmail(),
				$user->getLanguage(),
				$user->getLocale(),
				$user->getLastlogin(),
				$user->getLogincount(),
				$user->getActiveFrom(),
				$user->getActiveTo()
			));
			$xl->newline();
		}
				
		$xl->save();
	}
	
	/*
	 * Accessobject
	 */
	
	/**
	 * Ermittelt die Zugriffrechte einer gewählten Benutzergruppe
	 */
	public function accessobject_get()	{
		if(!User::canRead('core_usermanagement'))			return $this->jsonNoAccess();
		$accesobjectArray = Db::tAccessobject()->fetchAll();
		$rs=array();
		
		foreach( $accesobjectArray as $accessobject)	{
			// Zugriffsrecht vorhanden
			$testAccess = Db::tAccess()->getAccess($this->params->parentid, $accessobject->getId());
			
			$bool=1;
			$cnt=0;
			// Falls kein Zugriffsrecht vorhanden bool 0
			if(empty($testAccess)) $bool=0;
			$cnt++;
			$rs[] = array(
				'id'					=>	$accessobject->getId(),
				'code'				=>	$accessobject->getCode(),
				'name'				=>	$accessobject->getName(),
				'description'	=>	$accessobject->getDescription(),
				'bool'				=>	$bool
			);
		}
				
		return Zend_Json::encode(array(
			'success'				=>	true,
			'totalCount'		=>	$cnt,
			'data'					=>	$rs
		));
	}
	
	/**
	 * Aktualisiert die Zugriffsrechte einer gewählten Benutzergruppe
	 */
	public function accessobject_set()	{
		if(!User::canRead('core_usermanagement'))			return $this->jsonNoAccess();
		$accessTable = Db::tAccess();
		
		switch($this->params->field) {
			case 'code':						return $this->jsonError("Code is not available");								break;
			case 'name': 						return $this->jsonNoAccess("Name is not available");						break;
			case 'description':			return $this->jsonNoAccess("Description is not available");			break;
			case 'bool':
				switch($this->params->parentid) {
					case Db::tUsergroup()->getAdmin()->getId():	return $this->jsonError($this->_('This usergroup is reserved and may not be changed'));	break;
				}
				
				if($this->params->value == 'true'){
					$accessTable->insert(array(
						'usergroup_id'		=>	$this->params->parentid,
						'accessobject_id'	=>	$this->params->id,
						'access'					=>	'write'			// Muss noch definiert werden
					));
			}	else{
				$accessTable->delete('usergroup_id='.$this->params->parentid.' and accessobject_id='.$this->params->id);
			}
			break;
					
		}
		
	}
	
	/**
	 * Erzeugt einen Excel-Bericht mit den Benutzergruppen- und Rechten
	 */
	public function accessobject_excel() {
		$xl = New Export_XL(array(
			'browser'			=>	true,
			'report'			=>	$this->_('Accessrights'),
			'orientation'	=>	'landscape',
			'pageswide'		=>	1
		));
		
		$usergroups = Db::tUsergroup()->fetchAll('id='.$this->params->usergroup)->toArray();
		foreach($usergroups as $usergroup) {
			$xl->addSheet($this->_('Group').' '.$usergroup['code']);
			$xl->setColumnWidth(30,1,3);
			$xl->setColumnWidth(75,4,4);
			$accessData = Db::tAccess()->fetchAll('usergroup_id='.$usergroup['id']);
			
			// Header
			$xl->write(array(
				$this->_('Access'),
				$this->_('Code'),
				$this->_('Name'),
				$this->_('Description'),
			));
				
			$xl->newline();
				
			foreach ($accessData as $access)	{
				// @todo find parent row funktioniert nicht 
				$accessobject = Db::tAccessobject()->fetchRow('id='.$access->getAccessobjectId());
				// Daten
				$xl->write(array(
					$access->getAccess(),
					$accessobject->getCode(),
					$accessobject->getName(),
					$accessobject->getDescription()
				));
				$xl->newline();
			}
	
		}
		$xl->save();
	}
	
}
