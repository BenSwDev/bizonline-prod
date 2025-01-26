<?php
function hdate($date){
    return typemap(implode('-', array_reverse(explode('/', trim($date)))), 'date');
}

function db2dateD($date){
    return db2date($date, '.');
}


include "partials/setReportRange.php";

/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
$sid = intval($_GET['sid']) ?: $_CURRENT_USER->select_site();
if($sid && !$_CURRENT_USER->has($sid)){
    echo 'Access denied';
    return;
}

$title = "דוחות תקציב";


if($_GET['from']){
    $timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'])))),"date");
}else{
    $timeFrom = date("Y-m-01");
    $_GET['from'] = implode('/',array_reverse(explode('-',trim($timeFrom))));
}

if($_GET['to']){
    $timeUntil = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'])))),"date");
}else{
    $timeUntil = date("Y-m-t");
    $_GET['to'] = implode('/',array_reverse(explode('-',trim($timeUntil))));
}



$timeType = ($_GET['timeType'] == 2) ? "orders.create_date" : "T_orders.timeFrom";

if ($_GET['timeType'] == 2){
	$time_where = " AND orders.create_date >= '" . $timeFrom . " 00:00:00' AND orders.create_date <= '" . $timeUntil . " 23:59:59'";
	$date_type = '`create_date`';
}
else {
	$time_where = " AND orders.`timeFrom` >= '". $timeFrom . " 00:00:00' AND orders.`timeFrom` <= '". $timeUntil . " 23:59:59' ";
	$date_type = '`timeFrom`';
}


$sids_str = $sid ?: $_CURRENT_USER->sites(true);
$daily_ex = array();
	
	$que='SELECT treatmentID, treatmentName FROM `treatments` WHERE 1';	
	$treatments = udb::key_value($que);

	$que = "SELECT orders.orderID, orders.treatmentID, orders.price,	orders.treatmentLen, orders.treatmentClientSex, orders.treatmentMasterSex, gender_self,
			health_declare.settlementID AS hSetID, settlements.TITLE AS setTitle, pOrders.settlementID AS pSetID, pSetts.TITLE AS pSetTitle
			FROM orders 
			LEFT JOIN health_declare ON (orders.orderID = health_declare.orderID)
			LEFT JOIN settlements ON (settlements.settlementID = health_declare.settlementID)
			LEFT JOIN orders AS pOrders ON(orders.parentOrder = pOrders.orderID)
			LEFT JOIN settlements AS pSetts ON (pOrders.settlementID = pSetts.settlementID)
			LEFT JOIN therapists ON (orders.therapistID = therapists.therapistID)
			WHERE orders.siteID IN (" . $sids_str . ") AND orders.status=1  ".$time_where."  AND orders.parentOrder>0 AND orders.parentOrder<>orders.orderID";
    $allTreatments = udb::full_list($que);
	//echo $que;
	//print_r($allTreatments);

    //$daily_ex = $daily_ex_price = $all_ex_types = $length_per_type = $total_lengths = [];

    $settIDs = $settlements = [];

	foreach($allTreatments  as $ex){
		
		$settTitle = 0;		
		$settID = 0;		
		$byParent = "health";
		if($ex['hSetID']){
			$settID = $ex['hSetID'];
			$settTitle = $ex['setTitle'];
		}else if($ex['pSetID']){
			$settID = $ex['pSetID'];
			$settTitle = $ex['pSetTitle'];			
			$byParent = "pOrder";			
		}

		$settIDs[$settID]++;
		$settlements[$settTitle]['src_'.$byParent]['cnt']++;
		$settlements[$settTitle]['src_'.$byParent]['gender'.$ex['treatmentClientSex']]++;
		$settlements[$settTitle]['src_'.$byParent]['genderT'.$ex['treatmentMasterSex']]++;
		$settlements[$settTitle]['src_'.$byParent]['Tgender'.$ex['gender_self']]++;
	}

	$orderby = array_values($settIDs);
	
	array_multisort($orderby,$settlements);

	$settlements = array_reverse($settlements);



//print_r($allTreatments);

?>
<style>

.excel {
    line-height: 44px;
    margin: 10px 5px;
    display: inline-block;
    font-size: 16px;
    color: #0dabb6;
    background: white;
    border: 1px
    #0dabb6 solid;
    padding: 0 10px;
    cursor: pointer;
    border-radius: 10px;
}
</style>

<?
selectReportRange();
?>


