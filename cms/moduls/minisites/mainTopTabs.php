


<?php function showTopTabs($addLink = "") {
	global $domainID;
	//$siteName = addslashes(htmlspecialchars($_GET['siteName']));
    $siteName = $_GET['siteName'];
    $newTabs = "";
	if(isset($_GET['siteID'])) {
	    $siteType = udb::single_value("select siteType from sites where siteID=".intval($_GET['siteID']));
	    if(in_array($siteType,[2,3, 6,7,10,11,14,15])  !== false ) {
	        $newTabs = '<div class="tab' . ($_GET['tab']==15?" active":"").'"' . ' onclick="window.location.href=\'/cms/moduls/minisites/treats/treaters.php?siteID='.$_GET['siteID'].'&tab=15&domid='.$domainID.'&siteName='.urlencode($siteName).'\'"><p>מטפלים</p></div>' ;
            $newTabs .= '<div class="tab' . ($_GET['tab']==16?" active":"").'"' . ' onclick="window.location.href=\'/cms/moduls/minisites/treats/treatrooms.php?siteID='.$_GET['siteID'].'&tab=16&domid='.$domainID.'&siteName='.urlencode($siteName).'\'"><p>חדרי טיפולים</p></div>' ;
            $newTabs .= '<div class="tab' . ($_GET['tab']==17?" active":"").'"' . ' onclick="window.location.href=\'/cms/moduls/minisites/treats/orders.php?siteID='.$_GET['siteID'].'&tab=17&domid='.$domainID.'&siteName='.urlencode($siteName).'\'"><p>הזמנות</p></div>' ;
            $newTabs .= '<div class="tab' . ($_GET['tab']==18?" active":"").'"' . ' onclick="window.location.href=\'/cms/moduls/minisites/treats/treats.php?siteID='.$_GET['siteID'].'&tab=18&domid='.$domainID.'&siteName='.urlencode($siteName).'\'"><p>טיפולים</p></div>' ;
            $newTabs .= '<div class="tab' . ($_GET['tab']==19?" active":"").'"' . ' onclick="window.location.href=\'/cms/moduls/minisites/sites_cupons/table.php?siteID='.$_GET['siteID'].'&tab=19&domid='.$domainID.'&siteName='.urlencode($siteName).'\'"><p>קופוני הנחה</p></div>' ;

        }

    }
?>

	<div class="siteMainTabs">
		<div class="miniTabs">
			<?php if($_GET['siteID']!=0){ ?>
			<div class="tab<?=($_GET['tab']==1?" active":"")?>"  onclick="window.location.href='/cms/moduls/minisites/frame.dor2.php?siteID=<?=$_GET['siteID']?>&tab=1&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>המתחם</p></div>
            <div class="tab<?=($_GET['tab']==13?" active":"")?>"  onclick="window.location.href='/cms/moduls/minisites/giftCards/paymentsSetting.php?siteID=<?=$_GET['siteID']?>&tab=13&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>עמלות ותשלומים</p></div>
			<div class="tab<?=($_GET['tab']==2?" active":"")?>"  onclick="window.location.href='/cms/moduls/minisites/rooms/index.php?siteID=<?=$_GET['siteID']?>&tab=2&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>היחידות</p></div>
			<div class="tab<?=($_GET['tab']==3?" active":"")?>"  onclick="window.location.href='/cms/moduls/minisites/prices/index.php?siteID=<?=$_GET['siteID']?>&tab=3&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>מחירים</p></div>
			<div class="tab<?=($_GET['tab']==6?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/reviews/index.php?siteID=<?=$_GET['siteID']?>&tab=6&domainID=<?=$domainID?>&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>חוות דעת</p></div>
			<div class="tab<?=($_GET['tab']==7?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/galleries/gallery.php?siteID=<?=$_GET['siteID']?>&tab=7&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>גלריות - בנק</p></div>
			<div class="tab<?=($_GET['tab']==8?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/galleries/video.php?siteID=<?=$_GET['siteID']?>&tab=8&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>וידאו - בנק</p></div>
			<div class="tab<?=($_GET['tab']==9?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/virtualtours/virtualtours.php?siteID=<?=$_GET['siteID']?>&tab=9&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>סיורים וירטואליים</p></div>
			<div class="tab<?=($_GET['tab']==10?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/faqs/faqs.php?siteID=<?=$_GET['siteID']?>&tab=10&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>שאלות ותשובות</p></div>
            <?=$newTabs?>
			<div class="tab<?=($_GET['tab']==11?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/benfits/index.php?siteID=<?=$_GET['siteID']?>&tab=11&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>מבצעים</p></div>
            <div class="tab<?=($_GET['tab']==12?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/extras/index.php?siteID=<?=$_GET['siteID']?>&tab=12&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>תוספות</p></div>
            <div class="tab<?=($_GET['tab']==21?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/pixels/index.php?siteID=<?=$_GET['siteID']?>&tab=21&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>פיקסלים</p></div>
            <div class="tab<?=($_GET['tab']==15?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/health-fields/index.php?siteID=<?=$_GET['siteID']?>&tab=15&domid=<?=$domainID?>&siteName=<?=urlencode($siteName)?>'"><p>שדות הצהרות בריאות</p></div>

			<?php /*
			<div class="tab<?=($_GET['tab']==3?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/prices/index.php?siteID=<?=$_GET['siteID']?>&tab=3&innerTab=1&domid=<?=$domainID?>&siteName=<?=$siteName?>'"><p>תמחור</p></div>
			<div class="tab<?=($_GET['tab']==4?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/occupation/index.php?siteID=<?=$_GET['siteID']?>&tab=4&domid=<?=$domainID?>&siteName=<?=$siteName?>'"><p>תפוסה</p></div>
			<div class="tab<?=($_GET['tab']==5?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/orders/index.php?siteID=<?=$_GET['siteID']?>&tab=5&domid=<?=$domainID?>&siteName=<?=$siteName?>'"><p>הזמנות</p></div>
			<div class="tab<?=($_GET['tab']==8?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/accessoires/rank/index.php?siteID=<?=$_GET['siteID']?>&tab=8&domid=<?=$domainID?>&siteName=<?=$siteName?>'"><p>דירוג מאפיינים</p></div>
			<div class="tab<?=($_GET['tab']==9?" active":"")?>" onclick="window.location.href='/cms/moduls/minisites/articleAds/index.php?siteID=<?=$_GET['siteID']?>&tab=9&domid=<?=$domainID?>&siteName=<?=$siteName?>'"><p>מודעת כתבה</p></div>
			 */ ?>
			<?php } ?>
		</div>
	</div>
<?php } ?>