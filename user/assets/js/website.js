Date.prototype.add || (Date.prototype.add = function(a, u){
    var d = new Date(this.valueOf());
    switch(u){
        case 'y': case 'Y': d.setFullYear(d.getFullYear() + parseInt(a)); break;
        case 'm': case 'M': d.setMonth(d.getMonth() + parseInt(a)); break;
        default: d.setDate(d.getDate() + parseInt(a));
    }
    return d;
});

Date.dayLength = 1000 * 60 * 60 * 24;
Date.prototype.diff || (Date.prototype.diff = function(d){
    return Math.round(Math.abs(this - d) / Date.dayLength);
});

Date.prototype.toDB || (Date.prototype.toDB = function(){
    return [this.getFullYear(), ('0' + (this.getMonth() + 1)).substr(-2), ('0' + this.getDate()).substr(-2)].join('-');
});

Number.prototype.round || (Number.prototype.round = function (len) {
    var m = (len >= 0) ? Math.pow(10, Math.min(len, 8)) : 1;
    return Math.round(this * m) / m;
});

String.prototype.flipDate || (String.prototype.flipDate = function(nd) {
    var d = this.match(/^\d+(\D)\d+\1\d{2,4}$/);
    return d ? this.split(d[1]).reverse().join(nd === undefined ? d[1] : nd) : this;
});

var spaOrderChanged = false;

function copyToClipboard(inp) {
	//debugger;
    inp.select();
    document.execCommand("copy");
}

function getFlames(dates) {
    console.log('getFlames initialized');
    
    let _currmonth = String(dates.selectedMonth+1).padStart(2, '0');

    let _firstdate = dates.selectedYear+'-'+(_currmonth)+'-01';


    setTimeout(function() {
        $.post('ajax_markedDates.php', {fromDate: _firstdate}, function(res) {
            let _currsite = $('#orderSite').length?$('#orderSite').val():($('.sites-select select').length?$('.sites-select select').val():$('.rank').attr('data-siteid'));
   

            let _datesarray = res.dates[_currsite];

            $.each(_datesarray, function(key, val) {

                let _date = new Date(val);
                
                $('.ui-datepicker-calendar tbody td').each(function() {
                if($(this).is(':visible')) {
                    let _fulldate = 0;
                    if($(this).attr('data-year')) {
                        _fulldate = $(this).attr('data-year')+'-'+(String((parseInt($(this).attr('data-month'))+1)).padStart(2, '0'))+'-'+(String($(this).find('a').text()).padStart(2, '0'));
                        if(_fulldate == val) {
                            $(this).addClass('onFire');
                        }
                    }
                }
                })

            })
        });
    }, 100)
}

var checkForInternetConnection = null;



function checkInternetConnectivity(){
    $.get("isConnected.php",function(res){
        if(res == "1") {
            clearInterval(checkForInternetConnection);
            $(".no-internet").remove();
            $(".holder").hide();
            checkForInternetConnection = null;

        }
    });
}

$(function() {

    $( document ).ajaxError(function( event, jqxhr, settings, thrownError ) {
        console.log("********************",thrownError,jqxhr);
        if(jqxhr.readyState == 0 && checkForInternetConnection == null) {
            $("<div class='no-internet'><div>החיבור שלך לאינטרנט לא תקין , ממתין לחיבור לאינטרנט</div></div>").appendTo($("body"));
            checkForInternetConnection = setInterval(checkInternetConnectivity,2000);

        }
    });

	$(".order .whatsapp.call").click(function(){
		var self = this;
		var phone = $(self).data('phone');
		var name = $(self).closest('.order').find('.customerName').text();
		var siteName = $(".user-name .rank").text();
		var link = encodeURI("https://bizonline.co.il/signature.php?guid="+$(self).closest('.order').find('.guid').val());
		if($(self).closest('.order').hasClass("approved") || $(self).closest('.order').hasClass("canceled")){

			var body = name + " שלום, מצורף קישור להזמנתך ב " +  siteName + " לצפיה בהזמנה " + link;
			window.open('///wa.me/972' + phone.trim() + "?text="+body, '_blank');
			window.event.cancelBubble = true;
		}else{
			name = name + " שלום,";
			var body = name+" על מנת לאשר את הזמנתך ב"+ siteName +" יש ללחוץ על הקישור הנ\"ל "+link;
			window.open('///wa.me/972' + phone.trim() +"?text="+body, '_blank');
			window.event.cancelBubble = true;
		}
	});


    $('.menuButton').click(function() {
        $('body').toggleClass('menuOpen')
    })

    $('.tfusa .rooms .row').on('click', function(e) {
		console.log('click not shifts');
        var orderDate = $(this).data("date");
        var uid = $(this).data("uid");

        var newOrder = new addMonthOrder({
            orderDate: orderDate,
            roomID: uid,
            allDay: 1
        });
        newOrder.addOccDay(this);

    });



    $('.rooms:not(.spa) .order .all').on('click', function(e) {
        var orderID = $(this).closest('.order').data("orderid");
        var orderIDBySite = $(this).closest('.order').data("orderidbysite");
        e.stopPropagation();
        $("#orderForm-orderID").val(orderID);
        openOrderFrom({"orderID":orderID});
		$('.create_order .mainTitle').text("הזמנה מספר "+orderIDBySite);
        $('.create_order').fadeIn('fast');
	});

	$(document).on('click', 'input[name="paymethod"]', function() {
		if(parseInt($(this).val()) == 2){
			$('.payMethod').addClass('safe');
			if($(this).hasClass('j5')){$('.payMethod').addClass('j5')}
		}
		else $('.payMethod').removeClass('safe j5');
	});

	$('.item.order .orderPrice.new').on('click', function(e) {
	        var payOrder = $(this).data('pay');
			var orderID = $(this).closest('.order').data("orderid");
			var orderIDBySite = $(this).closest('.order').data("orderidbysite");
			if($(this).closest(".order").hasClass('canceled')){
				$('.statusBtn').addClass("del");
			}
			$("#orderForm-orderID").val(orderID);
			openPayOrder({"orderID":payOrder || orderID});
			$('.pay_order .mainTitle').text("ביצוע תשלום");
			$('.pay_order').fadeIn('fast');
			$('.pay_order .signOpt').show();
	});

    $('.item.order:not(".is-lead") .f').on('click', function(e) {
			var target = $(event.target);
			if(target.hasClass('c_status')){
				change_c_s(target);
				return;
			}
			if(target.hasClass('c_slots')){
				return;
			}
			var co = $(this).closest('.order'), orderID = co.data("orderid"), isSpa = co.hasClass('isSpa');
			var orderIDBySite = $(this).closest('.order').data("orderidbysite");
			if($(this).closest(".order").hasClass('canceled')){
				$('.statusBtn').addClass("del");
			}
			$("#orderForm-orderID").val(orderID);
            window.openFoo ? window.openFoo.call(window, {"orderID":orderID}) : (isSpa ? openSpaFrom({"orderID":orderID}) : openOrderFrom({"orderID":orderID}));
			$('.create_order .mainTitle').text("הזמנה מספר "+orderIDBySite);
			$('.create_order').fadeIn('fast');
			$('.create_order .signOpt').show();

    });

    $('.signBtn').click(function() {


    });



    $('.tfusa .rooms .row').on('long-press', function() {
		console.log('long press not shifts');
		openNewOrder(this);
	});



	$('input').focus(function() {
		$(this).addClass('not-empty');
	});

	$('input').focusout(function() {
        if($(this).val().length)$(this).addClass('not-empty');
        else $(this).removeClass('not-empty');
	})

	$('input').keyup(function() {
        if($(this).val().length)$(this).addClass('not-empty');
        else $(this).removeClass('not-empty');
    })



    $('.searchFrom').datetimepicker({
        format: 'd/m/Y',
        timepicker: false

    });
    $('.searchTo').datetimepicker({
        format: 'd/m/Y',
		  onShow:function( ct ){
		   this.setOptions({
			minDate:$('.searchFrom').val()?$('.searchFrom').val().split("/").reverse().join("-"):false
		   })
		  },
        timepicker: false
    });

    $.datetimepicker.setLocale('he');

    $('.last-orders .item.order')
        .find('.deleteOrder').on('click', function(){
            var order = $(this).closest('.item.order'), id = order.data('orderid');
            if (order.hasClass('canceled'))
                orderDelete(id);
            else
                orderCancel(id);
        }).end()
        .find('.restore').on('click', function(){
            var id = $(this).closest('.item.order').data('orderid');
            orderRestore(id);
        }).end()
        .find('.createOrder').on('click', function(){
            var order = $(this).closest('.item.order'), orderID = order.data('orderid'), orderIDBySite = order.data("orderidbysite");

            openOrderFrom({orderID:orderID, as_order: 1});
            $("#orderForm-orderID").val(orderID);
            $('.create_order .mainTitle').text("הזמנה מספר " + orderIDBySite);
            $('.create_order').fadeIn('fast').find('.signOpt').show();
        });

    $('.cancelOrderBtn').on('click', function(){
        var orderID = $("#orderForm-orderID").val();
        orderCancel(orderID);
    });

    $('.delOrderBtn').on('click', function(){
        var orderID = $("#orderForm-orderID").val();
        orderDelete(orderID);
    });
});

