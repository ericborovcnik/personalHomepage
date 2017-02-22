<?php
/**
 * Tabelle userlog
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-18	eb	from scratch
 */
class Model_UserlogTable extends Model_BaseTable {

	/**
	 * Ein Protokolleintrag gehört zu einem Benutzer
	 */
	protected $_referenceMap    = array(
		'User' => array(
			'columns'				=>	'user_id',
			'refTableClass'	=>	'Model_UserTable',
			'refColumns' 		=>	'id'
		)
	);

}