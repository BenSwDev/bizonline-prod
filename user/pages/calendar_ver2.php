<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if ($_CURRENT_USER->select_site())
    echo '<script>$(function(){$("a[data-all-site=\'' . $_CURRENT_USER->select_site() . '\']").click();});</script>';

$monthView = $_GET['monthView'];

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

	if(!$_GET['date'] || date("m/Y") == date("m/Y",$_GET['date'])){
		$date = date("Y/m/d");

	}else{
		if(typemap(implode('-',array_reverse(explode('/',trim($_GET['date'])))),"date")){
			$date = implode('/',array_reverse(explode('/',trim($_GET['date']))));
		}else{
			//echo "תאריך שגוי";
			$date = date("Y/m/d");
		}
	}
	$dateMonth =  substr($date,0,-2)."01";
	//echo $dateMonth;

	$endMonthDay = date("t",date(strtotime($date)));
	$curMonthDate = date("m",strtotime($date));
	$curYearDate = date("Y",strtotime($date));

	$dayNameShort = array ("א","ב","ג","ד","ה","ו","ש");
	$dayNameMonth = array ("א","ב","ג","ד","ה","שישי","שבת");
	$dayName = array ("ראשון","שני","שלישי","רביעי","חמישי","שישי","שבת");
	$monthNames = array("ינואר", "פברואר", "מרץ", "אפריל", "מאי", "יוני","יולי", "אוגוסט", "ספטמבר", "אוקטובר", "נובמבר", "דצמבר");

	$monthStart = date('Y-m-01',strtotime($date));
	$monthEnd = date('Y-m-t',strtotime($date));
	if($monthView){
		$firstDay = date('Y-m-d',  strtotime($monthStart." -".date('w',strtotime($monthStart))." day"));
		$lastDay = date('Y-m-d',  strtotime($monthEnd." +".(6 - date('w',strtotime($monthEnd)))." day"));

	}else{

		$firstDay = $monthStart;
		$lastDay = $monthEnd;
	}
	$insertDay = $firstDay;
	$key = 1;
	$showDays = array();
	while(strtotime($insertDay)<=strtotime($lastDay)){
		$showDays[$key] = $insertDay;
		$key++;
		$insertDay = date('Y-m-d', strtotime($insertDay. "+1 day"));
	}



	$que = "SELECT `orders`.*, `orderUnits`.`unitID`
	FROM `orders` 
	INNER JOIN `orderUnits` USING(`orderID`)
	WHERE siteID IN (" . $_CURRENT_USER->sites(true) . ") AND `orders`.`status`=1
	    AND timeFrom <= '".$lastDay." 23:59:59' AND timeUntil >= '".$firstDay." 00:00:00'
	";

	$monthOrders = udb::full_list($que);
	//print_r($monthOrders);

    $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");

//    $que = "(SELECT `dateStart`, `dateEnd`, `holidayName` FROM `holidays` WHERE '" . $monthStart . "' <= `dateEnd` AND '" . $monthEnd . "' >= `dateStart` AND `active` = 1)
//            UNION
//            (SELECT `dateStart`, `dateEnd`, `notHolidayName` FROM `not_holidays` WHERE '" . $monthStart . "' <= `dateEnd` AND '" . $monthEnd . "' >= `dateStart` AND `active` = 1)
//            ORDER BY `dateStart`";
    $que = "SELECT `dateStart`, `dateEnd`, `notHolidayName` AS `holidayName` FROM `not_holidays` WHERE '" . $firstDay . "' <= `dateEnd` AND '" . $lastDay . "' >= `dateStart` AND `active` = 1 ORDER BY `dateStart`";
    $holidays = udb::single_list($que);

//        $que = "SELECT `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`, rooms.active, rooms.siteID, rooms.roomID
//            FROM `rooms_units`
//            INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
//            WHERE `rooms`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ") AND rooms.active = 1
//            ORDER BY rooms.showOrder";
//        $crooms = udb::key_list($que, 'siteID');
//        ksort($crooms, SORT_NUMERIC);



    $que = "SELECT `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`, rooms_domains.active, rooms.siteID, rooms.roomID
                FROM `rooms_units`
                INNER JOIN `rooms_domains` ON (`rooms_domains`.`roomID` = `rooms_units`.`roomID` and rooms_domains.domainID=1)  
                INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
                WHERE `rooms`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ") AND  rooms_domains.active=1
                ORDER BY `rooms`.`siteID` ASC,rooms.showOrder ASC";
    $crooms = udb::key_list($que, 'siteID');
    //ksort($crooms, SORT_NUMERIC);


	//print_r($crooms);
