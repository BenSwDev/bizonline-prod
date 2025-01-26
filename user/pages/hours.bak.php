<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$sid = $_CURRENT_USER->active_site() ?: 0;
if (!$sid)
    return;

//$sid = intval($_POST['sid'] ?? $_GET['sid'] ?? $_CURRENT_USER->active_site());
//
//if (!$_CURRENT_USER->has($sid)){
//    echo '<h2>Access denied</h2>';
//    return;
//}

$dayNames = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];

$extraPriceAfter = udb::single_row("select ExtraPriceAfterTime,ExtraPriceAfterPrice from sites where siteID=".$sid);

if ('POST' == $_SERVER['REQUEST_METHOD']){
    $weekIn = typemap($_POST, [
        'openFrom'  => ['int' => 'time'],
        'openTill'  => ['int' => 'time'],
        'treatFrom' => ['int' => 'time'],
        'treatTill' => ['int' => 'time'],
        'price'     => ['int' => 'int'],
        'discount'  => ['int' => 'int'],
        'holiday'   => ['int' => 'int'],
        'holiday2'  => ['int' => 'int']
    ]);

    $priceAfter = typemap($_POST, [
            'ExtraPriceAfterTime' => 'string',
            'ExtraPriceAfterPrice' => 'int'
    ]);

    //udb::update("sites",['ExtraPriceAfterTime' => $priceAfter['ExtraPriceAfterTime'],'ExtraPriceAfterPrice' => $priceAfter['ExtraPriceAfterPrice']]," siteID=".$sid);

//    foreach($dayNames as $i => $day)
//        udb::insertNull('sites_weekly_hours', [
//            'siteID'     => $sid,
//            'weekday'    => $i,
//            'holidayID'  => 0,
//            'extraPrice' => $weekIn['price'][$i] ?? 0,
//            'packDiscount' => $weekIn['discount'][$i] ?? 0,
//            'isWeekend'  => $weekIn['holiday'][$i] ? 1 : 0,
//            'openFrom'   => $weekIn['openFrom'][$i] ?? null,
//            'openTill'   => $weekIn['openTill'][$i] ?? null,
//            'treatFrom'  => $weekIn['treatFrom'][$i] ?? null,
//            'treatTill'  => $weekIn['treatTill'][$i] ?? null
//        ], true);

    $customIn = typemap($_POST, [
        'custom'      => ['int'],
        'c_active'    => ['int' => 'int'],
        'c_openFrom'  => ['int' => 'time'],
        'c_openTill'  => ['int' => 'time'],
        'c_treatFrom' => ['int' => 'time'],
        'c_treatTill' => ['int' => 'time'],
        'c_price'     => ['int' => 'int'],
        'c_disc'      => ['int' => 'int'],
        'c_holiday'   => ['int' => 'int'],
        'c_holiday2'   => ['int' => 'int']
    ]);

    if ($customIn['custom']){
        udb::query("DELETE FROM `sites_weekly_hours` WHERE `siteID` = " . $sid . " AND `holidayID` IN (" . implode(',', $customIn['custom']) . ")");

        foreach ($customIn['custom'] as $periodID) {
            // skipping inactive periods
//            if (!$customIn['c_active'][$periodID])
//                continue;

            $wdays = udb::single_column("SELECT DISTINCT year.weekday FROM `year` INNER JOIN " . (($periodID < 0) ? "`sites_periods` ON (year.date BETWEEN sites_periods.dateFrom AND sites_periods.dateTo) WHERE sites_periods.siteID = " . $sid . " AND periodID = " . (-$periodID)
                                : "`not_holidays` ON (year.date BETWEEN not_holidays.dateStart AND not_holidays.dateEnd) WHERE not_holidays.notHolidayID = " . $periodID));

            foreach($wdays as $wday)
                udb::insertNull('sites_weekly_hours', [
                    'siteID'     => $sid,
                    'weekday'    => $wday,
                    'holidayID'  => $periodID,
                    'active'     => $customIn['c_active'][$periodID] ? 1 : 0,
                    'isWeekend'  => $customIn['c_holiday'][$periodID] ? 1 : 0,
                    'isWeekend2' => $customIn['c_holiday2'][$periodID] ? 1 : 0,
                    'extraPrice' => $customIn['c_price'][$periodID] ?? 0,
                    'packDiscount' => $customIn['c_disc'][$periodID] ?? 0,
                    'openFrom'   => $customIn['c_openFrom'][$periodID] ?? null,
                    'openTill'   => $customIn['c_openTill'][$periodID] ?? null,
                    'treatFrom'  => $customIn['c_treatFrom'][$periodID] ?? null,
                    'treatTill'  => $customIn['c_treatTill'][$periodID] ?? null
                ], true);
        }
        udb::query("update cronFlags set runCron=1 where `type`='biz-holidays-to-spaplus'");
    }
	$updated = 1;
}

