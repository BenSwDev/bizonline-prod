<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";


$siteID=intval($_GET['sID']);
$frameID=intval($_GET['frame']);
$galID=intval($_GET['gID']);

$position=6;
if ('POST' == $_SERVER['REQUEST_METHOD']) {

	
	$cp=Array();
	$cp['GalleryTitle'] = inDB($_POST['GalleryTitle']);
	$cp['GalleryDesc'] = inDB($_POST['GalleryDesc']);
	$cp["ifShow"] = intval($_POST['ifShow'])?"1":"0";
	$cp["sID"] = $siteID;


	if($galID){
		udb::update("galleries", $cp, "GalleryID =".$galID);
	} else {
		$cp['ShowOrder']=1;
		$galID = udb::insert("galleries", $cp);
	}

	$photos = pictureUpload('images',"../../gallery/");

	if(isset($photos)){
		foreach($photos as $key=>$photo){	
			$fileArr=Array();
			$fileArr['src']=$photo['file'];
			$fileArr['link']=$_POST['link'][$key];
			$fileArr['title']=$_POST['title'][$key];
			$fileArr['desc']=$_POST['desc'][$key];
			$fileArr['table']="site";
			$fileArr['ref']=$galID;
			$file = udb::insert("files", $fileArr);
		}
	}

	if(isset($_POST['imTitle'])){
		foreach($_POST['imTitle'] as $key=>$val){

			$fileImgs=Array();
			$fileImgs['link']=$_POST['imLink'][$key];
			$fileImgs['title']=$_POST['imTitle'][$key];
			$fileImgs['desc']=$_POST['imDesc'][$key];
			$fileImgs['ifshow']=$_POST['imVisible'][$key];
			udb::update("files", $fileImgs, "id =".$key);
		}
	}



if(isset($_POST['orderResult'])){
	$ids = str_replace("imageBox_","",$_POST['orderResult']);
	$ids = explode(",",$ids);
	if($ids){
		foreach($ids as $key=>$id){
			if($id){
				$query=Array();
				$query['showorder']=$key;
				udb::update("files", $query, "`table`='site' AND ref='".$galID."' AND  id=".$id."");
			}
		}
	}
}

	$que="SELECT files.id, files.src, files.showorder, galleries.GalleryID, galleries.sID, galleries.ShowOrder
	  FROM files
	  INNER JOIN galleries ON (galleries.GalleryID = files.ref)
	  WHERE galleries.ifShow=1 AND files.ifshow=1 AND files.`table`='site' AND galleries.sID=".$siteID."
	  GROUP BY sID, files.id
	  ORDER BY galleries.ShowOrder DESC, files.showorder DESC
	  ";
	$firstPictures = udb::key_row($que, "sID");

	if($firstPictures){
		foreach($firstPictures as $first){
			$cp=Array();
			$cp['prPictureFirst']=$first['src'];
			udb::update("sites", $cp, "siteID=".$siteID."");
		}
	}
	
	?>
	<script>window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>'</script>
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
	udb::query("DELETE FROM `galleries` WHERE GalleryID=".$galID." ");
	
	?>
	<script>window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>'</script>	
	<?php
	exit;
}

$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);

$que="SELECT * FROM `galleries` WHERE sID=".$siteID." ORDER BY ShowOrder, GalleryID";
$galleries= udb::full_list($que);


if($galID){
	$que="SELECT * FROM `galleries` WHERE GalleryID=".$galID."";
	$gallery= udb::single_row($que);

	$que="SELECT * FROM `files` WHERE `ref`=".$gallery['GalleryID']." AND `table`='site' ORDER BY showorder ";
	$images= udb::full_list($que);
}
$menu = include "site_menu.php";

$que="SELECT roomID, roomName, galleryID FROM `sitesRooms` WHERE sitesRooms.siteID = ".$siteID." ORDER BY showOrder";
$rooms= udb::full_list($que);


?>
<div class="editItems">
    <h1><?=outDb($site['TITLE'])?></h1>
	<div class="miniTabs">
		<?php foreach($menu as $men){
		if($men['position']==$position && $men['sub']){
			$subMenu = $men['sub'];
		}
		?>
		<div class="tab<?=$men['position']==$position?" active":""?>" onclick="window.location.href='<?=$men['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><p><?=$men['name']?></p></div>
		<?php } ?>
	</div>
	<?php if($subMenu){ ?>
	<div class="subMenuTabs">
		<?php foreach($subMenu as $sub){ ?>
		<div class="minitab" onclick="window.location.href='<?=$sub['href']?>?frame=<?=$frameID?>&sID=<?=$siteID?>'"><?=$sub['name']?></div>
		<?php } ?>
	</div>
	<?php } ?>
