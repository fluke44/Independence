<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require $root.'/Website/Base/master.page';
include $root . "/Components/cSecurity/cLogin.php";

if(isset($_GET["errMsg"])) {
    echo $_GET["errMsg"];
}

    $login = new cLogin();
    if(isset($_SESSION["userid"]) && $_SESSION["userid"] != 0) {
        $loggedIn = $login->AuthenticateUser();
        if($loggedIn === false) {
            $login->DestroySession();
            echo $login->GetErrMsg();
            //Header("Location: login.php");
        } else {
            Header("Location: home.php");
        }
    } else {
        if(!empty($_POST["tbLogin"]) && !empty($_POST["tbPassword"])) {
            $loggedIn = $login->LogInUser($_POST["tbLogin"], $_POST["tbPassword"]);
            if($loggedIn) {
                if($login->GetErrMsg() == MSG_LOGIN_EXPIRED) {
                    Header("Location: home.php?err=loginExpired");
                } else {
                    Header("Location: home.php");
                }
            }
        }
    }
    //authorise user
?>

<form action="login.php" method="POST">
	<table>
		<tr>
			<td>Login:</td>
			<td><input type="text" name="tbLogin" /></td>
		</tr>
		<tr>
			<td>Password:</td>
			<td><input type="password" name="tbPassword" /></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><input type="submit" value="Login" /></td>
		</tr>
	</table>
</form>

<a href="forgottenPassword.php">Zapoměli jste heslo?</a>
