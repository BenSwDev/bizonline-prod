<?php
include_once "../../../bin/system.php";

error_reporting(-1 ^ E_NOTICE);

if ($_GET['out'] == 'selected'){
    include 'export_selected.php';
    exit;
}

if($_GET['tab']){
	include_once "../../../bin/top_frame.php";	
	include_once "../mainTopTabs.php";
	include_once "innerMenu.php";
}else{
	include_once "../../../bin/top.php";	
}
include_once "../../../_globalFunction.php";


function htmlDate($date){
    return implode('/', array_reverse(explode('-', $date)));
}

//$domainsList = udb::key_row("SELECT * FROM domains","domainID");


$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];

$filter = intval($_GET['filter'] ?? 1);
$siteID = intval($_GET['siteID']);
$sourceID = $_GET["sourceID"]? typemap($_GET['sourceID'], 'string') : "spaplus";
$domainID = intval($_GET['domainID'] ?? 0);
$statusID = (intval($_GET['statusID'])>0 ? intval($_GET['statusID'])-1 :  -1);
//$commission = intval($_GET['commission']);
$free = typemap($_GET['free'], 'string');
$dateType = intval($_GET['dateType'] ?? 0);
$dateFrom = implode('-',array_reverse(explode('/',(($_GET['dateFrom'])))))." 00:00:00";
$dateUntil = implode('-',array_reverse(explode('/',(($_GET['dateUntil'])))))." 23:59:59";


//echo $statusID;
$status = ['cancelled' => 'בוטלה', 'confirmed' => 'אושרה', 'pending' => 'מחכה לאישור', 'error' => 'שגיאה!', 'request' => 'בקשת הזמנה'];
   
//  switch($filter){
//	  case  1: $cond = "`timeFrom` >= '" . date('Y-m-d') . "'"; $class = 'future'; break;
//	  case -1: $cond = "`timeUntil` < '" . date('Y-m-d') . "'"; $class = 'past'; break;
//	  default: $cond = '1'; $class = 'all'; break;
//  }
//
$where = ['1'];

if($siteID) $where[]="`orders`.siteID = ".$siteID."  ";
$dateT[2]='`Torders`.`TimeFrom`';
$dateT[1]='`Torders`.`create_date`';
if($dateType){
	$addSearch[] =  $dateT[$dateType]." >='".$dateFrom."' AND ".$dateT[$dateType]." <='".$dateUntil."'";
}

if($sourceID){
	$addSearch[] =  "`Torders`.sourceID LIKE '".$sourceID."'";
}

if($addSearch){
$joinSearch ="INNER JOIN `orders` AS `Torders` ON (`Torders`.parentOrder = orders.orderID AND ".implode(' AND ', $addSearch).")";
}


if($statusID >=0){
	$where[]= "`orders`.`status` IN ('".$statusID."')  ";
}



if($free)
    $where[] = is_numeric($free) ? "(orders.customerPhone LIKE '%".$free."%' OR orderID = " . intval($free) . ")" : "(sites.siteName LIKE '%".$free."%' OR orders.customerName LIKE '%".$free."%' OR orders.customerPhone LIKE '%".$free."%' OR orders.customerEmail LIKE '%".$free."%')";

//$where = " WHERE orders.apiSource = 'spaplus' AND ".$where.$cond;

$oids = [];

$que = "SELECT `orders`.*, sites.siteName, sites.owners ,sites.onlineCommission
        FROM `orders` 
		INNER JOIN sites ON (`orders`.siteID = sites.siteID) ".
		$joinSearch
        ."WHERE orders.apiSource = 'spaplus' AND `orders`.`allDay`= 0 AND orders.parentOrder = orders.orderID  AND ". implode(' AND ', $where) . "			   
        ORDER BY `orderID` DESC LIMIT 1500"; //echo $que;
if($_GET)
	$oids = udb::key_row($que, 'orderID');
//echo $que;
if(count($oids)){
$que = "SELECT `parentOrder`, COUNT(*) AS `cnt`, GROUP_CONCAT(DISTINCT DATE(`timeFrom`) SEPARATOR ',') AS `dates` 
        FROM `orders` 
        WHERE `orderID` <> `parentOrder` AND `parentOrder` IN (" . implode(',', array_keys($oids)) . ")
        GROUP BY `parentOrder`
        ORDER BY NULL";

$treats = udb::key_row($que, 'parentOrder');
}
?>


