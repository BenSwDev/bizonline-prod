<?php
class CardCom extends Terminal {
    const REMOTE_URL = 'https://secure.cardcom.solutions/Interface/BillGoldService.asmx?wsdl';
    const REMOTE_URL_POST = 'https://secure.cardcom.solutions/Interface/';
    const REMOTE_URL_API  = 'https://secure.cardcom.solutions/api/v11/';

    public static $_customPayTypeMap = [
        'Cash'    => ['Cash'],
        'Check'   => ['Check'],
        'CCard'   => ['CCard']
    ];

    public static $_invoiceList = [
        1 => ['name' => 'חשבונית מס קבלה', 'refund' => 2],
        3 => ['name' => 'קבלה מלכ"ר / פטור מע"מ', 'refund' => 4]
    ];

    protected $username;
    protected $terminal;
    protected $cancelPwd;

    protected $successURL;
    protected $failURL;
    protected $notifyURL;
    protected $cssURL;

    protected $department;
    protected $invoiceType;
    protected $cardCheckType;

    protected $logging;
    protected $client;

    /**
     * @var Transaction
     */
    private $transaction = null;

    public function __construct($term, $user, $pwd = '', $log = false) {
        $this->username  = $user;
        $this->terminal  = $term;
        $this->cancelPwd = $pwd;
        $this->logging   = $log;

        $this->client  = new SoapClient(self::REMOTE_URL, ['encoding' => 'UTF-8', 'cache_wsdl' => WSDL_CACHE_BOTH, 'keep_alive' => false, 'trace' => $log]);
    }


    public function send($method, $request, $step)
    {
        if ($this->transaction)
            $record = $this->transaction->add_record($request, $step);

        $result = $this->client->$method($request);

        if ($this->transaction)
            $record->update($result);

        return $result;
    }


