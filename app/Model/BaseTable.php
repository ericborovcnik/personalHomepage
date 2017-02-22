<?php
abstract class Model_BaseTable extends Zend_Db_Table {
	/**
	 * The primary key column or columns.
	 * A compound key should be declared as an array.
	 * You may declare a single-column primary key
	 * as a string.
	 *
	 * @var mixed
	 */
	protected $_primary = 'id';

	protected $_isCustomerTable = false;

	/**
	 * get the table name
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}

	public function _($key, $subst=array()) {
		return Language::get($key, $subst);
	}

	public function find() {
		$rowset = parent::find(func_get_args());
		return $rowset->current();
	}

	public function __construct($config = array(), $definition = null) {
		$this->_name = strtolower(str_replace(array('Model_', 'Table'), '', get_class($this)));

		$this->_rowClass = 'Model_' . ucfirst($this->_name);
		if (!class_exists($this->_rowClass, true)) {
			$this->_rowClass = 'Zend_Db_Table_Row';
		}

		$this->_rowsetClass = 'Model_' . ucfirst($this->_name) . 'Rowset';
		if (!class_exists($this->_rowsetClass, true)) {
			$this->_rowsetClass = 'Zend_Db_Table_Rowset';
		}

		if ($this->_isCustomerTable) {
			$Customer = Registry::getCustomer();
			$config[Zend_Db_Table::ADAPTER] = Db::getDb($Customer->getId());
		} else {
			$config[Zend_Db_Table::ADAPTER] = Db::getDb();
		}

		parent::__construct($config, $definition);
	}

	/**
	 * Ermittelt basierend vom Basis-Select-Objekt eine Datensatzanfrage
	 * für Json-Tabellen
	 * @param array $params								Standard-Parameter [fields, query, limit, start, sort, dir]
	 * @param string|array $where					Optionale Einschränkungen
	 * @param string $substitutes					Array mit Feld-Substituten
	 * @param array $translations					Sendet alle aufgeführten Felder durch die Übersetzungsmatrix
	 * @return Jsontable									Json-Struktur mit success, totalCount, data
	 */
	public function getJsonTable($params = array(), $where=false, $substitutes=false, $translations=array()) {
		if(is_array($params))		$params = Util::arrayToObject($params);
		$select = $this->getSelect();
		if(is_string($where)) {
			$select = $select->where($where);
		} else if(is_array($where)) {
			foreach($where as $w) {
				$select = $select->where($w);
			}
		}
		$where = '';
		if($substitutes) {
			foreach($substitutes as $src => $dest) {
				if($params->sort == $src)		$params->sort = $dest;
				$fields = Util::getTags($params->fields,'"','"');
				if($fields) {
					foreach($fields as $key => $field) {
						if($field == $src)		$fields[$key] = $dest;
					}
				}
				$params->fields = '[';
				if ($fields) {
					foreach($fields as $field) {
						$params->fields .= '"'.$field.'",';
					}
				}
				$params->fields = substr($params->fields,0,strlen($params->fields)-1);
				$params->fields .= ']';
			}
		}
		if($params->query)	{
			if(is_array($params->fields)) {
				$fields = $params->fields;
			} else {
				$fields = Zend_Json::decode($params->fields);
			}
			foreach($fields as $searchField) {
				if(strpos($searchField,'(') === false) {
					$searchFieldComp = explode('.',$searchField);
					$searchField = '';
					foreach($searchFieldComp as $fieldItem) {
						$searchField .= "`$fieldItem`.";
					}
					$searchField = substr($searchField,0,-1);
				}
				$where .= " OR $searchField LIKE '%" . $params->query . "%'";
			}
			if($where) $where =substr($where,4, strlen($where)-4);
			$select->where($where);
		}
		try {
			$rs = $select->query()->fetchAll();
		} catch(Exception $e) {
			Log::err($e->getMessage(), $select->__toString());
		}
		$cnt = count($rs);
		if($params->limit)	$select->limit($params->limit, $params->start);
		if($params->sort) {
			$select->reset('order');
			$select->order($params->sort . ' ' . $params->dir);
		}
		$rs = $select->query()->fetchAll();
		foreach($translations as $field) {
			foreach($rs as $key => $item) {
				$rs[$key]->$field = $this->_($item->$field);
			}
		}
		return Zend_Json::encode(array(
			'success'				=>	true
			,'totalCount'		=>	$cnt
			,'data'					=>	$rs
		));
	}

	/**
	 * Ermittelt das Tabellenspezifische Norm-Select-Objekt für IO-Queries
	 * @return Zend_Db_Select
	 */
	public function getSelect() {
		return $this->select();
	}

	/**
	 * Löscht die gesamte Tabelle
	 */
	public function truncate() {
		try {
			Db::query('delete from '.$this->getName());
			Db::query('set foreign_key_checks=0');
			Db::query('truncate '.$this->getName());
			Db::query('set foreign_key_checks=1');
		} catch(Exception $e) {
			Log::err($e->getMessage());
		}
	}
	
	/**
	 * Ermittelt die Anzahl Datensätze in der Tabelle
	 * @param mixed $where								Optionale Einschränkungen
	 * @return integer										Anzahl Datensätze
	 */
	public function count($where=null) {
		try {
			$select = Db::select()
			->from($this->getName(), 'count(*) as count');
			if(is_string($where)) {
				$select = $select->where($where);
			}
			if(is_array($where) == 'array') {
				foreach($where as $whereItem) {
					$select = $select->where($whereItem);
				}
			}
			return $select->query()->fetchColumn();
		} catch(Exception $e) {
			Log::err($e->getMessage());
		}
	}
	
}


class Model_RowNotFoundException extends Exception {}