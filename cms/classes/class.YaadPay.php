<?php
class YaadPay extends Terminal {
    protected $API_URL  = 'https://icom.yaad.net/p3/';
    protected $API_KEY  = 'b6589fc6ab0dc82cf12099d1c2d40ab994e8410c';
    protected $API_PASS = '123456';
    protected $TERMINAL = '0010151920';
    protected $ownerID  = 0;

    protected static $DEFAULT_PASS = 'Zfg3vcmk83pS0N';
    protected static $DEFAULT_PARENT = '4500766330';

    private $link;
    private $currentSite = 0;

    public static $INVOICE_URL = 'https://icom.yaad.net/cgi-bin/yaadpay/yaadpay3ds.pl';

    protected function _send($data, $transID = 0, $step = 0){
        $perm = [
            'Masof'  => $this->TERMINAL,
            'KEY'    => $this->API_KEY,
            'PassP'  => $this->API_PASS
        ];

        $prm = is_array($data) ?  http_build_query(array_merge($perm, $data)) : $data . '&' . http_build_query($perm);

        curl_setopt($this->link, CURLOPT_POSTFIELDS, $prm);

        $recordID = $this->trans_record_create($transID, $step, $prm);
        $res = curl_exec($this->link);
        $this->trans_record_update($recordID, $res);

        if (curl_errno($this->link))
            throw new Exception('Connect error: ' . curl_error($this->link));

        return $res;
    }

    protected function _send2($data, $transID = 0, $step = 0){
        $perm = [
            'Masof'  => $this->TERMINAL,
            'KEY'    => $this->API_KEY,
            'PassP'  => $this->API_PASS
        ];

        $prm = is_array($data) ?  http_build_query(array_merge($perm, $data)) : $data . '&' . http_build_query($perm);

        curl_setopt($this->link, CURLOPT_URL, self::$INVOICE_URL);      // changing URL to correct link
        curl_setopt($this->link, CURLOPT_POSTFIELDS, $prm);

        $recordID = $this->trans_record_create($transID, $step, $prm);
        $res = curl_exec($this->link);
        $this->trans_record_update($recordID, $res);

        curl_setopt($this->link, CURLOPT_URL, $this->API_URL);      // restoring URL for next request

        if (curl_errno($this->link))
            throw new Exception('Connect error: ' . curl_error($this->link));

        return $res;
    }

    protected function trans_create($type, $sum, $input){
        return udb::insert('pm_transactions', [
            'transType'  => $type,
            'siteID'     => $this->currentSite,
            'sum'        => $sum,
            'createTime' => date('Y-m-d H:i:s'),
            'engine'     => 'Yaad',
            'input'      => json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ]);
    }

    protected function trans_update($transID, $success, $error, $result = ''){
        return udb::update('pm_transactions', [
            'status'       => $success ? 1 : 0,
            'completeTime' => date('Y-m-d H:i:s'),
            'error'        => $error,
            'result'       => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ], '`transID` = ' . $transID);
    }

    protected function trans_result($transID){
        $result = udb::single_value("SELECT `result` FROM `pm_transactions` WHERE `transID` = " . $transID);
        return json_decode($result, true);
    }

