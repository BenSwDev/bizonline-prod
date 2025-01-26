<div id="fullscale" class="<?=$_SESSION['fullscale']? "on" : ""?>"></div>
<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=atfusa&v=<?=rand()?>" rel="stylesheet">
<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$classtypes[0] = "shifts";
$classtypes[1] = "therapists";
$classtypes[2] = "units";

$totalname[0] = "משמרות";
$totalname[1] = "ט'";
$totalname[2] = "הזמנות";

$workers = intval($_GET['workers'])?: 0;

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


$adminAccess = $_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN);
if($_CURRENT_USER->suffix()!='member'){
	$adminAccess = 1;
}



//echo "access - ". $_CURRENT_USER->suffix()."<br>";
//echo "access - ". $adminAccess;

function findHoliday($date){
    global $holidays;

    $data = [];

    foreach($holidays as $day)
        if ($day['dateStart'] <= $date && $day['dateEnd'] >= $date)
            $data[] = ' - ' . $day['holidayName'] ;

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


$weekly = $_GET['weekly'];
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
	if($weekly){
		$dayofweek = date("w",date(strtotime($date)));
		$firstDay = date('Y-m-d',  strtotime($date." -".date('w',strtotime($date))." day"));
		$lastDay = date('Y-m-d',  strtotime($date." +".(6 - date('w',strtotime($date)))." day"));
	}
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
$flareDates = array();
$flareDates = returnMarkedDates($firstDay,$lastDay);
/*
foreach(explode(',', $_CURRENT_USER->active_site()) as $ssid) {
		$que = "SELECT `dateFrom` AS `dateStart`, `dateTo` AS `dateEnd` FROM `sites_periods` WHERE `periodType` = 0 
		AND `dateFrom` >= '" . $fromDate . "' 
		AND `dateTo` <= '" . $fromDate . "'
		AND `siteID` = " . $ssid . " ORDER BY `dateEnd`";
		$custom = udb::key_row($que,'id');
		$datesArray[$ssid] = [];
		foreach($custom as $cust) {
			$diff=date_diff(date_create($cust['dateStart']),date_create($cust['dateEnd']))->days;

			for($xi = 0; $xi<=$diff; $xi++) {
				array_push($datesArray[$ssid], date('Y-m-d', strtotime($cust['dateStart'].'+'.$xi.' day')));
			}
		}
	}
	print_r($custom);
*/
############################################## PICK FIRST IF NOT ONE
$sname_list = udb::key_row("SELECT siteID,siteName,guid FROM sites WHERE siteID IN (" . $_CURRENT_USER->sites(true) . ")",'siteID');
	
	$cur_sid = $sname_list[ $_CURRENT_USER->active_site()]['siteID'];
	$cur_guid = $sname_list[$_CURRENT_USER->active_site()]['guid'];
	$cur_sid_name = $sname_list[$_CURRENT_USER->active_site()]['siteName'];
	
	/*if (isset($_GET["siteid"])) {
		foreach ($sname_list as $sname_list_vals) {
			if ($sname_list_vals['siteID'] == $_GET["siteid"]) {
				$cur_sid = $sname_list_vals['siteID'];
				$cur_guid = $sname_list_vals['guid'];
				$cur_sid_name = $sname_list_vals['siteName'];
			}
		}
	} */
	
	echo '<input type="hidden" name="sid" id="sid" value="'.$cur_sid.'">';
	echo '<input type="hidden" name="guid" id="guid" value="'.$cur_guid.'">';
	
	

##############################################



$calendarSettings = udb::single_value("SELECT `calendarSettings` FROM `sites` WHERE `siteID` = " . $cur_sid);

$remarksShort = floor($calendarSettings/1)%10? 1 : 0; //ALLOW remark show preview
$roomsShort = floor($calendarSettings/10)%10? 1 : 0;  //ALLOW short rooms order description
$therapistShort = floor($calendarSettings/100)%10? 1 : 0;  //ALLOW short therapists order description
$hidefaces = floor($calendarSettings/1000)%10? 1 : 0;  //Hide gender of client
$groupTreat = floor($calendarSettings/10000)%10? 1 : 0;  //Mark Group Treatment in blue
$timeStart = floor($calendarSettings/100000)%10? 1 : 0;  //SET first hour as start hour of activity/treatments in settings instead of 7:00
$redline = $cur_sid == 1054 ? 0 : 1;


//SET daily activity hours
if($viewtype==2){
	$weekday = date('w', strTotime($date));
	
	
	$que2 = "SELECT periodID FROM `sites_periods` WHERE `periodType` = 0 AND `dateFrom`<= '".date('Y-m-d',strtotime($date))."' AND `dateTo` >= '" . date('Y-m-d',strtotime($date)) . "' AND `siteID` = " . $cur_sid;
	$holiday = udb::single_value($que2);
	$holiday = $holiday? -$holiday : "0";
	if($holiday){
		$que = "SELECT * FROM sites_weekly_hours WHERE weekday = " .$weekday. " AND holidayID=0 AND siteID = ".$cur_sid;
		$default_hours = udb::single_row($que);
	}
	
	$que = "SELECT * FROM sites_weekly_hours WHERE weekday = " .$weekday. " AND holidayID=".$holiday." AND siteID = ".$cur_sid;
	$hours = udb::single_row($que);
	
	//----------SET Default data in case a holiday time data is set to default	--------
	$hours['openFrom'] = $hours['openFrom']?? $default_hours['openFrom'] ;
	$hours['openTill'] = $hours['openTill']?? $default_hours['openTill'] ;
	$hours['treatFrom'] = $hours['treatFrom']?? $default_hours['treatFrom'] ;
	$hours['treatTill'] = $hours['treatTill']?? $default_hours['treatTill'] ;
	//print_r($hours);
	//--------------------------------------------------------------------------

	//print_r($custom);
	//$dayly_from = ($hours["treatFrom"]?: "00:00:00");
	//$dayly_until = ($hours["treatTill"]?: "24:00:00");

	$dayly_from = ($hours["treatFrom"]?: ($hours["openFrom"])?: "00:00:00");
	$dayly_until = ($hours["treatTill"]?: ($hours["openTill"])?: "24:00:00");
	 
	$starthour = $timeStart? (min(intval(substr($hours["openFrom"],0,2)),intval(substr($hours["treatFrom"],0,2)))+1) : 8 ;
	//print_r($hours);
	//echo "<br>".$starthour;
	
	
	$dayly_from2 = ($hours["openFrom"]?: "00:00:00");
	$dayly_until2 = ($hours["openTill"]?: "24:00:00");

	$fromMin = intval(substr($dayly_from,0,2))*60 + intval(substr($dayly_from,3,2));
	$untilMin = intval(substr($dayly_until,0,2))*60 + intval(substr($dayly_until,3,2));
	$fromMin2 = intval(substr($dayly_from2,0,2))*60 + intval(substr($dayly_from2,3,2));
	$untilMin2 = intval(substr($dayly_until2,0,2))*60 + intval(substr($dayly_until2,3,2));

	for($i=1;$i<=24;$i++){
		$diff1 = $fromMin - ($i-1)*60;
		$diff2 = $untilMin - ($i)*60;
		//echo $i."/".$diff1."/".$diff2."   aaa<br>";
		if($diff1>0){
			if($diff1<60){	
				$hour[$i] = round(($diff1/60)*100);}
			else {			
				$hour[$i] = 100;
			}			
			$hourClass[$i] = "offHours";
		}else if($diff2<0){
			if($diff2>-60){	
				$hour[$i] = round(($diff2/60)*100 + 100);}
			else {			
				$hour[$i] = 100;
			}
			$hourClass[$i] = "offHours end";
		}

		$diff1 = $fromMin2 - ($i-1)*60;
		$diff2 = $untilMin2 - ($i)*60;
		//echo $i."/".$diff1."/".$diff2."   aaa<br>";
		if($diff1>0){
			if($diff1<60){	
				$hour2[$i] = round(($diff1/60)*100);}
			else {			
				$hour2[$i] = 100;
			}			
			//$hourClass2[$i] = "offHours offHours2";
		}else if($diff2<0){
			if($diff2>-60){	
				$hour2[$i] = round(($diff2/60)*100 + 100);}
			else {			
				$hour2[$i] = 100;
			}
			//$hourClass2[$i] = "offHours offHours2 end";
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
			WHERE `rooms`.`siteID` = '".$cur_sid."' AND  rooms_domains.active=1 AND  rooms.active > 0
			ORDER BY rooms.showOrder"; //echo $que;  //** | removed AND rooms_units.hasTreatments =1 | 13.11.22
}else if(!$adminAccess){
	//echo "*";
	$que = "SELECT 
		therapists.therapistID AS masterID
		,therapists.siteID
		,therapists.active
		,therapists.siteName AS masterName 
		,therapists.gender_self
		,IF(workerType = 'fictive', 1, 0) AS fictive
		,gender_client
		,`spaShifts`.orderID
		,`spaShifts`.status
		,`spaShifts`.orderName
		FROM therapists LEFT JOIN `spaShifts` ON(therapists.therapistID = `spaShifts`.masterID AND timeFrom <= '".$lastDay." 23:59:59' AND timeUntil >= '".$firstDay." 00:00:00') 
		WHERE therapists.therapistID = " . $_CURRENT_USER->id();
		$que .=" GROUP BY therapists.therapistID ORDER BY fictive DESC, masterName ";
}else if($workers){
	$que = "SELECT 
		workers.workerID AS masterID
		,workers.siteID
		,workers.active
		,workers.siteName AS masterName 
		,workers.gender_self		
		FROM workers 		
		WHERE workers.siteID = '".$cur_sid."' AND active = 1 AND deleted < 1 ";
}else{
	//$daylyCond = ($viewtype == 2)? "AND `spaShifts`.status IS NULL" : ""; //checks if fictive therapist is blocked for the day
	$que = "SELECT 
		therapists.therapistID AS masterID
		,therapists.siteID
		,therapists.workStart
		,therapists.workEnd
		,therapists.active
		,therapists.siteName AS masterName 
		,therapists.gender_self
		,IF(workerType = 'fictive', 1, 0) AS fictive
		,gender_client
		,`spaShifts`.orderID
		,`spaShifts`.status
		,`spaShifts`.orderName
		FROM therapists 
		LEFT JOIN `spaShifts` ON(therapists.therapistID = `spaShifts`.masterID AND `spaShifts`.timeFrom <= '".$lastDay." 23:59:59' AND `spaShifts`.timeUntil >= '".$firstDay." 00:00:00') 
		LEFT JOIN `orders` ON(therapists.therapistID = `orders`.therapistID AND `orders`.timeFrom <= '".$lastDay." 23:59:59' AND `orders`.timeUntil >= '".$firstDay." 00:00:00' and `orders`.status=1) 
		WHERE therapists.siteID = '".$cur_sid."' AND ((active = 1 AND deleted < 1 ";
		if($caltype<1){
			$que.= " AND workerType = 'regular')) ";
		}else{
			$que.= " AND ((`spaShifts`.orderID > 0  AND `spaShifts`.status = 1) 
					OR (workerType = 'fictive' AND (workStart IS NULL OR workStart <= '".$lastDay."') AND (workEnd IS NULL OR workEnd >= '".$firstDay."'))
					)) OR (`orders`.orderID > 0  AND `orders`.status = 1)) ";
		}
		$que .="GROUP BY therapists.therapistID ORDER BY fictive ASC, masterName ";
}
$crooms = udb::key_list($que, 'siteID');

//print_r($crooms);
//echo $que;


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
	if(!$adminAccess){
		if(strtotime($lastDay)> strtotime(date("Y-m-d"))){
			$therapist_lastDay = ($siteData['limit_metaplim'])? date('Y-m-d',strtotime("+".($siteData['limit_metaplim']-1)." days")) : $lastDay;
		}else{
			$therapist_lastDay = $lastDay;
		}
		//$therapist_lastDay = $lastDay;
		$calWhere = " AND orders.therapistID = " . $_CURRENT_USER->id();
		//echo $therapist_lastDay;
	}else{
		$therapist_lastDay = $lastDay;
	}
	$que = "SELECT `orders`.*,`orderUnits`.`unitID`, SUM(`orderPayments`.`sum`) AS `paidTotal`, p_orders.price AS priceTotal,SUM(`health_declare`.negative) AS h_negatives, `health_declare`.declareID, p_orders.sourceID AS p_sourceID
		FROM `orders` 
		LEFT JOIN `orderUnits` USING(`orderID`)
		LEFT JOIN `orderPayments` ON(`orderPayments`.`orderID` = `orders`.parentOrder AND `complete` = 1 AND `subType` NOT IN ('card_test', 'freeze_sum') AND `cancelled` = 0)
		LEFT JOIN `health_declare` ON (`health_declare`.orderID = `orders`.orderID )
		LEFT JOIN `orders` AS p_orders ON (`orders`.parentOrder = `p_orders`.orderID)
		WHERE `orders`.siteID IN (" . $_CURRENT_USER->active_site() . ") AND `orders`.`status`=1 AND ((`orders`.orderID <> `orders`.parentOrder AND `orders`.parentOrder > 0)".($caltype ==2? " OR `orderUnits`.unitID > 0" : "").")
		AND `orders`.timeFrom <= '".$therapist_lastDay." 23:59:59' AND `orders`.timeUntil >= '".$firstDay." 00:00:00'
	".$calWhere." GROUP BY `orders`.orderID";
	//echo $que;
	$calendarOrders = udb::full_list($que);
	//--------- GET TOTAL Treatments
	$countTotalTreats = count($calendarOrders);
}

//---------GET all parent orderID 
$pOrdersIDs = array_map(function($pOrderID) {
    return $pOrderID['parentOrder'];
}, $calendarOrders);

if(count($pOrdersIDs)){
	//-----------GET treatments Count for parents
	$que = "SELECT parentOrder,  COUNT(orderID) AS countTreats
			FROM `orders` 
			WHERE parentOrder IN(".implode(',',$pOrdersIDs).")
			GROUP BY parentOrder";
			//echo $que;
	$countTreats = udb::key_value($que,'parentOrder');

	//-----------SET treatments Count for calendarOrders
	foreach($calendarOrders as $key => $corder){
		$calendarOrders[$key]['countTreats'] = $countTreats[$corder['parentOrder']]-1;
	}
}

//print_r($calendarOrders);

##############################################

//Metaplim / Shifts
$fictiveShifts = array();
$calendarShifts = array();

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
						$fictiveShifts[$unit['masterID']]['status'] = 1;
						if($fromMin2 < $fromMin){
							$fictiveShifts[$unit['masterID']]['bshift'] = $fromMin - $fromMin2;
						}
						if($untilMin2 > $untilMin){
							$fictiveShifts[$unit['masterID']]['ashift'] = $untilMin2 - $untilMin;
						}
						//$fictiveShifts[$unit['masterID']]['bshift'] = 30;
						
					}
				}			
			}
			
		}
	}
	if(!$adminAccess){
		$masters = array($_CURRENT_USER->id());
	}
	$shiftsTable = $workers? '`workShifts`' : '`spaShifts`';
	$que = "SELECT ".$shiftsTable.".* ,therapists.gender_self ,therapists.workerType
		FROM ".$shiftsTable." 
		LEFT JOIN therapists ON (".$shiftsTable.".masterID = therapists.therapistID)
		WHERE ".$shiftsTable.".siteID = '".$cur_sid."' 
		AND timeFrom <= '".$lastDay." 23:59:59' AND timeUntil >= '".$firstDay." 00:00:00'";
		if($masters){
		$que.=" AND masterID IN(".implode(",",$masters).") ORDER BY masterID , timeFrom";
		}
		
	if(!$adminAccess){
		//echo $que;
	}
	
	$calendarShifts = udb::full_list($que);
	//print_r($calendarShifts);

	//Creates fictive breaks and remove locked on daily therapists view
	if($viewtype == 2){
		foreach($calendarShifts as $c_shift){
			if($c_shift['workerType']=='fictive' && $c_shift['status']==0){
				if(substr($c_shift['timeFrom'],-8)== "00:00:00" && substr($c_shift['timeUntil'],-8)== "23:59:59"){
					foreach($crooms as $croom_sid){
						foreach($croom_sid as $key => $thrpst ){
							if($thrpst['masterID'] == $c_shift['masterID'])
								unset($crooms[$cur_sid][$key]);									
						}
					}
									
				}else{

				}
				//unset($fictiveShifts[$c_shift['masterID']]);
			}
		}
	}
}
	//echo $que;



	
	
