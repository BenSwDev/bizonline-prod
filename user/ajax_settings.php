<?php 
require_once "auth.php";

/**
 * @var TfusaUser $_CURRENT_USER
 */

if(intval($_POST['type']) ==1){

	$exists = udb::single_value("SELECT sendReviews FROM sites WHERE siteID =".intval($_POST['id']));
	$change = $exists==1? 0 : 1;
	  $siteData = [
            'sendReviews' => $change
		];
    udb::update('sites', $siteData, 'siteID = '.intval($_POST['id']));
	echo $change; 
    exit;
}

if(intval($_POST['type']) ==2){

	$exists = udb::single_value("SELECT publishReviews FROM sites WHERE siteID =".intval($_POST['id']));
	$change = $exists==1? 0 : 1;
	  $siteData = [
            'publishReviews' => $change
		];
    udb::update('sites', $siteData, 'siteID = '.intval($_POST['id']));
	echo $change; 
    exit;
}

function hdate($date){
    return typemap(implode('-', array_reverse(explode('.', $date))), 'date');
}

function decc($num){
    return round(floatval($num), 2);
}

$result = new JsonResult(['status' => 99]);

try {
    switch($_POST['act']){
		case 'setReportRange':
			$_SESSION[$_POST['type']] = intval($_POST['val']);
			break;

		case 'calendarSettings':            
			$sid  = intval($_POST['sid']);
			$c_val = intval($_POST['valc']);
            $changeTo = typemap($_POST['val'], 'int');
			$current_val = udb::single_value('SELECT calendarSettings FROM sites WHERE siteID ='.$sid);
			$current_val =  floor($current_val/$c_val)%10;
			if($current_val && !$changeTo)
				$new_val = " - ".$c_val;
			elseif(!$current_val && $changeTo)
				$new_val = " + ".$c_val;

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

			//throw new Exception("changeto ".$changeTo." |  c_val ". $c_val." | newval ". $current_val);
            
			if($new_val){
				udb::query('Update sites SET calendarSettings = calendarSettings'.$new_val.' WHERE `siteID` = ' . $sid);
			}
            break;


        case 'hideUnfilled':
            $sid  = intval($_POST['sid']);
            $hideUnfilled = typemap($_POST['val'], 'int');

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);
            
            udb::update('sites', ['hideUnfilled' => $hideUnfilled], '`siteID` = ' . $sid);
            break;

		case 'autoHidePrice':
			$sid  = intval($_POST['sid']);
			$autoHidePrice = typemap($_POST['val'], 'int');

			if (!$sid)
				throw new Exception('No siteID');
			if (!$_CURRENT_USER->has($sid))
				throw new Exception("Access denied to site #" . $sid);
			
			udb::update('sites', ['autoHidePrice' => $autoHidePrice], '`siteID` = ' . $sid);
			break;
		

		case 'enableReminders':
			$sid  = intval($_POST['sid']);
			$enableReminders = typemap($_POST['val'], 'int');

			if (!$sid)
				throw new Exception('No siteID');
			if (!$_CURRENT_USER->has($sid))
				throw new Exception("Access denied to site #" . $sid);
			
			udb::update('sites', ['enableReminders' => $enableReminders], '`siteID` = ' . $sid);
			break;
		
		
		case 'CancelCondSpa':
			$sid  = intval($_POST['sid']);
			$input = typemap($_POST, [
				'daysCancel'  => ['int' => 'int'],
				'typeCancel'  => ['int' => 'int'],
				'costCancel'  => ['int' => 'int'],
				'allowCancel' => 'int' ,
				'cancelType' => 'int'
			]);

			rsort($input['daysCancel']);

			$cancelJson = ['allowCancel' => $input['allowCancel'],'cancelType'=> $input['cancelType']];
			foreach($input['daysCancel'] as $key => $days){
				if ($days)
					$cancelJson[$days] = ($input['typeCancel'][$key] == 1) ? ($input['costCancel'][$key] ?: 0) : round(($input['costCancel'][$key] ?: 0) / 100, 2);
			}

			$CancelCondSpa = json_encode($cancelJson,JSON_NUMERIC_CHECK);
			

			if (!$sid)
				throw new Exception('No siteID');
			if (!$_CURRENT_USER->has($sid))
				throw new Exception("Access denied to site #" . $sid);
			
			udb::update('sites', ['CancelCondSpa' => $CancelCondSpa], '`siteID` = ' . $sid);
			break;

		case 'sendReminderHour':
			$sid  = intval($_POST['sid']);
			$enableReminders = typemap($_POST['val'], 'int');
			$enableReminders = sprintf("%02d",$enableReminders).":00:00";

			if (!$sid)
				throw new Exception('No siteID');
			if (!$_CURRENT_USER->has($sid))
				throw new Exception("Access denied to site #" . $sid);
			
			udb::update('sites', ['sendReminderHour' => $enableReminders], '`siteID` = ' . $sid);
			break;
		
		case 'sourceRequired':
            $sid  = intval($_POST['sid']);
            $sourceRequired = typemap($_POST['val'], 'int');

            if (!$sid)
                throw new Exception('No siteID');
			if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);
			
            udb::update('sites', ['sourceRequired' => $sourceRequired], '`siteID` = ' . $sid);
            break;

        case 'roomRequired':
            $sid  = intval($_POST['sid']);
            $roomRequired = intval($_POST['val']) ? 1 : 0;

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

            udb::update('sites', ['roomRequired' => $roomRequired], '`siteID` = ' . $sid);
            break;

        case 'addressRequired':
            $sid  = intval($_POST['sid']);
            $addressRequired = typemap($_POST['val'], 'int');

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);
            
            udb::update('sites', ['addressRequired' => $addressRequired], '`siteID` = ' . $sid);
            break;

		 case 'blockDelete':
            $sid  = intval($_POST['sid']);
            $blockDelete = typemap($_POST['val'], 'int');

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);
            
            udb::update('sites', ['blockDelete' => $blockDelete], '`siteID` = ' . $sid);
            break;

		 case 'blockAutoSend':
            $sid  = intval($_POST['sid']);
            $blockAutoSend = typemap($_POST['val'], 'int');

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);
            
            udb::update('sites', ['blockAutoSend' => $blockAutoSend], '`siteID` = ' . $sid);
            break;
		
		
		case 'baseSalaryType':
            $sid  = intval($_POST['sid']);
            $type = typemap($_POST['val'], 'string');

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

            $json = udb::single_value("SELECT `salaryDefault` FROM `sites` WHERE `siteID` = " . $sid);
            $json = $json ? json_decode($json, true) : ['activeType' => '', 'all' => []];

            $json['activeType'] = $type;

            udb::update('sites', ['salaryDefault' => json_encode($json)], '`siteID` = ' . $sid);
            break;

        case 'baseSalaryTypeNew':
            $sid  = intval($_POST['sid']);
            $type = typemap($_POST['val'], 'string');

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

            (new SalarySite($sid))->change_salary($type);
            break;

        case 'baseSalary':
            $today     = date('Y-m-d');
            $nextMonth = date('Y-m-01', strtotime('next month'));

            $input = typemap($_POST, [
                'sid'      => 'int',
                'minute'   => ['wday' => 'decc', 'wend' => 'decc'],
                'percent'  => ['wday' => 'decc', 'wend' => 'decc'],
                'sminute'  => ['wday' => 'hdate', 'wend' => 'hdate'],
                'spercent' => ['wday' => 'hdate', 'wend' => 'hdate']
            ]);

            if (!$input['sid'])
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($input['sid']))
                throw new Exception("Access denied to site #" . $input['sid']);

            $json = udb::single_value("SELECT `salaryDefault` FROM `sites` WHERE `siteID` = " . $input['sid']);
            $json = $json ? json_decode($json, true) : ['activeType' => '', 'all' => []];

            $rate = $ii = $date = 0;      // slight hack

            foreach(['minute', 'percent'] as $i){
                if (!$input[$i])
                    continue;

                $ii = $i;

                foreach($input[$i] as $day => $rate){
                    $date = $input['s' . $i][$day] ?? $nextMonth;

                    udb::query("DELETE FROM `salaryLog` WHERE `targetType` = 'site' AND `targetID` = " . $input['sid'] . " AND `salaryType` = '" . $i . "' AND `salaryDay` = '" . $day . "'");

                    udb::insert('salaryLog', [
                        'targetType' => 'site',
                        'targetID'   => $input['sid'],
                        'salaryType' => $i,
                        'salaryDay'  => $day,
                        'salaryRate' => round($rate, 2),
                        'startFrom'  => $date
                    ]);

                    if (strcmp($date, $today) <= 0)
                        $json['all'][$i][$day] = round($rate, 2);
                }
            }

            udb::update('sites', ['salaryDefault' => json_encode($json)], '`siteID` = ' . $input['sid']);

            $result['amount'] = round($rate, 2);
            $result['btn'] = ($ii == 'minute') ? '<div>ישתנה ל-₪' . $rate . '</div><div>החל מ-' . db2date($date, '.', 2) . '</div>' : '<div>ישתנה ל-' . $rate . '%</div><div>החל מ-' . db2date($date, '.', 2) . '</div>';
            $result['class'] = (strcmp($date, $today) <= 0) ? 'edit' : 'changed';
            break;

        case 'baseSalaryNew':
            $today     = date('Y-m-d');
            $nextMonth = date('Y-m-01', strtotime('next month'));

            $input = typemap($_POST, [
                'sid'      => 'int',
                'minute'   => ['wday' => 'decc', 'wend' => 'decc'],
                'percent'  => ['wday' => 'decc', 'wend' => 'decc'],
                'sminute'  => ['wday' => 'hdate', 'wend' => 'hdate'],
                'spercent' => ['wday' => 'hdate', 'wend' => 'hdate']
            ]);

            if (!$input['sid'])
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($input['sid']))
                throw new Exception("Access denied to site #" . $input['sid']);

            if (count($input['percent'] ?? []) && count($input['spercent'] ?? []))
                $type = 'percent';
            elseif (count($input['minute'] ?? []) && count($input['sminute'] ?? []))
                $type = 'minute';
            else
                throw new Exception("Illegal type for rate change");

            $rate = reset($input[$type]);
            $day  = key($input[$type]);
            $date = reset($input['s' . $type]);

            $salary = new SalarySite($input['sid']);

            $last = $salary->change_rate($type, $day, $date, $rate)->get_last_salary();
            $curr = $salary->get_day_salary($today, $type);

            $result['amount'] = (strcmp($date, $today) <= 0) ? round($rate, 2) : ($day == 'wend' ? $curr->rateWeekend : $curr->rateRegular);
            $result['btn'] = ($type == 'minute') ? '<div>ישתנה ל-₪' . $rate . '</div><div>החל מ-' . db2date($date, '.', 2) . '</div>' : '<div>ישתנה ל-' . $rate . '%</div><div>החל מ-' . db2date($date, '.', 2) . '</div>';
            $result['class'] = (strcmp($date, $today) <= 0) ? 'edit' : 'changed';
            break;

        case 'masterSalaryType':
            $tid  = intval($_POST['tid']);
            $type = typemap($_POST['val'], 'string');

            if (!$tid)
                throw new Exception('No therapistID');

            $sid = udb::single_value("SELECT `siteID` FROM `therapists` WHERE `therapistID` = " . $tid);

            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

            $json = udb::single_value("SELECT `salary` FROM `therapists` WHERE `therapistID` = " . $tid);
            $json = $json ? json_decode($json, true) : ['activeType' => 'default', 'all' => []];

            $json['activeType'] = $type;

            udb::update('therapists', ['salary' => json_encode($json)], '`therapistID` = ' . $sid);
            break;

        case 'masterSalaryTypeNew':
            $tid  = intval($_POST['tid']);
            $type = typemap($_POST['val'], 'string');

            if (!$tid)
                throw new Exception('No therapistID');

            $sid = udb::single_value("SELECT `siteID` FROM `therapists` WHERE `therapistID` = " . $tid);
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

            (new SalaryMaster($tid))->change_salary($type);
            break;

        case 'masterSalary':
            $today     = date('Y-m-d');
            $nextMonth = date('Y-m-01', strtotime('next month'));

            $input = typemap($_POST, [
                'tid'      => 'int',
                'minute'   => ['wday' => 'decc', 'wend' => 'decc'],
                'percent'  => ['wday' => 'decc', 'wend' => 'decc'],
                'sminute'  => ['wday' => 'hdate', 'wend' => 'hdate'],
                'spercent' => ['wday' => 'hdate', 'wend' => 'hdate']
            ]);

            if (!$input['tid'])
                throw new Exception('No therapistID');

            $sid = udb::single_value("SELECT `siteID` FROM `therapists` WHERE `therapistID` = " . $input['tid']);

            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

            $json = udb::single_value("SELECT `salary` FROM `therapists` WHERE `therapistID` = " . $input['tid']);
            $json = $json ? json_decode($json, true) : ['activeType' => 'default', 'all' => []];

            $rate = $ii = $date = 0;      // slight hack

            foreach(['minute', 'percent'] as $i){
                if (!$input[$i])
                    continue;

                $ii = $i;

                foreach($input[$i] as $day => $rate){
                    $date = $input['s' . $i][$day] ?? $nextMonth;

                    udb::query("DELETE FROM `salaryLog` WHERE `targetType` = 'therapist' AND `targetID` = " . $input['tid'] . " AND `salaryType` = '" . $i . "' AND `salaryDay` = '" . $day . "'");

                    udb::insert('salaryLog', [
                        'targetType' => 'therapist',
                        'targetID'   => $input['tid'],
                        'salaryType' => $i,
                        'salaryDay'  => $day,
                        'salaryRate' => round($rate, 2),
                        'startFrom'  => $date
                    ]);

                    if (strcmp($date, $today) <= 0)
                        $json['all'][$i][$day] = round($rate, 2);
                }
            }

            udb::update('therapists', ['salary' => json_encode($json)], '`therapistID` = ' . $input['tid']);

            $result['amount'] = round($rate, 2);
            $result['btn'] = ($ii == 'minute') ? '<div>ישתנה ל-₪' . $rate . '</div><div>החל מ-' . db2date($date, '.', 2) . '</div>' : '<div>ישתנה ל-' . $rate . '%</div><div>החל מ-' . db2date($date, '.', 2) . '</div>';
            $result['class'] = (strcmp($date, $today) <= 0) ? 'edit' : 'changed';
            break;

        case 'masterSalaryNew':
            $today     = date('Y-m-d');
            $nextMonth = date('Y-m-01', strtotime('next month'));

            $input = typemap($_POST, [
                'tid'      => 'int',
                'minute'   => ['wday' => 'decc', 'wend' => 'decc'],
                'percent'  => ['wday' => 'decc', 'wend' => 'decc'],
                'sminute'  => ['wday' => 'hdate', 'wend' => 'hdate'],
                'spercent' => ['wday' => 'hdate', 'wend' => 'hdate']
            ]);

            if (!$input['tid'])
                throw new Exception('No therapistID');

            $sid = udb::single_value("SELECT `siteID` FROM `therapists` WHERE `therapistID` = " . $input['tid']);

            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

            if (count($input['percent'] ?? []) && count($input['spercent'] ?? []))
                $type = 'percent';
            elseif (count($input['minute'] ?? []) && count($input['sminute'] ?? []))
                $type = 'minute';
            else
                throw new Exception("Illegal type for rate change");

            $rate = reset($input[$type]);
            $day  = key($input[$type]);
            $date = reset($input['s' . $type]);

            $salary = new SalaryMaster($input['tid']);

            $last = $salary->change_rate($type, $day, $date, $rate)->get_last_salary();
            $curr = $salary->get_day_salary($today, $type);

            $result['amount'] = (strcmp($date, $today) <= 0) ? round($rate, 2) : ($day == 'wend' ? $curr->rateWeekend : $curr->rateRegular);
            $result['btn'] = ($type == 'minute') ? '<div>ישתנה ל-₪' . $rate . '</div><div>החל מ-' . db2date($date, '.', 2) . '</div>' : '<div>ישתנה ל-' . $rate . '%</div><div>החל מ-' . db2date($date, '.', 2) . '</div>';
            $result['class'] = (strcmp($date, $today) <= 0) ? 'edit' : 'changed';
            break;


        case 'bookBefore':
            $sid  = intval($_POST['sid']);
            $bookBefore = typemap($_POST['val'], 'int');

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

            $bookBefore = max(0, $bookBefore);

            udb::update('sites', ['bookBefore' => $bookBefore], '`siteID` = ' . $sid);
            break;

        case 'exactIncome':
            $sid  = intval($_POST['sid']);
            $exactIncome = typemap($_POST['val'], 'int');

            if (!$sid)
                throw new Exception('No siteID');
            if (!$_CURRENT_USER->has($sid))
                throw new Exception("Access denied to site #" . $sid);

            $exactIncome = min(1, max(0, $exactIncome));

            udb::update('sites', ['showExactIncome' => $exactIncome], '`siteID` = ' . $sid);
            break;
    }

    $result['status'] = 0;
}
catch (Exception $e){
    $result['error']  = $e->getMessage();
    $result['status'] = 99;
}
