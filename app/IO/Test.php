<?php
/**
 * Enthält Quick-Testroutinen
 * @author		Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2015-12-16	eb	from scratch
 *
 */
class IO_Test extends IO_Base {
	
	const CHECK_LOGIN = false;
	const ACCESS = '';
	
	/**
	 * Haupt-Einstiegspunkt für die Testroutine
	 * Der Parameter method verweist auf die Ziel-Methode
	 */
	public function run() {
		$method = $this->params->method;
		if(!$method)		$method = 'quick';
		$silent = in_array($method, array('Export','Excel'));
		if($silent) {
			if(!$_SESSION['ExportEmptyPage']) {
				$_SESSION['ExportEmptyPage'] = true;
				$this->out('calling '.$method.' at next request');
				return;
			}
		} else {
			$_SESSION['ExportEmptyPage'] = false;
			header('content-type:text/html; charset=utf-8');
			echo('<head><style type="text/css">body {font-family:courier;font-size:12px}</style></head><body>');
			$this->out('***   Start   ***');
			$t0 = microtime(true);
		}
		if(!method_exists($this,$method)) {
			$this->err('Method IO_Test->'.$method.'() not implemented yet');
		} else {
			try {		
				$ret = $this->{$method}();
			} catch(Exception $e) {
				$this->err($e->getMessage());
			}
		}
		if($ret)		$this->out($ret);
		if(!$silent)		$this->out('***   Finished in '.number_format(microtime(true)-$t0, 2).'s   ***');
	}
	
	/**
	 * Erzeugt eine Ausgabe
	 * @param mixed $message
	 */
	private function out($message) {
		echo(Util::dump($message,0,true));
		flush();
		ob_flush();
		Log::out($message);
	}
	
	/**
	 * Erzeugt eine rot gefärbte Ausgabe
	 * @param mixed $message
	 */
	private function err($message) {
		echo('<font color="red">');
		echo(Util::dump($message,0,true));
		echo('</font>');
		flush();
		ob_flush();
	}

	////////////////////
	//	Test-Methoden	//
	////////////////////
	
	private function quick() {
		
		
	}
		
	public function Log() {
		$log = new Util_Log;
		$log->reset();
		
		$main = $this->params->main;		//	Anzahl Haupt-Iterationsschritte
		$part = $this->params->part;		//	Anzahl Teil-Iterationsschritte
		$sub = $this->params->sub;			//	Anzahl Sub-Iterationsschritte
		if(!$main)			$main = 3;
		if(!$part)			$part = 5;
		if(!$sub)				$sub = 10;
		$log->counter($main);
		for($i=1; $i<=$main; $i++) {
			$log->counterPart($part);
			for($j=1; $j<=$part; $j++) {
				$log->counterSub($sub);
				for($k=1; $k<=$sub; $k++) {
					if(!$log->active())		return;
					$log->counterSub();
					$this->out('Sub: '.$log->getState('progress'));
					sleep(1);
				}
				$log->counterPart();
				$this->out('Part: '.$log->getState('progress'));
			}
			$log->counter();
			$this->out('Main: '.$log->getState('progress'));
		}
		$log->stop();
	}
	
	public function getLog() {
		$log = new Util_Log();
		$this->out($log->readState());
	}
	
	public function getShortList() {
		$data = array();
		$rs = Db::select()->from('user', array('id', 'loginname', 'username'))->order('loginname')->query()->fetchAll();
		foreach($rs as $rec) {
			$data[] = array(
				'id'					=>	$rec->id,
				'value'				=>	$rec->loginname,
				'description'	=>	$rec->username
			);
		}
		return Zend_Json::encode(array(
			'data'	=>	$data,
		));
	}
	
	public function getPlz() {
		$data = array();
		$select = Db::select()->from('plz', array('PlzID as id', "concat(Plz,' ',Ort) as value"))->order('Plz');
		if($this->params->query) {
			$select = $select->where("concat(PLZ,' ',Ort) like '%".$this->params->query."%'");
		}
		$rs = $select->query()->fetchAll();
		return Zend_Json::encode(array(
			'data'	=>	$rs
		));
	}
	
}