<?php 
	include_once "auth.php";
	$siteID = intval($_GET['siteID']);
	$saleID = intval($_GET['id']);

	$que = "SELECT rooms.*
			FROM `rooms` 
			WHERE siteID = ".$siteID."
			ORDER BY showOrder";
	$rooms = udb::full_list($que);

	$que = "SELECT * FROM language WHERE 1";
	$langs = udb::full_list($que);


	if($_POST['action']){
		switch ($_POST['action']){
			case "active":
				udb::update("benefits",["active" => intval($_POST['status'])],"benefitID=".intval($_POST['id']));
			break;

			case "del":
				udb::query("DELETE FROM `benefits` WHERE `benefitID`=".intval($_POST['id']));
				udb::query("DELETE FROM `benefits_units` WHERE `benefitID`=".intval($_POST['id']));
			break;
		}
	
		exit;
	}


	if('POST' == $_SERVER['REQUEST_METHOD']) {
	

		$data = [
			"siteID" => intval($_POST['siteID']),
			"active" => 1,
			"benefitType" => intval($_POST['saleType']),
			"benefitPrice" => intval($_POST['salePrice']),
			"benefitTo" => intval($_POST['saleWho']),
			"present" => intval($_POST['present']),
			"benefitPresentPrice" => intval($_POST['benefitPresentPrice']),
			"benefitTiming" => intval($_POST['saleWhen']),
			"benefitTimingBefore" => intval($_POST['daysBefore']),
			"benefitMinDates" => intval($_POST['orderMinDays']),
			"orderActualFrom" => typemap(implode('-',array_reverse(explode('/',trim($_POST['fromDate'])))),"date"),
			"orderActualTill" => typemap(implode('-',array_reverse(explode('/',trim($_POST['toDate'])))),"date"),
			"noHot" => intval($_POST['hotNotValid']),
			"benefitUnits" => intval($_POST['units'])
		];
		if($_POST['saleID']){
			$data['benefitID'] = intval($_POST['saleID']);
		}

		if($_POST['weekendValid']){
			$postSum = 0;
			foreach($_POST['weekendValid'] as $num){
				$postSum += $num;
			}
			$data["benfitWeek"] = $postSum;
		}

		if(intval($_POST['saleWhen'])==4){
			$data["benefitDateStart"] = typemap(implode('-',array_reverse(explode('/',trim($_POST['fromDateOrder'])))),"date");
			$data["benefitDateEnd"] = typemap(implode('-',array_reverse(explode('/',trim($_POST['toDateOrder'])))),"date");
		}

		$saleID = udb::insert("benefits",$data,true);

		if($_POST['saleID']){
			$saleID = intval($_POST['saleID']);
		}

		udb::query("DELETE FROM `benefits_units` WHERE `benefitID`=".$saleID);
		if(intval($_POST['units'])==2){
			foreach($_POST['roomsID'] as $roomID){
				udb::insert("benefits_units",['benefitID' => $saleID, 'roomID' => intval($roomID)]);
			}
		}
		else
            udb::insert("benefits_units",['benefitID' => $saleID, 'roomID' => 0]);

		udb::query("DELETE FROM `benefits_langs` WHERE `benefitID`=".$saleID);
		if($_POST['present']){
			foreach($_POST['presentText'] as $key => $txt){
				$langs = [];
				$langs['benefitID'] = $saleID;
				$langs['langID'] = $key;
				$langs['textShort'] = trim(filter_var($txt, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW));
				udb::insert("benefits_langs",$langs);

			}

		}
	}
	if($saleID){
		$que = "SELECT * FROM `benefits` WHERE benefitID=".$saleID;
		$saleRow = udb::single_row($que);

		if($saleRow['benefitUnits']==2){
			$saleUnits = udb::key_row("SELECT * FROM benefits_units WHERE `benefitID`=".$saleID,'roomID');
		}
		if($saleRow['present']){
			$presents = udb::key_row("SELECT * FROM benefits_langs WHERE `benefitID`=".$saleID,'langID');
		}
	
	}

?>



