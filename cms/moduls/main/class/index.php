<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";
include_once "../../../_globalFunction.php";


$domainID = intval($_GET['domainID']);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);
}

/*
$freeSearch=inDB($_GET['free']);
if($freeSearch){
    $where="title LIKE '%".$freeSearch."%'";
}


$que="SELECT * FROM `search` WHERE ".$where." ORDER BY active DESC, `count` DESC";
$search= udb::full_list($que);
*/

$domainsList = udb::full_list(DomainList::getLoadQuery());

?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>דפי מחלקה</h1>

	<div class="miniTabs general">
		<?php foreach($domainsList as $domain) { ?>
			<div class="tab <?=$_GET['domainID']==$domain['domainID']?'active':''?>  " <?=$_GET['domainID']==$domain['domainID']?'active':''?>  onclick="window.location.href='/cms/moduls/main/class/index.php?domainID=<?=$domain['domainID']?>'"><p><?=$domain['domainName']?></p></div>
		<?php } ?>
	</div>
	<div class="searchCms">
		<form method="GET">
			<input type="text" name="free" placeholder="שם דף">
			<div  class="secParmLine">
				<select name="">
					<option value="0" >אזור</option>
					<option value="1"></option>
				</select>
				<select name="">
					<option value="0" >ישוב</option>
					<option value="1"></option>
				</select>
				<select name="">
					<option value="0">אבזורים</option>
					<option value="1"></option>
				</select>
			</div>

			<a href="/cms/moduls/main/class/index.php">נקה</a>
			<input type="submit" value="חפש">	
		</form>
	</div>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0,<?=$domainID?>)">
	</div>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>כותרת</th>
            <th>כמות כניסות</th>
			<th>מוצג/לא מוצג</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php 
			if($search){
			foreach($search as $page){ ?>
            <tr id="<?=$page['id']?>">
                <td><?=$page['id']?></td>
                <td onclick="openPop(<?=$page['id']?>, <?=$domainID?>)"><?=outDb($page['title'])?></td>
                <td onclick="openPop(<?=$page['id']?>, <?=$domainID?>)"><?=outDb($page['count'])?></td>
                <td><?=($page['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="if(confirm('אתה בטוח רוצה למחוק את הדף?')){ location.href='?type=<?=$pageType?>&delPage=<?=$page['id']?>';  }" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
				<?php } ?>
				</td>
            </tr>
			<?php }
			} ?>
        </tbody>
    </table>
</div>
<script>

function openPop(pageID,domainID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/main/class/frame.php?pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}
</script>

<?php
include_once "../../../bin/footer.php";
?>