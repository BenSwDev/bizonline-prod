<?php

function dupGallery($galID , $roomID , $toDomain , $curDomain ,$galleryType){

    $domainList = udb::full_list("select * from domains where domainID < 100");

    $copyTo = [];
    if($toDomain == -1){
        foreach($domainList as $dom){
            if($dom['domainID'] != $curDomain)
                $copyTo[] = $dom['domainID'];
        }
    }else{
        $copyTo[] = $toDomain;

    }

    if($galID){
        $que = "SELECT * FROM `galleries` WHERE galleryID=".$galID;
        $galleries = udb::single_row($que);

        $que = "SELECT * FROM `pictures` WHERE galleryID=".$galID;
        $pictures = udb::full_list($que);

        foreach($copyTo as $domID){
            $newGal = [
                "siteID" => $galleries['siteID'],
                "pageID" => $galleries['pageID'],
                "orgGalID" => $galID,
                "domainID" => $domID,
                "galleryTitle" => $galleries['galleryTitle']
            ];

            $newgalID = udb::insert("galleries",$newGal);

            foreach($pictures as $picture){
                udb::insert("pictures",['galleryID' => $newgalID, 'fileID' => $picture['fileID'], 'showOrder' => $picture['showOrder'], 'summer' => $picture['summer'], 'winter' => $picture['winter']]);
            }

            if($roomID){
                udb::insert('rooms_galleries', ['roomID'  => $roomID,'galleryID' =>  $newgalID , 'orgGalID' => $galID]);
            }else{
                if($galleryType) {
                    udb::insert($galleryType, ['siteID'  => $galleries['siteID'],'galleryID' =>  $newgalID, 'domainID'=>$domID, 'orgGalID' => $galID],true);
                }
                else {
                    udb::insert('sites_galleries', ['siteID'  => $galleries['siteID'],'galleryID' =>  $newgalID , 'orgGalID' => $galID , 'showOrder'=>$newgalID]);
                }

            }
        }



    }
}

function globalLangSwitch($langCode){

		switch($langCode)
		{
			case 1: $datal = "he"; break;
			case 2: $datal = "eng"; break;
			case 3: $datal = "fr"; break;
		}
		return $datal;
}

function minisite_domainTabs($select = 0,$addLink = "") {

	global $siteName;
    static $domains = null;
    $selectDomain = $select;
    if(!$select) {
        $selectDomain = DomainList::active();
    }
    if (!$domains)
        $domains = udb::key_value("SELECT `domainID`, `domainName` FROM `domains` WHERE  domainMenu=1", 0, 1);
   //if($addLink != "2" && $addLink != "") return $domains;

?>
	<div class="miniTabs">
<?php
    foreach($domains as $did => $dom){
?>
        <div onclick="window.location.href='/cms/moduls/minisites/frame.dor<?=$addLink?>.php?siteID=<?=$_GET['siteID']?>&tab=1&domid=<?=$did?>&siteName=<?=urlencode($siteName)?>'" class="tab <?=(($did == $selectDomain) ? 'active' : '')?>" data-show="domain" data-id="<?=$did?>"><p><?=$dom?></p></div>


<?php
    }
?>
	</div>
<?php
    return $domains;
}

function domainTabs($select = 0) {
    static $domains = null;

    if (!$domains)
        $domains = udb::key_value("SELECT `domainID`, `domainName` FROM `domains` WHERE `active` = 1", 0, 1);
?>
	<div class="miniTabs">
<?php
    foreach($domains as $did => $dom){
?>
        <div class="tab <?=(($did == $select) ? 'active' : '')?>" data-show="domain" data-id="<?=$did?>"><p><?=$dom?></p></div>


<?php
    }
?>
	</div>
<?php
    return $domains;
}


function languagTabs($select = 1){
    static $langs = null;

    if (!$langs)
        $langs = udb::key_value("SELECT `langID`, `LangName` FROM `language` WHERE 1", 0, 1);
?>
    <div class="miniTabs">
<?php
    foreach($langs as $lid => $lom) {
?>
        <div class="tab language <?=(($lid == $select) ? 'active' : '')?>" data-show="language" data-id="<?=$lid?>"><p><?=$lom?></p></div>
<?php
    }
?>
    </div>
<?php
    return $langs;
}


function reloadParent($end = '</section></body></html>'){
    echo '<script type="text/javascript">$(".tabCloser").click(); window.parent.location.reload();</script>' . $end;
    exit;
}


function js_safe($str, $replace = ''){
    $base = ['"' => '&quot;', "'" => '&#039;'];
    return strtr($str, $replace ? (is_array($replace) ? $replace : [$replace => $base[$replace]]) : $base);
}


