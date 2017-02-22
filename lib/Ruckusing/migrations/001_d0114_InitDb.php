<?php
/**
 * Initialisierung der Basis-Datenbank
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-14	eb	from scratch
 */
class d0114_InitDb extends Ruckusing_Factory {

	public function run() {
		$this->log('Primary initialization of application-database.');
		$this->_createTables();
	}

	private function _createTables() {
		$this->log('Creating tables...');
		//
		//	session
		$this->log('- [session]');
		$this->query("CREATE TABLE IF NOT EXISTS `session` (
										`id` CHAR(32) NOT NULL COMMENT 'PK',
										`user_id` INT(11) NULL COMMENT 'Referenz zu user (ohne FK)',
										`modified` INT(11) NULL COMMENT 'Timestamp letztes Sitzungssignal',
										`lifetime` INT(11) NULL COMMENT 'Lebenszeit der Sitzung Total',
										`data` MEDIUMTEXT NULL COMMENT 'Sitzungsdaten \$_SESSION',
										PRIMARY KEY (`id`))
									ENGINE = InnoDB
									COMMENT = 'Benutzersitzungen und ihre Daten'");
		//
		//	usergroup
		$this->log('- [usergroup]');
		$this->query("CREATE TABLE `usergroup` (
										`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
										`code` VARCHAR(20) NULL COMMENT 'Kurzbezeichnung',
										`name` VARCHAR(50) NULL COMMENT 'Bezeichnung',
										`description` TEXT NULL COMMENT 'Beschreibung',
										PRIMARY KEY (`id`))
									ENGINE = InnoDB
									COMMENT = 'Benuterzgruppen/Rollen'");
		//
		//	user
		$this->log('- [user]');
		$this->query("CREATE TABLE IF NOT EXISTS `user` (
										`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
										`usergroup_id` INT(11) NOT NULL COMMENT 'FK zu usergroup',
										`loginname` VARCHAR(50) NULL COMMENT 'Anmeldename',
										`username` VARCHAR(45) NULL COMMENT 'Benutzername',
										`password` VARCHAR(128) NULL COMMENT 'SHA512-Verschlüsseltes Kennwort',
										`email` VARCHAR(255) NULL COMMENT 'Email-Adresse',
										`language` VARCHAR(10) NULL COMMENT 'Sprachcode in ISO-Notation (de_CH)',
										`locale` VARCHAR(10) NULL COMMENT 'Lokalisierung in ISO-Notation (de_CH)',
										`lastlogin` DATE NULL COMMENT 'Datum der letzten Anmeldung',
										`logincount` INT(11) NULL COMMENT 'Anzahl Anmeldungen',
										`active_from` DATE NULL COMMENT 'Aktiv ab',
										`active_to` DATE NULL COMMENT 'Aktiv bis',
										PRIMARY KEY (`id`),
										INDEX `loginname` (`loginname` ASC),
										INDEX `fk_user_usergroup_idx` (`usergroup_id` ASC),
										CONSTRAINT `fk_user_usergroup`
											FOREIGN KEY (`usergroup_id`)
											REFERENCES `usergroup` (`id`)
											ON DELETE CASCADE
											ON UPDATE CASCADE)
									ENGINE = InnoDB
									COMMENT = 'Benutzerstamm'");
		//
		//	userlog
		$this->log('- [userlog]');
		$this->query("CREATE TABLE IF NOT EXISTS `userlog` (
									  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
									  `user_id` INT(11) NULL COMMENT 'FK zu user.id',
									  `date` DATETIME NULL COMMENT 'Zeitpunkt der Anmeldung am System',
									  `host` VARCHAR(255) NULL COMMENT 'IP des verwendeten Hosts',
									  `agent` VARCHAR(255) NULL COMMENT 'Verwendeter Browser + Browserversion',
									  PRIMARY KEY (`id`),
									  INDEX `fk_user_id_idx` (`user_id` ASC),
									  CONSTRAINT `fk_user_id`
									    FOREIGN KEY (`user_id`)
									    REFERENCES `user` (`id`)
									    ON DELETE CASCADE
									    ON UPDATE CASCADE)
									ENGINE = InnoDB
									COMMENT = 'Anmeldeprotokoll'");
		//
		//	module
		$this->log('- [module]');
		$this->query("CREATE TABLE IF NOT EXISTS `module` (
									  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'PK',
									  `code` VARCHAR(50) NULL COMMENT 'Module-Code',
									  `name` VARCHAR(255) NULL COMMENT 'Modul-Bezeichnung',
									  `description` TEXT NULL COMMENT 'Modulbeschreibung',
									  `active` INT(1) NULL DEFAULT 0 COMMENT 'Modul verfügbar?',
										`sort` INT(3) NULL COMMENT 'Sort-Schlüssel',
									  PRIMARY KEY (`id`),
									  INDEX `code` (`code` ASC),
									  INDEX `name` (`name` ASC))
									ENGINE = InnoDB
									COMMENT = 'Verfügbare Module'");
		//
		//	accessobject
		$this->log('- [accessobject]');
		$this->query("CREATE TABLE IF NOT EXISTS `accessobject` (
									  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
									  `module_id` INT(11) NOT NULL,
									  `code` VARCHAR(50) NULL COMMENT 'Zugriffsobjekt',
									  `name` VARCHAR(255) NULL COMMENT 'Zugriffsobjekt-Bezeichnung',
									  `description` TEXT NULL COMMENT 'Beschreibung',
									  PRIMARY KEY (`id`, `module_id`),
									  INDEX `accessobject` (`code` ASC),
									  INDEX `fk_accessobject_module` (`module_id` ASC),
									  CONSTRAINT `fk_accessobject_module`
									    FOREIGN KEY (`module_id`)
									    REFERENCES `module` (`id`)
									    ON DELETE CASCADE
									    ON UPDATE CASCADE)
									ENGINE = InnoDB
									COMMENT = 'Datenzugriffsobjekte'");
		//
		//	access
		$this->log('- [access]');
		$this->query("CREATE TABLE IF NOT EXISTS `access` (
									  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
									  `usergroup_id` INT(11) NOT NULL COMMENT 'FK zu usergroup.id',
									  `accessobject_id` INT(11) NOT NULL COMMENT 'FK zu accessobject',
									  `access` ENUM('read','write') NULL COMMENT 'Art des Zugriffs (\'read\',\'write\')',
									  PRIMARY KEY (`id`, `usergroup_id`, `accessobject_id`),
									  INDEX `fk_access_usergroup` (`usergroup_id` ASC),
									  INDEX `fk_access_accessobject` (`accessobject_id` ASC),
									  CONSTRAINT `fk_access_usergroup`
									    FOREIGN KEY (`usergroup_id`)
									    REFERENCES `usergroup` (`id`)
									    ON DELETE CASCADE
									    ON UPDATE CASCADE,
									  CONSTRAINT `fk_access_accessobject`
									    FOREIGN KEY (`accessobject_id`)
									    REFERENCES `accessobject` (`id`)
									    ON DELETE CASCADE
									    ON UPDATE CASCADE)
									ENGINE = InnoDB
									COMMENT = 'Zugriffsrechte'");
		//
		//	userparam
		$this->log('- [userparam]');
		$this->query("CREATE TABLE IF NOT EXISTS `userparam` (
									  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
									  `user_id` INT(11) NULL COMMENT 'FK zu user.id',
									  `paramkey` VARCHAR(50) NULL COMMENT 'Schlüsselbezeichnung zum Parameter',
									  `paramvalue` MEDIUMTEXT NULL COMMENT 'Wert des Parameters',
									  PRIMARY KEY (`id`),
									  INDEX `fk_userparam_user_idx` (`user_id` ASC),
									  INDEX `user_param` (`user_id` ASC, `paramkey` ASC),
									  CONSTRAINT `fk_userparam_user`
									    FOREIGN KEY (`user_id`)
									    REFERENCES `user` (`id`)
									    ON DELETE CASCADE
									    ON UPDATE CASCADE)
									ENGINE = InnoDB
									COMMENT = 'Benutzer-Parameter'");
		//
		//	language
		$this->log('- [language]');
		$this->query("CREATE TABLE IF NOT EXISTS `language` (
									  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
									  `frontend` INT(1) NULL DEFAULT 0 COMMENT 'Sprachelement für Frontend?',
									  `locked` INT(1) NULL DEFAULT 0 COMMENT 'Sprachelement durch Benutzer übersteuert?',
									  `key` VARCHAR(255) NULL COMMENT 'Sprachschlüssel',
									  `de_CH` VARCHAR(255) NULL COMMENT 'Übersetzung für de_CH',
									  `de_DE` VARCHAR(255) NULL COMMENT 'Übersetzung für de_DE',
									  `de_AT` VARCHAR(255) NULL COMMENT 'Übersetzung für de_AT',
									  `en_GB` VARCHAR(255) NULL COMMENT 'Übersetzung für en_GB',
									  `fr_FR` VARCHAR(255) NULL COMMENT 'Übersetzung für fr_FR',
									  `it_IT` VARCHAR(255) NULL COMMENT 'Übersetzung für it_IT',
									  PRIMARY KEY (`id`),
									  INDEX `key` (`key` ASC),
									  INDEX `frontend` (`frontend` ASC, `key` ASC))
									ENGINE = InnoDB
									COMMENT = 'Übersetzungsmatrix'");
		//
		// customer
		$this->log('- [customer]');
		$this->query("CREATE TABLE IF NOT EXISTS `customer` (`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
									`name` VARCHAR(50) NOT NULL COMMENT 'Customer Name',
									`code` VARCHAR(250) NOT NULL COMMENT 'Postfix Kunden DB',
									`description` TEXT NULL COMMENT 'Freitext',
									PRIMARY KEY (`id`))
									COMMENT='Kundenstamm'
									ENGINE=InnoDB");

		//
		// member
		$this->query("CREATE TABLE `member` (
									`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
									`customer_id` INT(11) NOT NULL DEFAULT '0' COMMENT 'FK customer',
									`user_id` INT(11) NOT NULL DEFAULT '0' COMMENT 'FK user',
									`role` ENUM('admin','reader','writer') NOT NULL DEFAULT 'reader' COMMENT 'Typisierung',
									PRIMARY KEY (`id`),
									INDEX `fk_member_user` (`user_id`),
									INDEX `fk_member_customer` (`customer_id`),
									CONSTRAINT `fk_member_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
									CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
									)
									COMMENT='Mitglieder-Tabelle verbindet Benutzer mit Kunden'
									ENGINE=InnoDB");
		
		
	}

}