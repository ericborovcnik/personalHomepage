<?php
/**
 * Select-Methoden - bieten Norm-Auswahlmethoden für select-Felder an
 * getSelect() als zentrale Einstiegsmethode vermittelt aufgrund des Source-Attributs eine JSON-encodierte Liste für Dropdown-Steuerelemente
 * Jede spezifische _select_xxx-Methode prüft autark die spezifischen Zugriffsrechte
 * 
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-04-26	eb	from scratch
 * @version		2016-04-27	du	_selectUsergroups/_selectLanguages
 */
class IO_Select extends IO_Base {
	
	const CHECK_LOGIN = false;
	const ACCESS = '';

	/**
	 * Prüft den Parameter $source und vermittelt aus der spezifischen Sub-Methode _select_{$source} die Auswahl.
	 * Liegt keine Auswahl vor, dann wird eine leere Auswahl vermittelt.
	 * Liegt keine Methode vor, dann wird eine Auswahl mit der Fehlermeldung vermittelt
	 */
	public function getSelect() {
		$source = $this->params->source;
		if(!$source) {
			$data = $this->_selectError($this->_('No source-attribute specified'));
		} else {
			$source = strtoupper(substr($source,0,1)) . strtolower(substr($source,1));
			if(!method_exists('IO_Select', '_select'.$source)) {
				$data = $this->_selectError($this->_('IO_Select::_select{0} not implemented', $source));
			} else {
				$data = $this->{'_select'.$source}();
				if($this->params->query) {
					$query = strtolower($this->params->query);
					//	filter elements
					foreach($data as $key => $item) {
						$haystack = strtolower($item['id'].$item['value'].$item['description']);
						if(strpos($haystack, $query) === false)	unset($data[$key]);
					}
				}
			}
		}
		return Zend_Json::encode(array(
			'data'		=>	array_merge(array(),$data)
		));
	}
	
	/**
	 * Protokolliert einen Select-Fehler und übermittelt eine Fehlerliste als gültige Datenstruktur
	 * @param string $error
	 * @return json
	 */
	private function _selectError($error) {
		Log::err($error);
		return array(
			array(
				'id'					=>	0,
				'value'				=>	$this->_('ERROR'),
				'description'	=>	$error
			)
		);
	}

	/**
	 * Protokolliert einen Zugriffsfehler und übermittelt eine Fehlerliste als gültige Datenstruktur
	 */
	private function _selectErrorNoAccess() {
		return $this->_selectError($this->_('Access denied'));
	}
	
	////////////////////////////////////////////////////////////////////
	//	PRIVATE SELECT-METHODEN FÜR CLIENT_DROPDOWNS									//
	//	!!!	ACHTUNG	: Jeweils separate Rechteprüfung berücksichtigen	//
	////////////////////////////////////////////////////////////////////
	
	/**
	 * Sprachenliste von ISO-Code zu Klartext-Sprache
	 */
	private function _selectLanguage() {
		$data = array();
		foreach(Language::getLanguages() as $code => $language) {
			$data[] = array(
				'id'		=>	$code,
				'value'	=>	$language
			);
		}
		return $data;
	}
	
	/*
	 * Demo-Listen
	 */
	/**
	 * Ermittelt eine Kurzliste für Testauswahlen
	 */
	private function _selectDemoshort() {
		return array(
			array('id'=>0,	'value'=>'zero',	'description'=>'Empty record-substitute'),
			array('id'=>1,	'value'=>'one',		'description'=>'First real Element'),
			array('id'=>2,	'value'=>'two',		'description'=>'Second real Element'),
			array('id'=>3,	'value'=>'three',	'description'=>'Third real Element')
		);
	}
	
	/**
	 * Ermittelt eine Liste mit allen Benutzergruppen
	 */
	private function _selectUsergroup()	{
		if(User::isLoggedIn()== false)	return Util::jsonNoAccess();
		$t = Db::tUsergroup();
		$ab = $t->fetchAll();
		$res = array();
		foreach ($ab as $resultRow){
			$res[] = array(
				'id'		=> $resultRow->getId(),
				'value'	=>	$resultRow->getName()
			);
		}
		return $res;
	}
	
	/**
	 * Ermittelt eine Liste mit allen Benutzern
	 */
	private function _selectUser()	{
		if(User::isLoggedIn()== false)	return Util::jsonNoAccess();
		$res = array();
		$t = Db::tUser();
		$dataArray = $t->fetchAll();
		foreach ($dataArray as $resultRow)	{
			$res[]	=	array(
				'id'		=>	$resultRow->getId(),
				'value'	=>	$resultRow->getLoginname().': '.$resultRow->getUsername()
			);
		}
		return $res;
		
	}
	
	private function _selectMemberrole()	{
		return array(
			array('id'=>'admin',	'value'=>'admin'),
			array('id'=>'reader',	'value'=>'reader'),
			array('id'=>'writer',	'value'=>'writer')
		);
		
	}
	
	/**
	 * Ermittelt eine Liste mit allen zur verfügungstehenden Sprachschlüssel
	 */
	private function _selectLanguages()	{
		return array(
			array('id'=>'de_CH',	'value'=>'de_CH'),
			array('id'=>'de_DE',	'value'=>'de_DE'),
			array('id'=>'de_AT',	'value'=>'de_AT'),
			array('id'=>'en_GB',	'value'=>'en_GB'),
			array('id'=>'fr_FR',	'value'=>'fr_FR'),
			array('id'=>'it_IT',	'value'=>'it_IT'),
		);

	}
	
	/**
	 * Ermittelt alle Kunden
	 */
	private function _selectCustomers()	{
		if(User::isLoggedIn()== false)	return Util::jsonNoAccess();
		$t = Db::tCustomer();
		$res = array();
		$dataArray = $t->fetchAll();
		
		if($dataArray->toArray()==null){
				$res[] = array(
					'id'					=>	0,
					'value'				=>	'-'
				);
		}	else{
			foreach($dataArray as $resultRow){
				$res[] = array(
					'id'					=>	$resultRow->getId(),
					'value'				=>	$resultRow->getName(),
					'description'	=>	$resultRow->getDescription()
				);
			}	
		}
		
		return $res;
	}
	
	/**
	 * Ermittelt die Kundenliste, zu welchen der angemeldete Benutzer Zugriff hat
	 */
	private function _selectCustomersofuser() {
		if(!User::isLoggedIn())			return $this->jsonNoAccess();
		$res = array();
		$members = Db::tMember()->fetchUser();
		if($members->count() == 0) {
			$res[] = array(
				'id'		=>	0,
				'value'	=>	'-'
			);
		} else {
			foreach($members as $member) {
				$customer = $member->getCustomer();
				$res[] = array(
					'id'					=>	$customer->getId(),
					'value'				=>	$customer->getName(),
					'description'	=>	$customer->getDescription()
				);
			}
		}
		return $res;
	}
	
}
