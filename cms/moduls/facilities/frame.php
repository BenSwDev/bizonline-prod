<?php

include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";

$attrID   = typemap($_GET['pageID'], 'int');

$domainID = Translation::$domain_id = DomainList::active();
$langID   = 1;

Translation::$strict_domain = true;

if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
        $attrID = typemap($_POST['attrID'], 'int');
        $data = typemap($_POST, [
            'defaultName'   => ['int' => 'string'],
			'sumAble'		=> 'int',
//            'fontCode'   => 'string',
            '!active'       => 'int',
            '!popular'       => 'int',
            '!promotedSEO'       => 'int',
            '!activeFilter'  => 'int',
            '!activeAutoSuggest'	=> 'int',
            '!categoryID'   => 'int',
            'category'   => 'int',
//            'domActive'     => ['int' => 'int'],
            'attrName'      => ['int' => 'string'],
            'attrDesc'      => ['int' => 'text'],
            'attrHeadTitle' => ['int' => 'string'],
            'filterName' => ['int' => 'string'],
            'attrHeadKeys'  => ['int' => 'string'],
            'attrHeadDesc'  => ['int' => 'text'],
            'parentCategory' => 'int'
        ]);

//        if (!$data['defaultName'][1])
//            throw new LocalException('חייב להיות שם');

        if (!$data['categoryID'] && !$data['category']){
            $data['categoryID'] = 1;
			//udb::single_value("SELECT `categoryID` FROM `attributes_categories` WHERE `active` = 1 ORDER BY `categoryID` DESC LIMIT 1");      // last added category
		}
		else if($data['category']){
			 $data['categoryID'] = $data['category'];
		}

        $photo = pictureUpload('picture', CMS_PATH . "../gallery/");


        $que = [
            'attrID' => $attrID,
            'domainID' => $domainID,
            'active' => $data['active'],
            'categoryID' => $data['categoryID'] ,
            'activeFilter' => max($data['activeFilter'], $data['promotedSEO']),
            'activeAutoSuggest' => $data['activeAutoSuggest'],
            'popular' => $data['popular'],
            'promotedSEO' => $data['promotedSEO'],
            'sumAble' => $data['sumAble'],
            'parentCategory'=>$data['parentCategory']
        ];
		//print_r($que);
		if($photo){
			$que["iconImage"] = $photo[0]['file'];
		}

		$que['showOrder'] = udb::single_value("SELECT `showOrder` FROM `attributes_domains` WHERE `attrID` = " . $attrID . " AND `domainID` = " . $domainID);
        if (!strlen($que['showOrder'])){        // MUST use "strlen()" coz there can be "zero" value
            udb::query("LOCK TABLES `attributes_domains` WRITE");
            $que['showOrder'] = udb::single_value("SELECT IFNULL(MAX(`showOrder`) + 1, 1) FROM `attributes_domains` WHERE `categoryID` = " . $data['categoryID']);
            udb::query("UNLOCK TABLES");
        }

        udb::insert('attributes_domains', $que, true);

        //Update room Attributes
        if(intval($data['parentCategory'])) {
            $sql = "update rooms_attributes set relAttr=".intval($data['parentCategory'])." where attrID=".$attrID;
            udb::query($sql);
        }

        // ends ***
        $base = udb::single_row("SELECT * FROM `attributes` WHERE `attrID` = " . $attrID);
        foreach(LangList::get() as $lid => $lang){
            $ldata = [
                'defaultName' => $data['defaultName'][$lid]
            ];

            //if ($ldata = Translation::validate_row($base, $ldata, 'attributes', $attrID, $lid, $domainID))
            Translation::save_row('attributes', $attrID, $ldata, $lid, $domainID);
        }

