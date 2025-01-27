in this form there are 2 options:
1. add new site
2. edit existing site

I need to improve this code according to the following requests:

Adding new site:
1. none of the countries should be checked at start if its for adding new site

2. if none of the countries selected, all of elements inside the form should be disabled. until choosing one.


2. once country selected, it should disappear the other countries options and add instead a restart button. it will restart the form without sending anything and will let the user choose another country. 

3. none of the countries should be checked at start if its for adding new site.

4. Once finished and the user wants to save the new site ->new row in the the localization_site table should be inserted. 

this table helps us to determine the user's country, state, city, currency, and language.

-----------

For editing:
once its for  editing we need to:
1. fetching the localization data of the site and update the checkboxes and locked them without option for changing at all.

2. updating should update the city/state if there is need for that in the localization table


----------------

** instructions**:

do without changing any existing stuff as possible,
make sure it wont cause any error, and that it will work perfect.

if there is something missing for you to implement it perfect, let me know and ill provide it to you.

-----

this is the complete code:

<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "mainTopTabs.php";
include_once "../../_globalFunction.php";
const BASE_LANG_ID = 1;

function send_update_to_spaplus($siteID, $value) {
    $link = curl_init('https://www.spaplus.co.il/bizapi/?key=Kew0Rd!Kew0Rd!Kew0Rd!&action=331&disableAutoConfirm=' . $value . '&siteID=' . $siteID);

    curl_setopt($link, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($link, CURLOPT_FAILONERROR, true);
    curl_setopt($link, CURLOPT_CONNECTTIMEOUT, 20);

    $result = curl_exec($link);
    //file_put_contents("temptest.txt", $result );
    $result = json_decode($result);

    return is_object($result) ? !strcmp($result->status, 'ok') : false;
}

$domainID = Translation::$domain_id = DomainList::active();
$reload = false;

$siteTypes = [1 => 'מתחם', 2 => 'ספא'];
$cleanTime = [1 => '15 דקות', 2 => '30 דקות', 3 => '45 דקות', 4 => 'שעה', 6 => 'שעה וחצי', 8 => 'שעתיים', 12 => '3 שעות', 16 => '4 שעות', 20 => '5 שעות'];
$domains = udb::key_row("SELECT `domainID`, `domainName` ,`domainURL` FROM `domains` WHERE  domainMenu=1", "domainID");
$pageID = intval($_POST['pageID'] ?? $_GET['pageID'] ?? 0);
$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
$siteName = urldecode($_GET['siteName']);

function galGetUrl($uurl){
    $url = $uurl;
    $curlSend = curl_init();

    curl_setopt($curlSend, CURLOPT_URL, $url);
    curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, 1);

    $curlResult = curl_exec($curlSend);
    $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);
    curl_close($curlSend);
    if ($curlStatus === 200)
        return $curlResult;
    else
        return [];

}



$paymentsOpt = [1 => 'מזומן', 2 => 'צ\'ק' , 4 => 'ישראכארד', 8 => 'מאסטרכארד', 16 => 'ויזה' , 32 => 'דיינרס', 64 => 'אמריקן אקספרס'];

$errorMsg = '';

if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {

        $active = $exclusive = 0;
        if ($siteID)
            list($active, $exclusive) = udb::single_row("SELECT `active`,`invoice`,`exclusive` FROM `sites` WHERE `siteID` = " . $siteID, UDB_NUMERIC);

        if($_POST["questions"])
            $_POST["healthQList"] = implode(",",$_POST["questions"]);


        $data = typemap($_POST, [
            'siteName'   => ['int' => 'string'],
            'address'    => ['int' => 'string'],
            'owners'     => ['int' => 'string'],
            'popBg' => 'string',
            'showDatesUpdate' => 'string',
            'showStats' => 'string',
            'bookkeeping' => 'string',
            'bussinessName'     => ['int' => 'string'],
            'phone'      => ['int' => 'string'],
            'phone2'     => ['int' => 'string'],
            'phone3'     => ['int' => 'string'],
            'attr1'      => ['int' => ['int' => 'string']],
            'attr2'      => ['int' => ['int' => 'string']],
            'attr3'      => ['int' => ['int' => 'string']],
            'attr4'      => ['int' => ['int' => 'string']],
            'attr5'      => ['int' => ['int' => 'string']],
            'ServiceLevelAgreement' => ['int' => 'int'],
            'checkedDate' => ['int' => 'date'],
            'checkedBy' => ['int' => 'string'],
            '!active'    => ['int' => 'int'],
            'hideContactMethods'    => ['int' => 'int'],
            '!invoice'    => ['int' => 'int'],
            '!404'    => ['int' => 'int'],
            '!showOnHome'    => ['int' => 'int'],
            '!maskyooActive'    => ['int' => 'int'],
            'shortDesc'  => ['int' => ['int' => 'html']],
            'payopt'    =>  ['int' => 'int'],
            'forCouples'    =>  ['int' => 'int'],
            'seoTitle'   => ['int' => ['int' => 'string']],
            'seoH1'      => ['int' => ['int' => 'string']],
            'seoLink'    => ['int' => ['int' => 'string']],
            'seoKwords'  => ['int' => ['int' => 'string']],
            'seoDesc'    => ['int' => ['int' => 'string']],
            'reviewStarter'    => ['int' => ['int' => 'html']],
            'reviewReport'    => ['int' => ['int' => 'html']],
            'review'    => ['int' => ['int' => 'html']],
            'reviewTitle'    => ['int' => ['int' => 'html']],
            'searchBoxSent'    => ['int' => ['int' => 'string']],
            'reviewLocation'    => ['int' => ['int' => 'html']],
            'minisiteAgree'    => ['int' => ['int' => 'html']],
            'minisiteVAgree'    => ['int' => ['int' => 'html']],
            'minisiteSAgree'    => ['int' => ['int' => 'html']],
            'reviewInPlace'    => ['int' => ['int' => 'html']],
            'reviewGoodToKnow'    => ['int' => ['int' => 'html']],
            'reviewFeeling'    => ['int' => ['int' => 'html']],
            'reviewWeLiked'    => ['int' => ['int' => 'html']],
            'reviewAttentionTo'    => ['int' => ['int' => 'html']],
            'reviewHostsInfo'    => ['int' => ['int' => 'html']],
            'cancellation'    => ['int' => ['int' => 'html']],
            'defaultAgr'    => ['int' => ['int' => 'int']],
            'orderTerms'    => ['int' => ['int' => 'html']],
            'agreement4'    => ['int' => ['int' => 'html']],
            'hostInclude'    => ['int' => ['int' => 'html']],
            'saturday_text'    => ['int' => ['int' => 'string']],
            '!attributes'    => ['int' => 'int'],
            '!attributesisTop'    => ['int' => 'int'],
            '!descToAttr'    => ['int' => 'string'],
            '!orderApproveType' => 'int',
            '!cleanGlobal'     => 'int',
            'activeCal' => 'int',
            'calOnly' => 'int',
            //'portalsID' => 'int',
            //'newsletter' => 'int',
            'spaplusID' => 'int',
            'smsName' => 'string',
            //'zimmersID' => 'int',
            'areas'      => ['int'],
            'video'      => ['string'],
            'compSize' => 'string',
            //'priceMin'       => 'int',
            //'priceMax'       => 'int',
            'reloadgooglemap' => 'int',
            'unitCount'       => 'int',
            '!city'       => 'int',
            '!onlineOrder'   => 'int',
            'masof_type'   => 'string',
            '!masof_active'   => 'int',
            '!masof_number'   => 'string',
            '!masof_key'   => 'string',
            '!masof_pwd'   => 'string',
            '!masof_no_cvv' => 'int',
            '!masof_invoice' => 'int',
            '!masof_swipe' => 'int',
            '!masof_noVAT' => 'int',
            '!masof_cc_data' => 'int',
            '!masof_j5' => 'int',
            '!masof_check_type' => 'int',
            'masof_department' => 'string',
            'masof_doc_type'   => 'int',
            'addDescToInvoice' => 'int',
            'autoInvoice' => 'int',
            'infoInvoiceText' => 'text',
            'downPayment'  => 'string',
            'email'      => 'email',
            'website'   => 'string',
            'facebook'   => 'string',
            'googlePlus' => 'string',
            'youtube1' => 'string',
            'youtube2' => 'string',
            'youtube3' => 'string',
            'gpsLat' => 'string',
            'gpsLong' => 'string',
            'checkInHour' => 'string',
            'checkOutHour' => 'string',
            'checkOutHourSat' => 'string',
            'maskyooPhone' => ['int' => 'string'],
            'phoneSms' => ['int' => 'string'],
            'hostPhrase' => ['int' => 'string'],
            'whatsappPhone' => ['int' => 'string'],
            'orderEditPhone' => ['int' => 'string'],
            'waitingTime'      => ['int' => 'string'],
            'password'  => 'string',
            'userName'  => 'string',
            'bedroomCount'  => ['int' => 'int'],
            'bathroomCount'  => ['int' => 'int'],
            'healthActive' => 'int',
            'healthText1Show' => 'int',
            'healthText2Show' => 'int',
            'healthDefault1' => 'int',
            'healthDefault2' => 'int',
            'healthText1' => 'html',
            'healthText2' => 'html',
            'healthQuestions' => 'int',
            'healthQuestions2' => 'int',
            'healthQList' => 'string',
            'showKidsAndAdults' => ['int' => 'int'],
            'exBits' => ['int'],
            'externalEngine' => 'string',
            'externalID'  => 'string',
            'protelID'  => 'int',
            'siteType' => 'int',
            '!popBizPayOptions' => 'int'
        ]);
        //"SELECT permValue  FROM `bizPopSettings` WHERE `module` LIKE 'bizpop' AND `key1` = ".($siteID)." and permName = 'popBizPayOptions' LIMIT 1"


 //       if (!$data['siteName'][BASE_LANG_ID])
   //         throw new LocalException('חייב להיות שם בעברית');

        // main site data
        $siteData = [
            'active'       => $data['activeCal'] ?? 0,
            'calendarOnly' => $data['calOnly'] ?? 0,
            'invoice'      => $data['invoice'][1] ?? 0,
            '404'       => $data['404'][1] ?? 0,
            'siteName'     => $data['siteName'][BASE_LANG_ID],
            'email'        => $data['email'],
            'bussinessName'        => $data['bussinessName'][BASE_LANG_ID],
            'phone'        => $data['phone'][1],
            'phone2'       => $data['phone2'][1],
            'website'      => $data['website'],
//            'bathroomCount'      => $data['bathroomCount'],
//            'bedroomCount'      => $data['bedroomCount'],
            'facebook'     => $data['facebook'],
            'googlePlus'   => $data['googlePlus'],
            'masof_type'   => $data['masof_type'] ?? '',
            'masof_active' => $data['masof_active'],
//            'masof_no_cvv' => $data['masof_no_cvv'],
            'masof_invoice' => $data['masof_invoice'],
            'addDescToInvoice' => $data['addDescToInvoice'],
            'autoInvoice' => $data['autoInvoice'],
            'masof_swipe' => $data['masof_swipe'],
            'masof_noVAT' => $data['masof_noVAT'],
            'masof_department' => $data['masof_department'],
            'masof_doc_type'   => $data['masof_doc_type'] ?: 1,
            'infoInvoiceText'  => $data['infoInvoiceText'],
            'masof_key'   => $data['masof_key'],
            'masof_pwd'   => $data['masof_pwd'],
            'masof_number'   => $data['masof_number'],
            'youtube1'   => $data['youtube1'],
            'youtube2'   => $data['youtube2'],
            'youtube3'   => $data['youtube3'],
            //'newsletter'   => $data['newsletter'],

            //'priceMin'   => $data['priceMin'],
            //'priceMax'   => $data['priceMax'],
            'unitCount'   => $data['unitCount'] ?: 1,
            'checkInHour'   => $data['checkInHour'],
            'checkOutHour'   => $data['checkOutHour'],
            'checkOutHourSat'   => $data['checkOutHourSat'],
            'maskyooPhone'   => $data['maskyooPhone'][1],
            'phoneSms'   => $data['phoneSms'][1],
            //'hostPhrase'   => $data['hostPhrase'][1],
            'whatsappPhone' => $data['whatsappPhone'][1],
            'orderEditPhone' => $data['orderEditPhone'][1],
            'waitingTime'   => $data['waitingTime'][1],
            'onlineOrder'   => $data['onlineOrder'] ?: 0,
            'orderApproveType' => $data['orderApproveType'] ?: 0,
            'cleanGlobal'      => $data['cleanGlobal'] ?: 2,
            'downPayment' => $data['downPayment'] ,
            'healthActive' => $data['healthActive'] ?: 0,
            'healthText1Show' => $data['healthText1Show'] ?: 0,
            'healthText2Show' => $data['healthText2Show'] ?: 0,
            'healthDefault1' => $data['healthDefault1'] ?: 0,
            'healthDefault2' => $data['healthDefault2'] ?: 0,
            'healthText1' => $data['healthText1'],
            'healthText2' => $data['healthText2'],
            'healthQuestions' => $data['healthQuestions'] ?: 0,
            'healthQuestions2' => $data['healthQuestions2'] ?: 0,
            'healthQList' => $data['healthQList'],
            'showKidsAndAdults' => $data['showKidsAndAdults'][$domainID] ??  0,
            'exBits' => array_sum($data['exBits'] ?? [0]),
            'externalEngine' => $data['externalEngine'],
            'externalID'  => $data['externalID'] ?? '',
            'protelID'  => $data['protelID'] ?? 0,
            'compSize' => $data['compSize']
            //'portalsID' => $data['portalsID'],
            //'zimmersID' => $data['zimmersID'],

        ];

        if ($data['masof_cc_data']) {
            $siteData['masof_no_charge']  = ($data['masof_cc_data'] & 1) ? 0 : 1;
            $siteData['masof_no_cvv']     = ($data['masof_cc_data'] & 2) ? 1 : 0;
            if ($data['masof_j5'])
                $siteData['masof_check_type'] = ($siteData['masof_no_cvv'] && $data['masof_j5']) ? 5 : 2;
            elseif ($data['masof_check_type'] && in_array($data['masof_check_type'], [2,5,10]))
                $siteData['masof_check_type'] = $siteData['masof_no_cvv'] ? $data['masof_check_type'] : 2;
        }
        else
            unset($siteData['masof_no_charge'], $siteData['masof_no_cvv']);

        if($domainID == 1) {
            $siteData['spaplusID'] = $data['spaplusID'];
            $siteData['hostPhrase'] = $data['hostPhrase'][1];
            $siteData['popBg'] = $data['popBg'];
            $siteData['showStats'] = $data['showStats'];
            $siteData['showDatesUpdate'] = $data['showDatesUpdate'];
            $siteData['bookkeeping'] = $data['bookkeeping'];
            $siteData['gpsLat'] = $data['gpsLat'];
            $siteData['gpsLong'] = $data['gpsLong'];
            $siteData['settlementID'] = $data['city'] ?? 0;
            if($data['reloadgooglemap']) {
                $siteData['googlemap'] = '';
            }
            if( (!$data['gpsLat'] || !$data['gpsLong'])) {
                $cityName = udb::single_value("select TITLE from settlements where settlementID=".$siteData['settlementID']);
                $searchAddress =  $data['address'][1] . "," . $cityName;
                $didNotHaveLatLng = true;
                $latlng = getLocationNumbers($searchAddress);
                $siteData['gpsLat'] = $latlng['lat'];
                $siteData['gpsLong'] = $latlng['long'];
                udb::update("sites",$siteData," siteID=".$siteID);
                $didNotHaveLatLng = true;

            }

        }
        if($domainID == 1) {
            $siteData['siteType'] = $data['siteType'] ?: 1;
            if($siteData['siteType'] & 2){
                udb::query("update `bizPopSettings` set permValue=".$data['popBizPayOptions'] . " where  `key1` = ".($siteID)." and permName = 'popBizPayOptions'");
            }
        }

        if ($data['owners'][BASE_LANG_ID]) {
            $siteData['fromName'] = $data['owners'][BASE_LANG_ID];
            $siteData['owners'] = $data['owners'][BASE_LANG_ID];
        }

        if($data['payopt'] && array_sum($data['payopt'])){
            $siteData['paymentOpt'] = array_sum($data['payopt']);
        }

        //save attributes

        $cancelJson = [];
        foreach($_POST['daysCancel'] as $key => $days){
            if($days)
                $cancelJson[$days] = ($_POST['typeCancel'][$key]==1 ? $_POST['costCancel'][$key] : (intval($_POST['costCancel'][$key]) ? ($_POST['costCancel'][$key]/100) : 0 ));
        }

        $siteData['cancelCond'] = json_encode($cancelJson,JSON_NUMERIC_CHECK);

        $photo = pictureUpload('hostsPicture',"../../../gallery/");
        if($photo){
            $siteData["hostsPicture"] = $photo[0]['file'];
        }
        $photo2 = pictureUpload('logoPicture',"../../../gallery/");
        if($photo2){
            $siteData["logoPicture"] =  $photo2[0]['file'];
        }

        if(isset($_POST['logoPictureDel'])){
            $siteData["logoPicture"] = "";
        }

        if (!$siteID){
            if ($data['userName']){
                $exists = udb::single_value("SELECT COUNT(*) FROM `sites` WHERE `userName` = '" . udb::escape_string($data['userName']) . "'" . ($siteID ? " AND `siteID` <> " . $siteID : ''));
                if ($exists)
                    throw new LocalException('שם משתמש זה כבר קיים במערכת');
                //$errorMsg = 'שם משתמש זה כבר קיים במערכת';
                else
                    $siteData['userName'] = $data['userName'];
            }
            else
                throw new LocalException('Please enter username');

            if ($data['password'])
                $siteData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            $siteData['guid'] = GUID();

            // opening new site
            if($domainID != 1) {
                $saveName = $siteData['siteName'];
                unset($siteData['siteName']);
            }
            $siteID = udb::insert('sites', $siteData);
            $siteData['siteName'] = $saveName;
            $reload = true;
            //open new gallery folder
            udb::insert('folder', ['siteID'=>$siteID,'folderTitle'=>$data['siteName'][BASE_LANG_ID],'isMain'=>1]);
            udb::insert('sites_periods', ['siteID'=>$siteID,'periodType'=> 1,'weekend'=> "4,5"]);
            udb::insert('sites_periods', ['siteID'=>$siteID,'periodType'=> 2,'weekend'=> "4,5"]);

            $buser = udb::insert('biz_users', [
                'name'   => $siteData['fromName'],
                'email'  => $siteData['email'],
                'phone'  => $siteData['phone'],
                'phone2' => $siteData['phone2'],
                'username' => $siteData['userName'],
                'password' => $siteData['password']
            ]);

            $que = udb::query("INSERT INTO `sites_health_fields`(`siteID`, `fieldLabel`, `fieldType`, `fieldName`, `fieldClass`, `fieldATTRID`, `ifShow`, `showOrder`) VALUES (".$siteID.", 'שם מלא', 'text', 'name', '', '', 1, 0)");
            $que = udb::query("INSERT INTO `sites_health_fields`(`siteID`, `fieldLabel`, `fieldType`, `fieldName`, `fieldClass`, `fieldATTRID`, `ifShow`, `showOrder`) VALUES (".$siteID.", 'תעודת זהות', 'text', 'tZehoot', '', '', 1, 0)");
            $que = udb::query("INSERT INTO `sites_health_fields`(`siteID`, `fieldLabel`, `fieldType`, `fieldName`, `fieldClass`, `fieldATTRID`, `ifShow`, `showOrder`) VALUES (".$siteID.", 'טלפון', 'tel', 'phone', '', '', 1, 0)");
            $que = udb::query("INSERT INTO `sites_health_fields`(`siteID`, `fieldLabel`, `fieldType`, `fieldName`, `fieldClass`, `fieldATTRID`, `ifShow`, `showOrder`) VALUES (".$siteID.", 'תאריך לידה', 'text', 'fromDate', 'hasDatepicker', '', 1, 0)");
            $que = udb::query("INSERT INTO `sites_health_fields`(`siteID`, `fieldLabel`, `fieldType`, `fieldName`, `fieldClass`, `fieldATTRID`, `ifShow`, `showOrder`) VALUES (".$siteID.", 'אימייל', 'text', 'email', '', '', 1, 0)");
            $que = udb::query("INSERT INTO `sites_health_fields`(`siteID`, `fieldLabel`, `fieldType`, `fieldName`, `fieldClass`, `fieldATTRID`, `ifShow`, `showOrder`) VALUES (".$siteID.", 'כתובת', 'text', 'address', '', 'clientAddress', 1, 0)");

            udb::insert('sites_users', ['buserID' => $buser, 'siteID' => $siteID]);

            if ($siteData['spaplusID'] && isset($siteData['calendarOnly']))
                send_update_to_spaplus($siteData['spaplusID'], $siteData['calendarOnly']);

        } else if($domainID == 1) {
            $oldCal = udb::single_row("SELECT `calendarOnly`, `spaplusID` FROM `sites` WHERE `siteID` = " . $siteID);
            if ($siteData['spaplusID'] &&  !send_update_to_spaplus($oldCal['spaplusID'], $siteData['calendarOnly']))       // if couldn't send new value to spaplus - cancel the change
                $siteData['calendarOnly'] = $oldCal['calendarOnly'];

            udb::update('sites', $siteData, '`siteID` = ' . $siteID);

            $oldSmsName = udb::single_value("SELECT `smsName` FROM `sites` WHERE `siteID` = " . $siteID);
            if ($data['smsName'] && !preg_match('/^[a-z][a-z0-9_-]{2,10}$/i', $data['smsName']))
                $data['smsName'] = $oldSmsName;

            if (strcmp($data['smsName'], $oldSmsName)){     // if name changed
                if ($data['smsName']){
                    $res = Maskyoo::register_name($data['smsName']);
                    if (!strpos($res, '<status>seccess - 1</status>') && !strpos($res, '<status>Error - 308</status>'))     // if not added and doesn't already exists
                        $data['smsName'] = $oldSmsName;
                }

                udb::update('sites', ['smsName' => $data['smsName']], '`siteID` = ' . $siteID);
            }

            TerminalModel::sync_terminals($siteID);
        }


        if($domainID == 1) {
            udb::query('DELETE FROM `sites_areas` WHERE `siteID` = ' . $siteID);
            udb::query('INSERT INTO `sites_areas`(`siteID`, `areaID`) SELECT sites.siteID, settlements.areaID FROM `sites` INNER JOIN `settlements` USING(`settlementID`) WHERE sites.siteID = ' . $siteID);
            /*if(is_array($data['areas']) && count($data['areas'])){
                foreach($data['areas'] as $attr){

                    $siteArea['siteID'] = $siteID;
                    $siteArea['areaID'] = $attr;
                    udb::insert('sites_areas', $siteArea);

                }
            }*/
        }


        //udb::query('DELETE FROM `sites_attributes` WHERE siteID = ' . $siteID . " AND `attrID` NOT IN (" . (count($data['attributes']) ? implode(',', $data['attributes']) : '0') . ")");
        if($domainID == 1) {
            $olda = udb::single_column("SELECT `attrID` FROM `sites_attributes` WHERE `siteID` = " . $siteID);
            if($data['attributes'] && count($data['attributes'])){
                $que = [];
                foreach($data['attributes'] as $attr)
                    $que[] = '(' . $siteID . ', ' . $attr . ',"'.inDb(str_replace('"', "&quot;" ,$data['descToAttr'][$attr])).'",'.intval($data['attributesisTop'][$attr]).')';

                udb::query("INSERT INTO `sites_attributes`(`siteID`, `attrID` , `descToAttr`,`isTop`) VALUES" . implode(',', $que) . " ON DUPLICATE KEY UPDATE `attrID` = VALUES(`attrID`),`descToAttr` = VALUES(`descToAttr`),`isTop` = VALUES(`isTop`)");
                unset($que);
            }

            //$new = array_diff($data['attributes'], $olda);
            if($data['attributes'] && $olda){
                if ($old = array_diff($olda, $data['attributes'])) {
                    udb::query("DELETE FROM `sites_attributes` WHERE `siteID` = " . $siteID . " AND `attrID` IN (" . implode(',', $old) . ")");
                }

            }
            else {
                if(!$data['attributes'] || count($data['attributes']) == 0) {
                    udb::query('DELETE FROM `sites_attributes` WHERE siteID=' . $siteID );
                }
            }
        }


        // saving data per domain
        // foreach(DomainList::get() as $did => $dom){
        $did = $domainID;

        $insSiteDomains = [
            'siteID'   => $siteID,
            'domainID' => $did,
            'active'   => $data['active'][$did] ?? 0,
            'hideContactMethods'   => $data['hideContactMethods'][$did] ?? 0,
            'invoice'   => $data['invoice'][$did] ?? 0,
            '404'   => $data['404'][$did] ?? 0,
            'showOnHome'   => $data['active'][$did] ?? 0,
            'maskyooActive'   => $data['maskyooActive'][$did] ?? 0,
            'phone'    => $data['phone'][$did],
            'phone2'   => $data['phone2'][$did],
            'phone3'   => $data['phone3'][$did],
            'maskyooPhone'   => $data['maskyooPhone'][$did],
            'hostPhrase'   => $data['hostPhrase'][$did],
            'phoneSms' => $data['phoneSms'][$did],
            'whatsappPhone' => $data['whatsappPhone'][$did],
            'orderEditPhone' => $data['orderEditPhone'][$did],
            'waitingTime' => $data['waitingTime'][$did],
            'ServiceLevelAgreement' => $data['ServiceLevelAgreement'][$did],
            'forCouples' => $data['forCouples'][$did],
            'showKidsAndAdults' => $data['showKidsAndAdults'][$did]
        ];
        if($domainID != 1) {
            $insSiteDomains['checkedDate'] = $data['checkedDate'][$did];
            $insSiteDomains['checkedBy'] = $data['checkedBy'][$did];
            $insSiteDomains['hostPhrase'] = $data['hostPhrase'][$did];
            $insSiteDomains['bathroomCount'] = $data['bathroomCount'][$did];
            $insSiteDomains['bedroomCount'] = $data['bedroomCount'][$did];
        }
        $insSiteDomains['lastUpdate'] = date("Y-m-d H:i:s");
        udb::insert('sites_domains', $insSiteDomains, true);


        // saving data per domain / language
        foreach(LangList::get() as $lid => $lang){
            // inserting/updating data in domains table
            $ldata = [
                'siteName'  => $data['siteName'][$lid],
                'bussinessName' => $data['bussinessName'][$lid],
                'owners'    => $data['owners'][$lid],
                'shortDesc' => $data['shortDesc'][$domainID][$lid],
                'agreement4' => $data['agreement4'][$domainID][$lid],
                'agreement3' => $data['orderTerms'][$domainID][$lid],
                'agreement2' => $data['cancellation'][$domainID][$lid],
                'agreement1' => $data['hostInclude'][$domainID][$lid],
                'saturday_text' => $data['saturday_text'][$domainID][$lid],
                'defaultAgr' => $data['defaultAgr'][$domainID][$lid],
                'searchBoxSent' => $data['searchBoxSent'][$domainID][$lid],
                'reviewTitle' => $data['reviewTitle'][$domainID][$lid],
                'review' => $data['review'][$domainID][$lid],
                'reviewReport' => $data['reviewReport'][$domainID][$lid],
                'reviewStarter' => $data['reviewStarter'][$domainID][$lid],
                'reviewInPlace' => $data['reviewInPlace'][$domainID][$lid],
                'reviewGoodToKnow' => $data['reviewGoodToKnow'][$domainID][$lid],
                'reviewFeeling' => $data['reviewFeeling'][$domainID][$lid],
                'reviewWeLiked' => $data['reviewWeLiked'][$domainID][$lid],
                'reviewAttentionTo' => $data['reviewAttentionTo'][$domainID][$lid],
                'reviewHostsInfo' => $data['reviewHostsInfo'][$domainID][$lid]
            ];
            $insData = [
                'siteID'    => $siteID,
                'domainID'  => $did,
                'langID'    => $lid,
                'siteName'  => $data['siteName'][$lid],
                'bussinessName' => $data['bussinessName'][$lid],
                'owners'    => $data['owners'][$lid],
                'shortDesc' => $data['shortDesc'][$did][$lid],
                'agreement4' => $data['agreement4'][$did][$lid],
                'agreement3' => $data['orderTerms'][$did][$lid],
                'agreement2' => $data['cancellation'][$did][$lid],
                'agreement1' => $data['hostInclude'][$did][$lid],
                'saturday_text' => $data['saturday_text'][$did][$lid],
                'defaultAgr' => $data['defaultAgr'][$did][$lid],
                'searchBoxSent' => $data['searchBoxSent'][$did][$lid],
                'reviewTitle' => $data['reviewTitle'][$did][$lid],
                'review' => $data['review'][$did][$lid],
                'reviewReport' => $data['reviewReport'][$did][$lid],
                'reviewStarter' => $data['reviewStarter'][$did][$lid],
                'reviewInPlace' => $data['reviewInPlace'][$did][$lid],
                'reviewGoodToKnow' => $data['reviewGoodToKnow'][$did][$lid],
                'reviewFeeling' => $data['reviewFeeling'][$did][$lid],
                'reviewWeLiked' => $data['reviewWeLiked'][$did][$lid],
                'reviewAttentionTo' => $data['reviewAttentionTo'][$did][$lid],
                'reviewHostsInfo' => $data['reviewHostsInfo'][$did][$lid]
            ];
            if($domainID == 1) {
                $insData['minisiteAgree'] = $data['minisiteAgree'][$did][$lid];
                $insData['minisiteSAgree'] = $data['minisiteSAgree'][$did][$lid];
                $insData['minisiteVAgree'] = $data['minisiteVAgree'][$did][$lid];
                $insData['address'] = $data['address'][$lid];

                $ldata['address'] = $data['address'][$lid];
                $ldata['minisiteAgree'] = $data['minisiteAgree'][$did][$lid];
                $ldata['minisiteSAgree'] = $data['minisiteSAgree'][$did][$lid];
                $ldata['minisiteVAgree'] = $data['minisiteVAgree'][$did][$lid];

            }
            if($domainID != 1) {
                $ldata['attr1'] = $data['attr1'][$did][$lid];
                $ldata['attr2'] = $data['attr2'][$did][$lid];
                $ldata['attr3'] = $data['attr3'][$did][$lid];
                $ldata['attr4'] = $data['attr4'][$did][$lid];
                $ldata['attr5'] = $data['attr5'][$did][$lid];
                $ldata['reviewLocation'] = $data['reviewLocation'][$did][$lid] ? $data['reviewLocation'][$did][$lid] : ' ' . PHP_EOL;


                $insData['attr1'] = $data['attr1'][$did][$lid];
                $insData['attr2'] = $data['attr2'][$did][$lid];
                $insData['attr3'] = $data['attr3'][$did][$lid];
                $insData['attr4'] = $data['attr4'][$did][$lid];
                $insData['attr5'] = $data['attr5'][$did][$lid];

            }
            else {
                if($siteData['siteType'] & 2) {
                    $insData['reviewLocation'] = $data['reviewLocation'][$did][$lid] ? $data['reviewLocation'][$did][$lid] : ' ' . PHP_EOL;
                    $ldata['reviewLocation'] = $data['reviewLocation'][$did][$lid] ? $data['reviewLocation'][$did][$lid] : ' ' . PHP_EOL;
                }

            }

            udb::insert('sites_langs', $insData , true);


            Translation::save_row('sites', $siteID, $ldata, $lid, $domainID);
        }

        // }





        $dataSeo = typemap($_POST, [
            'title'   => ['int' => ['int' => 'string']],
            'h1'   => ['int' => ['int' => 'string']],
            'seoKeyword'   => ['int' => ['int' => 'string']],
            'seoDesc'   => ['int' => ['int' => 'string']],
            'level2'   => ['int' => ['int' => 'string']],

        ]);
        $dataSeo['ref']=$siteID;
        $dataSeo['table']="sites";


        // saving data per domain
        if($domainID != 1) {
            foreach(LangList::get() as $lid => $lang){
                $siteDataSeo = [
                    'domainID'  => $domainID,
                    'langID'    => $lid,
                    'title'  => $dataSeo['title'][$domainID][$lid],
                    'h1'  => $dataSeo['h1'][$domainID][$lid],
                    'description'  => $dataSeo['seoDesc'][$domainID][$lid],
                    'keywords'  => $dataSeo['seoKeyword'][$domainID][$lid],
                    'ref'  => $dataSeo['ref'],
                    'table'  => $dataSeo['table']
                ];


                $siteDataSeo['LEVEL1'] = globalLangSwitch($lid);
                $addText = "";
                $dataSeo['level2'][$domainID][$lid] = str_replace("_"," ",$dataSeo['level2'][$domainID][$lid]);

                if($dataSeo['level2'][$domainID][$lid]) {

                    $siteDataSeo['LEVEL2'] = $dataSeo['level2'][$domainID][$lid]?$dataSeo['level2'][$domainID][$lid].$addText:"".$addText;
                }
                else {
                    $siteDataSeo['LEVEL2'] = $data['siteName'][$lid]?$data['siteName'][$lid].$addText:"".$addText; //.".html" gal removed 01-12-2020
                }



                $que = "SELECT `id` FROM `alias_text` WHERE `ref`=$siteID AND `table`='sites' AND `domainID`=$domainID AND `langID`=$lid" ;
                $checkId = udb::single_value($que);

                if(!$checkId){
                    udb::insert('alias_text', $siteDataSeo);
                }else{
                    udb::update('alias_text', $siteDataSeo, "`id`=$checkId");
                }

            }
        }


        if (!$active && $siteData['active'])
            SearchCache::update_sites($siteID);

        if ($_POST['wu']){
            $wuKeys = typemap($_POST['wu'], ['string' => 'string']);

            $wuClient = new BizWubook;
            $wuKeys ? $wuClient->save_site_keys($siteID, $wuKeys) : $wuClient->delete_keys('site', $siteID);
        }
    }
    catch (LocalException $e){
        // show error
        $errorMsg = $e->getMessage();
    }

    if (!$errorMsg){
        ?>

        <?php
    }
    if($reload ===  true) {
        echo '<script>window.location.href = "https://bizdev.c-ssd.com/cms/moduls/minisites/frame.dor2.php?siteID='.$siteID.'&siteName='.$siteData['siteName'].'&tab=1"; </script>';
    }
}
//$zimmerSites = galGetUrl("https://www.zimmersdaka90.co.il/sitesList.php?jsonActive=1");
//$zimmerSites = json_decode($zimmerSites,true);
//
//$villaSites = galGetUrl("https://www.villadaka90.co.il/api/?key=ssd205033&type=11&from=0");
//$villaSites = json_decode($villaSites,true);
//$villaSites = $villaSites['sites'];
$siteData = $siteDomains = $siteLangs = [];
// $domainID = DomainList::active(1);



