function addOrder(data){
	console.log(data.treatmentMasterSex);
	var slot = $(".row[data-row='" + data.row + "'][data-col='" + data.col + "']");

	//debugger;
	if(caltype > 0){
		var adminApproved = ((data.adminApproved>0)? "approved" : "");
		var approved = ((data.approved>0)? "מאושר" : "לא מאושר");
		var allpaid = (parseInt(data.paidTotal) - parseInt(data.priceTotal) >= 0)? "allpaid" : "";
		var orderID = data.orderID;
		var spaT = data.parentOrder !=  data.orderID ? " spa" : "";
		if(spaT){
			var tGender = data.treatmentMasterSex == 0? "" : (data.treatmentMasterSex == 1? " t-Male" : " t-Female");
			var tGroup = data.countTreats >= 5? " tGroup" : "" ;
			var lockedTherapist = data.lockedTherapist==1? " lockedTherapist" : "" ;
			var gender = data.treatmentClientSex == 1? "👨🏻�" : "👩🏼�";
			var genderClass = data.treatmentClientSex == 1? "male" : "female";
		}
		var cleandiv = '';
		if(data.cleanTime>0){
			var size = Math.round((parseInt(data.cleanTime)/(parseInt(data.treatmentLen) + parseInt(data.cleanTime)))*100);
			cleandiv ='<div class="cleanbreak" style="min-width:'+ size +'%;min-height:'+ size +'%;"></div>';
		}
		var writeslot;

		if(data.allDay == 1){
			//debugger;
			if(viewtype == 2){
				writeslot  = '<div class="order allday" data-parentOrder="'+data.parentOrder+'" data-orderid="' + orderID +'" data-orderidbysite="' + data.unitID + '" data-size="' + data.width + '" data-margin="' + data.right + '" style="width:' + data.width + '%;'+maindir+':' + data.right + '%"><div class="all"></div></div>';
				slot.append(writeslot);
			}else{
				slot.addClass('busy');
			}
		}else{

			var boxtitle =	data.timeFrom.substring(11,16)+" "+(data.therapistID>0? " - " + therapists[data.therapistID] : " - ללא מטפל") +  '&#013;'+
							(data.unitID>0?  roomsNames[data.unitID] : "ללא חדר") +  '&#013;' +
							(data.countTreats? (data.countTreats>1? data.countTreats+" טיפולים" : "טיפול אחד" ) + ' בהזמנה &#013;' : "") +
							(pExtras[data.parentOrder]? pExtras[data.parentOrder]+'&#013;' : "") +
							(pComments[data.parentOrder]? pComments[data.parentOrder]+'&#013;' : "") +
							(data.customerName? data.customerName+'&#013;' : "") +
							(data.customerPhone? data.customerPhone : "אין מספר") + '&#013;' +
							(data.treatmentID>0? treatments[data.treatmentID] : "לא נבחר טיפול")+ " "+(data.treatmentLen>0? data.treatmentLen+" דקות" : "") +  '&#013;עלות טיפול - ₪' + data.price ;

			//console.log(caltype);
			writeslot  = '<div title="'+boxtitle.replace('"','&#34;')+'" class="order ' + adminApproved + spaT + tGender + tGroup + lockedTherapist +'" data-parentOrder="'+data.parentOrder+'" data-orderid="' + orderID +'" data-orderidbysite="' + data.unitID + '" data-size="' + data.width + '" data-margin="' + data.right + '" style="width:' + data.width + '%;'+maindir+':' + data.right + '%">';
			writeslot += '<div class="all"></div><div class="container">';
			writeslot += '<div class="c_status c_s'+data.client_status+'"  ></div>';
			if(caltype == 1){  	
			writeslot += '<div class="c_slots" draggable="true"  ondragstart="startDrag(event)"><div></div></div>';
			}
			writeslot += cleandiv;
			writeslot += '<input type="hidden" class="guid" value="' + data.guid + '">';
			if(spaT){
				writeslot += '<div class="gender ' + genderClass + '">' + gender + '</div>';
			}
			/*************************/
			writeslot += '<div class="opt1">'
			writeslot += '<div class="name customerName"><span class="'+(data.declareID>0?  "V" : "") + (data.h_negatives>0?  " semi" : "")+'"></span>' + data.customerName + '</div>';
			
			if(spaT){
				writeslot += '<div class="phone">'+ (data.treatmentID>0? treatments[data.treatmentID] : "לא נבחר טיפול") + '</div>';
				writeslot += '<div class="phone">'+ (data.treatmentLen>0? data.treatmentLen+" דק" : "--") + '</div>';
			}
			if(caltype == 2){  			 	
				writeslot += '<div class="phone">' + (data.therapistID>0?  therapists[data.therapistID] : "ללא מטפל") + '</div>'
			}else{
				writeslot += '<div class="phone">' + (data.unitID>0?  roomsNames[data.unitID] : "ללא חדר") + '</div>'
			}
			if(data.customerPhone){
				writeslot += '<div class="phone">'+ (data.customerPhone? data.customerPhone : "אין מספר") + '</div>';
			}
			writeslot += '</div>';
			/*************************/
			writeslot += '<div class="opt2">'
			writeslot +=(data.countTreats? (data.countTreats>1? data.countTreats+" טיפולים" : "טיפול אחד" ) + ' בהזמנה &#013;' : "") ;
			writeslot += '</div>';
			/*************************/

			if(data.price){
			writeslot += '<div class="bottom '+ allpaid +'"><div class="price '+ allpaid +'"><span>₪' + data.price + '</span><span>' + (allpaid? "שולם" : "לא שולם")  + '</span></div></div>';
			}
			if(data.p_sourceID!='0' && data.p_sourceID && sourcesArray[data.p_sourceID]){
				writeslot += '<div class="domain-icon '+ data.p_sourceID +'" style="background-color: '+sourcesArray[data.p_sourceID].color+'">'+ sourcesArray[data.p_sourceID].letterSign +'</div>';
			}else{
				writeslot += '<div class="domain-icon" style="background-image:url(' + data.icon + ')"></div>';
			}
			writeslot += '<div class="whatsapp call" style="display:none" data-phone="' + data.customerPhone + '"></div>';
			writeslot += '</div></div>';

			slot.append(writeslot);
		}
	}else{
		//debugger;
		var _status = _classes[String(data.status)];
		var writeslot;
		writeslot  = '<div title=" ' + data.timeFrom + ' - ' +  data.timeUntil + ' " data-size="' + data.width + '" data-margin="' + data.right + '" class="shift_idan approved '+ _status +'" style="width:' + data.width + '%;'+maindir+':' + data.right + '%"><div class="all"></div>';
		writeslot += '<div class="the_overflow"><div class="start_time_look">' + data.timeFrom.substring(11,16) + '</div>';
		writeslot += '<div class="start_time_look"> - </div><div class="start_time_look">' +  data.timeUntil.substring(11,16) + '</div></div>';
		writeslot += '</div>';
		slot.append(writeslot);
	}
	var setDrag = slot.find('.order')[0];
	$(setDrag).on('drag', function (event) {
		if(event.originalEvent.clientX != d_mouse_x || d_mouse_y!=event.originalEvent.clientY){
			daysTableOfst = daysTable.offset();
			d_mouse_x = event.originalEvent.clientX;
			d_mouse_y = event.originalEvent.clientY;
			var d_frame_top = daysTableOfst.top + 200 - $(window).scrollTop();
			var d_frame_btm = daysTableOfst.top + daysTable.height() - 100 - $(window).scrollTop();
			var d_frame_lft = daysTableOfst.left + 100;
			var d_frame_rgt = daysTableOfst.left + daysTable.width() - 330;
			
			//console.log("drag - "+ xscroll+" " +yscroll);
			
			if(d_mouse_y < d_frame_top)	
				yscroll =  d_mouse_y - d_frame_top;
			else if(d_mouse_y > d_frame_btm)
				yscroll = d_mouse_y - d_frame_btm
			else
				yscroll = 0;

			if(d_mouse_x < d_frame_lft)	
				xscroll =  d_mouse_x - d_frame_lft ;
			else if(d_mouse_x > d_frame_rgt)
				xscroll = d_mouse_x - d_frame_rgt
			else
				xscroll = 0;
		}

		if(xscroll || yscroll){
		
			$("#divToScroll").scrollLeft($("#divToScroll").scrollLeft() + (xscroll/5))	
			daysTable.scrollTop(daysTable.scrollTop() + (yscroll/5))	
		}
	})
}

