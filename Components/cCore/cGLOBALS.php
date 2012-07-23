<?php

require("errorhandler.php");

/*define("SERVER", "localhost");
define("SERVERUSER", "root");
define("SERVERPASSWORD", "");
define("DBNAME", "independence");
define("BASEURL", "localhost");*/

error_reporting(E_ALL);
set_error_handler("ErrorHandler", E_ALL);

class cGLOBALS {

    const SERVER = "localhost";
    const SERVERUSER = "root";
    const SERVERPASSWORD = "";
    const DBNAME = "independence";
    const BASEURL = "localhost";

    public $clientInfo = array();
    //private $DB;
    private $browserInfo;
    private $dbConnection;

    public function __construct() {
        //$this->DB = cGLOBALS::CreateDBConnection();
        $this->CreateDBConnection();
        $this->browserInfo = new cBrowserInfo();
        $this->FillClientInfo();
    }

    private function FillClientInfo() {
        $this->clientInfo["ip"] = $_SERVER['SERVER_ADDR'];
    }

    /*public function DB() {
        return $this->DB;
    }*/

    public function DB() {
        return $this->dbConnection;
    }

    public function CreateDBConnection() {
        $init = new mysqli(self::SERVER, self::SERVERUSER, self::SERVERPASSWORD);//mysqli_init();
        if (!$init) {
            die('mysqli_init failed');
        } else {
            $this->dbConnection = $init;
        }
        /*if (!$init->real_connect(SERVER, SERVERUSER, SERVERPASSWORD))
            die('mysqli_real_connect failed');*/
        //var_dump($init);
        //echo $init;
        return $init;
    }

    public function getBrowserInfo() {
        return $this->browserInfo;
    }

    public function Serialize($aObject) {
        if (is_object($aObject)) {
            return serialize($aObject);
        } else {
            ErrMsg("Serialize(): argument is not object");
            return false;
        }
    }

    public function Unserialize($aByteString) {
        if (isset($aByteString)) {
            return unserialize($aByteString);
        } else {
            ErrMsg("Unserialize(): argument is not string");
            return false;
        }
    }

}

class cBrowserInfo {

    private $httpUserAgent;
    private $name;
    private $majVer;
    private $minVer;
    private $core;

    public function __construct() {
        $this->httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
    }

}

?>