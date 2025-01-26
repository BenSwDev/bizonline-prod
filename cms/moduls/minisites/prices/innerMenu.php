<?php 


$siteID=intval($_GET['siteID']);
$siteName = $_GET['siteName'];
function innerMenu(){ ?>

<div class="innerMenu">
	<div class="tabMenu<?=($_GET['innerTab']==1?" active":"")?>"  onclick="window.location.href='/cms/moduls/minisites/prices/index.php?siteID=<?=$_GET['siteID']?>&tab=3&innerTab=1&siteName=<?=str_replace("'","&#39;",$_GET['siteName'])?>'">מחירים</div>
    <div class="tabMenu<?=($_GET['innerTab']==2?" active":"")?>"  onclick="window.location.href='/cms/moduls/minisites/prices/index.php?siteID=<?=$_GET['siteID']?>&tab=3&innerTab=2&siteName=<?=str_replace("'","&#39;",$_GET['siteName'])?>'">החל מ-</div>
</div>
	

<? } ?>