var daysTable = $("#days-table")
var d_mouse_x
var d_mouse_y 
var xscroll = 0;
var yscroll = 0;


function addShift_inOrders(data){
	var slot = $(".row[data-row='" + data.row + "'][data-col='" + data.col + "']");
	var writeslot;
	var addtoshift="";
	var type_name = _typeNames[String(data.status)];
	var _status = _classes[String(data.status)];
	console.log(data.col);
	if(data.bshift){
		data.bshift = (data.bshift/0.006) / data.width + "%";
		addtoshift +='<div class="before-shift" style="width:'+data.bshift+'; height:'+data.bshift+';"></div>';
	}
	if(data.ashift){
		data.ashift = (data.ashift/0.006) / data.width + "%";
		addtoshift +='<div class="after-shift" style="width:'+data.ashift+'; height:'+data.ashift+';"></div>';
	}
	writeslot  = '<div class="shift '+_status+'" data-size="' + data.width + '" data-margin="' + data.right + '" style="width:' + data.width + '%;'+maindir+':' + data.right + '%">' + addtoshift + (data.orderName? data.orderName : type_name)  + '</div>';
	slot.append(writeslot);
}

function addLocked(data){
	//debugger;
	var slot = $(".row[data-row='" + data.row + "'][data-col='" + data.col + "']");
	slot.find('.lock').addClass('active');
}

