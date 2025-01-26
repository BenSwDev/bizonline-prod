<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


function createPath($path, $suff)
{
    $path = rtrim($path,'/').'/';
    $file = str_replace('.','', microtime(true)).($suff ? '.'.$suff : '');
    while(file_exists($path.$file))
        $file = str_replace('.','', microtime(true)).($suff ? '.'.$suff : '');

    return $path.$file;
}

function moveSingleFile($file, $path, $maxSize = 0)
{
    if ($file['error'] == UPLOAD_ERR_OK && (!$maxSize || $file['size'] <= mySize($maxSize))){
        $tmp     = explode('.', $file['name']);
        $newpath = createPath($path, strtolower(end($tmp)));

        if (move_uploaded_file($file['tmp_name'], $newpath)){
            chmod($newpath, 0777);
            return array('file' => basename($newpath), 'original' => $file['name'], 'size' => $file['size']);
        } else
            return "Can't move file '".$file['tmp_name']."' to '".$newpath."'";
    } else
        return ($file['error'] == UPLOAD_ERR_OK) ? "File '".$file['name']."' is larger than ".$maxSize : "File '".$file['name']."' error code ".$file['error'];
}

function fileUpload($field, $path, $maxSize = 0, &$error = null)
{
    $result = $ierror = array();

    if (isset($_FILES[$field]) && is_array($_FILES[$field])){
        $file = $_FILES[$field];

        if (is_array($file['error'])){
            foreach($file['error'] as $index => $err){
                $tmp = array('name' => $file['name'][$index], 'tmp_name' => $file['tmp_name'][$index], 'size' => $file['size'][$index], 'error' => $err);
                $res = moveSingleFile($tmp, $path, $maxSize);

                is_array($res) ? $result[] = $res : $ierror[] = $res;
            }
        } else {
            $res = moveSingleFile($file, $path, $maxSize);

            is_array($res) ? $result[] = $res : $ierror = $res;
        }
    }
    $error = $ierror;
    return $result;
}

function pictureUpload($field, $path, $maxSize = 0, $width = 0, $height = 0, &$error = null)
{

    if ($res = fileUpload($field, $path, $maxSize, $error)){
        is_numeric(key($res)) || $res = array($res);

        for($i = 0; $i < count($res); $i++){
            $tmp = explode('.', $res[$i]['file']);
            $sub = strtolower(end($tmp));

            if (in_array($sub, array('jpg','gif','png', 'jpeg','webp')) && getimagesize($path.$res[$i]['file'])){
                if ($width > 0 && $height > 0 && ($err = resizePicture($path.$res[$i]['file'], $sub, $width, $height))){
                    $error[] = "-PIC_ERROR-: File '" . $res[$i]['original'] . "' - " . $err;

                    @unlink($path.$res[$i]['file']);
                    unset($res[$i]);
                }
            }
            elseif (strcmp($sub, 'svg')) {
                $error[] = "-PIC_ERROR-: File '" . $res[$i]['original'] . "' - is not a picture";

                @unlink($path.$res[$i]['file']);
                unset($res[$i]);
            }
        }
    }

    return array_values($res);
}


$sid = $_CURRENT_USER->active_site() ?: 0;

$typesNames = ['package' => 'תוספים בחבילה', 'general' => 'תוספים כללי - כמותי', 'rooms' => 'תוספים חדרים', 'company' => 'תוספים מלווים'];

$que = "SELECT * FROM `sites_treatment_extras` AS `se` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE se.siteID = " . $sid . " AND se.active = 1 ORDER BY e.showOrder";
$extras = udb::key_row($que, ['extraType', 'extraID']);

$que = "SELECT DISTINCT t.treatmentID, t.treatmentName FROM `treatmentsPricesSites` AS `p` INNER JOIN `treatments` AS `t` USING(`treatmentID`) WHERE p.siteID = " . $sid;
$treats = udb::key_value($que);

$treatTimes = udb::key_column("SELECT `treatmentID`, `duratuion` FROM `treatmentsPricesSites` WHERE `siteID` = " . $sid);

