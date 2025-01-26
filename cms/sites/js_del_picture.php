<?
include_once "../bin/system.php";

$picID = intval($_POST['picID']);

$que="SELECT * FROM `files` WHERE id=".$picID."";
$image= udb::single_row($que);


$filename = "../../gallery/".$image['src'];
if (file_exists($filename)) {
	unlink($filename);
	echo 'File '.$filename.' has been deleted';

} else {
	echo 'Could not delete '.$filename.', file does not exist';
	exit;
}



udb::query("DELETE FROM files WHERE id=".$picID." ");