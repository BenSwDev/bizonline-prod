<?php
class ServerTest {
    protected static $file_domain = 'https://bizonline.co.il/';
    protected static $file_path   = __DIR__ . '/../../';
    protected static $file_name   = 'server_test_file_for_cron_scripts.txt';

    protected static $alert_to    = 'alchemist.tech@gmail.com';
    protected static $alert_from  = 'info@bizonline.co.il';

    public static function isActive()
    {
        $local = file_get_contents(static::$file_path . static::$file_name);
        if (!$local || substr($local, 0, 4) != 'SID-'){
            static::alert('Missing or illegal local file ' . static::$file_path . static::$file_name . ":\n\n" . $local);
            return false;
        }

        $curl = curl_init(static::$file_domain . static::$file_name);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $remote = curl_exec($curl);
        $stats  = curl_getinfo($curl);

        curl_close($curl);

        if (!$remote || substr($remote, 0, 4) != 'SID-' || $stats['http_code'] != 200){
            static::alert('Missing or illegal remote file ' . static::$file_domain . static::$file_name . ":\n\n" . $remote . "\r\rConnection info: " . print_r($stats, true));
            return false;
        }

        return !strcmp($local, $remote);
    }

    public static function alert($text)
    {
        mail(static::$alert_to, 'Critical error in server detection script', $text . "\r\rServer Info: " . print_r($_SERVER, true), "From: " . static::$alert_from);
    }
}
