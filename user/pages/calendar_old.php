<?php
function findHoliday($date){
    global $holidays;

    $data = [];

    foreach($holidays as $day)
        if ($day['dateStart'] <= $date && $day['dateEnd'] >= $date)
            $data[] = '<div>' . $day['holidayName'] . '</div>';

    return implode('', $data);
}

function quot($a){
    return str_replace('"', '\"', $a);
}

	if(!$_GET['date']){
		$date = date("Y/m/d");
		
	}else{
		if(typemap(implode('-',array_reverse(explode('/',trim($_GET['date'])))),"date")){
			$date = implode('/',array_reverse(explode('/',trim($_GET['date']))));
		}else{
			echo "תאריך שגוי";
			exit;
		}
	}

	$endMonthDay = date("t",date(strtotime($date)));
	$curMonthDate = date("m",strtotime($date));
	$curYearDate = date("Y",strtotime($date));

	$dayNameShort = array ("א","ב","ג","ד","ה","ו","ש");	
	$dayName = array ("ראשון","שני","שלישי","רביעי","חמישי","שישי","שבת");	
	$monthNames = array("ינואר", "פברואר", "מרץ", "אפריל", "מאי", "יוני","יולי", "אוגוסט", "ספטמבר", "אוקטובר", "נובמבר", "דצמבר");

	$monthStart = date('Y-m-01',strtotime($date));
	$monthEnd = date('Y-m-t',strtotime($date));
	$que = "SELECT `orders`.*, `orderUnits`.`unitID`
	FROM `orders` 
	INNER JOIN `orderUnits` USING(`orderID`)
	WHERE siteID IN (" . $_CURRENT_USER->sites(true) . ") AND `orders`.`status`=1
	AND ((timeFrom >= '".$monthStart." 00:00:00' AND timeFrom <= '".$monthEnd." 23:59:59') 
	OR (timeFrom > '".$monthStart." 00:00:00' AND timeUntil < '".$monthEnd." 23:59:59')
	OR (timeUntil >= '".$monthStart." 00:00:00' AND timeUntil <= '".$monthEnd." 23:59:59'))
	";	
	$monthOrders = udb::full_list($que);

    $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");

//    $que = "(SELECT `dateStart`, `dateEnd`, `holidayName` FROM `holidays` WHERE '" . $monthStart . "' <= `dateEnd` AND '" . $monthEnd . "' >= `dateStart` AND `active` = 1)
//            UNION
//            (SELECT `dateStart`, `dateEnd`, `notHolidayName` FROM `not_holidays` WHERE '" . $monthStart . "' <= `dateEnd` AND '" . $monthEnd . "' >= `dateStart` AND `active` = 1)
//            ORDER BY `dateStart`";
    $que = "SELECT `dateStart`, `dateEnd`, `notHolidayName` AS `holidayName` FROM `not_holidays` WHERE '" . $monthStart . "' <= `dateEnd` AND '" . $monthEnd . "' >= `dateStart` AND `active` = 1 ORDER BY `dateStart`";
    $holidays = udb::single_list($que);

    $que = "SELECT `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`, rooms.active, rooms.siteID
            FROM `rooms_units`
            INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
            WHERE `rooms`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ") AND rooms.active = 1
            ORDER BY rooms.showOrder";
    $crooms = udb::key_list($que, 'siteID');

    ksort($crooms, SORT_NUMERIC);
?>

<section class="tfusa">
	<div class="title">יומן תפוסה</div>
	<div class="top-buttons">
		<a href="<?=WEBSITE?>user/index.php?page=calendar&date=<?=date("d/m/Y",strtotime("-1 month",strtotime($date)))?>" class="prev">חודש קודם</a>
		<a href="<?=WEBSITE?>user/index.php?page=calendar&date=<?=date("d/m/Y",strtotime("+1 month",strtotime($date)))?>" class="next">חודש הבא</a>
		<div class="clicks">
			<div class="short-click">לחיצה קצרה - סימון פנוי תפוס</div>
			<div class="long-click">לחיצה ארוכה - פתיחת הזמנה</div>
		</div>
	</div>
	<div class="days-table">
		<div class="r-side">
			<div class="top">
				<div class="row month">
					<span class="month-label"><span class="month-name"><?=$monthNames[date('n', strTotime($date))-1]?></span><span class="month-year"><?=$curYearDate?></span></span>
					<div class="buttons">
						<button class="prev">אחורה</button>
						<button class="next">קדימה</button>                                        
					</div>
				</div>
			</div>
			<div class="rooms">
