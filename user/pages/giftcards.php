<script src="/user/assets/js/giftcards.js"></script>
<section class="giftcards">
    <div class="title">ניהול גיפטקארד</div>

<div class="health_send">
<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$siteID = $_CURRENT_USER->active_site() ?: 0;

/*if (!$_CURRENT_USER->single_site){
    $sname = udb::full_list("SELECT `siteID`, `siteName`,`vvouchers` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
    $guids = udb::key_value("SELECT `siteID`, `guid` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
<div class="site-select">
		בחר מתחם
            <select name="asid" title="שם מתחם">
                <option value="0">כל המתחמים</option>
                <?php
                foreach($sname as $id => $name) {
                    if(!$name['vvouchers'])continue;
                    echo '<option value="' , $name['siteID'] , '" ' , ($name['siteID'] == $siteID ? 'selected' : '') , '>' , $name['siteName'] , '</option>';
                }
                ?>
            </select>
        <?php
        foreach($guids as $id => $guid) {
            echo '<input type="hidden" name="guid'.$id.'" id="guid'.$id.'" value="'.$guid.'">';
        }
        echo '<input type="hidden" name="guid" id="guid" value="0">';

    ?>
</div>
<?php
}*/

$guid = udb::single_value("SELECT `guid` FROM `sites` WHERE `siteID` = " . $siteID);

echo '<input type="hidden" name="sid" id="sid" value="'.$siteID.'">';
echo '<input type="hidden" name="guid" id="guid" value="'.$guid.'">';
?>
    <div class="top-btns find">
        <div class="send_btn plusWrapper global-edit" >שליחה ללקוח<div class="plusSend"  data-title="שליחת גיפט קארד" data-msg=" http://www.vouchers.co.il/g.php?guid=<?=$guid?>" data-subject="גיפט קארד"></div></div>
        <a class="link send_btn" href="http://www.vouchers.co.il/g.php?guid=<?=$guid?>" target="_blank" >קישור למסך</a>
    </div>
</div>

    <div style="clear:both;"></div>

    <div class="add-new" onclick="loadGiftCardData(0)">הוסף חדש</div>	
    <div class="page-options" onclick="loadGeneralForm()">הגדרות תצוגת עמוד</div>
	
    <div class="clear"></div>
<?php
        $useSites = udb::key_row("select siteID,siteName from sites where siteID in (".$_CURRENT_USER->sites(true).")","siteID");
