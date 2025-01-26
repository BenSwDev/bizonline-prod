<?php
class TempActivePage {
    const DEFAULT_LANG_ID = 1;
    const DEFAULT_LANG_CODE = 'he';
    const DEFAULT_DOMAIN_ID = 10;
    const DEFAULT_DOMAIN  = 'roomsvip.com';
    const USE_IMAGES_DOMAIN = "https://roomsvip.com";
    const ATTRIBUTE_TYPE = 7;
    const ADDTOURL = "1000";
    const SEARCH_LEVEL2 = 'search';
    const META_TITLE_EXT = 'חדרים VIP';
    const BASE_TITLES_START = 'חדרים לפי שעה';

    static public $langID = self::DEFAULT_LANG_ID;
    static public $langCode = self::DEFAULT_LANG_CODE;
    static public $domainID = self::DEFAULT_DOMAIN_ID;
    static public $domainName = self::DEFAULT_DOMAIN;
    static public $imagesDomain = self::USE_IMAGES_DOMAIN;
    static public $attrType = self::ATTRIBUTE_TYPE;
    static public $searchLevel2 = self::SEARCH_LEVEL2;
    static public $addToSearchUrl = self::ADDTOURL;
    static public $metaTitleExt = self::META_TITLE_EXT;
    static public $baseTitlesStart = self::BASE_TITLES_START;
    static public $season = 'summer';

    static public $domainBase = '';

    static public $search_page_params = ['area', 'city', 'marea', 'roomTypes', 'attr', 'maxPrice'];
    static public $extra_page_params  = ['from', 'till', 'pax'];

    static public $_routes = [
        'homepage' => 'pages/home.php',
        '404'    => 'inc_404.php',
        'sites'    => 'pages/minisite.php',
        'search'    => 'pages/search-results.php',
        'closeToMe' => 'pages/search-results.php',
        'articles' => 'pages/article.php',
        'MainPages' => 'pages/content.php',
        'userVerify' => 'inc_userVerify.php',
        'resetPassword' => 'inc_resetPassword.php',
        'galtest' => 'pages/search-results-test.php'
    ];

    static public $langs  = [];
    static public $week   = [];

    static public $search = [];
    static public $isDateSearch = false;

    static public $cache = [];          // place for inter-vidget data transfer

    static public $page = [];

    static public $customerID = 0;

    static function map($var, $type){
        return typemap($var, $type);
    }


    static public function changeDomoain($did){
        self::$domainID = $did;
        $domainData = udb::single_row("SELECT `domainID`,searchLevel2,searchNumberExt,attrType,metaTitleExt,baseTitlesStart FROM `domains` WHERE `domainID` = " . $did );
        self::$langID = 1;
        self::$domainID = $domainData['domainID'];
        self::$baseTitlesStart = $domainData['baseTitlesStart'];
        self::$metaTitleExt = $domainData['metaTitleExt'];
        self::$attrType = $domainData['attrType'];// //udb::single_value("SELECT `attrType` FROM `domains` WHERE `domainID` = " . (self::$domainID ?: '0'));
        self::$addToSearchUrl = $domainData['searchNumberExt'];
        self::$searchLevel2 = $domainData['searchLevel2'];
        self::$domainBase = '/' . (self::$langID == self::DEFAULT_LANG_ID ? '' : self::$langCode . '/');

    }
    /**
     * Sets internal LangID and DomainID according to given params or $_SERVER data
     *
     * @param string $lang
     * @param string $host
     * @param string $method
     * @throws TempAPError
     */

