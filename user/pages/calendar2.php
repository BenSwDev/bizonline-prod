<?php
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

/*	$endMonthDay = date("t",date(strtotime($date)));
	$curMonthDate = date("m",strtotime($date));
	$curYearDate = date("Y",strtotime($date));
*/
	$dayNameShort = array ("א","ב","ג","ד","ה","ו","ש");	
	$dayName = array ("ראשון","שני","שלישי","רביעי","חמישי","שישי","שבת");

    $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");

	$que = "SELECT `orders`.*, `orderUnits`.`unitID`
	FROM `orders` 
	INNER JOIN `orderUnits` USING(`orderID`)
	WHERE siteID IN (" . $_CURRENT_USER->sites(true) . ") AND `orders`.`status`=1
	AND (
	(timeFrom >= '".$date." 00:00:00' AND timeFrom <= '".$date." 23:59:59') OR
	(timeFrom < '".$date." 00:00:00' AND timeUntil > '".$date." 23:59:59') OR
	(timeUntil >= '".$date." 00:00:00' AND timeUntil <= '".$date." 23:59:59'))
	";	
	$dayOrders = udb::full_list($que);

    $que = "SELECT `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`, rooms_domains.active, rooms.siteID, rooms.roomID
                FROM `rooms_units`
                INNER JOIN `rooms_domains` ON (`rooms_domains`.`roomID` = `rooms_units`.`roomID` and rooms_domains.domainID=1)  
                INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
                WHERE `rooms`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ") AND  rooms_domains.active=1
                ORDER BY `rooms`.`siteID` ASC,rooms.showOrder ASC";
    $crooms = udb::key_list($que, 'siteID');

    ksort($crooms, SORT_NUMERIC);
?>

<section class="tfusa">
	<div class="title">יומן תפוסה</div>
	<div class="top-buttons">
		<a href="?page=calendar2&date=<?=date("d/m/Y",strtotime("-1 day",strtotime($date)))?>" class="prev">יום קודם</a>
		<div class="month-select" style="line-height:40px">
			<select id="monthselect" onchange="newload()">
				<?
				
				for($i= -30;$i<=60;$i++){?>
				<option value="<?=$i!=0? date('d/m/Y', strtotime($i." day", strtotime($date))): date('d/m/Y');?>" <?=$i==0? "selected" : ""?>>
					<?=date('d.m.y', strtotime($i." day", strtotime($date)));?>
				</option>
			<?}?>
			</select>			
			<?=date('d.m.y', strTotime($date))?>
		</div>
		<a href="?page=calendar2&date=<?=date("d/m/Y",strtotime("+1 day",strtotime($date)))?>" class="next">יום הבא</a>
		<select id="monthview" onchange="newload()">
			<option value="0" >תצוגת רשימה</option>	
			<option value="1" >תצוגה חודשית</option>	
			<option value="2" selected>תצוגה יומית</option>	
		</select>
		<div id="showSelect" onclick="$('#unitSelect').fadeIn('fast')">כל היחידות</div>
		<div class="clicks">
			<!-- <div class="short-click">לחיצה קצרה - סימון פנוי תפוס</div>
			<div class="long-click">לחיצה ארוכה - פתיחת הזמנה</div> -->
		</div>
	</div>

	<div class="days-table" id="days-table">
		<div class="r-side">
			<div class="top">
				<div class="row month">
					<span class="month-label">
						<span class="dayName"><?=$dayName[date('w', strtotime($date))]?></span>
						<span class="month-name"><?=date("d/m/Y",strtotime($date))?></span>
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
?>
				<div class="row" data-name="<?=outDb($room['unitName'])?>" data-uid="<?=$room['unitID']?>">
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
				<div class="day last-month">
					<div class="top">
						<div class="row">
							<div class="title">הזמנות מיום קודם</div>
						</div>
					</div>
				</div>
				<?    for($i=0; $i<=23; $i++) {?>
				<div class="day">
					<div class="top">
						<div class="row">
							<span class="day-name"></span>
							<span class="day-date" ><?=($i<10?'0'.$i:$i).':00'?></span>
						</div>
					</div>
				</div>
				<?}?>
				
			</div>

			<div class="day last-month">
					<div class="rooms">

	<?php
		foreach($crooms as $siteID => $rlist){
			foreach($rlist as $room) {
	?>
						<div class="row" data-hour="x" data-name="<?=outDb($room['unitName'])?>" data-uid="<?=$room['unitID']?>"></div>
	<?php
			}
		}
	?>

					</div>
				</div>
<?php
    for($i=0; $i<=23; $i++) {
?>
			<div class="day" data-hour="<?=($i<10?'0'.$i:$i).':00'?>">
				
			
				<div class="rooms">
<?php
        foreach($crooms as $siteID => $rlist){
            foreach($rlist as $room) {
?>
					<div class="row" data-hour="<?=($i<10?'0'.$i:$i).':00'?>" data-name="<?=outDb($room['unitName'])?>" data-uid="<?=$room['unitID']?>">
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
<style>
.r-side .top{position:sticky;top:0;z-index:11}
.tfusa .days-table{max-height:calc(100vh - 176px);overflow:auto}
.tfusa .days-table .l-side #days{position:sticky;top:0;z-index:10}
.month-select{width:20%;max-width:200px;min-width:60px;border:1px green solid;border-radius:5px;background:white;display:inline-block;height:50px}
#monthselect{width:100%;height:50px;margin:-1px 0;position:absolute;opacity:0;font-size:18px;top:0;right:0}

</style>
<script src="assets/js/tfusa.js?v=<?=time()?>"></script> 
<script type="text/javascript">

$('#days-table').scroll(function(){
	//debugger;
	$('#days').css("top",this.scrollTop + "px");
});

var domain = new Array();
<?php
foreach($domain_icon as $key => $icon){?>
domain[<?=$key?>] = '<?=$icon?>';
<?}?>
	var currentDay = '<?=implode('/',array_reverse(explode('-',date("d/m/Y",strtotime($date)))))?>';

    $(function(){
<?php
if($dayOrders){
    foreach($dayOrders as $d){
        $dayOrder = array_map('quot', $d);
?>
    orderFormDay = (new addDayOrder({
        orderID:<?=$dayOrder['orderID']?>,
        orderType:"<?=$dayOrder['orderType']?>",
        orderDate:"<?=implode('/',array_reverse(explode('-',substr($dayOrder['timeFrom'],0,10))))?>",
        endDate:"<?=implode('/',array_reverse(explode('-',substr($dayOrder['timeUntil'],0,10))))?>",
        startTime:"<?=substr($dayOrder['timeFrom'],11,5)?>",
        endTime:"<?=substr($dayOrder['timeUntil'],11,5)?>",
        roomID:<?=$dayOrder['unitID']?>,
        name:"<?=$dayOrder['customerName']?>",
        phone:"<?=$dayOrder['customerPhone']?>",
        price:"<?=$dayOrder['price']?>",
        allDay:<?=$dayOrder['allDay']?>,
        approved:<?=$dayOrder['approved']?>,
        orderIDBySite:<?=$dayOrder['orderIDBySite']?>,
        domainIcon:<?=$dayOrder['domainID']?>,
        guid:"<?=$dayOrder['guid']?$dayOrder['guid']:0?>",
        showOrders: true
    })).init();
<?php
    }
}
?>
    });

function newload(){
		var page;
		if($('#monthview').val() ==2){
			page = "calendar2";
		}else if($('#monthview').val() ==0){
			page= "calendarnew";
		}else{
			page= "calendarnew&monthView=1";
		}
		window.location='index.php?page=' + page + '&date='+ $('#monthselect').val();
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
