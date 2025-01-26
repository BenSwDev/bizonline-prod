<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";


$where ="1 = 1 ";

if($_GET['free']){
	$where .= "AND `name` LIKE '%".$_GET['free']."%' OR lName LIKE '%".$_GET['free']."%' OR email LIKE '%".$_GET['free']."%'";
}

$users = udb::full_list("SELECT * FROM `usersConnection` WHERE " . $where . " LIMIT 300");


$pageNum = intval($_GET["pageNum"])? intval($_GET["pageNum"]) : 1;	
$que="SELECT COUNT(*) as TOTALP FROM `usersConnection` WHERE ".$where;
$totalPages = udb::full_list($que);
$pageTotal = 300;
$totalPages = ceil($totalPages[0]['TOTALP']/$pageTotal);

$que="SELECT * FROM `usersConnection` WHERE ".$where."
LIMIT ".(($pageNum-1)*$pageTotal).",".($pageTotal);
$users=udb::full_list($que);

?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>ניהול משתמשים</h1>
	<?/*
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, 0)">
	</div>
	*/?>
	<div class="searchCms">
		<form method="GET">
			<input type="text" name="free" placeholder="מלל חופשי" value="<?=$_GET['free']?>">
			<a href="/cms/moduls/users/index.php" style="line-height: 40px;">נקה</a>
			<input type="submit" value="חפש">	
		</form>
	</div>
	<div class="numbers">
		<?for($i=1;$i<=$totalPages;$i++){?>
			<input class="pageNum <?=$pageNum==$i? "active" : ""?>" value="<?=$i?>" onclick="window.location.href='?pageNum=<?=$i?><?=($_GET['free']?"&free=".$_GET['free']:"")?>'">
		<?}?>
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>שם מלא</th>
            <th>דוא"ל</th>
			<th>טלפון</th>
			<th>תאריך הרשמה</th>
        </tr>
        </thead>
        <tbody id="sortRow">
<?php
    if (count($users)){
        foreach($users as $user){
?>
		<tr id="<?=$user['ID']?>">
			<td><?=$user['ID']?></td>
			<td onclick="openPop(<?=$user['ID']?>)"><?=outDb($user['name'])?> - <?=outDb($user['lName'])?></td>
			<td onclick="openPop(<?=$user['ID']?>)"><?=outDb($user['email'])?></td>
			<td onclick="openPop(<?=$user['ID']?>)"><?=outDb($user['phone'])?></td>
			<td onclick="openPop(<?=$user['ID']?>)"><?=date("d-m-Y", strtotime($user['insertDate']))?></td>
		</tr>
<?php
		}
			}
?>
        </tbody>
    </table>
</div>
<style type="text/css">
	.manageItems table > tbody > tr > td{text-align: center;}
</style>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script>
function openPop(pageID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/users/frame.php?ID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}


</script>
<?php



include_once "../../bin/footer.php";
?>
