var baseUrl = "./";


function readURL(input,displayTaget) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function (e) {
            $(displayTaget).attr('src', e.target.result).show();
        }

        reader.readAsDataURL(input.files[0]);
    }
}

function changeBtnTxt(form,act){
    if(act == 1) {
        $("#"+form+" .save").data("mainTxt",$("#"+form+" .save").text());
        $("#"+form+" .save").text('מבצע שמירה...');
        $("#"+form+" .save").attr("disabled",true);
    }
    else {
        $("#"+form+" .save").text($("#"+form+" .save").data("mainTxt"));
        $("#"+form+" .save").attr("disabled",false);
    }

}

// function sortgiftCards() {
//     var siteid=  $("#sid").val();
//     var divList = $(".giftcard[data-sid='"+siteid+"']");
//     divList.sort(function(a, b){
//         var va = $(a).attr("data-ord"), vb = $(b).attr("data-ord");
//         if(va > vb) {
//             return 1;
//         } else if(va < vb) {
//             return -1;
//         } else {
//             return 0;
//         }
//     });
//     $(".giftcards-list[data-id='"+siteid+"']").append(divList);
//
// }

function resetOrd(arr){
    $.post(baseUrl + "ajax_load_giftcard.php?act=4",{data: arr} , function(res){
        //updated
    });
}

// function moveUp(id){
// 	alert(id);
//     var ord = $("#giftcard"+id).attr("data-ord");
//     ord = ord - 2;
//     $("#giftcard"+id).attr("data-ord",ord);
//     sortgiftCards();
//     setTimeout(resetOrd,10);
// }

// function moveDown(id){
//     var ord = $("#giftcard"+id).attr("data-ord");
//     ord = ord + 2;
//     $("#giftcard"+id).attr("data-ord",ord);
//     sortgiftCards();
//     setTimeout(resetOrd,10);
// }

function loadGeneralForm(){
    var siteID = $("#sid").val();
    $.get(baseUrl + "ajax_giftcards_setting.php?act=1&siteID2=" + siteID,function(response){
        try{
            var res = JSON.parse(response);
        } catch (e) {
            var res = response;
        }
        if(res.success == true && res.data) {

			$("#globaloptionsForm input[name=id]").val(res.data.giftCardsSettingID);
            $("#globaloptionsForm #title").val(res.data.title);
            $("#globaloptionsForm #desc").val(res.data.siteDescription);
            $("#globaloptionsForm #toptext").val(res.data.toptext);
            $("#globaloptionsForm #small_letters").val(res.data.smallLetters);
            $("#globaloptionsForm #meta_desc").val(res.data.meta_desc);

            if(res.data.logo) {
                $("#logo-img").attr("src",'/gallery/' + res.data.logo).show();
            }
            if(res.data.backgroundImage) {
                $("#bgimg-img").attr("src",'/gallery/' + res.data.backgroundImage).show();
            }
        }
        else {
            $("#globaloptionsForm")[0].reset();
            $("#logo-img").attr("src","").hide();
            $("#bgimg-img").attr("src","").hide();
        }

    });
    $('.global_edit').fadeIn('fast');
}

function submitGeneralForm(e,form){
    e.preventDefault();
    var formData = new FormData(form);
    formData.set("siteID2",$("#sid").val());
    if($('#logo')[0].files[0]) {
        formData.set("logo",$('#logo')[0].files[0]);
    }
    if($('#bgimg')[0].files[0]) {
        formData.set("bgimg",$('#bgimg')[0].files[0]);
    }


    $.ajax({
        method: "POST",
        url: baseUrl + "ajax_giftcards_setting.php?act=0",
        data: formData,
        success: function (resp) {
            try {
                var response = JSON.parse(resp);
            } catch (e) {
                var response = resp;
            }
            changeBtnTxt("globaloptionsForm",2);
            if(response.success == true) {
                swal.fire({type: "success", title: "נשמר" , text: "הפעולה הסתיימה בהצלחה"}).then(function () {
                    window.location.reload();
                });
            }
            else {
                swal.fire({type: "error", title: "שגיאה" , text: "משהו השתבש"});
            }
        },
        processData: false,
        contentType: false,
        cache: false,
        fail:function (res) {
            changeBtnTxt("globaloptionsForm",2);
            swal.fire({type: "error", title: "שגיאה" , text: "משהו השתבש"});
        }
    },'json');
}

