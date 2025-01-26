<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";

$langID   = typemap($_GET['LangID'], 'int') ?: 1;
$attrID   = typemap($_GET['pageID'], 'int');
$attrType = typemap($_GET['attrType'], 'int');
$domainID = $attrID ? ($_SESSION['cms']['domainID'] ?: 0) : 0;


if($adel = intval($_GET['atrdel'])){

	udb::query("DELETE FROM `attributes` WHERE `attrID` = " . $adel);
//	udb::query("DELETE FROM `attributes_langs` WHERE attrID=".intval($_GET['attrdel']));
//	udb::query("DELETE FROM `attributes_domains` WHERE attrID=".intval($_GET['attrdel']));
	//Delete all other uses
	udb::query("DELETE FROM `rooms_attributes` WHERE `attrID` = " . $adel);
	udb::query("DELETE FROM `sites_attributes` WHERE `attrID` = " . $adel);

    Translation::clear_row('attributes', $adel);
?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
}



if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
        $attrID = typemap($_POST['attrID'], 'int');
        $data = typemap($_POST, [
            'defaultName'   => ['int' => 'string'],
			'!sumAble'		=> 'int',
            'fontCode'      => 'string',
            '!active'       => 'int',
            'attrType'      => 'int',
            'attrName'      => ['int' => 'string'],
            'attrDesc'      => ['int' => 'text'],
            'attrHeadTitle' => ['int' => 'string'],
            'filterName'    => ['int' => 'string'],
            'attrHeadKeys'  => ['int' => 'string'],
            'attrHeadDesc'  => ['int' => 'text'],
            'connectionValue'=>['int' => 'string'],
        ]);

        if (!$data['defaultName'][1])
            throw new LocalException('חייב להיות שם');

		$photo = pictureUpload('picture', CMS_PATH . "../gallery/");


        $que = [
            'active'         => $data['active'],
            'sumAble'        => $data['sumAble'],
            'defaultName'    => $data['defaultName'][1],
            'connectionValue'    => $data['connectionValue'][1],
            'fontCode'       => $data['fontCode'],
            'parentCategory' => $data['parentCategory']
        ];
        if($attrType) {
            $que["attrType"] = $data['attrType'];
        }

		if($photo){
			$que["iconImage"] = $photo[0]['file'];
		}

        if ($attrID){
            udb::update('attributes', $que, '`attrID` = ' . $attrID);
        } else {
            $attrID = udb::insert('attributes', $que);
        }

        // ends ***

        $dlist = [];
        foreach(LangList::get() as $lid => $lang){
            $ldata = [
                'defaultName' => $data['defaultName'][$lid],
                'connectionValue' => $data['connectionValue'][$lid]
            ];

			Translation::save_row('attributes', $attrID, $ldata, $lid);
        }

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
        <script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
    }
    catch (LocalException $e){
        $saveError = $e->getMessage();
    }
}


$base = udb::single_row("SELECT * FROM `attributes` WHERE `attrID` = " . $attrID);
//$d_attr = udb::key_row("SELECT * FROM `attributes_domains` WHERE `attrID` = " . $attrID, 'domainID');
//$l_attr = udb::key_row("SELECT * FROM `attributes_langs` WHERE `attrID` = " . $attrID, ['domainID', 'langID']);
//$categories = udb::full_list("SELECT `categoryID`, `categoryName` FROM `attributes_categories` WHERE 1 ORDER BY `showOrder`");

$langs = languagTabs($langID);
?>
<div class="editItems">
	<form method="POST" id="myform" enctype="multipart/form-data">
        <input type="hidden" name="attrID" value="<?=$attrID?>" />
        <?php
            if($attrType) {
                ?>
                <input type="hidden" name="attrType" value="<?=$attrType?>" />
                <?php
            }
        ?>
		<div class="topInput">
<?php
foreach($langs as $lid => $langName){
    $attr = $l_attr[$lid];
    $trans = Translation::attributes($attrID, '*', $lid);
?>
			<div class="inputLblWrap langDiv" data-id="<?=$lid?>">
				<div class="labelTo">שם מאפיין : </div>
				<input type="text" placeholder="שם מערכת" name="defaultName" value="<?=js_safe($trans['defaultName'] ?: $base['defaultName'])?>" />
			</div>

    <div class="inputLblWrap langDiv" data-id="<?=$lid?>">
        <div class="labelTo">תצוגת כותרת אוטומטית</div>
        <input type="text" placeholder="תצוגת כותרת אוטומטית" name="connectionValue" value="<?=js_safe($trans['connectionValue'] ?: $base['connectionValue'])?>" />
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

			<div class="inputLblWrap" style="position: relative;">
				<div class="labelTo">קוד פונט</div>
				<?php if($base["fontCode"]) { ?>
					<span style="position: absolute;left: 0;bottom: 2px;"><span class="iconx-small" aria-hidden="true" data-icon="&#x<?=$base["fontCode"]?>"></span></span>
				<?php } ?>
				<input type="text" placeholder="קוד פונט" name="fontCode" value="<?=js_safe($base['fontCode'])?>" />
			</div>
			<div class="checkLabel">
				<label for="active">מוצג</label>
				<div class="checkBoxWrap">
                    <input type="checkbox" name="active" id="active" value="1" <?=(($base['active'] || !$attrID) ? 'checked="checked"' : '')?> title="" />
					<label for="active"></label>
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
		</div>

		<div class="section sub">
			<div class="inptLine">
				<?php if($attrID) { ?>
                <input type="button" value="מחק" class="submit" style="right:25px; left:auto; background:#c72323" onclick="if(confirm('האם את/ה בטוח/ה שברצונך למחוק את הפריט?')){location.href='?atrdel=<?=$attrID?>';}" />
				<?php } ?>
				<input type="submit" value="<?=($attrID ? "שמור" : "הוסף")?>" class="submit" />
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
