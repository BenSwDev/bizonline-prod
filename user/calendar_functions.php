<?
function setCalendarOrders($calendarOrders,$showDays,$viewtype,$caltype){
	global $domain_icon;
	//חותך הזמנות לפי שבוע במצב חודשי, מקצר הזמנות שחורגות מטווח התאריכים בתצוגה

	if($viewtype == 1){//CASE PER MONTH
		foreach($calendarOrders  as $k => $m){
			$i = 1;
			while($i < count($showDays)){

				//NEEDS TO ALSO CUT EACH ORDER IN THE END IF TO LONG
				if(strtotime($m['timeFromCal']) < strtotime($showDays[$i]) && $i==1 && $m['allDay']!=1){
					$calendarOrders[$k]['timeFromCal'] = date("Y-m-d 00:00",strtotime(strtotime($showDays[1])));
				}
				if(strtotime($m['timeFromCal'])<strtotime($showDays[$i]) && strtotime($m['timeUntilCal']) >= strtotime($showDays[$i]) && $m['allDay']!=1){
					$m['timeFromCal'] = date("Y-m-d 00:00:00",strtotime($showDays[$i]));
					$calendarOrders[] = $m;
				}
				$i+=7;

			}

		}
	}else if($viewtype == 2){ //CASE PER HOUR
		$date_start = date("Y-m-d 00:00:00",strtotime($date));
		$date_end = date("Y-m-d 23:59:59",strtotime($date));
		
		foreach($calendarOrders  as $k => $m){
			if(strtotime($m['timeFromCal'])<strtotime($date_start) && $m['allDay']!=1){
				$calendarOrders[$k]['timeFromCal'] = date("Y-m-d 23:00:00",strtotime($date. "-1 day"));
			}
			//echo strtotime($m['timeFromCal'])." - ".strtotime($date_start)." - | -  ".$m['timeFromCal']." - ".$date_start."<br><br>";
			if(strtotime($m['timeUntilCal'])>strtotime($date_end) && $m['allDay']!=1){
				
				$calendarOrders[$k]['timeUntilCal'] = date("Y-m-d 23:59:59",strtotime(($date)));
				$calendarOrders[$k]['this_end'] = "this";
				//echo "this_end";
			}
			//echo PHP_EOL;
		}
	}else{ //CASE REGULAR
		foreach($calendarOrders  as $k => $m){
			if(strtotime($m['timeFromCal'])<strtotime($showDays[1]) && $m['allDay']!=1){
				$calendarOrders[$k]['timeFromCal'] = date("Y-m-d H:i",strtotime($showDays[1]."-1 day"));
			}
			
			if(strtotime($m['timeUntilCal'])>strtotime($showDays[count($showDays)]) && $m['allDay']!=1){
				$calendarOrders[$k]['timeUntilCal'] = date("Y-m-d 23:59:59",strtotime($showDays[count($showDays)]));
			}
		}
	}

	foreach($calendarOrders  as $k => $m){	
		if($viewtype == 2){	
			$place_col = $m["timeFromCal"];
		}else{		
			
			$place_col = substr($m["timeFromCal"],0,10);
		}
		if($caltype == 2){	
		$place_row = $m["unitID"]?: "0";
		}else{
		$place_row = $m["therapistID"]?: "0";
		}

		
		$calendarOrders[$k]["col"]= $place_col;
		$calendarOrders[$k]["row"]= $place_row;
		$time1 = strtotime($m["timeFromCal"]);
		$time2 = strtotime($m['timeUntilCal']);
		//echo $time1." - ".$time2;
		if($viewtype == 2){
			$calendarOrders[$k]["width"] = round(((($time2 - $time1) / 60)/60) * 100);
			$calendarOrders[$k]["right"] = round((date("i",strtotime($m['timeFromCal']))/60) *100);
		}else{		
			$calendarOrders[$k]["width"] = round(((($time2 - $time1) / 3600)/24) * 100);
			$calendarOrders[$k]["right"] = round((date("H",strtotime($m['timeFromCal']))/24) *100);
		}
		$calendarOrders[$k]["icon"]= $domain_icon[$m["domainID"]];	
	}

	return $calendarOrders;
}

?>