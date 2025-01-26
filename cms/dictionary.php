<?php
include_once "bin/system.php";
include_once "bin/top.php";


if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    foreach($_POST['translation'] as $wordID=>$val){
        foreach($val as $lngID=>$wrd){
            $cp=Array();
            $cp['id']=$wordID;
            $cp['langID']=$lngID;
            $cp['translation']=$wrd;


            $que="SELECT * FROM dictionary_words WHERE id=".$wordID." AND langID=".$lngID." ";
            $checkWord=udb::single_row($que);
            if($checkWord){
                udb::update("dictionary_words", $cp, "id=".$wordID." AND langID=".$lngID."");
            } else {
                if($wrd){
                    udb::insert("dictionary_words", $cp);
                }
            }
        }
    }
}

$que="SELECT * FROM language WHERE 1";
$languages=udb::full_list($que);

$where=Array();
$where[]="1=1";
if(trim($_GET['freeSearch'])){
    $freeSearch=trim($_GET['freeSearch']);
    $where[]=" (word LIKE '%".addslashes($freeSearch)."%' OR dictionary_words.translation LIKE '%".addslashes($freeSearch)."%')";
}

$pageNum = intval($_GET["pageNum"])? intval($_GET["pageNum"]) : 1;	
$que="SELECT COUNT(*) as TOTALP FROM dictionary LEFT JOIN dictionary_words USING(id)  WHERE ".implode(" AND ", $where);
$totalPages = udb::full_list($que);
$pageTotal =150;
$totalPages = ceil($totalPages[0]['TOTALP']/$pageTotal);


$que="SELECT dictionary.* FROM dictionary LEFT JOIN dictionary_words USING(id) WHERE ".implode(" AND ", $where)." GROUP BY dictionary.id ORDER BY word LIMIT ".(($pageNum-1)*$pageTotal).",".($pageTotal);
$dictionary=udb::full_list($que);



$que="SELECT * FROM dictionary_words WHERE 1";
$words=udb::key_row($que, Array("langID","id"));


?>
<div class="manageItems" id="manageItems">
    <h1><?=Dictionary::translate("מילון")?></h1>
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
	</div>
    <form method="POST">
		
        <table>
            <thead>
            <tr>
                <th><?=Dictionary::translate("Word")?></th>
                <?php foreach($languages as $lang){ ?>
                    <th><?=$lang['LangName']?></th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach($dictionary as $dict){ ?>
                <tr>
                    <td><?=$dict['word']?></td>
                    <?php foreach($languages as $lang){ ?>
                        <td><input type="text" name="translation[<?=$dict['id']?>][<?=$lang['LangID']?>]" value='<?=htmlspecialchars ($words[$lang['LangID']][$dict['id']]['translation'],ENT_QUOTES)?>'></td>
                    <?php } ?>
                </tr>
            <?php } ?>
                <tr><td colspan="4">
                        <input type="submit" value="<?=Dictionary::translate("Save")?>" class="submit">
                </td></tr>
            </tbody>
        </table>

    </form>
</div>
<?php
include_once "bin/footer.php";
