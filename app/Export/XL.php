<?php
/**
 * Excel - Ausgabeklasse
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-26	eb	from revis
 */
class Export_XL {

	/**
	 * @var PHPExcel											Verweis auf das PHPExcel-Objekt
	 */
	public $excel;

	public $filename;											//	Basis-Dateiname (ohne Pfad, ohne Extension) - [export]
	public $filetype;											//	csv, xls, xlsx, pdf, html [xlsx]
	public $browser;											//	true erstellt eine Ausgabe an den Browser	(eigenständiger Paramter durch Template-Verwendung)

	/**
	 * @var PHPExcel_Worksheet $activeSheet
	 */
	public $activeSheet;									//	Pointer auf die aktuelle Tabelle

	public $row;													//	Aktuelle Zeile
	public $col;													//	Aktuelle Spalte
	public $lastRow;											//	Zuletzt genutzte Zeile
	public $lastCol;											//	Zuletzt genutzte Spalte
	public $maxRow;												//	Maximal genutzte Zeile
	public $maxCol;												//	Maximal genutzte Spalte
	public $selection;										//	Array mit Zellen der aktuellen Auswahl
	public $params;												//	object mit den Steuerparametern

	public $rgb;													//	Stellt RGB-Werte zur Verfügung - dies in Abhängigkeit der Excel-Version

	private $_path;												//	Dateispeicherpfad [$_SESSION['userdir'] . 'export/' . $_ident/]
	private $_xlrange;										//	Array mit den Excel-Teilbereichen
	private $_chartCount;									//	Anzahl Charts;
	private $_imageCount;									//	Anzahl Images im Sheet;

	////////////////////////////////////////
	//	Initialisierung / Sheet-Methoden	//
	////////////////////////////////////////

	/**
	 * Konstruktor initialisiert die Standard-Parameter und bereitet Status vo
	 * @param array $params								Array mit den Excel-Ausgabe-Parametern
	 */
	public function __construct($params = false) {
		$this->_initRGB();
		$this->_chartCount=0;
		$this->params = $params;
		if(is_array($this->params)) $this->params = Util::arrayToObject($this->params);
		if($this->params->noExcel						==true)				return;																								//	Stellt die Expor_Xl Klasse bereit ohne ein Excel-File anzulegen
		if(gettype($this->params)						!=	'object')	$this->params											=	new StdClass;
		if($this->params->path							===	null)			$this->params->path								=	User::getDir('user').'export/'.md5(microtime(true));
		if($this->params->filetype					===	null)			$this->params->filetype						=	'xlsx';	//	csv, xls, xlsx, pdf, html
		if($this->params->report						===	null)			$this->params->report		 					= 'export';					//	Berichtstitel
		if($this->params->browser						===	null)			$this->params->browser						=	false;						//	Ausgabe an den Browser?
		if($this->params->font							===	null)			$this->params->font								=	'Calibri';				//	Standard-Schriftart
		if($this->params->orientation				===	null)			$this->params->orientation				=	'landscape';			//	Ausrichtung (portrait - landscape)
		if($this->params->papersize					===	null)			$this->params->papersize					=	'A4';							//	Papierformat (A3, A4)
		if($this->params->pageswide					===	null)			$this->params->pageswide					=	null;							//	Anzahl Seiten in der Breite
		if($this->params->pagestall					===	null)			$this->params->pagestall					=	null;							//	Anzahl Seiten in der Höhe
		if($this->params->fontsize					===	null)			$this->params->fontsize						=	9;								//	Standard-Schriftgrösse
		if($this->params->marginleft				===	null)			$this->params->marginleft					=	1;								//	Linker Rand
		if($this->params->marginright				===	null)			$this->params->marginright				=	1;								//	Rechter Rand
		if($this->params->margintop					===	null)			$this->params->margintop					= 3;								//	Oberer Rand
		if($this->params->marginbottom			===	null)			$this->params->marginbottom				=	1.5;							//	Unterer Rand
		if($this->params->marginheader			===	null)			$this->params->marginheader				=	1;								//	Abstand zur Kopfzeile
		if($this->params->marginfooter			===	null)			$this->params->marginfooter				=	0.5;							//	Abstand zur Fusszeile
		if($this->params->fontsizeHeader		===	null)			$this->params->fontsizeHeader			=	14;								//	Schriftgrösse für Titel
		if($this->params->fontsizeSubheader	===	null)			$this->params->fontsizeSubheader	=	10;								//	Schritfgrösse für Untertitel
		if($this->params->fontsizeFooter		===	null)			$this->params->fontsizeFooter			=	8;								//	Schriftgrösse für Fusszeile
		if($this->params->encoding					===	null)			$this->params->encoding						= 'utf-8';					//	Encoding-Typ für CSVs [utf-8;latin1]
		if($this->params->csvdelimiter			===	null)			$this->params->csvdelimiter				=	';';							//	CSV-Trennzeichen
		if($this->params->csvenclosure			===	null)			$this->params->csvenclosure				=	'';								//	CSV-Encosling
		if($this->params->quick							===	null)			$this->params->quick							=	false;						//	Debugger-Modus - Quick-Production (no conditional format, no hyperlinks)
		$this->_path = $this->params->path;
		$this->_xlrange = array();
		$this->selection = array();
		$this->browser = $this->params->browser;
		$this->header = array();
		if(!file_exists($this->_path))	mkdir($this->_path, 0770, true);
		$this->filename = $this->params->report;
		$this->filetype = $this->params->filetype;
		$this->excel = new PHPExcel();
		$this->removeSheet(0);
	}

	/**
	 * Fügt eine extern vorhandene Tabelle ein
	 * @param PHPExcel_Worksheet $externalWorksheet	Externe Tabelle
	 * @param integer $index				Indexposition
	 */
	public function addExternalSheet(PHPExcel_Worksheet $externalWorksheet, $index = 0) {
		if($externalWorksheet == false) 	return;
		$this->excel->addExternalSheet($externalWorksheet, $index);
	}

	/**
	 * Fügt eine neue Tabelle hinzu
	 * @param string $sheetName
	 */
	public function addSheet($sheetName = false) {
		if($sheetName === false)			$sheetName = $this->params->report.' '.$this->params->options;
		$this->excel->createSheet();
		$this->excel->setActiveSheetIndex($this->sheetCount() - 1);
		$this->activeSheet = $this->excel->getActiveSheet();
		$this->setName($sheetName);
		$this->setPageSetup(array(
			'papersize'			=>	$this->params->papersize
			,'orientation'	=>	$this->params->orientation
			,'pageswide'		=>	$this->params->pageswide
			,'pagestall'		=>	$this->params->pagestall
			,'marginheader'	=>	$this->params->marginheader
			,'marginfooter'	=>	$this->params->marginfooter
			,'margintop'		=>	$this->params->margintop
			,'marginleft'		=>	$this->params->marginleft
			,'marginbottom'	=>	$this->params->maringbottom
			,'marginright'	=>	$this->params->marginright
		));
		$style = $this->activeSheet->getDefaultStyle();
		$style->getFont()->setName($this->params->font);
		$style->getFont()->setSize($this->params->fontsize);
		$style->getAlignment()->setHorizontal('left');
		$style->getAlignment()->setVertical('top');
		$this->row = 1;
		$this->col = 1;
		$this->lastRow = 1;
		$this->lastCol = 1;
		$this->maxRow = $this->activeSheet->getHighestRow();
		$this->maxCol = $this->intCol($this->activeSheet->getHighestColumn());
		$this->_addImages();
	}

	/**
	 * Löscht alle Tabellen aus einer Arbeitsmappe
	 */
	public function cleanWorkspace() {
		while($this->sheetCount()>0) {
			$this->removeSheet(0);
		}
	}

	/**
	 * Erstellt eine Kopie einer Tabelle und fügt sie hinten an
	 * @param string $sheetname						Neuer Tabellenname
	 */
	public function copySheet($sheetname='') {
		$this->activeSheet = $this->activeSheet->copy();
		$sheetcnt = $this->excel->getSheetCount();
		while($sheetname == '' || in_array($sheetname, $this->excel->getSheetNames())) {
			$sheetcnt++;
			$sheetname = 'sheet '.$sheetcnt;
		}
		$this->setName($sheetname);
		$this->activeSheet = $this->excel->addSheet($this->activeSheet);
	}

	/**
	 * Erstellt eine neue Tabelle mit dem Inhalt einer SQL-Abfrage
	 * @param string $table								Tabelle
	 * @param Zend_Db_Select $select			Select-Objekt
	 * @param array $config								Config-Aray für allfällige Spalten-Übersetzungen und Formatanwiesungen ('field'=>array('label','style') oder 'field'=>'label')
	 */
	public function createQueryTable($table, $select, $config=array()) {
		$this->addSheet($table);
		//
		//	Feldspezifikation extrahieren
		$fieldspec = array();
		$query = $select->query();
		for($i=0; $i<$query->columnCount(); $i++) {
			$meta = $query->getColumnMeta($i);
			$fieldname = $meta['name'];
			$label = $fieldname;
			$style = '';
			switch($meta['native_type']) {
				case 'LONG':				$style = 'number';
														break;
				case 'NEWDECIMAL':	$style = 'number';
														if($meta['precision']>0)		$style .= '_'.str_repeat('0',$meta['precision']);
														break;
				case 'DATE':				$style = 'date';
														break;
				case 'VAR_STRING':	$style = '';
														break;
				case 'BLOB':				$style = 'wrap';
														break;
				default:	$style = '';
			}
			if(array_key_exists($fieldname, $config)) {
				if(is_array($config[$fieldname])) {
					$label = $config[$fieldname]['label'];
					$style = $config[$fieldname]['style'];
				} else {
					$label = $config[$fieldname];
				}
			}
			$fieldspec[$fieldname] = array(
				'label'		=>	$this->_($label)
				,'style'	=>	$style
			);
		}
		foreach($fieldspec as $field) {
			$this->write($field['label']);
		}
		$this->setStyle('header',1,1,1,-1);
		$this->newline();
		$rs = $select->query()->fetchAll();
		foreach($rs as $record) {
			foreach($record as $value) {
				$this->write($value);
			}
			$this->newline();
		}
		$col=0;
		foreach($fieldspec as $field) {
			$col++;
			$this->setStyle($field['style'], 2, $col, -1, $col);
		}
		$this->freezePane('A2');
		$this->setColumnWidth(false, 1, -1);
	}

	/**
	 * Erstellt eine Zellen-/Zeilen-/Spaltenfixierung für das Scrolling
	 * @param integer|string $rowARange		Zeile A oder Bereich in AB-Notation
	 * @param integer $colA								Spalte A
	 * @param integer $rowB								Zeile B
	 * @param integer $colB								Spalte B
	 */
	public function freezePane($rowARange=false, $colA=false,$rowB=false,$colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		$this->activeSheet->freezePane($this->selection[0]);
	}

	/**
	 * Ermittelt die Dokumenteigenschaft 'Kategorie'
	 * @return string
	 */
	public function getCategory() {
		return $this->excel->getProperties()->getCategory();
	}

	/**
	 * Ermittelt den Dateinamen mit Typ-bestimmter Dateiendung
	 * @return string
	 */
	public function getFilename() {
		switch($this->filetype) {
			case 'csv':		$ext = '.csv';	break;
			case 'pdf':		$ext = '.pdf';	break;
			case 'html':	$ext = '.html';	break;
			default:			$ext = '.xlsx';	break;
		}
		return $this->filename . $ext;
	}

