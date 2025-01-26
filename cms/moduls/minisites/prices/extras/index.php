<?php
include_once "../../../bin/system.php";

$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);

?>
<html lang="he">
	<head>
		<meta charset="UTF-8">
	</head>
	<body style="padding:0;margin:0">
		<div class="modulFrame">
			<iframe src="/SiteManager__/tab-priceList.php?siteID=<?=$siteID?>" frameborder="0" width="100%" height="100%"></iframe>
		</div>
	</body>
</html>

<?php



/*include_once "../../bin/top.php";

$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
$extras = udb::full_list("SELECT * FROM `extra` WHERE siteID=".$siteID);

if($_GET['extraDel']==1 && $_GET['extraID']){

	$mainpageid=intval($_GET['extraID']);
	udb::query("DELETE FROM `extra` WHERE id=".$mainpageid."");
	udb::query("DELETE FROM `extra_langs` WHERE id=".$mainpageid."");

?>
<script>window.parent.closeTab();</script>
<?php
}



?>

<style>
#pricePeriods {text-overflow: ellipsis;white-space: nowrap;overflow: auto;}
#pricePeriods .item {min-width:120px;text-align:center;vertical-align:top;transition:all .2s ease;box-sizing:border-box;border:1px solid #09a5d9;display:inline-block;border-radius:50px;height:36px;color:#09a5d9;font-size:14px;padding-top:2px;cursor:pointer;margin-left:10px;background:transparent;font-weight:normal}
#pricePeriods .item:hover, #pricePeriods .item.active {background:#09a5d9;color:#fff}
#pricePeriods .item:empty {line-height:36px;padding-top:0}
#pricePeriods .item:before {content:attr(data-period)}
#pricePeriods .item > div {font-size:11px}
.editRoomsPrices .editRoomsPrices-periods {overflow: hidden;position: relative;box-sizing:border-box;width:30%;display:inline-block;border-left:1px solid #ccc;border-top: 1px solid #cccccc;border-right: 1px solid #cccccc;}

.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .row:first-child > .cell {height:92px;line-height:92px}

.editRoomsPrices .topof .rgtPeriodsArrow{position: absolute;right:0;top: 0;cursor: pointer;z-index: 9;}
.editRoomsPrices .topof .lftPeriodsArrow{position: absolute;left:0;top: 0;cursor: pointer;z-index: 9;}
.editRoomsPrices .topof .rgtPeriodsArrow i{color: #0d6582;font-size: 40px;}
.editRoomsPrices .topof .lftPeriodsArrow i{color: #0d6582;font-size: 40px;}

.editRoomsPrices .editRoomsPrices-months {box-sizing:border-box;font-size:14px;width:70%;display:inline-block;border-right:1px solid #ccc;border-left: 1px solid #cccccc;border-top: 1px solid #cccccc;}
.editRoomsPrices .editRoomsPrices-periods .editRoomsPrices-roomTitle {color:#333333;font-size:20px;padding:51px 20px 0 0;font-weight:500}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel {width:100%;position:relative;overflow: hidden;}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .owl-controls {color:#0d6582;font-size:40px;line-height:40px}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .owl-prev {position:absolute;top:0;right:0;}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .owl-next {position:absolute;top:0;left:0;}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period {}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period > span {font-size:24px;line-height:40px;display:block;width:100%;text-align:center;padding: 35px 0 0;}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike {display:block;}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .row {font-size:0;display:block;width:100%}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .row .cell {display:inline-block;width:33.33%;font-size:16px;white-space:nowrap}

.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .row:not(:first-child) .cell {height:50px;border-bottom:1px solid #ccc;padding-top:8px;box-sizing:border-box;vertical-align:top}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .row:not(:first-child) .cell span {display:none}

.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .row:last-child .cell {border-bottom:none}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .cell > div {font-size:12px}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .cell input {max-width:100%;display:block;margin:0 auto}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .row:first-child .cell {text-align:center;border-bottom:1px solid #cccccc;font-size:14px;color:#777777}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .row:first-child .cell.endof {color:#09a5d9}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period .tableLike .row .cell:first-child {box-sizing:border-box;padding-right:5px}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period input:not([type=submit]) {line-height: 32px;height: 32px;background: #f5f5f5;border: 0;border-radius: 3px;box-sizing: border-box;outline: none;font-size: 16px;padding: 0 5px;box-shadow: -1px 1px 0 rgba(0,0,0,0.2);margin: 0 auto;width: 51px;font-family: 'Rubik', sans-serif;}
.editRoomsPrices .editRoomsPrices-periods .periodcarousel .editRoomsPrices-period input:not([type=submit]).endofweek {background:#edf5fa;box-shadow:-1px 1px 0 #a9cce2}
</style>

<div class="popRoom"><div class="popRoomContent"></div></div>
<div class="manageItems" id="manageItems">
    <h1>ניהול מחירים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, <?=$siteID?>)">
	</div>
    <div id="pricePeriods">
		
		<div class="item callable" data-period="אוגוסט" data-param="periodID" data-value="-6" data-refresh="true"><div>01/08/19 - 31/08/19</div></div>
		<div class="item callable" data-period="ראש השנה" data-param="periodID" data-value="-7" data-refresh="true"><div>29/09/19 - 01/10/19</div></div>
		<div class="item callable" data-period="סוכות" data-param="periodID" data-value="-3" data-refresh="true"><div>13/10/19 - 21/10/19</div></div>
		<div class="item callable" data-period="פסח" data-param="periodID" data-value="-8" data-refresh="true"><div>08/04/20 - 15/04/20</div></div>
		<div class="item callable" data-period="שבועות" data-param="periodID" data-value="-9" data-refresh="true"><div>28/05/20 - 30/05/20</div></div>
		<div class="item callable" data-period="יולי" data-param="periodID" data-value="-5" data-refresh="true"><div>01/07/20 - 31/07/20</div></div>
		<div class="item addNew">
			הוסף תקופה חדשה
		</div>
	</div>
	<div></div>
	<div class="editRoomsPrices">
	<div class="editRoomsPrices-periods">

    <form method="POST" id="pricesForm">
        <div class="editRoomsCarousel periodcarousel">
            <div class="editRoomsPrices-period">
                <input type="hidden" name="siteID" value="15620">
                <input type="hidden" name="periodID" value="460">
                <input type="hidden" name="roomID" value="15621">
                
				<div class="tableLike">
                    
					<div class="row">
                        <div class="cell">סוג תמחור</div>
                        <div class="cell">אמצ"ש</div>
                        <div class="cell endof">סופ"ש</div>
                    </div>
                    <div class="row">
                        <div class="cell"><span>1-3 לילות</span>1-3 לילות<div>מחיר ללילה</div></div>
                        <div class="cell"><input type="text" class="middleweek" value="1150" name="weekday1" title="מחיר ללילה באמצ&quot;ש"></div>
                        <div class="cell"><input type="text" class="endofweek" value="1150" name="weekend1" title="מחיר ללילה בסופ&quot;ש"></div>
                    </div>
                    <div class="row">
                        <div class="cell"><span>4-15 לילות</span>4-15 לילות<div>מחיר ללילה</div></div>
                        <div class="cell"><input type="text" class="middleweek" value="0" name="weekday2" title="מחיר 2 לילות באמצ&quot;ש"></div>
                        <div class="cell"><input type="text" class="endofweek" value="0" name="weekend2" title="מחיר 2 לילות בסופ&quot;ש"></div>
                    </div>
                    <div class="row">
                        <div class="cell"><span>15 לילות ומעלה</span>15 לילות ומעלה<div>מחיר ללילה</div></div>
                        <div class="cell"><input type="text" class="middleweek" value="0" name="weekday3" title="מחיר 3 לילות בסופ&quot;ש"></div>
                        <div class="cell"><input type="text" class="endofweek" value="0" name="weekend3" title="מחיר 3 לילות בסופ&quot;ש"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script>

function openPop(pageID,siteID){
	$(".popRoomContent").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/prices/extras/frame.php?id='+pageID+'&siteID='+siteID+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".popRoom").show();
	window.parent.parent.$('.tabCloser').hide();
}
function closeTab(){
	$(".popRoomContent").html('');
	$(".popRoom").hide();
	window.parent.parent.$('.tabCloser').show();
}


</script>
<?php



?>
*/