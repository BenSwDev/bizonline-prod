<?php 
	require_once "auth.php";

	$multiCompound = !$_CURRENT_USER->single_site;

    $sid = 0;

	$que = "SELECT mainPageTitle,mainPageID FROM `MainPages` WHERE mainPageType = 100 AND ifShow = 1";
	$reasons = udb::full_list($que);

        $workers = intval($_POST['data']['workers']);
		$shiftsTable = $workers? '`workShifts`' : '`spaShifts`';

        $masterID = intval($_POST['data']['unitID']);
        $siteID = intval($_POST['data']['sid']);
		$day = implode('-',array_reverse(explode('/',($_POST['data']['startDate'])))); ;
        //echo $_POST['data']['startDate'].PHP_EOL;
        //echo $day.PHP_EOL;
        $all_data = "";
        //print_r($_POST['data']);
		if (isset($_POST['data']['OrderIDS'])) {
			$OrderIDS = $_POST['data']['OrderIDS'];
			if(!$OrderIDS) $OrderIDS = "0";
			$all_data_sql = "SELECT *
							,DATE_FORMAT(timeFrom,'%H:%i') AS h_from
							,DATE_FORMAT(timeUntil,'%H:%i') AS h_to
							FROM ".$shiftsTable." 
							WHERE  masterID = ".$masterID. " AND timeFrom <= '".$day." 23:59:59' AND timeUntil >= '".$day." 00:00:00'
							ORDER BY timeFrom";
			$all_data = udb::full_list($all_data_sql);
			if($workers){
				$isReal = 1;
			}else{
				list($isReal, $gender_self) = udb::single_row("SELECT IF(`workerType` = 'fictive', 0, 1) AS `fic`, gender_self FROM `therapists` WHERE `therapistID` = " . $masterID, UDB_NUMERIC);
			}
        }
        
       	$startDate = $_POST['data']['startDate'];
		$endDate = $_POST['data']['endDate'];

        
    
    
                $siteData = udb::single_row("SELECT `sites`.`cleanGlobal`, `sites`.`checkInHour`, `sites`.`checkOutHour`,`sites`.`siteName`,`sites`.`sendReviews`,  `sites_langs`.`defaultAgr`, `sites_langs`.`agreement1`, `sites_langs`.`agreement2`, `sites_langs`.`agreement3`
                , IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal`
            FROM `sites` INNER JOIN `sites_langs` ON (`sites_langs`.`siteID` = `sites`.`siteID` AND `sites_langs`.`langID` = 1 AND `sites_langs`.`domainID` = 1)
	        WHERE `sites`.`siteID` = '".$siteID."' ");

        $default = udb::key_value("SELECT `siteID`, `defaultAgr` FROM `sites_langs` WHERE `domainID` = 1 AND `langID` = 1 AND `siteID` = '".$siteID."' ");

        $startTime = $siteData['checkInHour'];
        $endTime = $siteData['checkOutHour'];

        
	if(!$order['domainID']) $order['domainID'] = "0";
?>
    
	<div class="create_order <?=$orderType?>" id="create_orderPop">
		<div class="arrow_cls" onclick="$(this).parent().toggleClass('cls')"></div>
		<style>.pdfbtn {display: inline-block;vertical-align: middle;min-width: 160px;font-size: 20px;text-align: center;line-height: 40px;background: #e73219;color: #fff;font-weight: 500;cursor: pointer;border-radius: 3px;margin: 20px 0 0 0;padding: 0 10px;/*width: 40%;*/}</style>
		<div class="container">
			<div class="close" onclick="closeOrderForm()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
			<div class="title mainTitle">	
				<div class="domain-icon" style="background-image:url(<?=$domain_icon[$order['domainID']]?>)"></div>	
				<?php
                                echo $_POST['data']['worker_name'];
                                ?>
			</div>
			<form class="form" id="orderForm" action="" 
                              data-guid="<?=$order['guid']?>" method="post" autocomplete="off" 
                              data-defaultAgr="<?=$siteData['defaultAgr']?>">
                
				<input type="hidden" name="workers" value="<?=$workers?>" class="ignore" />
				<input type="hidden" name="action" value="insertShift" class="ignore" />
				<input type="hidden" name="orderID" value="<?=$order['orderID']?>" id="orderForm-orderID" />
                                <input type="hidden" name="masterID" value="<?=$masterID?>"  />
                                <input type="hidden" name="sid" value="<?=$siteID?>"  />
                                <input type="hidden" name="OrderIDS" value="<?=$OrderIDS?>"  />
                                
                                <input type="hidden" name="time_units" id="idan_time_units" value=""  />
                                
				<input type="hidden" value="<?=$startDate?>" name="fromDate" >
                                    
								
								
							<div class="text_top">
									<div class="the_inline">משמרות</div>
									<div class="the_inline"><?=$startDate?>:</div>
							</div>
							
							<div class="the_shift_zonesss">
									
								<?php
								$typeName[-1] = "הפסקה אונליין";
								$typeName[0] = "הפסקה";
								$typeName[1] = "משמרת";

								$typeClass[-1] = "break online-break";
								$typeClass[0] = "break";
								$typeClass[1] = "";
								if (is_array($all_data)) {
									foreach ($all_data as $all_data_vals) {
										//$all_data_vals['status'] = $all_data_vals['status'] - $all_data_vals['online']; //if online status shows as -1
										?>
								<div class="time_units_row the_res <?=$typeClass[$all_data_vals['status']]?>"> 
									<input type="hidden" name="status[]" value="<?=$all_data_vals['status']?>">								
									<div class="the_remove_but" onclick="$(this).parent().remove();">
										<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M14.59 8L12 10.59 9.41 8 8 9.41 10.59 12 8 14.59 9.41 16 12 13.41 14.59 16 16 14.59 13.41 12 16 9.41 14.59 8zM12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>    
									</div>
									
									<div class="inputWrap half date time">
										<input type="text" value="<?=(substr($all_data_vals['h_from'],0,5))?>" name="startTime[]" data-type="start" class="timePicks readonlymob">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>
										<label for="from">תחילת <?=$typeName[$all_data_vals['status']]?></label>
									</div>
				
									<div class="inputWrap half date time" >
										<input type="text" value="<?=(substr($all_data_vals['h_to'],0,5))?>" name="endTime[]" data-type="end" class="timePicks readonlymob">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>                            
										<label for="from">סוף <?=$typeName[$all_data_vals['status']]?></label>
									</div> 

									<div class="inputWrap break_desc" >
										<input type="text" value="<?=$all_data_vals["orderName"]?>" name="desc[]" placeholder="תאור <?=$typeName[$all_data_vals['status']]?>" class="">
										<label for="from">תאור</label>
									</div> 


								</div>
											<?php
										}
									}
									if (empty($all_data) && $isReal) {
										?>
								<div class="time_units_row the_res">
									<input type="hidden" name="status[]" value="1">
									<div class="the_remove_but" onclick="$(this).parent().remove();">
										<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"></path><path d="M14.59 8L12 10.59 9.41 8 8 9.41 10.59 12 8 14.59 9.41 16 12 13.41 14.59 16 16 14.59 13.41 12 16 9.41 14.59 8zM12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg>
									</div>
									<div class="inputWrap half date time">
										<input type="text" value="10:00" name="startTime[]" data-type="start" class="timePicks readonlymob">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>
										<label for="from">תחילת משמרת</label>
									</div>
									
									<div class="inputWrap half date time" >
										<input type="text" value="15:00" name="endTime[]" data-type="end" class="timePicks readonlymob">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>                            
										<label for="from">סוף משמרת</label>
									</div>     
								</div>
										<?php
									}
									?>    
										
									
										
										
																	
							</div>
							
							<div class="text_bottom">
								<?if($isReal){?>
								<div class="text_bottom_but"  data-start="10:00" data-end="15:00" onclick="more_shifts(this,1);">
								+ משמרת
								</div>
								<?}?>
								<div class="text_bottom_but break"  data-start="10:00" data-end="15:00" onclick="more_shifts(this,0);">
								+ הפסקה
								</div>
								<div class="text_bottom_but break online-break"  data-start="10:00" data-end="15:00" onclick="more_shifts(this,-1);">
								+ הפסקה אונליין
								</div>

						</div>
									

						<div class="statusBtn">
							<?php
							$but_txt = "";
							if ($OrderIDS == 0) {$but_txt = "שמור";} else {$but_txt = "עדכן";}
							?>
							<button type="button" onclick="insertShift(this)" class="inputWrap submit"><?=$but_txt?></button>
						</div>
<?php
    if ($orderID){
        $actions = UserActionLog::getLogForOrder($orderID);
//					$actionsTypes[1]="שמירה";
//					$actionsTypes[2]="שמירה וביטול חתימה";
//					$actionsTypes[3]="ביטול";
//					$actionsTypes[4]="שיחזור";
//					$actionsTypes[5]="שליחה לחתימה";
//					$actionsTypes[6]="יצירת קשר";
//					$actions[]=array("type"=>1,"user"=>2,"actionTime"=>"2020-10-08 16:12:10");
//					$actions[]=array("type"=>2,"user"=>1,"actionTime"=>"2020-10-08 16:15:10");
//					$actions[]=array("type"=>3,"user"=>1,"actionTime"=>"2020-10-08 16:16:10");
//					$users[1]="רועי פלומבו";
//					$users[2]="סרגי פלדשר";
?>
					<div class="order-actions">
<?php
        foreach($actions as $action){
            if (strcasecmp('order', substr($action['actionType'], 0, 5)))
                continue;

            $time = substr($action['created'], 11, 5) . ' ' . db2date(substr($action['created'], 0, 10));
?>
					<div class="action-line">
						<div class="action-type"><?=UserActionLog::actionName($action['actionType'])?></div>
						<div class="action-user"><?=$_CURRENT_USER->user($action['buserID'])?></div>
						<div class="action-time"><?=$time?></div>
					</div>
<?php
        }
?>
				</div>
<?php
    }
?>
			</form>
			
		</div>
	</div>
