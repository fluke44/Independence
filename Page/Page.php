<?php
require_once "./staticData/messages.php";
require_once "./staticData/page_constants.php";
require_once "cGLOBALS.php";
require_once "cPageOperations.php";

define("MAX_LOGIN_RETRIES", 5);

class Page {
	private $_userID;
	private $_sessionID;
	private $_prevPageID;
	private $_prevURL;
	
	public function Authenticate() {
		if(isset($_SESSION["auth"])) {
			$auth = Unserialize($_SESSION["auth"]);
			$_userID = $auth->GetUserID();
			$_sessionID = $auth->GetSessionID();
			$_prevPageID = $autt->GetPageID();
			$_prevURL = $auth->GetURL();
		}

		
	}
	
	public function IsEmpty($var) {
		if(empty($var)) return true;
		if(trim($var) == "") return true;
		if($var == null) return true;
		return false;
	}
	
	
}