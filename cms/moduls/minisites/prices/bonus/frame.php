<?php
include_once "../../../../bin/system.php";
include_once "../../../../bin/top_frame.php";
include_once "../../../../_globalFunction.php";




const BASE_LANG_ID = 1;

$benefitsID = intval($_GET['id']);
$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
$siteName = $_GET['siteName'];

$que = "SELECT `roomID`, `roomName` FROM `rooms` WHERE siteID=".$siteID." AND roomOrPage=1";
$rooms = udb::full_list($que);

/*

if ('POST' == $_SERVER['REQUEST_METHOD']){

    try {

        $data = typemap($_POST, [
            'bonusName'   => ['int' => 'string'],
            '!active'    => 'int',
            'bonusPrice'   => 'int',
            'bonusLimit'   => 'int'
        ]);

        // main benefits data
        $siteData = [
            'active'    => $data['active'],
            'siteID'    => $siteID,
            'benefitType'  => $data['bonusName'][BASE_LANG_ID],
			'benefitPrice'  => $data['bonusPrice'],
			'benefitTo' => $data['bonusLimit'],
			'benefitTiming' => $data['bonusLimit'],
			'benefitTimingBefore' => $data['bonusLimit'],
			'benefitDays' => $data['bonusLimit'],
			'benefitMinDates' => $data['bonusLimit'],
			'benefitDates' => $data['bonusLimit'],
			'benefitDateStart' => $data['bonusLimit'],
			'benefitDateEnd' => $data['bonusLimit'],
			'benefitUnits' => $data['bonusLimit']

        ];

        if (!$benefitsID)
		{      // opening new room
            $benefitsID = udb::insert('benefits', $siteData);
        } else 
		{
            udb::update('benefits', $siteData, '`Id` = ' . $benefitsID);
        }
		
		if($siteData['benefitType']==3){/*only for text*/
			// saving data per domain / language
			foreach(LangList::get() as $lid => $lang){
				// inserting/updating data in domains table
				udb::insert('benefits_langs', [
					'benefitID'    => $benefitsID,
					'langID'    => $lid,
					'benefitText' => $data['bonusName'][$lid]
				], true);
			}
		}
		if($siteData['benefitUnits']!=0){
			foreach($rooms as $room){
				udb::insert('benefits_units', [
					'benefitID'    => $benefitsID,
					'roomID'    => $room
				], true);

			
				
		}
  

    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}
*/
$bonusData = $bonusLangs = [];

$domainID = DomainList::active();
$langID   = LangList::active();

if ($bonusID){
   // $bonusData    = udb::single_row("SELECT * FROM `bonus` WHERE `id` = " . $bonusID);
  //  $bonusLangs   = udb::key_row("SELECT * FROM `bonus_langs` WHERE `id` = " . $bonusID, ['langID']);
}

?>



