<link href="assets/css/atfusa.css?v=<?=rand()?>" rel="stylesheet">

<?php
$classtypes[0] = "shifts";
$classtypes[1] = "therapists";
$classtypes[2] = "units";

$totalname[0] = "משמרות";
$totalname[1] = "טיפולים";
$totalname[2] = "הזמנות";


$caltype = intval($_GET['type']);
//if(!$caltype) $caltype = 2;
if($caltype == 2){
	$caltypeName ='unitID';
	$caltypeName2 ='unitID';
}else{
	$caltypeName ='masterID';
	$caltypeName2 = $caltype==1 ?'therapistID' : 'masterID';
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
//SET daily activity hours
if($viewtype==2){
	$weekday = date('w', strTotime($date));
	$que = "SELECT * FROM sites_weekly_hours WHERE weekday = " .$weekday. " AND siteID = ".$cur_sid;
	$hours = udb::single_row($que);
	$dayly_from = ($hours["treatFrom"]?: ($hours["openFrom"])?: "00:00:00");
	$dayly_until = ($hours["treatTill"]?: ($hours["openTill"])?: "24:00:00");
	$fromMin = intval(substr($dayly_from,0,2))*60 + intval(substr($dayly_from,3,2));
	$untilMin = intval(substr($dayly_until,0,2))*60 + intval(substr($dayly_until,3,2));
	for($i=1;$i<=24;$i++){
		$diff1 = $fromMin - ($i-1)*60;
		$diff2 = $untilMin - ($i)*60;
		//echo $i."/".$diff1."/".$diff2."   aaa<br>";
		if($diff1>0){
			if($diff1<60){	$hour[$i] = round(($diff1/60)*100);}
			else {			$hour[$i] = 100;}			
			$hourClass[$i] = "offHours";
		}else if($diff2<0){
			if($diff2>-60){	$hour[$i] = round(($diff2/60)*100 + 100);}
			else {			$hour[$i] = 100;}
			$hourClass[$i] = "offHours end";
		}
	}
	//print_r($hours);



}



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
		,workerType
		,siteID
		,active
		,siteName AS masterName 
		,gender_self
		,IF(workerType = 'fictive', 1, 0) AS fictive
		,gender_client
		FROM therapists WHERE siteID = '".$cur_sid."' AND active = 1 AND deleted < 1 ";
		if($caltype<1){
			$que.= " AND workerType <> 'fictive' ";
		}
		$que .="ORDER BY fictive DESC, masterName";
}
$crooms = udb::key_list($que, 'siteID');
//print_r($crooms);

##############################################

if($caltype>0){
	if($caltype ==2){
		$crooms[0][0]["unitName"] = "ללא חדר";
		$crooms[0][0]["active"] = "1";
	}else{
		//$crooms[0][0]["masterName"] = "ללא מטפל";
		//$crooms[0][0]["gender_self"] = 3;
		//$crooms[0][0]["active"] = "1";
	}
	


}
ksort($crooms, SORT_NUMERIC);
	
$calendarOrders = array();

##############################################


if($caltype>0){
	$que = "SELECT `orders`.*,`orderUnits`.`unitID`
		FROM `orders` 
		LEFT JOIN `orderUnits` USING(`orderID`)
		WHERE siteID IN (" . $_CURRENT_USER->sites(true) . ") AND `orders`.`status`=1 AND ((`orders`.orderID <> `orders`.parentOrder AND `orders`.parentOrder > 0)".($caltype ==2? " OR `orderUnits`.unitID > 0" : "").")
		AND timeFrom <= '".$lastDay." 23:59:59' AND timeUntil >= '".$firstDay." 00:00:00'
	";
	$calendarOrders = udb::full_list($que);
}

##############################################

