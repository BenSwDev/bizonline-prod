<?php
require_once "auth.php";
require_once "functions.php";

// Handle language selection via GET parameter
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    // Validate the selected language
    if (in_array($lang, ['he', 'en', 'es', 'ru', 'fr', 'el', 'tr'])) {
        $_SESSION['lang'] = $lang;
    }

    // Remove 'lang' parameter from current URL while preserving others
    $url = $_SERVER['REQUEST_URI'];
    $parsed_url = parse_url($url);
    $path = $parsed_url['path'] ?? '';
    $query = [];
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query);
        unset($query['lang']);
    }
    $new_query = http_build_query($query);
    $redirect = $path . ($new_query ? '?' . $new_query : '');
    header("Location: " . $redirect);
    exit();
}

// Set default language to 'he' if not set
$lang = $_SESSION['lang'] ?? 'he';
// Determine text direction based on language
$dir = ($lang === 'he') ? 'rtl' : 'ltr';

// Determine the page to display
if(isset($_GET['page']))
    $page = $_GET['page'];
else if($_CURRENT_USER->userType==1)
    $page = 'healthStatements';
else
    $page = 'home';

$siteData = udb::single_row("SELECT sites.cleanGlobal, sites.vvouchers, sites.guid, sites.fromName, sites.phone, sites.email, sites.checkInHour, sites.checkOutHour,sites.siteName, sites.healthActive,  sites_langs.defaultAgr, sites_langs.agreement1, sites_langs.agreement2, sites_langs.agreement3, sites_langs.owners, hostsPicture,limit_metaplim,sites.blockDelete, sites.showDatesUpdate, sites.showStats
            ,sites.masof_type , IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS hasTerminal
    FROM sites INNER JOIN sites_langs ON (sites_langs.siteID = sites.siteID AND sites_langs.langID = 1 AND sites_langs.domainID = 1)
	WHERE sites.siteID IN (" . $_CURRENT_USER->sites(true) . ")");

//	$que = "SELECT rooms_units.unitID,rooms_units.unitName,rooms.roomName,rooms.cleanTime, rooms.maxAdults, rooms.maxKids, rooms.maxGuests, rooms.active
//	FROM rooms_units
//	INNER JOIN rooms ON (rooms.roomID = rooms_units.roomID)
//	WHERE rooms.siteID = " . $siteID . "
//    ORDER BY rooms.showOrder, rooms.roomID";
//	$rooms = udb::key_row($que,'unitID');

$que = "SELECT mainPageTitle,mainPageID FROM MainPages WHERE mainPageType=100 AND ifShow=1";
$reasons = udb::full_list($que);

$siteName = $siteData['siteName'];

//print_r($domains);

$sourcesArray = [];
UserUtilsNew::init($_CURRENT_USER->active_site());
$allSources = UserUtilsNew::fullSourcesList();
$jssourcesArray = "";
foreach($allSources as $k=>$source) {
    $color = $source['hexColor'];
    if(!$color)
        $color = '#' . substr(md5(mt_rand()), 0, 6);
    $sourcesArray[$k] = [ "letterSign"=>$source['letterSign'],"color"=>$color ];
    $jssourcesArray .= "sourcesArray['".$k."'] = { letterSign: '".$source['letterSign']."' , color: '". $color ."'};" . PHP_EOL;

}
?>
<!DOCTYPE html>
<html dir="<?= htmlspecialchars($dir) ?>" lang="<?= htmlspecialchars($lang) ?>">
<head>
    <title>Biz online</title>
    <meta charset="utf-8">
    <!-- issue-reporter.css -->
    <link rel="stylesheet" href="issue-reporter/issue-reporter.css">
    <link rel="stylesheet" href="issue-reporter-design.css">

    <link rel="stylesheet" href="<?=$_CURRENT_BASE?>assets/css/style_ctrl.php?dir=<?=$dir?>&v=<?=time()?>">
    <link rel="stylesheet" href="<?=$_CURRENT_BASE?>assets/css/stylefix_ltr.css?v=<?=time()?>">
    <link href="https://fonts.googleapis.com/css?family=Rubik:400,500,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?=$_CURRENT_BASE?>assets/addons/datetimepicker/jquery.datetimepicker.min.css">
    <link href="assets/css/c_status.css?v=<?=rand()?>" rel="stylesheet">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery.table2excel_Edited.js"></script>
    <!-- <script src="/cms/app/jquery-ui.min.js"></script> -->
    <link rel="stylesheet" href="/cms/app/jquery-ui.css">
    <script type="text/javascript" src="//www.spaplus.co.il/js/jquery.ui.custom.min.js"></script>
    <script type="text/javascript" src="//www.spaplus.co.il/datepicker/jquery.ui.datepicker-he.js"></script>
    <script>
        $.datepicker.setDefaults( $.datepicker.regional[ "he" ] );
    </script>


    <script src="assets/js/local_loader.js"></script>
    <script>
        function changeLanguage(lang) {
            // Reload the page with the selected language as a GET parameter
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
    </script>

</head>
<style>
    html, body {
        visibility: hidden;
    }
</style>
<body class="level-<?=$_CURRENT_USER->access()?> <?=$_SESSION['menuc']? "menu-closed" : ""?> <?=htmlspecialchars($dir)?> <?=($dir === 'rtl') ? 'rtl-layout' : 'ltr-layout'; ?>">
<style>

    .site-select {display: none;}
    .holder{background:rgba(255,255,255,.7);display:flex;position:fixed;left:0;top:0;bottom:0;right:0;width:100%;height:100%;z-index:99999999;}
    .preloader{width:100px;height:100px;position:absolute;left:50%;top:50%;transform:translateX(-50%) translateY(-50%);animation:rotatePreloader 2s infinite ease-in}
    @keyframes rotatePreloader{
        0%{transform:translateX(-50%) translateY(-50%) rotateZ(0)}
        100%{transform:translateX(-50%) translateY(-50%) rotateZ(-360deg)}
    }
    .preloader div{position:absolute;width:100%;height:100%;opacity:0}
    .preloader div:before{content:"";position:absolute;left:50%;top:0;width:10%;height:10%;background-color:#09a4d9;transform:translateX(-50%);border-radius:50%}
    .preloader div:nth-child(1){transform:rotateZ(0);animation:rotateCircle1 2s infinite linear;z-index:9}
    @keyframes rotateCircle1{
        0%{opacity:0}
        0%{opacity:1;transform:rotateZ(36deg)}
        7%{transform:rotateZ(0)}
        57%{transform:rotateZ(0)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
    .preloader div:nth-child(2){transform:rotateZ(36deg);animation:rotateCircle2 2s infinite linear;z-index:8}
    @keyframes rotateCircle2{
        5%{opacity:0}
        5.0001%{opacity:1;transform:rotateZ(0)}
        12%{transform:rotateZ(-36deg)}
        62%{transform:rotateZ(-36deg)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
    .preloader div:nth-child(3){transform:rotateZ(72deg);animation:rotateCircle3 2s infinite linear;z-index:7}
    @keyframes rotateCircle3{
        10%{opacity:0}
        10.0002%{opacity:1;transform:rotateZ(-36deg)}
        17%{transform:rotateZ(-72deg)}
        67%{transform:rotateZ(-72deg)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
    .preloader div:nth-child(4){transform:rotateZ(108deg);animation:rotateCircle4 2s infinite linear;z-index:6}
    @keyframes rotateCircle4{
        15%{opacity:0}
        15.0003%{opacity:1;transform:rotateZ(-72deg)}
        22%{transform:rotateZ(-108deg)}
        72%{transform:rotateZ(-108deg)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
    .preloader div:nth-child(5){transform:rotateZ(144deg);animation:rotateCircle5 2s infinite linear;z-index:5}
    @keyframes rotateCircle5{
        20%{opacity:0}
        20.0004%{opacity:1;transform:rotateZ(-108deg)}
        27%{transform:rotateZ(-144deg)}
        77%{transform:rotateZ(-144deg)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
    .preloader div:nth-child(6){transform:rotateZ(180deg);animation:rotateCircle6 2s infinite linear;z-index:4}
    @keyframes rotateCircle6{
        25%{opacity:0}
        25.0005%{opacity:1;transform:rotateZ(-144deg)}
        32%{transform:rotateZ(-180deg)}
        82%{transform:rotateZ(-180deg)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
    .preloader div:nth-child(7){transform:rotateZ(216deg);animation:rotateCircle7 2s infinite linear;z-index:3}
    @keyframes rotateCircle7{
        30%{opacity:0}
        30.0006%{opacity:1;transform:rotateZ(-180deg)}
        37%{transform:rotateZ(-216deg)}
        87%{transform:rotateZ(-216deg)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
    .preloader div:nth-child(8){transform:rotateZ(252deg);animation:rotateCircle8 2s infinite linear;z-index:2}
    @keyframes rotateCircle8{
        35%{opacity:0}
        35.0007%{opacity:1;transform:rotateZ(-216deg)}
        42%{transform:rotateZ(-252deg)}
        92%{transform:rotateZ(-252deg)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
    .preloader div:nth-child(9){transform:rotateZ(288deg);animation:rotateCircle9 2s infinite linear;z-index:1}
    @keyframes rotateCircle9{
        40%{opacity:0}
        40.0008%{opacity:1;transform:rotateZ(-252deg)}
        47%{transform:rotateZ(-288deg)}
        97%{transform:rotateZ(-288deg)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
    .preloader div:nth-child(10){transform:rotateZ(324deg);animation:rotateCircle10 2s infinite linear;z-index:0}
    @keyframes rotateCircle10{
        45%{opacity:0}
        45.0009%{opacity:1;transform:rotateZ(-288deg)}
        52%{transform:rotateZ(-324deg)}
        102%{transform:rotateZ(-324deg)}
        100%{transform:rotateZ(-324deg);opacity:1}
    }
</style>
<div class="holder">
    <div class="preloader">
        <div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
</div>
<div class="sendPop" style="display:none">
    <input type="hidden" id="sendPopMsg">
    <input type="hidden" id="sendPopSubject">
    <div class="container">
        <div class="close" onclick="$('.sendPop').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title mainTitle" id="SendPopTitle">שליחה לחתימה</div>

        <div class="content">
            <div class="lines">
                <div class="line">
                    <input type="text" id="sendPop_phone" placeholder="מספר טלפון">
                    <div class="signOpt">
                        <a href="" target="_blank" id="sendPop_icoWA"></a><span class="icon whatsapp" data-phone=""><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span>
                        <a href="" target="_blank" id="sendPop_icoSMS"></a><span class="icon sms a_sms" data-sms="">
								<img src="<?=$_CURRENT_BASE?>assets/img/icon_sms.png" alt="sms">
							</span>
                    </div>
                </div>
                <div class="line">
                    <input type="text" id="sendPop_email" placeholder="כתובת אימייל">
                    <div class="signOpt">
                        <a href="" target="_blank" id="sendPop_icoMail"></a><span  class="icon mail" data-mail=""><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" width="32" height="27"><style>.message-ic{fill:#fff}</style><path class="message-ic" d="m29.9 25L24.1 19.4 23.5 18.8 23 18.3 22.6 17.8 22.1 17.4 24.9 15.5 29.8 12 30 11.9 30 14 30 17.2 30 25 29.9 25ZM22.2 6L5 10.9 5.7 13.3 2.1 10.7 2.8 7.8C4.1 7 12.5 2.1 15.9 2 19.3 2.1 27.7 7 29 7.8L29.9 10.7 24.7 14.5 22.2 6ZM6.2 14.8L9.7 17.4 9 18.1 8.5 18.6 8.1 19.1 7.5 19.6 2.1 25 2 25 2 13.1 2 12 2 11.9 2.7 12.4 6.2 14.8ZM9.5 19L10 18.5 10.4 18.1 10.8 17.7 11.2 17.2 11.3 17.2C11.4 17.1 11.6 16.9 11.8 16.8 12.9 15.8 14.4 15.3 15.9 15.3 17.4 15.3 18.9 15.8 20 16.8 20.2 16.9 20.3 17 20.5 17.2L20.6 17.3 21.1 17.7 21.2 17.9 21.7 18.3 22.2 18.8 23 19.7 28.4 25 3.5 25 7.8 20.7 9.5 19Z"></path></svg></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .alerts {text-decoration:none;position: absolute;left: 0;width: 60px;height: 100%;display: flex;top: 0;align-items: center;justify-content: center;z-index:3}
    .alerts span {position: relative;}
    .alerts span > .alert-circle {text-decoration:none;position: absolute;left: 0;top: 0;display: flex;width: 26px;height: 26px;background: red;align-items: center;justify-content: center;color: white;border-radius: 50%;}
    .alerts.none {opacity: 0.3;}
    .alerts.none:hover {opacity: 1;}
    .alerts .alerts-bell{width:60px;height:60px;display:flex;align-items:center;justify-content:center}
</style>

<div id="calPlus" class="page-<?=$page?$page:""?>">
    <?php if($page != 'signature') { ?>
        <div class="menu-opener" onclick="$('body').toggleClass('menu-closed');var _menu=($('body').hasClass('menu-closed')? 1 : 0);localStorage.setItem('menuc', _menu);$.post('ajax_global.php',{act: 'menuc' , menuc:_menu })"></div>
        <div class="r-side">
            <header>
                <div class="menuButton"><span></span><span></span><span></span></div>
                <a class="logo" href="<?=$_CURRENT_BASE . $_CURRENT_USER->access_token . '/'?>">
                    <span>
                        <img src="<?=$_CURRENT_BASE?>assets/img/bizlogo2.png">

                    </span>
                </a>
                <?

                $alertsArr = udb::single_row("SELECT alerts_count, alerts_count_wr FROM biz_users WHERE buserID = ".$_CURRENT_USER->id());
                $alerts = $alertsArr['alerts_count'];
                $alerts_wr = $alertsArr['alerts_count_wr'];
                //$alerts_wr = 5;
                ?>

                <!-- Language Picker Start -->
                <div class="lang-wrapper">
                    <div class="lang-botton">
                        <div>Lang</div>
                        <svg version="1.1" id="svg2223" xml:space="preserve" width="682.66669" height="682.66669" viewBox="0 0 682.66669 682.66669" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg" style="fill: white;width: 30px;height: auto;stroke: white;"><defs id="defs2227"><clipPath clipPathUnits="userSpaceOnUse" id="clipPath2237"><path d="M 0,512 H 512 V 0 H 0 Z" id="path2235"></path></clipPath></defs><g id="g2229" transform="matrix(1.3333333,0,0,-1.3333333,0,682.66667)"><g id="g2231"><g id="g2233" clip-path="url(#clipPath2237)"><g id="g2239" transform="translate(497,256)"><path d="m 0,0 c 0,-132.548 -108.452,-241 -241,-241 -132.548,0 -241,108.452 -241,241 0,132.548 108.452,241 241,241 C -108.452,241 0,132.548 0,0 Z" style="fill:none;stroke:#fff;stroke-width:30;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2241"></path></g><g id="g2243" transform="translate(376,256)"><path d="m 0,0 c 0,-132.548 -53.726,-241 -120,-241 -66.274,0 -120,108.452 -120,241 0,132.548 53.726,241 120,241 C -53.726,241 0,132.548 0,0 Z" style="fill:none;stroke:#fff;stroke-width:30;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2245"></path></g><g id="g2247" transform="translate(256,497)"><path d="M 0,0 V -482" style="fill:none;stroke:#fff;stroke-width:30;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2249"></path></g><g id="g2251" transform="translate(15,256)"><path d="M 0,0 H 482" style="fill:none;stroke:#fff;stroke-width:30;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2253"></path></g><g id="g2255" transform="translate(463.8926,136)"><path d="M 0,0 H -415.785" style="fill:none;stroke:#fff;stroke-width:30;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2257"></path></g><g id="g2259" transform="translate(48.1079,377)"><path d="M 0,0 H 415.785" style="fill:none;stroke:#fff;stroke-width:30;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2261"></path></g></g></g></g></svg>
                    </div>
                    <div class="lang-select-wrapper">
                        <select id="language-picker" onchange="changeLanguage(this.value)" class="language-picker" style="display:none">
                            <option value="he" <?= ($lang === 'he') ? 'selected' : '' ?>>עברית</option>
                            <option value="en" <?= ($lang === 'en') ? 'selected' : '' ?>>English</option>
                            <option value="es" <?= ($lang === 'es') ? 'selected' : '' ?>>Español</option>
                            <option value="ru" <?= ($lang === 'ru') ? 'selected' : '' ?>>Русский</option>
                            <option value="fr" <?= ($lang === 'fr') ? 'selected' : '' ?>>Français</option>
                            <option value="el" <?= ($lang === 'el') ? 'selected' : '' ?>>Ελλάδα</option>
                            <option value="tr" <?= ($lang === 'tr') ? 'selected' : '' ?>>Turkey </option>
                        </select>
                    </div>
                </div>
                <!-- Language Picker End -->
                <style>
                    .alerts {text-decoration:none;position: absolute;right: 6px;width: 60px;height: 100%;display: flex;top: 0;align-items: center;justify-content: center;}
                    .alerts span {position: relative;}
                    .alerts span > .alert-circle {text-decoration:none;position: absolute;left: 0;top: 0;display: flex;width: 26px;height: 26px;background: red;align-items: center;justify-content: center;color: white;border-radius: 50%;font-size: 12px;}
                    .alerts.none {opacity: 0.3;}
                    .alerts.none:hover {opacity: 1;}
                    .alerts .alerts-bell{width:60px;height:60px;display:flex;align-items:center;justify-content:center}
                </style>

            </header>
            <div class="user logged_in">
                <div class="img"><?if($siteData['hostsPicture']){?><img src="/gallery/<?=$siteData['hostsPicture']?>" alt="" /><?}else{ echo mb_substr($_CURRENT_USER->name, 0, 1);}?></div>
                <div class="alerts <?=$alerts || $alerts_wr?  "" : "none"?>">
                    <?//=($_CURRENT_USER->id());?>
                    <span>
						<?if($alerts_wr || $_GET['a']){?>
                            <a class='alert-circle' href="?page=orders&otype=order&orderStatus=active&sourceID=9999&extras=1" style="left:auto;right:0px;background:#115dd3"><?=$alerts_wr?></a>
                        <?}else if($alerts || $_GET['a']){?>
                            <a class='alert-circle'><?=$alerts?></a>
                        <?}?>
						<a class="alerts-bell" style="opacity:0"  href="?page=orders&otype=order&orderStatus=active&sourceID=9999">
						<svg id="Capa_1" enable-background="new 0 0 512 512" style='fill:white' height="30" viewBox="0 0 512 512" width="30" xmlns="http://www.w3.org/2000/svg"><g><path d="m411 262.862v-47.862c0-69.822-46.411-129.001-110-148.33v-21.67c0-24.813-20.187-45-45-45s-45 20.187-45 45v21.67c-63.59 19.329-110 78.507-110 148.33v47.862c0 61.332-23.378 119.488-65.827 163.756-4.16 4.338-5.329 10.739-2.971 16.267s7.788 9.115 13.798 9.115h136.509c6.968 34.192 37.272 60 73.491 60 36.22 0 66.522-25.808 73.491-60h136.509c6.01 0 11.439-3.587 13.797-9.115s1.189-11.929-2.97-16.267c-42.449-44.268-65.827-102.425-65.827-163.756zm-170-217.862c0-8.271 6.729-15 15-15s15 6.729 15 15v15.728c-4.937-.476-9.94-.728-15-.728s-10.063.252-15 .728zm15 437c-19.555 0-36.228-12.541-42.42-30h84.84c-6.192 17.459-22.865 30-42.42 30zm-177.67-60c34.161-45.792 52.67-101.208 52.67-159.138v-47.862c0-68.925 56.075-125 125-125s125 56.075 125 125v47.862c0 57.93 18.509 113.346 52.671 159.138z"/><path d="m451 215c0 8.284 6.716 15 15 15s15-6.716 15-15c0-60.1-23.404-116.603-65.901-159.1-5.857-5.857-15.355-5.858-21.213 0s-5.858 15.355 0 21.213c36.831 36.831 57.114 85.8 57.114 137.887z"/><path d="m46 230c8.284 0 15-6.716 15-15 0-52.086 20.284-101.055 57.114-137.886 5.858-5.858 5.858-15.355 0-21.213-5.857-5.858-15.355-5.858-21.213 0-42.497 42.497-65.901 98.999-65.901 159.099 0 8.284 6.716 15 15 15z"/></g></svg>
						</a>
					</span>
                </div>
                <div class="user-name">
                    <div class="name"><?=$_CURRENT_USER->name?></div>
                    <?php
                    if ($_CURRENT_USER->single_site){
                        ?>
                        <div class="rank" data-siteid="<?=$_CURRENT_USER->active_site()?>"><?=$siteData['siteName']?></div>
                        <?php
                    } else {
                        if ($_CURRENT_USER->access() == 128) { // Check if the user is an admin
                            $sname = udb::key_value("SELECT siteID, siteName FROM sites WHERE active = 1");
                        } else {
                            $sname = udb::key_value("SELECT siteID, siteName FROM sites WHERE siteID IN (" . $_CURRENT_USER->sites(true) . ")");
                        }

                        $sid = $_CURRENT_USER->select_site();

                        ?>
                        <div class="sites-select" style="position:relative;z-index:2; display: <?= $_CURRENT_USER->access() == 128 ? 'block' : ($_CURRENT_USER->single_site ? 'none' : 'block') ?>">


                            <input id="sid_input" onkeyup="filtersid(this,'#sid_list')" type="text" placeholder="<?= $_CURRENT_USER->access() == 128 ? 'כל המתחמים (מנהל)' : 'כל המתחמים' ?>">
                            <ul id="sid_list">
                                <li data-val="">כל המתחמים</li>
                                <?
                                foreach($sname as $id => $name)
                                    echo '<li data-val="'.$id.'">'.$name.'</li>';
                                ?>
                            </ul>
                            <style>
                                #sid_input{height:34px;border:1px solid;padding:0 10px;box-sizing:border-box;width:160px}
                                #sid_list{position:absolute;right:0;width:200px;border:1px solid;max-height:220px;overflow:auto;display:none;z-index:999;background:white}
                                #sid_input:focus + #sid_list,#sid_list:hover {display:block}
                                #sid_list li:not(first-child) {border-top:1px solid #ccc;height:36px;line-height:36px;padding:0 20px;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;cursor:pointer}
                            </style>



                            <?/*
							<select name="sid" class="main-site-select" title="שם מתחם" style="height:34px;max-width:calc(100vw - 140px);border:1px #0dabb6 solid">
								<option value="">- כל המתחמים -</option>
								<?php
									foreach($sname as $id => $name)
										echo '<option value="' , $id , '" ' , ($id == $sid ? 'selected' : '') , '>' , $name , '</option>';
								?>
							</select>
							*/?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <a href="logout.php" class="logout"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 26" width="34" height="26"><path class="shp0" d="M32 7.7L16.3 7.7 16.3 2 2 13 16.3 24 16.3 18.3 32 18.3 32 7.7Z"></path></svg>יציאה</a>

            </div>
            <?php include "partials/menu" . $_CURRENT_USER->suffix('_') . ".php"; ?>
        </div>
    <?php } ?>
    <div class="l-side" id="orderLside">
        <?php include "partials/submenu.php";?>
        <?php if(file_exists("pages/".$page.".php"))include "pages/".$page.".php"; ?>
    </div>
</div>
<script src="<?=$_CURRENT_BASE?>assets/addons/datetimepicker/jquery.datetimepicker.full.min.js"></script>
<script src="<?=$_CURRENT_BASE?>assets/js/jquery.longclick-min.js?v=<?=time()?>"></script>

<script src="<?=$_CURRENT_BASE?>assets/js/Xswal.js?v=1"></script>
<?php if($page == 'signature') { ?>
    <script src="<?=$_CURRENT_BASE?>assets/js/signature_pad.umd.js?v=<?=time()?>"></script>
    <script src="<?=$_CURRENT_BASE?>assets/js/app.js?v=<?=time()?>"></script>
<?php } ?>

<link rel="stylesheet" type="text/css" href="src/anypicker-font.css" />
<link rel="stylesheet" type="text/css" href="src/anypicker.css" />

<link rel="stylesheet" type="text/css" href="src/anypicker-ios.css" />
<link rel="stylesheet" type="text/css" href="src/anypicker-android.css" />
<link rel="stylesheet" type="text/css" href="src/anypicker-windows.css" />

<link rel="stylesheet" href="<?=$_CURRENT_BASE?>assets/css/autoComplete.02.min.css" />

<script type="text/javascript" src="src/anypicker.js"></script>
<script src="<?=$_CURRENT_BASE?>assets/js/fm.timetator.jquery.js?v=<?=date("Y-m-d")?>"></script>

<style>


    /* LTR layout */










    .alerts .alerts-bell {
        margin-left:480px;
    }


    .rtl-layout .alerts .alerts-bell {
        margin-left:0;
    }


    .inputWrap select+.select2 .select2-selection__rendered {
        line-height: 60px!important;
    }

    .inputWrap select+.select2, .inputWrap select+.select2 span {
        height: 100%!important;
    }

    #calPlus > div.r-side > div.user.logged_in > div.user-name > div.sites-select > span {
        display: none !important;
    }
</style>


<link rel="stylesheet" href="<?=$_CURRENT_BASE?>assets/css/select2.min.css">


<style>
    .inputWrap .autoBox{position:absolute;top:60px;right:0;background:#fff;width:302px;max-height:0;overflow:hidden;border:2px solid red;z-index:101;box-sizing:border-box;opacity:0;-moz-transition:all .5s;-webkit-transition:all .5s;transition:all .5s}
    .inputWrap .autoBox a,.inputWrap .autoBox span{display:block;padding:5px 30px 5px 5px;text-align:right;cursor:pointer;font-size:18px}
    .inputWrap .autoBox a:nth-child(even),.inputWrap .autoBox span:nth-child(even){background:#f8f8f8}
    .inputWrap .autoBox a>b,.inputWrap .autoBox span>b{color:#d9418f}
    .inputWrap .autoBox a:hover,.inputWrap .autoBox span:hover{background-color:#09a1e7;color:#fff}
    .inputWrap .autoBox a:hover b,.inputWrap .autoBox span:hover b{color:#fff;font-weight:400}
    .inputWrap .autoBox a{color:#000}
    .inputWrap .autoBox a.keyActive,.inputWrap .autoBox span.keyActive{background:#fdf0d8!important}

    .inputWrap .inner.active .autoBox{max-height:200px;border:1px solid #2d3b60;padding:0;box-shadow:0 0 5px rgba(0,0,0,.5);opacity:1;overflow:auto}
    .inputWrap .autoBox .autoComplete .title{font-size:12px;text-align:left;padding:0 5px;background:#f1f1f1;color:#bebebe;border-top:1px solid #bebebe;border-bottom:1px solid #bebebe}
    .inputWrap .autoBox .autoSuggest .title{font-size:12px;text-align:left;padding:0 5px;background:#f1f1f1;color:#bebebe;border-top:1px solid #bebebe;border-bottom:1px solid #bebebe}
</style>
<script type="text/javascript">
    let placesArrayFree=[
        {
            id: "1",
            name:"שם איש קשר",
            idnumber: '000000000',
            phone: '0500000000',
            phone2: '0500000000',
            email: 'email@email.com',
            address: 'כתובת כלשהי',
            gender: 'male',
            tgender: 'male'
        },
    ];
    let placesArrayFree_phone=[
        {
            id: "1",
            name:"שם איש קשר",
            idnumber: '000000000',
            phone: '0500000000',
            phone2: '0500000000',
            email: 'email@email.com',
            address: 'כתובת כלשהי',
            gender: 'male',
            tgender: 'male'
        },
    ];
</script>

<script src="<?=$_CURRENT_BASE?>assets/js/select2.min.js"></script>
<script src="assets/js/AComplete_1306.js?v=<?=time()?>"></script>
<script src="<?=$_CURRENT_BASE?>assets/js/website.js?v=<?=time()?>"></script>
<script src="/cms/app/tinymce/tinymce.min.js"></script>
<script type="text/javascript">
    const menuc = localStorage.getItem('menuc');
    $.post('ajax_global.php',{act: 'menuc' , menuc:menuc })
    if(menuc == 1){
        $('body').addClass('menu-closed');
    }
    <?php if (!$_CURRENT_USER->single_site){ ?>

    function filtersid(element,what) {
        var value = $(element).val();
        value = value.toLowerCase().replace(/\b[a-z]/g, function(letter) {
            return letter.toUpperCase();
        });

        if (value == '') {
            $(what+'  li').show();
        }
        else {
            $(what + ' > li:not(:contains(' + value + '))').hide();
            $(what + ' > li:contains(' + value + ')').show();

        }
    };

    $(function() {
//				if($('.sites-select select').val()>0) {
//					$('#send-site').val($('.sites-select select').val()).change();
//				}

        if (<?= $_CURRENT_USER->access() == 128 ? 'true' : 'false' ?>) {
            $('#sid_list li').on('click', function() {
                let params = new URLSearchParams(window.location.search);
                params.set('asite', $(this).data('val'));
                window.location.search = '?' + params;
            });
        }

        $('.sites-select select').on('change', function() {
            let params = new URLSearchParams(window.location.search);
            params.set('asite', this.value);
            window.location.search = '?' + params;
        })
    });
    <?php } ?>

    var editors = $(document).find('textarea.textEditor:not([aria-hidden=true])');
    if(editors.length){
        editors.each(function(i,obj){
            tinymce.init({
                readonly:(obj.name=="agreement1"?1:0),
                target: this,
                height: 500,
                plugins: [
                    "advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
                    "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                    "table contextmenu directionality emoticons textcolor paste  textcolor colorpicker textpattern"
                ],
                fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
                toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
                toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
                toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking pagebreak restoredraft"
            });
        });
    }

    $(".sendPop .whatsapp").click(function(){
        if($("#sendPop_phone").val()){
            var hrefText = "///wa.me/972" + $("#sendPop_phone").val() + "?text=" + $("#sendPopMsg").val()
            $("#sendPop_icoWA").prop("href", hrefText);
            $("#sendPop_icoWA")[0].click();
        }else{
            swal.fire({icon: 'error',title: "יש להזין טלפון תקין"});
        }
    });

    $(document).on('change', '#tosign', function() {
        if($(this).is(':checked')) {
            $('.walink').attr('href', $('.walink').attr('data-sign-href'));
        } else {
            $('.walink').attr('href', $('.walink').attr('data-href'));
        }
    });

    $(".sendPop .sms").click(function(){
        if($("#sendPop_phone").val()){
            if($(window).width() > 992) {
                $.post('ajax_sendSMS.php', {phone:$("#sendPop_phone").val(), msg:$("#sendPopMsg").val()}, function(res) {
                    if(res.error)
                        return Swal.fire({icon: 'error',title: "יש להזין טלפון תקין"});
                    Swal.fire({icon:'success', text:res.msg});
                })
            } else {
                var hrefText = "sms:" + $("#sendPop_phone").val() + "?&body=" +$("#sendPopMsg").val()
                $("#sendPop_icoSMS").prop("href", hrefText);
                $("#sendPop_icoSMS")[0].click();
            }
        }else{
            swal.fire({icon: 'error',title: "יש להזין טלפון תקין"});
        }


    });

    $(".sendPop .mail").click(function(){
        if($("#sendPop_email").val()){
            var hrefText = "mailto:" + $("#sendPop_email").val() +"?subject=" + $("#sendPopSubject").val()  + "&body=" + $("#sendPopMsg").val()
            //$("#sendPop_icoMail").prop("href", hrefText);
            //$("#sendPop_icoMail")[0].click();
            var sendData = {
                to: $("#sendPop_email").val(),
                subject: $("#sendPopSubject").val(),
                body: $("#sendPopMsg").val().replace('%26', '&')
            };
            console.log(sendData);
            $.ajax({
                url: 'ajax_sendEmail.php',
                method: 'post',
                type: 'post',
                data: sendData,
                success: function (response) {
                    if(response.error) {
                        swal.fire({icon: 'error',title: response.error});
                    }
                    else {
                        swal.fire({icon: 'success',title: "נשלח בהצלחה"});
                    }
                }
            });
        }else{
            swal.fire({icon: 'error',title: "יש להזין כתובת מייל תקינה"});
        }

    });

    $(".plusSend").click(function(){
        if($(this).data('title')){
            $('#SendPopTitle').text($(this).data('title'))
        }
        $("#sendPopMsg").val($(this).data('msg'));
        $("#sendPopSubject").val($(this).data('subject'));
        $('.sendPop').fadeIn('fast');

    });
</script>

<?php if($_GET['ssd_new']) { ?>
    <div class="popup payAmount" id="payAmount">
        <div class="popup_container">
            <div class="close" onclick="$('#payAmount').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
            <div class="title">ביצוע תשלום</div>
            <div class="con">ביצוע תשלום באמצעות כרטיס לביטחון כרטיס שמסתיים בספרות 4582</div>
            <div class="form">
                <div class="inputWrap full">
                    <input type="text" inputmode="numeric" name="amount" value="2000">
                    <label for="amount">סכום תשלום</label>
                </div>
                <div class="inputWrap select">
                    <select name="payments">
                        <option>1</option>
                        <option>1</option>
                        <option>1</option>
                    </select>
                    <label for="payments">תשלומים</label>
                </div>
                <div class="inputWrap full">
                    <input type="text" inputmode="numeric" name="amount" value="333">
                    <label for="amount">CCV (3 ספרות בגב הכרטיס)</label>
                </div>
                <div class="submit">
                    ביצוע תשלום
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 26" width="34" height="26"><path class="shp0" d="M32 7.7L16.3 7.7 16.3 2 2 13 16.3 24 16.3 18.3 32 18.3 32 7.7Z"></path></svg>
                </div>
                <img src="<?=$_CURRENT_BASE?>assets/img/security_pay.jpg" style="max-width:none;margin:0 -40px 0 0" alt="" />
            </div>
        </div>
    </div>
<?php } ?>

<div class="picPop" style="display:none">
    <div class="container">
        <div class="close" onclick="$('.picPop').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title mainTitle" id="SendPopTitle">אסמכתא</div>

        <div class="content">
            <img src="" id="reviewDoc">
        </div>
    </div>
</div>
<script>
    window.openFoo = <?=($_CURRENT_USER->is_spa() ? 'openSpaFrom' : 'openOrderFrom')?>;
    $(function(){
        var match;
        if (window.location.hash && (match = window.location.hash.match(/#or(\d+)/))){
            window.openFoo.call(window, {orderID:match[1]});
            window.location.hash = '';
        }
    });
    var sourcesArray = [];
    <?=$jssourcesArray;?>

    // SET default date range for reports = this month / this week / this day

    var ReportRange = ['yaadTrans','report_menage','stats_treatments','report_extras'];

    ReportRange.forEach((elm) =>{
        if(parseInt(localStorage.getItem(elm))>0){
            set_session_global(parseInt(localStorage.getItem(elm)),elm)
        }
    })


    function set_session_global(type,tvalue){
        $.post('ajax_settings.php', { act:'setReportRange', type:type, val:tvalue}).then(function(res){
            /*
             if (!res || res.status === undefined || parseInt(res.status))
                 return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                     window.location.reload();
               });*/

        });
    }

    // END SET default date range for reports = this month / this week / this day

</script>
<!-- Add the Select2 initialization here -->
<script>
    $(document).ready(function() {
        $('#language-picker').select2({
            minimumResultsForSearch: -1 // Hides the search box
        });
    });
</script>
<!-- Include translations.js -->
<script src="translations.js"></script>
<!-- issue-reporter.js -->
<script src="issue-reporter/issue-reporter.js"></script>
<script src="issue-reporter/issue-reporter-comments-modal.js"></script>
</body>
</html>
