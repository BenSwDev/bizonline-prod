<script src="/user/assets/js/sites_cupons.js?time=<?=time()?>"></script>
<section class="cuponscards">
    <div class="title">ניהול קופוני הנחה</div>
    <div class="health_send">
<?php
/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
$siteID = $_CURRENT_USER->select_site();

/*if (!$_CURRENT_USER->single_site){
    $sname = udb::full_list("SELECT `siteID`, `siteName`,`vvouchers` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
    $guids = udb::key_value("SELECT `siteID`, `guid` FROM `sites` WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")");
?>
            <div class="site-select">
                בחר מתחם
                <select name="sid" id="sid" title="שם מתחם">
                    <option value="0">כל המתחמים</option>
                    <?php
                    foreach($sname as $id => $name) {
                        if(!$name['vvouchers'])continue;
                        echo '<option value="' , $name['siteID'] , '" ' , ($name['siteID'] == $sid ? 'selected' : '') , '>' , $name['siteName'] , '</option>';
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
        }
        else {*/
$guid = udb::single_value("SELECT `guid` FROM `sites` WHERE `siteID` = " . $siteID);

echo '<input type="hidden" name="sid" id="sid" value="'.$siteID.'" />';
echo '<input type="hidden" name="guid" id="guid" value="'.$guid.'" />';
        //}
?>



    </div>
    <div style="clear:both;"></div>
        <div class="add-new" onclick="loadcuponsCardData(0)">הוסף חדש</div>
    <div class="clear"></div>
<?php
    $site_str = $siteID ?: $_CURRENT_USER->sites(true);

    $useSites = udb::key_value("select siteID,siteName from sites where siteID in (".$site_str.")","siteID");
    foreach ($useSites as $sid => $siteName) {
        echo '<div class="cuponscards-list" data-id="'.$sid.'">';
        $sql = "SELECT * FROM `sites_cupons` where deleted=0 and siteID=".$sid." order by id DESC";
        $cuponsCards = udb::full_list($sql);
        $ord = -1;
        //print_r($cuponsCards);
        foreach($cuponsCards as $cuponsCard) {
			//print_r($cuponsCard);
            $ord++
            ?>
            <div class="cuponscard"  data-sid="<?=$cuponsCard['siteID']?>" data-id="<?=$cuponsCard['id']?>" id="cuponscard<?=$cuponsCard['id']?>">
                <div class="active">
                    <div class="inside">
                        <div class="status">פעיל</div>
                        <label class="switch">
                            <input type="checkbox" data-id="<?=$cuponsCard['id']?>" onchange="activeDeActive(<?=$cuponsCard['id']?>,<?=$sid?>)" name="showSpa" value="1" <?=$cuponsCard['active'] == 1 ? ' checked="checked" ' : ''?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
                <div class="r">
                    <div class="inside">
                        <div class="desc"><?=$siteName?></div>
                        <div class="title"><?=$cuponsCard['title']?><?=(intval($cuponsCard['amount'])!=0) ? ' - ' .($cuponsCard['cType']==1? '₪' : '%' ). $cuponsCard['amount']  : '';?></div>
                        <div class="desc"><?=str_replace(PHP_EOL,'<br>',$cuponsCard['cDesc'])?></div>
                    </div>
                </div>
                <div class="l">
                    <div class="inside">
                        <div class="edit" data-id="<?=$cuponsCard['id']?>">
                            <? if(isset($_SESSION['user_id']) || intval($_SESSION['user_id'])) {?><svg style="fill:#0dabb6" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 469.336 469.336" xml:space="preserve" enable-background="new 0 0 469.336 469.336"><g><g><g><path d="M347.878 151.357c-4-4.003-11.083-4.003-15.083 0L129.909 354.414c-2.427 2.429-3.531 5.87-2.99 9.258 0.552 3.388 2.698 6.307 5.76 7.84l16.656 8.34v28.049l-51.031 14.602 -51.51-51.554 14.59-51.075h28.025l8.333 16.67c1.531 3.065 4.448 5.213 7.833 5.765 0.573 0.094 1.146 0.135 1.708 0.135 2.802 0 5.531-1.105 7.542-3.128L317.711 136.26c2-2.002 3.125-4.712 3.125-7.548 0-2.836-1.125-5.546-3.125-7.548l-39.229-39.263c-2-2.002-4.708-3.128-7.542-3.128h-0.021c-2.844 0.01-5.563 1.147-7.552 3.159L45.763 301.682c-0.105 0.107-0.1 0.27-0.201 0.379 -1.095 1.183-2.009 2.549-2.487 4.208l-18.521 64.857L0.409 455.73c-1.063 3.722-0.021 7.736 2.719 10.478 2.031 2.033 4.75 3.128 7.542 3.128 0.979 0 1.958-0.136 2.927-0.407l84.531-24.166 64.802-18.537c0.195-0.056 0.329-0.203 0.52-0.27 0.673-0.232 1.262-0.61 1.881-0.976 0.608-0.361 1.216-0.682 1.73-1.146 0.138-0.122 0.319-0.167 0.452-0.298l219.563-217.789c2.01-1.991 3.146-4.712 3.156-7.558 0.01-2.836-1.115-5.557-3.125-7.569L347.878 151.357z"></path><path d="M456.836 76.168l-64-64.054c-16.125-16.139-44.177-16.17-60.365 0.031l-39.073 39.461c-4.135 4.181-4.125 10.905 0.031 15.065l108.896 108.988c2.083 2.085 4.813 3.128 7.542 3.128 2.719 0 5.427-1.032 7.51-3.096l39.458-39.137c8.063-8.069 12.5-18.787 12.5-30.192S464.899 84.237 456.836 76.168z"></path></g></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg><?}else{?><svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"viewBox="0 0 511.999 511.999" style="enable-background:new 0 0 511.999 511.999;" xml:space="preserve"><path d="M508.745,246.041c-4.574-6.257-113.557-153.206-252.748-153.206S7.818,239.784,3.249,246.035c-4.332,5.936-4.332,13.987,0,19.923c4.569,6.257,113.557,153.206,252.748,153.206s248.174-146.95,252.748-153.201C513.083,260.028,513.083,251.971,508.745,246.041z M255.997,385.406c-102.529,0-191.33-97.533-217.617-129.418c26.253-31.913,114.868-129.395,217.617-129.395c102.524,0,191.319,97.516,217.617,129.418C447.361,287.923,358.746,385.406,255.997,385.406z"/><path d="M255.997,154.725c-55.842,0-101.275,45.433-101.275,101.275s45.433,101.275,101.275,101.275s101.275-45.433,101.275-101.275S311.839,154.725,255.997,154.725z M255.997,323.516c-37.23,0-67.516-30.287-67.516-67.516s30.287-67.516,67.516-67.516s67.516,30.287,67.516,67.516S293.227,323.516,255.997,323.516z"/></svg><?}?></div>
                        <? if(isset($_SESSION['user_id']) || intval($_SESSION['user_id'])) {?>
                            <div class="remove" onclick="delete_cupons(<?=$cuponsCard['id']?>,<?=$sid?>)"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" viewBox="0 0 443 443" xml:space="preserve"><path d="M321.8 38h-83.4V0H125.2v38H41.8v60h280V38zM155.2 30h53.2v8h-53.2V30zM295.1 214.3l5.7-86.3H62.8l19 290h114.2c-14.9-21.1-23.6-46.7-23.6-74.4C172.4 274.4 226.8 217.8 295.1 214.3zM301.8 244.1c-54.8 0-99.4 44.6-99.4 99.4S247 443 301.8 443s99.4-44.6 99.4-99.4S356.6 244.1 301.8 244.1zM356 376.5l-21.2 21.2 -33-33 -33 33 -21.2-21.2 33-33 -33-33 21.2-21.2 33 33 33-33 21.2 21.2 -33 33L356 376.5z"></path></svg></div>
                        <?}?>
                    </div>
                </div>
            </div>
            <?
        }
        echo '</div>';
    }
    ?>
