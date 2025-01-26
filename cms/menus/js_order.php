<?
include_once "../bin/system.php";

$order = json_decode(str_replace("\\","", $_POST['order']), true);

foreach($order as $key=>$ord){
	
	$cp=Array();
	$cp['menuOrder']=$key;
	$cp['menuParent']=0;
	udb::update("menu", $cp, "menuID=".$ord['id']."");

	if($ord['children']){
		foreach($ord['children'] as $k=>$or){
			$cp=Array();
			$cp['menuOrder']=$k;
			$cp['menuParent']=$ord['id'];
			udb::update("menu", $cp, "menuID=".$or['id']."");

			if($or['children']){
				foreach($or['children'] as $k=>$o){
					$cp=Array();
					$cp['menuOrder']=$k;
					$cp['menuParent']=$or['id'];
					udb::update("menu", $cp, "menuID=".$o['id']."");
				}
			}
		}
	}
}