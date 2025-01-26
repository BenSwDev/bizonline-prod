<?
include_once "../bin/system.php";
$que = "DELETE FROM `prices` WHERE id=".$_POST['id'];
udb::query($que);