<?
include_once "../../bin/system.php";

if(isset($_POST['ids'])){
	$ids =  $_POST['ids'];
    $domid = intval($_POST['domid']);

	foreach($ids as $keyCat=>$cat){
		$queryC=Array();
		if(! $cat['type']){
			$queryC['showOrder']=$keyCat;
			udb::update('attributes_categories', $queryC, "categoryID=".$cat['id'] . " AND `domainID` = " . $domid);
			if($cat['children']){
				foreach($cat['children'] as $keyAttr=>$attr){
					$queryA=Array();
					$queryA['showOrder']=$keyAttr;
					$queryA['categoryID']=$cat['id'];
					$str = udb::update('attributes_domains', $queryA, " attrID=".$attr['id'] . " AND `domainID` = " . $domid);
				//	echo $str.'\n';
					echo 'success-'.$attr['id'];
				}
			}
			echo '/success category-'.$cat['id'];
		}
		else{
			echo 'false';
		}
	}
}
