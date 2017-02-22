<?php
/**
 * Tabelle access
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-21	eb	from scratch
 * @version		2016-01-13	eb	getAccessList
 * @version		2016-01-26	eb	copyUsergroup
 */
class Model_AccessTable extends Model_BaseTable {

	protected $_referenceMap    = array(
		//	Ein Zugriff gehört zu einer Benutzergruppe
		'Usergroup' => array(
			'columns'				=>	'usergroup_id',
			'refTableClass'	=>	'Model_UsergroupTable',
			'refColumns'		=>	'id'
		),
		//	Ein Zugiff gehört zu einem Zugriffsobjekt
		'Accessobject' => array(
			'columns'				=>	'accessobject_id',
			'refTableClass'	=>	'Model_AccessobjectTable',
			'refColumns'		=>	'id'
		)
	);

	/**
	 * Ermittelt ein Zugriffsrecht
	 * @param int	$usergroup_id
	 * @param int $accessobject_id
	 */
	public function getAccess($usergroup_id, $accessobject_id)	{
		$rs = $this->select(true)
		->where('usergroup_id=?',$usergroup_id)
		->where('accessobject_id=?',$accessobject_id)
		->query()->fetchAll();
		return $rs;
	}
	
	/**
	 * Erzeugt eine Kopie aller Zugriffsrechte aus einer Quell- für eine Zielgruppe
	 * @param integer $sourceId						Quellgruppe
	 * @param integer $targetId						Zielgruppe
	 */
	public function copyUsergroup($sourceId, $targetId) {
		$rs = $this->select(true)
		->where('usergroup_id=?',$sourceId)
		->query()->fetchAll();
		foreach($rs as $rec) {
			$this->insert(array(
				'usergroup_id'		=>	$targetId,
				'accessobject_id'	=>	$rec->accessobject_id,
				'access'					=>	$rec->access
			));
		}
	}
	
	/**
	 * Erzeugt oder löscht ein Zugriffsrecht für eine Benutzergruppe
	 * @param integer $usergroupId				Benutzergruppe
	 * @param integer $accessobjectId			Zugriffsrecht
	 * @param string $access							Zugriffsrecht read,write,none
	 */
	public function setAccess($usergroupId, $accessobjectId, $access) {
		$id = $this->select()
		->from('access', array('id'))
		->where('usergroup_id=?', $usergroupId)
		->where('accessobject_id=?', $accessobjectId)
		->query()->fetchColumn();
		switch($access) {
			case 'r':
			case 'read':
				$access = 'read';
				break;
			case 'w':
			case 'write':
				$access = 'write';
				break;
			default:
				$access = '';
				break;
		}
		if($access) {
			if($id) {
				$this->update(array('access'=>$access), "id=$id");
			} else {
				$this->insert(array(
					'usergroup_id'		=>	$usergroupId,
					'accessobject_id'	=>	$accessobjectId,
					'access'					=>	$access
				));
			}
		}	 else {
			if($id)			$this->delete("id=$id");
		}	
	}
	
	/**
	 * Erstellt eine Liste mit den Zugriffsrechten aus Zusammenschluss aus module.code_accessobject.code
	 * @param integer $usergroupId				Benutzergruppe
	 * @return array											Hash-Array mit den Rechte-Tags als Schlüssel und read/write als Wert
	 */
	public function getAccessList($usergroupId) {
		$rs = $select = Db::select()
		->from('access', array('access'))
		->joinLeft('accessobject', 'access.accessobject_id=accessobject.id', array('concat(module.code,"_",accessobject.code) as cd'))
		->joinLeft('module', 'accessobject.module_id=module.id')
		->where('access.usergroup_id=?', $usergroupId)
		->where('module.active=1')
		->query()->fetchAll();
		$acl = array();
		foreach($rs as $rec) {
			$acl[$rec->cd] = $rec->access;
		}
		return $acl;
	}
	
}