//Metaplim / Shifts
$fictiveShifts = array();
if($caltype<2 ){
	if($viewtype == 2){
	//print_r($crooms);
		foreach($crooms as $key => $rooms){
			if($key>0){
				foreach($rooms as $unit){
					$masters[] = $unit["masterID"];
					if($unit['fictive']){
						$fictiveShifts[$unit['masterID']]['timeFrom'] = $lastDay." ".$dayly_from;
						$fictiveShifts[$unit['masterID']]['timeUntil'] = $lastDay." ".$dayly_until;
						$fictiveShifts[$unit['masterID']]['masterID'] = $unit['masterID'];
					}
				}			
			}
			
		}
	}

	$que = "SELECT `spaShifts`.*
		,COUNT(*) AS counter   
		,DATE_FORMAT(timeFrom,'%d/%m/%Y') AS date_str_from
		,GROUP_CONCAT(DATE_FORMAT(timeFrom,'%H:%i'),'-',DATE_FORMAT(timeUntil,'%H:%i') SEPARATOR ', ') AS time_list_from
		,GROUP_CONCAT(orderID SEPARATOR ', ') AS orderID_list
		FROM `spaShifts` 
		WHERE siteID = '".$cur_sid."' AND `spaShifts`.`status`=1
		AND timeFrom <= '".$lastDay." 23:59:59' AND timeUntil >= '".$firstDay." 00:00:00'";
		if($masters){
		$que.="AND masterID IN(".implode(",",$masters).")";
		}
		$que.="GROUP BY DATE_FORMAT(timeFrom,'%d/%m/%Y'), masterID       
	";
$calendarShifts = udb::full_list($que);

//print_r($calendarShifts);

}
	//echo $que;



	
	
//print_r($calendarOrders);

####################################################################
if($calendarShifts){
	//SET SHIFTS SLOTS FROM time_list_from
	if($caltype < 2){
		//print_r($calendarShifts);
		foreach($calendarShifts as $k => $m){
			//echo "shitshere";
			$times = explode(",",$m['time_list_from']);
			//print_r($times);
			foreach($times as $time){
				$t = explode('-',trim($time));
				$m['timeFrom'] = substr($m['timeFrom'],0,10)." ".$t[0].":00";;
				$m['timeUntil'] = substr($m['timeUntil'],0,10)." ".$t[1].":00";
				$newCalShifts[] = $m;
			}
		}
		$calendarShifts = $newCalShifts;
		//print_r($calendarOrders);
	}
	
	//print_r($calendarShifts);

	$calendarOrders = array_merge($calendarOrders,$calendarShifts);
}
$calendarOrders = array_merge($calendarOrders,$fictiveShifts);
//print_r($calendarOrders);

####################################################################
if(count($calendarOrders)){
	//SET TOTALS
	foreach($calendarOrders as $k => $m){
		$m[$caltypeName2] = $m[$caltypeName2]?: 0;
		if($m["allDay"]<1 && !($caltype==1 && $m["masterID"]>0)){
			$totals[$m[$caltypeName2]] +=1;
			$totalsTime[$m[$caltypeName2]] += (strtotime($m['timeUntil']) - strtotime($m['timeFrom']))/60;			
		}
	}
//print_r($calendarOrders);
}
####################################################################


    $que = "SELECT `dateStart`, `dateEnd`, `notHolidayName` AS `holidayName` FROM `not_holidays` WHERE '" . $firstDay . "' <= `dateEnd` AND '" . $lastDay . "' >= `dateStart` AND `active` = 1 ORDER BY `dateStart`";
    $holidays = udb::single_list($que);


#############################################################

//חותך הזמנות לפי שבוע במצב חודשי, מקצר הזמנות שחורגות מטווח התאריכים בתצוגה

