<?php
include_once "../bin/system.php";
include_once "../bin/top_user.php";

$menu = include "menu_user.php";
$position=3;

$dealsTypes=Array(1=>"בין תאריכים", 2=>"קבוע");
$daysInWeek=Array(1=>"כל ימות השבוע", 2=>'אמצ"ש', 3=>'סופ"ש');
$periodInYear=Array(1=>"כל תקופה", 2=>"תקופה רגילה בלבד");
$limitations=Array(1=>"יום לפני הזמנה", 2=>"עד יומיים לפני הזמנה", 3=>"עד 3 ימים לפני הזמנה", 4=>"ללא הגבלה");
$dealTo=Array(1=>"לילה אחד ומעלה", 2=>"לילה שני", 3=>"לילה שלישי", 4=>"יום כיף");

$que="SELECT sitesRooms.* FROM `sitesRooms` WHERE sitesRooms.siteID = ".$site['siteID']." ORDER BY showOrder";
$rooms= udb::key_row($que, "roomID");



$que="SELECT * FROM sitesSpecials WHERE siteID=".$site['siteID']." AND baseSys!=0";
$siteDeals = udb::key_row($que, Array("baseSys"));

$que="SELECT * FROM sitesSpecials WHERE siteID=".$site['siteID']." AND baseSys=0";
$allDeals = udb::key_row($que, Array("specID"));

$que="SELECT MainPages.MainPageID, MainPages.MainPageTitle, MainPages.ifShow, sitesExtrasNew.* 
	  FROM MainPages 
	  INNER JOIN sitesExtrasNew ON (MainPages.MainPageID=sitesExtrasNew.extraID AND siteID=".$site['siteID'].") 
	  WHERE MainPageType=20 AND MainPages.ifShow=1";
$extras=udb::full_list($que);

$que="SELECT * FROM sitesSpecialsSys WHERE siteID=0 AND active=1";
$sysDeals = udb::key_row($que, "specID");