<div class="searchOrder">
	<div class="ttl" style="cursor:pointer;margin:-10px;padding:10px" onclick="$('#searchForm').toggleClass('hide');">חפש הזמנות</div>
	<form method="GET" autocomplete="off" action="" class="hide"  id="searchForm">
		<input type="hidden" name="page" value="<?=typemap($_GET['page'] ?? 'orders', 'string')?>" />
        <input type="hidden" name="otype" value="<?=typemap($_GET['otype'] ?? 'order', 'string')?>" />
<?php
    if (!$_CURRENT_USER->single_site){
        $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
        <div class="inputWrap">
            <select name="sid" id="sid" title="שם מתחם">
                <option value="0">כל המתחמים</option>
<?php
        foreach($sname as $id => $name)
            echo '<option value="' , $id , '" ' , ($id == $sid ? 'selected' : '') , '>' , $name , '</option>';
?>
            </select>
		</div>
<?php
    }
?>
        <div class="inputWrap">
            <select name="timeType" id="otype" title="">
                
                <option value="1" <?=($_GET['timeType'] == '1' ? 'selected' : '')?>>לפי תאריך הגעה</option>
                <option value="2" <?=($_GET['timeType'] == '2' ? 'selected' : '')?>>לפי תאריך רכישה</option>
            </select>
        </div>
		<div class="inputWrap">
			<input type="text" name="from" placeholder="מתאריך" class="searchFrom" value="<?=typemap($_GET['from'], 'string')?>" readonly>
		</div>
		<div class="inputWrap">
			<input type="text" name="to" placeholder="עד לתאריך" value="<?=typemap($_GET['to'], 'string')?>" class="searchTo" readonly>
		</div>				

		<a class="clear" href="?page=<?=$_GET['page']?>">נקה</a>
		<input type="submit" value="חפש">
		
	</form>	
</div>

<div class="excel" id="expExcel">ייצוא לאקסל</div> 
<div class="excel" onclick="printData()">הדפסה</div>


