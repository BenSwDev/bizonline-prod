
var LINE_WIDTH = 80;


function addDayOrder(data){
	this.orderDate  = data.orderDate;
    this.orderType  = data.orderType;
	this.endDate    = data.endDate;
	this.startTime  = data.startTime;
	this.endTime    = data.endTime;
	this.roomID     = data.roomID;
	this.name       = data.name;
	this.phone      = data.phone;
	this.price      = data.price;
	this.showOrders = data.showOrders || 0;
	this.orderID    = data.orderID ;
	this.cleanTime  = data.cleanTime || 15;
	this.allDay     = data.allDay || 0;
	this.approved   = data.approved;
	this.guid       = data.guid;
	this.orderIDBySite  = data.orderIDBySite;
	this.domainIcon  = data.domainIcon;
	
	this.showOrderDate  = this.orderDate;
	this.showEndDate    = this.endDate;
	this.showStartTime  = this.startTime;
	this.showEndTime    = this.endTime;
	this.dayBefore = false;


}
$.extend(addDayOrder.prototype, {
	init:function(){
		var self = this;


		if(self.orderDate==currentDay && self.endDate==currentDay){
			//הזמנה ליום הנוכחי בלבד

		}else if(self.orderDate==currentDay && self.endDate!=currentDay){
			//מתחיל היום ומסתיים לאחריו
		
			this.endTime = "23:59:59";
		}
		else if(self.orderDate!=currentDay && self.endDate!=currentDay){
			//יום שלם
			this.dayBefore = true;
			this.startTime = "00:00";	
			this.endTime = "23:59:59";
		}
		else{
			//ממשיך מיום קודם
			this.dayBefore = true;
			this.startTime = "00:00";
		}

		if(self.showOrders){
			this.addUI();
		}else{

			
		}
	},
	addUI:function(){
		var self = this, clist = ['order'];

        if (self.approved)
            clist.push('approved');
        if (self.orderType == 'preorder')
            clist.push('preorder');

		var dataui = $('<div class="'+clist.join(' ')+'" data-orderID="'+self.orderID+'" data-orderidbysite="'+self.orderIDBySite+'" style="width:calc('+self._width().toFixed(2)*100+'% + '+self._width().toFixed(2)*2+'px);margin-right:'+this.startTime.substr(3,5) +'%"><div class="all"></div>\
							<input type="hidden" class="guid" value="'+self.guid+'">\
							<div class="name customerName">'+self.name+'</div>\
							<div class="phone">'+self.phone+'</div>\
							<div class="bottom">\
								<div class="price">₪'+self.price+'</div>\
								<div class="status">'+(self.approved?"מאושר":"לא מאושר")+'</div>\
							</div>\
							<div class="domain-icon" style="background-image:url('+ domain[self.domainIcon] +')"></div>\
							<div class="whatsapp call" style="display:none" data-phone="'+self.phone+'" ><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></div>\
						</div>');
							
		$('.row[data-uid="'+self.roomID+'"][data-hour="'+(this.dayBefore?"x":self.startTime)+'"]').append(dataui);
		

	},
	_width:function(){
		var self = this;
		
		var currentDay2 = currentDay.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(currentDay2+" "+this.endTime) - new Date(currentDay2+" "+this.startTime));
		var hours = diff / 3600000;
		return self.dayBefore?hours+1:hours;
	
	},
	_hour:function(){
		var self = this;
		var currentDay2 = currentDay.split("/").reverse().join("-"); 
		//var endDay = self.endDate.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(currentDay2+" "+this.startTime) - new Date(currentDay2+" 00:00:00"));
		var hours = Math.floor((diff / 3600000));
		return hours;
	
	},
	addOccDay:function(element){
		var self = this;
		var data = {'unitID':this.roomID, 'from':this.orderDate,'allDay':1};
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
		
		},
		addOrderToDb:function(){
		var self = this;
		var from = this.orderDate+" "+this.startTime;
		var until = (this.endDate || this.orderDate)+" "+this.endTime;
		var result;
		var data = {'unitID':this.roomID, 'from':this.orderDate+" "+this.startTime, 'until':until};
		$.post('ajax_order.php',data, function(res){
			if(res.error){
				Swal.fire({type:'error', text:res.error});
				result =  0;
			}else{
				Swal.fire({type:'success', text:res.success});
				result =  1;
			}
		},'json').done(function(res){
		
			if(result){
				self.orderID = res.orderID;
				self.addUI();
				$('.orderForm')[0].reset();
				$("#orderID").val(res.orderID);
				$('.timePick[name="from"]').val(self.orderDate+" "+self.startTime);
				if(!self.endDate){
					$('.timePick[name="until"]').val(self.orderDate+" "+self.endTime);
				}else{
					$('.timePick[name="until"]').val(self.endDate+" "+self.endTime);
				}
				$(".orderFormPop").css('display','flex');

			}
		})
	}
	
})

