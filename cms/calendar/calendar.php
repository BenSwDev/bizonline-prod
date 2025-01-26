<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$position=8;
if(intval($_GET['sID'])){
$siteID=intval($_GET['sID']);
} else {
$siteID=$_SESSION['siteID'];
}
$frameID=intval($_GET['frame']);


$menu = include "../sites/site_menu.php";

$que = "SELECT `dateFrom`, `dateTo`, `periodName` as `title` FROM `sitesPeriods` WHERE `siteID` = 0 AND `dateTo` >= CURDATE()";
$events = udb::full_list($que);

foreach($events as &$row)
	$row['title'] = outDb($row['title']);
unset($row);

$que = "SELECT `TITLE` FROM `sites` WHERE siteID = " . $siteID;
$siteName = udb::single_value($que);

$que = "SELECT `roomID`, `roomCount`, `roomName` FROM `sitesRooms` WHERE `siteID` = " . $siteID . " ORDER BY `showOrder`";
$rooms = udb::key_row($que, "roomID");

$units = array();
?>

<div class="editItems">
    <h1><?=outDb($siteName)?> - יומן תפוסה</h1>
	<?php if($_SESSION['permission']==100){ ?>
	<div class="miniTabs">
		<?php foreach($menu as $men){ 
		if($men['position']==$position && $men['sub']){
			$subMenu = $men['sub'];
		}
		?>
		<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><p><?=$men['name']?></p></div>
		<?php } ?>
	</div>
	<?php } ?>
    <div class="container">
		<div class="oc_updateFrees" onclick="openEditor(<?=$siteID?>, '<?=date("Y-m-d")?>')">עדכון תפוסה</div>
		<div class="updateFrees" style="display:none">
			<div class="close">x</div>
		</div>
		<div class="updateCalendar">
			<div class="cal0">
				<input type="hidden" id="roomID" value="<?=((count($rooms) > 1) ? -1 : key($rooms))?>" />
				<div class="unitsSlct">
<?php
	$bclass = '';
	$i = 0;

	if (count($rooms) > 1)
		echo '<div class="allUnits active" data-func="allunits" data-roomid="-1">כל היחידות</div>';
	else
		$bclass = 'active';

	foreach($rooms as $room){ 
		$units[] = $room['roomCount'];
		if($room['roomCount']>5) { $many=" many";}

		
?>
					<div data-func="unit<?=(++$i)?>" data-roomid="<?=$room['roomID']?>" class="<?=($i ? '' : $bclass)?>"><?=outDb($room['roomName'])?></div>
<?php
	}
?>
				</div>
			</div>
			<div class="cal1">
				
			<script type="text/template" id="calTemp">
				<div class="clndr-controls">
					<div class="clndr-control-button"><span class="clndr-previous-button">הקודם</span></div>
					<div class="month"><%= month %> <%= year %></div>
					<div class="clndr-control-button rightalign"><span class="clndr-next-button">הבא</span></div>
				</div>
				<table class="clndr-table<?=$many?>" border="0" cellspacing="0" cellpadding="0">
					<thead>
						<tr class="header-days">
							<% _.each(daysOfTheWeek, function (day) { %>
								<td class="header-day"><%= day %></td>
							<% }); %>
						</tr>
					</thead>
					<tbody id="calTbody">
						<% var i = 0; _.each(days, function (day) { %>
							<% var free = this.options.extras.free[day.date.format('YYYY-MM')]; %>
							<% if(i===0){%><tr><% } %>
							<% if(i===7 || i===14 || i===21 || i===28 || i===35){%></tr><tr><% } %>
							<td class="<%= day.classes %>" data-date="<%= day.date.format('YYYY-MM-DD') %>">
								<div class="day-contents"><%= day.day %><span><%= day.events.length ? day.events[0].title : "" %></span></div>
								
								<div class='units'>

								<% day.properties.isAdjacentMonth || _.each(this.options.extras.units, function(u, i){ %>
									<div class="u<%= u %> a<%= (free[i] ? free[i][day.day] : 0) %>"></div>
								<% }); %>

								</div>
							</td>
						<% i++ }, this); %>
						</tr>
					</tbody>
				</table>
			</script>
			</div>
        </div>
    </div>
