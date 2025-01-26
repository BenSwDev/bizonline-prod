<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";

$siteTypes = [1 => 'מתחם', 2 => 'ספא'];

$where ="1 = 1 ";

if($free = trim(filter_var($_GET['free'] ?? '', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW))){
	$where .= "AND (`sites`.`siteName` LIKE '%". udb::escape_string($free) ."%' " . (is_numeric($free) ? "OR sites.phone LIKE '%" . udb::escape_string($free) . "%' OR `sites`.`siteID` = " . intval($free) : '').")";
}

if($_GET['active']!=""){	
	$where .= " AND `sites`.`active`=".intval($_GET['active']).""; 
}

if($_GET['area']!=""){
	$where .= " AND `areas`.`areaID`=".intval($_GET['area']); 
}

if($_GET['marea']!=""){
    $where .= " AND `areas`.`main_areaID`=".intval($_GET['marea']);
}

if($_GET['city']!=""){
	$where .= " AND `sites`.`settlementID`=".intval($_GET['city']); 
}


if($_GET['masof_active']!=""){
	$where .= " AND `sites`.`masof_active`=".(intval($_GET['masof_active']==1)? "1" : "0"); 
}

if($_GET['masof_invoice']!=""){
	$where .= " AND `sites`.`masof_invoice`=".(intval($_GET['masof_invoice']==1)? "1" : "0"); 
}

if($tmp = intval($_GET['site_type'])){
    $where .= " AND `sites`.`siteType`=".$tmp;
}

$domainID = intval($_GET['domain'])? intval($_GET['domain']) : 1;
if($domainID > 1){	
	$where .= " AND (sites_domains.domainID = ".$domainID." AND sites_domains.active = 1 and sites_domains.hideContactMethods = 0)";
}

if($_GET['exBits']!=""){
	switch($_GET['exBits']){
		case "1":
		$where .= " AND (`sites`.`exBits`=1 or `sites`.`exBits`=3 or `sites`.`exBits`=7)"; 
		break;
		case "2":
		$where .= " AND (`sites`.`exBits`=2 or `sites`.`exBits`=3 or `sites`.`exBits`=6)"; 
		break;
		case "4":
		$where .= " AND (`sites`.`exBits`=4 or `sites`.`exBits`=5 or `sites`.`exBits`=6)"; 
		break;
	}
}

if($_GET['onlineOrder']!=""){
	$where .= " AND `sites`.`onlineOrder`='".$_GET['onlineOrder']."'"; 
}
if($_GET['externalEngine']!=""){
	$where .= " AND `sites`.`externalEngine`='".$_GET['externalEngine']."'"; 
}

if($HAVING)
	$HAVING_exp = " HAVING (".implode(' AND ',$HAVING).")";

$que = "SELECT `sites`.`siteName`, sites.siteType, `sites`.`active` AS Yoman ,`sites`.`masof_active` ,`sites`.`masof_invoice`, `sites`.`signature`, `sites`.`siteID`,`sites`.`bookkeeping`, `sites`.`email`, `sites_langs`.`owners` , 
sites.phone, sites_domains.active ".$domainExtra."
FROM `sites` 
LEFT JOIN `settlements` USING (`settlementID`)
LEFT JOIN `areas` USING (`areaID`)
LEFT JOIN `sites_langs` USING (`siteID`) 
LEFT JOIN `sites_domains` ON (sites.siteID = sites_domains.`siteID` ) 
WHERE " . $where . " AND `sites_langs`.`langID`=1 AND `sites_langs`.`domainID`=1 AND sites.active > -1 GROUP BY sites.siteID ".$HAVING_exp." ORDER BY `sites`.`active` DESC, `sites`.`siteName` ASC";
//echo $que;
$sites = udb::full_list($que);
//print_r($sites);
$sitesActiveDom = udb::key_row("SELECT sites_domains.domainID, sites_domains.active,sites_domains.siteID, sites_domains.hideContactMethods FROM sites_domains INNER JOIN domains USING (domainID) WHERE sites_domains.active = 1 AND domains.domainMenu = 1",['siteID','domainID']);



$que = "SELECT SUM(rooms.roomCount) as roomsCount, siteID FROM rooms where active=1 GROUP BY siteID";
$roomsCountActive = udb::key_row($que,'siteID');

