<?php
class CouponManager {
    public static function getCoupon($number, $siteID = 0){
        $where = ["p.ordersID = '" . udb::escape_string($number) . "' AND p.status = 1"];

        if ($siteID)
            $where[] = "(p.siteID = " . intval($siteID) . " OR p.siteID = 0)";

        $que = "SELECT p.pID AS `couponID`, p.siteID, p.giftCardID AS `cardID`, p.ordersID AS `couponNumber`, p.transDate AS `purchased`, p.reciveTime + INTERVAL p.validMonths MONTH AS `expires`
                    , p.voucherSum AS `sum`, SUM(IFNULL(pay.useageSum, 0)) AS `used`
                FROM `gifts_purchases` AS `p` INNER JOIN `giftCards` AS `g` USING(`giftCardID`)
                    LEFT JOIN `giftCardsUsage` AS `pay` USING(`pID`)
                WHERE " . implode(' AND ', $where) . "
                GROUP BY p.pID
                ORDER BY NULL
                LIMIT 1";
        $coupon = udb::single_row($que);

        return $coupon ? new BizCoupon($coupon) : false;
    }
}


class BizCoupon {
    public $siteID;
    public $cardID;
    public $couponID;
    public $couponNumber;
    public $purchased;
    public $expires;
    public $sum;
    public $used;

    public $available = 0;
    public $active    = false;

    public function __construct($data = [])
    {
        foreach($data as $key => $val)
            if (property_exists($this, $key))
                $this->$key = $val;

        if ($this->couponID && $this->sum > 0 && round($this->sum - $this->used) > 0 && strcmp($this->expires, date('Y-m-d H:i:s')) >= 0){
            $this->active = true;
            $this->available = max(0, round($this->sum - $this->used, 2));
        }
    }

    public function charge($sum, $comment = '')
    {
        if (floatval($sum) <= 0)
            throw new Exception('Illegal sum to charge');
        if (!$this->active)
            throw new Exception('Coupon already used up or expired');
        if ($this->available < $sum)
            throw new Exception('Not enough funds left at coupon');

        udb::query("LOCK TABLES `giftCardsUsage` WRITE, `gifts_purchases` AS `p` READ");

        $que = "SELECT ROUND(p.voucherSum - " . floatval($sum) . " - SUM(IFNULL(giftCardsUsage.useageSum, 0)))
                FROM `gifts_purchases` AS `p` LEFT JOIN `giftCardsUsage` USING(`pID`)
                WHERE p.pID = " . $this->couponID;
        $res = udb::single_value($que);

        if (intval($res) < 0)
            throw new Exception('Coupon already used up or expired');

        $useID = udb::insert('giftCardsUsage', [
            'pID'        => $this->couponID,
            'giftCardID' => $this->cardID,
            'useageSum'   => round($sum, 2),
            'comments'   => $comment
        ]);

        udb::query("UNLOCK TABLES");

        $this->used     += round($sum, 2);
        $this->available = round($this->sum - $this->used, 2);
        $this->active    = ($this->available > 0);

        return $useID;
    }

    public function unUse_by_id($useID, $user = 0, $source = '')
    {
        $row = udb::single_row("SELECT * FROM `giftCardsUsage` WHERE `useID` = " . intval($useID) . " AND `pID` = " . $this->couponID);
        if (!$row)
            return false;

        udb::insert('giftCardsUsageDeleteLog', [
            'id'     => $row['useID'],
            'pID'    => $row['pID'],
            'userID' => intval($user),
            'source' => $source,
            'data'   => json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ]);

        udb::query("DELETE FROM `giftCardsUsage` WHERE `useID` = " . intval($useID) . " AND `pID` = " . $this->couponID);

        return true;
    }

    public function unUse_by_time_sum($datetime, $sum, $user = 0, $source = '')
    {
        for($i = 0; $i < 3; ++$i)
            if ($uses = udb::single_column("SELECT `useID` FROM `giftCardsUsage` WHERE `pID` = " . $this->couponID . " AND `useageSum` = " . floatval($sum) . " AND `usageDate` BETWEEN '" . udb::escape_string($datetime) . "' - INTERVAL " . $i . " SECOND AND '" . udb::escape_string($datetime) . "' + INTERVAL " . $i . " SECOND"))
                break;

        if (empty($uses) || count($uses) > 1)
            return false;

        return $this->unUse_by_id($uses[0], $user, $source);
    }
}