function warnAjax(orderID, action, title, button){
    Swal.fire({
        title: title,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'לא',
        confirmButtonText: button
    }).then(function(result){
        if (result.value) {
            $.post("ajax_orderPlus.php", {orderID: orderID, action: action}, function(res) {
                if (res.status) {
                    swal.fire({
                        icon: 'success',
                        title: res.text
                    }).then(function() {
                        window.location.reload();
                    });
                } else {
                    swal.fire({
                        icon: 'error',
                        title: 'שגיאה',
                        text: res.error || res._txt
                    });
                }
            }, "json");
        }
    });
}

function orderCancel(orderID){
    warnAjax(orderID, 'cancel', 'האם אתה בטוח שברצונך לבטל את ההזמנה?', 'כן, בטל');
}

function orderDelete(orderID){
    warnAjax(orderID, 'delete', 'האם אתה בטוח שברצונך למחוק את ההזמנה?', 'כן, מחק');
}

function orderRestore(orderID){
    warnAjax(orderID, 'restore', 'האם אתה בטוח שברצונך לשחזר את ההזמנה?', 'כן אני בטוח');
}

function getDayFormat(date){
        var dd = date.getDate();
        var mm = date.getMonth() + 1; //January is 0!

        var yyyy = date.getFullYear();
        if (dd < 10) {
            dd = '0' + dd;
        }
        if (mm < 10) {
            mm = '0' + mm;
        }
       return (dd + '/' + mm + '/' + yyyy);

}

function gotowhatsapp(num) {
    window.open('///wa.me/+972' + num, '_blank');
    window.event.cancelBubble = true;
}

function closeOrderForm(reload) {
	//debugger;
	$('#create_orderPop').remove();
	//$('.timePick').datetimepicker('destroy');
	$('.datePick.fromDate').datetimepicker('destroy');
	$('.datePick.endDate').datetimepicker('destroy');
	$('.xdsoft_datetimepicker').remove();

	if(reload){
		location.reload();
	}

}

function closeSpaForm() {
	$('#create_orderSpa').remove();
}

function closePayOrder(reload) {
    if (reload){
        window.location.href = window.location.href + '#or' + reload;
        window.location.reload();
    } else {
        $('#pay_orderPop').remove();
        //$('.timePick').datetimepicker('destroy');
        $('.datePick.fromDate').datetimepicker('destroy');
        $('.datePick.endDate').datetimepicker('destroy');
        $('.xdsoft_datetimepicker').remove();
    }

}

function showForm(orderID) {
    $.post("ajax_orderPlus.php", {
        "orderID": orderID,
        "action": "info"
    }, function(res) {


    }, "JSON");



}

function openNewOrder(elem){
	if($(".l-side > .order").length){
		swal.fire({icon: 'error',title: 'לא ניתן לפתוח 2 הזמנות במקביל'});

	}else{
		var unitID = $(elem).data("uid");
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

		var data = {unitID:unitID,startDate:date,endDate:tomorrow,ptype:ptype};
		openOrderFrom(data);


		window.event.cancelBubble = true;
	}


}

function openNewSpa(elem){
	if($(".l-side > .order").length){
		swal.fire({icon: 'error',title: 'לא ניתן לפתוח 2 הזמנות במקביל'});

	}else{
		var unitID = $(elem).data("uid");
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

		var data = {unitID:unitID,startDate:date,endDate:tomorrow,ptype:ptype};
		//debugger;
		openSpaFrom(data);
		window.event.cancelBubble = true;
	}
}

function openNewSpaSingle(data)
{
    closeOrderForm();

    return openSpaFrom({orderID:data.orderID}).then(function(){
        openSpaSingle(0, data.orderID);
    });
}




$('.month .next').click(function() {
	scrollTable("-");
});

$('.month .prev').click(function() {
	scrollTable("+");
});

function scrollTable(dirc){
	var tblWidth = $('.days-table').width() - $('.r-side').width();
	if($(window).width() < 1000) tblWidth = $(window).width() - 90;
	$('.l-side').animate({
    scrollLeft: dirc+"="+tblWidth+"px"
  }, "slow");
}


function scrollTableNew(){
	if($('#days-table').length){
		
		var _calendar = $('#a_tfusa , #n_tfusa')
		var _addpx = ($('#n_tfusa').length)? 2 : 0
		var _cell = $('#days-table .l-side .rooms .row').first();
		var _space = parseInt(localStorage.getItem('flipped'))? _cell.height() : _cell.width();		
		var _cellSpace =  _space + _addpx;		
	
		if(_calendar.hasClass('dayly')){
			scrollmonth = $('.day:not(.offHours)').prev('.day.offHours').index();
		}else if(!($('#n_tfusa').length)){
			scrollmonth = scrollmonth - 1;
		}

		if(typeof scrollmonth !== 'undefined'){
			var _scroll = String(scrollmonth * _cellSpace) + "px";
			if(_calendar.hasClass('flipped')) {
				$('#days-table').animate({scrollTop:_scroll}, 50);
			}else{
				$('.l-side').animate({scrollLeft: "-="+_scroll+"px"}, 50);
			}

		}
	}
}


function openDoc(docPath){
	$(".picPop").fadeIn("fast");
	$("#reviewDoc").attr("src","/gallery/"+docPath);
	//alert(docPath);
}

function insertOrder(){
	$('.holder').show();
	$.post("ajax_orderPlus.php",$('#orderForm').serialize(),function(res){
		if(res.success){
	        $('.holder').hide();
			swal.fire({icon: 'success',title: res.success}).then(function() {
                res.orderID ? openPayOrder({orderID:res.orderID}) : window.location.reload();
            });
		}
		else if(res.error){
            swal.fire({icon: 'error',title: res.error});
            $('.holder').hide();
		}

	},"JSON");
}

function hideSpaPrices(orderID,hidePrices){
	$.post("ajax_spaPlus.php",  {action:'hidePrices', orderID:orderID, hidePrices:hidePrices},function(res){
        if(res.error){
			Swal.fire({icon:'error', title:res.error})
		}
	});
}

function insertOrderSpa(callback){
    var PHONE_REGEXP = /^(\d(\W*)){9,12}$/;
    var EMAIL_REGEXP = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if(PHONE_REGEXP.test($('#orderForm input[name="phone"]').val()) == false && $('#orderForm input[name="phone"]').val()!=='') {
        Swal.fire({icon:'error', text:'טלפון אינו תקין'})
    } else if(EMAIL_REGEXP.test($('#orderForm input[name="email"]').val()) == false && $('#orderForm input[name="email"]').val()!=='') {
        Swal.fire({icon:'error', text:'אימייל אינו תקין'})
    }  else {
        $('.holder').show();
        $.post("ajax_spaPlus.php",$('#orderForm').serialize(),function(res){
            if(res.success){
                $('.holder').hide();
                callback ? callback(res) : swal.fire({icon: 'success',title: res.success}).then(function() {
                    if (res.orderID){
                        closeOrderForm();
                        spaOrderChanged = 1;
                        openSpaFrom({orderID:res.orderID});
                        //openPayOrder({orderID:res.orderID});
                    } else window.location.reload();
                });
            }
            else if(res.error){
                swal.fire({icon: 'error',title: res.error});
                $('.holder').hide();
            }

        },"JSON");
    }
}

function insertTreatmentNew(prm){
    var p = btoa($('#orderForm').serialize());
    $.when(openSpaSingle(0, p, prm)).done(function(){		
		//debugger;
		if($('#sourceID').prop('required',true)){
			setTimeout(function(){
				$('#sourceID_inner').val($('#sourceID').val());
				$('#sourceID_inner').change();
			},300);

		}
		if($('#clientAddress').prop('required',true)){
			setTimeout(function(){
				$('#create_orderSpa input[name="settlementID"]').val($('#orgAdress input[name="settlementID"]').val())
				$('#create_orderSpa .settlementName').html($('#orgAdress .settlementName').html())
				$('#create_orderSpa input[name="clientCity"]').val($('#orgAdress input[name="clientCity"]').val())
				$('#clientAddress_inner').val($('#clientAddress').val());
				$('#clientAddress_inner').change();
			},300);

		}
    });
}