function ns($time){
    return substr($time, 0, 5);
}
?>
<h1>שעות עבודה</h1>
<!--<div style="position:absolute;top: 96px;left:50px;"><?$treatmentStartTimes = udb::single_value("select treatmentStartTimes from sites where siteID=".$sid);?>
    <select name="treatmentStartTimes" id="treatmentStartTimes" >
        <option value="60" <?=($treatmentStartTimes == 60) ? " selected " : "";?>>הכנסת טיפלים כל שעה</option>
        <option value="30" <?=($treatmentStartTimes == 30) ? " selected " : "";?>>הכנסת טיפלים כל חצי שעה</option>
        <option value="15">הכנסת טיפלים כל 15 דקות</option>
    </select>
</div>-->
<div style="position:absolute;top: 96px;left:50px;display: none;">
<?php
    $sameDayOrderHoursBeforeList = ["1","2","3","4","5","6","7","8","9","10","11","12"];
    $sameDayOrderHoursBefore = udb::single_value("select sameDayOrderHoursBefore from sites where siteID=".$sid);
?>
    <label for="sameDayOrderHoursBefore">בהזמנת טיפול ספא באותו היום ,מספר שעות הגבלה לפני הזמנה</label>
    <select name="sameDayOrderHoursBefore" id="sameDayOrderHoursBefore" >
<?php
        foreach($sameDayOrderHoursBeforeList as $item) {
            $selected = $sameDayOrderHoursBefore == $item ? " selected " :"";
            echo '<option value="'.$item.'" '.$selected.' >'.$item.' שעות לפני</option>';
        }
?>
    </select>
</div>
    <style>

    #ui-datepicker-div{z-index:999!important}
td .delete {
    position: absolute;
    top: 50%;
    left: -30px;
    transform: translateY(-50%) scale(0.7);border:2px solid #000;border-radius:30px;cursor:pointer;
}
td .edit {
    position: absolute;
    top: 50%;
    left: 5px;
    transform: translateY(-50%);
    max-width: 20px;
    cursor: pointer;
}

td .edit svg {
    width: 100%;
    height: auto;
    fill: #0dabb6;
}

