<?php
include_once "../../../bin/system.php";

$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
?>
<html lang="he">
	<head>
		<meta charset="UTF-8">
	</head>
	<body style="padding:0;margin:0">
		<div class="modulFrame">
			<iframe src="/SiteManager__/tab-priceFrom.php?siteID=<?=$siteID?>" frameborder="0" width="100%" height="100%"></iframe>
		</div>
	</body>
</html>
