<?php
/**
 * Datensatz aus der Tabelle tenant
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	du	from scratch
 */
class Model_Tenant extends Model_Base {
	
	//	GET-Methoden
	
	public function getNr()	{
		return $this->__get('nr');
	}
	
	public function getName()	{
		return $this->__get('name');
	}
		
	//	SET-Methoden
	
	public function setNr($nr)	{
		$this->__set('nr', $nr);
	}
	
	public function setName($name)	{
		$this->__set('name', $name);
	}

}