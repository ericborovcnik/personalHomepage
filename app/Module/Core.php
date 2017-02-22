<?php
/**
 * Core-Modul - steuert die Basis-Funktionen
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-13	eb	from scratch
 */
abstract class Module_Core extends Module_Base {

	const MODULE_NAME					= 'Core-Module';
	const MODULE_DESCRIPTION	= 'Includes the basic system-functionality';
	const MODULE_ACTIVE				= true;
	
	/**
	 * Vermittelt die Liste der Zugriffsrechte für dieses  Modul
	 * @return array											Zugriffsrechte
	 */
	public static function getAccessObjects() {
		return array(
			array('code'=>'customermanagement',	'name'=>'Customermanagement',	'description'=>'Access to the central security point. Show/edit Customermanagement-Tools.',	'acl'=>'w'),
			array('code'=>'usermanagement',			'name'=>'Usermanagement',			'description'=>'Access to the central security point. Show/edit users and accessrights.',		'acl'=>'w'),
			array('code'=>'systemmanagement',		'name'=>'Systemmanagement',		'description'=>'Access to the central security point. Show/edit Systemmanagment-Tools.',		'acl'=>'w')
		);
	}
	
	/**
	 * Vermittelt die Liste der Administrations-Positionen
	 * @return array											Mehrdimensionales Array mit der Menüstruktur für die Administration
	 */
	public static function getAdministration() {
		return array(
			array('text'=>self::_('Customermanagement'),	'access'=>'customermanagement',	'icon'=>'x-icon x-icon-customer',	'action'=>'app.customermanagement.init()'),
			array('text'=>self::_('Usermanagement'),			'access'=>'usermanagement',			'icon'=>'x-icon x-icon-access',		'action'=>'app.access.init()'),
			array('text'=>self::_('Systemmanagement'),		'access'=>'systemmanagement',		'icon'=>'x-icon x-icon-settings',	'action'=>'app.systemmanagement.init()')
		);
	}
	
}