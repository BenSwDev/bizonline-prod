<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";

$position=4;

$pageID=intval($_GET['pageID']);
$pageType = intval($_GET['type']);
$boxID=intval($_GET['gID']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if ('POST' == $_SERVER['REQUEST_METHOD']) {

	$cp=Array();
	$cp['boxTitle'] = inputStr($_POST['boxTitle']);
	$cp['boxText'] = $_POST['boxText'];
	$cp['boxSize'] = intval($_POST['boxSize']);
	$cp["boxShow"] = intval($_POST['ifShow'])?"1":"0";
	$cp["langID"] = intval($_POST['LangID']);
	$cp["pageID"] = $pageID;

	$photo = pictureUpload('picture',"../../gallery/");
	if($photo){
		$cp["boxPicture"] = $photo[0]['file'];
	}

	if($boxID){
		udb::update("pagesBoxes", $cp, "boxID =".$boxID);
	} else {
		$boxID = udb::insert("pagesBoxes", $cp);
	}

	$photos = pictureUpload('images',"../../gallery/");

/*

if(isset($_POST['orderResult'])){
	$ids = str_replace("imageBox_","",$_POST['orderResult']);
	$ids = explode(",",$ids);
	if($ids){
		foreach($ids as $key=>$id){
			if($id){
				$query=Array();
				$query['showorder']=$key;
				udb::update("files", $query, "`table`='MainPages' AND ref='".$boxID."' AND  id=".$id."");
			}
		}
	}
}
*/

	?>
	<script>window.location.href='/cms/pages/boxes.php?pageID=<?=$pageID?>&type=<?=$pageType?>'</script>
	<?php
	exit;



}

if(intval($_GET['gdel'])){
	$boxID = intval($_GET['gdel']);
	$que="SELECT `boxPicture` FROM `pagesBoxes` WHERE boxID=".$boxID."";
	$file= udb::single_value($que);
	$path=$_SERVER['DOCUMENT_ROOT']."/gallery/";
	if($file){

		unlink($path.$file);
	}
	udb::query("DELETE FROM `pagesBoxes` WHERE boxID=".$boxID);
	?>
	<script>window.location.href='/cms/pages/boxes.php?pageID=<?=$pageID?>&type=<?=$pageType?>'</script>
	<?php
	
	
		exit;
}


$que="SELECT * FROM `MainPages` WHERE MainPageID=".$pageID."";
$page= udb::single_row($que);



$que="SELECT LangID, LangName FROM language WHERE 1";
$languages = udb::full_list($que);

if($boxID){
	$que="SELECT * FROM `pagesBoxes` WHERE boxID=".$boxID."";
	$box= udb::single_row($que);
}
$menu = include "pages_menu.php";



?>
<div class="editItems">
    <h1><?=outDb($page['MainPageTitle'])?></h1>
	<div class="miniTabs">
		<?php foreach($menu as $men){
		if($men['position']==$position && $men['sub']){
			$subMenu = $men['sub'];
		}
		?>
		<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>?pageID=<?=$pageID?>&type=<?=$pageType?>'"><p><?=$men['name']?></p></div>
		<?php } ?>
	</div>

<?php if($boxID || intval($_GET['newgal'])==1){ ?>
    <form method="POST" class="manageItems" enctype="multipart/form-data">
		<input type="hidden" name="LangID" value="<?=$langID?>">
		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת: </div>
				<input type="text" value="<?=($box['boxTitle']?$box['boxTitle']:$page['MainPageTitle'])?>" name="boxTitle" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">מוצג באתר: </div>
				<div class="chkBox">
					<input type="checkbox" value="1" <?=$box['boxShow']?"checked":""?> name="ifShow" id="ifShow">
					<label for="ifShow"></label>
				</div>
			</div>
		</div>
		<div class="section txtarea big">
			<div class="inptLine">
				<div class="label">טקסט: </div>
				<textarea  class="summernote" name="boxText"><?=outDb($box['boxText'])?></textarea>
			</div>
		</div>
		
		<div class="section">
			<div class="inptLine">
				<div class="label">גודל תיבה</div>
				<select name="boxSize" class="small">
					<option value="1">קטן</option>
					<option value="2">גדול</option>
				</select>
			</div>
		</div>
		<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;">
			<div class="section">
				<div class="inptLine">
					<div class="label">תמונה: </div>
					<input type="file" name="picture" class="inpt" value="<?=$box['picture']?>">
				</div>
			</div>
			<?php if($box['picture']){ ?>
			<div class="section">
				<div class="inptLine">
					<img src="../../gallery/<?=$box['picture']?>" style="width:100%">
				</div>
			</div>
			<?php } ?>
		</div>
		<!-- <input type="button" id="startOrder" onclick="startGalOrder(this)" class="submit" value="ערוך סדר תצוגה"> -->
		<div class="section sub">
			<div class="inptLine">
				<input type="hidden" id="orderResult" name="orderResult" value="<?=$ids?>">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
    </form>

	<link rel="stylesheet" href="../app/bootstrap.css">
	<link rel="stylesheet" href="../app/dist/summernote.css">
	<script src="../app/bootstrap.min.js"></script>
	<script src="../app/dist/summernote.js?v=<?=time()?>"></script>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function(){       
			$('.summernote').summernote({
				toolbar: [
				['style', ['bold', 'italic', 'underline', 'clear']],
				['fontname', ['fontname']],
				['fontsize', ['fontsize']],
				['para', ['ul', 'ol', 'paragraph']],
				['height', ['height']],
				['insert', ['picture', 'link','video']],
				['view', ['codeview']]
				],
				popover: {
					image: [
						['alt', ['addAlt']],
						['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
						['float', ['floatLeft', 'floatRight', 'floatNone']],
						['remove', ['removeMedia']]
					]},

				height: 300
			});
		});
	</script>
<?php } else { ?>
	<?php if($page['MainPageID']){ ?>
	<div class="miniTabs general" style="margin-right:50px;">
		<?php $i=2;
		foreach($languages as $lang){ ?>
			<div class="tab lngtab" data-langid="<?=$lang['LangID']?>"><p><?=$lang['LangName']?></p></div>
		<?php $i++; } ?>
	</div>
	<?php } 

	foreach($languages as $lang){ 
		
		$que="SELECT * FROM `pagesBoxes` WHERE pageID=".$pageID." AND LangID=".$lang['LangID']." ORDER BY ShowOrder";
		$pagesBoxes= udb::full_list($que);
		
		?>
	
		
	<div class="manageItems" id="menageLang-<?=$lang['LangID']?>" style="display:none;">
		<div class="addButton" style="margin-top: 20px;">
			<input type="button" class="addNew" id="addNewAcc" value="הוסף תיבה" onclick="window.location.href='/cms/pages/boxes.php?pageID=<?=$pageID?>&LangID=<?=$lang['LangID']?>&type=<?=$pageType?>&newgal=1'" >
			<?php if($pagesBoxes){ ?>
			<!-- <input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה"> -->
			<?php } ?>
		</div>

		<?php 
			if($pagesBoxes){ ?>
		<table>
			<thead>
			<tr>
				<th width="30">#</th>
				<th>שם התיבה</th>
				<th>מוצג באתר</th>
				<th width="60">&nbsp;</th>
			</tr>
			</thead>
			<tbody id="sortRow">

			<?php foreach($pagesBoxes as $row) { ?>
				<tr>
					<td align="center"><?=$row['boxID']?></td>
					<td onclick="window.location.href='/cms/pages/boxes.php?pageID=<?=$pageID?>&LangID=<?=$lang['LangID']?>&type=<?=$pageType?>&gID=<?=$row['boxID']?>'"><?=$row['boxTitle']?></td>
					<td align="center"><?=($row['boxShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
					<td align="center" class="actb">
					<div onclick="window.location.href='/cms/pages/boxes.php?pageID=<?=$pageID?>&LangID=<?=$lang['LangID']?>&type=<?=$pageType?>&gID=<?=$row['boxID']?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>|</div><div onClick="if(confirm('אתה בטוח??')){location.href='?pageID=<?=$pageID?>&LangID=<?=$lang['LangID']?>&type=<?=$pageType?>&gdel=<?=$row['boxID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
				</tr>
			<? } ?>
			</tbody>
		</table>
		<? } ?>
	</div>

	<?php } ?>



<!-- <input type="hidden" id="orderResult" name="orderResult" value=""> -->

<script>



/*
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
		url: 'js_order_pagesBoxes.php',
		type: 'POST',
		data: {ids:ids, pageID:<?=$pageID?>},
		async: false,
		success: function (myData) {
			window.location.reload();
		}
	});
}
*/
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
        function removeThis(id){
            $("#imageBox_"+id).remove();
			 $.ajax({
				url: '/cms/sites/js_del_picture.php',
				type: 'POST',
				data: {picID:id},
				async: false,
				success: function (myData) {
					console.log(myData);
				}
			});
        }
		function startGalOrder(is){
			$(".uploadLabel").hide();
			$("#sortRow input").attr("disabled", "disabled");
			$("#sortRow .fa-trash-o").hide();
			$(is).hide();
			$("#sortRow input[type = 'checkbox']").replaceWith('<i class="fa fa-outdent" aria-hidden="true"></i>');
			$("#sortRow").sortable({
				stop: function(){
					$("#orderResult").val($("#sortRow").sortable('toArray'));
				}
			});
			$("#orderResult").val($("#sortRow").sortable('toArray'));
		}

		$(".general .lngtab").click(function(){
			$(".general .lngtab").removeClass("active");
			$(this).addClass("active");

			var ptID = $(this).data("langid");
			$(".manageItems").css("display","none");
			$("#menageLang-"+ptID).css("display","block");
		});
    </script>

    <style>
        .uploadLabel{
            background: #00B0D0 none repeat scroll 0 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            display: table;
            float: right;
            font-size: 18px;
            height: 40px;
            margin: 0 2px;
            min-width: 110px;
            padding: 0 5px;
            line-height: 40px;
            text-align: center;
        }
        #newFiles th{
            text-align: center;
        }
        #newFiles td{
            vertical-align: middle;
        }
		#startOrder{
			width: 125px;
		}
		.manageItems b{display:inline}
		.editItems input.submit{display:block}
    </style>
    </body>
    </html>