<section class="orders" id="tableToPrint" style="max-width:none">
	<div class="last-orders">
		<div class="exp"><span>עדכנו ישוב בהצהרת בריאות</span><span>ישוב עודכן בהזמנה</span></div>
		<div class="report-container" style="font-size:14px">
			<?//print_r($gender);?>
			<?//print_r($genderT);?>
			<?//print_r($settlements);?>
			<table class="reports" id="reports">
				<thead>
					<tr class='sticky top'>
						<th>ישוב</th>
						<th>סה"כ</th>
						<th>נשים</th>
						<th>גברים</th>
						<th>העדיפו מטפלת</th>
						<th>העדיפו מטפל</th>
						<th>ללא העדפה</th>
						<th>קיבלו מטפלת</th>
						<th>קיבלו מטפל</th>
					</tr>
				</thead>
				<tbody>
					<?foreach($settlements as $key => $sett){?>
					<tr>
						<td><b><?=$key?: "לא צויין ישוב"?></b></td>
						<td>
							<div><?=$sett['src_health']['cnt']+$sett['src_pOrder']['cnt']?></div>
							<?if($key){?><span><?=$sett['src_health']['cnt']?? 0?></span><b>---</b><span><?=$sett['src_pOrder']['cnt']?? 0?></span><?}?>
						</td>
						<td>
							<div><?=$sett['src_health']['gender2']+$sett['src_pOrder']['gender2']?></div>
							<?if($key){?><span><?=$sett['src_health']['gender2']?? 0?></span><b>---</b><span><?=$sett['src_pOrder']['gender2']?? 0?></span><?}?>
						</td>
						<td>
							<div><?=$sett['src_health']['gender1']+$sett['src_pOrder']['gender1']?></div>
							<?if($key){?><span><?=$sett['src_health']['gender1']?? 0?></span><b>---</b><span><?=$sett['src_pOrder']['gender1']?? 0?></span><?}?>
						</td>
						<td>
							<div><?=$sett['src_health']['genderT2']+$sett['src_pOrder']['genderT2']?></div>
							<?if($key){?><span><?=$sett['src_health']['genderT2']?? 0?></span><b>---</b><span><?=$sett['src_pOrder']['genderT2']?? 0?></span><?}?>
						</td>
						<td>
							<div><?=$sett['src_health']['genderT1']+$sett['src_pOrder']['genderT1']?></div>
							<?if($key){?><span><?=$sett['src_health']['genderT1']?? 0?></span><b>---</b><span><?=$sett['src_pOrder']['genderT1']?? 0?></span><?}?>
						</td>						
						<td>
							<div><?=$sett['src_health']['genderT0']+$sett['src_pOrder']['genderT0']?></div>
							<?if($key){?><span><?=$sett['src_health']['genderT0']?? 0?></span><b>---</b><span><?=$sett['src_pOrder']['genderT0']?? 0?></span><?}?>
						</td>
						<td>
							<div><?=$sett['src_health']['Tgender2']+$sett['src_pOrder']['Tgender2']?></div>
							<?if($key){?><span><?=$sett['src_health']['Tgender2']?? 0?></span><b>---</b><span><?=$sett['src_pOrder']['Tgender2']?? 0?></span><?}?>
						</td>
						<td>
							<div><?=$sett['src_health']['Tgender1']+$sett['src_pOrder']['Tgender1']?></div>
							<?if($key){?><span><?=$sett['src_health']['Tgender1']?? 0?></span><b>---</b><span><?=$sett['src_pOrder']['Tgender1']?? 0?></span><?}?>
						</td>

					</tr>
					<?
					if($key){
						$totals['src_health']['cnt']+=$sett['src_health']['cnt'];
						$totals['src_health']['gender2']+=$sett['src_health']['gender2'];
						$totals['src_health']['gender1']+=$sett['src_health']['gender1'];
						$totals['src_health']['genderT2']+=$sett['src_health']['genderT2'];
						$totals['src_health']['genderT1']+=$sett['src_health']['genderT1'];
						$totals['src_health']['genderT0']+=$sett['src_health']['genderT0'];
						$totals['src_health']['Tgender2']+=$sett['src_health']['Tgender2'];
						$totals['src_health']['Tgender1']+=$sett['src_health']['Tgender1'];
					}
					$totals['src_pOrder']['cnt']+=$sett['src_pOrder']['cnt'];
					$totals['TOTAL']['cnt']+=$sett['src_health']['cnt']+$sett['src_pOrder']['cnt'];
					
					
					$totals['src_pOrder']['gender2']+=$sett['src_pOrder']['gender2'];					
					$totals['TOTAL']['gender2']+=$sett['src_health']['gender2']+$sett['src_pOrder']['gender2'];
					
					$totals['src_pOrder']['gender1']+=$sett['src_pOrder']['gender1'];				
					$totals['TOTAL']['gender1']+=$sett['src_health']['gender1']+$sett['src_pOrder']['gender1'];
					
					
					$totals['src_pOrder']['genderT2']+=$sett['src_pOrder']['genderT2'];				
					$totals['TOTAL']['genderT2']+=$sett['src_health']['genderT2']+$sett['src_pOrder']['genderT2'];
					
					$totals['src_pOrder']['genderT1']+=$sett['src_pOrder']['genderT1'];				
					$totals['TOTAL']['genderT1']+=$sett['src_health']['genderT1']+$sett['src_pOrder']['genderT1'];
					
					
					$totals['src_pOrder']['genderT0']+=$sett['src_pOrder']['genderT0'];				
					$totals['TOTAL']['genderT0']+=$sett['src_health']['genderT0']+$sett['src_pOrder']['genderT0'];

					$totals['src_pOrder']['Tgender2']+=$sett['src_pOrder']['Tgender2'];				
					$totals['TOTAL']['Tgender2']+=$sett['src_health']['Tgender2']+$sett['src_pOrder']['Tgender2'];

					$totals['src_pOrder']['Tgender1']+=$sett['src_pOrder']['Tgender1'];				
					$totals['TOTAL']['Tgender1']+=$sett['src_health']['Tgender1']+$sett['src_pOrder']['Tgender1'];
						
					}?>
					<tr class='sticky bottom total'>
						<td>סה"כ</td>
						<td>
							<div><?=$totals['TOTAL']['cnt']?></div>
							<span><?=$totals['src_health']['cnt']?? 0?></span><b>---</b><span><?=$totals['src_pOrder']['cnt']?? 0?></span>
						</td>
						<td>
							<div><?=$totals['TOTAL']['gender2']?></div>
							<span><?=$totals['src_health']['gender2']?? 0?></span><b>---</b><span><?=$totals['src_pOrder']['gender2']?? 0?></span>
						</td>
						<td>
							<div><?=$totals['TOTAL']['gender1']?></div>
							<span><?=$totals['src_health']['gender1']?? 0?></span><b>---</b><span><?=$totals['src_pOrder']['gender1']?? 0?></span>
						</td>
						<td>
							<div><?=$totals['TOTAL']['genderT2']?></div>
							<span><?=$totals['src_health']['genderT2']?? 0?></span><b>---</b><span><?=$totals['src_pOrder']['genderT2']?? 0?></span>
						</td>
						<td>
							<div><?=$totals['TOTAL']['genderT1']?></div>
							<span><?=$totals['src_health']['genderT1']?? 0?></span><b>---</b><span><?=$totals['src_pOrder']['genderT1']?? 0?></span>
						</td>
						<td>
							<div><?=$totals['TOTAL']['genderT0']?></div>
							<span><?=$totals['src_health']['genderT0']?? 0?></span><b>---</b><span><?=$totals['src_pOrder']['genderT0']?? 0?></span>
						</td>
						<td>
							<div><?=$totals['TOTAL']['Tgender2']?></div>
							<span><?=$totals['src_health']['Tgender2']?? 0?></span><b>---</b><span><?=$totals['src_pOrder']['Tgender2']?? 0?></span>
						</td>
						<td>
							<div><?=$totals['TOTAL']['Tgender1']?></div>
							<span><?=$totals['src_health']['Tgender1']?? 0?></span><b>---</b><span><?=$totals['src_pOrder']['Tgender1']?? 0?></span>
						</td>
					</tr>
				</tbody>

			</table>
		</div>
	</div>
