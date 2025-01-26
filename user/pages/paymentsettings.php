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


$protel = udb::single_row("SELECT `protelID`, `protel_config` FROM `sites` WHERE `siteID` = " . $sid);
$protel_cfg = $protel['protel_config'] ? (json_decode($protel['protel_config'], true) ?: []) : [];

//$sett = udb::single_value("SELECT `salaryDefault` FROM `sites` WHERE `siteID` = " . $sid);
//$sett = $sett ? json_decode($sett, true) : [];

$today = date('Y-m-d');

//$defDate = $sett ? date('01.m.Y', strtotime('next month')) : date('d.m.Y');

//$nextChange = udb::key_row("SELECT `salaryType`, `salaryDay`, `salaryRate`, `startFrom` FROM `salaryLog` WHERE `startFrom` > CURDATE() AND `targetType` = 'site' AND `targetID` = " . $sid . " ORDER BY `logID`", ['salaryType', 'salaryDay']);

$salary = new SalarySite($sid);

$todayMinute = $salary->get_day_salary($today, 'minute');
$todayPercent = $salary->get_day_salary($today, 'percent');

$lastSalary  = $salary->get_last_salary();

$defDate = $lastSalary['type']->date ? date('01.m.Y', strtotime('next month')) : date('d.m.Y');
?>
<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=paymentsettings&v=<?=rand()?>" rel="stylesheet">
<h2 class="title">הגדרות שכר</h2>
<div class="pers">
   <div class="per per-minute">
      <input type="radio" name="per" id="perminute" value="minute" <?=($lastSalary['type']->value == 'minute' ? 'checked' : '')?> />
      <label for="perminute">לפי דקה</label>
      <div>
<?php
    $last = $lastSalary['minute']['wday'];
    $class = (strcmp($last->date, $today) > 0) ? 'changed' : 'edit';
?>
         <div class="reg-days">
            <span class="visible">
                <span class="amount">₪<span><?=($todayMinute->rateRegular ?: '-')?></span></span>
                <span class="title">ימים רגילים</span>
            </span>
            <div class="editable">
                <input type="text" name="minute[wday]" id="shekelamount_days" value="<?=($last->value ?: '')?>" title="" />
                <span>₪ החל מ-</span>
                <input type="text" name="sminute[wday]" class="dtstart" value="<?=$defDate?>" title="" />
            </div>
            <div class="btn <?=$class?>">
               <div class="edit-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="save-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="changed-label">
                   <div><span>ישתנה ל</span>-₪<?=($last->value ?: '')?></div>
                   <div><span>החל מ</span>-<?=db2date($last->date, '.', 2)?></div>
               </div>
            </div>
         </div>
<?php
    $last = $lastSalary['minute']['wend'];
    $class = (strcmp($last->date, $today) > 0) ? 'changed' : 'edit';
?>
         <div class="reg-weekend">
            <span class="visible">
                <span class="amount">₪<span><?=($todayMinute->rateWeekend ?: '-')?></span></span>
                <span class="title">סופ"ש וחגים</span>
            </span>
            <div class="editable">
                <input type="text" name="minute[wend]" id="shekelamount_weekend" value="<?=($last->value ?: '')?>" title="" />
                <span>₪ החל מ-</span>
                <input type="text" name="sminute[wend]" class="dtstart" value="<?=$defDate?>" title="" />
            </div>
            <div class="btn <?=$class?>">
                <div class="edit-label">
                  <div>שנה תעריף</div>
                </div>
                <div class="save-label">
                  <div>שנה תעריף</div>
                </div>
                <div class="changed-label">
                    <div><span>ישתנה ל</span>?-₪<?=($last->value ?: '')?></div>
                    <div><span>החל מ</span>-<?=db2date($last->date, '.', 2)?></div>
                </div>
            </div>
         </div>
      </div>
   </div>

   <div class="per per-percent">
      <input type="radio" name="per" id="perpercent" value="percent" <?=($lastSalary['type']->value == 'percent' ? 'checked' : '')?> />
      <label for="perpercent">לפי אחוזים</label>
      <div>
