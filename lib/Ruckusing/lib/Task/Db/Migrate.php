<?php
/**	Task_Db_Migrate
	*	@author			Cody Caughlan <codycaughlan@gmail.com
	*	@author			Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
	*	@version		2014-10-03	eb	revis-Adaption
	*/

define('STYLE_REGULAR', 1);
define('STYLE_OFFSET', 2);
class Task_Db_Migrate extends Ruckusing_Task_Base implements Ruckusing_Task_Interface
{
////////////////////////
//	Instanzvariablen	//
////////////////////////
	/**	_migrator_util										migrator util
		*	@var	Ruckusing_Util_Migrator
		*/
		private $_migrator_util = null;
	/**	_adapter													current Adapter
		*	@var	Ruckusing_Adapter_Base
		*/
		private $_adapter = null;
	/**	_migratorDirs											Migrator Directories
		*	@var	string
		*/
		private $_migratorDirs = null;
	/**	_task_args												Task Arguments
		*	@var	array
		*/
		private $_task_args = array();
	/**	_debug														Debug-Flag
		*	@var	boolean
		*/
		private $_debug = false;
	/**	_return														Return executed string
		*	@var	string
		*/
		private $_return = '';

////////////////////////
//	Public Interface	//
////////////////////////
	/**	__construct($adapter)							Creates an instance of Task_DB_Migrate
		*	@param	Ruckusing_Adapter_Base	$adapter		The current adapter being used
		*	@return	Task_DB_Migrate
		*/
		public function __construct($adapter) {
			parent::__construct($adapter);
			$this->_adapter = $adapter;
			$this->_migrator_util = new Ruckusing_Util_Migrator($this->_adapter);
		}
	/**	execute($args)										Haupt-Einstiegspunkt für diesen Task
		*	@param	array	$args								Parameter
		*/
		public function execute($args) {

			if (!$this->_adapter->supports_migrations()) {
				throw new Ruckusing_Exception("This database does not support migrations.",	Ruckusing_Exception::MIGRATION_NOT_SUPPORTED);
			}
			$this->_task_args = $args;
			$this->_return .= "Started: " . date('Y-m-d g:ia T') . "\n\n";
			try {
				$this->verify_environment();
				$target_version = null;
				$style = STYLE_REGULAR;
				$current_version = $this->_migrator_util->get_max_version();
				$current_db = $this->_adapter->db_info['database'];
				$this->_return .= 'Migrating database '.$current_db.' from current version '.$current_version."\n";
				$this->prepare_to_migrate($target_version, 'up');
				//
				//	Completed - display accumulated output
				if (!empty($output)) {
					$this->_return .= "\n\n";
				}
			} catch (Ruckusing_Exception $ex) {
				if ($ex->getCode() == Ruckusing_Exception::MISSING_SCHEMA_INFO_TABLE) {
					$this->_return .= "\tSchema info table does not exist. I tried creating it but failed. Check permissions.";
				} else {
					throw $ex;
				}
			}
			$this->_return .= "\n\nFinished: " . date('Y-m-d g:ia T') . "\n\n";
			return $this->_return;
		}
	/**	migrate_from_offset($steps, $current_version, $direction)		Migrate to a specific version using steps from current version
		*	@param	integer	$steps						number of version to jump to
		*	@param	string	$current_version	Current version
		*	@param	string	$direction				direction to migrate (up/down)
		*/
		private function migrate_from_offset($steps, $current_version, $direction) {
			$migrations = $this->_migrator_util->get_migration_files($this->_migratorDirs, $direction);
			$current_index = $this->_migrator_util->find_version($migrations, $current_version, true);
			$current_index = $current_index !== null ? $current_index : -1;
			if ($this->_debug == true) {
				$this->_return .= print_r($migrations, true);
				$this->_return .= "\ncurrent_index: " . $current_index . "\n";
				$this->_return .= "\ncurrent_version: " . $current_version . "\n";
				$this->_return .= "\nsteps: " . $steps . " $direction\n";
			}
			// If we are not at the bottom then adjust our index (to satisfy array_slice)
			if ($current_index == -1 && $direction === 'down') {
				$available = array();
			} else {
				if ($direction === 'up') {
					$current_index += 1;
				} else {
					$current_index += $steps;
				}
				// check to see if we have enough migrations to run - the user
				// might have asked to run more than we have available
				$available = array_slice($migrations, $current_index, $steps);
			}
			$target = end($available);
			if ($this->_debug == true) {
				$this->_return .= "\n------------- TARGET ------------------\n";
				$this->_return .= print_r($target, true);
			}
			$this->prepare_to_migrate(isset($target['version']) ? $target['version'] : null, $direction);
		}
	/**	prepare_to_migrate($destination, $direction)
		*	@param	string	$destination				version to migrate to
		*	@param	string	$direction					direction to migrate (up/down)
		*/
		private function prepare_to_migrate($destination, $direction)	{
			try {
				$this->_return .= "\n";
				$migrations = $this->_migrator_util->get_runnable_migrations($this->_migratorDirs, $direction, $destination);
				//
				//	Eliminiere alle Migrationselemente, die Älter sind als $current_version
				$current_version = $this->_migrator_util->get_max_version();
				foreach($migrations as $key => $migration) {
					if($migration['version'] <= $current_version) {
						unset($migrations[$key]);
					}
				}
				if(count($migrations) == 0) {
					$this->_return .= "\nDatabase already at most current level $current_version\n";
					return;
				}
				$result = $this->run_migrations($migrations, $direction, $destination);
			} catch (Exception $ex) {
				throw $ex;
			}
		}
	/**	run_migrations($migrations, $target_method, $destination)		Run migrations
		*	@param	array		$migrations				Migrations to run
		*	@param	string	$target_method		Direction to migrate (up/down)
		*	@param	string	$destination			Version to migrate to
		*	@return	array
		*/
		private function run_migrations($migrations, $target_method, $destination) {
			$last_version = -1;
			foreach ($migrations as $file) {
				$full_path = $this->_migratorDirs[$file['module']] . DIRECTORY_SEPARATOR . $file['file'];
				if(is_file($full_path) && is_readable($full_path) ) {
					Log::out($full_path);
					require_once $full_path;
					$klass = Ruckusing_Util_Naming::class_from_migration_file($file['file']);
					Log::out($klass);
					$obj = new $klass($this->_adapter);
					$start = $this->start_timer();
					try {
						//start transaction
						$this->_adapter->start_transaction();
						$result =  $obj->$target_method();
						//successfully ran migration, update our version and commit
						$this->_migrator_util->resolve_current_version($file['version'], $target_method);
						$this->_adapter->commit_transaction();
					} catch (Ruckusing_Exception $e) {
						$this->_adapter->rollback_transaction();
						//wrap the caught exception in our own
						throw new Ruckusing_Exception(sprintf("%s - %s", $file['class'], $e->getMessage()),	Ruckusing_Exception::MIGRATION_FAILED);
					}
					$end = $this->end_timer();
					$diff = $this->diff_timer($start, $end);
					$this->_return .= sprintf("%s\t========== %s ========== (%.2f)\n", $file['version'], $file['class'], $diff);
					$this->_return .= $obj->result;
					$last_version = $file['version'];
					$exec = true;
				}
			}
			//update the schema info
			$result = array('last_version' => $last_version);
			return $result;
		}

