<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";


$sites = udb::full_list("SELECT sites_langs_bak.siteName,sites_langs_bak010621.siteID,sites_langs_bak010621.langID,sites_langs_bak010621.domainID,sites_langs_bak010621.agreement1,sites_langs_bak.agreement1 as live_agreement1,sites_langs_bak010621.agreement2, sites_langs_bak.agreement2 as live_agreement2,sites_langs_bak010621.agreement3  , sites_langs_bak.agreement3 as live_agreement3   FROM `sites_langs_bak` left join sites_langs_bak010621 on(sites_langs_bak010621.siteID = sites_langs_bak.siteID and sites_langs_bak010621.langID = sites_langs_bak.langID and sites_langs_bak010621.domainID = sites_langs_bak.domainID) 
where 
(sites_langs_bak010621.agreement1 != sites_langs_bak.agreement1 or sites_langs_bak010621.agreement2 != sites_langs_bak.agreement2 or sites_langs_bak010621.agreement3 != sites_langs_bak.agreement3) AND sites_langs_bak010621.domainID = 1 AND sites_langs_bak010621.langID = 1");
?>
<div style="height:100vh; overflow:auto">
<table class="tbl_agr">
	<tr>
		<th style="width:100px">שם</th>
		<th style="width:40px">מזהה</th>
		<th>הסכם 01.06 1</th>
		<th>הסכם בגיבוי 1</th>
		<th style="width:0"></th>
		<th>הסכם 01.06 2</th>
		<th>הסכם בגיבוי 2</th>
		<th style="width:0"></th>
		<th>הסכם 01.06 3</th>
		<th>הסכם בגיבוי 3</th>
	</tr>
<?$i=0;
foreach($sites as $site){ $i++;?>
<tr>
	<td><b><?=$i?>.</b><?=$site["siteName"]?></td>
	<td><?=$site["siteID"]?></td>
	<td><?=$site["agreement1"]?></td>
	<td><?=$site["live_agreement1"]?></td>
		<th style="width:0"></th>
	<td><?=$site["agreement2"]?></td>
	<td><?=$site["live_agreement2"]?></td>
		<th style="width:0"></th>
	<td><?=$site["agreement3"]?></td>
	<td><?=$site["live_agreement3"]?></td>
</tr>


<?}?>
</table>
</div>

<style>

.tbl_agr{border-collapse:collapse;}
.tbl_agr th, .tbl_agr td{border:1px black solid;padding:10px;width:400px}
.tbl_agr th{position:sticky;top:0;background:white;z-index:1}
</style>