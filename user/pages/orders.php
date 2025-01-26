<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
$sid = intval($_GET['sid']) ?: $_CURRENT_USER->select_site();
if($sid && !$_CURRENT_USER->has($sid)){
    echo 'Access denied';
    return;
}

if(intval($_GET['sourceID']==9999)){
	if(intval($_GET['extras']==1)){
		$dataSave['alerts_count_wr'] = 0;
	}else{
		$dataSave['alerts_count'] = 0;
	}
	udb::update('biz_users',$dataSave ,"buserID=".$_CURRENT_USER->id());
}

$title = "רשימת הזמנות";
if ($_CURRENT_USER->is_spa()){
$que = "SELECT SQL_CALC_FOUND_ROWS `sites`.`siteName`,`orders`.*
		, GROUP_CONCAT(rooms_units.unitName SEPARATOR ', ') AS `unitNames`
		, MIN(IF(T_orders.timeFrom = '00:00:00', NULL, T_orders.timeFrom)) AS `timeFrom`
		, MAX(T_orders.timeUntil) AS `timeUntil`
		, GROUP_CONCAT(treatments.treatmentName SEPARATOR ', ') AS `treatmentsNames`
		, GROUP_CONCAT(orders.treatmentLen SEPARATOR ', ') AS `treatmentsLen`
		, SUM(IF(workerType = 'fictive',1,0)) as hasfictive
		, SUM(IF(`orderUnits`.`orderID` IS NULL ,1,0)) as noroom
		FROM `orders` 			
		LEFT JOIN orders AS T_orders ON (T_orders.parentOrder = orders.orderID  AND T_orders.parentOrder <> T_orders.orderID)
		LEFT JOIN `orderUnits` ON(T_orders.`orderID` = `orderUnits`.`orderID`)
		LEFT JOIN `rooms_units` USING(`unitID`)
		LEFT JOIN `sites` ON (orders.siteID = sites.siteID)
		LEFT JOIN treatments ON (T_orders.treatmentID = treatments.treatmentID)
		LEFT JOIN therapists ON (T_orders.therapistID = therapists.therapistID)
		WHERE orders.allDay=0  AND orders.parentOrder = orders.orderID /*AND T_orders.timeFrom > 0*/ " ;
 
		$tblName = '`T_orders`';
		$group = 'parentOrder';

}else{

$que = "SELECT SQL_CALC_FOUND_ROWS `sites`.`siteName`, `orders`.*, GROUP_CONCAT(ru.unitName SEPARATOR ', ') AS `unitNames`
		FROM `orders` 
		LEFT JOIN `orderUnits` AS `u` USING(`orderID`)
		LEFT JOIN `rooms_units` AS `ru` USING(`unitID`)
		LEFT JOIN `sites` ON (`orders`.`siteID` = `sites`.`siteID`)
		WHERE allDay=0 ";

		$tblName = '`orders`';
		$group = 'orderID';
}

if($_GET['extras']){
	if(intval($_GET['extras']==1)){ //Has extras
		$que.= " AND (orders.extras NOT LIKE '%\"total\":0%' AND orders.extras > '')";
	}else{ //Doesn't have extras
		$que.= " AND (orders.extras IS NULL OR orders.extras LIKE '%\"total\":0%' OR orders.extras = '')";
	}
}

if ($sid && in_array($sid, $_CURRENT_USER->sites()))
    $que .= " AND `orders`.siteID = " . $sid;
else
    $que .= " AND `orders`.siteID IN (" . $_CURRENT_USER->sites(true) . ")";

if ($_GET['orderStatus'] == 'active'){
    $que .= " AND `orders`.`status` = 1";
    $title .= " פעילות";
}
elseif ($_GET['orderStatus'] == 'cancel'){
    $que .= " AND `orders`.`status` = 0";
    $title .= " מבוטלות";
}



if ($_GET['orderSign'] == 'done'){
    $que .= " AND `orders`.approved = 1";
    $title .= " חתומות";
}
elseif ($_GET['orderSign'] == 'incomplete'){
    $que .= " AND `orders`.approved = 0";
    $title .= " לחתימה";

    if (!$_GET['from'])
        $_GET['from'] = date('d/m/Y');
}

if($_GET['from']){
	$timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'])))),"date");
	$que.=" AND ".$tblName.".`timeFrom` >= '".$timeFrom." 00:00:00'";

}
if($_GET['to']){
	$timeUntil = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'])))),"date");
	$que.=" AND ".$tblName.".`timeFrom` <= '".$timeUntil." 23:59:59'";
}

