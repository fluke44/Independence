<?php
require "/Components/cCore/cGLOBALS.php";
require "/Components/cCore/cParams.php";
require "/Components/cCore/cRecordset.php";
require "/Components/cCore/cDAL.php";
include "/Components/cSecurity/cLogin.php";

$MYGLOBALS = new cGLOBALS();
$dal = new cDAL();
$search = new cSearchParams();
$recordset = new cRecordset();
$login = new cLogin;

/*$result = $dal->GetTableFromConstant("indSystemVariables");
echo $result["database"];
echo $result["tablename"];*/

/*$search->Add("webname", "=", "idiot");
$search->Add("group by", "webname");
$search->Add("limit", "1");

$result = $dal->OpenRecordset("indUsers", "webname, password", $search);

$recordset->AddRow();
$recordset->Add("clientid", 1);
$recordset->Add("email", "idiot@idiot.cz");
/*$recordset->AddRow();
$recordset->Add("webname", "Petr");
$recordset->Add("email", "c@de.cz");

$dal->BeginTransaction();
$result = $dal->UpdateRecordset("indClients", $recordset);
if($result != false) {
    $dal->CommitTransaction();
} else {
    $dal->CancelTransaction();
}*/

$status = $login->LogInUser("admin", "secret");
echo $login->GetErrMsg();
$status = $login->AuthoriseUser();

$search->Add("username", "=", "idiot");
$search->Add("password", "=", "idiot");
$result = $dal->OpenRecordset("spGetUserDetails", null, $search);

if($result == false) {
    echo "aaa";
}

echo "<br />";

$search = new cSearchParams();

$search->Add("TableConstant", "=", "indTables");

$result = $dal->OpenRecordset("indTables", null, $search);

$result->DebugPrint();

/*$link = mysql_connect("localhost", "root", "");
$result = mysql_query("SELECT * FROM `independence`.indTables`", $link);
var_dump($result);
echo mysql_result($result, 0, "idtblTables");
echo mysql_result($result, 0, "TableConstant");
echo($result);*/
?>