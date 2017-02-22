<?php
/**
 * Datensatz aus der Tabelle session
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-18	eb	from scratch
 */
class Model_Session extends Model_Base {

	//
	//	GET-Methoden
	//
	
	public function getUserId() {
		return $this->__get('user_id');
	}
	
	public function getModified() {
		return $this->__get('modified');
	}
	
	public function getLifetime() {
		return $this->__get('lifetime');
	}
	
	public function getData() {
		return $this->__get('data');
	}

	//
	//	SET-Methoden
	//
	
	public function setUserId($userId) {
		$this->__set('user_id', $userId);
		$this->save();
	}
	
}