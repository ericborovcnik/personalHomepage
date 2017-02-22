<?php
/**
 * Spezial-Model - Enthält die Basistabellen zur Initialisierung der Anwendungs- und Kundentabellen
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-21	eb	from scratch
 * @version		2016-05-23	eb	migrateDb; migrateCustomerDb
 *
 * @outline
 * createDb()									Erzeugt die Anwendungstabellen
 * createCustomerDb()					Erzeugt eine Kunden-Datenbank
 * migrateDb()								Startet die System-Migration
 * migrateCustomerDb()				Startet die Kunden-Migration
 */
abstract class Model_Database {
	
	/**
	 * Erzeugt die Anwendungstabellen
	 */
	public static function createDb() {
		$db = Db::getDb();
		Log::debug('Connected database is empty. Tables will be created from scratch...');
		Log::debug('...preparing Ruckusing-Updates by schema_info-table');
		$db->query("CREATE TABLE IF NOT EXISTS `schema_info` (
								  `version` INT NULL COMMENT 'Datenbank-Version')
								ENGINE = InnoDB
								COMMENT = 'Ruckusing-Versionentabelle'");
		$db->insert('schema_info', array(
			'version'	=>	0
		));
		Log::debug('...creating all further tables by Ruckusing-Updates');

		Model_Database::migrateDb();

		//
		//	Initialisiere Datensätze
		Log::debug('Initialize basic records...');
		Log::debug('...Usergroups');
		Db::tUsergroup()->getGuest();
		Db::tUsergroup()->getAdmin();
		Db::tUsergroup()->getUser();
		Log::debug('...Users');
		Db::tUser()->getGuest();
		Db::tUser()->getAdmin();
		Log::debug('...Modules');
		Module_Core::register();
		Module_Customer::register();
		Module_Import::register();
		Module_Datapool::register();
		Module_Test::register();
		
		

		//
		//	Initialisiere Module
		Log::debug('Initialize Modules...');
		$modules = Db::tModule()->getModuleList();
		foreach($modules as $module => $active) {
			$class = Util::getClass('Module_'.$module);
			if($class) {
				if($active) {
					Log::debug('...'.$class);
					$class::activate();
				}
			}
		}
		
		//
		//	Admin-Login
// 		Log::debug('Auto-Admin-Login...');
// 		User::login('admin',hash('sha512', 'admin', false));
// 		Log::debug('>>> '.User::getUser()->getUsername());
	
	}

	/**
	 * Erzeugt eine Kunden-Datenbank
	 * @param string $ident								Kunden-Ident
	 */
	public static function createCustomerDb($ident) {
		Db::query('Create database red_'.$ident);
		Db::query("create table red_".$ident.".schema_info (
								version INT(11) NULL DEFAULT NULL COMMENT 'Datenbank-Version'
							)
							COMMENT='Ruckusing-Versionentabelle'
							ENGINE=InnoDB;"
		);
		Db::query('insert into red_'.$ident.'.schema_info (version) values (0)');
		self::migrateCustomerDb($ident);
	}
	
	/**
	 * Führt die Ruckusing-Scripts für die System-Datenbank aus.
	 */
	public static function migrateDb() {
		Log::debug('Execute Ruckusing-Updates...');
		exec('php lib/Ruckusing/main.php db:migrate', $result);
		Log::debug($result);
	}
	
	/**
	 * Führt die Ruckusing-Scripts für eine Kunden-Datenbank aus
	 * @param string $ident								customer.code
	 */
	public static function migrateCustomerDb($ident) {
		Log::debug('Execute Ruckusing-Updates for Customer '.$ident.'...');
		exec('php lib/Ruckusing/migrate_customer.php db:migrate '.$ident, $result);
		Log::debug($result);
	}
	
}