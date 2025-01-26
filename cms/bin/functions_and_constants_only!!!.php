<?php

//const WEBSITE = "https://biz2.c-ssd.com/";
const WEBSITE = "https://bizonline.co.il/";
const DEFAULTLANGUAGE = "he";

const CMS_PATH     = __DIR__ . "/../";
const CLASSES_PATH = CMS_PATH . "classes/";

include_once CLASSES_PATH . "class.udb.php";
include_once CLASSES_PATH . "class.Dictionary.php";
include_once CLASSES_PATH . "class.TabList.php";
include_once CLASSES_PATH . "class.optimizer.php";


$_CRM_PICTURE_TYPES = array('jpg','jpeg','gif','png');

udb::init(array(
    'connect' => array(
        'user' => 'biz2cssd_sql'
    , 'pass' => 'ln7ntwG6gF8s'
    , 'db'   => 'biz2cssd_main'
    )
));

spl_autoload_register(function ($class){
    $base  = explode("\\", ltrim($class, "\\"));
    $base[count($base) - 1] = 'class.' . $base[count($base) - 1] . '.php';

    if (is_file($path = CLASSES_PATH . implode(DIRECTORY_SEPARATOR, $base))){
        include $path;
        return true;
    }
    return false;
});

class LocalException extends \Exception {}



function inputStr($text, $allow_tags=false, $quotes_only=false)
{
    $tmp = preg_replace('/<!--.+-->/isU','',stripslashes($text));
    if ($quotes_only)
        $tmp = str_replace("'","''",$tmp);
    else {
        if (!$allow_tags)
            $tmp = strip_tags($tmp);
        $tmp = str_replace(array('&','"',"'",'>','<'), array('&amp;','&quot;','&#039;','&gt;','&lt;'),$tmp);
    }
    return trim($tmp);
}

function report_error($file, $line, $que)
{
    die(basename($file).':'.$line.' '.mysql_error()."<br /><br />\r\n\r\n".$que);
}

function AddZero($num, $length = 2)
{
    return is_numeric($num) ? str_pad(intval($num), $length, '0', STR_PAD_LEFT) : $num;
}

function inDb($text){
    $text = trim($text);
    $text = udb::escape_string($text);
    return $text;
}

function outDb($text){
    $text = stripslashes($text);
    $text =str_replace("\\","",$text);
    return $text;
}

function showAlias($table,$ref,$folder=false){
    $string='';
    $que="SELECT * FROM `alias` WHERE `table`='".$table."' AND `ref`='".$ref."'";
    $item= udb::single_row($que);
    $linkBuild=Array();
    if ($item['LEVEL1']!=DEFAULTLANGUAGE) $linkBuild[]=$item['LEVEL1'];
    if ($item['LEVEL2']!='') $linkBuild[]=urlencode(str_replace(" ","_", $item['LEVEL2']));
    if ($item['LEVEL3']!='') $linkBuild[]=urlencode($item['LEVEL3']);
    if ($item['LEVEL4']!='') $linkBuild[]=urlencode($item['LEVEL4']);
    if ($item['LEVEL5']!='') $linkBuild[]=urlencode($item['LEVEL5']);
    return WEBSITE.implode("/",$linkBuild).($folder?"/":".html");
}
function showAliasLang($table,$ref,$langID,$folder=false){
    $string='';


    $que="SELECT `alias_text`.*, `language`.LangCode, `alias`.LEVEL2 as `originalLevel2`,  `alias`.LEVEL3 as `originalLevel3` 
          FROM `alias` 
          LEFT JOIN `alias_text` ON(alias.id=alias_text.id AND alias_text.`LangID`='".$langID."') 
          LEFT JOIN `language` ON(language.LangID = ".$langID.")
          WHERE  alias.`table`='".$table."' AND alias.`ref`='".$ref."'";
    $item= udb::single_row($que);

    if ($item['originalLevel2']) $item['LEVEL2']=$item['originalLevel2'];
    if ($item['originalLevel3']) $item['LEVEL3']=$item['originalLevel3'];

    $linkBuild=Array();
    if ($item['LangCode']!=DEFAULTLANGUAGE) $linkBuild[]=$item['LangCode'];
    if ($item['LEVEL2']!='') $linkBuild[]=urlencode(str_replace(" ","_", $item['LEVEL2']));
    if ($item['LEVEL3']!='') $linkBuild[]=urlencode(str_replace(" ","_", $item['LEVEL3']));
    if ($item['LEVEL4']!='') $linkBuild[]=urlencode($item['LEVEL4']);
    if ($item['LEVEL5']!='') $linkBuild[]=urlencode($item['LEVEL5']);
    return WEBSITE.implode("/",$linkBuild).($folder?"/":".html");
}