//Add Remove AllDay
/*function newallDayOrder(element,data){
	debugger;


	//var data = {'unitID':this.roomID, 'from':this.orderDate,'allDay':1};
	$.post('ajax_order.php',data, function(res){
		switch(res.status){
			case 1:
				$(element).addClass("busy");
			break;
			case 2:
				$(element).removeClass("busy");
			break;
			case 3:
				swal.fire({icon: 'info', title: 'החדר תפוס'});
			break;

			default:
		}

	},'json')

}*/

$('.day').hover(function(elm){
	//debugger;	
    var _indx = $(this).index()+1;
    if(!$(this).closest('.days').length){
        _indx--;
    }
    $('.day').removeClass('hovered');
    $('.days > .day:nth-child('+_indx+'),.l-side > .day:nth-child('+(_indx+1)+')').addClass('hovered')
})

$('.row').hover(function(e){
	var _target = $(this);
	var _indx = $(this).index()+1;
	if($(this).parent().find('.offHours').length)
		_indx--;
	
	console.log(_indx)
	$('.row').removeClass('hovered');
	$('.rooms > div.row:first-child ~ .row:nth-child('+(_indx)+'), .rooms > div:first-child:not(.row) ~ .row:nth-child('+(_indx+1)+'), .rooms > div:first-child:nth-child('+(_indx)+')').addClass('hovered');	
})

$('.row').mousemove(function(e){
	if(redline){
	var _indx = $(this).closest('.day').index()+1;
    if(!$(this).closest('.days').length){
        _indx--;
    }
	
	console.log(_indx)	
	if($('#a_tfusa').hasClass('flipped')){
		var _pos = e.pageY - $(this).offset().top;
		var _total = $(this).height();
		var _loc = "top";
	}else{
		var _pos = e.pageX - $(this).offset().left;
		var _total = $(this).width();
		_pos = _total - _pos;
		var _loc = maindir;
	}
	_minutes = (Math.round((_pos/_total)*6) * 10);
	_loc = _loc +":"+ ((_minutes/60) * 100) + "%" ;
	$('.line-marker').remove();
	$('.days > .day:nth-child('+_indx+'),.l-side > .day:nth-child('+(_indx+1)+')').each(function(){$(this).append('<div class="line-marker" style="'+_loc+'"></div>');	});
	}
})

$('.days-table:not(.blocked) .l-side .rooms.shifts .row').on('click', function() {
	openNewShift(this);
});


$('.atfusa .days-table .r-side .rooms .row .lock:not(.disabled)').on('click', function(e) {
	//debugger;
	$.post('ajax_lockShift.php',{uid:$(this).attr('data-uid'),date:$(this).attr('data-date')},function(res){
		console.log(res);
	}).done(function(res){
		if(res.error){
			swal.fire({icon: 'error',title: res.error});
		}else{
			if(res.event == 1){
				//$(".atfusa .row[data-uid='" + $(this).attr('data-uid') +"']").each(function(){$(this).remove()});
				location.reload();

				//alert('LOCK');
			}

		}

	});
});

var draged;
var dragto;
var dragtovars = {};
var shiftpx = {}



function choose_slot(elm){
	//debugger;
	
	var elmwidth = $(elm.target).outerWidth();
	var elmheight = $(elm.target).outerHeight();
	if(maindir == "right"){
		shiftpx.x = $(elm.target).offset().left + $(elm.target).width() - elm.clientX;
	}else{
		shiftpx.x = $(elm.target).offset().left - elm.clientX;
	}
	shiftpx.y = 20-($(elm.target).offset().top + $(elm.target).height() - elm.clientY);
	if($('#a_tfusa').hasClass('flipped'))
		shiftpx.y =  elm.clientY - $(elm.target).offset().top;
	
	if($(elm.target).closest('.order').hasClass('t-Male'))
		$('.a1a1.female').addClass('blocked');
	
	if($(elm.target).closest('.order').hasClass('t-Female'))
		$('.a1a1.male').addClass('blocked');
	
	
	$(elm.target).addClass('inmove');
	$(elm.target).on("dragend",function(){
		$($(this)).removeClass('inmove');
		$('.blap').removeClass('blap');	
		$('.a1a1').removeClass('dragover');	
		$('.a1a1').removeClass('blocked');
		xscroll = 0;
		yscroll = 0;
	});
	draged = $(elm.target);	
	
}



function startDrag(elm) {
	//debugger;
	console.log('test');
	$(elm.target).closest('.order').addClass('blap'); //hides the original element so the false one will have opacity
	
	
}