$siteData = udb::single_row("SELECT sites.*, sites_langs.limitsText, sites_langs.mustKnowText FROM sites
INNER JOIN sites_langs ON(sites_langs.siteID = sites.siteID AND sites_langs.domainID=1 AND sites_langs.langID=1)
WHERE sites.siteID=".$sid);


$allTimes = $treatTimes ? array_unique(array_merge(...$treatTimes)) : [];
sort($allTimes, SORT_NUMERIC);

function checkPermission($user, $permission) {
    if($user & $permission)return true;
    else return false;
}

if ('POST' == $_SERVER['REQUEST_METHOD']){
    $input = typemap($_POST, [
        '!treats' => ['int'],
        '!extras' => ['int'],
        'orderStep' => 'int',
        '!times'  => ['int' => ['int']],
        'bizpopdesc' => 'string',  
        'hideOnBizReviews' => 'int',
        'needRoom' => 'int',
        'useBizOnLinePop' => 'int',
        'popBg' => 'string',
        'logoPicture' => 'string',
        'mainPicture' => 'string',
        'limitsText' => 'text',
        'mustKnowText' => 'text',
        'popBizPayOptions'=>'int',
        'minGroup' => 'int',
        'maxGroup' => 'int',
        'bizPopDefaultTID' => 'int',
        'bizPopDefaultTDur' => 'int',
        'minPatients' => 'int',
        'maxPatients' => 'int',
        'singleOrder' => 'int',
        'coupleOrder' => 'int',
        'groupOrder' => 'int',
        'funDayOrder' => 'int',
        'sleepOrder' => 'int',
        'hsingleOrder' => 'int',
        'hcoupleOrder' => 'int',
        'hgroupOrder' => 'int',
        'hfunDayOrder' => 'int',
        'hsleepOrder' => 'int',
        'bizpophoteldiscount' => 'int',
        'hotelShowAccomo' => 'int',
        'hotelShowGeneralExtras' => 'int',
        'hotelShowAccomp' => 'int',
        'popHotelShowFreeExtras' => 'int',
        'popHotelShowPayedExtras' => 'int',
    ]);

    

    $singleOrder = $input['singleOrder']?$input['singleOrder']:0;
    $coupleOrder = $input['coupleOrder']?$input['coupleOrder']:0;
    $groupOrder = $input['groupOrder']?$input['groupOrder']:0;
    $funDayOrder = $input['funDayOrder']?$input['funDayOrder']:0;
    $sleepOrder = $input['sleepOrder']?$input['sleepOrder']:0;

    $hsingleOrder = $input['hsingleOrder']?$input['hsingleOrder']:0;
    $hcoupleOrder = $input['hcoupleOrder']?$input['hcoupleOrder']:0;
    $hgroupOrder = $input['hgroupOrder']?$input['hgroupOrder']:0;
    $hfunDayOrder = $input['hfunDayOrder']?$input['hfunDayOrder']:0;
    $hsleepOrder = $input['hsleepOrder']?$input['hsleepOrder']:0;

    $bitwise = $singleOrder | $coupleOrder | $groupOrder | $funDayOrder | $sleepOrder;
    $bitwise_h = $hsingleOrder | $hcoupleOrder | $hgroupOrder | $hfunDayOrder | $hsleepOrder;

	$photo2 = $photo3 =  "";

    //if($input['logoPicture'])
		$photo2 = pictureUpload('logoPicture',$_SERVER['DOCUMENT_ROOT']."/gallery/");

    //if($input['mainPicture'])
		$photo3 = pictureUpload('mainPicture',$_SERVER['DOCUMENT_ROOT']."/gallery/");
	
    if($input['minGroup'] || $input['maxGroup'] || $input['popBg'] || $bitwise || $bitWise_h || $photo2) {
        $update = udb::query("UPDATE `sites` SET 

        minGroup=".$input['minGroup'].",
        maxGroup=".$input['maxGroup'].",
        popBg='".$input['popBg']."',
        bitWise=".intval($bitwise).",
        bitWise_h=".intval($bitwise_h).
		($photo2? " ,logoPicture='".$photo2[0]['file']."' " : "").
        ($photo3? " ,mainPicture='".$photo3[0]['file']."' " : "")."
        
        WHERE siteID=".$sid);

    }

    // print_R($input);

    if($input['limitsText'] || $input['mustKnowText']) {
		$updateTexts['limitsText'] =  $input['limitsText'];
		$updateTexts['mustKnowText'] =  $input['mustKnowText'];
		udb::update('sites_langs',$updateTexts, "domainID=1 AND langID=1 AND siteID = " . $sid);
        //$update = udb::query("UPDATE `sites_langs` SET limitsText='".$input['limitsText']."',mustKnowText='".$input['mustKnowText']."' WHERE domainID=1 AND langID=1 AND siteID=".$sid);
    }

    $insert  = [];
    $exclude = array_diff(array_keys($treats) ?: [], $input['treats']);
    if ($exclude)
        foreach($exclude as $id){
            $insert[] = "('treatments', '" . $sid . "', '" . $id . "', 'bpHide', '*')";
            unset($input['times'][$id]);
        }

    foreach($input['treats'] as $id){
        $exclude = $input['times'][$id] ? array_values(array_diff($treatTimes[$id] ?: [], $input['times'][$id])) : [];
        if ($exclude)
            $insert[] = "('treatments', '" . $sid . "', '" . $id . "', 'bpHide', '" . ($exclude ? json_encode($exclude, JSON_NUMERIC_CHECK) : '*') . "')";
    }

    foreach($extras as $extra){
        $exclude = array_diff(array_keys($extra) ?: [], $input['extras']);
        if ($exclude)
            foreach($exclude as $id)
                $insert[] = "('extras', '" . $sid . "', '" . $id . "', 'bpHide', 'true')";
    }

    if($input['bizpopdesc']) {
        $insert[] = "('general','".$sid."','texts','bizpopdesc','".$input['bizpopdesc']."')";
    }


	
	udb::query("DELETE FROM `bizPopSettings` WHERE `module` = 'general' AND  `key1` = " . $sid." AND `key2` = 'setting' AND permName = 'hideOnBizReviews'" );
	if($input['hideOnBizReviews']) {  
        $insert[] = "('general','".$sid."','setting','hideOnBizReviews','".$input['hideOnBizReviews']."')";
    }


    if($input['minPatients'] || $input['maxPatients']) {
        $insert[] = "('bizpop','".$sid."','setting','minPatients','".($input['minPatients']?$input['minPatients']:0)."'),('treatments','".$sid."','patientsLimit','maxPatients','".($input['maxPatients']?$input['maxPatients']:0)."')";
    }
    $insert[] = "('bizpop','".$sid."','setting','orderStep','".$input['orderStep']."')";
    if(isset($input['bizPopDefaultTID'])) {
        $insert[] = "('bizpop','".$sid."','setting','bizPopDefaultTID','".$input['bizPopDefaultTID']."')";
    }
    if(isset($input['popBizPayOptions'])) {
        $insert[] = "('bizpop','".$sid."','setting','popBizPayOptions','".$input['popBizPayOptions']."')";
    }
    if(isset($input['bizPopDefaultTDur'])) {
        $insert[] = "('bizpop','".$sid."','setting','bizPopDefaultTDur','".$input['bizPopDefaultTDur']."')";
    }
    if(isset($input['needRoom'])) {
        $insert[] = "('bizpop','".$sid."','setting','needRoom','".$input['needRoom']."')";
    } 
	
    if(isset($input['useBizOnLinePop'])) {
        $insert[] = "('bizpop','".$sid."','setting','useBizOnLinePop','".$input['useBizOnLinePop']."')";
    }
    if($input['bizpophoteldiscount']) {
        $insert[] = "('bizpop','".$sid."','setting','bizpophoteldiscount','".$input['bizpophoteldiscount']."')";
    }
    $hotelsshow = ['hotelShowAccomo','hotelShowGeneralExtras','hotelShowAccomp','popHotelShowFreeExtras','popHotelShowPayedExtras'];
    foreach ($hotelsshow as $h) {
            $insert[] = "('bizpop','".$sid."','setting','".$h."','".$input[$h]."')";
    }

	//print_r($insert);

    udb::query("DELETE FROM `bizPopSettings` WHERE `module` IN ('treatments', 'extras','bizpop') AND `key1` = " . $sid );
    if ($insert)
        udb::query("INSERT INTO `bizPopSettings`(`module`, `key1`, `key2`, `permName`, `permValue`) VALUES" . implode(',', $insert));

	//print_r($input);
    unset($input, $insert, $exclude);

    echo '<script>window.location.href="?page=bizpop_settings"</script>';
}

$perms = udb::key_value("SELECT `module`, `key2`, `permName`, `permValue` FROM `bizPopSettings` WHERE `module` IN ('treatments', 'extras', 'general','bizpop') AND `key1` = " . $sid, ['module', 'key2', 'permName'], 'permValue');
//print_r($perms);
?>
<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=bizpop_settings&v=<?=rand()?>" rel="stylesheet">
<h1>הגדרות ביזפופ</h1>
<h2 style="text-decoration:underline; margin-top:10px; "> אישור תצוגה בביזפופ </h2>
<p>קישור לאונליין<BR>
<A href="https://bizonline.co.il/bizpop/?siteID=<?=$sid?>" target="_blank">https://bizonline.co.il/bizpop/?siteID=<?=$sid?></A>
</p>
<p>קישור לאונליין עבור מצב מלון<BR>
<A href="https://bizonline.co.il/bizpop/?siteID=<?=$sid?>&hotel=1" target="_blank">https://bizonline.co.il/bizpop/?siteID=<?=$sid?>&hotel=1</A>
</p>
<style>
    
</style>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="asite" value="<?=$sid?>" />
    <div class="priceTable checks">
        <style>

        </style>
<?php
//if (!$extras)
//    echo '<table><tr><td><i>לא הוגדרו תוספות</i></td></tr></table>';

if ($treats){
?>

    <table id="treatTable" style="width:initial">
        <thead>
            <tr>
                <th>סוגי טיפולים</th>
<?php
        echo '<th>' , implode(' <span>דקות</span></th><th>', $allTimes) , ' <span>דקות</span></th>';
?>
            </tr>
        </thead>
        <tbody>
<?php
    foreach($treats as $treatID => $treatName){
?>
            <tr>
                <td style="width:200px" class="cat">
                    <div>
                        <input class="star" type="checkbox" id="treat<?=$treatID?>" name="treats[]" value="<?=$treatID?>" <?=($perms['treatments'][$treatID]['bpHide'] == '*' ? '' : 'checked="checked"')?> />
                        <label for="treat<?=$treatID?>" style="margin-bottom:0"><?=$treatName?></label>
                    </div>
                </td>
<?php
        $hide = ($perms['treatments'][$treatID]['bpHide'] == '*') ? $allTimes : json_decode($perms['treatments'][$treatID]['bpHide'] ?: '[]', true);
        foreach($allTimes as $time){
            if (empty($treatTimes[$treatID]) || !in_array($time, $treatTimes[$treatID]))
                echo '<td class="col"></td>';
            else {
?>
                <td class="col">
                    <input class="num" type="checkbox" id="times<?=$treatID?>_<?=$time?>" name="times[<?=$treatID?>][]" value="<?=$time?>" <?=(in_array($time, $hide) ? '' : 'checked="checked"')?> />
                    <label for="times<?=$treatID?>_<?=$time?>"></label>
                </td>
<?php
            }
        }
?>
            </tr>
<?php
    }
?>
        </tbody>
    </table>

<div class="cat" style="width:100%;white-space:nowrap;overflow-x:auto;"></div>
<?php
}

?>

<style>
    
    </style>

<div class="cat genColor">
    <h2>שינוי צבע כללי</h2>
    <input type="color" name="popBg" value="<?=$siteData['popBg']?>">
</div>

<input type="hidden" name="minGroup" id="minGroup" value="3">
<input type="hidden" name="maxGroup" id="minGroup" value="20">
<!--<div class="cat minMax">
    <h2>מינימום\מקסימום לקבוצה</h2>
    <div class="inputWrap">
        <label for="minGroup">מינימום לקבוצה</label>
        <div></div>
        <input type="number" name="minGroup" id="minGroup" value="<?=$siteData['minGroup']?>">
    </div>
    <div class="inputWrap">
        <label for="maxGroup">מקסימום לקבוצה</label>
        <div></div>
        <input type="number" name="maxGroup" id="maxGroup" value="<?=$siteData['maxGroup']?>">
    </div>
</div>-->


<div class="cat logoPicture">
    <h2>תמונת לוגו</h2>
    <div class="inputWrap" style="padding:0;">
        <?php if($siteData['logoPicture']) { ?><div style="padding:0;" class="pic"><img style="max-width:200px;" src="/gallery/<?=$siteData['logoPicture']?>"></div><?php } ?>
        <input type="file" name="logoPicture" value="">
    </div>
</div>
<div class="cat logoPicture">
    <h2>תמונה ראשונה לפופ</h2>
    <div class="inputWrap" style="padding:0;">
        <?php if($siteData['mainPicture']) { ?><div style="padding:0;" class="pic"><img style="max-width:200px;" src="/gallery/<?=$siteData['mainPicture']?>"></div><?php } ?>
        <input type="file" name="mainPicture" value="">
    </div>
</div>


<div class="cat popSettings">
    <h2>הגדרות פופ אונליין</h2>
    <div>
        <input type="checkbox" id="useBizOnLinePop" value="1" name="useBizOnLinePop" <?=$perms['bizpop']['setting']['useBizOnLinePop'] ? " checked " : "";?>>
        <label for="useBizOnLinePop">השמשת פופ אונליין בבית העסק</label>
    </div>
    <div>
        <input type="checkbox" id="hideOnBizIncludedExtras" name="hideOnBizIncludedExtras" <?=$perms['bizpop']['setting']['hideOnBizIncludedExtras'] ? " checked " : "";?>>
        <label for="hideOnBizIncludedExtras">הסתר תוספות הכלולות בטיפול</label>
    </div>
    <div>
        <input type="checkbox" id="hideOnBizOnDemandExtras"  name="hideOnBizOnDemandExtras" <?=$perms['bizpop']['setting']['hideOnBizOnDemandExtras'] ? " checked " : "";?>>
        <label for="hideOnBizOnDemandExtras">תוספות בתשלום</label>
    </div>
	<div>
        <input type="checkbox" id="hideOnBizReviews" value="1" name="hideOnBizReviews" <?=$perms['general']['setting']['hideOnBizReviews'] ? " checked " : "";?>>
        <label for="hideOnBizReviews">הסתר חוות דעת</label>
    </div>
    <div>
        <input type="checkbox" id="needRoom" value="1" name="needRoom" <?=$perms['bizpop']['setting']['needRoom'] ? " checked " : "";?>>
        <label for="needRoom">הקצאת חדרים אוטומטית בהזמנה</label>
    </div>
    <div class="inputWrap " style="padding:0;">
        <label for="minPatients">מינימום מטופלים</label>
        <div></div>
        <input type="number" name="minPatients" id="minPatients" value="<?=$perms['bizpop']['setting']['minPatients']?$perms['bizpop']['setting']['minPatients']:0?>">
    </div>
    <div class="inputWrap " style="padding:0;">
        <label for="maxPatients">מקסימום מטופלים</label>
        <div></div>
        <input type="number" name="maxPatients" id="maxPatients" value="<?=$perms['treatments']['patientsLimit']['maxPatients']?:0?>">
    </div>
    <div class="inputWrap" style="padding:0;">
        <label for="orderStep">תחילת טיפולים</label>
        <select name="orderStep" id="orderStep" class="popup_text_p">
            <option value="30">טרם נבחר יוגדר כחצי שעה</option>
            <option value="60" <?=intval($perms['bizpop']['setting']['orderStep']) == 60 ? " selected " : "";?>>שעה עגולה</option>
            <option value="30" <?=intval($perms['bizpop']['setting']['orderStep']) == 30 ? " selected " : "";?>>חצי שעה</option>
        </select>
    </div>
    <div class="inputWrap" style="padding:0;display: none;">
        <label for="popBizPayOptions">אפשרות תשלום</label>
        <select name="popBizPayOptions" id="popBizPayOptions" class="popup_text_p">
            <option value="0" <?=intval($perms['bizpop']['setting']['popBizPayOptions']) == 0 ? " selected " : "";?>>גם וגם</option>
            <option value="1" <?=$perms['bizpop']['setting']['popBizPayOptions'] == 1 ? " selected " : "";?>>תשלום ביום ההגעה</option>
            <option value="2" <?=$perms['bizpop']['setting']['popBizPayOptions'] == 2 ? " selected " : "";?>>תשלום עכשיו</option>
        </select>
    </div>
    <div class="inputWrap" style="padding:0;">
        <label for="bizPopDefaultTID">טיפול ברירת מחדל בפופ </label>
        <select name="bizPopDefaultTID" id="bizPopDefaultTID" class="popup_text_p">
            <option value="-1">נא לבחור טיפול</option>
            <?php foreach($treats as $treatID => $treatName){?>
                <option value="<?=$treatID?>" <?=$perms['bizpop']['setting']['bizPopDefaultTID'] == $treatID ? " selected " : "";?> ><?=$treatName?></option>
            <?php }?>
        </select><BR>
        <small style="color: red">חובה לבחור טיפול וזמן תואמים למחירים באתר</small>
    </div>
    <div class="inputWrap" style="padding:0;">
        <label for="bizPopDefaultTDur">זמן ברירת מחדל לטיפולים בפופ</label>
        <select name="bizPopDefaultTDur" id="bizPopDefaultTDur" class="popup_text_p">
            <option value="-1">נא לבחור זמן טיפול</option>
            <?php foreach($allTimes as $time){?>
            <option value="<?=$time?>" <?=$perms['bizpop']['setting']['bizPopDefaultTDur'] == $time ? " selected " : "";?> > דקות </option>
            <?php }?>
        </select><BR>
		<script>
			setTimeout(function(){
				$('#bizPopDefaultTDur option').each(function(){
					if($(this).val()>0){
						$(this).html($(this).val()+" "+$(this).html())
					}
				});
			},1000)
		</script>
        <small style="color: red">חובה לבחור טיפול וזמן תואמים למחירים באתר</small>
    </div>
    <div class="inputWrap" style="padding:0;">
        <label for="bizPopDefaultTreaterSex">ברירת מחדל להעדפת מטפל/ת</label>
        <select name="bizPopDefaultTreaterSex" id="bizPopDefaultTreaterSex" class="popup_text_p">
            <option value="0" <?=$perms['bizpop']['setting']['bizPopDefaultTreaterSex'] == 0 ? " selected " : "";?>>ללא העדפה</option>
            <option value="1" <?=$perms['bizpop']['setting']['bizPopDefaultTreaterSex'] == 1 ? " selected " : "";?>>מטפלת</option>
            <option value="2" <?=$perms['bizpop']['setting']['bizPopDefaultTreaterSex'] == 2 ? " selected " : "";?>>מטפל</option>
        </select>
    </div>
</div>
<div class="cat hotelPopSettings">
    <h2>הגדרות פופ אונליין מלון</h2>
    <div>
        <input type="checkbox" id="hotelShowAccomo" name="hotelShowAccomo" value="1" <?=$perms['bizpop']['setting']['hotelShowAccomo'] ? " checked " : "";?>>
        <label for="hotelShowAccomo">הסתר תוספי לינה</label>
    </div>
    <div>
        <input type="checkbox" id="hotelShowGeneralExtras" name="hotelShowGeneralExtras" value="1" <?=$perms['bizpop']['setting']['hotelShowGeneralExtras'] ? " checked " : "";?>>
        <label for="hotelShowGeneralExtras">הסתר תוספים נוספים</label>
    </div>
    <div>
        <input type="checkbox" id="hotelShowAccomp" name="hotelShowAccomp" value="1" <?=$perms['bizpop']['setting']['hotelShowAccomp'] ? " checked " : "";?>>
        <label for="hotelShowAccomp">הסתר אפשרות מלווים</label>
    </div>
    <div>
        <input type="checkbox" id="popHotelShowFreeExtras" name="popHotelShowFreeExtras" value="1" <?=$perms['bizpop']['setting']['popHotelShowFreeExtras'] ? " checked " : "";?>>
        <label for="popHotelShowFreeExtras">הסתר תוספות כלולות במחיר</label>
    </div>
    <div>
        <input type="checkbox" id="popHotelShowPayedExtras" name="popHotelShowPayedExtras" value="1" <?=$perms['bizpop']['setting']['popHotelShowPayedExtras'] ? " checked " : "";?>>
        <label for="popHotelShowPayedExtras">הסתר תוספות בתשלום</label>
    </div>
</div>

<div class="cat orderTypesForShow">
    <h2>בחירת סוגי הזמנה לתצוגה</h2>

    <?php
    ?>
    <table class="not">
        <thead>
            <tr>
                <th>מלון</th>
                <th>ביזפופ</th>
                <th></th>
                <th>שם בפופ מלון</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><div style="position:relative"><input type="checkbox" id="htypes1" name="hsingleOrder" value="1" <?=checkPermission($siteData['bitWise_h'], 1)?"checked":""?>><label for="htypes1"></label></div></td>
                <td><div style="position:relative"><input type="checkbox" id="otypes1" name="singleOrder" value="1" <?=checkPermission($siteData['bitWise'], 1)?"checked":""?>><label for="otypes1"></label></div></td>
                <td>הזמנה ליחיד</td>
                <td><input type="text" name="singleButtonsText" value="<?=$perms['bizpop']['singleButtonsText']?>" ></td>
            </tr>
            <tr>
                <td><div style="position:relative"><input type="checkbox" id="htypes2" name="hcoupleOrder" value="2" <?=checkPermission($siteData['bitWise_h'], 2)?"checked":""?>><label for="htypes2"></label></div></td>
                <td><div style="position:relative"><input type="checkbox" id="otypes2" name="coupleOrder" value="2" <?=checkPermission($siteData['bitWise'], 2)?"checked":""?>><label for="otypes2"></label></div></td>
                <td>הזמנה לזוג</td>
                <td><input type="text" name="couplesButtonsText"></td>
            </tr>
            <tr>
                <td><div style="position:relative"><input type="checkbox" id="htypes4" name="hgroupOrder" value="4" <?=checkPermission($siteData['bitWise_h'], 4)?"checked":""?>><label for="htypes4"></label></div></td>
                <td><div style="position:relative"><input type="checkbox" id="otypes4" name="groupOrder" value="4" <?=checkPermission($siteData['bitWise'], 4)?"checked":""?>><label for="otypes4"></label></div></td>
                <td>הזמנה לקבוצה</td>
                <td><input type="text" name="groupOrder_hotelLabel"></td>
            </tr>
            <tr>
                <td><div style="position:relative"><input type="checkbox" id="htypes8" name="hfunDayOrder" value="8" <?=checkPermission($siteData['bitWise_h'], 8)?"checked":""?>><label for="htypes8"></label></div></td>
                <td><div style="position:relative"><input type="checkbox" id="otypes8" name="funDayOrder" value="8" <?=checkPermission($siteData['bitWise'], 8)?"checked":""?>><label for="otypes8"></label></div></td>
                <td>יום כיף</td>
                <td><input type="text" name="funDayOrder_hotelLabel"></td>
            </tr>
            <tr>
                <td><div style="position:relative"><input type="checkbox" id="htypes16" name="hsleepOrder" value="16" <?=checkPermission($siteData['bitWise_h'], 16)?"checked":""?>><label for="htypes16"></label></div></td>
                <td><div style="position:relative"><input type="checkbox" id="otypes16" name="sleepOrder" value="16" <?=checkPermission($siteData['bitWise'], 16)?"checked":""?>><label for="otypes16"></label></div></td>
                <td>לינה</td>
                <td><input type="text" name="sleepOrder_hotelLabel"></td>
            </tr>
        </tbody>    
    </table>
    <div class="txtAreaWrap ">
        <div class="ttl">% הנחה לאורחי מלון</div>
        <input name="bizpophoteldiscount" type="tel" class="bizpophoteldiscount" value="<?=$perms['bizpop']['setting']['bizpophoteldiscount']?>">
    </div>
</div>
<?php


foreach($typesNames as $tCode => $tName){

    if (isset($extras[$tCode])){
?>
<div class="cat">
    <h2><?=$tName?></h2>
<?php
        foreach($extras[$tCode] as $extra){
?>
    <div>
        <input type="checkbox" id="extra<?=$extra['extraID']?>" name="extras[]" value="<?=$extra['extraID']?>" <?=($perms['extras'][$extra['extraID']]['bpHide'] ? '' : 'checked="checked"')?> />
        <label for="extra<?=$extra['extraID']?>"><?=$extra['extraName']?></label>
    </div>
<?php
        }
?>
</div>
<?php
    }
}
?>
<div class="txtAreaWrap cat">
    <div class="ttl">טקסט meta description</div>
    <input name="bizpopdesc" type="text" class="bizpopdesc" value="<?=$perms['general']['texts']['bizpopdesc']?>">
</div>
        <div class="txtAreaWrap cat">
            <div class="ttl">טקסט תנאים והגבלות</div>
            <textarea name="limitsText" class="limitsText"><?=$siteData['limitsText']?></textarea>
        </div>
        <div class="txtAreaWrap cat">
            <div class="ttl">טקסט חשוב לדעת</div>
            <textarea name="mustKnowText" class="mustKnowText"><?=$siteData['mustKnowText']?></textarea>
        </div>
        <input type="submit" id="submitTreats" value="שמור">
    </div>
</form>
<script>
$(function(){
    $('#treatTable').find('input.star').on('click', function(){
            $(this).closest('tr').find('input.num').prop('checked', this.checked);
        }).end()
        .find('input.num').on('click', function(){
            let tr = $(this).closest('tr'), check = tr.find('input.num:checked').length;
            tr.find('input.star').prop('checked', check ? true : false);
        });
});
</script>