function addMonthOrder(data){
	
	
	this.orderDate  = data.orderDate;
        this.orderType  = data.orderType;
	this.endDate    = data.endDate;
	this.startTime  = data.startTime;
	this.endTime    = data.endTime;
	this.roomID     = data.roomID;
	this.name       = data.name;
	this.roomName     = data.roomName;
	this.roomNum     = data.roomNum;
	this.phone      = data.phone;
	this.price      = data.price;
	this.showOrders = data.showOrders || 0;
	this.orderID    = data.orderID ;
	this.cleanTime  = data.cleanTime || 15;
	this.allDay     = data.allDay || 0;
	this.approved   = data.approved;
	this.guid       = data.guid;
	this.orderIDBySite  = data.orderIDBySite;
	this.domainIcon  = data.domainIcon;
        
        this.counter  = data.counter;
        this.time_list  = data.time_list;

	this.showOrderDate  = this.orderDate;
	this.showEndDate    = this.endDate;
	this.showStartTime  = this.startTime;
	this.showEndTime    = this.endTime;
}
$.extend(addMonthOrder.prototype, {
	init:function(){
		var self = this;
		var startMonthYear = this.orderDate.substr(3,7);
		var endMonthYear = this.endDate.substr(3,7);
		
		if(startMonthYear==currentMonth && endMonthYear==currentMonth){
			//הזמנה לחודש הנוכחי בלבד

		}else if(startMonthYear==currentMonth && endMonthYear!=currentMonth){
			//מתחיל החודש ומסתיים לאחריו
			
				//בדיקה לתצוגה חודשית
				this.endDate = endMonthDay+"/"+currentMonth;//
				this.endTime = "23:59:59";
			
		}
		else if(startMonthYear!=currentMonth && endMonthYear!=currentMonth){
			//חודש שלם
			//need to check
			this.orderDate  = "01/"+currentMonth;
			this.endDate = endMonthDay+"/"+currentMonth;//
		}
		else{
			//ממשיך מחודש קודם
			this.orderDate  = passMonthYear;
			this.startTime = "00:00";
		}

		if(self.showOrders){
			this.addUI();
		}else{

			
		}
	},
	addUI:function(){
        var self = this, clist = ['shift_idan'];

		if(!self.allDay){

            if (self.approved)
                clist.push('approved');
            if (self.orderType == 'preorder')
                clist.push('preorder');
            if (self._width() > 120)
                clist.push('all-day');
            
                    if (self.counter > 1) {
                        
                        var keysss = 0;
                        var extime = self.time_list.split(",");
                        
                        var i_in = 1;
                        while (i_in <= self.counter) {
                            var extimein = extime[keysss].split("-");
                            
                            
                    var dataui = $('<div title=" '+extimein[0]+' -'+extimein[1]+'  " class="'+clist.join(' ')+'" style="width:'+self._width_d(extimein[0],extimein[1]).toFixed(2)+'%;right:'+self._hour_d(extimein[0]).toFixed(2)+'%"><div class="all"></div>\
							<div class="the_overflow"><div class="start_time_look">'+extimein[0]+'</div>\
   							<div class="start_time_look"> - </div>\
   							<div class="start_time_look">'+extimein[1]+'</div></div>\
						</div>');
		    $('.row[data-uid="'+self.roomID+'"][data-date="'+self.orderDate+'"]').append(dataui);
                    
                            i_in++;
                            keysss++;
                        }
                        
                    } else {
		    var dataui = $('<div title=" '+self.startTime+' -'+self.endTime+'  " class="'+clist.join(' ')+'" style="width:'+self._width().toFixed(2)+'%;right:'+self._hour().toFixed(2)+'%"><div class="all"></div>\
							<div class="the_overflow"><div class="start_time_look">'+self.startTime+'</div>\
   							<div class="start_time_look"> - </div>\
   							<div class="start_time_look">'+self.endTime+'</div></div>\
						</div>');
		    $('.row[data-uid="'+self.roomID+'"][data-date="'+self.orderDate+'"]').append(dataui);
                    }
		}else{
			$('.row[data-uid="'+self.roomID+'"][data-date="'+self.orderDate+'"]').addClass("busy")
		}
		//self.createBusy();
	},
	updateBusy:function(){
		var self = this;
		var startday = self.orderDate.split("/").reverse().join("-"); 
		var endDay = self.endDate.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(endDay) - new Date(startday));
		var days = Math.floor((diff / 86400000));
		var i = 0;
		var startData = $('.sideDate[data-date="'+self.orderDate+'"]');
		for(i;i<=days;i++){
			startData.find('.daysTotal').text(startData.find('.daysTotal').text()-1);
			startData = startData.next();
		}
	},
	createBusy:function(){
		var self = this;
		
		if(self.orderDate != self.endDate){
			var objEl = $('.roomsX .room[data-roomid="'+self.roomID+'"] .tab.days').find('.col[data-date="'+self.orderDate+'"][data-type="day"]').addClass('busy').nextUntil('.col[data-date="'+self.endDate+'"][data-type="day"]').addClass('busy');
		}else{
			var objEl = $('.roomsX .room[data-roomid="'+self.roomID+'"] .tab.days').find('.col[data-date="'+self.orderDate+'"][data-type="day"]').addClass('busy');

		}
		//this.updateBusy();
	},
        
        _width_d:function(starttime,endtime){
		var self = this;
		var startday = self.orderDate.split("/").reverse().join("-"); 		
		var endDay = self.endDate.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(endDay+"T"+endtime.trim()) - new Date(startday+"T"+starttime.trim()));
		var hours = diff / 3600000;
		return Math.round((hours/24)*100);
	
	},
	_hour_d:function(starttime){
		var self = this;

		var startday = self.orderDate.split("/").reverse().join("-"); 
		//var endDay = self.endDate.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(startday+"T"+starttime.trim()) - new Date(startday+"T00:00:00"));
                
                console.log("diff->"+diff+" startday->"+startday+ " starttime->"+starttime);
                
		var hours = Math.floor((diff / 3600000));
                
                console.log("hours->"+hours);
                
		return Math.round((hours/24)*100);
	
	},
        
	_width:function(){
		var self = this;
		var startday = self.orderDate.split("/").reverse().join("-"); 		
		var endDay = self.endDate.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(endDay+"T"+this.endTime) - new Date(startday+"T"+this.startTime));
		var hours = diff / 3600000;
		return Math.round((hours/24)*100);
	
	},
	_hour:function(){
		var self = this;

		var startday = self.orderDate.split("/").reverse().join("-"); 
		//var endDay = self.endDate.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(startday+"T"+this.startTime) - new Date(startday+"T00:00:00"));
		var hours = Math.floor((diff / 3600000));
		return Math.round((hours/24)*100);
	
	},
	/*_calcCleanHeight:function(){
		
		var cleanHeight = 0;
		if(this.endDate==currentDate){
			cleanHeight = ((this.cleanTime/15) * LINE_WIDTH);
		}
		return cleanHeight;
	},*/

		addOccDay:function(element){
			var self = this;
			var data = {'unitID':this.roomID, 'from':this.orderDate,'allDay':1};
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
		
		},
		addOrderToDb:function(){
		var self = this;
		var from = this.orderDate+" "+this.startTime;
		var until = (this.endDate || this.orderDate)+" "+this.endTime;
		var result;
		var data = {'unitID':this.roomID, 'from':this.orderDate+" "+this.startTime, 'until':until};
		$.post('ajax_order.php',data, function(res){
			if(res.error){
				Swal.fire({type:'error', text:res.error});
				result =  0;
			}else{
				Swal.fire({type:'success', text:res.success});
				result =  1;
			}
		},'json').done(function(res){
		
			if(result){
				self.orderID = res.orderID;
				self.addUI();
				$('.orderForm')[0].reset();
				$("#orderID").val(res.orderID);
				$('.timePick[name="from"]').val(self.orderDate+" "+self.startTime);
				if(!self.endDate){
					$('.timePick[name="until"]').val(self.orderDate+" "+self.endTime);
				}else{
					$('.timePick[name="until"]').val(self.endDate+" "+self.endTime);
				}
				$(".orderFormPop").css('display','flex');

			}
		})
	}
	

});



