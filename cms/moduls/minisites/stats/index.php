<?php
include_once "../../../bin/system.php";
if($_GET['tab']){
    include_once "../../../bin/top_frame.php";
    include_once "../mainTopTabs.php";
    include_once "innerMenu.php";
}else{
    include_once "../../../bin/top.php";
}
$clearUrl = "index.php";
$addLogOut = "";
include_once "../../../_globalFunction.php";



include_once "inc_stat_main.php";
if(!$_GET["tab"]) include_once "../../../bin/footer.php";
?>