function saveSpaSingle(callback){
    $('.holder').show();
    $.post("ajax_spaSingle.php", 'act=saveSpaSingle&' + $('#spaOrderForm').serialize(),function(res){
        if(res.success){
            $('.holder').hide();
            swal.fire({icon: 'success',title: res.success}).then(function() {
                //callback ? callback(res) : window.location.reload();
                closeOrderForm();
				spaOrderChanged = 1;
                openSpaFrom({orderID:res.parent});
            });
        }
        else if(res.error){
            swal.fire({icon: 'error',title: res.error});
            $('.holder').hide();
        }

    },"JSON");
}


function openOrderFrom(data){
	$.post('ajax_orderFrom.php',{data:data},function(res){
		$("#orderLside").append(res);
	}).done(function(){

		$('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 30
		});

        $('.datePick.fromDate').datetimepicker({
			format: 'd/m/Y',
			timepicker: false,
			// minDate:data.startDate,
			onSelectDate:function(ct){

			var tomorrow = new Date(ct);
			tomorrow.setDate(tomorrow.getDate() + 1);
			tomorrow = getDayFormat(tomorrow);
				$('.datePick.endDate').val(tomorrow);
			}
		});

		$('.datePick.endDate').datetimepicker({
			format: 'd/m/Y',
			  onShow:function( ct ){
			   this.setOptions({
				minDate:$('.datePick.fromDate').val()?$('.datePick.fromDate').val().split("/").reverse().join("-"):false
			   })
			  },
			timepicker: false
		});


		if($('.roomSelectWrap').length){//delete duplicate code
			$('.roomSelectWrap').each(function(){

				var self = $(this);
				var maxGuest = self.data('maxguests');
				var adults = self.data('adults');
				var kids = self.data('kids');
				if(maxGuest && !adults && !kids){
					var i = 1;
					var adultsNode = self.find(".adults_room");
					var kidsNode = self.find(".kids_room");
					/*
					for(i;i<=maxGuest;i++){
						adultsNode.append($("<option></option>").attr("value",i).text(i));
					}*/
					adultsNode.on("change select",function(){
						var select = this.value;
						i=0;
						kidsNode.find('option').remove();
						for(i;i<=maxGuest-select;i++){
							kidsNode.append($("<option></option>").attr("value",i).text(i));
						}
					})

				}

			})
                .find('input.unit-id').off('.calc').on('click.calc', calcSum).on('click.calc', prePay).end()
                .find('.payment-inp').off('keyup blur').on('keyup blur', calcSum).end()
                .find('.prePayment-inp').off('keyup blur').on('keyup blur', prePay);
		}

        $('#price_to_pay').add('#prePay').off('.calc').on('keyup.calc blur.calc', leftToPay);

        leftToPay();
	});
}

function openSpaFrom(data){
	//debugger;
    $('#create_orderPop').remove();

	return $.post('ajax_spaFrom.php',{data:data}).then(function(res){

		$("#orderLside").append(res);

        $('a.a_sms').click(function(e) {
            let _phone = $(this).attr('data-phone');
            let _msg = $(this).attr('data-msg');
            let _sid = $(this).attr('data-sid');
            
            if($(window).width() > 992) {
                e.preventDefault();
                $.post('ajax_sendSMS.php', {phone:_phone, msg:_msg, sid:_sid}, function(res) {
                    if(res.error)
                        return Swal.fire({icon: 'error',title: "יש להזין טלפון תקין"});
                    Swal.fire({icon:'success', text:res.msg});
                })
            } else {
                
            }
        })
		$('.timePicks').AnyPicker(
            {
                mode: "datetime",
                dateTimeFormat: "HH:mm",
                minValue: "00:00",
                maxValue: "23:59"
            });
		// $('.timePick').datetimepicker({
		// 	datepicker: false,
		// 	format: 'H:i',
		// 	step: 5
		// });

        $('.timePicks').on('blur',function(){
            //debugger;
            setTimeout(function(){
                $('#ap-button-cancel').trigger('click');
            },1)
        });

        if($(window).width() < 992) {
            $('.readonlymob').prop('readonly', true);
        }

		$('.datePick.fromDate').datepicker({
			format: 'd/m/Y',
			// minDate:data.startDate,

            afterShow: function (dates) {
                getFlames(dates);
            },
			onSelect:function(dateText, ct){
                //$('.datePick.endDate').val(ct);
                var dateFormat = ct.selectedYear+'-'+(ct.selectedMonth+1)+'-'+ct.selectedDay;
                var tomorrow = new Date(dateFormat);
                tomorrow.setDate(tomorrow.getDate() + 1);
                tomorrow = getDayFormat(tomorrow);
                $('.datePick.endDate').val(tomorrow);

                let _currsite = $('#orderSite').val() || $('.sites-select select').val() || $('.rank').attr('data-siteid') || 0;

                $.post('ajax_markedDates.php', {act:'extraPrices', date:dateText, ssid:_currsite, orid:$('#orderForm-orderID').val() || 0}).then(function(result){
                    if (result.error)
                        return alert(result.error);
                    if (!result.prices)
                        return;

                    let cont = $('.treatments_patients_addings.siteID' + result.sid);
                    $.each(result.prices, function(id, price){
                        let inp = $('#extra' + id);

                        if ($.isPlainObject(price))
                            inp.siblings('div.l').find('select.count').find('option').each(function(){
                                if (price[this.value] !== undefined)
                                    $(this).data('price', price[this.value]);
                            }).end().trigger(inp.prop('checked') ? 'change' : 'nothing');
                        else
                            inp.siblings('div.l').find('.unit_price').text('₪' + price).end().find('input.count').data('price', price).trigger(inp.prop('checked') ? 'change' : 'nothing');

                        /*if (inp.prop('checked'))
                            inp.trigger('click');*/
                    });
                });
			}
		});

		$('.datePick.endDate').datepicker({
			format: 'd/m/Y',
            
            afterShow: function (dates) {
                getFlames(dates);
            },
            // minDate:$('.datePick.fromDate').val()?$('.datePick.fromDate').val():false
		});


		if($('.roomSelectWrap').length){//delete duplicate code
			$('.roomSelectWrap').each(function(){

				var self = $(this);
				var maxGuest = self.data('maxguests');
				var adults = self.data('adults');
				var kids = self.data('kids');
				if(maxGuest && !adults && !kids){
					var i = 1;
					var adultsNode = self.find(".adults_room");
					var kidsNode = self.find(".kids_room");
					/*
					for(i;i<=maxGuest;i++){
						adultsNode.append($("<option></option>").attr("value",i).text(i));
					}*/
					adultsNode.on("change select",function(){
						var select = this.value;
						i=0;
						kidsNode.find('option').remove();
						for(i;i<=maxGuest-select;i++){
							kidsNode.append($("<option></option>").attr("value",i).text(i));
						}
					})

				}

			})
                .find('input.unit-id').off('.calc').on('click.calc', calcSum).on('click.calc', prePay).end()
                .find('.payment-inp').off('keyup blur').on('keyup blur', calcSum).end()
                .find('.prePayment-inp').off('keyup blur').on('keyup blur', prePay);
		}

        $('#price_to_pay').add('#prePay').add('#price_discount').off('.calc').on('keyup.calc blur.calc', function(){
            calcDiscount(this);
            leftToPay();
        });
        leftToPay();

        $('.treatments_patients_addings .adding .l input.count').on('change input', function(){
            $(this).siblings('.price').html('₪' + this.value * $(this).data('price'));
            showFoldedExtraSums();
            calcSum();
        });

		$('.treatments_patients_addings .adding input.extra').on('click', function(){
		    if (this.checked)
		        $(this.parentNode).find('.l input.count').val(1).trigger('change').end().find('.l select.count').trigger('change');
            else {
                showFoldedExtraSums();
                calcSum();
            }
        });

        if($('#open_treatmentID').val()){
            openSpaSingle($('#open_treatmentID').val())
        }

		$('#spa_orders').find('.spaorder .duplicate').on('click', function(){
            var tmp = new Duplicator(this.parentNode);
        });
	});

}


function openSubscription(data)
{
    //debugger;
    $('#create_subPop').add('#subsAddTreats').add('.pop-pay').remove();

    return $.post('ajax_subscription.php', $.extend({act:'loadSub'}, $.isPlainObject(data) ? data : {})).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status))
            return Swal.fire({icon: 'error',title: res.error || res._txt || 'Unknown error!'});

        let pop = $(res.html);

        $("#orderLside").append(pop);

        if($(window).width() < 992) {
            $('.readonlymob').prop('readonly', true);
        }

