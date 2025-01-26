<?
include_once "../../bin/system.php";

$menuID=intval($_POST['menuID']);
$menuType = intval($_POST['menuType'])?intval($_POST['menuType']):1;
echo $_GET['menuType'];
$sql="SELECT * FROM menu WHERE menuID=".$menuID."";
$menu=udb::single_row($sql);



?>
<h2>ערוך תפריט</h2>

<?/* ?>
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
				$que="SELECT MainPages_text.MainPageID, MainPages_text.mainPageTitle FROM MainPages 
					  INNER JOIN MainPages_text ON(MainPages.MainPageID = MainPages_text.MainPageID AND MainPages_text.LangID=".$menu['LangID']." AND MainPages_text.mainPageTitle!='' AND MainPages_text.ifShow=1) 
					  WHERE mainPageType IN (2)";
			} 
			else
			{
				$que="SELECT * FROM MainPages WHERE mainPageType IN (2) AND ifShow=1 AND LangID=".$menu['LangID'];
				
			}
		}
		$pages=udb::full_list($que);
		

		if($pages){
		echo "<select name='menuPage'>";
		echo "<option value='0'>-</option>";
		foreach($pages as $page){
		echo "<option value='".$page['mainPageID']."' ".($page['mainPageID']==$menu['menuPage']?"selected":"").">".outDb($page['mainPageTitle'])."</option>";
		}

		echo "</select>";
		}
	?>
</div>
<? */ ?>


<div id="freeBox" class="searchWrap">
	<input type="hidden" value="<?=$menu['menuPage']?>" id="freeSearchParam" name="freeSearchParam" />
	<input type="hidden" value="<?=$menu['menuSearch']?>" id="freeSearchType" name="freeSearchType" />
	<input type="hidden" value="<?=addslashes(htmlspecialchars($menu['menuTitle']))?>" id="menuTitle" name="menuTitle" />
	<div class="wrapToIco">
		<i class="icon-category"></i>
		<input type="text" placeholder="" name="free" autocomplete="off" id="freeInput" value="<?=addslashes(htmlspecialchars($menu['menuTitle']))?>">
	</div>
	<div class="autoBox">
		<div class="autoSuggest-mobc"></div>
		<div class="autoComplete"></div>
		
	</div>
	<div class="autoSuggest"></div>
	
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

<script type="text/javascript">

	 var freeAC;
 freeAC = new AComplete({
	container: "#freeBox",
	data: WebHandler.searchArray,
	builder: function (obj, value, box) {
		if(box == '.autoSuggest') return false;
		return $('<div class="autoCompleteWrap"><div class="autoSuggestName">' + value + '</div><div class="autoSuggestCount"></div></div>').attr(obj).prop('outerHTML');
	},
	onSelect: function (elem) {
		var id = $(elem).attr('id'), title = $(elem).attr('name'), type = $(elem).attr('type'), data = $(elem).data();

        if (id && type){
            $('#freeSearchParam').val(id);
            $('#freeSearchType').val(type);
            $('#freeInput').val(title);
            $('#menuTitle').val(title);
            $('#freeBox').removeClass('active');
        }
        else if (data.id && data.freeType) {
            $('#freeSearchParam').val(data.id);
            $('#freeSearchType').val(data.freeType);
            $('#freeInput').val(data.name);
            $('#menuTitle').val(data.name);
            $('#freeBox').removeClass('active');
        }
	}
});


</script>