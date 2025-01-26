<?php
require_once "auth.php";

$result = new JsonResult();

$OrderIDS = 0;
$time_units = "";
if (isset($_POST["OrderIDS"])) {$OrderIDS = $_POST["OrderIDS"];}
if (isset($_POST["time_units"])) {$time_units = $_POST["time_units"];}
$siteID = intval($_POST['sid']);
$workers = intval($_POST['workers']);
$shiftsTable = $workers? 'workShifts' : 'spaShifts';
	
if (!$_CURRENT_USER->has($siteID)){
	$result['success'] = false;
	$result['error'] = true;
	$result['title']  = 'נכשל';
	$result['text']   = 'אינך מורשה לבצע עדכון זה';
} 
else if (!empty($time_units)) 
{
	
	$result['success'] = false;
	$result['error'] = true;
	$result['title']  = 'נכשל';
	$result['text']   = 'ארעה שגיאה';

    if (!empty($_POST['fromDate'])) {
        if (!empty($_POST["sid"])) {
            if (!empty($_POST["masterID"])) {
        $time_units_json = json_decode($time_units);
                
				foreach ($time_units_json as $time_units_json_vals) {
					$status = $time_units_json_vals->status;
                    if(!$status){
						$fromDate_new = typemap(implode('-',array_reverse(explode('/',trim($_POST['fromDate'])))),"date");
						$timeFrom = $fromDate_new." ".date("H:i:s", strtotime($time_units_json_vals->start_time));
						$timeUntil = $fromDate_new." ".date("H:i:s", strtotime($time_units_json_vals->end_time));
						$crossTreat += udb::single_value("SELECT COUNT(orderID) FROM orders WHERE therapistID = ".intval($_POST["masterID"])." AND `timeFrom` < '".$timeUntil."' AND `timeUntil` > '".$timeFrom."' AND status = 1");
					}
                    
				}

				if($crossTreat){
					$result['success'] = false;
					$result['error'] = true;
					$result['title']  = 'נכשל';
					$result['text']   = 'לא ניתן לקבוע הפסקה על זמני טיפול קיים';

				}else{

					$del_all = "DELETE FROM ".$shiftsTable." WHERE masterID = '".intval($_POST["masterID"])."' AND DATE_FORMAT(timeFrom,'%d/%m/%Y') = '".udb::escape_string(typemap($_POST['fromDate'], 'string'))."' AND siteID = '".intval($_POST["sid"])."' ";
					udb::query($del_all);
					
					foreach ($time_units_json as $time_units_json_vals) {
						
						$fromDate_new = typemap(implode('-',array_reverse(explode('/',trim($_POST['fromDate'])))),"date");
						$timeFrom = $fromDate_new." ".date("H:i:s", strtotime($time_units_json_vals->start_time));
						$timeUntil = $fromDate_new." ".date("H:i:s", strtotime($time_units_json_vals->end_time));						
						$desc = $time_units_json_vals->desc;
						$status = $time_units_json_vals->status;
						
						#################
						$unit_in = array();
						/*if($status==-1){
							$status = 0;
							$unit_in['online'] = 1;
						}*/
						$unit_in['orderName'] = $desc;
						$unit_in['siteID'] = $_POST["sid"];
						$unit_in['masterID'] = $_POST["masterID"];
						$unit_in['timeFrom'] = $timeFrom;
						$unit_in['timeUntil'] = $timeUntil;
						$unit_in['status'] = $status;
						$unit_in['guid'] = "";
						$all_data[]= $unit_in;
						#################
						$new_id = udb::insert($shiftsTable, $unit_in);
						if ($new_id) {

									$result['data'] = $all_data;
									$result['success'] = true;
									$result['error'] = false;
									$result['title']  = 'הצלחה';
									$result['text']   = 'משמרת נוספה בהצלחה';

						}
						
					}
					if($result['error']){
						$result['success'] = true;
						$result['error'] = false;
						$result['title']  = 'הצלחה';
						$result['text']   = 'נוקו המשמרות למטפל';
					}

				}
            }
        }
        
    }
}

?>