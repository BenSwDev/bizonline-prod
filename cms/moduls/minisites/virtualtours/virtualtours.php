<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";
//include_once "../../../classes/class.BankGallery.php";


$fID =intval($_GET['fID']);
$tab=intval($_GET['tab']);
$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];

$langID   = LangList::active();
$domainID = DomainList::active();

if(intval($_GET['gdel'])){
	$galID = intval($_GET['gdel']);
	udb::query("DELETE FROM `virtualtours` WHERE id=".$fID." ");

	?>
	<script>window.location.href='/cms/moduls/minisites/virtualtours/virtualtours.php?siteID=<?=$siteID?>&tab=<?=$tab?>&siteName=<?=$siteName?>'</script>
	<?php
	exit;
}
if ('POST' == $_SERVER['REQUEST_METHOD']) {

   $data = typemap($_POST, [
		'virtualtours_title'   => 'string',
		'virtualtours_link'    => 'string',
		'virtualtours_desc'      => 'string',
		'siteID'    => 'int',
		'fID' => 'int'
	]);

	if (!$data['virtualtours_title'])
            throw new Exception('נא הכנס שם');

	$siteData = [
		'virtualtours_title'       => $data['virtualtours_title'],
		'virtualtours_link' => $data['virtualtours_link'],
		'virtualtours_desc' => $data['virtualtours_desc'],
		'siteID' => $data['siteID'],
	];

	if($data['fID']) {
		$fID = udb::update("virtualtours", $siteData , " id=".$fID);
	}
	else {
		$fID = udb::insert("virtualtours", $siteData);
	}


	// foreach(LangList::get() as $lid => $lang){
		////inserting/updating data in domains table
		// udb::insert('files_text', [
			// 'file_id'    => $fID,
			// 'langID'    => $lid,
			// 'ref'    => $folderID,
			// 'fileDesc'   => $data['desc'][$lid]
		// ], true);

	// }

	?>
	<script>window.location.href='/cms/moduls/minisites/virtualtours/virtualtours.php?siteID=<?=$siteID?>&tab=<?=$tab?>&siteName=<?=$siteName?>'</script>
	<?php
	exit;
}


$que="SELECT * FROM `virtualtours` WHERE id=".$fID;
$fileDb= udb::single_row($que);
//$fileLangs   = udb::key_row("SELECT * FROM `files_text` WHERE `files_text`.`file_id` = " . $fID, ['langID']);

?>
<div class="loader">
	<div class="spinner"></div>
</div>
<div class="editItems">
    <div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
	<?=showTopTabs(0)?>