<?php
    $last = $lastSalary['percent']['wday'];
    $class = (strcmp($last->date, $today) > 0) ? 'changed' : 'edit';
?>
         <div class="reg-days">
             <span class="visible">
                <span class="amount"><span><?=($todayPercent->rateRegular ?: '-')?></span>%</span>
                <span class="title">ימים רגילים</span>
             </span>
             <div class="editable">
                 <span>%</span>
                <input type="text" name="percent[wday]" id="percentamount_days" value="<?=($last->value ?: '')?>" title="" />
                 <span> החל מ-</span>
                 <input type="text" name="spercent[wday]" class="dtstart" value="<?=$defDate?>" title="" />
             </div>
             <div class="btn <?=$class?>">
               <div class="edit-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="save-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="changed-label">
                   <div><span>ישתנה ל</span>-<?=($last->value ?: '')?>%</div>
                   <div><span>החל מ</span>-<?=db2date($last->date, '.', 2)?></div>
               </div>
            </div>
         </div>
         <div class="reg-weekend">
<?php
    $last = $lastSalary['percent']['wend'];
    $class = (strcmp($last->date, $today) > 0) ? 'changed' : 'edit';
?>
             <span class="visible">
                <span class="amount"><span><?=($todayPercent->rateWeekend ?: '-')?></span>%</span>
                <span class="title">סופ"ש וחגים</span>
             </span>
             <div class="editable">
                 <span>%</span>
                <input type="text" name="percent[wend]" id="percentamountt_weekend" value="<?=($last->value ?: '')?>" title="" />
                 <span> החל מ-</span>
                 <input type="text" name="spercent[wend]" class="dtstart" value="<?=$defDate?>" title="" />
             </div>
            <div class="btn <?=$class?>">
               <div class="edit-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="save-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="changed-label">
                   <div>ישתנה ל-<?=($last->value ?: '')?>%</div>
                   <div>החל מ-<?=db2date($last->date, '.', 2)?></div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<?
list($bookBefore, $exactIncome) = udb::single_row("SELECT `bookBefore`, `showExactIncome` FROM `sites` WHERE `siteID` = " . $sid, UDB_NUMERIC);

//$sameDayOrderHoursBefore = udb::single_value("SELECT `sameDayOrderHoursBefore` FROM `sites` WHERE `siteID` = " . $sid);
$source = udb::single_value("SELECT `sourceRequired` FROM `sites` WHERE `siteID` = " . $sid);
$address = udb::single_value("SELECT `addressRequired` FROM `sites` WHERE `siteID` = " . $sid);
$blockDelete = udb::single_value("SELECT `blockDelete` FROM `sites` WHERE `siteID` = " . $sid);
$blockAutoSend = udb::single_value("SELECT `blockAutoSend` FROM `sites` WHERE `siteID` = " . $sid);
$hideUnfilled = udb::single_value("SELECT `hideUnfilled` FROM `sites` WHERE `siteID` = " . $sid);
$autoHidePrice = udb::single_value("SELECT `autoHidePrice` FROM `sites` WHERE `siteID` = " . $sid);
$enableReminders = udb::single_value("SELECT `enableReminders` FROM `sites` WHERE `siteID` = " . $sid);
$sendReminderHour = udb::single_value("SELECT `sendReminderHour` FROM `sites` WHERE `siteID` = " . $sid);
$calendarSettings = udb::single_value("SELECT `calendarSettings` FROM `sites` WHERE `siteID` = " . $sid);
$cancelCond = udb::single_value("SELECT `cancelCondSpa` FROM `sites` WHERE `siteID` = " . $sid);	 
$mustRoom = udb::single_value("SELECT `roomRequired` FROM `sites` WHERE `siteID` = " . $sid);

$siteCancelCond = json_decode($cancelCond ?: '[]', TRUE);


$remarksShort =		floor($calendarSettings/1)%10? 1 : 0;
$roomsShort =		floor($calendarSettings/10)%10? 1 : 0;
$therapistShort =	floor($calendarSettings/100)%10? 1 : 0;
$hidefaces =		floor($calendarSettings/1000)%10? 1 : 0;
$groupTreat =		floor($calendarSettings/10000)%10? 1 : 0;
$timeStart =		floor($calendarSettings/100000)%10? 1 : 0;




