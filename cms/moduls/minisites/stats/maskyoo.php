<?php
include_once "../../../bin/system.php";


function post_data($url, $data,$tkn){
    $fields = '';
    foreach($data as $key => $value) {
        $fields .= $key . '=' . $value . '&';
    }
    rtrim($fields, '&');
    $post = curl_init();
    curl_setopt($post, CURLOPT_URL, $url);
    curl_setopt($post, CURLOPT_POST, count($data));
    curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($post, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$tkn));
    $result = curl_exec($post);
    curl_close($post);
    return $result;
}

function getCalls($url,$tkn,$from,$to){
    $base_url = $url;
    if(isset($_GET['from'])) {
        $data = array(
            "service" => "cdr_query",
            "sql" => "SELECT id, start_call, end_call, call_duration, cdr_ani, cdr_ddi, onetouch, user_phone, user_name, cdr_uniqueid, call_status FROM webserviceview where  start_call > '".$from."'",
            "format" => "json"
        );
    }
    else {
        $data = array(
            "service" => "cdr_query",
            "sql" => "SELECT id, start_call, end_call, call_duration, cdr_ani, cdr_ddi, onetouch, user_phone, user_name, cdr_uniqueid, call_status FROM webserviceview where  start_call > '".date('Y-m-d H:i:s', strtotime('-1 hour'))."'",
            "format" => "json"
        );
    }

    $result = post_data($base_url, $data,$tkn);
    $result = json_decode($result,true);

    if($result['status']['code'] == 200 && $result['status']['description'] == 'ok') {
        return  $result;
    }
    file_put_contents("maskyooerror.txt",date("Y-m-d H:i:s") . PHP_EOL . print_r($result,true). PHP_EOL,FILE_APPEND);
    return  [];
}


if(isset($_GET['from'])) {
    $from = date('Y-m-d H:i:s',strtotime(implode("-",array_reverse(explode("/",$_GET['from'])))));
    if(isset($_GET['to'])) {
        $to = date('Y-m-d H:i:s',strtotime(implode("-",array_reverse(explode("/",$_GET['to'])))));
    }
    else {
        $to = date('Y-m-d H:i:s',strtotime('+1 Days',strtotime($from)));
    }

}
else {
    $from = date('Y-m-d H:i:s');
    $to = date('Y-m-d H:i:s',strtotime('+1 Days',strtotime($from)));
}



$sitesPhones = udb::key_row("select DISTINCT maskyooPhone,siteID,domainID from sites_domains where maskyooPhone!='' and maskyooPhone is not null ORDER BY `sites_domains`.`domainID` DESC","maskyooPhone");

// Vila4u and Daka90 and VII START............................
//$base_url = "https://maskyoo.co.il/vila4u/api/";
//$result = getCalls($base_url,$from,$to,6,1);

$tokens = [];
$tokens['mamash']['url'] = "https://maskyoo.com/mamash/api/";
$tokens['mamash']['token'] = "cymzPALh_DhJDB1rXbPueedBqW0ar7FuShsfbw3sk3voDvIqQ72veBtXVzZPpe5n8k2ve1C3v30YUVoySL_4Jr8OtQ1yjZ1ML2NvBn-JzBSGLA";

$tokens['vila4u']['url'] = "https://maskyoo.co.il/vila4u/api/";
$tokens['vila4u']['token'] = "HUKzPALh_DhJDB1rXbPuYu9AqSoHr7FuShsfbw3sk3voDvIqQ72veBtXVzZPpe5n8k2ve1C3v30aUF8wS7r0LLIOtQ1yjZ1ML2NvBn-JzBSGLA";

$tokens['peaks_251']['url'] = "https://maskyoo.com/peaks_251/api/";
$tokens['peaks_251']['token'] = "_duzPALh_DhJDB1rXbPuZONNo20tv6h9DU9NYwf5pWbjc6dwULepfR9RWzJLsfwkqg67ahPq-G4VQVYyS774LLMf_DwPnp1ML2NvBn-JzBSGLA";


$result = [];
$result['result'] = [];
foreach ($tokens as $k=>$tkn) {
    $tmp = getCalls($tkn['url'],$tkn['token'],$from,$to);
    if($tmp['result'])
        $result['result'] = array_merge($result['result'],$tmp['result']);
}
if($result && $result['result']) {
    $counter = 0;
    foreach ($result['result'] as $call) {
        $maskyoo = $call['cdr_ddi'];
        if(substr($call['cdr_ddi'],0 , 3) == "972") {
            $maskyoo = "0" .  substr($call['cdr_ddi'],3 , strlen($maskyoo));
        }
        if(strpos($maskyoo,"-") === false) {
            $maskyoo = substr_replace($maskyoo, "-", 3, 0);
        }

        if(isset($sitesPhones[$maskyoo])) {
            $counter++;
            $cp = [];
            $cp['siteID'] = $sitesPhones[$maskyoo]['siteID'];
            $cp['domainID'] = $sitesPhones[$maskyoo]['domainID'];
            $cp['start_call'] = $call['start_call'];
            $cp['end_call'] = $call['end_call'];
            $cp['cdr_ani'] = $call['cdr_ani'];
            $cp['cdr_ddi'] = $call['cdr_ddi'];
            $cp['user_phone'] = $call['user_phone'];
            $cp['user_name'] = $call['user_name'];
            $cp['call_status'] = $call['call_status'];
            $cp['id'] = $call['id'];
            udb::insert("maskyooCalls",$cp,true);
//        print_r($cp);
//        echo '<BR>**********************<BR>';
        }

    }
}

?>

