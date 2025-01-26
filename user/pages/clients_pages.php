<?php
function hdate($date){
    return typemap(implode('-', array_reverse(explode('/', trim($date)))), 'date');
}

function db2dateD($date){
    return db2date($date, '.');
}

$sid = intval($_GET['sid']);

$title = "רשימת לקוחות";

if($_GET['from']){
    $timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'])))),"date");
}else{
    $timeFrom = '2001-01-01';
    $_GET['from'] = implode('/',array_reverse(explode('-',trim($timeFrom))));
}

if($_GET['to']){
    $timeUntil = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'])))),"date");
}else{
    $timeUntil = date("Y-m-t");
    $_GET['to'] = implode('/',array_reverse(explode('-',trim($timeUntil))));
}

$where = ["c.siteID IN (" . ($sid ?: $_CURRENT_USER->sites(true)) . ")"];

$clients = udb::key_row("SELECT crm_clients.*, sites.siteName FROM `crm_clients` INNER JOIN `sites` USING(`siteID`) WHERE crm_clients.siteID IN (" . $_CURRENT_USER->sites(true) . ") ORDER BY `clientID` DESC", 'clientID');

if(!$_GET['sort'] || !in_array($_GET['sort'], ['ASC', 'DESC']))
    $_GET['sort'] = "DESC";


if ($_GET['timeType'] == 'u'){
    $where[] = "c.updateTime BETWEEN '" . udb::escape_string($timeFrom) . "' AND '" . udb::escape_string($timeUntil) . "'";
    $sorter  = 'c.updateTime ' . udb::escape_string($_GET['sort']);
}
else {
    $where[] = "c.createTime BETWEEN '" . udb::escape_string($timeFrom) . "' AND '" . udb::escape_string($timeUntil) . "'";
    $sorter  = 'c.createTime ' . udb::escape_string($_GET['sort']);
}

if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){
    if (is_numeric($freeText))
        $list = ['c.clientEmail', 'c.clientPhone', 'c.clientMobile', 'c.clientPassport', 'c.clientID'];
    else
        $list = ['c.clientName', 'c.clientEmail'];

    $where[] = "(" . implode(" LIKE '%" . $freeText . "%' OR ", $list) . " LIKE '%" . $freeText . "%')";
}


$pager = new UserPager();
$pager->setPage(10);

$clients = udb::key_row("SELECT c.*, sites.siteName FROM `crm_clients` AS `c` INNER JOIN `sites` USING(`siteID`) WHERE " . implode(" AND ", $where) . " ORDER BY " . $sorter. $pager->sqlLimit(), 'clientID');


$pager->setTotal(udb::single_value("SELECT FOUND_ROWS()"));


//include "partials/orders_menu.php";
?>

<div class="searchOrder">
	<div class="ttl" style="cursor:pointer;margin:-10px;padding:10px" onclick="$('#searchForm').toggleClass('hide');">חפש לקוח</div>
	<form method="GET" autocomplete="off" action="" class="hide"  id="searchForm">
		<input type="hidden" name="page" value="clients">

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
            <select name="timeType" id="otype" title="">
                <option value="c">לפי תאריך הוספה</option>
                <option value="u" <?=($_GET['timeType'] == 'u' ? 'selected' : '')?>>לפי תאריך עדכון</option>
            </select>
        </div>
		<div class="inputWrap">
			<input type="text" name="from" placeholder="מתאריך" class="searchFrom" value="<?=typemap($_GET['from'], 'string')?>" readonly>
		</div>
		<div class="inputWrap">
			<input type="text" name="to" placeholder="עד לתאריך" value="<?=typemap($_GET['to'], 'string')?>" class="searchTo" readonly>
		</div>
		
		<div class="inputWrap">
            <select name="sort" id="sort" title="">
                <option value="ASC">תאריך רחוק לקרוב</option>
                <option value="DESC" <?=($_GET['sort'] == 'DESC' ? 'selected' : '')?>>תאריך קרוב לרחוק</option>
            </select>
		</div>
        <div class="inputWrap">
            <input type="text" name="free" placeholder="חיפוש חופשי" value="<?=$freeText?>" />
        </div>

		<a class="clear" href="?page=<?=typemap($_GET['page'], 'string')?>">נקה</a>
		<input type="submit" value="חפש">
	</form>
</div>

<style>
.item.order.isSpa td.f,.item.order.isSpa td.c {direction:ltr; text-align:right}
.item.order.isSpa td.f.rtl, .item.order.isSpa td.c.rtl {direction:rtl}

