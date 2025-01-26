<?
include_once "../../bin/system.php";

$picID = intval($_POST['picID']);



/*
$que="SELECT `pictures`.`pictureID`,`files`.`src` 
FROM `pictures` 
INNER JOIN `files` ON `pictures`.`fileID`=`files`.`id`
WHERE pictureID=".$picID."";
$image= udb::single_row($que);



$filename = "../../../gallery/".$image['src'];
if (file_exists($filename)) {
	unlink($filename);
	echo 'File '.$filename.' has been deleted';

} else {
	echo 'Could not delete '.$filename.', file does not exist';
	exit;
}
*/


udb::query("DELETE FROM `pictures` WHERE `pictureID`=".$picID." ");
udb::query("DELETE FROM pictures_langs WHERE `pictureID`=".$picID." ");