?>
<div class="checks-flex">
	<div class="checks-wrap">
		<h2 class="title" style="margin-top:40px">תפקוד הזמנה</h2>
		<div class="checks">
			<input type="checkbox" name="sourceRequired" id="sourceRequired" value="1" <?=($source == 1 ? 'checked' : '')?> />
			<label for="sourceRequired">חייב בחירת מקור הגעה</label>
		</div>
        <div class="checks">
            <input type="checkbox" name="roomRequired" id="roomRequired" value="1" <?=($mustRoom == 1 ? 'checked' : '')?> />
            <label for="roomRequired">חייב בחירת חדר לטיפול</label>
        </div>
		<div class="checks">
			<input type="checkbox" name="addressRequired" id="addressRequired" value="1" <?=($address? 'checked' : '')?> />
			<label for="addressRequired">הצג  כתובת</label>
			<div class="checks">
				<input type="checkbox" name="addressRequired" id="addressRequired2" value="1" <?=($address == 2 ? 'checked' : '')?> />
				<label for="addressRequired2">חייב הזנת כתובת</label>
			</div>
		</div>
		<div class="checks">
			<input type="checkbox" name="blockDelete" id="blockDelete" value="1" <?=($blockDelete == 1 ? 'checked' : '')?> />
			<label for="blockDelete">מנע מחיקה מוחלטת</label>
		</div>
		<div class="checks">
			<input type="checkbox" name="blockAutoSend" id="blockAutoSend" value="1" <?=($blockAutoSend == 1 ? 'checked' : '')?> />
			<label for="blockAutoSend">מנע שליחת הזמנה אוטומטית</label>
		</div>
		<?/*
		<div class="checks" style="display:none">
			<input type="checkbox" name="hideUnfilled" id="hideUnfilled" value="1" <?=($hideUnfilled == 1 ? 'checked' : '')?> />
			<label for="hideUnfilled">הסתר שדות שלא מולאו באישור הזמנה</label>
		</div>
		*/?>
		<div class="checks">
			<input type="checkbox" name="autoHidePrice" id="autoHidePrice" value="1" <?=($autoHidePrice == 1 ? 'checked' : '')?> />
			<label for="autoHidePrice">הסתר מחיר כברירת מחדל</label>
		</div>
		<div class="checks withselect">
			<input type="checkbox" name="enableReminders" id="enableReminders" value="1" <?=($enableReminders == 1 ? 'checked' : '')?> />
			<label for="enableReminders">שליחת תזכורת והצהרת בריאות יום לפני בשעה 18:00</label>
			<select name="sendReminderHour" id="sendReminderHour" style="display:none">
				<?for($i=9; $i<=21;$i++){?>
					<option value="<?=$i?>" <?=sprintf("%02d",$i) == substr($sendReminderHour,0,2)? "selected": ""?>><?=sprintf("%02d",$i)?>:00</option>
				<?}?>
			</select>
			<style>
					
			</style>
		</div>
		<div class="checks" id="siteCancelCond">
			<input type="checkbox" name="allowCancel" id="typeCancelMain" value="1" <?=($siteCancelCond['allowCancel'] == 1 ? 'checked' : '')?> />
			<label for="typeCancelMain">אפשר ביטול הזמנה ע"י לקוח
				<select name="cancelType" style="margin:-5px 10px">
					<option value="0">כל ההזמנות</option>
					<option value="1" <?=$siteCancelCond['cancelType']==1? "selected" : ""?>>אונליין בלבד</option>
				</select>
			</label>	
			<?
			unset($siteCancelCond['cancelType']);
			unset($siteCancelCond['allowCancel']);
			$cancelArray = current($siteCancelCond);	  
			for($m=1;$m<=1;$m++) { ?>
                    <div class="cancelLine" <?=($m>1)? "style='display:none'" : ""?>>
                        <span><?//=$m?></span>
						<span style="padding-left: 10px">עד</span>
						<?/*
						<input type="number" placeholder="" value="<?=strpos(key($siteCancelCond),"Warning") === false ? key($siteCancelCond) : "5"?>" name="daysCancel[<?=$m?>]">
						*/?>
						<input type="number" placeholder="" value="<?=key($siteCancelCond) ? key($siteCancelCond) : ($m==1? "5" : "")?>" name="daysCancel[<?=$m?>]">
						<span style="padding:0 10px">ימים לפני, דמי ביטול של </span>
						<input type="number" placeholder="" readonly value="0<?//=$cancelArray <= 1?$cancelArray*100:$cancelArray?>" name="costCancel[<?=$m?>]">
						<select name="typeCancel[<?=$m?>]" style="display:none">
							<option value="">-</option>
							<option value="1" <?=$cancelArray > 1?"selected":""?>>₪</option>
							<option value="2" <?=$cancelArray <= 1?"selected":""?>>%</option>
						</select>
                    </div>
                    <?php $cancelArray = next($siteCancelCond); 
			} ?>
			<style>
			
			
			</style>
		</div>


        <h2 class="title" style="margin-top:40px">פירוט עסקאות</h2>
        <div class="checks">
            <input type="checkbox" name="exactIncome" id="exactIncome" value="1" <?=($exactIncome == 1 ? 'checked' : '')?> />
            <label for="exactIncome">הצג כפתור "דו"ח הכנסות"</label>
        </div>

