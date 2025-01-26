<?php 
	require_once "auth.php";
    
	$result = new JsonResult();

    $orderID = intval($_POST['orderID']);
    $client_status = intval($_POST['client_status']);
	$siteID = udb::single_value('SELECT siteID FROM `orders` WHERE orderID ='.$orderID);
	
    try {
		if (!$_CURRENT_USER->has($siteID))
          throw new Exception("אינך מורשה לבצע עדכון זה");
		
		udb::query("UPDATE orders SET client_status = ".$client_status." WHERE orderID = ".$orderID);
		$result['status']='success';
    }


    
        
    catch(Exception $e){
        $result['error'] = $e->getMessage();
    }
