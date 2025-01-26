<?php
include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "mainTopTabs.php";
include_once "../../_globalFunction.php";

/*
$pageID=intval($_GET['pageID']);
$pageType=intval($_GET['type']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;

$langID   = typemap($_GET['LangID'], 'int') ?: 1;
$attrID   = typemap($_GET['pageID'], 'int');
$domainID = $attrID ? ($_SESSION['cms']['domainID'] ?: 0) : 0;

if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
        $attrID = typemap($_POST['attrID'], 'int');
        $data = typemap($_POST, [
            'defaultName'   => 'string',
            '!active'       => 'int',
            '!categoryID'   => 'int',
            'domActive'     => ['int' => 'int'],
            'attrName'      => ['int' => ['int' => 'string']],
            'attrDesc'      => ['int' => ['int' => 'text']],
            'attrHeadTitle' => ['int' => ['int' => 'string']],
            'attrHeadKeys'  => ['int' => ['int' => 'string']],
            'attrHeadDesc'  => ['int' => ['int' => 'text']]
        ]);

        if (!$data['defaultName'])
            throw new LocalException('חייב להיות שם');
        if (!$data['categoryID'])
            $data['categoryID'] = udb::single_value("SELECT `categoryID` FROM `attributes_categories` WHERE `active` = 1 ORDER BY `categoryID` DESC LIMIT 1");      // last added category

        $que = ['active' => $data['active'], 'categoryID' => $data['categoryID'], 'defaultName' => $data['defaultName']];
        if ($attrID){
            udb::update('attributes', $que, '`attrID` = ' . $attrID);
        } else {
            udb::query("LOCK TABLES `attributes` WRITE");
            $que['showOrder'] = udb::single_value("SELECT IFNULL(MAX(`showOrder`) + 1, 1) FROM `attributes` WHERE `categoryID` = " . $data['categoryID']);

            $attrID = udb::insert('attributes', $que);
            udb::query("UNLOCK TABLES");
        }

        $dlist = [];
        $doms  = udb::single_column("SELECT `domainID` FROM `domains` WHERE 1", 0);
        $langs = udb::single_column("SELECT `langID` FROM `language` WHERE 1", 0);
        foreach($doms as $did){
            $dlist[] = "(" . $attrID . ", " . $did . ", " . intval($data['domActive'][$did]) . ")";

            $list = [];
            foreach($langs as $lid)
                $list[] = "(" . $attrID . ", " . $did .  ", " . $lid . ", '" . udb::escape_string($data['attrName'][$did][$lid]) . "', '" . udb::escape_string($data['attrDesc'][$did][$lid]) . "'
                    , '" . udb::escape_string($data['attrHeadTitle'][$did][$lid]) . "', '" . udb::escape_string($data['attrHeadDesc'][$did][$lid]) . "', '" . udb::escape_string($data['attrHeadKeys'][$did][$lid]) . "')";
            count($list) and udb::query("INSERT INTO `attributes_langs`(`attrID`, `domainID`, `langID`, `attrName`, `attrDesc`, `attrHeadTitle`, `attrHeadDesc`, `attrHeadKeys`) 
                                            VALUES" . implode(',', $list) . " ON DUPLICATE KEY UPDATE `attrName` = VALUES(`attrName`), `attrDesc` = VALUES(`attrDesc`), `attrHeadDesc` = VALUES(`attrHeadDesc`), `attrHeadTitle` = VALUES(`attrHeadTitle`), `attrHeadKeys` = VALUES(`attrHeadKeys`)");
        }
        count($dlist) and udb::query("INSERT INTO `attributes_domains`(`attrID`, `domainID`, `active`) VALUES" . implode(',', $dlist) . " ON DUPLICATE KEY UPDATE `active` = VALUES(`active`)");

        //reloadParent();
    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php 

}
 */
?>


