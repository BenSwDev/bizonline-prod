<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$siteID = $_CURRENT_USER->active_site() ?: 0;
?>
<link rel="stylesheet" href="/user/assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=giftcardlog&v=<?=time()?>" />
<script src="/user/assets/js/giftcards.js?time=<?=time()?>"></script>
<section class="giftcards">
    <div class="title">גיפטקארד רכישות ומימושים</div>


    <div class="searchOrder">
	<div class="ttl">חפש פעולות</div>
	<form method="GET" autocomplete="off" action="">
        <input type="hidden" name="page" value="<?=$_GET['page']?>">
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
                <input type="text" name="usingDate" placeholder="תאריך מימוש מ" value="<?=implode('/',array_reverse(explode('-',trim($usingDate))))?>" class="searchTo" readonly>
            </div>
            <div class="inputWrap half">
                <input type="text" name="usingDateTo" placeholder="תאריך מימוש עד" value="<?=implode('/',array_reverse(explode('-',trim($usingDateTo))))?>" class="searchTo" readonly>
            </div>
        <div class="inputWrap">
            <select  name="type" >
                <option value="">סוג מימוש</option>
                <option value="1" <?=$_GET['type'] == 1 ? " selected " : ""?> >חלקי</option>
                    <option value="2" <?=$_GET['type'] == 2 ? " selected " : ""?> >מלא</option>
            </select>
        </div>

        <div class="inputWrap">
            <input type="text" name="free" placeholder="חיפוש חופשי" value="<?=$_GET['free']?>" />
        </div>

		<a class="clear" href="?page=<?=$_GET['page']?>">נקה</a>
		<input type="submit" value="חפש">
		
	</form>	
</div>
    <div style="clear:both;"></div>
    <div class="add-new" style="display: none" onclick="loadGiftCardData(0)">הוסף חדש</div>
    <div class="page-options" style="display: none" onclick="loadGeneralForm()">הגדרות תצוגת עמוד</div>

    <div class="clear"></div>
</section>

<div class="fast-find">
    <div class="inputWrap">
        <input type="text" name="giftnum" id="giftnum" placeholder="הקלידו מספר שובר">
        <label for="giftnum">איתור שובר מהיר</label>
        <div class="submit" onclick="searchShovar($('#giftnum').val())"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" viewBox="0 0 447.2 447.2" xml:space="preserve"><path d="M420.4 192.2c-1.8-0.3-3.7-0.4-5.5-0.4H99.3l6.9-3.2c6.7-3.2 12.8-7.5 18.1-12.8l88.5-88.5c11.7-11.1 13.6-29 4.6-42.4 -10.4-14.3-30.5-17.4-44.7-6.9 -1.2 0.8-2.2 1.8-3.3 2.8l-160 160C-3.1 213.3-3.1 233.5 9.4 246c0 0 0 0 0 0l160 160c12.5 12.5 32.8 12.5 45.3-0.1 1-1 1.9-2 2.7-3.1 9-13.4 7-31.3-4.6-42.4l-88.3-88.6c-4.7-4.7-10.1-8.6-16-11.7l-9.6-4.3h314.2c16.3 0.6 30.7-10.8 33.8-26.9C449.7 211.5 437.8 195.1 420.4 192.2z"></path></svg></div>
    </div>  
</div>
<style>
</style>
<table class="giftcards-log" cellspacing="0">
    <thead>
        <tr>
            <th>מספר</th>
            <th>שם הכרטיס</th>
            <th>מקור</th>
            <th>ת. הפקה</th>
            <th>הזמנה</th>
             <th>ת.מימוש</th>
            <th>שווי</th>
            <th>סכום מימוש</th>
            <th>עמלה</th>
            <th>זכאות</th>
            <th width="80">סטטוס</th>
            <th>תוקף</th>
            <?php if($_GET['btns']) { ?><th>פעולות<?php } ?>
        </tr>
    </thead>
    <tbody>
