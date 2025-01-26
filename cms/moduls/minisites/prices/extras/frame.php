<?php
include_once "../../../../bin/system.php";
include_once "../../../../bin/top_frame.php";
include_once "../../../../_globalFunction.php";




const BASE_LANG_ID = 1;

$extraID = intval($_GET['id']);
$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
$siteName = $_GET['siteName'];


if ('POST' == $_SERVER['REQUEST_METHOD']){

    try {

        $data = typemap($_POST, [
            'extraName'   => ['int' => 'string'],
            'extraDesc'   => ['int' => 'string'],
            '!active'    => 'int',
            'extraPrice'   => 'int',
            'extraLimit'   => 'int'
        ]);

        // main extra data
        $siteData = [
            'active'    => $data['active'],
            'siteID'    => $siteID,
            'extraName'  => $data['extraName'][BASE_LANG_ID],
			'extraPrice'  => $data['extraPrice'],
			'extraLimit' => $data['extraLimit']

        ];
        if (!$extraID){      // opening new room
            $extraID = udb::insert('extra', $siteData);
        } else {
            udb::update('extra', $siteData, '`Id` = ' . $extraID);
        }

            // saving data per domain / language
            foreach(LangList::get() as $lid => $lang){
                // inserting/updating data in domains table
                udb::insert('extra_langs', [
                    'id'    => $extraID,
                    'langID'    => $lid,
                    'extraName' => $data['extraName'][$lid],
                    'extraDesc' => $data['extraDesc'][$lid]
                ], true);
            }
  

    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

$extraData = $extraLangs = [];

$domainID = DomainList::active();
$langID   = LangList::active();

if ($extraID){
    $extraData    = udb::single_row("SELECT * FROM `extra` WHERE `id` = " . $extraID);
    $extraLangs   = udb::key_row("SELECT * FROM `extra_langs` WHERE `id` = " . $extraID, ['langID']);
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
					  <input type="checkbox" name="active" value="1" <?=($extraData['active']==1)?"checked":""?> <?=($extraID==0)?"checked":""?>/>
					  <span class="slider round"></span>
					</label>
				</div>
			<?php 
			foreach(LangList::get() as $lid => $lang){ ?>
				<div class="language" data-id="<?=$lid?>">
					<div class="inputLblWrap">
						<div class="labelTo">שם התוספת</div>
						<input type="text" placeholder="שם התוספת" value="<?=$extraLangs[$lid]['extraName']?>" name="extraName" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">תיאור</div>
						<input type="text" placeholder="תיאור" value="<?=$extraLangs[$lid]['extraDesc']?>" name="extraDesc" />
					</div>
				</div>
			<?php  } ?>
			<div class="inputLblWrap">
				<div class="labelTo">מחיר</div>
				<input type="text" placeholder="מחיר" value="<?=$extraData['extraPrice']?>" name="extraPrice" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">הגבלת כמות</div>
				<input type="text" placeholder="הגבלת כמות" value="<?=$extraData['extraLimit']?>" name="extraLimit" />
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


});
</script>