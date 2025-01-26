<?php
class UserActionLog {
    const USER_CLIENT = -1;

    public static $action_list = [
        'preorder'       => 'יצירת שיריון',
        'order'          => 'יצירת הזמנה',
        'order_update'   => 'עדכון הזמנה',
        'order_cancel'   => 'ביטול הזמנה',
        'order_restore'  => 'שחזור הזמנה',
        'payment'        => 'הוספת תשלום',
        'payment_cancel' => 'ביטול תשלום',
        'payment_refund' => 'החזר תשלום',
        'order_sg_sent'  => 'נשלח לחתימה',
        'order_sg_done'  => 'חתום',
        'order_treat_delete' => 'הסרת טיפול'
    ];

    public $userID;
    public $siteID;
    public $orderID;

    public function __construct($userID = 0, $siteID = 0, $orderID = 0){
        $this->siteID  = $siteID ?: 0;
        $this->orderID = $orderID ?: 0;

        $this->setUser($userID ?: (new TfusaUser)->id() ?: 0);
    }

    public function setUser($user){
        return $this->userID = is_a($user, 'TfusaUser') ? $user->id() : intval($user);
    }

    public function save($action, $siteID = -1, $orderID = -1, $extra = ''){
        $data = [
            'buserID' => $this->userID,
            'siteID'  => ($siteID >= 0) ? $siteID : $this->siteID,
            'orderID' => ($orderID >= 0) ? $orderID : $this->orderID,
            'actionType' => $action
        ];

        if ($extra)
            $data['extra'] = json_encode($extra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        udb::insert('bu_action_log', $data);
    }

    public static function getData($userID, $siteID, $orderID, $extra = []){
        $where = [];

        if ($userID >= 0)
            $where[] = "`buserID` = " . $userID;
        if ($siteID >= 0)
            $where[] = "`siteID` " . (is_array($siteID) ? " IN (" . implode(',', array_map('intval', $siteID)) . ")" : " = " . $siteID);
        if ($orderID >= 0)
            $where[] = "`orderID` " . (is_array($orderID) ? " IN (" . implode(',', array_map('intval', $orderID)) . ")" : " = " . $orderID);

        foreach($extra as $key => $val)
            if (is_int($key))
                $where[] = $val;
            else
                $where[] = "`" . $key . "` IN ('" . (is_array($val) ? implode("','", $val) : udb::escape_string($val)) . "')";

        return udb::single_list("SELECT * FROM `bu_action_log` WHERE " . implode(' AND ', $where) . " ORDER BY `logID`");
    }

    public static function getLogForOrder($orderID, $extra = []){
        return self::getData(-1, -1, $orderID, $extra);
    }

    public static function getLogForSite($siteID, $extra = []){
        return self::getData(-1, $siteID, -1, $extra);
    }

    public function getLogForUser($userID, $extra = []){
        return self::getData($userID, -1, -1, $extra);
    }

    public static function actionName($code){
        return self::$action_list[$code] ?: $code;
    }
}