/*        $('.timePicks').AnyPicker(
            {
                mode: "datetime",
                dateTimeFormat: "HH:mm",
                minValue: "00:00",
                maxValue: "23:59"
            });
        // $('.timePick').datetimepicker({
        // 	datepicker: false,
        // 	format: 'H:i',
        // 	step: 5
        // });

        $('.timePicks').on('blur',function(){
            //debugger;
            setTimeout(function(){
                $('#ap-button-cancel').trigger('click');
            },1)
        });

        $('.datePick.fromDate').datepicker({
            format: 'd/m/Y',
            // minDate:data.startDate,

            afterShow: function (dates) {
                getFlames(dates);
            },
            onSelect:function(dateText, ct){
                //$('.datePick.endDate').val(ct);
                var dateFormat = ct.selectedYear+'-'+(ct.selectedMonth+1)+'-'+ct.selectedDay;
                var tomorrow = new Date(dateFormat);
                tomorrow.setDate(tomorrow.getDate() + 1);
                tomorrow = getDayFormat(tomorrow);
                $('.datePick.endDate').val(tomorrow);
            }
        });

        $('.datePick.endDate').datepicker({
            format: 'd/m/Y',

            afterShow: function (dates) {
                getFlames(dates);
            },
            // minDate:$('.datePick.fromDate').val()?$('.datePick.fromDate').val():false
        });


        if($('.roomSelectWrap').length){//delete duplicate code
            $('.roomSelectWrap').each(function(){

                var self = $(this);
                var maxGuest = self.data('maxguests');
                var adults = self.data('adults');
                var kids = self.data('kids');
                if(maxGuest && !adults && !kids){
                    var i = 1;
                    var adultsNode = self.find(".adults_room");
                    var kidsNode = self.find(".kids_room");

                    adultsNode.on("change select",function(){
                        var select = this.value;
                        i=0;
                        kidsNode.find('option').remove();
                        for(i;i<=maxGuest-select;i++){
                            kidsNode.append($("<option></option>").attr("value",i).text(i));
                        }
                    })

                }

            })
                .find('input.unit-id').off('.calc').on('click.calc', calcSum).on('click.calc', prePay).end()
                .find('.payment-inp').off('keyup blur').on('keyup blur', calcSum).end()
                .find('.prePayment-inp').off('keyup blur').on('keyup blur', prePay);
        }*/

        /*$('#price_to_pay').add('#prePay').add('#price_discount').off('.calc').on('keyup.calc blur.calc', function(){
            calcDiscount(this);
            leftToPay();
        });
        leftToPay();

        $('.treatments_patients_addings .adding .l input.count').on('change input', function(){
            $(this).siblings('.price').html('₪' + this.value * $(this).data('price'));
            calcSum();
        });

        $('.treatments_patients_addings .adding input.extra').on('click', function(){
            if (this.checked)
                $(this.parentNode).find('.l input.count').val(1).trigger('change').end().find('.l select.count').trigger('change');
            else
                calcSum();
        });

        if($('#open_treatmentID').val()){
            openSpaSingle($('#open_treatmentID').val())
        }

        $('#spa_orders').find('.spaorder .duplicate').on('click', function(){
            var tmp = new Duplicator(this.parentNode);
        });*/
    });

}


function saveSubscription(f, callback){
    var PHONE_REGEXP = /^(\d(\W*)){9,12}$/;
    var EMAIL_REGEXP = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;

    let form = $(f), phone = form.find('input[name="phone"]'), email = form.find('input[name="email"]');

    if (phone.val() !== '' && PHONE_REGEXP.test(phone.val()) == false)
        Swal.fire({icon:'error', text:'טלפון אינו תקין'});
    else if (email.val() !== '' && EMAIL_REGEXP.test(email.val()) == false)
        Swal.fire({icon:'error', text:'אימייל אינו תקין'});
    else {
        $('.holder').show();
        $.post("ajax_subscription.php", 'act=saveSubs&' + form.serialize(), function(res){
            $('.holder').hide();

            if (!res || res.status === undefined || parseInt(res.status))
                return Swal.fire({icon: 'error',title: res.error || res._txt || 'Unknown error!'});

            callback ? callback(res) : swal.fire({icon: 'success',title: res.success}).then(function() {
                if (res.subID){
                    openSubscription({subID:res.subID});
                    spaOrderChanged = 1;
                }
                else
                    window.location.reload();
            });
        });
    }
}


const HIDDEN_BY_TREAT = 1;
const HIDDEN_BY_DATE = 2;
const HIDDEN_BY_SEX = 4;
const HIDDEN_BY_OSEX = 8;       // own gender

function openSpaSingle(id, p, prm){
	//debugger;
	$.post('ajax_spaSingle.php',$.extend({act:'openSpaSingle', id:id, parent:p||''}, prm || {}),function(res){
		var cont = $("#create_orderPop").find(".container").append(res.html), dropCurrent = false;
		cont = $('#create_orderSpa');
        cont.find('.timePicks').AnyPicker(
            {
                mode: "datetime",
                dateTimeFormat: "HH:mm",
                minValue: "00:00",
                maxValue: "23:59",
				onSetOutput  : checkchangetime
            });
		$(function () {
			$('.timePicks').timetator();
		});


        $('a.a_sms').click(function(e) {
            let _phone = $(this).attr('data-phone');
            let _msg = $(this).attr('data-msg');
            let _sid = $(this).attr('data-sid');

            if($(window).width() > 992) {
                e.preventDefault();
                $.post('ajax_sendSMS.php', {phone:_phone, msg:_msg, sid:_sid}, function(res) {
                    if(res.error)
                        return Swal.fire({icon: 'error',title: "יש להזין טלפון תקין"});
                    Swal.fire({icon:'success', text:res.msg});
                })
            } else {
                
            }
        })

        
        if($(window).width() < 992) {
            $('.readonlymob').prop('readonly', true);
        }

		function checkchangetime(){
			//debugger;
			if($('#spa_frot').val() !=timeval){
                filterMastersRooms();
				timeval = $('#spa_frot').val();

			}
		}

        /*
		// OLD FUNCTION
        cont.find('.timePick').datetimepicker({
            datepicker: false,
            format: 'H:i',
            step: 5,
            onSelectTime: filterMastershifts
        });*/

		cont.find('#spa_frot').on('blur',function(){
			//debugger;
			setTimeout(function(){
				$('#ap-button-cancel').trigger('click');
			},1)
        });

        var timeinput = cont.find('#spa_frot');
        var timeval = timeinput.val();
        timeinput.on("change blur",function(e){
            checkchangetime();
        });

        cont.find('.datePick.fromDate').datepicker({
            format: 'd/m/Y',
            // minDate: 0,
            
            afterShow: function (dates) {
                getFlames(dates);
            },
            onSelect: function(dateText, ct){
                var dateFormat = ct.selectedYear+'-'+(ct.selectedMonth+1)+'-'+ct.selectedDay;
                var date = new Date(dateFormat).toDB();

                $('#therapist').find('option').each(function(){
                    var data = $(this).data();
                    $(this).data('hide', (data.locked && data.locked.indexOf(date) >= 0) ? (data.hide || 0) | HIDDEN_BY_DATE : (data.hide || 0) & ~HIDDEN_BY_DATE);
                });

                filterMastersRooms();
            }
        });

        cont.find('input[name="malefemale"], input[name="tmalefemale"]').on('click', function(){
            filterMastersRooms();
        });

        cont.find('select[name="duration"]').on('change', function(){
            filterMastersRooms();
        });

        cont.find('select[name="treatmentID"]').on('change', function(){
            var times = $(this.options[this.selectedIndex]).data('prices') || ['- - - -'], sel = $('#duration').get(0), val = parseInt(sel.value), opt = sel.options;

            opt.length = 0;
            times.forEach(function(t, i){
                opt[opt.length] = new Option((t == '- - - -') ? t : t + ' דקות', t, false, t == val);
            });

            filterMastersRooms();
            });
	});
}

