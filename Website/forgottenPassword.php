<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require $root.'/Website/Base/master.page';
include $root . "/Components/cSecurity/cLogin.php";
include_once $root . "/Components/cCore/cCommon.php";

if (isset($_POST["newRequest"])) {
    if ($_POST["newRequest"] == "1") {
        if (isset($_POST["tbEmail"])) {
            $login = new cLogin;
            $email = trim($_POST["tbEmail"]);

            $processEmail = $login->LostPasswordProcess($email);
            if ($processEmail["errCode"] == 1) {
                echo cCommon::ShowErrMsg($processEmail["errMsg"]);
            } else {
                echo cCommon::ShowSuccMsg("Email byl úspešně odeslán na adresu " . $email);
                header("Location: login.php");
            }
        }
    }
}

?>

<h1>Zapomenuté heslo</h1>
<p>
    Pokud jste zapoměli heslo, do následujícího pole vložte svůj e-mail na který Vám bude zasláno heslo nové.
    To si pak můžete kdykoliv změnit.
</p>

<form action="forgottenPassword.php" method="POST">
	<table>
		<tr>
			<td>E-mail:</td>
			<td><input type="text" name="tbEmail" /></td>
                </tr>
		<tr>
			<td>&nbsp;</td>
			<td><input type="submit" value="Odeslat" /></td>
		</tr>
	</table>
    <input type="hidden" name="newRequest" value = "1" />
</form>

<?php include_once "/Base/pageFooter.php"; ?>