<?php
    if($protel['protelID']) {
?>
		<h2 class="title" style="margin-top:40px">פרוטל</h2>
		<div class="checks">
			<input type="checkbox" name="checkinNotification" id="checkinNotification" value="1" <?=($protel_cfg['sms_welcome2'] ? 'checked' : '')?> />
			<label for="checkinNotification">תשלח התראה בתאריך צ'ק אין</label>
		</div>
		<div class="checks">
			<input type="checkbox" name="numdaysbefore_check" id="numdaysbefore_check" value="1" <?=($protel_cfg['sms_welcome1'] ? 'checked' : '')?> />
			<label for="numdaysbefore_check">תשלח התראה
                <select style="width:40px;" name="numdaysbefore">
<?php
        for($i = 1; $i <= 7; ++$i)
            echo '<option value="' , $i , '" ' , ($i == $protel_cfg['sms_welcome1'] ? 'selected' : '') , '>' , $i , '</option>';
?>
                </select> ימים  לפני
            </label>
		</div>
<?php
    }
?>
	</div>

	<div class="checks-wrap calendarCheck">
		<h2 class="title" style="margin-top:40px">יומן תפוסה</h2>
		<?/*
		<div class="checks">
			<input data-valc="1" type="checkbox" name="remarksShort" id="remarksShort" value="1" <?=($remarksShort ? 'checked' : '')?> />
			<label for="remarksShort">תצוגת תקציר הערות</label>
		</div>
		*/?>
		<div class="checks">
			<input data-valc="10" type="checkbox" name="roomsShort" id="roomsShort" value="1" <?=($roomsShort? 'checked' : '')?> />
			<label for="roomsShort">תצוגת טיפולי חדרים מצומצמת</label>			
		</div>
		<div class="checks" >
			<input data-valc="100" type="checkbox" name="therapistShort" id="therapistShort" value="1" <?=($therapistShort? 'checked' : '')?> />
			<label for="therapistShort">הסתרת מחיר ביומן מטפלים</label>			
		</div>
		<div class="checks" >
			<input data-valc="1000" type="checkbox" name="hidefaces" id="hidefaces" value="1" <?=($hidefaces? 'checked' : '')?> />
			<label for="hidefaces">הסתרת מגדר מטופלים</label>			
		</div>
		<div class="checks" >
			<input data-valc="10000" type="checkbox" name="groupTreat" id="groupTreat" value="1" <?=($groupTreat? 'checked' : '')?> />
			<label for="groupTreat">סימון בכחול של טיפולי קבוצות (מעל 5)</label>			
		</div>
		<div class="checks">
			<input data-valc="100000" type="checkbox" name="timeStart" id="timeStart" value="1" <?=($timeStart? 'checked' : '')?> />
			<label for="timeStart">שעת תחילה לפי שעת תחילת פעילות / טיפולים</label>			
		</div>
	</div>
	<div class="checks-wrap">
		<h2 class="title" style="margin-top:40px">הזמנת אונליין</h2>
		<div class="checks">
			<select name="bookBefore" id="bookBefore">
                <option value="0">-- ללא --</option>