function showAliasPortal($table,$ref,$portalID,$folder=false){
    $string='';

    $que="SELECT * FROM `portals` WHERE `portalID`='".$portalID."'";
    $prtl= udb::single_row($que);

    $que="SELECT * FROM `alias_text` WHERE `PortalID`='".$portalID."' AND `table`='".$table."' AND `ref`='".$ref."'";
    $item= udb::single_row($que);

    $linkBuild=Array();
    if ($item['LEVEL1']!=DEFAULTLANGUAGE) $linkBuild[]=$item['LEVEL1'];
    if ($item['LEVEL2']!='') $linkBuild[]=urlencode(str_replace(" ","_", $item['LEVEL2']));
    if ($item['LEVEL3']!='') $linkBuild[]=urlencode($item['LEVEL3']);
    if ($item['LEVEL4']!='') $linkBuild[]=urlencode($item['LEVEL4']);
    if ($item['LEVEL5']!='') $linkBuild[]=urlencode($item['LEVEL5']);
    return $prtl['portalUrl'].implode("/",$linkBuild).($folder?"/":".html");
}


function mySize($size)
{
    $exp = array('k' => 1, 'K' => 1, 'M' => 2, 'G' => 3, 'T' => 4);

    if (is_numeric($size))
        return (int)$size;
    if (preg_match('/^(\d+)('.implode('|',array_keys($exp)).')$/', $size, $match))
        return $match[1] * pow(1024, $exp[$match[2]]);
    return 0;
}

function createPath($path, $suff)
{
    $path = rtrim($path,'/').'/';
    $file = str_replace('.','', microtime(true)).($suff ? '.'.$suff : '');
    while(file_exists($path.$file))
        $file = str_replace('.','', microtime(true)).($suff ? '.'.$suff : '');

    return $path.$file;
}

function moveSingleFile($file, $path, $maxSize = 0)
{
    if ($file['error'] == UPLOAD_ERR_OK && (!$maxSize || $file['size'] <= mySize($maxSize))){
        $tmp     = explode('.', $file['name']);
        $newpath = createPath($path, strtolower(end($tmp)));

        if (move_uploaded_file($file['tmp_name'], $newpath)){
            chmod($newpath, 0777);
            return array('file' => basename($newpath), 'original' => $file['name'], 'size' => $file['size']);
        } else
            return "Can't move file '".$file['tmp_name']."' to '".$newpath."'";
    } else
        return ($file['error'] == UPLOAD_ERR_OK) ? "File '".$file['name']."' is larger than ".$maxSize : "File '".$file['name']."' error code ".$file['error'];
}

function resizePicture($file, $sub, $width = 0, $height = 0)
{
    global $_CRM_PICTURE_TYPES;

    $sub_con = array('jpg' => 'jpeg');

    if (!$file || !file_exists($file))
        return 'not a file';
    if (!is_writable($file))
        return 'file is not writable';
    if (!in_array($sub, $_CRM_PICTURE_TYPES))
        return 'type not supported';

    list($w, $h) = getimagesize($file);

    if (($width && $width < $w) || ($height && $height < $h))
    {
        if($height && round($h / $w, 4) >= round($height / $width, 4)) {
            $new_h = $height;
            $new_w = round($w * $height / $h);
        } else {
            $new_w = $width;
            $new_h = round($h * $width / $w);
        }
    }
    else {
        $new_w = $width;
        $new_h = $height;
    }

    if ($img = call_user_func('imagecreatefrom' . ($sub_con[$sub] ? $sub_con[$sub] : $sub), $file)){
        $new_im = imagecreatetruecolor($new_w, $new_h);

        imagecopyresampled($new_im, $img ,0 ,0 ,0 ,0 ,$new_w, $new_h, $w, $h);

        switch($sub){
            case 'jpeg':
            case 'jpg' : $save = imagejpeg($new_im, $file, 100); break;
            case 'gif' : $save = imagegif($new_im, $file); break;
            case 'png' : $save = imagepng($new_im, $file); break;
        }

        imagedestroy($img);
        imagedestroy($new_im);

        return $save ? '' : 'cannot save resized picture';
    }
    else
        return 'cannot create image from file';
}

