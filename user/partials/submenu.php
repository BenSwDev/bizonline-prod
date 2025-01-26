<?
$subMenu[1][] = array("url"=>"agreements", "name"=>"הסכמים");
$subMenu[1][] = array("url"=>"settings", "name"=>"חוות דעת");
if ($_CURRENT_USER->is_spa()) {
$subMenu[1][] = array("url"=>"orderTexts", "name"=>"טקסט בהזמנה");
$subMenu[1][] = array("url"=>"sites_cupons", "name"=>"קופונים");

}
$subMenu[1][] = array("url"=>"paytypes", "name"=>"ספקים ותשלומים");
$subMenu[1][] = array("url"=>"sources", "name"=>"מקורות הגעה");

######################################################

$subMenu[2][] = array("url"=>"orders&otype=order&orderStatus=active", "name"=>"פעילות");
$subMenu[2][] = array("url"=>"orders&otype=preorder&orderStatus=active", "name"=>"שיריונים");
$subMenu[2][] = array("url"=>"orders&orderSign=incomplete&otype=order&orderStatus=active", "name"=>"לחתימה");
$subMenu[2][] = array("url"=>"orders&otype=order&orderStatus=active&last", "name"=>"אחרונות");
$subMenu[2][] = array("url"=>"orders&from=".urlencode(date("d/m/Y"))."&orderStatus=active&sort=arrive", "name"=>"אירועים קרובים");
$subMenu[2][] = array("url"=>"orders&to=".urlencode(date("d/m/Y"))."&orderStatus=active&sort=past", "name"=>"אירועים שהיו");
$subMenu[2][] = array("url"=>"orders&orderStatus=cancel", "name"=>"הזמנות מבוטלות");

######################################################

$subMenu[3][] = array("url"=>"treaters", "name"=>"ניהול מטפלים");
$subMenu[3][] = array("url"=>"workers", "name"=>"ניהול עובדים");
$subMenu[3][] = array("url"=>"treatmentsrooms", "name"=>"הגדרת חדרים");
$subMenu[3][] = array("url"=>"treatments", "name"=>"טיפולי ספא");
$subMenu[3][] = array("url"=>"extras", "name"=>"תוספות");
$subMenu[3][] = array("url"=>"products", "name"=>"מוצרים");
$subMenu[3][] = array("url"=>"hours", "name"=>"שעות פעילות וחגים");
$subMenu[3][] = array("url"=>"paymentsettings", "name"=>"ניהול הגדרות");
$subMenu[3][] = array("url"=>"healthdec", "name"=>"הצהרות בריאות");
$subMenu[3][] = array("url"=>"bizpop_settings", "name"=>"תצוגת אונליין");

######################################################

$subMenu[4][] = array("url"=>"giftcards-log2&gc=1", "name"=>"רכישות ומימושים (ישיר)");
$subMenu[4][] = array("url"=>"giftcards-log&gc=1", "name"=>"רכישות ומימושים");
if(!$_CURRENT_USER->userType){
$subMenu[4][] = array("url"=>"giftcards&gc=1", "name"=>"ניהול גיפטקארד");
}


######################################################


$subMenu[5][] = array("url"=>"report_manage", "name"=>"דוחות ניהוליים");
$subMenu[5][] = array("url"=>"report_treatments", "name"=>"דוחות טיפולים");
$subMenu[5][] = array("url"=>"stats_treatments", "name"=>"סטטיסטיקות מטופלים");
$subMenu[5][] = array("url"=>"report_extras", "name"=>"דוחות תוספים");
$subMenu[5][] = array("url"=>"monthtotals", "name"=>"סיכום חודשי");
/*
$subMenu[5][] = array("url"=>"report_operative", "name"=>"דוחות תפעוליים");
$subMenu[5][] = array("url"=>"report_budget", "name"=>"דוחות תקציב");
$subMenu[5][] = array("url"=>"report_period", "name"=>"דוחות תקופה");*/


?>

<?
foreach($subMenu as $key => $subgroup){
	foreach($subgroup as $sub){
		$thisPage = (strpos($sub['url'],"&") !== false) ? explode('&',$sub['url'])[0] : $thisPage = $sub['url'];
		//echo $sub['url'].PHP_EOL;
		if($thisPage == $_GET["page"]){
			$menuType = $key;
			continue;
		}
		if($menuType) continue;
	}
}
?>



<?if($menuType){?>
	<div class="topMenu" >
	<?foreach($subMenu[$menuType] as $sub){
	$active = ($_SERVER['QUERY_STRING'] == "page=".$sub["url"])? "active" : "";
	?>
	<a class="<?=$active?>" href="?page=<?=$sub["url"]?>"><?=$sub["name"]?></a>
	<?}?>
	</div>
<?}?>