<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require $root.'/Website/Base/master.page';
require $root . "/Website/security/authorise_header.php";

include_once $root . "/Components/cCore/cDAL.php";
include_once $root . "/Components/cCore/cRecordset.php";
include_once $root . "/Components/cCore/cParams.php";
include_once $root . "/Components/cCore/cCommon.php";

$gPageCaption = "User Access";

$dal = new cDAL();
$search = new cSearchParams();
$sort = new cSortParams;

if (isset($_GET["action"])) {
    $action = $_GET["action"];
} else {
    $action = "";
}

if ($action == "AssignUserToGroup") {
    if (!empty($_POST["selUser"]) && !empty($_POST["selUserGroup"])) {
        $update = new cRecordset();
        $update->AddRow();
        $update->Add("userID", $_POST["selUser"]);
        $update->Add("userGroupID", $_POST["selUserGroup"]);

        $result = $dal->UpdateRecordset("indUsersToGroups", $update);
        if ($result === false) {
            echo cCommon::ShowErrMsg($dal->GetErrMsg());
        } else if($dal->Error()) {
            echo cCommon::ShowErrMsg("DB update failed");
        }
    }
} else if ($action == "AssignPageToGroup") {
    if (!empty($_POST["selURL"]) && !empty($_POST["selUserGroup"])) {
        $update = new cRecordset();
        $update->AddRow();
        $update->Add("pageID", $_POST["selUrl"]);
        $update->Add("userGroupID", $_POST["selUserGroup"]);

        $result = $dal->UpdateRecordset("indGroupsToPages", $update);
        if ($result === false) {
            echo cCommon::ShowErrMsg($dal, "Update failed");
        } else if($dal->Error()) {
            echo cCommon::ShowErrMsg("DB update failed");
        }
    }
} else if ($action == "") {

}

$sort->Add("userid");

$rUsers = $dal->OpenRecordset("indvwUserContact", null, null, $sort);
if ($rUsers->Count() > 0) {
    $rUsers->MoveFirst();
    while (!$rUsers->AtEnd()) {
        $itemName = $rUsers->Get("webname") . " (" . $rUsers->Get("firstname") . " " . $rUsers->Get("surname") . ")";
        $users[$itemName] = $rUsers->Get("userid");
        $rUsers->MoveNext();
    }
}

$sort->Clear();
$sort->Add("GroupName");

$rUserGroups = $dal->OpenRecordset("indUserGroups", null, null, $sort);
if ($rUserGroups->Count() > 0) {
    $rUserGroups->MoveFirst();
    while (!$rUserGroups->AtEnd()) {
        $itemName = $rUserGroups->Get("GroupName") . " (" . $rUserGroups->Get("userGroupID") . ")";
        $userGroups[$itemName] = $rUserGroups->Get("UserGroupID");
        $rUserGroups->MoveNext();
    }
}

$sort->Clear();
$sort->Add("Url");

$rPages = $dal->OpenRecordset("indPages", null, null, $sort);
if ($rPages->Count() > 0) {
    $rPages->MoveFirst();
    while (!$rPages->AtEnd()) {
        $itemName = $rPages->Get("Url") . " (" . $rPages->Get("Name") . ")";
        $pages[$itemName] = $rPages->Get("PageID");
        $rPages->MoveNext();
    }
}
?>

<?php include $root."/Website/Base/pageHeader.php"; ?>

<h1><?php echo $gPageCaption; ?></h1>

<span>
<?php 
if(isset($userMsg)) {
    echo $userMsg;
}
?>
</span>

<span>
    <form action="?action=AssignUserToGroup" method="POST">
        <label for="selectUser">Uživatel:</label><select name="selUser" id="selectUser">
            <?php foreach ($users as $user => $value) { ?>
                <option value="<?php echo $value; ?>"><?php echo $user; ?></option>
            <?php } ?>
        </select>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <label for="selectUserGroup">Skupina</label><select name="selUserGroup" id="selectUserGroup">
            <?php foreach ($userGroups as $group => $value) { ?>
            <option value="<?php echo $value; ?>"><?php echo $group; ?></option>
            <?php } ?>
        </select>
        <input type="submit" value="Uložit" />
    </form>
</span>

<span>
    <form action="?action=AssignPageToGroup" method="POST">
        <label for="selectUrl">URL:</label><select name="selURL" id="selectUrl">
            <?php foreach ($pages as $page => $value) { ?>
            <option value="<?php echo $value; ?>"><?php echo $page; ?></option>
            <?php } ?>
        </select>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <label for="selectUserGroup">Skupina</label><select name="selUserGroup" id="selectUserGroup">
            <?php foreach ($userGroups as $group => $value) { ?>
            <option value="<?php echo $value; ?>"><?php echo $group; ?></option>
            <?php } ?>
        </select>
        <input type="submit" value="Uložit" />
    </form>
</span>

<?php include $root."/Website/Base/pageFooter.php"; ?>


