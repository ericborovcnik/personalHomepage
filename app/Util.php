<?php
/**
 * Bietet Hilfsfunktionen querbeet an
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-11-30	eb	from scratch
 * @version		2016-02-09	cz	jsonSystemError(dummy)
 * @version		2016-02-18	cz	is_null in dump return value
 */
abstract class Util {

	/*
	 * Datastructures (strings, array, typeconversions)
	 */

	/**
	 * Konvertiert ein assoziatives Array in ein Objekt
	 * @param	array	$array								Array
	 * @return stdClass										Konvertiertes Array
	 */
	public static function arrayToObject($array) {
		if(!empty($array)) {
			$data = new stdClass;
			foreach($array as $akey => $aval) {
				if($akey != '') {
					$data->{$akey} = $aval;
				}
			}
			return $data;
		}
		return false;
	}

	/**
	 * Erstellt eine Zeichenkette aus Array und Object-Strukturen für Analyse-Dumps
	 * @param	mixed		$var							Zu analysierender Inhalt
	 * @param	integer	$indent						Einrückungs-Level
	 * @param boolean	$html							steuert die Leerzeichen und Newline-Steuerzeichen
	 * @return string										Zeichenkette
	 */
	public static function dump($var=false, $indent=0, $html=false) {
		$rc = '';
		if($html) {
			$space = '&nbsp;';
			$break = '<br />';
			$tabfact = 6;
		} else {
			$space = ' ';
			$break = "\n";
			$tabfact = 1;
		}

		$tab = str_Pad('', $indent*3, ' ');
		if (is_null($var)) {
			$rc .= 'NULL';
		} else if(is_array($var)) {
			$rc .= 'array(' . count($var) . ') {'.$break;
			$indent++;
			$tab = str_Pad('', $indent*3*$tabfact, $space);
			$max=0;
			foreach($var as $key => $item) {
				if(strlen($key) > $max)	$max=strlen($key);
			}
			foreach($var as $key => $item) {
				$out = $tab . '[' . $key . ']';
				for($i=0;$i<=($max - strlen($key));$i++) {
					$out .= $space;
				}
				$rc .= $out . '=>'.$space.$space;
				if(is_array($item) || is_object($item)) {
					$rc .= self::dump($item, $indent, $html);
				} elseif(is_bool($item)) {
					$rc .= $item ? 'true'.$break : 'false'.$break;
				} else {
					$rc .= $item.$break;
				}
			}
			$indent--;
			$tab = str_Pad('', $indent*3, $space);
			$rc .= $tab . '}'.$break;
		} elseif(is_object($var)) {
			$class = get_class($var);
			$properties = get_object_vars($var);
			$rc .= $class.'('.count($properties).') {'.$break;
			$indent++;
			$tab = str_pad('', $indent*3*$tabfact, $space);
			$max=0;
			foreach($properties as $key => $item) {
				if(strlen($key) > $max) $max = strlen($key);
			}
			foreach($properties as $key => $item) {
				$out = $tab . '->' . $key;
				for($i=0;$i<=($max - strlen($key));$i++) {
					$out .= $space;
				}
				$rc .= $out . '='.$space.$space;
				if(is_array($item) || is_object($item)) {
					$rc .= self::dump($item, $indent, $html);
				} elseif(is_bool($item)) {
					$rc .= $item ? 'true'.$break : 'false'.$break;
				}	else {
					$rc .= $item.$break;
				}
			}
			//	Extension um Methoden, wenn komplexes Objekt
			if($class != 'stdClass') {
				$methods = get_class_methods($class);
				if(count($methods)) {
					foreach($methods as $method) {
						$rc .= $tab.'->'.$method.'()'.$break;
					}
				}
			}
			$indent--;
			$tab = str_Pad('', $indent*3*$tabfact, $space);
			$rc .= $tab . '}'.$break;
		} elseif(is_bool($var)) {
			$rc .= $var ? 'true'.$break : 'false';
		} else {
			$rc .= $var;
		}
		if($html)		$rc .= $break;
		return $rc;
	}

	/**
	 * Ermittelt ein Array mit allen Tags, die in einem Text auftreten
	 * @param	string	$text								Zeichenkette
	 * @param	string	$startChar					Eröffnungszeichen für das Tag
	 * @param	string	$endChar						Schliesszeichen für das Tag
	 * @return	array											Array mit den gefundenen Tags
	 */
	public static function getTags($text, $startChar, $endChar) {
		$arr = array();
		$regExp = '~'.preg_quote($startChar).'[^'.preg_quote($startChar).preg_quote($endChar).']*'.preg_quote($endChar) . '~';
		$l1 = strlen($startChar);
		$l2 = strlen($endChar) + $l1;
		preg_match_all($regExp, $text, $tags);
		if($tags) {
			foreach($tags[0] as $item) {
				$arr[] = substr($item, $l1, strlen($item) - $l2);
			}
		}
		return $arr;
	}

