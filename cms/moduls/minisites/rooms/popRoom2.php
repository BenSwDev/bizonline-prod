<?php
include_once "../../../bin/system.php";
include_once "../../../bin/top_frame.php";
include_once "../mainTopTabs.php";
include_once "../../../_globalFunction.php";
include_once "../../../classes/class.SearchManager.php";

$cleanTime = [1 => '15 דקות', 2 => '30 דקות', 3 => '45 דקות', 4 => 'שעה', 6 => 'שעה וחצי', 8 => 'שעתיים',12 => '3 שעות', 16 => '4 שעות'];
$siteID = intval($_GET['siteID']);
$roomID = intval($_POST['roomID'] ?? $_GET['roomID'] ?? 0);

if($_GET['srdel']==1 && $_GET['spaceID']!=""){

	udb::query("DELETE FROM `spaces` WHERE spaceID=".intval($_GET['spaceID']));
	udb::query("DELETE FROM `spaces_langs` WHERE spaceID=".intval($_GET['spaceID']));
	udb::query("DELETE FROM `spaces_accessories` WHERE spaceID=".intval($_GET['spaceID']));
	udb::query("DELETE FROM `spaces_accessories_langs` WHERE spaceID=".intval($_GET['spaceID']));
	$reload = "/cms/moduls/minisites/rooms/popRoom.php?roomID=".$roomID."&siteID=".$siteID."&openunits=true";
?>

<script>window.parent.location.reload(); window.location.href = "<?=$reload?>"; //removed by Gal should Return to rooms in unit section//window.parent.closeTab();</script>
<?php
}

const BASE_LANG_ID = 1;

$siteID = intval($_GET['siteID']);
$roomID = intval($_POST['roomID'] ?? $_GET['roomID'] ?? 0);
$siteName = $_GET['siteName'];
$domainID = DomainList::active();



if ('POST' == $_SERVER['REQUEST_METHOD']){

	$que = "SELECT * FROM rooms_units WHERE roomID=".$roomID;
	$roomUnits = udb::full_list($que);

    $active = 0;
    if ($siteID)
        $active = udb::single_value("SELECT `active` FROM `sites` WHERE `siteID` = " . $siteID);

    try {
        $data = typemap($_POST, [
            'roomTitle'   => [ 'int' => ['int' => 'string']],
            'roomNote'   => [ 'int' => ['int' => 'string']],
            '!active'    => ['int' => 'int'],
            '!recommend'    => ['int' => 'int'],
            'roomDesc'  => ['int' => ['int' => 'html']],
			'descToAttr' => ['int' => 'string'],
            'roomFeatures'  => ['int' => ['int' => 'html']],
            '!roomType'   => 'int',
            'roomCount'   => 'int',
			'!attributes'    => ['int' => 'int'],
            '!roomOrPage'   => 'int',
            '!maxKids'   => 'int',
            '!maxAdults'  => 'int',
            '!maxInfants' => 'int',
            '!maxGuests'  => 'int',
            '!basisGuests'   => 'int',
            '!couplesOrFamily'   => 'int',
            'roomSize'   => 'string',
			'roomSizeTotal'   => 'string',
            '!showSpaceAccessories'   => 'int',
            'hour'   => 'int',
            'twoHours'   => 'int',
            'threeHours'   => 'int',
            'night'   => 'int',
            'weekend'   => 'int',
            '!cleanTime'   => 'int',
            'exID' => 'string',
			'showKidsAndAdults' => 'int',
            'vis' => ['int' => 'int']
        ]);
		// print_r($data);
		// exit;

        // main room data
		//'roomCount' => max($data['roomCount'], count($roomUnits)) changed by gal 12.11.20
		 $siteData = [
            'active'    => $data['active'][$domainID] ?? 0,
            'recommend'    => $data['recommend'][$domainID] ?? 0,
            'siteID'    => $siteID,
            'roomName'  => $data['roomTitle'][$domainID][BASE_LANG_ID],
			'roomType'  => $data['roomType'],
			'roomCount' => $data['roomCount'],
			'roomOrPage' => $data['roomOrPage'],
			'maxKids'	 => $data['maxKids'],
            'maxInfants' => $data['maxInfants'],
			'maxAdults'	 => $data['maxAdults'],
			'maxGuests'	 => $data['maxGuests'],
			'basisGuests' => $data['basisGuests'],
			'roomSize' => $data['roomSize'],
			'roomSizeTotal' => $data['roomSizeTotal'],
			'showSpaceAccessories' => $data['showSpaceAccessories'],
			'couplesOrFamily' => $data['couplesOrFamily'],
            'externalRoomID' => $data['exID'],
            'cleanTime'     => $data['cleanTime'],
			'showKidsAndAdults'=> $data['showKidsAndAdults'],
        ];

        if (!$roomID){      // opening new room
            $roomID = udb::insert('rooms', $siteData);


        } else {
            udb::update('rooms', $siteData, '`roomId` = ' . $roomID);

        }

		if(!$roomUnits && $siteData['roomCount']){
			for($i=1;$i<=$siteData['roomCount'];$i++){
				udb::insert('rooms_units',['roomID'=>$roomID, 'unitName' => $siteData['roomCount'] > 1? $siteData['roomName']."_".$i : $siteData['roomName'] , 'showorder' => $i]);

			}
		}
		elseif($roomUnits && $siteData['roomCount']){
			if($siteData['roomCount'] > count($roomUnits)){
				for($i=count($roomUnits)+1;$i<=$siteData['roomCount'];$i++){
					udb::insert('rooms_units',['roomID'=>$roomID, 'unitName' => $siteData['roomName']."_".$i, 'showorder' => $i]);
				}

			}
			elseif($siteData['roomCount'] < count($roomUnits)){
				//udb::query("DELETE FROM `rooms_units` WHERE `roomID`= ".$roomID." AND `showorder` IN (".implode(",",range($siteData['roomCount']+1,count($roomUnits))).")");
				//udb::update('rooms', ['roomCount' => count($roomUnits)], "`roomID` = " . $roomID);//line removed by gal 12.11.20

			}/*else{
				$i=count($roomUnits)+1;
				foreach($roomUnits as $roomUnit){
					udb::update('rooms_units',['unitName' => $siteData['roomCount'] > 1? $siteData['roomName']."_".$i : $siteData['roomName']], 'unitID='.$roomUnit['unitID']);
					$i++;
				}

			}*/

		}


		udb::query("DELETE FROM room_pricesTok where `roomID`=".$roomID);
        $sitePrices = [
            'roomID'    => $roomID,
            'hour'    => $data['hour'],
            'twoHours'    => $data['twoHours'],
            'threeHours'    => $data['threeHours'],
            'night'  => $data['night'],
			'weekend'  => $data['weekend']
        ];
             udb::insert('room_pricesTok', $sitePrices);


/* save attribute*/
if($domainID == 1) {
            $olda = udb::single_column("SELECT `attrID` FROM `rooms_attributes` WHERE `roomID` = " . $roomID);
		if($data['attributes']){
		    $que = [];
			foreach($data['attributes'] as $attr) {
				$attrDesc = $data['descToAttr'][$attr];
				//$que[] = "(" . $roomID . ", " . $attr . " , '".$attrDesc."')";
				$que[] = '(' . $roomID . ', ' . $attr . ', ' . ($data['vis'][$attr] ? 1 : 0) . ', "' . udb::escape_string($attrDesc) . '")';
			}
            $didupdate = udb::query("INSERT INTO `rooms_attributes`(`roomID`, `attrID`, `shown`, `attrDesc`) VALUES" . implode(',', $que) . " ON DUPLICATE KEY UPDATE `attrID` = VALUES(`attrID`), `shown` = VALUES(`shown`), `attrDesc` = VALUES(`attrDesc`)");
            unset($que);
		}

        $new = array_diff($data['attributes'] ?? [], $olda);
        if ($old = array_diff($olda, $data['attributes'] ?? []))
            udb::query("DELETE FROM `rooms_attributes` WHERE `roomID` = " . $roomID . " AND `attrID` IN (" . implode(',', $old) . ")");
}



        // saving data per domain
       // foreach(DomainList::get() as $did => $dom){
         //   if ($did > 0) {     // no need to save "default" domain, as it is already saved in main table
                // inserting/updating data in domains table
                udb::insert('rooms_domains', [
                    'roomID'   => $roomID,
                    'domainID' => $domainID,
					'active'   => $data['active'][$domainID] ?? 0,
					'recommend'   => $data['recommend'][$domainID] ?? 0

                ], true);
        //    }

            // saving data per domain / language
            foreach(LangList::get() as $lid => $lang){
                // inserting/updating data in domains table
                udb::insert('rooms_langs', [
                    'roomID'    => $roomID,
                    'domainID'    => $domainID,
                    'langID'    => $lid,
                    'roomName' => $data['roomTitle'][$domainID][$lid],
                    'roomNote' => $data['roomNote'][$domainID][$lid],
                    'roomDesc' => $data['roomDesc'][$domainID][$lid],
                    'roomFeatures' => $data['roomFeatures'][$domainID][$lid]
                ], true);
            }

        //};

		udb::query("DELETE FROM room_type_search where `roomID`=".$roomID);
		if($_POST['roomTypesSearch']){

			foreach($_POST['roomTypesSearch'] as $type){

				$roomSearchType = ['roomID' => $roomID, 'roomType' => $type];
				udb::insert('room_type_search',$roomSearchType);

			}

		}


		$dataSeo = typemap($_POST, [
			'title'   => ['int' => ['int' => 'string']],
			'h1'   => ['int' => ['int' => 'string']],
			'seoKeyword'   => ['int' => ['int' => 'string']],
			'seoDesc'   => ['int' => ['int' => 'string']]

		]);
		$dataSeo['ref']=$roomID;
		$dataSeo['table']="rooms";


		// saving data per domain
		/*
		foreach(DomainList::get() as $did => $dom){
			foreach(LangList::get() as $lid => $lang){
				$siteDataSeo = [
					'domainID'  => $did,
					'langID'    => $lid,
					'title'  => $dataSeo['title'][$did][$lid],
					'h1'  => $dataSeo['h1'][$did][$lid],
					'description'  => $dataSeo['seoDesc'][$did][$lid],
					'keywords'  => $dataSeo['seoKeyword'][$did][$lid],
					'ref'  => $dataSeo['ref'],
					'table'  => $dataSeo['table']
				];



				$siteDataSeo['LEVEL1'] = globalLangSwitch($lid);
				$siteDataSeo['LEVEL2'] = $dataSeo['title'][$did][$lid];

				$que = "SELECT `id` FROM `alias_text` WHERE `ref`=$roomID AND `table`='rooms' AND `domainID`=$did AND `langID`=$lid" ;
				$checkId = udb::single_value($que);

				if(!$checkId){
					udb::insert('alias_text', $siteDataSeo);
				}else{
					udb::update('alias_text', $siteDataSeo, "`id`=$checkId");
				}
			}

		}
*/

		// if switching "inactive" -> "active"
        if (!$active && $siteData['active'])
            SearchCache::update_rooms($roomID);

        if ($siteData['externalRoomID']){
            $external = udb::single_row("SELECT `externalEngine`, `externalID` FROM `sites` WHERE `siteID` = " . $siteData['siteID']);
            if ($external['externalEngine'] && $external['externalID']){
                $details = SearchManager::get_room_list($siteData['siteID'], true);
                $tmp = $details[$siteData['externalRoomID']];

                if ($tmp && is_array($tmp))
                    udb::update('rooms', [
                        'maxGuests'  => $tmp['maxTotal'],
                        'maxAdults'  => $tmp['maxAdults'],
                        'maxKids'    => $tmp['maxKids'],
                        'maxInfants' => $tmp['maxInfants']
                    ], '`roomID` = ' . $roomID);
            }
        }

        if ($_POST['wu']){
            $wuKeys = typemap($_POST['wu'], ['string' => 'string']);

            $wuClient = new BizWubook;
            $wuKeys ? $wuClient->save_room_keys($roomID, $wuKeys) : $wuClient->delete_keys('room', $roomID);
        }
    }
    catch (LocalException $e){
        // show error
    } ?>
<script>window.parent.closeTab(true); //window.parent.location.reload();</script>
<?php
    exit;
}



