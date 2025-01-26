<?php
header("Content-Type: text/css");
$dir=$_GET['dir'];
$fname = filter_var($_GET['fileName'] ?? '', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$fileName = (empty($fname) || !file_exists(__DIR__ . '/' . $fname.".css")) ? "style.css" : $fname.".css";
//echo $_GET['fileName'].PHP_EOL.$fname.PHP_EOL;
//$fileName = "style.css";
$str = file_get_contents($fileName);

$replaced = preg_replace_callback('/(?<=\W)(left|right|rtl|ltr|gotoSearch.png|gotoSearch2.png|redprice-left.png|translateX\(50%)(?=\W)/i', function($m){return array_search($m[0], array('right' => 'left', 'left' => 'right', 'rtl' => 'ltr', 'ltr' => 'rtl', 'ltr/gotoSearch2.png' => 'gotoSearch2.png', 'ltr/gotoSearch.png' => 'gotoSearch.png', 'ltr/redprice-left.png' => 'redprice-left.png', 'translateX(-50%' => 'translateX(50%'));}, $str);
$withSwap = preg_replace('/(margin|padding):\s*([0-9a-z%]+)\s+([0-9a-z%]+)\s+([0-9a-z%]+)\s+([0-9a-z%]+)\s*;/ism', '\\1:\\2 \\5 \\4 \\3;', $replaced);
$withBRad = preg_replace('/border-radius:\s*([0-9a-z%]+)\s+([0-9a-z%]+)\s+([0-9a-z%]+)\s+([0-9a-z%]+)\s*;/ism', 'border-radius:\\2 \\1 \\4 \\3;', $withSwap);

echo ($dir=='ltr')?$withBRad:$str;
