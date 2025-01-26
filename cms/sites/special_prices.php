<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";
require_once "../classes/class.simpleDate.php";
require_once "../classes/class.PriceCache.php";

$position=3;
$subposition=3;

$siteID=intval($_GET['sID']);
$frameID=intval($_GET['frame']);


$_wdays = array('א','ב','ג','ד','ה','ו','שבת');

$periods = $rooms = $prices = $minDays = array();
$pra = array('weekday' => 'weekday','weekday2' => 'weekday2', 'weekend1' => 'weekend1', 'weekend2' => 'weekend2', 'kids' => 'kids', 'babies' => 'babies');
$pranew = array('type1' => 'type1', 'type2' => 'type2', 'type3' => 'type3', 'type4' => 'type4', 'extraGeneral1' => 'extraGeneral1', 'extraGeneral2' => 'extraGeneral2','extraGeneral3' => 'extraGeneral3','extraGeneral4' => 'extraGeneral4', 'extraAdults1' => 'extraAdults1', 'extraAdults2' => 'extraAdults2', 'extraAdults3' => 'extraAdults3','extraAdults4' => 'extraAdults4','extraKids1' => 'extraKids1', 'extraKids2' => 'extraKids2', 'extraKids3' => 'extraKids3','extraKids4' => 'extraKids4','extraBabies1' => 'extraBabies1', 'extraBabies2' => 'extraBabies2', 'extraBabies3' => 'extraBabies3','extraBabies4' => 'extraBabies4' );

$que = "SELECT `roomID`,`roomName`,'1' as `roomType` FROM `sitesRooms` WHERE `siteID` = ".$siteID." ORDER BY `roomID`";
$sql = mysql_query($que) or report_error(__FILE__,__LINE__,$que);
while($row = mysql_fetch_assoc($sql)){
	$rooms[$row['roomType']][$row['roomID']]["name"] = $row['roomName'];
}
mysql_free_result($sql);

$que = "SELECT '".$siteID."', a.dateFrom, a.dateTo, a.periodID
FROM `sitesPeriods` AS `a` LEFT JOIN `sitesPeriods` AS `b` ON (a.siteID = 0 AND b.siteID = ".$siteID." AND b.dateTo >= a.dateFrom AND b.dateFrom <= a.dateTo)
WHERE a.siteID = 0 AND b.periodID IS NULL";
$sql = mysql_query($que) or report_error(__FILE__,__LINE__,$que);
if (mysql_num_rows($sql)) {
	$que = "INSERT INTO `sitesPeriods`(`siteID`,`dateFrom`,`dateTo`,`baseID`) (
	SELECT '".$siteID."', a.dateFrom, a.dateTo, a.periodID
	FROM `sitesPeriods` AS `a` LEFT JOIN `sitesPeriods` AS `b` ON (a.siteID = 0 AND b.siteID = ".$siteID." AND b.dateTo >= a.dateFrom AND b.dateFrom <= a.dateTo)
	WHERE a.siteID = 0 AND b.periodID IS NULL
	)";
	$sql = mysql_query($que) or report_error(__FILE__,__LINE__,$que);
}

