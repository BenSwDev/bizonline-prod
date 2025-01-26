<?php
include_once "auth.php";
include_once "../picUpload.php";
$siteID = intval($_POST['siteID2']);
$settingID = intval($_POST['id']);
$act  = intval($_GET['act']);

$results = [];
$results["success"] = true;
$cp = [];
$formData = typemap($_POST, [
    'title'   => 'string',
    'desc'   => 'text',
    'toptext'   => 'text',
    'meta_desc'   => 'text',
    'small_letters'   => 'text'
    ]);
//print_r($_FILES);
//exit;
foreach ($formData as $k=>$formDatum) {
    $formData[$k] = udb::escape_string($formDatum);
}
try {
    switch($act) {
        case 1:
            $siteID = intval($_GET['siteID2']);
            $results['data'] = udb::single_row("select * from giftCardsSetting where siteID=".$siteID);
            $results["success"] = true;
            break;
        case 0:
            $cp['title'] = $formData['title'];
            $cp['siteID'] = $siteID;
            $cp['updateDate'] = date("Y-m-d H:i");
            $cp['updateManager'] = $_SESSION['name'];
            $cp['siteDescription'] = $formData['desc'];
            $cp['toptext'] = $formData['toptext'];
            $cp['meta_desc'] = $formData['meta_desc'];

            $cp['smallLetters'] = $formData['small_letters'];
            if($_FILES['bgimg']) {
                $photo = pictureUpload('bgimg',"../gallery/");
                if($photo && $photo[0]['file']) {
                    $cp['backgroundImage'] = $photo[0]['file'];
                }
                unset($photo);
            }
            if($_FILES['logo']) {
                $photo = pictureUpload('logo',"../gallery/");
                if($photo && $photo[0]['file']) {
                    $cp['logo'] = $photo[0]['file'];
                }
            }
            if($_POST['removelogo']) {
                $cp['logo'] = '';
            }
            if($_POST['removebgimg']) {
                $cp['bgimg'] = '';
            }

            if($settingID) {
                udb::update("giftCardsSetting",$cp," giftCardsSettingID=".$settingID, false);
            }
            else {
                $cp['addDate'] = date("Y-m-d H:i");
                $cp['addManager'] = $_SESSION['name'];
                udb::insert("giftCardsSetting",$cp);
            }
            break;
    }
} catch (Exception $e) {
    $results["success"] = false;
    $results['msg'] = $e->getMessage();
}
echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
