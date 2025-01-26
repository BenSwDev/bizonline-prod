<?php 
	
	
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

	if($orderID){
		$noOrder = 1;
		while($noOrder && $noOrder<3){
			$noOrder ++;
			$que = "SELECT sites.siteName, orders.* 
			, MIN(IF(T_orders.timeFrom = '00:00:00', NULL, T_orders.timeFrom)) AS `abs_timeFrom`
			, MAX(T_orders.timeUntil) AS `abs_timeUntil`
			FROM `orders` 
			INNER JOIN `sites` USING(`siteID`) 
			LEFT JOIN `orders` AS T_orders ON (`orders`.orderID = T_orders.parentOrder)
			WHERE `orders`.`orderID` = " . $orderID;
			$order = udb::single_row($que);
			if($order['parentOrder'] && $order['parentOrder'] != $order['orderID'] ){
				$orderID = $order['parentOrder'];
				$open_treatmentID = $order['orderID'];				
				
			}else{
				$noOrder = 0;
			}
			//echo "data ".$order['parentOrder']." - ".$order['orderID'].PHP_EOL;
		}

        if (!$order['siteID'] || !$_CURRENT_USER->has($order['siteID'])){
            echo 'Access denied to site ' . $order['siteID'];
            return;
        }

		$startDate = implode('/',array_reverse(explode('-',substr($order['showTimeFrom'],0,10))));
		$endDate = implode('/',array_reverse(explode('-',substr($order['showTimeUntil'],0,10))));
		$startTime = substr($order['showTimeFrom'],11,5);
		$endTime = substr($order['showTimeUntil'],11,5);

		$orderType = $asOrder ? 'order' : $order['orderType'];

        $que = "SELECT `rooms`.`siteID`, `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms_units`.`hasStaying`,`rooms`.`roomName`,`rooms`.`cleanTime`, `rooms`.maxAdults, `rooms`.maxKids, `rooms`.maxGuests
                FROM `rooms_units` INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
                    LEFT JOIN `orderUnits` ON (orderUnits.unitID = `rooms_units`.`unitID` AND orderUnits.orderID = " . $orderID . ")
                WHERE `rooms`.`siteID` = " . $order['siteID'] . " AND (rooms.active = 1 OR orderUnits.unitID IS NOT NULL) AND `rooms_units`.`hasStaying` > 0";
        $rooms = udb::key_row($que,'unitID');

        $siteData = udb::single_row("SELECT `sites`.`cleanGlobal`, `sites`.`checkInHour`, `sites`.`checkOutHour`,`sites`.`siteName`,`sites`.`sendReviews`,  `sites_langs`.`defaultAgr`, `sites_langs`.`agreement1`, `sites_langs`.`agreement2`, `sites_langs`.`agreement3`
                , IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal`
            FROM `sites` INNER JOIN `sites_langs` ON (`sites_langs`.`siteID` = `sites`.`siteID` AND `sites_langs`.`langID` = 1 AND `sites_langs`.`domainID` = 1)
	        WHERE `sites`.`siteID` = " . $order['siteID']);

        $default = udb::key_value("SELECT `siteID`, `defaultAgr` FROM `sites_langs` WHERE `domainID` = 1 AND `langID` = 1 AND `siteID` = " . $order['siteID']);

        $paid = (new OrderSpaMain($orderID))->get_paid_sum();

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
        }
        unset($treat);

        $orderExtras = $order['extras'] ? json_decode($order['extras'], true) : [];
	}
	else {
//        $que = "SELECT `rooms`.`siteID`,`rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms_units`.`hasStaying`,`rooms`.`roomName`,`rooms`.`cleanTime`, `rooms`.maxAdults, `rooms`.maxKids, `rooms`.maxGuests
//                FROM `rooms_units`
//                INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
//                WHERE rooms.active = 1 AND `rooms`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ") AND `rooms_units`.`hasStaying` > 0" ;
//        $rooms = udb::key_row($que,'unitID');
//
//        $siteData = udb::single_row("SELECT `sites`.`cleanGlobal`, `sites`.`checkInHour`, `sites`.`checkOutHour`,`sites`.`siteName`,`sites`.`sendReviews`,  `sites_langs`.`defaultAgr`, `sites_langs`.`agreement1`, `sites_langs`.`agreement2`, `sites_langs`.`agreement3`
//                , IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal`
//            FROM `sites` INNER JOIN `sites_langs` ON (`sites_langs`.`siteID` = `sites`.`siteID` AND `sites_langs`.`langID` = 1)
//	        WHERE `sites`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
//
//        $default = udb::key_value("SELECT `siteID`, `defaultAgr` FROM `sites_langs` WHERE `domainID` = 1 AND `langID` = 1 AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
//
//        $startTime = $siteData['checkInHour'];
//        $endTime = $siteData['checkOutHour'];
//
//        $paid = 0;
//
//        $treatments = $orderExtras = [];

        echo 'No orderID ';
        return;
    }

    $lastTreat = '0000-00-00 00:00:00';
    foreach($treatments as $treat)
        if (strcmp($lastTreat, $treat['timeUntil']) < 0)
            $lastTreat = $treat['timeUntil'];

    $que = "SELECT * FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
                WHERE " . ($orderID ? "s.siteID = " . $order['siteID'] : "s.siteID IN (" . $_CURRENT_USER->sites(true) . ") AND e.extraType <> 'package'") . " AND s.included = 0 AND s.active = 1 ORDER BY e.showOrder";
    $extras = udb::key_list($que, ['siteID', 'extraType']);

	if(!$order['domainID']) $order['domainID'] = "0";
?>
	<div class="create_order <?=$orderType?>" id="create_orderPop">
		<div class="container">
			<div class="close" onclick="closeOrderForm()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
			<div class="title mainTitle">	
				<div class="domain-icon" style="background-image:url(<?=$domain_icon[$order['domainID']]?>)"></div>	
				
					<?=($order['orderIDBySite'] ? (($orderType=="preorder"? "שיריון" : "הזמנה")." מספר ".$order['orderIDBySite']) : (($orderType=="preorder")?  "שיריון מקום" :  "הזמנת ספא חדשה"))?>
				
			</div>
			
			
               
				<?php
					if($order['guid'] && $orderType == 'order' || $showthisOrder){
				?><!--test  <?=print_r($order)?>-->
				<?/*
				<div class="inputLblWrap">
					<div class="switchTtl">הזמנה פעילה / מבוטלת</div>
					<label class="switch">
					  <input type="checkbox" name="status" value="1" class="ignore" <?=!$order?"checked":$order['status']?"checked":""?> >
					  <span class="slider round"></span>
					</label>
				</div>*/?>
				



			
			<form class="therapistForm" >
				
				<div class="inputWrap half">
					<?=$order['customerName']?>
					<label for="name">שם המזמין</label>
				</div>
				<div class="inputWrap half tZehoot orderOnly">
					<?=$order['customerTZ']?>
					<label for="tZehoot">תעודת זהות</label>                            
				</div>
				<div class="inputWrap half">
					<?=$order['customerPhone']?>
					<label for="phone">טלפון</label>
				</div>
				
				<div class="inputWrap half orderOnly">
					<?=$order['customerPhone2']?>
					<label for="phone2">טלפון נוסף</label>
				</div>
				
				<div class="inputWrap half email orderOnly">
					<?=$order['customerEmail']?>
					<label for="email">אימייל</label>                            
				</div>
				<div class="inputWrap half select orderOnly">
					
						<?php foreach($reasons as $reason) { ?>
							<?=$order['reason']==$reason['mainPageID']? $reason['mainPageTitle']:""?>
						<?php } ?>

					
					<label for="reason">סיבת הגעה</label>
				</div>				
				<div class="inputWrap orderOnly">
					<?=$order['customerAddress']?>
					<label for="clientAddress">כתובת המזמין</label>
				</div>	
				
				<div id="spa_orders_wrap">
					
					<div id="spa_orders">
<?php
    if ($treatments){
        foreach($treatments as $treat){
			
			
			
?>
                        <div class="spaorder" id="spaorder<?=$treat['orderID']?>" data-id="<?=$treat['orderID']?>" data-price="<?=$treat['price']?>">
                            
                            <div class="spasect">
                                <b><?=$treat['customerName']?><?=$treat['health_guid']?"<span class=\"V\"></span>":""?></b><span><?=$treat['treatmentName']?></span>
                            </div>
                            <div class="spasect">
                                <span><?=($treat['startDate'] ? db2date($treat['startDate'], '/') : '<i>(אין תאריך)</i>')?></span>
                                <span><?=($treat['startTime'] ? $treat['endTime'] . ' - ' . $treat['startTime'] : '')?></span>
                            </div>
                            <div class="spasect">
                                <span class="null"><?=($treat['therapistID'] ? $treat['masterName'] : 'לא נבחר מטפל')?></span>
                                <span class="null"><?=($treat['roomName'] ? $treat['roomName'] : 'לא נבחר חדר')?></span>
                            </div>
							<div class="spasect">
								<?if($treat['health_guid']){?><a href="/health/<?=$treat['siteID']?>/<?=$treat['health_guid']?>" target="_blank">צפיה בהצהרה</a><?}?>
							</div>
							<div class="spasect" style="min-width:160px;font-weight:normal;font-size:16px;text-align:right">
								<?=nl2br($treat['comments_customer'])?>
							</div>
                            
                            
                        </div>
<?php
        }
    }
?>
                    </div>
					
				</div>



				
					
			</form>
		</div>
		
	</div>

<?}?>
