<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";

$siteTypes = [1 => 'מתחם', 2 => 'ספא'];

$where ="1 = 1 ";

if($free = trim(filter_var($_GET['free'] ?? '', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW))){
    $where .= "AND (`sites`.`siteName` LIKE '%". udb::escape_string($free) ."%' " . (is_numeric($free) ? "OR sites.phone LIKE '%" . udb::escape_string($free) . "%' OR `sites`.`siteID` = " . intval($free) : '').")";
}

if($_GET['active']!=""){
    $where .= " AND `sites_domains`.`active`=".intval($_GET['active']);
}

if($_GET['area']!=""){
    $where .= " AND `areas`.`areaID`=".intval($_GET['area']);
}

if($_GET['city']!=""){
    $where .= " AND `sites`.`settlementID`=".intval($_GET['city']);
}


if($_GET['masof_active']!=""){
    $where .= " AND `sites`.`masof_active`=".(intval($_GET['masof_active']==1)? "1" : "0");
}

if($_GET['masof_invoice']!=""){
    $where .= " AND `sites`.`masof_invoice`=".(intval($_GET['masof_invoice']==1)? "1" : "0");
}

if($tmp = intval($_GET['site_type'])){
    $where .= " AND `sites`.`siteType`=".$tmp;
}

if($_GET['domain']!=""){
    $where .= " AND `sites_domains`.`domainID`=".intval($_GET['domain']);
}

if($_GET['exBits']!=""){
    switch($_GET['exBits']){
        case "1":
            $where .= " AND (`sites`.`exBits`=1 or `sites`.`exBits`=3 or `sites`.`exBits`=7)";
            break;
        case "2":
            $where .= " AND (`sites`.`exBits`=2 or `sites`.`exBits`=3 or `sites`.`exBits`=6)";
            break;
        case "4":
            $where .= " AND (`sites`.`exBits`=4 or `sites`.`exBits`=5 or `sites`.`exBits`=6)";
            break;
    }
}

if($_GET['onlineOrder']!=""){
    $where .= " AND `sites`.`onlineOrder`='".$_GET['onlineOrder']."'";
}
if($_GET['externalEngine']!=""){
    $where .= " AND `sites`.`externalEngine`='".$_GET['externalEngine']."'";
}

$sites = udb::full_list("SELECT `sites`.`siteName`,`sites`.`portalsID`,`sites`.`zimmersID`, sites.siteType, `sites`.`active` ,`sites`.`masof_active` ,`sites`.`masof_invoice`, `sites`.`signature`, `sites`.`siteID`, `sites`.`phone`, `sites`.`email`, `sites_langs`.`owners` , sites_domains.phone, sites_domains.phone, sites_domains.active
FROM `sites` 
LEFT JOIN `settlements` USING (`settlementID`)
LEFT JOIN `areas` USING (`areaID`)
LEFT JOIN `sites_langs` USING (`siteID`) 
LEFT JOIN `sites_domains` USING (`siteID`) 

WHERE " . $where . " AND `sites_langs`.`langID`=1 AND sites_domains.domainID=1 AND (`sites`.`active`=1 || `sites`.`active`=0) AND `sites_langs`.`domainID`=1 ORDER BY `sites`.`active` DESC, `sites`.`siteName` ASC");


$sitesActiveDom = udb::key_row("SELECT domainID, active,siteID FROM sites_domains",['siteID','domainID']);








$que="SELECT * FROM `domains` WHERE domainID = 1  order by domainID ASC";
$domains= udb::full_list($que);




function getsites($uurl){
    $url = $uurl;
    $curlSend = curl_init();

    curl_setopt($curlSend, CURLOPT_URL, $url);
    curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, 1);

    $curlResult = curl_exec($curlSend);
    $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);
    curl_close($curlSend);
    if ($curlStatus === 200)
        return $curlResult;
    else
        return [];

}
$zimmerSites = getsites("https://www.zimer4u.com/sitesList.php?jsonActive=1");
$zimmerSites = json_decode($zimmerSites,true);

$villaSites = getsites("https://www.vila.co.il/api/?key=ssd205033&type=11&from=0");
$villaSites = json_decode($villaSites,true);
$villaSites = $villaSites['sites'];
$vilas = [];
$zimmers = [];
foreach ($villaSites as $villaSite) {
    $vilas[$villaSite['siteid']] =  $villaSite;
}

foreach ($zimmerSites as $zimmerSite) {
    $zimmers[$zimmerSite['siteid']] =  $zimmerSite;
}

function cmp_sites($a, $b) {
    $retVal = strnatcmp($a['title'], $b['title']);
    if($retVal > 0) $retVal = -1;
    if($retVal < 0) $retVal = 1;
    return $retVal;
}



