<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";







if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
		
	}
    catch (LocalException $e){
        // show error
    } 
}

$que="SELECT * FROM `domains` WHERE 1";
$domains= udb::key_row($que,'domainID');

$where ="1 = 1 ";

if($_GET['free']){
	$where .= " AND (`sites`.`siteName` LIKE '%".$_GET['free']."%' OR `reviews`.`title` LIKE '%".$_GET['free']."%' OR `reviews`.`text` LIKE '%".$_GET['free']."%')";
}

if($_GET['active']!=""){
	$where .= " AND `reviews`.`ifShow`=".intval($_GET['active']); 
}


if($_GET['promoted']!=""){
	$where .= " AND `reviews`.`selected`=".intval($_GET['promoted']); 
}


if($_GET['hostDate']!=""){
	$where .= " AND `reviews`.`day` LIKE '%".intval($_GET['hostDate'])."%'"; 
}



$pager = new CmsPager;
$que = "SELECT SQL_CALC_FOUND_ROWS reviews.*,sites.siteName,sites.publishReviews, reviewsDomains.domainID AS showInDomain  FROM `reviews`  		
		LEFT JOIN sites using(siteID)
		LEFT JOIN reviewsDomains ON (reviews.reviewID = reviewsDomains.reviewID AND reviewsDomains.domainID > 0)
WHERE " . $where . " ORDER BY `reviewID` DESC ". $pager->sqlLimit();
$reviews = udb::full_list($que);


$pager->items_total = udb::single_value("SELECT FOUND_ROWS()");



?>

<style type="text/css">
	input[type="checkbox"]{width: 20px !important;height: 20px !important;-webkit-appearance: checkbox !important;}
	.manageItemfs table > thead > tr > th{width: auto !important;text-align: center;}
	.manageItems table > tbody > tr > td{text-align: center;}
	.filters{padding: 30px 10px;border: 1px solid #000;margin-top: 10px;display: inline-block;}
	.filters .inpWrap{display: inline-block;vertical-align: top;margin:0 10px ;}
	.filters .inpWrap .lbl{display: inline-block;vertical-align: top;line-height: 20px;}
	.filters .inpWrap select{display: inline-block;vertical-align: top;width: 100px;height: 20px;-webkit-appearance: menulist;}
	.filters  input[type="submit"]{width: 50px;cursor: pointer;background: #2aafd4;color: #fff;}
	.submiForm{float: left;width: 80px;line-height: 40px;cursor: pointer;background: #2aafd4;color: #fff;font-size: 18px;margin: 10px 0;}

</style>

<div class="popRoom"><div class="popRoomContent"></div></div>

<div class="manageItems" id="manageItems">
    <h1>חוות דעת</h1>

	<div class="searchCms">
		<form method="GET">
			<input type="text" name="free" placeholder="מלל חופשי" value="<?=$_GET['free']?>">
			<select name="active">
				<option value="">מוצג/לא מוצג</option>
				<option value="1" <?=($_GET['active']==1?"selected":"")?> >מוצג</option>
				<option value="0" <?=(isset($_GET['active']) && $_GET['active']=="0" ?"selected":"")?>>לא מוצג</option>
			</select>
			<select name="promoted">
				<option value="1" <?=($_GET['promoted']==1?"selected":"")?> >מקודם</option>
				<option value="2" <?=($_GET['promoted']==2?"selected":"")?> >לא מקודם</option>
				<option value="0" <?=(!isset($_GET['promoted']) || $_GET['promoted']=="0" ?"selected":"")?>>הכל</option>
			</select>
			<input type="text" name="hostDate" style="width:auto;" class="datepicker" placeholder="תאריך ביקור">

			<a href="/cms/moduls/minisites/allReviews/index.php">נקה</a>
			<input type="submit" value="חפש">	
		</form>
	</div>
	<form method="post">
	<?=$pager->render()?>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>בית העסק</th>	 
			<th>שם הכותב</th>	
			<th>דירוג</th>
			<th>תאריך ביקור</th>
			<th>תאריך הכנסה</th>
			<th>הוזן דרך</th>
			<th>מוצג בדומיין</th>
			<th>מוצג</th>
        </tr>
        </thead>
        <tbody id="sortRow">
		<?php
		if($reviews){
		foreach($reviews as $review) { ?> 
            <tr>
				<input type="hidden" name="id[<?=$review['reviewID']?>]" value="<?=$review['reviewID']?>">
				<td onclick="openPop(<?=$review['reviewID']?>,<?=$review['siteID']?>,<?=$review['domainID']?>)"><?=$review['reviewID']?></td>
				<td onclick="openPop(<?=$review['reviewID']?>,<?=$review['siteID']?>,<?=$review['domainID']?>)"><?=$review['siteName']?></td> 
				<td onclick="openPop(<?=$review['reviewID']?>,<?=$review['siteID']?>,<?=$review['domainID']?>)"><?=$review['name']?></td>				
				<td onclick="openPop(<?=$review['reviewID']?>,<?=$review['siteID']?>,<?=$review['domainID']?>)"><?=$review['avgScore']?></td>				
				<td onclick="openPop(<?=$review['reviewID']?>,<?=$review['siteID']?>,<?=$review['domainID']?>)"><?=date("d-m-Y", strtotime($review['day']))?></td>
				<td onclick="openPop(<?=$review['reviewID']?>,<?=$review['siteID']?>,<?=$review['domainID']?>)"><?=date("d-m-Y", strtotime($review['created']))?></td>
				<td><?
				if($review['orderID']) {
						echo 'Biz order';
					}
					else {
						if($review['via'] == 1 ) {
							echo 'Biz link';							
							$fromUSER = 1;
						}
						else {
							if($review['domainID'] == 0 ) {
							echo 'Biz cms';
							}
							else {
								if($review['domainID'] != 1){
									echo $domains[$review['domainID']]['domainName'];
								}
								else {
									echo 'Biz link';									
									$fromUSER = 1;
								}
								
							}
						}
						
					}
				//=$review['via']?"Bizonline.co.il":"Vii"?>
					</td>
					<td><?=$review['showInDomain']? $domains[$review['showInDomain']]['domainName'] : "לא מוגדר"?></td>
					<td><?=($review['ifShow']==1?"<span class='showYes'></span>":"<span class='showNo'></span>")?><?=($fromUSER && !$review['publishReviews'])? "<b style='color:red;display:inline'>*</b>" : ""?></td>

            </tr>
			<?php } } ?>
        </tbody>
    </table>
	</form>
</div>

<script>

	function openPop(pageID,siteID,domainID){
		$(".popRoomContent").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/reviews/frame.php?pageID='+pageID+'&siteID='+siteID+'&domainID='+domainID+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
		$(".popRoom").show();
		
	}
	function closeTab(){
		$(".popRoomContent").html('');
		$(".popRoom").hide();
		
	}

	$(function() {
		$('.datepicker').datepicker({"dateFormat": "dd-mm-yy"});
	})

</script>
<?php

include_once "../../../bin/footer.php";
?>