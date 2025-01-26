<?php
require "auth.php";

$gcID = intval($_GET['gcid']);
$type = typemap($_GET['type'] ?? '', 'string');

try {
    $purchase = udb::single_row("SELECT * FROM `gifts_purchases` WHERE `pID` = " . $gcID);

    if (!$purchase || strcmp($purchase['terminal'], 'direct'))
        throw new Exception("Cannot find a purchase for this gift card");
    if (!$_CURRENT_USER->has($purchase['siteID']))
        throw new Exception('Access denied to this purchase');

    $term = Terminal::hasTerminal($purchase['siteID'], 'vouchers');
    if (!$term)
        throw new Exception("Terminal is inactive or missing required data");
//    if (strtoupper($term) != 'CARDCOM')
//        throw new Exception("Terminal " . $term . " is not supported");

    $client = new CardComGeneral($purchase['siteID'], 'vouchers');
    if (!$client)
        throw new Exception("There's no terminal or the terminal is inactive");

    if (!$client->has_invoice)
        throw new Exception('You do not have invoice service');

    if ($type == 'refund'){
        if (!$purchase['refunded'])
            throw new Exception("The purchase wasn't refunded yet");

        $trans = new Transaction($purchase['refunded']);
        if ($trans->transType != 'pay_refund')
            throw new Exception("Wrong transaction type: " . $trans->transType);

        $payData = $trans->result;

        if (!$payData['invoice'])
            throw new Exception("Invoice wan't issued for this transaction");

        $invoice = $payData['invoice'];
    }
    else {
        $payment = udb::single_row("SELECT * FROM `gift_purchase_payments` WHERE `orderID` = " . $gcID . " AND `complete` = 1");
        if (!$payment)
            throw new Exception('Cannot find purchase log for #' . $gcID);

        $payData = json_decode($payment['resultData'], true);

        if (!$payment['invoice'])
            throw new Exception("Invoice wasn't issued yet");

        $invoice = $payment['invoice'];
    }

    if (strtoupper($term) == 'CARDCOM'){
        $result = $client->downloadPrintout($invoice, $payData['invoiceType'] ?? $payData['invoiceData']['invoiceType'] ?? 1);
        if (!$result)
            throw new Exception("Could not get invoice file");

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: binary");
        header("Content-disposition: attachment; filename=\"invoice-" . $invoice . ".pdf\"");

        echo $result;
    }
    else {
        header('Location: ' . YaadPay::$INVOICE_URL . "?" . $invoice);
    }
}
catch (Exception $e){
?>
<html><head></head><body><b style="display:block; text-align:center; color:red"><?=$e->getMessage()?></b></body></html>
<?php
}