.priceTable table > tbody > tr > td:first-child {
    text-align: right;
}


    .priceTable table {
        margin-top: 25px;
        margin-bottom: 10px;
        width: 100%;
        border-bottom: 2px solid rgba(0,0,0,0.1);
        box-sizing: border-box;
        border-radius: 5px;
    }
    .priceTable table > thead {
        background: #ffffff;
        border-bottom: 2px solid #f5f5f5;
        line-height: 32px;
        font-weight: bold;
    }

    .priceTable table > thead > tr > th {
        text-align: center;
        border: 2px solid #f5f5f5;
        line-height: 1;
        padding: 10px 4px;
        vertical-align: middle;
        height:40px;
    }
    .priceTable table > thead > tr > th:nth-child(1) {

        text-align: center;

    }
    .priceTable table > tbody > tr {
        line-height: 32px;
        color: #666;
        cursor: pointer;
        font-size: 14px;
    }
    .priceTable table > tbody > tr:nth-child(odd) {
        background: #F9F9F9;
    }
    .priceTable table > tbody > tr > td {position:relative;
        border: 1px solid #f5f5f5;
        padding-right: 10px;
        vertical-align: middle;
        height:40px;
        line-height: 1;
    }
    .priceTable table tbody tr td input[type='text'], .priceTable table tbody tr td input[type='number'],.priceTable table tbody tr td input[type='tel'],.priceTable table tbody tr td select {
        line-height: 32px;
        height: 32px;
        background: #f5f5f5;
        border: 0;
        border-radius: 3px;
        box-sizing: border-box;
        outline: none;
        font-size: 12px;
        padding: 0 5px;
        box-shadow: -1px 1px 0 rgb(0 0 0 / 20%);
        margin: 0 auto;
        width: 32%;
        font-family: 'Rubik', sans-serif;
    }

    .priceTable table tbody tr td textarea {
        width:200px;
        height:45px
    }

    input#submitTreats {
        position: fixed;
        left: 23px;
        bottom: 38px;
        width: 90px;
        height: 50px;
        line-height: 50px;
        color: #ffffff;
        font-weight: bold;
        background: #2FC2EB;
        font-size: 16px;
        margin-top: 20px;
        text-shadow: -1px 1px 0 rgb(0 0 0 / 10%);
        border-bottom: 2px solid rgba(0,0,0,0.1);
        cursor: pointer;
        box-shadow: none;
        -moz-transition: all 0.25s;
        -webkit-transition: all 0.25s;
        transition: all 0.25s;
        text-align: center;
        display: inline-block;
        vertical-align: top;
    }
    .labelTo {
        display: block;
        vertical-align: middle;
        font-weight: bold;
        margin-bottom: 5px;
    }

	.pricetype {
		display: block;
		margin-bottom: -10px;
		margin-top: 10px;
		font-size: 14px;
		font-weight: normal;
	}

    .shekel {padding-right:5px;line-height:32px}

	.pricetype span {
		width: 32%;
		display: inline-block;
	}

    .holidays td {line-height:1}
    td input[type=checkbox] {display:none}
    td input[type=checkbox]+label {position:relative;padding-right:30px;box-sizing:border-box;color:#999999;font-size:14px;cursor:pointer}
    td input[type=checkbox]+label::before {content:'';position:absolute;top:50%;right:0;transform:translateY(-50%);width:20px;height:20px;border:1px solid #999999;box-sizing:border-box;;background:#fff}
    td input[type=checkbox]:checked+label::after {content: '';position: absolute;top: 50%;right: 4px;width: 10px;height: 3px;border-bottom: 2px solid #0dabb6;border-left: 2px solid #0dabb6;transform: translateY(-50%) rotate(-45deg);}
    td input[type=checkbox]:checked+label {color:#0dabb6}

    .addholiday {display:block;max-width:160px;line-height:40px;height:40px;border-radius:8px;margin:0 auto;color:#fff;font-size:16px;background:#0dabb6;cursor:pointer}


tr .only-active {display:none}
tr.active .only-active {display:block}
tr.active span.only-active{display:inline}
    .priceTable table > tbody > tr > td .active {
    position: absolute;
    top: 50%;
    right: -30px;
    transform: translateY(-50%);
}

tr .closethis {
    line-height: 32px;
	float:right;
}

tr .closethis span {
    width: 24px;
    height: 24px;
    display: inline-block;
    background: white;
    border: 1px black solid;
    box-sizing: border-box;
    margin-top: 3px;
    position: relative;
}

tr .closethis.checked span {
    background: #0dabb6;
}

tr .closethis.checked ~ span{display:none}

tr .closethis.checked span::after {
    content: "";
    position: absolute;
    top: 50%;
    right: 4px;
    width: 10px;
    height: 3px;
    border-bottom: 2px solid #fff;
    border-left: 2px solid #fff;
    transform: translateY(-50%) rotate(-45deg);
}

td .active input[type=checkbox]:checked+label::before {
    background: #0dabb6;border-color:#000;transition:all .2s ease;
}

td .active  input[type=checkbox]:checked+label::after {
    border-color: #FFF;
}
.default {
    display: inline-block;
}

td .default input[type=checkbox]+label {
    max-width: 70px;
    display: inline-block;
    padding-right: 25px;
}
.holiday_pop .inputWrap.half{max-width:48%;margin:0 1% 10px 1%}
    /*select{background:0 0;font-size:20px;color:#333;padding:0 10px;box-sizing:border-box}*/
    @media(max-width:900px){
        .priceTable table tbody tr td input[type='text'] {width:98%}
.holiday_pop .inputWrap.half{max-width:100%}
    }
.lock-td{position:relative;pointer-events:none;}
.lock-td::after{position:absolute;left:0;right:0;top:0;bottom:0;z-index:8}
</style>
<?php
if (count($_CURRENT_USER->sites()) > 1){
    $sname = udb::key_row("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")",'siteID');
?>
    <div class="site-select">
		    <label for="sid" class="labelTo">בחר מתחם</label>
            <select name="sid" id="sid" title="שם מתחם" onchange="location.href = '?page=extras&sid=' + this.value">
                <?php
                foreach($sname as $id => $name) {
                    echo '<option value="' , $name['siteID'] , '" ' , ($name['siteID'] == $sid ? 'selected' : '') , '>' , $name['siteName'] , '</option>';
                }
                ?>
            </select>
        </div>
<?php
}

$allTimes = udb::key_row("SELECT * FROM `sites_weekly_hours` AS `se` WHERE `siteID` = " . $sid, ['holidayID', 'weekday']);
$times = $allTimes[0] ?? [];

$custom = udb::key_row("SELECT -`periodID` AS `id`, `dateFrom` AS `dateStart`, `dateTo` AS `dateEnd`, `holidayID`, `periodName` AS `title` FROM `sites_periods` WHERE `periodType` = 0 AND `dateTo` >= '" . date('Y-m-d') . "' AND `siteID` = " . $sid . " ORDER BY `dateEnd`", 'id');
//$holidays = udb::key_row("SELECT `notHolidayID` AS `id`, `notHolidayName` AS `title`, `dateStart`, `dateEnd` FROM `not_holidays` WHERE `dateEnd` > '" . date('Y-m-d') . "' AND `active` = 1 ORDER BY `dateStart`", 'id');
$holidays = [];

// removing holidays that already exist in custom periods
foreach($custom as $period)
    if ($period['holidayID'])
        unset($holidays[$period['holidayID']]);

// combining both and sorting by start date
$custom = $custom + $holidays;
uasort($custom, function($a, $b){
    return $a['dateStart'] <=> $b['dateStart'];
});
?>
    <form method="post">
        <input type="hidden" name="sid" id="sid" value="<?=$sid?>" />
<div class="priceTable">

<?php
if (!$times)
    echo '<table><tr><td><i>לא הוגדרו תוספות</i></td></tr></table>';

?>
    <table>
        <thead>
            <tr>
                <th>ימים</th>
                <th>שעות פתיחה</th>
                <th>שעות טיפולים</th>
                <th>תוספת לאדם</th>
                <th>הנחת חבילה</th>
                <th>תמחור תוספים</th>
                <th>תעריף מטפל</th>
            </tr>
        </thead>
    <tbody>
<?php
    $isSuper = ($_CURRENT_USER->access(TfusaBaseUser::ACCESS_SUPER) == TfusaBaseUser::ACCESS_SUPER);
    foreach($dayNames as $dayID => $dayName){
?>
        <tr>
            <td width="120"><?=$dayName?></td>
            <td class="lock-td">
                <input  type="text" class="stime" name="openFrom[<?=$dayID?>]" value="<?=ns($times[$dayID]['openFrom'] ?: '')?>" title="" />
                <input  type="text" class="stime" name="openTill[<?=$dayID?>]" value="<?=ns($times[$dayID]['openTill'] ?: '')?>" title="" />
            </td>
            <td class="lock-td">
                <input  type="text" class="stime" name="treatFrom[<?=$dayID?>]" value="<?=ns($times[$dayID]['treatFrom'] ?: '')?>" title="" />
                <input  type="text" class="stime" name="treatTill[<?=$dayID?>]" value="<?=ns($times[$dayID]['treatTill'] ?: '')?>" title="" />
            </td>
            <td class="lock-td"><input  type="text" name="price[<?=$dayID?>]" value="<?=($times[$dayID]['extraPrice'] ?: '')?>" title="" /><span class="shekel">₪</span></td>
            <td><input type="text" name="discount[<?=$dayID?>]" value="<?=($times[$dayID]['packDiscount'] ?: '')?>" title="" /><span class="shekel">%</span></td>
            <td class="<?=($isSuper ? '' : 'lock-td')?>">
                <input  type="checkbox" name="holiday2[<?=$dayID?>]" id="weekend2holiday<?=$dayID?>" value="<?=$dayID?>" <?=($times[$dayID]['isWeekend2'] ? 'checked' : '')?> <?=($isSuper ? 'class="editable weekend2"' : '')?> />
                <label for="weekend2holiday<?=$dayID?>">חג / סופ"ש</label>
            </td>
			<td class="<?=($isSuper ? '' : 'lock-td')?>">
                <input  type="checkbox" name="holiday[<?=$dayID?>]" id="weekendholiday<?=$dayID?>" value="<?=$dayID?>" <?=($times[$dayID]['isWeekend'] ? 'checked' : '')?> <?=($isSuper ? 'class="editable weekend"' : '')?> />
                <label for="weekendholiday<?=$dayID?>">חג / סופ"ש</label>
            </td>
        </tr>
<?php
    }
?>
		<tr>
			<td>לאחר השעה</td>
			<td>
				<select  name="ExtraPriceAfterTime" id="ExtraPriceAfterTime" disabled>
                    <option value="">בחרו שעה</option>
                    <?for($t=9 ; $t < 22;$t++){
                        $showTime = ($t < 10 ? "0" : "") . $t . ":00";
                        $selected = "";
                        if($showTime == $extraPriceAfter['ExtraPriceAfterTime']) $selected = " selected ";
                        ?>
                        <option value="<?=$showTime?>" <?=$selected?>><?=$showTime?></option>
                    <?}?>
                </select>
			</td>
			<td>תוספת כללית בכל יום</td>
			<td><input type="tel" name="ExtraPriceAfterPrice" id="ExtraPriceAfterPrice" disabled value="<?=$extraPriceAfter['ExtraPriceAfterPrice']?>"><span class="shekel">₪</span></td>
			<td></td>
		</tr>

    </tbody>
</table>
    <div style="text-align: right">
        <style>
            .mainSectionWrapper input[type=tel] {width:50px;height:42px;line-height:40px;border:1px solid #ccc;box-sizing:border-box;border-radius:4px;padding:0 10px;}
            .inputWrap {
                position: relative;
                display: inline-block;}
        </style>
		<?/*
        <div class="mainSectionWrapper">
            <div class="inputWrap ">
                <label for="ExtraPriceAfterTime">לאחר השעה</label>
                <select name="ExtraPriceAfterTime" id="ExtraPriceAfterTime" disabled>
                    <option value="">בחרו שעה</option>
                    <?for($t=9 ; $t < 22;$t++){
                        $showTime = ($t < 10 ? "0" : "") . $t . ":00";
                        $selected = "";
                        if($showTime == $extraPriceAfter['ExtraPriceAfterTime']) $selected = " selected ";
                        ?>
                        <option value="<?=$showTime?>" <?=$selected?>><?=$showTime?></option>
                    <?}?>
                </select>

            </div>
            <div class="inputWrap ">
                <label for="ExtraPriceAfterPrice">תוספת מחיר</label>
                <input type="tel" name="ExtraPriceAfterPrice" id="ExtraPriceAfterPrice" disabled value="<?=$extraPriceAfter['ExtraPriceAfterPrice']?>">

            </div>
        </div>
		*/?>
    </div>
<div class="addholiday" onclick="holidayPop()">הוסף חג / מועד</div>
<div class="holidays">
<table>
    <thead>
        <tr>
            <th>חגים ומועדים</th>
            <th>שעות פתיחה</th>
            <th>שעות טיפולים</th>
            <th>תוספת לאדם</th>
            <th>הנחת חבילה</th>
            <th>תמחור תוספים</th>
            <th>תעריף מטפל</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach($custom as $periodID => $period){
        $tdata = isset($allTimes[$periodID]) ? reset($allTimes[$periodID]) : [];
?>
        <tr class="<?=($tdata['active'] ? 'active' : '')?>" >
            <td width="120" style="padding-left:25px">
                <input type="hidden" name="custom[]" value="<?=$periodID?>" />
                <div class="active">
                    <input type="checkbox" name="c_active[<?=$periodID?>]" <?=($tdata['active'] ? 'checked' : '')?> id="c_active<?=$periodID?>"  value="1" />
                    <label for="c_active<?=$periodID?>"></label>
                </div>
                <?=($period['title'] ?: '<i>ללא שם</i>')?>
                <div class="date" style="direction:ltr;text-align:right"><?=(strcmp($period['dateStart'], $period['dateEnd']) ? db2date($period['dateStart'], '.', 2) . ' - ' . db2date($period['dateEnd'], '.', 2) : db2date($period['dateStart'], '.', 2))?></div>
<?php
        if ($periodID < 0){
?>
                <div class="edit" onclick="holidayPop(<?=-$periodID?>, '<?=str_replace("'", "\'", $period['title'])?>', '<?=db2date($period['dateStart'])?>', '<?=db2date($period['dateEnd'])?>')"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M7.127 22.562l-7.127 1.438 1.438-7.128 5.689 5.69zm1.414-1.414l11.228-11.225-5.69-5.692-11.227 11.227 5.689 5.69zm9.768-21.148l-2.816 2.817 5.691 5.691 2.816-2.819-5.691-5.689z"/></svg></div>
<?php
        }
?>
            </td>
            <td>
                <span class="only-active closethis <?=(($tdata['openFrom'] == $tdata['openTill'] && $tdata['openTill']) ? 'checked' : '')?>">
					<span></span>
					<label for="">מושבת</label>
				</span>
				<span class="only-active">
                    
					<input type="text" class="stime" name="c_openFrom[<?=$periodID?>]" data-val="<?=ns($tdata['openFrom'])?>" value="<?=ns($tdata['openFrom'])?>" placeholder="ללא שינוי" title="" />
                    <input type="text" class="stime" name="c_openTill[<?=$periodID?>]" data-val="<?=ns($tdata['openTill'])?>" value="<?=ns($tdata['openTill'])?>" placeholder="ללא שינוי" title="" />
                </span>
            </td>
            <td>
                <span class="only-active closethis <?=(($tdata['treatFrom'] == $tdata['treatTill'] && $tdata['treatTill']) ? 'checked' : '')?>">
					<span></span>
					<label for="">מושבת</label>
				</span>
				<span class="only-active">
                    <input type="text" class="stime" name="c_treatFrom[<?=$periodID?>]" data-val="<?=ns($tdata['treatFrom'])?>" value="<?=ns($tdata['treatFrom'])?>" placeholder="ללא שינוי" title="" />
                    <input type="text" class="stime" name="c_treatTill[<?=$periodID?>]" data-val="<?=ns($tdata['treatTill'])?>"  value="<?=ns($tdata['treatTill'])?>" placeholder="ללא שינוי" title="" />
                </span>
            </td>
            <td>
                <div class="only-active">
                    <input type="text" name="c_price[<?=$periodID?>]" value="<?=($tdata['extraPrice'] ?: '')?>" title="" /><span class="shekel">₪</span>
                </div>
            </td>
            <td>
                <div class="only-active">
                    <input type="text" name="c_disc[<?=$periodID?>]" value="<?=($tdata['packDiscount'] ?: '')?>" title="" /><span class="shekel">%</span>
                </div>
            </td>
			<td>
                <div class="only-active">
                    <input type="checkbox" name="c_holiday2[<?=$periodID?>]" id="choliday2_<?=$periodID?>" value="1" <?=($tdata['isWeekend2'] ? 'checked' : '')?> />
                    <label for="choliday2_<?=$periodID?>">חג / סופ"ש</label>
                </div>
			</td>
            <td>
                <div class="only-active">
                    <input type="checkbox" name="c_holiday[<?=$periodID?>]" id="choliday<?=$periodID?>" value="1" <?=($tdata['isWeekend'] ? 'checked' : '')?> />
                    <label for="choliday<?=$periodID?>">חג / סופ"ש</label>
                </div>
<?php
        if ($periodID < 0){
?>
                <div class="delete" onclick="removePeriod(<?=$periodID?>)"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30"><path fill-rule="evenodd" fill="rgb(0, 0, 17)" d="M25.6 25.6C19.8 31.5 10.2 31.5 4.4 25.6 -1.5 19.8-1.5 10.2 4.4 4.4 10.2-1.5 19.8-1.5 25.6 4.4 31.5 10.2 31.5 19.8 25.6 25.6ZM5.7 5.7C0.5 10.8 0.5 19.2 5.7 24.3 10.8 29.5 19.2 29.5 24.3 24.3 29.5 19.2 29.5 10.8 24.3 5.7 19.2 0.5 10.8 0.5 5.7 5.7ZM20.6 10.7L16 15.2 20.6 19.8C21 20.2 21 20.7 20.6 21.1 20.3 21.4 19.7 21.4 19.3 21.1L14.8 16.5 10.5 20.8C10.2 21.1 9.6 21.1 9.2 20.8 8.9 20.4 8.9 19.9 9.2 19.5L13.5 15.2 9.2 10.9C8.8 10.6 8.8 10 9.2 9.7 9.5 9.3 10.1 9.3 10.5 9.7L14.8 14 19.4 9.4C19.7 9 20.3 9 20.6 9.4 21 9.7 21 10.3 20.6 10.7Z"></path></svg></div>
<?php
        }
?>
            </td>
        </tr>
<?php
    }
?>
    </tbody>
</table>
</div>
<div class="addholiday" onclick="$(this).closest('form').submit()">שמור</div>
</div></form>
<script>
    $(function() {
        $('div.active input').change(function() {
            if($(this).is(':checked')) {
                $(this).closest('tr').addClass('active');
            } else {
                $(this).closest('tr').removeClass('active');
            }
        });
        $('div.default input').change(function() {
            if($(this).is(':checked')) {
                $(this).closest('td').find('input').prop('readonly', true);
            } else {
                $(this).closest('td').find('input').prop('readonly', false);
            }
        });

        $("#treatmentStartTimes").on("change",function(){
            $.ajax({
                method: "POST",
                url: "ajax_global.php",
                data: {act: "treatmentStartTimes" , treatmentStartTimes: $("#treatmentStartTimes").val()},
                success:function(response){

                },
                fail:function(response){

                }
            });
        });

		$('.closethis').on('click',function(){
			if($(this).hasClass('checked')){
				$(this).removeClass('checked');
				$(this).closest('td').find('input.stime').each(function(){
					$(this).val($(this).data('val'));
				});
			}else{
				$(this).addClass('checked');
				$(this).closest('td').find('input.stime').each(function(){
					//debugger;
					$(this).data('val',$(this).val());
					$(this).attr('data-val',$(this).val());
					$(this).val('00:00')
				})
			}
		});

        $("#sameDayOrderHoursBefore").on("change",function(){
            $.ajax({
                method: "POST",
                url: "ajax_global.php",
                data: {act: "sameDayOrderHoursBefore" , sameDayOrderHoursBefore: $("#sameDayOrderHoursBefore").val()},
                success:function(response){

                },
                fail:function(response){

                }
            });
        });

    })
</script>


<style>
.priceTable table > tbody > tr > td:last-child {
    white-space: nowrap;
}
.priceTable table tbody tr td .default+input[type='text'], .priceTable table tbody tr td .default+input[type='text']+input[type='text'] {width:50px}
.holiday_pop{z-index:99;display:block;position:fixed;top:0;right:0;left:0;bottom:0;width:100%;height:100%;background:rgba(0,0,0,.6)}
.holiday_pop .container{position:absolute;top:50%;right:50%;transform:translateY(-50%) translate(50%);width:calc(100% - 10px);max-width:650px;height:100%;max-height:calc(100vh - 60px);background:#f5f5f5;border-radius:8px;overflow:auto}
.holiday_pop .container .close{position:absolute;top:14px;left:14px;cursor:pointer;z-index:2}
.holiday_pop .container .close svg{fill:#aaa;width:17px;height:17px}
.holiday_pop .container>.title{display:block;font-weight:500;color:#333;font-size:30px;text-align:center;padding:12px 0 13px;background:#fff;box-shadow:0 0 10px rgba(0,0,0,.5);z-index:1;position:relative}
.holiday_pop .container>.title .domain-icon {width: 40px;height: 40px;top: 10px;right: 10px;}
.holiday_pop .form{display:block;padding:20px;box-sizing:border-box;font-size:0;overflow:auto;position:absolute;left:0;right:0;top:60px;bottom:0;height:auto}
.holiday_pop .inputWrap svg{position:absolute;top:50%;left:10px;transform:translateY(-50%);fill:#0dabb6}
.holiday_pop .inputWrap{border-radius:3px;font-size:14px;filter:drop-shadow(0 1px 1px rgba(2, 3, 3, .1));position:relative;height:auto;min-height:60px;background-color:#fff;border:1px solid #eee;display:inline-block;width:100%;max-width:98%;margin:0 1% 10px 1%;box-sizing:border-box}
.holiday_pop .inputWrap.date.four{max-width:58%}
.holiday_pop .inputWrap.date.time.four{max-width:38%}
.holiday_pop .inputWrap>label{position:absolute;top:3px;transform:none;right:5px;font-size:14px;color:#0dabb6;font-weight:500;line-height:1;transition:all .2s ease}
.holiday_pop .inputWrap.signature>label{font-size:20px}
.holiday_pop .inputWrap>input.empty:not(:focus)+label{font-size:20px;font-weight:400;top:50%;transform:translateY(-50%);padding-right:10px;opacity:.5}
.holiday_pop .inputWrap>input{font-size:20px;position:absolute;top:0;right:0;left:0;bottom:0;width:100%;height:100%;background:0 0;padding:0 10px;box-sizing:border-box;z-index:2;color:#333}
.holiday_pop .inputWrap>textarea{color:#000;font-size:20px;width:100%;height:100%;background:0 0;padding:20px 10px 10px;box-sizing:border-box;-webkit-transform:translateZ(0);-webkit-overflow-scrolling:touch}
.holiday_pop .inputWrap.submit{background:#e73219;color:#fff;text-align:center;font-size:30px;font-weight:500;cursor:pointer;border-radius:3px}

</style>

<div class="holiday_pop" id="holidayPop" style="display:none">
    <div class="container">
        <div class="close" onclick="$('.holiday_pop').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title mainTitle">
            <div class="domain-icon" style="background-image:url(/user/assets/domains/biz.jpg)"></div>הוספה/עריכת מועד/חג
        </div>
        <form class="form" id="orderForm" action="" data-guid="" method="post" autocomplete="off" data-defaultagr="0">
            <input type="hidden" name="periodID" id="periodID" value="0" />
            <div class="inputWrap ">
                <input type="text" name="periodNameI" id="name" value="">
                <label for="name">שם</label>
            </div>
            <div class="inputWrap half">
                <input type="text" class="datepicker" name="periodStartI" id="from" value="">
                <label for="from">מ</label>
            </div>
            <div class="inputWrap half">
                <input type="text" class="datepicker" name="periodEndI" id="till" value="">
                <label for="till">עד</label>
            </div>
            <div class="statusBtn">
                <button type="button" id="npBtn" onclick="" class="inputWrap submit">שמירה</button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript" src="//www.spaplus.co.il/js/jquery.ui.custom.min.js"></script>
<script type="text/javascript" src="//www.spaplus.co.il/datepicker/jquery.ui.datepicker-he.js"></script>
<?if($updated){?>
<script>
window.addEventListener('load', function() {
 swal.fire({title: 'עודכן בהצלחה!',  icon: 'success'});
})
</script>
<?}?>
<script>
$(function() {
    $.datepicker.setDefaults( $.datepicker.regional[ "he" ] );

    $( ".datepicker" ).datepicker({
        minDate: '<?=date('d/m/Y')?>'
    });

    $('.stime').datetimepicker({
        datepicker: false,
        format: 'H:i',
        step: 30,
        defaultTime: '09:00'
    });

    $(".editable.weekend").on('change', function(){
        $.post('ajax_global_spa.php', {act:'saveWeekendSpa', day:this.value, is:this.checked ? 1 : 0}).then(function(res){
            if (!res || res.status === undefined || parseInt(res.status))
                return swal.fire({title: 'שגיאה!', html: res ? (res.error || res._txt) : '', icon: 'error'});
        });
    });

	$(".editable.weekend2").on('change', function(){
        $.post('ajax_global_spa.php', {act:'saveWeekendSpa2', day:this.value, is:this.checked ? 1 : 0}).then(function(res){
            if (!res || res.status === undefined || parseInt(res.status))
                return swal.fire({title: 'שגיאה!', html: res ? (res.error || res._txt) : '', icon: 'error'});
        });
    });
});

function submitHolidayPop(prm){
    return $.post('ajax_global_spa.php', prm).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({title: 'שגיאה!', html: res.error || res._txt || '', icon: 'error'});

        window.location.reload();
    });
}

function removePeriod(pid){
    return $.post('ajax_global_spa.php', {act:'removePeriod', asite:<?=$sid?>, periodID:-pid}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({title: 'שגיאה!', html: res.error || res._txt || '', icon: 'error'});

        window.location.reload();
    });
}

// 'act=newPeriod&siteID=<?=$sid?>&' + $('#orderForm').serialize()

function holidayPop(id, name, from, till){
    var form = $('#orderForm');

    form.find('input[name="periodID"]').val(id || 0);
    form.find('input[name="periodNameI"]').val(name || '');
    form.find('input[name="periodStartI"]').datepicker('setDate', from || null);
    form.find('input[name="periodEndI"]').datepicker('setDate', till || null);

    $('#holidayPop').fadeIn('fast');

    $('#npBtn').off('click').on('click', function(){
        var prm = 'act=' + (id ? 'editPeriod' : 'newPeriod') + '&asite=<?=$sid?>&' + $('#orderForm').serialize();
        submitHolidayPop(prm);
    });




//    $('#pricePeriods').children('.item').add('#addPeriodBtn').on('click', function(){
//
//        if($(this).data('period')) {
//            $(this).addClass('active').siblings('.active').removeClass('active');
//
//            $('#minRoom0').localLoader('show');
//            $.post('ajax_global.php', {act:'getMin', asite:392,  periodID:$(this).data('value')}).then(function(res){
//                if (res.status === undefined || parseInt(res.status))
//                    return swal('שגיאה', 'לא מצליח לעדכן תקופה', 'error');
//
//                $('#minRoom0').empty().html(res.html).localLoader('hide');
//                $('.pricecutfrom')[res.sync ? 'show' : 'hide']();
//
//                window.setTimeout(function(){
//                    $('#periodStartE').datepicker({
//                        minDate: 0,
//                        beforeShowDay: function(day){
//                            var pd = [day.getFullYear(), ('0' + (day.getMonth() + 1)).substr(-2), ('0' + day.getDate()).substr(-2)].join('-'), res = [true, ''], vd = this.value.split('/').reverse().join('-');
//                            $.each(periods, function(from, till){
//                                if (pd < from)
//                                    return false;
//                                if (pd <= till && pd >= from && !(vd <= till && vd >= from))
//                                    return (res = [false, '']) && false;
//                            });
//                            return res;
//                        }
//                    });
//
//                    $('#periodEndE').datepicker({
//                        minDate: 0,
//                        beforeShow: function(){
//                            var start = $('#periodStartE').val(), pre = {maxDate: null}, vd = this.value.split('/').reverse().join('-'), tmp;
//
//                            if (start.length){
//                                pre.minDate = start;
//
//                                tmp = start.split('/').reverse().join('-');
//                                $.each(periods, function(from, till){
//                                    if (tmp < from && vd != till){
//                                        pre.maxDate = from.split('-').reverse().join('/');
//                                        return false;
//                                    }
//                                });
//                            }
//                            return pre;
//                        },
//                        beforeShowDay: function(day){
//                            var pd = [day.getFullYear(), ('0' + (day.getMonth() + 1)).substr(-2), ('0' + day.getDate()).substr(-2)].join('-'), res = [true, ''], vd = this.value.split('/').reverse().join('-');
//                            $.each(periods, function(from, till){
//                                if (pd < from)
//                                    return false;
//                                if (pd <= till && pd >= from && !(vd <= till && vd >= from))
//                                    return (res = [false, '']) && false;
//                            });
//                            return res;
//                        }
//                    });
//                }, 1000);
//
//                setMinTriggers('#minRoom0', caller);
//            });
//        } else {
//            $('#newPeriod').fadeIn('fast');
//        }
//    });
}
</script>
