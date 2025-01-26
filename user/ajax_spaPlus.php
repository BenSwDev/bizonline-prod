<?php
	require_once "auth.php";
$_timer = new BizTimer;
	$result = new JsonResult();

	function addTfusa($orderID, $unitID, $from, $to)
	{
		$tz = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$diff = abs((strtotime($to) - strtotime($from)) / 3600*60);
		$timeUnits = (($diff / 15));
		for($i=0;$i<$timeUnits;$i++){
			udb::insert('tfusa',[
			    'orderID' => $orderID,
				'unitID' => $unitID,
				'date' => date("Y-m-d", strtotime($from)+900*$i), 
				'hour' => date("H:i:s", strtotime($from)+900*$i)
            ]);
		}
		date_default_timezone_set($tz);
	}

	function checkAvil($from, $till, $units, $orderID = 0){
	    list($fromDate, $fromTime) = explode(' ', date('Y-m-d H:i:s', strtotime($from." -5 minutes")));
        list($tillDate, $tillTime) = explode(' ', date('Y-m-d H:i:s', strtotime($till." -5 minutes")));
		$orders_arr = array();
		$orders_arr = udb::single_column("SELECT orderID FROM orders WHERE parentOrder = ".intval($orderID));
		if (!strcmp($fromDate, $tillDate))
			$que = "SELECT COUNT(*) FROM `tfusa` WHERE tfusa.unitID ".(is_array($units) ? " in (" . implode(',', $units) . ")" : " = " . $units)." 
					AND `date` = '" . $fromDate . "'
					AND `hour` BETWEEN '" . $fromTime . "' 
					AND '" . $tillTime . "'" . (count($orders_arr) ? " AND `orderID` NOT IN( " . implode(",",$orders_arr).")" : "");
		else
	        $que = "SELECT COUNT(*) FROM `tfusa` WHERE tfusa.unitID ".(is_array($units) ? " in (" . implode(',', $units) . ")" : " = " . $units)." 
		            AND ((`date` = '" . $fromDate . "' AND `hour` > '" . $fromTime . "') 
		              OR (`date` = '" . $tillDate . "' AND `hour` < '" . $tillTime . "') 
		              OR (`date` < '" . $tillDate . "' AND `date` > '" . $fromDate . "')) " .
                    (count($orders_arr) ? " AND `orderID` NOT IN( " . implode(",",$orders_arr).")" : "");

        return udb::single_value($que);
    }

	function deleteUnitsOrder($orderID)	{
		udb::query("DELETE FROM `orderUnits` WHERE `orderID`=".$orderID);
		udb::query("DELETE FROM `tfusa` WHERE `orderID`=".$orderID);
	}

	function updateOrder($orderID, $orderData, $dataSave){
		$timeFrom = $orderData['fromDate']." ".date("H:i:s", strtotime($orderData['startTime']));
		$timeUntil = $orderData['endDate']." ".date("H:i:s", strtotime($orderData['endTime']));
		// $dataSave['approved']=0;//cancel sign after update

        if ($orderData['as_order'] == 1)        // changing status from "prebook" to "booking"
            $dataSave['orderType'] = 'order';

		if (!checkAvil($timeFrom, $timeUntil, $orderData['unitID'], $orderID)){
			udb::update('orders',$dataSave ,"orderID=".$orderID);

            udb::query("DELETE FROM `orderUnits` WHERE `orderID`=".$orderID);
			udb::query("DELETE FROM `tfusa` WHERE `orderID`=".$orderID);

            $active = udb::single_value("SELECT `status` FROM `orders` WHERE `orderID` = " . intval($orderID));

            $unit_in = ['orderID' => $orderID];
            foreach($orderData['unitID'] as $uid){
                $unit_in['unitID'] = $uid;
                $unit_in['base_price'] = $orderData['payment'][$uid];
                //$unit_in['advance'] = $orderData['adv_payment'][$uid];
                $unit_in['breakfast'] = $orderData['meal'][$uid] ? 1 : 0;

                $people  = ['adults' => 0, 'kids' => 0, 'babies' => 0];
                foreach($people as $key => $value)
                    $unit_in[$key] = ($orderData[$key . '_room'][$uid] ?? 0);

                udb::insert("orderUnits", $unit_in);
                if ($active)
                    addTfusa($orderID, $uid, $timeFrom, $timeUntil);
            }
		} else {
			throw new Exception ("החדר תפוס");
		}
	}

$user_log = new UserActionLog($_CURRENT_USER);

