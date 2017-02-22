<?php
/**
 * Tabelle		member
 * @author			Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-05-17	du	from scratch
 */
class Model_MemberTable extends Model_BaseTable {
	
	/**
	 * Ermittelt einen Member
	 * @param int	$customerId
	 * @param int $userId
	 */
	public function getMember($customerId, $userId)	{
		$rs = $this->select(true)
		->where('user_id=?',$userId)
		->where('customer_id=?',$customerId)
		->query()->fetchAll();
		return $rs;
	}
	
	/**
	 * Ermittelt einen dedizierten Customer-Datensatz zum aktuellen Benutzer, sofern einer vorliegt. Sonst NULL
	 * @param integer $customerId					customer_id
	 * @return Model_Customer
	 */
	public function getUserCustomer($customerId) {
		$row = $this->fetchRow(array(
			'customer_id' => $customerId,
			'user_id' => User::getId()
		));
		if($row)		return Db::tCustomer()->find($customerId);
	}
	
	/**
	 * Ermittelt alle Kunden des aktuellen Benutzers
	 * @return Model_MemberRowset
	 */
	public function fetchUser() {
		return $this->fetchAll( 'user_id='.User::getId());
	}
	
}