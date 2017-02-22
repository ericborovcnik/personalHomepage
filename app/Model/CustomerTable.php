<?php
/**
 * Tabelle 		Customer
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-05-17	du	from scratch
 */
class Model_CustomerTable extends Model_BaseTable {
	
	/**
	 * Ermittelt die Customer anhand des Codes
	 * @param string $code
	 * @return Model_Usergroup
	 */
	public function findByCode($code) {
		return $this->fetchRow('code='.Db::quote($code));
	}
	
	/**
	 * Ermittelt die Customer anhand des Namens
	 * @param string $code
	 * @return Model_Usergroup
	 */
	public function findByName($name) {
		return $this->fetchRow('name='.Db::quote($name));
	}
	
	/**
	 * Anlegen einens neuen Customers
	 * @param string $name
	 * @param string $code
	 * @param string $desc
	 */
	public function addCustomer($name, $code, $desc)	{
		$this->insert(array(
			'name'					=>	$name,
			'code'					=>	$code,
			'description'		=>	$desc
		));
		Model_Database::createCustomerDb($code);
	}
	
	/**
	 * Löschen eines Customers
	 * @param int			$id
	 * @param string	$code
	 */
	public function deleteCustomer($id, $code)	{
		$this->delete("id=".Db::quote($id));
		db::query('drop database '.$code.' ');
	}

}