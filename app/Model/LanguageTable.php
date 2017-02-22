<?php
/**
 * Tabelle language
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-21	eb	from scratch
 */
class Model_LanguageTable extends Model_BaseTable {

	/**
	 * Löscht alle Einträge, die nicht blockiert sind.
	 */
	public function deleteUnlocked() {
		$this->delete('locked <> 1');
		Db::query('set @id=0; update language set id=(select @id:=@id+1) order by id;');
		$id = Db::fetchOne('select max(id) from language');
		if(!$id)		$id=1;
		Db::query('alter table language auto_increment='.$id);
	}
	
	/**
	 * Ermittelt alle Sprachschlüssel einer Zielsprache
	 * @param string $language						Sprachschlüssel
	 * @param boolean $frontend						true ermittelt ausschliesslich Frontend-Schlüssel
	 * @return array											Hash-Array mit Key => Translation
	 */
	public function fetchLanguage($language, $frontend=false) {
		$select = $this->select()
		->from('language', array('key', $language.' as word'));
		if($frontend)		$select = $select->where('frontend=1');
		$keys = array();
		foreach($this->fetchAll($select)->toArray() as $item) {
			$keys[$item['key']] = $item['word'];
		}
		return $keys;		
	}
	
	/**
	 * Prüft, ob ein Datensatz mit betreffendem Schlüssel existiert
	 * @param string $key
	 */
	public function hasKey($key) {
 		if($this->fetchRow('`key`='.Db::quote($key)))		return true;
		return false;
	}
	
}