//        $arrSites = $_CURRENT_USER->sites(false);
//        foreach ($arrSites as $siteID) {
            echo '<div class="giftcards-list" data-id="'.$siteID.'">';
            $sql = "SELECT * FROM `giftCards` where deleted=0 and siteID=".$siteID." order by giftType DESC, showOrder";
            $giftCards = udb::full_list($sql);
            $ord = -1;
			//print_r($giftCards);
            foreach($giftCards as $giftCard) {
                $ord++
                ?>
                <div class="giftcard" data-ord="<?=$giftCard['showOrder'] + $ord;?>" data-sid="<?=$giftCard['siteID']?>" data-id="<?=$giftCard['giftCardID']?>" id="giftcard<?=$giftCard['giftCardID']?>">
                    <div class="active">
                        <div class="inside">
                            <div class="status">פעיל</div>
                            <label class="switch">
                            <input type="checkbox" data-id="<?=$giftCard['giftCardID']?>" onchange="activeDeActive(<?=$giftCard['giftCardID']?>)" name="showSpa" value="1" <?=$giftCard['active'] == 1 ? ' checked="checked" ' : ''?>>
                            <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="r">
                        <div class="inside">
                            <div class="desc"><?=$useSites[$giftCard['siteID']]['siteName']?></div>
                            <div class="title"><?=$giftCard['title']?><?=(intval($giftCard['sum'])!=0) ? ' - ' . '₪' . $giftCard['sum']  : '';?></div>
                            <div class="desc"><?=str_replace(PHP_EOL,'<br>',$giftCard['description'])?></div>
                        </div>
                    </div>
                    <div class="l">
                        <div class="inside">
                            <div class="edit" data-id="<?=$giftCard['giftCardID']?>">							
								<svg style="fill:#0dabb6" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 469.336 469.336" xml:space="preserve" enable-background="new 0 0 469.336 469.336"><g><g><g><path d="M347.878 151.357c-4-4.003-11.083-4.003-15.083 0L129.909 354.414c-2.427 2.429-3.531 5.87-2.99 9.258 0.552 3.388 2.698 6.307 5.76 7.84l16.656 8.34v28.049l-51.031 14.602 -51.51-51.554 14.59-51.075h28.025l8.333 16.67c1.531 3.065 4.448 5.213 7.833 5.765 0.573 0.094 1.146 0.135 1.708 0.135 2.802 0 5.531-1.105 7.542-3.128L317.711 136.26c2-2.002 3.125-4.712 3.125-7.548 0-2.836-1.125-5.546-3.125-7.548l-39.229-39.263c-2-2.002-4.708-3.128-7.542-3.128h-0.021c-2.844 0.01-5.563 1.147-7.552 3.159L45.763 301.682c-0.105 0.107-0.1 0.27-0.201 0.379 -1.095 1.183-2.009 2.549-2.487 4.208l-18.521 64.857L0.409 455.73c-1.063 3.722-0.021 7.736 2.719 10.478 2.031 2.033 4.75 3.128 7.542 3.128 0.979 0 1.958-0.136 2.927-0.407l84.531-24.166 64.802-18.537c0.195-0.056 0.329-0.203 0.52-0.27 0.673-0.232 1.262-0.61 1.881-0.976 0.608-0.361 1.216-0.682 1.73-1.146 0.138-0.122 0.319-0.167 0.452-0.298l219.563-217.789c2.01-1.991 3.146-4.712 3.156-7.558 0.01-2.836-1.115-5.557-3.125-7.569L347.878 151.357z"></path><path d="M456.836 76.168l-64-64.054c-16.125-16.139-44.177-16.17-60.365 0.031l-39.073 39.461c-4.135 4.181-4.125 10.905 0.031 15.065l108.896 108.988c2.083 2.085 4.813 3.128 7.542 3.128 2.719 0 5.427-1.032 7.51-3.096l39.458-39.137c8.063-8.069 12.5-18.787 12.5-30.192S464.899 84.237 456.836 76.168z"></path></g></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>
							</div>
                            <? if(isset($_SESSION['user_id']) || intval($_SESSION['user_id'])) {?>
                                <div class="remove" onclick="delete_gift(<?=$giftCard['giftCardID']?>)"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" viewBox="0 0 443 443" xml:space="preserve"><path d="M321.8 38h-83.4V0H125.2v38H41.8v60h280V38zM155.2 30h53.2v8h-53.2V30zM295.1 214.3l5.7-86.3H62.8l19 290h114.2c-14.9-21.1-23.6-46.7-23.6-74.4C172.4 274.4 226.8 217.8 295.1 214.3zM301.8 244.1c-54.8 0-99.4 44.6-99.4 99.4S247 443 301.8 443s99.4-44.6 99.4-99.4S356.6 244.1 301.8 244.1zM356 376.5l-21.2 21.2 -33-33 -33 33 -21.2-21.2 33-33 -33-33 21.2-21.2 33 33 33-33 21.2 21.2 -33 33L356 376.5z"></path></svg></div>
                            <?}?>
                        </div>
                    </div>
                </div>
                <?
            }
            echo '</div>';
//        }
    ?>
</section>



<div class="giftpop order" id="giftpopPop" style="display:none;">
   <div class="container">
      <div class="close" onclick="$('.giftpop').fadeOut('fast')">
         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21">
            <path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path>
         </svg>
      </div>
      <div class="title mainTitle">
         עריכת גיפטקארד
      </div>
      <form class="form" id="giftCardForm" action="" data-guid="" method="post" autocomplete="off" data-defaultagr="1">
         <input type="hidden" name="siteID2" value="<?=$siteID?>">
         <input type="hidden" name="giftCardID" id="giftCardID" value="0">
         <div class="inputWrap">
            <input type="text" name="title" id="title" value="">
            <label for="title">כותרת</label>
         </div>
         <div class="half">
            <div class="inputWrap">
                <input type="text" name="amount" id="amount" class="num" value="">
                <label for="amount">סכום</label>
            </div>
             <div class="inputWrap">
                 <label for="title">הצג מחיר</label>
                 <label class="switch" style="top:20px">
                     <input type="checkbox"  name="showPrice" id="showPrice" value="1" >
                     <span class="slider round"></span>
                 </label>
             </div>
            <div class="inputWrap">
                <input type="text" name="daysValid" id="daysValid" class="num" value="">
                <label for="daysValid">תוקף בחודשים בין 3 ל 24</label>
            </div>
        </div>
        <div class="half">
            <div class="inputWrap textarea">
                <img src="" id="picpic-img" style="display: none">
                <input type="file" name="picpic" id="picpic" style="display: none">
                <label for="picpic">תמונה</label>
            </div>
        </div>
        <div class="inputWrap textarea">
            <textarea id="desc" name="desc"></textarea>
            <label for="desc">תאור החבילה</label>
        </div>
          <div class="inputWrap textarea">
              <textarea id="restrictions" name="restrictions"></textarea>
              <label for="restrictions">הגבלות</label>
          </div>

          <?//if(isset($_SESSION['user_id']) || intval($_SESSION['user_id'])) {
                echo '<div class="save">שמור</div>';
          //}?>

      </form>
   </div>
