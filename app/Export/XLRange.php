<?php
/**
 * XLRange-Klasse für Berecihsabgrenzungen in PHPExcel
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @versoin		2016-01-26	eb	from revis
 */
class Export_XLRange {

	/*
	 * Instance Variables
	 */

	/**
	 * @var Export_XL											Verweis auf das Export_XL-Objekt
	 */
	public $xl;
	public $rowA;													//	Ursprungszeile beginnend mit 1
	public $colA;													//	Ursprungsspalte beginnend mit 1
	public $lastRow;											//	Zuletzt geschriebene Zeile
	public $lastCol;											//	Zuletzt geschriebene Spalte
	public $maxRow;												//	Maximale Zeile
	public $maxCol;												//	Maximale Spalte
	public $row;													//	Aktuelle Zeile (initialisiert mit 1)
	public $col;													//	Aktuelle Spalte (initialisiert mit 1)
	public $selection;										//	Array mit Zelladressen der aktuellen Auswahl (Adressen bereits auf Excel-Sheet normalisiert)
	private $_key;												//	Schlüsselbegriff das Bereichs

	/*
	 * General Methods
	 */

	/**
	 * Initialisiert den Bereich
	 * @param Export_XL $xl								Tragendes Excel-Objekt
	 * @param string $key									Identifikationsschlüssel
	 * @param integer|string $rowARange		Zeile A oder Bereich in AB-Notation
	 * @param integer $colA								Spalte A
	 * @param integer $rowB								Zeile B
	 * @param integer $colB								Spalte B
	 */
	public function __construct($xl, $key, $rowARange='A1', $colA=false, $rowB=false, $colB=false) {
		$this->xl = $xl;
		$this->_key = $key;
		if(is_numeric($rowARange)) {
			$this->rowA = $rowARange;
			$this->colA = $colA ? $colA : 1;
			$this->maxRow = $rowB ? $rowB - $this->rowA + 1 : 1;
			$this->maxCol = $colB ? $colB - $this->colA + 1 : 1;
		} else {
			$cells = explode(':', $rowARange);
			$this->rowA = $this->intRow($cells[0]);
			$this->colA = $this->intCol($cells[0]);
			if(count($cells)>1) {
				$this->maxRow = $this->intRow($cells[1]) - $this->rowA + 1;
				$this->maxCol = $this->intCol($cells[1]) - $this->colA + 1;
			}
			if($this->maxRow<1)	$this->maxRow = 1;
			if($this->maxCol<1) $this->maxCol = 1;
		}
		$this->row = 1;
		$this->col = 1;
		$this->lastRow = 1;
		$this->lastCol = 1;
		$this->selection = array();
	}

	/*
	 * Add Content
	 */

	/**
	 * Einen Zellkommentar hinzufügen
	 * @param string $comment							Kommentar
	 * @param integer|string $rowRange		Zeile oder Bereich in AB-Notation
	 * @param integer $col								Spalte
	 * @param string $style								Style-Anweisung
	 */
	public function addComment($comment, $rowRange=false, $col=false, $style=false) {
		$this->selectRange($rowRange, $col);
		foreach($this->selection as $range) {
			$this->xl->addComment($comment, $range, false, $style);
		}
	}

	/**
	 * Eine Zelle mit einem Hyperlink verbinden
	 * @param string $target							Ziel-URL
	 * @param integer|string $rowRange		Zeile oder Bereich in AB-Notation
	 * @param integer $col								Spalte
	 */
	public function addHyperlink($target, $rowRange=false, $col=false) {
		$this->selectRange($rowRange, $col);
		foreach($this->selection as $range) {
			$this->xl->addHyperlink($target, $range);
		}
	}

	/**
	 * Fügt ein Bild in einen Zellbereich ein
	 * @param string $startCell						Startzelle in AB-Notation
	 * @param string $imageFileName				Dateiverweis auf die Bilddatei
	 * @param string $description					Bildbeschreibung
	 * @param integer $width							Bildbreite
	 * @param integer $height							Bildhöhe
	 */
	public function addImage($startCell, $imageFileName, $description='', $width=null, $height=null) {
		$this->xl->addImage($this->xlRange($startCell), $imageFileName, $description, $width, $height);
	}

	/*
	 * Ranges, Selection, Adressing
	 */

