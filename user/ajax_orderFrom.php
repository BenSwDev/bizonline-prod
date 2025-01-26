<?php 
	require_once "auth.php";
	require_once "functions.php";

/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
	$multiCompound = !$_CURRENT_USER->single_site;

    $sid = 0;

	$que = "SELECT mainPageTitle,mainPageID FROM `MainPages` WHERE mainPageType = 100 AND ifShow = 1";
	$reasons = udb::full_list($que);

	$orderID = intval($_POST['data']['orderID']);
	$orderType = typemap($_POST['data']['ptype'] ?? 'order', 'string');
    $asOrder = intval($_POST['data']['as_order'] ?? 0);
    if (!UserUtilsNew::$orderTypes[$orderType])
        $orderType = 'order';

	$startDate = $_POST['data']['startDate'];
	$endDate = $_POST['data']['endDate'];

    $order = $units = [];

	if($orderID){
		$que = "SELECT sites.siteName, orders.* FROM `orders` INNER JOIN `sites` USING(`siteID`) WHERE `orderID` = " . $orderID;
		$order = udb::single_row($que);

        if (!$_CURRENT_USER->has($order['siteID']))
            throw new Exception("Access denied to order #" . $order['orderIDBySite']);

        $sid = $order['siteID'];

        $que = "SELECT orderUnits.*, rooms_units.roomID FROM orderUnits INNER JOIN `rooms_units` USING(`unitID`) WHERE `orderID` = ".$orderID;
        $units = udb::key_row($que, 'unitID');

        /**CREATE MAIL WHATSAPP SMS**/
		$link = WEBSITE . "signature.php?guid=".$order['guid'];
		//if($order['approved'] || $order['status']!=1){
        if(!$order['approved'] && $order['status']==1){
			
			$subject = "טופס לאישור הזמנה ב". $order['siteName'] ." בתאריך".date('d.m.y', strtotime($order['timeFrom']));
			$body = $order['customerName'].' שלום, על מנת לאשר את הזמנתך ב'.$order['siteName']. ', בימים ' .$weekday[date('w', strtotime($order['timeFrom']))]."-".$weekday[date('w', strtotime($order['timeUntil']))].":".date('d.m.y', strtotime($order['timeFrom']))." - ". date('d.m.y', strtotime($order['timeUntil'])).' יש ללחוץ על הקישור הבא '.$link;
			
		}else{
			$subject = "יצירת קשר בנוגע להזמנה ב". $order['siteName'] ." בתאריך".date('d.m.y', strtotime($order['timeFrom']));
			$body = $order['customerName'].' שלום, '.(($order['approved'] && $order['status']==1)? "מצורף קישור לטופס ההזמנה שלך ".$link : "");
		}

		if($order["customerPhone"]){
			//$order["whatsapp"] = "///wa.me/972".substr($order['customerPhone'],1)."?text=".$body;
			//$order["whatsapp"] = whatsappBuild($order['customerPhone'],$body);
			$order["sms"] = "sms:".$order['customerPhone']."?&body=".$body;
		}
		$order["mailto"] = "mailto:".$order['customerEmail']."?subject=".$subject."&body=".$body;
		/*****/

		$startDate = implode('/',array_reverse(explode('-',substr($order['showTimeFrom'],0,10))));
		$endDate = implode('/',array_reverse(explode('-',substr($order['showTimeUntil'],0,10))));
		$startTime = substr($order['showTimeFrom'],11,5);
		$endTime = substr($order['showTimeUntil'],11,5);

		$orderType = $asOrder ? 'order' : $order['orderType'];

        $que = "SELECT `rooms`.`siteID`, `rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`,`rooms`.`cleanTime`, `rooms`.maxAdults, `rooms`.maxKids, `rooms`.maxGuests
                FROM `rooms_units` INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
                    LEFT JOIN `orderUnits` ON (orderUnits.unitID = `rooms_units`.`unitID` AND orderUnits.orderID = " . $orderID . ")
                WHERE `rooms`.`siteID` = " . $order['siteID'] . " AND (rooms.active = 1 OR orderUnits.unitID IS NOT NULL)";
        $rooms = udb::key_row($que,'unitID');

        $siteData = udb::single_row("SELECT `sites`.`cleanGlobal`, `sites`.`checkInHour`, `sites`.`checkOutHour`,`sites`.`siteName`,`sites`.`sendReviews`,  `sites_langs`.`defaultAgr`, `sites_langs`.`agreement1`, `sites_langs`.`agreement2`, `sites_langs`.`agreement3`
                , IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal`
            FROM `sites` INNER JOIN `sites_langs` ON (`sites_langs`.`siteID` = `sites`.`siteID` AND `sites_langs`.`langID` = 1 AND `sites_langs`.`domainID` = 1)
	        WHERE `sites`.`siteID` = " . $order['siteID']);

        $default = udb::key_value("SELECT `siteID`, `defaultAgr` FROM `sites_langs` WHERE `domainID` = 1 AND `langID` = 1 AND `siteID` = " . $order['siteID']);

        $paid = (new OrderSpaMain($orderID))->get_paid_sum();
	}
	else {
        $que = "SELECT `rooms`.`siteID`,`rooms_units`.`unitID`,`rooms_units`.`unitName`,`rooms`.`roomName`,`rooms`.`cleanTime`, `rooms`.maxAdults, `rooms`.maxKids, `rooms`.maxGuests
                FROM `rooms_units`
                INNER JOIN `rooms` ON (`rooms`.`roomID` = `rooms_units`.`roomID`)
                WHERE rooms.active = 1 AND `rooms`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ")" ;
        $rooms = udb::key_row($que,'unitID');

        $siteData = udb::single_row("SELECT `sites`.`cleanGlobal`, `sites`.`checkInHour`, `sites`.`checkOutHour`,`sites`.`siteName`,`sites`.`sendReviews`,  `sites_langs`.`defaultAgr`, `sites_langs`.`agreement1`, `sites_langs`.`agreement2`, `sites_langs`.`agreement3`
                , IF(sites.masof_active AND sites.masof_number > '', 1, 0) AS `hasTerminal`
            FROM `sites` INNER JOIN `sites_langs` ON (`sites_langs`.`siteID` = `sites`.`siteID` AND `sites_langs`.`langID` = 1 AND `sites_langs`.`domainID` = 1)
	        WHERE `sites`.`siteID` IN (" . $_CURRENT_USER->sites(true) . ")");

        $default = udb::key_value("SELECT `siteID`, `defaultAgr` FROM `sites_langs` WHERE `domainID` = 1 AND `langID` = 1 AND `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");

        $startTime = $siteData['checkInHour'];
        $endTime = $siteData['checkOutHour'];

        $paid = 0;

        $sid = $_CURRENT_USER->select_site();
    }
	//print_r($order); exit;
	if(!$order['domainID']) $order['domainID'] = "0";
?>
    <style>
        .pdfbtn {
            display: inline-block;
            vertical-align: middle;
            min-width: 160px;
            font-size: 20px;
            text-align: center;
            line-height: 40px;
            background: #e73219;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
            border-radius: 3px;
            margin: 20px 0 0 0;
            padding: 0 10px;
            /*width: 40%;*/
        }
    </style>
	<div class="create_order <?=$orderType?>" id="create_orderPop">
		<div class="container">
			<div class="close" onclick="closeOrderForm()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
			<div class="title mainTitle">	
				<div class="domain-icon" style="background-image:url(<?=$domain_icon[$order['domainID']]?>)"></div>	
				<div class="order"  data-orderid="<?=$order['orderID']?>" ><div class="c_status c_s<?=$order['client_status']?>" onclick="change_c_s($(this))"></div></div>
				<?if($asOrder==1){?>
					הפוך שיריון להזמנה
				<?}else{?>
					<?=($order['orderIDBySite'] ? (($orderType=="preorder"? "שיריון" : "הזמנה")." מספר ".$order['orderIDBySite']) : (($orderType=="preorder")?  "שיריון מקום" :  "הזמנה חדשה"))?>
				<?}?>
			</div>
			
			<form class="form" id="orderForm" action="" data-guid="<?=$order['guid']?>" method="post" autocomplete="off" data-defaultAgr="<?=$siteData['defaultAgr']?>">
                <?=($asOrder ? '<input type="hidden" name="as_order" value="1" class="ignore" />' : '')?>
                <input type="hidden" name="otype" value="<?=$orderType?>" class="ignore" />
				<input type="hidden" name="action" value="insertOrder" class="ignore" />
				<input type="hidden" name="orderID" value="<?=$order['orderID']?>" id="orderForm-orderID" />
				<input type="hidden" name="realStartTime" id="realStartTime" value="<?=$siteData['checkInHour']?>" class="ignore" />
				<input type="hidden" name="realEndTime" id="realEndTime" value="<?=$siteData['checkOutHour']?>" class="ignore" />
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
					});
				</script>
				<?php
					if($order['guid'] && $orderType == 'order'){
				?>
				<div class="inputLblWrap" style="width:100%;margin:0;text-align:right">
					<?php if(!$order['signature']) { ?>
                    <div class="pdfbtn" data-print="pre_print.php?oid=<?=$orderID?>" data-p="" style="margin:0">הדפס הסכם לא חתום</div>
					<div style="display: block;background: white;border: 1px #ccc solid;border-radius: 10px;padding: 10px;margin: 10px 0;height:34px;position:relative">
						<div class="switchTtl">אשר הזמנה <span style="font-weight: normal;
    font-size: 12px;display:inline-block;line-height:1;vertical-align:middle;">(למרות שלא נחתמה)</span></div>
						<label class="switch" style="float:left" for="approvedx">
						  <input type="checkbox" name="adminApproved" value="1" id="approvedx" <?=$order['adminApproved']?"checked":""?>  class="">
						  <span class="slider round"></span>
						</label>
					</div>
					<?php } ?>
					<div style="display: block;background: white;border: 1px #ccc solid;border-radius: 10px;padding: 10px;margin: 10px 0;height:34px;position:relative">
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
					
<?
					if (!$asOrder){?>
					<div class="signOpt inOrder">
<?php
					$subject = "טופס לאישור הזמנה ב". $siteData['siteName'] ." בתאריך".date('d.m.y', strtotime($order['timeFrom']));
					$body = $order['customerName'].' שלום, על מנת לאשר את הזמנתך ב' . $siteData['siteName'] . ', בימים ' .$weekday[date('w', strtotime($order['timeFrom']))]."-".$weekday[date('w', strtotime($order['timeUntil']))].":".date('d.m.y', strtotime($order['timeFrom']))." - ". date('d.m.y', strtotime($order['timeUntil'])).' יש ללחוץ על הקישור הבא '.$link;
					
					
					
					 /*else if(!$order['approved']){*/
?>
							<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:34px"><?=$order['approved']?"יצירת קשר":"שליחה לחתימה"?></div>
							<div style="float:left;margin:-5px -5px -5px 0">
							<?if($order['customerPhone']) { ?>	
								<a href="<?=whatsappBuild($order['customerPhone'],$body);?>" target="_blank"><span class="icon whatsapp" data-phone="<?=$order['customerPhone']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span></a>
								<a href="sms:<?=$order['customerPhone']?>?&body=<?=$body?>" target="_blank" class="a_sms"><span class="icon sms" data-sms="<?=$order['customerPhone']?>">
									<img src="/user/assets/img/icon_sms.png" alt="sms">
								</span></a>
							<?php } ?>
							<?php if($order['customerEmail']) { ?>
								<a href="mailto:<?=$order['customerEmail']?>?subject=<?=$subject?>&body=<?=$body?>" target="_blank"><span class="icon mail" data-mail="<?=$order['customerEmail']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" width="32" height="27"><style>.message-ic{fill:#fff}</style><path class="message-ic" d="M29.9 25L24.1 19.4 23.5 18.8 23 18.3 22.6 17.8 22.1 17.4 24.9 15.5 29.8 12 30 11.9 30 14 30 17.2 30 25 29.9 25ZM22.2 6L5 10.9 5.7 13.3 2.1 10.7 2.8 7.8C4.1 7 12.5 2.1 15.9 2 19.3 2.1 27.7 7 29 7.8L29.9 10.7 24.7 14.5 22.2 6ZM6.2 14.8L9.7 17.4 9 18.1 8.5 18.6 8.1 19.1 7.5 19.6 2.1 25 2 25 2 13.1 2 12 2 11.9 2.7 12.4 6.2 14.8ZM9.5 19L10 18.5 10.4 18.1 10.8 17.7 11.2 17.2 11.3 17.2C11.4 17.1 11.6 16.9 11.8 16.8 12.9 15.8 14.4 15.3 15.9 15.3 17.4 15.3 18.9 15.8 20 16.8 20.2 16.9 20.3 17 20.5 17.2L20.6 17.3 21.1 17.7 21.2 17.9 21.7 18.3 22.2 18.8 23 19.7 28.4 25 3.5 25 7.8 20.7 9.5 19Z"></path></svg></span></a>
							<?php } ?>
								<span class="icon plusSend" onclick='$("#sendPopMsg").val($(this).data("msg"));$("#sendPopSubject").val($(this).data("subject"));$(".sendPop").fadeIn("fast");$("#SendPopTitle").text($(this).data("title"));' data-title="<?=$order['approved']?"יצירת קשר":"שליחה לחתימה"?>" data-msg="<?=$body?>" data-subject="<?=$subject?>"></span>
							</div>
					</div>
<?php
					}

					if ($order && $orderType == 'order' && $order["orderID"]) {
					$review = udb::single_row("SELECT  reviews.*,orders.orderID, files.src AS document FROM `reviews` LEFT JOIN orders USING (orderID) LEFT JOIN files ON (files.ref = reviewID AND files.table = 'reviews') WHERE reviews.orderID = ".$order["orderID"]);
					if(!$review["reviewID"]) {
						$reviewBody = urlencode("שלום ".$order['customerName'].". לאחר שהותך  ב".$siteData['siteName'].", נשמח לקבל את חוות דעתך. למילוי חוות הדעת:  https://bizonline.co.il/review.php?guid=".$order["guid"]);
						$reviewSubjsct = "מילוי חוות דעת ".$order["siteName"];
?>
					<div class="signOpt inOrder">
						<div style="font-size:16px;font-weight:bold;color:#424242;display:inline-block;line-height:34px">חוות דעת 
							<span style="font-size: 14px; line-height: 1; width: 74px; display: inline-block; padding: 3px 10px; font-weight: normal;">
								<?if(strtotime(date("Y-m-d h:i:s")) <= strtotime($order['showTimeFrom'])){?>תשלח למילוי ביום עזיבה
								<?}else if($order['SentReview']){?><b style="color:green">נשלחה בקשה למילוי</b><?}?>
							</span>
						</div>
						<?if(strtotime(date("Y-m-d h:i:s")) <= strtotime($order['showTimeFrom'])){?>
							<label class="switch" style="float:left" onclick="swal.fire({icon: 'error',title: 'שינוי הגדרת שליחת חוות דעת ביום עזיבה מתבצע דרך תפריט הגדרות - חוות דעת'});">
							  <input type="checkbox" name="allowReview" value="1"  class="ignore" <?=$siteData["sendReviews"]? "checked" : ""?> onclick="return false;">
							  <span class="slider round"></span>
							</label>
						<?}else{?>
						<div style="float:left;margin:-5px -5px -5px 0">
						<?php if($order['customerPhone']){?>
							<a href="<?=whatsappBuild($order['customerPhone'],$reviewBody);?>" target="_blank"><span class="icon whatsapp" data-phone="<?=$order['customerPhone']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span></a>

							<a href="sms:<?=$order['customerPhone']?>?&body=<?=$reviewBody?>" target="_blank" class="a_sms"><span class="icon sms" data-sms="<?=$order['customerPhone']?>">
								<img src="/user/assets/img/icon_sms.png" alt="sms">
							</span></a>
						<?php } ?>
						<?php if($order['customerEmail']){?>
                            <a href="#" onclick="reviewInvite(<?=$orderID?>)"><span class="icon mail" data-mail="<?=$order['customerEmail']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" width="32" height="27"><style>.message-ic{fill:#fff}</style><path class="message-ic" d="M29.9 25L24.1 19.4 23.5 18.8 23 18.3 22.6 17.8 22.1 17.4 24.9 15.5 29.8 12 30 11.9 30 14 30 17.2 30 25 29.9 25ZM22.2 6L5 10.9 5.7 13.3 2.1 10.7 2.8 7.8C4.1 7 12.5 2.1 15.9 2 19.3 2.1 27.7 7 29 7.8L29.9 10.7 24.7 14.5 22.2 6ZM6.2 14.8L9.7 17.4 9 18.1 8.5 18.6 8.1 19.1 7.5 19.6 2.1 25 2 25 2 13.1 2 12 2 11.9 2.7 12.4 6.2 14.8ZM9.5 19L10 18.5 10.4 18.1 10.8 17.7 11.2 17.2 11.3 17.2C11.4 17.1 11.6 16.9 11.8 16.8 12.9 15.8 14.4 15.3 15.9 15.3 17.4 15.3 18.9 15.8 20 16.8 20.2 16.9 20.3 17 20.5 17.2L20.6 17.3 21.1 17.7 21.2 17.9 21.7 18.3 22.2 18.8 23 19.7 28.4 25 3.5 25 7.8 20.7 9.5 19Z"></path></svg></span></a>
									
						<?php 
						}?>
						<!-- span class="icon plusSend" onclick='$("#sendPopMsg").val($(this).data("msg"));$("#sendPopSubject").val($(this).data("subject"));$(".sendPop").fadeIn("fast");$("#SendPopTitle").text($(this).data("title"));' data-msg="<?=$reviewBody?>" data-title="שליחת חוות דעת" data-subject="<?=$reviewSubject?>"></span -->
						</div>
						<?}?>
					</div>

				

					<?php }else if($review){?>
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
					?>
				</div>
				<?}else{?>
					<input type="hidden"  name="status" value="1" >
					<input type="hidden"  name="allowReview" value="<?=$siteData['sendReviews']?>" >
				<?}?>
				<div>
				<div class="inputWrap half select orderOnly" id="wrapsources">
					<select <?=($siteData['sourceRequired'] && !$orderID)? "class='required'" : ""?> onchange="if($(this).val()!='novalue'){$(this).removeClass('required')}else{$(this).addClass('required')};showCoupons($(this))" name="sourceID" id="sourceID" <?=($order['apiSource']=='spaplus' || $order['sourceID']=='online') ? "readonly style='pointer-events:none'":""?>>
						<?if($siteData['sourceRequired'] && !$orderID){?>
						<option style='color:red' value="novalue">יש לבחור</option>
						<?}?>
						<option value="0">הזמנה רגילה</option>
<?php
            $cuponTypes = $orderID ? SourceList::site_list($sid, false, $order['sourceID']) : SourceList::site_list($sid ?: $_CURRENT_USER->active_site());

            foreach($cuponTypes as $source)
                echo '<option value="' . $source['key'] . '" ' . (($source['key'] == $order['sourceID']) ? 'selected' : '') . '>' . $source['fullname'] . '</option>';

                        /*UserUtilsNew::init($_CURRENT_USER->active_site());
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
                        <option value="online" <?=$order['sourceID']=='online' ? "selected":""?>>הזמנת Online</option> */
?>
					</select>
					<label for="sourceID">מקור ההזמנה</label>
				</div>
				</div>
				<div class="inputWrap date four">
					<input type="text" value="<?=$startDate?>" name="fromDate" class="datePick fromDate" readonly>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23" width="23" height="23"><path class="shp0" d="M12 16.1C12 16.9 12.7 17.6 13.6 17.6L15.4 17.6C16.2 17.6 16.9 16.9 16.9 16.1L16.9 14.2C16.9 13.4 16.2 12.7 15.4 12.7L13.6 12.7C12.7 12.7 12 13.4 12 14.2L12 16.1ZM13.6 14.2L15.4 14.2 15.4 16.1C15.4 16.1 15.4 16.1 15.4 16.1L13.6 16.1 13.6 14.2ZM16.2 9.3C16.6 9.3 16.9 9.7 16.9 10.1 16.9 10.5 16.6 10.9 16.2 10.9 15.7 10.9 15.4 10.5 15.4 10.1 15.4 9.7 15.7 9.3 16.2 9.3ZM12.8 9.3C13.2 9.3 13.6 9.7 13.6 10.1 13.6 10.5 13.2 10.9 12.8 10.9 12.4 10.9 12 10.5 12 10.1 12 9.7 12.4 9.3 12.8 9.3ZM20.3 15.6C20.7 15.6 21.1 15.3 21.1 14.8L21.1 6.6C21.1 4.9 19.7 3.5 18 3.5L16.9 3.5 16.9 2.7C16.9 2.3 16.6 2 16.2 2 15.7 2 15.4 2.3 15.4 2.7L15.4 3.5 11.9 3.5 11.9 2.7C11.9 2.3 11.5 2 11.1 2 10.7 2 10.3 2.3 10.3 2.7L10.3 3.5 6.8 3.5 6.8 2.7C6.8 2.3 6.5 2 6.1 2 5.6 2 5.3 2.3 5.3 2.7L5.3 3.5 4.3 3.5C2.6 3.5 1.2 4.9 1.2 6.6L1.2 18.7C1.2 20.4 2.6 21.8 4.3 21.8L18 21.8C19.7 21.8 21.1 20.4 21.1 18.7 21.1 18.3 20.7 17.9 20.3 17.9 19.9 17.9 19.5 18.3 19.5 18.7 19.5 19.6 18.8 20.3 18 20.3L4.3 20.3C3.5 20.3 2.8 19.6 2.8 18.7L2.8 6.6C2.8 5.8 3.5 5.1 4.3 5.1L5.3 5.1 5.3 5.8C5.3 6.3 5.6 6.6 6.1 6.6 6.5 6.6 6.8 6.3 6.8 5.8L6.8 5.1 10.3 5.1 10.3 5.8C10.3 6.3 10.7 6.6 11.1 6.6 11.5 6.6 11.9 6.3 11.9 5.8L11.9 5.1 15.4 5.1 15.4 5.8C15.4 6.3 15.7 6.6 16.2 6.6 16.6 6.6 16.9 6.3 16.9 5.8L16.9 5.1 18 5.1C18.8 5.1 19.5 5.8 19.5 6.6L19.5 14.8C19.5 15.3 19.9 15.6 20.3 15.6ZM6.1 16.1C6.5 16.1 6.8 16.4 6.8 16.8 6.8 17.3 6.5 17.6 6.1 17.6 5.6 17.6 5.3 17.3 5.3 16.8 5.3 16.4 5.6 16.1 6.1 16.1ZM6.1 9.3C6.5 9.3 6.8 9.7 6.8 10.1 6.8 10.5 6.5 10.9 6.1 10.9 5.6 10.9 5.3 10.5 5.3 10.1 5.3 9.7 5.6 9.3 6.1 9.3ZM6.1 12.7C6.5 12.7 6.8 13 6.8 13.5 6.8 13.9 6.5 14.2 6.1 14.2 5.6 14.2 5.3 13.9 5.3 13.5 5.3 13 5.6 12.7 6.1 12.7ZM9.4 12.7C9.9 12.7 10.2 13 10.2 13.5 10.2 13.9 9.9 14.2 9.4 14.2 9 14.2 8.6 13.9 8.6 13.5 8.6 13 9 12.7 9.4 12.7ZM9.4 9.3C9.9 9.3 10.2 9.7 10.2 10.1 10.2 10.5 9.9 10.9 9.4 10.9 9 10.9 8.6 10.5 8.6 10.1 8.6 9.7 9 9.3 9.4 9.3ZM9.4 16.1C9.9 16.1 10.2 16.4 10.2 16.8 10.2 17.3 9.9 17.6 9.4 17.6 9 17.6 8.6 17.3 8.6 16.8 8.6 16.4 9 16.1 9.4 16.1Z"></path></svg>
					<label for="from">מתאריך</label>
				</div>
				<div class="inputWrap date four time">
					<input type="text" value="<?=(substr($startTime,0,5))?>" name="startTime" class="timePick" readonly>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>
					<label for="from">שעת כניסה</label>
				</div>
				<div class="inputWrap date four">
					<input type="text" value="<?=$endDate?>" name="endDate" class="datePick endDate" readonly>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23" width="23" height="23"><path class="shp0" d="M12 16.1C12 16.9 12.7 17.6 13.6 17.6L15.4 17.6C16.2 17.6 16.9 16.9 16.9 16.1L16.9 14.2C16.9 13.4 16.2 12.7 15.4 12.7L13.6 12.7C12.7 12.7 12 13.4 12 14.2L12 16.1ZM13.6 14.2L15.4 14.2 15.4 16.1C15.4 16.1 15.4 16.1 15.4 16.1L13.6 16.1 13.6 14.2ZM16.2 9.3C16.6 9.3 16.9 9.7 16.9 10.1 16.9 10.5 16.6 10.9 16.2 10.9 15.7 10.9 15.4 10.5 15.4 10.1 15.4 9.7 15.7 9.3 16.2 9.3ZM12.8 9.3C13.2 9.3 13.6 9.7 13.6 10.1 13.6 10.5 13.2 10.9 12.8 10.9 12.4 10.9 12 10.5 12 10.1 12 9.7 12.4 9.3 12.8 9.3ZM20.3 15.6C20.7 15.6 21.1 15.3 21.1 14.8L21.1 6.6C21.1 4.9 19.7 3.5 18 3.5L16.9 3.5 16.9 2.7C16.9 2.3 16.6 2 16.2 2 15.7 2 15.4 2.3 15.4 2.7L15.4 3.5 11.9 3.5 11.9 2.7C11.9 2.3 11.5 2 11.1 2 10.7 2 10.3 2.3 10.3 2.7L10.3 3.5 6.8 3.5 6.8 2.7C6.8 2.3 6.5 2 6.1 2 5.6 2 5.3 2.3 5.3 2.7L5.3 3.5 4.3 3.5C2.6 3.5 1.2 4.9 1.2 6.6L1.2 18.7C1.2 20.4 2.6 21.8 4.3 21.8L18 21.8C19.7 21.8 21.1 20.4 21.1 18.7 21.1 18.3 20.7 17.9 20.3 17.9 19.9 17.9 19.5 18.3 19.5 18.7 19.5 19.6 18.8 20.3 18 20.3L4.3 20.3C3.5 20.3 2.8 19.6 2.8 18.7L2.8 6.6C2.8 5.8 3.5 5.1 4.3 5.1L5.3 5.1 5.3 5.8C5.3 6.3 5.6 6.6 6.1 6.6 6.5 6.6 6.8 6.3 6.8 5.8L6.8 5.1 10.3 5.1 10.3 5.8C10.3 6.3 10.7 6.6 11.1 6.6 11.5 6.6 11.9 6.3 11.9 5.8L11.9 5.1 15.4 5.1 15.4 5.8C15.4 6.3 15.7 6.6 16.2 6.6 16.6 6.6 16.9 6.3 16.9 5.8L16.9 5.1 18 5.1C18.8 5.1 19.5 5.8 19.5 6.6L19.5 14.8C19.5 15.3 19.9 15.6 20.3 15.6ZM6.1 16.1C6.5 16.1 6.8 16.4 6.8 16.8 6.8 17.3 6.5 17.6 6.1 17.6 5.6 17.6 5.3 17.3 5.3 16.8 5.3 16.4 5.6 16.1 6.1 16.1ZM6.1 9.3C6.5 9.3 6.8 9.7 6.8 10.1 6.8 10.5 6.5 10.9 6.1 10.9 5.6 10.9 5.3 10.5 5.3 10.1 5.3 9.7 5.6 9.3 6.1 9.3ZM6.1 12.7C6.5 12.7 6.8 13 6.8 13.5 6.8 13.9 6.5 14.2 6.1 14.2 5.6 14.2 5.3 13.9 5.3 13.5 5.3 13 5.6 12.7 6.1 12.7ZM9.4 12.7C9.9 12.7 10.2 13 10.2 13.5 10.2 13.9 9.9 14.2 9.4 14.2 9 14.2 8.6 13.9 8.6 13.5 8.6 13 9 12.7 9.4 12.7ZM9.4 9.3C9.9 9.3 10.2 9.7 10.2 10.1 10.2 10.5 9.9 10.9 9.4 10.9 9 10.9 8.6 10.5 8.6 10.1 8.6 9.7 9 9.3 9.4 9.3ZM9.4 16.1C9.9 16.1 10.2 16.4 10.2 16.8 10.2 17.3 9.9 17.6 9.4 17.6 9 17.6 8.6 17.3 8.6 16.8 8.6 16.4 9 16.1 9.4 16.1Z"></path></svg>
					<label for="from">עד תאריך</label>
				</div>
				<div class="inputWrap date four time">
					<input type="text" value="<?=(substr($endTime,0,5))?>" name="endTime" class="timePick" readonly>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>                            
					<label for="from">שעת עזיבה</label>
				</div>
				<div class="inputWrap half">
					<input type="text" name="name" id="name" value="<?=$order['customerName']?>">
					<label for="name">שם המזמין</label>
				</div>
				<div class="inputWrap half tZehoot orderOnly">
					<input type="text" name="tZehoot" id="tZehoot" inputmode="numeric" value="<?=$order['customerTZ']?>">
					<label for="tZehoot">תעודת זהות</label>                            
				</div>
				<div class="inputWrap half">
					<input type="text" name="phone" id="phone" value="<?=$order['customerPhone']?>">
					<label for="phone">טלפון</label>
				</div>
				
				<div class="inputWrap half orderOnly">
					<input type="text" name="phone2" id="phone2" value="<?=$order['customerPhone2']?>">
					<label for="phone2">טלפון נוסף</label>
				</div>
				
				<div class="inputWrap half email orderOnly">
					<input type="text" name="email" id="email" value="<?=$order['customerEmail']?>">
					<label for="email">אימייל</label>                            
				</div>
				<div class="inputWrap half select orderOnly">
					<select name="reason" id="reason">
						<option value="0">-</option>
						<?php foreach($reasons as $reason) { ?>
						<option value="<?=$reason['mainPageID']?>" <?=$order['reason']==$reason['mainPageID']?"selected":""?>><?=$reason['mainPageTitle']?></option>
						<?php } ?>

					</select>
					<label for="reason">סיבת הגעה</label>
				</div>				
				<div class="inputWrap orderOnly">
					<input type="text" name="clientAddress" id="clientAddress" value="<?=$order['customerAddress']?>">
					<label for="clientAddress">כתובת המזמין</label>
				</div>				
<?php
    if($multiCompound){
        $sname = udb::key_value("SELECT `siteID`, `siteName` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
				<div style="background: #4fc2ca;padding-top: 10px;margin-bottom:10px">
				<div class="inputWrap half select ">
					<select name="orderSite" id="orderSite" <?=($order['siteID'] ? "readonly disabled" : "")?>>
						<option value="0">יש לבחור מתחם להזמנה</option>
<?php
        foreach($sname as $id => $name)
            echo '<option value="' , $id , '" ' , ($id == $sid ? 'selected' : '') , ' data-agree="' , ($default[$id] ?: 1) , '">' , $name , '</option>';
?>
					</select>
					<label for="orderSite">מתחם נבחר</label>
				</div>
				</div>
<?php
    }
?>
				<div class="mutltiRooms" style="<?=($multiCompound && !$order['siteID'])? "display:none" : ""?>">
					<div class="rooms">
<?php
    foreach($rooms as $room) {
        $pepole = $room['maxAdults']?$room['maxAdults']:$room['maxGuests'];
        $kids = $room['maxKids']?$room['maxKids']:($order['adults']?$room['maxGuests']-$order['adults']:0);
        $unit = $units[$room['unitID']];
?>
						<div class="roomSelectWrap siteID<?=$room['siteID']?>" data-unitid="<?=$room['unitID']?>" data-adults="<?=$room['maxAdults']?>" data-kids="<?=$room['maxKids']?>" data-maxguests="<?=$room['maxGuests']?>">
							<input type="checkbox" id="room<?=$room['unitID']?>" name="unitID[]" value="<?=$room['unitID']?>" class="ignore unit-id" <?=($unit?"checked":"")?>>
							<div class="room">
							<label for="room<?=$room['unitID']?>">
								<div class="title"><?=$room['unitName']?></div>
							</label>
								<div class="l">
									<div class="dataInp adults">
										<label for="adults_room<?=$room['unitID']?>">מבוגרים</label>
										<select name="adults_room[<?=$room['unitID']?>]" class="adults_room">
											<?php for($i=1;$i<=$pepole;$i++) { ?>
											<option value="<?=$i?>" <?=(($unit['adults'] == $i) ?"selected": ($i==2?"selected":""))?>><?=$i?></option>
											<?php } ?>
										</select>
									</div>
									<div class="dataInp kids">
										<label for="kids_room<?=$room['unitID']?>">ילדים</label>
										<select name="kids_room[<?=$room['unitID']?>]" class="kids_room" <?=$order['kids']?>>
											<option value="0">0</option>
											<?php for($i=1;$i<=$kids;$i++) { ?>
											  <option value="<?=$i?>" <?=$unit['kids']==$i?"selected":""?>><?=$i?></option>
											<?php } ?>
										</select>
									</div>
									<div class="dataInp babies">
										<label for="babies_room<?=$room['unitID']?>">תינוקות</label>
										<select name="babies_room[<?=$room['unitID']?>]" class="babies_room">
											<option value="0">0</option>
											<?php for($i=1;$i<=$kids;$i++) { ?>
												<option value="<?=$i?>" <?=$unit['babies']==$i?"selected":""?>><?=$i?></option>
											<?php } ?>
										</select>
									</div>
									<div class="payments">
										<div class="meals" style="display:none">
											<select name="meal[<?=$room['unitID']?>]">
												<option value="0">ללא ארוחת בוקר</option>
												<option value="1" <?=($unit['breakfast'] ? 'selected' : '')?>>כולל ארוחת בוקר</option>
											</select>
										</div>
										<div class="dataInp">
											<label>סכום לתשלום</label>
											<input name="payment[<?=$room['unitID']?>]" class="payment-inp" value="<?=($unit['base_price'] ?: '')?>" type="number" />
										</div>
										<?/*
										<div class="dataInp">
											<label>מקדמה ששולמה</label>
											<input readonly name="adv_payment[<?=$room['unitID']?>]" class="prePayment-inp" value="<?=($unit['advance'] ?: '')?>" type="number" />
										</div>
										*/?>
									</div>
								</div>
							</div>
						</div>
<?php
    }
?>
					</div>
<?php
    $selectedForm = $order['form_to_sign'] ?: $siteData['defaultAgr'];
?>
					<div class="inputWrap select orderOnly">
						<label for="form_to_sign">טופס לחתימה</label>
						<select name="form_to_sign" id="form_to_sign">
							<option value="1" <?=$selectedForm==1?"selected":""?>>הסכם 1</option>
							<option value="2" <?=$selectedForm==2?"selected":""?>>הסכם 2</option>
							<option value="3" <?=$selectedForm==3?"selected":""?>>הסכם 3</option>
							<option value="4" <?=$selectedForm==4?"selected":""?>>הסכם 4</option>
                            <option value="10" <?=$selectedForm==10?"selected":""?>>הסכם שכירות</option>
						</select>
					</div>
					<div class="inputWrap half orderOnly">
						<input type="number" name="price_to_pay" id="price_to_pay" value="<?=$order['price']?>">
						<label for="price_to_pay">סכום ההזמנה</label>
					</div>

					<!-- div class="inputWrap half orderOnly">
						<input readonly type="number" name="prePay" id="prePay" value="<?=$order['advance']?>">
						<label for="prePay">סה"כ מקדמה ששולמה</label>
					</div -->
                    <div class="inputWrap half orderOnly">
						<input readonly type="number" name="prePay" id="prePay" value="<?=$paid?>">
						<label for="prePay">סה"כ שולם</label>
					</div>
					<!--div style="font-size: 14px;text-align: left;margin-bottom: 20px;max-width:300px;float:left">
						<span style="float:left;height:40px;transform: translate(10px, 5px);"><span style="float: left;border-top: 10px black solid;border-left: 10px transparent solid;display: block;margin-right: 20px;transform: rotate(-45deg);"></span></span>
						<?=$orderID? "שימו לב! מקדמות תשלום יש לעדכן דרך כפתור התשלום בתחתית המסך" : "לאחר שמירת ההזמנה יפתח מסך עדכון תשלומים בו תוכלו לעדכן מקדמה על התשלום"?>
					</div -->
					<div class="inputWrap half orderOnly">
						<input type="text" name="leftPay" id="leftPay" value="" readonly>
						<label for="leftPay">נותר לתשלום</label>
					</div>
					
					<div class="inputWrap half orderOnly">
						<input type="number" name="extraPrice" id="extraPrice" value="<?=$order['extraPrice']?>">
						<label for="extraPrice">תוספת לאדם</label>
					</div>
					<div class="inputWrap half textarea">
						<textarea id="comments_customers" name="comments_customer"><?=$order['comments_customer']?></textarea>
						<label for="comments_customer">הערות מזמין</label>
					</div>
					<div class="inputWrap half textarea">
						<textarea id="comments_owner" name="comments_owner"><?=$order['comments_owner']?></textarea>
						<label for="comments_owner">הערות בעל מקום</label>
					</div>
					<div class="inputWrap textarea">
						<textarea id="comments_payment" name="comments_payment"><?=$order['comments_payment']?></textarea>
						<label for="comments_payment">תנאי תשלום</label>
					</div>
				
					<div class="statusBtn">
<?php
		if ($orderID /*&& $siteData['hasTerminal']*/){
?>
						<span class="orderPrice new <?=($paid >= $order['price'] ? 'paid' : '')?>" onclick="openPayOrder({orderID: <?=$orderID?>})">
							<i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg></i>
							<span>₪<?=number_format($order['price'])?><span>(₪<?=number_format($paid)?>)</span></span>
						</span>
<?php
	}
?>
						<button type="button" onclick="insertOrder()" class="inputWrap submit">שמור<?=$order['signature']?" ובטל חתימה":""?></button>
						<!-- <div class="cancelOrderBtn">בטל הזמנה</div> -->
						

						<?if($orderID){?>
						<div style="margin-top:20px;margin-bottom:10px">
							<?if($order['status']){?>
							<div class="delOrderBtn" onclick="orderCancel(<?=$orderID?>)">
								<svg height="427pt" viewBox="-40 0 427 427.00131" width="427pt" xmlns="http://www.w3.org/2000/svg"><path d="m232.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m114.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m28.398438 127.121094v246.378906c0 14.5625 5.339843 28.238281 14.667968 38.050781 9.285156 9.839844 22.207032 15.425781 35.730469 15.449219h189.203125c13.527344-.023438 26.449219-5.609375 35.730469-15.449219 9.328125-9.8125 14.667969-23.488281 14.667969-38.050781v-246.378906c18.542968-4.921875 30.558593-22.835938 28.078124-41.863282-2.484374-19.023437-18.691406-33.253906-37.878906-33.257812h-51.199218v-12.5c.058593-10.511719-4.097657-20.605469-11.539063-28.03125-7.441406-7.421875-17.550781-11.5546875-28.0625-11.46875h-88.796875c-10.511719-.0859375-20.621094 4.046875-28.0625 11.46875-7.441406 7.425781-11.597656 17.519531-11.539062 28.03125v12.5h-51.199219c-19.1875.003906-35.394531 14.234375-37.878907 33.257812-2.480468 19.027344 9.535157 36.941407 28.078126 41.863282zm239.601562 279.878906h-189.203125c-17.097656 0-30.398437-14.6875-30.398437-33.5v-245.5h250v245.5c0 18.8125-13.300782 33.5-30.398438 33.5zm-158.601562-367.5c-.066407-5.207031 1.980468-10.21875 5.675781-13.894531 3.691406-3.675781 8.714843-5.695313 13.925781-5.605469h88.796875c5.210937-.089844 10.234375 1.929688 13.925781 5.605469 3.695313 3.671875 5.742188 8.6875 5.675782 13.894531v12.5h-128zm-71.199219 32.5h270.398437c9.941406 0 18 8.058594 18 18s-8.058594 18-18 18h-270.398437c-9.941407 0-18-8.058594-18-18s8.058593-18 18-18zm0 0"></path><path d="m173.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path></svg>	
								בטל הזמנה
							</div>
							<?}else{?>
							<div class="delOrderBtn" onclick="orderDelete(<?=$orderID?>)">
								<svg height="427pt" viewBox="-40 0 427 427.00131" width="427pt" xmlns="http://www.w3.org/2000/svg"><path d="m232.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m114.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path><path d="m28.398438 127.121094v246.378906c0 14.5625 5.339843 28.238281 14.667968 38.050781 9.285156 9.839844 22.207032 15.425781 35.730469 15.449219h189.203125c13.527344-.023438 26.449219-5.609375 35.730469-15.449219 9.328125-9.8125 14.667969-23.488281 14.667969-38.050781v-246.378906c18.542968-4.921875 30.558593-22.835938 28.078124-41.863282-2.484374-19.023437-18.691406-33.253906-37.878906-33.257812h-51.199218v-12.5c.058593-10.511719-4.097657-20.605469-11.539063-28.03125-7.441406-7.421875-17.550781-11.5546875-28.0625-11.46875h-88.796875c-10.511719-.0859375-20.621094 4.046875-28.0625 11.46875-7.441406 7.425781-11.597656 17.519531-11.539062 28.03125v12.5h-51.199219c-19.1875.003906-35.394531 14.234375-37.878907 33.257812-2.480468 19.027344 9.535157 36.941407 28.078126 41.863282zm239.601562 279.878906h-189.203125c-17.097656 0-30.398437-14.6875-30.398437-33.5v-245.5h250v245.5c0 18.8125-13.300782 33.5-30.398438 33.5zm-158.601562-367.5c-.066407-5.207031 1.980468-10.21875 5.675781-13.894531 3.691406-3.675781 8.714843-5.695313 13.925781-5.605469h88.796875c5.210937-.089844 10.234375 1.929688 13.925781 5.605469 3.695313 3.671875 5.742188 8.6875 5.675782 13.894531v12.5h-128zm-71.199219 32.5h270.398437c9.941406 0 18 8.058594 18 18s-8.058594 18-18 18h-270.398437c-9.941407 0-18-8.058594-18-18s8.058593-18 18-18zm0 0"></path><path d="m173.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"></path></svg>	
								מחיקה מוחלטת
							</div>
							<div class="delOrderBtn" onclick="orderRestore(<?=$orderID?>)">
								<svg id="Capa_1" enable-background="new 0 0 497.883 497.883" height="512" viewBox="0 0 497.883 497.883" width="512" xmlns="http://www.w3.org/2000/svg"><path d="m435.647 155.588-62.235 93.353h31.118c0 85.786-69.802 155.588-155.588 155.588-52.788 0-99.368-26.561-127.511-66.883l-36.282 54.424c39.959 45.668 98.487 74.694 163.793 74.694 120.11 0 217.823-97.714 217.823-217.823h31.118z"></path><path d="m93.353 248.941c0-85.786 69.802-155.588 155.588-155.588 52.788 0 99.368 26.561 127.511 66.883l36.282-54.423c-39.959-45.668-98.487-74.694-163.793-74.694-120.11 0-217.823 97.714-217.823 217.823h-31.118l62.235 93.353 62.235-93.353z"></path></svg>
								שחזור הזמנה
							</div>
							<?}?>
						</div>
						<?}?>
					</div>
					<div class="signBtn" >שלח לחתימה</div>
				</div>
<?php
    if ($orderID){
        $actions = UserActionLog::getLogForOrder($orderID);
//					$actionsTypes[1]="שמירה";
//					$actionsTypes[2]="שמירה וביטול חתימה";
//					$actionsTypes[3]="ביטול";
//					$actionsTypes[4]="שיחזור";
//					$actionsTypes[5]="שליחה לחתימה";
//					$actionsTypes[6]="יצירת קשר";
//					$actions[]=array("type"=>1,"user"=>2,"actionTime"=>"2020-10-08 16:12:10");
//					$actions[]=array("type"=>2,"user"=>1,"actionTime"=>"2020-10-08 16:15:10");
//					$actions[]=array("type"=>3,"user"=>1,"actionTime"=>"2020-10-08 16:16:10");
//					$users[1]="רועי פלומבו";
//					$users[2]="סרגי פלדשר";
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
    }
?>
			</form>
			
		</div>
	</div>
<?php
    if ($multiCompound){
?>
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

<?php
        if ($sid && !$orderID)
            echo '$(function(){$("#orderSite").trigger("change");});';
?>
</script>
<?php
    }
?>

<script>
    $('.pdfbtn').on('click', function() {
        open($(this).attr('data-print')).print();
    });
</script>