<div class="editItems">
	<div class="frameContent">
		<div class="siteMainTitle"><?=$siteName?></div>
		<div class="inputLblWrap langsdom">
			<div class="labelTo">שפה</div>
			<?=LangList::html_select()?>
		</div>
		
		<form action="" method="post">
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
				  <input type="checkbox" name="active" value="1" <?=($bonusData['active']==1)?"checked":""?> <?=($bonusID==0)?"checked":""?>/>
				  <span class="slider round"></span>
				</label>
			</div>
			<?php 
			foreach(LangList::get() as $lid => $lang){ ?>
				<div class="language" data-id="<?=$lid?>">
					<div class="inputLblWrap">
						<div class="labelTo">כותרת ההטבה*</div>
						<input type="text" placeholder="כותרת ההטבה" value="<?=$bonusLangs[$lid]['bonusName']?>" name="bonusName" />
					</div>
				</div>
			<?php } ?>
				
			<div class="titleSec">1. מה אני מציע?</div>
			<div class="sectionWrap">
				<div class="selectWrap">
					<div class="selectLbl">סוג ההטבה*</div>
					<select name="discount_type">
						<option value="0">הנחה</option>
						<option value="1">מתנה</option>
					</select>
				</div>
				<div class="selectWrap" id="action">
					<div class="selectLbl">פעולה</div>
					<select name="operation">
						<option value="1">החסר מהמחיר</option>
						<option value="2">הקטן מחיר ב %</option>
					</select>
				</div>

				<div class="selectWrap" id="giftDesc">
					<div class="labelTo">תאור המתנה</div>
					<input type="text" placeholder="תאור" value="" name="gift_description" />
				</div>
				<div class="selectWrap" id="giftPrice">
					<div class="labelTo">שווי כספי</div>
					<input type="text" placeholder="שווי כספי" value="" name="gift_value" />
				</div>
				<div class="selectWrap" id="disNightNis">
					<div class="selectLbl">גובה הנחה ללילה</div>
					<select name="discount-select-velues">
						<?php for($i=50;$i<=350;$i+=50) { ?>
						<option value="<?=$i?>"><?=$i?>₪</option>
						<?php } ?>
						<option value="-8">בהתאמה אישית</option>
					</select>
				</div>
				<div class="selectWrap" id="disNightPre">
					<div class="selectLbl">גובה הנחה ללילה</div>
					<select name="discount-select-velues">
						<?php for($i=5;$i<=30;$i+=5) { ?>
						<option value="<?=$i?>"><?=$i?>%</option>
						<?php } ?>
						<option value="-8">בהתאמה אישית</option>
					</select>
				</div>
				<div class="selectWrap" id="disNightCust">
					<div class="labelTo">הזן ערך</div>
					<input type="text" placeholder="הזן ערך" value="" name="gift_value_custom" />
				</div>
				<div class="selectWrap">
					<div class="selectLbl">תנאים לקבלת הטבה*</div>
					<select name="benefit_type">
						<option value="3">בהזמנה ברגע האחרון</option>
						<option value="4">למזמינים היום</option>
						<option value="2">למזמינים מראש</option>
						<option value="1">בהתאמה אישית</option>
					</select>
				</div>
				<div class="selectWrap" id="preOrder">
					<div class="selectLbl">הגדר הזמנה מראש</div>
					<select name="preOrder">
						<option value="1">שבוע (7 ימים)</option>
						<option value="2">שבועיים (14 ימים)</option>
						<option value="3">חודש (30 ימים)</option>
						<option value="8">בהתאמה אישית</option>
					</select>
				</div>
				<div class="selectWrap" id="lastMom">
					<div class="selectLbl">הגדר רגע אחרון</div>
					<select name="lastMom">
						<option value="1">יום אחד</option>
						<option value="2">יומיים</option>
						<option value="3">שלושה ימים</option>
						<option value="8">בהתאמה אישית</option>
					</select>
				</div>
				<div class="selectWrap" id="lastMomCus">
					<div class="selectLbl">הזן ערך</div>
					<input type="text" value="" name="lastMomCus" />
				</div>
				<div class="selectWrap" id="benefitFreeText">
					<div class="selectLbl">כתוב תנאי לקבלת ההטבה</div>
					<input type="text" value="" name="benefit_free_text" />
				</div>
				<div class="floatsWrap" id="orderToday">
					<div class="miniTitle">תאריכי הצגה</div>
					<div class="inputLblWrap">
						<div class="labelTo">תאריך התחלה</div>
						<input type="text" value="" name="benfitTypeFrom" class="datePick" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">תאריך סיום</div>
						<input type="text" value="" name="benfitTypeUntil" class="datePick" />
					</div>
				</div>
				<div class="inputLblWrap full">
					<div class="labelTo">פירוט ההטבה</div>
					<input type="text" placeholder="פירוט ההטבה" value="" name="benefit_desc" />
				</div>
			</div>

			<div class="titleSec">2. טקסט ההטבה כפי שיוצג באתר</div>
			<div class="siteText">50 ש"ח הנחה</div>
			<?php 
			foreach(LangList::get() as $lid => $lang){ ?>
				<div class="language" data-id="<?=$lid?>">
					<div class="inputLblWrap full">
						<div class="labelTo">קבע טקסט מותאם אישית</div>
						<input type="text" placeholder="" value="" name="" />
					</div>
				</div>
			<?php } ?>
			<div class="titleSec">3. באילו יחידות ההטבה תקפה?</div>
			<div class="sectionWrap">
			<?php foreach($rooms as $room) { ?>	
				<div class="checkBoxWrap">
					<input type="checkbox" name="room[]" id="da<?=$room['roomID']?>">
					<label for="da<?=$room['roomID']?>" class="checkName"><?=$room['roomName']?></label>
				</div>
			<?php } ?>

			</div>

			

			<div class="titleSec">4. מתי ההטבה תקפה?</div>
			<div class="floatsWrap">
				<div class="miniTitle">ההטבה תקפה בימים</div>
				<div class="sectionWrap">
					<div class="checkBoxWrap">
						<input type="checkbox" name="" id="day1">
						<label for="da1" class="checkName">א'</label>
					</div>
					<div class="checkBoxWrap">
						<input type="checkbox" name="" id="day2">
						<label for="da2" class="checkName">ב'</label>
					</div>
					<div class="checkBoxWrap">
						<input type="checkbox" name="" id="day3">
						<label for="da3" class="checkName">ג'</label>
					</div>
					<div class="checkBoxWrap">
						<input type="checkbox" name="" id="day4">
						<label for="da4" class="checkName">ד'</label>
					</div>
					<div class="checkBoxWrap">
						<input type="checkbox" name="" id="day5">
						<label for="da5" class="checkName">ה'</label>
					</div>
					<div class="checkBoxWrap">
						<input type="checkbox" name="" id="day">
						<label for="da6" class="checkName">ו'</label>
					</div>
					<div class="checkBoxWrap">
						<input type="checkbox" name="" id="day7">
						<label for="da7" class="checkName">ש'</label>
					</div>
				</div>
			</div>
			<div class="floatsWrap">
				<div class="miniTitle">ההטבה תקפה בתאריכים</div>
				<div class="inputLblWrap">
					<div class="labelTo">תאריך התחלה</div>
					<input type="text" value="<?=$bonusData['startDate']?>" name="startDate" class="datePick" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">תאריך סיום</div>
					<input type="text" value="<?=$bonusData['endDate']?>" name="endDate" class="datePick" />
				</div>
			</div>

			<div class="clear"></div>
			<input type="submit" value="שמור" class="submit">
		</form>	
	</div>
