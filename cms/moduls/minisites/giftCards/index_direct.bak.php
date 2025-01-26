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

function date2U($date){
    $d = array_map('intval', explode('/', $date));
    return (count($d) == 3 && checkdate($d[1], $d[0], $d[2])) ? implode('-', [$d[2], str_pad($d[1], 2, '0', STR_PAD_LEFT), str_pad($d[0], 2, '0', STR_PAD_LEFT)]) : null;
}

function smartNF($num){
    return rtrim(rtrim(number_format($num, 2), '0'), '.');
}

?>
<script src="/user/assets/js/swal.js?v=1"></script>
<style>

.giftcard.gift-pop{position:fixed;top:0;left:0;bottom:0;background:rgba(0,0,0,.6);width:100%;right:0;height:100vh;z-index:9}
.giftcard.gift-pop .gift_container{position:absolute;top:50%;right:50%;transform:translateY(-50%) translateX(50%);width:100%;max-width:800px;background:#e0e0e0;padding:10px;text-align:right;box-sizing:border-box}
.giftcard.gift-pop .gift_inside{background:#fff;box-shadow:0 0 2px rgb(0 0 0 / 60%);position:relative;}
.giftcard.gift-pop .gift_inside>.title{padding:20px;box-sizing:border-box}
.giftcard.gift-pop .gift_container ul {list-style: none;font-size: 0;padding: 10px;box-sizing: border-box;max-height: calc(100vh - 220px);overflow: auto;}
.giftcard.gift-pop .gift_container ul li {display: inline-block;width: 100%;max-width: 33.33%;font-size: 16px;padding-left: 20px;box-sizing: Border-box;}
.giftcard.gift-pop .gift_container ul li>div .title{display:inline-block;width:130px;color:#9e9e9e;padding-bottom:4px}
.giftcard.gift-pop .gift_container ul li>div .con{display:inline-block;width:100%}
.giftcard.gift-pop .gift_container ul li:last-child{padding:0;}
.giftcard.gift-pop.mimush .gift_container ul li:last-child{max-width:66%}
.giftcard.gift-pop .gift_container ul li>div{min-height:30px;margin-bottom:10px}
.giftcard.gift-pop .gift_inside>.close{position:Absolute;top:10px;left:10px;width:20px;height:20px;padding:1px 4px;box-sizing:border-box;border:1px solid #0dabb6;cursor:pointer;border-radius:20px}
.giftcard.gift-pop .gift_inside>.close svg{width:100%;height:auto;fill:#0dabb6}
.giftcard.gift-pop .gift_inside>hr{height:2px;background:#e0e0e0;display:block;margin-bottom:10px}
.bottom-btns{display:block;text-align:center}
.bottom-btns>div{cursor:pointer;background:#0dabb6;display:inline-block;margin:0 5px 10px 5px;line-height:40px;padding:0 20px;box-sizing:border-box;color:white}
.fast-find{background:#0dabb6;margin:20px auto 0;display:block;padding:5px;border:1px solid #0dabb6;border-radius:8px;left:0;right:0;position:relative;box-sizing:border-box;max-width:300px;width:100%}
.fast-find .inputWrap{background:#fff;border-radius:8px;position:relative;height:50px}
.fast-find .inputWrap .submit{position:absolute;top:50%;left:5px;width:45px;height:45px;transform:translateY(-50%);background:#000;fill:#fff;border-radius:50px;padding:12px;box-sizing:border-box;cursor:pointer}
.fast-find .inputWrap input{position:absolute;top:0;right:0;left:0;bottom:0;width:100%;height:100%;border-radius:8px;background:0 0;font-size:18px;padding:6px 15px 0 15px;box-sizing:border-box}
.fast-find .inputWrap input+label{position:absolute;top:0;right:15px;font-size:14px;font-weight:500;color:#0dabb6}


.searchOrder {margin: 20px auto 0;display: block;padding: 13px 30px;border: 1px solid #0dabb6;border-radius: 8px;background: #fff;left: 0;right: 0;position: relative;max-width: 240px;overflow: hidden;}
.searchOrder form {margin-top: 10px;}
.searchOrder form .inputWrap {margin: 5px;}
.searchOrder form .inputWrap input[type=text] {height: 40px;box-sizing: border-box;padding-right: 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;width: 100%;}
.searchOrder form .clear {text-decoration: none;font-size: 16px;display: inline-block;vertical-align: top;background: #fff;color: #0dabb6;border-radius: 5px;margin: 5px;border: 1px #0dabb6 solid;float: right;line-height: 40px;width: 60px;text-align: center;}
.searchOrder form input[type=submit] {display: block;vertical-align: top;background: #0dabb6;color: #fff;border-radius: 5px;cursor: pointer;margin: 5px;width: 100px;float: left;font-size: 20px;line-height: 40px;}
.searchOrder form .inputWrap select {height: 40px;box-sizing: border-box;padding-right: 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;text-align: right;width: 100%;}
.giftcard.gift-pop .gift_container ul li>div .con input {height: 30px;border: 1px #aaa solid;padding: 0 10px;width: 100%;box-sizing: border-box;}
.giftcard.gift-pop.mimush .gift_container {max-width: 570px;}

@media (min-width: 992px) {
    .giftcard.gift-pop {}
}

@media(max-width:700px){
	.giftcard.gift-pop .gift_container ul li{max-width:100%}
}

</style>
<?php

$createDate   = date2U($_GET['createDate']);
$createDateTo = date2U($_GET['createDateTo']);
$useDate      = date2U($_GET['usingDate']);
$useDateTo    = date2U($_GET['usingDateTo']);

$siteID   = intval($_GET['selectedSite']);
$freeText = typemap($_GET['free'], 'string');

$sites = udb::key_value("SELECT DISTINCT gifts_purchases.siteID, sites.siteName FROM `gifts_purchases` INNER JOIN `sites` USING(`siteID`) WHERE gifts_purchases.terminal = 'direct' AND gifts_purchases.status = 1");



//$sql_sum = "select pID,giftCards.siteID,sites.siteName,sum(useageSum) as totalUsage from giftCardsUsage inner join gifts_purchases using (pID) left join giftCards on (gifts_purchases.giftCardID = giftCards.giftCardID) left join sites on (giftCards.siteID = sites.siteID) where gifts_purchases.terminal = 'direct'  group by pID";
//$sums = udb::key_row($sql_sum,"pID");
?>
<div class="manageItems" id="manageItems">
    <h1>מימוש שוברים ישירים - חדש</h1>
    <script src="/user/assets/js/giftcards.js?time=<?=time()?>"></script>

    <div class="searchOrder">
        <div class="ttl">חפש פעולות</div>
        <form method="GET" autocomplete="off" action="">
            <div class="inputWrap">
                <select  name="selectedSite" title="בחר עסק">
                    <option value="">בחר עסק</option>
<?php
//    $displyedSites = [];
//    foreach ($sums as $item) {
//        if(in_array($item['siteID'],$displyedSites) === false) {
//            $displyedSites[] = $item['siteID'];
//            $selected = "";
//            if(intval($item['siteID']) == intval($_GET['selectedSite'])) $selected = " selected ";
//            echo '<option value="'.$item['siteID'].'" '.$selected.'>'.$item['siteName'].'</option>';
//        }
//    }

    foreach($sites as $sid => $siteName)
        echo '<option value="' . $sid . '" ' . ($sid == $siteID ? 'selected' : '') . '>' . $siteName . '</option>';
?>

                </select>
            </div>
			<?
			$createDate = $_GET['createDate'];
            $createDateTo = $_GET['createDateTo'];
            $usingDate = $_GET['usingDate'];
            $usingDateTo = $_GET['usingDateTo'];

			?>

            <div class="inputWrap half">
                <input type="text" name="createDate" placeholder="תאריך הפקה מ" class="searchFrom" value="<?=implode('/',array_reverse(explode('-',trim($createDate))))?>" readonly>
            </div>
            <div class="inputWrap half">
                <input type="text" name="createDateTo" placeholder="תאריך הפקה עד" class="searchFrom" value="<?=implode('/',array_reverse(explode('-',trim($createDateTo))))?>" readonly>
            </div>
            <div class="inputWrap half">
                <input type="text" name="usingDate" placeholder="תאריך מימוש מ" value="<?=implode('/',array_reverse(explode('-',trim($useDate))))?>" class="searchTo" readonly>
            </div>
            <div class="inputWrap half">
                <input type="text" name="usingDateTo" placeholder="תאריך מימוש עד" value="<?=implode('/',array_reverse(explode('-',trim($useDateTo))))?>" class="searchTo" readonly>
            </div>
            <div class="inputWrap">
                <select  name="type" title="סוג מימוש">
                    <option value="">סוג מימוש</option>
                    <option value="1" <?=($_GET['type'] == 1 ? " selected " : "")?> >חלקי</option>
                    <option value="2" <?=($_GET['type'] == 2 ? " selected " : "")?> >מלא</option>
                </select>
            </div>

            <div class="inputWrap">
                <input type="text" name="free" placeholder="חיפוש חופשי" value="<?=str_replace('"', '&quot;', $freeText)?>" />
            </div>

            <a class="clear" href="index.php">נקה</a>
            <input type="submit" value="חפש">

        </form>
    </div>

    <div class="fast-find">
        <div class="inputWrap">
            <input type="text" name="giftnum" id="giftnum" placeholder="הקלידו מספר שובר">
            <label for="giftnum">איתור שובר מהיר</label>
            <div class="submit" onclick="searchShovar($('#giftnum').val())"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" viewBox="0 0 447.2 447.2" xml:space="preserve"><path d="M420.4 192.2c-1.8-0.3-3.7-0.4-5.5-0.4H99.3l6.9-3.2c6.7-3.2 12.8-7.5 18.1-12.8l88.5-88.5c11.7-11.1 13.6-29 4.6-42.4 -10.4-14.3-30.5-17.4-44.7-6.9 -1.2 0.8-2.2 1.8-3.3 2.8l-160 160C-3.1 213.3-3.1 233.5 9.4 246c0 0 0 0 0 0l160 160c12.5 12.5 32.8 12.5 45.3-0.1 1-1 1.9-2 2.7-3.1 9-13.4 7-31.3-4.6-42.4l-88.3-88.6c-4.7-4.7-10.1-8.6-16-11.7l-9.6-4.3h314.2c16.3 0.6 30.7-10.8 33.8-26.9C449.7 211.5 437.8 195.1 420.4 192.2z"></path></svg></div>
        </div>
    </div>
<?php
//    $sql = "select siteID,giftCardCommission from sites ";
//    $coms = udb::key_row($sql,"siteID");
//    //print_r($sums);
//    $sql = "select siteID,giftCardCommission from sites ";
//    $coms = udb::key_row($sql,"siteID");
//    $where = [];
//    $where[] = "  and gifts_purchases.terminal = 'direct' ";

$where = [" gifts_purchases.terminal = 'direct' AND gifts_purchases.status = 1 "];

    if($freeText){
//        $isID = '0';
//        if (is_numeric($_GET['free']))
//            $isID = 'gifts_purchases.pID = ' . intval($_GET['free']);
//        $where[] = " (" . $isID . " OR gifts_purchases.`giftTitle` like '%".inDb($_GET['free'])."%' or gifts_purchases.giftSender like '%".inDb($_GET['free'])."%' or gifts_purchases.famname like '%".inDb($_GET['free'])."%')" ;

        if (is_numeric($freeText)){
            $isID = intval($freeText);
            $where[] = " (gifts_purchases.pID = " . $isID . " OR gifts_purchases.sum = " . $isID . " OR gifts_purchases.ordersID LIKE '%" . $isID . "%' OR gifts_purchases.giftPhoneSender LIKE '%" . $isID . "%' OR gifts_purchases.giftPhoneReciver LIKE '%" . $isID . "%' OR gifts_purchases.giftEmailSender LIKE '%" . $isID . "%' OR gifts_purchases.giftEmailReciver LIKE '%" . $isID . "%') ";
        }
        else {
            $isID = udb::escape_string($freeText);
            $where[] = " (gifts_purchases.giftTitle LIKE '%" . $isID . "%' OR gifts_purchases.giftSender LIKE '%" . $isID . "%' OR gifts_purchases.famname LIKE '%" . $isID . "%' OR gifts_purchases.giftEmailSender LIKE '%" . $isID . "%' OR gifts_purchases.giftEmailReciver LIKE '%" . $isID . "%') ";
        }
    }

if (!$createDate && !$createDateTo && !$useDate && !$useDateTo){
    $createDate   = date('Y-m-01');
    $createDateTo = date('Y-m-t');
}

    if($createDate)
        $where[] = " gifts_purchases.transDate >= '" . $createDate . " 00:00:00'";
    if($createDateTo)
        $where[] = " gifts_purchases.transDate <= '" . $createDateTo . " 23:59:59'";

    if($useDate)
        $where[] = " giftCardsUsage.usageDate >= '" . $useDate . " 00:00:00'";
    if($useDateTo)
        $where[] = " giftCardsUsage.usageDate <= '" . $useDateTo . " 23:59:59'";

    if($siteID)
        $where[] = " gifts_purchases.siteID = " . $siteID;

    $que = "SELECT gifts_purchases.*, SUM(giftCardsUsage.useageSum) AS `useageSum`, GROUP_CONCAT(DATE(giftCardsUsage.usageDate) SEPARATOR '~~~') AS `usageDates`, sites.siteName, sites.bussinessName, giftCards.daysValid
            FROM `gifts_purchases` INNER JOIN `sites` USING(`siteID`) INNER JOIN `giftCards` USING(`giftCardID`)
                LEFT JOIN `giftCardsUsage` USING(`pID`)
            WHERE " . implode(" AND ", $where) . "
            GROUP BY gifts_purchases.pID";
    $list = udb::single_list($que);

//    $sqlNew = "select gifts_purchases.*,`giftCardsUsage`.`giftCardID`,`giftCardsUsage`.`useageSum`,`giftCardsUsage`.usageDate,`giftCardsUsage`.`comments`,`giftCardsUsage`.`commission`,giftCards.daysValid,giftCards.siteID,giftCards.title,sites.siteName
//	from gifts_purchases
//	left join giftCardsUsage on (giftCardsUsage.pID = gifts_purchases.pID)
//	left join giftCards on (giftCards.giftCardID = gifts_purchases.giftCardID)
//	left join sites on (sites.siteID = giftCards.siteID)
//	where  gifts_purchases.paid=1 " . ($where ?  implode(" and ",$where) : "") . "
//	order by gifts_purchases.pID DESC ";
?>
	<style>
	.giftcards-log thead th{position:sticky;z-index:1;background:white;top:0;box-shadow:0 -1px 1px #333 inset;line-height:1;height:30px}
	.giftcards-log tbody th{position:sticky;z-index:1;background:white;bottom:0;font-weight:bold;height:50px;vertical-align:middle;box-shadow:0 1px 1px #333 inset;line-height:1}
	.usageType1 td, .usageType1{color:#00af00}
	.usageType2 td, .usageType2{color:#028993}
	.usageType3 td, .usageType3{color:#00007c}
    .usageType9 td, .usageType9{color:#e79b14}
	.usageTypes{line-height:1;font-size:12px}
	.usageTypes div{margin-top:3px}

	</style>
	
	<div class="excel" onclick="export_xl('#exp_xl')">ייצוא לאקסל</div>
    <table class="giftcards-log" cellspacing="0" id="exp_xl" style="overflow:auto">
    <thead>
        <tr>
            <th style="width:120px;">מספר</th>
            <th style="width:auto;">שם הכרטיס</th>
            <th style="width:120px;">מקור</th>
            <th style="width:auto">עסק</th>
            <th style="width:120px">ת. רכישה</th>
            <!-- th>הזמנה</th -->
            <th style="width:120px">ת. מימוש</th>
            <th style="width:100px;">עלות</th>
            <th style="width:100px;">מימוש</th>
            <th style="width:100px;">יתרה</th>
            <th style="width:100px;">עמלה</th>
            <th style="width:120px;">סכום עמלה</th>
            <th style="width:80px;">סטטוס</th>
            <th style="width:120px">תוקף</th>
        </tr>
    </thead>
    <tbody>
<?php
    $totals = ['paidSum' => 0, 'commission' => 0, 1 => 0, 2 => 0, 3 => 0, 9 => 0];
    foreach($list as $item) {
            //$commission = ($coms[$item['siteID']]['giftCardCommission'] / 100)  * $item['useageSum'];
//            $commrate = ($item['commission']) ?: $coms[$item['siteID']]['giftCardCommission'];
//            $commission = (($commrate / 100)  * $item['useageSum']);
//            if($_GET['type']) {
//                if($_GET['type'] == 1) {
//                    if(!$item['useageSum'] || $item['sum'] == $item['useageSum']){
//                        continue;
//                    }
//                }
//                if($_GET['type'] == 2) {
//                    if( $item['sum'] != $item['useageSum']){
//                        continue;
//                    }
//                }
//            }
//			if(in_array($item['pID'],$showed)){
//
//			}
//			$showed[]=$item['pID'];

        $usageType = $item['refunded'] ? 9 : (!$item['useageSum']? 1 : ($item['sum'] - $item['useageSum']>0? 2 : 3));
?>
        <tr onclick="showPOP(<?=$item['pID']?>)" class="usageType<?=$usageType?>">
            <td><?=$item['ordersID']?></td>
            <td style="text-align:right"><?=$item['giftTitle']?></td>
            <td>Vouchers.co.il</td>
            <td><?=($item['siteName'] . ($item['bussinessName'] ? ' (' . $item['bussinessName'] . ')' : ''))?></td>
            <td><?=date("d/m/Y", strtotime($item['transDate']))?></td>
            <!-- td>#<?=$item['pID']?></td -->
            <td><?=implode('<br />', array_map('db2date', explode('~~~', $item['usageDates'])))?></td>
            <td><?=$item['sum']?></td>
            <td>₪<?=smartNF($item['useageSum'])?></td>
            <td>₪<?=smartNF($item['sum'] - $item['useageSum'])?></td>
			<td><?=($item['commPrec'] . '%')?></td>
            <td>₪<?=smartNF($item['commSum'])?></td>
            <td width="100" style="text-align:right;">
<?php
                if ($item['refunded'])
                    echo 'בוצע זיכוי';
                elseif(!$item['useageSum']) {
                    echo 'הונפק';
                }
                else {
                    echo ($item['sum'] - $item['useageSum'] > 0) ? 'מומש חלקית' : 'מומש';
                }
?>
            </td>
            <td><?=date("d/m/Y", strtotime($item['transDate'] . " +".$item['daysValid']." months"))?></td>
        </tr>
<?php
		$totals['paidSum'] += $item['sum'];
		$totals['commission'] += $item['commSum'];
		$totals[$usageType]++;
    }
?>
		<tr>
			<th colspan="3"><?=count($list)?> שוברים</th>
			<th></th>
			<th></th>
			<th></th>
            <th>₪<?=smartNF($totals['paidSum'])?></th>
            <th></th>
			<th></th>
            <th></th>
            <th>₪<?=smartNF($totals['commission'])?></th>
			<th class="usageTypes" colspan="2">
				<div class="usageType1"><?=$totals[1]?: "0"?> - הונפקו</div>
				<div class="usageType3"><?=$totals[3]?: "0"?> - מימוש מלא</div>
				<div class="usageType2"><?=$totals[2]?: "0"?> - מימוש חלקי</div>
                <div class="usageType9"><?=$totals[9]?: "0"?> - זוכתו</div>
			</th>
		</tr>
    </tbody>
</table>
</div>

<div class="giftcard gift-pop gift" style="display: none">

</div>
<div class="giftcard gift-pop mimush" style="display: none">
     <div class="gift_container">
        <div class="gift_inside">
            <div class="title">מימוש הגיפט קארד - <span id="mimushLeftSum"></span></div>
            <div class="close" onclick="$('.giftcard.gift-pop.mimush').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>

			 <form id="mimushShovar">
			 <ul>
				 <li>
					  <div class="gift-balance">
							<div class="title">סכום למימוש</div>
							<div class="con"><input type="text" name="sumToUse" id="sumToUse" class="num" value=""></div>
						</div>
				 </li>
				 <li>
					  <div class="gift-balance">
							<div class="title">הערות מממש</div>
							<div class="con"><input type="text" name="commentsUsage" id="commentsUsage" value=""></div>
						</div>
				 </li>
			 </ul>
			 <div class="bottom-btns">
				 <div class="part">ממש את השובר</div>
			 </div>
			 </form>
		 </div>
     </div>
</div>
<script>
$('.searchFrom').datepicker({
    format: 'd/m/Y',
    timepicker: false

});
$('.searchTo').datepicker({
    format: 'd/m/Y',
    onShow:function( ct ){
        this.setOptions({
            minDate:$('.searchFrom').val()?$('.searchFrom').val().split("/").reverse().join("-"):false
        })
    },
    timepicker: false
});

$.datetimepicker.setLocale('he');

</script>
<?
if (!$_GET["tab"]) include_once "../../../bin/footer.php";