/**
 * @param string $field index in $_FILES array
 * @param string $path path to upload dir
 * @param int $maxSize maximum allowed file size for file (0 = unlimited)
 * @param mixed $error error message(s) that happened during upload
 * @return array with or arrays with data on uploaded file(s) in format ('file' => new filename, 'original' => original filename. 'size' => file size)
 **/
function fileUpload($field, $path, $maxSize = 0, &$error = null)
{
    $result = $ierror = array();

    if (isset($_FILES[$field]) && is_array($_FILES[$field])){
        $file = $_FILES[$field];

        if (is_array($file['error'])){
            foreach($file['error'] as $index => $err){
                $tmp = array('name' => $file['name'][$index], 'tmp_name' => $file['tmp_name'][$index], 'size' => $file['size'][$index], 'error' => $err);
                $res = moveSingleFile($tmp, $path, $maxSize);

                is_array($res) ? $result[] = $res : $ierror[] = $res;
            }
        } else {
            $res = moveSingleFile($file, $path, $maxSize);

            is_array($res) ? $result[] = $res : $ierror = $res;
        }
    }
    $error = $ierror;
    return $result;
}

/**
 * @param string $field index in $_FILES array
 * @param string $path path to upload dir
 * @param int $maxSize maximum allowed file size for file (0 = unlimited)
 * @param int $width resized picture max width
 * @param int $height resized picture max height
 * @param mixed $error error message(s) that happened during upload
 * @return array with or arrays with data on uploaded file(s) in format ('file' => new filename, 'original' => original filename. 'size' => file size)
 **/
function pictureUpload($field, $path, $maxSize = 0, $width = 0, $height = 0, &$error = null)
{

    if ($res = fileUpload($field, $path, $maxSize, $error)){
        is_numeric(key($res)) || $res = array($res);

        for($i = 0; $i < count($res); $i++){
            $tmp = explode('.', $res[$i]['file']);
            $sub = strtolower(end($tmp));

            if (in_array($sub, array('jpg','gif','png', 'jpeg')) && getimagesize($path.$res[$i]['file'])){
                if ($width > 0 && $height > 0 && ($err = resizePicture($path.$res[$i]['file'], $sub, $width, $height))){
                    $error[] = "-PIC_ERROR-: File '" . $res[$i]['original'] . "' - " . $err;

                    @unlink($path.$res[$i]['file']);
                    unset($res[$i]);
                }
            }
            elseif (strcmp($sub, 'svg')) {
                $error[] = "-PIC_ERROR-: File '" . $res[$i]['original'] . "' - is not a picture";

                @unlink($path.$res[$i]['file']);
                unset($res[$i]);
            }
        }
    }

    return array_values($res);
}



