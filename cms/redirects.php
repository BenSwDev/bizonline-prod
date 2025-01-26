<?php
include_once "bin/system.php";
include_once "_globalFunction.php";

$type = intval($_GET['type']) ? intval($_GET['type']) : 1;
$domainID = intval($_GET['domainID']);

if(intval($_GET['did'])){
    udb::query("DELETE FROM redirects WHERE ID=".intval($_GET['did']));
    header("Location: /cms/redirects.php?domainID=".$domainID."&type=".$type);
    exit;
}

function endecode($str){
    return urlencode(urldecode($str));
}

function showcode($str){
    return str_replace(' ', '+', urldecode($str));
}

function prepareURL($url){
    $link = parse_url($url);
    return implode('/', array_map('endecode', explode('/', ltrim($link['path'], '/')))) . ($link['query'] ? '?' . $link['query'] : '');
}

include_once "bin/top.php";
/*
ini_set('display_errors', 1);
error_reporting(-1);
*/

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	$checkDup = 0;
	if($_POST['old_url'][0]){
		$que = "SELECT `ID` FROM `redirects` WHERE domainID=".$domainID." AND `old_url`='".udb::escape_string(prepareURL($_POST['old_url'][0]))."' ";
		$checkDup = udb::single_value($que);
	}

	if(!$checkDup){
		foreach($_POST['old_url'] as $id=>$val){
			$cp=Array();
			$cp['old_url']=prepareURL($val);
			$cp['new_url']=typemap($_POST['new_url'][$id],"string");			
			$cp['code']=301;
			$cp['type']=$type;
			if($id == 0){
				$cp['domainID']=$domainID;
			}

			$que="SELECT * FROM redirects WHERE ID=".$id." AND type=".$type;
			$checkWord=udb::single_row($que);
			if($checkWord){
				udb::update("redirects", $cp, "ID=".$id." AND domainID = " . $domainID);
			} else if($val!="") {
				udb::insert("redirects", $cp);
			}
		}
	}
	else{
		echo "<div class='errorDup'>!!!Duplicate url</div>";
	}

}
$where=Array();
$where[]="domainID = ".$domainID;
if(trim($_GET['freeSearch'])){
    $freeSearch=typemap($_GET['freeSearch'],"string");
    $freeSearchEncode = udb::escape_string(urlencode($freeSearch));
    $where[]=" (old_url LIKE '%".$freeSearch."%' OR new_url LIKE '%".$freeSearch."%' OR old_url like '%".$freeSearchEncode."%')";
}


$pageNum = intval($_GET["pageNum"])? intval($_GET["pageNum"]) : 1;
$que="SELECT COUNT(*) as TOTALP FROM redirects WHERE ".implode(" AND ", $where);
$totalPages = udb::full_list($que);
$pageTotal = 50;
$totalPages = ceil($totalPages[0]['TOTALP']/$pageTotal);


$que="SELECT * FROM redirects WHERE ".implode(" AND ", $where)." AND type=".$type."  ORDER BY ID LIMIT ".(($pageNum-1)*$pageTotal).",".($pageTotal);
$redirects=udb::full_list($que);

$domians = udb::key_row("SELECT * FROM `domains` WHERE 1",'domainID');

foreach ($domians as $k=>$domain) {
    if($domain['domainMenu'] == 0) unset($domians[$k]);
}

switch($type){

 case 1: $title="הפניות 301"; break;
 case 2: $title="הפניות 301 אתר ישן"; break;

}

?>

    <div class="manageItems" id="manageItems">
        <h1><?=$title?></h1>
		<div class="miniTabs">
			<?php foreach($domians as $key=>$mlist){ ?>
				<div class="tab<?=$key==$_GET['domainID']?" active":""?>" onclick="window.location.href='/cms/redirects.php?domainID=<?=$key?>&type=<?=$_GET['type']?>'"><p><?=$mlist['domainName']?></p></div>
			<?php } ?>
		</div>
        <div class="filter" style="margin-top:0;border-top:1px solid #fff;">
            <h2>חיפוש הפניה</h2>
            <form method="get">
                <div>
					<input type="hidden" name="type" value="<?=$type?>">
                    <input type="hidden" name="domainID" value="<?=$domainID?>">
                    <input type="search" name="freeSearch" <?php if(isset($freeSearch)){ echo 'value="'.$freeSearch.'"'; }?> placeholder="חופשי" autocomplete="off">
                    <input type="submit" value="חפש">
                </div>
            </form>
        </div>
        <div class="numbers">
            <?for($i=1;$i<=$totalPages;$i++){?>
                <input class="pageNum <?=$pageNum==$i? "active" : ""?>" value="<?=$i?>" onclick="window.location.href='?pageNum=<?=$i?>&domainID=<?=$domainID?>'">
            <?}?>
        </div>
        <form method="POST">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>קישור ישן</th>
                    <th>קישור חדש</th>
                    <!-- <th>code</th> -->
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>חדש</td>
                    <td><input type="text" name="old_url[0]" value="" style="width:100%;box-sizing:border-box;"></td>
                    <td><input type="text" name="new_url[0]" value="" style="width:100%;box-sizing:border-box;direction:ltr;text-align:right"></td>
                    <!-- <td><input type="text" name="code[0]" value="" style="width:100%;box-sizing:border-box;"></td> -->

                    <td></td>
                </tr>
                <?php foreach($redirects as $red){ ?>
                    <tr>
                        <td><?=$red['ID']?></td>
                        <td><input type="text" name="old_url[<?=$red['ID']?>]" value="/<?=showcode($red['old_url'])?>" style="width:100%;box-sizing:border-box;"></td>
                        <td><input type="text" name="new_url[<?=$red['ID']?>]" value="<?=$red['new_url']?>" style="width:100%;box-sizing:border-box;direction:ltr;text-align:right"></td>
						<?php /*
                        <td><input type="text" name="code[<?=$red['ID']?>]" value="<?=$red['code']?>" style="width:100%;box-sizing:border-box;"></td>
						*/?>
                        <td onclick="window.location.href='?did=<?=$red['ID']?>&type=<?=$type?>&domainID=<?=$domainID?>'">מחק</td>
                    </tr>
                <?php } ?>
                <tr><td colspan="5">
                        <input type="submit" value="Save" class="submit">
                    </td></tr>
                </tbody>
            </table>

        </form>
    </div>
<style>
    .manageItems table > thead > tr > th:nth-child(1){width:2%}
    .manageItems table > thead > tr > th:nth-child(4){width:6%}
    .manageItems table > thead > tr > th:nth-child(5){width:6%}
	.errorDup{ display: block;text-align: center;font-size: 20px;color: red;width: 200px;margin: 10px auto;background: #fff;padding: 10px;border: 1px solid #c6c1c1;}
</style>
<?php
include_once "bin/footer.php";
