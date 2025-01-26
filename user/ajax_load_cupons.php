<?php
include_once "auth.php";
include_once "../picUpload.php";

$siteID = intval($_POST['siteID2'])?: intval($_GET['siteID2']);
$id = intval($_GET['id']) ;
$act = intval($_GET['act']);
$results = [];
$results['success'] = false;


try {

	if ($act && !$_CURRENT_USER->has(intval($siteID)))
		throw new Exception("גישה נדחתה לאתר #" . intval($siteID));

	switch($act) {
		case 0://just get data
			$sql = "SELECT * FROM `sites_cupons` where deleted=0 and id=".$id;
			$item = udb::single_row($sql);
			if(isset($_SESSION['user_id']) || intval($_SESSION['user_id']))
				$results['admin']=1;
			if($item) {
				$results['success'] = true;
				$results['data'] = $item;
				$results['data']['expire'] = date("d/m/Y",strtotime($results['data']['expire']));
				$results['data']['cDesc'] = $results['data']['cDesc'];
				$results['data']['cCode'] = strtoupper($results['data']['cCode']);
			}
			break;
		case 1: //inset or update
			$cp = [];
			$id = intval($_POST['id']);
			$cp['title'] = typemap($_POST['title'],'text');
			$cp['siteID'] = intval($siteID); //must use siteID2!!!
			$cp['cDesc'] = typemap($_POST['cDesc'],'text');
			$cp['maxDiscount'] = typemap($_POST['maxDiscount'],'int');

			$cp['cCode'] = strtoupper(typemap($_POST['cCode'],'string'));
			$cp['cType'] = typemap($_POST['cType'],'int');
			$cp['amount'] = intval($_POST['amount']);
			$cp['expire'] = date("Y-m-d",strtotime(implode("-",array_reverse(explode("/",$_POST['expire'])))));
			
			if(!$cp['title'] || !$cp['amount'] || !$cp['cCode']  || !$_POST['expire'])
				throw new Exception('חובה לעדכן כותרת, מספר קופון, שווי  ותאריך תוקף');

			if(strtotime($cp['expire']) < strtotime(date("Y-m-d")))
				throw new Exception('תאריך קופון חייב להיות גדול או זהה ליום נוכחי');

			if($cp['amount'] > 100 && $cp['cType']==2)
				throw new Exception('אחוז הנחה מקסימלי לא יכול להיות גדול מ 100');			
			
			
			$results['success'] = true;
			if($id) {
				udb::update("sites_cupons",$cp," id=".$id);
			}
			else {
	//            $count = udb::single_value("select count(*) from sites_cupons where siteID=".intval($siteID)); //must use siteID2!!!
	//            $cp['showOrder'] = $count + 1;
				udb::insert("sites_cupons",$cp);
			}
			break;
		case 2: //set active or not
			$sql = "update `sites_cupons` set active= NOT active where id=".$id." AND siteID = ".$siteID;
			udb::query($sql);
			$results['success'] = true;
			break;
		case 3: //Delete coupon
			
			$sql = "update `sites_cupons` set deleted=1 where id=".$id." AND siteID = ".$siteID;
			udb::query($sql);
			$results['success'] = true;
			break;
		/*case 4:
			$showOrder = 1;
			foreach($_POST['data'] as $item) {
				$cp = [];
				$cp['showOrder'] = $showOrder;
				//udb::update("sites_cupons",$cp," id=".$item);
				$showOrder++;
			}
			$results['success'] = true;
			break;*/
	}
} catch (Exception $e){		
	$results['error'] = $e->getMessage();
}




echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
