<?php
include_once "../bin/system.php";
include_once "../bin/top_user.php";

$menu = include "menu_user.php";
$position=0;
$contactType=Array(1=>"שאלה לבעל המתחם", 2=>"בקשה להזמנת נופש");

if ('POST' == $_SERVER['REQUEST_METHOD']) {
    /*
            $cp=Array();
            $cp['contactStatus']=intval($_POST['contactStatus'])?intval($_POST['contactStatus']):0;
            $cp['contactID']=intval($_POST['contactID']);
            udb::update("sitesContacts", $cp, "contactID=".intval($_POST['contactID']));
    */
    ?>
    <script type="text/javascript">
        window.location.href="/cms/user/reviews.php";
    </script>
    <?php
}

?>
    <div class="userTabs">
        <?php foreach($menu as $men){ ?>
            <div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>'"><p><?=$men['name']?></p></div>
        <?php } ?>
    </div>

<?php if(!$_GET['test']){
    $cID=intval($_GET['cID']);
    if($cID){
        $que="SELECT * FROM Comments WHERE siteID=".$site['siteID']." AND commentID=".$cID." ";
        $contact = udb::single_row($que);

$vacType=Array(1=>"חופשה רומנטית", 2=>"חופשה משפחתית", 3=>"חופשה קבוצתית");

        if($contact) {
            $cp=Array();
            $cp['show']=1;
            udb::update("Comments", $cp, "commentID=".$contact['commentID']."");

            $que="SELECT * FROM sites WHERE siteID=".$contact['siteID']."";
            $site=udb::single_row($que);
            ?>
            <div class="pnia">
                <div class="contLine">
                    <div class="label">נשלח ב</div>
                    <div class="label2" style="direction:ltr;"><?= date("d.m.y", strtotime($contact['add_date'])) ?></div>
                </div>

                <div class="contLine">
                    <div class="label">דירוג:</div>
                    <div class="label2"><?= $contact['newRate'] ?></div>
                </div>
                <div class="contLine">
                    <div class="label">אופי החופשה:</div>
                    <div class="label2"><?= $vacType[$contact['vactype']] ?></div>
                </div>
                <?php if($contact['comPayed']){ ?>
                <div class="contLine">
                    <div class="label">מחיר ששולם:</div>
                    <div class="label2"><?= $contact['comPayed'] ?></div>
                </div>
                <?php } ?>
                <?php if($contact['dateHost']!="0000-00-00" && $contact['dateHost']!="1970-01-01"){ ?>
                <div class="contLine">
                    <div class="label">תאריך נופש:</div>
                    <div class="label2"><?= date("d.m.y", strtotime($contact['dateHost'])) ?></div>
                </div>
                <?php } ?>
                <div class="contLine">
                    <div class="label">שם הפונה:</div>
                    <div class="label2"><?= $contact['com_name'] ?></div>
                </div>
                <div class="contLine">
                    <div class="label">טלפון:</div>
                    <a class="label2" href="tel:<?=$contact['com_phone'] ?>"><?=$contact['com_phone'] ?></a>
                </div>
                <div class="contLine">
                    <div class="label">אימייל:</div>
                    <div class="label2"><?= $contact['com_mail'] ?></div>
                </div>
                <div class="contLine">
                    <div class="label">כותרת:</div>
                    <div class="label2"><?= $contact['com_title'] ?></div>
                </div>
                <div class="contLine large">
                    <div class="label">תוכן הודעה:</div>
                    <div class="label2"><?= $contact['com_text'] ?></div>
                </div>

                <form method="POST" class="statusform">
                    <input type="hidden" value="<?=$contact['commentID']?>" name="contactID">
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

        $que="SELECT * FROM Comments WHERE siteID=".$site['siteID']." AND add_date >='2016-07-25' ORDER BY add_date DESC";
        $messages = udb::full_list($que);
        if($messages){ ?>
            <div class="pnia">
            <table cellpadding="0" cellspacing="0" class="tablemessages">
                <tr>

                    <th>שם האורח</th>
                    <th>תאריך חופשה</th>
                    <th>חדש</th>
                    <th>מוצג</th>
                </tr>
                <?php foreach($messages as $mes){ ?>
                    <tr>

                        <td  onclick="window.location.href='/cms/user/reviews.php?cID=<?=$mes['commentID']?>'"><?=$mes['com_name']?></td>
                        <td  onclick="window.location.href='/cms/user/reviews.php?cID=<?=$mes['commentID']?>'" style="direction:ltr;"><?=$mes['dateHost']!="0000-00-00" && $mes['dateHost']!="1970-01-01"?date("d.m.Y", strtotime($mes['dateHost'])):""?></td>
                        <td  onclick="window.location.href='/cms/user/reviews.php?cID=<?=$mes['commentID']?>'"><?=$mes['show']?"קראתי":" <span style='color:red'>חדש</span>"?></td>
                        <td  onclick="window.location.href='/cms/user/reviews.php?cID=<?=$mes['commentID']?>'"><?=$mes['ifShow']?"מוצג":" <span style='color:red'>לא מוצג</span>"?></td>
                    </tr>
                <?php } ?>
            </table>
            </div>
        <?php } ?>

    <?php }?>

    <style>
        .tablemessages td:nth-child(1){width:auto;}
        .tablemessages td:nth-child(2){width:60px;}
        .tablemessages td:nth-child(3){width:60px;}
        .tablemessages td:nth-child(4){width:60px;}
        section {height:100%;overflow:auto}
    </style>
<?php } else { ?>
    <div class="textSys">
        ברוכים הבאים למערכת ההקפצות של צימרטופ החדש, 

        אנו גאים להשיק את המערכת המתקדמת פרי פיתוחנו לעדכון תפוסה והקפצות במערכת.

        בשלב הבא תוכלו לעדכן דילים עבור הצימרים שלכם  ולשלוח קישור לחוות דעת לכל אורח.

        לעזרה בהתמצאות במערכת - <a href="tel:04-9285135">04-9285135</a>

    </div>
<?php } ?>
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