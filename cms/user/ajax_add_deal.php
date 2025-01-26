<?php
include_once "../bin/system.php";

$i=intval($_POST['target']);
$siteID=intval($_POST['siteID']);
$dealsTypes=Array(1=>"בין תאריכים", 2=>"קבוע");
$daysInWeek=Array(1=>"כל ימות השבוע", 2=>'אמצ"ש', 3=>'סופ"ש');
$periodInYear=Array(1=>"כל תקופה", 2=>"תקופה רגילה בלבד");
$limitations=Array(1=>"יום לפני הזמנה", 2=>"עד יומיים לפני הזמנה", 3=>"עד 3 ימים לפני הזמנה", 4=>"ללא הגבלה");
$dealTo=Array(1=>"לילה אחד ומעלה", 2=>"לילה שני", 3=>"לילה שלישי", 4=>"יום כיף");

$que="SELECT MainPages.MainPageID, MainPages.MainPageTitle, MainPages.ifShow, sitesExtrasNew.* 
	  FROM MainPages 
	  INNER JOIN sitesExtrasNew ON (MainPages.MainPageID=sitesExtrasNew.extraID AND siteID=".$siteID.") 
	  WHERE MainPageType=20 AND MainPages.ifShow=1";
$extras=udb::full_list($que);


$que="SELECT sitesRooms.* FROM `sitesRooms` WHERE sitesRooms.siteID = ".$siteID." ORDER BY showOrder";
$rooms= udb::key_row($que, "roomID");



?>
<form class="goDeal open" id="form<?=$i?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="siteID" value="<?=$siteID?>">
    <input type="hidden" name="sysID" value="0">
    <input type="hidden" name="dealID" value="0">
    <div class="allSelect">
        <div class="selectBoxNoBg">
            <p class="text">הגבלת הדיל לתאריך ההזמנה</p>
        </div>
        <div class="selectBox" >
            <select name="limitations" class="oneSmall" onchange="changeSave(<?=$i?>)">
                <?php foreach($limitations as $dType=>$dText){ ?>
                    <option value="<?=$dType?>" <?=($dType==$deal['limitations']?"selected":"")?>><?=$dText?></option>
                <?php } ?>
            </select>
        </div>

        <div class="selectBox">
            <select name="discount" class="oneBig discountSelect">
                <?php foreach(range(20,100,10) as $n){  echo '<option value="',$n,'" ',( $deal['discount'] == $n ? 'selected' : ''),'>',$n,'% הנחה</option>'; }	?>
                <option value="0" <?=$deal['discount']==0 && $deal['specID']?"selected":""?> >מתנה</option>
            </select>
        </div>
        <div class="selectBox">
            <select name="daysInWeek" class="oneSmall" onchange="changeSave(<?=$i?>)">
                <?php foreach($daysInWeek as $dType=>$dText){ ?>
                    <option value="<?=$dType?>" <?=($dType==$deal['daysInWeek']?"selected":"")?>><?=$dText?></option>
                <?php } ?>
            </select>
        </div>
        <div class="openBox">
            <div class="selectBox">
                <select name="dealTo" class="withSpn" onchange="changeSave(<?=$i?>)">
                    <?php foreach($dealTo as $dType=>$dText){ ?>
                        <option value="<?=$dType?>" <?=$dType==$deal['dealTo']?"selected":""?>><?=$dText?></option>
                    <?php } ?>
                </select>
                <span>הנחה תקפה על</span>
            </div>
            <div class="selectBox">
                <select name="periodInYear" class="withSpn" onchange="changeSave(<?=$i?>)">
                    <?php foreach($periodInYear as $dType=>$dText){ ?>
                        <option value="<?=$dType?>" <?=$dType==$deal['periodInYear']?"selected":""?>><?=$dText?></option>
                    <?php } ?>
                </select>
                <span>הנחה תקפה ב</span>
            </div>
            <div class="selectBox">
                <select name="roomID" class="withSpn" onchange="changeSave(<?=$i?>)">
                    <option value="0">על כל המתחם</option>
                    <?php foreach($rooms as $room){ ?>
                        <option value="<?=$room['roomID']?>"><?=$room['roomName']?></option>
                    <?php } ?>
                </select>
                <span>על איזה חדר</span>
            </div>
            <div class="selectBox extrasBox" style="display:<?=$deal['discount']==0 && $deal['specID']?"bloc":"none"?>">
                <select name="extras" class="withSpn" onchange="changeSave(<?=$i?>)">
                    <option value="0">תוספת מתנה</option>
                    <?php foreach($extras as $ex){ ?>
                        <option value="<?=$ex['MainPageID']?>"><?=$ex['MainPageTitle']?></option>
                    <?php } ?>
                </select>
                <span>תוספות</span>
            </div>
            <div class="selectBox date">
                <input type="text" name="dateTo" onchange="changeSave(<?=$i?>)" value="" class="withDate datepicker" readonly>
                <input type="text" name="dateFrom" onchange="changeSave(<?=$i?>)" value="" class="withDate datepicker" readonly>
                <span>בתוקף לתאריכים</span>
                <span class="fromDate">מתאריך</span>
                <span  class="toDate">עד תאריך</span>
                <div class="chkBox">
                    <input type="checkbox" onchange="changeSave(<?=$i?>)" id="dealType<?=$i?>" name="dealType" value="1" <?=$deal['dealType']==1?"checked":""?> >
                    <label for="dealType<?=$i?>"></label>
                </div>
            </div>
        </div>
        <div class="selectBoxClose">
            <input type="text" id="" name="" value="" class="oneBigClose dte" readonly>
        </div>
        <div class="selectBoxClose" onchange="changeSave(<?=$i?>)">
            <input type="text" id="" name="" value="" class="oneBigClose dte" readonly>
        </div>
    </div>
    <div class="lft" id="lft_form<?=$i?>">
        <div class="plusBtn" onclick="openMoreDeal('form<?=$i?>')"></div>
        <input type="hidden" class="activehidden" name="active" value="0">
        <input type="hidden" class="exclusivehidden" name="exclusive" value="0">
        <div data-bttn="active" class="ifActive<?=$deal['active']==1?"":" no"?>" onclick="saveChanges('form<?=$i?>', this)">לא פעיל</div>
        <div data-bttn="exclusive" class="where<?=$deal['exclusive']==1?"":" no"?>" onclick="saveChanges('form<?=$i?>', this)">ראשי</div>
        <div class="saveBtn" onclick="saveChanges('form<?=$i?>', this)">שמור</div>
        <div class="desc">על מנת להוסיף דיל זה יש ללחוץ על שמור</div>
    </div>
</form>
