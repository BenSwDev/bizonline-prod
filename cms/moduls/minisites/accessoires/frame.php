<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";

$pageID=intval($_GET['pageID']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;
const BASE_LANG_ID = 1;

$que = "SELECT * FROM `spaces_type` WHERE 1";
$spaces = udb::full_list($que);

if('POST' == $_SERVER['REQUEST_METHOD']) {

    try {
        $data = typemap($_POST, [
            'accessoryName'   => ['int' => 'string'],
            'oldAccessoryName'   => ['int' => 'string'],
            'fontCode'   => 'string',
            'defaultSpace'   => 'int',
			'showOrder'   => 'int'
        ]);

        if (!$data['accessoryName'][BASE_LANG_ID])
            throw new LocalException('חייב להיות שם בעברית');
	
        $siteData = [

            'accessoryName' => $data['accessoryName'][BASE_LANG_ID],
			'defaultSpace' => $data['defaultSpace'],
			'fontCode' => $data['fontCode'],
			'showOrder' => $data['showOrder']

        ];
		$photo = pictureUpload('picture',"../../../../gallery/", 0, 0, 0, $errors);
		if($photo){
			$siteData["accessoryPic"] = $photo[0]['file'];
		}
		//print_r($errors);

        if (!$pageID){      // opening new site
            $pageID = udb::insert('accessories', $siteData);
        } else {
            udb::update('accessories', $siteData, '`accessoryID` = ' . $pageID);
        }
		//fixOrder
		$que="SELECT * FROM `accessories` order by showOrder ASC";
		$setOrder = udb::full_list($que);
		$o=0;
		foreach($setOrder as $item){
			$o++;
			udb::update("accessories",array('showOrder'=> $o)," accessoryID=".$item['accessoryID']);
		}

		// saving data per domain / language
		foreach(LangList::get() as $lid => $lang){
			// inserting/updating data in domains table
			udb::insert('accessories_langs', [
				'accessoryID'    => $pageID,
				'langID'    => $lid,
				'accessoryName'  => $data['accessoryName'][$lid],
				'oldName'  => $data['oldAccessoryName'][$lid]
			], true);
		}
       
    }
	catch (LocalException $e){
        // show error
    } ?>

?>

<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php
	
}


if ($pageID){
    $accessData    = udb::single_row("SELECT * FROM `accessories` WHERE accessoryID=".$pageID);
    $accessLangs   = udb::key_row("SELECT * FROM `accessories_langs` WHERE `accessoryID` = " . $pageID, ['langID']);
}

$que="SELECT Count(*) FROM `accessories`";
$totalAccs= udb::single_value($que);

$domainID = DomainList::active();
$langID   = LangList::active();

?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">

    <h1><?=$accessData['accessoryName']?outDb($accessData['accessoryName']):"הוספת אביזר חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
		<?=LangList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
		<?php foreach(LangList::get() as $lid => $lang){ ?>
			<div class="language" data-id="<?=$lid?>">
				<div class="section">
					<div class="inptLine">
						<div class="label">שם האביזר </div>
						<input type="text" value='<?=js_safe($accessLangs[$lid]['accessoryName'])?>' name="accessoryName" class="inpt">
					</div>
				</div>
				<div class="section">
					<div class="inptLine">
						<div class="label">ערך ישן</div>
						<input type="text" value='<?=js_safe($accessLangs[$lid]['oldName'])?>' name="oldAccessoryName" class="inpt">
					</div>
				</div>
			</div>
		<?php } ?>

			<div style="border:1px solid #ccc;display:inline-block;vertical-align:top;clear:both;margin:4%;">
				<div class="section">
					<div class="inptLine">
						<div class="label">תמונה: </div>
						<input type="file" name="picture" class="inpt" value="<?=$accessData['accessoryPic']?>">
					</div>
				</div>
				<?php if($accessData['accessoryPic']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../../../gallery/<?=$accessData['accessoryPic']?>" style="width:100%">
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		
		<div class="section">
			<div class="inptLine">
				<div class="label">מיקום</div>
				<select name="showOrder">
					<?
					for($o=1;$o <= $totalAccs;$o++) {
					$selected = ($o == $accessData['showOrder']) ? " selected " : "";
					echo "<option value='".$o."' ".$selected." >".$o."</option>";
					}?>
				</select>

			</div>
		</div>
		<!-- <div class="section">
			<div class="inptLine">
				<div class="label">קוד פונט</div>
				<input type="text" value='<?=$accessData['fontCode']?>' name="fontCode" class="inpt">
				<span class="iconx-big" aria-hidden="true" data-icon="&#x<?=$accessData["fontCode"]?>"></span>
			</div>
		</div> -->

		<div class="inputLblWrap">
			<div class="labelTo">שייך אביזר לסוג חדר</div>
			<select name="defaultSpace">
				<option value="0">-</option>
				<?php foreach($spaces as $space) { ?>
				<option value="<?=$space['id']?>" <?=($space['id']==$accessData['defaultSpace']?"selected":"")?> ><?=$space['spaceName']?></option>
				<?php } ?>
			</select>
		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="<?=$accessData['accessoryID']?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>


<script type="text/javascript">
	$(function(){
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
	})
</script>