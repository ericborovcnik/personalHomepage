<?php
/**
 * Datensatz aus der Tabelle contract
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	du	from scratch
 */
class Model_Contract extends Model_Base {
	
	//	GET-Methoden
	
	public function getNr()	{
		return $this->__get('nr');
	}
	
	public function getActiveFrom()	{
		return $this->__get('activefrom');
	}
	
	public function getActiveTo()	{
		return $this->__get('activeto');
	}
	
	public function getRentalobjectID()	{
		return $this->__get('rentalobject_id');
	}
	
	public function getTenantID()	{
		return $this->__get('tenant_id');
	}
	
	
	
	//	SET-Methoden
	
	public function setNr($nr)	{
		$this->__set('nr', $nr);
	}
	
	public function setActiveFrom($date)	{
		$this->__set('activefrom', $date);
	}
	
	public function setActiveTo($date)	{
		$this->__set('activeto', $date);
	}
	
	public function setRentalobjectID($id)	{
		$this->__set('rentalobject_id', $id);
	}
	
	public function setTenantID($id)	{
		$this->__set('tenant_id', $id);
	}
	

}