function addMonthViewOrder(data){
	
	
	this.orderDate  = data.orderDate;
    this.orderType  = data.orderType;
	this.endDate    = data.endDate;
	this.startTime  = data.startTime;
	this.endTime    = data.endTime;
	this.roomID     = data.roomID;
	this.name       = data.name;
	this.roomName     = data.roomName;
	this.roomNum     = data.roomNum;
	this.phone      = data.phone;
	this.price      = data.price;
	this.showOrders = data.showOrders || 0;
	this.orderID    = data.orderID ;
	this.cleanTime  = data.cleanTime || 15;
	this.allDay     = data.allDay || 0;
	this.approved   = data.approved;
	this.guid       = data.guid;
	this.orderIDBySite  = data.orderIDBySite;
	this.domainIcon  = data.domainIcon;

	this.showOrderDate  = this.orderDate;
	this.showEndDate    = this.endDate;
	this.showStartTime  = this.startTime;
	this.showEndTime    = this.endTime;
}
$.extend(addMonthViewOrder.prototype, {
	init:function(){
		var self = this;
		var startMonthYear = this.orderDate.substr(3,7);
		var endMonthYear = this.endDate.substr(3,7);
		
		if(startMonthYear==currentMonth && endMonthYear==currentMonth){
			//הזמנה לחודש הנוכחי בלבד

		}else if(startMonthYear==currentMonth && endMonthYear!=currentMonth){
			//מתחיל החודש ומסתיים לאחריו
			
				//בדיקה לתצוגה חודשית
				//this.endDate = endMonthDay+"/"+currentMonth;//
				//this.endTime = "23:59:59";
			
		}
		else if(startMonthYear!=currentMonth && endMonthYear!=currentMonth){
			//חודש שלם
			//need to check
			//this.orderDate  = "01/"+currentMonth;
			//this.endDate = endMonthDay+"/"+currentMonth;//
		}
		else{
			//ממשיך מחודש קודם
			//this.orderDate  = passMonthYear;
			//this.startTime = "00:00";
		}

		if(self.showOrders){
			this.addUI();
		}else{

			
		}
	},
	addUI:function(){
            //debugger;
        var self = this, clist = ['order'];

		if(!self.allDay){

            if (self.approved)
                clist.push('approved');
            if (self.orderType == 'preorder')
                clist.push('preorder');
            if (self._width() > 120)
                clist.push('all-day');

		    var dataui = $('<div class="KOKO2 '+clist.join(' ')+'" data-orderID="'+self.orderID+'" data-orderidbysite="'+self.orderIDBySite+'" style="width:'+self._width().toFixed(2)+'%;right:'+self._hour().toFixed(2)+'%"><div class="all"></div>\
							<input type="hidden" class="guid" value="'+self.guid+'">\
							<div class="roomNum show">'+self.roomNum+'</div>\
							<div class="roomName show">'+self.roomName+'</div>\
							<div class="name customerName">'+self.name+'</div>\
							<div class="phone">'+self.phone+'</div>\
							<div class="bottom">\
								<div class="price">₪'+self.price+'</div>\
								<div class="status">'+(self.approved?"מאושר":"לא מאושר")+'</div>\
							</div>\
							<div class="domain-icon" style="background-image:url('+ domain[self.domainIcon] +')"></div>\
							<div class="whatsapp call" style="display:none" data-phone="'+self.phone+'" ><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30"><defs><image width="30" height="30" id="img-whatsapp" href="data:image/png;base64,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"></image></defs><use id="L0001" href="#img-whatsapp" x="0" y="0"></use></svg></div>\
						</div>');
		    $('.row[data-uid="'+self.roomID+'"][data-date="'+self.orderDate+'"]').append(dataui);
		}else{
			$('.row[data-uid="'+self.roomID+'"][data-date="'+self.orderDate+'"]').addClass("busy")
		}
		//self.createBusy();
	},
	updateBusy:function(){
		var self = this;
		var startday = self.orderDate.split("/").reverse().join("-"); 
		var endDay = self.endDate.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(endDay) - new Date(startday));
		var days = Math.floor((diff / 86400000));
		var i = 0;
		var startData = $('.sideDate[data-date="'+self.orderDate+'"]');
		for(i;i<=days;i++){
			startData.find('.daysTotal').text(startData.find('.daysTotal').text()-1);
			startData = startData.next();
		}
	},
	createBusy:function(){
		var self = this;
		
		if(self.orderDate != self.endDate){
			var objEl = $('.roomsX .room[data-roomid="'+self.roomID+'"] .tab.days').find('.col[data-date="'+self.orderDate+'"][data-type="day"]').addClass('busy').nextUntil('.col[data-date="'+self.endDate+'"][data-type="day"]').addClass('busy');
		}else{
			var objEl = $('.roomsX .room[data-roomid="'+self.roomID+'"] .tab.days').find('.col[data-date="'+self.orderDate+'"][data-type="day"]').addClass('busy');

		}
		//this.updateBusy();
	},
	_width:function(){
		var self = this;
		var startday = self.orderDate.split("/").reverse().join("-"); 		
		var endDay = self.endDate.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(endDay+"T"+this.endTime) - new Date(startday+"T"+this.startTime));
		var hours = diff / 3600000;
		return Math.round((hours/24)*100);
	
	},
	_hour:function(){
		var self = this;

		var startday = self.orderDate.split("/").reverse().join("-"); 
		//var endDay = self.endDate.split("/").reverse().join("-"); 
		var diff = Math.abs(new Date(startday+"T"+this.startTime) - new Date(startday+"T00:00:00"));
		var hours = Math.floor((diff / 3600000));
		return Math.round((hours/24)*100);
	
	},
	/*_calcCleanHeight:function(){
		
		var cleanHeight = 0;
		if(this.endDate==currentDate){
			cleanHeight = ((this.cleanTime/15) * LINE_WIDTH);
		}
		return cleanHeight;
	},*/

		addOccDay:function(element){
			var self = this;
			var data = {'unitID':this.roomID, 'from':this.orderDate,'allDay':1};
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
		
		},
		addOrderToDb:function(){
		var self = this;
		var from = this.orderDate+" "+this.startTime;
		var until = (this.endDate || this.orderDate)+" "+this.endTime;
		var result;
		var data = {'unitID':this.roomID, 'from':this.orderDate+" "+this.startTime, 'until':until};
		$.post('ajax_order.php',data, function(res){
			if(res.error){
				Swal.fire({type:'error', text:res.error});
				result =  0;
			}else{
				Swal.fire({type:'success', text:res.success});
				result =  1;
			}
		},'json').done(function(res){
		
			if(result){
				self.orderID = res.orderID;
				self.addUI();
				$('.orderForm')[0].reset();
				$("#orderID").val(res.orderID);
				$('.timePick[name="from"]').val(self.orderDate+" "+self.startTime);
				if(!self.endDate){
					$('.timePick[name="until"]').val(self.orderDate+" "+self.endTime);
				}else{
					$('.timePick[name="until"]').val(self.endDate+" "+self.endTime);
				}
				$(".orderFormPop").css('display','flex');

			}
		})
	}
	

});


