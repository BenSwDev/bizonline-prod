<?php
require_once "auth.php";

$gID = intval($_GET['gID']);
$oid = typemap($_GET['oid'], 'numeric');

$sql = "select siteID,giftCardCommission from sites where siteID in(" . $_CURRENT_USER->sites(true) . ")";
$coms = udb::key_row($sql,"siteID");
$where = " gifts_purchases.pID=".$gID;
if(!$gID) {
    $where = " gifts_purchases.ordersID='".$oid."'";
}
$item = udb::single_row("SELECT `gifts_purchases`.ordersID,`gifts_purchases`.pID,`gifts_purchases`.giftSender,`gifts_purchases`.token,`gifts_purchases`.famname,`gifts_purchases`.giftPhoneSender,`gifts_purchases`.hasInvoice,`gifts_purchases`.validMonths,
                            `gifts_purchases`.bless,`gifts_purchases`.transID,`gifts_purchases`.reciveTime,`gifts_purchases`.transDate,`gifts_purchases`.actualDiscount,`gifts_purchases`.voucherSum,`gifts_purchases`.terminal,`gifts_purchases`.refunded,
                             giftCards.* FROM `gifts_purchases` left join giftCards on (giftCards.giftCardID = gifts_purchases.giftCardID) WHERE ".$where);
if($item) {
    $sql_sum = "select pID,sum(useageSum) as totalUsage from giftCardsUsage left join gifts_purchases using (pID) left join giftCards on (gifts_purchases.giftCardID = giftCards.giftCardID) where pID=".$item['pID'];
    $sums = udb::key_row($sql_sum,"pID");

    $que = "SELECT COUNT(DISTINCT p.pID) AS `cnt`, GROUP_CONCAT(DISTINCT p.ordersID SEPARATOR ', ') AS `list`, SUM(`giftCardsUsage`.`useageSum`) AS `useageSum`
            FROM `gifts_purchases` AS `p` LEFT join giftCardsUsage ON (giftCardsUsage.pID = p.pID)
            WHERE p.transID = " . $item['transID'];
    $trans = udb::single_row($que);

    $isDirect = ($item['terminal'] == 'direct');
?>
    <div class="gift_container"><input type="hidden" name="pID" id="pID" value="<?=$item['pID']?>"><input type="hidden" name="giftCardID" id="giftCardID" value="<?=$item['giftCardID']?>">
        <div class="gift_inside">
            <div class="title">פרטי הגיפט קארד</div>
            <div class="close" onclick="closeShovarPop()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
            <ul>
                <li>
                    <div class="gift-num">
                        <div class="title">מספר גיפט קארד</div>
                        <div class="con"><?=$item['ordersID']?></div>
                    </div>
                    <div class="gift-type">
                        <div class="title">סוג גיפט קארד</div>
                        <div class="con"><?=$item['giftTitle']?></div>
                    </div>
                    <div class="gift-worth">
                        <div class="title">שווי מקורי</div>
                        <div class="con"><?=$item['voucherSum']?> ש"ח</div>
                    </div>
                     <div class="gift-balance" id="moneyLeft" data-avail="<?=isset($sums[$item['pID']]['totalUsage']) ? round($item['voucherSum'] - $sums[$item['pID']]['totalUsage'], 1) :  $item['voucherSum']?>">
                        <div class="title">יתרת גיפט קארד</div>
                        <div class="con"><?=isset($sums[$item['pID']]['totalUsage']) ? round($item['voucherSum'] - $sums[$item['pID']]['totalUsage'], 1) :  $item['voucherSum']?> ש"ח</div>
                    </div>
                </li>
                <li>
                    <div class="gift-balance">
                        <div class="title">תאריך רכישה</div>
                        <div class="con"><?=date("d/m/Y",strtotime($item['transDate']))?></div>
                    </div>
                    <div class="gift-expiry">
                        <div class="title">תוקף גיפט קארד</div>
                        <div class="con"><?=date("d/m/Y", strtotime(" +".$item['validMonths']." months", strtotime($item['reciveTime'] ? $item['reciveTime'] : date("Y-m-d"))))?></div>
                    </div>
                    <div class="gift-paid">
                        <div class="title">סכום רכישה</div>
                        <div class="con"><?=round($item['voucherSum'] - $item['actualDiscount'])?> ש"ח</div>
                    </div>
                    <div class="gift-balance">
                        <div class="title">מקור רכישה</div>
                        <div class="con">Vouchers.co.il</div>
                    </div>
                </li>
                <li>
                    <div class="gift-num">
                        <div class="title">שם המזמין</div>
                        <div class="con"><?=$item['giftSender']?> <?=$item['famname']?></div>
                    </div>
                    <div class="gift-balance">
                        <div class="title">טלפון המזמין</div>
                        <div class="con"><?=$item['giftPhoneSender']?></div>
                    </div>
                    <div class="gift-expiry">
                        <div class="title">ברכה</div>
                        <div class="con"><?=$item['bless']?></div>
                    </div>
                    <div class="more-info"><A href="https://www.vouchers.co.il/viewgift.php?tk=<?=$item['token']?>" target="_blank">לצפייה בשובר</A></div>
                </li>
                <?
                $sql_usage = "SELECT * FROM `giftCardsUsage` WHERE `pID` = " . $item['pID'];
                $log_usage = "SELECT * FROM `giftCardsUsageDeleteLog` WHERE `pID` = " . $item['pID'];
                $usage = array_merge(udb::single_list($sql_usage), udb::single_list($log_usage));

                if($usage) {
                ?>
                
                       
				<style>
					.mimushim{width:100%}
					.mimushim th{font-weight:bold}
					.mimushim td, .mimushim th{padding:5px 2px;border-bottom:1px #EEE solid;font-size:14px}
				</style>
				<div class="title" style="font-size:16px;margin-top:10px">מימושים</div>
				<table class="mimushim">
					<thead>
						<tr>
							<th width="160">תאריך</th>
                            <th width="60">סכום</th>
<?php
    if (!$isDirect){
?>
							<th width="60">עמלה</th>
                            <th width="60">זכאות</th>
<?php
    }
?>
							<th>הערות</th>
                            <th>&nbsp;</th>
						</tr>
<?php
                            $totals = ['useageSum' => 0, 'commission' => 0];
							foreach ($usage as $use) {
                                if ($use['deleted']){
                                    $use     = array_merge($use, json_decode($use['data'], true));
                                    $delBtn  = '<div class="del-log">בוטל</div>';
                                    $trStyle = 'style="color:#bbb"';
                                }
                                else {
                                    $delBtn  = $use['cancellable'] ? '<div class="del-btn" onclick="deleteUsage(' . $use['useID'] . ',' . $use['pID'] . ',\'' . $item['ordersID'] . '\',\'' . '₪' . number_format($use['useageSum']) . ' - ' . htmlspecialchars(str_replace("'", "\\'", $use['comments']), ENT_QUOTES) . '\')">ביטול מימוש</div>' : '';
                                    $trStyle = '';
                                }

								$commrate = ($use['commission']) ?: $coms[$use['siteID']]['giftCardCommission'];
								$commission = (($commrate / 100)  * $use['useageSum']);
?>
								<tr <?=$trStyle?>>
									<td><?=date("d.m.Y H:i",strtotime($use['usageDate']))?></td>
									<TD>₪<?=number_format($use['useageSum'])?></TD>
<?php
    if (!$isDirect){
?>
									<TD><?=$commrate?>%</TD>
									<TD>₪<?=number_format($commission,2)?></TD>
<?php
    }
?>
									<TD ><?=$use['comments']?></TD>
                                    <TD><?=$delBtn?></TD>
								</tr>
<?php
                                if (!$use['deleted']){
                                    $totals['useageSum'] += $use['useageSum'];
                                    $totals['commission'] += $commission;
                                }
                            }
?>
							<tr>
								<th>סה"כ מומש</th>
								<th>₪<?=number_format($totals['useageSum'])?></th>
<?php
    if (!$isDirect){
?>
								<th></th>
								<th>₪<?=number_format($totals['commission'],2)?></th>
<?php
    }
?>
								<th>&nbsp;</th>
                                <th>&nbsp;</th>
							</tr>
					</thead>
				</table>
                        
                <?}?>
            </ul>
            <hr />
			<div style="padding: 0 10px 10px;">* מומלץ לבצע מימוש מתוך ההזמנה על מנת שהשובר ישוייך&nbsp;בצורה&nbsp;תקינה.</div>

            <div class="bottom-btns">
<?php
            $leftOver  = isset($sums[$item['pID']]['totalUsage']) ? $item['voucherSum'] - $sums[$item['pID']]['totalUsage'] :  $item['voucherSum'];
            $canRefund = ($isDirect && empty($trans['useageSum']));

            $terminal = (strtoupper(Terminal::hasTerminal($item['siteID'], 'vouchers') ?: '') == 'CARDCOM') ? new CardComGeneral($item['siteID'], 'vouchers') : null;

            if ($isDirect && $terminal){
                if ($item['hasInvoice']){
                    $click = "window.open('download_invoice_gc.php?gcid=" . $item['pID'] . "', 'pay_gc" . $item['pID'] . "')";
?>
                <div class="pay invoice done" onclick="<?=$click?>"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"/><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"/></svg><div>חשבונית</div></div>
<?php
                }
                elseif ($terminal->has_invoice && !$item['refunded']){
?>
                <div class="pay invoice"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"/><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"/></svg><div>חשבונית</div></div>
<?php
                }

                if ($item['refunded']){
                    $click = "window.open('download_invoice_gc.php?gcid=" . $item['pID'] . "&type=refund', 'pay_gc" . $item['pID'] . "')";
?>
                <div class="pay refunded" onclick="<?=$click?>">בוצע זיכוי</div>
<?php
                }
            }

            if($leftOver > 0 && !$item['refunded']) {
?>
                <div class="part" onclick="mimushPop(1)">למימוש חלקי</div>
<?php
                if ($leftOver == $item['voucherSum'])
                    echo '<div class="full" onclick="mimushPop(2)">למימוש מלא</div>';
                if ($canRefund)
                    echo '<div class="full refund" onclick="askGCRefund(' . $item['pID'] . ', this)" data-cnt="' . $trans['cnt'] . '" data-list="' . $trans['list'] . '">זיכוי</div>';
?>
<?php
            }
?>
            </div>

        </div>
    </div>

<?
}
else {
?>
    <div class="gift_container">
        <div class="gift_inside">
            <div class="title">פרטי הגיפט קארד</div>
            <div class="close" onclick="$('.gift-pop').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
            שובר לא נמצא!!
        </div>
    </div>
<?php
}
