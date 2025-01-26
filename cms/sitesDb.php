<?php
require_once "../functions.php";
header('Content-Type: application/json');
	
	$langID = $_GET['langID'];
	ActivePage::init();

	$que = "SELECT `settlements_text`.`settlementID` AS id, `settlements_text`.`TITLE` AS name
	FROM `settlements_text` 
	INNER JOIN `sites` USING (settlementID)
	INNER JOIN `sites_domains` ON (`sites`.siteID = `sites_domains`.siteID)
	WHERE `sites_domains`.domainID=".ActivePage::$domainID." AND `sites_domains`.`active`=1 AND `settlements_text`.`LangID`=".$langID." GROUP BY `settlements_text`.`settlementID` ORDER BY `settlements_text`.`TITLE`";
	$cities = udb::full_list($que);


	$que = "SELECT `areas_text`.`areaID` AS id, `areas_text`.`TITLE` AS name
	FROM `areas_text` 
	INNER JOIN `settlements` USING (areaID)
	INNER JOIN `sites` USING (settlementID)
	INNER JOIN `sites_domains` ON (`sites`.siteID = `sites_domains`.siteID)
	WHERE `sites_domains`.domainID=".ActivePage::$domainID." AND `sites_domains`.`active`=1 AND `areas_text`.`LangID`=".$langID." GROUP BY `areas_text`.`areaID` ORDER BY `areas_text`.`TITLE`";
	$areas = udb::full_list($que);


	$que = "SELECT `main_areaID` AS id, `TITLE` AS name
	FROM `main_areas_text` 
	WHERE `LangID`=".$langID."
	ORDER BY `TITLE`";
	$mainAreas = udb::full_list($que);
	
	$que = "SELECT a.`attrID` as id, IF(a.`attrName` > '', a.`attrName`, b.`attrName`) as name
	FROM `attributes_langs` as `a`
	INNER JOIN `attributes` USING (attrID)
	INNER JOIN `attributes_domains` USING (attrID)
	INNER JOIN `attributes_langs` as `b` ON (a.attrID = b.attrID AND a.langID = b.langID AND b.domainID = 0)
	WHERE `attributes_domains`.`active`=1 AND `attributes_domains`.domainID=".ActivePage::$domainID." AND `a`.`langID`=".$langID." GROUP BY `id` ORDER BY name";
	$facilities = udb::full_list($que);


	$areasjson = ['cities' => $cities, 'areas' => $areas, 'facilities'=> $facilities, 'mainAreas' => $mainAreas];
	
	print_r(json_encode($areasjson));



?>