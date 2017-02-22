<?php
/**
 * Ruckusing_Factory		DB-Update-Tools
 * @author			Eric Borovcnik <eric.borovcnik@wb-informatik.ch>
 * @copyright	Copyright (c) WB Informatik AG (http://www.wb-informatik.ch)
 * @version		2014-01-23	eb	addSystemParam
 * @version		2014-04-23	eb	removeTemplates
 * @version		2014-10-07	eb	Review; Comments; Ruckusing-Framework-Update
 * @version		2015-05-23	eb	resetLanguage
 */
class Ruckusing_Factory extends Ruckusing_Migration_Base {

	// ////////////////////////
	// Instance variables //
	// ////////////////////////
	/**
	 *
	 * @var string $result stores string-messages from update-log
	 */
	public $result = '';

	// ////////////////////
	// Public methods //
	// ////////////////////
	/**
	 * Called by framework to raise the db-level
	 */
	public function up() {
		$this->execute ( 'SET NAMES utf8' );
		$this->execute ( 'SET CHARACTER SET utf8' );
		$this->run ();
	}

	/**
	 * Stellt sicher, dass bestimmte Benutzergruppen keine Zugriffsrechte tragen
	 * @param string $accessObject				Zugriffsobjekt aus usraccessobject
	 * @param string $usrgrp							Komma-separierte Liste mit Benutzergruppen inkl. Wildcards
	 */
	protected function disableAccessObject($accessObject, $usrgrp='%') {
		$usraccessobject = $this->select_one('select UsrAccessObjectID from usraccessobject where UsrAccessObject = "'.$accessObject.'"');
		if(!$usraccessobject)		return;
		$this->removeAccess($usrgrp, 'UsrAccessObjectID', $usraccessobject['UsrAccessObjectID']);
	}
	
	/**
	 * Entfernt das Zugriffsrecht zu einer Ausgabeklasse. Wird das Recht für alle Gruppen entzogen, dann wird die Ausgabe invisible
	 * @param string $exportclass
	 * @param string $usrgrp
	 */
	protected function disableExport($exportclass, $usrgrp='%') {
		$exportId = $this->select_one('select AuswertungID from auswertung where AuswertungClass="'.$exportclass.'"');
		if(!$exportId)		return;
		$exportId = $exportId['AuswertungID'];
		$this->removeAccess($usrgrp, 'AuswertungID', $exportId);
		if($usrgrp == '%')		$this->updateRecord('auswertung', array('AuswertungVisible'=>0), 'AuswertungID='.$exportId);
	}
	
	/**
	 * Entfernt das Zugriffsrecht zu einer Importklasse. Wird das Recht für alle Gruppen entzogen, dann wird die Importklasse invisible
	 * @param string $importclass
	 * @param string $usrgrp
	 */
	protected function disableImport($importclass, $usrgrp='%') {
		$importId = $this->select_one('select ImportFileTypeID from importfiletype where ImportClass="'.$importclass.'"');
		if(!$importId)		return;
		$importId = $importId['ImportFileTypeID'];
		$this->removeAccess($usrgrp, 'ImportFileTypeID', $importId);
		if($usrgrp == '%')		$this->updateRecord('importfiletype', array('ImportVisible'=>0), 'ImportFileTypeID='.$importId);
	}
	
	/**
	 * Never called - framework may downgrade db-level
	 */
	public function down() {
	}
	
	/**
	 * Ermittelt eine Zeichenkette mit dem Dump-String für Debug-Meldungen
	 * @param mixed $var									Ausgabe-Meldung als string, array oder stdClass
	 * @param integer $ident							Einrückung für Sub-Meldungen
	 * @return string
	 */
	private function dump($var=false, $ident=0) {
		$rc = '';
		$tab = str_Pad('', $ident*3, ' ');
		if(is_array($var)) {
			$rc .= 'array('.count($var).") {\n";
			$ident++;
			$tab = str_Pad('', $ident*4, ' ');
			$max=0;
			foreach($var as $key => $item) {
				if(strlen($key) > $max)	$max=strlen($key);
			}
			foreach($var as $key => $item) {
				$out = $tab . '[' . $key . ']';
				for($i=0;$i<=($max - strlen($key));$i++) {
					$out .= ' ';
				}
				$rc .= $out . '=>  ';
				if(is_array($item) || is_object($item)) {
					$rc .= self::dump($item, $ident);
				} elseif(is_bool($item)) {
					$rc .= $item ? "true\n" : "false\n";
				} else {
					$rc .= $item . "\n";
				}
			}
			$ident--;
			$tab = str_Pad('', $ident*3*6, ' ');
			$rc .= $tab . "}\n";
		} elseif(is_object($var)) {
			$properties = get_object_vars($var);
			$rc .= 'object(' . count($properties) . ") { \n";
			$ident++;
			$tab = str_Pad('', $ident*4, ' ');
			$max=0;
			foreach($properties as $key => $item) {
				if(strlen($key) > $max) $max = strlen($key);
			}
			foreach($properties as $key => $item) {
				$out = $tab . '->' . $key;
				for($i=0;$i<=($max - strlen($key));$i++) {
					$out .= ' ';
				}
				$rc .= $out . '= ';
				if(is_array($item) || is_object($item)) {
					$rc .= self::dump($item, $ident);
				} elseif(is_bool($item)) {
					$rc .= $item ? "true\n" : "false\n";
				}	else {
					$rc .= $item . "\n";
				}
			}
			$ident--;
			$tab = str_Pad('', $ident*4, ' ');
			$rc .= $tab . "}\n";
		} elseif(is_bool($var)) {
			$rc .= $var ? "true\n" : "false\n";
		} else {
			$rc .= $var . "\n";
		}
		return $rc;
	}
	
	
	/**
	 * Generic Method has to be implemented by Update-Process
	 */
	public function run() {
	}

