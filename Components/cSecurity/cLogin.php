<?php

require_once "/../staticData/messages.php";
require_once "/../staticData/page_constants.php";
require_once "/../cCore/cGLOBALS.php";
require_once "/../cCore/cPageOperations.php";
require_once "/../cCore/cCommon.php";
require_once '/../cCore/cDAL.php';
require_once '/../cCore/cParams.php';
include_once "/../cCore/cError.php";

define("MAX_LOGIN_RETRIES", 5);

class cLogin {
    private $environment = "TEST";
    private $userID = 0;
    private $login;
    private $errNum = 0;
    private $errMsg;
    private $now;

    private $gDAL;
    private $gSearch;
    private $MYGLOBALS;

    public function __construct() {
        $this->Initialise();
    }

    private function Initialise() {
        $this->now = time();    //new DateTime(date('Y-m-d H:m:s'));
        $this->gDAL = new cDAL();
        $this->gSearch = new cSearchParams();
        $this->MYGLOBALS = new cGLOBALS();
    }

    private function Now() {
        return $this->now;
    }

    public function GetUserID() {
        return $this->userID;
    }

    public function GetLogin() {
        return $this->login;
    }

    public function GetErrMsg() {
        return $this->errMsg;
    }

    public function GetErrNum() {
        return $this->errNum;
    }

    public function AuthenticateUser() {
        $this->errNum = 0;
        $authenticated = false;
        $recordset = new cRecordset();
        $dal = new cDAL();
        $searchParams = new cSearchParams();
        $MYGLOBALS = new cGLOBALS();
        $common = new cCommon();

        try {

            $sessionDuration = $common->GetSystemVariable("sessionDuration");

            //$state = 0;    //0 - goto login page, 1 - already logged in
            //exists some session already?
            if (!empty($_SESSION['userid'])) {
                $userid = $_SESSION['userid'];
                $searchParams->Add("userid", "=", $userid);
                $recordset = $dal->OpenRecordset("indspAuthoriseUser", null, $searchParams);
                if ($recordset->Count() == 1) {
                    $rIP = $recordset->Get("ipaddress");
                    $rSID = $recordset->Get("session");
                    $rActive = $recordset->Get("active");
                    $rLocked = $recordset->Get("locked");
                    $rSessionExpires = new DateTime($recordset->Get("sessionexpires"));
                    $rLoginExpires = new DateTime($recordset->Get("loginexpires"));

                    if ($rActive != 1) {
                        $this->errNum = 1;
                        $this->errMsg = MSG_ACCOUNT_NOT_ACTIVE;
                    }
                    if ($rLocked != 0) {
                        $this->errNum = 1;
                        $this->errMsg = MSG_ACCOUNT_LOCKED;
                    }
                    if ($rLoginExpires->getTimestamp() <= $this->Now()) {
                        $this->errNum = 1;
                        $this->errMsg = MSG_LOGIN_EXPIRED;
                    }
                    if ($rSessionExpires->getTimestamp() <= $this->Now()) {
                        $this->errNum = 1;
                        $this->errMsg = MSG_SESSION_EXPIRED;
                    }
                    if ($this->GetErrNum() == 0) {
                        if ($rSID != session_id()) {
                            $this->errNum = 1;
                            $this->errMsg = MSG_SESSION_HIJACK;
                        } else {
                            $authenticated = true;
                            $this->errNum = 0;
                            $this->errMsg = MSG_ALREADY_LOGGED_IN;

                            $newSessionExpireTime = new DateTime(date('Y-m-d H:i:s'));
                            $newSessionExpireTime->add(new DateInterval('PT' . $sessionDuration . 'S'));

                            $recordset->Clear();
                            $recordset->Add("userid", $userid);
                            $recordset->Add("sessionExpires", $newSessionExpireTime->format("Y-m-d H:i:s"));
                            $dal->UpdateRecordset("indUsers", $recordset);
                        }
                    }
                } else {
                    $this->errNum = 1;
                    $this->errMsg = MSG_WRONG_USER_OTHER_THAN_ONE_RETURNED;
                }
            } else {
                $this->errNum = 1;
                $this->errMsg = MSG_NO_SESSION_ID;
            }
            return $authenticated;
        } catch (Exception $e) {
            cError::Report(COMPONENT_NAME, "cLogin", __FUNCTION__, $e->getMessage(), null, $e->getTraceAsString(), $e->getCode(),
                            $e->getMessage(), null, null);
            return array("errCode" => 1, "errMsg" => (string) $e->getMessage());
        }
    }

