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

function resetOrd(arr){
    $.post(baseUrl + "ajax_load_cupons.php?act=4",{data: arr} , function(res){
        //updated
    });
}



function bindListElements(){
    $("#sid").off().on("change",function () {
        let selected = $(this).val();
        if(selected == 0) {
            $(".top-btns .global-edit").hide();
            $(".top-btns .link").hide();
            $(".add-new").hide();
            $(".page-options").hide();
            $('.cuponscard').show();
            $(".ordercards").hide();
            $(".link.send_btn").attr("href","#");
            $(".plusSend").data("msg","");
            $("#guid").val("0");
            $(".cuponscards-list").sortable( "disable" );
        }
        else {
            $("#guid").val($("#guid" + selected).val());
            $(".top-btns .global-edit").show();
            $(".top-btns .link").show();
            $(".add-new").show();
            $(".page-options").show();
            $('.cuponscard').hide();
            $('.cuponscard[data-sid="'+selected+'"]').show();
            $(".ordercards").show();

            $(".cuponscards-list").sortable({
                stop: function(){
                    var sorted = $(".cuponscards-list").sortable('toArray');
                    console.log(sorted)
                    var iter = 1;
                    sorted = sorted.map(function(Item){
                        return Item.replace("cuponscard","");
                    });
                    resetOrd(sorted);



                }});

        }
        $("input[name='siteID2']").val(selected);
    });



    bindInputNum();
}

function delete_cupons(cuponsid,siteID) {
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
            $.get("ajax_load_cupons.php?act=3&id=" + cuponsid + "&siteID2=" +siteID, function(resp){
				try{
					var response = JSON.parse(resp);
				} catch (e) {
					var response = resp;
				}
				if(response.success == true) {
					$("#cuponscard" + cuponsid).remove();
					Swal.fire({
						title: 'נמחק',
						type: 'info'
					});
				}else{
					swal.fire({type: "error", title: "שגיאה" , text: response.error});
				}
            });

        }
    });
}

function activeDeActive(id,siteID){
    $.get("ajax_load_cupons.php?act=2&id=" + id + "&siteID2=" +siteID,function(resp){
		try{
			var response = JSON.parse(resp);
		} catch (e) {
			var response = resp;
		}
		if(response.success == true) {
			//Done
		}else{
			swal.fire({type: "error", title: "שגיאה" , text: response.error});
		}
    });
}
function stringEscape(s) {
    return s ? s.replace(/\\/g,'\\\\').replace(/\n/g,'\\n').replace(/\t/g,'\\t').replace(/\v/g,'\\v').replace(/'/g,"\\'").replace(/"/g,'\\"').replace(/[\x00-\x1F\x80-\x9F]/g,hex) : s;
    function hex(c) { var v = '0'+c.charCodeAt(0).toString(16); return '\\x'+v.substr(v.length-2); }
}
function loadcuponsCardData(gid){
    if(gid != 0) {

        $.get("ajax_load_cupons.php?id=" + gid,function(resp){
            try{
                var response = JSON.parse(resp);
            } catch (e) {
                var response = resp;
            }
            if(response.success == true) {

                //debugger;
                $('#cuponspopPop .mainTitle').html("עריכת קופון הנחה");
                $("input[name='siteID2']").val(response.data.siteID);
                $(".cuponspop #title").val(response.data.title);
                $(".cuponspop #amount").val(response.data.amount);
                $(".cuponspop #cCode").val(response.data.cCode);
                $(".cuponspop #expire").val(response.data.expire);
                $(".cuponspop #maxDiscount").val(response.data.maxDiscount);
                $("#cType").val(response.data.cType);
                $("#cType").change();
                response.data.cDesc = response.data.cDesc.replace(/(?:\\r\\n)/g, '\r\n');
                response.data.cDesc = response.data.cDesc.replace(/(?:\\)/g, '');
                $(".cuponspop #cDesc").val(response.data.cDesc);



                $("#id").val(gid);
                $('.cuponspop').fadeIn('fast');
            }
            else {
                swal.fire({type: "error", title: "שגיאה" , text: "משהו השתבש"});
            }

        });
    }
    else {
        var siteID = $("#cuponsCardForm input[name='siteID2']").val();
        $("#cuponsCardForm")[0].reset();
        $("#cuponsCardForm input[name='siteID2']").val(siteID);
        $("#picpic-img").attr("src","").hide();
        $("#cuponsCardID").val(gid);
        $("#id").val(gid);		
		$("#cType").change();
        $('.cuponspop').fadeIn('fast');
    }

}

function submitcuponsCardForm(e,form){
    e.preventDefault();
    var formData = new FormData(form);
    var daysValid = parseInt($("#daysValid").val());
    // if(!daysValid || daysValid < 3 || daysValid > 24) {
    //     chan''eBtnTxt("cuponsCardForm",2);
    //     return swal.fire({type: "info", title: "תוקף" , text: "תוקף אפשרי בין 3 ל 24 חודשים"});
    // }


    $.ajax({
        method: "POST",
        url: baseUrl + "ajax_load_cupons.php?act=1",
        data: formData,
        success: function (resp) {
            try {
                var response = JSON.parse(resp);
            } catch (e) {
                var response = resp;
            }
            changeBtnTxt("cuponsCardForm",2);
            if(response.success == true) {
                swal.fire({type: "success", title: "נשמר" , text: "הפעולה הסתיימה בהצלחה"}).then(function () {
                    window.location.reload();
                });
            }
            else {
                swal.fire({type: "error", title: "שגיאה" , text: response.error});
            }
        },
        processData: false,
        contentType: false,
        cache: false,
        fail:function (res) {
            changeBtnTxt("cuponsCardForm",2);
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

function bindcuponsCardsElements(){

    $("#cuponspopPop .img label").off().on("click",function(){
        $(this).closest("input[type='file']").trigger("click");
    });

    $("#cuponspopPop input[type='file']").on("change",function(){
        var imgId = "#" + $(this).attr("id") + "-img";
        readURL(this, imgId);
    });

    $(".cuponscard .edit").off().on("click",function(){
        var id = $(this).data("id");
        loadcuponsCardData(id);
    });

    $("#cuponsCardForm .save").off().on("click",function(){
        changeBtnTxt("cuponsCardForm",1);
        $("#cuponsCardForm").submit();
    });

    $("#cuponsCardForm").on("submit",function (e) {
        submitcuponsCardForm(e,this);
    });




    bindInputNum();
}
var showPOP = function(gid,oid){
    $.get("ajax_pop_cupons.php?gID=" + gid + "&oid=" + oid , function (res) {
        $(".cuponscard.cupons-pop.cupons").html(res);
        $('.cuponscard.cupons-pop.cupons').fadeIn('fast');
    });
} ;



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
        baseUrl = "/cms/modules/minisites/cuponsCards/"
    }
    $('#expire').datetimepicker({
        format: 'd/m/Y',
        timepicker: false
    });
    bindcuponsCardsElements();
    bindListElements();

});