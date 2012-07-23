<?php
require_once "IMoveable.php";

abstract class cParams implements IMoveable {
    protected $mArray = array();
    protected $mPos = -1;
    protected $mSize = 0;

    public function AtBeginning() {
        return ($this->mPos < 1 || $this->mSize == 0);
    }

    public function AtEnd() {
        if ($this->mPos >= $this->mSize || $this->mSize == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function Remove() {
        try {
            unset($this->mArray[$this->mPos]);
            $this->mArray = array_values($this->mArray);
            $this->setSize();
            $this->MovePrevious();
        } catch (Exception $e) {
            $this->SetError(1, $e->getMessage());
        }
    }
    
    public function Count() {
    	return count($this->mArray);
    }
    
    public function MoveFirst() {
		$this->mPos = 0;
		return $this->mPos;
	}
	
	public function MoveLast() {
		$this->mPos = $this->Count() - 1;
	}
	
	public function Clear() {
		$this->mPos = -1;
                $this->mSize = 0;
                unset($this->mArray);
		return $this->mPos;
	}
	
	public function MoveNext() {
		if(!$this->AtEnd()) {
			$this->mPos++;
			return $this->mPos;
		} else {
			return -1;
		}
	}
	
	public function MovePrevious() {
		if(!$this->AtBeginning()) {
			$this->mPos--;
			return $this->mPos;
		} else {
			return -1;
		}
	}
	
	protected function setSize() {
		$this->mSize = count($this->mArray);
		return $this->mSize;
	}
	
	public function DebugPrint() {
            $i = 0;
            $string = "cParams DebugPrint: <br />";
            foreach($this->mArray as $param) {
                $string .= $param->GetName().", ".$param->GetOperator().", ".$param->GetValue()."<br />";
                $i++;
            }
            print($string);
            $string = substr(str_replace('<br />', '; ', $string), 26);
            return $string;
	}
	
	public function GetRecord() {
		//echo $this->mPos;
		return ($this->mArray[$this->mPos]);
	}

        public function GetParams() {
            return $this->mArray;
        }
	
}

class cSearchParams extends cParams {
    private $junctionOperator;
	
    public function Add($name, $operator, $value = null, $junction = "AND") {
        $this->mArray[] = new cSearchElement($name, $operator, $value, $junction);
        $this->setSize();
        $this->MoveNext();
    }
	
    public function Get($element) {
        return $this->mArray[$element]->GetElement();
    }
	
}

class cSortParams extends cParams {
	
	public function Add($name, $direction = null) {
		$this->mArray[] = new cSortElement($name, $direction);
		$this->mPos++;
		$this->setSize();
	}
	
	public function Get($element) {
		return $this->mArray[$element]->GetElement();
	}
}

class cFieldParams extends cParams {
	
	public function Add($name, $db = DBNAME) {
		$this->mArray[] = new cFieldElement($name, $db);
		$this->mPos++;
		$this->setSize();
	}
	
	public function Get($element) {
		return $this->mArray[$element]->GetElement();
	}
}

class cSearchElement {
	private $name;
	private $operator;
	private $value;
	private $junction;
	
	public function __construct($name, $operator, $value, $junction) {
		$this->name = $name;
		$this->operator = $operator;
		$this->value = $value;
		$this->junction = $junction;
	}
	
	public function GetName() {
		return $this->name;
	}
	
	public function GetOperator() {
		return $this->operator;
	}
	
	public function GetValue() {
		return $this->value;
	}
	
	public function GetElement() {
		if(!is_numeric($this->value)) $this->value = "'".$this->value."'";
		return $this->name." ".$this->operator." ".$this->value;
	}

	public function DebugPrint() {
		print $this->name.", ".$this->operator.", ".$this->value."<br />";
	}
}

/*class cSortElement {
	private $element = Array();
	
	public function Add($name, $direction) {
		$this->element[$name] = $direction;
	}
}*/
class cSortElement {
	private $name;
	private $direction;
	
	public function __construct($name, $direction) {
		$this->name = $name;
		$this->direction = $direction;
	}

	public function DebugPrint() {
		print $this->name.", ".$this->direction."<br />";
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getDirection() {
		return $this->direction;
	}
	
	public function GetElement() {
		return $this->name." ".$this->direction;
	}
}

class cFieldElement {
	private $name;
	private $db;
	
	public function __construct($name, $db = null) {
		$this->name = $name;
		$this->db = $db;
	}

	public function DebugPrint() {
		echo $this->db.".".$this->name."<br />";
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getDB() {
		return $this->db;
	}
	
	public function GetElement() {
		return "`".$this->name."`";
	}
}
?>