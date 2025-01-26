function app(){
    $("nav > ul > li.hasSub > .subFix").click(function(){
        $(this).parent().toggleClass("open");
    });

	$("nav").mouseover(function(){
		$("nav").addClass("over");
	});
	$("nav").mouseout(function(){
		$("nav").removeClass("over");
	});
	// $('nav').on({ 'touchstart' : function(e){$("nav").addClass("over");e.stopPropagation();} });
	// $('body').on({ 'touchstart' : function(){
	// 	if($(window).width() > 1024)
	// 		$("nav").removeClass("over");
	// }});




    $("#leftTabsOpener").click(function(){
        $(this).toggleClass("active");
        $("#leftTabs").toggleClass("active");
    });
}

$(function(){
	$('.editItems .tab').click(function(){
	    var data = $(this).data();

		$(this).addClass('active').siblings().removeClass('active');
		$('.' + data.show).removeClass('active').filter('[data-id="' + data.id + '"]').addClass('active');
	});

	$('.openMenu').click(function(){
		$('nav').toggleClass('over');
	});

	$('.sectionName').off().on("click",function(){
		$(this).parent().toggleClass('open');
		if($(window).width() < 766){
			//window.parent.$('body').toggleClass('sectionOpen');
			//$(this).parent().scrollTop(0);
		}
	});

	setSessioninterVal();

});

if($(window).width() <= 1024) {
	$(document).mouseup(function (e)
	{
		var container = $(".openMenu");
		var container2 = $("nav");
		if (!container.is(e.target) && container.has(e.target).length === 0 && !container2.is(e.target) && container2.has(e.target).length === 0)
		{
			$('nav').removeClass('over');
		}
	});
}

function setSessioninterVal(){
	setInterval(newSession(), 15000 * 60);
}
function alerts(title,text){
    $("#alerts").addClass("active");
    $("#alerts > .container > .title").html(title);
    $("#alerts > .container > .body").html(text);
    $("#alerts > .container > .closer").click(function(){
        $("#alerts").removeClass("active");
    });
}

function setSessioninterVal(){
	setInterval(newSession(), 15000 * 60);
}
function formAlert(color, title, text){
    $("#formError > i").removeClass();
    switch(color){
        case "red":
            $("#formError").css({"background":"#E9CCD1", "border-bottom":"1px solid #A54646","color":"#A54646"});
            $("#formError > i").addClass("fa fa-warning");
            break;
        case "green":
            $("#formError").css({"background":"#DFF0D9", "border-bottom":"1px solid #3A7640","color":"#3A7640"});
            $("#formError > i").addClass("fa fa-check");
            break;
        case "blue":
            $("#formError").css({"background":"#D7EDF6", "border-bottom":"1px solid #23718E","color":"#23718E"});
            $("#formError > i").addClass("fa fa-exclamation-circle");
            break;
    }
    $("#formError > .title").text(title);
    $("#formError > .text").text(text);
    $("#formError").fadeIn();

	window.setTimeout(function(){
			 $("#formError").fadeOut();
	}, 9000);
}



/* TABS */

function closeTab(id){
    $("#openTab-"+id).remove();
    $("#aTab-"+id).remove();
    $("table").find("[tab-id='"+id+"']").attr("tab-id", "");
}
function bringToFront(id){
    $(".aTab").css("z-index", "-1");
    $("#aTab-"+id).css("z-index", "200");
    $("#leftTabsOpener").removeClass("active");
    $("#leftTabs").removeClass("active");
}
function minTab(id){
	$("#aTab-"+id).css("z-index", "-1");
}
var indexTabs=0;

function openFrameSite(is, id, title){
	indexTabs++;

	
	if($(is).parent().attr("tab-id").length){
		var openID = $(is).parent().attr("tab-id");
		$("#openTab-"+openID).trigger("click");
	} else {
		$("#theTabs").append('<div class="aTab" style="z-index:200" id="aTab-'+indexTabs+'"><div class="inTabBox"><iframe id="frame'+indexTabs+'" frameborder=0 src="/cms/sites/minisite.php?frame='+indexTabs+'&sID='+id+'"></iframe><div class="aTabCloser" onclick="closeTab(\''+indexTabs+'\')">x</div><div class="aTabMin" onclick="minTab(\''+indexTabs+'\')">-</div></div></div>');
		$("#leftTabs ul").append('<li id="openTab-'+indexTabs+'" onclick="bringToFront(\''+indexTabs+'\')">'+title+'<span onclick="closeTab(\''+indexTabs+'\')"></span></li>');
        if(id!='0'){
			$(is).parent().attr("tab-id", indexTabs);
		}
	}
}

