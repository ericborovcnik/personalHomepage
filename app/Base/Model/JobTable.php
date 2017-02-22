<?php
abstract class Base_Model_JobTable extends Model_BaseTable {
	/**
	 * @param string $uid
	 * @return Ambigous <Zend_Db_Table_Row_Abstract, NULL>
	 */
	public function getRowByUniqueId($uid) {
		$Select = $this->select();
		$Select->where('unique_id = ?', $uid);
		return $this->fetchRow($Select);
	}

}