<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 26/12/2021
 * Time: 17:22
 */
include_once "../../bin/system.php";
include_once "../../_globalFunction.php";

$siteID = intval($_GET['siteID']);
$domainID = intval($_GET['domainID']);
$siteGalleries = udb::full_list("SELECT sites_galleries.*,galleries.domainID,galleries.galleryTitle,galleries.active FROM `sites_galleries`
	LEFT JOIN galleries USING (galleryID) WHERE galleries.domainId=".$domainID." and sites_galleries.`siteID`=".$siteID . " order by sites_galleries.showOrder ASC");
if ($siteGalleries)
    foreach($siteGalleries as $gallery) {
    $showGal = false;
    ?>
    <div class="rowWrap" data-id="<?=$gallery['galleryID']?>" id="galRow<?=$gallery['galleryID']?>">
        <!-- <div class="tblCell">**</div> -->
        <div class="tblCell"><?=$gallery['galleryID']?>
            <div class="checkGal">
                <?=$gallery['galleryID']==$siteData['gallerySummer']?'<span class="summer"></span>':''?>
                <?=$gallery['galleryID']==$siteData['galleryWinter']?'<span class="winter"></span>':''?>
            </div>
        </div>
        <div class="tblCell"><?=$gallery['galleryTitle']?></div>
        <div class="tblCell"><span onclick="galleryOpen(<?=$domainID.",".$siteID.",".$gallery['galleryID']?>)"  class="editGalBtn">ערוך גלריה</span>
            <div class="dupGalWrap">
                <select name="galWrapSelect" id="galWrapSelect<?=$gallery['galleryID']?>">
                    <option value="-1">כל הדומיינים</option>
                    <?php foreach(DomainList::get() as $domain) {
                        if($domain['domainID'] ==1 || $domain['domainID'] == $domainID) continue;
                        ?>
                        <option value="<?=$domain['domainID']?>"><?=$domain['domainName']?></option>
                    <?php } ?>
                </select>
                <span class="editGalBtn" onclick="dupGal('',<?=$gallery['galleryID']?>,<?=$siteID?>,<?=$domainID?>,'')">שכפל גלריה</span>
            </div>

        </div>
        <div class="tblCell">
            <?php if(!$showGal) { ?>
                <div class="delBtn" onclick="deleteGallery(<?=$gallery['galleryID']?>)">
                    <i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק
                </div>
            <?php } ?>
        </div>
        <div class="tblCell">
            <?php if(!$showGal) { ?>

                <label class="switch">
                    <input type="checkbox" name="galactive<?=$gallery['galleryID']?>" data-galid="<?=$gallery['galleryID']?>"
                           class="galleryactive" value="1" <?=($gallery['active'] & 1 ? 'checked="checked"' : '')?> />
                    <span class="slider round"></span>
                </label>

            <?php } ?>
        </div>



    </div>
    <?php } ?>