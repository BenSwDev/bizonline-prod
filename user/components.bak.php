<?php
$weekday = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
$month_name = ['','ינואר', 'פברואר', 'מרץ', 'אפריל', 'מאי', 'יוני', 'יולי','אוגוסט', 'ספטמבר', 'אוקטובר', 'נובמבר', 'דצמבר'];
$domain_icon = [
        0 => "/user/assets/domains/biz.jpg",
        1 => "/user/assets/domains/vii.jpg"
];
function curl_func($url){
    $curl = curl_init();
    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: ";
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    $html = curl_exec($curl);
    curl_close($curl);
    return $html;
}
function getLocationNumbers($address){
    $apikey = "AIzaSyCnNTvqCR_SwqqkCCOcAr7WlPYQTsZXbQ0";
    //$apikey = "AIzaSyBCBut3eC_1LxaeXMmyILFv6nGJxTa_hZ4";
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&sensor=false&key=" . $apikey;
    $response=curl_func($url);
    $response_a = json_decode($response);
    $loc=Array();
    $loc['lat']=$response_a->results[0]->geometry->location->lat;
    $loc['long']=$response_a->results[0]->geometry->location->lng;
    if(!$loc['lat']) file_put_contents("geolocation-errors.txt",print_r($response_a,true), FILE_APPEND);
    return $loc;
}

