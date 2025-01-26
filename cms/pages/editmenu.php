<?
include_once "../bin/system.php";

$menuID=intval($_POST['menuID']);
$sql="SELECT * FROM categories WHERE menuID=".$menuID."";
$menu=udb::single_row($sql);

if($menu['menuType'] == 1){
$type = 22;
}

if($menu['menuType'] == 2){
$type = 32;
}


?>
<h2>ערוך תפריט</h2>
<div>
	<label for="menuTitle">כותרת: </label>
	<input type="text" name="menuTitle" value="<?=$menu['menuTitle']?>" id="menuTitle" placeholder="הזן כותרת">
</div>

<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
	<div class="section">
		<div class="inptLine">
			<div class="label">תמונה: </div>
			<input type="file" name="picture" class="inpt" value="<?=$menu['picture']?>">
		</div>
	</div>
	<?php if($menu['picture']){ ?>
	<div class="section">
		<div class="inptLine">
			<img src="../../gallery/<?=$menu['picture']?>" style="width:100%">
		</div>
	</div>
	<?php } ?>
</div>
<?php /*
<div>
	<label for="menuLink">קישור: </label>
	<input type="text" name="menuLink" value="<?=$menu['menuLink']?>" id="menuLink" placeholder="הזן קישור">
</div> */?>

<div>
	<label for="menuPage">דף תוכן: </label>
	<?php 
		if($menu['LangID']!=1){
			$que="SELECT MainPages_text.MainPageID, MainPages_text.MainPageTitle FROM MainPages 
				  INNER JOIN MainPages_text ON(MainPages.MainPageID = MainPages_text.MainPageID AND MainPages_text.LangID=".$menu['LangID']." AND MainPages_text.MainPageTitle!='' AND MainPages_text.ifShow=1) 
				  WHERE MainPageType=".$type;
		} else {
			$que="SELECT MainPages.MainPageID, MainPages.MainPageTitle FROM MainPages WHERE MainPageType=".$type." AND LangID=".$menu['LangID'];
		}


		//$que="SELECT * FROM MainPages WHERE MainPageType IN (1,5,6,7) AND ifShow=1 AND PortalID=".$menu['PortalID']." AND LangID=".$menu['LangID'];
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

<?/* 
<div>
	<label style="float:right;" for="menuShow">מוצג באתר: </label>
	<input style="width:16px;height:16px;clear:both;float:right;" type="checkbox" value="1" <?=$menu['menuShow']!=1?"":"checked"?> name="menuShow" id="menuShow">
</div>*/?>
<!-- <div>
	<label style="float:right;" for="menuTargetBlank">פתיחה בחלון חדש: </label>
	<input style="width:16px;height:16px;clear:both;float:right;" type="checkbox" value="1" <?=$menu['menuTargetBlank']!=1?"":"checked"?> name="menuTargetBlank" id="menuTargetBlank">
</div> -->

<div>
	<input style="width:80px;" type="button" class="sub" onclick="saveMenu()" id="addSave" value="שמור">
	<input style="width:80px;" type="button" class="sub" onclick="if(confirm('אתה בטוח רוצה למחוק את הפריט בתפריט????')){ removeMenus(<?=$menu['menuID']?>) }" id="removeMenu" value="מחק">
	<input style="width:80px;" type="button" class="sub" onclick="addMenuNew()" id="addNew" value="הוסף חדש">
</div>