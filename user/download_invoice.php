<?php
require "auth.php";

$orderID = intval($_GET['orid']);
$payID   = intval($_GET['pid']);
$canceled   = intval($_GET['canceled']);

try {
    if (!$orderID)
        throw new Exception('מספר הזמנה שגוי');

    $siteID = udb::single_value("SELECT `siteID` FROM `orders` WHERE `orderID` = " . $orderID);
    if (!$_CURRENT_USER->has($siteID))
        throw new Exception("Access denied to booking #" . $orderID);

    $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `orderID` = " . $orderID . " AND `lineID` = " . $payID);
    if (!$payment)
        throw new Exception('Cannot find payment for booking #' . $orderID);

    $client = Terminal::bySite($siteID);
    if (!$client)
        throw new Exception("There's no terminal or the terminal is inactive");

	$dataType = $canceled? $payment['cancelData'] : $payment['resultData'];
    
	$payData = json_decode($dataType, true);

	$canInvoice = udb::single_value("SELECT `masof_invoice` FROM `sites` WHERE `siteID` = " . $siteID);

    if (!$canInvoice)
        throw new Exception('You do not have invoice service');
    if (!$payData['invoice'])
        throw new Exception("Invoice wasn't issued yet");

    if ($client->engine() == 'CardCom'){
        $result = $client->downloadPrintout($payData['invoice'], $payData['invoiceType'] ?: $payData['invoiceData']['invoiceType'] ?: 1);
        if (!$result)
            throw new Exception("Could not get invoice file");

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: binary");
        header("Content-disposition: attachment; filename=\"invoice-" .$payData['invoice'] . ".pdf\"");

        echo $result;
    }
    else {
        header('Location: ' . YaadPay::$INVOICE_URL . "?" . $payData['invoice']);
    }
}
catch (Exception $e){
?>
<html><head></head><body><b style="display:block; text-align:center; color:red"><?=$e->getMessage()?></b></body></html>
<?php
}