<?php
    for($i = 1; $i <= 12; ++$i)
        //echo '<option value="' , $i , '" ' , ($i == $bookBefore ? 'selected' : '') , '>' , ($i == 1 ? 'שעה אחת' : $i . ' שעות') , '</option>';
        echo '<option value="' , $i , '" ' , ($i == $bookBefore ? 'selected' : '') , '>' , ($i == 1 ? 'שעה אחת' :  ' שעות') , '</option>';
?>
                <option value="24" <?=($bookBefore == 24 ? 'selected' : '')?>>שעות</option>
			</select>
			<label for="bookBefore">הגבלת שעות לפני הזמנת אונליין</label>
		</div>
		
	</div>
	<script>
	setTimeout(function(){
		$('#bookBefore option').each(function(){
			if($(this).val()>1){
				$(this).html($(this).val()+" "+$(this).html())
			}
		});
	},1000);
	</script>
<?/*
    <div class="checks-wrap">
        <h2 class="title" style="margin-top:40px">הזמנת אונליין ספא פלוס</h2>
        <div class="checks">
            <select name="sameDayOrderHoursBefore" id="sameDayOrderHoursBefore">
                <option value="0">-- ללא --</option>
                <?php
                for($i = 1; $i <= 12; ++$i)
                    echo '<option value="' , $i , '" ' , ($i == $sameDayOrderHoursBefore ? 'selected' : '') , '>' , ($i == 1 ? 'שעה אחת' : $i . ' שעות') , '</option>';
                ?>
                <option value="24" <?=($sameDayOrderHoursBefore == 24 ? 'selected' : '')?>>24 שעות</option>
            </select>
            <label for="remarksShort">הגבלת שעות לפני הזמנת אונליין</label>
        </div>

    </div>
*/?>

</div>

<script type="text/javascript" src="/assets/js/jquery.ui.datepicker-he.js"></script>
<script>
$(function() {
    $.datepicker.setDefaults( $.datepicker.regional[ "he" ] );  
	
	$('.dtstart').datepicker({
        minDate: 0,
        dateFormat: 'dd.mm.yy'
    });

$('.calendarCheck input[type="checkbox"]').on('change', function(){
    debugger;
    var _val =  ($(this).is(':checked'))? 1 : 0;
    var _valc =  $(this).data('valc');
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'calendarSettings', val:_val,valc:_valc}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});






$('#hideUnfilled').on('change', function(){
    //debugger;
    var hideUnfilled =  ($(this).is(':checked'))? 1 : 0;
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'hideUnfilled', val:hideUnfilled}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});

$('#autoHidePrice').on('change', function(){
    //debugger;
    var autoHidePrice =  ($(this).is(':checked'))? 1 : 0;
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'autoHidePrice', val:autoHidePrice}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});

$('#enableReminders').on('change', function(){
    //debugger;
    var enableReminders =  ($(this).is(':checked'))? 1 : 0;
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'enableReminders', val:enableReminders}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});

$('#siteCancelCond input, #siteCancelCond select').on('change', function(){
    debugger;
    let data = {sid:<?=$sid?>, act:'CancelCondSpa'};
	$('#siteCancelCond input, #siteCancelCond select').each(function(){
		data[$(this).attr("name")] = $(this).attr('type')=='checkbox'? ($(this).is(':checked')? 1 : 0) : $(this).val();
	});	
		
	
    $.post('ajax_settings.php', data).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                //window.location.reload();
            });
    });
});


$('#sendReminderHour').on('change', function(){
    //debugger;
    var enableReminders =  $(this).val();
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'sendReminderHour', val:enableReminders}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});



$('#sourceRequired').on('change', function(){
    //debugger;
    var sourceRequired =  ($(this).is(':checked'))? 1 : 0;
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'sourceRequired', val:sourceRequired}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});


