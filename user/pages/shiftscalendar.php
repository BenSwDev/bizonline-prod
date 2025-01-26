<?php
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
    $que = "SELECT 
            therapistID AS masterID
            ,siteID
            ,active
            ,siteName AS masterName 
            FROM therapists WHERE siteID = '".$cur_sid."' ";
    
    $crooms = udb::key_list($que, 'siteID');
    ksort($crooms, SORT_NUMERIC);  
	foreach($crooms as $siteID => $rlist){
        foreach($rlist as $room) {
			$masters[] = $room['masterID'];
		}
	}

	$que = "SELECT `spaShifts`.*
        ,COUNT(*) AS counter   
        ,DATE_FORMAT(timeFrom,'%d/%m/%Y') AS date_str_from
        ,GROUP_CONCAT(DATE_FORMAT(timeFrom,'%H:%i'),'-',DATE_FORMAT(timeUntil,'%H:%i') SEPARATOR ', ') AS time_list_from
        ,GROUP_CONCAT(orderID SEPARATOR ', ') AS orderID_list
	FROM `spaShifts` 
	WHERE siteID = '".$cur_sid."' AND `spaShifts`.`status`=1
	    AND timeFrom <= '".$lastDay." 23:59:59' AND timeUntil >= '".$firstDay." 00:00:00'
		AND masterID IN(".implode(",",$masters).")
        GROUP BY DATE_FORMAT(timeFrom,'%d/%m/%Y'), masterID       
	";
	//echo $que;
	$monthOrders = udb::full_list($que);

        #$sname = udb::key_value("SELECT `masterID`, `masterName` FROM `masters` WHERE `siteID` = '".$cur_sid."' ");

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



    /*
    $que = "SELECT `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`, rooms_domains.active, rooms.siteID, rooms.roomID
                FROM `rooms_units`
                INNER JOIN `rooms_domains` ON (`rooms_domains`.`roomID` = `rooms_units`.`roomID` and rooms_domains.domainID=1)  
                INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
                WHERE `rooms`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ") AND  rooms_domains.active=1
                ORDER BY rooms.showOrder";
    */
    
    /*
    $que = "SELECT 
            masterID
            ,siteID
            ,active
            ,masterName 
            FROM masters WHERE siteID = '".$cur_sid."' ";
    */
    


	//print_r($crooms); 
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

setclanedarNav(3);

	?>
     


</div>

<section class="tfusa <?=$monthView? "month" : ""?>">
	<div class="title">יומן משמרות</div>
	<div class="top-buttons">
		<a href="?page=calendarnew&date=<?=date("d/m/Y",strtotime("-1 month",strtotime($dateMonth)))?>&monthView=<?=$monthView?>" class="prev">חודש קודם</a>
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
		<a href="?page=calendarnew&date=<?=date("d/m/Y",strtotime("+1 month",strtotime($dateMonth)))?>&monthView=<?=$monthView?>" class="next">חודש הבא</a>
		<?php
                if(!$monthView && 1==2){
                ?>
		<div class="clicks">
			<div class="short-click">לחיצה קצרה - סימון פנוי תפוס</div>
			<div class="long-click">לחיצה ארוכה - פתיחת הזמנה</div>
		</div>
		<?php 
                    }
                ?>
		
		<select id="monthview" onchange="newload()">
			<option value="0" <?=$monthView? "" : "selected"?>>תצוגת רשימה</option>
			<option value="1" <?=$monthView? "selected" : ""?>>תצוגה חודשית</option>
			<option value="2" >תצוגה יומית</option>
		</select>
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
			<div class="rooms shifts">
