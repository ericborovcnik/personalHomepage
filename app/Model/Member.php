<?php
/**
 * Datensatz aus der Tabelle member
* @author			Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
* @copyright	WB Informatik AG (http://www.wb-informatik.ch)
* @version		2016-05-17	du	from scratch
*/
class Model_Member extends Model_Base {

	//	GET-Methoden
	
	/**
	 * @return Model_Customer
	 */
	public function getCustomer() {
		return Db::tCustomer()->find($this->getCustomerId());
	}
	
	public function getCustomerId()	{
		return $this->__get('customer_id');
	}
	
	public function getUserId()	{
		return $this->__get('user_id');
	}
	
	public function getRole()	{
		return $this->__get('role');
	}
	
	
	//	SET-Methoden
	
	public function setCustomerId($customerId)	{
		$this->__set('customer_id',$customerId);
	}
	
	public function setUserId($userId)	{
		$this->__set('user_id', $userId);
	}
	
	public function setRole($role)	{
		$this->__set('role', $role);
	}
		
}