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
    switch($_POST['act']){
        case 'saveSrc':
            $siteID  = $_CURRENT_USER->active_site();

            $input = typemap($_POST, [
                'source' => ['int' => 'int'],
                'showP'  => ['int' => 'int']
            ]);

            udb::query("DELETE FROM `siteArrivalSources` WHERE `siteID` = " . $siteID);

            $sources = SourceList::site_list($siteID, true);

            $que = [];
            foreach($sources as $src){
                $id = $src['id'];

                // saving only records that different from "default" values (why? coz i can!)
                if ($src['active'] != $input['source'][$id] || $src['showPrice'] != $input['showP'][$id])
                    $que[] = "(" . $siteID . ", " . $id . ", " . (empty($input['source'][$id]) ? 0 : 1) . ", " . (empty($input['showP'][$id]) ? 0 : 1) . ")";
            }

            if ($que)
                udb::query("INSERT INTO `siteArrivalSources`(`siteID`, `sourceID`, `active`, `showPrice`) VALUES" . implode(',', $que));

            break;

        default:
            throw new Exception('Unknown action code: ' . typemap($_POST['act'], 'string'));
    }
}
catch (Exception $e){
    $result['error'] = $e->getMessage();
}
