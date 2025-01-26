<?php
require_once "auth.php";

$data = json_decode(gzinflate(base64_decode($_POST['jcsv'])));

if (!$data)
    die('No data received');
if (!$data['fname'])
    die('No file name');

header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: binary");
header("Content-disposition: attachment; filename=\"" . $data['fname'] . "\"");

$xmlFile = fopen('php://output', 'w');

fwrite($xmlFile, "\xEF\xBB\xBF");        // BOM

if ($data['rows'])
    foreach($data['rows'] as $row)
        fputcsv($xmlFile, $row);

fclose($xmlFile);
