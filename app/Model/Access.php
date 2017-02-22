<?php
/**
 * Datensatz aus der Tabelle access
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-21	eb	from scratch
 */
class Model_Access extends Model_Base {

	//	GET-Methoden

	public function getUsergroupId() {
		return $this->__get('usergroup_id');
	}


	public function getAccessobjectId() {
		return $this->__get('accessobject_id');
	}

	/**
	 * @return Model_Accessobject
	 */
	public function getAccessobject() {
		return $this->findParentRow('Model_Accessobject');
	}

	public function getAccess() {
		return $this->__get('access');
	}
	
	//	SET-Methoden

	public function setUsergroupId($usergroupId) {
		$this->__set('usergroup_id', $usergroupId);
	}

	public function setAccessobjectId($objectId) {
		$this->__set('accessobject_id', $objectId);
	}

	public function setAccess($access) {
		$this->__set('access', $access);
	}
	
}