//print_r($fictiveShifts);
//print_r($calendarOrders);

####################################################################

	

$calendarOrders = array_merge($calendarOrders,$calendarShifts);
$calendarOrders = array_merge($calendarOrders,$fictiveShifts);
//print_r($calendarOrders);

####################################################################

$org_starthour = $starthour;

if(count($calendarOrders)){
	//SET TOTALS
	foreach($calendarOrders as $k => $m){
		if(intval(substr($m["timeFrom"],-8,2)) && intval(substr($m["timeFrom"],-8,2)) < ($starthour-1)){
			$starthour = 	intval(substr($m["timeFrom"],-8,2)) + 1;
		}
		$m[$caltypeName2] = $m[$caltypeName2]?: 0;
		if($m["allDay"]<1 && !($caltype==1 && $m["masterID"]>0)){
			$totals[$m[$caltypeName2]] +=1;
			$totalsTime[$m[$caltypeName2]] += (strtotime($m['timeUntil']) - strtotime($m['timeFrom']))/60;	
			if($m[$caltypeName2]==78){
				$test_cnt++;
				echo "<!--".$test_cnt." - ".$m[$caltypeName2]." - orderID:".$m['orderID']." - parentOID:".$m['parentOrder']." - ".$m." - ".$totals[$m[$caltypeName2]]." - ".$totalsTime[$m[$caltypeName2]]."-->".PHP_EOL;
			}
		}
	}
?>
<!--<?//print_r($calendarOrders);?>-->
<?
}
?>
<!-- starthour <?= $starthour." and ".$org_starthour;?>-->
<?
 

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
			
			if(strtotime($m['timeUntil'])>strtotime($showDays[count($showDays)]. "23:59:59") && $m['allDay']!=1){
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
			$calendarOrders[$k]["width"] = round(((($time2 - $time1) / 3600)/25) * 100);
			$calendarOrders[$k]["right"] = round((date("H",strtotime($m['timeFrom']))/25) *100);
		}
		$calendarOrders[$k]["icon"]= $domain_icon[$m["domainID"]];
		
			
	}
