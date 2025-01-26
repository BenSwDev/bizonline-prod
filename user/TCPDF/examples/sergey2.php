<?php
require_once('../tcpdf_config.php');
require_once('../tcpdf.php');
require_once('../../functions.php');
require_once('../../../cms/bin/functions_and_constants_only!!!.php');

$input = udb::single_row("SELECT * FROM `bookings` WHERE `bookID` = 51");

$people = [['name' => $input['fullName'], 'id' => $input['pid'], 'signature' => SIGN_UPLOAD_PATH . $input['sign1']]];
if ($input['fullName2'] && $input['pid2'])
    $people[] = ['name' => $input['fullName2'], 'id' => $input['pid2'], 'signature' => SIGN_UPLOAD_PATH . $input['sign2']];

$room = udb::single_row("SELECT * FROM `rooms` WHERE `roomID` = 79256");

$room['parking'] = -1;

ob_start();
genContract($room);
$html = ob_get_clean();

genContractPDF('sergey2.pdf', $html, $people, 'I');
