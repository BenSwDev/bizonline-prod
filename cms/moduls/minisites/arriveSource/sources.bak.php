<?php
include_once "../../../bin/system.php";
include_once "../../../_globalFunction.php";
include_once "../../../bin/top.php";

if ('POST' == $_SERVER['REQUEST_METHOD']) {
    $postData = typemap($_POST, [
        'id'         => 'int',
        'active'     => 'int',
        'fullname'   => 'string',
        'shortname'  => 'string',
        'hexColor'   => 'string',
        'letterSign' => 'string',
        '!type'      => ['int'],
        'showPrice'  => 'int'
    ]);

    $sourceID = $postData['id'] ?? 0;

    // main extra data
    $que = [
        'shortname'  => $postData['shortname'],
        'fullname'   => $postData['fullname'],
        'hexColor'   => $postData['hexColor'],
        'letterSign' => $postData['letterSign'],
        'active'     => $postData['active'] ? 1 : 0,
        'siteType'   => array_sum($postData['type']) ?: 0,
        'showPrice'  => $postData['showPrice'] ? 1 : 0
    ];

    if ($sourceID)
        udb::update("arrivalSources" , $que , "`id` = " . $postData['id']);
    else {
        $maxOrder = udb::single_value("SELECT MAX(`showOrder`) FROM `arrivalSources` WHERE `showOrder` < 10000") ?: 0;

        $que['showOrder'] = $maxOrder + 1;

        $sourceID = udb::insert("arrivalSources" , $que);
    }

    echo '<script> location.href = "/cms/moduls/minisites/arriveSource/sources.php"; </script>';
    exit;
}

$arrMain = udb::single_list("SELECT * FROM `arrivalSources` WHERE `payTypeKey` = '' ORDER BY `showOrder`");
$arrSupp = udb::single_list("SELECT a.*, p.id AS `ptID` FROM `arrivalSources` AS `a` INNER JOIN `payTypes` AS `p` ON (a.payTypeKey = p.key) WHERE a.payTypeKey <> '' ORDER BY a.showOrder");

$itemID = intval($_GET['id'] ?? 0);
$item   = udb::single_row("SELECT * FROM `arrivalSources` WHERE `id` = " . $itemID) ?: [];

?>
<style>
.sign{width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold;border-radius:50%;color:black}
green{color:green}
red{color:red}
</style>
<div class="pagePop" <?=($itemID || isset($_GET['new'])) ? " style='display: block;' " : "";?>><div class="pagePopCont">
        <section id="mainContainer">
            <div class="editItems">
                <h1><?=($itemID ? "עריכת פריט" : "הוספת פריט חדש")?></h1>
                <form method="POST" id="myform">
                    <div class="frm">
                        <div class="inputLblWrap">
                            <div class="labelTo">שם קצר</div>
                            <input type="text" placeholder="שם קצר" name="shortname" value="<?=$item['shortname']?>" />
                        </div>
						<div class="inputLblWrap">
                            <div class="labelTo">שם מלא</div>
                            <input type="text" placeholder="שם מלא" name="fullname" value="<?=$item['fullname']?>" />
                        </div>
						<div class="inputLblWrap">
                            <div class="labelTo">אותיות מייצגות</div>
                            <input type="text" placeholder="" name="letterSign" maxlength="2" value="<?=$item['letterSign']?>" />
                        </div>
						<div class="inputLblWrap">
                            <div class="labelTo">צבע</div>
                            <input type="color" placeholder="צבע" name="hexColor" value="<?=$item['hexColor']?>" />
                        </div>
						<div></div>
                        <div class="inputLblWrap">
                            <div class="switchTtl">פעיל</div>
                            <label class="switch">
                                <input type="checkbox" name="active" value="1" <?=(($item['active'] == 1 || !$itemID) ? "checked" : "")?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
						<div class="inputLblWrap">
                            <div class="switchTtl">ספא</div>
                            <label class="switch">
                                <input type="checkbox" name="type[]" value="2" <?=(($item['siteType'] & 2) ? "checked" : "")?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
						<div class="inputLblWrap">
                            <div class="switchTtl">מתחמים</div>
                            <label class="switch">
                                <input type="checkbox" name="type[]" value="5" <?=(($item['siteType'] & 1) ? "checked" : "")?> />
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="inputLblWrap">
                            <div class="switchTtl">הצג מחיר ללקוח</div>
                            <label class="switch">
                                <input type="checkbox" name="showPrice" value="1" <?=($item['showPrice'] ? "checked" : "")?> />
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="inputLblWrap">
                            <input type="submit" name="submit" id="submit" class="submit" value="שמור" style="position: absolute;" />
                        </div>
                    </div>
                    <input type="hidden" name="id" id="id" value="<?=$itemID?>" />
                </form>
            </div>
        </section>
        <div class="tabCloser" onclick="closepop()">x</div>
    </div></div>
