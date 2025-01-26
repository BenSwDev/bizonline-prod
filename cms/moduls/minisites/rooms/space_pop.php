<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";


const BASE_LANG_ID = 1;


$roomId = intval($_GET['roomID']);
$siteID = intval($_GET['siteID']);
$spaceID = intval($_GET['spaceID']);


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {

        $data = typemap($_POST, [
            'spaceTitle'   => ['int' => 'string'],
            'spaceNotes'   => ['int' => 'string'],
            'spaceDesc'   => ['int' => 'string'],
            'roomType'   => 'int'

        ]);

        if (!$data['spaceTitle'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');

        // main site data
        $siteData = [

            'spaceName' => $data['spaceTitle'][BASE_LANG_ID],
            'spaceType' => $data['roomType'],
            'spaceDesc' => $data['spaceDesc'],
            'roomID'    => $roomId
        ];



        if (!$spaceID){// opening new site
			$spaceCount = udb::single_value("select count(*) from spaces where roomID=".$roomId);
			$siteData['showOrder'] = intval($spaceCount) + 1;
            $spaceID = udb::insert('spaces', $siteData);
        } else {
            udb::update('spaces', $siteData, '`spaceID` = ' . $spaceID);

        }

		//insert note to accessory if has one
		udb::query("DELETE FROM `spaces_accessories_langs` WHERE spaceID=".$spaceID);
		foreach($_POST['descToAttr'] as $key => $descToAttr){
			foreach(LangList::get() as $lid => $lang){
				if($descToAttr[$lid]){
					udb::insert('spaces_accessories_langs', [
						'spaceID'    => $spaceID,
						'langID'    => $lid,
						'accessoryID' => $key,
						'translate'  => $descToAttr[$lid]
					], true);
				}
			}
		}
		//saving spaces_accessories
		if(count($_POST['attrCheck'])){
			udb::query("DELETE FROM `spaces_accessories` WHERE spaceID=".$spaceID);
			$spaAcc = [];
			foreach($_POST['attrCheck'] as $att){
				$spaAcc['accessoryID'] = $att;
				$spaAcc['spaceID'] = $spaceID;
				udb::insert('spaces_accessories', $spaAcc);
			}
		}else{

				udb::query("DELETE FROM `spaces_accessories` WHERE spaceID=".$spaceID);
		}

		//*save here*//


		// saving data per language
		foreach(LangList::get() as $lid => $lang){
			udb::insert('spaces_langs', [
				'spaceID'    => $spaceID,
				'langID'    => $lid,
				'spaceName'  => $data['spaceTitle'][$lid],
				'spaceNotes'  => $data['spaceNotes'][$lid],
				'spaceDesc'  => $data['spaceDesc'][$lid]
			], true);
		}

    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}




$domainID = DomainList::active();
$langID   = LangList::active();


if($spaceID){
   	$spaceRow = udb::single_row("SELECT * FROM `spaces` WHERE `spaceID`=".$spaceID);
	$spaceLangs = udb::key_row("SELECT * FROM `spaces_langs` WHERE `spaceID` = " . $spaceID, 'langID');
	$accessories = udb::full_list("SELECT * , IF(`defaultSpace` = " . $spaceRow['spaceType'] . ", 1, 0) as `ord` FROM accessories WHERE 1 ORDER BY `ord` DESC");
	if($spaceRow['galleryID'])
	$gallery = udb::single_row("SELECT * FROM galleries WHERE galleryID=".$spaceRow['galleryID']);
	if($spaceRow){
		$spaceAccess = udb::key_row("SELECT accessoryID FROM `spaces_accessories` WHERE spaceID=" . $spaceID , 'accessoryID');
		$spaceAccessLangs = udb::key_row("SELECT * FROM `spaces_accessories_langs` WHERE `spaceID` = " . $spaceID, ['accessoryID','langID']);
	}
}else{

	$accessories = udb::full_list("SELECT * FROM accessories WHERE 1");
}


$spacesTypes = udb::full_list("SELECT * FROM spaces_type WHERE 1");


?>



<div class="editItems frameContent ">
	<div class="popGallery">
		<div class="popGalleryCont"></div>
	</div>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
		<?=LangList::html_select()?>
	</div>

	<form method="post">
        <h3 style="color: red;">נא לשים לב , העריכה של החדרים ביחידות הינה אחידה לכל הדומיינים</h3>
		<?php foreach(LangList::get() as $lid => $lang){ ?>
			<div class="language" data-id="<?=$lid?>">
				<div class="inputLblWrap">
					<div class="labelTo">שם החדר</div>
					<input type="text" placeholder="שם החדר" name="spaceTitle" value="<?=js_safe($spaceLangs[$lid]['spaceName'])?>" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">הערות לחדר</div>
					<input type="text" placeholder="הערות לחדר" name="spaceNotes" value="<?=js_safe($spaceLangs[$lid]['spaceNotes'])?>" />
				</div>
			</div>
		<?php } ?>

		<div class="inputLblWrap">
			<div class="labelTo">סוג החדר</div>
			<select name="roomType" id="spaceTypeSelect">
				<?php foreach($spacesTypes as $spaces) { ?>
					<option value="<?=$spaces['id']?>" <?=($spaceRow['spaceType']==$spaces['id']?"selected":"")?> ><?=$spaces['spaceName']?></option>
				<?php } ?>
			</select>
		</div>
		<?php foreach(LangList::get() as $lid => $lang){ ?>
			<div class="language" data-id="<?=$lid?>">
				<div class="section txtarea big">
					<div class="label">תיאור קצר: </div>
					<textarea name="spaceDesc" class="shortextEditor" title=""><?=$spaceLangs[$lid]['spaceDesc']?></textarea>
				</div>
			</div>
		<?php } ?>
		<div class="statSection">
			<div class="statSectionTtl">איבזורים</div>
			<div class="facilWrap">
			<?php
			foreach($accessories as $fac) { ?>
				<div class="checkLabel checkIb" data-space="<?=$fac['defaultSpace']?>">
					<div class="checkBoxWrap">
						<input <?=($spaceAccess[$fac['accessoryID']]['accessoryID']==$fac['accessoryID']?"checked":"")?> type="checkbox" name="attrCheck[]" id="ch<?=$fac['accessoryID'].$lid?>" value="<?=$fac['accessoryID']?>">
						<label for="ch<?=$fac['accessoryID'].$lid?>"></label>
					</div>
					<label for="ch<?=$fac['accessoryID'].$lid?>"><?=$fac['accessoryName']?></label>
					<?php foreach(LangList::get() as $lid => $lang){ ?>
					<div class="language" data-id="<?=$lid?>">
						<input type="text" name="descToAttr[<?=$fac['accessoryID']?>]" placeholder="תאור קצר לאבזור" value="<?=$spaceAccessLangs[$fac['accessoryID']][$lid]['translate']?>" style="margin-top:8px">
					</div>
					<?php } ?>
				</div>
			<?php } ?>
			</div>
		</div>
		<div class="mainSectionWrapper open">
			<div class="sectionName">גלריה</div>
			<div class="manageItems">
				<div class="addButton" style="margin-top: 20px;">
					<?php foreach(DomainList::get() as $domid => $dom){ ?>
						<div class="domain" data-id="<?=$domid?>">
							<div class="tableWrap">
								<div class="rowWrap top">
									<!-- <div class="tblCell">#</div> -->
									<div class="tblCell">ID</div>
									<div class="tblCell">שם הגלריה</div>
									<div class="tblCell"></div>
								</div>

								<?php
								if($gallery){ ?>
								<div class="rowWrap">
									<!-- <div class="tblCell">**</div> -->
									<div class="tblCell"><?=$gallery['galleryID']?></div>
									<div class="tblCell"><?=$gallery['galleryTitle']?></div>
									<div class="tblCell"><span onclick="galleryOpenSpace(<?=$domid.",".$siteID.",".$spaceID.",".$spaceRow['galleryID']?>)"  class="editGalBtn">ערוך גלריה</span>
									</div>
								</div>
								<?php  } ?>
							</div>
							<?php if(!$gallery){ ?>
							<div class="addNewBtnWrap">
								<input type="button" class="addNew" id="addNewAcc<?=$domid?>" value="הוסף חדש" onclick="galleryOpenSpace(<?=$domid.",".$siteID.",".$spaceID?>,'new')" >
							</div>
							<?php } ?>

						</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<input type="submit" value="שמור" class="submit">
	</form>
</div>

<script type="text/javascript">
	//var elme = window.parent.document.getElementById("frame_<?=$siteID?>_<?=$roomId?>");
	//elem.scrolling = "no";


	function galleryOpenSpace(domainID,siteID, spaceID, galleryID){
		$(".popGalleryCont").html('<iframe width="100%" height="100%" id="frameGal_'+spaceID+'" frameborder=0 src="/cms/moduls/minisites/galleryGlobalSpace.php?domainID='+domainID+'&siteID='+siteID+'&spaceID='+spaceID+'&gID='+galleryID+'"></iframe><div class="tabCloserSpace" onclick="tabCloserGlobGalSpace(\'frameGal_'+spaceID+'\')">x</div>');
		$(".popGallery").show();
		var elme = window.parent.document.getElementById("frame_"+spaceID);
		elme.style.zIndex="16";
		elme.style.position="relative";
	}



	function tabCloserGlobGalSpace(id){
		$(".popGalleryCont").html('');
		$(".popGallery").hide();
		var elme = window.parent.document.getElementById(id);
		$('#mainContainer').css("overflow","visible");
		elme.style.zIndex="12";
		elme.style.position ="static";
	}

	$('#spaceTypeSelect').change(function(){
		var select = this.value;
		$('.checkIb').sort(function(a, b){
			return $(a).data('space') == select ? -1 : ($(b).data('space') == select ? 1 : 0);
		}).appendTo('.facilWrap');

	});


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
	$("html , body").scrollTop(10);
	})
</script>