    static public function init($lang = '', $host = '', $method = 'GET'){
        if (!self::$langs)
            self::$langs = udb::key_value("SELECT `LangCode`, `LangID` FROM `language` WHERE 1");
        /*elseif (!self::$lang && !self::$host)
            return;     // means domain and lang already set*/

        // checking lang, updating if nessesary
        if ($lang && self::$langs[$lang]){
            self::$langID   = self::$langs[$lang];
            self::$langCode = $lang;
        }
        elseif (preg_match('~^(' . implode('|', array_keys(self::$langs)) . ')/~', ltrim($lang ?: $_SERVER['REQUEST_URI'], '/'), $match)) {
            self::$langID   = self::$langs[$match[1]];
            self::$langCode = $match[1];
        }
        else {
            self::$langID   = self::DEFAULT_LANG_ID;
            self::$langCode = self::DEFAULT_LANG_CODE;
        }

        // checking domain, updating if nessesary
        $host = preg_replace('/^www\./i', '', ($host ?: $_SERVER['HTTP_HOST']));

        if ($host && strcmp(self::DEFAULT_DOMAIN, $host)){
            $domainData = udb::single_row("SELECT `domainID`,searchLevel2,searchNumberExt,attrType,metaTitleExt,baseTitlesStart FROM `domains` WHERE `domainURL` = '" . $host . "' AND `active` = 1");
            self::$domainID = $domainData['domainID'];
            self::$domainName = $host;
            if (!self::$domainID)
                throw new TempAPError('domain error: Cannot find domain ' . $host);
        }
        else {
            self::$domainID   = self::DEFAULT_DOMAIN_ID;
            self::$domainName = self::DEFAULT_DOMAIN;
            $domainData = udb::single_row("SELECT `domainID`,searchLevel2,searchNumberExt,attrType,metaTitleExt,baseTitlesStart FROM `domains` WHERE `domainID` = '" . self::$domainID . "' AND `active` = 1");
        }

        switch(self::$langID){
            case 1:  self::$week = ["א'", "ב'", "ג'", "ד'", "ה'", "ו'", "ש'"]; break;
            case 2:  self::$week = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; break;
            default: self::$week = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; break;
        }
        self::$baseTitlesStart = $domainData['baseTitlesStart'];
        self::$metaTitleExt = $domainData['metaTitleExt'];
        self::$attrType = $domainData['attrType'];// //udb::single_value("SELECT `attrType` FROM `domains` WHERE `domainID` = " . (self::$domainID ?: '0'));
        self::$addToSearchUrl = $domainData['searchNumberExt'];
        self::$searchLevel2 = $domainData['searchLevel2'];

        self::$domainBase = '/' . (self::$langID == self::DEFAULT_LANG_ID ? '' : self::$langCode . '/');

        self::$season = (date('n') < 4 || date('n') > 10) ? 'winter' : 'summer';

        self::initSearch($method);

        Dictionary::setLanguage(self::$langID);
    }

    static public function initSearch($from = 'get'){
        $src = strcasecmp($from, 'post') ? $_GET : $_POST;

        // setting attribute param from source
        if (isset($src['tids']) && is_array($src['tids']))
            self::$search['attr'] = array_map('intval', explode('-', $src['tids']));
        if (isset($src['attr']))
            self::$search['attr'] = array_merge(self::$search['attr'] ?? [], array_map('intval', is_array($src['attr']) ? $src['attr'] : explode('-', $src['attr'])));

        // room types
        if (isset($src['roomTypes']) && $src['roomTypes']) {
            if(is_array($src['roomTypes'])){ //for some reason sometimes gets array somtimes get string - update by gal
                self::$search['roomTypes'] = array_map('intval', $src['roomTypes']);
            }
            else {
                self::$search['roomTypes'] = array_map('intval', explode('-', $src['roomTypes']));
            }
        }
        //promoted
        if ($tmp = $src['promoted'] ?? 0)
            self::$search['promoted'] = $tmp;

        // instant booking
        if ($tmp = intval($src['instant'] ?? 0))
            self::$search['instant'] = 1;

        // no cancel fee
        if ($tmp = intval($src['freecnl'] ?? 0))
            self::$search['freecnl'] = 1;

        // max price
        if ($tmp = intval($src['maxPrice'] ?? 0))
            self::$search['maxPrice'] = $tmp;

        // min price
        if ($tmp = intval($src['minPrice'] ?? 0))
            self::$search['minPrice'] = $tmp;

        // location
        if ($tmp = intval($src['sett'] ?? 0))
            self::$search['city'] = $tmp;
        elseif ($tmp = intval($src['city'] ?? 0))
            self::$search['city'] = $tmp;
        elseif ($tmp = intval($src['area'] ?? 0))
            self::$search['area'] = $tmp;
        elseif ($tmp = intval($src['marea'] ?? 0))
            self::$search['marea'] = $tmp;

        // coordinates
        foreach(['lat', 'lon'] as $key)
            if ($tmp = floatval($src[$key] ?? 0))
                self::$search[$key] = round($tmp, 7);

        // setting pax from source
        if (isset($src['pax']) && is_array($src['pax'])){
            self::$search['pax'] = array_map(function($p){
                return is_array($p) ? $p : array_combine(['adults', 'kids'], explode('-', $p));
            }, $src['pax']);

            $_SESSION['search_pax'] = self::$search['pax'];
        }
        elseif (!empty($_SESSION['search_pax']))
            self::$search['pax'] = $_SESSION['search_pax'];

        // setting dates
        if ($src['from'] && $src['till']){
            TempActivePage::checkDateSearch($src['from'], $src['till']);

            if (TempActivePage::$isDateSearch)
                $_SESSION['search_dates'] = [TempActivePage::$search['from'], TempActivePage::$search['till']];
        }
        elseif (is_array($_SESSION['search_dates']))
            TempActivePage::checkDateSearch($_SESSION['search_dates'][0], $_SESSION['search_dates'][1]);

        $chunks = explode(':', date('Y-m-d:w'));
        self::$search['default'] = [
            'from'    => $chunks[0],
            'dayFrom' => $chunks[1],
            'till'    => date('Y-m-d', strtotime('+1 day')),
            'dayTill' => ($chunks[1] + 1) % 7
        ];
    }

