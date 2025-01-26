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
	udb::query("DELETE FROM `faqs` WHERE siteID=".$siteID." and domainID=".$domainID);

	?>
	<script>window.location.href='/cms/moduls/minisites/faqs/faqs.php?siteID=<?=$siteID?>&tab=<?=$tab?>&siteName=<?=$siteName?>'</script>
	<?php
	exit;
}
if ('POST' == $_SERVER['REQUEST_METHOD']) {

   $data = typemap($_POST, [
		'siteID' => 'int',
		'question'   => ['int'],
		'answer'    => ['int'=>['int']]
	]);

	udb::query("delete from faqs where siteID='".$data['siteID']."'"." and domainID=".$domainID);
	foreach($data['question'] as $question) {
		$answerID = $data['answer'][$question][0];
		if(intval($answerID)) {
			$insertArray = [];
			$insertArray['questionID'] = $question;
			$insertArray['answerID'] = $answerID;
			$insertArray['siteID'] = $data['siteID'];
            $insertArray["domainID"] = $domainID;
			udb::insert("faqs",$insertArray);
		}
	}






	?>
	<script>window.location.href='/cms/moduls/minisites/faqs/faqs.php?siteID=<?=$siteID?>&tab=<?=$tab?>&siteName=<?=$siteName?>'</script>
	<?php
	exit;
}


//$que="SELECT * FROM `faqs` WHERE siteID=".$siteID);
// $fileDb= udb::single_row($que);
$siteQuestionsSql = "SELECT questionID,answerID FROM `faqs` WHERE siteID='".$siteID."' and domainID=".$domainID;
$siteQuestions  = udb::key_row($siteQuestionsSql,"questionID");


?>
<div class="loader">
	<div class="spinner"></div>
</div>
<div class="editItems">
    <div class="siteMainTitle"><?=$siteName?></div>
    <?php minisite_domainTabs($domainID,"2")?>
	<?=showTopTabs(0)?>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
		<?=LangList::html_select()?>
	</div>
    <form method="POST" class="manageItems" enctype="multipart/form-data">
        <input type="hidden" name="siteID" id="siteID" value="<?=$siteID?>">
		<div class="insertWrap">

				<?
				$questions = udb::full_list("select questionID,questionTitle from questions");
				foreach($questions as $question) {
					?>
					<div class="section">
						<div class="inputLblWrap">
							<div class="switchTtl"><?=$question['questionTitle']?></div>
							<label class="switch">
							  <input type="checkbox" name="question[]" id="question<?=$question['questionID']?>"
							  <?=$siteQuestions[$question['questionID']] ? ' checked="checked" ' : '';?> value="<?=$question['questionID']?>">
							  <span class="slider round"></span>
							</label>
						</div>
						<div class="section ">
							<div class="inptLine">
								<input type="radio" name="answer[<?=$question['questionID']?>][]" id="q<?=$question['questionID']?>answer0"
								<?=!intval($siteQuestions[$question['questionID']]['answerID']) ? ' checked="checked" ' : '';?> value="0">
								<label for="q<?=$question['questionID']?>answer0">ללא תשובה</label>
							</div>
						</div>
						<?
						$answers = udb::full_list("select ansID,answerTitle from answers where questionID=".$question['questionID']);
						foreach($answers as $answer) {
						?>
						<div class="section ">
							<div class="inptLine">
								<input type="radio" name="answer[<?=$question['questionID']?>][]" id="q<?=$question['questionID']?>answer<?=$answer['ansID']?>"
								<?=$siteQuestions[$question['questionID']]['answerID']==$answer['ansID'] ? ' checked="checked" ' : '';?> value="<?=$answer['ansID']?>">
								<label for="q<?=$question['questionID']?>answer<?=$answer['ansID']?>"><?=$answer['answerTitle']?></label>
							</div>
						</div>
						<?
						}
						?>

					</div>
					<?
				}
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
    <div id="alerts">
        <div class="container">
            <div class="closer"></div>
            <div class="title"></div>
            <div class="body"></div>
        </div>
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
