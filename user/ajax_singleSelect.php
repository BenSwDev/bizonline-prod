<?php 
require_once "auth.php";

$result = new JsonResult;

$act= $_POST['act'];


$siteID = intval($_POST['tsid']);




$date   = implode('-',array_reverse(explode('/',trim($_POST['startDate']))));
$date   = typemap($date, 'date') ?: date("Y-m-d");

$time   = typemap($_POST['startTime'], 'time');
$duration   = intval($_POST['duration']) ?: 5;
$spa_cleanTime   = intval($_POST['spa_cleanTime']);
$malefemale   = intval($_POST['malefemale']);
$tmalefemale   = intval($_POST['tmalefemale']);
$treatmentID   = intval($_POST['treatmentID']);
$orderID = intval($_POST['id']);
$timeFrom = $date." ". $time;
$timeUntil = date('Y-m-d H:i:s', strtotime($timeFrom."+".$duration." minutes"));
$timeToRoom = date('Y-m-d H:i:s', strtotime($timeFrom."+".($duration+$spa_cleanTime)." minutes"));
$roomID = intval($_POST['roomID']);

$title['therapist'] = "בחירת  מטפל".($tmalefemale==2? "ת" : ($tmalefemale==0? "/ת" :""));
$title['spa_roomID'] = "בחירת חדר";
$title['treatmentID'] = "בחירת טיפול";


if($treatmentID){
	$que = "SELECT treatmentName FROM treatments WHERE treatmentID = ".$treatmentID;
	$treatmentName = udb::single_value($que);
}

$parentOrder = intval($_POST['parent'])?: 0;
if(!$parentOrder && $orderID){
	$que = "SELECT parentOrder FROM orders WHERE orderID = ".$orderID;
	$parentOrder = udb::single_value($que);
}


