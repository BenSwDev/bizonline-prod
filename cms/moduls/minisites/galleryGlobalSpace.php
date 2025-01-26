<?php

include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";


//$domainID = intval($_GET['domainID']);
$siteID = intval($_GET['siteID']);
$galleryID = intval($_GET['gID']);
$spaceID = intval($_GET['spaceID']);
//$siteName = intval($_GET['siteName']);
//$siteMainGallery = $_GET['siteMainGallery'];

$domainID = DomainList::active();
$langID   = LangList::active();


if ('POST' == $_SERVER['REQUEST_METHOD']){


	try {
     $data = typemap($_POST, [
		'galleryTitle'   => 'string',
		'imTitle'  => ['int' => ['int' => 'string']],
		'imDesc'   => ['int' => ['int' => 'string']],
		'imLink'   => ['int' => ['int' => 'string']],
		'imID'     => ['int']

	]);


        if (!$data['galleryTitle'])
            throw new LocalException('נא להכניס שם לגלריה');


	
		if($_POST['orderResult'] && count($_POST['orderResult']))
		{
			$ids = str_replace("imageBoxList_","",$_POST['orderResult']);
			$ids = explode(",",$ids);
			if($ids)
			{
				foreach($ids as $key=>$id)
				{
					if($id)
					{
						$query=Array();
						$query['showOrder']=$key+1;
						udb::update("pictures", $query, "galleryID='".$galleryID."' AND  fileID=".$id);
					}
				}	
			}
		}
		if($data['imID']){
			foreach($data['imID'] as $picID){
			// saving data per domain / language
				foreach(LangList::get() as $lid => $lang){
					// inserting/updating data in domains table
					udb::insert('pictures_text', [
						'pictureID'    => $picID,
						'langID'    => $lid,
					//	'galleryID'    => $galleryID,
						'pictureTitle'  => $data['imTitle'][$picID][$lid],
						'pictureDesc'   => $data['imDesc'][$picID][$lid],
						'pictureLink'   => $data['imLink'][$picID][$lid]
					], true);
				}
			}
		}
	

		if($galleryID){
/*
			if($_FILES['images'] && $_FILES['images']['name'][0]){

				$que = "SELECT `folderID` FROM `folder` WHERE folderTitle='".$data['galleryTitle']."' AND siteID=".$siteID;
				$folderID = udb::single_value($que);

				if(!$folderID) {
					$folderID = udb::insert("folder", ["siteID" => $siteID, "folderTitle" => $data['galleryTitle'], 'isMain' => 0]);
				}
				$newFile = [];
				$cnt = count($_FILES['images']['name']);
				
				$count = 0;
				$que = "SELECT max(`showOrder`) FROM pictures WHERE galleryID=".$galleryID;
				$count = udb::single_value($que);


				for($i=0;$i<$cnt;$i++){
					foreach($_FILES['images'] as $key => $picKey){
						$newFile[$key] = $picKey[$i];
					}
					$file = new Core\Files\Optimizer($newFile);
					$resultAws = $file->saveToLocal('/gallery');
					$fileArr=Array();
					$fileArr['src']=$resultAws['ssd_db_path'];
					$fileArr['table']="folder";
					$fileArr['ref']=$folderID;
					
					$filePic = udb::insert("files", $fileArr);

					$picData['showOrder'] = $count+$i+1;
					$picData['galleryID'] = $galleryID;
					$picData['fileID'] = $filePic;
					$picID = udb::insert('pictures', $picData,true);


					foreach(LangList::get() as $lid => $lang){
						// inserting/updating data in domains table
						udb::insert('pictures_text', [
							'pictureID'    => $picID,
							'langID'    => $lid,
						//	'galleryID'    => $galleryID,
							'pictureTitle'  => $data['imTitle'][$picID][$lid],
							'pictureDesc'   => $data['imDesc'][$picID][$lid],
							'pictureLink'   => $data['imLink'][$picID][$lid]
						], true);
					}

				}
			}
*/
			if($_POST['img']){
				 $count = 0;
				 $que = "SELECT max(`showOrder`) FROM pictures WHERE galleryID=".$galleryID;
				 $count = udb::single_value($que);
				foreach($_POST['img'] as $key => $pos){
					$picData['galleryID'] = $galleryID;
					$picData['fileID'] = $pos;
					$picData['showOrder'] = $count+$key+1;
					$picID = udb::insert('pictures', $picData,true);

					foreach(LangList::get() as $lid => $lang){
						// inserting/updating data in domains table
						udb::insert('pictures_text', [
							'pictureID'    => $picID,
							'langID'    => $lid,
						//	'galleryID'    => $galleryID,
							'pictureTitle'  => $data['imTitle'][$picID][$lid],
							'pictureDesc'   => $data['imDesc'][$picID][$lid],
							'pictureLink'   => $data['imLink'][$picID][$lid]
						], true);
					}
					
				}
			 }


			if($spaceID){
				$galData['galleryID'] = $galleryID;
				udb::update('spaces', $galData, '`spaceID` = ' . $spaceID);
			}



		}
		else{
			
			 $siteData = [
				'siteID'  => $siteID,
				'domainID'  => $domainID,
				'galleryTitle'	 => $data['galleryTitle'],
				'spaceGallery'	 => 1
			];
			 $gallID = udb::insert('galleries', $siteData);


			if($spaceID){
				$galData['galleryID'] = $gallID;
				udb::update('spaces', $galData, '`spaceID` = ' . $spaceID);
			}
/*
			if($_FILES['images'] && $_FILES['images']['name'][0]){

				$que = "SELECT `folderID` FROM `folder` WHERE folderTitle='".$data['galleryTitle']."' AND siteID=".$siteID;
					$folderID = udb::single_value($que);

					if(!$folderID) {
						$folderID = udb::insert("folder", ["siteID" => $siteID, "folderTitle" => $data['galleryTitle'], 'isMain' => 0]);
					}
					$newFile = [];
					$cnt = count($_FILES['images']['name']);
						

					for($i=0;$i<$cnt;$i++){
						foreach($_FILES['images'] as $key => $picKey){
							$newFile[$key] = $picKey[$i];
						}
						$file = new Core\Files\Optimizer($newFile);
						$resultAws = $file->saveToLocal('/gallery');
						$fileArr=Array();
						$fileArr['src']=$resultAws['ssd_db_path'];
						$fileArr['table']="folder";
						$fileArr['ref']=$folderID;
						
						$filePic = udb::insert("files", $fileArr);

						$picData['galleryID'] = $gallID;
						$picData['fileID'] = $filePic;
						$picID = udb::insert('pictures', $picData,true);

						foreach(LangList::get() as $lid => $lang){
							// inserting/updating data in domains table
							udb::insert('pictures_text', [
								'pictureID'    => $picID,
								'langID'    => $lid,
							//	'galleryID'    => $galleryID,
								'pictureTitle'  => $data['imTitle'][$picID][$lid],
								'pictureDesc'   => $data['imDesc'][$picID][$lid],
								'pictureLink'   => $data['imLink'][$picID][$lid]
							], true);
						}
					}
				}
				*/
			 foreach($_POST['img'] as $key => $pos){
				$picData['galleryID'] = $gallID;
				$picData['fileID'] = $pos;
				$picData['showOrder'] = $key+1;
				$picID = udb::insert('pictures', $picData);

				foreach(LangList::get() as $lid => $lang){
					// inserting/updating data in domains table
					udb::insert('pictures_text', [
						'pictureID'    => $picID,
						'langID'    => $lid,
					//	'galleryID'    => $galleryID,
						'pictureTitle'  => $data['imTitle'][$picID][$lid],
						'pictureDesc'   => $data['imDesc'][$picID][$lid],
						'pictureLink'   => $data['imLink'][$picID][$lid]
					], true);
				}


			 }
			
		}

	
}
    catch (LocalException $e){
        // show error
    } ?>
	<script>$('.tabCloser', window.parent.parent.document).css('z-index','16');
window.parent.location.reload(); window.parent.closeTab();</script>

<?php }


