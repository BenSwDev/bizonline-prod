<?php
function hdate($date){
    return typemap(implode('-', array_reverse(explode('/', trim($date)))), 'date');
}

function db2dateD($date){
    return db2date($date, '.');
}


include "partials/setReportRange.php";

/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
$sid = intval($_GET['sid']) ?: $_CURRENT_USER->select_site();
if($sid && !$_CURRENT_USER->has($sid)){
    echo 'Access denied';
    return;
}

$title = "דוחות תקציב";


if($_GET['from']){
    $timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'])))),"date");
}else{
    $timeFrom = date("Y-m-01");
    $_GET['from'] = implode('/',array_reverse(explode('-',trim($timeFrom))));
}

if($_GET['to']){
    $timeUntil = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'])))),"date");
}else{
    $timeUntil = date("Y-m-t");
    $_GET['to'] = implode('/',array_reverse(explode('-',trim($timeUntil))));
}

if($_GET['timeType']==3){
	//Search by charging dates
	//SET pays for Next stage, Sets order ID's 
	$arr_keys = array('orderID','lineID');
	$pays = udb::key_row($que, $arr_keys);
	
	$que = "SELECT `orderID`,`lineID`,`startTime`, `sum` AS `total`, CONCAT_WS('@@', `payType`, `provider`)  AS `ppType`, inputData 
			FROM `orderPayments` 
			INNER JOIN orders USING (orderID)
			WHERE `complete` = 1 AND `cancelled` = 0 AND `subType` NOT IN ('card_test', 'freeze_sum') 
			AND `orderPayments`.`startTime`>= '".$timeFrom." 00:00:00'
			AND `orderPayments`.`startTime`<= '".$timeUntil." 23:59:59'
			AND `orders`.siteID IN (" . $_CURRENT_USER->sites(true) . ")";
	//echo $que;
	$arr_keys = array('orderID','lineID');
	$pays = udb::key_row($que, $arr_keys);
	//print_r($pays);
	$orderIDs = array_unique(array_keys($pays));
	//echo $que;
}

if ($_CURRENT_USER->is_spa()){

    $que = "SELECT `sites`.`siteName`,`orders`.*
            , MIN(IF(T_orders.orderID IS NOT NULL,IF(T_orders.timeFrom = '0000-00-00 00:00:00', NULL, T_orders.timeFrom),`orders`.timeFrom)) AS `timeFrom`
            , MAX(T_orders.timeUntil) AS `timeUntil`
            , SUM(IF(T_orders.parentOrder = T_orders.orderID, 0, T_orders.price)) AS `treatCost`
            , SUM(orderUnits.base_price) AS `roomCost`
            , GROUP_CONCAT(rooms_units.unitName SEPARATOR ', ') AS `unitNames`
            , GROUP_CONCAT(treatments.treatmentName SEPARATOR ', ') AS `treatmentsNames`
            , GROUP_CONCAT(orders.treatmentLen SEPARATOR ', ') AS `treatmentsLen`
            , GROUP_CONCAT(DISTINCT DATE(T_orders.timeFrom) SEPARATOR '#') AS `treatDates`
            , bus.buserID AS `agent_buserID`
            , bus.name AS `userName`
			, COUNT(T_orders.treatmentID) as `countTreatments`
		FROM `orders` 			
            LEFT JOIN orders AS T_orders ON (T_orders.parentOrder = orders.orderID AND T_orders.parentOrder <> T_orders.orderID)
            LEFT JOIN `orderUnits` ON(T_orders.`orderID` = `orderUnits`.`orderID`)
            LEFT JOIN `rooms_units` USING(`unitID`)
            LEFT JOIN `sites` ON (orders.siteID = sites.siteID)
            LEFT JOIN treatments ON (T_orders.treatmentID = treatments.treatmentID)
            LEFT JOIN `bu_action_log` AS `bu` ON (orders.orderID = bu.orderID AND bu.actionType = 'order')
            LEFT JOIN `biz_users` AS `bus` ON (bus.buserID = bu.buserID)
		WHERE orders.allDay=0  AND orders.parentOrder = orders.orderID  " ;
 
		$tblName = '`T_orders`';
		$group = 'parentOrder';

        $timeType = ($_GET['timeType'] == 2) ? "orders.create_date" : "timeFrom";

        if ($_GET['timeType'] == 2){
            $que .= " AND orders.create_date >= '" . $timeFrom . " 00:00:00' AND orders.create_date <= '" . $timeUntil . " 23:59:59'";
        }
        else if($_GET['timeType']==3) {
			$que .= " AND orders.orderID IN (".implode(',',$orderIDs).")";
		}else{
			$pp_que =	"SELECT DISTINCT `parentOrder` 
						FROM `orders` 
						WHERE (
							(`parentOrder` > 0 AND `parentOrder` <> `orderID` AND `timeFrom` >= '" . $timeFrom . " 00:00:00' AND `timeFrom` <= '" . $timeUntil . " 23:59:59')
						OR
							(`parentOrder` > 0 AND `parentOrder` = `orderID` AND `timeFrom` >= '" . $timeFrom . " 00:00:00' AND `timeFrom` <= '" . $timeUntil . " 23:59:59')
						)
						AND `siteID` IN (" . ($sid ?: $_CURRENT_USER->sites(true)) . ")";
            $pps = udb::single_column($pp_que);
            $que .= " AND orders.`orderID` IN (" . implode(',', $pps ?: [0]) . ")";
        }
}else{
    $timeType = ($_GET['timeType'] == 2) ? "orders.create_date" : "orders.timeFrom";

    $que = "SELECT `sites`.`siteName`, `orders`.*, GROUP_CONCAT(ru.unitName SEPARATOR ', ') AS `unitNames`
		FROM `orders` 
		LEFT JOIN `orderUnits` AS `u` USING(`orderID`)
		LEFT JOIN `rooms_units` AS `ru` USING(`unitID`)
		LEFT JOIN `sites` ON (`orders`.`siteID` = `sites`.`siteID`)
		WHERE allDay=0 AND " . $timeType . " >= '" . $timeFrom . " 00:00:00' AND " . $timeType . " <= '" . $timeUntil . " 23:59:59'";

		$tblName = '`orders`';
		$group = 'orderID';
}