.orders_num.o-ctrl {
    background: #c9f2fd;
}

.orders_num {
    cursor: pointer;
    position: relative;
}

.orders_num.o-ctrl::after {
    opacity: 1;
}

.orders_num.o-up::after {
    opacity: 0.2;
}
.orders_num::after {
    content: "";
    width: 6px;
    height: 6px;
    box-sizing: border-box;
    border-left: 2px black solid;
    border-bottom: 2px black solid;
    display: block;
    position: absolute;
    bottom: 0;
    margin: 0 auto;
    left: 0;
    right: 0;
    transform: rotate(-45deg);
    opacity: 0;
}

.orders_num.o-down::after {
    opacity: 0.5;
    transform: rotate(135deg);
}

</style>
<section class="orders" style="max-width:none">
	<div class="last-orders">
		<div class="title"><?=$title?>
			<!-- div id="btns-reports" class="btns">
				<div id="b-makor" onclick="show_makor()">מקורות הגעה</div>
				<div id="b-paytypes" onclick="show_paytypes()">אמצעי תשלום</div>
				<div id="b-agents" onclick="show_agents()">סוכנים</div>
				<div id="b-all" onclick="show_all()" class='active'>מקיף</div>
			</div -->
		</div>
		<div class="AAAreport-container">
			
 <?php echo $pager->render() ?>
			<table class="reports" id="reports">
				<thead>
				<tr class='sticky top'>
					<th>#</th>
					<th>שם מלא</th>
                    <th>ת.ז.</th>
					<th>טלפון</th>
                    <th>טלפון נוסף</th>
                    <th>דוא"ל</th>
                    <th>כתובת</th>
					<th>תאריך הוספה</th>
                    <th>עדכון אחרון</th>
                    <?=($multiSite ? '<th>שם העסק</th>' : '')?>
<? /*					<th class="orders_num">כמות  טיפולים</th>
					<th class="orders_num">עלות טיפולים</th>
                    <th class="orders_num">עמלת מטפלים</th>
					<th class="orders_num">הכנסה מטיפולים</th>
					<th class="orders_num">הכנסה מחדרים</th>
					<th class="orders_num">הכנסה מתוספות</th>
                    <th class="orders_num">מחיר מחירון</th>
                    <th class="orders_num">הנחה</th>
					<th>עלות בפועל<br />(לתשלום)</th>
					<th class="orders_num">שולם</th>
					<th class="orders_num">נותר לתשלום</th>
                    <th>סוכן</th>
                    <th style="min-width:90px">מקור הגעה</th>
                    <th style="min-width:90px">אמצעי התשלום</th>  */ ?>
				</tr>
				</thead>
				<tbody>

