<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$commentID = intval($_GET['commentID']);


if ('POST' == $_SERVER['REQUEST_METHOD']) {
    $cp=Array();
    $cp['ifShow'] = intval($_POST['ifShow'])?intval($_POST['ifShow']):0;
    udb::update("Comments", $cp, "commentID =".$commentID);

    if(!intval($_POST['refresh'])){ // save and close iframe ?>
        <script> window.parent.location.reload(); window.parent.closeTab(<?=$frameID?>); </script>
        <?php
    }
}



$que="SELECT Comments.*, sites.TITLE FROM `Comments` INNER JOIN sites USING(siteID) WHERE commentID =".$commentID." ";
$comment= udb::single_row($que);





?>
<div class="editItems">
    <form method="POST" id="myform" enctype="multipart/form-data">
        <input type="hidden" name="refresh" value="0" id="refresh">
        <h1>חוות דעת - <?=$comment['TITLE']?></h1>
        <div class="section">
            <div class="inptLine">
                <div class="label">כותרת: </div>
                <input type="text" value="<?=$comment['com_title']?>" name="com_title" class="inpt">
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">שם הגולש: </div>
                <input type="text" value="<?=$comment['com_name']?>" name="com_name" class="inpt">
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">אימייל: </div>
                <input type="text" value="<?=$comment['com_mail']?>" name="com_mail" class="inpt">
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">טלפון: </div>
                <input type="text" value="<?=$comment['com_phone']?>" name="com_phone" class="inpt">
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">תאריך אירוח: </div>
                <input type="text" value="<?=($comment['dateHost']!="0000-00-00" && $comment['dateHost']!='1970-01-01'?date("d/m/Y", strtotime($comment['dateHost'])): "")?>" name="dateHost" class="inpt datepicker">
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">אופי החופשה: </div>
                <select name="vactype">
                    <option value="0">-</option>
                    <option value="1" <?=$comment['vactype']==1?"selected":""?>>חופשה רומנטית</option>
                    <option value="2" <?=$comment['vactype']==2?"selected":""?>>חופשה משפחתית</option>
                    <option value="3" <?=$comment['vactype']==3?"selected":""?>>חופשה קבוצתית</option>

                </select>
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">דירוג: </div>
                <select name="newRate">
                    <option value="0">-</option>
                    <?php foreach (range(1, 5) as $number) { ?>
                        <option value="<?=$number?>" <?=($number==$comment['newRate']?"selected":"")?>><?=$number?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">מוצג באתר: </div>
                <div class="chkBox">
                    <input type="checkbox" value="1" <?=$comment['ifShow']?"checked":""?> name="ifShow" id="ifShow_<?=$siteID?$siteID:0?>">
                    <label for="ifShow_<?=$siteID?$siteID:0?>"></label>
                </div>
            </div>
        </div>
        <div  style="clear:both;"></div>
        <div class="section txtarea">
            <div class="inptLine">
                <div class="label">דעה: </div>
                <textarea style="height:160px;" name="com_text"><?=$comment['com_text']?></textarea>
            </div>
        </div>

        <div class="section sub">
            <div class="inptLine">
                <input type="submit" value="<?=$commentID?"שמור":"הוסף"?>" class="submit">
            </div>
        </div>
    </form>
</div>