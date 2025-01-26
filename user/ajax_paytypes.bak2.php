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

    switch($_POST['act']){
        case 'allow':
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
            break;

        case 'getHts':
            $id = intval($_POST['id'] ?? 0);
            if (!$id)
                throw new Exception("מזהה ספק שגוי");

            $item = (new SiteItemList($siteID, 'hotel_guest_supplier'))->get_items($id);
            if (!$item)
                throw new Exception("לא נמצא סקפ נדרשץ נא לחדש את הדף ולנסות שוב.");

            $result['id']   = $item['itemID'];
            $result['name'] = $item['itemName'];
            break;

        case 'saveHts':
            $input = typemap($_POST, [
                'itemID' => 'int',
                'title'  => 'string'
            ]);

            if (!$input['title'])
                throw new Exception("נא למלא את השם הספק");

            $list = new SiteItemList($siteID, 'hotel_guest_supplier');
            $input['itemID'] ? $list->update_item($input['itemID'], $input['title']) : $list->add_item($input['title']);

            $result['message'] = $input['itemID'] ? "השינוי בוצע בהצלחה" : "הספק נוסף בהצלחה";
            break;

        default:
            throw new Exception("Unknown operation code");
    }

    $result['status'] = 0;
}
catch (Exception $e){
    $result['status'] = 99;
    $result['error']  = $e->getMessage();
}