if ('POST' == $_SERVER['REQUEST_METHOD'])
{ 
	$que = "SELECT IF(`basePeriod`,0,`periodID`) as `tmpID`, `periodID` FROM `sitesPeriods` WHERE  basePeriod=0 AND `siteID` = ".$siteID;
	$sql = mysql_query($que) or report_error(__FILE__,__LINE__,$que);
	while(list($p_id, $real_id) = mysql_fetch_row($sql)){
		$que = "DELETE FROM `sitesPrices` WHERE `periodID` = ".$real_id;		// removing old prices
		mysql_query($que) or report_error(__FILE__,__LINE__,$que);

		if (isset($rooms[1]) && is_array($rooms[1])) {
			foreach($rooms[1] as $d => $n){
				$tmp = array();
				$sum = 0;

				foreach($pra as $k => $v){
					if (!is_array($_POST[$k][$p_id][$d])){
						if (strlen($_POST[$k][$p_id][$d]))
							$tmp[] = "`".$v."` = ".floatval($_POST[$k][$p_id][$d]);
							$sum++;
					} 
				}
				if (count($tmp) && $sum) {
					$que = "INSERT INTO `sitesPrices` SET `periodID` = ".$real_id.",`roomID` = ".$d.", ".implode(',',$tmp);
					mysql_query($que) or report_error(__FILE__,__LINE__,$que);
				}
				
				$tmp = array();
				for($p=0; $p<7; $p++)
					$tmp[] = "(".$real_id.",".$d.",".$p.",".intval($_POST['minNights'][$p_id][$d][$p]).")";
					
				$que = "INSERT INTO `sitesMinNights`(`periodID`,`roomID`,`dayCode`,`minNights`) VALUES".implode(',',$tmp)."
							ON DUPLICATE KEY UPDATE `minNights` = VALUES(`minNights`)";
				mysql_query($que) or report_error(__FILE__,__LINE__,$que);
			}
		}

	 $tmp2 = array();
		foreach($pranew as $k => $v){
			if (strlen($_POST[$k][$p_id])){
				$tmp2[] = "`".$v."` = ".floatval($_POST[$k][$p_id]);
			}
		}
		if (count($tmp2)) {
			$que = "UPDATE `sitesPeriods` SET ".implode(',',$tmp2)." WHERE `periodID` = ".$real_id." ";
			mysql_query($que) or report_error(__FILE__,__LINE__,$que);
		}	
				
	}
	
	$que = "OPTIMIZE TABLE `sitesPrices`";
	mysql_query($que) or report_error(__FILE__,__LINE__,$que);
	
	PriceCache::updateTomorrow();
	PriceCache::updateWeekend();

	
}
elseif ($_GET['act']){
	switch($_GET['act']){
		case 'newper':
			$dates = $dd = array();
			$d1 = array($_GET['y_from'], $_GET['m_from'], $_GET['d_from']);
			$d2 = array($_GET['y_to'], $_GET['m_to'], $_GET['d_to']);

			$error['newper'] = period_error(0, 'sitesPeriods', '`siteID` = '.$siteID, $d1, $d2, $dates);
			if (!$error['newper']){
				$que = "INSERT INTO `sitesPeriods`(`siteID`,`dateFrom`,`dateTo`,`periodName`) VALUES(".$siteID.",'".$dates[0]."','".$dates[1]."','".inputStr($_GET['pName'])."')";
				mysql_query($que) or report_error(__FILE__,__LINE__,$que);
				$np = mysql_insert_id();
			}
			unset($dates,$dd,$np,$td,$iday,$wday,$bday,$list,$start);
		break;
		
		case 'delper':
			$tmp = intval($_GET['pid']);
			$que = "DELETE sitesPeriods.*, sitesPrices.*, sitesMinNights.* 
					FROM `sitesPeriods` LEFT JOIN `sitesPrices` USING(`periodID`) 
						LEFT JOIN `sitesMinNights` ON (sitesMinNights.periodID = sitesPeriods.periodID)
					WHERE sitesPeriods.siteID = ".$siteID." AND sitesPeriods.periodID = ".$tmp;
			mysql_query($que) or report_error(__FILE__,__LINE__,$que);
			$que = "OPTIMIZE TABLE `sitesPeriods`, `sitesPrices`, `sitesMinNights`";
			mysql_query($que) or report_error(__FILE__,__LINE__,$que);
		break;
	}
}



$que = "SELECT sitesPrices.*, IF(sitesPeriods.basePeriod,0,sitesPeriods.periodID) as `periodID`, sitesPeriods.dateFrom, sitesPeriods.dateTo, sitesPeriods.periodName, sitesPeriods.type1, sitesPeriods.type2, sitesPeriods.type3,sitesPeriods.type4, sitesPeriods.extraGeneral1, sitesPeriods.extraGeneral2, sitesPeriods.extraGeneral3,sitesPeriods.extraGeneral4,sitesPeriods.extraAdults1, sitesPeriods.extraAdults2, sitesPeriods.extraAdults3 ,sitesPeriods.extraAdults4 ,sitesPeriods.extraKids1, sitesPeriods.extraKids2, sitesPeriods.extraKids3, sitesPeriods.extraKids4, sitesPeriods.extraBabies1, sitesPeriods.extraBabies2, sitesPeriods.extraBabies3, sitesPeriods.extraBabies4
		FROM `sitesPeriods` LEFT JOIN `sitesPrices` USING(`periodID`) 
		WHERE sitesPeriods.siteID = ".$siteID." AND basePeriod=0 
		ORDER BY `dateFrom`";
