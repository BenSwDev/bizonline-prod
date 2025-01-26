<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
$sid = intval($_GET['sid']) ?: $_CURRENT_USER->select_site();
if ($sid && !in_array($sid, $_CURRENT_USER->sites()))
    $sid = 0;

$timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'] ?? date('01/m/Y'))))),"date");
$timeTill = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'] ?? date('t/m/Y'))))),"date");

$where[] = "h.siteID IN (" . ($sid ? $sid : $_CURRENT_USER->sites(true)) . ")";
if($_GET['searchtype']==1){
	$where[] =  "orders.create_date BETWEEN '" . $timeFrom . " 00:00:00' AND '" . $timeTill . " 23:59:59'";
}else if($_GET['searchtype']==2){
	$where[] =  "orders.timeFrom BETWEEN '" . $timeFrom . " 00:00:00' AND '" . $timeTill . " 23:59:59'";
}else{
	$where[] =  "h.time_create BETWEEN '" . $timeFrom . " 00:00:00' AND '" . $timeTill . " 23:59:59'";
}

if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){
    $list = ['clientName', 'clientEmail', 'clientPhone', 'clientPhone2', 'clientPassport'];
    $where[] = "(`" . implode("` LIKE '%" . $freeText . "%' OR `", $list) . "` LIKE '%" . $freeText . "%')";
}

$pager = new UserPager();
$pager->setPage(50);

if(!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN) && $_CURRENT_USER->userType!=1){
	$paysFROM = " LEFT JOIN orders USING (orderID)";
	$paysWhere = " AND orders.therapistID = " . $_CURRENT_USER->id();
}else if($_CURRENT_USER->is_spa()){
	$paysFROM = " LEFT JOIN orders USING (orderID)";
	if($_GET['searchtype']){
		$paysFROM = " INNER JOIN orders USING (orderID)";
	}
}

$paysFROM.=" LEFT JOIN `orders` AS `o` ON (orders.`parentOrder` = o.orderID)";


$que = "SELECT SQL_CALC_FOUND_ROWS h.*, orders.guid as oGuid, o.orderIDBySite AS `siteOrder` FROM `health_declare` AS `h` ".$paysFROM." WHERE " . implode(' AND ', $where) .$paysWhere ." ORDER BY h.time_create DESC " . $pager->sqlLimit();
//echo $que;
$pays = udb::key_row($que, 'declareID');

$pager->setTotal(udb::single_value("SELECT FOUND_ROWS()"));

$sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
<div class="searchOrder">
	<div class="ttl" style="cursor:pointer;margin:-10px;padding:10px" onclick="$('#searchForm').toggleClass('hide');">חפש הצהרות</div>
	<form method="GET" autocomplete="off" action="" class="hide"  id="searchForm">
		<input type="hidden" name="page" value="healthStatements" />
        <!-- input type="hidden" name="otype" value="<?=typemap($_GET['otype'] ?? 'order', 'string')?>" />
        <div class="inputWrap">
            <select name="yaadtype" id="yaadtype" title="">
                <option value="">כל הסוגים</option>
                <option value="order" <?=($_GET['otype'] == 'order' ? 'selected' : '')?>>הזמנות בלבד</option>
                <option value="preorder" <?=($_GET['otype'] == 'preorder' ? 'selected' : '')?>>שיריונים בלבד</option>
            </select>
        </div -->
