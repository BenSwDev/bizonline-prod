<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
$sid = intval($_GET['sid']) ?: $_CURRENT_USER->select_site();
if($sid && !$_CURRENT_USER->has($sid)){
    echo 'Access denied';
    return;
}

//$sid = intval($_GET['sid'])?intval($_GET['sid']):$_CURRENT_USER->active_site();
//if ($sid && !in_array($sid, $_CURRENT_USER->sites()))
//    $sid = 0;

$timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'] ?? date('d/m/Y',strtotime("-90 days")))))),"date");
$timeTill = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'] ?? date('t/m/Y'))))),"date");

$where = ["h.siteID IN (" . ($sid ? $sid : $_CURRENT_USER->sites(true)) . ")", "h.created BETWEEN '" . $timeFrom . " 00:00:00' AND '" . $timeTill . " 23:59:59'"];

if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){
    $list = ['name', 'email', '`h`.title', '`h`.text'];
    $where[] = "(" . implode(" LIKE '%" . $freeText . "%' OR ", $list) . " LIKE '%" . $freeText . "%')";
}

$pager = new UserPager();
$pager->setPage(50);

//$paysWhere = $_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN) ? "" : " AND orders.therapistID = " . $_CURRENT_USER->id();
if (!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN))
    $where[] = "orders.therapistID = " . $_CURRENT_USER->id();

$que = "SELECT SQL_CALC_FOUND_ROWS h.*,orders.orderID, files.src AS document FROM `reviews` AS `h` LEFT JOIN orders USING (orderID) LEFT JOIN files ON (files.ref=h.reviewID AND files.table = 'reviews') WHERE " . implode(' AND ', $where) . " ORDER BY h.created DESC " . $pager->sqlLimit();
//echo $que;
$pays = udb::key_row($que, 'reviewID');
$pager->setTotal(udb::single_value("SELECT FOUND_ROWS()"));

//print_r($pays);

$sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
<div class="searchOrder">
	<div class="ttl" style="cursor:pointer;margin:-10px;padding:10px" onclick="$('#searchForm').toggleClass('hide');">חפש חוות דעת</div>
	<form method="GET" autocomplete="off"  action="" class="hide"  id="searchForm">
		<input type="hidden" name="page" value="reviews" />
        <!-- input type="hidden" name="otype" value="<?=typemap($_GET['otype'] ?? 'order', 'string')?>" />
        <div class="inputWrap">
            <select name="yaadtype" id="yaadtype" title="">
                <option value="">כל הסוגים</option>
                <option value="order" <?=($_GET['otype'] == 'order' ? 'selected' : '')?>>הזמנות בלבד</option>
                <option value="preorder" <?=($_GET['otype'] == 'preorder' ? 'selected' : '')?>>שיריונים בלבד</option>
            </select>
        </div -->
<?php
    if (count($_CURRENT_USER->sites()) > 1){
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
<?if($_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){?>

<div class="health_send">
	<? if (count($_CURRENT_USER->sites()) > 1){?>
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
			<div class="send_btn plusWrapper">שליחת חו"ד <div class="plusSend"  data-title="שליחת חוות דעת" data-msg="שלום לך, לאחר שהותך ב-<?=$name?>, נשמח לקבל את חוות דעתך. למילוי חוות דעת : https://bizonline.co.il/review.php?siteID=<?=$id?>" data-subject="בקשה למילוי חוות דעת <?=$name?>"></div></div>
			<a class="send_btn" href="/review.php?siteID=<?=$id?>/" target="_blank">קישור למילוי חו"ד</a>
		</div>
		<?}?>
	<?}else{?>
		<div class="send_btn plusWrapper">שליחת חו"ד <div class="plusSend" data-title="שליחת חוות דעת" data-mail-file="/user/mails/rate.php?siteID=<?=5?>" data-msg="לחצו למילוי חוות דעת <?=$sname[$asid]?> https://bizonline.co.il/review.php?siteID=<?=$asid?>" data-subject="חוות דעת <?=$sname[$asid]?>"></div></div>
		<a class="send_btn" href="/review.php?siteID=<?=$asid?>" target="_blank">קישור למילוי חו"ד</a>
	<?}?>

</div>
<?}?>
<section class="orders">
	<div class="last-orders" >
		<div class="title">

		</div>
        <?php echo $pager->render() ?>
		<div class="pay_order yaadTrans">
			<div class="payments">
				<div class="title"><?=$pager->items_total?> חוות דעת</div>