function filterMastershifts(){
    var d = $('#spa_from'), t = $('#spa_frot'), sid = $('#spa_sid');
    if (d.val() && t.val())
        $.post('ajax_spaSingle.php', {act:'shifts', sid:sid.val(), dt:d.val().flipDate('-'), tm:t.val()}).then(function(res){
            var a = res.active || {}, sel = $('#therapist'), si = sel.val();
            var aroom = res.active_rooms || {}, selroom = $('#spa_roomID'), siroom = selroom.val();

            sel.find('option').each(function(){
                if (parseInt($(this).data('weight'))){
                    a[this.value] ? $(this).removeClass('inactive').data('weight', -1) : $(this).addClass('inactive').data('weight', 2);
				}else{
					a[this.value] ? $(this).removeClass('inactive') : $(this).addClass('inactive');
				}
            }).sort(function(a, b){
                return ($(a).data('weight') -  $(b).data('weight')) || a.text.localeCompare(b.text);
            }).appendTo(sel);

			/********************************************/

			selroom.find('option').each(function(){
                if (parseInt($(this).data('weight'))){
                    //a[this.value] ? $(this).removeClass('inactive').data('weight', -1) : $(this).addClass('inactive').data('weight', 2);
				}else{
					aroom[this.value] ? $(this).removeClass('inactive') : $(this).addClass('inactive');
				}
            }).sort(function(aroom, b){
                return ($(aroom).data('weight') -  $(b).data('weight')) || aroom.text.localeCompare(b.text);
            }).appendTo(selroom);

			/*******************************************/

            sel.val(si)
            selroom.val(siroom)

        });
}

function filterMastersRooms(){
	
    var form = $('#spaOrderForm'), m = $('#therapist'), r = $('#spa_roomID');
    if (parseInt(m.val()) || parseInt(r.val())){
        $.post('ajax_spaSingle.php', 'act=shifts&' + form.serialize()).then(function(res){
            var errorText= "";
			if (res.master && res.master != 'ok'){
                if (res.master == 'no-shift'){					
					//debugger;
					errorText += 'למטפל הנבחר אין משמרת בשעת הטיפול';                    
				} else {
                    errorText += 'המטפל שבחרת הוסר - ' + res.master;					
                    m.val(0);
                    $('#therapistName').val('--- בחר ---');
                }
            }
			

            if (res.room && res.room != 'ok'){
				if(errorText)
					errorText += "<br>";
				errorText += 'החדר הוסר - '+ res.room	
                r.val(0);
                $('#spa_roomIDName').val('--- בחר ---');
            }

			if(errorText)
				swal.fire({icon:'warning', title:'שימו לב!', html:errorText});

        });
    }
}

var ssd_new = 0;

function getInvoice(){
    var self = $('#payAmount'), data = self.data(), req = {act: 'getInvoice', orderID: data.order, payID: data.pid};

    self.find('input, textarea, select').map(function(i, e){
        req[e.name] = e.value;
    });

    self.localLoader('show');
    self.find('.submit').css('pointer-events', 'none');

    $.post("ajax_payOrder.php", req, function(res) {
        if (!res || res.status === undefined || parseInt(res.status) || !res.ok)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            }).then(function(){
                self.localLoader('hide');
                self.find('.submit').css('pointer-events', 'auto');
            });

        self.remove();
        openPayOrder({orderID: data.order});

        Swal.fire({
            title: 'קבלה נשלחה בהצלחה!',
            icon: 'success'
        });
    }, "json");
}

function docSharePop(orid, pid, url){
    let pop = $('#dsPop');

    pop.off('click').on('click', '.icon', function(){
        let via = $(this).data('via'), papa = $('#pay_orderPop');

        if (via == 'dl'){
            window.open(url);
            pop.hide();
        }
        else {
            let phone = $('#sharePhone').val(), req = {act:'shareInvoice', orderID:orid, payID:pid, phone:phone, via:via};

            papa.localLoader('show');
            $.post('ajax_payOrder.php', req).then(function(res){
                papa.localLoader('hide');

                if (!res || res.status === undefined || parseInt(res.status) || !res.success)
                    return Swal.fire({title: 'שגיאה!', html: res.error || res._txt, icon: 'error'});

                if (via == 'wha')
                    $('#dsAnc').prop('href', "//wa.me/972" + phone.replace(/^0+/, '') + "?text=" + encodeURIComponent(res.message)).get(0).click();
                else        // sms
                    Swal.fire({title: 'הקישור לחשבונית נשלח בהצלחה!', html: '', icon: 'success'});
            });
        }
    }).show();
}

function getInvoiceSubs(){
    var self = $('#payAmount'), data = self.data(), req = {act: 'getInvoice', subID: data.order, payID: data.pid};

    self.find('input, textarea, select').map(function(i, e){
        req[e.name] = e.value;
    });

    self.localLoader('show');
    self.find('.submit').css('pointer-events', 'none');

    $.post("ajax_paySubs.php", req, function(res) {
        if (!res || res.status === undefined || parseInt(res.status) || !res.ok)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            }).then(function(){
                self.localLoader('hide');
                self.find('.submit').css('pointer-events', 'auto');
            });

        self.remove();
        openPaySubs({subID: data.order});

        Swal.fire({
            title: 'קבלה נשלחה בהצלחה!',
            icon: 'success'
        });
    }, "json");
}

function openDirectPop(btn){
    var pay = $(btn).closest('.payment').data().payId, order = $(btn).closest('.pay_order').data().orderId, prev = $('#payAmount');
	var _sum = $('#left_to_pay').val();
    $.post("ajax_payOrder.php", {act: 'directPayPop', orderID: order, payID: pay, sum: _sum}, function(res) {
        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });

        if (prev.length)
            prev.replaceWith(res.html);
        else
            $(res.html).appendTo('body');
    }, "json");
}

function openDirectRefund(btn){
    var pay = $(btn).closest('.payment').data().payId, order = $(btn).closest('.pay_order').data().orderId, prev = $('#payAmount');

    $.post("ajax_payOrder.php", {act: 'directRefundPop', orderID: order, payID: pay}, function(res) {
        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });

        if (prev.length)
            prev.replaceWith(res.html);
        else
            $(res.html).appendTo('body');
    }, "json");
}

function openInvoicePop(btn){
    var pay = $(btn).closest('.payment').data().payId, order = $(btn).closest('.pay_order').data().orderId, prev = $('#payAmount');

    $.post("ajax_payOrder.php", {act: 'invoicePop', orderID: order, payID: pay}, function(res) {
        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });

        if (prev.length)
            prev.replaceWith(res.html);
        else
            $(res.html).appendTo('body');

        $('#payment_date').datetimepicker({
            format: 'd/m/Y',
            timepicker: false
        });
    }, "json");
}


function openInvoiceSubs(btn){
    var pay = $(btn).closest('.payment').data().payId, order = $(btn).closest('.pay_order').data().orderId, prev = $('#payAmount');

    $.post("ajax_paySubs.php", {act: 'invoicePop', subID: order, payID: pay}, function(res) {
        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });

        if (prev.length)
            prev.replaceWith(res.html);
        else
            $(res.html).appendTo('body');

        $('#payment_date').datetimepicker({
            format: 'd/m/Y',
            timepicker: false
        });
    }, "json");
}


function initDirectPay(){
    var pop = $('#payAmount'), data = pop.data(), sum = $('#dpAmount').val(), pays = $('#dpPayments').val(), cvv = $('#dpcvv').val(), iname = $('#dpName').val();

    pop.localLoader('show');
    pop.find('.submit').css('pointer-events', 'none');

    $.post("ajax_payOrder.php", {act: 'directPay', orderID: data.order, payID: data.pid, sum: sum, pays:pays, cvv:cvv, iname:iname}, function(res) {
        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            }).then(function(){
                pop.localLoader('hide');
                pop.find('.submit').css('pointer-events', 'auto');
            });

        pop.remove();
        openPayOrder({orderID: data.order});
        Swal.fire({
            title: 'תשלום עבר בהצלחה!',
            icon: 'success'
        });
    }, "json");
}

function initDirectRefund(){
    var pop = $('#payAmount'), data = pop.data(), sum = $('#dpAmount').val(), /*pays = $('#dpPayments').val(),*/ cvv = $('#dpcvv').val(), iname = $('#dpName').val();

    pop.localLoader('show');
    pop.find('.submit').css('pointer-events', 'none');

    $.post("ajax_payOrder.php", {act: 'directRefund', orderID: data.order, payID: data.pid, sum: sum, pays:1, cvv:cvv, iname:iname}, function(res) {
        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            }).then(function(){
                pop.localLoader('hide');
                pop.find('.submit').css('pointer-events', 'auto');
            });

        pop.remove();
        openPayOrder({orderID: data.order});
        Swal.fire({
            title: 'זיכוי בוצע בהצלחה!',
            icon: 'success'
        });
    }, "json");
}

function openPayAfterSave(data){
    insertOrderSpa(function(){
        openPayOrder(data);
    });
}

function openPayAfterSaveSubs(f, data){
    saveSubscription(f, function(){
        openPaySubs(data);
    });
}


function setCupons(){
    var cType = $('#provd').val();
    var cpn = $("#cupons").val();
    $("#payamount").attr("disabled",false);
    window.cuponsArray[cType].forEach((item)=>{
    	if(item.id == cpn) {
    		$("#payamount").val(item.cuponPrice);
            $("#payamount").attr("disabled",true);
    		return;
		}
	});
}