<?php
        $sql_sum = "select pID,sum(useageSum) as totalUsage from giftCardsUsage left join gifts_purchases using (pID) left join giftCards on (gifts_purchases.giftCardID = giftCards.giftCardID) where giftCards.siteID in (".$siteID.")  group by pID";
        $sums = udb::key_row($sql_sum,"pID");
        //print_r($sums);
        $sql = "select siteID,giftCardCommission from sites where siteID in(".$siteID.")";
        $coms = udb::key_row($sql,"siteID");
        $where = [];
        $where[] = " and gifts_purchases.terminal = 'company' ";
        if($_GET['free']){
            $isID = '0';
            if (is_numeric($_GET['free']))
                $isID = 'gifts_purchases.pID = ' . intval($_GET['free']);

            $where[] = " (" . $isID . " OR gifts_purchases.`giftTitle` like '%".inDb($_GET['free'])."%' or gifts_purchases.giftSender like '%".inDb($_GET['free'])."%' or gifts_purchases.famname like '%".inDb($_GET['free'])."%')" ;
        }

        /**** this piece of code is too complicated and WRONG (meaning it throws warnings in Mysql and DOESN'T return correct results) ***/

//        if($_GET['createDate']) {
//            if($_GET['createDateTo']) {
//                $useDate = implode('-',array_reverse(explode('/',trim($_GET['createDate']))));
//                $useDate2 = implode('-',array_reverse(explode('/',trim($_GET['createDateTo']))));
//                $useDate  =  date("Y-m-d",strtotime($useDate));
//                $useDate2  =  date("Y-m-d",strtotime($useDate2));
//                $where[] = " gifts_purchases.transDate BETWEEN  STR_TO_DATE('".$useDate."','%Y-%m-%d') AND STR_TO_DATE('".$useDate2."','%Y-%m-%d')";
//            }
//            else {
//                $useDate = implode('-',array_reverse(explode('/',trim($_GET['createDate']))));
//                $where[] = " STR_TO_DATE(gifts_purchases.transDate,'%Y-%m-%d') >= '".$useDate."'";
//            }
//
//        }
//        else {
//            if($_GET['createDateTo']) {
//                $useDate  =  implode('-',array_reverse(explode('/',trim($_GET['createDateTo']))));
//                $where[] = " STR_TO_DATE(gifts_purchases.transDate,'%Y-%m-%d') <= '".$useDate."'";
//            }
//        }
//
//        if($_GET['usingDate']) {
//            if($_GET['usingDateTo']) {
//                $useDate = implode('-',array_reverse(explode('/',trim($_GET['usingDate']))));
//                $useDate2 = implode('-',array_reverse(explode('/',trim($_GET['usingDateTo']))));
//
//                $where[] = " giftCardsUsage.usageDate BETWEEN  STR_TO_DATE('".$useDate."','%Y-%m-%d') AND STR_TO_DATE('".$useDate2."','%Y-%m-%d')";
//            }
//            else {
//                $useDate = implode('-',array_reverse(explode('/',trim($_GET['usingDate']))));
//                $where[] = " STR_TO_DATE(giftCardsUsage.usageDate,'%Y-%m-%d') >= '".$useDate."'";
//            }
//
//        }
//        else {
//            if($_GET['usingDateTo']) {
//                $useDate  =  implode('-',array_reverse(explode('/',trim($_GET['usingDateTo']))));
//                $where[] = " STR_TO_DATE(giftCardsUsage.usageDate,'%Y-%m-%d') <= '".$useDate."'";
//            }
//        }

        /**** this is a CORRECT way to deal with date ranges ***/

// there are numerous implementations of this function across all of our sites. Use any of them.
// also, you SHOULDN'T work or transfer dates in dd/mm/yyyy format. Always use YYYY-MM-DD whenever possible.
function date2U($date){
    $d = array_map('intval', explode('/', $date));
    return (count($d) == 3 && checkdate($d[1], $d[0], $d[2])) ? implode('-', [$d[2], str_pad($d[1], 2, '0', STR_PAD_LEFT), str_pad($d[0], 2, '0', STR_PAD_LEFT)]) : null;
}

        if($tmp = date2U($_GET['createDate']))
            $where[] = " gifts_purchases.transDate >= '" . $tmp . " 00:00:00'";
        if($tmp = date2U($_GET['createDateTo']))
            $where[] = " gifts_purchases.transDate <= '" . $tmp . " 23:59:59'";

        if($tmp = date2U($_GET['usingDate']))
            $where[] = " giftCardsUsage.usageDate >= '" . $tmp . " 00:00:00'";
        if($tmp = date2U($_GET['usingDateTo']))
            $where[] = " giftCardsUsage.usageDate <= '" . $tmp . " 23:59:59'";

        /********************************************************/



        if(intval($_GET['selectedSite'])) {
            $where[] = " giftCards.siteID=" . intval($_GET['selectedSite']) ." ";
        }
