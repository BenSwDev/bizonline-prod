										  <?php
/**
 * @var int $siteID
 */

/*
###########################
$siteID = intval($_GET['sid'] ?: $_CURRENT_USER->active_site());
if(!$_CURRENT_USER->has($siteID)){
    echo 'Access denied';
    return;
}
###########################
*/

$masterID = intval($_GET['tid']);
$payment = new SalaryMaster($masterID);

$master = udb::single_row("SELECT `therapists`.*, sites.siteName AS spaName FROM `therapists` LEFT JOIN sites USING (siteID) WHERE `therapistID` = " . $masterID );
//print_r($_CURRENT_USER->sites());
if (!$master || !(in_array($master["siteID"], $_CURRENT_USER->sites())) || (!$master['phone']) && strpos($sid,',')){
    echo 'Access denied to master ' . $masterID;
    return;
}
//print_r($master);

$siteSelect = "";


if(strpos($sid,',')){
$masterID = udb::single_value("SELECT GROUP_CONCAT(`therapistID`) FROM  therapists WHERE phone LIKE '".$master['phone']."' AND siteID IN (".$sid.")");
}
$master['spaName'] = udb::single_value("SELECT GROUP_CONCAT(sites.siteName SEPARATOR ', ') FROM  therapists LEFT JOIN sites USING (siteID) WHERE therapists.`therapistID` IN (".$masterID.")");
$sitesIDs = udb::full_list("SELECT siteID FROM  therapists WHERE phone LIKE '".$master['phone']."' AND siteID IN (".$_CURRENT_USER->sites(true).") GROUP BY siteID");
?>
<script>
 $('#sid_list li').hide();
 $('#sid_list li:first-child').show();
<?
foreach($sitesIDs as $singleID)	{?>
$('#sid_list li[data-val="<?=$singleID['siteID']?>"]').show();
<?}?>
</script>
<?

list($start, $end) = explode(':', date('Y-m-01:Y-m-t', preg_match('/^20\d\d-[01]\d$/', trim($_GET['month'] ?? '')) ? strtotime($_GET['month'] . '-01') : time()));

$ctotal  = [];
$totals  = [];
//$masters = udb::key_row("SELECT * FROM `therapists` WHERE `siteID` = " . $siteID . " AND `workerType` <> 'fictive' " . ($showAll ? "" : " AND `active` = 1") . " AND (`workStart` IS NULL OR `workStart` <= '" . $end . "') AND (`workEnd` IS NULL OR `workEnd` >= '" . $start . "') ORDER BY `siteName`", 'therapistID');

$que = "SELECT orders.*, treatments.treatmentName, sites.siteName AS spaName
        FROM `orders`  
		LEFT JOIN sites USING (siteID)
		INNER JOIN `orders` AS `parent` ON (orders.parentOrder = parent.orderID)
        INNER JOIN `treatments` ON (orders.treatmentID = treatments.treatmentID)
        WHERE ".$siteSelect." orders.parentOrder > 0 AND orders.parentOrder <> orders.orderID AND orders.status = 1 AND parent.status = 1 
		AND orders.timeFrom BETWEEN '" . $start . " 00:00:00' AND '" . $end . " 23:59:59' AND orders.therapistID IN (" . $masterID . ")
        ORDER BY orders.timeFrom";
$orders = udb::single_list($que);
//echo "<br>".$que;

$extraPays = udb::single_list("SELECT * FROM `therapists_pay_extra` WHERE `therapistID` IN (" . $masterID . ") AND `date` BETWEEN '" . $start . "' AND '" . $end . "' ORDER BY `date` ASC");