	/**
	 * Ermittelt den Variablenname aus einer Zeichenkette mit der Syntax Variable=Wert
	 * @param string $variable						Zeichenkette in der Syntax Variable=Wert
	 * @return string											Zeichenkette vor =
	 */
	public static function varName($variable) {
		return trim(Util::getWord($variable,1,'='));
	}
	
	/**
	 * Ermittelt den Variablenwert aus einer Zeichenkette mit der Syntax Name=Wert
	 * @param	string	$variable					Variable
	 * @return	string										Zeichenkette nach =
	 */
	public static function varValue($variable) {
		return trim(Util::getWord($variable,2,'='));
	}
	
	/**
	 * Ermittelt ein Wort aus der Zeichenkette
	 * @param		string 	$text
	 * @param		integer	$index
	 * @param		string	$delimiter
	 * @return	string
	 */
	public static function getWord($text, $index, $delimiter=' ') {
		$words = explode($delimiter, $text);
		if($index<0 || $index>count($words)) return '';
		return $words[$index - 1];
	}
	
	/**
	 * Konvertiert einen Wert in einen Boolean
	 * @param mixed $any
	 * @return boolean
	 */
	public static function toBool($any=null) {
		if(!isset($any))	return false;
		if(is_bool($any))	return $any;
		if(is_numeric($any)) {
			if((float)$any == 0) return false;
			if($any == 0 || $any == '0') return false;
			return true;
		}
		if(is_string($any)) {
			if($any == '')	return false;
			if(strpos(' 0 falsch false nein - ', strtolower(' ' . $any . ' '))) return false;
			return true;
		}
		if(is_array($any)) {
			if(count($any) == 0) return false;
			return true;
		}
		return (bool)$any;
	}
	
	/*
	 * Json
	 */

	/**
	 * Ermittelt eine Norm-Json-Antowrt für Rückfragen
	 * @param	string	$message						Bestätigungsmeldung
	 * @return json												Bestätigungsanfrage
	 */
	public static function jsonConfirm($message='') {
		return Zend_Json::encode(array(
			'success'		=>	true
			,'confirm'	=>	true
			,'msg'			=>	$message
		));
	}

	/**
	 * Erstellt eine Json-Fehlermeldung
	 * @param string|array $msg						Eine Fehlermeldung oder eine Antwortstruktur
	 * @return json
	 */
	public static function jsonError($msg='') {
		if(is_string($msg)) {
			$data = array(
				'success'	=>	false,
				'error'		=>	$msg
			);
		} else if(is_array($msg)) {
			$data = $msg;
		} else $data = array();

		if(!array_key_exists('success', $data))		$data['success'] = false;
		return Zend_Json::encode($data);
	}

	/**
	 * Erstellt eine Json-Fehlermeldung auf Systemschicht-Ebene
	 * Diese Fehler sollen vor der definierten Callback-Funktion abgeprüft werden
	 * Diese Fehler sind systemnah und clientseitig unerwartet
	 * Diese Fehler sollen in den Callback-Funktionen nicht näher betrachtet werden müssen
	 * @param string|array $msg						Eine Fehlermeldung oder eine Antwortstruktur
	 * @return json
	 */
	public static function jsonSystemError($msg='') {
		return self::jsonError($msg);
	}

	/**
	 * Erstellt eine Json-Erfolgsmeldung
	 * @param string|array $msg						Eine Erfolgsmeldung oder eine Antwortstruktur
	 * @return json
	 */
	public static function jsonSuccess($msg='') {
		if(is_string($msg)) {
			$data = array(
				'success'	=>	true,
				'msg'			=>	$msg
			);
		} else if(is_array($msg)) {
			$data = $msg;
		} else $data = array();

		if(!array_key_exists('success', $data))		$data['success'] = true;
		return Zend_Json::encode($data);
	}

	/**
	 * Erstellt eine Json-Rückmeldung mit Fehlercode, dass Anmeldung benötigt wird
	 * @param string|array $msg						Eine Optionale Antwortstruktur
	 * @return json
	 */
	public static function jsonLoginRequired($msg='') {
		$data = array(
			'success'		=>	false,
			'msg'				=>	'',
			'error'			=>	Language::get('Login required')
		);
		if(is_string($msg))	{
			$data['msg'] = $msg;
		} else if(is_array($msg)) {
			$data = array_merge($data, $msg);
		}
		return Zend_Json::encode($data);
	}

	/**
	 * Erstellt eine Json-Rückmeldung mit Zugriff verweigert-Meldung
	 * @param string|array $msg						Eine Optionale Antwortstruktur
	 * @return json
	 */
	public static function jsonNoAccess($msg='') {
		if(is_string($msg)) {
			$data = array(
				'success'	=>	false,
				'msg'			=>	$msg
			);
		} else if(is_array($msg)) {
			$data = $msg;
		} else $data = array();
		if(!array_key_exists('error', $data))			$data['error'] = Language::get('Access denied');
		if(!array_key_exists('success', $data))		$data['success'] = false;
		return Zend_Json::encode($data);
	}

