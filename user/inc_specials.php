<?php

include_once "auth.php";
$siteID   = intval($_GET['siteID']);
$specials = array();


$que = "SELECT * FROM `benefits` WHERE siteID=".$siteID;
$sales = udb::full_list($que);

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>מבצעים</title>
	<link rel="stylesheet" href="/user/assets/addons/datetimepicker-master/jquery.datetimepicker.min.css">
</head>
<body>

<style type="text/css">
*{margin:0;padding:0;vertical-align:top;outline:0;border:0}
@font-face{font-family:'Segoe UI Regular';font-style:normal;font-weight:400;src:local('Segoe UI Regular'),url('fonts/Segoe UI.woff') format('woff')}
@font-face{font-family:'Segoe UI Bold';font-style:normal;font-weight:400;src:local('Segoe UI Bold'),url('fonts/Segoe UI Bold.woff') format('woff')}
@font-face{font-family:'Segoe UI Semibold';font-style:normal;font-weight:400;src:local('Segoe UI Semibold'),url('fonts/Segoe UI Semibold.woff') format('woff')}
body{font-family:Rubik,arial;background:transparent}
button,input,select,textarea{font-family:inherit}
img{max-width:100%}

#specTab {margin:20px auto; direction:RTL;}
#specTab TH {border-bottom:1px solid #ccc; padding:5px}
#specTab TD {direction:RTL; text-align:center; cursor:pointer; padding:5px}
#specTab TD.dates {direction:LTR; text-align:center}
#specTab TD.reg {direction:RTL; text-align:right}