function typemap($param, $map){
    static $scalar = array('int' => 'intval', 'string' => 1, 'bool' => 'boolval', 'float' => 'floatval', 'decimal' => 1, 'html' => 1, 'text' => 1, 'email' => 1, 'date' => 1, 'numeric' => 1);

    if ((is_string($map) || is_callable($map)) && ($param === null || is_scalar($param))){
        switch($map){
            case 'int'    : return intval($param);
            case 'float'  : return floatval($param);
            case 'bool'   : return !!$param;
            case 'decimal': return round(floatval($param), 2);
            case 'string' : return trim(filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW));
            case 'email'  : return trim(filter_var(filter_var(trim($param), FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL) ?: '');
            case 'html'   : return trim(filter_var($param, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW));
            case 'text'   : return trim(str_replace('{:~:}', "\n", filter_var(str_replace("\n", '{:~:}', $param), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW)));
            case 'date'   : return preg_match('/^\d{4}-\d{2}-\d{2}$/i', trim($param)) ? trim($param) : null;
            case 'numeric': return preg_replace('/\D+/', '', $param);
            default       : return is_callable($map) ? $map($param) : null;
        }
    }
    elseif (is_array($map) && is_array($param)){
        reset($map);
        $key = key($map); $val = current($map);
        $result = array();

        if (count($map) == 1 && ($key === 0 || isset($scalar[$key]) || is_callable($key))){
            if (isset($scalar[$key]))
                $key_foo = is_string($scalar[$key]) ? $scalar[$key] : function($p) use ($key) {return typemap($p, $key);};
            else
                $key_foo = ($key === 0) ? null : $key;

            if (is_array($val))
                $val_foo = function($p) use ($val) {return typemap($p, $val);};
            elseif (isset($scalar[$val]))
                $val_foo = is_string($scalar[$val]) ? $scalar[$val] : function($p) use ($val) {return typemap($p, $val);};
            else
                $val_foo = $val;

            foreach($param as $k => $v)
                $key_foo ? $result[$key_foo($k)] = $val_foo($v) : $result[] = $val_foo($v);
        }
        else
            foreach($map as $k => $v){
                if ($k[0] == '!' || isset($param[$k])){
                    $rk = ($k[0] == '!') ? substr($k, 1) : $k;
                    $result[typemap($rk, 'string')] = typemap(isset($param[$rk]) ? $param[$rk] : null, $v);
                }
            }

        return $result;
    }
    elseif ($map == 'date' && is_array($param)){
        if (count($param) == 3 && checkdate($param[1], $param[2], $param[0]))
            return date('Y-m-d', mktime(9, 0, 0, $param[1], $param[2], $param[0]));
        return null;
    }
    return null;
}


class SearchFiller {
	private function insertSearch(array $jsonData, array $titles){
		ksort($jsonData);

		$siteDataSe= [
			'active'       => 1,
			'domainID'    => 1,
			'title'    =>  $titles[BASE_LANG_ID],
			'data'    => json_encode($jsonData, JSON_NUMERIC_CHECK)

		];
		$searchID = udb::insert('search', $siteDataSe);

		foreach(LangList::get() as $langID => $lang){
			udb::insert('search_langs', [
				'id'    => $searchID,
				'langID'    => $langID,
				'title'   => $titles[$langID]
			], true);
		}
		foreach(LangList::get() as $langID => $lang){
			$siteDataSeo =
			[
				'langID'    => $langID,
				'domainID'    => 1,
				'title'  => $titles[$langID],
				'h1'  => $titles[$langID],
				'description'  => $titles[$langID],
				'keywords'  => "",
				'ref'  => $searchID,
				'table'  => "search",
				'LEVEL1' => globalLangSwitch($langID),
				'LEVEL2' => ($titles[$langID]? $titles[$langID].".html":"")
			];

			udb::insert('alias_text', $siteDataSeo,true);

		}


	}

	private function getTypeData($type){
		switch($type){
			case 'type':
				return udb::key_row("SELECT id, roomTypeMany as TITLE, LangID FROM `roomTypesLangs` WHERE 1", ["id", 'LangID']);
			case 'city':
				return udb::key_row("SELECT settlementID, TITLE, LangID FROM `settlements_text` WHERE 1",["settlementID","LangID"]);

			case 'area':
				return udb::key_row("SELECT areaID, TITLE, LangID FROM `areas_text` WHERE 1",["areaID","LangID"]);

			case 'main_area':
				return udb::key_row("SELECT main_areaID, TITLE ,LangID FROM `main_areas_text` WHERE 1",["main_areaID","LangID"]);

			default: return [];
		}
	}

	private function createFillers($newType, $newID, array $newNames, array $addTypes){
		$jsonData = [$newType => $newID];
		$titles   = $newNames;


		$this->insertSearch($jsonData, $titles);

		foreach($addTypes as $type){
			$typeData = $this->getTypeData($type);

			$temp = $jsonData;
			foreach($typeData as $id => $names){
				$temp[$type] = $id;

				$titles = [];
				foreach(LangList::get() as $langID => $lang){
					$titles[$langID] = $this->createText($type, $names[$langID]['TITLE'], $newNames[$langID],$langID);
				}
				$this->insertSearch($temp, $titles);
			}
		}
	}

	private function createText($type, $typeName, $title, $langID){
		Dictionary::setLanguage($langID);
		if($type == "type"){
			return $typeName." ".Dictionary::translate('at_location').Dictionary::$specialSpace.$title;

		}
		else{
			return $title." ".Dictionary::translate('at_location').Dictionary::$specialSpace.$typeName;
		}

	}

	public function newAppartmentType($id, array $names){
		//$this->createFillers('type', $id, $names, ['city', 'area', 'main_area']);
	}

	public function newCity($id, array $names){
		//$this->createFillers('city', $id, $names, ['type']);
	}

	public function newArea($id, array $names){
		//$this->createFillers('area', $id, $names, ['type']);
	}

	public function newMainArea($id, array $names){
		//$this->createFillers('main_area', $id, $names, ['type']);
	}
}


function GUID(){
	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}