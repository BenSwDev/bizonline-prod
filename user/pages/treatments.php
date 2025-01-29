<?php
/**
 * This file allows authorized users to view and configure spa treatments and prices.
 * It updates waiting times and treatment price data, then optionally sends pricing
 * to an external system via SpaPlusRelay.
 *
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()) {
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$sid = $_CURRENT_USER->active_site() ?: 0;

// Access check
$canSave = ($_CURRENT_USER->access() == TfusaUser::ACCESS_SUPER) || ($_CURRENT_USER->userType == 0);

// Check if there are no treatments/times for this site. If none, insert one generic row:
$countExisting = udb::single_value("SELECT COUNT(*) FROM treatmentsPricesSites WHERE siteID=" . intval($sid));
if ($countExisting == 0 && $canSave) {
    // Insert a default row as requested:
    $query = "INSERT INTO treatmentsPricesSites 
              SET siteID='" . intval($sid) . "', 
                  treatmentID='58', 
                  duratuion='30',
                  price1='0', 
                  price2='0', 
                  price3='0',
                  special1='', 
                  special2='', 
                  special3=''";
    udb::query($query);
}

// Handling POST requests when authorized to save
if ('POST' == $_SERVER['REQUEST_METHOD'] && $canSave) {
    udb::update('sites', ['waitingTime' => max(0, intval($_POST['timeout']))], '`siteID` = ' . $sid);

    $data = typemap($_POST, [
        'tprice'  => ['int'=>['int'=>'numeric']],
        'tprice2' => ['int'=>['int'=>'numeric']],
        'tprice3' => ['int'=>['int'=>'numeric']]
    ]);

    udb::query("DELETE FROM `treatmentsPricesSites` WHERE `siteID` = " . $sid);

    foreach ($data['tprice'] as $t => $treats) {
        foreach ($treats as $time => $price1) {
            if (strlen($price1)) {
                $que = [
                    'siteID'      => $sid,
                    'treatmentID' => $t,
                    'duratuion'   => $time,
                    'price1'      => $price1 ?: 0,
                    'price2'      => $data['tprice2'][$t][$time] ?: 0,
                    'price3'      => $data['tprice3'][$t][$time] ?: 0,
                ];
                udb::insert('treatmentsPricesSites', $que, true);
            }
        }
    }

    try {
        $relay = new SpaPlusRelay($sid);
        $relay->sendPrices();
    } catch (Exception $e) {
        mail(
            'alchemist.tech@gmail.com',
            'Failed to send price update to SpaPlus',
            'Failed to send price update to SpaPlus (site ' . $sid . '): ' . $e->getMessage()
        );
    }
    echo '<script> window.location.href = "?page=treatments"; </script>';
    return;
}
?>
<h1>טיפולי ספא</h1>

<?php if ($canSave) { ?>
    <div style="margin: 20px 0;">
        <button type="button" class="pop-btn" onclick="openTreatmentsPop()">
            הוספה/הסרה של טיפולים
        </button>
        <button type="button" class="pop-btn" onclick="openTimesPop()" style="margin-right: 10px;">
            הגדרת זמני טיפול
        </button>
    </div>
<?php } ?>

<?php
$realTimes = udb::single_column(
    "SELECT DISTINCT `duratuion` FROM `treatmentsPricesSites` WHERE `siteID` = " . $sid . " ORDER BY `duratuion`"
);

if ($_GET['gal']) {
    echo "SELECT DISTINCT `duratuion` FROM `treatmentsPricesSites` WHERE `siteID` = " . $sid . " ORDER BY `duratuion`";
}

$timeout = max(0, udb::single_value("SELECT `waitingTime` FROM `sites` WHERE `siteID` = " . $sid));

if ($realTimes) {
    $tratsSQL = "SELECT * FROM `treatments` WHERE 1";
    $treatments = udb::full_list($tratsSQL);
    ?>
    <form method="post">
        <input type="hidden" name="asite" value="<?= $sid ?>" />
        <div class="priceTable">
            <div class="timeouts">
                <div class="timeout">
                    <u><b>זמן נקיון: </b></u>
                </div>
                <?php
                foreach (range(0, 30, 5) as $i) {
                    echo '<div class="timeout">
                          <input type="radio" name="timeout" id="timeout' , $i , '" value="' , $i , '" ' ,
                    ($timeout == $i ? 'checked' : '') , '/>
                          <label for="timeout' , $i , '">' , $i , " דק'</label>
                          </div>";
                }
                ?>
            </div>

            <table>
                <thead>
                <th>טיפול</th>
                <?php foreach ($realTimes as $time) { ?>
                    <th>
                        <?= $time ?> דקות
                        <div class="pricetype">
                            <span>יחיד</span>
                            <span>זוג</span>
                            <span>קבוצה</span>
                        </div>
                    </th>
                <?php } ?>
                </thead>
                <tbody>
                <?php
                $readonly = $canSave ? '' : 'readonly';
                $valuesSql = "SELECT * FROM treatmentsPricesSites WHERE siteID=" . $sid;
                $values = udb::full_list($valuesSql);
                $prices = $prices2 = $prices3 = [];

                foreach ($values as $v) {
                    $prices[$v['treatmentID']][$v['duratuion']] = $v['price1'];
                    $prices2[$v['treatmentID']][$v['duratuion']] = $v['price2'];
                    $prices3[$v['treatmentID']][$v['duratuion']] = $v['price3'];
                }
                foreach ($treatments as $t) {
                    if (!isset($prices[$t['treatmentID']])) {
                        continue;
                    }
                    ?>
                    <tr>
                        <td width="120"><?= $t['treatmentName'] ?></td>
                        <?php
                        foreach ($realTimes as $time) {
                            $useTime  = preg_replace("/[^0-9]/", "", $time);
                            echo '<td class="inputs">
                                    <input type="text" ' . $readonly . ' name="tprice[' . $t['treatmentID'] . '][' . $useTime . ']" 
                                           value="' . ($prices[$t['treatmentID']][$useTime]) . '">
                                    <input type="text" ' . $readonly . ' name="tprice2[' . $t['treatmentID'] . '][' . $useTime . ']" 
                                           value="' . ($prices2[$t['treatmentID']][$useTime] ?: '') . '">
                                    <input type="text" ' . $readonly . ' name="tprice3[' . $t['treatmentID'] . '][' . $useTime . ']" 
                                           value="' . ($prices3[$t['treatmentID']][$useTime] ?: '') . '">
                                  </td>';
                        }
                        ?>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

            <?php if ($canSave) { ?>
                <input type="submit" id="submitTreats" class="save-btn" value="שמור">
            <?php } ?>
        </div>
    </form>
<?php } ?>

<link href="assets/css/style_ctrl.php?dir=<?= $dir ?>&fileName=treatments&v=<?= rand() ?>" rel="stylesheet">

<!-- Popups and related scripts -->
<div class="pop-window" id="treatmentsPop" style="display:none;">
    <div class="pop-overlay" onclick="closeTreatmentsPop()"></div>
    <div class="pop-content">
        <button class="close" onclick="closeTreatmentsPop()">✕</button>
        <h3>בחר/הסר טיפולים</h3>
        <div id="treatmentsList"></div>
        <div class="btn-area">
            <button onclick="saveTreatments()">שמור בחירות</button>
        </div>
    </div>
</div>

<div class="pop-window" id="timesPop" style="display:none;">
    <div class="pop-overlay" onclick="closeTimesPop()"></div>
    <div class="pop-content">
        <button class="close" onclick="closeTimesPop()">✕</button>
        <h3>בחר זמני טיפול (בדקות)</h3>
        <div id="timesList"></div>
        <div class="btn-area">
            <button onclick="saveTimes()">שמור בחירות</button>
        </div>
    </div>
</div>

<script>
    // Ajax logic for the treatments popup
    function openTreatmentsPop(){
        document.getElementById('treatmentsPop').style.display='block';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajax_pop_treats.php?siteID=<?= $sid ?>', true);
        xhr.onload = function(){
            if(xhr.status===200){
                document.getElementById('treatmentsList').innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }

    function closeTreatmentsPop(){
        document.getElementById('treatmentsPop').style.display='none';
    }

    function saveTreatments(){
        var checks = document.querySelectorAll('#treatmentsList input[type=checkbox]');
        var chosen = [];
        for(var i=0; i<checks.length; i++){
            if(checks[i].checked){
                chosen.push(checks[i].value);
            }
        }
        if(chosen.length === 0){
            alert("Please select at least one treatment.");
            return;
        }
        var formData = new FormData();
        formData.append('siteID','<?= $sid ?>');
        formData.append('treatments', chosen.join(','));

        var xhr = new XMLHttpRequest();
        xhr.open('POST','ajax_pop_treats.php',true);
        xhr.onload = function(){
            if(xhr.status===200){
                closeTreatmentsPop();
                location.reload();
            }
        };
        xhr.send(formData);
    }

    // Ajax logic for the times popup
    function openTimesPop(){
        document.getElementById('timesPop').style.display='block';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajax_pop_times.php?siteID=<?= $sid ?>', true);
        xhr.onload = function(){
            if(xhr.status===200){
                document.getElementById('timesList').innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }

    function closeTimesPop(){
        document.getElementById('timesPop').style.display='none';
    }

    function saveTimes(){
        var checks = document.querySelectorAll('#timesList input[type=checkbox]');
        var chosen = [];
        for(var i=0; i<checks.length; i++){
            if(checks[i].checked){
                chosen.push(checks[i].value);
            }
        }
        if(chosen.length === 0){
            alert("Please select at least one time.");
            return;
        }
        var formData = new FormData();
        formData.append('siteID','<?= $sid ?>');
        formData.append('times', chosen.join(','));

        var xhr = new XMLHttpRequest();
        xhr.open('POST','ajax_pop_times.php',true);
        xhr.onload = function(){
            if(xhr.status===200){
                closeTimesPop();
                location.reload();
            }
        };
        xhr.send(formData);
    }
</script>

<style>
    /* Basic styling for the new elements */

    /* Save button in the treatments table */
    .save-btn {
        background: #4CAF50;
        border: none;
        border-radius: 4px;
        color: #fff;
        font-size: 14px;
        padding: 8px 16px;
        cursor: pointer;
    }
    .save-btn:hover {
        background-color: #45a049;
    }

    /* Buttons that open the pop-ups */
    .pop-btn {
        background-color: #2196F3;
        color: #fff;
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
    }
    .pop-btn:hover {
        background-color: #0b7dda;
    }

    /* Pop-up structure */
    .pop-window {
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
    }
    .pop-overlay {
        position: absolute;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        top: 0;
        left: 0;
        cursor: pointer;
    }
    .pop-content {
        direction: rtl;
        position: relative;
        margin: 5% auto;
        background: #fff;
        padding: 20px;
        width: 400px;
        border-radius: 6px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .pop-content h3 {
        margin: 0 0 10px;
        font-size: 18px;
        text-align: right;
    }
    .pop-content .close {
        float: left;
        background: transparent;
        border: none;
        color: #333;
        font-size: 18px;
        cursor: pointer;
        margin-top: -4px;
    }
    .pop-content .close:hover {
        color: #888;
    }
    #treatmentsList, #timesList {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 4px;
    }
    #treatmentsList label, #timesList label {
        display: block;
        margin-bottom: 5px;
        cursor: pointer;
    }
    .btn-area {
        text-align: center;
        margin-top: 15px;
    }
    .btn-area button {
        background: #007BFF;
        color: #fff;
        padding: 7px 14px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
    }
    .btn-area button:hover {
        background: #0056b3;
    }
</style>
