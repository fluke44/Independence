<?php

$root = $_SERVER['DOCUMENT_ROOT'];
echo $root."/Website/Base/master.page";
require $root."/Website/Base/master.page";
include $root."/Website/Security/authorise_header.php";
include $root."/Website/Base/pageHeader.php";
//include $root."Components/cSecurity/cLogin.php";

if(isset($_GET["action"])) {
    if($_GET["action"] == "logout") {
        $login->DestroySession();
        header('Location: login.php');
    }
}
if(isset($_GET["err"])) {
    if($_GET["err"] == "loginExpired") {
        header('Location: /Security/updatePassword.php');
    }
}
?>

<h1>
    hello!
</h1>

<p><a href="home.php?action=logout">Logout</a>
<p><a href="Admin/userAccessCreate.php">User access</a>
<p><a href="Admin/userCreate.php">Vytvořit nového uživatele</a>

<?php include $root."/Website/Base/pageFooter.php"; ?>
