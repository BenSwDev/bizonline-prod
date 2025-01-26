<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";
include_once "../../../_globalFunction.php";

include_once "../../../classes/class.CmsPager.php";

$domainID = intval($_GET['domainID']);

if(intval($_GET['delPage'])!=''){
	$mainpageid=intval($_GET['delPage']);

	$que = "SELECT `data` FROM `search` WHERE id=".$mainpageid;
	$searchData = udb::single_value($que);
	$que = "SELECT `id` FROM `category_articles` WHERE `searchField`='".$searchData."'";
	$catID = udb::single_value($que);

	if($catID){
		udb::query("DELETE FROM `category_articles` WHERE id=".$catID."");
		udb::query("DELETE FROM `category_articles_langs` WHERE id=".$catID."");
		udb::query("DELETE FROM `alias_text` WHERE ref=".$catID." AND `table`='category_articles'");
	}

	udb::query("DELETE FROM `search` WHERE id=".$mainpageid."");
	udb::query("DELETE FROM `search_langs` WHERE id=".$mainpageid."");
	udb::query("DELETE FROM `alias_text` WHERE ref=".$mainpageid." AND `table`='search'");

	$pictures = udb::single_row("SELECT * FROM `files` WHERE `table`='search' AND `ref`=".$mainpageid);
	if($pictures){
		unlink('../../../gallery/'.$pictures['src']);
		udb::query("DELETE FROM `files` WHERE ref=".$mainpageid." AND `table`='search'");
	}


}

$where =" domainID=" . $domainID . " ";
if($_GET['free']){
	$where .= "AND `title` LIKE '%".udb::escape_string($_GET['free'])."%' OR `id`='".udb::escape_string($_GET['free'])."'";
}
if($_GET['area']){
	$where .= "AND `data` LIKE '%\"area\":".$_GET['area']."%'";
}
if($_GET['city']){
	$where .= "AND `data` LIKE '%\"city\":".$_GET['city']."%'";
}
if($_GET['attr']){
	$where .= "AND `data` LIKE '%\"attr\":[".$_GET['attr']."%' OR '%,".$_GET['attr']."]%'";
}

switch($_GET['sort'] ?? ''){
    case 'count': $order = '`count` DESC'; break;
    case 'name':  $order = '`title`'; break;
    default:      $order = '`updateDate` DESC';
}

$pager = new CmsPager;

$que="SELECT SQL_CALC_FOUND_ROWS * FROM `search` WHERE " . $where . " AND domainID = " . $domainID ." ORDER BY " . $order . $pager->sqlLimit();
$search= udb::full_list($que);

$pager->items_total = udb::single_value("SELECT FOUND_ROWS()");

$domainsList = DomainList::get();
foreach ($domainsList as $k=>$domain) {
    if($domain['domainMenu'] == 0) unset($domainsList[$k]);
}

unset($domainsList[0]);

$domData  = reset(DomainList::get($domainID));


$que = "SELECT d.attrID 
		FROM `attributes` AS `a` 
		INNER JOIN `attributes_domains` AS `d` 
		ON (a.attrID = d.attrID AND d.domainID = " . $domainID . ") 
		WHERE d.active=1 ";

$all_attr = udb::single_column($que);

//print_r($all_attr);