?>
<link rel="stylesheet" href="<?=$_CURRENT_BASE?>assets/css/tfusa_ver2.css?v=<?=time()?>">
<div class="popup unitSelect" id="unitSelect" >
    <div class="popup_container">
        <div class="close" onclick="$('#unitSelect').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
		<div class="title">בחרו יחידה לתצוגה</div>
		<div class="all-units">
			<?if(count($crooms)>1){?>
			<a style="border-color:#333" onclick="showunits('all',Array(0),$(this).html())">כל היחידות</a>
			<div style="padding-top:10px;margin-bottom:10px;border-bottom:1px #333 solid"></div>
			<?}
			$roomNum=0;
			foreach($crooms as $siteID => $rlist){
				$siteRooms = array();
				foreach($rlist as $room){
					$siteRooms[]=$room['unitID'];
					$roomType[$room["roomID"]][] = $room["unitID"];
				}?>

					<a style="margin-top:20px;border-color:#333" data-all-site="<?=$siteID?>" onclick="showunits('units',[<?=implode(",",$siteRooms)?>],$(this).html())">כל היחידות - <?=$sname[$siteID]?></a>
					<div class="units">
				<?
				$roomIDs = 0;
				foreach($rlist as $room) {
					if(count($roomType[$room["roomID"]]) > 1){
						if($roomIDs ==0){
						?>
						<a class="roomNameTitle" style="border-color:#0dabb6;margin-top:10px" onclick="showunits('units',[<?=implode(",",$roomType[$room["roomID"]])?>],$(this).html())"><?=$room['unitName']?> <span> - <?=count($roomType[$room["roomID"]])?> יחידות</span></a>
						<div class="roomIDs">
						<?
						}$roomIDs ++;
					}else{$roomIDs =0;}

					$roomNum++;
					$roomNumber[$room['unitID']] = $roomNum;
					?>
					<a onclick="showunits('unit',<?=$room['unitID']?>,$(this).html())"><i><?=$roomNum?></i><?=$room['unitName']?></a>
					<?if(count($roomType[$room["roomID"]]) > 1 && $roomIDs ==count($roomType[$room["roomID"]])){
						$roomIDs = 0;?>
						</div>
					<?}?>
				<?}?>
				</div>
			<?}?>
		</div>
	</div>
</div>
<section id="n_tfusa" class="tfusa <?=$monthView? "month" : ""?>">
	<div class='calendar-top cright'>
		<?if(!$monthView){?>
		<div class="settings" id="setview" style="display:">
			<div class="view-set" onclick="toggleFlip()">סובב תצוגה</div>
			<div class="time-set">
				<div onclick="$('.tfusa').removeClass('wide');set_session('wide',0)">רגיל</div>
				<div id="wideview" onclick="$('.tfusa').addClass('wide');set_session('wide',1)">רחב</div>
			</div>
		</div>
		<?}?>
		<div class="top-buttons">
			<a href="?page=calendar_ver2&date=<?=date("d/m/Y",strtotime("-1 month",strtotime($dateMonth)))?>&monthView=<?=$monthView?>" class="prev">חודש קודם</a>
			<div class="month-select">
				<select id="monthselect" onchange="newload()">
					<?

					for($i= -12;$i<=24;$i++){?>
					<option value="<?=$i!=0? date('d/m/Y', strtotime($i." months", strtotime($monthStart))): date('d/m/Y');?>" <?=$i==0? "selected" : ""?>>
						<?=date('m - Y', strtotime($i." months", strtotime($monthStart)));?>
					</option>
				<?}?>
				</select>
				<?=$monthNames[date('n', strTotime($date))-1]?><br>
				<?=date('m - Y', strTotime($date))?>
			</div>
			<a href="?page=calendar_ver2&date=<?=date("d/m/Y",strtotime("+1 month",strtotime($dateMonth)))?>&monthView=<?=$monthView?>" class="next">חודש הבא</a>
		</div>
	</div>
	<div class='calendar-top cleft'>

		<select id="monthview" onchange="newload()">
			<option value="0" <?=$monthView? "" : "selected"?>>תצוגת רשימה</option>
			<option value="1" <?=$monthView? "selected" : ""?>>תצוגה חודשית</option>
			<option value="2" >תצוגה יומית</option>
		</select>
		<div id="showSelect" onclick="$('#unitSelect').fadeIn('fast')">כל היחידות</div>
	</div>
	<div class="days-table" id="days-table">
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
			<div class="days" id="days">