$('.a1a1').on("dragover",function(elm){
	//console.log("1");
	var theslot = $(elm.target);
	//if(dragto[0].data(col)!=theslot[0].data(col) && dragto[0].data(row)!=theslot[0].data(row)){
	if(!dragto)
		dragto = theslot;
	if(dragto[0]!=theslot[0]){
		$('.a1a1').removeClass('dragover');	
		if(maindir == "right"){
			dragtovars._rgt = $(window).width() - (theslot.offset().left + theslot.outerWidth()); 
		}else{
			dragtovars._rgt = (theslot.offset().left); 
		}
		dragtovars._top = theslot.offset().top; 
		dragtovars._width = theslot.outerWidth();
		dragtovars._height = theslot.outerHeight();		
		dragto = theslot;
	}
	
	
	$(".a1a1[data-row = '"+theslot.data('row')+"']").addClass('dragover');
	
})

function allowDrop(ev) {
  ev.preventDefault();
}

function drag(ev) {
	//debugger;
}

function drop(elm) {
  elm.preventDefault();
  slot = $(elm.target);
  if(slot.hasClass('a1a1')){
	draged.closest('.order').detach().appendTo(slot);
	$('.a1a1').removeClass('dragover');	
	set_drop(elm);
  }
	
  
}

function set_drop(elm){
	if(maindir == "right"){
		var _mx = $(window).width() - elm.clientX;
	}else{
	   var _mx = elm.clientX;
	}
	var _my = elm.clientY;
	//debugger;
	if($('#a_tfusa').hasClass('flipped')){
		var dir = "top";
		var dist = ((_my - dragtovars._top - shiftpx.y)/dragtovars._height)*100
	}else{
		var dir = maindir;
		var dist = ((_mx - dragtovars._rgt - shiftpx.x)/dragtovars._width)*100
	}
	//ROUND distance to 5 minutes gap
	dist = Math.round(dist/25)*25;
	var set_minutes = dist/25 * 15;
	draged.closest('.order').attr("data-margin",dist);
	draged.closest('.order').css(dir,dist+"%")
	$.post('ajax_spaSingle.php',{
		'act':		"moveSpaSingle",
		'id':		draged.closest('.order').attr('data-orderid'),
		'tsid':		draged.closest('.a1a1').attr('data-uid'),		
		'slotstart':draged.closest('.a1a1').attr('data-col'),		
		'minutes':	set_minutes		
		},function(res){
		console.log(res);
	}).done(function(res){
		if(res.success){
			//swal.fire({icon: 'success',title: 'טיפול שונה בהצלחה'}).then(function() {
			//});	
			//debugger;
			var oldTitle = draged.closest('.order').attr('title');
			var oldPart = oldTitle.split("\r")[0];
			var newPart = draged.closest('.a1a1').attr('data-name')+' - '+res.time;
			var newTitle = oldTitle.replace(oldPart, newPart);
			draged.closest('.order').attr('title',newTitle);
				//window.location.reload();
            	
		}else{
			swal.fire({icon: 'error',title: res.error}).then(function(){				
                window.location.reload();
			});	
		}
	});
}

$('body .atfusa .days-table .l-side .rooms .row ').on('click', '.all', function(e) {
	//debugger;
	var _d = $(this);

    if (dragging == 0){
	//debugger;
	var orderID = _d.closest('.order').data("orderid");
	var orderIDBySite = _d.closest('.order').data("orderidbysite");
	e.stopPropagation();
	$("#orderForm-orderID").val(orderID);
	openSpaFrom({"orderID":orderID});
	$('.create_order .mainTitle').text("הזמנה מספר "+orderIDBySite);
	$('.create_order').fadeIn('fast');
	}

});

$('.atfusa .days-table:not(.blocked) .l-side .rooms:not(.shifts) .row').on('click', function(e) {
	//debugger;
	var target = $(event.target);
	if(target.hasClass('c_status')){
		change_c_s(target);
		return;
	}
	if(target.hasClass('c_slots')){
		return;
	}

	if(target.hasClass('price')){
		var orderID = target.closest('.order').attr('data-parentOrder');
		openPayOrder({"orderID":orderID});
		return;
	}

	var _d = $(this);
	console.log(_d.data('col'));

	if($('#a_tfusa').hasClass('dayly')){
		//debugger;
		if($('#a_tfusa').hasClass('flipped')){
			var _pos = e.pageY - $(this).offset().top;
			var _total = $(this).height();
		}else{
			var _pos = e.pageX - $(this).offset().left;
			var _total = $(this).width();
			_pos = _total - _pos;
		}
		_minutes = Math.round((_pos/_total)*6) * 10;
		var _hour = _d.data('col').substring(11,13);
		if(_minutes>=60){
			_hour = parseInt(_hour) + 1;
			_minutes = "00";
		}
		if(_minutes < 1){
			_minutes = "00";
		}
		var _new_time = _d.data('col').substring(0,11)+ _hour + ":" + _minutes + _d.data('col').substring(16);
		_d.data('col', _new_time);
	}





	setTimeout(function() {
    if (dragging == 0){
		//debugger;
		if($(e.target).hasClass('lock')) {
			$.post('ajax_lockShift.php',{uid:_d.attr('data-uid'),date:_d.attr('data-date')},function(res){

				console.log(res);

			}).done(function(res){
				if(res.error){
					swal.fire({icon: 'error',title: res.error});
				}else{
					if(res.event == 1){
						$(e.target).addClass('active');
					}else{
						$(e.target).removeClass('active');
					}
				}


			});
			//alert('LOCK');
		} else {
			//debugger;
			var locked = _d.find('.lock');
			var _break = $(e.target).hasClass('break')? _d.closest('.row') : "";
			if(locked.hasClass('active')){
				swal.fire({icon: 'error',title: 'כדי להכניס טיפול יש לבטל נעילה'});
			}else if(_break){
				//alert('_break')
				openNewShift(_break);//needs to be completed
			}else{
				var data = _d.data(), dt = data.col.split(' ');
				openSpaFrom({}).then(function(){
					insertTreatmentNew({terid:data.uid, date:dt[0], hour:dt[1]});
				});
			}
		}
	}
	}, 300)
});