<div class="salePopWrap">
	<div class="salePop">
		<div class="salePopTtl"><?=$saleRow? "עריכת" : "הוספת"?>  מבצע
			<div class="close"></div>
		</div>
		<div class="linePop"></div>
		<form class="inWrap" id="saleForm" autocomplete="off">
			<input type="hidden" name="siteID" value="<?=$siteID?>">
			<input type="hidden" name="saleID" value="<?=$saleID?>">
			<div class="inputTxtWrap">
				<div class="sec_title">הגדרות כלליות</div>
				<div class="radioWrap">
					<input type="radio" name="saleWho" value="1" id="saleWho1" <?=$saleRow?($saleRow['benefitTo']==1?"checked":""):"checked"?>>
					<label class="radioLbl" for="saleWho1"></label>
					<label class="radioTxtLbl" for="saleWho1">מבצע לכולם</label>
				</div>
				<div class="radioWrap">
					<input type="radio" name="saleWho" value="2" id="saleWho2" <?=$saleRow['benefitTo']==2?"checked":""?> >
					<label class="radioLbl" for="saleWho2"></label>
					<label class="radioTxtLbl" for="saleWho2">רק לחברי מועדון</label>
				</div>
				<div style="display:inline-block;border-right:1px solid #569ce3;padding-right:10px">
					<label>הזמנה של לפחות</label>
					<input type="number" value="<?=$saleRow['benefitMinDates']? $saleRow['benefitMinDates'] : "1"?>" name="orderMinDays" style="width:50px"><span class="symbol">לילות</span>
				</div>
			</div>
			<div class="linePop"></div>
			<div class="radioFullWrap">				
				<div class="sec_title">קביעת הנחה</div>
				<div class="radioWrap">
					<input type="radio" name="saleType" value="1" id="saleType1" <?=$saleRow?($saleRow['benefitType']==1?"checked":""):"checked"?>>
					<label class="radioLbl" for="saleType1"></label>
					<label class="radioTxtLbl" for="saleType1">הנחה בשקלים</label>
				</div>
				<div class="radioWrap">
					<input type="radio" name="saleType" value="2" id="saleType2" <?=$saleRow['benefitType']==2?"checked":""?>>
					<label class="radioLbl" for="saleType2"></label>
					<label class="radioTxtLbl" for="saleType2">הנחה באחוזים</label>
				</div>
