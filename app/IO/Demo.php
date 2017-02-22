<?php
/**
 * Enthält IO-Routinen für die Demo-Routinen
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-04-26	eb
 *
 */
class IO_Demo extends IO_Base {
	
	const CHECK_LOGIN = false;
	const ACCESS = '';

	/**
	 * Ermittelt die Daten zum upper-Grid (app.demo.grid)
	 */
	public function upper_get() {
		$select = Db::select()
		->from('user', array(
			'id',
			'loginname',
			'username',
			'language',
			'usergroup_id'
		));
		return Util::jsonTable($select, $this->params);
	}

	public function upper_set() {
		$user = Db::tUser()->find($this->params->id);	/*	@var $user Model_User	*/
		switch($this->params->field) {
			case 'usergroup_id':	$user->setUsergroupId($this->params->value);		break;		
		}
		$user->save();
		return Util::jsonSuccess();
	}
	
	/**
	 * Ermittelt die Daten zum lower-Grid (app.demo.grid)
	 */
	public function lower_get() {
		/*
		 * Initialisiert Benutzerparameter für Admin-, User-, Guest
		 */
		foreach(array('Guest','Admin') as $username) {
 			$user = Db::tUser()->{'get'.$username}();
			foreach(array('A','B','C','D','E') as $key => $paramKey) {
				$user->getParam($paramKey,$username.'_'.$key);
			}	
		}
		
		$select = Db::select()
		->from('userparam', array(
			'id',
			'paramkey as string',
			'paramvalue as text',
			'current_date() as date',
			'current_time() as time',
			'("#ABCDEF") as color',
			'(pow(-1,round(rand())) * round(rand()*10000000)) as int',
			'(pow(-1,round(rand())) * round(rand()*10000,2)) as float',
			'(pow(-1,round(rand())) * round(rand()* 100,4)) as percent',
			'(round(rand())) as bool',
			'(round(rand()*2)-1) as trend',
			'(round(rand()*2)-1) as trend-',
			'(round(rand()*2)-1) as redlight',
			'("MyS3cr8P@ssw0rd") as password'
		))
		->where('user_id=?',$this->params->parentid);
				
		return Util::jsonTable($select, $this->params);
		
		
	}

	/**
	 * Aktualisiert ein Attribut zum lower-Grid (app.demo.gri)
	 */
	public function lower_set() {
		$param = Db::tUserparam()->find($this->params->id);
		if(!$param)		return Util::jsonError('invalid record');
 		switch($this->params->field) {
 			case 'string':			$param->setParamkey($this->params->value);		break;
 			case 'text':				$param->setParamvalue($this->params->value);	break;
 		}
 		$param->save();
 		return Util::jsonSuccess();
	}

	public function getLog() {
		$log = new Util_Log(array(
		));
		return $log->getJson();
	}
	
	public function sleep() {
		$seconds = $this->params->seconds;
		sleep($seconds);
	}
	
}