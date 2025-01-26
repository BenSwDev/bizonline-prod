<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../../../_globalFunction.php";


$pageID=intval($_GET['pageID']);
$langID=intval($_GET['LangID'])?intval($_GET['LangID']):1;


if ('POST' == $_SERVER['REQUEST_METHOD']){
    try {
		  	

		
	    $data = typemap($_POST, [
           // 'optionName'   =>   ['int' => ['int' => 'string']],
            'optionName'   =>  'string',
            'optionMark'   =>  'float'
        ]);

	;
		  
		foreach($_POST['ids'][1] as $key=>$val){
		
			$siteData=Array();
			$siteData["optionName"] = $_POST['optionName'][1][$key];
			$siteData["optionMark"] = $_POST['optionMark'][1][$key];
			$siteData["optionCategory"] = $pageID;

			if($key){
				udb::update("reviewOptions", $siteData, "id =".$key);
			} else {
				if($siteData["optionName"] && $siteData['optionMark']){
					$key = udb::insert("reviewOptions", $siteData);
				}
			}	
		}
		
        // main site data
		/*
        $siteData = [
            'optionName' => $data['optionName'],
            'optionMark' => $data['optionMark'],
            'optionCategory' => $pageID
        ];
        $pageID = udb::insert('reviewOptions', $siteData);

          //  udb::update('reviewOptions', $siteData, '`id` = ' . $pageID);*/
        
	}

    catch (LocalException $e){
        // show error
    } ?>

	<script>window.parent.location.reload(); window.parent.closeTab();</script>
<?php

}

if ($pageID){
    $data = udb::full_list("SELECT * FROM `reviewOptions` WHERE `optionCategory`=".$pageID);

}

?>


<style type="text/css">
.editItems input[type='checkbox']{margin: 4px !important}
.delBtn{width: 80px;height: 30px;background: #bebaba;text-align: center;color: #fff;line-height: 30px;cursor: pointer;border-radius: 5px;border: 1px solid #a0a0a0;font-size: 14px;}
.inputLblWrap{margin: 1% 4%;}
</style>
<div class="editItems">
	<div class="miniTabs">
		<div class="tab" style="margin-right: 30px;" onclick="window.location.href='frame.php?pageID=<?=$pageID?>'"><p>קטגוריות</p></div>
		<div class="tab active"><p>אופציות</p></div>
	</div>
    <h1><?=$site['categoryName']?outDb($site['categoryName']):"אופציות"?></h1>
	<form method="POST" id="myform" enctype="multipart/form-data">
		<div class="frm" >
			<?php
			if($data) {
			foreach($data as $opt) { ?>
			<input type="hidden" name="ids[1][<?=$opt['id']?>]" value="<?=$opt['id']?>" >
			<div class="inputLblWrap">
				<div class="labelTo">שם האופציה</div>
				<input type="text" placeholder="שם האופציה" name="optionName[1][<?=$opt['id']?>]" value="<?=js_safe($opt['optionName'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">ציון</div>
				<input type="text" placeholder="ציון" name="optionMark[1][<?=$opt['id']?>]" value="<?=js_safe($opt['optionMark'])?>" />
			</div>
			<div class="inputLblWrap">
				<div class="delBtn" onClick="delOpt(<?=$opt['id']?>)">מחק</div>
			</div>
		
			<div style="clear:both;"></div>
			<?php } } ?>
			<input type="hidden" name="ids[1][0]">
			<div class="inputLblWrap">
				<div class="labelTo">שם האופציה</div>
				<input type="text" placeholder="שם האופציה" name="optionName[1][0]" value="" />
			</div>
			<div class="inputLblWrap">
				<div class="labelTo">ציון</div>
				<input type="text" placeholder="ציון" name="optionMark[1][0]" value="" />
			</div>
			<div style="clear:both;"></div>
		</div>
		<div style="clear:both;"></div>

		<div class="section sub">
			<div class="inptLine">
				<input type="submit" value="הוסף" class="submit">
			</div>
		</div>
	</form>
</div>


<script type="text/javascript">
function delOpt(id){
	
	if(confirm("האם אתה בטוח?")){
		$.post('delOption.php',{'id':id}).done(function(){
		
			window.location.reload();
		});
	}
}
</script>