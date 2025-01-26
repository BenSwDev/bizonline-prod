<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
$sid = intval($_GET['sid']) ?: $_CURRENT_USER->select_site();
if($sid && !$_CURRENT_USER->has($sid)){
    echo 'Access denied';
    return;
}
include "partials/setReportRange.php";
$timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'] ?? date('01/m/Y'))))),"date");
$timeTill = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'] ?? date('t/m/Y'))))),"date");



//$siteID = $_CURRENT_USER->active_site();


UserUtilsNew::init($sid);

$where = $where2 = $where3 = ["p.complete = 1", "p.startTime BETWEEN '" . $timeFrom . " 00:00:00' AND '" . $timeTill . " 23:59:59'", 'vou' => "(p.payType <> 'coupon' OR p.provider <> 'vouchers')", 'sub' => "p.payType <> 'member2'"];
$withRefund = true;

if ($sid && $_CURRENT_USER->has($sid))
    $where[] = $where2[] = $where3[] = "o.siteID = " . $sid;
else
    $where[] = $where2[] = $where3[] = "o.siteID IN (" . $_CURRENT_USER->sites(true) . ")";

if ($_GET['orderSign'] == 'done')
    $where[] = "o.approved = 1";
elseif ($_GET['orderSign'] == 'incomplete')
    $where[] = "o.approved = 0";

if (!empty($_GET['payType']) && isset(UserUtilsNew::$payTypesFull[$_GET['payType']])){
    $where['pt'] = $where2['pt'] = $where3['pt'] = ($_GET['payType'] == 'ccard') ? "(p.payType = 'ccard' OR p.payType = 'pseudocc')" : "p.payType = '" . udb::escape_string($_GET['payType']) . "'";
    $withRefund  = ($_GET['payType'] == 'refund');
}
elseif (is_array($_GET['payType2'] ?? null)){
    $ptypes = typemap($_GET['payType2'], ['string']);

    if (in_array('ccard', $ptypes))
        $ptypes[] = 'pseudocc';

    $where['pt'] = $where2['pt'] = $where3['pt'] = "p.payType IN ('" . implode("','", array_map('udb::escape_string', $ptypes)) . "')";
    $withRefund  = in_array('refund', $ptypes);
}

if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){
    $list = ['customerName', 'customerEmail', 'customerPhone', 'customerTZ'];
    $where[] = "(`" . implode("` LIKE '%" . $freeText . "%' OR `", $list) . "` LIKE '%" . $freeText . "%')";

    $list2 = ['clientName', 'clientEmail', 'clientPhone', 'clientTZ'];
    $where2[] = "(`" . implode("` LIKE '%" . $freeText . "%' OR `", $list2) . "` LIKE '%" . $freeText . "%')";

    $list2 = ['giftSender', 'famname', 'giftReciver', 'giftPhoneSender', 'giftPhoneReciver', 'giftEmailSender', 'giftEmailReciver'];
    $where3[] = "(`" . implode("` LIKE '%" . $freeText . "%' OR `", $list2) . "` LIKE '%" . $freeText . "%')";
}

if ($_GET['otype'] && UserUtilsNew::$orderTypes[$_GET['otype']])
    $where[] = "o.orderType = '" . udb::escape_string($_GET['otype']) . "'";


$pageLimit = 30;

$pager = new UserPager();
$pager->setPage($pageLimit);

$where4 = $where3;      // special conditions for Vouchers refunds - no limitations on type of initial payment
unset($where4['pt']);