<div class="manageItems" id="manageItems">
    <h1>ניהול מקורות הגעה כלליים</h1>
    <div style="margin-top: 20px;">
        <input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="window.location='?new=1'">
    </div>
    <div class="tblMobile">
        <table style="width:auto">
            <thead>
                <!-- tr style="background-color:#E9E9E9">
                    <th colspan="6" style="background-color:#E9E9E9; font-size:24px; color:#2AAFD4; border:0">מקורות ראשיים</th>
                </tr -->
                <tr>
					<th>#</th>
                    <th>שם</th>
                    <th>סימון</th>
                    <th>פעיל</th>
                    <th style="width:50px">ספא</th>
                    <th style="width:50px">מתחמים</th>
                </tr>
            </thead>
            <tbody>
<?php
    foreach ($arrMain as $item) {
?>
                <tr onclick="window.location.href='?id=<?=$item['id']?>'">
                    <td><?=$item['id'] ?></td>
                    <td><?=$item['fullname'] ?></td>
                    <td><div class="sign" style="background:<?=$item['hexColor']?>"><?=$item['letterSign'] ?></div></td>
                    <td><?=($item['active'] ? "<green>פעיל</green>" : "<red>לא פעיל</red>") ?></td>
                    <td><?=(($item['siteType'] & 2) ? "<green>כן</green>" : "<red>לא</red>") ?></td>
                    <td><?=(($item['siteType'] & 1) ? "<green>כן</green>" : "<red>לא</red>") ?></td>
                </tr>
<?php
    }
?>
            </tbody>
        </table>

        <table style="width:auto; margin-top:25px">
            <thead>
                <tr style="background-color:#E9E9E9">
                    <th colspan="6" style="background-color:#E9E9E9; font-size:24px; color:#2AAFD4; border:0">ספקים</th>
                </tr>
                <tr>
					<th>#</th>
                    <th>שם</th>
                    <th>סימון</th>
                    <th>פעיל</th>
                    <th style="width:50px">ספא</th>
                    <th style="width:50px">מתחמים</th>
                </tr>
            </thead>
            <tbody>
<?php
    foreach ($arrSupp as $item) {
?>
                <tr onclick="window.location.href='?id=<?=$item['ptID']?>'">
                    <td><?=$item['id'] ?></td>
                    <td><?=$item['fullname'] ?></td>
                    <td><div class="sign" style="background:<?=$item['hexColor']?>"><?=$item['letterSign'] ?></div></td>
                    <td><?=($item['active'] ? "<green>פעיל</green>" : "<red>לא פעיל</red>") ?></td>
                    <td><?=(($item['siteType'] & 2) ? "<green>כן</green>" : "<red>לא</red>") ?></td>
                    <td><?=(($item['siteType'] & 1) ? "<green>כן</green>" : "<red>לא</red>") ?></td>
                </tr>
<?php
    }
?>
            </tbody>
        </table>
    </div>
</div>
<script>
    function closepop(){
        $("#myform")[0].reset();
        $(".pagePop").hide();
    }
</script>
<?php
include_once "../../../bin/footer.php";