    static public function setRoute($key, $file){
        self::$_routes[$key] = $file;
    }

    static public function variant($from_table, $to_table, $fields){
        $list   = is_array($fields) ? $fields : [$fields];
        $result = [];

        foreach($list as $key => $value){
            $from = $from_table . "." . (is_numeric($key) ? $value : $key);
            $to   = $to_table . "." . $value;

            $result[] = "IF(LENGTH(" . $from . ") > 0, " . $from . ", " . $to . ") as `" . $value . "`";
        }

        return implode(', ', $result);
    }

    static public function checkDateSearch($f, $t){

        $from = self::map($f, 'date');
        $till = self::map($t, 'date');
        if ($from && $till && $from < $till){
            self::$search['from']    = $from;
            self::$search['dayFrom'] = date('w', strtotime($from));
            self::$search['till']    = $till;
            self::$search['dayTill'] = date('w', strtotime($till));
            self::$search['nights']  = round((strtotime($till) - strtotime($from)) / (3600 * 24));

            self::$isDateSearch = true;
        }
    }
    static public function updateSearchCount($id){
        udb::query("update search set count=count+1 where id=".$id);
    }
    static public function updateSearch($data, $merge = true){
        if (is_array($data))
            foreach($data as $key => $val){
                if (is_array($val))
                    self::$search[$key] = $merge ? array_merge(self::$search[$key] ?? [], $val) : $val;
                elseif (isset(self::$search[$key]) || $val)
                    self::$search[$key] = $val;
            }

    }

    static public function createAlias($table, $id, $path){
        $que = ['LEVEL1' => self::$langCode, 'table' => $table, 'ref' => $id, 'domainID' => self::$domainID, 'langID' => self::$langID];
        $path = array_slice($path, 0, 4);

        foreach($path as $i => $value)
            $que['LEVEL' . ($i + 2)] = $value;

        return udb::insert('alias_text', $que);
    }

    /**
     * Returns URL for requested resource based on resource table and ID
     *
     * @param $table
     * @param $id
     * @param int $domain
     * @param int $lang
     * @return string URL for requested resource
     */
    static public function showAlias($table, $id, $lang = 0, $domain = 0){
        $langID = (intval($lang) ?: self::$langID);

        $que = "SELECT * FROM `alias_text` WHERE `ref` = " . intval($id) . " AND `table` = '" . udb::escape_string($table) . "' AND (`domainID` = " . (intval($domain) ?: self::$domainID) . " OR `domainID` = 0) AND `langID` = " . $langID . " ORDER BY `domainID` DESC LIMIT 1";
        $row = udb::single_row($que);

        return $row ? self::compileAlias($row) : '';
        /*$link = [];
        for($i = 2; $i <= 5; ++$i){
            if ($row['LEVEL' . $i])
                $link[] = urlencode($row['LEVEL' . $i]);
            else
                break;
        }

        return (($langID == self::DEFAULT_LANG_ID) ? '/' : '/' . $row['LEVEL1'] . '/') . implode('/', $link);*/
    }

