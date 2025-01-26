<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";



if('POST' == $_SERVER['REQUEST_METHOD']){
	$name = inputStr($_POST['newsett']);
	$name_eng = inputStr($_POST['neweng']);
	$name_fra = inputStr($_POST['newfra']);
	$newgps = inputStr($_POST['newgps']);
	$mar = intval($_POST['sarea']);

	$que = "SELECT `settlementID` FROM `settlements` WHERE `TITLE` = '".$name."'";
	$sql =  udb::single_row($que);
	if ($sql){
		$error = "Area already exists in database";
	}elseif (!$name){
		$error = "Empty city name";
	}elseif (!$mar){
		$error = "Please choose region.";
	}else {
		$gps = explode(',',$newgps);
		$x = floatval(trim($gps[1]));
		$y = floatval(trim($gps[0]));
		$lat_y=$y;
		$lon_x=$x;
/*
		$que = "INSERT INTO `settlements`(`areaID`,`TITLE`, `lon_x`, `lat_y`) VALUES(".$mar.",'".$name."','".$lat_y."','".$lon_x."')";
		udb::query($que);*/

		$id = udb::insert('settlements', [
			'areaID' => $mar,
			'TITLE'   => $name,
			'lon_x'   => $lat_x,
			'lat_y'   => $lat_y
		], true);

	   udb::insert('settlements_text', [
			'settlementID'   => $id,
			'LangID' => 1,
			'TITLE'   => $name
		], true);
		if($_POST['newarea_eng']){

		   udb::insert('settlements_text', [
				'settlementID'   => $id,
				'LangID' => 2,
				'TITLE'   => $_POST['newarea_eng']
			], true);
		}
		if($_POST['newarea_fra']){
			
			 udb::insert('settlements_text', [
				'settlementID'   => $id,
				'LangID' => 3,
				'TITLE'   => $_POST['newarea_fra']
			], true);

		$que = "OPTIMIZE TABLE `settlements`";
		udb::query($que);

	}
} 

}
elseif ($d = intval($_GET['sdel'])){
	$que = "DELETE FROM `settlements` WHERE settlementID = ".$d;
	udb::query($que);
	$que = "DELETE FROM `settlements_text` WHERE settlementID = ".$d;
	udb::query($que);

	$que = "OPTIMIZE TABLE `settlements`";
	udb::query($que);
}


$areaID = intval($_POST['area']) ? intval($_POST['area']) : intval($_GET['area']);
$free = inputStr($_GET['free']);
$show = intval($_GET['show']);


$areas = $setts = array();

$que = "SELECT `areaID`,`TITLE` FROM `areas` WHERE 1 ORDER BY `areaID`";
$areas= udb::key_row($que, "areaID" );

$que = "SELECT * FROM settlements_text WHERE 1";
$main_areas_langs = udb::key_row($que, array("settlementID","LangID"));

$where = '1';
if ($show) {
	if ($areaID)
		$where .= ' AND `areaID` = '.(($areaID > 0) ? $areaID : 0);
	if ($free)
		$where .= " AND (`TITLE` LIKE '%".$free."%' OR `TITLE_eng` LIKE '%$free%' OR `TITLE_rus` LIKE '%$free%' OR `TITLE_fra` LIKE '%$free%')";
}

	$que = "SELECT * FROM `settlements`	WHERE ".$where." ORDER BY `TITLE`";
	$setts= udb::full_list($que);

?>
<div class="popGallery">
	<div class="popGalleryCont"></div>
