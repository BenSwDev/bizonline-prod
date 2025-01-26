<?php
include_once "../bin/system.php";
include_once "../bin/top_user.php";

$menu = include "menu_user.php";
$position=2;

?>	<div class="grid">
		<div class="userTabs">
			<?php foreach($menu as $men){ ?>
				<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>'"><p><?=$men['name']?></p></div>
			<?php } ?>
		</div>
		<div class="userCont">
			<form method="post" action="" id="" class="promoForm">
				<div class="status">
					<div class="statusTxt">מנוי פעיל</div>
					<div class="until">עד 15.09.2016</div>
					<div class="renew">
						<input type="checkbox" name="renew" id="renewBox" class="checkBoxSty" readonly>
						<label for="renewBox" class="chakSty"></label>
						<label for="renewBox" class="renewLab">חידוש אוטומטי</label>
					</div>
				</div>
				<div class="buyAdMain">
					<div class="subscTtlWrap">
						<div class="subscTtl">דף ראשי</div>
						<div class="noAd">לא קיים פרסום</div>
					</div><!-- לשנות מחיר באינפוטים ובלייבל -->
					<div class="BtnWrap">
						<div class="radBtn">
							<div class="lengthSub">שבוע</div>
							<div class="costSub">250 ₪</div>
							<label for="short" class="lblRad"></label>
							<input type="radio" id="short" class="rad" name="mainPrices" value="">
						</div>
						<div class="radBtn">
							<div class="lengthSub">חודש</div>
							<div class="costSub">900 ₪</div>
							<label for="med" class="lblRad"></label>
							<input type="radio" id="med" class="rad" name="mainPrices" value="">
						</div>
						<div class="radBtn">
							<div class="lengthSub">3 חודשים</div>
							<div class="costSub">2600 ₪</div>
							<label for="long" class="lblRad"></label>
							<input type="radio" id="long" class="rad" name="mainPrices" value="">
						</div>
					</div>
					<div class="sendBtn">
						<div class="sendTtl">רכישת פרסום</div>
					</div>
				</div>
				<div class="topTen">
					<div class="topTtl">טופ 10</div>
					<div class="buyAd">נרכש פרסום</div>
					<div class="until">עד 15.09.2016</div>
					<div class="renew">
						<input type="checkbox" name="renew" id="renewBox" class="checkBoxSty" readonly>
						<label for="renewBox" class="chakSty"></label>
						<label for="renewBox" class="renewLab">חידוש אוטומטי</label>
					</div>
					<div class="perMonth">לחודש ₪900</div>
				</div>
				<div class="buyAdMain">
					<div class="subscTtlWrap">
						<div class="subscTtl">נצפים</div>
						<div class="noAd">לא קיים פרסום</div>
					</div><!-- לשנות מחיר באינפוטים ובלייבל -->
					<div class="BtnWrap">
						<div class="radBtn">
							<div class="lengthSub">שבוע</div>
							<div class="costSub">250 ₪</div>
							<label for="shortWa" class="lblRad"></label>
							<input type="radio" id="shortWa" class="rad" name="watchPrices" value="">
						</div>
						<div class="radBtn">
							<div class="lengthSub">חודש</div>
							<div class="costSub">900 ₪</div>
							<label for="medWa" class="lblRad"></label>
							<input type="radio" id="medWa" class="rad" name="watchPrices" value="">
						</div>
						<div class="radBtn">
							<div class="lengthSub">3 חודשים</div>
							<div class="costSub">2600 ₪</div>
							<label for="longWa" class="lblRad"></label>
							<input type="radio" id="longWa" class="rad" name="watchPrices" value="">
						</div>
					</div>
					<div class="buyBtn">
						<div class="buyTtl">בחרו תקופה לפרסום</div>
					</div>
				</div>
			</form>
		</div>
		<?=getFixedButtons()?>
	</div>
    <script>
        function openCalendar(id){
            $(".loaderUser").show();
            indexTabs++;
            $("#theTabs").append('<div class="aTab" style="z-index:200" id="aTab-'+indexTabs+'"><div class="inTabBox"><iframe id="frame'+indexTabs+'" frameborder=0 src="/cms/calendar/calendar.php?frame='+indexTabs+'&sID='+id+'"></iframe><div class="aTabCloser" onclick="closeTab(\''+indexTabs+'\')">x</div></div></div>');
            window.setTimeout(function(){
                $(".loaderUser").hide();
            }, 300);
        }


		$('.BtnWrap .radBtn').click(function(){
			$(this).parent().children('.radBtn').removeClass('choose');
			$(this).addClass('choose');	
		
		});
    </script>

<?php
include_once "../bin/footer.php";
?>