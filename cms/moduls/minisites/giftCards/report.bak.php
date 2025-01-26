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
    $minDate = udb::single_row("select min(usageDate) as lowerDate,max(usageDate) as highestDate from giftCardsUsage");
    $minYear = intval(date("Y",strtotime($minDate['lowerDate'])));
    $minMonth = intval(date("m",strtotime($minDate['lowerDate'])));
    $maxYear = intval(date("Y",strtotime($minDate['highestDate'])));
    $maxMonth = intval(date("m",strtotime($minDate['highestDate'])));
    $where = [];
    $where[] = " gifts_purchases.terminal = 'company' ";
    if(intval($_GET['year'])) {
        $currYear = intval($_GET['year']);
        $where[] = "  YEAR(usageDate)='".$currYear."'";
        if(intval($_GET['month'])) {
            $currMonth = intval($_GET['month']);
            $where[] = "  MONTH(usageDate)='".$currMonth."'";
        }

    }
    else {
        $currYear = intval(date("Y"));
        $currMonth = intval(date("m"));
        $where[] = "  YEAR(usageDate)='".$currYear."'";
        $where[] = "  MONTH(usageDate)='".$currMonth."'";
    }



?>
<div class="manageItems" id="manageItems">
    <h1>דו"ח שוברים</h1>
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

    <?if(intval($_GET['siteID']) == 0) {
        ?>
        <h3>מימוש שוברים מ <?=$currMonth < 10 ?'0':'';?><?=$currMonth?>/<?=$currYear?></h3>
        <?/*<A href="export.php?<?=$_SERVER['QUERY_STRING'];?>" target="_blank">ייצוא ל EXCEL</A>*/?>
		<div class="excel" onclick="export_xl('#exp_xl')">ייצוא לאקסל</div>
        <table class="giftcards-log" id="exp_xl" cellspacing="0">
    <thead>
        <tr>
            <th>שם בית העסק</th>
            <th>פרטי חשבון</th>
            <th width="100">מספר שוברים</th>
            <th>סכום כולל</th>
            <th>סכום מימושים</th>
            <th>סך יתרות</th>
            <th>עמלה</th>
            <th>סה"כ תשלום</th>
            <th>שולם</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "select siteID,giftCardCommission from sites ";
        $coms = udb::key_row($sql,"siteID");
        $list = udb::full_list("SELECT giftCards.siteID,
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
        $paids = udb::key_row("select * from commissionPayments where pMonth=".$currMonth . " and pYear=".$currYear,"siteID");
        $totals = array(
            'totalCards' => 0,
            'totalPaid' => 0,
            'mimushSum' => 0,
            'remains' => 0,
            'commi' => 0,
            'aToPay' => 0
        );
        foreach($list as $item) {
            $commi = ($coms[$item['siteID']]['giftCardCommission'] / 100)  * $item['mimushSum'];
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
            ?>
            <tr style="cursor:pointer;" onclick="location.href = 'report.php?siteID=<?=$item['siteID']?>&month=<?=$currMonth?>&year=<?=$currYear?>'">
                <td><?=$item['siteName']?></td>
                <td><?
					//echo $que . "<br>";
                    echo 'בנק: ' . $item['bankName'] . '<BR>';
                    echo 'קוד בנק: ' . $item['bankNumber'] . '<BR>';
                    echo 'סניף: ' . $item['bankBranch'] . '<BR>';
                    echo 'מספר חשבון: ' . $item['bankAccount'] . '<BR>';
                    echo 'בעל החשבון: ' . $item['bankAcoountOwner'] ;
                    ?></td>
                <td><?=$item['totalCards']?></td>
                <td><?=$toalCards//$item['totalPaid']?></td>
                <td><?=$item['mimushSum']?></td>
                <td><?=$item['totalPaid'] - $item['mimushSum']?></td>
                <td><?=$commi?></td>
                <td><?=$item['totalPaid']-$commi?></td>
                <td><label class="switch">
						<input style="" type="checkbox" name="paid" id="paid<?=$item['siteID']?>" <?if($paids[$item['siteID']]) echo ' checked ';?> data-atopay="<?=$item['totalPaid']-$commi?>" data-siteid="<?=$item['siteID']?>" />
						<span class="slider round"></span>
					</label></td>
        </tr>
        <?php } ?>
        <tr>
        <td></td>
        <td align="left">סה"כ:</td>
            <?
            foreach ($totals as $total) {
                echo '<td>'.$total.'</td>';
            }
            ?>

            <td></td>
    </tr>
    </tbody>
</table>
        <?
        }
        else {
            $siteID = intval($_GET['siteID']);
            $sql = "select siteID,giftCardCommission,siteName from sites where siteID=".$siteID;
            $coms = udb::key_row($sql,"siteID");
            $siteName = $coms[$siteID]['siteName'];
            ?>
            <h3>מימוש שוברים מ <?=$currMonth < 10 ?'0':'';?><?=$currMonth?>/<?=$currYear?> ב<?=$siteName?></h3>
            <?/*<A href="export.php?<?=$_SERVER['QUERY_STRING'];?>" target="_blank">ייצוא ל EXCEL</A>*/?>
			<div class="excel" onclick="export_xl('#exp_xl2')">ייצוא לאקסל</div>
            <input type="button" onclick="location.href = 'report.php?month=<?=$currMonth?>&year=<?=$currYear?>'" class="addNew" id="back" value="חזרה לדף הקודם">
            <table cellspacing="0" id="exp_xl2">
                <thead>
                    <tr>
                        <th>תאריך מימוש</th>
                        <th>שם מזמין השבור</th> <?//needs to add who used the giftCard?>
                        <th>טלפון</th>
                        <th>שם השובר</th>
                        <th>קוד קופון</th>
                        <th>הערות מימוש</th>
                        <th>סכום השובר</th>
                        <th>סכום המימוש</th>
                        <th>עמלה</th>
                        <th>זכאות</th>
                        <th>זיכוי</th>
                    </tr>
                </thead>
                <tbody>
                <?

                $where[] = " giftCards.siteID=".$siteID;

                $list = udb::full_list("SELECT giftCards.siteID,giftCards.title,giftCardsUsage.useageSum,giftCardsUsage.comments,giftCardsUsage.usageDate,giftCardsUsage.pID,
gifts_purchases.giftSender,gifts_purchases.famname,gifts_purchases.giftPhoneSender,gifts_purchases.sum,gifts_purchases.ordersID,
sites.siteName,sites.bankName,sites.bankNumber,sites.bankBranch,sites.bankAccount,sites.bankAcoountOwner from giftCardsUsage 
left join giftCards on (giftCards.giftCardID = giftCardsUsage.giftCardID) 
left join gifts_purchases on(gifts_purchases.pID = giftCardsUsage.pID) 
left JOIN sites on (sites.siteID = giftCards.siteID) where ".implode(" AND ",$where));
                $paids = udb::key_row("select * from commissionPayments where pMonth=".$currMonth . " and pYear=".$currYear,"siteID");
                $totals = array('useageSum' => 0 ,'comi' => 0 ,'aToPay' => 0);
                foreach($list as $item) {
                    $commi = ($coms[$siteID]['giftCardCommission'] / 100)  * $item['useageSum'];
                    $totals['useageSum'] +=$item['useageSum'];
                    $totals['comi'] +=$commi;
                    $totals['aToPay'] += ($item['useageSum']-$commi);

                    ?>
                    <tr>
                        <td><?=date("d/m/Y H:i",strtotime($item['usageDate']))?></td>
                        <td><?=$item['giftSender'] . ' ' . $item['famname'] ?></td>
                        <td><?=$item['giftPhoneSender']?></td>
                        <td><?=$item['title']?></td>
                        <td><?=$item['ordersID']?></td>
                        <td><?=$item['comments']?></td>
                        <td><?=$item['sum']?></td>
                        <td><?=$item['useageSum']?></td>
                        <td><?=$coms[$siteID]['giftCardCommission'] . '%'?> <?=$commi?></td>
                        <td><?=$item['useageSum']-$commi?></td>
                        <tD><input type="button" class="addNew" id="addNewAcc" value="זיכוי עמלה"></tD>
                    </tr>
                    <?
                    }
                    ?>
                    <tR>
                        <td colspan="7"></td>

                        <td><?=$totals['useageSum']?></td>
                        <td><?=$totals['comi']?></td>
                        <td><?=$totals['aToPay']?></td>

                        <td></td>
                    </tR>
                </tbody>
            </table>
            <?
        }


    ?>
</div>
<script>
    $(".switch input").on("change",function(){
        var siteId = $(this).data("siteid");
        $.post("ajax_updatePayed.php",{siteID: siteId , month: <?=$currMonth?>, year: <?=$currYear?>},function(res){

        });
    });
</script>
<?

if (!$_GET["tab"]) include_once "../../../bin/footer.php";
?>