<?php
		if($monthView){
			$wday = 0;
			foreach($dayNameMonth as $day){ $wday++;?>
				<div class="day <?=$wday>5? "weekend" : ""?>">
					<a class="top">
					<div class="row">
						<span class="day-name"><?=$day?></span>
					</div>
					</a>
				</div>
			<?}

		}else{?>
				<div class="day last-month">
					<div class="top">
						<div class="row">
							<div class="title">הזמנות מחודש קודם</div>
						</div>
					</div>
				</div>
		<?
		for($i=1; $i<=count($showDays); $i++) {
			$today = date('d/m/Y',strtotime($showDays[$i]));
			$todayRev = date('Y-m-d',strtotime($showDays[$i]));?>
				<div class="day <?=($today==date("d/m/Y"))?"today":""?> <?=(date("w",strtotime($todayRev))>4)?"weekend":""?>" data-date="<?=$today?>">
					<a href="index.php?page=calendar2_ver2&date=<?=date("d/m/Y",strtotime($todayRev))?>" class="top">
						<div class="row">
							<div class="special_date" title="<?=strip_tags(findHoliday($todayRev))?>"><?=findHoliday($todayRev)?></div>
							<span class="day-name"><?=$dayName[date('w', strTotime($todayRev))]?></span>
							<span class="day-date" ><?=date("d/m/Y",strtotime($todayRev))?></span>
						</div>
					</a>

				</div>
			<?}
		}?>
		</div>
<?php
		if($monthView){?>
		<div class="days">
		<?

		}else{?>

			<div class="day last-month">
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
<?php	}


        for($i=1; $i<=count($showDays); $i++) {
			$today = date('d/m/Y',strtotime($showDays[$i]));
			$todayRev = date('Y-m-d',strtotime($showDays[$i]));
?>

			<div class="day <?=($today==date("d/m/Y"))?"today":""?> <?=(date("w",strtotime($todayRev))>4)?"weekend":""?>" data-date="<?=$today?>">
				<?if($monthView){?><div class="daydate"><div><?=explode("-",$showDays[$i])[2]?></div><span><?=findHoliday($todayRev)?></span></div><?}?>
				<div class="rooms">
<?php
            foreach($crooms as $siteID => $rlist){
                foreach($rlist as $room) {
?>
					<div class="row<?=($room['active'] ? '' : ' inactive')?>" data-date="<?=$today?>" data-name="<?=outDb($room['unitName'])?>" data-uid="<?=$room['unitID']?>" data-num="<?=$roomNumber[$room['unitID']]?>">

					</div>
<?php
                }
            }

?>
				</div>
			</div>
<?php
			if($monthView && ($startDay+$i)%7 < 1){?>
			</div>
			<div class="days">

			<?}
        }
		if($monthView){?>
			</div>

		<?}?>

		</div>
	</div>
</section>
<style>


</style>



<script src="assets/js/tfusa2.js?v=<?=time()?>"></script>
<script type="text/javascript">
var domain = new Array();
<?php
foreach($domain_icon as $key => $icon){?>
domain[<?=$key?>] = '<?=$icon?>';
<?}?>

