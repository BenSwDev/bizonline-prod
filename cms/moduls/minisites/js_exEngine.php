<?php
include "../../bin/system.php";

header('Content-Type: application/json');

switch($_REQUEST['act']){
	case 'list':
		$engine = inputStr($_REQUEST['id']);
		$sid    = intval($_REQUEST['sid']);
		
		$que = "SELECT `manual`, `updateTime` FROM `searchManager_engines` WHERE `index` = '" . $engine . "'";
        $man = udb::single_row($que) or die(json_encode(array('error' => 'Unknown engine ID')));

		$que = "SELECT `externalEngine`, `externalID` FROM `sites` WHERE `siteID` = " . $sid;
		$exist = udb::single_row($que);

        strcmp($engine, $exist['externalEngine']) && $exist['externalID'] = '';

		if (intval($man['manual']))
            $exSites = '<input type="text" name="externalID" value="' . $exist['externalID'] . '" style="direction:ltr; text-align:right" />';
		else {
			$exSites = array('<option value="">- - - - - - - - - - - - - - -</option>');

			$que = "SELECT `siteID`,`siteName` FROM `searchManager_sites` WHERE `engine` = '" . $engine . "' ORDER BY `siteName`";
			foreach(udb::key_value($que, 0, 1) as $key => $val)
				$exSites[] = '<option value="' . $key . '" ' . (strcmp($key, $exist['externalID']) ? '' : 'selected="selected"') . '>' . $val . '</option>';

			$exSites = '<select name="externalID">' . implode('', $exSites) . '</select>';
		}

		echo json_encode(array('html' => $exSites), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	break;

	default:
		echo json_encode(array('error' => 'Unknown operation code'));
}