function getSearchParams($mainAreaID=0, $areaID=0, $settID=0, $facilityID=0, $genderID=0){
    $searchPageTitle=Array();

    if($settID){
        $que="SELECT * FROM settlements WHERE settlementID='".$settID."'";
        $settlement=udb::single_row($que);

        $que="SELECT * FROM areas WHERE areaID='".$settlement['areaID']."'";
        $area=udb::single_row($que);

        $que="SELECT * FROM main_areas WHERE main_areaID='".$area['main_areaID']."'";
        $mainArea=udb::single_row($que);

        $searchPageTitle[]="צימרים ב".$settlement['TITLE'].", ב".$area['TITLE'];
    } else if($areaID){
        $que="SELECT * FROM areas WHERE areaID='".$areaID."'";
        $area=udb::single_row($que);

        $que="SELECT * FROM main_areas WHERE main_areaID='".$area['main_areaID']."'";
        $mainArea=udb::single_row($que);

        $searchPageTitle[]="צימרים ב".$area['TITLE'].", ב".$mainArea['TITLE'];

    } else if($mainAreaID){
        $que="SELECT * FROM main_areas WHERE main_areaID='".$mainAreaID."'";
        $mainArea=udb::single_row($que);
        $searchPageTitle[]="צימרים ב".$mainArea['TITLE'];
    }

    if($facilityID){
        $que="SELECT MainPageID, MainPageTitle FROM MainPages WHERE MainPageID='".$facilityID."'";
        $facility=udb::single_row($que);
        if($searchPageTitle){
            $searchPageTitle[]=", ".$facility['MainPageTitle'];
        } else {
            $searchPageTitle[]="צימרים עם ".$facility['MainPageTitle'];
        }
    }
    if($genderID){
        $que="SELECT MainPageID, MainPageTitle FROM MainPages WHERE MainPageID='".$genderID."'";
        $gender=udb::single_row($que);
        if($searchPageTitle){
            $searchPageTitle[]=", ".$gender['MainPageTitle'];
        } else {
            $searchPageTitle[]="צימרים ".$gender['MainPageTitle'];
        }
    }

    $results['title']=implode("", $searchPageTitle);

    return $results;
}


function showAliasSearch($id){

    $que="SELECT * FROM `search` WHERE id=".$id." ";
    $linkSearch= udb::single_row($que);


    $levels=Array();

    if($linkSearch['settID']){
        $que="SELECT * FROM settlements WHERE settlementID='".$linkSearch['settID']."'";
        $settlement=udb::single_row($que);

        $que="SELECT * FROM areas WHERE areaID='".$linkSearch['areaID']."'";
        $area=udb::single_row($que);

        $que="SELECT * FROM main_areas WHERE main_areaID='".$area['main_areaID']."'";
        $mainArea=udb::single_row($que);

        $levels[]=$mainArea['TITLE_eng'];
        $levels[]=$area['TITLE_eng'];
        $levels[]=$settlement['TITLE_eng'];
    } else if($linkSearch['areaID']){
        $que="SELECT * FROM areas WHERE areaID='".$linkSearch['areaID']."'";
        $area=udb::single_row($que);

        $que="SELECT * FROM main_areas WHERE main_areaID='".$area['main_areaID']."'";
        $mainArea=udb::single_row($que);

        $levels[]=$mainArea['TITLE_eng'];
        $levels[]=$area['TITLE_eng'];
    } else if($linkSearch['mainAreaID']){
        $que="SELECT * FROM main_areas WHERE main_areaID='".$linkSearch['mainAreaID']."'";
        $mainArea=udb::single_row($que);
        $levels[]=$mainArea['TITLE_eng'];
    }

    if($linkSearch['whoID']){
        $que="SELECT MainPageID, MainPageTitle, TITLE_eng FROM MainPages WHERE MainPageID='".$linkSearch['whoID']."'";
        $gender=udb::single_row($que);
        $levels[]=($gender['TITLE_eng']?$gender['TITLE_eng']:$gender['MainPageTitle']);
    }
    if($linkSearch['accID']){
        $que="SELECT MainPageID, MainPageTitle, TITLE_eng FROM MainPages WHERE MainPageID='".$linkSearch['accID']."'";
        $facility=udb::single_row($que);
        $levels[]=($facility['TITLE_eng']?$facility['TITLE_eng']:$facility['MainPageTitle']);
    }


    $levels=WEBSITE.str_replace(" ","_", implode("/",$levels)).".html";
    return $levels;
}