    private function GetPathToPageFromUrl() {

        $exploded = explode("/Website", $_SERVER["PHP_SELF"]);
        foreach ($exploded as $item) {
            if ($item != "Website") {
                $url = (string) $item;
            }
        }
        return $url;
    }

    public function AuthoriseUser() {
        $authorised = false;
        $this->errNum = 0;  //reset if another function made an error
        $userID = 0;

        $url = $this->GetPathToPageFromUrl();

        if($this->userID == 0) {
            if(isset($_SESSION["userid"]) && $_SESSION["userid"] != 0) {
                $userID = $_SESSION["userid"];
            } else {
                $this->errNum = 1;
                $this->errMsg = MSG_NO_USERID;
            }
        } else {
            $userID = $this->userID;
        }

        $this->gSearch->Add("userid", "=", $userID);
        $this->gSearch->Add("url", "=", $url);

        $result = $this->gDAL->OpenRecordset("indvwUserAccessToPage", null, $this->gSearch);
        if($result->Count() > 0) {
            $authorised = true;
        } else {
            $this->errNum = 1;
            $this->errMsg = MSG_ACCESS_TO_PAGE_DENIED." ".$url;
        }
        return $authorised;

    }

    public function LogInUser($asLogin, $asPassword) {
        try {
            $loggedSuccessfuly = false;
            $userID = 0;
            $login = "";

            $dal = new cDAL();
            $searchParams = new cSearchParams();
            $common = new cCommon();
            $MYGLOBALS = new cGLOBALS();

            $sessionDuration = $common->GetSystemVariable("sessionDuration");

            //for updating records in db
            $update = new cRecordset();
            $update->AddRow();

            $searchParams->Add("login", "=", $asLogin);
            $recordset = $dal->OpenRecordset("indspGetLoginDetails", null, $searchParams, null);

            if ($recordset->Count() != 1) { //if user was not found
                $this->errNum = 1;
                $this->errMsg = MSG_WRONG_USER_OTHER_THAN_ONE_RETURNED;
                return $loggedSuccessfully;
            } else {
                $userID = $recordset->Get("userid");
                $login = $recordset->Get("webname");
                $update->Add("userid", $userID);

                $date = new DateTime(date('Y-m-d H:i:s'));
                $date->add(new DateInterval('PT' . $sessionDuration . 'S'));
                $loginExpires = new DateTime($recordset->Get("loginexpires"));

                if ($recordset->Get("password") != md5($asPassword)) { //if password doesn't match
                    if ($recordset->Get("loginretriesremaining") > 0) { //if there are login tries remaining
                        $update->Add("loginretriesremaining", $recordset->Get("loginretriesremaining") - 1); //discount one try
                        $this->errMsg = MSG_WRONG_PASSWORD;
                    } else {
                        $update->Add("locked", 1); //set account locked
                        $this->errMsg = MSG_WRONG_PASSWORD_ACCOUNT_LOCKED;
                    }
                } else {
                    if ($loginExpires->getTimestamp() <= $this->Now()) {
                        $this->errMsg = MSG_LOGIN_EXPIRED;
                        $update->Add("ipaddress", $MYGLOBALS->clientInfo['ip']);
                        $loggedSuccessfuly = true;
                    } else {
                        $update->Add("ipaddress", $MYGLOBALS->clientInfo['ip']);
                        $this->errMsg = MSG_LOGIN_SUCCESSFUL;
                        $loggedSuccessfuly = true;
                    }
                }

                if ($loggedSuccessfuly == true) {
                    $this->userID = $userID;
                    $this->login = $login;

                    session_start();
                    $update->Add("lastlogin", date('Y-m-d H:i:s'));
                    $update->Add("loginretriesremaining", MAX_LOGIN_RETRIES);
                    $update->Add("locked", 0);
                    $update->Add("session", session_id());
                    $update->Add("sessionExpires", $date->format('Y-m-d H:i:s'));

                    $_SESSION["userid"] = $userID;  //store user identification into session
                }

                if ($update->Count() > 0) {
                    if (!$dal->UpdateRecordset("indUsers", $update)) {
                        throw new Exception("LoginUser(): update recordset error: " . mysql_error());
                    }
                }
            }
            return $loggedSuccessfuly;
        } catch (Exception $e) {
            cError::Report(COMPONENT_NAME, "cLogin", __FUNCTION__, $e->getMessage(), null, $e->getTraceAsString(), $e->getCode(),
                            $e->getMessage(), null, null);
            return array("errCode" => 1, "errMsg" => (string) $e->getMessage());
        }
    }

