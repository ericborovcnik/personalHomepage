<?php
/**
 * Datensatz aus der Tabelle userparam
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-11	eb	from scratch
 */
class Model_Userparam extends Model_Base {

	//
	//	GET-Methoden
	//
	
	public function getUserId() {
		return $this->__get('user_id');
	}

	public function getParamkey() {
		return $this->__get('paramkey');
	}	
	
	public function getParamvalue() {
		return $this->__get('paramvalue');
	}
	
	//
	//	SET-Methoden
	//
	
	public function setParamkey($paramkey) {
		$this->__set('paramkey', $paramkey);
	}
	
	public function setParamvalue($paramvalue) {
		$this->__set('paramvalue', $paramvalue);
	}
	
}