</div>
<div id="loaderUserCal" class="loaderUser"></div>
<script src="<?=$root;?>/app/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>
<script src="moment.js"></script>
<script src="clndr.js"></script>
<script>



/* freeDates = array(siteID => array(month => array(array(freeunits), unit2, ...) ) ) */

var calendars = {};
//freeDates = {s<?=$siteID?>:{'<?=date("Y-m")?>':[[0,1],[],[0,5,5,5,5,3],[]]}};

$(document).ready( function() {




$(".unitsSlct > div").click(function(){
	$(".unitsSlct > div").removeClass("active");
	$(this).addClass("active");
	var cls = $(this).data("func");
	var rmid = $(this).data("roomid");

	$('#calTbody').attr('class', cls);
	$("#roomID").val(rmid);
	$(".updateFrees").html('');
});




createCalender();
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

function createCalender(){

    //var thisMonth = moment().format('YYYY-MM');
	 $.getJSON( "/cms/calendar/js_freeDates.php?siteID=<?=$siteID?>&m=<?=date('Y-m')?>", function(resp){
		

    calendars.clndr1 = $('.cal1').clndr({
		classes: {event: 'isHoliday'},
		events: <?=json_encode($events, JSON_UNESCAPED_UNICODE)?>,
    	daysOfTheWeek: ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'שבת'],
    	template: $("#calTemp").html(),
        extras: {
        	free: {'<?=date('Y-m')?>': resp.free},
        	units: [<?=implode(',',$units)?>]
        },
        clickEvents: {
            click: function (target) {
                console.log('Cal-1 clicked: ', target);
                //console.log(target.date._i);
				
				//var thisDate = target.element.attributes[0]['value'];
				var thisDate = target.date._i;

				openDataEdit(<?=$siteID?>, thisDate);
            }/*,
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
            }*/
        },
        multiDayEvents: {
            singleDay: 'date',
            endDate: 'dateTo',
            startDate: 'dateFrom'
        },
        showAdjacentMonths: true,
        adjacentDaysChangeMonth: false,
        doneRendering: function(){
        	var prev = moment(this.month).subtract(1,'M').format('YYYY-MM'), next = moment(this.month).add(1,'M').format('YYYY-MM'), list = this.options.extras.free;
        	
        	list[prev] || $.getJSON( "/cms/calendar/js_freeDates.php?siteID=<?=$siteID?>&m=" + prev, function(resp){
				list[prev] = resp.free
        	});
        	
        	list[next] || $.getJSON( "/cms/calendar/js_freeDates.php?siteID=<?=$siteID?>&m=" + next, function(resp){
				list[next] = resp.free
        	});
        }

	
		});

	});

}

var frameDataOpener=false;
function openDataEdit(siteID, dateTo){
	var roomID = $("#roomID").val();
	$("#loaderUserCal").show();
	$(".updateFrees").html('<iframe id="frame_'+roomID+'_'+siteID+'" height="320" frameborder=0 src="/cms/calendar/editdates.php?date='+dateTo+'&roomID='+roomID+'&siteID='+siteID+'"></iframe>');
	$(".updateFrees").show();
	$(".oc_updateFrees").html("סגור");
	frameDataOpener=true;

	window.setTimeout(function(){
		$("#loaderUserCal").hide();
	}, 300);
}

function closeTab(id){
	$(".updateFrees").html('');
	$(".updateFrees").hide();
}

function openEditor(siteID, dateTo){
	var roomID = $("#roomID").val();
	$("#loaderUserCal").show();
	if(frameDataOpener){
		$(".updateFrees").html("");
		$(".updateFrees").hide();
		$(".oc_updateFrees").html("עדכון תפוסה");
		frameDataOpener=false;
	} else {
		$(".updateFrees").html('<iframe id="frame_'+roomID+'_'+siteID+'" height="320" frameborder=0 src="/cms/calendar/editdates.php?date='+dateTo+'&roomID='+roomID+'&siteID='+siteID+'"></iframe>');
		$(".updateFrees").show();
		$(".oc_updateFrees").html("סגור");
		frameDataOpener=true;
	}
	window.setTimeout(function(){
		$("#loaderUserCal").hide();
	}, 300);
}

</script>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div