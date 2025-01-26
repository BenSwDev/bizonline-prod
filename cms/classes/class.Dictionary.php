<?php
class Dictionary{
    const DEFAULT_CACHE = false;

    static public $lang = 1;
    static public $specialSpace = "";


    static private $globalDictionary = [];
    static private $useCache = self::DEFAULT_CACHE;
    static private $filePath = __DIR__ . '/lang_cache/';

    public static function setLanguage($langID = 1){
        self::$lang = intval($langID);

        self::$useCache = self::DEFAULT_CACHE;
        self::$globalDictionary = [];
		if($langID != 1){
			self::$specialSpace = " ";
		}else{
			self::$specialSpace = "";
		}
    }

    static public function translate($word){
        $word = trim(filter_var($word, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW));

        if (self::$useCache){
            if (file_exists(self::$filePath . 'lang' . self::$lang . '.dict')){
                self::$globalDictionary = include self::$filePath . 'lang' . self::$lang . '.dict';
            }

            self::$useCache = false;
        }

        if(isset(self::$globalDictionary[$word]))
            return self::$globalDictionary[$word];

        $que = "SELECT d.word, IFNULL(w.translation, d.word) AS 'wordNew' FROM dictionary AS `d` LEFT JOIN dictionary_words AS `w` ON (d.id = w.id AND w.langID = " . self::$lang . ") WHERE d.word = '" . udb::escape_string($word) . "'";
        $trans = udb::single_row($que);

        if ($trans && is_array($trans))
            return self::$globalDictionary[$trans['word']] = $trans['wordNew'];

        udb::query("INSERT INTO `dictionary`(`word`) VALUES('" . udb::escape_string($word) . "') ON DUPLICATE KEY UPDATE `word` = `word`");
        return $word;
    }

    static public function updateCache($langID = 1){
        $list = udb::key_value("SELECT d.word, IFNULL(t.translation, d.word) AS 'translation' FROM `dictionary` AS `d` LEFT JOIN `dictionary_words` AS `t` ON (d.id = t.id AND t.langID = " . intval($langID) . ") WHERE 1", 0, 1);

        $file = [];
        foreach($list as $key => $trans)
            $file[] = "'" . addcslashes($key, "'\\") . "'=>'" . addcslashes($trans, "'\\") . "'";

        $tmpfile = 'lang' . intval($langID) . '.' . time();
        $newfile = 'lang' . intval($langID) . '.dict';
        $oldfile = null;

        file_put_contents(self::$filePath . $tmpfile, '<?php return array(' . implode(',', $file) . ');' . PHP_EOL, LOCK_EX);

        if (file_exists(self::$filePath . $newfile))
            if (!copy(self::$filePath . $newfile, $oldfile = self::$filePath . str_replace('.dict', '.bak.' . time(), $newfile)))
                throw new Exception("Cannot backup current file");

        if (!rename(self::$filePath . $tmpfile, self::$filePath . $newfile)){       // if failed to update lang file
            @unlink(self::$filePath . $newfile);                          // clear lang file remains

            if ($oldfile){                                      // if we have backup
                if (!rename($oldfile, self::$filePath . $newfile))        // try to restore backup
                    @unlink(self::$filePath . $newfile);                  // if failed - clear lang file remains
            }

            throw new Exception("Failed to update lang file");
        }
        elseif ($oldfile)        // if successfully updated file, and there's backup
            unlink($oldfile);    // remove backup
    }
}
