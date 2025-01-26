<?php
include_once "../bin/system.php";
include_once "../bin/top_user.php";

$menu = include "menu_user.php";
$position=0;
if($_SESSION['permission']==10){

    if('POST' == $_SERVER['REQUEST_METHOD']) {
        $cp=Array();
        $que="SELECT siteID, TITLE FROM sites WHERE siteID=".$site['siteID']." AND password='".sha1(sha1(sha1($_POST['oldPass'])))."'";
        $checkOldPass=udb::single_row($que);
        if($checkOldPass){
            if(sha1(sha1(sha1($_POST['newPass'])))==sha1(sha1(sha1($_POST['newPass2'])))){
                $cp['password']=sha1(sha1(sha1($_POST['newPass'])));
                    udb::update("sites", $cp, "siteID = ".$site['siteID']."");
                    ?>
                    <script>window.formAlert("green", "עודכן בהצלחה", ""); </script>
                    <?php

            } else {
                ?>
                <script>window.formAlert("red", "אימות סיסמה נכשל", ""); </script>
                <?php
            }
        } else {
            ?>
            <script>window.formAlert("red", "סיסמה ישנה לא נכונה", ""); </script>
            <?php
        }

    }

    ?>
    <div class="userTabs">
        <?php foreach($menu as $men){ ?>
            <div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>'"><p><?=$men['name']?></p></div>
        <?php } ?>
    </div>
    <div class="editItems user">
        <form method="POST" enctype="multipart/form-data" id="myForm">
            <input type="password" style="opacity:0;width:0;height:0;visibility:hidden;">
            <input type="text" style="opacity:0;width:0;height:0;visibility:hidden;">
            <b>שינוי סיסמה</b>
            <div class="section">
                <div class="inptLine">
                    <input type="password" name="oldPass" class="inpt" placeholder="סיסמה ישנה" autocomplete="off" style="-webkit-user-select: text !important;">
                </div>
            </div>
            <div class="section">
                <div class="inptLine">
                    <input type="password" name="newPass" class="inpt" autocomplete="off" placeholder="סיסמה חדשה" style="-webkit-user-select: text !important;">
                </div>
            </div>
            <div class="section">
                <div class="inptLine">
                    <input type="password" name="newPass2" class="inpt" autocomplete="off" placeholder="הכנס סיסמה חדשה שוב" style="-webkit-user-select: text !important;">
                </div>
            </div>
            <div class="section sub">
                <div class="inptLine">
                    <input type="submit" value="שנה סיסמה" class="submit">
                </div>
            </div>
        </form>
    </div>
<?php } ?>
<?php
include_once "../bin/footer.php";
?>