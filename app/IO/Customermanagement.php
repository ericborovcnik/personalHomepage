<?php
/**
 * Customermanagement
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-05-10	du	from scratch
 */
class IO_Customermanagement extends IO_Base {
	
	const CHECK_LOGIN = true;
	const ACCESS = 'core_customermanagement';

	/**
	 * Ermittelt die Kunden
	 */
	public function customer_get()	{
		if(!User::canRead('core_customermanagement'))			return $this->jsonNoAccess();
		return Db::tCustomer()->getJsonTable($this->params);
	}
	
	/**
	 * Bearbeiten von Kunden
	 */
	public function customer_set()	{
		if(!User::canRead('core_customermanagement'))			return $this->jsonNoAccess();
		$t = Db::tCustomer();
		
		switch ($this->params->field)	{
			case 'code':		return $this->jsonNoAccess();		break;
				
			case	'name':		
				if($this->params->value=='')	return $this->jsonError($this->_('Name may not be empty.'));			
				break;
		}
		
		$t->update(array($this->params->field => $this->params->value), 'id='.$this->params->id);
		
	}
	
	/**
	 * Definiert den aktuellen Kunden, sofern der aktuelle Benutzer auch Zugriff hat
	 */
	public function customer_setCurrent() {
		$customer = Db::tMember()->getUserCustomer($this->params->id);
		if($customer) {
			User::setCustomerId($customer->getId());
		} else {
			User::setCustomerId(0);
		}
	}
	
	/**
	 * Erstellt einen Kunden inkl. Kunden DB
	 */
	public function customer_add()	{
		if(!User::canRead('core_customermanagement'))			return $this->jsonNoAccess();
		$t = Db::tCustomer();
		$errfields = array();
		$errmsg = '';
				
		$checkExists = Db::query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME=".Db::quote($this->params->code)."")->fetch();	
		
		// Überprüfung Code
		if($this->params->code == '')		$this->addError($this->_('Code may not be empty'),	'code');
		
		// Code darf keinen Sonderzeichen enthalten		
		if(preg_match('/[^A-Za-z0-9_]/', $this->params->code)) {
			$this->addError($this->_('This syntax is not possible.'), 		'code');
		}
		
		// Code muss kleingeschrieben sein
		if($this->params->code !== strtolower($this->params->code)){
			$this->addError($this->_('Code must begin with a Letter'), 		'code');
		}
		
		// Code muss eindeutig sein
		if($t->findByCode($this->params->code)) $this->addError($this->_('Code already exists'), 		'code');
		if($checkExists != false) $this->addError($this->_('DB already exists'), 		'code');
		
		// Überprüfung Name
		if($this->params->name == '')	$this->addError($this->_('Name may not be empty'), 'name');
		if($t->findByName($this->params->name)) $this->addError($this->_('Name already exists'), 'name');
		
		if(!$this->hasError()) {
			$t->addCustomer($this->params->name,$this->params->code,$this->params->description);
		}
		return $this->jsonResponse();
			
	}
	
	/**
	 * Löschen einen Kunden inkl. Kunden DB
	 */
	public function customer_del()	{
		if(!User::canWrite('core_usermanagement'))							return $this->jsonNoAccess();
		$t = Db::tCustomer();
		do {
			if($this->params->confirmed)			break;
			return Util::jsonConfirm($this->_('You delete all customer datas.'));
		} while(0);
			$code = 'red_'.$t->fetchRow('id='.$this->params->id)->getCode();
			$t->deleteCustomer($this->params->id, $code);
			return $this->jsonResponse($this->_('Customer successfully deleted'));
		
	}
	
