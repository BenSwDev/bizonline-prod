<?
include_once "bin/system.php";



$username=inDB($_POST['user']);
$password=inDB($_POST['password']);

$que="SELECT * FROM `users` WHERE username='".$username."' AND password='".$password."'";
$user= udb::single_row($que);
				
if($user){
	$_SESSION['user_id'] = $user['id'];
	$_SESSION['name'] = $user['name'];
	$_SESSION['permission'] = $user['permission'];
	$que = "SELECT `listID`, `file` FROM `users_access` WHERE `userID` = " . $user['id'];
	$_SESSION['access'] = udb::key_list($que, 'listID');
	

	echo "/cms/";
	exit;
}/* else {
	$que="SELECT siteID, TITLE, owners FROM `sites` WHERE username='".$username."' AND password='".$password."'";
	$site= udb::single_row($que);
	if($site){
		$que="SELECT * FROM sites_users WHERE siteID=".$site['siteID']." ";
		$checkLogin=udb::single_row($que);
		if($checkLogin){
			$cp=Array();
			$cp['siteID']=$site['siteID'];
			$cp['lastLogin']=date("Y-m-d, H:i:s");
			udb::update("sites_users", $cp, "siteID=".$site['siteID']."");
		} else {
			$cp=Array();
			$cp['siteID']=$site['siteID'];
			$cp['lastLogin']=date("Y-m-d, H:i:s");
			udb::insert("sites_users", $cp);
		}

		$_SESSION['siteID'] = $site['siteID'];
		$_SESSION['name'] = $site['owners'];
		$_SESSION['permission'] = 10;
		echo "/cms/user/";
		exit;
	}
}*/