function bindListElements(){
    $("#sid").off().on("change",function () {
        let selected = $(this).val();
        if(selected == 0) {
            $(".top-btns .global-edit").hide();
            $(".top-btns .link").hide();
            $(".add-new").hide();
            $(".page-options").hide();
            $('.giftcard').show();
            $(".ordercards").hide();
            $(".link.send_btn").attr("href","#");
            $(".plusSend").data("msg","");
            $("#guid").val("0");
			$(".giftcards-list").sortable( "disable" );
        }
        else {
            $("#guid").val($("#guid" + selected).val());
            $(".top-btns .global-edit").show();
            $(".top-btns .link").show();
            $(".add-new").show();
            $(".page-options").show();
            $('.giftcard').hide();
            $('.giftcard[data-sid="'+selected+'"]').show();
            $(".ordercards").show();

            $(".link.send_btn").attr("href","http://vouchers.co.il/g.php?guid=" + $("#guid").val());
            $(".plusSend").data("msg",encodeURIComponent(" http://vouchers.co.il/g.php?guid=" +  $("#guid").val()));
			$(".giftcards-list").sortable({
                stop: function(){
                    var sorted = $(".giftcards-list").sortable('toArray');
                    console.log(sorted)
                    var iter = 1;
                    sorted = sorted.map(function(Item){
                        return Item.replace("giftcard","");
                    });
                    resetOrd(sorted);



                }});

        }
        $("input[name='siteID2']").val(selected);
    });



    bindInputNum();
}

function bindGeneralFormElements(){


    $("#global_editPop .img label").off().on("click",function(){
        $(this).closest("input[type='file']").trigger("click");
    });

    $("#global_editPop input[type='file']").on("change",function(){
        var imgId = "#" + $(this).attr("id") + "-img";
        readURL(this, imgId);
    });

    $("#globaloptionsForm .save").off().on("click",function(){
        changeBtnTxt("globaloptionsForm",1);
        $("#globaloptionsForm").submit();
    });

    $("#globaloptionsForm").on("submit",function (e) {
        submitGeneralForm(e,this);
    });

}

function delete_gift(giftid) {
    Swal.fire({
        title: 'האם אתם בטוחים שברצוכם למחוק?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'כן מחק!',
        cancelButtonText: 'ביטול'
    }).then((result) => {
        console.log(result);
        if (result.value == true) {
            $.get("ajax_load_giftcard.php?act=3&id=" + giftid , function(resp){
                $("#giftcard" + giftid).remove();
                Swal.fire({
                        title: 'נמחק',
                        type: 'info'
                    });
            });

        }
    });
}

