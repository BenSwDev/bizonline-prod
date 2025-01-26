<?php
include_once "../bin/system.php";
include_once "../bin/top_frame.php";




$siteID=intval($_GET['sID']);
$frameID=intval($_GET['frame']);

$position=1;

if('POST' == $_SERVER['REQUEST_METHOD']) {

	$cp=Array();
	$cp['lastUpdate'] = date("Y-m-d H:i:s");

	$cp['username'] = inDB($_POST['username']);
	if($_POST['password']!='ddddd'){
		$cp['password'] = sha1(sha1(sha1($_POST['password'])));
	}

	$cp['TITLE'] = inDB($_POST['TITLE']);
	$cp["address"] = inDB($_POST['address']);
	$cp["lineDesc"] = inDB($_POST['lineDesc']);
	$cp["lineDescSpecial"] = intval($_POST['lineDescSpecial']);
	$cp["arem"] = inDB($_POST['arem']);
	$cp["email"] = inDB($_POST['email']);
	$cp["fax"] = inDB($_POST['fax']);
	$cp["if_show"] = intval($_POST['if_show'])?"1":"0";
	$cp["showInSysFilters"] = intval($_POST['showInSysFilters'])?"1":"0";
	$cp["if_show_sitemap"] = intval($_POST['if_show_sitemap'])?"1":"0";
	$cp["dealPermission"] = intval($_POST['dealPermission'])?"1":"0";
	$cp["couples"] = intval($_POST['couples'])?"1":"0";
	$cp["link"] = inDB($_POST['link']);
	$cp["owners"] = inDB($_POST['owners']);
	$cp["owner_real"] = inDB($_POST['owner_real']);
	$cp["phone1"] = inDB($_POST['phone1']);
	$cp["phone2"] = inDB($_POST['phone2']);
	$cp["settlementID"] = intval($_POST['sett']);
	$cp["virtual"] = inDB($_POST['virtual']);
	$cp["colorSite"] = inDB($_POST['colorSite']);
	if(intval($_POST['sett'])){
		$que="SELECT TITLE FROM `settlements` WHERE settlementID=".intval($_POST['sett'])."";
		$city= udb::single_row($que);
	}


	if($_POST['address']){
		$location=getLocationNumbers(inDB($_POST['address']).($city?", ".$city['TITLE']:""));
		$cp["gps_lat"] = $location['lat'];
		$cp['gps_long'] = $location['long'];
	} else if($city){
		$location=getLocationNumbers($city['TITLE']);
		$cp["gps_lat"] = $location['lat'];
		$cp['gps_long'] = $location['long'];
	}
	$photo = pictureUpload('prPicture',"../../gallery/");
	if($photo){
	$cp["prPicture"] = $photo[0]['file'];
	}
	$logo = pictureUpload('prLogo',"../../gallery/");
	if($logo){
	$cp["prLogo"] = $logo[0]['file'];
	}
	$cp["virtual"] = inDB($_POST['virtual']);
	




	if($siteID){
		udb::update("sites", $cp, "siteID =".$siteID);
	} else {
		$siteID = udb::insert("sites", $cp);
		customsInit($siteID);
		
		udb::query("INSERT INTO `sitesPeriods`(`siteID`,`basePeriod`) VALUES(".$siteID.",1),(".$siteID.",2)");
		//mysql_query($sql) or die(mysql_error().nl2br($sql));

		$alias=Array();
		$alias['LEVEL1']="he";
		$alias['LEVEL2']="zimmer";
		$alias['LEVEL3']=inDB($_POST['TITLE']);
		$alias['h1']=inDB($_POST['TITLE']);
		$alias['title']=inDB($_POST['TITLE']);
		$alias['ref']=$siteID;
		$alias['table']='sites';
		udb::insert("alias", $alias);
	}






	if($siteID){
	udb::query("DELETE FROM `sitesOptions` WHERE siteID=".$siteID." ");
	$params=Array();
		if(isset($_POST['param'])){
			foreach($_POST['param'] as $key=>$val){
				$params['siteID']=$siteID;
				$params['optionID']=$key;
				udb::insert("sitesOptions", $params);
			}
		}
	udb::query("DELETE FROM `sitesOptionsTags` WHERE siteID=".$siteID." ");
	$tags=Array();
		if(isset($_POST['tags'])){
			foreach($_POST['tags'] as $key=>$val){
				$tags['siteID']=$siteID;
				$tags['optionID']=$key;
				udb::insert("sitesOptionsTags", $tags);
			}
		}
	
	}


	$cpDeal=Array();
	$cpDeal['dealType']=0;
	$cpDeal['dealVisible']=1;
	$cpDeal['siteID']=$siteID;
	$que="SELECT * FROM deals
		  WHERE dealType=0 AND siteID=".$siteID." ";
	$checkDeal=udb::single_row($que);
	if($checkDeal){
		$dealID = $checkDeal['dealID'];
	} else {
		$dealID = udb::insert("deals", $cpDeal);
	}

	$cpDealPortal=Array();
	$cpDealPortal['dealID']=$dealID;
	$cpDealPortal['portalID']=1;
	if($_POST['fromDate']){
		$date=explode("/", $_POST['fromDate']);
		$date=$date[2]."-".$date[1]."-".$date[0];
		$cpDealPortal["fromDate"] = $date;
	}
	if($_POST['toDate']){
		$date=explode("/", $_POST['toDate']);
		$date=$date[2]."-".$date[1]."-".$date[0];
		$cpDealPortal["toDate"] = $date;
	}

	
	$que="SELECT * FROM dealsPortals
		  WHERE dealID=".$dealID." AND portalID=1 ";
	$checkDeal=udb::single_row($que);
	if($checkDeal){
		udb::update("dealsPortals", $cpDealPortal, "dealID=".$dealID." AND portalID=1 ");
	} else {
		udb::insert("dealsPortals", $cpDealPortal);
	}

if(isset($_POST['portalID'])){
	foreach($_POST['portalID'] as $portalID=>$val){
		$fromDate = $_POST['portal_fromDate'][$portalID];
		$toDate = $_POST['portal_toDate'][$portalID];

		$portalTITLE = $_POST['portal_TITLE'][$portalID];
		$portalPhone =  $_POST['portal_phone2'][$portalID];
		$portalArem =  $_POST['portal_arem'][$portalID];

		$portalAr=Array();
		$portalAr["if_show"] = intval($_POST['portal_if_show'][$portalID])?"1":"0";
		$portalAr["if_show_sitemap"] = intval($_POST['portal_if_show_sitemap'][$portalID])?"1":"0";
		$portalAr["TITLE"] = $portalTITLE;
		$portalAr["phone2"] = $portalPhone;
		$portalAr["arem"] = $portalArem;
		$portalAr["PortalID"] = $portalID;
		$portalAr["LangID"] = 1;
		$portalAr["siteID"] = $siteID;

		$que="SELECT siteID FROM sites_text WHERE PortalID=".$portalID." AND siteID=".$siteID." AND LangID=1 ";
		$checkPrtl = udb::single_row($que);
		if($checkPrtl){
			udb::update("sites_text", $portalAr, "PortalID=".$portalID." AND siteID=".$siteID." AND LangID=1");
		} else {
			udb::insert("sites_text", $portalAr);
		}



		$cpDealPortalAr=Array();
		$cpDealPortalAr['dealID'] = $dealID;
		$cpDealPortalAr['portalID'] = $portalID;
		if($fromDate){
			$date=explode("/", $fromDate);
			$date=$date[2]."-".$date[1]."-".$date[0];
			$cpDealPortalAr["fromDate"] = $date;
		}
		if($toDate){
			$date=explode("/", $toDate);
			$date=$date[2]."-".$date[1]."-".$date[0];
			$cpDealPortalAr["toDate"] = $date;
		}

		$que="SELECT * FROM dealsPortals
		  WHERE dealID=".$dealID." AND portalID=".$portalID." ";
		$checkDeal=udb::single_row($que);
		if($checkDeal){
			udb::update("dealsPortals", $cpDealPortalAr, "dealID=".$dealID." AND portalID=".$portalID." ");
		} else {
			udb::insert("dealsPortals", $cpDealPortalAr);
		}

	}
}


	if(!intval($_POST['refresh'])){ // save and close iframe ?>
		<script> window.parent.location.reload(); window.parent.closeTab(<?=$frameID?>); </script>
	<?php
	} else { // save and get alert success ?>
		<script>window.parent.formAlert("green", "עודכן בהצלחה", ""); </script>
	<?php }
	
}
$que="SELECT * FROM `sites` WHERE siteID=".$siteID."";
$site= udb::single_row($que);