	/**
	 * Erstellt Zugriffsrechte an Benutzergruppen, sofern diese noch nicht bestehen
	 *
	 * @param string $usrGroups						Komma-separierte Liste mit Benutzergruppen (inkl. Widlcards %)
	 * @param string $accessObject
	 * @param integer $accessValue
	 */
	protected function addAccess($usrGroups, $accessObject, $accessValue) {
		if ($usrGroups == '')			return;
		// Erstelle Zugriffsrechte zur Auswertung für alle Benutzergruppen im Fokus
		$sql = 'select UsrGrpID from usrgrp where ';
		foreach ( explode ( ',', $usrGroups ) as $group ) {
			$sql .= "Name like '$group' or ";
		}
		$sql = substr($sql, 0, -3);
		$rs = $this->select_all ( $sql );
		foreach ( $rs as $group ) {
			$groupid = $group ['UsrGrpID'];
			if ($this->select_one ( "select UsrAccessID from usraccess where UsrGrpID=$groupid and Object='$accessObject' and Value=$accessValue" ))					continue;
			$this->execute ( "insert into usraccess (UsrGrpID, Object, Value) values ($groupid, '$accessObject', $accessValue)" );
		}
	}

	/**
	 * Erstellt ein neues Zugriffsobjekt bzw.
	 * überarbeitet dieses, wenn es schon existiert
	 *
	 * @param string $accessObject
	 * @param string $description
	 * @param boolean $visible
	 * @return integer
	 */
	protected function addAccessObject($accessObject, $description, $visible = false) {
		$data = array (
				'UsrAccessObjectID' => '?',
				'UsrAccessObject' => $accessObject,
				'UsrAccessObjectDesc' => $description
		);
		$id = $this->getValue ( 'usraccessobject', 'UsrAccessObjectID', "UsrAccessObject='$accessObject'" );
		if ($id) {
			if ($visible)
				$data ['Visible'] = 1;
			unset ( $data ['UsrAccessObjectID'] );
			$this->updateRecord ( 'usraccessobject', $data, "UsrAccessObjectID=$id" );
		} else {
			$data ['Visible'] = $visible ? 1 : 0;
			$id = $this->addRecord ( 'usraccessobject', $data );
		}
		return $id;
	}
	
	/**
	 * Fügt einer Tabelle einen Kommentar hinzu
	 *
	 * @param string $table
	 * @param string $comment
	 */
	protected function addComment($table, $comment) {
		$this->execute ( "alter table `$table` comment='$comment'" );
	}
	protected function addConstraint($table, $fields, $fktable, $fkfields, $ondelete = '') {
		$constraint = 'fk_' . $table . '_' . $fktable;
		switch ($ondelete) {
			case 'cascade' :
				$ondelete = 'on update cascade on delete cascade';
				break;
			default :
				$ondelete = 'on update restrict on delete restrict';
				break;
		}
		$this->dropConstraint ( $table, $constraint );
		$this->execute ( "alter table `$table` add constraint `$constraint` foreign key ($fields) references `$fktable` ($fkfields) $ondelete" );
	}

	/**
	 * Erstellt eine neue Auswertung in der Tabelle auswertung
	 * @param string $class								Auswerteklasse
	 * @param string $title								Auswerte-Bezeichnung
	 * @param string $description					Beschreibung zur Auswertung
	 * @param integer $groupId						auswertung.AuswerteGruppeID
	 * @param string $usrGroups						Optionale, Komma-separierte Liste mit Benutzergruppen, für welche die Auswertung freigeschalten wird.
	 */
	protected function addExport($class, $title, $description, $groupId, $usrGroups = '') {
		if ($this->select_one ( "select * from auswertung where AuswertungClass='$class'" )) {
			// Auswertung existiert bereits -> aktualisiere Titel und Beschreibung
			$fields = array ();
			if ($title)
				$fields ['AuswertungName'] = $title;
			if ($description)
				$fields ['AuswertungDesc'] = $description;
			if (count ( $fields ) > 0)
				$this->updateRecord ( 'auswertung', $fields, "AuswertungClass='$class'" );
			return;
		}
		$visible = $usrGroups == '' ? 0 : 1;
		$id = $this->addRecord ( 'auswertung', array (
				'AuswertungID' => '?',
				'AuswertungGruppeID' => $groupId,
				'OrderIdx' => 99,
				'AuswertungVisible' => $visible,
				'AuswertungName' => $title,
				'AuswertungDesc' => $description,
				'AuswertungClass' => $class
		) );
		if ($usrGroups != 'makeVisible')
			$this->addAccess ( $usrGroups, 'AuswertungID', $id );
	}
	protected function addExportGroup($AuswertungGruppe, $orderidx, $visible = 0) {
		if (! $AuswertungGruppe)
			return false;
		if (! $orderidx)
			return false;
		if ($visible != 1 && $visible != 0)
			$visible = 0;

		$exists = $this->select_one ( "SELECT * FROM auswertunggruppe WHERE AuswertungGruppe='$AuswertungGruppe'" );

		if ($exists)
			return false;

		$this->addRecord ( 'auswertunggruppe', array (
				'AuswertungGruppeID' => '?',
				'AuswertungGruppe' => $AuswertungGruppe,
				'orderidx' => $orderidx,
				'visible' => $visible
		) );
	}