	/**
	 * Erstellt einen Excel Report
	 */
	public function customer_excel()	{
		$xl = New Export_XL(array(
			'browser'			=>	true,
			'report'			=>	$this->_('Customers'),
			'orientation'	=>	'landscape',
			'pageswide'		=>	1
		));
		
		$xl->addSheet($this->_('Customers'));
		
		$customers = Db::tCustomer()->fetchAll();
		
		$xl->setColumnWidth(25,2,4);
		$xl->setColumnWidth(80,5,5);
		
		$xl->write(array(
			$this->_('id'),
			$this->_('Name'),
			$this->_('Code'),
			$this->_('Database'),
			$this->_('Description'),
		));
		$xl->newline();
		
		foreach ($customers as $customer)	{
			$xl->write(array(
				$customer->getId(),
				$customer->getName(),
				$customer->getCode(),
				'red_'.$customer->getCode(),
				$customer->getDescription()

			));
			$xl->newline();
			
		}
		
		$xl->save();
			
	}
	
	/**
	 * Ermittelt die Customermembers
	 */
	public function customermember_get()	{
		if(!User::canRead('core_customermanagement'))			return $this->jsonNoAccess();
		try {
			// provisorische Lösung
			$res = Db::select()
			->from('member')
			->where('customer_id='.$this->params->parentid)
			->joinLeft('customer', 'member.customer_id=customer.id', array('customer.name as customer'))
			->joinLeft('user', 'member.user_id=user.id', array('username as user'))
			->query()->fetchAll();
		} catch (Exception $e) {
			$res = array();
		}
		return Zend_Json::encode(array(
			'success'				=>	true,
			'totalCount'		=>	false,
			'data'					=>	$res
		));
		return $res;
	}
	
	/**
	 * Bearbeiten von Customermembers
	 */
	public function customermember_set()	{
		
	}
	
	/**
	 * Erstellt eine Memberrole 
	 */
	public function customermember_add()	{
		if(!User::canRead('core_customermanagement'))			return $this->jsonNoAccess();
		$t = Db::tMember();
		$errfields = array();
		$errmsg = '';
		
		if($this->params->customer == '') $this->addError($this->_('Customer may not be empty'),	'customer');
		if($this->params->user == '') 		$this->addError($this->_('User may not be empty'),	'user');
		if($this->params->role == '') 		$this->addError($this->_('Role may not be empty'),	'role');
		
		if($t->getMember($this->params->customer, $this->params->user)){
			$this->addError($this->_('A Memberrole for your selection already exists'),	'user');
			$this->addError(null,	'customer');
		}
		
		if(!$this->hasError()) {
			$t->insert(array(
				'customer_id'	=>	$this->params->customer,
				'user_id'			=>	$this->params->user,
				'role'				=>	$this->params->role
				));
		}
		
		return $this->jsonResponse();
			
	}
	
	/**
	 * Löscht einen Customermember
	 */
	public function customermember_del()	{
		if(!User::canWrite('core_usermanagement'))							return $this->jsonNoAccess();
		$t = Db::tMember();
		do {
			if($this->params->confirmed)			break;
			return Util::jsonConfirm($this->_('You delete this customermember'));
		} while(0);
		$t->delete('id='.$this->params->id);
		return $this->jsonResponse($this->_('User successfully deleted'));
		
	}
	
	public function customermember_excel()	{
		if(!User::canWrite('core_usermanagement'))							return $this->jsonNoAccess();
		$t = Db::tMember();
		
		$xl = New Export_XL(array(
			'browser'			=>	true,
			'report'			=>	$this->_('Customer-Members'),
			'orientation'	=>	'landscape',
			'pageswide'		=>	1
		));
		
		$xl->addSheet($this->_('Members'));
		$members = $t->fetchAll('customer_id='.$this->params->parentid);
		
		// Header
		$xl->write(array(
			$this->_('id'),
			$this->_('Customer'),
			$this->_('User'),
			$this->_('Role')
		));
		
		$xl->setColumnWidth(15,2,4);				// Customer, User, Role
		$xl->newline();
		
		foreach ($members as $member)	{
			$xl->write(array(
				$member->getId(),
				$member->getCustomerId(),
				$member->getUserId(),
				$member->getRole()
			));
			$xl->newline();
		}
		
		$xl->save();
				
	}
	
}