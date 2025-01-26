<?php
const VILLA_ID = 1604225514;
const ROOM_ID = 481829;

include_once(__DIR__ . '/PhpXmlRpc/Autoloader.php');

PhpXmlRpc\Autoloader::register();

class WuBookManager {
    const API_URL   = 'https://wired.wubook.net/xrws/';
    const API_TOKEN = 'wr_807d6039-6eb6-4387-a0d7-3c56d6b083fb';

    private $client;

    public function __construct(){
        $this->client = new PhpXmlRpc\Client(static::API_URL);
    }

    public function fetch_rooms(){
        $myStruct = (new PhpXmlRpc\Encoder())->encode([
            static::API_TOKEN,
            VILLA_ID
        ]);

        $req = new PhpXmlRpc\Request(
            'fetch_rooms',
            $myStruct
        );

        $result = $this->client->send($req);

        //print_r($result);

        $decoded = (new PhpXmlRpc\Encoder())->decode($result->value());

        print_r($decoded);
    }

    public function update_avail($data){
        ksort($data, SORT_STRING);

        $avails = [];
        foreach($data as $date => $avail)
            $avails[] = ['avail' => intval($avail)];

        $date = min(array_keys($data));

        $myStruct = (new PhpXmlRpc\Encoder())->encode([
            static::API_TOKEN,
            VILLA_ID,
            date('d/m/Y', strtotime($date)),
            [['id' => ROOM_ID, 'days' => $avails]]
        ]);

        $req = new PhpXmlRpc\Request(
            'update_avail',
            $myStruct
        );

        $result = $this->client->send($req);

        //print_r($result);

        $decoded = (new PhpXmlRpc\Encoder())->decode($result->value());

        print_r($decoded);

    }
}
