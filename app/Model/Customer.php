<?php
/**
 * Datensatz aus der Tabelle customer
* @author			Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
* @copyright	WB Informatik AG (http://www.wb-informatik.ch)
* @version		2016-05-17	du	from scratch
*/
class Model_Customer extends Model_Base {

	//	GET-Methoden
	
	public function getName()	{
		return $this->__get('name');
	}
	
	public function getCode()	{
		return $this->__get('code');
	}
	
	public function getDescription()	{
		return $this->__get('description');
	}
	
	
	
	//	SET-Methoden
	
	public function setName($name)	{
		$this->__set('name', $name);
	}
	
	public function setCode($code)	{
		$this->__set('code',$code);
	}
	
	public function setDescription($description)	{
		$this->__set('description',description);
	}
	
}