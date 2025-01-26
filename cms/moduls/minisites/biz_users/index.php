<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";

$where = ['(s.active >= 0 OR s.siteID IS NULL)'];

if ($freeText = udb::escape_string(trim(filter_var($_GET['free'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW)))){
    $list = ['u.name', 'u.username', 'u.phone', 'u.phone2', 's.siteName'];
    $where[] = "(" . implode(" LIKE '%" . $freeText . "%' OR ", $list) . " LIKE '%" . $freeText . "%')";
}

if(strlen($_GET["active"]) ){
	$active = $_GET["active"] == "true"? 1 : 0;
	$where[] = "u.active = ".$active;
}

$having = "";

if(intval($_GET['siteType'])){
	$having = " HAVING s.siteType & ".intval($_GET['siteType'])." > 0";
}



$busers = udb::key_row("SELECT u.*,s.siteType FROM `biz_users` AS `u` LEFT JOIN `sites_users` USING(`buserID`) LEFT JOIN `sites` AS `s` USING(`siteID`) WHERE " . implode(' AND ', $where) . $having." ORDER BY `buserID`", 'buserID');
$sites  = $busers ? udb::key_value("SELECT u.buserID, GROUP_CONCAT(s.siteName SEPARATOR '<br />') AS `sites` FROM `biz_users` AS `u` LEFT JOIN `sites_users` USING(`buserID`) LEFT JOIN `sites` AS `s` USING(`siteID`) WHERE s.active >= 0 AND u.buserID IN (" . implode(',', array_keys($busers)) . ") GROUP BY u.buserID ORDER BY NULL") : [];

?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>ניהול מנהלי מתחמים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, '')">
	</div>
	<div class="searchCms" style="display:inline-block">
		<form method="GET" style="padding-left:100px">
			<input type="text" name="free" style="width:120px" placeholder="מלל חופשי" value="<?=htmlspecialchars(stripslashes($freeText), ENT_QUOTES | ENT_XHTML, 'UTF-8', false)?>">
			<select name="active">
				<option value="">הכל</option>
				<option value="true" <?=strval($_GET["active"])=="true"? "selected" : ""?>>פעיל</option>
				<option value="false" <?=strval($_GET["active"])=="false"? "selected" : ""?>>לא פעיל</option>
			</select>
			<select name="siteType">
				<option>סוג עסק</option>
				<option <?=$_GET['siteType']==1? "selected" : ""?> value="1">צימר</option>
				<option <?=$_GET['siteType']==2? "selected" : ""?> value="2">ספא</option>
				<option <?=$_GET['siteType']==4? "selected" : ""?> value="4">אירועים</option>
				<option <?=$_GET['siteType']==8? "selected" : ""?> value="8">ח.לשעה</option>
			</select>
			<input type="submit" value="חפש">
		</form>
	</div>
	<div class="tblMobile">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th style="width:15%">שם מלא</th>
            <th style="width:5%">פעיל</th>
			<th style="width:10%">שם משתמש</th>
			<th>טלפון</th>
            <th style="width:20%">דוא"ל</th>
            <th style="width:unset">מתחמים</th>
            <th width="120px"></th>
			<th width="60px"></th>
        </tr>
        </thead>
        <tbody id="sortRow">
<?php
    if (count($busers)){
        foreach($busers as $buser){
            
?>
            <tr id="<?=$buser['buserID']?>">
                <td><?=$buser['buserID']?></td>
                <td onclick="openPop(<?=$buser['buserID']?>,'<?=addslashes(htmlspecialchars($buser['name']))?>')"><?=outDb($buser['name'])?></td>
                <td onclick="openPop(<?=$buser['buserID']?>,'<?=addslashes(htmlspecialchars($buser['name']))?>')"><?=($buser['active']? "כן" : "לא")?></td>
                <td onclick="openPop(<?=$buser['buserID']?>,'<?=addslashes(htmlspecialchars($buser['name']))?>')"><?=outDb($buser['username'])?></td>
                <td onclick="openPop(<?=$buser['buserID']?>,'<?=addslashes(htmlspecialchars($buser['name']))?>')"><?=trim($buser['phone'] . ', ' . $buser['phone2'], ', ')?></td>
                <td onclick="openPop(<?=$buser['buserID']?>,'<?=addslashes(htmlspecialchars($buser['name']))?>')"><?=outDb($buser['email'])?></td>
                <td onclick="openPop(<?=$buser['buserID']?>,'<?=addslashes(htmlspecialchars($buser['name']))?>')"><?=$sites[$buser['buserID']]?></td>
                <td><a target="_blank" class="connectBtn" href="/user/?buserID=<?=$buser['buserID']?>">התחברות</a></td>
				<td><div onclick="window.location.href='?udel=<?=$buser['buserID']?>'" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
            </tr>
<?php
		}
			}
?>
        </tbody>
    </table>
	</div>
</div>
<script src="/user/assets/js/swal.js?v=1"></script>
<script>
function getDel(sid) {
	$('.popup.delete-order input[name="buserID"]').val(sid);
	$('.popup.delete-order').show();
	$('.popup.delete-order input[type="text"]').focus();
}

function openPop(pageID, name){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/biz_users/frame.php?buserID='+pageID+'&name='+encodeURIComponent(name)+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}

$('.orders_num').click(function(){
    var table = $(this).parents('table').eq(0)
    var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
    this.asc = !this.asc
    if (!this.asc){rows = rows.reverse()}
    for (var i = 0; i < rows.length; i++){table.append(rows[i])}
});
function comparer(index) {
    return function(a, b) {
        var valA = getCellValue(a, index), valB = getCellValue(b, index)
        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB)
    }
}
function getCellValue(row, index){ return $(row).children('td').eq(index).text() }


function checkActiveDomain(buserID,domainID,elem){

	var status = elem.checked?1:0;
	$.post("ajax_changeActiveDomain.php",{buserID:buserID,domainID:domainID,status:status});
}
</script>
<?php
include_once "../../../bin/footer.php";
