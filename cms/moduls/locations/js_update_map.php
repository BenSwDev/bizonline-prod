<?
include_once "../../bin/system.php";
require_once "../../classes/class.subsys.php";


$name = inputStr($_REQUEST['name']);
$name_eng = inputStr($_REQUEST['name_eng']);
$name_fra = inputStr($_REQUEST['name_fra']);
$id = intval($_REQUEST['id']);
$area = intval($_REQUEST['sarea']);
$eng = inputStr($_REQUEST['eng']);
$rus = inputStr($_REQUEST['rus']);

if (!$id || !$name){
	$_RESULT['status'] = '001';
	die('Incomplete input data. Please check that all fields are filled.');
}
if($area){
$que = "SELECT `TITLE` FROM `".(($_REQUEST['tab'] == 'a') ? 'main_areas' : 'areas')."` WHERE `".(($_REQUEST['tab'] == 'a') ? 'main_areaID' : 'areaID')."` = ".$area;
$sql = udb::single_value($que);
$sql ? $_RESULT['newarea']=$sql:die("Illegal area / region index.");
}

$_RESULT['status'] = '004';

switch($_REQUEST['tab']){
	case 'a':
		$que = "UPDATE `areas` SET `TITLE` = '".$name."', `TITLE_eng` = '".$name_eng."', `TITLE_fra`= '".$name_fra."', `main_areaID` = ".$area." WHERE areas.areaID = ".$id;
	break;
	
	case 's':
		$gps = explode(',',inputStr($_REQUEST['gps']));
		$x = floatval(trim($gps[1]));
		$y = floatval(trim($gps[0]));
		$_RESULT['newgps'] = $y.' , '.$x;
		
		$que = "UPDATE `settlements` SET `TITLE` = '".$name."', `TITLE_eng` = '".$name_eng."', `TITLE_fra`= '".$name_fra."', `areaID` = ".$area.", `lon_x` = ".$x.", `lat_y` = ".$y." WHERE settlements.settlementID = ".$id;
	break;
	
	case 'm':
		$que = "UPDATE `main_areas` SET `TITLE` = '".$name."', `TITLE_eng` = '".$name_eng."', `TITLE_fra`= '".$name_fra."' WHERE `main_areaID` = ".$id;
	break;	

	default:
		$_RESULT['status'] = '003';
		die('Unknown operational index. Please reload the page.');
}
udb::query($que);

$que = "OPTIMIZE TABLE `areas`,`settlements`, `main_areas`";
udb::query($que);

$_RESULT['status'] = 0;
$_RESULT['newname'] = $name;
$_RESULT['neweng'] = $name_eng;
$_RESULT['newfra'] = $name_fra;
//$_RESULT['newrus'] = $rus;
$_RESULT['index'] = $_REQUEST['index'];
echo json_encode($_RESULT, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