$sql = mysql_query($que) or die(report_error(__FILE__,__LINE__,$que));
while($row = mysql_fetch_assoc($sql)) {
	$periods[$row['periodID']] = $row;
	
	if ($row['roomID'])
		$prices[$row['periodID']][$row['roomID']] = $row;
}
mysql_free_result($sql);


if (count($periods)){
	$que = "SELECT * FROM `sitesMinNights` WHERE `periodID` IN (".implode(',',array_keys($periods)).")";
	$sql = mysql_query($que) or die(report_error(__FILE__,__LINE__,$que));
	while($row = mysql_fetch_assoc($sql))
		$minDays[$row['periodID']][$row['roomID']][$row['dayCode']] = $row['minNights'];
	mysql_free_result($sql);
}


$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);

$menu = include "site_menu.php";



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
	<div class="manageItems">

	<form id="newper" method="GET">
	<input type="hidden" name="sID" value="<?=$siteID?>">
	<input type="hidden" name="frame" value="<?=$frameID?>">
	<? if ($error['newper']) { ?>
	<div class="errorText"><?=$error['newper']?></div>
	<? } ?>
	<? SimpleDate::GetSimpleDateJS() ?>
	<script type="text/javascript" src="period.js?v=1"></script>
	<script type="text/javascript" src="../app/subsys.js"></script>
	<div class="newPeriod">	
			<div>תקופה חדשה: </div>
			<div>		
				<div style="float:right;"><?=SimpleDate::GetSimpleDate2(1,'from')?></div>
				<div style="float:right;">&nbsp;-&nbsp;</div>
				<div style="float:right;"><?=SimpleDate::GetSimpleDate2(2,'to')?></div>
			</div>
			<div>
				<div style="float:right;padding:0 5px;">שם תקופה: </div>
				<div style="float:right;"><input type="text" name="pName" value=""></div>
				<div style="float:right;padding-right:5px;"><input type="hidden" name="act" value="newper"><input type="submit" value="הוסף" style="border:1px solid #999; width:80px"></div>
			</div>
	</div>
	</form>

	<form method="POST">
	
		<?
		
			if (count($periods) && count($rooms)) {
				foreach($periods as $p_id => $p_row){
					if (!$p_id)
						continue;
		?>
		<div class="errorText"><?=$error[$p_id]?></div>
		<div class="periodBox">
			<div class="periodTitle">תקופה: <span id="pspan<?=$p_id?>"><?=date('d.m.Y',strtotime($p_row['dateFrom']))?> - <?=date('d.m.Y',strtotime($p_row['dateTo']))?> &nbsp;&nbsp;&nbsp; <?=$p_row['periodName']?></span></div>
			<div class="periodButtons">
				<div  onClick="period_edit(<?=$p_id?>,'sites','siteID',0)"><i class="fa fa-pencil" aria-hidden="true"></i></div>
				<div>|</div>
				<div  onClick="if(confirm('You are about to delete period, and all it\'s prices. Continue?')){location.href='?act=delper&pid=<?=$p_id?>&sID=<?=$siteID?>&frame=<?=$frameID?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i></div>
			</div>
		</div>
		<div class="sectionPrices">
			<b style="color:#990000; font-size:14px">יש להזין מחירים ללילה</b>
		
		<?			if (count($rooms[1])) {			?>
		<table cellpadding=0 cellspacing=5 border=0 id="bigtbl<?=$p_id?>" class="btb"  style="width:100%;float:right;display:block;overflow:hidden;">
			<tr valign="top">
				<td class="phead" width="90">שם החדר</td>
				<td class="phead">אמצ"ש</td>
				<td class="phead">אמצ"ש 2 לילות</td>
				<td class="phead">סופ"ש לילה 1</td>
				<td class="phead">סופ"ש 2 לילות</td>
			</tr>
		<?
			foreach($rooms[1] as $rid => $name){
				$tmp_pr = isset($prices[$p_id][$rid]) ? array_map('posValue',$prices[$p_id][$rid]) : array();
		?>
			<tr>
				<td><?=$name["name"]?></td>
				<td><input type="text" name="weekday[<?=$p_id?>][<?=$rid?>]" value="<?=$tmp_pr['weekday']?>" class="std"></td>
				<td><input type="text" name="weekday2[<?=$p_id?>][<?=$rid?>]" value="<?=$tmp_pr['weekday2']?>" class="std"></td>
				<td><input type="text" name="weekend1[<?=$p_id?>][<?=$rid?>]" value="<?=$tmp_pr['weekend1']?>" class="std"></td>
				<td><input type="text" name="weekend2[<?=$p_id?>][<?=$rid?>]" value="<?=$tmp_pr['weekend2']?>" class="std"></td>
			</tr>
				<?php ?>

		<tr>
			<td style="width:100px;display:block;font-size:12px;">ימי כניסה</td>
			<td colspan="4">
		<?php
							foreach($_wdays as $day)
								echo '<div class="checkbox"><span>',$day,'</span></div>';

		?>
			</td>
		</tr>
		<tr>
			<td style="width:100px;display:block;font-size:12px;">מינימום לילות כניסה</td>
			<td colspan="4">
		<?php
			foreach($_wdays as $index => $day){
				isset($minDays[$p_id][$rid][$index]) or $minDays[$p_id][$rid][$index] = 1; ?>
				<div class="checkbox"><select name="minNights[<?=$p_id?>][<?=$rid?>][<?=$index?>]"><?php foreach (range(0, 5) as $number) { ?><option <?=(($minDays[$p_id][$rid][$index] == $number) ? 'selected' : '')?>  value="<?=$number?>" ><?=$number?></option><?php } ?></select></div>
		<?php 
			}
		?>
			</td>
		</tr>
		<?				}			  ?>

		</table>
		</div>
		<div style="display:inline-block;width:320px;margin:0 10px;vertical-align:top;">
		<B style="color:#990000; font-size:14px">סוגי פנסיון</B>

		<table cellpadding=0 cellspacing=5 border=0  class="btb" width="320" style="float:right;display:block;overflow:hidden;">
		<tr style="font-size:13px;">	
				<td class="phead" width="60" style="text-align:right;">שם הפנסיון</td>
				<td class="phead"><p>סטאטוס</p></td>
				<td class="phead"><p>תוספת עלות</p></td>
				<td class="phead"><p>תוספת מבוגר</p></td>
				<td class="phead"><p>תוספת ילד</p></td>	
				<td class="phead"><p>מחיר לתינוק</p></td>
		</tr>
		<tr  style="font-size:12px;">
			<td>לינה בלבד</td>
			<td><select name="type1[<?=$p_id?>]" ><option value="-1">רגיל</option><option value="0" <?=((is_numeric($p_row['type1']) && !$p_row['type1'] )?"selected":"")?>>לא פעיל</option><option value="1" <?=$p_row['type1']=="1"?"selected":""?>>פעיל</option></select></td>
			<td><input type="text" name="extraGeneral1[<?=$p_id?>]" value="<?=$p_row['extraGeneral1']?>" class="std" style="font-size:11px;<?=$p_row['type1']=="0" || $p_row['type1']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraAdults1[<?=$p_id?>]" value="<?=$p_row['extraAdults1']?>" class="std" style="margin:0 2px;font-size:11px;<?=$p_row['type1']=="0" || $p_row['type1']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraKids1[<?=$p_id?>]" value="<?=$p_row['extraKids1']?>" class="std" style="font-size:11px;<?=$p_row['type1']=="0" || $p_row['type1']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraBabies1[<?=$p_id?>]" value="<?=$p_row['extraBabies1']?>" class="std" style="font-size:11px;<?=$p_row['type1']=="0" || $p_row['type1']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
		</tr>
		<tr  style="font-size:12px;">

			<td>ארוחת בוקר</td>
			<td><select name="type2[<?=$p_id?>]" ><option value="-1">רגיל</option><option value="0" <?=((is_numeric($p_row['type2']) && !$p_row['type2'] )?"selected":"")?>>לא פעיל</option><option value="1" <?=$p_row['type2']=="1"?"selected":""?>>פעיל</option></select></td>
			<td><input type="text" name="extraGeneral2[<?=$p_id?>]" value="<?=$p_row['extraGeneral2']?>" class="std" style="font-size:11px;<?=$p_row['type2']=="0" || $p_row['type2']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraAdults2[<?=$p_id?>]" value="<?=$p_row['extraAdults2']?>" class="std" style="margin:0 2px;font-size:11px;<?=$p_row['type2']=="0" || $p_row['type2']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraKids2[<?=$p_id?>]" value="<?=$p_row['extraKids2']?>" class="std" style="font-size:11px;<?=$p_row['type2']=="0" || $p_row['type2']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraBabies2[<?=$p_id?>]" value="<?=$p_row['extraBabies2']?>" class="std" style="font-size:11px;<?=$p_row['type2']=="0" || $p_row['type2']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>

		</tr>
		<tr style="font-size:12px;">

			<td>חצי פנסיון</td>
			<td><select name="type3[<?=$p_id?>]" ><option value="-1">רגיל</option><option value="0" <?=((is_numeric($p_row['type3']) && !$p_row['type3'] )?"selected":"")?>>לא פעיל</option><option value="1" <?=$p_row['type3']=="1"?"selected":""?>>פעיל</option></select></td>
			<td><input type="text" name="extraGeneral3[<?=$p_id?>]" value="<?=$p_row['extraGeneral3']?>" class="std" style="font-size:11px;<?=$p_row['type3']=="0" || $p_row['type3']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraAdults3[<?=$p_id?>]" value="<?=$p_row['extraAdults3']?>" class="std" style="margin:0 2px;font-size:11px;<?=$p_row['type3']=="0" || $p_row['type3']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraKids3[<?=$p_id?>]" value="<?=$p_row['extraKids3']?>" class="std" style="font-size:11px;<?=$p_row['type3']=="0" || $p_row['type3']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraBabies3[<?=$p_id?>]" value="<?=$p_row['extraBabies3']?>" class="std" style="font-size:11px;<?=$p_row['type3']=="0" || $p_row['type3']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
		</tr>
		<tr style="font-size:12px;">

			<td>פנסיון מלא</td>
			<td><select name="type4[<?=$p_id?>]" ><option value="-1">רגיל</option><option value="0" <?=((is_numeric($p_row['type4']) && !$p_row['type4'] )?"selected":"")?>>לא פעיל</option><option value="1" <?=$p_row['type4']=="1"?"selected":""?>>פעיל</option></select></td>
			<td><input type="text" name="extraGeneral4[<?=$p_id?>]" value="<?=$p_row['extraGeneral4']?>" class="std" style="font-size:11px;<?=$p_row['type4']=="0" || $p_row['type4']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraAdults4[<?=$p_id?>]" value="<?=$p_row['extraAdults4']?>" class="std" style="margin:0 2px;font-size:11px;<?=$p_row['type4']=="0" || $p_row['type4']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraKids4[<?=$p_id?>]" value="<?=$p_row['extraKids4']?>" class="std" style="font-size:11px;<?=$p_row['type4']=="0" || $p_row['type4']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
			<td><input type="text" name="extraBabies4[<?=$p_id?>]" value="<?=$p_row['extraBabies4']?>" class="std" style="font-size:11px;<?=$p_row['type4']=="0" || $p_row['type4']=="-1"?"background:#eee;border:1px solid #ddd;":""?>"></td>
		</tr>
		</table>
		</div>
		<?
					}
					
				}
			}
		?>
		
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
	</form>

















	</div>
	
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
<?
function period_error($pid, $table, $where, $from, $to, &$back)
{
	if (!is_array($from) || !is_array($to) || !count($from) || !count($to))
		return "Empty date";
	if (!checkdate($from[1],$from[2],$from[0]) || !checkdate($to[1],$to[2],$to[0]))
		return 'One or more dates are illegal';
	
	$d1 = intval($from[0]).'-'.AddZero(intval($from[1])).'-'.AddZero(intval($from[2]));
	$d2 = intval($to[0]).'-'.AddZero(intval($to[1])).'-'.AddZero(intval($to[2]));
	if (strcmp($d1,$d2) > 0)
		return 'Illegal period. First date must be smaller then second';
	
	$que = "SELECT `dateFrom`,`dateTo` FROM `".$table."` WHERE `dateFrom` <= '".$d2."' AND `dateTo` >= '".$d1."' AND ".$where." AND `periodID` <> ".$pid;
	$sql = mysql_query($que) or report_error(__FILE__,__LINE__,$que);
	if ($row = mysql_fetch_assoc($sql)) 
		return 'Periods intersection. You already have period: '.date('d.m.Y',strtotime($row['dateFrom'])).' - '.date('d.m.Y',strtotime($row['dateTo']));
	
	$back = array($d1,$d2);
	return null;
}

function posValue($val)
{
	return ($val >= 0) ? $val : '';
}
?>
</body>
</html>