if(!$_GET['from'] && !$_GET['to']){
    $que.=" AND (".$tblName.".`timeFrom` > NOW() - INTERVAL 1 YEAR OR ".$tblName.".`timeFrom` IS NULL)";
}

if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){
	if($_GET['ordernum']){
		$que.= " AND orders.orderIDBySite = '".$freeText."'";
	}else{
		$list = ['orders.customerName', 'orders.customerEmail', 'orders.customerPhone', 'orders.customerTZ','orders.orderIDBySite'];
		if($_CURRENT_USER->is_spa()){
			$list2 = ['`T_orders`.customerName', '`T_orders`.customerEmail', '`T_orders`.customerPhone', '`T_orders`.customerTZ','`T_orders`.orderIDBySite'];
			$list = array_merge($list, $list2);
		}
		$que .= " AND (" . implode(" LIKE '%" . $freeText . "%' OR ", $list) . " LIKE '%" . $freeText . "%')";
	}
}

if(strlen($_GET['sourceID'])){
	if($_GET['sourceID'] == "9999"){
		$que .= " AND (orders.sourceID = 'spaplus' OR orders.sourceID = 'online')";
	}else{
		$que .= " AND orders.sourceID = '" . udb::escape_string($_GET['sourceID']) . "'";
	}
}

if ($_GET['otype'] && UserUtilsNew::$orderTypes[$_GET['otype']])
    $que .= " AND orders.orderType = '" . udb::escape_string($_GET['otype']) . "'";


$que.= " GROUP BY orders.".$group;


if ($_GET['noroom'] == '1' || $_GET['isfictive'] == '1'){
	$que .= " HAVING";
	if ($_GET['noroom'] == '1'){
		$que1[] .= "  noroom > 0";
	}

	if ($_GET['isfictive'] == '1'){
		$que1[] .= " hasfictive > 0";
	}
	$que.= implode($que1," AND ");
}

$que.=" ORDER BY " . ($_GET['sort'] == 'arrive' ? 'orders.`timeFrom` ASC' : ($_GET['sort'] == 'past' ? 'orders.`timeFrom` DESC' : 'orders.`orderID` DESC'));

$pager = new UserPager();
$pager->setPage(30);

$que.= $pager->sqlLimit();

//echo $que;
$orders = udb::key_row($que, 'orderID');
//print_r($orders);
$pager->setTotal(udb::single_value("SELECT FOUND_ROWS()"));


if ($orders){
    $pays = udb::key_value("SELECT `orderID`, SUM(`sum`) AS `total` FROM `orderPayments` WHERE `complete` = 1 AND `cancelled` = 0 AND `subType` NOT IN ('card_test', 'freeze_sum') AND `orderID` IN (" . implode(',', array_keys($orders)) . ") GROUP BY `orderID` ORDER BY NULL");
    foreach($orders as $id => &$order)
        $order['paid'] = $pays[$id] ?? 0;
    unset($pays, $order);
}

//possible classes: inputWrap, checkWrap


//include "partials/orders_menu.php"; 
?>