//foreach($orders as $order){
//    // if no data yet - setup some defaults
//    if (!isset($totals[$order['therapistID']])){
//        $totals[$order['therapistID']] = [
//            'count'   => 0,
//            'cost'    => 0,
//            'weekday' => ['sum' => 0, 'time' => 0],
//            'weekend' => ['sum' => 0, 'time' => 0],
//            'sum'     => 0,
//            'forSite' => 0,
//            '_pay'    => new MasterPay($order['therapistID'])
//        ];
//    }
//
//    $row =& $totals[$order['therapistID']];
//
//    $row['count'] += 1;
//    $row['cost']  += $order['price'];
//
//    $orderRate = $row['_pay']->get_treat_pay($order['timeFrom'], $order['treatmentLen'], $order['price']);
//    $rowkey = $orderRate['weekend'] ? 'weekend' : 'weekday';
//
//    $row[$rowkey]['sum']  += $orderRate['total'];
//    $row[$rowkey]['time'] += $order['treatmentLen'];
//    $row[$rowkey]['rate']  = $orderRate['rate'];
//
//    $row['sum'] += $orderRate['total'];
//    $row['forSite'] += $order['price'] - $orderRate['total'];
//
//    unset($row);
//}


?>
<div class="sendPop" id="addPop" style="display: none;">
    <div class="container" style="max-width:500px">
        <div class="close" onclick="$('#addPop').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title mainTitle">תוספת תשלום / קנס</div>

        <div class="content">
            <div class="lines">
                <input type="hidden" id="multiplier" value="1">
                <input type="radio" style="display:none" name="multiplier" value="1" id="multiplierP" checked>
                <input type="radio" style="display:none" name="multiplier" value="-1" id="multiplierM">
				<div class="line" style="width:auto;display:flex;justify">
					<label for="multiplierP">תשלום</label>
					<label for="multiplierM">קנס</label>
				</div>
				<div class="line" style="width:auto;display:flex">
                    <input type="text" id="dateAdd" placeholder="תאריך" style="margin:0 20px" readonly />
                    <input type="number" id="sumAdd" oninput="this.value = Math.abs(this.value)" placeholder="סכום" style="margin:0 20px; direction:ltr; text-align:right" />
                    <span style="position: absolute; font-size: 30px; left: 35px;">₪</span>
                </div>
                <div class="line" style="width:auto;display:flex">
                    <input type="text" id="descAdd" placeholder="תיאור" style="margin:0 20px; width:90%; display:inline-block" />
                </div>
                <div id="submitAdd" style="display:inline-block;vertical-align:top;background:#0dabb6;color:#fff;border-radius:5px;cursor:pointer;margin:5px;width:140px;font-size:20px;line-height:40px">
					הוסף
					<span> תשלום</span>
					<span> קנס</span>
				</div>
            </div>
        </div>
    </div>
</div>
<div><a href="?page=monthtotals_dev&month=<?=$_GET["month"]?>">חזרה</a></div>
<div class="searchCms">
	
	<form method="GET">
        <input type="hidden" name="page" value="monthtotals" />
        <input type="hidden" name="tid" value="<?=$masterID?>" />
		<div class="inputWrap">
    		<select name="month">
<?php
$selected = substr($start, 0, 7);
$select = explode('-', date('Y-n', strtotime($end . ' +3 month')));

for($j = 0; $j < 12; ++$j){
    $curr = date('Y-m', mktime(10, 0, 0, $select[1] - $j, 1, $select[0]));
    echo '<option value="' , $curr , '" ' , (strcmp($curr, $selected) ? '' : 'selected') , '>' , implode('/', array_reverse(explode('-', $curr))) , '</option>';
}
?>
            </select>
        </div>

		<div class="btnWrap" style="display:inline-block">
            <a href="?page=monthtotals">נקה</a>
            <input type="submit" value="חפש">
		</div>
	</form>
    
    <div class="excel" id="expExcel">ייצוא לאקסל</div>
	<div class="excel"  onclick="printFunc();">הדפס</div>
