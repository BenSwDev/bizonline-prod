<?php
include_once "bin/system.php";
include_once "bin/top.php";

$pager = new CmsPager;



$pager->items_total = udb::single_value("SELECT FOUND_ROWS()");


if ('POST' == $_SERVER['REQUEST_METHOD']){
/*
    try {

		foreach($_POST['id'] as $id){
			$data = [
				'payed' =>  ($_POST['pay'][$id]?1:0),
				'remarks' => trim(str_replace('{:~:}', "\n", filter_var(str_replace("\n", '{:~:}', $_POST['notes'][$id]), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW))),
				
			];
			udb::update('log_purchase', $data, '`id` = ' . $id);		
		}
		
	}
    catch (LocalException $e){
        // show error
    } 
*/
}


$where ="1 = 1 ";

if($_GET['month']){
	$where .= "AND `log_purchase`.`purchase_date` >= '".$_GET['month']."-01' AND `log_purchase`.`purchase_date` <  '".$_GET['month']."-01' + INTERVAL 1 MONTH";
}

if($_GET['site']!=""){
	$where .= "AND `log_purchase`.`siteID`=".intval($_GET['site']); 
}

if($_GET['accountType']!=""){
	$where .= "AND `log_purchase`.`type`='".trim(filter_var($_GET['accountType'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW))."'"; 
}

$que = "SELECT SQL_CALC_FOUND_ROWS `log_purchase`.*, `sites`.`siteName`, `users`.`name` FROM `log_purchase`
INNER JOIN `sites` USING (siteID)
LEFT JOIN `users` ON (`log_purchase`.`adminID` = `users`.`id`)
WHERE " . $where . " ORDER BY `purchase_date` DESC ". $pager->sqlLimit();
$purchases = udb::full_list($que);




$que =  "SELECT `log_purchase`.`siteID`, `sites`.siteName FROM `log_purchase` INNER JOIN `sites` USING (`siteID`) WHERE 1 GROUP BY siteID ORDER BY siteID";
$sitesList = udb::full_list($que);

$que = "SELECT MIN(`purchase_date`) FROM `log_purchase` WHERE 1";
$minDate = udb::single_value($que);

$year = substr($minDate,0,4);
$mouth = substr($minDate,5,2);
$curYear = date("Y");
$curMonth = date("m");


?>

<style type="text/css">
	input[type="checkbox"]{width: 20px !important;height: 20px !important;-webkit-appearance: checkbox !important;}
	.manageItems table > thead > tr > th{width: auto !important;text-align: center;}
	.manageItems table > tbody > tr > td{text-align: center;}
	.filters{padding: 30px 10px;border: 1px solid #000;margin-top: 10px;display: inline-block;}
	.filters .inpWrap{display: inline-block;vertical-align: top;margin:0 10px ;}
	.filters .inpWrap .lbl{display: inline-block;vertical-align: top;line-height: 20px;}
	.filters .inpWrap select{display: inline-block;vertical-align: top;width: 100px;height: 20px;-webkit-appearance: menulist;}
	.filters  input[type="submit"]{width: 50px;cursor: pointer;background: #2aafd4;color: #fff;}
	.submiForm{float: left;width: 80px;line-height: 40px;cursor: pointer;background: #2aafd4;color: #fff;font-size: 18px;margin: 10px 0;}

</style>

<div class="pagePop"><div class="pagePopCont"></div></div>

<div class="manageItems" id="manageItems">
    <h1>סטטיסטיקות</h1>
	<form method="get" class="filters">
		<div class="inpWrap">
			<div class="lbl">צימרים</div>	
			<select name="sites">
				<option value="">כל הרשימות</option>
				<option value=""></option>
			</select>
		</div>
		<div class="inpWrap">
			<div class="lbl">מ - </div>	
			<input type="text" name="from" value="" readonly class="datePick">
		</div>
		<div class="inpWrap">
			<div class="lbl">עד - </div>	
			<input type="text" name="to" value="" readonly class="datePick">
		</div>
		<input type="submit" value="סנן">
	</form>
	<form method="post">
	<?=$pager->render()?>
    <table>
        <thead>
        <tr>
            <th>מס' צימר</th>
            <th>צימר</th>
            <th>חבילה חודשית</th>
			<th>מטרה יומית</th>
			<th>שימוש יומי</th>
			<th>שימוש %</th>
			<th>סה"כ שימוש</th>
			<th>סה"כ שימוש %</th>
        </tr>
        </thead>
        <tbody id="sortRow">
		<?php
		if($purchases){
		foreach($purchases as $purchase) { ?>
            <tr>
				<input type="hidden" name="id[<?=$purchase['id']?>]" value="<?=$purchase['id']?>">
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
            </tr>
			<?php } } ?>
        </tbody>
    </table>
	<input type="submit" value="שלח" class="submiForm">
	</form>
</div>

<script>
function openPop(pageID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="frame.php?pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}


$(".datePick").datepicker({
	format:"dd/mm/yyyy",
	changeMonth:true
});

</script>
<?php

include_once "bin/footer.php";
?>