	/**
	 * Erweitert die aktuelle Auswahl um einen Bereich
	 * @param integer|string $rowARange		Zeile A oder Bereich in AB-Notation
	 * @param string $colA								Spalte A
	 * @param string $rowB								Zeile B
	 * @param string $colB								Spalte B
	 */
	public function addRange($rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB, true);
	}

	/**
	 * Kopiert eine Zeile mit allen Inhalten (Werte, Formeln, Formate), indem
	 * diese direkt darunter eingefügt wird. Bestehender Inhalt wird um die eingefügte Zeilen nach unten verschoben
	 * @param		integer	$numRows					Anzahl Zeilen, die eingefügt werden
	 */
	public function copyRow($row=1,$numRows=1) {
		//	Durch den Zeileneinschub verändert sich die Range-Dimension
		if($numRows == 0) {
			Log::err('Error: Cannot copy row zero times');
			return;
		}
		if($row > $this->maxRow) {
			$this->maxRow = $row+$numRows;
		} else {
			$this->maxRow += $numRows;
		}
		$this->xl->copyRow($this->xlRow($row), $numRows);
	}

	/**
	 * Fixiert die Tabelle beim aufgeführten Zellbereich
	 * @param		mixed		$rowARange				Zeile 1 oder Zellbereich für den Freeze
	 * @param		integer	$colA							Spalte 1
	 * @param		integer	$rowB							Zeile 2
	 * @param		integer	$colB							Spalte 2
	 */
	public function freezePane($rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		$this->xl->freezePane($this->selection[0]);
	}

	/**
	 *	Diese Methode gibt die Koordinaten einer Zelle der Range in Koordinaten auf dem Sheet zurück.
	 *	@param 	mixed 	$cellAB 					Zelle in AB-Notation
	 */
	public function getAbsolutCellCoordinates($cellAB)
	{
		$row = $this->xl->intRow($cellAB);
		$col = $this->xl->intCol($cellAB);

		$realRow = $this->rowA + $row-1;
		$realCol = $this->colA + $col-1;

		$realCoord = $this->xl->getCoordinate($realRow, $realCol);

		return $realCoord;
	}

	/**
	 *	Diese Methode gibt die Zeile einer Zelle der Range in Koordinaten auf dem Sheet zurück.
	 *	@param 	mixed 	$cellAB 					Zelle in AB-Notation
	 */
	public function getAbsolutCellRow($cellAB)
	{
		$row = $this->xl->intRow($cellAB);
		$realRow = $this->rowA + $row-1;
		return $realRow;
	}

	/**
	 *	Diese Methode gibt die Spalte einer Zelle der Range in Koordinaten auf dem Sheet zurück.
	 *	@param 	mixed 	$cellAB 					Zelle in AB-Notation
	 */
	public function getAbsolutCellCol($cellAB, $returnInString = true)
	{
		$col = $this->xl->intCol($cellAB);
		$realCol = $this->colA + $col-1;
		if($returnInString == true)
		{
			$strCol = $this->xl->strCol($realCol);
			return $strCol;
		}
		else
		{
			return $realCol;
		}
	}

	/**
	 * Ermittelt den RC-Zellbezug relativ zur absoluten Adresse $ref zu den Bereichskoordinaten
	 * @param		mixed		$ref							Zelle oder array(Zeile,Spalte) für den absoluten Bezugspunkt
	 * @param		mixed		$rowARange				Zeile A oder Bereich(e) in Array oder Komma-separierter Notation
	 * @param		integer	$colA							Spalte A
	 * @param		integer	$rowB							Zeile B
	 * @param		integer	$colB							Spalte B
	 * @return	string										Zellbezugsadresse in RC-Notation R[3]C[-3]
	 */
	public function getRC($ref, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		return $this->xl->getRC($ref, $this->selection[0]);
	}

	/**
	 * Ermittelt die aktuelle Auswahl in AB-Notation Komma-delimitiert
	 * @return	string										Ausgewählte Bereiche als Komma-delimitierte Zeichenkette
	 */
	public function getSelectionString() {
		if(count($this->selection) == 0) $this->selectRange();
		foreach($this->selection as $range) {
			$selection .= $range . ',';
		}
		return substr($selection,0,strlen($selection)-1);
	}

	/**
	 * Gruppiert Spalten
	 * @param		mixed		$colARange				Erste Spalte oder Spalte(n) in Array- oder Komma-separierter Notation
	 * @param		integer	$colB							Zweite Spalte
	 * @param		boolean	$collapse					true, wenn die Gruppierung geschlossen werden soll
	 */
	public function groupCols($colARange, $colB=false, $collapse=true) {
		if(is_numeric($colARange) || (is_string($colARange) && strpos($colARange,',') === false && strpos($colARange,':') === false)) {
			if($colB === false)		$colB = $colARange;
			if($colB == -1)				$colB = $this->maxCol;
			$this->xl->groupCols($this->xlCol($colARange), $this->xlCol($colB), $collapse);
		} elseif(is_array($colARange)) {
			foreach($colARange as $range) {
				$this->groupCols($range, $colB, $collapse);
			}
		} else {
			$ranges = explode(',', $colARange);
			foreach($ranges as $range) {
				if(strpos($range, ':') === false) {
					$this->groupCols($range, $colB, $collapse);
				} else {
					$colArr = explode(':', $range);
					$this->groupCols($colArr[0],$colArr[1], $collapse);
				}
			}
		}
	}

	/**
	 * Versteckt die ausgewählten Spalten
	 * @param		mixed		$colARange				Spalte 1 oder Zelle(n) in AB-Notation als Array oder komma-separierte Zeichenkette
	 * @param		integer	$colB							Spalte 2
	 */
	public function hideCols($colARange=false, $colB=false) {
		if(is_numeric($colARange)) {
			$this->selectRange(1, $colARange, 1, $colB);
		} else {
			$this->selectRange($colARange, false, false, $colB);
		}
		foreach($this->selection as $range) {
			$this->xl->hideCols($range);
		}
	}

	/**
	 * Versteckt die ausgewählten Zeilen
	 * @param		mixed		$rowARange				Zeile 1 oder Zelle(n) in AB-Notation als Array oder komma-separierte Zeichenkette
	 * @param		integer	$rowB							Zeile 2
	 */
	public function hideRows($rowARange=false, $rowB=false) {
		$this->selectRange($rowARange, false, $rowB, false);
		foreach($this->selection as $range) {
			$this->xl->hideRows($range);
		}
	}

	/**
	 * Fügt eine Spalte VOR der definierten Spalte  und verschiebt den gesamten Inhalte nach rechts
	 * @param integer $col								Spalte (aktuelle Spalte)
	 * @param number $cols 								Anzahl Spalten
	 */
	public function insertCol($col=false, $cols=1) {
		if(!$col)	$col= $this->col;
		if($col == -1)		$col = $this->maxCol;
		$this->xl->insertCol($this->colA + $col - 1, $cols);
		$this->maxCol += $cols;
	}

	/**
	 * Fügt eine Zeile VOR der definierten Zeile ein und verschiebt den gesamten Inhalte nach unten
	 * @param integer $row								Zeile (aktuelle Zeile
	 * @param number $rows								Anzahl Zeilen
	 */
	public function insertRow($row=false, $rows=1) {
		if(!$row)	$row = $this->row;
		if($row == -1)		$row = $this->maxRow;
		$this->xl->insertRow($this->rowA + $row - 1, $rows);
		$this->maxRow += $rows;
	}

	/**
	 * Verbindet Zellen
	 * @param		mixed	$rowARange					Zelle(n) in AB-Notation als Array oder Komma-separiert - oder Zeile A
	 * @param		integer	$colA							Spalte A
	 * @param		integer	$rowB							Zeile B
	 * @param		integer	$colB							Spalte B
	 */
	public function mergeCells($rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$this->xl->mergeCells($range);
		}
	}

	/**
	 * Erstellt einen Zeilenvorschub und springt an die 1. Spalte des Bereichs
	 */
	public function newline() {
		$this->row++;
		$this->col=1;
	}

	/**
	 *	Diese Funktion erstellt einen Seitenumbruch.
	 */
	public function setPageBreak($row = false, $col = false)
	{
		if($row !== false)	$row = $this->row+$row;
		if($col !== false)	$col = $this->col+$col;
		$this->xl->setPageBreak($row, $col);
	}

	/**
	 * Positioniert den Cursor an eine Zellposition
	 * @param		string	$rowRange					Zeile/Zelle
	 * @param		integer	$col							Spalte
	 */
	public function setPos($rowRange=false, $col=false) {
		if(is_numeric($rowRange)) {
			$this->row = $rowRange;
			if($this->row == -1)		$this->row = $this->maxRow;
		} else if(is_string($rowRange)) {
			$this->row = $this->intRow($rowRange);
			$this->col = $this->intCol($rowRange);
		}
		if(is_numeric($col))		$this->col = $col;
		if($this->col == -1)		$this->col = $this->maxCol;
	}

	/**
	 * Erstellt den Druckbereich
	 * @param		mixed		$rowARange				Zeile A oder Bereich in AB-Notation
	 * @param		integer	$colA							Spalte A
	 * @param		integer	$rowB							Zeile B
	 * @param		integer	$colB							Spalte B
	 * @param		boolean	$append						true fügt den Bereich dem bestehenden Druckbereich hinzu
	 */
	public function setPrintArea($rowARange, $colA=false, $rowB=false, $colB=false, $append=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		$first = true;
		foreach($this->selection as $range) {
			if($first) {
				$this->xl->setPrintArea($range, false, false, false, $append);
				$first = true;
			} else {
				$this->xl->setPrintArea($range, false, false, false, true);
			}
		}

	}

	/**
	 * Wählt einen Zellbereich aus bzw. erweitert die aktuelle Zellauswahl
	 * @param		mixed		$rowARange				Erste Zeile oder Bereich(e) in AB-Notation als Array oder Komma-delimited
	 * @param		integer	$colA							Erste Spalte
	 * @param		integer	$rowB							Zweite Zeile
	 * @param		integer	$colB							Zweite Spalte
	 * @param		boolean	$append						true, wenn die Auswahl der bestehenden Auswahl hinzugefügt werden soll
	 */
	public function selectRange($rowARange=false, $colA=false, $rowB=false, $colB=false, $append=false) {
		if($rowARange !== false) {					//	Verarbeite gemeldete Auswahl
			if($append == false)	$this->selection = array();
			if(is_numeric($rowARange)) {
				$this->selection[] = $this->xlRange($rowARange, $colA, $rowB, $colB);
			} else if(is_array($rowARange)) {
				foreach($rowARange as $range) {
					$this->selection[] = $this->xlRange($range);
				}
			} else {
				$ranges = explode(',', $rowARange);
				foreach($ranges as $range) {
					$this->selection[] = $this->xlRange($range);
				}
			}
		}
		if(count($this->selection) == 0)	$this->selection[] = $this->xlRange($this->lastRow, $this->lastCol);
	}

	/**
	 * Erstellt einen Rahmen um einen Zellbereich
	 * none, dashDot, dashDotDot, dashed, dotted, double, hair, medium, mediumDashDot, mediumDashDotDot, mediumDashed, slantDashDot, thick, thin
	 * @param		string	$frame						Äussere Rahmenlinien
	 * @param		string	$grid							Innere Gridlinien
	 * @param		mixed		$rowARange				Zeile 1 oder Bereich(e) in AB-Notation als Array oder Komma-delimited
	 * @param		integer	$colB							Spalte 1
	 * @param		integer	$rowB							Zeile 2
	 * @param		integer	$colB							Spalte 2
	 */
	public function setBorder($frame=false, $grid=false, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$this->xl->setBorder($frame, $grid, $range);
		}
	}

	/**
	 * Definiert die Spaltenbreite
	 * @param		mixed		$width						Spaltenbreite oder false für AutoSize
	 * @param		mixed		$colARange				Erste Spalte oder Bereich(e) in Spaltennummer als Array oder Komma-delimitert
	 * @param		integer	$colB							Zweite Spalte
	 */
	public function setColumnWidth($width=false, $colARange=false, $colB=false) {
		if($colARange == false && $colB == false) {
			$colARange = 1;
			$colB = $this->maxCol;
		}
		if($colB == -1)	$colB = $this->maxCol;
		if(is_numeric($colARange)) {
			$colARange = (string)$colARange;
			if($colB !== false) $colARange .= ':' . $colB;
		}
		if(is_array($colARange)) {
			foreach($colARange as $range) {
				$this->setColumnWidth($width, $range);
			}
		} else {
			$ranges = explode(',',$colARange);
			foreach($ranges as $range) {
				$cells = explode(':', $range);
				$col0 = $cells[0];
				$col1 = $cells[1];
				if(!$col1)	$col1 = $col0;
				if(!is_numeric($col0))		$col0 = $this->intCol($col0);
				if(!is_numeric($col1))		$col1 = $this->intCol($col1);
				$this->xl->setColumnWidth($width, $this->colA+$col0 - 1, $this->colA + $col1 - 1);
			}
		}
	}

	/**
	 * Erstellt bedingte Formatierung für einen Zellbereich
	 * ACHTUNG!!! Die bedingte Formatierung basiert auf bestehendem Styling. Demnach muss die bedingte Formatierung
	 * immer NACH Spezifikation der Standardformatierung definiert werden.
	 * @param		string	$condition				Bedingung mit Zellwertvergleich = < > <= >= <> oder Ausdruck ... = ...
	 * @param		string	$style						Styling-Anweisung für die bedingte Formatierung
	 * @param		mixed		$rowARange				Zeile 1 / Zelle(n) in AB-Notation als Komma-separierte Liste oder Array
	 * @param		integer	$colA							Spalte 1
	 * @param		integer	$rowB							Zeile 2
	 * @param		integer	$colB							Spalte 2
	 */
	public function setCondition($condition, $style, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$this->xl->setCondition($condition, $style, $range);
		}
	}

	/**
	 * Definiert einen Namen für einen Zellbereich
	 * @param		string	$name							Bereichsbezeichnung
	 * @param		mixed		$rowARange				Zeile 1 oder Zelle(n) in AB-Notation, als Komma-separierte Liste oder als Array
	 * @param		integer	$colA							Spalte 1
	 * @param		integer	$rowB							Zeile 2
	 * @param		integer	$colB							Spalte 2
	 */
	public function setNamedRange($name, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		$range = $this->selection[0];
		$this->xl->setNamedRange($name, $range);
	}

	/**
	 * Erstellt einen XLRange-Bereich mit einer Spalte, stellt darin einen Listeninhalt auf und kennzeichnet den Bereich mit einem Namen
	 * Dadurch sollten die ValidationLists vereinfacht werden.
	 * Die Spalte, die für diesen Bereich verwendet wird, wird initial versteckt
	 * @param		string		$label					Titel für die Auswahlquelle (für eine schönere Präsentation, wenn die Spalte eingeblendet wird
	 * @param		string		$rangeName			Bezeichnung der Auswahlquellen (schönere Präsentation, wenn die Spalte eingeblendet wird)
	 * @param		mixed			$list						Auswahlliste als komma-separierte Liste, Array von Elementen oder Array von Objekten
	 * @param		mixed			$rowRange				Zelle oder Zeile
	 * @param		integer		$col						Spalte
	 * @param		boolean		$hideCol				true versteckt die Spalte
	 * @param		boolean		$hideRows				true versteckt die Zeilen
	 */
	public function setNamedRangeByList($label, $rangeName, $list, $rowRange='A1', $col=false, $hideCol=true, $hideRows=false) {
		$this->selectRange($rowRange, $col);
		$range = $this->selection[0];
		$this->xl->setNamedRangeByList($label, $rangeName, $list, $range, false, $hideCol, $hideRows);
	}

	/**
	 * Definiert die Wiederholungsspalten
	 * @param		integer	$start						Erste Wiederholungsspalte
	 * @param		integer	$end							Letzte Wiederholungsspalte
	 */
	public function setRepeatColumns($start=1, $end=false) {
		if(!$end)	$end=$start;
		$this->xl->setRepeatColumns($this->colA+$start - 1, $this->colA + $end - 1);
	}

	/**
	 * Definiert die Wiederholungszeilen
	 * @param		integer	$start						Erste Wiederholungszeile
	 * @param		integer	$end							Letzte Wiederholungszeile
	 */
	public function setRepeatRows($start=1, $end=false) {
		if(!$end)	$end=$start;
		$this->xl->setRepeatRows($this->rowA + $start - 1, $this->rowA + $end - 1);
	}

	/**
	 * Definiert die Zeilenhöhe
	 * @param		mixed		$height						Zeilenhöhe oder false für Autosize
	 * @param		mixed		$rowARange				Erste Zeile oder Bereich(e) in Zeilennummer als Array oder Komma-delimitiert
	 * @param		integer	$rowB							Zweite Zeile
	 */
	public function setRowHeight($height=false, $rowARange=false, $rowB=false) {
		if($rowARange == false && $rowB == false) {
			$rowARange = 1;
			$rowB = $this->maxRow;
		}
		if(is_numeric($rowARange)) {
			$rowARange = (string)$rowARange;
			if($rowB !== false) $rowARange .= ':' . $rowB;
		}
		if(is_array($rowARange)) {
			foreach($rowARange as $range) {
				$this->setRowHeight($height, $range);
			}
		} else {
			$ranges = explode(',',$rowARange);
			foreach($ranges as $range) {
				$cells = explode(':', $range);
				$row0 = $cells[0];
				$row1 = $cells[1];
				if(!$row1)	$row1 = $row0;
				$this->xl->setRowHeight($height, $this->rowA+$row0 - 1, $this->rowA + $row1 - 1);
			}
		}
	}

	/**
	 * Setzt den Cursor an eine Position
	 * @param int|string $rowRange
	 * @param integer $col
	 */
	public function setSelection($rowRange, $col=false) {
		$this->selectRange($rowRange, $col);
		foreach($this->selection as $range) {
			$this->xl->setSelection($range);
		}
	}

	/**
	 * Definiert den Style für einen Zellbereich
	 * @param		string	$style						Style-Anweisung oder Konstante
	 * @param		mixed		$rowARange				Erste Zeile oder Bereiche in AB-Notation als array oder Komma-delimited
	 * @param		integer	$colA							Erste Spalte
	 * @param		integer	$rowB							Zweite Zeile
	 * @param		integer	$colB							Zweite Spalte
	 */
	public function setStyle($style, $rowRangeArr, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowRangeArr, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$this->xl->setStyle($style, $range);
		}
	}

	/**
	 * Ermittelt das Format der Zelle
	 * @param integer|string $rowRange		Zeile oder Zelle in AB-Notation
	 * @param integer $col								Spalte
	 * @return string;
	 */
	public function getNumberformat($rowRange=false, $col=false) {
		return $this->xl->activeSheet->getStyle($this->xlRange($rowRange,$col))->getNumberFormat()->getFormatCode();
	}

	/**
	 * Diese Funktion setzt einen Divider (Spalte).
	 */
	public function setColumnDivider($dividerLineStyle, $rowARange=false, $colA=false, $rowB=false, $colB=false)
	{
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$this->xl->setColumnDivider($dividerLineStyle, $range);
		}
	}

	/**
	 * Diese Funktion setzt einen Divider (Spalte).
	 */
	public function setRowDivider($dividerLineStyle, $rowARange=false, $colA=false, $rowB=false, $colB=false)
	{
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$this->xl->setRowDivider($dividerLineStyle, $range);
		}
	}

	/**
	 * Fügt eine Auswahlliste auf den Zellbereich ein
	 * @param		$mixed	$list							Werteliste in Komma-separierter Notation oder als Array
	 * @param		mixed		$rowARange				Zeile A oder Zellbereich
	 * @param		integer	$colA							Spalte A
	 * @param		integer	$rowB							Zeile B
	 * @param		integer	$colB							Spalte B
	 */
	public function setValidationList($list, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$this->xl->setValidationList($list, $range);
		}
	}

	/**
	 * Fügt eine Auswahlliste basierend auf einen Quell-Bereich auf den Zellbereich ein
	 * @param		$mixed	$selection				Quellbereich
	 * @param		mixed		$rowARange				Zeile A oder Zellbereich
	 * @param		integer	$colA							Spalte A
	 * @param		integer	$rowB							Zeile B
	 * @param		integer	$colB							Spalte B
	 */
	public function setValidationRange($selection, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$this->xl->setValidationRange($selection, $range);
		}
	}

	/**
	 * Überspringt eine Spalte
	 */
	public function skip() {
		$this->col++;
	}

	/**
	 * Schreibt Daten in die relative Zellposition des XLRange und bewegt die Position um eine Spalte nach rechts
	 * @param		mixed		$data							Wert(e) als String oder Array
	 * @param		mixed		$rowRange					Zeile oder Zelle(n) in AB-Notation als Array oder Komma-separiert
	 * @param		integer	$col							Spalte
	 * @param		string	$style						Style-Anweisung, die der Zelle hinterlegt wird.
	 */
	public function write($data, $rowRange=false, $col=false, $style=false) {
		$this->setPos($rowRange, $col);
		if(is_array($data)) {
			foreach($data as $item) {
				$this->write($item, false, false, $style);
			}
		} else {
			$this->lastRow = $this->row;
			$this->lastCol = $this->col;
			if($this->lastRow > $this->maxRow) $this->maxRow = $this->lastRow;
			if($this->lastCol > $this->maxCol) $this->maxCol = $this->lastCol;
			$this->xl->write($data, $this->rowA + $this->row - 1, $this->colA + $this->col - 1, $style);
			$this->col++;
		}
	}

	/**
	 * Schreibt Daten als TEXT in die relative Zeilenposition des XLRange und bewegt die Position um eine Spalte nach rechts
	 * @param string|array $data					Wert oder Array von Werten
	 * @param integer|string $rowRange		Zeile oder Zelle in AB-Notation
	 * @param integer $col								Spalte
	 */
	public function writeText($data, $rowRange=false, $col=false) {
		$this->setPos($rowRange, $col);
		if(is_array($data)) {
			foreach($data as $item) {
				$this->writeText($item);
			}
		} else {
			$this->lastRow = $this->row;
			$this->lastCol = $this->col;
			if($this->lastRow > $this->maxRow) $this->maxRow = $this->lastRow;
			if($this->lastCol > $this->maxCol) $this->maxCol = $this->lastCol;
			$this->xl->writeText($data, $this->rowA + $this->row - 1, $this->colA + $this->col - 1);
			$this->col++;
		}
	}

	/**
	 * Schreibt Daten in die relative Zellposition des XLRange und bewegt die Position um eine Zeile nach unten
	 * @param		mixed		$data							Wert(e) als String oder Array
	 * @param		mixed		$rowRange					Zeile oder Zelle(n) in AB-Notation als Array oder Komma-separiert
	 * @param		integer	$col							Spalte
	 * @param		string	$style						Style-Anweisung, die der Zelle hinterlegt wird.
	 */
	public function writeCol($data, $rowRange=false, $col=false, $style=false) {
		$this->setPos($rowRange, $col);
		if(is_array($data)) {
			foreach($data as $item) {
				$this->writeCol($item, false, false, $style);
			}
		} else {
			$this->lastRow = $this->row;
			$this->lastCol = $this->col;
			if($this->lastRow > $this->maxRow) $this->maxRow = $this->lastRow;
			if($this->lastCol > $this->maxCol) $this->maxCol = $this->lastCol;
			$this->xl->writeCol($data, $this->rowA + $this->row - 1, $this->colA + $this->col - 1, $style);
			$this->row++;
		}
	}

	/**
	 * Schreibt eine Datenzeile zur vermittelten Liegenschaft gemäss Spaltenspezifikation
	 * @param		array		$lig						Liegenschaften-Array
	 */
	public function writeLig($lig) {
		$this->xl->writeLig($lig, $this->xlRange($this->row,1));
		$this->lastRow = $this->xl->lastRow - $this->rowA + 1;
		$this->lastCol = $this->xl->lastCol - $this->colA + 1;
		$this->row = $this->xl->row - $this->rowA + 1;
		$this->col = $this->xl->col - $this->colA + 1;
		if($this->lastRow > $this->maxRow)		$this->maxRow = $this->lastRow;
		if($this->lastCol > $this->maxCol)		$this->maxCol = $this->lastCol;
	}

	/**
	 * Ermittelt die Original-Koordinaten in AB-Notation
	 * @param	mixed		$rowARange					Bereich oder Zeile A
	 * @param	integer	$colA								Spalte A
	 * @param	integer	$rowB								Zeile B
	 * @param	integer	$colB								Spalte B
	 * @param	mixed		$absolute						true, wenn der Zellbereich in absoluter Adressierung erfolgen soll
	 *																		'col' mit absoluter Spalten- bwz. 'row' mmit absoluter Zeilen-Adressierung
	 */
	public function xlRange($rowARange, $colA=false, $rowB=false, $colB=false, $absolute=false) {
		if(is_numeric($rowARange)) {
			if($rowARange == -1)	$rowARange = $this->maxRow;
			if($colA == -1)				$colA = $this->maxCol;
			if($rowB == -1)				$rowB = $this->maxRow;
			if($colB == -1)				$colB = $this->maxCol;
			$row0 = $this->rowA + $rowARange - 1;
			$col0 = $this->colA + $colA - 1;
			$row1 = false;
			$col1 = false;
			if($rowB) {
				$row1 = $this->rowA + $rowB -1;
				if(!$colB)	$col1 = $col0;
			}
			if($colB) {
				if(!$rowB) $row1 = $row0;
				$col1 = $this->colA + $colB - 1;
			}
			return $this->xl->xlRange($row0, $col0, $row1, $col1, $absolute);
		} else if(is_array($rowARange)) {
			foreach($rowARange as $range) {
				$rc .= $this->xlRange($range, false, false, false, $absolute) . ',';
			}
			return substr($rc, 0, strlen($rc)-1);
		} else {
			$ranges = explode(',',$rowARange);
			foreach($ranges as $range) {
				$cells = explode(':', $range);
				if($cells[0]) {
					$row0 = $this->rowA + $this->intRow($cells[0]) - 1;
					$col0 = $this->colA + $this->intCol($cells[0]) - 1;
				}
				if($cells[1]) {
					$row1 = $this->rowA + $this->intRow($cells[1]) - 1;
					$col1 = $this->colA + $this->intCol($cells[1]) - 1;
				} else {
					$row1 = false;
					$col1 = false;
				}
				$rc .= $this->xl->xlRange($row0, $col0, $row1, $col1, $absolute) . ',';
			}
			return substr($rc, 0, strlen($rc) - 1);
		}
	}

	/*
	 * Private Interface
	 */

	/**
 	 * Ermittelt die Spaltennummer ab AB#-Notation
 	 * @param		string	$range						Range in AB-Notation
 	 * @return	integer										Spaltennummer
 	 */
	private function intCol($range) {
		$lrange = preg_replace("/[0-9$]/","",$range);
		$strlen = strlen($lrange);
		for($i=0; $i<$strlen; $i++) {
			$rc += pow(26,$strlen - $i - 1) * (ord($lrange{$i}) - 64);
		}
		return $rc;
	}

	/**
	 * Ermittelt die Zeilennumemr ab AB#-Notation
	 * @param		string	$range						Range in AB-Notation
	 * @return	integer										Zeilennummer
	 */
	private function intRow($range) {
		return preg_replace("/[^0-9]/","",$range);
	}

	/**
	 * Vermittelt die aktuelle Tabelle
	 * @return PHPExcel_Worksheet
	 */
	public function activeSheet() {
		return $this->xl->activeSheet;
	}

	public function clearStyle($rowARange, $colA=false, $rowB=false, $colB=false) {
		$this->xl->clearStyle($this->xlRange($rowARange, $colA, $rowB, $colB));
	}

	/**
	 * Erstellt an der Zelle einen Kommentar
	 * @param string			$comment
	 * @param int/string	$rowRange
	 * @param [int	$col]
	 */
	public function comment($comment, $rowRange, $col=false) {
		$this->xl->comment($comment, $this->xlRange($rowRange, $col));
	}

	public function findValue($value, $rowARange = false, $colA = false, $rowB = false, $colB = false) {
		return $this->xl->findValue($value, $this->xlRange($rowARange, $colA, $rowB, $colB));
	}

	/**
	 * Ermitteln in absoluter RC-Notation eine Zell- oder Bereichsadresse
	 * @param $rowARange		int/string
	 * @param [$colA]				int
	 * @param [$rowB]				int
	 * @param [$colB]				int
	 */
	public function getRCAbs($rowARange, $colA=false, $rowB=false, $colB=false) {
		if(is_string($rowARange) && !is_numeric($rowARange)) {
			$ranges = explode(':',$rowARange);
			$row = $this->intRow($ranges[0]);
			$col = $this->intCol($ranges[0]);
			$rc = 'R' . $this->xlRow($ranges[0]) . 'C' . $this->xlCol($ranges[0]);
			if(count($ranges)>1) $rc .= ':R' . $this->xlRow($ranges[1]) . 'C' . $this->xlCol($ranges[1]);
			return $rc;
		}
		$rc = 'R' . $this->xlRow($rowARange) . 'C' . $this->xlCol($colA);
		if($rowB && $colB) $rc .= ':R' . $this->xlRow($rowB) . 'C' . $this->xlCol($colB);
		return $rc;
	}

	public function getRCRange($absRange, $rowRange, $col=false) {
		$ranges = split(':',$absRange);
		$rcA = $this->getRC($ranges[0], $rowRange, $col);
		$rcB = $this->getRC($ranges[1], $rowRange, $col);
		return $rcA . ':' . $rcB;
	}

	/**
	 * Ermittelt einen bestehenden XLRange
	 * @param $rangeName	string		Bezeichnung des XLRanges
	 * @return Export_XLRange
	 */
	public function getXLRange($rangeName, $rowRange='', $col=1) {
		return $this->xl->getXLRange($rangeName, $this->xlRange($rowRange, $col));
	}

	/**
	 * Ermittelt einen Folgerange, der dem aktuellen Range rechts angrenzt
	 * @param		string	$rangeName				Bereichsname
	 * @param		integer	$rowOffset				Zeilenversatz
	 * @param		integer	$colOffset				Spaltenversatz
	 * @param		integer	$maxRow						Prä-Spezifikation der Anzahl Zeilen
	 * @param		integer	$maxCol						Prä-Spezifikation der Anzahl Spalten
	 * @return	Export_XLRange
	 */
	public function getXLRangeRight($rangeName, $rowOffset=0, $colOffset=0, $maxRow=1, $maxCol=1) {
		return $this->xl->getXLRange($rangeName, $this->xlRange(1+$rowOffset, $this->maxCol+1+$colOffset, $rowOffset+$maxRow, $this->maxCol+$colOffset+$maxCol));
	}

	/**
	 * Ermittelt einen Folgerange, der an dem aktuellen Range unten angrenzt
	 * @param		string	$rangeTag					Schlüsselbezeichnung für neuen Bereich
	 * @param		integer	$rowOffset				Zeilenversatz
	 * @param		integer	$colOffset				Spaltenversatz
	 * @param		integer	$maxRow						Prä-Spezifikation der Anzahl Zeilen
	 * @param		integer	$maxCol						Prä-Spezifikation der Anzahl Spalten
	 */
	public function getXLRangeBottom($rangeTag, $rowOffset=0, $colOffset=0, $maxRow=1, $maxCol=1) {
		return $this->xl->getXLRange($rangeTag, $this->xlRange($this->maxRow+1+$rowOffset, 1+$colOffset, $this->maxRow+$rowOffset+$maxRow, $colOffset+$maxCol));
	}

	/**
	 * Ermittelt die Zelle oder Bereichsadresse in der AB-Notation
	 * @param $rangeName	string			Bezeichnung des XLRange-Objekts
	 * @param	$rowA				int/string	Zeile 1 ODER Range in AB-Notation
	 * @param $colA				int					Spalte 1
	 * @param	$rowB				int					Zeile 2
	 * @param $colB				int					Spalte 2
	 */
	public function getXLRangeAdr($rangeName, $rowA, $colA = false, $rowB = false, $colB = false, $absolute = false) {
		return $this->xl->getXLRange($rangeName)->xlRange($rowA, $colA, $rowB, $colB, $absolute);
	}

	/**
	 * Ermittelt die absolute Zelladresse in RC-Notation eines anderen Bereichs
	 * @param $rangeName	string			Bezeichnung des XLRange-Objekts
	 * @param $rowARange	int/string	Zeile 1 ODER Range in AB-Notation
	 * @param $colA				int					Spalte 1
	 * @param $rowB				int					Zeile 2
	 * @param $colB				int					Spalte 2
	 */
	public function getXLRangeRCAbs($rangeName, $rowARange, $colA = false, $rowB = false, $colB = false) {
		return $this->xl->getXLRange($rangeName)->getRCAbs($rowARange, $colA, $rowB, $colB);
	}

	/**
	 * Gruppiert Zeilen
	 * @param $rowA			int						Erste Zeile der Gruppierung
	 * @param $rowB			int						Letzte Zeile der Gruppierung
	 * @param $collapse	boolean				Gruppierung schliessen?
	 */
	public function groupRows($rowA, $rowB, $collapse=true) {
		if($rowB == -1)	$rowB = $this->maxRow;
		$this->xl->groupRows($this->xlRow($rowA) - 1, $this->xlRow($rowB) - 1, $collapse);
	}

	/**
	 * Verschiebt den logischen Adressbereich des Ranges
	 * @param int $row							Zeilenversatz
	 * @param int $col							Spaltenversatz
	 */
	public function moveRange($row=0, $col=0) {
		$this->rowA += $row;
		$this->colA += $col;
	}

	/**
	 * Ermittelt ein Bereichsobjekt zur schnellen Zusammenfassung mehrerer Teilbereiche
	 * Die Bereichsranges werden in der Notation 'A1:B3,C4,D5:E6' bereitgestellt
	 * @param $rowARangeArray	int/string/array
	 * @param $colA						int
	 * @param $rowB						int
	 * @param $colB						int
	 */
	public function ranges($rowA=false, $colA=false, $rowB=false, $colB=false) {
		$range = new Export_Range($this->maxRow, $this->maxCol);
		return $range->add($rowA, $colA, $rowB, $colB);
	}

	/**
	 * Vereinfachte Zuweisung der Format-Konstante)
	 * @param string	$formatConst		Abgekürzte Format-Konstante aus Auswertung_xl::$formatConst
	 */
	public function setFormat($formatConst, $rowRangeArr, $colA=false, $rowB=false, $colB=false) {
		if(is_array($rowRangeArr)) {
			foreach($rowRangeArr as $range) {
				$this->setFormat($formatConst, $range);
			}
		} else if(count(explode(',', $rowRangeArr))>1) {
			foreach(explode(',', $rowRangeArr) as $range) {
				$this->setFormat($formatConst, $range);
			}
		}	else {
			$this->activeSheet()->getStyle($this->xlRange($rowRangeArr, $colA, $rowB, $colB))->getNumberFormat()->setFormatCode(constant('Auswertung_xl::' . $formatConst));
		}
	}

	/**
	 * Erstellt einen Hyperlink
	 * @param string $range		Bereich in AB-Notation, der den Link enthält
	 * @param string $link		Zielbereich in AB-Notation, wohin der Link führt
	 */
	public function setHyperlink($link, $rowARange, $colA=false, $rowB=false, $colB=false) {
		$this->xl->setHyperlink($this->xlRange($rowARange, $colA, $rowB, $colB), $link);
	}

	/**
	 * Trennt verbundene Zellen, wobei entweder ein rowA/colA - rowB/colB-Bereich abgesteckt ist oder ein in
	 * rowA definierter Bereich oder ein in rowA übergebenes Array
	 * @param $rowA		int/string		Zeile 1 ODER Range in AB-Notation ODER Array von Ranges in AB-Notation
	 * @param $colA		int						Spalte 1
	 * @param $rowB		int						Zeile 1
	 * @param $colB		int						Spalte 2
	 */
	public function unmergeCells($rowA, $colA = false, $rowB = false, $colB = false) {
		if(is_array($rowA)) {
			foreach($rowA as $range) {
				$this->unmergeCells($range);
			}
		} else if(count(explode(',',$rowA))>1) {
			$this->unmergeCells(explode(',',$rowA), $colA, $rowB, $colB);
		}	else {
			$this->xl->unmergeCells($this->xlRange($rowA, $colA, $rowB, $colB));
		}
	}

	/**
	*	Ermittelt den Wert in der Zelle mit AB- oder row/col-Notation
	*	@param	int/string	$rowRange			Zelle in der AB-Notation (string) oder Zeile (int)
	*	@param	int					$col					Spalte bei row/col-Notation
	*/
	public function value($rowRange, $calc = false) {
		return $this->xl->value($this->xlRange($rowRange), $calc);
	}

	/**
	 * Ermittelt eine RC-Zeichenkette mit den absoluten Zellbezügen (R3C4:R5C8)
	 * @param $rowARange	int/string		Zeile (3), Zelle (A5) oder Bereich (B3:C9)
	 * @param $colA				int
	 * @param $rowB				int
	 * @param $colB				int
	 */
	public function xlRC($rowARange, $colA=false, $rowB=false, $colB=false) {
		if(is_string($rowARange)) {
			if(count(explode(':',$rowARange)>1)) {
				$ranges = explode(':', $rowARange);
				return 'R' . $this->xlRow($ranges[0])
							 . 'C' . $this->xlCol($ranges[0])
							 .	':R' . $this->xlRow($ranges[1])
							 . 'C'	.	$this->xlCol($ranges[1]);
			} else {
				return 'R' . $this->xlRow($rowARange) . 'C' . $this->xlCol($rowARange);
			}
		} else {
			$rc = 'R' . $this->xlRow($rowARange);
			if($colA) $rc .= 'C' . $this->xlCol($colA);
			if($rowB) $rc .= ':R' . $this->xlRow($rowB) . 'C' . $this->xlCol($colB);
			return $rc;
		}
	}

	/**
	 * Ermittelt die Originalspalte
	 * @param $colRange	int/string	Spalte oder Range in AB-Notation
	 */
	public function xlCol($colRange=false) {
		if($colRange === false) return ($this->colA + $this->col - 1);
		if(is_numeric($colRange)) {
			if($colRange == -1)		$colRange = $this->maxCol;
			return ($this->colA + $colRange - 1);
		}
		return ($this->colA + $this->intCol($colRange) - 1);
	}

	/**
	 * Ermittelt die Originalzeile
	 * @param $rowRange	int/string		Zeile oder Range in AB-Notation
	 */
	public function xlRow($rowRange=false) {
		if($rowRange === false) 	return ($this->rowA + $this->row - 1);
		if($rowRange == -1)				return ($this->rowA + $this->maxRow - 1);
		if(is_numeric($rowRange)) return ($this->rowA + $rowRange - 1);
		return ($this->rowA + $this->intRow($rowRange) - 1);
	}

}