$langID   = LangList::active();
$domData  = reset(DomainList::get($domainID));
$areas = udb::key_value("SELECT `areaID`, `TITLE` FROM `areas` WHERE 1 ORDER BY `TITLE`");
$settlements = udb::full_list("SELECT `areaID`, `TITLE`, settlementID FROM `settlements` WHERE 1 ORDER BY `TITLE`");
//$categories = udb::key_row("SELECT * FROM `attributes_categories` WHERE active=1 ORDER BY showOrder" , 'categoryID');
//$attributes = udb::key_list("SELECT * FROM `attributes` WHERE active=1 ORDER BY showOrder" , 'categoryID');
$attrType = udb::single_value("select attrType from domains where domainID=".$domainID);
$defaultAgr = udb::single_value("SELECT `text` FROM `defaultAgr` WHERE `agrName`='agreement1'");

//$categories = udb::key_row("SELECT * FROM `attributes_categories` WHERE `domainID` = 1 ORDER BY `showOrder`", 'categoryID');

//$attributes = udb::key_list("SELECT a.* FROM `attributes` AS `a` LEFT JOIN `attributes_domains` AS `d` ON (a.attrID = d.attrID AND d.domainID = 1)  ORDER BY d.showOrder", 'categoryID');

//$attributes = udb::key_row("select attributes.* FROM attributes_domains left join `attributes` on(attributes_domains.attrID = attributes.attrID) where attributes_domains.domainID=".$domainID,"categoryID");

//$attributes = [];

//foreach ($categories as $category) {
//    $attributes[$category['categoryID']] = udb::full_list("select attributes.* FROM attributes_domains left join `attributes` on(attributes_domains.attrID = attributes.attrID) where attributes_domains.domainID=".$domainID. " and attributes_domains.categoryID=".$category['categoryID']);
//}

//$categories = udb::key_row("SELECT * FROM `attributes_categories` WHERE active=1 ORDER BY showOrder" , 'categoryID');
$categories = udb::key_row("SELECT * FROM `attributes_categories` WHERE active=1 and domainID=6 ORDER BY showOrder" , 'categoryID');
$categories[0] = array('categoryName'=>'ללא קטגוריה' , 'categoryID'=>0);
$all_cats = udb::full_list("SELECT * FROM `attributes_categories` WHERE active=1 ORDER BY domainID, showOrder");
$all_attributes = udb::full_list("SELECT domainID,attrID,categoryID FROM `attributes_domains` WHERE active=1 AND domainID>1  ORDER BY domainID,categoryID, showOrder");
foreach($all_cats as $all_c){
    $all_categories[$all_c['domainID']][] = $all_c;
}

$displayedAttr = [];
if($domainID == 1) {
    $attributes = udb::key_list("SELECT distinct attrID,attributes.defaultName,attributes_domains.categoryID,attributes.attrType FROM `attributes_domains` left join attributes using (attrID) WHERE attributes_domains.active=1 and attributes_domains.domainID=6 ORDER BY attributes_domains.showOrder" , 'categoryID');
    $attributes[0] = udb::single_list("SELECT a.*,d.active as `domainActive` FROM `attributes` AS `a` LEFT JOIN `attributes_domains` AS `d` ON (a.attrID = d.attrID AND d.domainID = " . $domainID . ") WHERE d.attrID IS NULL");

}
else {
    $attributes = udb::key_list("SELECT attributes.defaultName,attributes_domains.categoryID,attributes_domains.active as `domainActive` FROM `attributes_domains` left join attributes using (attrID) WHERE attributes_domains.active=1 and domainID=".$domainID."   ORDER BY attributes_domains.showOrder" , 'categoryID');
}






if ($siteID){

    $siteData    = udb::single_row("SELECT `sites`.* FROM `sites` WHERE  `sites`.`siteID` = " . $siteID);
    $siteData['popBizPayOptions'] = udb::single_value("SELECT permValue  FROM `bizPopSettings` WHERE `module` LIKE 'bizpop' AND `key1` LIKE ".($siteID)." and permName = 'popBizPayOptions' LIMIT 1");
    $siteDomains = udb::key_row("SELECT * FROM `sites_domains` WHERE `siteID` = " . $siteID, 'domainID');
    $siteLangs   = udb::key_row("SELECT * FROM `sites_langs` WHERE `siteID` = " . $siteID, ['domainID', 'langID']);
    $siteAttr = udb::single_column("SELECT attrID FROM `sites_attributes` WHERE `siteID`=".$siteID);
    $siteAttrFull = udb::key_row("SELECT * FROM `sites_attributes` WHERE `siteID`=".$siteID,'attrID');
    $siteAreas = udb::single_column("SELECT areaID FROM `sites_areas` WHERE `siteID`=".$siteID);
    $siteGalleries = udb::full_list("SELECT sites_galleries.*,galleries.domainID,galleries.galleryTitle,galleries.active FROM `sites_galleries`
	LEFT JOIN galleries USING (galleryID) WHERE galleries.domainId=".$domainID." and sites_galleries.`siteID`=".$siteID . " order by sites_galleries.showOrder ASC");
//	foreach ($siteGalleries as $gallery) {
//	    udb::query("update galleries set orderGallery=".$gallery['showOrder']." where galleryID=".$gallery['galleryID'] . " and siteID=".$siteID ." and domainID=".$domainID);
//    }


    $siteMainGalleries = udb::key_list("SELECT site_main_galleries.* , galleries.domainID, galleries.galleryTitle FROM `site_main_galleries`
	LEFT JOIN galleries USING (galleryID)
	WHERE galleries.domainID = ".$domainID." and site_main_galleries.`siteID`=".$siteID ,'domainID');


    //print_r($siteLangs);

    $que = "SELECT * FROM `alias_text` WHERE `ref`=".$siteID." AND `table`='sites'";
    $seo = udb::key_row($que, ['domainID','langID']);

    $wuClient = new BizWubook;
    $wuKeys = $wuClient->get_site_keys($siteID);
}
else {
    $siteAttrFull = $siteAttr = $siteData = $siteDomains = $siteLangs = $siteAreas = $siteGalleries =  [];
}

$que  = "SELECT `index` FROM `searchManager_engines` WHERE 1";
$exEngines = udb::single_column($que);

$que = "SELECT * FROM reviewsWriters WHERE active=1 ORDER BY showOrder";
$reviewsWriters = udb::full_list($que);

$protelSites = udb::key_value("SELECT `id`, `name` FROM `protel_sites` WHERE 1");


$siteCancelCond = json_decode($siteData['cancelCond'] ?: '[]', TRUE);



?>
<link rel="stylesheet" href="../../../user/assets/css/sweetalert2.min.css" />
<script src="../../../user/assets/js/swal.js"></script>
<style>
    .oldnew {
        position: relative;
        width: 50px;
        height: 25px;
        line-height: 22px;
        color: #ffffff;
        font-weight: bold;
        background: #2FC2EB;
        font-size: 13px;
        cursor: pointer;
        box-shadow: none;
        -moz-transition: all 0.25s;
        -webkit-transition: all 0.25s;
        transition: all 0.25s;
        text-align: center;
        display: inline-block;
    }
    .oldnew.active {
        border:1px solid #0a0a0a;
    }
    div#whichDomain {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255,255,255,0.8);
        text-align: center;
        padding: 150px 0;
        font-size: 25px;
    }
    #whichDomain h1 {
        font-size: 20px;
        color: red;
    }
