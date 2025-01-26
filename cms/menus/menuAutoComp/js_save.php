<?
include_once "../../bin/system.php";

$menuID = intval($_POST['menuID']);



$cp=Array();
$cp['menuType']=intval($_POST['menuType']);
$cp['LangID']=intval($_POST['LangID']);
$cp['menuTitle']=($_POST['menuTitle']?$_POST['menuTitle']:$_POST['free']);
//$cp['menuLink']=$_POST['menuLink'];
$cp['menuPage']=$_POST['freeSearchParam'];
$cp['menuSearch']=$_POST['freeSearchType'];
$cp['menuShow']=intval($_POST['menuShow'])?intval($_POST['menuShow']):0;
//$cp['menuTargetBlank']=intval($_POST['menuTargetBlank'])?intval($_POST['menuTargetBlank']):0;


if($menuID){
	udb::update("menu", $cp, "menuID=".$menuID."");
} else {
	$cp['menuOrder'] = 999;
	$menuID = udb::insert("menu", $cp);
}

$sql="SELECT * FROM menu WHERE menuID=".$menuID;
$menu=udb::single_row($sql);

echo $menu['menuID'].'@@@@'.$menu['menuTitle'];