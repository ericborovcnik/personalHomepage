<?php
/**
 * Datenbankadapter
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-11-27	eb	from scratch
 * @version		2015-12-17	eb	Core_Db to Db; getDb erhält optional eine DB-Suffix (CustomerId)
 * @version		2015-12-21	eb	Model_CreateDatabase-Delegation
 * @version		2016-01-16	eb	fetchOne, query, quote
 * @version		2016-02-08	cz	bind für query und fetchOne
 * @version		2016-02-17	cz	Transaktionssteuerung (für $db)
 * @version		2016-05-23	eb	getCustomerDb
 *
 * @outline
 * fetchOne()									Ermittelt einen Einzelwert ab Anwendungs-DB
 * getDb()										Vermittelt den Datenbank-Adapter zur Anwendungs-DB
 * getCustomerDb()						Vermittelt den Datenbank-Adapter zur Kunden-DB
 * initDb()										Öffnet den Anwendungs-DB-Adapter und initialisiert diesen ggfs.
 * query()										Führt auf der Anwendungs-DB einen SQL-Anfrage durch
 * quote()										Erstellt gequoterte Sequenzen
 * startTransaction()					Beginnt eine Transaktion
 * commitTransaction()				Bestätigt eine Transaktion
 * rollbackTransaction()			Bricht eine Transaktion ab
 * hasTransaction()						Befindet man sich innerhalb einer Transaktion?
 *
 */
abstract class Db {

	/**
	 * @var Zend_Db_Adapter_Abstract $db
	 */
	private static $db;

	/**
	 * @var Zend_Db_Adapter_Abstract $custDb;
	 */
	private static $customerDb;
	
	private static $customerIdent;				//	CustomerIdent zum aktuellen customerDb-Pointer
	
	private static $cashTables = array();
	
	/*
	 * Public Methods
	 */

	/**
	 * Developer-Methode - macht zu Testzwecken die gesamten Systemtabellen platt
	 */
	public static function dropTables() {
		Db::query('set foreign_key_checks=0');
		Db::query('drop table if exists language');
		Db::query('drop table if exists module');
		Db::query('drop table if exists schema_info');
		Db::query('drop table if exists session');
		Db::query('drop table if exists user');
		Db::query('drop table if exists access');
		Db::query('drop table if exists accessobject');
		Db::query('drop table if exists usergroup');
		Db::query('drop table if exists userlog');
		Db::query('drop table if exists userparam');
		Db::query('set foreign_key_checks=1');
	}

	/**
	 * Ermittelt einen Einzelwert ab SQL-Query
	 * @param string $query
	 * @return string
	 */
	public static function fetchOne($query, $bind = array()) {
		return Db::getDb()->fetchOne($query, $bind);
	}

	/**
	 * Ermittelt den Datenbank-Adapter der Stamm-Datenbank
	 * @return Zend_Db_Adapter_Abstract
	 */
	public static function getDb() {
		if(self::$db)		return self::$db;
		return self::initDb();
	}

	/**
	 * Vermittelt den Datenbank-Adapter zur Kunden-Datenbank
	 * @param string $ident								customer.code
	 * @return Zend_Db_Adapter_Abstract
	 */
	public static function getCustomerDb($ident) {
		if($ident !== self::$customerIdent)		self::$customerDb = null;
		self::$customerIdent = $ident;
		if(self::$customerDb)			return self::$customerDb;
		try {
			self::$customerDb = Zend_Db::factory('Pdo_Mysql', array(
				'host'				=>	CONFIG_DB_SERVER
				,'username'		=>	CONFIG_DB_USER
				,'password'		=>	CONFIG_DB_PASSWORD
				,'dbname'			=>	CONFIG_DB_NAME.'_'.self::$customerIdent
				,'charset'		=>	'UTF8'
			));
			self::$customerDb->setFetchMode(Zend_Db::FETCH_OBJ);
			self::$customerDb->query('SET NAMES utf8');
			self::$customerDb->query('SET CHARACTER SET utf8');
		} catch (Exception $e) {
			Log::err($e->getMessage());
		}
		return self::$customerDb;
	}
	
	/**
	 * Öffnet den Db-Adapter, prüft die Tabellen und initialisiert diese ggfs.
	 * @return Zend_Db_Adapter_Abstract
	 */
	public static function initDb() {
		self::$db = Zend_Db::factory('Pdo_Mysql', array(
			'host'				=>	CONFIG_DB_SERVER
			,'username'		=>	CONFIG_DB_USER
			,'password'		=>	CONFIG_DB_PASSWORD
			,'dbname'			=>	CONFIG_DB_NAME
			,'charset'		=>	'UTF8'
		));
		Zend_Db_Table::setDefaultAdapter(self::$db);
		self::$db->setFetchMode(Zend_Db::FETCH_OBJ);
		self::$db->query('SET NAMES utf8');
		self::$db->query('SET CHARACTER SET utf8');
		// 		self::dropTables();		//	Testing System-Initialization
		if(count(self::$db->listTables()) == 0)		Model_Database::createDb();
		return self::$db;
	}

	/**
	 * Führt eine SQL-Anfrage aus
	 * @param string $query
	 * @return Zend_Db_Statement_Interface
	 */
	public static function query($query, $bind = array()) {
		return Db::getDb()->query($query, $bind);
	}

	/**
	 * Erstellt sichere Quotes für SQL-Abfragen
	 * @param mixed $value								Wert der gequotert werden soll
	 * @param mixed $type									Optional SQL-Datentypname, Konstante oder null
	 * @return string
	 */
	public static function quote($value, $type=null) {
		return self::getDb()->quote($value, $type);
	}

