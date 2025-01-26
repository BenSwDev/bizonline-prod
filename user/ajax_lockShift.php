<?php 
	require_once "auth.php";
    
	$result = new JsonResult();

    $cur_uid = udb::escape_string(intval($_POST['uid']));
    $todayRev = udb::escape_string($_POST['date']);
	$todayRev = implode('-',array_reverse(explode('/',trim($todayRev))));


    try {
		$siteID = udb::single_value("SELECT siteID FROM therapists WHERE therapistID=".$cur_uid);
		
		if (!$_CURRENT_USER->has($siteID))
          throw new Exception("אינך מורשה לבצע עדכון זה");
		
		$lock = udb::single_row("SELECT * FROM spaShifts WHERE masterID=".$cur_uid." AND (timeFrom = '".$todayRev." 00:00:00' AND timeUntil = '".$todayRev." 23:59:59')");
		//echo "SELECT * FROM spaShifts WHERE masterID=".$cur_uid." AND (timeFrom LIKE '%".$todayRev."%' OR timeUntil LIKE '%".$todayRev."%)";
        if($lock) {
            $que = udb::query("DELETE FROM spaShifts WHERE masterID=".$cur_uid." AND timeFrom  = '".$todayRev." 00:00:00' AND timeUntil = '".$todayRev." 23:59:59' AND status=0");
			$result['event'] = '0';
		}else{
			$site = udb::single_value('SELECT siteID FROM `therapists` WHERE therapistID = '.$cur_uid);
			$treatments = udb::single_row("SELECT * FROM orders WHERE status=1 AND therapistID=".$cur_uid." AND (timeFrom LIKE '%".$todayRev."%' OR timeUntil LIKE '%".$todayRev."%')");
			//echo "SELECT * FROM spaShifts WHERE masterID=".$cur_uid." AND (timeFrom LIKE '%".$todayRev."%' OR timeUntil LIKE '%".$todayRev."%')";
			if($treatments){
				throw new Exception("לא ניתן לנעול את המטפל - קיימים לו טיפולים");
			}else{
				$shiftLock = array();
				$shiftLock['siteID'] = $site;
				$shiftLock['masterID'] = $cur_uid;
				$shiftLock['timeFrom'] = $todayRev." 00:00:00";
				$shiftLock['timeUntil'] = $todayRev." 23:59:59";
				$shiftLock['status'] = 0;
				udb::insert("spaShifts", $shiftLock);   
				$result['event'] = '1';                    
			}

        }


    }
        
    catch(Exception $e){
        $result['error'] = $e->getMessage();
    }
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);