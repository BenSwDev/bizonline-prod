<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";

$menusList = "איבזורים";

/*

$menuType = intval($_GET['menuType'])?intval($_GET['menuType']):1;
$langID = intval($_GET['langID'])?intval($_GET['langID']):1;

$que="SELECT * FROM menu WHERE menuType=".$menuType." AND menuParent=0 AND LangID=".$langID." ORDER BY menuOrder";
$menu=udb::full_list($que);


$que="SELECT LangID, LangName FROM language WHERE 1";
$languages = udb::full_list($que);

*/

$attributes = udb::full_list("SELECT * FROM `attributes` WHERE attrType & 8 ORDER BY defaultName ASC");


?>
<div class="pagePop"><div class="pagePopCont"></div></div>

<div class="manageItems" id="manageItems">
    <h1>מאפיינים</h1>
	<div class="editItems" style="max-width:200px;">
	<select class="domSelector">
		<option value="index2.php">רשימה כללית</option>
        <option value="index_spa.php" selected >רשימה כללית ספא</option>
<?php
    foreach(DomainList::get() as $did => $dom)
        echo '<option value="index.php?domid=' , $did , '">' , $dom['domainName'] , '</option>';
?>
	</select>
	</div>
	<div class="editItems">
		<form id="manageMenus">
			<input type="hidden" value="" name="menuID" id="menuID">
			<div class="rightSide" id="rightSide">
				<h2>הוסף ערך</h2>
				<div>
					<input style="display: inline-block;vertical-align: top;width: auto;" type="button" class="sub" onclick="openPop(0)" value="הוסף מאפיין">
				</div>
			</div>
			<div class="mainCtrl">
				<div class="dd" id="sortContinaer">
					<ol class="dd-list catlist">
<?php
            echo '<ol class="dd-list attrlist">';

            foreach($attributes as $attr){
?>
                        <li class="dd-item dd3-item attr" data-id="<?=$attr['attrID']?>" data-type="child">
                            <div class="dd3-content" onclick="openPop(<?=$attr['attrID']?>)"><?=$attr['defaultName']?>
                            <?php if($attr["fontCode"]) { ?>
                                <span style="float:left"><span class="iconx-small" aria-hidden="true" data-icon="&#x<?=$attr["fontCode"]?>"></span></span>
                            <?php } ?>
                            </div>
                            <div class="attr-type-select" data-test="<?=$attr['attrType']?>">
                                <div class="a-type"><input type="checkbox" title="" data-attr-id="<?=$attr['attrID']?>" value="8" <?=($attr['attrType'] & 8 ? 'checked' : '')?> /></div>
                            </div>
                        </li>
<?php
            }
?>
					</ol>
				</div>
				<!-- <div style="width:100%;overlfow:hidden;clear:both;"></div>
				<input type="button" class="sub" onclick="saveAll()" value="שמור סדר" style="width:200px;clear:both;"> -->
				<div class="attr-by-type">
					<div class="type-col"></div>

				</div>
				<div class="attr-by-type titles">
                    <div class="type-col">ספא</div>
				</div>
			</div>
		</form>
	</div>
</div>
<!-- <script src="<?=$root?>/app/jquery.nestable_range.js"></script> -->
<!-- script src="<?=$root?>/app/jquery0.nestable.js"></script -->
<script>
function openPop(pageID){
	$(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/facilities/frameBase.php?attrType=8&pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
	$(".pagePop").show();
}
function openCat(pageID){
    $(".pagePopCont").html('<iframe id="frame_cat_'+pageID+'" frameborder=0 src="/cms/moduls/facilities/frame_cat.php?pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_cat_'+pageID+'\')">x</div>');
    $(".pagePop").show();
}
function closeTab(){
	$(".pagePopCont").html('');
	$(".pagePop").hide();
}

$(function(){
    $('div.a-type input[type="checkbox"]').on('click', function(){
        var id = $(this).data('attr-id'), sum = 0;

        $(this.parentNode.parentNode).find('input[type="checkbox"]:checked').each(function(){
            sum += this.value * 1;
        });

        $.post('js_update.php', {act:'baseType', aid:id, val:sum}).then(function(res){
            if (!res || res.status === undefined || parseInt(res.status))
                return alert(res ? (res.error || res._txt) : 'Connection error');
        });
    });


    $(".a-type span").on("click",function () {
        if($(this).hasClass("selected")) return;
        $(this).parent().find("span").removeClass("selected");
        $(this).addClass("selected");
        var updateValue = $(this).text();
        var useID = $(this).closest(".dd-item.dd3-item.attr").data("id");
        $.ajax({
            url: 'ajax_updateWord.php',
            method: 'post',
            data: {id: useID , word: updateValue},
            success: function (response) {
                console.log("done maybe pasive alert is good here");
            }
        });


    });


    $('.domSelector').on('change', function(){
        window.location.href = this.value;
    });
});
</script>
<style>

    .a-type span {
        display: inline-block;
        padding: 4px;
        cursor: pointer;

    }
    .a-type span:nth-child(1) {
        margin-right:4px;
    }
    .a-type span.selected {
        border:1px solid #0b0e07;
    }
    .rightSide{float: right;width: 300px;}
    .rightSide > div{overflow:hidden;}
    .rightSide h2{font-size: 16px; font-weight: bold;margin-bottom: 10px;}
    .rightSide label{margin-bottom: 5px;margin-top: 10px;}
    .sub{line-height: 22px;height: 26px;border: 0;border-radius: 3px;box-sizing: border-box;outline: none;padding: 0 5px;margin: 0 auto;width: 98%;margin-top:20px !important;color: #ffffff;font-weight: bold;background: #2FC2EB !important;font-size: 16px;text-shadow: -1px 1px 0 rgba(0, 0, 0, 0.1);border-bottom: 2px solid rgba(0, 0, 0, 0.1);cursor: pointer;box-shadow: none;-moz-transition: all 0.25s;-webkit-transition: all 0.25s;transition: all 0.25s;}
    .sub:hover{background: #34AFD2 !important;}
    .mainCtrl {margin-right: 310px;overflow: auto;position: relative;max-height: calc(100vh - 120px);}
    .mainCtrl h2{font-size: 16px; font-weight: bold;margin-bottom: 20px;}

    .dd { position: relative; display: block; margin: 0; padding: 0; max-width: 600px; list-style: none; font-size: 13px; line-height: 20px;z-index:1;margin-top:50px}

    .dd-list { display: block; position: relative; margin: 0; padding: 0; list-style: none; }

	.dd-list .dd-list { padding-left: 30px; }
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

	.dd3-content span{float:left;color:green;margin:0 5px}

	.attr-by-type {display: inline-block;top: 0px;bottom: 0;position: absolute;border-right: 1px #ccc solid;margin-right: 10px;}
	.attr-by-type .type-col {height: 100%;display: inline-block;width: 80px;text-align: center;padding: 0 10px;box-sizing: border-box;vertical-align: top;position: relative;border-left: 1px #ccc solid;}
	.attr-by-type.titles {position: sticky;top: 0;background: white;z-index: 2;}

	.attr-type-select {position: absolute;padding-right: 40px;top: 0;transform: translateX(-100%);left: 0;height: 30px;background: rgb(47 194 235 / 20%);white-space:nowrap}
	.attr-type-select .a-type {display: inline-block;width: 80px;text-align: center;}
	.attr-type-select .a-type input {width: 20px;height: 20px;}
</style>
<?php
include_once "../../bin/footer.php";
