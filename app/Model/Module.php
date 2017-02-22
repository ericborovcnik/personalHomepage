<?php
/**
 * Datensatz aus der Tabelle module
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-13	eb	from scratch
 */
class Model_Module extends Model_Base {

	//
	//	GET-Methoden
	//
	
	public function getCode() {
		return $this->__get('code');
	}
	
	public function getName() {
		return $this->__get('name');
	}	
	
	public function getDescription() {
		return $this->__get('description');
	}
	
	public function getActive() {
		return $this->__get('active');
	}
	
	public function getSort() {
		return $this->__get('sort');
	}
	//
	//	SET-Methoden
	//
	
	public function setCode($code) {
		$this->__set('code', $code);
	}
	
	public function setName($name) {
		$this->__set('name', $name);
	}
	
	public function setDescription($description) {
		$this->__set('description', $description);
	}
	
	public function setActive($active) {
		$this->__set('active', $active);
	}
	
	public function setSort($sort) {
		$this->__set('sort', $sort);
	}
	
}