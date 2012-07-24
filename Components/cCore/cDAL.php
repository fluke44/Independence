<?php

require_once "cGLOBALS.php";
require_once "cError.php";
require_once "cRecordset.php";

if(!defined(COMPONENT_NAME)) {
    $COMPONENT_NAME = "cCore";
}

/*if(!isset($MYGLOBALS)) {
    $MYGLOBALS = new cGLOBALS();
}*/

class cDAL {
    const CLASS_NAME = "cDAL";
    private $MYGLOBALS;
    private $insertStatement = null;
    private $updateStatement = null;
    private $transactionStarted = false;
    private $transactionCommited = false;
    private $transactionCancelled = false;
    private $errMsg = "";
    private $error = 0;

    public function __construct() {
        $this->MYGLOBALS = new cGLOBALS();
    }

    public function Error() {
        if($this->error) {
            return true;
        } else {
            return false;
        }
    }

    private function SetError($errMsg) {
        $this->errMsg = $errMsg;
        $this->error = 1;
    }

    public function GetErrMsg() {
        $errMsg = $this->errMsg;
        $this->errMsg = "";
        $this->error = 0;
        return $errMsg;
    }

    public function OpenRecordset($aTable, $aFields, $aSearch, $aSort = NULL) {
        try {
            $isTableStoredProcedure = self::isStoredProcedure($aTable);
            if($isTableStoredProcedure === -1) {
                return -1;
            } else {
                if ($isTableStoredProcedure) {
                    $result = self::CallSP($aTable, $aSearch);
                    echo mysql_error();
                } else {
                    $query = self::GenerateSelectStatement($aTable, $aFields, $aSearch, $aSort);
                    if(!$query) {
                        throw new Exception('Invalid SQL query');
                    } else {
                        $result = self::RunQuery($query);
                        while($row = mysqli_fetch_assoc($result)){
                            ++$z;
                        }
                        while($row = $result->fetch_array()) {
                            cCommon::do_dump($row);
                        }
                        cCommon::do_dump($result);
                        self::FreeMoreResults();
                    }
                }
                cCommon::do_dump($result);
                $recordset = new cRecordset($result);
                if ($this->MYGLOBALS->DB()->errno) {
                    $recordset->SetInternalData("SQLErrorNo", $this->MYGLOBALS->DB()->errno);
                    $recordset->SetInternalData("SQLError", $this->MYGLOBALS->DB()->error);
                }
                return $recordset;
            }
        } catch (Exception $e) {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "OpenRecordset failed", $query, $e->getTraceAsString(), $e->getCode(),
                    $e->getMessage(), $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
            return false;
        }
    }

    /*public function UpdateRecordset2($aTable, & $aRecords, $aSearch = null) {
        $searchByIndex = new cSearchParams();
        $searchFields = array();
        $error = new cError();
        $noIndex = true;


        if (isset($aTable) AND isset($aRecords)) {
             //get index fields on updated table
            $searchFields = self::GetSearchFieldsForUpdateOrInsert($aTable, $aRecords);
            if(count($searchFields) > 0) {
                $noIndex = false;
            }

            $aRecords->MoveFirst();
            while(!$aRecords->AtEnd()) {
                if ($noIndex == false) {
                    $i = 0;
                    $searchByIndex->Clear();
                    while($i < count($searchFields)) {
                        $searchByIndex->Add($searchFields[$i], "=", $aRecords->Get($searchFields[$i]));
                        $i++;
                    }
                }

                switch($aRecords->GetAction()) {
                    case $aRecords->Action("Insert"):
                        $result = cDAL::InsertRecordset($aTable, $aRecords);
                        if($result === false) {
                            return false;
                        }
                        break;
                    case $aRecords->Action("Update"):
                        $result = self::RealUpdateRecordset($aTable, $aRecords, $searchByIndex);
                        if($result === false) {
                            return false;
                        }
                        break;
                    case $aRecords->Action("Delete"):
                        break;
                    default:
                }
                $aRecords->MoveNext();
            }
        }
    }*/