function activeDeActive(id){
    $.get("ajax_load_giftcard.php?act=2&id=" + id,function(resp){
        //Done
    });
}
function stringEscape(s) {
    return s ? s.replace(/\\/g,'\\\\').replace(/\n/g,'\\n').replace(/\t/g,'\\t').replace(/\v/g,'\\v').replace(/'/g,"\\'").replace(/"/g,'\\"').replace(/[\x00-\x1F\x80-\x9F]/g,hex) : s;
    function hex(c) { var v = '0'+c.charCodeAt(0).toString(16); return '\\x'+v.substr(v.length-2); }
}
function loadGiftCardData(gid){
    if(gid != 0) {

        $.get("ajax_load_giftcard.php?id=" + gid,function(resp){
            try{
                var response = JSON.parse(resp);
            } catch (e) {
                var response = resp;
            }
            if(response.success == true) {

			//debugger;
				if(response.admin){
					$('#giftpopPop .mainTitle').html("עריכת גיפטקארד");
					$('#giftpopPop input,#giftpopPop textarea,#giftpopPop file').attr('readonly', false);
					$('#giftpopPop input,#giftpopPop textarea,#giftpopPop file').attr('disabled', false);
				}else{
					$('#giftpopPop .mainTitle').html("נתוני גיפטקארד");
					//$('#giftpopPop input,#giftpopPop textarea,#giftpopPop file').attr('readonly', true);
					//$('#giftpopPop input,#giftpopPop textarea,#giftpopPop file').attr('disabled', true);
				}
				if(response.data.giftType == '2') {
                    $(".giftpop #showPrice").parent().parent().hide();
                    $(".giftpop #amount").parent().hide();
                }
				else {
                    $(".giftpop #showPrice").parent().parent().show();
                    $(".giftpop #amount").parent().show();
                }
                $("input[name='siteID2']").val(response.data.siteID);
                $(".giftpop #title").val(response.data.title);
                $(".giftpop #amount").val(response.data.sum);
                $(".giftpop #daysValid").val(response.data.daysValid);
                if(response.data.showPrice == 1 || response.data.giftType == '2') {
                    $(".giftpop #showPrice").attr("checked",true);
                }
                else {
                    $(".giftpop #showPrice").attr("checked",false);
                }
                //if(response.data.expirationDate) {
                    // let date = new Date(response.data.expirationDate);
                    // let month = (date.getMonth() + 1);
                    // let day = date.getDate();
                    // let hour = date.getHours();
                    // let minutes = date.getMinutes();
                    // minutes = minutes < 10 ? "0" + minutes : minutes;
                    // hour = hour < 10 ? "0" + hour : hour;
                    // day = day < 10 ? "0" + day : day;
                    // month = month < 10 ? "0" + month : month;
                    // date = day + "/" + month + "/" + date.getFullYear() + " " + hour + ":" + minutes;
                    //$(".giftpop #expirationDate").val(response.data.daysValid);
                //}
                response.data.description = response.data.description.replace(/(?:\\r\\n)/g, '\r\n');
                response.data.description = response.data.description.replace(/(?:\\)/g, '');

                response.data.restrictions = response.data.restrictions.replace(/(?:\\r\\n)/g, '\r\n');
                response.data.restrictions = response.data.restrictions.replace(/(?:\\)/g, '');

                $(".giftpop #desc").val(response.data.description);
                $(".giftpop #restrictions").val(response.data.restrictions);

                if(response.data.image) {
                    $("#picpic-img").attr("src", "/gallery/" + response.data.image).show();
                }
                $("#giftCardID").val(gid);
                $('.giftpop').fadeIn('fast');
            }
            else {
                swal.fire({type: "error", title: "שגיאה" , text: "משהו השתבש"});
            }

        });
    }
    else {
        var siteID = $("#giftCardForm input[name='siteID2']").val();
        $("#giftCardForm")[0].reset();
        $("#giftCardForm input[name='siteID2']").val(siteID);
        $("#picpic-img").attr("src","").hide();
        $("#giftCardID").val(gid);
        $('.giftpop').fadeIn('fast');
    }

}

function submitGiftCardForm(e,form){
    e.preventDefault();
    var formData = new FormData(form);
    var daysValid = parseInt($("#daysValid").val());
    if(!daysValid || daysValid < 3 || daysValid > 24) {
        changeBtnTxt("giftCardForm",2);
        return swal.fire({type: "info", title: "תוקף" , text: "תוקף אפשרי בין 3 ל 24 חודשים"});
    }
    if($('#picpic')[0].files[0]) {
        formData.set("picpic",$('#picpic')[0].files[0]);
    }

    $.ajax({
        method: "POST",
        url: baseUrl + "ajax_load_giftcard.php?act=1",
        data: formData,
        success: function (resp) {
            try {
                var response = JSON.parse(resp);
            } catch (e) {
                var response = resp;
            }
            changeBtnTxt("giftCardForm",2);
            if(response.success == true) {
                swal.fire({type: "success", title: "נשמר" , text: "הפעולה הסתיימה בהצלחה"}).then(function () {
                    window.location.reload();
                });
            }
            else {
                swal.fire({type: "error", title: "שגיאה" , text: "משהו השתבש"});
            }
        },
        processData: false,
        contentType: false,
        cache: false,
        fail:function (res) {
            changeBtnTxt("giftCardForm",2);
            swal.fire({type: "error", title: "שגיאה" , text: "משהו השתבש"});
        }
    },'json');
}


function bindInputNum(){
    $("input.num").keydown(function (e) {
        var key = e.charCode || e.keyCode || 0;
        // allow backspace, tab, delete, enter, arrows, numbers and keypad numbers ONLY
        // home, end, period, and numpad decimal
        return (
            key == 8 ||
            key == 9 ||
            key == 13 ||
            key == 46 ||
            key == 110 ||
            key == 190 ||
            (key >= 35 && key <= 40) ||
            (key >= 48 && key <= 57) ||
            (key >= 96 && key <= 105));
    });
}