/*$('.atfusa.units:not(.dayly) .rooms .row').on('click', function(e) {
	//debugger;
	var orderDate = $(this).data("date");
	var uid = $(this).data("uid");
	if(uid){
		newallDayOrder($(this),{
			from: orderDate,
			unitID: uid,
			allDay: 1
		});
	}


});*/




function openNewShift(elem){
	//alert('shiftpop');
	//debugger;

    if (dragging == 0){
		if($(".l-side > .order").length){
			swal.fire({icon: 'error',title: 'לא ניתן לפתוח 2 הזמנות במקביל'});

		}else{
			var siteID = $('#sid').val();
			var unitID = $(elem).data("uid");
			var OrderIDS = $(elem).data("num");
			var worker_name = $(elem).data("name");
			var date = $(elem).data("date");
			var ptype = $(elem).data("pagetype");


			if(!date){
				var myDay = new Date();
				date = getDayFormat(myDay);
			}
			var today = new Date(date.split("/").reverse().join("/"));
			var tomorrow = new Date(today);
			tomorrow.setDate(tomorrow.getDate() + 1);
			tomorrow = getDayFormat(tomorrow);

			var data = {unitID:unitID
								,startDate:date
								,endDate:tomorrow
								,ptype:ptype
								,worker_name:worker_name
								,sid:siteID
								,OrderIDS:OrderIDS
								,workers:workers
								};
			openShiftForm(data);




			window.event.cancelBubble = true;
		}
	}
}


function openShiftForm(data){

	//debugger;
    $('#create_orderPop').remove();
	$.post('ajax_shiftFrom.php',{data:data},function(res){
		$("#orderLside").append(res);
	}).done(function(){

		/*$('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 30
		});*/
		$('.timePicks:not(.added)').each(function(){$(this).AnyPicker(
            {
                mode: "datetime",
                dateTimeFormat: "HH:mm",
                minValue: "00:00",
                maxValue: "23:59",
				intervals: 
				{
					h: 1,
					m: 5,
					s: 1
				}
            }).addClass('added').timetator();
		});
		$('.timePicks').on('blur',function(){
			//debugger;
			setTimeout(function(){
				$('#ap-button-cancel').trigger('click');
			},1)
		});

		if($(window).width() < 992) {
			$('.readonlymob').prop('readonly', true);
		}


	});
}


$('.weekly_shifts').click(function(){
	//debugger;
    $('#create_orderPop').remove();
	var siteID = $('#sid').val();
	var unitID = $(this).data("uid");
	var OrderIDS = $(this).data("num");
	var worker_name = $(this).data("name");
	var date = tfusaDate

	var data = {
		unitID:unitID
		,startDate:date
		,worker_name:worker_name
		,sid:siteID
	};

	$.post("ajax_shiftFromWeek.php", {
        data:data
    },
	function(res){
		$("#orderLside").append(res);
	}).done(function(){

		/*$('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 30
		});*/
		
		$('.timePicks:not(.added)').each(function(){$(this).AnyPicker(
            {
                mode: "datetime",
                dateTimeFormat: "HH:mm",
                minValue: "00:00",
                maxValue: "23:59",
				intervals: 
				{
					h: 1,
					m: 5,
					s: 1
				}
            }).addClass('added').timetator();
		});

		$('.timePicks').on('blur',function(){
		//debugger;
		setTimeout(function(){
			$('#ap-button-cancel').trigger('click');
		},1)
		});

		if($(window).width() < 992) {
			$('.readonlymob').prop('readonly', true);
		}
	});


});

