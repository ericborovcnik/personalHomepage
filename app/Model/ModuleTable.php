<?php
/**
 * Tabelle module
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-13	eb	from scratch
 */
class Model_ModuleTable extends Model_BaseTable {

	/**
	 * Ermittelt eine Liste mit allen Modulen, sortiert und mit Aktiv-Flag
	 * @param boolean $onlyActive					Zeige nur die Aktiven Module
	 * @return array											Hash-Array mit dem Camel-Case-Code und Aktiv als Wert
	 */
	public function getModuleList($onlyActive = false) {
		$result = array();
		$select = $this->select(true)->order('sort');
		if($onlyActive)		$select = $select->where('active=1'); 
		$rs = $select->query()->fetchAll();
		$func = create_function('$c', 'return "_".strtoupper($c[1]);');
		foreach($rs as $rec) {
			$code = strtoupper(substr($rec->code,0,1)) . strtolower(substr($rec->code,1));
			$code = preg_replace_callback('/_([a-z])/', $func, $code);
			$result[$code] = $rec->active;
		}
		return $result;
	}
	
}