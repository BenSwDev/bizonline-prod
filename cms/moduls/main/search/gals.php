<?php
define('ACTIVE', "Kew0Rd!Kew0Rd!Kew0Rd!");
header('Content-Type: application/json');
include_once "../../../bin/system.php";

$sql = 'SELECT search.* FROM `search` left join alias_text on (alias_text.table="search" and alias_text.ref=search.id) where  data like "%:[]%" and alias_text.id is null';
$todel = udb::full_list($sql);
foreach ($todel as $del) {

    udb::query("delete from search_langs where id=".$del['id']);
    udb::query("delete from search where id=".$del['id']);
}


