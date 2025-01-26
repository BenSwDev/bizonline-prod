<?
include_once "../../../bin/system.php";
$que = "SELECT `src` FROM `files` WHERE id=".$_POST['picID'];
$fileNAme = udb::single_value($que);
if($fileNAme){
	unlink('../../../../gallery/'.$fileNAme);
}
$que = "DELETE FROM `files` WHERE id=".$_POST['picID'];
udb::query($que);