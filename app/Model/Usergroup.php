<?php
/**
 * Datensatz aus der Tabelle usergroup
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-17	eb	from scratch
 */
class Model_Usergroup extends Model_Base {

	/**
	 * Erzeugt eine Kopie der aktuellen Gruppe mit ihren Zugriffsrechten
	 * @return integer										ID der Ziel-Benutzergruppe
	 */
	public function copy() {
		/*
		 * Ermittle Code für neue Gruppe durch Extraktion einer String-Prä- und Nummer-Postfix
		 * Inkrementiere die Postfix-Nummer (Code => Code2 => Code3 => ...)
		 */
		$t = Db::tUsergroup();
		$code = $this->getCode();
		$p = 0;
		do {
			$p++;
			if(!is_numeric(substr($code,-$p)))		break;
		} while($p<strlen($code));
		$p--;
		if(is_numeric(substr($code, -$p))) {
			$idx = substr($code, - $p);
			$code = substr($code, 0, strlen($code)-$p);
		} else {
			$idx = 1;
		}
		do {
			$idx++;
			$group = $t->findByCode($code.$idx);
		} while($group != null);
		$code .= $idx;
		/*
		 * Ermittle Name für neue Gruppe durch Extraktion einer String-Prä- und (Kopie #)-Postfix
		 * Inkrementiere die Kopie-Nummer (Name => Name (Kopie) => Name (Kopie 2) => ...)
		 */
		$name = $this->getName();
		$copy = Language::get('Copy');
		$p = strpos($name,' ('.$copy);
		if($p) 			$name = substr($name, 0, $p);
		$idx = 0;
		do {
			$idx++;
			$postfix = ' ('.$copy;
			if($idx > 1)		$postfix .= ' '.$idx;
			$postfix .= ')';
			$group = $t->findByName($name.$postfix);
		} while ($group != null);
		$name .= $postfix;
		/*
		 * Erzeuge Kopie der Benutzergruppe
		 */
		$id = $t->insert(array(
			'code'				=>	$code,
			'name'				=>	$name,
			'description'	=>	Language::get('Copy of {0}', $this->getCode())
		));
		/*
		 * Erzeuge Kopie der Zugriffsrechte
		 */
		Db::tAccess()->copyUsergroup($this->getId(), $id);
		return $id;
	}
	
	//
	//	GET-Methoden
	//
	public function getCode() {
		return $this->__get('code');
	}

	public function getName() {
		return $this->__get('name');
	}

	public function getDescription() {
		return $this->__get('description');
	}

	public function getUsers() {
		$usertable = new Model_UserTable();
		return $usertable->fetchAll('usergroup_id='.$this->getId());
	}

	//
	//	SET-Methoden
	//
	
	public function setCode($code) {
		$this->__set('code', $code);
	}

	public function setName($name) {
		$this->__set('name', $name);
	}

	public function setDescription($description) {
		$this->__set('description', $description);
	}

}