<?php
require_once "auth.php";
header('Content-Type: text/html; charset=utf-8');

if (!$_CURRENT_USER->select_site()) {
    die("No site selected");
}

$siteID = intval($_GET['siteID'] ?? $_POST['siteID'] ?? 0);
$canSave = (($_CURRENT_USER->access() == TfusaUser::ACCESS_SUPER) || ($_CURRENT_USER->userType == 0));
if (!$canSave) {
    die("No permission");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Generate a list of 30..120 in steps of 5
    $allTimes = [];
    for ($t = 30; $t <= 120; $t += 5) {
        $allTimes[] = $t;
    }

    // Fetch which times are already used for this site
    $used = udb::single_column("SELECT DISTINCT duratuion FROM treatmentsPricesSites WHERE siteID=" . $siteID . " ORDER BY duratuion");
    $used = array_map('intval', $used);

    // Minimal CSS for better readability
    echo '<style>
    .time-item {
      margin-bottom: 5px;
      font-size: 14px;
    }
    .time-item label {
      cursor: pointer;
    }
    </style>';

    foreach ($allTimes as $mt) {
        $checked = in_array($mt, $used) ? 'checked' : '';
        echo '<div class="time-item">';
        echo '<label>';
        echo '<input type="checkbox" value="'.$mt.'" '.$checked.'> ';
        echo $mt.' דקות';
        echo '</label>';
        echo '</div>';
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $timesStr = trim($_POST['times'] ?? '');
    $siteID = intval($_POST['siteID'] ?? 0);

    $arr = [];
    if ($timesStr !== '') {
        foreach (explode(',', $timesStr) as $val) {
            $arr[] = intval($val);
        }
        $arr = array_unique($arr);
    }

    // Server-side validation: at least one time must be selected
    if (count($arr) === 0) {
        die("ERROR: No times selected.");
    }

    $existing = udb::single_column("SELECT DISTINCT duratuion FROM treatmentsPricesSites WHERE siteID=" . $siteID);
    $existing = array_map('intval', $existing);

    // Remove times not in new list
    $in = implode(',', $arr);
    udb::query("DELETE FROM treatmentsPricesSites WHERE siteID=" . $siteID . " AND duratuion NOT IN (" . $in . ")");

    // Add new times that are not already present
    $treatmentsUsed = udb::single_column("SELECT DISTINCT treatmentID FROM treatmentsPricesSites WHERE siteID=" . $siteID);

    foreach ($arr as $d) {
        if (!in_array($d, $existing)) {
            // Insert one row for each treatment
            foreach ($treatmentsUsed as $tID) {
                $que = [
                    'siteID' => $siteID,
                    'treatmentID' => $tID,
                    'duratuion' => $d,
                    'price1' => 0,
                    'price2' => 0,
                    'price3' => 0
                ];
                udb::insert('treatmentsPricesSites', $que);
            }
        }
    }

    echo "OK";
    exit;
}
