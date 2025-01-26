<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top.php";







if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
		
	}
    catch (LocalException $e){
        // show error
    } 
}


$where ="1 = 1 ";



if($_GET['signTime']!=""){
	$where .= " AND `ag_agreements`.`createdTime` LIKE '%".intval($_GET['signTime'])."%'"; 
}


if($_GET['free']!=""){
	$where .= " AND `ag_agreements`.`customer_name` LIKE '%".$_GET['free']."%'"; 
}



$pager = new CmsPager;
$que = "SELECT SQL_CALC_FOUND_ROWS ag_agreements.* FROM ag_agreements 
WHERE " . $where . " ORDER BY `agid` DESC ". $pager->sqlLimit();
$reviews = udb::full_list($que);


$pager->items_total = udb::single_value("SELECT FOUND_ROWS()");



?>

<style type="text/css">
	input[type="checkbox"]{width: 20px !important;height: 20px !important;-webkit-appearance: checkbox !important;}
	.manageItemfs table > thead > tr > th{width: auto !important;text-align: center;}
	.manageItems table > tbody > tr > td{text-align: center;}
	.filters{padding: 30px 10px;border: 1px solid #000;margin-top: 10px;display: inline-block;}
	.filters .inpWrap{display: inline-block;vertical-align: top;margin:0 10px ;}
	.filters .inpWrap .lbl{display: inline-block;vertical-align: top;line-height: 20px;}
	.filters .inpWrap select{display: inline-block;vertical-align: top;width: 100px;height: 20px;-webkit-appearance: menulist;}
	.filters  input[type="submit"]{width: 50px;cursor: pointer;background: #2aafd4;color: #fff;}
	.submiForm{float: left;width: 80px;line-height: 40px;cursor: pointer;background: #2aafd4;color: #fff;font-size: 18px;margin: 10px 0;}

</style>

<div class="popRoom"><div class="popRoomContent"></div></div>

<div class="manageItems" id="manageItems">
    <h1>חוות דעת</h1>

	<div class="searchCms">
		<form method="GET">
			<input type="text" name="free" placeholder="שם הלקוח" value="<?=$_GET['free']?>">
			<input type="text" name="signTime" style="width:auto;" class="datepicker" placeholder="תאריך חתימה">
			<a href="/cms/moduls/minisites/ag_agreements/index.php">נקה</a>
			<input type="submit" value="חפש">	
		</form>
	</div>
	<form method="post">
	<?=$pager->render()?>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>PDF</th>
			<th>חתימה</th>
			<th>שם הלקוח</th>
			<th>תאריך חתימה</th>
        </tr>
        </thead>
        <tbody id="sortRow">
		<?php
		if($reviews){
		foreach($reviews as $review) { ?> 
            <tr>
				<td><?=$review['agid']?></td>
				<td>
					<?php if($review['pdf']) { ?>
					<a target="_blank" href="<?=$review['pdf']?>">
					<svg xmlns="http://www.w3.org/2000/svg" version="1.2" viewBox="0 0 239 304" width="239" height="304"><style>.ss0 { fill: #e8e8e8 } .ss1 { fill: #fb3449 } .ss2 { fill: #a4a9ad } .ss3 { fill: #d1d3d3 } </style><path id="Layer" class="ss0" d="m238.3 50.5v252.7h-237.5v-303.2h187z"/><path id="Layer" fill-rule="evenodd" class="ss1" d="m198 161.5c-0.4 5.6-7.1 7.4-11.7 7.6q-3.1 0.1-6.1-0.4c-3.9-0.6-7.8-1.6-11.5-2.9-3.8-1.3-7.4-3-10.7-5.1-4.3-2.6-8.3-5.7-12-9.1q-7.8 0.7-15.3 1.9c-10.8 1.8-21.1 4.3-30.5 7.2q-0.1 0.1-0.1 0.1c-5.5 7.8-10.7 14.3-15.3 19.4-6.7 7.5-14.6 16.3-24.9 18.4-3.1 0.7-6.7 0.8-9.4-1.2-1.9-1.4-2.9-3.7-2.7-6.1 0.2-4.3 3.1-8.1 5.9-11 3-3.2 6.6-6 10.6-8.7 7.6-4.8 16.7-9 26.9-12.6 4.4-7.4 8.2-15.1 11.8-23q1.7-3.7 3.5-7.5c2.2-5.2 4.6-10.5 6.7-16.2q1.4-3.7 2.6-7.6-1.9-4.9-3.6-9.9c-1.1-3.7-2.2-7.3-2.9-11.2-0.6-3.8-0.8-7.7-0.5-11.5 0.4-4.8 1.7-11.6 6.5-14 4.1-2.1 7.7 2.1 9.1 5.5 3.9 10 3.5 21.8 2.3 32.3-0.7 5.7-1.8 11.2-3.1 16.4 5.1 11 11.4 21.5 19.2 30.7q1.9 2.2 3.9 4.2 1.9-0.2 3.8-0.3c3.5-0.2 7.1-0.4 10.7-0.4 4.6 0 9.3 0.3 13.8 1 5.8 0.9 18.4 2.9 22 9.4 0.8 1.4 1.1 2.9 1 4.6zm-110.5 3.6c-7.8 3.1-14.8 6.5-20.7 10.3-3.8 2.5-7.1 5.2-9.8 8-1.2 1.2-2.3 2.6-3.2 4.2-0.9 1.4-1.4 2.8-1.5 3.9 0 2.1 1.8 3 3.7 3.1 2.1 0.1 4.1-0.5 5.6-1 8-2.9 13.5-10.7 18.3-17.3q3-4 6.2-9.1 0.7-1 1.4-2.1zm28.5-71.5q0.8 2.4 1.7 4.8 0.5-1.9 1-3.9c1.8-8.2 3.2-17.6 1.7-26-0.3-1.4-1.1-6.7-3.5-6-1.5 0.5-2.4 3.7-2.8 5-0.5 1.6-0.9 3.3-1.1 5-0.3 3.4-0.2 7 0.4 10.5 0.5 3.5 1.5 7 2.6 10.6zm26.1 54.1q-1-1.1-2-2.3c-7.3-8.2-13.3-17.5-18.2-27.2-2.8 9.2-6.6 17.9-11.4 26.2-1 1.8-5.4 10.3-6.4 10.6 8.1-2.3 16.8-4.3 25.9-5.7q6-1 12.1-1.6zm48.5 11.1c-5.1-4.3-12.4-6.5-19-7.2-3.4-0.5-6.9-0.7-10.4-0.7-3.5-0.1-7 0.1-10.4 0.3 2.8 2.5 5.7 4.7 8.9 6.7 3.2 1.9 6.6 3.5 10.1 4.6 5.1 1.7 10.7 2.4 16 1.6 1.8-0.3 5.7-1.3 6-3.1 0.1-0.7-0.2-1.4-1.2-2.2z"/><path id="Layer" class="s1" d="m195.6 25.3h-194.8v-25.3h187z"/><path id="Layer" fill-rule="evenodd" class="ss2" d="m94.8 241.2q0 8-4.7 12.4-4.7 4.4-13.5 4.4h-3.6v15.9h-13v-48h16.6q9.1 0 13.7 4 4.5 3.9 4.5 11.3zm-21.8 6.2h2.3q3 0 4.7-1.7 1.7-1.6 1.7-4.5 0-4.8-5.4-4.8h-3.3z"/><path id="Layer" fill-rule="evenodd" class="s2" d="m143.2 248.9q0 12-6.6 18.5-6.6 6.5-18.6 6.5h-15.5v-48h16.6q11.5 0 17.8 5.9 6.3 5.9 6.3 17.1zm-13.4 0.4q0-6.6-2.6-9.8-2.7-3.1-8-3.1h-3.7v26.8h2.8q5.9 0 8.7-3.4 2.8-3.4 2.8-10.5z"/><path id="Layer" class="s2" d="m164.6 273.9h-12.8v-48h28.4v10.4h-15.6v9.2h14.4v10.4h-14.4z"/><path id="Layer" class="ss3" d="m187.8 0l50.5 50.5h-50.5z"/></svg>
					 <span style="display:inline-block;min-width:120px;">לחצו לצפייה בPDF</span></a></td>
					<?php } else { ?>
						<a href="/signature_zimmers_new.php?zguid=<?=$review['zguid']?>">
						<svg xmlns="http://www.w3.org/2000/svg" version="1.2" viewBox="0 0 239 304" width="239" height="304"><title>pdf-svg</title><defs><image width="512" height="512" id="img1" href="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE5LjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJMYXllcl8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCINCgkgdmlld0JveD0iMCAwIDUxMiA1MTIiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDUxMiA1MTI7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxwb2x5Z29uIHN0eWxlPSJmaWxsOiNGQkI0Mjk7IiBwb2ludHM9IjYwLjI5OSwzNTcuMTIgMzQuNzY4LDQzMC44NTEgODEuMTQ5LDQ3Ny4yMzIgMTU0Ljg4LDQ1MS43IDQ1Mi42NjksMTUzLjkxMSAzNTguMDg5LDU5LjMzIA0KCSIvPg0KPHBhdGggc3R5bGU9ImZpbGw6I0ZGNzU3QzsiIGQ9Ik00MDAuOTMxLDE2LjQ4N0wzNTguMDg5LDU5LjMzbDk0LjU4MSw5NC41ODFsNDIuODQyLTQyLjg0MmM4LjQwNC04LjQwNCw4LjQwNC0yMi4wMywwLTMwLjQzNA0KCWwtNjQuMTQ3LTY0LjE0N0M0MjIuOTYxLDguMDg0LDQwOS4zMzUsOC4wODQsNDAwLjkzMSwxNi40ODd6Ii8+DQo8cG9seWdvbiBzdHlsZT0iZmlsbDojODQ3QzdDOyIgcG9pbnRzPSIxMC4xOTksNTAxLjggODEuMTQ5LDQ3Ny4yMzIgMzQuNzY4LDQzMC44NTEgIi8+DQo8Zz4NCgk8cGF0aCBzdHlsZT0iZmlsbDojNEQ0RDREOyIgZD0iTTUzLjA4NywzNDkuOTA3Yy0xLjA5MSwxLjA5MS0xLjkyMSwyLjQxNi0yLjQyNSwzLjg3NWwtNTAuMSwxNDQuNjgNCgkJYy0xLjI3OCwzLjY5MS0wLjMzNiw3Ljc4OCwyLjQyNSwxMC41NUM0LjkzMiw1MTAuOTU3LDcuNTM5LDUxMiwxMC4yMDEsNTEyYzEuMTE4LDAsMi4yNDUtMC4xODQsMy4zMzYtMC41NjJsMTQ0LjY4MS01MC4xDQoJCWMxLjQ1OS0wLjUwNSwyLjc4My0xLjMzNSwzLjg3NS0yLjQyNWwzNDAuNjMxLTM0MC42MzFjMTIuMzY3LTEyLjM2OCwxMi4zNjctMzIuNDkxLDAtNDQuODU5TDQzOC41NzcsOS4yNzUNCgkJYy0xMi4zNjctMTIuMzY3LTMyLjQ5LTEyLjM2Ny00NC44NTcsMGwwLDBMNTMuMDg3LDM0OS45MDd6IE02NS4xMDQsMzc0LjQxNWw3Mi40ODEsNzIuNDgxbC01My43MzksMTguNjA5bC0zNy4zNTEtMzcuMzUxDQoJCUw2NS4xMDQsMzc0LjQxNXogTTI2LjcxMSw0ODUuMjg4bDEyLjM2NC0zNS43MDVsMjMuMzQyLDIzLjM0MkwyNi43MTEsNDg1LjI4OHogTTE1NS44NDgsNDM2LjMwOGwtNTAuNzgzLTUwLjc4M2wxMjUuMzgxLTEyNS4zODENCgkJYzMuOTgzLTMuOTgzLDMuOTgzLTEwLjQ0MSwwLTE0LjQyNWMtMy45ODMtMy45ODItMTAuNDQxLTMuOTgyLTE0LjQyNSwwTDkwLjY0MSwzNzEuMTAxbC0xNC45NDgtMTQuOTQ4TDM1OC4wODksNzMuNzU1DQoJCWw0MC4wNzgsNDAuMDc4bDQwLjA3OCw0MC4wNzhMMTU1Ljg0OCw0MzYuMzA4eiBNNDg4LjI5OSwxMDMuODU2bC0zNS42MywzNS42M0wzNzIuNTEzLDU5LjMzbDM1LjYzLTM1LjYzMQ0KCQljNC40MTQtNC40MTQsMTEuNTk3LTQuNDE0LDE2LjAxLDBsNjQuMTQ3LDY0LjE0N0M0OTIuNzE0LDkyLjI1OSw0OTIuNzE0LDk5LjQ0Myw0ODguMjk5LDEwMy44NTZ6Ii8+DQoJPHBhdGggc3R5bGU9ImZpbGw6IzRENEQ0RDsiIGQ9Ik0yNTQuMjY5LDIwNy40NzJsLTE0LjI3OSwxNC4yNzljLTMuOTgzLDMuOTgzLTMuOTgzLDEwLjQ0MSwwLDE0LjQyNQ0KCQljMS45OTIsMS45OTEsNC42MDIsMi45ODcsNy4yMTIsMi45ODdjMi42MSwwLDUuMjIxLTAuOTk3LDcuMjEyLTIuOTg3bDE0LjI3OS0xNC4yNzljMy45ODMtMy45ODMsMy45ODMtMTAuNDQxLDAtMTQuNDI1DQoJCUMyNjQuNzExLDIwMy40OSwyNTguMjUzLDIwMy40OSwyNTQuMjY5LDIwNy40NzJ6Ii8+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8L3N2Zz4NCg=="/></defs><style>.s0 { fill: #e8e8e8 } .s1 { fill: #d1d3d3 } </style><path id="Layer" class="s0" d="m238.3 50.5v252.7h-237.5v-303.2h187z"/><use id="pencil-svgrepo-com" href="#img1" transform="matrix(.332,0,0,.332,35,67)"/><path id="Layer" class="s1" d="m187.8 0l50.5 50.5h-50.5z"/></svg>
						<span style="display:inline-block;min-width:120px;">קישור לחתימה</span></a></td>
					<?php } ?>
				<td><a href="/<?=$review['signature']?>" target="_blank"><img src="/<?=$review['signature']?>" alt="" style="max-height:40px" /></a></td>

				<td><?=$review['customer_name']?></td>
				<td><?=$review['createdTime']?></td>
            </tr>
			<?php } } ?>
        </tbody>
    </table>
	</form>
	<style>
svg {width:50px;height:auto}
		</style>
</div>

<script>

	function openPop(pageID,siteID,domainID){
		$(".popRoomContent").html('<iframe id="frame_'+pageID+'" frameborder=0 src="/cms/moduls/minisites/reviews/frame.php?pageID='+pageID+'&siteID='+siteID+'&domainID='+domainID+'&tab=1"></iframe><div class="tabCloser" onclick="closeTab(\'frame_'+pageID+'\')">x</div>');
		$(".popRoom").show();
		
	}
	function closeTab(){
		$(".popRoomContent").html('');
		$(".popRoom").hide();
		
	}

	$(function() {
		$('.datepicker').datepicker({"dateFormat": "dd-mm-yy"});
	})

</script>
<?php

include_once "../../../bin/footer.php";
?>