	/**
	 * Fügt ein Feld zu einer Tabelle hinzu oder änder die Feldspezifikation,
	 * wenn das Feld bereits exisitert.
	 *
	 * @param string $table
	 * @param string $field
	 * @param string $fieldspec
	 */
	protected function addField($table, $field, $fieldspec) {
		if ($this->hasField ( $table, $field )) {
			$this->execute ( "alter table `$table` change column `$field` `$field` $fieldspec;" );
		} else {
			$this->execute ( "alter table `$table` add column `$field` $fieldspec;" );
		}
	}
	protected function addImport($class, $title, $reader = 'stream', $order = 1, $usrGroups = '') {
		if ($this->select_one ( "select * from importfiletype where ImportClass='$class'" ))
			return; // Auswertung existiert bereits;
		$visible = $usrGroups == '' ? 0 : 1;
		$id = $this->addRecord ( 'importfiletype', array (
				'ImportFileTypeID' => '?',
				'ImportClass' => $class,
				'ImportName' => $title,
				'reader' => $reader,
				'ImportVisible' => $visible,
				'orderidx' => $order
		) );
		$this->addAccess ( $usrGroups, 'ImportFileTypeID', $id );
	}

	/**
	 * Fügt einer Tabelle einen Index hinzu.
	 * Existiert der Index bereits, dann wird dieser gelöscht und neu erstellt
	 *
	 * @param string $table
	 * @param string $index
	 * @param string $fields
	 * @param string $type
	 *        	'spatial', 'fulltext'
	 */
	protected function addIndex($table, $index, $fields, $type = '') {
		$this->dropIndex ( $table, $index );
		$this->execute ( "alter table `$table` add $type index `$index` ($fields)" );
	}

	/**
	 * Erstellt einen Spracheintrag, wenn dieser noch nicht existiert bzw.
	 * ersetzt diesen, wenn $force=true
	 *
	 * @param string $module
	 *        	oder Postfix . wird im Frontend berücksichtigt)
	 * @param string $key
	 * @param string $de_CH
	 *        	(=$key)
	 * @param string $de_DE
	 *        	(=$de_CH)
	 * @param boolean $force
	 *        	einen allfällig existierenden Datensatz
	 */
	protected function addLanguage($module, $key, $de_CH = '', $de_DE = '', $force = false) {
		if ($de_CH == '')
			$de_CH = $key;
		if ($de_DE == '')
			$de_DE = $de_CH;
		$id = $this->getValue ( 'language', 'LanguageID', "Module='$module' and LanguageKey='$key'" );
		if (! $id) {
			$this->execute ( "insert into language (Module, LanguageKey, de_CH, de_DE) values ('$module', '$key', '$de_CH', '$de_DE')" );
		} else if ($force) {
			$this->execute ( "update language set de_CH='$de_CH', de_DE='$de_DE' where LanguageID=$id" );
		}
	}

	/*
	 * Diese Funktion fügt einen News Eintrag hinzu.
	 */
	protected function addNews($title, $description, $datestring, $author) {
		$rs = $this->select_one ( "select id from news where title='$title' and date='$datestring'" );
		if ($rs ['id']) {
			$this->updateRecord ( 'news', array (
					'description' => $description,
					'date' => $datestring,
					'author' => $author
			), 'id=' . $rs ['id'] );
		} else {
			$this->addRecord ( 'news', array (
					'id' => '?',
					'title' => $title,
					'description' => $description,
					'date' => $datestring,
					'author' => $author
			) );
		}
	}

	/**
	 * Erstellt einen Prozess, wenn dieser noch nicht exisitert
	 *
	 * @param string $class
	 * @param string $title
	 * @param string $description
	 * @param integer $order
	 * @param string $usrGroups
	 *        	automatische Rechtevergabe
	 */
	protected function addProcess($class, $title, $description = '', $order = 1, $usrGroups = '') {
		if ($this->select_one ( "select * from process where ProcessClass='$class'" )) {
			$this->updateRecord('process', array(
				'ProcessName'					=>	$title
				,'ProcessDescription'	=>	$description
				,'OrderIdx'						=>	$order
			), 'ProcessClass="'. $class . '"');
			return; // Prozess existiert bereits;
		}
		$visible = $usrGroups == '' ? 0 : 1;
		$id = $this->addRecord ( 'process', array (
				'ProcessID' => '?',
				'ProcessName' => $title,
				'ProcessDescription' => $description,
				'ProcessClass' => $class,
				'OrderIdx' => $order,
				'Visible' => $visible
		) );
		$this->addAccess ( $usrGroups, 'ProcessID', $id );
	}

