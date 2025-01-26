<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 01/07/2021
 * Time: 16:03
 */

include_once "../../../bin/system.php";
if($_GET['tab']){
    include_once "../../../bin/top_frame.php";
    include_once "../mainTopTabs.php";
    include_once "innerMenu.php";
}else{
    include_once "../../../bin/top.php";
}
include_once "../../../_globalFunction.php";

$domains = DomainList::get();

$sites = udb::full_list("select siteID,siteName from sites");
foreach ($sites as $site) {
   foreach ($domains as $domain) {
       $sitesDomains[$site['siteID']][$domain['domainID']] = udb::single_value("select active from sites_domains where siteID=".$site['siteID']." and domainID=".$domain['domainID']);
   }
}
$promoted = udb::key_list("select * from promotedDomains",['domainID','siteID']);
$promoted2 = udb::key_list("select * from promotedHomeDomains",['domainID','siteID']);
$promotedsearch= udb::key_list("select * from promotedsearchDomains",['domainID','siteID']);

$domainID =intval($_GET["domainID"]);
$orderby = intval($_GET["orderby"]);

switch($orderby) {
    case 1:
        usort($sites,function ($a,$b){
            global $sitesDomains;
            if($sitesDomains[$a['siteID']][intval($_GET["domainID"])] == $sitesDomains[$b['siteID']][intval($_GET["domainID"])]) {
                return 0;
            }
            if($sitesDomains[$a['siteID']][intval($_GET["domainID"])] > $sitesDomains[$b['siteID']][intval($_GET["domainID"])]) {
                return -1;
            }
            else {
                return 1;
            }
        });
        break;
    case 2:
        usort($sites,function ($a,$b){
            global $promoted;
            if($promoted[intval($_GET["domainID"])][$a['siteID']][0]['active'] == $promoted[intval($_GET["domainID"])][$b['siteID']][0]['active']) {
                return 0;
            }
            if($promoted[intval($_GET["domainID"])][$a['siteID']][0]['active'] > $promoted[intval($_GET["domainID"])][$b['siteID']][0]['active']) {
                return -1;
            }
            else {
                return 1;
            }
        });
        break;
    case 3:
        usort($sites,function ($a,$b){
            global $promoted2;
            if($promoted2[intval($_GET["domainID"])][$a['siteID']][0]['active'] == $promoted2[intval($_GET["domainID"])][$b['siteID']][0]['active']) {
                return 0;
            }
            if($promoted2[intval($_GET["domainID"])][$a['siteID']][0]['active'] > $promoted2[intval($_GET["domainID"])][$b['siteID']][0]['active']) {
                return -1;
            }
            else {
                return 1;
            }
        });
        break;
    case 4:
        usort($sites,function ($a,$b){
            global $promotedsearch;
            if($promotedsearch[intval($_GET["domainID"])][$a['siteID']][0]['active'] == $promotedsearch[intval($_GET["domainID"])][$b['siteID']][0]['active']) {
                return 0;
            }
            if($promotedsearch[intval($_GET["domainID"])][$a['siteID']][0]['active'] > $promotedsearch[intval($_GET["domainID"])][$b['siteID']][0]['active']) {
                return -1;
            }
            else {
                return 1;
            }
        });
        break;
}
//print_r($domains);
//uasort($sites,)

?>
<style>
    .manageItems table > thead td {
        position: sticky;
        top: 0;
        z-index: 99999999;
        background: white;
        line-height: 1.5;
        height: 120px;
        position: relative;
    }
	.manageItems table > thead td:not(.static) {max-width: 40px;}
    .rotate-90 {
		width: 120px;
		font-size: 13px;
		line-height: 0.9;
		text-align: left;
		transform: rotate(-90deg);
		position: absolute;
		right: -44px;
		bottom: 44px;
		height: 36px;
		display: inline-flex;
		justify-content: end;
		align-items: center;
		border-bottom: 1px solid black;
		z-index: 1;
		padding-left: 10px;
		box-sizing: border-box;
		font-weight: normal;
	}
	.favicon{position:absolute;z-index:2;width:26px;right:2px}
	.filters{display:flex}
	.filters * {height:40px;box-sizing:border-box}
	.filters #filterSiteInput {border:1px solid black;height:40px}
	.filters #filterSiteSubmit {border:1px solid black;background:blue;color:white;padding:0 10px;cursor:pointer;line-height:38px}