<?php
    if (!$_CURRENT_USER->single_site){
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
			<select name="searchtype">
				<option value="0" <?=$_GET['searchtype'] == 0?"selected":""?>>לפי תאריך הצהרה</option>
				<option value="1" <?=$_GET['searchtype'] == 1?"selected":""?>>לפי תאריך יצירת הזמנה</option>
				<option value="2" <?=$_GET['searchtype'] == 2?"selected":""?>>לפי תאריך טיפול</option>
			</select>
		</div>
		<div class="inputWrap">
			<input type="text" name="from" placeholder="מתאריך" class="searchFrom" value="<?=implode('/',array_reverse(explode('-',trim($timeFrom))))?>" readonly>
		</div>
		<div class="inputWrap">
			<input type="text" name="to" placeholder="עד לתאריך" value="<?=implode('/',array_reverse(explode('-',trim($timeTill))))?>" class="searchTo" readonly>
		</div>
        <div class="inputWrap">
            <input type="text" name="free" placeholder="חיפוש חופשי" value="<?=$freeText?>" />
        </div>

		<a class="clear" href="?page=<?=$_GET['page']?>">נקה</a>
		<input type="submit" value="חפש">
		
	</form>
</div>
<?php
    $asid = $sid ?: $_CURRENT_USER->active_site();
?>
<?if($_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN) || $_CURRENT_USER->userType==1){?>
<div class="health_send">
	<? if (!$_CURRENT_USER->single_site){ ?>
	<div class="site-select">
		בחר מתחם
		<select id="send-site" title="שם מתחם">
			<option value="0">לאיזה מתחם לשלוח</option>
			<?php
        foreach($sname as $id => $name)
            echo '<option value="' , $id , '" ' , ($id == $sid ? 'selected' : '') , '>' , $name , '</option>';
		?>
        </select>
	</div>
		<?foreach($sname as $id => $name){?>
		<div class="site-line" id="site-send<?=$id?>" style="display:<?=($id == $sid && $sid>0)? "block" : "none"?>">
			<div class="send_btn plusWrapper">שליחת הצהרה <div class="plusSend"  data-title="מילוי הצהרת בריאות" data-msg="לחצו למילוי הצהרת בריאות <?=$name?> https://bizonline.co.il/health/<?=$id?>/" data-subject="הצהרת בריאות <?=$name?>"></div></div>
			<a class="send_btn" href="/health/<?=$id?>/" target="_blank">קישור למילוי הצהרה</a>
		</div>
		<?}?>
	<?}else{?>
		<div class="send_btn plusWrapper">שליחת הצהרה <div class="plusSend"  data-title="מילוי הצהרת בריאות" data-msg="לחצו למילוי הצהרת בריאות <?=$sname[$asid]?> https://bizonline.co.il/health/<?=$asid?>/" data-subject="הצהרת בריאות <?=$sname[$asid]?>"></div></div>
		<a class="send_btn" href="/health/<?=$asid?>/" target="_blank">קישור למילוי הצהרה</a>
	<?}?>
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

<?php
$fullurl = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$fullurl = str_replace('?page=healthStatements&', 'ajax_health_excel.php?', $fullurl);
$fullurl = str_replace('?page=healthStatements', 'ajax_health_excel.php?', $fullurl);
?>

	<a class="excel" href="//<?=$fullurl?>">ייצוא לאקסל</a>
</div>
<?}?>
<section class="orders">
	<div class="last-orders" >
		<div class="title">
			
		</div>
        <?php echo $pager->render() ?>
		<div class="pay_order yaadTrans">
			<div class="payments">
				<div class="title">הצהרות בריאות</div>
<?php
    foreach($pays as $pay){
        $time = explode(' ', $pay['time_create']);
        $time = implode('.', array_reverse(explode('-', $time[0]))) . ' ' . substr($time[1], 0, 5);

        $phones = [];
        if ($pay['clientPhone'])
            $phones[] = substr($pay['clientPhone'], 0, 3) . '-' . substr($pay['clientPhone'], 3);
        if ($pay['clientPhone2'])
            $phones[] = substr($pay['clientPhone2'], 0, 3) . '-' . substr($pay['clientPhone2'], 3);
?>
				<div class="item health">
					<div style="display:none;">
					<?print_r($pay)?>
					</div>
					<div class="payTop">
						<div class="name" style="direction:ltr"><?=$time?></div>
						<div class="stats"><span>מספר הצהרה:</span> <?=$pay['siteID']?>-<?=$pay['declareID']?></div>
					</div>
					<div class="pay-wrap-flex">
						<div class="pay-wrap-flex1">
							<div class="date"><?=$pay['clientName']?></div>
							<div class="phone"><?=implode(', ', $phones)?></div>
						</div>
						
						<div class="paytype"><div class="inner"><?=($pay["negative"]? $pay["negative"]." חריגות": "בריאות תקינה")?><?=$pay['siteOrder'] != 0?"<br /><div style=\"font-weight:normal\"><span>הזמנה</span> ".$pay['siteOrder']."</div>":""?></div></div>
						<div class="buttons-flex">
							<?php if($pay['orderID'] != 0) {
								//$pay_order = udb::single_row("SELECT * FROM orders WHERE orderID=".$pay['orderID']);
								if($pay['oGuid']) { ?>
									
									<a class="see_order btn" href="/signature2.php?guid=<?=$pay['oGuid']?>" target="_blank" data-oid="<?=$pay['orderID']?>">
										<div class="svg"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"></path><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"></path></svg><div>הזמנה</div>
										</div>
									</a>
								<?php } ?>
							
							<?php } ?>
							
							<?php if($pay['fromOrder'] == 0) { ?>
								<div class="change_order btn" data-pid="<?=$pay['declareID']?>" data-name="<?=$pay['clientName']?>" data-phone="<?=$phones[0]?>" data-oid="<?=$pay_order['orderID']?>">
										<div class="svg"><div><?=$pay['orderID'] != 0?"שינוי<br />":""?>שיוך להזמנה</div>
										</div>
								</div>
							<?php } ?>
							<a href="/health/<?=$pay['siteID']?>/<?=$pay['guid']?>" target="_blank" class="form_holder btn">
								<div class="svg"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"/><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"/></svg><div>הצהרה</div>
								</div>
							</a>
						
						</div>
					</div>
				</div>
<?php
    }
