<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$asid = $_CURRENT_USER->active_site() ?: 0;

//$list = $_CURRENT_USER->sites();

if ('POST' == $_SERVER['REQUEST_METHOD']){
    $asid = typemap($_POST['asid'], 'int');

    try {
        $_CURRENT_USER->select_site($asid);

        $data = typemap($_POST, [
            'healthText1'   => 'text',
            'healthText1En'   => 'text'
        ]);
        $data['health_prev_day'] = intval($_POST['health_prev_day'])?: 0;

//    if ($asid && in_array($asid, $list)){
        udb::update('sites',$data, " siteID = " . $asid);
        ?>
        <script type="text/javascript">
            window.location.href = window.location.href;
        </script>
        <?php
        return;
//    }
    }
    catch (Exception $e){
        $error = $e->getMessage();
    }
}

//$asid = intval($_GET['asid'])?intval($_GET['asid']):$_CURRENT_USER->active_site();
//
//if (!$asid || !in_array($asid, $list))
//    $asid = reset($list);
//
//$sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . implode(',', $list) . ")");
$que = "SELECT 	healthText1,healthText1En,siteID,health_prev_day FROM `sites` WHERE  siteID = " . $asid;
$agreements = udb::single_row($que);

?>
<section class="orders">
    <div class="last-orders">
        <div class="title">הצהרות בריאות</div>
        <form method="post" class="agreements">
            <input type="hidden" name="asid" value="<?=$asid?>" />
<?php /*            <select name="asid" title="מתחם" onchange="window.location.href='?page=<?=$_GET['page']?>&asite='+this.value">
                <?php
                foreach($list as $sid)
                    echo '<option value="' , $sid , '" ' , ($sid == $asid ? 'selected' : '') , '>' , $sname[$sid] , '</option>';
                ?>
            </select> */ ?>
            <!-- <div class="inputLblWrap" style="font-size:16px;margin: 20px 0;clear: both;width:100%;max-width: 340px;display: flex;align-items: center;justify-content: space-between;">
				שלח בנוסף הצהרות  יום לפני טיפול
				<label class="switch" style="float:left" for="health_prev_day">
				  <input type="checkbox" name="health_prev_day" value="1" id="health_prev_day" <?=$agreements['health_prev_day']? "checked" : ""?> class="">
				  <span class="slider round"></span>
				</label>
			</div> -->
			<div class="txtAreaWrap">
                <div class="ttl">הצהרה בעברית</div>
                <textarea name="healthText1" class="textEditorX"><?=$agreements['healthText1']?></textarea>
            </div>
            <div class="txtAreaWrap">
                <div class="ttl">הצהרה באנגלית</div>
                <textarea name="healthText1En" class="textEditorX"><?=$agreements['healthText1En']?></textarea>
            </div>
            <input type="submit" value="עדכן הסכם">
        </form>
    </div>
</section>