function loadCupons() {
	var cType = $('#provd').val();
    $.post('ajax_global.php',{act: 'getCuponsList' , cType:cType },function(res){
        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });
        var options = '';
        if(!window.cuponsArray) window.cuponsArray = new Array();
        window.cuponsArray[cType] = res.data; //maybe used the selected here
        res.data.forEach(function(item){
            options += '<option value="'+item.id+'">'+item.shortname+'</option>';
		});
        $("#cupons option").remove();
        if(options) {
            options = '<option value="">שובר פתוח</option>' + options;
            $("#cupons").parent().show();
		}
        else {
            $("#cupons").parent().hide();
		}
        $(options).appendTo($("#cupons"));


    });
}

function openPayOrder(data){
	if($('body').hasClass('ssd_new'))ssd_new = 1;
            let _selected_option = $(this).find('option:selected').html();
            $(this).next('.select2').find('.select2-selection__rendered').html(_selected_option);
	$.post('ajax_payOrder.php',{data:data, new:ssd_new},function(res){
	    if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });

        var prev = $('#pay_orderPop');

		prev.length ? prev.replaceWith(res.html) : $("#orderLside").append(res.html);
        $('#ccorcash').select2();
        $('#ccorcash').off('change').on('change', function(){
            let _selected_option = $(this).find('option:selected'), iname = $(this).closest('form').find('.invoice-name'), cont = $(this).closest('form');

            $(this).next('.select2').find('.select2-selection__rendered').html(_selected_option.html());

            cont.find('.cpn-data').css('display', (this.value == 'coupon') ? 'inline-block' : 'none').find('select, input').val('');
            cont.find('.room-number').css('display', (this.value == 'guest' || this.value == 'guestHts') ? 'inline-block' : 'none').find('select, input').val('');
            cont.find('.hts-select').css('display', (this.value == 'guestHts') ? 'inline-block' : 'none').find('select, input').val('');
            cont.find('.manuy-number').css('display', (this.value == 'member' || this.value == 'member2') ? 'inline-block' : 'none').find('select, input').val('');

            if (this.value == 'guestHts')
                cont.find('.room-number.for-protel').css('display', 'none');

            iname.css('display', (this.value == 'ccard' || (this.value == 'cash' && _selected_option.data('ai') == 2)) ? 'inline-block' : 'none');

            $('#manuy-number').off('change');
            $('#payamount').prop('readonly', this.value == 'member2');

            if (this.value == 'coupon') {
                $('#provd').select2();
			}
			else if (this.value == 'member2') {
			    let lag;

                $('#payamount').val(0);
                $('#manuy-number').on('keyup paste', function(){
                    let sel = this;

                    if (lag)
                        window.clearTimeout(lag);

                    lag = window.setTimeout(function(){
                        lag = null;
                        checkSubscription(sel.value, data.orderID);
                    }, 500);
                });
            }

            $('#provd').on('change', function(){
                var coup = $('#coupon'), lag;

                coup.off('keydown paste');

                if (this.value == 'vouchers'){
                    coup.on('keydown paste', function(){
                        if (lag)
                            window.clearTimeout(lag);

                        lag = window.setTimeout(function(){
                            lag = null;
                            checkCoupon(coup.val(), data);
                        }, 500);
                    });
                }
            });
        });

        if (data.payID)
            $('.item.payment[data-pay-id=' + data.payID + ']').find('.pay.invoice:not(.done)').click();
	});
}


function openPaySubs(data){
    let _selected_option = $(this).find('option:selected').html();
    $(this).next('.select2').find('.select2-selection__rendered').html(_selected_option);

    $.post('ajax_paySubs.php', $.extend({}, data),function(res){
        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });

        var prev = $('#pay_orderPop');

        prev.length ? prev.replaceWith(res.html) : $("#orderLside").append(res.html);
        $('#ccorcash').select2();
        $('#ccorcash').off('change').on('change', function(){
            let _selected_option = $(this).find('option:selected'), iname = $(this).closest('form').find('.invoice-name');

            $(this).next('.select2').find('.select2-selection__rendered').html(_selected_option.html());

            $('.cpn-data').css('display', (this.value == 'coupon') ? 'inline-block' : 'none').find('select, input').val('');
            $('.room-number').css('display', (this.value == 'guest') ? 'inline-block' : 'none').find('select, input').val('');
            $('.manuy-number').css('display', (this.value == 'member') ? 'inline-block' : 'none').find('select, input').val('');

            iname.css('display', (this.value == 'ccard' || (this.value == 'cash' && _selected_option.data('ai') == 2)) ? 'inline-block' : 'none');

            if (this.value == 'coupon') {
                $('#provd').select2();
            }

            $('#provd').on('change', function(){
                var coup = $('#coupon'), lag;

                coup.off('keydown paste');

                if (this.value == 'vouchers'){
                    coup.on('keydown paste', function(){
                        if (lag)
                            window.clearTimeout(lag);

                        lag = window.setTimeout(function(){
                            lag = null;
                            checkCoupon(coup.val(), data);
                        }, 500);
                    });
                }
            });
        });

        if (data.payID)
            $('.item.payment[data-pay-id=' + data.payID + ']').find('.pay.invoice:not(.done)').click();
    });
}


function checkCoupon(cnum, data){
    var pop = $('#pay_orderPop');

    pop.localLoader('show');
    $.post('ajax_payOrder.php', $.extend({act:'checkCoupon', cnum: cnum}, data)).then(function(res){
        if (!res || res.status === undefined || parseInt(res.status) || res.error){
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });
        }
        else
            $('#payamount').val(res.csum || '0');
    }).then(function(){
        pop.localLoader('hide');
    });
}


function checkSubscription(snum, orderID){
    var pop = $('#pay_orderPop');

    pop.localLoader('show');
    $.post('ajax_payOrder.php', {act:'checkSubscription', snum: snum, orderID:orderID}).then(function(res){
        $('#payamount').val(res ? res.csum || '0' : '0');
        /*if (!res || res.status === undefined || parseInt(res.status) || res.error){
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });
        }
        else
            $('#payamount').val(res ? res.csum || '0' : '0');*/
    }).then(function(){
        pop.localLoader('hide');
    });
}


function calcSum(){
    var sum = 0, disc = $('#price_discount').val() || 0;

    $('.roomSelectWrap').has('input.unit-id:checked').find('.payment-inp').each(function(){
        sum += Number(this.value || 0);
    });
    $('#spa_orders').children('.spaorder').each(function(){
        sum += Number($(this).data('price') || 0);
    });
    $('.treatments_patients_addings input.extra:checked').parent().find('.l input.count, .l select.count').each(function(){
        if (this.nodeName == 'SELECT')
            sum += $(this.options[this.selectedIndex]).data('price');
        else
        sum += this.value * $(this).data('price');
    });
    $('#price_total').val(sum);
    $('#price_to_pay').val(sum - disc);
	leftToPay();
}

function showFoldedExtraSums(){
    $('.addings').each(function(){
        var _sum = 0;
        $(this).find('.adding input:checked ~ label ~ .l .price').each(function(){
            _sum += parseInt($(this).html().replace(/\D/g, ''));
        });
        $(this).find('.title .addings_sum').remove();
        $(this).find('.title').append('<span class="addings_sum">₪'+_sum+'</span>')
    })
}

function prePay(){
    var sum = 0;
    $('.roomSelectWrap').has('input.unit-id:checked').find('.prePayment-inp').each(function(){
        sum += Number(this.value || 0);
    });
    $('#prePay').val(sum);
	leftToPay();
}

function leftToPay(){

	var a = $('#price_to_pay').val();
	var b = $('#prePay').val();
    var btn = $('#create_orderPop').find('.orderPrice > span');
    var diff = a - b, tmp;
	$('#leftPay').val(diff);
    if (btn[0] && btn[0].firstChild)
        btn[0].firstChild.nodeValue = '₪' + a;
    (diff > 0) ? btn.parent().removeClass('paid') : btn.parent().addClass('paid');
}

function calcDiscount(elem)
{
    var total = $('#price_total').val() || 0;
    if (elem.name == 'price_to_pay')
        $('#price_discount').val(total - (elem.value || 0));
    else if (elem.name == 'price_discount')
        $('#price_to_pay').val(total - (elem.value || 0));
}

