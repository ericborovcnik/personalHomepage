<?php
//## LOCALE ##
define('CONFIG_LOCALE_CODE',         'de_CH');           //  Lokalisierung in ISO-Notation
define('CONFIG_LOCALE_TIMEZONE',     'Europe/Zurich');   //  Zeitzone in ISO-Notation
define('CONFIG_LOCALE_LANGUAGE',     'de_CH');           //  Standard-Systemsprache

//## DB ##
define('CONFIG_DB_SERVER',            '127.0.0.1');       //  Datenbank-Host
define('CONFIG_DB_USER',              'root');            //  Datenbank-Benutzer
define('CONFIG_DB_PASSWORD',          '');                //  Datenbank-Passwort
define('CONFIG_DB_NAME',              'template');        //  Datenbankname

//## MAIL ##
define('CONFIG_SMTP_SERVER',          '');                //  Mail-Server
define('CONFIG_SMTP_USERNAME',        '');                //  SMTP-Benutzername
define('CONFIG_SMTP_PASSWORD',        '');                //  SMTP-Passwort
define('CONFIG_SMTP_FROM',            '');                //  Email-Sendeadresse (=SMTP-Username;=User.EMail)
define('CONFIG_SMTP_SUPPORT',         '');                //  Email-Adresse für Support
define('CONFIG_SMTP_AUTH',            'login');           //  SMTP-Authentifzierung
define('CONFIG_SMTP_SSL',             false);             //  SSL       'tls'
define('CONFIG_SMTP_PORT',            false);             //  SMTP-Port '587'

//## SMS ##
define('CONFIG_SMS_GATEWAY', 					'');								//	SMS-Gateway z.b. swisscom.com/gateway
define('CONFIG_SMS_SERVICETYPE',			'');								//	Servicetyp falls angeboten
define('CONFIG_SMS_USERNAME',					'');								//  Username vom Service
define('CONFIG_SMS_PASSWORD',					'');								// 	Passwort vom Usernamen, meist MD5

//##	DEBUG	##
define('CONFIG_DEBUG',                true);              //  Debug-Meldungen werden nach debug.log geschrieben