.wrapper{text-align: center;    direction: rtl;}
.wrapper .ttl{font-size: 20px;text-align: right;font-weight:bold}
.addNewSaleBtn{white-space: nowrap;box-shadow: inset 0px -3px 7px 0px #29bbff;background: linear-gradient(to bottom, #2dabf9 5%, #0688fa 100%);background-color: #2dabf9;border-radius: 3px;border: 1px solid #0b0e07;display: inline-block;cursor: pointer;color: #ffffff;font-family: Arial;font-size: 15px;padding: 9px 23px;text-decoration: none;text-shadow: 0px 1px 0px #263666;}

.salePopWrap{background: rgba(46,55,63,0.8); position: absolute;left: 0;right: 0;top: 0;bottom: 0;display: flex;align-content: center;justify-content: center;}
.salePopWrap .salePop {position: relative;margin: 20px auto;max-width: 540px;min-height: 10vh;width: 100%;border-radius: 0;background: white;overflow: auto;border: 4px black solid;}
.salePopWrap .salePop .salePopTtl {font-size: 26px;color: #569ce3;text-align: center;padding: 6px 0;position: absolute;left: 0;right: 0;background: white;line-height: 38px;font-weight: bold;box-shadow: 0 0 10px;}
.salePopWrap .close{color: #000;position: absolute;left: 7px;top:7px;cursor: pointer;width:30px;height:30px;border-radius:50%;border:2px black solid;box-sizing:border-box}
.salePopWrap .close::before{content:"";width:20px;height:2px;position:absolute;left:0;right:0;top:0;bottom:0;margin:auto;transform:rotate(45deg);background:black}
.salePopWrap .close::after{content:"";width:20px;height:2px;position:absolute;left:0;right:0;top:0;bottom:0;margin:auto;transform:rotate(135deg);background:black}
.salePopWrap .linePop{height: 2px;width:100%;margin:0 auto;background:#333;}
.salePopWrap .linePop.fade{opacity: 0.2;height:1px}
.salePopWrap .inWrap {padding: 10px;position: absolute;top: 50px;bottom: 0;left: 0;right: 0;overflow: auto;}
.salePopWrap .inWrap .radioFullWrap{text-align: right;padding: 15px 0;}
.radioWrap{display: inline-block;vertical-align: middle;margin-left:12px;color:#555}
.radioWrap.active {background: linear-gradient(45deg, white, #d2e4f7);border-radius: 0 20px 20px 0;color: black;font-weight:500}
.radioWrap input{display: none;}
.radioWrap input[type="radio"]:checked + .radioLbl::after{content: "";width: 18px;height: 18px;background: #569ce3;border-radius: 26px;display: block;margin: auto;position: absolute;left: 0;right: 0;bottom: 0;top: 0;}
/*.radioWrap input[type="radio"]:checked + .radioTxtLbl{color: #2e373f;font-weight: bold;}*/
.radioWrap .radioLbl{position: relative;width: 30px;height: 30px;box-sizing:border-box;border:1px solid #a1a7ad;background: #fff;display: inline-block;vertical-align: middle;border-radius:50px;cursor: pointer;}
.radioWrap.active .radioLbl{border-color:#333}
.radioWrap .radioTxtLbl{cursor: pointer;line-height: 16px;font-size: 14px;text-align: right;width: 62px;display: inline-block;vertical-align: middle;padding-right:6px;}
.inputTxtWrap{text-align: right;padding: 15px 0;}
.inputTxtWrap.dates{display: none;}
.inputTxtWrap.dates.active{display: block;}
.inputTxtWrap label{display: inline-block;vertical-align: middle;font-size: 16px;color: #2e373f;line-height:38px}
.inputTxtWrap input[type="text"],.inputTxtWrap input[type="number"]{font-size: 16px;display: inline-block;vertical-align: middle;margin-right: 10px;width: 70px;height: 36px;border:1px solid #a1a7ad; border-radius:5px;text-align: center;}
.inputTxtWrap.daysBefore label span:nth-child(2){display:none}
.inputTxtWrap.daysBefore.ahead label span:nth-child(2){display:block}
.inputTxtWrap.daysBefore.ahead label span:nth-child(1){display:none}
.inputTxtWrap .symbol{display: inline-block;vertical-align: middle;font-size: 20px;color: #2e373f;margin-right: 4px;}
.inputTxtWrap.heightSale{display: none;}
.inputTxtWrap.heightSale.show{display: block;}
.inputTxtWrap.heightSale .inputOrSelect{display: none;}
.inputTxtWrap.heightSale .inputOrSelect.show{display: inline-block;}
.inputTxtWrap.heightSale .inputOrSelect select{height: 42px;border-radius: 5px;width: 70px;font-size: 19px;}
.inputTxtWrap .datePick{width: 130px !important;margin: 0 !important;}
.inputTxtWrap .datePick{outline:0}
.inputTxtWrap .datePick.rgt{ border-left: 1px #ccc solid;border-radius: 0 5px 5px 0;}
.inputTxtWrap .datePick.lft{ border-right: 0;border-radius: 5px 0px 0px 5px;margin-right: -4px !important;margin-left:10px !important;}
.inputTxtWrap.daysBefore{display: none;}
.inputTxtWrap.daysBefore.active{display: block;}
.inputTxtWrap.toDate > label{display: block;width: 100%;}
.checkBoxWrap{display: inline-block;vertical-align: middle;margin-left:12px;}
.checkBoxWrap input[type="checkbox"]{display: none;}
.checkBoxWrap .chckBoxLbl{cursor: pointer;position: relative;width: 30px;height: 30px;box-sizing:border-box;border:1px solid #a1a7ad;background: #fff;display: 
inline-block;vertical-align: middle;border-radius:5px;cursor: pointer;}
.checkBoxWrap input[type="checkbox"]:checked + .chckBoxLbl::after{content: "";position: absolute;left: 6px;bottom: 10px;width: 14px;height: 4px;border: 3px solid #569ce3;border-left: 0;border-bottom: 0;transform: rotate(135deg);}
.checkBoxWrap .chckBoxTxt{cursor: pointer;line-height: 18px;font-size: 14px;text-align: right;color: #2e373f;width: 62px;display: inline-block;vertical-align: middle;padding-right:6px;}
.radioFullWrap .checkBoxNote{width: 100px;display: inline-block;vertical-align: middle;font-size: 14px;color: #a1a7ad;line-height: 16px;}
.radioFullWrap .ttl{color: #2e373f;margin-bottom: 10px;}
.salePopWrap .salePop .approveBtn{background: red;text-align: center;padding: 10px 20px;cursor: pointer;color: #fff;font-size: 24px;border-radius:6px;display: inline-block;margin:10px 0;font-weight:bold}
.salePopWrap .inWrap .radioFullWrap.roomsChecks{display: none;}
.salePopWrap .inWrap .radioFullWrap.roomsChecks.active{display: block;}

.salePopWrap .sec_title {font-weight: bold;margin-top: -4px;padding-bottom: 10px;}

.inputLblWrap {display: inline-block;vertical-align: middle;}
.inputLblWrap .switchTtl {display: block;vertical-align: middle;font-weight: bold;margin-bottom: 5px;color: #569ce3;font-size: 14px;}
.inputLblWrap .switch {position: relative;display: inline-block;width: 60px;height: 34px;}
.inputLblWrap .switch input {display: none;}
.inputLblWrap .switch .slider {position: absolute;cursor: pointer;top: 0;left: 0;right: 0;bottom: 0;background-color: #ccc;-webkit-transition: .4s;transition: .4s;}
.inputLblWrap .switch .slider.round {border-radius: 34px;}
.inputLblWrap .switch .slider.round:before {border-radius: 50%;}
.inputLblWrap .switch .slider:before {position: absolute;content: "";height: 26px;width: 26px;left: 4px;bottom: 4px;background-color: white;-webkit-transition: .4s;transition: .4s;}
.inputLblWrap .switch input:checked + .slider {background-color: #569ce3;}
.inputLblWrap .switch input:focus + .slider {box-shadow: 0 0 1px #569ce3;}
.inputLblWrap .switch input:checked + .slider:before {-webkit-transform: translateX(26px);-ms-transform: translateX(26px);transform: translateX(26px);}

.inputPresent{display: none;}
.inputPresent.show{display: block;}
.inputPresent .inputTxtWrap input {width: 90%;text-align: left;padding: 0 10px;box-sizing: border-box;}
.inputPresent .inputTxtWrap:last-child input{width: 70px;}
.inputPresent .inputTxtWrap:first-child input{text-align: right;}
.salesWrapper{margin-top: 50px;}
.salesWrapper .saleStrip {position: relative;max-width: 880px;min-height: 100px;display: block;margin: 30px 0;background: #FFF;border-radius: 10px;margin-right: 10px;overflow: hidden;box-sizing: border-box;padding: 10px;border: 1px #AAA solid;}
.salesWrapper .saleStrip .rgtStrip{width: 100px;float: right;margin-top: 6px;}
.salesWrapper .saleStrip .midSrip{float: right;width: calc(100% - 272px);}
.salesWrapper .saleStrip .midSrip .saleName{text-align: right;font-size: 20px;font-weight: bold;color: #569ce3;}
.salesWrapper .saleStrip .midSrip .saleDescWrap{text-align: right;}
.salesWrapper .saleStrip .midSrip .saleDescWrap .saleline{display: block;}
.salesWrapper .saleStrip .midSrip .saleDescWrap .saleDesc{font-size: 14px;line-height: 18px;color: #2e373f;display: inline-block;vertical-align: top;}
.salesWrapper .saleStrip .midSrip .saleDescWrap .round{width: 6px;height: 6px;border-radius:6px;margin: 5px 6px;background:#454d54;display: inline-block;vertical-align: top;}
.salesWrapper .saleStrip .lftStrip{position: absolute;left: 0;top: 0;width: 170px;}
.salesWrapper .saleStrip .lftStrip .editBtn{position: absolute;left: 100px;top: 34px;}
.salesWrapper .saleStrip .lftStrip .editBtn i{}
.salesWrapper .saleStrip .lftStrip .editBtn i svg{width: 35px;fill:#579de3;cursor: pointer;}
.salesWrapper .saleStrip .lftStrip .removeBtn{position: absolute;left: 30px;top: 34px;}
.salesWrapper .saleStrip .lftStrip .removeBtn i{}
.salesWrapper .saleStrip .lftStrip .removeBtn i svg{width: 35px;fill:#a1a7ad;cursor: pointer;}


@media(max-width:768px){
	.salesWrapper .saleStrip .rgtStrip{float:none}
	.salesWrapper .saleStrip .midSrip{float:none;width: 100%;}
	.salesWrapper .saleStrip .lftStrip{width: 140px;}
}
</style>


<div class="wrapper" id="taboffers">
	<div class="ttl"></div>
	<div class="addNewSaleBtn">הוסף חדש</div>

	<div class="salesWrapper">
		<?php if($sales) { 
		foreach($sales as $sale){ 

			$sentence = "";

			switch ($sale['benefitType']){
				case 1:
					$sentence .= $sale['benefitPrice']." שקלים הנחה";
				break;
				case 2:
					$sentence .= $sale['benefitPrice']."% הנחה";
				break;
				case 3:
					$sentence .= $sale['benefitPrice']." שקלים הנחת מתנה";
				break;
				case 4:
					$sentence .= $sale['benefitPrice']."% לילה חינם";
				break;
			
			}
			if($sale['benefitTo']==1){
				$sentence .=" לכולם";
			
			}else{
				$sentence .=" לחברי מועדון";
			}
			$sentence2= "";
			switch ($sale['benefitTiming']){
				case 1:
					$sentence2 .= "בכל הזמנה";
				break;
				case 2:
					$sentence2 .= "ברגע האחרון";
				break;
				case 3:
					$sentence2 .= "בהזמנה מראש";
				break;
				case 4:
					$sentence2 .= "בין התאריכים ".implode('/',array_reverse(explode('-',trim($sale['benefitDateStart']))))." - ".implode('/',array_reverse(explode('-',trim($sale['benefitDateEnd']))));
				break;
			
			}
			$sentence3= "";
			switch ($sale['benfitWeek']){
				case 1:
					$sentence3 .= "תקף לאמצ\"ש";
				break;
				case 2:
					$sentence3 .= "תקף לסופ\"ש";
				break;
				case 3:
					$sentence3 .= "תקף לכל השבוע";
				break;
			
			}
			$sentence4 = "בהזמנה של לפחות ".$sale['benefitMinDates']." לילות בין התאריכים ".implode('/',array_reverse(explode('-',trim($sale['orderActualFrom']))))." - ".implode('/',array_reverse(explode('-',trim($sale['orderActualTill']))));

			if($sale['noHot']){
				
				$sentence4 .=" לא כולל תקופות חמות";
			}
			if($sale['benefitUnits']==1){
			
				$sentence5 = "תקף לכל היחידות";
			}else{
				$saleUnits = udb::full_list("SELECT rooms.roomName FROM rooms
				INNER JOIN benefits_units USING(roomID)
				WHERE `benefitID`=".$sale['benefitID']);
				$sentence5 = "תקף ל-";
				foreach($saleUnits as $unit){

					$sentence5 .= $unit['roomName']." ";
				
				}
			}
			
		?>
		<div class="saleStrip" data-saleid="<?=$sale['benefitID']?>">
			<div class="rgtStrip">
				<div class="inputLblWrap">
					<div class="switchTtl">פעיל</div>
					<label class="switch">
					  <input type="checkbox" name="approved" value="1" <?=$sale['active']?"checked":""?>>
					  <span class="slider round"></span>
					</label>
				</div>
			</div>
			<div class="midSrip">
				<div class="saleName"><?=$sentence?></div>
				<div class="saleDescWrap">
					<div class="saleline">
						<div class="saleDesc"><?=$sentence2?></div>
						<div class="round"></div>
						<div class="saleDesc"><?=$sentence3?></div>
					</div>
					<div class="saleline">
						<div class="saleDesc"><?=$sentence4?></div>
						<div class="round"></div>
						<div class="saleDesc"><?=$sentence5?></div>
					</div>
				</div>
			</div>
			<div class="lftStrip">
				<div class="editBtn" onclick="showSale(<?=$sale['benefitID']?>)"><i><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 469.336 469.336" xml:space="preserve" enable-background="new 0 0 469.336 469.336"><g><g><g><path d="M347.878 151.357c-4-4.003-11.083-4.003-15.083 0L129.909 354.414c-2.427 2.429-3.531 5.87-2.99 9.258 0.552 3.388 2.698 6.307 5.76 7.84l16.656 8.34v28.049l-51.031 14.602 -51.51-51.554 14.59-51.075h28.025l8.333 16.67c1.531 3.065 4.448 5.213 7.833 5.765 0.573 0.094 1.146 0.135 1.708 0.135 2.802 0 5.531-1.105 7.542-3.128L317.711 136.26c2-2.002 3.125-4.712 3.125-7.548 0-2.836-1.125-5.546-3.125-7.548l-39.229-39.263c-2-2.002-4.708-3.128-7.542-3.128h-0.021c-2.844 0.01-5.563 1.147-7.552 3.159L45.763 301.682c-0.105 0.107-0.1 0.27-0.201 0.379 -1.095 1.183-2.009 2.549-2.487 4.208l-18.521 64.857L0.409 455.73c-1.063 3.722-0.021 7.736 2.719 10.478 2.031 2.033 4.75 3.128 7.542 3.128 0.979 0 1.958-0.136 2.927-0.407l84.531-24.166 64.802-18.537c0.195-0.056 0.329-0.203 0.52-0.27 0.673-0.232 1.262-0.61 1.881-0.976 0.608-0.361 1.216-0.682 1.73-1.146 0.138-0.122 0.319-0.167 0.452-0.298l219.563-217.789c2.01-1.991 3.146-4.712 3.156-7.558 0.01-2.836-1.115-5.557-3.125-7.569L347.878 151.357z"></path><path d="M456.836 76.168l-64-64.054c-16.125-16.139-44.177-16.17-60.365 0.031l-39.073 39.461c-4.135 4.181-4.125 10.905 0.031 15.065l108.896 108.988c2.083 2.085 4.813 3.128 7.542 3.128 2.719 0 5.427-1.032 7.51-3.096l39.458-39.137c8.063-8.069 12.5-18.787 12.5-30.192S464.899 84.237 456.836 76.168z"></path></g></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg></i></div>
				<div class="removeBtn"><i>
				<svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" viewBox="0 0 443 443" xml:space="preserve"><path d="M321.8 38h-83.4V0H125.2v38H41.8v60h280V38zM155.2 30h53.2v8h-53.2V30zM295.1 214.3l5.7-86.3H62.8l19 290h114.2c-14.9-21.1-23.6-46.7-23.6-74.4C172.4 274.4 226.8 217.8 295.1 214.3zM301.8 244.1c-54.8 0-99.4 44.6-99.4 99.4S247 443 301.8 443s99.4-44.6 99.4-99.4S356.6 244.1 301.8 244.1zM356 376.5l-21.2 21.2 -33-33 -33 33 -21.2-21.2 33-33 -33-33 21.2-21.2 33 33 33-33 21.2 21.2 -33 33L356 376.5z"></path></svg>
				</i></div>
			</div>

		</div>
		<?php } } ?>
	</div>
		

</div>


<script src="/user/assets/js/jquery-2.2.4.min.js"></script>
<script src="/user/assets/addons/datetimepicker-master/jquery.datetimepicker.full.min.js"></script>
<script type="text/javascript">

function showSale(id){

	$.get("/user/ajax_salePop.php",{siteID:<?=$siteID?>,id:id},function(res){
		$("#taboffers").append(res);
		
	}).done(function(){
	
		ajaxLoadScripts();
	
	});

}
$(document).ready(function(){
	$('.addNewSaleBtn').click(function(){
		$.get("/user/ajax_salePop.php",{siteID:<?=$siteID?>},function(res){
			$("#taboffers").append(res);
		}).done(function(){
			ajaxLoadScripts();
		});
	});
	$('input[name="approved"]').on("change", function(){
		var id = $(this).closest('.saleStrip').data('saleid');
		$.post("/user/ajax_salePop.php",{action:"active",status:($(this).is(":checked")?1:0),id:id});
	
	});
	$('.removeBtn').click(function(){
		if(confirm("האם אתה בטוח שאתה רוצה למחוק מבצע זה?")){
			var id = $(this).closest('.saleStrip').data('saleid');
			$.post("/user/ajax_salePop.php",{action:"del",id:id}).done(function(){
				window.location.reload();
			});
		}
	});
});

function ajaxLoadScripts(){
	if($.datetimepicker){
		$.datetimepicker.setLocale('he');
	}
	$('.salePopWrap .close').click(function(){
		$('.salePopWrap').remove();
		$('.xdsoft_datetimepicker').remove();
	});
	$('input[type="radio"]').on("click load",function(e){
		if(e.type=="click"){
			$(this).parent().addClass("active").siblings().removeClass("active");
		}else{
			if($(this).is(":checked")){
				$(this).parent().addClass("active").siblings().removeClass("active");
			}
		
		}
		
		if(this.name == "saleType"){
			switch (this.value)
			{
				case "1":
					if(this.checked){
						$('.inputTxtWrap.heightSale .symbol').text("₪");
						$('.heightSale').addClass("show");
						$('.inputOrSelect.nis').addClass("show").siblings().removeClass("show");
						$("input[name='salePrice']").prop("disabled", false);
						$("select[name='salePrice']").prop("disabled", true);
					}
				break;

				case "2":
					if(this.checked){
						$('.inputTxtWrap.heightSale .symbol').text("%");
						$('.heightSale').addClass("show");
						$('.inputOrSelect.pre').addClass("show").siblings().removeClass("show");
						$("input[name='salePrice']").prop("disabled", true);
						$('.inputOrSelect.pre select').prop("disabled", false);
						$('.inputOrSelect.last select').prop("disabled", true);
					}
				break;
/*
				case "3":
					$('.inputTxtWrap.heightSale .symbol').text("₪");
				break;
*/
				case "4":
					if(this.checked){
						$('.inputTxtWrap.heightSale .symbol').text("%");
						$('input[name="salePrice"]').val()==""?$('input[name="salePrice"]').val("100"):"";
						$('.heightSale').addClass("show");
						$('.inputOrSelect.last').addClass("show").siblings().removeClass("show");
						$('.inputOrSelect.last select').prop("disabled", false);
						$('.inputOrSelect.pre select').prop("disabled", true);
						$("input[name='salePrice']").prop("disabled", true);
					}

				break;
			}
		}
		if(this.name == "saleWhen"){
			switch (this.value)
			{
				case "1":
					$('.inputTxtWrap.dates').removeClass("active");
					$('.daysBefore').removeClass("active");
				break;
				case "4":
					$('.inputTxtWrap.dates').addClass("active");
					$('.daysBefore').removeClass("active");
				break;
				case "3":
					$('.inputTxtWrap.dates').removeClass("active");
					$('.daysBefore').addClass("active");
					$('.daysBefore').addClass("ahead");
				break;
			 default:
					$('.inputTxtWrap.dates').removeClass("active");
					$('.daysBefore').addClass("active");
					$('.daysBefore').removeClass("ahead");
			}
		}
		if(this.name == "units"){
			if(this.value==2 && this.checked){
				$('.radioFullWrap.roomsChecks').addClass("active");
			}else{
				$('.radioFullWrap.roomsChecks').removeClass("active");
			}	
		}
	});

	$('input[name="present"]').on("click load",function(e){
		if(this.checked){
			if(e.type!="load"){
				$('input[name="saleType"]').prop('checked', false);
				$(this).closest(".radioFullWrap").find(".radioWrap").removeClass("active");
				$('.heightSale').removeClass("show");
				$('.inputPresent').addClass("show");
			}
			else{
				$('.inputPresent').addClass("show");
			}
		}else{
			if(e.type=="click"){
				$('#saleType1').prop('checked', true);
				$('.inputPresent').removeClass("show");
				$(this).closest(".radioFullWrap").find(".radioWrap").removeClass("active");
				$('.heightSale').addClass("show");
			}else{
			
			}
			
		}	
	});


	$('.datePick.rgt.top').datetimepicker({
        format: 'd/m/Y',
        timepicker: false
		
    });
    $('.datePick.lft.top').datetimepicker({
        format: 'd/m/Y',
		  onShow:function( ct ){
		   this.setOptions({
			minDate:$('.datePick.rgt.top').val()?$('.datePick.rgt.top').val().split("/").reverse().join("-"):false
		   })
		  },
        timepicker: false
    });

	$('.datePick.rgt.bot').datetimepicker({
        format: 'd/m/Y',
        timepicker: false
		
    });
    $('.datePick.lft.bot').datetimepicker({
        format: 'd/m/Y',
		  onShow:function( ct ){
		   this.setOptions({
			minDate:$('.datePick.rgt.bot').val()?$('.datePick.rgt.bot').val().split("/").reverse().join("-"):false
		   })
		  },
        timepicker: false
    });
	
	$(".approveBtn").click(function(){
		if(!$('#weekendValid1').is(":checked") && !$('#weekendValid2').is(":checked")){
			alert("נא לסמן תקף מוצ\"ש או אמצ\"ש");
			return
		}
		if($('#units2').is(":checked")){
			var checkRoom = false;
			$("input[name='roomsID[]']").each(function(){
				if($(this).is(":checked")){
					checkRoom = true;	
				}
			});
			if(!checkRoom){
				alert("נא לבחור יחידה למבצע");
				return
			}
		}
		$.post("/user/ajax_salePop.php",$("#saleForm").serialize(),function(res){

			window.location.reload();
		
		})

	});
	$('.salePopWrap input[type="checkbox"],.salePopWrap input[type="radio"]').trigger("load");

}
</script>


	
</body>
</html>