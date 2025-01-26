<?php
	require_once "auth.php";

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
	    list($fromDate, $fromTime) = explode(' ', date('Y-m-d H:i:s', strtotime($from)));
        list($tillDate, $tillTime) = explode(' ', date('Y-m-d H:i:s', strtotime($till)));

		if (!strcmp($fromDate, $tillDate))
			$que = "SELECT COUNT(*) FROM `tfusa` WHERE tfusa.unitID ".(is_array($units) ? " in (" . implode(',', $units) . ")" : " = " . $units)." AND `date` = '" . $fromDate . "' AND `hour` BETWEEN '" . $fromTime . "' AND '" . $tillTime . "'" . ($orderID ? " AND `orderID` <> " . intval($orderID) : "");
		else
	        $que = "SELECT COUNT(*) FROM `tfusa` WHERE tfusa.unitID ".(is_array($units) ? " in (" . implode(',', $units) . ")" : " = " . $units)." 
		            AND ((`date` = '" . $fromDate . "' AND `hour` >= '" . $fromTime . "') 
		              OR (`date` = '" . $tillDate . "' AND `hour` < '" . $tillTime . "') 
		              OR (`date` < '" . $tillDate . "' AND `date` > '" . $fromDate . "')) " .
                    ($orderID ? " AND `orderID` <> " . intval($orderID) : "");
        return udb::single_value($que);
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

            $active = udb::single_value("SELECT GREATEST(`status`, `allDay`) FROM `orders` WHERE `orderID` = " . intval($orderID));

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
    if (!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN))
        throw new Exception('Access denied.');

	switch($_POST['action']){

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
		break;

		case "cancel":
			$orderID = intval($_POST['orderID']);
			if($orderID){
				$que = "SELECT `siteID`,`parentOrder` FROM `orders` WHERE `orderID` = " . $orderID;
				$order = udb::single_row($que);

                if(!$_CURRENT_USER->has($order['siteID']))
                    throw new Exception("You are not authorized to see this booking");


					
				if ($order['parentOrder']){
					$que = "SELECT orderID FROM `orders` WHERE `parentOrder` = " . $order['parentOrder'];
					$orders = udb::single_column($que);
				}
				else
					$orders = [$orderID];

                $que = "DELETE FROM `tfusa` WHERE `orderID`=".$orderID;
                udb::query($que);

                $que = "UPDATE `orders` SET `status`= 0 WHERE `orderID`=".$orderID;
                udb::query($que);




                $user_log->save('order_cancel', $order['siteID'], $orderID);
				

				$haveTreats = (count($orders) > 1);
				foreach($orders as $ord) {
						$que = "DELETE FROM `tfusa` WHERE `orderID`=".$ord;
						udb::query($que);
		
						$que = "UPDATE `orders` SET `status`= 0 WHERE `orderID`=".$ord;
						udb::query($que);

						
						$user_log->save('order_cancel', $order['siteID'], $ord);
	
				}

                $result['text'] = $haveTreats?"ההזמנה וטיפוליה בוטלו בהצלחה":"ההזמנה בוטלה בהצלחה";
                $result['status'] = 1;
            }
		break;
		case "delete":
			$orderID = intval($_POST['orderID']);
            $pays = 0;

			if($orderID){
				$que = "SELECT `siteID`, `parentOrder`, `apiSource` FROM `orders` WHERE `orderID` = " . $orderID;
				$order = udb::single_row($que);

                $pays = udb::single_value("SELECT COUNT(*) FROM `orderPayments` WHERE `orderID` = " . $orderID) ?: 0;
			}
            if(!$_CURRENT_USER->has($order['siteID']))
                throw new Exception("You are not authorized to see this booking");
            if ($order['apiSource'] == 'spaplus')
                throw new Exception("Cannot delete online bookings");
            if ($pays > 0)
                throw new Exception("Cannot delete bookings with payments");
			
			if($order['parentOrder'] != $orderID && $order['parentOrder']>0) //check if parent order or not spa order
				throw new Exception("ביטול הזמנה לא חוקי - זו אינה הזמנת אב");


				$que = "SELECT orderID FROM `orders` WHERE `parentOrder` = ".$orderID. " OR `orderID` = ".$orderID ;
				$orders = udb::single_column($que);

				$que = "DELETE FROM `orders` WHERE `orderID` IN (".implode(",", $orders).")";
				udb::query($que);
				$que = "DELETE FROM `orderUnits` WHERE `orderID` IN (".implode(",", $orders).")";
				udb::query($que);
				$que = "DELETE FROM `tfusa` WHERE `orderID` IN (".implode(",", $orders).")";
				udb::query($que);

				$user_log->save('order_delete', $order['siteID'], $orderID, ['orderIDBySite' => $order['orderIDBySite']]);

				$result['text'] = "ההזמנה נמחקה בהצלחה";
				$result['status'] = 1;

		break;
	
		case "insertOrder":

		  $data = typemap($_POST, [
				'!orderID'   => 'int',
//				'!status'   => 'int',
				'approved'   => 'int',
				'adminApproved'   => 'int',
				'setapproved'   => 'int',
				'realStartTime'   => 'string',
				'sourceID'   => 'string',
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
				'form_to_sign'  => 'int',
				'price_to_pay'    => 'int',
                //'prePay'        => 'int',
				'extraPrice'    => 'int',
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
			]);
			$data['fromDate'] = typemap(implode('-',array_reverse(explode('/',trim($_POST['fromDate'])))),"date");
			$data['endDate'] = typemap(implode('-',array_reverse(explode('/',trim($_POST['endDate'])))),"date");

			try{
				//if(!$data['unitID'] || !$data['realStartTime'] || !$data['realEndTime'] || !$data['fromDate'] || !$data['endDate']){
                if(!$data['unitID'] || !$data['startTime'] || !$data['endTime'] || !$data['fromDate'] || !$data['endDate']){
					throw new Exception ("שגיאת נתונים");
				}
				if(strcmp($data['fromDate'], $data['endDate']) > 0){
					throw new Exception ("תאריך התחלה גדול מתאריך סיום");
				}

				if (!$data['otype'] || !UserUtilsNew::$orderTypes[$data['otype']])
                    $data['otype'] = 'order';

				//$timeFrom = $data['fromDate']." ".date("H:i:s", strtotime($data['realStartTime']));
				//$timeUntil = $data['endDate']." ".date("H:i:s", strtotime($data['realEndTime']));
                $timeFrom = $data['fromDate']." ".date("H:i:s", strtotime($data['startTime']));
                $timeUntil = $data['endDate']." ".date("H:i:s", strtotime($data['endTime']));

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

                if(!$_CURRENT_USER->has($siteID))
                    throw new Exception("You are not authorized to see this booking");

				$dataSave = [
                    'timeFrom' => $timeFrom,
                    'timeUntil' => $timeUntil,
                    'showTimeFrom' => $data['fromDate']." ".date("H:i:s", strtotime($data['startTime'])),
                    'showTimeUntil' => $data['endDate']." ".date("H:i:s", strtotime($data['endTime'])),
                    'sourceID' => $data['sourceID'],
                    'customerName' => $data['name'],
                    'customerPhone' => $data['phone'],
                    'customerPhone2' => $data['phone2'],
                    'customerEmail' => $data['email'],
                    'customerTZ' => $data['tZehoot'],
                    'price' => $data['price_to_pay'] ?: $payment['payment'],
                    'extraPrice' => $data['extraPrice'],
                    'comments_customer' => $data['comments_customer'],
                    'comments_owner' => $data['comments_owner'],
                    'comments_payment' => $data['comments_payment'],
                    'reason' => $data['reason'],
                    'adults' => $people['adults'],
                    'kids' => $people['kids'],
                    'babies' => $people['babies'],
                    'form_to_sign' => $data['form_to_sign'],
//                    'status' => $data['status'] ?? 0,
                    'approved' => $data['approved'] ?? 0,
                    'adminApproved' => $data['adminApproved'] ?? 0,
                    //'advance'  => $data['prePay'] ?: $payment['adv_payment'],
                    'customerAddress' => $data['clientAddress']
                ];

				if(!$data['orderID']){
				    $dataSave['siteID'] = $siteID;

					if(!checkAvil($timeFrom, $timeUntil, $data['unitID'])){
						$dataSave['guid'] = GUID();
                        $dataSave['orderType'] = $data['otype'];

                        udb::query("LOCK TABLE `orders` WRITE");

						$que = "SELECT MAX(`orderIDBySite`) FROM `orders` WHERE `siteID` = " . $siteID;
						$maxOrderID = udb::single_value($que);
						$dataSave['orderIDBySite'] = $maxOrderID+1;
						$orderID = udb::insert('orders', $dataSave);

                        udb::query("UNLOCK TABLES");

                        $unit_in = ['orderID' => $orderID];
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

                        $user_log->save($data['otype'], $siteID, $orderID);

						$result['success'] = "בוצע בהצלחה";
						$result['orderID'] = $orderID;
					}else{
						
						throw new Exception ("החדר תפוס");
					}
				}else{
				    $prevID = udb::single_value("SELECT `siteID` FROM `orders` WHERE `orderID` = " . $data['orderID']);
				    $_isApproved = udb::single_value("SELECT `approved` FROM `orders` WHERE `orderID` = " . $data['orderID']);
                    if ($prevID != $siteID)
                        throw new Exception("Cannot select units from different site");     // should not happen

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

					
					updateOrder($data['orderID'],$data,$dataSave);
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
			catch(Exception $e){
				
				$result['error'] = $e->getMessage();
			}
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

                // checking if it's spa order
                if ($order['parentOrder'] > 0){
                    $O = new OrderSpaMain($orderID);
                    $O->restore();
                }
                else {      // zimer order
                    $units = udb::single_column("SELECT `unitID` FROM `orderUnits` WHERE `orderID` = " . $orderID);
                    if (!count($units))
                        throw new Exception("לא נמצא חדר בהזמנה #" . $showID);

                    if (!checkAvil($order['timeFrom'], $order['timeUntil'], $units, $orderID)){
                        foreach($units as $uid)
                            addTfusa($orderID, $uid, $order['timeFrom'], $order['timeUntil']);

                        udb::update('orders', array('status' => 1), '`orderID` = ' . $orderID);
                    }
                    else
                        throw new Exception("ישנם חדרים תפוסים בהזמנה #" . $showID);
                }

                $user_log->save('order_restore', $order['siteID'], $orderID);

                $result['text'] = "ההזמנה שוחזרה בהצלחה";
                $result['status'] = 1;
			}
			catch(Exception $e){
				
				$result['error'] = $e->getMessage();
			}

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
            break;

		default:
	
	}
}
catch (Exception $e){
    $result['error'] = $e->getMessage();
}
	//echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
