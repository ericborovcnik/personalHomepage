<?php
abstract class Registry {
	
	private static $vars = array();

	public static function get($key) {
		if (isset(self::$vars[$key])) {
			return self::$vars[$key];
		}
		return null;
	}

	public static function set($key, $val) {
		self::$vars[$key] = $val;
	}


	/**
	 * @var Model_Customer
	 */
	private static $Customer;

}