	/**
	 * Erstellt einen Datensatz, wenn dieser noch nicht existiert oder wenn mit ID=? ein neuer Datensatz
	 *
	 * @param string $table
	 * @param array $fields
	 *        	das 1. Feld markiert die ID-Spalte. ID=? erstellt einen neuen Datensatz
	 * @return integer der ID-Spalte (=Vorgabewert oder ID des neuen Datensatzes)
	 */
	protected function addRecord($table, $fields) {
		$idfield = '';
		foreach ( $fields as $field => $value ) {
			if ($idfield === '') {
				$idfield = $field;
				$idvalue = $value;
				if($idvalue === '?') {
					$rs = $this->select_one ( "select MAX($idfield) as id from $table" );
					$idvalue = $rs ['id'] + 1;
				}

				if (is_numeric($idvalue)) {
					$rs = $this->select_one ( "select * from $table where $idfield=$idvalue" );
					if($rs)			return $idvalue;
				} else {
					$rs = $this->select_one("select * from $table where $idfield like '$idvalue'" );
					if ($rs)		return $idvalue;
				}

				$sql = "insert into $table (`$idfield`, ";
				$values = "values ('$idvalue', ";
			} else {
				$sql .= "`$field`, ";
				$values .= "'$value', ";
			}
		}
		$sql = substr ( $sql, 0, strlen ( $sql ) - 2 ) . ') ';
		$values = substr ( $values, 0, strlen ( $values ) - 2 ) . ')';
		$sql .= $values;
		$this->execute ( $sql );
		return $idvalue;
	}

	/**
	 * Erfasst einen neuen Systemparameter, sofern er nicht schon vorliegt
	 *
	 * @param string $paramName
	 * @param string $description
	 * @param string $value
	 */
	protected function addSystemParam($paramName, $description, $value) {
		$id = $this->getValue ( 'systemparam', 'SystemParamID', "ParamName='$paramName'" );
		if (!$id) {
			$this->addRecord ( 'systemparam', array (
					'SystemParamID' => '?',
					'ParamName' => $paramName,
					'ParamValue' => $value,
					'Description' => $description
			) );
		} else {
			$this->updateRecord ( 'systemparam', array (
					'ParamValue' => $value,
					'Description' => $description
			), 'SystemParamID=' . $id );
		}
	}

	/**
	 * Erfasst ein neues Widget, steuert orderidx und visibility
	 * @param integer $widgetgroupId			widget.widget_group_id
	 * @param string $label								widget.label
	 * @param string $description					widget.description
	 * @param string $sourcen							widget.sourcen
	 * @param string $class								widget.class
	 * @param string $thumbnail						widget.thumbnail
	 * @param string $usrgroups						Komma-separierte Liste mit Rechtegruppen
	 */
	protected function addWidget($widgetgroupId, $label, $description, $sourcen, $class, $thumbnail, $usrgroups='') {
		if($this->select_one("select * From widget where class='$class'")) {
			//
			//	Widget existiert bereits -> aktualisiere Attribute
			$fields = array(
				'widget_group_id'			=>	$widgetgroupId
				,'label'							=>	$label
				,'description'				=>	$description
				,'sourcen'						=>	$sourcen
				,'thumbnail'					=>	$thumbnail
				,'height'							=>	320
			);
			$this->updateRecord('widget', $fields, "class='$class'");
			return;
		}
		//
		//	Erfasse Widget
		$visible = $usrgroups == '' ? 0 : 1;
		$neworder = $this->select_one('select max(orderidx)+1 as neworder from widget where widget_group_id='.$widgetgroupId);
		$neworder = $neworder['neworder'];
		
		$id = $this->addRecord('widget', array(
			'id' => '?'
			,'widget_group_id'		=>	$widgetgroupId
			,'label'							=>	$label
			,'description'				=>	$description
			,'sourcen'						=>	$sourcen
			,'class'							=>	$class
			,'thumbnail'					=>	$thumbnail
			,'height'							=>	320
			,'orderidx'						=>	$neworder
			,'visible'						=>	$visible
		));
		if($usrgroups)			$this->addAccess($usrgroups, 'WidgetID', $id);
	}
	
	protected function canFind($table, $where) {
		$rs = $this->select_one ( "select * from $table where $where" );
		if ($rs) {
			return true;
		}
		return false;
	}

