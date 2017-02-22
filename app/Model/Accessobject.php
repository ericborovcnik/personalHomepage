<?php
/**
 * Datensatz aus der Tabelle accessobject
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-21	eb	from scratch
 */
class Model_Accessobject extends Model_Base {

	//
	//	GET-Methoden
	//
	public function getModuleId() {
		return $this->__get('module_id');
	}

	public function getCode() {
		return $this->__get('code');
	}

	public function getName() {
		return $this->__get('name');
	}

	public function getDescription() {
		return $this->__get('description');
	}

	//
	//	SET-Methoden
	//
	
	public function setModuleId($moduleId) {
		$this->__set('module_id', $moduleId);
	}

	public function setCode($code) {
		$this->__set('code', $code);
	}

	public function setName($name) {
		$this->__set('name', $name);
	}

	public function setDescription($description) {
		$this->__set('description', $description);
	}

}