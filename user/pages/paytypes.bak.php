<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 03/11/2021
 * Time: 15:52
 */

/**
 * @var TfusaBaseUser $_CURRENT_USER
 */
if (!$_CURRENT_USER->select_site()){
    $_CURRENT_USER->select_site($_CURRENT_USER->active_site());
    echo '<script>$(function(){$(".sites-select select").val(' , $_CURRENT_USER->active_site() , ');});</script>';
}

$siteID = $_CURRENT_USER->active_site() ?: 0;

$title = "אפשרויות תשלום";
UserUtilsNew::init($siteID);


//$_CURRENT_USER->active_site()
$sql = "select * from sitePayTypes where siteID in (".$siteID.")";
$sitePayments = udb::key_row($sql,'paytypekey');

$autoCash = $sitePayments['cash']['invoice'] ?? 2;

$siteCustomPayments = UserUtilsNew::getCustomPayTypes($siteID);// return under siteID key

$canInvoice = udb::single_value("SELECT `masof_invoice` FROM `sites` WHERE `siteID` = " . $siteID);
?>
<style>
    .mainSectionWrapper ul li { height:30px;line-height: 30px;vertical-align: middle;}
    .mainSectionWrapper ul li:hover {background-color:#a2f6ff54}
    .mainSectionWrapper ul li.hasSub {height:auto;}
    .mainSectionWrapper ul li.hasSub ul {
        margin-right: 20px;}
    .mainSectionWrapper ul li input , .mainSectionWrapper ul li label {display: inline-block;line-height: 30px;vertical-align: middle;margin-left: 7px;}
    .mainSectionWrapper ul li.editAble label { color:blue;text-decoration: underline ;cursor: pointer;}
    button.save {
        position: relative;
        display: block;
        width: 120px;
        height: 35px;
        margin-top: 10px;
        background: #0dabb6;
        color: #FFFFFF;
    }
</style>

<div class="mainSectionWrapper">
    <div class="topMenu"><button id="addCpn" class="add-new">קופון תשלום חדש</button></div><div style="clear: both;"></div>

    <h1 class="sectionName" style="text-align: right"><?=$title?> - תשלומי ספק/קופון/שובר</h1>
    <div>
        <button class="save saveList" style="padding: 0">שמור</button>
    </div>
    <div style="text-align: right">
            <ul style="display:none">
            <?
                foreach (UserUtilsNew::$payTypesFull as $k=>$item) {
                    ?>
                    <li><input type="checkbox" class="paytypescheckbox" name="paytypes[]" value="<?=$k?>" checked><label><?=$item?></label></li>
                    <?
                }
            ?>

        </ul>
        <div id="cpnsList">

            <ul >
<?php
    if ($canInvoice) {
?>
                <li style="display:none">
                    <input type="checkbox" id="payCash" class="paytypescheckbox" checked disabled />
                    <label style="width:350px" for="payCash">מזומן</label>
                    <div class="invoiceDiv" style="display:inline-block">
                        <input type="checkbox" id="autoCash" name="autoCash" class="paytypescheckbox" value="2" <?=$autoCash ? ' checked ' : ''?> />
                        <label for="autoCash" style="width:250px">חשבונית מיידית</label>
                    </div>
                </li>
<?php
    }

                foreach (UserUtilsNew::$dbCuponTypes as $k=>$item) {
                    $sql = "select * from payTypes where active=1 and parent=".$item['id'];
                    $theCupons = udb::full_list($sql);

                    ?>
                    <li class="<?=($theCupons || ($siteCustomPayments && $siteCustomPayments[$item['id']])) ? 'hasSub' : '';?>">
                    <input type="checkbox" id="payt_<?=$item['key']?>" class="paytypescheckbox" name="paytypes[]" value="<?=$item['key']?>" <?=$sitePayments[$item['key']] ? ' checked ' : ''?> />
                    <label style="width:350px" for="payt_<?=$item['key']?>"><?=$item['fullname']?></label>
<?php
    if ($canInvoice) {
?>
                    <div class="invoiceDiv" style="display:<?=($sitePayments[$item['key']] ? 'inline-block' : 'none')?>;">
                        <input type="checkbox" id="payIn_<?=$item['key']?>" class="paytypescheckbox" name="allowIn" value="<?=$item['key']?>" <?=$sitePayments[$item['key']]['invoice'] ? ' checked ' : ''?> />
                        <label for="payIn_<?=$item['key']?>" style="width:250px">אפשר להוציא חשבונית</label>
                        <div class="autoInvoiceDiv" style="display:<?=($sitePayments[$item['key']]['invoice'] ? 'inline-block' : 'none')?>">
                            <input type="checkbox" id="payAu_<?=$item['key']?>" class="paytypescheckbox" name="allowAu" value="<?=$item['key']?>" <?=(($sitePayments[$item['key']]['invoice'] & 2) ? ' checked ' : '')?> />
                            <label for="payAu_<?=$item['key']?>">חשבונית מיידית</label>
                        </div>
                    </div>
<?php
    }
                    $sql = "select * from payTypes where active=1 and parent=".$item['id'];
                    $theCupons = udb::full_list($sql);
                    $theCupons = [];
                    if(($siteCustomPayments && $siteCustomPayments[$item['id']]) || $theCupons) {
                        echo '<ul>';
                    }
                    if($theCupons) {
                        foreach ($theCupons as $theCupon) {
                            ?>
                            <li><input type="checkbox" class="paytypescheckbox" name="paytypes[]" value="<?=$theCupon['id']?>" <?=$sitePayments[$theCupon['id']] ? ' checked ' : ''?>><label><?=$theCupon['fullname']?></label>
                            <?
                        }
                    }
                    if($siteCustomPayments && $siteCustomPayments[$item['id']]) {
                        foreach ($siteCustomPayments[$item['id']] as $customCupons) {
                            ?>
                            <li class="editAble" ><input type="checkbox" class="paytypescheckbox" name="paytypes[]" value="<?=$customCupons['id']?>" <?=$sitePayments[$customCupons['id']] ? ' checked ' : ''?>><label onclick="editCupon(<?=$customCupons['id']?>)"><?=$customCupons['fullname']?></label>
                            <?
                        }
                    }
                    if(($siteCustomPayments && $siteCustomPayments[$item['id']]) || $theCupons) {
                        echo '</ul>';
                    }
                    ?>
                    </li>
                    <?
                }
                ?>

            </ul>
        </div>

    </div>
    <div class="cuponpop order" id="cuponpop" style="display: none">
        <div class="container">
            <div class="close" onclick="$('.cuponpop').fadeOut('fast')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21">
                    <path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path>
                </svg>
            </div>
            <div class="title mainTitle">עריכת קופון</div>
            <form class="form" id="payTypeForm" action="" data-guid="" method="post" autocomplete="off" data-defaultagr="1">
                <input type="hidden" name="siteID2" id="siteID2" value="<?=$siteID?>">
                <input type="hidden" name="customPayTypeID" id="customPayTypeID" value="0">
                <div class="inputWrap">
                    <input type="text" name="title" id="title" value="">
                    <label for="title">כותרת</label>
                </div>
                <div class="inputWrap" >
                    <select name="parent" id="parent"><?
                        $dbCuponTypes = UserUtilsNew::$dbCuponTypes;
                        foreach ($dbCuponTypes as $l){
                            echo '<option value="'.$l['id'].'" '.$selected.' >'.$l['fullname'].'</option>';
                        }
                        ?>

                    </select>
                    <label for="amount">סוג הקופון</label>
                </div>
                <div class="inputWrap" >
                    <input type="text" name="couponPayed" id="couponPayed" class="num" value="">
                    <label for="couponPayed">עלות רכישה</label>
                </div>
                <div class="inputWrap" >
                    <input type="text" name="cuponPrice" id="cuponPrice" class="num" value="">
                    <label for="cuponPrice">סכום קופון למימוש</label>
                </div>
				<div class="inputWrap" >
                    <textarea id="cpn_remarks" name="cpn_remarks"></textarea>
                    <label for="cpn_remarks">הערות</label>
                </div>
                <div class="inputWrap" style="display: none;">
                    <input type="checkbox" name="active" id="active"  value="1">
                    <label for="active">פעיל</label>
                </div>
                <div class="save">שמור</div>
            </form>
        </div>
    </div>
</div>
<style>
    .cuponpop{z-index:99;display:block;position:fixed;top:0;right:0;left:0;bottom:0;width:100%;height:100%;background:rgba(0,0,0,.6)}
    .popup .close{position:absolute;top:10px;left:10px;cursor:pointer}
    .popup .title{display:block;text-align:center;font-size:26px;font-weight:500;padding:20px 0}
    .cuponpop .container{position:absolute;top:50%;right:50%;transform:translateY(-50%) translate(50%);width:calc(100% - 10px);max-width:650px;height:100%;max-height:calc(100vh - 10px);background:#f5f5f5;border-radius:8px;overflow:auto}
    .cuponpop .container .close{position:absolute;top:14px;left:14px;cursor:pointer;z-index:2}
    .cuponpop .container .close svg{fill:#aaa;width:17px;height:17px}
    .cuponpop .container>.title{display:block;font-weight:500;color:#333;font-size:30px;text-align:center;padding:12px 0 13px;background:#fff;box-shadow:0 0 10px rgba(0,0,0,.5);z-index:1;position:relative}
    .cuponpop .container>.title .domain-icon{width:40px;height:40px;top:10px;right:10px}
    .cuponpop .container .tabs{display:block;text-align:center;margin:10px 0;font-size:0}
    .cuponpop .container .tabs .tab{display:inline-block;width:100%;max-width:120px;border-radius:7px;color:#0dabb6;height:40px;line-height:40px;margin:0 5px;font-size:16px;cursor:pointer;transition:all .2s ease;background:#fff;filter:drop-shadow(0 0 1.5px rgba(2, 3, 3, .2))}
    .cuponpop .container .tabs .tab.active{background:#2ab5bf;background:-moz-linear-gradient(top,#2ab5bf 0,#3dbcc5 49%,#0dabb6 52%,#0dabb6 100%);background:-webkit-linear-gradient(top,#2ab5bf 0,#3dbcc5 49%,#0dabb6 52%,#0dabb6 100%);background:linear-gradient(to bottom,#2ab5bf 0,#3dbcc5 49%,#0dabb6 52%,#0dabb6 100%);color:#fff}
    .cuponpop .form{display:block;padding:20px;box-sizing:border-box;font-size:0;overflow:auto;position:absolute;left:0;right:0;top:60px;bottom:0;height:auto}
    .signature .cuponpop .form{top:45px}
    .signature a{color:inherit}
    .cuponpop .inputWrap svg{position:absolute;top:50%;left:10px;transform:translateY(-50%);fill:#0dabb6}
    .cuponpop .inputWrap{border-radius:3px;font-size:14px;filter:drop-shadow(0 1px 1px rgba(2, 3, 3, .1));position:relative;height:auto;min-height:60px;background-color:#fff;border:1px solid #eee;display:inline-block;width:100%;max-width:98%;margin:0 1% 10px 1%;box-sizing:border-box}
    .cuponpop .inputWrap.date.four{max-width:58%}
    .cuponpop .inputWrap.date.time.four{max-width:38%}
    .cuponpop .inputWrap>label{position:absolute;top:3px;transform:none;right:5px;font-size:14px;color:#0dabb6;font-weight:500;line-height:1;transition:all .2s ease}
    .inputWrap> input[type="file"] + label {padding: 10px;border: 1px #0dabb6 solid;padding-left: 40px;border-radius: 20px;background-color: rgba(240,240,240,0.8);background-image: url(/user/assets/img/upload.svg);background-size: 16px;background-repeat: no-repeat;background-position: left 10px center;cursor:pointer}
    .cuponpop .inputWrap.signature>label{font-size:20px}
    .cuponpop .inputWrap>input.empty:not(:focus)+label{font-size:20px;font-weight:400;top:50%;transform:translateY(-50%);padding-right:10px;opacity:.5}
    .cuponpop .inputWrap>input{font-size:20px;position:absolute;top:0;right:0;left:0;bottom:0;width:100%;height:100%;background:0 0;padding:0 10px;box-sizing:border-box;z-index:2;color:#333}
    .cuponpop .inputWrap>textarea{color:#000;font-size:20px;width:100%;height:100%;background:0 0;padding:20px 10px 10px;box-sizing:border-box;-webkit-transform:translateZ(0);-webkit-overflow-scrolling:touch}
    .cuponpop .inputWrap.submit{background:#e73219;color:#fff;text-align:center;font-size:30px;font-weight:500;cursor:pointer;border-radius:3px}
    .cuponpop .cancelOrderBtn{box-sizing:border-box;width:100%;max-width:98%;margin:0 1% 10px 1%;background:#c03;color:#fff;text-align:center;font-size:30px;font-weight:500;cursor:pointer;border:1px solid #eee;border-radius:3px;line-height:60px}
    .cuponpop .delOrderBtn{display:none;box-sizing:border-box;width:100%;max-width:98%;margin:0 1% 10px 1%;background:#c03;color:#fff;text-align:center;font-size:30px;font-weight:500;cursor:pointer;border:1px solid #eee;border-radius:3px;line-height:60px}
    .cuponpop .signBtn{display:none;box-sizing:border-box;width:100%;max-width:48%;margin:0 1% 40px 1%;background:#03f;color:#fff;text-align:center;font-size:30px;font-weight:500;cursor:pointer;border:1px solid #eee;border-radius:3px;line-height:60px}
    .cuponpop .signBtn.show{display:block}
    .cuponpop .inputWrap>select{position:absolute;top:0;right:0;left:0;bottom:0;width:100%;height:100%;background:0 0;font-size:20px;color:#333;padding:0 10px;box-sizing:border-box}
    .cuponpop .inputWrap:not(.date)>input{color:#333}
    .cuponpop .inputWrap:not(.date)>input::-webkit-input-placeholder{color:#0dabb6}
    .cuponpop .inputWrap:not(.date) input:read-only{background:rgba(13 ,171 ,182,.2);cursor:initial}
    .cuponpop .inputWrap.textarea>textarea::-webkit-input-placeholder{color:#0dabb6}
    .cuponpop .inputWrap.textarea{height:180px}
    .cuponpop .inputWrap.textarea img{max-height:100%}
    .cuponpop .inputWrap .short-desc{font-size:16px;padding:20px 5px;display:block;box-sizing:border-box}
    .cuponpop .statusBtn.del .cancelOrderBtn{display:none}
    .cuponpop .statusBtn.del .delOrderBtn{display:block}
    .cuponpop .rooms .room{cursor:pointer;border-radius:3px;font-size:14px;filter:drop-shadow(0 1px 1px rgba(2, 3, 3, .1));position:relative;height:auto;min-height:60px;background-color:#fff;border:1px solid #eee;display:inline-block;width:100%;max-width:98%;margin:0 1% 10px 1%;box-sizing:border-box}
    .cuponpop .rooms .room .title{float:right;display:inline-block;color:#777;font-size:20px;line-height:58px;position:relative;padding-right:50px}
    .signature .cuponpop .rooms .room .title{padding-right:10px}
    .signature .rooms select{text-align-last:center}
    .signature .cuponpop .inputWrap input{background:0 0!important}
    .cuponpop .rooms input:checked+.room{border:1px solid #0dabb6}
    .cuponpop .rooms input:checked+.room .title{color:#0dabb6}
    .cuponpop .rooms input:not(:checked)+.room .l::after{content:"";position:absolute;top:0;bottom:0;left:0;right:0;background:rgba(255,255,255,.7)}
    .cuponpop .rooms .room .title::before{content:'';position:absolute;top:50%;right:10px;width:30px;height:30px;box-sizing:border-box;border:1px solid #d0d0d0;border-radius:30px;transform:translateY(-50%);transition:all .2s ease}
    .cuponpop .rooms .room .title::after{content:'';position:absolute;top:50%;right:13px;transform:translateY(-50%);width:24px;height:24px;border-radius:25px;background:#0dabb6;opacity:0;transition:all .2s ease}
    .cuponpop .rooms input[type=radio]{display:none}
    .cuponpop .rooms input[type=checkbox]{display:none}
    .cuponpop .rooms .room .l{display:block;text-align:center;position:relative;margin-bottom:10px;clear:both}
    .cuponpop .rooms .room .l .payments{border-top:1px #ccc solid;margin-top:10px;margin-bottom:10px}
    .cuponpop .rooms .room .l .payments .meals{height:40px;border-bottom:1px #ccc solid;margin-bottom:10px;padding:5px 0}
    .cuponpop .rooms .room .l .payments .meals select{width:calc(100% - 20px);height:40px;font-size:16px}
    .cuponpop .rooms .room .l .payments .dataInp{width:calc(100% - 20px);margin:0 5px}
    .cuponpop .rooms .room .l .dataInp input{width:100%;border:1px #ccc solid;margin-top:-18px;height:50px;background:0 0;text-align:center;font-size:20px;padding-top:10px;box-sizing:border-box}
    .cuponpop .rooms input:not(:checked)+.room .l{display:none}
    .cuponpop .rooms .room .l .dataInp{display:inline-block;width:45px;text-align:center;margin-right:20px;position:relative}
    .cuponpop .rooms .room .l .dataInp.adults::before,.cuponpop .rooms .room .l .dataInp.babies::before,.cuponpop .rooms .room .l .dataInp.kids::before{content:'';position:absolute;top:50%;left:0;border-left:2px solid #000;border-bottom:2px solid #000;width:5px;height:5px;transform:rotate(-45deg)}
    .cuponpop .rooms .room .l .dataInp label{font-size:14px;color:#0dabb6;display:block;margin:0 -10px;text-align:center}
    .cuponpop .rooms .room .l .dataInp select{height:30px;width:100%;font-size:20px;appearance:none;-webkit-appearance:none}
    .cuponpop .rooms input:not(:checked)+.room .l .payments{display:none}
    .cuponpop .rooms input:checked+.room .title::before{border-color:#14adb8}
    .cuponpop .rooms input:checked+.room .title::after{opacity:1}
    .cuponpop .text-wrapper{font-size:18px;margin:30px 0}
    .cuponpop .text-wrapper .question{margin:30px 20px;position:relative}
    .cuponpop .text-wrapper .question::before{content:"";width:10px;height:10px;background:#0dabb6;display:block;position:absolute;margin-right:-18px;margin-top:6px;border-radius:50%}
    .cuponpop .text-wrapper .question input[type=checkbox]{height:30px;width:30px;margin-top:-4px;margin-left:5px;position:absolute}
    .cuponpop .text-wrapper .question input[type=checkbox]+span{padding-right:40px;display:inline-block}
    .cuponpop .text-wrapper .question select{clear:both;display:block;border:1px #000 solid;padding:8px 10px;margin-top:6px;-webkit-appearance:auto;background:#d4f6f9}
    .cuponpop .text-wrapper .question.checked::after{content:"";position:absolute;bottom:11px;right:-16px;width:4px;height:8px;border-right:3px #0dabb6 solid;border-bottom:3px #0dabb6 solid;transform:rotate(45deg)}
    .cuponpop .text-wrapper .question .extra{display:none}
    .cuponpop .text-wrapper .question.open .extra{display:block}
    .cuponpop .text-wrapper .question .extra input{width:300px;max-width:calc(100% - 20px);border-bottom:1px #ccc solid;margin:0 10px;font-size:18px}
    .cuponpop .text-wrapper .question .extra span{display:inline-block}
    .cuponpop .text-wrapper .question .extra div{margin:10px 0}
    .signature .cuponpop .inputWrap.signature{margin-bottom:70px;padding-top:40px}
    .signature .cuponpop .inputWrap.signature .btnWrap{text-align:center;margin-bottom:20px}
    .signature .cuponpop .inputWrap.signature .waze{background-image:url(../img/waze.png)}
    .signature .cuponpop .inputWrap.signature .addToCal{background-image:url(../img/dl.png)}
    .signature .cuponpop .inputWrap.signature .print{background-image:url(../img/printer-4-48.png);background-size:70%!important}
    .signature .cuponpop .inputWrap.signature .google{background-image:url(../img/google.png)}
    .signature .cuponpop .inputWrap.signature .signBtn{position:relative;width:80px;height:80px;border-radius:100px;background-color:#0dabb6;display:inline-block;color:#555;background-size:contain;background-repeat:no-repeat;background-position:center center}
    .signature .cuponpop .inputWrap.signature .signBtn span{position:absolute;bottom:-40px;left:0;right:0;font-size:18px;font-weight:400}
    .page-signature .cuponpop .inputWrap.signature{height:180px}
    .pay_order{z-index:99;display:block;position:fixed;top:0;right:0;left:0;bottom:0;width:100%;height:100%;background:rgba(0,0,0,.6)}
    .pay_order .inputLblWrap .switch input:checked+.slider span{display:block;padding-right:0}
    .pay_order .inputLblWrap .switch input+.slider span{display:block;font-size:16px;color:#fff;font-weight:500;line-height:34px;padding-right:10px}
    .pay_order .inputLblWrap .switch input:checked+.slider:after{content:'';position:absolute;top:44%;right:0;width:10px;height:2px;border-left:2px solid #0dabb6;border-bottom:2px solid #0dabb6;transform:rotate(-45deg);right:11px}
    .last-orders .items .order.allpaid ul li.send>div .orderPrice.new{border-color:#0dabb6;background:#f5fcfc}
    .last-orders .items .order.allpaid ul li.send>div .orderPrice.new svg{fill:#0dabb6}
    .last-orders .items .order.allpaid ul li.send>div .orderPrice.new>span>span{color:#0dabb6}
    section.giftcards{text-align:center;box-sizing:border-box}
    section.giftcards>.title{display:block;font-size:24px}
    .top-btns{display:block;text-align:center}
    .giftcard .inside{height:120px;vertical-align:middle;display:table-cell;width:100%}
    .giftcard .r{display:inline-block;width:100%;max-width:calc(100% - 300px);font-size:16px}
    .giftcard .r .title{font-weight:800;color:#0dabb6}
    .giftcard .l{text-align:center;display:inline-block;width:100%;max-width:150px}
    .giftcard .active .inside{width:150px}
    .page-options{float:left}
    .clear{clear:both}
    .add-new,.page-options,.save,.top-btns>div{background:#0dabb6;font-size:16px;display:inline-block;padding:15px 30px;box-sizing:border-box;color:#fff;cursor:pointer;border-radius:10px;margin:0 5px}
    .edit svg{fill:#0dabb6}
    .remove svg{fill:#a1aaad}
    .add-new{display:block;float:right;max-width:150px}
    .giftcards-list{clear:both;margin:20px 0}
    .giftcards-list>.giftcard{position:relative;font-size:0;background:#fff;text-align:Right;min-height:120px;border-radius:10px;border:1px solid #ccc;box-sizing:border-box;box-shadow:-1px 0 5px rgba(2,3,3,.1);margin-top:20px}
    .slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#ccc;-webkit-transition:.4s;transition:.4s}
    .slider:before{position:absolute;content:"";height:26px;width:26px;left:4px;bottom:4px;background-color:#fff;-webkit-transition:.4s;transition:.4s}
    input:checked+.slider{background-color:#0dabb6}
    input:focus+.slider{box-shadow:0 0 1px #0dabb6}
    .giftcard .active{width:100%;display:inline-block;max-width:150px;text-align:center;font-size:16px;font-weight:800;color:#0dabb6}
    .giftcard input[type=checkbox]{display:none}
    input:checked+.slider:before{-webkit-transform:translateX(26px);-ms-transform:translateX(26px);transform:translateX(26px)}
    .switch{position:relative;display:inline-block;width:60px;height:34px}
    .slider.round{border-radius:34px}
    .slider.round:before{border-radius:50%}
    .giftcard .l svg{width:40px;height:Auto}
    .giftcard .l .inside>div{display:inline-block;vertical-align:middle;cursor:pointer}
    .giftcard .l .inside{width:150px;padding-left:20px;box-sizing:border-box}
    .giftcard .ordercards {position: absolute;
        right: 0;
        display: block;
        left: auto;
        top: 40px;
        width: 50px;
        height: 80px;
    }

    .giftcard .ordercards a.ord {
        position: absolute;
        display: block;
        top: 10px;
        right: 10px;
        font-size: 20px;
        width: 25px;
        height: 25px;
        cursor: pointer;
    }


    a.ord.up {
        transform: rotate(90deg);
        margin-top:-50%
    }
    .giftcard:first-of-type .ordercards a.ord.up {display:none}

    a.ord.down {
        transform: rotate(-90deg);
        top: auto;
        bottom: 0;
        margin-top:50%
    }
    .giftcard:last-of-type .ordercards a.ord.down {display:none}
    @media (min-width:1000px){
        .global_edit{width:calc(100% - 300px);right:300px}
        .global_edit .inputWrap{height:60px}
        .global_edit .rooms .room{min-height:60px}
        .global_edit .inputWrap.half{max-width:48%;margin:0 1% 10px 1%}
        .global_edit .inputWrap.four{max-width:23%}
        .global_edit .inputWrap.three{max-width:31.33%}
        .global_edit .inputWrap.date.four{max-width:28%}
        .global_edit .inputWrap.date.time.four{max-width:18%}

        .cuponpop form>.half {
            display: inline-block;
            width: 100%;
            max-width: 48%;
            margin: 0 1% 10px 1%;
        }

        .cuponpop{width:calc(100% - 300px);right:300px}
        .cuponpop .inputWrap{height:60px}
        .cuponpop .rooms .room{min-height:60px}
        .cuponpop .inputWrap.half{max-width:48%;margin:0 1% 10px 1%}
        .cuponpop .inputWrap.four{max-width:23%}
        .cuponpop .inputWrap.three{max-width:31.33%}
        .cuponpop .inputWrap.date.four{max-width:28%}
        .cuponpop .inputWrap.date.time.four{max-width:18%}

        .cuponpop label.switch {
            top: 20px;
        }

    }
</style>
<script>
    $(document).ready(function () {
        $(".paytypescheckbox").on("change", function(){
            $(this).siblings('.invoiceDiv, .autoInvoiceDiv').css('display', this.checked ? 'inline-block' : 'none');

            if($(this).val() == 'coupon' ) {
                if($(this).is(":checked")) {
                    $("#cpnsList").show();
                }
                else {
                    $("#cpnsList").hide();
                }
            }

        });

        $(".saveList").on("click",function(){
            var data = { ptypes: [], allowIn:[], allowAu:[] };

            $('.paytypescheckbox:checkbox:checked').each(function(){
                switch(this.name){
                    case 'allowIn': data.allowIn.push(this.value); break;
                    case 'allowAu': data.allowAu.push(this.value); break;
                    case 'autoCash': data.autoCash = 2; break;
                    default: data.ptypes.push(this.value);
                }
            });
            $.ajax({
                method: 'post',
                url: 'ajax_paytypes.php',
                data: data,
                success: function(res){
                    return Swal.fire((!res || res.error) ? {icon:"error", title:"שגיאה!", text:(res ? (res.error||res._txt) : '') ||'Unknown error' } : {icon: 'success' , title: 'רשימה עודכנה'});
                }
            });
        });
        $("#addCpn").on("click",function () {
            $("#cuponpop").show();
            $("#payTypeForm")[0].reset();
            $("#siteID2").val(<?=$siteID?>);
            $("#customPayTypeID").val('0');
        });



        $("#payTypeForm .save").on("click",function(){
            var formData = {};
            formData.cuponPrice = $("#cuponPrice").val();
            formData.couponPayed = $("#couponPayed").val();
            formData.parent = $("#parent").val();
            formData.title = $("#title").val();
            formData.cpn_remarks = $("#cpn_remarks").val();
            formData.active = $("#active").val();
            formData.customPayTypeID = $("#customPayTypeID").val();
            formData.siteID2 = $("#siteID2").val();
            if(formData.title  == '' ) {
                return Swal.fire({type: 'error' , title: 'נא למלא שם קופון'});
            }
            $.ajax({
                method: 'POST',
                url: 'ajax_customPayType.php',
                data: formData,
                cache : false,
                success: function (res) {
                    try {
                        var response  = JSON.parse(res);
                    } catch (e) {
                        var response  = res;
                    }
                    if(response.status != 1) {
                        return Swal.fire({type: 'error' , title: response.message});
                    }
                    return Swal.fire({type: 'success' , title: 'פעולה הסתיימה בהצלחה'}).then(function (res) {
                        $("#cuponpop .close").trigger("click");
                        window.location.reload();
                    });

                }
            });

        });
    });

    function editCupon(cid){
        $("#customPayTypeID").val(cid);
        $.ajax({
            method: 'POST',
            url: 'ajax_customPayType.php',
            data: {cpn: cid ,act: 'load' , siteID2: $("#siteID2").val()},
            cache : false,
            success: function (res) {
                $("#cuponpop").show();
                try {
                    var response  = JSON.parse(res);
                } catch (e) {
                    var response  = res;
                }
				//debugger;
                $("#cuponPrice").val(response.data.cuponPrice);
                $("#couponPayed").val(response.data.couponPayed);
                $("#parent").val(response.data.parent);
                $("#title").val(response.data.fullname);
                $("#cpn_remarks").html(response.data.cpn_remarks);
                if(response.data.active = 1) {
                    $("#active").attr("checked",true);
                }
                else {
                    $("#active").attr("checked",false);
                }

                // return Swal.fire({type: 'success' , title: 'פעולה הסתיימה בהצלחה'}).then(function (res) {
                //     $("#cuponpop .close").trigger("click");
                // });
            }
        });

    }
</script>