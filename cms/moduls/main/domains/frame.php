<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";



$pageID=intval($_GET['pageID']);
$langID = 1;

function articlesFolderHandler($did,$folderName = "art",$new = false){
    if($new == false) {
        $prevFolderName = udb::single_value("select articlesFolder from domains where domainID=".$did);
        if($prevFolderName == $folderName) return;
        $que = [];
        $que['LEVEL2'] = $folderName;
        udb::update("alias_text",$que," domainID=".$did." and LEVEL2='".$prevFolderName."'"); //update all urls + problem no redirects
    }
    else {
        //create article page on MaiinPages
        $que = [];
        $que['MainPageTitle'] = 'כתבות';
        $que['langID'] = 1;
        $que['mainPageType'] = 1;
        $que['ifShow'] = 1;
        $newID = udb::insert("MainPages",$que);
        //create alias heb and en for MainPages of article page
        $que = [];
        $que['LEVEL1'] = 'he';
        $que['LEVEL2'] = $folderName;
        $que['domainID'] = $did;
        $que['ref'] = $newID;
        $que['table'] = 'MainPages';
        udb::insert("alias_text",$que);
        $que['LEVEL1'] = 'en';
        udb::insert("alias_text",$que);

    }

}

$domainSettingsFields = [];
$domainSettingsFields["generalMetaTitle"] = array("title"=>"כותרת מטה לחיפוש","value"=>"");
$domainSettingsFields["generalDesc"] = array("title"=>"תאור לתוצאות חיפוש","value"=>"");
$domainSettingsFields["generalKeys"] = array("title"=>"מילות חיפוש כללי","value"=>"");
$domainSettingsFields["areaDesc"] = array("title"=>"תאור אזור/יישוב","value"=>"");
$domainSettingsFields["areasKeys"] = array("title"=>"מילות מפתח אזור/יישוב","value"=>"");
$domainSettingsFields["areaAttrDesc"] = array("title"=>"תאור אזור/יישוב ומאפיין","value"=>"");
$domainSettingsFields["cityAttrDesc"] = array("title"=>"תאור יישוב ומאפיין","value"=>"");
$domainSettingsFields["attrDesc"] = array("title"=>"תאור מאפיין","value"=>"");
$domainSettingsFields["attrKeys"] = array("title"=>"מילות מפתח מאפיין","value"=>"");
$domainSettingsFields["miniSiteDescription"] = array("title"=>"תאור מיניסייט","value"=>"");
$domainSettingsFields["miniSiteKeys"] = array("title"=>"מילות מפתח מיניסייט","value"=>"");
$domainSettingsFields["miniSiteTitle"] = array("title"=>"כותרת מטה מיניסייט","value"=>"");
$domainSettingsFields["roomTypesKeys"] = array("title"=>"מילות מפתח סוגי יחידות","value"=>"");
$domainSettingsFields["roomTypesDesc"] = array("title"=>"תאור סוגי יחידות","value"=>"");

if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	    $data = typemap($_POST, [
            'active'   => 'int',
            'domainMenu'   => 'int',
            'domainName'   => 'string',
            'domainURL'   =>  'string',
            'metaTitleExt'=>  'string',
            'searchNumberExt'=>  'string',
            'searchLevel2'=>  'string',
            'baseTitlesStart'=>  'string',
            'searchResultsViews'=>  'int',
            'articlesFolder'=> 'string',
            'attrType'    => ['int']
        ]);

		
		
		

		if (!$data['domainName'])
            throw new LocalException('חייב להיות שם בעברית');


        // main site data
        $siteData = [
            'active' => $data['active'] ?? 0,
            'domainMenu' => $data['domainMenu'] ?? 0,
            'domainName' => $data['domainName'],
            'domainURL' => $data['domainURL'],
            'metaTitleExt' => $data['metaTitleExt'],
            'baseTitlesStart' => $data['baseTitlesStart'],
            'searchResultsViews' => $data['searchResultsViews'],
            'searchNumberExt' => $data['searchNumberExt'],
            'searchLevel2' => $data['searchLevel2'],
            'articlesFolder' => $data['articlesFolder'],
            'attrType'  => $data['attrType'] ? array_sum($data['attrType']) : 0
        ];

		if($_POST['social']){
			$socialData = typemap($_POST['social'],['int' => 'string']); 			
			$siteData['social'] = json_encode($socialData);
		}



        if (!$pageID){
            // opening new
            $lastID = udb::single_value("select max(lastID) from domains");
            $lastID++;
            $siteData['domainID'] = $lastID;
            $pageID = udb::insert('domains', $siteData);
            udb::query("update domains set lastID=".$lastID);
            articlesFolderHandler($lastID,$data['articlesFolder'],true);
        } else {
            $lastID =  $pageID;
            articlesFolderHandler($pageID,$data['articlesFolder'],false);
            udb::update('domains', $siteData, '`domainID` = ' . $pageID);
        }
        $que = [];
        $que['domainID'] = $pageID;
        foreach ($domainSettingsFields as $name=>$domainSettingsField) {
            $que['param'] = $name;
            $que["value"] = $_POST[$name];
            udb::insert("domainsSetting",$que,true);
        }


        echo '<script>window.parent.location.reload(); window.parent.closeTab();</script>';
	}
    catch (Exception $e){
        // show error
        echo "<script>alert('".$e->getMessage()."');</script>";
    } ?>


<?php

}


if ($pageID){
    $domain = udb::single_row("SELECT * FROM `domains` WHERE `domainID`=".$pageID);
}