	/**
	 * Ermittelt den vollen Dateinamen mit Pfad und Extension
	 * @return string
	 */
	public function getFullname() {
		return $this->_path . '/' . $this->getFilename();
	}

	/**
	 * Gbit den Haupttitel zurück
	 * @return string
	 */
	public function getMainTitle() {
		$title = $this->activeSheet->getHeaderFooter()->getOddHeader();
		$titleParts = explode("\n", $title);
		$mainTitle = $titleParts[0];
		return $mainTitle;
	}

	/**
	 * Ermittelt den Tabellennamen
	 * @return string
	 */
	public function getName() {
		return $this->activeSheet->getTitle();
	}

	/**
	 * Kopiert (deep copy clone) ein Sheet aus einer Datei
	 * @param string $sheetName
	 * @param string $filename
	 */
	public function getSheetFromFile($sheetName = false, $filename = false) {
		if(file_exists($filename)) {
			$type = PHPExcel_IOFactory::identify($filename);
			$reader = PHPExcel_IOFactory::createReader($type);
			$object = $reader->load($filename);
			$sheetToCopy = $object->getSheetByName($sheetName);
			if($sheetToCopy != null) {
				return clone $sheetToCopy;
			}
		}
	}

	/**
	 * Ermittelt den Untertitel
	 * @return string
	 */
	public function getSubTitle() {
		$title = $this->activeSheet->getHeaderFooter()->getOddHeader();
		$titleParts = explode("\n", $title);
		$subTitle = $titleParts[1];
		return $subTitle;
	}

	/**
	 * Ermittelt, ob eine Tabelle in der Arbeitsmappe vorhanden ist
	 * @param		mixed		$sheetIndexName		Indexnummer oder Name der Tabelle
	 * @return	boolean										true, wenn die Tabelle existiert
	 */
	public function hasSheet($sheetIndexName) {
		if(is_numeric($sheetIndexName)) {
			try {
				$sheet = $this->excel->getSheet($sheetIndexName);
			} catch(Exception $e) {}
		} else {
			try {
				$sheet = $this->excel->getSheetByName($sheetIndexName);
			} catch(Exception $e) {}
		}
		if($sheet)	return true;
		return false;
	}