</div>
<script type="text/javascript" src="areas.js?v=1.1"></script>
<script type="text/javascript" src="../../app/subsys.js"></script>
<div class="manageItems" id="manageItems">
    <h1>ניהול ישובים</h1>
    <div class="filter">
        <h2>חיפוש עיר: </h2>
        <form method="get">
			<input type="hidden" name="show" value="1">
            <div>
                <input type="search" name="free" <?php if(isset($_GET['free'])){ echo 'value="'.$_GET['free'].'"'; }?> placeholder="טקסט חופשי">
                <select name="area" <?php if(isset($_GET['item'])){ echo 'value="'.$_GET['item'].'"'; }?> placeholder="כל האזורים">
					<option value="0">- כל האזורים -</option>
					<?php
						foreach($areas as $area)
							echo '<option value="',$area['areaID'],'" ',($area['areaID'] == $areaID ? 'selected' : ''),'>',$area['TITLE'],'</option>';
					?>
				</select>
				<input type="submit" value="חפש" class="submit">
            </div>
        </form>
    </div>
	<form method="POST" style="margin:0px" onSubmit="return checkForm('s')">
    <table>
        <thead>
			<input type="hidden" name="free" value="<?=$free?>">
			<input type="hidden" name="area" value="<?=$areaID?>">
				<tr>
					<th width="30">#</th>
					<th>שם ישוב</th>
					<th>שם איזור אנגלית</th>
					<th>שם ישוב ברוסית</th>
					<th>איזור</th>
					<th width="150">GPS</th>
					<th width="110">&nbsp;</th>
				</tr>
        </thead>
        <tbody>
				<tr>
					<th>חדש:</th>
					<td><input type="text" name="newsett" id="newsett" value="" style="width:100%"></td>
					<td><input type="text" class="inptText" name="newarea_eng" id="newarea_eng" value=""></td>
					<td><input type="text" class="inptText" name="newarea_fra" id="newarea_ru" value=""></td>

					<td>
						<select name="sarea" id="sarea">
							<option value="0">בחר...</option>
<?
	foreach($areas as $area)
		echo '<option value="',$area['areaID'],'" ',($area['areaID'] == $areaID ? 'selected' : ''),'>',$area['TITLE'],'</option>';
?>
						</select>
					</td>
					<td><input type="text" name="newgps" id="newgps" value="" style="width:100%; direction:LTR; text-align:right"></td>
					<td align="center"><input type="submit" value="הוסף" style="width:60px"></td>
				</tr>

<?
	$c = 0;
	foreach($setts as $arr){
?>
				<tr id="trs<?=$arr['settlementID']?>">
					<td align="center"><?=(++$c)?></td>
					<td><?=$arr['TITLE']?><img src="/cms/images/textA.gif" border="0" onclick="showText('settlementID=<?=$arr['settlementID']?>',1,'settlements_text')"></td>
					<td><?=$main_areas_langs[$arr['settlementID']][2]['TITLE']?><img src="/cms/images/textA.gif" border="0" onclick="showText('settlementID=<?=$arr['settlementID']?>',2,'settlements_text')"></td>
					<td><?=$main_areas_langs[$arr['settlementID']][3]['TITLE']?><img src="/cms/images/textA.gif" border="0" onclick="showText('settlementID=<?=$arr['settlementID']?>',3,'settlements_text')"></td>

					<td><?=$areas[$arr['areaID']]['TITLE']?></td>
					<td style="direction:LTR; text-align:center"><?=$arr['lat_y']?> , <?=$arr['lon_x']?></td>
					<td align="center" class="actb"><div onClick="tab_edit('s<?=$arr['settlementID']?>')"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;שנה שם</div><div>|</div><div onClick="if(confirm('You are about to delete settlement. Continue?')){location.href='?sdel=<?=$arr['settlementID'].'&show=1'.($areaID ? '&area='.$areaID : '').($free ? '&free='.$free : '')?>'}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
				</tr>
<?  }  ?>
        </tbody>
    </table>
</div>
<?php if($error){ ?>
<script>formAlert("red","","<?=$error?>");</script>
<?php } ?>
<?php
include_once "../../bin/footer.php";
?>



<script type="text/javascript">
	function showText(id,lang,table){
		$(".popGalleryCont").html('<iframe width="100%" height="100%" id="frame_'+lang+'" frameborder=0 src="/cms/moduls/locations/frameText.php?id='+id+'&lang='+lang+'&table='+table+'"></iframe><div class="tabCloserSpace" onclick="tabCloserGlobGal(\'frame_'+lang+'\')">x</div>');
		$(".popGallery").show();
		var elme = window.parent.document.getElementById("frame_"+lang);
	
		elme.style.zIndex="16";
		elme.style.position="relative";
	}

	function tabCloserGlobGal(id){
		$(".popGalleryCont").html('');
		$(".popGallery").hide();
		var elme = window.parent.document.getElementById(id);
		elme.style.zIndex="12";
		elme.style.position ="static";
	}
</script>