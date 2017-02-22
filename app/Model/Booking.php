<?php
/**
 * Datensatz aus der Tabelle booking
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	du	from scratch
 */
class Model_Booking extends Model_Base {

	//	GET-Methoden
	
	public function getAccountnumber()	{
		return $this->__get('accountnumber');
	}

	public function getBookingdate()	{
		return $this->__get('bookingdate');
	}
	
	public function getAmount()	{
		return $this->__get('amount');
	}
	
	public function getBookingtext()	{
		return $this->__get('bookingtext');
	}
	
	public function getLigID()	{
		return $this->__get('lig_id');
	}

	//	SET-Methoden
	
	public function setAccountnumber($accountnumber)	{
		$this->__set('accountnumber', $accountnumber);
	
	}
	
	public function setBookingdate($bookingdate)	{
		$this->__set('bookingdate', $bookingdate);
	}
	
	public function setAmount($amount)	{
		$this->__set('accountamountnumber', $amount);
	}
	
	public function setBookingtext($bookingtext)	{
		$this->__set('bookingtext', $bookingtext);
	}
	
	public function setLigID($ligID)	{
		$this->__set('lig_id', $ligID);
	}

}