<?php
//        UserUtilsNew::init();
//
//        $mcache = [];
//		$cuponTypes = UserUtilsNew::$CouponsfullList;
//		$cuponTypes['online'] = 'הזמנה אונליין';
        foreach($clients as $client) {
//            $que = "SELECT orders.timeFrom, orders.therapistID, orders.treatmentLen, orders.price FROM `orders` WHERE orders.orderID <> orders.parentOrder AND `parentOrder` = " . $client['parentOrder'];
//            $tipulim = udb::single_list($que);
//
//            $totalPay = 0;
//            foreach($tipulim as $sub){
//                $mcache[$sub['therapistID']] = $master = $mcache[$sub['therapistID']] ?? new MasterPay($sub['therapistID']);
//
//                $pay = $master->get_treat_pay($sub['timeFrom'], $sub['treatmentLen'], $sub['price']);
//                $totalPay += $pay['total'];
//            }
//
//            $extraCost = 0;
//            if ($client['extras']){
//                $tmp = json_decode($client['extras'], true);
//                $extraCost = $tmp['total'];
//            }
//            $payTypes = explode('|', $client['ppType']);
//
//            $payTypes = $payTypes ? implode('<br />', array_unique(array_map(function ($str){return  UserUtilsNew::method(...explode('-', $str));}, $payTypes))) : '';
?>
				<tr class="item order isSpa" data-id='<?=$client['clientID']?>'>
                    <td class="c"><?=$client['clientID']?></td>
					<td class="c rtl"><?=$client['clientName']?></td>
                    <td class="c"><?=$client['clientPassport']?></td>
					<td class="c"><?=$client['clientMobile']?></td>
                    <td class="c"><?=$client['clientPhone']?></td>
                    <td class="c"><?=$client['clientEmail']?></td>
                    <td class="c"><?=$client['clientAddress']?></td>
					<td class="c"><?=date('d.m.y', strtotime($client['createTime']))?></td>
					<td class="c"><?=date('d.m.y H:i:s', strtotime($client['updateTime']))?></td>
                    <?=($multiSite ? '<td class="c">' . $client['siteName'] . '</td>' : '')?>
<? /*                    
					<td class="c"><?=implode('<br />', array_map('db2dateD', explode('#', $client['treatDates'])))?></td>
					<td class="c"><?=number_format($client['countTreatments'])?></td>
					<td class="c"><?=number_format($client['treatCost'])?></td>
                    <td class="c"><?=number_format($totalPay)?></td>
					<td class="c"><?=number_format($client['treatCost'] - $totalPay)?></td>
                    <td class="c"><?=number_format($client['roomCost']) ?></td>
					<td class="c"><?=number_format($extraCost)?></td>
                    <td class="c"><?=number_format($client['price'] + $client['discount'])?></td>
                    <td class="c"><?=number_format($client['discount'])?></td>
					<td class="c number"><?=number_format($client['price'])?></td>
					<td class="c number"><?=number_format($client['paid'])?></td>
					<td class="c number"><?=number_format($client['price'] - $client['paid'])?></td>
                    <td><?=$client['userName']?></td>
                    <td><?=$client['sourceID']? $cuponTypes[$client['sourceID']] : "הזמנה רגילה"?></td>
                    <td><?=$payTypes?><?//print_r($payTypesIDs)?></td> */ ?>
				</tr>
				
				<?

//				/*****************************Total Lines Create **********************************/
//				$totalOrders ++;
//				$totalTreatCost += $order['treatCost'];
//				$totalpayAll += $totalPay;
//				$totalTreatIncome += $order['treatCost'] - $totalPay;
//				$totalExtraIncome += $order['roomCost'] + $extraCost;
//				$totalRooms += $order['roomCost'];
//				$totalExtras += $extraCost;
//				$totalMechiron += $order['treatCost'] + $order['roomCost'] + $extraCost;
//				$totalDiscount += $order['treatCost'] + $order['roomCost'] + $extraCost - $order['price'];
//				$totalPrice +=$order['price'];
//				$totalBalance += ($order['price'] - $order['paid']);
//				$totalPaid +=$order['paid'];
//				$totalTreatments += $order['countTreatments'];
//
//				/***********************************************************************************/
//
//				/***************************** Total Agents Lines Create **********************************/
//
//				$a_name[$order['agent_buserID']] = $order['userName'];
//				$a_totalOrders[$order['agent_buserID']] ++;
//				$a_totalTreatCost[$order['agent_buserID']] += $order['treatCost'];
//				$a_totalpayAll[$order['agent_buserID']] += $totalPay;
//				$a_totalTreatIncome[$order['agent_buserID']] += $order['treatCost'] - $totalPay;
//				$a_totalExtraIncome[$order['agent_buserID']] += $order['roomCost'] + $extraCost;
//				$a_totalRooms[$order['agent_buserID']] += $order['roomCost'];
//				$a_totalExtras[$order['agent_buserID']] += $extraCost;
//				$a_totalMechiron[$order['agent_buserID']] += $order['treatCost'] + $order['roomCost'] + $extraCost;
//				$a_totalDiscount[$order['agent_buserID']] += $order['treatCost'] + $order['roomCost'] + $extraCost - $order['price'];
//				$a_totalPrice[$order['agent_buserID']] +=$order['price'];
//				$a_totalBalance[$order['agent_buserID']] += ($order['price'] - $order['paid']);
//				$a_totalPaid[$order['agent_buserID']] +=$order['paid'];
//				$a_totalTreatments[$order['agent_buserID']] += $order['countTreatments'];
//
//
//
//				/***********************************************************************************/
//
//				/***************************** Total payType Lines Create **********************************/
//
//
//
//				/***********************************************************************************/
//
//
//
//
//				/***************************** Total Source Lines Create **********************************/
//
//				$m_name[$order['sourceID']] = $order['sourceID']? $cuponTypes[$order['sourceID']] : "הזמנה רגילה";
//				$m_totalOrders[$order['sourceID']] ++;
//				$m_totalTreatCost[$order['sourceID']] += $order['treatCost'];
//				$m_totalpayAll[$order['sourceID']] += $totalPay;
//				$m_totalTreatIncome[$order['sourceID']] += $order['treatCost'] - $totalPay;
//				$m_totalExtraIncome[$order['sourceID']] += $order['roomCost'] + $extraCost;
//				$m_totalRooms[$order['sourceID']] += $order['roomCost'];
//				$m_totalExtras[$order['sourceID']] += $extraCost;
//				$m_totalMechiron[$order['sourceID']] += $order['treatCost'] + $order['roomCost'] + $extraCost;
//				$m_totalDiscount[$order['sourceID']] += $order['treatCost'] + $order['roomCost'] + $extraCost - $order['price'];
//				$m_totalPrice[$order['sourceID']] +=$order['price'];
//				$m_totalBalance[$order['sourceID']] += ($order['price'] - $order['paid']);
//				$m_totalPaid[$order['sourceID']] +=$order['paid'];
//				$m_totalTreatments[$order['sourceID']] += $order['countTreatments'];
//
//
//				/***********************************************************************************/
//
//
//


        }

		