function bindGiftCardsElements(){

    $("#giftpopPop .img label").off().on("click",function(){
        $(this).closest("input[type='file']").trigger("click");
    });

    $("#giftpopPop input[type='file']").on("change",function(){
        var imgId = "#" + $(this).attr("id") + "-img";
        readURL(this, imgId);
    });

    $(".giftcard .edit").off().on("click",function(){
        var id = $(this).data("id");
        loadGiftCardData(id);
    });

    $("#giftCardForm .save").off().on("click",function(){
        changeBtnTxt("giftCardForm",1);
        $("#giftCardForm").submit();
    });

    $("#giftCardForm").on("submit",function (e) {
        submitGiftCardForm(e,this);
    });

    // $('#expdate').datetimepicker({
    //     format: 'd/m/Y H:i',
    //     timepicker: true
    // });


    bindInputNum();
}
var showPOP = function(gid,oid){
    $.get("ajax_pop_gift.php?gID=" + gid + "&oid=" + oid , function (res) {
        $(".giftcard.gift-pop.gift").html(res);
        $('.giftcard.gift-pop.gift').fadeIn('fast');
    });
} ;

var bindMimushElems = function(){

    $("#mimushShovar .bottom-btns .part").off().on("click",function(){
        var availSum = +$("#moneyLeft").data("avail");
        var wishSum = +$("#sumToUse").val();
        if(wishSum && (availSum - wishSum) > -1) {
            Swal.fire({
                title: 'האם אתה בטוח שברצונך לממש את השובר?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'כן!',
                cancelButtonText: 'ביטול'
            }).then((result) => {
                if (result.value == true) {
                    $("#mimushShovar").submit();
                }
            });
        }
        else {
            swal.fire({icon: "error", title: "שגיאה" , text: "נא להקליד סכום קטן או שווה ל " + availSum});
        }

    });
    $("#mimushShovar").off().on("submit",function(event){
        event.preventDefault();
        $(".bottom-btns .part", this).css('pointer-events', 'none');

        var formData = {act:"use"};
        var wishSum = +$("#sumToUse").val();
        formData.giftcardID = $("#giftCardID").val();
        formData.pID = $("#pID").val();
        formData.sumToUse = wishSum;
        formData.comments = $("#commentsUsage").val();
        $.ajax({
            method: "POST",
            url: "ajax_giftcards.php",
            data: formData,
            success: function (resp) {
                try{
                    var res = JSON.parse(resp);
                } catch (e) {
                    var res = resp;
                }
                if(res.success) {
                    swal.fire({type: "success",title: "הפעולה בוצעה בהצלחה"}).then(function(){
                        window.location.reload();
                    });
                }
                else {
                        swal.fire({type: "error",title: res.error || "משהו השתבש"});

                }
                $(".giftcard.gift-pop.mimush").fadeOut('fast');

            },
            fail: function (resp) {
                swal.fire({icon: "error",title: "משהו השתבש"});
                $(".giftcard.gift-pop.mimush").fadeOut('fast');
            }
        });

    });
};

var mimushPop = function(mtype){
    $(".giftcard.gift-pop.mimush").fadeIn('fast');
    $(".bottom-btns .part", '#mimushShovar').css('pointer-events', 'auto');

    var availSum = +$("#moneyLeft").data("avail");
	if(mtype == 2) {
        $("#sumToUse").val(availSum);
        $("#sumToUse").attr("disabled",true);
		$("#mimushLeftSum").html("בשווי ₪" + availSum);
    }
    else {
        $("#sumToUse").val("");
        $("#sumToUse").attr("disabled",false);
		$("#mimushLeftSum").html("עד ₪" + availSum);
    }

    bindMimushElems();
}

function searchShovar(gid){
    if(gid.length == 12) {
        showPOP(0,gid);
    }
    else {
        return swal.fire({type: "error", title: "שגיאה" , text: "מספר שובר חייב להיות באורך 12 תווים"});
    }
}
$(document).ready(function () {

    if($("body").hasClass("dashboard")) {
        baseUrl = "/cms/modules/minisites/giftCards/"
    }

    bindGeneralFormElements();
    bindGiftCardsElements();
    bindListElements();

});