<?if($_GET['tab']){?>
<div class="popRoom"><div class="popRoomContent"></div></div>
<?}else{?>
<div class="pagePop"><div class="pagePopCont"></div></div>
<?}?>

<?if($_GET['tab']){?>
<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
	<?=showTopTabs(0)?>
<?}?>

		
<div class="manageItems" id="manageItems">
    <h1>ניהול הזמנות</h1>
	
	<div class="searchCms">
		<form method="GET">
			<input type="text" name="free" placeholder="מלל חופשי" value="<?=$free?>" style="width:auto">
<?php /*
			<select name="filter">
				<option value="1" <?=($filter =="1" ?"selected":"")?>>עתידיות</option>				
				<option value="-1" <?=($filter=="-1"?"selected":"")?> >הזמנות עבר</option>
				<option value="0" <?=($filter=="0"?"selected":"")?>>הכל</option>				
			</select>
			*/?>
			<select name="siteID">
				<option>כל בתי הספא</option>
<?
				$que = "SELECT siteID, siteType,siteName FROM sites WHERE siteType=2 ORDER BY siteName";
				$sites = udb::key_row($que, 'siteID');
				foreach($sites as $key => $site){
?>
				<option value="<?=$key?>" <?=$siteID==$key? "selected" : ""?>><?=$site['siteName']?></option>
<?
				}
?>
			</select>
			
			<select name="statusID">
				<option value='-1'>כל הסטטוסים</option>
				<option value="1" <?=(0==$statusID)? "selected" : "" ?>>מבוטלת</option>
				<option value="2" <?=(1==$statusID)? "selected" : "" ?>>מאושרת</option>
			</select>
			<div class='dates'>
				<select name="dateType" <?=$dateType? "class='on'" : ""?> onchange='if($(this).val()>0){$(this).addClass("on")}else{$(this).removeClass("on")}'>
					<option value='0'>ללא הגבלת תאריך</option>
					<option value="1" <?=(1==$dateType)? "selected" : "" ?>>תאריך רכישה</option>
					<option value="2" <?=(2==$dateType)? "selected" : "" ?>>תאריך מימוש</option>
				</select>			
				<input style='width:auto' type="text" name="dateFrom" placeholder="מתאריך" class="searchFrom " value="<?=$_GET['dateFrom']?>" readonly="" id="dp<?=time()?>">
				<input style='width:auto' type="text" name="dateUntil" placeholder="עד תאריך" class="searchTo " value="<?=$_GET['dateUntil']?>" readonly="" id="dp<?=time()+1?>">
			
			</div>
			<div style="margin-top:10px">
			<?/*
				חישוב עמלה לפי
				<input style='width:40px' type="text" value="<?=$commission?>" name='commission'>%
				*/?>
				<select name="sourceID" style="margin-right:10px">
					<option value="spaplus" <?=("spaplus"==$sourceID)? "selected" : "" ?>>spaplus</option>
					<option value="online" <?=("online"==$sourceID)? "selected" : "" ?>>online</option>
				</select>
			</div>
			
			<?/*
			<select name="domainID">
				<option value="0" selected="">bizonline</option>	
				<option value="1">Vii</option>	
			</select>
*/?>
			<input type="submit" value="חפש">	
			</form>
			<div class="excel-link">
				<?php 
				
					$ac_link = $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].($_SERVER['QUERY_STRING']?"?".$_SERVER['QUERY_STRING']:"");
					$adding = strpos($ac_link, '?') !== false?'&out=selected':'?out=selected';
					$excel_link = $ac_link.$adding;
				?>
				<a data-fullurl="" style="width: auto;line-height: 120px;" href="//<?=$excel_link?>">ייצוא לאקסל</a>
			</div>
	</div>
	<?//print_r($sites);?>
	<div class="div-table">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>שם המקום</th>
			<th>ת. רכישה</th>
			<th>ת. מימוש</th>
            <th>שם המזמין</th>
            <th>טיפולים</th>
            <th>מייל</th>
            <th>טלפונים</th>
            <th>עלות</th>
<?php
	if($sourceID == "online")
	{
?>
            <th>%</th>
            <th>עמלה</th>
<?php
	}
?>
            <th>מקור</th>
            <th>סטטוס</th>
        </tr>
        </thead>
        <tbody id="sortRow">