function initPay($cont, _target){
	//if needed you can use window.cuponsArray[cpntype][selected cupon]
	var cpntype = $("#provd").val();


    var data = $cont.data(), req = {
        orderID: data.orderId,
        act : 'initPay',
        type: $cont.find('input[name="paymethod"]:checked').val(),
        sum : $('#payamount').val(),
        adv : $('#approved').prop('checked') ? 1 : 0,
        cpn : $('#coupon').val(),
        via : $('#ccorcash').val(),
		cpnname : $("#cupons option:selected").text(),
		cpnid: $("#cupons").val(),
        iname: $("#invoiceName").val()
    };

    $('#pay_orderPop').localLoader('show');

    if (req.via == 'coupon')
        req.prv = $('#provd').val();
    else if (req.via == 'member' || req.via == 'member2')
        req.mbr = $('#manuy-number').val();
    else if (req.via == 'guest' || req.via == 'guestHts'){
        req.apt = $('#guestAppt').val();
        req.bon = $('#guestBon').val();
        req.hts = $('#guestSupp').val();
        req.booker = $('#booker').val();
        req.inner  = $('#innerID').val();
    }

    $.post(_target || 'ajax_payOrder.php', req).then(function(res){
        $('#pay_orderPop').localLoader('hide');

        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });

        if (res.complete == 1)
            Swal.fire({title: 'תשלום נרשם בהצלחה', icon: 'success'}).then(function(){
                switch(_target){
                    case 'ajax_paySubs.php':
                        openPaySubs({subID: req.orderID, reload: 1, payID: res.payID});
                        break;

                    default:
                        openPayOrder({orderID: req.orderID, reload: 1, payID: res.payID});
                }
            });

        else if (res.url)
            $('#ccpop').fadeIn('fast').find('iframe').attr('src', res.url);
    });
}

function closeCCDiv(div){
    $(div || '#ccpop').fadeOut('fast').find('iframe').attr('src', 'about:blank');
}

function deletePayment(btn, title, callback){
    var pay = $(btn).closest('.payment').data().payId, order = $(btn).closest('.pay_order').data().orderId;

    Swal.fire({
        title: title || 'למחוק תשלום?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'לא',
        confirmButtonText: 'כן'
    }).then(function(result){
        if (result.value) {
            $('#pay_orderPop').localLoader('show');

            $.post("ajax_payOrder.php", {orderID: order, act: 'payDelete', payID: pay}, function(res) {
                $('#pay_orderPop').localLoader('hide');

                if (!res || res.status === undefined || parseInt(res.status) || res.error)
                    return Swal.fire({
                        title: 'שגיאה!',
                        html: res.error || res._txt,
                        icon: 'error'
                    });

                (typeof callback == 'function') ? callback.call(btn) : openPayOrder({orderID: order});
            }, "json");
        }
    });
}

function deletePaymentSubs(btn, title, callback){
    var pay = $(btn).closest('.payment').data().payId, order = $(btn).closest('.pay_order').data().orderId;

    Swal.fire({
        title: title || 'למחוק תשלום?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'לא',
        confirmButtonText: 'כן'
    }).then(function(result){
        if (result.value) {
            $('#pay_orderPop').localLoader('show');

            $.post("ajax_paySubs.php", {subID: order, act: 'payDelete', payID: pay}, function(res) {
                $('#pay_orderPop').localLoader('hide');

                if (!res || res.status === undefined || parseInt(res.status) || res.error)
                    return Swal.fire({
                        title: 'שגיאה!',
                        html: res.error || res._txt,
                        icon: 'error'
                    });

                (typeof callback == 'function') ? callback.call(btn) : openPaySubs({subID: order});
            }, "json");
        }
    });
}

function cancelRefund(btn, title, html, act){
    var pay = $(btn).closest('.payment').data().payId, order = $(btn).closest('.pay_order').data().orderId;

    Swal.fire({
        title: title,
        html: html,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'לא',
        confirmButtonText: 'כן'
    }).then(function(result){
        if (result.value) {
            $.post("ajax_payOrder.php", {orderID: order, act: act, payID: pay}, function(res) {
                if (!res || res.status === undefined || parseInt(res.status) || res.error)
                    return Swal.fire({
                        title: 'שגיאה!',
                        html: res.error || res._txt,
                        icon: 'error'
                    });

                if (res.warning)
                    Swal.fire({
                        title: 'שים לב!',
                        html: res.warning,
                        icon: 'warning'
                    }).then(function(){
                        openPayOrder({orderID: order});
                    });
                else
                    openPayOrder({orderID: order});
            }, "json");
        }
    });
}

function refundPayment(btn){
    cancelRefund(btn, 'לזכות תשלום?', 'הלקוח יקבל זיכוי על סכום של תשלום', 'payRefund');
}

function cancelPayment(btn){
    cancelRefund(btn, 'לבטל תשלום?', 'התשלום לא יופיע אצל לקוח כלל', 'payCancel');
}

function cancelRefundSub(btn, title, html, act){
    var pay = $(btn).closest('.payment').data().payId, order = $(btn).closest('.pay_order').data().orderId;

    Swal.fire({
        title: title,
        html: html,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'לא',
        confirmButtonText: 'כן'
    }).then(function(result){
        if (result.value) {
            $.post("ajax_paySubs.php", {subID: order, act: act, payID: pay}, function(res) {
                if (!res || res.status === undefined || parseInt(res.status) || res.error)
                    return Swal.fire({title: 'שגיאה!', html: res.error || res._txt, icon: 'error'});

                if (res.warning)
                    Swal.fire({title: 'שים לב!', html: res.warning, icon: 'warning'}).then(function(){
                        openPaySubs({subID: order});
                    });
                else
                    openPaySubs({subID: order});
            }, "json");
        }
    });
}

function refundSubsPayment(btn){
    cancelRefundSub(btn, 'לזכות תשלום?', 'הלקוח יקבל זיכוי על סכום של תשלום', 'payRefund');
}

function cancelSubsPayment(btn){
    cancelRefundSub(btn, 'לבטל תשלום?', 'התשלום לא יופיע אצל לקוח כלל', 'payCancel');
}

function sendContact(formId) {
	$.post('js_sendContact.php',$('#'+formId).serialize(),function(result){
			$('.holder').hide();
			if(result.error){
				 swal.fire({title:result.error , type:'error'});
			}
			else if(result.success){
				swal.fire({title:result.title ,text:result.text, type:'success'});
				$('#ccpop').hide();
				//$('#'+formId)[0].reset();
			}
		},"JSON");
}

function reviewInvite(orid){
    $.post('ajax_orderPlus.php', {action:'reviewInvite', orderID: orid}, function(result){
        if(result.error || !result)
            swal.fire({title:result.error || result._txt || 'Internal error' , icon:'error'});
        else if(result.success)
            swal.fire({title:result.title ,text:result.text, icon:'success'});
    }, 'json');
}

function deleteTreatment(elem){
    var papa = $(elem.parentNode), tid = papa.data('id');

    swal.fire({title:'להסיר טיפול?', icon:'question', showDenyButton: true, confirmButtonText: 'כן', denyButtonText: 'לא'}).then(function(res){
        if (res.isConfirmed)
            $.post('ajax_spaSingle.php', {act:'deleteSpaSingle', id:tid}, function(result){
                if(result.error || !result)
                    swal.fire({title:result.error || result._txt || 'Internal error' , icon:'error'});
                else if(result.success) {
                    swal.fire({title:result.success, icon:'success'}).then(function(){
                        window.openFoo ? window.openFoo({orderID: papa.data('parent')}) : papa.remove();
                    });
                }
            }, 'json');
    });
}


function openSingleSelect(act) {
	//debugger;


	$.post('ajax_singleSelect.php','act='+ act +'&' + $('#spaOrderForm').serialize(),function(result){
		if(result.success){
			$('#selectcontent').html(result.html);
			$('#selectpop').fadeIn('fast');
		}else{
			swal.fire({title:result.error || 'ארעה שגיאה' , icon:'error'});
		}
	},"JSON");

}

