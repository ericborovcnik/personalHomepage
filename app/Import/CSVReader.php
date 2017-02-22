<?php
/**
 * CSV-Reader
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-11	eb	from revis
 */
class Import_CSVReader {

	/**
	 * @var array $line										Array mit den Zellinhalten der letzten Datenzeile
	 */
	public $line;
	/**
	 * @var string $data									String mit der zuletzt gelesenen Datenzeile
	 */
	public $data;
	/**
	 * @var boolean $utf8encode						True wenn die Daten in UTF8 vorliegen
	 */
	public $utf8encode;

	private $file;
	private $delimiter;
	private $enclosure;
	private $escape;
	private $fh;		

	/**
	 * Initialisiert eine CSV-Datei
	 * @param string $file								Dateiname mit absoluter oder zu webroot relativer Pfadreferenz
	 * @param string $delimiter						Trennzeichen
	 * @param string $enclosure						Quoter
	 * @param string $escape							Escape-Sequenz
	 * @return boolean										true, wenn die Datei erfolgreich zum lesen geöffnet werden konnte
	 */
	public function __construct($file, $delimiter=';', $enclosure='"', $escape='\\') {
		$this->file				= $file;
		$this->delimiter	=	$delimiter;
		$this->enclosure	=	$enclosure;
		$this->escape			=	$escape;
		$this->utf8encode = false;
		try {
			$this->fh = fopen($file, 'r');
		} catch(Exception $e) {
			Log::err($e->getMessage());
			return false;
		}
		if(!$this->fh)	return false;
		$cnt = 0;
		while(!$this->eof()) {
			$cnt++;
			$line = fgets($this->fh);
			if(!$this->is_utf8($line)) {
				$this->utf8encode = true;
				break;
			}
			if($cnt>1000)	break;
		}
		fclose($this->fh);
		$this->fh = fopen($file, 'r');
	}

	/**
	 * Prüft, ob das Dateiende erreicht ist
	 * @return boolean										true, wenn das Dateiende erreicht ist		
	 */
	public function eof() {
		if(!$this->fh)		return false;
		return feof($this->fh);
	}

	/**
	 * Erneutes öffnen der Datei mit Pointer am Anfang der Datei
	 */
	public function open() {
		fclose($this->fh);
		$this->fh = fopen($this->file, 'r');
	}

	/**
	 * Liesst eine Datenzeile und gliedert die CSV-Zellstruktur manuell
	 * return array
	 */
	public function readline() {
		if(!$this->fh)			return false;
		$this->line = array();
		$line = fgets($this->fh);
		$this->data = $line;
		while($line == '' && !$this->eof()) {
			$line = fgets($this->fh);
		}
		if($this->utf8encode)		$line = utf8_encode($line);
		while($line != '') {
			$item = $this->extractValue($line); 
			$this->line[] = $item;
		}
		return $this->line;
	}

	/**
	 * Extrahiert einen CSV-Wert und kürzt die Datenzeile
	 * @param	string &$line
	 * @return string 
	 */
	private function extractValue(&$line) {
		//	Beginnt die Sequenz mit einem Quoter?
		if(substr($line,0,1) == $this->enclosure) {
			//Suche den nächsten alleinstehenden Quoter
			for($p=1; $p<strlen($line); $p++) {
				if(substr($line,$p,1) == $this->enclosure) {
					if($p == strlen($line)-1) {	//	Ende erreicht
						$item = str_replace($this->enclosure.$this->enclosure,$this->enclosure, substr($line, 1, $p - 1));
						$line = '';
						return $item;
					}
					if(substr($line, $p+1, 1) ==  $this->enclosure) {	// Innerer Doublequote ignorieren
						$p++;
					} else {
						//Schlussquote gefunden
						$item = str_replace($this->enclosure.$this->enclosure, $this->enclosure, substr($line, 1, $p - 1));
						$remaining = strlen($line) - $p - 2;
						
						if($remaining > 0) {
							$line = substr($line, -$remaining);
						} else {
							$line = '';
						}
						return $item;
					}
				}
			}
		} else {
			// Suche nach der ersten Fundstelle an Delimiter
			$p = strpos($line, $this->delimiter);
			if($p !== false) {
				$item = substr($line, 0, $p);
				if(strlen($line) == $p + 1) {
					$line = '';
				} else {
					$line = substr($line, -strlen($line) + $p + 1);
				}
			} else {
				$line = str_replace(chr(13), '', $line);
				$line = str_replace(chr(10), '', $line);
				$item = $line;
				$line = '';
			}
		}
		return $item;
	}
	
	/**
	 * Ermittelt den Wert einer Zelle
	 * @param integer $idx								Spaltenindex [0 .. n-1]
	 * @return string
	 */
	public function value($idx) {
		try {
			$value = $this->line[$idx];
		}
		catch(Exception $e) {
			return false;
		}
		return $value;
	}
	
	/**
	 * Ermittelt die Boolean-Interpretation eines Zellwerts
	 * @param integer $idx								Spaltenindex [0 .. n-1]
	 * @return boolean
	 */
	public function valueBool($idx) {
		switch($this->value($idx)) {
			case 0:
			case '0':
			case false:
			case 'false':
			case 'FALSCH':
				return false;
			break;
			default:	
				return true;
		}
	}
	
	/**
	 * Ermittelt die Datum-Interpretation eines Zellwerts
	 * @param integer $idx								Spaltenindex [0 .. n-1]
	 * @param string $format							Optional: Datumsformat
	 * @return string											Datum
	 */
	public function valueDate($idx, $format='yyyy-MM-dd') {
		$value = trim($this->value($idx));
		if($value == ''						||
			 $value == 'NULL'				||
			 $value == '(NULL)'			||
			 $value == '0000-00-00'	||
			 $value == '0000.00.00') return '';
		try {
			$date = new Zend_Date($value);
		} catch (Exception $e) {
			Log::err('['.$value.'] - invalid date');
			return '';
		}
		return $date->get($format);
	}

	/**
	 * Prüft, ob eine Zeichenkette im UTF8-Format vorliegt
	 * @param string $str									Teststring
	 * @return boolean										true, wenn die Zeichenkette in UTF-8 encodiert ist
	 */
	public function is_utf8($str) {
  	$strlen = strlen($str);
  	for($i=0; $i<$strlen; $i++) {
    	$ord = ord($str[$i]);
    	if($ord < 0x80)															continue; // 0bbbbbbb
    	elseif(($ord&0xE0)===0xC0 && $ord>0xC1)			$n = 1;		// 110bbbbb (exkl C0-C1)
    	elseif(($ord&0xF0)===0xE0)									$n = 2;		// 1110bbbb
    	elseif(($ord&0xF8)===0xF0 && $ord<0xF5)			$n = 3;		// 11110bbb (exkl F5-FF)
    	else return false;													// ungültiges UTF-8-Zeichen
    	for($c=0; $c<$n; $c++) {										// $n Folgebytes? // 10bbbbbb
      	if(++$i===$strlen || (ord($str[$i])&0xC0)!==0x80)		return false;		// ungültiges UTF-8-Zeichen
      }
  	}
  	return true;																	// kein ungültiges UTF-8-Zeichen gefunden
	}

}