<?php

require_once "IMoveable.php";
require_once "exceptions.php";

define("COMPONENT_NAME", "cRecordset");

class cRecordset implements IMoveable {

    protected $errNum = 0;
    protected $errMsg;
    protected $internalData = array();
    private $mRows = array();
    private $isFieldsSet = false;
    //private $mFields = array();
    private $fields = array();
    //private $fieldsCount;
    protected $mPos = -1;
    protected $mSize = 0;
    //private $action;
    //public static $actions = array("Update" => 0, "Insert" => 1, "Delete" => 2);
    private $indexFields = array();
    Private $mnMaxRecords;
    Private $mnEndRecord; //Zero-based index of last row to retrieve
    Private $mnStartRecord; //First record to retrieve
    Private $mbGetTotalCount;
    Private $mbSelectDistinct;
    Private $mnSelectTop;  //select top n
    Private $mbIncludeIDFields;
    Private $msGroupBy;
    Private $mnGroupByCount;



    public function __construct(&$aResult = null) {
        //$this->action = $this->SetAction("Update");
        $i = 0;
        if ($aResult != null) {
            cCommon::do_dump($aResult);
            /*while ($row = $aResult->fetch_assoc()) {
                $this->AddRow($row);

                //naplneni nazvu sloupcu
                if (!$this->isFieldsSet) {
                    $this->fields = new cFields($aResult);
                    if($this->fields != false) {
                        $this->isFieldsSet = true;
                    }

                    //$this->SetFields($aResult);
                }
            }
            echo "kokot";*/
            while($row = $aResult->fetch_array()) {
                cCommon::do_dump($row);
            }

            $rows = $aResult->fetch_all();
            foreach($rows as $row) {
                $this->AddRow($row);

                //naplneni nazvu sloupcu
                if (!$this->isFieldsSet) {
                    $this->fields = new cFields($aResult);
                    if($this->fields != false) {
                        $this->isFieldsSet = true;
                    }
                }
            }
        }
    }

    /*private function SetFields($aResult) {
        // aResult - query result

        $i = 0;
        while ($i < $aResult->field_count) {
            $field = $aResult->fetch_field();
            $this->fields[] = $field;
            $i++;
        }
        $this->isFieldsSet = true;
        $this->fieldsCount = count($this->fields);
    }*/

    public function AddIndex($index) {
        if(!empty($index)) {
            if(is_numeric($index)) {
                return false;
            } else {
                $this->indexFields = $index;
            }
        }
        return true;
    }

    public function ClearIndex() {
        $this->indexFields = array();
    }

    public function MoveTo($key) {
        if(!empty($key)) {
            $this->MoveFirst();
            foreach($this->indexFields as $indexField) {
                while($this->AtEnd()) {
                    if($this->Get($indexField) == $key) {
                        // $key value found in index field
                        // we can access the row using mPos property
                        return true;
                    }
                }
            }
        }
        //no $key value found in index fields
        return false;
    }

    public function Remove() {
        try {
            unset($this->mRows[$this->mPos]);
            $this->mRows = array_values($this->mRows);
            $this->setSize();
            $this->MovePrevious();
        } catch (Exception $e) {
            $this->SetError(1, $e->getMessage());
        }
    }

    /*public function SetAction($action) {
        try {
            $this->action = self::$actions[$action];
        } catch (Exception $e) {
            $this->SetInternalData("warning", "Unknown recordset action '$action', UPDATE used.");
        }
    }*/

    /*public function GetAction($action = null) {
        if ($action == null) {
            return $this->action;
        }

        switch(strtolower($action)) {
            case "update":
                return self::$actions["Update"];
                break;
            case "insert":
                return self::$actions["Insert"];
                break;
            case "delete":
                return self::$actions["Delete"];
                break;
            default:
                $this->SetInternalData("warning", "Unknown recordset action '$action'");
                return false;
        }
    }*/

    public function Action($action = null)
    {
        if($action === null) {
            return $this->Row()->Action;
        }
        $this->Row()->Action = $action;
    }

    public function Fields() {
        return $this->fields;
    }

    public function Field($name) {
        return $this->fields->Field($name);
    }

    /*public function FieldsCount() {
        return $this->fieldsCount;
    }*/

    private function SetError($errNum, $errMsg) {
        $this->errNum = $errNum;
        $this->errMsg = $errMsg;
    }

    public function GetErrorNum() {
        return $this->errNum;
    }

    public function GetErrorMsg() {
        return $this->errMsg;
    }