if(count($calendarOrders)){ 
	if($viewtype == 1){//CASE PER MONTH
		foreach($calendarOrders  as $k => $m){
			$i = 1;
			while($i < count($showDays)){

				//NEEDS TO ALSO CUT EACH ORDER IN THE END IF TO LONG
				if(strtotime($m['timeFrom']) < strtotime($showDays[$i]) && $i==1 && $m['allDay']!=1){
					$calendarOrders[$k]['timeFrom'] = date("Y-m-d 00:00",strtotime(strtotime($showDays[1])));
				}
				if(strtotime($m['timeFrom'])<strtotime($showDays[$i]) && strtotime($m['timeUntil']) >= strtotime($showDays[$i]) && $m['allDay']!=1){
					$m['timeFrom'] = date("Y-m-d 00:00:00",strtotime($showDays[$i]));
					$calendarOrders[] = $m;
				}
				$i+=7;

			}

		}
	}else if($viewtype == 2){ //CASE PER HOUR
		$date_start = date("Y-m-d 00:00:00",strtotime($date));
		$date_end = date("Y-m-d 23:59:59",strtotime($date));
		
		foreach($calendarOrders  as $k => $m){
			if(strtotime($m['timeFrom'])<strtotime($date_start) ){
				$calendarOrders[$k]['timeFrom'] = date("Y-m-d 23:00:00",strtotime($date. "-1 day"));
			}
			//echo strtotime($m['timeFrom'])." - ".strtotime($date_start)." - | -  ".$m['timeFrom']." - ".$date_start."<br><br>";
			if(strtotime($m['timeUntil'])>strtotime($date_end)){
				
				$calendarOrders[$k]['timeUntil'] = date("Y-m-d 23:59:59",strtotime(($date)));
				$calendarOrders[$k]['this_end'] = "this";
				//echo "this_end";
			}
			//echo PHP_EOL;
		}
	}else{ //CASE REGULAR
		foreach($calendarOrders  as $k => $m){
			if(strtotime($m['timeFrom'])<strtotime($showDays[1]) && $m['allDay']!=1){
				$calendarOrders[$k]['timeFrom'] = date("Y-m-d H:i",strtotime($showDays[1]."-1 day"));
			}
			
			if(strtotime($m['timeUntil'])>strtotime($showDays[count($showDays)]) && $m['allDay']!=1){
				$calendarOrders[$k]['timeUntil'] = date("Y-m-d 23:59:59",strtotime($showDays[count($showDays)]));
			}
		}
	}
	


	################################################################

	//SET DATA calendarOrders

	foreach($calendarOrders  as $k => $m){
		
		if($viewtype == 2){	
			$place_col = substr($m["timeFrom"],0,13).":00:00";
		}else{		
			
			$place_col = substr($m["timeFrom"],0,10);
		}
		if($caltype == 2){	
		$place_row = $m["unitID"]?: "0";
		}else if($caltype == 1 && !$m["masterID"]){
		$place_row = $m["therapistID"]?: "0";
		}else{
		$place_row = $m["masterID"]?: "0";
		}

		
		$calendarOrders[$k]["col"]= $place_col;
		$calendarOrders[$k]["row"]= $place_row;
		$time1 = strtotime($m["timeFrom"]);
		$time2 = strtotime($m['timeUntil']);
		//echo $time1." - ".$time2;
		if($viewtype == 2){
			$calendarOrders[$k]["width"] = round(((($time2 - $time1) / 60)/60) * 100);
			$calendarOrders[$k]["right"] = round((date("i",strtotime($m['timeFrom']))/60) *100);
		}else{		
			$calendarOrders[$k]["width"] = round(((($time2 - $time1) / 3600)/24) * 100);
			$calendarOrders[$k]["right"] = round((date("H",strtotime($m['timeFrom']))/24) *100);
		}
		$calendarOrders[$k]["icon"]= $domain_icon[$m["domainID"]];
		
			
	}
//print_r($calendarOrders);
	#################################################################
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

<?if($caltype == 2 ){?>
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

					<a style="margin-top:20px;border-color:#333" onclick="showunits('units',Array(<?=implode(",",$siteRooms)?>),$(this).html())">כל היחידות - <?=$sname[$siteID]?></a>
					<div class="units">
				<?
				$roomIDs = 0;
				foreach($rlist as $room) {
					if(count($roomType[$room["roomID"]]) > 1){
						if($roomIDs ==0){
						?>
						<a class="roomNameTitle" style="border-color:#0dabb6;margin-top:10px" onclick="showunits('units',Array(<?=implode(",",$roomType[$room["roomID"]])?>),$(this).html())"><?=$room['unitName']?> <span> - <?=count($roomType[$room["roomID"]])?> יחידות</span></a>
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

<?}?>

<section class="atfusa <?=$viewtype == 1? "month" : ""?> <?=$classtypes[$caltype]?> <?=$viewtype==2? "dayly" : ""?>">
	
	<div class="top-buttons">
	


		<?if($viewtype == 2){?>
		<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($date."-1 day"))?>&viewtype=<?=$viewtype?>" class="prev">יום קודם</a>
		<?}else{?>
		<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($dateMonth."-1 month"))?>&viewtype=<?=$viewtype?>" class="prev">חודש קודם</a>
		<?}?>
		
			<style>
				#ui-datepicker-div{z-index:9999!important}
			</style>

		<div class="month-select">

			
			
				<?if($viewtype==2){?>
					<input type="text" value="" id="monthselect" name="maor" style="height:100%;" onchange="newload_dpicker()" readonly="" class="datepicker" placeholder="תאריך">
			
			<? $todayRev = date('Y-m-d',strtotime($date));?>
			יום <?=$dayName[date('w', strTotime($date))]?> <?=findHoliday($todayRev)?><br>
			<?=date('d', strTotime($date))?> ב<?=$monthNames[date('n', strTotime($date))-1]?> <?=date('Y', strTotime($date))?>
			
			<?}else{?>
				<!-- <input type="text" value="" id="monthselect" name="maor" style="height:100%;" onchange="newload_dpicker()" readonly="" class="datepicker" placeholder="תאריך"> -->


<input type="text" name="month" id="monthselect" style="height:100%;" onchange="newload_dpicker()" class="datepicker" />

			
			<?=$monthNames[date('n', strTotime($date))-1]?><br>
			<?=date('m - Y', strTotime($date))?>
			<?}?>
		
			
		</div>
		<?if($viewtype == 2){?>
		<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($date."+1 day"))?>&viewtype=<?=$viewtype?>" class="next">יום הבא</a>
		<?}else{?>
		<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($dateMonth."+1 month"))?>&viewtype=<?=$viewtype?>" class="next">חודש הבא</a>
		<?}?>
		<?php
        if($caltype==1 && $viewtype == 2){
                ?>
		<div class="settings">
			<div class="view-set" onclick="toggleFlip()">סובב תצוגה</div>
			<div class="time-set">
				<div onclick="$('.atfusa').removeClass('wide')">רגיל</div>
				<div onclick="$('.atfusa').addClass('wide')">רחב</div>			
			</div>
		</div>
		<?php 
        }
		if($caltype<1){
                ?>
		<div class="clicks">
			<div class="short-click">לחצו על התאריך כדי לערוך</div>
			<div class="long-click">או להוסיף משמרות למטפל</div>
		</div>
		<?php 
        }
		if($caltype == 2){?>
			<div id="showSelect" onclick="$('#unitSelect').fadeIn('fast')">כל היחידות</div>
		<?}?>
		
		<div id="viewtype" value="<?=$viewtype?>">			
			<a href="?page=absolute_calendar&type=<?=$caltype?>&viewtype=2&date=<?=$_GET['date']?>&siteid=<?=$_GET["siteid"]?>" class="<?=$viewtype==2? "active" : ""?>" >תצוגה יומית</a>
			<a href="?page=absolute_calendar&type=<?=$caltype?>&viewtype=0&date=<?=$_GET['date']?>&siteid=<?=$_GET["siteid"]?>" class="<?=!$viewtype? "active" : ""?>">תצוגת רשימה</a>
			<a href="?page=absolute_calendar&type=<?=$caltype?>&viewtype=1&date=<?=$_GET['date']?>&siteid=<?=$_GET["siteid"]?>" class="<?=$viewtype==1? "active" : ""?>">תצוגה חודשית</a>
		</div>
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
			$room[$caltypeName] =$room[$caltypeName]?: 0;