/*        foreach($a_name as $key =>  $name ){
?>
				<tr class='agents' data-userid="<?=$key?>">
					<td><b><?=$name?></b><br><?=$a_totalOrders[$key]?> הזמנות</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td><?=number_format($a_totalTreatments[$key]);?></td>
					<td>₪<?=number_format($a_totalTreatCost[$key]);?><div>מחיר ממוצע ₪<?=number_format(($a_totalTreatCost[$key]/$a_totalTreatments[$key]),2);?></div></td>
					<td>₪<?=number_format($a_totalpayAll[$key]);?></td>
					<td>₪<?=number_format($a_totalTreatIncome[$key]);?></td>
					<td>₪<?=number_format($a_totalRooms[$key]);?></td>
					<td>₪<?=number_format($a_totalExtras[$key]);?></td>
                    <td>₪<?=number_format($a_totalMechiron[$key]);?></td>
                    <td>₪<?=number_format($a_totalDiscount[$key]);?></td>
					<td>₪<?=number_format($a_totalPrice[$key])?><div>מחיר ממוצע ₪<?=number_format(($a_totalPrice[$key]/$a_totalOrders[$key]),2);?></div><div></div></td>
					<td>₪<?=number_format($a_totalPaid[$key])?></td>
					<td>₪<?=number_format($a_totalBalance[$key])?></td>
                    <td><?=$name?></td>
                    <td></td>
                    <td></td>
                </tr>
<?php
        }

		foreach($m_name as $key =>  $name ){
?>
				<tr class='makor' data-makorid="<?=$key?>">
					<td><b><?=$name?></b><br><?=$m_totalOrders[$key]?> הזמנות</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td><?=number_format($m_totalTreatments[$key]);?></td>
					<td>₪<?=number_format($m_totalTreatCost[$key]);?><div>מחיר ממוצע ₪<?=number_format(($m_totalTreatCost[$key]/$m_totalTreatments[$key]),2);?></div></td>
					<td>₪<?=number_format($m_totalpayAll[$key]);?></td>
					<td>₪<?=number_format($m_totalTreatIncome[$key]);?></td>
					<td>₪<?=number_format($m_totalRooms[$key]);?></td>
					<td>₪<?=number_format($m_totalExtras[$key]);?></td>
                    <td>₪<?=number_format($m_totalMechiron[$key]);?></td>
                    <td>₪<?=number_format($m_totalDiscount[$key]);?></td>
					<td>₪<?=number_format($m_totalPrice[$key])?><div>מחיר ממוצע ₪<?=number_format(($m_totalPrice[$key]/$m_totalOrders[$key]),2);?></div><div></div></td>
					<td>₪<?=number_format($m_totalPaid[$key])?></td>
					<td>₪<?=number_format($m_totalBalance[$key])?></td>
                    <td></td>
                    <td><?=$name?></td>
                    <td></td>
                </tr>
<?php
        }

        foreach($payTypeSums as $pt => $prow ){
?>
				<tr class='payTypes' data-paytype="<?=$pt?>">
					<td><b><?=(UserUtilsNew::method(...explode('-', $pt)) ?: $pt)?></b><br><?=$prow['cnt']?> הזמנות</td>
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
					<td>₪<?=number_format($prow['total'])?></td>
					<td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
<?php
        }*/

?>
				<tr class='sticky bottom total' id="totalall">
					<td>סה"כ: </td>
					<td colspan="15"><?=count($clients)?> לקוחות</td>