<?php if($fID || intval($_GET['newgal'])==1){ ?>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
		<?=LangList::html_select()?>
	</div>
    <form method="POST" class="manageItems" enctype="multipart/form-data">
        <input type="hidden" name="siteID" id="siteID" value="<?=$siteID?>">
		<div class="insertWrap">
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת: </div>
					<input type="text" value="<?=outDb($fileDb['virtualtours_title'])?>" name="virtualtours_title" class="inpt">
				</div>
			</div>


			<div class="section txtarea big">
				<div class="inptLine">
					<div class="label noFloat">קישור</div>
					<textarea  name="virtualtours_link" class="inpt" style="direction:ltr" dir="ltr"><?=outDb($fileDb['virtualtours_link'])?></textarea>
				</div>
			</div>
			<div class="section txtarea big">
				<div class="inptLine">
					<div class="label noFloat">תיאור</div>
					<textarea class="textEditor" name="virtualtours_desc"><?=outDb($fileDb['virtualtours_desc'])?></textarea>
				</div>
			</div>
				<?php
				// foreach(LangList::get() as $id => $lang){
				//}
				?>


		</div>

		<div class="section sub">
			<div class="inptLine">
				<input type="hidden" id="fID" name="fID" value="<?=$fID?>">
				<input type="hidden" id="orderResult" name="orderResult" value="<?=$ids?>">
				<input type="submit" value="שמור" onClick="showLoader()" class="submit">
			</div>
		</div>


    </form>
		<script src="../../../app/tinymce/tinymce.min.js"></script>
		<script type="text/javascript">
			$(function(){
				tinymce.init({
					  selector: 'textarea.textEditor' ,
					  height: 'auto',
					 plugins: [
						"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
						"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
						"table contextmenu directionality emoticons textcolor paste  textcolor colorpicker textpattern"
					  ],
					  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
					  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
					  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
					  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking pagebreak restoredraft"

					});
					// $.each({domain: <?=$domainID?>, language: <?=$langID?>}, function(cl, v){
						// $('.' + cl).hide().each(function(){
							// var id = $(this).data('id');
							// $(this).find('input, select, textarea').each(function(){
								// this.name = this.name + '[' + id + ']';
							// });
						// }).filter('[data-id="' + v + '"]').show();

						// $('.' + cl + 'Selector').on('change', function(){
							// $('.' + cl, $(this).data('cont')).hide().filter('[data-id="' + this.value + '"]').show();
						// });
					// });
			});
		</script>
<?php }
else { ?>
	<div class="manageItems">
		<div class="addButton" style="margin-top: 20px;">
			<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="window.location.href='/cms/moduls/minisites/virtualtours/virtualtours.php?siteID=<?=$siteID?>&newgal=1&tab=<?=$tab?>&siteName=<?=$siteName?>'" >
		</div>
		<?php
			$que="SELECT * FROM `virtualtours` WHERE siteID=".$siteID;
			$files= udb::full_list($que);
			if($files){ ?>
		<table>
			<thead>
			<tr>

				<th>שם הסיור</th>
				<!-- <th>שייך ל-</th> -->

				<th width="60">&nbsp;</th>
			</tr>
			</thead>
			<tbody  id="sortRow">

			<?php foreach($files as $row) { ?>
				<tr id="<?=$row['id']?>">
					<td onclick="window.location.href='/cms/moduls/minisites/virtualtours/virtualtours.php?frame=<?=$frameID?>&siteID=<?=$siteID?>&fID=<?=$row['id']?>&tab=<?=$tab?>&siteName=<?=$siteName?>'"><?=$row['virtualtours_title']?></td>
					<!-- <td align="center"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td> -->
					<td align="center" class="actb">
					<div onclick="window.location.href='/cms/moduls/minisites/virtualtours/virtualtours.php?frame=<?=$frameID?>&siteID=<?=$siteID?>&fID=<?=$row['id']?>&tab=<?=$tab?>&siteName=<?=$siteName?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>
					<?php if(!$row['isMain']) { ?>
					|</div><div onClick="if(confirm('אתה בטוח??')){location.href='/cms/moduls/minisites/virtualtours/virtualtours.php?frame=<?=$frameID?>&siteID=<?=$siteID?>&fID=<?=$row['id']?>&tab=<?=$tab?>&siteName=<?=$siteName?>&gdel=<?=$row['id']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
					<?php } ?>
					</td>
				</tr>
			<? } ?>
			</tbody>
		</table>

		<? } ?>
	</div>
<input type="hidden" id="orderResult" name="orderResult" value="">
<script>
function orderNow(is){
	$("#addNewAcc").hide();
	$(is).val("שמור סדר תצוגה");
	$(is).attr("onclick", "saveOrder()");
	$("#sortRow td").attr("onclick", "");
	$("#sortRow").sortable({
		stop: function(){
			$("#orderResult").val($("#sortRow").sortable('toArray'));
		}
	});
	$("#orderResult").val($("#sortRow").sortable('toArray'));
}
function saveOrder(){
	var ids = $("#orderResult").val();
	$.ajax({
		url: 'js_order_virtualtours.php',
		type: 'POST',
		data: {ids:ids, siteID:<?=$siteID?>},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}

</script>
<?php } ?>
    <div id="alerts">
        <div class="container">
            <div class="closer"></div>
            <div class="title"></div>
            <div class="body"></div>
        </div>
    </div>
    <script src="<?=$root;?>/app/jquery-ui.min.js"></script>

    <script>


		function showLoader(){
			$('.loader').show();
		}

		$(".datePick").datepicker({
			format:"dd/mm/yyyy",
			changeMonth:true,
			changeYear:true
		});
    </script>

    </body>
    </html>