/*
ini_set('display_errors', 1);
error_reporting(-1 ^ E_NOTICE);
*/
?>
<link href="/cms/moduls/minisites/selecte2/css/select2.css?rnd=<?=time()?>" rel="stylesheet" />
<script src="/cms/moduls/minisites/selecte2/js/select2.js?rnd=<?=time()?>"></script>
<div class="pagePop"><div class="pagePopCont"></div></div>
<div class="manageItems" id="manageItems">
    <h1>ניהול מתחמים</h1>
	<div style="margin-top: 20px;">
		<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="openPop(0, 0)">
	</div>
	<div class="tblMobile">
    <table>
        <thead>
        <tr>
			<th>ID</th>
            <th>שם המקום</th>
			<th>שיוך וילות</th>
			<th>שיוך צימרים</th>
        </tr>
        </thead>
        <tbody id="sortRow">
            <?php
            $iter = 0;
            if (count($sites)){
                foreach($sites as $site){
                    $iter++;
                    ?>
                    <tr id="<?=$site['siteID']?>" data-sitename="<?=outDb($site['siteName'])?>" data-pid="<?=$site['portalsID']?>" data-zid="<?=$site['zimmersID']?>">

                            <td><?=$site['siteID']?></td>
                            <td ><?=outDb($site['siteName'])?></td>
                            <td><?=$site['portalsID'] ? $vilas[$site['portalsID']]['title']  :"לא משוייך" ?></td>
                            <td><?=$site['zimmersID'] ? $zimmers[$site['zimmersID']]['title'] :"לא משוייך" ?></td>

                        </tr>
                    <?php
                }
            }
            ?>
        </tbody>
    </table>
	</div>
</div>
<div class="popup delete-order rel-site" style="display:none;">
	<div class="popup_container">
		<div class="title" id="siteName"></div>
		<form class="form" id="relForm">
            <input type="hidden" name="relSiteID" id="relSiteID" value="0">
			<div class="inputWrap">
				 <select name="zimmersID" id="zimmersID" class="useSelect2">
                     <option value="0">מזהה צימרים</option>
                         <?
                         foreach ($zimmerSites as $zimmer) {
                             $selected = "";
                             echo '<option value="'.$zimmer['siteid'].'" '.$selected.'>'.$zimmer['title'].'</option>';
                         }
                         ?>
                  </select>
				<label for="pass">מזהה צימרים</label>
			</div>
            <div  class="inputWrap">
                <select name="portalsID" id="portalsID" class="useSelect2">
                <option value="0">מזהה פורטלים</option>
                    <?
                    foreach ($villaSites as $villa) {
                        $selected = "";
                        echo '<option value="'.$villa['siteid'].'" '.$selected.' >'.$villa['title'].'</option>';
                    }
                    ?>
                </select>
                <label for="pass">מזהה פורטלים</label>
            </div>
			<div class="buttons">
				<div class="submit" onclick="sendRel('relForm')">שמריה</div>
				<div class="cancel" onclick="$('.popup.rel-site').hide(),$('.popup.rel-site input').val('')">סגירה</div>
			</div>
		</form>
	</div>
</div>
<style>
    .popup.delete-order {position:fixed;top:0;right:0;left:0;bottom:0;width:100%;height:100%;z-index:2;background:rgba(0,0,0,0.6);text-align:center;}
    .popup.delete-order .popup_container {position:absolute;top:50%;right:50%;width:100%;max-width:500px;padding:10px;box-sizing:border-box;min-height:100px;background:#fff;border-radius:8px;background:#fff;transform:translateY(-50%) translateX(50%)}
    .popup.delete-order .inputWrap {background:#fff;display:block;max-width:200px;margin:0 auto;border-radius:6px;position:relative;height:50px;box-sizing:border-box}
    .popup.delete-order .inputWrap input {position:relative;top:0;right:0;left:0;bottom:0;width:100%;height:100%;background:transparent;border:0;box-sizing:border-box;padding:0 5px;}
    .popup.delete-order .inputWrap label {position:relative;top:0;right:5px;z-index:2;font-size:14px;}
    .popup.delete-order .form {margin:10px 0 0 0;display:block}
    .popup.delete-order .form .need {text-decoration:underline;font-weight:600;padding-bottom:10px;}
    .popup.delete-order .buttons {display:block;margin:10px auto}
    .popup.delete-order .buttons > div {display:inline-block;cursor:pointer;height:50px;width:100px;border-radius:8px;text-align:center;line-height:50px;color:#fff;font-size:18px;font-weight:500}
    .popup.delete-order .buttons > div.cancel {background:#111}
    .popup.delete-order .buttons > div.submit {background:#ea5656}
</style>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script src="/user/assets/js/swal.js?v=1"></script>
<script>
var pageType="<?=$pageType?>";


function getCellValue(row, index){ return $(row).children('td').eq(index).text() }

$("#sortRow tr").on("click",function () {
    var sid = $(this).attr("id");
    var sn = $(this).data("sitename");
    var zid = $(this).data("zid");
    var pid = $(this).data("pid");
    if(zid) {
        $("#zimmersID").val(zid);
    }
    else {
        $("#zimmersID").val(0);
    }
    if(pid) {
        $("#portalsID").val(pid);
    }
    else {
        $("#portalsID").val(0);
    }
    $("#siteName").text(sn);

    $("#relSiteID").val(sid);
    $(".popup.rel-site").show();
    $('.useSelect2').select2();
});


function sendRel(formID){
    var siteID = $("#relSiteID").val();
    var pid  = $("#portalsID").val();
    var zid  = $("#zimmersID").val();
    $("tr[id='"+siteID+"']").data("zid",zid);
    $("tr[id='"+siteID+"']").data("pid",pid);
    $.post("ajax_relSite.php",{siteID:siteID , portalsID: pid, zimmersID:zid },function(res){
        window.location.reload();
    });
}
</script>
<?php
include_once "../../bin/footer.php";
?>
