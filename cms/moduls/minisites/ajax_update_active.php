<?php
include_once "../../bin/system.php";
$galID = intval($_POST['id']);

udb::query("update galleries set active = !active WHERE `galleryID`=".$galID);

?>{
    status: "ok"
}