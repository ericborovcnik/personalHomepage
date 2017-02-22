<?php
/**
 * Tabelle accessobject
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-21	eb	from scratch
 */
class Model_AccessobjectTable extends Model_BaseTable {

	protected $_referenceMap    = array(
		//	Ein Zugriffsobjekt gehört zu einem Modul
		'Module' => array(
			'columns'				=>	'module_id',
			'refTableClass'	=>	'Model_Module',
			'refColumns'		=>	'id'
		)
	);
	
	/**
	 * Erzeugt einen accessobject-Datensatz und vermittelt die id
	 * @see Zend_Db_Table_Abstract::insert()
	 */
	public function insert($attributes) {
		return parent::insert(array(
			'module_id'			=>	$attributes['module_id'],
			'code'					=>	$attributes['code'],
			'name'					=>	$attributes['name'],
			'description'		=>	$attributes['description']
		));
	}
		
}