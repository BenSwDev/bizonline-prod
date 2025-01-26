<?php
include_once "../../../bin/system.php";
//include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";
include_once "../../../bin/top.php";

const BASE_LANG_ID = 1;

$domainID = DomainList::active();
$langID   = LangList::active();



if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
	
	    $data = typemap($_POST, [
            'html_text'   => ['int' => ['int' => 'html']]
			

        ]);

        // main site data
        $siteData = [
            'text' => $data['html_text'][1][BASE_LANG_ID]  
        ];
        $siteData2 = [
            'agreement1' => $data['html_text'][1][BASE_LANG_ID]  
        ];
		
        udb::update('defaultAgr', $siteData, '`agrName` = "agreement1"');
        udb::update('sites_langs', $siteData2, '1');
        udb::update('translations', ['translation_text' => $data['html_text'][1][BASE_LANG_ID]], "`table_name` = 'sites' AND `field_name` = 'agreement1' AND `lang_id` = " . BASE_LANG_ID);
	}

	

    catch (LocalException $e){
        // show error
    } 

}


   $agg = udb::single_row("SELECT * FROM `defaultAgr` WHERE 1");


?>

<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
</style>
<div class="editItems">
    <h1><?=$site['mainPageTitle']?outDb($site['mainPageTitle']):"הוספת דף חדש"?></h1>
	<div class="inputLblWrap langsdom">
		<div class="labelTo">שפה</div>
        <?=LangList::html_select()?>
	</div>
	<div class="inputLblWrap langsdom domainsHide">
		<div class="labelTo">דומיין</div>
        <?=DomainList::html_select()?>
	</div>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="pageType" value="<?=$pageType?>">
		<div class="frm" >
			<?php 
			foreach(DomainList::get() as $did => $dom){
				foreach(LangList::get() as $lid => $lang){ ?>
					<div class="domain" data-id="<?=$did?>">
						<div class="language" data-id="<?=$lid?>">
							<div class="section txtarea big">
								<div class="inptLine">
									<div class="label noFloat">טקסט</div>
									<textarea class="textEditor" name="html_text"><?=outDb($agg['text'])?></textarea>
								</div>
							</div>
						</div>
					</div>
			<?php } } ?>


		</div>
		<div style="clear:both;"></div>
		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="שמור" class="submit">
			</div>
		</div>
	</form>
</div>

<script src="../../../app/tinymce/tinymce.min.js"></script>
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


});


	tinymce.init({
	  selector: 'textarea.textEditor' ,
	  height: 500,
	  directionality : "rtl",
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

</script>

<?php

include_once "../../../bin/footer.php";
?>