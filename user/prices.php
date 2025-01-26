<?php
	include_once "auth.php";

	$siteID = SITE_ID; //(intval($_GET['siteID'])?intval($_GET['siteID']):$_SESSION['siteManager']['siteID']);
	
	$que = "SELECT roomID,roomName FROM rooms WHERE  siteID=".$siteID." and rooms.active=1 ORDER BY showOrder";//`active`=1 AND
	$room = udb::full_list($que);

	$que = "SELECT * FROM sites_periods WHERE siteID = " . $siteID . " AND (`periodType` > 0 OR `dateTo` >= '" . date('Y-m-d') . "')";
	$periods = udb::key_list($que, 'periodType');

    $que = "SELECT h.*, p.periodID FROM `holidays` AS `h` LEFT JOIN `sites_periods` AS `p` ON (h.holidayID = p.holidayID AND p.siteID = " . $siteID . " AND p.periodType = 0) WHERE h.active = 1 AND h.dateEnd >= CURDATE() AND p.periodID IS NULL";
    $holidays = udb::key_row($que, 'holidayID');

    if (count($holidays)){
        $cross = udb::single_column("SELECT DISTINCT h.holidayID FROM `holidays` AS `h` INNER JOIN `sites_periods` AS `p` ON (p.siteID = " . $siteID . " AND p.periodType = 0 AND p.holidayID = 0 AND h.dateStart <= p.dateTo AND h.dateEnd >= p.dateFrom) WHERE h.holidayID IN (" . implode(',', array_keys($holidays)) . ")");
        if (count($cross))
            foreach($cross as $cr)
                unset($holidays[$cr]);
    }

	$sunday  = date('Y-m-d', strtotime('-' . date('w') . ' days'));

    $base1   = $periods[1][0];        // regular period
    $base2   = $periods[2][0];        // hot period
    $periods = array_merge($periods[0] ?? [], $holidays ?? []);

    usort($periods, function($a, $b){
        return strcmp($a['periodID'] ? $a['dateFrom'] : $a['dateStart'], $b['periodID'] ? $b['dateFrom'] : $b['dateStart']);
    });

    $for_js = [];
    foreach($periods as $period)
        $for_js[$period['dateFrom'] ?? $period['dateStart']] = $period['dateTo'] ?? $period['dateEnd'];

    $hebNames = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];

    $minRoomPeriod = udb::key_row("SELECT `weekday`, `minNights`, `minVoid` FROM `rooms_min_nights` WHERE `roomID` = 0 AND `periodID` = " . $base1['periodID'], 'weekday');
    $weekend = explode(',', $base1['weekend']);
?>
<!doctype html>
<html lang="he">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
	<!-- link rel="stylesheet" href="/css/owl.carousel.css" -->
    <link rel="stylesheet" href="assets/css/jquery.ui.all.css" />
    <link rel="stylesheet" href="assets/css/styleprices.css" />
	<link rel="stylesheet" href="assets/css/sweetalert2.min.css" />
    <!-- link rel="stylesheet" href="assets/addons/datetimepicker/jquery.datetimepicker.min.css" -->
	<script src="/assets/js/jquery-2.2.4.min.js?v=<?=time()?>"></script>
    <script src="/assets/js/jquery.ui.min.js"></script>
    <script src="/assets/js/jquery.ui.datepicker-he.js"></script>
    <script src="assets/addons/datetimepicker/jquery.datetimepicker.full.min.js"></script>
	<!-- script src="/assets/js/owl.carousel.min.js"></script -->
	<script src="assets/js/sweetalert2.min.js"></script>
	<script src="assets/js/website.js?v=20190611"></script>
    <script src="assets/js/local_loader.js"></script>
	<title></title>
    <style>
        .tabin.priceList {padding-top:0}
    </style>
