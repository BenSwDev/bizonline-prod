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
$time_where = "";
$time_having = "";

if ($_GET['timeType'] == 2){
	$time_where = " AND o.create_date >= '" . $timeFrom . " 00:00:00' AND o.create_date <= '" . $timeUntil . " 23:59:59'";
	$date_type = '`create_date`';
}
else {	
	$time_having = " HAVING(`daydate` >= '". $timeFrom . " 00:00:00' AND `daydate` <= '". $timeUntil . " 23:59:59') ";
	$date_type = '`timeFrom`';
}


$sids_str = $sid ?: $_CURRENT_USER->sites(true);
$daily_ex = $daily_ex_price = $all_ex_types = array();
	
	$que = "SELECT extraID, extraName FROM treatmentsExtras 
                WHERE  1 ORDER BY showOrder";
	$extrasNames = udb::key_value($que);

	$que = "SELECT `o`.orderID, `o`.extras,	 
			CAST(MIN(IF(T_orders.orderID IS NOT NULL,IF(T_orders.".$date_type." = '0000-00-00 00:00:00', NULL, T_orders.".$date_type."), `o`.timeFrom)) AS DATE) AS daydate
			FROM orders AS `o`
			INNER JOIN orders AS T_orders ON (T_orders.parentOrder = o.orderID)
			WHERE o.siteID IN (" . $sids_str . ") AND o.status=1  ".$time_where." AND `o`.extras IS NOT NULL AND `o`.extras NOT LIKE ''
			GROUP BY o.orderID ".$time_having." 
			ORDER BY daydate";
    $oextras = udb::full_list($que);
	//echo $que."<br>";
	//print_r($oextras);
	foreach($oextras as $extra){
		$exx = json_decode($extra['extras'], true) ;	
		
		if(is_array($exx)){
			foreach($exx['extras']  as $key => $ex){				
				if($date_type == 'timeFrom')
					$extra['daydate'] = $extra['timeFrom2'];
				if($extrasNames[$key]){
					if($ex['price']){
						//print_r($ex).PHP_EOL;
						$daily_ex[$extra['daydate']][$key] += $ex['count'];
						$daily_ex_price[$extra['daydate']][$key] += $ex['price']*$ex['count'];
						$all_ex_types[$key] = $extrasNames[$key];
					}
				}
			}
		}
	}



//print_r($daily_ex);

?>

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


<section class="orders" style="max-width:none">
	<div class="last-orders">
		
		<div class="report-container" id="tableToPrint">
			<table class="reports" id="reports">
				<thead>
				<tr class='sticky top'>
					<th>תאריך <?=($_GET['timeType'] == '2')? "רכישה" : "הגעה" ?></th>
					<?foreach($all_ex_types as $ex_type){?>
					<th style='width:70px'><?=$ex_type?></th>
					<?}?>
                    <th>סה"כ</th>
				</tr>
				</thead>
				<tbody>
				<?foreach($daily_ex as $day_date => $daily_list){?>
				
				<tr>
					<td><?=implode('/',array_reverse(explode('-',$day_date)))?></td>
					<?foreach($all_ex_types as $k => $ex_type){?>
						<td><b><?=$daily_list[$k]?: "-" ?></b><br><?=$daily_ex_price[$day_date][$k]? "₪".number_format($daily_ex_price[$day_date][$k]) : ""?></td>
					<?
						$type_total[$k]+= $daily_list[$k];
						$type_total_price[$k]+= $daily_ex_price[$day_date][$k];

						$dayly_total[$day_date]+=$daily_list[$k];	
						$dayly_total_price[$day_date]+=$daily_ex_price[$day_date][$k];	
					}?>
					<td><b><?=$dayly_total[$day_date]?></b><br>₪<?=number_format($dayly_total_price[$day_date])?></td>
				</tr>
				<?}?>
				<tr class='sticky bottom total'>					
					<td></td>
					<?foreach($all_ex_types as $k => $ex_type){?>
						<td><b><?=$type_total[$k]?: "-" ?></b><br><?=$type_total_price[$k]? "₪".number_format($type_total_price[$k]) : ""?></td>
					<?
						$all_total+=$type_total[$k];	
						$all_total_price+=$type_total_price[$k];	
					}?>
					<td><b><?=$all_total?></b><br>₪<?=number_format($all_total_price)?></td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
</section>

<style id="theStyle">

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
.reports td, .reports th{padding:5px;vertical-align:middle;border:1px #ccc solid;text-align:right}
.reports td.number{direction:ltr}
.reports .sticky{position:sticky;background:white;}
.reports .sticky.top{top:0}
.reports .sticky.bottom{bottom:0}
.reports .bottom td{font-weight:bold}
.reports tr:hover{background:#cfeef0;cursor:pointer}
.reports .agents{display:none}
.reports .makor{display:none}
.reports .payTypes{display:none}
.reports .total{display:table-row }
.reports td > div{font-size:12px;font-weight:normal}
.last-orders .btns {float: left;}
.last-orders .btns > div {display: inline-block;font-size: 16px;line-height: 34px;padding: 0 20px;margin: 0 2px;border: 1px #0dabb6 solid;color: #999;font-weight: normal;background: white;border-radius: 10px;cursor: pointer;}
.last-orders .btns > div.active {color: white;background: #0dabb6;}
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
        // window.location.href = 'ajax_excel_reports_extras.php' + window.location.search;
		var table = $('table#reports');
		if(table && table.length){
			var preserveColors = (table.hasClass('table2excel_with_colors') ? true : false);
			$(table).table2excel({
				exclude: ".noExl",
				name: "Excel Document Name",
				filename: "report_extras" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
				fileext: ".xls",
				exclude_img: true,
				exclude_links: true,
				exclude_inputs: true,
				preserveColors: preserveColors
			});
		}  
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