$roomTypes = udb::full_list("SELECT * FROM `roomTypes` WHERE 1");

$roomData = $roomDomains = $roomLangs = [];

$domainID = DomainList::active();
$langID   = LangList::active();

$areas = udb::key_value("SELECT `areaID`, `TITLE` FROM `areas` WHERE 1 ORDER BY `TITLE`");

$external = [];

$domains = udb::key_row("SELECT `domainID`, `domainName` ,`domainURL` FROM `domains` WHERE  domainMenu=1", "domainID");

//$categories = udb::key_row("SELECT * FROM `attributes_categories` WHERE active=1 ORDER BY showOrder" , 'categoryID');
$categories = udb::key_row("SELECT * FROM `attributes_categories` WHERE active=1 and domainID=6 ORDER BY showOrder" , 'categoryID');
$attributes = udb::key_list("SELECT distinct attrID,attributes.defaultName,attributes_domains.categoryID,attributes.attrType FROM `attributes_domains` left join attributes using (attrID) WHERE attributes_domains.active=1 and attributes_domains.domainID=6 ORDER BY attributes_domains.showOrder" , 'categoryID');
$categories[0] = array('categoryName'=>'ללא קטגוריה' , 'categoryID'=>0);
$attributes[0] = udb::single_list("SELECT a.* FROM `attributes` AS `a` LEFT JOIN `attributes_domains` AS `d` ON (a.attrID = d.attrID AND d.domainID = " . $domainID . ") WHERE d.attrID IS NULL");

$all_cats = udb::full_list("SELECT * FROM `attributes_categories` WHERE active=1 ORDER BY domainID, showOrder");
$all_attributes = udb::full_list("SELECT * FROM `attributes_domains` WHERE active=1 ORDER BY domainID,categoryID, showOrder");
foreach($all_cats as $all_c){
	$all_categories[$all_c['domainID']][] = $all_c;
}

//$attributes = udb::key_list("SELECT * FROM `attributes` WHERE active=1 ORDER BY showOrder" , 'categoryID');

