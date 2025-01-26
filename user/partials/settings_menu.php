<?
$subMenu2[] = array("url"=>"page=agreements", "name"=>"הסכמים");
$subMenu2[] = array("url"=>"page=settings", "name"=>"חוות דעת");

?>

<div class="topMenu" >
<?foreach($subMenu2 as $sub){
$active = ($_SERVER['QUERY_STRING'] == $sub["url"])? "active" : "";
?>
<a class="<?=$active?>" href="?<?=$sub["url"]?>"><?=$sub["name"]?></a>
<?}?>
</div>