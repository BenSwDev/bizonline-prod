<?php
/**
 * Created by PhpStorm.
 * User: Gal Matheys
 * Date: 15/05/2022
 * Time: 13:23
 */


if(isset($_POST) && isset($_POST['title'])) {

    $data = typemap($_POST,[
        'id' => 'int',
        'siteID2' => 'int',
        'title' => 'string',
        'active' => 'int',
        'fromDate' => 'date',
        'toDate' => 'date'
    ]);
    $dateFrom = implode("-", array_reverse(explode(".",$_POST['fromDate'])));
    $dateTo = implode("-", array_reverse(explode(".",$_POST['toDate'])));
    $updateData = [
        'siteID' => $data['siteID2'],
        'title' => $data['title'],
        'active' => $data['active'],
        'fromDate' => $dateFrom,
        'toDate' => $dateTo
    ];

    if($data['id']) {
        udb::update("sites_sales_texts",$updateData," id=" . $data['id']);
        echo 'ok update';
    }
    else {
        udb::insert("sites_sales_texts",$updateData);
        echo 'ok insert';
    }


    die();
}

$sql = "select * from sites_sales_texts WHERE `siteID` IN (" . $_CURRENT_USER->sites(true) . ")";
$sales = udb::full_list($sql);
?>
<style>
    table.giftcards-log{background:#fff;margin:20px auto;text-align:center;box-sizing:border-box}
    table.giftcards-log td,table.giftcards-log th{padding:10px;border-bottom:.5px solid #0dabb6;border-left:.5px solid #0dabb6;border-right:.5px solid #0dabb6;border-top:.5px solid #0dabb6}
    .giftcard.gift-pop{position:fixed;top:0;left:0;bottom:0;background:rgba(0,0,0,.6);width:100%;right:0;height:100vh}
    .giftcard.gift-pop .gift_container{position:absolute;top:50%;right:50%;transform:translateY(-50%) translateX(50%);width:100%;max-width:800px;background:#e0e0e0;padding:10px;text-align:right;box-sizing:border-box}
    .giftcard.gift-pop .gift_inside{background:#fff;box-shadow:0 0 2px rgb(0 0 0 / 60%);position:relative;}
    .giftcard.gift-pop .gift_inside>.title{padding:20px;box-sizing:border-box}
    .giftcard.gift-pop .gift_container ul {list-style: none;font-size: 0;padding: 10px;box-sizing: border-box;max-height: calc(100vh - 220px);overflow: auto;}
    .giftcard.gift-pop .gift_container ul li {display: inline-block;width: 100%;max-width: 33.33%;font-size: 16px;padding-left: 20px;box-sizing: Border-box;}
    .giftcard.gift-pop .gift_container ul li>div .title{display:inline-block;width:130px;color:#9e9e9e;padding-bottom:4px}
    .giftcard.gift-pop .gift_container ul li>div .con{display:inline-block;width:100%}
    .giftcard.gift-pop .gift_container ul li:last-child{padding:0;}
    .giftcard.gift-pop.mimush .gift_container ul li:last-child{max-width:66%}
    .giftcard.gift-pop .gift_container ul li>div{min-height:30px;margin-bottom:10px}
    .giftcard.gift-pop .gift_inside>.close{position:Absolute;top:10px;left:10px;width:20px;height:20px;padding: 4px;box-sizing:border-box;border:1px solid #0dabb6;cursor:pointer;border-radius:20px}
    .giftcard.gift-pop .gift_inside>.close svg{width:100%;height:auto;fill:#0dabb6}
    .giftcard.gift-pop .gift_inside>hr{height:2px;background:#e0e0e0;display:block;margin-bottom:10px}
    .bottom-btns{display:block;text-align:center}
    .bottom-btns>div{cursor:pointer;background:#0dabb6;display:inline-block;margin:0 5px 10px 5px;line-height:40px;padding:0 20px;box-sizing:border-box;color:white}
    .fast-find{background:#0dabb6;margin:20px auto 0;display:block;padding:5px;border:1px solid #0dabb6;border-radius:8px;left:0;right:0;position:relative;box-sizing:border-box;max-width:300px;width:100%}
    .fast-find .inputWrap{background:#fff;border-radius:8px;position:relative;height:50px}
    .fast-find .inputWrap .submit{position:absolute;top:50%;left:5px;width:45px;height:45px;transform:translateY(-50%);background:#000;fill:#fff;border-radius:50px;padding:12px;box-sizing:border-box;cursor:pointer}
    .fast-find .inputWrap input{position:absolute;top:0;right:0;left:0;bottom:0;width:100%;height:100%;border-radius:8px;background:0 0;font-size:18px;padding:6px 15px 0 15px;box-sizing:border-box}
    .fast-find .inputWrap input+label{position:absolute;top:0;right:15px;font-size:14px;font-weight:500;color:#0dabb6}


    .searchOrder {margin: 20px auto 0;display: block;padding: 13px 30px;border: 1px solid #0dabb6;border-radius: 8px;background: #fff;left: 0;right: 0;position: relative;max-width: 240px;overflow: hidden;}
    .searchOrder form {margin-top: 10px;}
    .searchOrder form .inputWrap {margin: 5px;}
    .searchOrder form .inputWrap input[type=text] {height: 40px;box-sizing: border-box;padding-right: 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;width: 100%;}
    .searchOrder form .clear {text-decoration: none;font-size: 16px;display: inline-block;vertical-align: top;background: #fff;color: #0dabb6;border-radius: 5px;margin: 5px;border: 1px #0dabb6 solid;float: right;line-height: 40px;width: 60px;text-align: center;}
    .searchOrder form input[type=submit] {display: block;vertical-align: top;background: #0dabb6;color: #fff;border-radius: 5px;cursor: pointer;margin: 5px;width: 100px;float: left;font-size: 20px;line-height: 40px;}
    .searchOrder form .inputWrap select {height: 40px;box-sizing: border-box;padding-right: 10px;font-size: 16px;border: 1px #ccc solid;border-radius: 5px;text-align: right;width: 100%;}
    .giftcard.gift-pop .gift_container ul li>div .con input {height: 30px;border: 1px #aaa solid;padding: 0 10px;width: 100%;box-sizing: border-box;}
    .giftcard.gift-pop.mimush .gift_container {max-width: 570px;}

    .ui-datepicker {z-index: 400 !important;}

    @media (min-width: 992px) {
        .giftcard.gift-pop {max-width:calc(100vw - 300px);right:auto;}
    }

    @media(max-width:700px){
        .giftcard.gift-pop .gift_container ul li{max-width:100%}
    }
</style>
<div class="last-orders">
<h1>מבצעים</h1>
    <button id="newSale" class="add-new">מבצע חדש</button>
    <table class="giftcards-log">
        <thead>
            <tr>
                <th>כותרת</th>
                <th>מתאריך</th>
                <th>עד תאריך</th>
                <th>פעיל</th>
            </tr>
        </thead>
        <tbody>
        <?
        foreach ($sales as $sale) {
            ?>
            <tr data-id="<?=$sale['id']?>" data-siteid="<?=$sale['siteID']?>">
                <td data-item="title"><?=$sale['title']?></td>
                <td data-item="fromDate" data-val="<?=date("d.m.Y" , strtotime($sale['fromDate']))?>"><?=date("d/m/Y" , strtotime($sale['fromDate']))?></td>
                <td data-item="toDate" data-val="<?=date("d.m.Y" , strtotime($sale['toDate']))?>"><?=date("d/m/Y" , strtotime($sale['toDate']))?></td>
                <td data-item="active" data-val="<?=$sale['active']?>"><?=$sale['active'] ? '' :'לא '?>פעיל</td>
            </tr>
            <?
        }
        ?>
        </tbody>
    </table>
</div>
<div class="giftpop order" id="giftpopPop" style="display:none;">
    <div class="container">
        <div class="close" onclick="$('.giftpop').fadeOut('fast')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21">
                <path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path>
            </svg>
        </div>
        <div class="title mainTitle">
            עריכת מבצע
        </div>
        <form class="form" id="giftCardForm" action="" data-guid="" method="post" autocomplete="off" data-defaultagr="1">
            <input type="hidden" name="siteID2" id="siteID2" value="">
            <input type="hidden" name="id" id="id" value="">
            <div class="inputWrap">
                <input type="text" name="title" id="title" value="">
                <label for="title">כותרת</label>
            </div>
            <div class="inputWrap">
                <input type="text" name="fromDate" id="fromDate" class="datepicker" value="">
                <label for="title">מתאריך</label>
            </div>
            <div class="inputWrap">
                <input type="text" name="toDate" id="toDate" class="datepicker" value="">
                <label for="title">עד תאריך</label>
            </div>
            <div class="inputWrap">
                <label for="title">פעיל</label>
                <label class="switch">
                    <input type="checkbox"  name="active" id="active" value="1" >
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="save">שמור</div>
        </form>
    </div>
</div>
<script type="text/javascript" src="/assets/js/jquery.ui.datepicker-he.js"></script>
<link rel="stylesheet" href="/user/assets/css/giftcardedit.css" />
<script>
    $(function() {
        $.datepicker.setDefaults( $.datepicker.regional[ "he" ] );


    });
    $(document).ready(function(){
        $("#newSale").on("click" , function () {
            $("#giftpopPop").show();
            $("#id").val("");
            $("#siteID2").val(<?=$_CURRENT_USER->active_site()?>);
            $("#fromDate").val("");
            $("#toDate").val("");
            $("#title").val("");
            $("#active").attr("checked",true);

            $('.datepicker').datepicker({
                minDate: 0,
                dateFormat: 'dd.mm.yy'
            });
        });

        $(".giftcards-log tbody tr").on("click",function () {
            var thisID = $(this).data("id");
            var siteID = $(this).data("siteid");
            var title = $(this).find("[data-item='title']").text();
            var fromDate = $(this).find("[data-item='fromDate']").data("val");
            var toDate = $(this).find("[data-item='toDate']").data("val");
            var active = $(this).find("[data-item='active']").data("val") == 1 ? true : false;
            console.log(siteID);
            $("#siteID2").val(siteID);
            $("#id").val(thisID);
            $("#fromDate").val(fromDate);
            $("#toDate").val(toDate);
            $("#title").val(title);
            $("#active").attr("checked",active);
            $("#giftpopPop").show();

        });

        $(".save").on("click",function () {

            var form = $("#giftCardForm");
            var sendData = form.serialize();
            $.ajax({
                url: '',
                method: 'POST',
                data: sendData,
                success:function (res) {
                   window.location.reload();
                }
            });

        });


    });
</script>