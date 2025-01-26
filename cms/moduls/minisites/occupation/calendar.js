String.prototype.toDate || (String.prototype.toDate = function(){
    var a = this.split(/\D/);
    return (a.length == 3) ? ((a[0] > 100) ? new Date(a[0], a[1] - 1, a[2]) : new Date(a[2], a[1] - 1, a[0])) : null;
});

Date.prototype.toUnixDate || (Date.prototype.toUnixDate = function(){
    return [this.getFullYear(), ('0' + (this.getMonth() + 1)).substr(-2), ('0' + this.getDate()).substr(-2)].join('-');
});

Date.prototype.add || (Date.prototype.add = function(num, unit){
    var d = new Date(this);
    switch(unit){
        case 'y': case 'Y': d.setFullYear(this.getFullYear() + num); break;
        case 'm': case 'M': d.setMonth(this.getMonth() + num); break;
        case 'd': case 'D': default: d.setDate(this.getDate() + num); break;
    }
    return d;
});

Date.prototype.nextSunday || (Date.prototype.nextSunday = function(){
    return this.getDay() ? this.add(7 - this.getDay()) : new Date(this);
});

Date.prototype.lastSunday || (Date.prototype.lastSunday = function(){
    return this.getDay() ? this.add(-this.getDay()) : new Date(this);
});

console.log('oh...');

function CalendarWeek(units, options) {
    this.rooms    = {};
    this.settings = {
        sunday: ''
    };
    $.extend(this.settings, options);

    this._buildBase(units);
}
CalendarWeek.weekdays = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];

$.extend(CalendarWeek.prototype, {
    // returns array with 7 string dates in unix format, starting from sunday
    _genWeekdDates: function(sunday){       // Date sunday
        return $.map(CalendarWeek.weekdays, function(name, i){
            return sunday.add(i).toUnixDate();
        });
    },

    // generates html for dates row
    _genDatesHtml: function(dates, holidays){
        return '<div class="tblCell"></div>' + $.map(CalendarWeek.weekdays, function(name, i){
            var cd = dates[i], ca = cd.split('-'), hd = (holidays && holidays[cd]) ? '<div class="specialDate" style="width:' + (100 * holidays[cd].days) + '%">' + holidays[cd].name + '</div>' : '';

            return '<div class="tblCell"><div class="fLine">' + ca[2] + '/' + ca[1] + '</div><div class="sLine">' + name + '</div>' + hd + '</div>';
        }).join('') + '<div class="rArrowWeek"><i class="fa fa-angle-right"></i></div><div class="lArrowWeek"><i class="fa fa-angle-left"></i></div>';
    },

    // generates html for unit summary row
    _genSummaryHtml: function(dates){
        return '<div class="tblCell">כל היחידות</div><div class="mobTtl">כל היחידות</div>' + $.map(CalendarWeek.weekdays, function(name, i){
            return '<div class="tblCell" data-date="' + dates[i] + '"><div class="free"><div class="num">0</div><div class="label">פנוי</div></div><div class="occ"><div class="num">0</div><div class="label">תפוס</div></div></div>';
        }).join('');
    },

    // generates jQuery object with empty calendar table for current week
    _buildBase: function(units){
        var self = this, dateList = this._genWeekdDates(this.settings.sunday.toDate()), $scroll, i;

        this.cont = $('<div class="calendar"><div class="tableRow date">' + this._genDatesHtml(dateList) + '</div>' +
                      '<div class="tableRow sum">' + this._genSummaryHtml(dateList) + '</div>' +
                      '<div class="divScroll"></div></div>');

        $scroll = this.cont.find('.divScroll');

        this.cont.find('.tableRow.date .fa')
            .filter('.fa-angle-left').click(function(){
                //self.settings.caller && self.settings.caller.getWeek && self.settings.caller.showWeek(self.settings.sunday.toDate().add(7).toUnixDate(), 'left');
                self.settings.caller && self.settings.caller.calendar && self.settings.caller.calendar.change(self.settings.sunday.toDate().add(7), 'left');
            }).end()
            .filter('.fa-angle-right').click(function(){
                //self.settings.caller && self.settings.caller.getWeek && self.settings.caller.showWeek(self.settings.sunday.toDate().add(-7).toUnixDate(), 'right');
                self.settings.caller && self.settings.caller.calendar && self.settings.caller.calendar.change(self.settings.sunday.toDate().add(-7), 'right');
            }).end();

        $.each(units, function(i, unit){
            self.rooms[unit.id] = new CalendarRoom(dateList, unit, self);
            $scroll.append(self.rooms[unit.id].getHTML());
        });

        for(i = 0; i < dateList.length; ++i)
            this.updateCount(dateList[i]);
    },

    updateCount: function(date, unit){
        var cnt = {free: 0, occ: 0}, cont = this.cont.find('.tableRow.sum .tblCell[data-date="' + date + '"]'), caller = this.settings.caller;

        $.each(this.rooms, function(id, room){
            var c = room.getCount(date);

            cnt.free += c.free;
            cnt.occ  += c.occ;

            if (unit == id && caller && caller.setFree)
                caller.setFree(date, id, c.free);
        });

        $.each(cnt, function(key, num){
            cont.find('.' + key + ' > .num').text(num);
        });
    }
});



