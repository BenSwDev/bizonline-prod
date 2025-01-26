<?php
include_once "../bin/system.php";
include_once "../bin/top.php";

$freeSearch=inputStr($_GET['freeSearch']);
$where="1";
if($freeSearch){
    $where.=" AND ( com_name LIKE '%".$freeSearch."%' OR com_phone LIKE '%".$freeSearch."%' OR com_mail LIKE '%".$freeSearch."%')";
}

if(intval($_GET['active'])==1){
    $where.=" AND ifShow=1";
} else if(intval($_GET['active'])==2){
    $where.=" AND ifShow=0";
}


$que="SELECT Comments.*
      FROM `Comments` 
      WHERE ".$where." 
      ORDER BY add_date DESC";
$messages= udb::full_list($que);

?>
    <div class="popRoom">
        <div class="popRoomContent"></div>
    </div>
    <script> var defaultPass = "32"; </script>
    <div class="manageItems" id="manageItems">
        <h1>חוות דעת לצימרים</h1>
        <div class="filter" style="margin-top:0;border-top:1px solid #fff;">
            <h2>חיפוש צימר:</h2>
            <form method="get">
                <div>
                    <input type="search" name="freeSearch" <?php if(isset($freeSearch)){ echo 'value="'.$freeSearch.'"'; }?> placeholder="חפש לפי שם לקוח, טלפון או אימייל" autocomplete="off">
                    <select name="active" style="width:100px;">
                        <option value="0">-</option>
                        <option value="1" <?=intval($_GET['active'])==1?"selected":""?>>מוצג</option>
                        <option value="2" <?=intval($_GET['active'])==2?"selected":""?>>לא מוצג</option>
                    </select>
                    <input type="submit" value="חפש">
                </div>
            </form>
        </div>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>כותרת</th>
                <th>שם האורח</th>
                <th>מס' טלפון</th>
                <th>אימייל</th>
                <th>תאריך אירוח</th>
                <th>תאריך</th>
                <th>מוצג</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($messages as $msg){ ?>
                <tr id="<?=$msg['commentID']?>" tab-id="">
                    <td onclick="openPnia(<?=$msg['commentID']?>)"><?=($msg['commentID'])?></td>
                    <td onclick="openPnia(<?=$msg['commentID']?>)"><?=outDb($msg['com_title'])?></td>
                    <td onclick="openPnia(<?=$msg['commentID']?>)"><?=outDb($msg['com_name'])?></td>
                    <td onclick="openPnia(<?=$msg['commentID']?>)"><?=outDb($msg['com_phone'])?></td>
                    <td onclick="openPnia(<?=$msg['commentID']?>)"><?=outDb($msg['com_mail'])?></td>
                    <td onclick="openPnia(<?=$msg['commentID']?>)" style="direction:ltr;text-align:center;"><?=$msg['dateHost']!="0000-00-00" && $msg['dateHost']!="1970-01-01" ? date("d.m.Y", strtotime($msg['dateHost'])): ""?></td>
                    <td onclick="openPnia(<?=$msg['commentID']?>)" style="direction:ltr;text-align:center;"><?=date("d.m.Y", strtotime($msg['add_date']))?></td>
                    <td onclick="openPnia(<?=$msg['commentID']?>)"><?=($msg['ifShow']==1?"<span class='greenColor'>כן</span>":"<span class='redColor'>לא</span>")?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <style>
        .manageItems table > thead > tr > th:nth-child(2){width:60px;}
        .manageItems table > thead > tr > th:nth-child(3){width:60px;}
        .manageItems table > thead > tr > th:nth-child(4){width:60px;}
        .manageItems table > thead > tr > th:nth-child(5){width:60px;}
        .manageItems table > thead > tr > th:nth-child(6){width:60px;}
        .manageItems table > thead > tr > th:nth-child(7){width:80px;}
        .manageItems table > thead > tr > th:nth-child(8){width:30px;}
        .manageItems table > thead > tr > th:nth-child(9){width:40px;}
        .manageItems table > thead > tr > th:nth-child(10){width:40px;}
    </style>
    <input type="hidden" id="orderResult" name="orderResult" value="">
    <script>

        function deleteMinisite(siteID){
            s=prompt('הכנס סיסמא','');
            if(s==defaultPass){
                if(confirm("אתה באמת מתכוון למחוק את המתחם??????")){
                    location.href='?siteDel='+siteID;
                }
            } else {
                alert("סיסמא שגויה");
            }
        }

        function openPnia(contactID){
            $(".popRoomContent").html('<iframe id="frame_'+contactID+'" frameborder=0 src="/cms/messages/minicomment.php?contactID='+contactID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+contactID+'\')">x</div>');
            $(".popRoom").show();
        }
        function closeTab(id){
            $(".popRoomContent").html('');
            $(".popRoom").hide();
        }
    </script>
<?php
include_once "../bin/footer.php";
?>