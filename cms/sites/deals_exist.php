<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$position=11;

$siteID=intval($_GET['sID']);
$frameID=intval($_GET['frame']);

if('POST' == $_SERVER['REQUEST_METHOD']) {
    if(!intval($_POST['refresh'])){ // save and close iframe ?>
        <script>
            window.parent.closeTab(<?=$frameID?>);
        </script>
        <?php
    } else { // save and get alert success ?>
        <script>
            window.parent.formAlert("green", "עודכן בהצלחה", "");
        </script>
    <?php }
}


$dealsList=Array();
$dealsList[0]="עסקה ראשית";
$dealsList[1]="פרסום בדף הבית";
$dealsList[2]="באנר גדול יוקרתי";
$dealsList[3]="באנר גדול רומנטי";
//$dealsList[4]="צימרים מומלצים";
$dealsList[5]="מומלצים דף הבית";
$dealsList[6]="חדשים באתר";
//$dealsList[7]="דילים תפריט ניווט";
$dealsList[8]="קידום טופ טן בעמודי חיפוש לפי קטגוריה";
$dealsList[9]="צימרים שחשבנו שתאהבו";
$dealsList[10]="קידום באנרים שוכב";
$dealsList[11]="קידום באנר ימין";

$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);
/*
$que="SELECT * FROM `deals` INNER JOIN dealsPortals USING(dealID) WHERE dealsPortals.portalID=1 AND siteID=".$siteID."";
$deals= udb::full_list($que);
*/

$que="SELECT * FROM `portals` WHERE 1";
$portals= udb::key_row($que, "portalID");

$que="SELECT * FROM `dealsPortals` INNER JOIN `deals` USING (dealID) 
	  WHERE siteID=".$siteID." ORDER BY dealType";
$deals= udb::key_row($que, Array("dealID"));


$menu = include "site_menu.php";

?>
<div class="popRoom">
    <div class="popRoomContent"></div>
</div>
<div class="editItems">
    <h1><?=outDb($site['TITLE'])?></h1>
    <div class="miniTabs">
        <?php foreach($menu as $men){
            if($men['position']==$position && $men['sub']){
                $subMenu = $men['sub'];
            }
            ?>
            <div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><p><?=$men['name']?></p></div>
        <?php } ?>
    </div>
    <?php if($subMenu){ ?>
        <div class="subMenuTabs">
            <?php foreach($subMenu as $sub){ ?>
                <div class="minitab<?=$sub['position']==$subposition?" active":""?>" onclick="window.location.href='<?=$sub['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><?=$sub['name']?></div>
            <?php } ?>
        </div>
    <?php } ?>


    <div class="manageItems">

        <table>
            <thead>
            <tr>
                <th width="30">#</th>
                <th>סוג מבצע</th>
                <th>פעיל</th>
                <th width="10%">תאריך</th>
                <th width="10%">פורטל</th>
            </tr>
            </thead>
            <tbody id="sortRow">
            <?php
            $total = count($deals);
            foreach($deals as $row) {
                ?>
                <tr id="<?=$row['dealID']?>">
                    <td align="center"><?=$row['dealID']?></td>
                    <td ><?=$dealsList[$row['dealType']]?></td>
                    <td onclick=><?=($row['dealVisible']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
                    <?php
                    if($row['toDate'] >= date("Y-m-d") && $row['fromDate'] <= date("Y-m-d")){
                        $color="green";
                    } else if($row['toDate'] > date("Y-m-d") && $row['fromDate'] > date("Y-m-d")) {
                        $color="blue";
                    } else {
                        $color="red";
                    }
                    ?>
                    <td>
                        <div style="font-size:12px;line-height:12px;color:<?=$color?>"><?=$row['fromDate'] ? date("d.m.Y", strtotime($row['fromDate'])) : ""?></div>
                        <div style="font-size:12px;line-height:12px;color:<?=$color?>"><?=$row['toDate'] ? date("d.m.Y", strtotime($row['toDate'])) : ""?></div>
                    </td>
                    <td><?=$portals[$row['portalID']]['portalName']?></td>
                </tr>
            <? } ?>
            </tbody>
        </table>
    </div>
</div>
<style>
    .manageItems table > thead > tr > th:nth-child(2){width:60%;}
    .manageItems table > thead > tr > th:nth-child(3){width:10%;}
    .manageItems table > thead > tr > th:nth-child(4){width:10%;}
    .manageItems table > thead > tr > th:nth-child(5){width:10%;}
</style>
<input type="hidden" id="orderResult" name="orderResult" value="">
</section>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div>
    </div>
</div>
<script src="<?=$root;?>/app/jquery-ui.min.js"></script>
<script>
    function openDeal(dealID, siteID){
        $(".popRoomContent").html('<iframe id="frame_'+dealID+'_'+siteID+'" frameborder=0 src="/cms/sites/minideal.php?dealID='+dealID+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+dealID+'_'+siteID+'\')">x</div>');
        $(".popRoom").show();
        window.parent.document.getElementById("frame"+<?=$frameID?>).style.zIndex="12";
    }

    function closeTab(id){
        $(".popRoomContent").html('');
        $(".popRoom").hide();
        window.parent.document.getElementById("frame"+<?=$frameID?>).style.zIndex="10";
    }
</script>
</body>
</html>