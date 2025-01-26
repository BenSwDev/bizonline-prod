<?php
include_once "../../../bin/system.php";
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

$domainsList = udb::key_row("SELECT * FROM domains","domainID");


$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];

$filter = intval($_GET['filter'] ?? 1);
$siteID = intval($_GET['siteID']);
$domainID = intval($_GET['domainID'] ?? 0);
$statusID = ($_GET['statusID'] ?? 1);
$free = $_GET['free'];


$status = ['cancelled' => 'בוטלה', 'confirmed' => 'אושרה', 'pending' => 'מחכה לאישור', 'error' => 'שגיאה!', 'request' => 'בקשת הזמנה'];
   
  switch($filter){
	  case  1: $cond = "`timeFrom` >= '" . date('Y-m-d') . "'"; $class = 'future'; break;
	  case -1: $cond = "`timeUntil` < '" . date('Y-m-d') . "'"; $class = 'past'; break;
	  default: $cond = '1'; $class = 'all'; break;
  }

$where = "";
if($free) $where.="(sites.siteName LIKE '%".$free."%' OR orders.customerName LIKE '%".$free."%' OR orders.customerPhone LIKE '%".$free."%' OR orders.customerEmail LIKE '%".$free."%' OR orderID = ".intval($free).") AND ";
if($siteID) $where.="siteID = ".$siteID." AND ";

/*
if($statusID){
	$where.= "`orders`.`status` IN ('".$statusID."') AND ";
}
*/
$where = " WHERE ".$where.$cond;

$oids = udb::full_list("SELECT `orders`.*, sites.siteName,`sites_langs`.owners 
						FROM `orders` 
						LEFT JOIN sites USING (siteID) 
						LEFT JOIN `sites_langs` USING (`siteID`)". $where." AND `orders`.`allDay`= 0 AND `orders`.`domainID` = ".$domainID."			   
						GROUP BY orders.orderID
						ORDER BY `orderID` DESC");

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
			<input type="hidden" name="tab" value="<?=$_GET["tab"]?>">
			<input type="hidden" name="siteID" value="<?=$_GET["siteID"]?>">
			<input type="text" name="free" placeholder="מלל חופשי" value="<?=$_GET['free']?>" style="width:auto">
			<?/*
			<select name="filter">
				<option value="1" <?=($filter =="1" ?"selected":"")?>>עתידיות</option>				
				<option value="-1" <?=($filter=="-1"?"selected":"")?> >הזמנות עבר</option>
				<option value="0" <?=($filter=="0"?"selected":"")?>>הכל</option>				
			</select>
			<select name="statusID">
				<option>כל הסטטוסים</option>
					<option value="0" <?=(0==$statusID)? "selected" : "" ?>>מבוטלת</option>
					<option value="1" <?=(1==$statusID)? "selected" : "" ?>>מאושרת</option>
			</select>
			*/?>
			<select name="domainID">
				<option value="0" selected="">bizonline</option>	
				<option <?=$_GET['domainID']==6? "selected" : ""?> value="6">Vii</option>	
			</select>
			<?/*<select name="domainID">
				<?php foreach($domainsList as $dom){ ?>
				<option value="<?=$dom['domainID']?>" <?=($domainID ==$dom['domainID'] ?"selected":"")?>><?=$dom['domainName']?></option>	
				<?php } ?>
			</select> */?>
			<input type="submit" value="חפש">	
		</form>
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>שם המקום</th>
			<th>ת. ביצוע</th>
			<th>ת. הזמנה</th>
            <th>שם המזמין</th>
            <th>הזמנה</th>
            <th>פרטי קשר</th>
            <th>עלות</th>
            <th>סטטוס</th>
			
        </tr>
        </thead>
        <tbody id="sortRow">
<?php
    if (count($oids)){
		$i=0;
        foreach($oids as  $row) { 

			 //$roomsCount = ((count($orderData['rooms'])>1)? count($orderData['rooms'])." יחידות" : "יחידה אחת");
			$adults=$row['adults']; 
			$kids=$row['kids'];
			$babies=$row['babies'];

			 
			 $pplCount = ($adults>1)? $adults." מבוגרים" : "מבוגר אחד";
			 if($kids) $pplCount.=", ".(($kids>1)? $kids." ילדים" : "ילד אחד");
			 if($babies) $pplCount.=", ".(($babies>1)? $babies." תינוקות" : "תינוק אחד");

			
			 $click = "onclick=\"openPop(".$row['orderID'].",'".addslashes(htmlspecialchars($row['siteName']))."')\"";
			 ?>
            <tr id="<?=$row['orderID']?>">
                <td><?=$row['orderID']?></td>
                <td><?=outDb($row['siteName'])?></td>
                <td ><?=htmlDate(substr($row['create_date'], 0, 10))?></td>
                <td style="direction: ltr;text-align: left;"><?=htmlDate(substr($row['timeFrom'], 0, 10))." : ".substr($row['timeFrom'], 11, 5)?> - <?=htmlDate(substr($row['timeUntil'], 0, 10))." : ".substr($row['timeUntil'], 11, 5)?></td>
                <td ><?=outDb($row['customerName'])?> </td>
                <td ><?=$pplCount?> </td>
                <td style="direction: ltr;text-align: left;"><?=outDb($row['customerPhone'])?> <?=outDb($row['customerEmail'])?></td>				
                <td >₪<?=outDb($row['price'] + $row['extraPrice'])?></td>
                <td><?=$row['orderType']=="order"?"הזמנה":"מקדמה"?></td>
            </tr>
<?php
		}
			}
?>
        </tbody>
    </table>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">

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
<style>
	.manageItems table > thead > tr > th, .manageItems table > thead > tr > td {width:auto !important}
</style>

<?php



if(!$_GET["tab"]) include_once "../../../bin/footer.php";
?>

