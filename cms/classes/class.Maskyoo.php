<?php
class Maskyoo {
    static protected $user = "palombo.r@gmail.com";
    static protected $pass = "O0Dev0qj";

    static public function sms($msg, $destination, $sender){
        $msg = urlencode($msg);

        if($sender){
            $sms_user = self::$user;
            $sms_password = self::$pass;

            $request = "https://sms.deals/ws.php?service=send_sms&message=".$msg."&dest=".preg_replace('/\D/', '', $destination)."&sender=".$sender."&username=".$sms_user."&password=".$sms_password;

            $curlSend = curl_init();

            curl_setopt($curlSend, CURLOPT_URL, $request);
            curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, 1);

            $curlResult = curl_exec($curlSend);
            $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);
            curl_close($curlSend);

            if ($curlStatus === 200) return $curlResult;
            else return "ERROR";
        }

        return 'error_no_sender';
    }

    static public function register_name($name){
        $request = 'https://www.sms.deals/api/ws.php?service=add_sender_text&username=' . urlencode(self::$user) . '&password=' . urlencode(self::$pass) . '&sender=' . urlencode($name);

        $curlSend = curl_init($request);

        curl_setopt($curlSend, CURLOPT_RETURNTRANSFER, 1);

        $curlResult = curl_exec($curlSend);
        $curlStatus = curl_getinfo($curlSend, CURLINFO_HTTP_CODE);
        curl_close($curlSend);

        return $curlResult ?: 'ERROR CODE ' . $curlStatus;
    }
}
