<?
include_once "../../bin/system.php";

$menuID=intval($_POST['menuID']);

$sql="SELECT * FROM menu WHERE menuID=".$menuID."";
$menu=udb::single_row($sql);

if($menu){
	$sql="SELECT * FROM menu WHERE menuParent=".$menuID."";
	$checkSub=udb::full_list($sql);
	if($checkSub){
		$cp=Array();
		$cp['menuParent']=$menu['menuParent'];
		$cp['menuOrder']=$menu['menuOrder'];
		udb::update("menu", $cp, "menuParent=".$menuID." ");
	}
	udb::query("DELETE FROM `menu` WHERE menuID=".$menuID."");
}