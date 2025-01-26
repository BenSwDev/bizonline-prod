

function more_shifts_week(ele,_get_type) {
    //debugger;
	if(_get_type=="break")
		_type=0;
	else
		_type=1;

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

    var type_name;
	if(_type){
		//new_start=last_end;
		type_name = "משמרת";
	}else{		
		//new_start=last_start;
		type_name = "הפסקה";
	}
    
    var svgclock = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><path d="M10 1C5 1 1 5 1 10 1 15 5 19 10 19 15 19 19 15 19 10 19 5 15 1 10 1ZM10 17C6.1 17 3 13.9 3 10 3 6.1 6.1 3 10 3 13.9 3 17 6.1 17 10 17 13.9 13.9 17 10 17ZM10.8 10L10.8 6.2C10.8 5.8 10.4 5.5 10 5.5 9.6 5.5 9.3 5.8 9.3 6.2L9.3 10.3C9.3 10.3 9.3 10.3 9.3 10.3 9.3 10.5 9.3 10.7 9.5 10.9L12.3 13.7C12.6 14 13.1 14 13.4 13.7 13.7 13.4 13.7 12.9 13.4 12.6L10.8 10Z"></path></svg>';
    var svg_remove = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M14.59 8L12 10.59 9.41 8 8 9.41 10.59 12 8 14.59 9.41 16 12 13.41 14.59 16 16 14.59 13.41 12 16 9.41 14.59 8zM12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>';
					
    var html = "";
    html += "<div class='time_units_row the_res "+_get_type+"'>";
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
    html += "<label for='from'>סוף"+type_name+"</label>";
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
			m: 30,
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
					


				});
				if(parseInt(start_time.replace(':',''))>=parseInt(end_time.replace(':',''))){
					console.log(parseInt(start_time) +" - "+ parseInt(end_time))
					con = false;
				}
				
				var unit_rec = {
					'start_time':start_time,
					'end_time':end_time,
					'status':_status
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
				m: 30,
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