<?php
    foreach($crooms as $siteID => $rlist){
        foreach($rlist as $room) {
?>
				<div class="row<?=($room['active'] ? '' : ' inactive')?> flex_idan_mid" data-name="<?=outDb($room['masterName'])?>" data-uid="<?=$room['masterID']?>">
					<div class="name"><?=outDb($room['masterName'])?> (<?=$room['masterID']?>)</div>
                                        
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
					<a href="index.php?page=shiftscalendar&date=<?=date("d/m/Y",strtotime($todayRev))?>" class="top">
						<div class="row">
							<div class="special_date"><?=findHoliday($todayRev)?></div>
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
				<div class="rooms shifts">
<?php

			foreach($crooms as $siteID => $rlist){
				foreach($rlist as $room) {
		?>
					<div class="row<?=($room['active'] ? '' : ' inactive')?>" data-date="<?=date("d/m/Y",strtotime("-1 day",strtotime($curYearDate."-".$curMonthDate."-01")))?>" data-name="<?=outDb($room['masterName'])?>" data-uid="<?=$room['masterID']?>"></div>
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
				<div class="rooms shifts">
<?php
            foreach($crooms as $siteID => $rlist){
                foreach($rlist as $room) {
                    ################################# חפש אם יש משמרת פה
                    $shift_id = 0;
                    
                    foreach ($monthOrders as $monthOrders_vals) {
                        if ($monthOrders_vals['masterID'] == $room['masterID']) {
                            if ($today == $monthOrders_vals['date_str_from']) {
                                $shift_id = $monthOrders_vals['orderID_list'];
                            }
                        }
                    }
                    
                    #################################
                
                    
                
?>
					<div class="a1a1 row<?=($room['active'] ? '' : ' inactive')?>" data-date="<?=$today?>" data-name="<?=outDb($room['masterName'])?>" data-uid="<?=$room['masterID']?>" data-num="<?=$shift_id?>">

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
.r-side .top{position:sticky;top:0;z-index:11}
.tfusa .days-table{max-height:calc(100vh - 176px);overflow:auto}
.tfusa .days-table .l-side #days{position:sticky;top:0;z-index:10}
.month-select{width:20%;max-width:200px;min-width:60px;border:1px green solid;border-radius:5px;background:white;display:inline-block;height:50px}
#monthselect{width:100%;height:50px;margin:-1px 0;position:absolute;opacity:0;font-size:18px;top:0;right:0}


.tfusa.month .days-table .rooms .row {height: 16px;padding: 1px;}
.tfusa.month .days-table .r-side{display:none}
.tfusa.month .days-table .l-side{width:100%;margin:0}
.tfusa.month .days-table .l-side .rooms .row .order{height:14px;overflow:hidden;margin:0}
.tfusa.month .days-table .l-side .rooms .row::before{height:14px;width:14px;line-height:14px;font-size:10px;content:attr(data-num);color:#bbb}
.tfusa.month .days-table .l-side .rooms .row.busy::before{color:white}
.tfusa.month .days-table .l-side .day { width: calc(100% / 7); border: 1px transparent solid; border-top-color: #b0c7c9; border-left-color: #b0c7c9;box-sizing:border-box }
.tfusa.month .days-table .l-side .days{overflow:hidden}
.tfusa.month .days-table .l-side .rooms .row{border:0}
.tfusa.month .days-table .l-side .daydate{background:white}
.tfusa.month .days-table .l-side .daydate > div {font-size: 16px;width: 30px;font-weight: bold;display: inline-block;height: 30px;line-height: 30px;}
.tfusa.month .days-table .l-side .daydate span {font-size: 10px;line-height: 1;width: calc(100% - 30px);font-weight: normal;display: inline-block;text-align: right;height: 30px;vertical-align: middle;white-space: initial;}
.tfusa.month .days-table .l-side .daydate span div {display: table-cell;vertical-align: middle;height: 30px;}
.tfusa.month .days-table .l-side .top .row .day-name {padding: 10px 0;}
.tfusa.month .days-table .l-side .top .row {height: 50px;}
.tfusa.month .days-table .l-side .rooms .row .order *:not(.show) {display: none !important;}
.tfusa.month .days-table .l-side .rooms .row .order::after {display: none !important;}
.tfusa.month .days-table .l-side .rooms .row .order .roomNum { position: absolute; top: 0; font-size: 10px; line-height: 14px; right: 0; width: 16px; text-align: center; }
.tfusa.month .days-table .l-side .rooms .row .order .roomName { position: relative; margin: -4px; line-height: 14px; font-size: 8px; text-align: right; padding-right: 14px; color: rgba(255,255,255,0.8); }

@media(min-width:1000px){
.tfusa.month .days-table .l-side .rooms .row .order .roomName{font-size:12px;line-height:12px}

}
.flex_idan_mid{
    display: flex;
  align-items: center;
  justify-content: center;
}

.tfusa .days-table .l-side .rooms .row .shift_idan {
    cursor: pointer;
    height: 85px;
    background: #0dabb6;
    border-radius: 8px;
    color: #fff;
    padding: 5px;
    position: absolute;
    right: 0;
    box-sizing: border-box;
    margin: 5px 0;
    font-size: 12px;
    display: inline-block;
    z-index: 5;
    overflow: hidden;
    display: flex;
  align-items: center;
  justify-content: center;
}
.text_top{
    font-size: 20px;
    overflow: hidden;
    text-align: right;
    margin-right: 1%;
    margin-bottom: 10px;
}
.text_bottom{
    text-align:right;
    margin-right: 1%;
    margin-bottom: 5px;
}
.text_bottom_but{
    font-size: 16px;
    line-height: 40px;
    border: 1px solid black;
    border-radius: 30px;
    padding-right: 10px;
    padding-left: 10px;
    background: white;
    display: inline-block;
    margin-top: 5px;
    margin-bottom: 10px;
    cursor: pointer;    
}
.the_inline{
    display: inline-block;
}
.the_res{
    position: relative;
}
.the_remove_but{
        position: absolute;
    top: -10px;
    left: -5px;
    z-index: 1;
    cursor: pointer;
}
.the_remove_but svg{
    fill:#e73219;
}
.tfusa .days-table .l-side .rooms .row::before {
    background: transparent;
}

</style>



<script src="assets/js/tfusa_shifts.js?v=<?=time()?>"></script>
<script type="text/javascript">
var domain = new Array();
<?php
foreach($domain_icon as $key => $icon){?>
domain[<?=$key?>] = '<?=$icon?>';
<?}?>

$('#days-table').scroll(function(){
	$('#days').css("top",this.scrollTop + "px");
});

		var currentMonth = '<?=date("m/Y",strtotime($date))?>';
		var endMonthDay = '<?=$endMonthDay?>';
		var passMonthYear = '<?=date("t/m/Y",strtotime("first day of last month",strtotime($date)))?>';

		var monthview = <?=$monthView? "1" :"0"?>;
		var scrollmonth = <?=(date("m",strtotime($date)) == date("m"))? date("d",strtotime($date)) : 0?>

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
            if (count($crooms) > 0) {
			foreach($crooms[$month['siteID']] as $unit){
				if($unit["masterID"] == $month['masterID']) $roomName = $unit['masterName'];
			}
?>
			orderFormMonth = (new addMonth<?=$monthView? "View" : ""?>Order({
            orderID:<?=$month['orderID']?>,
            orderType:"<?=$month['orderType']?>",
            orderDate:"<?=implode('/',array_reverse(explode('-',substr($month['timeFrom'],0,10))))?>",
            endDate:"<?=implode('/',array_reverse(explode('-',substr($month['timeUntil'],0,10))))?>",
            startTime:"<?=substr($month['timeFrom'],11,5)?>",
            endTime:"<?=substr(($month['endDate']?: $month['timeUntil']),11,5)?>",
            roomID:"<?=$month['masterID']?>",
            roomName:"<?=str_replace('"','\"',$roomName)?>",
            roomNum:"<?=$roomNumber[$month['unitID']]?>",
            name:"<?=$month['customerName']?>",
            phone:"<?=$month['customerPhone']?>",
            price:"<?=$month['price']?>",
            allDay:"",
            approved:<?=($month['approved'] | $month['adminApproved'])?>,
            orderIDBySite:"",
            domainIcon:"",
            guid:"<?=$month['guid']?$month['guid']:0?>",
            showOrders: true,
            counter:"<?=$m["counter"]?>",
            time_list: "<?=$m["time_list_from"]?>"
        })).init();
<?php
        }
        }
        
    }
?>
    });
        
        function select_site_id(ele) {
            var page = "shiftscalendar";
            var the_val = $(ele).val();
            window.location='index.php?page=' + page + '&monthView='+ $('#monthview').val() +'&date='+ $('#monthselect').val()+'&siteid='+ the_val;
    
        }
        
	function newload(){
		var page;
		if($('#monthview').val() ==2){
			page = "shiftscalendar";
		}else{
			page= "shiftscalendar";
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
        
</script>
