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
<style>
    .manageItems table {
        margin-top: 25px;
        margin-bottom: 10px;
        width: 100%;
        border-bottom: 2px solid rgba(0,0,0,0.1);
        box-sizing: border-box;
        border-radius: 5px;
        overflow: hidden;
    }
    .manageItems table > thead {
        background: #ffffff;
        border-bottom: 2px solid #f5f5f5;
        line-height: 32px;
        font-weight: bold;
    }

    .manageItems table > thead > tr > th {
        text-align: right;
        border: 2px solid #f5f5f5;
        line-height: 1;
        padding: 10px 4px;
        vertical-align: middle;
    }
    .manageItems table > thead > tr > th:nth-child(1) {
        width: 5%;
        text-align: center;
        padding-right: 0;
    }
    .manageItems table > tbody > tr {
        line-height: 30px;
        color: #666;
        cursor: pointer;
        font-size: 14px;
    }
    .manageItems table > tbody > tr:nth-child(odd) {
        background: #F9F9F9;
    }
    .manageItems table > tbody > tr > td {
        border: 1px solid #f5f5f5;
        padding-right: 10px;
        vertical-align: middle;
    }

	.addNew {
		height: 30px;
		padding: 0 20px;
		border-radius: 15px;
		color: white;
		background: #0dabb6;
		font-size: 16px;
		cursor:pointer;
	}
	table#therapists {
		text-align: right;
	}


