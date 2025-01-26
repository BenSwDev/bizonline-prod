<?php
include_once "../cms/classes/class.TfusaBaseUser.php";
class_alias('TfusaBaseUser', 'TfusaUser', false);

include_once '../functions.php';

session_id() or session_start();

$result = new JsonResult(['success' => false]);

if(isset($_POST['login'])){
    $username = typemap($_POST['user'], 'string');
    $password = typemap($_POST['pass'], 'string');

    if (!$username)
        $result['error'] = 'Empty or illegal e-mail';
    elseif (!$password)
        $result['error'] = 'Empty password';
    else {
        $que = "SELECT `therapistID`, `siteID`, `password`, `siteName` FROM `therapists` WHERE `deleted` = 0 AND `workerType` <> 'fictive' AND `userName` = '" . udb::escape_string($username) . "' AND `active` = 1 AND (`workStart` IS NULL OR `workStart` <= CURDATE()) AND (`workEnd` IS NULL OR `workEnd` >= CURDATE())";
        $user = udb::single_row($que);

        if($user && MemberUser::passVerify($password, $user['password'])){
            $sess = new MemberUser($user['therapistID'], $user['siteName'], 127, [$user['siteID']], 0, 1);

            $result['success'] = true;
            $result['link']    = '/member/' . $sess->access_token . '/';

            $_SESSION['tfusa']['member'] = [$sess->access_token => $sess];
        }
        else
            $result['error'] = 'Wrong e-mail or password';
    }
}