    static public function getAliasTexts($table,$id , $lang = 0 , $domain = 0){
        $langID = (intval($lang) ?: self::$langID);

        $que = "SELECT * FROM `alias_text` WHERE `ref` = " . intval($id) . " AND `table` = '" . udb::escape_string($table) . "' AND (`domainID` = " . (intval($domain) ?: self::$domainID) . " OR `domainID` = 0) AND `langID` = " . $langID . " ORDER BY `domainID` DESC LIMIT 1";
        $row = udb::single_row($que);

        return $row;
    }

    static public function compileAlias($row){
        $link = [];
        for($i = 2; $i <= 5; ++$i){
            if ($row['LEVEL' . $i]){
                if($i == 4 && intval($row['LEVEL4'])) {
                    $row['LEVEL4'] = self::$addToSearchUrl . $row['LEVEL4'];
                }
                $link[] = urlencode(str_replace(" ","_",$row['LEVEL' . $i]));
            }
            else
                break;
        }

        return (($row['langID'] == self::DEFAULT_LANG_ID) ? '/' : '/' . $row['LEVEL1'] . '/') . implode('/', $link);
    }

    static public function _resetPage(){
        //self::$langID = self::DEFAULT_LANG_ID;
        //self::$domainID = self::DEFAULT_DOMAIN_ID;
        self::$search = [];
        self::$page = [
            'file'   => 'index.php',
            'table'  => 'homepage',
            'ref'    => 0,
            'header' => []
        ];

    }

    static public function _go404(){

        //$alias = self::getAlias(self::$langCode, '404'); echo self::$langCode;

        //self::$page = array_merge(self::$page, $alias);
        //self::$langID = $alias['langID'];
        header("Location: /404");

//        self::$page['header']['status'] = 404;
//        self::$page['title'] = 'הדף לא נמצא Rooms VIP';
//        self::$page['h1'] = 'הדף לא נמצא Rooms VIP';
//        self::$page['file'] = self::$_routes[404];

    }
    static public function set404(){

        //$alias = self::getAlias(self::$langCode, '404'); echo self::$langCode;

        //self::$page = array_merge(self::$page, $alias);
        //self::$langID = $alias['langID'];

        self::$page['header']['status'] = 404;
        self::$page['title'] = 'הדף לא נמצא ' . self::$metaTitleExt;
        self::$page['h1'] = 'הדף לא נמצא ' . self::$metaTitleExt;
        self::$page['file'] = self::$_routes[404];

    }

