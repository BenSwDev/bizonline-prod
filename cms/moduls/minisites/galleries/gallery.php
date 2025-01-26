<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";
//include_once "../../../classes/class.BankGallery.php";


//ini_set('display_errors', 1);
//error_reporting(-1 ^ E_NOTICE);

$galID=intval($_GET['gID']);
$siteID=intval($_GET['siteID']);
$frameID=intval($_GET['frame']);
$siteName = $_GET['siteName'];

if ('POST' == $_SERVER['REQUEST_METHOD']) {



	$cp=Array();
	$cp['folderTitle'] = inDB($_POST['GalleryTitle']);
	$cp["siteID"] = $siteID;

	if (!$cp['folderTitle'])
		throw new LocalException('נא להכניס שם לגלריה');


	if($galID){
		udb::update("folder", $cp, "folderID =".$galID);
	} else {
		$galID = udb::insert("folder", $cp);
	}

	if($_FILES['images'] && $_FILES['images']['name'][0]){

		$newFile = [];
		$cnt = count($_FILES['images']['name']);


		for($i=0;$i<$cnt;$i++){
			foreach($_FILES['images'] as $key => $picKey){
				$newFile[$key] = $picKey[$i];
			}
			$file = new Core\Files\Optimizer($newFile);
			$resultAws = $file->saveToLocal('/gallery');
			$fileArr=Array();
			$fileArr['src']=$resultAws['ssd_db_path'];
			$fileArr['table']="folder";
			$fileArr['ref']=$galID;

			$filePic = udb::insert("files", $fileArr);


		}
	}

	?>
	<script>window.location.href='/cms/moduls/minisites/galleries/gallery.php?siteID=<?=$siteID?>&tab=7&siteName=<?=$siteName?>'</script>
	<?php
	exit;
}


if(intval($_GET['gdel'])){
	$galID = intval($_GET['gdel']);
	$que="SELECT * FROM `files` WHERE ref=".$galID."";
	$files= udb::full_list($que);
	$path=$_SERVER['DOCUMENT_ROOT']."/gallery/";
	if($files){
		foreach($files as $res){
			unlink($path.$res['src']);
		}
	}
	udb::query("DELETE FROM `files` WHERE ref=".$galID." ");
	udb::query("DELETE FROM `folder` WHERE folderID=".$galID." ");

	?>
	<script>window.location.href='/cms/moduls/minisites/galleries/gallery.php?siteID=<?=$siteID?>&tab=7&siteName=<?=$siteName?>'</script>
	<?php
	exit;
}

	$que="SELECT * FROM `folder` WHERE siteID=".$siteID." ORDER BY folderID";
	$galleries= udb::full_list($que);


if($galID){
	$que="SELECT * FROM `folder` WHERE siteID=".$siteID." AND folderID=".$galID;
	$gallery= udb::single_row($que);

	if($gallery){
		$que="SELECT * FROM `files` WHERE `ref`=".$galID." AND `table`='folder'";
		$images= udb::full_list($que);
	}
}

?>
<div class="loader">
	<div class="spinner"></div>
</div>
<div class="editItems">
    <div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
	<?=showTopTabs(0)?>