</head>
<body style="">
	<div class="tabin priceList">
	
	
	<div class="popup" id="newPeriod" style="display:none;">
		<div class="popup_container">
            <div class="pop-close"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30"><path fill-rule="evenodd" fill="rgb(0, 0, 17)" d="M25.6 25.6C19.8 31.5 10.2 31.5 4.4 25.6 -1.5 19.8-1.5 10.2 4.4 4.4 10.2-1.5 19.8-1.5 25.6 4.4 31.5 10.2 31.5 19.8 25.6 25.6ZM5.7 5.7C0.5 10.8 0.5 19.2 5.7 24.3 10.8 29.5 19.2 29.5 24.3 24.3 29.5 19.2 29.5 10.8 24.3 5.7 19.2 0.5 10.8 0.5 5.7 5.7ZM20.6 10.7L16 15.2 20.6 19.8C21 20.2 21 20.7 20.6 21.1 20.3 21.4 19.7 21.4 19.3 21.1L14.8 16.5 10.5 20.8C10.2 21.1 9.6 21.1 9.2 20.8 8.9 20.4 8.9 19.9 9.2 19.5L13.5 15.2 9.2 10.9C8.8 10.6 8.8 10 9.2 9.7 9.5 9.3 10.1 9.3 10.5 9.7L14.8 14 19.4 9.4C19.7 9 20.3 9 20.6 9.4 21 9.7 21 10.3 20.6 10.7Z"></path></svg></div>
			<div class="popTitle">הוספת תקופה</div>
			<form method="post" action="">
				<div class="wrap_input" id="periodName">
					<input type="text" name="periodNameI" autocomplete="off" data-q-error="שם תקופה לא חוקי" data-validator="notEmpty,lessThen:61" id="periodNameI" class="form_inp error_input" />
					<label for="periodNameI" data-error="שם תקופה לא חוקי">שם תקופה</label>
				</div>
				<div class="wrap_input" id="periodStart">
					<input type="text" name="periodStartI" autocomplete="off" id="periodStartI" class="form_inp error_input" required />
					<label for="periodStartI">תאריך התחילה</label>
				</div>
				<div class="wrap_input" id="periodEnd">
					<input type="text" name="periodEndI" autocomplete="off" id="periodEndI" class="form_inp error_input" required />
					<label for="periodEndI">תאריך סיום</label>
				</div>
				<button class="npgo">יצירת תקופה חדשה</button>
			</form>
		</div>
	</div>

		<div style="margin-bottom:10px">תקופות</div>
		<div id="pricePeriods">
		<?php /* ?>
			<div class="item" data-period="תקופה רגילה" data-type="1" onClick="ajaxRoomsPrice(<?=$room[0]['roomID']?>,0,1)"><div></div></div>
			<div class="item" data-period="תקופה חמה" data-type="2" onClick="ajaxRoomsPrice(<?=$room[0]['roomID']?>,0,2)"><div></div></div>
		<?php */ ?>
            <div class="item callable active" data-period="מחיר רגיל" data-param="periodID" data-value="<?=$base1['periodID']?>"><div></div></div>
            <div class="item callable" data-period="<?=toHTML('תקופה "חמה"')?>" data-param="periodID" data-value="<?=$base2['periodID']?>"><div></div></div>
<?php
    foreach($periods as $period) {
        $periodID   = $period['periodID'] ?: -$period['holidayID'];
        $dateStr    = $period['periodID'] ? db2date($period['dateFrom'], '/', 2) . ' - ' . db2date($period['dateTo'], '/', 2) : db2date($period['dateStart'], '/', 2) . ' - ' . db2date($period['dateEnd'], '/', 2);
        $periodName = $period['periodID'] ? ($period['periodName'] ?: 'בין תאריכים') : $period['holidayName'];
?>
            <div class="item callable" data-from="<?=db2date($period['dateStart'], '/', 2)?>" data-till="<?=db2date($period['dateEnd'], '/', 2)?>" data-period="<?=htmlspecialchars($periodName, ENT_QUOTES)?>" data-param="periodID" data-value="<?=$periodID?>" data-refresh="true"><div><?=$dateStr?></div></div>
<?php
    }
