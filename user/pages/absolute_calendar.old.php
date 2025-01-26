<link href="assets/css/atfusa.css" rel="stylesheet">
<?php

require_once "../user/calendar_functions.php";


$caltype = intval($_GET['type']);
if(!$caltype) $caltype = 2;
if($caltype == 2){
	$caltypeName ='unitID';
}else{
	$caltypeName ='masterID';
}
$viewtype = $_GET['viewtype']? intval($_GET['viewtype']) :  "0";
//print_r($domain_icon);



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

if(!$_GET['date'] || (date("m/Y") == date("m/Y",$_GET['date']) && $viewtype!=2)){
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
	if($viewtype == 1){
		$firstDay = date('Y-m-d',  strtotime($monthStart." -".date('w',strtotime($monthStart))." day"));
		$lastDay = date('Y-m-d',  strtotime($monthEnd." +".(6 - date('w',strtotime($monthEnd)))." day"));
	

	}else if($viewtype == 2){
		$firstDay =  date('Y-m-d',  strtotime($date));
		$lastDay =  date('Y-m-d',  strtotime($date));
	}else{		
		$firstDay = $monthStart;
		$lastDay = $monthEnd;		
	}

	$insertDay = $firstDay;
	$showDays = array();
	if($viewtype == 2){
		for($key=0;$key<24;$key++){
			$showDays[$key] = $key;
		}
	}else{		
		$key = 1;
		while(strtotime($insertDay)<=strtotime($lastDay)){
			$showDays[$key] = $insertDay;
			$key++;
			$insertDay = date('Y-m-d', strtotime($insertDay. "+1 day"));
		}
	}


############################################## PICK FIRST IF NOT ONE
$sname_list = udb::full_list("SELECT siteID,siteName,guid FROM sites WHERE siteID IN (" . $_CURRENT_USER->sites(true) . ")");
	
	$cur_sid = $sname_list[0]['siteID'];
	$cur_guid = $sname_list[0]['guid'];
	$cur_sid_name = $sname_list[0]['siteName'];
	
	if (isset($_GET["siteid"])) {
		foreach ($sname_list as $sname_list_vals) {
			if ($sname_list_vals['siteID'] == $_GET["siteid"]) {
				$cur_sid = $sname_list_vals['siteID'];
				$cur_guid = $sname_list_vals['guid'];
				$cur_sid_name = $sname_list_vals['siteName'];
			}
		}
	} 
	
	echo '<input type="hidden" name="sid" id="sid" value="'.$cur_sid.'">';
	echo '<input type="hidden" name="guid" id="guid" value="'.$cur_guid.'">';
	
	

##############################################

if($caltype ==2){
$que = "SELECT `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`, rooms_domains.active, rooms.siteID, rooms.roomID
			FROM `rooms_units`
			INNER JOIN `rooms_domains` ON (`rooms_domains`.`roomID` = `rooms_units`.`roomID` and rooms_domains.domainID=1)  
			INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
			WHERE `rooms`.`siteID` = '".$cur_sid."' AND  rooms_domains.active=1
			ORDER BY rooms.showOrder";
}else{
	$que = "SELECT 
		therapistID AS masterID
		,siteID
		,active
		,siteName AS masterName 
		FROM therapists WHERE siteID = '".$cur_sid."' ";
}
$crooms = udb::key_list($que, 'siteID');
if($caltype ==2){
	$crooms[0][0]["unitName"] = "ללא חדר";		
}else{
	$crooms[0][0]["masterName"] = "ללא מטפל";
}
$crooms[0][0]["active"] = "1";
ksort($crooms, SORT_NUMERIC);


$que = "SELECT `orders`.*, `orders`.timeFrom AS timeFromCal, `orders`.timeUntil AS timeUntilCal ,`orderUnits`.`unitID`
FROM `orders` 
LEFT JOIN `orderUnits` USING(`orderID`)
WHERE siteID IN (" . $_CURRENT_USER->sites(true) . ") AND `orders`.`status`=1 AND ((`orders`.orderID <> `orders`.parentOrder AND `orders`.parentOrder > 0)".($caltype ==2? " OR `orderUnits`.unitID > 0" : "").")
	AND timeFrom <= '".$lastDay." 23:59:59' AND timeUntil >= '".$firstDay." 00:00:00'
";
//echo $que;
$calendarOrders = udb::full_list($que);


$que = "SELECT `dateStart`, `dateEnd`, `notHolidayName` AS `holidayName` FROM `not_holidays` WHERE '" . $firstDay . "' <= `dateEnd` AND '" . $lastDay . "' >= `dateStart` AND `active` = 1 ORDER BY `dateStart`";
$holidays = udb::single_list($que);






if($calendarOrders){ 
	$calendarOrders = setCalendarOrders($calendarOrders,$showDays,$viewtype,$caltype);
}



?>


<div class="health_send">
<?php
    if (count($_CURRENT_USER->sites()) > 1){
        ?>    
    <div class="site-select">
		בחר מתחם

     
                <select title="שם מתחם" onchange="select_site_id(this);">
                <option value="<?=$cur_sid?>"><?=$cur_sid_name?></option>
                <?php
                foreach($sname_list as $id => $name) {
                    ?>
                    <option value="<?php echo $name['siteID'];?>" ><?php echo $name['siteName'];?></option>
                    <?php
                }
                ?>
            </select>
        
    </div>
    <?php
     }

setclanedarNav($caltype);

	?>
     


</div>

<section class="atfusa <?=$viewtype == 1? "month" : ""?>">
	<div class="title">יומן משמרות</div>
	<div class="top-buttons">
		<?if($viewtype == 2){?>
		<a href="<?=WEBSITE?>user/index.php?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($date."-1 day"))?>&viewtype=<?=$viewtype?>" class="prev">יום קודם</a>
		<?}else{?>
		<a href="<?=WEBSITE?>user/index.php?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($dateMonth."-1 month"))?>&viewtype=<?=$viewtype?>" class="prev">חודש קודם</a>
		<?}?>
		<div class="month-select">
			<?if($viewtype==2){?>
			<select id="monthselect" onchange="newload()">
				<?
				
				for($i= -30;$i<=60;$i++){?>
				<option value="<?=$i!=0? date('d/m/Y', strtotime($i." day", strtotime($date))): date('d/m/Y');?>" <?=$i==0? "selected" : ""?>>
					<?=date('d.m.y', strtotime($i." day", strtotime($date)));?>
				</option>
			<?}?>
			</select>
			<? $todayRev = date('Y-m-d',strtotime($date));?>
			יום <?=$dayName[date('w', strTotime($date))]?> <?=findHoliday($todayRev)?><br>
			<?=date('d', strTotime($date))?> ב<?=$monthNames[date('n', strTotime($date))-1]?> <?=date('Y', strTotime($date))?>
			
			<?}else{?>
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
			<?}?>
			
		</div>
		<?if($viewtype == 2){?>
		<a href="<?=WEBSITE?>user/index.php?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($date."+1 day"))?>&viewtype=<?=$viewtype?>" class="prev">יום הבא</a>
		<?}else{?>
		<a href="<?=WEBSITE?>user/index.php?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($dateMonth."+1 month"))?>&viewtype=<?=$viewtype?>" class="next">חודש הבא</a>
		<?}?>
		<?php
                if(!$viewtype ==1){
                ?>
		<div class="clicks">
			<div class="short-click">לחיצה קצרה - סימון פנוי תפוס</div>
			<div class="long-click">לחיצה ארוכה - פתיחת הזמנה</div>
		</div>
		<?php 
                    }
                ?>
		
		<select id="viewtype" onchange="newload()">
			<option value="0" <?=!$viewtype? "selected" : ""?>>תצוגת רשימה</option>
			<option value="1" <?=$viewtype==1? "selected" : ""?>>תצוגה חודשית</option>
			<option value="2" <?=$viewtype==2? "selected" : ""?> >תצוגה יומית</option>
		</select>
	</div>



	<div class="days-table <?=$viewtype == 2? "hours" : ""?>" id="days-table">
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
					<?//print_r($room);?>
					<?if($caltype ==2){?>
						<div class="name"><?=$room['unitName']?></div>
						<div class="people adults"><?=$sname[$siteID]?></div>
					<?}else{?>
						<div class="name"><?=$room['masterName']?> (<?=$room['masterID']?>)</div>
					<?}?>
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
		if($viewtype == 1){
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
							<div class="title">הזמנות <?=$viewtype==2? "מיום" : "מחודש"?> קודם</div>
						</div>
					</div>
				</div>
		<?
		for($i=1; $i<=count($showDays); $i++) {
			$today = date('d/m/Y',strtotime($showDays[$i]));
			$todayRev = date('Y-m-d',strtotime($showDays[$i]));?>
				<?if($viewtype == 2){?>
				<div class="day">
					<div class="top">
						<div class="row">
							<span class="day-name"></span>
							<span class="day-date" ><?=((($i-1)<10)? '0':"").($i-1).':00'?></span>
						</div>
					</div>
				</div>
				<?}else{?>
				<div class="day <?=($today==date("d/m/Y"))?"today":""?> <?=(date("w",strtotime($todayRev))>4)?"weekend":""?>" data-date="<?=$today?>">
					<a href="/user/index.php?page=absolute_calendar&type=<?=$caltype?>&viewtype=2&date=<?=date("d/m/Y",strtotime($todayRev))?>" class="top">
						<div class="row">
							<div class="special_date"><?=findHoliday($todayRev)?></div>
							<span class="day-name"><?=$dayName[date('w', strTotime($todayRev))]?></span>
							<span class="day-date" ><?=date("d/m/Y",strtotime($todayRev))?></span>
						</div>
					</a>
				</div>
				<?}?>
			<?}
		}?>
		</div>
<?php
		if($viewtype == 1){?>
		<div class="days">
		<?

		}else{?>

			<div class="day last-month">
				<div class="rooms">
<?php
			if($viewtype == 2){
				$todayRev = date('Y-m-d 23:00:00',strtotime($date. "-1 day"));
			}else{
				$todayRev = date('Y-m-d',strtotime($showDays[1]. "-1 day"));
			}
			foreach($crooms as $siteID => $rlist){
				foreach($rlist as $room) {

		?>
					<div class="row<?=($room['active'] ? '' : ' inactive')?>" data-col="<?=$todayRev?>"  data-row="<?=$room[$caltypeName]?: "0"?>" data-date="<?=date("d/m/Y",strtotime("-1 day",strtotime($curYearDate."-".$curMonthDate."-01")))?>" data-name="<?=outDb($room['masterName'])?>" data-uid="<?=$room['masterID']?>">
						
					</div>
		<?php
				}
			}

?>

				</div>
			</div>
<?php	}


        for($i=1; $i<=count($showDays); $i++) {
			$today = date('d/m/Y',strtotime($showDays[$i]));
			if($viewtype == 2){
				$todayRev = date('Y-m-d '.(($i<=10)? "0" : "".($i-1)).':00:00',strtotime($date));
			}else{
				$todayRev = date('Y-m-d',strtotime($showDays[$i]));
			}
?>
			<div class="day <?=($today==date("d/m/Y"))?"today":""?> <?=(date("w",strtotime($todayRev))>4)?"weekend":""?>" data-date="<?=$today?>" data-hour="<?=$todayRev?>">
				<?if($viewtype == 1){?><div class="daydate"><div><?=explode("-",$showDays[$i])[2]?></div><span><?=findHoliday($todayRev)?></span></div><?}?>
				<div class="rooms spa">
<?php
            foreach($crooms as $siteID => $rlist){
                foreach($rlist as $room) {
?>
					<div class="a1a1 row<?=($room['active'] ? '' : ' inactive')?>" data-col="<?=$todayRev?>" data-row="<?=$room[$caltypeName]?: "0"?>" data-date="<?=$today?>" data-name="<?=outDb($room['masterName'])?>" data-uid="<?=$room[$caltypeName]?>" data-num="<?=$roomNumber[$room['unitID']]?>">
						
					</div>
<?php
                }
            }
?>
				</div>
			</div>
<?php
			if($viewtype == 1 && ($startDay+$i)%7 < 1){?>
			</div>
			<div class="days">

			<?}
        }
		if($viewtype == 1){?>
			</div>

		<?}?>

		</div>
	</div>
</section>


<?//print_r($calendarOrders);?>

<script src="assets/js/absolute_tfusa.js?v=<?=time()?>"></script>
<script type="text/javascript">


$('#days-table').scroll(function(){
	$('#days').css("top",this.scrollTop + "px");
});

var currentMonth = '<?=date("m/Y",strtotime($date))?>';
var endMonthDay = '<?=$endMonthDay?>';
var passMonthYear = '<?=date("t/m/Y",strtotime("first day of last month",strtotime($date)))?>';

var viewtype = <?=$viewtype ?>;
var scrollmonth = <?=(date("m",strtotime($date)) == date("m"))? date("d",strtotime($date)) : 0?>

$(function(){
	<?php
	foreach($calendarOrders as $cOrder){?>
		 addOrder(<?= json_encode($cOrder)?>);
	<?php
	}?>

});
        
function select_site_id(ele) {
	var page = "absolute_calendar";
	var the_val = $(ele).val();
	window.location='/user/index.php?page=absolute_calendar&type=' + <?=$caltype?> +'viewtype='+ $('#viewtype').val() +'&date='+ $('#monthselect').val()+'&siteid='+ the_val; 

}
        
function newload(){		
	window.location='/user/index.php?page=absolute_calendar&type=' + <?=$caltype?> +'&viewtype='+ $('#viewtype').val() +'&date='+ $('#monthselect').val()+'&siteid=<?=$cur_sid?>' ;
}

function showunits(type,units,ttl){
	//debugger;
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
        
</script>
