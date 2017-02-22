<?php
/**
 * Systemmanagment
 * @author		Dominik Uehlinger <dominik.uehlinger@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2016-05-10	du	from scratch
 */
class IO_Systemmanagement extends IO_Base {
	
	const CHECK_LOGIN = true;
	const ACCESS = 'core_systemmanagement';

	
	/*
	 * Module
	 */
	
	/** 
	 * Ermittelt ein Module
	 */
	public function module_get()	{
		if(!User::canRead('core_systemmanagement'))			return $this->jsonNoAccess();
		return Db::tModule()->getJsonTable($this->params);
	}
	
	/**
	 * Bearbeiten eines Modules
	 */
	public function module_set()	{
		if(!User::canRead('core_systemmanagement'))			return $this->jsonNoAccess();
		$t = Db::tModule();
		
		switch ($this->params->field)		{
			case 'active':
				if($this->params->value == 'true'){
					$this->params->value=1;
				}	else{
						$this->params->value = 0;
				}
				
				$t->update(array('active'	=>	$this->params->value), 'id='.$this->params->id);
			
				break;
		}
		
		return $this->jsonResponse();
	}
	
	/** 
	 * Erstellt einen Excel Report
	 */
	public function module_excel()	{
		if(!User::canRead('core_systemmanagement'))			return $this->jsonNoAccess();
		$xl = New Export_XL(array(
			'browser'			=>	true,
			'report'			=>	$this->_('Modules'),
			'orientation'	=>	'landscape',
			'pageswide'		=>	1
		));
		
		$xl->addSheet($this->_('Modules'));
		$xl->setColumnWidth(35,1,2);
		$xl->setColumnWidth(10,3,4);
		$xl->setColumnWidth(80,5,5);
		
		$modules = Db::tModule()->fetchAll();
		$xl->write(array(
			$this->_('Code'),
			$this->_('Name'),
			$this->_('Active'),
			$this->_('Sort'),
			$this->_('Description')
		));
		
		$xl->newline();
		
		foreach($modules as $module) {
			$xl->write(array(
				$module->getCode(),
				$module->getName(),
				$module->getActive(),
				$module->getSort(),
				$module->getDescription()
			));
			$xl->newline();
		
		}
		
		$xl->save();
	}
	
	/*
	 * Userlog
	 */
	
	/**
	 * Ermittelt die User-Logins
	 */
	public function userlog_get()	{
		if(!User::canRead('core_systemmanagement'))			return $this->jsonNoAccess();
		
		$select = Db::select()
		->from('userlog')
		->joinLeft('user', 'userlog.user_id=user.id', array('loginname', 'username'))
		->joinLeft('usergroup', 'user.usergroup_id=usergroup.id', array('name as usergroup'));
		if($this->params->userid) $select->where('userlog.user_id='.$this->params->userid);
		return Util::jsonTable($select, $this->params);		
	}
	
	/**
	 * Erzeugt einen Excel-Bericht für die User Logins
	 */
	public function userlog_excel() {
		if(!User::canRead('core_systemmanagement'))			return $this->jsonNoAccess();
		$xl = New Export_XL(array(
			'browser'			=>	true,
			'report'			=>	$this->_('Userlog'),
			'orientation'	=>	'landscape',
			'pageswide'		=>	1
		));
	
		$xl->addSheet($this->_('Userlog'));
		$xl->setColumnWidth(25,1,4);
		$xl->setColumnWidth(70,5,5);
		
		$select = Db::select()
		->from('userlog')
		->joinLeft('user', 'userlog.user_id=user.id', array('loginname', 'username'))
		->joinLeft('usergroup', 'user.usergroup_id=usergroup.id', array('name as usergroup'));
		if($this->params->userid)	$select->where('userlog.user_id='.$this->params->userid);
		$res = $select->query()->fetchAll();
		
		$xl->write(array(
			$this->_('User'),
			$this->_('Usergroup'),
			$this->_('Date'),
			$this->_('Host'),
			$this->_('Agent')
		));
		
		$xl->newline();
		
		foreach ($res as $row)	{
			$xl->write(array(
				$row->username,
				$row->usergroup,
				$row->date,
				$row->host,
				$row->agent
			));
			$xl->newline();
			
		}
		
		$xl->save();
	}
	
	/*
	 *	Overview 
	 */
	