$que = "SELECT SUM(rooms.roomCount) as roomsCount, siteID FROM rooms GROUP BY siteID";
$roomsCount = udb::key_row($que,'siteID');

$orders = udb::key_value("SELECT siteID, COUNT(*) FROM `orders` WHERE 1 GROUP BY `siteID` ORDER BY NULL");


$que="SELECT * FROM `domains` WHERE  domains.domainMenu = 1  order by domainID ASC";
$domains= udb::key_list($que,'domainID');


$domains2=  DomainList::get();//NOT IN USE
//print_r($domains2);

$areas = udb::full_list("SELECT `areaID`, `TITLE` FROM `areas` WHERE 1 ORDER BY `TITLE`");
$settlements = udb::full_list("SELECT `areaID`, `TITLE`, settlementID FROM `settlements` INNER JOIN sites USING (`settlementID`) WHERE 1 GROUP BY settlementID ORDER BY `TITLE`");

/*
ini_set('display_errors', 1);
error_reporting(-1 ^ E_NOTICE);
*/
?>

<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>ניהול מתחמים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, 0)">
		<input type="button" class="addNew" id="expExcel" value="ייצא לאקסל" >
	</div>
	<div class="searchCms" style="max-width:700px">
		<form method="GET">
			<input type="text" name="free" placeholder="מלל חופשי" value="<?=htmlspecialchars($_GET['free'], ENT_QUOTES | ENT_XHTML, 'UTF-8', false)?>">
			<div class="seLine" style="margin-left:120px">
				<select name="active">
					<option value="">פעיל/לא פעיל יומן</option>
					<option value="1" <?=($_GET['active']==1?"selected":"")?> >פעיל יומן</option>
					<option value="0" <?=(isset($_GET['active']) && $_GET['active']=="0" ?"selected":"")?>>לא פעיל יומן</option>
				</select>
				<select name="area">
					<option value="">אזור</option>
					<?php foreach($areas as $area){ ?>
					<option value="<?=$area['areaID']?>" <?=($_GET['area']==$area['areaID']?"selected":"")?> ><?=$area['TITLE']?></option>
					<?php } ?>

				</select>
				<select name="city">
					<option value="">יישוב</option>
					<?php foreach($settlements as $settlement){ ?>
					<option value="<?=$settlement['settlementID']?>" <?=($_GET['city']==$settlement['settlementID']?"selected":"")?>><?=$settlement['TITLE']?></option>
					<?php } ?>
				</select>
				<select name="domain">
					<option value="">דומיין</option>
					<?php foreach($domains as $key => $domain){ $domain= $domain[0]?>
					<option value="<?=$domain['domainID']?>" <?=($_GET['domain']==$domain['domainID']?"selected":"")?>><?=$domain['domainName']?></option>
					<?php } ?>
				</select>
				<select name="externalEngine">
					<option value="">מנוע הזמנות חיצוני</option>
					<option value="CheckIn" <?=($_GET['externalEngine']=='CheckIn'?"selected":"")?>>CheckIn</option>
					<option value="easyGo" <?=($_GET['externalEngine']=='easyGo'?"selected":"")?>>easyGo</option>
					<option value="MiniHotel" <?=($_GET['externalEngine']=='MiniHotel'?"selected":"")?>>MiniHotel</option>
					<option value="Optima" <?=($_GET['externalEngine']=='Optima'?"selected":"")?>>Optima</option> 
				</select>
				<select name="exBits">
					<option value="">לקוח של</option>
					<option value="1" <?=($_GET['exBits']=='1'?"selected":"")?>>BizOnline</option>
					<option value="2" <?=($_GET['exBits']=='2'?"selected":"")?>>Booking</option>
					<option value="4" <?=($_GET['exBits']=='4'?"selected":"")?>>Airbnb</option>
				</select>
				<select name="masof_active">
					<option value="">מסוף</option>
					<option value="1" <?=($_GET['masof_active']=='1'?"selected":"")?>>מ.פעיל</option>
					<option value="-1" <?=($_GET['masof_active']=='-1'?"selected":"")?>>מ.לא פעיל</option>
				</select>
				<select name="masof_invoice">
					<option value="">חשבוניות</option>
					<option value="1" <?=($_GET['masof_invoice']=='1'?"selected":"")?>>ח. פעיל</option>
					<option value="-1" <?=($_GET['masof_invoice']=='-1'?"selected":"")?>>ח.לא פעיל</option>
				</select>
                <select name="site_type">
                    <option value="">כל סוג עסק</option>
                    <option value="1" <?=($_GET['site_type']==1?"selected":"")?>>מתחם</option>
                    <option value="2" <?=($_GET['site_type']==2?"selected":"")?>>ספא</option>
                </select>
			</div>

			<a href="/cms/moduls/minisites/table.php">נקה</a>
			<input type="submit" value="חפש">	
		</form>
	</div>
	<div class="tblMobile">
        <div style="left:0"><?=count($sites)?> תוצאות</div>
    <table id="reports">
        <thead>
        <tr>
            
			<th>ID</th>
            <th style="width:auto">שם המקום</th>
            <th style="width:3%">סוג עסק</th>
			<th style="width:auto">בעלים</th>
			<th style="width:auto">טלפון</th>
            <th>דוא"ל</th>
            <th class="orders_num" style="cursor:pointer">מספר יחידות</th>
            <th class="orders_num" style="cursor:pointer">מסוף פעיל</th>
            <th class="orders_num" style="cursor:pointer">חשבוניות</th>
            <th class="orders_num" style="cursor:pointer">מספר הזמנות</th>
            <th class="orders_num" style="cursor:pointer">חשבשבת</th>
			
			<th width="40">יומן</th>
			<?php //foreach($domains as $domain){ ?>
            <th style="width:150px">דומיינים<?//=$domain['domainName']?></th>
			<?php //} ?>
            <th class="noExl">התחברות</th>
            <th class="noExl">הסכם</th>
			<th class="noExl">אמנה</th>
            <th class="noExl">הסכם Vouchers</th>
			<th class="noExl"></th>
        </tr>
        </thead>
        <tbody id="sortRow">
