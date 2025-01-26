<?
include_once "../bin/system.php";
print_R($_POST);
$menuID = intval($_POST['menuID']);



$cp=Array();
$cp['menuType']=intval($_POST['menuType']);
$cp['LangID']=intval($_POST['LangID']);
$cp['menuTitle']=$_POST['menuTitle'];
$cp['menuLink']=$_POST['menuLink'];
$cp['menuPage']=$_POST['menuPage'];
$cp['menuSearch']=$_POST['menuSearch'];
$cp['menuShow']=intval($_POST['menuShow'])?intval($_POST['menuShow']):0;
$cp['menuTargetBlank']=intval($_POST['menuTargetBlank'])?intval($_POST['menuTargetBlank']):0;

$photo = pictureUpload('picture',"../../gallery/");
if($photo){
	$cp["picture"] = $photo[0]['file'];
}

if($menuID){
	udb::update("categories", $cp, "menuID=".$menuID."");
} else {
	$cp['menuOrder'] = 999;
	$menuID = udb::insert("categories", $cp);
}



$sql="SELECT * FROM categories WHERE menuID=".$menuID;
$menu=udb::single_row($sql);

echo $menu['menuID'].'@@@@'.$menu['menuTitle'];