<?php



	function searchUI(){ ?>

	<div class="searchCms">
		<form method="GET">
			<input type="hidden" name="domainID" value="<?=$_GET['domainID']?>">
			<input type="text" name="free" placeholder="שם דף" value="<?=$_GET['free']?>">
			<div  class="secParmLine">
				<select name="area">
					<option value="0">אזור</option>
					<?php foreach($areas as $area){ ?>
					<option value="<?=$area['areaID']?>" <?=($area['areaID']==$_GET['area']?"selected":"")?> ><?=$area['TITLE']?></option>
					<?php } ?>
				</select>
				<select name="city">
					<option value="0" >ישוב</option>
					<?php foreach($citys as $city){ ?>
					<option value="<?=$city['settlementID']?>" <?=($city['settlementID']==$_GET['city']?"selected":"")?> ><?=$city['TITLE']?></option>
					<?php } ?>
				</select>
				<select name="attr">
					<option value="0">אבזורים</option>
					<?php foreach($attrs as $attr){ ?>
					<option value="<?=$attr['attrID']?>" <?=($attr['attrID']==$_GET['attr']?"selected":"")?> ><?=$attr['defaultName']?></option>
					<?php } ?>
				</select>
			</div>
			<a href="index.php?domainID=<?=$_GET['domainID']?>">נקה</a>
			<input type="submit" value="חפש">	
		</form>
	</div>
	
	
	<?php }

	function searchSql(){

		global $where ="1 = 1 ";
		if($_GET['free']){
			$where .= "AND `title` LIKE '%".$_GET['free']."%'";
		}
		if($_GET['area']){
			$where .= "AND `data` LIKE '%\"area\":".$_GET['area']."%'"; 
		}
		if($_GET['city']){
			$where .= "AND `data` LIKE '%\"city\":".$_GET['city']."%'"; 
		}
		if($_GET['attr']){
			$where .= "AND `data` LIKE '%\"attr\":[".$_GET['attr']."%' OR '%,".$_GET['attr']."]%'"; 
		}
	
	
	}


 ?>