function Duplicator(base){
    var data = $(base).data();

    if (!data.id)
        return swal.fire({icon:'error', title:'שגיאה!', text:'Cannot find order ID'});

    this.opener  = $(base);
    this.orderID = data.id;
    this.popup   = $('#duplicate_treatment');
    this.select  = 'select';

    this.popup.find('.close').off('click').on('click', this.closeDiv.bind(this));
    this.popup.find('button.submit').off('click').on('click', this.duplicate.bind(this));
    this.popup.find('button.submit2').off('click').on('click', this.duplicateGroup.bind(this));

    this.openDiv();
}
$.extend(Duplicator.prototype, {
    ajax: function(prm){
        return $.post('ajax_spaSingle.php', prm).then(function(res){
            if (!res || !res.success)
                return swal.fire({icon:'error', title:'שגיאה!', text:res.error || res._txt || res}) && Promise.reject(res);
            return res;
        });
    },

    openDiv: function(){
        var self = this;

        this.ajax({act:'freeFictive', id:this.orderID}).then(function(res){
            self.popup.fadeIn('fast').find('.content').html(res.html).end().find('.content2').html(res.groupHTML);
            self.popup.find('button.submit')[res.max ? 'show' : 'hide']();
            self.popup.find('button.submit2')[res.groupMax ? 'show' : 'hide']();
            if (res.select)
                self.select = res.select;
        });
    },

    closeDiv: function(){
        this.popup.fadeOut('fast');
    },

    duplicate: function(){
        var cnt = this.popup.find(this.select).val();

        this.ajax({act:'copyOrder', id:this.orderID, mult:cnt}).then(function(res){
            spaOrderChanged = true;
            openSpaFrom({orderID: res.orderID});
        });
    },

    duplicateGroup: function(){
        var prms = {act:'copyGroup', id:this.orderID};

        this.popup.find('.content2 select').each(function(){
            prms[this.name] = this.value;
        });

        this.ajax(prms).then(function(res){
            spaOrderChanged = true;
            openSpaFrom({orderID: res.orderID});
        });
    }
});
function setCupons(){
    var cType = $('#provd').val();
    var cpn = $("#cupons").val();
    $("#payamount").attr("disabled",false);
    window.cuponsArray[cType].forEach((item)=>{
        if(item.id == cpn) {
            $("#payamount").val(item.cuponPrice);
            $("#payamount").attr("disabled",true);
            return;
        }
    });
}

function loadCupons() {
    var slct = $('#provd'), cType = slct.val(), _selected_option = slct.find('option:selected');

    $.post('ajax_global.php',{act: 'getCuponsList' , cType:cType },function(res){
        if (!res || res.status === undefined || parseInt(res.status) || res.error)
            return Swal.fire({
                title: 'שגיאה!',
                html: res.error || res._txt,
                icon: 'error'
            });
        var options = '';
        if(!window.cuponsArray) window.cuponsArray = new Array();
        window.cuponsArray[cType] = res.data; //maybe used the selected here
        res.data.forEach((item)=>{
            options += '<option value="'+item.id+'">'+item.shortname+'</option>';
        });
        $("#cupons option").remove();
        if(options) {
            options = '<option value="">שובר פתוח</option>' + options;
            $("#cupons").parent().show();
        }
        else {
            $("#cupons").parent().hide();
        }
        $(options).appendTo($("#cupons"));

        slct.closest('form').find('.invoice-name').css('display', (_selected_option.data('ai') == 2) ? 'inline-block' : 'none');
    });
}


function change_c_s(elm){
	debugger;
	$('#s_box').remove();
	var _right = elm[0].getBoundingClientRect().right;
	var _top = elm[0].getBoundingClientRect().top;
	if(parseInt(_top)>($(window).height()-160)){
		_top = ($(window).height()-160);
	}
	var orderID;
	orderID = elm.data('orderid');
	if(!orderID){
		orderID = elm.closest('.order').data('orderid');
	}
	var c_s 
	
	if(elm.hasClass('c_s1'))
		c_s = 1;
	else if(elm.hasClass('c_s2'))
		c_s = 2;
	else if(elm.hasClass('c_s10'))
		c_s = 10;
	else if(elm.hasClass('c_s3'))
		c_s = 3;
	else if(elm.hasClass('c_s4'))
		c_s = 4;

	var s_box = document.createElement('div');
	s_box.setAttribute('id','s_box');
	s_box.setAttribute('data-id',orderID);
	var _sbox='<div class="s_box_wrapper">';
		_sbox+='<div class="closer" onclick="$(this).closest(\'#s_box\').remove()"></div>'
		_sbox+='<div class="line c_s1 '+(c_s==1? "selected" : "")+'" data-val="1" onclick="set_c_s(1)">לא תוזכר</div>';
		_sbox+='<div class="line c_s2 '+(c_s==2? "selected" : "")+'" data-val="2" onclick="set_c_s(2)">תוזכר</div>';
		_sbox+='<div class="line c_s10 '+(c_s==10? "selected" : "")+'" data-val="10" onclick="set_c_s(10)">אישר הגעה אוטומטית</div>';
		_sbox+='<div class="line c_s3 '+(c_s==3? "selected" : "")+'" data-val="3" onclick="set_c_s(3)">הגיע</div>';
		_sbox+='<div class="line c_s4 '+(c_s==4? "selected" : "")+'" data-val="4" onclick="set_c_s(4)">לא הגיע</div>';
		_sbox+='</div>'
		$('body').append(s_box);
	$('#s_box').html(_sbox);
	var _style = 'left: '+(parseInt(_right) - 160)+'px;right: auto;top: '+_top+'px;bottom: auto;transform: none;';
	$('#s_box .s_box_wrapper').attr('style',_style)
	console.log('');
}


function set_c_s(c_s_val){
	debugger;
	//var c_s_val = elm.attr('data-val');
	var orderID = $('#s_box').attr('data-id');
	$.post('ajax_change_c_s.php', {orderID:orderID,client_status:c_s_val}).then(function(res){
           if (res.status == 'success'){
				$('.order[data-orderid="'+orderID+'"] .c_status').attr('class','c_status c_s'+c_s_val);
				$('#s_box').remove();
		   }else{               
				return swal.fire({icon:'error', title:'שגיאה!', html:res.error || 'אראה שגיאה'});
		   }
       });
	
	

}

function printExtra(elm){
    var win = window.open("", elm.attr('data-title'), "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));


    let _html = `
    <!DOCTYPE html>
    <html dir="rtl">
    <head>
        <style>
            body {font-family:arial;}
            .left {text-align:center;width:100%;}
            .block {text-align:center;font-size:18px;max-width:500px;display:inline-block;width:100%;}
            .block .border {padding:10px;box-sizing:border-box;border:1px solid #000;text-align:right;font-size:16px;border-radius:10px;}
        </style>
    </head>
    <body>
        <div class="left">
        <div class="block" dir="rtl" style="direction:rtl;">
        <div class="place">${elm.attr('data-place')}</div>
        <div class="border">
            <div><strong>אורח:</strong> <span>${elm.attr('data-person')}</span></div>
            <div><strong>תאריך:</strong> <span>${elm.attr('data-date')}</span></div>
            <div><strong>שעה:</strong> <span>${elm.attr('data-time')}</span></div>
            <div><strong>פריט:</strong> <span>${elm.attr('data-name')}</span></div>
            <div><strong>כמות:</strong> <span>${elm.attr('data-quantity')}</span></div>
        </div>
        </div>
        </div>
    </body>
    </html>
    `;
	
    win.document.body.innerHTML = _html;
    setTimeout(function () { win.print(); }, 500);
    win.onfocus = function () { setTimeout(function () { win.close(); }, 500); }
}



class SumBalancer {
    inputs = {};

    total_sum   = 0;
    total_disc  = 0;
    total_payed = 0;

    constructor(f, map = {}){
        let form = $(f);

        this.inputs.total    = map.total ? form.find(map.total) : form.find('input[name="price_total"]');
        this.inputs.discount = map.discount ? form.find(map.discount) : form.find('input[name="price_discount"]');
        this.inputs.toPay    = map.toPay ? form.find(map.toPay) : form.find('input[name="price_to_pay"]');
        this.inputs.payed    = map.payed ? form.find(map.payed) : form.find('input[name="prePay"]');
        this.inputs.left     = map.left ? form.find(map.left) : form.find('input[name="leftPay"]');

        this.total_sum   = parseInt(this.inputs.total.val()) || 0;
        this.total_disc  = parseInt(this.inputs.discount.val()) || 0;
        this.total_payed = parseInt(this.inputs.payed.val()) || 0;

        this.inputs.discount.on('keyup paste', e => this.set_discount(e.target.value));
        this.inputs.toPay.on('keyup paste', e => this.set_to_pay(e.target.value));
    }

    _render_depends(){
        this.inputs.toPay.val(this.total_sum - this.total_disc);
        this.inputs.left.val(this.total_sum - this.total_disc - this.total_payed);

        return this;
    }

    set_total(sum){
        this.total_sum = parseInt(sum) || 0;
        this.inputs.total.val(this.total_sum);

        return this._render_depends();
    }

    set_discount(sum){
        this.total_disc = parseInt(sum) || 0;
        this.inputs.discount.val(this.total_disc);

        return this._render_depends();
    }

    set_to_pay(sum){
        return this.set_discount(this.total_sum - sum);
    }

    add_total(sum){
        return this.set_total(this.total_sum + sum);
    }
}

$('body').on("wheel mousewheel", 'input[type="number"]', function(e){
	//console.log('x');
    $(this).blur();
})
