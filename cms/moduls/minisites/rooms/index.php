<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";


$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];
$domainID = DomainList::active();
$que = "SELECT u.roomID, COUNT(*) AS `orders` FROM `orders` AS `o` INNER JOIN `orderUnits` USING(`orderID`) INNER JOIN `rooms_units` AS `u` USING(`unitID`) WHERE o.allDay = 0 AND o.siteID = " . $siteID . " GROUP BY u.roomID ORDER BY NULL";
$orders = udb::key_value($que);

$minOrderUnits = udb::single_value("select minOrderUnits from sites where siteID=".$siteID);


if($_GET['rdel']!=0 && !$orders[intval($_GET['rdel'])]){
    $roomID = intval($_GET['rdel']);



    if ($units = udb::single_column("SELECT `unitID` FROM `rooms_units` WHERE `roomID` = " . $roomID)){
        $str = implode(',', $units);

        udb::query("DELETE FROM `tfusa` WHERE `unitID` IN (" . $str . ")");
        udb::query("DELETE FROM `rooms_units` WHERE `unitID` IN (" . $str . ")");

        udb::query("DELETE o.*, u.* FROM `orderUnits` AS `u` INNER JOIN `orders` AS `o` USING(`orderID`) WHERE o.allDay = 1 AND u.unitID IN (" . $str . ")");
    }

    $tables = ['rooms', 'rooms_attributes', 'rooms_domains', 'rooms_langs', 'rooms_min_nights', 'rooms_prices', 'room_pricesTok', 'room_type_search'];
    foreach($tables as $table)
        udb::query("DELETE FROM `" . $table . "` WHERE `roomID` = " . $roomID);

    // TODO
    // properly remove rooms galleries (table "rooms_galleries")

	$reload = "/cms/moduls/minisites/rooms/index.php?siteID=".$siteID."&tab=2&siteName=".$siteName ."&domid=".$domainID;

?>
<script>window.location.href = "<?=$reload?>"; //removed by Gal should Return to rooms in unit section//window.parent.location.reload(); window.parent.closeTab();</script>
<?php
}

$pages = udb::full_list("SELECT `rooms`.* , rooms_domains.active , rooms_langs.roomName as roomDomName FROM `rooms` INNER JOIN `rooms_domains` USING (roomID) INNER JOIN `rooms_langs` USING (roomID) WHERE `siteID`=".$siteID." AND rooms_domains.domainID=".$domainID."  AND rooms_langs.domainID=".$domainID." and rooms_langs.langID=1  ORDER BY `ShowOrder`");

//$pages = udb::full_list("SELECT `rooms`.* , rooms_domains.active FROM `rooms` INNER JOIN `rooms_domains` USING (roomID) WHERE `siteID`=".$siteID." AND domainID=1  ORDER BY `ShowOrder`");

if(!$pages) {
    //no rooms somain check for rooms is there are rooms clone then into rooms domains
    $rooms = udb::full_list("select *  from rooms where siteID=".$siteID);
    foreach ($rooms as $room) {
        $que = [];
        $que['domainID'] = $domainID;
        $que['roomID'] = $room['roomID'];
        $que['active'] = 0;
        udb::insert("rooms_domains",$que,true);
    }
    //$pages = udb::full_list("SELECT `rooms`.* , rooms_domains.active FROM `rooms` INNER JOIN `rooms_domains` USING (roomID) WHERE `siteID`=".$siteID." AND domainID=".$domainID."  ORDER BY `ShowOrder`");
    $pages = udb::full_list("SELECT `rooms`.* , rooms_domains.active , rooms_langs.roomName as roomDomName FROM `rooms` INNER JOIN `rooms_domains` USING (roomID) INNER JOIN `rooms_langs` USING (roomID) WHERE `siteID`=".$siteID." AND rooms_domains.domainID=".$domainID."  AND rooms_langs.domainID=".$domainID." and rooms_langs.langID=1  ORDER BY `ShowOrder`");
}

