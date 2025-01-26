<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$contactID = intval($_GET['contactID']);


if ('POST' == $_SERVER['REQUEST_METHOD']) {
    $cp=Array();
    $cp['ifShow'] = intval($_POST['ifShow'])?intval($_POST['ifShow']):0;
    udb::update("Comment", $cp, "commentID =".$contactID);

    if(!intval($_POST['refresh'])){ // save and close iframe ?>
        <script> window.parent.location.reload(); window.parent.closeTab(<?=$frameID?>); </script>
        <?php
    }
}



$que="SELECT * FROM `Comments` WHERE commentID=".$contactID." ";
$contact= udb::single_row($que);

$que="SELECT TITLE FROM sites WHERE siteID=".$contact['siteID']."";
$site=udb::single_row($que);

$vacType=Array(1=>"חופשה רומנטית", 2=>"חופשה משפחתית", 3=>"חופשה קבוצתית");

?>
<div class="editItems">
    <form method="POST" id="myform" enctype="multipart/form-data">
        <input type="hidden" name="refresh" value="0" id="refresh">
        <b>נתוני החדר</b>
        <?php if($contact['dateHost']!="0000-00-00" && $contact['dateHost']!="1970-01-01"){ ?>
        <div class="section">
            <div class="inptLine">
                <div class="label">תאריך אירוח: </div>
                <input type="text" value="<?=date("d.m.y", strtotime($contact['dateHost']))?>" disabled class="inpt">
            </div>
        </div>
        <?php } ?>

        <div class="section">
            <div class="inptLine">
                <div class="label">דירוג: </div>
                <input type="text" value="<?=$contact['newRate']?>" disabled class="inpt">
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">אופי החופשה: </div>
                <input type="text" value="<?=$vacType[$contact['vactype']]?>" disabled class="inpt">
            </div>
        </div>


        <div style="clear:both;"></div>
        <div class="section">
            <div class="inptLine">
                <div class="label">שם לקוח: </div>
                <input type="text" value="<?=$contact['com_name']?>" disabled class="inpt">
            </div>
        </div>
        <?php if($contact['com_email']){ ?>
        <div class="section">
            <div class="inptLine">
                <div class="label">אימייל: </div>
                <input type="text" value="<?=$contact['com_email']?>" disabled class="inpt">
            </div>
        </div>
        <?php } ?>
        <?php if($contact['com_phone']){ ?>
        <div class="section">
            <div class="inptLine">
                <div class="label">טלפון: </div>
                <input type="text" value="<?=$contact['com_phone']?>" disabled class="inpt">
            </div>
        </div>
        <?php } ?>
        <div style="clear:both;"></div>
        <div class="section">
            <div class="inptLine">
                <div class="label">כותרת: </div>
                <input type="text" value="<?=$contact['com_title']?>" disabled class="inpt">
            </div>
        </div>
        <div class="section">
            <div class="inptLine">
                <div class="label">תוכן הודעה: </div>
                <textarea disabled><?=$contact['com_text']?></textarea>
            </div>
        </div>
        <div style="clear:both;"></div>
        <div class="section">
            <div class="inptLine">
                <div class="label">מוצג: </div>
                <div class="chkBox">
                    <input type="checkbox" value="1" <?=$contact['ifShow']?"checked":""?> name="ifShow" id="ifShow_<?=$contactID?$contactID:0?>">
                    <label for="ifShow_<?=$contactID?$contactID:0?>"></label>
                </div>
            </div>
        </div>
        <div class="section sub">
            <div class="inptLine">
                <input type="submit" value="שמור" class="submit">
            </div>
        </div>
    </form>
</div>