<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

// Active site
$sid = $_CURRENT_USER->active_site() ?: 0;

// Will hold the selected unitID, if any
$uid = 0;
if (isset($_GET["uid"]) || isset($_POST['uid'])) {
    $uid = intval($_GET["uid"] ?? $_POST['uid']);
}
?>
<h1>הגדרות חדרים</h1>
<style>
    .editItems form {
        margin-top: 30px;
        background: #ffffff;
        padding: 10px;
        border-bottom: 2px solid rgba(0,0,0,0.2);
        margin-bottom: 10px;
        border-radius: 3px;
        overflow: hidden;
        font-size: 14px;
    }

    .mainSectionWrapper {
        border: 1px solid #f3f3f3;
        clear: both;
        margin-top: 10px;
    }
    .mainSectionWrapper .sectionName {
        background: #d8d8d8;
        line-height: 50px;
        margin-bottom: 20px;
        cursor: pointer;
        text-align: right;
        box-sizing: border-box;
        font-weight: bold;
        font-size: 20px;
        padding-right: 10px;
    }
    .inputLblWrap {
        display: inline-block;
        vertical-align: middle;
        min-width: 200px;
        margin: 4%;
    }
    .editItems input#submitTreats {
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
    .inputLblWrap .labelTo {
        display: block;
        vertical-align: middle;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .editItems input[type='text'], .editItems input[type='password'], .editItems input[type='submit'], .editItems input[type='number'], .editItems textarea {
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
        width: 98%;
        font-family: 'Rubik', sans-serif;
    }

    .frameContent {
        position: relative;
    }
    .checkIb {
        display: inline-block;
        margin: 1%;
        width: 206px;
    }
    .checkLabel .checkBoxWrap {
        position: relative;
        width: 20px;
        height: 20px;
        cursor: pointer;
        box-sizing: border-box;
        border: 1px solid #666;
        background: #fff;
        display: inline-block;
        vertical-align: middle;
        border-radius: 4px;
    }
    .checkLabel .checkBoxWrap input[type="checkbox"] {
        display: none;
    }
    .editItems input[type='checkbox'] {
        margin: 4px !important;
        -webkit-appearance: checkbox !important;
    }
    input, select, textarea {
        font-family: 'Rubik', sans-serif;
        border: 1px solid #ccc;
    }
    .checkLabel .checkBoxWrap label {
        width: 100%;
        height: 100%;
        cursor: pointer;
        position: absolute;
        top: 0;
        left: 0;
    }
    .checkLabel .checkBoxWrap label::after {
        content: '';
        width: 14px;
        height: 3px;
        position: absolute;
        top: 4px;
        left: 1px;
        border: 3px solid #666;
        border-top: none;
        border-right: none;
        background: transparent;
        opacity: 0;
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
    }
    .checkLabel > label {
        font-size: 16px;
        color: #666;
        display: inline-block;
        vertical-align: middle;
        font-weight: bold;
        cursor: pointer;
    }
    .checkLabel .checkBoxWrap input:checked + label:after {
        opacity: 1;
    }
    .labelTo {
        display: block;
        vertical-align: middle;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .manageItems table {
        margin-top: 25px;
        margin-bottom: 10px;
        width: 100%;
        border-bottom: 2px solid rgba(0,0,0,0.1);
        box-sizing: border-box;
        border-radius: 5px;
        overflow: hidden;
    }
    .manageItems table > thead {
        background: #ffffff;
        border-bottom: 2px solid #f5f5f5;
        line-height: 32px;
        font-weight: bold;
    }

    .manageItems table > thead > tr > th {
        text-align: right;
        padding-right: 5px;
        border: 2px solid #f5f5f5;
        line-height: 1;
        padding: 10px 4px;
        vertical-align: middle;
    }
    .manageItems table > thead > tr > th:nth-child(1) {
        width: 5%;
        text-align: center;
        padding-right: 0;
    }
    .manageItems table > tbody > tr {
        line-height: 30px;
        color: #666;
        cursor: pointer;
        font-size: 14px;
    }
    .manageItems table > tbody > tr:nth-child(odd) {
        background: #F9F9F9;
    }
    .manageItems table > tbody > tr > td {
        border: 1px solid #f5f5f5;
        padding-right: 10px;
        vertical-align: middle;
    }

    /* Additional style for toggle switch if needed */
    .switch {
        position: relative;
        display: inline-block;
        width: 45px;
        height: 24px;
        margin-top: 5px;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider {
        background-color: #0dabb6;
    }
    input:checked + .slider:before {
        -webkit-transform: translateX(20px);
        -ms-transform: translateX(20px);
        transform: translateX(20px);
    }

    /* New styles for add/remove buttons */
    .addNewRoom {
        height: 30px;
        padding: 0 20px;
        border-radius: 15px;
        color: white;
        background: #0dabb6;
        font-size: 16px;
        cursor: pointer;
        border: none;
    }
    .deleteRoom {
        height: 30px;
        padding: 0 20px;
        border-radius: 15px;
        color: white;
        background: #0dabb6;
        font-size: 12px;
        cursor: pointer;
        display: inline-block;
        text-align: center;
        line-height: 30px;
    }
</style>
<?php

//////////////////////////////////////////////////////////////////////////////////
// Handle saving (insert/update) for either new or existing "rooms_units" record //
//////////////////////////////////////////////////////////////////////////////////
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isError = '';
    try {
        $data = typemap($_POST, [
            'hasTreatments'   => 'int',
            'hasStaying'      => 'int',
            'maxTreatments'   => 'int',
            'attributes'      => ['int' => 'int'],
            'uname'           => ['int' => 'string'],
            // We remove 'roomID' because user doesn't pick or see it
        ]);

        // Build array for insertion or updating
        $siteData = [
            'hasTreatments'  => $data['hasTreatments'],
            'hasStaying'     => $data['hasStaying'],
            'maxTreatments'  => $data['maxTreatments'],
        ];

        if (!empty($data['uname'][1])) {
            $siteData['unitName'] = $data['uname'][1];
        }

        // If uid == 0 => Insert new record
        if ($uid === 0) {
            // Auto-select the first "roomID" from `rooms` for the site, if any
            $firstRoom = udb::single_row("SELECT * FROM `rooms` WHERE `siteID` = " . intval($sid) . " ORDER BY roomID ASC LIMIT 1");
            $siteData['roomID'] = $firstRoom ? $firstRoom['roomID'] : 0;

            // Insert
            $uid = udb::insert('rooms_units', $siteData);

            // English name if given
            if (!empty($data['uname'][2])) {
                Translation::save('rooms_units', $uid, 'unitName', $data['uname'][2], 2);
            }

        } else {
            // Update existing
            udb::update('rooms_units', $siteData, '`unitID` = ' . $uid);

            // If there's an English name or clearing it
            if (empty($data['uname'][2])) {
                Translation::clear('rooms_units', $uid, 'unitName', 2);
            } else {
                Translation::save('rooms_units', $uid, 'unitName', $data['uname'][2], 2);
            }
        }

        // Handle linked treatments
        udb::query("DELETE FROM `units_treats` WHERE `unitID` = " . $uid);
        if (!empty($data['attributes'])) {
            $que = [];
            foreach($data['attributes'] as $attr) {
                $que[] = '(' . $uid . ', ' . intval($attr) . ')';
            }
            if ($que) {
                $sqlInsert = "INSERT INTO `units_treats` (`unitID`, `treatmentID`) VALUES " . implode(',', $que);
                udb::query($sqlInsert);
            }
        }
    }
    catch (LocalException $e) {
        $isError = $e->getMessage();
    }
    ?>
    <script>
        <?php if ($isError) { ?>
        alert('<?= $isError ?>');
        <?php } ?>
    </script>
    <?php
}

// If we have a 'del' GET param => remove the specified unit
if (isset($_GET['del'])) {
    $delID = intval($_GET['del']);
    if ($delID > 0) {
        // You can do a "soft delete" or a real "delete" from DB:
        udb::query("DELETE FROM `rooms_units` WHERE `unitID` = " . $delID);
        // Also remove any treatments linked to it
        udb::query("DELETE FROM `units_treats` WHERE `unitID` = " . $delID);
    }
    // Redirect back to main to avoid repeated confirmations
    echo '<script>window.location.href = "?page=' . $_GET['page'] . '";</script>';
    exit;
}

//////////////////////////////////////////////////////
// If we're editing (or creating) a single unit form //
//////////////////////////////////////////////////////
if (isset($_GET['uid'])) {
    if ($uid > 0) {
        // Existing
        $siteData = udb::single_row("SELECT * FROM `rooms_units` WHERE `unitID`=" . $uid);
        // For listing possible treatments, figure out which site:
        $usite = 0;
        if ($siteData) {
            $usite = udb::single_value("SELECT `siteID` FROM `rooms` WHERE `roomID` = " . $siteData['roomID']);
        }
        if (!$usite) {
            // fallback to current $sid
            $usite = $sid;
        }
        $tTreats = udb::single_column("SELECT treatmentID FROM `units_treats` WHERE `unitID`=" . $uid);
        if (!$siteData) {
            // If not found, just treat as new
            $siteData = [
                'unitName'      => '',
                'hasTreatments' => 0,
                'hasStaying'    => 0,
                'maxTreatments' => 1,
            ];
            $tTreats = [];
        }
    } else {
        // brand new
        $siteData = [
            'unitName'      => '',
            'hasTreatments' => 0,
            'hasStaying'    => 0,
            'maxTreatments' => 1,
        ];
        $usite = $sid;
        $tTreats = [];
    }

    // Fetch possible treatments for $usite
    $tratsSQL = "SELECT t.* 
                 FROM `treatments` t
                 INNER JOIN `treatmentsPricesSites` USING(`treatmentID`) 
                 WHERE treatmentsPricesSites.siteID = " . intval($usite) . " 
                 GROUP BY t.treatmentID";
    $treatments = udb::full_list($tratsSQL);

    // For display title
    $titleName = ($uid > 0 && !empty($siteData['unitName'])) ? $siteData['unitName'] : "חדר חדש";
    ?>
    <div class="edit_subtitle"><?= htmlentities($titleName) ?></div>
    <div class="editItems">
        <a class="backbtn" href="?page=<?= $_GET["page"] ?>">חזרה</a>
        <div class="inputLblWrap">
            <div class="frameContent">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="uid" value="<?= $uid ?>">

                    <div class="mainSectionWrapper">
                        <div class="sectionName">הגדרות</div>

                        <!-- We no longer show “בחר חדר” since user should not deal with any IDs -->

                        <div class="inputLblWrap">
                            <div class="labelTo">שם החדר בעברית</div>
                            <input type="text" name="uname[1]" value="<?= htmlentities($siteData['unitName']) ?>" />
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">שם החדר באנגלית</div>
                            <input type="text"
                                   name="uname[2]"
                                   value="<?= htmlentities(Translation::rooms_units(($uid ?: 0), 'unitName', 2)) ?>" />
                        </div>

                        <div class="inputLblWrap">
                            <div class="labelTo">טיפולים בחדר?</div>
                            <label class="switch">
                                <input type="checkbox"
                                       onchange="$('#maxTreats').toggleClass('show', this.checked)"
                                       name="hasTreatments"
                                       value="1"
                                    <?= ($siteData['hasTreatments'] ? 'checked' : '') ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap">
                            <div class="labelTo">שהות בחדר?</div>
                            <label class="switch">
                                <input type="checkbox"
                                       name="hasStaying"
                                       value="1"
                                    <?= ($siteData['hasStaying'] ? 'checked' : '') ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <style>
                            #maxTreats { opacity:0; transition:0.2s all; }
                            #maxTreats.show { opacity:1; }
                        </style>
                        <div class="inputLblWrap <?= ($siteData['hasTreatments'] ? 'show' : '') ?>" id="maxTreats">
                            <div class="labelTo">כמות טיפולים מקסימלית במקביל</div>
                            <select name="maxTreatments">
                                <?php
                                for ($i = 1; $i <= 10; $i++) {
                                    echo '<option value="', $i, '"',
                                    ($i == (int)$siteData['maxTreatments'] ? ' selected' : ''),
                                    '>', $i, '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="mainSectionWrapper attr">
                        <div class="sectionName">טיפולים</div>
                        <div class="checksWrap">
                            <div><span class="checkall">סמן הכל</span></div>
                            <?php
                            if (is_array($treatments)) {
                                foreach ($treatments as $attribute) {
                                    $tid = $attribute['treatmentID'];
                                    ?>
                                    <div class="checkLabel checkIb">
                                        <div class="checkBoxWrap">
                                            <input class="checkBoxGr"
                                                   type="checkbox"
                                                   name="attributes[]"
                                                   value="<?= $tid ?>"
                                                   id="ch<?= $tid ?>"
                                                <?= in_array($tid, $tTreats) ? 'checked' : '' ?>>
                                            <label for="ch<?= $tid ?>"></label>
                                        </div>
                                        <label for="ch<?= $tid ?>"><?= $attribute['treatmentName'] ?></label>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <input type="submit" value="שמור" id="submitTreats" class="submit not-empty">
                </form>
            </div>
        </div>
    </div>
    <script>
        // "Select all" toggle
        $('.checkall').on('click',function(){
            $(this).toggleClass('checked');
            const checked = $(this).hasClass('checked');
            $(this).html(checked ? 'בטל הכל' : 'סמן הכל');
            $(this).closest('.mainSectionWrapper').find('input[type=checkbox]').prop('checked', checked);
        });
    </script>
    <?php
}
////////////////////////////////////////////////////////
// Otherwise, show the main table listing all "units" //
////////////////////////////////////////////////////////
else {
    ?>
    <div class="manageItems" id="manageItems">
        <h2>רשימת חדרים / יחידות</h2>

        <!-- "Add new" button -->
        <div style="margin-top: 20px;">
            <button type="button"
                    class="addNewRoom"
                    onclick="openPop(0)">
                הוסף חדש
            </button>
        </div>

        <div class="tblMobile">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>חדר</th>
                    <th>חדר טיפולים</th>
                    <th>שהות</th>
                    <th>מחיקה</th>
                </tr>
                </thead>
                <tbody id="sortRow">
                <?php
                // We fetch each "parent" room, then child "units"
                $roomsSql = "SELECT * FROM `rooms` WHERE `siteID`=" . intval($sid);
                $rooms = udb::full_list($roomsSql);
                if ($rooms) {
                    foreach ($rooms as $room) {
                        $roomID = $room['roomID'];
                        $que = "SELECT u.*
                                FROM `rooms_units` AS u
                                WHERE u.roomID = " . $roomID . "
                                ORDER BY u.unitID ASC";
                        $units = udb::key_row($que, 'unitID');

                        if (!empty($units)) {
                            foreach ($units as $uID => $unit) {
                                $utitle = outDb($unit['unitName']);
                                // "חדר טיפולים" boolean
                                $isTreat = $unit['hasTreatments'] ?
                                    ("<span style='color:green;'>כן</span> (" . $unit['maxTreatments'] . ")") :
                                    ("<span style='color:red;'>לא</span>");
                                // "שהות בחדר" boolean
                                $isStay  = $unit['hasStaying'] ?
                                    "<span style='color:green;'>כן</span>" :
                                    "<span style='color:red;'>לא</span>";
                                ?>
                                <tr>
                                    <td onclick="openPop(<?= $unit['unitID'] ?>)"><?= $unit['unitID'] ?></td>
                                    <td onclick="openPop(<?= $unit['unitID'] ?>)"><?= $utitle ?></td>
                                    <td onclick="openPop(<?= $unit['unitID'] ?>)"><?= $isTreat ?></td>
                                    <td onclick="openPop(<?= $unit['unitID'] ?>)"><?= $isStay ?></td>
                                    <td>
                                        <div class="deleteRoom"
                                             onclick="delRoom(<?= $unit['unitID'] ?>)">
                                            מחק
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        function openPop(uID){
            location.href = "?page=<?= $_GET['page'] ?>&uid=" + uID;
        }
        function delRoom(uID) {
            if (!confirm('למחוק את החדר?')) return;
            // Use GET param for simplicity:
            location.href = "?page=<?= $_GET['page'] ?>&del=" + uID;
        }
    </script>
    <?php
}
?>
<script>
    // "Select all" toggle for the main list, if needed
    $('.checkall').on('click',function(){
        $(this).toggleClass('checked');
        if($(this).hasClass('checked')){
            $(this).html('בטל הכל');
            $(this).closest('.mainSectionWrapper').find('input').prop('checked',true);
        } else {
            $(this).closest('.mainSectionWrapper').find('input').prop('checked', false);
            $(this).html('סמן הכל');
        }
    });
</script>
