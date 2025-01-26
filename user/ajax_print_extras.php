<?php 
require_once "auth.php";

$result = new JsonResult();
$sids_str = $_CURRENT_USER->sites(true);
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
			<h1>הדפסת לו"ז יומי</h1>
			<style>
			.printextras{margin:20px auto;border-collapse:collapse}
			.printextras td,.printextras th{border:1px #ccc solid;padding:10px;text-align:right;vertical-align:center;display:table-cell}
				@media print{
					body > *:not(#extraspop){display:none !important}
					#extraspop  {position:relative;width:auto;height:auto;margin:0;right:0}
					#extraspop .pop-cont {position:relative;width:auto;height:auto;transform:none;right:0;max-width:none;max-height:none}
					#extraspop .pop-cont > *:not(#printpopthis){display:none !important}

				}
			</style>
	
			
			<div class="printpop" onclick="window.print();">הדפס<svg width="8px" height="8px" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg"><path d="M2 0v2h4v-2h-4zm-1.91 3c-.06 0-.09.04-.09.09v2.81c0 .05.04.09.09.09h.91v-2h6v2h.91c.05 0 .09-.04.09-.09v-2.81c0-.06-.04-.09-.09-.09h-7.81zm1.91 2v3h4v-3h-4z"></path></svg></div>
			<div id="printpopthis">
				<div style="font-size:18px;font-weight:bold;margin:20px">תוספות בתשלום ל <?=date("d/m/Y",strtotime($date))?></div>
				<table cellpadding=0 cellspacing=0 class='printextras'>
					<tr>
						<th>שעות טיפולים</th>
						<th>שם הלקוח</th>
						<th>מס הזמנה</th>
						<th>מקור הגעה</th>
						<th>תוספות בתשלום</th>
					</tr>
				<?
				foreach($oextras as $extra){
					$exx = json_decode($extra['extras'], true) ;
					$show_extras = 0;
					foreach($exx['extras'] as $key => $ex){
						if($extrasNames[$key]){
							$show_extras = 1;
						}
						
					}
					if($show_extras){
					?>
					<tr>
						<td><?=substr($extra['startTime'],-8,-3)?> - <?=substr($extra['endTime'],-8,-3)?></td>
						<td><?=$extra['customerName']?></td>
						<td><?=$extra['orderIDBySite']?></td>
						<td><?=$pSource[$extra['sourceID']]?></td>
						<td class="extras"><?//=print_r($exx)?>
							<?
							
							foreach($exx['extras'] as $key => $ex){
								if($extrasNames[$key]){
								?>
								<div class="extra"><span><?=$ex['count']?></span><span><?=$extrasNames[$key]?></span></div>
								<?}
							}
							
							?>
						</td>
					</tr>

					<?
					}
					//print_r($extra);
				}

				?>
				</table>
			</div>
	</div>
</div>


	