try {

if (!$_CURRENT_USER->has($siteID))
	throw new Exception("גישה נדחתה לאתר #" . $siteID);
// checking that user has access to this $siteID
	
	
	switch ($act){
		case 'therapist':
		
			if($tmalefemale > 0)
			    $on_genders = " AND (`t`.gender_self = ".$tmalefemale."  OR `t`.gender_self = 3)" ;
			    //$on_genders = "" ;

            $que ="SELECT
					`t`.therapistID AS sendID,
					`t`.siteName AS showName,
					`t`.gender_self,
					`t`.gender_client,
					`s`.timeFrom,
					`s`.timeUntil, 
					`tr`.`treatmentID`,
					COUNT(`breaks`.`orderID`) AS on_break,
					COUNT(`parent`.orderID) AS countOrders,
					IF(workerType = 'fictive', 1, 0) AS fictive,					
					IF((COUNT(`parent`.orderID) = 0 AND ((s.`timeFrom` > 0 AND workerType = 'regular') OR (workerType = 'fictive' AND `s`.timeFrom IS NULL))),1,0)  AS Available,
					IF ((`tr`.`treatmentID` > 0 OR workerType='fictive') AND (`t`.gender_client = 3 OR `t`.gender_client = ".$malefemale.") ".$on_genders.",1,0) AS Allowed
					FROM`therapists` AS `t`
					LEFT JOIN `therapists_treats` as `tr` ON (`t`.`therapistID` = `tr`.`therapistID` AND `tr`.`treatmentID` = ".$treatmentID.")
					LEFT JOIN `spaShifts` AS `s` ON(s.masterID = t.therapistID AND `s`.status >= 0  AND '" . $timeFrom . "' >= s.timeFrom AND '" . $timeUntil . "' <= s.timeUntil)
					LEFT JOIN `spaShifts` AS `breaks` ON(`breaks`.masterID = t.therapistID AND `breaks`.status = 0  AND '" . $timeUntil . "' > `breaks`.timeFrom AND '" . $timeFrom . "' < `breaks`.timeUntil)
					LEFT JOIN `orders` AS `o` ON(`t`.`therapistID` = `o`.`therapistID` AND `o`.orderID <> ".$orderID." AND '" . $timeFrom . "' < o.timeUntil AND '" . $timeUntil . "' > o.timeFrom)
					LEFT JOIN `orders` AS `parent` ON (`o`.parentOrder = `parent`.`orderID` AND `parent`.`status` = 1) 
					WHERE 	t.siteID = " . $siteID . " AND t.active = 1 AND t.deleted < 1 AND (t.workStart IS NULL OR t.workStart <= '" . $date . "') AND (t.workEnd IS NULL OR t.workEnd >= '" . $date . "')
					GROUP BY t.therapistID
					ORDER BY Allowed DESC, Available DESC, on_break, fictive DESC, countOrders ASC, showName ASC  ";
					
				
			$list = udb::full_list($que);
$result['q'] = $que;
		break;

		case 'spa_roomID':
			$que = "SELECT 
					u.unitID AS sendID, 
					u.unitName AS showName,
					u.maxTreatments,
					COUNT(`o`.orderID) AS countOrders,	
					IF(units_treats.treatmentID,1,0) AS treatValid
					FROM `rooms_units` AS `u` INNER JOIN `rooms` USING(`roomID`) 
					LEFT JOIN orderUnits AS `ou` ON (`ou`.unitID = `u`.unitID)
					LEFT JOIN orders AS `o` ON (`ou`.orderID = `o`.orderID AND `o`.parentOrder <> ".$parentOrder." AND '" . $timeFrom . "' < o.timeUntil AND '" . $timeUntil . "' > o.timeFrom AND `o`.`status` = 1)
					LEFT JOIN units_treats ON(`u`.unitID = units_treats.unitID AND units_treats.treatmentID = ".$treatmentID.")
					WHERE rooms.active = 1 AND rooms.siteID = " .  $siteID . " AND u.hasTreatments 	> 0	
					GROUP BY u.unitID
					ORDER BY treatValid DESC, countOrders, showName
					";
			//echo $que;
				
			$list = udb::full_list($que);
			if($parentOrder){
			$que2 = "SELECT
					u.unitID AS sendID, 
					COUNT(`thisOrder`.`orderID`) AS treatsInRoom
					FROM `rooms_units` AS `u` 
					INNER JOIN `rooms` USING(`roomID`) 
					INNER JOIN orderUnits AS `thisOrder` ON (`u`.unitID = `thisOrder`.unitID) 
					INNER JOIN orders USING(orderID)
					WHERE rooms.active = 1 AND rooms.siteID = " .  $siteID . " AND orders.parentOrder =  ".$parentOrder." AND orderID <> ".$parentOrder." AND '" . $timeFrom . "' < orders.timeUntil AND '" . $timeUntil . "' > orders.timeFrom AND `orders`.`status` = 1 AND orders.orderID <> ".$orderID."
					GROUP BY u.unitID";
					$treatsInRoom = udb::key_value($que2, 'sendID');
			}


					



		//IF((COUNT(s.masterID) > 0 AND COUNT(o.orderID) = 0 OR (gender_self = 3 AND `s`.timeFrom > '0')),1,0)  AS Available		
				
		break;

		default:
			throw new Exception('Unknown operation code');
	}?>
	


	<?php
}
catch (Exception $e){
    //udb::query("UNLOCK TABLES"); ?????
    $result['error'] = $e->getMessage();
}
		
ob_start();	
//print_r($list);
$malefemale = $malefemale?: 0;
$mf[0] = 'יש לבחור מגדר';
$mf[1] = 'גבר';
$mf[2] = 'אשה';
?>

<div class="title selectTitle">
	<?=$title[$act]?> - שעה <?=$time?> 
	<br>
	<?=$_POST['name']? $_POST['name'] : ""?> (<?=$mf[$malefemale]?>)
</div>