if ($roomID){
    $roomData    = udb::single_row("SELECT * FROM `rooms` WHERE `roomID` = " . $roomID);
    $roomDomains = udb::key_row("SELECT * FROM `rooms_domains` WHERE `roomID` = " . $roomID, 'domainID');
    $roomLangs   = udb::key_row("SELECT * FROM `rooms_langs` WHERE `roomID` = " . $roomID, ['domainID', 'langID']);
    $roomPrices   = udb::single_row("SELECT * FROM `room_pricesTok` WHERE `roomID` = " . $roomID);
	//$roomsAttr = udb::single_column("SELECT attrID FROM `rooms_attributes` WHERE `roomID`=".$roomID);
	$roomsAttrFull = udb::key_row("SELECT * FROM `rooms_attributes` WHERE `roomID`=".$roomID,"attrID");
	$spaces = udb::full_list("SELECT spaceID,spaceName,spaceType,showOrder FROM `spaces` WHERE roomID=".$roomID. " order by showOrder");
	//$prices = udb::key_row('SELECT * FROM `rooms_prices` WHERE `roomID`='.$roomID,"periodType");
	if($spaces){
		$spaceType = udb::key_row("SELECT * FROM spaces_type WHERE 1" , 'id');
	}
	$roomsGalleries = udb::key_list("SELECT rooms_galleries.galleryID, galleries.galleryTitle, galleries.`domainID`,galleries.active FROM `rooms_galleries`
	LEFT JOIN galleries USING (galleryID)
	WHERE rooms_galleries.`roomID`=".$roomID,'domainID');

	$que = "SELECT * FROM `alias_text` WHERE `ref` = $roomID AND `table`='rooms'";
	$seo = udb::key_row($que, ['domainID','langID']);

	$roomTypeSearchDb = udb::single_column("SELECT `roomType` FROM `room_type_search` WHERE `roomID`=".$roomID);

    $external = udb::single_row("SELECT sites.externalEngine, sites.externalID FROM `sites` WHERE `siteID` = " . $roomData['siteID']);
    if ($external['externalEngine'])
        $external['manual'] = udb::single_value("SELECT `manual` FROM `searchManager_engines` WHERE `index` = '" . $external['externalEngine'] . "'");

    $wuClient = new BizWubook;
    $wuKeys = $wuClient->get_room_keys($roomID);
}

?>
<div class="editItems">
	<div class="popGallery">
		<div class="popGalleryCont"></div>
	</div>
	<div class="frameContent">
		<div class="siteMainTitle"><?=$siteName?></div>
<?php /*
		<div class="roomChoosePage" <?=$roomData['roomOrPage']?"style='visibility: hidden;'":""?> >
			<div class="labelTo">חדר/דף</div>
			<select name="page" id="selectFrame">
				<option value="1" <?=($roomData['roomOrPage']==1?"selected":"")?>>חדר</option>
				<option value="2" <?=($roomData['roomOrPage']==2?"selected":"")?>>דף</option>
			</select>
		</div>
*/ ?>
		<div class="inputLblWrap langsdom domainsHide">
			<div class="labelTo">דומיין</div>
			<?//=DomainList::html_select()?>
		</div>
		<div class="inputLblWrap langsdom">
			<div class="labelTo">שפה</div>
			<?=LangList::html_select()?>
		</div>
		<div id="room" class="frameChoose" style="display: block;">
			<form action="" method="post">
			<input type="hidden" name="roomOrPage" value="1">
		<?php
		//foreach(DomainList::get() as $did => $dom){ ?>
			<div class="domain" data-id="<?=$domainID?>">
				<div class="inputLblWrap">
					<div class="switchTtl">מוצג</div>
					<label class="switch">
					  <input type="checkbox" name="active" value="1"  <?=($roomDomains[$domainID]['active'] ? 'checked="checked"' : '')?> <?=(!$roomID ? 'checked="checked"' : '')?> />
					  <span class="slider round"></span>
					</label>
				</div>
<?/*
				<div class="inputLblWrap">
					<div class="switchTtl">יחידה מומלצת</div>
					<label class="switch">
					  <input type="checkbox" name="recommend" value="1" <?=($roomData['recommend']==1)?"checked":""?> <?=($roomDomains[$did]['recommend'] ? 'checked="checked"' : '')?> />
					  <span class="slider round"></span>
					</label>
				</div>
*/?>



		<?php //foreach(LangList::get() as $lid => $lang){
        $lid = 1;
        ?>
			<div class="language" data-id="<?=$lid?>">
				<div class="inputLblWrap">
					<div class="labelTo">שם היחידה</div>
					<input type="text" placeholder="שם היחידה" name="roomTitle" value="<?=$roomLangs[$domainID][$lid]['roomName']?>" />
				</div>
				<div class="inputLblWrap">
					<div class="labelTo">הערות ליחידה</div>
					<input type="text" placeholder="הערות ליחידה" name="roomNote" value="<?=$roomLangs[$domainID][$lid]['roomNote']?>" />
				</div>
			</div>
		<? // } ?>
		</div>
		<?  //} ?>

		<div class="inputLblWrap">
			<div class="labelTo">סוג היחידה</div>
			<select name="roomType">
			<?php foreach($roomTypes as $type) { ?>
				<option value="<?=$type['id']?>" <?=($roomData['roomType']==$type['id']?"selected":"")?> ><?=$type['roomType']?></option>
			<?php } ?>
			</select>
		</div>
		<div class="inputLblWrap">
			<div class="labelTo">כמות מבנים מסוג זה</div>
			<select name="roomCount" id="roomCount">
			<?php for($i=1;$i<30;$i++) { ?>
				<option value="<?=$i?>"  <?=((intval($roomData['roomCount'])==$i)?"selected":"")?>><?=$i?></option>
			<?php } ?>
			</select>
		</div>

		<div class="inputLblWrap">
			<div class="labelTo">גודל היחידה</div>
			<input type="text" placeholder="גודל היחידה" name="roomSize" value="<?=$roomData['roomSize']?>" />
		</div>
		<div class="inputLblWrap">
			<div class="labelTo">גודל שטח כולל</div>
			<input type="text" placeholder="גודל שטח כולל" name="roomSizeTotal" value="<?=$roomData['roomSizeTotal']?>" />
		</div>


        <div class="inputLblWrap">
            <div class="labelTo">זמן נקיון</div>
            <select name="cleanTime">
                <option value="0">- - לפי הגדרת העסק - -</option>
<?php
        foreach($cleanTime as $ci => $ctime)
            echo '<option value="' , $ci , '" ' , ($ci == $roomData['cleanTime'] ? 'selected="selected"' : '') , '>' , $ctime , '</option>';
?>
            </select>
        </div>

<?php /*
		<div class="inputLblWrap">
			<div class="labelTo">מתאים לזוגות/משפחות</div>
			<select name="couplesOrFamily">
				<option value="0" <?=($roomData['couplesOrFamily']==0?"selected":"")?>>---</option>
				<option value="1" <?=($roomData['couplesOrFamily']==1?"selected":"")?>>זוגות בלבד</option>
				<option value="2" <?=($roomData['couplesOrFamily']==2?"selected":"")?>>זוגות ומשפחות</option>
			</select>
		</div>
*/ ?>

<?php
            if ($external['externalEngine'] && $external['externalID'])
			{
                if ($external['manual'])
                    $exField = '<input type="text" value="' . str_replace("'", '&#039;', outDb($roomData['externalRoomID'])) . '" name="exID" class="inpt" />';
                else {
                    try {
                        $exField = array('<option value="">- - - - - - - - - - - - -</option>');
                        foreach(SearchManager::get_room_list($siteID) as $exRid => $exRoom)
                            $exField[] = '<option value="' . $exRid . '" ' . (($exRid == $roomData['externalRoomID']) ? 'selected="selected"' : '') . '>' . $exRoom . '</option>';

                        $exField = '<select name="exID" style="width:186px">' . implode('', $exField) . '</select>';
                    } catch (Exception $e){
                        $exField = '<input type="text" value="' . str_replace("'", '&#039;', outDb($roomData['externalRoomID'])) . '" name="exID" class="inpt" />';
                    }
                }
?>
                <div class="inputLblWrap">
                    <div class="labelTo">מזהה חיצוני: </div>
                    <?=$exField?>
                </div>
<?php
                unset($exField);
            }
?>
        <div class="tableWrap">
            <div class="rowWrap top">
                <div class="tblCell" style="text-align:center;width:100px">אתר</div>
                <div class="tblCell" style="text-align:center;width:500px">מפתח</div>
            </div>
            <div class="rowWrap">
                <div class="tblCell" style="text-align:center;width:100px">Booking</div>
                <div class="tblCell" style="width:500px"><input type="text" name="wu[booking]" value="<?=($wuKeys['booking'] ?? '')?>" style="width:100%" /></div>
            </div>
            <div class="rowWrap">
                <div class="tblCell" style="text-align:center;width:100px">AirBnB</div>
                <div class="tblCell" style="width:500px"><input type="text" name="wu[airbnb]" value="<?=($wuKeys['airbnb'] ?? '')?>" style="width:100%" /></div>
            </div>
        </div>


		<div class="catName">סוג החדר בחירה מרובה(תוצאות חיפוש)</div>
		<div class="checksWrap">
			<?php foreach($roomTypes as $type) { ?>
			<div class="checkLabel checkIb">
				<div class="checkBoxWrap">
					<input class="checkBoxGr" <?=($roomTypeSearchDb?in_array($type['id'],$roomTypeSearchDb):"")?"checked":""?> type="checkbox" name="roomTypesSearch[]"  value="<?=$type['id']?>" id="pay<?=$type['id']?>">
					<label for="pay<?=$type['id']?>"></label>
				</div>
				<label for="pay<?=$type['id']?>"><?=$type['roomType']?></label>
			</div>
			<?php } ?>
		</div>
		<div  style="clear:both;"></div>

            <div  style="clear:both;"></div>


<?php
			//foreach(DomainList::get() as $did => $dom){
				foreach(LangList::get() as $lid => $lang){ ?>
				<div class="domain" data-id="<?=$domainID?>">
					<div class="language" data-id="<?=$lid?>">
						<div class="section txtarea big">
							<div class="label">תאור היחידה</div>
							<textarea name="roomDesc" class="textEditor"><?=$roomLangs[$domainID][$lid]['roomDesc']?></textarea>
						</div>
						<div class="section txtarea big">
							<div class="label">roomFeatures</div>
							<textarea name="roomFeatures" class="textEditor"><?=$roomLangs[$domainID][$lid]['roomFeatures']?></textarea>
						</div>
					</div>
				</div>
			<?php }
//} ?>
				<div class="clear"></div>

            <?if($domainID == 1) { ?>
			<div class="mainSectionWrapper attr">
				<div class="sectionName">מאפיינים</div>
                <select id="locationsTypes" onchange="filterProperties('locationsTypes')"  style="width:auto;margin:10px;padding-left:20px">
                    <option value="-1">הכל</option>
                    <option value="1,7,3,5">צימר</option>
                    <!--<option value="8">ספא</option>-->
                    <option value="2,3,7,6">חדרים לפי שעה</option>
                    <option value="4,6,5,7">אירועים</option>
                </select>


				<?//print_r($all_categories);

				############################################################################

				if(1==1){
				?>
				<select id="locationsTypes" onchange="changeAttrDomain($(this).val())" style="width:auto;margin:10px;padding-left:20px">
					<option value="6" selected="">Vii</option>
					<option value="10">חדרים לפי שעה</option>
				</select>

				<style>
					.domain_cat{display:none}
					.domain_cat.show{display:block}
				</style>
				<div>
					<?
					$this_catID = "";
					foreach($domains as $d_ID => $domain){
						if(!$all_categories[$d_ID])
							continue;
						?>
					<div id="domain_cat<?=$d_ID?>" class='domain_cat <?=$d_ID==6? "show" : ""?>'>
					<?
						$no_cat['categoryID']='0';
						$no_cat['categoryName']='ללא קטגוריה';

						$all_categories[$d_ID][]= $no_cat;
						foreach($all_categories[$d_ID] as $domain_cat){

							?>
						<div class="catName"><?=$domain_cat['categoryName']?>()</div>
						<div class="checksWrap" id='wrap_cat<?=$domain_cat['categoryID']?>'>
							<?php
							if($d_ID == 6 && $attributes[$domain_cat['categoryID']]){

								foreach($attributes[$domain_cat['categoryID']] as $attribute) {
								$ra = $roomsAttrFull[$attribute['attrID']] ?? [];
								if($displayedAttr[$attribute['attrID']]) continue;
								$displayedAttr[$attribute['attrID']] = $attribute['attrID'];?>
								<div class="checkLabel checkIb attr_box" data-attrtype="<?=$attribute['attrType']?>" id='attrID<?=$attribute['attrID']?>'>
									<div class="checkBoxWrap">
										<input class="checkBoxGr" type="checkbox" name="attributes[]" <?=($ra ? "checked":"")?> value="<?=$attribute['attrID']?>" id="ch<?=$attribute['attrID']?><?=$category['categoryID']?>">
										<label for="ch<?=$attribute['attrID']?><?=$category['categoryID']?>"></label>
									</div>
									<label for="ch<?=$attribute['attrID']?>"><?=$attribute['defaultName']?></label>
									<div class="inputLblWrap">
										<div class="label">תיאור קצר: </div>
										<input type="text" name="descToAttr[<?=$attribute['attrID']?>]" value="<?=outDb($siteAttrFull[$attribute['attrID']]['descToAttr'])?>" title="">
									</div>
								</div>
							<?php
								}
							}?>
							</div>

						<?php
							}?>
							</div>


					<?php
					}
					?>
				</div>
				<?php

				################################################################

				}else{


                $displayedAttr = [];


                foreach($categories as $category) {
				        if (!$attributes[$category['categoryID']])
				            continue;
				    ?>
					<div class="catName"><?=$category['categoryName']?></div>
					<div class="checksWrap">
<?php
            foreach($attributes[$category['categoryID']] as $attribute) {
                $ra = $roomsAttrFull[$attribute['attrID']] ?? [];
                if($displayedAttr[$attribute['attrID']]) continue;
                $displayedAttr[$attribute['attrID']] = $attribute['attrID'];
?>
						<div class="checkLabel checkIb" data-attrtype="<?=$attribute['attrType']?>">
							<div class="checkBoxWrap">
								<input class="checkBoxGr" type="checkbox" name="attributes[]" <?=($ra ? "checked":"")?> value="<?=$attribute['attrID']?>" id="ch<?=$attribute['attrID']?>">
								<label for="ch<?=$attribute['attrID']?>"></label>
							</div>
                            <?=($ra ? '<label class="switch small" style="float:left"><input type="checkbox" name="vis[' . $attribute['attrID'] . ']" value="' . $attribute['attrID'] . '" ' . ($ra['shown'] ? 'checked="checked"' : '') . '><span class="slider round"></span></label>' : '')?>
							<label for="ch<?=$attribute['attrID']?>"><?=$attribute['defaultName']?></label>
							<?php //foreach(LangList::get() as $lid => $lang){ ?>
							<div class="inputLblWrap" !class="language" !data-id="<?=$lid?>">
								<input type="text" name="descToAttr[<?=$attribute['attrID']?>]" placeholder="תאור קצר למאפיין" value="<?=$ra['attrDesc']?>" style="margin-top:8px">
							</div>
							<?//}?>
						</div>
						<?php } ?>
					</div>
				<?php } ?>
				<?}?>
			</div>
            <?}?>
				<?php if($roomID) { ?>
				<div class="mainSectionWrapper">
					<div class="popSpace">
						<div class="popSpaceCont"></div>
					</div>
					<div class="sectionName" id="roominunit">הגדרת חדרים ליחידה</div>
					<div class="addNew space" onclick="addSpacePop(<?=$_GET['roomID']?>,<?=$_GET['siteID']?>,0)">הוסף חדר</div>
					<div class="tablWrapper">
						<div class="baseTbl">
							<div class="tblRow top" id="spacesTblHead">
								<div class="tblCell ttl">שם החדר</div>
								<div class="tblCell ttl">סוג החדר</div>
								<div class="tblCell ttl">&nbsp;&nbsp;</div>
							</div>
							<?php
							$order = 0;
							foreach($spaces as &$space) {
							$order++;
							if($space['showOrder'] == 0) {
								$space['showOrder'] = $order;
								udb::query("update spaces set showorder=".$order." where spaceID=".$space['spaceID']);
							}
							else {
								$order = $space['showOrder'];
							}

							?>
							<div class="tblRow spaceRow" style="cursor:pointer" id="space<?=$space['spaceID']?>" data-order="<?=$order?>" data-spaceid="<?=$space['spaceID']?>">
								<div class="tblCell" onclick="addSpacePop(<?=$_GET['roomID']?>,<?=$_GET['siteID']?>,<?=$space['spaceID']?>)"><?=$space['spaceName']?></div>
								<div class="tblCell" onclick="addSpacePop(<?=$_GET['roomID']?>,<?=$_GET['siteID']?>,<?=$space['spaceID']?>)">
								<?=$spaceType[$space['spaceType']]['spaceName']?></div>
								<div class="tblCell">
									<div class="tblCell" onclick="if(confirm('האם אתה בטוח רוצה למחוק את החדר?')){location.href='/cms/moduls/minisites/rooms/popRoom.php?roomID=<?=$roomID?>&siteID=<?=$siteID?>&spaceID=<?=$space['spaceID']?>&srdel=1'}"><i class="fa fa-trash-o" aria-hidden="true"></i> מחיקה</div>
									<div class="tblCell" onclick="if(confirm('האם את/ה בטוח/ה שברצונכם לשכפל את החדר')){location.href='/cms/moduls/minisites/rooms/dupSpace.php?roomID=<?=$roomID?>&siteID=<?=$siteID?>&spaceID=<?=$space['spaceID']?>&srdel=1'}"><i class="fa fa-copy"></i> שכפול</div>
									<div class="tblCell" onclick="moveUp('space<?=$space['spaceID']?>')">&#8657;</div>
									<div class="tblCell" onclick="moveDown('space<?=$space['spaceID']?>')">&#8659;</div>
								</div>

							</div>
							<?php } ?>
						</div>
					</div>

					<div class="checkLabel">
						<label for="showSpaceAccessories">הצג אבזורים לחדרים</label>
						<div class="checkBoxWrap">
							<input type="checkbox" name="showSpaceAccessories" id="showSpaceAccessories" value="1" <?=(($roomData['showSpaceAccessories']) ? 'checked="checked"' : '')?> title="" />
							<label for="showSpaceAccessories"></label>
						</div>
					</div>

				</div>

				<div class="mainSectionWrapper">
					<div class="sectionName">גלריה</div>
					<div class="manageItems">
						<div class="addButton" style="margin-top: 20px;">
							<?php //foreach(DomainList::get() as $domid => $dom){ ?>
								<div class="domain" data-id="<?=$domainID?>">
									<div class="tableWrap" id="gallery<?=$domainID?>">
										<div class="rowWrap top">
											<!-- <div class="tblCell">#</div> -->
											<div class="tblCell">ID</div>
											<div class="tblCell">שם הגלריה</div>
											<div class="tblCell"></div>
											<div class="tblCell">#</div>
                                            <div class="tblCell">#</div>
										</div>

										<?php
										if($roomsGalleries[$domainID]){
										foreach($roomsGalleries[$domainID] as $gallery) {
											$showGal = false;
											if($gallery['galleryID']==$roomData['gallerySummer'] || $gallery['galleryID']==$roomData['galleryWinter']){
												$showGal = true;
											}

											?>
										<div class="rowWrap" id="galRow<?=$gallery['galleryID']?>">
											<!-- <div class="tblCell">**</div> -->
											<div class="tblCell"><?=$gallery['galleryID']?></div>
											<div class="tblCell"><?=$gallery['galleryTitle']?></div>
											<div class="tblCell"><span onclick="galleryOpen(<?=$domainID.",".$siteID.",".$roomID.",".$gallery['galleryID']?>)"  class="editGalBtn">ערוך גלריה</span>
												<div class="dupGalWrap">
													<select name="galWrapSelect" id="galWrapSelect">
														<option value="-1">כל הדומיינים</option>
													<?php foreach(DomainList::get() as $domain) {
                                                        if($domain['domainID'] == 1 || $domain['domainID'] == $domainID) continue;
													    ?>
														<option value="<?=$domain['domainID']?>"><?=$domain['domainName']?></option>
													<?php } ?>
													</select>
													<span class="editGalBtn" onclick="dupGal(<?=$gallery['galleryID']?>,<?=$domainID?>,<?=$roomID?>)">שכפל גלריה</span>

												</div>
											</div>
											<div class="tblCell">
											<?php if(!$showGal) { ?>
												<div class="delBtn" onclick="deleteGallery(<?=$gallery['galleryID']?>)">
													<i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק
												</div>
											<?php } ?>
											</div>
                                            <div class="tblCell">
                                                <?php if(!$showGal) { ?>

                                                    <label class="switch">
                                                        <input type="checkbox" name="galactive<?=$gallery['galleryID']?>" data-galid="<?=$gallery['galleryID']?>" class="galleryactive" value="1" <?=($gallery['active'] & 1 ? 'checked="checked"' : '')?> />
                                                        <span class="slider round"></span>
                                                    </label>

                                                <?php } ?>
                                            </div>
										</div>
										<?php } } ?>
									</div>
									<div class="addNewBtnWrap">
										<input type="button" class="addNew" id="addNewAcc<?=$domainID?>" value="הוסף חדש" onclick="galleryOpen(<?=$domainID.",".$siteID.",".$roomID?>,'new')" >
									</div>

									<?php /* if(!$roomData['galleryID']){ ?>
										<input type="button" class="addNew" id="addNewAcc<?=$domid?>" value="הוסף חדש" onclick="galleryOpen(<?=$domid.",".$siteID.",".$roomID?>,'new')" >
									<?php } else { ?>
										<input type="button" class="addNew" id="addNewAcc<?=$domid?>" value="הצג תמונות" onclick="galleryOpen(<?=$domid.",".$siteID.",".$roomID.",".$roomData['galleryID']?>)" >
									<?php }  */?>


								</div>
							<?php //} ?>
						</div>
						<?php
							if($galleries){ ?>
						<table id="gallery">
							<thead>
							<tr>
								<th width="30">#</th>
								<th>שם גלריה</th>
								<th>מוצג באתר</th>
								<th width="60">&nbsp;</th>
							</tr>
							</thead>
							<tbody  id="sortRow">

							<?php foreach($galleries as $row) { ?>
								<tr  id="<?=$row['GalleryID']?>">
									<td align="center"><?=$row['GalleryID']?></td>
									<td onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><?=outDb($row['GalleryTitle'])?></td>
									<td align="center"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
									<td align="center" class="actb">
									<div onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>|</div><div onClick="if(confirm('אתה בטוח??')){location.href='?sID=<?=$siteID?>&frame=<?=$frameID?>&gdel=<?=$row['GalleryID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
								</tr>
							<? } ?>
							</tbody>
						</table>
						<? } ?>
					</div>
				</div>


				<div class="mainSectionWrapper">
					<div class="sectionName">הגדרת תפוסה ליחידה</div>

					<div class="inputLblWrap">
						<div class="switchTtl">הצגת ילדים ומבוגרים</div>
						<label class="switch">
						  <input type="checkbox" name="showKidsAndAdults" value="1" <?=($roomData['showKidsAndAdults']==1)?"checked":""?>>
						  <span class="slider round"></span>
						</label>
					</div>
					<div class="tablWrapper doInline">
						<div class="tablTttl">הגדרת כמות אורחים לחדר</div>
						<div class="baseTbl prices">
							<div class="tblRow">
								<div class="tblCell"></div>
								<div class="tblCell ttl">כמות</div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">מקסימום</div>
									<div class="bot">אורחים</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="maxGuests" value="<?=($roomData['maxGuests'] ?? 30)?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">מקסימום</div>
									<div class="bot">מבוגרים</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="maxAdults" value="<?=($roomData['maxAdults'] ?? 20)?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">מקסימום</div>
									<div class="bot">ילדים</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="maxKids" value="<?=($roomData['maxKids'] ?? 0)?>"></div>
							</div>
                            <div class="tblRow">
                                <div class="tblCell">
                                    <div class="top">מקסימום</div>
                                    <div class="bot">תינוקות</div>
                                </div>
                                <div class="tblCell"><input type="number" min="0" name="maxInfants" value="<?=($roomData['maxInfants'] ?? 0)?>"></div>
                            </div>
						</div>
					</div>

					<div class="tablWrapper doInline">
						<div class="tablTttl">הגדרת הרכב למחיר בסיס</div>
						<div class="baseTbl prices">
							<div class="tblRow">
								<div class="tblCell"></div>
								<div class="tblCell ttl">כמות</div>

							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">מקסימום</div>
									<div class="bot">אורחים</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="basisGuests" value="<?=$roomData['basisGuests']?>"></div>
							</div>
						</div>
					</div>
                    <?/* ?>
					<div class="tablWrapper doInline">
						<div class="tablTttl">הגדרת מחיר בסיס אמצ"ש סופ"ש תקופה רגילה</div>
						<div class="baseTbl prices">
							<div class="tblRow">
								<div class="tblCell"></div>
								<div class="tblCell ttl">אמצ"ש</div>
								<div class="tblCell ttl">סופ"ש</div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">לילה אחד</div>
									<div class="bot">מחיר לילה</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="weekday[1][1]" value="<?=$prices[1]['weekday1']?>"></div>
								<div class="tblCell"><input type="number" min="0" name="weekend[1][1]" value="<?=$prices[1]['weekend1']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">2 לילות</div>
									<div class="bot">מחיר לילה</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="weekday[2][1]" value="<?=$prices[1]['weekday2']?>"></div>
								<div class="tblCell"><input type="number" min="0" name="weekend[2][1]" value="<?=$prices[1]['weekend2']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">3 לילות +</div>
									<div class="bot">מחיר לילה</div>
								</div>
								<div class="tblCell" ><input type="number" min="0" name="weekday[3][1]" value="<?=$prices[1]['weekday3']?>"></div>
								<div class="tblCell" ><input type="number" min="0" name="weekend[3][1]" value="<?=$prices[1]['weekend3']?>"></div>
							</div>
						</div>
					</div>
					<div class="tablWrapper doInline">
						<div class="tablTttl">הגדרת מחיר בסיס אמצ"ש סופ"ש תקופה חמה</div>
						<div class="baseTbl prices">
							<div class="tblRow">
								<div class="tblCell"></div>
								<div class="tblCell ttl">אמצ"ש</div>
								<div class="tblCell ttl">סופ"ש</div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">לילה אחד</div>
									<div class="bot">מחיר לילה</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="weekday[1][2]" value="<?=$prices[2]['weekday1']?>"></div>
								<div class="tblCell"><input type="number" min="0" name="weekend[1][2]" value="<?=$prices[2]['weekend1']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">2 לילות</div>
									<div class="bot">מחיר לילה</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="weekday[2][2]" value="<?=$prices[2]['weekday2']?>"></div>
								<div class="tblCell"><input type="number" min="0" name="weekend[2][2]" value="<?=$prices[2]['weekend2']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">3 לילות +</div>
									<div class="bot">מחיר לילה</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="weekday[3][2]" value="<?=$prices[2]['weekday3']?>"></div>
								<div class="tblCell"><input type="number" min="0" name="weekend[3][2]" value="<?=$prices[2]['weekend3']?>"></div>
							</div>
						</div>
					</div>
					<div class="tablWrapper doInline">
						<div class="tablTttl">הגדרת מחיר תוספת ילד/מבוגר תקופה רגילה</div>
						<div class="baseTbl prices">
							<div class="tblRow">
								<div class="tblCell"></div>
								<div class="tblCell ttl">אמצ"ש</div>
								<div class="tblCell ttl">סופ"ש</div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">תוספת מבוגר</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="extraPriceAdultWeekday[1]" value="<?=$prices[1]['extraPriceAdultWeekday']?>"></div>
								<div class="tblCell"><input type="number" min="0" name="extraPriceAdultWeekend[1]" value="<?=$prices[1]['extraPriceAdultWeekend']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">תוספת ילד</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="extraPriceKidWeekday[1]" value="<?=$prices[1]['extraPriceKidWeekday']?>"></div>
								<div class="tblCell"><input type="number" min="0" name="extraPriceKidWeekend[1]" value="<?=$prices[1]['extraPriceKidWeekend']?>"></div>
							</div>
						</div>
					</div>
					<div class="tablWrapper doInline">
						<div class="tablTttl">הגדרת מחיר תוספת ילד/מבוגר תקופה חמה</div>
						<div class="baseTbl prices">
							<div class="tblRow">
								<div class="tblCell"></div>
								<div class="tblCell ttl">אמצ"ש</div>
								<div class="tblCell ttl">סופ"ש</div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">תוספת מבוגר</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="extraPriceAdultWeekday[2]" value="<?=$prices[2]['extraPriceAdultWeekday']?>"></div>
								<div class="tblCell"><input type="number" min="0" name="extraPriceAdultWeekend[2]" value="<?=$prices[2]['extraPriceAdultWeekend']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">תוספת ילד</div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="extraPriceKidWeekday[2]" value="<?=$prices[2]['extraPriceKidWeekday']?>"></div>
								<div class="tblCell"><input type="number" min="0" name="extraPriceKidWeekend[2]" value="<?=$prices[2]['extraPriceKidWeekend']?>"></div>
							</div>
						</div>
					</div>
					<? */ ?>
				</div>
<?php /*
				<div class="mainSectionWrapper">
					<div class="sectionName">מחירון חדר</div>
					<div class="tablWrapper doInline">
						<div class="tablTttl">מחירון</div>
						<div class="baseTbl prices">
							<div class="tblRow">
								<div class="tblCell"></div>
								<div class="tblCell ttl">מחיר</div>

							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">שעה</div>
									<div class="bot"></div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="hour" value="<?=$roomPrices['hour']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">שעתיים</div>
									<div class="bot"></div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="twoHours" value="<?=$roomPrices['twoHours']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">3 שעות</div>
									<div class="bot"></div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="threeHours" value="<?=$roomPrices['threeHours']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">לילה</div>
									<div class="bot"></div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="night" value="<?=$roomPrices['night']?>"></div>
							</div>
							<div class="tblRow">
								<div class="tblCell">
									<div class="top">סופ"ש</div>
									<div class="bot"></div>
								</div>
								<div class="tblCell"><input type="number" min="0" name="weekend" value="<?=$roomPrices['weekend']?>"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="mainSectionWrapper">
					<div class="sectionName">SEO</div>
					<?php foreach(DomainList::get() as $did => $dom){
							foreach(LangList::get() as $lid => $lang){ ?>
								<div class="domain" data-id="<?=$did?>">
									<div class="language" data-id="<?=$lid?>">
										<div class="inputLblWrap">
											<div class="labelTo">כותרת עמוד</div>
											<input type="text" placeholder="כותרת עמוד" name="title" value="<?=outDb($seo[$did][$lid]['title'])?>" />
										</div>
										<div class="inputLblWrap">
											<div class="labelTo">H1</div>
											<input type="text" placeholder="H1" name="h1" value="<?=outDb($seo[$did][$lid]['h1'])?>" />
										</div>
										<!-- <div class="inputLblWrap">
											<div class="labelTo">קישור</div>
											<input type="text" placeholder="קישור" name="link" value="" />
										</div> -->
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
									</div>
								</div>
					<?php } } ?>
				</div>
*/?>

                <div class="mainSectionWrapper">
                    <div class="sectionName">יחידות</div>
                    <div class="manageItems">
                        <div class="tableWrap" id="unitsTab" style="width:88%">
                            <div class="rowWrap top">
                                <div class="tblCell" style="text-align:center">#</div>
                                <div class="tblCell" style="width:2%; padding-right:5px">שם היחידה</div>
                                <div class="tblCell" style="text-align:center">הזמנות</div>
                                <div class="tblCell" style="text-align:center">&nbsp;</div>
                            </div>
<?php
            $que = "SELECT u.*, COUNT(o.orderID) AS `orders` 
                    FROM `rooms_units` AS `u` 
                        LEFT JOIN `orderUnits` AS `o` USING(`unitID`) 
                        LEFT JOIN `orders` ON (orders.orderID = o.orderID AND orders.allDay = 0) 
                    WHERE u.roomID = " . $roomID . " 
                    GROUP BY u.unitID";
            $units = udb::key_row($que, 'unitID');
            foreach($units as $uid => $unit) {
?>
                            <div class="rowWrap" id="galRow<?=$uid?>">
                                <div class="tblCell" style="text-align:center"><?=$uid?></div>
                                <div class="tblCell caned" style="width:2%; padding-right:5px" data-uid="<?=$uid?>"><?=$unit['unitName']?></div>
                                <div class="tblCell" style="text-align:center"><?=($unit['orders'] ? $unit['orders'] . ' הזמנות' : '-')?></div>
                                <div class="tblCell" style="text-align:center">
                                    <?php if(!$unit['orders'] && count($units) > 1) { ?>
                                        <div class="delBtn" onclick="deleteUnit(<?=$uid?>)"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק </div>
                                    <?php } ?>
                                </div>
                            </div>
<?php
            }
?>
                        </div>
                    </div>
                </div>

				<?php } ?>
				<input type="submit" value="שמור" class="submit">
			</form>
		</div>
		<div id="page" class="frameChoose">
			<form action="" method="post">
				<input type="hidden" name="roomOrPage" value="2">
				<?php
				//foreach(DomainList::get() as $did => $dom){ ?>
					<div class="domain" data-id="<?=$did?>">
						<div class="inputLblWrap">
							<div class="switchTtl">מוצג</div>
							<label class="switch">
							  <input type="checkbox" name="active" value="1" <?=($roomData['active']==1 && $domainID==0)?"checked":""?> <?=($roomDomains[$domainID]['active'] ? 'checked="checked"' : '')?> />
							  <span class="slider round"></span>
							</label>
						</div>
					</div>
				<?php //} ?>
				<?php
					foreach(LangList::get() as $lid => $lang){ ?>
						<div class="language" data-id="<?=$lid?>">
							<div class="inputLblWrap">
								<div class="labelTo">כותרת עמוד</div>
								<input type="text" placeholder="כותרת עמוד" value="<?=$roomLangs[$domainID][$lid]['roomName']?>" name="roomTitle" />
							</div>
						</div>
				<?php  } ?>
				<?php
					//foreach(DomainList::get() as $did => $dom){
						foreach(LangList::get() as $lid => $lang){ ?>
						<div class="domain" data-id="<?=$domainID?>">
							<div class="language" data-id="<?=$lid?>">
								<div class="section txtarea big">
									<div class="label">תיאור קצר</div>
									<textarea name="roomDesc" class="textEditor"><?=$roomLangs[$domainID][$lid]['roomDesc']?></textarea>
								</div>
							</div>
						</div>
				<?php }
					//} ?>

				<div class="clear"></div>
				<div class="mainSectionWrapper">
					<div class="sectionName">גלריה</div>
					<div class="manageItems">
						<div class="addButton" style="margin-top: 20px;">
							<?php //foreach(DomainList::get() as $domid => $dom){ ?>
								<div class="domain" data-id="<?=$domainID?>">
									<?php if(!$siteData['galleryID']){ ?>
										<input type="button" class="addNew" id="addNewAcc<?=$domainID?>" value="הוסף חדש" onclick="galleryOpen(<?=$domainID.",".$siteID.",".$roomID?>,'new')" >
									<?php } else { ?>
										<input type="button" class="addNew" id="addNewAcc<?=$domainID?>" value="הצג תמונות" onclick="galleryOpen(<?=$domainID.",".$siteID.",".$roomID.",".$siteData['galleryID']?>)" >
									<?php } ?>
								</div>
							<?php// } ?>
						</div>
						<?php
							if($galleries){ ?>
						<table>
							<thead>
							<tr>
								<th width="30">#</th>
								<th>שם גלריה</th>
								<th>מוצג באתר</th>
								<th width="60">&nbsp;</th>
							</tr>
							</thead>
							<tbody  id="sortRow">

							<?php foreach($galleries as $row) { ?>
								<tr  id="<?=$row['GalleryID']?>">
									<td align="center"><?=$row['GalleryID']?></td>
									<td onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><?=outDb($row['GalleryTitle'])?></td>
									<td align="center"><?=($row['ifShow']?"<span style='color:green;'>כן</span>":"<span style='color:red;'>לא</span>")?></td>
									<td align="center" class="actb">
									<div onclick="window.location.href='/cms/sites/gallery.php?frame=<?=$frameID?>&sID=<?=$siteID?>&gID=<?=$row['GalleryID']?>'"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;ערוך</div><div>|</div><div onClick="if(confirm('אתה בטוח??')){location.href='?sID=<?=$siteID?>&frame=<?=$frameID?>&gdel=<?=$row['GalleryID']?>';}" class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i>&nbsp;מחק</div></td>
								</tr>
							<? } ?>
							</tbody>
						</table>
						<? } ?>
					</div>
				</div>
				<input type="submit" value="שמור" class="submit">
			</form>
		</div>
	</div>
</div>


<script src="../../../app/tinymce/tinymce.min.js"></script>
<script>
    function filterProperties(sid) {
        var selected = $("#" + sid).val(),listSelect = "";
        if(selected == -1) {
            $(".checkLabel.checkIb").show();
        }
        else {

            $(".checkLabel.checkIb").hide();
            listSelect = selected.split(",");
            for(var s=0;s<listSelect.length;s++) {
                $(".checkLabel.checkIb[data-attrtype="+listSelect[s]+"]").show();
            }

        }

    }
	function sortSpaces(){
		var allSpaces = $(".spaceRow");
		allSpaces.sort(function(a, b){
				return $(a).attr("data-order")-$(b).attr("data-order");
			});
		$(".spaceRow").remove();
		allSpaces.insertAfter($("#spacesTblHead"));
		setTimeout(function(){
			var postdata = {};
			allSpaces.each(function(){
				var id = $(this).attr("data-spaceid");
				var showOrder = $(this).attr("data-order");
				postdata[id] = showOrder;
			});
			setTimeout(function(){
				$.post("saveSpaceOrder.php",{order: postdata},function(){

				});
			},100);

		},350);
	}

	function moveUp(id){
		//debugger;
		var ord = $("#" + id).attr("data-order");
		ord = ord - 1;
		if(ord < 1)
			ord = 1;
		var prevorder = $("#" + id).prev(".spaceRow").attr("data-order");
		prevorder = prevorder + 1;

		$("#" + id).attr("data-order",ord);
		$("#" + id).prev(".spaceRow").attr("data-order",prevorder);
		sortSpaces();
	}
	function moveDown(id){
		//debugger;
		var ord = $("#" + id).attr("data-order");
		ord = ord + 1;
		if($("#" + id).next(".spaceRow")) {
		var nextorder = $("#" + id).next(".spaceRow").attr("data-order");
		}
		else {
		var nextorder = ord + 1;
		}
		nextorder = nextorder - 1;
		$("#" + id).attr("data-order",ord);
		$("#" + id).next(".spaceRow").attr("data-order",nextorder)
		sortSpaces();
	}

    function smallToggle(id){
      //  return $('<label class="switch small" style="float:left"><input type="checkbox" name="vis[' + id + ']" value="' + id + '" checked="checked"><span class="slider round"></span></label>');
    }

	function dupGal(galleryID,curDomain,roomID){
		if(confirm("האם אתה בטוח שברצונך לשכפל את הגלריה?")){
			var domain = $('#galWrapSelect').val();
			console.log({galID:galleryID,toDomain:domain,curDomain:curDomain,roomID:roomID});
			$.post("../dupGal.php",{galID:galleryID,toDomain:domain,curDomain:curDomain,roomID:roomID}).done(function(){
				alert("הגלריה שוכפלה בהצלחה");
				window.parent.location.reload(); window.parent.closeTab();
			});
		}
	}

    function deleteGallery(galID){

        if(confirm("האם אתה בטוח שברצונך למחוק את הגלריה?")){
            $.post("../ajax_del_gallery.php",{id:galID},function(){
                $("#galRow"+galID).remove();
            });
        }
    }

	function deleteUnit(uid){

		if(confirm("האם את/ה בטוח/ה שברצונך למחוק את היחידה?")){
			$.post("ajax_del_unit.php",{id:uid},function(res){
			    if (!res.success)
			        return alert(res.error || res._txt || 'Error !');

				$("#galRow"+uid).remove();
                if (res.count){
                    $('#roomCount').val(res.count);
                    if (res.count <= 1)
                        $('#unitsTab').find('.delBtn').remove();
                }
			});
		}
	}


	function galleryOpen(domainID,siteID,roomID,galleryID){
		$(".popGalleryCont").html('<iframe width="100%" height="100%" id="frame_'+domainID+'_'+roomID+'_'+galleryID+'" frameborder=0 src="/cms/moduls/minisites/galleryGlobal.php?domainID='+domainID+'&siteID='+siteID+'&roomID='+roomID+'&gID='+galleryID+'"></iframe><div class="tabCloserSpace" onclick="tabCloserGlobGal(\'frame_'+siteID+'_'+roomID+'\')">x</div>');
		$(".popGallery").show();
		var elme = window.parent.document.getElementById("frame_"+siteID+"_"+roomID);
		$('#mainContainer').css("overflow","hidden");
		elme.style.zIndex="16";
		elme.style.position="relative";
	}


	function tabCloserGlobGal(id){
		$(".popGalleryCont").html('');
		$(".popGallery").hide();
		var elme = window.parent.document.getElementById(id);
		$('#mainContainer').css("overflow","visible");
		elme.style.zIndex="12";
		elme.style.position ="static";
	}


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

	function resizeIframe(obj) {
		obj.style.height = (obj.contentWindow.document.documentElement.scrollHeight + 120) + 'px';
	  }
	function addSpacePop(roomID,siteID,spaceID){

		$(".popSpaceCont").html('<iframe width="100%" height="850px" id="frame_'+siteID+'_'+roomID+'_'+spaceID+'" frameborder=0 src="/cms/moduls/minisites/rooms/space_pop.php?roomID='+roomID+'&siteID='+siteID+'&spaceID='+spaceID+'" !onload="resizeIframe(this)"></iframe><div class="tabCloserSpace" onclick="tabCloserSpace(\'frame_'+siteID+'_'+roomID+'\')">x</div>');
		$(".popSpace").show();
		$("html , body").scrollTop(10);
		var elme = window.parent.document.getElementById("frame_"+siteID+"_"+roomID);
		elme.style.zIndex="14";
		elme.style.position="relative";
	}

	function tabCloserSpace(id){
		$(".popSpaceCont").html('');
		$(".popSpace").hide();
		var elme = window.parent.document.getElementById(id);
		elme.style.zIndex="12";
		elme.style.position ="static";
	}


	$(function(){


        $(".galleryactive").on("change",function () {
            var id = $(this).data("galid");
            $.ajax({
                method: 'POST',
                url: '/cms/moduls/minisites/ajax_update_active.php',
                data: {id: id},
                success: function(response){
                    console.log("update");
                }
            });
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

            $('#selectFrame').on('change', function() {

              switch(this.value){
                case "0":$('.frameChoose').hide() ;break;
                case "1":$('.frameChoose').hide();$('#room').show();break;
                case "2":$('.frameChoose').hide();$('#page').show();break;

              }
            }).trigger('change');

        });
        <?if($_GET['openunits']) {
            echo '$("#roominunit").click();';
            echo '$([document.documentElement, document.body]).animate({
            scrollTop: ( $("#roominunit").offset().top  + 270)
        }, 2000);';
        }
        else {
            echo '$("html , body").scrollTop(0);';
        }
        ?>

        $('#unitsTab').on('click', '.caned', function(){
            var cell = $(this), prev = cell.html(), uid = cell.data('uid');

            cell.removeClass('caned').html('<input type="text" style="width:80%; height:26px" value="" /><button style="width:16%; margin:4px; border:0" class="save addNew">שמור</button>');
            cell.find('input').val(prev);
            cell.find('.save').on('click', function(){
                var txt = $(this).siblings('input').val();

                if (!txt)
                    return alert('Empty name !') || false;

                $.post('ajax_edit_unit.php', {uid:uid, name:txt}).then(function(res){
                    if (!res.success)
                        return alert(res.error || res._txt || 'Error !');

                    cell.addClass('caned').html(res.name);
                });

                return false;
            });
        });

        $('.mainSectionWrapper.attr').on('click', 'input[name="attributes[]"]', function(){
            if (this.checked)
                //smallToggle(this.value).insertAfter(this.parentNode);
            else
                //$(this.parentNode.parentNode).find('label.switch.small').remove();
        });
    });

    $('.mainSectionWrapper.attr input[name="attributes[]"]:checked').each( function(){
       // smallToggle(this.value).insertAfter(this.parentNode);
    });