</div>
<?if(!strpos($sid,',')){?>
<div id="addNewPayExtra" class="add_reduce" onclick="$('#addPop').fadeIn('fast')">הוסף תשלום/קנס</div>
<?}?>
<table id="monthmaster_table" class="bigDamnTable">
   <thead>
      <tr>	  	
		<th colspan="2"><?=$master["siteName"]?></th>
		<th colspan="<?=strpos($sid,',')? "8" : "7"?>"><?=$master["spaName"]?></th>
	  </tr>
	  <tr class="hideonprint">
	  	<th class=""><input type="checkbox"> הסתר</th>		
	  	<th class=""><input type="checkbox"> הסתר</th>
		<?if(strpos($sid,',')){?>
		<th class=""><input type="checkbox"> הסתר</th>
		<?}?>
	  	<th class=""><input type="checkbox"> הסתר</th>
	  	<th class=""><input type="checkbox"> הסתר</th>
	  	<th class="default-hide"><input type="checkbox"> הסתר</th>
	  	<th class="default-hide"><input type="checkbox"> הסתר</th>
	  	<th class="default-hide"><input type="checkbox"> הסתר</th>
	  	<th class=""><input type="checkbox"> הסתר</th>
	  	<th class="default-hide"><input type="checkbox"> הסתר</th>
	  </tr>
	  <tr>
         <th>ID</th>
         <th>תאריך</th>
		<?if(strpos($sid,',')){?>
		<th>שם הספא</th>
		<?}?>
         <th>סוג הטיפול</th>
         <th>משך הטיפול</th>
         <th>עלות הטיפול</th>
         <th>סופש / חג</th>
         <th>עמלת מטפל</th>
         <th>סכום למטפל</th>
         <th>רווח</th>
      </tr>
   </thead>
   <tbody id="sortRow">
<?php
    $nextExtra = array_shift($extraPays) ?: ['date' => '9999-99-99', 'therapistID' => 0];

    $collect = ['cost' => 0, 'master' => 0, 'rest' => 0];
    foreach($orders as $order){
        $orderDate = substr($order['timeFrom'], 0, 10);

        while(strcmp($orderDate, $nextExtra['date']) > 0){
            if ($nextExtra['agent'] > 500)
                $agentName = udb::single_value("SELECT `name` FROM `biz_users` WHERE `buserID` = " . $nextExtra['agent']);
            elseif ($nextExtra['agent'])
                $agentName = udb::single_value("SELECT `name` FROM `users` WHERE `id` = " . $nextExtra['agent']);
            else
                $agentName = '';
?>
	  <tr>
         <td>&nbsp;</td>
         <td style="text-align:right; direction:ltr"><?=db2date($nextExtra['date'], '.', 2)?></td>
         <td colspan="4"><?=$nextExtra['description']?></td>
         <td><?=$agentName?></td>
         <td style="text-align:right; direction:ltr">₪<?=number_format($nextExtra['sum'] ?? 0)?></td>
         <td style="text-align:right; direction:ltr">₪<?=number_format(-$nextExtra['sum'] ?? 0)?></td>
      </tr>
<?php
            $collect['master'] += $nextExtra['sum'];
            $collect['rest']   -= $nextExtra['sum'];

            $nextExtra = array_shift($extraPays) ?: ['date' => '9999-99-99', 'therapistID' => 0];
        }

        //$orderRate = $payment->get_treat_pay($order['timeFrom'], $order['treatmentLen'], $order['price']);
        $orderRate  = $payment->get_day_salary($orderDate);
        $orderTotal = $payment->get_order_salary($orderDate, $order['treatmentLen'], $order['price']);

        $rate = $orderRate->isHoliday ? $orderRate->rateWeekend : $orderRate->rateRegular;
?>
	  <tr id="o<?=$order['orderID']?>" data-sid="<?=$siteID?>" data-order="<?=$order['orderID']?>">
         <td><?=$order['orderID']?></td>
         <td style="text-align:right; direction:ltr"><?=db2date($orderDate, '.', 2)?> <?=substr($order['timeFrom'], 11, 5)?></td>
         <?if(strpos($sid,',')){?>
		 <td><?=$order['spaName']?></td>
		 <?}?>
		 <td><?=$order['treatmentName']?></td>
         <td><?=$order['treatmentLen']?> דק'</td>
         <td>₪<?=number_format($order['price'] ?? 0)?></td>
         <td><?=($orderRate->isHoliday ? 'כן' : '')?></td>
<?php
        if ($orderRate->type == 'minute'){
?>
		 <td>₪<?=round($rate, 2)?> לדקה</td>
<?php
        } else {
?>
		<td><?=$rate?>% עמלה</td>
<?php
        }
?>
         <td class="totalT">₪<?=number_format($orderTotal, 1)?></td>
         <td>₪<?=number_format($order['price'] - $orderTotal, 1)?></td>
      </tr>
<?php
        $collect['cost'] += $order['price'];
        $collect['master'] += $orderTotal;
        $collect['rest'] += $order['price'] - $orderTotal;
		
		$dayType = $orderRate->isHoliday ? 'weekend' : 'weekday';
		$ctotal[$dayType]['cnt']++;
		$collect[$dayType][$order['treatmentLen']]['cnt']++;
		$collect[$dayType][$order['treatmentLen']]['cost']+=$order['price'];
		$collect[$dayType][$order['treatmentLen']]['master']+=$orderTotal;
		$collect[$dayType][$order['treatmentLen']]['rest']+=$order['price'] - $orderTotal;
    }

    while($nextExtra['therapistID']){
?>
	  <tr>
         <td>&nbsp;</td>
         <td style="text-align:right; direction:ltr"><?=db2date($nextExtra['date'], '.', 2)?></td>
         <td colspan="5"><?=$nextExtra['description']?></td>
         <td style="text-align:right; direction:ltr">₪<?=number_format($nextExtra['sum'] ?? 0)?></td>
         <td style="text-align:right; direction:ltr">₪<?=number_format(-$nextExtra['sum'] ?? 0)?></td>
      </tr>
<?php
        $collect['master'] += $nextExtra['sum'];
        $collect['rest']   -= $nextExtra['sum'];

        $nextExtra = array_shift($extraPays) ?: ['date' => '9999-99-99', 'therapistID' => 0];
    }
