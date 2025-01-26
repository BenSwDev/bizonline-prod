<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";
include_once "../../../../commonFunctions.php";



const BASE_LANG_ID = 1;

$reviewID = intval($_GET['pageID']);
$siteID = intval($_POST['siteID'] ?? $_GET['siteID'] ?? 0);
$siteName = $_GET['siteName'];
$domainID = $_GET['domainID'];
$languages = LangList::get();

$publishReviews = udb::single_value("select publishReviews from sites where siteID=$siteID");
if($reviewID) {
	$reviewData  = udb::single_row("SELECT * FROM `reviews` WHERE `reviewID` = " . $reviewID);
}

if(($reviewID && $reviewData['orderID'] && $publishReviews) || !$reviewID || $publishReviews) {
	$que="SELECT * FROM `domains` WHERE domainID != 1 and domainMenu=1"; // dont show bizonline - ask roi
}
else {
	$que="SELECT * FROM `domains` WHERE domainID = 999"; // dont show any deomains
	$que="SELECT * FROM `domains` WHERE domainID != 1 and domainMenu=1"; // dont show bizonline - ask roi
}
$domains= udb::key_row($que,'domainID');
//print_r($domains);
//$options = udb::key_row("SELECT * FROM `reviewOptions` WHERE 1",array('optionCategory','id'));