$roomType=udb::key_row("SELECT * FROM `roomTypes` WHERE 1","id");
?>
<style>
    div#warpmin {
        position: relative;
        width: 470px;
        text-align: center;
        vertical-align: middle;
    }
    #msg {
        position: absolute;
        display: none;
        width: 100%;
        height: 110%;
        background: #FFFFFF;
        top: 0;
        left: 0;
        color: green;
        line-height: 2;
    }
</style>
<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
	<?=showTopTabs(0)?>
	<div class="manageItems" id="manageItems">
		<h1>ניהול יחידות</h1>
        <div id="warpmin"><div id="msg">הנתון עודכן</div>
            <label for="minOrderUnits">מינימום יחידות להזמנה</label>
            <select name="minOrderUnits" id="minOrderUnits" onchange="updatesitemin()" style="width: 320px;">
                <option value="0">ללא הזמנת מינימום</option>
                <?php
                foreach ($pages as $k=>$i) {
                    $selected = '';
                    if($minOrderUnits == ($k+1) ) $selected = ' selected ';
                    echo '<option '.$selected.' value="' . ($k+1) . '">'. ($k+1) .' יחידות</option>';
                }
                ?>
            </select>
        </div>
		<div style="margin-top: 20px;">
			<input type="button" class="addNew" id="addNewAcc" value="הוסף יחידה" onclick="openPopRoom(0, <?=$siteID?>)">
			<?php if($pages){ ?>
			<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
			<?php } ?>
		</div>
		<table>
			<thead>
			<tr>
				<th>#</th>
				<th>שם היחידה</th>
				<th>סוג היחידה</th>
				<th>כמות</th>
				<th>מוצג</th>
<?php
        if ($domainID == 1)
            echo '<th>בפירסום</th>';
?>
				<th></th>
			</tr>
			</thead>
			<tbody id="sortRow">
				<?php
				if($pages){
				foreach($pages as $key => $page){ ?>
				<tr id="<?=$page['roomID']?>">
					<td><?=$page['roomID']?></td>
					<td onclick="openPopRoom(<?=$page['roomID']?>,<?=$siteID?>)"><?=($domainID == 1) ? $page['roomName'] : $page['roomDomName']?></td>
					<td onclick="openPopRoom(<?=$page['roomID']?>,<?=$siteID?>)"><?=$page['roomType']?$roomType[$page['roomType']]['roomType']:"דף"?></td>
					<td onclick="openPopRoom(<?=$page['roomID']?>,<?=$siteID?>)"><?= $page['roomCount']?></td>
					<td><?=($page['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
<?php
        if ($domainID == 1)
            echo '<td>' . ($page['recommend'] ? "<span style='color:green;'>כן</span>" : "<span style='color:red;'>לא</span>") . '</td>';
?>
					<td><?php if (!$orders[$page['roomID']]) {?><div onclick="if(confirm('האם אתה בטוח רוצה למחוק את החדר?')){location.href='?siteID=<?=$siteID?>&frame=&rdel=<?=$page['roomID']?>&siteName=<?//=$siteName?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div><?php } ?></td>
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
	$(".popRoomContent").html('<iframe id="frame_'+siteID+'_'+roomID+'" frameborder=0 src="/cms/moduls/minisites/rooms/popRoom.php?roomID='+roomID+'&siteID='+siteID+'&"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+siteID+'_'+roomID+'\')">x</div>');
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
		url: 'js_order_pages.php',
		type: 'POST',
		data: {ids:ids},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}

let updatesitemin = function(){
    //update-site-min.php
    let minu= $("#minOrderUnits").val();
    $.ajax({
        url: 'update-site-min.php',
        type: 'POST',
        data: {id:<?=$siteID?>, minu: minu},
        async: false,
        success: function (myData) {
            $("#msg").show();
            setTimeout(function(){
                $("#msg").fadeOut();
            },2500);
        }
    });
}
</script>