	/**
	 * Ermittelt eine HTML-Sequenz mit der Systemübersicht
	 * @return	string
	 */
	public function getOverview() {
		
		//	Abstract Output
		$rc = "<style>"
			.	"	div #maintenance_overview td {font-family:tahoma,verdana,arial,sans-serif; font-size:11px;vertical-align:top;}"
			.	"	div #maintenance_overview th {font-family:tahoma,verdana,arial,sans-serif; font-size:11px;vertical-align:center;font-weight:bold;text-decoration:underline;"
			.	"</style>"
			.	"<div id='maintenance_overview'>"
			.	"<table cellpadding=0 cellspacing=5>"
			.	"	<tr><td width='180px'><b>{Software-Version}:</b></td><td>{%version} ({Revision}: {%revision})</td></tr>"
			.	"	<tr><td><b>{Database-Information}:</b></td><td>"
			.	"	<table cellpadding=0 cellspacing=0>"
			.	"		<tr><td width=150>{Database name}:</td><td>{%dbname}</td></tr>"
			.	"		<tr><td>{Database revision}:</td><td>{%dbrevision}{%do_update}</td></tr>"
			.	"		<tr><td>{Server uptime}:</td><td>{%uptime}</td></tr>"
			.	"		<tr><td>{Connections}:</td><td>{%connections}</td></tr>"
			.	"		<tr><td>{Number of objects}:</td><td>{%number_of_objects}</td></tr>"
			.	"		<tr><td>{Slow queries}:</td><td>{%slow_queries}</td></tr>"
			.	"	</table>"
			.	"	</td></tr>"
			.	"	<tr><td><b>{System-Information}</b></td><td>"
			.	"		<table cellpadding=0 cellspacing=0>"
			.	"			<tr><td width=150>{Operatingsystem}:</td><td>{%os}</td></tr>"
			.	"			<tr><td>{System memory}:</td><td>{%total_memory}</td></tr>"
			.	"			<tr><td>{PHP usage}:</td><td>{%phpusage}</td></tr>"
			.	"			<tr><td>{CPU load}:</td><td>{%cpuload}</td></tr>"
			.	"		</table>"
			.	"	<tr><td><b>{Systemcheck}</b></td><td>"
			.	"		<table cellpadding=0 cellspacing=0>"
			.	"			<tr><th width=150>{Component}</th><th width=100>{Required}</th><th width=100>{Installed}</th></tr>"
			.	"			{%components}"
			.	"		</table>"
			.	"</table>"
			.	"</div>";
		
		return $rc;
	}
	
	/*
	 * Debug
	 */
	
	/**
	 * Ermittelt den Inhalt von debug.log
	 * @return	string
	 */
	public function getDebug() {
		if(!User::canRead('core_systemmanagement'))			return $this->jsonNoAccess();
		$str = '<pre>';
		$str .= file_get_contents('debug.log');
		$str .= '</pre>';
		return $str;
	}
	
	/**
	 * Löscht das Debug-Protokoll
	 */
	public function clearDebug() {
		if(!User::canRead('core_systemmanagement'))			return $this->jsonNoAccess();
		file_put_contents('debug.log', '');
		Log::out($this->_('{0} cleared by {1}',array('debug.log',db::tUser()->fetchRow('id='.$_SESSION['userid'])->getLoginname())));
	}
	
	/*
	 * Session
	 */
	
	/**
	 * Ermittelt die Eingeträgen Sessions
	 */
	public function session_get()	{
		if(!User::canRead('core_systemmanagement'))			return $this->jsonNoAccess();
		$cnt=0;
		
		$select = Db::select()
			->from('session')
			->joinLeft('user', 'session.user_id=user.id', array('loginname', 'username'))
			->joinLeft('usergroup', 'user.usergroup_id=usergroup.id', array('name as usergroup'));
		$res  = $select->query()->fetchAll();
		
		foreach($res as $row){
			$actual = date::get('now', 'unix');
			$res = ($actual-($row->modified+$row->lifetime))*-1;
			
			// Bestimmen der Stunden
			$houres = floor($res/3600);
			if($houres>0 && $res>=3600) {
				$rest = $res - ($houres*3600);
			}	else{
				$rest = $res;
			}
			
			// Bestimmt die Minuten und Sekunden
			if($rest>0){
				$minutesDetailed = $rest/60;
				$minutes = floor($minutesDetailed);
					
			}	else{
				$minutes=0;
			}
			
			if($minutes>0 && $minutes<60) {
				$seconds = $rest-($minutes*60);
			}	else{
				$seconds = $rest;
			}
			
			if($minutes<10) $minutes = '0'.$minutes;
			if($seconds<10) $seconds = '0'.$seconds;
			$timeRest = '0'.$houres.':'.$minutes.':'.$seconds;
			
			$rs[]	= array(
				'id'							=>	$row->id,
				'user_id'					=>	$row->user_id,
				'start'						=>	date::get($row->modified,'Y-m-d-H-i-s'),
				'modified'				=>	$row->modified,
				'lifetime'				=>	$row->lifetime,
				'data'						=>	$row->data,
				'loginname'				=>	$row->loginname,
				'username'				=>	$row->username,
				'usergroup'				=>	$row->usergroup,
				'remainingTime'		=>	$timeRest	
			);
			$cnt++;
			
		}
		
		return Zend_Json::encode(array(
			'success'				=>	true,
			'totalCount'		=>	$cnt,
			'data'					=>	$rs
		));
		
	}

	/**
	 * Löscht einen Session Eintrag aus der Datenbank
	 */
	public function session_del()	{
		if(!User::canRead('core_systemmanagement'))			return $this->jsonNoAccess();
		if($_SESSION['userid'] == Db::tSession()->fetchRow('id='.Db::quote($this->params->id))->getUserID())			return $this->jsonError($this->_('You could not delete your own Session'));

		Db::tSession()->delete('id='.Db::quote($this->params->id));
		return $this->jsonResponse($this->_('Session successfully deleted'));
	}
	
}