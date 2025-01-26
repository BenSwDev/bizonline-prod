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
<?
$sql_sum = "select pID,giftCards.siteID,sites.siteName,sum(useageSum) as totalUsage from giftCardsUsage left join gifts_purchases using (pID) left join giftCards on (gifts_purchases.giftCardID = giftCards.giftCardID) left join sites on (giftCards.siteID = sites.siteID) where gifts_purchases.terminal = 'company' group by pID";
$sums = udb::key_row($sql_sum,"pID");
?>
<div class="manageItems" id="manageItems">
    <h1>מימוש שוברים</h1>
    <script src="/user/assets/js/giftcards.js?time=<?=time()?>"></script>

    <div class="searchOrder">
        <div class="ttl">חפש פעולות</div>
        <form method="GET" autocomplete="off" action="">
            <div class="inputWrap">
                <select  name="selectedSite" >
                    <option value="">בחר עסק</option>
                    <?
                    $displyedSites = [];
                    foreach ($sums as $item) {
                        if(in_array($item['siteID'],$displyedSites) === false) {
                            $displyedSites[] = $item['siteID'];
                            $selected = "";
                            if(intval($item['siteID']) == intval($_GET['selectedSite'])) $selected = " selected ";
                            echo '<option value="'.$item['siteID'].'" '.$selected.'>'.$item['siteName'].'</option>';
                        }
                    }
                    ?>

                </select>
            </div><?
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
    $sql = "select siteID,giftCardCommission from sites ";
    $coms = udb::key_row($sql,"siteID");
    //print_r($sums);
    $sql = "select siteID,giftCardCommission from sites ";
    $coms = udb::key_row($sql,"siteID");
    $where = [];
    $where[] = "  and gifts_purchases.terminal = 'company' ";
    if($_GET['free']){
        $isID = '0';
        if (is_numeric($_GET['free']))
            $isID = 'gifts_purchases.pID = ' . intval($_GET['free']);

        $where[] = " (" . $isID . " OR gifts_purchases.`giftTitle` like '%".inDb($_GET['free'])."%' or gifts_purchases.giftSender like '%".inDb($_GET['free'])."%' or gifts_purchases.famname like '%".inDb($_GET['free'])."%')" ;
    }

