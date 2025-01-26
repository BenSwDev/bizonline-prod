<?
$subMenu2[] = array("url"=>"page=orders&otype=order&orderStatus=active", "name"=>"פעילות");
$subMenu2[] = array("url"=>"page=orders&otype=preorder&orderStatus=active", "name"=>"שיריונים");
$subMenu2[] = array("url"=>"page=orders&orderSign=incomplete&otype=order&orderStatus=active", "name"=>"לחתימה");
$subMenu2[] = array("url"=>"page=orders&otype=order&orderStatus=active&last", "name"=>"אחרונות");
$subMenu2[] = array("url"=>"page=orders&from=".urlencode(date("d/m/Y"))."&orderStatus=active&sort=arrive", "name"=>"אירועים קרובים");
$subMenu2[] = array("url"=>"page=orders&to=".urlencode(date("d/m/Y"))."&orderStatus=active&sort=past", "name"=>"אירועים שהיו");
$subMenu2[] = array("url"=>"page=orders&orderStatus=cancel", "name"=>"ממתין לביטול");

?>

<div class="topMenu" >
<?foreach($subMenu2 as $sub){
$active = ($_SERVER['QUERY_STRING'] == $sub["url"])? "active" : "";
?>
<a class="<?=$active?>" href="?<?=$sub["url"]?>"><?=$sub["name"]?></a>
<?}?>
</div>