</div>



<script>

	$(function(){
    $.each({domain: <?=$domainID?>, language: <?=$langID?>}, function(cl, v){
        $('.' + cl).hide().each(function(){
            var id = $(this).data('id');
            $(this).find('input, select, textarea').each(function(){
                this.name = this.name + '[' + id + ']';
            });
        }).filter('[data-id="' + v + '"]').show();

        $('.' + cl + 'Selector').on('change', function(){
            $('.' + cl, $(this).data('cont')).hide().filter('[data-id="' + this.value + '"]').show();
        });
    });

	$(".datePick").datepicker({
		"minDate":0
	});
});


$('.sectionWrap').find('select').change(getText);
$('.sectionWrap').find('input').on('keypress',getText);

var price,type,condition;

function getText (){
	console.log($('.sectionWrap').find('select:enabled','input:enabled').map(function(){

		if(this.name == "discount-select-velues" && this.value !=-8){
			price = this.options[this.selectedIndex].text;
		}
		if(this.name == "gift_value_custom"){
			price = this.value;
		}
		if(this.name == "discount_type"){
			type = this.options[this.selectedIndex].text;
		}
		if(this.name == "benefit_type"){
			condition = this.options[this.selectedIndex].text;
		}

		return {name: this.name, text: this.options[this.selectedIndex].text};
	}).get());

	//console.log(price+" "+type+" "+condition);
}


