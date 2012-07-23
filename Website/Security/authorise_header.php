<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root.'/Components/cSecurity/cLogin.php';

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

echo $_SERVER["HTTP_REFERER"];

global $login;
$login = new cLogin();
if(isset($_SESSION["userid"]) && $_SESSION["userid"] != 0) {
    if(!$login->AuthenticateUser()) {
        $login->DestroySession();
        $page = "login.php?errMsg=".urlencode($login->GetErrMsg());
        header("Location: http://$host$uri/$page");
        exit;
    }

    if(!$login->AuthoriseUser()) {
        $login->DestroySession();
        $page = "login.php?errMsg=".urlencode($login->GetErrMsg());
        header("Location: http://$host$uri/$page");
        //header('Location: '.$root.'/Website/login.php?errMsg='.urlencode($login->GetErrMsg()));
        exit;
    }
} else {
    $login->DestroySession();
    $page = "login.php?errMsg=".urlencode('User not logged in');
    header("Location: http://$host$uri/$page");
    exit;
}

?>