</style>
<div class="editItems" style="max-height:100vh">
    <div class="manageItems" id="manageItems">
    <h1 style="text-align: center;" >שיוך אתר לדומיין ללא שכפול</h1>
    <table class="domainsTable" style="overflow:auto;width:auto">
        <thead style="position:sticky;top:0;background:white">
        <tr>
            <td class="static">ID</td>
            <td class="static">					
				<div style="width:200px;padding:5px">					
					<div style="height:5px"></div>		
					<select onchange="selectDomain($(this).val())">
						<option value="0">כל הדומיינים</option>
						<?php foreach ($domains as $domain) {?>	 
						<option value="<?=$domain['domainID']?>"><?=$domain['domainName']?></option> 
						<?}?>
					</select>
					<div style="height:5px"></div>					
					<div class="filters">
					<input type="text"  id="filterSiteInput"> <span id="filterSiteSubmit">סינון</span>
				</div>
				שם המתחם
			</td>
            <?php foreach ($domains as $domain) {?>
                <td align="center" data-domain="<?=$domain['domainID']?>">
					<img class="favicon" src="https://<?=$domain['domainURL']?>/favicon.ico">
					<div class="rotate-90"><A href="?domainID=<?=$domain['domainID']?>&orderby=1">סטטוס<BR><?=$domain['domainName']?></A></div>
				</td>
                <td align="center" data-domain="<?=$domain['domainID']?>" <?php if($domain['domainID'] == $domain['lastID'] || ($domain['domainID'] != 6 && $domain['domainID'] != 1  ) ) echo "!";?>style="border-left:2px solid #000000;"><div class="rotate-90"><A href="?domainID=<?=$domain['domainID']?>&orderby=2">קידום מומלצים<BR><?=$domain['domainName']?></A></div></td>
                <?php if($domain['domainID'] == 109 || $domain['domainID'] == 111) {?>
                <td align="center" data-domain="<?=$domain['domainID']?>" !style="border-left:2px solid #000000;"><div class="rotate-90"><A href="?domainID=<?=$domain['domainID']?>&orderby=3">לופטים שיעניינו<BR><?=$domain['domainName']?></a></div></td>
                <?php }?>
                <?php if($domain['domainID'] != 6  && $domain['domainID'] != 1 ) {?>
                    <td data-domain="<?=$domain['domainID']?>" align="center" style="border-left:2px solid #000000;"><div class="rotate-90"><A href="?domainID=<?=$domain['domainID']?>&orderby=4">ק.תוצאות<BR><?=$domain['domainName']?></A></div></td>
                    <?php }?>
                <?php }?>

        </tr>
        </thead>
        <tbody id="sortRow">
        <?php
        foreach ($sites as $site) {
            ?>
            <tr>
                <td class="static"><?=$site['siteID']?></td>
                <td class="static"><?=$site['siteName']?></td>
                <?php foreach ($domains as $domain) {?>
                    <td data-domain="<?=$domain['domainID']?>"><input type="checkbox" name="active" id="active<?=$site['siteID']?><?=$domain['domainID']?>" data-type="0" data-did="<?=$domain['domainID']?>" data-sid="<?=$site['siteID']?>"
                               value="1" <?=$sitesDomains[$site['siteID']][$domain['domainID']] ? ' checked ' : '';?>></td>
                    <td data-domain="<?=$domain['domainID']?>" <?php if(($domain['domainID'] == $domain['lastID'] || ($domain['domainID'] != 6 && $domain['domainID'] != 1  ))  ) echo "!";?>style="border-left:2px solid #000000;"><input type="checkbox" name="active" id="promoted<?=$site['siteID']?><?=$domain['domainID']?>" data-type="1"
                               data-did="<?=$domain['domainID']?>" data-sid="<?=$site['siteID']?>"
                               value="1" <?=$promoted[$domain['domainID']][$site['siteID']][0]['active'] ? ' checked ' : '';?>></td>
                <?php if($domain['domainID'] == 109 ||  $domain['domainID'] == 111) {?>
                        <td data-domain="<?=$domain['domainID']?>" ><input type="checkbox" name="promotedhome" id="promotedhome<?=$site['siteID']?><?=$domain['domainID']?>" data-type="2"
                                                                          data-did="<?=$domain['domainID']?>" data-sid="<?=$site['siteID']?>"
                                                                          value="1" <?=$promoted2[$domain['domainID']][$site['siteID']][0]['active'] ? ' checked ' : '';?>></td>
                    <?php }?>
                    <?php if($domain['domainID'] != 6 && $domain['domainID'] != 1) {?>
                        <td data-domain="<?=$domain['domainID']?>" style="border-left:2px solid #000000;"><input type="checkbox" name="promotedsearch" id="promotedsearch<?=$site['siteID']?><?=$domain['domainID']?>" data-type="3"
                                                                          data-did="<?=$domain['domainID']?>" data-sid="<?=$site['siteID']?>"
                                                                          value="1" <?=$promotedsearch[$domain['domainID']][$site['siteID']][0]['active'] ? ' checked ' : '';?>></td>
                    <?php }?>
                <?php }?>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    </div></div>


<script>
	function selectDomain(domainID){
	  domainID = parseInt(domainID)
	  if(domainID>0){
		$('.domainsTable td:not(.static)').hide();
		$('.domainsTable td[data-domain="'+domainID+'"]').show();
	  }else{
		$('.domainsTable td').show();
	  }
		
	}
    $(document).ready(function(){
		var _isFound;
        $('#filterSiteSubmit').on("click",function(){
			var _find = $('#filterSiteInput').val();
			if(_find){
				//debugger;
				$('.domainsTable tbody tr').each(function(){  
					//debugger;
					var _tr = $(this);
					_isFound = 0;
					$(this).find('.static').each(function(){ 						
						//debugger;
						var _text = $(this).html();
						if(_text && _text.indexOf(_find)>-1){
							_isFound++;
						}
					});	
					if(_isFound){
						_tr.show();
					}else{
						_tr.hide();
					}
				});
			}else{
				$('.domainsTable tbody tr').show();
			}
        });
		
		$("input[type='checkbox']").on("change",function(){
            var active = $(this).is(":checked") == true ? 1 : 0;
            var type = $(this).data("type");
            var siteID =  $(this).data("sid");
            var domainID =  $(this).data("did");
            var sendData = {
                siteID: siteID,
                domainID: domainID,
                active: active,
                type: type
            };
            $.ajax({
                method: 'POST',
                url: 'ajax_update_site_domain.php',
                data: sendData,
                success: function (res) {
                    try {
                        var result = JSON.parse(res);
                    } catch (e) {
                        var result = res;
                    }
                   if(result.error) {
                       alert('Oooops somthing went Wrong');
                   }
                },
                error: function() {
                    alert('Oooops somthing went Wrong');
                },
                fail: function() {
                    alert('Oooops somthing went Wrong');
                }
            },'json');

        });
    });
</script>
<?php

if(!$_GET["tab"]) include_once "../../../bin/footer.php";


