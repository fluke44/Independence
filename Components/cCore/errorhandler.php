<?php

function ErrorHandler($errLevel, $errMsg, $errFile, $errLine, $errContext) {

    switch ($errLevel) {
        case E_ERROR:
            $errorMsg = "<b>Fatal Run-time Error (" . $errLevel . "):</b><br />";
            break;
        case E_WARNING:
            $errorMsg = "<b>Run-time Warning (" . $errLevel . "):</b><br />";
            break;
        case E_PARSE:
            $errorMsg = "<b>Parse Error (" . $errLevel . "):</b><br />";
            break;
        case E_NOTICE:
            $errorMsg = "<b>Run-time Notice (" . $errLevel . "):</b><br />";
            break;
        case E_CORE_WARNING:
            $errorMsg = "<b>Core Warning (" . $errLevel . "):</b><br />";
            break;
        case E_COMPILE_WARNING:
            $errorMsg = "<b>Run-time Warning (" . $errLevel . "):</b><br />";
            break;
        case E_USER_ERROR:
            $errorMsg = "<b>User Run-time Error (" . $errLevel . "):</b><br />";
            break;
        case E_USER_WARNING:
            $errorMsg = "<b>User Run-time Warning (" . $errLevel . "):</b><br />";
            break;
        case E_USER_NOTICE:
            $errorMsg = "<b>User Run-time Notice (" . $errLevel . "):</b><br />";
            break;
        case E_USER_DEPRECATED:
            $errorMsg = "<b>User Warning (" . $errLevel . "):</b><br />";
            break;
        default:
            $errorMsg = "<b>Unspecified Error (" . $errLevel . "):</b><br />";
            break;
    }
    $errorMsg .= $errMsg . " in " . $errFile . "<br />" . "on line " . $errLine . "<br />";
    //$errorMsg .= $errContext;

    echo $errorMsg;
    return $errorMsg;
}

function ErrMsg($aMsg) {
    echo $aMsg;
}

?>