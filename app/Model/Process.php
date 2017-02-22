<?php
/**
 * process-record
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-21	eb	from scratch
 */
class Model_Process extends Model_Base {

	//
	//	GET-Methoden
	//
	/**
	 * @return Model_Customer
	 */
	public function getCustomer() {
		return Db::tCustomer()->find($this->getCustomerId());
	}
	
	public function getCustomerId() {
		return $this->__get('customer_id');
	}
	
	//
	//	SET-Methoden
	//
	

}