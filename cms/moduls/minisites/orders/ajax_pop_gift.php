<?php
include_once "../../../bin/system.php";

include_once "../../../_globalFunction.php";

$gID = intval($_GET['gID']);
$oid = $_GET['oid'];
$sql = "select siteID,giftCardCommission from sites where siteID in(".$_CURRENT_USER->sites(true).")";
$coms = udb::key_row($sql,"siteID");
$where = " gifts_purchases.pID=".$gID;
if(!$gID) {
    $where = " gifts_purchases.ordersID='".$oid."'";
}
$item = udb::single_row("SELECT `gifts_purchases`.ordersID,`gifts_purchases`.pID,`gifts_purchases`.giftSender,`gifts_purchases`.famname,`gifts_purchases`.token,`gifts_purchases`.giftPhoneSender,`gifts_purchases`.giftTitle,`gifts_purchases`.validMonths,
                            `gifts_purchases`.bless,`gifts_purchases`.transID,`gifts_purchases`.reciveTime,`gifts_purchases`.transDate,ROUND(`gifts_purchases`.sum - `gifts_purchases`.actualDiscount) as `paidSum`,`gifts_purchases`.voucherSum,
                             giftCards.* FROM `gifts_purchases` left join giftCards on (giftCards.giftCardID = gifts_purchases.giftCardID) WHERE ".$where);

if($item) {
    $sql_sum = "select pID,sum(useageSum) as totalUsage from giftCardsUsage left join gifts_purchases using (pID) left join giftCards on (gifts_purchases.giftCardID = giftCards.giftCardID) where pID=".$item['pID'];
    $sums = udb::key_row($sql_sum,"pID");
    ?>
    <div class="gift_container"><input type="hidden" name="pID" id="pID" value="<?=$item['pID']?>"><input type="hidden" name="giftCardID" id="giftCardID" value="<?=$item['giftCardID']?>">
        <div class="gift_inside">
            <div class="title">פרטי הגיפט קארד</div>
            <div class="close" onclick="$('.giftcard.gift-pop.gift').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
            <ul>
                <li>
                    <div class="gift-num">
                        <div class="title">מספר גיפט קארד</div>
                        <div class="con"><?=$item['ordersID']?></div>
                    </div>
                    <div class="gift-type">
                        <div class="title">סוג גיפט קארד</div>
                        <div class="con"><?=($item['giftTitle'] ?: $item['title'])?></div>
                    </div>
                    <div class="gift-worth">
                        <div class="title">שווי מקורי</div>
                        <div class="con"><?=$item['voucherSum']?> ש"ח</div>
                    </div>
                     <div class="gift-balance" id="moneyLeft" data-avail="<?=isset($sums[$item['pID']]['totalUsage']) ? $item['voucherSum'] - $sums[$item['pID']]['totalUsage'] :  $item['voucherSum']?>">
                        <div class="title">יתרת גיפט קארד</div>
                        <div class="con"><?=isset($sums[$item['pID']]['totalUsage']) ? $item['voucherSum'] - $sums[$item['pID']]['totalUsage'] :  $item['voucherSum']?> ש"ח</div>
                    </div>
                </li>
                <li>
                    <div class="gift-balance">
                        <div class="title">תאריך רכישה</div>
                        <div class="con"><?=date("d/m/Y",strtotime($item['transDate']))?></div>
                    </div>
                    <div class="gift-expiry">
                        <div class="title">תוקף גיפט קארד</div>
                        <div class="con"><?=date("d/m/Y", strtotime(" +".($item['validMonths'] ?: $item['daysValid']))." months", strtotime($item['reciveTime'] ? $item['reciveTime'] : date("Y-m-d"))))?></div>
                    </div>
                    <div class="gift-worth">
                        <div class="title">עלות רכישה</div>
                        <div class="con"><?=$item['paidSum']?> ש"ח</div>
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
                </li>
                <li>
                    <div class="gift-balance">
                        <div class="title">עמלה</div>
                        <div class="con"><?=($coms[$item['siteID']]['giftCardCommission'] / 100)  * $item['paidSum']?> ש"ח</div>
                    </div>
                    <div class="gift-expiry">
                        <div class="title">ברכה</div>
                        <div class="con"><?=$item['bless']?></div>
                    </div>
                    <div class="more-info">לפרטים נוספים</div>
                </li>
            </ul>
            <hr /><?
            $leftOver = isset($sums[$item['pID']]['totalUsage']) ? $item['voucherSum'] - $sums[$item['pID']]['totalUsage'] :  $item['voucherSum'];
            if($leftOver > 0) {
                ?>
                <div class="bottom-btns">
                <div class="part" onclick="mimushPop(1)">למימוש חלקי</div>
                <?if($leftOver == $item['voucherSum']) {?>
                    <div class="full" onclick="mimushPop(2)">למימוש מלא</div><?}?>
                </div><?
            }
            ?>
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
    <?
}
