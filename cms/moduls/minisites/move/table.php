<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 01/07/2021
 * Time: 16:03
 */

include_once "../../../bin/system.php";
include_once "../../../_globalFunction.php";
include_once "../../../bin/top.php";

$sites = udb::key_row("SELECT `siteID`, `siteName`, `moveData` FROM `sites` WHERE `siteType` & 2 ORDER BY `siteName`", 'siteID');

?>
<div class="editItems" style="max-height:100vh">
    <div class="manageItems" id="manageItems">
    <h1 style="text-align: center;" >שיוך אתר ל-Move</h1>
    <table style="overflow:auto">
        <thead style="position:sticky;top:0;background:white">
        <tr>
            <th style="text-align:center">ID</th>
            <th>שם המתחם</th>
            <th>פעיל ב-MOVE</th>
            <th>אחוז עמלה</th>
            <th>חבילת יחיד</th>
        </tr>
        </thead>
        <tbody id="sortRow">
<?php
    foreach ($sites as $siteID => $siteData) {
        $data = $siteData['moveData'] ? json_decode($siteData['moveData'], true) : [];
?>
        <tr data-site="<?=$siteID?>">
            <td><?=$siteID?></td>
            <td><?=$siteData['siteName']?></td>
            <td><input type="checkbox" name="active" value="1" <?=($data['active'] ? 'checked="checked"' : '')?> title="" /></td>
            <td><input type="text" name="percent" value="<?=$data['percent']?>" title="" style="width:80px" class="perc" placeholder="15" /></td>
            <td><input type="text" name="package" value="<?=$data['package']?>" title="" style="width:80px" class="pack" /></td>
        </tr>
<?php
        }
?>
        </tbody>
    </table>
    </div></div>

<style>
    .manageItems table > thead td {
        position: sticky;
        top: 0;
        z-index: 99999999;
        background: white;
        line-height: 1.5;
        height: 120px;
        max-width: 40px;
        position: relative;
    }
    .rotate-90 {
        width: 110px;
        font-size: 14px;
        line-height: 1;
        text-align: left;
        transform: rotate(-90deg);
        position: relative;
        right: -30px;
        bottom: -35px;
    }
</style>
<script>
    $(function(){
        let table = $("#manageItems"), ts;

        table.on('click', 'input[type="checkbox"]', function(){
            let siteID = $(this).closest('tr').data('site');
            saveMoveData({site:siteID, active:this.checked ? 1 : 0});
        }).on('blur', 'input[type="text"]', function(){
            let siteID = $(this).closest('tr').data('site'), prm = {site:siteID}, ts = $(this).data('ts');

            prm[this.name] = this.value;
            if (ts) {
                window.clearTimeout(ts);
                $(this).data('ts', 0);
            }

            saveMoveData(prm);
        }).on('keyup', 'input[type="text"]', function(){
            let siteID = $(this).closest('tr').data('site'), prm = {site:siteID}, ts = $(this).data('ts');

            prm[this.name] = this.value;

            if (ts)
                window.clearTimeout(ts);

            $(this).data('ts', window.setTimeout(function(){
                saveMoveData(prm);
            }, 500));
        });


        function saveMoveData(prms){
            $.post('ajax_move_data.php', prms).then(function(res){
                if (!res || res.status === undefined || parseInt(res.status))
                    alert('Error: ' + (res.error || res._txt || 'unknown error'));
            });
        }
    });
</script>
<?

if(!$_GET["tab"]) include_once "../../../bin/footer.php";