//        $dlist = [];
//        $langs = udb::single_column("SELECT `langID` FROM `language` WHERE 1", 0);
//        foreach(DomainList::get() as $did => $dom){
//
//            $dlist[] = "(" . $attrID . ", " . $did . ", " . intval($data['domActive'][$did]) . ")";
//
//            $list = [];
//            foreach($langs as $lid)
//                $list[] = "(" . $attrID . ", " . $did .  ", " . $lid . "
//			, '" . udb::escape_string($data['attrName'][$did][$lid]) . "'
//			, '" . udb::escape_string($data['filterName'][$did][$lid]) . "'
//			, '" . udb::escape_string($data['attrDesc'][$did][$lid]) . "'
//            , '" . udb::escape_string($data['attrHeadTitle'][$did][$lid]) . "'
//			, '" . udb::escape_string($data['attrHeadDesc'][$did][$lid]) . "'
//			, '" . udb::escape_string($data['attrHeadKeys'][$did][$lid]) . "')";
//
//            count($list) and udb::query("INSERT INTO `attributes_langs`(`attrID`, `domainID`, `langID`, `defaultName`, `filterName`, `attrDesc`, `attrHeadTitle`, `attrHeadDesc`, `attrHeadKeys`)
//					   VALUES" . implode(',', $list) . " ON DUPLICATE KEY UPDATE `defaultName` = VALUES(`defaultName`), filterName = VALUES(`filterName`) ,`attrDesc` = VALUES(`attrDesc`), `attrHeadDesc` = VALUES(`attrHeadDesc`), `attrHeadTitle` = VALUES(`attrHeadTitle`), `attrHeadKeys` = VALUES(`attrHeadKeys`)");
//        }
//        count($dlist) and udb::query("INSERT INTO `attributes_domains`(`attrID`, `domainID`, `active`) VALUES" . implode(',', $dlist) . " ON DUPLICATE KEY UPDATE `active` = VALUES(`active`)");
//
//
//		$dom = udb::single_row("SELECT * FROM `attributes_domains` WHERE `domainID` = 1 AND `attrID` = " . $attrID);
//		$dom['domainID'] = 1;
//		udb::insert('attributes_domains', $dom, true);
//
//		$doms = udb::single_list("SELECT * FROM `attributes_langs` WHERE `domainID` = 1 AND `attrID` = " . $attrID);
//		foreach($doms as $dom){
//			$dom['domainID'] = 1;
//			udb::insert('attributes_langs', $dom, true);
//		}

        //reloadParent();
?>
    <?/*<script>window.parent.location.reload(); window.parent.closeTab();</script>*/?>
<?php
    }
    catch (LocalException $e){
        // show error
    }?>
	<script>window.parent.location.reload();</script>
<?}


//$base = udb::single_row("SELECT * FROM `attributes` WHERE `attrID` = " . $attrID);
//$d_attr = udb::key_row("SELECT * FROM `attributes_domains` WHERE `attrID` = " . $attrID, 'domainID');
//$l_attr = udb::key_row("SELECT * FROM `attributes_langs` WHERE `attrID` = " . $attrID, ['domainID', 'langID']);
$categories = udb::full_list("SELECT `categoryID`, `categoryName` FROM `attributes_categories` WHERE `domainID` = " . $domainID . " ORDER BY `showOrder`");

$base = udb::single_row("SELECT a.*, d.* FROM `attributes` AS `a` LEFT JOIN `attributes_domains` AS `d` ON (a.attrID = d.attrID AND d.domainID = " . $domainID . ") WHERE a.attrID = " . $attrID);
$domData  = reset(DomainList::get($domainID));
$trans = $btr = [];
$langs = languagTabs($langID);
?>

<div class="editItems">
	<form method="POST" id="myform" enctype="multipart/form-data">
        <input type="hidden" name="attrID" value="<?=$attrID?>" />
        <input type="hidden" name="categoryID" value="<?=($base['categoryID']?$base['categoryID']:1)?>" />
		<div class="topInput">
<?php
    foreach($langs as $lid => $langName){
        $trans[$lid] = Translation::attributes($attrID, '*', $lid, $domainID);
        $btr[$lid] = Translation::attributes($attrID, '*', $lid, Translation::DEFAULT_DOMAIN);
?>
            <div class="inputLblWrap langDiv" data-id="<?=$lid?>">
				<div class="labelTo">שם מאפיין : </div>
				<input type="text" placeholder="<?=js_safe($btr[$lid]['defaultName'] ?? $base['defaultName'])?>" name="defaultName" value="<?=js_safe($trans[$lid]['defaultName'])?>" />
			</div>
<?php
    }
