<?php
/**
 * Eine Statusdatei enthält eine NULL-delimitierte Aufstellung von Einträgen in der Syntax Variable=Wert.
 * Sie wird mit einigen Standard-Variablen initialisiert und kann beliebig angereichert werden.
 * Die Statusdatei erhält mit dem Konstruktor einen Dateiverweis.
 */
 class Util_State {
 	
 	private $_file;												//	Statusdatei
 	private $_errorfile;									//	Fehlermeldungen
 	private $_warningfile;								//	Warnungen
 	private $_data;												//	Array mit Status-Variablen
 	private $_copy;												//	Array mit einer Kopie der Status-Variablen
 	private $_lastWrite = 0;							//	Zeitstempel mit dem letzten Schreibvorgang
 	
 	private $_progressCount = 0;					//	Anzahl erledigter Haupt-Iterationschritte
 	private $_progressTotal = 0;					//	Totale Anzahl Haupt-Iterationsschritte
 	private $_progressPartCount = 0;			//	Anzahl erledigter Teil-Iterationsschritte
 	private $_progressPartTotal = 0;			//	Totale Anzahl Teil-Iterationsschritte
 	private $_progressSubCount = 0;				//	Anzahl erledigter Sub-Iterationsschritte
 	private $_progressSubTotal = 0;				//	Totale Anzahl Sub-Iterationsschritte
 	
 	/**
 	 * Erstellt/Liesst die Statusdatei
 	 * @param string $dir									//	Verzeichnis, wo die Statusdatei angelegt werden soll
 	 */
 	public function __construct($dir='') {
 		if(!$dir)				$dir = User::getDir('user');
 		if(substr($dir, -1) != '/') $dir.= '/';
 		if(!file_exists($dir)) {
 			try {
 				mkdir($dir, '0775', true);
 			} catch(Exception $e) {
 				Log::err($e->getMessage());
 			}
 		} 		
 		$this->_file = $dir.'.state';
 		$this->_errorfile = $dir.'.error';
 		$this->_warningfile = $dir.'.warning';
 		if(file_exists($this->_file)) {
 			$this->_readState();
 		} else {
 			$this->reset();
 		}
 	}
 	
 	/**
 	 * Inkrementiert den Fehler-Counter und fügt die Meldung der Fehlerliste an
 	 * @param string $errorMessage				Fehlermeldung
 	 */
 	public function addError($errorMessage='') {
 		$this->set('errors', $this->get('errors')+1);
 		if($errorMessage == '')	return;
 		$fh = fopen($this->_errorfile, 'a');
 		fwrite($fh, Date::get('now', 'Y-m-d H:i:s').' : '.$errorMessage."\n");
 		fclose($fh);
 	}
 	
 	/**
 	 * Inkrementiert den Warnung-Counter und fügt die Meldung der Warnungsliste an.
 	 * @param string $warningMessage			Warnmeldung
 	 */
 	public function addWarning($warningMessage='') {
 		$this->set('warnings', $this->get('warnings')+1);
 		if($warningMessage == '')	return;
 		$fh = fopen($this->_warningfile, 'a');
 		fwrite($fh, Date::get('now', 'Y-m-d H:i:s').' : '.$warningMessage."\n");
 		fclose($fh);
 	}
 	
 	/**
 	 * Steuert die Haupt-Iterationsschritte oder inkrementiert den Counter
 	 * Ist $total gesetzt, dann wird der Counter initialisiert; ansonsten inkrementiert
 	 * @param integer $total							Totale Anzahl Haupt-Iterationsschritte
 	 */
 	public function counter($total=false) {
 		$this->_progressPartCount = 0;
 		$this->_progressPartTotal = 0;
 		$this->_progressSubCount = 0;
 		$this->_progressSubTotal = 0;
 		if($total == false) {
 			$this->_progressCount++;
 		} else {
 			$this->_progressTotal = $total;
 			$this->_progressCount = 0;
 		}
 		$this->_writeState();
 	}
 	
 	/**
 	 * Steuert die Teil-Iterationsschritte oder inkrementiert den Counter
 	 * Ist $total gesetzt, dann wird der Counter initialisiert; ansonsten inkrementiert
 	 * @param integer $total							Totale Anzahl Teil-Iterationsschritte 
 	 */
 	public function counterPart($total=false) {
 		$this->_progressSubCount = 0;
 		$this->_progressSubTotal = 0;
 		if($total == false) {
 			$this->_progressPartCount++;
 		} else {
 			$this->_progressPartTotal = $total;
 			$this->_progressPartCount = 0;
 		}
 		$this->_writeState();
 	}
 	
 	/**
 	 * Steuert die Sub-Iterationsschritte oder inkrementiert den Counter
 	 * Ist $total gesetzt, dann wird der Counter initialisiert; ansonsten inkrementiert
 	 * @param integer $total							Totale Anzahl Sub-Iterationsschritte
 	 */
 	public function counterSub($total=false) {
 		if($total == false) {
 			$this->_progressSubCount++;
 		} else {
 			$this->_progressSubTotal = $total;
 			$this->_progressSubCount = 0;
 		}
 		$this->_writeState();
 	}
 	
 	/**
 	 * Ermittelt den Inhalt einer Statusvariablen
 	 * @param string $variable						Statusvariable
 	 * @return mixed 
 	 */
 	public function get($variable) {
 		return $this->_data[$variable];
 	}
 	
 	/**
 	 * Ermittelt den Inhalt einer Statusvariablen als Boolean
 	 * @param string $variable						Statusvariable
 	 * @return boolean
 	 */
 	public function getBool($variable) {
 		return Util::toBool($this->_data[$variable]);
 	}
 	
 	/**
 	 * Ermittelt die gesamte Status-Struktur.
 	 * Die Struktur wird im Hinblick auf duration, memory und activestring angereichert
 	 */
 	public function getData() {
 		$data = $this->_data;
 		if($this->active()) {
 			$data['duration'] = Util::lifetime(microtime(true) - $data['starttimer']);
 			$data['memory'] = Util::getMemoryUsage(true);
 		}
 		/*
 		 * Status-String
 		 */
 		if($this->active()) {
 			if(microtime(true) - $data['lifetimer'] > 60) {
 				$active = 'Missing';
 			} else {
 				$active = 'Active';
 			}
 		} else {
 			if($this->canceled()) {
 				$active = 'Canceled';
 			} else if($data['starttimer'] == null) {
 				$active = "Inactive";
 			} else {
 				$active = 'Finished';
 			}
 		}
 		$data['status'] = Language::get($active);
 		return $data;
 	}
 	
 	/**
 	 * Re-Initialisiert den Status.
 	 * @param array $statevars						Zusätzliche Status-Variablen
 	 */
 	public function reset($statevars=array()) {
 		$this->_lastWrite = 0;
 		if(file_exists($this->_file))					@unlink($this->_file);
 		if(file_exists($this->_errorfile))		@unlink($this->_errorfile);
 		if(file_exists($this->_warningfile))	@unlink($this->_warningfile);
 		$this->_data = array_merge($statevars, array(
 			'active'				=>	false,
 			'errors'				=>	0,
 			'warnings'			=>	0,
 			'canceled'			=>	false,
 			'starttimer'		=>	null,
 			'lifetimer'			=>	null,
 			'userid'				=>	User::getId(),
 			'username'			=>	User::getUser()->getLoginname(),
 			'userfullname'	=>	User::getUser()->getUsername(),
 			'memory'				=>	Util::getMemoryUsage(true)
 		));
 		$this->_copy = $this->_data;
 		$this->_writeState();
 	}
 	
 	/**
 	 * Setzt eine Status-Variable
 	 * @param string $variable						Statusvariable
 	 * @param mixed $value								Variablen-Inhalt
 	 */
 	public function set($variable, $value) {
 		$this->_data[$variable] = $value;
 		$this->_writeState();
 	}
 	
 	/**
 	 * Beendet den Prozess, indem die active-Variable auf false und die cancel-Variable auf true gesetzt wird.
 	 */
 	public function cancel() {
 		$this->set('canceled', true);
 		$this->set('active', false);
 	}
 	
 	/**
 	 * Ermittelt den Status der active-Variablen
 	 */
 	public function active() {
 		return $this->getBool('active');
 	}
 	
 	/**
 	 * Ermittelt den Status der canceled-Variablen
 	 */
 	public function canceled() {
 		return $this->getBool('canceled');
 	}
 	
 	/**
 	 * Aktiviert den Prozess und setzt die active, und starttimer-Variable
 	 */
 	public function start() {
		$this->_data['starttimer'] = microtime(true);
		$this->_data['active'] = true;
		$this->_writeState();
 	}
 	
 	/**
 	 * Beendet den Prozess, inde die active-Variable auf false gesetzt wird
 	 */
 	public function stop() {
 		$this->writeState();
 		$this->set('duration', Util::lifetime(microtime(true) - $this->get('starttimer')));
 		$this->set('active', false);
 	}
 	
 	/**
 	 * Schreibt/aktualisiert die Status-Informationen
 	 * Das Schreiben wird verzögert, wenn sich der Aktiv-/Cancel-Status nicht verändert hat,
 	 * und die letzte Statusaktualisierung vor kleiner 1s war.
 	 */
 	private function _writeState() {
 		// Ignoriere Status-Write?
 		if($this->_data['active'] == $this->_copy['active']
 			&& $this->_data['canceled'] == $this->_copy['canceled']
 			&& microtime(true) - $this->_lastWrite < 1) {
 			return;
 		}
 		if($this->_data['active']) {
 			$this->_data['duration'] = Util::lifetime(microtime(true) - $this->get('starttimer')); 
 		}
 		$this->_data['count'] = $this->_progressCount;
 		$this->_data['total'] = $this->_progressTotal;
 		$this->_data['progress'] = $this->_calcProgress();
 		$this->_data['lifetimer'] = microtime(true);
 		$this->_data['memory'] = Util::getMemoryUsage(true);
 		$fh = fopen($this->_file, 'w');
 		foreach($this->_data as $key => $value) {
 			fwrite($fh, $key.'='.$value."\n");
 		}
 		fclose($fh);
 		$this->_copy = $this->_data;
 		$this->_lastWrite = microtime(true);
 	}

 	/**
 	 * Liesst die Inhalte der Statusdatei und bestückt die Variablen
 	 */
 	private function _readState() {
 		$this->_lastWrite = 0;
 		$this->_data = array();
 		$content = explode("\n", file_get_contents($this->_file));
 		foreach($content as $item) {
 			$varName = Util::varName($item);
 			if($varName)	$this->_data[$varName] = Util::varValue($item);
 		}
 	}
 	
 	/**
 	 * Berechnet den Fortschrittsgrad aufgrund Haupt-, Teil- und Sub-Iterationsshritte
 	 * @return float											Prozent zwischen 0 und 1
 	 */
	private function _calcProgress() {
		if($this->_progressTotal == 0)		return 0;
		$main = 1 / $this->_progressTotal;
		$progress = $main * $this->_progressCount;
		if($this->_progressPartTotal == 0) {
			/*
			 * Kein Part-Counter => Sub-Counter
			 */
			if($this->_progressSubTotal == 0)		return $progress;
			$part = $main / $this->_progressSubTotal;
			$progress += $part * $this->_progressSubCount;
			return $progress;
		}
		$part = $main / $this->_progressPartTotal;
		$progress += $part * $this->_progressPartCount;
		if($this->_progressSubTotal == 0)	return $progress;
		$sub = $part / $this->_progressSubTotal;
		$progress += $sub * $this->_progressSubCount;
		return $progress;
	}
	
 }