?>
			</div>
		</div>
	</div>
</section>

<style>
body .pay_order.yaadTrans {max-width: 600px;position: relative;margin: auto;left: 0;right: 0;background: white;padding: 10px;}
.pay_order.yaadTrans .payments>.item {margin-top: 36px;}
.pay_order .payments>.item .payTop {margin-top: -20px;position: absolute;background: #0dabb6;color: white;display: block;width: 100%;height: 20px;font-size: 14px;line-height: 20px;padding: 0 10px;box-sizing: border-box;}
.pay_order .payments>.item .payTop .name {float:right}
.pay_order .payments>.item .payTop .stats {float:left;font-size:12px}
</style>



<script>
$('.plusWrapper').click(function(){
	$(this).find('.plusSend').trigger('click');
});


$('.change_order').click(function() {
	let _name = $(this).attr('data-name');
	let _phone = $(this).attr('data-phone');
	let _pid = $(this).attr('data-pid');
	var popHtml = `
		<div class="change_order_pop">
			<div class="pop_container">
				<div class="title">שיוך הצהרת בריאות להזמנה</div>
				<div class="close" onclick="$('.change_order_pop').remove();"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
				<div class="orders">
					<div class="fields">
						<div class="order-search">
							<input type="text" name="name" value="${_name}" id="name">
							<label for="name">שם</label>
						</div>
						<div class="order-search">
							<input type="text" name="phone" value="${_phone}" id="phone">
							<label for="phone">טלפון</label>
						</div>
						<div class="submit">חפש</div>
					</div>
					<div class="orders-list">

					</div>
				</div>
			</div>
			<style>
			
			.change_order_pop .submit {height:40px;border-radius:6px;width:10%;background:#0dabb6;cursor:pointer;font-weight:600;text-align:center;color:#FFF;line-height:40px;}
				.change_order_pop {position:fixed;top:0;right:300px;left:0;bottom:0;height:100%;z-index:10;background:rgba(0,0,0,0.6)}
				.change_order_pop .pop_container {position:absolute;top:50%;right:50%;transform:translateY(-50%) translateX(50%);width:100%;max-width:600px;max-height:90vh;height:auto;padding:10px;box-sizing:border-box;background:#fff}
				.change_order_pop .close {position:absolute;top:10px;left:10px;width:24px;height:24px;cursor:pointer}
				.change_order_pop .fields {display:flex;align-items:center;justify-content:space-between}
				.change_order_pop .order-search {border: 1px solid #000;width:40%;display: block;margin: 10px auto;border-radius: 6px;height: 40px;position: relative;}
				.change_order_pop .order-search input {position: absolute;top: 0;left: 0;bottom: 0;right: 0;width: 100%;height: 100%;background: transparent;padding: 0 10px;box-sizing: border-box;}
				.change_order_pop .order-search input+label {font-size: 12px;font-weight: 500;padding: 0 10px;}
				.orders-list {display: block;height:300px;overflow:auto}
				.orders-list>.order {display: flex;justify-content: space-around;align-items: center;height:40px;border-bottom: 1px solid #000;cursor:pointer;transition:all .2s ease}
				.orders-list>.order:hover {background:rgba(13,171,182,.2)}
				.orders-list>.order:last-child {border: 0;}
			</style>
		</div>
	`;
	$(popHtml).appendTo('body');
	$('.fields .submit').on('click', function() {
		$('.orders-list').html('');
		$.post('ajax_searchOrder.php', {act:'search', pid: _pid, name: $('#name').val().length?$('#name').val():'', phone: $('#phone').val().length?$('#phone').val():''}, function(res) {
			$('.orders-list').html(res.html);
		})
	})
	$('.fields .submit').trigger('click')
})


function changeOrder(declareID, orderID) {
	Swal.fire({
		title: 'האם אתה בטוח?',
		text: "האם אתה בטוח שאתה מעוניין לשייך את הצהרת הבריאות("+declareID+") להזמנה("+orderID+")",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'שיוך'
		}).then((result) => {
		if (result.isConfirmed) {
			$.post('ajax_searchOrder.php', {act:'changeOrder', pid: declareID, oid: orderID}, function(res) {
				if(typeof res.msg != 'undefined') {
					Swal.fire({icon:'success', text:res.msg})
				}
			})
		}
		})

}

$('#send-site').change(function(){
	$(this).closest(".health_send").find('.site-line').hide();
	if(this.value>0){ 
		$(this).closest(".health_send").find('#site-send'+parseInt(this.value)).show();
	}
});

</script>
