
<?php 


class Seo_manager {


	static function saveSeo($table,$ref,Array $levels = []){
		$data = typemap($_POST, [
			'seoTitle'   => ['int' => ['int' => 'string']],
			'seoH1'   => ['int' => ['int' => 'string']],
			//'seoLink'   => ['int' => ['int' => 'string']],
			'seoKeyword'   => ['int' => ['int' => 'string']],
			'seoDesc'   => ['int' => ['int' => 'string']],
			'LEVEL2'   => ['int' => ['int' => 'string']]
			
		]);
		$data['ref']=intval($ref);
		$data['table']=$table;
		/*$data['LEVEL2']=$levels[0];
		$data['LEVEL3']=$levels[1];
		$data['LEVEL4']=$levels[2];
		$data['LEVEL5']=$levels[3];*/

		$que = "SELECT `id` FROM alias_text WHERE `ref`=$ref AND `table`=\"$table\"" ;
		$checkId = udb::single_value($que);
		// saving data per domain
		foreach(DomainList::get() as $did => $dom){
			foreach(LangList::get() as $lid => $lang){
				$siteData = [
					'domainID'  => $did,
					'langID'    => $lid,
					'title'  => $data['seoTitle'][$did][$lid],
					'h1'  => $data['seoH1'][$did][$lid],
					'description'  => $data['seoDesc'][$did][$lid],
					'keywords'  => $data['seoKeyword'][$did][$lid],
					'ref'  => $data['ref'],
					'table'  => $data['table']
				];
			
				$siteData['LEVEL2'] = ($data['LEVEL2'][$did][$lid]?$data['LEVEL2'][$did][$lid]:$data['seoTitle'][$did][$lid]);
				


				$data['LEVEL1'] = globalLangSwitch($lid);			

				if(!$checkId){
					udb::insert('alias_text', $siteData);
				}else{
					udb::update('alias_text', $siteData, "`domainID`=$did AND `langID`=$lid AND `ref`=".$data['ref']." AND `table`='".$data['table']."'");
				}


			}
		}
	}



	static function showSeo($table,$ref) { 
		if($ref){
			$que = "SELECT * FROM `alias_text` WHERE `ref`=$ref AND `table`='$table'";
			$seo = udb::key_row($que, ['domainID','langID']);
		}
		
		?>
	<div class="mainSectionWrapper">
		<div class="sectionName">SEO</div>
		<?php foreach(DomainList::get() as $did => $dom){
				foreach(LangList::get() as $lid => $lang){ ?>
					<div class="domain" data-id="<?=$did?>">
						<div class="language" data-id="<?=$lid?>">
							<div class="inputLblWrap">
								<div class="labelTo">כותרת עמוד</div>
								<input type="text" placeholder="כותרת עמוד" name="seoTitle" value="<?=outDb($seo[$did][$lid]['title'])?>" />
							</div>
							<div class="inputLblWrap">
								<div class="labelTo">H1</div>
								<input type="text" placeholder="H1" name="seoH1" value="<?=outDb($seo[$did][$lid]['h1'])?>" />
							</div>
							<div class="section txtarea">
								<div class="inptLine">
									<div class="label">מילות מפתח</div>
									<textarea name="seoKeyword"><?=outDb($seo[$did][$lid]['keywords'])?></textarea>
								</div>
							</div>
							<div class="section txtarea">
								<div class="inptLine">
									<div class="label">תאור דף</div>
									<textarea name="seoDesc"><?=outDb($seo[$did][$lid]['description'])?></textarea>
								</div>
							</div>
							<?php /* ?>
							<div class="inputLblWrap">
								<div class="labelTo">קישור</div>
								<input type="text" placeholder="קישור" name="LEVEL2" value="<?=js_safe($seo[$did][$lid]['LEVEL2'])?>" />
							</div>
							<?php */ ?>
						</div>
					</div>
		<?php } } ?>
	</div>
	<?php } 
} ?>

