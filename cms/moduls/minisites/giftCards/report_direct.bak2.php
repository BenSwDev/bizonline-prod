<?php
include_once "../../../bin/system.php";
if($_GET['tab']){
    include_once "../../../bin/top_frame.php";
    include_once "../mainTopTabs.php";
    include_once "innerMenu.php";
}else{
    include_once "../../../bin/top.php";
}
include_once "../../../_globalFunction.php";

function smartNF($num){
    return rtrim(rtrim(number_format($num, 2), '0'), '.');
}
?>
<style>

.giftcard.gift-pop{position:fixed;top:0;left:0;bottom:0;background:rgba(0,0,0,.6);width:100%;right:0;height:100vh}
.giftcard.gift-pop .gift_container{position:absolute;top:50%;right:50%;transform:translateY(-50%) translateX(50%);width:100%;max-width:100%;background:#e0e0e0;padding:10px;text-align:right;box-sizing:border-box}
.giftcard.gift-pop .gift_inside{background:#fff;box-shadow:0 0 2px rgb(0 0 0 / 60%);position:relative;}
.giftcard.gift-pop .gift_inside>.title{padding:20px;box-sizing:border-box}
.giftcard.gift-pop .gift_container ul{list-style:none;font-size:0;padding:10px;box-sizing:border-box}
.giftcard.gift-pop .gift_container ul li{display:inline-block;width:100%;max-width:33.33%;font-size:16px;padding-left:50px;box-sizing:Border-box}
.giftcard.gift-pop .gift_container ul li>div .title{display:inline-block;width:130px;color:#9e9e9e}
.giftcard.gift-pop .gift_container ul li>div .con{display:inline-block;width:230px}
.giftcard.gift-pop .gift_container ul li:last-child{padding:0}
.giftcard.gift-pop .gift_container ul li>div{min-height:30px;margin-bottom:10px}
.giftcard.gift-pop .gift_inside>.close{position:Absolute;top:10px;left:10px;width:20px;height:20px;padding:4px;box-sizing:border-box;border:1px solid #0dabb6;cursor:pointer;border-radius:20px}
.giftcard.gift-pop .gift_inside>.close svg{width:100%;height:auto;fill:#0dabb6}
.giftcard.gift-pop .gift_inside>hr{height:2px;background:#e0e0e0;display:block;margin-bottom:10px}
.bottom-btns{display:block;text-align:center}
.bottom-btns>div{cursor:pointer;background:#0dabb6;display:inline-block;margin:0 5px 10px 5px;line-height:40px;padding:0 20px;box-sizing:border-box}
.fast-find{background:#0dabb6;margin:20px auto 0;display:block;padding:5px;border:1px solid #0dabb6;border-radius:8px;left:0;right:0;position:relative;box-sizing:border-box;max-width:300px;width:100%}
.fast-find .inputWrap{background:#fff;border-radius:8px;position:relative;height:50px}
.fast-find .inputWrap .submit{position:absolute;top:50%;left:5px;width:45px;height:45px;transform:translateY(-50%);background:#000;fill:#fff;border-radius:50px;padding:12px;box-sizing:border-box;cursor:pointer}
.fast-find .inputWrap input{position:absolute;top:0;right:0;left:0;bottom:0;width:100%;height:100%;border-radius:8px;background:0 0;font-size:18px;padding:6px 15px 0 15px;box-sizing:border-box}
.fast-find .inputWrap input+label{position:absolute;top:0;right:15px;font-size:14px;font-weight:500;color:#0dabb6}
@media (min-width: 992px) {
    .giftcard.gift-pop {max-width:calc(100vw - 300px);right:auto;}
}
</style>
<?
    $minDate = udb::single_row("SELECT MIN(`transDate`) AS `lowerDate`, MAX(`transDate`) AS `highestDate` FROM `gifts_purchases` WHERE `status` = 1 AND `terminal` = 'direct'");

    list($minYear, $minMonth) = explode('-', $minDate['lowerDate'] ? substr($minDate['lowerDate'], 0, 7) : date('Y-m'));
    list($maxYear, $maxMonth) = explode('-', $minDate['highestDate'] ? substr($minDate['highestDate'], 0, 7) : date('Y-m'));

    $currYear  = intval($_GET['year']) ?: date("Y");
    $currMonth = intval($_GET['month']) ?: date("n");

    $siteID = intval($_GET['siteID']);

    $where = [" gifts_purchases.terminal = 'direct' AND gifts_purchases.status = 1 AND gifts_purchases.transDate >= '" . $currYear . "-" . AddZero($currMonth) . "-01 00:00:00' AND gifts_purchases.transDate < '" . $currYear . "-" . AddZero($currMonth) . "-01 00:00:00' + INTERVAL 1 MONTH "];

?>
<div class="manageItems" id="manageItems">
    <h1>דו"ח שוברים ישירים - חדש</h1>
    <div class="searchCms">
		<form method="GET">
			<div class="inputWrap">
			<select name="year">
                <option>משנה</option>
                    <?
                    for($i=$minYear;$i<=$maxYear;$i++) {
                        echo '<option value="'.$i.'"' . ($currYear == $i ? ' selected="selected" ' : '') . '>'.$i.'</option>';
                    }
                    ?>
            </select>
			</div>

			<div class="inputWrap">
			<select name="month">
                <option>מחודש</option>
                <?
                    $addZero = '0';
                    for($i=1;$i<=12;$i++) {
                        if($i > 9) $addZero='';
                        $selected = $currMonth == $i ? ' selected="selected" ' : '';
                        echo '<option value="'.$i.'" '.$selected.'>'.$addZero.$i.'</option>';
                    }
                ?>
    		</select>
			</div>

			<div class="btnWrap">
			<a href="/cms/moduls/minisites/stats/index.php">נקה</a>
			<input type="submit" value="חפש">
			</div>
		</form>
	</div>
    <script src="/user/assets/js/giftcards.js?time=<?=time()?>"></script>
<?php
    if(!$siteID) {
?>
        <h3>מימוש שוברים מ <?=AddZero($currMonth)?>/<?=$currYear?></h3>
		<div class="excel" onclick="export_xl('#exp_xl')">ייצוא לאקסל</div>
<table class="giftcards-log" id="exp_xl" cellspacing="0">
    <thead>
        <tr>
            <th style="width:auto">שם בית העסק</th>
            <th style="width:250px">פרטי חשבון</th>
            <th style="width:100px">מספר שוברים</th>
            <th style="width:100px">סכום כולל</th>
            <th style="width:100px">סכום מימושים</th>
            <th style="width:100px">סך יתרות</th>
            <th style="width:100px">עלות כוללת</th>
            <th style="width:100px">אחוז עמלת</th>
            <th style="width:100px">סה"כ עמלה</th>
            <th style="width:100px">שולם</th>
        </tr>
    </thead>
    <tbody>
<?php
/*        $sql = "select siteID,giftCardCommission from sites ";
        $coms = udb::key_row($sql,"siteID");*/

        $que = "SELECT sites.siteID, sites.siteName, sites.giftCardCommission, sites.bankName, sites.bankNumber, sites.bankBranch, sites.bankAccount, sites.bankAcoountOwner,
                    COUNT(DISTINCT gifts_purchases.pID) AS `totalCards`, SUM(gifts_purchases.sum) AS `totalPaid`, SUM(gifts_purchases.voucherSum) AS `totalV`, SUM(gifts_purchases.commSum) AS `total_commissions`, AVG(gifts_purchases.commPrec) AS `avgPerc`,
                    SUM(giftCardsUsage.useageSum) AS `mimushSum`
                FROM `gifts_purchases` INNER JOIN `sites` USING(`siteID`)
                    LEFT JOIN `giftCardsUsage` USING(`pID`)
                WHERE " . implode(" AND ", $where) . "
                GROUP BY sites.siteID
                ORDER BY sites.siteName";
        $list = udb::single_list($que);


        /*$list = udb::full_list("SELECT giftCards.siteID,
			count(DISTINCT (giftCardsUsage.pID)) as totalCards,
			sum(giftCardsUsage.useageSum) as mimushSum,
			sum((giftCardsUsage.useageSum * giftCardsUsage.commission / 100)) as total_commissions,
			sum(gifts_purchases.sum) as totalPaid ,
			sites.siteName,sites.bankName,
			sites.bankNumber,
			sites.bankBranch,
			sites.bankAccount,
			sites.bankAcoountOwner 
			from giftCardsUsage 
			left join giftCards on (giftCards.giftCardID = giftCardsUsage.giftCardID) 
			left join gifts_purchases on(gifts_purchases.pID = giftCardsUsage.pID) 
			left JOIN sites on (sites.siteID = giftCards.siteID) 
			where ".implode(" AND ",$where)." 
			GROUP by giftCards.siteID");
        $paids = udb::key_row("select * from commissionPayments where pMonth=".$currMonth . " and pYear=".$currYear,"siteID");*/

        $totals = array(
            'totalCards' => 0,
            'totalPaid' => 0,
            'mimushSum' => 0,
            'left' => 0,
            'totalV' => 0,
            'percent' => '',
            'aToPay' => 0
        );
        foreach($list as $item) {
            /*$commi = ($coms[$item['siteID']]['giftCardCommission'] / 100)  * $item['mimushSum'];
            $commi = $item['total_commissions'];
			
			$startMonth =  $currYear."-".(str_pad($currMonth, 2, '0', STR_PAD_LEFT))."-01 00:00:00";
			
            $endMonth =  date("Y-m-d 00:00:00",strtotime($startMonth. " +1 month"));
			$que = "SELECT sum(gifts_purchases.sum) as totalPaid  
			FROM `gifts_purchases` 
			left join giftCards on(giftCards.giftCardID = gifts_purchases.giftCardID) 
			where `gifts_purchases`.sendTime>='".$startMonth."' and `gifts_purchases`.sendTime< '".$endMonth."' AND `gifts_purchases`.`paid`=1 and giftCards.siteID=".$item['siteID'];
			$toalCards = udb::single_value($que);
            $totals['totalCards'] += $item['totalCards'];
            //$totals['totalPaid'] += $item['totalPaid'];
            $totals['totalPaid'] += $toalCards;
            $totals['mimushSum'] += $item['mimushSum'];
            $totals['remains'] += ($item['totalPaid'] - $item['mimushSum']);
            $totals['commi'] += $commi;
            $totals['aToPay'] += ($item['totalPaid']-$commi);
            */

            $totals['totalCards'] += $item['totalCards'];
            $totals['totalPaid']  += $item['totalPaid'];
            $totals['mimushSum']  += $item['mimushSum'];
            $totals['totalV']     += $item['totalV'];
            $totals['aToPay']     += $item['total_commissions'];
?>
            <tr style="cursor:pointer;" onclick="location.href='report_direct.php?siteID=<?=$item['siteID']?>&month=<?=$currMonth?>&year=<?=$currYear?>'">
                <td><?=$item['siteName']?></td>
                <td>
<?php
            echo 'בנק: ' . $item['bankName'] . '<BR>';
            echo 'קוד בנק: ' . $item['bankNumber'] . '<BR>';
            echo 'סניף: ' . $item['bankBranch'] . '<BR>';
            echo 'מספר חשבון: ' . $item['bankAccount'] . '<BR>';
            echo 'בעל החשבון: ' . $item['bankAcoountOwner'] ;
?>
                </td>
                <td><?=$item['totalCards']?></td>
                <td><?=smartNF($item['totalV'])?></td>
                <td><?=smartNF($item['mimushSum'])?></td>
                <td><?=smartNF($item['totalV'] - $item['mimushSum'])?></td>
                <td><?=smartNF($item['totalPaid'])?></td>
                <td><?=round($item['avgPerc'])?>%</td>
                <td><?=smartNF($item['total_commissions'])?></td>
                <td><label class="switch">
						<input style="" type="checkbox" name="paid" id="paid<?=$item['siteID']?>" <?if($paids[$item['siteID']]) echo ' checked ';?> data-atopay="<?=$item['totalPaid']-$commi?>" data-siteid="<?=$item['siteID']?>" />
						<span class="slider round"></span>
					</label></td>
        </tr>
<?php
        }
?>
        <tr>
            <td align="center" colspan="2">סה"כ:</td>
<?php
        $totals['left'] = $totals['totalV'] - $totals['mimushSum'];
        foreach ($totals as $total)
            echo '<td>' . ($total ? smartNF($total) : $total) . '</td>';
?>
            <td></td>
        </tr>
    </tbody>
</table>
<?php
    }
    else {
            /*$sql = "select siteID,giftCardCommission,siteName from sites where siteID=".$siteID;
            $coms = udb::key_row($sql,"siteID");
            $siteName = $coms[$siteID]['siteName'];*/
        $siteName = udb::single_value("SELECT `siteName` FROM `sites` WHERE `siteID` = " . $siteID);
?>
<h3>מימוש שוברים מ <?=AddZero($currMonth)?>/<?=$currYear?> ב<?=$siteName?></h3>
<div class="excel" onclick="export_xl('#exp_xl2')">ייצוא לאקסל</div>
<input type="button" onclick="location.href = 'report_direct.php?month=<?=$currMonth?>&year=<?=$currYear?>'" class="addNew" id="back" value="חזרה לדף הקודם">
<table cellspacing="0" id='exp_xl2'>
    <thead>
        <tr>
            <th style="width:200px">זמן רכישה</th>
            <th style="width:auto">שם מזמין השבור</th> <?//needs to add who used the giftCard?>
            <th style="width:150px">טלפון</th>
            <th style="width:250px">שם השובר</th>
            <th style="width:150px">קוד קופון</th>
            <th style="width:auto">הערות מימוש</th>
            <th style="width:80px">עלות השובר</th>
            <th style="width:80px">עמלה</th>
            <th style="width:80px">זכאות</th>
            <!-- th style="width:80px">זיכוי</th -->
        </tr>
    </thead>
    <tbody>
<?php
        $where[] = " gifts_purchases.siteID = " . $siteID;

        $que = "SELECT gifts_purchases.*, SUM(giftCardsUsage.useageSum) AS `mimushSum`, GROUP_CONCAT(giftCardsUsage.comments SEPARATOR '~~~') AS `comments`
                FROM `gifts_purchases` LEFT JOIN `giftCardsUsage` USING(`pID`)
                WHERE " . implode(" AND ", $where) . "
                GROUP BY gifts_purchases.pID";
        $list = udb::single_list($que);

                /*$where[] = " giftCards.siteID=".$siteID;

                $list = udb::full_list("SELECT giftCards.siteID,giftCards.title,giftCardsUsage.useageSum,giftCardsUsage.comments,giftCardsUsage.usageDate,giftCardsUsage.pID,
gifts_purchases.giftSender,gifts_purchases.famname,gifts_purchases.giftPhoneSender,gifts_purchases.sum,gifts_purchases.ordersID,
sites.siteName,sites.bankName,sites.bankNumber,sites.bankBranch,sites.bankAccount,sites.bankAcoountOwner from giftCardsUsage 
left join giftCards on (giftCards.giftCardID = giftCardsUsage.giftCardID) 
left join gifts_purchases on(gifts_purchases.pID = giftCardsUsage.pID) 
left JOIN sites on (sites.siteID = giftCards.siteID) where ".implode(" AND ",$where));
                $paids = udb::key_row("select * from commissionPayments where pMonth=".$currMonth . " and pYear=".$currYear,"siteID");*/

        $totals = array('useageSum' => 0 ,'paidSum' => 0 ,'aToPay' => 0);

        foreach($list as $item) {
                    //$commi = ($coms[$siteID]['giftCardCommission'] / 100)  * $item['useageSum'];
            $totals['useageSum'] += $item['mimushSum'];
            $totals['paidSum'] += $item['sum'];
            $totals['aToPay'] += $item['commSum'];
?>
        <tr>
            <td style="direction:ltr; text-align:center"><?=date("d/m/Y H:i", strtotime($item['transDate']))?></td>
            <td><?=$item['giftSender'] . ' ' . $item['famname'] ?></td>
            <td><?=$item['giftPhoneSender']?></td>
            <td><?=$item['giftTitle']?></td>
            <td><?=$item['ordersID']?></td>
            <td><?=str_replace('~~~', '<br />', $item['comments'])?></td>
            <td><?=$item['sum']?></td>
            <!-- td><?=round($item['mimushSum'], 2)?></td -->
            <td><?=$item['commPrec'] . '%'?></td>
            <td><?=round($item['commSum'],2)?></td>
            <!-- tD><input type="button" class="addNew" id="addNewAcc" value="זיכוי עמלה"></tD -->
        </tr>
<?php
        }
?>
        <tr>
            <td colspan="6"><?=count($list)?> שוברים</td>

            <td><?=smartNF($totals['paidSum'])?></td>
            <!-- td><?=smartNF($totals['useageSum'])?></td -->
            <td>&nbsp;</td>
            <td><?=smartNF($totals['aToPay'])?></td>

            <!-- td></td -->
        </tr>
    </tbody>
</table>
<?php
    }
?>
</div>
<script>
    $(".switch input").on("change", function(){
        var siteId = $(this).data("siteid");
        $.post("ajax_updatePayed.php",{siteID: siteId , month: <?=$currMonth?>, year: <?=$currYear?>},function(res){

        });
    });
</script>
<?php

if (!$_GET["tab"]) include_once "../../../bin/footer.php";
