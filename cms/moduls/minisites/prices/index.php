<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "innerMenu.php";
include_once "../../../_globalFunction.php";


$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];

?>



<div class="editItems">
	<div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
	<?=showTopTabs(0)?>
	<?=innerMenu()?>


	<?php
		switch(intval($_GET['innerTab'])){

			case 1: include "extras/index.php";break;
			//case 2: include "bonus/index.php";break;
			case 2: include "extras/fromPrice.php";break;


			default: include "extras/index.php";

		}



		?>
</div>