if ($sid && in_array($sid, $_CURRENT_USER->sites()))
    $que .= " AND `orders`.siteID = " . $sid;
else
    $que .= " AND `orders`.siteID IN (" . $_CURRENT_USER->sites(true) . ")";


    //$que .= " AND `orders`.`status` = 1";
    $title .= " פעילות";



if(!$_GET['sort']) $_GET['sort'] = "arrive";

//if($_GET['from']){
//	$timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'])))),"date");
//}else{
//	$timeFrom = date("Y-m-01");
//	$_GET['from'] = implode('/',array_reverse(explode('-',trim($timeFrom))));
//}
//
//	$que.=" AND ".$timeType." >= '".$timeFrom." 00:00:00'";
//
//
//if($_GET['to']){
//	$timeUntil = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'])))),"date");
//}else{
//	$timeUntil = date("Y-m-t");
//	$_GET['to'] = implode('/',array_reverse(explode('-',trim($timeUntil))));
//}
//
//	$que.=" AND ".$timeType." <= '".$timeUntil." 23:59:59'";

if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){

    $list = [$tblName.'.customerName', $tblName.'.customerEmail', $tblName.'.customerPhone', $tblName.'.customerTZ','orders.orderIDBySite'];
    $que .= " AND (" . implode(" LIKE '%" . $freeText . "%' OR ", $list) . " LIKE '%" . $freeText . "%')";
}


    $que .= " AND orders.orderType = 'order'";

$que.= " GROUP BY orders.".$group." ORDER BY ".$timeType." ".($_GET['sort'] == 'arrive' ?  'ASC' : ' DESC');
if($orderIDs || $_GET['timeType']!=3){
	$orders = udb::key_row($que, 'orderID');
}
//print_r($orders);
//echo $que;



$payTypeSums = [];

if ($orders){
    if(!$pays){
		$que = "SELECT `orderID`,`lineID`,`startTime`, `sum` AS `total`, CONCAT_WS('@@', `payType`, `provider`)  AS `ppType`, inputData 
				FROM `orderPayments`
				WHERE `orderID` IN (" . implode(',', array_keys($orders)) . ") 
				AND `complete` = 1 AND `cancelled` = 0 AND `subType` NOT IN ('card_test', 'freeze_sum')";
				
		//echo $que;
		$arr_keys = array('orderID','lineID');
		$pays = udb::key_row($que, $arr_keys);
		//print_r($pays);
	}
	//print_r(array_diff_key($pays, $orders));
	foreach($orders as $id => &$order){		
		if(($pays[$id])){
			$paycnt=0;
			$is2nd = "";
			foreach($pays[$id] as $pay){
				$order['ppType'] .= $is2nd.$pay['ppType'];
				$order['paid'] += $pay['total'] ?? 0;
				$order['pays'][$pay['lineID']]['ppType'] = $pay['ppType'] ?? '';		
				$order['pays'][$pay['lineID']]['total'] = $pay['total'] ?? 0;		
				$order['pays'][$pay['lineID']]['date'] = $pay['startTime'] ?? 0;		
				$tmp = json_decode($pay['inputData'], true);
				//echo($pay['inputData']);
				$order['pays'][$pay['lineID']]['cpn'] = $tmp['cpn'];
				$is2nd = "|";
				$payKeys[$pay['lineID']] = $pay['lineID'];
			}
		}
		
    }
    unset($pays, $order);
	if($_GET['timeType']==3){
		$fromKeys = "AND `lineID` IN (" . implode(',', array_keys($payKeys)) . ")";
	}else{
		$fromKeys = "AND `orderID` IN (" . implode(',', array_keys($orders)) . ")";
	}
    // skipping "deleted" orders without any payments
    foreach($orders as $id => $order)
        if (!$order['status'] && !$order['paid'])
            unset($orders[$id]);

    $que = "SELECT CONCAT_WS('@@', `payType`, `provider`) AS `pt`, SUM(`sum`) AS `total`, COUNT(*) AS `cnt` 
            FROM `orderPayments` 
            WHERE `complete` = 1 AND `cancelled` = 0 AND `subType` NOT IN ('card_test', 'freeze_sum')  ".$fromKeys."
            GROUP BY CONCAT_WS('-', `payType`, `provider`)
            ORDER BY NULL";
    $payTypeSums = udb::key_row($que, 'pt');
}