    public function DestroySession() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public function LostPasswordProcess($email) {
        $search = new cSearchParams();
        $dal = new cDAL();

        try {
            $search->Add("email", "=", $email);
            $result = $dal->OpenRecordset("indvwUserContact", null, $search);
            if ($result->Count() == 1) {
                $email = $result->Get("email");
                if(empty($email)) {
                    $email2 = $result->Get("email2");
                    if(empty($email2)) {
                        throw new Exception("No email address for user ".$userid);
                    } else {
                        $recipientMail = $result->Get("email2");
                    }
                } else {
                    $recipientMail = $result->Get("email");
                }
                $newPassword = self::GenerateRandomString();
                $hashedPassword = md5($newPassword);
                $email = self::CreateLostPasswordMail($newPassword);
                
                $emailSent = self::SendMail($recipientMail, $email[0], $email[1]);

                if($emailSent) {
                    $updated = self::UpdateNewPassword($userid, $hashedPassword);
                    if ($dal === false) {
                        throw new Exception("LostPasswordProcess failed for user ".$userid);
                    } else {
                        return array("errCode" => 0);
                    }
                } else {
                    throw new Exception("Email wasn't sent");
                }

            } else {
                throw new Exception("No user with email ".$email." exists");
            }
            return array("errCode" => 0);
        } catch (Exception $e) {
            cError::Report(COMPONENT_NAME, "cLogin", __FUNCTION__, $e->getMessage(), null, $e->getTraceAsString(), $e->getCode(),
                    $e->getMessage(), $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
            return array("errCode" => 1, "errMsg" => (string)$e->getMessage());
        }

    }

    private static function UpdateNewPassword($userid, $newPassword) {
        $dal = new cDAL();
        $update = new cRecordset();
        try {
            if (!empty($userid) && !empty($newPassword)) {
                $update->AddRow();
                $update->Add("userid", $userid);
                $update->Add("password", $newPassword);

                $result = $dal->UpdateRecordset("indUsers", $update);
                if ($dal->Error()) {
                    throw new Exception($dal->GetErrMsg());
                }
                return true;
            }
            return false;
        } catch (Exception $e) {
            cError::Report(COMPONENT_NAME, "cLogin", __FUNCTION__, "UpdateNewPassword failed", $query, $e->getTraceAsString(), $e->getCode(),
                    $e->getMessage(), $this->MYGLOBALS->DB()->errno, $this->MYGLOBALS->DB()->error);
            return false;
        }
    }

    private static function SendMail($emailAddress, $headers, $email_str) {
        return mail($emailAddress, "Změna hesla na serveru ???", $email_str, $headers);
    }

    private static function CreateLostPasswordMail($newPassword) {
        $search = new cParams();
        $dal = new cDAL();

        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $headers .= 'From: pp23@seznam.cz' . "\r\n" . 'Reply-To: pp23@seznam.cz' . "\r\n" . 'X-Mailer: PHP/' . phpversion();

        $email = "<html>
                    <head>
			<title>Změna hesla na serveru ???</title>
                    </head>
                    <body>
			<h3>Zažádal jste o změnu hesla</h3>
			<p>Vaše nové přihlašovací heslo je: <b>" . $newPassword . "</b></p>
			<p>Pro příští přihlášení prosím použijte toto heslo, změnit si jej můžete v administraci svého účtu.<br /></p>
			<p>S pozdravem <a href='mailto://pp23@seznam.cz'>webmaster</a> serveru SONSkyjov.cz
                    </body>
                  </html>";

        $result = array($headers,$email);
        return $result;
    }

    private static function GenerateRandomString($length = 10) {
        $characters = "0123456789abcdefghijklmnopqrstuvwxyz";
        $string = "";
        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, strlen($characters))];
        }
        return $string;
    }

    public static function HashPassword($password, $salt) {
        $shaHash = self::GenerateHash($password);
        $bfHash = self::GenerateBlowfishHash($shaHash, $salt);
        return bfHash;
    }

    private static function GenerateBlowfishHash($string, $saltSource) {
        if(CRYPT_BLOWFISH == 1) {
            $bfSalt = '$2a$52$'.substr(md5($saltSource), 0, CRYPT_SALT_LENGTH).'.';
            return crypt($string, $bfSalt);
        } else {
            return 0;
        }
    }

    private static function GenerateHash($string, $algo = 'sha1') {
        return hash($algo, $string);
    }

    public function ShowLoginScreen() {
        cPageOperations::Redirect(cPageOperations::GetPageFromConstant("PC_LOGIN_SCREEN"));
    }

}