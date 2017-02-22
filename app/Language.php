<?php
/**
 * Bietet Methoden für die Übersetzungsmatrix an
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-11	eb	from scratch
 * @version		2016-04-27	eb	getLanguages vermittelt Liste der ISO-Sprachcodes mit ihren Klartext-Sprachen
 */
abstract class Language{
	
	private static $_keys;
	private static $_init;

	/**
	 * Ermittelt den Sprachschlüssel und substituiert allfällige {#}-Platzhalter
	 * @param string $key									Sprachschlüssel
	 * @param string|array $subst					Einzel- oder Mehrfach-Substitution
	 */
	public static function get($key, $subst=null) {
		if(!self::$_init)		self::refresh();
		$value = self::$_keys[$key];
		if(!$value)		$value = $key;
		if(is_null($subst))		$subst = '';
		if(is_string($subst) || is_numeric($subst)) {
			$subst = explode(',', $subst);
		}
		if(is_array($subst)) {
			foreach($subst as $key => $item) {
				if(is_null($item))		$item = 'NULL';
				$value = str_replace('{'.$key.'}', $item, $value);
			}
		}
		return $value;
	}
	
	/**
	 * Aktualisiert den Cache mit aktuellem Sprachcode
	 */
	public static function refresh() {
		self::$_keys = Db::tLanguage()->fetchLanguage($_SESSION['language'], false);
		self::$_init = true;
	} 
	
	/**
	 * Initialisiert die gesamte Übersetzungsmatrix auf Basis der Datei config/languageKeys.csv
	 * Bestehende Einträge mit dem 'locked'-Status bleiben dabei bestehen.
	 * Gegebenenfalls wird die Frontend-Marke gesetzt
	 */
	public static function reset() {
		$tLanguage = Db::tLanguage();
		$tLanguage->deleteUnlocked();
		$csv = new Import_CSVReader('config/languageKeys.csv');
 		$header = $csv->readline();
 		while(!$csv->eof()) {
 			$csv->readline();
 			$key = $csv->value(0);
 			$de = $csv->value(1);
 			$fr = $csv->value(2);
 			$frontend = $csv->value(4) == '' ? 0 : 1;
 			if($key == '')	continue;
			//	Eintrag einfügen, sofern der Schlüssel noch nicht existiert (not locked)
			if(!$tLanguage->hasKey($key)) {
				$tLanguage->insert(array(
					'frontend'		=>	$frontend,
					'locked'			=>	0,
					'key'					=>	$key,
					'de_CH'				=>	$de,
					'de_DE'				=>	$de,
					'de_AT'				=>	$de,
					'en_GB'				=>	$key,
					'fr_FR'				=>	$fr
				));
			} else {
				if($frontend == 1) {
					//	Aktualisiere Frontend-Marke pauschal (unabhängig ob Locked)
					$tLanguage->update(array('frontend'=>1), '`key`='.Db::quote($key));
				}
			}		
		}
	}

	/**
	 * Ermittelt eine Datenstruktur, welche die verfügbaren ISO-Codes mit den Sprachen verbindet
	 * @return array
	 */
	public static function getLanguages() {
		$languages = array(
			'de_CH'		=>	'Deutsch (Schweiz)',
			'de_DE'		=>	'Deutsch (Deutschland)',
			'de_AT'		=>	'Deutsch (Österreich)',
			'en_GB'		=>	'English',
			'fr_FR'		=>	'Français',
			'it_IT'		=>	'Italiano'
		);
		array_multisort($languages,SORT_ASC,$languages);
		return $languages;
	}
	
}