</section>



<div class="cuponspop order" id="cuponspopPop" style="display:none;">
    <div class="container">
        <div class="close" onclick="$('.cuponspop').fadeOut('fast')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21">
                <path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path>
            </svg>
        </div>
        <div class="title mainTitle">
            עריכת קופון הנחה
        </div>
        <form class="form" id="cuponsCardForm" action="" data-guid="" method="post" autocomplete="off" data-defaultagr="1">
            <input type="hidden" name="siteID2" value="<?=$siteID?>">
            <input type="hidden" name="id" id="id" value="0">
            <div class="half">
                <div class="inputWrap">
                    <input type="text" name="title" id="title" value="">
                    <label for="title">כותרת</label>
                </div>
                <div class="inputWrap">
                    <input type="text" name="cCode" id="cCode" value="">
                    <label for="cCode">קוד קופון</label>
                </div>
            </div>
            <div class="half">
                <div class="inputWrap">
                    <select name="cType" id="cType" onchange="if($(this).val()==1){$('#maxSum').hide()}else{$('#maxSum').show()}">
                        <option value="1">סכום ₪</option>
                        <option value="2">אחוזים %</option>
                    </select>
                    <label for="cType">סוג קופון</label>
                </div>
			
                <div class="inputWrap">
                    <input type="text" name="amount" id="amount" value="">
                    <label for="amount">שווי</label>
                </div>
            </div>
            <div class="half">              
			
                <div class="inputWrap">
                    <input type="text" name="expire" id="expire" class="datepicker" value="">
                    <label for="daysValid">תוקף</label>
                </div>
			</div>			
            <div class="half">
				 <div class="inputWrap" id="maxSum">
                    <input type="text" name="maxDiscount" id="maxDiscount" value="">
                    <label for="maxDiscount">סכום מימוש מקסימלי</label>
                </div>

            </div>
            <div class="inputWrap textarea">
                <textarea id="cDesc" name="cDesc"></textarea>
                <label for="cDesc">תאור הקופון לשימוש פנימי</label>
            </div>
            <div class="save">שמור</div>
        </form>
    </div>
</div>	 
<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=cuponscardedit&v=<?=rand()?>" rel="stylesheet">
