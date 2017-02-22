<?php
/**
 * Die Bereichsklasse dient der Konkatenierung von Excel-Teilbereichen.
 * Sie liefert eine Notation in der Syntax A1,B10:B20,E3' als Komma-separierte Liste
 * Die Teilbereich werden mit range->add()->add()->add() bereitgestellt
 * Die Klasse wird ausschliesslich von Export_XL und Export_XLRange bedient.
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-05-02	eb	from revis
 */
class Export_Range {

	public $ranges;

	private $_maxRow;
	private $_maxCol;
	
	/**
	 * Initialisiert den Bereich mit den Max-Koordinaten
	 * @param		integer	$maxRow						Aktuelle MaxRow
	 * @param		integer	@maxcol						Aktuelle Maxcol
	 */
	public function __construct($maxRow, $maxCol) {
		$this->_maxRow = $maxRow;
		$this->_maxCol = $maxCol;
		$ranges = '';
	}
	
	/**
	 * Fügt ein Adress-Satz dem Gesamtbereich hinzug
	 * @param		integer	$rowA							Zeile A
	 * @param		integer	$colA							Spalte A
	 * @param		integer	$rowB							Zeile B
	 * @param		integer	$colB							Spalte B
	 * @return	object										Export_Range
	 */
	public function add($rowA=false, $colA=false, $rowB=false, $colB=false) {
		//	Zelle A
		if($rowA == -1)	$rowA = $this->_maxRow;
		if($colA == -1)	$colA	=	$this->_maxCol;
		$cellA = '';
		if($colA) {
			$cellA = $this->strCol($colA);
			$hasCol = true;
		} 
		if($rowA) {
			$cellA .= $rowA;
			$hasRow = true;
		}
		//	Zelle B
		$cellB = '';
		if($rowB == -1)	$rowB = $this->_maxRow;
		if($colB == -1)	$colB = $this->_maxCol;
		if($hasCol) {
			if($colB == false)	$colB = $colA;
			$cellB = $this->strCol($colB);
		}
		if($hasRow) {
			if($rowB == false)	$rowB = $rowA;
			$cellB .= $rowB;
		}
		if($this->ranges)	$this->ranges .=',';
		$this->ranges .= $cellA;
		if($cellB)	$this->ranges	.= ':'.$cellB;
		return $this;
	}

	/**
	 * Konvertiert eine Spalten aus der Nummern- in die Zeichen-Notation (1->A)
	 * @param		integer	$col							Spalte als Zahl
	 * @return	string										Spalte als Zeichen
	 */
	public function strCol($col) {
		if($col<=26) {
			$f3=false;
			$f2=false;
			$f1=$col;
		} else {
			$rest = $col  -26;
			$f3 = floor($rest / 676);
			if($f3 == $rest / 676)	$f3--;
			$rest -= $f3 * 676;
			$f2 = floor($rest / 26);
			if($f2 == $rest / 26)		$f2--;
			$f1 = $rest  - $f2 * 26;
			$f2++;
		}
		$colStr = '';
		if($f3)	$colStr .= chr(64 + $f3);
		if($f2) $colStr .= chr(64 + $f2);
		$colStr .= chr(64 + $f1);
		return $colStr;
	}

}