function gen_step_week(data){	
	//debugger;
	$.post("ajax_shiftFromWeek.php", {
        data:data
    },
	function(res){
		$("#orderLside").append(res);
	}).done(function(){

		/*$('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 30
		});*/
		
		$('.timePicks:not(.added)').each(function(){$(this).AnyPicker(
            {
                mode: "datetime",
                dateTimeFormat: "HH:mm",
                minValue: "00:00",
                maxValue: "23:59",
				intervals: 
				{
					h: 1,
					m: 5,
					s: 1
				}
            }).addClass('added').timetator();
		});

		$('.timePicks').on('blur',function(){
		//debugger;
		setTimeout(function(){
			$('#ap-button-cancel').trigger('click');
		},1)
		});

		if($(window).width() < 992) {
			$('.readonlymob').prop('readonly', true);
		}
	});
}



function insertShift(ele){

        var con = true;

        var the_input_data = $('#orderForm').serializeArray();
        var time_units = [];

        $(ele).parent().parent().find('.the_shift_zonesss').find('.time_units_row').each(function(){

            var start_time = "";
            var end_time = "";
            var _status = "";
            var _desc = "";

            $(this).find('input').each(function(){
				//debugger;
                if ($(this).attr("name") == "startTime[]") {start_time = $(this).val();}
                if ($(this).attr("name") == "endTime[]") {end_time = $(this).val();}
                if ($(this).attr("name") == "status[]") {_status = $(this).val();}
                if ($(this).attr("name") == "desc[]") {_desc = $(this).val();}
            });

            var unit_rec = {
                'start_time':start_time,
                'end_time':end_time,
				'status':_status,
				'desc':_desc
            }
            time_units.push(unit_rec);

        });

        console.log("time_units->"+JSON.stringify(time_units));

        // בודק שכל ההתחלות יותר קטנות מהסיום
        $(time_units).each(function(kk,vv){
            if (parseInt(vv.start_time) > parseInt(vv.end_time)) {
                con = false;
            }

        });

        if (con) {

        $('#idan_time_units').val(JSON.stringify(time_units));

	$('.holder').show();
	//debugger;
	$.post("ajax_shiftPlus.php"
               ,$('#orderForm').serialize()
               ,function(res){
		if(res.success){
	        $('.holder').hide();
			swal.fire({icon: 'success',title: res.text}).then(function() {
                window.location.reload();
            });
		}
		else if(res.error){
            swal.fire({icon: 'error',title: res.text});
            $('.holder').hide();
		}

	},"JSON");


        } else {
            Swal.fire("נא לציין שעת סיום יותר גבוהה משעת התחלה");
        }
}

const _classes = {'-1':"break online-break",'0':"break",'1':""}
const _typeNames = {'-1':"הפסקה אונליין",'0':"הפסקה",'1':"משמרת"}
var svgclock = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>';
var svg_remove = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M14.59 8L12 10.59 9.41 8 8 9.41 10.59 12 8 14.59 9.41 16 12 13.41 14.59 16 16 14.59 13.41 12 16 9.41 14.59 8zM12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>';


function more_shifts(ele,_type) {
	//debugger;
	
	var _typeA = _type.toString();
	var _class = _classes[_typeA];
	var type_name = _typeNames[_typeA];

    var start_time = $(ele).data("start");
    var end_time = $(ele).data("end");

    $(ele).closest('#orderForm').find('.time_units_row').each(function(){

		$(this).find('input').each(function(){
			if ($(this).data("type") == "start") {start_time = $(this).val();}
			if ($(this).data("type") == "end") {start_time = $(this).val();}
		});
		
		start_time;
		//debugger
		end_time = (parseInt(start_time.replace(':',''))+100).toString()//.addAt(3, ':');
		end_time = end_time.substr(0,2)+":"+end_time.substr(2);
		console.log(end_time);


	});

    var html = "";
    html += "<div class='time_units_row the_res "+_class+"'>";
	html += "<input type='hidden' name='status[]' value= '"+_type+"'>";

    html += "<div class='the_remove_but' onclick=\"$(this).parent().remove();\">"+svg_remove+"</div>";

    html += "<div class='inputWrap half date time'>";
    html += "<input type='text' value='"+start_time+"' data-type='start' name='startTime[]' class='timePicks readonlymob' >";
    html += svgclock;
    html += "<label for='from'>תחילת "+ type_name +"</label>";
    html += "</div>";

    html += "<div class='inputWrap half date time'>";
    html += "<input type='text' value='"+end_time+"' data-type='end' name='endTime[]' class='timePicks readonlymob' >";
    html += svgclock;
    html += "<label for='from'>סוף "+ type_name +"</label>";
    html += "</div>";
	
	html += "<div class='inputWrap break_desc'>";
    html += "<input type='text' value='' placeholder='תאור "+type_name+"' data-type='desc' name='desc[]'  class='' >";
    html += "<label for='from'>תאור</label>";
    html += "</div>";

    html += "</div>";

    $('.the_shift_zonesss').append(html);

    /*$('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 30,
                        setMin: last_end
		});*/
	$('.timePicks:not(.added)').AnyPicker(
	{
		mode: "datetime",
		dateTimeFormat: "HH:mm",
		minValue: "00:00",
		maxValue: "23:59",
		intervals: 
		{
			h: 1,
			m: 5,
			s: 1
		}
	}).addClass('added').timetator();

	$('.timePicks').on('blur',function(){
		//debugger;
		setTimeout(function(){
			$('#ap-button-cancel').trigger('click');
		},1)
	});

	if($(window).width() < 992) {
		$('.readonlymob').prop('readonly', true);
	}


}




