<?php

/**
 * Stellt die Modellklasse für Messenger-Fehler dar.
 * @author		Mike Ladurner <mike.ladurner@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-22	ml	from scratch
 */
class Model_MessengerError {

	// Private Variabeln
	private $dateString = null;
	private $errorMessage = null;
	private $userName = null;
	private $userId = null;

	/**
	 * Konstruktor des MessengerError-Objekts.
	 * @param string 		$dateString			Datumsstring im Format 2016-01-01
	 * @param integer 	$userId					ID des Users
	 * @param string 		$userName				Name des Users
	 * @param string 		$errorMessage		Fehlermeldung
	 */
	public function __construct($dateString, $userId, $userName, $errorMessage){
		$this->setDateString($dateString);
		$this->setUserId($userId);
		$this->setUserName($userName);
		$this->setErrorMessage($errorMessage);
		$this->getLogText(true);
	}

	/**
	 * Setzt den Datumsstring.
	 * @param string 		$dateString			Datumsstring im Format 2016-01-01
	 */
	public function setDateString($dateString){
		if(strlen($dateString != 10)) return ;
		$this->dateString = $dateString;
	}

	/**
	 * Setzt die Fehlermeldung.
	 * @param string 		$errorMessage		Fehlermeldung
	 */
	public function setErrorMessage($errorMessage){
		$this->errorMessage = $errorMessage;
	}

	/**
	 * Setzt den Usernamen.
	 * @param string 		$userName				Name des Users
	 */
	public function setUserName($userName){
		$this->userName = $userName;
	}

	/**
	 * Setzt die UserID.
	 * @param integer 	$userId					ID des Users
	 */
	public function setUserId($userId){
		$this->userId = $userId;
	}

	/**
	 * Gibt den Datumsstring zurück.
	 * @return string
	 */
	public function getDateString(){
		return $this->dateString;
	}

	/**
	 * Gibt die Fehlermeldung zurück.
	 * @return string
	 */
	public function getErrorMessage(){
		return $this->errorMessage;
	}

	/**
	 * Gibt den Benutzernamen zurück.
	 * @return string
	 */
	public function getUserName(){
		return $this->userName;
	}

	/**
	 * Gibt die Benutzer ID zurück.
	 * @return integer
	 */
	public function getUserId(){
		return $this->userId;
	}

	/**
	 * Gibt einen formatierten Text für den Log zurück.
	 * @return string
	 */
	public function getLogText($write = true){
		$text = "MESSENGER ERROR - ".$this->getDateString()." - ".$this->getErrorMessage()." \n UserID: ".$this->getUserId()." \n UserName: ".$this->getUserName();
		Log::err($text);
		return $text;
	}
}