$(function(){
	$("select[name='discount_type']").change(function(){
	
		if($(this).val()==0){

			$('#action').show().find('select').removeAttr('disabled');
			$('#disNight').show().find('select').removeAttr('disabled');
			$('#disNightNis').show().find('select').change().removeAttr('disabled');
			$('#giftDesc').hide().find('select').attr('disabled', 'disabled');
			$('#giftPrice').hide().find('select').attr('disabled', 'disabled');
		
		}
		if($(this).val()==1){
			$('#action').hide().find('select').attr('disabled', 'disabled');
			$('#disNight').hide().find('select').attr('disabled', 'disabled');
			$('#disNightNis').hide().find('select').attr('disabled', 'disabled');
			$('#disNightPre').hide().find('select').attr('disabled', 'disabled');
			$('#disNightCust').hide().find('select').attr('disabled', 'disabled');
			$('#giftDesc').show().find('select').removeAttr('disabled');
			$('#giftPrice').show().find('select').removeAttr('disabled');
		}
	}).change();

	$("select[name='operation']").change(function(){

		if($(this).val()==1){

			$('#disNightNis').show().find('select').removeAttr('disabled');
			$('#disNightPre').hide().find('select').attr('disabled', 'disabled');
		}
		else{
			$('#disNightNis').hide().find('select').attr('disabled', 'disabled');
			$('#disNightPre').show().find('select').removeAttr('disabled');
		}
	}).change();
	$("select[name='discount-select-velues']").change(function(){

		if($(this).val()==-8){

			$('#disNightCust').show().find('input').removeAttr('disabled');
		}
		else{
			$('#disNightCust').hide().find('input').attr('disabled', 'disabled');
		}
	}).change();
	$("select[name='benefit_type']").change(function(){

		if($(this).val()==1){/*התאמה אישית*/
			$('#benefitFreeText').show().find('select').removeAttr('disabled');
			$('#lastMom').hide().find('select').attr('disabled', 'disabled');
			$('#preOrder').hide().find('select').attr('disabled', 'disabled');
			$('#orderToday').hide().find('input').attr('disabled', 'disabled');
			$('#lastMomCus').hide().find('select').attr('disabled', 'disabled');
		}
		else if($(this).val()==2){/*מראש*/
			$('#benefitFreeText').hide().find('select').attr('disabled', 'disabled');
			$('#lastMom').hide().find('select').attr('disabled', 'disabled');
			$('#preOrder').show().find('select').removeAttr('disabled');
			$('#orderToday').hide().find('input').attr('disabled', 'disabled');
		}
		else if($(this).val()==3){/*אחרון*/
			$('#lastMom').show().find('select').change().removeAttr('disabled');
			$('#benefitFreeText').hide().find('select').attr('disabled', 'disabled');
			$('#preOrder').hide().find('select').attr('disabled', 'disabled');
			$('#orderToday').hide().find('input').attr('disabled', 'disabled');
		}
		else if($(this).val()==4){/*היום*/
			$('#lastMom').hide().find('select').attr('disabled', 'disabled');
			$('#benefitFreeText').hide().find('select').attr('disabled', 'disabled');
			$('#preOrder').hide().find('select').attr('disabled', 'disabled');
			$('#lastMomCus').hide().find('select').attr('disabled', 'disabled');
			$('#orderToday').show().find('input').removeAttr('disabled');
		}
	}).change();

	$("select[name='lastMom']").change(function(){

		if($(this).val()==8){
			$('#lastMomCus').show().find('input').removeAttr('disabled');
			
		}
		else{
			$('#lastMomCus').hide().find('input').attr('disabled', 'disabled');
		}
	}).change();
	$("select[name='preOrder']").change(function(){
		if($(this).val()==8){
			$('#lastMomCus').show().find('input').removeAttr('disabled');
		}
		else{
			$('#lastMomCus').hide().find('input').attr('disabled', 'disabled');
		}
	}).change();



});
</script>