	/**
	 * Versteckt die aktuelle Tabelle
	 */
	public function hideSheet() {
		$this->activeSheet->setSheetState(PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
	}

	/**
	 * Prüft, ob die Auswertung noch am Laufen ist
	 * @return	boolean										true, wenn die Auswertung noch am Laufen ist
	 */
	public function isRunning() {
		return file_exists($this->_path);
	}

	/**
	 * Löscht die erstellte Datei
	 */
	public function removeFile() {
		Util::cleanDir($this->_path, true);
	}

	/**
	 * Entfernt eine Tabelle aus der Arbeitsmappe
	 * @param		integer	$sheet						Tabellen-Index
	 */
	public function removeSheet($sheet=0) {
		$this->excel->removeSheetByIndex($sheet);
	}

	/**
	 * Speichet die Excel-Datei ab
	 * @param string $filename						Dateiname
	 */
	public function save($filename=false) {
		if(!$this->isRunning()) return;
		if($filename)	$this->filename = $filename;
		$subst = array(
				'/'		=>	','
				,'\\'	=>	' '
				,':'	=>	''
		);
		foreach($subst as $needle => $replace) {
			$this->filename = str_replace($needle, $replace, $this->filename);
		}
		//	Erste Tabelle sicherstellen und fokussieren
		if($this->sheetCount() == 0) $this->addSheet();
		$this->setActivesheet(0);
		$this->activeSheet->setSelectedCells('A1');
		//	In Abhängigkeit des Dokumenttyps den Writer erstellen
		switch($this->filetype) {
			case 'csv':		$type = 'CSV';				break;
			case 'pdf':		$type = 'PDF';				break;
			case 'html':	$type = 'HTML';				break;
			default:			$type = 'Excel2007';	break;
		}
		$writer = PHPExcel_IOFactory::createWriter($this->excel, $type);
		if($this->_chartCount && $type == 'Excel2007')	$writer->setIncludeCharts(true);
		switch($this->filetype) {
			case 'csv':
				{
					$writer->setEnclosure($this->params->csvenclosure);
					$writer->setDelimiter($this->params->csvdelimiter);
					break;
				}
			case 'xls':
			case 'xlsx':			$writer->setPreCalculateFormulas(false);			break;
		}
		if($type == 'Excel2007' && $_SESSION['userversion'] == '2003')	$writer->setOffice2003Compatibility(true);
		//	Ausgabe an den Browser, wenn BROWSER als Liste oder Multi als Kumuliert oder Multisingle
		if($this->browser && (!$this->params->multi || $this->params->multi && $this->params->cumulate || $this->params->multi && $this->params->multisingle)) {
			if (isset($this->params->appDownloadToken)) {
				setcookie($this->params->appDownloadToken, 1, time() + 15, "/");
			}
			//	Ausgabe an den Client-Browser steuern
			switch($this->filetype) {
				case 'csv':		header('Content-type: text/csv');																														break;
				case 'xls':
				case 'xlsx':
					header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');	break;
				case 'pdf':		header('Content-type: application/pdf');																										break;
			}
			header('Content-Disposition: attachment; filename="' . $this->getFilename() . '"');
			header('Cache-Control: max-age=0');
			ob_end_clean();
			$writer->save('php://output');
			$this->removeFile();
		} else {
			$filename = $this->getFullname();
			if(APP_SYSWIN)		$filename = utf8_decode($filename);
			$writer->save($filename);
		}
	}

	/**
	 * Fokusiert auf die Tabelle über Indexnummer oder Name.
	 * Bei Misserfolg wird die erste Tabelle adressiert
	 * @param		mixed	$sheetIdxOrName			Tabellenindex oder Name
	 */
	public function setActiveSheet($sheetIdxOrName=0) {
		if(is_numeric($sheetIdxOrName)) {
			$this->excel->setActiveSheetIndex($sheetIdxOrName);
		} else {
			$this->excel->setActiveSheetIndexByName($sheetIdxOrName);
		}
		$this->activeSheet = $this->excel->getActiveSheet();
	}

	/**
	 * Setzt den Haupttitel
	 * @param string $mainTitle						Haupttitel
	 * @param boolean $clearSubtitle			true löscht den Untertitel
	 */
	public function setMainTitle($mainTitle, $clearSubtitle = false) {
		$title = $this->activeSheet->getHeaderFooter()->getOddHeader();
		$titleParts = explode("\n", $title);
		if($clearSubtitle) $titleParts[1] = '';
		$titleParts[0] = $mainTitle;
		$title = $titleParts[0]."\n".$titleParts[1];
		$this->activeSheet->getHeaderFooter()->setOddHeader($title);
	}

	/**
	 * Ändert den Tabellenname
	 * @param		string	$tableName				Tabellenname
	 */
	public function setName($tableName) {
		$this->activeSheet->setTitle('@');			//	Pseudo-Titel um Eindeutigkeit zu erzwingen
		//	Ermittle Index der aktuellen Tabelle
		foreach($this->excel->getSheetNames() as $index => $name) {
			if($this->activeSheet->getTitle() == $name)		$currentIndex = $index;
		}
		$forbiddenChars = ':\/?*[]';
		$maxtablename = 31 - 2;	//	2 Reserve für Auto-Counter
		for($i=0;$i<strlen($forbiddenChars);$i++) {
			$tableName = str_replace(substr($forbiddenChars,$i,1), ' ', $tableName);
		}
		$tableName = str_replace('?','',trim($tableName));

		$rawName = $tableName;
		$cnt=0;
		$suffix = '';
		do {
			$tableName = $rawName;
			if(strlen($tableName)>$maxtablename) {
				$tableName = substr($tableName, 0, $maxtablename-1);
				if($cnt == 0) {
					$suffix = '...';
				} else if($cnt<10) {
					$suffix = '..';
				} else if($cnt<100) {
					$suffix = '.';
				} else {
					$suffix = '';
				}
			}
			$tableName .= $suffix;
			if($cnt>0)		$tableName .= $cnt;
			//	Existiert bereits eine gleichlautende Tabelle unter anderem Index?
			$found = false;
			foreach($this->excel->getSheetNames() as $index => $name) {
				if($name == $tableName && $index != $currentIndex) {
					$found = true;
					break;
				}
			}
			if(!$found)	break;
			$cnt++;
		}	while(true);
		$this->activeSheet->setTitle($tableName);
	}

	/**
	 * Definiert Seiteneinstellungen zur aktuellen Seite
	 * @param		array		$config						Parameter zur Seiteneinstellung
	 */
	public function setPagesetup($config=array()) {
		$pageSetup = $this->activeSheet->getPageSetup();
		switch($config['papersize']) {
			case 'A4':	$pageSetup->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);	break;
			case 'A3':	$pageSetup->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A3); break;
		}
		switch($config['orientation']) {
			case 'landscape':	$pageSetup->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);	break;
			case 'portrait':	$pageSetup->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);		break;
		}
		if(is_numeric($config['pageswide']) || is_numeric($config['pagestall'])) {
			$pageSetup->setFitToWidth($config['pageswide']);
			$pageSetup->setFitToHeight($config['pagestall']);
		}
		$margins = $this->activeSheet->getPageMargins();
		if($config['marginleft'])		$margins->setLeft($config['marginleft']/2.54);
		if($config['marginright'])	$margins->setRight($config['marginright']/2.54);
		if($config['margintop'])		$margins->setTop($config['margintop']/2.54);
		if($config['marginbottom'])	$margins->setBottom($config['marginbottom']/2.54);
		if($config['maringheader'])	$margins->setHeader($config['marginheader']/2.54);
		if($config['marginfooter'])	$margins->setFooter($config['marginfooter']/2.54);
		if($config['zoom'])					$this->setZoom($config['zoom']);
		if($config['printarea'])		$this->setPrintArea($config['printarea']);
	}

	/**
	 * Setzt das Basisverzeichnis für die Dateisicherung. Das Verzeichnis wird erstellt, sofern es noch nicht existiert.
	 * @param		string	$path							Verzeichnisname
	 */
	public function setPath($path) {
		if(!file_exists($path))		mkdir($path, 0770, true);
		$this->_path = $path;
	}

	/**
	 * Setzt den Untertitel
	 * @param string $subtitle						Untertitel
	 * @param boolean $clearTitle					true löscht den Haupttitel
	 */
	public function setSubTitle($subtitle, $clearTitle = false)	{
		$title = $this->activeSheet->getHeaderFooter()->getOddHeader();
		$titleParts = explode("\n", $title);
		if($clearTitle) $titleParts[0] = '';
		$titleParts[1] = $subtitle;
		$title = $titleParts[0]."\n".$titleParts[1];
		$this->activeSheet->getHeaderFooter()->setOddHeader($title);
	}

	/**
	 * Ermittelt die Anzahl Tabellen in der Arbeitsmappe
	 * @return	integer										Anzahl Tabellen der Arbeitsmappe
	 */
	public function sheetCount() {
		return $this->excel->getSheetCount();
	}

	////////////////////////
	//	XLRange-Methoden	//
	////////////////////////

	/**
	 * Löscht alle XLRange-Objekte
	 */
	public function clearXLRanges() {
		unset($this->_xlrange);
		$this->_xlrange = array();
	}

	/**
	 * Ermittelt ein XLRange-Objekt und adressiert diesen über den Namen
	 * @param string $rangeTag						Schlüsselbegriff für den Bereich
	 * @param integer|string $rowARange		Zeile A oder Zelle/Bereich in AB-Notation
	 * @param integer $colA								Spalte A
	 * @param integer $rowB								Zeile B
	 * @param iteger $colB								Spalte B
	 * @return Export_XLRange
	 */
	public function getXLRange($rangeTag='sheet', $rowARange='A1', $colA=false, $rowB=false, $colB=false) {
		if(!$this->_xlrange[$rangeTag]) {
			if(!$rowARange) {
				Log::err("getXLRange($rangeTag) - unknown range");
				return $this->getXLRange($rangeTag, 'A1');
			}
			$this->selectRange($rowARange, $colA, $rowB, $colB);
			$range = $this->selection[0];
			$this->_xlrange[$rangeTag] = new Export_XLRange($this, $rangeTag, $range);
		}
		return $this->_xlrange[$rangeTag];
	}

	/**
	 * Erstellt einen neuen Bereich unten angrenzend an einen bestehenden Bereich
	 * @param string $rangeTag						Schlüsselbegriff für den Bereich
	 * @param string $referenceTag				Schlüsselbegriff für den Referenzbereich
	 * @param integer $rowOffset					Zeilenversatz
	 * @param integer $colOffset					Spaltenversatz
	 * @param integer $maxRow							Prä-Spezifikation der Anzahl Zeilen
	 * @param integer $maxCol							Prä-Spezifikation der Anzahl Spalten
	 * @return Export_XLRange
	 */
	public function getXLRangeBottom($rangeTag, $referenceTag, $rowOffset=0, $colOffset=0, $maxRow=1, $maxCol=1) {
		return $this->getXLRange($referenceTag)->getXLRangeBottom($rangeTag, $rowOffset, $colOffset, $maxRow, $maxCol);
	}

	/**
	 * Erstellt einen neuen Bereich rechts angrenzend an einen bestehenden Bereich
	 * @param string $rangeTag						Schlüsselbegriff für den Bereich
	 * @param string $referenceTag				Schlüsselbegriff für den Referenzbereich
	 * @param integer $rowOffset					Zeilenversatz
	 * @param integer $colOffset					Spaltenversatz
	 * @param integer $maxRow							Prä-Spezifikation der Anzahl Zeilen
	 * @param integer $maxCol							Prä-Spezifikation der Anzahl Spalten
	 * @return Export_XLRange
	 */
	public function getXLRangeRight($rangeTag, $referenceTag, $rowOffset=0, $colOffset=0, $maxRow=1, $maxCol=1) {
		return $this->getXLRange($referenceTag)->getXLRangeRight($rangeTag, $rowOffset, $colOffset, $maxRow, $maxCol);
	}

	//////////////////////////////
	//	Zellen lesen/schreiben	//
	//////////////////////////////

	/**
	 * Erweitert die Kategorie-Information um einen zusätzlichen Tag (Kennzeichen für Import-Prozesse)
	 * @param unknown $category						Info-Tag für die Dokument-Kategorie (Bsp. Finance=Cuts_IBS_Planning_DCF_Finance)
	 */
	public function addCategory($category) {
		$value = $this->getCategory();
		if($value)	$value .= ' ';
		$value .= $category;
		$this->setCategory($value);
	}


	/**
	 * Erstellt einen Zellkommentar
	 * @param string $comment							Freitext-Kommentar
	 * @param integer|string $rowRange		Zeile oder Zelle in AB-Notation
	 * @param integer $col								Spalte
	 * @param string $style								Style-Information
	 */
	public function addComment($comment, $rowRange=false, $col=false, $style='') {
		$params = array(
			'font-size'		=>	9
			,'font-bold'	=>	false
			,'width'			=>	180
			,'height'			=>	70
		);
		$styleArr = explode(';',$style);
		foreach($styleArr as $styleElement) {
			$styleElementArr = explode(':',$styleElement);
			$params[$styleElementArr[0]] = $styleElementArr[1];
		}
		$this->selectRange($rowRange, $col);
		foreach($this->selection as $range) {
			$objComment = $this->activeSheet->getComment($range);
			$objText = $objComment->getText()->createTextRun($comment);
			if($params['font-bold'])		$objText->getFont()->setBold();
			$objText->getFont()->setSize($params['font-size']);
			$objComment->setWidth($params['width']);
			$objComment->setHeight($params['height']);
		}
	}


	/**
	 * Erstellt einen Hyperlink von einer Quell-Zelle in eine Ziel-Zelle oder URL
	 * @param string $target							Zeil als sheetname!AB oder URL
	 * @param integer|string $rowRange		Zeile oder Zelle in AB-Notation
	 * @param integer $col								Spalte
	 */
	public function addHyperlink($target, $rowRange=false, $col=false) {
		if($this->params->quick)	return;
		$this->selectRange($rowRange, $col);
		foreach($this->selection as $range) {
			if(strpos($target, '://') === false)			$target = 'sheet://'.$target;
			$this->activeSheet->getCell($range)->getHyperlink()->setUrl('sheet://'.$target);
		}
	}

	/**
	 * Fügt ein Bild hinzu
	 * @param string $startCell						Zelle in AB-Notation
	 * @param string $imageFile						Dateiverweis auf die Bilddatei
	 * @param string $description					Bildbeschreibung
	 * @param integer $width							Bildbreite
	 * @param integer $height							Bildhöhe
	 */
	public function addImage($startCell, $imageFile, $description='', $width=null, $height=null) {
		$this->_imageCount++;
		$objDrawing = new PHPExcel_Worksheet_Drawing();
		$objDrawing->setName('Image'.$this->_imageCount);
		$objDrawing->setDescription($description);
		$objDrawing->setPath($imageFile, true);
		$objDrawing->setCoordinates($startCell);
		$objDrawing->getShadow()->setVisible(true);
		$objDrawing->getShadow()->setDirection(45);
		if($width && $height)		$objDrawing->setWidthAndHeight($width, $height);
		if($width && !$height)	$objDrawing->setWidth($width);
		if($height && !$width)	$objDrawing->setHeight($height);
		$objDrawing->setWorksheet($this->excel->getActiveSheet());
	}

	/**
	 * Verbindet Zellen
	 * @param		mixed		$rowARange				Zelle(n) in array oder Komma-separierter Notation oder erste Zeile
	 * @param		integer	$colA							Erste Spalte
	 * @param		integer	$rowB							Zweite Zeile
	 * @param		integer	$colB							Zweite Spalte
	 */
	public function mergeCells($rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$this->activeSheet->mergeCells($range);
		}
	}

	/**
	 * Erstellt einen Zeilenvorschub und springt an die erste Spalte
	 */
	public function newline() {
		$this->row++;
		$this->col=1;
	}

	/**
	 * Schreibt die Dokumentinformation 'Kategorie' für IO-Tags
	 * @param		string	$category					Zeichenkette für die Dokumentinformation
	 */
	public function setCategory($category) {
		$this->excel->getProperties()->setCategory($category);
	}

	/**
	 *	Setzt einen Manuellen Zeilenumbruch.
	 */
	public function setPageBreak($row = false, $col = false) {
		if($row) $this->activeSheet->setBreak($this->activeSheet->getCellByColumnAndRow(1, $row)->getCoordinate(), PHPExcel_Worksheet::BREAK_ROW);
		if($col) $this->activeSheet->setBreak($this->activeSheet->getCellByColumnAndRow($col, 1)->getCoordinate(), PHPExcel_Worksheet::BREAK_COLUMN);
	}

	/**
	 * Definiert den Kopf- und Fussbereich
	 * @param		string	$title						Titelzeile (Report - Option)
	 * @param		string	$subtitle					Untertitel (Verdichtung / Wirtschatseinheit)
	 */
	public function setTitle($title='', $subTitle='', $color='') {
		if(strlen($title) > 200)				$title = substr($title, 0, 200).'...';
		if(strlen($subTitle) >200)			$subTitle = substr($subTitle, 0, 200).'...';
		switch($this->params->orientation) {
			case 'landscape':	$maxSubtitle = 210;	break;
			default:					$maxSubtitle = 130 - $this->params->fontsizeSubheader * 5;
		}
		$subWords = explode(' ',$subTitle);
		$st = '';
		$line = '';
		foreach($subWords as $word) {
			if(strlen($line) + strlen($word) > $maxSubtitle) {
				$st .= substr($line, 0, strlen($line)-1) . "\n";
				$line = '';
			}
			$line .= $word . ' ';
		}
		$st .= $line == '' ? '' : substr($line,0,strlen($line) - 1);
		$st = str_replace('&','&&',$st);
		$st = str_replace('&&P',' && P', $st);
		$title = str_replace('&','&&',$title);
		$title = str_replace('&&P',' && P', $title);
		$color = str_replace('#','', $color);
		if($color)		$color = '&K'.$color;
		$this->activeSheet->getHeaderFooter()->setOddHeader("&L&G&C&".$this->params->fontsizeHeader."&B$color".$title."\n&".$this->params->fontsizeSubheader."&B".$st." &R&G");
		$this->activeSheet->getHeaderFooter()->setOddFooter('&L&'.$this->params->fontsizeFooter.$_SESSION['userfullname']."\n".date('d.m.Y H:i')."&C&P/&N&R&8&F");
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
		$elements = $list;
		if(is_array($list)) {
			foreach($list as $item) {
				$elements .= $item.',';
			}
			if(strlen($elements)>0)		$elements=substr($elements,0,strlen($elements)-1);
		}
		if(substr($elements,0,1) != '=')		$elements = '"'.$elements.'"';
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$cells = explode(':',$range);
			$row0 = $this->intRow($cells[0]);
			$col0 = $this->intCol($cells[0]);
			$row1 = $this->intRow($cells[1]);
			$col1 = $this->intCol($cells[1]);
			if(!$row1)	$row1=$row0;
			if(!$col1)	$col1=$col0;
			for($row=$row0; $row<=$row1; $row++) {	//	foreach row...
				for($col=$col0; $col<=$col1; $col++) {	//	foreach col in row...
					$cell = $this->activeSheet->getCellByColumnAndRow($col-1,$row);
					$validation = $cell->getDataValidation();
					$validation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
					$validation->setAllowBlank(false);
					$validation->setShowDropDown(true);
					$validation->setFormula1($elements);
					$cell->setDataValidation($validation);
				}
			}
		}
	}

	/**
	 * Fügt eine Auswahlliste auf einen Zellbereich an. Die Auswahlliste ein eine Zellreferenz
	 * @param		string		$selection			Quellbereich mit den Auswahlwerten
	 * @param		mixed			$rowARange			Zeile A/Zelle/Zellbereich für die Validierung
	 * @param		integer		$colA						Spalte A
	 * @param		integer		$rowB						Zeile B
	 * @param		integer		$colB						Spalte B
	 */
	public function setValidationRange($selection, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			$cells = explode(':',$range);
			$row0 = $this->intRow($cells[0]);
			$col0 = $this->intCol($cells[0]);
			$row1 = $this->intRow($cells[1]);
			$col1 = $this->intCol($cells[1]);
			if(!$row1)	$row1=$row0;
			if(!$col1)	$col1=$col0;
			for($row=$row0; $row<=$row1; $row++) {	//	foreach row...
				for($col=$col0; $col<=$col1; $col++) {	//	foreach col in row...
					$cell = $this->activeSheet->getCellByColumnAndRow($col-1,$row);
					$validation = $cell->getDataValidation();
					$validation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
					$validation->setAllowBlank(false);
					$validation->setShowDropDown(true);
					$validation->setFormula1('='.$selection);
					$cell->setDataValidation($validation);
				}
			}
		}
	}

	/**
	 * Überspring eine Spalte
	 */
	public function skip() {
		$this->col++;
	}

	/**
	 * Ermittelt einen Zellwert
	 * @param string $range								Zelle in AB-Notation
	 * @param boolean $calc								true forciert Berechnung
	 * @return Ambigous <NULL, number, mixed>
	 */
	public function value($range = false, $calc = true) {
		$this->setPos($range);
		$value = null;
		if($calc) {
			$value = $this->activeSheet->getCell($range)->getCalculatedValue();
		}
		else $value = $this->activeSheet->getCellByColumnAndRow($this->col, $this->row)->getValue();

		if($value == '#NUM!')		$value = 0;
		if($value == '#VALUE!')	$value = 0;
		return $value;
	}

	/**
	 * Schreibt einen oder mehrere Werte in einen Zellbereich und führt einen Spaltenvorschub
	 * @param		mixed		$data							Wert(e), die in den Zielbereich geschrieben werden
	 * @param		mixed		$rowRange					Zeile oder Zellen(n) in AB-Notation als Array oder Komma-separiert
	 * @param		integer	$col							Spalte
	 * @param		string	$style						Style-Anweisungen
	 */
	public function write($data=false, $rowRange=false, $col=false, $style=false, $type = false) {
		$this->setPos($rowRange, $col);
		if(is_array($data)) {
			foreach($data as $item) {
				$this->write($item, false, false, $style, $type);
			}
		} else {
			$this->lastRow = $this->row;
			$this->lastCol = $this->col;
			if($this->lastRow > $this->maxRow) $this->maxRow = $this->lastRow;
			if($this->lastCol > $this->maxCol) $this->maxCol = $this->lastCol;
			//
			//	Formel parsen und RC-Notation umlegen
			if(strpos($data, '=') === 0) {
				$data = $this->_parseFormula($data);
			}
			if(strpos($style, 'text') !== false)	{
				$this->activeSheet->setCellValueExplicitByColumnAndRow($this->col - 1, $this->row, str_replace('¦',chr(13), $data));
			} else {
				$this->activeSheet->setCellValueByColumnAndRow($this->col - 1, $this->row, str_replace('¦',chr(13), $data), null, $type);
			}
			if($style !== false) $this->setStyle($style, $this->row, $this->col);
			$this->col++;
		}
	}

	/**
	 * Schreibt einen Zellwert als Text
	 * @param mixed $data									Wert(e), die in den Zielbereich geschriebe nwerden
	 * @param mixed $rowRange							Zeile oder Zelle in AB-Notation
	 * @param integer $col								Spalte
	 */
	public function writeText($data=false, $rowRange=false, $col=false) {
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
			//
			//	Formel parsen und RC-Notation umlegen
			if(strpos($data, '=') === 0) {
				$data = $this->_parseFormula($data);
			}
			$this->activeSheet->setCellValueExplicitByColumnAndRow($this->col-1,$this->row, str_replace('¦',chr(13), $data));
			$this->col++;
		}
	}

	/**
	 * Schreibt einen oder mehrere Werte in einen Zellbereich und führt einen Zeilenvorschub aus
	 * @param		mixed		$data							Wert(e), die in den Zielbereich geschrieben werden
	 * @param		mixed		$rowRange					Zeile oder Zelle(n) in AB-Notation als Array oder Komma-separiert
	 * @param		integer	$col							Spalte
	 * @param		string	$style						Stlye-Anweisungen
	 */
	public function writeCol($data=false, $rowRange=false, $col=false, $style=false) {
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
			//
			//	Formel parsen und RC-Notation umlegen
			if(strpos($data, '=') === 0) {
				$data = $this->_parseFormula($data);
			}
			$this->activeSheet->setCellValueByColumnAndRow($this->col - 1, $this->row, str_replace('¦',chr(13), $data));
			if($style !== false) $this->setStyle($style, $this->row, $this->col);
			$this->row++;
		}
	}

	//////////////////////////////////////
	//	Selection, Range, Adressierung	//
	//////////////////////////////////////

	/**
	 * Erweitert die aktuelle Selektion um einen Bereich
	 * @param mixed $rowARange		Zeile A oder Bereich in AB-Notation als String, Array oder komma-separierte Liste
	 * @param integer $colA				Spalte A
	 * @param integer $rowB				Zeile B
	 * @param integer $colB				Spalte B
	 */
	public function addRange($rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB, true);
	}

	/**
	 * Kopiert eine Zeile mit allen Inhalten (Werte, Formeln, Format), indem diese direkt darunter eingefügt wird.
	 * Bestehender Inhalt wird um die eingefügte Zeilen nach unten verschoben.
	 * @param integer $sourceRow						Quellzeile
	 * @param	integer $numRows							Anzahl Zeilen
	 * @todo Folgezeilen, welche Formelbezüge in den übergeordneten Bereich tragen, müssen nachgerechnet werden.
	 */
	public function copyRow($sourceRow=1, $numRows=1) {
		$this->activeSheet->insertNewRowBefore($sourceRow+1,$numRows);
		//	MergeCells vorbereiten
		$mergeCells = array();
		foreach($this->activeSheet->getMergeCells() as $mergeRange) {
			$cells = explode(':', $mergeRange);
			$rowA = $this->intRow($cells[0]);
			$colA = $this->intCol($cells[0]);
			$rowB = $this->intRow($cells[1]);
			$colB = $this->intCol($cells[1]);
			if($rowA<=$sourceRow && $rowB>=$sourceRow) {
				$mergeCells[] = array($rowA, $colA, $rowB, $colB);
			}
		}
		for($col=1; $col<=$this->maxCol; $col++) {	//	foreach Column...
			//	Zellinhalt (Wert und Formeln) an Spaltenposition ermitteln
			$sourceAdr	=	$this->xlRange($sourceRow, $col);
			$sourceCell	=	$this->activeSheet->getCell($sourceAdr);
			$value			=	$sourceCell->getValue();
			$validation	=	$sourceCell->getDataValidation();
			if(substr($value,0,1) == '=') {
				$valueArr = $this->_parseFormulaAB($value);
			} else {
				$valueArr = array();
			}
			//	Bedingte Formatierungen an Spaltenposition ermitteln
			$conditionalStyles = $this->activeSheet->getConditionalStyles($sourceAdr);
			$conditionsArr = array();
			foreach($conditionalStyles as $conditionalStyle) {
				foreach($conditionalStyle->getConditions() as $condition) {
					$conditionsArr[] = $this->_parseFormulaAB($condition);
				}
			}
			//	Werte, Formeln und bedingte Formate für alle eingefügten Zeilen eintragen
			$rowIdx = 0;
			for($row=$sourceRow+1; $row<=$sourceRow+$numRows; $row++) {	//	foreach Row...
				$value = $sourceCell->getValue();
				$targetAdr	=	$this->xlRange($row, $col);
				$targetCell	=	$this->activeSheet->getCell($targetAdr);
				$rowIdx++;
				if($col == 1) {	//	Erster Iterationsschritt
					//	MergeCells behandeln
					foreach($mergeCells as $range) {
						$this->mergeCells($range[0]+$rowIdx, $range[1], $range[2]+$rowIdx, $range[3]);
					}
				}
				//	Formel-Zellbezüge umrechnen und Inhalt schreiben
				foreach($valueArr as $key => $valuePart) {
					$value = str_replace($key, $valuePart[0].($valuePart[1]+$rowIdx), $value);
				}
				$targetCell->setValue($value);
				$targetCell->setDataValidation($validation);
				//	Bedingte Formatierungen ermitteln und umrechnen
				$conditionalStyles = $this->activeSheet->getConditionalStyles($targetAdr);
				foreach($conditionalStyles as $idx => $conditionalStyle) {
					foreach($conditionalStyle->getConditions() as $condition) {
						if(!is_array($conditionsArr[$idx]))		continue;
						foreach($conditionsArr[$idx] as $adrSrc => $conditionPart) {
							$condition = str_replace($adrSrc,$conditionPart[0].($conditionPart[1]+$rowIdx), $condition);
						}
						$conditionalStyle->setCondition($condition);
					}
				}
			} // next Row
		}	//	next Column
	}

	/**
	 * Ermittelt die Zell-Koordinaten in AB-Notation
	 * @param integer $row								Zeile
	 * @param integer $col								Spalte
	 * @param boolean $absolute						true für absoluten Zellbezug
	 * @return string											Zelle in AB-Notation
	 */
	public function getCoordinate($row, $col, $absolute=false) {
		$colStr = $this->strCol($col);
		if($absolute === false) return $colStr . ($row);
		if($absolute === true)	return '$' . $colStr . '$' . ($row);
		if($absolute == 'row')	return $colStr . '$' . ($row);
		if($absolute == 'col')	return '$' . $colStr . ($row);
	}

	/**
	 * Ermittelt die aktuelle Auswahl in AB-Notation als Komma-delimitierte Liste
	 * @return string
	 */
	public function getSelectionString() {
		if(count($this->selection) == 0)	$this->selectRange();
		foreach($this->selection as $range) {
			$selection .= $range . ',';
		}
		return substr($selection,0,strlen($selection)-1);
	}

	/**
	 * Ermittelt eine Zell-Bezugsadresse von der absoluten Position $ref zum relativen Zielbereich in RC-Notation
	 * @param mixed $ref									Zelle oder array(Zeile,Spalte) für den absoluten Bezugspunkt
	 * @param mixed $rowARange						Zeile A oder Bereich in AB-Notation
	 * @param integer $colA								Spalte A
	 * @param integer $rowB								Zeile B
	 * @param integer $colB								Spalte B
	 * @return string											Zell-Bezug in RC-Notation
	 */
	public function getRC($ref, $rowARange, $colA=false, $rowB=false, $colB=false) {
		if(is_array($ref)) {
			$row0 = $ref[0];
			$col0 = $ref[1];
		} else {
			$row0 = $this->intRow($ref);
			$col0 = $this->intCol($ref);
		}
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		$cells = explode(':',$this->selection[0]);
		$row1 = $this->intRow($cells[0]);
		$col1 = $this->intCol($cells[0]);
		$rowDiff = $row1 - $row0;
		$colDiff = $col1 - $col0;
		$rc = 'R';
		if($rowDiff != 0)	$rc .= '[' . $rowDiff . ']';
		$rc .= 'C';
		if($colDiff != 0) $rc .= '[' . $colDiff . ']';
		if(count($cells)>1) {
			$rc .= ':';
			$row1 = $this->intRow($cells[1]);
			$col1 = $this->intCol($cells[1]);
			$rowDiff = $row1 - $row0;
			$colDiff = $col1 - $col0;
			$rc .= 'R';
			if($rowDiff != 0)	$rc .= '[' . $rowDiff . ']';
			$rc .= 'C';
			if($colDiff != 0) $rc .= '[' . $colDiff . ']';
		}

		return $rc;
	}

	/**
	 * Fügt VOR einer Spalte weitere Spalte(n) ein und verschiebt den Inhalt nach rechts
	 * @param integer $col								Spalte (false=aktuelle Spalte)
	 * @param integer $cols								Anzahl Spalten, die eingefügt werden sollen
	 */
	public function insertCol($col=false, $cols=1) {
		if(!$col)		$col = $this->col;
		if($col == -1)	$col = $this->maxCol;
		$this->activeSheet->insertNewColumnBeforeByIndex($col-1, $cols);
		$this->maxCol += $cols;
	}

	/**
	 * Fügt VOR einer Zeile weitere Zeilen ein und verschiebt den Inhalt nach unten
	 * @param integer $row								Zeile (false = aktuelle Zeile)
	 * @param integer $rows								Anzahl Zeilen, die eingefügt werden sollen
	 */
	public function insertRow($row=false, $rows=1) {
		if(!$row)					$row = $this->row;
		if($row == -1)		$row = $this->maxRow;
		$this->activeSheet->insertNewRowBefore($row, $rows);
		$this->maxRow += $rows;
	}

	/**
	 * Ermittelt die Spaltennummer ab AB-Notation
	 * @param		string	$range						Zelle in AB-Notation
	 * @return	integer										Zeilennummer
	 */
	public function intCol($range) {
		$lrange = preg_replace("/[0-9$]/","",$range);
		$strlen = strlen($lrange);
		for($i=0; $i<$strlen; $i++) {
			$rc += pow(26,$strlen - $i - 1) * (ord($lrange{$i}) - 64);
		}
		return $rc;
	}

	/**
	 * Ermittelt die Zeilennummer ab AB-Notation
	 * @param		string	$range						Zelle in AB-Notation
	 * @return	integer										Zeilennummer
	 */
	public function intRow($range) {
		return preg_replace("/[^0-9]/","",$range);
	}

	/**
	 * Wählt einen Zellbereich aus bzw. erweitert die aktuelle Zellauswahl
	 * @param		mixed		$rowARange				Zeile A oder Bereich(e) in AB-Notation als Array oder Komma-delimited
	 * @param		integer	$colA							Spalte A
	 * @param		integer	$rowB							Zeile B
	 * @param		integer	$colB							Spalte B
	 * @param		boolean	$append						true, wenn die Auswahl erweitert werden soll
	 */
	public function selectRange($rowARange=false, $colA=false, $rowB=false, $colB=false, $append=false) {
		if($rowARange !== false) {					//	Verarbeite gemeldete Auswahl
			if($append == false)	$this->selection = array();
			if(is_numeric($rowARange)) {
				$this->selection[] = $this->xlRange($rowARange, $colA, $rowB, $colB);
			} else if(is_array($rowARange)) {
				foreach($rowARange as $range) {
					$this->selection[] = $range;
				}
			} else {
				$ranges = explode(',',$rowARange);
				foreach($ranges as $range) {
					$this->selection[] = $range;
				}
			}
		}
		if(count($this->selection) == 0)	$this->selection[] = $this->xlRange($this->lastRow, $this->lastCol);
	}

	/**
	 * Definiert für einen Zellbereich einen Namen
	 * @param		string	$name							Adressbezeichnung
	 * @param		mixed		$rowARange				Zeile 1 oder Zelle in AB-Notation, als komma-separierte Liste oder Array
	 * @param		integer	$colA							Spalte 1
	 * @param		integer	$rowB							Zeile 2
	 * @param		integer $colB							Spalte 2
	 */
	public function setNamedRange($name, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		$range = $this->selection[0];
		$this->excel->addNamedRange(new PHPExcel_NamedRange($name, $this->activeSheet, $range));
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
	 * @param		boolean		$hideRows				true verstekct die Zeilen
	 */
	public function setNamedRangeByList($label, $rangeName, $list, $rowRange, $col=false, $hideCol=true, $hideRows=false) {
		$this->setPos($rowRange, $col);
		$row0 = $this->row;
		$col0 = $this->col;
		if(is_string($list)) {
			$larray = explode(',', $list);
		} else {
			$larray = $list;
		}
		$this->write($label, $row0, $col);
		foreach($larray as $item) {
			if(is_object($item)) {
				foreach($item as $element) {
					$value = $element;
					break;
				}
			} else {
				$value = $item;
			}
			$this->newline();
			$this->write($value, $this->row, $col0);
		}
		$this->setStyle('header', $row0, $col0);
		$this->setBorder('thin', 'hair', $row0, $col0);
		$this->setBorder('thin', 'hair', $row0+1, $col0, $this->row, $col0);
		$this->setNamedRange($rangeName, $row0+1, $col0, $this->row, $col0);
		if($hideCol)		$this->hideCols($col0);
		if($hideRows)		$this->hideRows($row0, $this->row);
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
	 * Bestimmt den Druckbereich
	 * @param		mixed		$rowARange				Zeile A oder Bereich in AB-Notation
	 * @param		integer	$colA							Spalte A
	 * @param		integer	$rowB							Zeile B
	 * @param		integer	$colB							Spalte B
	 * @param		boolean	$append						true fügt den Druckbereich dem bestehenden hinzu
	 */
	public function setPrintArea($rowARange, $colA=false, $rowB=false, $colB=false, $append=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			if(!$append) {
				$this->activeSheet->getPageSetup()->setPrintArea($range, 0, PHPExcel_Worksheet_PageSetup::SETPRINTRANGE_OVERWRITE);
				$append = true;
			} else {
				$this->activeSheet->getPageSetup()->setPrintArea($range, 0, PHPExcel_Worksheet_PageSetup::SETPRINTRANGE_INSERT);
			}
		}
	}

	/**
	 * Setzt den Cursor an eine Zielposition
	 * @param string $range
	 */
	public function setSelection($range) {
		$this->activeSheet->setSelectedCell('A1');
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

	/**
	 * Ermittelt den Zellbereich in AB-Notation
	 * @param		integer	$rowA			Erste Zeile
	 * @param		integer	$colA			Erste Spalte
	 * @param		integer	$rowB			Zweite Zeile
	 * @param		integer	$colB			Zweite Spalte
	 * @param		mixed		$absolute	true, wenn der Zellbezug absolut sein soll
	 * @return	string						Zelladresse in AB-Notation
	 */
	public function xlRange($rowA=false, $colA=false, $rowB=false, $colB=false, $absolute=false) {
		if($rowA == false)		$rowA = $this->row;
		if($rowA == -1)				$rowA = $this->maxRow;
		if($colA == false)		$colA = $this->col;
		if($colA == -1)				$colA = $this->maxCol;
		$rc = $this->getCoordinate($rowA, $colA, $absolute);
		if($rowB !== false || $colB !== false) {
			if($rowB == false)	$rowB = $rowA;
			if($rowB == -1)			$rowB = $this->maxRow;
			if($colB == false)	$colB = $colA;
			if($colB == -1)	$colB = $this->maxCol;
			$rc .= ':' . $this->getCoordinate($rowB, $colB, $absolute);
		}
		return $rc;
	}

	/*
	 * Chart-Methods
	 */

	/**
	 * Erstellt eine Excel-Chart nach vorliegender Spezfikation
	 * @param array $params								Array mit kompletter Chart-Spezifikation
	 */
	public function createChart($params=array()) {
		//Unumgängliche Kriterien:
		if(!is_array($params))					return;
		if(!$params['values'])					return;

		//Optionale Kriterien:
		if(!$params['title'])									$params['title']					= '';
		if(!$params['categories'])						$params['categories']			= array();			//	mixed		Range(s) mit Kategorien
		if(!$params['type'])									$params['type']						= 'barChart';		//	string	[areaChart,area3DChart,barChart,bar3DChart,bubbleChart,doughnutChart,lineChart,line3DChart,pieChart,pie3DChart,radarChart,scatterChart,stockChart,surfaceChart,surface3DChart]
		if(!$params['direction'])							$params['direction']			= 'col';				//	string	[bar,col]
		if(!$params['group'])									$params['group']					= 'standard';		//	string	[clustered,percentStacked,stacked,standard]
		if(!$params['range'])									$params['range']					= $this->xlRange($this->maxRow+1,1,$this->maxRow+20,10);	//	string	Zielbereich
		if(!is_array($params['layout']))			$params['layout']					= array();
		if(is_string($params['categories']))	$params['categories']			= array($params['categories']);
		if(!$params['legend'])								$params['legend']					= '';
		//
		//	Verarbeitung der Chart-Types
		switch($params['type']) {
			case 'areaChart':
				$params['marker'] = null;
				if($params['direction'] != 'col') 		$params['group'] = 'col'; 			//Darf auch bar sein, hat aber keinen Einfluss.
				if($params['group'] == 'clustered') 	$params['group'] = 'standard';		//Clustered funktioneirt nicht.
				break;
			case 'area3DChart':
				$params['marker'] = null;
				if($params['direction'] != 'col') 		$params['group'] = 'col'; 			//Darf auch bar sein, hat aber keinen Einfluss.
				if($params['group'] == 'clustered') 	$params['group'] = 'standard';		//Clustered funktioneirt nicht.
				break;
			case 'barChart':
				$params['marker'] = null;
				if($params['group'] == 'clustered') 	$params['group'] = 'standard';		//Darf auch clustered sein, hat aber keinen Einfluss
				break;
			case 'bar3DChart':
				$params['marker'] = null;
				if($params['group'] == 'clustered') 	$params['group'] = 'standard';		//Darf auch clustered sein, hat aber keinen Einfluss
				break;
			case 'doughnutChart':
				$params['marker'] = null;
				if($params['group'] != 'standard')		$params['group'] = 'standard';		//Darf alles sein, sieht immer aus wie Standard.
				if($params['direction'] != 'col')		$params['direction'] = 'col';		//Darf alles sein, sieht immer so aus wie Col.
				break;
			case 'lineChart':
				$params['marker'] = null;
				if($params['direction'] != 'col') 		$params['direction'] = 'col'; 		//Darf auch bar sein, hat aber keinen Einfluss.
				if($params['group'] == 'clustered') 	$params['group'] = 'standard';		//Clustered funktioneirt nicht.
				break;
			case 'line3DChart':
				$params['marker'] = null;
				if($params['direction'] != 'col') 			$params['direction'] = 'col'; 	//Darf auch bar sein, hat aber keinen Einfluss.
				if($params['group'] == 'clustered') 		$params['group'] = 'standard';	//Clustered funktioneirt nicht.
				break;
			case 'pieChart':
				$params['marker'] = null;
				if($params['direction'] != 'col') 			$params['direction'] = 'col'; 	//Darf auch bar sein, hat aber keinen Einfluss.
				if($params['group'] == 'clustered') 		$params['group'] = 'standard';	//Clustered funktioneirt nicht.
				break;
			case 'pie3DChart':
				$params['marker'] = null;
				if($params['direction'] != 'col') 			$params['direction'] = 'col'; 	//Darf auch bar sein, hat aber keinen Einfluss.
				if($params['group'] == 'clustered') 		$params['group'] = 'standard';	//Clustered funktioneirt nicht.
				break;
			case 'radarChart':
				$params['marker'] = 'marker';												//Marker muss marker sein
				$params['group'] = null;													//Group darf nur null sein
				$params['direction'] = 'col';												//Direction muss col sein
				break;
			default:
				$params['marker'] = null;
				if($params['group'] == 'clustered') 	$params['group'] = 'standard';		//Darf auch clustered sein, hat aber keinen Einfluss
		}
		//
		//	Aufbereiten des Titels
		$title = null;
		if($params['title']) {
			$title = new PHPExcel_Chart_Title();
			$title->setCaption($params['title']);
		}

		//
		//	Aufbereiten des Layouts
		$layout = new PHPExcel_Chart_Layout();
		if($params['layout']) {
			if(isset($params['layout']['showvalues']))			$layout->setShowVal($params['layout']['showvalues']);
			if(isset($params['layout']['showlegendkeys']))	$layout->setShowLegendKey($params['layout']['showvalues']);
			if(isset($params['layout']['showcategories']))	$layout->setShowCatName($params['layout']['showcategories']);
			if(isset($params['layout']['showpercent']))			$layout->setShowPercent($params['layout']['showpercent']);
			if(isset($params['layout']['showserial']))			$layout->setShowSerName($params['layout']['showserial']);
			if(isset($params['layout']['showbubblesize']))	$layout->setShowBubbleSize($params['layout']['showbubblesize']);
			if(isset($params['layout']['showleaderlines']))	$layout->setShowSerName($params['layout']['showleaderlines']);
		}
		//
		//	Aufbereiten der Legende
		$legend = null;
		if($params['legend']) {
			$legend = new PHPExcel_Chart_Legend();
			$legend->setPosition($params['legend']);
		}
		//
		//	Aufbereiten der Beschriftungen
		$labels = array();
		if(is_string($params['labels']))		$params['labels'] = explode(',',$params['labels']);
		if(is_array($params['labels'])) {
			foreach($params['labels'] as $label) {
				if(strpos($label,'"') === false) {
					if(strpos($label,'!') === false) {
						$label = "'".$this->activeSheet->getTitle()."'!".$label;
					}
				}
				$labels[] = new PHPExcel_Chart_DataSeriesValues('String' ,$label, null, 1);
			}
		}
		//
		//	Aufbereiten der Kategorien
		$categories	=	array();
		if(is_string($params['categories'])) {
			$params['categories'] = explode(',',$params['categories']);
		}
		if(is_array($params['categories'])) {
			foreach($params['categories'] as $category) {
				$categories[] = new PHPExcel_Chart_DataSeriesValues	('String' ,"'".$this->activeSheet->getTitle()."'!". $category, null, 1);
			}
		}
		//	Aufbereiten der Serien
		if(is_string($params['values'])) {
			$params['values'] = explode(',',$params['values']);
		}
		$params['values'] = array($params['values']);
		$series = array();
		foreach($params['values'] as $valueSerie) {
			$values = array();
			$order = array();
			foreach($valueSerie as $key => $value) {
				$order[] = $key;
				$values[] = new PHPExcel_Chart_DataSeriesValues('Number', "'".$this->activeSheet->getTitle()."'!".$value, null, 1);
			}
			$newSerie = new PHPExcel_Chart_DataSeries($params['type'], $params['group'], $order, $labels, $categories, $values, null, $params['marker']);
			$newSerie->setPlotDirection($params['direction']);
			$series[] = $newSerie;
		}
		//
		//	Aufbereiten des Ausgabebereichs
		$plotArea = new PHPExcel_Chart_PlotArea($layout	,$series);																						//	plotSeries			array(PHPExcel_Chart_DataSeries)
		//
		//	Aufbereiten des Diagramms
		$chart = new PHPExcel_Chart('Chart ' + $this->_chartCount+1, $title, $legend, $plotArea);
		$range = explode(':',$params['range']);
		$chart->setTopLeftPosition($range[0]);
		$chart->setBottomRightPosition($range[1]);
		$this->activeSheet->addChart($chart);
		//
		//	Erhöhe Chart Anzahl
		$this->_chartCount++;
	}

	/*
	 * Styling, Gliederung und Formatierung
	 */

	/**
	 * Ermittelt einen Standard-Style
	 * @param string $style								Textkonstante für Style
	 * @param string $additionalStyle			Zusätzliche Style-Anweisungen
	 * @return string
	 */
	public function getStyle($style, $additionalStyle = '') {
		switch(strtolower($style)) {
			case 'footer':			$style = Util::systemParam('ExcelStyleFooter');																			break;
			case 'formula':			$style = Util::systemParam('ExcelStyleFormula');																		break;
			case 'header':			$style = Util::systemParam('ExcelStyleHeader');																			break;
			case 'hyperlink':		$style = Util::systemParam('ExcelStyleHyperlink');																	break;
			case 'input':				$style = Util::systemParam('ExcelStyleInput') . ';protection-locked:unprotected';		break;
			case 'manual':			$style = Util::systemParam('ExcelStyleManual');																			break;
			case 'subfooter':		$style = Util::systemParam('ExcelStyleSubfooter');																	break;
			case 'subheader':		$style = Util::systemParam('ExcelStyleSubheader');																	break;
		}
		if($additionalStyle)	$style .= ';'.$additionalStyle;
		return str_replace('rgb:#','rgb:',$style);
	}

	/**
	 * Gruppiert Spalten
	 * @param		integer	$startArr					Erste Spalte oder Spalte(n) in Array- oder Komma-delimited Nottion
	 * @param		integer	$end							Letzte Spalte
	 * @param		boolean	$collapse					true schliesst die Gruppierung
	 */
	public function groupCols($startArr, $end=false, $collapse = false) {
		if(is_numeric($startArr)) {
			if($end == -1)	$end = $this->maxCol;
			if($end === false)	$end = $startArr;
			for($col=$startArr;$col<=$end;$col++) {
				$columnDimension = $this->activeSheet->getColumnDimensionByColumn($col-1);
				$level = $columnDimension->getOutlineLevel() + 1;
				$columnDimension->setOutlineLevel($level);
				if($level == 1) {
					$columnDimension->setCollapsed($collapse);
					$columnDimension->setVisible($collapse == false ? true : false);  			}
			}
		} elseif(is_array($startArr)) {
			foreach($startArr as $start) {
				$this->groupCols($start, $end, $collapse);
			}
		} else {
			$ranges = explode(',',$startArr);
			foreach($ranges as $start) {
				$end=false;
				if(!is_numeric($start)) {
					$cellArr = explode(':',$start);
					$start = $this->intCol($cellArr[0]);
					if(count($cellArr)>1) {
						$end = $this->intCol($cellArr[1]);
					}
				}
				$this->groupCols($start, $end, $collapse);
			}
		}
	}

	/**
	 * Gruppiert Zeilen
	 * @param		integer	$start						Erste Zeile
	 * @param		integer	$end							Letzte Zeile
	 * @param		boolean	$collapse					true schliesst die Gruppierung
	 */
	public function groupRows($start, $end, $collapse = false) {
		if($end == -1)	$end = $this->maxCol;
		for($i=$start+1;$i<=$end+1;$i++) {
			$rowDimension = $this->activeSheet->getRowDimension($i);
			$level = $rowDimension->getOutlineLevel() + 1;
			$rowDimension->setOutlineLevel($level);
			if($level==1) {
				$rowDimension->setCollapsed($collapse);
				$rowDimension->setVisible($collapse == false ? true : false);
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
			$cells = explode(':', $range);
			$col0 = $this->intCol($cells[0]);
			$col1 = $col0;
			if(count($cells)>1) $col1 = $this->intCol($cells[1]);
			for($col=$col0; $col<=$col1; $col++) {
				$this->activeSheet->getColumnDimensionByColumn($col-1)->setVisible(false);
			}
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
			$cells = explode(':', $range);
			$row0 = $this->intRow($cells[0]);
			$row1 = $row0;
			if(count($cells)>1)	$row1 = $this->intRow($cells[1]);
			for($row=$row0; $row<=$row1;$row++) {
				$this->activeSheet->getRowDimension($row)->setVisible(false);
			}
		}
	}

	/**
	 * Erstellt einen Rahmen um einen Zellbereich
	 * none, dashDot, dashDotDot, dashed, dotted, double, hair, medium, mediumDashDot, mediumDashDotDot, mediumDashed, slantDashDot, thick, thin
	 * @param		string	$frame						Äussere Rahmenlinen
	 * @param		string	$grid							Innere Gridlinien
	 * @param		mixed		$rowARange				Zeile 1 oder Bereich(e) in AB-Notation als Array oder Komma-delimited
	 * @param		integer	$colA							Spalte 1
	 * @param		integer	$rowB							Zeile 2
	 * @param		integer	$colB							Spalte 2
	 */
	public function setBorder($frame=false, $grid=false, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		if($this->params->quick)		return;
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		$style = '';
		if($frame	!==	false)	$style	.=	'borders-outline-style:' . $frame . ';';
		if($grid	!==	false)	$style	.=	'borders-inside-style:' . $grid;
		if($style) {
			foreach($this->selection as $range) {
				$this->setStyle($style, $range);
			}
		}
	}

	/**
	 * Diese Funktion setzt einen Divider (Spalte).
	 */
	public function setColumnDivider($dividerLineStyle, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			if(strpos($range,':')) {
				$array = explode(':', $range);
				$col 	= $this->intCol($array[0]);
				$col2	=	$this->intCol($array[1])+1;
				$this->setColumnWidth('divider', $col);
				$this->setColumnWidth('divider', $col2);
			} else {
				$this->setColumnWidth('divider', $this->intCol($range));
				$this->setColumnWidth('divider', $this->intCol($range)+1);
			}
			$this->setStyle($dividerLineStyle, $range);
		}
	}

	/**
	 * Definiert die Spaltenbreite
	 * @param		mixed		$width						Spaltenbreite oder false für AutoSize
	 * @param		mixed		$colARange				Erste Spalte oder Bereich(e) in Spaltennummern als Array oder Komma-delimitiert
	 * @param		integer	$colB							Zweite Spalte
	 */
	public function setColumnWidth($width=false, $colARange=false, $colB=false) {
		switch(strtolower($width)) {
			case 'div':				$width=1;		break;
			case 'divider':		$width=1.8;	break;
			case 'year':
			case 'percent':		$width=6;		break;
			case 'grid':			$width=8;		break;
			case 'date':			$width=12;	break;
			case 'thousand':	$width=10;	break;
			case 'zip':				$width=10;	break;
			case 'million':		$width=11;	break;
			case 'date':			$width=12;	break;
			case 'billion':		$width=15;	break;
			case 'label':			$width=16;	break;
			case 'lig':				$width=25;	break;
			case 'comment':		$width=35;	break;
			case 'account':		$width=50;	break;
			case 'long':			$width=70;	break;
		}
		if($colARange == false && $colB == false)	{
			$colARange = 1;
			$colB = $this->maxCol;
		}
		if(is_numeric($colARange)) {
			$colARange = (string)$colARange;
			if($colB !== false) {
				if($colB == -1)	$colB = $this->maxCol;
				$colARange .= ':' . $colB;
			}
		}
		if(is_array($colARange)) {
			foreach($colARange as $range) {
				$this->setColumWidth($width, $range);
			}
		} else {
			$ranges = explode(',',$colARange);
			foreach($ranges as $range) {
				$cells = explode(':', $range);
				$col0 = $cells[0];
				$col1 = $cells[1];
				if(!$col1)	$col1=$col0;
				if(!is_numeric($col0))	$col0 = $this->intCol($col0);
				if(!is_numeric($col1))	$col1 = $this->intCol($col1);
				for($col=$col0;$col<=$col1;$col++) {
					$column = $this->activeSheet->getColumnDimensionByColumn($col-1);
					if($width == false) {
						$column->setAutoSize(true);
					} else {
						$column->setAutoSize(false);
						$column->setWidth($width);
					}
				}
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
		if($this->params->quick)	return;
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		$styleArr = $this->_parseStyle($style);
		foreach($this->selection as $range) {
			$cells = explode(':', $range);
			$row0 = $this->intRow($cells[0]);
			$col0 = $this->intCol($cells[0]);
			$row1 = $row0;
			$col1 = $col0;
			if(count($cells)>1) {
				$row1 = $this->intRow($cells[1]);
				$col1 = $this->intCol($cells[1]);
			}
			for($row=$row0;$row<=$row1;$row++) {
				for($col=$col0;$col<=$col1;$col++) {
					$cond = new PHPExcel_Style_Conditional();
					$this->lastRow = $row;
					$this->lastCol = $col;
					$condStyle = $cond->getStyle();
					$condFormula = $this->_parseFormula($condition);
					switch(substr($condFormula, 0, 2)) {
						case '<=':
							$cond->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
							$cond->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_LESSTHANOREQUAL);
							$cond->addCondition(substr($condFormula,2,strlen($condFormula)-2));
							break;
						case '>=':
							$cond->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
							$cond->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_GREATERTHANOREQUAL);
							$cond->addCondition(substr($condFormula,2,strlen($condFormula)-2));
							break;
						case '<>':
							$cond->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
							$cond->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_NOTEQUAL);
							$cond->addCondition(substr($condFormula,2,strlen($condFormula)-2));
							break;
						default:
							switch(substr($condFormula, 0, 1)) {
								case '=':
									$cond->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
									$cond->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_EQUAL);
									$cond->addCondition(substr($condFormula,1,strlen($condFormula)-1));
									break;
								case '<':
									$cond->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
									$cond->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_LESSTHAN);
									$cond->addCondition(substr($condFormula,1,strlen($condFormula)-1));
									break;
								case '>':
									$cond->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
									$cond->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_GREATERTHAN);
									$cond->addCondition(substr($condFormula,1,strlen($condFormula)-1));
									break;
								default:
									$cond->setConditionType(PHPExcel_Style_Conditional::CONDITION_EXPRESSION);
									$cond->addCondition($condFormula);
							}
					}
					$baseStyle	=	$this->activeSheet->getStyle($this->xlRange($row, $col));
					$baseAlign	=	$baseStyle->getAlignment();
					$baseFont		=	$baseStyle->getFont();
					$condStyle->getNumberFormat()->setFormatCode($baseStyle->getNumberFormat()->getFormatCode());
					$condStyle->getFont()->applyFromArray(array(
							'name'				=>	$baseFont->getName()
							,'bold'				=>	$baseFont->getBold()
							,'italic'			=>	$baseFont->getItalic()
							,'underline'	=>	$baseFont->getUnderline()
							,'strike'			=>	$baseFont->getStrikethrough()
							,'size'				=>	$baseFont->getSize()
					));
					$condStyle->getAlignment()->applyFromArray(array(
							'horizontal'	=>	$baseAlign->getHorizontal()
							,'vertical'		=>	$baseAlign->getVertical()
							,'rotation'		=>	$baseAlign->getTextRotation()
							,'wrap'				=>	$baseAlign->getWrapText()
					));
					$condStyle->applyFromArray($this->_parseStyle($style));
					$condStyle->getFill()->setStartColor($condStyle->getFill()->getStartColor());
					$condStyle->getFill()->setEndColor($condStyle->getFill()->getStartColor());
					$styles = $baseStyle->getConditionalStyles();
					array_push($styles, $cond);
					$baseStyle->setConditionalStyles($styles);
				}
			}
		}
	}

	/**
	 * Definiert die Wiederholungszspalten
	 * @param		integer	$start						Erste Wiederholungsspalte
	 * @param		integer	$end							Letzte Wiederholungsspalte
	 */
	public function setRepeatColumns($start=1, $end=false) {
		if(!$end)	$end=$start;
		$start -= 1;
		if($end !== false)	$end -= 1;
		$start	= PHPExcel_Cell::stringFromColumnIndex($start);
		$end		=	PHPExcel_Cell::stringFromColumnIndex($end);
		$this->activeSheet->getPageSetup()->setColumnsToRepeatAtLeftByStartAndEnd($start, $end);
	}

	/**
	 * Definiert die Wiederholungszeilen
	 * @param		integer	$start						Erste Wiederholungszeile
	 * @param		integer	$end							Letzte Wiederholungszeile
	 */
	public function setRepeatRows($start=1, $end=false) {
		if(!$end)	$end=$start;
		if($end == -1)	$end = $this->maxRow;
		$this->activeSheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($start, $end);
	}

	/**
	 * Diese Funktion setzt einen Divider (Spalte).
	 */
	public function setRowDivider($dividerLineStyle, $rowARange=false, $colA=false, $rowB=false, $colB=false)	{
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		foreach($this->selection as $range) {
			if(strpos($range,':')) {
				$array = explode(':', $range);
				$col 	= $this->intCol($array[0]);
				$col2	=	$this->intCol($array[1]);
				$row	= $this->intRow($array[0]);
				$row2	= $this->intRow($array[1])+1;

				$this->setRowHeight('divider_small', $row);
				$this->setRowHeight('divider_small', $row2);
			}	else {
				$this->setRowHeight('divider_small', $this->intRow($range));
				$this->setRowHeight('divider_small', $this->intRow($range)+1);
			}
			$this->setStyle($dividerLineStyle, $range);
		}
	}

	/**
	 * Definiert die Zeilenhöhe
	 * @param		mixed		$height						Zeilenhöhe oder false für AutoSize
	 * @param		mixed		$rowARange				Erste Zeile oder Bereich(e) in Zeilennummern als Array oder Komma-delimitiert
	 * @param		integer	$rowB							Zweite Zeile
	 */
	public function setRowHeight($height=false, $rowARange=false, $rowB=false) {
		switch(strtolower($height)) {
			case false:						$height	=	-1;		break;
			case 'div':						$height = 4;		break;
			case 'divider_small':	$height	=	4;		break;
			case 'divider':				$height	=	8;		break;
			case 'break':					$height = 12;		break;
			case 'subsum':				$height = 17;		break;
			case 'header':				$height = 25;		break;
			case 'title':					$height	=	30;		break;
			case 'comment':				$height	=	50;		break;
			case 'introlabel':		$height	=	80;		break;
			case 'label':					$height	=	150;	break;
		}
		if($rowARange == false && $rowB == false)	{
			$rowARange=1;
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
				if(!$row1)	$row1=$row0;
				for($row=$row0;$row<=$row1;$row++) {
					if($height === true) {

					} else {
						$this->activeSheet->getRowDimension($row)->setRowHeight($height);
					}
				}
			}
		}
	}

	/**
	 * Print-Scale-Einstellung für Ansicht
	 * @param		integer	$scale						Druck-Skalierung in Prozentpunkten
	 */
	public function setScale($scale=100) {
		$this->activeSheet->getPageSetup()->setScale($scale);
	}

	/**
	 * Definiert den Style für einen Zellbereich
	 * @param		string	$style						Style-Anweisung oder Konstante
	 * @param		mixed		$rowARange				Erste Zeile oder Bereiche in AB-Notation als Array oder Komma-delimited
	 * @param		integer	$colA							Erste Spalte
	 * @param		integer	$rowB							Zweite Zeile
	 * @param		integer	$colB							Zweite Spalte
	 */
	public function setStyle($style, $rowARange=false, $colA=false, $rowB=false, $colB=false) {
		if($this->params->quick)	return;
		$this->selectRange($rowARange, $colA, $rowB, $colB);
		$style = $this->_parseStyle($style);
		foreach($this->selection as $range) {
			$this->activeSheet->duplicateStyleArray($style, $range);
		}
	}

	/**
	 * Setzt den Zoom-Faktor für die aktuelle Tabelle
	 * @param		integer	$zoom							Zoom-Faktor
	 */
	public function setZoom($zoom=100) {
		$this->activeSheet->getSheetView()->setZoomScale($zoom);
	}

	/*
	 * Private Interface
	 */

	/**
	 * Fügt Produkt- und Company-Images ein
	 */
	private function _addImages() {
		$file = $_SESSION['logosmallfile'];
		if(file_exists($file)) {
			$img = new PHPExcel_Worksheet_HeaderFooterDrawing();
			$img->setName($_SESSION['productlabel']);
			$img->setPath($file);
			$img->setHeight(60);
			$this->activeSheet->getHeaderFooter()->addImage($img, PHPExcel_Worksheet_HeaderFooter::IMAGE_HEADER_LEFT);
		}
		$this->activeSheet->getHeaderFooter()->setOddHeader('&G');
		$this->activeSheet->getHeaderFooter()->setOddHeader("&L&G&C&R&G");
	}

	/**
	 * Wandelt eine Zellangabe von RC- in AB-Notation
	 * @param		string	$rc								Zellbezug in RC-Notation
	 * @return	string										Zellbezug in AB-Notation
	 */
	private function _convertRC($rc) {
		$arr = explode('C', $rc);
		$row = trim($arr[0]);
		$col = trim($arr[1]);
		if(strpos($row, '[') !== false) {
			$pos1 = strpos($row, '[');
			$pos2 = strpos($row, ']');
			$rowPos = substr($row, $pos1 + 1, $pos2 - $pos1 - 1);
		}
		if(strpos($col, '[') !== false) {
			$pos1 = strpos($col, '[');
			$pos2 = strpos($col, ']');
			$colPos = substr($col, $pos1 + 1, $pos2 - $pos1 - 1);
		}
		$rowPos = ($rowPos + 1) + $this->lastRow - 1;
		$colPos = ($colPos + 1) + $this->lastCol - 1;
		if($rowPos < 0) {
			$rowPos = 0;
		}
		if($colPos < 0) {
			$colPos = 0;
		}
		return PHPExcel_Cell::stringFromColumnIndex($colPos-1) . $rowPos;
	}

	/**
	 * Initialisiert die Standard-RGB-Farbwerte
	 */
	private function _initRGB() {
		$this->rgb = array(
			'red'					=>	'f69e9e'
			,'lightred'		=>	'f2dddc'
			,'yellow'			=>	'ffff64'
			,'lightyellow'=>	'ffffc8'
			,'darkyellow'	=>	'dcdc00'
			,'yellow'			=>	'ffff64'
			,'green'			=>	'64ff64'
			,'lightgreen'	=>	'c8ffc8'
			,'darkgreen'	=>	'76C176'
			,'grey'				=>	'dddddd'
			,'lightgrey'	=>	'eeeeee'
		);
	}

	/**
	 * Sucht Zellbezüge in einer Formel und konvertiert diese aus RC in AB-Notation
	 * @param		string	$formula					Formel im Originalformat
	 * @return	string										Formeln in AB-Notation
	 */
	private function _parseFormula($formula) {
		preg_match_all("~R(\[[\-]?[0-9]*\])?C(\[[\-]?[0-9]*\])?~", $formula, $search);
		preg_match_all("~R[0-9]*C[0-9]*~", $formula, $search2);
		$findFormulas = array_merge($search, $search2);
		foreach($findFormulas[0] as $rc) {
			if(strpos($rc, '[') || $rc = 'RC') {
				$formula = preg_replace('/' . preg_quote($rc) . '/', $this->_convertRC($rc), $formula, 1);
			}
		}
		return $formula;
	}

	/**
	 * Sucht Zellbezüge in einer Formel in der AB-Notation und übermittelt die
	 * Fundstellen als array mit col,row-Elementen
	 */
	private function _parseFormulaAB($formula) {
		preg_match_all('~[A-Z]+[1-9]+[0-9]*~', $formula, $matches);		//Alle AB-Notationen extrahieren
		$adress = array();
		foreach($matches[0] as $key => $match) {
			preg_match('~[A-Z]+~',$match,$colPart);
			preg_match('~[1-9]+[0-9]*~',$match,$rowPart);
			$adress[$match] = array(
				$colPart[0]
				,$rowPart[0]
			);
		}
		return $adress;
	}

	/**
	 * Parst einen Style-String im CSS-Format
	 * @param		string	$stlye						Style-Anweisung
	 * @return	array											Style-Array
	 */
	private function _parseStyle($style) {
		if(is_array($style))	return $style;
		$styles = explode(';',$style);
		$style = '';
		foreach($styles as $tag) {
			$style .= $this->getStyle($tag) . ';';
		}
		$style = substr($style, 0, strlen($style) - 1);
		$style = str_replace(';[Red]', '@[Red]', $style);												//	Semikolon ersetzen für Parser, wird später rückgängig gemacht
		$styles = array();
		foreach(explode(';', $style) as $element) {
			$element = strtolower(str_replace('FORMAT_','', $element));
			switch(strtolower($element)) {
				//	Alignment & Wordwrap
				case 'left':							$element	=	'alignment-horizontal:left';																										break;
				case 'right':							$element	=	'alignment-horizontal:right';																										break;
				case 'center':						$element	=	'alignment-horizontal:center';																									break;
				case 'top':								$element	=	'alignment-vertical:top';																												break;
				case 'bottom':						$element	=	'alignment-vertical:bottom';																										break;
				case 'middle':						$element	=	'alignment-vertical:center';																										break;
				case 'wrap':							$element	=	'alignment-wrap:true';																													break;
				case '30':								$element	=	'alignment-rotation:30';																												break;
				case '45':								$element	=	'alignment-rotation:45';																												break;
				case '60':								$element	=	'alignment-rotation:60';																												break;
				case '90':								$element	=	'alignment-rotation:90';																												break;
				//	Font-Attributes
				case 'bold':							$element	=	'font-bold:true';																																break;
				case '-bold':							$element	=	'font-bold:false';																															break;
				case 'italic':						$element	=	'font-italic:true';																															break;
				case '-italic':						$element	=	'font-italic:false';																														break;
				case 'underline':					$element	=	'font-underline:true';																													break;
				case '-underline':				$element	=	'font-underline:false';
				//	Numberformat
				case 'number':						$element	=	'numberformat-code:#,##0;[Red]-#,##0|alignment-horizontal:right';								break;
				case 'number_':						$element	=	'numberformat-code:#,##0 ;[Red]-#,##0 |alignment-horizontal:right';							break;
				case 'number_0':					$element	=	'numberformat-code:#,##0.0;[Red]-#,##0.0|alignment-horizontal:right';						break;
				case 'number_0_':					$element	=	'numberformat-code:#,##0.0 ;[Red]-#,##0.0 |alignment-horizontal:right';					break;
				case 'number_00':					$element	=	'numberformat-code:#,##0.00;[Red]-#,##0.00|alignment-horizontal:right';					break;
				case 'number_00_':				$element	=	'numberformat-code:#,##0.00 ;[Red]-#,##0.00 |alignment-horizontal:right';				break;
				case 'number_000':				$element	=	'numberformat-code:#,##0.000;[Red]-#,##0.000|alignment-horizontal:right';				break;
				case 'number_000_':				$element	=	'numberformat-code:#,##0.000 ;[Red]-#,##0.000 |alignment-horizontal:right';			break;
				case 'number_0000':				$element	=	'numberformat-code:#,##0.0000;[Red]-#,##0.0000|alignment-horizontal:right';			break;
				case 'number_0000_':			$element	=	'numberformat-code:#,##0.0000 ;[Red]-#,##0.0000 |alignment-horizontal:right';		break;
				case 'number_zero':				$element	=	'numberformat-code:#,##0;[Red]-#,##0;|alignment-horizontal:right';							break;
				case 'number_zero_':			$element	=	'numberformat-code:#,##0 ;[Red]-#,##0 ; |alignment-horizontal:right';						break;
				case 'number_0_zero':			$element	=	'numberformat-code:#,##0.0;[Red]-#,##0.0;|alignment-horizontal:right';					break;
				case 'number_0_zero_':		$element	=	'numberformat-code:#,##0.0 ;[Red]-#,##0.0 ; |alignment-horizontal:right';				break;
				case 'number_00_zero':		$element	=	'numberformat-code:#,##0.00;[Red]-#,##0.00;|alignment-horizontal:right';				break;
				case 'number_00_zero_':		$element	=	'numberformat-code:#,##0.00 ;[Red]-#,##0.00 ; |alignment-horizontal:right';			break;
				case 'number_000_zero':		$element	=	'numberformat-code:#,##0.000;[Red]-#,##0.000;|alignment-horizontal:right';			break;
				case 'number_000_zero_':	$element	=	'numberformat-code:#,##0.000 ;[Red]-#,##0.000 ; |alignment-horizontal:right';		break;
				case 'number_0000_zero':	$element	=	'numberformat-code:#,##0.0000;[Red]-#,##0.0000;|alignment-horizontal:right';		break;
				case 'number_0000_zero_':	$element	=	'numberformat-code:#,##0.0000 ;[Red]-#,##0.0000 ; |alignment-horizontal:right';	break;
				case 'number_thousand': 	$element = 'numberformat-code:#,##0,[Red]-#,##0,|alignment-horizontal:right';								break;
				case 'number_thousand_zero': $element = 'numberformat-code:#,##0,;[Red]-#,##0,; |alignment-horizontal:right';					break;
				case 'percent':						$element	=	'numberformat-code:0%;[Red]-0%|alignment-horizontal:right';											break;
				case 'percent_':					$element	=	'numberformat-code:0% ;[Red]-0% |alignment-horizontal:right';										break;
				case 'percent_0':					$element	=	'numberformat-code:0.0%;[Red]-0.0%|alignment-horizontal:right';									break;
				case 'percent_0_':				$element	=	'numberformat-code:0.0% ;[Red]-0.0% |alignment-horizontal:right';								break;
				case 'percent_00':				$element	=	'numberformat-code:0.00%;[Red]-0.00%|alignment-horizontal:right';								break;
				case 'percent_00_':				$element	=	'numberformat-code:0.00% ;[Red]-0.00% |alignment-horizontal:right';							break;
				case 'percent_000':				$element	=	'numberformat-code:0.000%;[Red]-0.000%|alignment-horizontal:right';							break;
				case 'percent_000_':			$element	=	'numberformat-code:0.000% ;[Red]-0.000% |alignment-horizontal:right';						break;
				case 'percent_zero':			$element	=	'numberformat-code:0%;[Red]-0%;|alignment-horizontal:right';										break;
				case 'percent_zero_':			$element	=	'numberformat-code:0% ;[Red]-0% ; |alignment-horizontal:right';									break;
				case 'percent_0_zero':		$element	=	'numberformat-code:0.0%;[Red]-0.0%;|alignment-horizontal:right';								break;
				case 'percent_0_zero_':		$element	=	'numberformat-code:0.0% ;[Red]-0.0% ; |alignment-horizontal:right';							break;
				case 'percent_00_zero':		$element	=	'numberformat-code:0.00%;[Red]-0.00%;|alignment-horizontal:right';							break;
				case 'percent_00_zero_':	$element	=	'numberformat-code:0.00% ;[Red]-0.00% ; |alignment-horizontal:right';						break;
				case 'percent_000_zero':	$element	=	'numberformat-code:0.000%;[Red]-0.000%;|alignment-horizontal:right';						break;
				case 'percent_000_zero_':	$element	=	'numberformat-code:0.000% ;[Red]-0.000% ; |alignment-horizontal:right';					break;
				case 'year':							$element	=	'numberformat-code:#;[Red]-#';																									break;
				case 'year_zero':					$element	=	'numberformat-code:#;[Red]-#;';																									break;
				case 'text':							$element	=	'numberformat-code:@';																													break;
				case 'date':							$element	=	'numberformat-code:DD.MM.YYYY';																									break;
				case 'dateshort':					$element	=	'numberformat-code:YYYY-MM';																										break;
				//	Colors
				case 'black':							$element	=	'fill-type:solid|fill-color-rgb:000000|font-color-rgb:ffffff';									break;
				case 'green':							$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['green'];													break;
				case 'lightgreen':				$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['lightgreen'];											break;
				case 'darkgreen':					$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['darkgreen'];											break;
				case 'grey':							$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['grey'];														break;
				case 'lightgrey':					$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['lightgrey'];											break;
				case 'red':								$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['red'];														break;
				case 'lightred':					$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['lightred'];												break;
				case 'yellow':						$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['yellow'];													break;
				case 'lightyellow':				$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['yellow'];													break;
				case 'darkyellow':				$element	=	'fill-type:solid|fill-color-rgb:'.$this->rgb['yellow'];													break;
				// Font Colors
				case 'font_white':        $element  = 'font-color-rgb:ffffff';																										break;
				case 'font_blue':        	$element  = 'font-color-rgb:0000ff';																										break;
				case 'font_grey':        	$element  = 'font-color-rgb:888888';																										break;
				//	Borders
				case 'bordertop':					$element	=	'borders-top-style:thin';																												break;
				case 'borderbottom':			$element	=	'borders-bottom-style:thin';																										break;
				case 'borderleft':				$element	=	'borders-left-style:thin';																											break;
				case 'borderright':				$element	=	'borders-right-style:thin';																											break;
				case 'bordertop_hair':		$element	=	'borders-top-style:hair';																												break;
				case 'borderbottom_hair':	$element	=	'borders-bottom-style:hair';																										break;
				case 'borderleft_hair':		$element	=	'borders-left-style:hair';																											break;
				case 'borderright_hair':	$element	=	'borders-right-style:hair';																											break;


				//BFW
				case 'bfwstandardfont':   $element  = 'font-name:Arial|font-size:8';																								break;
				case 'bfwheaderfont':   	$element  = 'font-name:Arial|font-size:10';																								break;
				case 'bfwblue': 					$element  = 'fill-type:solid|fill-color-rgb:110067|font-color-rgb:ffffff';									break;


			}
			$substyles = explode('|',$element);
			foreach($substyles as $substyle) {
				$styleArr = explode(':', $substyle);
				$attr			= explode('-', $styleArr[0]);
				$arr 			= array();
				switch(count($attr)) {
					case 2: $arr[$attr[1]]				 							= $styleArr[1]; break;
					case 3: $arr[$attr[1]][$attr[2]] 						= $styleArr[1]; break;
					case 4: $arr[$attr[1]][$attr[2]][$attr[3]] 	= $styleArr[1]; break;
				}
				if(is_array($styles[$attr[0]])) {
					$styles[$attr[0]] = Util::array_merge_recursive_distinct($styles[$attr[0]], $arr);
				} else {
					$styles[$attr[0]] = str_replace('@[Red]', ';[Red]', $arr);		//	Rückformatierung der Semikolon
				}
			}

		}
		return $styles;
	}

}