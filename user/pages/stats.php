<?
function htmlDate($date){
    return implode('/', array_reverse(explode('-', $date)));
}
$clearUrl = '?page=stats';
$siteDomains = udb::single_column("select distinct domainID from sites_domains where active=1 and siteID=" .  $_CURRENT_USER->active_site());
if(!$siteDomains) $siteDomains = [0];
if(in_array(8,$siteDomains)) {
    $siteDomains[] = 7;
}
$domainsList = udb::key_row("SELECT * FROM domains where domainURL!='' and domainID in (".implode(",",$siteDomains).") and domainID!=1 ","domainID");



function formatPhoneNumber($num){
    if(substr($num,0,2) == "05") {
        return substr($num,0,3) . "-" . substr($num,3);
    }
    return substr($num,0,2) . "-" . substr($num,2);
}

//$sitesP = udb::key_row("SELECT siteID,portalsID FROM sites where portalsID > 0 ","portalsID");
//$sitesZ = udb::key_row("SELECT siteID,zimmersID FROM sites where zimmersID > 0 ","zimmersID");


// copy ends

$free = udb::escape_string($_GET['free']);
$domainID = udb::escape_string($_GET['domainID']);


$where = " 1 = 1 ";
if($free) $where.=" AND sites.siteName LIKE '%".$free."%' ";
if($domainID) {
    $where .= ' AND sites_domains.domainID='.$domainID ;
}
else {
    //$where .= ' AND sites_domains.domainID=1';
}
$where .= ' AND sites.siteID= ' . $_CURRENT_USER->active_site();
$wheret = $wherec = $wherew = $wherem =  ' siteID = ' . $_CURRENT_USER->active_site();


if($_GET['createDate']) {
    if($_GET['createDateTo']) {
        $useDate = implode('-',array_reverse(explode('/',trim($_GET['createDate']))));
        $useDate2 = implode('-',array_reverse(explode('/',trim($_GET['createDateTo']))));
        $useDate  =  date("Y-m-d",strtotime($useDate));
        $useDate2  =  date("Y-m-d",strtotime($useDate2));
        if($_GET['createDateTo'] == $_GET['createDate']) {
            $wheret.= " AND STR_TO_DATE(sites_clicks.datetime,'%Y-%m-%d')=STR_TO_DATE('".$useDate."','%Y-%m-%d') ";
            $wherec.= " AND STR_TO_DATE(contactForm.created,'%Y-%m-%d')=STR_TO_DATE('".$useDate."','%Y-%m-%d') ";
            $wherew.= " AND DATE_FORMAT(contact_whatsapp.created,'%Y-%m-%d')=DATE_FORMAT('".$useDate."','%Y-%m-%d') ";
            $wherem.= " AND  STR_TO_DATE(maskyooCalls.start_call,'%Y-%m-%d')='".$useDate."'";
        }
        else {
            $wheret.= " AND STR_TO_DATE(sites_clicks.datetime,'%Y-%m-%d') BETWEEN  STR_TO_DATE('".$useDate."','%Y-%m-%d') AND STR_TO_DATE('".$useDate2."','%Y-%m-%d')";
            $wherec.= " AND STR_TO_DATE(contactForm.created,'%Y-%m-%d') BETWEEN  STR_TO_DATE('".$useDate."','%Y-%m-%d') AND STR_TO_DATE('".$useDate2."','%Y-%m-%d')";
            $wherew.= " AND DATE_FORMAT(contact_whatsapp.created,'%Y-%m-%d') BETWEEN  DATE_FORMAT('".$useDate."','%Y-%m-%d') AND DATE_FORMAT('".$useDate2."','%Y-%m-%d')";
            $wherem.= " AND maskyooCalls.start_call BETWEEN  '".$useDate."' AND '".$useDate2."'";
        }

    }
    else {
        $useDate = implode('-',array_reverse(explode('/',trim($_GET['createDate']))));
        $wheret.= " AND STR_TO_DATE(sites_clicks.datetime,'%Y-%m-%d') >= '".$useDate."'";
        $wherec.= " AND STR_TO_DATE(contactForm.created,'%Y-%m-%d') >= '".$useDate."'";
        $wherew.= " AND DATE_FORMAT(contact_whatsapp.created,'%Y-%m-%d') >= '".$useDate."'";
        $wherem.= " AND STR_TO_DATE(maskyooCalls.start_call,'%Y-%m-%d') >= '".$useDate."'";
    }

}
else {
    if($_GET['createDateTo']) {
        $useDate  =  implode('-',array_reverse(explode('/',trim($_GET['createDateTo']))));
        $wheret.= " AND STR_TO_DATE(sites_clicks.datetime,'%Y-%m-%d') <= '".$useDate."'";
        $wherec.= " AND STR_TO_DATE(contactForm.created,'%Y-%m-%d') <= '".$useDate."'";
        $wherew.= " AND DATE_FORMAT(contact_whatsapp.created,'%Y-%m-%d') <= '".$useDate."'";
        $wherem.= " AND STR_TO_DATE(maskyooCalls.start_call,'%Y-%m-%d') <= '".$useDate."'";
    }
}

