<?php
/**
 * Datensatz aus der Tabelle user
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-17	eb	from scratch
 */
class Model_User extends Model_Base {

	//
	//	GET-Methoden
	//
	
	public function getUsergroupId() {
		return $this->__get('usergroup_id');
	}
	
	/**
	 * @return Model_Usergroup
	 */
	public function getUsergroup() {
		return $this->findParentRow('Model_UsergroupTable');
	}

	public function getLoginname() {
		return $this->__get('loginname');
	}

	public function getUsername() {
		return $this->__get('username');
	}

	public function getPassword() {
		return $this->__get('password');
	}

	public function getEmail() {
		return $this->__get('email');
	}

	public function getLanguage() {
		return $this->__get('language');
	}

	public function getLocale() {
		return $this->__get('locale');
	}

	public function getLastlogin() {
		return $this->__get('lastlogin');
	}

	public function getLogincount() {
		return $this->__get('logincount');
	}

	public function getActiveFrom() {
		return $this->__get('active_from');
	}

	public function getActiveTo() {
		return $this->__get('active_to');
	}
	
	/*
	 * Advanced GET
	 */
	/**
	 * Ermittelt einen Benutzerparameter aus der Tabelle userparam
	 * @param string $paramKey						userparam.paramkey
	 * @param mixed $paramValue						Optionaler Zielwert, wenn kein Parameter vorliegt
	 * @return string											userparam.paramvalue
	 */
	public function getParam($paramKey, $paramValue='') {
		$tparam = Db::tUserparam();
		
		$param = $tparam->fetchRow(array(
			'user_id='.$this->getId(),
			'paramkey='.Db::quote($paramKey)
		));
		if(!$param) {
			$tparam->insert(array(
				'user_id'			=>	$this->getId(),
				'paramkey'		=>	$paramKey,
				'paramvalue'	=>	$paramValue
			));
			return $paramValue;
		}
		return $param->getParamvalue();
	}
	
	//
	//	SET-Methoden
	//
	public function setUsergroupId($usergroupId) {
		$this->__set('usergroup_id', $usergroupId);
	}
	
	public function setLoginname($loginname) {
		$this->__set('loginname', $loginname);
	}
	
	public function setLastlogin($lastlogin) {
		$this->__set('lastlogin', $lastlogin);
	}
	
	public function setLogincount($logincount) {
		$this->__set('logincount', $logincount);
	}
	
	public function setPassword($password) {
		$this->__set('password', $password);
	}
	
	public function setUsername($username) {
		$this->__set('username', $username);
	}
	
	public function setLanguage($language) {
		$this->__set('language', $language);
	}
	
	public function setEmail($email) {
		$this->__set('email', $email);
	}
	
	public function setLocale($locale) {
		$this->__set('locale', $locale);
	}
	
	public function setActiveFrom($activeFrom) {
		$this->__set('active_from', $activeFrom);
	}
	
	public function setActiveTo($activeTo) {
		$this->__set('active_to', $activeTo);
	}

	/*
	 * Advanced SET
	 */
	public function setParam($paramKey, $paramValue) {
		$tparam = Db::tUserparam();
		$param = $tparam->fetchRow(array(
			'user_id='.$this->getId(),
			'paramkey='.Db::quote($paramKey)
		));
		if(!$param) {
			$tparam->insert(array(
				'user_id'			=>	$this->getId(),
				'paramkey'		=>	$paramKey,
				'paramvalue'	=>	$paramValue
			));
		} else {
			$param->setParamvalue($paramValue);
			$param->save();
		}
	}
		
}