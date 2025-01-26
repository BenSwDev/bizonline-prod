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
			udb::insert('tfusa', 
				['orderID' => $orderID,
				'unitID' => $unitID,
				'date' => date("Y-m-d", strtotime($from)+900*$i), 
				'hour' => date("H:i:s", strtotime($from)+900*$i)
				]
				);
		}
		date_default_timezone_set($tz);
	}
	function checkAvil($data){
		$hourFrom = date("H:i:s", strtotime($data['realStartTime']));
		$hourTo = date("H:i:s", strtotime($data['realEndTime']));

		$que = "SELECT COUNT(*) FROM tfusa 
		INNER JOIN `orders` USING (orderID)
		WHERE unitID=".$data['unitID']." AND `orders`.`status` = 1 
		AND ((date='".$data["fromDate"]."' AND hour >= '$hourFrom') 
		OR (date = '".$data["endDate"]."' AND hour < '$hourTo') 
		OR (date < '".$data["fromDate"]."' AND date > '".$data["endDate"]."'))".($data['orderID']?" AND orderID!=".$data['orderID']:"");
		return udb::single_value($que);
	}

	try{
		$orderID = intval($_POST['orderID']);
		$allDay = intval($_POST['allDay']);
		$unitID = intval($_POST['unitID']);
		$dateFrom = typemap(implode('-',array_reverse(explode('/',trim($_POST['from'])))),"date");
		$dateTo = date("Y-m-d",strtotime("+1 day",strtotime($dateFrom)));

		if(!$dateFrom){
			exit;
		}

			
		if($allDay){
			$que = "SELECT `sites`.`checkInHour`, `sites`.`checkOutHour`, `sites`.`siteID`, rooms.roomID  FROM `sites`
                    INNER JOIN `rooms` USING (siteID)
                    INNER JOIN `rooms_units` USING (roomID)
                    WHERE `rooms_units`.`unitID` = " . $unitID;
			$times = udb::single_row($que);

			$hourFrom = $times['checkInHour'];
			$hourTo = $times['checkOutHour'];
			$siteID = $times['siteID'];

            // checking if unit belongs to one of user's sites
            if (!$_CURRENT_USER->has($siteID))
                throw new Exception("You are not authorized to update this unit");

			$data = ["realStartTime" => $hourFrom ,"realEndTime" => $hourTo, "fromDate" => $dateFrom, "endDate" => $dateTo, "unitID" => $unitID];
			$timeFrom = $dateFrom." ".$hourFrom;
			$timeUntil = $dateTo." ".$hourTo;

			if(!checkAvil($data)){

				$orderID = udb::insert('orders', 
					[
					'siteID' => $siteID,
					'timeFrom' => $timeFrom, 
					'timeUntil' => $timeUntil,
					'allDay' => 1
					]
					);
				udb::insert("orderUnits",["orderID" => $orderID, "unitID" => $unitID]);
				addTfusa($orderID, $unitID, $timeFrom, $timeUntil);
				$result['success'] = "בוצע בהצלחה";
				$result['orderID'] = $orderID;
				$result['status'] = 1;
			}else{
				
				$que = "SELECT orders.orderID, orders.siteID,orders.allDay FROM tfusa 
                            INNER JOIN `orders` USING (orderID)
                        WHERE unitID = " . $unitID . " AND `orders`.`status` = 1 
                            AND ((date='".$dateFrom."' AND hour >= '$hourFrom') 
                            OR (date = '".$dateTo."' AND hour < '$hourTo') 
                            OR (date < '".$dateFrom."' AND date > '".$data["endDate"]."'))";
                $checkOcc = udb::single_row($que);

                // checking if unit belongs to one of user's sites
                if (!$_CURRENT_USER->has($siteID))
                    throw new Exception("You are not authorized to update this unit");

				if($checkOcc['allDay']){
					$que = "DELETE FROM `orders` WHERE `orderID`=".$checkOcc['orderID'];
					udb::query($que);
					$que = "DELETE FROM `orderUnits` WHERE `orderID`=".$checkOcc['orderID'];
					udb::query($que);
					$que = "DELETE FROM `tfusa` WHERE `orderID`=".$checkOcc['orderID'];
					udb::query($que);
					$result['status'] = 2;
				}else{
					$result['status'] = 3;
				}	
			}

            DatesManager::update_wubook($dateFrom, 1, $times['roomID']);
		}


	}
	
	catch(Exception $e){
		$result['error'] = $e->getMessage();
	}
