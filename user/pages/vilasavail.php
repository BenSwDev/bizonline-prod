<?
function apiCallGET($act = "" , $siteID = 0){
    $url = "https://www.vila.co.il/api/data-for-biz.php?key=avg142f2g!00a&act=".$act."&siteid=".$siteID;
    $curlSend = curl_init();

    curl_setopt($curlSend, CURLOPT_URL, $url);
    curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, 1);

    $curlResult = curl_exec($curlSend);
    $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);
    curl_close($curlSend);
    if ($curlStatus === 200)
        return json_decode($curlResult,true);
    else
        return [];
}

function apiCallPOST($act = "" , $siteID = 0 ){
    $url = "https://www.vila.co.il/api/data-for-biz.php?key=avg142f2g!00a&act=".$act."&siteid=".$siteID;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, 1);
//    curl_setopt($ch, CURLOPT_POSTFIELDS,
//        "postvar1=value1&postvar2=value2&postvar3=value3");

    // In real life you should use something like:
     curl_setopt($ch, CURLOPT_POSTFIELDS,
              http_build_query($_POST, '', '&'));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $curlStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    if ($curlStatus === 200)
        return json_decode($server_output,true);
    else
        return [];
}

$sql = "select portalsID from sites where siteID=".$_CURRENT_USER->active_site();

$portalsID =  udb::single_value($sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $period = $_POST['period'];

    $updateResponse  = apiCallPOST("updateAvail",$portalsID);
}

echo '<h2>הגדרות זמינות לאתרי וילה פור יו ווילה</h2>';
print_r($updateResponse);
if(!$portalsID) {
    echo 'לא מחובר לאתרי וילה ווילה פור יו';
    return;
}
$periodHtml = apiCallGET('getperiods',$portalsID);
?>
<style>
.table-wrap {display: table;margin: 10px auto;}
.line{display:table-row}
.line * {display: table-cell;padding: 2px 5px;text-align: right;border: 1px #ccc solid;background: white;}
.line * input[type=checkbox]{width:20px;height:20px}
.frees > input[type=submit]{background:green;color:white;padding:10px}
.frees > input[type=submit] {background: #0dabb6;color: white;height: 40px;padding: 0 20px;font-size: 18px;border-radius: 5px;}
</style>

<?
echo $periodHtml['html'];