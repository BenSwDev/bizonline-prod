<?php
class DocURL extends ShortURL\ShortURL
{
    protected static $table = 'doc_download_links';

    public static $expireTime = 60 * 60 * 24 * 7;        // default exire time - 7 days

    public static function create($data, $ts = ''){
        if (!is_array($data))
            throw new ShortURL\Exception("Illegal data format: array expected, " . gettype($data) . " received");
        if (!$data)
            throw new ShortURL\Exception("Empty data");

        return parent::create($data, date('Y-m-d 23:59:59', time() + self::$expireTime));
    }
}
