<?php
require_once "auth.php";

$result = new JsonResult();

$IA = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;

try {
    switch($IA['act']){
        case 'clientInfo':
            $input = typemap($IA, [
                'sid' => 'int',
//                'fld' => 'string',
                'val' => 'string'
            ]);

            if (!$input['sid'] || !$_CURRENT_USER->has($input['sid']))
                throw new Exception("Access denied to site #" . $input['sid']);
            if (mb_strlen($input['val'], 'UTF-8') < 3)
                throw new Exception("Search string too short");

            $flist = ['phone' => 'clientMobile', 'name' => 'clientName', 'tZehoot' => 'clientPassport', 'email' => 'clientEmail'];
//            if (!$input['fld'] || !$flist[$input['fld']])
//                throw new Exception("unknown field name - " . $input['fld']);

            $where = implode("` LIKE '%" . udb::escape_string($input['val']) . "%' OR `", $flist);

            $que = "SELECT `clientID`, `clientName` AS `name`, CONCAT(`clientMobile`, ' ') AS `phone`, `clientEmail` AS `email`, `clientPassport` AS `tZehoot`, CONCAT(`clientName`, ' - ', `clientMobile`) AS `_text` 
                    FROM `crm_clients` 
                    WHERE `siteID` = " . $input['sid'] . " AND (`" . $where . "` LIKE '%" . udb::escape_string($input['val']) . "%')";
            $result['clients'] = udb::single_list($que);
            break;

        case 'delete':
            $clientID = intval($_POST['cid']);
            $siteID   = intval($_POST['sid']);

            if (!$siteID || !$_CURRENT_USER->has($siteID))
                throw new Exception("Access denied to site #" . $siteID);

            udb::query("DELETE FROM `crm_clients` WHERE `siteID` = " . $siteID . " AND `clientID` = " . $clientID);
            break;

        default:
            throw new Exception('Unknown action');
    }

    $result['status'] = 0;
}
catch (Exception $e){
    $result['error']  = $e->getMessage();
    $result['status'] = $e->getCode() ?: 99;
}
