<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$sid = $_CURRENT_USER->active_site() ?: 0;

//$sid = intval($_POST['sid'] ?? $_GET['sid'] ?? $_CURRENT_USER->active_site());
//
//if (!$_CURRENT_USER->has($sid)){
//    echo 'Access denied to site ' . $sid;
//    return;
//}

$canSave = ($_CURRENT_USER->access() == TfusaUser::ACCESS_SUPER) || ($_CURRENT_USER->userType == 0);

if('POST' == $_SERVER['REQUEST_METHOD'] /*&& isset($_POST['sid'])*/ && $canSave){
    udb::update('sites', ['waitingTime' => max(0, intval($_POST['timeout']))], '`siteID` = ' . $sid);

    $data = typemap($_POST, [
        'tprice'  => ['int'=>['int'=>'numeric']],
        'tprice2' => ['int'=>['int'=>'numeric']],
        'tprice3' => ['int'=>['int'=>'numeric']]
    ]);


    udb::query("delete from `treatmentsPricesSites` where `siteID` = ".$sid);

    foreach ($data['tprice'] as $t=>$treats) {
        foreach ($treats as $time=>$price1) {
			if(strlen($price1)){
                $que = [
                    'siteID'      => $sid,
                    'treatmentID' => $t,
                    'duratuion'   => $time,
                    'price1'      => $price1 ?: 0,
                    'price2'      => $data['tprice2'][$t][$time] ?: 0,
                    'price3'      => $data['tprice3'][$t][$time] ?: 0,
                ];
                udb::insert('treatmentsPricesSites',$que,true);
			}
        }
    }

    try {
        $relay = new SpaPlusRelay($sid);
        $relay->sendPrices();
    } catch (Exception $e){
        mail('alchemist.tech@gmail.com', 'Failed to send price update to SpaPlus', 'Failed to send price update to SpaPlus (site ' . $sid . '): ' . $e->getMessage());
    }
    echo '<script> window.location.href = "?page=treatments"; </script>';
    return;
}
?>
<h1>טיפולי ספא</h1>

<?php
/*if (!$_CURRENT_USER->single_site){
    $sname = udb::key_row("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")",'siteID');
?>
    <div class="site-select">
        <label for="sid" class="labelTo">בחר מתחם</label>
        <select title="שם מתחם" onchange="location.href = '?page=treatments&asite=' + this[this.selectedIndex].value">
            <?php
            foreach($sname as $id => $name) {
                echo '<option value="' , $name['siteID'] , '" ' , ($name['siteID'] == $sid ? 'selected' : '') , '>' , $name['siteName'] , '</option>';
            }
            ?>
        </select>
    </div>
<?
}*/




$sitesTretmentsSQL = "SELECT treatments,times FROM `sitesTratments` where bizSiteID=".$sid;

$sitesTretments = udb::full_list($sitesTretmentsSQL);
/*$useTreats = [];
$useTimes = [];
foreach ($sitesTretments as $trs) {

    if($trs['times']) {
        $useTimes[] = json_decode($trs['times'],true);
    }
    $trstreatments = json_decode($trs['treatments'],true);

    foreach ($trstreatments as $t) {
        if(isset($t['id']))
            $useTreats[] = intval($t['id']);

    }
}



$realTimes = [];
foreach ($useTimes as $time) {
    foreach ($time as $rtime) {
        $realTimes[$rtime] = $rtime . " דקות";
    }
}*/

$realTimes = udb::single_column("SELECT DISTINCT `duratuion` FROM `treatmentsPricesSites` WHERE `siteID` = " . $sid." ORDER BY `duratuion`");
if ($_GET['gal']) echo "SELECT DISTINCT `duratuion` FROM `treatmentsPricesSites` WHERE `siteID` = " . $sid." ORDER BY `duratuion`";
$timeout = max(0, udb::single_value("SELECT `waitingTime` FROM `sites` WHERE `siteID` = " . $sid));

if($realTimes) {

    //$tratsSQL = "SELECT * FROM `treatments` where spaplusID in (".implode(",",$useTreats).")";
    $tratsSQL = "SELECT * FROM `treatments` where 1";

    $treatments = udb::full_list($tratsSQL);

?>
    <form method="post">
<?

    echo '<input type="hidden" name="asite" value="' . $sid . '" />';
?>
        <div class="priceTable">
            <div class="timeouts">
                <div class="timeout"><u><b>זמן נקיון: </b></u></div>
<?php
    foreach(range(0, 30, 5) as $i)
        echo '<div class="timeout"><input type="radio" name="timeout" id="timeout' , $i , '" value="' , $i , '" ' , ($timeout == $i ? 'checked' : '') , '/> <label for="timeout' , $i , '">' , $i , " <span>דק'</span></label></div>";
?>
            </div>
<table>
        <thead>
            <th>טיפול</th>
            <?foreach ($realTimes as $time) {?>
                <th><?=$time?> <span>דקות</span><div class="pricetype"><span>יחיד</span><span>זוג</span><span>קבוצה</span></div></th>
            <?}?>

        </thead>
    <tbody>
    <?
    $readonly = $canSave ? '' : 'readonly';
    $valuesSql = "select * from treatmentsPricesSites where siteID=".$sid;
    $values = udb::full_list($valuesSql);
    $prices = $prices2 = $prices3 = [];
    foreach ($values as $k=>$v) {
        $prices[$v['treatmentID']][$v['duratuion']] = $v['price1'];
        $prices2[$v['treatmentID']][$v['duratuion']] = $v['price2'];
        $prices3[$v['treatmentID']][$v['duratuion']] = $v['price3'];
    }
    foreach ($treatments as $t) {
        if (!$prices[$t['treatmentID']])
            continue;
        ?>
        <tr>
            <td width="120"><?=$t['treatmentName']?></td>
<?php

            foreach ($realTimes as $time) {
                $useTime  = preg_replace("/[^0-9]/", "", $time );

                echo '<td class="inputs"><input type="text" ' . $readonly . ' name="tprice['.$t['treatmentID'].']['.$useTime.']" value="'.($prices[$t['treatmentID']][$useTime]).'">
                        <input type="text" ' . $readonly . ' name="tprice2['.$t['treatmentID'].']['.$useTime.']" value="'.($prices2[$t['treatmentID']][$useTime] ?: '').'">
                        <input type="text" ' . $readonly . ' name="tprice3['.$t['treatmentID'].']['.$useTime.']" value="'.($prices3[$t['treatmentID']][$useTime] ?: '').'"></td>';
            }
?>
        </tr>
        <?
    }
    ?>
    </tbody>
</table>
<?if($canSave){?>
            <input type="submit" id="submitTreats" value="שמור" >
<?}?>
</div></form>
    <?
}?>

<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=treatments&v=<?=rand()?>" rel="stylesheet">
