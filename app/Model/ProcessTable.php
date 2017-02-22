<?php
/**
 * Tabelle process
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-21	eb	from scratch
 */
class Model_ProcessTable extends Model_BaseTable {
	
	/**
	 * Erzeugt enen neuen Datensatz
	 * @see Zend_Db_Table_Abstract::insert()
	 * @param string $name								Prozessbezeichnung
	 * @param string $class								Klassen-Referenz in Short-Notation [Import_Process_XXX => XXX]
	 * @param string $description					Beschreibung
	 * @param string $docman							Docman-Referenz
	 */
	public function insert($name, $class, $description='', $docman='') {
		$data = array(
			'name'				=>	$name,
			'class'				=>	$class,
			'description'	=>	$description,
			'docman'			=>	$docman
		);
		try {
			return $this->find(parent::insert($data));
		} catch(Exception $e) {
			Log::err($e->getMessage());
		}
	}
	
	/**
	 * @see Model_BaseTable::find()
	 * @return Model_Process
	 */
	public function find() {
		return parent::find(func_get_args());
	}
	
	/**
	 * Vermittelt den (ersten) Datensatz anhand des Namens
	 * @param string $name								Bezeichnung
	 * @return Model_Process
	 */
	public function findByName($name) {
		return parent::fetchRow('name='.Db::quote($name));
	}
	
	/**
	 * Vermittelt den Datensatz anhand der Klasse
	 * @param string $class								Klassenbezeichnung (in Short oder Long-Notation)
	 * @return Model_Process
	 */
	public function findByClass($class) {
		$p = strpos($class, 'Import_Process_');
		if($p !== false) {
			$class = substr($class, -(strlen($class)-$p-15));
		}
		return parent::fetchRow('class='.Db::quote($class));
	}
	
}