$('.days-table .l-side .rooms.shifts .row').on('click', function() {
	//debugger;
	openNewShift(this);
});


function openNewShift(elem){
	//alert('shiftpop');
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
                            ,siteID:siteID
                            ,OrderIDS:OrderIDS
                            };
		openShiftForm(data);
		


	
		window.event.cancelBubble = true;
	}
}


function openShiftForm(data){
	debugger;
	$.post('ajax_shiftFrom.php',{data:data},function(res){
		$("#orderLside").append(res);
	}).done(function(){
	
		$('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 15
		});
		

	});
}

function insertShift(ele){
        
        var con = true;
        
        var the_input_data = $('#orderForm').serializeArray();
        var time_units = [];
                
        $(ele).parent().parent().find('.the_shift_zonesss').find('.time_units_row').each(function(){
            
            var start_time = "";
            var end_time = "";
            
            $(this).find('input').each(function(){
                if ($(this).attr("name") == "startTime[]") {start_time = $(this).val();}
                if ($(this).attr("name") == "endTime[]") {end_time = $(this).val();}
            });
            
            var unit_rec = {
                'start_time':start_time,
                'end_time':end_time
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
            swal.fire({icon: 'error',title: res.error});
            $('.holder').hide();
		}
	
	},"JSON");
        
        
        } else {
            Swal.fire("נא לציין שעת סיום יותר גבוהה משעת התחלה");
        }
}

