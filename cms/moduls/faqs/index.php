<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";

$menusList = "שאלות ותשובות";

/*

$menuType = intval($_GET['menuType'])?intval($_GET['menuType']):1;
$langID = intval($_GET['langID'])?intval($_GET['langID']):1;

$que="SELECT * FROM menu WHERE menuType=".$menuType." AND menuParent=0 AND LangID=".$langID." ORDER BY menuOrder";
$menu=udb::full_list($que);

 
$que="SELECT LangID, LangName FROM language WHERE 1";
$languages = udb::full_list($que);

*/

$categories = udb::key_row("SELECT * FROM `questions` WHERE 1 ORDER BY `showOrder`", 'questionID');
$answers = udb::key_list("SELECT * FROM `answers` WHERE 1 ORDER BY showOrder", 'questionID');


?>
<div class="pagePop"><div class="pagePopCont"></div></div>

<div class="manageItems" id="manageItems">
    <h1>שאלות ותשובות</h1>
	<div class="editItems">
		<h1><?=$menuList?></h1>
		<form id="manageMenus">
			<input type="hidden" value="" name="menuID" id="menuID">
			<div class="rightSide" id="rightSide">
				<h2>הוסף ערך</h2>
				<div>
					<input style="display: inline-block;vertical-align: top;width: auto;" type="button" class="sub" onclick="openCat(0)" value="הוסף שאלה">
					<input style="display: inline-block;vertical-align: top;width: auto;" type="button" class="sub" onclick="openPop(0)" value="הוסף תשובה">
				</div>
			</div>
			<div class="mainCtrl">
				<h2>סדר תצוגה</h2>
				<div class="dd" id="sortContinaer">
					<ol class="dd-list catlist">
<?php
    foreach($categories as $category){
        $red = $category['active'] ? '' : 'style="color:darkred"';
?>
						<li class="dd-item dd3-item cat" data-id="<?=$category['questionID']?>">
							<div class="dd-handle dd3-handle"></div>
							<div class="dd3-content" onclick="openCat(<?=$category['questionID']?>)" <?=$red?>><?=$category['questionTitle']?></div>

<?php
        if ($answers[$category['questionID']]){
            echo '<ol class="dd-list attrlist">';

            foreach($answers[$category['questionID']] as $attr){
?>
								<li class="dd-item dd3-item attr" data-id="<?=$attr['ansID']?>" data-type="child">
									<div class="dd-handle dd3-handle"></div>
									<div class="dd3-content" onclick="openPop(<?=$attr['ansID']?>)"><?=$attr['answerTitle']?>
									
									
									</div>
								</li>
<?php
            }

            echo '</ol>';
        }
?>
						</li>
<?php
    }
?>
					</ol>
				</div>
				<!-- <div style="width:100%;overlfow:hidden;clear:both;"></div>
				<input type="button" class="sub" onclick="saveAll()" value="שמור סדר" style="width:200px;clear:both;"> -->
			</div>
		</form>
	</div>
