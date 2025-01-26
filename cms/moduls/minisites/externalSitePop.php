<?php
include_once "../../bin/system.php";
include_once "../../_globalFunction.php";

//$engine  = typemap($_GET['engine'] ?? '', 'string');
$engine = $engineName = 'WuBook';
$siteID  = typemap($_GET['sid'] ?? 0, 'int');
$hotelID = typemap($_GET['hotel'] ?? '', 'string');

if (!$siteID)
    die('No site ID');

/* if no engine selected - let select one */
if (!$engine){
    include_once "../../bin/top_frame.php";
?>
        html for engine selection
<?php
    include_once "../../bin/footer.php";

    return;
}
//--------------------------------------------------------------------------------------------------------------------------------------------------

/* if no external hotel selected - let select one */
if (!$hotelID){
    $site = $siteID ? ReferenceMap::from_local($siteID, $engine) : null;

    include_once "../../bin/top_frame.php";
?>
<style>
    .editItems select, .editItems input {line-height:25px; height:30px; font-size:16px}
</style>
<h1 class="frameTitle"><?=$engineName?></h1>
<div class="editItems">
    <form id="locationForm">
        <input type="hidden" name="engine" value="<?=htmlspecialchars($engineName)?>" />
        <input type="hidden" name="sid" value="<?=$siteID?>" />
        <div class="section">
            <div class="label">קוד המקום :</div>
            <input type="text" name="hotel" data-last="<?=($site ? $site->remote_id() : '')?>" value="<?=($site ? $site->remote_id() : '')?>" title="" />
        </div>
        <div class="section bttns">
            <div class="inptLine">
                <input type="submit" value="המשך" class="submit" style="margin-left:30px" />
            </div>
        </div>
    </form>
</div>
<?php
    /************************* if found hotels ********************************/
/*    if (count($hotels)){
?>
<div class="editItems">
    <form>
        <input type="hidden" name="engine" value="<?=safeForJS($engineName, '"')?>" />
        <input type="hidden" name="sid" value="<?=$siteID?>" />

        <b style="margin-top:0">בחר בית מלון</b>
        <div class="section txtarea" id="hotelsDiv">
            <ul>
<?php
        $cnt = 0;
        foreach($hotels as $key => $name)
	            echo '<li><input type="radio" name="hotel" id="hotel' , $key , '" value="' , safeForJS($key, '"') , '" /><label for="hotel' , $key , '" style="width:unset;direction:ltr;text-align:right">' , $name , '</label></li>';
?>
            </ul>
        </div>
        <div class="section bttns">
            <div class="inptLine">
                <input type="submit" value="בחר" class="submit" style="margin-left:30px" />
            </div>
        </div>
    </form>
</div>
<?php
    }*/
?>
<script>
$(function(){
    //$('form').on('submit', showLoader);

    /*$('#area').on('change', function(){
        var area = this.value;
        $('#loc').val('').find('option').each(function(){
            this.style.display = (!area || $(this).data('area') == area) ? 'block' : 'none';
        });
    });*/

    /*$('#locationForm').on('submit', function(e){
        e.preventDefault();
    })*/
});
</script>
<?php
    include_once "../../bin/footer.php";

    return;
}
else {      // there is hotelID - show rooms selection
    $today = date('Y-m-d');

    $eObject = new ReferenceMap($engine);     // just for class load for now

    $local = udb::key_value("SELECT `roomID`, `roomName` FROM `rooms` WHERE `siteID` = " . $siteID);
    try {
        $remote = (new WuBook\WuBookManager)->fetch_rooms($hotelID);
    }
    catch (WuBook\Exception $e){
        $remote = [];
    }
    $map = (new RemoteSite($siteID, $hotelID, $engine))->room_map();


    if ('POST' == $_SERVER['REQUEST_METHOD']){
        $post = typemap($_POST['room'] ?? [], ['int' => 'string']);

        if (strlen(implode('', $post))){
            udb::insert('referenceMap', ['localID' => $siteID, 'remoteID' => $hotelID, 'refType' => 'site', 'engineID' => $eObject->engine_id()], true);

            foreach($local as $roomID => $roomName){
                if (!$post[$roomID])
                    udb::query("DELETE FROM `referenceMap` WHERE `localID` = " . $roomID . " AND `engineID` = 1 AND `refType` = 'room'");
                elseif ($post[$roomID] == -99){
                    $roomData = udb::single_row("SELECT * FROM `rooms` WHERE `roomID` = " . $roomID);
                    $names = udb::key_value("SELECT l.LangCode, r.roomName FROM `rooms_langs` INNER JOIN `language` USING(`langID`) WHERE `roomID` = " . $roomID ." AND `domainID` = 1 AND `roomName` > ''");

                    try {
                        // create a new room at WuBook and link it
                        $wb = new WuBook\WuBookManager;
                        $remoteID = $wb->create_regular_room($siteID, 'Room ' . $roomID, $roomData['maxGuests'], 999, 'r' . substr($roomID, -3), $roomData['roomCount'], $names);

                        udb::insert('referenceMap', ['localID' => $roomID, 'remoteID' => $remoteID, 'refType' => 'room', 'engineID' => $eObject->engine_id()], true);
                    }
                    catch (WuBook\Exception $e){
                        $error = "Cannot create room at WuBook: " . $e->getMessage();
                    }
                }
                else {
                    udb::insert('referenceMap', ['localID' => $roomID, 'remoteID' => $post[$roomID], 'refType' => 'room', 'engineID' => $eObject->engine_id()], true);
                }
            }
        }

        die('<html><head></head><body><script>parent._closeTab()</script></body>');
    }

    include_once "../../bin/top_frame.php";
?>
<script>
$(function(){
    //$('form').on('submit', showLoader);
});
</script>
<h1 class="frameTitle">שיוך חדרים חיצוניים</h1>
<div class="editItems">
    <form id="locationForm" method="POST">
        <input type="hidden" name="engine" value="<?=htmlspecialchars($engineName)?>" />
        <input type="hidden" name="sid" value="<?=$siteID?>" />
        <input type="hidden" name="hotel" value="<?=htmlspecialchars($hotelID)?>" />
<?php
    foreach($local as $localID => $localName){
?>
        <div class="section" style="width:170px; padding:0; margin:3px">
            <div style="direction:ltr;text-align:right"><?=$localName?></div>
        </div>
        <div class="section" style="width:230px; padding:0; margin:3px">
            <select name="room[<?=$localID?>]" title="">
                <option value="0">- - - - - - - - - - - -</option>
<?php
        foreach($remote as $remRoom)
            if (!$remRoom['subroom'])
                echo '<option value="' , $remRoom['id'] , '" ' , ($remRoom['id'] == $map[$localID] ? 'selected' : '') , '>' , $remRoom['name'] , '</option>';
?>
                <option value="-99">- - חדר חדש - -</option>
            </select>
        </div> 
<?php
    }
?>
        <div class="section bttns">
            <div class="inptLine">
                <input type="submit" value="סיום" class="submit" style="margin-left:30px" />
            </div>
        </div>
    </form>
</div>
<?php
    include_once "../../bin/footer.php";

    return;
}