//possible classes: inputWrap, checkWrap


//include "partials/orders_menu.php"; 
?>
<?
selectReportRange();
?>

<div class="searchOrder">
	<div class="ttl" style="cursor:pointer;margin:-10px;padding:10px" onclick="$('#searchForm').toggleClass('hide');">חפש הזמנות לפי תאריך</div>
	<form method="GET" autocomplete="off" action="" class="hide"  id="searchForm">
		<input type="hidden" name="page" value="<?=typemap($_GET['page'] ?? 'orders', 'string')?>" />
        <input type="hidden" name="otype" value="<?=typemap($_GET['otype'] ?? 'order', 'string')?>" />
<?php
    if (!$_CURRENT_USER->single_site){
        $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
        <div class="inputWrap">
            <select name="sid" id="sid" title="שם מתחם">
                <option value="0">כל המתחמים</option>
<?php
        foreach($sname as $id => $name)
            echo '<option value="' , $id , '" ' , ($id == $sid ? 'selected' : '') , '>' , $name , '</option>';
?>
            </select>
		</div>
<?php
    }
?>
        <div class="inputWrap">
            <select name="timeType" id="otype" title="">
                
                <option value="1" <?=($_GET['timeType'] == '1' ? 'selected' : '')?>>לפי תאריך הגעה</option>
                <option value="2" <?=($_GET['timeType'] == '2' ? 'selected' : '')?>>לפי תאריך רכישה</option>
                <option value="3" <?=($_GET['timeType'] == '3' ? 'selected' : '')?>>לפי תאריך חיוב</option>
            </select>
        </div>
		<div class="inputWrap">
			<input type="text" name="from" placeholder="מתאריך" class="searchFrom" value="<?=typemap($_GET['from'], 'string')?>" readonly>
		</div>
		<div class="inputWrap">
			<input type="text" name="to" placeholder="עד לתאריך" value="<?=typemap($_GET['to'], 'string')?>" class="searchTo" readonly>
		</div>
		
		<div class="inputWrap">
            <select name="sort" id="sort">                
                <option value="arrive" <?=($_GET['sort'] == 'arrive' ? 'selected' : '')?>>תאריך רחוק לקרוב</option>
                <option value="past" <?=($_GET['sort'] == 'past' ? 'selected' : '')?>>תאריך קרוב לרחוק</option>
            </select>
		</div>
        <div class="inputWrap">
            <input type="text" name="free" placeholder="חיפוש חופשי" value="<?=$freeText?>" />
        </div>

		<a class="clear" href="?page=<?=$_GET['page']?>">נקה</a>
		<input type="submit" value="חפש">
		
	</form>	
</div>
<?if(count($orders)>1000){
	for($i=500;$i<count($orders);$i+=500){?>
		<style id="loadstage<?=$i?>">
		  .reports tr.order:nth-child(n+<?=$i?>){display:none}
		</style>
		<script>
			$(function(){
				setTimeout(function(){
					$('#loadstage<?=$i?>').remove();
				},<?=$i*2?>)
			});
		</script>
	<?}
	}		
?>
<section class="orders" style="max-width:none">
	<div class="last-orders">
		<div class="title"><?=$title?>
			<?if($orderIDs){?>
			<br style="mso-data-placement:same-cell;" />
			<b style="font-size:16px;color:red">שימו לב - חיובים שבוצעו מחוץ לטווח התאריכים הנבחר אינם מוצגים בדו"ח</b>
			<?}?>
			<div id="btns-reports" class="btns">
				<div id="b-makor" onclick="show_makor()">מקורות הגעה</div>
				<div id="b-paytypes" onclick="show_paytypes()">אמצעי תשלום</div>
				<div id="b-agents" onclick="show_agents()">סוכנים</div>
				<div id="b-all" onclick="show_all()" class='active'>מקיף</div>
			</div>
			
			<div class="excel" id="expExcel">ייצוא לאקסל</div>
			<div class="excel" onclick="printData()">הדפסה</div>
		</div>
		<div class="report-container" id="tableToPrint">
			
			<input type="checkbox" style="display:none" id="changever">
			<table class="reports sticky2" id="reports">
				<thead>
				<tr class='sticky top'>
					<th>מספר הזמנה</th>
					<th>שם המזמין</th>
					<th>טלפון</th>
					<th>תאריך רכישה</th>
					<th>תאריך הגעה</th>
					<th class="orders_num">כמות  טיפולים</th>
					<th class="orders_num">עלות<br style="mso-data-placement:same-cell;" />טיפולים</th>
                    <th class="orders_num">עמלת מטפלים</th>
					<th class="orders_num">הכנסה מטיפולים</th>
					<th class="orders_num">הכנסה מחדרים</th>
					<th class="orders_num">הכנסה מתוספות</th>
                    <th class="orders_num">מחיר מחירון</th>
                    <th class="orders_num">הנחה</th>
					<th>עלות בפועל<br />(לתשלום)</th>
					<th>מחיר ממוצע</th>
					<!-- th>סה"כ רווח</th -->
					<th class="orders_num">שולם</th>
					<th class="orders_num">נותר לתשלום</th>
                    <th>סוכן</th>
                    <th style="min-width:90px">מקור הגעה</th>
                    <th style="min-width:90px" class="ver1">
						פרטי התשלום<br style="mso-data-placement:same-cell;" />
						<label class="changever" for="changever">בעמודות</label>
					</th>
					<th style="min-width:120px" class="ver2">
						אמצעי תשלום<br style="mso-data-placement:same-cell;" />
						<label class="changever" for="changever">בעמודות</label>
					</th>
					<th class="ver2">קוד קופון</th>
					<th class="ver2">תאריך</th>
					<th class="ver2">סכום</th>
				</tr>
				</thead>
				<tbody>

