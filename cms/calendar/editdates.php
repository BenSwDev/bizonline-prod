<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";
require_once "../classes/class.PriceCache.php";

function zeropad($num, $len = 2){
	return str_pad(intval($num), $len, '0', STR_PAD_LEFT);
}

$siteID  = intval($_GET['siteID']);
$roomID  = intval($_GET['roomID']);
$frameID = intval($_GET['frame']);
$date    = $_GET['date'];

if('POST' == $_SERVER['REQUEST_METHOD'])
{
	$error = '';
	$data  = array();

	foreach($_POST as $key => $val){
		switch($key){
			case 'siteID':
			case 'roomID':
			case 'free':
				$data[$key] = intval($val);
				break;
				
			case 'fromDate':
			case 'toDate':
				if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $val))
					$data[$key] = implode('-', array_reverse(array_map('zeropad', explode('/', $val))));
				break;
		}
	}
	
	if (!$data['siteID'] || !$data['roomID'])
		$error = 'Insufficient data';
	elseif (!$data['fromDate'] || !$data['toDate'] || $data['toDate'] <= $data['fromDate'])
		$error = 'Illegal dates';
	elseif ($data['free'] > 0){
		$que = "SELECT `roomCount` FROM `sitesRooms` WHERE `siteID` = " . $data['siteID'] . " AND `roomID` = " . $data['roomID'];
		$rc  = udb::single_value($que);
		
		if (!$rc || $data['free'] > $rc)
			$error = 'Wrong num of rooms';
	}
	
	if (!$error){
		$from  = strtotime($data['fromDate'] . ' 09:00:00');
		$till  = strtotime($data['toDate'] . ' 06:00:00');
		$dates = array();
		
		for($from; $from < $till; $from += 24 * 3600)
			$dates[] = date('Y-m-d', $from);
		
		$tname = 'dtt' . mt_rand();
		$que = "CREATE TEMPORARY TABLE `" . $tname . "`(`cdate` DATE NOT NULL) ENGINE=MEMORY";
		udb::query($que);
		
		$que = "INSERT INTO `" . $tname . "`(`cdate`) VALUES('" . implode("'),('", $dates) . "')";
		udb::query($que);
		
		$que = "DELETE unitsDates.* FROM `unitsDates` INNER JOIN `sitesRooms` USING(`roomID`) 
					WHERE unitsDates.date BETWEEN '" . $data['fromDate'] . "' AND ('" . $data['toDate'] . "' - INTERVAL 1 DAY)
						AND " . (($data['roomID'] > 0) ? 'unitsDates.roomID = ' . $data['roomID'] : 'sitesRooms.siteID = ' . $data['siteID']);
		udb::query($que);
		
		$que = "INSERT INTO `unitsDates`(`roomID`, `date`, `free`) (
					SELECT sitesRooms.roomID, dt.cdate, " . (($data['free'] >= 0) ? "'" . $data['free'] . "'" : 'sitesRooms.roomCount') . "
					FROM `sitesRooms` INNER JOIN `" . $tname . "` AS `dt`
					WHERE " . (($data['roomID'] > 0) ? 'sitesRooms.roomID = ' . $data['roomID'] : 'sitesRooms.siteID = ' . $data['siteID']) . "
				)";
		udb::query($que);
		
		$que = "OPTIMIZE TABLE `unitsDates`";
		udb::query($que);

		PriceCache::updateTomorrow();
		PriceCache::updateWeekend();
?>
		<script> window.parent.location.reload(); </script></body></html>
<?php
	}
}

if ($roomID > 0){
	$que = "SELECT sitesRooms.roomCount, sitesRooms.roomName
			FROM sitesRooms 
			WHERE sitesRooms.siteID = " . $siteID . " AND sitesRooms.roomID = " . $roomID;
	$room = udb::single_row($que);
	
	$que = "SELECT `free` FROM `unitsDates` WHERE `roomID` = " . $roomID . " AND `date` = '" . udb::escape_string($date) . "'";
	$udt = udb::single_value($que);
} else
	$room = array('roomCount' => 0, 'roomName' => 'כל היחידות');
?>
<style>
body{padding:0 !important;}
#mainContainer{padding:0 !important;}
</style>
<div class="tbs">
	<div class="tb active">עדכון תפוסה</div>
</div>
<div class="editDates dates">
    <h1><?=$room['roomName']?></h1><b style="color:red"><?=$error?></b>

	<form method="POST" id="myform" class="calenderform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<input type="hidden" name="siteID" value="<?=$siteID?>" />
		<input type="hidden" name="roomID" value="<?=$roomID?>" />
		
		<div class="section">
			<div class="inptLine">
				<div class="label">מתאריך</div>
				<input type="text" value="<?=date("d/m/Y", strtotime($date))?>" name="fromDate" class="inpt datepicker" id="dt1" readonly>
			</div>
		</div>
		
		<div class="section">
			<div class="inptLine">
				<div class="label">עד תאריך</div>
				<input type="text" value="<?=date("d/m/Y", strtotime($date . ' +1 day'))?>" name="toDate" class="inpt datepicker"  id="dt2" readonly>
			</div>
		</div>
		<div class="section sel">
			<div class="inptLine">
				<div class="label">סוג עדכון</div>
				<select name="free">
					<option value="-2">בחר סוג עדכון</option>
					<option value="0">הכל תפוס</option>
<?php
	is_numeric($udt) or $udt = $room['roomCount'];
	for($i = 1; $i < $room['roomCount']; ++$i)
		echo '<option value="' , $i , '" ' . (($i == $udt) ? 'selected="selected"' : '') . '>' , (($i > 1) ? $i . ' יחידות פנויות' : 'יחידה אחת פנויה') , '</option>';
?>
					<option value="-1" >הכל פנוי</option>
				</select>
			</div>
		</div>
		
		
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="עדכון נתונים" class="submit">
			</div>
		</div>
	</form>
</div>
</section>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div>
    </div>
</div>
<script src="<?=$root;?>/app/jquery-ui.min.js"></script>
<script>
$(function() {
	/*$( ".datepicker" ).datepicker({

	});*/

	$("#dt1").datepicker({

		onSelect: function (date,dp) {
			var nextDay = new Date(dp.selectedYear,dp.selectedMonth,parseInt(dp.selectedDay) + 1);
			var dt1 = $('#dt1').datepicker('getDate');
			var dt2 = $('#dt2').datepicker('getDate');
			if (dt1 > dt2) {
				$('#dt2').datepicker('setDate', nextDay );
			}
			$('#dt2').datepicker('option', 'minDate', nextDay );
		}
	});
	$('#dt2').datepicker({

		minDate: $('#dt1').datepicker('getDate'),
		onClose: function () {
			var dt1 = $('#dt1').datepicker('getDate');
			var dt2 = $('#dt2').datepicker('getDate');
			//check to prevent a user from entering a date below date of dt1
			if (dt2 <= dt1) {
				var minDate = $('#dt2').datepicker('option', 'minDate');
				$('#dt2').datepicker('setDate', minDate);
			}
		}
	});

});
</script>
</body>
</html>