function dupbtn(){
	//debugger;
	var selectedday = $('#dupfrom').val();
	$('.the_shift_zonesss[data-id="'+selectedday+'"] input').each(function(){
		$(this).attr('value',$(this).val());
	})
	var shifts_to_copy = $('.the_shift_zonesss[data-id="'+selectedday+'"]').html();
	var cond = false;
	$('.the_shift_zonesss').each(function(){
		//debugger;
		if($('#dupcheck'+$(this).data('id')).is(":checked")){
			cond = true;
			$(this).html(shifts_to_copy);
			var setdate = $(this).data('date');
			$(this).find('input[data-type="start"]').attr('name','startTime['+setdate+'][]');
			$(this).find('input[data-type="end"]').attr('name','endTime['+setdate+'][]');
			$(this).find('input[data-type="status"]').attr('name','status['+setdate+'][]');
		}

	})
	if(cond){
		 /*$('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 30
                  
		});*/
		$('.timePicks:not(.added)').each(function(){$(this).AnyPicker(
		{
			mode: "datetime",
			dateTimeFormat: "HH:mm",
			minValue: "00:00",
			maxValue: "23:59",
			intervals: 
			{
				h: 1,
				m: 5,
				s: 1
			}
		}).addClass('added').timetator();
		});
		$('.timePicks').on('blur',function(){
			//debugger;
			setTimeout(function(){
				$('#ap-button-cancel').trigger('click');
			},1)
		});


		if($(window).width() < 992) {
			$('.readonlymob').prop('readonly', true);
		}

		swal.fire({icon: 'success',title: 'שוכפל בהצלחה'}).then(function() {
            $('#dupshifts').hide();   
        });
	}else{
		swal.fire({icon: 'error',title: 'לא נבחרו ימים לשכפול'})
	}
}


function more_shifts_week(ele,_type) {
    //debugger;
	var _typeA = _type.toString();
	var _class = _classes[_typeA];
	var type_name = _typeNames[_typeA];

    var start_time = $(ele).data("start");
    var end_time = $(ele).data("end");
    var datadate = $(ele).data("date");
	if($(ele).closest('.day-wrap').find('.time_units_row').length){
		$(ele).closest('.day-wrap').find('.time_units_row').each(function(){
			//debugger;
			
			
			$(this).find('input').each(function(){
				if ($(this).data("type") == "start") {start_time = $(this).val();}
				if ($(this).data("type") == "end") {start_time = $(this).val();}
			});
			
			start_time;
			//debugger
			end_time = (parseInt(start_time.replace(':',''))+100).toString()//.addAt(3, ':');
			end_time = end_time.substr(0,2)+":"+end_time.substr(2);
			console.log(end_time);
		});
	}

   					
    var html = "";
    html += "<div class='time_units_row the_res "+_class+"'>";
	html += "<input type='hidden' data-type='status' name='status["+datadate+"][]' value= '"+_type+"'>";
    
    html += "<div class='the_remove_but' onclick=\"$(this).parent().remove();\">"+svg_remove+"</div>";
    
    html += "<div class='inputWrap half date time'>";
    html += "<input type='text' value='"+start_time+"' data-type='start' name='startTime["+datadate+"][]' class='timePicks readonlymob' >";
    html += svgclock;
    html += "<label for='from'>תחילת "+type_name+"</label>";
    html += "</div>";
    
    html += "<div class='inputWrap half date time'>";
    html += "<input type='text' value='"+end_time+"' data-type='end' name='endTime["+datadate+"][]'  class='timePicks readonlymob' >";
    html += svgclock;
    html += "<label for='from'>סוף "+type_name+"</label>";
    html += "</div>";

	html += "<div class='inputWrap break_desc'>";
    html += "<input type='text' value='' placeholder='תאור "+type_name+"' data-type='desc' name='desc["+datadate+"][]'  class='' >";
    html += "<label for='from'>תאור</label>";
    html += "</div>";


    html += "</div>";
    
    $(ele).closest('.day-wrap').find('.the_shift_zonesss').append(html);
    
    /*$('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 30
                  
		});*/
	$('.timePicks:not(.added)').each(function(){$(this).AnyPicker(
	{
		mode: "datetime",
		dateTimeFormat: "HH:mm",
		minValue: "00:00",
		maxValue: "23:59",
		intervals: 
		{
			h: 1,
			m: 5,
			s: 1
		}
	}).addClass('added').timetator();
	});

	$('.timePicks').on('blur',function(){
		//debugger;
		setTimeout(function(){
			$('#ap-button-cancel').trigger('click');
		},1)
	});

	if($(window).width() < 992) {
		$('.readonlymob').prop('readonly', true);
	}

    
}

