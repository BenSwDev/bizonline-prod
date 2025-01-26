<style id="theStyle">
    table.bigDamnTable{margin-top:25px;margin-bottom:10px;width:100%;border-bottom:2px solid rgba(0,0,0,.1);box-sizing:border-box;border-radius:5px;overflow:auto;text-align:right}
    table.bigDamnTable>thead{border-bottom:2px solid #f5f5f5;line-height:32px;font-weight:700;position: sticky;top: 0;z-index: 1;}
	table.bigDamnTable>thead>tr>th {background:#fff;text-align: right;padding-right: 5px;border: 2px solid #f5f5f5;vertical-align: middle;line-height: 1;height: 50px;}
	table.bigDamnTable>thead>tr.mini>th{height:20px}
	table.bigDamnTable>thead>tr>th.trans{height:20px;background:transparent}
	table.bigDamnTable>thead>tr>th:nth-child(1){width:5%;text-align:center;padding-right:0}
    table.bigDamnTable>thead>tr>th.address{width:5%!important}
    table.bigDamnTable>thead>tr>th.dtim{width:2%!important}
    
    table.bigDamnTable>tbody>tr{line-height:1;color:#666;cursor:pointer;font-size:14px;height: 60px;position: relative;}
    table.bigDamnTable>tbody>tr:nth-child(odd){background:#f9f9f9}
    table.bigDamnTable>tbody>tr:nth-child(even){background:#fff}
	table.bigDamnTable>tbody>tr.highlight{background:#cfeef0}
    table.bigDamnTable>tbody>tr:hover{background:#e5f6ff}
    table.bigDamnTable>tbody>tr>td{border:1px solid #f5f5f5;padding:10px;vertical-align:middle}
    table.bigDamnTable>tbody>tr>td>span.redColor{color:red}
    table.bigDamnTable>tbody>tr>td>span.greenColor{color:green}
    table.bigDamnTable>tbody>tr>td:nth-child(1){text-align:center;padding-right:0}
    table.bigDamnTable>tbody>tr>td.bank{padding:}
	table.bigDamnTable>tbody>tr>td .submit{width:100px;background:#2aafd4;color:#fff;border:none;font-weight:700;font-size:14px;height:26px}
    table.bigDamnTable>tbody>tr>td span{font-size:12px;line-height:1;white-space:nowrap}
	table.bigDamnTable>tbody>tr>td span.total{margin-top:5px;font-size:14px;color:black}
	table.bigDamnTable>tbody>tr>td.totalT{color:black}
	.searchCms{max-width:200px;margin:20px 0;position:relative;text-align:right}
	.searchCms select{height:30px;padding:0 10px;-webkit-appearance:menulist}
	.searchCms a {text-align: center;line-height: 50px;font-size: 14px;position: absolute;left: 60px;top: 0;color: #000;box-sizing: border-box;background: #fff;cursor: pointer;width: 42px;border: 1px solid #ccc;}
	.searchCms input[type=submit] {position: absolute;left: 10px;top: 0;color: #fff;box-sizing: border-box;background: #0dabb6;cursor: pointer;width: 42px;height: 52px;font-size: 16px;}
	.searchCms .secParmLine{display:block}
	table.bigDamnTable .ttime{text-align:center;width:30px;white-space:nowrap}
	table.bigDamnTable td:nth-child(2),	table.bigDamnTable th:nth-child(2){background:white;position:sticky;right:0;z-index:1}
	table.bigDamnTable>tbody>tr:nth-child(odd) td:nth-child(2){background:#f9f9f9}
	.orders_num.o-ctrl {background: #c9f2fd;}
	.orders_num {cursor: pointer;position: relative;}
	.orders_num.o-ctrl::after {opacity: 1;}
	.orders_num.o-up::after {opacity: 0.2;}
	.orders_num::after {content: "";width: 6px;height: 6px;box-sizing: border-box;border-left: 2px black solid;border-bottom: 2px black solid;display: block;position: absolute;bottom: 0;margin: 0 auto;left: 0;right: 0;transform: rotate(-45deg);opacity: 0;}
	.orders_num.o-down::after {opacity: 0.5;transform: rotate(135deg);}
	.excel {line-height: 44px;margin: 10px 5px;display: inline-block;font-size: 16px;color: #0dabb6;background: white;border: 1px#0dabb6 solid;padding: 0 10px;cursor: pointer;border-radius: 10px;}
	
	.changever{display:inline-flex;align-items:center;font-size:14px;font-weight:normal;height:30px;cursor:pointer}
	.changever::before{content:"";display:block;width:14px;height:14px;margin-left:4px;border:2px solid black;box-shadow:0 0 0 2px white inset}
	#changever:checked ~ .changever::before{background:#0dabb6}
	#changever:not(:checked) ~ table .ver2{display:none}
	#changever:checked ~ table .ver1{display:none}
	th.ttime.ver2{font-size:14px}
	

</style>
<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */

$sid = $_CURRENT_USER->select_site()?:  $_CURRENT_USER->sites(true);

/*echo "user sites ".$_CURRENT_USER->sites(true)."<br>";
echo "user selected ".$_CURRENT_USER->select_site()."<br>";
echo "final val = ".$sid."<br>";*/
//exit;

/*
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}
*/
/*
echo "user sites ".$_CURRENT_USER->sites(true)."<br>";
echo "user selected ".$_CURRENT_USER->select_site()."<br>";
echo "final val = ".$sid."<br>";   */
//exit;

$siteID = intval($_GET['sid'] ?: $_CURRENT_USER->active_site());
if(!$_CURRENT_USER->has($siteID)){
    echo 'Access denied';
    return;
}

 

if ($_GET['tid']){
    include "monthmaster.php";
    return;
}

/*$sitePay = udb::single_value("SELECT `salaryDefault` FROM `sites` WHERE `siteID` = " . $siteID);
if (!$sitePay){
    echo 'טרם הוגדר תעריף<br />לעריכת תעריף לחץ על <a href="?page=paymentsettings">ניהול הגדרות</a>';
    return;
}*/

$treatTimes = [];
//$showAll = intval($_GET['sa'] ?? 0);

list($start, $end) = explode(':', date('Y-m-01:Y-m-t', preg_match('/^20\d\d-[01]\d$/', trim($_GET['month'] ?? '')) ? strtotime($_GET['month'] . '-01') : time()));

$totals  = [];



	


$masters = udb::key_row("SELECT `therapists`.*, sites.siteName AS spaName FROM `therapists` LEFT JOIN sites USING(siteID) WHERE `siteID` IN (" . $sid . ") AND `workerType` <> 'fictive' " . /*($showAll ? "" : " AND `active` = 1") .*/ " AND (`workStart` IS NULL OR `workStart` <= '" . $end . "') AND (`workEnd` IS NULL OR `workEnd` >= '" . $start . "') ORDER BY therapists.`siteName`", 'therapistID');
//print_r($masters);
$que = "SELECT orders.*, rooms_units.unitName
        FROM `orders` INNER JOIN `orders` AS `parent` ON (orders.parentOrder = parent.orderID)
            INNER JOIN `therapists` ON (orders.therapistID = therapists.therapistID)
            LEFT JOIN `orderUnits` ON (orders.orderID = orderUnits.orderID) LEFT JOIN `rooms_units` USING(`unitID`)
        WHERE orders.siteID  IN (" . $sid . ") AND orders.parentOrder > 0 AND orders.parentOrder <> orders.orderID AND orders.status = 1 AND parent.status = 1 
            AND orders.timeFrom BETWEEN '" . $start . " 00:00:00' AND '" . $end . " 23:59:59' AND therapists.`workerType` <> 'fictive'
        ORDER BY orders.orderID";

$lim = 0;
while($orders = udb::single_list($que . " LIMIT " . $lim . ", 1000")){
    foreach($orders as $order){
        // if no data yet - setup some defaults
        if (!isset($totals[$order['therapistID']])){
            $totals[$order['therapistID']] = [
                'count'   => 0,
                'cost'    => 0,
                'weekday' => ['sum' => 0, 'time' => 0, 'rate' => []],
                'weekend' => ['sum' => 0, 'time' => 0, 'rate' => []],
                'sum'     => 0,
                'forSite' => 0,
                '_pay'    => new SalaryMaster($order['therapistID'])
            ];
        }

        $row =& $totals[$order['therapistID']];

        $row['count'] += 1;
        $row['cost']  += $order['price'];

        //$orderRate = $row['_pay']->get_treat_pay($order['timeFrom'], $order['treatmentLen'], $order['price']);
        //$rowkey = $orderRate['weekend'] ? 'weekend' : 'weekday';
        $orderRate  = $row['_pay']->get_day_salary(substr($order['timeFrom'], 0, 10));
        $orderTotal = $row['_pay']->get_order_salary(substr($order['timeFrom'], 0, 10), $order['treatmentLen'], $order['price']);

        $rowkey = $orderRate->isHoliday ? 'weekend' : 'weekday';
		$treatTimes[$rowkey][$order['treatmentLen']]=$order['treatmentLen'];
		
		$row[$rowkey][$order['treatmentLen']]["cnt"]++;
		$row[$rowkey][$order['treatmentLen']]["sum"]+=$orderTotal;
        
		$row[$rowkey]['sum']  += $orderTotal;
        if ($orderRate->type == 'minute')
            $row[$rowkey]['time'] += $order['treatmentLen'];
        $row[$rowkey]['rate'][$orderRate->type][] = $orderRate->isHoliday ? $orderRate->rateWeekend : $orderRate->rateRegular;

        $row['sum'] += $orderTotal;
        $row['forSite'] += $order['price'] - $orderTotal;

        unset($row);
    }

    $lim += 1000;
	//print_r($orders);
}

foreach($treatTimes as &$v)
    ksort($v);
unset($v);



$extraPays = udb::key_row("SELECT `therapistID`, SUM(IF(`sum`>0,`sum`,0)) AS `sumP`, SUM(IF(`sum`<0,`sum`,0)) AS `sumM` FROM `therapists_pay_extra` WHERE `therapistID` IN (" . implode(',', array_keys($masters) ?: [0]) . ") AND `date` BETWEEN '" . $start . "' AND '" . $end . "' GROUP BY `therapistID`","therapistID");
//print_r($extraPays);
//exit;

$que = "SELECT TIMEDIFF(timeUntil,timeFrom) AS shiftlength, masterID as therapistID, timeFrom
        FROM spaShifts
        WHERE siteID IN (" . $sid . ") AND status = 1 
            AND timeFrom BETWEEN '" . $start . " 00:00:00' AND '" . $end . " 23:59:59'
        ORDER BY timeFrom";
$shifts = udb::full_list($que);
//print_r($shifts);
foreach($shifts as $shift){
	$tr_shifts[$shift['therapistID']]['hours'] += intval(explode(":",$shift['shiftlength'])[0]);
	$tr_shifts[$shift['therapistID']]['minutes'] += intval(explode(":",$shift['shiftlength'])[1]);
	$tr_shifts[$shift['therapistID']]['date'][explode(" ",$shift['timeFrom'])[0]] = 1;
}

?>
<div class="searchCms">
	<form method="GET" style="height:60px">
        <input type="hidden" name="page" value="monthtotals" />
		<div class="inputWrap">
    		<select name="month">
<?php
$selected = substr($start, 0, 7);
$select = explode('-', date('Y-n', strtotime($end . ' +3 month')));

for($j = 0; $j < 12; ++$j){
    $curr = date('Y-m', mktime(10, 0, 0, $select[1] - $j, 1, $select[0]));
    echo '<option value="' , $curr , '" ' , (strcmp($curr, $selected) ? '' : 'selected') , '>' , implode('/', array_reverse(explode('-', $curr))) , '</option>';
}
?>
            </select>
        </div>

		<div class="btnWrap">
            <a href="?page=monthtotals">נקה</a>
            <input type="submit" value="חפש">
		</div>
	</form>
    
    <div class="excel" id="expExcel">ייצוא לאקסל</div>
    <div class="excel" onclick="printData()">הדפסה</div>
</div>

<div id="tableToPrint">
<input type="checkbox" style="display:none" id="changever">
<label class="changever" for="changever">הפרדה לעמודות</label>
<table id="monthtotals_table" class="bigDamnTable">
   <thead>
      <tr>
         <th>ID</th>
         <th>שם המטפל</th>
		 <?if(strpos($sid,',')){?>
		 <th>שם הספא</th>
		 <?}?>
         <th>טלפון</th>
         <th class="orders_num">חשבון בנק</th>
         <th class="orders_num">כמות טיפולים חודשית</th>
         <th class="orders_num">עלות הטיפולים</th>
         <th class="orders_num colspan" colspan="<?=count($treatTimes['weekday'])+1?>">אמצ"ש</th>
         <th class="orders_num colspan" colspan="<?=count($treatTimes['weekend'])+1?>">סופש / חג</th>
         <th class="orders_num">תוספות</th>
         <th class="orders_num">קנסות</th>
         <th class="orders_num">סכום למטפל</th>
         <th class="orders_num">רווח</th>
		 <th class="orders_num">שעות משמרת</th>
		 <th class="orders_num">ימי משמרת</th>
         <!-- th>שולם</th -->
      </tr>
	  <tr class="mini">
	  	<th colspan="<?=(strpos($sid,','))? 7: 6?>" class="trans">
		<?
		if($treatTimes['weekday'])
		foreach($treatTimes['weekday'] as $ttime){?>
			<th class="ver1 ttime"><?=$ttime?></th>
			<th class="ver2 ttime"><?=$ttime?> דק'<br style="mso-data-placement:same-cell;" />כמות</th>
			<th class="ver2 ttime"><?=$ttime?> דק'<br style="mso-data-placement:same-cell;" />דקות</th>
		<?}?>
		<th class="ver1">סה"כ</th>
		<th class="ttime ver2">סה"כ<br style="mso-data-placement:same-cell;" />דקות</th>
		<th class="ttime ver2">עלות<br style="mso-data-placement:same-cell;" />דקה</th>
		<th class="ttime ver2">סה"כ<br style="mso-data-placement:same-cell;" />תשלום</th>
		<?
		if($treatTimes['weekend'])
		foreach($treatTimes['weekend'] as $ttime){?>
			<th class="ver1 ttime"><?=$ttime?></th>
			<th class="ver2 ttime"><?=$ttime?> דק'<br style="mso-data-placement:same-cell;" />כמות</th>
			<th class="ver2 ttime"><?=$ttime?> דק'<br style="mso-data-placement:same-cell;" />דקות</th>
		<?}?>
		<th class="ver1">סה"כ</th>
		<th class="ttime ver2">סה"כ<br style="mso-data-placement:same-cell;" />דקות</th>
		<th class="ttime ver2">עלות<br style="mso-data-placement:same-cell;" />דקה</th>
		<th class="ttime ver2">סה"כ<br style="mso-data-placement:same-cell;" />תשלום</th>
		<th colspan="3" class="trans">
   </thead>
   <tbody id="sortRow">
<?php
    $masterUrl = ['page' => 'monthtotals', 'month' => $selected];
    $sums = [];
    $mtc  = 0;

    foreach($masters as $masterID => $master){
        if (!$master['active'] && empty($totals[$masterID]) && empty($tr_shifts[$masterID]) && empty($extraPays[$masterID]))
            continue;

		$total = $totals[$masterID] ?? ['_pay' => new SalaryMaster($masterID)];

		if(!$total['count'] && empty($tr_shifts[$masterID]['minutes']))  
            continue; //don't show therapists without treatments or shifts

        $mtc += 1;
        
        $masterUrl['tid'] = $masterID;
		$bankData =  json_decode($master['bankData'], true);
		$masterURL =  'onclick="window.location.href=\'?'.http_build_query($masterUrl).'\'"';
?>
	  <tr class="<?=$master['salary_type']? "highlight" : ""?>" id="<?=$masterID?>" data-sid="<?=$siteID?>" data-month="<?=$selected?>">
         <td <?=$masterURL?>><?=$masterID?></td>
         <td <?=$masterURL?>><?=$master['siteName']?></td>
		 <?if(strpos($sid,',')){?>
		 <td><?=$master['spaName']?></td>
		 <?}?>
         <td <?=$masterURL?>><?=$master['phone']?></td>
         <td class="bank" <?=$masterURL?>>
		 	<?if($bankData['bankName']){?>
			<span>
            בנק: <?=$bankData['bankName']?><br style="mso-data-placement:same-cell;" />
            קוד בנק: <?=$bankData['bankNumber']?><br style="mso-data-placement:same-cell;" />
            מספר סניף: <?=$bankData['bankBranch']?><br style="mso-data-placement:same-cell;" />
            מספר חשבון: <?=$bankData['bankAccount']?><br style="mso-data-placement:same-cell;" />
            בעל החשבון: <?=$bankData['bankNumberOwner']?><br style="mso-data-placement:same-cell;" />
			</span>
			<?}?>
         </td>
         <td <?=$masterURL?>><?=($total['count'] ?? '-')?></td>
         <td <?=$masterURL?>>₪<?=number_format($total['cost'] ?? 0)?></td>
         <?
		if($treatTimes['weekday'])
		foreach($treatTimes['weekday'] as $key=>$ttime){?>
			<td <?=$masterURL?> class="ttime ver1">
			<?if($total['weekday'][$key]['cnt']){
				$sums['ttimes']['weekday'][$key] += $total['weekday'][$key]['cnt'];?>
				<b><?=$total['weekday'][$key]['cnt']?></b><br style="mso-data-placement:same-cell;" />
				(<?=$total['weekday'][$key]['cnt']*$key?>)
			<?}?>
			</td>
			<td <?=$masterURL?> class="ttime ver2"><?=$total['weekday'][$key]['cnt']?></td>
			<td <?=$masterURL?> class="ttime ver2"><?=($total['weekday'][$key]['cnt']*$key)?: ""?></td>
		<?}?>
		
		 <td <?=$masterURL?> class="ver1">
<?php
        if ($total['weekday']['rate']){
            $rates = $total['weekday']['rate'];

            if ($rates['minute']){
                $min = round(min($rates['minute']), 2);
                $max = round(max($rates['minute']), 2);
				$rate = "₪".($min == $max ? $min : $min . '-' . $max);
?>
			  <span><?=number_format($total['weekday']['time'] ?? 0)?> דקות <br style="mso-data-placement:same-cell;" />
			  <?=($rate)?> לדקה</span><br style="mso-data-placement:same-cell;" />
<?php
            }

            if ($rates['percent']){
                $min = round(min($rates['percent']), 2);
                $max = round(max($rates['percent']), 2);
				$rate = ($min == $max ? $min : $min . '-' . $max)."%";
?>
              <span><?=$rate?> עמלה </span>
<?php
            }
        }
?>
			  <span class="total">₪<?=number_format($total['weekday']['sum'] ?? 0, 1)?></span>
		  </td>
		   <td <?=$masterURL?> class="ver2"><?=$total['weekday']['time']?></td>
		   <td <?=$masterURL?> class="ver2"><?=$rate?></td>
		   <td <?=$masterURL?> class="ver2"><?=$total['weekday']['sum']?></td>
		
          <?
		if($treatTimes['weekend'])
		foreach($treatTimes['weekend'] as $key=>$ttime){?>
			<td <?=$masterURL?> class="ttime ver1">
			<?if($total['weekend'][$key]['cnt']){
				$sums['ttimes']['weekend'][$key] += $total['weekend'][$key]['cnt'];
	
				?>
				<b><?=$total['weekend'][$key]['cnt']?></b><br style="mso-data-placement:same-cell;" />
				(<?=$total['weekend'][$key]['cnt']*$key?>)
			<?}?>
			</td>
			<td <?=$masterURL?> class="ttime ver2"><?=$total['weekend'][$key]['cnt']?></td>
			<td <?=$masterURL?> class="ttime ver2"><?=($total['weekend'][$key]['cnt']*$key)?: ""?></td>
		<?}?>
		

		  <td <?=$masterURL?> class="ver1">
<?php
        if ($total['weekend']['rate']){
            $rates = $total['weekend']['rate'];

            if ($rates['minute']){
                $min = round(min($rates['minute']), 2);
                $max = round(max($rates['minute']), 2);
				$rate = "₪".($min == $max ? $min : $min . '-' . $max);
?>
			  <span><?=number_format($total['weekend']['time'] ?? 0)?> דקות <br style="mso-data-placement:same-cell;" />
			  <?=$rate?> לדקה</span><br style="mso-data-placement:same-cell;" />
<?php
            }

            if ($rates['percent']){
                $min = round(min($rates['percent']), 2);
                $max = round(max($rates['percent']), 2);
				$rate = ($min == $max ? $min : $min . '-' . $max)."%";
?>
              <span><?=$rate?> עמלה </span>
<?php
            }
        }

		$extras = ($extraPays[$masterID]['sumP'] ?? 0) + ($extraPays[$masterID]['sumM'] ?? 0);
        $forMaster = ($total['sum'] ?? 0) + $extras ;
        $forSite   = ($total['forSite'] ?? 0) - $extras;
?>
              <span class="total">₪<?=number_format($total['weekend']['sum'] ?? 0, 1)?></span>
		</td>
		<td <?=$masterURL?> class="ver2"><?=$total['weekend']['time']?></td>
		<td <?=$masterURL?> class="ver2"><?=$rate?></td>
		<td <?=$masterURL?> class="ver2"><?=$total['weekend']['sum']?></td>
		<td <?=$masterURL?> class="totalT">₪<?=number_format($extraPays[$masterID]['sumP'])?></td>
		<td <?=$masterURL?> class="totalT">₪<?=number_format($extraPays[$masterID]['sumM'])?></td>
		<td <?=$masterURL?> class="totalT">₪<?=number_format($forMaster, 1)?></td>
		<td <?=$masterURL?>>₪<?=number_format($forSite, 1)?></td>
		<td <?=$masterURL?>>
			<?=$tr_shifts[$masterID]['hours']+floor($tr_shifts[$masterID]['minutes']/60).":".$tr_shifts[$masterID]['minutes']%60;?><?=strlen(strval($tr_shifts[$masterID]['minutes']%60)==1)?>
		</td>		 
         <td <?=$masterURL?>>
		 	<?=$tr_shifts[$masterID]['date']? count($tr_shifts[$masterID]['date']) : ""?>
		 </td>
			
		 <!-- td>
            <label class="switch">
                <input style="" type="checkbox" name="paid" id="paid<?=$masterID?>">
                <span class="slider round"></span>
            </label>
         </td -->
      </tr>
<?php
        $sums['sum']     += $forMaster;
        $sums['forSite'] += $forSite;
        $sums['weekday'] += $total['weekday']['sum'] ?? 0;
        $sums['weekdayt'] += $total['weekday']['time'] ?? 0;
        $sums['weekend'] += $total['weekend']['sum'] ?? 0;
        $sums['weekendt'] += $total['weekend']['time'] ?? 0;
        $sums['cost']    += $total['cost'];
		$sums['count'] += $total['count'];
		$sums['extrasP'] += $extraPays[$masterID]['sumP'];
		$sums['extrasM'] += $extraPays[$masterID]['sumM'];
		$sums['hours'] += $tr_shifts[$masterID]['hours'];
		$sums['minutes'] += $tr_shifts[$masterID]['minutes'];
		$sums['shiftdays'] += $tr_shifts[$masterID]['date']? count($tr_shifts[$masterID]['date']) : 0;
    }
?>
      <tr style="position:sticky;bottom:0;z-index:1;background:white">
         <td style="line-height:1">סה"כ מטפלים: <?=$mtc?></td>
		 <?if(strpos($sid,',')){?><td></td><?}?>
         <td></td>
         <td></td>
         <td></td>
         <td><?=number_format($sums['count'])?></td>
         <td>₪<?=number_format($sums['cost'])?></td>
		 <?
		if($treatTimes['weekday'])
		foreach($treatTimes['weekday'] as $key=> $ttime){?>
			<td class="ttime ver1"><?=$sums['ttimes']['weekday'][$key]?></td>
			<td class="ttime ver2"><?=$sums['ttimes']['weekday'][$key]?></td>
			<td class="ttime ver2"><?=$sums['ttimes']['weekday'][$key]*$key?></td>
		<?}?>
         <td class="ver1">₪<?=number_format($sums['weekday'], 1)?></td>
         <td class="ver2"><?=number_format($sums['weekdayt'], 1)?></td>
         <td class="ver2"></td>
         <td class="ver2">₪<?=number_format($sums['weekday'], 1)?></td>
		 <?
		if($treatTimes['weekend'])
		foreach($treatTimes['weekend'] as $key => $ttime){?>
			<td class="ttime ver1"><?=$sums['ttimes']['weekend'][$key]?></td>
			<td class="ttime ver2"><?=$sums['ttimes']['weekend'][$key]?></td>
			<td class="ttime ver2"><?=$sums['ttimes']['weekend'][$key]*$key?></td>
		<?}?>
         <td class="ver1">₪<?=number_format($sums['weekend'], 1)?></td>
         <td class="ver2"><?=number_format($sums['weekendt'], 1)?></td>
         <td class="ver2"></td>
         <td class="ver2">₪<?=number_format($sums['weekend'], 1)?></td>
		 <td>₪<?=number_format($sums['extrasP'],1)?></td>
		 <td>₪<?=number_format($sums['extrasM'],1)?></td>
         <td>₪<?=number_format($sums['sum'], 1)?></td>
         <td>₪<?=number_format($sums['forSite'], 1)?></td>
		 <td><?=$sums['hours']+floor($sums['minutes']/60).":".$sums['minutes']%60;?>
         <td><?=$sums['shiftdays']?></td>
         <!-- td></td -->
      </tr>
   </tbody>
</table>
</div>
<script>
	function printData() {
            var styleToPrint = document.getElementById("theStyle");
            var divToPrint = document.getElementById("tableToPrint");
            newWin = window.open("");
            newWin.document.write(styleToPrint.outerHTML);
            newWin.document.write("<style>*{direction:rtl;font-family:'Arial';font-size:8px !important} td,th{ border: 1px solid black !important;padding: 2px !important;width: auto !important;}table{border-collapse:collapse}</style>")
            newWin.document.write(divToPrint.outerHTML);   
            newWin.print();	
            newWin.close();
            
	}

	
	$('#changever').on('change',function(){
		var _checked = $(this).is(':checked');
		
		$('#monthtotals_table .colspan').each(function(){
			//debugger;
			var _cols = $(this).prop('colspan');
			_cols = parseInt(_cols)		
			if(_checked){
				_cols = (_cols - 1)*2 + 3;
			}else{
				_cols = (_cols - 3)/2 + 1;
			}
			$(this).prop('colspan',_cols).attr('colspan',_cols);
		})
		
	})

    $('#expExcel').on('click', function(){
		$.when(
			$('table#monthtotals_table tr, table#monthtotals_table td, table#monthtotals_table th').each(function(){
				if($(this).is(":visible")){
					$(this).removeClass('noExl');
				}else{
					$(this).addClass('noExl');		
				}
		})
		).then(function(){				
			//return;
			var table = $('table#monthtotals_table');
			if(table && table.length){
				var preserveColors = (table.hasClass('table2excel_with_colors') ? true : false);
				$(table).table2excel({
					exclude: ".noExl",
					name: "Excel Document Name",
					filename: "report_manage" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
					fileext: ".xls",
					exclude_img: true,
					exclude_links: true,
					exclude_inputs: true,
					preserveColors: preserveColors
				});
			}
		});
    });

$('.orders_num').click(function(){
    var table = $(this).parents('table').eq(0)
    var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
    this.asc = !this.asc
    $(".orders_num").removeClass("o-ctrl");
	$(this).addClass('o-ctrl');
	if (!this.asc){
		rows = rows.reverse();
		$(this).removeClass('o-up');
		$(this).addClass('o-down');
	}else{		
		$(this).addClass('o-up');
		$(this).removeClass('o-down');
	}
    for (var i = 0; i < rows.length; i++){table.append(rows[i])}
})
function comparer(index) {
    return function(a, b) {
        var valA = getCellValue(a, index).replace(/[^\d.-]/g, ''), valB = getCellValue(b, index).replace(/[^\d.-]/g, '')
        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB)
    }
}
function getCellValue(row, index){ return $(row).children('td').eq(index).text() }
</script>