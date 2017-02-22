<?php
/**
 * Tabelle userparam
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-01-11	eb	from scratch
 */
class Model_UserparamTable extends Model_BaseTable {

	protected $_referenceMap    = array(
		/*
		 * Ein Eintrag gehört zu einem Benutzer
		 */
		'User' => array(
			'columns'				=>	'user_id',
			'refTableClass'	=>	'Model_UserTable',
			'refColumns' 		=>	'id'
		)
	);

}