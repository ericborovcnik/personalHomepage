<?php
/**
 * Datensatz aus der Tabelle userlog
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-18	eb	from scratch
 */
class Model_Userlog extends Model_Base {

	//
	//	GET-Methoden
	//
	
	public function getUserId() {
		return $this->__get('user_id');
	}
	
	public function getDate() {
		return $this->__get('date');
	}	
	
	public function getHost() {
		return $this->__get('host');
	} 
	
	public function getAgent() {
		return $this->__get('agent');
	}

}