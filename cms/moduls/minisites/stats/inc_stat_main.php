<?php
function htmlDate($date){
    return implode('/', array_reverse(explode('-', $date)));
}

function date2db($date){
    return implode('-', array_reverse(explode('/', $date)));
}

$domainsList = udb::key_row("SELECT * FROM domains where domainURL!='' ","domainID");



//$sitesP = udb::key_row("SELECT siteID,portalsID FROM sites where portalsID > 0 ","portalsID");
//$sitesZ = udb::key_row("SELECT siteID,zimmersID FROM sites where zimmersID > 0 ","zimmersID");


// copy ends

$tab = typemap($_GET['tab'], 'string');

$free = typemap($_GET['free'], 'string');
$domainID = intval($_GET['domainID']);
$dateFrom = $_GET['createDate'] ? typemap(date2db($_GET['createDate']), 'date') : '';
$dateTill = $_GET['createDateTo'] ? typemap(date2db($_GET['createDateTo']), 'date') : '';

$where = ['1'];
if ($domainID)
    $where[] = "`domainID` = " . $domainID;
if ($dateFrom)
    $where[] = "### >= '" . $dateFrom . " 00:00:00'";
if ($dateTill)
    $where[] = "### <= '" . $dateTill . " 23:59:59'";

//$free = udb::escape_string($_GET['free']);
//$domainID = udb::escape_string($_GET['domainID']);
//
//
//$where = " 1 = 1 ";
//if($free) $where.=" AND sites.siteName LIKE '%".$free."%' ";
//if($domainID) {
//    $where .= ' AND sites_domains.domainID='.$domainID ;
//}
//else {
//    $where .= ' AND sites_domains.domainID=1';
//}
//$wheret = ' 1 = 1 ';
//$wherec = ' 1 = 1 ';
//$wherew = ' 1 = 1 ';
//$wherem = ' 1 = 1 ';
//
//
//if($_GET['createDate']) {
//    if($_GET['createDateTo']) {
//        $useDate = implode('-',array_reverse(explode('/',trim($_GET['createDate']))));
//        $useDate2 = implode('-',array_reverse(explode('/',trim($_GET['createDateTo']))));
//        $useDate  =  date("Y-m-d",strtotime($useDate));
//        $useDate2  =  date("Y-m-d",strtotime($useDate2));
//        if($_GET['createDateTo'] == $_GET['createDate']) {
//            $wheret.= " AND STR_TO_DATE(sites_clicks.datetime,'%Y-%m-%d')=STR_TO_DATE('".$useDate."','%Y-%m-%d') ";
//            $wherec.= " AND STR_TO_DATE(contactForm.created,'%Y-%m-%d')=STR_TO_DATE('".$useDate."','%Y-%m-%d') ";
//            $wherew.= " AND DATE_FORMAT(contact_whatsapp.created,'%Y-%m-%d')=DATE_FORMAT('".$useDate."','%Y-%m-%d') ";
//            $wherem.= " AND  STR_TO_DATE(maskyooCalls.start_call,'%Y-%m-%d')='".$useDate."'";
//        }
//        else {
//            $wheret.= " AND STR_TO_DATE(sites_clicks.datetime,'%Y-%m-%d') BETWEEN  STR_TO_DATE('".$useDate."','%Y-%m-%d') AND STR_TO_DATE('".$useDate2."','%Y-%m-%d')";
//            $wherec.= " AND STR_TO_DATE(contactForm.created,'%Y-%m-%d') BETWEEN  STR_TO_DATE('".$useDate."','%Y-%m-%d') AND STR_TO_DATE('".$useDate2."','%Y-%m-%d')";
//            $wherew.= " AND DATE_FORMAT(contact_whatsapp.created,'%Y-%m-%d') BETWEEN  DATE_FORMAT('".$useDate."','%Y-%m-%d') AND DATE_FORMAT('".$useDate2."','%Y-%m-%d')";
//            $wherem.= " AND STR_TO_DATE(maskyooCalls.start_call,'%Y-%m-%d') BETWEEN  STR_TO_DATE('".$useDate."','%Y-%m-%d') AND STR_TO_DATE('".$useDate2."','%Y-%m-%d')";
//        }
//
//    }
//    else {
//        $useDate = implode('-',array_reverse(explode('/',trim($_GET['createDate']))));
//        $wheret.= " AND STR_TO_DATE(sites_clicks.datetime,'%Y-%m-%d') >= '".$useDate."'";
//        $wherec.= " AND STR_TO_DATE(contactForm.created,'%Y-%m-%d') >= '".$useDate."'";
//        $wherew.= " AND DATE_FORMAT(contact_whatsapp.created,'%Y-%m-%d') >= '".$useDate."'";
//        $wherem.= " AND STR_TO_DATE(maskyooCalls.start_call,'%Y-%m-%d') >= '".$useDate."'";
//    }
//
//}
//else {
//    if($_GET['createDateTo']) {
//        $useDate  =  implode('-',array_reverse(explode('/',trim($_GET['createDateTo']))));
//        $wheret.= " AND STR_TO_DATE(sites_clicks.datetime,'%Y-%m-%d') <= '".$useDate."'";
//        $wherec.= " AND STR_TO_DATE(contactForm.created,'%Y-%m-%d') <= '".$useDate."'";
//        $wherew.= " AND DATE_FORMAT(contact_whatsapp.created,'%Y-%m-%d') <= '".$useDate."'";
//        $wherem.= " AND STR_TO_DATE(maskyooCalls.start_call,'%Y-%m-%d') <= '".$useDate."'";
//    }
//}
//
//if($domainID) {
//    $sql = "SELECT siteID, `type`, COUNT(*) FROM sites_clicks WHERE ".$wheret." AND sites_clicks.type IN ('whatsapp', 'phone', 'showphone') AND sites_clicks.domainID=".$domainID." GROUP BY siteID, `type`";
//    $qsites = udb::key_list($sql,  ['siteID', 'type'], 2);
////	echo $sql;
////	print_r($qsites);
//
//} else {
//    $qsites = udb::key_list("SELECT siteID, `type`, COUNT(*) FROM sites_clicks WHERE ".$wheret." AND sites_clicks.type IN ('whatsapp', 'phone', 'showphone') GROUP BY siteID, `type`",  ['siteID', 'type'], 2);
//
//}