<?php
	$iter = 0;
    if (count($sites)){
        foreach($sites as $site){
			$agre = udb::single_row("SELECT * FROM sites_agreements WHERE siteID=".$site['siteID']);
			$vagre = udb::single_row("SELECT * FROM sites_vouchers_agreements WHERE siteID=".$site['siteID']);
			$signedAmana = udb::single_value("SELECT * FROM  sites_amana_signed WHERE siteID=".$site['siteID']); 
			$iter++;
?>
            <tr id="<?=$site['siteID']?>">
				
                <td><?=$site['siteID']?></td>
                <td onclick="openPop(<?=$site['siteID']?>,'<?=addslashes(htmlspecialchars($site['siteName']))?>')"><?=outDb($site['siteName'])?></td>
                <td onclick="openPop(<?=$site['siteID']?>,'<?=addslashes(htmlspecialchars($site['siteName']))?>')"><?=$siteTypes[$site['siteType']]?></td>
                <td onclick="openPop(<?=$site['siteID']?>,'<?=addslashes(htmlspecialchars($site['siteName']))?>')"><?=outDb($site['owners'])?></td>
                <td onclick="openPop(<?=$site['siteID']?>,'<?=addslashes(htmlspecialchars($site['siteName']))?>')"><?=outDb($site['phone'])?></td>
				
                <td onclick="openPop(<?=$site['siteID']?>,'<?=addslashes(htmlspecialchars($site['siteName']))?>')"><?=outDb($site['email'])?></td>
				
				<td><?=$roomsCountActive[$site['siteID']]['roomsCount']?>(<?=$roomsCount[$site['siteID']]['roomsCount']?>)</td>
                <td><?=$site['masof_active']? "<b style='color:green'>כן</b>" : ""?></td>
                <td><?=$site['masof_invoice']? "<b style='color:green'>כן</b>" : ""?></td>
				<td><?=($orders[$site['siteID']] ?: '-')?></td>
				
				<td><?=outDb($site['bookkeeping'])?></td>
				
                <td><?=($site['Yoman']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
				
				
				<td class="domCheck">
                    <?php foreach($domains as $domain){ $domain = $domain[0]?>
					<?if($sitesActiveDom[$site['siteID']][$domain['domainID']] && $domain['domainID']!=1){
					$hideContact = ($sitesActiveDom[$site['siteID']][$domain['domainID']]['hideContactMethods'])? "<span>🚫</span> " : "<span>✅</span> ";
							
					?>
						<?//print_r($domain)?>
						<a class="clink" href="https://<?=$domain['domainURL']?><?=str_replace("+","_",ActivePage::showAlias('sites', $site['siteID'],1,$domain['domainID']))?>" target="_blank"><?=$hideContact.$domain['domainName']?></a><br>
					<?}?>
					<?php } ?>
				</td>
				
                <td class="noExl"><a target="_blank" class="connectBtn" href="/user?siteID=<?=$site['siteID']?>">התחברות</a></td>
				<td class="noExl"><?=$agre?"<svg xmlns='http://www.w3.org/2000/svg' version='1.1' x='0' y='0' viewBox='0 0 367.8 367.8' xml:space='preserve' width='30' height='30'><path d='M183.9 0c101.6 0 183.9 82.3 183.9 183.9s-82.3 183.9-183.9 183.9S0 285.5 0 183.9l0 0C-0.3 82.6 81.6 0.3 182.9 0 183.2 0 183.6 0 183.9 0z' fill='#3BB54A'></path><polygon points='285.8 133.2 155.2 263.8 82 191.2 111.8 162 155.2 204.8 256 104 ' fill='#D4E1F4'></polygon></svg>":"<div class=\"connectBtn\" onclick=\"sendAgre('".$site['siteID']."')\">שלח הסכם</div>"?></td>
				<td class="noExl"><?=$signedAmana?"<svg xmlns='http://www.w3.org/2000/svg' version='1.1' x='0' y='0' viewBox='0 0 367.8 367.8' xml:space='preserve' width='30' height='30'><path d='M183.9 0c101.6 0 183.9 82.3 183.9 183.9s-82.3 183.9-183.9 183.9S0 285.5 0 183.9l0 0C-0.3 82.6 81.6 0.3 182.9 0 183.2 0 183.6 0 183.9 0z' fill='#3BB54A'></path><polygon points='285.8 133.2 155.2 263.8 82 191.2 111.8 162 155.2 204.8 256 104 ' fill='#D4E1F4'></polygon></svg>":"<div class=\"connectBtn\" onclick=\"sendAmana('".$site['siteID']."')\">שלח אמנה</div>"?></td>
				<td class="noExl"><?=$vagre?"<svg xmlns='http://www.w3.org/2000/svg' version='1.1' x='0' y='0' viewBox='0 0 367.8 367.8' xml:space='preserve' width='30' height='30'><path d='M183.9 0c101.6 0 183.9 82.3 183.9 183.9s-82.3 183.9-183.9 183.9S0 285.5 0 183.9l0 0C-0.3 82.6 81.6 0.3 182.9 0 183.2 0 183.6 0 183.9 0z' fill='#3BB54A'></path><polygon points='285.8 133.2 155.2 263.8 82 191.2 111.8 162 155.2 204.8 256 104 ' fill='#D4E1F4'></polygon></svg>":"<div class=\"connectBtn\" onclick=\"sendVAgre('".$site['siteID']."')\">שלח הסכם Voucher</div>"?></td>
				<td class="noExl"><div onclick="getDel(<?=$site['siteID']?>)" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
            </tr>
<?php
		}
			}
?>
        </tbody>
    </table>
	</div>
</div>
<div class="popup delete-order" style="display:none;">
	<div class="popup_container">
		<div class="title">מחיקת מתחם</div>
		<form class="form" id="delForm">
			<div class="need">הזינו את הסיסמא ולחצו מחיקה</div>
			<div class="inputWrap">
				<input type="hidden" name="siteID" value="">
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
	td .clink{margin:0;text-align:right}
	td .clink span{font-size:10px}

	</style>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script src="/user/assets/js/swal.js?v=1"></script>
<script>
var pageType="<?=$pageType?>";

function getDel(sid) {
	$('.popup.delete-order input[name="siteID"]').val(sid);
	$('.popup.delete-order').show();
	$('.popup.delete-order input[type="text"]').focus();
}

function openPop(pageID, siteName){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/frame.dor2.php?siteID='+pageID+'&siteName='+encodeURIComponent(siteName)+'&tab=1&domid=<?=$domainID?:1;?>"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}

function sendDelete(formId){
	$('.holder').show();

		$.post('js_sendDelete.php',$('#'+formId).serialize(),function(result){
			if(result.error){
				 Swal.fire(result.error);
			}
			else {
				delsite(parseInt(result.siteid));
			}
		},"JSON");

}

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
        var valA = getCellValue(a, index), valB = getCellValue(b, index)
        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB)
    }
}
function getCellValue(row, index){ return $(row).children('td').eq(index).text() }


