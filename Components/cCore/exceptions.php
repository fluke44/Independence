<?php
class DefaultException {
	protected $exceptionMsg;
	
	public function __construct() {
		$this->exceptionMsg = "Default Exception";
	}
	
	public function GetMsg() {
		return $this->exceptionMsg;
	}
}

class AddRecordException extends DefaultException {
	public function __construct() {
		$this->exceptionMsg = "AddRecord Exception";
	}
}

class ParamsException extends DefaultException {
	
	public function __construct() {
		$this->exceptionMsg = "Params Exception";
	}
}

?>