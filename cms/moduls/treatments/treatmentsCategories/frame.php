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
            'categoryName'   => ['int' => 'string']

        ]);
		if (!$data['categoryName'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');


        // main site data
        $siteData = [
            'categoryName' => $data['categoryName'][BASE_LANG_ID]

        ];


        if (!$pageID){      // opening new site
            $pageID = udb::insert('treatmentsCat', $siteData);
        } else {
            udb::update('treatmentsCat', $siteData, '`id` = ' . $pageID);
        }

		foreach(LangList::get() as $lid => $lang){
			udb::insert('treatmentsCatLang', [
				'id'    => $pageID,
				'langID'    => $lid,
				'categoryName'  => $data['categoryName'][$lid]
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
    $site    = udb::single_row("SELECT * FROM `treatmentsCat` WHERE id=".$pageID);
    $siteLangs   = udb::key_row("SELECT * FROM `treatmentsCatLang` WHERE `id` = " . $pageID, ['langID']);
}




?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['categoryName']?outDb($site['categoryName']):"הוספת קטגוריה חדשה"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<?php foreach(LangList::get() as $id => $lang){ ?>
			<div class="language" data-id="<?=$id?>">
				<div class="inputLblWrap">
					<div class="labelTo">קטגוריה</div>
					<input type="text" placeholder="קטגוריה" name="categoryName" value="<?=js_safe($siteLangs[$id]['categoryName'])?>" />
				</div>
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