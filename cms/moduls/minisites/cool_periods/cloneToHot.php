<?
include_once "../../../bin/system.php";
include_once "../../../_globalFunction.php";
$result = [];
$pageID = intval($_GET['id']);
$site    = udb::single_row("SELECT * FROM `not_holidays` WHERE `notHolidayID`=".$pageID);

$hData = [
    'holidayName' => $site['notHolidayName'],
    'dateStart' => $site['dateStart'],
    'dateEnd' => $site['dateEnd'],
    'active' => $site['active'],
    'annual' => $site['annual'],
    'allRangeSearch' => 0,
    'allRangeBefore' => 0

];


$sql = "select * from holidays where dateStart='".$hData['dateStart']."' and dateEnd='".$hData['dateEnd']."'";
$exist = udb::single_row($sql);
if($exist) {
    $result['status'] = 'fail';
    $result['message'] = 'תקופה כבר קיימת בתקופות חמות לא ניתן לשכפל';
}
else {
    $pageID = udb::insert('holidays', $hData);
    $result['status'] = 'ok';
    $result['message'] = 'תקופה שוכפלה';
}

echo json_encode($result);