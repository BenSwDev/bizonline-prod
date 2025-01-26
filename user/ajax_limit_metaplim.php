<?php 
	require_once "auth.php";
    
	$result = new JsonResult();

    $siteID = udb::escape_string(intval($_POST['siteID']));
    $limit = udb::escape_string(intval($_POST['limit']));
	
    try {
		if (!$_CURRENT_USER->has($siteID))
          throw new Exception("אינך מורשה לבצע עדכון זה");
		
		udb::query("UPDATE sites SET limit_metaplim = ".$limit." WHERE siteID = ".$siteID);
		$result['status']='success';
    }


    
        
    catch(Exception $e){
        $result['error'] = $e->getMessage();
    }
