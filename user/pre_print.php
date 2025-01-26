<?php
require_once "auth.php";
require_once('TCPDF/tcpdf_config.php');
require_once('TCPDF/tcpdf.php');

$file = __DIR__ . '/../logs/cont' . mt_rand() . '.pdf';

$order = udb::single_row("SELECT `guid`, `orderID` FROM `orders` WHERE `orderID` = " . intval($_GET['oid']) . " AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");

if (!$order){
    header('HTTP/1.1 403 Forbidden');
    exit;
}

ob_start();
genContract($order, '');
$contract = ob_get_clean();


genContractPDF($file, $contract);


// Process download
if(file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    flush(); // Flush system output buffer
    readfile($file);

    unlink($file);
}
else {
    echo 'Cannot create contract PDF';
}