//    if($_GET['createDate']) {
//        if($_GET['createDateTo']) {
//            $useDate = implode('-',array_reverse(explode('/',trim($_GET['createDate']))));
//            $useDate2 = implode('-',array_reverse(explode('/',trim($_GET['createDateTo']))));
//            $useDate  =  date("Y-m-d",strtotime($useDate));
//            $useDate2  =  date("Y-m-d",strtotime($useDate2));
//            $where[] = " gifts_purchases.transDate BETWEEN  STR_TO_DATE('".$useDate."','%Y-%m-%d') AND STR_TO_DATE('".$useDate2."','%Y-%m-%d')";
//        }
//        else {
//            $useDate = implode('-',array_reverse(explode('/',trim($_GET['createDate']))));
//            $where[] = " STR_TO_DATE(gifts_purchases.transDate,'%Y-%m-%d') >= '".$useDate."'";
//        }
//
//    }
//    else {
//        if($_GET['createDateTo']) {
//            $useDate  =  implode('-',array_reverse(explode('/',trim($_GET['createDateTo']))));
//            $where[] = " STR_TO_DATE(gifts_purchases.transDate,'%Y-%m-%d') <= '".$useDate."'";
//        }
//    }
//
//    if($_GET['usingDate']) {
//        if($_GET['usingDateTo']) {
//            $useDate = implode('-',array_reverse(explode('/',trim($_GET['usingDate']))));
//            $useDate2 = implode('-',array_reverse(explode('/',trim($_GET['usingDateTo']))));
//
//            $where[] = " giftCardsUsage.usageDate BETWEEN  STR_TO_DATE('".$useDate."','%Y-%m-%d') AND STR_TO_DATE('".$useDate2."','%Y-%m-%d')";
//        }
//        else {
//            $useDate = implode('-',array_reverse(explode('/',trim($_GET['usingDate']))));
//            $where[] = " STR_TO_DATE(giftCardsUsage.usageDate,'%Y-%m-%d') >= '".$useDate."'";
//        }
//
//    }
//    else {
//        if($_GET['usingDateTo']) {
//            $useDate  =  implode('-',array_reverse(explode('/',trim($_GET['usingDateTo']))));
//            $where[] = " STR_TO_DATE(giftCardsUsage.usageDate,'%Y-%m-%d') <= '".$useDate."'";
//        }
//    }

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


    if(intval($_GET['selectedSite'])) {
        $where[] = " giftCards.siteID=" . intval($_GET['selectedSite']) ." ";
    }

    $sqlNew = "select gifts_purchases.*,`giftCardsUsage`.`giftCardID`,`giftCardsUsage`.`useageSum`,`giftCardsUsage`.usageDate,`giftCardsUsage`.`comments`,`giftCardsUsage`.`commission`,giftCards.daysValid,giftCards.title,sites.siteName 
	from gifts_purchases 
	left join giftCardsUsage on (giftCardsUsage.pID = gifts_purchases.pID) 
	left join giftCards on (giftCards.giftCardID = gifts_purchases.giftCardID) 
	left join sites on (sites.siteID = giftCards.siteID) 
	where  gifts_purchases.paid=1 " . ($where ?  implode(" and ",$where) : "") . "  
	order by gifts_purchases.pID DESC ";
    ?>
	<style>
	.giftcards-log thead th{position:sticky;z-index:1;background:white;top:0;box-shadow:0 -1px 1px #333 inset;line-height:1;height:30px}
	.giftcards-log tbody th{position:sticky;z-index:1;background:white;bottom:0;font-weight:bold;height:50px;vertical-align:middle;box-shadow:0 1px 1px #333 inset;line-height:1}
	.usageType1 td, .usageType1{color:#00af00}
	.usageType2 td, .usageType2{color:#028993}
	.usageType3 td, .usageType3{color:#00007c}
	.usageTypes{line-height:1;font-size:12px}
	.usageTypes div{margin-top:3px}

	</style>
	
	<div class="excel" onclick="export_xl('#exp_xl')">ייצוא לאקסל</div>
    <table class="giftcards-log" cellspacing="0" id="exp_xl" style="overflow:auto">
    <thead>
        <tr>
            <th style="width:130px;">מספר</th>
            <th style="width:170px;">שם הכרטיס</th>
            <th style="width:130px;">מקור</th>
            <th>עסק</th>
            <th>ת. הפקה</th>
            <th>הזמנה</th>
            <th>ת.מימוש</th>
            <th width="100">שווי</th>
            <th width="100">מימוש</th>
            <th width="100">עמלה</th>
            <th width="100">זכאות</th>
            <th width="100">יתרה</th>
            <th width="100">סטטוס</th>
            <th width="250">תוקף</th>
        </tr>
    </thead>
    <tbody>
        <?php
		$showed = array();
        $list = udb::full_list($sqlNew);
        foreach($list as $item) {
            //$commission = ($coms[$item['siteID']]['giftCardCommission'] / 100)  * $item['useageSum'];
            $commrate = ($item['commission']) ?: $coms[$item['siteID']]['giftCardCommission'];
            $commission = (($commrate / 100)  * $item['useageSum']);
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
			if(in_array($item['pID'],$showed)){

			}
			$showed[]=$item['pID'];
			$usageType = (!$item['useageSum']? 1 : ($item['sum'] - $item['useageSum']>0? 2 : 3));
            ?>
            <tr onclick="showPOP(<?=$item['pID']?>)" class="usageType<?=$usageType?>">
            <td style="width:130px;"><?=$item['ordersID']?></td>
            <td style="text-align:right;width: 170px"><?=($item['giftTitle'] ?: $item['title'])?></td>
            <td style="width:120px;">Vouchers.co.il</td>
            <td style="width:130px;"><?=($item['siteName'] . ($item['bussinessName'] ? ' (' . $item['bussinessName'] . ')' : ''))?></td>
            <td><?=date("d/m/Y",strtotime($item['transDate']))?></td>
            <td>#<?=$item['pID']?></td>
            <td><?=$item['usageDate'] ? date("d/m/Y",strtotime($item['usageDate'])) : ''?></td>
            <td width="100"><?=$item['sum']?></td>            
            <td width="100">₪<?=number_format($item['useageSum'])?></td>
			<td width="100"><?=$item['useageSum']? $commrate . '%' : ""?></td>
            <td width="100"><?=($commission) ? "₪" . $commission   : ""?></td>
            <td width="100"><?=isset($sums[$item['pID']]) ? $item['sum'] - $item['useageSum'] : $item['sum']?></td>
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
        </tr>
        <?php 
		$totals['total_actions']++;
		$totals['useageSum']+=$item['useageSum'];
		$totals['commission']+=$commission;
		$totalsU[$usageType]++;
		
				
		} ?>
		<tr>
			<th><?=$totals['total_actions']?> רשומות</th>
			<th></th>
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
				<div class="usageType1"><?=$totalsU[1]?: "0"?> - הנפקות</div>
				<div class="usageType3"><?=$totalsU[3]?: "0"?> - מימושים מלאים</div>
				<div class="usageType2"><?=$totalsU[2]?: "0"?> - מימושים חלקיים</div>
			</th>
			<th></th>
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
?>
