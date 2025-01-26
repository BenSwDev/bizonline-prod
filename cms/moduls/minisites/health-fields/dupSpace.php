<?php
include_once "../../../bin/system.php";
include_once "../../../_globalFunction.php";


const BASE_LANG_ID = 1;


$roomId = intval($_GET['roomID']);
$siteID = intval($_GET['siteID']);
$spaceID = intval($_GET['spaceID']);

$data = udb::single_row("select * from spaces where spaceID=".$spaceID);
$attrCheck = udb::full_list("select * from spaces_accessories where spaceID=".$spaceID);

$newSpaceName = $data['spaceName'];
$newSpaceNameArr = explode(" ", $newSpaceName);

foreach($newSpaceNameArr as &$Item) {
	if(intval($Item)) {
		$ItemPlus = intval($Item) + 1;
		$newSpaceName = str_replace($Item,$ItemPlus,$newSpaceName);
	}
}

if($newSpaceName != $data['spaceName']) {
	$data['spaceName'] = $newSpaceName;
}
else {
	$data['spaceName'] = $data['spaceName'] . " חדש";	
	$newSpaceName  = $data['spaceName'] . " חדש";	
}


// main site data
$siteData = [

	'spaceName' => $data['spaceName'],
	'spaceType' => $data['spaceType'],
	'spaceDesc' => $data['spaceDesc'],
	'roomID'    => $roomId
];



$NewspaceID = udb::insert('spaces', $siteData);

//insert note to accessory if has one
foreach($attrCheck as $key => $descToAttr){
	foreach(LangList::get() as $lid => $lang){
		$descToAttr  = udb::full_list("select * from spaces_accessories_langs where spaceID=".$spaceID.' and langID='.$lid);
		if($descToAttr){
			foreach($descToAttr as $descToAttrOne) {
				udb::insert('spaces_accessories_langs', [
					'spaceID'    => $NewspaceID,
					'langID'    => $lid,
					'accessoryID' => $descToAttrOne['accessoryID'],
					'translate'  => $descToAttrOne['translate']  
				], true);	
			}
			
		}
	}
}
//saving spaces_accessories
if(count($attrCheck)){
	
	$spaAcc = [];
	foreach($attrCheck as $att){
		$spaAcc['accessoryID'] = $att['accessoryID'];
		$spaAcc['spaceID'] = $NewspaceID;
		udb::insert('spaces_accessories', $spaAcc);
	}		
}
//*save here*//


// saving data per language
foreach(LangList::get() as $lid => $lang){
	$spaceLang  = udb::single_row("select * from spaces_langs where spaceID=".$spaceID.' and langID='.$lid);
	

	udb::insert('spaces_langs', [
		'spaceID'    => $NewspaceID,
		'langID'    => $lid,
		'spaceName'  => $newSpaceName,
		'spaceDesc'  => $spaceLang['spaceDesc'] ?  $spaceLang['spaceDesc'] : $data['spaceDesc'], 
		'spaceNotes' => $spaceLang['spaceNotes']
	]);
}
header("Location: /cms/moduls/minisites/rooms/popRoom.php?roomID=".$roomId."&siteID=".$siteID."&openunits=true");