?>
      <tr>
         <td style="line-height:1">סה"כ טיפולים: <?=count($orders)?></td>
         <td></td>
         <td></td>
         <td></td>
         <td class="totalT">₪<?=number_format($collect['cost'])?></td>
         <td></td>
         <td></td>
         <td class="totalT">₪<?=number_format($collect['master'], 1)?></td>
         <td class="totalT">₪<?=number_format($collect['rest'], 1)?></td>
      </tr>
	<?if($collect['weekday']){?>
	  <tr>
         <td style="line-height:1">סה"כ אמצ"ש <?=$ctotal['weekday']['cnt']?></td>
         <td></td>
         <td></td>
         <td>
		 <?
		 foreach($collect['weekday'] as $key => $treats){?>
			<?=$key?> דק' - <?=$treats['cnt'];?><br>
		 <?}
		 ?>
		 </td>
         <td></td>
         <td></td>
         <td></td>
         <td></td>
         <td></td>
      </tr>
	<?}?>
	<?if($collect['weekend']){?>
	  <tr>
         <td style="line-height:1">סה"כ סופ"ש <?=$ctotal['weekend']['cnt']?></td>
         <td></td>
         <td></td>
         <td>
		 <?
		 foreach($collect['weekend'] as $key => $treats){?>
			<?=$key?> דק' - <?=$treats['cnt'];?><br>
		 <?}
		 ?>
		 </td>
         <td></td>
         <td></td>
         <td></td>
         <td></td>
         <td></td>
      </tr>
	 <?}?>
	  
   </tbody>
</table>
<style id="reportstyle">
.add_reduce{display:block;vertical-align:top;background:#0dabb6;color:#fff;border-radius:5px;cursor:pointer;margin:5px;width:200px;font-size:20px;line-height:40px; float:left}
td.noprint{opacity:0.2;background:#ccc !important}
table.bigDamnTable .hideonprint th {padding: 0;height: auto !important;margin-bottom: -4px;position: relative !important;top: 5px;z-index: 9;}
.sendPop .container>.content .line{display:flex;justify-content:center}
.sendPop .container>.content .line label {display: flex;padding: 0 16px;margin: 0 5px;background: white;height: 40px;align-items: center;cursor: pointer;border-radius:25px;box-sizing:border-box;border:1px solid #0dabb6;}
.sendPop .container>.content .line label::before {content: "";width: 20px;height: 20px;border: 1px solid;margin-left: 6px;border-radius: 50%;box-sizing: border-box;box-shadow: 0 0 0 2px white inset;}
#multiplierP:checked ~  .line label:nth-child(1), 
#multiplierM:checked  ~ .line label:nth-child(2){
    background:#cfeef0
}
#multiplierP:checked ~  .line label:nth-child(1)::before, 
#multiplierM:checked  ~ .line label:nth-child(2)::before{
    background:#0dabb6
}

#multiplierP:checked ~  #submitAdd span:nth-child(2), 
#multiplierM:checked  ~ #submitAdd span:nth-child(1){
    display:none;
}

