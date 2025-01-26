<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";


$siteID=intval($_GET['siteID']);
$siteName = $_GET['siteName'];

$sunday = date('Y-m-d', strtotime('-' . date('w') .' days'));

$rooms = udb::key_value("SELECT `roomID`, `roomName` FROM `rooms` WHERE `active` = 1 AND `siteID` = " . $siteID);
?>
<style type="text/css">
.calendarWrap{max-width:840px;box-sizing:border-box;border:4px solid #bababa;margin: 20px auto;background: #fff;position: relative;overflow: hidden;}
.calendarWrap .topSection{height: 70px;box-sizing:border-box;border-bottom:1px solid #bababa;position: relative;overflow: hidden;}
.calendarWrap .topSection .closeCal{cursor: pointer;margin-right: 10px;width: 30px;height: 30px;background: url('../../../images/closeCal.png') no-repeat center center;float: right;margin-top: 22px;}
.calendarWrap .topSection .calTitle{font-weight: bold;font-size: 20px;float: right;margin-right: 20px;color: #696969;line-height: 74px;}
.calendarWrap .topSection .newEventBtn{width: 44px;height: 44px;background:#f46f54  url('../../../images/addEventPop.png') no-repeat center center;border-radius:100px;cursor: pointer;position: absolute;left:100px;top: 12px;	}
.calendarWrap .topSection .switchWrap{position: absolute;left: 20px;top: 14px;}
.calendarWrap .topSection .switchWrap .switchTtl{text-align: center;font-size: 12px;color: #696969;}
.switch{height: 26px;}
.slider:before{width:30px;height:30px;box-sizing:border-box;border: 1px solid #a2a2a2;bottom: -2px;left: 0;}
.switch input:checked + .slider:before{-webkit-transform: translateX(30px);-ms-transform: translateX(30px);transform: translateX(30px);}
.calendarWrap .monthLine{position: relative;height: 50px;border-bottom:1px solid #f0f0f0;}
.calendarWrap .monthLine .monthName{text-align: center;line-height: 50px;font-size: 16px;color: #000;}
.calendarWrap .monthLine .rArrowMonth{position: absolute;right: 11%;top: 5px;font-size: 40px;color: #000;cursor: pointer;}
.calendarWrap .monthLine .lArrowMonth{position: absolute;left: 5px;top: 5px;font-size: 40px;color: #000;cursor: pointer;}


.calendar.right{position: absolute;right: -100%;z-index: 99;top: 121px;background: #fff;animation-name: slideFromRight;animation-duration: 0.5s;animation-direction: alternate;animation-fill-mode: forwards;}
.calendar.left{position: absolute;left: -100%;z-index: 99;top: 121px;background: #fff;animation-name: slideFromLeft;animation-duration: 0.5s;animation-direction: alternate;animation-fill-mode: forwards;}

.calendar{display: table;width: 100%;border-collapse: collapse;table-layout: fixed;background: #fff;}
.calendar .tableRow{/* display: table-row; */border-bottom:1px solid #f0f0f0;position: relative;margin-right: 90px;}
.calendar .tableRow .tblCell:first-child{position: absolute !important;right: -90px !important;display: block !important;width: 90px !important;}
.calendar .tableRow .tblCell:last-child{border-left: 1px solid #f0f0f0;}
.calendar .tableRow .tblCell{display: table-cell;border-right:1px solid #f0f0f0;width: 100px;box-sizing:border-box;height: 70px;position: relative;}
.calendar .divScroll { overflow-y: auto;height: 320px;background: #fff;width: 100%;border-top: 0;overflow-x: hidden;}
.calendar .tableRow.date .tblCell .specialDate{line-height: 18px;position: absolute;bottom: -9px;background:#1e8fca;color: #fff;font-size: 14px;box-sizing:border-box;padding-right: 10px;z-index: 1;}
.calendar .tableRow.date .tblCell .fLine{text-align: center;font-size: 14px;color: #555;margin-top: 15px;}
.calendar .tableRow.date .tblCell .sLine{text-align: center;font-size: 14px;color: #777;font-weight: bold;margin-top: 8px;}
.calendar .tableRow.date .rArrowWeek{position: absolute;right: 0;top: calc(50% - 20px);font-size: 40px;color: #000;cursor: pointer;}
.calendar .tableRow.date .lArrowWeek{position: absolute;left: 5px;top: calc(50% - 20px);font-size: 40px;color: #000;cursor: pointer;}
.calendar .tableRow.sum .tblCell{overflow: hidden;vertical-align: middle;}
.calendar .tableRow.sum .tblCell:first-child{text-align: center;vertical-align: middle;font-weight: bold;font-size: 14px;line-height: 70px;}
.calendar .tableRow.sum .tblCell .free{width: 50%;text-align: center;float: right;}
.calendar .tableRow.sum .tblCell .occ{width: 50%;text-align: center;float: right;}
.calendar .tableRow.sum .tblCell .free .num{font-size: 16px;font-weight: bold;color: #000;}
.calendar .tableRow.sum .tblCell .free .label{font-size: 10px;font-weight: bold;color: #000;}
.calendar .tableRow.sum .tblCell .occ .num{font-size: 16px;color: #aaaaaa;}
.calendar .tableRow.sum .tblCell .occ .label{font-size: 10px;color: #aaaaaa;}
.calendar .tableRow.site .tblCell .topCellSite{height: 100%;max-height: 69px;position: relative;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails{position: relative;overflow: hidden;height: 100%;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet{width: 50%;text-align: center;float: right;margin-top: 22px;position: relative;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet .freeArr{position: absolute;left: 4px;top: 4px;font-size: 22px;color: #1e8fca;cursor: pointer;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet .occArr{position: absolute;right: 4px;top: 4px;font-size: 22px;color: #f79a87;cursor: pointer;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet:first-child .num{font-size: 16px;font-weight: bold;color: #1e8fca;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet:first-child .lbl{font-size: 10px;font-weight: bold;color: #1e8fca;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet:nth-child(2) .num{font-size: 16px;color: #f79a87;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet:nth-child(2) .lbl{font-size: 10px;color: #f79a87;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .all{background: #fff;position: absolute;width: 18px;height: 18px;line-height: 18px;border:1px solid #c0c0c0;border-radius:18px;color: #c0c0c0;font-size: 12px;box-sizing:border-box;text-align: center;margin: 0 auto;left: 0;right: 0;top: 8px;}

.calendar .tableRow.site .tblCell:first-child{cursor: pointer;text-align: center;vertical-align: middle;color: #1e8fca;font-size: 14px;font-weight: bold;}
.calendar .tableRow.site .tblCell:first-child .topCellSite .siteTDetails{padding-right: 36px;background: url('../../../images/plusCal.png') no-repeat center right 10px; position: absolute;top: 50%;left: 50%;transform: translate(-40%, -50%);height: auto;}
.calendar .tableRow.site .tblCell:first-child .topCellSite.open .siteTDetails{background: url('../../../images/minusCal.png') no-repeat center right 10px;}

.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .sep {position: absolute;left: 0;right: 0;top: 0;width: 1px;border-right:1px dotted #e0e0e0;height: 70px;margin: auto;z-index: 0;}


.calendar .tableRow.siteOcc{border-bottom:0;height: 0;overflow: hidden;}
.calendar .tableRow.siteOcc.open{height: auto;overflow: visible;}
.calendar .tableRow.siteOcc:last-child{border-bottom: 1px solid #f0f0f0;}
.calendar .tableRow.siteOcc .tblCell{height: 35px;border-bottom:0;position: relative;}
.calendar .tableRow.siteOcc .tblCell .siteNum{font-weight: bold;font-size: 14px;color:#1e8fca;text-align: center;line-height: 35px;position: relative;}
.calendar .tableRow.siteOcc .tblCell .siteNum::before{content:'•';position: absolute;right: 18px;font-size: 22px;}
.calendar .tableRow.siteOcc .tblCell .event{width: calc(100% - 6px);box-sizing:border-box;line-height: 18px;margin:7.5px auto 0;}
.calendar .tableRow.siteOcc .tblCell .free{border:1px solid #1e8fca;height: 20px;line-height: 20px;text-align: right;padding-right: 10px;color: #1e8fca;font-size: 14px;cursor: pointer;position: absolute;z-index: 1;top: 7.5px;box-sizing: border-box;right: 3px;width: calc(100% - 6px);}
.calendar .tableRow.siteOcc .tblCell .occ{border:1px solid #f46f54;background:rgba(244,111,84,0.7);color: #fff;position: absolute;z-index: 2;top: 7.5px;box-sizing: border-box;line-height: 18px;right: 3px;width: calc(100% - 6px);padding-right: 10px;}
.calendar .tableRow.siteOcc .tblCell .occNames{right: 3px;border:1px solid #f46f54;background:rgba(244,111,84,1);color: #fff;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;position: absolute;z-index: 3;top: 7.5px;box-sizing: border-box;line-height: 18px;}
.mobTtl{display: none;font-weight: bold;font-size: 12px;color: #fff;line-height: 14px;}
.calendar .tableRow.site .mobTtl{background:#8ec7e4}
.calendar .tableRow.sum .mobTtl{background: #d6d6d6}
.calendarWrap.mobOpen .calendar .tableRow.siteOcc{height: auto;overflow: visible;}



.bodyBG{position:fixed;left:0;right:0;top:0;bottom:0;background:rgba(0,0,0,0.5);z-index:100;display: none;}
.popup{overflow-y:auto;position:fixed;max-height:100%;height:auto;width:320px;background:#fff;transform: translateY(-50%);border-radius: 5px;box-shadow: 0 0 10px rgba(0,0,0,0.5);left:0;right:0;margin:auto;z-index:200;top: 50%;}
.popup .notavailable{color:rgb(188,188,188); pointer-events: none}
.popup .chosen{color: rgb(30,143,202);}
.popup .available{color: rgb(105,105,105);}
.popup i{font-size:25px;}
.popup .maintitle{ padding: 15px 10px;font-size: 20px;color: rgb(105,105,105);border-bottom:1px solid #bbbbbb;}
.popup .maintitle span i {font-size:30px;}
.popup .maintitle .closer{cursor: pointer;display:inline-block;vertical-align:top;background: url('../../../images/popX.png') no-repeat center center;background-size: 35px;height: 35px;width: 35px;}
.popup .maintitle  .text{line-height: 35px;}
.popup .maintitle .switchWrap{ float: left;width: 55px;text-align: center;margin-left: 5px;}
.popup .maintitle .switchWrap .switchTtl{font-size: 12px;color: rgb(105,105,105);margin-top: 3px;}
.popup .inputChanger{cursor:pointer}
.popup .content .nights .inputtextLbl{font-size: 16px;display: inline-block;vertical-align: top;padding: 0 15px;line-height: 22px;}
.popup .content{padding:10px;}
.popup .content .nights{padding:10px;text-align:center;font-size: 0;}
.popup .content .dates{text-align:center;margin-top: 8px;font-size:0}
.popup .content .dates input {cursor:pointer;width: 128px;height: 35px;background:url('../../../images/popDate.png')no-repeat center    left 2px;background size:20px;font-size: 16px;color: rgb(105,105,105);padding-right:10px;margin:0 5px;    border: 1px solid #bbbbbb;}
.popup .content .inputs input{1px solid #bbbbbb;width:70px;font-size: 16px;text-align:center;border:none;vertical-align: top;padding-top: 3px;background:#fff}
.popup .content .rooms{border-top: 1px solid #bbbbbb;max-height: 225px;overflow-y: auto;}
.popup .content .rooms .line{padding: 10px 2px;}
.popup .content .rooms .line{border-bottom:1px solid #bbbbbb;}
.popup .content .line .inputs{ width: 139px;vertical-align: top;text-align: left;display: inline-block;}  
.popup .content .line .inputs input{width:25px}
.popup .content .rooms .line .text{ width: 130px;display: inline-block;}
.popup .content .rooms .line .text .title{font-size: 16px;}
.popup .content .rooms .line .text .desc{font-size: 12px;}
.popup .content .rooms .line .text .title{font-size: 16px;}
.popup .content .rooms .line .text .desc{font-size: 12px;}
.popup .content .btnWrap{cursor:pointer;width: 160px;height: 50px;margin: 20px auto;border: 1px solid #1e8fc9;font-size: 20px;text-align: center;line-height: 50px;color: rgb(30,143,202);}

.hasDatepicker.dead {pointer-events:none}
.inputs .fa.disabled {pointer-events:none; color:#a2a2a2}

@media(max-width:768px){

.calendarWrap{border:0;}
.calendar .tableRow .tblCell{height: 60px;}
.calendar .tableRow{margin-right: 10px;}
.calendar .divScroll{border:0;}
.calendar .tableRow.date .tblCell .sLine{margin-top: 2px;}
.calendarWrap .monthLine .rArrowMonth{right: 10px;}
.calendar .divScroll{right: 0;}
.calendar .tableRow.siteOcc{overflow: hidden;position: static !important;display: none !important;}
.calendar .tableRow.siteOcc.open{display: table-row !important;}
.calendar .tableRow.sum .tblCell:first-child{background:#d6d6d6;}
.calendar .tableRow.site .tblCell:first-child{background:#8ec7e4;}

.calendar .tableRow.siteOcc .tblCell:first-child{display: table-cell !important;width: 10px !important;right: 0 !important;position: static !important;}/*fix this line*/


.calendar .tableRow.date .tblCell .specialDate{bottom: 0;}
.mobTtl{display: block;}
.calendar .tableRow.sum .tblCell .free, .calendar .tableRow.sum .tblCell .occ{width: 100%;float: none;}
.calendar .tableRow.sum .tblCell .free .num,.calendar .tableRow.sum .tblCell .free .label{display: inline-block;vertical-align: middle;line-height: 20px;}
.calendar .tableRow.sum .tblCell .occ .num{font-size: 12px;}
.calendar .tableRow.sum .tblCell .occ .num,.calendar .tableRow.sum .tblCell .occ .label{display: inline-block;vertical-align: middle;line-height: 20px;}
.calendar .tableRow.date .tblCell .fLine{margin-top: 10px;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .all{display: none;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet{float: none;width: 100%;margin-top:0px;height: 30px}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet .num{display: inline-block;margin-top: 10px;vertical-align: middle;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet .lbl{display: inline-block;margin-top: 10px;vertical-align: middle;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet .freeArr{transform:rotateZ(-90deg);top: -6px;right: 0;left: 0;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet .occArr{transform:rotateZ(-90deg);top: 12px;right: 0;left: 0;height: 20px;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet:nth-child(2) .num{margin-top: 0px;font-size: 12px;}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .unitDet:nth-child(2) .lbl{margin-top: 0px;	}
.calendar .tableRow.site .tblCell .topCellSite .siteTDetails .sep{width: 100%;height: 1px;border-right: 0;top: 50%;border-top: 1px dotted #e0e0e0;}
.calendar .tableRow.siteOcc .tblCell .siteNum::before{display: none;}




}

@keyframes slideFromRight {
    0%   {right: -100%;}
    100% {right: 0;}
}
@keyframes slideFromLeft {
    0%   {left: -100%;}
    100% {left: 0;}
}


</style>

<script type="text/javascript">

$(function(){
	$('#showallBtn').change(function(){
		if($(this).is(':checked')){
			$('.tableRow.siteOcc').addClass('open');
		}
		else{
			$('.tableRow.siteOcc').removeClass('open');
		}
	});
});



function showAll(siteId){
	$('.tableRow.siteOcc[data-sitenum='+siteId+']').toggleClass('open');
}
</script>



<div class="bodyBG" id="addEventPop">
	<div class="popup">
		<div class="maintitle">
			<span class="closer" onclick="closePopID('addEventPop')"></span>
			<span class="text">עדכון תפוסה</span>
			<div class="switchWrap">
				<label class="switch">
				  <input type="checkbox" id="realBook" value="1" />
				  <span class="slider round"></span>
				</label>
				<div class="switchTtl">צור הזמנה</div>
			</div>
		</div>
		<div class="content">
			<div class="dates">
				 <input type="text" id="datepickerFrom" name="event_start" required value="" title="תאריך כניסה" />
				 <input type="text" id="datepickerTo" name="event_end" required value="" class="dead" title="תאריך יציאה" />
			</div>
			<div class="nights inputs chosen" id="nightsDiv">
				<i class="fa fa-minus-circle inputChanger disabled" id="remNight"></i>
				<input type="hidden" id="nightsValue" class="chosen" value="1" />
				<span class="inputtextLbl" id="inputtextLbl">לילה אחד</span>
				<i class="fa fa-plus-circle inputChanger" id="addNight"></i>
			</div>
			<div class="rooms" id="roomsDiv">
<?php
    foreach($rooms as $roomID => $roomName){
?>
				<div class="line notavailable" data-room-id="<?=$roomID?>">
					<div class="text">
						<div class="title"><?=$roomName?></div>
						<div class="desc">0 פנויות בין התאריכים</div>
					</div>
					<div class="inputs">
						<i class="fa fa-minus-circle inputChanger Minus disabled"></i>
						<input type="text" value="0" disabled title="" />
						<i class="fa fa-plus-circle inputChanger Plus"></i>
					</div>
				</div>
<?php
    }

    /*
?>
				<div class="line">
					<div class="text">
						<div class="title available">היחידה הדרומית</div>
						<div class="desc notavailable">0 יחידות בין התאריכים</div>
					</div>
					<div class="inputs notavailable">
						<i class="fa fa-minus-circle inputChanger Minus" data-action="-1" data-inputID="roomValue2"></i>
						<input type="text" id="roomValue2" class="notavailable" value="0" disabled>
						<i class="fa fa-plus-circle inputChanger Plus" data-action="+1" data-inputID="roomValue2"></i>
					</div>
				</div>
    */ ?>
			</div>				
			<div class="btnWrap" id="book">ביצוע עדכון</div>
		</div>
	</div>
</div>

<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
	<?=showTopTabs(0)?>
	<div class="calendarWrap" id="calendarWrap">
		<div class="topSection">
			<div class="closeCal"></div>
			<div class="calTitle">עדכון תפוסה</div>
			<div class="newEventBtn" onclick="openPopID('addEventPop')"></div>
			<div class="switchWrap">
				<label class="switch">
				  <input type="checkbox" name="showall" id="showallBtn" value="1">
				  <span class="slider round"></span>
				</label>
				<div class="switchTtl">הזמנות</div>
			</div>
		</div>
		<div class="monthLine">
			<div class="monthName"></div>
			<div class="rArrowMonth"><i class="fa fa-angle-right"></i></div><div class="lArrowMonth"><i class="fa fa-angle-left"></i></div>
		</div>
	</div>
</div>
<script type="text/javascript" src="calendar.js?1"></script>
<script type="text/javascript">
var weeks = {}, caller = {calendar:null, getWeek:getWeek, showWeek:showWeek, setFree:setFree};

function openPopID(id){
	$('#'+id).show();
}
function closePopID(id){
	$('#'+id).hide();
}

function getWeek(start){
    if (weeks[start])
        return Promise.resolve(weeks[start]);

    return $.get('ajax_week.php', {act:'week', siteID:<?=$siteID?>, start:start}).then(function(data){
        if (data.status === undefined || parseInt(data.status))
            return alert('שגיאה', 'לא מצליח למשוך נתוני תפוסה', 'error');
        return weeks[start] = new CalendarWeek(data.rooms, {sunday: start, caller:caller});
    }).fail(function(){
        alert('שגיאה', 'לא מצליח למשוך נתוני תפוסה', 'error');
    });
}

function showWeek(sunday, dir){
    getWeek(sunday).then(function(week){
        var $wrap = $('#calendarWrap'), $old = $wrap.children('.calendar'), $new = $(week.cont).addClass(dir);

        $wrap.children('.monthLine').after($new);
        setTimeout(function(){
            $new.removeClass(dir);
            $old.detach();

            /*$wrap.find('.calendar').each(function(){
                var self = $(this);
                self.hasClass(dir) ? self.removeClass(dir) : self.detach();
            });*/
        }, 500);
    });
}

function setFree(date, unit, change){
    if (setFree.timeout)
        window.clearTimeout(setFree.timeout);

    if (!setFree.stack[date])
        setFree.stack[date] = {};
    setFree.stack[date][unit] = change;

    setFree.timeout = window.setTimeout(function(){
        $.post('ajax_week.php', {act:'free', siteID:<?=$siteID?>, data:setFree.stack}).then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return alert('שגיאה', 'לא מצליח לעדכן נתוני תפוסה', 'error');
        });

        setFree.timeout = 0;
        setFree.stack   = {};
    }, 500);
}
setFree.timeout = 0;
setFree.stack   = {};


/*var days = 1;
updateDaysLbl();
function updateDaysLbl(){

		if(days==1){
			$('.inputtextLbl').html('לילה אחד');
			$('.nights .fa-minus-circle').css('color','#a2a2a2');
		}	
		else{
			$('.inputtextLbl').html(days + " לילות");
			$('.nights .fa-minus-circle').css('color','#1e8fca');
		}
		$('#nightsValue').val(days);

}


$('.inputChanger').click(function(){

	if($(this).attr('data-inputID')=='nightsValue'){

		if (!$('#datepickerFrom').val()){
			alert('נא בחר תאריך התחלה');
			return;
		}
		if(days==1 && $(this).attr('data-action')=="-1"){ return; }

		days = days + parseInt($(this).attr('data-action'));
		$('#'+$(this).attr('data-inputID')).val(days);

		if($('#datepickerFrom').val()){

			var date = new Date($("#datepickerFrom").val().split('/').reverse().join('/'));
			date.setDate(date.getDate() + days);
			$("#datepickerTo").datepicker('setDate', date);
	
		}
		updateDaysLbl()

	}
});

function chnageDaysLbl(){

	if(!$('#datepickerTo').val()){

		var date = new Date($("#datepickerFrom").val().split('/').reverse().join('/'));
		date.setDate(date.getDate() + 1);
		$("#datepickerTo").datepicker('setDate', date);
	}

	if($('#datepickerTo').val() && $('#datepickerFrom').val()){
		var start= $("#datepickerFrom").datepicker("getDate");
		var end= $("#datepickerTo").datepicker("getDate");
		days = (end - start) / (1000 * 60 * 60 * 24);

		updateDaysLbl()

	}
}

	var loadrightNext = function(){
		$.post('ajax_week.php', {'dir':'right'}, function(resuls){
			$(resuls).find('.lArrowWeek').click(loadleftNext).end().find('.rArrowWeek').click(loadrightNext).end().insertAfter('.monthLine');
			setTimeout(function(){
				$('.calendar').not('.right').remove();
				$('.calendar').removeClass('right');
			},500);
		});
	};
	var loadleftNext = function(){
		$.post('ajax_week.php', {'dir':'left'}, function(resuls){
			$(resuls).find('.lArrowWeek').click(loadleftNext).end().find('.rArrowWeek').click(loadrightNext).end().insertAfter('.monthLine');
			setTimeout(function(){
				$('.calendar').not('.left').remove();
				$('.calendar').removeClass('left');
			},500);
		});
	};



	//test scripts
	$('.rArrowWeek').click(loadrightNext);
	$('.lArrowWeek').click(loadleftNext);*/

// starting calendar
$(function(){
    //$.getScript('calendar.js?rnd=' + Math.random()).then(function(){
        var nights, rooms, timer = 0;

        caller.calendar = new CalendarMonth({
            cont: $('.monthLine', '#calendarWrap'),
            date: '<?=$sunday?>'
        }, caller);

        rooms = $('#roomsDiv').children('.line').map(function(){
            return new CalendarPopRoom(this);
        }).get();

        nights = new CalendarCounter({
            cont : $('#nightsDiv'),
            count: 1,
            min  : 1,
            max  : 14,
            refresh: function(cnt){
                $('#inputtextLbl').text(cnt == 1 ? 'לילה אחת' : cnt + ' לילות');
                $('#addNight')[cnt >= 14 ? 'addClass' : 'removeClass']('disabled');
                $('#remNight')[cnt <= 1 ? 'addClass' : 'removeClass']('disabled');
            },
            callback: function(cnt){
                var from = $("#datepickerFrom").val().toDate();
                from && $('#datepickerTo').datepicker('setDate', from.add(cnt));

                if (timer)
                    window.clearTimeout(timer);

                timer = window.setTimeout(function(){
                    timer = 0;

                    $.get('ajax_week.php', {act:'range', siteID:<?=$siteID?>, from:from.toUnixDate(), nights:cnt}).then(function(res){
                        if (res.status === undefined || parseInt(res.status))
                            return alert('שגיאה', 'לא מצליח לעדכן נתוני תפוסה', 'error');

                        $.each(rooms, function(i, room){
                            var id = room.room_id();
                            room.setMax(res.rooms[id] || 0);
                        });
                    });
                }, 300);
            }
        });

        $("#datepickerFrom").datepicker({
            onSelect: function(date){
                nights.set(1);
                $('#nightsDiv').removeClass('notavailable');
                $('#datepickerTo').removeClass('dead').datepicker('setDate', date.toDate().add(1));
            }
        });

        $('#datepickerTo').datepicker({
            onSelect: function(date){
                var from = $("#datepickerFrom").val();
                nights.set(Math.round((date.toDate() - from.toDate()) / (1000 * 3600 * 24)));
            },
            beforeShow: function(){
                return {minDate: $('#datepickerFrom').val() || 0};
            }
        });

        $('#book').on('click', function(){
            var rList = {}, total = 0, from = $('#datepickerFrom').val(), n = nights.counter, real = $('#realBook').prop('checked');

            $.each(rooms, function(i, room){
                if (room.chosen){
                    rList[room.room_id()] = room.chosen;
                    total += room.chosen;
                }
            });

            if (real && from && n){
                from = from.toDate();
                window.open('/site/<?=$siteID?>?date1=' + from.toUnixDate() + '&date2=' + from.add(n).toUnixDate());
            }
            else if (from && n && total)
                $.post('ajax_week.php', {act:'filler', siteID:<?=$siteID?>, from:from.toDate().toUnixDate(), nights:n, rooms:rList}).then(function(res){
                    if (res.status === undefined || parseInt(res.status))
                        return alert('שגיאה', 'לא מצליח לעדכן נתוני תפוסה', 'error');
                    window.location.reload();
                });
        });

        /*var week = new CalendarWeek([
            {
                id: 1234,
                units: 3,
                name: 'best room',
                orders: [],
                empty: {
                    '2018-11-21': 2,
                    '2018-11-22': 1,
                    '2018-11-23': 2
                }
            },
            {
                id: 2346,
                units: 2,
                name: 'good room',
                orders: [{
                    start: '2018-11-18',
                    nights: 2,
                    title: 'שלומי גותליב'
                },
                {
                    start: '2018-11-19',
                    nights: 2,
                    title: 'קווין ספייסי'
                },
                {
                    start: '2018-11-20',
                    nights: 2,
                    title: "ג'ון ראמבו"
                }],
                empty: {
                    '2018-11-20': 0,
                    '2018-11-21': 0,
                    '2018-11-22': 2,
                    '2018-11-23': 1
                }
            }
        ],
        {
            sunday: '2018-11-18'
        });

        $('.monthLine').after(week.cont);*/
    //});
});
</script>
