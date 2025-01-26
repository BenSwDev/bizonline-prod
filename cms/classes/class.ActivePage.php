<?php
class ActivePage {
    const DEFAULT_LANG_ID = 1;
    const DEFAULT_LANG_CODE = 'he';
    const DEFAULT_DOMAIN_ID = 1;
    //const DEFAULT_DOMAIN  = 'biz2.c-ssd.com';
    const DEFAULT_DOMAIN  = 'bizonline.co.il';

    static public $langID = self::DEFAULT_LANG_ID;
    static public $langCode = self::DEFAULT_LANG_CODE;
    static public $domainID = self::DEFAULT_DOMAIN_ID;
    static public $domainName = self::DEFAULT_DOMAIN;

    static public $season = 'summer';

    static public $domainBase = '';

    static public $_routes = [
        'homepage' => 'inc_index.php',
        '404'    => 'inc_404.php',
        'sites'    => 'pages/minisite.php',
        'search'    => 'pages/search.php',
		'closeToMe' => 'pages/search.php',
		'articles' => 'pages/article.php'
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

    /**
     * Sets internal LangID and DomainID according to given params or $_SERVER data
     *
     * @param string $lang
     * @param string $host
     * @throws APError
     */
    static public function init($lang = '', $host = ''){
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
            self::$domainID = udb::single_value("SELECT `domainID` FROM `domains` WHERE `domainURL` = '" . $host . "' AND `active` = 1");
            self::$domainName = $host;

            if (!self::$domainID)
                throw new APError('domain error: Cannot find domain ' . $host);
        }
        else {
            self::$domainID   = self::DEFAULT_DOMAIN_ID;
            self::$domainName = self::DEFAULT_DOMAIN;
        }

        switch(self::$langID){
            case 1:  self::$week = ["א'", "ב'", "ג'", "ד'", "ה'", "ו'", "ש'"]; break;
            case 2:  self::$week = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; break;
            default: self::$week = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; break;
        }

        self::$domainBase = '/' . (self::$langID == self::DEFAULT_LANG_ID ? '' : self::$langCode . '/');

        self::$season = (date('n') < 4 || date('n') > 10) ? 'winter' : 'summer';

        self::initSearch();

        Dictionary::setLanguage(self::$langID);
    }

    static public function initSearch($from = 'get'){
        $src = strcasecmp($from, 'post') ? $_GET : $_POST;

        // setting attribute param from source
        if (isset($src['tids']) && is_array($src['tids']))
            self::$search['attr'] = array_map('intval', explode('-', $src['tids']));

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
        if ($src['date1'] /*&& $src['date2']*/){
            ActivePage::checkDateSearch($src['date1'], date("Y-m-d", strtotime($src['date1'] . "+1 day")) );

            if (ActivePage::$isDateSearch)
                $_SESSION['search_dates'] = [ActivePage::$search['from'], ActivePage::$search['till']];
        }
        elseif (is_array($_SESSION['search_dates']))
            ActivePage::checkDateSearch($_SESSION['search_dates'][0], $_SESSION['search_dates'][1]);

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

        $que = "SELECT * FROM `alias_text` WHERE `ref` = " . intval($id) . " AND `table` = '" . udb::escape_string($table) . "' AND (`domainID` = " . (intval($domain) ?: self::$domainID) . " OR `domainID` = 0) AND `langID` = " . $langID . " ORDER BY `domainID` DESC, `id` ASC LIMIT 1";
        $row = udb::single_row($que);

        return self::compileAlias($row);
        /*$link = [];
        for($i = 2; $i <= 5; ++$i){
            if ($row['LEVEL' . $i])
                $link[] = urlencode($row['LEVEL' . $i]);
            else
                break;
        }

        return (($langID == self::DEFAULT_LANG_ID) ? '/' : '/' . $row['LEVEL1'] . '/') . implode('/', $link);*/
    }

    static public function compileAlias($row){
        $link = [];
        for($i = 2; $i <= 5; ++$i){
            if ($row['LEVEL' . $i])
                $link[] = urlencode($row['LEVEL' . $i]);
            else
                break;
        }

        return (($row['langID'] == self::DEFAULT_LANG_ID) ? '/' : '/' . $row['LEVEL1'] . '/') . implode('/', $link);
    }

    static public function _resetPage(){
        //self::$langID = self::DEFAULT_LANG_ID;
        //self::$domainID = self::DEFAULT_DOMAIN_ID;

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

        self::$page['header']['status'] = 404;
        self::$page['file'] = self::$_routes[404];
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
        if ($alias){
            self::$page = array_merge(self::$page, $alias);
            $file = self::$_routes[$alias['table']] ?? self::$_routes[404];

            if (is_callable($file))
                self::$page['file'] = $file($alias['ref']) ?: self::$_routes[404];
            else
                self::$page['file'] = $file;

            return true;
        }

        // pulling alias data for 404 page (per language)
        self::_go404();

        return false;
    }

    static public function getAlias(...$level){
        $cond = ['1'];
        for($i = 1; $i <= 5; ++$i)
            $cond[] = "`LEVEL" . $i . "` = '" . udb::escape_string($level[$i - 1] ?? '') . "'";
        // echo "SELECT * FROM `alias_text` WHERE (`domainID` = " . self::$domainID . " OR `domainID` = 0) AND " . implode(' AND ', $cond) . " ORDER BY `domainID` DESC LIMIT 1";
        return udb::single_row("SELECT * FROM `alias_text` WHERE (`domainID` = " . self::$domainID . " OR `domainID` = 0) AND " . implode(' AND ', $cond) . " ORDER BY `domainID` DESC, `id` ASC LIMIT 1") ?: [];
    }

    static public function langPrefix($full = false){
        return ($full ? 'https://' . self::$domainName : '') . (self::$langID == self::DEFAULT_LANG_ID) ? '/' : '/' . self::$langCode . '/';
    }

    static public function roomTypeName($type, $plural = false){
        if (!isset(self::$cache['roomTypes']))
            self::$cache['roomTypes'] = udb::key_row("SELECT `roomType`, `roomTypeMany` FROM `roomTypesLangs` WHERE `langID` = " . self::$langID, 'id');
        return self::$cache['roomTypes'][$type][$plural ? 1 : 0] ?? Dictionary::translate($plural ? 'יחידות נופש' : 'יחידת נופש אחת');
    }

    static public function attribute($id){
        if (!isset(self::$cache['attributes'][$id]))
            self::$cache['attributes'][$id] = udb::single_row("SELECT a.fontCode, al.defaultName FROM `attributes` AS `a` INNER JOIN `attributes_langs` AS `al` USING(`attrID`) WHERE al.langID = " . self::$langID . " AND al.defaultName <> '' AND al.domainID IN (0, " . self::$domainID . ") AND a.attrID = " . $id . " ORDER BY `domainID` DESC");
        return self::$cache['attributes'][$id];
    }
}


class APError extends \Exception {}