	/**
	 * Erstellt eine Json-Tabelle für Data-Grids
	 * @param Zend_Db_Select $select			Select-Query
	 * @param array $params								Standard-Query-Parameter (start, limit, sort, dir, query)
	 * @param array $substitutes					Attribute-Substitution
	 * @param array $translations					Alle aufgeführten Attribute werden durch die Übersetzungsmatrix geschleust					
	 * @return string											json-encodierte Tabelle
	 */
	public static function jsonTable($select, $params=array(), $substitutes=false, $translations=array()) {
		if(is_array($params))		$params = Util::arrayToObject($params);
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
	
	/*
	 * System
	 */

	/**
	 * Ermittelt eine Zeichenkette mit aktuellen bzw. peak-Speicherverbrauch
	 * @param		boolean	$current=false		true zeigt aktuellen, false zeigt Spitzen-Speicherverbruach
	 * @return	string
	 */
	public static function getMemoryUsage($current=false) {
		if($current) {
			$memUsed = memory_get_usage(true);
		} else {
			$memUsed = memory_get_peak_usage(true);
		}
		$memUsed = $memUsed / pow(2,20);	//	Usage in MB
		$memTotal = Util::getIniValue('memory_limit', 'M');
		$memory = number_format($memUsed,1,'.',"'") . 'M (' . number_format($memUsed/$memTotal	*	100,0) . '%)';
		return $memory;
	}
	
	/**
	 * Ermittelt den Wert einer INI-Variablen, interpretiert die Postfix-Notation für Speichereinheiten
	 * und konvertiert diese in eine Ziel-Einheit, sofern eine definiert ist.
	 * @param		string	$variable					INI-Variable
	 * @param		string	$measure					[K]ilo, [M]ega [G]iga
	 * @return	numeric										Ini-Wert in der Zielbemessung
	 */
	public static function getIniValue($variable, $measure='') {
		$value = ini_get($variable);
		switch($measure) {
			case 'K':		$pow = -10;	break;
			case 'M':		$pow = -20;	break;
			case 'G':		$pow = -30;	break;
			default:		$pow = 0;
		}
		if(!strpos($value,'K') === false)		$pow += 10;
		if(!strpos($value,'M') === false)		$pow += 20;
		if(!strpos($value,'G') === false)		$pow += 30;
		return (float)$value*pow(2,$pow);
	}
	
	/*
	 * Dateisystem
	 */

	/**
	 * Löscht den Inhalt eines Verzeichnisses
	 * @param string $dir									Verzeichnis, dessen Inhalt gelöscht werden soll
	 * @param boolean $deleteRoot					Löscht das Verzeichnis selbst
	 */
	public static function cleanDir($dir, $deleteRoot=false) {
		if(!$fh = @opendir($dir))			return;
		while(false !== ($file = readdir($fh))) {
			if($file == '.' || $file == '..')		continue;
			if(!@unlink($dir.'/'.$file))		Util::cleandir($dir.'/'.$file, true);
		}
		closedir($fh);
		if($deleteRoot)		@rmdir($dir);
	}

	/*
	 * Date/Time
	 */

	/**
	 * Ermittelt eine Zeitanzeige aufgrund anzahl Sekunden
	 *	@param	integer	$seconds					Anzahl Sekunden
	 *	@return	string										Zeit 'human-readable'
	 */
	public static function lifetime($seconds) {
		$rc = '';
		$days = 0;
		$hours = 0;
		$minutes = 0;
		//	Tage
		if($seconds >= 86400) {
			$days = intval($seconds / 86400);
			$seconds -= $days	*	86400;
			$rc .= $days.'d';
		}
		//	Stunden
		if($seconds >=3600) {
			$hours = intval($seconds / 3600);
			$seconds -= $hours	*	3600;
		}
		if($hours || $rc) {
			if($hours <10)		$rc .= '0';
			$rc .= $hours.':';
		}
		//	Minuten
		if($seconds >=60) {
			$minutes = intval($seconds / 60);
			$seconds -= $minutes	*	60;
		}
		if($minutes <10)	$rc .= '0';
		$rc .= $minutes.':';
		//	Sekunden
		if($seconds <10 && $rc)		$rc .= '0';
		$rc .= intval($seconds);
		return $rc;
	}
	
	/*
	 * Individualisierung
	 */

	/**
	 * Ermittelt eine Klasse ab Basisklasse und prüft Customer-Spezialisierungen
	 * @param	string	$baseClass					Basisklasse
	 * @return string											Verfügbare Zielklasse (Basisklasse rsp. Customer-Spezialisierung)
	 * @todo Der Einbezug des Customer-Tags über Systemparameter
	 */
	public static function getClass($baseClass) {
		$customer = '';
		if($customer != '') {
			$class = 'Cust_'.$customer.'_'.$baseClass;
			try {
				if(@class_exists($class))		return $class;
			} catch(Exception $e) {}
		}
		try {
			if(@class_exists($baseClass))		return $baseClass;
		} catch(Exception $e) {}
	}

}