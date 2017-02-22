<?php
/**	Ruckusing_Util_Migrator
	*	@author			Cody Caughlan <codycaughlan@gmail.com>
	*	@author			Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
	*	@version		2014-10-07	eb	revis-Adaption
	*/

class Ruckusing_Util_Migrator
{
////////////////////
//	Declarations	//
////////////////////
	/**	_adapter													Adapter
		*	@var	Ruckusing_Adapter_Base
		*/
		private $_adapter = null;
	/**	_migrations												Migrations
		*	@var	array
		*/
		private $_migrations = array();

//////////////////////
//	Public methods	//
//////////////////////
	/**	__construct($adapter)							Creates an instance of Ruckusing_Util_Migrator
		*	@param	Ruckusing_Adapter_Base	$adapter		The current adapter being used
		*	@return	Ruckusing_Util_Migrator
		*/
		public function __construct($adapter) {
			$this->setAdapter($adapter);
		}
	/**	setAdapter($adapter)							Set Adapter
		*	@param	Ruckusing_Adapter_Base	$adapter		The current adapter
		*	@return	Ruckusing_Util_Migrator
		*/
		public function setAdapter($adapter) {
			if (!($adapter instanceof Ruckusing_Adapter_Base)) {
				throw new Ruckusing_Exception('Adapter must be implement Ruckusing_Adapter_Base!', Ruckusing_Exception::INVALID_ADAPTER);
			}
			$this->_adapter = $adapter;
			return $this;
		}
	/**	get_max_version()									Return the max version number from DB or 0 if not available
		*	@return	string
		*/
		public function get_max_version() {
			if (defined('APP_BRANCH_DO_MIGRATE')) {
				$result = $this->_adapter->select_one(sprintf("SELECT version FROM schema_info WHERE branch='%s'", APP_BRANCH));
				if (!$result['version']) {
					$result['version'] = '1';
					$this->_adapter->execute(sprintf("INSERT INTO schema_info SET branch='%s', version=1", APP_BRANCH));
				}
				return $result['version'];
			} else {
				try {
					$result = $this->_adapter->select_one(sprintf("SELECT version from %s WHERE branch = ''", RUCKUSING_SCHEMA_TBL_NAME));
				} catch (Exception $e) {
// 					if (!is_int(stripos($e->getMessage(), "Unknown column"))) {
// 						throw $e;
// 					}
					$result = $this->_adapter->select_one(sprintf("SELECT version from %s", RUCKUSING_SCHEMA_TBL_NAME));
				}
				return $result['version'];
			}
		}
	/**	get_runnable_migrations($directories, $direction, $destination=null, $use_cache=true)
		*	This methods calculates the actual set of migrations that should be performed, taking into account
		*	the current version, the target version and the direction (up/down). When going up this method will
		*	skip migrations that have not been executed, when going down this method will only include migrations
		*	that have been executed.
		*	@param	array		$directories			the migration dirs
		*	@param	string	$direction				up/down
		*	@param	string	$destination			the version to migrate to
		*	@param	boolean	$use_cache				the current logger
		*	@return	array
		*/
		public function get_runnable_migrations($directories, $direction, $destination = null, $use_cache = true) {
			// cache migration lookups and early return if we've seen this requested set
			if($use_cache == true) {
				$key = $direction.'-'.$destination;
				if (array_key_exists($key, $this->_migrations)) {
					return($this->_migrations[$key]);
				}
			}
			$runnable = array();
			$migrations = array();
			$migrations = $this->get_migration_files($directories, $direction);
			$current = $this->find_version($migrations, $this->get_max_version());
			$target = $this->find_version($migrations, $destination);
			if(is_null($target) && !is_null($destination) && $destination > 0) {
				throw new Ruckusing_Exception("Could not find target version {$destination} in set of migrations.",	Ruckusing_Exception::INVALID_TARGET_MIGRATION);
			}
			$start = $direction == 'up' ? 0 : array_search($current, $migrations);
			$start = $start !== false ? $start : 0;
			$finish = array_search($target, $migrations);
			$finish = $finish !== false ? $finish : (count($migrations) - 1);
			$item_length = ($finish - $start) + 1;
			$runnable = array_slice($migrations, $start, $item_length);
			//dont include first item if going down but not if going all the way to the bottom
			if ($direction == 'down' && count($runnable) > 0 && $target != null) {
				array_pop($runnable);
			}
			$executed = $this->get_executed_migrations();
			$to_execute = array();
			foreach ($runnable as $migration) {
				//Skip ones that we have already executed
				if ($direction == 'up' && in_array($migration['version'], $executed)) 			continue;
				//Skip ones that we never executed
				if ($direction == 'down' && !in_array($migration['version'], $executed)) 		continue;
				$to_execute[] = $migration;
			}
			if ($use_cache == true) {
				$this->_migrations[$key] = $to_execute;
			}
			return($to_execute);
		}
	/**	generate_timestamp()							Genearte a timestamp for the current time in UTC-Format
		*	@return	string										Timestamp like 20090122193325
		*/
		public static function generate_timestamp()	{
			return gmdate('YmdHis', time());
		}
	/**	resolve_current_version($version, $direction)	If we are going UP then log this version as executed.
		*																		If going down then delete this version from our set of executed migrations
		*	@param	string	$version					the version
		*	@param	string	$direction				up/down
		*	@return	string
		*/
		public function resolve_current_version($version, $direction)	{
			if ($direction === 'up') {
				$this->_adapter->set_current_version($version);
			}
			if ($direction === 'down') {
				$this->_adapter->remove_version($version);
			}
			return $version;
		}
	/**	get_executed_migrations()					Returns array of strings which represent version number that we *have* migrated
		*	@return	array
		*/
		public function get_executed_migrations() {
			return $this->executed_migrations();
		}
	/**	get_migration_files($directories, $direction)	Return a set of migration files, according to the given direction.
		*																		If nested, then return a complex array with the migration parts broken up into parts
		*																		which make analysis much easier.
		*	@param	array		$directories			The migration dirs
		*	@param	string	$direction				Direction up/down
		*	@return	array
		*/
		public static function get_migration_files($directories, $direction) {
			$valid_files = array();
			foreach ($directories as $name => $path) {
				if (!is_dir($path)) {
					if (mkdir($path, 0755, true) === FALSE) {
						throw new Ruckusing_Exception("\n\tUnable to create migrations directory at %s, check permissions?", $path,	Ruckusing_Exception::INVALID_MIGRATION_DIR);
					}
				}
				$files = scandir($path);
				$file_cnt = count($files);
				if($file_cnt > 0) {
					for ($i = 0; $i < $file_cnt; $i++) {
						if(preg_match('/^(\d+)_(.*)\.php$/', $files[$i], $matches)) {
							if(count($matches) == 3) {
								$valid_files[] = array(
									'name' 		 =>  $files[$i]
									,'module'  =>  $name
								);
							}
						}
					}
				}
			}
			usort($valid_files, array("Ruckusing_Util_Migrator", "migration_compare")); //sorts in place
			if ($direction == 'down') {
				$valid_files = array_reverse($valid_files);
			}
			//user wants a nested structure
			$files = array();
			$cnt = count($valid_files);
			for($i = 0; $i < $cnt; $i++) {
				$migration = $valid_files[$i];
				if(preg_match('/^(\d+)_(.*)\.php$/', $migration['name'], $matches)) {
					$files[] = array(
						'version'	=>	$matches[1]
						,'class'	=>	$matches[2]
						,'file'		=>	$matches[0]
						,'module'	=>	$migration['module']
					);
				}
			}
			return $files;
		}
	/**	find_version($migrations, $version, $only_index=false)		Find the specified structure (representing a migration) that matches the given version
		*	@param	array		$migrations				The list of migrations
		*	@param	string	$version					The verison being searched
		*	@param	boolean	$only_index				Wheter to only return the index of the version
		*	@return	null | integer | array
		*/
		public function find_version($migrations, $version, $only_index = false) {
			$len = count($migrations);
			for ($i = 0; $i < $len; $i++) {
				if ($migrations[$i]['version'] == $version) {
					return $only_index ? $i : $migrations[$i];
				}
			}
			return null;
		}

//////////////////////
//	Private methods //
//////////////////////
	/**	migration_compare($a, $b)					Custom comparator for migration sorting
		*	@param	array		$a								first migration structure
		*	@param	array		$b								second migration struction
		*	@return	integer
		*/
		private static function migration_compare($a, $b) {
			return strcmp($a["name"], $b["name"]);
		}
	/**	find_version_index($migrations, $version)	Find the index of the migration in the set of migrations that match the given version
		*	@param	array		$migrations				List of migrations
		*	@param	string	$version					The version being searched
		*	@return	integer
		*/
		private function find_version_index($migrations, $version) {
			//edge case
			if(is_null($version))				return null;
			$len = count($migrations);
			for ($i = 0; $i < $len; $i++) {
				if ($migrations[$i]['version'] == $version) {
					return $i;
				}
			}
			return null;
		}
	/**	executed_migrations()							Query the database and return a list of migration versions that *have* been executed
		*	@return	array
		*/
		private function executed_migrations() {
			//	do nothing
			return array();
		}
}