</style>
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

    try {
//        $active = 0;
//        if ($therapistID)
//            list($active) = udb::single_row("SELECT `active` FROM `therapists` WHERE `therapistID` = " . $therapistID, UDB_NUMERIC);

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
            'upass' => 'string',
            'fictive' => 'int'
        ]);

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
                $data['siteName'] = 'ללא מטפל ' . ($tc + 1);
            }

            $siteData = [
                'workerType' => 'fictive',
                'siteName'      => $data['siteName'],
                'active'        => 1,
                'gender_self'   => $data['gender_self'] ?: 3,
                'gender_client' => 3,
            ];
        } else {
            if (!$data['siteName'])
                throw new LocalException('חייב להיות שם בעברית');

            $siteData = [
//                'active'       => $data['active'][1] ?? 0,
                'workerType'   => 'regular',
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
                $tmp = udb::single_value("SELECT `therapistID` FROM `therapists` WHERE `deleted` = 0 AND `workerType` <> 'fictive' AND `userName` = '" . udb::escape_string($data['userName']) . "' AND `therapistID` <> " . $therapistID);
                if ($tmp)
                    throw new Exception('Username already exists');
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
    catch (LocalException $e){
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
<style>
.editItems form {
    margin-top: 30px;
    background: #ffffff;
    padding: 10px;
    border-bottom: 2px solid rgba(0,0,0,0.2);
    margin-bottom: 10px;
    border-radius: 3px;
    overflow: hidden;
    font-size: 14px;
}

.checkall {
    margin-bottom: 10px;
    line-height: 30px;
    padding: 0 10px;
    border-radius: 15px;
    background: #EEE;
    display: inline-block;
    border: 1px #ccc solid;
    box-sizing: border-box;
	cursor:pointer;
}

.mainSectionWrapper {
    border: 1px solid #f3f3f3;
    clear: both;
    /*overflow: hidden;*/
    /*height: 50px;*/
    margin-top: 10px;
}
.mainSectionWrapper .sectionName {
    background: #d8d8d8;
    line-height: 50px;
    margin-bottom: 20px;
    cursor: pointer;
    text-align: right;
    box-sizing: border-box;
    font-weight: bold;
    font-size: 20px;
    padding-right: 10px;
}
.inputLblWrap {
    display: inline-block;
    vertical-align: middle;
    min-width: 200px;
    margin: 4%;
}
.editItems input#submitTreats {
    position: fixed;
    left: 23px;
    bottom: 38px;
    width: 90px;
    height: 50px;
    line-height: 50px;
    color: #ffffff;
    font-weight: bold;
    background: #2FC2EB;
    font-size: 16px;
    margin-top: 20px;
    text-shadow: -1px 1px 0 rgb(0 0 0 / 10%);
    border-bottom: 2px solid rgba(0,0,0,0.1);
    cursor: pointer;
    box-shadow: none;
    -moz-transition: all 0.25s;
    -webkit-transition: all 0.25s;
    transition: all 0.25s;
    text-align: center;
    display: inline-block;
    vertical-align: top;
}
.inputLblWrap .labelTo {
    display: block;
    vertical-align: middle;
    font-weight: bold;
    margin-bottom: 5px;
}
.editItems input[type='text'], .editItems input[type='password'], .editItems input.submit, .editItems input[type='submit'], .editItems input[type='number'], .editItems textarea {
    line-height: 32px;
    height: 32px;
    background: #f5f5f5;
    border: 0;
    border-radius: 3px;
    box-sizing: border-box;
    outline: none;
    font-size: 12px;
    padding: 0 5px;
    box-shadow: -1px 1px 0 rgb(0 0 0 / 20%);
    margin: 0 auto;
    width: 98%;
    font-family: 'Rubik', sans-serif;
}

.frameContent {
    position: relative;
	text-align:right;
}
.checkIb {
    display: inline-block;
    margin: 1%;
    width: 206px;
}
.checkLabel .checkBoxWrap {
    position: relative;
    width: 20px;
    height: 20px;
    cursor: pointer;
    box-sizing: border-box;
    border: 1px solid #666;
    background: #fff;
    display: inline-block;
    vertical-align: middle;
    border-radius: 4px;
}
.checkLabel .checkBoxWrap input[type="checkbox"] {
    display: none;
}
.editItems input[type='checkbox'] {
    margin: 4px !important;
}
.editItems input[type='checkbox'] {
    -webkit-appearance: checkbox !important;
}
input, select, textarea {
    font-family: 'Rubik', sans-serif;
    border: 1px solid #ccc;
}
.checkLabel .checkBoxWrap label {
    width: 100%;
    height: 100%;
    cursor: pointer;
    position: absolute;
    top: 0;
    left: 0;
}
.checkLabel .checkBoxWrap label::after {
    content: '';
    width: 14px;
    height: 3px;
    position: absolute;
    top: 4px;
    left: 1px;
    border: 3px solid #666;
    border-top: none;
    border-right: none;
    background: transparent;
    opacity: 0;
    -webkit-transform: rotate(
-45deg
);
    transform: rotate(
-45deg
);
}
.checkLabel > label {
    font-size: 16px;
    color: #666;
    display: inline-block;
    vertical-align: middle;
    font-weight: bold;
    cursor: pointer;
}
.checkLabel .checkBoxWrap input:checked + label:after {
        opacity: 1;
    }

    
    .pers {text-align:right;color:#777777}
	
    .pers .per {margin-top:50px}
    .pers input[type=radio] {display:none}
    .pers input[type=radio]+label {font-weight:500;padding-right:40px;box-sizing:border-box;font-size:16px;position:relative;cursor:pointer;margin-bottom:15px;display:block;color:#000}
    .pers input[type=radio]+label::before {content:'';position:absolute;top:50%;right:0;transform:translateY(-50%);width:30px;height:30px;box-sizing:border-box;background:#FFF;border-radius:30px;border:1px solid #13adb8}
    .pers input[type=radio]:checked+label::after {content: '';position: absolute;top: 50%;right: 5px;transform: translateY(-50%);width: 20px;height: 20px;box-sizing: border-box;background: #13adb8;border-radius: 30px;}
    .pers span.amount {display:inline-block;min-width:45px}
    .pers .btn {display:inline-block;width:113px;background:#0dabb6;height:42px;color:#fff;font-size:14px;text-align:center;box-sizing:border-box;border-radius:4px;cursor:pointer}
    .pers>div>div>div {margin-bottom: 10px;display: block}
    .pers>div>div>div input {display:none}
    .pers>div>div>div.edit input {display:inline-block}
    .pers span.title {min-width:100px;display:inline-block}
    .pers .btn>div {display:none;vertical-align:middle;width:113px;height:42px;}
    .pers .btn.edit>div.edit-label {display:table-cell}
    .pers .btn.save>div.save-label {display:table-cell}
    .pers .btn.changed>div.changed-label {display:table-cell}
    .pers input[type=text] {width:50px;height:42px;line-height:40px;border:1px solid #ccc;box-sizing:border-box;border-radius:4px;padding:0 10px;}
    .pers .include-cleantime input {display:none}
    .pers .include-cleantime input+label {padding-right:30px;box-sizing:border-box;display:block;position:relative;cursor:pointer;}
    .pers .include-cleantime input+label::before {content: '';width: 20px;height: 20px;border: 1px solid #ccc;box-sizing: border-box;background: #fff;position: absolute;top: 50%;right: 0;transform: translateY(-50%);}
    .pers .include-cleantime input:checked+label::after {content: '';position: absolute;top: 50%;right: 4px;width: 10px;height: 3px;border-bottom: 2px solid #0dabb6;border-left: 2px solid #0dabb6;transform: translateY(-50%) rotate(-45deg);}
     .pers .btn.changed {background: #e73219;}

    .pers>div>div>div .editable {display:none}
    .pers>div>div>div .visible span {line-height:42px}
    .pers>div>div>div.edit .editable {display:inline-block}
    .pers>div>div>div.edit .editable span {line-height:42px}
    .pers>div>div>div.edit .visible {display:none}

    .pers>div>div>div.edit input.dtstart {width:90px}


     .per-single-percent {text-align:right;color:#777777}
	
    .per-single-percent .per {margin-top:50px}
    .per-single-percent input[type=radio] {display:none}
    .per-single-percent input[type=radio]+label {font-weight:500;padding-right:40px;box-sizing:border-box;font-size:16px;position:relative;cursor:pointer;margin-bottom:15px;display:block;color:#000}
    .per-single-percent input[type=radio]+label::before {content:'';position:absolute;top:50%;right:0;transform:translateY(-50%);width:30px;height:30px;box-sizing:border-box;background:#FFF;border-radius:30px;border:1px solid #13adb8}
    .per-single-percent input[type=radio]:checked+label::after {content: '';position: absolute;top: 50%;right: 5px;transform: translateY(-50%);width: 20px;height: 20px;box-sizing: border-box;background: #13adb8;border-radius: 30px;}
    .per-single-percent span.amount {display:inline-block;min-width:45px}
    .per-single-percent .btn {display:inline-block;width:113px;background:#0dabb6;height:42px;color:#fff;font-size:14px;text-align:center;box-sizing:border-box;border-radius:4px;cursor:pointer}
    .per-single-percent>div>div>div {margin-bottom: 10px;display: block}
    .per-single-percent>div>div>div input {display:none}
    .per-single-percent>div>div>div.edit input {display:inline-block}
    .per-single-percent span.title {min-width:100px;display:inline-block}
    .per-single-percent input[type=text] {width:50px;height:42px;line-height:40px;border:1px solid #ccc;box-sizing:border-box;border-radius:4px;padding:0 10px;}
    .per-single-percent .include-cleantime input {display:none}
    .per-single-percent .include-cleantime input+label {padding-right:30px;box-sizing:border-box;display:block;position:relative;cursor:pointer;}
    .per-single-percent .include-cleantime input+label::before {content: '';width: 20px;height: 20px;border: 1px solid #ccc;box-sizing: border-box;background: #fff;position: absolute;top: 50%;right: 0;transform: translateY(-50%);}
    .per-single-percent .include-cleantime input:checked+label::after {content: '';position: absolute;top: 50%;right: 4px;width: 10px;height: 3px;border-bottom: 2px solid #0dabb6;border-left: 2px solid #0dabb6;transform: translateY(-50%) rotate(-45deg);}


@media (min-width: 992px) {
    .pers, .per-single-percent {display:flex;align-items:start;justify-content:space-between;max-width:750px;margin:0 10px;}

.inputLblWrap {
    margin: 4% 1%;
    width: auto;
}
}

</style>

    <div class="editItems">
	<div class="popGallery">
		<div class="popGalleryCont"></div>
	</div>
	<a class="backbtn" href="?page=<?=$_GET["page"]?>">חזרה</a>
	<div class="siteMainTitle"><?=$siteName?> -  <?=$siteData['siteName']?: "מטפל חדש"?></div>
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
			<?if($isFake == 0) {?><div class="inputLblWrap">
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
<script type="text/javascript" src="/assets/js/jquery.ui.datepicker-he.js"></script>
<script>
$(function() {
   $.datepicker.setDefaults( $.datepicker.regional[ "he" ] );

   $('.dtstart').datepicker({
       minDate: 0,
       dateFormat: 'dd.mm.yy'
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
}
else {

    ?>


	<?php
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
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" data-siteid="<?=$siteID?>" value="הוסף חדש" onclick="openPop(0, $(this).data('siteid'))">
	</div>

	<div class="tblMobile">
    <table id="therapists">
        <thead>
        <tr>
            <th>ID</th>
            <th>שם המטפל</th>
            <th>מגדר</th>
			<th>טלפון</th>
            <th>דוא"ל</th>
			<th width="40">מוצג</th>
			<th></th>

        </tr>
        </thead>
        <tbody id="sortRow">
<?php
//print_r($therapists);
$count_fictive = 0;
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
        <tr id="<?=$site['therapistID']?>" data-siteID="<?=$site['siteID']?>" >
                <td><?=$site['therapistID']?></td>
                <td onclick="openPop(<?=$site['therapistID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=outDb($site['siteName'])?></td>
                <td onclick="openPop(<?=$site['therapistID']?>,'<?=addslashes(htmlspecialchars($site['siteID']))?>')"><?=$therapistType[$site['workerType']]?> - <?=$genderName[$site['gender_self']]?></td>
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

		<form method="post" action="?page=treaters&tID=0&asite=<?=$siteID?>">
			<input type="hidden" name="fictive" value="1" />
			<input type="hidden" name="gender_self" value="3" />
			<input name="site" value="<?=$siteID?>" type="hidden">
			<input type="submit" class="addNew not-empty"  value="הוסף פיקטיבי">
		</form>

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