	/**
	 * Erstellt ein Select-Objekt
	 * @return Zend_Db_Select
	 */
	public static function select() {
		return self::getDb()->select();
	}

	public static function getTable($name) {
		$class = 'Model_' . $name . 'Table';
		return new $class();
	}

	/*
	 * Tabellen-Referenzen
	 */
	
	/**
	 * Ermittelt die Model-Instanz zur Tabelle und legt diese ggfs an
	 */
	private static function _getTable($table) {
		
		$class = 'Model_'.ucwords($table, '_').'Table';
		if(!array_key_exists($class, static::$cashTables)) 			static::$cashTables[$class] = new $class;
		return static::$cashTables[$class];
	}
	
	/**
	 * @return Model_AccessTable
	 */
	public static function tAccess() {
		return static::_getTable('access');
	}
	
	/**
	 * @return Model_AccessobjectTable
	 */
	public static function tAccessobject() {
		return static::_getTable('accessobject');
	}
	
	/**
	 * @return Model_CustomerTable
	 */
	public static function tCustomer()	{
		return static::_getTable('customer');
	}

	/**
	 * @return Model_Customer_ProcessTable
	 */
	public static function tCustomerProcess() {
		return static::_getTable('customer_process');
	}
	
	/**
	 * @return Model_LanguageTable
	 */
	public static function tLanguage() {
		return static::_getTable('language');
	}

	/**
	 * @return Model_ModuleTable
	 */
	public static function tModule() {
		return static::_getTable('module');
	}

	/**
	 * @return Model_MemberTable
	 */
	public static function tMember()	{
		return static::_getTable('member');
	}
		
	/**
	 * @return Model_ProcessTable
	 */
	public static function tProcess() {
		return static::_getTable('process');
	}
	
	/**
	 * @return Model_Schema_InfoTable
	 */
	public static function tSchemaInfo() {
		return static::_getTable('schema_info');
	}

	/**
	 * @return Model_SessionTable
	 */
	public static function tSession() {
		return static::_getTable('session');
	}

	/**
	 * @return Model_UserTable
	 */
	public static function tUser() {
		return static::_getTable('user');
	}

	/**
	 * @return Model_UsergroupTable
	 */
	public static function tUsergroup() {
		return static::_getTable('usergroup');
	}

	/**
	 * @return Model_UserlogTable
	 */
	public static function tUserlog() {
		return static::_getTable('userlog');
	}

	/**
	 * @return Model_UserparamTable
	 */
	public static function tUserparam() {
		return static::_getTable('userparam');
	}

	/*
	 * Transaktionssteuerung
	 */

	/**
	 * @var integer $transactionCounter Counter für verschachtlete Transaktionen
	 */
	public static $transactionCounter = 0;

	/**
	 * Startet eine Transaktion
	 * @throws Exception
	 */
	public static function startTransaction() {
		if (defined("CONFIG_DB_TRANSACTIONS") && !CONFIG_DB_TRANSACTIONS) return;

		if (self::$transactionCounter > 0) {
			self::$transactionCounter++;
			return true;
		}
		$try = false;
		$dtStart = time();
		while(true) {
			try {
				$try = Db::getDb()->beginTransaction();
				self::$transactionCounter++;
				break;
			} catch (Zend_Db_Statement_Exception $e) {
				//@todo endlosschleifenhandling?
				if ((time() - $dtStart) > (1 * 60)) {
					// 1minute....
					throw new Exception("startTransaction failed for 1 minute...");
				}
				sleep(1);
				//2011-11-08T10:18:31+01:00 ERR (3): Zend_Db_Statement_Exception::__set_state(array(
				//   '_previous' => NULL,
				//   'message' => 'SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction',
			}
		}
		if (!$try) {
			throw new Exception("startTransaction failed for unknown reason...");
		}
		return $try;
	}

	/**
	 * Bestätigt eine Transaktion
	 * @param boolean $force							Setzt den Transaktionscounter auf 0
	 * @return boolean
	 */
	public static function commitTransaction($force = false) {
		if (defined("CONFIG_DB_TRANSACTIONS") && !CONFIG_DB_TRANSACTIONS) return;

		if ($force && self::$transactionCounter) {
			self::$transactionCounter = 0;
			return Db::getDb()->commit();
		}
		if (self::$transactionCounter) {
			self::$transactionCounter--;
			if (self::$transactionCounter == 0) {
				return Db::getDb()->commit();
			}
		}
		return true;
	}

	/**
	 * Rollt eine Transaktion zurück
	 * @param boolean $force								Setzt den Transaktionscounter auf 0
	 * @return boolean
	 */
	public static function rollbackTransaction($force = false) {
		if (defined("CONFIG_DB_TRANSACTIONS") && !CONFIG_DB_TRANSACTIONS) return;

		if ($force && self::$transactionCounter) {
			self::$transactionCounter = 0;
			return Db::getDb()->rollBack();
		}
		if (self::$transactionCounter) {
			self::$transactionCounter--;
			if (self::$transactionCounter == 0) {
				return Db::getDb()->rollBack();
			}
		}
		return true;
	}

	/**
	 * Prüft, ob Transaktionen aktiv sind
	 * @return boolean true, wenn Transaktionen aktiv sind
	 */
	public static function hasTransaction() {
		return (bool)self::$transactionCounter;
	}

}