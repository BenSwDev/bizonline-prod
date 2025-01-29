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
    // Fetch all treatments
    $allTreats = udb::full_list("SELECT treatmentID, treatmentName FROM treatments WHERE 1 ORDER BY treatmentName ASC");
    // Fetch which treatments the site is already using
    $used = udb::single_column("SELECT DISTINCT treatmentID FROM treatmentsPricesSites WHERE siteID=" . $siteID);
    $used = array_map('intval', $used);

    // Minimal CSS for better readability
    echo '<style>
    .treats-item {
      margin-bottom: 5px;
      font-size: 14px;
    }
    .treats-item label {
      cursor: pointer;
    }
    </style>';

    foreach ($allTreats as $tr) {
        $checked = in_array($tr['treatmentID'], $used) ? 'checked' : '';
        echo '<div class="treats-item">';
        echo '<label>';
        echo '<input type="checkbox" value="'.$tr['treatmentID'].'" '.$checked.'> ';
        echo htmlspecialchars($tr['treatmentName']);
        echo '</label>';
        echo '</div>';
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $treatmentsStr = trim($_POST['treatments'] ?? '');
    $siteID = intval($_POST['siteID'] ?? 0);

    $arr = [];
    if ($treatmentsStr !== '') {
        foreach (explode(',', $treatmentsStr) as $val) {
            $arr[] = intval($val);
        }
        $arr = array_unique($arr);
    }

    // Server-side validation: at least one treatment must be selected
    if (count($arr) === 0) {
        die("ERROR: No treatments selected.");
    }

    $timesList = udb::single_column("SELECT DISTINCT duratuion FROM treatmentsPricesSites WHERE siteID=" . $siteID . " ORDER BY duratuion");
    $existing = udb::single_column("SELECT DISTINCT treatmentID FROM treatmentsPricesSites WHERE siteID=" . $siteID);
    $existing = array_map('intval', $existing);

    // Remove treatments not in new list
    $in = implode(',', $arr);
    udb::query("DELETE FROM treatmentsPricesSites WHERE siteID=" . $siteID . " AND treatmentID NOT IN (" . $in . ")");

    // Add new treatments that were not previously there
    foreach ($arr as $tID) {
        if (!in_array($tID, $existing)) {
            if ($timesList) {
                foreach ($timesList as $d) {
                    $que = [
                        'siteID' => $siteID,
                        'treatmentID' => $tID,
                        'duratuion' => intval($d),
                        'price1' => 0,
                        'price2' => 0,
                        'price3' => 0
                    ];
                    udb::insert('treatmentsPricesSites', $que);
                }
            }
        }
    }

    echo "OK";
    exit;
}