</div>
<!-- <script src="<?=$root?>/app/jquery.nestable_range.js"></script> -->
<script src="<?=$root?>/app/jquery0.nestable.js"></script>
<script>
function openPop(pageID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/faqs/frame.php?pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function openCat(pageID){
    $(".pagePopCont").html('<iframe id="frame_cat_'+pageID+'" frameborder=0 src="/cms/moduls/faqs/frame_cat.php?pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_cat_'+pageID+'\')">x</div>');
    $(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}

    $("#sortContinaer").nestable({
/*        group: 1,
        itemClass: 'catlist',
        listClass: 'cat',
        maxDepth: 2
    });

    $(".sublist").nestable({
        group: 2,
        itemClass: 'attrlist',
        listClass: 'attr',*/

        dropCallback   : function(e){
            var type = e.sourceEl[0].dataset.type;
            if(type != undefined){
                if(e.destId == null){
                   window.location = location.href;
                    return false;
                }
            }
        },
        maxDepth: 2
}).on('change', function(e) {
    $.ajax({
        url: 'js_order.php',
        type: 'POST',
        data: {ids:$(this).nestable('serialize')},
        async: false,
        success: function (myData) {
            //window.location.reload();
        }
    });


});


/*
 saveAll();
    var i = 1;
    function addMenu(){

	var formData = new FormData($("#manageMenus")[0]);
	$.ajax({
		url: 'http://'+window.location.hostname+'/cms/menus/js_save.php',
		type: 'POST',
		data: formData,
		async: false,
		cache: false,
		contentType: false,
		processData: false,
		success: function (returndata) {
			if(returndata){
				var returntext = returndata.split("@@@@");

				$("#sortContinaer > ol").append('<li class="dd-item dd3-item" data-id="'+returntext[0]+'"><div class="dd-handle dd3-handle"></div><div class="dd3-content" onclick="editMenu('+returntext[0]+')">'+returntext[1]+'</div></li>');
				console.log($("#sortContinaer").nestable('serialize'));

				$("#manageMenus")[0].reset();
				$("#menuType").val(<?=$menuType?>);

			}
		}
	});


      //$("#sortContinaer > ol").append('<li class="dd-item" data-id="'+i+'"><div class="dd-handle"><div>'+$("#itemName").val()+'</div></div></li>');
       // i++;
      //  console.log($("#sortContinaer").nestable('serialize'));
    }
    function saveMenu(){

		var formData = new FormData($("#manageMenus")[0]);
		$.ajax({
			url: 'http://'+window.location.hostname+'/cms/menus/js_save.php',
			type: 'POST',
			data: formData,
			async: false,
			cache: false,
			contentType: false,
			processData: false,
			success: function (returndata) {
				if(returndata){
					window.location.reload();
				}
			}
		});
    }




$('.dd').on('change', function() {
   saveAll();
});


    function saveAll(){
	 //alert(JSON.stringify($("#sortContinaer").nestable('serialize')))
	 var arrayOrder = (JSON.stringify($("#sortContinaer").nestable('serialize')))

	$.ajax({
		url: 'http://'+window.location.hostname+'/cms/menus/js_order.php?menuType=<?=$menuType?>',
		type: 'POST',
		data: {order:arrayOrder},
		async: false,
		success: function (myData) {

		}
	});

    }

	function addMenuNew(){
		$("#rightSide > h2").html("הוסף ערך לתפריט");
		$("#manageMenus").find("input[type=text], textarea").val("");
		$("#menuID").val(0);


		$("#menuType").val(<?=$menuType?>);
		$("#addSave").val("הוסף");
		$("#addNew").hide();
		$("#removeMenu").hide();
	}

	function removeMenus(menuID){
		$.ajax({
			url: 'http://'+window.location.hostname+'/cms/menus/js_delete.php',
			type: 'POST',
			data: {menuID:menuID},
			async: false,
			success: function (myData) {
				window.location.reload();
			}
		});
	}


	function editMenu(menuID){
		var meID = menuID;
		$.ajax({
			url: 'http://'+window.location.hostname+'/cms/menus/editmenu.php',
			type: 'POST',
			data: {menuID:meID},
			async: false,
			success: function (myData) {
				$("#menuID").val(meID);
				$("#rightSide").html(myData);
			}
		});
	}*/
</script>
<style>
    .rightSide{float: right;width: 300px;}
    .rightSide > div{overflow:hidden;}
    .rightSide h2{font-size: 16px; font-weight: bold;margin-bottom: 10px;}
    .rightSide label{margin-bottom: 5px;margin-top: 10px;}
    .sub{line-height: 22px;height: 26px;border: 0;border-radius: 3px;box-sizing: border-box;outline: none;padding: 0 5px;margin: 0 auto;width: 98%;margin-top:20px !important;color: #ffffff;font-weight: bold;background: #2FC2EB !important;font-size: 16px;text-shadow: -1px 1px 0 rgba(0, 0, 0, 0.1);border-bottom: 2px solid rgba(0, 0, 0, 0.1);cursor: pointer;box-shadow: none;-moz-transition: all 0.25s;-webkit-transition: all 0.25s;transition: all 0.25s;}
    .sub:hover{background: #34AFD2 !important;}
    .mainCtrl{margin-right: 310px;overflow:hidden;}
    .mainCtrl h2{font-size: 16px; font-weight: bold;margin-bottom: 20px;}

    .dd { position: relative; display: block; margin: 0; padding: 0; max-width: 600px; list-style: none; font-size: 13px; line-height: 20px; }

    .dd-list { display: block; position: relative; margin: 0; padding: 0; list-style: none; }
    
	.dd-list .dd-list { padding-right: 30px; }
    .dd-collapsed .dd-list { display: none; }
	/*ol.dd-list.catlist > .dd-placeholder { display: none;}*/
    .dd-item,
    .dd-empty,
    .dd-placeholder { display: block; position: relative; margin: 0; padding: 0; min-height: 20px; font-size: 13px; line-height: 20px; }

    .dd-handle { display: block; height: 30px; margin: 5px 0; padding: 5px 10px; color: #333; text-decoration: none; font-weight: bold; border: 1px solid #ccc;
        background: #fafafa;
        background: -webkit-linear-gradient(top, #fafafa 0%, #eee 100%);
        background:    -moz-linear-gradient(top, #fafafa 0%, #eee 100%);
        background:         linear-gradient(top, #fafafa 0%, #eee 100%);
        -webkit-border-radius: 3px;
        border-radius: 3px;
        box-sizing: border-box; -moz-box-sizing: border-box;
    }
    .dd-handle:hover { color: #2ea8e5; background: #fff; }

    .dd-item > button { display: block; position: relative; cursor: pointer; float: left; width: 25px; height: 20px; margin: 5px 0; padding: 0; text-indent: 100%; white-space: nowrap; overflow: hidden; border: 0; background: transparent; font-size: 12px; line-height: 1; text-align: center; font-weight: bold; }
    .dd-item > button:before { content: '+'; display: block; position: absolute; width: 100%; text-align: center; text-indent: 0; }
    .dd-item > button[data-action="collapse"]:before { content: '-'; }

    .dd-placeholder,
    .dd-empty { margin: 5px 0; padding: 0; min-height: 30px; background: #f2fbff; border: 1px dashed #b6bcbf; box-sizing: border-box; -moz-box-sizing: border-box; }
    .dd-empty { border: 1px dashed #bbb; min-height: 100px; background-color: #e5e5e5;
        background-image: -webkit-linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff),
        -webkit-linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff);
        background-image:    -moz-linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff),
        -moz-linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff);
        background-image:         linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff),
        linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff);
        background-size: 60px 60px;
        background-position: 0 0, 30px 30px;
    }

    .dd-dragel { position: absolute; pointer-events: none; z-index: 9999; }
    .dd-dragel > .dd-item .dd-handle { margin-top: 0; }
    .dd-dragel .dd-handle {
        -webkit-box-shadow: 2px 4px 6px 0 rgba(0,0,0,.1);
        box-shadow: 2px 4px 6px 0 rgba(0,0,0,.1);
    }

    /**
     * Nestable Extras
     */

    .nestable-lists { display: block; clear: both; padding: 30px 0; width: 100%; border: 0; border-top: 2px solid #ddd; border-bottom: 2px solid #ddd; }

    #nestable-menu { padding: 0; margin: 20px 0; }

    #nestable-output,
    #nestable2-output { width: 100%; height: 7em; font-size: 0.75em; line-height: 1.333333em; font-family: Consolas, monospace; padding: 5px; box-sizing: border-box; -moz-box-sizing: border-box; }

    #nestable2 .dd-handle {
        color: #fff;
        border: 1px solid #999;
        background: #bbb;
        background: -webkit-linear-gradient(top, #bbb 0%, #999 100%);
        background:    -moz-linear-gradient(top, #bbb 0%, #999 100%);
        background:         linear-gradient(top, #bbb 0%, #999 100%);
    }
    #nestable2 .dd-handle:hover { background: #bbb; }
    #nestable2 .dd-item > button:before { color: #fff; }

    @media only screen and (min-width: 700px) {

        .dd { float: right; width: 48%; }
        .dd + .dd { margin-right: 2%; }

    }

    .dd-hover > .dd-handle { background: #2ea8e5 !important; }

    /**
     * Nestable Draggable Handles
     */

    .dd3-content {cursor:pointer; display: block; height: 30px; margin: 5px 0; padding: 5px 10px 5px 40px; color: #333; text-decoration: none; font-weight: bold; border: 1px solid #ccc;
        background: #fafafa;
        background: -webkit-linear-gradient(top, #fafafa 0%, #eee 100%);
        background:    -moz-linear-gradient(top, #fafafa 0%, #eee 100%);
        background:         linear-gradient(top, #fafafa 0%, #eee 100%);
        -webkit-border-radius: 3px;
        border-radius: 3px;
        box-sizing: border-box; -moz-box-sizing: border-box;
    }
    .dd3-content:hover { color: #2ea8e5; background: #fff; }

    .dd-dragel > .dd3-item > .dd3-content { margin: 0; }

    .dd3-item > button { margin-left: 30px; }

    .dd3-handle { position: absolute; margin: 0; left: 0; top: 0; cursor: pointer; width: 30px; text-indent: 100%; white-space: nowrap; overflow: hidden;
        border: 1px solid #aaa;
        background: #ddd;
        background: -webkit-linear-gradient(top, #ddd 0%, #bbb 100%);
        background:    -moz-linear-gradient(top, #ddd 0%, #bbb 100%);
        background:         linear-gradient(top, #ddd 0%, #bbb 100%);
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .dd3-handle:before { content: '≡'; display: block; position: absolute; left: 0; top: 3px; width: 100%; text-align: center; text-indent: 0; color: #fff; font-size: 20px; font-weight: normal; }
    .dd3-handle:hover { background: #ddd; }


</style>
<?php
include_once "../../bin/footer.php";
