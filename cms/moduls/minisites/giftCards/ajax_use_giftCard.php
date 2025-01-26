<?php
include_once "../../../bin/system.php";

include_once "../../../_globalFunction.php";

$results = [];
$results['success'] = true;

try{


    $giftcardID = intval($_POST['giftcardID']);
    $pID = intval($_POST['pID']);
    $sumToUse = intval($_POST['sumToUse']);
    $comments = inDb($_POST['comments']);
    $giftCardCommission = udb::single_value("select sites.giftCardCommission from giftCards left join sites on (sites.siteID = giftCards.giftcardID) where giftcardID=".$giftcardID);
    if(!$giftCardCommission) $giftCardCommission = 0; //must have commistion value


//TODO validates

    $cp = [];
    $cp['pID'] = $pID;
    $cp['giftCardID'] = $giftcardID;
    $cp['useageSum'] = $sumToUse;
    $cp['comments'] = $comments;
    $cp['commission'] = $giftCardCommission;
    udb::insert('giftCardsUsage',$cp);


} catch (Exception $e) {

    $results['error'] = true;

}

echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);