<?php /*
				<div class="radioWrap">
					<input type="radio" name="saleType" value="3" id="saleType3" <?=$saleRow['benefitType']==3?"checked":""?>>
					<label class="radioLbl" for="saleType3"></label>
					<label class="radioTxtLbl" for="saleType3">הנחת מתנה</label>
				</div>
*/?>

				<div class="radioWrap">
					<input type="radio" name="saleType" value="4" id="saleType4" <?=$saleRow['benefitType']==4?"checked":""?>>
					<label class="radioLbl" for="saleType4"></label>
					<label class="radioTxtLbl" for="saleType4">על לילה אחרון</label>
				</div>
				<div class="checkBoxWrap">
					<input type="checkbox" name="present" value="1" id="present" <?=$saleRow['present']==1?"checked":""?>>
					<label class="chckBoxLbl" for="present"></label>
					<label class="chckBoxTxt" for="present">מתנה</label>
				</div>
			</div>
			<div class="linePop fade"></div>
			<div class="inputTxtWrap heightSale">
				<label>גובה הנחה</label>
				<div class="inputOrSelect nis">
					<input type="number" min="0" max="100" value="<?=$saleRow['benefitType']==1?$saleRow['benefitPrice']:""?>" name="salePrice"><span class="symbol">₪</span>
				</div>
				<div class="inputOrSelect pre">
					<select name="salePrice">
						<?php for($i=10;$i<=70;$i+=10) { ?>
						<option value="<?=$i?>" <?=$saleRow['benefitType']==2 && $saleRow['benefitPrice']==$i?"selected":""?> ><?=$i?>%</option>
						<?php } ?>
					</select>
				</div>
				<div class="inputOrSelect last">
					<select name="salePrice">
						<?php for($i=10;$i<=90;$i+=10) { ?>
						<option value="<?=$i?>" <?=$saleRow['benefitType']==4 && $saleRow['benefitPrice']==$i?"selected":""?>><?=$i?>%</option>
						<?php } ?>
						<option value="100" <?=$saleRow['benefitType']==4 && $saleRow['benefitPrice']==100?"selected":""?>>לילה חינם</option>
					</select>
				</div>
			</div>
			<div class="inputPresent">
				<?php foreach($langs as $lang){ ?>
				<div class="inputTxtWrap" style="width:48%;display:inline-block">
					<label>תיאור המתנה <?=$lang['LangName']?></label>
					<input type="text" value="<?=$presents?$presents[$lang['LangID']]['textShort']:""?>" name="presentText[<?=$lang['LangID']?>]">
				</div>
				<?php } ?>
				<div class="inputTxtWrap">
					<label>שווי מתנה</label>
					<input type="number" value="<?=$saleRow['benefitPresentPrice']?>" name="benefitPresentPrice"><span class="symbol">₪</span>
				</div>
			</div>

			<div class="linePop"></div>
			<div class="radioFullWrap">
				<div class="sec_title">המבצע תקף למזמינים אשר ביצעו הזמנה</div>
				<div class="radioWrap">
					<input type="radio" name="saleWhen" value="2" id="saleWhen2" <?=$saleRow['benefitTiming']==2?"checked":""?>>
					<label class="radioLbl" for="saleWhen2"></label>
					<label class="radioTxtLbl" for="saleWhen2">ברגע האחרון</label>
				</div>
				<div class="radioWrap">
					<input type="radio" name="saleWhen" value="3" id="saleWhen3" <?=$saleRow['benefitTiming']==3?"checked":""?>>
					<label class="radioLbl" for="saleWhen3"></label>
					<label class="radioTxtLbl" for="saleWhen3">בהזמנה מראש</label>
				</div>
				<div class="radioWrap">
					<input type="radio" name="saleWhen" value="4" id="saleWhen4" <?=$saleRow['benefitTiming']==4?"checked":""?>>
					<label class="radioLbl" for="saleWhen4"></label>
					<label class="radioTxtLbl" for="saleWhen4">בין תאריכים</label>
				</div>
				<div class="radioWrap">
					<input type="radio" name="saleWhen" value="1" id="saleWhen1" <?=$saleRow?($saleRow['benefitTiming']==1?"checked":""):"checked"?>>
					<label class="radioLbl" for="saleWhen1"></label>
					<label class="radioTxtLbl" for="saleWhen1">בכל תאריך</label>
				</div>
			</div>
			<div class="inputTxtWrap dates">
				<label>בין תאריכים</label>
				<input type="text" value="<?=implode('/',array_reverse(explode('-',trim($saleRow['benefitDateStart']))))?>" class="datePick rgt top" name="fromDateOrder">
				<input type="text" value="<?=implode('/',array_reverse(explode('-',trim($saleRow['benefitDateEnd']))))?>" class="datePick lft top" name="toDateOrder">
			</div>
			<div class="linePop fade"></div>
			<div class="inputTxtWrap daysBefore">
				<label><span>עד</span><span>יותר מ</span></label>
				<input type="number" value="<?=$saleRow['benefitTimingBefore']?>" name="daysBefore">
				<label>ימים לפני תאריך צ'ק אין</label>
			</div>
			<div class="linePop fade"></div>
			<div class="radioFullWrap">
				<div class="checkBoxWrap">
					<input type="checkbox" name="weekendValid[]" value="1" id="weekendValid1" <?=$saleRow?($saleRow["benfitWeek"] & 1 ?"checked":""):"checked"?>>
					<label class="chckBoxLbl" for="weekendValid1"></label>
					<label class="chckBoxTxt" for="weekendValid1">תקף לאמצ"ש</label>
				</div>
				<div class="checkBoxWrap">
					<input type="checkbox" name="weekendValid[]" value="2" id="weekendValid2" <?=$saleRow?($saleRow["benfitWeek"] & 2 ?"checked":""):"checked"?>>
					<label class="chckBoxLbl" for="weekendValid2"></label>
					<label class="chckBoxTxt" for="weekendValid2">תקף לסופ"ש</label>
				</div>
				<div class="checkBoxNote">חובה לבחור אחד ניתן לסמן גם וגם</div>
			</div>
			<div class="linePop"></div>
			<div class="inputTxtWrap toDate">
				<div class="sec_title">תקף להזמנות שתאריך ההגעה שלהן בין תאריכים</div>
				<input type="text" value="<?=implode('/',array_reverse(explode('-',trim($saleRow['orderActualFrom']))))?>" class="datePick rgt bot" name="fromDate">
				<input type="text" value="<?=implode('/',array_reverse(explode('-',trim($saleRow['orderActualTill']))))?>" class="datePick lft bot" name="toDate">
				<div class="checkBoxWrap">
					<input type="checkbox" name="hotNotValid" value="1" id="hotValid" <?=$saleRow['noHot']==1?"checked":""?>>
					<label class="chckBoxLbl" for="hotValid"></label>
					<label class="chckBoxTxt" for="hotValid" style="width:auto">לא תקף בתקופות חמות</label>
				</div>
			</div>

			<div class="linePop"></div>
			<div class="radioFullWrap">				
				<div class="sec_title">על אילו יחידות תקף</div>
				<div class="radioWrap">
					<input type="radio" name="units" value="1" id="units1" <?=$saleRow?($saleRow['benefitUnits']==1?"checked":""):"checked"?>>
					<label class="radioLbl" for="units1"></label>
					<label class="radioTxtLbl" for="units1">על כל היחידות</label>
				</div>
				<div class="radioWrap">
					<input type="radio" name="units" value="2" id="units2" <?=$saleRow['benefitUnits']==2?"checked":""?>>
					<label class="radioLbl" for="units2"></label>
					<label class="radioTxtLbl" for="units2">בחירת יחידות</label>
				</div>
			</div>
			<div class="linePop fade"></div>
			<div class="radioFullWrap roomsChecks">
				<?php foreach($rooms as $room) { ?>
				<div class="checkBoxWrap">
					<input type="checkbox" name="roomsID[]" value="<?=$room['roomID']?>" id="roomsID<?=$room['roomID']?>" <?=$saleUnits[$room['roomID']]?"checked":""?>>
					<label class="chckBoxLbl" for="roomsID<?=$room['roomID']?>"></label>
					<label class="chckBoxTxt" for="roomsID<?=$room['roomID']?>"><?=$room['roomName']?></label>
				</div>
				<?php } ?>
			</div>
			<div class="linePop"></div>
			<div class="approveBtn">אישור</div>
		</form>
	</div>
</div>