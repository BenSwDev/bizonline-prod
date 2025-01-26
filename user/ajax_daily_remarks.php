<?php 
	require_once "auth.php";
    
	$result = new JsonResult();
	
    $siteID = intval($_POST['siteID']);
    $date = typemap($_POST['date'], 'date');
	$remarks = typemap($_POST['remarks'], 'text');


    try {
		
		
		if (!$_CURRENT_USER->has($siteID))
          throw new Exception("אינך מורשה לבצע עדכון זה");
		
		
         $que = udb::query("DELETE FROM daily_calendar_remarks WHERE siteID=".$siteID." AND date = '".$date."'");
		if($remarks){
			$remarksUpdate = array();
			$remarksUpdate['siteID'] = $siteID;
			$remarksUpdate['date'] = $date;
			$remarksUpdate['remarks'] = $remarks;
			udb::insert("daily_calendar_remarks", $remarksUpdate);   
			$result['event'] = '1';   
		}
			

        


    }
        
    catch(Exception $e){
        $result['error'] = $e->getMessage();
    }
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);