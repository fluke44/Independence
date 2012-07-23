<?php
include_once "cGLOBALS.php";
define("COMPONENT", "cCore");

class cError {

    const CLASS_NAME = "cError";

    public static function Report   ($component = COMPONENT, $application = "cError", $function = "Report", $msg = "",
                                     $parameters = null, $data = null, $phpErrorNum = 0, $phpErrorMsg = "", $sqlErrorNum = 0,
                                     $sqlErrorMsg = "") {
        $MYGLOBALS = new cGLOBALS();
        $msg = str_replace("'", "`", $msg);
        $parameters = str_replace("'", "`", $parameters);
        $phpErrorMsg = str_replace("'", "`", $phpErrorMsg);
        $sqlErrorMsg = str_replace("'", "`", $sqlErrorMsg);
        $query = "INSERT INTO `independence`.`tblErrorHandler` (component, class, function, errormsg, parameters, phpErrorNo,
                    phpErrorMsg, sqlErrorNum, sqlErrorMsg)";
        $query .= " VALUES ('$component', '$application', '$function', '$msg', '$parameters', '$phpErrorNum', '$phpErrorMsg',
                    '$sqlErrorNum', '$sqlErrorMsg')";
        $MYGLOBALS->DB()->query($query);
    }

    /*public static function ShowMsg($msg, $altMsg = null) {
        $isError = false;
        $isSuccess = false;
        $message = "";

        if(is_object($msg)) {
            switch(get_class($msg)) {
                case "cDAL":
                    if($msg->Error()) {
                        $isError = true;
                        $message = $msg->GetErrMsg();
                    } else {
                        $message = $altMsg;
                    }
                    break;
                default:
                    break;
            }
        } else if(is_array($err)) {

        } else {
            $exploded = array();
            $exploded = explode("::", $msg);
            if($exploded[0] == "ERR") {
                $isError = true;
                $message = $exploded[1];
            } else if($exploded[0] == "SUC") {
                $isError = true;
                $message = $exploded[1];
            }
            if(count($exploded) == 1) {
                $message = $exploded[0];
            }
        }

        if ($message != null) {
            if ($isError) {
                return "<div class='errMsg'>" . $message . "</div>";
            } else {
                return "<div class='msg'>" . $message . "</div>";
            }
        }
    }*/

}

?>