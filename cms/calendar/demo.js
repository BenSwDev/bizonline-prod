/* freeDates = array(siteID => array(month => array(array(freeunits), unit2, ...) ) ) */
var calendars = {}, freeDates = {s5:{'2016-06':[[0,1],[],[0,5,5,5,5,3],[]]}};

$(document).ready( function() {
    var thisMonth = moment().format('YYYY-MM');
    var eventArray = [];
    calendars.clndr1 = $('.cal1').clndr({
    	daysOfTheWeek: ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'],
    	template: $("#calTemp").html(),
        events: eventArray,
        extras: {
        	free: freeDates.s5 || (freeDates.s5 = {}),
        	units: [2,3,6,4]
        },
        clickEvents: {
            click: function (target) {
                console.log('Cal-1 clicked: ', target);
            },
            today: function () {
                console.log('Cal-1 today');
            },
            nextMonth: function () {
                console.log('Cal-1 next month');
            },
            previousMonth: function () {
                console.log('Cal-1 previous month');
            },
            onMonthChange: function () {
                console.log('Cal-1 month changed');
            },
            nextYear: function () {
                console.log('Cal-1 next year');
            },
            previousYear: function () {
                console.log('Cal-1 previous year');
            },
            onYearChange: function () {
                console.log('Cal-1 year changed');
            },
            nextInterval: function () {
                console.log('Cal-1 next interval');
            },
            previousInterval: function () {
                console.log('Cal-1 previous interval');
            },
            onIntervalChange: function () {
                console.log('Cal-1 interval changed');
            }
        },
        multiDayEvents: {
            singleDay: 'date',
            endDate: 'endDate',
            startDate: 'startDate'
        },
        showAdjacentMonths: true,
        adjacentDaysChangeMonth: false,
        doneRendering: function(){
        	var prev = moment(this.month).subtract(1,'M').format('YYYY-MM'), next = moment(this.month).add(1,'M').format('YYYY-MM'), list = this.options.extras.free;
        	
        	list[prev] || $.getJSON( "/cms/calendar/js_freeDates.php?m=" + prev, function(resp){
				list[prev] = resp.free
        	});
        	
        	list[next] || $.getJSON( "/cms/calendar/js_freeDates.php?m=" + next, function(resp){
				list[next] = resp.free
        	});
        }
    });

    // Bind all clndrs to the left and right arrow keys
    $(document).keydown( function(e) {
        // Left arrow
        if (e.keyCode == 37) {
            calendars.clndr1.back();
        }

        // Right arrow
        if (e.keyCode == 39) {
            calendars.clndr1.forward();
        }
    });
});