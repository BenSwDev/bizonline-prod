<?php
require_once "auth.php";

//ini_set('display_errors', 1);
//error_reporting(-1 ^ E_NOTICE);

//header('Access-Control-Allow-Origin: https://bizonline.co.il/');

if (!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){
    echo blockAccessMsg();
    return;
}

$siteID = 0;

$result = new JsonResult(['status' => 99]);

$order = $payment = null;

$payID   = intval($_POST['payID']);
$orderID = intval($_POST['orderID']);

if ($orderID){
    $order = udb::single_row("SELECT * FROM `orders` WHERE `orderID` = " . $orderID . " AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
    if (!$order)
        throw new Exception('Cannot find booking #' . $orderID);

    $siteID = $order['siteID'];
}
if(!$siteID) {
    $siteID = $_CURRENT_USER->active_site();
}

$user_log = new UserActionLog($_CURRENT_USER, $siteID, $orderID);

try {
    UserUtilsNew::init($siteID);
    switch($_POST['act']){
        case 'initPay':
            $input = typemap($_POST, [
                'type' => 'int',
                'sum'  => 'float',
                'adv'  => 'int',
                'hts'  => 'int',
                'mbr'  => 'numeric',
                'cpn'  => 'string',
                'via'  => 'string',
                'prv'  => 'string',
                'cpnname' => 'string',
                'cpnid' => 'int',
                'iname'  => 'string',
                'apt'    => 'string',
                'booker' => 'string',
                'inner'  => 'int'
            ]);

            if (!isset(UserUtilsNew::$payTypesFull[$input['via']]))
                throw new Exception('סוג תשלום שגוי');
            if (!$orderID)
                throw new Exception('מספר הזמנה שגוי');

            if ($input['type'] == 2)
                $input['via'] = 'ccard';

            $realType = $input['via'];
            if ($input['via'] == 'coupon' && $input['prv'])
                $input['via'] = $input['prv'];

            $protel = udb::single_row("SELECT ps.* FROM `protel_sites` AS `ps` INNER JOIN `sites` ON (sites.protelID = ps.id) WHERE ps.active = 1 AND sites.siteID = " . $siteID);

            // payment by credit card
            if ($input['via'] == 'ccard'){
                $result['complete'] = 0;

                //$client   = YaadPay::getTerminal($siteID);
                $client   = Terminal::hasTerminal($siteID) ? Terminal::bySite($siteID) : null;
                $getToken = !!$client;      // if no client - no token

                $req = [
                    'customerName' => $input['iname'] ?: $order['customerName'],
                    'phone'        => $order['customerPhone'],
                    'email'        => $order['customerEmail'],
                    '_target'      => 'order'
                ];

                if ($client && !$client->has_cc_charge)     // if there's terminal, but without charge - always set to card test
                    $input['type'] = 2;

                // card test
                if ($input['type'] == 2){
                    $mtype = udb::single_value("SELECT `masof_type` FROM `sites` WHERE `siteID` = " . $siteID);
                    if (!$client || $mtype == 'max')
                        $client = YaadPay::defaultTerminal();

                    $trans = $client->has_freeze ? $client->initFreezeSum($input['sum'], 'תפיסת מסגרת על סכום ' . $input['sum'], $req) : $client->initFrameCardTest($getToken, $req);

                    $insert = [
                        'payType'   => 'ccard',
                        'buserID'   => $_CURRENT_USER->id(),
                        'paymentID' => $trans['transID'],
                        'orderID'   => $orderID,
                        'startTime' => date('Y-m-d H:i:s'),
                        'subType'   => 'card_test',
                        'sum'       => 1,
                        'inputData' => json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    ];
                    udb::insert('orderPayments', $insert);

                    if ($client->has_freeze){
                        $insert['subType'] = 'freeze_sum';
                        $insert['sum']     = $input['sum'];

                        udb::insert('orderPayments', $insert);
                    }
                }
                else {
                    if (!$client)       // if not card test - MUST have working terminal
                        throw new Exception("There's no terminal or the terminal is inactive");
                    if ($input['sum'] <= 0)
                        throw new Exception("נא להכניס סכום תקין");
                    if ($client->has_invoice && !$input['iname'])
                        throw new Exception("נא למלא שם לחשבונית");

                    $req['description'] = 'תשלום עבור הזמנה #' . $order['orderIDBySite'];

                    // if it's spa order - change description
                    if ($order['parentOrder'] > 0){
                        $desc = [];

                        $que = "SELECT orders.treatmentLen, treatments.treatmentName 
                                FROM `orders` INNER JOIN `treatments` USING (`treatmentID`) 
                                WHERE orders.parentOrder = " . $orderID . " AND  orders.orderID <> " . $orderID . "
                                ORDER BY orders.orderID";
                        $treatments  = udb::single_list($que);
                        foreach($treatments as $treatment)
                            $desc[] = $treatment['treatmentName'] . " " . $treatment['treatmentLen'] . " דקות";

                        if ($order['extras']){
                            $que = "SELECT `extraID`, `extraName` FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE s.siteID = " . $order['siteID'] . " AND included = 0 ORDER BY e.showOrder";
                            $extras = udb::key_value($que);

                            $orderExtras = json_decode($order['extras'], true) ?: [];

                            foreach($orderExtras['extras'] as $extra)
                                if($extras[$extra['extraID']])
                                    $desc[] = $extra['count'] . " x " . $extras[$extra['extraID']];
                        }

                        $req['description'] = trim($req['description'] . ': ' . implode(" | ", $desc));
                    }

                    $trans = $client->initFramePay(array_merge($req, $input));

                    udb::insert('orderPayments', [
                        'payType'   => 'ccard',
                        'buserID'   => $_CURRENT_USER->id(),
                        'paymentID' => $trans['transID'],
                        'orderID'   => $orderID,
                        'startTime' => date('Y-m-d H:i:s'),
                        'subType'   => $input['adv'] ? 'advance' : '',
                        'sum'       => $input['sum'],
                        'inputData' => json_encode(array_merge($req, $input), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    ]);
                }

                $result['url'] = $trans['url'];
            }
            // payment "on room/hotel guest" for Protel site
            elseif ($input['via'] == 'guest' && $protel){
                //require_once __DIR__ . "/../cms/classes/ProtelParser/class.ProperParser.php";
                require_once __DIR__ . "/../api/protel/classes_prod.php";

                if (!$input['sum'])
                    throw new Exception("סכום לא חוקי");
                if (!$input['booker'] || !$input['apt'] || !$input['inner'])
                    throw new Exception("נא לבחור אורח מלון מרשימה");

                $protelOrder = udb::single_row("SELECT * FROM `protel_orders` WHERE `hotelID` = " . $protel['id'] . " AND `status` IN ('Reserved', 'In-house') AND `innerID` = " . $input['inner']);
                if (!$protelOrder)
                    throw new Exception("לא נמצא הזמנה שבחרת");
                if (strcasecmp($protelOrder['customerName'], $input['booker']))
                    throw new Exception("שם המזמין לא נכון לזמנה שבחרת");

                $data = unserialize($protelOrder['orderData']);

                // checking that booking has selected room
                if (strcasecmp(rtrim($protelOrder['room'], '~'), $input['apt'])){       // initial check
                    $drop = true;
                    if (substr($protelOrder['room'], -1) == '~')            // if booking has more than one room
                        foreach($data->HotelReservations[0]->RoomStays as $stay){
                            foreach($stay->Rooms as $room)
                                if ($room->_RoomID == $input['apt'])
                                    $drop = false;
                        }

                    if ($drop)
                        throw new Exception("מספר החדר לא נכון לזמנה שבחרת");
                }

                $input['desc'] = $description = ($order['parentOrder'] > 0) ? 'טיפול ספא' . ': ' . OrderSpaMain::full_description($orderID, $order['siteID'], $order['extras']) : 'תשלום עבור הזמנה #' . $order['orderIDBySite'];


/*                // if it's spa order - change description
                $input['desc'] = $description = 'טיפול ספא';
                if ($order['parentOrder'] > 0){
                    $desc = [];

                    $que = "SELECT orders.treatmentLen, treatments.treatmentName 
                            FROM `orders` INNER JOIN `treatments` USING (`treatmentID`) 
                            WHERE orders.parentOrder = " . $orderID . " AND  orders.orderID <> " . $orderID . "
                            ORDER BY orders.orderID";
                    $treatments  = udb::single_list($que);
                    foreach($treatments as $treatment)
                        $desc[] = $treatment['treatmentName'] . " " . $treatment['treatmentLen'] . " דקות";

                    if ($order['extras']){
                        $que = "SELECT `extraID`, `extraName` FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE s.siteID = " . $order['siteID'] . " AND included = 0 ORDER BY e.showOrder";
                        $extras = udb::key_value($que);

                        $orderExtras = json_decode($order['extras'], true) ?: [];

                        foreach($orderExtras['extras'] as $extra)
                            if($extras[$extra['extraID']])
                                $desc[] = $extra['count'] . " x " . $extras[$extra['extraID']];
                    }

                    $input['desc'] = $description = trim($description . ': ' . implode(" | ", $desc));
                }*/

                // creating payment row
                $payID = udb::insert('orderPayments', [
                    'payType'    => $realType,
                    'buserID'    => $_CURRENT_USER->id(),
                    'orderID'    => $orderID,
                    'startTime'  => date('Y-m-d H:i:s'),
                    'subType'    => $input['adv'] ? 'advance' : '',
                    'provider'   => $input['via'],
                    'sum'        => $input['sum'],
                    'inputData'  => json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ]);

                $prToken = ['CHARGE-SPA' , $protelOrder['protelOrderID'], $protelOrder['innerID'], $orderID, $payID];

                // sending charge to Protel
                $charge = new HTNG_ChargePostingRQ($protelOrder['hotelID'], $protelOrder['protelOrderID'], implode('-', $prToken), $payID, '');
                $ac = $charge->add_revenue(VAT::before_vat($input['sum']), 'ILS', 4700, $description);

                $clog = '';
                $className = get_class($charge);

                $logID = udb::insert('protel_comm_log', [
                    'type' => 'out',
                    'corrID' => $charge->_CorrelationID,
                    'name' => $className,
                    'request' => '' . $charge,
                    'parsed'  => serialize($charge)
                ]);

                $client = new MySoapCaller;
                $response = $client->send($className, $charge, $charge->_CorrelationID, $clog);

                udb::update('protel_comm_log', ['response' => $response], '`id` = ' . $logID);
                udb::update('orderPayments', ['paymentID' => 1000000 + $logID], "`lineID` = " . $payID);

                // protel works in asyncronous way, so we need to wait for response...
                $try = 0;
                do {
                    sleep(3);

                    $resp = udb::single_row("SELECT `resultData`, `complete` FROM `orderPayments` WHERE `lineID` = " . $payID . " AND `endTime` IS NOT NULL AND `resultData` IS NOT NULL");
                    if ($resp || ++$try >= 5)
                        break;
                } while(1);

                if (empty($resp))
                    throw new Exception("אין תשובה מבית מלון, נא להמתין או להתקשר למשרד.");
                if (!$resp['complete'])
                    throw new Exception("חיוב על החד נכשל");

                $result['complete'] = 1;
            }
            // payment NOT by credit card
            else {
                if (!$input['sum'])
                    throw new Exception("Zero sum payment");

                $noInvoice = false;

                $rd = ['success' => true, 'error' => ''];

                // coupon payment
                if ($input['via'] == 'vouchers'){
                    if (!$input['cpn'] || strlen($input['cpn']) < 6)
                        throw new Exception('Illegal coupon number');

                    $coupon = CouponManager::getCoupon($input['cpn'], $siteID);
                    if (!$coupon)
                        throw new Exception('Cannot find coupon with this number');

                    $rd['couponUse'] = $coupon->charge($input['sum'], 'הזמנה #' . $siteID . '-' . $order['orderIDBySite']);

                    if ($coupon->terminal == 'direct')
                        $input['via'] = 'vouchersD';

                    $noInvoice = true;
                }
                // biz subscription payment
                elseif ($realType == 'member2'){
                    $subNum = intval(preg_replace('/\D+/', '', $input['mbr']));

                    if (!$subNum || strlen($subNum) < 8)
                        throw new Exception('Illegal subscription number');

                    $siteID = udb::single_value("SELECT `siteID` FROM `orders` WHERE `orderID` = " . $orderID);

                    udb::query("LOCK TABLES `subscriptions` READ, `subscriptionTreatments` WRITE, `orderPayments` WRITE");

                    $treat = udb::single_row("SELECT * FROM `subscriptions` WHERE `active` = 1 AND `subNumber` = " . $subNum);
                    if (!$treat)
                        throw new Exception("Subscription " . prettySubsNumber($subNum) . " is inactive or doesn't exist.");
                    if ($treat['siteID'] != $siteID)
                        throw new Exception("Subscription " . prettySubsNumber($subNum) . " belongs to different owner.");

                    $stData = udb::single_row("SELECT * FROM `subscriptionTreatments` WHERE `subID` = " . $treat['subID'] . " AND `payID` IS NULL LIMIT 1");
                    if (!$stData)
                        throw new Exception("Subscription " . prettySubsNumber($subNum) . " has been used up already.");

                    $rd['subID'] = $treat['subID'];
                    $rd['stID']  = $stData['stID'];

                    $noInvoice = true;
                }

                $payID = udb::insert('orderPayments', [
                    'payType'    => $realType,
                    'buserID'    => $_CURRENT_USER->id(),
                    'paymentID'  => 0,
                    'orderID'    => $orderID,
                    'startTime'  => date('Y-m-d H:i:s'),
                    'endTime'    => date('Y-m-d H:i:s'),
                    'subType'    => $input['adv'] ? 'advance' : '',
                    'provider'   => $input['via'],
                    'complete'   => 1,
                    'sum'        => $input['sum'],
                    'inputData'  => json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'resultData' => json_encode($rd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ]);

                if ($noInvoice)
                    udb::update('orderPayments', ['invoice' => '-'], "`lineID` = " . $payID);

                if ($realType == 'member2' && $rd['stID']){
                    udb::update('subscriptionTreatments', ['payID' => $payID], "`stID` = " . $rd['stID']);
                    udb::query("UNLOCK TABLES");
                }

                $canInvoice = udb::single_value("SELECT `masof_invoice` FROM `sites` WHERE `siteID` = " . $siteID);
                $spTypes    = udb::key_value("SELECT `paytypekey`, `invoice` FROM `sitePayTypes` WHERE `siteID` = " . $siteID);

                if ($order['parentOrder'] <= 0)         // villas
                    $spTypes['cash'] = 0;
                elseif (!isset($spTypes['cash']))       // spa
                    $spTypes['cash'] = 2;

                if (!$noInvoice && $canInvoice && $spTypes[$input['via']] == 2){
                    try {
                        $invData = [
                            'full_name' => $input['iname'],
                            'email'     => $order['customerEmail'],
                            'desc'      => 'תשלום עבור הזמנה #' . $order['orderIDBySite']
                        ];

                        // if it's spa order - change description
                        if ($order['parentOrder'] > 0)
                            $invData['desc'] = trim($invData['desc'] . ': ' . OrderSpaMain::full_description($orderID, $order['siteID'], $order['extras']));

                        $client = Terminal::bySite($siteID);
                        if (!$client)
                            throw new Exception("There's no terminal or the terminal is inactive");

                        $pType = $realType;
                        if ($client->engine() == 'Yaad'){
                            $payBy = '';

                            switch($realType){
                                case 'transfer' : $pType = 'Multi'; break;
                                case 'check'    : $pType = 'Check'; break;
                                case 'cash'     : $pType = 'Cash';  break;
                                default         :
                                    $pType = 'Cash';
                                    $payBy = 'שולם דרך ' . UserUtilsNew::method($realType, $input['via']);
                                    break;
                            }

                            $res = $client->sendPrintout($pType, $input['sum'], $invData['desc'], $invData, $payID, $payBy);
                        }
                        else
                            $res = $client->sendPrintout($pType, $input['sum'], $invData['desc'], $invData, $payID);

                        if (!$res['success'])
                            throw new Exception($res['error'] ?: "Invoice failed");

                        $rd['invoice'] = $res['invoice'];
                        $rd['invoiceData'] = $res;

                        udb::update('orderPayments', [
                            'paymentID'  => $res['_transID'] ?: 0,
                            'invoice'    => $res['invoiceURL'] ?: '-',
                            'resultData' => json_encode($rd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                        ], '`lineID` = ' . $payID);

                        $user_log->save('payment_invoice', $siteID, $orderID, ['lineID' => $payID]);
                    }
                    catch(Exception $e){
                        // failed to create invoice
                    }
                }

                $result['complete'] = 1;

                $popInv = udb::single_value("SELECT `autoInvoice` FROM `sites` WHERE `siteID` = " . $siteID);
                $result['payID'] = ($popInv && in_array($input['via'], ['cash', 'check', 'transfer', 'bit', 'paybox'])) ? $payID : 0;

                updateOrderAdvance($orderID);
            }
            break;

        case 'directPay':
            $input = typemap($_POST, [
                'sum'   => 'float',
                'pays'  => 'int',
                'cvv'   => 'numeric',
                'iname' => 'string'
            ]);

            if (!$input['sum'] || $input['sum'] < 0)
                throw new Exception('Empty or illegal charge amount');
            if (!$orderID)
                throw new Exception('מספר הזמנה שגוי');

            $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `lineID` = " . $payID);

            if (!$payment || $payment['orderID'] != $orderID)
                throw new Exception('Cannot find payment for booking #' . $orderID);
            if (!$payment['complete'] || !$payment['endTime'])
                throw new Exception('Payment failed or was not completed');
            if ($payment['cancelled'] || $payment['cancelData'])
                throw new Exception('Payment already cancelled/refunded');
            if (strcasecmp($payment['payType'], 'ccard') || !in_array($payment['subType'], ['card_test', 'freeze_sum']))        // if not cc or wasn't card check or freezed sum
                throw new Exception('Can only charge credit card for security or freezed sum');

            $isFrozen = ($payment['subType'] == 'freeze_sum');
            $payData  = json_decode($payment['resultData'], true);

            if (!$payment['token'] || !$payData['tokenData'] || $payData['tokenData']['error'])
                throw new Exception('Cannot find credit card data');
            if (!$payData['exID'])
                throw new Exception('Cannot find transaction ID.');     // should not happen
            if ($isFrozen && !$payData['authCode'])
                throw new Exception('Cannot find approval number for transaction.');     // should not happen
            if (!$payData['tokenData']['name'] && !$input['iname'])
                throw new Exception('שם לחשבונית הוא שדה חובה!');

            // check if client can charge saved cards
            $canCharge = udb::single_value("SELECT IF(`masof_active` = 1 AND `masof_number` > '' AND `masof_no_cvv` = 1 AND `masof_no_charge` = 0, 1, 0) FROM `sites` WHERE `siteID` = " . $siteID);
            if (!$canCharge)
                throw new Exception('You are not allowed to charge saved cards');

            if ($input['cvv'])
                $payData['tokenData']['cvv'] = $input['cvv'];
            if ($input['iname'])              // replacing "token name" with one from popup
                $payData['tokenData']['name'] = $input['iname'];

            $client = Terminal::bySite($siteID);
            if (!$client)
                throw new Exception("There's no terminal or the terminal is inactive");

//            if ($isFrozen){
//                udb::query("START TRANSACTION");
//
//                // checking for parallel processes
//                $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `lineID` = " . $payID . " AND `cancelled` = 0 FOR UPDATE");
//                if (!$payment)
//                    throw new Exception("Payment already processed");
//            }

            $description = 'תשלום עבור הזמנה #' . $order['orderIDBySite'];

            // if it's spa order - change description
            if ($order['parentOrder'] > 0){
                $desc = [];

                $que = "SELECT orders.treatmentLen, treatments.treatmentName 
                        FROM `orders` INNER JOIN `treatments` USING (`treatmentID`) 
                        WHERE orders.parentOrder = " . $orderID . " AND  orders.orderID <> " . $orderID . "
                        ORDER BY orders.orderID";
                $treatments  = udb::single_list($que);
                foreach($treatments as $treatment)
                    $desc[] = $treatment['treatmentName'] . " " . $treatment['treatmentLen'] . " דקות";

                if ($order['extras']){
                    $que = "SELECT `extraID`, `extraName` FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE s.siteID = " . $order['siteID'] . " AND included = 0 ORDER BY e.showOrder";
                    $extras = udb::key_value($que);

                    $orderExtras = json_decode($order['extras'], true) ?: [];

                    foreach($orderExtras['extras'] as $extra)
                        if($extras[$extra['extraID']])
                            $desc[] = $extra['count'] . " x " . $extras[$extra['extraID']];
                }

                $description = trim($description . ': ' . implode(" | ", $desc));
            }

            $start = date('Y-m-d H:i:s');

            // trying to send charge to provider
            try {
                $res = $client->directPay($input['sum'], $description, $payData['tokenData'], $payID, $input['pays'] ?: 1, $isFrozen ? $payData['authCode'] : null);
            }
            catch (Exception $ie){
                if (empty($res))
                    $res = ['success' => false, 'error' => 'שגיאה בקבלנת תשובה מספק אשראי - ' . $ie->getMessage()];
            }
            finally {
                if (empty($res))
                    $res = ['success' => false, 'error' => 'שגיאה בקבלנת תשובה מספק אשראי'];
            }

            $inData = [
                'lineID'   => $payID,
                'exID'     => $payData['exID'],
                'sum'      => $input['sum'],
                'payments' => $input['pays']
            ];
            if ($isFrozen)
                $inData['authCode'] = $payData['authCode'];

            // log charge
            $payque = [
                'payType'    => 'ccard',
                'buserID'    => $_CURRENT_USER->id(),
                'paymentID'  => $res['_transID'],
                'orderID'    => $orderID,
                'startTime'  => $start,
                'endTime'    => date('Y-m-d H:i:s'),
                'subType'    => 'direct',
                'complete'   => $res['success'] ? 1 : 0,
                'sum'        => $res['sum'],
                'inputData'  => json_encode($inData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'resultData' => json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ];

            if (!$res['success'])
                $payque['invoice'] = '-';
            elseif ($res['invoiceURL'])
                $payque['invoice'] = $res['invoiceURL'];

            $lineID = udb::insert('orderPayments', $payque);

            if ($isFrozen){
                udb::update("orderPayments", ['cancelled' => 1, 'cancelData' => json_encode(['used' => true, 'use_row' => $lineID], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)], "`lineID` = " . $payID);
                //udb::query('COMMIT');       // completing transaction
            }

            // fail charge
            if (!$res['success'])
                throw new Exception(($res['ccode'] ? 'שגיאה ' . $res['ccode'] : '') . ' ' . $res['error']);

            if ($res['phone']){
                $siteName = udb::single_value("SELECT sites.siteName FROM `sites` INNER JOIN `orders` USING(`siteID`) WHERE orders.orderID = " . $orderID);
                sms_payment_success($res, $siteName, $res['phone']);
            }

            $user_log->save('payment', $siteID, $orderID, ['lineID' => $lineID]);
            break;

        case 'directRefund':
            $input = typemap($_POST, [
                'sum'   => 'float',
//                'pays'  => 'int',
                'cvv'   => 'numeric',
                'iname' => 'string'
            ]);

            if (!$input['sum'] || $input['sum'] < 0)
                throw new Exception('Empty or illegal refund amount');
            if (!$orderID)
                throw new Exception('מספר הזמנה שגוי');

            $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `orderID` = " . $orderID . " AND `lineID` = " . $payID);
            $payData = json_decode($payment['resultData'], true);

            // check if client can charge saved cards
            $canCharge = udb::single_value("SELECT IF(`masof_active` = 1 AND `masof_number` > '' AND `masof_no_cvv` = 1 AND `masof_no_charge` = 0, 1, 0) FROM `sites` WHERE `siteID` = " . $siteID);

            if (!$payment)
                throw new Exception('Cannot find payment for booking #' . $orderID);
            if ($payment['cancelled'] || $payment['cancelData'])
                throw new Exception('Payment already cancelled/refunded');
            if (!$payment['token'] || !$payData['tokenData'] || $payData['tokenData']['error'])
                throw new Exception('Cannot find credit card data');
            if (!$payment['complete'] || !$payment['endTime'])
                throw new Exception('Payment failed or was not completed');
            if (strcasecmp($payment['payType'], 'ccard') || strcmp($payment['subType'], 'card_test'))        // if not cc or wasn't card check
                throw new Exception('Can only refund credit card for security');
            if (!$payData['exID'])
                throw new Exception('Cannot find transaction ID.');     // should not happen
            if (!$canCharge)
                throw new Exception('You are not allowed to refund saved cards');

            if ($input['cvv'])
                $payData['tokenData']['cvv'] = $input['cvv'];

            //$client = YaadPay::getTerminal($siteID);
            $client = Terminal::bySite($siteID);
            if (!$client)
                throw new Exception("There's no terminal or the terminal is inactive");

            // replacing "token name" with one from popup
            if ($input['iname'])
                $payData['tokenData']['name'] = $input['iname'];

            $res = $client->directRefund($input['sum'], 'זיכוי הזמנה #' . $order['orderIDBySite'], $payData['tokenData'], $payID, 1);
            if (!$res['success'])
                throw new Exception(($res['ccode'] ? 'שגיאה ' . $res['ccode'] : '') . ' ' . $res['error']);

            $payque = [
                'payType'    => 'refund',
                'buserID'    => $_CURRENT_USER->id(),
                'paymentID'  => $res['_transID'],
                'orderID'    => $orderID,
                'startTime'  => date('Y-m-d H:i:s'),
                'endTime'    => date('Y-m-d H:i:s'),
                'subType'    => 'direct',
                'complete'   => $res['success'] ? 1 : 0,
                'sum'        => -1 * $res['sum'],
                'inputData'  => json_encode(['lineID' => $payID, 'exID' => $payData['exID'], 'sum' => $input['sum'], 'payments' => $input['pays']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'resultData' => json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ];

            if ($res['invoiceURL'])
                $payque['invoice'] = $res['invoiceURL'];

            $lineID = udb::insert('orderPayments', $payque);

//            if ($res['phone']){
//                $siteName = udb::single_value("SELECT sites.siteName FROM `sites` INNER JOIN `orders` USING(`siteID`) WHERE orders.orderID = " . $orderID);
//                sms_payment_success($res, $siteName, $res['phone']);
//            }

            $user_log->save('payment_refund', $siteID, $orderID, ['lineID' => $payID, 'payType' => $payment['payType'], 'sum' => $input['sum'], 'endTime' => $payment['endTime'], 'newLineID' => $lineID]);
            break;

        case 'payDelete':
            if (!$orderID)
                throw new Exception('מספר הזמנה שגוי');
            if (!$_CURRENT_USER->has($siteID))
                throw new Exception('Access denied to site ' . $siteID);

            $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `orderID` = " . $orderID . " AND `lineID` = " . $payID);

            if (!$payment)
                throw new Exception('Cannot find payment for booking #' . $orderID);
            if ($payment['cancelled'] || $payment['cancelData'])
                throw new Exception('Payment already cancelled/refunded');
            if (!$payment['complete'] || !$payment['endTime'])
                throw new Exception('Payment failed or was not completed');
            if ($payment['payType'] == 'ccard' || $payment['payType'] == 'refund' /*|| ($payment['payType'] == 'coupon' && $payment['provider'] == 'vouchers')*/)
                throw new Exception('Cannot delete this payment payment');

            $inputData = json_decode($payment['inputData'], true);
            $payData   = json_decode($payment['resultData'], true);

            if ($payment['payType'] == 'coupon' && $payment['provider'] == 'vouchers'){
                $inData = json_decode($payment['inputData'], true);
                $coupon = CouponManager::getCoupon($inData['cpn'], $siteID);

                if (!$coupon)
                    throw new Exception("Cannot find coupon " . $inData['cpn']);

                $source = 'order #' . $payment['orderID'];

                $delOk = $payData['couponUse'] ? $coupon->unUse_by_id($payData['couponUse'], $_CURRENT_USER->id(), $source) : $coupon->unUse_by_time_sum($payment['endTime'], $payment['sum'], $_CURRENT_USER->id(), $source);
                if (!$delOk)
                    throw new Exception('Cannot delete coupon usage');

                $payment['invoice'] = '';       // hack to allow row deletion
            }
            // check if it's protel charge
            elseif ($payment['payType'] == 'guest' && $payment['provider'] == 'guest' && $payment['paymentID'] > 1000000){
                // check that site still active at Protel
                $protelID = udb::single_value("SELECT `protelID` FROM `sites` WHERE `siteID` = " . $siteID);
                if ($protelID)
                    $protelID = udb::single_value("SELECT `id` FROM `protel_sites` WHERE `active` = 1 AND `id` = " . intval($protelID));

                if (!$protelID)
                    throw new Exception("לא ניתן לבטל חיוב על החדר");
                if (!$payData['protelCorrID'])
                    throw new Exception("Cannot find payment reference");

                require_once __DIR__ . "/../api/protel/classes_prod.php";

                $innerID = intval($inputData['inner']);

                $protelOrder = udb::single_row("SELECT * FROM `protel_orders` WHERE `hotelID` = " . $protelID . " AND `innerID` = " . $innerID);
                if (!$protelOrder)
                    throw new Exception("לא נמצא הזמנה שבחרת");
                if (!in_array($protelOrder['status'], ['Reserved', 'In-house']))
                    throw new Exception("לא ניתן לשנות חיובים בהזמנה סגורה");

                $description = $inputData['desc'];
                if (!$description)
                    $description = ($order['parentOrder'] > 0) ? 'טיפול ספא' . ': ' . OrderSpaMain::full_description($orderID, $order['siteID'], $order['extras']) : 'זיכוי';

                // creating payment row
                $refundID = udb::insert('orderPayments', [
                    'payType'    => 'refund',
                    'buserID'    => $_CURRENT_USER->id(),
                    'orderID'    => $orderID,
                    'startTime'  => date('Y-m-d H:i:s'),
                    'sum'        => -$payment['sum'],
                    'inputData'  => json_encode(['lineID' => $payID, 'sum' => $payment['sum'], 'chargeRef' => $payData['protelCorrID']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ]);

                $prToken = ['CHARGE-SPA' , $protelOrder['protelOrderID'], $protelOrder['innerID'], $orderID, $refundID];

                // sending charge to Protel
                $charge = new HTNG_ChargePostingRQ($protelOrder['hotelID'], $protelOrder['protelOrderID'], implode('-', $prToken), $payID, '');
                $ac = $charge->add_revenue(-VAT::before_vat($payment['sum']), 'ILS', 4700, $description);

                $clog = '';
                $className = get_class($charge);

                $logID = udb::insert('protel_comm_log', [
                    'type' => 'out',
                    'corrID' => $charge->_CorrelationID,
                    'name' => $className,
                    'request' => '' . $charge,
                    'parsed'  => serialize($charge)
                ]);

                $client = new MySoapCaller;
                $response = $client->send($className, $charge, $charge->_CorrelationID, $clog);

                udb::update('protel_comm_log', ['response' => $response], '`id` = ' . $logID);
                udb::update('orderPayments', ['paymentID' => 1000000 + $logID], "`lineID` = " . $refundID);

                // protel works in asyncronous way, so we need to wait for response...
                $try = 0;
                do {
                    sleep(3);

                    $resp = udb::single_row("SELECT `resultData`, `complete` FROM `orderPayments` WHERE `lineID` = " . $refundID . " AND `endTime` IS NOT NULL AND `resultData` IS NOT NULL");
                    if ($resp || ++$try >= 5)
                        break;
                } while(1);

                if (empty($resp))
                    throw new Exception("אין תשובה מבית מלון, נא להמתין או להתקשר למשרד.");
                if (!$resp['complete'])
                    throw new Exception("זיכוי על החד נכשל");

                $res = json_decode($resp);
                $res['innerRef'] = $payID;

                udb::update('orderPayments', ['cancelData' => json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)], '`lineID` = ' . $payID);
            }
            elseif ($payment['invoice'] && $payment['invoice'] != '-'){
                try {
                    $invData = $payData['invoiceData'] ?? $payData;

                    $client = Terminal::bySite($siteID);
                    if ($client->engine() == 'Yaad')
                        throw new Exception('Yaad');

                    list($today, $now) = explode(' ', date('Y-m-d H:i:s'));

                    $orderSite = udb::single_value("SELECT `orderIDBySite` FROM `orders` WHERE `orderID` = " . $orderID);

                    $refType = $payment['payType'];
                    if ($payment['payType'] == 'check'){
                        $refType = 'transfer';
                        $invData['comment'] = Dictionary::translate("זיכוי של צ'ק");
                    }

                    $res = $client->sendPrintout($refType, $payment['sum'], 'זיכוי תשלום עבור הזמנה ' . $orderSite, $invData, $payID, $client->refund_invoice_type(), true);

                    $res['buserID'] = $_CURRENT_USER->id();

                    udb::update('orderPayments', ['cancelData' => json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)], '`lineID` = ' . $payID);

                    $resque = [
                        'payType'    => 'refund',
                        'buserID'    => $_CURRENT_USER->id(),
                        'paymentID'  => $res['_transID'],
                        'orderID'    => $orderID,
                        'startTime'  => $today . ' ' . $now,
                        'endTime'    => date('Y-m-d H:i:s'),
                        'complete'   => 1,
                        'sum'        => -1 * ($res['sum'] ?: $payment['sum']),
                        'inputData'  => json_encode(['lineID' => $payID, 'exID' => $invData['exID'], 'sum' => $payment['sum']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'resultData' => json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    ];

                    if ($res['invoiceURL'])
                        $resque['invoice'] = $res['invoiceURL'];

                    $lineID = udb::insert('orderPayments', $resque);
                }
                catch (Exception $e){
                    $payment['invoice'] = $e->getMessage();
                }
            }

            if (!$payment['invoice'] || $payment['invoice'] == '-')
                udb::query("DELETE FROM `orderPayments` WHERE `payType` NOT IN ('ccard', 'refund') /*AND (`payType` <> 'coupon' OR `provider` <> 'vouchers')*/ AND `orderID` = " . $orderID . " AND `lineID` = " . $payID);

            updateOrderAdvance($orderID);
            $user_log->save('payment_remove', $siteID, $orderID, ['lineID' => $payment['lineID'], 'payType' => $payment['payType'], 'sum' => $payment['pay'], 'endTime' => $payment['endTime']]);
            break;

        case 'payCancel':
        case 'payRefund':
            if (!$orderID)
                throw new Exception('מספר הזמנה שגוי');
            if (!$_CURRENT_USER->has($siteID))
                throw new Exception('Access denied to site ' . $siteID);

            $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `orderID` = " . $orderID . " AND `lineID` = " . $payID);

            if (!$payment)
                throw new Exception('Cannot find payment for booking #' . $orderID);
            if ($payment['cancelled'] || $payment['cancelData'])
                throw new Exception('Payment already cancelled/refunded');
            if (!$payment['complete'] || !$payment['endTime'])
                throw new Exception('Payment failed or was not completed');
            if ((strcasecmp($payment['payType'], 'ccard') && strcasecmp($payment['payType'], 'member2')) || $payment['subType'] == 'card_test')        // if not cc or subscription or wasn't payment
                throw new Exception('Can only refund credit card payments');

            $p_date = substr($payment['endTime'], 0, 10);
            $p_time = substr($payment['endTime'], 11, 8);

            list($today, $now) = explode(' ', date('Y-m-d H:i:s'));

            $payData = json_decode($payment['resultData'], true);

            if ($payment['payType'] == 'member2'){
                if (!$payData['success'] || !$payData['subID'] || !$payData['stID'])
                    throw new Exception('Problem with cancelling payment by subscription: ' . print_r($payData, true));

                $subs = udb::single_row("SELECT * FROM `subscriptions` WHERE `subID` = " . intval($payData['subID']) . " AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
                if (!$subs)
                    throw new Exception('Cannot find payment subscription');
                if (!$subs['active'])
                    throw new Exception('Subscription is no longer active');

                $stPayID = udb::single_value("SELECT `payID` FROM `subscriptionTreatments` WHERE `subID` = " . $subs['subID'] . " AND `stID` = " . intval($payData['stID']));
                if ($stPayID != $payment['lineID'])
                    throw new Exception('Incorrect payment ID in subscription');

                udb::query("UPDATE `subscriptionTreatments` SET `payID` = NULL WHERE `subID` = " . $subs['subID'] . " AND `stID` = " . intval($payData['stID']) . " AND `payID` = " . intval($payment['lineID']));
                udb::update('orderPayments', ['cancelData' => json_encode([
                    'cancelled' => date('Y-m-d H:i:s'),
                    'user'      => $_CURRENT_USER->id(),
                ], JSON_UNESCAPED_UNICODE)], "`lineID` = " . $payment['lineID']);

                break;
            }

            // if got till here - it's a cc payment

            $client = Terminal::bySite($siteID);
            if (!$client)
                throw new Exception("There's no terminal or the terminal is inactive");

            if (!$payData['exID'])
                throw new Exception('Cannot find transaction ID.');

            // suspended deal / frozen sum  - the case will BREAK inside this block, no other code will be executed
            if ($payment['subType'] == 'freeze_sum'){
                $res = $client->unfreezeSum($payment['sum'], $payData['tokenData'], $payData['exID'], $payID);
                if ($res['success']){
                    $rse['used']    = false;
                    $res['buserID'] = $_CURRENT_USER->id();

                    udb::update('orderPayments', ['cancelled' => 1, 'cancelData' => json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)], '`lineID` = ' . $payID);

                    $user_log->save('frozen_deal_cancel', $siteID, $orderID, ['lineID' => $payID, 'payType' => $payment['payType'], 'sum' => $payment['sum'], 'endTime' => $payment['endTime']]);

                    break;
                }
                else
                    throw new Exception($res['error'] ?: 'Failed to cancel suspended deal.');
            }

            // if from previous day or already past "commit" time - must refund, otherwise - cancel
            if ($_POST['act'] == 'payCancel'){
                if ((strcmp($p_date, $today) < 0 || strcmp($now, '23:20:00') >= 0))
                    throw new Exception('Cannot cancel, payment already commited to SHVA. Please reload page');

                $res = ($client->engine() == 'Yaad') ? $client->payCancel($payData['exID'], $payment['paymentID']) : $client->payCancel($payData['exID'], $payData);
                if ($res['success']){
                    $res['buserID'] = $_CURRENT_USER->id();

                    udb::update('orderPayments', ['cancelled' => 1, 'cancelData' => json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)], '`lineID` = ' . $payID);

                    $user_log->save('payment_cancel', $siteID, $orderID, ['lineID' => $payID, 'payType' => $payment['payType'], 'sum' => $payment['sum'], 'endTime' => $payment['endTime']]);
                }
                elseif ($res['ccode'] == 5125){      // debit or instant card - cannot cancel deals, only refund
                    $_POST['act'] = 'payRefund';
                    $result['warning'] = 'לא ניתן לבצע ביטול עסקה בכרטיס הזה. מבצע זיכוי במקום.';
                }
                else
                    throw new Exception($res['error'] ?: 'Failed to cancel transaction.');
            }

            // can come from input OR from "payCancel"
            if ($_POST['act'] == 'payRefund') {
                $res = ($client->engine() == 'Yaad') ? $client->payRefund($payData['exID'], $payment['sum']) : $client->payRefund($payData['exID'], $payment['sum'], $payData);
                if (!$res['success'])
                    throw new Exception($res['error'] ?: 'Failed to refund transation.');

                $res['buserID'] = $_CURRENT_USER->id();

                udb::update('orderPayments', ['cancelData' => json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)], '`lineID` = ' . $payID);

                $resque = [
                    'payType'    => 'refund',
                    'buserID'    => $_CURRENT_USER->id(),
                    'paymentID'  => $res['_transID'],
                    'orderID'    => $orderID,
                    'startTime'  => $today . ' ' . $now,
                    'endTime'    => date('Y-m-d H:i:s'),
                    'complete'   => 1,
                    'sum'        => -1 * ($res['sum'] ?: $payment['sum']),
                    'inputData'  => json_encode(['lineID' => $payID, 'exID' => $payData['exID'], 'sum' => $payment['sum']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'resultData' => json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ];

                if ($res['invoiceURL'])
                    $resque['invoice'] = $res['invoiceURL'];

                $lineID = udb::insert('orderPayments', $resque);

                $user_log->save('payment_refund', $siteID, $orderID, ['lineID' => $payID, 'payType' => $payment['payType'], 'sum' => $payment['sum'], 'endTime' => $payment['endTime'], 'newLineID' => $lineID]);
            }

            updateOrderAdvance($orderID);

            break;

        case 'checkCoupon':
            $cnum = typemap($_POST['cnum'], 'string');
            $result['csum'] = ($cnum && strlen($cnum) >= 6 && ($coupon = CouponManager::getCoupon($cnum, $siteID))) ? $coupon->available : 0;
            break;

        case 'checkSubscription':
            $subNum = typemap($_POST['snum'], 'numeric');
            $siteID = $order['siteID'] ?: $_CURRENT_USER->active_site();

            if (!$subNum || strlen($subNum) < 8)
                throw new Exception('Illegal subscription number');

            $treat = udb::single_row("SELECT * FROM `subscriptions` WHERE `active` = 1 AND `subNumber` = " . $subNum);
            if (!$treat)
                throw new Exception("Subscription " . prettySubsNumber($subNum) . " is inactive or doesn't exist.");
            if ($treat['siteID'] != $siteID)
                throw new Exception("Subscription " . prettySubsNumber($subNum) . " belongs to different owner.");

            $stData = udb::single_row("SELECT * FROM `subscriptionTreatments` WHERE `subID` = " . $treat['subID'] . " AND `payID` IS NULL LIMIT 1");
            if (!$stData)
                throw new Exception("Subscription " . prettySubsNumber($subNum) . " has been used up already.");

            $result['csum'] = $stData['price'];
            break;

        case 'getInvoice':
            if (!$orderID)
                throw new Exception('מספר הזמנה שגוי');

            $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `orderID` = " . $orderID . " AND `lineID` = " . $payID);
            if ($payment['invoice'])
                throw new Exception(($payment['invoice'] == '-') ? "לא ניתן להוציא חשבונית לחיוב הזה" : "כבר נוצרה חשבונית עבור תשלום זה");

            $payData = json_decode($payment['resultData'], true);

            $canInvoice = udb::single_value("SELECT `masof_invoice` FROM `sites` WHERE `siteID` = " . $siteID);

            if (!$payment)
                throw new Exception('Cannot find payment for booking #' . $orderID);
            if ($payment['cancelled'] || $payment['cancelData'])
                throw new Exception('Payment already cancelled/refunded');
            if (!$payment['complete'] || !$payment['endTime'])
                throw new Exception('Payment failed or was not completed');
            if (!strcasecmp($payment['payType'], 'ccard'))        // if it's cc payment
                throw new Exception('Invoices for CC payments are issued automatically');
            if (!$canInvoice)
                throw new Exception('You do not have invoice service');

            $input = typemap($_POST, [
                'tz'        => 'numeric',
                'full_name' => 'string',
                'email'     => 'email',
                'desc'      => 'string',
                'bank'      => 'int',
                'pan'       => 'string',
                'branch'    => 'string',
                'docNum'    => 'numeric',
                'authNum'   => 'string'
            ]);

            $input['docDate'] = implode('', array_reverse(explode('/', $_POST['docDate'])));

            //$client = YaadPay::getTerminal($siteID);
            $client = Terminal::bySite($siteID);
            if (!$client)
                throw new Exception("There's no terminal or the terminal is inactive");

            $pType = $payment['payType'];
            if ($pType == 'coupon'){
                $allowed = $payment['provider'] ? udb::single_value("SELECT `invoice` FROM `sitePayTypes` WHERE `siteID` = " . $siteID . " AND `paytypekey` = '" . udb::escape_string($payment['provider']) . "'") : 0;
                if (!$allowed)
                    throw new Exception("לא ניתן להוציא חשבונית לחיוב הזה");
            }

            if ($client->engine() == 'Yaad'){
                $payBy = '';

                switch($payment['payType']){
                    case 'transfer' : $pType = 'Multi'; break;
                    case 'check'    : $pType = 'Check'; break;
                    case 'cash'     : $pType = 'Cash';  break;
                    default         :
                        $pType = 'Cash';
                        $payBy = 'שולם דרך ' . UserUtilsNew::method($payment['payType'], $payment['provider']);
                        break;
                }

                $res = $client->sendPrintout($pType, $payment['sum'], $input['desc'], $input, $payID, $payBy);
            }
            else
                $res = $client->sendPrintout($pType, $payment['sum'], $input['desc'], $input, $payID);

            if (!$res['success'])
                throw new Exception($res['error'] ?: "Invoice failed");

            $payData['invoice'] = $res['invoice'];
            $payData['invoiceData'] = $res;

            udb::update('orderPayments', [
                'paymentID'  => $res['_transID'] ?: 0,
                'invoice'    => $res['invoiceURL'] ?: '-',
                'resultData' => json_encode($payData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ], '`lineID` = ' . $payID);

            $user_log->save('payment_invoice', $siteID, $orderID, ['lineID' => $payID]);

            $result['ok'] = 1;
            break;


        case 'directPayPop':
            if (!$orderID)
                throw new Exception('מספר הזמנה שגוי');

			$creditPay = intval($_POST['sum'] ?? 0) ?: "";

            $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `orderID` = " . $orderID . " AND `lineID` = " . $payID);
            $payData = json_decode($payment['resultData'], true);

            if (!$payment)
                throw new Exception('Cannot find payment for booking #' . $orderID);
            if ($payment['cancelled'] || $payment['cancelData'])
                throw new Exception('Payment already cancelled/refunded');
            if (!$payment['token'] || !$payData['tokenData'] || $payData['tokenData']['error'])
                throw new Exception('Cannot find credit card data');
            if (!$payment['complete'] || !$payment['endTime'])
                throw new Exception('Payment failed or was not completed');
            if (strcasecmp($payment['payType'], 'ccard') || !in_array($payment['subType'], ['card_test', 'freeze_sum']))        // if not cc or wasn't card check
                throw new Exception('Can only charge credit card for security');

            //$client = YaadPay::getTerminal($siteID);
            $client = Terminal::bySite($siteID);
            if (!$client)
                throw new Exception("There's no terminal or the terminal is inactive");
            if (!$client->has_cc_charge)
                throw new Exception("פעולת חיוב כרטיס אשראי חסומה בהגדרות המסוף");

            ob_start();
?>

<div class="popup payAmount" id="payAmount" data-order="<?=$orderID?>" data-pid="<?=$payID?>" style="display:block">
    <div class="popup_container">
        <div class="close" onclick="$('#payAmount').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title">ביצוע תשלום</div>
        <div class="con">ביצוע תשלום באמצעות כרטיס לביטחון כרטיס שמסתיים בספרות <?=($payData['last4'] ?: '****')?></div>
        <div class="form">
            <div class="inputWrap full">
                <input type="text" inputmode="text" name="iname" id="dpName" value="<?=addslashes($payData['tokenData']['name'])?>" />
                <label for="dpName">חשבונית על שם</label>
            </div>
            <div class="inputWrap full">
			
                <input type="text" inputmode="numeric" value="<?=$creditPay?>" name="amount" id="dpAmount" value="">
                <label for="dpAmount">סכום תשלום</label>
            </div>
            <div class="inputWrap select">
                <select name="payments" id="dpPayments">
<?php
            for($i = 1; $i <= 12; ++$i)
                echo '<option value="' , $i , '">' , $i , '</option>';
?>
                </select>
                <label for="dpPayments">תשלומים</label>
            </div>
            <div class="inputWrap full">
                <input type="text" inputmode="numeric" name="cvv" id="dpcvv" value="" />
                <label for="dpcvv">CVV (3 ספרות בגב הכרטיס)</label>
            </div>
            <div class="submit" onclick="initDirectPay()">ביצוע תשלום
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 26" width="34" height="26"><path class="shp0" d="M32 7.7L16.3 7.7 16.3 2 2 13 16.3 24 16.3 18.3 32 18.3 32 7.7Z"></path></svg>
            </div>
            <img src="/user/assets/img/security_<?=strtolower($client->engine())?>.jpg" style="max-width:none;margin:0 -40px 0 0" alt="" />
        </div>
    </div>
</div>
<?php
            $result['html'] = ob_get_clean();
            break;

        case 'directRefundPop':
            if (!$orderID)
                throw new Exception('מספר הזמנה שגוי');

            $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `orderID` = " . $orderID . " AND `lineID` = " . $payID);
            $payData = json_decode($payment['resultData'], true);

            if (!$payment)
                throw new Exception('Cannot find payment for booking #' . $orderID);
            if ($payment['cancelled'] || $payment['cancelData'])
                throw new Exception('Payment already cancelled/refunded');
            if (!$payment['token'] || !$payData['tokenData'] || $payData['tokenData']['error'])
                throw new Exception('Cannot find credit card data');
            if (!$payment['complete'] || !$payment['endTime'])
                throw new Exception('Payment failed or was not completed');
            if (strcasecmp($payment['payType'], 'ccard') || strcmp($payment['subType'], 'card_test'))        // if not cc or wasn't card check
                throw new Exception('Can only refund credit card for security');

            $client = Terminal::bySite($siteID);
            if (!$client)
                throw new Exception("There's no terminal or the terminal is inactive");
            if (!$client->has_cc_charge)
                throw new Exception("פעולת זיכוי כרטיס אשראי חסומה בהגדרות המסוף");

            ob_start();
?>
<div class="popup payAmount" id="payAmount" data-order="<?=$orderID?>" data-pid="<?=$payID?>" style="display:block">
    <div class="popup_container">
        <div class="close" onclick="$('#payAmount').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title">ביצוע זיכוי</div>
        <div class="con">ביצוע זיכוי לכרטיס שמסתיים בספרות <?=($payData['last4'] ?: '****')?></div>
        <div class="form">
            <div class="inputWrap full">
                <input type="text" inputmode="text" name="iname" id="dpName" value="<?=addslashes($payData['tokenData']['name'])?>" />
                <label for="dpName">חשבונית על שם</label>
            </div>
            <div class="inputWrap full">
                <input type="text" inputmode="numeric" name="amount" id="dpAmount" value="">
                <label for="dpAmount">סכום זיכוי</label>
            </div>
            <!-- div class="inputWrap select">
                <select name="payments" id="dpPayments">
<?php
            for($i = 1; $i <= 12; ++$i)
                echo '<option value="' , $i , '">' , $i , '</option>';
?>
                </select>
                <label for="dpPayments">תשלומים</label>
            </div -->
            <div class="inputWrap full">
                <input type="text" inputmode="numeric" name="cvv" id="dpcvv" value="" />
                <label for="dpcvv">CVV (3 ספרות בגב הכרטיס)</label>
            </div>
            <div class="submit" onclick="initDirectRefund()">ביצוע זיכוי
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 26" width="34" height="26"><path class="shp0" d="M32 7.7L16.3 7.7 16.3 2 2 13 16.3 24 16.3 18.3 32 18.3 32 7.7Z"></path></svg>
            </div>
            <img src="/user/assets/img/security_<?=strtolower($client->engine())?>.jpg" style="max-width:none;margin:0 -40px 0 0" alt="" />
        </div>
    </div>
</div>
<?php
            $result['html'] = ob_get_clean();
            break;

/*          case 'cashPopup':
                ob_start(); ?>
                <div class="popup payAmount" id="payAmount" data-order="<?=$orderID?>" data-pid="<?=$payID?>" style="display:block">
                    <div class="popup_container">
                        <div class="close" onclick="$('#payAmount').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
                        <div class="title">הפקת חשבונית</div>
                        <div class="con">ביצוע תשלום באמצעות כרטיס לביטחון כרטיס שמסתיים בספרות <?=($payData['last4'] ?: '****')?></div>
                        <div class="form">
                            <div class="inputWrap full">
                                <input type="text" inputmode="numeric" name="tZehoot" id="tZehoot" value="">
                                <label for="tZehoot">תעודת זהות</label>
                            </div>
                            <div class="inputWrap full">
                                <input type="text" name="first_name" id="first_name" value="">
                                <label for="first_name">שם פרטי</label>
                            </div>
                            <div class="inputWrap full">
                                <input type="text" name="last_name" id="last_name" value="">
                                <label for="last_name">שם משפחה</label>
                            </div>
                            <div class="inputWrap full">
                                <input type="text" name="trans_desc" id="trans_desc" value="">
                                <label for="trans_desc">תיאור עסקה</label>
                            </div>
                            <div class="inputWrap full">
                                <input type="email" name="email" id="email" value="">
                                <label for="email">אימייל</label>
                            </div>
                            <!-- div class="inputWrap full">
                                <input type="text" inputmode="numeric" name="amount" value="333">
                                <label for="amount">CCV (3 ספרות בגב הכרטיס)</label>
                            </div -->
                            <div class="submit" onclick="initDirectPay()">הפקת חשבונית
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 26" width="34" height="26"><path class="shp0" d="M32 7.7L16.3 7.7 16.3 2 2 13 16.3 24 16.3 18.3 32 18.3 32 7.7Z"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
                <?php $result['html'] = ob_get_clean();
                        break;

                        case 'checkPopup':
                            ob_start(); ?>
                            <div class="popup payAmount" id="payAmount" data-order="<?=$orderID?>" data-pid="<?=$payID?>" style="display:block">
                                <div class="popup_container">
                                    <div class="close" onclick="$('#payAmount').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
                                    <div class="title">הפקת חשבונית</div>
                                    <div class="con">ביצוע תשלום באמצעות כרטיס לביטחון כרטיס שמסתיים בספרות <?=($payData['last4'] ?: '****')?></div>
                                    <div class="form">
                                        <div class="inputWrap full">
                                            <input type="text" inputmode="numeric" name="tZehoot" id="tZehoot" value="">
                                            <label for="tZehoot">תעודת זהות</label>
                                        </div>
                                        <div class="inputWrap full">
                                            <input type="text" name="first_name" id="first_name" value="">
                                            <label for="first_name">שם פרטי</label>
                                        </div>
                                        <div class="inputWrap full">
                                            <input type="text" name="last_name" id="last_name" value="">
                                            <label for="last_name">שם משפחה</label>
                                        </div>
                                        <div class="inputWrap full">
                                            <input type="text" name="trans_desc" id="trans_desc" value="">
                                            <label for="trans_desc">תיאור עסקה</label>
                                        </div>
                                        <div class="inputWrap full">
                                            <input type="email" name="email" id="email" value="">
                                            <label for="email">אימייל</label>
                                        </div>
                                        <div class="inputWrap full">
                                            <input type="text" name="account_number" id="account_number" value="">
                                            <label for="account_number">מספר חשבון</label>
                                        </div>
                                        <div class="inputWrap full">
                                            <input type="text" name="branch_number" id="branch_number" value="">
                                            <label for="branch_number">מספר סניף</label>
                                        </div>
                                        <div class="inputWrap full">
                                            <input type="text" name="bank_code" id="bank_code" value="">
                                            <label for="bank_code">מספר בנק</label>
                                        </div>
                                        <div class="inputWrap full">
                                            <input type="text" name="" id="payment_date" class="datePick" value="">
                                            <label for="payment_date">מועד פרעון</label>
                                        </div>
                                        <!-- div class="inputWrap full">
                                            <input type="text" inputmode="numeric" name="amount" value="333">
                                            <label for="amount">CCV (3 ספרות בגב הכרטיס)</label>
                                        </div -->
                                        <div class="submit" onclick="initDirectPay()">הפקת חשבונית
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 26" width="34" height="26"><path class="shp0" d="M32 7.7L16.3 7.7 16.3 2 2 13 16.3 24 16.3 18.3 32 18.3 32 7.7Z"></path></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $result['html'] = ob_get_clean();
                                    break;*/

        case 'invoicePop':
            if (!$orderID)
                throw new Exception('מספר הזמנה שגוי');

            $site    = udb::single_row("SELECT * FROM `sites` WHERE `siteID` = " . $siteID);

            $payment = udb::single_row("SELECT * FROM `orderPayments` WHERE `orderID` = " . $orderID . " AND `lineID` = " . $payID);
            $payData = json_decode($payment['resultData'], true);

            $siteOrderID = udb::single_value("SELECT `orderIDBySite` FROM `orders` WHERE `orderID` = " . $orderID);

            if (!$payment)
                throw new Exception('Cannot find payment for booking #' . $orderID);
            if ($payment['cancelled'] || $payment['cancelData'])
                throw new Exception('Payment already cancelled/refunded');
            if (!$payment['complete'] || !$payment['endTime'])
                throw new Exception('Payment failed or was not completed');
            if (!strcasecmp($payment['payType'], 'ccard'))        // if it's cc payment
                throw new Exception('Invoices for CC payments are issued automatically');
            if (!$site['masof_invoice'])
                throw new Exception('You do not have invoice service');

            if ($payment['payType'] == 'coupon'){
                $allowed = $payment['provider'] ? udb::single_value("SELECT `invoice` FROM `sitePayTypes` WHERE `siteID` = " . $siteID . " AND `paytypekey` = '" . udb::escape_string($payment['provider']) . "'") : 0;
                if (!$allowed)
                    throw new Exception("לא ניתן להוציא חשבונית לחיוב הזה");
            }

            ob_start();

            $desc = ($order['timeFrom'] == '0000-00-00 00:00:00') ? 'הזמנה ' . $siteOrderID . ' - ' . htmlspecialchars($site['siteName']) : 'הזמנה ' . $siteOrderID . " ב'" . htmlspecialchars($site['siteName']) . "' " . db2date(substr($order['timeFrom'], 0, 10), '.', 2) . ' עד ' . db2date(substr($order['timeUntil'], 0, 10), '.', 2);

            // if it's spa order - change description
            if ($order['parentOrder'] > 0){
                $desc = [];

                $que = "SELECT orders.treatmentLen, treatments.treatmentName 
                        FROM `orders` INNER JOIN `treatments` USING (`treatmentID`) 
                        WHERE orders.parentOrder = " . $orderID . " AND  orders.orderID <> " . $orderID . "
                        ORDER BY orders.orderID";
                $treatments  = udb::single_list($que);
                foreach($treatments as $treatment)
                    $desc[] = $treatment['treatmentName'] . " " . $treatment['treatmentLen'] . " דקות";

                if ($order['extras']){
                    $que = "SELECT `extraID`, `extraName` FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) WHERE s.siteID = " . $order['siteID'] . " AND included = 0 ORDER BY e.showOrder";
                    $extras = udb::key_value($que);

                    $orderExtras = json_decode($order['extras'], true) ?: [];

                    foreach($orderExtras['extras'] as $extra)
                        if($extras[$extra['extraID']])
                            $desc[] = $extra['count'] . " x " . $extras[$extra['extraID']];
                }

                $desc = trim('תשלום עבור הזמנה #' . $order['orderIDBySite'] . ': ' . implode(" | ", $desc));
            }
?>
<div class="popup payAmount" id="payAmount" data-order="<?=$orderID?>" data-pid="<?=$payID?>" style="display:block">
    <div class="popup_container">
        <div class="close" onclick="$('#payAmount').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title">הפקת חשבונית</div>
        <div class="con">עבור תשלום של <?=$payment['sum']?> ש"ח ב<?=UserUtilsNew::method($payment['payType'], $payment['provider'])?></div>
        <div class="form">
            <div class="inputWrap full">
                <input type="text" inputmode="numeric" name="tz" id="tZehoot" value="<?=$order['customerTZ']?>" />
                <label for="tZehoot">תעודת זהות</label>
            </div>
            <div class="inputWrap full">
                <input type="text" name="full_name" id="full_name" value="<?=str_replace('"', '&quot;', $order['customerName'])?>" />
                <label for="full_name">שם מלא</label>
            </div>
            <div class="inputWrap full">
                <input type="text" name="desc" id="trans_desc" value="<?=$desc?>" />
                <label for="trans_desc">תיאור עסקה</label>
            </div>
            <div class="inputWrap full">
                <input type="email" name="email" id="email" value="<?=$order['customerEmail']?>" />
                <label for="email">אימייל</label>
            </div>
<?php
            switch($payment['payType']){
                case 'check':
?>
            <div class="inputWrap full">
                <input type="text" name="docNum" id="document_number" value="">
                <label for="document_number">מספר צ'ק</label>
            </div>
<?php
                case 'transfer':
?>
            <div class="inputWrap full">
                <input type="text" name="pan" id="account_number" value="">
                <label for="account_number">מספר חשבון</label>
            </div>
            <div class="inputWrap full">
                <input type="text" name="branch" id="branch_number" value="">
                <label for="branch_number">מספר סניף</label>
            </div>
            <div class="inputWrap full">
                <input type="text" name="bank" id="bank_code" value="">
                <label for="bank_code">מספר בנק</label>
            </div>
            <div class="inputWrap full">
                <input type="text" name="docDate" id="payment_date" class="datePick" value="<?=db2date(substr($payment['endTime'], 0, 10), '/')?>">
                <label for="payment_date">מועד פרעון</label>
            </div>
<?php
                    break;

                default:
                    break;
            }
?>
            <div class="submit" onclick="getInvoice()">הפקת חשבונית
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 26" width="34" height="26"><path class="shp0" d="M32 7.7L16.3 7.7 16.3 2 2 13 16.3 24 16.3 18.3 32 18.3 32 7.7Z"></path></svg>
            </div>
        </div>
    </div>
</div>
<?php
            $result['html'] = ob_get_clean();
        break;

        default:
            $orderID = intval($_POST['data']['orderID']);
            $reload  = intval($_POST['data']['reload']);

            $order = $units = [];

            if(!$orderID)
                throw new Exception('No booking ID');

            $que = "SELECT orders.* FROM `orders` WHERE `orderID` = " . $orderID;
            $order = udb::single_row($que);

            if (!$order)
                throw new Exception('Cannot find booking #' . $orderID);
            if(!$_CURRENT_USER->has($order['siteID']))
                throw new Exception("הזמנה זאת לא שייכת למשתמש");

            $siteID = $order['siteID'];

            // check if client can charge saved cards
            list($hasTerminal, $canCharge, $canInvoice, $protelID) = udb::single_row("SELECT IF(`masof_active` = 1 AND `masof_number` > '', 1, 0) AS `hasMasof`, IF(`masof_active` = 1 AND `masof_number` > '' AND `masof_no_cvv` = 1, 1, 0) AS `charge`, `masof_invoice`, `protelID` FROM `sites` WHERE `siteID` = " . $siteID, UDB_NUMERIC);

            $terminal = $hasTerminal ? Terminal::bySite($siteID) : new Terminal;

            $payments  = udb::single_list("SELECT * FROM `orderPayments` WHERE (`payType` <> 'ccard' OR `subType` = 'direct' OR `complete` = 1) AND `cancelled` = 0 AND `orderID` = " . $orderID . " ORDER BY `lineID`");

            //$alreadyPaid = $order['advance'] ?: 0;
            //$yet2pay     = round($order['price'] - $alreadyPaid);
            $alreadyPaid = 0;
            $yet2pay     = $order['price'];
            $adv2pay     = $order['advance'];

            foreach($payments as $pay){
                if ($pay['subType'] == 'card_test' || $pay['subType'] == 'freeze_sum' || !$pay['complete'])
                    continue;

                $alreadyPaid += $pay['sum'];
                $yet2pay -= $pay['sum'];

                if ($pay['subType'] == 'advance')
                    $adv2pay = max(0, $adv2pay - $pay['sum']);
            }

            $yet2pay = max(0, $yet2pay);

            if ($protelID)
                $protelID = udb::single_value("SELECT `id` FROM `protel_sites` WHERE `active` = 1 AND `id` = " . intval($protelID));

//        /**CREATE MAIL WHATSAPP SMS**/
//		$link = urlencode(WEBSITE . "signature.php?guid=".$order['guid']);
//		//if($order['approved'] || $order['status']!=1){
//        if(!$order['approved'] && $order['status']==1){
//
//			$subject = "טופס לאישור הזמנה ב". $order['siteName'] ." בתאריך".date('d.m.y', strtotime($order['timeFrom']));
//			$body = $order['customerName'].' שלום, על מנת לאשר את הזמנתך ב'.$order['siteName']. ', בימים ' .$weekday[date('w', strtotime($order['timeFrom']))]."-".$weekday[date('w', strtotime($order['timeUntil']))].":".date('d.m.y', strtotime($order['timeFrom']))." - ". date('d.m.y', strtotime($order['timeUntil'])).' יש ללחוץ על הקישור הבא '.$link;
//
//		}else{
//			$subject = "יצירת קשר בנוגע להזמנה ב". $order['siteName'] ." בתאריך".date('d.m.y', strtotime($order['timeFrom']));
//			$body = $order['customerName'].' שלום, '.(($order['approved'] && $order['status']==1)? "מצורף קישור לטופס ההזמנה שלך ".$link : "");
//		}
//
//		if($order["customerPhone"]){
//			$order["whatsapp"] = "///wa.me/972".$order['customerPhone']."?text=".$body;
//			$order["sms"] = "sms:".$order['customerPhone']."?&body=".$body;
//		}
//		$order["mailto"] = "mailto:".$order['customerEmail']."?subject=".$subject."&body=".$body;
//		/*****/
//
//		$startDate = implode('/',array_reverse(explode('-',substr($order['showTimeFrom'],0,10))));
//		$endDate = implode('/',array_reverse(explode('-',substr($order['showTimeUntil'],0,10))));
//		$startTime = substr($order['showTimeFrom'],11,5);
//		$endTime = substr($order['showTimeUntil'],11,5);
//
//		$orderType = $asOrder ? 'order' : $order['orderType'];
//
//        $que = "SELECT `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`,`rooms`.`cleanTime`, `rooms`.maxAdults, `rooms`.maxKids, `rooms`.maxGuests
//                FROM `rooms_units` INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
//                    LEFT JOIN `orderUnits` ON (orderUnits.unitID = `rooms_units`.`unitID` AND orderUnits.orderID = " . $orderID . ")
//                WHERE `rooms`.`siteID`=" . $siteID . " AND (rooms.active = 1 OR orderUnits.unitID IS NOT NULL)";
//        $rooms = udb::key_row($que,'unitID');

//
//	else {
//        $que = "SELECT `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`,`rooms`.`cleanTime`, `rooms`.maxAdults, `rooms`.maxKids, `rooms`.maxGuests
//                FROM `rooms_units`
//                INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
//                WHERE rooms.active = 1 AND `rooms`.`siteID`=".$siteID;
//        $rooms = udb::key_row($que,'unitID');
//    }
	//print_r($order); exit;

            ob_start();
?>
<div class="pay_order order" id="pay_orderPop" data-order-id="<?=$orderID?>">
    <div class="sendPop" style="display:none">
        <div class="container">
            <div class="close" onclick="$('.sendPop').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
            <div class="title mainTitle" id="SendPopTitle">שליחה לחתימה</div>

            <div class="content">
                <div class="lines">
                    <div class="line">
                        <input type="text" id="sendPop_phone" placeholder="מספר טלפון">
                        <div class="signOpt">
                            <a href="" target="_blank" id="sendPop_icoWA"></a><span class="icon whatsapp" data-phone=""><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAYAAAA7MK6iAAAAAXNSR0IB2cksfwAABflJREFUeJyVV1tQk1cQ/tvOtB1n2ulbx7f2odOnTh/74JOdcaZ96XQ6tRWRiwGttyqOTK1a1FrFqtXqTG3pxV69VFsFL4iAIMpNQcFKQAhggBACJIAJt5AA2+874fwkGJGczE7+nPOf/c7ufrtnY4iIoWVoaMjo7Ow0nE6nKV1dXYbD4Xga8wtcLldmd3d3ocfjae7v7++F4NFj7+npKcPad3j3Pay/ADH3Y6/67ujoMOx2uynGbMB4ngdl8VB80+fzSSAQkMnJSYk2gsGgYL/09fW14BCbsfflmIGnZAEAa0dHR6MCTUxOKIk2eEB4Aio6kwkYC/Dqhw8f+sOt8/l9UuEol1N1J+VA+T7ZcS1Dthdvk8zS3fJb7TG5Zi8W16Ar4gD0AHQdgTw3K/Dw8DBPtp0bTBdOBOVC03nZkPepLDu7ROL//VgSzi6VpHPLlCSeWyrxU/MrL6SoQwyMDpj7x8bGBK7PaW9vfyYCmFZSBgcHDbjHgm9zk33ggXx1facshdLk7ARJPb98VrHkJKl3N15ZL3e6bkeAw+pDzc3NhhbD6/Uqcbvdb4Clw/rl5j6brLm0SlnzJMCZkpS9TEmxvSjC7bDcAgyDYvT29lKeBZEqJiZCZHF4HbI2dzXcGK8U0bUJZ+PmZLWW5TmJan+1s8oEB2Af3Dy/ra3NMHAKsm4R4qsWSaj95V+blvLkp+pOSKWjQnaV7FBxjcXyNHBDx5wph/TMYG1QFsPNFzWDr7eVgChLpiyNU6B63Hc3KGUp55PnDM6YH6v52dSBPHfCuy8xwV8dGBhQyRqYCMi2os+VVSTKOrjb6/eamybx2XNjlxmCuQj1fHIxVTzDHqVjZGSERFvM9En1+/1qsqG33lTK7y/h2pmjqvOWJCCFYiEbw3YDnuQYHx8XePl3Foss/uAoaLmi8lHHZ8vVzTKOPA4f+S15MQMz/3+6nWXqAPAtkqtAx/ev//4w48s4Ls9OlCZPo7nhrqtWMTslZ+4x1t4jMRmqKXbbDF4AWvEPVUdxujhzA8n1beVB82IguRgzS3ZSTMA87JbCzyQwHlB6wKkOsrpCA2dVfx8BrN1U1l5qWs2aTK/wAI8UjcekmgJG2EjecODL2qKT946brg7ftCFvHS6ALhP8jPVv5b4QkEU9ZxRtlQxcGnyeyXr+3o1sCEupRhaPI5pcRQ+uPgKsXA4y8cRDY9N1/LazWjZdSZPFZz5QKTiINRLxpqNS3VrhHqFOXh5h5Cpn8YhjbnG09DXDwsSoBYIh2Fu6R9zDblMBwfKRCU6fM4L5vNEOVhxQN5fee6szRCVWLxD6KC2eD5b5OMmLfVfJzsemC+O9KT9NbJ4medI4DFKSnKzZa3NXmYWIlwWK1juqM8AJTug404WM3ePKItdWIK5Mvdb+1qigNa4aVa2YdiyZp62nwuNrA7nmGbigKW/hHg7oRabQbEWC8WM14jdL6GXbJanvtYq1p06O3/tTzdNS6tgKbowGQ6FkSwQj1yCFDdWMAXg+TqGYwyTfi3ZmLvWYFvE9HiLUkcSreHKe6bXigkVsuNfDSNWKm+lFdqGqfUXZXKibuu4hF1qY1FChgKhKFcNtpMlEV9/trp0mIjobdB7v1tfXGw0NDQYrFyVTp9TV1kL58PT7KgXYQ20uSFelk5bNdgCuJcNKxnRnyXbEv8UEZdbg8k9vamoytDCdnkKvVa3d/GvNL7KvbK8UtOZLh7dD/EG/agJ2FH8Rii0ORNfSKrKWTNc9WXr+Rrlku2hWKA42GKwVBKurqzNoMcWAv19HzzWmgUcCI1GZylRj88ciw2JwuPKQHKr4Rt06uQBrdN8HiaZ7cGYJWuQg9K9nX22z2Qyr1RoBvDJa007X00U8se7F5jIISH0g0h0weCE8qnj0CDBeyOPLVM4NOOUo5qpxoP0g3SI8vw35h6znOg80828M97KZgOfGwZcSAH2Evc9PtVXRgQFQzqLNIoIXLPj9GlNsqgk0sKY2Q9ErWE/B/I9QXsTLHPNVeL6BueOQNKy/yX8i+k/bbMD/A9JqvbnfYMIJAAAAAElFTkSuQmCC"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span>
                            <a href="" target="_blank" id="sendPop_icoSMS"></a><span class="icon sms a_sms" data-sms="">
								<img src="/user/assets/img/icon_sms.png" alt="sms">
							</span>
                        </div>
                    </div>
                    <div class="line">
                        <div class="signOpt">
                            <a href="" target="_blank" id="sendPop_icoMail"></a>
                            <span  class="icon mail" >
                            <svg id="Capa_1" enable-background="new 0 0 515.283 515.283" height="512" viewBox="0 0 515.283 515.283" width="512" xmlns="http://www.w3.org/2000/svg" style=" fill: #FFF; max-height: 22px; width: auto; "><g><g><g><g><path d="m400.775 515.283h-286.268c-30.584 0-59.339-11.911-80.968-33.54-21.628-21.626-33.539-50.382-33.539-80.968v-28.628c0-15.811 12.816-28.628 28.627-28.628s28.627 12.817 28.627 28.628v28.628c0 15.293 5.956 29.67 16.768 40.483 10.815 10.814 25.192 16.771 40.485 16.771h286.268c15.292 0 29.669-5.957 40.483-16.771 10.814-10.815 16.771-25.192 16.771-40.483v-28.628c0-15.811 12.816-28.628 28.626-28.628s28.628 12.817 28.628 28.628v28.628c0 30.584-11.911 59.338-33.54 80.968-21.629 21.629-50.384 33.54-80.968 33.54zm-143.134-114.509c-3.96 0-7.73-.804-11.16-2.257-3.2-1.352-6.207-3.316-8.838-5.885-.001-.001-.001-.002-.002-.002-.019-.018-.038-.037-.057-.056-.005-.004-.011-.011-.016-.016-.016-.014-.03-.029-.045-.044-.01-.01-.019-.018-.029-.029-.01-.01-.023-.023-.032-.031-.02-.02-.042-.042-.062-.062l-114.508-114.509c-11.179-11.179-11.179-29.305 0-40.485 11.179-11.179 29.306-11.18 40.485 0l65.638 65.638v-274.409c-.001-15.811 12.815-28.627 28.626-28.627s28.628 12.816 28.628 28.627v274.408l65.637-65.637c11.178-11.179 29.307-11.179 40.485 0 11.179 11.179 11.179 29.306 0 40.485l-114.508 114.507c-.02.02-.042.042-.062.062-.011.01-.023.023-.032.031-.01.011-.019.019-.029.029-.014.016-.03.03-.044.044-.005.005-.012.012-.017.016-.018.019-.037.038-.056.056-.001 0-.001.001-.002.002-.315.307-.634.605-.96.895-2.397 2.138-5.067 3.805-7.89 4.995-.01.004-.018.008-.028.012-.011.004-.02.01-.031.013-3.412 1.437-7.158 2.229-11.091 2.229z"></path></g></g></g></g></svg> לחצו להורדת המסמך</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="ccpop" id="ccpop" style="display:none">
            <div class="container">
                <div class="close" onclick="closeCCDiv('#ccpop')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
                <iframe src="about:blank"></iframe>
            </div>
        </div>
        <div class="close" onclick="closePayOrder(<?=($reload ? $orderID : '')?>)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title mainTitle">ביצוע תשלום</div>
        <form class="form" id="payForm" action="" method="post" autocomplete="off" >
            <input type="hidden" name="orderID" value="<?=$order['orderID']?>" id="orderForm-orderID" />
            <!-- div></div -->
            <div class="inputWrap three paypp">
                <input type="text" value="<?=$order['price']?>" name="total_pay" id="total_pay" class="" readonly />
                <label for="total_pay">סה"כ לתשלום</label>
            </div>
            <div class="inputWrap date three time paypp">
                <input type="text" value="<?=$yet2pay?>" name="left_to_pay" id="left_to_pay" class="" readonly />
                <label for="left_to_pay">נותר לתשלום</label>
            </div>
            <div class="inputWrap date three paypp">
                <input type="text" value="<?=$alreadyPaid?>" name="paid" id="paid" class="" readonly />
                <label for="paid">שולם</label>
            </div>

            <div class="payMethod">
                <input type="radio" name="paymethod" value="1" id="addpay" checked />
                <label for="addpay">הוסף תשלום</label>
                <input type="radio" name="paymethod" class="<?=$terminal->has_freeze? "j5": ""?>" value="2" id="safecard" />
                <label for="safecard"><?=($canCharge ? ($terminal->has_freeze ? 'תפיסת מסגרת' : 'כרטיס לערבון') : 'בדיקת כרטיס')?></label>

                <div class="inputLblWrap full">
                    <label class="switch" for="approved">
                        <input type="checkbox" name="approved" id="approved" value="1" data-on="<?=$adv2pay?>" data-off="<?=$yet2pay?>" onclick="$('#payamount').val(this.checked ? this.dataset.on : this.dataset.off)" />
                        <span class="slider round"><span>מקדמה</span></span>
                    </label>
                </div>               
                <div class="inputWrap ccorcash half select orderOnly">
                    <select name="ccorcash" id="ccorcash">
<?php
            $stList = udb::key_value("SELECT `paytypekey`, `invoice` FROM `sitePayTypes` WHERE `siteID` = " . $siteID);
            if (!isset($stList['cash']))
                $stList['cash'] = 2;

            $ptList = UserUtilsNew::$payTypesFull;
            unset($ptList['refund']);
            if ($hasTerminal)
                unset($ptList['pseudocc']);         // removing pseudo cc
            else
                unset($ptList['ccard']);            // removing real cc

            foreach($ptList as $key => $name)
                echo '<option value="' , $key , '" data-ai="' , ($canInvoice ? $stList[$key] : '0') , '">' , $name , '</option>';
?>
                    </select>
                    <label for="ccorcash">אמצעי תשלום</label>
                </div>
                <div class="inputWrap half cpn-data" style="display:none">
                    <select name="prv" id="provd" onchange="loadCupons()">
                        <option value=""></option>
<?php
            foreach(UserUtilsNew::$CouponsfullList as $key => $name)
                echo '<option value="' , $key , '" data-ai="' , ($canInvoice ? $stList[$key] : '0') , '">' , $name , '</option>';
?>
                    </select>
                    <label for="provd">ספק</label>
                </div>
                <div class="inputWrap half " style="display:none">
                    <select name="cupons" id="cupons" onchange="setCupons()">
                        <option value="">שובר פתוח</option>
                    </select>
                    <label for="cupons">קופון</label>
                </div>
				<div class="inputWrap half cpn-data" style="display:none">
					<input type="text" name="coupon" id="coupon"  />
					<label for="coupon">מספר קופון / שובר</label>
				</div>
                <div class="inputWrap half manuy-number" style="display:none">
                    <input type="text" name="manuy-number" id="manuy-number" style="direction:ltr;text-align:right" />
                    <label for="manuy-number">מספר מנוי</label>
                </div>
<?php
            $hts = (new SiteItemList($siteID, 'hotel_guest_supplier'))->get_name_list();
            if ($protelID){
?>
                <input type="hidden" name="innerID" id="innerID" value="" />
                <div class="inputWrap half room-number" style="display:none; z-index:100;">
                    <input type="text" name="guestAppt" id="guestAppt" class="pay-ac" />
                    <label for="guestAppt">מספר חדר</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
                </div>
                <div class="inputWrap half room-number" style="display:none; z-index:100;">
                    <input type="text" name="booker" id="booker" class="pay-ac" />
                    <label for="booker">שם מזמין ב-Protel</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
                </div>
<?php
                if ($hts){
?>
                <div class="inputWrap half hts-select" style="display:none">
                    <select name="hts" id="guestSupp" onchange="setCupons()">
                        <option value="">- - - בחר ספק - - -</option>
<?php
                    foreach($hts as $id => $name)
                        echo '<option value="' , $id , '">' , $name , '</option>';
?>
                    </select>
                    <label for="guestSupp">ספק</label>
                </div>
<?php
                }
            } else {
?>
                <div class="inputWrap half room-number" style="display:none">
                    <input type="text" name="guestAppt" id="guestAppt"  />
                    <label for="guestAppt">מספר חדר</label>
                </div>
                <div class="inputWrap half room-number" style="display:none">
                    <input type="text" name="bon-number" id="guestBon"  />
                    <label for="guestBon">מספר בון</label>
                </div>
<?php
                if ($hts){
?>
                <div class="inputWrap half hts-select" style="display:none">
                    <select name="hts" id="guestSupp" onchange="setCupons()">
                        <option value="">- - - בחר ספק - - -</option>
<?php
                    foreach($hts as $id => $name)
                        echo '<option value="' , $id , '">' , $name , '</option>';
?>
                    </select>
                    <label for="guestSupp">ספק</label>
                </div>
<?php
                }
            }
?>
				 <div class="inputWrap half payamountc">
                    <input type="number" name="payamount" id="payamount" value="<?=$yet2pay?>" />
                    <label for="payamount">סכום תשלום</label>
                </div>
                <div class="cc-left-arrow" onclick="initPay($(this).closest('.pay_order'))"><span><?=($canCharge ? ($terminal->has_freeze ? 'תפיסת מסגרת' : 'כרטיס לערבון') : 'בדיקת כרטיס')?></span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 26" width="34" height="26"><path class="shp0" d="M32 7.7L16.3 7.7 16.3 2 2 13 16.3 24 16.3 18.3 32 18.3 32 7.7Z"></path></svg></div>
<?php
            if ($canInvoice) {
?>
                <div class="inputWrap half invoice-name">
                    <input type="text" name="invoiceName" id="invoiceName" value="<?=str_replace('"', '\"', $order['customerName'])?>" />
                    <label for="invoiceName">שם לחשבונית</label>
                </div>
<?php
            }
?>
            </div>
			
            <div class="payments">
                <div class="title">תשלומים</div>
<?php
            if (count($payments)){
                list($today, $now) = explode(' ', date('Y-m-d H:i:s'));

                foreach($payments as $pay){
                    $payType = UserUtilsNew::method($pay['payType'], $pay['provider']);
                    $picon   = ($pay['invoice'] && ($pay['payType'] == 'ccard' || $pay['invoice'] != '-')) ? 'refund' : 'cancel';
                    $showSum = true;

                    if ($pay['payType'] == 'ccard'){
                        if ($pay['subType'] == 'card_test'){
                            $payType = 'בדיקת כרטיס';
                            $showSum = false;
                        }
                        elseif ($pay['subType'] == 'freeze_sum')
                            $payType = 'תפיסת מסגרת בכרטיס';

                        $data = json_decode($pay['resultData'], true);
                        $payType .= '<div style="direction:ltr">****' . $data['last4'] . '</div>';

                        $p_date = substr($pay['endTime'], 0, 10);
                        $p_time = substr($pay['endTime'], 11, 8);

                        $picon = ($pay['subType'] != 'freeze_sum' && ($pay['invoice'] || strcmp($p_date, $today) < 0 || strcmp($now, '23:20:00') >= 0)) ? 'refund' : 'cancel';
                    }
                    elseif ($pay['payType'] == 'coupon'){
                        $data = json_decode($pay['inputData'], true);
                        $payType .= '<div style="direction:ltr">' . ($data['cpn'] ?: '') .  ($data['cpnname'] ? ',' . $data['cpnname'] : '') . '</div>';
                    }
                    elseif ($pay['payType'] == 'member2'){
                        $data = json_decode($pay['inputData'], true);
                        $payType .= '<div style="direction:ltr">' . ($data['mbr'] ? prettySubsNumber($data['mbr']) : '') . '</div>';
                    }
                    elseif ($pay['payType'] == 'guest' || $pay['payType'] == 'guestHts'){
                        $data = json_decode($pay['inputData'], true);
                        $row  = [];

                        if ($data['apt'])
                            $row[] = 'חדר: ' . $data['apt'];
                        if ($data['bon'])
                            $row[] = 'בון: ' . $data['bon'];
                        if ($data['hts']){
                            try {
                                $tmp = (new SiteItemList($siteID, 'hotel_guest_supplier'))->get_items($data['hts']);
                                $row[] = $tmp['itemName'];
                            } catch (Exception $e) {}
                        }

                        $payType .= '<div>' . implode(', ', $row) . '</div>';
                    }

                    $payClass = "";
                    if (!$pay['complete']){
                        $data = json_decode($pay['resultData'] ?: "[]", true);
                        $payType .= '<div style="direction:rtl; color:red; font-weight:bold">' . $data['error'] . '</div>';

                        $payClass = 'inactive';
                    }
                    elseif ($pay['cancelData'])
                        $payClass = 'refunded';
                    elseif ($pay['subType'] == 'freeze_sum')
                        $payClass = 'pay-frozen';
?>
                <div class="item payment <?=$payClass ?>" data-pay-id="<?=$pay['lineID']?>" data-tps="<?echo $pay['payType'], $pay['provider'];?>">
                    <div class="prepay"><?=($pay['subType'] == 'advance' ? 'מקדמה' : '')?> <?=db2date(substr($pay['endTime'], 0, 10), '.', 2)?></div>
                    <div class="amount"><?=($showSum ? '₪' . round($pay['sum'], 1) : '')?></div>
                    <div class="paytype"><div class="inner"><?=$payType?></div></div>
                    <div class="remove">
<?php
                    if (!$pay['complete']){
                        // do nothing
                    }
                    elseif($pay['cancelData']) {
?>
                        <div class="refunded">בוצע זיכוי</div>
<?php
                    }
                    elseif ($pay['payType'] == 'ccard'){
                        // suspended deal / frozen sum
                        if ($pay['subType'] == 'freeze_sum'){
?>
                        <div class="pay cancel" onclick="cancelPayment(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div>ביטול</div></div>
                        <div class="pay" onclick="openDirectPop(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div>חיוב</div></div>
<?php
                        }
                        // card for security
                        elseif ($pay['subType'] == 'card_test' && $pay['token'] && $canCharge){
?>
                        <div class="pay refund free-refund" onclick="openDirectRefund(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div>זיכוי ח.</div></div>
                        <div class="pay" onclick="openDirectPop(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div>חיוב</div></div>
<?php
                        }
                        // valid payment
                        elseif ($pay['subType'] != 'card_test' && !$pay['cancelData'] && $hasTerminal){
?>
                        <div class="pay <?=$picon?>" onclick="<?=$picon?>Payment(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div><?=($picon == 'cancel' ? 'ביטול' : 'זיכוי')?></div></div>
<?php
                        }
                    }
                    // coupon from vouchers.co.il
                    elseif ($pay['payType'] == 'coupon' && $pay['provider'] == 'vouchers'){
?>
                        <svg onclick="deletePayment(this, 'לחיצה על &quot;כן&quot; תחזיר את סכום המימוש לשובר. האם אתה בטוח שברצונך לבטל את המימוש?')" height="427pt" viewBox="-40 0 427 427.00131" width="427pt" xmlns="http://www.w3.org/2000/svg"><path d="m232.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m114.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m28.398438 127.121094v246.378906c0 14.5625 5.339843 28.238281 14.667968 38.050781 9.285156 9.839844 22.207032 15.425781 35.730469 15.449219h189.203125c13.527344-.023438 26.449219-5.609375 35.730469-15.449219 9.328125-9.8125 14.667969-23.488281 14.667969-38.050781v-246.378906c18.542968-4.921875 30.558593-22.835938 28.078124-41.863282-2.484374-19.023437-18.691406-33.253906-37.878906-33.257812h-51.199218v-12.5c.058593-10.511719-4.097657-20.605469-11.539063-28.03125-7.441406-7.421875-17.550781-11.5546875-28.0625-11.46875h-88.796875c-10.511719-.0859375-20.621094 4.046875-28.0625 11.46875-7.441406 7.425781-11.597656 17.519531-11.539062 28.03125v12.5h-51.199219c-19.1875.003906-35.394531 14.234375-37.878907 33.257812-2.480468 19.027344 9.535157 36.941407 28.078126 41.863282zm239.601562 279.878906h-189.203125c-17.097656 0-30.398437-14.6875-30.398437-33.5v-245.5h250v245.5c0 18.8125-13.300782 33.5-30.398438 33.5zm-158.601562-367.5c-.066407-5.207031 1.980468-10.21875 5.675781-13.894531 3.691406-3.675781 8.714843-5.695313 13.925781-5.605469h88.796875c5.210937-.089844 10.234375 1.929688 13.925781 5.605469 3.695313 3.671875 5.742188 8.6875 5.675782 13.894531v12.5h-128zm-71.199219 32.5h270.398437c9.941406 0 18 8.058594 18 18s-8.058594 18-18 18h-270.398437c-9.941407 0-18-8.058594-18-18s8.058593-18 18-18zm0 0"></path><path d="m173.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path></svg>
<?php
                    }
                    // room charge at protel site
                    elseif ($protelID && $pay['payType'] == 'guest' && $pay['provider'] == 'guest' && $pay['paymentID'] > 1000000){
?>
                        <div class="pay refund" onclick="deletePayment(this, 'האם אתה בטוח רוצה לבטל את חיוב החדר?')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div>זיכוי</div></div>
<?php
                    }
                    elseif ($pay['payType'] == 'member2' && $pay['provider'] == 'BizOnline'){
?>
                        <div class="pay refund" onclick="refundPayment(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div>זיכוי</div></div>
<?php
                    }
                     // any other payment method except refunds and subscriptions
                    elseif ($pay['payType'] != 'refund' && $pay['payType'] != 'member2') {
                        if ($picon == 'cancel'){
?>
                        <svg onclick="deletePayment(this, 'האם למחוק את שורת התשלום?')" height="427pt" viewBox="-40 0 427 427.00131" width="427pt" xmlns="http://www.w3.org/2000/svg"><path d="m232.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m114.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m28.398438 127.121094v246.378906c0 14.5625 5.339843 28.238281 14.667968 38.050781 9.285156 9.839844 22.207032 15.425781 35.730469 15.449219h189.203125c13.527344-.023438 26.449219-5.609375 35.730469-15.449219 9.328125-9.8125 14.667969-23.488281 14.667969-38.050781v-246.378906c18.542968-4.921875 30.558593-22.835938 28.078124-41.863282-2.484374-19.023437-18.691406-33.253906-37.878906-33.257812h-51.199218v-12.5c.058593-10.511719-4.097657-20.605469-11.539063-28.03125-7.441406-7.421875-17.550781-11.5546875-28.0625-11.46875h-88.796875c-10.511719-.0859375-20.621094 4.046875-28.0625 11.46875-7.441406 7.425781-11.597656 17.519531-11.539062 28.03125v12.5h-51.199219c-19.1875.003906-35.394531 14.234375-37.878907 33.257812-2.480468 19.027344 9.535157 36.941407 28.078126 41.863282zm239.601562 279.878906h-189.203125c-17.097656 0-30.398437-14.6875-30.398437-33.5v-245.5h250v245.5c0 18.8125-13.300782 33.5-30.398438 33.5zm-158.601562-367.5c-.066407-5.207031 1.980468-10.21875 5.675781-13.894531 3.691406-3.675781 8.714843-5.695313 13.925781-5.605469h88.796875c5.210937-.089844 10.234375 1.929688 13.925781 5.605469 3.695313 3.671875 5.742188 8.6875 5.675782 13.894531v12.5h-128zm-71.199219 32.5h270.398437c9.941406 0 18 8.058594 18 18s-8.058594 18-18 18h-270.398437c-9.941407 0-18-8.058594-18-18s8.058593-18 18-18zm0 0"></path><path d="m173.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path></svg>
<?php
                        } else {
?>
                        <div class="pay <?=$picon?>" onclick="deletePayment(this, 'האם אתה בטוח רוצה לבטל וליצור חשבונית מס זיכוי?')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg><div><?=($picon == 'cancel' ? 'ביטול' : 'זיכוי')?></div></div>
<?php
                        }
                    }

                    if ($canInvoice && ($pay['invoice'] || !in_array($pay['payType'], ['ccard', 'refund', 'unknown'])) && $pay['invoice'] != '-' && ($pay['payType'] != 'coupon' || $stList[$pay['provider']] > 0)){
                        $click = $pay['invoice'] ? ($pay['invoice'] == '-' ? '' : "window.open('" . ($pay['invoice'] == '+' ? 'download_invoice.php?orid=' . $orderID . '&pid=' . $pay['lineID'] : YaadPay::$INVOICE_URL . "?" . $pay['invoice']) . "', 'pay" . $pay['lineID'] . "')") : 'openInvoicePop(this)';
?>
						<div class="pay invoice <?=$pay['invoice']? "done" : ""?>" onclick="<?=$click?>"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"/><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"/></svg><div>חשבונית</div></div>
<?php
                    }
?>
                    </div>
                    <div class="actionUser"><?=$_CURRENT_USER->user($pay['buserID'])?></div>
                </div>
<?php
                }
            }
            else
                echo '<div class="item" style="text-align:center; color:gray; background-color:#eee; padding-top:13px; font-size:25px; font-style:italic">אין תשלומים</div>';
?>
            </div>
        </form>
    </div>
</div>
<?php
            if ($protelID){
?>
<script>
(typeof 'autoComplete' == 'function' ? Promise.resolve() : $.getScript('/user/assets/js/autoComplete.min.js')).then(function(){
    var cache = {tm: 0, cache: []};
    //debugger;
    function protel_caller(str, src){
        return new Promise(function(res){
            var c = {text:str, res:res};

            cache.cache.push(c);
            if (cache.tm)
                window.clearTimeout(cache.tm);

            cache.tm = window.setTimeout(function(){
                var last = cache.cache.pop();

                for(var i = 0; i < cache.cache.length; ++i)
                    cache.cache[i].res([]);

                cache.tm = null;
                cache.cache = [];

                last.res($.get('ajax_protel.php', 'act=clientInfo&sid=<?=($siteID ?: $_CURRENT_USER->active_site())?>&val=' + last.text + '&src=' + src).then(res => res.clients));
            }, 500);
        });
    }

    $('.pay-ac').each(function(){
        var inp = this, min = (this.id == 'booker') ? 3 : 1, keys = (this.id == 'booker') ? ['_text'] : ['room'];

        const autoCompleteJS = new autoComplete({
            selector: '#' + inp.id,
            data: {
                src: function(str){
                    if (str.length < min)
                        return Promise.resolve([]);
                    return protel_caller(str, inp.id);
                },
                cache: false,
                keys: keys
            },
            resultsList: {
                maxResults: 20
            },
            resultItem: {
                element: function(item, data){
                    item.innerHTML = `<span style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">${data.value.name}</span><span style="float:left; align-items: center; font-size: 13px; font-weight:bold; color:#999;">  ${data.value.roomText}  </span>`;
                    item.setAttribute("data-auto", JSON.stringify(data.value));
                },
                highlight: {
                    render: true
                }
            },
            events: {
                list: {
                    click: function(e){ debugger;
                        var li = e.target.nodeName.toUpperCase() == 'LI' ? e.target : $(e.target).closest('li').get(0), data = JSON.parse(li.dataset.auto || '{}');

                        document.getElementById('booker').value      = data.name;
                        document.getElementById('innerID').value     = data.pid;
                        document.getElementById('guestAppt').value = data.room;

                        this.setAttribute('hidden', '');
                    }
                }
            }
        });
    });
});
</script>
<?php
            }

            $result['html'] = ob_get_clean();
    }
    $result['status'] = 0;
}
catch (Exception $e){
    $result['error'] = $e->getMessage();
}
