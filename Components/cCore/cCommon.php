<?php
class cCommon {
    public static function GetSystemVariable($varName) {
        try {
            $dal = new cDAL();
            $search = new cSearchParams();

            $search->Add("name", "=", $varName);

            $recordset = $dal->OpenRecordset("indSystemVariables", null, $search);
            if ($recordset->GetInternalData("SQLErrorNo") != 0) {
                throw new Exception("Loading system variable '" . $varName . "' failed");
            }
            return $recordset->Get("value");
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public static function ShowErrMsg($msg) {
        if(is_string($msg)) {
            return "<div class='errMsg'>" . $msg . "</div>";
        }
    }

    public static function ShowSuccMsg($msg) {
        if(is_string($msg)) {
            return "<div class='succMsg'>" . $msg . "</div>";
        }
    }

    public static function ShowInfoMsg($msg) {
        if(is_string($msg)) {
            return "<div class='msg'>" . $msg . "</div>";
        }
    }
}
?>
