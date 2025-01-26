<?php
include_once "../bin/system.php";
include_once "../bin/top.php";

if($_SESSION['permission']!=100){ ?><script>window.location.href="/cms/";</script><?php	exit; }

if($udel = intval($_GET['udel'])){
	udb::query("DELETE users.*, users_access.* FROM `users` LEFT JOIN `users_access` ON (users.id = users_access.userID) WHERE users.id = " . $udel);
	udb::query("OPTIMIZE TABLE `users`, `users_access`");
}

$que   = "SELECT * FROM `users` WHERE 1 ORDER BY `id`";
$users = udb::full_list($que);

$accessLevel = array(100 => 'מנהל ראשי', 50 => 'משתמש רדיל');
?>
<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>ניהול משתמשים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew userRow" id="addNewAcc" value="הוסף חדש" data-id="0" data-title="" tab-id="" />
	</div>
    <table id="userTable" style="max-width:1100px; margin-left:auto; margin-right:auto">
        <thead>
        <tr>
            <th>ID</th>
            <th>שם</th>
			<th>שם משתמש</th>
			<th>פעיל</th>
			<th>רמת גישה</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
<?php
	if($users){
		foreach($users as $user){
?>
            <tr class="userRow" data-id="<?=$user['id']?>" data-title="<?=str_replace('"', '\\"', $user['username'])?>" tab-id="">
                <td><?=$user['id']?></td>
                <td><?=outDb($user['name'])?></td>
				<td><?=outDb($user['username'])?></td>
                <td><?=($user['isActive'] ? "<span style='color:green;'>כן</span>" : "<span style='color:red;'>לא</span>")?></td>
				<td><?=$accessLevel[$user['permission']]?></td>
				<td align="center" class="actb">
					<div class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
				</td>
            </tr>
<?php
		}
	}
?>
        </tbody>
    </table>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="" />
<script>
$(document).ready(function(){
	$('.delete', '#userTable').click(function(){
		if (confirm('אתה בטוח רוצה למחוק משתמש?'))
			window.location.href = '?udel=' + $(this.parentNode.parentNode).data('id');
		return false;
	});

	$('.userRow').click(function(){
		var tabID = $(this).attr("tab-id"), id = $(this).data('id'), title = $(this).data('title');

		if(tabID.length){
			$("#openTab-" + tabID).trigger("click");
		} else {
			++indexTabs;

			$("#theTabs").append('<div class="aTab" style="z-index:200" id="aTab-'+indexTabs+'"><div class="inTabBox"><iframe id="frame'+indexTabs+'" frameborder=0 src="/cms/access/edit.php?frame='+indexTabs+'&uID='+id+'"></iframe><div class="aTabCloser" onclick="closeTab(\''+indexTabs+'\')">x</div><div class="aTabMin" onclick="minTab(\''+indexTabs+'\')">-</div></div></div>');
			$("#leftTabs ul").append('<li id="openTab-'+indexTabs+'" onclick="bringToFront(\''+indexTabs+'\')">'+title+'<span onclick="closeTab(\''+indexTabs+'\')"></span></li>');
			parseInt(id) && $(this).attr("tab-id", indexTabs);
		}
	});
});
</script>

<?php
include_once "../bin/footer.php";