?>
				<div class="row<?=($room['active'] ? '' : ' inactive')?> <?=$room['gender_self']==2? "female" : ($room['gender_self']==3? "nogender" : "")?>" data-name="<?=outDb($room['unitName'])?>" data-uid="<?=$room['unitID']?>">
					<?//print_r($room);?>
					<?if($caltype ==2){?>
						<div class="name"><?=$room['unitName']?></div>
						<div class=" "><?=$sname[$siteID]?></div>
					<?}else{?>
						<div class="name"><?=$room['masterName']?></div>
						<div class=" "><?=$sname[$siteID]?></div>						
					<?}?>
					
					<div class="<?=$totals[$room[$caltypeName]]? "" : "nototal"?>" ><?=$totals[$room[$caltypeName]]?: "אין"?> <?=$totalname[$caltype]?> <?=$viewtype==2? "היום" : "החודש"?></div>
					<?if($caltype<2){?>
						<?if($totals[$room[$caltypeName]]){?>
							<div><?=floor($totalsTime[$room[$caltypeName]]/60)?>:<?=(round($totalsTime[$room[$caltypeName]]%60))>9? round($totalsTime[$room[$caltypeName]]%60) : "0".round($totalsTime[$room[$caltypeName]]%60)?> שעות</div>
						<?}?>
					<div><?=$room['gender_self']==2? "מטפלת" : "מטפל"?> ב<?=$room['gender_client']==3? "גברים ונשים" : ($room['gender_client']==2? "נשים בלבד" : "גברים בלבד")?></div>
					<?}?>

				</div>
<?php
        }
    }

