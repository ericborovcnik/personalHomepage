<?php
/**
 * Dieses Modul verarbeitet die über den Import-Prozess bereitgestellten Dateien
 * und begleitet die Analyse und Importschritte
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-28
 */
class IO_Import extends IO_Base {
	
	/**
	 * Ermittelt das Protokoll des aktuellen Import-Prozesses für den aktuellen Kunden
	 * 
	 */
	public function getLog() {
		
		$log = new Util_Log(User::getDir('customer'));
		Log::out(User::getCustomerId());
		return $log->getJson();
	}
	
}