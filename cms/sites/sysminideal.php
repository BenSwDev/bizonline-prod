<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";
require_once "../classes/class.PriceCache.php";




$siteID=intval($_GET['siteID']);
$dealID=intval($_GET['dealID']);
$frameID=intval($_GET['frame']);

$position=4;

if('POST' == $_SERVER['REQUEST_METHOD']) {

    $cp=Array();

    if($_POST['dateFrom']){
        $date=explode("/", $_POST['dateFrom']);
        $date=$date[2]."-".$date[1]."-".$date[0];
        $cp["dateFrom"] = $date;
    }
    if($_POST['dateTo']){
        $date=explode("/", $_POST['dateTo']);
        $date=$date[2]."-".$date[1]."-".$date[0];
        $cp["dateTo"] = $date;
    }
    $cp['dealType'] = intval($_POST['dealType'])?intval($_POST['dealType']):0;
    $cp['periodInYear'] = intval($_POST['periodInYear'])?intval($_POST['periodInYear']):0;

    $cp['limitations'] = intval($_POST['limitations'])?intval($_POST['limitations']):0;
    $cp['active'] = intval($_POST['active'])?intval($_POST['active']):0;
    $cp['dealTo'] = intval($_POST['dealTo'])?intval($_POST['dealTo']):0;
    $cp['dealTitle'] = inDb($_POST['dealTitle']);
    $cp['discount'] = intval($_POST['discount']);
    $cp['daysInWeek'] = intval($_POST['daysInWeek'])?intval($_POST['daysInWeek']):0;
    if($dealID){
        udb::update("sitesSpecialsSys", $cp, "specID =".$dealID);


        $cp2=Array();
        $cp2['dealTo']=$cp['dealTo'];
        $cp2['limitations']=$cp['limitations'];
        $cp2['dealType']=$cp['dealType'];
        $cp2['dateTo']=$cp['dateTo'];
        $cp2['dateFrom']=$cp['dateFrom'];

        udb::update("sitesSpecials", $cp2, "baseSys =".$dealID);
    } else {


        $dealID = udb::insert("sitesSpecialsSys", $cp);
    }





    if(!intval($_POST['refresh'])){ // save and close iframe ?>
        <script> window.parent.location.reload(); window.parent.closeTab(<?=$frameID?>); </script>
        <?php
    } else { // save and get alert success ?>
        <script>window.parent.formAlert("green", "עודכן בהצלחה", ""); </script>
    <?php }

}

$dealsTypes=Array(1=>"בין תאריכים", 2=>"קבוע");
$daysInWeek=Array(1=>"כל ימות השבוע", 2=>'אמצ"ש', 3=>'סופ"ש');
$periodInYear=Array(1=>"כל תקופה", 2=>"תקופה רגילה בלבד");
$limitations=Array(1=>"יום לפני הזמנה", 2=>"עד יומיים לפני הזמנה", 3=>"עד 3 ימים לפני הזמנה", 4=>"ללא הגבלה");
$dealTo=Array(1=>"לילה אחד ומעלה", 2=>"לילה שני", 3=>"לילה שלישי", 4=>"יום כיף");

$que="SELECT * FROM `sitesSpecialsSys` WHERE specID=".$dealID."";
$deal= udb::single_row($que);