</div>

<?
    //tbl: giftCardsSetting
    //@@fields: giftCardsSettingID, title, backgroundImage, logo, siteDescription, smallLetters, siteID, addManager, updateManager, addDate, updateDate
    $globalGiftData = [];
    $globalGiftSitesData = [];
    $sql = "select * from giftCardsSetting where siteID in (".$_CURRENT_USER->sites(true).")";
    $globalGiftSitesData = udb::key_row($sql,"siteID");
    if(intval($_GET['siteID2'])) {
        if($globalGiftSitesData[intval($_GET['siteID2'])]) {
            $globalGiftData = $globalGiftSitesData[intval($_GET['siteID2'])];
        }
    }
    if(!$globalGiftSitesData) {
        $globalGiftData['title'] = $siteName;
    }
    else {
        foreach ($globalGiftData as $item) {
            $globalGiftData = $item;
            break;
        }
    }
    //$disabled = array(' disabled="disabled" ','style="display:none"' ,' readonly ');
    //if(isset($_SESSION['user_id']) || intval($_SESSION['user_id'])) {
        $disabled[0] = "";
        $disabled[1] = "";
        $disabled[2] = "";
   // }
?>
<div class="global_edit order" id="global_editPop" style="display:none;">
   <div class="container">
      <div class="close" onclick="$('.global_edit').fadeOut('fast')">
         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21">
            <path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path>
         </svg>
      </div>
      <div class="title mainTitle">
         <div class="domain-icon" style="background-image:url(/user/assets/domains/biz.jpg)"></div>
         הגדרות תצוגת עמוד
      </div>
      <form class="form" id="globaloptionsForm" action="" data-guid="" method="post" autocomplete="off" data-defaultagr="1">
          <input type="hidden" name="id" value="<?=$globalGiftData['giftCardsSettingID'] ?? '0'?>">
          <input type="hidden" name="siteID2" value="<?=$globalGiftData['siteID'] ?? '0'?>">
         <div class="inputWrap">
            <input type="text" name="title" id="title" value="<?=$globalGiftData['title']?>" <?=$disabled[0].$disabled[2]?> >
            <label for="title">כותרת</label>
         </div>
         <div class="inputWrap half textarea img" >
            <img src="/gallery/<?=$globalGiftData['logo']?>"  style="<?=$globalGiftData['logo'] ? '' : 'display:none'?>" id="logo-img">
            <input type="file" name="logo" id="logo" style="display:none" <?=$disabled[0].$disabled[2]?>>
            <label for="logo" >לוגו</label>
        </div>
        <div class="inputWrap half textarea img"  >
            <img src="/gallery/<?=$globalGiftData['backgroundImage']?>"  style="<?=$globalGiftData['backgroundImage'] ? '' : 'display:none'?>" id="bgimg-img">
            <input type="file" name="bgimg" id="bgimg" style="display:none" <?=$disabled[0].$disabled[2]?>>
            <label for="bgimg" >תמונת רקע</label>
        </div>
        <div class="inputWrap textarea">
            <textarea id="toptext" name="toptext" <?=$disabled[0].$disabled[2]?>><?=$globalGiftData['toptext']?></textarea>
            <label for="desc">טקסט עליון</label>
        </div>
        <div class="inputWrap textarea">
            <textarea id="desc" name="desc" <?=$disabled[0].$disabled[2]?>><?=$globalGiftData['siteDescription']?></textarea>
            <label for="desc">תאור העסק</label>
        </div>
        <div class="inputWrap textarea">
            <textarea id="small_letters" name="small_letters" <?=$disabled[0].$disabled[2]?>><?=$globalGiftData['smallLetters']?></textarea>
            <label for="small_letters">אותיות קטנות</label>
        </div>
          <div class="inputWrap textarea">
              <textarea id="meta_desc" name="meta_desc" <?=$disabled[0].$disabled[2]?>><?=$globalGiftData['meta_desc']?></textarea>
              <label for="meta_desc">תאור מטה</label>
          </div>
        <?if($disabled[1] == '') {?><div class="save" <?=$disabled[1]?>>שמור</div><?}?>
      </form>
   </div>
</div>
<link rel="stylesheet" href="/user/assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=giftcardedit&v=<?=time()?>" />
