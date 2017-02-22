<?php
/**
 * Tabelle user
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-17	eb	from scratch
 */
class Model_UserTable extends Model_BaseTable {

	/**
	 * Ein Benutzer gehört einer Benutzergruppe an
	 */
	protected $_referenceMap    = array(
		'Usergroup' => array(
			'columns'				=>	'usergroup_id',
			'refTableClass'	=>	'Model_UsergroupTable',
			'refColumns' 		=>	'id'
		)
	);

	/**
	 * Ermittelt den per heute aktiven Benutzer anhand des Namens
	 * @param string $username
	 * @return Model_User
	 */
	public function fetch($username) {
		$date = Db::quote(Date::get('today'));
		return $this->fetchRow(array(
			'loginname='.db::quote($username),
			'active_from<='.$date.' or isnull(active_from) or active_from="0000-00-00"',
			'active_to>='.$date.' or isnull(active_to) or active_to="0000-00-00"'
		));
	}
	
	/**
	 * Ermittelt den Benutzer anhand des Loginnamens
	 * @param string $loginname
	 * @return Model_User
	 */
	public function findByLoginname($loginname,$today) {
		return $this->fetchRow('loginname='.Db::quote($loginname).' and isnull(active_to) or active_to>'.Db::quote($today));
	}
	
	/**
	 * Ermittelt den Gast-Benutzer und erzeugt diesen, sofern er nicht existiert
	 * @return Model_User
	 */
	public function getGuest() {
		$group = Db::tUsergroup()->getGuest();
		$user = $this->fetchRow('loginname="guest"');
		if(!$user) {
			$id = $this->insert(array(
				'usergroup_id'	=>	$group->getId(),
				'loginname'			=>	'guest',
				'username'			=>	'Visitor',
				'password'			=>	'',
				'email'					=>	'',
				'language'			=>	CONFIG_LOCALE_LANGUAGE,
				'locale'				=>	CONFIG_LOCALE_CODE,
				'lastlogin'			=>	null,
				'logincount'		=>	0,
				'active_from'		=>	null,
				'active_to'			=>	null
			));
			$user = $this->find($id);
		}
		return $user;
	}
	
	/**
	 * Ermittelt den Norm-Administrator und erzeugt diesen, sofern er nicht existiert
	 * @return Model_User
	 */
	public function getAdmin() {
		$group = Db::tUsergroup()->getAdmin();
		$user = $this->fetchRow('loginname="admin"');
		if(!$user) {
			$id = $this->insert(array(
				'usergroup_id'	=>	$group->getId(),
				'loginname'			=>	'admin',
				'username'			=>	'Administrator',
				'password'			=>	hash('sha512', 'admin', false),
				'email'					=>	'',
				'language'			=>	CONFIG_LOCALE_LANGUAGE,
				'locale'				=>	CONFIG_LOCALE_CODE,
				'lastlogin'			=>	null,
				'logincount'		=>	0,
				'active_from'		=>	null,
				'active_to'			=>	null
			));
			$user = $this->find($id);
		}
		return $user;
	}
	
	/**
	 * Ermittelt ein select-Objekt für die Grid-Anfragen
	 * @see Model_BaseTable::getSelect()
	 * @return Zend_Db_Select
	 */	
	public function getSelect() {
		return Db::select()
		->from('user')
		->joinLeft('usergroup','user.usergroup_id=usergroup.id','name as usergroup');
	}
}