// if there's "free" parameter - find sites first
if ($free){
    $que = "SELECT sites.siteID, sites.siteName, sites.portalsID, sites.zimmersID 
            FROM sites " . /*($domainID ? " INNER JOIN `sites_domains` AS `sd` ON (sd.siteID = sites.siteID AND sd.domainID = " . $domainID . " AND sd.active = 1)" : "") .*/ "
            WHERE sites.siteName LIKE '%" . udb::escape_string($free) . "%'
            ORDER BY `siteName`";
    $sites = udb::key_row($que, 'siteID');

    $where[] = "`siteID` IN (" . implode(',', array_keys($sites) ?: [0]) . ")";
}

$where = implode(' AND ', $where);

$qsites = udb::key_row("SELECT siteID, `type`, COUNT(*) AS `Total` FROM sites_clicks WHERE " . str_replace('###', '`datetime`', $where) . " AND sites_clicks.type IN ('whatsapp', 'phone', 'showphone') GROUP BY siteID, `type` ORDER BY NULL",  ['siteID', 'type']);

//$sites = udb::full_list("SELECT distinct(sites_domains.siteID) , sites.siteName,sites.portalsID,sites.zimmersID FROM sites_domains left join sites using (siteID) WHERE $where");

$conForm     = udb::key_value("SELECT `siteID`, COUNT(*) as `Total` FROM `contactForm` WHERE " . str_replace('###', 'contactForm.created', $where) . " GROUP BY `siteID` ORDER BY NULL");
$conWhatsapp = udb::key_value("SELECT `siteID`, COUNT(*) as `Total` FROM `contact_whatsapp` WHERE " . str_replace('###', 'contact_whatsapp.created', $where) . " GROUP BY `siteID` ORDER BY NULL");
$maskyoo     = udb::key_value("SELECT `siteID`, COUNT(*) as `Total` FROM `maskyooCalls` WHERE " . str_replace('###', 'maskyooCalls.start_call', $where) . " GROUP BY `siteID` ORDER BY NULL");

// if there's no "free" parameter - search sites AFTER all the others
if (!$free){
    $sids = array_unique(array_merge(array_keys($conForm), array_keys($conWhatsapp), array_keys($maskyoo), array_keys($qsites)));

    $que = "SELECT sites.siteID, sites.siteName, sites.portalsID, sites.zimmersID 
            FROM sites " . /*($domainID ? " INNER JOIN `sites_domains` AS `sd` ON (sd.siteID = sites.siteID AND sd.domainID = " . $domainID . " AND sd.active = 1)" : "") .*/ "
            WHERE sites.siteID IN (" . implode(',', $sids ?: [0]) . ")
            ORDER BY `siteName`";
    $sites = udb::key_row($que, 'siteID');
}