$que = "SELECT * FROM `settlements`	WHERE 1 ORDER BY `TITLE`";
$setts= udb::full_list($que);

$que = "SELECT * FROM `MainPages` WHERE ifShow=1 AND MainPageType=10 ORDER BY `ShowOrder`";
$params= udb::key_list($que, "inType");


$que = "SELECT * FROM `MainPages` WHERE ifShow=1 AND MainPageType=15 ORDER BY `ShowOrder`";
$tags= udb::full_list($que);


if($siteID){
	$que = "SELECT * FROM `sitesOptions` WHERE siteID=".$siteID." ";
	$siteParams= udb::key_row($que, "optionID");
	
	$que = "SELECT * FROM `sitesOptionsTags` WHERE siteID=".$siteID." ";
	$siteTags= udb::key_row($que, "optionID");
}


$menu = include "site_menu.php";

$que="SELECT * FROM `deals` 
	  INNER JOIN dealsPortals USING (dealID) 
	  WHERE dealType=0 AND portalID=1 AND siteID=".$siteID."";
$deal= udb::single_row($que);

$que="SELECT * FROM `portals` WHERE portalID!=1";
$portals= udb::key_row($que, "portalID");

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
	
	<form method="POST" id="myform" enctype="multipart/form-data">
		<input type="hidden" name="refresh" value="0" id="refresh">
		<input type="password" style="width:0;height:0;visibility:hidden;">
		<input type="text" style="width:0;height:0;visibility:hidden;">
		<b>מידע ראשי</b>
		<?php if($site['siteID']) { ?>
		<div class="miniTabs general" style="margin-right:50px;">
			<div class="tab active" data-portalid="1"><p>צימרטופ</p></div>
			<?php foreach($portals as $portal){ ?>
				<div class="tab<?=$portal['portalID']==1?" active":""?>" data-portalid="<?=$portal['portalID']?>"><p><?=$portal['portalName']?></p></div>
			<?php } ?>
		</div>
		<?php } ?>
		<div id="portalForm1" class="frm" >
			<div class="section">
				<div class="inptLine">
					<div class="label">שם משתמש: </div>
					<input type="text" value="<?=$site['username']?>" name="username" class="inpt" autocomplete="off">
				</div>
			</div>

			<div class="section">
				<div class="inptLine">
					<div class="label">סיסמא: </div>
					<input type="password" value="ddddd" name="password" class="inpt" autocomplete="off">
				</div>
			</div>
			<div  style="clear:both;"></div>
			<div class="section">
				<div class="inptLine">
					<div class="label">מתאריך: </div>
					<input type="text" value="<?=$deal['fromDate'] && $deal['fromDate']!="0000-00-00" ? date("d/m/Y", strtotime($deal['fromDate'])) : ""?>" name="fromDate" class="inpt datepicker">
				</div>
			</div>

			<div class="section">
				<div class="inptLine">
					<div class="label">עד תאריך: </div>
					<input type="text" value="<?=$deal['toDate'] && $deal['fromDate']!="0000-00-00" ? date("d/m/Y", strtotime($deal['toDate'])) : ""?>" name="toDate" class="inpt datepicker">
				</div>
			</div>
			<div  style="clear:both;"></div>
			<div class="section">
				<div class="inptLine">
					<div class="label">שם העסק: </div>
					<input type="text" value="<?=outDb($site['TITLE'])?>" name="TITLE" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">שם מוצג בעל עסק </div>
					<input type="text" value="<?=outDb($site['owners'])?>" name="owners" class="inpt">
				</div>
			</div>		<div class="section">
				<div class="inptLine">
					<div class="label">שם בעל עסק ל SMS</div>
					<input type="text" value="<?=outDb($site['owner_real'])?>" name="owner_real" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">אימייל: </div>
					<input type="text" value="<?=$site['email']?>" name="email" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">מוצג באתר: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$site['if_show']==0?"":"checked"?> name="if_show" id="ifShow_<?=$siteID?$siteID:0?>">
						<label for="ifShow_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">שייך למרכז הזמנות: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$site['showInSysFilters']==0?"":"checked"?> name="showInSysFilters" id="ifShow_<?=$siteID?$siteID:0?>">
						<label for="ifShow_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">מוצג במפת האתר: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$site['if_show_sitemap']==0?"":"checked"?> name="if_show_sitemap" id="ifShow2_<?=$siteID?$siteID:0?>">
						<label for="ifShow2_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">סימון לזוגות בלבד: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$site['couples']==0?"":"checked"?> name="couples" id="ifShow3_<?=$siteID?$siteID:0?>">
						<label for="ifShow3_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">טלפון בעל עסק: </div>
					<input type="text" value="<?=$site['phone1']?>" name="phone1" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">מספר מוצג: </div>
					<input type="text" value="<?=$site['phone2']?>" name="phone2" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">פקס: </div>
					<input type="text" value="<?=$site['fax']?>" name="fax" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">אתר העסק: </div>
					<input type="text" value="<?=$site['link']?>" name="link" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">כתובת: </div>
					<input type="text" value="<?=$site['address']?>" name="address" class="inpt">
				</div>
			</div>

			<div class="section">
				<div class="inptLine">
					<div class="label">יישוב: </div>
					<select name="sett">
					<option value="0">בחר יישוב</option>
					<?php foreach($setts as $sett){ ?>
					<option value="<?=$sett['settlementID']?>" <?=$sett['settlementID']==$site['settlementID']?"selected":""?>><?=$sett['TITLE']?></option>
					<?php } ?>
					</select>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">וידאו: </div>
					<input type="text" value="<?=$site['virtual']?>" name="virtual" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">תיאור קצר: </div>
					<textarea name="arem"><?=outDB($site['arem'])?></textarea>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">תיאור ריבוע מקדים: (i) </div>
					<input type="text" value="<?=$site['lineDesc']?>" name="lineDesc" class="inpt">
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">כותרת דף מידע: </div>
					<select name="lineDescSpecial">
						<option value="0">-</option>
						<option value="1" <?=$site['lineDescSpecial']==1?"selected":""?>>ייתכנו שינויים במחיר הסופי</option>
						<option value="2" <?=$site['lineDescSpecial']==2?"selected":""?>>המחיר הטוב ביותר בשבילך!</option>
					</select>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">צבעים: </div>
					<select name="colorSite">
						<option value="0">בחר צבע</option>
						<option value="1" <?=$site['colorSite']==1?"selected":""?> >כתום</option>
						<option value="2" <?=$site['colorSite']==2?"selected":""?>>ירוק</option>
						<option value="3" <?=$site['colorSite']==3?"selected":""?>>תכלת</option>
					</select>
				</div>
			</div>
			<div class="section">
				<div class="inptLine">
					<div class="label">עורך מבצעים במערכת יוזר: </div>
					<div class="chkBox">
						<input type="checkbox" value="1" <?=$site['dealPermission']==0?"":"checked"?> name="dealPermission" id="ifShow4_<?=$siteID?$siteID:0?>">
						<label for="ifShow4_<?=$siteID?$siteID:0?>"></label>
					</div>
				</div>
			</div>

			<div  style="clear:both;"></div>
			<div class="section">
				<div class="inptLine">
					<div class="label">תמונה מייצגת: </div>
					<input type="file" name="prPicture" class="inpt" value="<?=$site['prPicture']?>"><br>
				</div>
			</div>
			<?php if($site['prPicture']){ ?>
			<div class="section">
				<div class="inptLine">
					<img src="../../gallery/<?=$site['prPicture']?>" style="width:100%">
				</div>
			</div>
			<?php } ?>
			<div class="section">
				<div class="inptLine">
					<div class="label">לוגו: </div>
					<input type="file" name="prLogo" class="inpt" value="<?=$site['prLogo']?>"><br>
				</div>
			</div>
			<?php if($site['prLogo']){ ?>
				<div class="section">
					<div class="inptLine">
						<img src="../../gallery/<?=$site['prLogo']?>" style="width:100%">
					</div>
				</div>
			<?php } ?>
			<b>אבזור הצימר</b>
			<div class="sectionParams">
				<?php
				$paramTypes=Array();
				$paramTypes[1]="פנימי";
				$paramTypes[2]="חיצוני";
				$paramTypes[3]="כללי";
				if($params){
				foreach($params as $type=>$param){ ?>
				<b style="display:block;margin-bottom:5px;"><?=$paramTypes[$type]?></b>
				<?php foreach($param as $par){
				if(preg_match('/1/',$par['tags'])) {?>
				<div class="param">
					<input type="checkbox" name="param[<?=$par['MainPageID']?>]" <?=$siteParams[$par['MainPageID']]?"checked":""?> value="<?=$par['MainPageID']?>" id="param_<?=$par['MainPageID']?>">
					<label for="param_<?=$par['MainPageID']?>"><?=outDB($par['MainPageTitle'])?></label>
				</div>
				<?php } }
				}
			} ?>
			</div>
			<b style="margin-top:10px">למי מתאים</b>
			<div class="sectionParams">
				<?php if($tags){
				foreach($tags as $tag){ ?>
				<div class="param">
					<input type="checkbox" name="tags[<?=$tag['MainPageID']?>]" <?=$siteTags[$tag['MainPageID']]?"checked":""?> value="<?=$tag['MainPageID']?>" id="tags_<?=$tag['MainPageID']?>">
					<label for="tags_<?=$tag['MainPageID']?>"><?=$tag['MainPageTitle']?></label>
				</div>
				<?php }
				} ?>
			</div>
			<div  style="clear:both;"></div>
		</div>
		<?php
		if($site['siteID']) {
			$que = "SELECT * FROM sites_text WHERE siteID=" . $site['siteID'] . " ";
			$dataPortals = udb::key_row($que, "PortalID");

			$que = "SELECT dealsPortals.PortalID, dealsPortals.dealID, dealsPortals.fromDate, dealsPortals.toDate FROM dealsPortals INNER JOIN deals USING(dealID) WHERE deals.dealType=0 AND deals.siteID=" . $site['siteID'] . "  ";
			$dealsPortals = udb::key_row($que, "PortalID");


			foreach ($portals as $portal) { ?>
				<div id="portalForm<?= $portal['portalID'] ?>" class="frm" style="display:none;">
					<input type="hidden" name="portalID[<?= $portal['portalID'] ?>]" value="<?= $portal['portalID'] ?>">
					<div class="section">
						<div class="inptLine">
							<div class="label">מתאריך:</div>
							<input type="text"
								   value="<?= $dealsPortals[$portal['portalID']]['fromDate'] && $dealsPortals[$portal['portalID']]['fromDate'] != "0000-00-00" ? date("d/m/Y", strtotime($dealsPortals[$portal['portalID']]['fromDate'])) : "" ?>"
								   name="portal_fromDate[<?= $portal['portalID'] ?>]" class="inpt datepicker">
						</div>
					</div>

					<div class="section">
						<div class="inptLine">
							<div class="label">עד תאריך:</div>
							<input type="text"
								   value="<?= $dealsPortals[$portal['portalID']]['toDate'] && $dealsPortals[$portal['portalID']]['fromDate'] != "0000-00-00" ? date("d/m/Y", strtotime($dealsPortals[$portal['portalID']]['toDate'])) : "" ?>"
								   name="portal_toDate[<?= $portal['portalID'] ?>]" class="inpt datepicker">
						</div>
					</div>
					<div style="clear:both;"></div>
					<div class="section">
						<div class="inptLine">
							<div class="label">שם העסק:</div>
							<input type="text" value="<?= outDb($dataPortals[$portal['portalID']]['TITLE']) ?>"
								   name="portal_TITLE[<?= $portal['portalID'] ?>]" class="inpt">
						</div>
					</div>
					<div class="section">
						<div class="inptLine">
							<div class="label">מספר מוצג:</div>
							<input type="text" value="<?= $dataPortals[$portal['portalID']]['phone2'] ?>"
								   name="portal_phone2[<?= $portal['portalID'] ?>]" class="inpt">
						</div>
					</div>
					<div style="clear:both;"></div>
					<div class="section">
						<div class="inptLine">
							<div class="label">מוצג באתר:</div>
							<div class="chkBox">
								<input type="checkbox"
									   value="1" <?= $dataPortals[$portal['portalID']]['if_show'] == 0 ? "" : "checked" ?>
									   name="portal_if_show[<?= $portal['portalID'] ?>]"
									   id="ifShow<?= $portal['portalID'] ?>_<?= $siteID ? $siteID : 0 ?>">
								<label for="ifShow<?= $portal['portalID'] ?>_<?= $siteID ? $siteID : 0 ?>"></label>
							</div>
						</div>
					</div>
					<div class="section">
						<div class="inptLine">
							<div class="label">מוצג במפת האתר:</div>
							<div class="chkBox">
								<input type="checkbox"
									   value="1" <?= $dataPortals[$portal['portalID']]['if_show_sitemap'] == 0 ? "" : "checked" ?>
									   name="portal_if_show_sitemap[<?= $portal['portalID'] ?>]"
									   id="ifShow2<?= $portal['portalID'] ?>_<?= $siteID ? $siteID : 0 ?>">
								<label for="ifShow2<?= $portal['portalID'] ?>_<?= $siteID ? $siteID : 0 ?>"></label>
							</div>
						</div>
					</div>
					<div style="clear:both;"></div>
					<div class="section">
						<div class="inptLine">
							<div class="label">תיאור קצר:</div>
							<textarea
								name="portal_arem[<?= $portal['portalID'] ?>]"><?= outDB($dataPortals[$portal['portalID']]['arem']) ?></textarea>
						</div>
					</div>

				</div>
			<?php }
		} ?>
		<div class="section sub">
			<div class="inptLine">
				<?php if($siteID){ ?>
				<input type="buton" value="עדכן" class="submit" onclick="document.getElementById('refresh').value=1;document.getElementById('myform').submit(); ">
				<?php } ?>
				<input type="submit" value="<?=$siteID?"שמור":"הוסף"?>" class="submit">
			</div>
		</div>
	</form>
</div>
</section>
<div id="alerts">
    <div class="container">
        <div class="closer"></div>
        <div class="title"></div>
        <div class="body"></div>
    </div>
</div>
<script src="<?=$root;?>/app/jquery-ui.min.js"></script>
<script>
$(function() {
	$( ".datepicker" ).datepicker({

	});
});


$(".general .tab").click(function(){
	$(".general .tab").removeClass("active");
	$(this).addClass("active");

	var ptID = $(this).data("portalid");
	$(".frm").css("display","none");
	console.log(ptID);
	$("#portalForm"+ptID).css("display","block");
});
</script>
</body>
</html>