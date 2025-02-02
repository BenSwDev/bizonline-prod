<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

// Restore logic to pick the "active site" from GET/POST if present:
$sid = intval($_GET['asite'] ?? $_POST['asite'] ?? $_CURRENT_USER->active_site());

// Also ensure we "select" that site on the user object:
$_CURRENT_USER->select_site($sid);

if ('POST' == $_SERVER['REQUEST_METHOD']){
    $input = typemap($_POST, [
        'active'        => ['int' => 'int'],
        'voucherprint'  => ['int' => 'int'],
        'price1'        => ['int' => 'int'],
        'price2'        => ['int' => 'int'],
        'price3'        => ['int' => 'int'],
        'priceWE'       => ['int' => 'int'],
        'included'      => ['int' => 'int'],
        'description'   => ['int' => 'text'],
        'people'        => ['int' => ['int']],
        'max'           => ['int' => 'int']
    ]);

    $list = udb::single_column("SELECT `extraID` FROM `sites_treatment_extras` WHERE `siteID` = " . $sid);

    if ($list){
        $options = ['price2', 'price3', 'priceWE'];

        foreach($list as $extraID){
            $update = [
                'price1'      => intval($input['price1'][$extraID]),
                'active'      => intval($input['active'][$extraID]),
                'included'    => intval($input['included'][$extraID]),
                'description' => $input['description'][$extraID]
            ];

            // If present, update optional fields
            foreach($options as $key) {
                if (isset($input[$key][$extraID])) {
                    $update[$key] = $input[$key][$extraID];
                }
            }

            // Max count
            if (isset($input['max'][$extraID])) {
                $update['countMax'] = $input['max'][$extraID];
            }
            // For people
            if (isset($input['people'][$extraID])) {
                $update['forPeople'] = array_sum($input['people'][$extraID]);
            }

            // Append voucherprint instead of overwriting
            $update['voucherprint'] = intval($input['voucherprint'][$extraID]);

            udb::update('sites_treatment_extras', $update, "`siteID` = " . $sid . " AND `extraID` = " . $extraID);
        }
    }
}
?>
<h1>תוספות לטיפולי ספא</h1>
<style>
    .priceTable table {
        margin-top: 25px;
        margin-bottom: 10px;
        width: 100%;
        border-bottom: 2px solid rgba(0,0,0,0.1);
        box-sizing: border-box;
        border-radius: 5px;
        overflow: hidden;
    }
    .priceTable table > thead {
        background: #ffffff;
        border-bottom: 2px solid #f5f5f5;
        line-height: 32px;
        font-weight: bold;
    }

    .priceTable table > thead > tr > th {
        text-align: center;
        border: 2px solid #f5f5f5;
        line-height: 1;
        padding: 10px 4px;
        vertical-align: middle;
        height: 40px;
    }
    .priceTable table > tbody > tr {
        line-height: 32px;
        color: #666;
        cursor: pointer;
        font-size: 14px;
    }
    .priceTable table > tbody > tr:nth-child(odd) {
        background: #F9F9F9;
    }
    .priceTable table > tbody > tr > td {
        border: 1px solid #f5f5f5;
        padding-right: 10px;
        vertical-align: middle;
        height: 40px;
        line-height: 32px;
    }
    .priceTable table tbody tr td input[type='text'],
    .priceTable table tbody tr td input[type='number'] {
        line-height: 32px;
        height: 32px;
        background: #f5f5f5;
        border: 0;
        border-radius: 3px;
        box-sizing: border-box;
        outline: none;
        font-size: 12px;
        padding: 0 5px;
        box-shadow: -1px 1px 0 rgb(0 0 0 / 20%);
        margin: 0 auto;
        width: 32%;
        font-family: 'Rubik', sans-serif;
    }
    .priceTable table tbody tr td textarea {
        width:200px;
        height:45px;
    }
    input#submitTreats {
        position: fixed;
        left: 23px;
        bottom: 38px;
        width: 90px;
        height: 50px;
        line-height: 50px;
        color: #ffffff;
        font-weight: bold;
        background: #2FC2EB;
        font-size: 16px;
        margin-top: 20px;
        text-shadow: -1px 1px 0 rgb(0 0 0 / 10%);
        border-bottom: 2px solid rgba(0,0,0,0.1);
        cursor: pointer;
        box-shadow: none;
        -moz-transition: all 0.25s;
        -webkit-transition: all 0.25s;
        transition: all 0.25s;
        text-align: center;
        display: inline-block;
        vertical-align: top;
    }
    .labelTo {
        display: block;
        vertical-align: middle;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .pricetype {
        display: block;
        margin-bottom: -10px;
        margin-top: 10px;
        font-size: 14px;
        font-weight: normal;
    }
    .pricetype span {
        width: 32%;
        display: inline-block;
    }
    td.spt, th.spt {position: relative;border: 0 !important;padding: 2px !important;}
    td.spt::before, th.spt::before {
        position: absolute;
        background: #999;
        content: "";
        margin: -4px 0;
        display: block;
        width: 100%;
        top: 0; bottom: 0; right: 0;
    }
    .lock-td{position:relative;pointer-events:none;}
    .lock-td::after{position:absolute;left:0;right:0;top:0;bottom:0;z-index:8}
    @media(max-width:900px){
        .priceTable table tbody tr td input[type='text'] {
            width:98%;
        }
    }
</style>
<?php
if (!$_CURRENT_USER->single_site){
    $sname = udb::key_row("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")",'siteID');
    ?>
    <div class="site-select">
        <label for="isid" class="labelTo">בחר מתחם</label>
        <select id="isid" title="שם מתחם" onchange="location.href = '?page=extras&asite=' + this.value">
            <?php
            foreach($sname as $id => $name) {
                echo '<option value="' , $name['siteID'] , '" ' , ($name['siteID'] == $sid ? 'selected' : '') , '>' , $name['siteName'] , '</option>';
            }
            ?>
        </select>
    </div>
    <?php
}

// We’ll group extras by their `extraType`
$que = "SELECT * 
        FROM `sites_treatment_extras` AS `se`
        INNER JOIN `treatmentsExtras` AS `e` USING(`extraID`)
        WHERE se.siteID = " . $sid . "
        ORDER BY e.showOrder";
$extras = udb::key_list($que, 'extraType');
$typesNames = [
    'package' => 'תוספים בחבילה',
    'general' => 'תוספים כללי - כמותי',
    'company' => 'תוספים מלווים',
    'rooms'   => 'תוספים לחברילה - חדרים'
];
?>
<form method="post">
    <input type="hidden" name="asite" value="<?=$sid?>" />
    <div class="priceTable">
        <?php
        // If no extras at all:
        if (!$extras){
            echo '<table><tr><td><i>לא הוגדרו תוספות</i></td></tr></table>';
        }

        // "package" extras
        if (isset($extras['package'])) {
            ?>
            <table>
                <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>תוספים בחבילה</th>
                    <th>מחיר ליחיד</th>
                    <th>מחיר לאדם בזוג</th>
                    <th>מחיר לאדם בקבוצה</th>
                    <th>תוספת סופ"ש לאדם</th>
                    <th>תאור התוספת</th>
                    <th>חינם</th>
                    <th>שובר להדפסה</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach($extras['package'] as $extra){
                    ?>
                    <tr>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="active[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['active'] ? 'checked' : '')?> />
                        </td>
                        <td class="lock-td" width="200"><?=$extra['extraName']?></td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="price1[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price1'] ?: '')?>" />
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="price2[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price2'] ?: '')?>" />
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="price3[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price3'] ?: '')?>" />
                        </td>
                        <td>
                            <input type="text"
                                   name="priceWE[<?=$extra['extraID']?>]"
                                   value="<?=($extra['priceWE'] ?: '')?>" />
                        </td>
                        <td class="!lock-td">
                            <textarea name="description[<?=$extra['extraID']?>]"><?=$extra['description']?></textarea>
                        </td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="included[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['included'] ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="voucherprint[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['voucherprint'] ? 'checked' : '')?> />
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <?php
        }

        // "general" extras
        if (isset($extras['general'])) {
            ?>
            <table>
                <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>תוספים כללי - כמותי</th>
                    <th>מחיר</th>
                    <th>תוספת סופ"ש לפריט</th>
                    <th>ליחיד</th>
                    <th>לזוג</th>
                    <th>לקבוצה</th>
                    <th>מקסימום</th>
                    <th>תאור התוספת</th>
                    <th>חינם</th>
                    <th>שובר להדפסה</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach($extras['general'] as $extra){
                    ?>
                    <tr>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="active[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['active'] ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td" width="200"><?=$extra['extraName']?></td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="price1[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price1'] ?: '')?>" />
                        </td>
                        <td>
                            <input type="text"
                                   name="priceWE[<?=$extra['extraID']?>]"
                                   value="<?=($extra['priceWE'] ?: '')?>" />
                        </td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="people[<?=$extra['extraID']?>][]"
                                   value="1"
                                <?=($extra['forPeople'] & 1 ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="people[<?=$extra['extraID']?>][]"
                                   value="2"
                                <?=($extra['forPeople'] & 2 ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="people[<?=$extra['extraID']?>][]"
                                   value="4"
                                <?=($extra['forPeople'] & 4 ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td">
                            <select name="max[<?=$extra['extraID']?>]">
                                <?php
                                for($i = 1; $i <= 10; ++$i){
                                    $sel = ($extra['countMax'] == $i ? 'selected' : '');
                                    echo "<option value=\"$i\" $sel>$i</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td class="!lock-td">
                            <textarea name="description[<?=$extra['extraID']?>]"><?=$extra['description']?></textarea>
                        </td>
                        <td class="!lock-td">
                            <input type="checkbox"
                                   name="included[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['included'] ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="voucherprint[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['voucherprint'] ? 'checked' : '')?> />
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <?php
        }

        // "rooms" extras
        if (isset($extras['rooms'])) {
            ?>
            <table>
                <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>תוספים חדרים</th>
                    <th>תיאור</th>
                    <th>זמן שהיה בסיס</th>
                    <th>מחיר שהייה בסיס</th>
                    <th>תוספת סופ"ש</th>
                    <th class="spt"></th>
                    <th>תוספת שעה לבסיס</th>
                    <th>תוספת סופ"ש </th>
                    <th>מקסימום שעות</th>
                    <th class="spt"></th>
                    <th>עלות לינה</th>
                    <th>תוספת סופ"ש </th>
                    <th>שובר להדפסה</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach($extras['rooms'] as $extra){
                    ?>
                    <tr>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="active[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['active'] ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td" width="200"><?=$extra['extraName']?></td>
                        <td class="!lock-td">
                            <textarea name="description[<?=$extra['extraID']?>]"><?=$extra['description']?></textarea>
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="min[<?=$extra['extraID']?>]"
                                   value="<?=(round($extra['countMin'], 1) ?: '')?>" />
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="price1[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price1'] ?: '')?>" />
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="we1[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price1'] ? $extra['priceWE'] : '')?>" />
                        </td>
                        <td class="!lock-td spt"></td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="price2[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price2'] ?: '')?>" />
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="we2[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price2'] ? $extra['priceWE2'] : '')?>" />
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="max[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price2'] ? (round($extra['countMax'], 1) ?: '') : '')?>" />
                        </td>
                        <td class="!lock-td spt"></td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="price3[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price3'] ?: '')?>" />
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="we3[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price3'] ? $extra['priceWE3'] : '')?>" />
                        </td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="voucherprint[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['voucherprint'] ? 'checked' : '')?> />
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <?php
        }

        // "company" extras
        if (isset($extras['company'])) {
            ?>
            <table>
                <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>תוספים מלווים</th>
                    <th>ליחיד / זוג</th>
                    <th>לקבוצה</th>
                    <th>מחיר</th>
                    <th>תוספת סופ"ש לפריט</th>
                    <th>תאור התוספת</th>
                    <th>חינם</th>
                    <th>שובר להדפסה</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach($extras['company'] as $extra){
                    ?>
                    <tr>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="active[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['active'] ? 'checked' : '')?> />
                        </td>
                        <td class="lock-td" width="200"><?=$extra['extraName']?></td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="people[<?=$extra['extraID']?>][]"
                                   value="3"
                                <?=($extra['forPeople'] & 3 ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="people[<?=$extra['extraID']?>][]"
                                   value="4"
                                <?=($extra['forPeople'] & 4 ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="price1[<?=$extra['extraID']?>]"
                                   value="<?=($extra['price1'] ?: '')?>" />
                        </td>
                        <td class="!lock-td">
                            <input type="text"
                                   name="priceWE[<?=$extra['extraID']?>]"
                                   value="<?=($extra['priceWE'] ?: '')?>" />
                        </td>
                        <td class="!lock-td">
                            <textarea name="description[<?=$extra['extraID']?>]"><?=$extra['description']?></textarea>
                        </td>
                        <td class="!lock-td">
                            <input type="checkbox"
                                   name="included[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['included'] ? 'checked' : '')?> />
                        </td>
                        <td class="!lock-td" width="50">
                            <input type="checkbox"
                                   name="voucherprint[<?=$extra['extraID']?>]"
                                   value="1"
                                <?=($extra['voucherprint'] ? 'checked' : '')?> />
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <?php
        }
        ?>
        <input type="submit" id="submitTreats" value="שמור">
    </div>
</form>