//        $sql = "select giftCardsUsage.*,`gifts_purchases`.ordersID,`gifts_purchases`.extendExpiry,`gifts_purchases`.pID as gppID,`gifts_purchases`.transID,
//                                `gifts_purchases`.reciveTime,`gifts_purchases`.transDate,`gifts_purchases`.sum as `paidSum`,giftCards.*,sites.siteName from giftCardsUsage
//                                    left join gifts_purchases on (gifts_purchases.pID = giftCardsUsage.pID)
//                                    left join giftCards on (giftCards.giftCardID = gifts_purchases.giftCardID)
//                                    left join sites on (sites.siteID = giftCards.siteID) where giftCards.siteID in (".$_CURRENT_USER->sites(true).") and gifts_purchases.paid=1
//                                    " . ($where ?  implode(" and ",$where) : "") . "
//                                    order by gifts_purchases.pID DESC";

        $sqlNew = "SELECT gifts_purchases.*,`giftCardsUsage`.`giftCardID`,`giftCardsUsage`.`usageDate`,`giftCardsUsage`.`useageSum`,`giftCardsUsage`.`comments`,`giftCardsUsage`.`commission`,giftCards.daysValid,giftCards.title 
					FROM gifts_purchases 
					LEFT join giftCardsUsage on (giftCardsUsage.pID = gifts_purchases.pID) 
					LEFT join giftCards on (giftCards.giftCardID = gifts_purchases.giftCardID) 
					LEFT join sites on (sites.siteID = giftCards.siteID) 
					WHERE gifts_purchases.paid=1 AND giftCards.siteID in (".$siteID.")  " . ($where ?  implode(" and ",$where) : "") . " 
					ORDER by gifts_purchases.pID DESC";

        $list = udb::full_list($sqlNew);
        $displayed = [];
        foreach($list as $item) {
			$commrate = ($item['commission']) ?: $coms[$item['siteID']]['giftCardCommission'];
            $commission = (($commrate / 100)  * $item['useageSum']);
			$usageType = (!$item['useageSum']? 1 : ($item['sum'] - $item['useageSum']>0? 2 : 3));
            $displayed[] = $item['pID'];
            
			if($_GET['type']) {
                if($_GET['type'] == 1) {
                    if(!$item['useageSum'] || $item['sum'] == $item['useageSum']){
                        continue;
                    }
                }
                if($_GET['type'] == 2) {
                    if( $item['sum'] != $item['useageSum']){
                        continue;
                    }
                }
            }
            ?>
        <tr onclick="showPOP(<?=$item['pID']?>)" class="usageType<?=$usageType?>">
            <td style="width:130px;"><?=$item['ordersID']?></td>
            <td style="text-align:right;width: 170px"><?=($item['giftTitle'] ?: $item['title'])?></td>
            <td style="width:120px;">Vouchers.co.il</td>
            <td style="width:130px;"><?=($item['siteName'] . ($item['bussinessName'] ? ' (' . $item['bussinessName'] . ')' : ''))?></td>
            <td><?=date("d/m/Y",strtotime($item['transDate']))?></td>            
            <td><?=$item['usageDate'] ? date("d/m/Y",strtotime($item['usageDate'])) : ''?></td>
            <td width="100"><?=$item['sum']?></td>            
            <td width="100">₪<?=number_format($item['useageSum'])?></td>
			<td width="100"><?=$item['useageSum']? $commrate . '%' : ""?></td>
            <td width="100"><?=($commission) ? "₪" . $commission   : ""?></td>
            <td width="100" style="text-align:right;"><?
                if(!$item['useageSum']) {
                    echo 'הונפק';
                }
                else {
                    echo ($item['sum'] - $item['useageSum'] > 0) ? 'מומש חלקית' : 'מומש';
                }
                ?></td><?
                    $useExpirationDate = date("d/m/Y", strtotime(" +".($item['validMonths'] ?: $item['daysValid'])." months", strtotime($item['reciveTime'] ? $item['reciveTime'] : date("Y-m-d"))));
                    if($item['extendExpiry']) {
                        $useExpirationDate = date("d/m/Y",strtotime($item['extendExpiry']));
                    }
                ?>
            <td style="width:170px;">בתוקף עד <?=$useExpirationDate?></td>
            <?php if($_GET['btns']) { ?>
                <td style="width:290px;"> 
                    <div class="pay invoice" onclick="<?=$click?>"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"/><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"/></svg><div>חשבונית</div></div>
                    <div class="pay invoice done" onclick="<?=$click?>"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"/><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"/></svg><div>חשבונית</div></div>
                    <div class="pay refund" onclick="deletePayment(this, 'האם אתה בטוח רוצה לבטל וליצור חשבונית מס זיכוי?', function(){window.location.reload();})"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div><?=($picon == 'cancel' ? 'ביטול' : 'זיכוי')?></div></div>
                    <div class="pay refund cancel" onclick="deletePayment(this, 'האם אתה בטוח רוצה לבטל וליצור חשבונית מס זיכוי?', function(){window.location.reload();})"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div>ביטול</div></div>
                    </td>
            <?php } ?>
        </tr>
        <?php 
		$totals['total_actions']++;
		$totals['useageSum']+=$item['useageSum'];
		$totals['commission']+=$commission;
		$totalsU[$usageType]++;
			
			
			} ?>
		<tr>
			<th><?=$totals['total_actions']?> <span>רשומות</span></th>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
			<th>₪<?=number_format($totals['useageSum'],2)?></th>
			<th></th>
			<th>₪<?=number_format($totals['commission'],2)?></th>
			<th></th>
			<th class="usageTypes">
				<div class="usageType1"><?=$totalsU[1]?: "0"?> - <span>הנפקות</span></div>
				<div class="usageType3"><?=$totalsU[3]?: "0"?> - <span>מימושים מלאים</span></div>
				<div class="usageType2"><?=$totalsU[2]?: "0"?> - <span>מימושים חלקיים</span></div>
			</th>
		</tr>
    </tbody>
