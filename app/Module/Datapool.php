<?php
/**
 * Datenpool zur Darstellung, Erfassung, Pflege und Extraktion von Daten
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	eb	from Scratch
 */
abstract class Module_Datapool extends Module_Base {
	
	const MODULE_NAME					= 'Datapool';										//	Modul-Bezeichnung
	const MODULE_DESCRIPTION	= 'View, export, edit data';					//	Modul-Beschreibung
	const MODULE_ACTIVE				= true;												//	Modul initial aktiv?
	
	/**
	 * @return multitype:multitype:string
	 */
	public static function getAccessObjects() {
		return array(
			array('code'=>'datapool',	'name'=>'Datapool',	'description'=>'View, edit, export data from datapool',	'acl'=>'ww')
		);
	}
	
	/**
	 * Ermittelt die Navigationselemente
	 */
	public static function getNavigation() {
		return array(
			'datapool'	=>	array(
				'text'				=>	_('Datapool'),
				'access'			=>	'datapool',
				'action'			=>	'app.datapool.init()'
			)
		);
	}
	
}