if($domainID) {
    $sql = "SELECT siteID, `type`, COUNT(*) FROM sites_clicks WHERE ".$wheret." AND sites_clicks.type IN ('whatsapp', 'phone', 'showphone') AND sites_clicks.domainID=".$domainID." GROUP BY siteID, `type`";
    $qsites = udb::key_list($sql,  ['siteID', 'type'], 2);
//	echo $sql;
//	print_r($qsites);

} else {
    $qsites = udb::key_list("SELECT siteID, `type`, COUNT(*) FROM sites_clicks WHERE ".$wheret." AND sites_clicks.type IN ('whatsapp', 'phone', 'showphone') GROUP BY siteID, `type`",  ['siteID', 'type'], 2);

}
$sites = udb::full_list("SELECT distinct(sites_domains.siteID) , sites.siteName,sites.portalsID,sites.zimmersID FROM sites_domains left join sites using (siteID) WHERE $where");
?>
<div class="manageItems" id="manageItems">
    <h1>סטטיסטיקות</h1>

    <div class="searchOrder">
        <div class="ttl">חפש פעולות</div>
        <form method="GET" autocomplete="off" >
            <input type="hidden" name="page" value="stats">
            <?
            $createDate = $_GET['createDate'];
            $createDateTo = $_GET['createDateTo'];
            ?>
            <div class="inputWrap half">
                <input type="text" name="createDate" placeholder="תאריך מ" class="searchFrom2" value="<?=implode('/',array_reverse(explode('-',trim($createDate))))?>" readonly>
            </div>
            <div class="inputWrap half">
                <input type="text" name="createDateTo" placeholder="תאריך עד" class="searchFrom2" value="<?=implode('/',array_reverse(explode('-',trim($createDateTo))))?>" readonly>
            </div>
            <div class="inputWrap" style="display: none;">
                <select name="domainID">
                    <option value="0"<?php if(!$domainID) { ?>selected<?php } ?>>כללי</option>
                    <?
                    foreach ($domainsList as $domain) {
                        ?><option value="<?=$domain['domainID']?>"<?php if($domainID == $domain['domainID']) echo ' selected'?>><?=$domain['domainName']?></option><?
                    }
                    ?>


                </select>
            </div>
            <div  class="inputWrap">
                <select name="type">
                    <option value="0">סוג המידע</option>
                    <option <?=$_GET['type'] == 'whatsappstart' ? ' selected ' : ''; ?>  value="whatsappstart">התחלת שיחת וואטסאפ</option>
                    <option <?=$_GET['type'] == 'maskyoocall' ? ' selected ' : ''; ?>  value="maskyoocall">שיחות מסקיו</option>
                    <option <?=$_GET['type'] == 'contacts' ? ' selected ' : ''; ?>  value="contacts">לידים יצירת קשר</option>
                </select>
            </div>

            <a class="clear" href="<?=$clearUrl?>">נקה</a>
            <input type="submit" value="חפש">

        </form>
    </div>


    <div class="counter"></div>
    <table class="stats-table">
        <thead>
        <tr>
            <td>שם המתחם</td>
            <!--<td data-type="whatsappclick">הקלקות ווטסאפ</td>-->
            <td data-type="whatsappstart">התחלת שיחת וואטסאפ</td>
            <!--<td data-type="phonedisplay">הצגת טלפון</td>-->
            <!--<td data-type="phones">טלפון</td>-->
            <td  data-type="maskyoocall">שיחות מסקיו</td>
            <td data-type="contacts">לידים יצירת קשר</td>

        </tr>
        </thead>
        <tbody id="sortRow">
        <?php

        if (count($sites)){
            $i=0;
            $adding = '';
            if($domainID) $adding = '  domainID='.$domainID . " AND ";
            $conForm = udb::key_row("SELECT COUNT(*) as Total,siteID FROM contactForm WHERE ".$adding.$wherec ." group by siteID","siteID");
            $conWhatsapp = udb::key_row("SELECT COUNT(*) as Total,siteID FROM contact_whatsapp WHERE ".$adding.$wherew." group by siteID","siteID");
            $maskyooSql = "SELECT COUNT(*) as Total,siteID FROM maskyooCalls WHERE ".$adding.$wherem." group by siteID";
            $maskyoo = udb::key_row($maskyooSql,"siteID");
            //echo "SELECT COUNT(*) as Total,siteID FROM contactForm WHERE ".$adding.$wherec ." group by siteID";
            //print_r($maskyoo);
            //exit;


            $sitesCount = 0;
            foreach($sites as  $site) {

                // $whatsapp = udb::single_value("SELECT COUNT(type) FROM sites_clicks WHERE type='whatsapp' AND siteID=".$site['siteID']);
                // $showphone = udb::single_value("SELECT COUNT(type) FROM sites_clicks WHERE type='showphone' AND siteID=".$site['siteID']);
                // $phone = udb::single_value("SELECT COUNT(type) FROM sites_clicks WHERE type='phone' AND siteID=".$site['siteID']);
                if($domainID && $domainID == 9) {
                    if(!$site['zimmersID']) continue;
                }
                if($domainID && ($domainID == 7 || $domainID == 8)) {
                    if(!$site['portalsID']) continue;
                }
                $sitesCount++;
                ?>
                <tr id="<?=$site['siteID']?>">
                    <td><?=$site['siteName']?></td>
                    <!--<td><?=$qsites[$site['siteID']]['whatsapp'][0]['COUNT(*)']?$qsites[$site['siteID']]['whatsapp'][0]['COUNT(*)']:0?></td>-->
                    <td><?=intval($conWhatsapp[$site['siteID']]['Total'])?></td>
                    <!--<td><?=$qsites[$site['siteID']]['showphone'][0]['COUNT(*)']?$qsites[$site['siteID']]['showphone'][0]['COUNT(*)']:0?></td>-->
                    <!--<td><?=$qsites[$site['siteID']]['phone'][0]['COUNT(*)']?$qsites[$site['siteID']]['phone'][0]['COUNT(*)']:0?></td>-->
                    <td><?=$maskyoo[$site['siteID']] ? $maskyoo[$site['siteID']]['Total'] : 0?></td>
                    <td><?=$conForm[$site['siteID']]? $conForm[$site['siteID']]['Total'] : 0?></td>
                </tr>
                <?php
            }
        }
        ?>
        </tbody>
    </table>
    <?if($_GET['type']) {?>
        <div class="detailed-lists">
        <?
        require __DIR__ . '/../partials/stats/' . $_GET['type'] . '.php';
        ?>
        </div><?}?>
