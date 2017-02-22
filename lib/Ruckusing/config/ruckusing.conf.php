<?php
date_default_timezone_set('UTC');

//----------------------------
// DATABASE CONFIGURATION
//----------------------------

/*
Valid types (adapters) are Postgres & MySQL:
'type' must be one of: 'pgsql' or 'mysql' or 'sqlite'
*/
return array(
	'db' => array(
		'development' 	=> array(
			'type'				=>	'mysql',
			'host'				=>	CONFIG_DB_SERVER,
			'port'				=>	3306,
			'database'		=>	CONFIG_DB_NAME,
			'user'				=>	CONFIG_DB_USER,
			'password'		=>	CONFIG_DB_PASSWORD,
			// ,'charset'		=> 'utf8'
			// ,'directory'	=>	'custom_name'
			// ,'socket'			=>	'/var/run/mysqld/mysqld.sock'
		)
	

	),
	'migrations_dir'	=>	array('default' => RUCKUSING_WORKING_BASE . DIRECTORY_SEPARATOR . 'migrations'),
	'db_dir'					=>	RUCKUSING_WORKING_BASE . DIRECTORY_SEPARATOR . 'db',
	'log_dir'					=>	RUCKUSING_WORKING_BASE . DIRECTORY_SEPARATOR . 'logs',
	'ruckusing_base'	=>	dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
);
