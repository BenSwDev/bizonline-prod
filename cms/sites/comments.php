<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$position=5;


$siteID=intval($_GET['sID']);
$frameID=intval($_GET['frame']);
$commentID=intval($_GET['commentID']);



if('POST' == $_SERVER['REQUEST_METHOD']) { 


	$cp=Array();
	$cp['vactype'] = intval($_POST['vactype']);
	$cp['newRate'] = intval($_POST['newRate']);
	$cp['com_title'] = inputStr($_POST['com_title']);
	$cp['com_name'] = inputStr($_POST['com_name']);
	$cp['com_text'] = inputStr($_POST['com_text']);
	$cp['com_mail'] = inputStr($_POST['com_mail']);
	$cp['com_phone'] = inputStr($_POST['com_phone']);
	$cp["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$cp["siteID"] = $siteID;

	$date=explode("/", $_POST['dateHost']);
	$date=$date[2]."-".$date[1]."-".$date[0];
	$cp["dateHost"] = $date;



	if($commentID){
		udb::update("Comments", $cp, "commentID =".$commentID);
	} else {
		$cp["add_date"] = date("Y-m-d");
		$commentID = udb::insert("Comments", $cp);
	}

	?>
	<script>window.location.href='/cms/sites/comments.php?frame=<?=$frameID?>&sID=<?=$siteID?>'</script>
	<?php
	exit;

}


if (intval($_GET['cdel']))
{
	$cdel = intval($_GET['cdel']);
	
	$que = "DELETE FROM `Comments` WHERE `commentID` = ".$cdel;
	mysql_query($que) or report_error(__FILE__,__LINE__,$que);
	
	$que = "OPTIMIZE TABLE `Comments`";
	mysql_query($que) or report_error(__FILE__,__LINE__,$que);
}



$menu = include "site_menu.php";

$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);


$que = "SELECT * FROM `Comments` WHERE `siteID` = ".$siteID." ORDER BY `commentID` DESC";
$comments= udb::full_list($que);

?>
<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
    <h1><?=outDb($site['TITLE'])?></h1>
	<div class="miniTabs">
		<?php foreach($menu as $men){ 
		if($men['position']==$position && $men['sub']){
			$subMenu = $men['sub'];
		}
		?>
		<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><p><?=$men['name']?></p></div>
		<?php } ?>
	</div>
	<?php if($subMenu){ ?>
	<div class="subMenuTabs">
		<?php foreach($subMenu as $sub){ ?>
		<div class="minitab<?=$sub['position']==$subposition?" active":""?>" onclick="window.location.href='<?=$sub['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><?=$sub['name']?></div>
		<?php } ?>
	</div>
	<?php } ?>