$que = "SELECT `folderID`,folderTitle FROM `folder` WHERE siteID=".$siteID;
$folders = udb::key_row($que,"folderID");

$galleryFolderName = udb::single_value("SELECT `galleryTitle` FROM `galleries` WHERE galleryID=".$galleryID);
if($galleryID){
	$picList = udb::key_row("SELECT `files`.`src`, `pictures`.`fileID`, `pictures`.`pictureID`, `pictures`.`showOrder` 
	FROM `pictures` 
	INNER JOIN `files` ON `pictures`.`fileID`= `files`.`id`
	WHERE galleryID=".$galleryID." ORDER BY `showOrder`","fileID");
	$picLangs   = udb::key_row("SELECT * FROM `pictures_text` INNER JOIN pictures USING (pictureID)  WHERE `pictures`.`galleryID` = " . $galleryID, ['pictureID','langID']);
/*
	if($siteID){
		$galType = udb::single_row("SELECT galleryWinter,gallerySummer FROM `sites` WHERE siteID=".$siteID);
	}
	if($roomID){
		$galType = udb::single_row("SELECT galleryWinter,gallerySummer FROM `rooms` WHERE roomID=".$roomID);
	}
*/
} ?>

<div class="editItems">
	<form method="post" enctype="multipart/form-data" >
		<div class="inputLblWrap langsdom domainsHide">
			<div class="labelTo">דומיין</div>
			<?=DomainList::html_select()?>
		</div>
		<div class="inputLblWrap langsdom">
			<div class="labelTo">שפה</div>
			<?=LangList::html_select()?>
		</div>

		<div class="galName">

			<div class="inputLblWrap">
				<div class="labelTo">שם הגלריה</div>
				<input type="text" placeholder="שם הגלריה" name="galleryTitle" value="<?=$galleryID?$galleryFolderName:"גלריה מייצגת"?>" />
			</div>
<?php /*
			<div class="section" style="float:left;display:block;">
				<div class="inptLine">
					<label for="imagesUpload" class="uploadLabel">העלאת תמונות</label>
				</div>
			</div>
			<input type="file" id="imagesUpload" name="images[]" multiple style="visibility: hidden;">


			<div class="inputLblWrap">
				<div class="labelTo">סוג גלריה</div>
				<div class="checkLabel checkIb">
					<div class="checkBoxWrap">
						<input class="checkBoxGr" type="checkbox" name="galleryWinter" value="1" id="ch1" <?=$galType['galleryWinter']==$galleryID && $galleryID!=0?" checked":""?>>
						<label for="ch1"></label>
					</div>
					<label for="ch1">גלרית חורף</label>
				</div>
				<div class="checkLabel checkIb">
					<div class="checkBoxWrap">
						<input class="checkBoxGr" type="checkbox" name="gallerySummer" value="1" id="ch2" <?=$galType['gallerySummer']==$galleryID && $galleryID!=0?" checked":""?>>
						<label for="ch2"></label>
					</div>
					<label for="ch2">גלרית קיץ</label>
				</div>
			</div>
*/?>

		</div>

		<div class="imagesWrapSelected imagWrap">
			<?php 
			if($picList) { ?>
				<div class="frameTtl">תמונות שנבחרו</div>
				<div class="sortBtn" onclick="startGalOrder(this)">סדר תמונות</div>
				<div class="sortBtn showDescBtn">הצג/הסתר תיאור</div>
			<?php } ?>
			<div class="imgGalFrWrap">
			<?php 
			if($picList) {
			$ids="";
			$i=0;
			foreach($picList as $pic){ 
				$ids.=($i!=0?",":"")."imageBoxList_".$pic['fileID']; ?>
				<div class="imgGalFr chos" id="imageBoxList_<?=$pic['fileID']?>">
					<div class="delPic" onclick="delPic(<?=$pic['pictureID']?>,'imageBoxList_<?=$pic['fileID']?>')"><i class="fa fa-trash-o" aria-hidden="true"></i></div>
					<div class="pic"><a href="<?=picturePath($pic['src'])?>" data-lightbox="image-1" data-title="<?=htmlentities(stripslashes($pic['title']))?>"><img src="<?=picturePath($pic['src'])?>"></a></div>
					<input type="hidden" name="imID[]" value="<?=$pic['pictureID']?>">
					<?php foreach(LangList::get() as $id => $lang){ ?>
					<div class="language" data-id="<?=$id?>">
						<div class="ttlWrap">
						   <div class="ttl"><input type="text" name="imTitle[<?=$pic['pictureID']?>]" value="<?=htmlentities(stripslashes($picLangs[$pic['pictureID']][$id]['pictureTitle']))?>" placeholder="כותרת"></div>
						  <div class="ttl"><input type="text" name="imDesc[<?=$pic['pictureID']?>]" value="<?=htmlentities(stripslashes($picLangs[$pic['pictureID']][$id]['pictureDesc']))?>" placeholder="תיאור"></div>
						  <div class="ttl"><input type="text" name="imLink[<?=$pic['pictureID']?>]" value="<?=htmlentities(stripslashes($picLangs[$pic['pictureID']][$id]['pictureLink']))?>" placeholder="קישור"></div>
						</div>
					</div>
					<?php } ?>
				</div>
			<?php $i++;  } } ?>	
			</div>
			<input type="hidden" id="orderResult" name="orderResult" value="<?=$ids?>">
		</div>
		<?php if($folders){ ?>
			<div class="imagWrap" id="gal1">
				<div class="frameTtl">בחירת תמונות מבנק תמונות</div>
				
				<?php 
				$ids="";
				$i=0;
				foreach($folders as $fold){ ?>
					<div class="folderWrap">		
						<?php 
						$pictureFold = udb::full_list("SELECT * FROM `files` WHERE ref=".$fold['folderID']);?>
						<div class="galTitle"><?=$fold['folderTitle']?></div>
						<div class="frameTtl"><a href="javascript:void(0)" class="checkAllBtn" data-state="0" onclick="checkAll($(this));">סמן הכל</a></div>
						<?php foreach($pictureFold as $image) {
						if(!$picList[$image['id']]['fileID']==$image['id']) {
						$ids.=($i!=0?",":"")."imageBox_".$image['id']; ?>
						<div class="imgGalFr" id="imageBox_<?=$image['id']?>">
							<label for="imgcheck<?=$image['id']?>">סמן</label>
							<input <?=($picList[$image['id']]['fileID']==$image['id']?"checked":"")?>  class="choosePic" value="<?=$image['id']?>" type="checkbox" name="img[]" id="imgcheck<?=$image['id']?>">
							<div class="pic"><a href="<?=picturePath($image['src'])?>" data-lightbox="image-1" data-title="<?=htmlentities(stripslashes($image['title']))?>"><img src="<?=picturePath($image['src'])?>"></a></div>
						</div>
					<?php $i++; } } ?>
					</div>
				
				<?php } ?>	
			</div>
			<!-- <div class="addPicBtnWrap">
				<input class="addPicBtn" type="submit" value="הוסף תמונות נבחרות">
			</div> -->
		<?php } ?>

		
		<div class="addPicBtnWrap">
			<input type="submit" name="saveOrder" value="שמור" class="addPicBtn">	
		</div>
	</form>
</div>



<script type="text/javascript">
		function checkAll(btnItem){
			if(btnItem.data("state") == "0") {
				btnItem.parent().siblings("input[type='checkbox'].choosePic");
				btnItem.closest(".folderWrap").find(".choosePic").attr("checked",false);
				btnItem.closest(".folderWrap").find(".choosePic").each(function(){
					$(this).trigger("click");
				});
				btnItem.data("state","1");
				btnItem.text("הסר הכל");
			}
			else {
				btnItem.closest(".folderWrap").find(".choosePic").attr("checked",false);
				btnItem.data("state","0");
				btnItem.text("סמן הכל");
			}
			
		}
		
        $("#imagesUpload").change(function(){
            if (typeof (FileReader) != "undefined") {
                var table = $(".imagWrap");
                var regex = /^([a-zA-Z0-9א-ת\s_\\.\-:])+(.jpg|.jpeg|.gif|.png|.bmp|.JPG|.JPEG|.GIF|.PNG|.BMP)$/;
                var id = 0;
                $($(this)[0].files).each(function () {
                    var file = $(this);
					var fileName = file[0].name.replace('(','');
					    fileName = fileName.replace(')','');
					 
					if (regex.test(fileName)) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            var img = '<div class="imgGalFr new" id="imageBox_' + id + '">'
                                + '<div class="pic"><img src="' + e.target.result + '" style="max-width:100%;max-height:100%;vertical-align: middle;"></div>'
                                + '</div >';
                            table.append(img);
                            id++;
                        };
                        reader.readAsDataURL(file[0]);
                    } else {
                        alert(file[0].name + " קובץ לא תקין");
                        return false;
                    }
                });
            } else {
                alert("ERROR");
            }
            $("#sortRow").sortable();
            $(this).hide();
            //$(".uploadLabel").hide();
        });

	function startGalOrder(is){
	//	$(".uploadLabel").hide();
	//	$(".imgGalFr input").attr("disabled", "disabled");
		$(".delPic").hide();
		$(".sortBtn.showDescBtn").hide();
		$(is).hide();
		$(".imgGalFr.chos").css({'box-shadow':'0 0 16px 0px rgba(0,0,0,0.8)','cursor':'pointer'});
		$(".imgGalFrWrap").sortable({
			stop: function(){
				$("#orderResult").val($(".imgGalFrWrap").sortable('toArray'));
			}
		});
		$("#orderResult").val($(".imgGalFrWrap").sortable('toArray'));
	}
		$('.showDescBtn').click(function(){
				$('.ttlWrap').toggleClass("show");
		});


	function delPic(id,removeElemnt){
		if(confirm("האם אתה מעוניין למחוק תמונה זו?")){
			$.post("ajax_del_picture.php",{picID:id}).done(function(){
				window.location.reload();
			});
		
		}
	}

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


	});
</script>