<?php
abstract class Base_Model_Job extends Model_Base {

	//### SAVE

	/**
	 * automatically set unique_id on save
	 * @see Zend_Db_Table_Row_Abstract::save()
	 */
	public function save() {
		if (!$this->getUniqueId()) {
			$this->setUniqueId(uniqid() . '_' . rand(100, 999));
		}
		parent::save();
	}

	//### GET

	public function getUniqueId() {
		return $this->__get('unique_id');
	}

	public function getCommand() {
		return $this->__get('command');
	}

	public function getParams() {
		return $this->__get('params');
	}

	public function getPrio() {
		return $this->__get('prio');
	}

	public function getUserId() {
		return $this->__get('user_id');
	}

	public function getUserName($value) {
		return $this->__get('user_name');
	}

	public function getCreatedAt() {
		return $this->__get('created_at');
	}

	public function getStartedAt() {
		return $this->__get('started_at');
	}

	public function getIsRunning() {
		return $this->__get('is_running');
	}

	//### SET

	public function setUniqueId($value) {
		return $this->__set('unique_id', $value);
	}

	public function setCommand($value) {
		return $this->__set('command', $value);
	}

	public function setParams($value) {
		return $this->__set('params', $value);
	}

	public function setPrio($value) {
		return $this->__set('prio', $value);
	}

	public function setUserId($value) {
		return $this->__set('user_id', $value);
	}

	public function setUserName($value) {
		return $this->__set('user_name', $value);
	}

	public function setCreatedAt($value) {
		return $this->__set('created_at', $value);
	}

	public function setStartedAt($value) {
		return $this->__set('started_at', $value);
	}

	public function setIsRunning($value) {
		return $this->__set('is_running', $value);
	}

}