	/**
	 * Prüft den Systemparameter CustomerIdent, ob dieser im Parameter aufgeführt ist
	 * (Individuelle Updates)
	 *
	 * @param string $customer
	 *        	mit Kunden-Kürzel (Allreal,IBS,Bonn,...)
	 * @return boolean wenn das System einem der aufgeführten Kunden gehört
	 */
	protected function customer($customer) {
		foreach ( explode ( ',', $customer ) as $ident ) {
			if ($this->select_one ( "select SystemParamID from systemparam where ParamName='CustomerIdent' and ParamValue='$ident'" )) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Löscht ein Feld aus einer Tabelle, sofern es existiert
	 */
	protected function dropField($table, $field) {
		if ($this->hasField ( $table, $field )) {
			$this->execute ( "alter table `$table` drop column `$field`" );
		}
	}

	/**
	 * Löscht einen Index aus der Tabelle, sofern er existiert
	 *
	 * @param string $table
	 * @param string $index
	 */
	protected function dropIndex($table, $index) {
		if ($this->hasIndex ( $table, $index )) {
			$this->execute ( "alter table `$table` drop index `$index`" );
		}
	}
	protected function dropConstraint($table, $constraint) {
		if ($this->hasConstraint ( $table, $constraint )) {
			$this->execute ( "alter table `$table` drop foreign key `$constraint`" );
		}
	}

	/**
	 * Initialisiert das Farbprofil - spezfiziert durch Systemparameter und Kontenlayouts
	 * mittels Norm-Json-Farbspezifikation
	 * @param json $colorprofile					Farbprofil mit Systemparam und KtoLayout-Referenzen
	 */
	protected function initColorProfile($colorprofile) {
		try {
			$json =json_decode($colorprofile);
		} catch(Exception $e) {
			return;
		}
		if($json == null)		return;
		foreach($json->Systemparam as $paramName => $paramValue) {
			$this->updateRecord('systemparam', array('ParamValue'=>$paramValue), 'ParamName="'.$paramName.'"');
		};
		foreach($json->KtoLayout as $id => $layout) {
			$this->updateRecord('ktolayout', array(
				'Layout'						=>	$layout->Layout
				,'Font'							=>	$layout->Font
				,'FontSize'					=>	$layout->FontSize
				,'Bold'							=>	$layout->Bold
				,'Italic'						=>	$layout->Italic
				,'BackgroundColor'	=>	$layout->BackgroundColor
				,'FontColor'				=>	$layout->FontColor
				,'BorderTop'					=>	$layout->BorderTop
				,'BorderBottom'			=>	$layout->BorderBottom
			), 'KtoLayoutID='.$id);
		}
		$this->removeTemplates();
	}
	
	/**
	 * Ermittelt den Datenbanknamen aus der aktuellen Verbindung
	 */
	protected function getDBName() {
		$dbname = $this->select_one ( 'select database()' );
		return $dbname ['database()'];
	}
	protected function getValue($table, $field, $where = '') {
		$sql = "select $field as value from $table ";
		if ($where)
			$sql .= "where $where ";
		$sql .= 'limit 1 ';
		$rs = $this->select_one ( $sql );
		if ($rs)
			return $rs ['value'];
	}
	protected function hasConstraint($table, $constraint) {
		$dbname = $this->getDBName ();
		$rs = $this->select_all ( "select * from information_schema.table_constraints where table_schema='$dbname' and table_name='$table' and constraint_name='$constraint'" );
		return (count ( $rs ) > 0);
	}

	/**
	 * Prüft ob ein Feld in einer Tabelle bereits existiert
	 *
	 * @param string $table
	 * @param string $field
	 * @return boolean wenn das Feld bereits in der Tabelle enthalten ist.
	 */
	protected function hasField($table, $field) {
		$fields = $this->select_all ( "SHOW COLUMNS FROM $table" );
		foreach ( $fields as $f ) {
			if (strtolower ( $f ['Field'] ) == strtolower ( $field ))
				return true;
		}
		return false;
	}

	/**
	 * Prüft ob ein Index in einer Tabelle bereits existiert
	 *
	 * @param string $table
	 * @param string $index
	 * @return boolean wenn der Index bereits in der Tabelle enthalten ist.
	 */
	protected function hasIndex($table, $index) {
		$rs = $this->select_all ( "show index from $table where Key_name like '$index'" );
		return count ( $rs ) > 0;
	}

	/**
	 * Prüft, ob eine Tabelle bereits exisitert
	 *
	 * @param string $table
	 * @return boolean wenn die Tabelle existiert
	 */
	protected function hasTable($table) {
		return $this->get_adapter ()->has_table ( $table );
	}

	/**
	 * Erstellt einen Protokolleintrag
	 *
	 * @param string $msg
	 */
	protected function log($msg) {
		$msg = str_replace ( 'ü', 'ue', str_replace ( 'ö', 'oe', str_replace ( 'ä', 'ae', $msg ) ) );
		$this->result .= sprintf ( "\t%s\n", $msg );
	}
	protected function moveExport($sourceClass, $targetClass, $targetTitle = '', $targetDescription = '') {
		// Mehrfachvorkommen der Quelle?
		$rs = $this->select_all ( "select AuswertungID from auswertung where AuswertungClass='$sourceClass'" );
		if (count ( $rs ) == 0) {
			// Quellklasse existiert nicht -> erstelle Zielklasse ohne Sichtbarkeit
			$this->addExport ( $targetClass, $targetTitle, $targetDescription, 0 );
			return;
		}
		// Für alle Quellen...
		foreach ( $rs as $source ) {
			$sourceId = $source ['AuswertungID'];
			$targetId = $this->getValue ( 'auswertung', 'AuswertungID', "AuswertungClass='$targetClass'" );
			if (! $targetId) {
				// Auswertung existiert noch nicht -> migriere bestehende
				$fields = array ();
				$fields ['AuswertungClass'] = $targetClass;
				if ($targetTitle)
					$fields ['AuswertungName'] = $targetTitle;
				if ($targetDescription)
					$fields ['AuswertungDesc'] = $targetDescription;
				$this->updateRecord ( 'auswertung', $fields, "AuswertungID=$sourceId" );
			} else {
				// Auswertung existiert bereits -> ensure source-rights
				// ...Attribute übernehmen
				$visible = $this->getValue ( 'auswertung', 'AuswertungVisible', "AuswertungID=$sourceId" );
				$fields = array ();
				if ($targetTitle)
					$fields ['AuswertungName'] = $targetTitle;
				if ($targetDescription)
					$fields ['AuswertungDesc'] = $targetDescription;
				if ($visible)
					$fields ['AuswertungVisible'] = 1;
				if (count ( $fields ) > 0)
					$this->updateRecord ( 'auswertung', $fields, "AuswertungID=$targetId" );
					// Zugriffsrechte sicherstellen
				$srcGrp = $this->select_all ( "select usrgrp.Name from usraccess left join usrgrp on usraccess.UsrGrpID=usrgrp.UsrGrpID where Object='AuswertungID' and Value=$sourceId" );
				foreach ( $srcGrp as $grp ) {
					$this->addAccess ( $grp ['Name'], 'AuswertungID', $targetId );
				}
				// Lösche Quelltabelle und Zugriffsrechte
				$this->execute ( "delete from usraccess where Object='AuswertungID' and Value=$sourceId" );
				$this->execute ( "delete from auswertung where AuswertungID=$sourceId" );
			}
		}
	}
	protected function moveProcess($sourceClass, $targetClass, $targetTitle = null, $targetDescription = null) {
		// Mehrfachvorkommen der Quelle?
		$rs = $this->select_all ( "select ProcessID from process where ProcessClass='$sourceClass'" );
		if (count ( $rs ) == 0) {
			// Quellklasse existiert nicht -> erstelle Zielklasse ohne Sichtbarkeit
			$this->addProcess ( $targetClass, $targetTitle, $targetDescription, 0 );
			return;
		}
		// Für alle Quellen...
		foreach ( $rs as $source ) {
			$sourceId = $source ['ProcessID'];
			$targetId = $this->getValue ( 'process', 'ProcessID', "ProcessClass='$targetClass'" );
			if (! $targetId) {
				// Zielklasse existiert noch nicht -> migriere bestehende
				$fields = array ();
				$fields ['ProcessClass'] = $targetClass;
				if ($targetTitle)
					$fields ['ProcessName'] = $targetTitle;
				if ($targetDescription)
					$fields ['ProcessDescription'] = $targetDescription;
				$this->updateRecord ( 'process', $fields, "ProcessID=$sourceId" );
			} else {
				// Zielklasse existiert bereits -> übernehem Quell-Rechte
				// ...Attribute übernehmen
				$visible = $this->getValue ( 'process', 'Visible', "ProcessID=$sourceId" );
				$fields = array ();
				if ($targetTitle)
					$fields ['ProcessName'] = $targetTitle;
				if ($targetDescription)
					$fields ['ProcessDescription'] = $targetDescription;
				if ($visible)
					$fields ['Visible'] = 1;
				if (count ( $fields ) > 0)
					$this->updateRecord ( 'process', $fields, "ProcessID=$targetId" );
					// Zugriffsrechte sicherstellen
				$srcGrp = $this->select_all ( "select usrgrp.Name from usraccess left join usrgrp on usraccess.UsrGrpID=usrgrp.UsrGrpID where Object='ProcessID' and Value=$sourceId" );
				foreach ( $srcGrp as $grp ) {
					$this->addAccess ( $grp ['Name'], 'ProcessID', $targetId );
				}
				// Lösche Quelltabelle und Zugriffsrechte
				$this->execute ( "delete from usraccess where Object='ProcessID' and Value=$sourceId" );
				$this->execute ( "delete from process where ProcessID=$sourceId" );
			}
		}
	}
	protected function moveWidget($sourceClass, $targetClass, $name, $description, $type, $chart, $widgetgroup, $height = 300, $usrGroups = '') {
		// Mehrfachvorkommen der Quelle?
		$rs = $this->select_all ( "select WidgetID from widget where Class='$sourceClass'" );
		if (count ( $rs ) == 0) {
			// Quellklasse existiert nicht -> erstelle Zielklasse ohne Sichtbarkeit
			$this->addWidget ( $targetClass, $name, $description, $type, $chart, $widgetgroup, $height, $usrGroups );
			return;
		}
		// Für alle Quellen...
		foreach ( $rs as $source ) {
			$sourceId = $source ['WidgetID'];
			$targetId = $this->getValue ( 'widget', 'WidgetID', "Class='$targetClass'" );
			if (! $targetId) {
				// Auswertung existiert noch nicht -> migriere bestehende
				$fields = array ();
				$fields ['Class'] = $targetClass;
				$fields ['Name'] = $name;
				$fields ['Description'] = $description;
				$fields ['Type'] = $type;
				$fields ['Chart'] = $chart;
				$fields ['WidgetGroupID'] = $widgetgroup;
				$fields ['Height'] = $height;
				$this->updateRecord ( 'widget', $fields, "WidgetID=$sourceId" );
			} else {
				// Auswertung existiert bereits -> ensure source-rights
				// ...Attribute übernehmen
				$visible = $this->getValue ( 'widget', 'visible', "WidgetID=$sourceId" );
				$fields = array ();
				$fields ['Class'] = $targetClass;
				$fields ['Name'] = $name;
				$fields ['Description'] = $description;
				$fields ['Type'] = $type;
				$fields ['Chart'] = $chart;
				$fields ['WidgetGroupID'] = $widgetgroup;
				$fields ['Height'] = $height;
				$fields ['visible'] = $visible;
				$this->updateRecord ( 'widget', $fields, "WidgetID=$targetId" );
				// Zugriffsrechte sicherstellen
				$srcGrp = $this->select_all ( "select usrgrp.Name from usraccess left join usrgrp on usraccess.UsrGrpID=usrgrp.UsrGrpID where Object='WidgetID' and Value=$sourceId" );
				foreach ( $srcGrp as $grp ) {
					$this->addAccess ( $grp ['Name'], 'WidgetID', $targetId );
				}
				// Lösche Quelltabelle und Zugriffsrechte
				$this->execute ( "delete from usraccess where Object='WidgetID' and Value=$sourceId" );
				$this->execute ( "delete from widget where WidgetID=$sourceId" );
			}
		}
	}

	/**
	 * Erstellt eine String-Ausgabe für Debug-Meldungen
	 * @param mixed $msg									Meldung als string, array oder stdClass
	 */
	protected function out($msg) {
		echo($this->dump($msg));
	}
	
	/**
	 * Entfernt die Zugriffsrechte für Benutzergruppen
	 *
	 * @param string $usrGroups						Komma-separierte Liste mit Wildcards
	 * @param string $accessObject				Zugriffsobjekt
	 * @param integer $id									ID des Zugriffsbobjekts
	 */
	protected function removeAccess($usrGroups, $accessObject, $id) {
		if ($usrGroups == '')
			return;
			// Erstelle Zugriffsrechte zur Auswertung für alle Benutzergruppen im Fokus
		$sql = 'select UsrGrpID from usrgrp where ';
		foreach ( explode ( ',', $usrGroups ) as $group ) {
			$sql .= "Name like '$group' or ";
		}
		$sql = substr ( $sql, 0, strlen ( $sql ) - 3 );
		$rs = $this->select_all ( $sql );
		$ids = '';
		foreach ( $rs as $group ) {
			$ids .= $group ['UsrGrpID'] . ',';
		}
		$ids = substr ( $ids, 0, strlen ( $ids ) - 1 );
		$this->execute ( "delete from usraccess where Object='$accessObject' and Value=$id and UsrGrpID in ($ids)" );
	}

	/**
	 * Eliminiert ein obsoletes Zugriffsobjekt
	 * @param string $accessObject
	 */
	protected function removeAccessObject($accessObject) {
		$id = $this->getValue('usraccessobject','UsrAccessObjectID', 'usraccessObject="'.$accessobject.'"');
		if(!$id)		return;
		$this->removeAccess('%', 'UsrAccessObjectID', $id);
		$this->query('delete from usraccessobject where UsrAccessObjectID='.$id);
	}
	
	/**
	 * Entfernt eine obsolete Auswertungsklasse und alle damit verbundenen Rechten
	 *
	 * @param string $class
	 */
	protected function removeExport($class) {
		$rs = $this->select_one ( "select AuswertungID from auswertung where AuswertungClass='$class'" );
		if (! $rs)
			return;
		$id = $rs ['AuswertungID'];
		$this->execute ( "delete from usraccess where Object='AuswertungID' and Value=$id" );
		$this->execute ( "delete from auswertung where AuswertungID=$id" );
	}

	/**
	 * Entfernt eine obsolete Prozessklasse und alle damit verbundenen Rechte
	 *
	 * @param string $class
	 */
	public function removeProcess($class) {
		$rs = $this->select_one ( "select ProcessID from process where ProcessClass='$class'" );
		if (! $rs)
			return;
		$id = $rs ['ProcessID'];
		$this->execute ( "delete  from usraccess where Object='ProcessID' and Value=$id" );
		$this->execute ( "delete from process where ProcessID=$id" );
	}

	/**
	 * Entfernt alle Dokumentvorlagen des gewählten Kundensystems
	 */
	public function removeTemplates() {
		if($_SERVER['updateroot']) {
			$path = $_SERVER['updateroot'];
		} else {
			$path = $_SERVER['OLDPWD'];
		}
		$path .= '/media/'.$this->getDBName().'/templates';
		$this->cleanDir($path);
	}

	/**
	 * Entfernt eine obsolete Widget-Klasse und alle damit verbundenen Rechten
	 *
	 * @param string $class
	 */
	protected function removeWidget($class) {
		$rs = $this->select_one ( "select id from widget where class='$class'" );
		if (! $rs)
			return;
		$id = $rs ['id'];
		$this->execute ("delete from usraccess where Object='WidgetID' and Value=$id" );
		$this->execute ("delete from widget_data where widget_id=$id" );
		$this->execute ("delete from widget where id=$id" );
	}

	/**
	 * Reinitializes language-table with languageKeys.csv
	 */
	protected function resetLanguage() {
		//
		// ResetLanguageKeys
		if($_SERVER['updateroot']) {
			$file = $_SERVER['updateroot'];
		} else {
			$file = $_SERVER['OLDPWD'];
		}
		$file .= '/config/languageKeys.csv';


		$this->query ( 'delete from language where locked != 1' );

		$this->query ( 'set @id=0' );
		$this->query ( 'update language set LanguageID=(select @id:=@id+1) order by LanguageID;' );

		$id = $this->select_all ( 'select max(LanguageID) as value from language' );
		$id = $id [0] ['value'];
		$id ++;
		$this->query ( 'alter table language auto_increment=' . $id );
		try {
			$csv = fopen ( $file, 'r' );
		} catch ( Exception $e ) {
		}
		if (! $csv)			return;
		while ( ! feof ( $csv ) ) {
			$line = utf8_encode(fgets($csv));
			$data = array();
			while($line != '') {
				$data[] = $this->extractValue($line);
			}
			if(count($data) != 4)		continue;
			$key = $this->quote_string ($data[0]);
			$de = $this->quote_string ($data[1]);
			$frontend = $data[3] == '' ? 0 : 1;
			$check = $this->select_one ( 'select LanguageID from language where LanguageKey=\'' . $key . '\'' );
			if ($check === null) {
				$this->query ( "insert into language (frontend, locked, LanguageKey, de_CH, de_DE, de_AT, en_GB) values ($frontend, 0, '$key', '$de', '$de', '$de', '$key')" );
			} else {
				if ($frontend == 1) {
					$this->query ( "update language set frontend=1 where LanguageKey='$key'" );
				}
			}
		}
		fclose ( $csv );
	}

	/**
	 * Aktiviert ein Zugriffsrecht
	 * @param string $accessObject				Zugriffsrecht als usraccessobject-Eintrag
	 * @param string $usrgrp							Benutzergruppe(n) als Komma-separierte Liste inkl. Wildcards
	 */
	protected function enableAccessObject($accessObject, $usrgrp) {
		$usraccessobject = $this->select_one('select UsrAccessObjectID from usraccessobject where UsrAccessObject = "'.$accessObject.'"');
		if(!$usraccessobject)		return;
		$this->updateRecord('usraccessobject', array('visible'=>1), 'UsrAccessObjectID='.$usraccessobject['UsrAccessObjectID']);
		$this->addAccess($usrgrp, 'UsrAccessObjectID', $usraccessobject['UsrAccessObjectID']);
	}
	
	/**
	 * Aktiviert den Zugriff auf eine Exportklasse
	 * @param string $exportClass
	 * @param string $usrgrp
	 */
	protected function enableExport($exportClass, $usrgrp='%') {
		$export = $this->select_one('select AuswertungID from auswertung where AuswertungClass="'.$exportClass.'"');
		if(!$export)		return;
		$this->updateRecord('auswertung', array('AuswertungVisible'=>1), 'AuswertungID='.$export['AuswertungID']);
		$this->addAccess($usrgrp, 'AuswertungID', $export['AuswertungID']);
	}
	
	/**
	 * Aktiviert den Zugriff auf eine Importklasse
	 * @param string $importClass
	 * @param string $usrgrp
	 */
	protected function enableImport($importClass, $usrgrp='%') {
		$importfiletype = $this->select_one('select ImportFileTypeID from importfiletype where ImportClass="'.$importClass.'"');
		if(!$importfiletype)		return;
		$this->updateRecord('importfiletype', array('ImportVisible'=>1), 'ImportFileTypeID='.$importfiletype['ImportFileTypeID']);
		$this->addAccess($usrgrp, 'ImportFileTypeID', $importfiletype['ImportFileTypeID']);
	}
	
	/**
	 * Aktiviert den Zugriff auf eine Widgetklasse
	 * @param string $widgetClass
	 * @param string $usrgrp
	 */
	protected function enableWidget($widgetClass, $usrgrp) {
		$widget= $this->select_one('select id from widget where class="'.$widgetClass.'"');
		if(!$widget)		return;
		$this->updateRecord('widget', array('visible'=>1), 'id='.$widget['id']);
		$this->addAccess($usrgrp, 'WidgetID', $widget['id']);
	}
	
	/**
	 * Extrahiert einen CSV-Wert und kürzt die Datenzeile
	 * @param	string &$line
	 * @return string
	 */
	private function extractValue(&$line) {
		//	Beginnt die Sequenz mit einem Quoter?
		if(substr($line,0,1) == '"') {
			//Suche den nächsten alleinstehenden Quoter
			for($p=1; $p<strlen($line); $p++) {
				if(substr($line,$p,1) == '"') {
					if($p == strlen($line)-1) {	//	Ende erreicht
						$item = str_replace('""','"', substr($line, 1, $p - 1));
						$line = '';
						return $item;
					}
					if(substr($line, $p+1, 1) ==  '"') {	// Innerer Doublequote ignorieren
						$p++;
					} else {
						//Schlussquote gefunden
						$item = str_replace('""', '"', substr($line, 1, $p - 1));
						$remaining = strlen($line) - $p - 2;

						if($remaining > 0) {
							$line = substr($line, -$remaining);
						} else {
							$line = '';
						}
						return $item;
					}
				}
			}
		} else {
			// Suche nach der ersten Fundstelle an Delimiter
			$p = strpos($line, ';');
			if($p !== false) {
				$item = substr($line, 0, $p);
				if(strlen($line) == $p + 1) {
					$line = '';
				} else {
					$line = substr($line, -strlen($line) + $p + 1);
				}
			} else {
				$line = str_replace(chr(13), '', $line);
				$line = str_replace(chr(10), '', $line);
				$item = $line;
				$line = '';
			}
		}
		return $item;
	}

	protected function updateRecord($table, $fields, $where = '') {
		$sql = "update $table set ";
		foreach ( $fields as $field => $value ) {
			$sql .= "`$field`='$value', ";
		}
		$sql = substr ( $sql, 0, strlen ( $sql ) - 2 );
		if ($where)
			$sql .= " where $where";
		$this->execute ( $sql );
	}

	/**
	 * Ermittelt den Variablennamen aus einem String in der Syntax Variable=Wert
	 *
	 * @param string $variable
	 * @return string
	 */
	protected function varName($variable) {
		$var = explode ( '=', $variable );
		return $var [0];
	}

	/**
	 * Ermittelt den Variablenwert aus einem String mit der Syntax Variable=Wert
	 *
	 * @param string $variable
	 * @return string
	 */
	protected function varValue($variable) {
		$var = explode ( '=', $variable );
		if (count ( $var ) > 1) {
			return $var [1];
		} else {
			return '';
		}
	}
	public function cleanDir($dir, $deleteRootNode = false) {
		if (! is_dir ( $dir ))
			return;
		if (! $dh = @opendir ( $dir ))
			return;
		while ( false !== ($obj = readdir ( $dh )) ) {
			if ($obj == '.' || $obj == '..')
				continue;
			if (! @unlink ( $dir . '/' . $obj ))
				$this->cleanDir ( $dir . '/' . $obj, true );
		}
		closedir ( $dh );
		if ($deleteRootNode)
			@rmdir ( $dir );
	}
}
