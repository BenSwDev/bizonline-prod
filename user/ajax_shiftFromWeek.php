<?php 
	require_once "auth.php";

	$multiCompound = !$_CURRENT_USER->single_site;

	$dayName = array ("יום א","יום ב","יום ג","יום ד","יום ה","שישי","שבת");
	$dayNameShort = array ("א","ב","ג","ד","ה","ו","ש");

    $sid = 0;
		
		//exit;

        $workers = intval($_POST['data']['workers']);
		$shiftsTable = $workers? '`workShifts`' : '`spaShifts`';

        $masterID = intval($_POST['data']['unitID']);
        $siteID = intval($_POST['data']['sid']);
		$day = implode('/',array_reverse(explode('/',($_POST['data']['startDate'])))); ;
		$dayofweek = date('w',strtotime($day));
		$startweek = date('Y-m-d', strtotime($day." -".($dayofweek)." days")) ;
		$endweek = date('Y-m-d', strtotime($day." +".(6-$dayofweek)." days")) ;

		/*echo $day.PHP_EOL;
		echo $dayofweek.PHP_EOL;
		echo $startweek.PHP_EOL;
		echo $endweek.PHP_EOL;*/

        $all_data = "";
        //print_r($_POST['data']);
        
        $OrderIDS = $_POST['data']['OrderIDS'];
        if(!$OrderIDS) $OrderIDS = "0";
        $all_data_sql = "SELECT *
                        ,DATE_FORMAT(timeFrom,'%w') AS day_date
                        ,DATE_FORMAT(timeFrom,'%H:%i') AS h_from
                        ,DATE_FORMAT(timeUntil,'%H:%i') AS h_to
                        FROM ".$shiftsTable." 
                        WHERE  masterID = ".$_POST['data']['unitID']. " AND timeFrom <= '".$endweek." 23:59:59' AND timeUntil >= '".$startweek." 00:00:00'
						ORDER BY timeFrom";
        $all_data = udb::full_list($all_data_sql);
        //echo $all_data_sql;
		//print_r($all_data);

        
        
        
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
				<input type="hidden" name="startweek" value="<?=$startweek?>"  />

				<input type="hidden" name="time_units" id="idan_time_units" value=""  />

				
                                    
					<?for($i=0; $i<7;$i++){
						$date_day =  date('Y-m-d', strtotime($startweek." +".($i)." days")) ;
						?>        
				<div class="day-wrap" id="day-wrap<?=$i?>">
					<div class="day-title">
						<div class="the_inline"><?=$dayName[$i]?> <?= date('d.m.y', strtotime($startweek." +".($i)." days")) ;?> : </div>
						<div class="text_bottom the_inline">
							<div class="text_bottom_but plus" data-start="10:00" data-end="15:00" data-date='<?=$date_day?>' onclick="more_shifts_week(this,1);"><span>+</span>משמרת</div>
						</div>
						<div class="text_bottom the_inline">						
							<div class="text_bottom_but plus break" data-start="14:00" data-end="15:00" data-date='<?=$date_day?>' onclick="more_shifts_week(this,0);"><span>+</span>הפסקה</div>
						</div>
						<div class="text_bottom the_inline">						
							<div class="text_bottom_but plus break break-online" data-start="14:00" data-end="15:00" data-date='<?=$date_day?>' onclick="more_shifts_week(this,-1);"><span>+</span>ה.אונליין</div>
						</div>
						<div class="text_bottom the_inline">							
							<div class="text_bottom_but" data-dayname='<?=$dayName[$i]?>' data-day='<?=$i?>' onclick="duplicate_shifts(this);"><span class='dup'></span>שכפול משמרות ליום אחר</div>
						</div>
					</div>

					<div class="the_shift_zonesss" data-date="<?=$date_day?>" data-id="<?=$i?>" id='day<?=$i?>'>
						
						<?php
						//print_r($all_data);
						$typeName[-1] = "הפסקה אונליין";
						$typeName[0] = "הפסקה";
						$typeName[1] = "משמרת";

						$typeClass[-1] = "break online-break";
						$typeClass[0] = "break";
						$typeClass[1] = "";

						if (is_array($all_data)) {
							foreach ($all_data as $all_data_vals) {
								if($all_data_vals['day_date']==$i){
									//$all_data_vals['status'] = $all_data_vals['status'] - $all_data_vals['online']; //if online status shows as -1
								
								?>
						<div class="time_units_row the_res <?=$typeClass[$all_data_vals['status']]?>"> 
							<input type="hidden" data-type="status" name="status[<?=$date_day?>][]" value="<?=$all_data_vals['status']?>">								
							<div class="the_remove_but" onclick="$(this).parent().remove();">
								<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M14.59 8L12 10.59 9.41 8 8 9.41 10.59 12 8 14.59 9.41 16 12 13.41 14.59 16 16 14.59 13.41 12 16 9.41 14.59 8zM12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>    
							</div>
							
							<div class="inputWrap half date time">
								<input type="text" value="<?=(substr($all_data_vals['h_from'],0,5))?>" name="startTime[<?=$date_day?>][]"  data-type="start" class="timePicks readonlymob">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>
								<label for="from">תחילת <?=$typeName[$all_data_vals['status']]?></label>
							</div>
		
							<div class="inputWrap half date time" >
								<input type="text" value="<?=(substr($all_data_vals['h_to'],0,5))?>" name="endTime[<?=$date_day?>][]" data-type="end" class="timePicks readonlymob">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>                            
								<label for="from">סוף <?=$typeName[$all_data_vals['status']]?></label>
							</div> 

							<div class="inputWrap break_desc" >
								<input type="text" value="<?=$all_data_vals["orderName"]?>" data-type="desc" name="desc[<?=$date_day?>][]" placeholder="תאור <?=$typeName[$all_data_vals['status']]?>" class="">
								<label for="from">תאור</label>
							</div> 


						</div>
					<?php
								}
							}
						}
						
						?>    
							
						
							
							
														
					</div>
				</div>
					<?}?>

					
								

				<div class="statusBtn sticky-bottom">
					<?
						$prev_week = date("d/m/Y", strtotime($startweek." - 7 days" )) ;
						$next_week = date("d/m/Y", strtotime($startweek." + 7 days" )) ;
						?>
					<style>
					.save-btns{display:flex}
					.create_order .inputWrap.submit.small{width:30%;font-size:14px;margin:0 2px;height:60px;background:#f37867}
					.create_order .inputWrap.submit.big{width:40%;}
					</style>
					<script>
						var prev_week = {
							unitID:<?=$masterID?>
							,startDate:'<?=$prev_week?>'
							,worker_name:'<?=$_POST['data']['worker_name']?>'
							,sid:<?=$siteID?>
						};
						var next_week = {
							unitID:<?=$masterID?>
							,startDate:'<?=$next_week?>'
							,worker_name:'<?=$_POST['data']['worker_name']?>'
							,sid:<?=$siteID?>
						};
					</script>
					<div class="save-btns">
						<button type="button" onclick="insertShift_week(this,prev_week)" class="inputWrap submit small">שמור ועבור<br>לשבוע קודם</button>
						<button type="button" onclick="insertShift_week(this)" class="inputWrap submit big">שמור</button>
						<button type="button" onclick="insertShift_week(this,next_week)" class="inputWrap submit small">שמור ועבור<br>לשבוע הבא</button>
					</div>

					<?/*
					<button type="button" onclick="insertShift_week(this)" class="inputWrap submit">שמור</button>
					*/?>
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
			<div class="popup absolute" id="dupshifts">
				<input name='dupform' type='hidden' id="dupfrom">
				<div class="popup_container">
					<div class="close" onclick="$(this).closest('.popup').hide()"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M14.59 8L12 10.59 9.41 8 8 9.41 10.59 12 8 14.59 9.41 16 12 13.41 14.59 16 16 14.59 13.41 12 16 9.41 14.59 8zM12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg></div>
					<div style="margin:30px 10px;text-align:center">שכפל משמרות מ-<b class="dupdayname"></b> לימים נבחרים</div>
					<div class="dupdays">
						<?for($i=0; $i<7;$i++){?>
							<div class="checkwrap" data-day='<?=$i?>' id="dupday<?=$i?>">								
								<div><input class='dupcheck' id="dupcheck<?=$i?>" type="checkbox"></div>
								<span><?=$dayNameShort[$i]?></span>
							</div>
						<?}?>
					</div>
					<div class="dupbuttons">
						<?/*<div class="dupbtn" onclick="dupbtn(1)">הוסף משמרות מ-<b class="dupdayname"></b> לימים נבחרים</div>*/?>
						<div class="dupbtn" onclick="dupbtn(2)">דרוס משמרות בימים נבחרים<br>והוסף משמרות מ-<b class="dupdayname"></b> לימים נבחרים</div>
					</div>
							
				</div>
			</div>
		</div>
	</div>
