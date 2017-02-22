<?php
/**
 * Datensatz aus der Tabelle lig
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	du	from scratch
 */
class Model_Lig extends Model_Base {
	
	//	GET-Methoden
	
	public function getLigNr()	{
		return $this->__get('ligNr');
	}
	
	public function getLigName()	{
		return $this->__get('ligName');
	}
	
	public function getPlace()	{
		return $this->__get('place');
	}
	
	
	//	SET-Methoden
	
	public function setLigNr($nr)	{
		$this->__set('ligNr', $nr);
	}
	
	public function setLigName($name)	{
		$this->__set('ligName', $name);
	}
	
	public function setPlace($place)	{
		$this->__set('place', $place);
	}
	

}