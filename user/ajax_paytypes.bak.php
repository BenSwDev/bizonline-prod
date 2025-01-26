<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 03/11/2021 16:30
 * Updated by Sergey: 12/03/2024 15:10
 */

require_once "auth.php";

if (!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){
    echo blockAccessMsg();
    return;
}

$result = new JsonResult();

try {
    $siteID = $_CURRENT_USER->active_site();

    $canInvoice = udb::single_value("SELECT `masof_invoice` FROM `sites` WHERE `siteID` = " . $siteID);

    udb::query("DELETE FROM `sitePayTypes` WHERE `siteID` = " . $siteID);

    if(isset($_POST['ptypes'])) {
        $types    = typemap($_POST['ptypes'], ['string']);
        $invoices = is_array($_POST['allowIn']) ? typemap($_POST['allowIn'], ['string']) : [];
        $autos    = is_array($_POST['allowAu']) ? typemap($_POST['allowAu'], ['string']) : [];

        // TODO: need to add filter for correct existing pay types

        $que = [];
        foreach ($types as $item)
            $que[] = "('" . udb::escape_string($item) . "', " . $siteID . ", 1, " . (in_array($item, $invoices) ? (in_array($item, $autos) ? 2 : 1) : 0) . ")";

        if ($que)
            udb::query("INSERT INTO `sitePayTypes`(`paytypekey`, `siteID`, `active`, `invoice`) VALUES" . implode(',', $que) . " ON DUPLICATE KEY UPDATE `invoice` = VALUES(`invoice`)");
    }

    // updating invoice settings for "cash"
    udb::insert('sitePayTypes', ['paytypekey' => 'cash', 'siteID' => $siteID, 'active' => 1, 'invoice' => $_POST['autoCash'] ? 2 : 0], true);
}
catch (Exception $e){
    $result['error'] = $e->getMessage();
}