?>
<div class="editItems">
    <h1><?=$site['TITLE']?></h1>
    <form method="POST" id="myform" enctype="multipart/form-data">
        <input type="hidden" name="refresh" value="0" id="refresh">
        <b>מבצע</b>
        <div class="section">
            <div class="inptLine">
                <div class="label">כותרת</div>
                <input type="text" value="<?=outDb($deal['dealTitle'])?>" name="dealTitle" class="inpt">
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">סוג דיל: </div>
                <select name="dealType" id="dealType">
                    <?php foreach($dealsTypes as $dType=>$dText){ ?>
                        <option value="<?=$dType?>" <?=$dType==$deal['dealType']?"selected":""?>><?=$dText?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">פעיל: </div>
                <div class="chkBox">
                    <input type="checkbox" value="1" <?=($deal && $deal['active']==0?"":"checked")?> name="active" id="ifShow_<?=$dealID?$dealID:0?>">
                    <label for="ifShow_<?=$dealID?$dealID:0?>"></label>
                </div>
            </div>
        </div>
        <div class="section" id="datesShow" <?=($deal['dealType']==2?"style='display:none'":"")?>>
            <div class="inptLine">
                <div class="label">מתאריך</div>
                <input type="text" value="<?=($deal['dateFrom'] && $deal['dateFrom']!="0000/00/00"?date("d/m/Y", strtotime($deal['dateFrom'])):"")?>" name="dateFrom" class="inpt datepicker">
                <div class="label">עד</div>
                <input type="text" value="<?=($deal['dateTo'] && $deal['dateTo']!="0000/00/00"?date("d/m/Y", strtotime($deal['dateTo'])):"")?>" name="dateTo" class="inpt datepicker">
            </div>
        </div>
        <div  style="clear:both;"></div>

        <div class="section">
            <div class="inptLine">
                <div class="label">ימי שבוע: </div>
                <select name="daysInWeek">
                    <?php foreach($daysInWeek as $dType=>$dText){ ?>
                        <option value="<?=$dType?>" <?=$dType==$deal['daysInWeek']?"selected":""?>><?=$dText?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
     
        <div class="section">
            <div class="inptLine">
                <div class="label">תקופות שנה </div>
                <select name="periodInYear">
                    <?php foreach($periodInYear as $dType=>$dText){ ?>
                        <option value="<?=$dType?>" <?=$dType==$deal['periodInYear']?"selected":""?>><?=$dText?></option>
                    <?php } ?>
                </select>
            </div>
        </div>


        <div class="section">
            <div class="inptLine">
                <div class="label">הנחה: </div>
                <select name="discount" class="discount">
                    <option value="0">מתנה</option>
                    <?php
                    foreach(range(20,100,10) as $n){  echo '<option value="',$n,'" ',($deal['discount'] == $n ? 'selected' : ''),'>',$n,'%</option>'; }	?>
                </select>
            </div>
        </div>

        <div class="section">
            <div class="inptLine">
                <div class="label">הגבלות </div>
                <select name="limitations">
                    <?php foreach($limitations as $dType=>$dText){ ?>
                        <option value="<?=$dType?>" <?=$dType==$deal['limitations']?"selected":""?>><?=$dText?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">דיל תקף על הזמנות</div>
                <select name="dealTo">
                    <?php foreach($dealTo as $dType=>$dText){ ?>
                        <option value="<?=$dType?>" <?=$dType==$deal['dealTo']?"selected":""?>><?=$dText?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div  style="clear:both;"></div>
        <div class="section sub">
            <div class="inptLine">
                <?php if($dealID){ ?>
                    <input type="buton" value="עדכן" class="submit" onclick="document.getElementById('refresh').value=1;document.getElementById('myform').submit(); ">
                <?php } ?>
                <input type="submit" value="<?=$dealID?"שמור":"הוסף"?>" class="submit">
            </div>
        </div>
    </form>
</div>
</section>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div>
    </div>
</div>
<script>
    $(function() {

        $("#dealType").change(function(){
            var selVal = $("#dealType").val();
            if(selVal==2){
                $("#datesShow").hide();
            } else {
                $("#datesShow").show();
            }
        });

        $("input.roomschck").change(function(){
            var thisVal = $(this).val();
            if(thisVal=="all"){
                $("input.roomschck").prop("checked",false);
                $(this).prop("checked",true);
            } else {
                $(".allRooms").prop("checked",false);
            }
        });

        $( ".datepicker" ).datepicker({
            minDate: 0
        });
    });
</script>
</body>
</html>