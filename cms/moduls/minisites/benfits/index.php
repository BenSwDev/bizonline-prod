<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";


$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];
$domainID =intval($_GET['frame']);
?>


<div class="popRoom">
	<div class="popRoomContent"></div>
</div>
<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
	<?=showTopTabs(0)?>
	<div class="manageItems" id="manageItems">
		<h1>מבצעים</h1>
		<div style="margin-top: 20px;">
			<iframe src="/user/?siteID=<?=$siteID?>" frameborder="0" width="100%" height="100%" style="min-height:600px" id="iframe1"></iframe>
		</div>
	</div>
</div>
<script>
    $(document).ready(function () {
        $.ajax({
            method:'post',
            url: 'ajax_actions.php',
            data: {act: 'get-ul-ink'},
            success:function (res) {
                try {
                    var response = JSON.parse(res);
                } catch (e) {
                    var response = res;
                }

                $("#iframe1").attr("src",response.link + "?page=sales-banners&siteID=<?=$siteID?>&frame=1");
            }
        });

    });
</script>