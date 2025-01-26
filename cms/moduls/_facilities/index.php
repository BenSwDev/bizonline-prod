<?php
include_once "../../bin/system.php";
include_once "../../bin/top.php";
include_once "../../_globalFunction.php";

$menusList = "איבזורים";

$domainID = Translation::$domain_id = DomainList::active();
$toDomId = intval($_GET['toDomId']);
$domData  = reset(DomainList::get($domainID));

$attributes_categories = 'attributes_categories';
$attributes_domains = 'attributes_domains';

$categories = udb::key_row("SELECT * FROM `".$attributes_categories."` WHERE `domainID` = " . $domainID . " ORDER BY `showOrder`", 'categoryID');

//echo "SELECT a.*, d.* FROM `attributes` AS `a` LEFT JOIN `".$attributes_domains."` AS `d` ON (a.attrID = d.attrID AND d.domainID = " . $domainID . ") WHERE a.attrType & " . $domData['attrType'] . " ORDER BY d.showOrder";

$attributes = udb::key_list("SELECT a.*, d.* FROM `attributes` AS `a` LEFT JOIN `".$attributes_domains."` AS `d` ON (a.attrID = d.attrID AND d.domainID = " . $domainID . ") WHERE a.attrType & " . $domData['attrType'] . " ORDER BY d.showOrder", 'categoryID');

$attributes[0] = udb::single_list("SELECT a.* FROM `attributes` AS `a` LEFT JOIN `".$attributes_domains."` AS `d` ON (a.attrID = d.attrID AND d.domainID = " . $domainID . ") WHERE a.attrType & " . $domData['attrType'] . " AND (d.attrID IS NULL or d.categoryID=0)");
//print_r($categories);

if($toDomId > 1 && $toDomId != $domainID) {
    $fields = udb::single_column("SHOW FIELDS FROM `".$attributes_domains."`", 'Field');
    $fields = '`' . implode('`,`', array_diff($fields, ['categoryID', 'domainID'])) . '`';

    udb::query("delete from ".$attributes_categories." where domainID=".$toDomId);
    udb::query("delete from ".$attributes_domains." where domainID=".$toDomId);

    foreach($categories as $cid => $category){
        $newRow = array_merge($category, ['domainID' => $toDomId]);
        unset($newRow['categoryID']);

        $catID = udb::insert($attributes_categories, $newRow);

        udb::query("INSERT INTO `".$attributes_domains."`(`domainID`,`categoryID`," . $fields . ") SELECT '" . $toDomId . "', '" . $catID . "'," . $fields . " FROM `".$attributes_domains."` WHERE `domainID` = " . $domainID . " AND `categoryID` = " . $cid);

//        foreach($attributes[$cid] as $attr){
//            $newRow = $attr;
//            $newRow['domainID'] = $toDomId;
//            $newRow['categoryID'] = $catID;
//            unset($newRow['attrType']);
//            unset($newRow['attrWeight']);
//            unset($newRow['defaultName']);
//            unset($newRow['oldID']);
//            udb::insert($attributes_domains,$newRow);
//            unset($attr);
//            unset($newRow);
//        }
//        unset($newRow);
    }
}

if ($attributes[0])
    $categories = [0 => ['categoryID' => 0, 'categoryName' => '-- ללא קטגוריה --', 'active' => 0]] + $categories;

