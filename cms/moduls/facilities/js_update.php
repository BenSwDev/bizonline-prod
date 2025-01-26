<?php
include_once "../../bin/system.php";
include_once "../../_globalFunction.php";

$result = new JsonResult(['status' => 99]);

try {
    switch($_POST['act']){
        case 'baseType':
            $aid  = intval($_POST['aid']);
            $type = intval($_POST['val']);

            udb::update('attributes', ['attrType' => $type], "`attrID` = " . $aid);
            break;

        case 'attrType':
            $domainID = DomainList::active();
            if(intval($_POST['domid'])) $domainID = intval($_POST['domid']);
            $aid  = intval($_POST['aid']);
            $val  = intval($_POST['val']);
            $name = typemap($_POST['name'], 'string');

            udb::update('attributes_domains', [$name => $val], "`attrID` = " . $aid . " AND `domainID` = " . $domainID);
            break;

        default:
            throw new Exception('Unknown operation code');
    }

    $result['status'] = 0;
}
catch(Exception $e){
    $result['error'] = $e->getMessage();
}