if ('POST' == $_SERVER['REQUEST_METHOD']){

    try {
	//	$options = udb::key_row("SELECT * FROM `reviewOptions` WHERE 1",array('optionCategory','id'));
        $spaplusID = udb::single_value("select spaplusID from sites where siteID=".$siteID);
        $prevownComment = null;
        if($spaplusID){
            $prevownComment = udb::single_value("select ownComment from reviews where reviewID=".$reviewID);
        }

        $data = typemap($_POST, [
            'title'   => 'string',
            'name'   => 'string',
            '!ifShow'    => 'int',
            'avgScore'    => 'int',
            '!promte'    => 'int',
            'langID'    => 'int',
			'hostingDate'    => 'text',
			'text'			 => 'text',
			'ownComment'	 => 'html',
			'domains'        => 'int'
        ]);

        // main reviews data
        $siteData = [
            'ifShow'	=> $data['ifShow'],
            'siteID'    => $siteID,
            'title'		=> $data['title'],
            'name'		=> $data['name'],
            'avgScore'	=> $data['avgScore'],
            'selected'	=> $data['promte'],
            'text'		=> $data['text'],
			'day'	    => DateTime::createFromFormat("d/m/Y", $data['hostingDate'])->format("Y-m-d"),
			'ownComment' => $data['ownComment'],
			'ownDate' => date("Y-m-d"),
			'langID' =>  $data['langID']
			//save domainID

        ];

        $updateSpaplus = 0;
        if($spaplusID && $prevownComment && $prevownComment != $data['ownComment']) {
            $updateSpaplus = 1;
        }

		//reset promted review
		if($siteData['selected']==1){
			$que = "UPDATE `reviews` SET `selected`=0 WHERE LangID=".$data['langID']." AND siteID=".$siteID;
			udb::query($que);
		}

        if (!$reviewID){      // opening new reviews
            $reviewID = udb::insert('reviews', $siteData);

        } else {
            if($updateSpaplus) {
                $siteData['resendToSpaPlus'] = $updateSpaplus;
                $siteData['ownCommentDate'] = date("Y-m-d H:i");
                //send email to client about comment ??
            }
            udb::update('reviews', $siteData, '`reviewID` = ' . $reviewID);
			udb::query('DELETE FROM reviewsDomains WHERE `reviewID`='.$reviewID);

        }

		if($data['domains']){
			//foreach($data['domains'] as $domain => $val){
				//udb::insert("reviewsDomains",['reviewID' => $reviewID,'domainID' => $domain]);
				udb::insert("reviewsDomains",['reviewID' => $reviewID,'domainID' => $data['domains']]);
			//}
		}
/*
		if($reviewID){
			udb::query("DELETE FROM `reviewScore` WHERE `reviewID`=".$reviewID);
		foreach($_POST['qust'] as $key => $quest){
			$quesData = [];
			$quesData['reviewID'] = $reviewID;
			$quesData['categoryID'] = $key;
			$quesData['score'] = ($options[$key][$quest]['optionMark']?$options[$key][$quest]['optionMark']:0);
			$quesData['optionID'] = intval($quest);
			udb::insert("reviewScore", $quesData,true);
		}
	}
*/
/*
	udb::query("UPDATE `reviews` INNER JOIN (SELECT AVG(`optionMark`) as `score`, reviewScore.reviewID FROM `reviewScore` INNER JOIN `reviewOptions` ON (reviewScore.score = reviewOptions.id) WHERE `reviewID` = " . $reviewID . ") AS `tmp` USING(`reviewID`) SET reviews.avgScore = tmp.score WHERE reviews.reviewID = " . $reviewID);

*/
		/*
		// saving data per domain / language
		foreach(LangList::get() as $lid => $lang){
			// inserting/updating data in domains table
			udb::insert('bonus_langs', [
				'id'    => $bonusID,
				'langID'    => $lid,
				'bonusName' => $data['bonusName'][$lid]
			], true);
		}*/


    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

$reviewData = $reviewLangs = [];
$domainID = DomainList::active();
//$langID   = LangList::active();

if ($reviewID){
    $reviewData  = udb::single_row("SELECT * FROM `reviews` WHERE `reviewID` = " . $reviewID);
	$reviewScore = udb::key_row("SELECT * FROM `reviewScore` WHERE `reviewID`=".$reviewID."",'categoryID');

	$reviewPic = udb::full_list("SELECT * FROM `files` WHERE `table`= 'reviews' AND `ref`=".$reviewID);

	$reviewsDomains = udb::key_row('SELECT domainID FROM reviewsDomains WHERE reviewID='.$reviewID,'domainID');

}

	$que = "SELECT * FROM `reviewCategories` WHERE 1 ORDER BY id";
	$categories = udb::key_row($que, 'id');

	$que = "SELECT * FROM `reviewOptions` WHERE 1 ORDER BY `optionMark`";
	$options = udb::key_list($que, 'optionCategory');



?>

<style type="text/css">
	.sectionWrap .selectWrap{width: 22%;}
	.inputLblWrap{margin: 1%;display:flex;flex-wrap:wrap}
	.inputLblWrap > span { margin:4px}
	.editItems form .inputLblWrap  input[type='radio'] { display:inline-block;width:20px;height:20px;border:0}
</style>

<div class="editItems">
	<div class="frameContent">
		<div class="siteMainTitle"><?=$siteName?></div>
		<form action="" method="post">
		<?/*
			<div class="inputLblWrap">
				<div class="labelTo">כותרת</div>
				<input type="text" placeholder="כותרת" value="<?=js_safe($reviewData['title'])?>" name="title" />
			</div>
		*/?>
			<div class="labelTo">הצג בדומיינים</div>
			<div class="inputLblWrap" style="direction:rtl">

				<?php

				if($reviewsDomains){
					if(count($reviewsDomains)>5){
						$reviewsDomains = array(6=>"ok");
						echo "<div style='width:100%;color:red;font-weight:bold'>היה מסומן על כל הדומיינים</div>";
					}
				}else{
					$reviewsDomains = array(($domainID==1? 6 : $domainID) =>"ok");
				}

				foreach($domains as $domain){ ?>
				<span>
					<input type="radio" id="rad_domain<?=$domain['domainID']?>" name="domains" value="<?=$domain['domainID']?>" <?=($reviewsDomains?in_array($domain['domainID'],(array_keys($reviewsDomains)))?"checked":"":"")?> <?//=($reviewID == 0) ? "checked" : "";?>>
					<label for="rad_domain<?=$domain['domainID']?>" class=""><?=$domain['domainName']?></label>
				</span>
				<?php } ?>

			</div>

			<div class="inputLblWrap">
				<div class="labelTo">שם הכותב</div>
				<input type="text" placeholder="שם הכותב" required value="<?=js_safe($reviewData['name'])?>" name="name" />
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">מוצג</div>
				<label class="switch">
				  <input type="checkbox" name="ifShow" value="1" <?=($reviewData['ifShow']==1)?"checked":""?> <?=($reviewID==0)?"checked":""?>/>
				  <span class="slider round"></span>
				</label>
			</div>
			<div class="inputLblWrap">
				<div class="switchTtl">חוות דעת מקודמת</div>
				<label class="switch">
				  <input type="checkbox" name="promte" value="1" <?=($reviewData['selected']==1)?"checked":""?> />
				  <span class="slider round"></span>
				</label>
			</div>
			<div class="selectWrap inputLblWrap">
				<div class="selectLbl">שפה</div>
				<select name="langID">
					<?php foreach($languages as $lang) { ?>
					<option value="<?=$lang['LangID']?>" <?=$lang['LangID']==$reviewData['LangID']?"selected":""?> ><?=$lang['LangName']?></option>
					<?php } ?>
				</select>
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">תאריך האירוח</div>
				<input type="text" value="<?=($reviewData['day']?date("d/m/Y", strtotime($reviewData['day'])):date("d/m/Y"))?>" name="hostingDate" class="datePick" />
			</div>
			<div class="titleSec">דירוג</div>
			<div class="sectionWrap">
			<div class="selectWrap">

				<select name="avgScore" required>
					<option value="" disabled selected>-</option>
					<?php for($i=1;$i<=5;$i++){ ?>
					<option value="<?=$i?>" <?=($i==$reviewData['avgScore']?"selected":"")?> ><?=$i?></option>
					<?php } ?>
				</select>
			</div>
				<?php
				if($categories){
				foreach($categories as $cat){ ?>
				<div class="selectWrap">
					<div class="selectLbl"><?=$cat['categoryNameShow']?></div>
					<select name="qust[<?=$cat['id']?>]">
						<option value="0">-</option>
						<?php foreach($options[$cat['id']] as $opt){ ?>
						<option value="<?=$opt['id']?>" <?=($reviewScore[$cat['id']]['optionID']==$opt['id']?"selected":"")?> ><?=$opt['optionName']?></option>
						<?php } ?>
					</select>
				</div>
				<?php } } ?>

				<div class="sectionWrap">
					<div class="section txtarea big">
						<div class="label">כותרת</div>
						<input type="text" name="title" placeholder="כותרת" value="<?=outDb($reviewData['title'])?>">
					</div>
				</div>
				<div class="sectionWrap">
					<div class="section txtarea big">
						<div class="label">חוות דעת</div>
						<textarea name="text" class=""><?=$reviewData['text']?></textarea>
					</div>
				</div>
				<?php if($reviewPic) { ?>
				<div class="titleSec">תמונות</div>
				<div class="sectionWrap">
					<?php foreach($reviewPic as $pic){ ?>
						<A href="<?
						$fullUrl = "";
						if($reviewData['domainID'] == 6){
							$fullUrl .= 'https://www.vii.co.il/review-gallery/';
						}
						else {
							$fullUrl .= '/gallery/';
						}
						echo $fullUrl;
						?><?=picturePath($pic['src'])?>" target="_blank"><?=$pic['src']?></a>
						<?if(strpos($pic['src'],".jp") !== false) {?><img src="<?=$fullUrl?><?=picturePath($pic['src'])?>" alt="" style="max-width:290px;display:inline-block;vertical-align:top;"><?}?>
					<?php } ?>
				</div>
				<?php } ?>
			</div>

			<?php if($reviewID) { ?>
				<div class="titleSec">תגובת המארח</div>
				<div class="sectionWrap">
					<div class="section txtarea big">
						<div class="label">תגובה</div>
						<textarea name="ownComment" class="textEditor"><?=$reviewData['ownComment']?></textarea>
					</div>
				</div>
			<?php } ?>
			<div class="clear"></div>
			<input type="submit" value="שמור" class="submit">
		</form>
	</div>
</div>



<script src="../../../app/tinymce/tinymce.min.js"></script>
<script>



	$(function(){
/*
	tinymce.init({
	  selector: 'textarea.textEditor' ,
	  height: 300,
	 plugins: [
		"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
		"table contextmenu directionality emoticons template textcolor paste  textcolor colorpicker textpattern"
	  ],
	  fontsize_formats: '8px 10px 12px 14px 16px 18px 20px 22px 24px 30px 36px',
	  toolbar1: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	  toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview | forecolor backcolor",
	  toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | visualchars visualblocks nonbreaking template pagebreak restoredraft"

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
*/

	$(".datePick").datepicker({
		format:"dd/mm/yyyy",
		changeMonth:true
	});
});
</script>