</table>

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

<?
    //tbl: giftCardsSetting
    //@@fields: giftCardsSettingID, title, backgroundImage, logo, siteDescription, smallLetters, siteID, addManager, updateManager, addDate, updateDate
    $globalGiftData = [];
    $globalGiftSitesData = [];
    $sql = "select * from giftCardsSetting where siteID in (".$siteID.")";
    $globalGiftSitesData = udb::key_row($sql,"siteID");
    if(intval($_GET['siteID'])) {
        if($globalGiftSitesData[intval($_GET['siteID'])]) {
            $globalGiftData = $globalGiftSitesData[intval($_GET['siteID'])];
        }
    }
    if(!$globalGiftSitesData) {
        $globalGiftData['title'] = $siteName;
    }
    else {
        foreach ($globalGiftData as $item) {
            $globalGiftData = $item;
            break;
        }
    }
    $disabled = array(' disabled="disabled" ','style="display:none"' ,' readonly ');
    if(isset($_SESSION['user_id']) || intval($_SESSION['user_id'])) {
        $disabled[0] = "";
        $disabled[1] = "";
        $disabled[2] = "";
    }
?>


<script>
function deleteUsage(use, pid, code, text){
    swal.fire({icon:'question', title:'האם את/ה בטוח/ה שרוצה לבטל את המימוש?', text:text, showDenyButton:true, confirmButtonText:'כן', denyButtonText:'לא'}).then((result) => {
        if (result.isConfirmed) {
            $.post('ajax_giftcards.php', {act:'deleteUsage', pid:pid, uid:use, code:code}, function(res){
                if (!res || res.status === undefined || parseInt(res.status))
                    return swal.fire({icon:'error', title:res.error || res._txt || 'Unknown error'});
                window.location.href = window.location.href + '#' + code;
            });
        }
    });
}

function closeShovarPop(){
    $('.giftcard.gift-pop.gift').fadeOut('fast');
    window.location.hash = '';
}

$(function(){
    if (window.location.hash)
        searchShovar(window.location.hash.substr(1));
});
</script>