function orderComp($order){
    global $weekday;
	global $domain_icon;
    global $_CURRENT_USER;
    global $siteData;

    static $sourcesArray = null;

	if (!$sourcesArray)
        $sourcesArray = SourceList::full_list(true);

	$multiCompound = !$_CURRENT_USER->single_site;
    if ($order['timeFrom'] && strcmp($order['timeFrom'], '0000-00-00 00:00:00')){
        if(date('d.m.y', strtotime($order['timeUntil'])) == date('d.m.y', strtotime($order['timeFrom']))){
            $date_n_day = "ביום ".$weekday[date('w', strtotime($order['timeFrom']))]." - ".date('d.m.y', strtotime($order['timeFrom']));
        }else{
            $date_n_day = ', בימים ' .$weekday[date('w', strtotime($order['timeFrom']))]."-".$weekday[date('w', strtotime($order['timeUntil']))].": ".date('d.m.y', strtotime($order['timeFrom']))." - ". date('d.m.y', strtotime($order['timeUntil']));
        }
    }
    
	$siteName = $order['siteName'];
	if(!$siteName)
		$siteName = udb::single_value("SELECT `siteName` FROM `sites` WHERE `siteID` = " . $order['siteID']);

    $order['extraRooms'] = [];
    if ($order['treatmentsNames'] && $order['extras']){
        $temp = json_decode($order['extras'], true);
        if ($temp['extras'])
            $order['extraRooms'] = udb::single_column("SELECT `extraName` FROM `treatmentsExtras` WHERE `extraID` IN (" . implode(',', array_keys($temp['extras'])) . ") AND `extraType` = 'rooms'");
    }

?>
	<div class="item box-order order<?=($order['price'] <= $order['paid'] ? " allpaid" : '')?><?=$order['status']?"":" canceled"?><?=$order['approved']?" approved":""?><?=$order['parentOrder']?" isSpa":""?> new" data-orderid="<?=$order['orderID']?>" data-orderidbysite="<?=$order['orderIDBySite']?>">
		<input type="hidden" class="guid" value="<?=$order['guid']?>">
<?if($order['sourceID']){
    ?>
			<div class="domain-icon <?=$order['sourceID']?>" title='<?=$order['sourceID']?>' style="background-color: <?=$sourcesArray[$order['sourceID']]['hexColor']?>"><?=$sourcesArray[$order['sourceID']]['letterSign']?></div>
		<?}else{?>
		<div class="domain-icon" style="background-image:url(<?=$domain_icon[$order['domainID']]?>)"></div>
		<?}?>
		<span class="id"><?=$order['orderIDBySite']?> <?=$multiCompound? " - <b style='color:#0dabb6'>".$order['siteID']."</b>" : ""?></span>
<?php
    if($_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){
?>
		<?if(!$order['parentOrder']){?>
		<div  class="c_status c_s<?=$order['client_status']?>" onclick="change_c_s($(this))"></div>
		<?}?>
		<div class="l">
			<span class="date"><?=date('d.m.y', strtotime($order['create_date']))?></span>
			<?if(!$_CURRENT_USER->is_spa()){?>
				<span class="sign <?=$order['signature']?"yes":"no"?>"><?php if($order['signature']) { ?>חתום<?php } ?><?php if($order['adminApproved']) { ?><?php if(!$order['signature']) { ?>מאושר<?php } ?><?php } ?><?php if(!$order['signature'] && !$order['adminApproved']) { ?>לא חתום<?php } ?></span>
			<?}?>
		</div>
<?php
    }
?>
		<ul class="f">
			<li class="order-data">
				<i class="<?=(($order["orderType"]=="preorder")? "preorder" : ($order['approved']? "signed" : "notSigned")) ?>"></i>
				<div>
					<div class="name customerName"><?=$order['customerName']?></div>
<?php
    if ($order['timeFrom'] && strcmp($order['timeFrom'], '0000-00-00 00:00:00')){
        if(substr($order['timeFrom'],0,10) == substr($order['timeUntil'],0,10) || !$order['treatmentsNames']){
?>
					<div class="days">יום <?=$weekday[date('w', strtotime($order['timeFrom']))]?>: <?=date('d.m.y', strtotime($order['timeFrom']))?>
<?php
					if(!$order['treatmentsNames']){
?>
					בשעה <?=date('H:i', strtotime($order['timeFrom']))?>
<?php
					}else{
?>
					בשעות <?=date('H:i', strtotime($order['timeFrom']))?> - <?=date('H:i', strtotime($order['timeUntil']))?>
<?php				
					}
?>
					</div>
<?php
        } else {
?>
					<div class="days"><? if ($order['timeFrom']) { ?>ימים <?=$weekday[date('w', strtotime($order['timeFrom']))]?> - <?=$weekday[date('w', strtotime($order['timeUntil']))]?>: <?=date('d.m.y', strtotime($order['timeFrom']))?> - <?=date('d.m.y', strtotime($order['timeUntil']))?><? } ?></div>
<?php
        }
    }
    else
        echo '<div class="days"></div>';
?>
				</div>
			</li>
			<li class="phone">
				<i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 20" width="21" height="20"><path class="shp0" d="M5.4 13C7.2 15.1 9.3 16.8 11.7 18 12.7 18.4 13.9 18.9 15.3 19 15.3 19 15.4 19 15.5 19 16.4 19 17.2 18.7 17.8 18 17.8 18 17.8 18 17.8 18 18 17.8 18.3 17.5 18.5 17.3 18.7 17.1 18.9 16.9 19 16.8 19.8 15.9 19.8 14.9 19 14.1L16.8 11.9C16.4 11.5 16 11.3 15.5 11.3 15 11.3 14.6 11.5 14.2 11.9L12.8 13.2C12.7 13.1 12.6 13 12.5 13 12.3 12.9 12.2 12.8 12.1 12.8 10.9 12 9.8 11 8.7 9.7 8.2 9 7.8 8.5 7.6 7.9 7.9 7.6 8.3 7.3 8.6 6.9 8.7 6.8 8.8 6.7 8.9 6.6 9.3 6.2 9.5 5.7 9.5 5.3 9.5 4.8 9.3 4.3 8.9 3.9L7.8 2.8C7.7 2.7 7.6 2.6 7.4 2.4 7.2 2.2 6.9 1.9 6.7 1.7 6.3 1.3 5.9 1.1 5.4 1.1 4.9 1.1 4.5 1.3 4.1 1.7L2.7 3.1C2.2 3.6 1.9 4.2 1.8 4.9 1.8 5.8 1.9 6.7 2.3 7.9 3 9.6 4 11.3 5.4 13ZM2.7 5C2.8 4.5 3 4.1 3.3 3.7L4.7 2.3C4.9 2.1 5.1 2 5.4 2 5.6 2 5.8 2.1 6 2.3 6.3 2.6 6.5 2.8 6.8 3.1 6.9 3.2 7 3.3 7.2 3.5L8.3 4.6C8.5 4.8 8.6 5 8.6 5.3 8.6 5.5 8.5 5.7 8.3 6 8.2 6.1 8 6.2 7.9 6.3 7.6 6.7 7.3 7 6.9 7.3 6.9 7.3 6.9 7.3 6.9 7.3 6.6 7.6 6.6 7.9 6.7 8.1 6.7 8.2 6.7 8.2 6.7 8.2 7 8.9 7.4 9.5 8 10.3 9.1 11.7 10.3 12.7 11.6 13.5 11.7 13.6 11.9 13.7 12.1 13.8 12.2 13.9 12.4 14 12.5 14 12.5 14 12.5 14 12.5 14.1 12.6 14.1 12.8 14.1 12.9 14.1 13.2 14.1 13.4 14 13.4 13.9L14.8 12.5C15 12.3 15.3 12.2 15.5 12.2 15.8 12.2 16 12.4 16.1 12.5L18.4 14.7C18.8 15.2 18.8 15.7 18.4 16.1 18.2 16.3 18.1 16.5 17.9 16.6 17.6 16.9 17.3 17.1 17.1 17.4 16.7 17.9 16.2 18.1 15.5 18.1 15.5 18.1 15.4 18.1 15.3 18.1 14.1 18 13 17.5 12.1 17.1 9.8 16 7.8 14.4 6.1 12.4 4.7 10.8 3.8 9.2 3.2 7.6 2.8 6.5 2.7 5.7 2.7 5Z"></path></svg></i>
				<a href="tel:<?=$order['customerPhone']?>"><?=$order['customerPhone']?></a>
			</li>
			<li class="email">
				<i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><defs><image width="16" height="16" id="img-at" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABhElEQVQ4T4VT0W3CQAz1KSxARwgj0BFgBBghRiwAI8ACET42gBGaEWAEMkKZ4OLqpfYpREU9KR/x2c/vPfsCvTmn02kXQlgQEb7haVS12Ww2RwTDuP58Ps+7rhMimhNRVNUWyRavLI67hpmXLwCW9EVE95TServdPv8iaOwORMQZoK7raVEUNxQz8/qdNI+LyIOInhnAUHcppRk6j6Sg7kpEqxDCsqqqRkQukNMDWPeHqh4Hel0KTyaTSlV3yE0pfaCBiMCnRQ8gIisiulj3VkRQTDBpTJmZP60GDMoeIMZ4UNUVM8/qui6LooC+NTODdn9Mc/ZHRG4hhMYZwLwW5o3ZDCR+q+oeEk1y/+8A+On1+4hcjnXH/GVgYJYcHO1XMkdn4N1ijAtVhd4pYl3XRRs3GC8zgBdYR5iYVziEcFTVEmM0S3zR2izBFii7/t8i+b0DYKbQ+eL8GMT8KVNKe19zXySMDrRBMw4TAGKFoI9H1Hv1wmAwKjBxnUMCeFTXMTASfgCw6CBIVKDU0AAAAABJRU5ErkJggg=="></image></defs><use id="@" href="#img-at" transform="matrix(1,0,0,1,0,0)"></use></svg></i>
				<a href="mailto:<?=$order['customerEmail']?>"><?=$order['customerEmail']?></a>
			</li>
			<??>
			<li class="rooms">
				<i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 22" width="24" height="22"><path class="shp0" d="M21.8 9.1L12.3 2.1C12.1 2 11.9 2 11.7 2.1L2.2 9.1C2 9.3 1.9 9.6 2.1 9.8 2.3 10.1 2.6 10.1 2.8 10L12 3.2 21.2 10C21.3 10 21.4 10.1 21.5 10.1 21.6 10.1 21.8 10 21.9 9.8 22.1 9.6 22 9.3 21.8 9.1ZM19.3 10.1C19 10.1 18.8 10.3 18.8 10.6L18.8 19 14.6 19 14.6 14.4C14.6 13 13.4 11.8 12 11.8 10.6 11.8 9.4 13 9.4 14.4L9.4 19 5.2 19 5.2 10.6C5.2 10.3 5 10.1 4.7 10.1 4.4 10.1 4.2 10.3 4.2 10.6L4.2 19.5C4.2 19.8 4.4 20 4.7 20L9.9 20C10.2 20 10.4 19.8 10.4 19.5 10.4 19.5 10.4 19.5 10.4 19.5L10.4 14.4C10.4 13.5 11.1 12.8 12 12.8 12.9 12.8 13.6 13.5 13.6 14.4L13.6 19.5C13.6 19.5 13.6 19.5 13.6 19.5 13.6 19.8 13.8 20 14.1 20L19.3 20C19.6 20 19.8 19.8 19.8 19.5L19.8 10.6C19.8 10.3 19.6 10.1 19.3 10.1Z"></path></svg></i>
				<div>
					<div class="name"> <?=$multiCompound? "<b style='color:#222'>".$order['siteName'].":</b>" : ""?> <?=$order['unitNames']?></div>
					<div class="people"><?=$order['adults']? $order['adults']."מבוגרים " : "" ?> <?=$order['kids']? ", ".$order['kids']." ילדים" : "" ?> <?=$order['babies']? ", ".$order['babies']." תינוקות" : "" ?>
					<?=trim($order['treatmentsNames'] . ', ' . ($order['extraRooms'] ? '<b>' . implode('</b>, <b>', $order['extraRooms']) . '</b>' : ''), ', ')?>
					</div>
				</div>
			</li>
			<?if($order['comments_owner'] || $order['comments_customer']){?>
			<li class="comments">
				<i><svg height="511pt" viewBox="0 -25 511.99911 511" width="511pt" xmlns="http://www.w3.org/2000/svg"><path d="m504.292969 415.507812c-.496094-.28125-46.433594-26.347656-79.050781-62.386718 23.070312-36.648438 35.210937-78.714844 35.210937-122.394532 0-61.496093-23.949219-119.3125-67.433594-162.796874-43.484375-43.480469-101.300781-67.429688-162.792969-67.429688-61.496093 0-119.3125 23.949219-162.792968 67.429688-43.484375 43.484374-67.433594 101.300781-67.433594 162.796874 0 61.492188 23.949219 119.308594 67.433594 162.792969 43.480468 43.484375 101.296875 67.429688 162.792968 67.429688 39.25 0 77.6875-9.96875 111.75-28.902344 67.128907 37.320313 155.273438 12.277344 159.140626 11.148437 5.839843-1.707031 10.089843-6.746093 10.78125-12.789062.695312-6.046875-2.300782-11.914062-7.605469-14.898438zm-153.925781-13.6875c-4.878907-3.1875-11.160157-3.28125-16.136719-.246093-31.242188 19.0625-67.207031 29.140625-104.003907 29.140625-110.273437 0-199.992187-89.714844-199.992187-199.988282 0-110.277343 89.71875-199.992187 199.992187-199.992187 110.273438 0 199.988282 89.714844 199.988282 199.992187 0 41.382813-12.535156 81.097657-36.257813 114.847657-3.878906 5.519531-3.632812 12.945312.609375 18.191406 18.769532 23.238281 42.988282 43.035156 62.273438 56.886719-30.085938 3.28125-73.347656 2.789062-106.472656-18.832032zm0 0"/><path d="m332.714844 282.808594h-204.976563c-8.351562 0-15.117187 6.769531-15.117187 15.117187 0 8.347657 6.765625 15.117188 15.117187 15.117188h204.976563c8.347656 0 15.117187-6.769531 15.117187-15.117188 0-8.347656-6.769531-15.117187-15.117187-15.117187zm0 0"/><path d="m332.714844 215.609375h-204.976563c-8.351562 0-15.117187 6.769531-15.117187 15.121094 0 8.347656 6.765625 15.117187 15.117187 15.117187h204.976563c8.347656 0 15.117187-6.769531 15.117187-15.117187 0-8.351563-6.769531-15.121094-15.117187-15.121094zm0 0"/><path d="m332.714844 148.414062h-204.976563c-8.351562 0-15.117187 6.769532-15.117187 15.117188 0 8.351562 6.765625 15.117188 15.117187 15.117188h204.976563c8.347656 0 15.117187-6.765626 15.117187-15.117188 0-8.347656-6.769531-15.117188-15.117187-15.117188zm0 0"/></svg></i>
				<div class="commentsText"><?=nl2br($order['comments_customer']).(($order['comments_owner'] && $order['comments_customer'])? " | " : "" ).nl2br($order['comments_owner'])?></div>
			</li>
			<?}?>
		</ul>
		<?if(!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){
		$que = "SELECT health_declare.guid , clientName FROM orders LEFT JOIN health_declare USING (orderID) WHERE orders.parentOrder = ".$order['orderID']." AND health_declare.guid IS NOT NULL";
		echo $que;
		$healthdeclare = udb::single_row($que);
			if($healthdeclare){?>
		<ul>
            <li><i><svg style='fill:#0dabb6;enable-background:new 0 0 512 512;' version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512"  xml:space="preserve"><path d="M430.584,0H218.147v144.132c0,9.54-7.734,17.274-17.274,17.274H56.741v325.917c0,13.628,11.049,24.677,24.677,24.677h349.166c13.628,0,24.677-11.049,24.677-24.677V24.677C455.261,11.049,444.212,0,430.584,0z M333.321,409.763H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,409.763,333.321,409.763z M333.321,328.502H192.675c-9.54,0-17.274-7.734-17.274-17.274c0-9.54,7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274C350.595,320.768,342.861,328.502,333.321,328.502zM333.321,247.243H192.675c-9.54,0-17.274-7.734-17.274-17.274s7.734-17.274,17.274-17.274h140.646c9.54,0,17.274,7.734,17.274,17.274S342.861,247.243,333.321,247.243z"></path><path d="M183.389,0c-6.544,0-12.82,2.599-17.448,7.229L63.968,109.198c-4.628,4.628-7.229,10.904-7.229,17.448v0.211h126.86V0H183.389z"></path></svg></i>
			<div><a href="/health/<?=$order['siteID']?>/<?=$healthdeclare['guid']?>" target="_blank" style="text-decoration:underline" >הצהרת בריאות - <?=$healthdeclare['clientName']?></a></div></li>
		</ul>
		<?}
		}else{?>
		<ul>
			<li class="send  <?=($order['approved'] || $order['status']!=1)?"approved":""?>">

				<?php
				$link = WEBSITE . "signature".($_CURRENT_USER->is_spa()? "2" : "").".php?guid=".$order['guid'];
				if(!$order['approved'] && $order['status']==1){

					$subject = rawurlencode("טופס לאישור הזמנה ב". $siteName ." בתאריך".date('d.m.y', strtotime($order['timeFrom'])));
					$body = rawurlencode($order['customerName'].' שלום, על מנת לאשר את הזמנתך ב'.$siteName." ".$date_n_day.' יש ללחוץ על הקישור הבא '.$link);

				}else{
					$subject = rawurlencode("יצירת קשר בנוגע להזמנה ב". $siteName ." בתאריך".date('d.m.y', strtotime($order['timeFrom'])));
					$body = rawurlencode($order['customerName'].' שלום, '.(($order['approved'] && $order['status']==1)? "מצורף קישור לטופס ההזמנה שלך ".$link : ""));
				}
				?>

				<i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 19 19" width="19" height="19"><path id="Forma 1" class="shp0" d="M15.66 12.34C14.79 11.47 13.75 10.83 12.62 10.44 13.83 9.6 14.62 8.2 14.62 6.62 14.62 4.07 12.55 2 10 2 7.45 2 5.37 4.07 5.37 6.62 5.37 8.2 6.17 9.6 7.38 10.44 6.25 10.83 5.21 11.47 4.34 12.34 2.83 13.85 2 15.86 2 18L3.25 18C3.25 14.28 6.28 11.25 10 11.25 13.72 11.25 16.75 14.28 16.75 18L18 18C18 15.86 17.17 13.85 15.66 12.34ZM10 10C8.14 10 6.62 8.49 6.62 6.62 6.62 4.76 8.14 3.25 10 3.25 11.86 3.25 13.37 4.76 13.37 6.62 13.37 8.49 11.86 10 10 10Z"></path></svg></i>
				<div>
					<?if($order["orderType"]=="preorder" || !$order["status"]){
						$oType = ($order["orderType"]=="preorder")? "שיריון מקום" : "הזמנה";
						if(!$order["status"]){
							$canceled = $order["orderType"]=="preorder"? "מבוטל" : "מבוטלת";
						}
					?>
						<div class="preorderText">

							<?=$oType?> <?=$canceled?><br>
							<?=($order["orderType"]=="preorder")? "לא ניתן לשלוח לאישור" : "ניתן לשחזר" ?>
						</div>
						<?if(!$order["status"]){?>
							<div class="restore"><svg id="Capa_1" enable-background="new 0 0 497.883 497.883" height="512" viewBox="0 0 497.883 497.883" width="512" xmlns="http://www.w3.org/2000/svg"><path d="m435.647 155.588-62.235 93.353h31.118c0 85.786-69.802 155.588-155.588 155.588-52.788 0-99.368-26.561-127.511-66.883l-36.282 54.424c39.959 45.668 98.487 74.694 163.793 74.694 120.11 0 217.823-97.714 217.823-217.823h31.118z"/><path d="m93.353 248.941c0-85.786 69.802-155.588 155.588-155.588 52.788 0 99.368 26.561 127.511 66.883l36.282-54.423c-39.959-45.668-98.487-74.694-163.793-74.694-120.11 0-217.823 97.714-217.823 217.823h-31.118l62.235 93.353 62.235-93.353z"/></svg>שחזר</div>
						<?}else{?>
							<div class="createOrder">הפוך להזמנה</div>
						<?}?>


					<?}else if(!$_CURRENT_USER->is_spa()){?>
					<span class="tat"><?=(!$order['approved'] && $order['status']==1)?"שליחה לחתימה":"יצירת קשר"?></span>
					<?php if($order['customerPhone']){?>
					<a href="<?=whatsappBuild($order['customerPhone'],$body)?>" target="_blank"><span class="icon whatsapp" data-phone="<?=$order['customerPhone']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></span></a>

					<a href="sms:<?=$order['customerPhone']?>?&body=<?=$body?>" target="_blank" class="a_sms"><span class="icon sms" data-sms="<?=$order['customerPhone']?>">
						<img src="/user/assets/img/icon_sms.png" alt="sms">
					</span></a>
					<?php } ?>
					<?php if($order['customerEmail']){?>

					<a href="mailto:<?=$order['customerEmail']?>?subject=<?=$subject?>&body=<?=$body?>" target="_blank"><span class="icon mail" data-mail="<?=$order['customerEmail']?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 27" width="32" height="27"><style>.message-ic{fill:#fff}</style><path class="message-ic" d="M29.9 25L24.1 19.4 23.5 18.8 23 18.3 22.6 17.8 22.1 17.4 24.9 15.5 29.8 12 30 11.9 30 14 30 17.2 30 25 29.9 25ZM22.2 6L5 10.9 5.7 13.3 2.1 10.7 2.8 7.8C4.1 7 12.5 2.1 15.9 2 19.3 2.1 27.7 7 29 7.8L29.9 10.7 24.7 14.5 22.2 6ZM6.2 14.8L9.7 17.4 9 18.1 8.5 18.6 8.1 19.1 7.5 19.6 2.1 25 2 25 2 13.1 2 12 2 11.9 2.7 12.4 6.2 14.8ZM9.5 19L10 18.5 10.4 18.1 10.8 17.7 11.2 17.2 11.3 17.2C11.4 17.1 11.6 16.9 11.8 16.8 12.9 15.8 14.4 15.3 15.9 15.3 17.4 15.3 18.9 15.8 20 16.8 20.2 16.9 20.3 17 20.5 17.2L20.6 17.3 21.1 17.7 21.2 17.9 21.7 18.3 22.2 18.8 23 19.7 28.4 25 3.5 25 7.8 20.7 9.5 19Z"></path></svg></span></a>

					<?}}?>
					<?if($order["status"]>1){?>
					<span class="icon plusSend" data-msg="<?=$body?>" data-subject="<?=$subject?>"></span>
					<?}?>

					<?php if($order['status']) { ?>
						<div class="o-price">
							<span class="orderPrice new">
								<i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 20" width="22" height="20"><path d="M20.51 2.49C20.18 2.16 19.79 2 19.33 2L2.67 2C2.21 2 1.82 2.16 1.49 2.49 1.16 2.81 1 3.21 1 3.67L1 16.33C1 16.79 1.16 17.19 1.49 17.51 1.82 17.84 2.21 18 2.67 18L19.33 18C19.79 18 20.18 17.84 20.51 17.51 20.84 17.19 21 16.79 21 16.33L21 3.67C21 3.21 20.84 2.81 20.51 2.49ZM19.67 16.33C19.67 16.42 19.63 16.5 19.57 16.57 19.5 16.63 19.42 16.67 19.33 16.67L2.67 16.67C2.58 16.67 2.5 16.63 2.43 16.57 2.37 16.5 2.33 16.42 2.33 16.33L2.33 10 19.67 10 19.67 16.33ZM19.67 6L2.33 6 2.33 3.67C2.33 3.58 2.37 3.5 2.43 3.43 2.5 3.37 2.58 3.33 2.67 3.33L19.33 3.33C19.42 3.33 19.5 3.37 19.57 3.43 19.63 3.5 19.67 3.58 19.67 3.67L19.67 6 19.67 6ZM3.67 14L6.33 14 6.33 15.33 3.67 15.33 3.67 14ZM7.67 14L11.67 14 11.67 15.33 7.67 15.33 7.67 14Z"></path></svg></i>
								<span>₪<?=number_format($order['price'])?><span>(₪<?=number_format($order['paid'])?>)</span></span>
							</span>
						</div>
					<?php } else { ?>
						<span class="orderPrice">₪<?=$order['price']?></span>
					<?php } ?>
				</div>
			</li>
		</ul>
		<?if(!$order["status"] && !$siteData['blockDelete'] || $order["status"]){?>
		<div class="deleteOrder">			
			<?=!$order["status"]? "למחיקה מוחלטת" :"לביטול הזמנה"?> לחצו על האייקון <a href="#"><svg height="427pt" viewBox="-40 0 427 427.00131" width="427pt" xmlns="http://www.w3.org/2000/svg"><path d="m232.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"/><path d="m114.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"/><path d="m28.398438 127.121094v246.378906c0 14.5625 5.339843 28.238281 14.667968 38.050781 9.285156 9.839844 22.207032 15.425781 35.730469 15.449219h189.203125c13.527344-.023438 26.449219-5.609375 35.730469-15.449219 9.328125-9.8125 14.667969-23.488281 14.667969-38.050781v-246.378906c18.542968-4.921875 30.558593-22.835938 28.078124-41.863282-2.484374-19.023437-18.691406-33.253906-37.878906-33.257812h-51.199218v-12.5c.058593-10.511719-4.097657-20.605469-11.539063-28.03125-7.441406-7.421875-17.550781-11.5546875-28.0625-11.46875h-88.796875c-10.511719-.0859375-20.621094 4.046875-28.0625 11.46875-7.441406 7.425781-11.597656 17.519531-11.539062 28.03125v12.5h-51.199219c-19.1875.003906-35.394531 14.234375-37.878907 33.257812-2.480468 19.027344 9.535157 36.941407 28.078126 41.863282zm239.601562 279.878906h-189.203125c-17.097656 0-30.398437-14.6875-30.398437-33.5v-245.5h250v245.5c0 18.8125-13.300782 33.5-30.398438 33.5zm-158.601562-367.5c-.066407-5.207031 1.980468-10.21875 5.675781-13.894531 3.691406-3.675781 8.714843-5.695313 13.925781-5.605469h88.796875c5.210937-.089844 10.234375 1.929688 13.925781 5.605469 3.695313 3.671875 5.742188 8.6875 5.675782 13.894531v12.5h-128zm-71.199219 32.5h270.398437c9.941406 0 18 8.058594 18 18s-8.058594 18-18 18h-270.398437c-9.941407 0-18-8.058594-18-18s8.058593-18 18-18zm0 0"/><path d="m173.398438 154.703125c-5.523438 0-10 4.476563-10 10v189c0 5.519531 4.476562 10 10 10 5.523437 0 10-4.480469 10-10v-189c0-5.523437-4.476563-10-10-10zm0 0"/></svg></a>
		</div>
		<?}?>
		<?}?>
	</div>

<?php
}

function orderCompLine($order,$nextCnt){
    global $weekday, $month_name, $nextLimitTop, $extrasNames, $_CURRENT_USER;

	$multiCompound = !$_CURRENT_USER->single_site;
    if($_CURRENT_USER->is_spa() && $order['parentOrder']) {

        $order['extrasText'] ='';
        $que = "SELECT SUM(`sum`) AS `paidTotal` , orders.extras
		FROM orders 
		LEFT JOIN `orderPayments` ON (`orderPayments`.`orderID` = orders.`orderID` AND `complete` = 1 AND `subType` NOT IN ('card_test', 'freeze_sum') AND `cancelled` = 0)
		WHERE orders.`orderID` = " . $order['parentOrder']." 
		GROUP BY orderPayments.orderID";
        $extraData = udb::single_row($que);
        $order['paidTotal'] = $extraData['paidTotal'];
        $exx = json_decode($extraData['extras'], true) ;
        if(is_array($exx)){
            foreach($exx['extras']  as $key => $ex){
                if($extrasNames[$key]) 
					$order['extrasText'].="<span>".($ex['count']>1? $ex['count']." - " : "" ). $extrasNames[$key]."</span>" ;
            }
        }
    }
?>
	<?
//		echo "aaa".$_CURRENT_USER->sites(true);
//		echo "<div style='display:none'>".print_r($exx)."</div>";
//		echo "<div style='display:none'>".print_r($order)."</div>";
//		echo "<div style='display:none'>".print_r($extrasNames)."</div>";

	?>
    <div class="item order day_event_line lineComp<?=$nextCnt?> <?=$order['paidTotal']>= $order['priceTotal']? "allpaid " : ""?>" <?=$nextCnt>$nextLimitTop? "style='display:none'" : ""?> data-showorder="<?=$nextCnt?>" data-orderid="<?=$order['orderID']?>" data-orderidbysite="<?=$order['orderIDBySite']?>">
		<div class="time f">
			<div class="c_status c_s<?=$order['client_status']?>"></div>
			<?if($_CURRENT_USER->is_spa() && $order['parentOrder']) {?>
			<div class="day_line_gender"><?=$order['treatmentClientSex']== 1? "👨🏻" : "👩🏼";?></div>
			<?}?>
			<?=date('H:i', strtotime($order['timeFrom']))?>
		</div>
		<div class="details f">
			<div><?=$order['customerName']?> - <?=$multiCompound? "<b style='color:#222'>".$order['siteName'].":</b>" : ""?> <?=$order['unitNames']?> <?=$order['helthDelare']? "<span class='V ".($order['h_negatives']? "semi": "")."'></span>" : ""?></div>
			<?if($order['parentOrder']>0 && $order['orderID']!=$order['parentOrder']){?>
			<div><?=$order['treatmentName']?> <?=$order['treatmentLen']?> דקות <b><?=$order['therapistName']?></b></div>
                <div class='pextras'><?=$order['extrasText']?></div>
			<?}else{?>
			<div>עזיבה ביום <?=$weekday[date('w', strtotime($order['timeUntil']))]?>, <?=date('d', strtotime($order['timeUntil']))?> ב<?=$month_name[intval(date('m', strtotime($order['timeUntil'])))]?></div>
			<?}?>
		</div>
        <div style="max-width:70px;display:table-cell;text-align:left;width:70px">
            <?php if($_CURRENT_USER->is_spa() && $_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)) {  ?>
            <ul>
				<li class="send" style="margin:0"><div style="max-width:none">
					<div class="o-price">
					<span class="orderPrice new" style="padding:0 5px;text-align:center" data-pay="<?=$order['parentOrder']?>">
						<span>
							<div style="font-size:12px"> ₪<?=number_format($order['price'])?></div>
							<div style="font-size:14px; color:black;margin-top:5px"> ₪<?=number_format($order['priceTotal'])?></div>
							<span>(₪<?=number_format($order['paidTotal'])?>)</span>
						</span>
					</span>
                    </div>
					</div>
				</li>
			</ul>
			<?php }  ?>
        </div>
	</div>
<?php
}