$('#expExcel').on('click', function(e){
	e.preventDefault();
	var table = $('table#reports');
	if(table && table.length){
		var preserveColors = (table.hasClass('table2excel_with_colors') ? true : false);
		$(table).table2excel({
			exclude: ".noExl",
			name: "Excel Document Name",
			filename: "report_manage" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
			fileext: ".xls",
			exclude_img: true,
			exclude_links: true,
			exclude_inputs: true,
			preserveColors: preserveColors
		});
	}
	// window.location.href = 'ajax_excel_reports_manage.php' + window.location.search;
});



function delsite(siteID){
	$.post('delSite.php',{'siteID':siteID},function(){
		Swal.fire('המתחם נמחק בהצלחה').then(function() {
			window.location.reload();
		});
	});

}

function sendAgre(siteID){

	Swal.fire({
        title: 'האם אתה בטוח?',
        text: "האם אתה בטוח שתרצה לשלוח הסכם למתחם מספר "+siteID+"?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'שליחה',
      }).then((result) => {
        if (result.value) {
			$.post("ajax_sendAgre.php",{siteID:siteID},function(res){
            if(res.error)return Swal.fire('יש בעיה!', res.error, 'error');
            Swal.fire('ההסכם נשלח בהצלחה', '', 'success').then(function() {
              window.location.reload();
            });
          });
        }
      })

}