    static public function findSearchAlias(){
        $tempSearch = [];
        $searchTitleBase = "";
        $names = ['city' => 'settName', 'area' => 'areaName' , 'marea' => 'mareaName' , 'attr'=>'attrName' , 'roomTypes' => 'roomTypesNameMany'];


        foreach(self::$search_page_params as $key) {
            if (self::$search[$key]) {
                if ($tmp = self::$search[$key]){
                    if(method_exists('TempActivePage' , $names[$key])) {
                        $foo = $names[$key];
                        $searchTitleBase .= ' ' . self::$foo($tmp);
                    }
                }
                $tempSearch[$key] = self::$search[$key];
            }

        }


        $tempSearch = json_encode(InSearch::normalizeParams($tempSearch), JSON_NUMERIC_CHECK);

        $sql = "select * from search where  `data` = '" . udb::escape_string($tempSearch) . "' and domainID=".self::$domainID." LIMIT 1";
        $searchRecord = udb::single_row($sql);

        if($searchRecord) {
            $query = [];
            //$query['title'] =  "חדרים לפי שעה  " . $searchTitleBase;
            $query['updateDate'] = date("Y-m-d H:i:s");
            udb::query('update search set count=count+1,updateDate=NOW() where id='.$searchRecord['id']);
            if ($searchRecord['id'] != self::$page['ref']){
                $pdiff = array_intersect_key(self::$search, array_combine(self::$extra_page_params, self::$extra_page_params));
                $plink = self::showAlias("search",$searchRecord['id'],1,TempActivePage::$domainID);
                if ($plink != '' && $searchRecord['active'] == 1) {
                    return $plink . ($pdiff ? '?' . InSearch::stringifyParams($pdiff) : '');
                }
                return null;

            }
        }
        else {
            if(strlen($tempSearch) > 7) {
                $query = [];
                $query['data'] = $tempSearch;
                $query['active'] = 0;
                $query['count'] = 1;
                $query['updateDate'] = date('Y-m-d H:i:s');
                $query['createDate'] =  date('Y-m-d H:i:s');
                $query['domainID'] = self::$domainID;
                $searchTitle = searchPageName(self::$search , true);
                $query['title'] =  $searchTitle;
                $newID = udb::insert("search",$query);
                $query = [];
                $query['id'] = $newID;
                $query['langID'] = 1;
                $query['title'] =  $searchTitle;
                udb::insert("search_langs",$query);
            }
        }
        return null;
    }

    static public function searchPageAlias($data = null, $lang = 0, $domain = 0)
    {
        $input = (is_array($data) && count($data)) ? $data : self::$search;
        $tempSearch = [];
        foreach(self::$search_page_params as $key)
            if ($input[$key])
                $tempSearch[$key] = $input[$key];

        $normSearch = InSearch::normalizeParams($tempSearch);
        $tempSearch = json_encode($normSearch, JSON_NUMERIC_CHECK);

        $sql = "SELECT `id` FROM `search` WHERE `data` = '" . udb::escape_string($tempSearch) . "' AND `domainID` = " . ($domain ?: self::$domainID) . " LIMIT 1";
        $searchRecord = udb::single_value($sql);

        return $searchRecord ? self::showAlias("search", $searchRecord, $lang ?: self::$langID, $domain ?: self::$domainID) : '/'.self::$searchLevel2.'/?' . http_build_query($normSearch);
    }

    static public function route($link, $host = ''){
        // settings defauls values

        self::_resetPage();
        self::init($link, $host);
        $url  = parse_url($link);
        $path = array_map('urldecode', explode('/', trim($url['path'], '/')));

        // if default lang - add lang code to path array
        if (self::$langID == self::DEFAULT_LANG_ID)
            array_unshift($path, self::DEFAULT_LANG_CODE);

        // searching for path in aliases


        $alias = self::getAlias(...$path);
        //print_r($alias);
        if ($alias){
            self::$page = array_merge(self::$page, $alias);
            $file = self::$_routes[$alias['table']] ?? self::$_routes[404];
            if($path[1] ==self::$searchLevel2){
                self::$page['file']  = self::$_routes['search'];
                self::$page['table'] = 'search';
                return true;
            }
            if (is_callable($file))
                self::$page['file'] = $file($alias['ref']) ?: self::$_routes[404];
            else
                self::$page['file'] = $file;

            return true;
        }
        if($path[1] ==self::$searchLevel2){
            self::$page['file']  = self::$_routes['search'];
            self::$page['table'] = 'search';
            return true;
        }
        // pulling alias data for 404 page (per language)

        if($path[1] == '404'){
            self::$page['file']  = self::$_routes[404];
            self::set404();
            return true;
        }
        self::_go404();

        return false;
    }

    static public function getAlias(...$level){
        $cond = ['1'];
        for($i = 1; $i <= 5; ++$i){
            $useLevel = str_replace("_"," ",$level[$i - 1] ?? '');
            if($i==5) $useLevel = str_replace(self::$addToSearchUrl,"",$useLevel ?? '');
            $cond[] = "`LEVEL" . $i . "` = '" . udb::escape_string($useLevel) . "'";
        }
        $sql = "SELECT * FROM `alias_text` WHERE (`domainID` = " . self::$domainID . " and langID=".self::$langID.") AND " . implode(' AND ', $cond) . " ORDER BY `domainID` DESC LIMIT 1";
        //echo $sql;
        $currAlias = udb::single_row($sql);
        //print_r($currAlias);
        return $currAlias ?: [];
    }