?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>דפי חיפוש</h1>

	<div class="miniTabs">
		<?php foreach($domainsList as $domain) { ?>
			<div class="tab <?=$_GET['domainID']==$domain['domainID']?'active':''?>  " <?=$_GET['domainID']==$domain['domainID']?'active':''?>  onclick="window.location.href='/cms/moduls/main/search/index.php?domainID=<?=$domain['domainID']?>'"><p><?=$domain['domainName']?></p></div>
		<?php } ?>
	</div>
	<?php
		$areas = udb::full_list("SELECT `TITLE`, `areaID` FROM `areas` WHERE 1");
		$citys = udb::full_list("SELECT `TITLE`, `settlementID` FROM `settlements` WHERE 1");
		$attrs = udb::full_list("SELECT `defaultName`, `attrID` FROM `attributes` WHERE 1");
	?>
	<div class="searchCms">
		<form method="GET">
			<input type="hidden" name="domainID" value="<?=$_GET['domainID']?>">


			<input type="text" name="free" placeholder="שם דף" value="<?=$_GET['free']?>">
			<div  class="secParmLine">
				<select name="area">
					<option value="0">אזור</option>
					<?php foreach($areas as $area){ ?>
					<option value="<?=$area['areaID']?>" <?=($area['areaID']==$_GET['area']?"selected":"")?> ><?=$area['TITLE']?></option>
					<?php } ?>
				</select>
				<select name="city">
					<option value="0">ישוב</option>
					<?php foreach($citys as $city){ ?>
					<option value="<?=$city['settlementID']?>" <?=($city['settlementID']==$_GET['city']?"selected":"")?> ><?=$city['TITLE']?></option>
					<?php } ?>
				</select>
				<select name="attr">
					<option value="0">אבזורים</option>
					<?php foreach($attrs as $attr){ ?>
					<option value="<?=$attr['attrID']?>" <?=($attr['attrID']==$_GET['attr']?"selected":"")?> ><?=$attr['defaultName']?></option>
					<?php } ?>
				</select>
			</div>

			<a href="index.php?domainID=<?=$_GET['domainID']?>">נקה</a>
			<input type="submit" value="חפש">
		</form>
	</div>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0,<?=$domainID?>)">
	</div>
    <?=$pager->render()?>
    מציג <span><?=count($search)?></span> מתוך <span><?=$pager->items_total?></span>
    <table>
        <thead>
        <tr>
            <th onclick="customSort('id')">ID</th>
            <th onclick="customSort('name')">כותרת</th>
            <th onclick="customSort('count')">כמות כניסות</th>
			<th>מוצג/לא מוצג</th>
			<th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="sortRow">
			<?php
            include "fu.php";
            TempActivePage::changeDomoain($domainID);
			if($search){
			foreach($search as $page){
			$data = json_decode($page['data'],true);
            if($domainID == 1 || $domainID == 6) {
                $title = udb::single_value("select title from search_langs where id=".$page['id']);
                if(!$title) $title = searchPageName($data,0);
            }
            else {
                //$title = searchPageName($data,1);
                $title = $page['title'];
            }
			
			$noMatch = 0;
			$line_attr = '';
			$line_attr = $data['attr'];
			
			
			if($line_attr){
				$noMatch = count(array_intersect($line_attr, $all_attr))? 0 : 1;
			}
			
			

			?>
			
            <tr id="<?=$page['id']?>" style="<?=$noMatch? "background:#ffaaaa" : "" ?>" data-data='<?=$page['data']?>'>
                <td><?=$page['id']?> <?//=$line_attr? implode(',',$line_attr) : ""?></td>
                <td onclick="openPop(<?=$page['id']?>, <?=$domainID?>)"><?=outDb($title)?></td>
                <td onclick="openPop(<?=$page['id']?>, <?=$domainID?>)"><?=outDb($page['count'])?></td>
                <td><?=($page['active']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				<td align="center" class="actb">
				<?php if($pageType!=111) { ?>
				<div onClick="getDel(<?=$page['id']?>);" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
				<?php } ?>
				</td>
            </tr>
			<?php }
			} ?>
        </tbody>
    </table>
</div>
    <script src="/user/assets/js/swal.js?v=1"></script>
    <div class="popup delete-order" style="display:none;">
        <div class="popup_container">
            <div class="title">מחיקת חיפוש</div>
            <form class="form" id="delForm">
                <div class="need">הזינו את הסיסמא ולחצו מחיקה</div>
                <div class="inputWrap">
                    <input type="hidden" name="searchID" value="">
                    <input type="password" name="pass" placeholder="הקלידו סיסמא כאן">
                    <label for="pass">סיסמא</label>
                </div>
                <div class="buttons">
                    <div class="submit" onclick="sendDelete('delForm')">מחיקה</div>
                    <div class="cancel" onclick="$('.popup.delete-order').hide(),$('.popup.delete-order input').val('')">סגירה</div>
                </div>
            </form>
        </div>
    </div>
    <style>
        .popup.delete-order {position:fixed;top:0;right:0;left:0;bottom:0;width:100%;height:100%;z-index:2;background:rgba(0,0,0,0.6);text-align:center;}
        .popup.delete-order .popup_container {position:absolute;top:50%;right:50%;width:100%;max-width:500px;padding:10px;box-sizing:border-box;min-height:100px;background:#fff;border-radius:8px;background:#fff;transform:translateY(-50%) translateX(50%)}
        .popup.delete-order .inputWrap {background:#fff;display:block;max-width:200px;margin:0 auto;border-radius:6px;position:relative;height:50px;border:2px solid #ccc;box-sizing:border-box}
        .popup.delete-order .inputWrap input {position:absolute;top:0;right:0;left:0;bottom:0;width:100%;height:100%;background:transparent;border:0;box-sizing:border-box;padding:0 5px;}
        .popup.delete-order .inputWrap label {position:absolute;top:0;right:5px;z-index:2;font-size:14px;}
        .popup.delete-order .form {margin:10px 0 0 0;display:block}
        .popup.delete-order .form .need {text-decoration:underline;font-weight:600;padding-bottom:10px;}
        .popup.delete-order .buttons {display:block;margin:10px auto}
        .popup.delete-order .buttons > div {display:inline-block;cursor:pointer;height:50px;width:100px;border-radius:8px;text-align:center;line-height:50px;color:#fff;font-size:18px;font-weight:500}
        .popup.delete-order .buttons > div.cancel {background:#111}
        .popup.delete-order .buttons > div.submit {background:#ea5656}
    </style>
<script>

function openPop(pageID,domainID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/main/search/frame.php?pageID='+pageID+'&domainID='+domainID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}
function customSort(by){
    window.location.href = '?sort=' + by + '&' + String("<?=addslashes($_SERVER['QUERY_STRING'] ?: ' ')?>").replace(/sort=[^&]*&/, '');
}
function getDel(sid) {
    $('.popup.delete-order input[name="searchID"]').val(sid);
    $('.popup.delete-order').show();
    $('.popup.delete-order input[type="text"]').focus();
}
function sendDelete(formId){
    $('.holder').show();

    $.post('js_sendDelete.php',$('#'+formId).serialize(),function(result){
        if(result.error){
            Swal.fire(result.error);
        }
        else {
            delsearch(parseInt(result.searchID));
        }
    },"JSON");

}
function delsearch(searchID){
    $.post('delSearch.php',{'searchID':searchID},function(){
        Swal.fire('החיפוש נמחק בהצלחה').then(function() {
            $("#" + searchID).remove();
        });
    });

}
</script>

<?php
include_once "../../../bin/footer.php";
