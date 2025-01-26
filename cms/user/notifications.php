<?php
include_once "../bin/system.php";
include_once "../bin/top_user.php";

$menu = include "menu_user.php";
$position=0;
$contactType=Array(1=>"שאלה לבעל המתחם", 2=>"בקשה להזמנת נופש");
$lastDate = date("Y-m-d H:i:s", strtotime("-14 days"));

if ('POST' == $_SERVER['REQUEST_METHOD']) {
/*
        $cp=Array();
        $cp['contactStatus']=intval($_POST['contactStatus'])?intval($_POST['contactStatus']):0;
        $cp['contactID']=intval($_POST['contactID']);
        udb::update("sitesContacts", $cp, "contactID=".intval($_POST['contactID']));
*/
?>
<script type="text/javascript">
    window.location.href="/cms/user/notifications.php";
</script>
<?php
}

?>
    <div class="userTabs">
        <?php foreach($menu as $men){ ?>
            <div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>'"><p><?=$men['name']?></p></div>
        <?php } ?>
    </div>

<?php
    $cID=intval($_GET['cID']);
    if($cID){

        $que="SELECT * FROM sitesContacts 
			  WHERE contactSiteID=".$site['siteID']." AND contactID=".$cID." ";
        $contact = udb::single_row($que);


        if($contact) {
            $cp=Array();
            $cp['show']=1;
            udb::update("sitesContacts", $cp, "contactID=".$contact['contactID']."");

            if ($contact['contactRoom']) {
                $que = "SELECT sitesRooms.* FROM `sitesRooms` WHERE sitesRooms.siteID = " . $site['siteID'] . " AND roomID=" . $contact['contactRoom'] . " ORDER BY showOrder";
                $room = udb::single_row($que);
            }
            ?>
            <div class="pnia">
                <div class="contLine">
                    <div class="label">סוג פנייה</div>
                    <div class="label2"><?= $contactType[$contact['contactType']] ?></div>
                </div>
                <div class="contLine">
                    <div class="label">נשלח ב</div>
                    <div class="label2" style="direction:ltr;"><?= date("d.m.y H:i:s", strtotime($contact['contactDate'])) ?></div>
                </div>
                <?php if ($contact['contactType'] == 2) { ?>
                    <div class="contLine">
                        <div class="label">חדר:</div>
                        <div class="label2"><?= $room['roomName'] ?></div>
                    </div>
                    <div class="contLine">
                        <div class="label">תאריך מבוקש:</div>
                        <div class="label2"><?= date("d.m.y", strtotime($contact['contactDateHost'])) ?></div>
                    </div>
                    <div class="contLine">
                        <div class="label">כמות לילות:</div>
                        <div class="label2"><?= $contact['contactNights'] ?></div>
                    </div>
                    <div class="contLine">
                        <div class="label">הרכב אירוח</div>
                    </div>
                    <div class="contLine small">
                        <div class="label">מבוגרים</div>
                        <div class="label2"><?= $contact['contactAdults'] ?></div>
                    </div>
                    <div class="contLine small">
                        <div class="label">ילדים</div>
                        <div class="label2"><?= $contact['contactKids'] ?></div>
                    </div>
                    <div class="contLine small">
                        <div class="label">תינוקות</div>
                        <div class="label2"><?= $contact['contactBabies'] ?></div>
                    </div>
                <?php } ?>
                <div class="contLine">
                    <div class="label">שם הפונה:</div>
                    <div class="label2"><?= $contact['contactName'] ?></div>
                </div>
                <div class="contLine">
                    <div class="label">טלפון:</div>
                    <a class="label2" href="tel:<?=$contact['contactPhone'] ?>"><?=$contact['contactPhone'] ?></a>
                </div>
                <div class="contLine">
                    <div class="label">אימייל:</div>
                    <div class="label2"><?= $contact['contactEmail'] ?></div>
                </div>
                <div class="contLine large">
                    <div class="label">תוכן הודעה:</div>
                    <div class="label2"><?= $contact['contactMsg'] ?></div>
                </div>

                <form method="POST" class="statusform">
                    <input type="hidden" value="<?=$contact['contactID']?>" name="contactID">
                    <div class="contLine">
                        <input type="submit" class="submitform" value="סגור X" style="float: right;margin-top: 15px;">
                        <?/*?>
                        <input type="checkbox" id="status" value="1" <?=$contact['contactStatus']==1?"checked":""?> name="contactStatus">
                        <label for="status">טופל</label>
                        <?*/?>
                    </div>
                </form>
            </div>

            <?php
        }
    } else {

    $que="SELECT * FROM sitesContacts WHERE contactSiteID=".$site['siteID']." AND contactDate >='".$lastDate."'  ORDER BY contactDate DESC";
    $messages = udb::full_list($que);
    if($messages){ ?>
        <div class="pnia">
            <table cellpadding="0" cellspacing="0" class="tablemessages">
                <tr>
            
                    <th>שם פונה</th>
                    <th>טלפון</th>
                    <th>תאריך מבוקש</th>
                    <th>חדש</th>
                </tr>
                <?php foreach($messages as $mes){ ?>
                <tr>

                    <td  onclick="window.location.href='/cms/user/notifications.php?cID=<?=$mes['contactID']?>'"><?=$mes['contactName']?></td>
                    <td><a href="tel:<?=$mes['contactPhone']?>;" ><?=$mes['contactPhone']?></a></td>
                    <td  onclick="window.location.href='/cms/user/notifications.php?cID=<?=$mes['contactID']?>'" style="direction:ltr;"><?=$mes['contactDateHost']!="0000-00-00" && $mes['contactDateHost']!="1970-01-01"?date("d.m.Y", strtotime($mes['contactDateHost'])):""?></td>
                    <td  onclick="window.location.href='/cms/user/notifications.php?cID=<?=$mes['contactID']?>'"><?=$mes['show']?"קראתי":" <span style='color:red'>חדש</span>"?></td>
                </tr>
                <?php } ?>
            </table>
        </div>
<?php } else { ?>
<span style="font-size:25px;font-weight:bold;text-align:center;display:block;margin-top:10px;">אין פניות</span>
<?php } ?>

<?php }?>

    <style>
		section {height:100%;overflow:auto}
    </style>

    <?=getFixedButtons()?>
    <script>
        function openCalendar(id){
            $(".loaderUser").show();
            indexTabs++;
            $("#theTabs").append('<div class="aTab" style="z-index:200" id="aTab-'+indexTabs+'"><div class="inTabBox"><iframe id="frame'+indexTabs+'" frameborder=0 src="/cms/calendar/calendar.php?frame='+indexTabs+'&sID='+id+'"></iframe><div class="aTabCloser" onclick="closeTab(\''+indexTabs+'\')">x</div></div></div>');
            window.setTimeout(function(){
                $(".loaderUser").hide();
            }, 300);
        }
    </script>

<?php
include_once "../bin/footer.php";
?>