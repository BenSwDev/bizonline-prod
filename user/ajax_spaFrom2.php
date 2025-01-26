<?php
	require_once "auth.php";
	require_once "functions.php";

	if (!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){
		include 'ajax_spaFromTherapist.php';
		return;
	}
$_timer = new BizTimer;
	$multiCompound = !$_CURRENT_USER->single_site;

    $siteID = 0;

	$que = "SELECT mainPageTitle,mainPageID FROM `MainPages` WHERE mainPageType = 100 AND ifShow = 1";
	$reasons = udb::full_list($que);

	$orderID = intval($_POST['data']['orderID']);
	$orderType = typemap($_POST['data']['ptype'] ?? 'order', 'string');
    $asOrder = intval($_POST['data']['as_order'] ?? 0);
    if (!UserUtilsNew::$orderTypes[$orderType])
        $orderType = 'order';

	$startDate = $_POST['data']['startDate'];
	$endDate = $_POST['data']['endDate'];

    $order = $units = [];
$_timer->log();
	if($orderID){
		$noOrder = 1;
		while($noOrder && $noOrder<3){
			$noOrder ++;
			$que = "SELECT sites.siteName, orders.* , `settlements`.`TITLE` as `clientCity`
			, MIN(IF(T_orders.timeFrom = '0000-00-00 00:00:00', NULL, T_orders.timeFrom)) AS `abs_timeFrom`
			, MAX(T_orders.timeUntil) AS `abs_timeUntil`			
			, IF(T_orders.treatmentID = 0 AND `orderUnits`.orderID IS NULL, 1, 2) AS `TimeType`
			FROM `orders` 
			INNER JOIN `sites` USING(`siteID`) 
			LEFT JOIN settlements ON (orders.settlementID = settlements.settlementID)
			LEFT JOIN `orders` AS T_orders ON (`orders`.orderID = T_orders.parentOrder)
			LEFT JOIN `orderUnits` ON (`orders`.orderID = `orderUnits`.orderID)
			WHERE `orders`.`orderID` = " . $orderID;
			$order = udb::single_row($que);
			
			
			if($order['parentOrder'] && $order['parentOrder'] != $order['orderID'] ){
				$orderID = $order['parentOrder'];
				$open_treatmentID = $order['orderID'];

			}else{
				$noOrder = 0;
			}
		}
$_timer->log();
		if($order['orderType'] =="preorder"){	
			$showthisOrder = 1;
			include 'ajax_spaFromTherapist.php';
			return;
		}
$_timer->log();
		if(date('d.m.y', strtotime($order['abs_timeUntil'])) == date('d.m.y', strtotime($order['abs_timeFrom'])) || $order['TimeType']==1){
			$date_n_day = "ביום ".$weekday[date('w', strtotime($order['abs_timeFrom']))]." - ".date('d.m.y', strtotime($order['abs_timeFrom']));
		}else{
			$date_n_day = ', בימים ' .$weekday[date('w', strtotime($order['abs_timeFrom']))]."-".$weekday[date('w', strtotime($order['abs_timeUntil']))].": ".date('d.m.y', strtotime($order['abs_timeFrom']))." - ". date('d.m.y', strtotime($order['abs_timeUntil']));
		}

        if (!$_CURRENT_USER->has($order['siteID']))
            throw new Exception("Access denied to order #" . $order['orderIDBySite']);

        $siteID = $order['siteID'];

		$enable_review = udb::single_row("SELECT sendReviews FROM sites WHERE siteID=".$siteID);
		$blockAutoSend = udb::single_value("SELECT blockAutoSend FROM sites WHERE siteID=".$siteID);

        $que = "SELECT orderUnits.*, rooms_units.roomID FROM orderUnits INNER JOIN `rooms_units` USING(`unitID`) WHERE `orderID` = ".$orderID;
        $units = udb::key_row($que, 'unitID');

        /**CREATE MAIL WHATSAPP SMS**/
		$link = WEBSITE . "signature2.php?guid=".$order['guid'];
		$link_sign = WEBSITE . "signature2.php?guid=".$order['guid']."%26signature=true";
		//if($order['approved'] || $order['status']!=1){
        if(!$order['approved'] && $order['status']==1){

			$subject = "טופס לאישור הזמנה ב". $order['siteName'] ." בתאריך".date('d.m.y', strtotime($order['timeFrom']));
			$body = $order['customerName'].' שלום, על מנת לאשר את הזמנתך ב'.$order['siteName']. $date_n_day .' יש ללחוץ על הקישור הבא '.$link;

		}else{
			$subject = "יצירת קשר בנוגע להזמנה ב". $order['siteName'] ." בתאריך".date('d.m.y', strtotime($order['abs_timeFrom']));
			$body = $order['customerName'].' שלום, '.(($order['approved'] && $order['status']==1)? "מצורף קישור לטופס ההזמנה שלך ".$link : "");
		}
$_timer->log();
		if($order["customerPhone"]){
			//$phoneClean = str_replace("-","",$order['customerPhone']);
			//$order["whatsapp"] = "///wa.me/972".ltrim($phoneClean, '0')."?text=".$body;			
			//$order["whatsapp"] = whatsappBuild($order['customerPhone'],$body);
			$order["sms"] = "sms:".$phoneClean."?&body=".$body;
		}
		$order["mailto"] = "mailto:".$order['customerEmail']."?subject=".$subject."&body=".$body;
		/*****/

		$startDate =	intval(substr($order['showTimeFrom'],0,4))? implode('/',array_reverse(explode('-',substr($order['showTimeFrom'],0,10)))) : "";
		$endDate =		intval(substr($order['showTimeUntil'],0,4))? implode('/',array_reverse(explode('-',substr($order['showTimeUntil'],0,10)))): "";
		$startTime =	intval(substr($order['showTimeFrom'],0,4))? substr($order['showTimeFrom'],11,5): "";
		$endTime =		intval(substr($order['showTimeUntil'],0,4))? substr($order['showTimeUntil'],11,5): "";

		$orderType = $asOrder ? 'order' : $order['orderType'];

        $que = "SELECT `rooms`.`siteID`, `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms_units`.`hasStaying`,`rooms`.`roomName`,`rooms`.`cleanTime`, `rooms`.maxAdults, `rooms`.maxKids, `rooms`.maxGuests
                FROM `rooms_units` INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
                    LEFT JOIN `orderUnits` ON (orderUnits.unitID = `rooms_units`.`unitID` AND orderUnits.orderID = " . $orderID . ")
                WHERE `rooms`.`siteID` = " . $order['siteID'] . " AND (rooms.active = 1 OR orderUnits.unitID IS NOT NULL) AND `rooms_units`.`hasStaying` > 0";
        $rooms = udb::key_row($que,'unitID');

        $siteData = udb::single_row("SELECT `sites`.`cleanGlobal`, `sites`.`checkInHour`, `sites`.`checkOutHour`,`sites`.`siteName`,`sites`.`sendReviews`, `sites`.`sourceRequired`, `sites`.`addressRequired`,  `sites_langs`.`defaultAgr`, `sites_langs`.`agreement1`, `sites_langs`.`agreement2`, `sites_langs`.`agreement3`
                , IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal`
            FROM `sites` INNER JOIN `sites_langs` ON (`sites_langs`.`siteID` = `sites`.`siteID` AND `sites_langs`.`langID` = 1 AND `sites_langs`.`domainID` = 1)
	        WHERE `sites`.`siteID` = " . $order['siteID']);

        $default = udb::key_value("SELECT `siteID`, `defaultAgr` FROM `sites_langs` WHERE `domainID` = 1 AND `langID` = 1 AND `siteID` = " . $order['siteID']);

        $paid = (new OrderSpaMain($orderID))->get_paid_sum();

        $order['healthMailSent'] = 0;

        $lastTreat_x = 0;

        $que = "SELECT orders.*, health_declare.guid as health_guid, therapists.siteName AS `masterName`, orderUnits.extraRoomName AS `roomName`, treatments.treatmentName
                FROM `orders` 
                    LEFT JOIN `orderUnits` USING(`orderID`)
                    LEFT JOIN `therapists` USING(`therapistID`)
                    LEFT JOIN `treatments` USING(`treatmentID`)
					LEFT JOIN `health_declare` ON (orders.orderID = health_declare.orderID)
                WHERE orders.parentOrder = " . $orderID . " AND orders.orderID <> " . $orderID . "
                GROUP BY orders.orderID";
        $treatments = udb::single_list($que);
        foreach($treatments as &$treat){
            if ($treat['timeFrom'][0] != '0'){      // not 0000-00-00 00:00:00
                list($treat['startDate'], $treat['startTime']) = explode(' ', substr($treat['timeFrom'], 0, 16));
                list($treat['endDate'], $treat['endTime']) = explode(' ', substr($treat['timeUntil'], 0, 16));
            }

            if ($treat['healthMailSent'])
                $order['healthMailSent'] += 1;

            if(strtotime($treat['timeFrom']) > $lastTreat_x) {
                $lastTreat_x = strtotime($treat['timeFrom']);
            }
        }
        unset($treat);
$_timer->log();

        $order['healthMailSent'] = max(0, $order['healthMailSent'] - count($treatments) + 1);

        $orderExtras = $order['extras'] ? json_decode($order['extras'], true) : [];

        if ($treatments)
            $selectedDate = min(array_map(function($treat){
                return $treat['startDate'];
            }, $treatments));
        elseif ($order['timeFrom'][0] != 0)
            $selectedDate = substr($order['timeFrom'], 0, 10);
        else
            $selectedDate = date('Y-m-d');
	}
	else {
        $que = "SELECT `rooms`.`siteID`,`rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms_units`.`hasStaying`,`rooms`.`roomName`,`rooms`.`cleanTime`, `rooms`.maxAdults, `rooms`.maxKids, `rooms`.maxGuests
                FROM `rooms_units`
                INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
                WHERE rooms.active = 1 AND `rooms`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ") AND `rooms_units`.`hasStaying` > 0" ;
        $rooms = udb::key_row($que,'unitID');

        $siteData = udb::single_row("SELECT `sites`.`cleanGlobal`, `sites`.`checkInHour`, `sites`.`checkOutHour`,`sites`.`siteName`,`sites`.`sendReviews`, `sites`.`sourceRequired`, `sites`.`addressRequired`, `sites_langs`.`defaultAgr`, `sites_langs`.`agreement1`, `sites_langs`.`agreement2`, `sites_langs`.`agreement3`
                , IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal`
            FROM `sites` INNER JOIN `sites_langs` ON (`sites_langs`.`siteID` = `sites`.`siteID` AND `sites_langs`.`langID` = 1 AND `sites_langs`.`domainID` = 1)
	        WHERE `sites`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ")");

        $default = udb::key_value("SELECT `siteID`, `defaultAgr` FROM `sites_langs` WHERE `domainID` = 1 AND `langID` = 1 AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
$_timer->log();
        $startTime = $siteData['checkInHour'];
        $endTime = $siteData['checkOutHour'];

        $paid = 0;

        $treatments = $orderExtras = [];

        $siteID = $_CURRENT_USER->select_site();

        $selectedDate = date('Y-m-d');
    }
$_timer->log();
    $lastTreat = '0000-00-00 00:00:00';
    foreach($treatments as $treat)
        if (strcmp($lastTreat, $treat['timeUntil']) < 0)
            $lastTreat = $treat['timeUntil'];

	if($orderID){

		$que = "SELECT s.*, e.*, IFNULL(s.siteID, " . $order['siteID'] .") AS `siteID`
			FROM  `treatmentsExtras` AS `e`
			LEFT JOIN `sites_treatment_extras` AS `s` ON (e.extraID = s.extraID AND s.siteID = " . $order['siteID'] .") 
			WHERE s.active = 1 " . (($orderExtras['extras']) ? " OR e.extraID IN (" . implode(',', array_keys($orderExtras['extras'] ?: [0])) . ")" : "") . " 
			ORDER BY e.showOrder";
        $extras = udb::key_list($que, ['siteID', 'extraType']);

        /*if ($orderExtras['extras']){
            foreach($orderExtras['extras'] as $exID => $ex)
                if (isset($extras[$order['siteID']][$exID]))
                    if ($extras[$order['siteID']][$exID]['extraType'] == 'rooms'){
                        if ($ex['forNight'])
                            $extras[$order['siteID']][$exID]['price3'] = $ex['price'];
                        elseif ($ex['extraHours'])
                            $extras[$order['siteID']][$exID] = array_merge($extras[$order['siteID']][$exID], ['price1' => $ex['basePrice'], 'price2' => $ex['hourPrice'], 'countMin' => $ex['baseHours'], 'countMax' => $ex['baseHours'] + $ex['extraHours']]);
                        else
                            $extras[$order['siteID']][$exID]['price1'] = $ex['price'];
                    }
                    else
                        $extras[$order['siteID']][$exID]['price1'] = $ex['price'];
        }*/
	} else {
		$que = "SELECT * 
			FROM  `treatmentsExtras` AS `e`
			INNER JOIN `sites_treatment_extras` AS `s`  USING(`extraID`) 
			WHERE (s.siteID IN (" . $_CURRENT_USER->sites(true) . ") AND e.extraType <> 'package' AND s.active = 1)
			ORDER BY e.showOrder";
        $extras = udb::key_list($que, ['siteID', 'extraType']);
	}

$_timer->log();

	if(!$order['domainID']) $order['domainID'] = "0";

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
$_timer->log();
?>
	<div class="create_order <?=$orderType?> spaorder" id="create_orderPop" style="<?=((isset($order['status']) && $order['status'] <= 0) ? 'filter:saturate(.1)' : '')?>">
		<div class="arrow_cls" onclick="$(this).parent().toggleClass('cls')"></div>
		<div class="container">
			<div class="close" onclick="closeOrderForm(typeof spaOrderChanged === 'undefined' ? false : spaOrderChanged)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
			<div class="title mainTitle">
				<?if($order['sourceID']){?>
					<div class="domain-icon <?=$order['sourceID']?>" title='<?=$order['sourceID']?>' style="background-color: <?=$sourcesArray[$order['sourceID']]['color']?>"><?=$sourcesArray[$order['sourceID']]['letterSign']?></div>
				<?}else{?>
				<div class="domain-icon" style="background-image:url(<?=$domain_icon[$order['domainID']]?>)"></div>
				<?}?>

				<?if($asOrder==1){?>
					הפוך שיריון להזמנה
				<?}else{?>
					<?=($order['orderIDBySite'] ? (($orderType=="preorder"? "שיריון" : "הזמנה")." מספר ".$order['orderIDBySite']) : (($orderType=="preorder")?  "שיריון מקום" :  "הזמנת ספא חדשה"))?>
				<?}?>
			</div>

			<form class="form" id="orderForm" action="" data-guid="<?=$order['guid']?>" method="post" autocomplete="off" data-defaultAgr="<?=$siteData['defaultAgr']?>">
                <?=($asOrder ? '<input type="hidden" name="as_order" value="1" class="ignore" />' : '')?>
                <input type="hidden" name="otype" value="<?=$orderType?>" class="ignore" />
				<input type="hidden" name="action" value="insertOrder" class="ignore" />
				<input type="hidden" name="orderID" value="<?=$order['orderID']?>" id="orderForm-orderID" />
				<input type="hidden" name="realStartTime" id="realStartTime" value="<?=$siteData['checkInHour']?>" class="ignore" />
				<input type="hidden" name="realEndTime" id="realEndTime" value="<?=$siteData['checkOutHour']?>" class="ignore" />
				<input type="hidden" name="specialfields" value="0">
				<script>
					$(document).on('change', '.dataInp.babies select', function() {
						$('input[name="specialfields"]').val(1)
					});
					$(document).on('change', 'textarea[name="comments_owner"]', function() {
						$('input[name="specialfields"]').val(1)
					});
					$(document).on('keyup', 'textarea[name="comments_owner"]', function() {
						$('input[name="specialfields"]').val(1)
					});
					$(document).on('keyup', 'textarea[name="comments_payment"]', function() {
						$('input[name="specialfields"]').val(1)
					});
					$(document).on('change', 'textarea[name="comments_payment"]', function() {
						$('input[name="specialfields"]').val(1)
					})
				</script>
				<div class="inputWrap half select orderOnly" id="wrapsources">
					<select <?=($siteData['sourceRequired'] && !$orderID)? "class='required'" : ""?> onchange="if($(this).val()!='novalue'){$(this).removeClass('required')}else{$(this).addClass('required')};showCoupons($(this))" name="sourceID" id="sourceID" <?=($order['apiSource']=='spaplus' || $order['sourceID']=='online') ? "readonly style='pointer-events:none'":""?>>
<?php
    if ($siteData['sourceRequired'] && !$orderID)
        echo '<option style="color:red" value="novalue">יש לבחור</option>';
?>
						<option value="0">הזמנה רגילה</option>
<?php
    $cuponTypes = $orderID ? SourceList::site_list($siteID, false, $order['sourceID']) : SourceList::site_list($siteID ?: $_CURRENT_USER->active_site());

    foreach($cuponTypes as $source)
        echo '<option value="' . $source['key'] . '" ' . (($source['key'] == $order['sourceID']) ? 'selected' : '') . '>' . $source['fullname'] . '</option>';

                        /*UserUtilsNew::init($_CURRENT_USER->active_site());
						$cuponTypes = UserUtilsNew::$CouponsfullList;
						foreach($cuponTypes as $k=>$source) { ?>
						<option  class="test0"  value="<?=$k?>" <?=$order['sourceID']==$k?"selected":""?>><?=$source?></option>
						<?php }
                        foreach(UserUtilsNew::guestMember() as $k => $source){
                            ?>
                            <option   value="<?=$k?>" <?=$order['sourceID']==$k?"selected":""?>><?=$source?></option>
                        <?php }
                        foreach(UserUtilsNew::otherSources() as $k => $source){
                            ?>
                            <option  value="<?=$k?>" <?=$order['sourceID']==$k?"selected":""?>><?=$source?></option>
                            <?php
                        }
                        ?>
                        <option value="online" <?=$order['sourceID']=='online' ? "selected":""?>>הזמנת Online</option> */ ?>
					</select>
					<label for="sourceID">מקור ההזמנה</label>
					<style>
						#couponTypes{display:none}
						#couponTypes > li{display:none}
						#couponTypes > li.show{display:block}
						#sourceID.hasCoupons{width:calc(100% - 110px)}
						#sourceID.hasCoupons ~ #couponTypes{display:block}
						#couponTypes > li {}
						#couponTypes > li > ul {display: none;}
						#wrapsources .close{display:none}
						#wrapsources.showCoupons{background:white;overflow:visible;z-index:99999}
						#couponTypes > li.show .showTheseCoupons {position: absolute;width: 100px;background: #0dabb6;left: 0;height: 100%;color: white;align-items: center;padding: 0 10px;box-sizing: border-box;cursor: pointer;display: flex;vertical-align: middle;flex-direction: column;align-items: center;justify-content: center;}
						#wrapsources.showCoupons #couponTypes > li.show .showTheseCoupons {display:none}
						#wrapsources.showCoupons .close{display:block;position:absolute;left:30px;top:30px;}
						#wrapsources.showCoupons #couponTypes > li > ul {display: block;margin-top: 60px;text-align: right;background: white;list-style: none;color:black}
						#couponTypes > li > ul li {padding: 10px;border-bottom: 1px solid #ccc;display: flex;flex-wrap: wrap;}
						#couponTypes > li > ul li .cpn_name {width: calc(100% - 120px);display:flex;align-items:center;font-weight:bold}
						#couponTypes > li > ul li .cpn_price {width: 60px;padding: 0 5px;box-sizing: border-box;}
						#couponTypes > li > ul li .cpn_remarks {color: #777;width: 100%;box-sizing: border-box;padding-top: 6px;}
					</style>
					<script>						
						
						function showCoupons(_src){
							var _srcval = _src.val();
							$('#couponTypes > li').removeClass('show');
							//debugger;
							if($('#couponTypes #source_'+_srcval+' ul').length){
								_src.addClass('hasCoupons');
								$('#couponTypes #source_'+_srcval).addClass('show');
							}else{
								_src.removeClass('hasCoupons');
								$('#wrapsources').removeClass('showCoupons');
							}
						}
						
						showCoupons($('#sourceID'));
						$('.showTheseCoupons').on('click',function(){
							$('#wrapsources').addClass('showCoupons');
						});
						$('#wrapsources .close').on('click',function(){
							$('#wrapsources').removeClass('showCoupons');
						});
					</script>
					<div class="close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
					<ul id="couponTypes">
<?php
    foreach($cuponTypes as $source){
        if (!$source['coupons'])
            continue;

        echo '<li id="source_' , $source['key'] , '" class="hasSub"><label></label><ul>';

        $c = count($source['coupons']);
        foreach($source['coupons'] as $customCupon){
?>
                        <li>
                            <div class="cpn_name"><?=$customCupon['fullname']?></div>
                            <div class="cpn_price">נרכש ב : <b><?=$customCupon['couponPayed'] ? "₪" . $customCupon['couponPayed'] : "----"?></b></div>
                            <div class="cpn_price">שווי : <b>₪<?=$customCupon['cuponPrice']?></b></div>
                            <?=($customCupon['cpn_remarks'] ? '<div class="cpn_remarks">' . $customCupon['cpn_remarks'] . '</div>' : '')?>
                        </li>
<?php
        }

        echo '</ul><div class="showTheseCoupons">לחצו לצפיה ב<b>' . (($c > 1) ? '-' . $c . ' קופונים' : 'קופון אחד') . '</b></div></li>';
    }

$_timer->log();

/*					$siteCustomPayments = UserUtilsNew::getCustomPayTypes($siteID);// return under siteID key
					$sql = "select * from sitePayTypes where siteID in (".$siteID.")";
					$sitePayments = udb::key_row($sql,'paytypekey');

					foreach (UserUtilsNew::$dbCuponTypes as $k=>$item) {
						$sql = "select * from payTypes where active=1 and parent=".$item['id'];
						$theCupons = udb::full_list($sql);
						$coupons_cnt=0;
						?>
						<li id="source_<?=$item['key']?>" class="<?=($theCupons || ($siteCustomPayments && $siteCustomPayments[$item['id']])) ? 'hasSub' : '';?>">
						
						<label><?//=$item['fullname']?></label>
						<?
						$sql = "select * from payTypes where active=1 and parent=".$item['id'];
						$theCupons = udb::full_list($sql);
						$theCupons = [];
						if(($siteCustomPayments && $siteCustomPayments[$item['id']]) || $theCupons) {
							
						}
						if($theCupons) {
							foreach ($theCupons as $theCupon) {
								if($sitePayments[$theCupon['id']]){
								if(!$coupons_cnt) echo '<ul>';
								$coupons_cnt++;
								?>
								<li>
									<div class="cpn_name"><?=$theCupon['fullname']?></div>
									<div class="cpn_price">נרכש ב : <b><?=$theCupon['couponPayed']? "₪".$theCupon['couponPayed'] : "----"?></b></div>
									<div class="cpn_price">שווי : <b>₪<?=$theCupon['cuponPrice']?></b></div>
									<?if($theCupon['cpn_remarks']){?><div class="cpn_remarks"><?=$theCupon['cpn_remarks']?></div><?}?>
								</li>
								<?
								}
							}
						}
						if($siteCustomPayments && $siteCustomPayments[$item['id']]) {
							foreach ($siteCustomPayments[$item['id']] as $customCupons) {
								if($sitePayments[$customCupons['id']]){
								if(!$coupons_cnt) echo '<ul>';
								$coupons_cnt++;
								?>
								<li>
									<div class="cpn_name"><?=$customCupons['fullname']?></div>
									<div class="cpn_price">נרכש ב : <b><?=$customCupons['couponPayed']? "₪".$customCupons['couponPayed'] : "----"?></b></div>
									<div class="cpn_price">שווי : <b>₪<?=$customCupons['cuponPrice']?></b></div>
									<?if($customCupons['cpn_remarks']){?><div class="cpn_remarks"><?=$customCupons['cpn_remarks']?></div><?}?>
								</li>								
								<?
								}
							}
						}
						if($coupons_cnt) {
							echo '</ul><div class="showTheseCoupons">לחצו לצפיה ב<b>'.($coupons_cnt>1? '-'.$coupons_cnt.' קופונים' : 'קופון אחד').'</b></div>';
						}
						?>
						</li>
					<?
					}*/
?>
					</ul>
				</div>
				<?php
					if($order['guid'] && $orderType == 'order'){
				?>

				<style>
					.sendswrap{display:none}
					#sends {line-height: 60px;color: white;padding: 0 10px;background: #0dabb6;font-size: 16px;display: inline-block;cursor:pointer}
					#sends.show ~ .sendswrap{display:block}
					#sends:not(.show) span:nth-child(2){display:none}
					#sends.show span:nth-child(1){display:none}
				</style>

				<div id="sends" class='inputWrap half' onclick="$('#sends').toggleClass('show')"><span>הצג</span><span>הסתר</span> ניהול שליחות</div>
				<div class="inputLblWrap sendswrap" style="width:100%;margin:0;text-align:right">
					<?php if(!$order['signature']) { ?>
                    <div class="pdfbtn" data-print="pre_print.php?oid=<?=$orderID?>" data-p="" style="margin:0">הדפס הסכם לא חתום</div>
					<div style="float:left;">
						<label class="switch" style="float:left;" for="hidePrices" >
							<input type="checkbox" name="hidePrices" onchange="hideSpaPrices(<?=$orderID?>,$(this).is(':checked'))" id="hidePrices" <?=$order['hidePrices']?"checked":""?>  class="">
							<span class="slider round"></span>
						</label>
						<span style="width:50px;font-size:12px;text-align:left;float:left;padding:4px">הסתר מחירים</span>
					</div>
					<div class="signOpt inOrder">
						<div class="switchTtl">אשר הזמנה <span style="font-weight: normal;
    font-size: 12px;display:inline-block;line-height:1;vertical-align:middle;">(למרות שלא נחתמה)</span></div>
						<label class="switch" style="float:left" for="approvedx">
						  <input type="checkbox" name="adminApproved" value="1" id="approvedx" <?=$order['adminApproved']?"checked":""?>  class="">
						  <span class="slider round"></span>
						</label>
					</div>
					<?php } ?>
					<div class="signOpt inOrder">
						<div class="switchTtl">חתומה / לא חתומה</div>
						<label class="switch" style="float:left">
						  <input type="checkbox" name="approved" value="1" class="ignore" <?=$order['signature']?"checked":""?> onclick="return false;">
						  <span class="slider round"></span>
						</label>
					</div>
					<?php if($order['signature'] && $order['approved']) { ?>
					<div style="background:white;border:1px #ccc solid;margin-bottom:10px;padding:10px;text-align:center;border-radius: 0 0 10px 10px;margin-top:-20px;z-index:0">

					<div class="pdfbtn" data-print="https://bizonline.co.il<?=$order['file']?>" data-p="">הדפס הסכם</div>
						<img class='sigImg' src='/<?=$order['signature']?>' style="    max-width: 60%;height: auto!important;">
					</div>
					<?php }?>


					<div class="signOpt inOrder" style="position:relative;">
						<?php if($order['order_mail_bymail'] || $order['order_mail_bysms']) { ?><span style="position: absolute;top: 0;font-weight:600;right: 10px;font-size: 14px;color: #e73219;">נשלחה תזכורת</span><?php } ?>
					<?php
					$subject = "טופס לאישור הזמנה ב". $siteData['siteName'] ." בתאריך".date('d.m.y', strtotime($order['abs_timeFrom']));
					$subject_sign = "טופס לאישור הזמנה ב". $siteData['siteName'] ." בתאריך".date('d.m.y', strtotime($order['abs_timeFrom']));
					$body = $order['customerName'].' שלום, לצפיה בפרטי ההזמנה שלך ב' . $siteData['siteName']." " . $date_n_day.' יש ללחוץ על הקישור הבא '.$link;
					$body_sign = $order['customerName'].' שלום, על מנת לאשר את הזמנתך ב' . $siteData['siteName']." " . $date_n_day.' יש ללחוץ על הקישור הבא '.$link_sign;
					$phoneClean = str_replace("-","",$order['customerPhone']);


					 /*else if(!$order['approved']){*/
						?>
							<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:1.4"><?=$order['approved']?"יצירת קשר":"שליחה ללקוח<span class='sign'><input type=\"checkbox\" style=\"display:inline-block;vertical-align:middle;margin-right:10px;\" id=\"tosign\" name=\"tosign\"> <label for=\"tosign\">לחתימה</label></span>"?></div>
							<div style="float:left;margin:-5px -5px -5px 0">
							<?if($order['customerPhone']) { ?>
								<a href="<?whatsappBuild($order['customerPhone'],$body);?>" class="walink" data-href="<?whatsappBuild($order['customerPhone'],$body);?>" data-sign-href="<?whatsappBuild($order['customerPhone'],$body_sign);?>" target="_blank"><span class="icon whatsapp" data-phone="<?=$phoneClean?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span></a>
								<a href="sms:<?=$phoneClean?>?&body=<?=$body?>"  data-phone="<?=$phoneClean?>" data-msg="<?=htmlspecialchars($body, ENT_COMPAT)?>" data-sid="<?=$siteID?>"  target="_blank" class="a_sms"><span class="icon sms" data-sms="<?=$phoneClean?>">
									<img src="/user/assets/img/icon_sms.png" alt="sms">
								</span></a>
							<?php } ?>
							<?php if($order['customerEmail']) { ?>
								<a href="mailto:<?=$order['customerEmail']?>?subject=<?=$subject?>&body=<?=$body?>" target="_blank"><span class="icon mail" data-mail="<?=$order['customerEmail']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" width="32" height="27"><style>.message-ic{fill:#fff}</style><path class="message-ic" d="M29.9 25L24.1 19.4 23.5 18.8 23 18.3 22.6 17.8 22.1 17.4 24.9 15.5 29.8 12 30 11.9 30 14 30 17.2 30 25 29.9 25ZM22.2 6L5 10.9 5.7 13.3 2.1 10.7 2.8 7.8C4.1 7 12.5 2.1 15.9 2 19.3 2.1 27.7 7 29 7.8L29.9 10.7 24.7 14.5 22.2 6ZM6.2 14.8L9.7 17.4 9 18.1 8.5 18.6 8.1 19.1 7.5 19.6 2.1 25 2 25 2 13.1 2 12 2 11.9 2.7 12.4 6.2 14.8ZM9.5 19L10 18.5 10.4 18.1 10.8 17.7 11.2 17.2 11.3 17.2C11.4 17.1 11.6 16.9 11.8 16.8 12.9 15.8 14.4 15.3 15.9 15.3 17.4 15.3 18.9 15.8 20 16.8 20.2 16.9 20.3 17 20.5 17.2L20.6 17.3 21.1 17.7 21.2 17.9 21.7 18.3 22.2 18.8 23 19.7 28.4 25 3.5 25 7.8 20.7 9.5 19Z"></path></svg></span></a>
							<?php } ?>
								<span class="icon plusSend" onclick='$("#sendPopMsg").val($("#tosign").is(":checked")?$(this).data("msg-sign"):$(this).data("msg"));$("#sendPopSubject").val($(this).data("subject"));$(".sendPop").fadeIn("fast");$("#SendPopTitle").text($(this).data("title"));' data-title="<?=$order['approved']?"יצירת קשר":"שליחה לחתימה"?>" data-msg="<?=$body?>" data-subject="<?=$subject?>" data-msg-sign="<?=$body_sign?>" data-subject-sign="<?=$subject_sign?>"></span>
							</div>
					</div>

<div class="signOpt inOrder">
	<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:34px">שליחת הזמנה
		<span style="font-size: 14px; line-height: 1; width: 115px; display: inline-block; padding: 3px 10px; font-weight: normal;">
<?php
    switch($order['mail_sent']){
        case 1: echo '<b style="color:green">נשלחו פרטי הזמנה במייל</b>'; break;
        case 2: echo '<b style="color:green">נשלחו פרטי הזמנה ב-sms</b>'; break;
        case 3: echo '<b style="color:green">נשלחו פרטי הזמנה במייל ו-sms</b>'; break;
        default: echo 'תשלח <strong>ללקוח בסיום שמירת ההזמנה</strong>';
    }
?>
        </span>
	</div>
<?php
    if(!$orderID || ($order['mail_sent'] <= 0 && $order['status'] == 1 && $order['orderType'] == 'order')) {
?>
		<label class="switch" style="float:left">
		  <input type="checkbox" name="sendOrderMail" value="1" class="ignore" <?=(((!$orderID || $order['mail_sent'] < 0) && $blockAutoSend!=1) ? 'checked="checked"' : '')?> />
		  <span class="slider round"></span>
		</label>
<?php
    }
?>
</div>
<?if($order['sourceID']!='spaplus'){?>

                    <div class="signOpt inOrder" style="display:none">
                        <div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:34px">הצהרת בריאות
                            <span style="font-size: 14px; line-height: 1; width: 115px; display: inline-block; padding: 3px 10px; font-weight: normal;">
<?php
	if ($order['healthMailSent'])
	    echo '<b style="color:green">בקשה למילוי נשלחה ללקוחות</b>';
	else {
		if(time() < $lastTreat_x) {
	    	echo 'תשלח למילוי <strong>בתחילת יום ההגעה</strong>';
		}
	}
?>
                            </span>
                        </div>
                        <?php
                        if (!$order['healthMailSent']){
                            ?>
							<?php if(time() < $lastTreat_x) { ?>
                            <label class="switch" style="float:left">
                                <input type="checkbox" name="healthMailAccept" value="1" class="ignore" <?=($order['healthMailAccept'] || !$orderID)?"checked=''":""?>>
                                <span class="slider round"></span>
                            </label>
							<?php } ?>
                            <?php
                        }
                        ?>
                    </div>
					<?php
					if($order && $orderType == 'order' && $order["orderID"]){
                    $que = "SELECT reviews.*, papa.orderID, files.src AS document 	
                            FROM `reviews` INNER JOIN `orders` USING (`orderID`) INNER JOIN `orders` AS `papa` ON (papa.orderID = orders.parentOrder) 	
                                LEFT JOIN files ON (files.ref = reviews.reviewID AND files.table = 'reviews') 	
                            WHERE papa.orderID = " . $order["orderID"];
					$reviews = udb::single_list($que);
					if(!$reviews) {
						$reviewBody = urlencode("שלום ".$order['customerName'].". לאחר שהותך  ב".$siteData['siteName'].", נשמח לקבל את חוות דעתך. למילוי חוות הדעת:  https://bizonline.co.il/review.php?guid=".$order["guid"]);
						$reviewSubjsct = "מילוי חוות דעת ".$order["siteName"];
					?>
					<div class="signOpt inOrder">
						<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:34px">

						חוות דעת
							<span style="font-size: 14px; line-height: 1; width: 104px; display: inline-block; padding: 3px 10px; font-weight: normal;">
								<?if(strtotime(date("Y-m-d h:i:s")) <= strtotime($order['showTimeFrom'][0] == 0 ? $lastTreat : $order['showTimeFrom'])){?>
									<?php if($enable_review['sendReviews']) { ?>
										תשלח למילוי ביום עזיבה
									<?php } else { ?>
										בסיום הטיפול, ניתן יהיה לשלוח בקשה למילוי
									<?php } ?>
								<?}else if($order['SentReview'] || $order['review_mail_sent']){?><b style="color:green">נשלחה בקשה למילוי</b><?}?>
							</span>
						</div>
						<?if(strtotime(date("Y-m-d h:i:s")) <= strtotime($order['showTimeFrom'][0] == 0 ? $lastTreat : $order['showTimeFrom'])){?>
							<?/*php if($enable_review['sendReviews']) {
							?>
							<label class="switch" style="float:left" onclick="swal.fire({icon: 'error',title: 'שינוי הגדרת שליחת חוות דעת ביום עזיבה מתבצע דרך תפריט הגדרות - חוות דעת'});">
							  <input type="checkbox" name="allowReview" value="1"  class="ignore" <?=$siteData["sendReviews"]? "checked" : ""?> onclick="return false;">
							  <span class="slider round"></span>
							</label>
							<?php } */?>
						<?}else{?>
						<div style="float:left;margin:-5px -5px -5px 0">
						<?php if($order['customerPhone']){
							$phoneClean = str_replace("-","",$order['customerPhone']);
							?>
							<a href="<?whatsappBuild($order['customerPhone'],$reviewBody)?>" target="_blank"><span class="icon whatsapp" data-phone="<?=$phoneClean?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span></a>

							<a href="sms:<?=$phoneClean?>?&body=<?=$reviewBody?>" target="_blank"  data-phone="<?=$phoneClean?>" data-msg="<?=htmlspecialchars(urldecode($reviewBody), ENT_COMPAT)?>" data-sid="<?=$siteID?>"  class="a_sms"><span class="icon sms" data-sms="<?=$phoneClean?>">
								<img src="/user/assets/img/icon_sms.png" alt="sms">
							</span></a>
						<?php } ?>
						<?php if($order['customerEmail']){?>
                            <a href="#" onclick="reviewInvite(<?=$orderID?>)"><span class="icon mail" data-mail="<?=$order['customerEmail']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" width="32" height="27"><style>.message-ic{fill:#fff}</style><path class="message-ic" d="M29.9 25L24.1 19.4 23.5 18.8 23 18.3 22.6 17.8 22.1 17.4 24.9 15.5 29.8 12 30 11.9 30 14 30 17.2 30 25 29.9 25ZM22.2 6L5 10.9 5.7 13.3 2.1 10.7 2.8 7.8C4.1 7 12.5 2.1 15.9 2 19.3 2.1 27.7 7 29 7.8L29.9 10.7 24.7 14.5 22.2 6ZM6.2 14.8L9.7 17.4 9 18.1 8.5 18.6 8.1 19.1 7.5 19.6 2.1 25 2 25 2 13.1 2 12 2 11.9 2.7 12.4 6.2 14.8ZM9.5 19L10 18.5 10.4 18.1 10.8 17.7 11.2 17.2 11.3 17.2C11.4 17.1 11.6 16.9 11.8 16.8 12.9 15.8 14.4 15.3 15.9 15.3 17.4 15.3 18.9 15.8 20 16.8 20.2 16.9 20.3 17 20.5 17.2L20.6 17.3 21.1 17.7 21.2 17.9 21.7 18.3 22.2 18.8 23 19.7 28.4 25 3.5 25 7.8 20.7 9.5 19Z"></path></svg></span></a>

						<?php
						}?>
						<!-- span class="icon plusSend" onclick='$("#sendPopMsg").val($(this).data("msg"));$("#sendPopSubject").val($(this).data("subject"));$(".sendPop").fadeIn("fast");$("#SendPopTitle").text($(this).data("title"));' data-msg="<?=$reviewBody?>" data-title="שליחת חוות דעת" data-subject="<?=$reviewSubject?>"></span -->
						</div>
						<?}?>
					</div>

<?php
					} else {
					    foreach($reviews as $review){
?>
						<div class="review">
							<div class="userData">
								<div class="topData"><?=date("d.m.y h:i",strtotime($review['created']));?> <?=(count($_CURRENT_USER->sites()) > 1)? $sname[$review["siteID"]] : ""?></div>
								<b><?=$review['name']?></b> <span><b><?=$review['avgScore']?></b>/5</span>
							</div>
							<div class="reviewText">
								<?if($review["document"]){?><div class="showOrder" onclick="openDoc('<?=$review["document"]?>')">הצג אסמכתא</div><?}?>
								<div class="revtitle"><?=$review['title']?></div>
								<div class="text"><?=nl2br($review['text'])?></div>
							</div>
						</div>
<?php
                        }
                    }
                }
}
$_timer->log();
?>

					<style>

					</style>
					<div class="signOpt inOrder">	
						<?php
							$sms_sent = udb::full_list("SELECT * FROM orders_sms WHERE orderID=".$orderID);
						?>
						<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:<?=$sms_sent?"1":"34px"?>">שליחת הודעת SMS
						<?php if($sms_sent) { ?>
							<div onclick="$('.sent-sms-list[data-orderid=\'<?=$orderID?>\']').fadeToggle('fast')" style="text-decoration:underline;font-weight:normal;font-size:14px;cursor:pointer;">
								<?=count($sms_sent) == 1?"נשלחה הודעה אחת- לחצו לצפייה":"נשלחו ".count($sms_sent)." הודעות- לחצו לצפייה"?>
							</div>
						<?php } ?>
						</div>
						<div onclick="$('.sms-pop[data-orderid=\'<?=$orderID?>\']').fadeIn('fast')" style="font-size: 16px;font-weight: bold;color: #FFF;display: inline-block;cursor:pointer;line-height: 34px;background: #0dabb6;padding: 0 10px;border-radius: 6px;">שלח הודעה</div>
					</div>
					<?php if($sms_sent) { ?>
						<div class="sent-sms-list"  data-orderid="<?=$orderID?>" style="display:none">
							<?php foreach($sms_sent as $sms) { ?>
								<div class="sent-sms">
									<div class="date">תאריך שליחה: <strong><?=$sms['sendTime']?></strong></div>
									<div class="con">
										<?=$sms['sms_con']?>
									</div>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
					<div class="popup sms-pop" data-orderid="<?=$orderID?>" style="display:none;">
						<div class="pop_cont">
							<div class="close" onclick="$('.sms-pop').fadeOut('fast')">&times;</div>
							<div class="title">שליחת הודעת SMS</div>						
							<textarea data-orderid="<?=$orderID?>" name="smscon" maxlength="80"></textarea>
							<div class="limit">הגבלת תווים <span><span class="len">0</span>/<span class="of">80</span></span></div>
							<div class="send" data-orderid="<?=$orderID?>">שליחה</div>
						</div>
					</div>

					<script>
                        $(function(){
                            //$(document).off('click.ajaxFrom').on('change keyup', 'textarea[name="smscon"]', function() {
                            $('textarea[name="smscon"]').on('change keyup', function() {
                                let _len = $(this).val().length;
                                $(this).parent().find('.limit .len').html(_len);
                            });

                            //$(document).on('click', '.sms-pop .send', function() {
                            $('.sms-pop .send').on('click', function() {
                                let _oid = $(this).attr('data-orderid');
                                let _con = $(this).closest('.sms-pop').find('textarea').val();

                                $.post('ajax_spaSMS.php', {orderID: _oid, sms_con: _con}, function(res) {
                                    if(res.error)
                                        Swal.fire({icon:'error', text:res.error});
                                    else Swal.fire({icon:'success', text:res.msg});
                                })
                            })
                        });
					</script>
				</div>
				<?}else{?>
					<input type="hidden"  name="status" value="1" >
					<input type="hidden"  name="allowReview" value="<?=$siteData['sendReviews']?>" >
				<?}?>
				<div></div>

				<div class="inputWrap half" style="z-index:20">
					<input type="text" name="name" id="name" value="<?=$order['customerName']?>" class="ac-inp" />
					<label for="name">שם המזמין</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
				</div>
				<div class="inputWrap half tZehoot orderOnly" style="z-index:15">
					<input type="text" name="tZehoot" id="tZehoot" inputmode="numeric" value="<?=$order['customerTZ']?>" class="ac-inp" />
					<label for="tZehoot">תעודת זהות</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
				</div>
				<div class="inputWrap half" style="z-index:10">
					<input type="text" name="phone" id="phone" value="<?=$order['customerPhone']?>" class="ac-inp" />
					<label for="phone">טלפון</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
				</div>

				<div class="inputWrap half orderOnly">
					<input type="text" name="phone2" id="phone2" value="<?=$order['customerPhone2']?>">
					<label for="phone2">טלפון נוסף</label>
				</div>

				<div class="inputWrap half email orderOnly" style="z-index:5">
					<input type="text" name="email" id="email" value="<?=$order['customerEmail']?>" class="ac-inp" />
					<label for="email">אימייל</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
				</div>
				<div class="inputWrap half select orderOnly">
					<select name="reason" id="reason">
						<option value="0">-</option>
						<?php foreach($reasons as $reason) { ?>
						<option value="<?=$reason['mainPageID']?>" <?=$order['reason']==$reason['mainPageID']?"selected":""?>><?=$reason['mainPageTitle']?></option>
						<?php } ?>

					</select>
					<label for="reason">סיבת הגעה</label>
				</div>
				<?//print_r($siteData);?>
				<div id="orgAdress" style="<?=$siteData['addressRequired']? "" : "display:none"?>">
					<div class="inputWrap half orderOnly">
						<input type="hidden" name="settlementID" value="<?=$order['settlementID']?>"  class="hide_next <?=($siteData['addressRequired']==2 && !$orderID)? "required" : ""?> <?=$order['settlementID']? "valid" : ""?>">
						<div class="settlementName"><?=$order['clientCity']?></div>
						<input  type="text"  class="ac-inp2" name="clientCity" id="clientCity" value="<?=$order['clientCity']?>">
						<label for="clientCity">עיר</label>
						<div class="autoBox"><div class="autoComplete"></div></div>
					</div>
					<div class="inputWrap half orderOnly">
						<input type="text" name="clientAddress" id="clientAddress" class="<?=($siteData['addressRequired']==2 && !$orderID)? "required" : ""?>" value="<?=$order['customerAddress']?>">
						<label for="clientAddress">רחוב ומספר</label>
					</div>
				</div>
					<?php
					if($multiCompound){
						$sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
				?>
								<div style="background: #4fc2ca;padding-top: 10px;margin-bottom:10px">
								<div class="inputWrap half select ">
									<select name="orderSite" id="orderSite" <?=($order['siteID'] ? "readonly disabled" : "")?>>
										<option value="0">יש לבחור מתחם להזמנה</option>
				<?php
						foreach($sname as $id => $name)
							echo '<option value="' , $id , '" ' , ($id == $siteID ? 'selected' : '') , ' data-agree="' , $default[$id] , '">' , $name , '</option>';
				?>
									</select>
									<label for="orderSite">מתחם נבחר</label>
								</div>
								</div>
				<?php
					}else{
				?>
				<input type="hidden" name="orderSite" value="<?=$_CURRENT_USER->active_site()?>">
				<?}?>
				<div id="spa_orders_wrap">
					<div class="spa_orders_title">
						<?=$treatments? count($treatments) : "לא הוגדרו"?> 
						טיפולים
					</div>
					<div id="spa_orders">
<?php
    if ($treatments){
        foreach($treatments as $treat){

			$health_declare = udb::single_row("SELECT * FROM health_declare WHERE orderID=".$treat['orderID']);

?>
                        <div style="display:none"><?//print_r($treat)?></div>
						<div class="spaorder" id="spaorder<?=$treat['orderID']?>" data-id="<?=$treat['orderID']?>" data-parent="<?=$treat['parentOrder']?>" data-price="<?=$treat['price']?>">
                            <div class="delete" onclick="deleteTreatment(this)"></div>
                            <div class="spasect">
                                <b><?=$treat['customerName']?><?=$health_declare?"<style>#spa_orders .spaorder .spasect .V {display: inline-block;position: relative;width: 10px;height: 10px;}#spa_orders .spaorder .spasect .V::before {content: '';position: absolute;top: 50%;right: 5px;border-bottom: 2px solid #0dabb6;border-left: 2px solid #0dabb6;width: 10px;height: 3px;transform: rotate(-45deg);}</style><span class=\"V\"></span>":""?></b>
								<?php
								?>
								<span><?=$treat['treatmentName']?> <span style="font-size:12px;vertical-align:middle;"><?=$treat['treatmentLen']?$treat['treatmentLen']." דק'":""?></span></span>
                            </div>
                            <div class="spasect">
                                <span><?=($treat['startDate'] ? db2date($treat['startDate'], '/') : '<i>(אין תאריך)</i>')?></span>
								<?php
									$endTimeT = strtotime("+".$treat['treatmentLen']." minutes", strtotime($treat['startTime']));
									$endTimeT = date("H:i", $endTimeT);
								?>
                                <span><?=($treat['startTime'] ? $endTimeT . ' - ' . $treat['startTime'] : '')?></span>
                            </div>
							<?$lockedTherapist = $treat['lockedTherapist']? "<img src='/user/assets/img/lock.svg' style='width:17px;height:14px;opacity:0.7'> " : ""?>
                            <div class="spasect">
                                <span class="null"><?=$lockedTherapist?><?=($treat['therapistID'] ? $treat['masterName'] : 'לא נבחר מטפל')?></span>
                                <span class="null"><?=($treat['roomName'] ? $treat['roomName'] : 'לא נבחר חדר')?></span>
                            </div>
                            <div class="spasect">₪<?=$treat['price']?></div>
                            <div class="edit" onclick="openSpaSingle(<?=$treat['orderID']?>)"></div>
							<div class="duplicate">שכפל<br>טיפול</div>
                        </div>
<?php
			//Calc Default Stay in Room Time
            // edit:2022-11-06 - Not only was it binding, and not "default", it's also not timezone safe. Need to rewrite. - Sergey
            if (!$startDate || !strcmp($startDate, '00/00/0000'))
                if(strtotime($startDate) <= strtotime($treat['startDate'])){
                    if(intval(preg_replace('~\D~', '', $startTime)) < intval(preg_replace('~\D~','',$treat['startTime'])) || strtotime($startDate) < strtotime($treat['startDate'])){
                        $startTime = $treat['startTime'];
                        $endTime = (intval(substr($startTime,0,2))+2).":".substr($startTime,3,2);
                    }

                    $startDate = $treat['startDate'];
                    $endDate = db2date($startDate);

                }

		}
		$startDate = db2date($startDate);
    }
$_timer->log();	
	$dateOnly = (!$treatments && $startDate && $orderID && !$units)? 1 : 0;
?>
                    </div>
					<div id="add_spa" class="add_button" onclick="<?=($orderID ? 'openSpaSingle(0,' . $orderID . ')' : 'insertTreatmentNew()')?>">הוסף טיפול</div>
				</div>
				<div style="display:block;overflow:hidden">
					<div id="add_order" class="add_date">
						<input id="add_order_button" class="add_date_button" name="add_order" type="checkbox" value="1" <?=($units ? "checked" : "")?> onchange="if($(this).is(':checked')){$('#addroom').addClass('active').removeClass('dateonly');$('#add_date_button').prop( 'checked', false)}else{$('#addroom').removeClass('active')}">
						<label for="add_order_button">שהות בחדר</label>
					</div>
					<?if(!$treatments){?>
					<div id="add_date" class="add_date">
						<input id="add_date_button" class="add_date_button" name="add_date" type="checkbox" value="1" <?=($dateOnly ? "checked" : "")?> onchange="if($(this).is(':checked')){$('#addroom').addClass('active dateonly');;$('#add_order_button').prop( 'checked', false)}else{$('#addroom').removeClass('active').removeClass('dateonly')}">
						<label for="add_date_button">תאריך להזמנה כשאין טיפולים</label>
					</div>
					<?}?>
					<div id="addroom" class="<?=($units? "active" : "")?> <?=($dateOnly? "active dateonly" : "")?>">
						<div class="inputWrap date four">
							<input type="text" value="<?=($startDate )?>" name="fromDate" class="datePick fromDate" readonly>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23" width="23" height="23"><path class="shp0" d="M12 16.1C12 16.9 12.7 17.6 13.6 17.6L15.4 17.6C16.2 17.6 16.9 16.9 16.9 16.1L16.9 14.2C16.9 13.4 16.2 12.7 15.4 12.7L13.6 12.7C12.7 12.7 12 13.4 12 14.2L12 16.1ZM13.6 14.2L15.4 14.2 15.4 16.1C15.4 16.1 15.4 16.1 15.4 16.1L13.6 16.1 13.6 14.2ZM16.2 9.3C16.6 9.3 16.9 9.7 16.9 10.1 16.9 10.5 16.6 10.9 16.2 10.9 15.7 10.9 15.4 10.5 15.4 10.1 15.4 9.7 15.7 9.3 16.2 9.3ZM12.8 9.3C13.2 9.3 13.6 9.7 13.6 10.1 13.6 10.5 13.2 10.9 12.8 10.9 12.4 10.9 12 10.5 12 10.1 12 9.7 12.4 9.3 12.8 9.3ZM20.3 15.6C20.7 15.6 21.1 15.3 21.1 14.8L21.1 6.6C21.1 4.9 19.7 3.5 18 3.5L16.9 3.5 16.9 2.7C16.9 2.3 16.6 2 16.2 2 15.7 2 15.4 2.3 15.4 2.7L15.4 3.5 11.9 3.5 11.9 2.7C11.9 2.3 11.5 2 11.1 2 10.7 2 10.3 2.3 10.3 2.7L10.3 3.5 6.8 3.5 6.8 2.7C6.8 2.3 6.5 2 6.1 2 5.6 2 5.3 2.3 5.3 2.7L5.3 3.5 4.3 3.5C2.6 3.5 1.2 4.9 1.2 6.6L1.2 18.7C1.2 20.4 2.6 21.8 4.3 21.8L18 21.8C19.7 21.8 21.1 20.4 21.1 18.7 21.1 18.3 20.7 17.9 20.3 17.9 19.9 17.9 19.5 18.3 19.5 18.7 19.5 19.6 18.8 20.3 18 20.3L4.3 20.3C3.5 20.3 2.8 19.6 2.8 18.7L2.8 6.6C2.8 5.8 3.5 5.1 4.3 5.1L5.3 5.1 5.3 5.8C5.3 6.3 5.6 6.6 6.1 6.6 6.5 6.6 6.8 6.3 6.8 5.8L6.8 5.1 10.3 5.1 10.3 5.8C10.3 6.3 10.7 6.6 11.1 6.6 11.5 6.6 11.9 6.3 11.9 5.8L11.9 5.1 15.4 5.1 15.4 5.8C15.4 6.3 15.7 6.6 16.2 6.6 16.6 6.6 16.9 6.3 16.9 5.8L16.9 5.1 18 5.1C18.8 5.1 19.5 5.8 19.5 6.6L19.5 14.8C19.5 15.3 19.9 15.6 20.3 15.6ZM6.1 16.1C6.5 16.1 6.8 16.4 6.8 16.8 6.8 17.3 6.5 17.6 6.1 17.6 5.6 17.6 5.3 17.3 5.3 16.8 5.3 16.4 5.6 16.1 6.1 16.1ZM6.1 9.3C6.5 9.3 6.8 9.7 6.8 10.1 6.8 10.5 6.5 10.9 6.1 10.9 5.6 10.9 5.3 10.5 5.3 10.1 5.3 9.7 5.6 9.3 6.1 9.3ZM6.1 12.7C6.5 12.7 6.8 13 6.8 13.5 6.8 13.9 6.5 14.2 6.1 14.2 5.6 14.2 5.3 13.9 5.3 13.5 5.3 13 5.6 12.7 6.1 12.7ZM9.4 12.7C9.9 12.7 10.2 13 10.2 13.5 10.2 13.9 9.9 14.2 9.4 14.2 9 14.2 8.6 13.9 8.6 13.5 8.6 13 9 12.7 9.4 12.7ZM9.4 9.3C9.9 9.3 10.2 9.7 10.2 10.1 10.2 10.5 9.9 10.9 9.4 10.9 9 10.9 8.6 10.5 8.6 10.1 8.6 9.7 9 9.3 9.4 9.3ZM9.4 16.1C9.9 16.1 10.2 16.4 10.2 16.8 10.2 17.3 9.9 17.6 9.4 17.6 9 17.6 8.6 17.3 8.6 16.8 8.6 16.4 9 16.1 9.4 16.1Z"></path></svg>
							<label for="from">מתאריך</label>
						</div>
						<div class="inputWrap date four time">
							<input type="text" value="<?=(substr($startTime,0,5))?>" name="startTime" class="timePicks readonlymob" >
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>
							<label for="from">שעת כניסה</label>
						</div>
						<div class="inputWrap date four hide-dateonly">
							<input type="text" value="<?=$endDate?>" name="endDate" class="datePick endDate" readonly>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23" width="23" height="23"><path class="shp0" d="M12 16.1C12 16.9 12.7 17.6 13.6 17.6L15.4 17.6C16.2 17.6 16.9 16.9 16.9 16.1L16.9 14.2C16.9 13.4 16.2 12.7 15.4 12.7L13.6 12.7C12.7 12.7 12 13.4 12 14.2L12 16.1ZM13.6 14.2L15.4 14.2 15.4 16.1C15.4 16.1 15.4 16.1 15.4 16.1L13.6 16.1 13.6 14.2ZM16.2 9.3C16.6 9.3 16.9 9.7 16.9 10.1 16.9 10.5 16.6 10.9 16.2 10.9 15.7 10.9 15.4 10.5 15.4 10.1 15.4 9.7 15.7 9.3 16.2 9.3ZM12.8 9.3C13.2 9.3 13.6 9.7 13.6 10.1 13.6 10.5 13.2 10.9 12.8 10.9 12.4 10.9 12 10.5 12 10.1 12 9.7 12.4 9.3 12.8 9.3ZM20.3 15.6C20.7 15.6 21.1 15.3 21.1 14.8L21.1 6.6C21.1 4.9 19.7 3.5 18 3.5L16.9 3.5 16.9 2.7C16.9 2.3 16.6 2 16.2 2 15.7 2 15.4 2.3 15.4 2.7L15.4 3.5 11.9 3.5 11.9 2.7C11.9 2.3 11.5 2 11.1 2 10.7 2 10.3 2.3 10.3 2.7L10.3 3.5 6.8 3.5 6.8 2.7C6.8 2.3 6.5 2 6.1 2 5.6 2 5.3 2.3 5.3 2.7L5.3 3.5 4.3 3.5C2.6 3.5 1.2 4.9 1.2 6.6L1.2 18.7C1.2 20.4 2.6 21.8 4.3 21.8L18 21.8C19.7 21.8 21.1 20.4 21.1 18.7 21.1 18.3 20.7 17.9 20.3 17.9 19.9 17.9 19.5 18.3 19.5 18.7 19.5 19.6 18.8 20.3 18 20.3L4.3 20.3C3.5 20.3 2.8 19.6 2.8 18.7L2.8 6.6C2.8 5.8 3.5 5.1 4.3 5.1L5.3 5.1 5.3 5.8C5.3 6.3 5.6 6.6 6.1 6.6 6.5 6.6 6.8 6.3 6.8 5.8L6.8 5.1 10.3 5.1 10.3 5.8C10.3 6.3 10.7 6.6 11.1 6.6 11.5 6.6 11.9 6.3 11.9 5.8L11.9 5.1 15.4 5.1 15.4 5.8C15.4 6.3 15.7 6.6 16.2 6.6 16.6 6.6 16.9 6.3 16.9 5.8L16.9 5.1 18 5.1C18.8 5.1 19.5 5.8 19.5 6.6L19.5 14.8C19.5 15.3 19.9 15.6 20.3 15.6ZM6.1 16.1C6.5 16.1 6.8 16.4 6.8 16.8 6.8 17.3 6.5 17.6 6.1 17.6 5.6 17.6 5.3 17.3 5.3 16.8 5.3 16.4 5.6 16.1 6.1 16.1ZM6.1 9.3C6.5 9.3 6.8 9.7 6.8 10.1 6.8 10.5 6.5 10.9 6.1 10.9 5.6 10.9 5.3 10.5 5.3 10.1 5.3 9.7 5.6 9.3 6.1 9.3ZM6.1 12.7C6.5 12.7 6.8 13 6.8 13.5 6.8 13.9 6.5 14.2 6.1 14.2 5.6 14.2 5.3 13.9 5.3 13.5 5.3 13 5.6 12.7 6.1 12.7ZM9.4 12.7C9.9 12.7 10.2 13 10.2 13.5 10.2 13.9 9.9 14.2 9.4 14.2 9 14.2 8.6 13.9 8.6 13.5 8.6 13 9 12.7 9.4 12.7ZM9.4 9.3C9.9 9.3 10.2 9.7 10.2 10.1 10.2 10.5 9.9 10.9 9.4 10.9 9 10.9 8.6 10.5 8.6 10.1 8.6 9.7 9 9.3 9.4 9.3ZM9.4 16.1C9.9 16.1 10.2 16.4 10.2 16.8 10.2 17.3 9.9 17.6 9.4 17.6 9 17.6 8.6 17.3 8.6 16.8 8.6 16.4 9 16.1 9.4 16.1Z"></path></svg>
							<label for="from">עד תאריך</label>
						</div>
						<div class="inputWrap date four time hide-dateonly">
							<input type="text" value="<?=(substr($endTime,0,5))?>" name="endTime" class="timePicks readonlymob" >
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>
							<label for="from">שעת עזיבה</label>
						</div>

						<div class="mutltiRooms" style="<?=($multiCompound && !$order['siteID'])? "display:none" : ""?>">
							<div class="rooms">
		<?php
			foreach($rooms as $room) {
				$pepole = $room['maxAdults']?$room['maxAdults']:$room['maxGuests'];
				$kids = $room['maxKids']?$room['maxKids']:($order['adults']?$room['maxGuests']-$order['adults']:0);
				$unit = $units[$room['unitID']];
		?>
								<div class="roomSelectWrap siteID<?=$room['siteID']?>" data-unitid="<?=$room['unitID']?>" data-adults="<?=$room['maxAdults']?>" data-kids="<?=$room['maxKids']?>" data-maxguests="<?=$room['maxGuests']?>">
									<input type="checkbox" id="room<?=$room['unitID']?>" name="unitID[]" value="<?=$room['unitID']?>" class="ignore unit-id" <?=($unit?"checked":"")?>>
									<div class="room">
									<label for="room<?=$room['unitID']?>">
										<div class="title"><?=$room['unitName']?></div>
									</label>
										<div class="l">
											<div class="dataInp adults">
												<label for="adults_room<?=$room['unitID']?>">מבוגרים</label>
												<select name="adults_room[<?=$room['unitID']?>]" class="adults_room">
													<?php for($i=1;$i<=$pepole;$i++) { ?>
													<option value="<?=$i?>" <?=(($unit['adults'] == $i) ?"selected": ($i==2?"selected":""))?>><?=$i?></option>
													<?php } ?>
												</select>
											</div>
											<div class="dataInp kids"  style="display:none">
												<label for="kids_room<?=$room['unitID']?>">ילדים</label>
												<select name="kids_room[<?=$room['unitID']?>]" class="kids_room" <?=$order['kids']?>>
													<option value="0">0</option>
													<?php for($i=1;$i<=$kids;$i++) { ?>
													  <option value="<?=$i?>" <?=$unit['kids']==$i?"selected":""?>><?=$i?></option>
													<?php } ?>
												</select>
											</div>
											<div class="dataInp babies" style="display:none">
												<label for="babies_room<?=$room['unitID']?>">תינוקות</label>
												<select name="babies_room[<?=$room['unitID']?>]" class="babies_room">
													<option value="0">0</option>
													<?php for($i=1;$i<=$kids;$i++) { ?>
														<option value="<?=$i?>" <?=$unit['babies']==$i?"selected":""?>><?=$i?></option>
													<?php } ?>
												</select>
											</div>
											<div class="payments" style="display:none">
												<div class="meals">
													<select name="meal[<?=$room['unitID']?>]">
														<option value="0">ללא ארוחת בוקר</option>
														<option value="1" <?=($unit['breakfast'] ? 'selected' : '')?>>כולל ארוחת בוקר</option>
													</select>
												</div>
												<div class="dataInp">
													<label>סכום לתשלום</label>
													<input name="payment[<?=$room['unitID']?>]" class="payment-inp" value="<?=($unit['base_price'] ?: '')?>" type="number" />
												</div>
												<?/*
												<div class="dataInp">
													<label>מקדמה ששולמה</label>
													<input readonly name="adv_payment[<?=$room['unitID']?>]" class="prePayment-inp" value="<?=($unit['advance'] ?: '')?>" type="number" />
												</div>
												*/?>
											</div>
										</div>
									</div>
								</div>
		<?php
			}
		?>
							</div>
		<?php
			$selectedForm = $order['form_to_sign'] ?: $siteData['defaultAgr'];
		?>
							<div class="inputWrap select orderOnly">
								<label for="form_to_sign">טופס לחתימה</label>
								<select name="form_to_sign" id="form_to_sign">
									<option value="1" <?=$selectedForm==1?"selected":""?>>הסכם 1</option>
									<option value="2" <?=$selectedForm==2?"selected":""?>>הסכם 2</option>
									<option value="3" <?=$selectedForm==3?"selected":""?>>הסכם 3</option>
									<option value="10" <?=$selectedForm==10?"selected":""?>>הסכם שכירות</option>
								</select>
							</div>


						</div>

					</div>
				</div>
<?php

	if ($extras){
        $ct = count($treatments);
		$que = "SELECT e.extraID, e.extraName FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
				WHERE s.siteID IN (" . $siteID . ") AND s.included = 0 AND s.active = 1 AND s.voucherprint = 1 ORDER BY e.showOrder";
		$extrasPrintVouchter = udb::key_value($que);

        $que = "SELECT IFNULL(b.isWeekend2, a.isWeekend2) AS `isWeekend2`
                FROM `sites_weekly_hours` AS `a` 
                    LEFT JOIN `sites_periods` AS `sp` ON (sp.siteID = a.siteID AND sp.periodType = 0 AND sp.dateFrom <= '" . $selectedDate . "' AND sp.dateTo >= '" . $selectedDate . "')
                    LEFT JOIN `sites_weekly_hours` AS `b` ON (b.siteID = a.siteID AND b.holidayID = -sp.periodID AND b.weekday = a.weekday AND b.active = 1)
                WHERE a.holidayID = 0 AND a.siteID = " . $siteID . " AND a.weekday = " . date('w', strtotime($selectedDate));
        $isWeekend2 = udb::single_value($que);


        foreach($extras as $sid => $eTypes){
            if ($ct && $eTypes['package']){
                $pin = min(3, max(1, $ct));
?>
				<div class="treatments_patients_addings addings closed siteID<?=$sid?>" <?=($multiCompound && !$order['siteID'])? 'style="display:none"' : ""?>>
					<div class="title">תוספות למקבלי טיפולים</div>
<?php
                foreach($eTypes['package'] as $extra){
                    $price = $orderExtras['extras'][$extra['extraID']]['price'] ?? (intval($extra['price' . $pin] ?: $extra['price' . ($pin - 1)]  ?: $extra['price1']) + ($isWeekend2 ? $extra['priceWE'] : 0));
                    $cnt   = $orderExtras['extras'][$extra['extraID']]['count'] ?? 1;
?>
					<div class="adding" <?=($extra['included'])? "style='opacity:0.6'" :"" ?>>
						<input type="checkbox" <?=$extra['included']? "onclick='return false;'" : ""?> class="extra" name="extra[]" id="extra<?=$extra['extraID']?>" <?=((isset($orderExtras['extras'][$extra['extraID']]) || ($extra['included'])) ? 'checked' : '')?> value="<?=$extra['extraID']?>" />

						<?php
						if(array_key_exists($extra['extraID'],$extrasPrintVouchter)){
							$printdate = $units?$startDate:date('d/m/Y', strtotime($treatments[0]['timeFrom']));
							if(!$printdate)
								$printdate = date('d/m/Y');
							if($units) {
								$print_roomName = '';
								foreach($rooms as $room) {
									if($units[$room['unitID']])$print_roomName = $room['unitName'];
									else continue;
								}
							}

?>
							<div class="print-icon" data-place="<?=$order['siteName']?>" data-date="<?=$printdate?>" data-time="<?=$units?date('H:i', strtotime($startDate)):date('H:i', strtotime($treatments[0]['timeFrom']))?>" data-person="<?=$order['customerName']?>" data-room="<?=$print_roomName?>"
							data-quantity="<?=$cnt?>" data-name="<?=htmlspecialchars($extra['extraName'])?>" data-title="<?=htmlspecialchars($extra['extraName'])?>"
							data-desc="<?=htmlspecialchars($extra['description'])?>"></div>
<?php
						}
?>

						<label for="extra<?=$extra['extraID']?>"><div><?=$extra['extraName']?><span><?=nl2br($extra['description'])?></span></div></label>
						<div class="l">
							<span class="unit_price">₪<?=$price?></span>
                            <input type="number"  <?=$extra['included']? "readonly" : ""?> class="count" inputmode="numeric" name="ecount[<?=$extra['extraID']?>]" value="<?=$cnt?>" min="1" title="" data-price="<?=$price?>" />
							<span class="price">₪<?=($price * $cnt)?></span>
						</div>
					</div>
<?php
                }
?>
                </div>
<?php
            }


            if ($eTypes['rooms']){
?>
				<div class="treatments_patients_addings addings closed siteID<?=$sid?>" <?=($multiCompound && !$order['siteID'])? 'style="display:none"' : ""?>>
					<div class="title">תוספי חדרים</div>
<?php
                foreach($eTypes['rooms'] as $extra){
                    $oe = $orderExtras['extras'][$extra['extraID']] ?? [];

                    if ($isWeekend2){
                        $extra['price1'] += $extra['priceWE'];
                        if ($extra['price2'])
                            $extra['price2'] += $extra['priceWE2'];
                        if ($extra['price3'])
                            $extra['price3'] += $extra['priceWE3'];
                    }

                    $options = [];
                    if ($extra['price1']){
                        $options[] = '<option data-price="' . (($oe && empty($oe['forNight']) && empty($oe['extraHours'])) ? $oe['price'] : $extra['price1']) . '" value="1">שהיה לטיפול ' . (ceil($extra['countMin']) ? round($extra['countMin'], 1) . ' שעות' : '') . '</option>';
                        if ($extra['price2'] && round($extra['countMax'], 1) > round($extra['countMin'], 1))
                            for($l = $extra['countMin'] + 1; $l <= $extra['countMax']; ++$l){
                                if ($l == (($oe['baseHours'] ?? 0) + ($oe['extraHours'] ?? 0)))
                                    $options[] = '<option data-price="' . $oe['price'] . '" value="' . $l . '" selected >שהיה לטיפול ' . $l . ' שעות</option>';
                                else
                                    $options[] = '<option data-price="' . ($extra['price1'] + $extra['price2'] * ($l - $extra['countMin'])) . '" value="' . $l . '">שהיה לטיפול ' . $l . ' שעות</option>';
                            }
                    }
                    if ($extra['price3'] || $oe['forNight'])
                        $options[] = '<option data-price="' . ($oe['forNight'] ? $oe['price'] : $extra['price3']) . '" value="99" ' . ($oe['forNight'] ? 'selected' : '') . '>לינה</option>';

                    // no-price protection - if there aren't ANY options for this room - set it as "zero-priced"
                    if (!count($options))
                        $options[] = '<option data-price="0" value="1">שהיה לטיפול</option>';
?>
					<div class="adding">
						<input type="checkbox" class="extra" name="extra[]" id="extra<?=$extra['extraID']?>" <?=(($oe || (!$orderID && $extra['included'])) ? 'checked' : '')?> value="<?=$extra['extraID']?>" />
						<label for="extra<?=$extra['extraID']?>"><?=$extra['extraName']?><div><?=nl2br($extra['description'])?></div></label>
						<div class="l">
                            <select class="count" name="ecount[<?=$extra['extraID']?>]" title="" <?//=(count($options) < 2 ? 'style="display:none"' : '')?>><?=implode('', $options)?></select>
							<span class="price">₪<?=($oe['price'] ?? $extra['price1'] ?: $extra['price3'])?></span>
						</div>
					</div>
<?
                }
?>
				</div>
<?php
            }


            foreach(['general' => 'תוספים כלליים', /*'rooms' => 'תוספי חדרים',*/ 'company' => 'תוספים מלווים','product' => 'מוצרים נלווים'] as $et => $en){
                if ($eTypes[$et]){
?>
				<div class="treatments_patients_addings addings closed <?=$et=="product"? "limit5" : ""?> siteID<?=$sid?>" <?=($multiCompound && !$order['siteID'])? 'style="display:none"' : ""?>>
					<div class="title"><?=$en?></div>
<?php
                    foreach($eTypes[$et] as $extra){
                        $price = $orderExtras['extras'][$extra['extraID']]['price'] ?? ($extra['price1'] + ($isWeekend2 ? $extra['priceWE'] : 0));
                        $cnt   = $orderExtras['extras'][$extra['extraID']]['count'] ?? 1;
?>
					<div class="adding">
						<input type="checkbox" class="extra" name="extra[]" id="extra<?=$extra['extraID']?>" <?=((isset($orderExtras['extras'][$extra['extraID']]) || (!$orderID && $extra['included'])) ? 'checked' : '')?> value="<?=$extra['extraID']?>" />
						<label for="extra<?=$extra['extraID']?>"><?=$extra['extraName']?><div><?=nl2br($extra['description'])?></div></label>
						<div class="l">
							<span class="unit_price">₪<?=$price?></span>
                            <input type="number" class="count" inputmode="numeric" name="ecount[<?=$extra['extraID']?>]" value="<?=$cnt?>" min="1" title="" data-price="<?=$price?>" />
							<span class="price">₪<?=($price * $cnt)?></span>
						</div>
					</div>
<?php
                    }
					
?>
					<?if($et=="product"){?>
					<div class="showhidemore" onclick="$(this).closest('.limit5').toggleClass('showall')"><span>הצג עוד</span><span>הצג פחות</span></div>
					<?}?>
				</div>
<?php
                }
            }
        }
    }

	$_timer->log();
?>

				
				<style>
					.percentage-disc { font-size: 18px; clear: both; display: flex; align-items: center; justify-content: center; } 
					.percentage-disc>.title { } 
					.percentage-disc .inputWrap { margin: 0 3px 0 10px; display: inline-block; width: 50px; }.applybutt { line-height: 50px; margin: 10px; background: #0dabb6; cursor: pointer; display: inline-block; font-size: 18px; color: white; border-radius: 5px; padding: 0 10px; }
					#discountWrap:not(.showText) .inputWrap{display:none}
					#discountWrap.showText .inputWrap{position:absolute;left:-5px;top:-1px;right:70px;height:130px;width:auto;z-index:2}
					
					@media(max-width:992px){
						#discountWrap.showText{margin-bottom:80px}
					}
				</style>
				<script>
					function setDiscountChange(elm){
						$('.disc span').html(Math.round(parseInt(elm.val())/100*$('#price_total').val()));
					}
					function setDiscountChange2(elm){
						if(parseInt(elm.val())>0){
							$('#discountWrap').addClass('showText');
						}else{
							$('#discountWrap').removeClass('showText');						
						}
					}
				</script>
				<div class="percentage-disc">
					<div class="title">אחוזי הנחה %</div>
                    <div class="inputWrap">
                        <input max="100" min="0" onchange="setDiscountChange($(this))"  onkeydown="setDiscountChange($(this))"   onkeyup="setDiscountChange($(this))" type="number" name="percentage_discount" id="percentage_discount" value="" />
                    </div>
					<div class="disc" style="display:inline-block;width:60px;text-align:center;">₪<span>0</span></div>
					<div class="applybutt" onclick="$('#price_discount').val(parseInt($('.disc span').html()));$('#price_discount').blur();setDiscountChange2($('#price_discount'))">הוסף הנחה</div>					
				</div>
				<div style="clear:both;margin-top:10px;padding-top:10px;border-top:1px #ccc solid">
                    <div class="inputWrap half orderOnly">
                        <input type="number" id="price_total" value="<?=($order['price'] + $order['discount'])?>" readonly />
                        <label for="price_total">מחיר מחירון</label>
                    </div>
                    <div class="inputWrap half orderOnly <?=$order['discount']? "showText" : ""?>" id="discountWrap">
                        <input type="number" name="price_discount" id="price_discount" value="<?=$order['discount']?>" onchange="setDiscountChange2($(this))"  onkeydown="setDiscountChange2($(this))"   onkeyup="setDiscountChange2($(this))" />
                        <label for="price_discount">הנחה</label>
						<div class="inputWrap textarea" style="">
							<textarea id="discountText" name="discountText"><?=$order['discountText']?></textarea>
							<label for="discountText">סיבת הנחה</label>
						</div>
                    </div>

                    <div class="inputWrap half orderOnly" style="float:right">
                        <input type="number" name="price_to_pay" id="price_to_pay" value="<?=$order['price']?>" />
                        <label for="price_to_pay">סכום לתשלום</label>
                    </div>
                    <div style="clear:both"></div>

                    <div class="inputWrap half orderOnly">
                        <input type="number" name="prePay" id="prePay" value="<?=$paid?>" readonly />
                        <label for="prePay">סה"כ שולם</label>
                    </div>
                    <div class="inputWrap half orderOnly">
                        <input type="text" name="leftPay" id="leftPay" value="" readonly />
                        <label for="leftPay">נותר לתשלום</label>
                    </div>

                    <!-- div class="inputWrap half orderOnly">
                        <input type="number" name="extraPrice" id="extraPrice" value="<?=$order['extraPrice']?>">
                        <label for="extraPrice">תוספת לאדם</label>
                    </div -->
<?php
$_timer->log();

    if ($order['onlineData']){
        $onlineData = json_decode($order['onlineData'], true);
        $oText = [];
        $payTimes = ['no-payment' => 'אין אמצעי תשלום', 'now' => ($order['sourceID'] == 'spaplus' ? 'שולם ב-SpaPlus' : 'שולם ב-Online'), 'on_arrive' => 'תשלום ביום ההגעה'];

        if ($onlineData['source'])
            $oText[] = 'מקור: ' . ucfirst($onlineData['source']);
        if ($onlineData['packCode'])
            $oText[] = 'קוד חבילה: ' . $onlineData['packCode'];
        if ($onlineData['payTime'])
            $oText[] = 'אופן תשלום: ' . ($payTimes[$onlineData['payTime']] ?? $onlineData['payTime']);
        if ($onlineData['bookID'])
            $oText[] = "מס' הזמנה חיצוני: " . $onlineData['bookID'];
        if ($onlineData['received'])
            $oText[] = "התקבלה: " . substr($onlineData['received'], 11, 5) . ' ' . db2date(substr($onlineData['received'], 0, 10), '.');

        if ($onlineData['payed'])
            $oText[] = (($onlineData['payTime'] == 'now') ? "שולם: " : "כרטיס לערבון: ") . substr($onlineData['payed'], 11, 5) . ' ' . db2date(substr($onlineData['payed'], 0, 10), '.');
        elseif ($onlineData['declined'])
            $oText[] = "בוטלה: " . substr($onlineData['declined'], 11, 5) . ' ' . db2date(substr($onlineData['declined'], 0, 10), '.');
        elseif ($onlineData['cancelled'])
            $oText[] = "בוטלה: " . substr($onlineData['cancelled'], 11, 5) . ' ' . db2date(substr($onlineData['cancelled'], 0, 10), '.');
        elseif ($onlineData['timeout'])
            $oText[] = "אזלה: " . substr($onlineData['timeout'], 11, 5) . ' ' . db2date(substr($onlineData['timeout'], 0, 10), '.');
?>
                    <div class="inputWrap textarea" style="background:rgb(40 218 231 / 20%)">
                        <textarea id="online_data" readonly><?=implode(PHP_EOL, $oText)?></textarea>
                        <label for="online_data">פרטי הזמנת אונליין</label>
                    </div>
<?php
        unset($oText, $onlineData);
    }
?>
                    <div class="inputWrap half textarea">
                        <textarea id="comments_customer" name="comments_customer"><?=$order['comments_customer']?></textarea>
                        <label for="comments_customer">הערות מזמין</label>
                    </div>
                    <div class="inputWrap half textarea">
                        <textarea id="comments_owner" name="comments_owner"><?=$order['comments_owner']?></textarea>
                        <label for="comments_owner">הערות המקום</label>
                    </div>
                    <div class="inputWrap textarea">
                        <textarea id="comments_payment" name="comments_payment"><?=$order['comments_payment']?></textarea>
                        <label for="comments_payment">תנאי תשלום</label>
                    </div>
				</div>
				<div class="statusBtn">
			<?php
					if ($orderID /*&& $siteData['hasTerminal']*/){
			?>
					<span class="orderPrice new <?=($paid >= $order['price'] ? 'paid' : '')?>" onclick="openPayAfterSave({orderID: <?=$orderID?>})">
						<i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg></i>
						<span>₪<?=number_format($order['price'])?><span>(₪<?=number_format($paid)?>)</span></span>
					</span>
			<?php
				}
			?>
					<button type="button" onclick="insertOrderSpa()" class="inputWrap submit">שמור<?=$order['signature']?" ובטל חתימה":""?></button>
					<!-- <div class="cancelOrderBtn">בטל הזמנה</div> -->
					<?if($orderID){?>
					<div style="margin-top:20px;margin-bottom:10px">
						<?if($order['status']){?>
						<div class="delOrderBtn" onclick="orderCancel(<?=$orderID?>)">
							<svg height="427pt" viewBox="-40 0 427 427.00131" width="427pt" xmlns="http://www.w3.org/2000/svg"><path d="m232.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m114.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m28.398438 127.121094v246.378906c0 14.5625 5.339843 28.238281 14.667968 38.050781 9.285156 9.839844 22.207032 15.425781 35.730469 15.449219h189.203125c13.527344-.023438 26.449219-5.609375 35.730469-15.449219 9.328125-9.8125 14.667969-23.488281 14.667969-38.050781v-246.378906c18.542968-4.921875 30.558593-22.835938 28.078124-41.863282-2.484374-19.023437-18.691406-33.253906-37.878906-33.257812h-51.199218v-12.5c.058593-10.511719-4.097657-20.605469-11.539063-28.03125-7.441406-7.421875-17.550781-11.5546875-28.0625-11.46875h-88.796875c-10.511719-.0859375-20.621094 4.046875-28.0625 11.46875-7.441406 7.425781-11.597656 17.519531-11.539062 28.03125v12.5h-51.199219c-19.1875.003906-35.394531 14.234375-37.878907 33.257812-2.480468 19.027344 9.535157 36.941407 28.078126 41.863282zm239.601562 279.878906h-189.203125c-17.097656 0-30.398437-14.6875-30.398437-33.5v-245.5h250v245.5c0 18.8125-13.300782 33.5-30.398438 33.5zm-158.601562-367.5c-.066407-5.207031 1.980468-10.21875 5.675781-13.894531 3.691406-3.675781 8.714843-5.695313 13.925781-5.605469h88.796875c5.210937-.089844 10.234375 1.929688 13.925781 5.605469 3.695313 3.671875 5.742188 8.6875 5.675782 13.894531v12.5h-128zm-71.199219 32.5h270.398437c9.941406 0 18 8.058594 18 18s-8.058594 18-18 18h-270.398437c-9.941407 0-18-8.058594-18-18s8.058593-18 18-18zm0 0"></path><path d="m173.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path></svg>
							בטל הזמנה
						</div>
						<?}else{?>
						<div class="delOrderBtn" onclick="orderDelete(<?=$orderID?>)">
							<svg height="427pt" viewBox="-40 0 427 427.00131" width="427pt" xmlns="http://www.w3.org/2000/svg"><path d="m232.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m114.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m28.398438 127.121094v246.378906c0 14.5625 5.339843 28.238281 14.667968 38.050781 9.285156 9.839844 22.207032 15.425781 35.730469 15.449219h189.203125c13.527344-.023438 26.449219-5.609375 35.730469-15.449219 9.328125-9.8125 14.667969-23.488281 14.667969-38.050781v-246.378906c18.542968-4.921875 30.558593-22.835938 28.078124-41.863282-2.484374-19.023437-18.691406-33.253906-37.878906-33.257812h-51.199218v-12.5c.058593-10.511719-4.097657-20.605469-11.539063-28.03125-7.441406-7.421875-17.550781-11.5546875-28.0625-11.46875h-88.796875c-10.511719-.0859375-20.621094 4.046875-28.0625 11.46875-7.441406 7.425781-11.597656 17.519531-11.539062 28.03125v12.5h-51.199219c-19.1875.003906-35.394531 14.234375-37.878907 33.257812-2.480468 19.027344 9.535157 36.941407 28.078126 41.863282zm239.601562 279.878906h-189.203125c-17.097656 0-30.398437-14.6875-30.398437-33.5v-245.5h250v245.5c0 18.8125-13.300782 33.5-30.398438 33.5zm-158.601562-367.5c-.066407-5.207031 1.980468-10.21875 5.675781-13.894531 3.691406-3.675781 8.714843-5.695313 13.925781-5.605469h88.796875c5.210937-.089844 10.234375 1.929688 13.925781 5.605469 3.695313 3.671875 5.742188 8.6875 5.675782 13.894531v12.5h-128zm-71.199219 32.5h270.398437c9.941406 0 18 8.058594 18 18s-8.058594 18-18 18h-270.398437c-9.941407 0-18-8.058594-18-18s8.058593-18 18-18zm0 0"></path><path d="m173.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path></svg>
							מחיקה מוחלטת
						</div>
						<div class="delOrderBtn" onclick="orderRestore(<?=$orderID?>)">
							<svg id="Capa_1" enable-background="new 0 0 497.883 497.883" height="512" viewBox="0 0 497.883 497.883" width="512" xmlns="http://www.w3.org/2000/svg"><path d="m435.647 155.588-62.235 93.353h31.118c0 85.786-69.802 155.588-155.588 155.588-52.788 0-99.368-26.561-127.511-66.883l-36.282 54.424c39.959 45.668 98.487 74.694 163.793 74.694 120.11 0 217.823-97.714 217.823-217.823h31.118z"></path><path d="m93.353 248.941c0-85.786 69.802-155.588 155.588-155.588 52.788 0 99.368 26.561 127.511 66.883l36.282-54.423c-39.959-45.668-98.487-74.694-163.793-74.694-120.11 0-217.823 97.714-217.823 217.823h-31.118l62.235 93.353 62.235-93.353z"></path></svg>
							שחזור הזמנה
						</div>
						<?}?>
					</div>
					<?}?>
				</div>
				<div class="signBtn" >שלח לחתימה</div>

<?php
    if ($orderID){
        $actions = UserActionLog::getLogForOrder($orderID);
?>
				<div class="order-actions">
<?php
        foreach($actions as $action){
            if (strcasecmp('order', substr($action['actionType'], 0, 5)))
                continue;

            $time = substr($action['created'], 11, 5) . ' ' . db2date(substr($action['created'], 0, 10));
?>
					<div class="action-line">
						<div class="action-type"><?=UserActionLog::actionName($action['actionType'])?></div>
						<div class="action-user"><?=$_CURRENT_USER->user($action['buserID'])?></div>
						<div class="action-time"><?=$time?></div>
					</div>
<?php
        }
?>
				</div>
<?php
    }
?>
			</form>


<div class="dup-pop" id="duplicate_treatment" style="display:none">
    <div class="dup-cont">
        <div class="close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div id="duplicateContent">
            <div class="title selectTitle">שכפול טיפול</div>
        </div>
        <div class="content" style="font-size:18px;padding-top:20px"></div>
        <div style="text-align:center;margin-top:20px;height: 0;top: -64px;margin-right: 91px;position: relative;"><button style="height:50px;padding:0 20px;color:white;border-radius:10px;background:#0dabb6;font-size:20px" class="submit">שכפל</button></div>
        <div style="margin: 10px; border-top: 2px solid gray;"></div>
        <div style="font-size:18px;" class="content2"></div>
        <div style="text-align:center;margin-top:20px"><button style="height:50px;padding:0 20px;color:white;border-radius:10px;background:#0dabb6;font-size:20px" class="submit2">שכפל</button></div>
    </div>
</div>


		</div>
		<input type="hidden" name="open_treatmentID" id="open_treatmentID" value="<?=$open_treatmentID?>">
	</div>

	<style>
	.pdfbtn {display: inline-block;vertical-align: middle;min-width: 120px;font-size: 16px;text-align: center;line-height: 40px;background: #e73219;color: #fff;font-weight: 500;cursor: pointer;border-radius: 3px;margin: 20px 0 0 0;padding: 0 10px;/* width: 40%; */}
    #addroom{display:none}
    #addroom.active{display:block;clear:both}
	#addroom.active.dateonly .mutltiRooms {display: none;}
	#addroom.active.dateonly .hide-dateonly{display:none}

	div#spa_orders_wrap {border: 1px #0dabb6 solid;padding: 20px 10px 10px;border-radius: 5px;text-align: right;position: relative;margin-top: 10px;}
	div#spa_orders_wrap .spa_orders_title {position: absolute;top: -10px;right: 10px;padding: 0 10px;background: #f5f5f5;font-size: 16px;font-weight: bold;}
	div#add_spa {cursor:pointer;height: 50px;background: #0dabb6;display: inline-block;font-size: 18px;line-height: 50px;color: white;border-radius: 5px;padding: 0 40px 0 20px;position: relative;}
    div#add_spa::before {position: absolute;content: "+";color: white;font-size: 20px;right: 7px;top: 0;bottom: 0;margin: auto;width: 26px;height: 26px;text-align: center;font-weight: bold;box-sizing: border-box;line-height: 20px;border: 2px white solid;border-radius: 50%;}

    div.add_date {line-height: 50px;margin: 10px;background: #0dabb6;display: inline-block;float: right;font-size: 18px;color: white;padding: 0;border-radius: 5px;}

    input.add_date_button {display: none;}
    .add_date label {padding: 0 40px 0 20px;cursor: pointer;position: relative;line-height: 1;display: flex;height: 50px;max-width: 110px;align-items:center;text-align:right}
    .add_date label::before {position: absolute;width: 30px;height: 30px;background: white;right: 5px;content: "";border-radius: 50%;top: 0;bottom: 0;margin: auto;}
    .add_date input:checked + label::after {position: absolute;width: 16px;height: 16px;background: black;right: 12px;content: "";border-radius: 50%;top: 0;bottom: 0;margin: auto;}

    .addings {border: 1px #0dabb6 solid;padding: 10px;border-radius: 5px;text-align: right;margin-top: 20px;display: block}
	.addings.closed{overflow:hidden;height:20px;}
    .addings>.title {font-weight: 500;font-size: 16px;padding: 10px;margin: -10px -10px 0;height: 20px;position:relative;cursor:pointer}
    .addings>.title::after{content:"";position:absolute;transform:rotate(45deg);width: 12px;height: 12px;left: 16px;border-left: 2px solid black;border-top: 2px solid black;top: 14px;}
    .addings.closed>.title::after{transform:rotate(-135deg);top:8px}
	.addings .addings_sum {position: absolute;left: 50px;}
	.addings>.adding {line-height: 30px;font-size: 18px;display: flex;margin-top: 5px;position:relative}
    .addings>.adding input ~ label {position: relative;padding-right: 40px;box-sizing: border-box;font-weight: 500;cursor: pointer;width: 60%;}
    .addings>.adding input ~ label::before {content: '';width: 30px;height: 30px;border: 1px solid #0dabb6;box-sizing: border-box;position: absolute;top: 50%;right: 0;background: #FFF;transform: translateY(-50%);}
    .addings>.adding input:checked ~ label::after {content: '';width: 7px;height: 14px;box-sizing: border-box;position: absolute;border-right: 3px solid #0dabb6;border-bottom: 3px solid #0dabb6;top: 50%;right: 11px;z-index: 1;transform: translateY(-50%) rotate(45deg);}
    .addings>.adding input ~ label span {display:block;font-size: 14px;font-weight: normal;line-height: 1;margin-top: -4px;}
	.addings>.adding>input {display: none;}
    .addings>.adding input ~ label ~ .l input {width: 50px;height: 30px;box-sizing: border-box;line-height: 30px;border: 1px solid #0dabb6;text-align: center;display:none;-webkit-appearance: none;appearance: none;}
    .addings>.adding input ~ label ~ .l select{width:120px;height:30px;border:1px solid #0dabb6;display:none}
    .addings>.adding input:checked ~ label ~ .l {display:block;}
    .addings>.adding input:checked ~ label ~ .l .price,.addings>.adding input:checked ~ label ~ .l input,.addings>.adding input:checked ~ label ~ .l select {display:inline-block}
    .addings>.adding input ~ label ~ .l {width: 40%;text-align: left;font-size: 0}
    .addings>.adding input ~ label ~ .l .unit_price {font-size: 18px;display: inline-block;min-width: 100px;padding-left: 20px;box-sizing: border-box;color:#ccc}
    .addings>.adding input ~ label ~ .l .price {min-width:70px;font-size: 18px;display: none;}	
	.addings>.adding input ~ label div{font-weight:normal;line-height:1;font-size:0.8em}
	.addings>.adding input ~ label div br{display:none}
	.addings>.adding .print-icon {display:none;width: 30px;float: right;height: 30px;margin-left: 5px;background: url(/user/assets/img/printer-4-48.png);margin-top: 5px;background-size: 80%;background-color: #0dabb6;background-position: center;background-repeat: no-repeat;border-radius: 3px;cursor: pointer;position: absolute;z-index: 9;right: 35px;}
    .addings>.adding input:checked ~ .print-icon{display:block}
	.addings>.adding input:checked ~ .print-icon ~ label{padding-right:70px}

	.addings>.showhidemore {font-size: 16px;margin: 5px 0;padding: 5px;cursor: pointer;display: none;}
	.addings.limit5>.adding:nth-child(7) ~ .showhidemore {display: block;}
	.addings.limit5:not(.showall)>.adding:nth-child(6) ~ .adding {display: none;}
	.addings.limit5:not(.showall) .showhidemore span:nth-child(2) {display: none;}
	.addings.limit5.showall .showhidemore span:nth-child(1) {display: none;}

	#spaOrderForm select > option.inactive {color:red}

	@media(max-width:700px){
		.addings>.adding input ~ label ~ .l .unit_price {font-size: 14px;min-width: auto;float: left;width: 40px;line-height: 1.2;padding-left: 0;}
		.addings>.adding input ~ label ~ .l input {margin-top: 6px;margin-right: 6px;float: right;}
		.addings>.adding input ~ label {font-size: 14px;line-height: 1;}
		.addings>.adding input ~ label  span {margin-top:0;font-size:12px}
		.addings>.adding {border-bottom: 1px #CCC solid;padding-bottom: 5px;min-height: 40px;align-items: center;}
		.addings>.adding input ~ label ~ .l .price {line-height: 1.2;float: left;min-width: 40%;}
		.addings>.adding input ~ label ~ .l {align-items: center;display: table-cell !important;}
	}
</style>
<?if($multiCompound){?>
<script>

$('#orderSite').change(function(){

	$(this).closest('form').find('.roomSelectWrap').hide();
	$(this).closest('form').find('.roomSelectWrap input[type=checkbox]').prop("checked", false );
	if(parseInt(this.value)>0){
		var room = '.roomSelectWrap.siteID'+this.value;
		$(this).closest('form').find('.mutltiRooms').show();
		$(this).closest('form').find(room).show();
	}else{
		$(this).closest('form').find('.mutltiRooms').hide();
	}

	var def = $(this.options[this.selectedIndex]).data('agree');
    $('#form_to_sign').val(def);
});
</script>
<?}?>
<?
$city_list = json_encode(udb::full_list("SELECT settlementID , TITLE AS clientCity FROM `settlements` WHERE 1"));
?>
<script>
    $('.addings>.title').on('click',function(){
		$(this).closest('.addings').toggleClass('closed');
	});

	/*$('.addings .price').on('DOMSubtreeModified', function(){
	  showSums();
	});
	
	function showSums(){
		$('.addings').each(function(){
			var _sum = 0;
			$(this).find('.adding input:checked ~ label ~ .l .price').each(function(){
				_sum += parseInt($(this).html().replace(/\D/g, ''));
			});
			$(this).find('.title .addings_sum').remove();
			$(this).find('.title').append('<span class="addings_sum">₪'+_sum+'</span>')
		})
	}*/

    showFoldedExtraSums();

	$('.pdfbtn').on('click', function() {
        open($(this).attr('data-print')).print();
    });

    $('.treatments_patients_addings .adding .l select.count').on('change', function(){
        $(this).siblings('.price').html('₪' + $(this.options[this.selectedIndex]).data('price'));
        showFoldedExtraSums();
        calcSum();
    });

    (typeof 'autoComplete' == 'function' ? Promise.resolve() : $.getScript('/user/assets/js/autoComplete.min.js')).then(function(){
        var cache = {tm: 0, cache: []};
		//debugger;
        function caller(str){			
			//debugger;
            if (str.length < 3)
                return Promise.resolve([]);

            return new Promise(function(res){
                var c = {text:str, res:res};

                cache.cache.push(c);
                if (cache.tm)
                    window.clearTimeout(cache.tm);

                cache.tm = window.setTimeout(function(){
                    var last = cache.cache.pop();

                    for(var i = 0; i < cache.cache.length; ++i)
                        cache.cache[i].res([]);

                    cache.tm = null;
                    cache.cache = [];

                    last.res($.get('ajax_client.php', 'act=clientInfo&sid=<?=($siteID ?: $_CURRENT_USER->active_site())?>&val=' + last.text).then(res => res.clients));
                }, 500);
            });
        }

		
		$('.print-icon').click(function() {
			printExtra($(this));
		});

        $('.ac-inp').each(function(){
            var inp = this;

            const autoCompleteJS = new autoComplete({
                selector: '#' + inp.id,
                data: {
                    src: caller,
                    cache: false,
                    keys: ['_text', 'email']
                },
                resultsList: {
                    maxResults: 20
                },
                resultItem: {
                    element: function(item, data){
                        item.setAttribute("data-auto", JSON.stringify(data.value));
                    },
                    highlight: {
                        render: true
                    }
                },
                events: {
                    list: {
                        click: function(e){
                            var li = e.target.nodeName.toUpperCase() == 'LI' ? e.target : $(e.target).closest('li').get(0), data = JSON.parse(li.dataset.auto || '{}'),
                                    form = document.getElementById('orderForm'), el;

                            Object.keys(data).forEach(function(key){
                                if (data[key] && (el = form.querySelector('input[name="' + key + '"]')))
                                    el.value = String(data[key]).trim();
                            });

                            this.setAttribute('hidden', '');
                        }
                    }
                }
            });
        });

		$('.ac-inp2').each(function(){
            var inp = this;
            const autoCompleteJS = new autoComplete({
                selector: '#' + inp.id,
                data: {
                    src: <?=$city_list?>,
                    cache: false,
                    keys: ['clientCity']
                },
                resultsList: {
                    maxResults: 20
                },
                resultItem: {
                    element: function(item, data){
                        item.setAttribute("data-auto", JSON.stringify(data.value));
                    },
                    highlight: {
                        render: true
                    }
                },
                events: {
					input: {
						focus: (event) => {		
							//debugger;
							searchval = ($(this).val()? $(this).val() : "*" );
							console.log(searchval);							
							autoCompleteJS.open();
							$(this).closest('.inputWrap').css('z-index','9')
						},
						blur: (event) => {	
							
							console.log("blur");							
							$(this).closest('.inputWrap').attr('style','')
													
							autoCompleteJS.close();
						}
					},
                    list: {
                        click: function(e){							
                            var li = e.target.nodeName.toUpperCase() == 'LI' ? e.target : $(e.target).closest('li').get(0), data = JSON.parse(li.dataset.auto || '{}'),
                                    form = document.getElementById('orderForm'), el;

                            Object.keys(data).forEach(function(key){
								
								if (data[key] && key=="settlementID" && (el = $('#orderForm input[name="settlementID"]'))){
                                    el.val(String(data[key]).trim());
									el.addClass('valid');
								}

								if (data[key] && key=="clientCity" && (el =  $('#orderForm input[name="clientCity"]'))){
                                    el.val(String(data[key]).trim());
									$('.settlementName').html(String(data[key]).trim())
								}

                                
                            });
							//debugger;
							console.log(e)
							$(e.target).closest('.inputWrap').next('.inputWrap').find('input').focus();						
							$(this).closest('.inputWrap').attr('style','')
                            this.setAttribute('hidden', '');
                        }
                    }
                }
            });
        });
    });
</script>