?>
            <div class="checkLabel">
                <label for="sumAble">מאפיין נסכם:</label>
                <div class="checkBoxWrap">
                <input type="checkbox" name="sumAble" id="sumAble" value="1" <?=$base['sumAble'] ? 'checked="checked"' : ''?> />
                <label for="sumAble"></label>
                </div>
            </div>

			<div class="checkLabel">
				<label for="active">מוצג</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="active" id="active" value="1" <?=(($base['active'] || !$attrID) ? 'checked="checked"' : '')?> title="" />
					<label for="active"></label>
				</div>
			</div>
			<div class="checkLabel">
				<label for="activeFilter">מוצג בסינונים</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="activeFilter" id="activeFilter" value="1" <?=(($base['activeFilter'] || !$attrID) ? 'checked="checked"' : '')?> title="" />
					<label for="activeFilter"></label>
				</div>
			</div>
			<div class="checkLabel">
				<label for="activeAutoSuggest">מוצג בהצעה אוטומטית</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="activeAutoSuggest" id="activeAutoSuggest" value="1" <?=(($base['activeAutoSuggest'] || !$attrID) ? 'checked="checked"' : '')?> title="" />
					<label for="activeAutoSuggest"></label>
				</div>
			</div>
			<div class="checkLabel">
				<label for="show2">פופולרי</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="popular" id="show2" value="1" <?=(($base['popular']) ? 'checked="checked"' : '')?>/>
					<label for="show2"></label>
				</div>
			</div>
			<div class="checkLabel">
				<label for="show2">דף SEO</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="promotedSEO" id="promotedSEO" value="1" <?=(($base['promotedSEO']) ? 'checked="checked"' : '')?> onclick="this.checked&&$('#activeFilter').prop('checked', true)"/>
					<label for="promotedSEO"></label>
				</div>
			</div>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$accessData['iconImage']?>">
					</div>
				</div>
				<?php if($accessData['iconImage']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$accessData['iconImage']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">שייך לקטגוריה</div>
				<select name="category">
<?php
    foreach($categories as $cid => $cname)
        echo '<option value="' . $cname['categoryID'] . '" ' . (($cname['categoryID'] == $base['categoryID']) ? 'selected="selected"' : '') . '>' . $cname['categoryName'] . '</option>';
?>
				</select>
			</div>
		</div>
        <div class="inputLblWrap">
				<div class="labelTo">קישור למאפיין</div>
                <select name="parentCategory">
                    <option value="0">ללא שיוך</option>
                    <?php
                    foreach($categories as  $cid=> $cname){
                        echo '<option value="' . $cname['categoryID'] . '" disabled>' . $cname['categoryName'] . '</option>';
                        $attrSql = "SELECT a.*, d.* FROM `attributes` AS `a` 
                                    LEFT JOIN `attributes_domains` AS `d` ON (a.attrID = d.attrID AND d.domainID = " . $domainID . ") WHERE a.attrType & " . $domData['attrType'] . " AND d.categoryID=".$cname['categoryID']." and d.attrID!=".$attrID." ORDER BY d.showOrder";
                        $attr2Sql = "SELECT a.*, d.* FROM `attributes` AS `a` 
                        LEFT JOIN `attributes_domains` AS `d` ON (a.attrID = d.attrID AND d.domainID = " . $domainID . ") 
                        left join attributes as m on (d.attrID = m.attrID)
                        WHERE a.attrType & " . $domData['attrType'] . " AND d.categoryID=".$cname['categoryID']." and d.attrID!=".$attrID." and m.parentCategory != " . $attrID . " and d.parentCategory != " . $attrID . " ORDER BY d.showOrder";
                        $allAttributes = udb::key_row($attr2Sql,"attrID");
                        foreach($allAttributes as $aid=>$aname) {
                            echo '<option value="' . $aname['attrID'] . '" ' . (($aname['attrID'] == $base['parentCategory']) ? 'selected="selected"' : '') . '>--' . $aname['defaultName'] . '</option>';
                        }

                    }
                    ?>
                </select>
        </div>
<?php
        foreach($langs as $lid => $langName){
            $attr = $trans[$lid];
            $btra = $btr[$lid];
?>
            <div class="frmWrapSelect langDiv" data-id="<?=$lid?>">
                <div class="section">
                    <div class="inptLine">
                        <div class="label">שם מאפיין : </div>
                        <input type="text" placeholder="<?=js_safe($btra['attrName'] ?? $base['attrName'])?>" value="<?=js_safe($attr['attrName'])?>" name="attrName" class="inpt" />
                    </div>
                </div>
                <div class="section">
                    <div class="inptLine">
                        <div class="label">כותרת SEO : </div>
                        <input type="text" placeholder="<?=js_safe($btra['attrHeadTitle'] ?? $base['attrHeadTitle'])?>" value="<?=js_safe($attr['attrHeadTitle'])?>" name="attrHeadTitle" class="inpt" />
                    </div>
                </div>

                <div class="section">
                    <div class="inptLine">
                        <div class="label">שם לתצוגה בסינונים : </div>
                        <input type="text" placeholder="<?=js_safe($btra['filterName'] ?? $base['filterName'])?>" value="<?=js_safe($attr['filterName'])?>" name="filterName" class="inpt" />
                    </div>
                </div>
                <div style="clear:both;"></div>
                <div class="section txtarea">
                    <div class="inptLine">
                        <div class="label">מילות מפתח : </div>
                        <textarea placeholder="<?=js_safe(smartcut($btra['attrHeadKeys'] ?? $base['attrHeadKeys']))?>" name="attrHeadKeys"><?=$attr['attrHeadKeys']?></textarea>
                    </div>
                </div>
                <div class="section txtarea">
                    <div class="inptLine">
                        <div class="label">תיאור SEO : </div>
                        <textarea placeholder="<?=js_safe(smartcut($btra['attrHeadDesc'] ?? $base['attrHeadDesc']))?>" name="attrHeadDesc"><?=$attr['attrHeadDesc']?></textarea>
                    </div>
                </div>
                <div style="clear:both;"></div>
                <div class="section txtarea big">
                    <div class="summerTtl">מידע נוסף</div>
                    <textarea placeholder="<?=js_safe(smartcut($btra['attrDesc'] ?? $base['attrDesc']))?>" name="attrDesc" class="summernote"><?=$attr['attrDesc']?></textarea>
                </div>
            </div>
<?php
        }
?>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit" />
			</div>
		</div>
	</form>
</div>

<script>
$(function(){
    $.each({domain: <?=$domainID?>, langDiv: <?=$langID?>}, function(cl, v){
        $('.' + cl).hide().each(function(){
            var id = $(this).data('id');
            $(this).find('input, select, textarea').each(function(){
                this.name = this.name + '[' + id + ']';
            });
        }).filter('[data-id="' + v + '"]').css('display', 'inline-block');

        $('.' + cl + 'Selector').on('change', function(){
            $('.' + cl, $(this).data('cont')).hide().filter('[data-id="' + this.value + '"]').show();
        });
    });

    $('.language').on('click', function(){
        $(this).addClass('active').siblings().removeClass('active');
        $('.langDiv').hide().filter('[data-id="' + $(this).data('id') + '"]').css('display', 'inline-block');
    });
});

/*
	$(".general .lngtab").click(function(){
		$(".general .lngtab").removeClass("active");
		$(this).addClass("active");

		var ptID = $(this).data("langid");
		$(".frm").css("display","none");

		$("#langTab"+ptID).css("display","block");
	});


	var addAlt = function (context) {
		var ui = $.summernote.ui;
		var button = ui.button({
			contents: '<i class="fa fa-paperclip"/> Alt',
			tooltip: 'הוספת תגית Alt',
			click: function () {
				var theAlt = prompt("הזן תגית Alt", "");

				if (theAlt != null) {
					$(context.layoutInfo.editable.data('target')).attr("alt",theAlt);
					$(context.layoutInfo.editor.data('target')).attr("alt",theAlt);
					$(context.layoutInfo.note.data('target')).attr("alt",theAlt);
					context.layoutInfo.note.val(context.invoke('code'));
					context.layoutInfo.note.change();
				}
			}
		});
		return button.render();
	};

	var insertPop = function (context) {
		var ui = $.summernote.ui;
		var button = ui.button({
			contents: '<i class="fa fa-paperclip"/> Alt',
			tooltip: 'הוספת תגית Alt',
			click: function () {
				console.log('a');
			}
		});
		return button.render();
	};
document.addEventListener('DOMContentLoaded', function(){
	$('.summernote').summernote({
		toolbar: [
		['style', ['bold', 'italic', 'underline', 'clear']],
		['fontname', ['fontname']],
		['color', ['color']],
		['fontsize', ['fontsize']],
		['para', ['ul', 'ol', 'paragraph']],
		['height', ['height']],
		['insert', ['picture', 'link','video']],
		['view', ['codeview']],
		],
		popover: {
			image: [
				['alt', ['addAlt']],
				['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
				['float', ['floatLeft', 'floatRight', 'floatNone']],
				['remove', ['removeMedia']]
			]},

		height: 300
	});
});

$(function() {
	$( ".datepicker" ).datepicker({
		dateFormat: 'yy/mm/dd'
	});
});
*/
</script>
