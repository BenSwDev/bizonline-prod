<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */

function js_safe($str, $replace = ''){
    $base = ['"' => '&quot;', "'" => '&#039;'];
    return strtr($str, $replace ? (is_array($replace) ? $replace : [$replace => $base[$replace]]) : $base);
}

if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$siteID = $_CURRENT_USER->active_site() ?: 0;

$domainID = 1;
$_langID  = (intval($_GET['langval']) % 3) ?: 1;

$settlements = udb::key_row("SELECT s.settlementID, s.areaID, IFNULL(t.TITLE, s.TITLE) AS `TITLE` FROM `settlements` AS `s` LEFT JOIN `settlements_text` AS `t` ON (s.settlementID = t.settlementID AND t.LangID = " . $_langID . ") WHERE 1 ORDER BY `TITLE`", 'settlementID');

//$siteID =  intval($_POST['site']) ?: intval($_GET['site']) ?: $_CURRENT_USER->active_site();
if ('POST' == $_SERVER['REQUEST_METHOD']) {
    $isError = '';
    $data = typemap($_POST, [
        'phoneOnOrder'          => 'string',
        'addressOnOrder'        => 'string',
        'topCommentsOnOrder'    => 'text',
        'cancelTermsOnOrder'    => 'text',
        'bottomCommentsOnOrder' => 'text',
        'reviewLocation' => 'text',
        'siteName' => 'string',
        'address'  => 'string',
        'city'     => 'int',
        'gpsLat'   => 'float',
        'gpsLong'  => 'float'
    ]);

//    $siteData = [
//        'phoneOnOrder'   => $data['phoneOnOrder'],
//        'addressOnOrder'      => $data['addressOnOrder'],
//        'topCommentsOnOrder'      => $data['topCommentsOnOrder'],
//        'cancelTermsOnOrder'      => $data['cancelTermsOnOrder'],
//        'bottomCommentsOnOrder'      => $data['bottomCommentsOnOrder']
//    ];

    $site = [
        'settlementID' => $data['city'],
        'gpsLat'       => $data['gpsLat'],
        'gpsLong'      => $data['gpsLong'],
    ];

    $sitesLang = [
        'siteID'                => $siteID,
        'domainID'              => 1,
        'langID'                => $_langID,
        'siteName'              => $data['siteName'],
        'reviewLocation'        => $data['reviewLocation'],
        'phoneOnOrder'          => $data['phoneOnOrder'],
        'addressOnOrder'        => $data['addressOnOrder'],
        'topCommentsOnOrder'    => $data['topCommentsOnOrder'],
        'cancelTermsOnOrder'    => $data['cancelTermsOnOrder'],
        'bottomCommentsOnOrder' => $data['bottomCommentsOnOrder'],
        'address'               => $data['address']
    ];

//    udb::update("sites_domains",$siteData," domainID=".$domainID." and siteID=".$siteID);
    udb::update("sites", $site, "`siteID` = " . $siteID);
    udb::insert("sites_langs", $sitesLang, true);
}

$sql = "SELECT siteName, phoneOnOrder, addressOnOrder, topCommentsOnOrder, cancelTermsOnOrder, bottomCommentsOnOrder, address,reviewLocation FROM sites_langs WHERE domainID = 1 AND langID = " . $_langID . " AND siteID = " . $siteID;
$langData = udb::single_row($sql);
$siteData = udb::single_row("SELECT * FROM sites WHERE siteID = " . $siteID);
//$siteAddress['address'] = udb::single_value("select address from sites_langs where siteID=".$siteID . " and langID=1 LIMIT 1");
?>
<div class="create_order" style="max-width:800px;margin:0 auto;position:relative;right:auto;left:auto;background:none;font-size:0">
<div style="border-bottom:1px #ccc solid;margin-bottom:10px">
	<div class="inputWrap half">
		<select name="langval" title="שינויים בשפה" onchange="location.href='?page=orderTexts&langval='+this.value">
			<option value="1">עברית</option>
			<option value="2" <?=($_langID == 2 ? 'selected' : '')?>>אנגלית</option>
		</select>
		<label for="city">שינויים בשפה</label>
	</div>
</div>

