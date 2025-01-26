<?php
namespace WuBook;

use \PhpXmlRpc as rpc;

rpc\Autoloader::register();

class WuBookManager {
    const API_URL   = 'https://wired.wubook.net/xrws/';
    const API_TOKEN = 'wr_807d6039-6eb6-4387-a0d7-3c56d6b083fb';

    private $client;

    public function __construct(){
        $this->client = new rpc\Client(static::API_URL);
    }

    /**
     * @param string  $foo    method to execute at server
     * @param array   $params list of parameters to send
     * @return mixed          data in server response
     * @throws Exception
     */
    protected function _send(string $foo, array $params = [])
    {
        try {
            $enc = new rpc\Encoder;
            $prm = array_merge([static::API_TOKEN], $params);

            $req = new rpc\Request($foo, $enc->encode($prm));
            $result = $this->client->send($req);

            $data = $enc->decode($result->value());
            if (!isset($data[0]) || intval($data[0]))
                throw new Exception($data[1] ?? 'XMLRPC error: ' . ($data[0] ?? ''));

            return $data[1];
        }
        catch (rpc\Exception $e){
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param  int    $siteID  property ID
     * @return array           array of room assigned to property
     * @throws Exception
     */
    public function fetch_rooms(int $siteID)
    {
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'fetch_rooms': " . $siteID);

        return $this->_send('fetch_rooms', [$sid]);
    }

    /**
     * @param int   $siteID  property ID
     * @param array $avails  multidimentional array in format [roomID => [YYYY-MM-DD => available]]
     * @return $this
     * @throws Exception
     */
    public function update_avail(int $siteID, array $avails)
    {
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'update_avail': " . $siteID);
        if (empty($avails) || !is_array($avails))
            throw new Exception("Illegal/missing dates list in 'update_avail': " . $avails);

        // selection MIN and MAX dates from data to build request structure
        $minDate = '9999-99-99';
        $maxDate = '0000-00-00';
        foreach($avails as $roomID => $list){
            if (!$list)
                continue;
            elseif (!is_array($list))
                throw new Exception("Wrong format for room " . $roomID . " in 'update_avail': " . $list);

            $dates = array_keys($list);

            $minDate = min($minDate, min($dates));
            $maxDate = max($maxDate, max($dates));
        }

        $empty = new \stdClass;         // empty value for dates that don't need updates
        $range = \UtilsDate::getRange($minDate, $maxDate, true);    // array will date from MIN to MAX

        $final = [];
        foreach($avails as $roomID => $list){
            if (!$list)
                continue;

            $local = [];
            foreach($range as $date)
                $local[] = isset($list[$date]) ? ['avail' => intval($list[$date])] : $empty;

            $final[] = ['id' => intval($roomID), 'days' => $local];
        }

        $dcon = implode('/', array_reverse(explode('-', $minDate)));

        $this->_send('update_avail', [$sid, $dcon, $final]);

        return $this;
    }

    /**
     * @param int    $siteID  property ID
     * @param string $url     callback API URL
     * @return $this
     * @throws Exception
     */
    public function set_api_url(int $siteID, string $url)
    {
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'set_api_url': " . $siteID);
        if (!$url)
            throw new Exception("Illegal/missing url in 'set_api_url': " . $url);

        $this->_send('push_activation', [$sid, $url]);

        return $this;
    }

    /**
     * @param int $siteID  property ID
     * @return $this
     * @throws Exception
     */
    public function remove_api_url(int $siteID){
        $sid = intval($siteID);
        if (!$sid)
            throw new Exception("Illegal/missing property ID in 'remove_api_url': " . $siteID);

        $this->_send('push_activation', [$sid, '']);

        return $this;
    }



}


class Exception extends \Exception {}
