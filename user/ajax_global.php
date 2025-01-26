<?php
header('Content-Type: application/json');

include_once "auth.php";

/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
$siteID = $_CURRENT_USER->active_site(); //(intval($_GET['siteID'])?intval($_GET['siteID']):$_SESSION['siteManager']['siteID']);

$hebNames = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];
$hebDays  = ['א','ב','ג','ד','ה','שישי','שבת'];

$result = [];

function completeMin($periodID){
    global $siteID;

    $base = udb::key_row("SELECT a.* FROM `rooms_min_nights` AS `a` INNER JOIN `sites_periods` AS `p` USING(`periodID`) WHERE a.roomID = 0 AND p.periodType = 2 AND p.siteID = " . $siteID, 'weekday');
    $curr = udb::key_row("SELECT a.* FROM `rooms_min_nights` AS `a` WHERE a.roomID = 0 AND a.periodID = " . $periodID, 'weekday');

    $insert = [];
    for($i = 0; $i <= 6; ++ $i)
        $insert[] = "(0, " . $periodID . ", " . $i . ", " . ($curr[$i]['minNights'] ?? $base[$i]['minNights'] ?? 1) . ", " . ($curr[$i]['minVoid'] ?? $base[$i]['minVoid'] ?? 1) . ")";

    $que = "INSERT INTO `rooms_min_nights`(`roomID`, `periodID`, `weekday`, `minNights`, `minVoid`) VALUES" . implode(',', $insert) . " ON DUPLICATE KEY UPDATE `weekday` = VALUES(`weekday`)";
    udb::query($que);

    /*$que = "INSERT INTO `rooms_min_nights`(`roomID`, `periodID`, `weekday`, `minNights`, `minVoid`)
                SELECT a.roomID, '" . $periodID ."' AS `periodID`, a.weekday, a.minNights, a.minVoid
                FROM `rooms_min_nights` AS `a` INNER JOIN `sites_periods` AS `p` USING(`periodID`)
                    LEFT JOIN `rooms_min_nights` AS `b` ON (b.roomID = a.roomID AND b.periodID = " . $periodID . " AND b.weekday = a.weekday)
                WHERE p.periodType = 2 AND p.siteID = " . $siteID . " AND b.roomID IS NULL";*/
}

function updateSpaPlus($dataToUpdate){

}

