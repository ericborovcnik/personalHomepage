<?php
/**
 * Datensatz aus der Tabelle debtposition
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	du	from scratch
 */
class Model_Debtposition extends Model_Base {
	
	//	GET-Methoden
	
	public function getType()	{
		return $this->__get('type');
	}
	
	public function getDescription()	{
		return $this->__get('description');
	}
	
	public function getAmount()	{
		return $this->__get('amount');
	}
	
	public function getPeriod()	{
		return $this->__get('period');
	}
	
	public function getActiveFrom()	{
		return $this->__get('activefrom');
	}
	
	public function getActiveTo()	{
		return $this->__get('activeto');
	}
	
	public function getContractID()	{
		return $this->__get('contract_id');
	}
	
	
	
	//	SET-Methoden
	
	public function setType($type)	{
		$this->__set('type', $type);
	}
	
	public function setDescription($description)	{
		$this->__set('description', $description);
	}
	
	public function setAmount($amount)	{
		$this->__set('amount', $amount);
	}
	
	public function setPeriod($period)	{
		$this->__set('period', $period);
	}
	
	public function setActiveFrom($date)	{
		$this->__set('activefrom', $date);
	}
	
	public function setActiveTo($date)	{
		$this->__set('activeto', $date);
	}
	
	public function setContractID($id)	{
		$this->__set('contract_id', $id);
	}
	
	

}