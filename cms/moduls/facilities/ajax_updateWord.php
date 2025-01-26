<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 27/01/2022
 * Time: 1:48
 */
include_once "../../bin/system.php";

$id = intval($_POST['id']);
$word = $_POST['word'] ?? null;

udb::query("update attributes set connectionValue='".$word."' where attrID=".$id);