<?php
include_once "../bin/system.php";

header('Content-Type: application/json');

if($_SESSION['permission'] < 100){
    die('{"complete": 0}');
}

$userID  = intval($_GET['uid']);
$newpass = sha1(sha1(sha1($_GET['newpass'])));

$que = "UPDATE `users` SET `password` = '" . $newpass . "' WHERE `id` = " . $userID;
udb::query($que);

echo '{"complete": 1}';