<?php

    foreach($pays as $pay){
?>
				<div class="review">


						<div class="userData">
							<div class="topData"><?=date("d.m.y H:i",strtotime($pay['created']));?> <?=(count($_CURRENT_USER->sites()) > 1)? $sname[$pay["siteID"]] : ""?></div>
							<b><?=$pay['name']?></b> <span><b><?=$pay['avgScore']?></b>/5</span>
                            <span class="add-com" style="margin-left:10px;"><a data-id="<?=$pay['reviewID']?>"><?=($pay['ownComment']) ? 'ערוך' : 'הוסף';?> תגובה</a></span>
						</div>
						<div class="reviewText">
							<?if($pay["orderID"]){?><div class="showOrder" onclick="window.openFoo.call(window, {'orderID':<?=$pay["orderID"]?>})">הצג הזמנה</div><?}?>
							<?if($pay["document"]){?><div class="showOrder" onclick="openDoc('<?=$pay["document"]?>')">הצג אסמכתא</div><?}?>
							<div class="revtitle"><?=$pay['title']?></div>
							<div class="text"><?=nl2br($pay['text'])?></div>
                            <?if($pay['ownComment'] ){?><div class="text comment"><div class="revtitle">תגובה</div><?=nl2br($pay['ownComment'])?></div><?}?>
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
body .pay_order.yaadTrans {max-width: 600px;position: relative;margin: auto;left: 0;right: 0;background: white;padding: 10px;z-index:1;box-sizing:border-box}
.pay_order.yaadTrans .payments>.item {margin-top: 36px;}
.pay_order .payments>.item .payTop {margin-top: -20px;position: absolute;background: #0dabb6;color: white;display: block;width: 100%;height: 20px;font-size: 14px;line-height: 20px;padding: 0 10px;box-sizing: border-box;}
.pay_order .payments>.item .payTop .name {float:right}
.pay_order .payments>.item .payTop .stats {float:left;font-size:14px}
#saveComment {cursor: pointer;
    display: inline-block;
    padding: 0 10px;
    line-height: 30px;
    color: white;
    border-radius: 10px;
    background: #0dabb6;
    margin-bottom: 10px;    font-size: 14px;}
#ownComment {width:100%;height:120px;border:1px solid #000000;}
.text.comment {padding:10px;}
</style>
<!--Pop comment review-->
<div class="sendPop" id="addcomm" style="display: none;">
    <input type="hidden" id="reviewID" value="0">
    <div class="container">
        <div class="close" onclick="$('.sendPop').fadeOut('fast')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title mainTitle" id="SendPopTitle">הוספה/עריכת תגובה</div>
        <div class="content">
            <div class="lines">
                <div class="line">
                    <textarea name="ownComment" id="ownComment" placeholder="תגובה"></textarea>
                </div>
                <div class="line">
                    <div class="signOpt">
                        <a   id="saveComment">שמירה</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Pop eomment review ends-->
<script>
    $(".add-com a").on("click",function(){
        var id = $(this).data("id");
        $.post("ajax_global.php",{
            act: 'ownComment',
            id: id
        },function(response){
            try {
                var json = JSON.parse(response);
            } catch (e) {
                var json = response;
            }
            $("#reviewID").val(id);
            $("#ownComment").val(response.text);
            $("#addcomm").show();

        });

    });

    $("#saveComment").on("click",function(){
        $.post("ajax_global.php",{
            act: 'ownComment',
            id: $("#reviewID").val(),
            ownComment: $("#ownComment").val()
        },function(response){

            $("#reviewID").val("0");
            $("#ownComment").val("");
            $("#addcomm").hide();

        });
    });
</script>

<script>
$('.plusWrapper').click(function(){
	$(this).find('.plusSend').trigger('click');
});



$('#send-site').change(function(){
	$(this).closest(".health_send").find('.site-line').hide();
	if(this.value>0){
		$(this).closest(".health_send").find('#site-send'+parseInt(this.value)).show();

	}
});



</script>
