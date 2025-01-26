<?php
class CardComOut extends CardComBiz
{
    public function __construct($apiApth, $siteID){
        if (strcasecmp(Terminal::hasTerminal($siteID), 'CardCom'))
            throw new Exception('Site ' . $siteID . ' has no CardCom terminal');

        $data = self::$_term_cache[$siteID];

        parent::__construct($siteID, $data['masof_number'], $data['masof_key'], $data['masof_pwd']);

        $this->has_swipe = false;

        $apiApth = rtrim($apiApth, '/');
        $urlBase = 'https://bizonline.co.il/api/' . basename($apiApth) . '/';

        if (is_file($apiApth . '/cardcom_result.php'))
            $this->successURL = $this->failURL = $urlBase . 'cardcom_result.php';

        if ($files = scandir($apiApth)){
            foreach($files as $file)
                if (substr($file, 0, 15) == 'cardcom_notify_' && substr($file, -4) == '.php')
                    $this->notifyURL = $urlBase . $file;
        }

        if (!$this->successURL || !$this->notifyURL || !$this->failURL)
            throw new Exception('Cannot find return URL files');
    }
}