<? /*					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td><?=number_format($totalTreatments);?></td>
					<td>₪<?=number_format($totalTreatCost);?><div>מחיר ממוצע ₪<?=$totalTreatments? number_format(($totalTreatCost/$totalTreatments),2) : "";?></div></td>
					<td>₪<?=number_format($totalpayAll);?></td>
					<td>₪<?=number_format($totalTreatIncome);?></td>
					<td>₪<?=number_format($totalRooms);?></td>
					<td>₪<?=number_format($totalExtras);?></td>
                    <td>₪<?=number_format($totalMechiron);?></td>
                    <td>₪<?=number_format($totalDiscount);?></td>
					<td>₪<?=number_format($totalPrice)?><div>מחיר ממוצע ₪<?=$totalTreatments? number_format(($totalPrice/$totalTreatments),2) : "";?></div></td>
					<td>₪<?=number_format($totalPaid)?></td>
					<td>₪<?=number_format($totalBalance)?></td>
                    <td></td>
                    <td></td>
                    <td></td> */ ?>
                </tr>
			</table>
		</div>
	</div>
</section>

<style>
.report-container{max-height:calc(100vh - 250px);overflow:auto;padding-left:20px;clear:both;position:relative;top:10px}
.reports{font-size:14px;border-collapse:collapse}
.reports td, .reports th{padding:5px;vertical-align:middle;border:1px #ccc solid;text-align:right}
.reports td.number{direction:ltr}
.reports .sticky{position:sticky;background:white;}
.reports .sticky.top{top:-20px}
.reports .sticky.bottom{bottom:-20px}
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

</style>

<script>


//$('.orders_num').click(function(){
//    var table = $(this).parents('table').eq(0)
//    var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
//    this.asc = !this.asc
//    $(".orders_num").removeClass("o-ctrl");
//	$(this).addClass('o-ctrl');
//	if (!this.asc){
//		rows = rows.reverse();
//		$(this).removeClass('o-up');
//		$(this).addClass('o-down');
//	}else{
//		$(this).addClass('o-up');
//		$(this).removeClass('o-down');
//	}
//    for (var i = 0; i < rows.length; i++){table.append(rows[i])}
//});

function comparer(index) {
    return function(a, b) {
        var valA = getCellValue(a, index).replace(/[^\d.-]/g, ''), valB = getCellValue(b, index).replace(/[^\d.-]/g, '')
        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB)
    }
}
function getCellValue(row, index){ return $(row).children('td').eq(index).text() }


$('.reports .agents td').click(function(){
	//debugger;
	var uid = $(this).parent().data('userid');
	$('.reports tbody tr').css('display','none');
	$(".reports tr[data-userid='"+uid+"']").css('display','table-row');
	$(this).parent().addClass('sticky bottom');
});

$('.reports .makor td').click(function(){
	//debugger;
	var uid = $(this).parent().data('makorid');
	$('.reports tbody tr').css('display','none');
	$(".reports tr[data-makorid='"+uid+"']").css('display','table-row');
	$(this).parent().addClass('sticky bottom');
});

$('.reports .payTypes td').click(function(){
	
	var pid = $(this).parent().data('paytype');
	$('.reports tbody tr').css('display','none');	
	$(this).parent().css('display','table-row');
	$(this).parent().addClass('sticky bottom');
	$(".reports tbody tr").each(function(){
		//debugger;
		var ptypes = $(this).data('paytypes');
		if (ptypes.indexOf(pid) >= 0){			
			$(this).css('display','table-row');
		}
	})
	
});

//function show_agents(){
//
//	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
//	$('.reports tbody tr').css('display','none');
//	$(".reports tbody .agents").css('display','table-row');
//	$("#totalall").css('display','table-row');
//	$("#btns-reports > div").removeClass('active');
//	$("#b-agents").addClass('active');
//
//
//}
//
//function show_makor(){
//
//	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
//	$('.reports tbody tr').css('display','none');
//	$(".reports tbody .makor").css('display','table-row');
//	$("#totalall").css('display','table-row');
//	$("#btns-reports > div").removeClass('active');
//	$("#b-makor").addClass('active');
//
//
//}
//
//function show_paytypes(){
//	//debugger;
//	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
//	$('.reports tbody tr').css('display','none');
//	$(".reports tbody .payTypes").css('display','table-row');
//	$("#totalall").css('display','table-row');
//	$("#btns-reports > div").removeClass('active');
//	$("#b-paytypes").addClass('active');
//
//
//}
//
//function show_all(){
//	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
//	$('.reports tbody tr').attr('style','');
//	$("#btns-reports > div").removeClass('active');
//	$("#b-all").addClass('active');
//}

</script>
