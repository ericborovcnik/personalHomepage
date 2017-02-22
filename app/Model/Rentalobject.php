<?php
/**
 * Datensatz aus der Tabelle rentalobject
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	du	from scratch
 */
class Model_Rentalobject extends Model_Base {
	
	//	GET-Methoden
	
	public function getNr()	{
		return $this->__get('nr');
	}
	
	public function getUsagetype()	{
		return $this->__get('usagetype');
	}
	
	public function getFloor()	{
		return $this->__get('floor');
	}
	
	public function getArea()	{
		return $this->__get('area');
	}
	
	public function getActiveFrom()	{
		return $this->__get('activefrom');
	}
	
	public function getActiveTo()	{
		return $this->__get('activeto');
	}
	
	public function getLigID()	{
		return $this->__get('lig_id');
	}
	
		
	//	SET-Methoden
	
	public function setNr($nr)	{
		$this->__set('nr', $nr);
	}
	
	public function setUsagetype($usagetype)	{
		$this->__set('usagetype', $usagetype);
	}
	
	public function setFloor($floor)	{
		$this->__set('floor', $floor);
	}
	
	public function setArea($area)	{
		$this->__set('period', $area);
	}
	
	public function setActiveFrom($date)	{
		$this->__set('activefrom', $date);
	}
	
	public function setActiveTo($date)	{
		$this->__set('activeto', $date);
	}
	
	public function setLigID($id)	{
		$this->__set('lig_id', $id);
	}

}