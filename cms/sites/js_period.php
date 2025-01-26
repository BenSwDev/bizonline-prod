<?
include_once "../bin/system.php";
require_once "../classes/class.subsys.php";
require_once "../classes/class.simpleDate.php";


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

$JsHttpRequest =& new JsHttpRequest("UTF-8");

$pid = intval($_REQUEST['pid']);
$pre = str_replace('`','',inputStr($_REQUEST['pre']));
$key = str_replace('`','',inputStr($_REQUEST['key']));
$table = $pre.'Periods';

$_RESULT['status'] = '001';
$_RESULT['id'] = $pid;

switch($_REQUEST['act']){
	case 'show':
		$que = "SELECT `dateFrom`,`dateTo`,`periodName` FROM `".$table."` WHERE `periodID` = ".$pid;
		$sql = mysql_query($que) or die(report_error(__FILE__,__LINE__,$que));
		list($d1,$d2,$pn) = mysql_fetch_row($sql);

		$ind = mt_rand(2,99) * 2;
		$d1 = explode('-',$d1);
		$d2 = explode('-',$d2);
?>
<table cellpadding=0 cellspacing=0 border=0>
	<tr>
		<td><?=SimpleDate::GetSimpleDate($ind,'jsf_'.$pid,$d1[0],$d1[1],$d1[2])?></td>
		<td align="center" width="10">-</td>
		<td><?=SimpleDate::GetSimpleDate($ind+1,'jst_'.$pid,$d2[0],$d2[1],$d2[2])?></td>
		<td align="center" width="10">&nbsp;</td>
		<td><b style="font-size:14px">שם: </b></td>
		<td align="center" width="5">&nbsp;</td>
		<td><input type="text" name="pName" id="pname<?=$ind?>" value="<?=$pn?>"></td>
		<td align="center" width="10">&nbsp;</td>
		<td><input type="button" value="שמור" onClick="period_edit(<?=$pid?>,'<?=$pre?>','<?=$key?>',<?=$ind?>)" style="width:60px"></td>
		<td align="center" width="10">&nbsp;</td>
		<td><input type="button" value="ביטול" onClick="period_reset(<?=$pid?>)" style="width:60px"></td>
	</tr>
</table>
<?
		$_RESULT['save'] = 1;
	break;
	
	case 'update':
		$dates = array();
		$que = "SELECT `".$key."` FROM `".$table."` WHERE `periodID` = ".$pid;
		$sql = mysql_query($que) or die(report_error(__FILE__,__LINE__,$que));
		list($kid) = mysql_fetch_row($sql);
		
		if ($err = period_error($pid, $table, '`'.$key.'` = '.$kid, $_REQUEST['from'], $_REQUEST['to'], $dates)){
			$_RESULT['status'] = '002';
			die($err);
		}
		
		$que = "UPDATE `".$table."` SET `dateFrom` = '".$dates[0]."', `dateTo` = '".$dates[1]."', `periodName` = '".inputStr($_REQUEST['pName'])."' WHERE `periodID` = ".$pid;
		mysql_query($que) or die(report_error(__FILE__,__LINE__,$que));
		
		if ($kid == 0){
			$ids = array(0);
			$que = "SELECT a.siteID
					FROM `sitesPeriods` AS `a` INNER JOIN `sitesPeriods` AS `b`
					WHERE a.siteID <> 0 AND a.baseID <> ".$pid." AND b.periodID = ".$pid." AND b.dateTo >= a.dateFrom AND b.dateFrom <= a.dateTo";
			$sql = mysql_query($que) or die(report_error(__FILE__,__LINE__,$que));
			if (mysql_num_rows($sql))
				while(list($a) = mysql_fetch_row($sql))
					$ids[] = $a;
			
			$que = "UPDATE `".$table."` SET `dateFrom` = '".$dates[0]."', `dateTo` = '".$dates[1]."', `periodName` = '".inputStr($_REQUEST['pName'])."' WHERE `baseID` = ".$pid." AND `siteID` NOT IN (".implode(',',$ids).")";
			mysql_query($que) or die(report_error(__FILE__,__LINE__,$que));
			
			if (count($ids) > 1){
				$que = "UPDATE `".$table."` SET `baseID` = 0 WHERE `siteID` IN (".implode(',',$ids).") AND `baseID` = ".$pid;
				mysql_query($que) or die(report_error(__FILE__,__LINE__,$que));
			}
		}
		
		echo '<b style="font-size:14px">',date('d.m.Y',strtotime($dates[0])),' - ',date('d.m.Y',strtotime($dates[1])),' &nbsp;&nbsp;&nbsp; ',inputStr($_REQUEST['pName']),'</b>';
	break;
	
	default:
		die('Unknown operation code.');
}

$_RESULT['status'] = 0;