<? if($list && is_array($list)){?>
	<div class="single-select">
	<?
	$quot1 = array("'",'"');
	$quot2 = array("\'",'\"');

	switch ($act){
		case 'therapist':

			$gender_s[1] = "<span class='gender m'>מטפל</span>";
			$gender_s[2] = "<span class='gender f'>מטפלת</span>";
			$gender_s[3] = "";
			
			$workerType[1] = "<span class='gender x'>פיקטיבי</span>";
			$workerType[0] = "";

			$gender_c[1] = "<span class='client t1'>בגברים בלבד</span>";
			$gender_c[2] = "<span class='client t2'>בנשים  בלבד</span>";
			$gender_c[3] = "<span class='client t3'>בגברים ונשים</span>";
			//echo "showme" .$que;
			foreach($list as $li){
				if($li['on_break'])
					$li['Available'] = 0;

				$click="";
				if($li['Available'] && $li['Allowed']){			
					$class = "Allowed";
				}else if(!$li["Allowed"]){
					$class = "notAllowed";
				}else{
					$class = "notAvailable a".$li['Available'];
				}

				if($li['Allowed'] && ($li['Available'] || (!$li['fictive'] && !$li['timeFrom'] && !$li['on_break']))){
					$click = "$('#".$act."').val(".$li['sendID'].");$('#".$act."Name').val('".str_replace($quot1,$quot2,$li['showName'])."');$('#selectpop').fadeOut('fast');";
					if(!$li['fictive'] && !$li['timeFrom']){
						$class = "noShifts";
					}
				}
				if($li['sendID'] == $_POST['therapist']){
					$class.=' selected';
				}

				if(!$li['Allowed'] && !$firstNA){ $firstNA=1;?>
				<div style="padding:15px 0;text-align:right;font-size:16px">מטפלים נוספים שלא יכולים לבצע את הטיפול בשל סוג הטיפול או העדפות המטופל או המטפל</div>
				<?}?>
				<div class="single-select-row <?=$class?>" onclick="<?=$click?>">
					<div>
					<?=$li['showName']?> 
					<?=$workerType[$li['fictive']]?> <?=$gender_s[$li['gender_self']]?> <?=!$li["fictive"]? "| ".$gender_c[$li["gender_client"]] : ($li["lockedFictive"]? "המטפל נעול" :"");?>
						<div>
						<?if($li['timeFrom']){
							if($li['fictive'] && substr($li['timeFrom'],-8)== "00:00:00" && substr($li['timeUntil'],-8)== "23:59:59"){
								?>מטפל נעול  ליום זה<?
							}else if($li['on_break']){
								?>בהפסקה בזמן הטיפול<?
							}else{
								?>משמרת מ <?=substr($li['timeFrom'],11,5)?> עד  <?=substr($li['timeUntil'],11,5)?>	<?
							}
						}else if(!$li['fictive'] && $li['on_break']){
							?>בהפסקה בזמן הטיפול<?
						}else if(!$li['fictive']){
							?>לא קיימת משמרת<?
						}?>
						<?if($li['countOrders']){?> | קיים טיפול מקביל למטפל<?=$li['gender_self']==2? "ת" : "" ?><?}?>
						<?if(!$li['treatmentID'] && !$li['fictive']){?> | ללא הכשרה ל<?=$treatmentName?><?}?>
						</div>
					</div>
				</div>
		<?
		
			}

		break;

		case 'spa_roomID':
			//echo $que2;
			//print_r($treatsInRoom);
			if($roomID){
			$click = "$('#".$act."').val('');$('#".$act."Name').val('--- בחר חדר ---');$('#selectpop').fadeOut('fast');";

			?>
			<div class="single-select-row Allowed" onclick="<?=$click?>"><div>בטל בחירת חדר</div></div>
			<?}
			
			
			foreach($list as $li){
			
				if($li['countOrders']>0 || (!$li['treatValid'] && $treatmentID) || $treatsInRoom[$li['sendID']]>=$li['maxTreatments']){
					$class = 'notAvailable a2';
				}else{
					$class = 'Allowed';
					$click = "$('#".$act."').val(".$li['sendID'].");$('#".$act."Name').val('".str_replace($quot1,$quot2,$li['showName'])."');$('#selectpop').fadeOut('fast');";
				}

				if($li['sendID'] == $roomID){
					$class.=' selected';
				}
			?>
				<div class="single-select-row <?=$class?>" onclick="<?=$click?>">
					<div>
					<?=$li['showName']?>
						<div>
						<?if(!$li['treatValid'] && $treatmentID){?>
							לא ניתן לבצע את הטיפול בחדר זה
						<?}else if($li['countOrders']){?>
							קיימת הזמנה מקבילה על חדר זה
						<?}else{?>
							לחדר משוייכים  <?=$treatsInRoom[$li['sendID']]?: "0"?> טיפולים (עד <?=$li['maxTreatments']?>)
						<?}?>
						</div>
					</div>
				</div>
			<?
						
			}
		break;
		}
	}
?>

</div>
 <?
if(!$result['error']){
	$result['html'] = ob_get_clean();
	$result['success'] = true;
}


?>