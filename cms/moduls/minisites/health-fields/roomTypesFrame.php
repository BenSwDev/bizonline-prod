<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	        $data = typemap($_POST, [
            'roomType'   => ['int' => 'string'],
            'roomTypeMany'    => ['int' => 'string'],
            'maleOrFemale'    => ['int' => 'int'],
            'purpose'    => 'int',

        ]);
		if (!$data['roomType'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');


        // main site data
        $siteData = [
            'roomType' => $data['roomType'][BASE_LANG_ID],
            'roomTypeMany'     => $data['roomTypeMany'][BASE_LANG_ID],
			'purpose'    => $data['purpose']

        ];

		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData['picture'] = $photo[0]['file']; 
		}
        if (!$pageID){      // opening new site

           	$pageID = udb::insert('roomTypes', $siteData);
			foreach(LangList::get() as $lid => $lang){
				$names[$lid] = $data['roomTypeMany'][$lid];
			}

			$newSerach =  new SearchFiller;
			$newSerach->newAppartmentType($pageID, $names);
        } else {
            udb::update('roomTypes', $siteData, '`id` = ' . $pageID);
        }

		foreach(LangList::get() as $lid => $lang){
			udb::insert('roomTypesLangs', [
				'id'    => $pageID,
				'langID'    => $lid,
				'roomType'  => $data['roomType'][$lid],
				'roomTypeMany'   => $data['roomTypeMany'][$lid],
				'maleOrFemale'   => $data['maleOrFemale'][$lid]

			], true);
		}

	}


    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

if ($pageID){
    $site    = udb::single_row("SELECT * FROM `roomTypes` WHERE id=".$pageID);
	$page = $site;
    $siteLangs   = udb::key_row("SELECT * FROM `roomTypesLangs` WHERE `id` = " .
	$pageID, ['langID']);
}




?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['roomType']?outDb($site['roomType']):"הוספת חלל חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >

				<?php foreach(LangList::get() as $id => $lang){ ?>
				<div class="language" data-id="<?=$id?>">
					<div class="inputLblWrap">
						<div class="labelTo">שם סוג:</div>
						<input type="text" placeholder="שם החלל" name="roomType" value="<?=js_safe($siteLangs[$id]['roomType'])?>" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">שם הסוג ברבים:</div>
						<input type="text" placeholder="שם החלל ברבים" name="roomTypeMany" value="<?=js_safe($siteLangs[$id]['roomTypeMany'])?>" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">זכר/נקבה</div>
						<select name="maleOrFemale">
							<option value="0" <?=($siteLangs[$id]['maleOrFemale']==0?"selected":"")?>>-</option>
							<option value="1" <?=($siteLangs[$id]['maleOrFemale']==1?"selected":"")?>>זכר</option>
							<option value="2" <?=($siteLangs[$id]['maleOrFemale']==2?"selected":"")?>>נקבה</option>
						</select>
					</div>
					
				</div>
				<?php } ?>


			<?php if(1) { ?>
			<div class="inputLblWrap">
				<div class="labelTo">מטרת המתחם</div>
				<select name="purpose">
					<option value="1" <?=($page['purpose']==1?"selected":"")?>>אירוח</option>
					<option value="2" <?=($page['purpose']==2?"selected":"")?>>אירועים</option>
				</select>
			</div>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$page['picture']?>">
					</div>
				</div>
				<?php if($page['picture']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="/gallery/<?=$page['picture']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			<?php } ?>
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