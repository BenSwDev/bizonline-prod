<?php
include_once "../../../bin/system.php";


$result = new JsonResult(['status' => 99]);

try {
    $siteID = intval($_POST['site']);

    $site = udb::single_row("SELECT `moveData` FROM `sites` WHERE `siteType` & 2 AND `siteID` = " . $siteID);
    if (!$site)
        throw new Exception('Cannot find site ' . $siteID);

    $data = $site['moveData'] ? json_decode($site['moveData'], true) : [];

    if (isset($_POST['active']))
        $data['active'] = intval($_POST['active']);

    foreach(['percent', 'package'] as $name){
        if (isset($_POST[$name])){
            if (strlen($_POST[$name]))
                $data[$name] = intval($_POST[$name]);
            else
                unset($data[$name]);
        }
    }

    udb::update('sites', ['moveData' => json_encode($data, JSON_NUMERIC_CHECK)], "`siteID` = " . $siteID);

    $result['status'] = 0;
}
catch (Exception $e){
    $result['error'] = $e->getMessage();
}
