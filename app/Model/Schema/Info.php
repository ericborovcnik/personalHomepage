<?php
/**
 * Datensatz aus der Tabelle schema_info
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-21	eb	from scratch
 */
class Model_Schema_Info extends Model_Base {

	//
	//	GET-Methoden
	//
	public function getVersion() {
		$this->__get('version');
	}
	
	//
	//	SET-Methoden
	//
	public function setVersion() {
		$this->__set('version');
	}
	
}