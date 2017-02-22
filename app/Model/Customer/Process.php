<?php
/**
 * customer_process-record
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-21	eb	from scratch
 */
class Model_Customer_Process extends Model_Base {

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
	/**
	 * @return Model_Process
	 */
	public function getProcess() {
		return Db::tProcess()->find($this->getProcessId());
	}
	public function getProcessId() {
		return $this->__get('process_id');
	}
	public function getSort() {
		return $this->__get('sort');
	}

	//
	//	SET-Methoden
	//
	public function setCustomerId($customerId) {
		$this->__set('customer_id', $customerId);
	}
	public function setProcessId($processId) {
		$this->__set('process_id', $processId);
	}
	public function setSort($sort) {
		$this->__set('sort', $sort);
	}

}