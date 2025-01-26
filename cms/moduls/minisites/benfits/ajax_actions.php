<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 15/05/2022
 * Time: 14:13
 */
include_once "../../../bin/system.php";
include_once "../../../_globalFunction.php";
$result = [];
$result['status'] = 'ok';
try {
    $data = typemap($_POST, [
        'act' => 'string'
    ]);

    switch ($data['act']) {
        case 'get-ul-ink':
            foreach ($_SESSION['tfusa']['user'] as $k=>$item) {
                if(strlen($k) > 7) {
                    $result['link'] = '/user/' . $k . "/";
                    break;
                }
            }
            break;
    }

} catch (Exception $e) {
    $result['status'] = 'fail';
    $result['error'] = $e->getMessage();
}


echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
