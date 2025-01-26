<?php
require_once "auth.php";

if (!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){
    echo blockAccessMsg();
    return;
}

$multiCompound = !$_CURRENT_USER->single_site;

$result = new JsonResult;

try {
    switch($_POST['act']){
        case 'loadSub':
            $subID = intval($_POST['subID']);

            $order = $units = [];

            if ($subID){
                $order = udb::single_row("SELECT s.*, sett.TITLE AS `cityName` FROM `subscriptions` AS `s` LEFT JOIN `settlements` AS `sett` ON (s.clientCity = sett.settlementID) WHERE s.subID = " . $subID . " AND s.siteID IN (" . $_CURRENT_USER->sites(true) . ")");
                if (!$order)
                    throw new Exception("Cannot find subscription " . $subID);

                $siteID = $order['siteID'];

                $que = "SELECT st.*, t.treatmentName, o.orderID AS `realID`, o.orderIDBySite AS `orderID`, DATE(o.timeFrom) AS `treatDate`, TIME(o.timeFrom) AS `treatDate`, TIME(o.timeUntil) AS `treatEndDate`
                        FROM `subscriptionTreatments` AS `st` LEFT JOIN `treatments` AS `t` USING(`treatmentID`)
                            LEFT JOIN `orderPayments` ON (st.payID = orderPayments.lineID) LEFT JOIN `orders` AS `o` USING(`orderID`)
                        WHERE st.subID = " . $subID;
                $treatments = udb::single_list($que);

                $siteData = udb::single_row("SELECT IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal` FROM `sites` WHERE `sites`.`siteID` = " . $siteID);

                $paid = udb::single_value("SELECT SUM(`sum`) FROM `subscriptionPayments` WHERE `complete` = 1 AND `cancelled` = 0 AND `subType` NOT IN ('card_test', 'freeze_sum') AND `subID` = " . $subID) ?: 0;
            }
            else {
                $siteID = $_CURRENT_USER->active_site();
                $paid   = 0;

                $treatments = [];
            }

            $siteData = udb::single_row("SELECT sites.addressRequired, IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal` FROM `sites` WHERE `sites`.`siteID` = " . $siteID);

            UserUtilsNew::init($siteID);
            $allSources = UserUtilsNew::fullSourcesList();
            $sourcesArray   = [];
            $jssourcesArray = "";
            foreach($allSources as $k=>$source) {
                $color = $source['hexColor'];
                if(!$color)
                    $color = '#' . substr(md5(mt_rand()), 0, 6);
                $sourcesArray[$k] = [ "letterSign"=>$source['letterSign'],"color"=>$color ];
                $jssourcesArray .= "sourcesArray['".$k."'] = { letterSign: '".$source['letterSign']."' , color: '". $color ."'};" . PHP_EOL;

            }

            $treatTypes = udb::key_value("SELECT `treatmentID` AS `treatID`, `treatmentName` FROM `treatments` WHERE 1 ORDER BY `treatmentName`");
            $durations  = udb::key_column("SELECT `treatmentID`, `duratuion` FROM `treatmentsPricesSites` WHERE `siteID` = " . $siteID . " ORDER BY `duratuion`", 'treatmentID', 'duratuion');
            $prices     = udb::key_column("SELECT `treatmentID`, `price1` FROM `treatmentsPricesSites` WHERE `siteID` = " . $siteID . " ORDER BY `duratuion`", 'treatmentID', 'price1');

            ob_start();
?>
<div class="create_order order spaorder subscript-pop" id="create_subPop">
	<style>
		
	</style>
	
		<div class="arrow_cls" onclick="$(this).parent().toggleClass('cls')"></div>
		<div class="container">
			<div class="close" onclick="$('#create_subPop').add('#subsAddTreats').remove();if(spaOrderChanged)window.location.reload();"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
			<div class="title mainTitle">
				<?if($order['sourceID']){?>
					<div class="domain-icon <?=$order['sourceID']?>" title='<?=$order['sourceID']?>' style="background-color: <?=$sourcesArray[$order['sourceID']]['color']?>"><?=$sourcesArray[$order['sourceID']]['letterSign']?></div>
				<?}?>

                <?=($order['subNumber'] ? '<span>מנוי </span> ' . trim(chunk_split($order['subNumber'], 3, '-'), '-') : 'מנוי חדש')?>
			</div>

			<form class="form" id="subsForm" action="" method="post" autocomplete="off">
				<input type="hidden" name="action" value="saveSub" class="ignore" />
				<input type="hidden" name="subID" value="<?=$subID?>" id="subID" />
				<input type="hidden" name="specialfields" value="0">
				<script>
					$(document).on('change', '.dataInp.babies select', function() {
						$('input[name="specialfields"]').val(1)
					});
					$(document).on('change', 'textarea[name="comments_owner"]', function() {
						$('input[name="specialfields"]').val(1)
					});
					$(document).on('keyup', 'textarea[name="comments_owner"]', function() {
						$('input[name="specialfields"]').val(1)
					});
					$(document).on('keyup', 'textarea[name="comments_payment"]', function() {
						$('input[name="specialfields"]').val(1)
					});
					$(document).on('change', 'textarea[name="comments_payment"]', function() {
						$('input[name="specialfields"]').val(1)
					})
				</script>
				<div class="inputWrap half select orderOnly" id="wrapsources">
					<select <?=($siteData['sourceRequired'] && !$subID)? "class='required'" : ""?> onchange="if($(this).val()!='novalue'){$(this).removeClass('required')}else{$(this).addClass('required')};showCoupons($(this))" name="sourceID" id="sourceID" <?=($order['apiSource']=='spaplus' || $order['sourceID']=='online') ? "readonly style='pointer-events:none'":""?>>
						<?if($siteData['sourceRequired'] && !$subID){?>
						<option style='color:red' value="novalue">יש לבחור</option>
						<?}?>
						<option value="0">הזמנה רגילה</option>
						<?
                        UserUtilsNew::init($_CURRENT_USER->active_site());
						$cuponTypes = UserUtilsNew::$CouponsfullList;
						foreach($cuponTypes as $k=>$source) { ?>
						<option  class="test0"  value="<?=$k?>" <?=$order['sourceID']==$k?"selected":""?>><?=$source?></option>
						<?php }
                        foreach(UserUtilsNew::guestMember() as $k => $source){
                            ?>
                            <option   value="<?=$k?>" <?=$order['sourceID']==$k?"selected":""?>><?=$source?></option>
                        <?php }
                        foreach(UserUtilsNew::otherSources() as $k => $source){
                            ?>
                            <option  value="<?=$k?>" <?=$order['sourceID']==$k?"selected":""?>><?=$source?></option>
                            <?php
                        }
                        ?>
                        <option value="online" <?=$order['sourceID']=='online' ? "selected":""?>>הזמנת Online</option>
					</select>
					<label for="sourceID">מקור ההגעה</label>

					<script>
						
						function showCoupons(_src){
							var _srcval = _src.val();
							$('#couponTypes > li').removeClass('show');
							//debugger;
							if($('#couponTypes #source_'+_srcval+' ul').length){
								_src.addClass('hasCoupons');
								$('#couponTypes #source_'+_srcval).addClass('show');
							}else{
								_src.removeClass('hasCoupons');
								$('#wrapsources').removeClass('showCoupons');
							}
						}
						
						showCoupons($('#sourceID'));
						$('.showTheseCoupons').on('click',function(){
							$('#wrapsources').addClass('showCoupons');
						});
						$('#wrapsources .close').on('click',function(){
							$('#wrapsources').removeClass('showCoupons');
						});
					</script>
					<div class="close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
					<ul id="couponTypes">
<?php
					$siteCustomPayments = UserUtilsNew::getCustomPayTypes($siteID);// return under siteID key					
					$sql = "select * from sitePayTypes where siteID in (".$siteID.")";
					$sitePayments = udb::key_row($sql,'paytypekey');

					foreach (UserUtilsNew::$dbCuponTypes as $k=>$item) {
						$sql = "select * from payTypes where active=1 and parent=".$item['id'];
						$theCupons = udb::full_list($sql);
						$coupons_cnt=0;
						?>
						<li id="source_<?=$item['key']?>" class="<?=($theCupons || ($siteCustomPayments && $siteCustomPayments[$item['id']])) ? 'hasSub' : '';?>">
						
						<label><?//=$item['fullname']?></label>
						<?
						$sql = "select * from payTypes where active=1 and parent=".$item['id'];
						$theCupons = udb::full_list($sql);
						$theCupons = [];
						if(($siteCustomPayments && $siteCustomPayments[$item['id']]) || $theCupons) {
							
						}
						if($theCupons) {
							foreach ($theCupons as $theCupon) {
								if($sitePayments[$theCupon['id']]){
								if(!$coupons_cnt) echo '<ul>';
								$coupons_cnt++;
								?>
								<li>
									<div class="cpn_name"><?=$theCupon['fullname']?></div>
									<div class="cpn_price">נרכש ב : <b><?=$theCupon['couponPayed']? "₪".$theCupon['couponPayed'] : "----"?></b></div>
									<div class="cpn_price">שווי : <b>₪<?=$theCupon['cuponPrice']?></b></div>
									<?if($theCupon['cpn_remarks']){?><div class="cpn_remarks"><?=$theCupon['cpn_remarks']?></div><?}?>
								</li>
								<?
								}
							}
						}
						if($siteCustomPayments && $siteCustomPayments[$item['id']]) {
							foreach ($siteCustomPayments[$item['id']] as $customCupons) {
								if($sitePayments[$customCupons['id']]){
								if(!$coupons_cnt) echo '<ul>';
								$coupons_cnt++;
								?>
								<li>
									<div class="cpn_name"><?=$customCupons['fullname']?></div>
									<div class="cpn_price">נרכש ב : <b><?=$customCupons['couponPayed']? "₪".$customCupons['couponPayed'] : "----"?></b></div>
									<div class="cpn_price">שווי : <b>₪<?=$customCupons['cuponPrice']?></b></div>
									<?if($customCupons['cpn_remarks']){?><div class="cpn_remarks"><?=$customCupons['cpn_remarks']?></div><?}?>
								</li>								
								<?
								}
							}
						}
						if($coupons_cnt) {
							echo '</ul><div class="showTheseCoupons">לחצו לצפיה ב<b>'.($coupons_cnt>1? '-'.$coupons_cnt.' קופונים' : 'קופון אחד').'</b></div>';
						}
						?>
						</li>
					<?
					}
					?>
					</ul>
				</div>
<?php
    if($multiCompound){
        $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
                <div class="inputWrap half select orderOnly" id="wrapsources">
                    <select name="subSite" id="subSite" <?=($order['siteID'] ? "readonly disabled" : "")?>>
<?php
        foreach($sname as $id => $name)
            echo '<option value="' , $id , '" ' , ($id == $siteID ? 'selected' : '') , '>' , $name , '</option>';
?>
                    </select>
                    <label for="subSite">מתחם</label>
                </div>
<?php
    }
?>
                <div class="inputLblWrap" style="width:100%;margin:0;text-align:right">
                    <div class="signOpt inOrder" style="margin:10px 7px;">
                        <div class="switchTtl">מנוי פעיל </div>
                        <label class="switch" style="float:left" for="subActive">
                            <input type="checkbox" name="subActive" value="1" id="subActive" <?=((!$subID || $order['active']) ? "checked" : "")?>  class="" />
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

				<?php
					/*if($order['guid'] && $orderType == 'order'){
				?>
				<div class="inputLblWrap " style="width:100%;margin:0;text-align:right">
					<?php if(!$order['signature']) { ?>
                    <div class="pdfbtn" data-print="pre_print.php?oid=<?=$subID?>" data-p="" style="margin:0">הדפס הסכם לא חתום</div>
					<div style="float:left;">
						<label class="switch" style="float:left;" for="hidePrices" >
							<input type="checkbox" name="hidePrices" onchange="hideSpaPrices(<?=$subID?>,$(this).is(':checked'))" id="hidePrices" <?=$order['hidePrices']?"checked":""?>  class="">
							<span class="slider round"></span>
						</label>
						<span style="width:50px;font-size:12px;text-align:left;float:left;padding:4px">הסתר מחירים</span>
					</div>
					<div class="signOpt inOrder">
						<div class="switchTtl">אשר הזמנה <span style="font-weight: normal;
    font-size: 12px;display:inline-block;line-height:1;vertical-align:middle;">(למרות שלא נחתמה)</span></div>
						<label class="switch" style="float:left" for="approvedx">
						  <input type="checkbox" name="adminApproved" value="1" id="approvedx" <?=$order['adminApproved']?"checked":""?>  class="">
						  <span class="slider round"></span>
						</label>
					</div>
					<?php } ?>
					<div class="signOpt inOrder">
						<div class="switchTtl">חתומה / לא חתומה</div>
						<label class="switch" style="float:left">
						  <input type="checkbox" name="approved" value="1" class="ignore" <?=$order['signature']?"checked":""?> onclick="return false;">
						  <span class="slider round"></span>
						</label>
					</div>
					<?php if($order['signature'] && $order['approved']) { ?>
					<div style="background:white;border:1px #ccc solid;margin-bottom:10px;padding:10px;text-align:center;border-radius: 0 0 10px 10px;margin-top:-20px;z-index:0">

					<div class="pdfbtn" data-print="https://bizonline.co.il<?=$order['file']?>" data-p="">הדפס הסכם</div>
						<img class='sigImg' src='/<?=$order['signature']?>' style="    max-width: 60%;height: auto!important;">
					</div>
					<?php }?>


					<div class="signOpt inOrder" style="position:relative;">
						<?php if($order['order_mail_bymail'] || $order['order_mail_bysms']) { ?><span style="position: absolute;top: 0;font-weight:600;right: 10px;font-size: 14px;color: #e73219;">נשלחה תזכורת</span><?php } ?>
					<?php
					$subject = "טופס לאישור הזמנה ב". $siteData['siteName'] ." בתאריך".date('d.m.y', strtotime($order['abs_timeFrom']));
					$subject_sign = "טופס לאישור הזמנה ב". $siteData['siteName'] ." בתאריך".date('d.m.y', strtotime($order['abs_timeFrom']));
					$body = $order['customerName'].' שלום, לצפיה בפרטי ההזמנה שלך ב' . $siteData['siteName']." " . $date_n_day.' יש ללחוץ על הקישור הבא '.$link;
					$body_sign = $order['customerName'].' שלום, על מנת לאשר את הזמנתך ב' . $siteData['siteName']." " . $date_n_day.' יש ללחוץ על הקישור הבא '.$link_sign;
					$phoneClean = str_replace("-","",$order['customerPhone']);



						?>
							<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:1.4"><?=$order['approved']?"יצירת קשר":"שליחה ללקוח<span class='sign'><input type=\"checkbox\" style=\"display:inline-block;vertical-align:middle;margin-right:10px;\" id=\"tosign\" name=\"tosign\"> <label for=\"tosign\">לחתימה</label></span>"?></div>
							<div style="float:left;margin:-5px -5px -5px 0">
							<?if($order['customerPhone']) { ?>
								<a href="<?whatsappBuild($order['customerPhone'],$body);?>" class="walink" data-href="<?whatsappBuild($order['customerPhone'],$body);?>" data-sign-href="<?whatsappBuild($order['customerPhone'],$body_sign);?>" target="_blank"><span class="icon whatsapp" data-phone="<?=$phoneClean?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span></a>
								<a href="sms:<?=$phoneClean?>?&body=<?=$body?>"  data-phone="<?=$phoneClean?>" data-msg="<?=$body?>" data-sid="<?=$siteID?>"  target="_blank" class="a_sms"><span class="icon sms" data-sms="<?=$phoneClean?>">
									<img src="/user/assets/img/icon_sms.png" alt="sms">
								</span></a>
							<?php } ?>
							<?php if($order['customerEmail']) { ?>
								<a href="mailto:<?=$order['customerEmail']?>?subject=<?=$subject?>&body=<?=$body?>" target="_blank"><span class="icon mail" data-mail="<?=$order['customerEmail']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" width="32" height="27"><style>.message-ic{fill:#fff}</style><path class="message-ic" d="M29.9 25L24.1 19.4 23.5 18.8 23 18.3 22.6 17.8 22.1 17.4 24.9 15.5 29.8 12 30 11.9 30 14 30 17.2 30 25 29.9 25ZM22.2 6L5 10.9 5.7 13.3 2.1 10.7 2.8 7.8C4.1 7 12.5 2.1 15.9 2 19.3 2.1 27.7 7 29 7.8L29.9 10.7 24.7 14.5 22.2 6ZM6.2 14.8L9.7 17.4 9 18.1 8.5 18.6 8.1 19.1 7.5 19.6 2.1 25 2 25 2 13.1 2 12 2 11.9 2.7 12.4 6.2 14.8ZM9.5 19L10 18.5 10.4 18.1 10.8 17.7 11.2 17.2 11.3 17.2C11.4 17.1 11.6 16.9 11.8 16.8 12.9 15.8 14.4 15.3 15.9 15.3 17.4 15.3 18.9 15.8 20 16.8 20.2 16.9 20.3 17 20.5 17.2L20.6 17.3 21.1 17.7 21.2 17.9 21.7 18.3 22.2 18.8 23 19.7 28.4 25 3.5 25 7.8 20.7 9.5 19Z"></path></svg></span></a>
							<?php } ?>
								<span class="icon plusSend" onclick='$("#sendPopMsg").val($("#tosign").is(":checked")?$(this).data("msg-sign"):$(this).data("msg"));$("#sendPopSubject").val($(this).data("subject"));$(".sendPop").fadeIn("fast");$("#SendPopTitle").text($(this).data("title"));' data-title="<?=$order['approved']?"יצירת קשר":"שליחה לחתימה"?>" data-msg="<?=$body?>" data-subject="<?=$subject?>" data-msg-sign="<?=$body_sign?>" data-subject-sign="<?=$subject_sign?>"></span>
							</div>
					</div>

<div class="signOpt inOrder">
	<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:34px">שליחת הזמנה
		<span style="font-size: 14px; line-height: 1; width: 115px; display: inline-block; padding: 3px 10px; font-weight: normal;">
<?php
    switch($order['mail_sent']){
        case 1: echo '<b style="color:green">נשלחו פרטי הזמנה במייל</b>'; break;
        case 2: echo '<b style="color:green">נשלחו פרטי הזמנה ב-sms</b>'; break;
        case 3: echo '<b style="color:green">נשלחו פרטי הזמנה במייל ו-sms</b>'; break;
        default: echo 'תשלח <strong>ללקוח בסיום שמירת ההזמנה</strong>';
    }
?>
        </span>
	</div>
<?php
    if($order['mail_sent'] <= 0) {
?>
		<label class="switch" style="float:left">
		  <input type="checkbox" name="sendOrderMail" value="1" class="ignore" <?=(((!$subID || $order['mail_sent'] < 0) && $blockAutoSend!=1) ? 'checked="checked"' : '')?> />
		  <span class="slider round"></span>
		</label>
<?php
    }
?>
</div>
<?if($order['sourceID']!='spaplus'){?>

                    <div class="signOpt inOrder">
                        <div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:34px">הצהרת בריאות
                            <span style="font-size: 14px; line-height: 1; width: 115px; display: inline-block; padding: 3px 10px; font-weight: normal;">
<?php
	if ($order['healthMailSent'])
	    echo '<b style="color:green">בקשה למילוי נשלחה ללקוחות</b>';
	else {
		if(time() < $lastTreat_x) {
	    	echo 'תשלח למילוי <strong>בתחילת יום ההגעה</strong>';
		}
	}
?>
                            </span>
                        </div>
                        <?php
                        if (!$order['healthMailSent']){
                            ?>
							<?php if(time() < $lastTreat_x) { ?>
                            <label class="switch" style="float:left">
                                <input type="checkbox" name="healthMailAccept" value="1" class="ignore" <?=($order['healthMailAccept'] || !$subID)?"checked=''":""?>>
                                <span class="slider round"></span>
                            </label>
							<?php } ?>
                            <?php
                        }
                        ?>
                    </div>
					<?php
					if($order && $orderType == 'order' && $order["orderID"]){
                    $que = "SELECT reviews.*, papa.orderID, files.src AS document 	
                            FROM `reviews` INNER JOIN `orders` USING (`orderID`) INNER JOIN `orders` AS `papa` ON (papa.orderID = orders.parentOrder) 	
                                LEFT JOIN files ON (files.ref = reviews.reviewID AND files.table = 'reviews') 	
                            WHERE papa.orderID = " . $order["orderID"];
					$reviews = udb::single_list($que);
					if(!$reviews) {
						$reviewBody = urlencode("שלום ".$order['customerName'].". לאחר שהותך  ב".$siteData['siteName'].", נשמח לקבל את חוות דעתך. למילוי חוות הדעת:  https://bizonline.co.il/review.php?guid=".$order["guid"]);
						$reviewSubjsct = "מילוי חוות דעת ".$order["siteName"];
					?>
					<div class="signOpt inOrder">
						<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:34px">

						חוות דעת
							<span style="font-size: 14px; line-height: 1; width: 104px; display: inline-block; padding: 3px 10px; font-weight: normal;">
								<?if(strtotime(date("Y-m-d h:i:s")) <= strtotime($order['showTimeFrom'][0] == 0 ? $lastTreat : $order['showTimeFrom'])){?>
									<?php if($enable_review['sendReviews']) { ?>
										תשלח למילוי ביום עזיבה
									<?php } else { ?>
										בסיום הטיפול, ניתן יהיה לשלוח בקשה למילוי
									<?php } ?>
								<?}else if($order['SentReview'] || $order['review_mail_sent']){?><b style="color:green">נשלחה בקשה למילוי</b><?}?>
							</span>
						</div>
						<?if(strtotime(date("Y-m-d h:i:s")) <= strtotime($order['showTimeFrom'][0] == 0 ? $lastTreat : $order['showTimeFrom'])){?>

						<?}else{?>
						<div style="float:left;margin:-5px -5px -5px 0">
						<?php if($order['customerPhone']){
							$phoneClean = str_replace("-","",$order['customerPhone']);
							?>
							<a href="<?whatsappBuild($order['customerPhone'],$reviewBody)?>" target="_blank"><span class="icon whatsapp" data-phone="<?=$phoneClean?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span></a>

							<a href="sms:<?=$phoneClean?>?&body=<?=$reviewBody?>" target="_blank"  data-phone="<?=$phoneClean?>" data-msg="<?=$reviewBody?>" data-sid="<?=$siteID?>"  class="a_sms"><span class="icon sms" data-sms="<?=$phoneClean?>">
								<img src="/user/assets/img/icon_sms.png" alt="sms">
							</span></a>
						<?php } ?>
						<?php if($order['customerEmail']){?>
                            <a href="#" onclick="reviewInvite(<?=$subID?>)"><span class="icon mail" data-mail="<?=$order['customerEmail']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" width="32" height="27"><style>.message-ic{fill:#fff}</style><path class="message-ic" d="M29.9 25L24.1 19.4 23.5 18.8 23 18.3 22.6 17.8 22.1 17.4 24.9 15.5 29.8 12 30 11.9 30 14 30 17.2 30 25 29.9 25ZM22.2 6L5 10.9 5.7 13.3 2.1 10.7 2.8 7.8C4.1 7 12.5 2.1 15.9 2 19.3 2.1 27.7 7 29 7.8L29.9 10.7 24.7 14.5 22.2 6ZM6.2 14.8L9.7 17.4 9 18.1 8.5 18.6 8.1 19.1 7.5 19.6 2.1 25 2 25 2 13.1 2 12 2 11.9 2.7 12.4 6.2 14.8ZM9.5 19L10 18.5 10.4 18.1 10.8 17.7 11.2 17.2 11.3 17.2C11.4 17.1 11.6 16.9 11.8 16.8 12.9 15.8 14.4 15.3 15.9 15.3 17.4 15.3 18.9 15.8 20 16.8 20.2 16.9 20.3 17 20.5 17.2L20.6 17.3 21.1 17.7 21.2 17.9 21.7 18.3 22.2 18.8 23 19.7 28.4 25 3.5 25 7.8 20.7 9.5 19Z"></path></svg></span></a>

						<?php
						}?>
						<!-- span class="icon plusSend" onclick='$("#sendPopMsg").val($(this).data("msg"));$("#sendPopSubject").val($(this).data("subject"));$(".sendPop").fadeIn("fast");$("#SendPopTitle").text($(this).data("title"));' data-msg="<?=$reviewBody?>" data-title="שליחת חוות דעת" data-subject="<?=$reviewSubject?>"></span -->
						</div>
						<?}?>
					</div>

<?php
					} else {
					    foreach($reviews as $review){
?>
						<div class="review">
							<div class="userData">
								<div class="topData"><?=date("d.m.y h:i",strtotime($review['created']));?> <?=(count($_CURRENT_USER->sites()) > 1)? $sname[$review["siteID"]] : ""?></div>
								<b><?=$review['name']?></b> <span><b><?=$review['avgScore']?></b>/5</span>
							</div>
							<div class="reviewText">
								<?if($review["document"]){?><div class="showOrder" onclick="openDoc('<?=$review["document"]?>')">הצג אסמכתא</div><?}?>
								<div class="revtitle"><?=$review['title']?></div>
								<div class="text"><?=nl2br($review['text'])?></div>
							</div>
						</div>
<?php
                        }
                    }
                }
}
?>

					<style>

					</style>
					<div class="signOpt inOrder">	
						<?php
							$sms_sent = udb::full_list("SELECT * FROM orders_sms WHERE orderID=".$subID);
						?>
						<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:<?=$sms_sent?"1":"34px"?>">שליחת הודעת SMS
						<?php if($sms_sent) { ?>
							<div onclick="$('.sent-sms-list[data-orderid=\'<?=$subID?>\']').fadeToggle('fast')" style="text-decoration:underline;font-weight:normal;font-size:14px;cursor:pointer;">
								<?=count($sms_sent) == 1?"נשלחה הודעה אחת- לחצו לצפייה":"נשלחו ".count($sms_sent)." הודעות- לחצו לצפייה"?>
							</div>
						<?php } ?>
						</div>
						<div onclick="$('.sms-pop[data-orderid=\'<?=$subID?>\']').fadeIn('fast')" style="font-size: 16px;font-weight: bold;color: #FFF;display: inline-block;cursor:pointer;line-height: 34px;background: #0dabb6;padding: 0 10px;border-radius: 6px;">שלח הודעה</div>
					</div>
					<?php if($sms_sent) { ?>
						<div class="sent-sms-list"  data-orderid="<?=$subID?>" style="display:none">
							<?php foreach($sms_sent as $sms) { ?>
								<div class="sent-sms">
									<div class="date">תאריך שליחה: <strong><?=$sms['sendTime']?></strong></div>
									<div class="con">
										<?=$sms['sms_con']?>
									</div>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
					<div class="popup sms-pop" data-orderid="<?=$subID?>" style="display:none;">
						<div class="pop_cont">
							<div class="close" onclick="$('.sms-pop').fadeOut('fast')">&times;</div>
							<div class="title">שליחת הודעת SMS</div>						
							<textarea data-orderid="<?=$subID?>" name="smscon" maxlength="80"></textarea>
							<div class="limit">הגבלת תווים <span><span class="len">0</span>/<span class="of">80</span></span></div>
							<div class="send" data-orderid="<?=$subID?>">שליחה</div>
						</div>
					</div>

					<script>
                        $(function(){
                            //$(document).off('click.ajaxFrom').on('change keyup', 'textarea[name="smscon"]', function() {
                            $('textarea[name="smscon"]').on('change keyup', function() {
                                let _len = $(this).val().length;
                                $(this).parent().find('.limit .len').html(_len);
                            });

                            //$(document).on('click', '.sms-pop .send', function() {
                            $('.sms-pop .send').on('click', function() {
                                let _oid = $(this).attr('data-orderid');
                                let _con = $(this).closest('.sms-pop').find('textarea').val();

                                $.post('ajax_spaSMS.php', {orderID: _oid, sms_con: _con}, function(res) {
                                    if(res.error)
                                        Swal.fire({icon:'error', text:res.error});
                                    else Swal.fire({icon:'success', text:res.msg});
                                })
                            })
                        });
					</script>
				</div>
				<?}else{?>
					<input type="hidden"  name="status" value="1" >
					<input type="hidden"  name="allowReview" value="<?=$siteData['sendReviews']?>" >
				<?} */?>
				<div></div>

				<div class="inputWrap half" style="z-index:20">
					<input type="text" name="name" id="name" value="<?=$order['clientName']?>" class="ac-inp" />
					<label for="name">שם המזמין</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
				</div>
				<div class="inputWrap half tZehoot orderOnly" style="z-index:15">
					<input type="text" name="tZehoot" id="tZehoot" inputmode="numeric" value="<?=$order['clientTZ']?>" class="ac-inp" />
					<label for="tZehoot">תעודת זהות</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
				</div>
				<div class="inputWrap half" style="z-index:10">
					<input type="text" name="phone" id="phone" value="<?=$order['clientPhone']?>" class="ac-inp" />
					<label for="phone">טלפון</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
				</div>

				<div class="inputWrap half orderOnly">
					<input type="text" name="phone2" id="phone2" value="<?=$order['clientPhone2']?>">
					<label for="phone2">טלפון נוסף</label>
				</div>

				<div class="inputWrap half email orderOnly" style="z-index:5">
					<input type="text" name="email" id="email" value="<?=$order['clientEmail']?>" class="ac-inp" />
					<label for="email">אימייל</label>
                    <div class="autoBox"><div class="autoComplete"></div></div>
				</div>

				<div id="orgAdress" style="<?=$siteData['addressRequired']? "" : "display:none"?>">
					<div class="inputWrap half orderOnly">
						<input type="hidden" name="settlementID" value="<?=$order['clientCity']?>"  class="hide_next <?=($siteData['addressRequired']==2 && !$subID)? "required" : ""?> <?=$order['clientCity']? "valid" : ""?>">
						<div class="settlementName"><?=$order['cityName']?></div>
						<input  type="text"  class="ac-inp2" name="clientCity" id="clientCity" value="<?=$order['cityName']?>">
						<label for="clientCity">עיר</label>
						<div class="autoBox"><div class="autoComplete"></div></div>
					</div>
					<div class="inputWrap half orderOnly">
						<input type="text" name="clientAddress" id="clientAddress" class="<?=($siteData['addressRequired']==2 && !$subID)? "required" : ""?>" value="<?=$order['clientAddress']?>">
						<label for="clientAddress">רחוב ומספר</label>
					</div>
				</div>

				<div id="sub_treatments_wrap">
					<div id="sub_treatments">
<?php
    if ($treatments){
        foreach($treatments as $treat){
            $tdata = json_decode($treat['data'], true);
            $onclick = $treat['orderID'] ? 'openSpaFrom({orderID:' . $treat['realID'] . '})' : '';
?>
						<div class="spaorder" id="spaorder<?=$treat['stID']?>" data-id="<?=$treat['stID']?>" data-parent="<?=$treat['subID']?>" data-price="<?=$treat['price']?>" onclick="<?=$onclick?>">
                            <!-- div class="delete" onclick="deleteTreatment(this)"></div -->
                            <div class="spasect">
                                <b><?=$tdata['name']?></b>
								<span><?=$treat['treatmentName']?> <span style="font-size:12px;vertical-align:middle;"><?=$treat['duration']?> דק'</span></span>
                            </div>
                            <div class="spasect">
                                <!-- span><?=($treat['treatDate'] ? db2date($treat['treatDate'], '/') : '')?></span>
                                <span><?=($treat['treatDate'] ? substr($treat['treatEndTime'], 0, 5) . ' - ' . substr($treat['treatTime'], 0, 5) : '')?></span -->
                            </div>
                            <div class="spasect">
                                <span class="null"><?=($treat['orderID'] ? 'הזמנה #' . $treat['orderID'] : '')?></span>
                            </div>
                            <div class="spasect">₪<?=$treat['price']?></div>
                            <?=($treat['orderID'] ? '' : '<div class="duplicate">מימוש<br>טיפול</div>')?>
                        </div>
<?php
        }
    }
?>
                    </div>
					<div id="add_treat" class="add_button">הוסף טיפולים</div>
				</div>

<?php

	/*if ($extras){
        $ct = count($treatments);

        foreach($extras as $sid => $eTypes){
            if ($ct && $eTypes['package']){
                $pin = min(3, max(1, $ct));
?>
				<div class="treatments_patients_addings addings siteID<?=$sid?>" <?=($multiCompound && !$order['siteID'])? 'style="display:none"' : ""?>>
					<div class="title">תוספות למקבלי טיפולים</div>
<?php
                foreach($eTypes['package'] as $extra){
                    $price = $orderExtras['extras'][$extra['extraID']]['price'] ?? intval($extra['price' . $pin] ?: $extra['price' . ($pin - 1)]  ?: $extra['price1']);
                    $cnt   = $orderExtras['extras'][$extra['extraID']]['count'] ?? 1;
?>
					<div class="adding" <?=($extra['included'])? "style='opacity:0.6'" :"" ?>>
						<input type="checkbox" <?=$extra['included']? "onclick='return false;'" : ""?> class="extra" name="extra[]" id="extra<?=$extra['extraID']?>" <?=((isset($orderExtras['extras'][$extra['extraID']]) || ($extra['included'])) ? 'checked' : '')?> value="<?=$extra['extraID']?>" />

						<?php
						if($extra['extraID']==1686){
							$printdate = $units?$startDate:date('d/m/Y', strtotime($treatments[0]['timeFrom']));
							if(!$printdate)
								$printdate = date('d/m/Y');
							if($units) {
								$print_roomName = '';
								foreach($rooms as $room) {
									if($units[$room['unitID']])$print_roomName = $room['unitName'];
									else continue;
								}
							}

?>
							<div class="print-icon" data-place="<?=$order['siteName']?>" data-date="<?=$printdate?>" data-time="<?=$units?date('H:i', strtotime($startDate)):date('H:i', strtotime($treatments[0]['timeFrom']))?>" data-person="<?=$order['customerName']?>" data-room="<?=$print_roomName?>"
							data-quantity="<?=$cnt?>" data-name="<?=htmlspecialchars($extra['extraName'])?>" data-title="<?=htmlspecialchars($extra['extraName'])?>"
							data-desc="<?=htmlspecialchars($extra['description'])?>"></div>
<?php
						}
?>

						<label for="extra<?=$extra['extraID']?>"><div><?=$extra['extraName']?><span><?=nl2br($extra['description'])?></span></div>
						</label>
						<div class="l">
							<span class="unit_price">₪<?=$price?></span>
                            <input type="number"  <?=$extra['included']? "readonly" : ""?> class="count" inputmode="numeric" name="ecount[<?=$extra['extraID']?>]" value="<?=$cnt?>" min="1" title="" data-price="<?=$price?>" />
							<span class="price">₪<?=($price * $cnt)?></span>
						</div>
					</div>
<?php
                }
?>
                </div>
<?php
            }


            if ($eTypes['rooms']){
?>
				<div class="treatments_patients_addings addings siteID<?=$sid?>" <?=($multiCompound && !$order['siteID'])? 'style="display:none"' : ""?>>
					<div class="title">תוספי חדרים</div>
<?php
                foreach($eTypes['rooms'] as $extra){
                    $oe = $orderExtras['extras'][$extra['extraID']] ?? [];

                    $options = [];
                    if ($extra['price1']){
                        $options[] = '<option data-price="' . $extra['price1'] . '" value="1">שהיה לטיפול ' . (ceil($extra['countMin']) ? round($extra['countMin'], 1) . ' שעות' : '') . '</option>';
                        if ($extra['price2'] && round($extra['countMax'], 1) > round($extra['countMin'], 1))
                            for($l = $extra['countMin'] + 1; $l <= $extra['countMax']; ++$l)
                                $options[] = '<option data-price="' . ($extra['price1'] + $extra['price2'] * ($l - $extra['countMin'])) . '" value="' . $l . '" ' . ($l == (($oe['baseHours'] ?? 0) + ($oe['extraHours'] ?? 0)) ? 'selected' : '') . '>שהיה לטיפול ' . $l . ' שעות</option>';
                    }
                    if ($extra['price3'])
                        $options[] = '<option data-price="' . $extra['price3'] . '" value="99" ' . ($oe['forNight'] ? 'selected' : '') . '>לינה</option>';

                    // no-price protection - if there aren't ANY options for this room - set it as "zero-priced"
                    if (!count($options))
                        $options[] = '<option data-price="0" value="1">שהיה לטיפול</option>';
?>
					<div class="adding">
						<input type="checkbox" class="extra" name="extra[]" id="extra<?=$extra['extraID']?>" <?=(($oe || (!$subID && $extra['included'])) ? 'checked' : '')?> value="<?=$extra['extraID']?>" />
						<label for="extra<?=$extra['extraID']?>"><?=$extra['extraName']?><div><?=nl2br($extra['description'])?></div></label>
						<div class="l">
                            <select class="count" name="ecount[<?=$extra['extraID']?>]" title="" <?//=(count($options) < 2 ? 'style="display:none"' : '')?>><?=implode('', $options)?></select>
							<span class="price">₪<?=($oe['price'] ?? $extra['price1'] ?: $extra['price3'])?></span>
						</div>
					</div>
<?
                }
?>
				</div>
<?php
            }


            foreach(['general' => 'תוספים כלליים', 'company' => 'תוספים מלווים','product' => 'מוצרים נלווים'] as $et => $en){
                if ($eTypes[$et]){
?>
				<div class="treatments_patients_addings addings siteID<?=$sid?>" <?=($multiCompound && !$order['siteID'])? 'style="display:none"' : ""?>>
					<div class="title"><?=$en?></div>
<?php
                    foreach($eTypes[$et] as $extra){
                        $price = $orderExtras['extras'][$extra['extraID']]['price'] ?? $extra['price1'];
                        $cnt   = $orderExtras['extras'][$extra['extraID']]['count'] ?? 1;
?>
					<div class="adding">
						<input type="checkbox" class="extra" name="extra[]" id="extra<?=$extra['extraID']?>" <?=((isset($orderExtras['extras'][$extra['extraID']]) || (!$subID && $extra['included'])) ? 'checked' : '')?> value="<?=$extra['extraID']?>" />
						<label for="extra<?=$extra['extraID']?>"><?=$extra['extraName']?><div><?=nl2br($extra['description'])?></div></label>
						<div class="l">
							<span class="unit_price">₪<?=$price?></span>
                            <input type="number" class="count" inputmode="numeric" name="ecount[<?=$extra['extraID']?>]" value="<?=$cnt?>" min="1" title="" data-price="<?=$price?>" />
							<span class="price">₪<?=($price * $cnt)?></span>
						</div>
					</div>
<?php
                    }
?>
				</div>
<?php
                }
            }
        }
    }*/
?>

				<!-- div class="percentage-disc">
					<div class="title">אחוזי הנחה %</div>
                    <div class="inputWrap">
                        <input max="100" min="0" onchange="$('.disc span').html(Math.round(parseInt($(this).val())/100*$('#price_total').val()))"  onkeydown="$('.disc span').html(Math.round(parseInt($(this).val())/100*$('#price_total').val()))"   onkeyup="$('.disc span').html(Math.round(parseInt($(this).val())/100*$('#price_total').val()))" type="number" name="percentage_discount" id="percentage_discount" value="" />
                    </div>
					<div class="disc" style="display:inline-block;width:60px;text-align:center;">₪<span>0</span></div>
					<div class="applybutt" onclick="$('#price_discount').val(parseInt($('.disc span').html()));$('#price_discount').blur();">הוסף הנחה</div>
				</div -->

				<div style="clear:both;margin-top:10px;padding-top:10px;border-top:1px #ccc solid">
                    <div class="inputWrap half orderOnly">
                        <input type="number" name="price_total" id="subs_price_total" value="<?=$order['price']?>" readonly />
                        <label for="subs_price_total">מחיר מחירון</label>
                    </div>
                    <div class="inputWrap half orderOnly">
                        <input type="number" name="price_discount" id="subs_price_discount" value="<?=$order['discount']?>" />
                        <label for="subs_price_discount">הנחה</label>
                    </div>

                    <div class="inputWrap half orderOnly" style="float:inline-start">
                        <input type="number" name="price_to_pay" id="subs_price_to_pay" value="<?=round($order['price'] - $order['discount'], 1)?>" />
                        <label for="subs_price_to_pay">סכום לתשלום</label>
                    </div>
                    <div style="clear:both"></div>

                    <div class="inputWrap half orderOnly">
                        <input type="number" name="prePay" id="subs_prePay" value="<?=$paid?>" readonly />
                        <label for="subs_prePay">סה"כ שולם</label>
                    </div>
                    <div class="inputWrap half orderOnly">
                        <input type="text" name="leftPay" id="subs_leftPay" value="<?=($order['price'] - $order['discount'] - $paid)?>" readonly />
                        <label for="subs_leftPay">נותר לתשלום</label>
                    </div>

<?php
/*    if ($order['onlineData']){
        $onlineData = json_decode($order['onlineData'], true);
        $oText = [];
        $payTimes = ['no-payment' => 'אין אמצעי תשלום', 'now' => 'תשלום עכשיו', 'on_arrive' => 'תשלום ביום ההגעה'];

        if ($onlineData['source'])
            $oText[] = 'מקור: ' . ucfirst($onlineData['source']);
        if ($onlineData['packCode'])
            $oText[] = 'קוד חבילה: ' . $onlineData['packCode'];
        if ($onlineData['payTime'])
            $oText[] = 'אופן תשלום: ' . ($payTimes[$onlineData['payTime']] ?? $onlineData['payTime']);
        if ($onlineData['bookID'])
            $oText[] = "מס' הזמנה חיצוני: " . $onlineData['bookID'];
        if ($onlineData['received'])
            $oText[] = "התקבלה: " . substr($onlineData['received'], 11, 5) . ' ' . db2date(substr($onlineData['received'], 0, 10), '.');

        if ($onlineData['payed'])
            $oText[] = (($onlineData['payTime'] == 'now') ? "שולם: " : "כרטיס לערבון: ") . substr($onlineData['payed'], 11, 5) . ' ' . db2date(substr($onlineData['payed'], 0, 10), '.');
        elseif ($onlineData['declined'])
            $oText[] = "בוטלה: " . substr($onlineData['declined'], 11, 5) . ' ' . db2date(substr($onlineData['declined'], 0, 10), '.');
        elseif ($onlineData['timeout'])
            $oText[] = "אזלה: " . substr($onlineData['timeout'], 11, 5) . ' ' . db2date(substr($onlineData['timeout'], 0, 10), '.');
?>
                    <div class="inputWrap textarea" style="background:rgb(40 218 231 / 20%)">
                        <textarea id="online_data" readonly><?=implode(PHP_EOL, $oText)?></textarea>
                        <label for="online_data">פרטי הזמנת אוליין</label>
                    </div>
<?php
        unset($oText, $onlineData);
    }*/
?>
                    <div class="inputWrap half textarea">
                        <textarea id="subs_comments_customer" name="comments_customer"><?=$order['comments_customer']?></textarea>
                        <label for="subs_comments_customer">הערות מזמין</label>
                    </div>
                    <div class="inputWrap half textarea">
                        <textarea id="subs_comments_owner" name="comments_owner"><?=$order['comments_owner']?></textarea>
                        <label for="subs_comments_owner">הערות בעל מקום</label>
                    </div>
                    <div class="inputWrap textarea">
                        <textarea id="subs_comments_payment" name="comments_payment"><?=$order['comments_payment']?></textarea>
                        <label for="subs_comments_payment">תנאי תשלום</label>
                    </div>
				</div>
				<div class="statusBtn">
<?php
    if ($subID){
?>
					<span class="orderPrice new <?=($paid >= $order['price'] - $order['discount'] ? 'paid' : '')?>" onclick="openPayAfterSaveSubs('#subsForm', {subID: <?=$subID?>})">
						<i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg></i>
						<span>₪<?=number_format(round($order['price'] - $order['discount'], 1))?><span>(₪<?=number_format($paid)?>)</span></span>
					</span>
<?php
    }
?>
					<button type="button" onclick="saveSubscription(this.form)" class="inputWrap submit">שמור מנוי</button>
				</div>

<?php
    /*if ($subID){
        $actions = UserActionLog::getLogForOrder($subID);
?>
				<div class="order-actions">
<?php
        foreach($actions as $action){
            if (strcasecmp('order', substr($action['actionType'], 0, 5)))
                continue;

            $time = substr($action['created'], 11, 5) . ' ' . db2date(substr($action['created'], 0, 10));
?>
					<div class="action-line">
						<div class="action-type"><?=UserActionLog::actionName($action['actionType'])?></div>
						<div class="action-user"><?=$_CURRENT_USER->user($action['buserID'])?></div>
						<div class="action-time"><?=$time?></div>
					</div>
<?php
        }
?>
				</div>
<?php
    }*/
?>
			</form>


<!-- div class="dup-pop" id="duplicate_treatment" style="display:none">
	<div class="dup-cont">
		<div class="close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
		<div id="duplicateContent">
			<div class="title selectTitle">שכפול טיפול</div>
		</div>
		<div class="content" style="font-size:18px;padding-top:20px"></div>
        <div style="text-align:center;margin-top:20px"><button style="height:50px;padding:0 20px;color:white;border-radius:10px;background:#0dabb6;font-size:20px" class="submit">שכפל</button></div>
	</div>
</div -->

<?######################### start add pop ?>
<div class="create_order spa" id="subsAddTreats" style="display:none">
    <div class="container" style="height:400px">
        <div class="close" onclick="$('#subsAddTreats').hide()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
        <div class="title mainTitle">הוסף טיפולים</div>

        <form class="form" id="spaOrderForm" autocomplete="off">
            <input type="hidden" name="price" id="subs_add_price" value="0" />

            <div class="inputWrap half" style="z-index:10">
                <input type="text" name="name" id="sub_single_name" value="" class="ac-inp" />
                <label for="sub_single_name">שם המזמין</label>
                <div class="autoBox"><div class="autoComplete"></div></div>
            </div>

            <div class="inputWrap date four" style="z-index:5">
                <input type="text" name="phone" id="sub_single_phone" value="" class="ac-inp" />
                <label for="sub_single_phone">טלפון</label>
                <div class="autoBox"><div class="autoComplete"></div></div>
            </div>
            <div class="inputWrap date four time gender">
                <div class="radios">
                    <div>
                        <input type="radio" name="malefemale" id="male" value="1" />
                        <label for="male">גבר</label>
                    </div>
                    <div>
                        <input type="radio" name="malefemale" id="female" value="2" />
                        <label for="female">אשה</label>
                    </div>
                </div>
            </div>
            <div class="inputWrap half select orderOnly">
                <select name="trid" id="trid">
                    <option value="0">- - - בחר - - -</option>
<?php
    foreach($treatTypes as $id => $name)
        if ($prices[$id] || $order['treatmentID'] == $id)
            echo '<option value="' , $id , '" ' , ($order['treatmentID'] == $id ? 'selected' : '') , ' data-prices="' , htmlspecialchars(json_encode($prices[$id], JSON_NUMERIC_CHECK)) , '" data-durs="' , htmlspecialchars(json_encode($durations[$id], JSON_NUMERIC_CHECK)) , '">' , $name , '</option>';
?>
                </select>
                <label for="trid">סוג טיפול</label>
            </div>
            <div class="inputWrap date four time">
                <select name="duration" id="subs_duration" title="">
<?php
    $durs = $order['treatmentID'] ? array_unique(array_reduce($durations, function($res, $a){ return array_merge($res, $a); }, [])) : [];
    foreach($durs as $id)
        echo '<option value="' , $id , '" ' , ($order['treatmentLen'] == $id ? 'selected' : '') , '>' , $id , ' דקות</option>';
?>
                </select>
                <label for="subs_duration">משך</label>
            </div>
            <div class="inputWrap date four prefer">
                <div class="radios">
                    <div>
                        <input type="radio" name="tmalefemale" id="subs_tmale" value="1" />
                        <label for="subs_tmale">מטפל</label>
                    </div>
                    <div>
                        <input type="radio" name="tmalefemale" id="subs_tfemale" value="2" />
                        <label for="subs_tfemale">מטפלת</label>
                    </div>
                    <div>
                        <input type="radio" name="tmalefemale" id="subs_tnone" value="0" checked />
                        <label for="subs_tnone">ללא העדפה</label>
                    </div>
                </div>
            </div>
            <div class="inputWrap date four time">
                <input type="number" name="tcount" id="subs_treat_count" value="" />
                <label for="subs_treat_count">כמות הטיפולים</label>
            </div>

            <div class="statusBtn">
                <button type="button" id="subs_submit" class="inputWrap submit">הוסף</button>
            </div>
        </form>
    </div>
		<style>
		.pdfbtn {display: inline-block;vertical-align: middle;min-width: 120px;font-size: 16px;text-align: center;line-height: 40px;background: #e73219;color: #fff;font-weight: 500;cursor: pointer;border-radius: 3px;margin: 20px 0 0 0;padding: 0 10px;/* width: 40%; */}
		#addroom{display:none}
		#addroom.active{display:block;clear:both}

		div#add_treat {cursor:pointer;height: 50px;background: #0dabb6;display: inline-block;font-size: 18px;line-height: 50px;color: white;border-radius: 5px;padding: 0 40px 0 20px;position: relative;}
		div#add_treat::before {position: absolute;content: "+";color: white;font-size: 20px;right: 7px;top: 0;bottom: 0;margin: auto;width: 26px;height: 26px;text-align: center;font-weight: bold;box-sizing: border-box;line-height: 20px;border: 2px white solid;border-radius: 50%;}

		div#add_order {line-height: 50px;margin: 10px;background: #0dabb6;display: inline-block;float: right;font-size: 18px;color: white;padding: 0;border-radius: 5px;}

		input#add_order_button {display: none;}
		#add_order label {padding: 0 40px 0 20px;cursor: pointer;position: relative;line-height:50px;display:block}
		#add_order label::before {position: absolute;width: 30px;height: 30px;background: white;right: 5px;content: "";border-radius: 50%;top: 0;bottom: 0;margin: auto;}
		#add_order input:checked + label::after {position: absolute;width: 16px;height: 16px;background: black;right: 12px;content: "";border-radius: 50%;top: 0;bottom: 0;margin: auto;}

		.addings {border: 1px #0dabb6 solid;padding: 10px;border-radius: 5px;text-align: right;margin-top: 20px;display: block}
		.addings>.title {font-weight: 500;font-size: 16px;}
		.addings>.adding {line-height: 30px;font-size: 18px;display: flex;margin-top: 5px;position:relative}
		.addings>.adding input ~ label {position: relative;padding-right: 40px;box-sizing: border-box;font-weight: 500;cursor: pointer;width: 60%;}
		.addings>.adding input ~ label::before {content: '';width: 30px;height: 30px;border: 1px solid #0dabb6;box-sizing: border-box;position: absolute;top: 50%;right: 0;background: #FFF;transform: translateY(-50%);}
		.addings>.adding input:checked ~ label::after {content: '';width: 7px;height: 14px;box-sizing: border-box;position: absolute;border-right: 3px solid #0dabb6;border-bottom: 3px solid #0dabb6;top: 50%;right: 11px;z-index: 1;transform: translateY(-50%) rotate(45deg);}
		.addings>.adding input ~ label span {display:block;font-size: 14px;font-weight: normal;line-height: 1;margin-top: -4px;}
		.addings>.adding>input {display: none;}
		.addings>.adding input ~ label ~ .l input {width: 50px;height: 30px;box-sizing: border-box;line-height: 30px;border: 1px solid #0dabb6;text-align: center;display:none;-webkit-appearance: none;appearance: none;}
		.addings>.adding input ~ label ~ .l select{width:120px;height:30px;border:1px solid #0dabb6;display:none}
		.addings>.adding input:checked ~ label ~ .l {display:block;}
		.addings>.adding input:checked ~ label ~ .l .price,.addings>.adding input:checked ~ label ~ .l input,.addings>.adding input:checked ~ label ~ .l select {display:inline-block}
		.addings>.adding input ~ label ~ .l {width: 40%;text-align: left;font-size: 0}
		.addings>.adding input ~ label ~ .l .unit_price {font-size: 18px;display: inline-block;min-width: 100px;padding-left: 20px;box-sizing: border-box;color:#ccc}
		.addings>.adding input ~ label ~ .l .price {min-width:70px;font-size: 18px;display: none;}	
		.addings>.adding input ~ label div{font-weight:normal;line-height:1;font-size:0.8em}
		.addings>.adding input ~ label div br{display:none}
		.addings>.adding .print-icon {display:none;width: 30px;float: right;height: 30px;margin-left: 5px;background: url(/user/assets/img/printer-4-48.png);margin-top: 5px;background-size: 80%;background-color: #0dabb6;background-position: center;background-repeat: no-repeat;border-radius: 3px;cursor: pointer;position: absolute;z-index: 9;right: 35px;}
		.addings>.adding input:checked ~ .print-icon{display:block}
		.addings>.adding input:checked ~ .print-icon ~ label{padding-right:70px}
		#spaOrderForm select > option.inactive {color:red}

		@media(max-width:700px){
			.addings>.adding input ~ label ~ .l .unit_price {font-size: 14px;min-width: auto;float: left;width: 40px;line-height: 1.2;padding-left: 0;}
			.addings>.adding input ~ label ~ .l input {margin-top: 6px;margin-right: 6px;float: right;}
			.addings>.adding input ~ label {font-size: 14px;line-height: 1;}
			.addings>.adding input ~ label  span {margin-top:0;font-size:12px}
			.addings>.adding {border-bottom: 1px #CCC solid;padding-bottom: 5px;min-height: 40px;align-items: center;}
			.addings>.adding input ~ label ~ .l .price {line-height: 1.2;float: left;min-width: 40%;}
			.addings>.adding input ~ label ~ .l {align-items: center;display: table-cell !important;}
		}
	</style>
	<?php /*if($multiCompound){?>
	<script>
	$('#orderSite').change(function(){

		$(this).closest('form').find('.roomSelectWrap').hide();
		$(this).closest('form').find('.roomSelectWrap input[type=checkbox]').prop("checked", false );
		if(parseInt(this.value)>0){
			var room = '.roomSelectWrap.siteID'+this.value;
			$(this).closest('form').find('.mutltiRooms').show();
			$(this).closest('form').find(room).show();
		}else{
			$(this).closest('form').find('.mutltiRooms').hide();
		}

		var def = $(this.options[this.selectedIndex]).data('agree');
		$('#form_to_sign').val(def);
	});
	</script>
	<?}*/?>
	<?
	$city_list = json_encode(udb::full_list("SELECT settlementID , TITLE AS clientCity FROM `settlements` WHERE 1"));
	?>
	<script>
		var bl = new SumBalancer('#subsForm');

		$('.pdfbtn').on('click', function() {
			open($(this).attr('data-print')).print();
		});

		$('.treatments_patients_addings .adding .l select.count').on('change', function(){
			$(this).siblings('.price').html('₪' + $(this.options[this.selectedIndex]).data('price'));
			calcSum();
		});

		(typeof 'autoComplete' == 'function' ? Promise.resolve() : $.getScript('/user/assets/js/autoComplete.min.js')).then(function(){
			var cache = {tm: 0, cache: []};
			//debugger;
			function caller(str){
				//debugger;
				if (str.length < 3)
					return Promise.resolve([]);

				return new Promise(function(res){
					var c = {text:str, res:res};

					cache.cache.push(c);
					if (cache.tm)
						window.clearTimeout(cache.tm);

					cache.tm = window.setTimeout(function(){
						var last = cache.cache.pop();

						for(var i = 0; i < cache.cache.length; ++i)
							cache.cache[i].res([]);

						cache.tm = null;
						cache.cache = [];

						last.res($.get('ajax_client.php', 'act=clientInfo&sid=<?=($siteID ?: $_CURRENT_USER->active_site())?>&val=' + last.text).then(res => res.clients));
					}, 500);
				});
			}


			$('.print-icon').click(function() {
				printExtra($(this));
			});

			$('.ac-inp').each(function(){
				var inp = this;

				const autoCompleteJS = new autoComplete({
					selector: '#' + inp.id,
					data: {
						src: caller,
						cache: false,
						keys: ['_text', 'email']
					},
					resultsList: {
						maxResults: 20
					},
					resultItem: {
						element: function(item, data){
							item.setAttribute("data-auto", JSON.stringify(data.value));
						},
						highlight: {
							render: true
						}
					},
					events: {
						list: {
							click: function(e){
								var li = e.target.nodeName.toUpperCase() == 'LI' ? e.target : $(e.target).closest('li').get(0), data = JSON.parse(li.dataset.auto || '{}'),
										form = document.getElementById('subsForm'), form2 = document.getElementById('spaOrderForm'), el;

								Object.keys(data).forEach(function(key){
									if (!data[key])
										return true;

									if (el = form.querySelector('input[name="' + key + '"]'))
										el.value = String(data[key]).trim();
									if (el = form2.querySelector('input[name="' + key + '"]'))
										el.value = String(data[key]).trim();
								});

								this.setAttribute('hidden', '');
							}
						}
					}
				});
			});

			$('.ac-inp2').each(function(){
				var inp = this;
				const autoCompleteJS = new autoComplete({
					selector: '#' + inp.id,
					data: {
						src: <?=$city_list?>,
						cache: false,
						keys: ['clientCity']
					},
					resultsList: {
						maxResults: 20
					},
					resultItem: {
						element: function(item, data){
							item.setAttribute("data-auto", JSON.stringify(data.value));
						},
						highlight: {
							render: true
						}
					},
					events: {
						input: {
							focus: (event) => {		
								//debugger;
								searchval = ($(this).val()? $(this).val() : "*" );
								console.log(searchval);							
								autoCompleteJS.open();
								$(this).closest('.inputWrap').css('z-index','9')
							},
							blur: (event) => {	
								
								console.log("blur");							
								$(this).closest('.inputWrap').attr('style','');
														
								autoCompleteJS.close();
							}
						},
						list: {
							click: function(e){							
								var li = e.target.nodeName.toUpperCase() == 'LI' ? e.target : $(e.target).closest('li').get(0), data = JSON.parse(li.dataset.auto || '{}'),
										form = document.getElementById('subsForm'), el;

								Object.keys(data).forEach(function(key){
									
									if (data[key] && key=="settlementID" && (el = $('#subsForm input[name="settlementID"]'))){
										el.val(String(data[key]).trim());
										el.addClass('valid');
									}

									if (data[key] && key=="clientCity" && (el =  $('#subsForm input[name="clientCity"]'))){
										el.val(String(data[key]).trim());
										$('.settlementName').html(String(data[key]).trim())
									}

									
								});
								//debugger;
								console.log(e);
								$(e.target).closest('.inputWrap').next('.inputWrap').find('input').focus();						
								$(this).closest('.inputWrap').attr('style','');
								this.setAttribute('hidden', '');
							}
						}
					}
				});
			});
		});

		$('#add_treat').on('click', function(){
			let fields = $(this).closest('form').serializeArray(), popForm = $('#subsAddTreats');

			popForm.find('form')[0].reset();
			$.each(fields, (i, e) => popForm.find('[name="' + e.name + '"]').val(e.value));
			popForm.show();
		});

		$('#sub_treatments').on('click', '.delete', function(){
			let div = $(this).closest('.spaorder'), price = div.data('price');

			div.remove();
			bl.add_total(-price);
		}).on('click', '.duplicate', function(){
			let id = $(this).closest('.spaorder').data('id');

			$('#create_subPop').add('#subsAddTreats').add('.pop-pay').remove();
			openSpaFrom({}).then(function(){
				insertTreatmentNew({subTr:id});
			});
		});

		$('#subs_submit').on('click', function(e){
			let data = {};

			try {
				$(this).closest('form').find('input, select').each(function(){
					if (this.type != 'radio' || this.checked)
						data[this.name] = this.value;
					if (this.options && this.selectedIndex >= 0)
						data[this.name + '_text'] = this.options[this.selectedIndex].text;
				});

				if (!parseInt(data.tcount))
					throw {msg:'Illegal treatments count !'};
				if (!parseInt(data.trid))
					throw {msg:'Please select treatment type !'};
				if (!parseInt(data.duration))
					throw {msg:'Please select treatment duration !'};

				let html = $('.subs-new-line').prop('outerHTML').replace('subs-new-line', ''), keys = ['trid', 'duration', 'tmalefemale', 'malefemale', 'price', 'name', 'phone'];

				$.each(data, (key, val) => html = html.replace('{#' + key + '}', val));
				html = html.replace('{#encoded}', $.map(keys, k => (data[k] || '')).join('|').replace(/"/g, '&quot;'));

				$(html.repeat(data.tcount)).each((i, e) => $(e).data('price', data.price)).appendTo('#sub_treatments');

				bl.add_total(data.price * data.tcount);

				$('#subsAddTreats').hide();
			}
			catch (x){
				console.log(x);
				if (x.msg)
					Swal.fire({icon:'error', text:x.msg, title:'שגיאה!'});
				return false;
			}
		});

		$('#trid').on('change', function(){
			var times = $(this.options[this.selectedIndex]).data('durs') || ['- - - -'], sel = $('#subs_duration').get(0), val = parseInt(sel.value), opt = sel.options;

			opt.length = 0;
			times.forEach(function(t, i){
				opt[opt.length] = new Option((t == '- - - -') ? t : t + ' דקות', t, false, t == val);
			});

			$(sel).trigger('change');
		});

		$('#subs_duration').on('change', function(){
			var trid = $('#trid').get(0), prices = trid ? ($(trid.options[trid.selectedIndex]).data('prices') || []) : [];
			$('#subs_add_price').val(prices[this.selectedIndex] || 0);
		});

	</script>
</div>
<?######################### end add pop ?>


		</div>

        <div style="display:none">
            <div class="spaorder subs-new-line">
                <input type="hidden" name="trin[]" value="{#encoded}" />
                <div class="delete"></div>
                <div class="spasect">
                    <b>{#name}</b>
                    <span>{#trid_text} <span style="font-size:12px;vertical-align:middle;">{#duration} דק'</span></span>
                </div>
                <div class="spasect"></div>
                <div class="spasect"></div>
                <div class="spasect">₪{#price}</div>
            </div>
        </div>
    </div>




<?php
            $result['html'] = ob_get_clean();
        break;

        case 'findSubs':
            $num = typemap($_POST['snum'], 'numeric');

            $subs = udb::single_row("SELECT * FROM `subscriptions` WHERE `subNumber` = '" . $num . "' AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
            if (!$subs)
                throw new Exception("Cannot find subscription " . prettySubsNumber($num));

            $result['subID'] = $subs['subID'];
            break;

        case 'saveSubs':
            $subID  = intval($_POST['subID']);

            $input = typemap($_POST, [
                'sourceID'  => 'string',
                'subActive' => 'int',
                'name'      => 'string',
                'tZehoot'   => 'numeric',
                'phone'     => 'numeric',
                'phone2'    => 'numeric',
                'email'     => 'email',
                'settlementID'      => 'int',
                'clientAddress'     => 'string',
                'price_discount'    => 'float',
                'price_to_pay'      => 'float',
                'comments_customer' => 'text',
                'comments_owner'    => 'text',
                'comments_payment'  => 'text',
                '!trin' => ['string']
            ]);

            if (!$input['name'])
                throw new Exception("Please enter correct name");
            if (!$input['phone'] || strlen($input['phone']) < 9 || strlen($input['phone']) > 12)
                throw new Exception("Please enter correct phone");

            $siteID = $_CURRENT_USER->active_site();

            $newTreats = $tins = [];
            foreach($input['trin'] as $t)
                $newTreats[$t] += 1;

            foreach($newTreats as $t => $c){
                $treat = typemap(array_combine(['id', 'tlen', 'mf', 'tmf', 'price', 'name', 'phone'], explode('|', $t)), [
                    'id'    => 'int',
                    'tlen'  => 'int',
                    'mf'    => 'int',
                    'tmf'   => 'int',
                    'price' => 'int',
                    'name'  => 'string',
                    'phone' => 'numeric'
                ]);

                if (!$treat['id'] || !$treat['tlen'])
                    throw new Exception('Illegal treatment ID or duration');

                $que = "SELECT * FROM `treatmentsPricesSites` WHERE `siteID` = " . $siteID . " AND `treatmentID` = " . $treat['id'] . " AND `duratuion` = " . $treat['tlen'];
                $row = udb::single_row($que);
                if (!$row)
                    throw new Exception('Cannot add treatment ' . $treat['id'] . " for " . $treat['tlen'] . 'min duration.');

                $treat['price'] = $row['price1'];
                $treat['count'] = $c;

                $tins[] = $treat;
            }

            $save = [
                'active'       => $input['subActive'] ? 1 : 0,
                'discount'     => $input['price_discount'],
                'sourceID'     => $input['sourceID'],
                'clientName'   => $input['name'],
                'clientTZ'     => $input['tZehoot'],
                'clientEmail'  => $input['email'],
                'clientPhone'  => $input['phone'],
                'clientPhone2' => $input['phone2'],
                'clientCity'   => $input['settlementID'],           // TODO: check that city exists
                'clientAddress'    => $input['clientAddress'],
                'comments_owner'   => $input['comments_owner'],
                'comments_client'  => $input['comments_client'],
                'comments_payment' => $input['comments_payment']
            ];

            if ($subID)
                udb::update('subscriptions', $save, '`subID` = ' . $subID);
            else {
                do {
                    $num = mt_rand(100000000, 999999999);
                } while(udb::single_value("SELECT COUNT(*) FROM `subscriptions` WHERE `subNumber` = " . $num));

                $save['siteID']    = $siteID;
                $save['subNumber'] = $num;

                $subID = udb::insert('subscriptions', $save);
            }

            $result['subID'] = $subID;

            if (count($tins)){
                foreach($tins as $tin){
                    $ins = [
                        'subID' => $subID,
                        'treatmentID' => $tin['id'],
                        'duration'    => $tin['tlen'],
                        'price'       => $tin['price'],
                        'data'        => json_encode(array_filter([
                            'name'  => $tin['name'],
                            'phone' => $tin['phone'],
                            'gen_m' => $tin['mf'],
                            'gen_c' => $tin['tmf']
                        ]), JSON_UNESCAPED_UNICODE)
                    ];

                    for($i = 0; $i < $tin['count']; ++$i)
                        udb::insert("subscriptionTreatments", $ins);
                }

                $row = udb::single_row("SELECT SUM(`price`) AS `total` FROM `subscriptionTreatments` WHERE `subID` = " . $subID);
                udb::update('subscriptions', ['price' => $row['total']], "`subID` = " . $subID);
            }

            break;

        default:
            throw new Exception('Unknown operaion');
    }

    $result['status'] = 0;
}
catch (Exception $e){
    $result['error']  = $e->getMessage();
    $result['status'] = 2;
}
