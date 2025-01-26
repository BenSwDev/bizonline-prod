<?php
include_once __DIR__ . "/../cms/classes/class.TfusaBaseUser.php";
include_once __DIR__ . "/../cms/classes/class.UserUtils2.php";
class_alias('TfusaBaseUser', 'TfusaUser', false);
class_alias('UserUtils2', 'UserUtils', false);

include_once __DIR__ . "/../functions.php";
include_once __DIR__ . "/components.php";

session_id() or session_start();

unset($_SESSION['tfusa_active']);

$siteID  = isset($_POST['siteID']) ? intval($_POST['siteID']) : intval($_GET['siteID']);
$buserID = isset($_POST['buserID']) ? intval($_POST['buserID']) : intval($_GET['buserID']);
$muserID = isset($_POST['muserID']) ? intval($_POST['muserID']) : intval($_GET['muserID']);

list($path_base, $path_token) = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

if ($path_token){
    if (!$_SESSION['tfusa'] || !$_SESSION['tfusa'][$path_base][$path_token]){
        header('Location: login.php');
        exit;
    }

    $tUser = $_SESSION['tfusa'][$path_base][$path_token];

    if (!is_a($tUser, 'TfusaUser')){
        unset($_SESSION['tfusa'][$path_base][$path_token]);

        header('Location: login.php');
        exit;
    }

    if ($tUser->access(TfusaUser::ACCESS_BIT_ADMIN)){
        $que   = "SELECT DISTINCT s.siteID FROM `sites` AS `s` INNER JOIN `sites_users` AS `u` USING(`siteID`) WHERE  s.active > 0 AND u.buserID = " . $tUser->id();
        $sites = udb::single_column($que);

        if (array_diff($sites, $tUser->sites()))
            $tUser->add_sites($sites);
    }
}
elseif ($path_base == 'user') {
    if ($siteID && $_SESSION['permission'] == 100){
        $tUser = new TfusaUser($_SESSION['user_id'], TfusaUser::SUPER_NAME, TfusaUser::ACCESS_SUPER, $siteID, 0, 1);
    }
    elseif ($buserID && $_SESSION['permission'] == 100){
        $user = udb::single_row("SELECT `buserID`, `name`, `access`,`showstats`, `userType` FROM `biz_users` WHERE `buserID` = " . $buserID);
        $sites = udb::single_column("SELECT DISTINCT s.siteID FROM `sites` AS `s` INNER JOIN `sites_users` AS `u` USING(`siteID`) WHERE s.active > 0 AND u.buserID = " . $buserID);

        $tUser = new TfusaUser($buserID, $user['name'], $user['access'], $sites, $user['userType'], $user['showstats']);
    }
}
elseif ($path_base == 'member' && $muserID && $_SESSION['permission'] == 100){
    $master = udb::single_row("SELECT `siteID`, `siteName` FROM `therapists` WHERE `therapistID` = " . $muserID);

    $tUser = new MemberUser($muserID, $master['siteName'], 127, [$master['siteID']], 0, 0);
}

if (!isset($tUser) || !$tUser->id() || $tUser->version < $tUser::CURRENT_VERSION) {
    header('Location: login.php');
    exit;
}

if (!$tUser->sites()){
    $tUser->logout();
    header('Location: login.php');
    exit;
}

try {
    if (!$tUser->active_site())
        throw new Exception('Empty site list');

    if ($siteID = intval($_POST['asite']))      // cannot be zero/empty
        $tUser->select_site($siteID);
    elseif (isset($_GET['asite']))              // can be zero/empty
        $tUser->select_site(intval($_GET['asite']));
    elseif ($tUser->single_site)
        $tUser->select_site($tUser->active_site());
}
catch (Exception $e){
    die('ERROR: ' . $e->getMessage());
}

if ($tUser->mult_login)
    $_SESSION['tfusa'][$path_base][$tUser->access_token] = $tUser;
else
    $_SESSION['tfusa'][$path_base] = [$tUser->access_token => $tUser];

if (!$path_token){
    header('Location: /' . $path_base . '/' . $tUser->access_token . '/');
    exit;
}

define('SITE_ID', $tUser->active_site());        // site ID of logged user

$_CURRENT_USER = $_SESSION['tfusa_active'] = $tUser;        // for later use
$_CURRENT_BASE = '/' . $path_base . '/';

unset($sites, $buserID, $muserID, $siteID, $tUser, $master, $path_token, $path_base);