    public function send_post($target, $request, $step = 1)
    {
        $link = curl_init(self::REMOTE_URL_POST . $target);

        curl_setopt($link, CURLOPT_POST, true);
        curl_setopt($link, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($link, CURLOPT_FAILONERROR, true);
        curl_setopt($link, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($link, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($link, CURLOPT_POSTFIELDS, http_build_query($request));

        if ($this->transaction)
            $record = $this->transaction->add_record($request, $step);

        $result = curl_exec($link);

        if ($this->transaction)
            $record->update($result);

        return $result;
    }


    public function send_api($target, $request, $step = 1)
    {
        $link = curl_init(self::REMOTE_URL_API . $target);

        curl_setopt($link, CURLOPT_POST, true);
        curl_setopt($link, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($link, CURLOPT_FAILONERROR, true);
        curl_setopt($link, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($link, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($link, CURLOPT_POSTFIELDS, json_encode($request, JSON_UNESCAPED_UNICODE));
        curl_setopt($link, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
        ));

        if ($this->transaction)
            $record = $this->transaction->add_record($request, $step);

        $result = curl_exec($link);

        if ($this->transaction)
            $record->update($result);

        return $result;
    }


    public function initFramePay($data){
        $this->transaction = Transaction::create($this->ownerID, 'frame_pay', 'CardCom', $data['sum'], array_merge($data, ['_owner' => $this->ownerID]));
        $transID = $this->transaction->id();

        $params = [
            'Operation'   => $this->has_tokens ? 'BillAndCreateToken' : 'BillOnly',
            'ReturnValue' => $transID,
            'SumToBill'   => $data['sum'],
            'ProductName' => $data['description'],
            'CoinID'      => 1,
            'Language'    => 'he',
            'APILevel'    => 10,
            'Codepage'    => 65001,
            'CreditType'  => 1,
            'HideCVV'     => false,
            'MinNumOfPayments'     => 1,
            'MaxNumOfPayments'     => 12,
            'DefaultNumOfPayments' => 1,
            'SuccessRedirectUrl'   => $this->successURL,
            'ErrorRedirectUrl'     => $this->failURL,
            'IndicatorUrl'         => $this->notifyURL,
            'InvoiceHeadOperation' => $this->has_invoice ? 1 : 0,
            'CardOwnerName'        => trim($data['customerName']),
            'CardOwnerPhone'       => $data['phone'],
            'CardOwnerEmail'       => $data['email'],
            'HideCreditCardUserId' => false,
            'IsCreateInvoice'      => $this->has_invoice,
            //'ShowInvoiceHead'      => 'true',
            'ShowCardOwnerEmail'   => 'true',
            'ReqCardOwnerEmail'    => 'true',
            'ShowCardOwnerPhone'   => 'true',
            'ReqCardOwnerPhone'    => 'true',
            'DocTypeToCreate'      => $this->has_invoice ? ($this->invoiceType ?: 1) : 0,
            'InvoiceHead'          => [
                'CustName'     => trim($data['customerName']),
                'CustMobilePH' => $data['phone'],
                'CoinID'       => 1,
                'Email'        => $data['email'],
                'ExternalId'   => $data['orderID'],
                'SendByEmail'  => $this->has_invoice,
                'ExtIsVatFree' => !$this->has_VAT,
                'Comments'     => $data['comment'] ?? ''
            ],
            'InvoiceLines'         => [
                'InvExtHeadLines' => [
                    [
                        'Description'       => $data['description'],
                        'Quantity'          => 1,
                        'IsPriceIncludeVAT' => true,
                        'IsVatFree'         => false,
                        'Price'             => $data['sum'],
                        'ProductID'         => '',
                        'TotalLineCost'     => $data['sum']
                    ]
                ]
            ]
        ];

        if ($this->terminal == 124261)
            $params['ShowInvoiceHead'] = 'true';

        if ($this->cssURL)
            $params['CSSUrl'] = $this->cssURL;

        if ($this->has_swipe){
            $params['IsVirtualTerminalMode'] = 'true';
            $params['IsOpenSum']             = 'false';
            $params['ChargeOnSwipe']         = 'true';
        }

        if ($tmp = intval($this->department))
            $params['InvoiceHead']['DepartmentId'] = $tmp;

        $res    = $this->send('CreateLowProfileDeal', ['lowprofileParams' => $params, 'terminalnumber' => $this->terminal, 'username' => $this->username, 'UserID' => 0], 1);
        $result = $res->CreateLowProfileDealResult;

        if (!empty($result->LowProfileCode))
            $this->transaction->update(['exID' => $result->LowProfileCode]);

        if (strcmp($result->ResponseCode, '0')){
            $final = [
                'success'  => false,
                'ccode'    => $result->ResponseCode,
                'error'    => $result->Description,
                'sum'      => $data['sum'],
                'exID'     => $result->LowProfileCode,
                '_transID' => $transID,
                '_type'    => 'payment',
                '_account' => $this->ownerID
            ];

            $this->transaction->complete(false, $final, $result->Description);

            throw new Exception('שגיאה ' . $result->ResponseCode . ': ' . $result->Description);
        }

        $this->transaction->add_record($result->url, 2);

        return [
            'transID' => $transID,
            'url'     => $result->url
        ];
    }


    public function directPay($sum, $desc, $tokenData, $_rel = 0, $payments = 1, $authNum = null)
    {
        $this->transaction = Transaction::create($this->ownerID, 'direct_pay', 'CardCom', $sum, ['_owner' => $this->ownerID, 'rel' => $_rel, 'sum' => $sum, 'description' => $desc]);
        $transID = $this->transaction->id();

        $params = [
            'Token'             => $tokenData['token'],
            'CardValidityMonth' => intval(substr($tokenData['exp'], -2)),
            'CardValidityYear'  => 2000 + intval(substr($tokenData['exp'], 0, 2)),
            'IdentityNumber'    => trim($tokenData['tz']),
            'SumToBill'         => round($sum, 2),
            'SumInStars'        => 0,
            'CoinID'            => 1,
            'NumOfPayments'     => $payments,
            'RefundInsteadOfCharge' => false,
            'CardOwnerName'     => $tokenData['name'],
            'CardOwnerPhone'    => $tokenData['phone'],
            'CardOwnerEmail'    => $tokenData['email'],
            'DocTypeToCreate'   => $this->has_invoice ? ($this->invoiceType ?: 1) : 0,
            'IsCreateInvoice'   => $this->has_invoice,
            'SendNote'          => false,
            'UniqAsmachtaReturnOriginal' => false,
            'InvoiceHead'          => [
                'CustName'     => $tokenData['name'],
                'CustMobilePH' => $tokenData['phone'],
                'CoinID'       => 1,
                'Email'        => $tokenData['email'],
                'ExternalId'   => $_rel,
                'SendByEmail'  => $this->has_invoice,
                'ExtIsVatFree' => !$this->has_VAT,
                'Comments'     => ''
            ],
            'InvoiceLines'         => [
                'InvExtHeadLines' => [
                    [
                        'Description'       => $desc,
                        'Quantity'          => 1,
                        'IsPriceIncludeVAT' => true,
                        'IsVatFree'         => false,
                        'Price'             => $sum,
                        'ProductID'         => '',
                        'TotalLineCost'     => $sum
                    ]
                ]
            ]
        ];

        $params['CardExpireDate'] = date('Y-m-t', mktime(0, 0, 0, $params['CardValidityMonth'], 1, $params['CardValidityYear'])) . 'T23:59:59';

        if ($authNum)
            $params['ApprovalNumber'] = $authNum;
        if ($tokenData['cvv'])
            $params['CVV2'] = $params['CVV'] = $tokenData['cvv'];
        if ($this->cssURL)
            $params['CSSUrl'] = $this->cssURL;
        if ($tmp = intval($this->department))
            $params['InvoiceHead']['DepartmentId'] = $tmp;

        $res    = $this->send('LowProfileChargeToken', ['tokenToCharge' => $params, 'terminalnumber' => $this->terminal, 'username' => $this->username], 1);
        $result = $res->LowProfileChargeTokenResult;

        $success = (strlen($result->ResposeCode) && strcmp($result->ResposeCode, '0') == 0) ? true : false;
        $final = typemap([
            'success'  => $success,
            'ccode'    => $result->ResposeCode,
            'error'    => $success ? '' : $result->Description,
            'sum'      => round($result->shvaRespons->_Sum_36 / 100, 2),
            'name'     => $tokenData['name'],
            'passport' => trim($tokenData['tz']),
            'phone'    => $tokenData['phone'],
            'email'    => $tokenData['email'],
            'authCode' => $result->shvaRespons->_Approval_Number_71,
            'last4'    => $result->shvaRespons->_Card_Number_5,
            'expires'  => implode('/', str_split($result->shvaRespons->_Tokef_30, 2)),
            'exID'     => $result->InternalDealNumber,
            'invoice'  => $result->InvoiceResponse->InvoiceNumber,
            'invoiceURL' => ($result->InvoiceResponse->InvoiceNumber > 0) ? '+' : '',
            'invoiceType' => $result->InvoiceResponse->InvoiceType,
            '_transID' => $transID,
            '_type'    => 'payment',
            '_account' => $this->ownerID
        ], ['string' => 'string']);

        $this->transaction->update(['exID' => $final['exID']])->complete($success, $final, $success ? '' : $result->Description);

        return $final;
    }


    public function directRefund($sum, $desc, $tokenData, $_rel = 0, $payments = 1)
    {
        $this->transaction = Transaction::create($this->ownerID, 'direct_refund', 'CardCom', $sum, ['_owner' => $this->ownerID, 'rel' => $_rel, 'sum' => $sum, 'description' => $desc]);
        $transID = $this->transaction->id();

        $params = [
            'Token'             => $tokenData['token'],
            'CardValidityMonth' => intval(substr($tokenData['exp'], -2)),
            'CardValidityYear'  => 2000 + intval(substr($tokenData['exp'], 0, 2)),
            'IdentityNumber'    => trim($tokenData['tz']),
            'SumToBill'         => round($sum, 2),
            'SumInStars'        => 0,
            'CoinID'            => 1,
            'NumOfPayments'     => $payments,
            'RefundInsteadOfCharge' => true,
            'UserPassword'      => $this->cancelPwd,
            'CardOwnerName'     => $tokenData['name'],
            'CardOwnerPhone'    => $tokenData['phone'],
            'CardOwnerEmail'    => $tokenData['email'],
            'DocTypeToCreate'   => $this->has_invoice ? static::$_invoiceList[$this->invoiceType ?: 1]['refund'] : 0,
            'IsCreateInvoice'   => $this->has_invoice,
            'SendNote'          => false,
            'UniqAsmachtaReturnOriginal' => false,
            'InvoiceHead'          => [
                'CustName'     => $tokenData['name'],
                'CustMobilePH' => $tokenData['phone'],
                'CoinID'       => 1,
                'Email'        => $tokenData['email'],
                'ExternalId'   => $_rel,
                'SendByEmail'  => $this->has_invoice,
                'ExtIsVatFree' => !$this->has_VAT,
                'Comments'     => ''
            ],
            'InvoiceLines'         => [
                'InvExtHeadLines' => [
                    [
                        'Description'       => $desc,
                        'Quantity'          => 1,
                        'IsPriceIncludeVAT' => true,
                        'IsVatFree'         => false,
                        'Price'             => $sum,
                        'ProductID'         => '',
                        'TotalLineCost'     => $sum
                    ]
                ]
            ]
        ];

        $params['CardExpireDate'] = date('Y-m-t', mktime(0, 0, 0, $params['CardValidityMonth'], 1, $params['CardValidityYear'])) . 'T23:59:59';

        if ($tokenData['cvv'])
            $params['CVV2'] = $params['CVV'] = $tokenData['cvv'];

        if ($this->cssURL)
            $params['CSSUrl'] = $this->cssURL;

        if ($tmp = intval($this->department))
            $params['InvoiceHead']['DepartmentId'] = $tmp;

        $res    = $this->send('LowProfileChargeToken', ['tokenToCharge' => $params, 'terminalnumber' => $this->terminal, 'username' => $this->username], 1);
        $result = $res->LowProfileChargeTokenResult;

        $success = (strlen($result->ResposeCode) && strcmp($result->ResposeCode, '0') == 0) ? true : false;
        $final = typemap([
            'success'  => $success,
            'ccode'    => $result->ResposeCode,
            'error'    => $success ? '' : $result->Description,
            'sum'      => round($result->shvaRespons->_Sum_36 / 100, 2),
            'name'     => $tokenData['name'],
            'passport' => trim($tokenData['tz']),
            'phone'    => $tokenData['phone'],
            'email'    => $tokenData['email'],
            'authCode' => $result->shvaRespons->_Approval_Number_71,
            'last4'    => $result->shvaRespons->_Card_Number_5,
            'expires'  => implode('/', str_split($result->shvaRespons->_Tokef_30, 2)),
            'exID'     => $result->InternalDealNumber,
            'invoice'  => $result->InvoiceResponse->InvoiceNumber,
            'invoiceURL'  => $result->InvoiceResponse->InvoiceNumber ? '+' : '',
            'invoiceType' => $result->InvoiceResponse->InvoiceType,
            '_transID' => $transID,
            '_type'    => 'payment',
            '_account' => $this->ownerID
        ], ['string' => 'string']);

        $this->transaction->update(['exID' => $final['exID']])->complete($success, $final, $success ? '' : $result->Description);

        return $final;
    }


    public function initFreezeSum($sum, $desc, $cardData = []){
        if ($this->cardCheckType != 5)
            throw new Exception(Dictionary::translate('Suspended deals are not allowed for this terminal'));

        $this->transaction = Transaction::create($this->ownerID, 'frame_freeze_sum', 'CardCom', $sum, ['_owner' => $this->ownerID]);
        $transID = $this->transaction->id();

        $params = [
            'Operation'   => 'CreateTokenOnly',
            'ReturnValue' => $transID,
            'SumToBill'   => $sum,
            'ProductName' => $desc,
            'CoinID'      => 1,
            'Language'    => 'he',
            'APILevel'    => 10,
            'Codepage'    => 65001,
            'CreditType'  => 1,
            'HideCVV'     => false,
            'MinNumOfPayments'     => 1,
            'MaxNumOfPayments'     => 1,
            'DefaultNumOfPayments' => 1,
            'SuccessRedirectUrl'   => $this->successURL,
            'ErrorRedirectUrl'     => $this->failURL,
            'IndicatorUrl'         => $this->notifyURL,
            'InvoiceHeadOperation' => 0,
            'CardOwnerName'        => trim($cardData['customerName'] ?? ''),
            'CardOwnerPhone'       => $cardData['phone'] ?? '',
            'CardOwnerEmail'       => $cardData['email'] ?? '',
            'HideCreditCardUserId' => false,
            'IsCreateInvoice'      => false,
            'ShowCardOwnerEmail'   => 'true',
            'ReqCardOwnerEmail'    => 'true',
            'ShowCardOwnerPhone'   => 'true',
            'ReqCardOwnerPhone'    => 'true',
            'InvoiceHead'          => [
                'CoinID'       => 1,
                'SendByEmail'  => false
            ],
            'InvoiceLines'         => [],
            'CreateTokenJValidateType' => 5       // 5 = J5, 2 = J2
        ];

        if ($this->cssURL)
            $params['CSSUrl'] = $this->cssURL;

        if ($this->has_swipe){
            $params['IsVirtualTerminalMode'] = true;
            $params['IsOpenSum']             = false;
            $params['ChargeOnSwipe']         = true;
        }

        $res    = $this->send('CreateLowProfileDeal', ['lowprofileParams' => $params, 'terminalnumber' => $this->terminal, 'username' => $this->username, 'UserID' => 0], 1);
        $result = $res->CreateLowProfileDealResult;

        if (!empty($result->LowProfileCode))
            $this->transaction->update(['exID' => $result->LowProfileCode]);

        if (strcmp($result->ResponseCode, '0')){
            $final = [
                'success'  => false,
                'ccode'    => $result->ResponseCode,
                'error'    => $result->Description,
                'sum'      => 1,
                'exID'     => $result->LowProfileCode,
                '_transID' => $transID,
                '_type'    => 'freeze_sum',
                '_account' => $this->ownerID
            ];

            $this->transaction->complete(false, $final, $result->Description);

            throw new Exception('שגיאה ' . $result->ResponseCode . ': ' . $result->Description);
        }

        $this->transaction->add_record($result->url, 2);

        return [
            'transID' => $transID,
            'url'     => $result->url
        ];
    }


    public function initFrameCardTest($needToken = null, $cardData = []){
        if (is_null($needToken))
            $needToken = $this->has_tokens;

        $this->transaction = Transaction::create($this->ownerID, 'frame_card_check', 'CardCom', 1, ['_owner' => $this->ownerID, 'needToken' => $needToken]);
        $transID = $this->transaction->id();

        $params = [
            'Operation'   => $this->has_tokens ? 'CreateTokenOnly' : 'SuspendDealOnly',
            'ReturnValue' => $transID,
            'SumToBill'   => 1,
            'ProductName' => 'בדיקת כרטיס',
            'CoinID'      => 1,
            'Language'    => 'he',
            'APILevel'    => 10,
            'Codepage'    => 65001,
            'CreditType'  => 1,
            'HideCVV'     => false,
            'MinNumOfPayments'     => 1,
            'MaxNumOfPayments'     => 1,
            'DefaultNumOfPayments' => 1,
            'SuccessRedirectUrl'   => $this->successURL,
            'ErrorRedirectUrl'     => $this->failURL,
            'IndicatorUrl'         => $this->notifyURL,
            'InvoiceHeadOperation' => 0,
//            'CardOwnerName'        => '',
//            'CardOwnerPhone'       => '',
//            'CardOwnerEmail'       => '',
            'CardOwnerName'        => trim($cardData['customerName'] ?? ''),
            'CardOwnerPhone'       => $cardData['phone'] ?? '',
            'CardOwnerEmail'       => $cardData['email'] ?? '',
            'HideCreditCardUserId' => false,
            'IsCreateInvoice'      => false,
            'ShowCardOwnerEmail'   => 'true',
            'ReqCardOwnerEmail'    => 'true',
            'ShowCardOwnerPhone'   => 'true',
            'ReqCardOwnerPhone'    => 'true',
            'InvoiceHead'          => [
                'CoinID'       => 1,
                'SendByEmail'  => false
            ],
            'InvoiceLines'         => [],
            'CreateTokenJValidateType' => $this->cardCheckType ?: 2       // 5 = J5, 2 = J2
        ];

        if ($this->cssURL)
            $params['CSSUrl'] = $this->cssURL;

        if ($this->has_swipe){
            $params['IsVirtualTerminalMode'] = 'true';
            $params['IsOpenSum']             = 'false';
            $params['ChargeOnSwipe']         = 'true';
        }

        $res    = $this->send('CreateLowProfileDeal', ['lowprofileParams' => $params, 'terminalnumber' => $this->terminal, 'username' => $this->username, 'UserID' => 0], 1);
        $result = $res->CreateLowProfileDealResult;

        if (!empty($result->LowProfileCode))
            $this->transaction->update(['exID' => $result->LowProfileCode]);

        if (strcmp($result->ResponseCode, '0')){
            $final = [
                'success'  => false,
                'ccode'    => $result->ResponseCode,
                'error'    => $result->Description,
                'sum'      => 1,
                'exID'     => $result->LowProfileCode,
                '_transID' => $transID,
                '_type'    => 'card_check',
                '_account' => $this->ownerID
            ];

            $this->transaction->complete(false, $final, $result->Description);

            throw new Exception('שגיאה ' . $result->ResponseCode . ': ' . $result->Description);
        }

        $this->transaction->add_record($result->url, 2);

        return [
            'transID' => $transID,
            'url'     => $result->url
        ];
    }


    protected function frame_pay_verify($code){
        $params = [
            'terminalnumber' => $this->terminal,
            'username'       => $this->username,
            'lowProfileCode' => $code,
            'codepage'       => 65001
        ];

        return $this->send('GetLowProfileIndicator', $params, 3)->GetLowProfileIndicatorResult;
    }


    public function framePayResult($code){
        return Transaction::getByExId($code)->result;
    }


    public function completeFrameTransaction($data){
        $code = preg_replace('/[^a-z0-9-]+/i', '', $data['lowprofilecode']);

        $this->transaction = Transaction::getByExId($code);
        $transID = $this->transaction->id();

        if ($this->transaction->status == 1 || $this->transaction->result)
            return array_merge(['duplicate' => true], $this->transaction->result);

        $record = $this->transaction->last_record();
        if ($record && $record->step == 2)
            $record->update($data);

        $verified = $this->frame_pay_verify($code);

        if (!$verified || !isset($verified->Indicator->OperationResponse) || !isset($verified->Indicator->ReturnValue) || !isset($verified->Indicator->DealRespone))
            throw new Exception('Incorrect answer from CardCom: ' . print_r($verified, true));
        if ($verified->Indicator->ReturnValue != $transID)
            throw new Exception('Transaction ID does not match: ' . $transID . ' -> ' . print_r($verified, true));

        //$success = (!strcmp($verified->Indicator->OperationResponse, '0') && !strcmp($verified->Indicator->DealRespone, '0'));
        $success = (!strcmp($verified->Indicator->OperationResponse, '0'));

        $final = [
            'success'  => $success,
            'ccode'    => $verified->Indicator->DealRespone,
            'error'    => $verified->Description,
            'sum'      => round($verified->ShvaResponce->Sum36 / 100, 2),
            'name'     => $verified->Indicator->CardOwnerName,
            'passport' => $verified->Indicator->CardOwnerID,
            'phone'    => $verified->Indicator->CardOwnerPhone,
            'email'    => $verified->Indicator->CardOwnerEmail,
            'authCode' => $verified->ShvaResponce->ApprovalNumber71,
            'last4'    => $verified->ShvaResponce->CardNumber5,
            'expires'  => implode('/', str_split($verified->ShvaResponce->Tokef30, 2)),
            'exID'     => $verified->ShvaResponce->InternalDealNumber,
            '_transID' => $transID,
            '_type'    => ($this->transaction->transType == 'frame_card_check') ? 'card_test' : (($this->transaction->transType == 'frame_freeze_sum') ? 'freeze_sum' : 'payment'),
            '_account' => $this->ownerID
        ];

        if ($verified->Indicator->InvoiceNumber){
            $final['invoice'] = $verified->Indicator->InvoiceNumber;
            $final['invoiceType'] = $verified->Indicator->InvoiceType;
        }

        if ($this->has_tokens && $verified->Indicator->Token){
            $final['hasToken']  = true;
            $final['tokenData'] = [
                'token'  => $verified->Indicator->Token,
                'exp'    => implode('', array_reverse(str_split($verified->ShvaResponce->Tokef30, 2))),
                'name'   => $verified->Indicator->CardOwnerName,
                'email'  => $verified->Indicator->CardOwnerEmail,
                'tz'     => trim($final['passport']),
                'phone'  => $verified->Indicator->CardOwnerPhone,
                'parent' => $verified->Indicator->Terminal_Number
            ];
        }

        //$this->transaction->update(['exID' => $final['exID']])->complete($success, $final, $success ? '' : $final['error']);
        $this->transaction->complete($success, $final, $success ? '' : $final['error']);

        return $final;
    }


    public function unfreezeSum($sum, $tokenData, $authNum,  $_rel = 0)
    {
        $this->transaction = Transaction::create($this->ownerID, 'unfreeze_sum', 'CardCom', $sum, ['_owner' => $this->ownerID, 'rel' => $_rel, 'sum' => $sum]);
        $transID = $this->transaction->id();

        $params = [
            'MTI'               => 420,             // constant value to "release frozen sum"
            'Token'             => $tokenData['token'],
            'CardValidityMonth' => intval(substr($tokenData['exp'], -2)),
            'CardValidityYear'  => 2000 + intval(substr($tokenData['exp'], 0, 2)),
            'IdentityNumber'    => trim($tokenData['tz']),
            'ApprovalNumber'    => $authNum,
            'SumToBill'         => 2.2,
            'SumInStars'        => 0,
            'CoinID'            => 1,
            'NumOfPayments'     => 1,
            'RefundInsteadOfCharge' => false,
            'CardOwnerName'     => $tokenData['name'],
            'CardOwnerPhone'    => $tokenData['phone'],
            'CardOwnerEmail'    => $tokenData['email'],
            'DocTypeToCreate'   => 0,
            'IsCreateInvoice'   => false,
            'SendNote'          => false,
            'UniqAsmachtaReturnOriginal' => false,
            'InvoiceHead'          => [
                'CoinID'        => 1,
                'SendByEmail'   => false
            ],
            'InvoiceLines'         => []
        ];

        $params['CardExpireDate'] = date('Y-m-t', mktime(0, 0, 0, $params['CardValidityMonth'], 1, $params['CardValidityYear'])) . 'T23:59:59';

        if ($tokenData['cvv'])
            $params['CVV2'] = $params['CVV'] = $tokenData['cvv'];

        $res    = $this->send('LowProfileChargeToken', ['tokenToCharge' => $params, 'terminalnumber' => $this->terminal, 'username' => $this->username], 1);
        $result = $res->LowProfileChargeTokenResult;

        $success = (strlen($result->ResposeCode) && strcmp($result->ResposeCode, '0') == 0) ? true : false;
        $final = typemap([
            'success'  => $success,
            'ccode'    => $result->ResposeCode,
            'error'    => $success ? '' : $result->Description,
            'sum'      => round($result->shvaRespons->_Sum_36 / 100, 2),
            'name'     => $tokenData['name'],
            'passport' => trim($tokenData['tz']),
            'phone'    => $tokenData['phone'],
            'email'    => $tokenData['email'],
            'authCode' => $result->shvaRespons->_Approval_Number_71,
            'last4'    => $result->shvaRespons->_Card_Number_5,
            'expires'  => implode('/', str_split($result->shvaRespons->_Tokef_30, 2)),
            'exID'     => $result->InternalDealNumber,
            'invoice'  => $result->InvoiceResponse->InvoiceNumber,
            'invoiceURL' => ($result->InvoiceResponse->InvoiceNumber > 0) ? '+' : '',
            'invoiceType' => $result->InvoiceResponse->InvoiceType,
            '_transID' => $transID,
            '_type'    => 'unfreeze_sum',
            '_account' => $this->ownerID
        ], ['string' => 'string']);

        $this->transaction->update(['exID' => $final['exID']])->complete($success, $final, $success ? '' : $result->Description);

        return $final;
    }

    public function payCancel($exID, $clientData, $cancelOnly = true)
    {
        $params = [
            'terminalnumber'     => $this->terminal,
            'name'               => $this->username,
            'pass'               => $this->cancelPwd,
            'InternalDealNumber' => $exID
        ];

        if ($cancelOnly){        // cancelling transaction, NOT refunding
            $params['CancelOnly'] = 'true';
            $this->transaction = new Transaction($clientData['_transID']);
        }

        $result = $this->send_post('CancelDeal.aspx', $params, 10);

        parse_str($result, $data);

        $final = [
            'success'  => (strlen($data['ResponseCode']) && strcmp($data['ResponseCode'], '0') == 0) ? true : false,
            'ccode'    => $data['ResponseCode'],
            'error'    => $data['Description'],
            'exID'     => $data['InternalDealNumber'],
            '_transID' => $this->transaction->id(),
            '_type'    => $cancelOnly ? 'pay_cancel' : 'pay_refund',
        ];

        if ($final['success'] && $this->has_invoice){
            $input = $this->transaction->input;

            $clientData['cc_deal'] = $data['InternalDealNumber'];

            $print = $this->sendPrintout('__cancel', $this->transaction->sum, 'זיכוי תשלום', $clientData, $input['rel'] ?: $clientData['_transID'], 2, false);

            $final['invoiceURL']  = '+';
            $final['invoice']     = $print['invoice'];
            $final['invoiceData'] = $print;
        }

        $this->transaction->complete($final['success'], $this->transaction->result ? array_merge($this->transaction->result, ['cancelData' => $final]) : $final, $final['success'] ? '' : $data['Description']);

        return $final;
    }

    public function payRefund($exID, $sum, $clientData)
    {
        $this->transaction = Transaction::create($this->ownerID, 'pay_refund', 'CardCom', $sum, ['_owner' => $this->ownerID, 'exID' => $exID]);

        return $this->payCancel($exID, $clientData, false);
    }


    public function sendPrintout($type, $sum, $desc, $clientData, $_rel = 0, $docType = 0, $newTrans = true)
    {
        if (!$this->has_invoice)
            throw new Exception("Invoices aren't allowed for this terminal");

        if ($newTrans)
            $this->transaction = Transaction::create($this->ownerID, 'invoice_request', 'CardCom', 1, ['_owner' => $this->ownerID, 'rel' => $_rel, 'sum' => $sum, 'description' => $desc, 'client' => $clientData]);

        $params = [
            'terminalnumber' => $this->terminal,
            'username'       => $this->username,
            'codepage'       => 65001,
            'InvoiceType'    => ($docType ?: $this->invoiceType) ?: 1,
            'InvoiceHead.CustName'      => $clientData['full_name'] ?: $clientData['name'],
            'InvoiceHead.SendByEmail'   => 'true',
            'InvoiceHead.Language'      => 'he',
            'InvoiceHead.Email'         => $clientData['email'],
            'InvoiceHead.CustMobilePH'  => $clientData['phone'],
            'InvoiceHead.CompID'        => ($clientData['tz'] ?: $clientData['passport']),
            'InvoiceHead.Comments'      => $clientData['comment'],
            'InvoiceHead.CoinID'        => 1,          // 1 - NIS
            'InvoiceHead.SiteUniqueId'  => $_rel,
            'InvoiceHead.ExtIsVatFree'  => $this->has_VAT ? 'false' : 'true',
            'InvoiceLines.Description'  => $desc,
            'InvoiceLines.Price'        => $sum,
            'InvoiceLines.Quantity'     => 1,
            'InvoiceHead.IsAutoCreateUpdateAccount' => 'false'
        ];

        if ($tmp = intval($this->department))
            $params['InvoiceHead.DepartmentId'] = $tmp;

        if ($type == '__cancel')
            $params['CreditDealNum.DealNumber'] = $clientData['cc_deal'];
        elseif (isset(static::$_customPayTypeMap[$type])){
            $pm = static::$_customPayTypeMap[$type];

            if (is_int($pm[0]))
                $params = array_merge($params, [
                    'CustomPay.TransactionID' => $pm[0],
                    'CustomPay.TranDate'      => date('d/m/Y'),
                    'CustomPay.Description'   => $pm[1],
                    'CustomPay.Asmacta'       => $clientData['authNum'],
                    'CustomPay.Sum'           => $sum
                ]);
            else {
                switch($pm[0]){
                    case 'Cash':
                        $params['cash'] = $sum;
                        break;

                    case 'Check':
                        $params = array_merge($params, [
                            'Cheque.ChequeNumber'  => $clientData['docNum'],
                            'Cheque.BankNumber'    => $clientData['bank'],
                            'Cheque.SnifNumber'    => $clientData['branch'],
                            'Cheque.AccountNumber' => $clientData['pan'],
                            'Cheque.DateCheque'    => is_numeric($clientData['docDate']) ? implode('/', [substr($clientData['docDate'], -2), substr($clientData['docDate'], 4, 2), substr($clientData['docDate'], 0, 4)]) : $clientData['docDate'],
                            'Cheque.Sum'           => $sum
                        ]);
                        break;

                    case 'CCard':
                        $params['CreditDealNum.DealNumber'] = $clientData['cc_deal'];
                        break;

                    default:
                        throw new Exception("Unknown payment method for CardCom");
                }
            }
        }

        $result = $this->send_post('CreateInvoice.aspx', $params, 20);

        parse_str($result, $data);

        $final = typemap([
            'ccode'       => $data['ResponseCode'],
            'error'       => $data['Description'],
            'exID'        => $data['InternalDealNumber'],
            'sum'         => $sum,
            'name'        => $clientData['full_name'] ?: $clientData['name'],
            'passport'    => $clientData['tz'] ?: $clientData['passport'],
            'email'       => $clientData['email'],
            'invoice'     => $data['InvoiceNumber'],
            'invoiceType' => intval($data['InvoiceType'] ?: $docType ?: $this->invoiceType ?: 1),
            '_transID'    => $this->transaction->id(),
            '_type'       => 'invoice_request',
            '_account'    => $this->ownerID
        ], ['string' => 'string']);

        $final['success']     = (strlen($data['ResponseCode']) && strcmp($data['ResponseCode'], '0') == 0) ? true : false;

        if ($final['success'])
            $final['invoiceURL'] = '+';

        if ($newTrans)
            $this->transaction->complete($final['success'], $final, $final['success'] ? '' : $data['Description']);

        return $final;
    }


    public function downloadPrintout($invoice, $docType)
    {
        if (!$this->has_invoice)
            throw new Exception("Invoices aren't allowed for this terminal");

        $this->transaction = null;

        $params = [
            'UserName'       => $this->username,
            'UserPassword'   => $this->cancelPwd,
            'DocumentNumber' => $invoice,
            'DocumentType'   => $docType
        ];

        return $this->send_post('GetDocumentPDF.aspx', $params);
    }


    public function sendPrintoutsToEmail($from, $till, $email)
    {
        $params = [
            'ApiName'          => $this->username,
            'ApiPassword'      => $this->cancelPwd,
            'EmailTo'          => $email,
            'FromDateYYYYMMDD' => str_replace('-', '', $from),
            'ToDateYYYYMMDD'   => str_replace('-', '', $till),
            'SendEmptyEmail'   => true,
            'ForceOriginal'    => false,
            'DocumentType'     => -1
        ];

        $res    = $this->send_api('Documents/SendAllDocumentsToEmail', $params, 100);
        $result = json_decode($res);

        return [
            'success' => $result->ResponseCode ? false : true,
            'ccode'   => $result->ResponseCode,
            'error'   => $result->ResponseCode ? $result->Description : ''
        ];
    }


    public function refund_invoice_type($type = 0){
        return static::$_invoiceList[$type ?: $this->invoiceType ?: 1]['refund'];
    }

    public function engine(){
        return 'CardCom';
    }
}
