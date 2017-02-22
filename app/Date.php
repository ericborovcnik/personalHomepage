<?php
/**
 * Datumsklasse bietet bequeme Methoden zur Datumsbearbeitung an
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-01	eb	from revis
 */
class Date {

	public static $millenium=30;					//	Jahresgrenze für 2-stellige Jahreszahlen. Darunter wird als 2000 und darüber als 1900 sublimiert
	public static $day;										//	Enthält den Tag des letzten Funktionsergebnisses
	public static $month;									//	Enthält den Monat des letzten Funktionsergebnisses
	public static $year;									//	Enthält das Jahr des letzten Funktionsergebnisses
	public static $hour;									//	Stunde			Bei Timestamp-Angaben werden auch Stunden, Minute, Sekunden aufgelöst
	public static $minute;								//	Minute
	public static	$second;								//	Sekunden 

	/**
	 * Ermittelt die Anzahl aktiver Monate im Jahr im Hinblick auf ein Beginn-End-Intervall
	 * @param	string	$beginDate					Startdatum der Betrachtungsperiode
	 * @param	string	$endDate						Enddatum der Betrachtungsperiode
	 * @param	integer	$refDate						Betrachtungsdatum
	 * @return	integer										Anzahl aktiver Monate
	 */
	public static function activeMonths($beginDate=null, $endDate=null, $refDate=null) {
		if(!$refDate)				return 0;
		$beginDate = self::get($beginDate);
		$beginYear = self::$year;
		$beginMonth = self::$month;
		$endDate = self::get($endDate);
		$endYear = self::$year;
		$endMonth = self::$month;
		$refDate = self::get($refDate);
		$refYear = self::$year;
		if($beginYear > $refYear)									return 0;			//	Beginn in Folgeperiode
		if($endYear < $refYear && $endYear != 0)		return 0;			//	Ende in Vorperiode
		if($beginYear == 0 || $beginYear < $refYear)	$beginMonth = 1;
		if($endYear == 0 ||$endYear > $refYear)			$endMonth = 12;
		if($endMonth<$beginMonth)								return 0;
		return $endMonth - $beginMonth + 1;
	}

	/**
	 * Berechnet von einem Ausgangsdatum ein neues Datum mit Offset und Offset-Einheit (d,m,y)
	 * @param		string $anydate						Datum in beliebiger Notation
	 * @param		integer $count						Anzahl Einheiten
	 * @param		string $dmy								Einheit (d:Tage, m:Monate, y:Jahre)
	 * @param		string $format						Datumsformat
	 * @return	string										Datum in ISO-Notation
	 */
	public static function add($anydate=null, $count=0, $dmy='d', $format='Y-m-d') {
		$datestr = self::get($anydate, 'Y-m-d', true);
		if(!$datestr)			return self::get($anydate, $format);
		$date = new Zend_Date($datestr);
		switch($dmy) {
			case 'd':		$date->add($count, Zend_Date::DAY);			break;
			case 'm':		$date->add($count, Zend_Date::MONTH);		break;
			case 'y':		$date->add($count, Zend_Date::YEAR);		break;
		}
		return self::get($date->tostring('yyyy-MM-dd'), $format);
	}
	
	/**
	 * Ermittelt ein Datum per Monatsersten
	 * @param	string	$anydate							Ausgangsdatum
	 * @return	string											Datum per Monatsersten
	 */
	public static function beginOfMonth($anydate=null, $format='Y-m-d') {
		self::set($anydate);
		return self::get(self::$year.'-'.self::$month, $format);
	}
	