if($tab){?>
    <div class="popRoom"><div class="popRoomContent"></div></div>
<?}else{?>
    <div class="pagePop"><div class="pagePopCont"></div></div>
<?}?>

<?if($tab){?>
<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
    <?=showTopTabs(0)?>
    <?}?>


    <div class="manageItems" id="manageItems">
    <h1>סטטיסטיקות</h1>
    <?=$addLogOut?>
<style>
.searchOrder {margin: 20px auto 0;display: block;padding: 13px 30px;border: 1px solid #0dabb6;border-radius: 8px;background: #fff;left: 0;right: 0;position: relative;max-width: 240px;overflow: hidden;}
.searchOrder form {margin-top: 10px;}
.searchOrder form .inputWrap {margin: 5px;}
.searchOrder form .inputWrap input[type=text] {height: 40px;box-sizing: border-box;padding-right: 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;width: 100%;}
.searchOrder form .clear {text-decoration: none;font-size: 16px;display: inline-block;vertical-align: top;background: #fff;color: #0dabb6;border-radius: 5px;margin: 5px;border: 1px #0dabb6 solid;float: right;line-height: 40px;width: 60px;text-align: center;}
.searchOrder form input[type=submit] {display: block;vertical-align: top;background: #0dabb6;color: #fff;border-radius: 5px;cursor: pointer;margin: 5px;width: 100px;float: left;font-size: 20px;line-height: 40px;}
.searchOrder form .inputWrap select {height: 40px;box-sizing: border-box;padding-right: 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;text-align: right;width: 100%;}
.excel {
    line-height: 44px;
    margin: 10px 5px;
    display: inline-block;
    font-size: 16px;
    color: #0dabb6;
    background: white;
    border: 1px #0dabb6 solid;
    padding: 0 10px;
    cursor: pointer;
    border-radius: 10px;
}
</style>
    <div class="searchOrder">
        <div class="ttl">חפש פעולות</div>
        <form method="GET" autocomplete="off" action="">

			<input type="hidden" name="tab" value="<?=$tab?>">
            <div class="inputWrap half">
                <input type="text" name="createDate" placeholder="תאריך מ" class="searchFrom" value="<?=($dateFrom ? db2date($dateFrom, '/') : '')?>" readonly>
            </div>
            <div class="inputWrap half">
                <input type="text" name="createDateTo" placeholder="תאריך עד" class="searchFrom" value="<?=($dateTill ? db2date($dateTill, '/') : '')?>" readonly>
            </div>

            <div class="inputWrap">
                <input type="text" name="free" placeholder="חיפוש חופשי" value="<?=str_replace('"', '\"', $free)?>" />
            </div>

            <div class="inputWrap">
				<select name="domainID" title="">
                    <option value="0">כללי</option>
<?
    foreach ($domainsList as $domain) {
        if($domain['domainID'] == 1) continue;
?>
                    <option value="<?=$domain['domainID']?>"<?=(($domainID == $domain['domainID']) ? ' selected' : '')?>><?=$domain['domainName']?></option>
<?php
    }
?>
    			</select>
            </div>
            <a class="clear" href="<?=$clearUrl?>">נקה</a>
            <input type="submit" value="חפש">
        </form>
    </div>

	<div class="searchCms">

	</div>
    <div class="excel" id="expExcel">ייצוא לאקסל</div>
    <div class="counter"></div>

    <table id="reports">
        <thead>
            <tr>
                <td>ID</td>
				<td>שם המתחם</td>
				<td>הקלקות ווטסאפ</td>
				<td>התחלת שיחת וואטסאפ</td>
				<td>הצגת טלפון</td>
				<td>טלפון</td>
                <td>שיחות מסקיו</td>
				<td>לידים יצירת קשר</td>

            </tr>
        </thead>
        <tbody id="sortRow">
<?php

$totals = [
        'qw' => 0,
        'cw' => 0,
        'qs' => 0,
        'qp' => 0,
        'm'  => 0,
        'cf' => 0
];

if (count($sites)){
//    $i=0;
//    $adding = '';
//    if($domainID) $adding = '  domainID='.$domainID . " AND ";
//    $conForm = udb::key_row("SELECT COUNT(*) as Total,siteID FROM contactForm WHERE ".$adding.$wherec ." group by siteID","siteID");
//    $conWhatsapp = udb::key_row("SELECT COUNT(*) as Total,siteID FROM contact_whatsapp WHERE ".$adding.$wherew." group by siteID","siteID");
//    $maskyooSql = "SELECT COUNT(*) as Total,siteID FROM maskyooCalls WHERE ".$adding.$wherem." group by siteID";
//    $maskyoo = udb::key_row($maskyooSql,"siteID");
//    //echo "SELECT COUNT(*) as Total,siteID FROM contactForm WHERE ".$adding.$wherec ." group by siteID";
//    //print_r($maskyoo);
//    //exit;

    $sitesCount = 0;
    foreach($sites as  $site) {

        if($domainID == 9 && !$site['zimmersID'])
            continue;
        if(($domainID == 7 || $domainID == 8) && !$site['portalsID'])
            continue;

        $siteID = $site['siteID'];

        $sitesCount++;
?>
        <tr id="<?=$site['siteID']?>">
            <td title="portalsID: <?=$site['portalsID']?>, zimmersID: <?=$site['zimmersID']?>"><?=$site['siteID']?></td>
            <td><?=$site['siteName']?></td>
            <td><?=($qsites[$siteID]['whatsapp']['Total'] ?? 0)?></td>
            <td><?=($conWhatsapp[$siteID] ?? 0)?></td>
            <td><?=($qsites[$siteID]['showphone']['Total'] ?? 0)?></td>
            <td><?=($qsites[$siteID]['phone']['Total'] ?? 0)?></td>
            <td><?=($maskyoo[$siteID] ?? 0)?></td>
            <td><?=($conForm[$siteID] ?? 0)?></td>
        </tr>
<?php
        $totals['qw'] += ($qsites[$siteID]['whatsapp']['Total'] ?? 0);
        $totals['cw'] += ($conWhatsapp[$siteID] ?? 0);
        $totals['qs'] += ($qsites[$siteID]['showphone']['Total'] ?? 0);
        $totals['qp'] += ($qsites[$siteID]['phone']['Total'] ?? 0);
        $totals['m']  += ($maskyoo[$siteID] ?? 0);
        $totals['cf'] += ($conForm[$siteID] ?? 0);
    }
}
?>
        <tr>
            <th colspan="2">סה"כ</th>
            <th style="text-align:right"><?=$totals['qw']?></th>
            <th style="text-align:right"><?=$totals['cw']?></th>
            <th style="text-align:right"><?=$totals['qs']?></th>
            <th style="text-align:right"><?=$totals['qp']?></th>
            <th style="text-align:right"><?=$totals['m']?></th>
            <th style="text-align:right"><?=$totals['cf']?></th>
        </tr>
        </tbody>
    </table>
        <?echo $sitesCount;?>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">

    <?if($_GET['tab']){?>
	</div>
    </div>
<?}?>
<script src="/assets/js/jquery.table2excel.min.js"></script>
<script>
$('.searchFrom').datepicker({
    format: 'd/m/Y',
    timepicker: false

});
$('.searchTo').datepicker({
    format: 'd/m/Y',
    onShow:function( ct ){
        this.setOptions({
            minDate:$('.searchFrom').val()?$('.searchFrom').val().split("/").reverse().join("-"):false
        })
    },
    timepicker: false
});

//$.datetimepicker.setLocale('he');

var pageType="<?=$pageType?>";
$(".counter").html( "<?=$sitesCount?> רשומות" );
function openPop(pageID, siteName){
    $(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/frame.dor.php?siteID='+pageID+'&siteName='+encodeURIComponent(siteName)+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
    $(".pagePop").show();
}
function closeTab(){
    $(".pagePopCont").html('');
    $(".pagePop").hide();
}

$('#expExcel').on('click', function(e){
    e.preventDefault();
    var table = $('table#reports');
    if(table && table.length){
        var preserveColors = (table.hasClass('table2excel_with_colors') ? true : false);
        $(table).table2excel({
            exclude: ".noExl",
            name: "Excel Document Name",
            filename: "report_manage" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
            fileext: ".xls",
            exclude_img: true,
            exclude_links: true,
            exclude_inputs: true,
            preserveColors: preserveColors
        });
    }
    // window.location.href = 'ajax_excel_reports_manage.php' + window.location.search;
});
</script>
<style>
	.manageItems table > thead > tr > th, .manageItems table > thead > tr > td {width:auto !important}
    .counter {position: relative;display: block;text-align: left;}
</style>
