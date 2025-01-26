

var eq = -1;
function autoCompleteNew(inputBox,inputText, e, showAll){
    var keyCheck = false;
    var clear = true;
    showAll = (showAll === undefined) ? true : showAll;
    if(e.which === 13 || e.which === 40 || e.which === 38){
        clear = false;
    }else{
        clearAutoKey();
    }
    locations = placesArrayFree;

    var currentVal = $("#"+inputBox+" #"+inputText+" ").val().toLowerCase();
    var results = [];

    $.grep(locations, function(val){
        if(showAll){
            if((val.name && val.name.toLowerCase().indexOf(currentVal.toLowerCase()) > -1) || (val.phone && val.phone.toLowerCase().indexOf(currentVal.toLowerCase()) > -1)){
                results.unshift(val);
            }else if ((val.name && val.name.toLowerCase().indexOf(currentVal.toLowerCase()) > -1) || (val.phone && val.phone.toLowerCase().indexOf(currentVal.toLowerCase()) > -1)) {
                results.push(val);
            }

        }
    });
    if(results.length && currentVal.length){
        if(clear){
        $("#"+inputBox +" .autoBox > .autoSuggest").hide();
        $("#"+inputBox +" .autoBox > .autoComplete").show();
        $("#"+inputBox +" .autoBox > .autoComplete .sites .cont, #"+inputBox +" .autoBox > .autoComplete .locations .cont").html("");
        $("#"+inputBox +" .autoBox > .autoComplete .sites, #"+inputBox +" .autoBox > .autoComplete .locations").show();

        $.each(results, function(i,val){
            var nameBiulder = val.name.toLowerCase().replace(currentVal.toLowerCase(), currentVal.toLowerCase());
            $("#" + inputBox + " .autoBox > .autoComplete .locations .cont").append("<span onclick=\"autoResults('" + val.id.replace("'", "`").replace("'", "`") + "', '" + inputText + "', '" + inputBox + "','" + val.name.replace("'", "`").replace("'", "`") + "', '"+val.idnumber+"', '"+val.phone+"', '"+val.phone2+"', '"+val.email+"', '"+val.address+"')\">" + nameBiulder + " - " +val.phone+ "</span>");
        });

        if(!$("#"+inputBox +" .autoBox > .autoComplete .sites .cont a").length)
            $("#"+inputBox +" .autoBox > .autoComplete .sites").hide();

        if(!$("#"+inputBox +" .autoBox > .autoComplete .locations .cont span").length)
            $("#"+inputBox +" .autoBox > .autoComplete .locations").hide();

        }
    }else{
        $("#"+inputBox +" .autoBox > .autoComplete").hide();
        $("#"+inputBox +" .autoBox > .autoSuggest").show();
    }
}



$(document).on('keyup', "input[name='name']", function(e){

    if($(".autoComplete").css("display")=="block"){
        $(this).parent().addClass("active");
    } else {
        $(this).parent().removeClass("active");
    }
   /* if(e.which==13 && $(this).val()){
        e.preventDefault();
        $('#submitSearchForm').trigger('click');
    }*/

});

function autoResults(resid,inputText,inputBox,name,idnumber,phone,phone2,email,address){
    clearAutoKey();
    $("#"+inputBox+" #"+inputText+" ").val(name);
    $("#"+inputBox+" input").removeClass("notSet");
    $("#"+inputBox+" #"+inputText+" ").closest('form').find('#phone').val(phone);
    $("#"+inputBox+" #"+inputText+" ").closest('form').find('#phone2').val(phone2);
    $("#"+inputBox+" #"+inputText+" ").closest('form').find('#email').val(email);
    $("#"+inputBox+" #"+inputText+" ").closest('form').find('#clientAddress').val(address);
    $("#"+inputBox+" #"+inputText+" ").closest('form').find('#tZehoot').val(idnumber);
    //$("#freeSearch").submit();
    //autoComplete(inputBox, inputText,e);
    $("#submitSearchForm").focus();
    $('#searchFreeBox>input').removeClass('viewCloser');
	$('#freeSearchParam').val(resid);
	// $('#freeSearchType').val(type);
    // createSearchLink();
}
$(window).keydown(function(e){
    var isAvtice = false;
    var activeBox;
    if($("#searchFreeBox").hasClass("active")){
        isAvtice = true;
        activeBox = "searchFreeBox";
    }

    if((e.which === 13) && isAvtice){
        e.preventDefault();
        $("a.keyActive").trigger("click");
    }

    $("#searchFreeBox a").removeClass("keyActive");

    if(e.which === 40 && isAvtice){
        e.preventDefault();
        if($("#"+ activeBox +" input").val() == ""){
            var howMany = $("#"+ activeBox +" .autoSuggest a").length;
            if(eq<howMany-1){
                eq++;
            }
            $("#"+ activeBox +" .autoSuggest a").eq(eq).addClass("keyActive");
        }else{
            var howMany = $("#"+ activeBox +" .autoComplete a").length;
            if(eq<howMany-1){
                eq++;
            }
            $("#"+ activeBox +" .autoComplete a").eq(eq).addClass("keyActive");
        }

        $(".autoBox").scrollTop(eq*32);
    }
    if(e.which === 38 && isAvtice){
        e.preventDefault();
        if(eq>0){
            eq--;
        }
        if($("#"+ activeBox +" input").val() == ""){
            $("#"+ activeBox +" .autoSuggest a").eq(eq).addClass("keyActive");
        }else{
            $("#"+ activeBox +" .autoComplete a").eq(eq).addClass("keyActive");
        }
        $(".autoBox").scrollTop(eq*32);
    }
});


function clearAutoKey(){
    eq=-1;
    $("#searchBox a").removeClass("keyActive");
}