<?php
include_once "bin/system.php";
include_once "bin/top.php";

$treatments = udb::single_list("SELECT * FROM `treatments` WHERE 1");
$extras     = udb::single_list("SELECT * FROM `treatmentsExtras` WHERE 1");

Translation::$lang_id = 2;
?>
    <div class="manageItems" id="manageItems">
        <!-- h1><?=Dictionary::translate("מילון")?></h1>
        <div class="filter" style="margin-top:0;border-top:1px solid #fff;">
            <h2><?=Dictionary::translate("חיפוש מילה")?>:</h2>
            <form method="get">
                <div>
                    <input type="search" name="freeSearch" <?php if(isset($freeSearch)){ echo 'value="'.$freeSearch.'"'; }?> placeholder="<?=Dictionary::translate("חופשי")?>" autocomplete="off">
                    <input type="submit" value="<?=Dictionary::translate("חפש")?>">
                </div>
            </form>
        </div>
        <div class="numbers">
            <?for($i=1;$i<=$totalPages;$i++){?>
                <input class="pageNum <?=$pageNum==$i? "active" : ""?>" value="<?=$i?>" onclick="window.location.href='?pageNum=<?=$i?>'">
            <?}?>
        </div -->
        <form method="POST">
            <h1>טיפולים:</h1>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>שם בעברית</th>
                    <th>תיאור בעברית</th>
                    <th>תיאור באנגלית</th>
                    <th>שם באנגלית</th>
                </tr>
                </thead>
                <tbody>
<?php
    foreach($treatments as $treat){
?>
                    <tr>
                        <td><?=$treat['treatmentID']?></td>
                        <td><?=$treat['treatmentName']?></td>
                        <td><?=$treat['treatmentDesc']?></td>
<?php
        Translation::treatments($treat['treatmentID'])->apply($treat);
?>
                        <td><?=$treat['treatmentDesc']?></td>
                        <td><?=$treat['treatmentName']?></td>
                    </tr>
<?php
    }
?>
                </tbody>
            </table>

            <h1>תוספים:</h1>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>שם בעברית</th>
                    <th>שם באנגלית</th>
                </tr>
                </thead>
                <tbody>
<?php
    foreach($extras as $extra){
?>
                    <tr>
                        <td><?=$extra['extraID']?></td>
                        <td><?=$extra['extraName']?></td>
<?php
        Translation::treatmentsExtras($extra['extraID'])->apply($extra);
?>
                        <td><?=$extra['extraName']?></td>
                    </tr>
<?php
    }
?>
                </tbody>
            </table>

        </form>
    </div>
<?php
include_once "bin/footer.php";
