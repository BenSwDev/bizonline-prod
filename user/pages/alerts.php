<?php
$sid = intval($_GET['sid']);

$alerts = udb::single_value("SELECT alerts_count FROM biz_users WHERE buserID = ".$_CURRENT_USER->id());
$limit = max(30,$alerts);

if(intval($_GET['extras']==1)){
$dataSave['alerts_count_wr'] = 0;
}else{
$dataSave['alerts_count'] = 0;
}
udb::update('biz_users',$dataSave ,"buserID=".$_CURRENT_USER->id());

$title = "הזמנות אונליין אחרונות";
if ($_CURRENT_USER->is_spa()){
$que = "SELECT `sites`.`siteName`,`orders`.*
		, GROUP_CONCAT(rooms_units.unitName SEPARATOR ', ') AS `unitNames`
		, MIN(IF(T_orders.timeFrom = '00:00:00', NULL, T_orders.timeFrom)) AS `timeFrom`
		, MAX(T_orders.timeUntil) AS `timeUntil`
		, GROUP_CONCAT(treatments.treatmentName SEPARATOR ', ') AS `treatmentsNames`
		, GROUP_CONCAT(orders.treatmentLen SEPARATOR ', ') AS `treatmentsLen`
		FROM `orders` 			
		LEFT JOIN orders AS T_orders ON (T_orders.parentOrder = orders.orderID)
		LEFT JOIN `orderUnits` ON(T_orders.`orderID` = `orderUnits`.`orderID`)
		LEFT JOIN `rooms_units` USING(`unitID`)
		LEFT JOIN `sites` ON (orders.siteID = sites.siteID)
		LEFT JOIN treatments ON (T_orders.treatmentID = treatments.treatmentID)
		WHERE orders.allDay=0  AND orders.parentOrder = orders.orderID /*AND T_orders.timeFrom > 0*/ " ;
 
		$tblName = '`T_orders`';
		$group = 'parentOrder';
		$online_src = array("'online'", "'spaplus'");
		$online_add = " AND `orders`.sourceID IN (".implode(",",$online_src).")";


}else{

$que = "SELECT `sites`.`siteName`, `orders`.*, GROUP_CONCAT(ru.unitName SEPARATOR ', ') AS `unitNames`
		FROM `orders` 
		LEFT JOIN `orderUnits` AS `u` USING(`orderID`)
		LEFT JOIN `rooms_units` AS `ru` USING(`unitID`)
		LEFT JOIN `sites` ON (`orders`.`siteID` = `sites`.`siteID`)
		WHERE allDay=0 ";

		$tblName = '`orders`';
		$group = 'orderID';
		$online_add = " AND orders.domainID > 1 ";
}

if(intval($_GET['extras']==1)){ //Has extras
	$que.= " AND (orders.extras NOT LIKE '%\"total\":0%' AND orders.extras > '')";
}

if ($sid && in_array($sid, $_CURRENT_USER->sites()))
    $que .= " AND `orders`.siteID = " . $sid;
else
    $que .= " AND `orders`.siteID IN (" . $_CURRENT_USER->sites(true) . ")";


$que .= " AND `orders`.`status` = 1";
$que .= $online_add;
$que .= " GROUP BY orders.".$group." ORDER BY orders.`orderID` DESC";
$que .= " LIMIT ".$limit;
if ($_GET['aaa']) echo $que;
$orders = udb::key_row($que, 'orderID');
//print_r($orders);

if ($orders){
    $pays = udb::key_value("SELECT `orderID`, SUM(`sum`) AS `total` FROM `orderPayments` WHERE `complete` = 1 AND `cancelled` = 0 AND `subType` NOT IN ('card_test', 'freeze_sum') AND `orderID` IN (" . implode(',', array_keys($orders)) . ") GROUP BY `orderID` ORDER BY NULL");
    foreach($orders as $id => &$order)
        $order['paid'] = $pays[$id] ?? 0;
    unset($pays, $order);
}

//possible classes: inputWrap, checkWrap


//include "partials/orders_menu.php"; 
?>


<?/*
<div class="searchOrder">
	<div class="ttl" style="cursor:pointer;margin:-10px;padding:10px" onclick="$('#searchForm').toggleClass('hide');">חפש הזמנות</div>
	<form method="GET" autocomplete="off" action="" class="hide"  id="searchForm">
		<input type="hidden" name="page" value="<?=typemap($_GET['page'] ?? 'alerts', 'string')?>" />
        <input type="hidden" name="otype" value="<?=typemap($_GET['otype'] ?? 'order', 'string')?>" />
<?php
    if (count($_CURRENT_USER->sites()) > 1){
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
		<input type="submit" value="חפש">
		
	</form>	
</div>
*/?>

<section class="orders">
	<div class="last-orders">
		<div class="title"><?=$title?></div>
		<div class="items">
			<?php foreach($orders as $order) { 
			orderComp($order);
			} ?>
		</div>
	</div>
</section>

