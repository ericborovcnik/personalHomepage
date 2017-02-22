<?php
/**
 * Test-Modul
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-14	eb	from scratch
 */
abstract class Module_Test extends Module_Base {
	
	/**
	 * Ermittelt die Navigationselemente
	 */
	public static function getNavigation() {
		return array(
			'report'	=>	array(
				'text'				=>	_('Reports'),
				'access'			=>	'',
				'action'			=>	'',
				'tasks'				=>	array(
					array('text'=>'app.demo',	'action'=>'app.demo.init()'),
					array('text'=>'app.access',	'action'=>'app.access.init()'),
				)
			)
		);
	}
	
}