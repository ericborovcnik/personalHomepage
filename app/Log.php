<?php
/**
 * Protokoll-Klasse
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-11-26	eb	from scratch
 * @version		2016-02-17	cz	cout()
 *
 * @outline
 * Log::info(message [,trace?])					Protokolliert eine Information
 * Log::err(message [,trace?])					Protokolliert einen Fehler
 * Log::debug(message [,trace?])				Protokolliert eine Meldung, wenn APP_DEBUG=true ist.
 * Log::cout(message [,trace?])					Protokolliert eine Meldung auf stdOut (Konsole)
 */
abstract class Log {

	/**
	 * @var Zend_Log $_logger
	 */
	private static $_logger;
	private static $_errlogger;

	/*
	 * Public Methods
	 */

	/**
	 * Erzeugt eine Protokollausgabe
	 * @param mixed $message							Protokollmeldung als String, Array oder Object
	 * @param boolean $trace							true erzeugt die Aufrufliste für das Backtracing
	 */
	public static function out($message, $trace=false) {
		self::_init();
		if($trace) 			self::$_logger->info(self::_getTrace());
		self::$_logger->info(Util::dump($message, 0, false));
	}

	/**
	 * Erzeugt eine Fehlermeldung in das Protokoll
	 * @param mixed $message							Protokollmeldung als String, Array, oder Object
	 * @param mixed $message2							2. Meldung (falls in einer ersten Meldung ein Textzeile und in der zweiten eine Struktur übermittelt werden soll)
	 */
	public static function err($message, $message2=null) {
		self::_init();
		//
		//	Ausgabe der Fehlermeldung ins debug.log
		self::$_logger->err(Date::get('today').' ERROR: '.self::_getTrace());
		self::$_logger->err(Util::dump($message, 0, false));
		if($message2)			self::$_logger->err(Util::dump($message2, 0, false));
		self::$_logger->err(str_repeat('=',100));
		//
		//	Ausgabe der Fehlermeldung ins error.log
		self::$_errlogger->err(Date::get('today').' ERROR: '.self::_getTrace());
		self::$_errlogger->err(Util::dump($message, 0, false));
		if($message2)			self::$_errlogger->err(Util::dump($message2, 0, false));
		self::$_errlogger->err(str_repeat('=',100));

	}

	/**
	 * Erzeugt eine Protokollausgabe, sofern APP_DEBUG gesetzt ist
	 * @param mixed $message
	 * @param boolean $trace
	 */
	public static function debug($message, $trace=false) {
		if(APP_DEBUG)		self::out($message, $trace);
	}

	/**
	 * Erzeugt eine Ausgabe auf stdOut (z.B. beim BackgroundWorkerTest)
	 * @param mixed $message
	 * @param bool $trace
	 */
	public static function cout($message, $trace = false) {
		if($trace) {
			print self::_getTrace();
			print "\n";
		}
		print(Util::dump($message, 0, false));
		print "\n";
		ob_flush();
	}

	/*
	 * Private Methods
	 */

	/**
	 * Initialisiert den Logger
	 */
	private static function _init() {
		if(is_object(self::$_logger))				return;
		//
		//	debug.log aufbereiten
		$writer = new Zend_Log_Writer_Stream(APP_BASE_PATH.'debug.log');
		$formatter = new Zend_Log_Formatter_Simple('%message%'.PHP_EOL);
		$writer->setFormatter($formatter);
		self::$_logger = new Zend_Log($writer);
		self::$_logger->setTimestampFormat('');
		//
		//	error.log aufbereiten
		$writer = new Zend_Log_Writer_Stream(APP_BASE_PATH.'error.log');
		$writer->setFormatter($formatter);
		self::$_errlogger = new Zend_Log($writer);
		self::$_errlogger->setTimestampFormat('');
	}

	/**
	 * Ermittelt den Backtrace-String
	 */
	private static function _getTrace() {
		$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$line = $bt[1]['line'];
		for($i=1;$i<=2;$i++) {
			array_shift($bt);
		}
		$first = true;
		$trace = '';
		foreach($bt as $item) {
			if(!$first) 		$trace .= ' <<< ';
			$trace .= $item['class'].$item['type'].$item['function'].'('.$line.')';
			$line = $item['line'];
			$first = false;
		}
		return $trace;
	}

}