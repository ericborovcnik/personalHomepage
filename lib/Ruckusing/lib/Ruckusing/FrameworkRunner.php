<?php
/**	Ruckusing_FrameworkRunner		Primary work-horse class. This class bootstraps the framework by loading all adapters and tasks
	*	@author			Cody Cuaghlan <codycuahglna@gmail.com>
	*	@author			Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
	*	@version		2014-10-07	eb	revis-Adaption
	*/
class Ruckusing_FrameworkRunner 
{
//////////////////////////
//	Instance variables	//
//////////////////////////
	/**	$_db															Reference to our db-connection
		*	@var	array
		*/
		private $_db = null;
	/**	$_active_db_config								The currently active config
		*	@var	array
		*/
		private $_active_db_config;
	/**	$_config													Availbe DB config (e.g. test, development, production)
		*	@var	array
		*/
		private $_config = array();
	/**	@_task_mgr												Task manager
		*	@var	Ruckusing_Task_Manager
		*/
		private $_task_mgr = null;
	/**	$_adapter													Ruckusing-Adapter
		*	@var	Ruckusing_Adapter_Base
		*/
		private $_adapter = null;
	/**	$_cur_task_name										Current Task name
		*	@var	string
		*/
		private $_cur_task_name = "";
	/**	$_task_options										Task-Options
		*	@var	string
		*/
		private $_task_options = "";
	/**	$_env															Environment (default is development)
		*	@var	string
		*/
		private $_env = "development";
	/**	$_opt_map													Set up some defaults
		*	@var	array
		*/
		private $_opt_map = array(
			'env' => 'development'
		);
	/**	$_showhelp												Flag to display help of task
		*	@see	Ruckusing_FrameworkRunner::parse_args
		*	@var	boolean
		*/
		private $_showhelp = false;

//////////////////////
//	Public methods	//
//////////////////////
	/**	__construct($config, $argv, Ruckusing_Util_Logger $log=null)		Creates an instance of Ruckusing_Adapter_Base
		*	@param	array		$config						The current config
		*	@param	array		$argv							The supplied command line arguments
		*	@param	Ruckusing_Util_Looger	$log	An optional custom logger
		*	@return	Ruckusing_FrameworkRunner
		*/
		public function __construct($config, $argv, Ruckusing_Util_Logger $log = null) {
			set_error_handler(array('Ruckusing_Exception', 'errorHandler'), E_ALL);
			set_exception_handler(array('Ruckusing_Exception', 'exceptionHandler'));
			$this->parse_args($argv);							//	parse arguments
			$this->_config = $config;							//	set config variables
			$this->verify_db_config();						//	verify config array
			$this->logger = $log;									//	initialize logger
			$this->initialize_logger();
			//include all adapters
			$this->load_all_adapters(RUCKUSING_BASE.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Ruckusing'.DIRECTORY_SEPARATOR.'Adapter');
			$this->initialize_db();
			$this->init_tasks();									//	initialize tasks
		}
	/**	execute()													Execute the current task	*/
		public function execute() {
			$output = '';
			if(empty($this->_cur_task_name)) {
				if(isset($_SERVER["argv"][1]) && stripos($_SERVER["argv"][1], '=') === false) {
					$output .= sprintf("\n\tWrong Task format: %s\n", $_SERVER["argv"][1]);
				}
				$output .= $this->help();
			} else {
				if($this->_task_mgr->has_task($this->_cur_task_name)) {
					if($this->_showhelp) {
						$output .= $this->_task_mgr->help($this->_cur_task_name);
					} else {
						$output .= $this->_task_mgr->execute($this, $this->_cur_task_name, $this->_task_options);
					}
				} else {
					$output .= sprintf("\n\tTask not found: %s\n", $this->_cur_task_name);
					$output .= $this->help();
				}
			}
			if($this->logger) {
				$this->logger->close();
			}
			return $output;
		}
	/**	get_adapter()											Get the current Adapter
		*	@return	object
		*/
		public function get_adapter()	{
			return $this->_adapter;
		}
	/**	init_tasks()											Initialize the task manager	*/
		public function init_tasks() {
			$this->_task_mgr = new Ruckusing_Task_Manager($this->_adapter, $this->_config);
		}
	/**	migrations_directory($key='')			Get the current migration dir
		*	@param	string	$key							the module key name
		*	@return	string
		*/
		public function migrations_directory($key = '') {
			return $this->_config['migrations_dir']['default'];
		}
	/**	migrations_directories()					Get all migrations directory
		*	@return	array
		*/
		public function migrations_directories() {
					return $this->_config['migrations_dir'];
		}
	/**	db_directory()										Get the current db schema dir
		*	@return	string
		*/
		public function db_directory() {
			return $this->_config['db_dir'];
		}
	/**	initialize_db()										Initialize the database	*/
		public function initialize_db() {
			$db = $this->_config['db'][$this->_env];
			$adapter = $this->get_adapter_class($db['type']);
			if (empty($adapter)) {
				throw new Ruckusing_Exception(sprintf("No adapter available for DB type: %s", $db['type']),	Ruckusing_Exception::INVALID_ADAPTER);
			}
			$this->_adapter = new $adapter($db, $this->logger);
		}
	/**	initialize_logger()								Initialize the logger	*/
		public function initialize_logger() {
			if(!$this->logger) {
				if(is_dir($this->_config['log_dir']) && !is_writable($this->_config['log_dir'])) {
					throw new Ruckusing_Exception("\n\nCannot write to log directory: " . $this->_config['log_dir'] . "\n\nCheck permissions.\n\n",	Ruckusing_Exception::INVALID_LOG);
				} elseif (!is_dir($this->_config['log_dir'])) {
					//try and create the log directory
					mkdir($this->_config['log_dir'], 0755, true);
				}
				$log_name = sprintf("%s.log", $this->_env);
				$this->logger = Ruckusing_Util_Logger::instance($this->_config['log_dir'].DIRECTORY_SEPARATOR.$log_name);
			}
		}
	/**	update_schema_for_timestamps()		Update the local schema to handle multiple records versus the prior architecture of 
		*																		storing a single version. In addition take all existing migration files and
		*																		register them in our new table, as they have already been executed.
		*/
		public function update_schema_for_timestamps() {
			//	do nothing
		}

//////////////////////
//	Private methods	//
//////////////////////
	/**	parse_args($arv)									0: task 1..n arguments for task
		*	@param	array		$argv							The current command line arguments
		*/
		private function parse_args($argv) {
			$num_args = count($argv);
			$options = array();
			for($i = 0; $i < $num_args; $i++) {
				$arg = $argv[$i];
				if(stripos($arg, ':') !== false) {
					$this->_cur_task_name = $arg;
				} elseif ($arg == 'help') {
					$this->_showhelp = true;
					continue;
				} elseif (stripos($arg, '=') !== false) {
					list($key, $value) = explode('=', $arg);
					$key = strtolower($key); // Allow both upper and lower case parameters
					$options[$key] = $value;
					if($key == 'env') {
						$this->_env = $value;
					}
				}
			}
			$this->_task_options = $options;
		}
	/**	set_opt($key, $value)							Set an option
		*	@param	string	$key							The key to set
		*	@param	string	$value						The value to set
		*/
		private function set_opt($key, $value) {
			if(!$key) {
				return;
			}
			$this->_opt_map[$key] = $value;
		}
	/**	verify_db_config()								Verify db config	*/
		private function verify_db_config() {
			if( !array_key_exists($this->_env, $this->_config['db'])) {
				throw new Ruckusing_Exception(sprintf("Error: '%s' DB is not configured", $this->_env),	Ruckusing_Exception::INVALID_CONFIG);
			}
			$env = $this->_env;
			$this->_active_db_config = $this->_config['db'][$this->_env];
			if(!array_key_exists("type",$this->_active_db_config)) {
				throw new Ruckusing_Exception(sprintf("Error: 'type' is not set for '%s' DB", $this->_env),	Ruckusing_Exception::INVALID_CONFIG);
			}
			if(!array_key_exists("host",$this->_active_db_config)) {
				throw new Ruckusing_Exception(sprintf("Error: 'host' is not set for '%s' DB", $this->_env),	Ruckusing_Exception::INVALID_CONFIG);
			}
			if(!array_key_exists("database",$this->_active_db_config)) {
				throw new Ruckusing_Exception(sprintf("Error: 'database' is not set for '%s' DB", $this->_env),	Ruckusing_Exception::INVALID_CONFIG);
			}
			if(!array_key_exists("user",$this->_active_db_config)) {
				throw new Ruckusing_Exception(sprintf("Error: 'user' is not set for '%s' DB", $this->_env),	Ruckusing_Exception::INVALID_CONFIG);
			}
			if(!array_key_exists("password",$this->_active_db_config)) {
				throw new Ruckusing_Exception(sprintf("Error: 'password' is not set for '%s' DB", $this->_env),	Ruckusing_Exception::INVALID_CONFIG);
			}
			if(empty($this->_config['migrations_dir'])) {
				throw new Ruckusing_Exception("Error: 'migrations_dir' is not set in config.", Ruckusing_Exception::INVALID_CONFIG);
			}
			if(is_array($this->_config['migrations_dir'])) {
				if(!isset($this->_config['migrations_dir']['default'])) {
					throw new Ruckusing_Exception("Error: 'migrations_dir' 'default' key is not set in config.",Ruckusing_Exception::INVALID_CONFIG);
				} elseif (empty($this->_config['migrations_dir']['default'])) {
					throw new Ruckusing_Exception("Error: 'migrations_dir' 'default' key is empty in config.",Ruckusing_Exception::INVALID_CONFIG);
				} else {
					$names = $paths = array();
					foreach ($this->_config['migrations_dir'] as $name => $path) {
						if(isset($names[$name])) {
							throw new Ruckusing_Exception("Error: 'migrations_dir' '$name' key is defined multiples times in config.",Ruckusing_Exception::INVALID_CONFIG);
						}
						if(isset($paths[$path])) {
							throw new Ruckusing_Exception("Error: 'migrations_dir' '{$paths[$path]}' and '$name' keys defined the same path in config.",Ruckusing_Exception::INVALID_CONFIG);
						}
						$names[$name] = $path;
						$paths[$path] = $name;
					}
				}
			}
			if (isset($this->_task_options['module']) && !isset($this->_config['migrations_dir'][$this->_task_options['module']])) {
				throw new Ruckusing_Exception(sprintf("Error: module name %s is not set in 'migrations_dir' option in config.", $this->_task_options['module']),	Ruckusing_Exception::INVALID_CONFIG);
			}
			if (empty($this->_config['db_dir'])) {
				throw new Ruckusing_Exception("Error: 'db_dir' is not set in config.",Ruckusing_Exception::INVALID_CONFIG);
			}
			if (empty($this->_config['log_dir'])) {
				throw new Ruckusing_Exception("Error: 'log_dir' is not set in config.",	Ruckusing_Exception::INVALID_CONFIG);
			}
		}
	/**	get_adapter_class($db_type)				Get the adapter class
		*	@param	string	$db_type					the database type
		*	@return	string
		*/
		private function get_adapter_class($db_type) {
			$adapter_class = null;
			switch ($db_type) {
				case 'mysql':		$adapter_class = "Ruckusing_Adapter_MySQL_Base";		break;
				case 'pgsql':		$adapter_class = "Ruckusing_Adapter_PgSQL_Base";		break;
				case 'sqlite':	$adapter_class = "Ruckusing_Adapter_Sqlite3_Base";	break;
			}
			return $adapter_class;
		}
	/**	load_all_adapters()								DB adapters are classes in lib/Ruckusing/Adapter and they follow the file name syntax of "<DB Name>/Base.php".
		*																		See the function "get_adapter_class" in this lass for examples.
		*	@param	string	$adapter_dir			the adapter directory
		*/
		private function load_all_adapters($adapter_dir) {
			if(!is_dir($adapter_dir)) {
				throw new Ruckusing_Exception(sprintf("Adapter dir: %s does not exist", $adapter_dir),Ruckusing_Exception::INVALID_ADAPTER);
				return false;
			}
			$files = scandir($adapter_dir);
			foreach ($files as $f) {
				//skip over invalid files
				if($f == '.' || $f == ".." || !is_dir($adapter_dir.DIRECTORY_SEPARATOR.$f))		continue;
				$adapter_class_path = $adapter_dir.DIRECTORY_SEPARATOR.$f.DIRECTORY_SEPARATOR.'Base.php';
				if(file_exists($adapter_class_path)) 		  require_once $adapter_class_path;
			}
		}
	/**	help()														Return the usage of the task
		*	@return	string
		*/
		public function help() {
			//	do nothing			
		}

}