<?php
    if (count($oids)){
		$i=0;
        foreach($oids as  $oid => $row) {
            $treat = $treats[$oid] ?? [];
			$click = "onclick=\"openPop(".$row['orderID'].",'".addslashes(htmlspecialchars($row['siteName']))."')\"";
?>
            <tr data-id="<?=$row['orderID']?>" data-site="<?=$row['siteID']?>" <?=($row['status'] ? "" : "style='color:".($row['autoCancel'] ? "#3366ff" : "#ff8888")."'")?>>
                <td><?=$row['orderID']?></td>
                <td><?=$row['siteName']?></td>
                <td ><?=htmlDate(substr($row['create_date'], 0, 10))?></td>
                <td><?=implode('<br />', array_map('htmlDate', explode(',', $treat['dates'])))?></td>
                <td ><?=$row['customerName']?> </td>
                <td ><?=$treat['cnt']?> </td>
                <td ><?=$row['customerEmail']?></td>
                <td ><?=str_replace('~', '<br />', trim($row['customerPhone'] . '~' . $row['customerPhone2'], '~'))?></td>
                <td >₪<?=number_format($row['price'] + $row['extraPrice'])?></td>
<?php
	if($sourceID == "online")
	{
?>                
				<td><?=$row['onlineCommission']?>%</td>
				<td><?=$row['onlineCommission']? "₪".number_format(($row['price'] + $row['extraPrice'])* $row['onlineCommission']/100,2) : ""?></td>
<?php
	}
?>                
				<td ><?=$row['sourceID']?></td>
                <td><?=($row['status'] ? "פעילה" : ($row['autoCancel'] ? "Lead" : "בוטלה"))?></td>
            </tr>
<?php
			$stat = $row['status']?: ($row['autoCancel'] ? 2 : 0);
			$total_cnt ++;
			$totalsites[$row['siteID']] = 1;
			$totalPrice[$stat] += $row['price'] + $row['extraPrice'];
			$totalStatus[$stat]++;
			$totalCommission[$stat]+= ($row['price'] + $row['extraPrice'])* $row['onlineCommission']/100;
		}
			
?>
		<tr class='totals'>
                <th><?=$total_cnt?></th>
                <th><?=count($totalsites)?> בתי ספא</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th>
					<b style="background:#ffff88">₪<?=number_format($totalPrice[1])?></b>
					<span style='color:#3366ff'>₪<?=number_format($totalPrice[2])?></span><br>
					<span style='color:#ff8888'>₪<?=number_format($totalPrice[0])?></span>

				</th>
<?php
	if($sourceID == "online")
	{
?>  
				<th></th>			
                <th>
					<?if($totalCommission){?>
						<b style="background:#ffff88">₪<?=number_format($totalCommission[1])  ?></b>
						<span style='color:#3366ff'>₪<?=number_format($totalCommission[2])  ?></span><br>
						<span style='color:#ff8888'>₪<?=number_format($totalCommission[0])  ?></span>

					<?}?>
				</th>
<?php
	}
?>
                <th ></th>
                <th>
					<b style="background:#ffff88"><?=$totalStatus[1]?> פעילות</b>
					<span style='color:#3366ff'><?=$totalStatus[2]?> לידים</span><br>
					<span style='color:#ff8888'><?=$totalStatus[0]?> מבוטלות</span>

                </th>
            </tr>
<?
			}
?>
        </tbody>
    </table>
</div>

<?if($_GET['tab']){?>
	</div>
</div>
<?}?>

<script>
var pageType="<?=$pageType?>";
function openPop(pageID, siteName){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/frame.dor2.php?siteID='+pageID+'&siteName='+encodeURIComponent(siteName)+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}
</script>

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

$.datetimepicker.setLocale('he');

</script>

<style>
	.manageItems table > thead > tr > th, .manageItems table > thead > tr > td {width:auto !important}
	.dates input{display:none}
	.dates .on ~ input{display:inline-block}
	.manageItems table > thead > tr > th {position: sticky;top: -1px;background: white;z-index: 1;}
	.manageItems table  tr.totals > th {position: sticky;bottom: -1px;background: white;z-index: 1;}
	.manageItems table {overflow: auto;}
	.div-table{max-height:calc(100vh - 180px);overflow:auto}
</style>

<?php

if(!$_GET["tab"]) include_once "../../../bin/footer.php";