function CalendarRoom(dates, room, week){
    var Room = this, i;

    this.cont = {
        total: null,
        units: $()
    };

    this.room  = room;
    this._buildHTML(dates, room);

    this.cont.total.find('.fa-angle-right').on('click', function(){
        var date = $(this).closest('.roomTotal').data('date');
        Room.setFree(date).calculate(date);
    });

    this.cont.total.find('.fa-angle-left').on('click', function(){
        var date = $(this).closest('.roomTotal').data('date');
        Room.setOccupied(date).calculate(date);
    });


    this.cont.total.find('.topCellSite.header').on('click', function(){
        Room.cont.units.toggleClass('open');
		$(this).toggleClass('open');
    });

    this.cont.units.on('click', '.free, .occ', function(e){
        var elem = $(this), date = $(this.parentNode).data('date');
        elem.hasClass('occNames') || Room[elem.hasClass('free') ? 'setOccupied' : 'setFree'](this).calculate(date);
    });

    // setting bookings data
    if (room.orders && room.orders.length)
        $.each(room.orders, function(i, order){
            Room.addOrder(order);
        });

    // setting initial occupancy data
    if (room.empty)
        $.each(room.empty, function(date, num){
            if (num < room.units){
                var occ = Room.cont.units.find('.dateCell[data-date="' + date + '"] .occ').length;
                (occ >= room.units - num) || Room.setOccupied(date, room.units - num - occ);
            }
        });

    for(i = 0; i < dates.length; ++i)
        this.calculate(dates[i]);

    this.week  = week;      // should be after "build" function to avoid extra calling
}
$.extend(CalendarRoom.prototype, {
    _genUnitHtml: function(dates, id, i){
        return $('<div class="tableRow siteOcc open" data-unit-id="' + id + '"><div class="tblCell"><div class="siteNum">' + i + '</div></div>' +
                $.map(dates, function(date){
                    return '<div class="tblCell dateCell" data-date="' + date + '"><div class="event">&nbsp;</div><div class="free">פנוי</div></div>';
                }).join('') + '</div>');
    },

    _buildHTML: function(dates, room){
        this.cont.total = $('<div class="tableRow site"><div class="tblCell"><div class="topCellSite header open"><div class="siteTDetails">' + room.name + '</div></div></div><div class="mobTtl">' + room.name + '<i class="fa fa-times"></i><span>' + room.units + '</span></div>' +
                          $.map(dates, function(date){
                              return '<div class="tblCell roomTotal" data-date="' + date + '" data-count="{free:' + room.units + ', occ:0}"><div class="topCellSite open"><div class="siteTDetails">' +
                                     '<div class="unitDet"><div class="num freeTotal">' + room.units + '</div><div class="lbl">פנוי</div><div class="freeArr"><i class="fa fa-angle-right"></i></div></div>' +
                                     '<div class="unitDet"><div class="num occTotal">0</div><div class="lbl">תפוס</div><div class="occArr"><i class="fa fa-angle-left"></i></div></div>' +
                                     '<div class="sep"></div><div class="all">' + room.units + '</div></div></div></div>';
                          }).join('') + '</div>');

        for (var i = 0; i < room.units; ++i)
            this.cont.units = this.cont.units.add(this._genUnitHtml(dates, room.id, i + 1));
    },

    getHTML: function(){
        return this.cont.total.add(this.cont.units);
    },

    getCount: function(date){
        return this.cont.total.find('.roomTotal[data-date="' + date + '"]').data('count');
    },

    calculate: function(date){
        var cnt = {free: 0, occ: 0}, total = this.cont.total.find('.roomTotal[data-date="' + date + '"]'), list = this.cont.units.find('.dateCell[data-date="' + date + '"]');

        $.each(cnt, function(key){
            cnt[key] = list.find('.' + key).length;
            total.find('.' + key + 'Total').text(cnt[key]);
        });

        total.data('count', cnt);
        this.week && this.week.updateCount(date, this.room.id);

        return this;
    },

    setFree: function(sel, mult){
        var e = (typeof sel == 'string') ? this.cont.units.find('.dateCell[data-date="' + sel + '"] .occ:not(.occNames)').slice(mult ? -mult : -1) : $(sel);
        e.length && e.removeClass('occ').addClass('free').text('פנוי');

        return this;
    },

    setOccupied: function(sel, mult){
        var e = (typeof sel == 'string') ? this.cont.units.find('.dateCell[data-date="' + sel + '"] .free').slice(0, mult || 1) : $(sel);
        e.length && e.removeClass('free').addClass('occ').text('תפוס');

        return this;
    },

    addOrder: function(order){
        var select = [], start = order.start.toDate(), i, list;

        for(i = 0; i < order.nights; ++i)
            select.push('.dateCell[data-date="' + start.add(i).toUnixDate() + '"]:has(.free)');

        list = this.cont.units.find(select.join(' + '));
        if (list.length){
            select = select.join(', ').split(':has(.free)').join('');
            list   = $(list[0].parentNode).children(select);

            this.setOccupied(list.find('.free'));
            list.filter('.dateCell[data-date="' + order.start + '"]').find('.occ').addClass('occNames').css('width', 'calc(' + (100 * order.nights) + '% - ' + (order.nights > 1 ? 5 : 0) + 'px)').text(order.title);
        }
    }
});