<?php
    foreach($crooms as $siteID => $rlist){
        foreach($rlist as $room) {
?>
				<div class="row<?=($room['active'] ? '' : ' inactive')?>" data-name="<?=outDb($room['unitName'])?>" data-uid="<?=$room['unitID']?>">
					<div class="name"><?=outDb($room['unitName'])?></div>
                    <div class="people adults"><?=$sname[$siteID]?></div>
					<!-- div class="people kids">עד 6 ילדים</div> -->
				</div>
<?php
        }
    }
?>
			</div>
		</div>
		<div class="l-side">
			<div class="day last-month">
				<div class="top">
					<div class="row">
						<div class="title">הזמנות מחודש קודם</div>
					</div>
				</div>
				<div class="rooms">
<?php
    foreach($crooms as $siteID => $rlist){
        foreach($rlist as $room) {
?>
                    <div class="row<?=($room['active'] ? '' : ' inactive')?>" data-date="<?=date("d/m/Y",strtotime("-1 day",strtotime($curYearDate."-".$curMonthDate."-01")))?>" data-name="<?=outDb($room['unitName'])?>" data-uid="<?=$room['unitID']?>"></div>
<?php
        }
    }
?>

				</div>
			</div>
<?php
        for($i=1; $i<=$endMonthDay; $i++) {
			$today = date('d/m/Y',strtotime($curYearDate."-".$curMonthDate."-".$i));
			$todayRev = date('Y-m-d',strtotime($curYearDate."-".$curMonthDate."-".$i))
?>
			
			<div class="day <?=($today==date("d/m/Y"))?"today":""?> <?=(date("w",strtotime($todayRev))>4)?"weekend":""?>" data-date="<?=$today?>">
				<a href="/user/index.php?page=calendar2&date=<?=date("d/m/Y",strtotime($todayRev))?>" class="top">
					<div class="row">
                        <div class="special_date"><?=findHoliday($todayRev)?></div>
						<span class="day-name"><?=$dayName[date('w', strTotime($todayRev))]?></span>						
						<span class="day-date" ><?=date("d/m/Y",strtotime($todayRev))?></span>
					</div>
				</a>
				<div class="rooms">
<?php
            foreach($crooms as $siteID => $rlist){
                foreach($rlist as $room) {
?>
					<div class="row<?=($room['active'] ? '' : ' inactive')?>" data-date="<?=$today?>" data-name="<?=outDb($room['unitName'])?>" data-uid="<?=$room['unitID']?>">
						<?php /*if($i == 100) { ?>
							<div class="order all-day">
								<div class="name">ירון וילנסקי</div>
								<div class="phone">050-4568213</div>
								<div class="bottom">
									<div class="price">₪2200</div>
									<div class="status">מאושר</div>
								</div>
								<a class="whatsapp" href="/wa.me/"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></a>
							</div>
						<?php }*/ ?>
					</div>  
<?php
                }
            }
?>
				</div>
			</div>
<?php
        }
?>
		</div>
	</div>
</section>
<script type="text/javascript">
		var currentMonth = '<?=implode('/',array_reverse(explode('-',date("m/Y",strtotime($date)))))?>';
		var endMonthDay = '<?=$endMonthDay?>';
		var passMonthYear = '<?=implode('/',array_reverse(explode('-',date("t/m/Y",strtotime("-1 month",strtotime($date))))))?>';
		
		var scrollmonth = <?=(date("m",strtotime($date)) == date("m"))? date("d",strtotime($date)) : 0?>

    $(function(){
<?php
    if($monthOrders){
        foreach($monthOrders as $m){
            $month = array_map('quot', $m);
?>
        orderFormMonth = (new addMonthOrder({
            orderID:<?=$month['orderID']?>,
            orderType:"<?=$month['orderType']?>",
            orderDate:"<?=implode('/',array_reverse(explode('-',substr($month['timeFrom'],0,10))))?>",
            endDate:"<?=implode('/',array_reverse(explode('-',substr($month['timeUntil'],0,10))))?>",
            startTime:"<?=substr($month['timeFrom'],11,5)?>",
            endTime:"<?=substr($month['timeUntil'],11,5)?>",
            roomID:<?=$month['unitID']?>,
            name:"<?=$month['customerName']?>",
            phone:"<?=$month['customerPhone']?>",
            price:"<?=$month['price']?>",
            allDay:<?=$month['allDay']?>,
            approved:<?=$month['approved']?>,
            orderIDBySite:<?=$month['orderIDBySite']?>,
            guid:"<?=$month['guid']?$month['guid']:0?>",
            showOrders: true
        })).init();
<?php
        }
    }
?>
    });
</script>
