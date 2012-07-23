<?php

/*$db = new mysqli("localhost", "root", "", "independence");
var_dump($db);
$result = $db->query("SELECT 'aha'");
var_dump($result);
var_dump($db->client_info);
var_dump($db->client_version);
var_dump($db->info);*/


require "/Components/cCore/cGLOBALS.php";
require "/Components/cCore/cParams.php";
require "/Components/cCore/cRecordset.php";
require "/Components/cCore/cDAL.php";
include "/Components/cSecurity/cLogin.php";


/*$db = new mysqli("localhost", "root", "", "independence");
var_dump($db);
$result = $db->query("SELECT 'aha'");
var_dump($result);*/

/*$con = mysqli_connect("localhost","root","");
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

mysqli_select_db("independence", $con);

$result = mysqli_query("SELECT 'kokot' as aaa");

while($row = mysqli_fetch_array($result))
  {
  echo $row['aaa'] . " " . $row['LastName'];
  echo "<br />";
  }

mysql_close($con);*/

/*$con = new mysqli(cGLOBALS::SERVER, cGLOBALS::SERVERUSER, cGLOBALS::SERVERPASSWORD, cGLOBALS::DBNAME);
//$aQuery = $con->real_escape_string("SELECT 'aaa' as test");
$aQuery = "SELECT 'aaa' as test";
$result = $con->query($aQuery);

if($result) {
    $data = $result->fetch_assoc();

    $result->free();
    while($con->next_result()) {
        $result = $con->use_result();
        if($result instanceof mysqli_result) {
            $result->free();
        }
    }
}*/

$MYGLOBALS = new cGLOBALS();
$dal = new cDAL();
$recordset = new cRecordset();

$recordset->AddRow();
$recordset->Add("TableConstant", "indTables");
$recordset->Add("TableName", "aaa");

$result = $dal->UpdateRecordset("indTables", $recordset);


$i = 5;

$login = new cLogin();
$prSearch = new cSearchParams();
$prSort = new cSortParams();
$prFields = new cFieldParams();

$prSearch->Add("login", "=", "admin");
//$prSearch->Add("EFM", "=", "M&G");
//$prSearch->Add("InstrumentCode", "=", "JK55.LN");

$prSort->Add("idtblUsers", "desc");
$prSort->Add("login");
$prFields->Add("login");
$prFields->Add("password");

//$prSearch->DebugPrint();
//$prSort->DebugPrint();

/*$prSearch->MoveFirst();
$prSearch->GetRecord()->DebugPrint();
$prSearch->MoveNext();
$prSearch->GetRecord()->DebugPrint();*/

//$prRecordset = new cRecordset();
//var_dump($prRecordset);
//$prRecordset->AddRow();
//var_dump($row);

/*$prRecordset->Row()->Add("webname", "petr_opt");
$prRecordset->Row()->Add("webpassword", "wrap123");
$prRecordset->AddRow();
$prRecordset->Row()->Add("webname", "petr_admin");
$prRecordset->Row()->Add("webpassword", "secret");
$prRecordset->Clear();*/

//if($i>0) trigger_error("Value bigger than 0", E_USER_WARNING);

$dal = new cDAL();
//$recordset = $dal->OpenRecordset("spGetLoginDetails", NULL, $prSearch, $prSort);
$recordset = $dal->OpenRecordset("vwUsersContact", null, null, null);
//$recordset->DebugPrint();
$recordset->SetInternalData("name", "test");
echo $recordset->GetInternalData("nam");
echo $recordset->GetInternalData(0);
echo $recordset->GetErrorMsg();
foreach($recordset->GetFields() as $field) {
	echo $field."<br />";
}

$update = new cRecordset();
$update->AddRow();
$update->Add("login", "Jouda");
$update->Add("ip", "127.0.0.1");


$upSearch = new cSearchParams();
$upSearch->Add("userid", "=", 1);
$upSearch->Add("userid", "IN", "2,3,hovno");

$recordset->MoveFirst();
while(!$recordset->AtEnd()) {
	echo $recordset->Get("firstname");
	$recordset->MoveNext();
}

//echo $dal->GenerateUpdateStatement("indUsers", $update, $upSearch);

$login = new cLogin();
$login->LoginUser("panvelky", "panvelky");
$_SESSION["auth"] = $MYGLOBALS->Serialize($login);
echo $_SESSION["auth"];
echo $MYGLOBALS->Unserialize($_SESSION["auth"]);
echo SID;
//$dal->OpenRecordset("tblClients", NULL, $prSearch, $prSort);

//$prRecordset->DebugPrint();


?>