<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../picUpload.php";

/**
 * @var TfusaBaseUser $_CURRENT_USER
 */

$result = new JsonResult();

$IA = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;

try {
    // may be incorrect - needs verification
    if (is_a($_CURRENT_USER, 'MemberUser'))
        throw new Exception("Access denied to edit products");

    $languages = udb::key_row("SELECT * FROM `language` WHERE 1", 'LangID');

    switch($IA['act']){
        case 'load':
            $extraID = intval($IA['id']);

            if (!$extraID)      // empty ID means new product (shoudn't happen)
                break;

            $extra = udb::single_row("SELECT * FROM `treatmentsExtras` WHERE `extraID` = " . $extraID . " AND `extraType` = 'product'");        // product condition may be temporary
            if (!$extra)
                throw new Exception("Cannot find product " . $extraID);

            $siteData = udb::single_row("SELECT * FROM `sites_treatment_extras` WHERE `extraID` = " . $extraID . " AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");        // product condition may be temporary
            if (!$siteData)
                throw new Exception("Product doesn't belong to your site");

            $data = [
                'extraID' => $extraID,
                'siteID2' => $siteData['siteID'],
                'amount'  => $siteData['price1'],
                'picture' => $extra['extraPic'],
                'title'   => [],
                'desc'    => []
            ];

            foreach($languages as $langID => $langRow){
                $data['title'][$langID] = ($langID == 1) ? $extra['extraName'] : (string) Translation::treatmentsExtras($extraID, 'extraName', $langID);
                $data['desc'][$langID]  = ($langID == 1) ? $siteData['description'] : (string) Translation::sites_treatment_extras($siteData['siteID'] . str_pad($extraID, 5, '0', STR_PAD_LEFT), 'description', $langID);
            }

            $result['extra'] = $data;
            break;


        case 'save':
            $input = typemap($_POST, [
                'siteID2' => 'int',
                'extraID' => 'int',
                'title'   => ['int' => 'string'],
                'amount'  => 'int',
                'desc'    => ['int' => 'text']
            ]);

            if ($input['extraID']){
                $extra = udb::single_row("SELECT * FROM `treatmentsExtras` WHERE `extraID` = " . $input['extraID'] . " AND `extraType` = 'product'");        // product condition may be temporary
                if (!$extra)
                    throw new Exception("Cannot find product " . $input['extraID']);

                $siteData = udb::single_row("SELECT * FROM `sites_treatment_extras` WHERE `extraID` = " . $input['extraID'] . " AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");        // product condition may be temporary
                if (!$siteData)
                    throw new Exception("Product doesn't belong to your site");

                $siteID   = $siteData['siteID'];
                $extraID  = $input['extraID'];
                $extraPic = $extra['extraPic'];

                udb::update('treatmentsExtras', ['extraName' => $input['title'][1]], "`extraID` = " . $extraID);
            }
            else {
                $siteID  = $input['siteID2'];
                if (!$siteID || !$_CURRENT_USER->has($siteID))
                    throw new Exception("Access denied to site #" . $siteID);

                udb::query("LOCK TABLES `treatmentsExtras` WRITE, `sites_treatment_extras` WRITE");

                $maxID    = udb::single_value("SELECT MAX(`extraID`) FROM `treatmentsExtras` WHERE `extraType` = 'product'");
                $extraID  = ($maxID ?: 100000) + 1;
                $extraPic = '';
            }

            $te = [
                'extraID'   => $extraID,
                'extraType' => 'product',
                'extraName' => $input['title'][1]
            ];

            if($_FILES['picpic']) {
                $photo = pictureUpload('picpic', __DIR__ . "/../gallery/");
                if($photo && $photo[0]['file']) {
                    $te['extraPic'] = '/gallery/' . $photo[0]['file'];

                    if ($extraPic)
                        unlink(__DIR__ . "/../" . ltrim($extraPic, '/'));
                }
            }

            udb::insert('treatmentsExtras', $te, true);

            udb::insert('sites_treatment_extras', [
                'siteID'      => $siteID,
                'extraID'     => $extraID,
                'price1'      => $input['amount'],
                'description' => $input['desc'][1]
            ], true);

            udb::query("UNLOCK TABLES");

            $trKey = $siteID . str_pad($extraID, 5, '0', STR_PAD_LEFT);
            foreach($languages as $langID => $langRow){
                if ($langID == 1)
                    continue;

                $input['title'][$langID] ? Translation::save('treatmentsExtras', $extraID, 'extraName', $input['title'][$langID], $langID) : Translation::clear('treatmentsExtras', $extraID, 'extraName', $langID);
                $input['desc'][$langID] ? Translation::save('sites_treatment_extras', $trKey, 'description', $input['desc'][$langID], $langID) : Translation::clear('sites_treatment_extras', $trKey, 'description', $langID);
            }
            break;


        case 'activate':
            $extraID = intval($_POST['id']);
            $status  = intval($_POST['status']);

            $extra = udb::single_row("SELECT * FROM `treatmentsExtras` WHERE `extraID` = " . $extraID . " AND `extraType` = 'product'");        // product condition may be temporary
            if (!$extra)
                throw new Exception("Cannot find product " . $extraID);

            $siteData = udb::single_row("SELECT * FROM `sites_treatment_extras` WHERE `extraID` = " . $extraID . " AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");        // product condition may be temporary
            if (!$siteData)
                throw new Exception("Product doesn't belong to your site");

            udb::update('sites_treatment_extras', ['active' => $status % 2], "`extraID` = " . $extraID . " AND `siteID` = " . $siteData['siteID']);
            break;

        case 'delete':
            $extraID = intval($_POST['id']);

            $extra = udb::single_row("SELECT * FROM `treatmentsExtras` WHERE `extraID` = " . $extraID . " AND `extraType` = 'product'");        // product condition may be temporary
            if (!$extra)
                throw new Exception("Cannot find product " . $extraID);

            $siteData = udb::single_row("SELECT * FROM `sites_treatment_extras` WHERE `extraID` = " . $extraID . " AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");        // product condition may be temporary
            if (!$siteData)
                throw new Exception("Product doesn't belong to your site");


            udb::query("DELETE FROM `sites_treatment_extras` WHERE `siteID` = " . $siteData['siteID'] . " AND `extraID` = " . $extraID);

            foreach($languages as $langID => $langRow)
                Translation::clear('sites_treatment_extras', $extraID, '*', $langID);

            $result['deleted'] = $extraID;
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
