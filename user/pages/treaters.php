<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$siteID = $_CURRENT_USER->active_site() ?: 0;
?>
<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=treaters&v=<?=rand()?>" rel="stylesheet">
<?php

//$siteID = intval($_POST['site']) ?: intval($_GET['site']) ?: $_CURRENT_USER->active_site();
//
//if (!$_CURRENT_USER->has($siteID)){
//    echo "שגיאת מערכת";
//    exit;
//}

function js_safe($str, $replace = ''){
    $base = ['"' => '&quot;', "'" => '&#039;'];
    return strtr($str, $replace ? (is_array($replace) ? $replace : [$replace => $base[$replace]]) : $base);
}

$therapistID = intval($_POST['tID'] ?? $_GET['tID'] ?? 0);

if($_GET['del_insurance'] == 1) {
    $update = udb::query('UPDATE therapists SET insurance_file=\'\' WHERE `therapistID` = ' . $therapistID);
    echo '<script>document.location= \''.$_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'].str_replace('&del_insurance=1', '', $_SERVER['REQUEST_URI']).'\'</script>';
}

if ('POST' == $_SERVER['REQUEST_METHOD']){

/*    if($_POST['fictive']){
        $tc = udb::single_value("SELECT COUNT(*) FROM `therapists` WHERE siteID = " . $siteID . " AND `workerType` = 'fictive'");
        $_POST = [
                'siteName'      => 'ללא מטפל ' . ($tc + 1),
                'active'        => 1,
                'gender_self'   => 3,
                'gender_client' => 3,
                'workerType'    => 'fictive'
        ];
    }*/

    $isError = '';

    function createPath($path, $suff)
    {
        global $therapistID;
        $path = rtrim($path,'/').'/';
        $file = str_replace('.','', microtime(true)).($suff ? '.'.$suff : '');
        while(file_exists($path.$file))
            $file = str_replace('.','', microtime(true)).($suff ? '.'.$suff : '');
    
        return $path.$therapistID.'_'.$file;
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

    try {
//        $active = 0;
//        if ($therapistID)
//            list($active) = udb::single_row("SELECT `active` FROM `therapists` WHERE `therapistID` = " . $therapistID, UDB_NUMERIC);
		$_POST['insurance_expiry_date'] = implode('-',array_reverse(explode('.',$_POST['insurance_expiry_date'])));
		$_POST['workStart'] = implode('-',array_reverse(explode('.',$_POST['workStart'])));
		$_POST['workEnd'] = implode('-',array_reverse(explode('.',$_POST['workEnd'])));
        $data = typemap($_POST, [
            'siteName'   => 'string',
            'phone'      => 'string',
            'insurance_expiry_date'      => 'date',
            'workStart'      => 'date',
            'workEnd'      => 'date',
            'email'      => 'email',
            'active'      => 'int',
            'gender_self'      => 'int',
            'gender_client'      => 'int',
            'salary_type'      => 'int',
            'address'   => 'string',
            'password'  => 'string',
            'bankName'  => 'string',
            'charge' => 'float',
            'bankNumber'  => 'string',
            'bankBranch'  => 'string',
            'bankAccount'  => 'string',
            'bankAcoountOwner'  => 'string',
            'attributes' => ['int' => 'int'],
            'per' => 'string',
            'userName' => 'string',
            'upass' => 'string',
            'tz' => 'string',
            'fictive' => 'int'
        ]);

        $fileArr=Array();
        if(count($_FILES))
		{
			$photos = fileUpload('insurance_file',__DIR__."/../../insurances/");

			if(isset($photos)){
				foreach($photos as $key=>$photo){
					$fileArr['src']=$photo['file'];
				}
			}
		}




        $bankData = [
            'bankName'  => $data['bankName'],
            'bankNumber'  => $data['bankNumber'],
            'bankBranch'  => $data['bankBranch'],
            'bankAccount'  => $data['bankAccount'],
            'bankAcoountOwner'  => $data['bankAcoountOwner']
        ];

        $bankData = json_encode($bankData, true);

// main site data
        if ($data['fictive']){
            if (!$therapistID){
                $tc = udb::single_value("SELECT COUNT(*) FROM `therapists` WHERE siteID = " . $siteID . " AND `workerType` = 'fictive'");
                $data['siteName'] = 'מטפל ' . ($tc + 1);
            }

            $siteData = [
                'workerType' => 'fictive',
                'siteName'      => $data['siteName'],
                'active'        => 1,
                'gender_self'   => $data['gender_self'] ?: 3,
                'gender_client' => 3
            ];
        } else {
            if (!$data['siteName'])
                throw new Exception('חייב להיות שם בעברית');

			if($data['tz'] && !checktz(intval($data['tz'])))
				throw new Exception('מספר תעודת זהות לא תקין');

            $siteData = [
//                'active'       => $data['active'][1] ?? 0,
                'workerType'   => 'regular',
                'siteName'     => $data['siteName'],
                'email'        => $data['email'],
                'address'        => $data['address'],
//                'charge'        => $data['charge'],
                'gender_self'  => $data['gender_self'],
                'gender_client' => $data['gender_client'],
                'phone'        => $data['phone'],
                'insurance_expiry_date'        => $data['insurance_expiry_date'] ?: '0000-00-00',
                'active'        => $data['active'],
                'userName'      => $data['userName'],
                'tz'			=> $data['tz'],
                'bankData' => $bankData,
                'salary_type' => $data['salary_type']?: 0
            ];
            if($fileArr['src'])
                $siteData['insurance_file'] = '/insurances/'.$fileArr['src'];

            if ($data['userName']){
                $tmp = udb::single_value("SELECT `therapistID` FROM `therapists` WHERE `deleted` = 0 AND `workerType` <> 'fictive' AND `userName` = '" . udb::escape_string($data['userName']) . "' AND `therapistID` <> " . $therapistID);
                if ($tmp)
                    throw new Exception('שם המשתמש קיים');
            }

            if ($data['upass'])
                $siteData['password'] = password_hash($data['upass'], PASSWORD_DEFAULT);

        }

//        if($therapistID) {
//            $workerType    = udb::single_value("SELECT workerType FROM `therapists` WHERE therapists.deleted=0 and  `therapists`.`therapistID` = " . $therapistID);
//            $isFake = ($workerType == "fictive") ? 1 : 0;
//            if($isFake == 1) {
//                $siteData = [
//                        'siteName' => $data['siteName'],
//                        'gender_self'  => $data['gender_self']
//                ];
//            }
//        }


//save attributes

        if (!$therapistID){
            $siteData['siteID'] =  $siteID;
            $therapistID = udb::insert('therapists', $siteData);

        } else {
            udb::update('therapists', $siteData, '`therapistID` = ' . $therapistID);
        }

        // special update for working date (coz of NULL) - need to be replaced (or merged with main update) once UDB supports NULL
        udb::query("UPDATE `therapists` SET `workStart` = " . ($data['workStart'] ? "'" . $data['workStart'] . "'" : "NULL") . ", `workEnd` = " . ($data['workEnd'] ? "'" . $data['workEnd'] . "'" : "NULL") . " WHERE `therapistID` = " . $therapistID);

//$olda = udb::single_column("select treatmentID from therapists_treats where therapistID=".$therapistID);
        udb::query('DELETE FROM `therapists_treats` WHERE therapistID=' . $therapistID );

        if($data['attributes'] && count($data['attributes'])){
            $que = [];
            foreach($data['attributes'] as $attr)
                $que[] = '(' . $therapistID . ', ' . $attr . ')';
            $upsql = "INSERT INTO `therapists_treats` (`therapistID`, `treatmentID` ) VALUES" . implode(',', $que); //. " ON DUPLICATE KEY UPDATE `therapistID` = VALUES(`therapistID`)";
            udb::query($upsql);
            unset($que);
        }

        if ($data['per']){
            $mRow = udb::single_value("SELECT `salary` FROM `therapists` WHERE `active` = 1 AND `therapistID` = " . $therapistID);
            $mRow = json_decode($mRow, true);

            $mRow['activeType'] = $data['per'];
            udb::update('therapists', ['salary' => json_encode($mRow, JSON_NUMERIC_CHECK)], '`therapistID` = ' . $therapistID);
        }

        if($data['fictive']){
?>
            <script>
                window.location.href = '?page=treaters';
            </script>
<?php
            return;
        }

    }
    catch (Exception $e){
        // show error
        $isError = $e->getMessage();
    }

    if ($isError)
        echo '<script>alert("' . str_replace('"', '\"', $isError) . '");</script>';
/*?>
<script><?=()?>
        <?if($isError) {?>
        alert('<?=$isError?>');
        <?} else
        {?>
        //window.parent.location.reload();
        <?}?>
    </script>
    <?php*/

}

if(isset($_GET['tID'])) {

$siteData = $siteDomains = $siteLangs = [];
/*$sitesTretmentsSQL = "SELECT treatments FROM `sitesTratments` where bizSiteID in (".$siteID.")";
//echo $sitesTretmentsSQL;
$sitesTretments = udb::single_column($sitesTretmentsSQL);
$useTreats = [];
foreach ($sitesTretments as $t) {
    $currTreats = json_decode($t,true);
    if(is_array($currTreats)) {
        foreach ($currTreats as $tr) {
            if(isset($tr['id']))
                $useTreats[] = intval($tr['id']);
        }
    }
}

if(count($useTreats)>0){
	$tratsSQL = "SELECT * FROM `treatments` where spaplusID in (".implode(",",$useTreats).")";
	//echo $tratsSQL;
	$treatments = udb::full_list($tratsSQL);
}*/

$treatments = udb::full_list("select treatments.* from treatmentsPricesSites INNER JOIN treatments USING(treatmentID) where siteID=".$siteID." GROUP BY treatments.treatmentID");
$tTreats = udb::single_column("select treatmentID from therapists_treats where therapistID=".$therapistID);
$isFake = 0;
if ($therapistID){
    $siteData    = udb::single_row("SELECT * FROM `therapists` WHERE therapists.deleted=0 and  `therapists`.`therapistID` = " . $therapistID);
    $isFake = ($siteData['workerType'] == "fictive") ? 1 : 0;
}
?>


    <div class="editItems">
	<div class="popGallery">
		<div class="popGalleryCont"></div>
	</div>
	<a class="backbtn" href="?page=<?=$_GET["page"]?>">חזרה</a>
	<div class="siteMainTitle"><?=$sname[$siteData['siteID']]?: $siteName?> -  <?=$siteData['siteName']?: "מטפל חדש"?></div>
	<div class="inputLblWrap ">
	<div class="frameContent">
		<form method="post" enctype="multipart/form-data" >
			 <input type="hidden" id="tID" name="tID" value="<?=$siteData['therapistID']?>">
             <input type="hidden" name="fictive" value="<?=$isFake?>" />
			<div class="mainSectionWrapper">
				<div class="sectionName">כללי</div>
			<div class="inputLblWrap">
				<div class="labelTo">שם המטפל</div>
				<input type="text" placeholder="שם המטפל" name="siteName" value="<?=js_safe($siteData['siteName'])?>" />
			</div>
			<div class="inputLblWrap">
			<div class="labelTo">מגדר</div>
			<select name="gender_self">
				<option value="0" <?=!$siteData['gender_self']?"selected='selected'":""?>>- בחירה -</option>
				<option value="1" <?=$siteData['gender_self'] == 1?"selected='selected'":""?>>גבר</option>
				<option value="2" <?=$siteData['gender_self'] == 2?"selected='selected'":""?>>אישה</option>
				<?if($siteData['workerType'] == "fictive"){?><option value="3" <?=$siteData['gender_self'] == 3?"selected='selected'":""?>>ללא מגדר</option><?}?>
			</select>
		</div>
                <div class="inputLblWrap">
                    <div class="checkLabel checkIb">
                        <div class="checkBoxWrap">
                            <input class="checkBoxGr" type="checkbox" name="active" <?=$siteData['active']?"checked":""?> value="1" id="active">
                            <label for="active"></label>
                        </div>
                        <label for="active">פעיל</label>
                    </div>
                </div>
                <?

                if($siteData['workStart'] || $siteData['workEnd']){?>
                <div>
                    <div class="inputLblWrap">
                        <div class="labelTo">מתאריך</div>
                        <input type="text" placeholder="מתאריך" autocomplete="off" name="workStart" id="workStart" placeholder="מתאריך" readonly class="dpicker" data-next="#workEnd" value="<?=implode(".",array_reverse(explode('-',$siteData['workStart'])))?>" />
                    </div>
                    <div class="inputLblWrap">
                        <div class="labelTo">עד תאריך</div>
                        <input type="text" placeholder="עד תאריך" autocomplete="off" name="workEnd" id="workEnd" placeholder="עד תאריך" readonly class="dpicker" data-prev="#workStart" value="<?=implode(".",array_reverse(explode('-',$siteData['workEnd'])))?>" />
                    </div>
                </div>

			<?}
			if($isFake == 0) {?>
          <div class="inputLblWrap">
				<div class="labelTo">כתובת</div>
				<input type="text" placeholder="כתובת" name="address" value="<?=js_safe($siteData['address'])?>" />
			</div>
            <div class="inputLblWrap">
			<div class="labelTo">אימייל</div>
			<input type="text" placeholder="אימייל" name="email" value="<?=$siteData['email']?>" />
		    </div>

		
		<div class="inputLblWrap">
			<div class="labelTo">מגדר מועדף</div>
			<select name="gender_client">
				<option value="0" <?=!$siteData['gender_client']?"selected='selected'":""?>>- בחירה -</option>
				<option value="1" <?=$siteData['gender_client'] == 1?"selected='selected'":""?>>גבר</option>
				<option value="2" <?=$siteData['gender_client'] == 2?"selected='selected'":""?>>אישה</option>
				<option value="3" <?=$siteData['gender_client'] == 3?"selected='selected'":""?>>לא משנה</option>
			</select>
		</div>
		<div class="inputLblWrap">
			<div class="labelTo">טלפון</div>
			<input type="text" placeholder="טלפון" name="phone" value="<?=js_safe($siteData['phone'])?>" />
		</div>
		<div class="inputLblWrap">
			<div class="labelTo">תעודת זהות</div>
			<input type="text" placeholder="תעודת זהות" name="tz" value="<?=js_safe($siteData['tz'])?>" />
		</div>
		<div class="inputLblWrap">
			<div class="labelTo"><?php if($siteData['insurance_file']) { ?>פוליסת ביטוח - העלה חדש
            
            <?php }else{ ?>פוליסת ביטוח העלה קובץ <?}?></div>
            
			<input type="file" placeholder="" name="insurance_file" value="<?=js_safe($siteData['insurance_file'])?>" />
			<?if($siteData['insurance_file']) { ?><br><a target="_blank" style="color: #0dabb6;font-weight: bold;" href="<?=$siteData['insurance_file']?>">קישור לפוליסה הקיימת</a> <a style="color: #e73219;font-weight: bold;" href="<?=$_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'], '?') !== false?"&":"?")?>del_insurance=1">מחיקת פוליסה</a><?}?>
		</div>
		<div class="inputLblWrap">
			<div class="labelTo">תוקף פוליסה</div>
			<input type="text" class="dpicker" placeholder="תוקף פוליסה" name="insurance_expiry_date" readonly value="<?=implode(".",array_reverse(explode('-',$siteData['insurance_expiry_date'])))?>" />
		</div>

        <div class="inputLblWrap">
            <div class="labelTo">שם משתמש</div>
            <input type="text" placeholder="שם משתמש" name="userName" value="<?=js_safe($siteData['userName'])?>" />
        </div>
        <div class="inputLblWrap">
            <div class="labelTo">סיסמא</div>
            <input type="text" placeholder="<?=($siteData['password'] ? 'סיסמא חדשה' : 'סיסמא')?>" name="upass" value="" />
        </div>
		<div class="inputLblWrap">
			<div class="labelTo">סוג העסקה</div>
			<select name="salary_type">
				<option value="0" <?=!$siteData['salary_type']?"selected='selected'":""?>>עצמאי</option>
				<option value="1" <?=$siteData['salary_type'] == 1?"selected='selected'":""?>>שכיר</option>
			</select>
		</div>

        

		<?php /*<div class="checkLabel checkIb">
			<div>תשלום ברירת מחדל <span>₪1.2</span> לדקה</div>
			<div class="checkBoxWrap">
				<input class="checkBoxGr" id="dpay" type="checkbox" name="defaultpayment" <?=$siteData['defaultpayment']?"checked":""?> value="1" id="active">
				<label for="active"></label>
			</div>
			<label for="active">תעריף אישי</label>
			<div class="inputLblWrap"><input type="text" placeholder="תעריף" name="payment" value="<?=js_safe($siteData['payment'])?>" /></div>
			
		</div>*/ ?>

<?php

    $today = date('Y-m-d');
	if($therapistID){
    $salary = new SalaryMaster($therapistID);

    $selectedType = $salary->active_type($today);                  // currently selected rates type
    $todayMinute  = $salary->get_day_salary($today, 'minute');     // minute rates for today
    $todayPercent = $salary->get_day_salary($today, 'percent');    // percent rates for today
    $todayDefault = $salary->get_day_salary($today, 'default');    // default (currently selected site's) rates for today

    $lastSalary    = $salary->get_last_salary();

    $defDate = $lastSalary['type']->date ? date('01.m.Y', strtotime('next month')) : date('d.m.Y');


//    $defs = udb::single_value("SELECT `salaryDefault` FROM `sites` WHERE `siteID` = " . $siteID);
//    $defs = $defs ? json_decode($defs, true) : [];
//
//    $sett = udb::single_value("SELECT `salary` FROM `therapists` WHERE `therapistID` = " . $therapistID);
//    $sett = $sett ? json_decode($sett, true) : ['activeType' => 'default'];
//
//    $defDate = $sett ? date('01.m.Y', strtotime('next month')) : date('d.m.Y');
//
//    $nextChange = udb::key_row("SELECT `salaryType`, `salaryDay`, `salaryRate`, `startFrom` FROM `salaryLog` WHERE `startFrom` > CURDATE() AND `targetType` = 'therapist' AND `targetID` = " . $therapistID . " ORDER BY `logID`", ['salaryType', 'salaryDay']);

    switch($todayDefault->type){
        case 'minute':
            $texts = [
                'title'  => 'פר דקה',
                'number' => '₪N',
            ];
            break;

        case 'percent':
            $texts = [
                'title'  => 'לפי אחוזים',
                'number' => 'N%',
            ];
            break;

        default:
            $texts = [
                'title'  => 'לא נבחר',
                'number' => '-',
            ];
    }
?>
<div class="per-single-percent">
    
   <div class="per per-percent">
      <input type="radio" name="per" id="perpercent-default" value="default" <?=($selectedType == 'default' ? 'checked' : '')?>>
      <label for="perpercent-default">ברירת מחדל - <?=$texts['title']?></label>
      <div>
         <div class="reg days">
            <span class="amount"><?=str_replace('N', $todayDefault->rateRegular ?: '-', $texts['number'])?></span>
            <span class="title">ימים רגילים</span>
         </div>
         <div class="reg weekend">
            <span class="amount"><?=str_replace('N', $todayDefault->rateWeekend ?: '-', $texts['number'])?></span>
            <span class="title">סופ"ש וחגים</span>
         </div>
      </div>
   </div>
</div>
<div class="pers">
   <div class="per per-minute">
      <input type="radio" name="per" id="perminute" value="minute" <?=($selectedType == 'minute' ? 'checked' : '')?> />
      <label for="perminute">לפי דקה</label>
      <div class="type-data type-minute" <?=($selectedType == 'minute' ? '' : 'style="visibility:hidden"')?>>
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
                   <div>ישתנה ל-₪<?=($last->value ?: '')?></div>
                   <div>החל מ-<?=db2date($last->date, '.', 2)?></div>
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
                    <div>ישתנה ל-₪<?=($last->value ?: '')?></div>
                    <div>החל מ-<?=db2date($last->date, '.', 2)?></div>
                </div>
            </div>
         </div>
      </div>
   </div>

   <div class="per per-percent">
      <input type="radio" name="per" id="perpercent" value="percent" <?=($selectedType == 'percent' ? 'checked' : '')?> />
      <label for="perpercent">לפי אחוזים</label>
       <div class="type-data type-percent" <?=($selectedType == 'percent' ? '' : 'style="visibility:hidden"')?>>
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
                 <span>₪ החל מ-</span>
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
                   <div>ישתנה ל-₪<?=($last->value ?: '')?></div>
                   <div>החל מ-<?=db2date($last->date, '.', 2)?></div>
               </div>
            </div>
         </div>
<?php
    $last = $lastSalary['percent']['wend'];
    $class = (strcmp($last->date, $today) > 0) ? 'changed' : 'edit';
?>
         <div class="reg-weekend">
             <span class="visible">
                <span class="amount"><span><?=($todayPercent->rateWeekend ?: '-')?></span>%</span>
                <span class="title">סופ"ש וחגים</span>
             </span>
             <div class="editable">
                 <span>%</span>
                <input type="text" name="percent[wend]" id="percentamountt_weekend" value="<?=($last->value ?: '')?>" title="" />
                 <span>₪ החל מ-</span>
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
                   <div>ישתנה ל-₪<?=($last->value ?: '')?></div>
                   <div>החל מ-<?=db2date($last->date, '.', 2)?></div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<?}?>

		</div><?}?>
        <?if($isFake == 0) {?><div class="mainSectionWrapper">
				<div class="sectionName">חשבון בנק</div>
				<div class="inSectionWrap">
					<?php if($siteData['bankData']) { $bData = json_decode($siteData['bankData'], true); } ?>
                    <div class="inputLblWrap">
						<div class="labelTo">שם בנק</div>
						<input type="text" placeholder='שם הבנק' name="bankName" value="<?=$bData['bankName']?>" />
					</div>

					<div class="inputLblWrap">
						<div class="labelTo">מספר בנק</div>
						<input type="text" placeholder='מספר בנק' name="bankNumber" value="<?=$bData['bankNumber']?>" />
					</div>

					<div class="inputLblWrap">
						<div class="labelTo">מספר סניף</div>
						<input type="text" placeholder='מספר סניף' name="bankBranch" value="<?=$bData['bankBranch']?>" />
					</div>

					<div class="inputLblWrap">
						<div class="labelTo">מספר חשבון</div>
						<input type="text" placeholder='מספר חשבון' name="bankAccount" value="<?=$bData['bankAccount']?>" />
					</div>

					<div class="inputLblWrap">
						<div class="labelTo">שם בעל החשבון</div>
						<input type="text" placeholder='שם הבעל החשבון' name="bankAcoountOwner" value="<?=$bData['bankAcoountOwner']?>" />
					</div>
				</div>
			</div>
            <div class="mainSectionWrapper attr">
				<div class="sectionName">טיפולים</div>
                    <div class="checksWrap">
						<div><span class="checkall">סמן הכל</span></div>
						<?php
						if(is_array($treatments)){
						foreach($treatments as $attribute) { ?>
                            <div class="checkLabel checkIb">
							<div class="checkBoxWrap">
								<input class="checkBoxGr" type="checkbox" name="attributes[]" <?=(in_array($attribute['treatmentID'],$tTreats)?"checked":"")?> value="<?=$attribute['treatmentID']?>" id="ch<?=$attribute['treatmentID']?>">
								<label for="ch<?=$attribute['treatmentID']?>"></label>
							</div>
							<label for="ch<?=$attribute['treatmentID']?>"><?=$attribute['treatmentName']?></label>
						</div>
                        <?php }} ?>
					</div>
			</div><?}?>
            <input type="submit" value="שמור" id="submitTreats" class="submit">
		</form>
	</div>
</div>
    <?
    $jsDates = $therapistID ? udb::single_column("SELECT DISTINCT DATE(`timeFrom`) FROM `orders` WHERE siteID = " . $siteID . " AND therapistID = " . $therapistID) : [];
}
else {




    $where =" therapists.siteID in (".$siteID.") and therapists.deleted=0 ";
    $sql = "SELECT `therapists`.*,
	COUNT(orders.orderID) AS order_count,
	SUM(orders.status) AS order_active

FROM `therapists`
LEFT JOIN orders USING (`therapistID`)
WHERE " . $where . " GROUP BY `therapists`.`therapistID` ORDER BY `therapists`.`active` DESC, therapists.workerType , therapists.gender_self, `therapists`.`siteName` ASC";
    //echo $sql;
    $therapists = udb::full_list($sql);
    ?>
    <div class="pagePop"><div class="pagePopCont"></div></div>
    <div class="manageItems" id="manageItems">
    <h1>ניהול מטפלים</h1>
<?php
/*    if (!$_CURRENT_USER->single_site){
        $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
	<div class="health_send">
		<div class="site-select">
			בחר מתחם
			<select id="send-site" title="שם מתחם">
				<option value="0">הצג מטפלים למתחם</option>
				<?php
			foreach($sname as $id => $name)
				echo '<option value="' , $id , '" ' , ($id == $sid ? 'selected' : '') , '>' , $name , '</option>';
			?>
			</select>
		</div>
	</div>

<?php
    }*/
?>
	<div style="margin-top:20px;height:40px;line-height:40px">		
		<span>תצוגת טיפולים </span>
		<select onchange="change_limit($(this).val(),<?=$siteID?>)" name="limit_metaplim" style="height:40px">
			<option value=0 <?=$siteData['limit_metaplim']<1? "selected" : ""?>>ללא הגבלה</option>
			<option value=1 <?=$siteData['limit_metaplim']==1? "selected" : ""?>>ליום נוכחי בלבד</option>
			<option value=2 <?=$siteData['limit_metaplim']==2? "selected" : ""?>>יום קדימה מיום נוכחי</option>
			<option value=3 <?=$siteData['limit_metaplim']==3? "selected" : ""?>>יומיים קדימה מיום נוכחי</option>
		</select>
			
	</div>
	<div style="margin-top: 20px;display:flex;flex-wrap:wrap;align-items:start">
		<input type="button" style="margin:10px" class="addNew" id="addNewAcc" data-siteid="<?=$siteID?>" value="הוסף חדש" onclick="openPop(0, $(this).data('siteid'))">
		<form  style="margin:10px" method="post" action="?page=treaters&tID=0&asite=<?=$siteID?>">
			<input type="hidden" name="fictive" value="1" />
			<input type="hidden" name="gender_self" value="3" />
			<input name="site" value="<?=$siteID?>" type="hidden">
			<input type="submit" class="addNew not-empty"  value="הוסף פיקטיבי">
		</form>
		<form  style="margin:10px" method="post" action="?page=treaters&tID=0&asite=<?=$siteID?>">

			<div id="datefictive" class="fictiveform" >
				<div>הוסף פיקטיבי לתאריך</div>
				<input type="hidden" name="fictive" value="1" />
				<input type="hidden" name="gender_self" value="3" />
				<input type="text" name="workStart" placeholder="מתאריך" readonly id="newStart" data-next="#newEnd" class="dtstart" value="" required />
				<input type="text" name="workEnd" placeholder="עד תאריך" readonly id="newEnd" data-prev="#newStart" class="dtstart" value="" required />
				<input name="site" value="<?=$siteID?>" type="hidden">
				<input type="submit" class="addNew not-empty"  value="הוסף פיקטיבי">
			</div>
		</form>
	</div>

	<div id="tblwrap" class="tblMobile">
	<style>
	#tblwrap.notactivet button.activet,#tblwrap:not(.notactivet) button.notactivet {background:#aaa;}
	#tblwrap.notactivet tbody tr:not(.notactivet), #tblwrap:not(.notactivet) tbody tr.notactivet{display:none}
	</style>
	<div style="display:flex">
		<button style="margin-left:10px" class="addNew activet" onclick="$('#tblwrap').removeClass('notactivet')">פעילים בלבד</button>
		<button class="addNew notactivet" onclick="$('#tblwrap').addClass('notactivet')">לא פעילים</button>
	</div>
		
    <table id="therapists">
        <thead>
        <tr>
            <th>ID</th>
            <th>שם המטפל</th>
            <th>מגדר</th>
            <th>העסקה</th>
            <th>תאריך</th>
			<th>טלפון</th>
            <th>דוא"ל</th>
			<th width="40">מוצג</th>
			<th></th>

        </tr>
        </thead>
        <tbody id="sortRow">
<?php
//print_r($therapists);

$therapistNames = array('ללא מטפל','מטפל','פיקטיבי');
$therapistReplace = array('<span>ללא מטפל</span>','<span>מטפל</span>','<span>פיקטיבי</span>');
$count_fictive = 0;
$salary_type[0] = 'עצמאי';
$salary_type[1] = 'שכיר';
if (count($therapists)){
	$genderName[1] = "גבר";
	$genderName[2] = "אשה";
	$genderName[3] = "ללא מגדר";
	$therapistType["regular"] = "רגיל";
	$therapistType["fictive"] = "<i style='color:#aaa'>פיקטיבי</i>";
	//$genderName[3] = "<i style='color:#aaa'>פיקטיבי</i>";
    foreach($therapists as $site){
		if($site['workerType'] == 'fictive') $count_fictive++;
        ?>
        <tr id="<?=$site['therapistID']?>" data-siteID="<?=$site['siteID']?>" class="<?=$site['active']? "" : "notactivet"?>">
                <td><?=$site['therapistID']?></td>
                <td onclick="openPop(<?=$site['therapistID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=str_replace($therapistNames,$therapistReplace,outDb($site['siteName']))?></td>
                <td onclick="openPop(<?=$site['therapistID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=$therapistType[$site['workerType']]?> - <?=$genderName[$site['gender_self']]?></td>
                <td onclick="openPop(<?=$site['therapistID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=$salary_type[$site['salary_type']]?></td>
				<td onclick="openPop(<?=$site['therapistID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')">
<?php
    if ($site['workStart'] && $site['workEnd'])
        echo db2date($site['workStart'], '.') . ' - ' . db2date($site['workEnd'], '.');
    elseif ($site['workStart'])
        echo 'החל מ-' . db2date($site['workStart'], '.');
    elseif ($site['workEnd'])
        echo 'עד ' . db2date($site['workEnd'], '.');
?>
				</td>
                <td onclick="openPop(<?=$site['therapistID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=outDb($site['phone'])?></td>
                <td onclick="openPop(<?=$site['therapistID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=outDb($site['email'])?></td>

                <td><?=($site['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td>
					<?if($site['order_active']){?>
						<b style="color:red"><?=$site['order_active']?> הזמנות</b> <?if($site['order_count']!=$site['order_active']){?>+ <?=$site['order_count'] - $site['order_active']?> מבוטלות<?}?>
					<?}?>
					<?if(!$site['order_active']){?>
						<div style='text-decoration:underline;color:#0dabb6' onclick="if(confirm('האם אתה בטוח רוצה למחוק את המטפל')){delsite(<?=$site['therapistID']?>)}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
					<?}?>
				</td>

            </tr>
<?php
    }
}
?>
        </tbody>
    </table>
	</div>




</div>
    <input type="hidden" id="orderResult" name="orderResult" value="">
    <script>
var pageType="<?=$pageType?>";
function openPop(pageID, siteID){
	if(!siteID){
		swal.fire({
			icon: 'error',
			title: 'שגיאה',
			text: 'יש לבחור מתחם'
		});
	}else{
	 location.href = "?page=treaters&tID=" + pageID + "&asite=" + siteID;
	}
}
function closeTab(){
    $(".pagePopCont").html('');
    $(".pagePop").hide();
}


function delsite(therapistID){
    $.post('ajax_delTreater.php',{'therapistID':therapistID},function(){
        window.location.reload();
    });

}


$('#send-site').change(function(){
	$("#therapists tbody tr").hide();
	if(this.value>0){
		$("#addNewAcc").show();
		$("#addNewAcc").attr('data-siteid',this.value);
		$("#therapists tr[data-siteID='"+ this.value +"']").show();
	}else{
		$("#addNewAcc").hide();
	}
});



</script>
<?php
}
?>
<script>

$('.checkall').on('click',function(){
	//debugger;
	$(this).toggleClass('checked');
	if($(this).hasClass('checked')){	
		$(this).html('בטל הכל');
		$(this).closest('.mainSectionWrapper').find('input').each(function(){$(this).prop('checked',true)});
	}else{
		$(this).closest('.mainSectionWrapper').find('input').each(function(){$(this).prop('checked', false)});
		$(this).html('סמן הכל');
	}
});

function change_limit(limit,siteID){
	debugger;
	$.post('ajax_limit_metaplim.php', {limit:limit,siteID:siteID}).then(function(res){
           if (res.status == 'success'){
				return swal.fire({icon:'info', title:'עודכן בהצלחה'});
		   }else{               
				return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'אראה שגיאה'});
		   }
       });
}

</script>
<script type="text/javascript" src="/assets/js/jquery.ui.datepicker-he.js"></script>
<script>
$(function() {
    var closed = <?=json_encode($jsDates ?? [])?>;

    function closeNext(date, list){
        let found = '';
        if (Array.isArray(list))
            list.forEach(curr => (curr >= date && (!found || curr < found)) ? found = curr : null);

        return found;
    }

    function closePrev(date, list){
        let found = '';
        if (Array.isArray(list))
            list.forEach(curr => (curr <= date && (!found || curr > found)) ? found = curr : null);

        return found;
    }


   $.datepicker.setDefaults( $.datepicker.regional[ "he" ] );

   $('.dtstart').each(function(){
       let data = $(this).data();

       $(this).datepicker({
           minDate: 0,
           dateFormat: 'dd.mm.yy',
           beforeShow: function(){
               let pv = data.prev ? $(data.prev).datepicker('getDate') : null,
                   nv = data.next ? $(data.next).datepicker('getDate') : null;
               return data.prev ? (pv ? {minDate: pv} : {minDate: 0}) : (nv ? {maxDate: nv} : {});
           }
       });
   });

   $('.dpicker').each(function(){
       let data = $(this).data();

       $(this).datepicker({
           dateFormat: 'dd.mm.yy',
           beforeShow: function(){
			   debugger;
               let pv = data.prev ? $(data.prev).datepicker('getDate') : null,
                   nv = data.next ? $(data.next).datepicker('getDate') : null,
                   curr = ($(this).datepicker('getDate') || new Date()).toDB(),
                   prev = closePrev(curr, pv ? closed.concat([pv.toDB()]) : closed),
                   next =  closeNext(curr, nv ? closed.concat([nv.toDB()]) : closed);
               return data.prev ? {minDate: prev ? prev.flipDate('.') : ''} : {maxDate: next ? next.flipDate('.') : ''};
           }
       });
   });

   $('input[name="per"]').on('click', function(){
       $('.type-data').css('visibility', 'hidden').filter('.type-' + this.value).css('visibility', 'visible');

       $.post('ajax_settings.php', {tid:<?=$therapistID?>, act:'masterSalaryTypeNew', val:this.value}).then(function(res){
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
               var prm = {tid:<?=$therapistID?>, act:'masterSalaryNew'}, papa = self.parent();

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
});
</script>
<style>


</style>