.sendPop .container{height:340px}
@media print{
.noprint, .hideonprint{display:none}
}
</style>
<script>

$('input[name="multiplier"]').on('change',function(){
	debugger;
	if($(this).is(':checked')){$('#multiplier').val($(this).val())}
})

function printFunc() {
		
		var divToPrint = document.getElementById('monthmaster_table');
		var htmlToPrint = '' +
			'<style type="text/css">' +
			'*{direction:rtl;font-family:"Arial"}' +
			'table{border-collaps:collapse;border-spacing:0}' +
			'td,th{border:1px solid black;padding:5px}' +
			'.noprint, .hideonprint{display:none}' +
			'</style>';
		htmlToPrint += divToPrint.outerHTML;
		newWin = window.open("");
		//newWin.document.write("<h3 align='center'>Print Page</h3>");
		newWin.document.write(htmlToPrint);
		newWin.print();
		newWin.close();
    }

$(function(){
	
	$('.hideonprint input').on('change',function(){
		//debugger;
		var nthchild = $(this).parent().index() + 1;
		if($(this).is(':checked')){
			$('table.bigDamnTable tbody td:nth-child('+nthchild+'), table.bigDamnTable thead th:nth-child('+nthchild+')').addClass('noprint');
		}else{
			$('table.bigDamnTable tbody td:nth-child('+nthchild+'), table.bigDamnTable thead th:nth-child('+nthchild+')').removeClass('noprint');
		}
	});

	

	

	

    $('#dateAdd').datepicker();
	<?if(!strpos($sid,',')){?>
    $('#submitAdd').on('click', function(){
        Swal.fire({icon:"question", title:"להוסיף תשלום/קנס ?", showCancelButton:true, cancelButtonText:"ביטול", confirmButtonText:"כן"}).then(function(res){
            if (res.isConfirmed){
                let data = {act:"masterExtraAdd", tid:<?=$masterID?>};

                $('#addPop').find('input').each((i, e) => data[e.id] = e.value);

                $.post('ajax_global_spa.php', data).then(function(rp){
                    if (!rp || rp.status === undefined || parseInt(rp.status))
                        return Swal.fire({icon:"error", title:"שגיאה!", text:rp.error || rp._txt || "Unknown error"});
                    window.location.reload();
                });
            }
        });
    });
	<?}?>
});

	$('.hideonprint th.default-hide input').each(function(){
		//debugger;
		$(this).trigger('click');
		var nthchild = $(this).parent().index() + 1;
		$('table.bigDamnTable tbody td:nth-child('+nthchild+'), table.bigDamnTable thead th:nth-child('+nthchild+')').addClass('noprint');
	})

    $('#expExcel').on('click', function(){
        $('.noprint, .hideonprint th').addClass('noExl');
		var table = $('table#monthmaster_table');
		if(table && table.length){
			var preserveColors = (table.hasClass('table2excel_with_colors') ? true : false);
			$(table).table2excel({
				exclude: ".noExl",
				name: "Excel Document Name",
				filename: "report_monthmaster" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
				fileext: ".xls",
				exclude_img: true,
				exclude_links: true,
				exclude_inputs: true,
				preserveColors: preserveColors
			});
		} 
		$('.noprint, .hideonprint th').removeClass('noExl');
        // window.location.href = 'ajax_excel_monthtotals.php' + window.location.search;
    });
</script>