<?php if($commentID || intval($_GET['newcom'])==1){ 
$que="SELECT * FROM `Comments` WHERE commentID=".$commentID."";
$comment= udb::single_row($que);

?>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת: </div>
				<input type="text" value="<?=$comment['com_title']?>" name="com_title" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">שם הגולש: </div>
				<input type="text" value="<?=$comment['com_name']?>" name="com_name" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">אימייל: </div>
				<input type="text" value="<?=$comment['com_mail']?>" name="com_mail" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">טלפון: </div>
				<input type="text" value="<?=$comment['com_phone']?>" name="com_phone" class="inpt">
			</div>
		</div>
		
		<div class="section">
			<div class="inptLine">
				<div class="label">תאריך אירוח: </div>
				<input type="text" value="<?=($comment && $comment['dateHost']!="0000-00-00"?date("d/m/Y", strtotime($comment['dateHost'])): date("d/m/Y"))?>" name="dateHost" class="inpt datepicker">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">אופי החופשה: </div>
				<select name="vactype">
					<option value="0">-</option>
					<option value="1" <?=$comment['vactype']==1?"selected":""?>>חופשה רומנטית</option>
					<option value="2" <?=$comment['vactype']==2?"selected":""?>>חופשה משפחתית</option>
					<option value="3" <?=$comment['vactype']==3?"selected":""?>>חופשה קבוצתית</option>
					
				</select>
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">דירוג: </div>
				<select name="newRate">
					<option value="0">-</option>
					<?php foreach (range(1, 5) as $number) { ?>
						<option value="<?=$number?>" <?=($number==$comment['newRate']?"selected":"")?>><?=$number?></option>
					<?php } ?>
				</select>
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">מוצג באתר: </div>
				<div class="chkBox">
					<input type="checkbox" value="1" <?=$comment['ifShow']?"checked":""?> name="ifShow" id="ifShow_<?=$siteID?$siteID:0?>">
					<label for="ifShow_<?=$siteID?$siteID:0?>"></label>
				</div>
			</div>
		</div>		
		<div  style="clear:both;"></div>
		<div class="section txtarea">
			<div class="inptLine">
				<div class="label">דעה: </div>
				<textarea style="height:160px;" name="com_text"><?=$comment['com_text']?></textarea>
			</div>
		</div>

		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$commentID?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
<?php } else { ?>
	<div class="manageItems">
		<div class="addButton" style="margin-top:10px">
			<input type="button" class="addNew" value="הוסף חדש" onclick="window.location.href='/cms/sites/comments.php?frame=<?=$frameID?>&sID=<?=$siteID?>&newcom=1'">
		</div>
		<table border=0 style="border-collapse:collapse" align="center" cellpadding=5 cellspacing=1>
			<tr>
				<th width="30">#</th>
				<th width=110>כותרת</th>
				<th width=110>שם הגולש</th>
				<th>דעה</th>
				<th width=100>תאריך הוספה</th>
				<th width=60>מוצג</th>
			</tr>
		<?php if($comments){
		foreach($comments as $tID => $row) { ?>
			<tr>
				<td align="center" onclick="window.location.href='/cms/sites/comments.php?frame=<?=$frameID?>&sID=<?=$siteID?>&commentID=<?=$row['commentID']?>'"><?=(++$i)?></td>
				<td align="center" onclick="window.location.href='/cms/sites/comments.php?frame=<?=$frameID?>&sID=<?=$siteID?>&commentID=<?=$row['commentID']?>'"><?=$row['com_title']?></td>
				<td align="center" onclick="window.location.href='/cms/sites/comments.php?frame=<?=$frameID?>&sID=<?=$siteID?>&commentID=<?=$row['commentID']?>'"><?=$row['com_name']?></td>
				<td align="center" onclick="window.location.href='/cms/sites/comments.php?frame=<?=$frameID?>&sID=<?=$siteID?>&commentID=<?=$row['commentID']?>'" style="font-size:13px;text-align:right;"><?=($row['com_text'])?></td>
				<td align="center" onclick="window.location.href='/cms/sites/comments.php?frame=<?=$frameID?>&sID=<?=$siteID?>&commentID=<?=$row['commentID']?>'"><?=date("d.m.Y", strtotime($row['add_date']))?></td>
				<td align="center" id="commentStatus_<?=$row['commentID']?>" onclick="changeStatusComment(<?=$row['commentID']?>)"><?=($row['ifShow'] ? '<span style="color:green">כן</span>' : '<span style="color:red">לא</span>')?></td>
			</tr>
		<?php }
		}
		?>
		</table>
	</div>
<?php } ?>
</div>
</section>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div>
    </div>
</div>
<script src="<?=$root;?>/app/jquery-ui.min.js"></script>
<script>

function changeStatusComment(commentID){
	
	$.ajax({
		url: 'js_status_comment.php',
		type: 'POST',
		data: {commentID:commentID},
		async: false,
		success: function (myData) {
			$("#commentStatus_"+commentID+"").html(myData);
		}
	});
}
$(function() {
	$( ".datepicker" ).datepicker({
		
	});
});
</script>
</body>
</html>