$totalDeals = count($sysDeals);
if($site['dealPermission']){
	$totalDeals = $totalDeals + count($allDeals);
}
?>	<div class="grid">
		<div class="userTabs">
			<?php foreach($menu as $men){ ?>
				<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>'"><p><?=$men['name']?></p></div>
			<?php } ?>
		</div>
		<div class="userCont">
			<div class="dealForm" id="dealForm">
				<div class="dealsCount">
					<div class="allCount">בחרו את המבצעים שתרצו לפרסם</div>
					<?php if($site['dealPermission']){?>
						<div class="newDealBtn" data-siteID="<?=$site['siteID']?>">יצירת דיל חדש</div>
					<?php } ?>
				</div>
				<?php $i=0;
				if($allDeals && $site['dealPermission']){
				foreach($allDeals as $deal){
					$que = "SELECT * FROM `sitesSpecialsExtras` WHERE siteID=".$site['siteID']." AND specID=".$deal['specID']." ";
					$sitesSpecialsExtras= udb::key_row($que, "extraID");
					
					$que = "SELECT * FROM `sitesSpecialsRooms` WHERE siteID=".$site['siteID']." AND specID=".$deal['specID']." ";
					$sitesSpecialsRooms= udb::key_row($que, "roomID");
					?>
					<form class="goDeal" id="form<?=$i?>" method="post" enctype="multipart/form-data">
						<input type="hidden" name="siteID" value="<?=$site['siteID']?>">
						<input type="hidden" name="sysID" value="0">
						<input type="hidden" name="dealID" value="<?=$deal['specID']?>">
						<div class="allSelect">
							<div class="selectBoxNoBg">
								<p class="text">הגבלת הדיל לתאריך ההזמנה</p>
							</div>
							<div class="selectBox" >
								<select name="limitations" class="oneSmall" onchange="changeSave(<?=$i?>)">
									<?php foreach($limitations as $dType=>$dText){ ?>
										<option value="<?=$dType?>" <?=($dType==$deal['limitations']?"selected":"")?>><?=$dText?></option>
									<?php } ?>
								</select>
							</div>

							<div class="selectBox">
								<select name="discount" class="oneBig discountSelect">
									<?php foreach(range(10,100,10) as $n){  echo '<option value="',$n,'" ',( $deal['discount'] == $n ? 'selected' : ''),'>',$n,'% הנחה</option>'; }	?>
									<option value="0" <?=$deal['discount']==0 && $deal['specID']?"selected":""?> >מתנה</option>
								</select>
							</div>
							<div class="selectBox">
								<select name="daysInWeek" class="oneSmall" onchange="changeSave(<?=$i?>)">
									<?php foreach($daysInWeek as $dType=>$dText){ ?>
										<option value="<?=$dType?>" <?=($dType==$deal['daysInWeek']?"selected":"")?>><?=$dText?></option>
									<?php } ?>
								</select>
							</div>
							<div class="openBox">
								<div class="selectBox">
									<select name="dealTo" class="withSpn" onchange="changeSave(<?=$i?>)">
										<?php foreach($dealTo as $dType=>$dText){ ?>
											<option value="<?=$dType?>" <?=$dType==$deal['dealTo']?"selected":""?>><?=$dText?></option>
										<?php } ?>
									</select>
									<span>הנחה תקפה על</span>
								</div>
								<div class="selectBox">
									<select name="periodInYear" class="withSpn" onchange="changeSave(<?=$i?>)">
										<?php foreach($periodInYear as $dType=>$dText){ ?>
											<option value="<?=$dType?>" <?=$dType==$deal['periodInYear']?"selected":""?>><?=$dText?></option>
										<?php } ?>
									</select>
									<span>הנחה תקפה ב</span>
								</div>
								<div class="selectBox">
									<select name="roomID" class="withSpn" onchange="changeSave(<?=$i?>)">
										<option value="0">על כל המתחם</option>
										<?php foreach($rooms as $room){ ?>
										<option value="<?=$room['roomID']?>" <?=$sitesSpecialsRooms[$room['roomID']]?"selected":""?> ><?=$room['roomName']?></option>
										<?php } ?>
									</select>
									<span>על איזה חדר</span>
								</div>
								<div class="selectBox extrasBox" style="display:<?=$deal['discount']==0 && $deal['specID']?"bloc":"none"?>">
									<select name="extras" class="withSpn" onchange="changeSave(<?=$i?>)">
										<option value="0">תוספת מתנה</option>
										<?php foreach($extras as $ex){ ?>
										<option value="<?=$ex['MainPageID']?>" <?=$sitesSpecialsExtras[$ex['MainPageID']]?"selected":""?> ><?=$ex['MainPageTitle']?></option>
										<?php } ?>
									</select>
									<span>תוספות</span>
								</div>
								<div class="selectBox date">
									<input type="text" name="dateTo" onchange="changeSave(<?=$i?>)" value="<?=date("d/m/y", strtotime($deal['dateTo']))?>" class="withDate datepicker" readonly>
									<input type="text" name="dateFrom" onchange="changeSave(<?=$i?>)" value="<?=date("d/m/y", strtotime($deal['dateFrom']))?>" class="withDate datepicker" readonly>
									<span>בתוקף לתאריכים</span>
									<span class="fromDate">מתאריך</span>
									<span  class="toDate">עד תאריך</span>
									<div class="chkBox">
										<input type="checkbox" onchange="changeSave(<?=$i?>)" id="dealType<?=$i?>" name="dealType" value="1" <?=$deal['dealType']==1?"checked":""?> >
										<label for="dealType<?=$i?>"></label>
									</div>
								</div>
							</div>
							<div class="selectBoxClose">
								<input type="text" id="" name="" value="<?=$dealTo[$deal['dealTo']]?>" class="oneBigClose dte" readonly>
							</div>
							<div class="selectBoxClose" onchange="changeSave(<?=$i?>)">
								<input type="text" id="" name="" value="<?=$deal['dealType']==1?date("d/m/y", strtotime($deal['dateFrom']))." - ".date("d/m/y", strtotime($deal['dateTo'])):"דיל קבוע"?>" class="oneBigClose dte" readonly>
							</div>
						</div>
						<div class="lft" id="lft_form<?=$i?>">
							<div class="plusBtn" onclick="openMoreDeal('form<?=$i?>')"></div>
							<input type="hidden" class="activehidden" name="active" value="<?=$deal['active']==1?"1":"0"?>">
							<input type="hidden" class="exclusivehidden" name="exclusive" value="<?=$deal['exclusive']==1?"1":"0"?>">
							<div data-bttn="active" class="ifActive<?=$deal['active']==1?"":" no"?>" onclick="saveChanges('form<?=$i?>', this)"><?=$deal['active']==1?"פעיל":"הפעל"?></div>
							<?php if(!$deal['exclusive']){ ?>
							<div data-bttn="exclusive" class="where<?=$deal['exclusive']==1?"":" no"?>" onclick="saveChanges('form<?=$i?>', this)">ראשי</div>
							<?php } ?>
							<div class="saveBtn" onclick="saveChanges('form<?=$i?>', this)">שמור</div>
							<div class="delBtn" onclick="deleteDeal('form<?=$i?>', this)">מחק</div>
						</div>
					</form>
				<?php $i++; } } ?>
				<?php if($sysDeals){

				foreach($sysDeals as $sys){ ?>
				<form class="goDeal" id="form<?=$i?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="siteID" value="<?=$site['siteID']?>">
					<input type="hidden" name="sysID" value="<?=$sys['specID']?>">
					<input type="hidden" name="dealID" value="<?=($siteDeals[$sys['specID']]['specID']?$siteDeals[$sys['specID']]['specID']:0)?>">
					<div class="allSelect">
						<div class="topTitle">
							<span><?=$sys['dealTitle']?></span>
							<span>לא כולל חגים וחופשות</span>
						</div>
						<div class="selectBox">
							<select name="discount" class="oneBig" onchange="changeSave(<?=$i?>)">
								<?php
								foreach(range(10,100,10) as $n){  echo '<option value="',$n,'" ',($siteDeals[$sys['specID']]['discount'] == $n ? "selected" :  ( !$siteDeals[$sys['specID']] && $sys['discount'] == $n ? 'selected' : '')),'>',$n,'% הנחה</option>'; }	?>
							</select>
						</div>
						<div class="selectBox">
							<select name="daysInWeek" class="oneSmall" onchange="changeSave(<?=$i?>)">
								<?php foreach($daysInWeek as $dType=>$dText){ ?>
									<option value="<?=$dType?>" <?=($dType==$siteDeals[$sys['specID']]['daysInWeek']?"selected":(!$siteDeals[$sys['specID']] && $dType==$sys['daysInWeek']?"selected":""))?>><?=$dText?></option>
								<?php } ?>
							</select>
						</div>
						<div class="selectBoxClose">
							<input type="text" id="" name="" value="<?=$dealTo[$sys['dealTo']]?>" class="oneBigClose dte" readonly>
						</div>
						<div class="selectBoxClose" onchange="changeSave(<?=$i?>)">
							<input type="text" id="" name="" value="<?=$siteDeals[$sys['specID']]['dealType']==1?date("d/m/y", strtotime($sys['dateFrom']))." - ".date("d/m/y", strtotime($sys['dateTo'])):"דיל קבוע"?>" class="oneBigClose dte" readonly>
						</div>
					</div>
					<div class="lft" id="lft_form<?=$i?>">
						<input type="hidden" class="activehidden" name="active" value="<?=$siteDeals[$sys['specID']]['active']==1?"1":"0"?>">
						<input type="hidden" class="exclusivehidden" name="exclusive" value="<?=$siteDeals[$sys['specID']]['exclusive']==1?"1":"0"?>">
						<div data-bttn="active" class="ifActive<?=$siteDeals[$sys['specID']]['active']==1?"":" no"?>" onclick="saveChanges('form<?=$i?>', this)"><?=$siteDeals[$sys['specID']]['active']==1?"פעיל":"הפעל"?></div>
						<?php if($siteDeals[$sys['specID']]['active']==1){ ?>
						<div data-bttn="exclusive" class="where<?=$siteDeals[$sys['specID']]['exclusive']==1?"":" no"?>" onclick="saveChanges('form<?=$i?>', this)">ראשי</div>
						<?php } ?>
						<div class="saveBtn" onclick="saveChanges('form<?=$i?>', this)">שמור</div>
					</div>
				</form>
				<?php $i++; }
				} ?>
			</div>
		</div>
		<?=getFixedButtons()?>
	</div>
    <script>

		$(".newDealBtn").click(function(){
			var forms = $(".goDeal").length;
			var siteID = $(this).data("siteid");

			$.post( "/cms/user/ajax_add_deal.php", { target: forms, siteID: siteID } , function( data ) {
				$(".dealsCount").after(data);
				$( ".datepicker" ).datepicker({
					dateFormat: 'dd/mm/y'
				});
			});

		});


		function changeSave(formID){
			$('#form'+formID+' .saveBtn').addClass("showsave");
		}


		$( ".datepicker" ).datepicker({
			dateFormat: 'dd/mm/y'
		});

		$(".discountSelect").change(function(){
			var frmID = $(this).parent().parent().parent().attr("id");
			if($(this).val()==0){
				$("#"+frmID+" .extrasBox").show();
				$("#"+frmID).addClass("open");
			} else {
				$("#"+frmID+" .extrasBox").hide();
			}
		});
		function openMoreDeal(form){
			$("#"+form).toggleClass("open");
		}
        function openCalendar(id){
            $(".loaderUser").show();
            indexTabs++;
            $("#theTabs").append('<div class="aTab" style="z-index:200" id="aTab-'+indexTabs+'"><div class="inTabBox"><iframe id="frame'+indexTabs+'" frameborder=0 src="/cms/calendar/calendar.php?frame='+indexTabs+'&sID='+id+'"></iframe><div class="aTabCloser" onclick="closeTab(\''+indexTabs+'\')">x</div></div></div>');
            window.setTimeout(function(){
                $(".loaderUser").hide();
            }, 300);
        }

		function saveChanges(formID,obj){

			if($(obj).data("bttn")=="active"){
				if($("#lft_"+formID+" .activehidden").val()==1){
					$("#lft_"+formID+" .activehidden").val('0');
					$("#lft_"+formID+" .ifActive").addClass("no");
					$("#lft_"+formID+" .ifActive").html("הפעל");
				} else {
					$("#lft_"+formID+" .activehidden").val(1);
					$("#lft_"+formID+" .ifActive").removeClass("no");
					$("#lft_"+formID+" .ifActive").html("פעיל");
				}
			}
			if($(obj).data("bttn")=="exclusive"){
				$(".lft .where").addClass("no");

				if($("#lft_"+formID+" .exclusivehidden").val()==1){
					$("#lft_"+formID+" .exclusivehidden").val('0');
					$("#lft_"+formID+" .where").addClass("no");
				} else {
					$("#lft_"+formID+" .exclusivehidden").val(1);
					$("#lft_"+formID+" .where").removeClass("no");
				}
			}



			$.post( "/cms/user/ajax_deal.php", $("#"+formID).serialize(), function( data ) {
				window.location.reload();
			});
		}

		function deleteDeal(formID, obj){
			if(confirm("את/ה בטוח/ה רוצה למחוק את הדיל?")){
				$.post( "/cms/user/ajax_delete_deal.php", $("#"+formID).serialize(), function( data ) {
					window.location.reload();
				});
			}
		}
    </script>

<?php
include_once "../bin/footer.php";
?>