<?php if($galID || intval($_GET['newgal'])==1){ ?>
    <form method="POST" class="manageItems" enctype="multipart/form-data">

		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת: </div>
				<input type="text" value="<?=outDb($gallery['folderTitle'])?>" name="GalleryTitle" class="inpt">
			</div>
		</div>
		<div class="section" style="float:left;display:block;">
			<div class="inptLine">
				<label for="imagesUpload" class="uploadLabel">העלאת תמונות</label>
			</div>
		</div>
        <input type="file" id="imagesUpload" name="images[]" multiple style="visibility: hidden;">

		<span style="display: block;margin-top: 10px;font-size: 20px;">סה"כ תמונות: <?=$images && count($images)?></span>
		<?php /* ?>
		<table id="newFiles">
            <thead>
                <tr><th style="width: 5%;">מוצג</th>
                    <th style="width: 15%;">תמונה</th>
                    <th style="width: 5%;">מחיקה</th>
                </tr>
            </thead>
            <tbody id="sortRow">

			<?php if($images){
			$ids="";
			$i=0;
			foreach($images as $image){
				$ids.=($i!=0?",":"")."imageBox_".$image['id']; ?>
			<tr id="imageBox_<?=$image['id']?>">
                <td><input type="checkbox" name="imVisible[<?=$image['id']?>]" value="1" <?=$image['ifshow']==1?"checked":""?>></td>
                <td><img src="<?=WEBSITE?><?=$image['src']?>" style="max-width:100px;max-height:100px;"></td>
                <td class="remove" onclick="removeThis('<?=$image['id']?>')"><?=substr($image['src'],-4)?><i class="fa fa-trash-o" aria-hidden="true"></i></td>
            </tr>
			<?php $i++; } } ?>
			</tbody>
        </table>
		<?php */ ?>
		<div class="imagWrap">
		<?php if($images){ ?>
			<?php
			$ids="";
			$i=0;
			foreach($images as $image){
				$image['src'] = str_replace('gallery/', 'gallery/thumb/600/', $image['src']);
				$ids.=($i!=0?",":"")."imageBox_".$image['id']; ?>
			<div class="imgGalFr" id="imageBox_<?=$image['id']?>">
                <div class="pic"><a href="<?=WEBSITE?><?=$image['src']?>" data-lightbox="image-1"><img src="<?=WEBSITE?><?=$image['src']?>"></a></div>
                <div class="remove" onclick="removeThis('<?=$image['id']?>')"><?=substr($image['src'],-4)?><i class="fa fa-trash-o" aria-hidden="true"></i></div>
            </div>
			<?php $i++;  } ?>
		<?php } ?>
		</div>
		<div class="section sub">
			<div class="inptLine">
				<input type="hidden" id="orderResult" name="orderResult" value="<?=$ids?>">
				<input type="submit" value="שמור" onClick="showLoader()" class="submit">
			</div>
		</div>


    </form>
<?php } else { ?>
	<div class="manageItems">
		<div class="addButton" style="margin-top: 20px;">
			<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="window.location.href='/cms/moduls/minisites/galleries/gallery.php?siteID=<?=$siteID?>&newgal=1&tab=7&siteName=<?=addslashes($siteName)?>'" >
			<?php if($galleries == "nothing"){ ?>
			<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
			<?php } ?>
		</div>
		<?php
			if($galleries){ ?>
		<table>
			<thead>
			<tr>

				<th>שם גלריה</th>
				<!-- <th>שייך ל-</th> -->

				<th width="60">&nbsp;</th>
			</tr>
			</thead>
			<tbody  id="sortRow">

			<?php  foreach($galleries as $row) { ?>
				<tr  id="<?=$row['folderID']?>">
					<td onclick="window.location.href='/cms/moduls/minisites/galleries/gallery.php?frame=<?=$frameID?>&siteID=<?=$siteID?>&gID=<?=$row['folderID']?>&tab=7&siteName=<?=$siteName?>'"><?=outDb($row['folderTitle'])?><?=$row['isMain']?' (תיקייה ראשית)':''?></td>
					<!-- <td align="center"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td> -->
					<td align="center" class="actb">
					<div onclick="window.location.href='/cms/moduls/minisites/galleries/gallery.php?frame=<?=$frameID?>&siteID=<?=$siteID?>&gID=<?=$row['folderID']?>&tab=7&siteName=<?=addslashes($siteName)?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>
					<?php if(!$row['isMain']) { ?>
					|</div><div onClick="if(confirm('אתה בטוח??')){location.href='/cms/moduls/minisites/galleries/gallery.php?frame=<?=$frameID?>&siteID=<?=$siteID?>&gID=<?=$row['folderID']?>&tab=7&siteName=<?=addslashes($siteName)?>&gdel=<?=$row['folderID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div>
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
		$('.showDescBtn').click(function(){
				$('.ttlWrap').toggleClass("show");
		});
        $("#imagesUpload").change(function(){
            if (typeof (FileReader) != "undefined") {
                var table = $(".imagWrap");
                var regex = /^([a-zA-Z0-9א-ת\s_\\.\-:])+(.jpg|.jpeg|.gif|.png|.bmp|.JPG|.JPEG|.GIF|.PNG|.BMP)$/;
                var id = 0;
                $($(this)[0].files).each(function () {
                    var file = $(this);
					var fileName = file[0].name.replace('(','');
					    fileName = fileName.replace(')','');

					if (regex.test(fileName)) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            var img = '<div class="imgGalFr" id="imageBox_' + id + '">'
                                + '<div class="pic"><img src="' + e.target.result + '" style="max-width:100%;max-height:100%;vertical-align: middle;"></div>'
                                + '<div class="remove" onclick="removeThis(' + id + ')"><i class="fa fa-trash-o" aria-hidden="true"></i></div>'
                                + '</div >';
                            table.append(img);
                            id++;
                        };
                        reader.readAsDataURL(file[0]);
                    } else {
                        alert(file[0].name + " קובץ לא תקין");
                        return false;
                    }
                });
            } else {
                alert("ERROR");
            }
            $("#sortRow").sortable();
            $(this).hide();
            //$(".uploadLabel").hide();
        });
        function removeThis(id){
			if(confirm("האם אתה רוצה למחוק תמונה זו?")){
				$("#imageBox_"+id).remove();
				 $.ajax({
					url: 'js_del_picture.php',
					type: 'POST',
					data: {picID:id},
					async: false,
					success: function (myData) {
						console.log(myData);
					}
				});
			}
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


		function startGalOrder2(is){
			$(".uploadLabel").hide();
			$(".imgGalFr input").attr("disabled", "disabled");
			$(".imgGalFr .fa-trash-o").hide();
			$(is).hide();
			$(".imgGalFr").css({'box-shadow':'0 0 16px 0px rgba(0,0,0,0.8)','cursor':'pointer'});
			$(".imagWrap").sortable({
				stop: function(){
					$("#orderResult").val($(".imagWrap").sortable('toArray'));
				}
			});
			$("#orderResult").val($(".imagWrap").sortable('toArray'));
		}


		function showLoader(){
			$('.loader').show();
		}
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
            min-width: 100px;
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

		.loader{display: none;position: fixed;top: 0;right: 0;left: 0;bottom: 0;top: 0;z-index: 999;background:rgba(255,255,255,0.7);}
		.loader .spinner{width: 70px;animation-direction: alternate;animation-duration: 1s;transform: rotateZ(0deg);height: 70px;border-radius:100px;border:8px solid #399ac5;border-bottom:8px solid #444444;position: absolute;top: 0;bottom: 0;left: 0;right: 0;margin: auto;animation-name:spin;animation-iteration-count: infinite;}

		@keyframes spin{

			from{transform: rotateZ(0deg);}
			to{transform: rotateZ(360deg);}
		}

		@-webkit-keyframes spin {
			from{transform: rotateZ(0deg);}
			to{transform: rotateZ(360deg);}

		}

    </style>
    </body>
    </html>