<?php if($galID || intval($_GET['newgal'])==1){ ?>
    <form method="POST" class="manageItems" enctype="multipart/form-data">
        
		<div class="section">
			<div class="inptLine">
				<div class="label">כותרת: </div>
				<input type="text" value="<?=outDb($gallery['GalleryTitle'])?>" name="GalleryTitle" class="inpt">
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">מוצג באתר: </div>
				<div class="chkBox">
					<input type="checkbox" value="1" <?=$gallery['ifShow']?"checked":""?> name="ifShow" id="ifShow">
					<label for="ifShow"></label>
				</div>
			</div>
		</div>
		<div class="section">
			<div class="inptLine">
				<div class="label">הערות: </div>
				<textarea name="arem"><?=$gallery['GalleryDesc']?></textarea>
			</div>
		</div>
		<div class="section" style="float:left;display:block;">
			<div class="inptLine">
				<label for="imagesUpload" class="uploadLabel">העלאת תמונות</label>
			</div>
		</div>
        <input type="file" id="imagesUpload" name="images[]" multiple style="visibility: hidden;">
		<input type="button" id="startOrder" onclick="startGalOrder(this)" class="submit" value="ערוך סדר תצוגה">
		<table id="newFiles">
            <thead>
                <tr><th style="width: 5%;">מוצג</th>
                    <th style="width: 15%;">תמונה</th>
                    <th style="width: 25%;">כותרת</th>
                    <th style="width: 25%;">תיאור</th>
                    <th style="width: 25%;">קישור</th>
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
                <td><img src="../../gallery/<?=$image['src']?>" style="max-width:100px;max-height:100px;"></td>
                <td><input type="text" name="imTitle[<?=$image['id']?>]" value="<?=$image['title']?>" placeholder="כותרת"></td>
                <td><input type="text" name="imDesc[<?=$image['id']?>]" value="<?=$image['desc']?>" placeholder="תיאור"></td>
                <td><input type="text" name="imLink[<?=$image['id']?>]" value="<?=$image['link']?>" placeholder="קישור"></td>
                <td class="remove" onclick="removeThis('<?=$image['id']?>')"><i class="fa fa-trash-o" aria-hidden="true"></i></td>
            </tr>
			<?php $i++; } } ?>
			</tbody>
        </table>

		
		<div class="section sub">
			<div class="inptLine">
				<input type="hidden" id="orderResult" name="orderResult" value="<?=$ids?>">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>


    </form>
<?php } else { ?>
	<div class="manageItems">
		<div class="addButton" style="margin-top: 20px;">
			<input type="button" class="addNew" id="addNewAcc" value="הוסף חדש" onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&newgal=1'" >
			<?php if($galleries){ ?>
			<input type="button" class="addNew" id="buttonOrder" onclick="orderNow(this)" value="ערוך סדר תצוגה">
			<?php } ?>
		</div>
		<?php 
			if($galleries){ ?>
		<table>
			<thead>
			<tr>
				<th width="30">#</th>
				<th>שם גלריה</th>
				<!-- <th>שייך ל-</th> -->
				<th>מוצג באתר</th>
				<th width="60">&nbsp;</th>
			</tr>
			</thead>
			<tbody  id="sortRow">

			<?php foreach($galleries as $row) { ?>
				<tr  id="<?=$row['GalleryID']?>">
					<td align="center"><?=$row['GalleryID']?></td>
					<td onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><?=outDb($row['GalleryTitle'])?></td>
					<!-- <td align="center">
					<?php foreach($rooms as $room){ if($row['GalleryID']==$room['galleryID']){ echo $room['roomName']; } } ?>
					</td>
					 -->				
					<td align="center"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
					<td align="center" class="actb">
					<div onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>|</div><div onClick="if(confirm('אתה בטוח??')){location.href='?sID=<?=$siteID?>&frame=<?=$frameID?>&gdel=<?=$row['GalleryID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
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
        $("#imagesUpload").change(function(){
            if (typeof (FileReader) != "undefined") {
                var table = $("#newFiles > tbody");
                var regex = /^([a-zA-Z0-9א-ת\s_\\.\-:])+(.jpg|.jpeg|.gif|.png|.bmp|.JPG|.JPEG|.GIF|.PNG|.BMP)$/;
                var id = 0;
                $($(this)[0].files).each(function () {
                    var file = $(this);
					var fileName = file[0].name.replace('(','');
					    fileName = fileName.replace(')','');
					 
					if (regex.test(fileName)) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            var img = '<tr id="imageBox_' + id + '">'
                                + '<td><input type="checkbox" name="visible[' + id + ']" checked></td>'
                                + '<td><img src="' + e.target.result + '" style="max-width:100px;max-height:100px;"></td>'
                                + '<td><input type="text" name="title[' + id + ']" placeholder="כותרת"></td>'
                                + '<td><input type="text" name="desc[' + id + ']" placeholder="תיאור"></td>'
                                + '<td><input type="text" name="link[' + id + ']" placeholder="קישור"></td>'
                                + '<td class="remove" onclick="removeThis(' + id + ')"><i class="fa fa-trash-o" aria-hidden="true"></i></td>'
                                + '</tr>';
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
    </style>
    </body>
    </html>
