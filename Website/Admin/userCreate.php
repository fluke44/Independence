<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require $root.'/Website/Base/master.page';
require $root . "/Website/security/authorise_header.php";

include_once $root . "/Components/cCore/cDAL.php";
include_once $root . "/Components/cCore/cRecordset.php";
include_once $root . "/Components/cCore/cParams.php";
include_once $root . "/Components/cCore/cCommon.php";
include_once $root . "/Components/cCore/cError.php";
include_once $root . "/Components/cSecurity/cLogin.php";

$gPageCaption = "Vytvořit nového uživatele";

$dal = new cDAL();
$search = new cSearchParams();
$sort = new cSortParams;
$userMsg = "";

if (isset($_GET["action"])) {
    $action = $_GET["action"];
} else {
    $action = "";
}

if($action == "save") {
    $updateUser = new cRecordset();
    $updateClient = new cRecordset();
    
    $updateUser->AddRow();
    if(isset($_POST["tbLogin"])) {
        $updateUser->Add("webname", (string)$_POST["tbLogin"]);
    } else {
        $userMsg = "Neplatný název uživatele";
    }
    if(isset($_POST["tbPassword"]) && isset($_POST["tbConfirmPassword"])) {
        if($_POST["tbPassword"] == $_POST["tbConfirmPassword"]) {
            $password = cLogin::HashPassword($_POST["tbPassword"], $_POST["tbLogin"]);
            $updateUser->Add("password", (string)$password);
        }
    } else {
        $userMsg = "Hesla se neshodují";
    }
    
    $updateClient->AddRow();
    if(isset($_POST["tbEmail"])) {
        $updateClient->Add("email", (string)$_POST["tbEmail"]);
    } else {
        $userMsg = "Neplatný email";
    }
    if(isset($_POST["tbFirstName"])) {
        $updateClient->Add("firstname", (string)$_POST["tbfirstName"]);
    } else {
        $userMsg = "Není vyplněno jméno uživatele";
    }
    if(isset($_POST["tbLastName"])) {
        $updateClient->Add("surname", (string)$_POST["tbLastName"]);
    } else {
        $userMsg = "Není vyplněno příjmení";
    }
    if(isset($_POST["tbStreet"])) {
        $updateClient->Add("street", (string)$_POST["tbStreet"]);
    }
    if(isset($_POST["tbCity"])) {
        $updateClient->Add("city", (string)$_POST["tbCity"]);
    }
    if(isset($_POST["tbPSC"])) {
        $updateClient->Add("psc", (string)$_POST["tbPSC"]);
    }
    if(isset($_POST["tbCountry"])) {
        $updateClient->Add("country", (string)$_POST["tbCountry"]);
    }
    if(isset($_POST["tbPhone"])) {
        $updateClient->Add("phone", $_POST["tbPhone"]);
    }

    $dal->BeginTransaction();
    $updateUser = $dal->UpdateRecordset("indUsers", $updateUser);

    $updateClient->Add("userid", $update->Get("userid"));
    $updateClient = $dal->UpdateRecordset("indClients", $updateClient);

    if($updateUser->GetInternalData("ErrNo") == 0 && $updateClient->GetInternalData("ErrNo") == 0) {
        $dal->CommitTransaction();
        $msg = "Uživatel vytvořen";
    } else {
        $dal->CancelTransaction();
        $msg .= "\nChyba při vytváření uživatele";
        cError::Report(COMPONENT_NAME, "userCreate.php", __FUNCTION__, $msg,
                    $updateUser->DebugPrint()."\n".$updateClient->DebugPrint(), null, null, null,
                    null, null);
    }
    $userMsg = $msg;
}

?>

<?php //include $root."/Website/Base/pageHeader.php"; ?>

<h1><?php echo $gPageCaption; ?></h1>

<span>
<?php
if(isset($userMsg)) {
    echo $userMsg;
}
?>
</span>

<span>
    <form action="?action=save" method="POST">
        <label for="tbLogin">Název uživatele:</label><input type="text" name="tbLogin" id="tbLogin">
        <label for="tbPassword">Heslo:</label><input type="password" name="tbPassword" id="tbPassword">
        <label for="tbConfirmPassword">Potvrdit heslo:</label><input type="password" name="tbConfirmPassword" id="tbConfirmPassword">
        <label for="tbEmail">E-mail:</label><input type="text" name="tbEmail" id="tbEmail">
        <hr />
        <label for="tbFirstName">Jméno:</label><input type="text" name="tbFirstName" id="tbFirstName">
        <label for="tbLastName">Příjmení:</label><input type="text" name="tbLastName" id="tbLastName">
        <label for="tbStreet">Ulice:</label><input type="text" name="tbStreet" id="tbStreet">
        <label for="tbCity">Město:</label><input type="text" name="tbCity" id="tbCity">
        <label for="tbPSC">PSČ:</label><input type="text" name="tbPSC" id="tbPSC">
        <label for="tbCountry">Země:</label><input type="text" name="tbCountry" id="tbCountry">
        <label for="tbPhone">Telefon:</label><input type="text" name="tbPhone" id="tbPhone">
        <input type="submit" value="Uložit" />
    </form>
</span>


<?php include $root."/Website/Base/pageFooter.php"; ?>