//print_r($calendarOrders);
	#################################################################
}
?>






<?
/************************************* UNITS POP *************************************/	
if($caltype == 2 ){?>
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

<?}
/************************************* END UNITS POP *************************************/

?>

<section id='a_tfusa' class="atfusa <?=$viewtype == 1? "month" : ""?> <?=$classtypes[$caltype]?> <?=$viewtype==2? "dayly" : ""?>">
	<div class="top-buttons-wrap">
		<div class="openbtn" onclick="$('.top-buttons-wrap').toggleClass('open')"></div>
		<div class="top-buttons set1">
			<?/**************************** Calendar Type **************************/?>
			<div class="health_send" style="display:block">
			<?php
				if (count($_CURRENT_USER->sites()) > 1){
					?>    
				<div class="site-select">
					בחר מתחם

				 
							<select title="שם מתחם" onchange="select_site_id(this);">
							
							<?php
							foreach($sname_list as $id => $name) {
								?>
								<option <?=$id == $_CURRENT_USER->active_site()? "selected" : ""?> value="<?php echo $name['siteID'];?>" ><?php echo $name['siteName'];?></option>
								<?php
							}
							?>
						</select>
					
				</div>
				<?php
				 }

				//$_GET["page"] = $_GET["page"] == "shiftscalendar"? "absolute_calendar" : $_GET["page"];
					?>
				<div class="calendar_type2">
					<a href="?page=<?=$_GET["page"]?>&type=0&viewtype=<?=$viewtype?>&date=<?=$_GET["date"]?>&siteid=<?=$_GET["siteid"]?>&weekly=<?=$_GET['weekly']?>" <?=$caltype<1? "class='active'" : ""?>>משמרות</a>
					<a href="?page=<?=$_GET["page"]?>&type=1&viewtype=<?=$viewtype?>&date=<?=$_GET["date"]?>&siteid=<?=$_GET["siteid"]?>&weekly=<?=$_GET['weekly']?>" <?=$caltype==1? "class='active'" : ""?>>לפי מטפלים</a>
					<a href="?page=<?=$_GET["page"]?>&type=2&viewtype=<?=$viewtype?>&date=<?=$_GET["date"]?>&siteid=<?=$_GET["siteid"]?>&weekly=<?=$_GET['weekly']?>" <?=$caltype==2? "class='active'" : ""?>>לפי חדרים</a>
				</div>

				 


			</div>
			<?/**************************** End Calendar Type **************************/?>
			<?/**************************** Date select **************************/?>
			<div class="calendar-wrap">
				<?if($viewtype == 2){?>		
					<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($date."-1 day"))?>&viewtype=<?=$viewtype?>&workers=<?=$workers?>" class="prev">יום קודם</a>
				<?}else if($weekly){?>
					<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($date."-1 week"))?>&viewtype=<?=$viewtype?>&workers=<?=$workers?>&weekly=1" class="prev">שבוע קודם</a>
				<?}else{?>
					<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($dateMonth."-1 month"))?>&viewtype=<?=$viewtype?>&workers=<?=$workers?>" class="prev">חודש קודם</a>
				<?}?>
				
				
				<style>
					#ui-datepicker-div{z-index:9999!important}
				</style>


				<div class="month-select">
					<?if($viewtype==2){?>
					<input type="text" value="" id="monthselect" name="maor" style="height:100%;" onchange="newload_dpicker()" readonly="" class="datepicker" placeholder="תאריך">
					<div>
					<? $todayRev = date('Y-m-d',strtotime($date));?>
					יום <?=$dayName[date('w', strTotime($date))]?> <?=findHoliday($todayRev)?><br>
					<?=date('d', strTotime($date))?> ב<span><?=$monthNames[date('n', strTotime($date))-1]?></span> <?=date('Y', strTotime($date))?>
					</div>
					<?}else{?>
									
						<input type="text" name="month" id="monthselect" style="height:100%;" onchange="newload_dpicker()" class="datepicker" />
						<?php if($_GET['weekly'] == 1) { ?>
							<div><?=date('Y', strTotime($date))?><br>
							<div><span class="top-from"></span> - <span class="top-till"></span></div></div>
						<?php } else { ?>
							<?=$monthNames[date('n', strTotime($date))-1]?><br>
							<?=date('m - Y', strTotime($date))?>
						<?}?>
					<?}?>
					
				</div>
				<?if($viewtype == 2){?>
				<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($date."+1 day"))?>&viewtype=<?=$viewtype?>&workers=<?=$workers?>" class="next">יום הבא</a>
				<?}else if($weekly){?>
				<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($date."+1 week"))?>&viewtype=<?=$viewtype?>&workers=<?=$workers?>&weekly=1" class="next">שבוע הבא</a>		
				<?}else{?>
				<a href="?page=absolute_calendar&type=<?=$caltype?>&date=<?=date("d/m/Y",strtotime($dateMonth."+1 month"))?>&viewtype=<?=$viewtype?>&workers=<?=$workers?>" class="next">חודש הבא</a>
				<?}?>
			</div>		
			<?/**************************** End Date select **************************/?>
		</div>
		<div class="top-buttons set2">
			<div id="viewtype" value="<?=$viewtype?>">			
				<a href="?page=absolute_calendar&type=<?=$caltype?>&viewtype=2&date=<?=$_GET['date']?>&workers=<?=$workers?>" class="<?=$viewtype==2? "active" : ""?>" >תצוגה יומית</a>
				<a href="?page=absolute_calendar&type=<?=$caltype?>&viewtype=0&date=<?=$_GET['date']?>&workers=<?=$workers?>&weekly=1" class="<?=!$viewtype && $weekly ? "active" : ""?>">תצוגה שבועית</a>
				<a href="?page=absolute_calendar&type=<?=$caltype?>&viewtype=0&date=<?=$_GET['date']?>&workers=<?=$workers?>" class="<?=!$viewtype && !$weekly? "active" : ""?>">תצוגת רשימה</a>
				<a href="?page=absolute_calendar&type=<?=$caltype?>&viewtype=1&date=<?=$_GET['date']?>&workers=<?=$workers?>" class="<?=$viewtype==1? "active" : ""?>">תצוגה חודשית</a>
			</div>
			<?if($viewtype!=1){?>
			<div class="settings" id='setview' style='display:<?//=$viewtype == 2? "flex" : "none"?>'>
				<div class="fullscale" onclick="$('#fullscale').toggleClass('on');var _fullscale=($('#fullscale').hasClass('on')? 1 : 0);localStorage.setItem('fullscale', _fullscale);$.post('ajax_global.php',{act: 'fullscale' , fullscale:_fullscale })">
					<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M511.894,19.228c-0.031-0.316-0.09-0.622-0.135-0.933c-0.054-0.377-0.098-0.755-0.172-1.13c-0.071-0.358-0.169-0.705-0.258-1.056c-0.081-0.323-0.152-0.648-0.249-0.968c-0.104-0.345-0.234-0.678-0.355-1.015c-0.115-0.319-0.22-0.641-0.35-0.956c-0.13-0.315-0.284-0.616-0.428-0.923c-0.153-0.324-0.297-0.651-0.467-0.969c-0.158-0.294-0.337-0.574-0.508-0.86c-0.186-0.311-0.362-0.626-0.565-0.93c-0.211-0.316-0.447-0.613-0.674-0.917c-0.19-0.253-0.366-0.513-0.568-0.76c-0.443-0.539-0.909-1.058-1.402-1.551c-0.004-0.004-0.007-0.008-0.011-0.012c-0.004-0.004-0.008-0.006-0.011-0.01c-0.494-0.493-1.012-0.96-1.552-1.403c-0.247-0.203-0.507-0.379-0.761-0.569c-0.303-0.227-0.6-0.462-0.916-0.673c-0.304-0.203-0.619-0.379-0.931-0.565c-0.286-0.171-0.565-0.35-0.859-0.508c-0.318-0.17-0.644-0.314-0.969-0.467c-0.307-0.145-0.609-0.298-0.923-0.429c-0.315-0.13-0.637-0.236-0.957-0.35c-0.337-0.121-0.669-0.25-1.013-0.354c-0.32-0.097-0.646-0.168-0.969-0.249c-0.351-0.089-0.698-0.187-1.055-0.258c-0.375-0.074-0.753-0.119-1.13-0.173c-0.311-0.044-0.617-0.104-0.933-0.135C492.072,0.037,491.37,0,490.667,0H341.333C329.551,0,320,9.551,320,21.333c0,11.782,9.551,21.333,21.333,21.333h97.83L283.582,198.248c-8.331,8.331-8.331,21.839,0,30.17s21.839,8.331,30.17,0L469.333,72.837v97.83c0,11.782,9.551,21.333,21.333,21.333S512,182.449,512,170.667V21.335C512,20.631,511.963,19.928,511.894,19.228z"/><path d="M198.248,283.582L42.667,439.163v-97.83c0-11.782-9.551-21.333-21.333-21.333C9.551,320,0,329.551,0,341.333v149.333c0,0.703,0.037,1.405,0.106,2.105c0.031,0.315,0.09,0.621,0.135,0.933c0.054,0.377,0.098,0.756,0.173,1.13c0.071,0.358,0.169,0.704,0.258,1.055c0.081,0.324,0.152,0.649,0.249,0.969c0.104,0.344,0.233,0.677,0.354,1.013c0.115,0.32,0.22,0.642,0.35,0.957c0.13,0.315,0.284,0.616,0.429,0.923c0.153,0.324,0.297,0.651,0.467,0.969c0.158,0.294,0.337,0.573,0.508,0.859c0.186,0.311,0.362,0.627,0.565,0.931c0.211,0.316,0.446,0.612,0.673,0.916c0.19,0.254,0.366,0.514,0.569,0.761c0.443,0.54,0.91,1.059,1.403,1.552c0.004,0.004,0.006,0.008,0.01,0.011c0.004,0.004,0.008,0.007,0.012,0.011c0.493,0.492,1.012,0.959,1.551,1.402c0.247,0.203,0.507,0.379,0.76,0.568c0.304,0.227,0.601,0.463,0.917,0.674c0.303,0.203,0.618,0.379,0.93,0.565c0.286,0.171,0.565,0.35,0.86,0.508c0.318,0.17,0.645,0.314,0.969,0.467c0.307,0.145,0.609,0.298,0.923,0.428c0.315,0.13,0.636,0.235,0.956,0.35c0.337,0.121,0.67,0.25,1.015,0.355c0.32,0.097,0.645,0.168,0.968,0.249c0.351,0.089,0.698,0.187,1.056,0.258c0.375,0.074,0.753,0.118,1.13,0.172c0.311,0.044,0.618,0.104,0.933,0.135c0.7,0.069,1.402,0.106,2.104,0.106c0,0,0.001,0,0.001,0h149.333c11.782,0,21.333-9.551,21.333-21.333s-9.551-21.333-21.333-21.333h-97.83l155.582-155.582c8.331-8.331,8.331-21.839,0-30.17S206.58,275.251,198.248,283.582z"/></svg>
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 307.22 307.22" style="enable-background:new 0 0 307.22 307.22;" xml:space="preserve"><g><g><path d="M223.944,199.818l61.199-0.021v-34.13l-102.4,0.015c-9.421,0-17.07,7.649-17.07,17.065v102.4h34.135v-61.199 l83.267,83.272l24.136-24.136L223.944,199.818z"/></g></g><g><g><path d="M107.397,22.072l0.015,61.179L24.146,0L0.01,24.136l83.267,83.267l-61.199,0.021v34.115h102.4    c9.4,0,17.07-7.649,17.07-17.065V22.052L107.397,22.072z"/></g></g></svg>
				</div>
				<div class="view-set" onclick="toggleFlip()">סובב תצוגה</div>
				<div class="time-set">
					<div onclick="$('.atfusa').removeClass('wide');set_session('wide',0)">רגיל</div>
					<div id='wideview' onclick="$('.atfusa').addClass('wide');set_session('wide',1)">רחב</div>			
				</div>
			</div>
			<?}?>
		</div>
		<div class="top-buttons set3">
			
			<?/*********************************** UNITS SELECT / Worker Type Select / Total Treats ***************************************/?>
			<div class="set-side">
				<?if($caltype == 2){?>
					<div id="showSelect" onclick="$('#unitSelect').fadeIn('fast')">כל היחידות</div>
				<?}
				if($caltype<1){?>
				<div id="spaShiftsSelect" value="<?=$workers?>">			
					<a href="?page=absolute_calendar&type=<?=$caltype?>&viewtype=<?=$viewtype?>&date=<?=$_GET['date']?>&workers=0&weekly=<?=$weekly?>" class="<?=!$workers? "active" : ""?>" >מטפלים</a>
					<a href="?page=absolute_calendar&type=<?=$caltype?>&viewtype=<?=$viewtype?>&date=<?=$_GET['date']?>&workers=1&weekly=<?=$weekly?>" class="<?=$workers? "active" : ""?>">עובדים</a>
				</div>
				<?}?>
				<div class="totalTreats"><span>סה"כ</span>&nbsp;<?=$countTotalTreats?>&nbsp;<span>טיפולים</span></div>
			</div>
			<?/*********************************** END UNITS SELECT / Worker Type Select ***************************************/?>
			<?/********************************* REMARKS ************************************/?>
			<?
			$daily_remarks = udb::single_value("SELECT remarks FROM daily_calendar_remarks WHERE siteID=".$cur_sid." AND date = '".(implode('-',(explode('/',$date))))."'");
			?>
			<?if($adminAccess){// Condition Removed   && $remarksShort?>
			<div id='daily_remarks_btn' class="daily_remarks  <?=$daily_remarks? "msgs" : ""?>" onclick="$('#remarksPop').fadeIn('fast')">
				<svg height="511pt" viewBox="0 -25 511.99911 511" width="511pt" xmlns="http://www.w3.org/2000/svg"><path d="m504.292969 415.507812c-.496094-.28125-46.433594-26.347656-79.050781-62.386718 23.070312-36.648438 35.210937-78.714844 35.210937-122.394532 0-61.496093-23.949219-119.3125-67.433594-162.796874-43.484375-43.480469-101.300781-67.429688-162.792969-67.429688-61.496093 0-119.3125 23.949219-162.792968 67.429688-43.484375 43.484374-67.433594 101.300781-67.433594 162.796874 0 61.492188 23.949219 119.308594 67.433594 162.792969 43.480468 43.484375 101.296875 67.429688 162.792968 67.429688 39.25 0 77.6875-9.96875 111.75-28.902344 67.128907 37.320313 155.273438 12.277344 159.140626 11.148437 5.839843-1.707031 10.089843-6.746093 10.78125-12.789062.695312-6.046875-2.300782-11.914062-7.605469-14.898438zm-153.925781-13.6875c-4.878907-3.1875-11.160157-3.28125-16.136719-.246093-31.242188 19.0625-67.207031 29.140625-104.003907 29.140625-110.273437 0-199.992187-89.714844-199.992187-199.988282 0-110.277343 89.71875-199.992187 199.992187-199.992187 110.273438 0 199.988282 89.714844 199.988282 199.992187 0 41.382813-12.535156 81.097657-36.257813 114.847657-3.878906 5.519531-3.632812 12.945312.609375 18.191406 18.769532 23.238281 42.988282 43.035156 62.273438 56.886719-30.085938 3.28125-73.347656 2.789062-106.472656-18.832032zm0 0"/><path d="m332.714844 282.808594h-204.976563c-8.351562 0-15.117187 6.769531-15.117187 15.117187 0 8.347657 6.765625 15.117188 15.117187 15.117188h204.976563c8.347656 0 15.117187-6.769531 15.117187-15.117188 0-8.347656-6.769531-15.117187-15.117187-15.117187zm0 0"/><path d="m332.714844 215.609375h-204.976563c-8.351562 0-15.117187 6.769531-15.117187 15.121094 0 8.347656 6.765625 15.117187 15.117187 15.117187h204.976563c8.347656 0 15.117187-6.769531 15.117187-15.117187 0-8.351563-6.769531-15.121094-15.117187-15.121094zm0 0"/><path d="m332.714844 148.414062h-204.976563c-8.351562 0-15.117187 6.769532-15.117187 15.117188 0 8.351562 6.765625 15.117188 15.117187 15.117188h204.976563c8.347656 0 15.117187-6.765626 15.117187-15.117188 0-8.347656-6.769531-15.117188-15.117187-15.117188zm0 0"/></svg>
				<div class="short-remarks"><?=$daily_remarks?></div>
			</div>
			<?}?>
			
			<div class="remarks_pop popup" id="remarksPop">
				<div class="popup_container">
					<div style="display:none" class="close" onclick="submit_remarks()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
					<div style="margin:10px auto;text-align:center;font-size:20px;font-weight:bold">הערות לתאריך <?=implode('/',array_reverse(explode('/',$date)))?></div>
					<textarea id="daily_remarks" style="border: 1px #ccc solid;width: 100%;height: 200px;padding: 10px;font-size: 16px;box-sizing:border-box" name="daily_remarks"><?=($daily_remarks)?></textarea>
					<div id="submit_remarks" onclick="submit_remarks()">שמור</div>
				</div>
			</div>
			<script>
			function submit_remarks(){
				var data = {
					'siteID':<?=$cur_sid?>, 
					'date':'<?=implode('-',(explode('/',$date)))?>',
					'remarks':$('#daily_remarks').val()};
				$.post('ajax_daily_remarks.php',data, function(res){
				});
				if($('#daily_remarks').val())
					$('#daily_remarks_btn').addClass('msgs');
				else
					$('#daily_remarks_btn').removeClass('msgs');

				
				$('#daily_remarks_btn .short-remarks').html($('#daily_remarks').val());
				$('#remarksPop').fadeOut('fast');
			}
			</script>

			<?/*********************************** END REMARKS ***************************************/?>
		</div>
	</div>



	<div class="days-table <?=$roomsShort && $caltype==2? "short-order-disp" : ""?> <?=$therapistShort && $caltype==1? "short-order-disp2" : ""?> <?=$viewtype == 2? "hours" : ""?> <?=$adminAccess ? "" : "blocked"?> <?=$weekly ? "weekly" : ""?> <?=($viewtype==0 && !$weekly)? "list" : ""?>" id="days-table">
		<div class="r-side">
			<div class="top">
				<div class="row month">
					<?
					$flare_pic = (in_array($firstDay,$flareDates[$cur_sid] ?? [])? "<img src='/user/assets/img/hot-sale.png' style='width:20px'>" : "")." ";
					if((!$viewtype && !$weekly) || $viewtype==1)
						$calendarName = $monthNames[date('n', strTotime($date))-1]." ".$curYearDate;
					else if($weekly == 1)
						$calendarName = date("d.m.y", strtotime($firstDay))." - ".date("d.m.y", strtotime($lastDay));
					else
						$calendarName = $flare_pic. $dayName[date('w', strTotime($date))]." - ".date("d.m.y", strtotime($date));
					?>
					<span class="month-label">
						<span class="month-name"><?=$calendarName?></span>
					</span>
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
				<div class="row <?php //=($room['active'] ? '' : 'inactive')?> <?=$room['gender_self']==2? "female" : ($room['gender_self']==3? "nogender" : "")?> <?=$room['fictive']? "fictive" : ""?>"  data-name="<?=($room['unitName'])?>" data-uid="<?=$room[$caltypeName]?>">
					<?//print_r($room);?>
					<?if($viewtype==2 && $caltype==1 && $room['fictive']){?>
						<div  data-test="<?=$room['workStart']." - ".$room['workEnd']?>" class="lock day <?=($room['workStart'] || $room['workEnd'])? "disabled" : ""?>" data-uid="<?=$room[$caltypeName]?>" data-date="<?=date("d/m/Y",strtotime($date))?>"></div>
					<?}?>
					<?if($viewtype==2 && $caltype==1 && $adminAccess){?>
						<div onclick="openNewShift($(this))" class="edit_shifts" data-siteid="<?=$cur_sid?>" data-num="" data-date="<?=date("d/m/Y",strtotime($date))?>" data-uid="<?=$room[$caltypeName]?>" data-name="<?=$room['masterName']?>"></div>
					<?}?>
					<?if($caltype ==2){?>
						<div class="name <?=(mb_strlen($room['unitName']) > 10)? "small" : ""?>"><?=$room['unitName']?></div>
						<div class=" "><?=$sname[$siteID]?></div>
					<?}else{?>						
						<div class="name <?=(mb_strlen($room['masterName']) > 10)? "small" : ""?>"><?=$room['masterName']?></div>
						<div class=" "><?=$sname[$siteID]?></div>						
					<?}?>
					
					<div class="<?=$totals[$room[$caltypeName]]? "" : "nototal"?>" ><?=$totals[$room[$caltypeName]]?: "אין"?> <?=$totalname[$caltype]?> <?//=$viewtype==2? "היום" : "החודש"?>
					<?if($caltype<2){?>
						<?if($totals[$room[$caltypeName]]){?>
							 - <?=floor($totalsTime[$room[$caltypeName]]/60)?>:<?=(round($totalsTime[$room[$caltypeName]]%60))>9? round($totalsTime[$room[$caltypeName]]%60) : "0".round($totalsTime[$room[$caltypeName]]%60)?> ש'
						<?}?>
					
						<?if(!$workers){?>
						<div><?//=$room['gender_self']==2? "מטפלת" : "מטפל"?> <?=$room['gender_client']==3? "" : ($room['gender_client']==2? "נשים בלבד" : "גברים בלבד")?></div>
						<?}?>
					<?}?>
					</div>
					<?if($caltype == 0 && $adminAccess){?>
					<div data-uid="<?=$room[$caltypeName]?>" data-name='<?=$room['masterName']?>' class="weekly_shifts">ערוך שבוע</div>
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
				<div class="day <?=$wday>5? "weekend" : ""?> ">
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
		
		if($viewtype == 2 && $caltype!=2){ $start=8; }else{ $start=1; $starthour=0; }
		for($i=$start; $i<=count($showDays); $i++) {
			$today = $viewtype<2? date('d/m/Y',strtotime($showDays[$i])) : date('d/m/Y',  strtotime($date));
			$todayRev = date('Y-m-d',strtotime($showDays[$i]));?>
				
				<?if($viewtype == 2){?>
				<div class="day <?=$i<$starthour? "hideCell" : ""?>" >
					<div class="top">
						<div class="row">
							<span class="day-name"></span>
							<span class="day-date" ><?=((($i-1)<10)? '0':"").($i-1).':00'?></span>
						</div>
					</div>
				</div>
				<?}else{
					$is_flare=in_array($todayRev,$flareDates[$cur_sid] ?? [])? "flare" : ""; 
					?>
				<div class="day <?=($today==date("d/m/Y"))?"today":""?> <?=(date("w",strtotime($todayRev))>4)?"weekend":""?> <?=$is_flare?>" data-date="<?=$today?>">
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
					<div class="row <?php //=($room['active'] ? '' : 'inactive')?> " data-col="<?=$todayRev?>"  data-row="<?=$room[$caltypeName]?: "0"?>" data-date="<?=date("d/m/Y",strtotime("-1 day",strtotime($curYearDate."-".$curMonthDate."-01")))?>" data-name="<?=outDb($room['masterName'])?>" data-uid="<?=$room['masterID']?>">
						
					</div>
		<?php
				}
			}

?>

				</div>
			</div>
<?php	}

		//if($viewtype == 2 && $caltype!=2) $start=8; else $start=1;
        for($i=$start; $i<=count($showDays); $i++) {
			$today = $viewtype<2? date('d/m/Y',strtotime($showDays[$i])) : date('d/m/Y',  strtotime($date));
			if($viewtype == 2){
				$todayRev = date('Y-m-d '.(($i<=10)? "0" : "").($i-1).':00:00',strtotime($date));
			}else{
				$todayRev = date('Y-m-d',strtotime($showDays[$i]));
			}			
			if($viewtype==2 && $hour[$i]){
				$offhourDiv = $hourClass[$i];
				$offhourDiv2 = $hourClass2[$i];
			}else{
				$offhourDiv = "";
			}
			
?>
			<div  data-i="<?=$i?>" class="day <?=$i<$starthour? "hideCell" :$offhourDiv?> <?=($today==date("d/m/Y"))?"today":""?> <?=(date("w",strtotime($todayRev))>4)?"weekend":""?>" data-date="<?=$today?>" data-hour="<?=$todayRev?>">
			<?php

if($viewtype==2 && $hour[$i]){
	$offhourDiv = "<div class='".$hourClass[$i]."' style='width:".$hour[$i]."%;height:".$hour[$i]."%'></div>";
	$offhourDiv2 = "<div class='".$hourClass2[$i]."' style='width:".$hour2[$i]."%;height:".$hour2[$i]."%'></div>";
}else{
	$offhourDiv = "";
	$offhourDiv2 = "";
}
				?>				
				<?if($viewtype == 1){?><div class="daydate"><div><?=explode("-",$showDays[$i])[2]?></div><span><?=findHoliday($todayRev)?></span></div><?}?>
				<div class="rooms spa <?=$classtypes[$caltype]?>">
					<?=$offhourDiv;?>
					<?//=$offhourDiv2;?>
<?php
            $lockTempFictive = Array();
            foreach($crooms as $siteID => $rlist){
                foreach($rlist as $room) {
?>
					<div ondrop="drop(event)" ondragover="allowDrop(event)" class="a1a1 row <?php //=($room['active'] ? '' : 'inactive')?> <?=$room['gender_self']==2? "female" : ($room['gender_self']==3? "nogender" : "male")?> <?=$room['fictive']? "fictive" : ""?>" data-col="<?=$todayRev?>" data-row="<?=$room[$caltypeName]?: "0"?>" data-date="<?=$today?>" data-name="<?=outDb($room['masterName'])?>" data-uid="<?=$room[$caltypeName]?>" data-num="<?=$roomNumber[$room['unitID']]?>">
<?php
						if($viewtype==0 && $caltype==1 && $room['fictive']){ //Create cell lock on week and list calendar
							if(($room['workStart'] && strtotime($room['workStart'])>strtotime($showDays[$i])) || ($room['workEnd'] && strtotime($room['workEnd'])<strtotime($showDays[$i]))){
								$lockthis="active";
							}else{
								$lockthis="";
							}

?>
							<div class="lock <?=$lockthis?> <?=($room['workStart'] || $room['workEnd'])? "disabled" : ""?>"></div>
<?php
						}
						/*
						for($slot=0;$slot<4;$slot++){?>
							<div class="slot" data-slot="<?=$slot?>"></div>
						<?}*/


?>
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

<style>
<?if($hidefaces){?>
.atfusa .days-table .l-side .rooms .row .order .gender{display:none}
<?}?>
<?if($groupTreat){?>
.atfusa .days-table .l-side .rooms .row .order.spa.tGroup {background: linear-gradient(0deg, #8591d3, #dbdbf4)}
.atfusa .days-table .l-side .rooms .row .order.spa.lockedTherapist{background:linear-gradient(0deg, #f5c064, #fcf2e2) !important}
.atfusa .days-table .l-side .rooms .row .order.spa:not(.tGroup):not(.lockedTherapist){background:linear-gradient(0deg, #c1deaa, #e2f4db) !important;}
<?}?>
</style>
<?
if($viewtype < 2){
	$scrollmonth = (date("m",strtotime($date)) == date("m"))? date("d",strtotime($date)) : 0;
}else{
	$scrollmonth = 10;
}
?>
<?//print_r($calendarOrders);?>

<script src="assets/js/absolute_tfusa.js?v=<?=time()?>"></script>


<script src="assets/js/absolute_tfusa2.js?v=<?=time()?>"></script>



<?php
if($_GET['date']) {
	$date = $_GET['date'];
	$date = str_replace('/', '-', $date);
}

$que = "SELECT 
		therapists.therapistID 
		,therapists.siteName 
		FROM therapists 
		WHERE therapists.siteID = '".$cur_sid."' AND active = 1 AND deleted < 1 ";
		
$therapistList = udb::full_list($que);
//print_r($therapistList);

$que = "SELECT `rooms_units`.`unitID`,`rooms_units`.`unitName`
			FROM `rooms_units`
			INNER JOIN `rooms_domains` ON (`rooms_domains`.`roomID` = `rooms_units`.`roomID` and rooms_domains.domainID=1)  
			INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
			WHERE `rooms`.`siteID` = '".$cur_sid."' AND  rooms_domains.active=1 AND rooms_units.hasTreatments =1 AND rooms.active > 0
			ORDER BY rooms.showOrder"; //echo $que;

$roomList = udb::full_list($que);



####################################################################
//SET EXTRAS TEXT and Comments FOR PARENTS

$que = "SELECT extraID , extraName
		FROM `sites_treatment_extras` AS `s` 
		INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
		WHERE s.siteID IN (" . $_CURRENT_USER->sites(true) . ") AND included = 0
		ORDER BY e.showOrder";


$extras = udb::key_value($que, ['extraID']);


$prentExt = [];
$prentCmnts = [];

foreach($calendarOrders as $corder)
	if($corder['parentOrder']) 
		$parentOrders[] =$corder['parentOrder'];


if($parentOrders){
	$parentOrders = array_unique($parentOrders);
	//print_r($parentOrders);

	$que = "SELECT extras , orderID, comments_owner FROM orders WHERE orderID IN (".implode(",",$parentOrders).")";
	$parentsInfo = udb::full_list($que);

	foreach($parentsInfo as  $PE){
		$parentsExtras[$PE['orderID']] = $PE['extras'] ? json_decode($PE['extras'], true) : [];
		$prentCmnts[$PE['orderID']] = $PE['comments_owner'];
	}
	//print_r($parentsExtras);
	if($parentsExtras){
		foreach($parentsExtras as $key => $PE2){
			$temp = [];
			if($PE2['extras']){
				foreach($PE2['extras'] as $PE2extras){
					//print_r($PE2extras).PHP_EOL."-<br>--".PHP_EOL;
					if($extras[$PE2extras['extraID']]){
						$temp[] = $PE2extras['count']." x ".$extras[$PE2extras['extraID']];
					}
				}
				$prentExt[$key] = implode(', ',$temp);
			}
			

		}
	}
}
//print_r($prentCmnts);


//SET EXTRAS TEXT and Comments FOR PARENTS
#####################################################################
?>
<script>
var redline = <?=$redline?>;
const pExtras = <?=json_encode($prentExt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
const pComments = <?=json_encode($prentCmnts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
var maindir = '<?=$dir=='rtl'? 'right' : 'left'?>';
</script>


<?php if($viewtype == 2) { ?>
<script>



//	$.datepicker.setDefaults( $.datepicker.regional[ "he" ] );


if($( ".datepicker" ).length){
    $( ".datepicker" ).datepicker({
		afterShow: function (dates) {
			getFlames(dates);
		},
        minDate: <?=$_GET['date']?"'".date('d/m/Y', strtotime($date.' -2 year'))."'":"'".date('d/m/Y', strtotime('-2 year'))."'"?>,
        defaultDate: <?="'".date('d/m/Y')."'"?>,
        maxDate: <?=$_GET['date']?"'".date('d/m/Y', strtotime($date.' +1 year'))."'":"'".date('d/m/Y', strtotime('+1 year'))."'"?>
    });
}
$(function() {
	$(".datepicker").datepicker("setDate", <?=$_GET['date']?"'".$_GET['date']."'":"'".date('d/m/Y')."'"?>);
})
	</script>

<?php } ?>


<?php if($viewtype != 2) { ?>
				<script type="text/javascript">
//	$.datepicker.setDefaults( $.datepicker.regional[ "he" ] );

	if($( ".datepicker" ).length){
 
    $(".datepicker").datepicker({
		afterShow: function (dates) {
			getFlames(dates);
		},
        minDate: <?=$_GET['date']?"'".date('d/m/Y', strtotime($date.' -2 year'))."'":"'".date('d/m/Y', strtotime('-2 year'))."'"?>,
        defaultDate: <?="'".date('d/m/Y')."'"?>,
        maxDate: <?=$_GET['date']?"'".date('d/m/Y', strtotime($date.' +2 year'))."'":"'".date('d/m/Y', strtotime('+2 year'))."'"?>,
        dateFormat: 'dd/mm/yy',
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,

        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            <?php if($_GET['weekly'] != 1) { ?>$(this).val($.datepicker.formatDate('01/mm/yy', new Date(year, month, 1)));<?php } ?>
			newload_dpicker();
        }
    });

    $(".datepicker").focus(function () {
        <?php if($_GET['weekly'] != 1) { ?>$(".ui-datepicker-calendar").hide();<?php } ?>
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
	$('#days').css("<?=$dir=='rtl'? 'right' : 'left'?>",Math.abs(this.scrollLeft) + "px");
});

therapists = [];
<?	foreach($therapistList as $ther){?>
therapists[<?=$ther['therapistID']?>] = '<?=str_replace("'","%27",$ther['siteName'])?>';
<?}	?>

roomsNames = [];
<?	foreach($roomList as $room){?>
roomsNames[<?=$room['unitID']?>] = '<?=str_replace("'","%27",$room['unitName'])?>';
<?}	?>

var currentMonth = '<?=date("m/Y",strtotime($date))?>';
var endMonthDay = '<?=$endMonthDay?>';
var passMonthYear = '<?=date("t/m/Y",strtotime("first day of last month",strtotime($date)))?>';
var tfusaDate = '<?=date("d/m/Y",strtotime($date))?>';
var weekly = <?=$_GET['weekly']? 1 : 0?>;

var viewtype = <?=$viewtype ?>;
var caltype = <?=$caltype ?>;
var scrollmonth = <?=$weekly? date('w')+1 : $scrollmonth?>;



var workers = <?=$workers?>

$(function(){
	<?php
	if(count($calendarOrders)){
	foreach($calendarOrders as $cOrder){
		if(!$adminAccess) $cOrder['price']='';
		if($cOrder['masterID'] && $caltype == 1){ //masterID indicates it's a shift OR locked day
			if(substr($cOrder['timeFrom'],-8)== "00:00:00" && substr($cOrder['timeUntil'],-8)== "23:59:59"){?>
				addLocked(<?= json_encode($cOrder)?>);
			<?}else{?>
				addShift_inOrders(<?= json_encode($cOrder)?>);			
			<?}?>
		<?}else {?>
			addOrder(<?= json_encode($cOrder);?>);
		<?php }
		}
	}?>

<?if($weekly && !$viewtype){?>
$('.top-from').html($('#days>.day:first-child').attr('data-date').slice(0, -5).replace('/', '.'))
$('.top-till').html($('#days>.day:last-child').attr('data-date').slice(0, -5).replace('/', '.'));
<?}?>

setclientview();
setTimeout(function(){scrollTableNew();},400);
});
        
function select_site_id(ele) {
	var page = "absolute_calendar";
	var the_val = $(ele).val();
	window.location='?page=absolute_calendar&type=' + <?=$caltype?> +'&viewtype='+ viewtype +'&date='+ tfusaDate +'&asite='+ the_val+'&weekly='+ weekly;

}
        
function newload(){		
	window.location='index.php?page=absolute_calendar&type=' + <?=$caltype?> +'&viewtype='+ $('#viewtype').val() +'&date='+ $('#monthselect').val()+'&asite=<?=$cur_sid?>' ;
}


function newload_dpicker(){		
let _url = 'index.php?page=absolute_calendar&type=' + <?=$caltype?> +'&viewtype='+ parseInt($('#viewtype').attr('value')) +'&date='+ $('#monthselect').val()+'&asite=<?=$cur_sid?>&maor=1';
<?php if($_GET['weekly'] == 1) { ?>_url+= '&weekly=1';<?php } ?>
window.location=_url ;
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
	set_session('flipped',1);
}else{		
	var size2 = 'width';
	var margin2 = '<?=$dir=='rtl'? 'right' : 'left'?>';
	var setwidth = false;	
	set_session('flipped',0);
}

	$('.shift , .shift_idan, .order').each(function(){
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
	/*$.post("ajax_tfusa_view.php", {
        "type": type,
        "value": tvalue
    });*/
	localStorage.setItem(type, tvalue);
}        
</script>


<script>
function setclientview(){
	//debugger;
	if($('#setview').length){
		
		if(parseInt(localStorage.getItem('flipped'))>0){
			toggleFlip();
		}

		if(parseInt(localStorage.getItem('wide'))>0){
			$('.atfusa').addClass('wide');
		}
	}
}

</script>

<? 
$que='SELECT * FROM `treatments` WHERE 1';
$treatments = udb::key_list($que, 'treatmentID');
?>
<script>
const treatments = [];
<?foreach($treatments as $key => $treatment){?>
	treatments[<?=$key?>]="<?=$treatment[0]['treatmentName']?>";
<?}?>
</script>

<script>

var dragging = 0;
var moved = 0;
var dx;
var dy;

const ele = document.getElementById('divToScroll');

    let pos = { top: 0, left: 0, x: 0, y: 0 };

	var addfix;
	document.getElementById('days-table').addEventListener('touchmove', function(e) {
	//debugger;
    if(!addfix){
		$('#days-table').addClass('fix_bug');//Weired bug not showing all columns when scrolling left		
		setTimeout(function(){$('#days-table').removeClass('fix_bug');addfix = 0},1000)
		addfix = 1;
	}
	
	
    
}, false);

$('#divToScroll , #days-table').mousedown(function(e) {
		var test = 0
        //ele.style.cursor = 'grabbing';
        //ele.style.userSelect = 'none';

        pos = {
            left: $('#divToScroll').scrollLeft(),
            top: $('#divToScroll').scrollTop(),
            // Get the current mouse position
            x: e.clientX,
            y: e.clientY,
        };
		pos2 = {
            left: $('#days-table').scrollLeft(),
            top: $('#days-table').scrollTop(),
            // Get the current mouse position
            x: e.clientX,
            y: e.clientY,
        };

	if($(e.target).hasClass('c_slots')){
		choose_slot(e)
	}else if(!$('.create_order').length){
		$(document).mousemove(function(dd){
			test++;
			
			dragging = 1;
			console.log(dragging);
			// How far the mouse has been moved
			 dx = dd.clientX - pos.x;
			 dy = dd.clientY - pos.y;
			$('#test').html(dx + " - " + dy)
					
			// Scroll the element
			$('#divToScroll').scrollLeft( pos.left - dx);
			$('#divToScroll').scrollTop( pos.top - dy);

			$('#days-table').scrollLeft( pos2.left - dx);
			$('#days-table').scrollTop( pos2.top - dy);
		});
	}
});

$(document).mouseup(function(){
    $(document).unbind('mousemove');
	if(Math.abs(dx) < 10 && Math.abs(dy)<10){
		dragging = 0;
	}
    setTimeout(function(){dragging = 0;},500);
	//console.log(dragging);
});

$('.class').click(function() {
    if (dragging == 0){
       // default behaviour goes here
    }
    else return false;
})



</script>
<div id="test" style='display:none;background:white;padding:10px;position:fixed;top:0;left:0'></div>