</section>

<style id="theStyle">
.exp{width:300px;display: flex;}
.exp > span{text-align:center;width:50%;font-size:14px;background:#c3dbc3;display:inline-block;color:rgba(0,0,0,0.7);padding:10px;box-sizing:border-box;display: flex;align-items: center;justify-content: center;}
.exp > span + span{;background:pink}

.item.order.isSpa td.f {direction:ltr; text-align:right}
.item.order.isSpa td.f.rtl {direction:rtl}

.orders_num.o-ctrl {background: #c9f2fd;}
.orders_num {cursor: pointer;position: relative;}
.orders_num.o-ctrl::after {opacity: 1;}
.orders_num.o-up::after {opacity: 0.2;}
.orders_num::after {content: "";width: 6px;height: 6px;box-sizing: border-box;border-left: 2px black solid;border-bottom: 2px black solid;display: block;position: absolute;bottom: 0;margin: 0 auto;left: 0;right: 0;transform: rotate(-45deg);opacity: 0;}
.orders_num.o-down::after {opacity: 0.5;transform: rotate(135deg);}

.report-container{max-height:calc(100vh - 250px);overflow:auto;padding-left:20px;clear:both;position:relative;top:10px}
.reports{font-size:14px;border-collapse:collapse}
.reports td, .reports th{padding:5px;vertical-align:middle;border:1px #ccc solid;text-align:right;min-width:80px;max-width:80px;}
.reports td.number{direction:ltr}
.reports .sticky{position:sticky;background:white;}
.reports .sticky.top{top:0}
.reports .sticky.bottom{bottom:0}
.reports .bottom td{font-weight:bold;vertical-align:top}
.reports tr:hover{background:#cfeef0;cursor:pointer}
.reports .agents{display:none}
.reports .makor{display:none}
.reports .payTypes{display:none}
.reports .total{display:table-row }
.reports td > div{text-align:center;font-weight:bold}
.reports td > span{text-align:center;width:40px;font-size:12px;background:#c3dbc3;display:inline-block;color:rgba(0,0,0,0.7)}
.reports td > span ~ span{;background:pink}
.reports td > span + b{display:none}
.last-orders .btns {float: left;}
.last-orders .btns > div {display: inline-block;font-size: 16px;line-height: 34px;padding: 0 20px;margin: 0 2px;border: 1px #0dabb6 solid;color: #999;font-weight: normal;background: white;border-radius: 10px;cursor: pointer;}
.last-orders .btns > div.active {color: white;background: #0dabb6;}
.lengths{border-bottom:1px #ccc solid}
.reports:not(.show_lengths) .lengths{display:none}
.reports:not(.show_lengths) .len-btn span:nth-child(2){display:none}
.reports.show_lengths .len-btn span:nth-child(1){display:none}
.len-btn{background:#0dabb6;color:white;padding:10px;cursor:pointer;font-weight:bold;border-radius:10px;font-size:14px;text-align:center}

 /*
.reports td:hover {position: relative;}
.reports td:hover::before {position: absolute;left: 0;right: 0;top: -100000px;bottom: -100000px;background: #cfeef0;content: "";z-index: -99;}
*/
</style>

<script>
 
	function printData() {
            var styleToPrint = document.getElementById("theStyle");
            var divToPrint = document.getElementById("tableToPrint");
            newWin = window.open("");
            newWin.document.write(styleToPrint.outerHTML);
            newWin.document.write("<style>.report-container{max-height:none} *{direction:rtl;font-family:'Arial';font-size:10px !important} td,th{ border: 1px solid black !important;padding: 2px !important;width: auto !important;}table{border-collapse:collapse}</style>")
            newWin.document.write(divToPrint.outerHTML);   
            newWin.print();	
            newWin.close();
            
	}

$('#expExcel').on('click', function(){
    
		var table = $('table#reports');
		if(table && table.length){
			var preserveColors = (table.hasClass('table2excel_with_colors') ? true : false);
			$(table).table2excel({
				exclude: ".noExl",
				name: "Excel Document Name",
				filename: "report_treatments" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
				fileext: ".xls",
				exclude_img: true,
				exclude_links: true,
				exclude_inputs: true,
				preserveColors: preserveColors
			});
		}    
	// window.location.href = 'ajax_excel_reports_treatments.php' + window.location.search;
    });

$('.orders_num').click(function(){
    var table = $(this).parents('table').eq(0)
    var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()))
    this.asc = !this.asc
    $(".orders_num").removeClass("o-ctrl");
	$(this).addClass('o-ctrl');
	if (!this.asc){
		rows = rows.reverse();
		$(this).removeClass('o-up');
		$(this).addClass('o-down');
	}else{		
		$(this).addClass('o-up');
		$(this).removeClass('o-down');
	}
    for (var i = 0; i < rows.length; i++){table.append(rows[i])}
})
function comparer(index) {
    return function(a, b) {
        var valA = getCellValue(a, index).replace(/[^\d.-]/g, ''), valB = getCellValue(b, index).replace(/[^\d.-]/g, '')
        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB)
    }
}
function getCellValue(row, index){ return $(row).children('td').eq(index).text() }


$('.reports .agents td').click(function(){
	//debugger;
	var uid = $(this).parent().data('userid');
	$('.reports tbody tr').css('display','none');
	$(".reports tr[data-userid='"+uid+"']").css('display','table-row');
	$(this).parent().addClass('sticky bottom');
});

$('.reports .makor td').click(function(){
	//debugger;
	var uid = $(this).parent().data('makorid');
	$('.reports tbody tr').css('display','none');
	$(".reports tr[data-makorid='"+uid+"']").css('display','table-row');
	$(this).parent().addClass('sticky bottom');
});

$('.reports .payTypes td').click(function(){
	
	var pid = $(this).parent().data('paytype');
	$('.reports tbody tr').css('display','none');	
	$(this).parent().css('display','table-row');
	$(this).parent().addClass('sticky bottom');
	$(".reports tbody tr").each(function(){
		//debugger;
		var ptypes = $(this).data('paytypes');
		if (ptypes.indexOf(pid) >= 0){			
			$(this).css('display','table-row');
		}
	})
	
});

function show_agents(){
	
	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
	$('.reports tbody tr').css('display','none');
	$(".reports tbody .agents").css('display','table-row');
	$("#totalall").css('display','table-row');
	$("#btns-reports > div").removeClass('active');
	$("#b-agents").addClass('active');
	

}

function show_makor(){
	
	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
	$('.reports tbody tr').css('display','none');
	$(".reports tbody .makor").css('display','table-row');
	$("#totalall").css('display','table-row');
	$("#btns-reports > div").removeClass('active');
	$("#b-makor").addClass('active');
	

}

function show_paytypes(){
	//debugger;	
	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
	$('.reports tbody tr').css('display','none');
	$(".reports tbody .payTypes").css('display','table-row');
	$("#totalall").css('display','table-row');
	$("#btns-reports > div").removeClass('active');
	$("#b-paytypes").addClass('active');
	

}

function show_all(){	
	$('.reports tbody tr:not(#totalall)').removeClass('sticky bottom')
	$('.reports tbody tr').attr('style','');
	$("#btns-reports > div").removeClass('active');
	$("#b-all").addClass('active');
}

</script>