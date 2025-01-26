<?php
$sid = intval($_GET['sid']);

$title = "רשימת הזמנות";

$que = "SELECT COUNT(contactForm.ID) AS totalContacts,domainID,siteID FROM contactForm WHERE 1";

if(!$_GET['from'])  $_GET['from'] = date('d/m/Y');
if(!$_GET['to'])  $_GET['to'] = date('d/m/Y', strtotime("+1 day"));

if ($sid && in_array($sid, $_CURRENT_USER->sites()))
    $que .= " AND contactForm.siteID = " . $sid;
else
    $que .= " AND contactForm.siteID IN (" . $_CURRENT_USER->sites(true) . ")";

if($_GET['from']){
	$timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'])))),"date");
	$que.=" AND `created` >= '".$timeFrom." 00:00:00'";
}
if($_GET['to']){
	$timeTo = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'])))),"date");
	$que.=" AND `created` <= '".$timeTo." 00:00:00'";

}
$que.=" GROUP BY domainID, siteID";

print_r($que);

$contacts = udb::key_row($que, 'siteID');

print_r($contacts);




?>



<div class="searchOrder">
	<div class="ttl" style="cursor:pointer;margin:-10px;padding:10px" onclick="$('#searchForm').toggleClass('hide');">חפש פניות</div>
	<form method="GET" autocomplete="off" action="" class="hide"  id="searchForm">
		
<?php
    if (count($_CURRENT_USER->sites()) > 1){
        $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
        <div class="inputWrap">
            <select name="sid" id="sid" title="שם מתחם">
                <option value="0">כל המתחמים</option>
<?php
        foreach($sname as $id => $name)
            echo '<option value="' , $id , '" ' , ($id == $sid ? 'selected' : '') , '>' , $name , '</option>';
?>
            </select>
		</div>
<?php
    }
?>
        
		<div class="inputWrap">
			<input type="text" name="from" placeholder="מתאריך" class="searchFrom" value="<?=typemap($_GET['from'], 'string')?>" readonly>
		</div>
		<div class="inputWrap">
			<input type="text" name="to" placeholder="עד לתאריך" value="<?=typemap($_GET['to'], 'string')?>" class="searchTo" readonly>
		</div>
		
		<a class="clear" href="?page=<?=$_GET['page']?>">נקה</a>
		<input type="submit" value="חפש">
		
	</form>	
</div>


<section class="orders">
	<div class="last-orders">
		<div class="title"><?=$title?></div>
		<div class="items">
			<?php foreach($orders as $order) { 
			orderComp($order);
			} ?>
		</div>
	</div>
</section>

