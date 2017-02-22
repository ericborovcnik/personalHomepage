<?php
/**
 * Diese Klasse dient der Begleitung und Protokollierung von Prozessen während des Ablaufs.
 * Sie zeichnet Status-, Protokoll-, Fehler- und Warungsinformationen auf und bietet Methoden
 * zur Protokollgestaltung. Sie dient auch als Schnittstelle zur Dialgoführung via app.log
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	Copyright (c) WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-14	eb	from revis Core_Util_Log
 */
class Util_Log {
	
	public $indent;												//	Einrücksungscounter für die Protokolle

	/**
	 * @var Util_State $_state						Status-Objekt
	 */
	private $_state;
	private $_logfile;										// Protokoll-Datei
	private $_title;
	private $_cancelfile;									//	Cancel-Flag-Datei
	
	const LOGINFOTAG = '@INFO:';
	const LOGERRORTAG = '@ERROR:';
	const LOGWARNINGTAG= '@WARNING:';	

	/**
	 * Initialisiert ein neues Protokoll
	 * @param string $dir									Verzeichnis für die Protokoll-Ablage
	 * @param string $title								Protokoll-Titel
	 * @param boolean $debug							true spiegelt die Protokoll-Meldungen ins debug.log
	 */
	public function __construct($dir='', $title='', $debug=false) {
		if(!$dir)				$dir = User::getDir('user');
 		if(substr($dir, -1) != '/')			$dir.='/';
 		$this->_title = $title;
 		if(!file_exists($dir)) {
 			try {
 				mkdir($dir, '0775', true);
 			} catch(Exception $e) {
 				Log::err($e->getMessage());
 			}
 		}
		$this->_logfile = $dir.'.log';
		$this->_cancelfile = $dir.'.cancel';
		$this->_state = new Util_State($dir);
		$this->indent = 0;
		if(!file_exists($this->_logfile)) 			$this->reset();
	}

	public function active() {
		$this->canceled();
		return $this->_state->active();
	}
	
	/**
	 * Fügt eine Textzeile dem Log hinzu
	 * @param	string	$msg								Protokollmeldung
	 */
	public function add($msg='') {
		if($this->indent>=0)	$msg = str_repeat(' ', $this->indent*2).$msg;
		$this->_writeLog($msg);
	}

	/**
	 * Fügt eine Fehlermeldung dem Log hinzu
	 * @param string $msg									Fehlermeldung
	 */
	public function addError($msg='') {
		$this->_state->addError($msg);
		if($this->indent>=0)	$msg = str_repeat(' ', $this->indent*2).$msg;
		$this->_writeLog(self::LOGERRORTAG.$msg);
	}
	
	/**
	 * Fügt eine Info-Meldung dem Log hinzu
	 * @param string $msg									Info-Meldung
	 */
	public function addInfo($msg='') {
		if($this->indent>=0)	$msg = str_repeat(' ', $this->indent*2).$msg;
		$this->_writeLog(self::LOGINFOTAG.$msg);
	}
	
	/**
	 * Fügt eine Sektion dem Protokoll hinzu
	 * @param string $section							Titel des Abschnitts
	 * @param number $level								Steuert den Level 1..4 für die Darstellung von 4 Gliederungsstufen
	 */
	public function addSection($section, $level=1) {
		switch($level) {
			case 1:		$maxchar = 100;	$char = '=';		break;
			case 2:		$maxchar = 80;	$char = '-';		break;
			case 3:		$maxchar = 60;	$char = '*';		break;
			case 4:		$maxchar = 40;	$char = '•';		break;
			default:	$this->_writeLog($section);			return;
		}
		$divider = str_repeat($char, $maxchar);
		$this->_writeLog($divider);
		$this->_writeLog(str_pad($section, $maxchar,' ', STR_PAD_BOTH));
		$this->_writeLog($divider);
	}

	/**
	 * Erzeugt eine Warnmeldung
	 * @param string $msg									Warnmeldung
	 */
	public function addWarning($msg) {
		$this->_state->addWarning($msg);
		if($this->indent>=0)	$msg = str_repeat(' ', $this->indent*2).$msg;
		$this->_writeLog(self::LOGWARNINGTAG.$msg);
	}
	
	/**
	 * Hinterlegt eine Abbruch-Signaldatei
	 */
	public function cancel() {
		Log::out($this->_cancelfile);
		$fh = fopen($this->_cancelfile,'a');
		fclose($fh);
	}
	
	public function canceled() {
		if(file_exists($this->_cancelfile) || $this->_state->canceled()) {
			$this->_state->cancel();
			return true;
		}
		return false;
	}
	
	/**
	 * Initialisert/Inkrementiert die Haupt-Iterationsschritte
	 * @param integer $total							Total Anzahl Haupt-Iterationsschritte
	 */
	public function counter($total=false) {
		$this->canceled();
		$this->_state->counter($total);
	}
	
	/**
	 * Initialisiert/Inkrementiert die Teil-Iterationsschritte
	 * @param integer $total							Total Teil-Iterationsschritte
	 */
	public function counterPart($total=false) {
		$this->canceled();
		$this->_state->counterPart($total);
	}
	
	/**
	 * Initialisiert/Inkrementiert die Sub-Iterationsschritte
	 * @param integer $total							Total Sub-Iterationsschritte
	 */
	public function counterSub($total=false) {
		$this->canceled();
		$this->_state->counterSub($total);
	}
		
		/**
		 * Ermittelt Status- und Protokoll-Informationen in Json-Notation
		 * Das Protokoll wird auf die letzten 100 Einträge reduziert, sofern der Prozess aktiv ist
		 * @return json												state, log
		 */
	/**
	 * Ermittelt Status und Protokoll in Json-Notation.
	 * Das Protokoll wird auf die letzten 100 Einträge gekürzt, sofern der Prozess aktiv ist.
	 * @return json													state, log
	 */
	public function getJson() {
		$log = explode("\n", file_get_contents($this->_logfile));
		if($this->active() && count($log)>100) {
			array_splice($log, 0, count($log) - 100);
			array_unshift($log, '...');
		}
		$state = $this->_state->getData();
		return Util::jsonSuccess(array(
			'log'		=>	$log,
			'state'	=>	$state
		));
	}
	
	/**
	 * Ermittelt den Wert einer Statusvariablen
	 * @param string $var									Statusvariable
	 * @return value
	 */
	public function getState($var) {
		return $this->_state->get($var);
	}
	
	/**
	 * Re-Initialisiert Protokoll und Status
	 * @param array $statevars						Zusätzliche Status-Variablen
	 */
	public function reset($statevars = array()) {
		$this->_state->reset(array_merge(array(
			'title'		=>		$this->_title
		), $statevars));
		if(file_exists($this->_logfile))				@unlink($this->_logfile);
		if(file_exists($this->_cancelfile))			@unlink($this->_cancelfile);
		$this->_writeLog(false);
	}
	

	/**
	 * Beendet das Protokoll, setzt den Status auf inaktiv und aktualisiert die Duration
	 */
	public function stop() {
		$this->_state->stop();
	}
	

	/**
	 * Fügt dem Log eine Meldung hinzu und aktualisiert den Status
	 * @param string $msg
	 */
	private function _writeLog($msg='') {
		$this->canceled();
		$this->_state->set('lifetimer', microtime(true));
		$fh = fopen($this->_logfile, 'a');
		if($msg !== false)		fwrite($fh, $msg."\n");
		fclose($fh);
	}

}