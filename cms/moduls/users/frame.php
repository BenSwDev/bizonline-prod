<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";

const BASE_LANG_ID = 1;


$pageID=intval($_GET['ID']);


if ('POST' == $_SERVER['REQUEST_METHOD']){

    try 
	{
		$data = typemap($_POST, [
			'name'		 => 'string',
			'lname'      => 'string',
			'email'      => 'email',
			'phone'      => 'string',
			'birthday'      => 'string',
			'anniversary'      => 'string',
			'birthdaySpouse'   => 'string',
			'regsex'		   => 'int',
			'!mailApprove'     => 'int',
			'uid'     => 'int'
		]);
	
		$hData = [
            'name' => $data['name'],
            'lname' => $data['lname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'birthday' => ($data['birthday'] && $data['birthday']!='30/11/-0001'?DateTime::createFromFormat("d/m/Y", $data['birthday'])->format("Y-m-d"):null),
            'anniversary' => ($data['anniversary'] && $data['anniversary']!='30/11/-0001'?DateTime::createFromFormat("d/m/Y", $data['anniversary'])->format("Y-m-d"):null),
            'birthdaySpouse' => ($data['birthdaySpouse'] && $data['birthdaySpouse']!='30/11/-0001'?DateTime::createFromFormat("d/m/Y", $data['birthdaySpouse'])->format("Y-m-d"):null),
            'regsex' => $data['regsex'],
            'mailApprove' => $data['mailApprove']
          
        ];

        if (!$pageID){      // opening new
            $pageID = udb::insertNull('usersConnection', $hData);
        } else {
            udb::updateNull('usersConnection', $hData, '`ID` = ' . $pageID);
		}


			
		udb::update('sites', ['uid' => 0], '`uid` = ' . $pageID);
		udb::update('sites', ['uid' => $pageID], '`siteID` = ' . $data['uid']);
	
	
	}


    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

if ($pageID){
    $user = udb::single_row("SELECT * FROM `usersConnection` WHERE `ID`=".$pageID);

}
$que = "SELECT siteName,uid,siteID FROM `sites` WHERE uid IN (0,".$pageID.") ORDER BY `siteName`";
$sites = udb::full_list($que);
?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$user['email']?></h1>

	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<?php if($user['picture'] || $user['picUpload']){ ?>
				<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
					
					<div class="section">
						<div class="inptLine">
							<img src="<?=($user['picUpload']?"/".$user['picUpload']:($user['picture']?$user['picture']:""))?>" style="width:100%">
						</div>
					</div>
				</div>
			<?php } ?>
			<div class="inputLblWrap">
				<div class="labelTo">שם</div>
				<input type="text" placeholder="שם" name="name" value="<?=js_safe($user['name'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">שם משפחה</div>
				<input type="text" placeholder="שם משפחה" name="lname" value="<?=js_safe($user['lName'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">איימייל</div>
				<input type="text" placeholder="איימייל" name="email" value="<?=js_safe($user['email'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">טלפון</div>
				<input type="text" placeholder="טלפון" name="phone" value="<?=js_safe($user['phone'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">יום הולדת</div>
				<input type="text" placeholder="יום הולדת" name="birthday" class="datePick" value="<?=($user['birthday']?date("d/m/Y", strtotime($user['birthday'])):"")?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">יום הולדת בן/בת הזוג</div>
				<input type="text" placeholder="יום הולדת בן/בת הזוג" name="birthdaySpouse" class="datePick" value="<?=($user['birthdaySpouse']?date("d/m/Y", strtotime($user['birthdaySpouse'])):"")?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">יום נישואין</div>
				<input type="text" placeholder="יום נישואין" name="anniversary" class="datePick" value="<?=($user['anniversary']?date("d/m/Y", strtotime($user['anniversary'])):"")?>" />
			</div>
			<div class="inputLblWrap">
				<div class="inputLblWrap">
					<div class="labelTo">מין</div>
					<select name="regsex">
						<option value="-">-</option>
						<option value="1" <?=$user['regsex']==1?"selected":""?>>זכר</option>
						<option value="2" <?=$user['regsex']==2?"selected":""?>>נקבה</option>
					</select>
				</div>
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">אישור לקבלת דיוור</div>
				<label class="switch">
				  <input type="checkbox" readonly name="mailApprove" value="1" <?=($user['mailApprove'] ? 'checked="checked"' : '')?>  />
				  <span class="slider round"></span>
				</label>
			</div>

			<div class="selectWrap inputLblWrap">
				<div class="selectLbl">בעל צימר</div>
				<select name="uid">
					<option value="0">-</option>

					<?php foreach($sites as $site) { ?>
					<option value="<?=$site['siteID']?>" <?=$site['uid']==$user['ID']?"selected":""?> ><?=$site['siteName']?></option>
					<?php } ?>
				</select>
			</div>

		</div>
		<div style="clear:both;"></div>

		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$user['ID']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>


<script type="text/javascript">

	$(".datePick").datepicker({
	format:"dd/mm/yyyy",
	changeMonth:true,
	changeYear:true
});

</script>