<?
include_once "../bin/system.php";

$menuID = intval($_POST['menuID']);



$cp=Array();
$cp['menuType']=intval($_POST['menuType']);
$cp['LangID']=intval($_POST['LangID']);
$cp['domainID']=intval($_POST['domainID']);
$cp['menuTitle']=$_POST['menuTitle'];
$cp['menuLink']=$_POST['menuLink'];
$cp['menuPage']=$_POST['menuPage'];
//$cp['menuSearch']=$_POST['menuSearch'];
$cp['menuShow']=intval($_POST['menuShow'])?intval($_POST['menuShow']):0;
$cp['menuTargetBlank']=intval($_POST['menuTargetBlank'])?intval($_POST['menuTargetBlank']):0;
$cp['showOnMainPage']=intval($_POST['showOnMainPage'])?intval($_POST['showOnMainPage']):0;

if($menuID){
	udb::update("menu", $cp, "menuID=".$menuID."");
} else {
	$cp['menuOrder'] = 999;
	$menuID = udb::insert("menu", $cp);
}

$sql="SELECT * FROM menu WHERE menuID=".$menuID;
$menu=udb::single_row($sql);

echo $menu['menuID'].'@@@@'.$menu['menuTitle'];