try
{
    switch($_POST['act']){
        case "getcoords":
            $result['data'] =  getLocationNumbers($_POST['val']);
            break;

		case "fullscale":
			$_SESSION['fullscale'] = intval($_POST['fullscale']);
			//echo  $_SESSION['menuc'];
			break;

		case "menuc":
			$_SESSION['menuc'] = intval($_POST['menuc']);
			//echo  $_SESSION['menuc'];
			break;

        case "ownComment":
            $rID = intval($_POST['id']);
            if(isset($_POST['ownComment'])) {
                $que = [];
                $que['ownComment'] = $_POST['ownComment'];
                $que['ownCommentDate'] = date("Y-m-d H:i");
                $que['resendToSpaPlus'] = 1;
                udb::update("reviews",$que, " reviewID=".$rID);
                $result['update'] = 1;
                $reviewData = udb::single_row("select orders.guid,reviews.email,reviews.name,orders.customerPhone,orders.customerEmail,sites.siteName from reviews left join orders using (orderID) left join sites on (sites.siteID=reviews.siteID) where reviews.reviewID=".$rID);
                if(!$guid['guid']) {
                    $guid = $rID . strtotime($que['ownCommentDate']);
                }
                $data = [];
                if($reviewData['email']) {
                    $data['email'] = $reviewData['email'] ;
                }
                else {
                    if($reviewData['customerEmail']) {
                        $data['email'] =  $reviewData['customerEmail'] ;
                    }
                }

                if($reviewData['customerPhone']) $data['phone'] = $reviewData['customerPhone'];
                $data['siteTitle'] = $reviewData['siteName'];
                $data['ownComment'] = $_POST['ownComment'];
                $data['author'] = $reviewData['name'];
                $data["link"] = "https://bizonline.co.il/review.php?reviewID=".$rID."&guid=".$guid;

                reviewcomment::sendEmailSmsforClientsNotifyAboutReviewComment($data);
            }
            else {
                $result['text'] = udb::single_value("select ownComment from reviews where reviewID=".$rID);
            }
            $result['status'] = 0;
            break;
        case "treatmentStartTimes":
            udb::update('sites', ['treatmentStartTimes' => intval($_POST['treatmentStartTimes'])], '`siteID` = ' . $siteID);
            $result['status'] = 0;
            break;
        case "sameDayOrderHoursBefore":
            udb::update('sites', ['sameDayOrderHoursBefore' => intval($_POST['sameDayOrderHoursBefore'])], '`siteID` = ' . $siteID);
            $spaplusID = udb::single_value("select spaplusID from sites where siteID=".$siteID);
            if($spaplusID) {
                $urlToGet = "https://www.spaplus.co.il/bizapi/?key=Kew0Rd!Kew0Rd!Kew0Rd!&action=102&sameDayOrderHoursBefore=".intval($_POST['sameDayOrderHoursBefore']) . "&siteID=".$spaplusID;
                $result['response'] = getUrl($urlToGet);
                $result['url'] = $urlToGet;
            }
            $result['status'] = 0;
            break;
        case 'priceFrom':
            udb::update('sites', ['startFromW' => intval($_POST['winter']), 'startFromS' => intval($_POST['summer'])], '`siteID` = ' . $siteID);
            $result['status'] = 0;
            break;

        case 'sync':
            $periodID = intval($_POST['periodID']);
            $sync = intval($_POST['sync']);

            if (!$periodID)
                throw new Exception("Insufficient data");

            if ($periodID < 0){
                $exists = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `siteID` = " . $siteID . " AND `holidayID` = " . abs($periodID));
                if ($exists)
                    $periodID = $exists;
            }

            if ($periodID > 0){
                $pdata = udb::single_row("SELECT `holidayID`, `periodType` FROM `sites_periods` WHERE `periodID` = " . $periodID . " AND `siteID` = " . $siteID);
                if (!$pdata)
                    throw new Exception("Period doesn't belong to this site");
                elseif ($pdata['periodType'] > 0)
                    throw new Exception("Cannot synchronize base period");
            }

            switch($sync){
                case 1:
                    if ($periodID > 0){
                        udb::query("DELETE FROM `rooms_min_nights` WHERE `periodID` = " . $periodID);
                        udb::query("UPDATE `sites_periods` AS `a` INNER JOIN `sites_periods` AS `b` ON (a.siteID = b.siteID AND b.periodType = 2) 
                                    SET a.weekend = b.weekend, a.breakfast = b.breakfast, a.sync = 1
                                    WHERE a.periodType = 0 AND a.periodID = " . $periodID);
                        udb::query("DELETE FROM `rooms_prices` WHERE `periodID` = " . $periodID);
                    }
                    break;

                case 2:
                    if ($periodID > 0){
                        udb::query("DELETE FROM `rooms_prices` WHERE `periodID` = " . $periodID);
                        udb::query("UPDATE `sites_periods` SET `sync` = 0 WHERE `periodID` = " . $periodID);
                    } else {
                        $periodID = holiday2period($siteID, -$periodID, false);     // should not happen
                    }
                    break;
            }

            $result['status'] = 0;
            break;

        case 'getMin':
            $periodID = intval($_POST['periodID']);
            if (!$periodID)
                throw new Exception("Insufficient data");

            if ($periodID < 0){
                $exists = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `siteID` = " . $siteID . " AND `holidayID` = " . abs($periodID));
                if ($exists)
                    $periodID = $exists;
            }

            // pulling data from period / hot period
            if ($periodID < 0){
                $pdata = udb::single_row("SELECT `periodID`, `periodType`, `weekend`, `sync` FROM `sites_periods` WHERE `periodType` = 2 AND `siteID` = " . $siteID);
                $periodID = $pdata['periodID'];
                $pType = -1;
            }
            else {
                $pdata = udb::single_row("SELECT `periodID`, `periodType`, `weekend`, `sync` FROM `sites_periods` WHERE `periodID` = " . $periodID);
                $pType = $pdata['periodType'];
            }

            $minRoomPeriod = udb::key_row("SELECT `weekday`, `minNights`, `minVoid` FROM `rooms_min_nights` WHERE `roomID` = 0 AND `periodID` = " . $periodID, 'weekday');
            $pm = count($minRoomPeriod);

            // if custom period
            if ($pdata['periodType'] == 0){
                $hotPeriod = udb::single_row("SELECT `periodID`, `weekend` FROM `sites_periods` WHERE `periodType` = 2 AND `siteID` = " . $siteID);
                $minRoomHot = udb::key_row("SELECT `weekday`, `minNights`, `minVoid` FROM `rooms_min_nights` WHERE `roomID` = 0 AND `periodID` = " . $hotPeriod['periodID'], 'weekday');

                foreach($minRoomHot as $i => $val)
                    if (!isset($minRoomPeriod[$i]))
                        $minRoomPeriod[$i] = $val;

                if (!$pdata['weekend'])
                    $pdata['weekend'] = $hotPeriod['weekend'];
            }

            $weekend = $pdata['weekend'] ? explode(',', $pdata['weekend']) : [4,5];

            $pc = udb::single_value("SELECT COUNT(*) FROM `rooms_prices` WHERE `periodID` = " . $periodID);

            ob_start();
			if(!$pType) {
				?>
				<button class="editPeriod" style="display:block;position:fixed;top:10px;left:10px" onclick="$('.editPeriodPopup').show();">עריכת/מחיקת תקופה</button>
				<?php
			}
            if ($pType <= 0){
?>
<div style="width:100%;max-width:250px;text-align:center">
    <select name="sync" id="sync" style="width:100%;max-width:210px;height:30px;display:block;margin:0 auto;font-size:20px">
<?php
            if ($pType == 0 && $pc > 0)
                echo '<option value="3">לא מסונכרן</option>';
            if ($pType == 0 && ($pm > 0 || $pdata['sync'] == 0))
                echo '<option value="2">סנכרון מחירים</option>';
?>
        <option value="1">סנכרון מחירים והגדרות</option>
    </select>
</div>
<?php
            }

            foreach($hebNames as $i => $dayName){
?>
<div class="day">
    <div class="title"><?=$dayName?></div>
    <select id="day_0_<?=$i?>">
        <option value="0">סגור</option>
<?php
            $selected = ($minRoomPeriod[$i]['minNights'] ?? 1);
            for($j = 1; $j <= 7; ++$j)
                echo '<option value="' . $j . '" ' . ($selected == $j ? 'selected="selected"' : '') . '>' . $j . '</option>';
?>
    </select>
    <select id="void_0_<?=$i?>">
        <option value="-1">סגור</option>
<?php
            $selected = ($minRoomPeriod[$i]['minVoid'] ?? 1);
            for($j = 0; $j <= 7; ++$j)
                echo '<option value="' . $j . '" ' . ($selected == $j ? 'selected="selected"' : '') . '>' . $j . '</option>';
?>
    </select>
    <input type="checkbox" value="<?=$i?>" name="weekend[0][]" id="weekend_0_<?=$i?>" <?=(in_array($i, $weekend) ? 'checked="checked"' : '')?> style="display:none" />
    <label for="weekend_0_<?=$i?>" style="cursor:pointer">אמצ"ש<span>סופ"ש</span></label>
</div>
<?php
            }

            $result['html']   = ob_get_clean();
            $result['sync']   = ($pType > 0) ? 0 : 1;
            $result['status'] = 0;
            break;


        case "roomPrices" :
            $roomID = intval($_POST['roomID']);
            $sunday  = typemap($_POST['week'], 'date');
            $periodID = intval($_POST['periodID']);


			$que = "SELECT * FROM sites_periods WHERE periodID = " . $periodID . "";
			$periodsX = udb::single_row($que);


            if (!$roomID || !$sunday || !$periodID)
                throw new Exception("Insufficient data");

            $que = "SELECT `roomID`, `roomName`, `showOrder` FROM `rooms` WHERE   `roomID` = " . $roomID  . " AND `siteID` = " . $siteID; //AND`active` = 1 AND
            $room = udb::single_row($que);
			$allRooms = udb::single_column("SELECT `roomID` FROM rooms WHERE siteID = ". $siteID);



            if (!$room)
                throw new Exception("Cannot find room #" . $roomID);

            if ($periodID < 0){
                $exists = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `siteID` = " . $siteID . " AND `holidayID` = " . abs($periodID));
                if ($exists)
                    $periodID = $exists;
            }

            if ($periodID > 0){
                $limits = udb::single_row("SELECT IF(`periodType` = 2, '9999-01-01', `dateFrom`) AS `from`, IF(`periodType` = 1, '9999-01-01', `dateTo`) AS `till`, `periodType`, `weekend` FROM `sites_periods` WHERE `periodID` = " . $periodID);

                $pricesData = udb::single_row("SELECT * FROM `rooms_prices` WHERE `periodID` = " . $periodID . " AND `roomID` = " . $roomID . " AND `day` = -1");

                $minRoomPeriod = udb::key_row("SELECT `roomID`, `weekday`, `minNights`, `minVoid` FROM `rooms_min_nights` WHERE `roomID` IN (0, " . $roomID . ") AND `periodID` = " . $periodID . " ORDER BY `roomID`", 'weekday');

                if ($limits['periodType'] == 0){
                    if (strcmp($limits['from'], $sunday) > 0)
                        $sunday = $limits['from'];
                    elseif (strcmp($limits['till'], $sunday) < 0)
                        $sunday = $limits['till'];

                    $hotPeriod = udb::single_row("SELECT `periodID`, `weekend` FROM `sites_periods` WHERE `periodType` = 2 AND `siteID` = " . $siteID);
                    $minRoomHot = udb::key_row("SELECT `weekday`, `minNights`, `minVoid` FROM `rooms_min_nights` WHERE `roomID` IN (0, " . $roomID . ") AND `periodID` = " . $hotPeriod['periodID'] . " ORDER BY `roomID`", 'weekday');

                    foreach($minRoomHot as $i => $val)
                        if (!isset($minRoomPeriod[$i]))
                            $minRoomPeriod[$i] = $val;
                }
            } else {
                $hotPeriod = udb::single_row("SELECT `periodID`, `weekend` FROM `sites_periods` WHERE `periodType` = 2 AND `siteID` = " . $siteID);

                /*$que = "SELECT rooms_prices.*, sites_periods.weekend FROM `rooms_prices` INNER JOIN `sites_periods` USING(`periodID`) WHERE rooms_prices.roomID = " . $roomID . " AND rooms_prices.day = -1 AND sites_periods.periodType > 0 AND sites_periods.siteID = " . $siteID . " ORDER BY sites_periods.periodType DESC";
                $pricesData = udb::single_row($que);*/
                $pricesData = [];

                $limits = udb::single_row("SELECT `dateStart` AS `from`, `dateEnd` AS `till`, '0' AS `periodType` FROM `holidays` WHERE `holidayID` = " . abs($periodID));
                $limits['weekend'] = $hotPeriod['weekend'];

                if (strcmp($limits['from'], $sunday) > 0)
                    $sunday = $limits['from'];
                elseif (strcmp($limits['till'], $sunday) < 0)
                    $sunday = $limits['till'];

                $minRoomPeriod = udb::key_row("SELECT `roomID`, `weekday`, `minNights`, `minVoid` FROM `rooms_min_nights` WHERE `roomID` IN (0, " . $roomID . ") AND `periodID` = " . $hotPeriod['periodID'] . " ORDER BY `roomID`", 'weekday');
            }

            $time = strtotime($sunday . ' 10:00:00');
            if ($w = date('w', $time)){
                $time  -= $w * 3600 * 24;
                $sunday = date('Y-m-d', $time);
            }

            $dates = [$sunday];
            for($i = 1; $i < 9; ++$i)
                $dates[] = date('Y-m-d', $time + $i * 3600 * 24);

            if ($limits['periodType'] == 0 && !$pricesData){
                $que = "SELECT rooms_prices.*, sites_periods.weekend FROM `rooms_prices` INNER JOIN `sites_periods` USING(`periodID`) WHERE rooms_prices.roomID = " . $roomID . " AND rooms_prices.day = -1 AND sites_periods.periodType > 0 AND sites_periods.siteID = " . $siteID . " ORDER BY sites_periods.periodType DESC";
                $pricesData = udb::single_row($que);
            }

            $hasPrices = $pricesData ? count($pricesData) : 0;

            $que = "SELECT tmp.date, tmp.periodID
                        , IF( FIND_IN_SET(tmp.weekday, p.weekend), pr.weekend1, pr.weekday1) AS `price1`
                        , IF( FIND_IN_SET(tmp.weekday, p.weekend), pr.weekend2, pr.weekday2) AS `price2`
                        , IF( FIND_IN_SET(tmp.weekday, p.weekend), pr.weekend3, pr.weekday3) AS `price3`
                        , IF( FIND_IN_SET(tmp.weekday, p.weekend), pr.halfDayEnd, pr.halfDay) AS `extraDay`
                        , IF( FIND_IN_SET(tmp.weekday, p.weekend), pr.halfNightEnd, pr.halfNight) AS `extraAdult`
                        , IF( FIND_IN_SET(tmp.weekday, p.weekend), pr.allDayEnd, pr.allDay) AS `extraKid`
                        , pr.extraHour, pr.extraHourEnd
                    FROM (
                        SELECT year.date, year.weekday, IFNULL(p3.periodID, IF(holidays.holidayID, p2.periodID, p1.periodID)) as `periodID`
                            , IF(rp3.roomID, p3.periodID, IF(p3.periodID OR holidays.holidayID, IF(rp2.roomID, p2.periodID, p1.periodID), p1.periodID)) as `ppID` 
                        FROM `year` INNER JOIN `rooms` INNER JOIN `sites` USING(`siteID`)
                            INNER JOIN `sites_periods` as `p1` ON (sites.siteID = p1.siteID AND p1.periodType = 1)
                            INNER JOIN `sites_periods` as `p2` ON (sites.siteID = p2.siteID AND p2.periodType = 2)
                            LEFT JOIN `rooms_prices` as `rp2` ON (rp2.periodID = p2.periodID AND rooms.roomID = rp2.roomID AND rp2.day = year.weekday)
                            LEFT JOIN `sites_periods` as `p3` ON (sites.siteID = p3.siteID AND p3.periodType = 0 AND year.date BETWEEN p3.dateFrom AND p3.dateTo)
                            LEFT JOIN `rooms_prices` as `rp3` ON (rp3.periodID = p3.periodID AND rooms.roomID = rp3.roomID AND rp3.day = year.weekday)
                            LEFT JOIN `holidays` ON (year.date BETWEEN holidays.dateStart AND holidays.dateEnd)
                        WHERE year.date BETWEEN '" . $sunday . "' - INTERVAL 1 DAY AND '" . $sunday . "' + INTERVAL 8 DAY AND rooms.roomID = " . $roomID . "
                    ) AS `tmp` INNER JOIN `sites_periods` AS `p` USING(`periodID`)
                        INNER JOIN `rooms_prices` AS `pr` ON (pr.periodID = tmp.ppID AND pr.day = tmp.weekday AND pr.roomID = " . $roomID . ")
                    WHERE 1"; //rooms.active = 1 AND
            $weekPrices = udb::key_row($que, 'date');

            $isFree = udb::key_value("SELECT `date`, `free` FROM `unitsDates` WHERE `date` BETWEEN '" . $sunday . "' AND '" . $sunday . "' + INTERVAL 6 DAY AND `roomID` = " . $roomID);

            $weekend = explode(',', $limits['weekend']);

            ob_start();
?>
		<div class="topof">
			<div class="r">

<?php
			$index = array_search($roomID, $allRooms);
			if($index !== false && $index > 0 ) $prev = $allRooms[$index-1];
			if($index !== false && $index < count($allRooms)-1) $next = $allRooms[$index+1];

            if ($prev)
                echo '<div class="rgtPeriodsArrow callable" data-param="roomID" data-value="' , $prev , '"><i class="icon-right"></i></div>';
            if ($next)
                echo '<div class="lftPeriodsArrow callable" data-param="roomID" data-value="' , $next , '"><i class="icon-left"></i></div>';
?>
			<span class="roomNameCal"><?=$room['roomName']?></span></div>

			<!--<button class="perRoomB" type="button">הגדרות יחידה</button>-->
		</div>

		<div class="roomName"></div>
		<div class="periodsPrices perRoom">
			<div class="r">
				<div>הגדרות ומחירים לפי יחידה</div>
				<div>מינימום לילות להזמנה החל מיום זה</div>
				<div>הזמנת לילה אחד תתאפשר רק _ ימים לפני</div>
			</div>
			<div class="l" data-room="<?=$roomID?>">
<?php
            foreach($hebNames as $i => $dayName){
?>
                <div class="day">
                    <div class="title"><?=$dayName?></div>
                    <select id="day_<?=$roomID?>_<?=$i?>" <?=($minRoomPeriod[$i]['roomID'] ? '' : 'data-rel="day_0_' . $i . '"')?>>
                        <option value="0">סגור</option>
<?php
                $selected = ($minRoomPeriod[$i]['minNights'] ?? 1);
                for($j = 1; $j <= 7; ++$j)
                    echo '<option value="' . $j . '" ' . ($selected == $j ? 'selected="selected"' : '') . '>' . $j . '</option>';
?>
					</select>
					<select id="void_<?=$roomID?>_<?=$i?>" <?=($minRoomPeriod[$i]['roomID'] ? '' : 'data-rel="void_0_' . $i . '"')?>>
                        <option value="-1">סגור</option>
<?php
                $selected = ($minRoomPeriod[$i]['minVoid'] ?? 1);
                for($j = 0; $j <= 7; ++$j)
                    echo '<option value="' . $j . '" ' . ($selected == $j ? 'selected="selected"' : '') . '>' . $j . '</option>';
?>
					</select>
                </div>
<?php
            }
?>
			</div>
		</div>

<div class="editRoomsPrices-periods">
    <div class="popup editPeriodPopup" style="display: none;">
		<div class="popup_container">
            <div class="pop-close" onclick="$(this).closest('.popup').hide();"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30"><path fill-rule="evenodd" fill="rgb(0, 0, 17)" d="M25.6 25.6C19.8 31.5 10.2 31.5 4.4 25.6 -1.5 19.8-1.5 10.2 4.4 4.4 10.2-1.5 19.8-1.5 25.6 4.4 31.5 10.2 31.5 19.8 25.6 25.6ZM5.7 5.7C0.5 10.8 0.5 19.2 5.7 24.3 10.8 29.5 19.2 29.5 24.3 24.3 29.5 19.2 29.5 10.8 24.3 5.7 19.2 0.5 10.8 0.5 5.7 5.7ZM20.6 10.7L16 15.2 20.6 19.8C21 20.2 21 20.7 20.6 21.1 20.3 21.4 19.7 21.4 19.3 21.1L14.8 16.5 10.5 20.8C10.2 21.1 9.6 21.1 9.2 20.8 8.9 20.4 8.9 19.9 9.2 19.5L13.5 15.2 9.2 10.9C8.8 10.6 8.8 10 9.2 9.7 9.5 9.3 10.1 9.3 10.5 9.7L14.8 14 19.4 9.4C19.7 9 20.3 9 20.6 9.4 21 9.7 21 10.3 20.6 10.7Z"></path></svg></div>
			<div class="popTitle">עריכת תקופה</div>
			<form method="post" action="">
				<input type="hidden" value="<?=$periodID?>" name="periodID">
				<div class="wrap_input" id="periodName">
					<input type="text" name="periodNameI" value="<?=$periodsX['periodName']?>" autocomplete="off" data-q-error="שם תקופה לא חוקי" data-validator="notEmpty,lessThen:61" id="periodNameE" class="form_inp error_input">
					<label for="periodNameE" data-error="שם תקופה לא חוקי">שם תקופה</label>
				</div>
				<div class="wrap_input" id="periodStart">
					<input type="text" name="periodStartI" autocomplete="off" value="<?=db2date($periodsX['dateFrom'], '/')?>" id="periodStartE" class="form_inp error_input" required="">
					<label for="periodStartE">תאריך התחילה</label>
				</div>
				<div class="wrap_input" id="periodEnd">
					<input type="text" name="periodEndI" autocomplete="off" value="<?=db2date($periodsX['dateTo'], '/')?>" id="periodEndE" class="form_inp error_input" required="">
					<label for="periodEndE">תאריך סיום</label>
				</div>
				<div class="npgo">שמירה</div>
				<?php if($periodID > 0) { ?><div class="removeButton" data-pid="<?=$periodID?>">מחיקת תקופה <svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" viewBox="0 0 59 59" xml:space="preserve"><path d="M52.5 6H38.5c-0.1-1.2-0.5-3.4-1.8-4.7C35.8 0.4 34.8 0 33.5 0H23.5c-1.3 0-2.3 0.4-3.1 1.3C19 2.6 18.7 4.8 18.5 6H6.5c-0.6 0-1 0.4-1 1s0.4 1 1 1h2l1.9 46C10.5 55.7 11.6 59 15.4 59h28.3c3.8 0 4.9-3.3 4.9-5L50.5 8H52.5c0.6 0 1-0.4 1-1S53.1 6 52.5 6zM20.5 50c0 0.6-0.4 1-1 1s-1-0.4-1-1V17c0-0.6 0.4-1 1-1s1 0.4 1 1V50zM30.5 50c0 0.6-0.4 1-1 1s-1-0.4-1-1V17c0-0.6 0.4-1 1-1s1 0.4 1 1V50zM40.5 50c0 0.6-0.4 1-1 1s-1-0.4-1-1V17c0-0.6 0.4-1 1-1s1 0.4 1 1V50zM21.8 2.7C22.2 2.2 22.8 2 23.5 2h10c0.7 0 1.3 0.2 1.7 0.7 0.8 0.8 1.1 2.3 1.2 3.3H20.6C20.7 5 21 3.5 21.8 2.7z"></path></svg></div><?php } ?>

			</form>
		</div>
	</div>
    <form method="POST" id="pricesForm">
        <div class="editRoomsCarousel periodcarousel">
            <div class="editRoomsPrices-period">
                <input type="hidden" name="siteID" value="<?=$siteID?>" />
                <input type="hidden" name="periodID" value="<?=$periodID?>" />
                <input type="hidden" name="roomID" value="<?=$roomID?>" />
                <!--<div class="month_botton">הצגה על לוח שנה</div>-->
				<div class="tableLike">

					<div class="row">
                        <div class="cell">סוג תמחור</div>
                        <div class="cell">אמצ"ש</div>
                        <div class="cell endof">סופ"ש</div>
                    </div>

                    <div class="row">
                        <div class="cell"><span>לילה אחד</span>לילה אחד<div>מחיר ללילה</div></div>
                        <div class="cell"><input type="text" class="middleweek" value="<?=($hasPrices ? $pricesData['weekday1'] : '')?>" name="weekday1" title="מחיר ללילה באמצ&quot;ש" /></div>
                        <div class="cell"><input type="text" class="endofweek" value="<?=($hasPrices ? $pricesData['weekend1'] : '')?>" name="weekend1" title="מחיר ללילה בסופ&quot;ש" /></div>
                    </div>
                    <div class="row">
                        <div class="cell"><span>2 לילות</span>2 לילות<div>מחיר כולל</div></div>
                        <div class="cell"><input type="text" class="middleweek" value="<?=($hasPrices ? round($pricesData['weekday2']*2) : "")?>" name="weekday2" title="מחיר 2 לילות באמצ&quot;ש" /></div>
                        <div class="cell"><input type="text" class="endofweek" value="<?=($hasPrices ? round($pricesData['weekend2']*2) : "")?>" name="weekend2" title="מחיר 2 לילות בסופ&quot;ש" /></div>
                    </div>
                    <div class="row">
                        <div class="cell"><span>3 לילות</span>3 לילות<div>מחיר כולל</div></div>
                        <div class="cell"><input type="text" class="middleweek" value="<?=($hasPrices ? round($pricesData['weekday3']*3) : "")?>" name="weekday3" title="מחיר 3 לילות בסופ&quot;ש" /></div>
                        <div class="cell"><input type="text" class="endofweek" value="<?=($hasPrices ? round($pricesData['weekend3']*3) : "")?>" name="weekend3" title="מחיר 3 לילות בסופ&quot;ש" /></div>
                    </div>
                    <div class="row">
                        <div class="cell"><span>+ לילה נוסף</span>כל לילה נוסף<div>מחיר ללילה</div></div>
                        <div class="cell"><input type="text" class="middleweek" value="<?=$pricesData['extraWeekday']?>" name="extraWeekday" title="מחיר ללילה נוסף" /></div>
                        <div class="cell"><input type="text" class="endofweek" value="<?=$pricesData['extraWeekend']?>" name="extraWeekend" title="מחיר ללילה נוסף" ></div>
                    </div>
                    <div class="row">
                        <div class="cell"><span>+ מבוגר</span>תוספת מבוגר<div>מחיר ללילה</div></div><?=print_r($pricesData)?>
                        <div class="cell"><input type="text" class="middleweek" value="<?=$pricesData['extraPriceAdultWeekday']?>" name="extraPriceAdultWeekday" title="" /></div>
                        <div class="cell"><input type="text" class="endofweek" value="<?=$pricesData['extraPriceAdultWeekend']?>" name="extraPriceAdultWeekend" title="" /></div>
                    </div>
                    <div class="row">
                        <div class="cell"><span>+ ילד</span>תוספת ילד<div>מחיר ללילה</div></div>
                        <div class="cell"><input type="text" class="middleweek" value="<?=$pricesData['extraPriceKidWeekday']?>" name="extraPriceKidWeekday" title="" /></div>
                        <div class="cell"><input type="text" class="endofweek" value="<?=$pricesData['extraPriceKidWeekend']?>" name="extraPriceKidWeekend" title="" /></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!--<div class="editRoomsPrices-months">
    <div class="editRoomsCarousel monthscarousel">
<?php
            if (strcmp($dates[0], $limits['from']) > 0)
                echo '<div class="rgtWeekArrow callable" data-param="week" data-value="' , date('Y-m-d', $time - 7 * 24 * 3600) , '"><i class="icon-right"></i></div>';
            if (strcmp($dates[7], $limits['till']) <= 0)
                echo '<div class="lftWeekArrow callable" data-param="week" data-value="' , $dates[7] , '"><i class="icon-left"></i></div>';

            foreach($hebNames as $i => $name){
                $date = $dates[$i];
                $free = ($isFree[$date] ?? 1) ? '<div>פנוי</div>' : '<div class="busy">תפוס</div>';
                $editable = (($weekPrices[$date]['periodID'] == $periodID) || ($limits['periodType'] <= 0 && strcmp($date, $limits['from']) >= 0 && strcmp($date, $limits['till']) <= 0)) ? '' : 'newPeriod';
?>
        <div class="editRoomsPrices-day <?=$editable?>" data-index="<?=$i?>">
            <div><?=implode('/', array_reverse(explode('-', substr($date, 5))))?><div><?=($name . $free)?></div></div>
            <div class="tableLike">
                <div class="row">
                    <div class="cell"><span class="priceCell">₪<?=($weekPrices[$date]['price1'])?></span></div>
                </div>
                <div class="row">
                    <div class="cell"><span class="days"><?=$hebDays[$i]?> - <?=$hebDays[($i + 2) % 7]?></span><span class="priceCell">₪<?=round($weekPrices[$date]['extraHour'] ?: $weekPrices[$date]['price2'] + $weekPrices[$dates[$i + 1]]['price2'])?></span></div>
                </div>
                <div class="row">
                    <div class="cell"><span class="days"><?=$hebDays[$i]?> - <?=$hebDays[($i + 3) % 7]?></span><span class="priceCell">₪<?=round($weekPrices[$date]['extraHourEnd'] ?: $weekPrices[$date]['price3'] + $weekPrices[$dates[$i + 1]]['price3'] + $weekPrices[$dates[$i + 2]]['price3'])?></div>
                </div>
            </div>
        </div>
<?php
            }
?>
    </div>
    <script type="text/javascript">
        var weekEndDays = [<?=$limits['weekend']?>];/* define weekend days*/
    </script>
</div>-->
<?php
            $result['content'] =  ob_get_clean();
            $result['status']  = 0;
        break;

        case "getCuponsList":
            $result['status'] = "ok";
            UserUtilsNew::init($_CURRENT_USER->sites(true));
            $parent = udb::single_value("select id from payTypes where `key`='".$_POST['cType']."'");
            $userCpns = udb::single_column("select paytypekey from sitePayTypes where siteID=".$_CURRENT_USER->sites(true));
            $userCpns = array_map(function ($a){ return intval($a); },$userCpns);
            $customs = udb::full_list("select * from customPayTypes where parent=".$parent." and siteID=".$_CURRENT_USER->sites(true) );
            $result['data'] = udb::full_list("select * from payTypes where parent=".$parent." and id in (" . implode(",",$userCpns) . ")" );
            $result['data'] = [];
            if($customs) {
                $result['data'] = array_merge($result['data'],$customs);
            }
            break;

        case "roomPricesDay":
            $roomID = intval($_POST['roomID']);
            $weekday  = intval($_POST['day']);
            $periodID = intval($_POST['periodID']);

            if (!$roomID || !$periodID)
                throw new Exception("Insufficient data");

            $que = "SELECT `roomID`, `roomName`, `showOrder` FROM `rooms` WHERE  `roomID` = " . $roomID  . " AND `siteID` = " . $siteID; //`active` = 1 AND
            $room = udb::single_row($que);

            if (!$room)
                throw new Exception("Cannot find room #" . $roomID);

            $days = [$weekday % 7, ($weekday + 1) % 7, ($weekday + 2) % 7];

            if ($periodID < 0){
                $exists = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `siteID` = " . $siteID . " AND `holidayID` = " . abs($periodID));
                if ($exists)
                    $periodID = $exists;
            }

            $cond = [];

            if ($periodID > 0){
                $period = udb::single_row("SELECT `periodType`, `periodName`, IF(FIND_IN_SET('" . $weekday . "', `weekend`), 'end', 'day') AS `end`  FROM `sites_periods` WHERE `periodID` = " . $periodID);
                $cond[] = "p.periodID = " . $periodID;

                switch($period['periodType']){
                    case 0:
                        $cond[] = "p.periodType = 2 AND p.siteID = " . $siteID;
                    case 2:
                        $cond[] = "p.periodType = 1 AND p.siteID = " . $siteID;
                }
            } else {
                $period = udb::single_row("SELECT '0' AS `periodType`, `holidayName` as `periodName` FROM `holidays` WHERE `holidayID` = " . abs($periodID));
                $check  = udb::single_value("SELECT pr.periodID FROM `rooms_prices` AS `pr` INNER JOIN `sites_periods` AS `p` USING(`periodID`) WHERE pr.day = -1 AND p.periodType = 2 AND p.siteID = " . $siteID);

                if ($check)
                    $cond[] = 'p.periodID = ' . $check;
                $cond[] = "p.periodType = 1 AND p.siteID = " . $siteID;
            }

            for($i = 0; $i < count($cond); ++$i){
                $que = "SELECT 
                              SUM( IF (pr.day = " . $weekday . ", IF( FIND_IN_SET(pr.day, p.weekend), pr.weekend1, pr.weekday1), 0)) AS `price1`
                            , SUM( IF (pr.day <> " . $days[2] . ", IF( FIND_IN_SET(pr.day, p.weekend), pr.weekend2, pr.weekday2), 0)) AS `price2`
                            , SUM( IF( FIND_IN_SET(pr.day, p.weekend), pr.weekend3, pr.weekday3)) AS `price3`
                            , SUM( IF (pr.day = " . $weekday . ", IF( FIND_IN_SET(pr.day, p.weekend), pr.halfDayEnd, pr.halfDay), 0)) AS `extraDay`
                            , SUM( IF (pr.day = " . $weekday . ", IF( FIND_IN_SET(pr.day, p.weekend), pr.halfNightEnd, pr.halfNight), 0)) AS `extraAdult`
                            , SUM( IF (pr.day = " . $weekday . ", IF( FIND_IN_SET(pr.day, p.weekend), pr.allDayEnd, pr.allDay), 0)) AS `extraKid`
                            , SUM( IF (pr.day = " . $weekday . ", pr.extraHour, 0)) AS `extraHour`, SUM( IF (pr.day = " . $weekday . ", pr.extraHourEnd, 0)) AS `extraHourEnd` 
                        FROM `sites_periods` AS `p` INNER JOIN `rooms_prices` AS `pr` ON (pr.periodID = p.periodID)
                        WHERE pr.day IN (" . implode(',', $days) . ") AND pr.roomID = " . $roomID . " AND " . $cond[$i];
                $prices = udb::single_row($que);

                if (array_sum($prices))
                    break;
            }

            switch($period['periodType']){
                case 1 : $pname = 'מחיר רגיל'; break;
                case 2 : $pname = 'תקופה "חמה"'; break;
                default: $pname = $period['periodName']; break;
            }

            ob_start();
?>
<div class="prices-pop">
    <div class="pop-close"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30"><path fill-rule="evenodd" fill="rgb(0, 0, 17)" d="M25.6 25.6C19.8 31.5 10.2 31.5 4.4 25.6 -1.5 19.8-1.5 10.2 4.4 4.4 10.2-1.5 19.8-1.5 25.6 4.4 31.5 10.2 31.5 19.8 25.6 25.6ZM5.7 5.7C0.5 10.8 0.5 19.2 5.7 24.3 10.8 29.5 19.2 29.5 24.3 24.3 29.5 19.2 29.5 10.8 24.3 5.7 19.2 0.5 10.8 0.5 5.7 5.7ZM20.6 10.7L16 15.2 20.6 19.8C21 20.2 21 20.7 20.6 21.1 20.3 21.4 19.7 21.4 19.3 21.1L14.8 16.5 10.5 20.8C10.2 21.1 9.6 21.1 9.2 20.8 8.9 20.4 8.9 19.9 9.2 19.5L13.5 15.2 9.2 10.9C8.8 10.6 8.8 10 9.2 9.7 9.5 9.3 10.1 9.3 10.5 9.7L14.8 14 19.4 9.4C19.7 9 20.3 9 20.6 9.4 21 9.7 21 10.3 20.6 10.7Z"></path></svg></div>
    <div>הגדרת מחיר ליום <span id="dayName"><?=$hebNames[$weekday]?></span> ב:</div>
    <div class="prices-pop-tabs">
        <div class="periodName active"><?=$pname?></div>
    </div>
    <form id="changePriceDayForm" data-context="<?=toHTML(json_encode(['siteID' => $siteID, 'periodID' => $periodID, 'roomID' => $roomID, 'weekday' => $weekday]))?>">
        <div class="prices-pop-tab passover active">
            <div class="item">
                <div class="item-right">1-3 לילות<span>יום <?=$hebDays[$weekday]?></span></div>
                <div class="item-center"><input type="text" value="<?=$prices['price1']?>" name="price1" title="" /></div>
                <!-- div class="item-left"><div class="highlight">מחירון <span class="oldPrice"></span><div>נוצר מבצע</div></div></div -->
            </div>
            <div class="item">
                <div class="item-right">4-15 לילות<span class="detailDay"><?=$hebDays[$weekday]?> - <?=$hebDays[($weekday + 2) % 7]?></span></div>
                <div class="item-center"><input type="text" value="<?=round($prices['extraHour'] ?: $prices['price2'])?>" name="price2" title="" /></div>
                <!-- div class="item-left"><div class="highlightc">מחירון <span class="oldPrice"></span><div>צור מבצע</div></div></div -->
            </div>
            <div class="item">
                <div class="item-right">16 לילות ומעלה<span class="detailDay"><?=$hebDays[$weekday]?> - <?=$hebDays[($weekday + 3) % 7]?></span></div>
                <div class="item-center"><input type="text" value="<?=round($prices['extraHourEnd'] ?: $prices['price3'])?>" name="price3" title="" /></div>
                <!-- div class="item-left"><div class="without">מחירון <span class="oldPrice"></span></div></div -->
            </div>
            <div class="updateButton">עדכן מחירים</div>
        </div>
    </form>
</div>
<?php
            $result['content'] =  ob_get_clean();
            $result['status']  = 0;
            break;

        case 'saveMinNights':
            $periodID = intval($_POST['periodID']);
            $roomID = intval($_POST['roomID']);
            $day    = intval($_POST['day']);
            $value  = max(-1, intval($_POST['value']));
            $type   = typemap($_POST['type'], 'string');

            if ($periodID < 0){
                $exists = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `siteID` = " . $siteID . " AND `holidayID` = " . abs($periodID));
                if ($exists)
                    $periodID = $exists;
                else
                    $periodID = holiday2period($siteID, abs($periodID));
            } else
                $periodID = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodID` = " . $periodID . " AND `siteID` = " . $siteID);

            if ($roomID)
                $roomID = udb::single_value("SELECT `roomID` FROM `rooms` WHERE `roomID` = " . $roomID . " AND `siteID` = " . $siteID);

            if (!$periodID)
                throw new Exception("Insufficient or incorrect data");

            $pType = udb::single_value("SELECT `periodType` FROM `sites_periods` WHERE `periodID` = " . $periodID);

            $update = ['roomID' => $roomID, 'periodID' => $periodID, 'weekday' => $day];
            if ($type == 'day')
                $update['minNights'] = $value;
            elseif ($type == 'void')
                $update['minVoid'] = $value;
            udb::insert('rooms_min_nights', $update, true, false);

            if ($pType == 0){
                udb::update('sites_periods', ['sync' => 0], '`periodID` = ' . $periodID);
                completeMin($periodID);
            }

            $result['status'] = 0;
            $result['sync'] = $pType ? 1 : 2;
            break;

        case 'saveWeekend':
            $periodID = intval($_POST['periodID']) ?: [];
            $weekend  = typemap($_POST['weekend'], ['int']) ?: [];

            if ($periodID < 0){
                $exists = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `siteID` = " . $siteID . " AND `holidayID` = " . abs($periodID));
                if ($exists)
                    $periodID = $exists;
                else
                    $periodID = holiday2period($siteID, abs($periodID), true);
            } else
                $periodID = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodID` = " . $periodID . " AND `siteID` = " . $siteID);

            if (!$periodID)
                throw new Exception("Insufficient or incorrect data");

            $pType = udb::single_value("SELECT `periodType` FROM `sites_periods` WHERE `periodID` = " . $periodID);

            udb::update('sites_periods', ['weekend' => implode(',', array_unique($weekend)), 'sync' => 0], "`periodID` = " . $periodID);

            if ($pType == 2){
                $exs = udb::single_column("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `sync` = 1 AND `siteID` = " . $siteID);
                if (count($exs))
                    foreach($exs as $ex)
                        udb::update('sites_periods', ['weekend' => implode(',', array_unique($weekend))], "`periodID` = " . $ex);
            }
            elseif ($pType == 0){
                completeMin($periodID);
            }

            SearchCache::update_sites($siteID);

            $result['sync'] = $pType ? 1 : 2;
            $result['status'] = 0;
            break;

        case "savePricesDay":
            $data = [
                'roomID'   => intval($_POST['roomID']),
                'day'      => intval($_POST['weekday']),
                'periodID' => intval($_POST['periodID']),

                'weekday1' => intval($_POST['price1']),
                'weekday2' => intval($_POST['price2']),
                'weekday3' => intval($_POST['price3']),

                'weekend1' => intval($_POST['price1']),
                'weekend2' => intval($_POST['price2']),
                'weekend3' => intval($_POST['price3']),

                'extraHour'   => intval($_POST['price2']),
                'extraHourEnd'   => intval($_POST['price3'])

                /*'halfDay' => intval($_POST['extraDay']),
                'halfDayEnd' => intval($_POST['extraDay']),
                'halfNight' => intval($_POST['extraAdult']),
                'halfNightEnd' => intval($_POST['extraAdult']),
                'allDay'   => intval($_POST['extraKid']),
                'allDayEnd'   => intval($_POST['extraKid'])*/
            ];

            if ($data['periodID'] < 0){
                $exists = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `siteID` = " . $siteID . " AND `holidayID` = " . abs($data['periodID']));
                if ($exists)
                    $data['periodID'] = $exists;
                else
                    $data['periodID'] = holiday2period($siteID, abs($data['periodID']), true);
            }
            else
                $data['periodID'] = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodID` = " . $data['periodID'] . " AND `siteID` = " . $siteID);

            $data['roomID'] = udb::single_value("SELECT `roomID` FROM `rooms` WHERE `roomID` = " . $data['roomID'] . " AND `siteID` = " . $siteID);

            if (!$data['roomID'] || !$data['periodID'])
                throw new Exception("Insufficient or incorrect data");

            //insert row
            udb::insert('rooms_prices', $data, true, false);

            $pType = udb::single_value("SELECT `periodType` FROM `sites_periods` WHERE `periodID` = " . $data['periodID']);

            if ($pType == 0){
                udb::update('sites_periods', ['sync' => 0], '`periodID` = ' . $data['periodID']);
                completeMin($data['periodID']);
            }

            SearchCache::update_sites($siteID);

            $result['sync'] = $pType ? 1 : 3;
            $result['status'] = 0;
        break;


        case "savePrices":

            $data = [
				'roomID'   => intval($_POST['roomID']),
				'periodID' => intval($_POST['periodID']),

				'weekday1' => intval($_POST['weekday1']),
				'weekday2' => intval($_POST['weekday2'])/2,
				'weekday3' => intval($_POST['weekday3'])/3,

				'weekend1' => intval($_POST['weekend1']),
				'weekend2' => intval($_POST['weekend2'])/2,
				'weekend3' => intval($_POST['weekend3'])/3,

				/*'fixed2'   => 0,
				'fixed3'   => 0,*/

				'extraWeekday' => intval($_POST['extraWeekday']),
				'extraWeekend' => intval($_POST['extraWeekend']),
				'extraPriceAdultWeekday' => intval($_POST['extraPriceAdultWeekday']),
				'extraPriceAdultWeekend' => intval($_POST['extraPriceAdultWeekend']),
				'extraPriceKidWeekday'   => intval($_POST['extraPriceKidWeekday']),
				'extraPriceKidWeekend'   => intval($_POST['extraPriceKidWeekend'])
            ];

            if ($data['periodID'] < 0){
                $exists = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `siteID` = " . $siteID . " AND `holidayID` = " . abs($data['periodID']));
                if ($exists)
                    $data['periodID'] = $exists;
                else
                    $data['periodID'] = holiday2period($siteID, abs($data['periodID']));
            }
            else
                $data['periodID'] = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodID` = " . $data['periodID'] . " AND `siteID` = " . $siteID);

            $data['roomID'] = udb::single_value("SELECT `roomID` FROM `rooms` WHERE `roomID` = " . $data['roomID'] . " AND `siteID` = " . $siteID);

            if (!$data['roomID'] || !$data['periodID'])
                throw new Exception("Insufficient or incorrect data");

            for($i = -1; $i < 7; ++$i){
                $data['day'] = $i;

                udb::insert('rooms_prices', $data, true, false);
            }

            $pType = udb::single_value("SELECT `periodType` FROM `sites_periods` WHERE `periodID` = " . $data['periodID']);

            if ($pType == 0){
                udb::update('sites_periods', ['sync' => 0], '`periodID` = ' . $data['periodID']);
                completeMin($data['periodID']);
            }

            SearchCache::update_sites($siteID);

            $result['sync'] = $pType ? 1 : 3;
            $result['status'] = 0;
            break;



        case "changeRev":
            $change = intval($_POST['ifchange']);
            if($change)
            {
                $que = "UPDATE `reviews` SET `selected`=0 WHERE siteID=".intval($_POST['siteID']);
                udb::query($que);
            }
            $que = "UPDATE `reviews` SET `selected`=".$change." WHERE `reviewID` = ".intval($_POST['revID']);
            udb::query($que);

            $result['status'] = 0;
            break;

        case 'newPeriod':
            $pname = typemap($_POST['periodNameI'], 'string');
            $from  = typemap(array_reverse(explode('/', $_POST['periodStartI'])), 'date');
            $till  = typemap(array_reverse(explode('/', $_POST['periodEndI'])), 'date');

            if (!$from || !$till)
                throw new Exception('Missing period dates');

            $exists = udb::single_row("SELECT `periodID`, `dateFrom`, `dateTo` FROM `sites_periods` WHERE `siteID` = " . $siteID . " AND `periodType` = 0 AND `dateFrom` <= '" . $till . "' AND `dateTo` >= '" . $from . "'");
            if ($exists)
                throw new Exception('Already exists period: ' . db2date($exists['dateFrom']) . ' - ' . db2date($exists['dateTo']));

            $exists = udb::single_row("SELECT `holidayName`, `dateStart`, `dateEnd` FROM `holidays` WHERE `active` = 1 AND `dateStart` <= '" . $till . "' AND `dateEnd` >= '" . $from . "'");
            if ($exists)
                throw new Exception('קיים חג "' . $exists['holidayName'] . '": ' . db2date($exists['dateFrom']) . ' - ' . db2date($exists['dateTo']));

            $hot = udb::single_row("SELECT `weekend`, `breakfast` FROM `sites_periods` WHERE `periodType` = 2 AND `siteID` = " . $siteID);

            udb::insert('sites_periods', [
                'siteID'     => $siteID,
                'periodType' => 0,
                'dateFrom'   => $from,
                'dateTo'     => $till,
                'weekend'    => $hot['weekend'],
                'periodName' => $pname,
                'breakfast'  => $hot['breakfast']
            ]);

            $result['status'] = 0;
            break;

        case 'editPeriod':
			$periodID = intval($_POST['periodID']);

			if ($periodID < 0){
                $exists = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodType` = 0 AND `siteID` = " . $siteID . " AND `holidayID` = " . abs($periodID));
                if ($exists)
                    $periodID = $exists;
                else
                    $periodID = holiday2period($siteID, abs($periodID));
            } else
                $periodID = udb::single_value("SELECT `periodID` FROM `sites_periods` WHERE `periodID` = " . $periodID . " AND `siteID` = " . $siteID);

            if (!$periodID)
                throw new Exception("Insufficient or incorrect data");

			$holidayID = udb::single_value("SELECT `holidayID` FROM `sites_periods` WHERE `periodID` = " . $periodID . " AND `siteID` = " . $siteID);

            $pname = typemap($_POST['periodNameI'], 'string');
            $from  = typemap(array_reverse(explode('/', $_POST['periodStartI'])), 'date');
            $till  = typemap(array_reverse(explode('/', $_POST['periodEndI'])), 'date');

			if(!$pname)
				throw new Exception('Missing period name');

            if (!$from || !$till)
                throw new Exception('Missing period dates');

			$exists = udb::single_row("SELECT `periodID`, `dateFrom`, `dateTo` FROM `sites_periods` WHERE `siteID` = " . $siteID . " AND `periodType` = 0 AND `dateFrom` <= '" . $till . "' AND `dateTo` >= '" . $from . "' AND periodID!=".$periodID);
            if ($exists)
                throw new Exception('Already exists period: ' . db2date($exists['dateFrom']) . ' - ' . db2date($exists['dateTo']));

            $exists = udb::single_row("SELECT `holidayName`, `dateStart`, `dateEnd` FROM `holidays` WHERE `active` = 1 AND `dateStart` <= '" . $till . "' AND `dateEnd` >= '" . $from . "' AND holidayID!=".$holidayID);
            if ($exists)
                throw new Exception('קיים חג "' . $exists['holidayName'] . '": ' . db2date($exists['dateFrom']) . ' - ' . db2date($exists['dateTo']));

			udb::update('sites_periods', ['dateFrom' => $from, 'dateTo' => $till, 'periodName' => $pname], '`periodID` = ' . $periodID);



            $result['status'] = 0;
            break;

			case 'removePeriod':
				$periodID = intval($_POST['periodID']);
				udb::query("DELETE FROM `rooms_min_nights` WHERE `periodID` = " . $periodID);
				udb::query("DELETE FROM `rooms_prices` WHERE `periodID` = " . $periodID);
				udb::query("DELETE FROM `sites_periods` WHERE `periodID` = " . $periodID);
				$result['status'] = 0;
			break;

        default:
            throw new Exception('Unknown action');
    }
}
catch(Exception $e){
    $result['error'] = $e->getMessage();
}


echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