?>
			
		</div>
		<div class="item addNew" style="margin-top:20px;line-height:34px;padding:0 10px" id="addPeriodBtn">
				הוספת תקופה חדשה
			</div>
		<div class="periodsPrices">

			<div class="r">
				<div class="pricecutfrom" style="display:none">סנכרון עם תקופה חמה</div>
				<div>הגדרות תקופה לפי ימים בשבוע</div>
				<div>מינימום לילות להזמנה החל מיום זה</div>
				<div>הזמנה של לילה אחד  אפשרית עד X ימים לפני </div>
				<div>יום זה נחשב לאמצ"ש / סופ"ש</div>
			</div>
			<div class="l" id="minRoom0" data-room="0">
<?php
    foreach($hebNames as $i => $dayName){
?>
				<div class="day">
					<div class="title"><?=$dayName?></div>
					<select id="day_0_<?=$i?>">
                        <option value="0">סגור</option>
<?php
        $selected = ($minRoomPeriod[$i]['minNights'] ?? 1);
        for($j = 1; $j <= 7; ++$j)
            echo '<option value="' . $j . '" ' . ($selected == $j ? 'selected="selected"' : '') . '>' . $j . '</option>';
?>
					</select>
					<select id="void_0_<?=$i?>">
						<option value="-1">סגור</option>
<?php
        $selected = ($minRoomPeriod[$i]['minVoid'] ?? 1);
        for($j = 0; $j <= 7; ++$j)
            echo '<option value="' . $j . '" ' . ($selected == $j ? 'selected="selected"' : '') . '>' . $j . '</option>';
?>
					</select>
                    <input type="checkbox" value="<?=$i?>" name="weekend[0][]" id="weekend_0_<?=$i?>" <?=(in_array($i, $weekend) ? 'checked="checked"' : '')?> style="display:none" />
					<label for="weekend_0_<?=$i?>" style="cursor:pointer">אמצ"ש<span>סופ"ש</span></label>
				</div>
<?php
    }
?>
			</div>
		</div>
		<div class="editRoomsPrices" id="rpDiv" style="position:relative"></div>
		<button id="buttonPriceForm" style="margin-top:20px;margin-right:20px;font-size:20px;line-height:38px">שמירה</button>
	</div>
	<div class="popup" id="dayPricePop" style="display:none">

	</div>

	<script type="text/javascript">
		var dayName = ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'];


function PriceCaller(){
    this.context = {};
    this.cache   = {};
}
$.extend(PriceCaller.prototype, {
    set: function(prm, value, must_refresh){
        if ($.type(prm) == 'object')
            $.extend(this.context, prm);
        else if (prm !== undefined && value !== undefined)
            this.context[prm] = value;
        else
            return;

        this.refresh(must_refresh);
    },

    refresh: function(force){
        var self = this;

        $('body').localLoader('show');
        this.call(force).then(function(data){
            $('#rpDiv').empty().html(data).find('.periodsPrices');
            $('#buttonPriceForm').show();
            $('body').localLoader('hide');
            setMinTriggers($('.periodsPrices', '#rpDiv'), self);
        });
    },

    call: function(force){
        var str = JSON.stringify(this.context), cache = this.cache;

        if (!force && cache[str])
            return Promise.resolve(cache[str]);

        return $.post('ajax_global.php', $.extend({act:'roomPrices'}, this.context)).then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return swal('שגיאה', 'לא מצליח לעדכן מחירים', 'error');
            return cache[str] = $(res.content);
        });
    },

    get_day: function(day){
        return $.post('ajax_global.php', $.extend({act:'roomPricesDay', day:day}, this.context)).then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return swal('שגיאה', 'לא מצליח לעדכן מחירים', 'error');
            return res;
        });
    },

    save_prices: function(data){
        return $.post('ajax_global.php', ($.type(data) == 'string') ? data + '&act=savePrices' : $.extend({act:'savePrices'}, data)).then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return swal('שגיאה', 'לא מצליח לעדכן מחירים', 'error');
            checkSync(res.sync);
            return res;
        });
    },

    clear: function(){
        $.each(this.cache, function(i, week){
            week.remove();
        });
        this.cache = {};

        return this;
    }
});

