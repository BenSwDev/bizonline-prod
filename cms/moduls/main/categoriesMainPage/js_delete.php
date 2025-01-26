<?php
include_once "../../../bin/system.php";

$menuID=intval($_POST['menuID']);

$sql="SELECT `id` FROM search_homepage WHERE id=".$menuID."";
$menu=udb::single_value($sql);

if($menu){
	udb::query("DELETE FROM `search_homepage` WHERE id=".$menuID."");
	udb::query("DELETE FROM `search_homepage_langs` WHERE id=".$menuID."");
   // CacheHomepage::rebuild();
}
