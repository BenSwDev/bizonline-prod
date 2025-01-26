<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();

$pageID=intval($_GET['pageID']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	
	    $data = typemap($_POST, [
            'categoryName'   => 'string',
            'categoryNameShow'   => 'string',
			 '!optional'		 => 'int',
        ]);

        // main site data
        $siteData = [
            'categoryName' => $data['categoryName'],
            'categoryNameShow' => $data['categoryNameShow'],
			'optional'       => $data['optional']
        ];
	
        if (!$pageID){      // opening new
            $pageID = udb::insert('reviewCategories', $siteData);
        } else {
            udb::update('reviewCategories', $siteData, '`id` = ' . $pageID);
        }
	}

    catch (LocalException $e){
        // show error
    } ?>

	<script></script>
<?php

}

if ($pageID){
    $site = udb::single_row("SELECT * FROM `reviewCategories` WHERE `id`=".$pageID);

}

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">

	<div class="miniTabs">
		<div class="tab active" style="margin-right: 30px;"><p>קטגוריות</p></div>
		<?php if($pageID) { ?>
		<div class="tab" onclick="window.location.href='options.php?pageID=<?=$pageID?>'"><p>אופציות</p></div>
		<?php } ?>
	</div>

    <h1><?=$site['categoryName']?outDb($site['categoryName']):"הוספת קטגוריה חדשה"?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<div class="inputLblWrap">
				<div class="labelTo">שם קטגוריה בטופס שליחה</div>
				<input type="text" placeholder="שם קטגוריה" name="categoryName" value="<?=js_safe($site['categoryName'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">שם קטגוריה בתצוגה</div>
				<input type="text" placeholder="שם קטגוריה בתצוגה" name="categoryNameShow" value="<?=js_safe($site['categoryNameShow'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">אופציונלי</div>
				<label class="switch">
				  <input type="checkbox" name="optional" value="1" <?=($site['optional']==1)?"checked":""?> />
				  <span class="slider round"></span>
				</label>
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