</style>
<div class="editItems">
    <div class="popGallery">
        <div class="popGalleryCont"></div>
    </div>
    <div class="siteMainTitle"><?=($siteData['siteName']?$siteData['siteName']:"הוספת מתחם חדש")?>
    </div>
    <?php minisite_domainTabs($domainID,"2")?>
    <?=showTopTabs("2")?>
    <div class="inputLblWrap langsdom domainsHide">
        <div class="labelTo">דומיין</div>
        <?=DomainList::html_select()?>
    </div>
    <div class="inputLblWrap langsdom">
        <div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
    </div>

    <div class="frameContent"><div id="whichDomain">
            <h1>שימו לב אתם עורכים את הדומיין <?=$domains[$domainID]['domainName']?> לעריכת דומיין אחר השתמשו בטאסים העליונים</h1>
        </div>


        <?php
        // --- NEW CODE: gather localization data from DB into a JSON object ---
        $localRows = udb::full_list("SELECT * FROM `localization` ORDER BY id");
        $localData = [];
        foreach ($localRows as $row) {
            $cid   = $row['country_id']; // e.g. "US" or "CY"
            $state = trim($row['state_name'] ?: '');
            $city  = trim($row['city_name']  ?: '');

            // If the country has states, store them under stateName => [cities...]
            // If the country has no states (like Cyprus), store its cities in "_cities".
            if (! isset($localData[$cid])) {
                $localData[$cid] = [];
            }
            if ($state) {
                if (! isset($localData[$cid][$state])) {
                    $localData[$cid][$state] = [];
                }
                if ($city) {
                    $localData[$cid][$state][] = $city;
                }
            } else {
                // No "state_name" => put city in special "_cities"
                if (! isset($localData[$cid]['_cities'])) {
                    $localData[$cid]['_cities'] = [];
                }
                if ($city) {
                    $localData[$cid]['_cities'][] = $city;
                }
            }
        }
        ?>
        <script>
            // Make localization data available to JavaScript:
            var locData = <?= json_encode($localData, JSON_UNESCAPED_UNICODE) ?>;
            // Example structure:
            // {
            //    "US": { "Florida": ["Miami","Orlando"] },
            //    "CY": { "_cities": ["Limassol","Nicosia"] }
            // }
        </script>
        <form method="post" enctype="multipart/form-data" >

            <!-- "מתחם בישראל" switch at the top -->
            <div class="inputLblWrap">
                <div class="switchTtl">מדינת המתחם</div>

                <div class="radioWrap">
                    <input type="radio" name="domainCountry" value="IL" id="domainIsrael" checked>
                    <label for="domainIsrael">ישראל</label>
                </div>

                <div class="radioWrap">
                    <input type="radio" name="domainCountry" value="US" id="domainUSA">
                    <label for="domainUSA">ארצות הברית</label>
                </div>

                <div class="radioWrap">
                    <input type="radio" name="domainCountry" value="CY" id="domainCyprus">
                    <label for="domainCyprus">קפריסין</label>
                </div>
            </div>

            <div id="israelFormContainer">
                <div class="inputLblWrap" style="float: left;">
                    <a id="copyData">שכפל גלריות לדומיין אחר</a>
                </div>
                <?if($domainID != 1 && $siteID) {?>
                    <div class="inputLblWrap" style="float: left">
                    <?
                    $linkToSite = "https://" . $domains[$domainID]['domainURL'] .  ActivePage::showAlias('sites', $siteID , 1 , $domainID) ;
                    $linkToSite = str_replace( "+", "_" , $linkToSite);
                    ?>
                    <a href="<?=$linkToSite?>" target="_blank">קישור לאתר</a>
                    </div><?}?>
                <input type="hidden" name="domid" value="<?=$domainID?>">
                <div class="inputLblWrap">
                    <div class="switchTtl">פעיל יומן</div>
                    <label class="switch">
                        <input type="checkbox" name="activeCal" value="1" <?=($siteData['active']==1 || !$siteID)?"checked":""?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="inputLblWrap">
                    <div class="switchTtl">רק יומן</div>
                    <label class="switch">
                        <input type="checkbox" name="calOnly" value="1" <?=($siteData['calendarOnly'] ? "checked" : "")?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="mainSectionWrapper">
                    <div class="sectionName">כללי</div>
                    <?php

                    foreach(LangList::get() as $id => $lang){
                        if($siteID){
                            $trans[$id] = ($domainID == 1 && $id == 1) ? $siteData : Translation::sites($siteID, '*', $id, $domainID);
                            $btr[$id] = Translation::sites($siteID, '*', $id, Translation::DEFAULT_DOMAIN);
                        }
                        else {
                            $trans[$id] = [];
                            $btr[$id] = [];
                        }

                        ?>
                        <div class="language" data-id="<?=$id?>">
                            <div class="inputLblWrap">
                                <div class="labelTo">שם המתחם</div>
                                <input type="text" name="siteName" placeholder="<?=js_safe($btr[$id]['siteName'] ?? $siteData['siteName'] ?? 'שם המתחם')?>" value="<?=$trans[$id]['siteName'] ? js_safe($trans[$id]['siteName']) : js_safe($siteData['siteName']);?>" />
                            </div>


                            <div class="inputLblWrap">
                                <div class="labelTo">שם בעלים</div>
                                <input type="text" name="owners" placeholder="<?=js_safe($btr[$id]['owners'] ?? $siteData['owners'] ?? 'שם בעלים')?>"  value="<?=js_safe($trans[$id]['owners'])  ?? js_safe($siteData['owners'])?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">שם העסק</div>
                                <input type="text" placeholder="<?=js_safe($btr[$id]['bussinessName'] ?? $siteData['bussinessName'] ?? 'שם העסק')?>" name="bussinessName" value="<?=js_safe($trans[$id]['bussinessName']) ?? js_safe($siteData['bussinessName']) ?>" />
                            </div>
                            <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
                                <div class="section">
                                    <div class="inptLine">
                                        <div class="label">תמונת בעלים: </div>
                                        <input type="file" name="hostsPicture" class="inpt" value="<?=$siteData['hostsPicture']?>">
                                    </div>
                                </div>
                                <?php if($siteData['hostsPicture']){ ?>
                                    <div class="section">
                                        <div class="inptLine">
                                            <img src="<?=picturePath($siteData['hostsPicture'],"../../../gallery/")?>" style="width:100%">
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
                                <div class="section">
                                    <div class="inptLine">
                                        <div class="label">לוגו: </div>
                                        <input type="file" id="logoPicture" name="logoPicture" class="inpt" value="<?=$siteData['logoPicture']?>">
                                        <?if($siteData['logoPicture']){?>
                                            <div style="display:flex;align-items:center;clear:both">
                                                <input style="width:20px;height:20px" type="checkbox" name="logoPictureDel"> מחק תמונה
                                            </div>
                                        <?}?>
                                    </div>
                                </div>
                                <?php if($siteData['logoPicture']){ ?>
                                    <div class="section">
                                        <div class="inptLine">
                                            <img src="<?=picturePath($siteData['logoPicture'],"../../../gallery/")?>" style="width:100%">
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                    <?
                    if($siteData['spaplusID'] && $domainID == 1) {
                        ?>
                        <div class="inputLblWrap">
                            <div class="labelTo">פופ ספא רקע</div>
                            <input type="color" name="popBg" placeholder="פופ ספא צבע רקע"  value="<?=$siteData['popBg']? $siteData['popBg'] : "#F595B3" ?>" />
                        </div>
                    <?}?>



                    <?php if (!$siteID){ ?>
                        <div class="inputLblWrap">
                            <div class="labelTo">שם משתמש<span class="checkBtn" onClick="checkFree($('#userName').val())">בדוק אם פנוי</span></div>
                            <input type="text" placeholder="שם משתמש" name="userName" id="userName" value="<?=$siteData['userName']?>" />
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">סיסמא</div>
                            <input type="text" placeholder="<?=($siteData['password'] ? '*********' : 'סיסמא')?>" name="password" id="password" value="" />
                        </div>
                        <?php
                    }
                    ?>
                    <div class="inputLblWrap">
                        <div class="labelTo">אימייל</div>
                        <input type="text" placeholder="אימייל" name="email" value="<?=$siteData['email']?>" />
                    </div>

                    <div class="inputLblWrap">
                        <div class="labelTo">שטח המתחם</div>
                        <input type="text" placeholder='שטח המתחם' name="compSize" value="<?=js_safe($siteData['compSize'])?>" />
                    </div>
                    <?if($domainID == 1) { ?>
                        <div class="inputLblWrap">
                            <div class="labelTo">מזהה ספא פלוס</div><?
                            $spaplusSites= udb::key_value("select spplusSites.* from spplusSites LEFT JOIN `sites` ON (spplusSites.siteID = sites.spaplusID AND sites.siteID <> " . $siteID . ") WHERE sites.siteID IS NULL","siteID");
                            ?>
                            <input type="text" placeholder='מזהה ספא פלוס' name="spaplusID2" id="spaplusID2" value="<?=intval($siteData['spaplusID']) ? $spaplusSites[intval($siteData['spaplusID'])] : '';?>" list="spaplussites" />
                            <datalist id="spaplussites">
                                <?

                                foreach ($spaplusSites as $k=>$spaplusSite) {
                                    ?><option value="<?=js_safe($spaplusSite)?>" data-value="<?=$k?>"></option><?
                                }
                                ?>
                            </datalist>
                            <input type="hidden" name="spaplusID" id="spaplusID" value="<?=intval($siteData['spaplusID'])?>" list="spaplussites" />

                        </div>

                        <div class="inputLblWrap">
                            <div class="labelTo">שם ל-SMS</div>
                            <input type="text" placeholder='שם ל-SMS' name="smsName" value="<?=js_safe($siteData['smsName'])?>" />
                        </div>

                    <?}?>






                    <?php
                    foreach([$domainID => ''] as $id => $dom){ ?>
                        <div class="domain" data-id="<?=$id?>">
                            <?if($id != 1) {?>
                                <div class="inputLblWrap">
                                    <div class="labelTo">סה"כ כמות חדרי שינה</div>
                                    <input type="text" placeholder='סה"כ כמות חדרי שינה' name="bedroomCount" value="<?=$siteDomains[$id]['bedroomCount']?>" />
                                </div>
                                <div class="inputLblWrap">
                                <div class="labelTo">סה"כ מקלחות</div>
                                <input type="text" placeholder='סה"כ מקלחות' name="bathroomCount" value="<?=$siteDomains[$id]['bathroomCount']?>" />
                                </div><?}?>
                            <div class="inputLblWrap">
                                <div class="labelTo">טלפון</div>
                                <input type="text" placeholder="טלפון" name="phone" value="<?=js_safe($siteDomains[$id]['phone'])?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">טלפון 2</div>
                                <input type="text" placeholder="טלפון נוסף" name="phone2" value="<?=js_safe($siteDomains[$id]['phone2'])?>" />
                            </div>
                            <div class="inputLblWrap" <?=$id == 1 ? ' style="display:none" ' : '';?>>
                                <div class="switchTtl">פעיל </div>
                                <label class="switch">
                                    <input type="checkbox" name="active" value="1" <?=($siteID ? '' : 'checked="checked"')?> <?=($siteDomains[$id]['active'] ? 'checked="checked"' : '')?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <div class="inputLblWrap" <?=$id == 1 ? ' style="display:none" ' : '';?>>
                                <div class="switchTtl">הסתר אמצעי התקשרות <span style="display:block;"></span>(מסתיר בנוסף גם בתוצאות חיפוש)</span></div>
                                <label class="switch">
                                    <input type="checkbox" name="hideContactMethods" value="1"  <?=($siteDomains[$id]['hideContactMethods'] ? 'checked="checked"' : '')?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="inputLblWrap">
                                <div class="switchTtl">לזוגות בלבד</div>
                                <label class="switch">
                                    <input type="checkbox" name="forCouples" value="1" <?=($siteDomains[$id]['forCouples'] ? 'checked="checked"' : '')?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="inputLblWrap" style="display:none">
                                <div class="switchTtl">כמות מבוגרים ילדים</div>
                                <label class="switch">
                                    <input type="checkbox" name="showKidsAndAdults" value="1" <?=($siteDomains[$domainID]['showKidsAndAdults']==1 || !$siteID ? 'checked="checked"' : '')?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="inputLblWrap" style="display:none">
                                <div class="switchTtl">הפק חשבוניות</div>
                                <label class="switch">
                                    <input type="checkbox" name="invoice" value="1"  <?=($siteDomains[$id]['invoice'] ? 'checked="checked"' : '')?> <?=($siteData['invoice']==1 && $id==0)?"checked":""?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="inputLblWrap">
                                <div class="labelTo">טלפון למיסוך</div>
                                <input type="text" placeholder="טלפון למיסוך" name="maskyooPhone" value="<?=$siteDomains[$id]['maskyooPhone']?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">סולולארי להודעות</div>
                                <input type="text" placeholder="סולולארי להודעות" name="phoneSms" value="<?=$siteDomains[$id]['phoneSms']?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">סולולארי לווטסאפ</div>
                                <input type="text" placeholder="סולולארי לווטסאפ" name="whatsappPhone" value="<?=$siteDomains[$id]['whatsappPhone']?>" />
                            </div>
                            <div class="inputLblWrap" <?=$id != 1 ? ' style="display:none" ' : '';?>>
                                <div class="labelTo">טלפון לעדכוני הזמנה</div>
                                <span style="width:160px;display:block">מספר זה יחליף את הטלפון למיסוך במסך חתימת הזמנה</span>
                                <input type="text" placeholder="טלפון לעדכוני הזמנה" name="orderEditPhone" value="<?=$siteDomains[$id]['orderEditPhone']?>" />
                            </div>
                            <?
                            if($domainID != 1) {
                                ?>

                                <div class="inputLblWrap" style="display:none">
                                    <div class="labelTo">משפט מארחים</div>
                                    <input type="text" placeholder="משפט מארחים" name="hostPhrase" value="<?=$siteDomains[$id]['hostPhrase']?>" />
                                </div>
                                <div class="inputLblWrap" style="display:none">
                                    <div class="labelTo">נבדק בתאריך</div>
                                    <input type="text" placeholder="נבדק בתאריך" class="datepicker" name="checkedDate" value="<?=$siteDomains[$id]['checkedDate']?>" />
                                </div>
                                <div class="inputLblWrap" style="display:none">
                                <div class="labelTo">נבדק על ידי</div>
                                <input type="text" placeholder="נבדק על ידי" name="checkedBy" value="<?=$siteDomains[$id]['checkedBy']?>" />
                                </div><?}?>
                            <div class="inputLblWrap" style="display:none">
                                <div class="switchTtl">אמנת שירות</div>
                                <label class="switch">
                                    <input type="checkbox" name="ServiceLevelAgreement" value="1" <?=($siteDomains[$id]['ServiceLevelAgreement'] ? 'checked="checked"' : '')?> <?=($siteData['ServiceLevelAgreement']==1 && $id==0)?"checked":""?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>



                            <?php foreach(LangList::get() as $lid => $lang){

                                if($siteid) {
                                    $trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[$domainID][$lid] : Translation::sites($siteID, '*', $lid, $domainID);
                                    $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                                }
                                ?>
                                <div class="language"  data-id="<?=$lid?>">
                                    <div class="section txtarea big" style="display:none !important;">
                                        <div class="label">תיאור קצר: </div>
                                        <textarea name="shortDesc" class="shortextEditor" title=""><?=$trans[$lid]['shortDesc']?></textarea>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
                <?
                if($domainID != 1 || $siteData['siteType'] & 2) {
                    ?>
                    <div class="mainSectionWrapper">
                        <div class="sectionName">תאור המתחם</div>
                        <?php if($domainID == 6 ) {?><a class="pullFromBiz" style="background:#2FC2EB;color:#FFFFFF;font-size: 16px;padding: 0 10px;cursor: pointer; ">משוך מביז</a><?php }?>
                        <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
                            <?php
                            foreach(LangList::get() as $lid => $lang){


                                //$trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[1][1] : Translation::sites($siteID, '*', $lid, $domainID);
                                $trans[$lid] = Translation::sites($siteID, '*', $lid, $domainID);
//                            if($trans[$lid]['reviewLocation'] !=' ' && !$trans[$lid]['reviewLocation'] ) {
//                                $trans[$lid] = Translation::sites($siteID, '*', $lid, 1);
//                            }
//                            if($trans[$lid]['reviewLocation'] !=' ' && !$trans[$lid]['reviewLocation'] ) {
//                                $trans[$lid] =  $siteLangs[$lid][$domainID];
//                                if($trans[$lid]['reviewLocation'] !=' ' && !$trans[$lid]['reviewLocation'] ) {
//                                    $trans[$lid] =  $siteLangs[$lid][1];
//
//                                }
//                            }

                                $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                                ?>
                                <div class="domain" data-id="<?=$domainID?>">
                                    <div class="language" data-id="<?=$lid?>">
                                        <div class="section txtarea big">
                                            <div class="inptLine">
                                                <div class="label noFloat">תאור המתחם: </div>
                                                <textarea class="textEditor" name="reviewLocation"><?=outDb($trans[$lid]['reviewLocation'])?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?  } ?>
                        </div>
                    </div>
                    <?
                }?>
                <?if($domainID == 1) {?>
                    <div class="mainSectionWrapper">
                        <div class="sectionName">הגדרות מערכת</div>


                        <div class="inputLblWrap">
                            <div class="switchTtl">הצג סטטיסטיקות</div>
                            <label class="switch">
                                <input type="checkbox" name="showStats" value="1" <?=($siteData['showStats'] ? 'checked="checked"' : '')?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap">
                            <div class="switchTtl">הצג עדכון תאריכים באתרי וילות</div>
                            <label class="switch">
                                <input type="checkbox" name="showDatesUpdate" value="1" <?=($siteData['showDatesUpdate'] ? 'checked="checked"' : '')?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">מספר חשבשבת</div>
                            <input type="text" placeholder="מספר חשבשבת" name="bookkeeping" value="<?=$siteData['bookkeeping']?>" />
                        </div>

                    </div>

                    <div class="mainSectionWrapper">
                        <div class="sectionName">הסכם מערכת המתחם</div>
                        <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
                            <?php

                            foreach(LangList::get() as $lid => $lang){

                                if($siteid) {
                                    $trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[1][1] : Translation::sites($siteID, '*', $lid, $domainID);
                                    $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                                }

                                ?>
                                <div class="domain" data-id="<?=$domainID?>">
                                    <div class="language" data-id="<?=$lid?>">
                                        <div class="section txtarea big">
                                            <div class="inptLine">
                                                <div class="label noFloat">הסכם מערכת המתחם: </div>
                                                <textarea class="textEditor" name="minisiteAgree"><?php
                                                    if($trans[$lid]['minisiteAgree']) {
                                                        echo outDb($trans[$lid]['minisiteAgree']);
                                                    }  else {
                                                        $que = "SELECT * FROM MainPages LEFT JOIN MainPages_text USING (MainPageID) WHERE MainPageType=2 AND MainPageID=18";
                                                        $ppage = udb::single_row($que);
                                                        echo outDb($ppage['html_text']);
                                                    }
                                                    ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?  }

                            ?>
                        </div>
                    </div>
                    <div class="mainSectionWrapper">
                        <div class="sectionName">הסכם Vouchers המתחם</div>
                        <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
                            <?php
                            foreach(LangList::get() as $lid => $lang){

                                if($siteid) {
                                    $trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[1][1] : Translation::sites($siteID, '*', $lid, $domainID);
                                    $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                                }

                                ?>
                                <div class="domain" data-id="<?=$domainID?>">
                                    <div class="language" data-id="<?=$lid?>">
                                        <div class="section txtarea big">
                                            <div class="inptLine">
                                                <div class="label noFloat">הסכם Vouchers המתחם: </div>
                                                <textarea class="textEditor" name="minisiteVAgree"><?php
                                                    if($trans[$lid]['minisiteVAgree']) {
                                                        echo outDb($trans[$lid]['minisiteVAgree']);
                                                    }  else {
                                                        $que = "SELECT * FROM MainPages LEFT JOIN MainPages_text USING (MainPageID) WHERE MainPageType=2 AND MainPageID=134";
                                                        $ppage = udb::single_row($que);
                                                        echo outDb($ppage['html_text']);
                                                    }
                                                    ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?  } ?>
                        </div>
                    </div>
                    <div class="mainSectionWrapper">
                        <div class="sectionName">אמנת שירות המתחם</div>
                        <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
                            <?php
                            foreach(LangList::get() as $lid => $lang){

                                if($siteID) {
                                    $trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[1][1] : Translation::sites($siteID, '*', $lid, $domainID);
                                    $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                                }

                                ?>
                                <div class="domain" data-id="<?=$domainID?>">
                                    <div class="language" data-id="<?=$lid?>">
                                        <div class="section txtarea big">
                                            <div class="inptLine">
                                                <div class="label noFloat">אמנת שירות המתחם: </div>
                                                <textarea class="textEditor" name="minisiteSAgree"><?php
                                                    if($trans[$lid]['minisiteSAgree']) {
                                                        echo outDb($trans[$lid]['minisiteSAgree']);
                                                    }  else {
                                                        $que = "SELECT * FROM MainPages LEFT JOIN MainPages_text USING (MainPageID) WHERE MainPageType=103 AND `MainPages_text`.langID = 1 AND `MainPages_text`.domainID = 1";
                                                        $ppage = udb::full_list($que);
                                                        $pp = 0;
                                                        foreach($ppage as $page) { $pp++;
                                                            ?>
                                                            <li style="margin-bottom:20px"><?if($pp>1){?><b><?=$page['mainPageTitle']?></b><br><?}?>
                                                            <?=$page['html_text'];?>
                                                            </li>
                                                        <?}?>
                                                    <?php }
                                                    ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?  } ?>
                        </div>
                    </div>
                <?}?>
                <?if($domainID != 1) {
                    ?>
                    <div class="mainSectionWrapper">
                        <div class="sectionName">מאפיינים ראשיים</div>
                        <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
                            <?php
                            foreach(LangList::get() as $lid => $lang){

                                //$trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[$domainID][$lid] : Translation::sites($siteID, '*', $lid, $domainID);
                                if($siteID) {
                                    $trans[$lid] = Translation::sites($siteID, '*', $lid, $domainID); // $siteLangs[1][$lid] ;
                                }
                                $hasData = 0;
                                for($attI = 1; $attI <=5;$attI++) {
                                    if($trans[$lid]['attr'.$attI]) $hasData++;
                                }
//                            if($hasData == 0) {
//                                $trans[$lid] = $siteLangs[$domainID][$lid];
//                                for($attI = 1; $attI <=5;$attI++) {
//                                    if($trans[$lid]['attr'.$attI]) $hasData++;
//                                }
//                                if($hasData == 0) {
//                                    $trans[$lid] = $siteLangs[1][$lid];
//
//                                }
//                            }
                                if($siteID) {
                                    $btr[$lid] = Translation::sites($siteID, '*', $lid, 1);
                                }
                                ?>
                                <div class="domain" data-id="<?=$domainID?>">
                                    <div class="language" data-id="<?=$lid?>">
                                        <?for($attI = 1; $attI <=5;$attI++) {?>
                                            <div class="inputLblWrap">
                                                <div class="inptLine">
                                                    <div class="label noFloat">כותרת <?=$attI?>: </div>
                                                    <input type="text" placeholder="<?=js_safe($btr[$id]['attr'.$attI] ?? $siteData['attr'.$attI] ?? 'כותרת '.$attI)?>" name="attr<?=$attI?>" value="<?=outDb(htmlspecialchars($trans[$lid]['attr'.$attI], ENT_QUOTES, 'UTF-8'))?>" />
                                                </div>
                                            </div>
                                        <?}?>
                                    </div>
                                </div>
                            <?  } ?>
                        </div>
                    </div>
                    <?
                }?>

                <div class="mainSectionWrapper">
                    <div class="sectionName" id="mainGallery">גלרייה מייצגת</div>
                    <div class="manageItems">
                        <div class="addButton" style="margin-top: 20px;">
                            <?php //foreach(DomainList::get() as $domid => $dom){
                            //
                            //
                            //
                            $domid = $domainID;
                            ?>

                            <div class="domain" data-id="<?=$domid?>">
                                <div class="tableWrap">
                                    <div class="rowWrap top">
                                        <!-- <div class="tblCell">#</div> -->
                                        <div class="tblCell">ID</div>
                                        <div class="tblCell">שם הגלריה</div>
                                        <div class="tblCell"></div>
                                    </div>

                                    <?php
                                    if($siteMainGalleries[$domid]){
                                        foreach($siteMainGalleries[$domid] as $gallery) { ?>
                                            <div class="rowWrap">
                                                <!-- <div class="tblCell">**</div> -->
                                                <div class="tblCell"><?=$gallery['galleryID']?></div>
                                                <div class="tblCell"><?=$gallery['galleryTitle']?></div>
                                                <div class="tblCell"><span onclick="galleryOpen(<?=$domid.",".$siteID.",".$gallery['galleryID']?>,'site_main_galleries')"  class="editGalBtn">ערוך גלריה</span>
                                                    <div class="dupGalWrap">
                                                        <select name="galWrapSelect" id="galWrapSelectsite_main_galleries<?=$gallery['galleryID']?>">
                                                            <option value="-1">כל הדומיינים</option>
                                                            <?php foreach(DomainList::get() as $domain) {
                                                                if($domain['domainID'] == 1 || $domain['domainID'] == $domainID) continue;
                                                                ?>
                                                                <option value="<?=$domain['domainID']?>"><?=$domain['domainName']?></option>
                                                            <?php } ?>
                                                        </select>
                                                        <span class="editGalBtn" onclick="dupGal('site_main_galleries',<?=$gallery['galleryID']?>,<?=$siteID?>,<?=$domid?>,'site_main_galleries')">שכפל גלריה</span>

                                                    </div>
                                                </div>
                                            </div>
                                        <?php } } ?>
                                </div>
                                <?if(!$siteMainGalleries[$domid] ) {?>
                                    <div class="addNewBtnWrap">
                                    <input type="button" class="addNew" id="addNewAcc<?=$domid?><?=time()?>" value="הוסף חדש" onclick="galleryOpen(<?="1".",".$siteID?>,'new','site_main_galleries')" >
                                    </div><?}?>

                                <?php /* if(!$roomData['galleryID']){ ?>
										<input type="button" class="addNew" id="addNewAcc<?=$domid?>" value="הוסף חדש" onclick="galleryOpen(<?=$domid.",".$siteID.",".$roomID?>,'new')" >
									<?php } else { ?>
										<input type="button" class="addNew" id="addNewAcc<?=$domid?>" value="הצג תמונות" onclick="galleryOpen(<?=$domid.",".$siteID.",".$roomID.",".$roomData['galleryID']?>)" >
									<?php }  */?>


                            </div>
                            <?php //} ?>
                        </div>
                        <?php
                        if($galleries){ ?>
                            <table>
                                <thead>
                                <tr>
                                    <th width="30">#</th>
                                    <th>שם גלריה</th>
                                    <th>מוצג באתר</th>
                                    <th width="60">&nbsp;</th>
                                </tr>
                                </thead>
                                <tbody  id="sortRow">

                                <?php foreach($galleries as $row) { ?>
                                    <tr  id="<?=$row['GalleryID']?>">
                                        <td align="center"><?=$row['GalleryID']?></td>
                                        <td onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><?=outDb($row['GalleryTitle'])?></td>
                                        <td align="center"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
                                        <td align="center" class="actb">
                                            <div onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>|</div><div onClick="if(confirm('אתה בטוח??')){location.href='?sID=<?=$siteID?>&frame=<?=$frameID?>&gdel=<?=$row['GalleryID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
                                    </tr>
                                <? } ?>
                                </tbody>
                            </table>
                        <? } ?>
                    </div>
                </div>
                <?if($domainID != 1) {?>
                    <div class="mainSectionWrapper">
                    <div class="sectionName">SEO</div>
                    <?php
                    $seoDataFromDestionation = [];
                    if($siteID) {
                        switch($domainID) {
                            case 6:
                                $seoDataFromDestionation = galGetUrl("https://www." . $domains[$domainID]['domainURL'] . "/viiapi.php?key=bizonline1025&type=seo&siteID=".$siteID);
                                if(!is_array($seoDataFromDestionation))
                                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                                break;
                            case 105:
                            case 106:
                            case 107:
                            case 108:
                            case 109:
                                $domains[$domainID]['domainURL'] = "www." . $domains[$domainID]['domainURL'];
                            case 111:
                                $seoDataFromDestionation = galGetUrl("https://ssd:ssdSSD1234!@loftland2.c-ssd.com/byhoursapi.php?key=bizonline1025&type=seo&siteID=".$siteID);
                                if(!is_array($seoDataFromDestionation))
                                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                                break;
                            case 10:
                                $seoDataFromDestionation = galGetUrl("https://" . $domains[$domainID]['domainURL'] ."/byhoursapi.php?key=bizonline1025&type=seo&siteID=".$siteID);
                                if(!is_array($seoDataFromDestionation))
                                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                                break;
                        }
                    }
                    foreach(LangList::get() as $lid => $lang){ ?>
                        <div class="domain" data-id="<?=$domainID?>">
                            <div class="language" data-id="<?=$lid?>">
                                <div class="inputLblWrap">
                                    <div class="labelTo">כתובת הדף</div>
                                    <input type="text" placeholder="כותרת עמוד" name="level2" value="<?=outDb($seo[$domainID][$lid]['LEVEL2'])?>"  />
                                </div>
                                <div class="inputLblWrap">
                                    <div class="labelTo">כותרת עמוד</div>
                                    <input type="text" placeholder="כותרת עמוד" title="<?=$seoDataFromDestionation['data']['title']?>" name="title" value="<?=outDb($seo[$domainID][$lid]['title'])?>" />
                                    <div onclick="$(this).parent().find('input').val($(this).val())" title="לחץ להעתקה" style="max-height:120px;overflow:auto;background:#fff;border:1px #ccc solid;padding:10px;cursor:pointer"><?=$seoDataFromDestionation['data']['title']?></div>
                                </div>
                                <div class="inputLblWrap" style="display: none">
                                    <div class="labelTo">H1</div>
                                    <input type="text" placeholder="H1" name="h1" value="<?=outDb($seo[$domainID][$lid]['h1'])?>" />
                                </div>
                                <!-- <div class="inputLblWrap">
                                    <div class="labelTo">קישור</div>
                                    <input type="text" placeholder="קישור" name="link" value="" />
                                </div> -->
                                <div class="section txtarea">
                                    <div class="inptLine">
                                        <div class="label">מילות מפתח</div>
                                        <textarea name="seoKeyword" title="<?=$seoDataFromDestionation['data']['keywords']?>"><?=outDb($seo[$domainID][$lid]['keywords'])?></textarea>
                                        <div onclick="$(this).parent().find('textarea').html($(this).html())" title="לחץ להעתקה" style="max-height:120px;overflow:auto;background:#fff;border:1px #ccc solid;padding:10px;cursor:pointer"><?=$seoDataFromDestionation['data']['keywords']?></div>
                                    </div>
                                </div>
                                <div class="section txtarea">
                                    <div class="inptLine">
                                        <div class="label">תאור דף</div>
                                        <textarea name="seoDesc" title="<?=$seoDataFromDestionation['data']['description']?>"><?=outDb($seo[$domainID][$lid]['description'])?></textarea>
                                        <div onclick="$(this).parent().find('textarea').html($(this).html())" title="לחץ להעתקה" style="max-height:120px;overflow:auto;background:#fff;border:1px #ccc solid;padding:10px;cursor:pointer"><?=$seoDataFromDestionation['data']['description']?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    </div><?}?>

                <div class="mainSectionWrapper">
                    <div class="sectionName">מסוף</div>
                    <div class="inSectionWrap">
                        <div class="inputLblWrap">
                            <div class="labelTo">סוג מסוף</div>
                            <select name="masof_type" title="סוג מסוף">
                                <option value="">- - - - - - - - - - - - - - -</option>
                                <option value="yaad" <?=($siteData['masof_type'] == 'yaad' ? 'selected' : '')?>>Yaad</option>
                                <option value="max" <?=($siteData['masof_type'] == 'max' ? 'selected' : '')?>>MAX (דרך Yaad)</option>
                                <option value="cardcom" <?=($siteData['masof_type'] == 'cardcom' ? 'selected' : '')?>>CardCom</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin:-30px 36px 0 0; font-size:smaller">* במידה והלקוח הוא לקוח של מקס (לא משנה אם גוייס דרכנו או לא) יש לבחור max</div>
                    <div class="inSectionWrap">
                        <div class="inputLblWrap">
                            <div class="labelTo">מספר מסוף</div>
                            <input type="text" placeholder="מספר מסוף" name="masof_number" value="<?=$siteData['masof_number']?>" />
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">מפתח מסוף</div>
                            <input type="text" placeholder="מפתח מסוף" name="masof_key" value="<?=$siteData['masof_key']?>" />
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">סיסמת ביטולים</div>
                            <input type="text" placeholder="סיסמת ביטולים" name="masof_pwd" value="<?=$siteData['masof_pwd']?>" />
                        </div>
                        <div class="inputLblWrap">
                            <div class="switchTtl">מסוף פעיל</div>
                            <label class="switch">
                                <input type="checkbox" name="masof_active" value="1" <?=$siteData['masof_active']?'checked="checked"':""?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <?if($siteData['siteType'] & 2 ) {?>
                            <div class="inputLblWrap">
                            <div class="labelTo">חיובים וכרטיס לערבון לאונליין</div>
                            <select name="popBizPayOptions" title="חיובים וכרטיס לערבון לאונליין" >
                                <option value="2" <?=($siteData['popBizPayOptions']) == 2 ? 'selected' : ''?>>חיובים בלבד</option>
                                <option value="0" <?=(($siteData['popBizPayOptions'] == 0) ? 'selected' : '')?>>חיובים וכרטיס לערבון</option>
                                <option value="1" <?=(($siteData['popBizPayOptions'] == 1) ? 'selected' : '')?>>כרטיס לערבון בלבד</option>
                                <option value="9" <?=(($siteData['popBizPayOptions'] == 9) ? 'selected' : '')?>>ללא שלב אשראי</option>
                            </select>
                            </div><?}?>
                        <div class="inputLblWrap">
                            <div class="labelTo">חיובים וכרטיס לערבון</div>
                            <select name="masof_cc_data" title="חיובים וכרטיס לערבון" onchange="$('#j5div').css('display', (this.value == 1) ? 'none' : 'inline-block')">
                                <option value="1" <?=(empty($siteData['masof_no_cvv']) ? 'selected' : '')?>>חיובים בלבד</option>
                                <option value="3" <?=(($siteData['masof_no_cvv'] && empty($siteData['masof_no_charge'])) ? 'selected' : '')?>>חיובים וכרטיס לערבון</option>
                                <option value="2" <?=(($siteData['masof_no_cvv'] && $siteData['masof_no_charge']) ? 'selected' : '')?>>כרטיס לערבון בלבד</option>
                            </select>
                        </div>
                        <div class="inputLblWrap" id="j5div" <?=(empty($siteData['masof_no_cvv']) ? 'style="display:none"' : '')?>>
                            <div class="labelTo">סוג בדיקת כרטיס</div>
                            <select name="masof_check_type" title="סוג בדיקת כרטיס">
                                <option value="2">בדיקת כרטיס פשוטה</option>
                                <option value="5" <?=(($siteData['masof_check_type'] == 5) ? 'selected' : '')?>>בדיקת כרטיס משודרגת</option>
                                <option value="10" <?=(($siteData['masof_check_type'] == 10) ? 'selected' : '')?>>תפיסת מסגרת</option>
                            </select>
                        </div>

                        <!-- div class="inputLblWrap" style="display:none">
                        <div class="switchTtl">בדיקת כרטיס משודרגת</div>
                        <label class="switch">
                            <input type="checkbox" name="masof_j5" value="1" <?=(($siteData['masof_check_type'] == 5) ? 'checked="checked"' : '')?> />
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="inputLblWrap" style="display:none">
                        <div class="switchTtl">כרטיס לערבון</div>
                        <label class="switch">
                            <input type="checkbox" name="masof_no_cvv" value="1" <?=$siteData['masof_no_cvv']?'checked="checked"':""?> />
                            <span class="slider round"></span>
                        </label>
                    </div -->
                    </div>
                    <div class="inSectionWrap">
                        <style>
                            #invoicewrap:not(.checked) ~ .invplus{display:none}
                        </style>
                        <div class="inputLblWrap <?=$siteData['masof_invoice']?'checked' :""?>" id='invoicewrap'>
                            <div class="switchTtl">חשבוניות</div>
                            <label class="switch">
                                <input type="checkbox" onchange="if($(this).is(':checked')){$('#invoicewrap').addClass('checked')}else{$('#invoicewrap').removeClass('checked')}" name="masof_invoice" value="1" <?=$siteData['masof_invoice']?'checked="checked"':""?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap invplus">
                            <div class="switchTtl">פירוט בחשבונית?</div>
                            <label class="switch">
                                <input type="checkbox" name="addDescToInvoice" value="1" <?=$siteData['addDescToInvoice']?'checked="checked"':""?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap invplus">
                            <div class="switchTtl">פתיחת פופ חשבונית אוטומטית?</div>
                            <label class="switch">
                                <input type="checkbox" name="autoInvoice" value="1" <?=$siteData['autoInvoice']?'checked="checked"':""?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap invplus">
                            <div class="switchTtl">חשבוניות בלי מע"מ</div>
                            <label class="switch">
                                <input type="checkbox" name="masof_noVAT" value="1" <?=$siteData['masof_noVAT']?'checked="checked"':""?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">סוג חשבונית</div>
                            <select name="masof_doc_type" title="סוג חשבונית">
                                <?php
                                foreach(CardCom::$_invoiceList as $key => $mdata)
                                    echo '<option value="' , $key , '" ' , (($key == $siteData['masof_doc_type']) ? 'selected' : '') , '>' , $mdata['name'] , '</option>';
                                ?>
                            </select>
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">טקסט מידע לדו"ח חשבוניות</div>
                            <textarea  placeholder='טקסט מידע לדו"ח חשבוניות' name="infoInvoiceText"  ><?=$siteData['infoInvoiceText']?></textarea>
                        </div>
                    </div>

                    <div class="inSectionWrap">
                        <div class="inputLblWrap">
                            <div class="switchTtl">CardReader</div>
                            <label class="switch">
                                <input type="checkbox" name="masof_swipe" value="1" <?=$siteData['masof_swipe']?'checked="checked"':""?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">מספר מחלקה ב-CardCom</div>
                            <input type="text" placeholder="מספר מחלקה ב-CardCom" name="masof_department" value="<?=$siteData['masof_department']?>" />
                        </div>
                    </div>
                </div><?if($domainID == 1) {?>
                    <div class="mainSectionWrapper">
                        <div class="sectionName">מיקום</div>
                        <div class="inSectionWrap">
                            <div class="inputLblWrap">
                                <div class="labelTo">אזורים</div>
                                <div class="selectAndCheck" id="areasChecks">
                                    <div class="choosenCheck"></div>
                                    <div class="checksWrrap">
                                        <?php
                                        foreach($areas as $aid => $aname)
                                            echo '<div><input type="checkbox" name="areas[]" value="' , $aid , '" ' , ($siteAreas?(in_array($aid, $siteAreas) ? 'checked="checked"' : ''):"") , ' /> ' , $aname , '</div>';
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">ישוב</div>
                                <select name="city" title="ישוב">
                                    <option value="0">- - בחר ישוב - -</option>
                                    <?php foreach($settlements as $settlement){ ?>
                                        <option value="<?=$settlement['settlementID']?>" <?
                                        if($siteData['settlementID']==$settlement['settlementID']){
                                            echo " selected";
                                            $cityName = $settlement['TITLE'];
                                        }?> data-area="<?=$settlement['areaID']?>"><?=$settlement['TITLE']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">כתובת</div>
                                <?php
                                foreach(LangList::get() as $id => $lang){

                                    if($siteID) {
                                        $trans[$id] = ($domainID == 1 && $id == 1) ? $siteLangs[$domainID][$id] : Translation::sites($siteID, '*', $id, $domainID);
                                        $btr[$id] = Translation::sites($siteID, '*', $id, Translation::DEFAULT_DOMAIN);
                                        $useAddress[$id] = js_safe($trans[$id]['address']);
                                    }
                                    else {
                                        $trans[$lid] = [];
                                        $btr[$lid] = [];
                                        $useAddress[$id] = "";
                                    }


                                    ?>
                                    <div class="language" data-id="<?=$id?>">
                                        <input type="text" placeholder="<?=js_safe($btr[$id]['address'] ?? $siteData['address'] ?? 'כתובת')?>" name="address" value="<?=js_safe($trans[$id]['address'])?>" />
                                    </div>
                                    <?php
                                }
                                ?>
                            </div><?
                            $didNotHaveLatLng = false;
                            //                        if($siteID && (!$siteData['gpsLat'] || !$siteData['gpsLong']) && $cityName) {
                            //                            $searchAddress =  $useAddress[1] ? $useAddress[1] ."," : '' . $cityName;
                            //
                            //                            $didNotHaveLatLng = true;
                            //                            $latlng = getLocationNumbers($searchAddress);
                            //                            $siteData['gpsLat'] = $latlng['lat'];
                            //                            $siteData['gpsLong'] = $latlng['long'];
                            //                            udb::update("sites",$siteData," siteID=".$siteID);
                            //                            $didNotHaveLatLng = true;
                            //
                            //                        }
                            ?>
                            <div class="clear"><?//=$searchAddress?></div>
                            <div class="inputLblWrap">
                                <div class="labelTo">GPS Lat</div>
                                <input type="text" placeholder="Lat" name="gpsLat" value="<?=$siteData['gpsLat']?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">GPS Long</div>
                                <input type="text" placeholder="Long" name="gpsLong" value="<?=$siteData['gpsLong']?>" />
                            </div>
                            <?if($didNotHaveLatLng === true) {
                                ?>
                                <div class="inputLblWrap" style="color: #FF0000;">
                                    קורדינטות חדשות אותרו<BR>
                                    יש לבצע שמירה לשמירת קורדינטות המקום
                                </div>
                            <?}?>
                            <div class="inputLblWrap">
                                <div class="labelTo">טען תמונת מפה מחדש</div>
                                <input type="checkbox"  name="reloadgooglemap" value="1" <?=($didNotHaveLatLng === true) ? ' checked '  : ''?> />
                            </div>
                        </div>
                    </div>
                <?}?>
                <div class="mainSectionWrapper">
                    <div class="sectionName">שעות כניסה יציאה והסכמי הזמנה</div>

                    <div class="inputLblWrap">
                        <div class="labelTo">שעת כניסה</div>
                        <input type="time" class="timepicker" placeholder="שעת כניסה" name="checkInHour" value="<?=$siteData['checkInHour']?$siteData['checkInHour']:"15:00"?>" />
                    </div>
                    <div class="inputLblWrap">
                        <div class="labelTo">שעת יציאה</div>
                        <input type="time" class="timepicker" placeholder="שעת יציאה" name="checkOutHour" value="<?=$siteData['checkOutHour']?$siteData['checkOutHour']:"11:00"?>" />
                    </div>
                    <div class="inputLblWrap">
                        <div class="labelTo">שעת יציאה בשבת</div>
                        <input type="time" class="timepicker" placeholder="שעת יציאה בשבת" name="checkOutHourSat" value="<?=$siteData['checkOutHourSat']?$siteData['checkOutHourSat']:"11:00"?>" />
                    </div>
                    <?
                    foreach(LangList::get() as $lid => $lang){

                        if($siteID) {
                            $trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[$domainID][$lid] : Translation::sites($siteID, '*', $lid, $domainID);
                            $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                        }
                        else {
                            $trans[$lid] = [];
                            $btr[$lid] = [];
                        }



                        ?>
                        <div class="domain" data-id="<?=$domainID?>">
                            <div class="language" data-id="<?=$lid?>">
                                <div class="inputLblWrap">
                                    <div class="labelTo">טקסט שעת יציאה בשבת</div>
                                    <input type="text" placeholder="<?=js_safe($btr[$lid]['saturday_text'] ?? $siteData['saturday_text'] ?? 'טקסט שבת')?>" name="saturday_text" value="<?=$trans[$lid]['saturday_text']?>" />
                                </div>
                            </div>
                        </div>
                    <?}?>



                    <?php
                    foreach(LangList::get() as $lid => $lang){


                        if($siteID) {
                            $trans[$lid] = Translation::sites($siteID, '*', $lid, $domainID);
                            $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                        }
                        else {
                            $trans[$lid] = [];
                            $btr[$lid] = [];
                        }
                        ?>
                        <div class="domain" data-id="<?=$domainID?>">
                            <div class="language" data-id="<?=$lid?>">
                                <div class="section txtarea big">
                                    <div class="inptLine">
                                        <textarea style="display:none" class="textEditor" name="hostInclude"><?=outDb($defaultAgr)?></textarea>

                                        <div class="label noFloat">הסכם הזמנה 1</div>
                                        <div class="textEditorShow" ><?=outDb($defaultAgr)?></div><!-- name="hostInclude" -->
                                    </div>
                                    <div class="radioWrap">
                                        <input type="radio"  name="defaultAgr" value="1" id="defaultAgr1<?=$domainID.$lid?>" <?=($trans[$lid]['defaultAgr']==1 || !$siteID?'data-checked="1"':'')?> >
                                        <label for="defaultAgr1<?=$domainID.$lid?>">בחר הסכם זה כברירת מחדל</label>
                                    </div>
                                </div>
                                <div class="section txtarea big">
                                    <div class="inptLine">
                                        <div class="label noFloat">הסכם הזמנה 2</div>
                                        <textarea class="textEditor" name="cancellation"><?=outDb($trans[$lid]['agreement2'])?></textarea>
                                    </div>
                                    <div class="radioWrap">
                                        <input type="radio"  name="defaultAgr" value="2" id="defaultAgr2<?=$domainID.$lid?>" <?=($trans[$lid]['defaultAgr']==2?'data-checked="1"':'')?>>
                                        <label for="defaultAgr2<?=$domainID.$lid?>">בחר הסכם זה כברירת מחדל</label>
                                    </div>
                                </div>
                                <div class="section txtarea big">
                                    <div class="inptLine">
                                        <div class="label noFloat">הסכם הזמנה 3</div>
                                        <textarea class="textEditor" name="orderTerms"><?=outDb($trans[$lid]['agreement3'])?></textarea>
                                    </div>
                                    <div class="radioWrap">
                                        <input type="radio"  name="defaultAgr" value="3" id="defaultAgr3<?=$domainID.$lid?>" <?=($trans[$lid]['defaultAgr']==3?'data-checked="1"':'')?>>
                                        <label for="defaultAgr3<?=$domainID.$lid?>">בחר הסכם זה כברירת מחדל</label>
                                    </div>
                                </div>
                                <div class="section txtarea big">
                                    <div class="inptLine">
                                        <div class="label noFloat">הסכם הזמנה 4</div>
                                        <textarea class="textEditor" name="agreement4"><?=outDb($trans[$lid]['agreement4'])?></textarea>
                                    </div>
                                    <div class="radioWrap">
                                        <input type="radio"  name="defaultAgr" value="4" id="defaultAgr4<?=$domainID.$lid?>" <?=($trans[$lid]['defaultAgr']==4?'data-checked="1"':'')?>>
                                        <label for="defaultAgr4<?=$domainID.$lid?>">בחר הסכם זה כברירת מחדל</label>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <?php
                    }
                    ?>


                </div>
                <?php /* ?>
			<div class="mainSectionWrapper">
				<div class="sectionName">מדיה</div>
				<div class="inputLblWrap">
					<div class="labelTo">אתר אינטרנט</div>
					<input type="text" placeholder="אתר אינטרנט" name="website" value="<?=$siteData['website']?>" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">פייסבוק</div>
					<input type="text" placeholder="פייסבוק" name="facebook" value="<?=$siteData['facebook']?>" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">גוגל פלוס</div>
					<input type="text" placeholder="גוגל פלוס" name="googlePlus" value="<?=$siteData['googlePlus']?>" />
				</div>
			</div>
<?php */ ?>
                <div class="mainSectionWrapper">
                    <div class="sectionName" id="sitesGalleries">גלריה</div>
                    <div class="manageItems">
                        <div class="addButton" style="margin-top: 20px;">
                            <?php
                            //foreach(DomainList::get() as $domid => $dom){
                            $domid = $domainID;
                            ?>
                            <div class="domain" data-id="<?=$domid?>">
                                <input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
                                <input type="hidden" id="orderResult" name="orderResult">
                                <div class="tableWrap">
                                    <div class="rowWrap top">
                                        <!-- <div class="tblCell">#</div> -->
                                        <div class="tblCell">galleryID</div>
                                        <div class="tblCell">שם הגלריה</div>
                                        <div class="tblCell"></div>
                                        <div class="tblCell">#</div>
                                        <div class="tblCell">#</div>
                                    </div>
                                    <div class="gallerySort" id="gallerySortCont" style="osition: relative;display: table-row-group;width: 100%;">
                                        <?php
                                        if ($siteGalleries)
                                            foreach($siteGalleries as $gallery) {
                                                $showGal = false;
                                                ?>
                                                <div class="rowWrap" data-id="<?=$gallery['galleryID']?>" id="galRow<?=$gallery['galleryID']?>">
                                                    <!-- <div class="tblCell">**</div> -->
                                                    <div class="tblCell"><?=$gallery['galleryID']?>
                                                        <div class="checkGal">
                                                            <?=$gallery['galleryID']==$siteData['gallerySummer']?'<span class="summer"></span>':''?>
                                                            <?=$gallery['galleryID']==$siteData['galleryWinter']?'<span class="winter"></span>':''?>
                                                        </div>
                                                    </div>
                                                    <div class="tblCell"><?=$gallery['galleryTitle']?></div>
                                                    <div class="tblCell"><span onclick="galleryOpen(<?=$domainID.",".$siteID.",".$gallery['galleryID']?>)"  class="editGalBtn">ערוך גלריה</span>
                                                        <div class="dupGalWrap">
                                                            <select name="galWrapSelect" id="galWrapSelect<?=$gallery['galleryID']?>">
                                                                <option value="-1">כל הדומיינים</option>
                                                                <?php foreach(DomainList::get() as $domain) {
                                                                    if($domain['domainID'] ==1 || $domain['domainID'] == $domainID) continue;
                                                                    ?>
                                                                    <option value="<?=$domain['domainID']?>"><?=$domain['domainName']?></option>
                                                                <?php } ?>
                                                            </select>
                                                            <span class="editGalBtn" onclick="dupGal('',<?=$gallery['galleryID']?>,<?=$siteID?>,<?=$domainID?>,'')">שכפל גלריה</span>
                                                        </div>

                                                    </div>
                                                    <div class="tblCell">
                                                        <?php if(!$showGal) { ?>
                                                            <div class="delBtn" onclick="deleteGallery(<?=$gallery['galleryID']?>)">
                                                                <i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                    <div class="tblCell">
                                                        <?php if(!$showGal) { ?>

                                                            <label class="switch">
                                                                <input type="checkbox" name="galactive<?=$gallery['galleryID']?>" data-galid="<?=$gallery['galleryID']?>"
                                                                       class="galleryactive" value="1" <?=($gallery['active'] & 1 ? 'checked="checked"' : '')?> />
                                                                <span class="slider round"></span>
                                                            </label>

                                                        <?php } ?>
                                                    </div>



                                                </div>
                                            <?php } ?></div>
                                </div>
                                <div class="addNewBtnWrap">
                                    <input type="button" class="addNew" id="addNewAcc<?=$domainID?><?=time()?><?=time()?>" value="הוסף חדש" onclick="galleryOpen(<?=$domainID.",".$siteID?>,'new')" >
                                </div>
                            </div>
                            <?php //} ?>


                        </div>
                        <?php
                        if($galleries){ ?>
                            <table>
                                <thead>
                                <tr>
                                    <th width="30">#</th>
                                    <th>שם גלריה</th>
                                    <th>מוצג באתר</th>
                                    <th width="60">&nbsp;</th>
                                </tr>
                                </thead>
                                <tbody  id="sortRow">

                                <?php foreach($galleries as $row) { ?>
                                    <tr  id="<?=$row['GalleryID']?>">
                                        <td align="center"><?=$row['GalleryID']?></td>
                                        <td onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><?=outDb($row['GalleryTitle'])?></td>
                                        <td align="center"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
                                        <td align="center" class="actb">
                                            <div onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>|</div><div onClick="if(confirm('אתה בטוח??')){location.href='?sID=<?=$siteID?>&frame=<?=$frameID?>&gdel=<?=$row['GalleryID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
                                    </tr>
                                <? } ?>
                                </tbody>
                            </table>
                        <? } ?>
                    </div>
                </div>

                <?if($domainID == 1) { ?>
                    <div class="mainSectionWrapper attr">
                    <div class="sectionName">מאפיינים</div>
                    <select id="locationsTypes" onchange="filterProperties('locationsTypes')" style="width:auto;margin:10px;padding-left:20px">
                        <option value="-1">הכל</option>
                        <option value="1,7,3,5">צימר</option>
                        <!--<option value="8">ספא</option>-->
                        <option value="2,3,7,6">חדרים לפי שעה</option>
                        <option value="4,6,5,7">אירועים</option>
                    </select>

                    <?//print_r($all_categories);

                    ############################################################################

                    if(1==1){
                        ?>
                        <select id="locationsTypes" onchange="changeAttrDomain($(this).val())" style="width:auto;margin:10px;padding-left:20px">
                            <? foreach(DomainList::get() as $did => $dom){
                                if($did == 1) continue;
                                echo '<option value="'.$did.'" selected="">'.$dom['domainName'].'</option>';
                            }?>



                        </select>

                        <style>
                            .domain_cat{display:none}
                            .domain_cat.show{display:block}
                        </style>
                        <div>
                            <?
                            $this_catID = "";
                            foreach($domains as $d_ID => $domain){
                                if(!$all_categories[$d_ID])
                                    continue;
                                ?>
                                <div id="domain_cat<?=$d_ID?>" class='domain_cat <?=$d_ID==6? "show" : ""?>'>
                                    <?
                                    $no_cat['categoryID']='0';
                                    $no_cat['categoryName']='ללא קטגוריה';

                                    $all_categories[$d_ID][]= $no_cat;
                                    foreach($all_categories[$d_ID] as $domain_cat){

                                        ?>
                                        <div class="catName"><?=$domain_cat['categoryName']?>()</div>
                                        <div class="checksWrap" id='wrap_cat<?=$domain_cat['categoryID']?>'>
                                            <?php
                                            if($d_ID == 6 && $attributes[$domain_cat['categoryID']]){

                                                foreach($attributes[$domain_cat['categoryID']] as $attribute) {

                                                    if($displayedAttr[$attribute['attrID']]) continue;
                                                    $displayedAttr[$attribute['attrID']] = $attribute['attrID'];?>
                                                    <div class="checkLabel checkIb attr_box" data-attrtype="<?=$attribute['attrType']?>" id='attrID<?=$attribute['attrID']?>'>
                                                        <div class="checkBoxWrap">
                                                            <input class="checkBoxGr" type="checkbox" name="attributes[]" <?=$siteAttr ? (in_array($attribute['attrID'],$siteAttr)?"checked":"") : "";?> value="<?=$attribute['attrID']?>" id="ch<?=$attribute['attrID']?><?=$category['categoryID']?>">
                                                            <label for="ch<?=$attribute['attrID']?><?=$category['categoryID']?>"></label>
                                                        </div>
                                                        <label for="ch<?=$attribute['attrID']?>"><?=$attribute['defaultName']?></label>
                                                        <div class="inputLblWrap">
                                                            <div class="label">תיאור קצר: </div>
                                                            <input type="text" name="descToAttr[<?=$attribute['attrID']?>]" value="<?=outDb($siteAttrFull[$attribute['attrID']]['descToAttr'])?>" title="">
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                            }?>
                                        </div>

                                        <?php
                                    }?>
                                </div>


                                <?php
                            }
                            ?>
                        </div>
                        <?php

                        ################################################################

                    }else{

                        foreach($categories as $category) {
                            if (!$attributes[$category['categoryID']])
                                continue;
                            ?>
                            <div class="catName"><?=$category['categoryName']?>()</div>
                            <div class="checksWrap">
                                <?php foreach($attributes[$category['categoryID']] as $attribute) {
                                    if($displayedAttr[$attribute['attrID']]) continue;
                                    $displayedAttr[$attribute['attrID']] = $attribute['attrID'];
                                    ?>
                                    <div class="checkLabel checkIb" data-attrtype="<?=$attribute['attrType']?>" data-test="<?=$attribute['categoryID']?>">
                                        <div class="checkBoxWrap">
                                            <input class="checkBoxGr" type="checkbox" name="attributes[]" <?=$siteAttr ? (in_array($attribute['attrID'],$siteAttr)?"checked":"") : '';?> value="<?=$attribute['attrID']?>" id="ch<?=$attribute['attrID']?><?=$category['categoryID']?>">
                                            <label for="ch<?=$attribute['attrID']?><?=$category['categoryID']?>"></label>
                                        </div>
                                        <label for="ch<?=$attribute['attrID']?>"><?=$attribute['defaultName']?></label>
                                        <div class="inputLblWrap">
                                            <div class="label">תיאור קצר: </div>
                                            <input type="text" name="descToAttr[<?=$attribute['attrID']?>]" value="<?=outDb($siteAttrFull[$attribute['attrID']]['descToAttr'])?>" title="">
                                        </div>
                                    </div>
                                    <!--<div class="checkLabel checkIb">
						    <div class="checkBoxWrap">
								<input class="checkBoxGr" type="checkbox" name="attributesisTop[<?=$attribute['attrID']?>]" <?=$siteAttr ? ((in_array($attribute['attrID'],$siteAttr) && intval($siteAttrFull[$attribute['attrID']]['isTop']) != 0) ? " checked ":"") : '';?> value="<?=$attribute['attrID']?>" id="istop<?=$attribute['attrID']?><?=$category['categoryID']?>">
								<label for="istop<?=$attribute['attrID']?><?=$category['categoryID']?>"></label>
							</div>
							<label for="istop<?=$attribute['attrID']?><?=$category['categoryID']?>">TOP</label>
						</div>-->

                                <?php } ?>
                            </div>
                        <?php }
                    }
                    ################################################################
                    ?>
                    </div><?}?>
                <?php /* ?>
			<div class="mainSectionWrapper">
				<div class="sectionName">סרטוני יוטיוב</div>
				<div class="inputLblWrap">
					<div class="labelTo">סרטון 1</div>
					<input type="text" placeholder="" name="youtube1" value="<?=$siteData['youtube1']?>" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">סרטון 2</div>
					<input type="text" placeholder="" name="youtube2" value="<?=$siteData['youtube2']?>" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">סרטון 3</div>
					<input type="text" placeholder="" name="youtube3" value="<?=$siteData['youtube3']?>" />
				</div>
			</div>
<?php */ ?>
                <div class="mainSectionWrapper">
                    <div class="sectionName">הזמנות</div>
                    <div class="inputLblWrap">
                        <div class="switchTtl">הזמנות און ליין</div>
                        <label class="switch">
                            <input type="checkbox" name="onlineOrder" value="1" <?=($siteData['onlineOrder'] ? 'checked="checked"' : '')?>/>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="inputLblWrap">
                        <div class="inputLblWrap">
                            <div class="labelTo">Approval type</div>
                            <select name="orderApproveType">
                                <option value="0">באישור של בעל המקום</option>
                                <option value="1" <?=($siteData['orderApproveType'] == 1 ? 'selected="selected"' : '')?>>הזמנה מיידית</option>
                            </select>
                        </div>
                    </div>
                    <div class="inputLblWrap">
                        <div class="labelTo">זמן נקיון</div>
                        <select name="cleanGlobal">
                            <?php
                            foreach($cleanTime as $ci => $ctime)
                                echo '<option value="' , $ci , '" ' , ($ci == $siteData['cleanGlobal'] ? 'selected="selected"' : '') , '>' , $ctime , '</option>';
                            ?>
                        </select>
                    </div>

                    <?php /*
                <div class="catName">דרכי תשלום אפשריות</div>
				<div class="checksWrap">
					<?php foreach($paymentsOpt as $key => $pay) { ?>
					<div class="checkLabel checkIb">
						<div class="checkBoxWrap">
							<input class="checkBoxGr" <?=($siteData['paymentOpt'] & $key)?"checked":""?> type="checkbox" name="payopt[]"  value="<?=$key?>" id="pay<?=$key?>">
							<label for="pay<?=$key?>"></label>
						</div>
						<label for="pay<?=$key?>"><?=$pay?></label>
					</div>
					<?php } ?>
				</div>
*/ ?>

                    <div class="inputLblWrap" id="exSection">
                        <div class="inputLblWrap">
                            <div class="labelTo">מערכת יומן חיצונית :</div>
                            <select name="externalEngine">
                                <option value="">- - - - - - - -</option>
                                <?php
                                foreach($exEngines as $ev)
                                    echo '<option value="' , $ev , '" ' , (strcmp($ev, $siteData['externalEngine']) ? '' : 'selected="selected"') , '>' , $ev , '</option>';
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="inptLine">
                        <div class="inputLblWrap">
                            <div class="switchTtl">לקוח BizOnline</div>
                            <label class="switch">
                                <input type="checkbox" name="exBits[]" value="1" <?=($siteData['exBits'] & 1 ? 'checked="checked"' : '')?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap">
                            <div class="switchTtl">לקוח Booking</div>
                            <label class="switch">
                                <input type="checkbox" name="exBits[]" value="2" <?=($siteData['exBits'] & 2 ? 'checked="checked"' : '')?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap">
                            <div class="switchTtl">לקוח Airbnb</div>
                            <label class="switch">
                                <input type="checkbox" name="exBits[]" value="4" <?=($siteData['exBits'] & 4 ? 'checked="checked"' : '')?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="tableWrap">
                        <div class="rowWrap top">
                            <div class="tblCell" style="text-align:center;width:100px">אתר</div>
                            <div class="tblCell" style="text-align:center;width:500px">מפתח</div>
                        </div>
                        <div class="rowWrap">
                            <div class="tblCell" style="text-align:center;width:100px">Booking</div>
                            <div class="tblCell" style="width:500px"><input type="text" name="wu[booking]" value="<?=($wuKeys['booking'] ?? '')?>" style="width:100%" /></div>
                        </div>
                        <div class="rowWrap">
                            <div class="tblCell" style="text-align:center;width:100px">AirBnB</div>
                            <div class="tblCell" style="width:500px"><input type="text" name="wu[airbnb]" value="<?=($wuKeys['airbnb'] ?? '')?>" style="width:100%" /></div>
                        </div>
                    </div>

                    <div class="inputLblWrap" id="exSection">
                        <div class="inputLblWrap">
                            <div class="labelTo">בית מלון Protel :</div>
                            <select name="protelID">
                                <option value="">- - - - - - - -</option>
                                <?php
                                foreach($protelSites as $id => $name)
                                    echo '<option value="' , $id , '" ' , (($id == $siteData['protelID']) ? 'selected="selected"' : '') , '>' , $name , '</option>';
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="catName">דמי ביטול</div>
                    <div class="inputLblWrap" >
                        <div class="inputLblWrap">
                            <div class="labelTo">מקדמה באחוזים</div>
                            <input type="text" name="downPayment" value="<?=outDb($siteData['downPayment'])?>">
                        </div>
                    </div>
                    <?php
                    $cancelArray = current($siteCancelCond);
                    for($m=1;$m<=5;$m++) { ?>
                        <div class="cancelLine">
                            <span><?=$m?></span>
                            <input type="text" placeholder="כמות ימים" value="<?=strpos(key($siteCancelCond),"Warning") === false ? key($siteCancelCond) : ""?>" name="daysCancel[<?=$m?>]">
                            <input type="text" placeholder="עלות ביטול" value="<?=$cancelArray <= 1?$cancelArray*100:$cancelArray?>" name="costCancel[<?=$m?>]">
                            <select name="typeCancel[<?=$m?>]">
                                <option value="">-</option>
                                <option value="1" <?=$cancelArray > 1?"selected":""?>>₪</option>
                                <option value="2" <?=$cancelArray <= 1?"selected":""?>>%</option>
                            </select>
                        </div>
                        <?php $cancelArray = next($siteCancelCond);  } ?>

                </div>
                <div class="mainSectionWrapper">
                    <div class="sectionName">הצהרת בריאות</div>
                    <div class="inputLblWrap">
                        <div class="switchTtl">פעילה</div>
                        <label class="switch">
                            <input type="checkbox" name="healthActive" value="1" <?=($siteData['healthActive'] ? 'checked="checked"' : '')?>/>
                            <span class="slider round"></span>
                        </label>
                    </div>



                    <div class="section txtarea big <?=($siteData['healthText1Show']? "" : "noText" )?>  <?=($siteData['healthDefault1']? "default" : "" )?>">
                        <div class="inptLine">
                            <div class="label">טקסט 1</div>
                            <div class="inputLblWrap">
                                <div class="switchTtl">טקסט 1 מוצג</div>
                                <label class="switch">
                                    <input type="checkbox" onclick='$(this).closest(".section").toggleClass("noText");' name="healthText1Show" value="1" <?=($siteData['healthText1Show']||!$siteData['siteID'] ? 'checked="checked"' : '')?>/>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <div class="inputLblWrap defaultSwitch">
                                <div class="switchTtl">טקסט ברירת מחדל</div>
                                <label class="switch">
                                    <input type="checkbox" onclick='$(this).closest(".section").toggleClass("default");' name="healthDefault1" value="1" <?=($siteData['healthDefault1']||!$siteData['siteID'] ? 'checked="checked"' : '')?>/>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <textarea class="textEditor" name="healthText1"><?=$siteData['healthText1']?></textarea>
                            <div class="default_text"><?=udb::single_value("SELECT `html_text` FROM `MainPages_text` WHERE `MainPageID` = 88 AND `domainID` = 1 AND `langID` = 1")?></div>
                        </div>
                    </div>
                    <div class="questions_section">
                        <div class="inputLblWrap">
                            <div class="switchTtl">שאלון בריאות</div>
                            <label class="switch">
                                <input type="checkbox" name="healthQuestions2" value="1" <?=($siteData['healthQuestions2'] ? 'checked="checked"' : '')?>/>
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="section" style="width:100%">
                            <?
                            $siteQuestions = explode(",",$siteData["healthQList"]);

                            $que="SELECT * FROM `MainPages_text` LEFT JOIN `MainPages` USING (mainPageID)  WHERE MainPageType=107  AND MainPages_text.`domainID` = 1 AND MainPages_text.`langID` = 1 AND MainPages_text.ifShow = 1 ORDER BY `showOrder`";
                            $questions= udb::full_list($que);
                            foreach($questions as $question){?>
                                <div class="checkLabel" style="margin-bottom:10px">
                                    <div class="checkBoxWrap">
                                        <input class="checkBoxGr" type="checkbox" name="questions[]" <?=(in_array($question['mainPageID'],$siteQuestions)?"checked":"")?> value="<?=$question['mainPageID']?>" id="qs<?=$question['mainPageID']?>">
                                        <label for="qs<?=$question['mainPageID']?>"></label>
                                    </div>
                                    <label for="qs<?=$question['mainPageID']?>"><?=$question['mainPageTitle']?></label>
                                </div>
                            <?}
                            ?>

                        </div>
                    </div>
                    <div class="questions_section">
                        <div class="inputLblWrap">
                            <div class="switchTtl">שאלון קורונה</div>
                            <label class="switch">
                                <input type="checkbox" name="healthQuestions" value="1" <?=($siteData['healthQuestions'] ? 'checked="checked"' : '')?>/>
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="section" style="width:100%">
                            <?
                            $siteQuestions = explode(",",$siteData["healthQList"]);

                            $que="SELECT * FROM `MainPages_text` LEFT JOIN `MainPages` USING (mainPageID)  WHERE MainPageType=106  AND MainPages_text.`domainID` = 1 AND MainPages_text.`langID` = 1 AND MainPages_text.ifShow = 1 ORDER BY `showOrder`";
                            $questions= udb::full_list($que);
                            foreach($questions as $question){?>
                                <div class="checkLabel" style="margin-bottom:10px">
                                    <div class="checkBoxWrap">
                                        <input class="checkBoxGr" type="checkbox" name="questions[]" <?=(in_array($question['mainPageID'],$siteQuestions)?"checked":"")?> value="<?=$question['mainPageID']?>" id="qs<?=$question['mainPageID']?>">
                                        <label for="qs<?=$question['mainPageID']?>"></label>
                                    </div>
                                    <label for="qs<?=$question['mainPageID']?>"><?=$question['mainPageTitle']?></label>
                                </div>
                            <?}
                            ?>

                        </div>
                    </div>

                    <div class="section txtarea big <?=($siteData['healthText2Show']? "" : "noText" )?>  <?=($siteData['healthDefault2']? "default" : "" )?>">
                        <div class="inptLine">
                            <div class="label">טקסט 2</div>
                            <div class="inputLblWrap">
                                <div class="switchTtl">טקסט 2 מוצג</div>
                                <label class="switch">
                                    <input type="checkbox" onclick='$(this).closest(".section").toggleClass("noText");' name="healthText2Show" value="1" <?=($siteData['healthText2Show']||!$siteData['siteID'] ? 'checked="checked"' : '')?>/>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <div class="inputLblWrap defaultSwitch">
                                <div class="switchTtl">טקסט ברירת מחדל</div>
                                <label class="switch">
                                    <input type="checkbox" onclick='$(this).closest(".section").toggleClass("default");' name="healthDefault2" value="1" <?=($siteData['healthDefault2']||!$siteData['siteID'] ? 'checked="checked"' : '')?>/>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <textarea class="textEditor" name="healthText2"><?=$siteData['healthText2']?></textarea>
                            <div class="default_text"><?=udb::single_value("SELECT `html_text` FROM `MainPages_text` WHERE `MainPageID` = 89 AND `domainID` = 1 AND `langID` = 1")?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="abroadFormContainer" style="display: none;">
                <?if($domainID != 1 && $siteID) {?>
                    <div class="inputLblWrap" style="float: left">
                    <?
                    $linkToSite = "https://" . $domains[$domainID]['domainURL'] .  ActivePage::showAlias('sites', $siteID , 1 , $domainID) ;
                    $linkToSite = str_replace( "+", "_" , $linkToSite);
                    ?>
                    <a href="<?=$linkToSite?>" target="_blank">קישור לאתר</a>
                    </div><?}?>
                <input type="hidden" name="domid" value="<?=$domainID?>">
                <div class="inputLblWrap">
                    <div class="switchTtl">פעיל יומן</div>
                    <label class="switch">
                        <input type="checkbox" name="activeCal" value="1" <?=($siteData['active']==1 || !$siteID)?"checked":""?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="inputLblWrap">
                    <div class="switchTtl">רק יומן</div>
                    <label class="switch">
                        <input type="checkbox" name="calOnly" value="1" <?=($siteData['calendarOnly'] ? "checked" : "")?> />
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="mainSectionWrapper">
                    <div class="sectionName">כללי</div>
                    <?php

                    foreach(LangList::get() as $id => $lang){
                        if($siteID){
                            $trans[$id] = ($domainID == 1 && $id == 1) ? $siteData : Translation::sites($siteID, '*', $id, $domainID);
                            $btr[$id] = Translation::sites($siteID, '*', $id, Translation::DEFAULT_DOMAIN);
                        }
                        else {
                            $trans[$id] = [];
                            $btr[$id] = [];
                        }

                        ?>
                        <div class="language" data-id="<?=$id?>">
                            <div class="inputLblWrap">
                                <div class="labelTo">שם המתחם</div>
                                <input type="text" name="siteName" placeholder="<?=js_safe($btr[$id]['siteName'] ?? $siteData['siteName'] ?? 'שם המתחם')?>" value="<?=$trans[$id]['siteName'] ? js_safe($trans[$id]['siteName']) : js_safe($siteData['siteName']);?>" />
                            </div>


                            <div class="inputLblWrap">
                                <div class="labelTo">שם בעלים</div>
                                <input type="text" name="owners" placeholder="<?=js_safe($btr[$id]['owners'] ?? $siteData['owners'] ?? 'שם בעלים')?>"  value="<?=js_safe($trans[$id]['owners'])  ?? js_safe($siteData['owners'])?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">שם העסק</div>
                                <input type="text" placeholder="<?=js_safe($btr[$id]['bussinessName'] ?? $siteData['bussinessName'] ?? 'שם העסק')?>" name="bussinessName" value="<?=js_safe($trans[$id]['bussinessName']) ?? js_safe($siteData['bussinessName']) ?>" />
                            </div>
                            <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
                                <div class="section">
                                    <div class="inptLine">
                                        <div class="label">תמונת בעלים: </div>
                                        <input type="file" name="hostsPicture" class="inpt" value="<?=$siteData['hostsPicture']?>">
                                    </div>
                                </div>
                                <?php if($siteData['hostsPicture']){ ?>
                                    <div class="section">
                                        <div class="inptLine">
                                            <img src="<?=picturePath($siteData['hostsPicture'],"../../../gallery/")?>" style="width:100%">
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
                                <div class="section">
                                    <div class="inptLine">
                                        <div class="label">לוגו: </div>
                                        <input type="file" id="logoPicture" name="logoPicture" class="inpt" value="<?=$siteData['logoPicture']?>">
                                        <?if($siteData['logoPicture']){?>
                                            <div style="display:flex;align-items:center;clear:both">
                                                <input style="width:20px;height:20px" type="checkbox" name="logoPictureDel"> מחק תמונה
                                            </div>
                                        <?}?>
                                    </div>
                                </div>
                                <?php if($siteData['logoPicture']){ ?>
                                    <div class="section">
                                        <div class="inptLine">
                                            <img src="<?=picturePath($siteData['logoPicture'],"../../../gallery/")?>" style="width:100%">
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>


                    <? if($domainID == 1) { ?>
                        <div class="checkLabel checkIb ">
                            <div class="checkBoxWrap">
                                <?
                                $siteTypesArrays = [];
                                $siteTypesArrays[1] = [1,3,5,7,9,11,13,15];
                                $siteTypesArrays[2] = [2,3,6,7,10,11,14,15];
                                $siteTypesArrays[8] = [8,9,10,12,11,13,14,15];
                                $siteTypesArrays[4] = [4,5,6,7,12,13,14,15];
                                ?>
                                <input type="checkbox"
                                       name="siteTypeTemp"
                                    <?= (in_array($siteData['siteType'], $siteTypesArrays[1]) !== false ? 'checked' : '') ?>
                                       value="1"
                                       id="siteType1" />
                                <label for="siteType1"></label>
                            </div>
                            <label for="siteType1">צימר</label>

                            <div class="checkBoxWrap">
                                <input type="checkbox"
                                       name="siteTypeTemp"
                                    <?= (in_array($siteData['siteType'], $siteTypesArrays[2]) !== false ? 'checked' : '') ?>
                                       value="2"
                                       id="siteType2" />
                                <label for="siteType2"></label>
                            </div>
                            <label for="siteType2">ספא</label>

                            <div class="checkBoxWrap">
                                <input type="checkbox"
                                       name="siteTypeTemp"
                                    <?= (in_array($siteData['siteType'], $siteTypesArrays[8]) !== false ? 'checked' : '') ?>
                                       value="8"
                                       id="siteType3" />
                                <label for="siteType3"></label>
                            </div>
                            <label for="siteType3">אירועים</label>

                            <div class="checkBoxWrap">
                                <input type="checkbox"
                                       name="siteTypeTemp"
                                    <?= (in_array($siteData['siteType'], $siteTypesArrays[4]) !== false ? 'checked' : '') ?>
                                       value="4"
                                       id="siteType4" />
                                <label for="siteType4"></label>
                            </div>
                            <label for="siteType4">ח.לשעה</label>

                            <input type="hidden"
                                   name="siteType"
                                   id="siteType"
                                   value="<?= $siteData['siteType'] ?>">

                            <script>
                                // Keeps original code for summing siteType
                                $('input[name="siteTypeTemp"]').off().on("change", function(){
                                    var total = 0;
                                    $('input[name="siteTypeTemp"]').each(function(){
                                        if($(this).is(":checked")) {
                                            total += parseInt($(this).val());
                                        }
                                    });
                                    $("#siteType").val(total);
                                });
                            </script>

                            <!-- כפתור הזמנות אונליין (initially hidden) -->
                            <div class="inputLblWrap online-wrap" style="display: none;">
                                <div class="labelTo">כפתור הזמנות אונליין</div>
                                <select name="online_order">
                                    <option value="0" selected>ללא</option>
                                    <option value="1">ביזפופ</option>
                                    <option value="2">ספא פלוס</option>
                                </select>
                            </div>
                        </div>
                    <? } ?>
                    <script>
                        $(function(){
                            function toggleOnlineWrap() {
                                // Show .online-wrap only if ספא is checked
                                if ($('#siteType2').is(':checked')) {
                                    $('.online-wrap').show();
                                } else {
                                    $('.online-wrap').hide();
                                }
                            }

                            // Run on page load
                            toggleOnlineWrap();

                            // Bind change event to ספא checkbox
                            $('#siteType2').on('change', function() {
                                toggleOnlineWrap();
                            });
                        });
                    </script>


                    <?php

                    if (!$siteID){
                        ?>
                        <div class="inputLblWrap">
                            <div class="labelTo">שם משתמש<span class="checkBtn" onClick="checkFree($('#userName').val())">בדוק אם פנוי</span></div>
                            <input type="text" placeholder="שם משתמש" name="userName" id="userName" value="<?=$siteData['userName']?>" />
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">סיסמא</div>
                            <input type="text" placeholder="<?=($siteData['password'] ? '*********' : 'סיסמא')?>" name="password" id="password" value="" />
                        </div>
                        <?php
                    }
                    ?>
                    <div class="inputLblWrap">
                        <div class="labelTo">אימייל</div>
                        <input type="text" placeholder="אימייל" name="email" value="<?=$siteData['email']?>" />
                    </div>

                    <div class="inputLblWrap">
                        <div class="labelTo">שטח המתחם</div>
                        <input type="text" placeholder='שטח המתחם' name="compSize" value="<?=js_safe($siteData['compSize'])?>" />
                    </div>

                    <?php
                    foreach([$domainID => ''] as $id => $dom){ ?>
                        <div class="domain" data-id="<?=$id?>">
                            <?if($id != 1) {?>
                                <div class="inputLblWrap">
                                    <div class="labelTo">סה"כ כמות חדרי שינה</div>
                                    <input type="text" placeholder='סה"כ כמות חדרי שינה' name="bedroomCount" value="<?=$siteDomains[$id]['bedroomCount']?>" />
                                </div>
                                <div class="inputLblWrap">
                                <div class="labelTo">סה"כ מקלחות</div>
                                <input type="text" placeholder='סה"כ מקלחות' name="bathroomCount" value="<?=$siteDomains[$id]['bathroomCount']?>" />
                                </div><?}?>
                            <div class="inputLblWrap">
                                <div class="labelTo">טלפון</div>
                                <input type="text" placeholder="טלפון" name="phone" value="<?=js_safe($siteDomains[$id]['phone'])?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">טלפון 2</div>
                                <input type="text" placeholder="טלפון נוסף" name="phone2" value="<?=js_safe($siteDomains[$id]['phone2'])?>" />
                            </div>
                            <div class="inputLblWrap" <?=$id == 1 ? ' style="display:none" ' : '';?>>
                                <div class="switchTtl">פעיל </div>
                                <label class="switch">
                                    <input type="checkbox" name="active" value="1" <?=($siteID ? '' : 'checked="checked"')?> <?=($siteDomains[$id]['active'] ? 'checked="checked"' : '')?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <div class="inputLblWrap" <?=$id == 1 ? ' style="display:none" ' : '';?>>
                                <div class="switchTtl">הסתר אמצעי התקשרות <span style="display:block;"></span>(מסתיר בנוסף גם בתוצאות חיפוש)</span></div>
                                <label class="switch">
                                    <input type="checkbox" name="hideContactMethods" value="1"  <?=($siteDomains[$id]['hideContactMethods'] ? 'checked="checked"' : '')?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>


                            <div class="inputLblWrap" style="display:none">
                                <div class="switchTtl">כמות מבוגרים ילדים</div>
                                <label class="switch">
                                    <input type="checkbox" name="showKidsAndAdults" value="1" <?=($siteDomains[$domainID]['showKidsAndAdults']==1 || !$siteID ? 'checked="checked"' : '')?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="inputLblWrap" style="display:none">
                                <div class="switchTtl">הפק חשבוניות</div>
                                <label class="switch">
                                    <input type="checkbox" name="invoice" value="1"  <?=($siteDomains[$id]['invoice'] ? 'checked="checked"' : '')?> <?=($siteData['invoice']==1 && $id==0)?"checked":""?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="inputLblWrap">
                                <div class="labelTo">טלפון למיסוך</div>
                                <input type="text" placeholder="טלפון למיסוך" name="maskyooPhone" value="<?=$siteDomains[$id]['maskyooPhone']?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">סולולארי להודעות</div>
                                <input type="text" placeholder="סולולארי להודעות" name="phoneSms" value="<?=$siteDomains[$id]['phoneSms']?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">סולולארי לווטסאפ</div>
                                <input type="text" placeholder="סולולארי לווטסאפ" name="whatsappPhone" value="<?=$siteDomains[$id]['whatsappPhone']?>" />
                            </div>
                            <div class="inputLblWrap" <?=$id != 1 ? ' style="display:none" ' : '';?>>
                                <div class="labelTo">טלפון לעדכוני הזמנה</div>
                                <span style="width:160px;display:block">מספר זה יחליף את הטלפון למיסוך במסך חתימת הזמנה</span>
                                <input type="text" placeholder="טלפון לעדכוני הזמנה" name="orderEditPhone" value="<?=$siteDomains[$id]['orderEditPhone']?>" />
                            </div>
                            <?
                            if($domainID != 1) {
                                ?>

                                <div class="inputLblWrap" style="display:none">
                                    <div class="labelTo">משפט מארחים</div>
                                    <input type="text" placeholder="משפט מארחים" name="hostPhrase" value="<?=$siteDomains[$id]['hostPhrase']?>" />
                                </div>
                                <div class="inputLblWrap" style="display:none">
                                    <div class="labelTo">נבדק בתאריך</div>
                                    <input type="text" placeholder="נבדק בתאריך" class="datepicker" name="checkedDate" value="<?=$siteDomains[$id]['checkedDate']?>" />
                                </div>
                                <div class="inputLblWrap" style="display:none">
                                <div class="labelTo">נבדק על ידי</div>
                                <input type="text" placeholder="נבדק על ידי" name="checkedBy" value="<?=$siteDomains[$id]['checkedBy']?>" />
                                </div><?}?>
                            <div class="inputLblWrap" style="display:none">
                                <div class="switchTtl">אמנת שירות</div>
                                <label class="switch">
                                    <input type="checkbox" name="ServiceLevelAgreement" value="1" <?=($siteDomains[$id]['ServiceLevelAgreement'] ? 'checked="checked"' : '')?> <?=($siteData['ServiceLevelAgreement']==1 && $id==0)?"checked":""?> />
                                    <span class="slider round"></span>
                                </label>
                            </div>



                            <?php foreach(LangList::get() as $lid => $lang){

                                if($siteid) {
                                    $trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[$domainID][$lid] : Translation::sites($siteID, '*', $lid, $domainID);
                                    $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                                }
                                ?>
                                <div class="language"  data-id="<?=$lid?>">
                                    <div class="section txtarea big" style="display:none !important;">
                                        <div class="label">תיאור קצר: </div>
                                        <textarea name="shortDesc" class="shortextEditor" title=""><?=$trans[$lid]['shortDesc']?></textarea>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
                <?
                if($domainID != 1 || $siteData['siteType'] & 2) {
                    ?>
                    <div class="mainSectionWrapper">
                        <div class="sectionName">תאור המתחם</div>
                        <?php if($domainID == 6 ) {?><a class="pullFromBiz" style="background:#2FC2EB;color:#FFFFFF;font-size: 16px;padding: 0 10px;cursor: pointer; ">משוך מביז</a><?php }?>
                        <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
                            <?php
                            foreach(LangList::get() as $lid => $lang){


                                //$trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[1][1] : Translation::sites($siteID, '*', $lid, $domainID);
                                $trans[$lid] = Translation::sites($siteID, '*', $lid, $domainID);
//                            if($trans[$lid]['reviewLocation'] !=' ' && !$trans[$lid]['reviewLocation'] ) {
//                                $trans[$lid] = Translation::sites($siteID, '*', $lid, 1);
//                            }
//                            if($trans[$lid]['reviewLocation'] !=' ' && !$trans[$lid]['reviewLocation'] ) {
//                                $trans[$lid] =  $siteLangs[$lid][$domainID];
//                                if($trans[$lid]['reviewLocation'] !=' ' && !$trans[$lid]['reviewLocation'] ) {
//                                    $trans[$lid] =  $siteLangs[$lid][1];
//
//                                }
//                            }

                                $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                                ?>
                                <div class="domain" data-id="<?=$domainID?>">
                                    <div class="language" data-id="<?=$lid?>">
                                        <div class="section txtarea big">
                                            <div class="inptLine">
                                                <div class="label noFloat">תאור המתחם: </div>
                                                <textarea class="textEditor" name="reviewLocation"><?=outDb($trans[$lid]['reviewLocation'])?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?  } ?>
                        </div>
                    </div>
                    <?
                }?>

                <?if($domainID != 1) {?>
                    <div class="mainSectionWrapper">
                    <div class="sectionName">SEO</div>
                    <?php
                    $seoDataFromDestionation = [];
                    if($siteID) {
                        switch($domainID) {
                            case 6:
                                $seoDataFromDestionation = galGetUrl("https://www." . $domains[$domainID]['domainURL'] . "/viiapi.php?key=bizonline1025&type=seo&siteID=".$siteID);
                                if(!is_array($seoDataFromDestionation))
                                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                                break;
                            case 105:
                            case 106:
                            case 107:
                            case 108:
                            case 109:
                                $domains[$domainID]['domainURL'] = "www." . $domains[$domainID]['domainURL'];
                            case 111:
                                $seoDataFromDestionation = galGetUrl("https://ssd:ssdSSD1234!@loftland2.c-ssd.com/byhoursapi.php?key=bizonline1025&type=seo&siteID=".$siteID);
                                if(!is_array($seoDataFromDestionation))
                                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                                break;
                            case 10:
                                $seoDataFromDestionation = galGetUrl("https://" . $domains[$domainID]['domainURL'] ."/byhoursapi.php?key=bizonline1025&type=seo&siteID=".$siteID);
                                if(!is_array($seoDataFromDestionation))
                                    $seoDataFromDestionation = json_decode($seoDataFromDestionation,true);
                                break;
                        }
                    }
                    foreach(LangList::get() as $lid => $lang){ ?>
                        <div class="domain" data-id="<?=$domainID?>">
                            <div class="language" data-id="<?=$lid?>">
                                <div class="inputLblWrap">
                                    <div class="labelTo">כתובת הדף</div>
                                    <input type="text" placeholder="כותרת עמוד" name="level2" value="<?=outDb($seo[$domainID][$lid]['LEVEL2'])?>"  />
                                </div>
                                <div class="inputLblWrap">
                                    <div class="labelTo">כותרת עמוד</div>
                                    <input type="text" placeholder="כותרת עמוד" title="<?=$seoDataFromDestionation['data']['title']?>" name="title" value="<?=outDb($seo[$domainID][$lid]['title'])?>" />
                                    <div onclick="$(this).parent().find('input').val($(this).val())" title="לחץ להעתקה" style="max-height:120px;overflow:auto;background:#fff;border:1px #ccc solid;padding:10px;cursor:pointer"><?=$seoDataFromDestionation['data']['title']?></div>
                                </div>
                                <div class="inputLblWrap" style="display: none">
                                    <div class="labelTo">H1</div>
                                    <input type="text" placeholder="H1" name="h1" value="<?=outDb($seo[$domainID][$lid]['h1'])?>" />
                                </div>
                                <!-- <div class="inputLblWrap">
                                    <div class="labelTo">קישור</div>
                                    <input type="text" placeholder="קישור" name="link" value="" />
                                </div> -->
                                <div class="section txtarea">
                                    <div class="inptLine">
                                        <div class="label">מילות מפתח</div>
                                        <textarea name="seoKeyword" title="<?=$seoDataFromDestionation['data']['keywords']?>"><?=outDb($seo[$domainID][$lid]['keywords'])?></textarea>
                                        <div onclick="$(this).parent().find('textarea').html($(this).html())" title="לחץ להעתקה" style="max-height:120px;overflow:auto;background:#fff;border:1px #ccc solid;padding:10px;cursor:pointer"><?=$seoDataFromDestionation['data']['keywords']?></div>
                                    </div>
                                </div>
                                <div class="section txtarea">
                                    <div class="inptLine">
                                        <div class="label">תאור דף</div>
                                        <textarea name="seoDesc" title="<?=$seoDataFromDestionation['data']['description']?>"><?=outDb($seo[$domainID][$lid]['description'])?></textarea>
                                        <div onclick="$(this).parent().find('textarea').html($(this).html())" title="לחץ להעתקה" style="max-height:120px;overflow:auto;background:#fff;border:1px #ccc solid;padding:10px;cursor:pointer"><?=$seoDataFromDestionation['data']['description']?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    </div><?}?>


                <?if($domainID == 1) {?>
                    <div class="mainSectionWrapper">
                        <div class="sectionName">מיקום</div>
                        <div class="inSectionWrap">

                            <div class="stateInputForUSA" style="display: none">
                                <div class="inputLblWrap">
                                    <div class="labelTo">מדינה</div>
                                    <select name="state" title="State">
                                        <option value="0">- - בחר מדינה - -</option>

                                    </select>
                                </div>
                            </div>

                            <div class="inputLblWrap">
                                <div class="labelTo">עיר</div>
                                <select class='citySelectorForAbroad' name="city" title="עיר">
                                    <option value="0">- - בחר עיר - -</option>

                                </select>
                            </div>

                            <div class="inputLblWrap">
                                <div class="labelTo">כתובת</div>
                                <?php
                                foreach(LangList::get() as $id => $lang){

                                    if($siteID) {
                                        $trans[$id] = ($domainID == 1 && $id == 1) ? $siteLangs[$domainID][$id] : Translation::sites($siteID, '*', $id, $domainID);
                                        $btr[$id] = Translation::sites($siteID, '*', $id, Translation::DEFAULT_DOMAIN);
                                        $useAddress[$id] = js_safe($trans[$id]['address']);
                                    }
                                    else {
                                        $trans[$lid] = [];
                                        $btr[$lid] = [];
                                        $useAddress[$id] = "";
                                    }


                                    ?>
                                    <div class="language" data-id="<?=$id?>">
                                        <input type="text" placeholder="<?=js_safe($btr[$id]['address'] ?? $siteData['address'] ?? 'כתובת')?>" name="address" value="<?=js_safe($trans[$id]['address'])?>" />
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>


                            <? $didNotHaveLatLng = false; //                        if($siteID && (!$siteData['gpsLat'] || !$siteData['gpsLong']) && $cityName) {
                            //                            $searchAddress =  $useAddress[1] ? $useAddress[1] ."," : '' . $cityName;
                            //
                            //                            $didNotHaveLatLng = true;
                            //                            $latlng = getLocationNumbers($searchAddress);
                            //                            $siteData['gpsLat'] = $latlng['lat'];
                            //                            $siteData['gpsLong'] = $latlng['long'];
                            //                            udb::update("sites",$siteData," siteID=".$siteID);
                            //                            $didNotHaveLatLng = true;
                            //
                            //                        ?>

                            <div class="clear"><?//=$searchAddress?></div>
                            <div class="inputLblWrap">
                                <div class="labelTo">GPS Lat</div>
                                <input type="text" placeholder="Lat" name="gpsLat" value="<?=$siteData['gpsLat']?>" />
                            </div>
                            <div class="inputLblWrap">
                                <div class="labelTo">GPS Long</div>
                                <input type="text" placeholder="Long" name="gpsLong" value="<?=$siteData['gpsLong']?>" />
                            </div>
                            <?if($didNotHaveLatLng === true) {
                                ?>
                                <div class="inputLblWrap" style="color: #FF0000;">
                                    קורדינטות חדשות אותרו<BR>
                                    יש לבצע שמירה לשמירת קורדינטות המקום
                                </div>
                            <?}?>
                            <div class="inputLblWrap">
                                <div class="labelTo">טען תמונת מפה מחדש</div>
                                <input type="checkbox"  name="reloadgooglemap" value="1" <?=($didNotHaveLatLng === true) ? ' checked '  : ''?> />
                            </div>
                        </div>
                    </div>
                <?}?>

                <div class="mainSectionWrapper">
                    <div class="sectionName">שעות כניסה יציאה והסכמי הזמנה</div>

                    <div class="inputLblWrap">
                        <div class="labelTo">שעת כניסה</div>
                        <input type="time" class="timepicker" placeholder="שעת כניסה" name="checkInHour" value="<?=$siteData['checkInHour']?$siteData['checkInHour']:"15:00"?>" />
                    </div>
                    <div class="inputLblWrap">
                        <div class="labelTo">שעת יציאה</div>
                        <input type="time" class="timepicker" placeholder="שעת יציאה" name="checkOutHour" value="<?=$siteData['checkOutHour']?$siteData['checkOutHour']:"11:00"?>" />
                    </div>

                    <?php
                    /*******************************************************************************
                     *    Removed all שבת-related fields. Only one block remains, containing
                     *    the 4 agreements and default selection radio buttons.
                     ******************************************************************************/

                    foreach (LangList::get() as $lid => $lang) {

                        // Same logic as before for loading translations
                        if ($siteID) {
                            // For each language $lid
                            // load the site translation from the relevant domain
                            $trans[$lid] = Translation::sites($siteID, '*', $lid, $domainID);
                            // load the base translation from the default domain, if needed
                            $btr[$lid]   = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                        } else {
                            $trans[$lid] = [];
                            $btr[$lid]   = [];
                        }
                        ?>

                        <div class="domain" data-id="<?=$domainID?>">
                            <div class="language" data-id="<?=$lid?>">

                                <!-- Agreement 1 -->
                                <div class="section txtarea big">
                                    <div class="inptLine">
                    <textarea
                            style="display:none"
                            class="textEditor"
                            name="hostInclude"
                    ><?=outDb($defaultAgr)?></textarea>

                                        <div class="label noFloat">הסכם הזמנה 1</div>
                                        <div class="textEditorShow"><?=outDb($defaultAgr)?></div>
                                    </div>

                                    <div class="radioWrap">
                                        <input
                                                type="radio"
                                                name="defaultAgr"
                                                value="1"
                                                id="defaultAgr1<?=$domainID.$lid?>"
                                            <?php
                                            // If no siteID is set, OR the DB says '1', mark as checked
                                            if ($trans[$lid]['defaultAgr'] == 1 || !$siteID) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                        >
                                        <label for="defaultAgr1<?=$domainID.$lid?>">
                                            בחר הסכם זה כברירת מחדל
                                        </label>
                                    </div>
                                </div>

                                <!-- Agreement 2 -->
                                <div class="section txtarea big">
                                    <div class="inptLine">
                                        <div class="label noFloat">הסכם הזמנה 2</div>
                                        <textarea
                                                class="textEditor"
                                                name="cancellation"
                                        ><?=outDb($trans[$lid]['agreement2'])?></textarea>
                                    </div>

                                    <div class="radioWrap">
                                        <input
                                                type="radio"
                                                name="defaultAgr"
                                                value="2"
                                                id="defaultAgr2<?=$domainID.$lid?>"
                                            <?php
                                            if ($trans[$lid]['defaultAgr'] == 2) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                        >
                                        <label for="defaultAgr2<?=$domainID.$lid?>">
                                            בחר הסכם זה כברירת מחדל
                                        </label>
                                    </div>
                                </div>

                                <!-- Agreement 3 -->
                                <div class="section txtarea big">
                                    <div class="inptLine">
                                        <div class="label noFloat">הסכם הזמנה 3</div>
                                        <textarea
                                                class="textEditor"
                                                name="orderTerms"
                                        ><?=outDb($trans[$lid]['agreement3'])?></textarea>
                                    </div>

                                    <div class="radioWrap">
                                        <input
                                                type="radio"
                                                name="defaultAgr"
                                                value="3"
                                                id="defaultAgr3<?=$domainID.$lid?>"
                                            <?php
                                            if ($trans[$lid]['defaultAgr'] == 3) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                        >
                                        <label for="defaultAgr3<?=$domainID.$lid?>">
                                            בחר הסכם זה כברירת מחדל
                                        </label>
                                    </div>
                                </div>

                                <!-- Agreement 4 -->
                                <div class="section txtarea big">
                                    <div class="inptLine">
                                        <div class="label noFloat">הסכם הזמנה 4</div>
                                        <textarea
                                                class="textEditor"
                                                name="agreement4"
                                        ><?=outDb($trans[$lid]['agreement4'])?></textarea>
                                    </div>

                                    <div class="radioWrap">
                                        <input
                                                type="radio"
                                                name="defaultAgr"
                                                value="4"
                                                id="defaultAgr4<?=$domainID.$lid?>"
                                            <?php
                                            if ($trans[$lid]['defaultAgr'] == 4) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                        >
                                        <label for="defaultAgr4<?=$domainID.$lid?>">
                                            בחר הסכם זה כברירת מחדל
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <?php
                    }
                    ?>




                </div>

                <div class="mainSectionWrapper">
                    <div class="sectionName">מסוף</div>
                    <div class="inSectionWrap">
                        <div class="inputLblWrap">
                            <div class="labelTo">סוג מסוף</div>
                            <select name="masof_type" title="סוג מסוף">
                                <option value="">- - - - - - - - - - - - - - -</option>
                                <option value="stripe" <?=($siteData['masof_type'] == 'stripe' ? 'selected' : '')?>>Stripe</option>
                                <option value="jcc" <?=($siteData['masof_type'] == 'jcc' ? 'selected' : '')?>>JCC</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin:-30px 36px 0 0; font-size:smaller">* בשלבי הקמה עבור מערכת חו"ל *</div>

                </div>

                <!-- (הגדרות מערכת block removed entirely at your request) -->

                    <?if($domainID == 1) {?>
                        <div class="mainSectionWrapper">
                            <div class="sectionName">הסכם מערכת המתחם</div>
                            <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
                                <?php
                                foreach(LangList::get() as $lid => $lang){
                                    if($siteid) {
                                        $trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[1][1] : Translation::sites($siteID, '*', $lid, $domainID);
                                        $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                                    }
                                    ?>
                                    <div class="domain" data-id="<?=$domainID?>">
                                        <div class="language" data-id="<?=$lid?>">
                                            <div class="section txtarea big">
                                                <div class="inptLine">
                                                    <div class="label noFloat">הסכם מערכת המתחם: </div>
                                                    <textarea class="textEditor" name="minisiteAgree"><?php
                                                        if($trans[$lid]['minisiteAgree']) {
                                                            echo outDb($trans[$lid]['minisiteAgree']);
                                                        }  else {
                                                            $que = "SELECT * FROM MainPages LEFT JOIN MainPages_text USING (MainPageID) WHERE MainPageType=2 AND MainPageID=18";
                                                            $ppage = udb::single_row($que);
                                                            echo outDb($ppage['html_text']);
                                                        }
                                                        ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?  }
                                ?>
                            </div>
                        </div>
                    <?}?>

                    <!-- (הסכם Vouchers המתחם block removed entirely at your request) -->

                    <?if($domainID == 1) {?>
                        <div class="mainSectionWrapper">
                            <div class="sectionName">אמנת שירות המתחם</div>
                            <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
                                <?php
                                foreach(LangList::get() as $lid => $lang){
                                    if($siteID) {
                                        $trans[$lid] = ($domainID == 1 && $lid == 1) ? $siteLangs[1][1] : Translation::sites($siteID, '*', $lid, $domainID);
                                        $btr[$lid] = Translation::sites($siteID, '*', $lid, Translation::DEFAULT_DOMAIN);
                                    }
                                    ?>
                                    <div class="domain" data-id="<?=$domainID?>">
                                        <div class="language" data-id="<?=$lid?>">
                                            <div class="section txtarea big">
                                                <div class="inptLine">
                                                    <div class="label noFloat">אמנת שירות המתחם: </div>
                                                    <textarea class="textEditor" name="minisiteSAgree"><?php
                                                        if($trans[$lid]['minisiteSAgree']) {
                                                            echo outDb($trans[$lid]['minisiteSAgree']);
                                                        }  else {
                                                            $que = "SELECT * FROM MainPages LEFT JOIN MainPages_text USING (MainPageID) WHERE MainPageType=103 AND `MainPages_text`.langID = 1 AND `MainPages_text`.domainID = 1";
                                                            $ppage = udb::full_list($que);
                                                            $pp = 0;
                                                            foreach($ppage as $page) { $pp++; ?>
                                                                <li style="margin-bottom:20px">
                                                                <?if($pp>1){?><b><?=$page['mainPageTitle']?></b><br><?}?>
                                                                <?=$page['html_text'];?>
                                                                </li>
                                                            <?}
                                                        }
                                                        ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?  } ?>
                            </div>
                        </div>
                    <?}?>

                    <?if($domainID != 1) {?>
                        <div class="mainSectionWrapper">
                            <div class="sectionName">מאפיינים ראשיים</div>
                            <div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
                                <?php
                                foreach(LangList::get() as $lid => $lang){
                                    if($siteID) {
                                        $trans[$lid] = Translation::sites($siteID, '*', $lid, $domainID);
                                    }
                                    $btr[$lid] = Translation::sites($siteID, '*', $lid, 1);
                                    ?>
                                    <div class="domain" data-id="<?=$domainID?>">
                                        <div class="language" data-id="<?=$lid?>">
                                            <?for($attI = 1; $attI <=5;$attI++) {?>
                                                <div class="inputLblWrap">
                                                    <div class="inptLine">
                                                        <div class="label noFloat">כותרת <?=$attI?>: </div>
                                                        <input type="text" placeholder="<?=js_safe($btr[$id]['attr'.$attI] ?? $siteData['attr'.$attI] ?? 'כותרת '.$attI)?>" name="attr<?=$attI?>" value="<?=outDb(htmlspecialchars($trans[$lid]['attr'.$attI], ENT_QUOTES, 'UTF-8'))?>" />
                                                    </div>
                                                </div>
                                            <?}?>
                                        </div>
                                    </div>
                                <?  } ?>
                            </div>
                        </div>
                    <?}?>


                    <div class="mainSectionWrapper">
                        <div class="sectionName">הזמנות</div>

                        <div class="inputLblWrap">
                            <div class="labelTo">זמן נקיון</div>
                            <select name="cleanGlobal">
                                <?php
                                foreach($cleanTime as $ci => $ctime)
                                    echo '<option value="' , $ci , '" ' , ($ci == $siteData['cleanGlobal'] ? 'selected="selected"' : '') , '>' , $ctime , '</option>';
                                ?>
                            </select>
                        </div>

                        <div class="catName">דמי ביטול</div>
                        <div class="inputLblWrap" >
                            <div class="inputLblWrap">
                                <div class="labelTo">מקדמה באחוזים</div>
                                <input type="text" name="downPayment" value="<?=outDb($siteData['downPayment'])?>">
                            </div>
                        </div>
                        <?php
                        $cancelArray = current($siteCancelCond);
                        for($m=1;$m<=5;$m++) { ?>
                            <div class="cancelLine">
                                <span><?=$m?></span>
                                <input type="text" placeholder="כמות ימים" value="<?=strpos(key($siteCancelCond),"Warning") === false ? key($siteCancelCond) : ""?>" name="daysCancel[<?=$m?>]">
                                <input type="text" placeholder="עלות ביטול" value="<?=$cancelArray <= 1?$cancelArray*100:$cancelArray?>" name="costCancel[<?=$m?>]">
                                <select name="typeCancel[<?=$m?>]">
                                    <option value="">-</option>
                                    <option value="1" <?=$cancelArray <= 1?"selected":""?>>%</option>
                                </select>
                            </div>
                            <?php $cancelArray = next($siteCancelCond);
                        } ?>
                    </div>

                    <div class="mainSectionWrapper">
                        <div class="sectionName">הצהרת בריאות</div>
                        <div class="inputLblWrap">
                            <div class="switchTtl">פעילה</div>
                            <label class="switch">
                                <input type="checkbox" name="healthActive" value="1" <?=($siteData['healthActive'] ? 'checked="checked"' : '')?>/>
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="section txtarea big <?=($siteData['healthText1Show']? "" : "noText" )?>  <?=($siteData['healthDefault1']? "default" : "" )?>">
                            <div class="inptLine">
                                <div class="label">טקסט 1</div>
                                <div class="inputLblWrap">
                                    <div class="switchTtl">טקסט 1 מוצג</div>
                                    <label class="switch">
                                        <input type="checkbox" onclick='$(this).closest(".section").toggleClass("noText");' name="healthText1Show" value="1" <?=($siteData['healthText1Show']||!$siteData['siteID'] ? 'checked="checked"' : '')?>/>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div class="inputLblWrap defaultSwitch">
                                    <div class="switchTtl">טקסט ברירת מחדל</div>
                                    <label class="switch">
                                        <input type="checkbox" onclick='$(this).closest(".section").toggleClass("default");' name="healthDefault1" value="1" <?=($siteData['healthDefault1']||!$siteID ? 'checked="checked"' : '')?>/>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <textarea class="textEditor" name="healthText1"><?=$siteData['healthText1']?></textarea>
                                <div class="default_text"><?=udb::single_value("SELECT `html_text` FROM `MainPages_text` WHERE `MainPageID` = 88 AND `domainID` = 1 AND `langID` = 1")?></div>
                            </div>
                        </div>

                        <div class="questions_section">
                            <div class="inputLblWrap">
                                <div class="switchTtl">שאלון בריאות</div>
                                <label class="switch">
                                    <input type="checkbox" name="healthQuestions2" value="1" <?=($siteData['healthQuestions2'] ? 'checked="checked"' : '')?>/>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="section" style="width:100%">
                                <?
                                $siteQuestions = explode(",",$siteData["healthQList"]);
                                $que="SELECT * FROM `MainPages_text` LEFT JOIN `MainPages` USING (mainPageID)  WHERE MainPageType=107  AND MainPages_text.`domainID` = 1 AND MainPages_text.`langID` = 1 AND MainPages_text.ifShow = 1 ORDER BY `showOrder`";
                                $questions= udb::full_list($que);
                                foreach($questions as $question){?>
                                    <div class="checkLabel" style="margin-bottom:10px">
                                        <div class="checkBoxWrap">
                                            <input class="checkBoxGr" type="checkbox" name="questions[]" <?=(in_array($question['mainPageID'],$siteQuestions)?"checked":"")?> value="<?=$question['mainPageID']?>" id="qs<?=$question['mainPageID']?>">
                                            <label for="qs<?=$question['mainPageID']?>"></label>
                                        </div>
                                        <label for="qs<?=$question['mainPageID']?>"><?=$question['mainPageTitle']?></label>
                                    </div>
                                <?}?>
                            </div>
                        </div>

                        <div class="questions_section">
                            <div class="inputLblWrap">
                                <div class="switchTtl">שאלון קורונה</div>
                                <label class="switch">
                                    <input type="checkbox" name="healthQuestions" value="1" <?=($siteData['healthQuestions'] ? 'checked="checked"' : '')?>/>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="section" style="width:100%">
                                <?
                                $siteQuestions = explode(",",$siteData["healthQList"]);
                                $que="SELECT * FROM `MainPages_text` LEFT JOIN `MainPages` USING (mainPageID)  WHERE MainPageType=106  AND MainPages_text.`domainID` = 1 AND MainPages_text.`langID` = 1 AND MainPages_text.ifShow = 1 ORDER BY `showOrder`";
                                $questions= udb::full_list($que);
                                foreach($questions as $question){?>
                                    <div class="checkLabel" style="margin-bottom:10px">
                                        <div class="checkBoxWrap">
                                            <input class="checkBoxGr" type="checkbox" name="questions[]" <?=(in_array($question['mainPageID'],$siteQuestions)?"checked":"")?> value="<?=$question['mainPageID']?>" id="qs<?=$question['mainPageID']?>">
                                            <label for="qs<?=$question['mainPageID']?>"></label>
                                        </div>
                                        <label for="qs<?=$question['mainPageID']?>"><?=$question['mainPageTitle']?></label>
                                    </div>
                                <?}?>
                            </div>
                        </div>

                        <div class="section txtarea big <?=($siteData['healthText2Show']? "" : "noText" )?>  <?=($siteData['healthDefault2']? "default" : "" )?>">
                            <div class="inptLine">
                                <div class="label">טקסט 2</div>
                                <div class="inputLblWrap">
                                    <div class="switchTtl">טקסט 2 מוצג</div>
                                    <label class="switch">
                                        <input type="checkbox" onclick='$(this).closest(".section").toggleClass("noText");' name="healthText2Show" value="1" <?=($siteData['healthText2Show']||!$siteID ? 'checked="checked"' : '')?>/>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div class="inputLblWrap defaultSwitch">
                                    <div class="switchTtl">טקסט ברירת מחדל</div>
                                    <label class="switch">
                                        <input type="checkbox" onclick='$(this).closest(".section").toggleClass("default");' name="healthDefault2" value="1" <?=($siteData['healthDefault2']||!$siteID ? 'checked="checked"' : '')?>/>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <textarea class="textEditor" name="healthText2"><?=$siteData['healthText2']?></textarea>
                                <div class="default_text"><?=udb::single_value("SELECT `html_text` FROM `MainPages_text` WHERE `MainPageID` = 89 AND `domainID` = 1 AND `langID` = 1")?></div>
                            </div>
                        </div>
                    </div>


            </div>

            <input type="submit" value="שמור" class="submit">
        </form>
        <script>
            // -----------------------------------------
            // NEW HELPER FUNCTIONS TO CLEAR/POPULATE
            // -----------------------------------------
            function clearSelectOptions(selectEl, placeholderText) {
                selectEl.innerHTML = "";
                if (placeholderText) {
                    let opt = document.createElement("option");
                    opt.value = "0";
                    opt.textContent = placeholderText;
                    selectEl.appendChild(opt);
                }
            }

            // For a chosen country, fill the “State” select with states from locData.
            // If the country has no states, fill city automatically from _cities.
            function populateStates(countryID) {
                const stateSelect = document.querySelector('select[name="state"]');
                const citySelect  = document.querySelector('select[class="citySelectorForAbroad"]');

                // Clear out both <select> each time
                clearSelectOptions(stateSelect, "- - בחר מדינה - -");
                clearSelectOptions(citySelect,  "- - בחר עיר - -");

                // If IL => do nothing
                if (countryID === "IL") {
                    return;
                }

                // If no data for that country => do nothing
                if (! locData[countryID]) {
                    return;
                }

                // Check if we have state objects or just a "_cities" array
                let hasStates = false;
                for (let key in locData[countryID]) {
                    if (key !== "_cities") {
                        hasStates = true;
                        let opt = document.createElement("option");
                        opt.value = key;
                        opt.textContent = key; // e.g. Florida
                        stateSelect.appendChild(opt);
                    }
                }

                // If has states (like US): show the “state” <div>.
                // If no states (like CY): hide the “state” <div> & fill city from _cities.
                const stateInputForUSA = document.querySelector('.stateInputForUSA');
                if (hasStates) {
                    if (stateInputForUSA) {
                        stateInputForUSA.style.display = '';
                    }
                } else {
                    if (stateInputForUSA) {
                        stateInputForUSA.style.display = 'none';
                    }
                    // populate city from locData[countryID]["_cities"]
                    let cityArray = locData[countryID]["_cities"] || [];
                    cityArray.forEach(city => {
                        let opt = document.createElement("option");
                        opt.value = city;
                        opt.textContent = city;
                        citySelect.appendChild(opt);
                    });
                }
            }

            // For US only: after picking a state, fill the City select
            function populateCities(countryID, chosenState) {
                const citySelect = document.querySelector('select[class="citySelectorForAbroad"]');
                if (! citySelect) return;
                clearSelectOptions(citySelect, "- - בחר עיר - -");

                if (! locData[countryID]) return;
                if (! locData[countryID][chosenState]) return;

                let cityArray = locData[countryID][chosenState];
                cityArray.forEach(city => {
                    let opt = document.createElement("option");
                    opt.value = city;
                    opt.textContent = city;
                    citySelect.appendChild(opt);
                });
            }

            // -----------------------------------------
            // EXISTING SCRIPT (with slight additions)
            // -----------------------------------------
            document.addEventListener('DOMContentLoaded', function() {
                // Grab all radio buttons with name="domainCountry"
                const radioButtons = document.querySelectorAll('input[name="domainCountry"]');

                // Grab the containers we want to show/hide
                const israelFormContainer = document.getElementById('israelFormContainer');
                const abroadFormContainer = document.getElementById('abroadFormContainer');

                // For the USA-only state input
                const stateInputForUSA = document.querySelector('.stateInputForUSA');

                // Re-run the code that toggles the entire form
                function toggleContainers() {
                    let selectedValue;
                    radioButtons.forEach(rb => {
                        if (rb.checked) {
                            selectedValue = rb.value;
                        }
                    });

                    // If Israel => show #israelFormContainer, hide #abroadFormContainer
                    if (selectedValue === 'IL') {
                        israelFormContainer.style.display = 'block';
                        abroadFormContainer.style.display = 'none';
                    }
                    else {
                        // Otherwise, we show the Abroad container
                        israelFormContainer.style.display = 'none';
                        abroadFormContainer.style.display = 'block';

                        if (selectedValue === 'US') {
                            // If “USA” => show .stateInputForUSA
                            if (stateInputForUSA) stateInputForUSA.style.display = 'block';
                        } else {
                            // If “CY” => hide .stateInputForUSA
                            if (stateInputForUSA) stateInputForUSA.style.display = 'none';
                        }


                    }

                    // ******* IMPORTANT: Now also populate states/cities *******
                    populateStates(selectedValue);
                }

                // Listen for changes on the domainCountry radios
                radioButtons.forEach(rb => {
                    rb.addEventListener('change', toggleContainers);
                });

                // (NEW) Listen for changes on the <select name="state"> to load city
                const stateSelect = document.querySelector('select[name="state"]');
                if (stateSelect) {
                    stateSelect.addEventListener('change', function() {
                        // Which country is selected in the radio?
                        let countryRB  = document.querySelector('input[name="domainCountry"]:checked');
                        let countryVal = (countryRB ? countryRB.value : 'IL');
                        populateCities(countryVal, this.value);
                    });
                }

                // Finally, run once on page load
                toggleContainers();
            });
        </script>

        <div id="bizReview" style="display: none;"><?
            $transBiz = [];
            if($siteID)
                $transBiz = Translation::sites($siteID, '*', 1, 1);
            echo outDb($transBiz['reviewLocation']);
            ?></div>
            </div>

    </div>
</div>
<style>
    div#cloner {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        z-index: 999;
        background: rgba(0,0,0,0.6);
        text-align: center;
        display: none;
    }
    #cloner .popup_container {
        position: absolute;
        top: 50%;
        right: 50%;
        width: 100%;
        max-width: 800px;
        max-height:90%;
        overflow:auto;
        padding: 10px;
        box-sizing: border-box;
        min-height: 100px;
        background: #fff;
        border-radius: 8px;
        background: #fff;
        transform: translateY(-50%) translateX(50%);
    }

    #cloner .popup_container .inputLblWrap{margin:10px}
    #cloneem , #notcloneem{
        position: relative;
        width: 90px;
        height: 50px;
        line-height: 50px;
        color: #ffffff;
        font-weight: bold;
        background: #2FC2EB;
        font-size: 16px;
        margin-top: 20px;
        text-shadow: -1px 1px 0 rgb(0 0 0 / 10%);
        border-bottom: 2px solid rgba(0,0,0,0.1);
        cursor: pointer;
        box-shadow: none;
        -moz-transition: all 0.25s;
        -webkit-transition: all 0.25s;
        transition: all 0.25s;
        text-align: center;
        display: inline-block;
        vertical-align: top;
    }
    #notcloneem {
        background: #333333;
    }
    #copyData {
        width: auto;
        padding: 0 10px;
        color: #ffffff;
        font-weight: normal;
        background: #2FC2EB;
        font-size: 16px;
        line-height: 44px;
        display: inline-block;
        border-radius: 3px;
    }
    .swal2-container:not(.swal2-in) {
        pointer-events: unset;
    }
</style>
<div class="pop" id="cloner" >
    <div class="popup_container">
        <form id="clonerform" name="clonerform" >
            <h2><strong>שכפול הנתונים לדומיינים אחרים</strong></h2>
            <?
            $domTypes[1]="אירוח לפי יום";
            $domTypes[2]="אירוח לפי שעה";
            $domTypes[4]="אירוח לאירועים";
            foreach(DomainList::get() as $did => $dom){
                for($i=0;$i<10;$i++){
                    $val = 2**$i;
                    if($dom['attrType'] & $val){
                        $domList[$val][$did] = $dom;
                        continue;
                    }
                }
            }
            ksort($domList);
            //print_r($domList);
            foreach($domList as $key => $domType){
                $start = 1;
                foreach($domType as $did => $dom){
                    if($did == 1 || $did == $domainID) continue;
                    if($start){
                        $start = 0;
                        ?>
                        <div style="padding:10px;border-top:1px solid black"><b><?=$domTypes[$key]?></b></div>
                        <?
                    }
                    ?>
                    <div class="inputLblWrap" style="float: none;">
                        <div class="switchTtl"><?=$dom['domainName']?></div>
                        <label class="switch">
                            <input type="checkbox" name="toDomains[]" value="<?=$did?>" >
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <?
                }
            }
            ?>

            <div style="clear: both;">
                <div class="inputLblWrap" >
                    <div class="switchTtl">שכפול גלריות?</div>
                    <label class="switch">
                        <input type="checkbox" name="clonegalleries" id="clonegalleries" value="1" >
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            <div style="clear: both;">
                <div class="inputLblWrap" id="overridegalleriesWrap" style="display: none;">
                    <div class="switchTtl">שכתב את הגלריות?</div>
                    <label class="switch">
                        <input type="checkbox" name="overridegalleries" id="overridegalleries" value="1" >
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>

            <input type="hidden" name="fromDomain" value="<?=$domainID?>">
            <input type="hidden" name="siteID" value="<?=$siteID?>">
            <div class="inputLblWrap">
                <input type="button" name="cloneem" id="cloneem" value="שכפל">
                <input type="button" name="notcloneem" id="notcloneem" value="ביטול">
            </div>
        </form>
    </div>
</div>
<script src="../../app/tinymce/tinymce.min.js"></script>

<script>

    function reloadGalleries(){

    }

    $("#clonegalleries").on("change",function () {
        if($(this).is(":checked")) {
            $("#overridegalleriesWrap").show();
        }
        else {
            $("#overridegalleries").attr("checked",false);
            $("#overridegalleriesWrap").hide();
        }
    }) ;


    $("#clonerform").on("submit",function(event){
        event.preventDefault();
        var sendData = $( this ).serialize();


        $.ajax({
            url: 'copyData.php',
            method: 'POST',
            data: sendData,
            success: function (response) {
                alert("פעולה הסתיימה");
                $("#notcloneem").trigger("click");
                //window.location.reload();
            }
        });

    });
    $("#cloneem").on("click",function(){
        allDomains = $("input[name='toDomains[]']:checked").length;
        if(allDomains == 0) {
            alert("לא נבחרו דומיינים");
            return;
        }
        if(confirm("שכפול הנתונים ישכתבו נתונים קיימים האם אתם בטוחים?")) {
            $("#clonerform").submit();
        }
    });
    $("#copyData").on("click",function(){
        $("#cloner").show();

    });
    $("#notcloneem").on("click",function(){
        $("#cloner").hide();

    });

    $(".galleryactive").on("change",function () {
        var id = $(this).data("galid");
        $.ajax({
            method: 'POST',
            url: '/cms/moduls/minisites/ajax_update_active.php',
            data: {id: id , tbl: "sites_galleries"},
            success: function(response){

            }
        });
    });

    setTimeout(function () {
        $("#whichDomain").fadeOut();
    },750);

    function scrollToElement(elem){

        //$("#" + elem).trigger("click");
        $('html, body').animate({
            scrollTop: $("#" + elem).offset().top
        }, 1000);
        if(elem == "sitesGalleries") {
            $.get("site_galleries_list.php?siteID=<?=$siteID?>&domainID=<?=$domainID?>",function(html){
                $("#gallerySortCont").html(html);
            });
        }

    }

    function filterProperties(sid) {
        var selected = $("#" + sid).val(),listSelect = "";
        if(selected == -1) {
            $(".checkLabel.checkIb").show();
        }
        else {

            $(".checkLabel.checkIb").hide();
            listSelect = selected.split(",");
            for(var s=0;s<listSelect.length;s++) {
                $(".checkLabel.checkIb[data-attrtype="+listSelect[s]+"]").show();
            }

        }

    }

    function dupGal(dupsel,galleryID,siteID,curDomain,galleryType){
        if(confirm("האם אתה בטוח שברצונך לשכפל את הגלריה?")){
            var domain = $('#galWrapSelect' + dupsel + galleryID).val();

            console.log({"galID":galleryID,toDomain:domain,curDomain:curDomain,galleryType: galleryType})
            $.post("dupGal.php",{"galID":galleryID,toDomain:domain,curDomain:curDomain,galleryType: galleryType}).done(function(){
                alert("הגלריה שוכפלה בהצלחה");
                //window.location.reload();
            });
        }
    }

    $(function(){
        $('#exclusive').on('click', function(){
            $('.mainSectionWrapper.attr').find('input[type="checkbox"][value="1"]').prop('checked', this.checked);
        });

        $('.mainSectionWrapper').click(function(){
            var editors = $(this).find('textarea.textEditor:not([aria-hidden=true])');

            if(editors.length){
                editors.each(function(i){
                    var obj = {
                        readonly : 0,
                        target: this,
                        height: 500,
                        plugins: [
                            "advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
                            "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                            "table contextmenu directionality emoticons textcolor paste  textcolor colorpicker textpattern"
                        ],
                        fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
                        toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
                        toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
                        toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking pagebreak restoredraft"
                    };
                    tinymce.init(obj);
                });
            }
        });
        if($("#bizReview").html() == '') {
            $('.pullFromBiz').remove();
        }

        $('.pullFromBiz').on("click",function(){
            tinymce.activeEditor.setContent($("#bizReview").html())
            console.log("ok");
        });
        /*		tinymce.init({
                  selector: 'textarea.textEditor' ,
                  height: 500,
                 plugins: [
                    "advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
                    "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                    "table contextmenu directionality emoticons textcolor paste  textcolor colorpicker textpattern"
                  ],
                  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
                  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
                  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
                  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking pagebreak restoredraft"

                }); */
    });



    var inputQuantity = [];
    $(function() {
        $(".inputNumber").each(function(i) {
            inputQuantity[i]=this.defaultValue;
            $(this).data("idx",i); // save this field's index to access later
        }).on("keyup", function (e) {
            var $field = $(this),
                val=this.value,
                $thisIndex=parseInt($field.data("idx"),10); // retrieve the index
//        window.console && console.log($field.is(":invalid"));
            //  $field.is(":invalid") is for Safari, it must be the last to not error in IE8
            if (this.validity && this.validity.badInput || isNaN(val) || $field.is(":invalid") ) {
                this.value = inputQuantity[$thisIndex];
                return;
            }
            if (val.length > Number($field.attr("maxlength"))) {
                val=val.slice(0, 5);
                $field.val(val);
            }
            inputQuantity[$thisIndex]=val;
        });
    });


    /*facilities save to one input
    var hidenInputFac = $("input[name='facilities']");
    var facilArr = [];
    if(hidenInputFac.val()){
        facilArr = [hidenInputFac.val()];
    }
    $('.checkBoxGr').change(function(){

        if($(this).is(':checked')){
            facilArr.push($(this).attr('id'));
        }
        else{
            facilArr.splice($.inArray($(this).attr('id')), 1 );
        }
        hidenInputFac.val(facilArr);
    });*/


    function galleryOpen(domainID,siteID,galleryID="",siteMainGallery = 0){
        $(".popGalleryCont").html('<iframe width="100%" height="100%" id="frame_'+domainID+'_'+siteID+'_'+galleryID+'" frameborder=0 src="/cms/moduls/minisites/galleryGlobal.php?domainID='+domainID+'&siteID='+siteID+'&gID='+galleryID+'&siteMainGallery='+siteMainGallery+'"></iframe><div class="tabCloserSpace" onclick="tabCloserGlobGal(\'frame_'+siteID+'\')">x</div>');
        $(".popGallery").show();
        var elme = window.parent.document.getElementById("frame_"+siteID);

        elme.style.zIndex="16";
        elme.style.position="relative";
    }

    function tabCloserGlobGal(id,CB,param){
        $(".popGalleryCont").html('');
        $(".popGallery").hide();
        var elme = window.parent.document.getElementById(id);
        elme.style.zIndex="12";
        elme.style.position ="static";
        if(CB) {
            CB(param);
        }
    }
    function tabCloserGlobGalMain(id,CB,param){
        $(".popGalleryCont").html('');
        $(".popGallery").hide();
        var elme = window.parent.document.getElementById(id);
        elme.src = '';
        elme.style.zIndex="12";
        elme.style.position ="static";
        if(CB) {
            CB(param);
        }
    }

    function deleteGallery(galID){

        if(confirm("האם אתה בטוח שברצונך למחוק את הגלריה?")){
            $.post("ajax_del_gallery.php",{id:galID},function(){
                $("#galRow"+galID).remove();
            });


        }
    }

    /*

        $(".general .lngtab").click(function(){
            $(".general .lngtab").removeClass("active");
            $(this).addClass("active");

            var ptID = $(this).data("langid");
            $(".frm").css("display","none");

            $("#langTab"+ptID).css("display","block");
        });
    */

    $(function(){

        $.each({domain: <?=$domainID?>, language: <?=$langID?>}, function(cl, v){
            $('.' + cl).hide().each(function(){
                var id = $(this).data('id');
                $(this).find('input, select, textarea').each(function(){
                    this.name = this.name + '[' + id + ']';
                });
            }).filter('[data-id="' + v + '"]').show();

            $('.' + cl + 'Selector').on('change', function(){

                $('.' + cl, $(this).data('cont')).hide().filter('[data-id="' + this.value + '"]').show();
            });
        });
        $('input[type="radio"][data-checked="1"]').attr('checked', true);
        var sel = $('select', '#exSection');
        sel.on('change', function(){
            $('#exSection2').remove();

            if (this.value != ''){
                $.getJSON('js_exEngine.php', {act:'list', sid:<?=$siteID?>, id:this.value}, function(res){
                    if (res.error)
                        return alert(res.error);
                    else
                        $('#exSection').after('<div class="inputLblWrap" id="exSection2"><div class="inputLblWrap"><div class="labelTo">מזהה חיצוני :</div>' + res.html + '</div></div>');
                });
            }
        }).trigger('change');

        function MultiCcLabel(){
            $('#areasChecks .choosenCheck').text($('#areasChecks input:checked').map(function(){
                return $(this.parentNode).text();
            }).get().join(', '));
        };

        MultiCcLabel();

        $('#areasChecks .choosenCheck').click(function(){
            $(this.parentNode).toggleClass('open');
        }).parent().find('input').off('click').click(MultiCcLabel);

        $(".datepicker").datepicker({dateFormat:"yy-mm-dd"}  );

        <?php
        if ($errorMsg){
        ?>
        //swal.fire({icon:'error', title:'<?=$errorMsg?>'});
        alert('<?=$errorMsg?>');
        <?php
        }
        ?>
    });

    $(".questions_section .switch input").change(function(){
        if($(this).is(':checked')){
            $(this).closest('.questions_section').find('.checkLabel input').prop( "checked", true );
        }
    });


    function orderNow(is){

        $(is).val("שמור סדר תצוגה");
        $(is).attr("onclick", "saveOrder()");
        $(".gallerySort").sortable({
            stop: function(){
                $("#orderResult").val($(".gallerySort").sortable('toArray'));
            }
        });
        $("#orderResult").val($(".gallerySort").sortable('toArray'));
    }
    function saveOrder(){
        var ids = $("#orderResult").val();
        $.ajax({
            url: 'ajax_save_galleries_order.php?siteID=<?=$siteID?>&domainID=<?=$domainID?>',
            type: 'POST',
            data: {ids:ids, table:"sites_galleries"},
            async: false,
            success: function (myData) {
                swal.fire({
                    title: 'נשמר בהצלחה',
                    type: 'success'
                });
            }
        });
    }


    $("#spaplusID2").on("input",function(){
        var text = $("#spaplusID2").val();
        //debugger;
        if(text != '') {
            $("#spaplussites option").each(function(){
                if(text == $(this).val()) {
                    var vval = $(this).data("value");

                    $("#spaplusID").val(vval);
                    return;
                }
            });
        }else{
            $("#spaplusID").val('');
        }

    });

    function changeAttrDomain(thedomainID){
        //debugger;
        $('.attr_box').each(function(){
            $(this).detach().appendTo('#domain_cat'+thedomainID+' #wrap_cat0')
        });
        $('.domain_cat').removeClass('show');
        $('#domain_cat'+thedomainID).addClass('show');
        var dom_categories =  dom_cat[thedomainID].split(',');
        $.each(dom_categories,function(key,catID){
            var categoryID = catID;
            var cat_attributes = cat_attr[categoryID].split(',');
            $.each(cat_attributes,function(key2,attrID){
                $('#attrID'+attrID).detach().appendTo('#wrap_cat'+categoryID);
            });
        });
    }

</script>

<?php
foreach($all_attributes as $all_attr){
    if($all_attr["categoryID"]!=$last_cat){
        $domain_categories[$all_attr["domainID"]][]= $all_attr["categoryID"];
    }
    $domain_cat_attr[$all_attr["categoryID"]][]= $all_attr["attrID"];

    $last_cat = $all_attr["categoryID"];
}
//print_r($all_attributes);


?>
<script>
    var cat_attr = {};
    var dom_cat = {};
    <?foreach($domain_cat_attr as $key=> $d_c_a){?>
    cat_attr[<?=$key?>] = '<?=implode(",",$d_c_a);?>';

    <?}?>
    <?foreach($domain_categories as $key2=> $d_c){?>
    dom_cat[<?=$key2?>] = '<?=implode(",",$d_c);?>';

    <?}?>
    //dom_cat[6] = '1,5,6,7,8,9,10,12,13,14,15,16,17,18,19,22,24';
</script>