$('#days-table').scroll(function(){
	$('#days').css("top",this.scrollTop + "px");
	$('#days').css("right",Math.abs(this.scrollLeft) + "px");
	$('#days-table .r-side').css("top",this.scrollTop + "px");
	$('#days-table .r-side .top').css("top",this.scrollTop + "px");
});

		var currentMonth = '<?=date("m/Y",strtotime($date))?>';
		var endMonthDay = '<?=$endMonthDay?>';
		var passMonthYear = '<?=date("t/m/Y",strtotime("first day of last month",strtotime($date)))?>';

		var monthview = <?=$monthView? "1" :"0"?>;

		<?php

			$scrollmonth = (date("m",strtotime($date)) == date("m"))? date("d",strtotime($date)) : 0;
		?>

		var scrollmonth = <?=$scrollmonth?>;

    $(function(){
<?php
    if($monthOrders){
		if($monthView){
			foreach($monthOrders  as $k => $m){
				$i = 1;
				while($i < count($showDays)){


					if(strtotime($m['timeFrom']) < strtotime($showDays[$i+6]) && strtotime($m['timeUntil']) >= strtotime($showDays[$i+6])){
						//$m['timeUntil'] = date("Y-m-d 23:59:59",strtotime($showDays[$i+6]));
						//if(!$monthOrders[$k]['endDate']) $monthOrders[$k]['endDate'] = $m['timeUntil'];
						//echo "console.log('".$m['orderID']." ".$m['timeUntil']."');";
					}
					if(strtotime($m['timeFrom'])<strtotime($showDays[$i]) && strtotime($m['timeUntil']) >= strtotime($showDays[$i]) && $m['allDay']!=1){
						$m['timeFrom'] = date("Y-m-d 00:00:00",strtotime($showDays[$i]));
						$monthOrders[] = $m;
					}
					$i+=7;

				}

			}
		}
        foreach($monthOrders as $m){
            $month = array_map('quot', $m);
			foreach($crooms[$month['siteID']] as $unit){
				if($unit["unitID"] == $month['unitID']) $roomName = $unit['unitName'];
			}
?>
			orderFormMonth = (new addMonth<?=$monthView? "View" : ""?>Order({
            orderID:<?=$month['orderID']?>,
            orderType:"<?=$month['orderType']?>",
            orderDate:"<?=implode('/',array_reverse(explode('-',substr($month['timeFrom'],0,10))))?>",
            endDate:"<?=implode('/',array_reverse(explode('-',substr($month['timeUntil'],0,10))))?>",
            startTime:"<?=substr($month['timeFrom'],11,5)?>",
            endTime:"<?=substr(($month['endDate']?: $month['timeUntil']),11,5)?>",
            roomID:<?=$month['unitID']?>,
            roomName:"<?=str_replace('"','\"',$roomName)?>",
            roomNum:"<?=$roomNumber[$month['unitID']]?>",
            name:"<?=$month['customerName']?>",
            phone:"<?=$month['customerPhone']?>",
            price:"<?=$month['price']?>",
            allDay:<?=$month['allDay']?>,
            approved:<?=($month['approved'] | $month['adminApproved'])?>,
            orderIDBySite:<?=$month['orderIDBySite']?>,
            domainIcon:<?=$month['domainID']?>,
            guid:"<?=$month['guid']?$month['guid']:0?>",
            showOrders: true
        })).init();
<?php
        }
    }
?>
	setclientview();
	setTimeout(function(){scrollTableNew();},400);

    });

	function newload(){
		var page;
		if($('#monthview').val() ==2){
			page = "calendar2_ver2";
		}else{
			page= "calendar_ver2";
		}
		window.location='index.php?page=' + page + '&monthView='+ $('#monthview').val() +'&date='+ $('#monthselect').val();
	}

	function showunits(type,units,ttl){
		debugger;
		if(type=="all"){
			$('.row').show();
			$('#showSelect').css('font-size','16px')
		}else{
			$('.row').hide();
			$('#days .row').show();
			$('.row.month').show();
			if(type=="units"){
				units.forEach(unit => $('.row[data-uid='+ unit +']').show());
			}else{
				$('.row[data-uid='+ units +']').show();
			}
			$('#showSelect').css('font-size','14px')
		}
		$('#unitSelect').hide();
		$('#showSelect').html(ttl);




	}



	function setclientview(){
		//debugger;
		if($('#setview').length){

			if(parseInt(localStorage.getItem('flipped'))>0){
				toggleFlip();
			}

			if(parseInt(localStorage.getItem('wide'))>0){
				$('.tfusa').addClass('wide');
			}
		}
	}

	function toggleFlip(){
		$('.tfusa').toggleClass('flipped');
		if($('.tfusa').hasClass('flipped')){
			var size2 = 'height';
			var margin2 = 'top';
			var setwidth = true;
			set_session('flipped',1);
		}else{
			var size2 = 'width';
			var margin2 = 'right';
			var setwidth = false;
			set_session('flipped',0);
		}
		$('.order').each(function(){
			//debugger;
			var newsize = $(this).attr("data-size")+"%";
			var newmargin = $(this).attr("data-margin")+"%";
			$(this).attr('style',"");
			$(this).css(size2, newsize);
			$(this).css(margin2, newmargin);
			if(setwidth) $(this).css('width', '100%');
		})
	}

	function set_session(type,tvalue){
		localStorage.setItem(type, tvalue);
	}
</script>
