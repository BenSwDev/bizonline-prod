<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";


$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];


if(isset($_POST) && isset($_POST['act'])) {
    switch($_POST['act']){
        case 'active':
            $siteID = intval($_POST['siteID']);
            $treatmentID = intval($_POST['treatmentID']);
            $active  = intval($_POST['active']);
            $que = [];
            $que['treatmentID'] = $treatmentID;
            $que['siteID'] = $siteID;
            $que['active'] = $active;
            udb::insert('sites_treatments',$que,true);
            break;
    }

    exit;
}

$que = "select * from treatments";
$pages = udb::key_row($que,"treatmentID");

$treats = udb::key_row("select * from sites_treatments where siteID=".$siteID . " order by showOrder","treatmentID");



?>


<div class="popRoom">
    <div class="popRoomContent"></div>
</div>
<div class="editItems">
    <div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
    <?=showTopTabs(0)?>
    <div class="manageItems" id="manageItems">
        <h1>ניהול יחידות</h1>
        <div style="margin-top: 20px;">
            <?php if($pages && 1==2){ ?>
                <input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
            <?php } ?>
        </div>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>שם הטיפול</th>
                <th>מוצג</th>
            </tr>
            </thead>
            <tbody id="sortRow">
            <?php
            if($pages){
                foreach($pages as $key => $page){ ?>
                    <tr id="<?=$page['treatmentID']?>">
                        <td><?=$page['treatmentID']?></td>
                        <td ><?=$page['treatmentName']?></td>
                        <td><input type="checkbox" name="treatmentID<?=$page['treatmentID']?>" <?=($treats[$page['treatmentID']] && $treats[$page['treatmentID']]['active']) ? ' checked ' : '';?> onchange="updateSiteTreat(<?=$page['treatmentID']?>,<?=$siteID?>,$(this))"></td>
                    </tr>
                <?php }
            } ?>
            </tbody>
        </table>
    </div>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script type="text/javascript">

    function openPopRoom(roomID, siteID){
        $(".popRoomContent").html('<iframe id="frame_'+siteID+'_'+roomID+'" frameborder=0 src="/cms/moduls/minisites/rooms/popRoom.php?roomID='+roomID+'&siteID='+siteID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+siteID+'_'+roomID+'\')">x</div>');
        $(".popRoom").show();
        window.parent.parent.$('.tabCloser').hide();

    }

    function closeTab(reload){
        $(".popRoomContent").html('');
        $(".popRoom").hide();
        window.parent.parent.$('.tabCloser').show();
        reload && window.location.reload();
    }


    function orderNow(is){
        $("#addNewAcc").hide();
        $(is).val("שמור סדר תצוגה");
        $(is).attr("onclick", "saveOrder()");
        $("#sortRow tr").attr("onclick", "");
        $("#sortRow").sortable({
            stop: function(){
                $("#orderResult").val($("#sortRow").sortable('toArray'));
            }
        });
        $("#orderResult").val($("#sortRow").sortable('toArray'));
    }

    function saveOrder(){
        var ids = $("#orderResult").val();
        $.ajax({
            url: 'js_order_siteTreats.php',
            type: 'POST',
            data: {ids:ids , siteID: <?=$siteID?>},
            async: false,
            success: function (myData) {
                window.location.reload();
            }
        });
    }

    function updateSiteTreat(tID,sID,obj){
        console.log($(obj).is(":checked"));
        var val = $(obj).is(":checked") == true ? 1 : 0;
        $.ajax({
            method: 'post',
            url: 'treats.php',
            data: {siteID: sID , treatmentID: tID , active: val, act: 'active'},
            success: function (res) {

            }
        });
    }

</script>