    static public function getAliasByRef($table, $ref, $domainID = 0, $langID = 0){
        $cond = ['`ref` = ' . intval($ref), "`table` = '" . udb::escape_string($table) . "'"];

        return udb::single_row("SELECT * FROM `alias_text` WHERE (`domainID` = " . ($domainID ?: self::$domainID) . " OR `domainID` = 0) AND `langID` = " . ($langID ?: self::$langID) . " AND " . implode(' AND ', $cond) . " ORDER BY `domainID` DESC LIMIT 1") ?: [];
    }

    static public function langPrefix($full = false){
        return ($full ? 'https://' . self::$domainName : '') . (self::$langID == self::DEFAULT_LANG_ID) ? '/' : '/' . self::$langCode . '/';
    }

    static public function roomTypeName($type, $plural = false){
        if (!isset(self::$cache['roomTypes'])) {

            self::$cache['roomTypes'] = udb::key_row("SELECT `id`,`roomType`, `roomTypeMany` FROM `roomTypesDomains` WHERE domainID=".self::$domainID." AND `LangID` = " . self::$langID, 'id');
        }

        $endWord = (( self::$domainID == 1 || self::$domainID == 6) ? 'נופש' : Dictionary::translate('לפי שעה'));
        if( self::$domainID == 109) {
            $endWord = " בלופטים";
        }
        return self::$cache['roomTypes'][$type][$plural!==false ? 'roomTypeMany' : 'roomType'] . ' ' . $endWord  ;
    }
    static public function roomTypesName($types , $plural = false){
        if(!isset(self::$cache['roomTypes']))
            self::$cache['roomTypes'] = udb::key_row("SELECT `roomType`, `roomTypeMany`,id FROM `roomTypesDomains` WHERE domainID=".self::$domainID." AND `LangID` = " . self::$langID, 'id');
        $typesArray = is_array($types) ? $types : array($types);
        $retValue = "";
        $arrSize = count($typesArray);
        $c = 0;
        foreach ($typesArray as $rType) {
            $c++;
            $retValue .= (($c > 1) ? ($c == $arrSize ? ' ו' : ', ' ) :'') . self::$cache['roomTypes'][$rType][$plural!==false ? 'roomTypeMany' : 'roomType'];
        }
        return $retValue;
    }

    static public function roomTypesNameMany($types){
        if(!isset(self::$cache['roomTypes']))
            self::$cache['roomTypes'] = udb::key_row("SELECT `roomType`, `roomTypeMany`,id FROM `roomTypesDomains` WHERE domainID=".self::$domainID." AND `LangID` = " . self::$langID, 'id');
        $typesArray = is_array($types) ? $types : array($types);
        $retValue = "";

        foreach ($typesArray as $rType) {
            $retValue .= ', ' . self::$cache['roomTypes'][$rType]['roomTypeMany'];
        }
        return $retValue;
    }

    static public function attribute($id){
        if (!isset(self::$cache['attributes'][$id]))
            self::$cache['attributes'][$id] = udb::single_row("SELECT attributes.defaultName , attributes_domains.fontCode , attributes.connectionValue FROM attributes inner JOIN `attributes_domains` USING(attrID) where attributes_domains.domainID in (0, " . self::$domainID . ")  and attributes_domains.attrID=" . $id . " ORDER BY `domainID` DESC");
        return self::$cache['attributes'][$id];
    }

    static public function attrName($id){
        $returnValue= "";
        if(is_array($id)) {
            foreach ($id as $item) {
                if (!isset(self::$cache['attributes'][$item])){
                    $att = self::attribute($item);
                    $returnValue .= ($att['connectionValue'] ? $att['connectionValue'] : $att['defaultName']);
                }
                else {
                    $att = self::attribute($item);
                    $returnValue .=  ($att['connectionValue'] ? $att['connectionValue'] : $att['defaultName']);
                }
            }
        }
        else {
            if (!isset(self::$cache['attributes'][$id])){
                $att = self::attribute($id);
            }
            $att = self::$cache['attributes'][$id];
            $returnValue = ($att['connectionValue'] ? $att['connectionValue'] : $att['defaultName']);
        }

        return $returnValue;
    }