</div>
<input type="hidden" id="orderResult" name="orderResult" value="">

<style>
    .stats-table{border-collapse:collapse;background:white;margin:20px auto}
    .stats-table thead td{font-weight:bold}
    .stats-table td{border:1px #ccc solid;padding:5px}


    .detailed-lists table{border-collapse:collapse;background:white;margin:20px auto}
    .detailed-lists table thead td{font-weight:bold}
    .detailed-lists table td, .detailed-lists table th{border:1px #ccc solid;padding:5px}
</style>


<script>
    $('.searchFrom2').datepicker({
        format: 'd/m/Y',
        timepicker: false

    });
    $('.searchTo2').datepicker({
        format: 'd/m/Y',
        onShow:function( ct ){
            this.setOptions({
                minDate:$('.searchFrom2').val()?$('.searchFrom2').val().split("/").reverse().join("-"):false
            })
        },
        timepicker: false
    });




    var pageType="<?=$pageType?>";
    $(".counter").html( "<?=$sitesCount?> רשומות" );
    function openPop(pageID, siteName){
        $(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/frame.dor.php?siteID='+pageID+'&siteName='+encodeURIComponent(siteName)+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
        $(".pagePop").show();
    }
    function closeTab(){
        $(".pagePopCont").html('');
        $(".pagePop").hide();
    }




    function search_table(){
        // Declare variables
        var input, filter, table, tr, td, i;
        input = document.getElementById("searchinTbl");
        filter = input.value.toUpperCase();
        table = document.getElementById("resTable");
        tr = table.getElementsByTagName("tr");

        // Loop through all table rows, and hide those who don't match the search query
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td") ;
            for(j=0 ; j<td.length ; j++)
            {
                let tdata = td[j] ;
                if (tdata) {
                    if (tdata.innerHTML.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        break ;
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    }
</script>
<style>
    .manageItems table > thead > tr > th, .manageItems table > thead > tr > td {width:auto !important}
    .counter {position: relative;display: block;text-align: left;}
    .searchOrder {margin: 20px auto 0;display: block;padding: 13px 30px;border: 1px solid #0dabb6;border-radius: 8px;background: #fff;left: 0;right: 0;position: relative;max-width: 240px;overflow: hidden;}
    .searchOrder form {margin-top: 10px;}
    .searchOrder form .inputWrap {margin: 5px;}
    .searchOrder form .inputWrap input[type=text] {height: 40px;box-sizing: border-box;padding-right: 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;width: 100%;}
    .searchOrder form .clear {text-decoration: none;font-size: 16px;display: inline-block;vertical-align: top;background: #fff;color: #0dabb6;border-radius: 5px;margin: 5px;border: 1px #0dabb6 solid;float: right;line-height: 40px;width: 60px;text-align: center;}
    .searchOrder form input[type=submit] {display: block;vertical-align: top;background: #0dabb6;color: #fff;border-radius: 5px;cursor: pointer;margin: 5px;width: 100px;float: left;font-size: 20px;line-height: 40px;}
    .searchOrder form .inputWrap select {height: 40px;box-sizing: border-box;padding-right: 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;text-align: right;width: 100%;}
    #searchinTbl{
        height: 40px;
        box-sizing: border-box;
        padding-right: 10px;
        font-size: 16px;
        border: 1px #ccc solid;
        border-radius: 5px;
        width: 100%;
    }
</style>