<form method="post" class="agreements">
    <input type="hidden" name="asite" value="<?=$siteID?>" />
    <div class="inputWrap half">
        <input type="text" name="siteName" id="siteName" value="<?=($langData['siteName'] ?: $siteData['siteName'])?>" />
        <label for="siteName">שם העסק על הזמנה</label>
    </div>
	<div class="inputWrap half">
		<input type="text" name="phoneOnOrder" id="phoneOnOrder" value="<?=$langData['phoneOnOrder']?>" />
		<label for="phoneOnOrder">טלפון שיופיע בהזמנה</label>
	</div>
	<div class="inputWrap half">
		<input type="text" name="addressOnOrder" id="addressOnOrder" value="<?=$langData['addressOnOrder']?>" />
		<label for="addressOnOrder">כתובת על הזמנה</label>
	</div>

    <!-- new code Gal -->
    <div class="inputWrap half">
        <select name="city" id="city" title="ישוב">
            <option value="0">- - בחר ישוב - -</option>
<?php
    foreach($settlements as $settlement)
        echo '<option value="' , $settlement['settlementID'] , '" data-area="' , $settlement['areaID'] , '" ' , ($settlement['settlementID'] == $siteData['settlementID'] ? 'selected' : '') , '>' , $settlement['TITLE'] , '</option>';

    $cityName = $settlements[$siteData['settlementID']]['TITLE'] ?? '';
?>
        </select>
        <label for="city">ישוב</label>
    </div>
    <div class="inputWrap half">
        <input type="text" placeholder="" name="address" id="address" value="<?=js_safe($langData['address'])?>" />
        <label for="address">כתובת</label>
    </div>
<?php
    $didNotHaveLatLng = false;
    $useAddress = $langData['address'];
    if($siteID && (!$siteData['gpsLat'] || !$siteData['gpsLong']) && $cityName) {
        $searchAddress =  ($useAddress ? $useAddress .", " : '') . $cityName;
        if($searchAddress && $cityName) {
            $didNotHaveLatLng = true;
            $latlng = getLocationNumbers($searchAddress);
            $siteData['gpsLat'] = $latlng['lat'];
            $siteData['gpsLong'] = $latlng['long'];
            $didNotHaveLatLng = true;
        }
    }
?>
    <div class="inputWrap half"><a id="getcoords">משוך קורדינטות</a></div>
    <div class="inputWrap half">
        <input type="text" placeholder="Lat" name="gpsLat" value="<?=$siteData['gpsLat']?>" />
        <label for="gpsLat">GPS Lat</label>
    </div>
    <div class="inputWrap half">
        <input type="text" placeholder="Long" name="gpsLong" value="<?=$siteData['gpsLong']?>" />
        <label for="gpsLong">GPS Long</label>
    </div>
    <!-- new code Gal -->

	<div class="inputWrap textarea">
		<textarea type="text" name="topCommentsOnOrder" id="topCommentsOnOrder" ><?=$langData['topCommentsOnOrder']?></textarea>
		<label for="topCommentsOnOrder">הערות חלק עליון</label>
	</div>
	<div class="inputWrap textarea">
		<textarea type="text" name="cancelTermsOnOrder" id="cancelTermsOnOrder" ><?=$langData['cancelTermsOnOrder']?></textarea>
		<label for="cancelTermsOnOrder">תנאי ביטול</label>
	</div>
	<div class="inputWrap textarea">
		<textarea type="text" name="bottomCommentsOnOrder" id="bottomCommentsOnOrder" ><?=$langData['bottomCommentsOnOrder']?></textarea>
		<label for="bottomCommentsOnOrder">הערות תחתונות</label>
	</div>
    <div class="inputWrap textarea">
        <textarea type="text" name="reviewLocation" id="reviewLocation" ><?=$langData['reviewLocation']?></textarea>
        <label for="reviewLocation">אודות המתחם</label>
    </div>
    <input type="submit" value="שמירה" class="not-empty">
</form>
</div>
<script>
    function getCoords(){
        let searchaddress = "";
        searchaddress = $("select[name='city'] option:selected").text();
        if(!searchaddress) {
            return Swal.fire({icon:'error', text:'יש לבחור יישוב'});
        }
        if(!$("#address").val()) {
            return Swal.fire({icon:'error', text:'יש למלא כתובת'});
        }
        searchaddress += "," + $("#address").val();
        $.ajax({
            method: 'POST',
            url: 'ajax_global.php',
            data: {
                act: 'getcoords',
                val: searchaddress
            },
            success: function(res){
                if(res && res['data'] && res['data']['lat'] && res['data']['long']) {
                    $("input[name='gpsLat']").val(res['data']['lat']);
                    $("input[name='gpsLong']").val(res['data']['long']);
                }
                else {
                    return Swal.fire({icon:'error', text:'לא אותרו קורדינטות'});
                }
            }
        });
    }

    $("#getcoords").on("click",getCoords);
</script>
<style>
    a#getcoords {
        position: relative;
        display: inline-block;
        cursor: pointer;
        line-height: 60px;
    }
</style>
