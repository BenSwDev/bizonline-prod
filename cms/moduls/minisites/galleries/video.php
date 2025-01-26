<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";
//include_once "../../../classes/class.BankGallery.php";

$tab = intval($_GET['tab']);
$fID=intval($_GET['fID']);
$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];

$langID   = LangList::active();
$domainID = DomainList::active();

$que = "SELECT `folderID` FROM `folder` WHERE isVideo = 1 AND siteID=".$siteID;
$folderID = udb::single_value($que);

if(!$folderID){

	$folderID = udb::insert("folder",["siteID" => $siteID, "isVideo" => 1, "folderTitle" => "גלריית וידאו ".$siteName]);
}

if ('POST' == $_SERVER['REQUEST_METHOD']) {

   $data = typemap($_POST, [
		'title'   => 'string',
		'link'    => 'html',
		'!webStar'     => 'int' ,
		'desc'      => ['int' => 'string']
	]);

	if (!$data['title'])
            throw new LocalException('נא הכנס שם');

	$siteData = [
		'title'       => $data['title'],
		'src' => $data['link'],
		'webStar'    => $data['webStar'],
		'table' => "folder",
	    'ref'  => $folderID,
		'video' => 1

	];
	$siteData['upload_date' ] = implode('-',array_reverse(explode('/',$_POST['upload_date'])));

	if($fID){
		udb::update("files", $siteData, "id =".$fID);
	} else {
		$fID = udb::insert("files", $siteData);
	}

	foreach(LangList::get() as $lid => $lang){
		// inserting/updating data in domains table
		udb::insert('files_text', [
			'file_id'    => $fID,
			'langID'    => $lid,
			'ref'    => $folderID,
			'fileDesc'   => $data['desc'][$lid]
		], true);

	}

	?>
	<script>window.location.href='/cms/moduls/minisites/galleries/video.php?siteID=<?=$siteID?>&tab=<?=$tab?>&siteName=<?=$siteName?>'</script>
	<?php
	exit;
}


if(intval($_GET['gdel'])){
	$fID = intval($_GET['gdel']);
//	$que="SELECT * FROM `files` WHERE ref=".$galID."";

	udb::query("DELETE FROM `files`  WHERE id=".$fID);
	udb::query("DELETE FROM `files_text` WHERE `file_id`=".$fID);

	?>
	<script>window.location.href='/cms/moduls/minisites/galleries/video.php?siteID=<?=$siteID?>&tab=<?=$tab?>&siteName=<?=$siteName?>'</script>
	<?php
	exit;
}
/*
	$que="SELECT * FROM `folder` WHERE siteID=".$siteID." AND isVideo=1 ORDER BY folderID ";
	$galleries= udb::full_list($que);

*/
if($fID){
	$que="SELECT * FROM `files` WHERE id=".$fID;
	$fileDb= udb::single_row($que);
	$fileLangs   = udb::key_row("SELECT * FROM `files_text` WHERE `files_text`.`file_id` = " . $fID, ['langID']);
}

	$que="SELECT * FROM `files` WHERE `ref`=".$folderID." AND `table`='folder'";
	$files= udb::full_list($que);


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

		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת: </div>
				<input type="text" value="<?=outDb($fileDb['title'])?>" name="title" class="inpt">
			</div>
		</div>

		<div class="insertWrap">
			<div class="section txtarea big">
				<div class="inptLine">
					<div class="label noFloat">קישור הטמעה</div>
					<textarea style="direction:ltr" class="" name="link"><?=htmlspecialchars($fileDb['src'], ENT_QUOTES | ENT_XHTML, 'UTF-8', false)?></textarea>
				</div>
			</div>
			<?php foreach(LangList::get() as $id => $lang){ ?>
			<div class="language" data-id="<?=$id?>">
				<div class="section txtarea big">
					<div class="inptLine">
						<div class="label noFloat">תיאור</div>
						<textarea class="textEditor" name="desc"><?=$fileLangs[$id]['fileDesc']?></textarea>
					</div>
				</div>
			</div>
			<?php } ?>
			<input style="width:200px;" type="text" placeholder="תאריך העלאה" name="upload_date" class="datePick" autocomplete="off" value="<?=$fileDb['upload_date']?implode('/',array_reverse(explode('-',$fileDb['upload_date']))):""?>">
			<div class="inputLblWrap" style="display:none">
				<div class="switchTtl">וידאו מארחים</div>
				<label class="switch">
				  <input type="checkbox" name="webStar" value="1" <?=$fileDb['webStar']?"checked":""?>/>
				  <span class="slider round"></span>
				</label>
			</div>
		</div>

		<div class="section sub">
			<div class="inptLine">
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
					$.each({domain: <?=$domainID?>, language: <?=$langID?>}, function(cl, v){
						$('.' + cl).hide().each(function(){
							var id = $(this).data('id');
							$(this).find('input, select, textarea').each(function(){
								this.name = this.name + '[' + id + ']';
							});
						}).filter('[data-id="' + v + '"]').show();

						$('.' + cl + 'Selector').on('change', function(){
							$('.' + cl, $(this).data('cont')).hide().filter('[data-id="' + this.value + '"]').show();
						});
					});
			});
		</script>
<?php }
else { ?>
	<div class="manageItems">
		<div class="addButton" style="margin-top: 20px;">
			<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="window.location.href='/cms/moduls/minisites/galleries/video.php?siteID=<?=$siteID?>&newgal=1&tab=<?=$tab?>&siteName=<?=$siteName?>'" >
		</div>
		<?php
			if($files){ ?>
		<table>
			<thead>
			<tr>

				<th>שם הוידאו</th>
				<!-- <th>שייך ל-</th> -->

				<th width="60">&nbsp;</th>
			</tr>
			</thead>
			<tbody  id="sortRow">

			<?php foreach($files as $row) { ?>
				<tr id="<?=$row['id']?>">
					<td onclick="window.location.href='/cms/moduls/minisites/galleries/video.php?frame=<?=$frameID?>&siteID=<?=$siteID?>&fID=<?=$row['id']?>&tab=<?=$tab?>&siteName=<?=$siteName?>'"><?=$row['title']?></td>
					<!-- <td align="center"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td> -->
					<td align="center" class="actb">
					<div onclick="window.location.href='/cms/moduls/minisites/galleries/video.php?frame=<?=$frameID?>&siteID=<?=$siteID?>&fID=<?=$row['id']?>&tab=<?=$tab?>&siteName=<?=$siteName?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>
					<?php if(!$row['isMain']) { ?>
					|</div><div onClick="if(confirm('אתה בטוח??')){location.href='/cms/moduls/minisites/galleries/video.php?gdel=<?=$row['id']?>&siteID=<?=$siteID?>&tab=<?=$tab?>&siteName=<?=$siteName?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
		url: 'js_order_galleries.php',
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