    public function GetInternalData($id) {
        try {
            if (is_numeric($id)) {
                if (!$this->internalData[$id]) {
                    throw new Exception("Index $id doesn't exist \n");
                } else {
                    return $this->internalData[$id];
                }
            } else {
                if (!$this->internalData["$id"]) {
                    throw new Exception("Index '$id' doesn't exist \n");
                } else {
                    return $this->internalData["$id"];
                }
            }
        } catch (Exception $e) {
            $this->SetError(1, $e->getMessage());
        }
    }

    public function SetInternalData($id, $value) {
        if (!empty($id) AND !empty($value)) {
            $this->internalData["$id"] = $value;
        }
    }

    /* public function FieldInfo() {
      return $this->fieldInfo;
      }

      public function GetFields() {
      return $this->mFields;
      } */

    public function Get($aKey = null) {
        return $this->Row()->GetRecord($aKey);
    }

    public function AddRow($recordsArray = null) {
        try {
            $this->mRows[] = new cRecordsetRow($recordsArray);
        } catch (AddRecordException $e) {
            echo $e->GetMsg();
        }
        $this->setSize();
        $this->mPos++;
    }

    public function Add($col, $value) {
        if ($this->Count() <= 0)
            $this->AddRow();
        $this->Row()->AddRecord($col, $value);
    }

    public function Set($col, $value) {

    }

    public function Row() {
        return $this->mRows[$this->mPos];
    }

    public function AtBeginning() {
        return ($this->mPos < 1 || $this->mSize == 0);
    }

    public function AtEnd() {
        return ($this->mPos == $this->mSize || $this->mSize == 0);
    }

    public function Count() {
        return count($this->mRows);
    }

    public function MoveFirst() {
        $this->mPos = 0;
        return $this->mPos;
    }

    public function MoveLast() {
        $this->mPos = $this->Count();
    }

    public function Clear() {
        $this->mRows = array();
        $this->mFields = array();
        $this->isFieldsSet = false;
        $this->mPos = -1;
        $this->mSize = 0;
        return $this->mPos;
    }

    public function MoveNext() {
        if (!$this->AtEnd()) {
            $this->mPos++;
            return $this->mPos;
        } else {
            return -1;
        }
    }

    public function MovePrevious() {
        if (!$this->AtBeginning()) {
            $this->mPos--;
            return $this->mPos;
        } else {
            return -1;
        }
    }

    protected function setSize() {
        $this->mSize = count($this->mRows);
        return $this->mSize;
    }

    public function GetRow($row = 0) {
        return $this->mRows[$row];
    }

    public function DebugPrint() {
        $position = $this->mPos;
        $this->MoveFirst();
        print "cRecordset DebugPrint: <br />";
        foreach ($this->mRows as $record) {
            print($record->DebugPrint() . "<br />");
        }
        $this->mPos = $position;
    }

}

class cRecordsetRow extends cRecordset implements IMoveable {

    private $mRecords = array();
    public $Action = eRecordsetAction::Unknown;

    public function __construct($recordsArray = null) {
        if ($recordsArray != null) {
            $recordsArray = array_change_key_case($recordsArray, CASE_LOWER);
            $this->mRecords = $recordsArray;
        }
    }

    public function AddRecord($col, $value) {
        if(!is_numeric($col)) {
            $col = strtolower($col);
        }
        $this->mRecords["$col"] = $value;
        $this->setSize();
        $this->mPos++;
    }

    public function DebugPrint() {
        //$this->Clear();
        foreach ($this->mRecords as $key => $value) {
            print (string) $key . ": " . (string) $value . ", ";
        }
    }

    /* public function DebugPrint() {
      //$this->Clear();
      foreach($this->mRecords as $row) {
      print $row->GetColName().": ".$row->GetValue().", ";
      }
      } */

    /* public function GetRecord() {
      return ($this->mRows[$this->mPos]);
      } */

    public function GetRecord($aKey = null) {
        if ($aKey != null) {
            if (is_numeric($aKey)) {
                return ($this->mRecords[$aKey]);
            } else {
                $aKey = strtolower($aKey);
                return ($this->mRecords["$aKey"]);
            }
        } else {
            return ($this->mRecords[$this->mPos]);
        }
    }

    public function GetRecords() {
        return $this->mRecords;
    }

    public function GetFields() {
        $fields = array();
        foreach($this->mRecords as $col => $value) {
            $fields[] = "$col";
        }
        return $fields;
    }

}

class cRecord {

    private $colName;
    private $value;

    public function __construct($colName, $value) {
        $this->colName = strtolower($colName);
        $this->value = $value;
    }

    public function GetColName() {
        return $this->colName;
    }

