<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	        $data = typemap($_POST, [
            'notHolidayName'   => ['int' => 'string'],
            'dateStart'   => 'string',
            'dateEnd'   => 'string',
			'!active'    => 'int',
			'!annual'    => 'int'

        ]);
		if (!$data['notHolidayName'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');

        // main not holiday data
        $hData = [
            'notHolidayName' => $data['notHolidayName'][BASE_LANG_ID],
            'dateStart' => DateTime::createFromFormat("d/m/Y", $data['dateStart'])->format("Y-m-d"),
            'dateEnd' => DateTime::createFromFormat("d/m/Y", $data['dateEnd'])->format("Y-m-d"),
            'active' => $data['active'],
            'annual' => $data['annual']
          
        ];

        if (!$pageID){      // opening new
            $pageID = udb::insert('not_holidays', $hData);
        } else {
            $prev = udb::single_row("SELECT * FROM `not_holidays` WHERE `notHolidayID` = " . $pageID);
            
            udb::update('not_holidays', $hData, '`notHolidayID` = ' . $pageID);

//            // if changed dates - try to change everywhere
//            if (strcmp($prev['dateStart'], $hData['dateStart']) || strcmp($prev['dateEnd'], $hData['dateEnd'])){
//                $que = "SELECT DISTINCT p.siteID FROM `sites_periods` WHERE `periodType` = 0 AND `notHolidayID` <> " . $pageID . " AND `dateFrom` <= '" . $hData['dateEnd'] . "' AND `dateTo` >= '" . $hData['dateStart'] . "'";
//                $problems = udb::single_column($que);
//
//                udb::update('sites_periods', [
//                    'dateFrom' => $hData['dateStart'],
//                    'dateTo' => $hData['dateEnd'],
//                    'periodName' => $hData['notHolidayName']
//                ], "`notHolidayID` = " . $pageID . (count($problems) ? ' AND `siteID` NOT IN (' . implode(',', $problems) . ')' : ''));
//            }
        }

		foreach(LangList::get() as $lid => $lang){
			udb::insert('not_holidays_text', [
				'notHolidayID'    => $pageID,
				'langID'    => $lid,
				'notHolidayName'  => $data['notHolidayName'][$lid]
			], true);
		}

		SearchCache::update_dates($hData['dateStart'], $hData['dateEnd']);
	}


    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

if ($pageID){
    $site    = udb::single_row("SELECT * FROM `not_holidays` WHERE `notHolidayID`=".$pageID);
    $siteLangs   = udb::key_row("SELECT * FROM `not_holidays_text` WHERE `notHolidayID` = " . $pageID, ['LangID']);

}


?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['notHolidayName']?outDb($site['notHolidayName']):"הוספת לא חג חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
				  <input type="checkbox" name="active" value="1" <?=($site['active']==1 || !$pageID)?"checked":""?> />
				  <span class="slider round"></span>
				</label>
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">שנתי</div>
				<label class="switch">
				  <input type="checkbox" name="annual" value="1" <?=($site['annual']==1)?"checked":""?> />
				  <span class="slider round"></span>
				</label>
			</div>
			<?php foreach(LangList::get() as $id => $lang){ ?>
			<div class="language" data-id="<?=$id?>">
				<div class="inputLblWrap">
					<div class="labelTo">שם התקופה</div>
					<input type="text" placeholder="שם התקופה" name="notHolidayName" value="<?=js_safe($siteLangs[$id]['notHolidayName'])?>" />
				</div>
			</div>
			<?php } ?>
			<div class="inputLblWrap">
				<div class="labelTo">תאריך התחלה</div>
				<input type="text" value="<?=($site['dateStart']?date("d/m/Y", strtotime($site['dateStart'])):date("d/m/Y"))?>" name="dateStart" class="datePick" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">תאריך סיום</div>
				<input type="text" value="<?=($site['dateEnd']?date("d/m/Y", strtotime($site['dateEnd'])):date("d/m/Y"))?>" name="dateEnd" class="datePick" />
			</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$site['id']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<script type="text/javascript">

$(function(){
    $.each({language: <?=$langID?>}, function(cl, v){
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


});


$(".datePick").datepicker({
	format:"dd/mm/yyyy",
	changeMonth:true
});
</script>
