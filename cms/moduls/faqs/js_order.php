<?
include_once "../../bin/system.php";

if(isset($_POST['ids'])){
	$ids =  $_POST['ids'];

	foreach($ids as $keyCat=>$cat){
		$queryC=Array();
		if(! $cat['type']){
			$queryC['showOrder']=$keyCat;
			udb::update('questions', $queryC, "questionID=".$cat['id']);

			foreach($cat['children'] as $keyAttr=>$attr){
				$queryA=Array();
				$queryA['showOrder']=$keyAttr;
				$queryA['questionID']=$cat['id'];
				$str = udb::update('answers', $queryA, "ansID=".$attr['id']);
			//	echo $str.'\n';
				echo 'success-'.$attr['id'];
			}
			echo 'success category\n';
		}
		else{
			echo 'false';
		}
	}
}