$(document).on('change', '.periodsPrices input[type=number]', function() {
	if($(this).val() == '2') {
		$(this).parent().addClass('on');
	} else {
		$(this).parent().removeClass('on');
	}
});

$(document).on('click', '.perRoomB', function() {
	$('.perRoom').toggleClass('open');
});

function checkSync(val){
    if (parseInt($("#sync").val()) < parseInt(val)){
        $('#pricePeriods').find('.item.active').click();
        return true;
    }
    return false;
}

function setMinTriggers(div, caller){
	
    $(div).find('select:not([name="sync"])').each(function(){
        var boss = $(this).data('rel');

        if (boss)
            $('#' + boss).data('slave', this);
    }).off('.min').on('change.min', function(){
        var self = $(this), ex = this.id.split('_'), rel = self.data('slave');

        if (rel)
            $(rel).val(this.value);

        $.post('ajax_global.php', {act:'saveMinNights', asite:<?=$siteID?>, periodID:caller.context.periodID, type:ex[0], roomID:ex[1], day:ex[2], value:this.value}).then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return swal('שגיאה', 'לא מצליח לעדכן תקופה', 'error');

            if (self.data('rel'))
                $('#' + self.data('rel')).data('slave', null);
        });
    });

    $(div).find('select[name="sync"]').on('change', function(){
        $.post('ajax_global.php', {act:'sync', asite:<?=$siteID?>, periodID:caller.context.periodID, sync:this.value}).then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return swal('שגיאה', 'לא מצליח לעדכן תקופה', 'error');
            $('#pricePeriods').find('.item.active').click();
        });
    });
}

$(document).on('click', '.month_botton', function(){
	$('.editRoomsPrices').toggleClass('open');
});

function linkedDP(item, linked, last){
    $(item).datepicker({
        minDate: 0,
        beforeShow: function(){
            var link = $(linked).val(), prekey = last ? 'maxDate' : 'minDate', pre = last ? {maxDate: null} : {minDate: 0}, tmp;

            if (link.length){
                pre[prekey] = link;

                tmp = link.split('/').reverse().join('-');

                $.each(periods, last ? function(from, till){
                    if (tmp < from){
                        pre.maxDate = from.split('-').reverse().join('/');
                        return false;
                    }
                } : function(from, till){
                    if (tmp > till)
                        pre.minDate = till.split('-').reverse().join('/');
                });
            }
            return pre;
        },
        beforeShowDay: function(day){
            var pd = [day.getFullYear(), ('0' + (day.getMonth() + 1)).substr(-2), ('0' + day.getDate()).substr(-2)].join('-'), res = [true, ''];
            $.each(periods, function(from, till){
                if (pd < from)
                    return false;
                if (pd <= till && pd >= from)
                    return (res = [false, '']) || false;
            });
            return res;
        }
    });
}