function updateOrderAdvance($orderID){
    $advance = udb::single_value("SELECT SUM(`sum`) FROM `orderPayments` WHERE `subType` = 'advance' AND `complete` = 1 AND `cancelled` = 0 AND (`cancelData` IS NULL OR `cancelData` = '') AND `orderID` = " . $orderID);
    if ($advance)
        udb::update('orders', ['advance' => $advance], '`orderID` = ' . $orderID);
}

function getTotalPayment($orderID){
    $que = "SELECT SUM(`sum`) AS `sum`, `orderID` FROM `orderPayments` WHERE `complete` = 1 AND `cancelled` = 0 AND `subType` NOT IN ('card_test', 'freeze_sum') AND `orderID` IN (" . $orderID . ") GROUP BY `orderID` ORDER BY NULL";
    return (is_array($orderID) ? udb::key_value($que, 'orderID', 'sum') : udb::single_value($que)) ?: 0;
}

//function updateParentOrderPrice($parent){
//    $rooms  = udb::single_value("SELECT SUM(`base_price`) AS `price` FROM `orderUnits` WHERE `orderID` = " . $parent);
//    $treats = $parent ? udb::single_value("SELECT SUM(`price`) AS `price` FROM `orders` WHERE `orderID` <> " . $parent . " AND `parentOrder` = " . $parent) : 0;
//
//    udb::update('orders', ['price' => ($rooms ?: 0) + ($treats ?: 0)], '`orderID` = ' . $parent);
//}

function blockAccessMsg($msg = 'Access denied'){
    return '<h2>' . $msg . '</h2>';
}


function prettySubsNumber($num, $d = '-'){
    return trim(chunk_split($num, 3, $d), $d);
}
