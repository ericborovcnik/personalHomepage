<?php
/**
 * Import - zeigt die Import-Prozessführung
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	eb	from Scratch
 */
abstract class Module_Import extends Module_Base {
	
	const MODULE_NAME					= 'Import';										//	Modul-Bezeichnung
	const MODULE_DESCRIPTION	= 'Import-Processes';					//	Modul-Beschreibung
	const MODULE_ACTIVE				= true;												//	Modul initial aktiv?
	
	/**
	 * @return multitype:multitype:string
	 */
	public static function getAccessObjects() {
		return array(
			array('code'=>'import',	'name'=>'Import-Processes',	'description'=>'Upload, pre- and postprocess documents',	'acl'=>'ww')
		);
	}
	
	/**
	 * Ermittelt die Navigationselemente
	 */
	public static function getNavigation() {
		return array(
			'import'	=>	array(
				'text'				=>	_('Import'),
				'access'			=>	'import',
				'action'			=>	'app.import.init()',
			)
		);
	}
	
}