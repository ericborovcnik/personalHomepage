<?php
abstract class Base_Model_Joblog extends Model_Base {
	//### GET

	public function getUniqueId() {
		return $this->__get('unique_id');
	}

	public function getCommand() {
		return $this->__get('command');
	}

	public function getParams() {
		return $this->__get('params');
	}

	public function getPrio() {
		return $this->__get('prio');
	}

	public function getUserId() {
		return $this->__get('user_id');
	}

	public function getUserName($value) {
		return $this->__get('user_name');
	}

	public function getCreatedAt() {
		return $this->__get('created_at');
	}

	public function getStartedAt() {
		return $this->__get('started_at');
	}

	public function getStoppedAt() {
		return $this->__get('stopped_at');
	}

	public function getDuration() {
		return $this->__get('duration');
	}

	public function getOutput() {
		return $this->__get('output');
	}

	public function getHasError() {
		return $this->__get('has_error');
	}

	public function getErrorOutput() {
		return $this->__get('error_output');
	}

	//### SET

	public function setUniqueId($value) {
		return $this->__set('unique_id', $value);
	}

	public function setCommand($value) {
		return $this->__set('command', $value);
	}

	public function setParams($value) {
		return $this->__set('params', $value);
	}

	public function setPrio($value) {
		return $this->__set('prio', $value);
	}

	public function setUserId($value) {
		return $this->__set('user_id', $value);
	}

	public function setUserName($value) {
		return $this->__set('user_name', $value);
	}

	public function setCreatedAt($value) {
		return $this->__set('created_at', $value);
	}

	public function setStartedAt($value) {
		return $this->__set('started_at', $value);
	}

	public function setStoppedAt($value) {
		return $this->__set('stopped_at', $value);
	}

	public function setDuration($value) {
		return $this->__set('duration', $value);
	}

	public function setOutput($value) {
		return $this->__set('output', $value);
	}

	public function setHasError($value) {
		return $this->__set('has_error', $value);
	}

	public function setErrorOutput($value) {
		return $this->__set('error_output', $value);
	}

}