<?php
        UserUtilsNew::init();

        $mcache = $a_name = $m_name = [];
		//$cuponTypes = array_merge(UserUtilsNew::$CouponsfullList,UserUtilsNew::guestMember(),UserUtilsNew::otherSources());
        $cuponTypes = UserUtilsNew::$CouponsfullList + UserUtilsNew::guestMember() + UserUtilsNew::otherSources();
		$cuponTypes['online'] = 'הזמנה אונליין';
        foreach($orders as $order) {
            $que = "SELECT orders.timeFrom, orders.therapistID, orders.treatmentLen, orders.price FROM `orders` WHERE orders.orderID <> orders.parentOrder AND `parentOrder` = " . $order['parentOrder'];
            $tipulim = udb::single_list($que);

            $totalPay = 0;
            foreach($tipulim as $sub){
                if($sub['therapistID']){
					$mcache[$sub['therapistID']] = $master = $mcache[$sub['therapistID']] ?? new SalaryMaster($sub['therapistID']);
					//$pay = $master->get_treat_pay($sub['timeFrom'], $sub['treatmentLen'], $sub['price']);
					//$totalPay += $pay['total'];
					$pay = $master->get_order_salary(substr($sub['timeFrom'], 0, 10), $sub['treatmentLen'], $sub['price']);
					$totalPay += $pay;
				}else{
					//echo "problem";
					//print_r($sub);
				}

                
            }

            $extraCost = 0;
            if ($order['extras']){
                $tmp = json_decode($order['extras'], true);
                $extraCost = $tmp['total'];
            }
            $payTypes = explode('|', $order['ppType']);
			
            $payTypes = $payTypes ? implode('<br />', array_unique(array_map(function ($str){return  UserUtilsNew::method(...explode('-', $str));}, $payTypes))) : '';
?>
				<tr class="item order isSpa" data-orderid='<?=$order['orderID']?>' data-makorid='<?=$order['sourceID']?>' data-userid='<?=$order['agent_buserID']?>' data-paytypes="<?=$order['ppType']?>">
					<td class="f"><?=$order['orderIDBySite']?></td>
					<td class="f rtl"><?=$order['customerName']?></td>
					<td class="f small"><?=$order['customerPhone']?></td>
					<td class="f small"><?=date('d.m.y', strtotime($order['create_date']))?></td>
					<!-- td class="f"><?=date('d.m.y', strtotime($order['timeFrom']))?></td -->
                    <td class="f small">
						<?if($order['treatDates']){
							echo implode('<br style="mso-data-placement:same-cell;"/>', array_map('db2dateD', explode('#', $order['treatDates'])));
						}else if(intval(substr($order['timeFrom'],0,4))) {						
							echo implode('.',array_reverse(explode("-", explode(" ",$order['timeFrom'])[0])));
						}else{
							echo "ללא תאריך";
						}
						?>
					</td>
					<td class="f"><?=number_format($order['countTreatments'])?></td>
					<td class="f"><?=number_format($order['treatCost'])?></td>
                    <td class="f"><?=number_format($totalPay)?></td>
					<td class="f"><?=number_format($order['treatCost'] - $totalPay)?></td>
                    <td class="f"><?=number_format($order['roomCost']) ?></td>
					<td class="f"><?=number_format($extraCost)?></td>
                    <td class="f"><?=number_format($order['price'] + $order['discount'])?></td>
                    <td class="f"><?=number_format($order['discount'])?></td>
					<td class="f number"><?=number_format($order['price'])?></td>
					<td class="f number">
						<?if($order['countTreatments']){?>
						<div>לטיפול <?=number_format($order['price']/$order['countTreatments'])?></div>
						<?}?>
					</td>
					<!-- td class="f number"><?=number_format($order['price'] - $totalPay)?></td -->
					<td class="f number"><?=number_format($order['paid'])?></td>
					<td class="f number"><?=number_format($order['price'] - $order['paid'])?></td>
                    <td><?=$order['userName']?></td>
                    <td><?=$order['sourceID']? $cuponTypes[$order['sourceID']] : "הזמנה רגילה"?></td>
                    <td class="tbl ver1"><?//=$payTypes?><?//print_r($payTypesIDs)?>
					<?if($order['pays']){?>
						<span class="pays">
						<?foreach($order['pays'] as $key => $orderPay){
							$m_totalPayTypeBySource[$order['sourceID']][$orderPay['ppType']] += $orderPay['total'];
							?>
						
							<span class="payments"  data-paytypes="<?=$orderPay['ppType']?>">
								<span><?=(UserUtilsNew::method(...explode('@@',$orderPay['ppType'])))?><b><?=$orderPay['cpn']?></b></span>
								<span><?=substr($orderPay['date'],8,2)?>.<?=substr($orderPay['date'],5,2)?>.<?=substr($orderPay['date'],2,2)?><b>₪<?=number_format($orderPay['total'])?></b></span>
							</span>
							<br style="mso-data-placement:same-cell;" />
						<?php } ?>						
						</span>
					<?php } ?>
					<?/*if($order['pays']){
								?>
						<table class="pays">
						<?foreach($order['pays'] as $key => $orderPay){?>
							<tr class="payments" data-paytypes="<?=$orderPay['ppType']?>">
								<td><?=(UserUtilsNew::method(...explode('-',$orderPay['ppType'])))?><br style="mso-data-placement:same-cell;" /><b><?=$orderPay['cpn']?></b></td>
								<td><?=substr($orderPay['date'],8,2)?>.<?=substr($orderPay['date'],5,2)?>.<?=substr($orderPay['date'],2,2)?><br style="mso-data-placement:same-cell;" /><b>₪<?=number_format($orderPay['total'])?></b></td>
							</tr>
						<?}?>
						</table>
					<?}*/?>
					</td>
					<?
					$paysArr = [];
					if($order['pays']){						
						foreach($order['pays'] as $key => $orderPay){
							$paysArr['ppType'][] = (UserUtilsNew::method(...explode('@@',$orderPay['ppType'])));
							$paysArr['cpn'][]	 = $orderPay['cpn'];
							$paysArr['date'][]	 = substr($orderPay['date'],8,2).".".substr($orderPay['date'],5,2).".".substr($orderPay['date'],2,2);
							$paysArr['total'][]	 = $orderPay['total'];
						}
					}?>
					<td class="ver2"><sptt><?=implode('</span><br/></sptt><sptt>',$paysArr['ppType'] ?: [])?></sptt></td>
					<td class="ver2"><sptt><?=implode('</span><br/></sptt><sptt>',$paysArr['cpn'] ?: [])?></sptt></td>
					<td class="ver2"><sptt><?=implode('</span><br/></sptt><sptt>',$paysArr['date'] ?: [])?></sptt></td>
					<td class="ver2"><sptt><?=implode('</span><br/></sptt><sptt>',$paysArr['total'] ?: [])?></sptt></td>

				</tr>
				
				<?

				/*****************************Total Lines Create **********************************/
				$totalOrders ++;
				$totalTreatCost += $order['treatCost'];
				$totalpayAll += $totalPay;
				$totalTreatIncome += $order['treatCost'] - $totalPay;
				$totalExtraIncome += $order['roomCost'] + $extraCost;
				$totalRooms += $order['roomCost'];
				$totalExtras += $extraCost;
				$totalMechiron += $order['treatCost'] + $order['roomCost'] + $extraCost;
				$totalDiscount += $order['treatCost'] + $order['roomCost'] + $extraCost - $order['price'];
				$totalPrice +=$order['price'];
				$totalBalance += ($order['price'] - $order['paid']);
				$totalPaid +=$order['paid'];
				$totalTreatments += $order['countTreatments'];

				/***********************************************************************************/
				
				/***************************** Total Agents Lines Create **********************************/

				$a_name[$order['agent_buserID']] = $order['userName'];
				$a_totalOrders[$order['agent_buserID']] ++;
				$a_totalTreatCost[$order['agent_buserID']] += $order['treatCost'];
				$a_totalpayAll[$order['agent_buserID']] += $totalPay;
				$a_totalTreatIncome[$order['agent_buserID']] += $order['treatCost'] - $totalPay;
				$a_totalExtraIncome[$order['agent_buserID']] += $order['roomCost'] + $extraCost;				
				$a_totalRooms[$order['agent_buserID']] += $order['roomCost'];
				$a_totalExtras[$order['agent_buserID']] += $extraCost;
				$a_totalMechiron[$order['agent_buserID']] += $order['treatCost'] + $order['roomCost'] + $extraCost;
				$a_totalDiscount[$order['agent_buserID']] += $order['treatCost'] + $order['roomCost'] + $extraCost - $order['price'];
				$a_totalPrice[$order['agent_buserID']] +=$order['price'];
				$a_totalBalance[$order['agent_buserID']] += ($order['price'] - $order['paid']);
				$a_totalPaid[$order['agent_buserID']] +=$order['paid'];
				$a_totalTreatments[$order['agent_buserID']] += $order['countTreatments'];


				
				/***********************************************************************************/
				
				/***************************** Total payType Lines Create **********************************/

				
				
				/***********************************************************************************/



				
				/***************************** Total Source Lines Create **********************************/
				
				

				$m_name[$order['sourceID']] = $order['sourceID']? $cuponTypes[$order['sourceID']] : "הזמנה רגילה";
				$m_totalOrders[$order['sourceID']] ++;
				$m_totalTreatCost[$order['sourceID']] += $order['treatCost'];
				$m_totalpayAll[$order['sourceID']] += $totalPay;
				$m_totalTreatIncome[$order['sourceID']] += $order['treatCost'] - $totalPay;
				$m_totalExtraIncome[$order['sourceID']] += $order['roomCost'] + $extraCost;				
				$m_totalRooms[$order['sourceID']] += $order['roomCost'];
				$m_totalExtras[$order['sourceID']] += $extraCost;
				$m_totalMechiron[$order['sourceID']] += $order['treatCost'] + $order['roomCost'] + $extraCost;
				$m_totalDiscount[$order['sourceID']] += $order['treatCost'] + $order['roomCost'] + $extraCost - $order['price'];
				$m_totalPrice[$order['sourceID']] +=$order['price'];
				$m_totalBalance[$order['sourceID']] += ($order['price'] - $order['paid']);
				$m_totalPaid[$order['sourceID']] +=$order['paid'];
				$m_totalTreatments[$order['sourceID']] += $order['countTreatments'];

				
				/***********************************************************************************/



				

        }

		
        foreach($a_name as $key =>  $name ){
			if(!$a_totalTreatments[$key])
				$a_totalTreatments[$key] = 1;
?>
				<tr class='agents' data-userid="<?=$key?>">
					<td><b><?=$name?></b><br style="mso-data-placement:same-cell;" /><?=$a_totalOrders[$key]?> הזמנות</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td><?=number_format($a_totalTreatments[$key]);?></td>
					<td>₪<?=number_format($a_totalTreatCost[$key]);?><div>ממוצע ₪<?=number_format(($a_totalTreatCost[$key]/$a_totalTreatments[$key]),2);?></div></td>
					<td>₪<?=number_format($a_totalpayAll[$key]);?></td>
					<td>₪<?=number_format($a_totalTreatIncome[$key]);?></td>
					<td>₪<?=number_format($a_totalRooms[$key]);?></td>
					<td>₪<?=number_format($a_totalExtras[$key]);?></td>
                    <td>₪<?=number_format($a_totalMechiron[$key]);?></td>
                    <td>₪<?=number_format($a_totalDiscount[$key]);?></td>
					<td>₪<?=number_format($a_totalPrice[$key])?></td>
					<td>
						<div>להזמנה ₪<?=number_format(($a_totalPrice[$key]/$a_totalOrders[$key]),2);?></div>
						<div>לטיפול ₪<?=number_format(($a_totalPrice[$key]/$a_totalTreatments[$key]),2);?></div>
					</td>
					<td>₪<?=number_format($a_totalPaid[$key])?></td>
					<td>₪<?=number_format($a_totalBalance[$key])?></td>
                    <td><?=$name?></td>
                    <td></td>
                    <td class="ver1"></td>
                </tr>
<?php
        }

		foreach($m_name as $key =>  $name ){
			if(!$m_totalTreatments[$key])
				$m_totalTreatments[$key] = 1;
?>
				<tr class='makor' data-makorid="<?=$key?>">
					<td><b><?=$name?></b><br style="mso-data-placement:same-cell;" /><?=$m_totalOrders[$key]?> הזמנות</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td><?=number_format($m_totalTreatments[$key]);?></td>
					<td>₪<?=number_format($m_totalTreatCost[$key]);?><div>ממוצע ₪<?=number_format(($m_totalTreatCost[$key]/$m_totalTreatments[$key]),2);?></div></td>
					<td>₪<?=number_format($m_totalpayAll[$key]);?></td>
					<td>₪<?=number_format($m_totalTreatIncome[$key]);?></td>
					<td>₪<?=number_format($m_totalRooms[$key]);?></td>
					<td>₪<?=number_format($m_totalExtras[$key]);?></td>
                    <td>₪<?=number_format($m_totalMechiron[$key]);?></td>
                    <td>₪<?=number_format($m_totalDiscount[$key]);?></td>
					<td>₪<?=number_format($m_totalPrice[$key])?></td>
					<td>
						<div>להזמנה ₪<?=number_format(($m_totalPrice[$key]/$m_totalOrders[$key]),2);?></div>
						<div>לטיפול ₪<?=number_format(($m_totalPrice[$key]/$m_totalTreatments[$key]),2);?></div>
					</td>
					<td>₪<?=number_format($m_totalPaid[$key])?></td>
					<td>₪<?=number_format($m_totalBalance[$key])?></td>
                    <td></td>
                    <td><?=$name?></td>
                    <td class="ver1">
						<?if($m_totalPayTypeBySource[$key]){?>
						<span class="pays">
						<?foreach($m_totalPayTypeBySource[$key] as $key => $orderPay){?>
						
							<span class="payments"  data-paytypes="<?=$orderPay['ppType']?>">
								<span><?=(UserUtilsNew::method(...explode('-', $key)) ?: $key)?></span>
								<span><b>₪<?=number_format($orderPay)?></b></span>
							</span>
							<br style="mso-data-placement:same-cell;" />
						<?php } ?>						
						</span>
					<?php } ?>
                    </td>
                </tr>
<?php
        }

        foreach($payTypeSums as $pt => $prow ){
				$totalProw += $prow['total'];
?>
				<tr class='payTypes' data-paytype="<?=$pt?>">
					<td><b><?=(UserUtilsNew::method(...explode('@@', $pt)) ?: $pt)?></b><br style="mso-data-placement:same-cell;" /><?=$prow['cnt']?> הזמנות</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
                    <td></td>
                    <td></td>
					<td></td>					
					<td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="ver1"></td>
					<td>₪<?=number_format($prow['total'])?></td>
                </tr>
<?php
        }
			if($totalOrders){
				if(!$totalTreatments)
					$totalTreatments = 1;
?>
				<tr class='sticky bottom total' id="totalall">
					<td>סה"כ <?=$totalOrders?></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td><?=number_format($totalTreatments);?></td>
					<td>₪<?=number_format($totalTreatCost);?><div>ממוצע ₪<?=$totalTreatments? number_format(($totalTreatCost/$totalTreatments),2) : "";?></div></td>
					<td>₪<?=number_format($totalpayAll);?></td>
					<td>₪<?=number_format($totalTreatIncome);?></td>
					<td>₪<?=number_format($totalRooms);?></td>
					<td>₪<?=number_format($totalExtras);?></td>
                    <td>₪<?=number_format($totalMechiron);?></td>
                    <td>₪<?=number_format($totalDiscount);?></td>
					<td>₪<?=number_format($totalPrice)?></td>
					<td>
						<div>להזמנה ₪<?=$totalTreatments? number_format(($totalPrice/$totalOrders),2) : "";?></div>
						<div>לטיפול ₪<?=$totalTreatments? number_format(($totalPrice/$totalTreatments),2) : "";?></div>
					</td>
					<td>₪<?=number_format($totalPaid)?></td>
					<td>₪<?=number_format($totalBalance)?></td>
                    <td></td>
                    <td></td>
                    <td class="ver1">₪<?=number_format($totalProw)?></td>
                    <td class="ver2"></td>
                    <td class="ver2"></td>
                    <td class="ver2"></td>
                    <td class="ver2"><?=$totalProw?></td>
                </tr>
			<?}?>
			</table>
		</div>
	</div>
