<?php
if(intval($_GET['siteID'])){
	unset($_SESSION['siteID']);
	$_SESSION['siteID_ADMIN']=intval($_GET['siteID']);
}
$que="SELECT siteID, TITLE, owners, freeInWeekend, jumpActive, dealPermission FROM `sites` WHERE siteID='".($_SESSION['siteID']? $_SESSION['siteID'] : $_SESSION['siteID_ADMIN'])."' ";
$site= udb::single_row($que);


//$que="SELECT * FROM msgRead INNER JOIN msgSystem ON( msgSystem.id=msgRead.msgID ) WHERE `show`=0 AND `siteID`=".$site['siteID']."";

//$que="SELECT * FROM msgSystem LEFT JOIN msgRead ON (msgSystem.id = msgRead.msgID AND `show`=0 AND msgRead.siteID=".$site['siteID'].")
 //	WHERE (msgRead.siteID=".$site['siteID']." OR type=0)  AND ('".date("Y-m-d")."' BETWEEN `msgSystem`.fromDate AND `msgSystem`.untilDate) ORDER BY id";

$que="SELECT * FROM msgSystem 
	  LEFT JOIN msgRead ON (msgSystem.id = msgRead.msgID AND msgRead.siteID=".$site['siteID'].")
	  WHERE ((msgRead.siteID=".$site['siteID']." AND `show`=0) OR  (type=0 AND `show` IS NULL)) AND ('".date("Y-m-d")."' BETWEEN `msgSystem`.fromDate AND `msgSystem`.untilDate) 
	  ORDER BY id ";
$sys = udb::full_list($que);

$newSysMsg = count($sys);

$lastDate = date("Y-m-d H:i:s", strtotime("-14 days"));


$que="SELECT * FROM sitesContacts WHERE `show`=0 AND `contactSiteID`=".$site['siteID']." AND contactDate >='".$lastDate."'";
$notifications = udb::full_list($que);
$newNotifications = count($notifications);

$que="SELECT * FROM Comments WHERE `show`=0 AND `siteID`=".$site['siteID']." AND add_date >='2016-07-25' ";
$reviews = udb::full_list($que);
$newReviews = count($reviews);

if($_SESSION['siteID']){
udb::query("UPDATE `sites_users` SET `lastCheck` = NOW() WHERE `siteID` = ".$_SESSION['siteID']);
}

function getFixedButtons(){
	global $site; ?>
	<form id="smsForm" method="POST" enctype="multipart/form-data" >
		<div>חוות דעת אמיתיות, יוצרות פניות איכותיות</div>
		<input type="hidden" value="<?=$site['siteID']?>" name="siteID">
		<input type="text" value="" name="clientName" id="clientName" placeholder="שם האורח">
		<input type="text" value="" name="clientPhone" id="clientPhone" placeholder="טלפון האורח">
		<input type="button" id="sendSmsForm" value="שילחו בקשה לחוות דעת">
	</form>
	<div class="fixedBttns">
		<div class="bttn active" onclick="openCalendar(<?=$site['siteID']?>)"><span>היומן שלי</span></div>
		<div class="bttn" onclick="showSmsForm()" ><span style="width:85px">אספו חוות דעת</span></div>
	</div>
<?php } ?>
<!doctype html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <script src="<?=$root;?>/app/jquery.js"></script>
    <script src="<?=$root;?>/app/jquery-ui.min.js"></script>
	<script src="<?=$root;?>/app/jquery.ui.datepicker-he.js"></script>
	<link rel="stylesheet" href="<?=$root;?>/app/app.css?v=<?=time()?>">
	<link rel="stylesheet" href="<?=$root;?>/app/jquery-ui.css?v<?=time()?>">
	<script src="<?=$root;?>/app/app.js?v=<?=time()?>"></script>
	<link rel="icon" href="<?=WEBSITE?>favicon.ico?v=2" type="image/x-icon">
	<link rel="shortcut icon" href="<?=WEBSITE?>favicon.ico?v=2" type="image/x-icon">
	<link rel="icon" sizes="192x192" href="<?=WEBSITE?>webimages/logo192.png?v=1">
	<link rel="icon" sizes="128x128" href="<?=WEBSITE?>webimages/logo128.png?v=1">
	<link rel="apple-touch-icon" sizes="128x128" href="<?=WEBSITE?>webimages/logo128.png?v=1">
	<link rel="apple-touch-icon-precomposed" sizes="128x128" href="<?=WEBSITE?>webimages/logo128.png?v=1">
    <title>צימרטופ - מערכת מארחים</title>
</head>
<body class="user">
<header class="user_header">
	<a class="logo" href="/cms/user/"></a>
	<a class="logout" href="/cms/logout.php" ><span>התנתק</span></a>
	<a class="user" href="/cms/user/messages.php" ><span>הודעות</span><?php if($newSysMsg ){ ?><span class="noti"><?=($newSysMsg)?></span><?php } ?></a>
	<a class="notifications" href="/cms/user/notifications.php"><span>פניות</span><?php if($newNotifications){ ?><span class="noti"><?=($newNotifications?$newNotifications:0)?></span><?php } ?></a>
	<a class="edit" href="/cms/user/reviews.php"><span>חוות דעת</span><?php if($newReviews ){ ?><span class="noti"><?=($newReviews)?></span><?php } ?></a>
</header>
<div id="theTabs"></div>
<section id="mainContainerSys">
    <div id="formError" style="display: none;"><i class="fa"></i><span class="title"></span><span class="text"></span></div>
	<div class="barTitle">
		<a target="_blank" href="<?=showAlias("sites", $site['siteID'])?>" class="siteTitle"><?=outDb($site['TITLE'])?> <span> - צפו בדף מתחם</span></a>
		<div class="owner">שלום <?=outDb($site['owners'])?></div>
	</div>
	<div class="loaderUser"></div>                                                                                                                  