function more_shifts(ele) {
    
    var last_end = "00:00";
    
    $(ele).parent().parent().find('.the_shift_zonesss').find('.time_units_row').each(function(){
            
            var start_time = "";
            var end_time = "";
            
            $(this).find('input').each(function(){
                if ($(this).attr("name") == "startTime[]") {start_time = $(this).val();}
                if ($(this).attr("name") == "endTime[]") {end_time = $(this).val();}
            });
            
            last_end = end_time;
            
            
        });
    
    
    var svgclock = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>';
    var svg_remove = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M14.59 8L12 10.59 9.41 8 8 9.41 10.59 12 8 14.59 9.41 16 12 13.41 14.59 16 16 14.59 13.41 12 16 9.41 14.59 8zM12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>';
					
    var html = "";
    html += "<div class='time_units_row the_res'>";
    
    html += "<div class='the_remove_but' onclick=\"$(this).parent().remove();\">"+svg_remove+"</div>";
    
    html += "<div class='inputWrap half date time'>";
    html += "<input type='text' value='"+last_end+"' name='startTime[]' class='timePick' readonly>";
    html += svgclock;
    html += "<label for='from'>שעת כניסה</label>";
    html += "</div>";
    
    html += "<div class='inputWrap half date time'>";
    html += "<input type='text' value='"+last_end+"' name='endTime[]' class='timePick' readonly>";
    html += svgclock;
    html += "<label for='from'>שעת עזיבה</label>";
    html += "</div>";
    html += "</div>";
    
    $('.the_shift_zonesss').append(html);
    
    $('.timePick').datetimepicker({
			datepicker: false,
			format: 'H:i',
			step: 15,
                        setMin: last_end
		});
    
}

/*
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
	

}*/