$(function(){
	
    var caller = new PriceCaller(), timer = null, periods = <?=json_encode($for_js, JSON_NUMERIC_CHECK)?>;

    caller.set({asite:<?=$siteID?>, <?=$base1['periodID']?'periodID:'.$base1['periodID'].',':''?> <?=$room[0]['roomID']?'roomID:'.$room[0]['roomID'].',':''?> week:'<?=$sunday?>'});


    $('#pricePeriods').children('.item').add('#addPeriodBtn').on('click', function(){
		
        if($(this).data('period')) {
            $(this).addClass('active').siblings('.active').removeClass('active');

			$('#minRoom0').localLoader('show');
			$.post('ajax_global.php', {act:'getMin', asite:<?=$siteID?>,  periodID:$(this).data('value')}).then(function(res){
				if (res.status === undefined || parseInt(res.status))
					return swal('שגיאה', 'לא מצליח לעדכן תקופה', 'error');

				$('#minRoom0').empty().html(res.html).localLoader('hide');
                $('.pricecutfrom')[res.sync ? 'show' : 'hide']();

                window.setTimeout(function(){
                    $('#periodStartE').datepicker({
                        minDate: 0,
                        beforeShowDay: function(day){
                            var pd = [day.getFullYear(), ('0' + (day.getMonth() + 1)).substr(-2), ('0' + day.getDate()).substr(-2)].join('-'), res = [true, ''], vd = this.value.split('/').reverse().join('-');
                            $.each(periods, function(from, till){
                                if (pd < from)
                                    return false;
                                if (pd <= till && pd >= from && !(vd <= till && vd >= from))
                                    return (res = [false, '']) && false;
                            });
                            return res;
                        }
                    });

                    $('#periodEndE').datepicker({
                        minDate: 0,
                        beforeShow: function(){
                            var start = $('#periodStartE').val(), pre = {maxDate: null}, vd = this.value.split('/').reverse().join('-'), tmp;

                            if (start.length){
                                pre.minDate = start;

                                tmp = start.split('/').reverse().join('-');
                                $.each(periods, function(from, till){
                                    if (tmp < from && vd != till){
                                        pre.maxDate = from.split('-').reverse().join('/');
                                        return false;
                                    }
                                });
                            }
                            return pre;
                        },
                        beforeShowDay: function(day){
                            var pd = [day.getFullYear(), ('0' + (day.getMonth() + 1)).substr(-2), ('0' + day.getDate()).substr(-2)].join('-'), res = [true, ''], vd = this.value.split('/').reverse().join('-');
                            $.each(periods, function(from, till){
                                if (pd < from)
                                    return false;
                                if (pd <= till && pd >= from && !(vd <= till && vd >= from))
                                    return (res = [false, '']) && false;
                            });
                            return res;
                        }
                    });
                }, 1000);

				setMinTriggers('#minRoom0', caller);
			});
		} else {
			$('#newPeriod').fadeIn('fast');
		}
    });

    $('body > .priceList').on('click', '.callable', function(){
        var data = $(this).data();
        caller.set(data.param, data.value, data.refresh);
	/*if($(this).data('period') == 'מחיר רגיל' || $(this).data('period') == 'תקופה "חמה"') {
			setTimeout(function() {
				$('.pricecutfrom').hide();
				$('select[name="periodprice"]').hide();
			}, 200);
		} else {
			setTimeout(function() {
				$('.pricecutfrom').show();
				$('select[name="periodprice"]').show();
			}, 200);
		}
		console.log($(this).data('period'));*/
    }).on('click', '.editRoomsPrices-day:not(.newPeriod)', function(){
        caller.get_day($(this).data('index')).then(function(res){
            $('#dayPricePop').empty().html(res.content).fadeIn()
                .find('.pop-close').on('click', function(){
                    $('#dayPricePop').fadeOut();
                }).end()
                .find('.updateButton').on('click', function(){
                    var form = $(this).closest('form'), prm = $.extend({act:'savePricesDay'}, form.data('context'), form.serializeArray().reduce(function(obj, curr){
                        obj[curr.name] = curr.value;
                        return obj;
                    }, {}));

                    $.post('ajax_global.php', prm).then(function(res){
                        if (res.status === undefined || parseInt(res.status))
                            return swal('שגיאה', 'לא מצליח לעדכן מחירים', 'error');

                        checkSync(res.sync) || caller.clear().refresh(true);
                        $('#dayPricePop').fadeOut();
                    });
                });
        });
    });

    $('#buttonPriceForm').on('click', function(){
        caller.save_prices($('#pricesForm').serialize()).then(function(){
            caller.clear().refresh(true);
        });
    });

    $('#minRoom0').on('click', 'input[type="checkbox"]', function(){
        var we = $('#minRoom0').find('input[type="checkbox"]:checked').map(function(){return this.value;}).get();

        $.post('ajax_global.php', {act:'saveWeekend', asite:<?=$siteID?>, periodID:caller.context.periodID, weekend:we}).then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return swal('שגיאה', 'לא מצליח לעדכן תקופה', 'error');

            timer && window.clearTimeout(timer);
            timer = window.setTimeout(function(){
                checkSync(res.sync) || caller.clear().refresh(true);
            }, 600);
        });
    });

    $(document).on('click', '.editPeriodPopup .npgo', function(e){
        $.post('ajax_global.php', $(this).parents('form').serialize() + '&act=editPeriod&asite=<?=SITE_ID?>').then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return swal('שגיאה', res.error || 'לא מצליח לערוך תקופה', 'error');
			window.location.reload();
        });
        return false;
    });

    $(document).on('click', '.editPeriodPopup .removeButton', function(e){
		var pid = $(this).data('pid');
		if(pid) {
        $.post('ajax_global.php', $(this).parents('form').serialize() + '&act=removePeriod&asite=<?=SITE_ID?>').then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return swal('שגיאה', res.error || 'לא מצליח למחוק תקופה', 'error');
			window.location.reload();
        });
		}
        return false;
    });
	
    $('#newPeriod').find('button.npgo').on('click', function(){
        $.post('ajax_global.php', $(this.form).serialize() + '&act=newPeriod&asite=<?=SITE_ID?>').then(function(res){
            if (res.status === undefined || parseInt(res.status))
                return swal('שגיאה', res.error || 'לא מצליח לצור תקופה', 'error');
            window.location.reload();
        });
        return false;
    }).end().find('.pop-close').on('click', function(){
        $(this.parentNode).find('input').val('');
        $('#newPeriod').fadeOut('fast');
    });

    $('#periodStartI').datepicker({
        minDate: 0,
        beforeShow: function(){
            var end = $('#periodEndI').val(), pre = {minDate: 0}, tmp;

            if (end.length){
                pre.maxDate = end;

                tmp = end.split('/').reverse().join('-');

                $.each(periods, function(from, till){
                    if (tmp > till)
                        pre.minDate = till.split('-').reverse().join('/');
                });
            }
            return pre;
        },
        beforeShowDay: function(day){
            var pd = [day.getFullYear(), ('0' + (day.getMonth() + 1)).substr(-2), ('0' + day.getDate()).substr(-2)].join('-'), res = [true, ''];
            $.each(periods, function(from, till){
                if (pd < from)
                    return false;
                if (pd <= till && pd >= from)
                    return (res = [false, '']) || false;
            });
            return res;
        }
    });

    $('#periodEndI').datepicker({
        minDate: 0,
        beforeShow: function(){
            var start = $('#periodStartI').val(), pre = {maxDate: null}, tmp;

            if (start.length){
                pre.minDate = start;

                tmp = start.split('/').reverse().join('-');

                $.each(periods, function(from, till){
                    if (tmp < from){
                        pre.maxDate = from.split('-').reverse().join('/');
                        return false;
                    }
                });
            }
            return pre;
        },
        beforeShowDay: function(day){
            var pd = [day.getFullYear(), ('0' + (day.getMonth() + 1)).substr(-2), ('0' + day.getDate()).substr(-2)].join('-'), res = [true, ''];
            $.each(periods, function(from, till){
                if (pd < from)
                    return false;
                if (pd <= till && pd >= from)
                    return (res = [false, '']) || false;
            });
            return res;
        }
    });

    setMinTriggers('#minRoom0', caller);
});
	</script>
</body>
</html>
