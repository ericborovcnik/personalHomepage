<?php
class Model_UsergroupTable extends Model_BaseTable {

	/**
	 * Ermittelt die Benutzergruppe anhand des Codes
	 * @param string $code
	 * @return Model_Usergroup
	 */
	public function findByCode($code) {
		return $this->fetchRow('code='.Db::quote($code));
	}
	
	/**
	 * Ermittelt die Benutzergruppe anhand des Namens
	 * @param string $name
	 * @return Model_Usergroup
	 */
	public function findByName($name) {
		return $this->fetchRow('name='.Db::quote($name));
	}
	
	/**
	 * Ermittelt die Gruppe der Administratoren.
	 * Diese Gruppe wird erzeugt, sofern sie noch nicht exisitert
	 * @return Model_Usergroup
	 */
	public function getAdmin() {
		$group = $this->fetchRow('code="admin"');
		if(!$group) {
			$id = $this->insert(array(
				'code'				=>	'admin',
				'name'				=>	'Administrators',
				'description'	=>	'Members of this group will have full administrative rights'
			));
			$group = $this->find($id);
		}
		return $group;
	}
	
	/**
	 * Ermittelt die Gruppe der Gäste.
	 * Diese Gruppe wird erzeugt, sofern sie noch nicht exisitert
	 * @return Model_Usergroup
	 */
	public function getGuest() {
		$group = $this->fetchRow('code="guest"');
		if(!$group) {
			$id = $this->insert(array(
				'code'				=>	'guest',
				'name'				=>	'Guests',
				'description'	=>	'All unidentified users will operate as guests'
			));
			$group = $this->find($id);
		}
		return $group;
	}
	
	/**
	 * Ermittelt die Gruppe der User.
	 * Diese Gruppe wird erzeugt, sofern sie noch nicht exisitert
	 * @return Model_Usergroup
	 */
	public function getUser() {
		$group = $this->fetchRow('code="user"');
		if(!$group) {
			$id = $this->insert(array(
				'code'				=>	'user',
				'name'				=>	'Users',
				'description'	=>	'Members of this group will have normal userrights'
			));
			$group = $this->find($id);
		}
		return $group;
	}
	
}