<?php
abstract class Model_Base extends Zend_Db_Table_Row {

	public function getId() {
		return $this->__get('id');
	}

}