<?php
include_once "../../../bin/system.php";

$menuID=intval($_POST['menuID']);
$sql="SELECT * FROM search_homepage_langs WHERE id=".$menuID." AND langID=".intval($_POST['langID'])." AND domainID=".intval($_POST['domainID']);
$menu=udb::single_row($sql);


?>
<h2>ערוך תפריט</h2>
<div>
	<label for="menuTitle">כותרת: </label>
	<input type="text" name="menuTitle" value="<?=toHTML($menu['catName'])?>" id="menuTitle" placeholder="הזן כותרת">
</div>
<div>
	<label for="menuTitle">תת כותרת:</label>
	<input type="text" name="menuSubTitle" id="menuSubTitle" placeholder="הזן תת כותרת" value="<?=toHTML($menu['catSubTitle'])?>">
</div>
<div>
	<label for="menuTitle">קישור לדף חיפוש</label>
	<input type="text" name="catLink" id="catLink" placeholder="קישור לדף חיפוש" value="<?=toHTML($menu['catLink'])?>">
</div>
<div>
	<label for="menuTitle">טקסט כפתור</label>
	<input type="text" name="catButton" id="catButton" placeholder="טקסט כפתור" value="<?=toHTML($menu['catButton'])?>">
</div>
<div>
	<label for="limit">הגבל כמות</label>
	<input type="text" name="limit" id="limit" placeholder="הגבל כמות" value="<?=toHTML($menu['limitCount'])?>">
</div>
<div>
	<label style="float:right;" for="menuShow">מוצג באתר: </label>
	<input style="width:16px;height:16px;clear:both;float:right;" type="checkbox" value="1" <?=$menu['active']!=1?"":"checked"?> name="menuShow" id="menuShow">
</div>
<div>
	<label style="float:right;" for="slider">סליידר </label>
	<input style="width:16px;height:16px;clear:both;float:right;" type="checkbox" value="1" <?=$menu['ifSlider']!=1?"":"checked"?> name="slider" id="slider">
</div>

<div>
	<input style="width:80px;" type="button" class="sub" onclick="saveMenu()" id="addSave" value="שמור">
	<input style="width:80px;" type="button" class="sub" onclick="if(confirm('אתה בטוח רוצה למחוק את הפריט בתפריט????')){ removeMenus(<?=$menu['id']?>) }" id="removeMenu" value="מחק">
	<input style="width:80px;" type="button" class="sub" onclick="addMenuNew()" id="addNew" value="הוסף חדש">
</div>
