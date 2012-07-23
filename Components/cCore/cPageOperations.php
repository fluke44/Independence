<?php

class cPageOperations {

    public function GetPageFromConstant($asConstant, $asParameters = null) {
        $searchParams = new cSearchParams();
        $dal = new cDAL();
        $dbCon = cGLOBALS::CreateDBConnection();

        if (!empty($asConstant)) {
            $searchParams->Add("constant", "=", $asConstant);
            $result = $dal->OpenRecordset("tblPageOperations", null, $searchParams, null, null);
            $page = $result->Get("page");
            $path = $result->Get("path");
            $return = $path . $page;
        } else {
            return false;
        }
        if (!empty($asParameters)) {
            $return .= "?" . $asParameters;
        }
        return true;
    }

    public function Redirect($url) {
        if (is_string($url)) {
            Header("Location: " . $url);
        }
    }

    public function InsertHiddenInput($id, $value) {
        if (!empty($id) && !empty($value)) {
            print("<input type='hidden' id='".$id."' value='".$value."' />");
        }
    }

}

?>