</section>
<?//print_r($m_totalPayTypeBySource)?>
<style id="theStyle">
.item.order.isSpa td.f {direction:ltr; text-align:right}
.item.order.isSpa td.f.rtl {direction:rtl}
.item.order.isSpa td.small {font-size:12px}

.orders_num.o-ctrl {background: #c9f2fd;}
.orders_num {cursor: pointer;position: relative;}
.orders_num.o-ctrl::after {opacity: 1;}
.orders_num.o-up::after {opacity: 0.2;}
.orders_num::after {content: "";width: 6px;height: 6px;box-sizing: border-box;border-left: 2px black solid;border-bottom: 2px black solid;display: block;position: absolute;bottom: 0;margin: 0 auto;left: 0;right: 0;transform: rotate(-45deg);opacity: 0;}
.orders_num.o-down::after {opacity: 0.5;transform: rotate(135deg);}

.reports  td div{white-space:nowrap}


.report-container{max-height:calc(100vh - 250px);overflow:auto;padding-left:20px;clear:both;position:relative;top:10px}
.reports{font-size:14px;border-collapse:collapse}
.reports td, .reports th{padding:5px;vertical-align:middle;border:1px #ccc solid;text-align:right}
.reports td.tbl{padding:0}
.reports td:nth-child(1), .reports th:nth-child(1){position:sticky;right: 0; z-index: 1;background:white;}
.reports.sticky2 td:nth-child(2), .reports.sticky2 th:nth-child(2){position:sticky;right: 50px; z-index: 1;background:white;}
.reports.sticky2 td:nth-child(2){background:#f5f5f5}
.reports td.number{direction:ltr}
.reports .sticky{position:sticky;background:white;z-index:2}
.reports .sticky.top{top:0}
.reports .sticky.bottom{bottom:0}
.reports .bottom td{font-weight:bold}
.reports tr:hover{background:#cfeef0;cursor:pointer}
.reports .agents{display:none}
.reports .makor{display:none}
.reports .payTypes{display:none}
.reports .total{display:table-row }
.reports td > div{font-size:12px;font-weight:normal}
.last-orders .btns {float: left;}
.last-orders .btns > div {display: inline-block;font-size: 16px;line-height: 34px;padding: 0 20px;margin: 0 2px;border: 1px #0dabb6 solid;color: #999;font-weight: normal;background: white;border-radius: 10px;cursor: pointer;}
.last-orders .btns > div.active {color: white;background: #0dabb6;}

.pays{border-spacing:0;border-collapse:collapse;width:100%;min-height:44px;display:table}
.pays .payments{display:table-row;border:1px #ccc solid}
.pays .payments > span{display:table-cell;border:1px #ccc solid;padding:2px 4px}
.pays .payments > span:nth-child(2){width:50px;max-width:50px;min-width:56px}
.pays br{display:none}
.pays b{display:block}


.excel {line-height: 44px;margin: 10px 5px;display: inline-block;font-size: 16px;color: #0dabb6;background: white;border: 1px#0dabb6 solid;padding: 0 10px;cursor: pointer;border-radius: 10px;}

#changever:not(:checked) ~ .reports.sticky2 .ver2{display:none}
.reports:not(.sticky2) .ver2{display:none}
#changever:checked ~ .reports.sticky2 .ver1{display:none}
.reports:not(.sticky2) .changever {display:none}
.changever{display:flex;align-items:center;font-size:12px;font-weight:normal;height:30px;cursor:pointer}
.changever::before{content:"";display:block;width:14px;height:14px;margin-left:4px;border:2px solid black;box-shadow:0 0 0 2px white inset}
#changever:checked ~ .reports .changever::before{background:#0dabb6}

sptt{display:flex;min-height:30px;align-items:center;line-height:1;font-size:12px}
sptt:nth-child(n+2){border-top:1px #ccc solid;}
@media(max-width:992px){

.reports td:nth-child(1), .reports th:nth-child(1){position:relative;}
.reports.sticky2 td:nth-child(2), .reports.sticky2 th:nth-child(2){right: 0;}


}

</style>

<script>

	function printData() {
            var styleToPrint = document.getElementById("theStyle");
            var divToPrint = document.getElementById("tableToPrint");
            newWin = window.open("");
            newWin.document.write(styleToPrint.outerHTML);
            newWin.document.write("<style>.report-container{max-height:none} *{direction:rtl;font-family:'Arial';font-size:8px !important} td,th{ border: 1px solid black !important;padding: 2px !important;width: auto !important;}table{border-collapse:collapse}</style>")
            newWin.document.write(divToPrint.outerHTML);   
            newWin.print();	
            newWin.close();
            
	}

$(function() {
	$('table tr').each(function() {
		if(!$(this).is(':visible'))
			$(this);
	})
})
$('#expExcel').on('click', function(e){
	e.preventDefault();
	
	$.when(
		$('table#reports tr, table#reports td, table#reports th').each(function(){
			if($(this).is(":visible")){
				$(this).removeClass('noExl');
			}else{
				$(this).addClass('noExl');		
			}
	})
	).then(function(){	
		//return;
		var table = $('table#reports');
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
        // window.location.href = 'ajax_excel_reports_manage.php' + window.location.search;
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


$('.reports .agents td').click(function(){
	sticky2(1);
	//debugger;
	var uid = $(this).parent().data('userid');
	$('.reports tbody tr').css('display','none');
	$(".reports tr[data-userid='"+uid+"']").css('display','table-row');
	$(this).parent().addClass('sticky bottom');
	$(".reports tbody .tbl .pays .payments").css('display','table-row');
});

$('.reports .makor td').click(function(){
	sticky2(1);
	//debugger;
	var uid = $(this).parent().data('makorid');
	$('.reports tbody tr').css('display','none');
	$(".reports tr[data-makorid='"+uid+"']").css('display','table-row');
	$(this).parent().addClass('sticky bottom');
	$(".reports tbody .tbl .pays .payments").css('display','table-row');
});


$('.reports .payTypes td').click(function(){
	sticky2(1);
	//debugger;
	var pid = $(this).parent().data('paytype');
	//hiding not relevant payments
	$('.reports tbody tr, .reports tbody .tbl .pays .payments').css('display','none');	
	$(this).parent().css('display','table-row');
	$(this).parent().addClass('sticky bottom');
	$(".reports tbody tr, .reports tbody .tbl .pays .payments").each(function(){
		//debugger;
		var ptypes = $(this).data('paytypes');
		if(ptypes){
			if (ptypes.indexOf(pid) >= 0){
				$(this).css('display','table-row');;
			}
		}
	})
	
});



function show_agents(){
	sticky2(0);
	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
	$('.reports tbody tr').css('display','none');
	$(".reports tbody .agents").css('display','table-row');
	$("#totalall").css('display','table-row');
	$("#btns-reports > div").removeClass('active');
	$("#b-agents").addClass('active');
	$(".reports tbody .tbl .pays .payments").css('display','table-row');
	

}

function show_makor(){	
	sticky2(0);
	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
	$('.reports tbody tr').css('display','none');
	$(".reports tbody .makor").css('display','table-row');
	$("#totalall").css('display','table-row');
	$("#btns-reports > div").removeClass('active');
	$("#b-makor").addClass('active');
	$(".reports tbody .tbl .pays .payments").css('display','table-row');
	

}

function show_paytypes(){
	//debugger;	
	sticky2(0);
	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
	$('.reports tbody tr').css('display','none');
	$(".reports tbody .payTypes").css('display','table-row');
	$("#totalall").css('display','table-row');
	$("#btns-reports > div").removeClass('active');
	$("#b-paytypes").addClass('active');
	$(".reports tbody .tbl .pays .payments").css('display','table-row');
	

}

function show_all(){	
	sticky2(1);
	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
	$('.reports tbody tr').attr('style','');
	$("#btns-reports > div").removeClass('active');
	$("#b-all").addClass('active');
	$(".reports tbody .tbl .pays .payments").css('display','table-row');
}

function sticky2(_display){	
	if(_display){
		$('.reports').addClass('sticky2');		
	}else{
		$('.reports').removeClass('sticky2');
	}
}


</script>