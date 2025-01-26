<?php
require_once "auth.php";

$result = new JsonResult();

$OrderIDS = 0;
$time_units = "";


$workers = intval($_POST['workers']);
$shiftsTable = $workers? 'workShifts' : 'spaShifts';

$startweek = udb::escape_string(typemap($_POST['startweek'], 'date'));
$endweek = date('Y-m-d 23:59:59',strtotime($startweek." +6 days"));
$startweek = $startweek." 00:00:00";
if($_CURRENT_USER->has(intval($_POST["sid"]))){
	$ts = udb::single_row("SELECT * FROM `therapists` WHERE `therapistID` = " . intval($_POST["masterID"]) . " AND `siteID` = " . intval($_POST["sid"]));   // ts = therapist status
}

if ( !empty($_POST['startweek']) && !empty($_POST["sid"]) && !empty($_POST["masterID"]) && $ts) {
    //print_r($_POST["startTime"])."<BR><BR>";	
    //print_r($_POST["endTime"])."<BR><BR>";
    //print_r($_POST["status"])."<BR><BR>";
				$crossTreat = 0;
				if(($_POST['startTime'])){
					foreach($_POST['startTime'] as $k=>$item) {
						foreach($item as $j => $timeFrom){
							$i++;
							$timeUntil = $_POST['endTime'][$k][$j];
							$timeUntil = udb::escape_string(typemap(($k." ".$timeUntil.":00"), 'string'));
							$timeFrom = udb::escape_string(typemap(($k." ".$timeFrom.":00"), 'string'));
							$status = $_POST['status'][$k][$j];
							if(!$status){
								$que = "SELECT COUNT(orderID) FROM orders WHERE therapistID = ".intval($_POST["masterID"])." AND `timeFrom` < '".$k." ".$timeUntil.":00"."' AND `timeUntil` > '".$k." ".$timeFrom.":00"."' AND `status` = 1";
								//echo $que;
								$crossTreat += udb::single_value($que );
								$result['crossTreat'] = $crossTreat;
							}
							
							/*if($crossTreat)
								break;*/
						}
						/*if($crossTreat)
								break;*/
					}
				}else{
					$result['success'] = true;
				}
				if($crossTreat){
					$result['success'] = false;
					$result['error'] = true;
					$result['title']  = 'נכשל';
					$result['text']   = 'לא ניתן לקבוע הפסקה על זמני טיפול קיים';

				}else{
							
                
					//echo "DELETE FROM ".$shiftsTable." WHERE masterID = '".intval($_POST["masterID"])."' AND timeFrom >= '".$startweek."' AND timeUntil <= '".$endweek."' AND siteID = '".intval($_POST["sid"])."' ";
					$del_all = "DELETE FROM ".$shiftsTable." WHERE masterID = '".intval($_POST["masterID"])."' AND timeFrom >= '".$startweek."' AND timeUntil <= '".$endweek."' AND siteID = '".intval($_POST["sid"])."' ";
					udb::query($del_all);
					if(($_POST['startTime'])){
						foreach($_POST['startTime'] as $k=>$item) {
							foreach($item as $j => $timeFrom){
								$timeUntil = $_POST['endTime'][$k][$j];
								$status = $_POST['status'][$k][$j];
								$k = str_replace('"','',$k);
								$k = str_replace("'",'',$k);
								$unit_in = array();
								$unit_in = array();
								/*if($status==-1){
									$status = 0;
									$unit_in['online'] = 1;
								}*/
								$unit_in['orderName'] = $_POST['desc'][$k][$j];
								$unit_in['siteID'] = intval($_POST["sid"]);
								$unit_in['masterID'] = intval($_POST["masterID"]);
								$unit_in['timeFrom'] = udb::escape_string(typemap(($k." ".$timeFrom.":00"), 'string'));
								$unit_in['timeUntil'] = udb::escape_string(typemap(($k." ".$timeUntil.":00"), 'string'));

								$unit_in['status'] = intval($status);
								print_r($unit_in);
								if($unit_in['timeFrom'] < $unit_in['timeUntil']){
									$new_id = udb::insert($shiftsTable, $unit_in);
									if ($new_id) {

												$result['success'] = true;
												$result['title']  = 'הצלחה';
												$result['text']   = 'משמרת נוספה בהצלחה';

									}
								}
							}
						}
					}
				}               
				
				
}
       

?>