function sendVAgre(siteID){

	Swal.fire({
        title: 'האם אתה בטוח?',
        text: "האם אתה בטוח שתרצה לשלוח הסכם Voucher למתחם מספר "+siteID+"?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'שליחה',
      }).then((result) => {
        if (result.value) {
			$.post("ajax_sendVAgre.php",{siteID:siteID},function(res){
            if(res.error)return Swal.fire('יש בעיה!', res.error, 'error');
            Swal.fire('ההסכם נשלח בהצלחה', '', 'success').then(function() {
              window.location.reload();
            });
          });
        }
      })

}
function sendAmana(siteID){

	Swal.fire({
        title: 'האם אתה בטוח?',
        text: "האם אתה בטוח שתרצה לשלוח אמנה למתחם מספר "+siteID+"?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'שליחה',
      }).then((result) => {
        if (result.value) {
			$.post("ajax_sendAmana.php",{siteID:siteID},function(res){
            if(res.error)return Swal.fire('יש בעיה!', res.error, 'error');
            Swal.fire('אמנת השירות נשלחה בהצלחה', '', 'success').then(function() {
              window.location.reload();
            });
          });
        }
      })

}
function deleteOrder(oid) {

}

function checkActiveDomain(siteID,domainID,elem){

	var status = elem.checked?1:0;
	$.post("ajax_changeActiveDomain.php",{siteID:siteID,domainID:domainID,status:status});
}

/*
function orderNow(is){
	$("#addNewAcc").hide();
	$(is).val("שמור סדר תצוגה");
	$(is).attr("onclick", "saveOrder()");
	$("#sortRow tr").attr("onclick", "");
	$("#sortRow").sortable({
		stop: function(){
			$("#orderResult").val($("#sortRow").sortable('toArray'));
		}
	});
	$("#orderResult").val($("#sortRow").sortable('toArray'));
}
function saveOrder(){
	var ids = $("#orderResult").val();
	$.ajax({
		url: 'js_order_pages.php',
		type: 'POST',
		data: {ids:ids, type:pageType},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}
*/
</script>
<?php



include_once "../../bin/footer.php";
?>