try {
	switch($_POST['action']){
		case "hidePrices":
			$orderID = intval($_POST['orderID']);
			$hidePrices = ($_POST['hidePrices'] == "true")? 1 : 0;
			$que = "SELECT orders.siteID FROM `orders` WHERE orders.orderID = " . $orderID;
            $order = udb::single_row($que);
			if(!$_CURRENT_USER->has($order['siteID']))
                throw new Exception("אינך מורשה לבצע פעולה זו");
			
			$que = "UPDATE `orders` SET `hidePrices`= ".$hidePrices." WHERE `orderID`=".$orderID;
            udb::query($que);
			$user_log->save('hide_price'.$hidePrices, $order['siteID'], $orderID);
$_timer->log();
		break;
		case "info":
			$orderID = intval($_POST['orderID']);

			if (!$orderID)
			    throw new Exception("No order ID");

            $que = "SELECT sites.siteName, orders.*, orderUnits.unitID FROM `orders` INNER JOIN `orderUnits` USING(`orderID`) INNER JOIN `sites` USING(`siteID`) WHERE orders.orderID = " . $orderID;
            $order = udb::single_row($que);

			if(!$_CURRENT_USER->has($order['siteID']))
                throw new Exception("You are not authorized to see this booking");

			/**CREATE MAIL WHATSAPP SMS**/
			$link = urlencode(WEBSITE . "signature.php?guid=".$order['guid']);
			if($order['approved'] || $order['status']!=1){					
				
				$subject = "טופס לאישור הזמנה ב". $order['siteName'] ." בתאריך".date('d.m.y', strtotime($order['timeFrom']));
				$body = $order['customerName'].' שלום, על מנת לאשר את הזמנתך ב'.$order['siteName']. ', בימים ' .$weekday[date('w', strtotime($order['timeFrom']))]."-".$weekday[date('w', strtotime($order['timeUntil']))].":".date('d.m.y', strtotime($order['timeFrom']))." - ". date('d.m.y', strtotime($order['timeUntil'])).' יש ללחוץ על הקישור הבא '.$link;
				
			}else{
				$subject = "יצירת קשר בנוגע להזמנה ב". $order['siteName'] ." בתאריך".date('d.m.y', strtotime($order['timeFrom']));
				$body = $order['customerName'].' שלום, '.(($order['approved'] && $order['status']==1)? "מצורף קישור לטופס ההזמנה שלך ".$link : "");
			}

			if($order["customerPhone"]){
				$order["whatsapp"] = "///wa.me/972".$order['customerPhone']."?text=".$body;
				$order["sms"] = "sms:".$order['customerPhone']."?&body=".$body;
			}
			$order["mailto"] = "mailto:".$order['customerEmail']."?subject=".$subject."&body=".$body;
			/*****/
			
			$order['customerPhone'] = $order['customerPhone']." ";
			$order['customerPhone2'] = $order['customerPhone2']." ";
			
			$result["order"] = $order;
$_timer->log();
		break;

		case "cancel":
			$orderID = intval($_POST['orderID']);
			if($orderID){
				$que = "SELECT orders.*,orderUnits.unitID  FROM orders 
				INNER JOIN orderUnits USING(orderID)
				WHERE orderID=".$orderID;
				$order = udb::single_row($que);

                if(!$_CURRENT_USER->has($order['siteID']))
                    throw new Exception("You are not authorized to see this booking");

                $que = "DELETE FROM `tfusa` WHERE `orderID`=".$orderID;
                udb::query($que);

                $que = "UPDATE `orders` SET `status`= 0 WHERE `orderID`=".$orderID;
                udb::query($que);

                $user_log->save('order_cancel', $order['siteID'], $orderID);

                $result['text'] = "ההזמנה בוטלה בהצלחה";
                $result['status'] = 1;
            }
$_timer->log();
		break;
		case "delete":

			$orderID = intval($_POST['orderID']);
			if($orderID){
				$que = "SELECT orders.*,orderUnits.unitID  FROM orders 
				INNER JOIN orderUnits USING(orderID)
				WHERE orderID=".$orderID;
				$order = udb::single_row($que);	
			}
            if(!$_CURRENT_USER->has($order['siteID']))
                throw new Exception("You are not authorized to see this booking");
			$que = "DELETE FROM `orders` WHERE `orderID`=".$orderID;
			udb::query($que);
			$que = "DELETE FROM `orderUnits` WHERE `orderID`=".$orderID;
			udb::query($que);
			$que = "DELETE FROM `tfusa` WHERE `orderID`=".$orderID;
			udb::query($que);

            $user_log->save('order_delete', $order['siteID'], $orderID, ['orderIDBySite' => $order['orderIDBySite']]);

			$result['text'] = "ההזמנה נמחקה בהצלחה";
			$result['status'] = 1;
$_timer->log();
		break;
	
		case "insertOrder":

            $healthMailAccept = intval($_POST['healthMailAccept']) == 1 ? 1 : 0;


            $data = typemap($_POST, [
                '!orderID'   => 'int',
                '!add_order' => 'int',
                '!add_date' => 'int',
                'orderSite' => 'int',
                'sourceID' => 'string',
                'approved'   => 'int',
                'adminApproved'   => 'int',
                'setapproved'   => 'int',
                'realStartTime'   => 'string',
                'realEndTime'    => 'string',
                'startTime'      => 'string',
                'endTime'     => 'string',
                'name'    => 'string',
                'phone'    => 'numeric',
                'phone2'    => 'numeric',
                'specialfields' => 'int',
                'reason'    => 'int',
                'email'  => 'string',
                'tZehoot' => 'numeric',
                'adults_room'   => ['int' => 'int'],
                'kids_room'      => ['int' => 'int'],
                'babies_room'    => ['int' => 'int'],
                'form_to_sign'   => 'int',
                'price_to_pay'   => 'int',
                'price_discount' => 'int',
                'extraPrice'     => 'int',
                'discountText'    => 'text',
                'comments_customer'    => 'text',
                'comments_owner'    => 'text',
                'comments_payment'  => 'text',
                'payment' => ['int' => 'int'],
                //'adv_payment' => ['int' => 'int'],
                'meal' => ['int' => 'int'],
                'unitID'  => ['int'],
                'otype' => 'string',
                'as_order' => 'int',
                'clientAddress' => 'string',
                'settlementID' => 'int',
                'extra'  => ['int'],
                'ecount' => ['int' => 'int'],
                'sendOrderMail' => 'int'
            ]);
$_timer->log();
			$data['fromDate'] = typemap(implode('-',array_reverse(explode('/',trim($_POST['fromDate'])))),"date");
			$data['endDate'] = typemap(implode('-',array_reverse(explode('/',trim($_POST['endDate'])))),"date");

			try{
                if($data['add_order'] && (!$data['unitID'] || !$data['startTime'] || !$data['endTime'] || !$data['fromDate'] || !$data['endDate'])){
					throw new Exception ("שגיאת נתוני שהות");
				}
				if($data['add_order'] && (strcmp($data['fromDate'], $data['endDate']) > 0)){
					throw new Exception ("תאריך התחלה גדול מתאריך סיום");
				}

				if (!$data['otype'] || !UserUtilsNew::$orderTypes[$data['otype']])
                    $data['otype'] = 'order';

                
				if($data['add_date']){
					$data['endDate'] = $data['fromDate'];
					$data['endTime'] = $data['startTime'];
				}

				if ($data['add_order'] || $data['add_date']){
                    $timeFrom = $data['fromDate']." ".date("H:i:s", strtotime($data['startTime']));
                    $timeUntil = $data['endDate']." ".date("H:i:s", strtotime($data['endTime']));					
				}
				if ($data['add_order']){
                    if (strcmp($timeFrom, $timeUntil) >= 0)
                        throw new Exception ("תאריך ושעת התחלה חייב להיות קטן מתאריך ושעת סיום");

                    $people  = ['adults' => 0, 'kids' => 0, 'babies' => 0];
                    $payment = ['payment' => 0, 'adv_payment' => 0];

                    // calculating total people

                    foreach($data['unitID'] AS $uid){
                        foreach($payment as $key => &$value)
                            $value += ($data[$key][$uid] ?? 0);
                        unset($value);

                        foreach($people as $key => &$value)
                            $value += ($data[$key . '_room'][$uid] ?? 0);
                        unset($value);
                    }

                    $sites = udb::single_column("SELECT DISTINCT `siteID` FROM `rooms` INNER JOIN `rooms_units` USING(`roomID`) WHERE rooms_units.unitID IN (" . implode(',', $data['unitID']) . ")");
                    if (count($sites) > 1)
                        throw new Exception ("Cannot select units from different sites");

                    $siteID = reset($sites);
                }
                elseif ($data['orderID']){
                    $siteID = udb::single_value("SELECT `siteID` FROM `orders` WHERE `orderID` = " . $data['orderID']);
                }
                elseif (!($siteID = $data['orderSite']))
                    throw new Exception ("לא נבחר מתחם להזמנה");                    

                if(!$_CURRENT_USER->has($siteID))
                    throw new Exception("You are not authorized to see this booking");

                    
                $address = udb::single_value("SELECT `addressRequired` FROM `sites` WHERE `siteID` = " . $siteID);
                
                if($address==2 && !$data['clientAddress'] && !$data['settlementID'])
                    throw new Exception ("חובה להזין כתובת בהזמנה");
$_timer->log();

                $saveExtraPrice = true;
                $baseDate       = '0000-00-00';

                // order with rooms
                if ($data['add_order'] || $data['add_date']){
                    $dataSave = [
                        'timeFrom' => $timeFrom,
                        'timeUntil' => $timeUntil,
                        'showTimeFrom' => $data['fromDate']." ".date("H:i:s", strtotime($data['startTime'])),
                        'showTimeUntil' => $data['endDate']." ".date("H:i:s", strtotime($data['endTime'])),
                        'customerName' => $data['name'],
                        'customerPhone' => $data['phone'],
                        'customerPhone2' => $data['phone2'],
                        'customerEmail' => $data['email'],
                        'customerTZ' => $data['tZehoot'],
                        'price' => $data['price_to_pay'] ?: $payment['payment'],
                        'discount' => $data['price_discount'] ?: 0,
                        'extraPrice' => $data['extraPrice'] ?: 0,
                        'discountText' => $data['discountText'],
                        'comments_customer' => $data['comments_customer'],
                        'comments_owner' => $data['comments_owner'],
                        'comments_payment' => $data['comments_payment'],
                        'reason' => $data['reason'],
                        'adults' => $people['adults'],
                        'kids' => $people['kids'],
                        'babies' => $people['babies'],
                        'form_to_sign' => $data['form_to_sign'],
                        'approved' => $data['approved'] ?? 0,
                        'adminApproved' => $data['adminApproved'] ?? 0,
                        'customerAddress' => $data['clientAddress'],
                        'settlementID' => $data['settlementID']
                    ];

                    // new order with rooms
                    if(!$data['orderID']){
                        $dataSave['siteID'] = $siteID;
                        $dataSave['mail_sent'] = -1;
                        $dataSave['sourceID'] = $data['sourceID'];

						if($dataSave['sourceID']=="novalue")
							throw new Exception ("יש לבחור מקור הגעה");
                        if($data['add_date'] || !checkAvil($timeFrom, $timeUntil, $data['unitID'])){
                            $dataSave['guid'] = GUID();
                            $dataSave['orderType'] = $data['otype'];
                            $dataSave['SentReview'] = 0;

                            udb::query("LOCK TABLE `orders` WRITE");

                            $que = "SELECT MAX(`orderIDBySite`) FROM `orders` WHERE `siteID` = " . $siteID;
                            $maxOrderID = udb::single_value($que);
                            $dataSave['orderIDBySite'] = $maxOrderID+1;
                            $orderID = udb::insert('orders', $dataSave);

                            udb::query("UNLOCK TABLES");

                            udb::update('orders', ['parentOrder' => $orderID], '`orderID` = ' . $orderID);

                            $unit_in = ['orderID' => $orderID];
							if($data['add_order']){
								foreach($data['unitID'] as $uid){
									$unit_in['unitID'] = $uid;
									$unit_in['base_price'] = $data['payment'][$uid];
									//$unit_in['advance'] = $data['adv_payment'][$uid];
									$unit_in['breakfast'] = $data['meal'][$uid] ? 1 : 0;

									foreach($people as $key => $value)
										$unit_in[$key] = ($data[$key . '_room'][$uid] ?? 0);

									udb::insert("orderUnits", $unit_in);
									addTfusa($orderID, $uid, $timeFrom, $timeUntil);
								}
							}

                            $user_log->save($data['otype'], $siteID, $orderID);

                            $result['success'] = "בוצע בהצלחה";
                            $result['orderID'] = $orderID;
                        } else {

                            throw new Exception ("החדר תפוס");
                        }

                        $saveExtraPrice = false;        // if new order - get updated prices (should happen anyway)

                    }
                    else {
                        list($prevFrom, $prevTill) = udb::single_row("SELECT `timeFrom`, `timeUntil` FROM `orders` WHERE `orderID` = " . $data['orderID'], UDB_NUMERIC);
                        if (strcmp($data['timeFrom'], $prevFrom) || strcmp($data['timeUntil'], $prevTill))
                            $saveExtraPrice = false;        // if existing order with changed dates - get updated prices

                        $dataSave['healthMailAccept'] = $healthMailAccept;
                        
                         list($prevID, $_isApproved, $oldSource, $apiSource) = udb::single_row("SELECT `siteID`, `approved`, `sourceID`, `apiSource` FROM `orders` WHERE `orderID` = " . $data['orderID'], UDB_NUMERIC);
                        //$_isApproved = udb::single_value("SELECT `approved` FROM `orders` WHERE `orderID` = " . $data['orderID']);
                        if ($prevID != $siteID)
                            throw new Exception("Cannot select units from different site");     // should not happen

                        if (strcmp($apiSource, 'spaplus'))
                            $dataSave['sourceID'] = $data['sourceID'];
                        else
                            $data['sourceID'] = $oldSource;

                        //update order

                        if(intval($data['specialfields']) == 0) {
                                $dataSave['approved'] = 0;
                        } else {
                            if(intval($_isApproved) == 1) {
                                $dataSave['approved'] = 1;
                            }
                        }


                        if(intval($data['setapproved'])) {
                            $dataSave['approved'] = 1;
                        }

                        if(intval($data['specialfields']) == 0) {
                            if(intval($data['setapproved'])) {
                            } else {
                                $dataSave['signature'] = '';

                            }
                        } else {
                        }
						//$result['datasave'] = $dataSave;
						if($data['add_order'] && is_array($data['unitID'])){
							updateOrder($data['orderID'],$data,$dataSave);
							//יש לבדוק מצב בדאטה בייס ולהוסיף לתנאי
						}else  {
							if($data['add_date']){								
								udb::update('orders',$dataSave ,"orderID=".$data['orderID']);
							}
							deleteUnitsOrder($data['orderID']);
						}
                        if($dataSave['approved']==1){


                            if(intval($data['specialfields']) == 0) {
                                if(intval($data['setapproved'])) {
                                    $result['success'] = "ההזמנה עודכנה בהצלחה";
                                } else {
                                    $result['success'] = "ההזמנה עודכנה בהצלחה, יש  לשלוח לחתימה חוזרת";
                                }
                            } else {
                                $result['success'] = "ההזמנה עודכנה בהצלחה";
                            }

                        }
                        elseif($dataSave['approved']==0){
                            if(intval($_isApproved) == 1) {
                                if(intval($data['setapproved'])) {
                                    $result['success'] = "ההזמנה עודכנה בהצלחה";
                                } else {
                                    $result['success'] = "ההזמנה עודכנה בהצלחה, יש  לשלוח לחתימה חוזרת";
                                }
                            } else {
                                $result['success'] = "ההזמנה עודכנה בהצלחה";
                            }
                        }

                        $user_log->save('order_update', $siteID, $data['orderID']);
                    }

                    $baseDate = substr($dataSave['timeFrom'], 0, 10);

                }       // end of if (data['add_order'])
                else {
					//$result['deletTimes']=1;
					deleteUnitsOrder($data['orderID']);
                    $dataSave = [
						'timeFrom' => '0000-00-00 00:00:00',
                        'timeUntil' => '0000-00-00 00:00:00',
						'showTimeFrom' => '0000-00-00 00:00:00',
                        'showTimeUntil' => '0000-00-00 00:00:00',
                        'customerName' => $data['name'],
                        'customerPhone' => $data['phone'],
                        'customerPhone2' => $data['phone2'],
                        'customerEmail' => $data['email'],
                        'customerTZ' => $data['tZehoot'],
                        'price' => $data['price_to_pay'] ?: $payment['payment'] ?: 0,
                        'discount' => $data['price_discount'] ?: 0,
                        'discountText' => $data['discountText'],
                        'comments_customer' => $data['comments_customer'],
                        'comments_owner' => $data['comments_owner'],
                        'comments_payment' => $data['comments_payment'],
                        'reason' => $data['reason'],
                        'form_to_sign' => $data['form_to_sign'],
                        'customerAddress' => $data['clientAddress'],
                        'settlementID' => $data['settlementID']
                    ];

                    if(!$data['orderID']) {
                        $dataSave['siteID'] = $siteID;
                        $dataSave['guid'] = GUID();
                        $dataSave['orderType'] = ($data['otype'] ?: 'order');
                        $dataSave['SentReview'] = 0;
                        $dataSave['mail_sent'] = -1;
                        $dataSave['hidePrices'] = udb::single_value("SELECT autoHidePrice FROM sites WHERE siteID=".$siteID);
                        $dataSave['sourceID'] = $data['sourceID'];
							
						if($dataSave['sourceID']=="novalue")
							throw new Exception ("יש לבחור מקור הגעה");


                        udb::query("LOCK TABLE `orders` WRITE");

                        $que = "SELECT MAX(`orderIDBySite`) FROM `orders` WHERE `siteID` = " . $siteID;
                        $maxOrderID = udb::single_value($que);
                        $dataSave['orderIDBySite'] = $maxOrderID+1;
                        $orderID = udb::insert('orders', $dataSave);

                        udb::update('orders', ['parentOrder' => $orderID], '`orderID` = ' . $orderID);
$_timer->log();
                        udb::query("UNLOCK TABLES");

                        $user_log->save($data['otype'], $siteID, $orderID);

                        $result['success'] = "בוצע בהצלחה";
                        $result['orderID'] = $orderID;
                    }
                    else {
                        $dataSave['healthMailAccept'] = $healthMailAccept;

                        list($prevID, $_isApproved, $oldSource) = udb::single_row("SELECT `siteID`, `approved`, `sourceID` FROM `orders` WHERE `orderID` = " . $data['orderID'], UDB_NUMERIC);
                        //$_isApproved = udb::single_value("SELECT `approved` FROM `orders` WHERE `orderID` = " . $data['orderID']);
                        if ($prevID != $siteID)
                            throw new Exception("Cannot select units from different site");     // should not happen

                        if (strcmp($oldSource, 'online'))
                            $dataSave['sourceID'] = $data['sourceID'];

                        //update order
                        if(intval($data['specialfields']) == 0) {
                            $dataSave['approved'] = 0;
                        } else {
                            if(intval($_isApproved) == 1) {
                                $dataSave['approved'] = 1;
                            }
                        }


                        if(intval($data['setapproved'])) {
                            $dataSave['approved'] = 1;
                        }

                        if(intval($data['specialfields']) == 0) {
                            if(intval($data['setapproved'])) {
                            } else {
                                $dataSave['signature'] = '';

                            }
                        }

                        udb::update('orders', $dataSave, "`orderID`=".$data['orderID']);
$_timer->log();
                        //יש לבדוק מצב בדאטה בייס ולהוסיף לתנאי
                        if($dataSave['approved']==1){


                            if(intval($data['specialfields']) == 0) {
                                if(intval($data['setapproved'])) {
                                    $result['success'] = "ההזמנה עודכנה בהצלחה";
                                } else {
                                    $result['success'] = "ההזמנה עודכנה בהצלחה, יש  לשלוח לחתימה חוזרת";
                                }
                            } else {
                                $result['success'] = "ההזמנה עודכנה בהצלחה";
                            }

                        }
                        elseif($dataSave['approved']==0){
                            if(intval($_isApproved) == 1) {
                                if(intval($data['setapproved'])) {
                                    $result['success'] = "ההזמנה עודכנה בהצלחה";
                                } else {
                                    $result['success'] = "ההזמנה עודכנה בהצלחה, יש  לשלוח לחתימה חוזרת";
                                }
                            } else {
                                $result['success'] = "ההזמנה עודכנה בהצלחה";
                            }
                        }

                        $user_log->save('order_update', $siteID, $data['orderID']);
                    }
                }
$_timer->log();
                $baseDate = udb::single_value("SELECT DATE(MIN(`timeFrom`)) FROM `orders` WHERE `status` = 1 AND `parentOrder` = " . $data['orderID'] . " AND `orderID` <> " . $data['orderID']) ?: ($baseDate ?? '0000-00-00');

                if ($data['extra']){
                    $que = "SELECT * FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
                                WHERE s.extraID IN (" . implode(',', $data['extra']) . ") AND s.siteID = " . $siteID . "  AND s.active = 1";
                    $extras = udb::key_row($que, 'extraID');

                    $isWeekend2 = false;

                    if ($extras){
                        $wday = date('w', strtotime($baseDate));

                        $que = "SELECT IFNULL(b.isWeekend2, a.isWeekend2) AS `isWeekend2`
                                FROM `sites_weekly_hours` AS `a` 
                                    LEFT JOIN `sites_periods` AS `sp` ON (sp.siteID = a.siteID AND sp.periodType = 0 AND sp.dateFrom <= '" . $baseDate . "' AND sp.dateTo >= '" . $baseDate . "')
                                    LEFT JOIN `sites_weekly_hours` AS `b` ON (b.siteID = a.siteID AND b.holidayID = -sp.periodID AND b.weekday = a.weekday AND b.active = 1)
                                WHERE a.holidayID = 0 AND a.siteID = " . $siteID . " AND a.weekday = " . $wday;
                        $isWeekend2 = udb::single_value($que);
                    }

                    if ($data['orderID']){
                        $existing = json_decode(udb::single_value("SELECT `extras` FROM `orders` WHERE `orderID` = " . $data['orderID']), true);
                        if ($existing && is_array($existing['extras']))
                            foreach($existing['extras'] as $ex){
                                if (!in_array($ex['extraID'], $data['extra']))
                                    continue;

                                if (empty($extras[$ex['extraID']])){
                                    if ($ex['forNight'])
                                        $extras[$ex['extraID']] = ['extraID' => $ex['extraID'], 'extraType' => 'rooms', 'price3' => $ex['price']];
                                    elseif ($ex['extraHours'])
                                        $extras[$ex['extraID']] = ['extraID' => $ex['extraID'], 'extraType' => 'rooms', 'price1' => $ex['basePrice'], 'price2' => $ex['hourPrice'], 'countMin' => $ex['baseHours'], 'countMax' => $ex['baseHours'] + $ex['extraHours']];
                                    elseif (array_key_exists('baseHours', $ex))
                                        $extras[$ex['extraID']] = ['extraID' => $ex['extraID'], 'extraType' => 'rooms', 'price1' => $ex['price'], 'countMin' => $ex['baseHours']];
                                    else
                                        $extras[$ex['extraID']] = ['extraID' => $ex['extraID'], 'price1' => $ex['price']];
                                }
                                /*elseif (array_key_exists('baseHours', $ex))
                                    $extras[$ex['extraID']] = array_merge($extras[$ex['extraID']], ['price1' => $ex['basePrice'] ?? $ex['price']]);*/
                            }
                    }
$_timer->log();
                    $tc = udb::single_value("SELECT COUNT(*) FROM `orders` WHERE `orderID` <> `parentOrder` AND `status` = 1 AND `parentOrder` = " . ($data['orderID'] ?: $result['orderID']));

                    $res = ['extras' => [], 'total' => 0];
                    foreach($extras as $extra){
                        if ($cnt = $data['ecount'][$extra['extraID']]) {
                            $exArr = [
                                'extraID' => $extra['extraID'],
                                'count'   => $cnt
                            ];

                            if ($extra['extraType'] == 'package'){
                                if (!$tc)
                                    continue;

                                $pin = min(3, max(1, $cnt));
                                $exArr['price'] = $price = ($extra['price' . $pin] ?: $extra['price' . ($pin - 1)] ?: $extra['price1']) + ($isWeekend2 ? $extra['priceWE'] : 0);
                            }
                            elseif ($extra['extraType'] == 'rooms'){
                                $exArr['count'] = 1;
                                if ($cnt == 99){     // overnight
                                    $exArr['forNight'] = 1;
                                    $exArr['price']    = $price = $extra['price3'] + ($isWeekend2 ? $extra['priceWE3'] : 0);
                                }
                                elseif ($cnt > 1 && $cnt > round($extra['countMin'], 1)){     // extra hours
                                    $exArr = array_merge($exArr, [
                                        'baseHours'  => round($extra['countMin'], 1),
                                        'extraHours' => $cnt - round($extra['countMin'], 1),
                                        'basePrice'  => $extra['price1'] + ($isWeekend2 ? $extra['priceWE'] : 0),
                                        'hourPrice'  => $extra['price2'] + ($isWeekend2 ? $extra['priceWE2'] : 0)
                                    ]);

                                    $exArr['price'] = $price = $exArr['basePrice'] + $exArr['hourPrice'] * ($cnt - round($extra['countMin'], 1));
                                }
                                else {     // base price
                                    $exArr['baseHours'] = round($extra['countMin'], 1);
                                    $exArr['price']     = $price = $extra['price1'] + ($isWeekend2 ? $extra['priceWE'] : 0);
                                }
                            }
                            else
                                $exArr['price'] = $price = $extra['price1'] + ($isWeekend2 ? $extra['priceWE'] : 0);

                            $res['extras'][$extra['extraID']] = $exArr;
                            $res['total'] += $exArr['count'] * $price;
                        }
                    }
$_timer->log();
                    udb::update('orders', ['extras' => json_encode($res, JSON_NUMERIC_CHECK)], '`orderID` = ' . ($data['orderID'] ?: $result['orderID']));
                }
                else
                    udb::updateNull('orders', ['extras' => null], '`orderID` = ' . ($data['orderID'] ?: $result['orderID']));

                // sending booking email
                if ($data['sendOrderMail'] == 1) {
                    // checking if mail already sent
                    list($sent, $oStatus, $oType) = udb::single_row("SELECT `mail_sent`, `status`, `orderType` FROM `orders` WHERE `orderID` = " . ($data['orderID'] ?: $result['orderID']), UDB_NUMERIC);
                    if ($sent < 1 && $oStatus == 1 && $oType == 'order'){
                        include_once __DIR__ . "/../sendgrid/sendgrid-php.php";

                        $oxsaved = udb::single_row("SELECT `sites`.`siteName`, sites.fromName, `orders`.*
                                FROM `orders` INNER JOIN `sites` ON (`orders`.`siteID` = `sites`.`siteID`)
                                WHERE orders.orderID = " . ($data['orderID'] ?: $result['orderID']));

                        $sent = 0;
$_timer->log();
                        if ($oxsaved['customerEmail']){
                            send_order_details($oxsaved, true);
                            $sent += 1;
                        }

                        if ($oxsaved['customerPhone']){
                            $smsName = udb::single_value("SELECT `smsName` FROM `sites` WHERE `siteID` = " . $siteID);

                            $text = 'שלום ' . $oxsaved['customerName'] . ', לצפייה בפרטי ההזמנה שלך' . PHP_EOL . WEBSITE . 'signature2.php?guid=' . $oxsaved['guid'];
                            Maskyoo::sms($text, $oxsaved['customerPhone'], $smsName ?: 'BizOnline');
                            $sent += 2;
                        }

                        udb::update('orders', ['mail_sent' => $sent], "`orderID` = " . ($data['orderID'] ?: $result['orderID']));
                    }
                }
            }
			catch(Exception $e){
				
				$result['error'] = $e->getMessage();
			}
$_timer->log();
		break;

        case 'restore':
			try{
            $orderID = intval($_POST['orderID']);
            $order   = udb::single_row("SELECT * FROM `orders` WHERE `orderID` = " . $orderID);
            if (!$order)
                throw new Exception("Cannot find booking " . $orderID);

            $showID = $order['orderIDBySite'];

            if(!$_CURRENT_USER->has($order['siteID']))
                throw new Exception("הזמנה #" . $showID . " לא שייכת למשתמש");
            if ($order['status'])
                throw new Exception("הזמנה #" . $showID . " לא בוטלה");

            $units = udb::single_column("SELECT `unitID` FROM `orderUnits` WHERE `orderID` = " . $orderID);
            if (!count($units))
                throw new Exception("לא נמצא חדר בהזמנה #" . $showID);

            if (!checkAvil($order['timeFrom'], $order['timeUntil'], $units, $orderID)){
                foreach($units as $uid)
                    addTfusa($orderID, $uid, $order['timeFrom'], $order['timeUntil']);

                udb::update('orders', array('status' => 1), '`orderID` = ' . $orderID);

                $user_log->save('order_restore', $order['siteID'], $orderID);

                $result['text'] = "ההזמנה שוחזרה בהצלחה";
                $result['status'] = 1;
            }
            else
                throw new Exception("ישנם חדרים תפוסים בהזמנה #" . $showID);
			}
			catch(Exception $e){
				
				$result['error'] = $e->getMessage();
			}
$_timer->log();
            break;

        case 'reviewInvite':
            $orderID = intval($_POST['orderID']);
            $order = udb::single_row("SELECT orders.*, sites.siteName, sites.fromName FROM `orders` INNER JOIN `sites` USING(`siteID`) WHERE orders.orderID = " . $orderID);

            if (!$order)
                throw new Exception("Cannot find booking " . $orderID);
            if(!$_CURRENT_USER->has($order['siteID']))
                throw new Exception("הזמנה #" . $orderID . " לא שייכת למשתמש");
            if (!$order['customerEmail'])
                throw new Exception("חסר אימייל בהזמנה");

            //include_once __DIR__ . "/../phpmailer/class.bizonlineMailer.php";
            include_once __DIR__ . "/../sendgrid/sendgrid-php.php";

            send_review_invite($order);

            $result['success'] = true;
            $result['title']  = 'הצלחה';
            $result['text']   = 'הזמנה לשליחת חוות דעת נשלחה בהצלחה';
$_timer->log();
            break;

		default:
	
	}
}
catch (Exception $e){
    $result['error'] = $e->getMessage();
}
	//echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


function getExtras($siteID, $tc, $list, $counts){
    $que = "SELECT * FROM `sites_treatment_extras` AS `s` INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`) 
                                WHERE s.extraID IN (" . implode(',', $list) . ") AND s.siteID = " . $siteID . " AND s.included = 0 AND s.active = 1";
    $extras = udb::key_row($que, 'extraID');

    $pin = min(3, max(1, $tc));

    $res = ['extras' => [], 'total' => 0];
    foreach($extras as $extra){
        if ($extra['extraType'] == 'package'){
            if (!$tc)
                continue;

            $price = $extra['price' . $pin] ?: $extra['price' . ($pin - 1)] ?: $extra['price1'];

            $res['extras'][$extra['extraID']] = [
                'extraID' => $extra['extraID'],
                'price'   => $price,
                'count'   => $tc
            ];

            $res['total'] += $tc * $price;
        }
        elseif ($cnt = $counts[$extra['extraID']]) {
            $res['extras'][$extra['extraID']] = [
                'extraID' => $extra['extraID'],
                'price'   => $extra['price1'],
                'count'   => $cnt
            ];

            $res['total'] += $cnt * $extra['price1'];
        }
    }

    return $res;
}
