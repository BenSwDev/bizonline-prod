<?php
require_once "auth.php";
$nextLimitTop = 500;
$day   = typemap($_GET['day'], 'date');

$sids_str = $_CURRENT_USER->sites(true);
$sids_str = intval($_GET['siteID'])>0?intval($_GET['siteID']):$sids_str;
if(!$_GET['rt']){
	$que = "SELECT sites.siteName, `orders`.*, rooms_units.unitName , treatments.treatmentName, therapists.siteName AS therapistName,health_declare.declareID  AS helthDelare, rooms_units.unitID,
			pOrders.sourceID AS pSource
			FROM `orders` INNER JOIN `sites` ON (orders.siteID = sites.siteID)
				INNER JOIN treatments ON (orders.treatmentID = treatments.treatmentID)
				INNER JOIN therapists ON (orders.therapistID = therapists.therapistID AND therapists.active = 1 AND therapists.deleted = 0)
				LEFT JOIN `orderUnits` USING(`orderID`)
				LEFT JOIN `rooms_units` USING(`unitID`)
				LEFT JOIN health_declare ON (orders.orderID = health_declare.orderID)
				LEFT JOIN orders AS pOrders ON(orders.parentOrder = pOrders.orderID)
			WHERE orders.status=1 AND orders.allDay=0 AND orders.siteID IN (" . $sids_str . ") AND orders.parentOrder > 0  AND orders.parentOrder <> orders.orderID AND orders.timeFrom >= '".$day." 00:00:00' AND orders.timeUntil <= '".$day." 23:59:59' 
			GROUP BY `orders`.orderID ORDER BY `therapistName`, `timeFrom` ";
	$luz = udb::key_list($que, 'therapistID');
	$daylitreats = udb::full_list($que);
	//echo $que;

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

	$count_treats=0;
	foreach($luz as $therapistID => $lup){
		foreach($lup as $lu){$count_treats++;}
	}
}else{
	$que = "SELECT sites.siteName, `orders`.*, 
			GROUP_CONCAT(rooms_units.unitName SEPARATOR ', ') AS `unitNames`, 
			treatments.treatmentName, therapists.siteName AS therapistName,health_declare.declareID  AS helthDelare, SUM(`health_declare`.negative) AS h_negatives,
			`p`.price AS priceTotal
            FROM `orders` 
            LEFT JOIN `orderUnits` USING(`orderID`)
            LEFT JOIN `rooms_units` USING(`unitID`)
			LEFT JOIN health_declare ON (orders.orderID = health_declare.orderID)
            LEFT JOIN `sites` ON (orders.siteID = sites.siteID)
			LEFT JOIN treatments ON (orders.treatmentID = treatments.treatmentID)
			LEFT JOIN therapists ON (orders.therapistID = therapists.therapistID)
			LEFT JOIN orders AS `p` ON (orders.parentOrder = `p`.orderID)
            WHERE p.status=1 AND p.allDay=0 AND orders.siteID IN (" . $sids_str . ") AND orders.timeFrom >= '".$day." 00:00:00' AND orders.timeUntil <= '".$day." 23:59:59'
            GROUP BY orders.orderID
            ORDER BY orders.`timeFrom`";
	//echo $que;
    $next = udb::full_list($que);

	$count_treats=0;
	foreach($next as $lup){
		$count_treats++;
	}
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Schedule</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="assets/css/style.css?v=<?=time()?>">
    <style>
        body {
            background: #fff;
            direction:rtl;
            padding-top:10px
        }
        page[size="A4"] {background: white;width: 21cm;height: 29.7cm;display: block;margin: 0 auto;margin-bottom: 0.5cm;}
        h1 {display: block;text-align: center;padding: 20px 0;font-size: 18px;border-bottom: 1px #ccc solid;}
        h2 {display: block;width: 95%;margin: 15px auto 0;font-size: 18px;}
		.name {break-inside: avoid;}
        table {width:95%;margin:0 auto;border-collapse:collapse;}
        table td {border: 1px solid #CCC;border-bottom: 1px #777 solid;padding: 3px;font-size: 14px;width:calc(100% / 5);vertical-align:middle;}
		table th{text-align:right;vertical-align:bottom}
		body .ptype {display:none}
		body .ptype.dshow {display:inline-block;padding-left:5px;}
		body .ptype.dshow.checkall {display:inline-block}
		body .ptype.dshow.uncheckall {display:inline-block}
        @media print {
			body.ptype .treatcount {display:none!important}
			.names-select, input{display:none!important}
			body.ptype .report-type {display:none!important}
			body .ptype.dshow {display:none!important}
			body.ptype label span.ptype.dshow {display:inline-block!important}
			.checkall, .uncheckall {display:none!important}
			body.ptype h1 {display:none!important}

			body.ptype .name{ display:block; page-break-after:always; }body.ptype  div.name:not(.show) {display:none}

            body, page[size="A4"] {
                margin: 0;
                box-shadow: 0;
            }
        }
    </style>
</head>
<body>
<page size="A4">
    <?php
    $dayNames = array(
        'Sun' => 'ראשון',
        'Mon' => 'שני',
        'Tue' => 'שלישי',
        'Wed' => 'רביעי',
        'Thu' => 'חמישי',
        'Fri' => 'שישי',
        'Sat' => 'שבת',
    );
    ?>
	<style>
		.report-type a {padding: 5px 10px;margin: 5px;border: 1px #0dabb6 solid;color: #0dabb6;text-decoration: none;}
		.report-type a.active {background: #0dabb6;color: white;}
		.last-orders .items .order ul li.send{display:none}
	</style>
	<div class="report-type">
		<a class="<?=!$_GET['rt']? "active" : "" ?>" href="?day=<?=$day?>">לפי מטפלים</a>
		<a class="<?=$_GET['rt']==2? "active" : "" ?>" href="?day=<?=$day?>&rt=2">לפי שעה</a>
		<a class="<?=$_GET['rt']==3? "active" : "" ?>" href="?day=<?=$day?>&rt=3">משמרות</a>
	</div>
    <h1>יום <?=$dayNames[date('D', strtotime($_GET['day']))]?> <?=date('d/m/Y', strtotime($_GET['day']))?></h1>
	<?
	?>
	<?if($_GET['rt']<3){?>
    <div class="treatcount" style="margin:10px 20px 0"><b>כמות טיפולים ליום זה : <?=$count_treats?> </b></div>
	<?}?>
<?php
	
	if($_GET['rt']==2){?>
	<div class="last-orders">        
        <div class="items">
		<style>
		.day_line {line-height: 50px;background: #cfeef0;margin-top: 10px;border-radius: 10px;padding: 0 20px;font-size: 16px;font-weight: bold;}
		.last-orders .items .order.day_event_line {background: white;display: table;width: 100%;font-size: 16px;min-height: 60px;border-radius: 10px;margin: 2px 0 0;max-width: none;border: 0;box-shadow:none;border-bottom:1px #ccc solid;cursor:pointer}
		.day_event_line .time {width: 100px;display: table-cell;text-align: center;vertical-align: middle;border-left: 2px #0dabb6 solid;position:relative}
		.day_line_gender {display: block;font-size: 14px;position: absolute;top: 24px;right: 2px;}
		.day_event_line .details {display: table-cell;vertical-align: middle;padding-right: 10px;}
		.day_event_line .details div ~ div {font-size:14px;max-height: 30px;overflow: hidden;line-height: 1;margin-top: 2px;}
		.day_event_line .details div.pextras span{position:relative;color: #777;}
		.day_event_line .details div.pextras span::before{content:"";width: 4px;height: 4px;border-radius:50%;background:black;margin: 2px 2px 2px 4px;position:relative;display: inline-block;}
		</style>
            <?php 
			if($next){
				$cur_date = "";
				$nextCnt = 0;
				foreach($next as $order) {
					$nextCnt++;
					$order['paid'] = udb::single_value("SELECT SUM(`sum`) FROM `orderPayments` WHERE `complete` = 1 AND `subType` <> 'card_test' AND `orderID` = " . $order['orderID']) ?: 0;
				   
						if($cur_date != date('d-m-y', strtotime($order['timeFrom']))){
							$cur_date= date('d-m-y', strtotime($order['timeFrom']));
							?>
							<div class="day_line lineComp<?=$nextCnt?>" <?=$nextCnt>$nextLimitTop? "style='display:none'" : ""?>>יום <?=$weekday[date('w', strtotime($order['timeFrom']))]?>, <?=date('d', strtotime($order['timeFrom']))?> ב<?=$month_name[intval(date('m', strtotime($order['timeFrom'])))]?></div>
						<?}
						orderCompLine($order,$nextCnt);
					
						//orderComp($order);
					
				}
			}?>
			
        </div>
        
    </div>
	<?
	}else if($_GET['rt']==3){
		$que = "SELECT 
		therapists.therapistID AS masterID
		,therapists.siteID
		,therapists.active
		,therapists.siteName AS masterName 
		,therapists.gender_self
		,IF(workerType = 'fictive', 1, 0) AS fictive
		,gender_client
		,`spaShifts`.*
		FROM therapists 
		INNER JOIN `spaShifts` ON(therapists.therapistID = `spaShifts`.masterID AND `spaShifts`.timeFrom <= '".$day." 23:59:59' AND `spaShifts`.timeUntil >= '".$day." 00:00:00') 
		WHERE therapists.siteID = '".$sids_str."' AND active = 1 AND deleted < 1 ";
		
		$que.= " AND workerType = 'regular' ";		
		$que .="ORDER BY masterName, timeFrom ";
		$shifts = udb::full_list($que);
		$therapists = array();
		foreach($shifts as $shift){
			if($therapistName!=$shift['masterName']){
				$therapistName = $shift['masterName'];
				?><div class="div_th_name"><b><?=$therapistName?></b></div><?
			}?>
			<div class="div_shift">
				<span class="shift_type"><?=$shift['status']? "משמרת" :"הפסקה"?></span>
				<span class="shift_time"><?=substr($shift['timeFrom'],11,5)?> - <?=substr($shift['timeUntil'],11,5)?></span>
				<span><?=$shift['orderName']?></span>
			</div>
			<?
			$therapists[$shift['masterID']][] = $shift;
		}
		//print_r($shifts);
		
		?>
		<style>
			.div_th_name {margin-top: 20px;margin-bottom:4px}
			.div_shift {height: 20px;display: flex;align-items: center;border-bottom: 1px #ccc solid;}
			span.shift_type {width: 70px;}
			span.shift_time{width:120px}

			table.shift_print {width: auto;margin-top:40px}
			td.hour {width: 44px;position: relative;max-width: 44px;min-width: 44px;padding: 0;}
			td.hour span {position: absolute;right: -20px;font-size: 12px;background: white;width: 40px;top: 4px;text-align: center;}
			td.th_name {width: 120px;min-width: 120px;}
			td.hour:last-child, tr.therapists td:last-child {width: 0;max-width: 0;min-width: 0;padding: 0;border: 0;}
			tr.therapists td {padding: 0;height: 60px;box-sizing: border-box;width: 120px;}
			tr.therapists td:first-child {padding: 5px;position: relative;}
			.shifts {right: 127px;position: absolute;width: 45px;top: 0;font-size: 12px;}
			.shift {position: absolute;background: white;top: 5px;box-sizing: border-box;border: 1px solid;text-align: center;height: 50px;display: flex;align-items: center;justify-content: center;}
			.shift.break {color: #990000;border-color: #990000;}
		</style>
		<table class="shift_print">
			<tr>
				<td class="th_name">משמרות</td>
			<?for($i=7;$i<=22;$i++){?>
				<td class="hour"><span><?=sprintf("%02d",$i)?>:00</span></td>
			<?}?>
			</tr>
			<?foreach($therapists as $therapist){?>
			<tr class="therapists">
				<td>
					<?
					$first = 1;
					foreach($therapist as $shift){
						if($first){
							$first=0;?>
							<?=$shift['masterName']?>
						<div class="shifts">
						<?}
						$timeFrom = intval(substr($shift['timeFrom'],11,2));
						$timeFromM = intval(substr($shift['timeFrom'],14,2));
						$timeUntil = intval(substr($shift['timeUntil'],11,2));
						$timeUntilM = intval(substr($shift['timeUntil'],14,2));
						$right = ($timeFrom + $timeFromM/60 - 7)*100;
						$width= (($timeUntil+$timeUntilM/60) - ($timeFrom + $timeFromM/60))*100
							
						?>
						<div class="shift <?=$shift['status']? "" :"break"?>" style="right:<?=$right?>%;width:<?=$width?>%">
							<?=$shift['status']? "משמרת" :"הפסקה"?><br>
							<?=sprintf("%02d",$timeFrom)?>:<?=sprintf("%02d",$timeFromM)?><br>
							<?=sprintf("%02d",$timeUntil)?>:<?=sprintf("%02d",$timeUntilM)?>
						</div>

					<?}?>
						</div>
				</td>
				<?for($i=7;$i<=22;$i++){?>
					<td>
						
					</td>
				<?}?>
			</tr>
			<?}?>
		</table>
	<?}else{?>
		
        <script src="/user/assets/js/jquery.min.js"></script>

		
		<div class="names-select" style="padding:0 2.5%;box-sizing:border-box">

		
			<input type="checkbox" id="bymaster" onchange="if($(this).is(':checked')){$('body').addClass('ptype');$('.ptype').addClass('dshow')}else{$('body').removeClass('ptype');$('.ptype').removeClass('dshow')}">
			<label for="bymaster">הדפסת לו"ז לפי מטפל</label>

		</div>

		<span class="ptype" style="vertical-align:middle;">רק המטפלים המסומנים יודפסו</span>
		<div style="vertical-align: middle;cursor: pointer;padding: 5px 10px;margin: 5px;border: 1px #0dabb6 solid;color: #0dabb6;text-decoration: none;color: #FFF;font-weight: 600;background: #0dabb6;" class="checkall ptype" onclick="$('.name input').prop('checked', true).trigger('change')">סמן את כולם</div>
		<div style="vertical-align: middle;cursor: pointer;padding: 5px 10px;margin: 5px;border: 1px #0dabb6 solid;color: #0dabb6;text-decoration: none;color: #FFF;font-weight: 600;background: #0dabb6;" class="uncheckall ptype" onclick="$('.name input').prop('checked', false).trigger('change')">בטל סימון לכולם</div>

	<div class="names">
	<?
	$count = 0;

        foreach($luz as $therapistID => $lup){
			$count++;
            $name = $lup[0]['therapistName'];
?> 
        <div class="name">
            <?/*
				<h2><?=($lup[0]['therapistName'] ?: "חסר שם")?></h2>
			*/?>
            <table cellspacing="0" cellpadding="0">
                <thead>
					<th colspan=4>
						
						<h2>
							<div class="ptype"><input type="checkbox" style="display: inline-block;vertical-align: middle;" id="view<?=$count?>" onchange="if($(this).is(':checked')){$(this).closest('.name').addClass('show')}else{$(this).closest('.name').removeClass('show')}"></div>
							
							<label for="view<?=$count?>"><?=($lup[0]['therapistName'] ?: "חסר שם")?><span class="ptype"> - יום <?=$dayNames[date('D', strtotime($_GET['day']))]?> <?=date('d/m/Y', strtotime($_GET['day']))?></span></label></h2>
					</th>
					<th>מטפל נוסף</th>
					<?/*<th>מקור</th>*/?>
                </thead>
                <tbody>
<?php
            foreach($lup as $lu){
?>
                <tr>
                    <td><?=substr($lu['timeFrom'],11,5)?> - <?=substr($lu['timeUntil'],11,5)?></td>
                    <td><?=$lu['treatmentName']?> - <?=$lu['treatmentLen']?> דקות</td>
                    <td><?=$lu['unitName']?></td>
                    <td><?=$lu['customerName']?> - <?=$lu['treatmentClientSex']==1? "גבר" : "אשה"?></td>
					<td >
						<?
						$more_therapists = [];
						$stt_timeFrom = intval(strtotime($lu['timeFrom']));
						$stt_timeUntil = intval(strtotime($lu['timeUntil']));
						foreach($daylitreats as $treat){
							if($treat['unitID'] == $lu['unitID'] && $treat['therapistID']!=$lu['therapistID']){
								
								if(intval(strtotime($treat['timeUntil']))>$stt_timeFrom && intval(strtotime($treat['timeFrom']))<$stt_timeUntil){
									$more_therapists[] = $treat['therapistName'];
									//echo $stt_timeFrom."-".$stt_timeUntil.' | '.strtotime($treat['timeFrom']).'-'.strtotime($treat['timeUntil']).'<br>';
								}
							}
						}
						//print_r($more_therapists);
						echo implode(',',$more_therapists);
						?>
					</td>
					<?/*
						<td><?=$pSource[$lu['pSource']]?></td>
						*/
						?>
                </tr>
<?php
            }
?>
                </tbody>
            </table>
        </div>
		
		

<?php
        }
	
?>
    </div>
	<?}?>
</page>
</body>
</html>