//---------- IMPORTANT !!! ------------------
// the assumption on lower query is that "lineID" from two tables DON'T intersect (they shouldn't under any normal dates conditions)
$que = "SELECT SQL_CALC_FOUND_ROWS *
        FROM (
            (SELECT p.*, o.customerName, o.orderIDBySite, o.siteID, 'order' AS `lineType` 
            FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`) 
            WHERE " . implode(' AND ', $where) . " AND p.subType NOT IN ('card_test', 'freeze_sum'))
            UNION ALL
            (SELECT p.*, o.clientName, o.subNumber, o.siteID, 'subs' AS `lineType`
            FROM `subscriptionPayments` AS `p` INNER JOIN `subscriptions` AS `o` USING(`subID`) 
            WHERE " . implode(' AND ', $where2) . " AND p.subType NOT IN ('card_test', 'freeze_sum'))
            UNION ALL
            (SELECT p.lineID, p.payType, p.paymentID, p.orderID, p.buserID, p.startTime, p.endTime, p.subType, p.provider, p.complete, p.cancelled, p.sum, p.token, IF(LENGTH(p.invoice) > 0, '+', ''), p.inputData, p.resultData, t.result, o.giftSender, o.pID, o.siteID, 'voucher' AS `lineType` 
            FROM `gift_purchase_payments` AS `p` INNER JOIN `gifts_purchases` AS `o` ON (p.orderID = o.pID) LEFT JOIN `pm_transactions` AS `t` ON (t.transID = o.refunded) 
            WHERE " . implode(' AND ', $where3) . ")
            " . ($withRefund ? "
            UNION ALL
            (SELECT 1000000 + p.lineID, 'refund', o.refunded, p.orderID, p.buserID, t.createTime, t.completeTime, p.subType, 'ccard', t.status, 0, -p.sum, 0, '+', t.input, t.result, NULL, o.giftSender, o.pID, o.siteID, 'voucher' AS `lineType`
            FROM `gift_purchase_payments` AS `p` INNER JOIN `gifts_purchases` AS `o` ON (p.orderID = o.pID) INNER JOIN `pm_transactions` AS `t` ON (t.transID = o.refunded)
            WHERE " . str_replace('p.startTime', 't.createTIme', implode(' AND ', $where4)) . " AND t.status = 1)
            " : "") . "
        ) as `bigT`
        ORDER BY `startTime` DESC " . $pager->sqlLimit();
//$pays = udb::key_row($que, 'lineID');
$pays0 = udb::single_list($que);

$pager->setTotal(udb::single_value("SELECT FOUND_ROWS()"));

$pays = [];
foreach($pays0 as $pay)
    $pays[($pay['lineType'] == 'voucher' ? 100000000 : 0) + $pay['lineID']] = $pay;
unset($pays0);

//$que = "SELECT SQL_CALC_FOUND_ROWS p.*, o.customerName, o.orderIDBySite, o.siteID
//        FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`)
//        WHERE " . implode(' AND ', $where) . "
//        ORDER BY p.startTime DESC " . $pager->sqlLimit();
//$pays = udb::key_row($que, 'lineID');

$sites = udb::key_row("SELECT `siteID`, `masof_active`, `masof_number`, `masof_no_cvv`, `masof_invoice`, `showExactIncome` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")", 'siteID');
//$totalQue = "SELECT p.payType, SUM(p.sum) AS `total` FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`) WHERE " . implode(' AND ', $where) . " AND p.subType <> 'card_test' AND p.cancelled = 0 /*AND p.cancelData IS NULL*/ GROUP BY `payType` ORDER BY NULL";

$totalQue = "SELECT `payType`, SUM(`sum`) AS `total`
             FROM (
                (SELECT p.payType, p.sum 
                FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`) 
                WHERE " . implode(' AND ', $where) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
                UNION ALL
                (SELECT p.payType, p.sum 
                FROM `subscriptionPayments` AS `p` INNER JOIN `subscriptions` AS `o` USING(`subID`) 
                WHERE " . implode(' AND ', $where2) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
                UNION ALL
                (SELECT p.payType, p.sum 
                FROM `gift_purchase_payments` AS `p` INNER JOIN `gifts_purchases` AS `o` ON (p.orderID = o.pID) 
                WHERE " . implode(' AND ', $where3) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
                " . ($withRefund ? "
                UNION ALL
                (SELECT 'refund', -p.sum
                FROM `gift_purchase_payments` AS `p` INNER JOIN `gifts_purchases` AS `o` ON (p.orderID = o.pID) INNER JOIN `pm_transactions` AS `t` ON (t.transID = o.refunded)
                WHERE " . str_replace('p.startTime', 't.createTIme', implode(' AND ', $where4)) . " AND t.status = 1)
                " : "") . "
             ) AS `bigT`
             GROUP BY `payType` 
             ORDER BY NULL";
$totals = count($pays) ? udb::key_value($totalQue) : [];
//echo $totalQue;

$type_refs = [];
if ($totals['refund']){
    $refunds = udb::single_list("SELECT p.lineID, p.payType, p.sum, p.inputData FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`) WHERE " . implode(' AND ', $where) . " AND p.payType = 'refund'");
    foreach($refunds as $refund){
        $inp = json_decode($refund['inputData'], true);
        $_type = '';

        if ($inp['lineID']){
            $_type = udb::single_value("SELECT IFNULL(`provider`, `payType`) FROM `orderPayments` WHERE `lineID` = " . intval($inp['lineID']));
            if (isset($pays[$refund['lineID']]))
                $pays[$refund['lineID']]['_rtype'] = $_type;
        }

        $type_refs[$_type ?: 'ccard'] += $refund['sum'];
    }

    $refunds = udb::single_list("SELECT p.lineID, p.payType, p.sum, p.inputData FROM `subscriptionPayments` AS `p` INNER JOIN `subscriptions` AS `o` USING(`subID`) WHERE " . implode(' AND ', $where2) . " AND p.payType = 'refund'");
    foreach($refunds as $refund){
        $inp = json_decode($refund['inputData'], true);
        $_type = '';

        if ($inp['lineID']){
            $_type = udb::single_value("SELECT IFNULL(`provider`, `payType`) FROM `subscriptionPayments` WHERE `lineID` = " . intval($inp['lineID']));
            if (isset($pays[$refund['lineID']]))
                $pays[$refund['lineID']]['_rtype'] = $_type;
        }

        $type_refs[$_type ?: 'ccard'] += $refund['sum'];
    }

    $refunds = udb::single_list("SELECT p.lineID, p.payType, p.sum, t.result FROM `gift_purchase_payments` AS `p` INNER JOIN `gifts_purchases` AS `o` ON (p.orderID = o.pID) INNER JOIN `pm_transactions` AS `t` ON (t.transID = o.refunded) WHERE " . str_replace('p.startTime', 't.createTIme', implode(' AND ', $where4)) . " AND t.status = 1");
    foreach($refunds as $refund){
        if (isset($pays[$refund['lineID'] + 100000000])){
            $pays[$refund['lineID'] + 100000000]['_rtype'] = $refund['payType'];
            $pays[$refund['lineID'] + 100000000]['cancelData'] = $refund['result'];
        }

        $type_refs[$refund['payType'] ?: 'ccard'] -= $refund['sum'];
    }

    unset($refunds);
}


$subTotals = [];
if ($totals['coupon']){
    unset($where['vou'], $where2['vou'], $where['sub'], $where2['sub']);

    $where['pt'] = $where2['pt'] = "p.payType = 'coupon' AND p.provider <> 'vouchers'";

    $que = "SELECT `provider`, SUM(`sum`) AS `total`
            FROM (
                (SELECT p.provider, p.sum FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`) WHERE " . implode(' AND ', $where) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
                UNION ALL
                (SELECT p.provider, p.sum FROM `subscriptionPayments` AS `p` INNER JOIN `subscriptions` AS `o` USING(`subID`) WHERE " . implode(' AND ', $where2) . " AND p.subType NOT IN ('card_test', 'freeze_sum') AND p.cancelled = 0)
            ) as `uni`
            GROUP BY `provider` 
            ORDER BY NULL";
    $subTotals = udb::key_value($que);
    //$subTotals = udb::key_value("SELECT p.provider, SUM(p.sum) AS `total` FROM `orderPayments` AS `p` INNER JOIN `orders` AS `o` USING(`orderID`) WHERE " . implode(' AND ', $where) . " AND p.subType <> 'card_test' AND p.cancelled = 0 GROUP BY p.provider ORDER BY NULL");
}

$payList = UserUtilsNew::$payTypesFull;
unset($payList['pseudocc']);

if (isset($totals['pseudocc']))
    $totals['ccard'] = ($totals['ccard'] ?? 0) + $totals['pseudocc'];
?>
<style>
.pg-numbers input {cursor:pointer}
</style>
<div class="giftcard gift-pop gift" style="display: none"></div>
<?
selectReportRange();
?>
<div class="searchOrder" style="overflow:initial">
	<div class="ttl">חפש פעולות</div>
	<form method="GET" autocomplete="off" action="" id="searchForm"  style="overflow:visible">
		<input type="hidden" name="page" value="<?=$_GET['page']?>" />
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
			<input type="text" name="from" placeholder="מתאריך" class="searchFrom" value="<?=implode('/',array_reverse(explode('-',trim($timeFrom))))?>" readonly>
		</div>
		<div class="inputWrap">
			<input type="text" name="to" placeholder="עד לתאריך" value="<?=implode('/',array_reverse(explode('-',trim($timeTill))))?>" class="searchTo" readonly>
		</div>
<?php /*
        <div class="inputWrap">
            <select name="payType" id="payType" title="">
                <option value="">כל אמצעי תשלום</option>
<?php
    foreach($payList as $key => $name)
        echo '<option value="' , $key , '" ' , ($_GET['payType'] == $key ? 'selected' : '') , '>' , $name , '</option>';
?>
                <!-- option value="incomplete" <?=($_GET['orderSign'] == 'incomplete' ? 'selected' : '')?>>הזמנות לחתימה</option -->
            </select>
        </div>
*/ ?>

		 <div class="inputWrap">
            <div class="multi-select">
                <div class="multi-select-selected"><span>כל אמצעי תשלום</span><b></b></div>
				<div class="multi-select-options">
					<div class="clear-all"><input id="payType20" type="checkbox" <?=!$_GET['payType2'] ? 'checked' : ''?>><label for="payType20">כל אמצעי תשלום</label></div>
<?php
    $payType2 = (isset($_GET['payType2']) && is_array($_GET['payType2'])) ? $_GET['payType2'] : [];
    foreach($payList as $key => $name){?>
        <div><input type="checkbox" name="payType2[]" id="payType2<?=$key?>" value="<?=$key?>" <?=(in_array($key, $payType2) ? 'checked' : '')?>><label for="payType2<?=$key?>"><?=$name?></label></div>
<?php
    }
?>
				</div>
            </div>
        </div>
		<style>
		.multi-select {position: relative;}
		.inputWrap .multi-select-selected {height: 40px;box-sizing: border-box;padding:0 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;text-align: right;width: 100%;display: flex;align-items: center;}
		.multi-select-selected {height: 40px;box-sizing: border-box;padding-right: 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;text-align: right;width: 100%;display: flex;align-items: center;}
		.multi-select-options {display:none;box-sizing: border-box;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;text-align: right;width: 100%;padding: 4px 10px;position: absolute;z-index: 999;background: white;}
		.multi-select.open .multi-select-options{display:block}
		.multi-select-options div {display: flex;min-height: 24px;align-items: center;}
		.multi-select-options div label {width: 100%;padding-right: 26px;margin-right: -20px;position: relative;z-index: 1;cursor: pointer;}
		.multi-select-selected span{pointer-events:none;white-space:nowrap;text-overflow:ellipsis;overflow:hidden}
		.multi-select-selected b{pointer-events:none}

		</style>
		<script>
		$(function(){
			$('.multi-select-selected').on('click',function(){
			    let mspop = $(this).closest('.multi-select');

				//debugger;				
				if(mspop.hasClass('open')){
                    mspop.removeClass('open');
                    $('body').off('click.msopen')
				} else {
					$('.multi-select').removeClass('open');
                    mspop.addClass('open');

                    $('body').on('click.msopen',function(event){
                        //debugger;
                        var target = $(event.target);
                        if(!target.closest('.multi-select').length){
                            $('.multi-select').removeClass('open');
                            $('body').off('click.msopen');
                        }
                    });
				}
			});

            function combineText(pop){
                let _text = pop.find('.multi-select-options div:not(.clear-all) input').map(function(){
                    if (this.checked)
                        return $(this).next().html();
                }).get();

                return [_text.length, _text.join(', ')];
            }

			$('.multi-select-options input').on('change',function(){
                let mspop = $(this).closest('.multi-select'), _defaultText = mspop.find('.clear-all label').html();
				//debugger;
				if($(this.parentNode).hasClass('clear-all')){
					//debugger;
                    mspop.find('.multi-select-options div:not(.clear-all) input').prop('checked', false);
                    mspop.find('.multi-select-selected span').html(_defaultText);
                    mspop.find('.multi-select-selected b').hide();
				} else {
					let _text = combineText(mspop);

					if(_text[0]){
                        mspop.find('.multi-select-selected span').html(_text[1]);
                        mspop.find('.multi-select-selected b').html("("+_text[0]+")").show();
                        mspop.find('div.clear-all input').prop('checked', false);
					} else {
                        mspop.find('.multi-select-selected span').html(_defaultText);
                        mspop.find('.multi-select-selected b').hide();
                        mspop.find('div.clear-all input').prop('checked', true);
					}
				}
			});

<?php
            if ($payType2)
                echo "$('.multi-select-options input:checked').trigger('change');";
?>
		})

		</script>

		<div class="inputWrap">
            <select name="orderSign" id="orderSign" title="">
                <option value="all">סטטוס פעולה</option>
                <option value="done" <?=($_GET['orderSign'] == 'done' ? 'selected' : '')?>>הזמנות חתומות בלבד</option>
                <option value="incomplete" <?=($_GET['orderSign'] == 'incomplete' ? 'selected' : '')?>>הזמנות לחתימה</option>
			</select>
		</div>

		<!-- div class="inputWrap">
            <select name="sort" id="sort">
                <option value="all">סדר פעולות</option>
                <option value="arrive" <?=($_GET['sort'] == 'arrive' ? 'selected' : '')?>>תאריך רחוק לקרוב</option>
                <option value="past" <?=($_GET['sort'] == 'past' ? 'selected' : '')?>>תאריך קרוב לרחוק</option>
            </select>
		</div -->
        <div class="inputWrap">
            <input type="text" name="free" placeholder="חיפוש חופשי" value="<?=$freeText?>" />
        </div>
		<div style="overflow:hidden">
		<a class="clear" href="?page=<?=$_GET['page']?>">נקה</a>
		<input type="submit" value="חפש">
		</div>

	</form>
</div>
<section class="orders">
	<div class="last-orders" >
		<div class="title">
			<div class="types_sum">
<?php
    foreach($payList as $key => $name){
        if (isset($totals[$key])){
			$all_totals +=$totals[$key];
?>
				<div>
				
					<div><?=$name?></div>
					<div style="direction:ltr">
						<?if($type_refs[$key]){?>
						<?=rtrim(rtrim(number_format($totals[$key] + $type_refs[$key], 1), '0'), '.')?>₪
						<div class="real_sum" style="font-size:12px;border:0;margin:-10px 0 -5px;line-height:1;padding:0;font-weight:normal;white-space:nowrap">
						(לפני  זיכויים <?=rtrim(rtrim(number_format($totals[$key], 1), '0'), '.')?>)</div>
						<?}else{?>
						<?=rtrim(rtrim(number_format($totals[$key], 1), '0'), '.')?>₪
						<?}?>
					</div>
					<?if($key == "coupon"){?>
						<div class="plus_min" onclick="$(this).parent().toggleClass('open')"></div>
					<?}?>
				</div>
<?php
        }

        if ($key == 'coupon'){?>
		<span>
		<?
            foreach($subTotals as $_type => $_sum){
?>
                <div>
                    <div><?=UserUtilsNew::$typeCoupon[$_type]?></div>
                    <div style="direction:ltr"><?=rtrim(rtrim(number_format($_sum, 1), '0'), '.')?>₪</div>
                </div>
<?php
            }
		}?>
		</span>

	<?
    }
?>
				<div>
					<div>סה"כ</div>
					<div style="direction:ltr"><?=rtrim(rtrim(number_format($all_totals, 1), '0'), '.')?>₪</div>
				</div>
				
			</div>
<?php
    if ($_CURRENT_USER->is_spa()){
?>
            <div style="display:flex">
                <div class='print_pdf' ><a target="blank" href='print_deal_report.php?asite=<?=$sid?>&<?=$_SERVER['QUERY_STRING']?>'>דו"ח עסקאות</a></div>
<?php
        if ($sites[$sid]['showExactIncome'])
            echo "<div class='print_pdf' style='background:#0d70b6'><a target=\"blank\" href='print_deal_report_special.php?asite=" . $sid . "&" . $_SERVER['QUERY_STRING'] . "'>דו\"ח הכנסות</a></div>";
?>
            </div>
<?php
    }
?>
		</div>
        <?php echo $pager->render() ?>
		<div class="pay_order yaadTrans">
			<div class="payments">
				<div class="title">תשלומים</div>
<?php
    $today = date('Y-m-d');
    $now   = date('H:i:s');

    $stList = udb::key_value("SELECT `siteID`, `paytypekey`, `invoice` FROM `sitePayTypes` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")", ['siteID', 'paytypekey'], 'invoice');
    foreach($stList as $sid => &$_list)
        if (!isset($_list['cash']))
            $_list['cash'] = 2;
    unset($_list);

    foreach($pays as $pay){
        $class = $specialAction = "";

        $hasTerminal = ($sites[$pay['siteID']]['masof_active'] && $sites[$pay['siteID']]['masof_number']);
        //$canCharge   = ($hasTerminal && $sites[$pay['siteID']]['masof_no_cvv']);

        $payType = $pay['_rtype'] ? 'זיכוי ' . UserUtilsNew::method($pay['_rtype']) : (($pay['payType'] == 'ccard' && $pay['subType'] == 'card_test') ? 'בדיקת כרטיס' : UserUtilsNew::method($pay['payType'], $pay['provider'], true));
        $picon   = ($pay['invoice'] && ($pay['payType'] == 'ccard' || $pay['invoice'] != '-')) ? 'refund' : 'cancel';

        if ($pay['payType'] == 'ccard'){
            $payData = json_decode($pay['resultData'], true);
            $payType .= '<div style="direction:ltr">****' . $payData['last4'] . '</div>';

            $p_date = substr($pay['endTime'], 0, 10);
            $p_time = substr($pay['endTime'], 11, 8);

            $picon = (strcmp($p_date, $today) < 0 || strcmp($now, '23:20:00') >= 0) ? 'refund' : 'cancel';
        }
        elseif ($pay['payType'] == 'coupon'){
            $data = json_decode($pay['inputData'], true);
            $payType .= '<div style="direction:ltr">' .  ($data['cpn'] ?: '') .  ($data['cpnname'] ?  $data['cpnname'] . ($data['cpn'] ?',' : '') : '') . '</div>';
        }

        if ($pay['cancelled']){
            $status = 'בוטל';
            $class  = 'refunded';
        }
        elseif ($pay['cancelData']){
            $status = 'בוצע זיכוי';
            $class  = 'refunded';
        }
        else
            $status = 'בוצע';
		
        switch($pay['lineType']){
            case 'subs':
                $onclick = 'window.openSubscription({subID: ' . $pay['orderID'] . '});';
                $onInvoice = 'download_invoice_sub.php?orid=' . $pay['orderID'] . '&pid=' . $pay['lineID'] . '&t=' . $pay['lineType'];
                $onRefund = 'download_invoice_sub.php?orid=' . $pay['orderID'] . '&canceled=1&pid=' . $pay['lineID'] . '&t=' . $pay['lineType'];
                $class .= ' pay-subs';
				$specialAction = $_CURRENT_USER->user($pay['buserID']). " - מנוי";
                break;

            case 'voucher':
                $onclick = 'window.showPOP(' . $pay['orderID'] . ');';
                $onInvoice = 'download_invoice_gc.php?gcid=' . $pay['orderID'];
                $onRefund = 'download_invoice_gc.php?type=refund&gcid=' . $pay['orderID'];
                $class .= ' pay-voucher';
                $specialAction = "חוייב בוואוצ'רס";

				$inputData = json_decode($pay['inputData'],true);
				$pay['customerName'] = $inputData['fname']." ".$inputData['lname'];	
                break;

            default:
                $onclick = 'window.openFoo({orderID: ' . $pay['orderID'] . '});';
                $onInvoice = 'download_invoice.php?orid=' . $pay['orderID'] . '&pid=' . $pay['lineID'] . '&t=' . $pay['lineType'];
                $onRefund = 'download_invoice.php?orid=' . $pay['orderID'] . '&canceled=1&pid=' . $pay['lineID'] . '&t=' . $pay['lineType'];
                break;
        }
?>
				<div class="item payment pay_order <?=$class?>" onclick="<?=$onclick?>" data-order-id="<?=$pay['orderID']?>" data-pay-id="<?=$pay['lineID']?>">
					<div class="payTop">
						<div class="name"><?=$pay['customerName']?></div>
						<div class="stats">מספר עסקה: <?=($pay['siteID'] . '-' . $pay['orderIDBySite'])?> | <?=$status?></div>
					</div>
					
					<div class="amount">
						<div class="prepay"><?=($pay['subType'] == 'advance' ? 'מקדמה' : '')?> <?=db2date(substr($pay['endTime'], 0, 10), '.', 2)?></div>
						<?=round($pay['sum'], 2)?>₪
					</div>
					<div class="paytype">
						<div class="actionUser"><?=$specialAction?: $_CURRENT_USER->user($pay['buserID'])?></div>
						<div class="inner"><?=$payType?></div>
					</div>
                    <div class="remove">
<?php
        if($pay['cancelData'] || $pay['cancelled']) {
?>
                        <div class="refunded"><?=$status?></div>
<?php
        }
        elseif ($pay['payType'] == 'ccard'){
            if ($pay['subType'] != 'card_test' && !$pay['cancelData'] && $hasTerminal){
?>
                        <div class="pay <?=$picon?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div><?=($picon == 'cancel' ? 'ביטול' : 'זיכוי')?></div></div>
<?php
            }
        }
        // any other payment method except credit card and refund
        elseif ($pay['payType'] != 'refund' && ($pay['invoice'] && $pay['invoice'] != '-') && $hasTerminal) {

?>
                        <div class="pay <?=$picon?>" onclick="deletePayment(this, 'האם אתה בטוח רוצה לבטל וליצור חשבונית מס זיכוי?', function(){window.location.reload();})"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div><?=($picon == 'cancel' ? 'ביטול' : 'זיכוי')?></div></div>
<?php
        }

        if ($sites[$pay['siteID']]['masof_invoice'] && ($pay['invoice'] || !in_array($pay['payType'], ['ccard', 'refund', 'unknown'])) && $pay['invoice'] != '-' && ($pay['payType'] != 'coupon' || $stList[$pay['siteID']][$pay['provider']] > 0)){
            $click = $pay['invoice'] ? ($pay['invoice'] == '-' ? '' : "window.open('" . ($pay['invoice'] == '+' ? $onInvoice : YaadPay::$INVOICE_URL . "?" . $pay['invoice']) . "', 'pay" . $pay['lineID'] . "')") : 'openInvoicePop(this,1)';

?>
						<div class="pay invoice <?=$pay['invoice']? "done" : ""?>" onclick="<?=$click?>"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"/><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"/></svg><div>חשבונית</div></div>
<?php
        
		
			if($pay['cancelData'] ){
				$payData = json_decode($pay['cancelData'], true);
				if($payData['invoice']){
					$click = $pay['invoice'] ? ($pay['invoice'] == '-' ? '' : "window.open('" . ($pay['invoice'] == '+' ? $onRefund : YaadPay::$INVOICE_URL . "?" . $pay['invoice']) . "', 'pay" . $pay['lineID'] . "')") : 'openInvoicePop(this,1)';
					?>
					<div class="pay invoice <?=$pay['invoice']? "done" : ""?>" onclick="<?=$click?>"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"/><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"/></svg><div>ח.ביטול</div></div>
				<?}
			}
		}?>

		 
			
                    </div>
                    
				</div>
<?php
    }
?>
			</div>
		</div>
	</div>
</section>

<style>
body .pay_order.yaadTrans {max-width: 600px;position: relative;margin: auto;left: 0;right: 0;background: white;padding: 10px;}
.pay_order.yaadTrans .payments>.item {margin-top: 36px;}
.pay_order .payments>.item .payTop {margin-top: -20px;position: absolute;background: #0dabb6;color: white;display: block;width: 100%;height: 20px;font-size: 14px;line-height: 20px;padding: 0 10px;box-sizing: border-box;}
.pay_order .payments>.item .payTop .name {float:right}
.pay_order .payments>.item .payTop .stats {float:left;font-size:12px}
</style>
<script>
$(function(){
    $('.item.payment.pay_order').find('.pay.refund, .pay.invoice').click(function(e){
        e.stopPropagation();
    });
});
function showPOP(gid,oid){
    $.get("ajax_pop_gift.php?gID=" + gid + "&oid=" + oid , function (res) {
        $(".giftcard.gift-pop.gift").html(res).fadeIn('fast');
    });
}

function closeShovarPop(){
    $('.giftcard.gift-pop.gift').fadeOut('fast');
    window.location.hash = '';
}

</script>