		/**
		 * Start Timer
		 *
		 * @return int
		 */
		private function start_timer()
		{
				return microtime(true);
		}

		/**
		 * End Timer
		 *
		 * @return int
		 */
		private function end_timer()
		{
				return microtime(true);
		}

		/**
		 * Calculate the time difference
		 *
		 * @param int $s the start time
		 * @param int $e the end time
		 *
		 * @return int
		 */
		private function diff_timer($s, $e)
		{
				return $e - $s;
		}

	/**	verify_environment()							Check the environment and create the migration dir if it doesn't exists	*/
		private function verify_environment() {
			$this->_migratorDirs = $this->get_framework()->migrations_directories();
			// create the migrations directory if it doesnt exist
			foreach ($this->_migratorDirs as $name => $path) {
				if(!is_dir($path)) {
					$this->_return .= sprintf("\n\tMigrations directory (%s) doesn't exist, attempting to create.", $path);
					if (mkdir($path, 0755, true) === FALSE) {
						$this->_return .= sprintf("\n\tUnable to create migrations directory at %s, check permissions?", $path);
					} else {
						$this->_return .= sprintf("\n\tCreated OK");
					}
				}
				//check to make sure our destination directory is writable
				if (!is_writable($path)) {
					throw new Ruckusing_Exception("ERROR: Migrations directory '".$path."' is not writable by the current user. Check permissions and try again.\n", Ruckusing_Exception::INVALID_MIGRATION_DIR);
				}
			}
		}

		/**
		 * Create the schema
		 *
		 * @return boolean
		 */
		private function auto_create_schema_info_table()
		{
				try {
						$this->_return .= sprintf("\n\tCreating schema version table: %s", RUCKUSING_TS_SCHEMA_TBL_NAME . "\n\n");
						$this->_adapter->create_schema_version_table();

						return true;
				} catch (Exception $e) {
						throw new Ruckusing_Exception(
										"\nError auto-creating 'schema_info' table: " . $e->getMessage() . "\n\n",
										Ruckusing_Exception::MIGRATION_FAILED
						);
				}
		}

		/**
		 * Return the usage of the task
		 *
		 * @return string
		 */
		public function help()
		{
				$output =<<<USAGE

\tTask: db:migrate [VERSION]

\tThe primary purpose of the framework is to run migrations, and the
\texecution of migrations is all handled by just a regular ol' task.

\tVERSION can be specified to go up (or down) to a specific
\tversion, based on the current version. If not specified,
\tall migrations greater than the current database version
\twill be executed.

\tExample A: The database is fresh and empty, assuming there
\tare 5 actual migrations, but only the first two should be run.

\t\tphp {$_SERVER['argv'][0]} db:migrate VERSION=20101006114707

\tExample B: The current version of the DB is 20101006114707
\tand we want to go down to 20100921114643

\t\tphp {$_SERVER['argv'][0]} db:migrate VERSION=20100921114643

\tExample C: You can also use relative number of revisions
\t(positive migrate up, negative migrate down).

\t\tphp {$_SERVER['argv'][0]} db:migrate VERSION=-2

USAGE;

				return $output;
		}

}
