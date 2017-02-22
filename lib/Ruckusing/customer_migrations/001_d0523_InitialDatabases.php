<?php
/**
 * Initialisierung der Basis Customer-Datenbank
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-06-02	du	InitialDatabases for customer 
 */
class d0523_InitialDatabases extends Ruckusing_Factory {

	public function run() {
		Log::out($this);
		$this->log('Primary initialization of customer-database.');
		$this->_createTables();

	}
	
	private function _createTables() {
		$this->log('Creating tables...');
		
		// booking
		$this->query("CREATE TABLE IF NOT EXISTS `booking` (
										`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK ',
										`accountnumber` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'Kontonummer',
										`bookingdate` DATE NOT NULL COMMENT 'Buchungsdatum',
										`amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Betrag',
										`bookingtext` TEXT NULL COMMENT 'Buchungstext',
										`lig_id` INT(11) NOT NULL DEFAULT '0' COMMENT 'FK lig.id',
										PRIMARY KEY (`id`))
									COMMENT='Buchungen'
									ENGINE=InnoDB");
		
		// contract
		$this->query("CREATE TABLE IF NOT EXISTS `contract` (
										`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
										`nr` VARCHAR(50) NOT NULL COMMENT 'Number',
										`activefrom` DATE NOT NULL COMMENT 'Aktiv von',
										`activeto` DATE NOT NULL COMMENT 'Aktiv bis',
										`rentalobject_id` INT(11) NOT NULL COMMENT 'FK rentalobject.id',
										`tenant_id` INT(11) NOT NULL COMMENT 'FK tenant.id',
										PRIMARY KEY (`id`))
									COMMENT='Verträge'
									ENGINE=InnoDB");
		
		// debtposition
		$this->query("CREATE TABLE IF NOT EXISTS `debtposition` (
										`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
										`type` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'Sollstellungstyp',
										`description` TEXT NULL COMMENT 'Beschreibung',
										`amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Betrag',
										`period` INT(11) NOT NULL COMMENT 'Periodizität',
										`activefrom` DATE NOT NULL COMMENT 'Aktiv von',
										`activeto` DATE NOT NULL COMMENT 'Aktiv bis',
										`contract_id` INT(11) NOT NULL COMMENT 'FK contract.id',
										PRIMARY KEY (`id`))
									COMMENT='Sollstellungen'
									ENGINE=InnoDB");
		
		
		// lig
		$this->query("CREATE TABLE IF NOT EXISTS `lig` (
										`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
										`ligNr` VARCHAR(11) NOT NULL DEFAULT '0' COMMENT 'Liegenschaftsnummer',
										`ligName` VARCHAR(11) NOT NULL DEFAULT '0' COMMENT 'Liegenschaftsname',
										`place` VARCHAR(11) NOT NULL DEFAULT '0' COMMENT 'Ort',
										PRIMARY KEY (`id`))
									COMMENT='Liegenschaften'
									ENGINE=InnoDB");
		
		
		// rentalobject
		$this->query("CREATE TABLE IF NOT EXISTS `rentalobject` (
										`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
										`nr` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'Mietobjekt-Nummer',
										`usagetype` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'Nutzungsart',
										`floor` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'Stockwerk',
										`area` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Fläche',
										`activefrom` DATE NOT NULL COMMENT 'Aktive von',
										`activeto` DATE NULL DEFAULT NULL COMMENT 'Aktiv bis',
										`lig_id` INT(11) NOT NULL DEFAULT '0' COMMENT 'FK lig.id',
										PRIMARY KEY (`id`))
									COMMENT='Mietobjekte'
									ENGINE=InnoDB");
		
		
		// tenant
		$this->query("CREATE TABLE IF NOT EXISTS `tenant` (
										`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
										`nr` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'Mieternummer',
										`name` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'Name des Mieters',
										PRIMARY KEY (`id`))
									COMMENT='Mieter'
									ENGINE=InnoDB");
	
	}

}