//print_r($attributes);
//print_r($categories);
//exit;
?>

    <div class="pagePop"><div class="pagePopCont"></div></div>

    <div class="manageItems" id="manageItems">
        <h1>מאפיינים</h1>
        <div class="editItems actions" >
            <select class="domSelector" style="display: inline-block">
                <option value="index2.php">רשימה כללית</option>
                <?php
                foreach(DomainList::get() as $did => $dom)
                    echo '<option value="index.php?domid=' , $did , '" ' , ($did == $domainID ? 'selected' : '') , '>' , $dom['domainName'] , '</option>';
                ?>
            </select>
            <?if($domainID && $domainID!=1) {?>
                <select class="domSelectorcopy"  style="display: inline-block;">
                    <option value="">העתק אל</option>
                    <?php
                    foreach(DomainList::get() as $did => $dom){
                        if($did != $domainID && $domainID!=1)
                            echo '<option value="' , $did , '" '  , '>' , $dom['domainName'] , '</option>';
                    }
                    ?>
                </select>
                <button class="sub"  id="copyAttrs" style="display: inline-block;width:200px;">העתק נתונים</button>
            <?}?>
        </div>
        <div class="editItems">
            <h1><?=$menuList?></h1>
            <form id="manageMenus">
                <input type="hidden" value="" name="menuID" id="menuID">
                <div class="rightSide" id="rightSide">
                    <h2>הוסף ערך</h2>
                    <div>
                        <input style="display: inline-block;vertical-align: top;width: auto;" type="button" class="sub" onclick="openCat(0)" value="הוסף קטגוריה">
                        <?if(!$domainID){?><input style="display: inline-block;vertical-align: top;width: auto;" type="button" class="sub" onclick="openPop(0)" value="הוסף מאפיין"><?}?>
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
                            <li class="dd-item dd3-item cat" data-id="<?=$category['categoryID']?>">
                                <div class="dd-handle dd3-handle"></div>
                                <div class="dd3-content" onclick="openCat(<?=$category['categoryID']?>)" <?=$red?>><?=$category['categoryName']?> </div>

                                <?php
                                if ($attributes[$category['categoryID']]){
                                    echo '<ol class="dd-list attrlist">';

                                    foreach($attributes[$category['categoryID']] as $attr){
                                        //print_r($attr);
                                        //exit;
                                        $trans = Translation::attributes($attr['attrID'], '*', 1, $domainID);
                                        ?>
                                        <li class="dd-item dd3-item attr" data-id="<?=$attr['attrID']?>" data-type="child">
                                            <div class="dd-handle dd3-handle"></div>
                                            <div class="dd3-content" onclick="openPop(<?=$attr['attrID']?>)"><?=($trans['defaultName'] ? $trans['defaultName'] . ' (' . $attr['defaultName'] . ')' : $attr['defaultName'])?>
                                                <?php if($attr["fontCode"]) { ?>
                                                    <span style="float:left"><span class="iconx-small" aria-hidden="true" data-icon="&#x<?=$attr["fontCode"]?>"></span></span>
                                                <?php } ?>
                                            </div>
                                            <?php
                                            if ($category['categoryID']  || $category['categoryID'] ==0){
                                                ?>
                                                <div class="attr-type-select dd-nodrag">
                                                    <div class="a-type"><input data-attrid="<?=$attr['attrID']?>" data-name="active" type="checkbox" <?=($attr['active'] ? 'checked' : '')?> title="" /></div>
                                                    <div class="a-type"><input data-attrid="<?=$attr['attrID']?>" data-name="activeFilter" type="checkbox" <?=($attr['activeFilter'] ? 'checked' : '')?> title="" /></div>
                                                    <div class="a-type"><input data-attrid="<?=$attr['attrID']?>" data-name="activeAutoSuggest" type="checkbox" <?=($attr['activeAutoSuggest'] ? 'checked' : '')?> title="" /></div>
                                                    <div class="a-type"><input data-attrid="<?=$attr['attrID']?>" data-name="popular" type="checkbox" <?=($attr['popular'] ? 'checked' : '')?> title="" /></div>
                                                    <div class="a-type"><input data-attrid="<?=$attr['attrID']?>" data-name="promotedSEO" type="checkbox" <?=($attr['promotedSEO'] ? 'checked' : '')?> title="" /></div>
                                                    <div class="a-type"><input data-attrid="<?=$attr['attrID']?>" data-name="sumAble" type="checkbox" <?=($attr['sumAble'] ? 'checked' : '')?> title="" /></div>
                                                </div>
                                                <?php
                                            }
                                            else {
                                                echo '<!-- No categoryID '.$category['categoryID'].' -->';
                                            }
                                            ?>
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
                    <div class="attr-by-type">
                        <div class="type-col"></div>
                        <div class="type-col"></div>
                        <div class="type-col"></div>
                        <div class="type-col"></div>
                        <div class="type-col"></div>
                        <div class="type-col"></div>
                    </div>
                    <div class="attr-by-type titles">
                        <div class="type-col">מוצג</div>
                        <div class="type-col">מוצג בסינונים</div>
                        <div class="type-col">מוצג בהצעה אוטומטית</div>
                        <div class="type-col">פופלרי</div>
                        <div class="type-col">דף SEO</div>
                        <div class="type-col">מאפיין נסכם</div>
                    </div>

                </div>
            </form>
        </div>
    </div>
    <script src="<?=$root?>/app/jquery0.nestable.js"></script>
    <link rel="stylesheet" href="<?=$root?>/../user/assets/css/sweetalert2.min.css" />
    <script src="<?=$root?>/../user/assets/js/swal.js"></script>
    <script>
        function openPop(pageID){
            $(".pagePopCont").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/facilities/frame.php?domid=<?=$domainID?>&pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
            $(".pagePop").show();
        }
        function openCat(pageID){
            $(".pagePopCont").html('<iframe id="frame_cat_'+pageID+'" frameborder=0 src="/cms/moduls/facilities/frame_cat.php?domid=<?=$domainID?>&pageID='+pageID+'"></iframe><div class="tabCloser" onclick="closeTab(\'frame_cat_'+pageID+'\')">x</div>');
            $(".pagePop").show();
        }
        function closeTab(){
            $(".pagePopCont").html('');
            $(".pagePop").hide();
        }

        $(function(){
            $('div.a-type input[type="checkbox"]').on('click', function(){
                var data = $(this).data();
                var useAtrID = data.attrid ? data.attrid : data.attrId;
                console.log(data);
                $.post('js_update.php', {act:'attrType', domid:<?=$domainID?>, aid: useAtrID, name:data.name, val:this.checked ? 1 : 0}).then(function(res){
                    if (!res || res.status === undefined || parseInt(res.status))
                        return alert(res ? (res.error || res._txt) : 'Connection error');
                });
            });

            $('.domSelector').on('change', function(){
                window.location.href = this.value;
            });

            $("#copyAttrs").on("click",function () {
                swal.fire({
                    title: 'העתקה ושכתוב נתונים',
                    text: 'פעולה זו תמחק את המאפיינים בדומיין הנבחר ותעתיק את נתונים מהדומיין הנוכחי האם אתם בטוחים?',
                    type: 'question',
                    showConfirmButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'בצע',
                    confirmButtonColor: '#3085d6',
                    cancelButtonText: 'ביטול',
                    cancelButtonColor: '#aaa',
                    onOpen: function(){
                        $(".swal2-container").addClass("swal2-in");
                    }
                }).then(function(answer){
                    if(answer.value === true) {
                        window.location.href = "index.php?domid=<?=$domainID?>&toDomId=" + $(".domSelectorcopy").val();
                    }

                });
            });

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
                if ($(e.target.parentNode).hasClass('a-type'))
                    return;

                $.ajax({
                    url: 'js_order.php',
                    type: 'POST',
                    data: {domid:<?=$domainID?>, ids:$(this).nestable('serialize')},
                    async: false,
                    success: function (myData) {
                        //window.location.reload();
                    }
                });
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
        .mainCtrl {margin-right: 310px;overflow: auto;position: relative;max-height: calc(100vh - 120px);}
        .mainCtrl h2{font-size: 16px; font-weight: bold;margin-bottom: 20px;}

        .dd { position: relative; display: block; margin: 0; padding: 0; max-width: 600px; list-style: none; font-size: 13px; line-height: 20px;z-index:1 }

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

        .attr-type-select {position: absolute;padding-right: 40px;top: 0;transform: translateX(-100%);left: 0;height: 30px;background: rgb(47 194 235 / 20%);}
        .attr-type-select .a-type {display: inline-block;width: 80px;text-align: center;}
        .attr-type-select .a-type input {width: 20px;height: 20px;}
        .editItems.actions {max-width:840px;}
        .editItems.actions select {
            display: inline-block;
            width:200px;
        }

    </style>
<?php
include_once "../../bin/footer.php";