<div class="editItems">
	<div class="popGallery">
		<div class="popGalleryCont"></div>
	</div>
	<div class="siteMainTitle">שם הצימר</div>
	<?=showTopTabs()?>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">דומיין</div>
		<select name="domain">
			<option value="">דומיין 1</option>
			<option value="">דומיין 2</option>
			<option value="">דומיין 3</option>
		</select>
	</div>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
		<select name="language">
			<option value="">עברית</option>
			<option value="">English</option>
			<option value="">Русский</option>
		</select>
	</div>
	<div class="frameContent">
		<form method="post" enctype="multipart/form-data" >
			<div class="mainSectionWrapper">
				<div class="sectionName">כללי</div>
				<div class="langWrap">
					<div class="inputLblWrap">
						<div class="labelTo">שם המתחם</div>
						<input type="text" placeholder="כתובת" name="miniSiteName" value="" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">כתובת</div>
						<input type="text" placeholder="כתובת" name="address" value="" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">שם בעלים</div>
						<input type="text" placeholder="שם בעלים" name="ownerName" value="" />
					</div>
				</div>
				<div class="portalWrap">
					<div class="inputLblWrap">
						<div class="labelTo">טלפון</div>
						<input type="text" placeholder="טלפון" name="phone" value="" />
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">טלפון נוסף</div>
						<input type="text" placeholder="טלפון נוסף" name="addPhone" value="" />
					</div>
					<div class="inputLblWrap">
						<label class="switch">
						  <input type="checkbox" name="ifShow">
						  <span class="slider round"></span>
						</label>
						<div class="switchTtl">מוצג</div>
					</div>
				</div>
				<div class="portalWrap langWrap">
					<div class="section txtarea big">
						<div class="label">תיאור קצר: </div>
						<textarea name="ShortDesc" class="shortextEditor"></textarea>
					</div>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">שפה 2 - כללי</div>
				<div class="inputLblWrap">
					<div class="labelTo">כתובת</div>
					<input type="text" placeholder="כתובת" name="address" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">שם בעלים</div>
					<input type="text" placeholder="שם בעלים" name="ownerName" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">טלפון</div>
					<input type="text" placeholder="טלפון" name="phone" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">טלפון נוסף</div>
					<input type="text" placeholder="טלפון נוסף" name="addPhone" value="" />
				</div>
				<div class="section txtarea big">
					<div class="label">תיאור קצר: </div>
					<textarea name="ShortDesc" class="shortextEditor"></textarea>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">שפה 3 - כללי</div>
				<div class="inputLblWrap">
					<div class="labelTo">כתובת</div>
					<input type="text" placeholder="כתובת" name="address" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">שם בעלים</div>
					<input type="text" placeholder="שם בעלים" name="ownerName" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">טלפון</div>
					<input type="text" placeholder="טלפון" name="phone" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">טלפון נוסף</div>
					<input type="text" placeholder="טלפון נוסף" name="addPhone" value="" />
				</div>
				<div class="section txtarea big">
					<div class="label">תיאור קצר: </div>
					<textarea name="ShortDesc" class="shortextEditor"></textarea>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">SEO</div>
				<div class="inputLblWrap">
					<div class="labelTo">כותרת עמוד</div>
					<input type="text" placeholder="כותרת עמוד" name="" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">H1</div>
					<input type="text" placeholder="H1" name="H1" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">קישור</div>
					<input type="text" placeholder="קישור" name="" value="" />
				</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">מילות מפתח</div>
						<textarea name="seoKeyword"><?=outDb($page['seoKeyword'])?></textarea>
					</div>
				</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">תאור דף</div>
						<textarea name="seoDesc"><?=outDb($page['seoDesc'])?></textarea>
					</div>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">SEO-שפה 2</div>
				<div class="inputLblWrap">
					<div class="labelTo">כותרת עמוד</div>
					<input type="text" placeholder="כותרת עמוד" name="" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">H1</div>
					<input type="text" placeholder="H1" name="H1" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">קישור</div>
					<input type="text" placeholder="קישור" name="" value="" />
				</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">מילות מפתח</div>
						<textarea name="seoKeyword"><?=outDb($page['seoKeyword'])?></textarea>
					</div>
				</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">תאור דף</div>
						<textarea name="seoDesc"><?=outDb($page['seoDesc'])?></textarea>
					</div>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">SEO-שפה 3</div>
				<div class="inputLblWrap">
					<div class="labelTo">כותרת עמוד</div>
					<input type="text" placeholder="כותרת עמוד" name="" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">H1</div>
					<input type="text" placeholder="H1" name="H1" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">קישור</div>
					<input type="text" placeholder="קישור" name="" value="" />
				</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">מילות מפתח</div>
						<textarea name="seoKeyword"><?=outDb($page['seoKeyword'])?></textarea>
					</div>
				</div>
				<div class="section txtarea">
					<div class="inptLine">
						<div class="label">תאור דף</div>
						<textarea name="seoDesc"><?=outDb($page['seoDesc'])?></textarea>
					</div>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">מיקום</div>
				<div class="inSectionWrap">
					<div class="inputLblWrap">
						<div class="labelTo">אזורים</div>
						<div class="selectAndCheck" id="areasChecks">
							<div class="choosenCheck"></div>
							<div class="checksWrrap">
								<?php for($i=0;$i<=20;$i++) { ?>
								<div class="checkLabel<?=($i < 6 ? ' active' : '')?>" data-value="<?=$i?>">מאפיין <?=$i?></div>
								<?php } ?>
							</div>
						</div>
					</div>
					<div class="inputLblWrap">
						<div class="labelTo">ישוב</div>
						<select name="city">
							<option value=""></option>
						</select>
					</div>
				</div>
			</div>

			<div class="mainSectionWrapper">
				<div class="sectionName">פרטי התחברות משתמש</div>
				<div class="inputLblWrap">
					<div class="labelTo">אימייל</div>
					<input type="text" placeholder="אימייל" name="email" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">סיסמא</div>
					<input type="password" placeholder="סיסמא" name="password" value="" />
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">מדיה</div>
				<div class="inputLblWrap">
					<div class="labelTo">אתר אינטרנט</div>
					<input type="text" placeholder="אתר אינטרנט" name="website" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">פייסבוק</div>
					<input type="text" placeholder="פייסבוק" name="" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">גוגל פלוס</div>
					<input type="text" placeholder="גוגל פלוס" name="" value="" />
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">תמונה מייצגת</div>
				<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
					<div class="section">
						<div class="inptLine">
							<div class="label">תמונה  - קיץ</div>
							<input type="file" name="pictureSummery" class="inpt" value="<?=$page['pictureSummery']?>">
						</div>
					</div>
					<?php if($page['pictureSummery']){ ?>
					<div class="section">
						<div class="inptLine">
							<img src="../../gallery/<?=$page['pictureSummery']?>" style="width:100%">
						</div>
					</div>
					<?php } ?>
				</div>
				<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
					<div class="section">
						<div class="inptLine">
							<div class="label">תמונה - חורף</div>
							<input type="file" name="pictureWinter" class="inpt" value="<?=$page['pictureWinter']?>">
						</div>
					</div>
					<?php if($page['pictureWinter']){ ?>
					<div class="section">
						<div class="inptLine">
							<img src="../../gallery/<?=$page['pictureWinter']?>" style="width:100%">
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">גלריה</div>
				<div class="manageItems">
					<div class="addButton" style="margin-top: 20px;">
						<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="galleryOpen(<?=$frameID.",".$siteID?>,'new')" >
						<?php if($galleries){ ?>
						<!-- <input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה"> -->
						<?php } ?>
					</div>
					<?php 
						if($galleries){ ?>
					<table>
						<thead>
						<tr>
							<th width="30">#</th>
							<th>שם גלריה</th>
							<th>מוצג באתר</th>
							<th width="60">&nbsp;</th>
						</tr>
						</thead>
						<tbody  id="sortRow">

						<?php foreach($galleries as $row) { ?>
							<tr  id="<?=$row['GalleryID']?>">
								<td align="center"><?=$row['GalleryID']?></td>
								<td onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><?=outDb($row['GalleryTitle'])?></td>			
								<td align="center"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
								<td align="center" class="actb">
								<div onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>|</div><div onClick="if(confirm('אתה בטוח??')){location.href='?sID=<?=$siteID?>&frame=<?=$frameID?>&gdel=<?=$row['GalleryID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
							</tr>
						<? } ?>
						</tbody>
					</table>
					<? } ?>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">חוות דעת הפסגה</div>
				<div class="inputLblWrap">
					<div class="labelTo">כותב חוות דעת</div>
					<select name="">
						<option value=""></option>
					</select>
				</div>
				<div class="section txtarea big">
					<div class="label">חוות דעת</div>
					<textarea name="" class="textEditor"></textarea>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">מאפיינים</div>
				<div class="checksWrap">
					<?php for($i=0;$i<30;$i++) { ?>
					<div class="checkLabel checkIb">
						<div class="checkBoxWrap">
							<input type="checkbox" name="" id="ch<?=$i?>">
							<label for="ch<?=$i?>"></label>
						</div>
						<label for="ch<?=$i?>">מאפיין <?=$i?></label>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">סרטוני יוטיוב</div>
				<div class="inputLblWrap">
					<div class="labelTo">סרטון 1</div>
					<input type="text" placeholder="" name="youtube1" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">סרטון 2</div>
					<input type="text" placeholder="" name="youtube2" value="" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">סרטון 2</div>
					<input type="text" placeholder="" name="youtube3" value="" />
				</div>
			</div>
			<div class="mainSectionWrapper">
				<div class="sectionName">הגדרת ימי אמצ"ש סופ"ש</div>
				<div class="weekTbl">
					<div class="tblRow">
						<div class="tblCell">נחשב לסופ"ש</div>
						<div class="tblCell"></div>
						<div class="tblCell"></div>
						<div class="tblCell"></div>
						<div class="tblCell"></div>
						<div class="tblCell">
							<div class="checkLabel">
								<div class="checkBoxWrap">
									<input type="checkbox" name="" id="weekend5">
									<label for="weekend5"></label>
								</div>
							</div>
						</div>
						<div class="tblCell">
							<div class="checkLabel">
								<div class="checkBoxWrap">
									<input type="checkbox" name="" id="weekend6">
									<label for="weekend6"></label>
								</div>
							</div>
						</div>
						<div class="tblCell">
							<div class="checkLabel">
								<div class="checkBoxWrap">
									<input type="checkbox" name="" id="weekend7">
									<label for="weekend7"></label>
								</div>
							</div>
						</div>
					</div>
					<div class="tblRow">
						<div class="tblCell"></div>
						<div class="tblCell">א</div>
						<div class="tblCell">ב</div>
						<div class="tblCell">ג</div>
						<div class="tblCell">ד</div>
						<div class="tblCell">ה</div>
						<div class="tblCell">ו</div>
						<div class="tblCell">ש</div>
					</div>
					<div class="tblRow">
						<div class="tblCell">מינימום לילות</div>
						<?php for($i=1;$i<=7;$i++) { ?>
						<div class="tblCell">
							<input type="number" class="inputNumber" min="1" max="7" maxlength="1" name="" id="dd<?=$i?>">
						</div>
						<?php } ?>

					</div>
					<div class="tblRow">
						<div class="tblCell">הזמנת לילה<br>ברגע האחרון</div>
						<?php for($i=1;$i<=7;$i++) { ?>
						<div class="tblCell">
							<input type="number" class="inputNumber2" maxlength="99" min="99999" max="5" name="" id="dm<?=$i?>">
						</div>
						<?php } ?>
					</div>
				
				</div>

			</div>
			<input type="submit" value="שמור" class="submit">
		</form>	
	</div>
</div>
<!-- <link rel="stylesheet" href="../app/bootstrap.css">
<link rel="stylesheet" href="../app/dist/summernote.css">
<script src="../app/bootstrap.min.js"></script>
<script src="../app/dist/summernote.js?v=<?=time()?>"></script> -->
<script src="../../app/tinymce/tinymce.min.js"></script>
<script>


function CustomCheckBox(cont, opt){
	var self = this;
		this.values = [];
		this.cont = $(cont);
		this.settings = $.extend({
			openClass: 'open',
			activeClass: 'active',
			childs: '.checkLabel',
			label: '.choosenCheck',
			fieldName: this.cont.attr('id') || 'checkBox'
		}, (opt || {}));

		this.input = $('<input type="hidden" name="'+ this.settings.fieldName +'" value="" />');


		this.cont.on('click', this.settings.label, function(){
			self.cont.toggleClass(self.settings.openClass);
		})
		.append(this.input)
		.find(this.settings.childs).on('click', function(){
			$(this).toggleClass(self.settings.activeClass);
			self._toggleSelected($(this).data('value'));
			self.updateDom();
		}).filter('.'+this.settings.activeClass).each(function(){
			self._toggleSelected($(this).data('value'));
		});

		self.updateDom();
}
$.extend(CustomCheckBox.prototype, {
	_toggleSelected: function(value){
		var index = this.values.indexOf(value);

		if(index > -1){
			this.values.splice(index, 1);
		}else{
			this.values.push(value);
		}

		this.values = this.values.sort();

	},
	updateDom: function(){
		var self = this;

		this.cont.find(this.settings.label).text($.map(this.values, function(index){
			return self.cont.find(self.settings.childs+'[data-value='+index+']').text();
		}).join(', '));

		this.input.val(this.values.join());
	}
});

var AreasCCB = new CustomCheckBox('#areasChecks');


	$('.sectionName').click(function(){
		$(this).parent().toggleClass('open');
		if($(window).width() < 766){
			window.parent.$('body').toggleClass('sectionOpen');
			$(this).parent().scrollTop(0);

		}
	});

	tinymce.init({
	  selector: 'textarea.textEditor' ,
	  height: 500,  
	 plugins: [
		"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
		"table contextmenu directionality emoticons textcolor paste  textcolor colorpicker textpattern"
	  ],
	  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
	  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
	  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking pagebreak restoredraft"

	});


    var inputQuantity = [];
    $(function() {
      $(".inputNumber").each(function(i) {
        inputQuantity[i]=this.defaultValue;
         $(this).data("idx",i); // save this field's index to access later
      });
      $(".inputNumber").on("keyup", function (e) {
        var $field = $(this),
            val=this.value,
            $thisIndex=parseInt($field.data("idx"),10); // retrieve the index
//        window.console && console.log($field.is(":invalid"));
          //  $field.is(":invalid") is for Safari, it must be the last to not error in IE8
        if (this.validity && this.validity.badInput || isNaN(val) || $field.is(":invalid") ) {
            this.value = inputQuantity[$thisIndex];
            return;
        } 
        if (val.length > Number($field.attr("maxlength"))) {
          val=val.slice(0, 5);
          $field.val(val);
        }
        inputQuantity[$thisIndex]=val;
      });      
    });

	function galleryOpen(frameID,siteID,galleryID){
		$(".popGalleryCont").html('<iframe width="100%" height="100%" id="frame_'+frameID+'_'+siteID+'_'+galleryID+'" frameborder=0 src="/cms/moduls/minisites/gallery.php?frame='+frameID+'&sID='+siteID+'&gID='+galleryID+'"></iframe><div class="tabCloserSpace" onclick="tabCloserSpace(\'frame_'+frameID+'_'+siteID+'_'+galleryID+'\')">x</div>');
		$(".popGallery").show();
		//var elme = window.parent.document.getElementById("frame_"+frameID);
		elme.style.zIndex="16";
		elme.style.position="relative";
	}

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