 public function UpdateRecordset($aTable, $aRecordset) {
        $searchByIndex = new cSearchParams();
        $searchFields = array();
        $error = new cError();
        $noIndex = true;
        $identityFieldName = null;
        $result = null;

        if (isset($aTable) AND isset($aRecordset)) {
            $tablePath = self::GetTableFromConstant($aTable);
            $identityFieldName = self::GetPrimaryKeyName($tablePath);

            $aRecordset->MoveFirst();
            if ($identityFieldName != null) {
                while (!$aRecordset->AtEnd()) {
                    switch ($aRecordset->Action()) {
                        case eRecordsetAction::Unknown:
                        case eRecordsetAction::Update:
                            $updateStatement = $this->GenerateUpdateStatement($tablePath, $aRecordset->Row(), $identityFieldName);
                            $this->updateStatement .= $updateStatement;
                            break;
                        case eRecordsetAction::Insert:
                            $insertStatement = $this->GenerateInsertStatement($tablePath, $aRecordset->Row(), $identityFieldName);
                            $this->insertStatement .= $insertStatement;
                            break;
                    }
                    $aRecordset->MoveNext();
                }
            }

            if ($this->updateStatement != null OR $this->updateStatement !== false) {
                $result = $this->RunSQLStatement(eSQLStatementType::Update);
                if($result === false) {
                    cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Recordset update failed",
                    null, null, null, null, $result->ErrNum, $result->ErrMsg);
                }
            }
            if ($this->insertStatement != null OR $this->insertStatement !== false) {
                $result = $this->RunSQLStatement(eSQLStatementType::Insert);
                if($result === false) {
                    cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Recordset insert failed.",
                    null, null, null, null, $result->ErrNum, $result->ErrMsg);
                }
            }
            return true;
        }
        return false;
    }

    private function RunSQLStatement($action) {
        switch($action) {
            case eSQLStatementType::Update:
                $result = cDAL::RunQuery($this->updateStatement);
                self::FreeMoreResults();
                if ($result === false) {
                    //return new cError($this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
                    return false;
                }
                return true;
                break;
            case eSQLStatementType::Insert:
                $result = cDAL::RunQuery($this->insertStatement);
                if ($result != false) {
                    return true;
                } else {
                    //return new cError($this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
                    return false;
                }
                break;
        }
    }

    /*public function UpdateRecordset($aTable, $aRecords) {
        $searchByIndex = new cSearchParams();
        $searchFields = array();
        $error = new cError();
        $noIndex = true;

        if (isset($aTable) AND isset($aRecords)) {
            //get index fields on updated table
            $searchFields = self::GetSearchFieldsForUpdateOrInsert($aTable, $aRecords);
            if(count($searchFields) > 0) {
                $noIndex = false;
            }
            $aRecords->MoveFirst();
            while (!$aRecords->AtEnd()) {
                if ($noIndex == false) {
                    $i = 0;
                    $searchByIndex->Clear();
                    while($i < count($searchFields)) {
                        $searchByIndex->Add($searchFields[$i], "=", $aRecords->Get($searchFields[$i]));
                        $i++;
                    }
                    //check if already exists some record in database
                    $recordExist = cDAL::OpenRecordset($aTable, null, $searchByIndex, null);
                }

                if ($noIndex OR $recordExist->Count() < 1) {
                    //no record exists, insert new one
                    $result = cDAL::InsertRecordset($aTable, $aRecords);
                    return $result;
                } else {
                    //some record like that exists, update it
                    $result = cDAL::RealUpdateRecordset($aTable, $aRecords, $searchByIndex);
                    return $result;
                }
                $aRecords->MoveNext();
            }
            return true;
        }
        return false;
    }*/

    /*private function RealUpdateRecordset($aTable, $aRecords, $aSearch) {
        $query = cDAL::GenerateUpdateStatement($aTable, $aRecords, $aSearch);
        if(substr($query, 0, 3) == "ERR") {
            $errMsg = array();
            $errMsg = explode("::", $query);
            $this->SetError($errMsg[1]);
            return false;
        }
        $result = cDAL::RunQuery($query);
        self::FreeMoreResults();
        if ($result == false) {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Recordset update failed.", $query,
                    null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
            if ($this->MYGLOBALS->DB()->errno) {
                $aRecords->SetInternalData("ErrNo", $this->MYGLOBALS->DB()->errno);
                $aRecords->SetInternalData("ErrMsg", $this->MYGLOBALS->DB()->error);
            }
            return false;
        }
        return true;
    }*/

    /*private function InsertRecordset($asTable, $arRecords) {
        if (isset($asTable) AND isset($arRecords)) {
            $query = cDAL::GenerateInsertStatement($asTable, $arRecords);
            $result = cDAL::RunQuery($query);
            if ($result != false) {
                return true;
            } else {
                cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Recordset insert failed.", $query,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
                if ($this->MYGLOBALS->DB()->errno) {
                    $arRecords->SetInternalData("SQLErrorNo", $this->MYGLOBALS->DB()->errno);
                    $arRecords->SetInternalData("SQLError", $this->MYGLOBALS->DB()->error);
                }
                return false;
            }
            return true;
        }
    }*/

    private static function RunQuery($aQuery, $aResultMode = eSQLResultMode::SQLResult) {
        $con = new mysqli(cGLOBALS::SERVER, cGLOBALS::SERVERUSER, cGLOBALS::SERVERPASSWORD, cGLOBALS::DBNAME);
        $result = $con->query($aQuery);

        if($result) {
            $data = $result->fetch_assoc();
            $retResult = $result;

            cCommon::do_dump($result);
            cCommon::do_dump($retResult);
            //echo "Field count: ".$con->field_count;

            //$result->free();
            while(@$con->next_result()) {
                $result = $con->use_result();
                if($result instanceof mysqli_result) {
                    $result->free();
                }
            }

            switch($aResultMode) {
                case eSQLResultMode::SQLArray:
                    return $data;
                case eSQLResultMode::SQLResult:
                    //return new mysqli_result($retResult);
                    return $retResult;
                case eSQLResultMode::SQLRecordset:
                default:
                    return new cRecordset($retResult);
            }
        } else {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Possible problem with SQL statement: " . $aQuery,
                    $aQuery, null, null, null, $con->errno, $con->error);
            return false;
        }
    }

    private static function RunQuery2($aQuery) {
        if (!empty($aQuery)) {
            /*$dbCon = new cGLOBALS();
            //$result = $this->MYGLOBALS->DB()->query($aQuery);
            //$result = $dbCon->DB()->query($aQuery);
            $dbCon->DB()->query($aQuery);
            $result = new mysqli_result($dbCon->DB());
            $ret = $result;
            $result->free();
            //$dbCon->DB()->free_result($result);
            return $ret;*/
            $mysql = new mysqli('localhost', 'root', '');
            var_dump($mysql);
            $mysql->query("SELECT 'test'");
            var_dump($mysql);
            $result = new mysqli_result($mysql);
            var_dump($result);
            $row = $result->fetch_row();
            var_dump($row);
        }
    }

    private function CallSP($spname, $aSearch) {
        
        $tablePath = self::GetTableFromConstant($spname);
        $query = "USE " . $tablePath["database"].";";
        $query .= "CALL " . $tablePath["tablename"] . "(";
        $parameters = self::GetProcedureParameters($tablePath["tablename"]);
        $paramsCount = $parameters->Count();
        if($paramsCount == $aSearch->Count()) {
            if (isset($aSearch)) {
                $aSearch->MoveFirst();
                $parameters->MoveFirst();
                while(!$parameters->AtEnd()) {
                    while (!$aSearch->AtEnd()) {
                        if($parameters->Get("parameter_name") == $aSearch->GetRecord()->GetName()) {
                            $param = $aSearch->GetRecord()->GetValue();
                            if (!is_numeric($param))
                                $param = "'" . $param . "'";
                            $query .= $param;
                            $aSearch->MoveNext();
                            if (!$aSearch->AtEnd())
                                $query .= ", ";
                        }
                    }
                    $parameters->MoveNext();
                }
                $query .= ");";
            }
            $this->MYGLOBALS->DB()->multi_query($query);
            $this->MYGLOBALS->DB()->next_result();
            $sqlResult = $this->MYGLOBALS->DB()->store_result();
            $result = $sqlResult;
            mysql_free_result($sqlResult);
            return $result;
        } else {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Wrong number of parameters for '".$spname."'", 
                    $aSearch->DebugPrint(), null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
        }
        return false;
    }

    private function GetSearchFieldsForUpdateOrInsert($aTable, $aRecords) {
        $indexFieldsSearch = new cSearchParams();
        $searchFields = array();
        $indexFieldsSearch->Add("TableConstant", "=", $aTable);
        $rIndexedFields = cDAL::OpenRecordset("indTables", null, $indexFieldsSearch);
        if($rIndexedFields->Count() == 1) {
            $sIndex = $rIndexedFields->Get("Index");
            $indexes = explode(",", $sIndex);
            foreach ($aRecords->Row()->GetFields() as $recordsetField) {
                foreach ($indexes as $index) {
                    if (strtolower($recordsetField) == trim(strtolower($index))) {
                        $searchFields[] = $recordsetField;
                        $noIndex = false;
                    }
                }
            }
            if(count($searchFields) == 0) {
                $primaryKey = self::GetPrimaryKeyName($aTable);
                if(!empty($primaryKey)) {
                    foreach ($aRecords->Row()->GetFields() as $recordsetField) {
                        if (strtolower($recordsetField) == trim(strtolower($primaryKey))) {
                            $searchFields[] = $recordsetField;
                            $noIndex = false;
                        }
                    }
                }
                $uniqueKeys = self::GetUniqueKeysNames($aTable);
                if(count($uniqueKeys) > 0) {
                    foreach ($aRecords->Row()->GetFields() as $recordsetField) {
                        foreach ($uniqueKeys as $uniqueKey) {
                            if (strtolower($recordsetField) == trim(strtolower($uniqueKey))) {
                                $searchFields[] = $recordsetField;
                                $noIndex = false;
                            }
                        }
                    }
                }
            }
        } else {
        }
        return $searchFields;
    }

    private static function GetPrimaryKeyName($aTable) {
        $query = "SELECT COLUMN_NAME FROM information_schema.columns ";
        $query .= "WHERE table_schema = '" . $aTable["database"] . "' AND table_name = '" . $aTable["tablename"] . "'";
        //$fields = self::RetrieveFields($aTable);
        $result = self::RunQuery($query, eSQLResultMode::SQLArray);
        if($result != false && count($result) == 1) {
            return $result["COLUMN_NAME"];
        } else {
            return null;
        }
        /*foreach($fields as $field)  {
            if($field->flags & 2) {
                return (string)$field->name;
            }
        }
        return false;*/

    }

    private function GetUniqueKeysNames($aTable) {
        $uniqueKeys = array();
        $fields = self::RetrieveFields($aTable);
        foreach($fields as $field)  {
            if($field->flags & 2) {
                $uniqueKeys[] = $field->name;
            }
        }
        if(count($uniqueKeys) > 0) {
            return $uniqueKeys;
        } else {
            return false;
        }
    }

    private function GenerateSelectStatement($aTable, $aFields = NULL, $aSearch = NULL, $aSort = NULL) {
        $groupBy = -1;
        $limit = -1;

        try {
            if ($aSearch != null) {
                $aSearch->MoveFirst();

                while (!$aSearch->AtEnd()) {
                    $paramName = strtoupper($aSearch->GetRecord()->GetName());
                    $paramValue = $aSearch->GetRecord()->GetOperator();
                    switch ($paramName) {
                        case "LIMIT":
                            //$limit = $this->SQLEscapeString($paramValue);
                            $limit = $paramValue;
                            $aSearch->Remove();
                            break;
                        case "GROUP BY":
                            //$groupBy = $this->SQLEscapeString($paramValue);
                            $groupBy = $paramValue;
                            $aSearch->Remove();
                            break;
                    }
                    $aSearch->MoveNext();
                }
            }

            $tablePath = self::GetTableFromConstant($aTable);
            if ($tablePath["database"] == "" || $tablePath["tablename"] == "") {
                throw new Exception('Unknown table constant '.$aTable);
            }

            $sqlQuery = "SELECT ";
            if (isset($aFields)) {
                if (is_object($aFields)) {
                    $aFields->MoveFirst();
                    while (!$aFields->AtEnd()) {
                        //$sqlQuery .= $this->SQLEscapeString($aFields->GetRecord()->GetElement());
                        $sqlQuery .= $aFields->GetRecord()->GetElement();
                        $aFields->MoveNext();
                        if (!$aFields->AtEnd())
                            $sqlQuery .= ", ";
                    }
                } else {
                    $sqlQuery .= self::FormatStringToFields($aFields);
                }
            } else {
                $sqlQuery .= "*";
            }
            $sqlQuery .= " FROM ";
            $sqlQuery .= "`" . $tablePath["database"] . "`.";
            $sqlQuery .= "`" . $tablePath["tablename"] . "`";
            if (isset($aSearch)) {
                $aSearch->MoveFirst();
                $sqlQuery .= " WHERE ";
                while (!$aSearch->AtEnd()) {
                    //$sqlQuery .= $this->SQLEscapeString($aSearch->GetRecord()->GetElement());
                    $sqlQuery .= $aSearch->GetRecord()->GetElement();
                    $aSearch->MoveNext();
                    if (!$aSearch->AtEnd())
                        $sqlQuery .= " AND ";
                }
            }

            if ($groupBy != -1) {
                $sqlQuery .= " GROUP BY ";
                $sqlQuery .= self::FormatStringToFields($groupBy);
            }

            if (isset($aSort)) {
                $aSort->MoveFirst();
                $sqlQuery .= " ORDER BY ";
                while (!$aSort->AtEnd()) {
                    //$sqlQuery .= $this->SQLEscapeString($aSort->GetRecord()->GetElement());
                    $sqlQuery .= $aSort->GetRecord()->GetElement();
                    $aSort->MoveNext();
                    if (!$aSort->AtEnd())
                        $sqlQuery .= ", ";
                }
            }

            if ($limit != -1) {
                $sqlQuery .= " LIMIT " . $limit;
            }
        } catch (Exception $e) {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, null, null,
                            $e->getTraceAsString(), $e->getCode(), $e->getMessage(), $this->MYGLOBALS->DB()->errno,
                            $this->MYGLOBALS->DB()->error);
            return false;
        }

        return $sqlQuery;
    }

    private function FormatStringToFields($sParams) {
        $j = 0;
        $sFields = "";
        
        $params = explode(",", $sParams);
        $count = count($params);
        foreach($params as $param) {
            $param = trim($param);
            $sFields .= "`".$param."`";
            $j++;
            if($j < $count) {
                $sFields .= ", ";
            }
        }
        return $sFields;
    }

    private function GenerateUpdateStatement($tablePath, $aRecordsetRow, $identityFieldName) {
        //aTable as String
        //aParams as cRecordset
        //aSearch as cSearchParams
        //$tablePath = self::GetTableFromConstant($aTable);
        try {
            $i = 0;
            $removedFields = 0;
            $sqlQuery = "UPDATE `" . $tablePath["database"] . "`.`" . $tablePath["tablename"] . "` SET ";
            if (isset($aRecordsetRow)) {
                $params = $aRecordsetRow->GetRecords();

                $ubound = count($params) - 1;
                foreach ($params as $col => $value) {
                    if ($col == $identityFieldName) {
                        continue;
                    }
                    if (!is_numeric($value))
                        $value = "'" . $value . "'";
                    $sqlQuery .= $col . " = " . $value;
                    //if ($i <= $ubound - $removedFields) $sqlQuery .= ", ";
                    if ($i < $ubound)
                        $sqlQuery .= ", ";
                    $i++;
                }
            }
            $identityFieldValue = $aRecordsetRow->GetRecord($identityFieldName);
            if (empty($identityFieldValue)) {
                throw new Exception("Identity field (" . $identityFieldName . ") is missing value in recordset.");
            }
            $sqlQuery .= " WHERE " . $identityFieldName . "=" . $aRecordsetRow->GetRecord($identityFieldName) . ";";
            return $sqlQuery;
        } catch (Exception $e) {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, $e->getMessage() . " in file " . $e->getFile() . " on line ".$e->getLine(),
                        null, null, null, null, null, null);
            return false;
        }
    }

    /*private function GenerateUpdateStatement($aTable, $aParams, $aSearch) {
        //aTable as String
        //aParams as cRecordset
        //aSearch as cSearchParams
        $tablePath = self::GetTableFromConstant($aTable);

        $i = 0;
        $removedFields = 0;
        $sqlQuery = "UPDATE `".$tablePath["database"]."`.`".$tablePath["tablename"]."` SET ";
        if (isset($aParams)) {
            $params = $aParams->GetRow()->GetRecords();

            $ubound = count($params) - 1;
            foreach ($params as $col => $value) {
                foreach($aSearch->GetParams() as $param) {
                    if($param->GetName() == $col) {
                        $removedFields++;
                        continue 2;
                    }
                }
                if (!is_numeric($value)) $value = "'" . $value . "'";
                $sqlQuery .= $col . " = " . $value;
                $i++;
                if ($i <= $ubound - $removedFields) $sqlQuery .= ", ";
            }
            if(count($params) <= $removedFields) {
                return "ERR::Nothing to update (nothing for SET statement)";
            }
        }
        if (isset($aSearch)) {
            $i = 0;
            $aSearch->MoveFirst();
            $sqlQuery .= " WHERE ";
            while (!$aSearch->AtEnd()) {
                $sqlQuery .= $aSearch->GetRecord()->GetName() . " " . $aSearch->GetRecord()->GetOperator() . " ";
                $iValues = $aSearch->GetRecord()->GetValue();
                $aValues = explode(",", $iValues);
                $ubound = count($aValues) - 1;
                if ($ubound > 0) {
                    $oValues = "(";
                    foreach ($aValues as $value) {
                        $value = trim($value);
                        if (is_numeric($value)) {
                            $oValues .= $value;
                        } else {
                            $oValues .= "'" . $value . "'";
                        }
                        $i++;
                        if ($i <= $ubound)
                            $oValues .= ", ";
                    }
                    $oValues .= ")";
                } else {
                    if (!is_numeric($iValues)) {
                        $oValues = $iValues;
                    } else {
                        $oValues = $iValues;
                    }
                }
                $sqlQuery .= $oValues;
                $aSearch->MoveNext();
                if (!$aSearch->AtEnd())
                    $sqlQuery .= " AND ";
            }
        }
        return $sqlQuery;
    }*/

    private function GenerateInsertStatement($tablePath, $aRecordsetRow, $identityFieldName) {
        //asTable as String
        //arData as cRecordset
        //$tablePath = self::GetTableFromConstant($asTable);

        $fieldsToInsert = self::FieldsToInsertForQuery($tablePath, $aRecordsetRow);
        if ($fieldsToInsert != false) {
            $sqlQuery = "INSERT INTO `" . $tablePath["database"] . "`.`" . $tablePath["tablename"] . "`(";

            $i = 0;
            $fieldsCount = count($fieldsToInsert);
            foreach ($fieldsToInsert as $fieldname => $value) {
                if($fieldname == $identityFieldName) {
                    continue;
                }
                $sqlQuery .= $fieldname;
                if ($i < $fieldsCount - 1) {
                    $sqlQuery .= ",";
                }
                $i++;
            }

            $sqlQuery .= ") VALUES (";

            $i = 0;
            foreach ($fieldsToInsert as $value) {
                $sqlQuery .= $value;
                if ($i < $fieldsCount - 1) {
                    $sqlQuery .= ",";
                }
                $i++;
            }

            $sqlQuery .= ");";

            return $sqlQuery;
        } else {
            $msg = "FieldsToInsertForQuery returned false";
            echo COMPONENT_NAME . "." . __CLASS__ . "." . __FUNCTION__ . ": " . $msg . "<br />";
            return false;
        }
    }
    
    /*private function GenerateInsertStatement($asTable, $arData) {
        //asTable as String
        //arData as cRecordset
        $tablePath = self::GetTableFromConstant($asTable);

        $fieldsToInsert = self::FieldsToInsertForQuery($asTable, $arData);
        if ($fieldsToInsert != false) {
            $sqlQuery = "INSERT INTO `" . $tablePath["database"] . "`.`" . $tablePath["tablename"] . "`(";

            $i = 0;
            $fieldsCount = count($fieldsToInsert);
            foreach ($fieldsToInsert as $fieldname => $value) {
                $sqlQuery .= $fieldname;
                if ($i < $fieldsCount - 1) {
                    $sqlQuery .= ",";
                }
                $i++;
            }

            $sqlQuery .= ") VALUES (";

            $i = 0;
            foreach ($fieldsToInsert as $value) {
                $sqlQuery .= $value;
                if ($i < $fieldsCount - 1) {
                    $sqlQuery .= ",";
                }
                $i++;
            }

            $sqlQuery .= ")";

            return $sqlQuery;
        } else {
            $msg = "FieldsToInsertForQuery returned false";
            echo COMPONENT_NAME . "." . __CLASS__ . "." . __FUNCTION__ . ": " . $msg . "<br />";
            return false;
        }
    }*/

    private function isStoredProcedure($aTable) {
        if (!empty($aTable)) {
            $tablePath = self::GetTableFromConstant($aTable);
            self::FreeMoreResults();
            $query =    "SELECT COUNT(*) FROM mysql.proc WHERE db = '" . strtolower($tablePath["database"]) .
                        "' AND specific_name = '" . $tablePath["tablename"] . "'";
            $result = $this->MYGLOBALS->DB()->query($query);
            echo $this->MYGLOBALS->DB()->error;
            if($result == false) {
                return -1;
            } else {
                $data = $result->fetch_array();
                if ($data[0] > 0) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    private function FreeMoreResults() {
        if(is_object($this->MYGLOBALS)) {
            if ($this->MYGLOBALS->DB()->more_results()) {
                while ($this->MYGLOBALS->DB()->next_result());
            }
        }
    }

    private static function RetrieveFields($tableConstant) {
        $tablePath = self::GetTableFromConstant($tableConstant);
        return self::RetrieveFields2($tablePath['database'], $tablePath['tablename']);
    }

    private static function RetrieveFields2($db, $table) {
        $fields = array();
        $i = 0;

        if (!empty($db) && !empty($table)) {
            //$tablePath = self::GetTableFromConstant($asTable);
            $query = "SELECT * FROM `" . $db . "`.`" . $table . "` LIMIT 1";
            $result = self::RunQuery($query, eSQLResultMode::SQLResult);//$this->MYGLOBALS->DB()->query($query);
            if ($result != false) {
                while ($i < $result->field_count) {
                    $field = $result->fetch_field();
                    $fields[] = $field;
                    $i++;
                }
                $result->free_result();
            } else {
                return false;
            }
        } else {
            return false;
        }
        return $fields;
    }

    private function FieldsToInsertForQuery($asTable, $arRecordsetRow) {
        $tableFields = self::RetrieveFields($asTable);
        if ($tableFields != false) {
            try {
                //$row = $arRecordset->GetRow();
                foreach ($arRecordsetRow->GetRecords() as $key => $value) {
                    foreach ($tableFields as $field) {
                        if (strtolower($key) == strtolower($field->name)) {
                            if (($field->flags & 1) && (empty($value) || $value == null)) {
                                if ($field->flags & 32768) {
                                    $value = "";
                                } else {
                                    $value = 0;
                                }
                            }

                            if ($field->flags & 32768) {
                                $fieldsToInsert[$key] = $value;
                            } else {
                                $fieldsToInsert[$key] = chr(39).str_replace(chr(39), chr(34), $value).chr(39);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, null, null,
                        $e->getTraceAsString(), $e->getCode(), $e->getMessage(), $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
                return false;
            }
        } else {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "RetrieveFields returned false", null,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
            return false;
        }
        return $fieldsToInsert;
    }
    
    private function GetProcedureParameters($procedureName) {
        if(!empty($procedureName)) {
            $query = "SELECT specific_name, parameter_name, data_type, ordinal_position ";
            $query .= "FROM `INFORMATION_SCHEMA`.`PARAMETERS` WHERE specific_name = '" . $procedureName . "' ";
            $query .= "ORDER BY ordinal_position";
            $result = $this->MYGLOBALS->DB()->query($query);
            if($result != false) {
                $parameters = new cRecordset($result);
                $parameters->MoveFirst();
                while(!$parameters->AtEnd()) {
                    $parameters->Add("parameter_name", ltrim($parameters->Get("parameter_name"), "$"));
                    $parameters->MoveNext();
                }
                return $parameters;
            } else {
                cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Stored procedure ".$procedureName." wasn't found", null,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
                return false;
            }
        }
        return false;
    }

    public function BeginTransaction() {
        if(!$this->transactionStarted) {
            $query = "START TRANSACTION;";
            $result = $this->MYGLOBALS->DB()->query($query);
            if($result == false) {
                cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "BeginTransaction failed", null,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
            }
            $this->transactionStarted = true;
            $this->transactionCommited = false;
            $this->transactionCancelled = false;
        } else {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Another transaction already in progress", null,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
        }
        return $this->transactionStarted;
    }

    public function CommitTransaction() {
        if(!$this->transactionCommited) {
            $query = "COMMIT;";
            $result = $this->MYGLOBALS->DB()->query($query);
            if($result == false) {
                cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "CommitTransaction failed", null,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
            }
            $this->transactionStarted = false;
            $this->transactionCommited = true;
            $this->transactionCancelled = false;
        } else {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Transaction already commited", null,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
        }
        return $this->transactionCommited;
    }

    public function CancelTransaction() {
        if(!$this->transactionCancelled) {
            $query = "ROLLBACK;";
            $result = $this->MYGLOBALS->DB()->query($query);
            if($result == false) {
                cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "CancelTransaction failed", null,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
            }
            $this->transactionStarted = false;
            $this->transactionCommited = false;
            $this->transactionCancelled = true;
        } else {
            cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "Transaction already cancelled", null,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
        }
        return $this->transactionCancelled;
    }

    private static function GetTableFromConstant($tableConstant) {
        if(!empty($tableConstant)) {
            $query = "SELECT `database`, `tablename` FROM `independence`.`tblTables` ";
            $query.= "WHERE tableconstant = '".$tableConstant."'";
            //self::FreeMoreResults();
            //$result = $this->MYGLOBALS->DB()->query($query);
            $result = self::RunQuery($query, eSQLResultMode::SQLArray);
            var_dump($result);
            //$res = new mysqli_result($result);
            //var_dump($res);
            if($result === false) {
                cError::Report(COMPONENT_NAME, "cDAL", __FUNCTION__, "GetTableFromConstant failed", null,
                        null, null, null, $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
                return false;
            } else {
                return $result;
            }
        }
    }

    private function SQLEscapeString($input) {
        return mysql_real_escape_string($input);
    }

}

class eSQLStatementType {
    const CLASS_NAME = "eSQLStatementType";
    const Update = "Update";
    const Insert = "Insert";
}

class eSQLResultMode {
    const SQLResult = 0;
    const SQLArray = 1;
    const SQLRecordset = 2;
}

class cErrorMessage {
    private $errNum = null;
    private $errMsg = null;

    public function _construct($num, $msg) {
        $this->errNum = $num;
        $this->errMsg = $msg;
    }

    public function ErrMsg($msg = null) {
        if($msg == null) {
            return $this->errMsg;
        } else {
            $this->errMsg = $msg;
        }
    }

    public function ErrNum($num = null) {
        if($num == null) {
            return $this->errNum;
        } else {
            $this->errNum = $num;
        }
    }
}

?>