$('#addressRequired2').on('change', function(){
    //debugger;
    var addressRequired =  ($(this).is(':checked'))? 2 : 1;
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'addressRequired', val:addressRequired}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});

$('#addressRequired').on('change', function(){
    //debugger;
    var addressRequired =  ($(this).is(':checked'))? 1 : 0;
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'addressRequired', val:addressRequired}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
		$('#addressRequired2').prop( "checked", false );

		
    });
});

$('#roomRequired').add('#exactIncome').on('change', function(){
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:this.id, val:this.checked ? 1 : 0}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});

$('#bookBefore').on('change', function(){
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:this.id, val:this.value}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});

//
/*
    $('#sameDayOrderHoursBefore').on('change', function(){
        $.post('ajax_global.php', {sid:<?=$sid?>, act:this.id, val:this.value , sameDayOrderHoursBefore: this.value }).then(function(res){
            if (!res || res.status === undefined || parseInt(res.status))
                return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                    window.location.reload();
                });
        });
    });
*/

$('#blockAutoSend').on('change', function(){
    //debugger;
    var blockAutoSend =  ($(this).is(':checked'))? 1 : 0;
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'blockAutoSend', val:blockAutoSend}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});

$('#blockDelete').on('change', function(){
    //debugger;
    var blockDelete =  ($(this).is(':checked'))? 1 : 0;
    $.post('ajax_settings.php', {sid:<?=$sid?>, act:'blockDelete', val:blockDelete}).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                window.location.reload();
            });
    });
});

    $('input[name="per"]').on('click', function(){
        $.post('ajax_settings.php', {sid:<?=$sid?>, act:'baseSalaryTypeNew', val:this.value}).then(function(res){
            if (!res || res.status === undefined || parseInt(res.status))
                return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'Cannot save salary'}).then(function(){
                    window.location.reload();
                });
        });
    });

    $('.per .btn').on('click', function(){
        var self = $(this);

        if (self.hasClass('edit') || self.hasClass('changed')) {
            self.removeClass('changed edit').addClass('save').parent().addClass('edit');

            self.on('click.save', function(){
                var prm = {sid:<?=$sid?>, act:'baseSalaryNew'}, papa = self.parent();

                papa.find('input').each(function(){
                    prm[this.name] = this.value;
                });

                $.post('ajax_settings.php', prm).then(function(res){
                    if (!res || res.status === undefined || parseInt(res.status))
                        return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'Cannot save salary'}).then(function(){
                            window.location.reload();
                        });

                    papa.removeClass('edit').find('.amount > span').html(Math.round(res.amount * 10) / 10);
                    self.toggleClass('save ' + res.class).off('click.save').find('.changed-label').html(res.btn);
                });
            });
        }
    });

<?php
    if($protel['protelID']) {
?>

    $('#checkinNotification').on('change', function(){
        $.post('ajax_protel.php', {sid:<?=$sid?>, act:'welcome2', val:this.checked ? 1 : 0}).then(function(res){
            if (!res || res.status === undefined || parseInt(res.status))
                return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                    window.location.reload();
                });
        });
    });


    $('#numdaysbefore_check').on('change', function(){
        var numdaysbefore = $('select[name="numdaysbefore"]').val();

        $.post('ajax_protel.php', {sid:<?=$sid?>, act:'welcome1', val:this.checked ? 1 : 0, before:numdaysbefore}).then(function(res){
            if (!res || res.status === undefined || parseInt(res.status))
                return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                    window.location.reload();
                });
        });
    });


    $('select[name="numdaysbefore"]').on('change', function(){
        var numdaysbefore =  parseInt(this.value);

        $('#numdaysbefore_check').prop('checked', true);

        $.post('ajax_protel.php', {sid:<?=$sid?>, act:'welcome1', val:1, before:numdaysbefore}).then(function(res){
            if (!res || res.status === undefined || parseInt(res.status))
                return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'שגיאה בשמירה'}).then(function(){
                    window.location.reload();
                });
        });
    });
<?php
    }
?>
});
</script>
