<?php 
require_once "auth.php";

$result = new JsonResult();
$sids_str = $_CURRENT_USER->select_site() ?: $_CURRENT_USER->sites(true);
$date = typemap($_POST['date'],'date');

$que = "SELECT e.extraID, e.extraName FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
                WHERE s.siteID IN (" . $sids_str . ") AND s.included = 0 AND s.active = 1 ORDER BY e.showOrder";
	$extrasNames = udb::key_value($que);

$que = "SELECT `o`.*, `o`.extras,	 		
		Min(`T_orders`.`timeFrom`) AS startTime,
		Max(`T_orders`.`timeUntil`) AS endTime
		FROM orders AS `o`
		INNER JOIN orders AS T_orders ON (T_orders.parentOrder = o.orderID)
		WHERE o.siteID IN (" . $sids_str . ") AND o.status=1  AND T_orders.`timeFrom` >= '".date("Y-m-d 00:00:00",strtotime($date))."' AND T_orders.`timeFrom` <= '".date("Y-m-d 23:59:59",strtotime($date))."' AND `o`.extras IS NOT NULL AND `o`.extras NOT LIKE ''
		GROUP BY o.orderID
		ORDER BY startTime";
$oextras = udb::full_list($que);

$siteData = udb::single_row("SELECT `sites`.`cleanGlobal`, `sites`.`checkInHour`, `sites`.`checkOutHour`,`sites`.`siteName`,`sites`.`sendReviews`, `sites`.`sourceRequired`, `sites`.`addressRequired`, `sites_langs`.`defaultAgr`, `sites_langs`.`agreement1`, `sites_langs`.`agreement2`, `sites_langs`.`agreement3`
                , IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal`
            FROM `sites` INNER JOIN `sites_langs` ON (`sites_langs`.`siteID` = `sites`.`siteID` AND `sites_langs`.`langID` = 1 AND `sites_langs`.`domainID` = 1)
	        WHERE `sites`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ")");


$que = "SELECT `rooms`.`siteID`,`rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms_units`.`hasStaying`,`rooms`.`roomName`,`rooms`.`cleanTime`, `rooms`.maxAdults, `rooms`.maxKids, `rooms`.maxGuests
		FROM `rooms_units`
		INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
		WHERE rooms.active = 1 AND `rooms`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ") AND `rooms_units`.`hasStaying` > 0" ;
$rooms = udb::key_row($que,'unitID');





//print_r($oextras);

UserUtilsNew::init($_CURRENT_USER->active_site());
$cuponTypes = UserUtilsNew::$CouponsfullList;
foreach($cuponTypes as $k=>$source) {
	$pSource[$k] = $source;
 }
foreach(UserUtilsNew::guestMember() as $k => $source){
	$pSource[$k] = $source;
}
foreach(UserUtilsNew::otherSources() as $k => $source){
	$pSource[$k] = $source;
}
$pSource['online'] = "הזמנה אונליין";
$pSource['spaplus'] = "ספא פלוס אונליין";
$pSource[0] = "הזמנה רגילה";

?>

<div class="luz-pop" id="extraspop" style="display: block;">
	
	<div class="pop-cont">
		<div class="close" onclick="$(this).closest('.luz-pop').remove()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
			<h1>הדפסת שוברים <?=date("d/m/Y",strtotime($date))?></h1>
			<style>
			.printextras{margin:20px auto;border-collapse:collapse}
			.printextras td,.printextras th{border:1px #ccc solid;padding:10px;text-align:right;vertical-align:center;display:table-cell}
			.left {text-align:center;width:100%;}
			.block {text-align:center;font-size:18px;max-width:500px;display:inline-block;width:100%;margin-bottom:10px;position:relative}
			.block .border {padding:10px;box-sizing:border-box;border:1px solid #000;text-align:right;font-size:16px;border-radius:10px;}
			.placename{display:none}
			.print-icon {display:none;width: 30px;float: left;height: 30px;margin-left: 5px;background: url(/user/assets/img/printer-4-48.png);margin-top: 5px;background-size: 80%;background-color: #0dabb6;background-position: center;background-repeat: no-repeat;border-radius: 3px;cursor: pointer;position: absolute;z-index: 9;left: 0;}
			.print-icon{display:block}
				@media print{
					body > *:not(#extraspop){display:none !important}
					#extraspop  {position:relative;width:auto;height:auto;margin:0;right:0}
					#extraspop .pop-cont {position:relative;width:auto;height:auto;transform:none;right:0;max-width:none;max-height:none}
					#extraspop .pop-cont > *:not(#printpopthis){display:none !important}
					body page[size="A4"] {background: white;width: 21cm;height: 25.7cm;display: block;margin: 0 auto;margin-bottom: 0.5cm;}
					body, page[size="A4"] {margin: 0;box-shadow: 0;}
					.placename{display:block}
					.print-icon{display:none}
					}
			</style>
	
			
			<div class="printpop" style="margin-bottom:10px" onclick="$('page').attr('size','A4');window.print();">הדפס בנפרד<svg width="8px" height="8px" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg"><path d="M2 0v2h4v-2h-4zm-1.91 3c-.06 0-.09.04-.09.09v2.81c0 .05.04.09.09.09h.91v-2h6v2h.91c.05 0 .09-.04.09-.09v-2.81c0-.06-.04-.09-.09-.09h-7.81zm1.91 2v3h4v-3h-4z"></path></svg></div>
			<div class="printpop" style="margin-bottom:10px" onclick="$('page').attr('size','none');window.print();">הדפס במרוכז<svg width="8px" height="8px" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg"><path d="M2 0v2h4v-2h-4zm-1.91 3c-.06 0-.09.04-.09.09v2.81c0 .05.04.09.09.09h.91v-2h6v2h.91c.05 0 .09-.04.09-.09v-2.81c0-.06-.04-.09-.09-.09h-7.81zm1.91 2v3h4v-3h-4z"></path></svg></div>
			<div id="printpopthis">
				
					
				<?
				$que = "SELECT e.extraID, e.extraName FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
					    WHERE s.siteID IN (" . $sids_str . ") AND s.included = 0 AND s.active = 1 AND s.voucherprint = 1 ORDER BY e.showOrder";
				$extrasPrintVouchter = udb::key_value($que);
				foreach($oextras as $extra){
					$units ='';
					$que = "SELECT orderUnits.*, rooms_units.roomID FROM orderUnits INNER JOIN `rooms_units` USING(`unitID`) WHERE `orderID` = ".$extra['orderID'];
					$units = udb::key_row($que, 'unitID');
					foreach($rooms as $room) {
						if($units[$room['unitID']])$print_roomName = $room['unitName'];
						else continue;
					}
					$exx = json_decode($extra['extras'], true) ;
					$show_extras = 0;
					foreach($exx['extras'] as $key => $ex){
						if(array_key_exists($key,$extrasPrintVouchter)){?>
							<page size="A4">
								<div class="block" dir="rtl" style="direction:rtl;">
								<div class="print-icon" data-place="<?=$siteData['siteName']?>" onclick="printExtra($(this));"
								data-date="<?=date("d/m/Y",strtotime($date))?>" 
								data-time="<?=$units?date('H:i', strtotime($startDate)):date('H:i', strtotime($extra['startTime']))?>" 
								data-person="<?=$extra['customerName']?>" 
								data-room="<?=$print_roomName?>"
								data-quantity="<?=$ex['count']?>" data-name="<?=htmlspecialchars($extrasNames[$key])?>" 
								data-title="<?=htmlspecialchars($extra['extraName'])?>"
								data-desc="<?=htmlspecialchars($extra['description'])?>"></div>
								<div class="placename"><?=$siteData['siteName']?></div>
								<div class="border">
									<div><strong>אורח:</strong> <span><?=$extra['customerName']?></span></div>
									<?/*
									<div><strong>חדר:</strong> <span><?=$print_roomName?></span></div>
									*/?>
									<div><strong>תאריך:</strong> <span><?=date("d/m/Y",strtotime($date))?></span></div>
									<?/*
										<div><strong>שעה:</strong> <span><?=$units?date('H:i', strtotime($startDate)):date('H:i', strtotime($extra['startTime']))?></span></div>
									*/?>
									<div><strong>פריט:</strong> <span><?=$extrasNames[$key]?></span></div>
									<div><strong>כמות:</strong> <span><?=$ex['count']?></span></div>
								</div>
								</div>
							</page>
							
						<?}
						
					}
					
					//print_r($extra);
				}

				?>
			</div>
	</div>
</div>


	