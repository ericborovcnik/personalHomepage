<?php
/**
 * Generische IO-Klasse
 * Stellt Universelle IO-Konstanten und Methoden zur Verfügung.
 * Alle IO-Klassen müssen zwingend von dieser Factory-Klasse abgeleitet werden
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-21-07	eb	from scratch
 * @version		2016-06-15	eb	jsonConfirm
 */
abstract class IO_Base {
	
	const CHECK_LOGIN = true;
	const ACCESS = '';

	public $params;												//	stdclass mit den IO-Parametern

	private $_errorFields;								//	Puffer hält fehlerhafte Felder fest
	private $_errorMsg;										//	Puffer fasst Fehlemeldungen zusammen
	private $_warningMsg;									//	Puffer fasst Warnungen zusammen
	
	/**
	 * Konstruktor
	 * @param stdClass $params						IO-Parameter							
	 */
	public function __construct($params) {
		$this->_errorFields = array();
		$this->_errorMsg= '';
		$this->params = $params;
	}
	
	/**
	 * Registriert einen Fehler und ggfs. Fehler-Quellfelder
	 * @param string $msg									Fehlermeldung
	 * @param string|array $fields				Feld-Id oder Array mit Feld-Ids
	 */
	public function addError($msg='', $fields=null) {
		if($this->_errorMsg && $msg)		$this->_errorMsg .= '|';
		if($msg)												$this->_errorMsg .= $msg;
		if($fields) {
			if(is_string($fields))				$this->_errorFields[$fields] = $fields;
			if(is_array($fields)) {
				foreach($fields as $field) {
					$this->_errorFields[$field] = $fields;
				}
			}
		}
	}
	
	/**
	 * Registriert eine Meldung als Warnung
	 * @param string $msg
	 */
	public function addWarning($msg) {
		if($this->_warningMsg && $msg)		$this->_warningMsg .= '|';
		if(msg)														$this->_warningMsg .= $msg;
	}
	
	/**
	 * True, wenn Fehler registriert wurden
	 */
	public function hasError() {
		return $this->_errorFields || $this->_errorMsg;
	}
	
	/**
	 * Erzeugt eine Antwortstruktur, bei gleichzeitigem produzieren eines Fehlers
	 * @param string $error
	 * @param mixed $fields
	 */
	public function jsonError($error, $fields=null) {
		$this->addError($error, $fields);
		return $this->jsonResponse();
	}
	
	/**
	 * Erzeugt eine 'Zugriff verweigert' Rückmeldung
	 * @param string $msg									Optionaler Meldungstext
	 */
	public function jsonNoAccess($msg='') {
		$this->addError(Language::get('Access denied'));
		return $this->jsonResponse($msg);
	}
	
	/**
	 * Erzeugt eine Standard-JSON-Antwort
	 * Liegen Fehlermeldungen vor, dann wird eine Errormeldung aufbereitet; ansonsten ein OK-Meldung
	 * @param string|array $msg									Optionale Meldung
	 */
	public function jsonResponse($msg='') {
		$data = array();
		$data['success'] = true;
		$data['error'] = '';
		if($msg) {
			if(is_string($msg)) {
				$data['msg'] = $msg;
			}
			if(is_array($msg)) {
				$data = array_merge($data, $msg);
			}
		}
		if($this->_errorFields) {
			$data['fields'] = $this->_errorFields;
			$data['success'] = false;
		}
		if($this->_errorMsg) {
			$data['error'] = $this->_errorMsg;
			$data['success'] = false;
		}
		if($this->_warningMsg) {
			$data['warning'] = $this->_warningMsg;
		}
		return Zend_Json::encode($data);
	}
	
	/**
	 * Ermittelt eine Norm-Json-Antowrt für Rückfragen
	 * @param	string	$confirmMessage			Bestätigungsmeldung
	 * @return	json											Bestätigungsanfrage
	 */
	public function jsonConfirm($confirmMessage='') {
		return $this->jsonResponse(array(
			'confirm'		=>	true,
			'msg'				=>	$confirmMessage
		));
	}
	
	/**
	 * Generische run-Methode.
	 */
	public function run() {
		return $this->jsonResponse();
	}
	
	/**
	 * Short-Translation
	 * @param unknown $key
	 * @param unknown $subst
	 * @return unknown
	 * @todo implement
	 */
	public function _($key, $subst=null) {
		return Language::get($key, $subst);
	}
	
}