    static public function settName($sett){
        if (!isset(self::$cache['setts']) || !isset(self::$cache['setts'][$sett]))
            self::$cache['setts'][$sett] = udb::single_value("SELECT IF(l.TITLE > '', l.TITLE, s.TITLE) AS `name` FROM `settlements` AS `s` LEFT JOIN `settlements_text` AS `l` ON (s.settlementID = l.settlementID AND l.langID = " . self::$langID . ") WHERE s.settlementID = " . $sett);
        return self::$cache['setts'][$sett];
    }

    static public function areaName($area){
        if (!isset(self::$cache['areas']) || !isset(self::$cache['areas'][$area]))
            self::$cache['areas'][$area] = udb::single_value("SELECT IF(l.TITLE > '', l.TITLE, s.TITLE) AS `name` FROM `areas` AS `s` LEFT JOIN `areas_text` AS `l` ON (s.areaID = l.areaID AND l.langID = " . self::$langID . ") WHERE s.areaID = " . $area);
        return self::$cache['areas'][$area];
    }
    static public function mareaName($marea){
        if (!isset(self::$cache['areas']) || !isset(self::$cache['areas'][$marea]))
            self::$cache['mareas'][$marea] = udb::single_value("SELECT IF(l.TITLE > '', l.TITLE, s.TITLE) AS `name` FROM `main_areas` AS `s` LEFT JOIN `main_areas_text` AS `l` ON (s.main_areaID = l.main_areaID AND l.langID = " . self::$langID . ") WHERE s.main_areaID = " . $marea);
        return self::$cache['mareas'][$marea];
    }

    static public function check_redirect($url, $domainID = 0){

        $link = ltrim($url ?: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        if (!$link)
            return null;


        $target = udb::single_row("SELECT `ID`, `new_url`, `code` FROM `redirects` WHERE `old_url` = '" . udb::escape_string($link) . "' AND `domainID` = " . self::$domainID . " LIMIT 1");
        if ($target)
            udb::query("UPDATE `redirects` SET `useCount` = `useCount` + 1, `useLast` = NOW() WHERE `ID` = '" . $target['ID'] . "'");


        return $target;
    }
}


class TempAPError extends \Exception {}

function searchPageName($param,$ish1 = 0){
    $pageName = [];
    $addstart = false;
    if (is_array($param['roomTypes']) && count($param['roomTypes']) > 1) {
        $pageName[] = TempActivePage::roomTypesName($param['roomTypes'], true);
        $addstart = true;
    }
    else
        if(is_array($param['roomTypes'])) {
            $pageName[] = TempActivePage::roomTypeName($param['roomTypes'][0], true);
            $addstart = true;
        }
        else {
            $pageName[] = TempActivePage::$baseTitlesStart;
            $addstart = true;
        }

    if ($param['city']) {
        $pageName[] = (($addstart === false) ? TempActivePage::$baseTitlesStart .' ' : '') .  Dictionary::translate('at') . TempActivePage::settName($param['city']);
    }
    elseif ($param['area']) {
        $pageName[] = (($addstart === false) ? TempActivePage::$baseTitlesStart .' ' : '') .  Dictionary::translate('at') . TempActivePage::areaName($param['area']);
    }
    elseif ($param['marea']) {
        $pageName[] = (($addstart === false) ? TempActivePage::$baseTitlesStart .' ' : '') .  Dictionary::translate('at') . TempActivePage::mareaName($param['marea']);
    }
    if ($param['attr'])
        $pageName[] = TempActivePage::attrName($param['attr']);
    if($param['promoted']) {
        $pageName[] = "הכי חמים";
    }
    if($param['maxPrice']) {
        $pageName[] = ' עד ' . "₪" . $param['maxPrice']  ;
    }


    return implode(' ', $pageName);
}