function changeAttrDomain(thedomainID){
	//debugger;
	$('.attr_box').each(function(){
		$(this).detach().appendTo('#domain_cat'+thedomainID+' #wrap_cat0')
	});
	$('.domain_cat').removeClass('show');
	$('#domain_cat'+thedomainID).addClass('show');
	var dom_categories =  dom_cat[thedomainID].split(',');
	$.each(dom_categories,function(key,catID){
		var categoryID = catID;
		var cat_attributes = cat_attr[categoryID].split(',');
		$.each(cat_attributes,function(key2,attrID){
			$('#attrID'+attrID).detach().appendTo('#wrap_cat'+categoryID);
		});
	});
}

</script>

<?php
foreach($all_attributes as $all_attr){
	if($all_attr["categoryID"]!=$last_cat){
		$domain_categories[$all_attr["domainID"]][]= $all_attr["categoryID"];
	}
	$domain_cat_attr[$all_attr["categoryID"]][]= $all_attr["attrID"];

	$last_cat = $all_attr["categoryID"];
}



?>
<script>
let cat_attr = {};
let dom_cat = {};
<?foreach($domain_cat_attr as $key=> $d_c_a){?>
	 cat_attr[<?=$key?>] = '<?=implode(",",$d_c_a);?>';

<?}?>
<?foreach($domain_categories as $key=> $d_c){?>
	 dom_cat[<?=$key?>] = '<?=implode(",",$d_c);?>';

<?}?>
</script>