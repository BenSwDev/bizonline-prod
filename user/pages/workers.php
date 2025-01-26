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
<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=workers&v=<?=rand()?>" rel="stylesheet">
<?


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

$workerID = intval($_POST['tID'] ?? $_GET['tID'] ?? 0);

if(isset($_GET['tID']) || 'POST' == $_SERVER['REQUEST_METHOD']) {

if ('POST' == $_SERVER['REQUEST_METHOD']){
    if($_POST['fictive']){
        $tc = udb::single_value("SELECT COUNT(*) FROM `workers` WHERE siteID = " . $siteID . " AND workerType = 'fictive'");
        $_POST = [
            'siteName'      => 'ללא עובד ' . ($tc + 1),
            'active'        => 1,
            'gender_self'   => 3,
            'gender_client' => 3
        ];
    }

$isError = '';
    try {
        $active = 0;
        if ($workerID)
            list($active) = udb::single_row("SELECT `active` FROM `workers` WHERE `workerID` = " . $workerID, UDB_NUMERIC);

        $data = typemap($_POST, [
            'siteName'   => 'string',
            'phone'      => 'string',
            'email'      => 'email',
            'active'      => 'int',
            'gender_self'      => 'int',
            'gender_client'      => 'int',
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
            'upass' => 'string'
        ]);
        if (!$data['siteName'])
            throw new LocalException('חייב להיות שם בעברית');



        $bankData = [
            'bankName'  => $data['bankName'],
            'bankNumber'  => $data['bankNumber'],
            'bankBranch'  => $data['bankBranch'],
            'bankAccount'  => $data['bankAccount'],
            'bankAcoountOwner'  => $data['bankAcoountOwner']
        ];

        $bankData = json_encode($bankData, true);

// main site data
        $siteData = [
            'active'       => $data['active'][1] ?? 0,
            'siteName'     => $data['siteName'],
            'email'        => $data['email'],
            'address'        => $data['address'],
            'charge'        => $data['charge'],
            'gender_self'  => $data['gender_self'],
            'gender_client' => $data['gender_client'],
            'phone'        => $data['phone'],
            'active'        => $data['active'],
            'userName'      => $data['userName'],
            'bankData' => $bankData
        ];

        if ($data['userName']){
            $tmp = udb::single_value("SELECT `workerID` FROM `workers` WHERE `deleted` = 0 AND `userName` = '" . udb::escape_string($data['userName']) . "' AND `workerID` <> " . $workerID);
            if ($tmp)
                throw new Exception('Username already exists');
        }

        if ($data['upass'])
            $siteData['password'] = password_hash($data['upass'], PASSWORD_DEFAULT);

        if($workerID) {
            $gender_self    = udb::single_value("SELECT gender_self FROM `workers` WHERE workers.deleted=0 and  `workers`.`workerID` = " . $workerID);
            $isFake = ($gender_self == 3) ? 1 : 0;
            if($isFake == 1) {
                $siteData = [
                    'siteName' => $data['siteName']
                ];
            }
        }


//save attributes

        if (!$workerID){
            $siteData['siteID'] =  $siteID;
            $workerID = udb::insert('workers', $siteData);

        } else {
            udb::update('workers', $siteData, '`workerID` = ' . $workerID);
        }

//$olda = udb::single_column("select treatmentID from workers_treats where workerID=".$workerID);
       

        

        /*
        $new = array_diff($data['attributes'], $olda);
        if($data['attributes'] && $olda){
        if ($old = array_diff($olda, $data['attributes'])) {
        udb::query("DELETE FROM `workers_treats` WHERE `workerID` = " . $workerID . " AND `treatmentID` IN (" . implode(',', $old) . ")");
        }

        }
        else {
        if(!$data['attributes'] || count($data['attributes']) == 0) {

        }
        }*/

        if ($data['per']){
            $mRow = udb::single_value("SELECT `salary` FROM `workers` WHERE `active` = 1 AND `workerID` = " . $workerID);
            $mRow = json_decode($mRow, true);

            $mRow['activeType'] = $data['per'];
            udb::update('workers', ['salary' => json_encode($mRow, JSON_NUMERIC_CHECK)], '`workerID` = ' . $workerID);
        }

        if($data['gender_self']==3){?>
            <script>
                window.location.href = '?page=workers';
            </script>
            <?
            exit;
        }

    }
    catch (LocalException $e){
        // show error
        $isError = $e->getMessage();
    }
?>
<script>
    <?if($isError) {?>
    alert('<?=$isError?>');
    <?} else
    {?>
    //window.parent.location.reload();
    <?}?>
</script>
<?php

}

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
$isFake = 0;
if ($workerID){
    $siteData    = udb::single_row("SELECT * FROM `workers` WHERE workers.deleted=0 and  `workers`.`workerID` = " . $workerID);
    $isFake = ($siteData['gender_self'] == 3) ? 1 : 0;
}
?>
<style>

</style>

    <div class="editItems">
	<div class="popGallery">
		<div class="popGalleryCont"></div>
	</div>
	<a class="backbtn" href="?page=<?=$_GET["page"]?>">חזרה</a>
	<div class="siteMainTitle"><?=$siteName?> -  <?=$siteData['siteName']?: "עובד חדש"?></div>
	<div class="inputLblWrap ">
	<div class="frameContent">
		<form method="post" enctype="multipart/form-data" >
			 <input type="hidden" id="tID" name="tID" value="<?=$siteData['workerID']?>">
			<div class="mainSectionWrapper">
				<div class="sectionName">כללי</div>
			<div class="inputLblWrap">
				<div class="labelTo">שם העובד</div>
				<input type="text" placeholder="שם העובד" name="siteName" value="<?=js_safe($siteData['siteName'])?>" />
			</div>
			<?if($isFake == 0) {?><div class="inputLblWrap">
				<div class="labelTo">כתובת</div>
				<input type="text" placeholder="כתובת" name="address" value="<?=js_safe($siteData['address'])?>" />
			</div>
            <div class="inputLblWrap">
			<div class="labelTo">אימייל</div>
			<input type="text" placeholder="אימייל" name="email" value="<?=$siteData['email']?>" />
		    </div>

		<div class="inputLblWrap">
			<div class="labelTo">מגדר</div>
			<select name="gender_self">
				<option value="0" <?=!$siteData['gender_self']?"selected='selected'":""?>>- בחירה -</option>
				<option value="1" <?=$siteData['gender_self'] == 1?"selected='selected'":""?>>גבר</option>
				<option value="2" <?=$siteData['gender_self'] == 2?"selected='selected'":""?>>אישה</option>
			</select>
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
		<div class="checkLabel checkIb">
			<div class="checkBoxWrap">
				<input class="checkBoxGr" type="checkbox" name="active" <?=$siteData['active']?"checked":""?> value="1" id="active">
				<label for="active"></label>
			</div>
			<label for="active">פעיל</label>
        </div>
        <div class="inputLblWrap">
            <div class="labelTo">שם משתמש</div>
            <input type="text" placeholder="שם משתמש" name="userName" value="<?=js_safe($siteData['userName'])?>" />
        </div>
        <div class="inputLblWrap">
            <div class="labelTo">סיסמא</div>
            <input type="text" placeholder="<?=($siteData['password'] ? 'סיסמא חדשה' : 'סיסמא')?>" name="upass" value="" />
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
    $defs = udb::single_value("SELECT `salaryDefault` FROM `sites` WHERE `siteID` = " . $siteID);
    $defs = $defs ? json_decode($defs, true) : [];

    $sett = udb::single_value("SELECT `salary` FROM `workers` WHERE `workerID` = " . $workerID);
    $sett = $sett ? json_decode($sett, true) : ['activeType' => 'default'];

    $defDate = $sett ? date('01.m.Y', strtotime('next month')) : date('d.m.Y');

    $nextChange = udb::key_row("SELECT `salaryType`, `salaryDay`, `salaryRate`, `startFrom` FROM `salaryLog` WHERE `startFrom` > CURDATE() AND `targetType` = 'therapist' AND `targetID` = " . $workerID . " ORDER BY `logID`", ['salaryType', 'salaryDay']);

    switch($defs['activeType']){
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
   <?/*
   <div class="per per-percent">
      <input type="radio" name="per" id="perpercent-default" value="default" <?=($sett['activeType'] == 'default' ? 'checked' : '')?>>
      <label for="perpercent-default">ברירת מחדל - <?=$texts['title']?></label>
      <div>
         <div class="reg days">
            <span class="amount"><?=str_replace('N', $defs['all'][$defs['activeType']]['wday'] ?? '', $texts['number'])?></span>
            <span class="title">ימים רגילים</span>
         </div>
         <div class="reg weekend">
            <span class="amount"><?=str_replace('N', $defs['all'][$defs['activeType']]['wend'] ?? '', $texts['number'])?></span>
            <span class="title">סופ"ש וחגים</span>
         </div>
      </div>
   </div>
</div>
<div class="pers">
   <div class="per per-minute">
      <input type="radio" name="per" id="perminute" value="minute" <?=($sett['activeType'] == 'minute' ? 'checked' : '')?> />
      <label for="perminute">לפי דקה</label>
      <div>
         <div class="reg-days">
            <span class="visible">
                <span class="amount">₪<span><?=($sett['all']['minute']['wday'] ?? '-')?></span></span>
                <span class="title">ימים רגילים</span>
            </span>
            <div class="editable">
                <input type="text" name="minute[wday]" id="shekelamount_days" value="<?=($sett['all']['minute']['wday'] ?? '')?>" />
                <span>₪ החל מ-</span>
                <input type="text" name="sminute[wday]" class="dtstart" value="<?=$defDate?>" />
            </div>
<?php
    $class = isset($nextChange['minute']['wday']) ? 'changed' : 'edit';
?>
            <div class="btn <?=$class?>">
               <div class="edit-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="save-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="changed-label">
                   <div>ישתנה ל-₪<?=($nextChange['minute']['wday']['salaryRate'] ?? '')?></div>
                   <div>החל מ-<?=db2date($nextChange['minute']['wday']['startFrom'] ?? '', '.', 2)?></div>
               </div>
            </div>
         </div>
         <div class="reg-weekend">
            <span class="visible">
                <span class="amount">₪<span><?=($sett['all']['minute']['wend'] ?? '-')?></span></span>
                <span class="title">סופ"ש וחגים</span>
            </span>
            <div class="editable">
                <input type="text" name="minute[wend]" id="shekelamount_weekend" value="<?=($sett['all']['minute']['wend'] ?? '')?>" />
                <span>₪ החל מ-</span>
                <input type="text" name="sminute[wend]" class="dtstart" value="<?=$defDate?>" />
            </div>
<?php
    $class = isset($nextChange['minute']['wend']) ? 'changed' : 'edit';
?>
            <div class="btn <?=$class?>">
                <div class="edit-label">
                  <div>שנה תעריף</div>
                </div>
                <div class="save-label">
                  <div>שנה תעריף</div>
                </div>
                <div class="changed-label">
                    <div>ישתנה ל-₪<?=($nextChange['minute']['wend']['salaryRate'] ?? '')?></div>
                    <div>החל מ-<?=db2date($nextChange['minute']['wend']['startFrom'] ?? '', '.', 2)?></div>
                </div>
            </div>
         </div>
      </div>
   </div>

   <div class="per per-percent">
      <input type="radio" name="per" id="perpercent" value="percent" <?=($sett['activeType'] == 'percent' ? 'checked' : '')?> />
      <label for="perpercent">לפי אחוזים</label>
      <div>
         <div class="reg-days">
             <span class="visible">
                <span class="amount"><span><?=($sett['all']['percent']['wday'] ?? '-')?></span>%</span>
                <span class="title">ימים רגילים</span>
             </span>
             <div class="editable">
                 <span>%</span>
                <input type="text" name="percent[wday]" id="percentamount_days" value="<?=($sett['all']['percent']['wday'] ?? '')?>" />
                 <span>₪ החל מ-</span>
                 <input type="text" name="spercent[wday]" class="dtstart" value="<?=$defDate?>" />
             </div>
<?php
    $class = isset($nextChange['percent']['wday']) ? 'changed' : 'edit';
?>
            <div class="btn <?=$class?>">
               <div class="edit-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="save-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="changed-label">
                   <div>ישתנה ל-₪<?=($nextChange['percent']['wday']['salaryRate'] ?? '')?></div>
                   <div>החל מ-<?=db2date($nextChange['percent']['wday']['startFrom'] ?? '', '.', 2)?></div>
               </div>
            </div>
         </div>
         <div class="reg-weekend">
             <span class="visible">
                <span class="amount"><span><?=($sett['all']['percent']['wend'] ?? '-')?></span>%</span>
                <span class="title">סופ"ש וחגים</span>
             </span>
             <div class="editable">
                 <span>%</span>
                <input type="text" name="percent[wend]" id="percentamountt_weekend" value="<?=($sett['all']['percent']['wend'] ?? '')?>" />
                 <span>₪ החל מ-</span>
                 <input type="text" name="spercent[wend]" class="dtstart" value="<?=$defDate?>" />
             </div>
<?php
    $class = isset($nextChange['percent']['wend']) ? 'changed' : 'edit';
?>
            <div class="btn <?=$class?>">
               <div class="edit-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="save-label">
                  <div>שנה תעריף</div>
               </div>
               <div class="changed-label">
                   <div>ישתנה ל-₪<?=($nextChange['percent']['wend']['salaryRate'] ?? '')?></div>
                   <div>החל מ-<?=db2date($nextChange['percent']['wend']['startFrom'] ?? '', '.', 2)?></div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
*/?>
<script type="text/javascript" src="/assets/js/jquery.ui.datepicker-he.js"></script>
<script>
$(function() {
   $.datepicker.setDefaults( $.datepicker.regional[ "he" ] );

   $('.dtstart').datepicker({
       minDate: 0,
       dateFormat: 'dd.mm.yy'
   });

   $('input[name="per"]').on('click', function(){
       $.post('ajax_settings.php', {tid:<?=$workerID?>, act:'masterSalaryType', val:this.value}).then(function(res){
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
               var prm = {tid:<?=$workerID?>, act:'masterSalary'}, papa = self.parent();

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
		</div><?}?>
        <?if($isFake == 0 && 1==2) {?><div class="mainSectionWrapper">
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
}
else {





    $where =" workers.siteID in (".$siteID.") and workers.deleted=0 ";
    $sql = "SELECT `workers`.`siteName`, `workers`.`active`, `workers`.`workerID`, `workers`.`phone`, `workers`.`email`,  `workers`.`workerID`, `workers`.`siteID`,workers.gender_self
FROM `workers`
WHERE " . $where . " GROUP BY `workers`.`workerID` ORDER BY `workers`.`active` DESC, workers.gender_self, `workers`.`siteName` ASC";
    //echo $sql;
    $workers = udb::full_list($sql);
    ?>
    <div class="pagePop"><div class="pagePopCont"></div></div>
    <div class="manageItems" id="manageItems">
    <h1>ניהול עובדים</h1>
<?php
/*    if (!$_CURRENT_USER->single_site){
        $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
	<div class="health_send">
		<div class="site-select">
			בחר מתחם
			<select id="send-site" title="שם מתחם">
				<option value="0">הצג עובדים למתחם</option>
				<?php
			foreach($sname as $id => $name)
				echo '<option value="' , $id , '" ' , ($id == $siteID ? 'selected' : '') , '>' , $name , '</option>';
			?>
			</select>
		</div>
	</div>

<?php
    }*/
?>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" data-siteid="<?=$siteID?>" value="הוסף חדש" onclick="openPop(0, $(this).data('siteid'))" />
	</div>
	<div class="tblMobile">
    <table id="workers">
        <thead>
        <tr>
            <th>ID</th>
            <th>שם העובד</th>
            <th>מגדר</th>
			<th>טלפון</th>
            <th>דוא"ל</th>
			<th width="40">מוצג</th>
			<th></th>

        </tr>
        </thead>
        <tbody id="sortRow">
<?php
$count_fictive = 0;
if (count($workers)){
	$genderName[1] = "גבר";
	$genderName[2] = "אשה";
	$genderName[3] = "<i style='color:#aaa'>פיקטיבי</i>";
    foreach($workers as $site){
		if($site['gender_self'] == 3) $count_fictive ++;
        ?>
        <tr id="<?=$site['workerID']?>" data-siteID="<?=$site['siteID']?>" >
                <td><?=$site['workerID']?></td>
                <td onclick="openPop(<?=$site['workerID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=outDb($site['siteName'])?></td>
                <td onclick="openPop(<?=$site['workerID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=$genderName[$site['gender_self']]?></td>
                <td onclick="openPop(<?=$site['workerID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=outDb($site['phone'])?></td>
                <td onclick="openPop(<?=$site['workerID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=outDb($site['email'])?></td>

                <td><?=($site['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td>
					<?if($site['order_count']){?>
						<b style="color:red"><?=$site['order_count']?> הזמנות</b>
					<?}else{?>
						<div onclick="if(confirm('האם אתה בטוח רוצה למחוק את העובד')){delsite(<?=$site['workerID']?>)}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
	<?/*if($_count_fictive < 4){?>
		<form method="post" action="?page=workers&tID=0&site=<?=(count($_CURRENT_USER->sites()) == 1)? $_CURRENT_USER->sites(true) : ""?>">
			<input name="fictive" value="1" type="hidden">
			<input type="hidden" name="gender_self" value="3">
			<input name="site" value="<?=(count($_CURRENT_USER->sites()) == 1)? $_CURRENT_USER->sites(true) : ""?>" type="hidden">
		</form>
	<?}*/?>
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
	 location.href = "?page=workers&tID=" + pageID + "&asite=" + siteID;
	}
}
function closeTab(){
    $(".pagePopCont").html('');
    $(".pagePop").hide();
}


function delsite(workerID){
    $.post('ajax_delWorker.php',{'workerID':workerID},function(){
        //window.location.reload();
    });

}


$('#send-site').change(function(){
	$("#workers tbody tr").hide();
	if(this.value>0){
		$("#addNewAcc").show();
		$("#addNewAcc").attr('data-siteid',this.value);
		$("#workers tr[data-siteID='"+ this.value +"']").show();
	}else{
		$("#addNewAcc").hide();
	}
});



</script>
    <?
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
</script>
