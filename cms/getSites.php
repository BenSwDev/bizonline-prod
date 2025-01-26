<?php
    include_once "bin/system.php";
	function curl_funcjson($url,$dataToSend){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS,$dataToSend);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    $html = curl_exec($curl);
    curl_close($curl);
    return $html;
}

$getSitesUrl = "https://www.vila.co.il/dataGetter.php";
$data = [
	'tbl'=> 'sites'
];
 
$sites = curl_funcjson($getSitesUrl,$data);
$sites = json_decode($sites,true); 
$getSitesUrl = "zimer4u.co.il/zimmers.php?all=11";
$zimmers = curl_funcjson($getSitesUrl,$data);
$zimmers = json_decode($zimmers,true); 
?>

<html lang="he">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body dir="rtl" style="font-family:'Arial'">
<table cellpadding="2"  border="1" cellspacing="2"> 
	<TR>
		<TD>שם המתחם</TD>
		<TD>אזור</TD>
		<TD>יישוב</TD>
		<TD>כתובת</TD>
		<TD>קורדינטות</TD>
		<TD>בעלים</TD>
		<TD>טלפון ראשי</TD>
		<td></td>
		<TD>שם תואם בביז און ליין</TD>
	</TR>
<?
foreach($sites as $site) { 
if($site['title']){
$singleSite = udb::full_list("select *  from sites where siteName like '%". udb::escape_string($site['title'])."%' AND siteName IS NOT NULL ");
}
?>
	<TR style="height:40px;<?if($singleSite){?>background:#CCFFCC<?}?>">
		<TD><?=$site['title']?></TD>
		<TD><?=$site['area']?></TD>
		<TD><?=$site['city']?></TD>
		<TD><?=$site['address']?></TD>
		<TD><?=$site['lat']?>,<?=$site['lon']?></TD> 
		<TD><?=$site['owner']?></TD>
		<TD><?=$site['phone']?></TD>
		<td></td>
		<TD>
		<?

foreach($singleSite as $singleS){?>
			<div style="border-bottom:1px black solid"><?=$singleS["siteID"]?> - <?=$singleS["siteName"]?></div>
		<?}?>
		</TD>
		
	</TR>
	<?
}
?>
<TR style="height:40px;background:#CCCCCC"><td colspan="9"></td></tr>
<?
foreach($zimmers as $site) { 
if($site['title']){
$singleSite = udb::full_list("select *  from sites where siteName like '%". udb::escape_string($site['title'])."%' AND siteName IS NOT NULL ");
}
?>
	<TR style="height:40px;<?if($singleSite){?>background:#CCFFCC<?}?>" data-type="zimmers">
		<TD><?=$site['title']?></TD>
		<TD><?=$site['area']?></TD>
		<TD><?=$site['city']?></TD>
		<TD><?=$site['address']?></TD>
		<TD><?=$site['lat']?>,<?=$site['lon']?></TD> 
		<TD><?=$site['owner']?></TD>
		<TD><?=$site['phone']?></TD>
		<td></td>
		<TD>
		<?

foreach($singleSite as $singleS){?>
			<div style="border-bottom:1px black solid"><?=$singleS["siteID"]?> - <?=$singleS["siteName"]?></div>
		<?}?>
		</TD>
		
	</TR>
	<?
}
?>
</table>
</body>
</html>
