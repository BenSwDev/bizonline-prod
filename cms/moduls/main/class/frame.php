<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

$pageID=intval($_GET['pageID']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;
const BASE_LANG_ID = 1;

/*
if('POST' == $_SERVER['REQUEST_METHOD']) {


    try {
        $data = typemap($_POST, [
            'accessoryName'   => ['int' => 'string']
        ]);

        if (!$data['accessoryName'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');
	
        $siteData = [

            'accessoryName' => $data['accessoryName'][BASE_LANG_ID]

        ];
		$photo = pictureUpload('picture',"../../../../gallery/");
		if($photo){
			$siteData["accessoryPic"] = $photo[0]['file'];
		}

        if (!$pageID){      // opening new site
            $pageID = udb::insert('accessories', $siteData);
        } else {
            udb::update('accessories', $siteData, '`accessoryID` = ' . $pageID);
        }


		// saving data per domain / language
		foreach(LangList::get() as $lid => $lang){
			// inserting/updating data in domains table
			udb::insert('accessories_langs', [
				'accessoryID'    => $pageID,
				'langID'    => $lid,
				'accessoryName'  => $data['accessoryName'][$lid]
			], true);
		}
       
    }
	catch (LocalException $e){
        // show error
    } ?>

<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
	
}
*/

if ($pageID){
    $accessData    = udb::single_row("SELECT * FROM `accessories` WHERE accessoryID=".$pageID);
    $accessLangs   = udb::key_row("SELECT * FROM `accessories_langs` WHERE `accessoryID` = " . $pageID, ['langID']);
}

$domainID = DomainList::active();
$langID   = LangList::active();
$areas = udb::key_value("SELECT `areaID`, `TITLE` FROM `areas` WHERE 1 ORDER BY `TITLE`");

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$accessData['accessoryName']?outDb($accessData['accessoryName']):"הוספת דף חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
		<?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<?php foreach(LangList::get() as $lid => $lang){ ?>
				<div class="language" data-id="<?=$lid?>">
					<div class="section">
						<div class="inptLine">
							<div class="label">כותרת</div>
							<input type="text" value='' name="title" class="inpt">
						</div>
					</div>
				</div>
			<?php } ?>
			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$accessData['accessoryPic']?>">
					</div>
				</div>
				<?php if($accessData['accessoryPic']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$accessData['accessoryPic']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
			<div style="clear:both;"></div>
			<div class="inputLblWrap">
				<div class="labelTo">אזור ראשי</div>
				<select name="city" title="ישוב">
					<option value="0"></option>
				</select>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">אזורים</div>
				<div class="selectAndCheck" id="areasChecks">
					<div class="choosenCheck"></div>
					<div class="checksWrrap">
						<?php
							foreach($areas as $aid => $aname)
								echo '<div><input type="checkbox" name="areas[]" value="' , $aid , '" ' , (in_array($aid, $siteData['areas']) ? 'checked="checked"' : '') , ' /> ' , $aname , '</div>';
						?>
					</div>
				</div>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">ישוב</div>
				<select name="city" title="ישוב">
					<option value="0">- - בחר ישוב - -</option>
				</select>
			</div>
			<div class="sepLineWrap">
				<div class="inputLblWrap">
					<div class="labelTo">תאריך התחלה</div>
					<input type="text" value="" name="startDate" class="datePick" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">תאריך סיום</div>
					<input type="text" value="" name="endDate" class="datePick" />
				</div>
			</div>
			<div class="sepLineWrap">
				<div class="inputLblWrap">
					<div class="labelTo">ממחיר</div>
					<input type="text" value="" name=""/>
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">עד מחיר</div>
					<input type="text" value="" name="" />
				</div>
			</div>
			<input type="hidden" name="facilities" value="">
			<?php for($j=0;$j<3;$j++) { ?>
				<div class="catName">שם קטגוריה</div>
				<div class="checksWrap">
					<?php for($i=0;$i<10;$i++) { ?>
					<div class="checkLabel checkIb">
						<div class="checkBoxWrap">
							<input class="checkBoxGr" type="checkbox" name="" id="ch<?=$j.$i?>">
							<label for="ch<?=$j.$i?>"></label>
						</div>
						<label for="ch<?=$j.$i?>">מאפיין <?=$i?></label>
					</div>
					<?php } ?>
				</div>
			<?php } ?>


			<?php 

			foreach(LangList::get() as $lid => $lang){ ?>

				<div class="language" data-id="<?=$lid?>">
					<div class="section txtarea big">
						<div class="label">טקסט</div>
						<textarea name="roomDesc" class="textEditor"><?=$roomLangs[$lid]['roomDesc']?></textarea>
					</div>
				</div>
			
			<?php }  ?>
			<div class="seoSection">
				<div class="miniTitle">עריכת SEO</div>
					
				
				<?php 
				foreach(LangList::get() as $lid => $lang){ ?>
					<div class="language" data-id="<?=$lid?>">
						<div class="section">
							<div class="inptLine">
								<div class="label">כותרת עמוד</div>
								<input type="text" value='' name="title" class="inpt">
							</div>
						</div>
						<div class="section txtarea">
							<div class="label">Keywords:</div>
							<textarea name="" class=""></textarea>
						</div>
						<div class="section txtarea">
							<div class="label">Description:</div>
							<textarea name="" class=""></textarea>
						</div>
						<a href="" target="_blank" style="direction:ltr;text-align:left;display:block">קישור לעמוד יופיע כאן</a>
					</div>
				<?php }  ?>
			</div>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$accessData['accessoryID']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>

<script src="../../../app/tinymce/tinymce.min.js"></script>
<script type="text/javascript">
$(function(){
    $.each({domain: <?=$domainID?>, language: <?=$langID?>}, function(cl, v){
        $('.' + cl).hide().each(function(){
            var id = $(this).data('id');
            $(this).find('input, select, textarea').each(function(){
                this.name = this.name + '[' + id + ']';
            });
        }).filter('[data-id="' + v + '"]').show();

        $('.' + cl + 'Selector').on('change', function(){
            $('.' + cl, $(this).data('cont')).hide().filter('[data-id="' + this.value + '"]').show();
        });
    });

	tinymce.init({
	  selector: 'textarea.textEditor' ,
	  height: 300,  
	 plugins: [
		"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
		"table contextmenu directionality emoticons template textcolor paste  textcolor colorpicker textpattern"
	  ],
	  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
	  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
	  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking template pagebreak restoredraft"

	});

	/*facilities save to one input*/
	var hidenInputFac = $("input[name='facilities']");
	var facilArr = [];
	if(hidenInputFac.val()){
		facilArr = [hidenInputFac.val()];
	}
	$('.checkBoxGr').change(function(){
	
		if($(this).is(':checked')){
			facilArr.push($(this).attr('id'));
		}
		else{
			facilArr.splice($.inArray($(this).attr('id')), 1 );
		}
		hidenInputFac.val(facilArr);
	});

	$(".datePick").datepicker({
		"minDate":0
	});




})

</script>