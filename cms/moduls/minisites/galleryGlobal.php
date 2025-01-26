<?php

include_once "../../bin/system.php";
include_once "../../bin/top_frame.php";
include_once "../../_globalFunction.php";


//$domainID = intval($_GET['domainID']);
$siteID = intval($_GET['siteID']);
$galleryID = intval($_GET['gID']);
$roomID = intval($_GET['roomID']);
//$siteName = intval($_GET['siteName']);
$siteMainGallery = $_GET['siteMainGallery'];

$domainID = DomainList::active();
$langID   = LangList::active();


if ('POST' == $_SERVER['REQUEST_METHOD']){

//	print_R($_POST);exit;

	try {
	 $updateAllGalleries = intval($_POST['updateAllGalleries']);

     $data = typemap($_POST, [
		'galleryTitle'   => 'string',
		'oldgalleryTitle'=> 'string',
		'!gallerySummer'   => 'int',
		'!galleryWinter'   => 'int',
		'imTitle'  => ['int' => ['int' => 'string']],
		'imDesc'   => ['int' => ['int' => 'string']],
		'imLink'   => ['int' => ['int' => 'string']],
		'imID'     => ['int']

	]);


        if (!$data['galleryTitle'])
            throw new LocalException('נא להכניס שם לגלריה');


        if($galleryID) {
            $eqGalleryIds = udb::single_column("select galleryID from galleries where orgGalID=" . $galleryID);
        }
		if($_POST['orderResult'] && count(explode(",",$_POST['orderResult']))) {
			$ids = str_replace("imageBoxList_","",$_POST['orderResult']);
			$ids = explode(",",$ids);
			if($ids) {
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

            if($data['oldgalleryTitle'] != $data['galleryTitle']) {
                udb::update("galleries",array("galleryTitle" => $data['galleryTitle'])," galleryID=".$galleryID);
                if($updateAllGalleries){

                    udb::update("galleries",array("galleryTitle" => $data['galleryTitle'])," orgGalID=".$galleryID);
                }
            }



			if($_FILES['images'] && $_FILES['images']['name'][0]){



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

			if($_POST['img']){
				 $count = 0;
				 $que = "SELECT max(`showOrder`) FROM pictures WHERE galleryID=".$galleryID;
				 $count = udb::single_value($que);
                $picData = $picData2 = [];
				foreach($_POST['img'] as $key => $pos){
					$picData['galleryID'] = $galleryID;
					$picData['fileID'] = $pos;
					$picData['showOrder'] = $count+$key+1;
					$picID = udb::insert('pictures', $picData,true);
					$pidsIDs2 = [];
                    if($updateAllGalleries) {
                        foreach ($eqGalleryIds as $kgal=>$eqgalID) {
                            $picData2['galleryID'] = $eqgalID;
                            $picData2['fileID'] = $pos;
                            $picData2['showOrder'] = $count+$key+1;
                            $picsIDs2[] = udb::insert('pictures', $picData,true);
                        }

                    }

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
                        if($updateAllGalleries) {
                            foreach ($picsIDs2 as $picID2) {
                                udb::insert('pictures_text', [
                                    'pictureID'    => $picID2,
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
			 }


			if($roomID){
				$galData['gallerySummer'] = $galleryID;
			   $galData['galleryWinter'] = $galleryID;
				udb::update('rooms', $galData, '`roomID` = ' . $roomID);
			}
			else{
				$galData['gallerySummer'] = $galleryID;
			    $galData['galleryWinter'] = $galleryID;
				udb::update('sites', $galData, '`siteID` = ' . $siteID);
			}

			if($updateAllGalleries) {
                $useRoom = null;
                $useSiteMainGallery = null;
                if($roomID) $useRoom = $roomID;
                if($siteMainGallery) $useSiteMainGallery = $siteMainGallery;
			    foreach ($eqGalleryIds as $eqgalID) {
			        //delete from sites_gallery
                    udb::query("DELETE p.* FROM pictures p INNER JOIN pictures_text USING (pictureID) WHERE `galleryID`=".$eqgalID);
                    udb::query("DELETE FROM galleries WHERE `galleryID`=".$eqgalID);
                    udb::query("DELETE FROM sites_galleries WHERE `galleryID`=".$eqgalID);
                    udb::query("DELETE FROM rooms_galleries WHERE `galleryID`=".$eqgalID);
                    if($useSiteMainGallery){
                        udb::query("DELETE FROM ".$useSiteMainGallery." WHERE `galleryID`=".$eqgalID);
                    }
                    //delete from rooms_gallrey
                    dupGallery($galleryID , $useRoom , -1 , $domainID ,$useSiteMainGallery);
                }
            }


		}
		else{
		    $maxOrder = udb::single_value("SELECT max(sites_galleries.showOrder) FROM `sites_galleries` LEFT JOIN galleries USING (galleryID) WHERE galleries.domainId=".$domainID." and sites_galleries.`siteID`=".$siteID." order by sites_galleries.showOrder ASC");
            $maxOrder++;
			 $siteData = [
				'siteID'  => $siteID,
				'domainID'  => $domainID,
				'galleryTitle'	 => $data['galleryTitle']
			];
			 $gallID = udb::insert('galleries', $siteData);

			 if($roomID){
				 udb::insert('rooms_galleries', ['roomID'  => $roomID,'galleryID' =>  $gallID]);
				 $galData['galleryID']=$gallID;
				 $galData['gallerySummer'] = $gallID;
			     $galData['galleryWinter'] = $gallID;
				 udb::update('rooms', $galData, '`roomID` = ' . $roomID);
			 }else{
				 if($siteMainGallery) {
					 udb::insert($siteMainGallery, ['siteID'  => $siteID,'galleryID' =>  $gallID, 'domainID'=> $domainID]);
					 // $galData['galleryID']=$gallID;
					 // $galData['mainGallerySummer'] = $gallID;
					 // $galData['mainGalleryWinter'] = $gallID;
					 // udb::update('sites', $galData, '`siteID` = ' . $siteID);
				 }
				 else {
					udb::insert('sites_galleries', ['siteID'  => $siteID,'galleryID' =>  $gallID,'showOrder'=>$maxOrder]);
					$galData['galleryID']=$gallID;
					$galData['gallerySummer'] = $gallID;
					$galData['galleryWinter'] = $gallID;
					udb::update('sites', $galData, '`siteID` = ' . $siteID);
				 }

			 }

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
			if (is_array($_POST['img'] ?? null))
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
//            if($domainID == 1 ) {
//                $useRoom = null;
//                $useSiteMainGallery = null;
//                if($roomID) $useRoom = $roomID;
//                if($siteMainGallery) $useSiteMainGallery = $siteMainGallery;
//                dupGallery($gallID , $useRoom , -1 , $domainID ,$useSiteMainGallery);
//            }


		}


}
    catch (LocalException $e){
        // show error
    }
?>
	<script id="galtest">
	$('.tabCloser', window.parent.parent.document).css('z-index','16');
	<?if($galleryID || $siteMainGallery){
		//frame_1_392_7570
		$id = 'frame_' . $domainID . '_'  . $siteID  . '_'.$galleryID;
		if($siteMainGallery) {
			echo 'window.parent.tabCloserGlobGalMain("'.$id.'",window.parent.scrollToElement,"mainGallery");';
		}
		else {
			echo 'window.parent.tabCloserGlobGal("frame_'.$siteID.'",window.parent.scrollToElement,"sitesGalleries");';
		}

	}
	else {
		if($roomID) {
			$newRoom = '<div class="rowWrap" id="galRow'.$gallID.'"><div class="tblCell">'.$gallID.'</div><div class="tblCell">'.$data['galleryTitle'].'</div><div class="tblCell"><span onclick="galleryOpen('.$langID.','.$siteID.','.$roomID.','.$gallID.')" class="editGalBtn">ערוך גלריה</span><div class="dupGalWrap"><select name="galWrapSelect[1]" id="galWrapSelect"><option value="-1">כל הדומיינים</option><option value="1">bizonline</option><option value="6">Vii</option></select><span class="editGalBtn" onclick="dupGal('.$gallID.','.$langID.','.$roomID.')">שכפל גלריה</span></div></div><div class="tblCell"></div></div>';
			echo  "var newRoom = '".$newRoom."';";
			echo "if($('#gallery".$domainID."', window.parent.document).length) { ";
			echo "$('#gallery".$domainID."', window.parent.document).append(newRoom);";
			echo " } ";
			echo "else { ";
			echo "window.parent.location.reload(); ";
			echo "}";
			echo 'window.parent.tabCloserGlobGal("frame_'.$siteID.'",window.parent.scrollToElement,"sitesGalleries");';
		}
		else {
            echo 'window.parent.tabCloserGlobGal("frame_'.$siteID.'",window.parent.scrollToElement,"sitesGalleries");';
		}

	}
	?>
	</script>

<?php
exit;
}


$que = "SELECT `folderID`,folderTitle FROM `folder` WHERE siteID=".$siteID;
$folders = udb::key_row($que,"folderID");

$galleryFolderName = udb::single_value("SELECT `galleryTitle` FROM `galleries` WHERE galleryID=".$galleryID);
if($galleryID){
	$picList = udb::key_row("SELECT `files`.`src`, `pictures`.`fileID`, `pictures`.`pictureID`, `pictures`.`showOrder` 
	FROM `pictures` 
	INNER JOIN `files` ON `pictures`.`fileID`= `files`.`id`
	WHERE galleryID=".$galleryID." ORDER BY `showOrder`","fileID");
	$picLangs   = udb::key_row("SELECT * FROM `pictures_text` INNER JOIN pictures USING (pictureID)  WHERE `pictures`.`galleryID` = " . $galleryID, ['pictureID','langID']);

	if($siteID){
		$galType = udb::single_row("SELECT galleryWinter,gallerySummer FROM `sites` WHERE siteID=".$siteID);
	}
	if($roomID){
		$galType = udb::single_row("SELECT galleryWinter,gallerySummer FROM `rooms` WHERE roomID=".$roomID);
	}

} ?>
<style>
    .delmultiple {
        position: absolute;
        display: block;
        top: 7px;
        left:auto;
        right:7px;
        width:30px;
        height: 30px;
    }
</style>
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
            <?if($galleryID && $domainID == 1) {?>
            <div class="inputLblWrap">
                <div class="labelTo">עדכן גלריות מקבילות</div>
                <input class="checkBoxGr" type="checkbox" value="0" name="updateAllGalleries">

            </div><?}?>
			<div class="inputLblWrap">
				<div class="labelTo">שם הגלריה</div>
				<input type="text" placeholder="שם הגלריה" name="galleryTitle" value="<?=$galleryID?$galleryFolderName:"גלריה מייצגת"?>" />
				<input type="hidden" name="oldgalleryTitle" value="<?=$galleryID?$galleryFolderName:"גלריה מייצגת"?>" />
			</div>
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

		</div>


		<div class="imagesWrapSelected imagWrap">
			<?php
			if($picList) { ?>
				<div class="frameTtl">תמונות שנבחרו</div>
				<div class="sortBtn" onclick="startGalOrder(this)">סדר תמונות</div>
				<div class="sortBtn showDescBtn">הצג/הסתר תיאור</div>
				<div class="sortBtn removeAllPictures" style="background:red" onclick="deleteallPictures()">מחק את כל התמונות </div>
                <div class="sortBtn removellPicturesFromGallery" onclick="removellPictures()">הסר תמונות נבחרות מהגלרייה</div>
			<?php } ?>
			<div class="imgGalFrWrap" id="selectedPictures">
			<?php
			if($picList) {
			$ids="";
			$i=0;
			foreach($picList as $pic){
				$pic['src'] = str_replace('gallery/', 'gallery/thumb/600/', $pic['src']);
                $exists       = (!strpos($pic['src'], '/thumb/') || file_exists(__DIR__ . '/../../..' . $pic['src']));
                $picTag       = $exists ? ' src="' . picturePath($pic['src']) . '" ' : ' src="about:blank" data-thumb="' . picturePath($pic['src']) . '" class="lazy-thumb"';

				$ids.=($i!=0?",":"")."imageBoxList_".$pic['fileID']; ?>
				<div class="imgGalFr chos" id="imageBoxList_<?=$pic['fileID']?>" data-picid="<?=$pic['pictureID']?>" data-itemid="imageBoxList_<?=$pic['fileID']?>">
                    <input type="checkbox" name="delmultiple" class="delmultiple" value="<?=$pic['pictureID']?>_<?=$pic['fileID']?>" />
					<div class="delPic" id="delpic_<?=$pic['pictureID']?>_<?=$pic['fileID']?>" onclick="delPic(<?=$pic['pictureID']?>,'imageBoxList_<?=$pic['fileID']?>')"><i class="fa fa-trash-o" aria-hidden="true"></i></div>
					<div class="pic"><a href="<?=picturePath($pic['src'])?>" data-lightbox="image-1" data-title="<?=htmlentities(stripslashes($pic['title']))?>"><img <?=$picTag?> title="" /></a></div>
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
							
				$image['src'] = str_replace('gallery/', 'gallery/thumb/600/', $image['src']);
                $exists       = (!strpos($image['src'], '/thumb/') || file_exists(__DIR__ . '/../../..' . $image['src']));
                $picTag       = $exists ? ' src="' . picturePath($image['src']) . '" ' : ' src="about:blank" data-thumb="' . picturePath($image['src']) . '" class="lazy-thumb"';

						if(!$picList[$image['id']]['fileID']==$image['id']) {
						$ids.=($i!=0?",":"")."imageBox_".$image['id']; ?>
						<div class="imgGalFr" id="imageBox_<?=$image['id']?>">
							<label for="imgcheck<?=$image['id']?>">סמן</label>
							<input <?=($picList[$image['id']]['fileID']==$image['id']?"checked":"")?>  class="choosePic" value="<?=$image['id']?>" type="checkbox" name="img[]" id="imgcheck<?=$image['id']?>">
							<div class="pic"><a href="<?=picturePath($image['src'])?>" data-lightbox="image-1" data-title="<?=htmlentities(stripslashes($image['title']))?>"><img <?=$picTag?> title="" /></a></div>
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

    function removellPictures(){
        if(confirm("האם להסיר את התמונות שנבחרו מהגלריה?")){

            var selPicscount = $(".delmultiple:checked").length;
            var counter = 0;
            $(".delmultiple:checked").each(function(){
                var itemVal = $(this).val();
                var itemid = itemVal.split("_");
                itemid = itemid[0];
                $.post("ajax_del_picture.php",{picID:itemid}).done(function(){
                    counter++;
                    if(counter >= selPicscount) {
                        window.location.reload();
                    }
                });
            });

        }
    }

	function deleteallPictures(){
		if(confirm("האם אתם בטוחים שברצונכם למחוק את כל התמונות שנבחרו בגלריה?")){
			var saveBtnHtml = $(".removeAllPictures").html();
			$(".removeAllPictures").html("מוחק נא להמתין");
			$(".removeAllPictures").css("color","red");
			var allPics = $("#selectedPictures .imgGalFr.chos").length;
			var counter = 0;
			debugger;
			$("#selectedPictures .imgGalFr.chos").each(function(){
				var id = $(this).data("picid");
				var itemid = $(this).data("itemid");
				counter++;
				$.post("ajax_del_picture.php",{picID:id}).done(function(){
					$("#" + itemid).remove();
				});
				if(allPics >= counter) {
					$(".removeAllPictures").html(saveBtnHtml);
					$(".removeAllPictures").css("color","#FFF");
				}
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

        let lazy = $('.lazy-thumb').on('load', function(){
            window.setTimeout(loadNext, 500);
        }).get(), max = Math.min(1, lazy.length), i;

        console.log('Found ' + lazy.length + ' lazy pictures');

        function loadNext(){
            let img = lazy.shift();
            if (img && img.dataset.thumb)
                img.src = img.dataset.thumb;

            console.log('Loading ' + img.dataset.thumb);
        }

        for(i = 0; i < max; ++i)
            loadNext();
	});
</script>