?>
			</div>
		</div>
		<div class="l-side" id="divToScroll">
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

		}else {?>
			<?if($caltype==2){?>
				<div class="day last-month">
					<div class="top">
						<div class="row">
							<div class="title">הזמנות <?=$viewtype==2? "מיום" : "מחודש"?> קודם</div>
						</div>
					</div>
				</div>
			<?}?>
		<?
		if($viewtype == 2 && $caltype!=2) $start=8; else $start=1;
		for($i=$start; $i<=count($showDays); $i++) {
			$today = $viewtype<2? date('d/m/Y',strtotime($showDays[$i])) : date('d/m/Y',  strtotime($date));
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
					<a href="index.php?page=absolute_calendar&type=<?=$caltype?>&viewtype=2&date=<?=date("d/m/Y",strtotime($todayRev))?>" class="top">
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

		}else if($caltype==2){?>

			<div class="day last-month">
				<div class="rooms spa">
<?php
			if($viewtype == 2){
				$todayRev = date('Y-m-d 23:00:00',strtotime($date. "-1 day"));
			}else{
				$todayRev = date('Y-m-d',strtotime($showDays[1]. "-1 day"));
			}
			foreach($crooms as $siteID => $rlist){
				foreach($rlist as $room) {

		?>
					<div class="row<?=($room['active'] ? '' : ' inactive')?> " data-col="<?=$todayRev?>"  data-row="<?=$room[$caltypeName]?: "0"?>" data-date="<?=date("d/m/Y",strtotime("-1 day",strtotime($curYearDate."-".$curMonthDate."-01")))?>" data-name="<?=outDb($room['masterName'])?>" data-uid="<?=$room['masterID']?>">
						
					</div>
		<?php
				}
			}

?>

				</div>
			</div>
<?php	}

		if($viewtype == 2 && $caltype!=2) $start=8; else $start=1;
        for($i=$start; $i<=count($showDays); $i++) {
			$today = $viewtype<2? date('d/m/Y',strtotime($showDays[$i])) : date('d/m/Y',  strtotime($date));
			if($viewtype == 2){
				$todayRev = date('Y-m-d '.(($i<=10)? "0" : "").($i-1).':00:00',strtotime($date));
			}else{
				$todayRev = date('Y-m-d',strtotime($showDays[$i]));
			}
			if($viewtype==2 && $hour[$i]){
				$offhourDiv = "<div class='".$hourClass[$i]."' style='width:".$hour[$i]."%'></div>";
			}else{
				$offhourDiv = "";
			}
?>
			<div data-i="<?=$i?>" class="day <?=($today==date("d/m/Y"))?"today":""?> <?=(date("w",strtotime($todayRev))>4)?"weekend":""?>" data-date="<?=$today?>" data-hour="<?=$todayRev?>">
				<?=$offhourDiv;?>
				<?if($viewtype == 1){?><div class="daydate"><div><?=explode("-",$showDays[$i])[2]?></div><span><?=findHoliday($todayRev)?></span></div><?}?>
				<div class="rooms spa <?=$classtypes[$caltype]?>">
<?php
            foreach($crooms as $siteID => $rlist){
                foreach($rlist as $room) {
?>
					<div class="a1a1 row<?=($room['active'] ? '' : ' inactive')?> <?=$room['gender_self']==2? "female" : ($room['gender_self']==3? "nogender" : "")?>" data-col="<?=$todayRev?>" data-row="<?=$room[$caltypeName]?: "0"?>" data-date="<?=$today?>" data-name="<?=outDb($room['masterName'])?>" data-uid="<?=$room[$caltypeName]?>" data-num="<?=$roomNumber[$room['unitID']]?>">
						
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
<?
if($viewtype < 2){
	$scrollmonth = (date("m",strtotime($date)) == date("m"))? date("d",strtotime($date)) : 0;
}else{
	$scrollmonth = 10;
}
?>
<?//print_r($calendarOrders);?>

<script src="assets/js/absolute_tfusa.js?v=<?=time()?>"></script>

<?php
if($_GET['date']) {
	$date = $_GET['date'];
	$date = str_replace('/', '-', $date);
}
?>

<script type="text/javascript" src="//www.spaplus.co.il/js/jquery.ui.custom.min.js"></script>
<script type="text/javascript" src="//www.spaplus.co.il/datepicker/jquery.ui.datepicker-he.js"></script>

<?php if($viewtype == 2) { ?>
<script>
	$.datepicker.setDefaults( $.datepicker.regional[ "he" ] );


if($( ".datepicker" ).length){
    $( ".datepicker" ).datepicker({
        minDate: <?=$_GET['date']?"'".date('d/m/Y', strtotime($date.' -30 day'))."'":"'".date('d/m/Y', strtotime('-30 day'))."'"?>,
        defaultDate: <?="'".date('d/m/Y')."'"?>,
        maxDate: <?=$_GET['date']?"'".date('d/m/Y', strtotime($date.' +60 day'))."'":"'".date('d/m/Y', strtotime('+60 day'))."'"?>
    });
}
$(function() {
	$(".datepicker").datepicker("setDate", <?=$_GET['date']?"'".$_GET['date']."'":"'".date('d/m/Y')."'"?>);
})
	</script>

<?php } ?>


<?php if($viewtype != 2) { ?>
				<script type="text/javascript">
	$.datepicker.setDefaults( $.datepicker.regional[ "he" ] );

	if($( ".datepicker" ).length){
 
    $(".datepicker").datepicker({
        minDate: <?=$_GET['date']?"'".date('d/m/Y', strtotime($date.' -1 year'))."'":"'".date('d/m/Y', strtotime('-1 year'))."'"?>,
        defaultDate: <?="'".date('d/m/Y')."'"?>,
        maxDate: <?=$_GET['date']?"'".date('d/m/Y', strtotime($date.' +2 year'))."'":"'".date('d/m/Y', strtotime('+2 year'))."'"?>,
        dateFormat: 'dd/mm/yy',
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,

        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).val($.datepicker.formatDate('01/mm/yy', new Date(year, month, 1)));
			newload_dpicker();
        }
    });

    $(".datepicker").focus(function () {
        $(".ui-datepicker-calendar").hide();
        $("#ui-datepicker-div").position({
            my: "center top",
            at: "center bottom",
            of: $(this)
        });
    });
	}

	

$(function() {
	$(".datepicker").datepicker("setDate", <?=$_GET['date']?"'".$_GET['date']."'":"'".date('d/m/Y')."'"?>);
})
</script>


<?php } ?>

<script type="text/javascript">
var caltype = <?=$caltype?>;

$('#days-table').scroll(function(){
	$('#days').css("top",this.scrollTop + "px");
});

var currentMonth = '<?=date("m/Y",strtotime($date))?>';
var endMonthDay = '<?=$endMonthDay?>';
var passMonthYear = '<?=date("t/m/Y",strtotime("first day of last month",strtotime($date)))?>';

var viewtype = <?=$viewtype ?>;
var caltype = <?=$caltype ?>;
var scrollmonth = <?=$scrollmonth?>

$(function(){
	<?php
	if(count($calendarOrders)){
	foreach($calendarOrders as $cOrder){
		if($cOrder['masterID'] && $caltype == 1){?>
		addShift_inOrders(<?= json_encode($cOrder)?>);
		<?}else{?>
		addOrder(<?= json_encode($cOrder);?>);
		<?php }
		}
	}?>

});
        
function select_site_id(ele) {
	var page = "absolute_calendar";
	var the_val = $(ele).val();
	window.location='index.php?page=absolute_calendar&type=' + <?=$caltype?> +'viewtype='+ $('#viewtype').val() +'&date='+ $('#monthselect').val()+'&siteid='+ the_val;

}
        
function newload(){		
window.location='index.php?page=absolute_calendar2&type=' + <?=$caltype?> +'&viewtype='+ $('#viewtype').val() +'&date='+ $('#monthselect').val()+'&siteid=<?=$cur_sid?>' ;
}


function newload_dpicker(){		
window.location='index.php?page=absolute_calendar2&type=' + <?=$caltype?> +'&viewtype='+ parseInt($('#viewtype').attr('value')) +'&date='+ $('#monthselect').val()+'&siteid=<?=$cur_sid?>&maor=1' ;
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


function toggleFlip(){
$('.atfusa').toggleClass('flipped');
if($('.atfusa').hasClass('flipped')){
	var size2 = 'height';
	var margin2 = 'top';
	var setwidth = true;
}else{		
	var size2 = 'width';
	var margin2 = 'right';
	var setwidth = false;
}

	$('.shift').each(function(){
		debugger;
		var newsize = $(this).attr("data-size")+"%";
		var newmargin = $(this).attr("data-margin")+"%";
		$(this).attr('style',"");
		$(this).css(size2, newsize);
		$(this).css(margin2, newmargin);
		if(setwidth) $(this).css('width', '100%');
	})
	
	$('.order').each(function(){
		var newsize = $(this).attr("data-size")+"%";
		var newmargin = $(this).attr("data-margin")+"%";
		$(this).attr('style',"");
		$(this).css(size2, newsize);
		$(this).css(margin2, newmargin);
		if(setwidth) $(this).css('width', '100%');
	})
}
        
</script>
