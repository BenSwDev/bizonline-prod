<?
include_once "../bin/system.php";

$commentID=intval($_POST['commentID']);

$que="SELECT commentID, ifShow FROM Comments WHERE commentID=".$commentID." ";
$comment=udb::single_row($que);


$query=Array();
$query['ifShow']=($comment['ifShow']==1?"0":"1");
udb::update("Comments", $query, "commentID=".$commentID."");

if($comment['ifShow']==1){
	echo '<span style="color:red">לא</span>';
} else {
	echo '<span style="color:green">כן</span>';
}