	/**
	 * Normalisiert ein Datum aus einer Notation y-m-d oder d.m.y oder Unix-Timestamp mit und ohne Quoter
	 * @param	mixed [$anydate=false]			Datum oder Symbol 'today', 'self'
	 * @param	string $format							Datumsformat oder 'user' oder 'unix'
	 * 																		d		day with leading zeros				01..31
	 * 																		D		Weekday in short-notation			Mon..Sun
	 * 																		j	 	day without leading zeros			1..31
	 * 																		N		ISO-Weekday										1..7 (=Monday..Sunday)
	 * 																		S		English ordinal suffix				st, nd, rd, th.
	 * 																		W		ISO-Weeknumber								1..52
	 * 																		F		Month													January..December
	 * 																		m		Month with leading zeros			01..12
	 * 																		M		Month in short-notation				Jan..Dec
	 * 																		n		Month without leading zeros		1..12
	 * 																		t		Number of days in given month	28..31
	 * 																		L		Wheter it's a leap year				0,1
	 * 																		Y		Year with 4 digits						1990..9999
	 * 																		y		Year in 2 digits							31..99
	 * 																		G		Hour withou leading zeros			0..23
	 * 																		H		Hour with leading zeros				00..23
	 * 																		i		Minutes with leading zeros		00..59
	 * 																		s		Minutes with leading zeros		00..59
	 * 																		c		ISO-Date											2004-02-12T15:19:21+02:00
	 * 																		r		RFC 2822 formatted date				Thu, 21 Dec 2000 16:01:07 +0200
	 * @param boolean $nullDate						true übermittelt NULL anstelle '0000-00-00'
	 * @return	string										Datum in der Notation y-m-d bzw. Null-Datum
	 */
	public static function get($anydate=null, $format='Y-m-d', $nullDate=false ) {
		if($format == 'user')		$format = $_SESSION['userlocaledata']->date;
		$year		=	0;
		$month	=	0;
		$day		=	0;
		$hour		= 0;
		$minute	=	0;
		$second	=	0;		
		//
		//	Format normalisieren
		if(is_null($format) || $format === false || $format === '') {
			$format = 'Y-m-d';
		}
		//
		//	Quotes eliminieren
		$anydate = str_replace('"','',str_replace("'",'',$anydate));
		if($anydate === null
				|| $anydate === false
				|| $anydate === ''
				|| $anydate === '0'
				|| $anydate === 0)		$anydate = '0000-00-00';

		if($anydate === 'today' || $anydate === 'now')		$anydate = (int)microtime(true);
		if($anydate === 'self')									return self::get(self::$year.'-'.self::$month.'-'.self::$day, $format);
		if(strlen($anydate) == 2 && is_numeric($anydate))	$anydate = (int)$anydate;
		//
		//	Integer-Werte normalisieren: Jahr (1900-2100), Milleniumjahr (00-99), Unix-Timestamp (>100000) oder ungültig
		if(strpos($anydate,'.') === false && strpos($anydate,'-') === false && strpos($anydate,'/') === false && is_numeric($anydate)) {
			if($anydate >= 1900 && $anydate <= 2100) {
				$anydate .= '-01-01';
			} else if($anydate < 100) {
				if($anydate > self::$millenium) {
					$anydate = (1900 + $anydate).'-01-01';
				} else {
					$anydate = (2000 + $anydate).'-01-01';
				}
			} else if($anydate > 100000) {
				//
				//	Unix-Timestamp
				$date = new DateTime();
				$date->setTimestamp($anydate);
				$anydate = $date->format('Y-m-d');
				$hour = $date->format('G');
				$minute = $date->format('i');
				$second = $date->format('s');
			} else {
				$anydate = '';
			}
		}
		//
		//	Zeit aus Datumsstring extrahieren
		$timeStr = '';
		if(strpos($anydate,'T') == 10 && strlen($anydate) == 25) {
			$timeStr = substr($anydate, 11,8);
			$anydate = substr($anydate, 0, 10);
			$timeArr = explode(':', $timeStr);
			$hour = $timeArr[0];
			$minute = $timeArr[1];
			$second = $timeArr[2];
		}
		if(strpos($anydate, ' ') !== false && strpos($anydate, ':') !== false) {
			foreach(explode(' ', $anydate) as $timeItem) {
				if(strpos($timeItem, ':') !== false) {
					$timeArr = explode(':', $timeItem);
					$hour = $timeArr[0];
					$minute = $timeArr[1];
					$second = $timeArr[2];
				}
			}
		}
		//
		//	Datumsstring in Jahr - Monat - Tag auschlüsseln y-m-d | y-m | m-y | d.m.y | m.y | y.m | m/d/y | m/y | y/m
		do {
			if(strpos($anydate,'-') !== false) {					//	y-m-d | y-m | m-y
				$p = explode('-', $anydate);
				if(count($p) >= 3) {
					$year = $p[0];		$month = $p[1];		$day = $p[2];
				} else {
					$year = $p[0];		$month = $p[1];		$day = 1;
				}
				break;
			}
			if(strpos($anydate,'.') !== false) {					//	d.m.y | m.y | y.m
				$p = explode('.', $anydate);
				if(count($p) >=3) {
					$day = $p[0];			$month = $p[1];		$year = $p[2];
				} else {
					$month = $p[0];		$year = $p[1];		$day = 1;
				}
				break;
			}
			if(strpos($anydate,'/') !== false) {					//	m/d/y | m/y | y/m
				$p = explode('/', $anydate);
				if(count($p) >= 3) {
					$month = $p[0];		$day = $p[1];		$year = $p[2];
				} else {
					$month = $p[0];		$year = $p[1];	$day = 1;
				}
				break;
			}
				
		} while(false);
		//
		//	Monat und Jahr vertauscht?
		if($month > 12 && $year <=12) {
			$dummy = $month;	$month = $year; $year = $dummy;
		}
		//
		//	Check Milleniumyear
		if($month != 0 && $day != 0) {
			if(strlen($year) <=2) {
				if($year > self::$millenium) {
					$year = 1900 + $year;
				} else {
					$year = 2000 + $year;
				}
			}
		}
		//
		//	SAP-unlimited? 9999-12-31 => 0000-00-00
		if($year == '9999' && $month == '12' && $day='31') {
			$year = 0;			$month = 0;			$day = 0;
		}
		//
		//	Vorgaben plausibel?
		if($year<0 || $year > 9999)		{$year = 0; $month = 0; $day = 0;}
		if($month<0 || $month >12)		{$year = 0; $month = 0; $day = 0;}
		if($day<0 || $day > 31)				{$year = 0; $month = 0; $day = 0;}
		self::$day		= (int)$day;
		self::$month	= (int)$month;
		self::$year		= (int)$year;
		self::$hour		=	(int)$hour;
		self::$minute	=	(int)$minute;
		self::$second	=	(int)$second;
		//
		//	Ergebnis aufbereiten
		if($year == 0) {
			return $nullDate ? null : ($format == 'Y-m-d' ? '0000-00-00' : '');
		}
		//
		//	Unix-Timestamp
		if($format == 'unix') {
			return mktime(self::$hour, self::$minute, self::$second, self::$month,self::$day,self::$year);
		}
		$result = date($format, mktime(self::$hour, self::$minute, self::$second, self::$month, self::$day, self::$year));
		if(strpos($format, 'F') !== false) {	//	Übersetzungsmatrix für Monate in langer Schreibweise
			foreach(explode(',','January,February,March,April,May,June,July,August,September,October,November,December') as $src) {
				$result = str_replace($src, Language::get($src), $result);
			}
		}
		if(strpos($format, 'M') !== false) {	//	Übersetzungsmatrix für Monate in kurzer Schreibweise
			foreach(explode(',', 'Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec') as $src) {
				$result = str_replace($src, Language::get($src), $result);
			}
		}
		if(strpos($format, 'l') !== false) {	//	Übersetzungsmatrix für Wochentage in langer Schreibweise
			foreach(explode(',', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday') as $src) {
				$result = str_replace($src, Language::get($src), $result);
			}
		}
		if(strpos($format, 'D') !== false) {	//	Übersetzungsmatrix für Wochentage in kurzer Schreiweise
			foreach(explode(',', 'Mon,Tue,Wed,Thu,Fri,Sat,Sun') as $src) {
				$result = str_replace($src, Language::get($src), $result);
			}
		}
		return $result;
	}
	
	/**
	 * Ermittelt die Anzahl Tage/Monate/Jahre zwischen zwei Daten
	 * diff($a,$b) > 0 => $a jünger als $b
	 * diff($a,$b) < 0 => $a älter als $b
	 * @param		string	$date							Prüfdatum
	 * @param		string	$refdate					Referenzdatum
	 * @param		string	[$dmy='d']				Bemessung in d:Tage, m:Monaten,y:Jahren
	 * @param		string	[$round=true]			Ergebniss auf ganze Einheiten runden
	 * @return	number
	 */
	public static function diff($date=null, $refdate=null, $dmy='d', $round=true) {
		$datestr = self::get($date,'Y-m-d', true);
		$refdatestr = self::get($refdate, 'Y-m-d', true);
		if(!$datestr || !$refdatestr)					return 0;
		$d1 = mktime(0,0,0,substr($datestr,5,2),substr($datestr,8,2),substr($datestr,0,4));
		$d2 = mktime(0,0,0,substr($refdatestr,5,2),substr($refdatestr,8,2), substr($refdatestr,0,4));
		$diff = $d1 - $d2;
		switch($dmy) {
			case 'd':		$diff = $diff / 86400;		break;
			case 'm':		$diff = $diff / 2628000;	break;
			case 'y':		$diff = $diff / 31536000;	break;
		}
		if($round) $diff = round($diff);
		return $diff;
	}

	/**
	 * Ermittelt ein Datum per Ende Monat
	 * @param string $anydate							Datum in beliebiger Notation
	 * @param string $format							Formatanweisung
	 */
	public static function endOfMonth($anydate=null, $format='Y-m-d') {
		self::set($anydate);
		if(self::$year == 0)		return self::get('self', $format);
		return self::get(strtotime('-1 second', strtotime('+1 month', strtotime(static::$year.'-'.static::$month.'-01'))), $format);
	}

	/**
	 * Ermittelt den älteren von zwei Daten
	 * @param	string	$date1								Datum1
	 * @param	string	$date2								Datum2
	 * @param	string $format								Zielformat
	 * @param	boolean	$ignoreNull						true vergleicht keine NULL-Daten
	 * @return	string											Das ältere Datum
	 */
	public static function getOlder($date1=null, $date2=null, $format='Y-m-d', $ignoreNull=true) {
		$date1 = self::get($date1, 'Y-m-d', true);
		$date2 = self::get($date2, 'Y-m-d', true);
		if($ignoreNull) {
			if(!$date1)								return self::get($date2, $format);
			if(!$date2)								return self::get($date1, $format);
		}
		if(self::diff($date1, $date2) > 0) {
			return self::get($date2, $format);
		}
		return self::get($date1, $format);
	}

	/**
	 * Ermittelt den jüngeren von zwei Daten
	 * @param	string $date1								Datum 1
	 * @param	string $date2								Datum 2
	 * @param string $format							Datumsformat 
	 * @param	boolean	$ignoreNull					true vergleicht keine NULL-Daten
	 * @return	string										das jüngere Datum
	 */
	public static function getYounger($date1=null, $date2=null, $format='Y-m-d', $ignoreNull=true) {
		$date1 = self::get($date1, 'Y-m-d', true);
		$date2 = self::get($date2, 'Y-m-d', true);
		if($ignoreNull) {
			if(!$date1)								return self::get($date2, $format);
			if(!$date2)								return self::get($date1, $format);
		}
		if(self::diff($date1, $date2) < 0) {
			return self::get($date2, $format);
		}
		return self::get($date1, $format);
	}

	/**
	 * Prüft, ob ein Objekt mit STart- und Enddatum per Stichdatum aktiv ist.
	 * @param	string	$objStartDate				Objekt-Startdatum
	 * @param	string	$objEndDate					Objekt-Enddatum
	 * @param	string	$refDate						Referenzdatum
	 * @return	boolean											true, wenn das Objekt per Stichdatum aktiv ist.
	 */
	public static function isActive($objStartDate=null, $objEndDate=null, $refDate=null) {
		$objStartDate = self::get($objStartDate, 'Y-m-d', true);
		$objEndDate = self::get($objEndDate, 'Y-m-d', true);
		$refDate = self::get($refDate, 'Y-m-d', true);
		if(!$objStartDate && !$objEndDate)													return true;
		if(!$refDate)																								return false;
		if($objEndDate && self::diff($objEndDate, $refDate)<0)			return false;
		if($objStartDate && self::diff($objStartDate, $refDate)>0)	return false;
		return true;
	}

	/**
	 * Prüft, ob ein Zeitraum [start..ende] innerhalb eines anderen zeitraums (teil)aktiv ist
	 * Aktiv, wenn Objektperiode oder Referenzperiode NULL
	 * @param	string	$objStartDate				Startdatum des Objekts
	 * @param	string	$objEndDate					Enddatum des Objekts
	 * @param	string	$chkecStartDate			Startdatum des Referenzbereichs
	 * @param	string	$checkEndDate				Enddatum des Referenzbereichs
	 * @return boolean										true, wenn Objektdatum partiell oder vollständig im Referenzbereich liegt.
	 */
	public static function isActiveWithin($objStartDate=null, $objEndDate=null, $checkStartDate=null, $checkEndDate=null) {
		$objStartDate = self::get($objStartDate, 'Y-m-d', true);
		$objEndDate = self::get($objEndDate, 'Y-m-d', true);
		$checkStartDate = self::get($checkStartDate, 'Y-m-d', true);
		$checkEndDate = self::get($checkEndDate, 'Y-m-d', true);
		if(!$objStartDate && !$objEndDate)												return true;
		if(!$checkStartDate && !$checkEndDate)										return true;
		if($objStartDate) {
			if($checkEndDate && $checkEndDate < $objStartDate)			return false;
		}
		if($objEndDate) {
			if($checkStartDate && $checkStartDate > $objEndDate)		return false;
		}
		return true;
	}

	/**
	 * Prüft, ob ein Null-Datum vorliegt
	 * @param	string	$date									Datum
	 * @return	boolean											true, wenn das Datum ein NULL-Datum repräsentiert
	 */
	public static function isNull($date=null) {
		return self::get($date) == '0000-00-00';
	}

	/**
	 * Prüft, ob ein gültiges Datum vorliegt
	 * @param	string	$anydate							Datum
	 * @return	boolean											true, wenn das Datum gültig ist
	 */	
	public static function isDate($anydate=null) {
		$datestr = self::get($anydate);
		return checkdate(self::$month,self::$day,self::$year);
	}

	/**
	 * Ermittelt das Quartal in dem sich das Datum befindet
	 * @param	string	$anydate							Datum
	 * @return	integer											Quartal false, oder 1..4
	 */
	public static function quarter($anydate=null) {
		self::get($anydate);
		if(self::$month == 0)		return false;
		
		return floor((self::$month-1)/3)+1;
	}
	
	/**
	 * Ermittelt den Monat in dem sich das Quartal befindet
	 * @param integer $quarter
	 * @param string $format
	 */
	public static function quarterToMonth($quarter=null, $format='m') {
		if(!$quarter || $quarter < 1 or $quarter > 4)		return false;
		$month = $quarter * 3;
		return self::get('2000-'.$month, $format);
	}

	/**
	 * Definiert ein Datum
	 * @param	mixed		$yearDate							Datum oder Jahr
	 * @param	integer	$month								Monat
	 * @param	integer	$day									Tag
	 * @return	string											Datum
	 */
	public static function set($yearDate=null, $month=null, $day=null) {
		if(is_numeric($yearDate) && is_numeric($month)) {
			if($yearDate < 0 || $yearDate > 2100 || $month< 1 || $month > 12) {
				$date = false;
			} else {
				$date = $yearDate.'-'.$month;
				if(is_numeric($day)) {
					if($day < 1 || $day > 31) {
						$date = false;
					} else {
						$date .= '-'.$day;
					}
				}
			}
		} else {
			$date = $yearDate;
		}
		return self::get($date);
	}
	
}