function getLocationNumbers($address){
    $apikey = "AIzaSyCnNTvqCR_SwqqkCCOcAr7WlPYQTsZXbQ0";
    //$apikey = "AIzaSyBCBut3eC_1LxaeXMmyILFv6nGJxTa_hZ4";
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&sensor=false&key=" . $apikey;
    $response=curl_func($url);
    $response_a = json_decode($response);
    $loc=Array();
    $loc['lat']=$response_a->results[0]->geometry->location->lat;
    $loc['long']=$response_a->results[0]->geometry->location->lng;
    if(!$loc['lat']) file_put_contents("geolocation-errors.txt", $address . " " . print_r($response_a,true), FILE_APPEND);
    return $loc;
}


function curl_func($url){
    $curl = curl_init();
    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: ";
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    $html = curl_exec($curl);
    curl_close($curl);
    return $html;
}

function maskyoo_send_sms($msg, $destination, $sender){
    $msg = urlencode($msg);

    if($sender){
        $sms_user = "";
        $sms_password = "";

        $request = "https://sms.deals/ws.php?service=send_sms&message=".$msg."&dest=".$destination."&sender=".$sender."&username=".$sms_user."&password=".$sms_password;


        /*if (strlen($to) > 8) $request .= "&list=".$to;
        else $request .= "&pid=".$to;

        if ($msgsm && $link) return "ERROR";
        else if ($msgsm)
        {
            $msgsm = urlencode($msgsm);
            $request .= "&msgsm=".$msgsm;
        }
        else if ($link)
        {
            $link = urlencode($link);
            $request .= "&link=".$link;
        }

        if ($date != "")
        {
            $DateValue = strtotime($date);
            $DateParts = getdate($DateValue);

            $request .= "&dy=".$DateParts["year"];
            $request .= "&dm=".$DateParts["mon"];
            $request .= "&dd=".$DateParts["mday"];
            $request .= "&dh=".$DateParts["hours"];
            $request .= "&di=".$DateParts["minutes"];
        }*/

        $curlSend = curl_init();

        curl_setopt($curlSend, CURLOPT_URL, $request);
        curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, 1);

        $curlResult = curl_exec($curlSend);
        $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);
        curl_close($curlSend);

        if ($curlStatus === 200) return $curlResult;
        else return "ERROR";
    }
}

function flattenMenuKeys($array)
{
    $list = array();
    foreach($array as $key => $val) {
        is_numeric($key) or $list[] = $key;
        if (isset($val['sub']) && count($val['sub']))
            $list = array_merge($list, flattenMenuKeys($val['sub']));
    }

    return array_values(array_unique($list));
}

function db2date($date, $delim = '.', $ylen = 4)
{
    return implode($delim, array_reverse(explode('-', substr($date, max(0, 4 - ($ylen ?: -1))))));
}

function picturePath($path,$beforePath=""){

    if(strpos($path, 'http') !== false){
        $picture = str_replace(" ","%20", $path);
    }else{
        $picture = $beforePath.$path;
    }
    return $picture;


}

function toHTML($str){
    return htmlspecialchars($str, ENT_QUOTES | ENT_XHTML, 'UTF-8', false);
}


function sha1_pass($pass){
    return sha1(sha1(sha1(trim($pass))));
}

function getUserIpAddr(){
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function emptyDir($path){
    foreach (new DirectoryIterator($path) as $file) {
        if ($file->isDir()){
            emptyDir($file->getPathname());
            rmdir($file->getPathname());
        }
        elseif(!$file->isDot()) {
            unlink($file->getPathname());
        }
    }
}

function pictureDir($path){
    $pattern = '/\.(jpg|jpeg|gif|png|tiff|bmp)$/';

    if (preg_match($pattern, $path))
        return preg_replace($pattern, '', $path);
    return false;
}

function smartcut($text, $len = 64){
    $trtext = trim($text);
    $total  = mb_strlen($text, 'UTF-8');

    if ($total <= $len)
        return str_replace(PHP_EOL, ' ', $trtext);

    $break = strpos($trtext, PHP_EOL);
    if ($break !== false){
        $tmp = substr($trtext, 0, $break);
        $tl = mb_strlen($tmp, 'UTF-8');

        if ($tl <= $len)
            return $tmp . ($total > $tl ? '...' : '');
    }

    return mb_substr($trtext, 0, $len, 'UTF-8') . ($total > $len ? '...' : '');
}