function openFrameSiteOcc(is, id, title){
	indexTabs++;

	
	if($(is).parent().attr("tab-id").length){
		var openID = $(is).parent().attr("tab-id");
		$("#openTab-"+openID).trigger("click");
	} else {
		$("#theTabs").append('<div class="aTab" style="z-index:200" id="aTab-'+indexTabs+'"><div class="inTabBox"><iframe id="frame'+indexTabs+'" frameborder=0 src="/cms/calendar/calendar.php?frame='+indexTabs+'&sID='+id+'"></iframe><div class="aTabCloser" onclick="closeTab(\''+indexTabs+'\')">x</div><div class="aTabMin" onclick="minTab(\''+indexTabs+'\')">-</div></div></div>');
		$("#leftTabs ul").append('<li id="openTab-'+indexTabs+'" onclick="bringToFront(\''+indexTabs+'\')">'+title+'<span onclick="closeTab(\''+indexTabs+'\')"></span></li>');
        if(id!='0'){
			$(is).parent().attr("tab-id", indexTabs);
		}
	}
}




function openFrameSiteAdd(is, id, title){
	indexTabs++;

	
	if($(is).attr("tab-id").length){
		var openID = $(is).attr("tab-id");
		$("#openTab-"+openID).trigger("click");
	} else {
		$("#theTabs").append('<div class="aTab" style="z-index:200" id="aTab-'+indexTabs+'"><div class="inTabBox"><iframe id="frame'+indexTabs+'" frameborder=0 src="/cms/sites/minisite.php?frame='+indexTabs+'&sID='+id+'"></iframe><div class="aTabCloser" onclick="closeTab(\''+indexTabs+'\')">x</div><div class="aTabMin" onclick="minTab(\''+indexTabs+'\')">-</div></div></div>');
		$("#leftTabs ul").append('<li id="openTab-'+indexTabs+'" onclick="bringToFront(\''+indexTabs+'\')">'+title+'<span onclick="closeTab(\''+indexTabs+'\')"></span></li>');
        if(id!='0'){
			$(is).attr("tab-id", indexTabs);
		}
	}
}

function openCls(cls){

	$('#'+cls).toggleClass('open');

}


function showSmsForm(){
    $("#smsForm").toggleClass("open");
}

function changeDates(where, index){



	if (where=="next"){
		index++;
		window.location.href="OccList.php?next="+index;
		

	}
	else{
		index--;
		window.location.href="OccList.php?next="+index;
	}
}

function openMore(classNm){
	$('#'+classNm).toggleClass('open');

}

function autoComplete(inputBox, inputText, inputID){
	
	locations = placesArray;
	var currentVal = $("#"+inputBox+" #"+inputText+" ").val();
	locations = $.grep(locations, function(value) {
		var vals = value.split("@@@");		
		return ( vals[0].search(currentVal) !== -1 );
		
	});
	if(locations != "" && currentVal != ""){
		$("#"+inputBox +" .autoBox > .autoSuggest").hide();
		$("#"+inputBox +" .autoBox > .autoComplete").show();
		$("#"+inputBox +" .autoBox > .autoComplete").html("");

		var countVal = currentVal.length;

		$.each( locations, function(i, res) {
			var newres = res.split("@@@");
			var splitter0 = newres[0].search(currentVal);
			var splitter = newres[0].slice(countVal+splitter0);

			$("#"+inputBox +" .autoBox > .autoComplete").append("<span onclick=\"autoResults('"+inputBox+"','"+newres[0].replace("'","`")+"','"+newres[1].replace("'","`")+"','"+inputText+"','"+inputID+"')\">"+newres[0].substr(0,splitter0)+"<b>"+currentVal+"</b>"+splitter+"</span>");
		});
	}else{
		$("#"+inputBox +" .autoBox > .autoComplete").hide();
		$("#"+inputBox +" .autoBox > .autoSuggest").show();
	}
}


function autoResults(inputBox,res,resid,inputText,inputID){
	$("#"+inputBox+" #"+inputText+" ").val(res);
	$("#"+inputBox+" #"+inputID+" ").val(resid);
	$("#"+inputBox+" input").removeClass("notSet");
	$("#freeSearch").submit();
	autoComplete(inputBox, inputText, inputID);
}

function newSession(){

	$.ajax({
		url: '//'+window.location.hostname+'/cms/reNewSession.php',
		type: 'POST',
		async: false,
		dataType: "text",
		success: function (returndata) {
			if(returndata=="error"){
				
			} else {
			}
		}
	});
}


function checkFree(name){
	$.post('../minisites/ajax_checkFree.php',{name:name},function(res){
	
		alert(res);
	})


}


function export_xl(_tblname){
		var table = $(_tblname);
		if(table && table.length){
			var preserveColors = (table.hasClass('table2excel_with_colors') ? true : false);
			$(table).table2excel({
				exclude: ".noExl",
				name: "Excel Document Name",
				filename: "report_manage" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
				fileext: ".xls",
				exclude_img: true,
				exclude_links: true,
				exclude_inputs: true,
				preserveColors: preserveColors
			});
		}
        // window.location.href = 'ajax_excel_reports_manage.php' + window.location.search;
}