function CalendarMonth(settings, caller){
    this.hebNames = ['ינואר','פברואר','מרץ','אפריל','מאי','יוני','יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר'];

    this.html = $('.monthName', settings.cont);
    this.date = new Date(0);

    this.change = function(d, dir){
        if (this.date != d){
            this.date = d.lastSunday();

            this.html.text(this.hebNames[this.date.getMonth()]);
            caller.showWeek && caller.showWeek(this.date.toUnixDate(), dir);

            this.date.setDate(1);
        }
    };

    this.nextMonth = function(){
        this.change(this.date.add(1,'m').nextSunday(), 'left');
    };

    this.prevMonth = function(){
        this.change(this.date.add(-1,'m').nextSunday(), 'right');
    };

    if (settings.date)
        this.change(settings.date.toDate(), '-nw');

    $('.rArrowMonth', settings.cont).on('click', $.proxy(this.prevMonth, this));
    $('.lArrowMonth', settings.cont).on('click', $.proxy(this.nextMonth, this));
}

function CalendarOrderPop(settings, caller){
    this.from   = $(settings.from, settings.cont);
    this.till   = $(settings.till, settings.cont);
    this.nights = $(settings.nights, settings.cont);

    this.rooms  = $(settings.rooms, settings.cont);
}

function CalendarPopRoom(elem){
    var room = this;

    this.chosen = 0;

    this.cont    = $(elem);
    this.sub     = $('.text > .desc', elem);
    this.counter = new CalendarCounter({
        cont : $('.inputs', elem),
        max  : 0,
        callback: function(cnt){
            room.chosen = cnt;
            cnt ? room.cont.addClass('chosen') : room.cont.removeClass('chosen');
        }
    });
}
$.extend(CalendarPopRoom.prototype, {
    setMax: function(max){
        this.counter.minMax(0, max);
        (max > 0) ? this.cont.removeClass('notavailable') : this.cont.addClass('notavailable');
        this.cont.find('.desc').text(max + ' פנויות בין התאריכים');
    },

    room_id: function(){
        return this.cont.data('roomId');
    }
});



function CalendarCounter(settings){
    var self = this, cont = $(settings.cont);

    this.counter = settings.count || 0;
    this.callback = settings.callback || $.noop;

    this.min = settings.min || 0;
    this.max = settings.max || Number.MAX_VALUE;

    this.plus  = $('.fa-plus-circle', cont);
    this.minus = $('.fa-minus-circle', cont);
    this.input = $('input', cont);

    if (settings.refresh)
        this.refresh = settings.refresh;

    cont.find('.fa-minus-circle').on('click', function(){
        self.counter = Math.max(self.min, self.counter - 1);
        self.refresh(self.counter);
        self.callback(self.counter);
    });

    cont.find('.fa-plus-circle').on('click', function(){
        self.counter = Math.min(self.max, self.counter + 1);
        self.refresh(self.counter);
        self.callback(self.counter);
    });
}
$.extend(CalendarCounter.prototype, {
    refresh: function(cnt){
        this.input.length && this.input.val(cnt);
        (cnt <= this.min) ? this.minus.addClass('disabled') : this.minus.removeClass('disabled');
        (cnt >= this.max) ? this.plus.addClass('disabled') : this.plus.removeClass('disabled');
    },

    set: function(val, noback){
        this.counter = Math.max(this.min, Math.min(this.max, val));
        this.refresh(this.counter);
        noback || this.callback(this.counter);
    },

    minMax: function(min, max){
        this.min = min;
        this.max = max;

        var cnt = Math.max(min, Math.min(max, this.counter));
        (cnt != this.counter) ? this.set(cnt) : this.refresh(cnt);
    }
});