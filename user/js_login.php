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
        $que = "SELECT `buserID`, `name`, `password`, `access` , `showstats`,`userType` FROM `biz_users` WHERE `username` = '" . udb::escape_string($username) . "' AND `active` = 1";
        $user = udb::single_row($que);

        if($user && password_verify($password, $user['password'])){
            $que   = "SELECT DISTINCT s.siteID FROM `sites` AS `s` INNER JOIN `sites_users` AS `u` USING(`siteID`) WHERE  s.active = 1 AND u.buserID = " . $user['buserID'];
            $sites = udb::single_column($que);

            if (count($sites)){
                $sess = new TfusaUser($user['buserID'], $user['name'], $user['access'], $sites, $user['userType'],$user['showstats']);
                $result['success'] = true;
                $result['link']    = '/user/' . $sess->access_token . '/';

                $_SESSION['tfusa']['user'][$sess->access_token] = $sess;
            }
            else
                $result['error'] = 'No sites attached to user';
        }
        else
            $result['error'] = 'Wrong e-mail or password';
    }
}
