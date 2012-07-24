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

    public static function do_dump(&$var, $var_name = NULL, $indent = NULL, $reference = NULL) {
        $do_dump_indent = "<span style='color:#666666;'>|</span> &nbsp;&nbsp; ";
        $reference = $reference . $var_name;
        $keyvar = 'the_do_dump_recursion_protection_scheme';
        $keyname = 'referenced_object_name';

        // So this is always visible and always left justified and readable
        echo "<div style='text-align:left; background-color:white; font: 100% monospace; color:black;'>";

        if (is_array($var) && isset($var[$keyvar])) {
            $real_var = &$var[$keyvar];
            $real_name = &$var[$keyname];
            $type = ucfirst(gettype($real_var));
            echo "$indent$var_name <span style='color:#666666'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
        } else {
            $var = array($keyvar => $var, $keyname => $reference);
            $avar = &$var[$keyvar];

            $type = ucfirst(gettype($avar));
            if ($type == "String")
                $type_color = "<span style='color:green'>";
            elseif ($type == "Integer")
                $type_color = "<span style='color:red'>";
            elseif ($type == "Double") {
                $type_color = "<span style='color:#0099c5'>";
                $type = "Float";
            } elseif ($type == "Boolean")
                $type_color = "<span style='color:#92008d'>";
            elseif ($type == "NULL")
                $type_color = "<span style='color:black'>";

            if (is_array($avar)) {
                $count = count($avar);
                echo "$indent" . ($var_name ? "$var_name => " : "") . "<span style='color:#666666'>$type ($count)</span><br>$indent(<br>";
                $keys = array_keys($avar);
                foreach ($keys as $name) {
                    $value = &$avar[$name];
                    self::do_dump($value, "['$name']", $indent . $do_dump_indent, $reference);
                }
                echo "$indent)<br>";
            } elseif (is_object($avar)) {
                echo "$indent$var_name <span style='color:#666666'>$type</span><br>$indent(<br>";
                foreach ($avar as $name => $value)
                    self::do_dump($value, "$name", $indent . $do_dump_indent, $reference);
                echo "$indent)<br>";
            } elseif (is_int($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . strlen($avar) . ")</span> $type_color" . htmlentities($avar) . "</span><br>";
            elseif (is_string($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . strlen($avar) . ")</span> $type_color\"" . htmlentities($avar) . "\"</span><br>";
            elseif (is_float($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . strlen($avar) . ")</span> $type_color" . htmlentities($avar) . "</span><br>";
            elseif (is_bool($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . strlen($avar) . ")</span> $type_color" . ($avar == 1 ? "TRUE" : "FALSE") . "</span><br>";
            elseif (is_null($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . strlen($avar) . ")</span> {$type_color}NULL</span><br>";
            else
                echo "$indent$var_name = <span style='color:#666666'>$type(" . strlen($avar) . ")</span> " . htmlentities($avar) . "<br>";

            $var = $var[$keyvar];
        }

        echo "</div>";
    }
}
?>