$social[1] = 'Facebook';
$social[2] = 'Twitter x.com';
$social[3] = 'Instagram';
$social[4] = 'Tiktok';
$social[5] = 'YouTube';

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$domain['domainName']?outDb($domain['domainName']):"הוספת דומיין חדש"?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<div class="inputLblWrap">
				<div class="switchTtl">פעיל</div>
				<label class="switch">
					<input type="checkbox" name="active" value="1" <?=($domain['active'] ? 'checked="checked"' : '')?>/>
					<span class="slider round"></span>
				</label>
			</div>
            <div class="inputLblWrap">
                <div class="switchTtl">תפריט דומיינים</div>
                <label class="switch">
                    <input type="checkbox" name="domainMenu" value="1" <?=($domain['domainMenu'] ? 'checked="checked"' : '')?>/>
                    <span class="slider round"></span>
                </label>
            </div>
			<div class="inputLblWrap">
				<div class="labelTo">שם הדומיין</div>
				<input type="text" placeholder="שם הדומיין" name="domainName" value="<?=$domain['domainName']?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">דומיין URL</div>
				<input type="text" placeholder="דומיין URL" name="domainURL" value="<?=$domain['domainURL']?>" onfocus="this.blur()" />
			</div>
            <div class="inputLblWrap">
                <div class="labelTo">סיומת כותרת מטה</div>
                <input type="text" placeholder="סיומת כותרת מטה" name="metaTitleExt" value="<?=$domain['metaTitleExt']?>" onfocus="this.blur()" />
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">תיקיית חיפוש</div>
                <input type="text" placeholder="תיקיית חיפוש" name="searchLevel2" value="<?=$domain['searchLevel2']?>" onfocus="this.blur()" />
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">תקיית מאמרים</div>
                <input type="text" placeholder="תקיית מאמרים" name="articlesFolder" value="<?=$domain['articlesFolder']?>"  />
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">תוספת מספר לחיפוש</div>
                <input type="text" placeholder="תוספת מספר לחיפוש" name="searchNumberExt" value="<?=$domain['searchNumberExt']?>" onfocus="this.blur()" />
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">משפט בסיס אוטומטי</div>
                <input type="text" placeholder="משפט בסיס אוטומטי" name="baseTitlesStart" value="<?=$domain['baseTitlesStart']?>" onfocus="this.blur()" />
            </div>

		</div>
        <div class="frm" >
            <div class="inputLblWrap">
                <div class="switchTtl">אירוח לפי יום</div>
                <label class="switch">
                    <input type="checkbox" name="attrType[]" value="1" <?=($domain['attrType'] & 1 ? 'checked="checked"' : '')?>/>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="inputLblWrap">
                <div class="switchTtl">אירוח לפי שעה</div>
                <label class="switch">
                    <input type="checkbox" name="attrType[]" value="2" <?=($domain['attrType'] & 2 ? 'checked="checked"' : '')?>/>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="inputLblWrap">
                <div class="switchTtl">אירוח לאירועים</div>
                <label class="switch">
                    <input type="checkbox" name="attrType[]" value="4" <?=($domain['attrType'] & 4 ? 'checked="checked"' : '')?>/>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="inputLblWrap">
                <div class="switchTtl">בתי ספא</div>
                <label class="switch">
                    <input type="checkbox" name="attrType[]" value="8" <?=($domain['attrType'] & 8 ? 'checked="checked"' : '')?>/>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="inputLblWrap">
                <div class="labelTo">תצוגת תוצאות חיפוש</div>
                <select name="searchResultsViews">  
                    <option value="1" <?=$domain['searchResultsViews'] == 1?"selected":""?>>הצג מתחמים ויחידות</option>
                    <option value="2" <?=$domain['searchResultsViews'] == 2?"selected":""?>>הצג מתחמים בלבד</option>
                    <option value="3" <?=$domain['searchResultsViews'] == 3?"selected":""?>>הצג יחידות בלבד</option>
                </select>
            </div>
        </div>
		<div class="frm">
			<?
			$socialVal = $domain['social']?  json_decode($domain['social'],true) : array();
			//print_r($socialVal);
			foreach($social as $key => $soc){?>
			<div class="inputLblWrap">
                <div class="labelTo"><?=$soc?></div>
                <input type="text" placeholder="קישור" name="social[<?=$key?>]" value="<?=$socialVal[$key]?>"  />
            </div>
			<?}?>
		</div>
        <div class="frm" >
            <?php

                $domainSettingsValues = udb::key_row("select * from domainsSetting where domainID=".intval($pageID) , "param");
                foreach ($domainSettingsFields as $name=>$domainSettingsField) {
            ?>
            <div class="inputLblWrap">
                <div class="labelTo"><?=$domainSettingsField['title']?></div>
                <textarea placeholder="<?=$domainSettingsField['title']?>" name="<?=$name?>"   ><?=$domainSettingsValues[$name]["value"]?></textarea>
            </div>
            <?php }?>
        </div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$domain['domainID']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<script src="../../../app/tinymce/tinymce.min.js"></script>
<script type="text/javascript">

$(function(){
    $.each({domain: <?=$pageID?>, language: <?=$langID?>}, function(cl, v){
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


	tinymce.init({
	  selector: 'textarea.textEditor' ,
	  height: 500,
	  directionality : "rtl",
	  plugins: [
		"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
		"table contextmenu directionality emoticons textcolor paste  textcolor colorpicker textpattern"
	  ],
	  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
	  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
	  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking pagebreak restoredraft"

	});

</script>