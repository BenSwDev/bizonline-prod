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

</style>
<link href="assets/css/style_ctrl.php?dir=<?=$dir?>&fileName=paytypes&v=<?=rand()?>" rel="stylesheet">
<div class="mainSectionWrapper">
    <div><button id="addCpn" class="add-new">קופון תשלום חדש</button></div><div style="clear: both;"></div>

    <h1 class="sectionName" style=""><?=$title?> - תשלומי ספק/קופון/שובר</h1>
    <div>
        <button class="save saveList" style="padding: 0">שמור</button>
    </div>
    <div style="">
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


    <h2 class="sectionName" style=" margin-top: 50px">ספקי אורחי מלון</h2>
    <div><button id="addHts" class="add-new">הוסף ספק</button></div><div style="clear: both;"></div>
    <div style=" margin-top: 20px">
        <div id="htsList">
            <ul >
<?php
        $list = (new SiteItemList($siteID, 'hotel_guest_supplier'))->get_full_list(false);
        asort($list, SORT_STRING);

        foreach ($list as $id => $item) {
?>
                <li class="editAble hts" data-id="<?=$id?>">
                    <input type="checkbox" id="hts<?=$id?>" class="hts-checkbox" <?=($item['_deleted'] ? '' : 'checked')?> />
                    <label style="width:375px" for="hts<?=$id?>"><?=$item['itemName']?></label>
                </li>
<?php
                }
?>
            </ul>
        </div>
    </div>


    <div class="cuponpop order" id="cuponpop" style="display: none">
        <div class="container">
            <div class="close" onclick="$('#cuponpop').fadeOut('fast')">
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

    <div class="cuponpop order" id="newHTS" style="display: none">
        <div class="container">
            <div class="close" onclick="$('#newHTS').fadeOut('fast')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21">
                    <path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path>
                </svg>
            </div>
            <div class="title mainTitle">עריכת קופון</div>
            <form class="form" id="newHtsForm" action="" data-guid="" method="post" autocomplete="off" data-defaultagr="1">
                <input type="hidden" name="siteID2" id="siteID3" value="<?=$siteID?>" />
                <input type="hidden" name="itemID" id="itemID" value="0" />
                <div class="inputWrap">
                    <input type="text" name="title" id="titleHts" value="" />
                    <label for="titleHts">שם ספק</label>
                </div>
                <div class="save">שמור</div>
            </form>
        </div>
    </div>
</div>
<style>

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
            var data = { act:'allow', ptypes: [], allowIn:[], allowAu:[] };

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

        $("#addCpn").on("click",function() {
            $("#cuponpop").show();
            $("#payTypeForm")[0].reset();
            $("#siteID2").val(<?=$siteID?>);
            $("#customPayTypeID").val('0');
        });

        $("#addHts").on("click", function() {
            $("#newHtsForm")[0].reset();
            $("#siteID3").val(<?=$siteID?>);
            $("#itemID").val(0);
            $("#newHTS").show().find('.mainTitle').text('ספק חדש');
        });

        $('#htsList').on('click', 'input.hts-checkbox', function(){
            let id = $(this).parent().data('id'), self = this;
            $.post('ajax_paytypes.php', 'act=toggleHts&id=' + id).then(function(res){
                if (!res || res.status === undefined || parseInt(res.status))
                    return Swal.fire({icon: 'error' , title: (res ? (res.error || res._txt) : '') || 'Unknown error'});

                self.checked = res.active;
            });
        })
        .find('.editAble.hts > lable').on('click', function(){
            let id = $(this).parent().data('id');

            $.post('ajax_paytypes.php', 'act=getHts&id=' + id).then(function(res){
                if (!res || res.status === undefined || parseInt(res.status))
                    return Swal.fire({icon: 'error' , title: (res ? (res.error || res._txt) : '') || 'Unknown error'});

                $("#siteID3").val(<?=$siteID?>);
                $("#itemID").val(res.id);
                $("#titleHts").val(res.name);
                $("#newHTS").show().find('.mainTitle').text('עריכת ספק');
            });
        });



        $("#newHtsForm").find('.save').on("click", function(){
            $.post('ajax_paytypes.php', 'act=saveHts&' + $(this).closest('form').serialize()).then(function(res){
                if (!res || res.status === undefined || parseInt(res.status))
                    return Swal.fire({icon: 'error' , title: (res ? (res.error || res._txt) : '') || 'Unknown error'});

                Swal.fire({icon: 'success' , title: res.message}).then(() => window.location.reload());
            });
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
                        return Swal.fire({icon: 'error' , title: response.message});
                    }
                    return Swal.fire({icon: 'success' , title: 'פעולה הסתיימה בהצלחה'}).then(function (res) {
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