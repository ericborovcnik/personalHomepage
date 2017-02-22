<?php
/**
 * Tabelle customer_process
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-21	eb	from scratch
 */
class Model_Customer_ProcessTable extends Model_BaseTable {
	
	/**
	 * Erzeugt einen neuen Datensatz
	 * @see Zend_Db_Table_Abstract::insert()
	 * @param integer $customerId					customer.id
	 * @param integer $processId					process.id
	 * @param integer $sort								Optionaler Sort-Schlüssel
	 */
	public function insert($customerId, $processId, $sort=null) {
		$data = array(
			'customer_id'	=>	$customerId,
			'process_id'	=>	$processId,
			'sort'				=>	$sort
		);
		try {
			return $this->find(parent::insert($data));
		} catch(Exception $e) {
			Log::err($e->getMessage());
		}
	}
	
	/**
	 * @see Model_BaseTable::find()
	 * @return Model_Customer_Process
	 */
	public function find() {
		return parent::find(func_get_args());
	}
	
	/**
	 * Ermittelt alle Prozesse eines Kunden
	 * @param integer $customerId					customer.id
	 */
	public function fetchCustomerProcesses($customerId) {
		return $this->fetchAll(array( 
			'customer_id'	=>	$customerId
		), 'sort');		 
	}
	
}