    public function GetValue() {
        return $this->value;
    }

}

class cFields {
    private $fields;
    private $fieldsCount;
    private $isFieldsSet = false;

    public function __construct($aResult) {
        // aResult - query result
        if(isset($aResult)) {
            $i = 0;
            while ($i < $aResult->field_count) {
                $field = $aResult->fetch_field();
                //$this->fields[] = $field;
                $objField = new cField($field);
                $this->fields[$objField->GetName()] = $objField;
                $i++;
            }
            $this->isFieldsSet = true;
            $this->fieldsCount = count($this->fields);
        }
    }

    public function Count() {
        return $fieldsCount;
    }

    public function NamesToString() {
        if($this->isFieldsSet) {
            $i = 0;
            $string = "";
            foreach($this->fields as $key => $value) {
                $string = $string.$key;
                if($i < $this->fieldsCount) {
                    $string = $string.", ";
                }
                $i++;
            }
            return $string;
        }
        return false;
    }

    public function Fields() {
        return $this->fields;
    }

    public function Field($name) {
        return $this->fields["$name"];
    }
}

class cField {

    /* Flags
      NOT_NULL_FLAG = 1
      PRI_KEY_FLAG = 2
      UNIQUE_KEY_FLAG = 4
      BLOB_FLAG = 16
      UNSIGNED_FLAG = 32
      ZEROFILL_FLAG = 64
      BINARY_FLAG = 128
      ENUM_FLAG = 256
      AUTO_INCREMENT_FLAG = 512
      TIMESTAMP_FLAG = 1024
      SET_FLAG = 2048
      NUM_FLAG = 32768
      PART_KEY_FLAG = 16384
      GROUP_FLAG = 32768
      UNIQUE_FLAG = 65536
     */

    private $fieldname;
    private $table;
    private $maxLength;
    private $default;
    private $length;
    private $charSet;
    private $flags;
    private $type;
    private $decimals;

    public function __construct($aSqlField) {
        if(isset($aSqlField)) {
            $this->SetProperties($aSqlField);
        } else {
            return false;
        }
    }

    private function SetProperties($aSqlField) {
        $this->fieldname = $aSqlField->name;
        $this->table = $aSqlField->table;
        $this->maxLength = $aSqlField->max_length;
        $this->default = $aSqlField->def;
        $this->length = $aSqlField->length;
        $this->charSet = $aSqlField->charsetnr;
        $this->flags = $aSqlField->flags;
        $this->type = $aSqlField->type;
        $this->decimals = $aSqlField->decimals;
    }

    public function GetName() {
        return (string)$this->fieldname;
    }

    public function GetTableBelongsTo() {
        return (string)$this->table;
    }

    public function GetLength() {
        return (int)$this->length;
    }

    public function GetMaxLength() {
        return (int)$this->maxLength;
    }

    public function GetDefaultValue() {
        return (string)$this->default;
    }

    public function GetCharSet() {
        return (int)$this->charSet;
    }

    public function GetDataType() {
        return (string)$this->type;
    }

    public function GetDecimals() {
        return (int)$this->decimals;
    }

    public function IsNotNull() {
        if($this->dataType & 1) {
            return true;
        } else {
            return false;
        }
    }

    public function IsPrimaryKey() {
        if($this->dataType & 2) {
            return true;
        } else {
            return false;
        }
    }

    public function IsUniqueKey() {
        if($this->dataType & 4) {
            return true;
        } else {
            return false;
        }
    }

    public function IsNumeric() {
        if($this->dataType & 32768) {
            return true;
        } else {
            return false;
        }
    }

    public function IsUnique() {
        if($this->dataType & 65536) {
            return true;
        } else {
            return false;
        }
    }

    public function IsUnsigned() {
        if($this->dataType & 32) {
            return true;
        } else {
            return false;
        }
    }

    public function IsZerofilled() {
        if($this->dataType & 64) {
            return true;
        } else {
            return false;
        }
    }

    public function IsBinary() {
        if($this->dataType & 128) {
            return true;
        } else {
            return false;
        }
    }

    public function IsEnum() {
        if($this->dataType & 256) {
            return true;
        } else {
            return false;
        }
    }

    public function IsAutoIncrement() {
        if($this->dataType & 512) {
            return true;
        } else {
            return false;
        }
    }

    public function IsTimestamp() {
        if($this->dataType & 1024) {
            return true;
        } else {
            return false;
        }
    }
}

class eRecordsetAction
{
    const Insert = "Insert";
    const Update = "Update";
    const Unknown = "Unknown";
}

?>