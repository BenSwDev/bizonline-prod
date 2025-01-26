<?php
require_once "auth.php";

$result = new JsonResult(['status' => 99]);

try {
    $act = typemap($_POST['act'], 'string');

    switch($act){
        case 'refundDirect':
            if (!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN))
                throw new Exception(blockAccessMsg());

            $pid = typemap($_POST['pid'], 'int');

            $purchase = udb::single_row("SELECT * FROM `gifts_purchases` WHERE `pID` = " . $pid);

            if (!$purchase || strcmp($purchase['terminal'], 'direct') || !$purchase['transID'])
                throw new Exception("Cannot find a purchase for this gift card");
            if (!$_CURRENT_USER->has($purchase['siteID']))
                throw new Exception('Access denied to this purchase');
            if (!Terminal::hasTerminal($purchase['siteID'], 'vouchers'))
                throw new Exception("Terminal is inactive or missing required data");

            // pulling all purchases for same transaction
            $pid_list = udb::single_column("SELECT `pID` FROM `gifts_purchases` WHERE `transID` = " . $purchase['transID']);

            udb::query("LOCK TABLES `giftCardsUsage` WRITE");

            $used = udb::single_value("SELECT SUM(`useageSum`) FROM `giftCardsUsage` WHERE `pID` IN (" . implode(',', $pid_list) . ")");
            if ($used > 0)
                throw new Exception("Cannot refund " . ((count($pid_list) > 1) ? 'some of the gift cards were' : 'a gift card was') . " used already");

            // this is concurrency hack - due to MySQL limitations, to prevent simultaneous operations we pre-add "refund" row to usage table BEFORE actually refunding
            foreach($pid_list as $i)
                udb::insert('giftCardsUsage', [
                    'pID'        => $i,
                    'giftCardID' => $purchase['giftCardID'],
                    'useageSum'  => $purchase['sum'],           // all gift card in transaction are the same
                    'comments'   => 'זיכוי',
                    'commission' => -1
                ]);

            udb::query("UNLOCK TABLES");

            try {
                $trans = new Transaction($purchase['transID']);
                if (!$trans->id() || $trans->result['ccode'] > 0 || !$trans->result['exID'])
                    throw new Exception("Cannot find payment transaction");

                $terminal = new CardComGeneral($purchase['siteID'], 'vouchers');
                $res = $terminal->payRefund($trans->result['exID'], $trans->sum, $trans->result);

                if (!$res['success'])
                    throw new Exception($res['error'] ?: 'Failed to refund transaction');

                udb::update('gifts_purchases', ['refunded' => $res['_transID']], "`transID` = " . $purchase['transID']);
                udb::update('giftCardsUsage', ['commission' => 0], "`pID` IN (" . implode(',', $pid_list) . ") AND `giftCardID` = " . $purchase['giftCardID'] . " AND `commission` = -1");
            }
            catch (Exception $ie){
                // if caught here - the refund didn't happen and we MUST delete "usage" row
                udb::query("DELETE FROM `giftCardsUsage` WHERE `pID` IN (" . implode(',', $pid_list) . ") AND `giftCardID` = " . $purchase['giftCardID'] . " AND `commission` = -1");
                throw new Exception($ie->getMessage());
            }
            break;

        case 'deleteUsage':
            $pid  = typemap($_POST['pid'], 'int');
            $uid  = typemap($_POST['uid'], 'int');
            $code = typemap($_POST['code'], 'numeric');

            $purchase = udb::single_row("SELECT `pID`, `giftCardID`, `siteID` FROM `gifts_purchases` WHERE `pID` = " . $pid . " AND `ordersID` = '" . udb::escape_string($code) . "'");

            if (!$purchase)
                throw new Exception("Cannot find a purchase for this gift card");
            if (!$_CURRENT_USER->has($purchase['siteID']))
                throw new Exception('Access denied to this purchase');

            $row = udb::single_row("SELECT * FROM `giftCardsUsage` WHERE `useID` = " . intval($uid) . " AND `pID` = " . $pid . " AND `giftCardID` = " . $purchase['giftCardID'] . " AND `cancellable` = 1");
            if (!$row)
                throw new Exception("Cannot cancel usage for coupon");

            udb::insert('giftCardsUsageDeleteLog', [
                'id'     => $row['useID'],
                'pID'    => $row['pID'],
                'userID' => $_CURRENT_USER->id(),
                'source' => 'manual',
                'data'   => json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ]);

            udb::query("DELETE FROM `giftCardsUsage` WHERE `useID` = " . intval($uid));
            break;

        case 'use':
            $formData = typemap($_POST, [
                'giftcardID' => 'int',
                'pID'        => 'int',
                'sumToUse'   => 'int',
                'comments'   => 'string'
            ]);

            $purData = udb::single_row("SELECT `giftCardID`, `voucherSum`, `siteID`, `terminal` FROM `gifts_purchases` WHERE `pID` = " . $formData['pID']);
            if (!$purData || $purData['giftCardID'] != $formData['giftcardID'])
                throw new Exception('Cannot find gift card purchase ' . $formData['pID']);
            if ($purData['siteID'] && !$_CURRENT_USER->has($purData['siteID']))
                throw new Exception("Access denied to gift card purchase " . $formData['pID']);

            $siteComm = ($purData['terminal'] == 'direct') ? 0 : udb::single_value("SELECT `giftCardCommission` FROM `sites` WHERE `siteID` = " . $purData['siteID']);

            udb::query("LOCK TABLE `giftCardsUsage` WRITE");

            $used = udb::single_value("SELECT SUM(`useageSum`) FROM `giftCardsUsage` WHERE `pID` = " . $formData['pID'] . " AND `giftCardID` = " . $formData['giftcardID']) ?: 0;
            if ($used >= $purData['voucherSum'])
                throw new Exception('Gift card is already fully used');
            if (round($formData['sumToUse'], 1) > round($purData['voucherSum'] - $used, 1))
                throw new Exception('Cannot use ' . $formData['sumToUse'] . ' NIS. Only ' . round($purData['voucherSum'] - $used, 1) . ' NIS left on gift card.');

            $cp = [
                'pID'         => $formData['pID'],
                'giftCardID'  => $formData['giftcardID'],
                'useageSum'   => $formData['sumToUse'],
                'comments'    => $formData['comments'],
                'commission'  => $siteComm ?: 0,
                'cancellable' => 1
            ];

            udb::insert('giftCardsUsage', $cp);

            $result['success'] = true;
            break;

        default:
            throw new Exception("Unknown act: " . $act);
    }

    $result['status'] = 0;
}
catch (Exception $e){
    $result['error'] = $e->getMessage();
}
