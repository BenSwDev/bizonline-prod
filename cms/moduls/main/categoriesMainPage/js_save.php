<?php
include_once "../../../bin/system.php";

$menuID = intval($_POST['menuID']);
/*
if($_POST['catLink']){
    $path = parse_url($_POST['catLink'], PHP_URL_PATH);

    ActivePage::route($path);

    if (ActivePage::$page['table']!= 'search')
        die('קישור לא תקין');

}
elseif (!$menuID)
    die('קישור לא תקין');
*/
$cp=Array();
$cp['catName']=$_POST['menuTitle'];
$cp['langID']=intval($_POST['LangID']);
$cp['domainID']=intval($_POST['domainID']);

$cplang=Array();
$cplang['langID']=intval($_POST['LangID']);
$cplang['domainID']=intval($_POST['domainID']);
$cplang['catName']=$_POST['menuTitle'];
$cplang['catSubTitle']=$_POST['menuSubTitle'];
$cplang['catButton']=$_POST['catButton'];
$cplang['limitCount']=intval($_POST['limit']);
$cplang['active']=intval($_POST['menuShow'])?intval($_POST['menuShow']):0;
$cplang['ifSlider']=intval($_POST['slider'])?intval($_POST['slider']):0;

if ($_POST['catLink']){
    $cplang['catLink']  = $_POST['catLink'];
    $cplang['searchID'] = ActivePage::$page['ref'];

 /*   $json = udb::single_value("SELECT `data` FROM `search` WHERE `id` = " . ActivePage::$page['ref']);
    $data = json_decode($json, true);*/

}

if($menuID){
	udb::update("search_homepage", $cp, "id=".$menuID."");
	
} else {
	$cp['showOrder'] = 999;
	$menuID = udb::insert("search_homepage", $cp);
}
$cplang['id'] = $menuID;
udb::insert("search_homepage_langs", $cplang, true);

//CacheHomepage::recount($menuID);
//CacheHomepage::rebuild();

$sql="SELECT * FROM `search_homepage` WHERE id=".$menuID;
$menu=udb::single_row($sql);

echo $menu['id'].'@@@@'.$menu['catName'];
