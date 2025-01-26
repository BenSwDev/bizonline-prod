<?
include_once "../bin/system.php";

$menuID=intval($_POST['menuID']);
$menuType = intval($_POST['menuType'])?intval($_POST['menuType']):1;
echo $_GET['menuType'];
$sql="SELECT * FROM menu WHERE menuID=".$menuID."";
$menu=udb::single_row($sql);

$domainID = $menu['domainID'];
$langID = $menu['LangID'];

?>
<h2>ערוך תפריט</h2>
<div>
	<label for="menuTitle">כותרת: </label>
	<input type="text" name="menuTitle" value="<?=addslashes(htmlspecialchars($menu['menuTitle']))?>" id="menuTitle" placeholder="הזן כותרת">
</div>
<div>
	<label for="menuLink">קישור: </label>
	<input type="text" name="menuLink" value="<?=$menu['menuLink']?>" id="menuLink" placeholder="הזן קישור">
</div>
<div>
	<label for="menuPage">דף תוכן: </label>
	<?php


		if($menuType==1)
		{
			if($menu['LangID']!=1)
			{
				$que="SELECT MainPages_text.MainPageID, MainPages_text.mainPageTitle FROM MainPages 
					  INNER JOIN MainPages_text ON(MainPages.MainPageID = MainPages_text.MainPageID AND MainPages_text.LangID=".$menu['LangID']." AND MainPages_text.mainPageTitle!='' AND MainPages_text.ifShow=1) 
					  WHERE mainPageType IN (1)";
			}
			else
			{
				$que="SELECT * FROM MainPages WHERE mainPageType IN (1) AND ifShow=1 AND LangID=".$menu['LangID'];
			}
		}
		if($menuType==5)
		{
			if($menu['LangID']!=1)
			{
				$que="SELECT MainPages_text.MainPageID as mainPageID, MainPages_text.mainPageTitle FROM MainPages 
					  INNER JOIN MainPages_text ON(MainPages.MainPageID = MainPages_text.MainPageID AND MainPages_text.LangID=".$menu['LangID']." AND MainPages_text.mainPageTitle!='' AND MainPages_text.ifShow=1) 
					  WHERE mainPageType IN (2)";
			}
			else
			{
				$que="SELECT * FROM MainPages WHERE mainPageType IN (2) AND ifShow=1 AND LangID=".$menu['LangID'];

			}
		}
		else {
        $que="SELECT MainPages_text.MainPageID, MainPages_text.MainPageTitle FROM MainPages 
									  INNER JOIN MainPages_text ON(MainPages.MainPageID = MainPages_text.MainPageID AND MainPages_text.LangID=".$langID."  AND MainPages_text.domainID=" . $domainID . " AND MainPages_text.MainPageTitle!='' AND MainPages_text.ifShow=1) 
									  WHERE MainPageType IN (1)";
         }
		$pages=udb::full_list($que);


		if($pages){
		echo "<select name='menuPage'>";
		echo "<option value='0'>-</option>";
		foreach($pages as $page){
		echo "<option value='".$page['MainPageID']."' ".($page['MainPageID']==$menu['menuPage']?"selected":"").">".outDb($page['MainPageTitle'])."</option>";
		}

		echo "</select>";
		}
	?>
</div>


<div>
	<label style="float:right;" for="menuShow">מוצג באתר: </label>
	<input style="width:16px;height:16px;clear:both;float:right;" type="checkbox" value="1" <?=$menu['menuShow']!=1?"":"checked"?> name="menuShow" id="menuShow">
</div>
<div>
	<label style="float:right;" for="menuTargetBlank">פתיחה בחלון חדש: </label>
	<input style="width:16px;height:16px;clear:both;float:right;" type="checkbox" value="1" <?=$menu['menuTargetBlank']!=1?"":"checked"?> name="menuTargetBlank" id="menuTargetBlank">
</div>

<?php if($menuType == 555) { ?>
<div>
	<label style="float:right;" for="showOnMainPage">הצג קטגוריה בדף הראשי של המגזין </label>
	<input style="width:16px;height:16px;clear:both;float:right;" type="checkbox" value="1"  name="showOnMainPage" id="showOnMainPage">
</div>
<?php } ?>

<div>
	<input style="width:80px;" type="button" class="sub" onclick="saveMenu()" id="addSave" value="שמור">
	<input style="width:80px;" type="button" class="sub" onclick="if(confirm('אתה בטוח רוצה למחוק את הפריט בתפריט????')){ removeMenus(<?=$menu['menuID']?>) }" id="removeMenu" value="מחק">
	<input style="width:80px;" type="button" class="sub" onclick="addMenuNew()" id="addNew" value="הוסף חדש">
</div>