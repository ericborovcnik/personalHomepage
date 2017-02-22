<?php
/**
 * Overview - zeigt die Import-Übersicht eines Mandanten an
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	eb	from Scratch
 */
abstract class Module_Customer extends Module_Base {
	
	const MODULE_NAME					= 'Overview';										//	Modul-Bezeichnung
	const MODULE_DESCRIPTION	= 'Show an customer-overview';	//	Modul-Beschreibung
	const MODULE_ACTIVE				= true;													//	Modul initial aktiv?
	
	/**
	 * @return multitype:multitype:string
	 */
	public static function getAccessObjects() {
		return array(
			array('code'=>'overview',	'name'=>'Customer-Overview',	'description'=>'Shows the customer-specific stats about his imports and datas',	'acl'=>'wr')
		);
	}
	
	/**
	 * Ermittelt die Navigationselemente
	 */
	public static function getNavigation() {
		return array(
			'overview'	=>	array(
				'text'				=>	_('Overview'),
				'access'			=>	'overview',
				'action'			=>	'app.overview.init()'
			)
		);
	}
	
}