    protected function trans_update_result($transID, $data){
        $newres = array_merge($this->trans_result($transID), $data);

        return udb::update('pm_transactions', [
            'result' => json_encode($newres, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ], '`transID` = ' . $transID);
    }

    protected function trans_record_create($transID, $step, $request){
        $rid = udb::insert('pm_trans_log', [
            'transID'   => $transID,
            'step'      => $step,
            'startTime' => date('Y-m-d H:i:s'),
            'request'   => json_encode($request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ]);

        udb::update('pm_transactions', [
//            'status'   => 0,
            'recordID' => $rid
        ], '`transID` = ' . $transID);

        return $rid;
    }

    protected function trans_record_update($recordID, $response){
        return udb::update('pm_trans_log', [
            'endTime'  => date('Y-m-d H:i:s'),
            'response' => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ], '`recordID` = ' . $recordID);
    }

    public function __construct($siteID = 0){
        $this->link = curl_init($this->API_URL);

        curl_setopt($this->link, CURLOPT_POST, true);
        curl_setopt($this->link, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->link, CURLOPT_FAILONERROR, true);
        curl_setopt($this->link, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->link, CURLOPT_CONNECTTIMEOUT, 20);

        $this->currentSite = intval($siteID);
    }

    public function directPay($sum, $desc, $tokenData, $_rel = 0, $payments = 1){
        $transID = $this->trans_create('direct_pay', 1, ['_owner' => $this->ownerID, 'rel' => $_rel, 'sum' => $sum, 'description' => $desc]);

        $params = [
            'action'      => 'soft',
            'Info'        => str_replace('#', '', $desc),
            'UTF8'        => 'True',
            'UTF8out'     => 'True',
            'Amount'      => $sum,
            'Tash'        => $payments ?: 1,
            'tashType'    => 1,     // regular
            'Order'       => $transID,
            'sendemail'   => 'True',
            'MoreData'    => 'True',
            'CC'          => $tokenData['token'],
            'Tmonth'      => substr($tokenData['exp'], -2),
            'Tyear'       => substr($tokenData['exp'], 0, 2),
            'Coin'        => 1,     // ILS
            'Token'       => 'True',
            'SendHesh'    => 'True',
            'UserId'      => $tokenData['tz'],
            'ClientName'  => $tokenData['name']
        ];

        if ($tokenData['cvv'])
            $params['cvv'] = $tokenData['cvv'];

        if ($tokenData['parent'] && strcmp($this->TERMINAL, $tokenData['parent']))
            $params['tOwner'] = $tokenData['parent'];

        $result = $this->_send($params, $transID, 1);

        parse_str($result, $data);

        $success = (strlen($data['CCode']) && strcmp($data['CCode'], '0') == 0) ? true : false;
        $final = typemap([
            'success'  => $success,
            'ccode'    => $data['CCode'],
            'error'    => $success ? '' : self::error($data['errMsg']),
            'sum'      => $data['Amount'],
            'name'     => $data['Fild1'],
            'passport' => $data['UserId'],
            'phone'    => $tokenData['phone'],
            'authCode' => $data['ACode'],
            'last4'    => $data['L4digit'],
            'expires'  => $data['Tmonth'] . '/' . substr($data['Tyear'], -2),
            'exID'     => $data['Id'],
            'invoice'  => $data['Hesh'],
            '_transID' => $transID,
            '_type'    => 'payment',
            '_account' => $this->ownerID
        ], ['string' => 'string']);

        // if successful - create an URL to invoice
        if ($success && $final['invoice'])
            $final['invoiceURL'] = $this->getInvoice($transID, $final['invoice']);

        $this->trans_update($transID, $success, $data['error'], $final);

        return $final;
    }

    public function initFramePay($data){
        $transID = $this->trans_create('frame_pay', 1, array_merge($data, ['_owner' => $this->ownerID]));

        $params = [
            'action'      => 'APISign',
            'What'        => 'SIGN',
            'Info'        => str_replace('#', '', $data['description']),
            'UTF8'        => 'True',
            'Sign'        => 'True',
            'UTF8out'     => 'True',
            'Amount'      => $data['sum'],
            'Order'       => $transID,
            'PageLang'    => 'HEB',
            'sendemail'   => 'True',
            'MoreData'    => 'True',
            'pageTimeOut' => 'True',
            'SendHesh'    => 'True',
            'tmp'         => 3
        ];

        $result = $this->_send($params, $transID, 1);

        if (preg_match('/^CCode=/', $result))
            throw new Exception(preg_match('/^CCode=902/', $result) ? "שגיאה בהתחברות ל-Yaad" : "שגיאה בהתחול סליקה");

        $this->trans_record_create($transID, 2, $this->API_URL . '?action=pay&' . $result);

        return [
            'transID' => $transID,
            'url'     => $this->API_URL . '?action=pay&' . $result
        ];
    }

    public function checkFramePay($data){
        $transID = intval($data['Order']);
        if (!$transID)
            throw new Exception('Missing transaction ID');

        $recordID = udb::single_value("SELECT `recordID` FROM `pm_transactions` WHERE `transType` = 'frame_pay' AND `transID` = " . $transID);

        $verified = $this->frameVerify($transID, $data);

        if ($verified){
            $this->trans_record_update($recordID, $data);

            $success = (strlen($data['CCode']) && strcmp($data['CCode'], '0') == 0) ? true : false;
            $final = typemap([
                'ccode'    => $data['CCode'],
                'error'    => $data['errMsg'],
                'sum'      => $data['Amount'],
                'name'     => $data['Fild1'],
                'passport' => $data['UserId'],
                'phone'    => $data['cell'],
                'authCode' => $data['ACode'],
                'last4'    => $data['L4digit'],
                'expires'  => $data['Tmonth'] . '/' . substr($data['Tyear'], -2),
                'exID'     => $data['Id'],
                'invoice'  => $data['Hesh'],
                '_transID' => $transID,
                '_type'    => 'payment',
                '_account' => $this->ownerID
            ], ['string' => 'string']);

            $final['success'] = $success;

            // if successful - create an URL to invoice
            if ($success && $final['invoice'])
                $final['invoiceURL'] = $this->getInvoice($transID, $final['invoice']);

            $this->trans_update($transID, $success, $final['error'], $final);

            return $final;
        }
        else
            throw new Exception('Cannot verify transaction');
    }

    public function initFrameCardTest($needToken = false){
        $transID = $this->trans_create('frame_card_check', 1, ['_owner' => $this->ownerID, 'needToken' => $needToken]);

        $params = [
            'action'   => 'APISign',
            'What'     => 'SIGN',
            'Info'     => 'בדיקת כרטיס',
            'UTF8'     => 'True',
            'Sign'     => 'True',
            'UTF8out'  => 'True',
            'Amount'   => 1,
            'Order'    => $transID,
            'PageLang' => 'HEB',
            'MoreData' => 'True',
            'J5'       => 'True',
            'pageTimeOut' => 'True',
            'tmp'      => 3,
            'tashType' => 1,     // regular payment
            'Tash'     => 1,     // payments count
            'FixTash'  => 'True'
        ];

        $result = $this->_send($params, $transID, 1);

        $this->trans_record_create($transID, 2, $this->API_URL . '?action=pay&' . $result);

        return [
            'transID' => $transID,
            'url'     => $this->API_URL . '?action=pay&' . $result
        ];
    }

    public function checkFrameCardTest($data){
        $transID = intval($data['Order']);
        if (!$transID)
            throw new Exception('Missing transaction ID');

        $recordID = udb::single_value("SELECT `recordID` FROM `pm_transactions` WHERE `transType` = 'frame_card_check' AND `transID` = " . $transID);

        $verified = $this->frameVerify($transID, $data);

        if ($verified){
            $this->trans_record_update($recordID, $data);

            $success = (strlen($data['CCode']) && strcmp($data['CCode'], '700') == 0) ? true : false;
            $final = typemap([
                'ccode'    => $data['CCode'],
                'error'    => $data['errMsg'],
                'sum'      => $data['Amount'],
                'name'     => $data['Fild1'],
                'passport' => $data['UserId'],
                'phone'    => $data['cell'],
                'authCode' => $data['ACode'],
                'last4'    => $data['L4digit'],
                'expires'  => $data['Tmonth'] . '/' . substr($data['Tyear'], -2),
                'exID'     => $data['Id'],
                '_transID' => $transID,
                '_type'    => 'card_check',
                '_account' => $this->ownerID
            ], ['string' => 'string']);

            $final['success'] = $success;

            $this->trans_update($transID, $success, $data['errMsg'], $final);

            return $final;
        }
        else
            throw new Exception('Cannot verify transaction');
    }

    public function frameVerify($transID, $data){
        $params = array_merge($data, [
            'action' => 'APISign',
            'What'   => 'VERIFY'
        ]);

        $result = $this->_send($params, $transID, 3);

        return !!(trim($result) == 'CCode=0');
    }

    public function payCancel($exID, $transID){
        $params = [
            'action'   => 'CancelTrans',
            'TransId'  => $exID,
            'SendHesh' => 'True'
        ];

        $result = $this->_send($params, $transID, 10);

        parse_str($result, $data);

        $final = array_merge([
            'success'  => (strlen($data['CCode']) && strcmp($data['CCode'], '0') == 0) ? true : false,
            'ccode'    => $data['CCode'],
            'error'    => self::error($data['CCode']),
            '_transID' => $transID,
            '_type'    => 'pay_cancel',
            '_account' => $this->ownerID
        ], $data);

        $this->trans_update($transID, $final['success'], '', $final);

        return $final;
    }

    public function payRefund($exID, $sum){
        $transID = $this->trans_create('pay_refund', $sum, ['_owner' => $this->ownerID, 'exID' => $exID]);

        $params = [
            'action'   => 'zikoyAPI',
            'TransId'  => $exID,
            'Amount'   => $sum,
            'UTF8'     => 'True',
            'UTF8out'  => 'True',
            'SendHesh' => 'True',
            'Tash'     => 1
        ];

        $result = $this->_send($params, $transID, 20);

        parse_str($result, $data);

        $final = array_merge([
            'success'  => (strlen($data['CCode']) && strcmp($data['CCode'], '0') == 0) ? true : false,
            'ccode'    => $data['CCode'],
            'error'    => self::error($data['CCode']),
            'sum'      => $sum,
            '_transID' => $transID,
            '_type'    => 'pay_refund',
            '_account' => $this->ownerID
        ], $data);

        // if successful - create an URL to invoice
        if ($final['success'] && trim($final['HeshASM']))
            $final['invoiceURL'] = $this->getInvoice($transID, trim($final['HeshASM']));

        $this->trans_update($transID, $final['success'], $final['success'] ? '' : 'ERROR ' . $data['CCode'], $final);

        return $final;
    }


    public function requestToken($exID, $trans = 0){
        $transID = $trans ?: $this->trans_create('get_token', 0, ['_owner' => $this->ownerID, 'exID' => $exID]);

        $params = [
            'action'   => 'getToken',
            'TransId'  => $exID
        ];

        $result = $this->_send($params, $transID, 50);

        parse_str($result, $data);

        $final = array_merge([
            'success'  => (strlen($data['CCode']) && strcmp($data['CCode'], '0') == 0) ? true : false,
            'exID'     => $data['Id'],
            'ccode'    => $data['CCode'],
            'error'    => self::error($data['CCode']),
            '_transID' => $transID,
            '_type'    => 'get_token',
            '_account' => $this->ownerID
        ], $data);

        if (!$trans)
            $this->trans_update($transID, $final['success'], $final['success'] ? '' : 'ERROR ' . $data['CCode'], $final);
        elseif ($final['success']) {
            $prev = $this->trans_result($transID);

            $this->trans_update_result($transID, ['tokenData' => [
                'token'  => $final['Token'],
                'exp'    => $final['Tokef'],
                'name'   => $prev['name'],
                'tz'     => $prev['passport'],
                'phone'  => $prev['phone'],
                'parent' => $this->TERMINAL
            ]]);
        }
        else
            $this->trans_update_result($transID, ['tokenData' => [
                'error' => 'ERROR: ' . $final['CCode']
            ]]);

        return $final;
    }

    public function sendPrintout($type, $sum, $desc, $clientData, $_rel = 0, $payBy = ''){
        $iTypes = ['Cash', 'Check', 'Multi'];

        if (!in_array($type, $iTypes))
            throw new Exception("Unknown transaction type");

        $transID = $this->trans_create('invoice_request', 1, ['_owner' => $this->ownerID, 'rel' => $_rel, 'sum' => $sum, 'description' => $desc, 'client' => $clientData]);

        $params = [
            'action'      => 'soft',
            'Info'        => str_replace('#', '', $desc),
            'UTF8'        => 'True',
            'UTF8out'     => 'True',
            'TransType'   => $type,
            'Amount'      => $sum,
            'Order'       => $transID,
//            'sendemail'   => 'True',
            'MoreData'    => 'True',
            'SendHesh'    => 'True',
            'Coin'        => 1,      // ILS
            'UserId'      => $clientData['tz'] ?: '000000000',
            'ClientName'  => $clientData['full_name'] ?: $clientData['name'],
            'email'       => $clientData['email']
        ];

        if ($payBy){
            $params['Pritim'] = 'True';
            $params['heshDesc'] = '[0~' . $params['Info'] . '~1~' . $sum . '][0~' . $payBy . '~1~0]';
        }

        if ($type == 'Check' || $type == 'Multi'){
            $params = array_merge($params, [
                'Bank'     => $clientData['bank'],
                'Snif'     => $clientData['branch'],
                'PAN'      => $clientData['pan'],
                'CheckNum' => $clientData['docNum'],
                'Date'     => $clientData['docDate']
            ]);
        }

        $result = $this->_send($params, $transID, 1);

        parse_str($result, $data);

        $success = (strlen($data['CCode']) && strcmp($data['CCode'], '0') == 0) ? true : false;
        $final = typemap([
            'success'  => $success,
            'ccode'    => $data['CCode'],
            'error'    => $success ? '' : self::error($data['CCode']),
            'sum'      => $data['Amount'],
            'name'     => $data['Fild1'],
            'passport' => $data['UserId'],
            'exID'     => $data['Id'],
            'invoice'  => $data['Hesh'],
            '_transID' => $transID,
            '_type'    => 'payment',
            '_account' => $this->ownerID
        ], ['string' => 'string']);

        // if successful - create an URL to invoice
        if ($success && $final['invoice']){
            $final['invoiceURL'] = $this->getInvoice($transID, $final['invoice']);
//            $params = [
//                'action' => 'APISign',
//                'What'   => 'SIGN',
//                'asm'    => $final['invoice'],
//                'type'   => 'PDF',
//                'ACTION' => 'PrintHesh'
//            ];
//
//            $result = $this->_send2($params, $transID, 2);
//            parse_str($result, $data);
//
//            if ($data['signature'])
//                $final['invoiceURL'] = $result;
        }

        $this->trans_update($transID, $success, $data['error'], $final);

        return $final;
    }

    public function getInvoice($transID, $hesh, $step = 10){
        $params = [
            'action' => 'APISign',
            'What'   => 'SIGN',
            'asm'    => $hesh,
            'type'   => 'PDF',
            'ACTION' => 'PrintHesh'
        ];

        $result = $this->_send2($params, $transID, $step);
        parse_str($result, $data);

        return $data['signature'] ? $result : null;
    }


    public static function factory($masof, $key, $pass, $owner = 0){
        $client = new self;

        $client->TERMINAL = $masof;
        $client->API_KEY  = $key;
        $client->API_PASS = $pass;
        $client->ownerID  = $owner;

        return $client;
    }

    public static function setupTerminal($siteID, $termID, $termKey, $type = 'max'){
        $site = udb::single_row("SELECT `masof_active`, `masof_number`, `masof_key` FROM `sites` WHERE `siteID` = " . $siteID);

        if (!$site)
            throw new Exception("Cannot find site #" . $siteID);
        if ($site['masof_number'] || $site['masof_key'])
            throw new Exception("Site #" . $siteID . " already have terminal data: " . print_r($site, true));

        udb::update('sites', [
            'masof_type'   => $type,
            'masof_active' => 1,
            'masof_number' => $termID,
            'masof_key'    => $termKey
        ], '`siteID` = ' . $siteID);
    }

    public static function getTerminal($siteID){
        $site = udb::single_row("SELECT `masof_active`, `masof_number`, `masof_key`, `masof_no_cvv`, `masof_invoice`, `masof_swipe`, `masof_noVAT`, `masof_department`, `masof_no_charge` FROM `sites` WHERE `siteID` = " . $siteID);

        if (!$site || !$site['masof_active'] || !$site['masof_key'] || !$site['masof_number'])
            return null;

        $mas = self::factory($site['masof_number'], $site['masof_key'], self::$DEFAULT_PASS, $siteID);

        $mas->has_cc_check  = true;
        $mas->has_cc_charge =  !intval($site['masof_no_charge']);
        $mas->has_tokens    = !!intval($site['masof_no_cvv']);
        $mas->has_invoice   = !!intval($site['masof_invoice']);
        $mas->has_swipe     = !!intval($site['masof_swipe']);
        $mas->has_VAT       =  !intval($site['masof_noVAT']);

        $mas->department   = $site['masof_department'];

        return $mas;
    }

    public static function defaultTerminal(){
        return self::factory('4500766330', '29f0a15b8a1d1456149570cd55f4c06f36e0524a', 'cVj83naNqo0');
    }

    public static function checkByInput($data){
        $transID = intval($data['Order']);
        if (!$transID)
            throw new Exception('Missing transaction ID');

        $trans = udb::single_row("SELECT * FROM `pm_transactions` WHERE `transID` = " . $transID);
        if (!$trans)
            throw new Exception('Cannot find transaction data');

        $input = json_decode($trans['input'], true);

        $client = $input['_owner'] ? self::getTerminal($input['_owner']) : self::defaultTerminal();

        switch($trans['transType']){
            case 'frame_card_check':
                $res = $client->checkFrameCardTest($data);

                if ($res['success'] && ($input['_owner'] || $input['needToken'])){
                    $token = $client->requestToken($res['exID'], $res['_transID']);
                    $res['hasToken'] = $token['success'] ? 1 : 0;
                }

                return $res;

            case 'frame_pay':
                return $client->checkFramePay($data);

            default:
                throw new Exception('Unknown transaction type');
        }
    }

    public static function cleanup($transID){
        $result = udb::single_row("SELECT * FROM `pm_transactions` WHERE `transID` = " . $transID);

        $result = json_decode($result, true);
        if (is_array($result)){
            if ($result['Token'])
                $result['Token'] = '****' . substr($result['Token'], -4);
            if ($result['Tokef'])
                $result['Tokef'] = '****';

            udb::update('pm_transactions', ['result' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)], '`transID` = ' . $transID);
        }

        udb::query("DELETE FROM `pn_trans_log` WHERE `transID` = " . $transID . " AND `step` = 50");      // token request
    }

    public static function error($code){
        return YaadError::$list[intval(ltrim($code, '0'))] ?? 'Error ' . $code;
    }

    public function engine(){
        return 'Yaad';
    }
}


class YaadError {
    public static $list = [
        0 => '',
        1 => 'חסום החרם כרטיס',
        2 => 'גנוב החרם כרטיס',
        3 => 'התקשר לחברת האשראי',
        4 => 'סירוב',
        5 => 'מזויף החרם כרטיס',
        6 => 'תז או CVV שגויים',
        7 => 'חובה להתקשר לחברת האשראי',
        8 => 'תקלה בבניית מפתח גישה לקובץ חסומים',
        9 => 'לא הצליח להתקשר, התקשר לחברת האשראי',
        10 => 'תוכנית הופסקה עפ"י הוראת המפעיל (ESC) או COM PORT לא ניתן לפתיחה (WINDOWS)',
        15 => 'אין התאמה בין המספר שהוקלד לפס המגנטי',
        17 => 'לא הוקלדו 4 ספרות האחרונות',
        19 => 'רשומה בקובץ INT_IN קצרה מ',
        20 => 'קובץ קלט (IN_INT) לא קיים',
        21 => 'קובץ חסומים (NEG) לא קיים או לא מעודכן',
        22 => 'אחד מקבצי פרמטרים או ווקטורים לא קיים',
        23 => 'קובץ תאריכים (DATA) לא קיים',
        24 => 'קובץ אתחול (START) לא קיים',
        25 => 'הפרש בימים בקליטת חסומים גדול מדי',
        26 => 'הפרש דורות בקליטת חסומים גדול מידי – בצע שידור או בקשה לאישור עבור כל עסקה',
        27 => 'כאשר לא הוכנס פס מגנטי כולו הגדר עסקה כעסקה טלפונית או כעסקת חתימה בלבד',
        28 => 'מספר מסוף מרכזי לא הוכנס למסוף המוגדר לעבודה כרב ספק',
        29 => 'מספר מוטב לא הוכנס למסוף המוגדר לעבודה כרב מוטב',
        30 => "מסוף שאינו מעודכן כרב ספק/רב מוטב והוקלד מס' ספק/מס' מוטב",
        31 => "מסוף מעודכן כרב ספק והוקלד גם מס' מוטב",
        32 => 'תנועות ישנות בצע שידור או בקשה לאישור עבור כל עסקה',
        33 => 'כרטיס לא תקין',
        34 => 'כרטיס לא רשאי לבצע במסוף זה או אין אישור לעסקה כזאת',
        35 => 'כרטיס לא רשאי לבצע עסקה עם סוג אשראי זה',
        36 => 'פג תוקף',
        37 => 'שגיאה בתשלומים',
        38 => 'לא ניתן לבצע עסקה מעל תקרה לכרטיס לאשראי חיוב מיידי',
        39 => 'ספרת ביקורת לא תקינה',
        40 => "מסוף שמוגדר כרב מוטב הוקלד מס' ספק",
        41 => 'מעל תקרה, אך קובץ מכיל הוראה לא לבצע שאילתא (J1,J2,J3 )',
        42 => 'חסום בספק, אך קובץ הקלט מכיל הוראה לא לבצע שאילתא (J1,J2,J3 )',
        43 => 'אקראית, אך קובץ הקלט מכיל הוראה לא לבצע שאילתא (J1,J2,J3 )',
        44 => 'מסוף לא רשאי לבקש אישור ללא עסקה, אך קובץ הקלט מכיל (5J)',
        45 => 'מסוף לא רשאי לבקש אישור ביוזמתו, אך קובץ הקלט מכיל (6J)',
        46 => 'יש לבקש אישור, אך קובץ הקלט מכיל הוראה לא לבצע שאילתא (J1,J2,J3 )',
        47 => 'יש לבקש אישור בשל בעיה הקשורה לקכ"ח אך קובץ הקלט מכיל הוראה לא לבצע שאילתא',
        51 => 'מספר רכב לא תקין',
        52 => 'מד מרחק לא הוקלד',
        53 => 'מסוף לא מוגדר כתחנת דלק (הועבר כרטיס דלק או קוד עסקה לא מתאים)',
        57 => 'לא הוקלד מספר תעודת זהות',
        58 => 'לא הוקלד CVV2',
        59 => 'לא הוקלדו מספר תעודת הזהות וה',
        60 => 'צרוף ABS לא נמצא בהתחלת נתוני קלט בזיכרון',
        61 => 'מספר כרטיס לא נמצא או נמצא פעמיים',
        62 => 'סוג עסקה לא תקין',
        63 => 'קוד עסקה לא תקין',
        64 => 'סוג אשראי לא תקין',
        65 => 'מטבע לא תקין',
        66 => 'קיים תשלום ראשון ו/או תשלום קבוע לסוג אשראי שונה מתשלומים',
        67 => 'קיים מספר תשלומים לסוג אשראי שאינו דורש זה',
        68 => 'לא ניתן להצמיד לדולר או למדד לסוג אשראי שונה מתשלומים',
        69 => 'אורך הפס המגנטי קצר מידי',
        70 => 'לא מוגדר מכשיר להקשת מספר סודי',
        71 => 'חובה להקליד מספר סודי',
        72 => 'קכ"ח לא זמין – העבר בקורא מגנטי',
        73 => 'הכרטיס נושא שבב ויש להעבירו דרך הקכ"ח',
        74 => 'דחייה – כרטיס נעול',
        75 => 'דחייה – פעולה עם קכ"ח לא הסתיימה בזמן הראוי',
        76 => 'דחייה – נתונים אשר התקבלו מקכ"ח אינם מוגדרים במערכת',
        77 => 'הוקש מספר סודי שגוי',
        80 => 'הוכנס "קוד מועדון" לסוג אשראי לא מתאים',
        99 => 'לא מצליח לקרוא/ לכתוב/ לפתוח קובץ TRAN',
        101 => 'אין אישור מחברת אשראי לעבודה',
        106 => 'למסוף אין אישור לביצוע שאילתא לאשראי חיוב מיידי',
        107 => 'סכום העסקה גדול מידי – חלק למספר העסקאות',
        108 => 'למסוף אין אישור לבצע עסקאות מאולצות',
        109 => 'למסוף אין אישור לכרטיס עם קוד השרות 587',
        110 => 'למסוף אין אישור לכרטיס חיוב מיידי',
        111 => 'למסוף אין אישור לעסקה בתשלומים',
        112 => 'למסוף אין אישור לעסקה טלפון/ חתימה בלבד בתשלומים',
        113 => 'למסוף אין אישור לעסקה טלפונית',
        114 => 'למסוף אין אישור לעסקה "חתימה בלבד',
        115 => 'למסוף אין אישור לעסקה בדולרים',
        116 => 'למסוף אין אישור לעסקת מועדון',
        117 => 'למסוף אין אישור לעסקת כוכבים/נקודות/מיילים',
        118 => 'למסוף אין אישור לאשראי ישראקרדיט',
        119 => 'למסוף אין אישור לאשראי אמקס קרדיט',
        120 => 'למסוף אין אישור להצמדה לדולר',
        121 => 'למסוף אין אישור להצמדה למדד',
        122 => 'למסוף אין אישור להצמדה למדד לכרטיסי חו"ל',
        123 => 'למסוף אין אישור לעסקת כוכבים/נקודות/מיילים לסוג אשראי זה',
        124 => 'למסוף אין אישור לאשראי קרדיט בתשלומים לכרטיסי ישראכרט',
        125 => 'למסוף איו אישור לאשראי קרדיט בתשלומים לכרטיסי אמקס',
        126 => 'למסוף אין אישור לקוד מועדון זה',
        127 => 'למסוף אין אישור לעסקת חיוב מיידי פרט לכרטיסי חיוב מיידי',
        128 => 'למסוף אין אישור לקבל כרטיסי ויזה אשר מתחילים ב',
        129 => 'למסוף אין אישור לבצע עסקת זכות מעל תקרה',
        130 => 'כרטיס לא רשאי לבצע עסקת מועדון',
        131 => 'כרטיס לא רשאי לבצע עסקת כוכבים/נקודות/מיילים',
        132 => 'כרטיס לא רשאי לבצע עסקאות בדולרים (רגילות או טלפוניות)',
        133 => 'כרטיס לא תקף על פי רשימת כרטיסים תקפים של ישראכרט',
        134 => 'כרטיס לא תקין עפ”י הגדרת המערכת (VECTOR1 של ישראכרט)',
        135 => 'כרטיס לא רשאי לבצע עסקאות דולריות עפ”י הגדרת המערכת (VECTOR1 של ישראכרט)',
        136 => 'הכרטיס שייך לקבוצת כרטיסים אשר אינה רשאית לבצע עסקאות עפ”י הגדרת המערכת (VECTOR20 של ויזה)',
        137 => 'קידומת הכרטיס (7 ספרות) לא תקפה עפ”י הגדרת המערכת (21VECTOR של דיינרס)',
        138 => 'כרטיס לא רשאי לבצע עסקאות בתשלומים על פי רשימת כרטיסים תקפים של ישראכרט',
        139 => 'מספר תשלומים גדול מידי על פי רשימת כרטיסים תקפים של ישראכרט',
        140 => 'כרטיסי ויזה ודיינרס לא רשאים לבצע עסקאות מועדון בתשלומים',
        141 => 'סידרת כרטיסים לא תקפה עפ”י הגדרת המערכת (VECTOR5 של ישראכרט)',
        142 => 'קוד שרות לא תקף עפ”י הגדרת המערכת (VECTOR6 של ישראכרט)',
        143 => 'קידומת הכרטיס (2 ספרות) לא תקפה עפ”י הגדרת המערכת (VECTOR7 של ישראכרט)',
        144 => 'קוד שרות לא תקף עפ”י הגדרת המערכת (VECTOR12 של ויזה)',
        145 => 'קוד שרות לא תקף עפ”י הגדרת המערכת (VECTOR13 שלויזה)',
        146 => 'לכרטיס חיוב מיידי אסור לבצע עסקת זכות',
        147 => 'כרטיס לא רשאי לבצע עסקאות בתשלומים עפ"י וקטור 31 של לאומיקארד',
        148 => 'כרטיס לא רשאי לבצע עסקאות טלפוניות וחתימה בלבד עפ"י ווקטור 31 של לאומיקארד',
        149 => 'כרטיס אינו רשאי לבצע עסקאות טלפוניות עפ"י וקטור 31 של לאומיקארד',
        150 => 'אשראי לא מאושר לכרטיסי חיוב מיידי',
        151 => 'אשראי לא מאושר לכרטיסי חו"ל',
        152 => 'קוד מועדון לא תקין',
        153 => 'כרטיס לא רשאי לבצע עסקאות אשראי גמיש (עדיף +30/) עפ"י הגדרת המערכת (21VECTOR של דיינרס)',
        154 => 'כרטיס לא רשאי לבצע עסקאות חיוב מיידי עפ"י הגדרת המערכת (VECTOR21 של דיינרס)',
        155 => 'סכום המינמלי לתשלום בעסקת קרדיט קטן מידי',
        156 => 'מספר תשלומים לעסקת קרדיט לא תקין',
        157 => 'תקרה 0 לסוג כרטיס זה בעסקה עם אשראי רגיל או קרדיט',
        158 => 'תקרה 0 לסוג כרטיס זה בעסקה עם אשראי חיוב מיידי',
        159 => 'תקרה 0 לסוג כרטיס זה בעסקת חיוב מיידי בדולרים',
        160 => 'תקרה 0 לסוג כרטיס זה בעסקה טלפונית',
        161 => 'תקרה 0 לסוג כרטיס זה בעסקת זכות',
        162 => 'תקרה 0 לסוג כרטיס זה בעסקת תשלומים',
        163 => 'כרטיס אמריקן אקספרס אשר הונפק בחו"ל לא רשאי לבצע עסקאות תשלומים',
        164 => 'כרטיסיJCB רשאי לבצע עסקאות רק באשראי רגיל',
        165 => 'סכום בכוכבים/נקודות/מיילים גדול מסכום העסקה',
        166 => 'כרטיס מועדון לא בתחום של המסוף',
        167 => 'לא ניתן לבצע עסקת כוכבים/נקודות/מיילים בדולרים',
        168 => 'למסוף אין אישור לעסקה דולרית עם סוג אשראי זה',
        169 => 'לא ניתן לבצע עסקת זכות עם אשראי שונה מהרגיל',
        170 => 'סכום הנחה בכוכבים/נקודות/מיילים גדול מהמותר',
        171 => 'לא ניתן לבצע עסקה מאולצת לכרטיס/אשראי חיוב מיידי',
        172 => 'לא ניתן לבטל עסקה קודמת (עסקת זכות או מספר כרטיס אינו זהה)',
        173 => 'עסקה כפולה',
        174 => 'למסוף אין אישור להצמדה למדד לאשראי זה',
        175 => 'למסוף אין אישור להצמדה לדולר לאשראי זה',
        176 => 'כרטיס אינו תקף עפ”י הגדרת ה מערכת (וקטור 1 של ישראכרט)',
        177 => 'בתחנות דלק לא ניתן לבצע "שרות עצמי" אלא "שרות עצמי בתחנות דלק"',
        178 => 'אסור לבצע עסקת זכות בכוכבים/נקודות/מיילים',
        179 => 'אסור לבצע עסקת זכות בדולר בכרטיס תייר',
        180 => 'בכרטיס מועדון לא ניתן לבצע עסקה טלפונית',
        200 => 'שגיאה יישומית'
    ];
}