function insertShift_week(ele,week_step){
        //debugger;
        var con = true;
        
        var the_input_data = $('#orderForm').serializeArray();
        var time_units = [];
                
        $(ele).parent().parent().find('.the_shift_zonesss').each(function(){
			var daydate = $(this).data('id');
			$(this).find('.time_units_row').each(function(){
				//debugger;
            
				var start_time = "";
				var end_time = "";
				var _status = "";
				
				$(this).find('input').each(function(){
					//debugger;
					if ($(this).data("type") == "start") {start_time = $(this).val();}
					if ($(this).data("type") == "end") {end_time = $(this).val();}
					if ($(this).data("type") == "status") {_status = $(this).val();}
					if ($(this).data("type") == "desc") {_desc = $(this).val();}
					


				});
				if(parseInt(start_time.replace(':',''))>=parseInt(end_time.replace(':',''))){
					console.log(parseInt(start_time) +" - "+ parseInt(end_time))
					con = false;
				}
				
				var unit_rec = {
					'start_time':start_time,
					'end_time':end_time,
					'status':_status,
					'desc':_desc
				}
				//time_units[daydate].push(unit_rec);
				
			});
			var test = con;
		});
        
        console.log("time_units->"+JSON.stringify(time_units));
        
        // בודק שכל ההתחלות יותר קטנות מהסיום        
        
        
        if (con) {
        
        $('#idan_time_units').val(JSON.stringify(time_units));
        
	$('.holder').show();
	
	$.post("ajax_shiftPlus_week.php"
               ,$('#orderForm').serialize()
               ,function(res){
		if(res.success){
			//debugger;
	        $('.holder').hide();
			if(week_step){
				$('#create_orderPop').remove();
				gen_step_week(week_step);
			}else{
				swal.fire({icon: 'success',title: res.text}).then(function() {
					window.location.reload();
				});
			}
		}
		else if(res.error){
            swal.fire({icon: 'error',title: res.text});
            $('.holder').hide();
		}
	
	},"JSON");
        
        
        } else {
            Swal.fire("נא לציין שעת סיום יותר גבוהה משעת התחלה");
        }
}

function duplicate_shifts(ele){
	var day = $(ele).data("day");
	var dayname = $(ele).data("dayname");
	$('#dupfrom').val(day);
	$('.dupdayname').html(dayname);
	$('.dupdays input').prop( "checked", false );
	$('.dupdays  .checkwrap').removeClass("disabled");
	$('#dupday'+day).addClass("disabled");
	$('#dupshifts').show();
}

function dupbtn(){
	//debugger;
	var selectedday = $('#dupfrom').val();
	$('.the_shift_zonesss[data-id="'+selectedday+'"] input').each(function(){
		$(this).attr('value',$(this).val());
	})
	var shifts_to_copy = $('.the_shift_zonesss[data-id="'+selectedday+'"]').html();
	var cond = false;
	$('.the_shift_zonesss').each(function(){
		//debugger;
		if($('#dupcheck'+$(this).data('id')).is(":checked")){
			cond = true;
			$(this).html(shifts_to_copy);
			var setdate = $(this).data('date');
			$(this).find('input[data-type="start"]').attr('name','startTime['+setdate+'][]');
			$(this).find('input[data-type="end"]').attr('name','endTime['+setdate+'][]');
			$(this).find('input[data-type="status"]').attr('name','status['+setdate+'][]');
			$(this).find('input[data-type="desc"]').attr('name','desc['+setdate+'][]');
		}

	})
	if(cond){
		 /*$('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 30
                  
		});*/
		$('.timePicks:not(.added)').each(function(){$(this).AnyPicker(
		{
			mode: "datetime",
			dateTimeFormat: "HH:mm",
			minValue: "00:00",
			maxValue: "23:59",
			intervals: 
			{
				h: 1,
				m: 5,
				s: 1
			}
		}).addClass('added').timetator();
		});
		$('.timePicks').on('blur',function(){
			//debugger;
			setTimeout(function(){
				$('#ap-button-cancel').trigger('click');
			},1)
		});


		if($(window).width() < 992) {
			$('.readonlymob').prop('readonly', true);
		}

		swal.fire({icon: 'success',title: 'שוכפל בהצלחה'}).then(function() {
            $('#dupshifts').hide();   
        });
	}else{
		swal.fire({icon: 'error',title: 'לא נבחרו ימים לשכפול'})
	}
}