<div class="searchOrder">
	<div class="ttl" style="cursor:pointer;margin:-10px;padding:10px" onclick="$('#searchForm').toggleClass('hide');">חפש הזמנות</div>
	<form method="GET" autocomplete="off" action="" class="hide"  id="searchForm">
		<input type="hidden" name="page" value="<?=typemap($_GET['page'] ?? 'orders', 'string')?>" />
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
        <div class="inputWrap">
            <select name="otype" id="otype" title="">
                <option value="">הזמנות ושיריונים</option>
                <option value="order" <?=($_GET['otype'] == 'order' ? 'selected' : '')?>>הזמנות בלבד</option>
                <option value="preorder" <?=($_GET['otype'] == 'preorder' ? 'selected' : '')?>>שיריונים בלבד</option>
            </select>
        </div>
		<div class="inputWrap">
			<input type="text" name="from" placeholder="מתאריך" class="searchFrom" value="<?=typemap($_GET['from'], 'string')?>" readonly>
		</div>
		<div class="inputWrap">
			<input type="text" name="to" placeholder="עד לתאריך" value="<?=typemap($_GET['to'], 'string')?>" class="searchTo" readonly>
		</div>
		<div class="inputWrap">
            <select name="orderSign" id="orderSign" title="">
                <option value="all">הזמנות חתומות ולא חתומות</option>
                <option value="done" <?=($_GET['orderSign'] == 'done' ? 'selected' : '')?>>הזמנות חתומות בלבד</option>
                <option value="incomplete" <?=($_GET['orderSign'] == 'incomplete' ? 'selected' : '')?>>הזמנות לחתימה</option>
			</select>
		</div>
		<div class="inputWrap">
            <select name="orderStatus" id="orderStatus" title="">
                <option value="all">הזמנות מכל הסטטוסים</option>
                <option value="active" <?=($_GET['orderStatus'] == 'active' ? 'selected' : '')?>>הזמנות פעילות בלבד</option>
                <option value="cancel" <?=($_GET['orderStatus'] == 'cancel' ? 'selected' : '')?>>הזמנות מבוטלות בלבד</option>
            </select>
		</div>
		<div class="inputWrap">
            <select name="sort" id="sort">
                <option value="all">סדר הזמנות</option>
                <option value="arrive" <?=($_GET['sort'] == 'arrive' ? 'selected' : '')?>>תאריך רחוק לקרוב</option>
                <option value="past" <?=($_GET['sort'] == 'past' ? 'selected' : '')?>>תאריך קרוב לרחוק</option>
            </select>
		</div>
		<?if($_CURRENT_USER->is_spa()){?>
		<div class="inputWrap" >
            <select name="sourceID" id="sourceID">
                <option value="">סינון לפי מקור הגעה</option>
				<option value="0" <?=$_GET['sourceID']=='0' ? "selected":""?>>הזמנה רגילה</option>
                <option value="9999" <?=($_GET['sourceID'] == '9999' ? 'selected' : '')?>>אונליין ומקור חיצוני</option>
                <option value="online" <?=$_GET['sourceID']=='online' ? "selected":""?>>הזמנת Online</option>
						<?
                        UserUtilsNew::init($_CURRENT_USER->active_site());
						$cuponTypes = UserUtilsNew::$CouponsfullList;
						foreach($cuponTypes as $k=>$source) { ?>
						<option  class="test0"  value="<?=$k?>" <?=$_GET['sourceID']==$k?"selected":""?>><?=$source?></option>
						<?php }
                        foreach(UserUtilsNew::guestMember() as $k => $source){
                            ?>
                            <option   value="<?=$k?>" <?=$_GET['sourceID']==$k?"selected":""?>><?=$source?></option>
                        <?php }
                        foreach(UserUtilsNew::otherSources() as $k => $source){
                            ?>
                            <option  value="<?=$k?>" <?=$_GET['sourceID']==$k?"selected":""?>><?=$source?></option>
                            <?php
                        }
                        ?>
            </select>
		</div>
		<div class="inputWrap">
            <select name="extras" id="extras">
                <option value="">סינון לפי תוספים בהזמנה</option>
                <option value="1" <?=($_GET['extras'] == '1' ? 'selected' : '')?>>הזמנות הכוללות תוספים בתשלום</option>
                <option value="2" <?=($_GET['extras'] == '2' ? 'selected' : '')?>>הזמנות ללא תוספים בתשלום</option>
            </select>
		</div>
		<?}?>
        <div class="inputWrap">
            <input type="text" name="free" placeholder="חיפוש חופשי" value="<?=$freeText?>" />
        </div>
		<div class="inputWrap checkbox">
			<input type="checkbox" name="ordernum" <?=($_GET['ordernum'])? "checked" : ""?> value=1 id="isfictive" for="isfictive"><label>חיפוש לפי מספר הזמנה בלבד</label>
		</div>
		<?if($_CURRENT_USER->is_spa()){?>
		<div class="inputWrap checkbox">
			<input type="checkbox" name="noroom" <?=($_GET['noroom'])? "checked" : ""?> value=1 id="noroom"><label for="noroom">ללא חדר</label>
		</div>
		
		<div class="inputWrap checkbox">
			<input type="checkbox" name="isfictive" <?=($_GET['isfictive'])? "checked" : ""?> value=1 id="isfictive" for="isfictive"><label>עם מטפל פיקטיבי</label>
		</div>
		<?}?>
		<a class="clear" href="?page=<?=$_GET['page']?>">נקה</a>
		<input type="submit" value="חפש">
		
	</form>	
</div>
<style>
.inputWrap.checkbox{display:flex;height:40px;align-items:center}
.inputWrap.checkbox input{width:30px;height:30px;margin-left:6px}
.inputWrap.checkbox label{cursor:pointer}
</style>
 <?php echo $pager->render() ?>

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

