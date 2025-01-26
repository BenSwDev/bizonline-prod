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
    window.location.href="/cms/user/messages.php";
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
        $que="SELECT * FROM msgSystem 
              LEFT JOIN msgRead ON (msgSystem.id = msgRead.msgID AND msgRead.siteID=".$site['siteID'].") 
              WHERE id=".$cID."";
        $contact = udb::single_row($que);
        
		if($contact['show']==0) {
            $cp=Array();
            $cp['show']=1;
            $cp['msgID']=$cID;
            $cp['siteID']=$site['siteID'];
			$que="SELECT * FROM msgRead WHERE msgID=".$cID." AND siteID=".$site['siteID']." ";
            $checkCp=udb::single_row($que);
			if($checkCp){
				udb::update("msgRead", $cp, "msgID=".$cID);
			} else {
				udb::insert("msgRead", $cp);
			}
			
		}
            ?>
            <div class="pnia">
                <div class="contLine">
                    <div class="label">מאת:</div>
                    <div class="label2">הודעת מערכת</div>
                </div>
                <div class="contLine">
                    <div class="label">נושא:</div>
                    <div class="label2"><?= $contact['subject']?></div>
                </div>
                <div class="contLine">
                    <div class="label">הודעה:</div>
                    <div class="label2"><?= $contact['message']?></div>
                </div>

                <form method="POST" class="statusform">
                    <input type="hidden" value="<?=$contact['contactID']?>" name="contactID">
                    <div class="contLine">
                        <input type="submit" class="submitform" value="סגור X" style="float: right;margin-top: 15px;">
                    </div>
                </form>
            </div>

            <?php
        
    } else {
    $que="SELECT * FROM msgSystem 
          LEFT JOIN msgRead ON (msgSystem.id = msgRead.msgID AND msgRead.siteID=".$site['siteID'].") 
          WHERE (msgRead.siteID=".$site['siteID']." OR type=0) AND ('".date("Y-m-d")."' BETWEEN `msgSystem`.fromDate AND `msgSystem`.untilDate)
          GROUP BY msgSystem.id
          ORDER BY id DESC";

    $messages = udb::full_list($que);
    if($messages){ ?>
        <div class="pnia">
            <table cellpadding="0" cellspacing="0" class="tablemessages">
                <tr>
                    <th >הודעות מערכת</th>
					<th width="10%">קראתי</th>
                </tr>
                <?php foreach($messages as $mes){ ?>
                <tr onclick="window.location.href='/cms/user/messages.php?cID=<?=$mes['id']?>'">               
                    <td style="text-align:center;"><?=$mes['subject']?></td>
					<td><?=$mes['show']?"קראתי":" <span style='color:red'>חדש</span>"?></td>
                </tr>
                <?php } ?>
            </table>
        </div>
<?php } else { ?>
<div class="noMsg"><span class="inNoMsg">אין הודעות</span></div>
	

<?php } }?>

    <style>
		section {height:100%;overflow:auto}
    </style>

   
    <?=getFixedButtons()?>
<?php
include_once "../bin/footer.php";
?>